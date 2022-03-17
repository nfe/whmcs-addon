<?php

namespace NFEioServiceInvoices\Hooks;

use \WHMCS\Database\Capsule;

/**
 * Classe com execução das rotinas para o gatilho aftercronjob
 * @see https://developers.whmcs.com/hooks-reference/cron/#aftercronjob
 * @author Andre Bellafronte
 * @version 2.1.0
 */
class AfterCronJob
{

    /**
     * @var \NFEioServiceInvoices\Configuration
     */
    private $config;
    /**
     * @var \NFEioServiceInvoices\Models\ServiceInvoices\Repository
     */
    private $serviceInvoicesRepo;
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
        $storageKey = $this->config->getStorageKey();
        $serviceInvoicesTable = $this->serviceInvoicesRepo->tableName();
        $storage = new \WHMCSExpert\Addon\Storage($storageKey);
        $dataAtual = date('Y-m-d H:i:s');
        $initialDate = $storage->get('initial_date');

        // atualiza a data da ultima cron
        $storage->set('last_cron', $dataAtual);

        $hasNfWaiting = Capsule::table($serviceInvoicesTable)->whereBetween('created_at', [$initialDate, $dataAtual])->where('status', '=', 'Waiting')->count();

        if ($hasNfWaiting) {

            $queryNf = Capsule::table($serviceInvoicesTable)->orderBy('id', 'desc')->whereBetween('created_at', [$initialDate, $dataAtual])->where('status', '=', 'Waiting')->get();

            foreach ($queryNf as $invoice) {

                //$getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);

                $this->nf->emit($invoice);

                /**foreach ($getQuery as $invoices) {
                    $this->nf->emit($invoices, $waiting);
                }*/

            }

            logModuleCall('NFEioServiceInvoices', 'Hook - AfterCronJob', "{$hasNfWaiting} notas a serem geradas", $queryNf);
        } else {
            logModuleCall('NFEioServiceInvoices', 'Hook - AfterCronJob', "", "Nenhuma ação a ser executada");

        }
    }

}