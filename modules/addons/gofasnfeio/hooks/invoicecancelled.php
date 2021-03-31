<?php

if (!defined('WHMCS')) {
    exit();
}
$params = gnfe_config();
if ($params['cancel_invoice_cancel_nfe']) {
    $nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'], ['nfe_id', 'status', 'services_amount', 'environment']);
    if ($nfe_for_invoice['status'] === (string) 'Issued') {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);
        $delete_nfe = gnfe_delete_nfe($nfe_for_invoice['nfe_id']);
        if (!$delete_nfe->message) {
            logModuleCall('gofas_nfeio', 'invoicecancelled', $nfe_for_invoice['nfe_id'], $delete_nfe, 'OK', '');
            $gnfe_update_nfe = gnfe_update_nfe((object) ['id' => $nfe_for_invoice['nfe_id'], 'status' => 'Cancelled', 'servicesAmount' => $nfe_for_invoice['services_amount'], 'environment' => $nfe_for_invoice['environment'], 'flow_status' => $nfe_for_invoice['flow_status']], $invoice['userid'], $vars['invoiceid'], 'n/a', $nfe_for_invoice['created_at'], date('Y-m-d H:i:s'));
        } else {
            logModuleCall('gofas_nfeio', 'invoicecancelled', $nfe_for_invoice['nfe_id'], $delete_nfe, 'ERROR', '');
        }
    }
}
