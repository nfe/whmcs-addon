<?php

if (!defined('WHMCS')) {
    exit();
}

use WHMCS\Database\Capsule;

$params = gnfe_config();
$dataAtual = date('Y-m-d H:i:s');

if (Capsule::table('tbladdonmodules')->where('setting', '=', 'last_cron')->count() == 0) {
    Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'last_cron', 'value' => $dataAtual]);
} else {
    Capsule::table('tbladdonmodules')->where('setting', '=', 'last_cron')->update(['value' => $dataAtual]);
}

$hasNfWaiting = Capsule::table('gofasnfeio')->whereBetween('created_at', [$params['initial_date'], $dataAtual])->where('status', '=', 'Waiting')->count();

if ($hasNfWaiting) {
    $queryNf = Capsule::table('gofasnfeio')->orderBy('id', 'desc')->whereBetween('created_at', [$params['initial_date'], $dataAtual])->where('status', '=', 'Waiting')->get(['id', 'invoice_id', 'services_amount']);
    foreach ($queryNf as $waiting) {

        $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
        logModuleCall('nfeio', 'aftercronjob - getQuery 1', $waiting, $getQuery);

        foreach ($getQuery as $invoices) {
            emitNFE($invoices, $waiting);
        }
    }
}
