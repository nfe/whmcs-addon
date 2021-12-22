<?php

if (!defined('WHMCS')) {
    exit();
}

use WHMCS\Database\Capsule;

$params = gnfe_config();

if (isset($params['issue_note_after']) && (int)$params['issue_note_after'] > 0) {

    $todayDate = date("Y-m-d");
    // qtd de dias configurado para gerar nf apos pgto
    $issueNoteAfterDays = $params['issue_note_after'];
    // instancia o dia atual
    $invoicesPaidOnDay = date_create($todayDate);
    // subtrai a quantidade de dias com base no dia atual para chegar no dia que deverá ser verificado
    date_sub($invoicesPaidOnDay, new DateInterval("P{$issueNoteAfterDays}D"));

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

    // seleciona todas as possiveis NF já geradas para as faturas encontradas
    $alreadyGenerateNFData = [];
    $queryNfs = Capsule::table('gofasnfeio')->whereIn('invoice_id', $invoicesToGenerateID)->select('invoice_id')->get();
    if (count($queryNfs) > 0) {
        foreach ($queryNfs as $data) {
            $alreadyGenerateNFData[] = $data->invoice_id;
        }
    }
    // calcula a diferença das coleções
    $invoicesIdToGenerateNF = array_diff($invoicesToGenerateID, $alreadyGenerateNFData);


    logModuleCall('nfeio', 'dailycronjob', array(
        "todayDate =>" => $todayDate,
        "issueNoteAfterDays" => $issueNoteAfterDays,
        "invoicesPaidOnDay" => $invoicesPaidOnDay->format('Y-m-d'),
        "toMySQLDateStart = " => $invoicesPaidOnDay->setTime(0, 0, 0)->format('Y-m-d H:i:s.000'),
        "toMySQLDateEnd = " => $invoicesPaidOnDay->setTime(23, 59, 59)->format('Y-m-d H:i:s.000'),
        "invoicesToGenerateData =>" => $invoicesToGenerateData,
        "invoicesToGenerateID" => $invoicesToGenerateID,
        "alreadyGenerateNFData" => $alreadyGenerateNFData,
        "invoicesIdToGenerateNF" => $invoicesIdToGenerateNF,
    ), '');

    // percorre a coleção e emite as notas necessárias
    if (count($invoicesIdToGenerateNF) > 0) {
        foreach ($invoicesIdToGenerateNF as $invoice) {
            $queue = gnfe_queue_nfe($invoice);
            logModuleCall('nfeio', 'dailycronjob emissão de notas', $invoice, $queue);

        }
    }

}
