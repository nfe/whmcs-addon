<?php

namespace NFEioServiceInvoices\Legacy;

use Illuminate\Database\Capsule\Manager as Capsule;

class Hooks
{
    private $config;
    private $functions;
    private $serviceInvoicesRepo;
    private $productCodeRepo;
    private $clientConfigurationRepo;

    public function __construct()
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->functions = new \NFEioServiceInvoices\Legacy\Functions();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $this->clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
    }

    function dailycronjob()
    {
        $params = $this->functions->gnfe_config();
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);
        if (isset($params['issue_note_after']) && (int)$params['issue_note_after'] > 0) {
            foreach (Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('status', '=', 'Paid')->get(['id', 'userid', 'datepaid', 'total']) as $invoices) {
                foreach (Capsule::table('mod_nfeio_si_serviceinvoices')->where('status', '=', 'Waiting')->where('invoice_id', '=', $invoices->id)->get(['id', 'nfe_id', 'status', 'created_at', 'invoice_id', 'service_code', 'services_amount']) as $nfeio) {
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

        if (!isset($params['issue_note_after']) || $params['issue_note_after'] <= 0) {
            foreach (Capsule::table($serviceInvoicesTable)->orderBy('id', 'desc')->where('status', '=', 'Waiting')->get(['id', 'invoice_id', 'services_amount']) as $waiting) {
                logModuleCall('gofas_nfeio', 'aftercronjob - checktablegofasnfeio', '', $waiting,'', '');

                $data = getTodaysDate(false);
                $dataAtual = toMySQLDate($data);

                if ($params['issue_note_default_cond'] !== 'Manualmente') {
                    $getQuery = Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
                    logModuleCall('gofas_nfeio', 'aftercronjob - getQuery', ['date' => [$params['initial_date'], $dataAtual], 'where' => 'id=' . $waiting->invoice_id], $getQuery,'', '');
                } else {
                    $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
                    logModuleCall('gofas_nfeio', 'aftercronjob - getQuery', 'id=' . $waiting->invoice_id, $getQuery,'', '');
                }

                foreach ($getQuery as $invoices) {
                    $this->functions->emitNFE($invoices,$waiting);
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

        try {
            if (Capsule::schema()->hasTable($_table)) {
                return ['Emitir nota fiscal quando' => $this->functions->gnfe_show_issue_invoice_conds($vars['userid'])];
            } else {
                return [
                    'Módulo NFE.io' => 'Não existem opções'
                ];
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }


    }

    function admininvoicescontrolsoutput($vars)
    {
        $serviceInvoicesTableName = $this->serviceInvoicesRepo->tableName();
        $params = $this->functions->gnfe_config();
        $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);
        $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);
        $client = localAPI('GetClientsDetails', ['clientid' => $vars['userid'], 'stats' => false], false);

        if ($_REQUEST['gnfe_create']) {
            if ($nfe_for_invoice['status'] !== (string) 'Created' && $nfe_for_invoice['status'] !== (string) 'Issued' && $nfe_for_invoice['status'] !== (string) 'Waiting') {
                foreach ($invoice['items']['item'] as $value) {
                    $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                }
                $customer = $this->functions->gnfe_customer($invoice['userid'], $client);
                $queue = $this->functions->gnfe_queue_nfe($vars['invoiceid'], true);
                if ($queue !== 'success') {
                    logModuleCall('gofas_nfeio', 'admininvoicecontorloutput - gnfe_create',$vars['invoiceid'], $queue, 'ERROR', '');
                    header_remove();
                    header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);

                    exit;
                }
                if ($queue === 'success') {
                    logModuleCall('gofas_nfeio', 'admininvoicecontorloutput - gnfe_create',$vars['invoiceid'], $queue, 'OK', '');
                    $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Nota Fiscal enviada para processamento</div>';
                    header_remove();
                    header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_message=' . base64_encode(urlencode($message)));

                    exit;
                }
            }
        }

        if ($_REQUEST['gnfe_open']) {
            foreach (Capsule::table($serviceInvoicesTableName)->where('invoice_id', '=', $_REQUEST['gnfe_open'])->get(['id', 'nfe_id']) as $nfe) {
                $url = 'https://app.nfe.io/companies/' . $params['company_id'] . '/service-invoices/' . $nfe->nfe_id;
                echo "<script type='text/javascript' language='Javascript'>window.open('" . $url . "');</script>";
            }
        }

        if ($_REQUEST['gnfe_cancel']) {
            foreach (Capsule::table($serviceInvoicesTableName)->where('invoice_id', '=', $_REQUEST['id'])->get(['id', 'nfe_id']) as $nfe) {
                $delete_nfe = $this->functions->gnfe_delete_nfe($nfe->nfe_id);
                if ($delete_nfe->message) {
                    logModuleCall('gofas_nfeio', 'admininvoicecontorloutput - gnfe_cancel',$nfe->nfe_id, $delete_nfe, 'ERROR', '');
                    $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $delete_nfe->message . '</div>';
                    header_remove();
                    header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_message=' . base64_encode(urlencode($message)));

                    return '';
                }
            }
            if (!$delete_nfe->message) {
                logModuleCall('gofas_nfeio', 'admininvoicecontorloutput - gnfe_cancel',$nfe->nfe_id, $delete_nfe, 'OK', '');
                $gnfe_update_nfe = $this->functions->gnfe_update_nfe((object) ['id' => $nfe_for_invoice['nfe_id'], 'status' => 'Cancelled', 'servicesAmount' => $nfe_for_invoice['services_amount'], 'environment' => $nfe_for_invoice['environment'], 'flow_status' => $nfe_for_invoice['flow_status']], $nfe_for_invoice['user_id'], $vars['invoiceid'], 'n/a', $nfe_for_invoice['created_at'], date('Y-m-d H:i:s'));
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Nota Fiscal Cancelada com Sucesso</div>';
                header_remove();
                header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_message=' . base64_encode(urlencode($message)));

                return '';
            }
        }
        if ($_REQUEST['gnfe_email']) {
            foreach (Capsule::table($serviceInvoicesTableName)->where('invoice_id', '=', $_REQUEST['id'])->get(['id', 'nfe_id']) as $nfe) {
                $gnfe_email = $this->functions->gnfe_email_nfe($_REQUEST['gnfe_email']);
                if (!$gnfe_email->message) {
                    logModuleCall('gofas_nfeio', 'admininvoicecontorloutput - gnfe_email',$_REQUEST['gnfe_email'], $gnfe_email, 'OK', '');
                    $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Email Enviado com Sucesso</div>';
                    header_remove();
                    header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_message=' . base64_encode(urlencode($message)));

                    exit;
                }
            }
            if ($gnfe_email->message) {
                logModuleCall('gofas_nfeio', 'admininvoicecontorloutput - gnfe_email',$_REQUEST['gnfe_email'], $gnfe_email, 'ERROR', '');
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $gnfe_email->message . '</div>';
                header_remove();
                header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_message=' . base64_encode(urlencode($message)));

                exit;
            }
        }

        if ($nfe_for_invoice['status'] === (string) 'Waiting') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#f0ad4e;">Aguardando</span>';
            $disabled = ['a' => 'disabled="disabled"', 'b' => 'disabled="disabled"', 'c' => '', 'd' => 'disabled="disabled"'];
        }
        if ($nfe_for_invoice['status'] === (string) 'Error_cep') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#c00;">Erro no CEP do usuário</span>';
            $disabled = ['a' => 'disabled="disabled"', 'b' => 'disabled="disabled"', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
        }
        if ($nfe_for_invoice['status'] === (string) 'Created') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#f0ad4e;">Processando</span>';
            $disabled = ['a' => 'disabled="disabled"', 'b' => '', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
        }
        if ($nfe_for_invoice['status'] === (string) 'Issued') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#779500;">Emitida</span>';
            $disabled = ['a' => 'disabled="disabled"', 'b' => '', 'c' => '', 'd' => ''];
        }
        if ($nfe_for_invoice['status'] === (string) 'Cancelled') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#c00;">Cancelada</span>';
            $disabled = ['a' => '', 'b' => '', 'c' => 'disabled="disabled"', 'd' => ''];
        }
        if ($nfe_for_invoice['status'] === (string) 'Error') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#c00;">Falha ao Emitir</span>';
            $disabled = ['a' => '', 'b' => '', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
        }
        if ($nfe_for_invoice['status'] === (string) 'None') {
            $invoice_nfe = ' Criada em ' . date('d/m/Y H:i:s', strtotime($nfe_for_invoice['created_at'])) . ' - Status: <span style="color:#f0ad4e;">Nenhum</span>';
            $disabled = ['a' => '', 'b' => '', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
        }
        if (!$nfe_for_invoice['status']) {
            $invoice_nfe = ' Nenhuma nota fiscal foi emitida para essa fatura.';
            $disabled = ['a' => '', 'b' => 'disabled="disabled"', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
        }
        if ((string) $invoice['status'] === (string) 'Draft') {
            $disabled = ['a' => 'disabled="disabled"', 'b' => 'disabled="disabled"', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
        }
        echo '<div style="text-align: left; padding: 8px 0px; max-width: 445px; border-top: 1px solid #ccc; margin: 8px 0px;">';
        echo '<div style="margin: 0px 0px 5px 0px;"><strong>Nota Fiscal:</strong>' . $invoice_nfe . '</div>';
        echo '<button ' . $disabled['a'] . ' style="margin-right: 4px;" onclick="location.href=`invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_create=yes`" class="btn btn-primary" id="gnfe_generate" title="Emitir Nota Fiscal">Emitir NFE</button>';
        echo '<button ' . $disabled['b'] . ' style="margin-right: 4px;" onclick="location.href=`invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_open=' . $vars['invoiceid'] . '`" class="btn btn-success" id="gnfe_view" title="Ver Nota Fiscal">Visualizar NFE</button>';
        echo '<button ' . $disabled['c'] . ' style="margin-right: 4px;" onclick="location.href=`invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_cancel=' . $nfe_for_invoice['nfe_id'] . '`" class="btn btn-danger" id="gnfe_cancel" title="Cancelar Nota Fiscal">Cancelar NFE</button>';
        echo '<button ' . $disabled['d'] . ' onclick="location.href=`invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_email=' . $nfe_for_invoice['nfe_id'] . '`" class="btn btn-primary" id="gnfe_email" title="Enviar Nota Fiscal por Email">Enviar Email</button>';
        echo '<div>';

        if ($_REQUEST['gnfe_error']) {
            echo '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $_REQUEST['gnfe_error'] . '</div>';
        }
        if ($_REQUEST['gnfe_message']) {
            echo urldecode(base64_decode($_REQUEST['gnfe_message']));
        }
    }
}