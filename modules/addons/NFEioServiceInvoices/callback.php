<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Loader.php';

use WHMCS\Database\Capsule;
use NFEioServiceInvoices\Legacy\Functions;
use NFEioServiceInvoices\Helpers\Validations;

new NFEioServiceInvoices\Loader();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

if (isset($_GET['echo'])) {
    http_response_code(200);
    echo "ok";
    exit();
}

$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$signature = $headers['x-hub-signature'] ?? $headers['x-nfeio-signature'] ?? null;

if (!$signature) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Assinatura não encontrada', ['headers' => $headers]);
    http_response_code(403);
    exit();
}

$signature = explode('=', $signature)[1];
$body = file_get_contents('php://input');
$functions = new Functions();
$module = $functions->gnfe_config();
$secret = $module['webhook_secret'];

if (!Validations::webhookHashValid($secret, $body, $signature)) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Assinatura inválida', ['headers' => $headers]);
    http_response_code(403);
    exit();
}

$payload = json_decode($body, true);

if (!is_array($payload) || !isset($payload['id'], $payload['status'], $payload['flowStatus'], $payload['environment'])) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Payload inválido', ['body' => $payload]);
    http_response_code(400);
    exit();
}

$environment = $module['NFEioEnvironment'];
$nf_id = $payload['id'];
$nf_status = $payload['status'];
$nf_flow_status = $payload['flowStatus'];
$nf_flow_message = $payload['flowMessage'] ?? '';
$nf_environment = $payload['environment'];
$nf_rps_number = $payload['rpsNumber'];
$nf_rps_serial = $payload['rpsSerialNumber'] ?? 'IO';

if (($environment === 'on' && $nf_environment === 'Production') || ($environment === '' && $nf_environment === 'Development')) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error_environment', 'Ambiente incompatível', $payload);
    http_response_code(400);
    exit();
}

if (!Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', $nf_id)->exists()) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Nota Fiscal não existe no banco local', $payload);
    http_response_code(404);
    exit();
}

$nfData = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', $nf_id)->get()->toArray();
$nfe = $nfData[0] ?? null;

if (!$nfe) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Nota Fiscal não encontrada no banco local', $payload);
    http_response_code(404);
    exit();
}

if ($nfe->nfe_id === $nf_id && $nfe->status !== $nf_status) {
    $new_nfe = [
        'rpsNumber' => $nf_rps_number,
        'rpsSerialNumber' => $nf_rps_serial,
        'status' => $nf_status,
        'flow_status' => $nf_flow_status,
        'issue_note_conditions' => $nf_flow_message,
    ];

    try {
        Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', $nf_id)->update($new_nfe);
        logModuleCall('nfeio_serviceinvoices', 'callback_success', $payload, $new_nfe);
        http_response_code(202);
    } catch (\Exception $e) {
        logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Erro ao atualizar a nota no banco de dados', $e->getMessage());
        http_response_code(500);
        exit();
    }
} else {
    logModuleCall('nfeio_serviceinvoices', 'callback', 'Nenhuma informação foi alterada', ['nfe' => $nfe, 'payload' => $payload]);
    http_response_code(200);
    exit();
}