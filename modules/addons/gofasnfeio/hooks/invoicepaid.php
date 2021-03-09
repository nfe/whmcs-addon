<?php

if (!defined('WHMCS')) {
    exit();
}
$params = gnfe_config();
logModuleCall('gofas_nfeio', 'invoice paid', $vars, '', '', 'replaceVars');

if (stripos($params['issue_note'], 'Paga') && $vars['status'] != 'Draft' && (!$params['issue_note_after'] || 0 == $params['issue_note_after'])) {
    $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

    if ((float) $invoice['total'] > 0.00 and $invoice['status'] != 'Draft') {
        $nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'], ['id']);

        if (!$nfe_for_invoice['id']) {
            $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

            foreach ($invoice['items']['item'] as $value) {
                $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
            }

            $queue = gnfe_queue_nfe($vars['invoiceid'], true);
            if ($queue != 'success') {
                if ($vars['source'] === 'adminarea') {
                    header('Location: ' . gnfe_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                    exit;
                }
            }
        }
    }
}
if ($params['debug']) {
    save_remote_log($queue,'invoicePaid');
    logModuleCall('gofas_nfeio', 'InvoicePaid', ['vars' => $vars, 'gnfe_ibge' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode']))], 'post', ['params' => $params, 'invoice' => $invoice, 'client' => $client, 'queue' => $queue, 'nfe_for_invoice' => $nfe_for_invoice], 'replaceVars');
}
