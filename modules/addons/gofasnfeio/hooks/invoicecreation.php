<?php
if (!defined('WHMCS')) {
    die();
}
use WHMCS\Database\Capsule;
$params = gnfe_config();

if ( stripos($params['issue_note'], 'Gerada') and (string)$vars['status'] !== (string)'Draft' and (!$params['issue_note_after'] or $params['issue_note_after'] === 0 )) {
    $invoice = localAPI('GetInvoice',  ['invoiceid' => $vars['invoiceid']], false);
    if ( (float)$invoice['total'] > (float)'0.00' and $invoice['status'] !== (string)'Draft' ) {
        $nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'],['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);
        if ($nfe_for_invoice['status'] !== (string)'Created' or $nfe_for_invoice['status'] !== (string)'Issued') {
            $client = localAPI('GetClientsDetails',['clientid' => $invoice['userid'], 'stats' => false, ], false);
            foreach ( $invoice['items']['item'] as $value) {
                $line_items[] = $value['description'];//substr( $value['description'],  0, 100);
            }

            /*if($params['email_nfe']) {
            	$client_email = $client['email'];
            }
            elseif(!$params['email_nfe']) {
            	$client_email = $client['email'];
            }*/
            $queue = gnfe_queue_nfe($vars['invoiceid'],true);
            if ($queue !== 'success') {
                if ($vars['source'] === 'adminarea') {
                    header('Location: ' . gnfe_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                    exit;
                }
            }
            if ($queue === 'success') {
            }
        }
    }
}
if ($params['debug']) {
    logModuleCall('gofas_nfeio', 'InvoiceCreation', ['vars' => $vars, 'gnfe_ibge' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode'])), 'postifields' => $postifields], 'post',  ['params' => $params, 'invoice' => $invoice, 'client' => $client, 'queue' => $queue, 'nfe_for_invoice' => $nfe_for_invoice, 'company' => $company], 'replaceVars');
}