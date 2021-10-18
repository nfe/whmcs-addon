<?php

namespace NFEioServiceInvoices\Legacy;

class Hooks
{
    private $config;
    private $functions;

    public function __construct()
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->functions = new \NFEioServiceInvoices\Legacy\Functions();
    }

    function dailycronjob()
    {
        $params = $this->functions->gnfe_config();
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);
        if (isset($params['issue_note_after']) && (int)$params['issue_note_after'] > 0) {
            foreach (Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('status', '=', 'Paid')->get(['id', 'userid', 'datepaid', 'total']) as $invoices) {
                foreach (Capsule::table('gofasnfeio')->where('status', '=', 'Waiting')->where('invoice_id', '=', $invoices->id)->get(['id', 'nfe_id', 'status', 'created_at', 'invoice_id', 'service_code', 'services_amount']) as $nfeio) {
                    $datepaid = date('Ymd', strtotime($invoices->datepaid));
                    $datepaid_to_issue_ = '-' . $params['issue_note_after'] . ' days';
                    $datepaid_to_issue = date('Ymd', strtotime($datepaid_to_issue_));

                    if ((float) $invoices->total > '0.00' and (int) $datepaid_to_issue >= (int) $datepaid) {
                        logModuleCall('gofas_nfeio', 'dailycronjob', 'emitNFE', '', '');

                        $this->functions->emitNFE($invoices,$nfeio);
                    }
                }
            }
        }
    }

    function invoicecreation($vars)
    {
        $issueInvoiceCondition = $this->functions->gnfe_get_client_issue_invoice_cond_from_invoice_id($vars['invoiceid']);

        if ($issueInvoiceCondition === 'quando a fatura é gerada') {
            logModuleCall('gofas_nfeio', 'quando a fatura é gerada invoicecreation', $issueInvoiceCondition , '', '', '');

            $params = $this->functions->gnfe_config();
            $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

            if ((float) $invoice['total'] > (float) '0.00' and $invoice['status'] != 'Draft') {
                $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);

                if (!$nfe_for_invoice['id']) {
                    $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

                    foreach ($invoice['items']['item'] as $value) {
                        $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                    }
                    $queue = $this->functions->gnfe_queue_nfe($vars['invoiceid'], true);

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
        } elseif ($issueInvoiceCondition === 'quando a fatura é paga') {
            logModuleCall('gofas_nfeio', 'quando a fatura é paga invoicecreation', '', '', '', '');

            return;
        } else {
            logModuleCall('gofas_nfeio', 'seguir configuração do módulo nfe.io invoicecreation', '', '', '', '');
            $params = $this->functions->gnfe_config();
            if (stripos($params['issue_note_default_cond'], 'Gerada') && (string) $vars['status'] != 'Draft' && (!$params['issue_note_after'] || 0 == $params['issue_note_after'])) {
                $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

                if ((float) $invoice['total'] > (float) '0.00' and $invoice['status'] != 'Draft') {
                    $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);

                    if (!$nfe_for_invoice['id']) {
                        $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

                        foreach ($invoice['items']['item'] as $value) {
                            $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                        }
                        $queue = $this->functions->gnfe_queue_nfe($vars['invoiceid'], true);

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

    }

    function invoicepaid($vars)
    {
        $params = $this->functions->gnfe_config();
        $issueInvoiceCondition = $this->functions->gnfe_get_client_issue_invoice_cond_from_invoice_id($vars['invoiceid']);

        // Uma fatura é paga
        if ($issueInvoiceCondition === 'quando a fatura é paga') {
            $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

            if ((float) $invoice['total'] > 0.00 and $invoice['status'] != 'Draft') {
                $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['id']);

                if (!$nfe_for_invoice['id']) {
                    $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

                    foreach ($invoice['items']['item'] as $value) {
                        $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                    }

                    $queue = $this->functions->gnfe_queue_nfe($vars['invoiceid'], true);
                    if ($queue != 'success') {
                        if ($vars['source'] === 'adminarea') {
                            header('Location: ' . gnfe_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                            exit;
                        }
                    } else {
                        logModuleCall('gofas_nfeio', 'invoicepaid', $vars['invoiceid'], $queue, 'OK', '');
                    }
                }
            }
        } elseif ($issueInvoiceCondition === 'quando a fatura é gerada') {
            return;
        } else {
            if (stripos($params['issue_note_default_cond'], 'Paga') && $vars['status'] != 'Draft' && (!$params['issue_note_after'] || 0 == $params['issue_note_after'] || stripos(strtolower($issueNfeUser),'paga'))) {
                $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

                if ((float) $invoice['total'] > 0.00 and $invoice['status'] != 'Draft') {
                    $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['id']);

                    if (!$nfe_for_invoice['id']) {
                        $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

                        foreach ($invoice['items']['item'] as $value) {
                            $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                        }

                        $queue = $this->functions->gnfe_queue_nfe($vars['invoiceid'], true);
                        if ($queue != 'success') {
                            logModuleCall('gofas_nfeio', 'invoicepaid', $vars['invoiceid'], $queue, 'ERROR', '');
                            if ($vars['source'] === 'adminarea') {
                                header('Location: ' . gnfe_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                                exit;
                            }
                        } else {
                            logModuleCall('gofas_nfeio', 'invoicepaid', $vars['invoiceid'], $queue, 'OK', '');
                        }
                    }
                }
            }
        }
    }
}