<?php

namespace NFEioServiceInvoices\Legacy;

use \WHMCS\Database\Capsule;

class Hooks
{
    private $config;
    private $functions;
    private $serviceInvoicesRepo;
    private $productCodeRepo;
    private $clientConfigurationRepo;
    /**
     * @var \NFEioServiceInvoices\NFEio\Nfe
     */
    private $nf;

    public function __construct()
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->functions = new \NFEioServiceInvoices\Legacy\Functions();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $this->clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $this->nf = new \NFEioServiceInvoices\NFEio\Nfe();
    }



    // TODO: falta funcao gnfe_whmcs_admin_url
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

    // TODO: falta funcao gnfe_whmcs_admin_url
    // TODO: falta os redirecionamentos
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

                    $queue = $this->nf->queue($vars['invoiceid']);
                    if (!$queue['success']) {
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

                        $queue = $this->nf->queue($vars['invoiceid']);
                        if (!$queue['success']) {
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

    function invoicecancelled($vars)
    {
        $params = $this->functions->gnfe_config();
        if ($params['cancel_invoice_cancel_nfe']) {
            $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['nfe_id', 'status', 'services_amount', 'environment']);
            if ($nfe_for_invoice['status'] === (string) 'Issued') {
                $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);
                $delete_nfe = $this->functions->gnfe_delete_nfe($nfe_for_invoice['nfe_id']);
                if (!$delete_nfe->message) {
                    logModuleCall('gofas_nfeio', 'invoicecancelled', $nfe_for_invoice['nfe_id'], $delete_nfe, 'OK', '');
                    $gnfe_update_nfe = $this->functions->gnfe_update_nfe((object) ['id' => $nfe_for_invoice['nfe_id'], 'status' => 'Cancelled', 'servicesAmount' => $nfe_for_invoice['services_amount'], 'environment' => $nfe_for_invoice['environment'], 'flow_status' => $nfe_for_invoice['flow_status']], $invoice['userid'], $vars['invoiceid'], 'n/a', $nfe_for_invoice['created_at'], date('Y-m-d H:i:s'));
                } else {
                    logModuleCall('gofas_nfeio', 'invoicecancelled', $nfe_for_invoice['nfe_id'], $delete_nfe, 'ERROR', '');
                }
            }
        }
    }

    function aftercronjob()
    {
        $storageKey = $this->config->getStorageKey();
        $serviceInvoicesTable = $this->serviceInvoicesRepo->tableName();
        $params = $this->functions->gnfe_config();
        $dataAtual = date('Y-m-d H:i:s');

        if (Capsule::table('tbladdonmodules')->where('setting','=','last_cron')->count() == 0) {
            Capsule::table('tbladdonmodules')->insert(['module' => $storageKey, 'setting' => 'last_cron', 'value' => $dataAtual]);
        } else {
            Capsule::table('tbladdonmodules')->where('setting','=','last_cron')->update(['value' => $dataAtual]);
        }

        $hasNfWaiting = Capsule::table($serviceInvoicesTable)->whereBetween('created_at', [$params['initial_date'], $dataAtual])->where('status', '=', 'Waiting')->count();

        if ($hasNfWaiting) {
            $queryNf = Capsule::table($serviceInvoicesTable)->orderBy('id', 'desc')->whereBetween('created_at', [$params['initial_date'], $dataAtual])->where('status', '=', 'Waiting')->get(['id', 'invoice_id', 'services_amount']);
            foreach ($queryNf as $waiting) {

                $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
                logModuleCall('nfeio', 'aftercronjob - getQuery 1', $waiting, $getQuery);

                foreach ($getQuery as $invoices) {
                    $this->functions->emitNFE($invoices, $waiting);
                }
            }
        }
    }

    function productdelete($vars)
    {
        $productCodeTable = $this->productCodeRepo->tableName();
        try {
            $delete = Capsule::table($productCodeTable)->where('product_id', '=', $vars['pid'])->delete();
            logModuleCall('gofas_nfeio', 'productdelete', 'product_id=' . $vars['pid'], $delete, 'OK', '');
        } catch (Exception $e) {
            logModuleCall('gofas_nfeio', 'productdelete', 'product_id=' . $vars['pid'], $e->getMessage(), 'ERROR', '');
        }
    }

    function customclientissueinvoice($vars)
    {
        $_table = $this->clientConfigurationRepo->tableName();
        $result = [];

        try {
            if (Capsule::schema()->hasTable($_table)) {
                $result =  ['Emitir nota fiscal quando' => $this->functions->gnfe_show_issue_invoice_conds($vars['userid'])];
            } else {
                $result =  ['Módulo NFE.io' => 'Não existem opções'];
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        return $result;


    }

}