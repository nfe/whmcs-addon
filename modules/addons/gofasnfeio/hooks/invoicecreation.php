<?php

if (!defined('WHMCS')) {
    exit();
}

$issueNfeUser = verifyIssueFromUser($vars);

if (strtolower($issueNfeUser) === 'quando a fatura é gerada') {
    logModuleCall('gofas_nfeio', 'quando a fatura é gerada invoicecreation', strtolower($issueNfeUser) , '', '', '');

    $params = gnfe_config();
    $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

    if ((float) $invoice['total'] > (float) '0.00' and $invoice['status'] != 'Draft') {
        $nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);

        if (!$nfe_for_invoice['id']) {
            $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

            foreach ($invoice['items']['item'] as $value) {
                $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
            }
            $queue = gnfe_queue_nfe($vars['invoiceid'], true);

            if ($queue !== 'success') {
                logModuleCall('gofas_nfeio', 'invoicecreation', $vars['invoiceid'], $queue, 'ERROR', '');
                if ('adminarea' === $vars['source']) {
                    header('Location: ' . gnfe_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                    exit;
                }
            } else {
                logModuleCall('gofas_nfeio', 'invoicecreation', $vars['invoiceid'], $queue, 'OK', '');
            }
        }
    }
} elseif (strtolower($issueNfeUser) === 'quando a fatura é paga') {
    logModuleCall('gofas_nfeio', 'quando a fatura é paga invoicecreation', '', '', '', '');

    return;
} elseif (strtolower($issueNfeUser) === 'nenhum (padrão do whmcs) deve seguir a configuração do modulo.') {
    logModuleCall('gofas_nfeio', 'nenhum (padrão do whmcs) deve seguir a configuração do modulo invoicecreation', '', '', '', '');
    $params = gnfe_config();
    if (stripos($params['issue_note'], 'Gerada') && (string) $vars['status'] != 'Draft' && (!$params['issue_note_after'] || 0 == $params['issue_note_after'])) {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

        if ((float) $invoice['total'] > (float) '0.00' and $invoice['status'] != 'Draft') {
            $nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);

            if (!$nfe_for_invoice['id']) {
                $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

                foreach ($invoice['items']['item'] as $value) {
                    $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                }
                $queue = gnfe_queue_nfe($vars['invoiceid'], true);

                if ($queue !== 'success') {
                    logModuleCall('gofas_nfeio', 'invoicecreation', $vars['invoiceid'], $queue, 'ERROR', '');
                    if ('adminarea' === $vars['source']) {
                        header('Location: ' . gnfe_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                        exit;
                    }
                } else {
                    logModuleCall('gofas_nfeio', 'invoicecreation', $vars['invoiceid'], $queue, 'OK', '');
                }
            }
        }
    }
} 
