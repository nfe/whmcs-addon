<?php

if (!defined('WHMCS')) {
    exit();
}
$params = gnfe_config();
logModuleCall('gofas_nfeio', 'invoice paid', $vars, '', '', 'replaceVars');

if (stripos($params['issue_note'], 'Paga') and (string) $vars['status'] !== (string) 'Draft' and (!$params['issue_note_after'] or 0 === $params['issue_note_after'])) {
    $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);
    if ((float) $invoice['total'] > (float) '0.00' and $invoice['status'] !== (string) 'Draft') {
        $nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'], ['nfe_id', 'status', 'services_amount']);
        if ($nfe_for_invoice['status'] !== (string) 'Created' or $nfe_for_invoice['status'] !== (string) 'Issued') {
            $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);
            foreach ($invoice['items']['item'] as $value) {
                $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
            }
            $queue = gnfe_queue_nfe($vars['invoiceid'], true);
            if ('success' !== $queue) {
                if ('adminarea' === $vars['source']) {
                    header('Location: '.gnfe_whmcs_admin_url().'invoices.php?action=edit&id='.$vars['invoiceid'].'&gnfe_error=Erro ao criar nota fiscal: '.$queue);

                    exit;
                }
            }
            if ('success' === $queue) {
            }
        }
    }
}
if ($params['debug']) {
    logModuleCall('gofas_nfeio', 'InvoicePaid', ['vars' => $vars, 'gnfe_ibge' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode']))], 'post', ['params' => $params, 'invoice' => $invoice, 'client' => $client, 'queue' => $queue, 'nfe_for_invoice' => $nfe_for_invoice], 'replaceVars');
}
