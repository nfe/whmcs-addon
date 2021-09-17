<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

$params = gnfe_config();
$nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at']);
$invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);
$client = localAPI('GetClientsDetails', ['clientid' => $vars['userid'], 'stats' => false], false);

if ($_REQUEST['gnfe_create']) {
    if ($nfe_for_invoice['status'] !== (string) 'Created' && $nfe_for_invoice['status'] !== (string) 'Issued' && $nfe_for_invoice['status'] !== (string) 'Waiting') {
        foreach ($invoice['items']['item'] as $value) {
            $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
        }
        $customer = gnfe_customer($invoice['userid'], $client);
        $queue = gnfe_queue_nfe($vars['invoiceid'], true);
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
    foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $_REQUEST['gnfe_open'])->get(['id', 'nfe_id']) as $nfe) {
        $url = 'https://app.nfe.io/companies/' . $params['company_id'] . '/service-invoices/' . $nfe->nfe_id;
        echo "<script type='text/javascript' language='Javascript'>window.open('" . $url . "');</script>";
    }
}

if ($_REQUEST['gnfe_cancel']) {
    foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $_REQUEST['id'])->get(['id', 'nfe_id']) as $nfe) {
        $delete_nfe = gnfe_delete_nfe($nfe->nfe_id);
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
        $gnfe_update_nfe = gnfe_update_nfe((object) ['id' => $nfe_for_invoice['nfe_id'], 'status' => 'Cancelled', 'servicesAmount' => $nfe_for_invoice['services_amount'], 'environment' => $nfe_for_invoice['environment'], 'flow_status' => $nfe_for_invoice['flow_status']], $nfe_for_invoice['user_id'], $vars['invoiceid'], 'n/a', $nfe_for_invoice['created_at'], date('Y-m-d H:i:s'));
        $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Nota Fiscal Cancelada com Sucesso</div>';
        header_remove();
        header('Location: invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_message=' . base64_encode(urlencode($message)));

        return '';
    }
}
if ($_REQUEST['gnfe_email']) {
    foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $_REQUEST['id'])->get(['id', 'nfe_id']) as $nfe) {
        $gnfe_email = gnfe_email_nfe($_REQUEST['gnfe_email']);
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
