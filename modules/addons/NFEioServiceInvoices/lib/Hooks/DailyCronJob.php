<?php

namespace NFEioServiceInvoices\Hooks;

use \WHMCS\Database\Capsule;

/**
 * Classe com execução das rotinas para o gatilho dailycronjob
 * @see https://developers.whmcs.com/hooks-reference/cron/#dailycronjob
 * @author Andre Bellafronte
 * @version 2.1.0
 */
class DailyCronJob
{

    /**
     * @var \NFEioServiceInvoices\Models\ServiceInvoices\Repository
     */
    private $serviceInvoicesRepo;
    /**
     * @var \NFEioServiceInvoices\Configuration
     */
    private $config;
    /**
     * @var \NFEioServiceInvoices\NFEio\Nfe
     */
    private $nf;

    public function __construct()
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->nf = new \NFEioServiceInvoices\NFEio\Nfe();
    }

    public function run()
    {
        $storage = new \WHMCSExpert\Addon\Storage($this->config->getStorageKey());
        $serviceInvoicesTable = $this->serviceInvoicesRepo->tableName();
        $issueNfAfter = $storage->get('issue_note_after');

        // condição que se certifica da existência de configuração para emissão de NF X dias após pgto da fatura
        if (isset($issueNfAfter) && (int)$issueNfAfter > 0) {

            $todayDate = date("Y-m-d");
            // qtd de dias configurado para gerar nf apos pgto
            $issueNoteAfterDays = $issueNfAfter;
            // instancia o dia atual
            $invoicesPaidOnDay = date_create($todayDate);
            // subtrai a quantidade de dias com base no dia atual para chegar no dia que deverá ser verificado
            // a ocorrência do pagamento. Ex.: pega todas as faturas pagas no dia 06/12/2021.
            date_sub($invoicesPaidOnDay, new \DateInterval("P{$issueNoteAfterDays}D"));

            // seleciona todas as faturas que tenham sido pagas no dia calculado em $invoicesPaidOnDay
            $invoicesToGenerateData = Capsule::table('tblinvoices')->whereDate('datepaid', $invoicesPaidOnDay->format('Y-m-d'))->select(['id as invoice_id', 'total'])->get();
            // coleção com os IDs das faturas encontradas
            $invoicesToGenerateID = [];
            // alimenta a coleção com os dados
            if (count($invoicesToGenerateData) > 0) {
                foreach ($invoicesToGenerateData as $invoice) {
                    if ($invoice->total > 0) {
                        $invoicesToGenerateID[] = $invoice->invoice_id;
                    }
                }
            }

            // seleciona todas as possiveis NF já geradas para as faturas encontradas. Isso evita gerar NF em duplicidade
            // caso já existam notas emitidas.
            $alreadyGenerateNFData = [];
            $queryNfs = Capsule::table($serviceInvoicesTable)->whereIn('invoice_id', $invoicesToGenerateID)->select('invoice_id')->get();

            if (count($queryNfs) > 0) {
                foreach ($queryNfs as $data) {
                    $alreadyGenerateNFData[] = $data->invoice_id;
                }
            }

            // calcula a diferença das coleções
            $invoicesIdToGenerateNF = array_diff($invoicesToGenerateID, $alreadyGenerateNFData);

            // percorre a coleção e emite as notas necessárias
            if (count($invoicesIdToGenerateNF) > 0) {
                foreach ($invoicesIdToGenerateNF as $invoice) {
                    $queue = $this->nf->queue($invoice);
                    logModuleCall('NFEioServiceInvoices', 'Hook - DailyCronJob', $invoice, $queue);

                }
            }

        }

    }

}