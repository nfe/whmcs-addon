<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;
$params = gnfe_config();

if (!isset($params['issue_note_after']) || $params['issue_note_after'] <= 0) {
    foreach (Capsule::table('gofasnfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->get(['id', 'invoice_id', 'services_amount']) as $waiting) {
        logModuleCall('gofas_nfeio', 'aftercronjob - checktablegofasnfeio', '', $waiting,'', '');

        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);

        if ($params['issue_note'] !== 'Manualmente') {
            $getQuery = Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
            logModuleCall('gofas_nfeio', 'aftercronjob - getQuery', ['date' => [$params['initial_date'], $dataAtual], 'where' => 'id=' . $waiting->invoice_id], $getQuery,'', '');
        } else {
            $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
            logModuleCall('gofas_nfeio', 'aftercronjob - getQuery', 'id=' . $waiting->invoice_id, $getQuery,'', '');
        }

        foreach ($getQuery as $invoices) {
            emitNFE($invoices,$waiting);
        }
    }
}
