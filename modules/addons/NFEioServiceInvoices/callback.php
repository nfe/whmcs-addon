<?php

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . DS . 'Loader.php';

use WHMCS\Database\Capsule;
use NFEioServiceInvoices\Legacy\Functions;
use NFEioServiceInvoices\Helpers\Validations;

new NFEioServiceInvoices\Loader();


if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/405
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

// workaround para retornar status code 200 quando a requisição conter uma query iniciando em 'echo' (verificacao do webhook)
if (isset($_GET['echo'])) {
    http_response_code(200);
    echo "ok";
    exit();
}

// armazena o cabecalho da requisição
$headers = getallheaders();

// calcula o hmac da requisição
// cabecalho com a assinatura
$signature = $headers['X-Hub-Signature'];

// corpo da requisição
$body = file_get_contents('php://input');

// se requisicao nao possuir assinatura, retorna erro
if (!$signature) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Assinatura não encontrada', ['headers' => $headers, 'body' => $body]);
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/403
    http_response_code(403);
    exit();
}

// separa o algoritmo do hmac da assinatura
$signature = explode('=', $signature);
$signature = $signature[1];

// carrega as configurações do módulo
$functions = new Functions();
$module = $functions->gnfe_config();

// segredo do webhook
$secret = $module['webhook_secret'];

// verifica se a assinatura é válida
$sign_valid = Validations::webhookHashValid($secret, $body, $signature);

// se a assinatura for inválida, retorna erro
if (!$sign_valid) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Assinatura inválida', [
        'valid' => $sign_valid, 'headers' => $headers, 'body' => $body
    ]);
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/403
    http_response_code(403);
    exit();
}

$payload = json_decode($body, true);

logModuleCall('nfeio_serviceinvoices', 'callback', 'Webhook Raw Payload', ['headers' => $headers, 'body' => $payload]);

if(!is_array($payload) || ( !isset($payload['id']) && !isset($payload['status']) && !isset($payload['flowStatus']) && !isset($payload['environment']) )){
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Payload inválido', ['headers' => $headers, 'body' => $payload]);
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/400
    http_response_code(400);
    exit();
}

$environment = $module['NFEioEnvironment'];

$nf_id = $payload['id'];
$nf_status = $payload['status'];
$nf_flow_status = $payload['flowStatus'];
$nf_environment = $payload['environment'];

//verificar o ambiente
if ($environment == 'on' && $nf_environment == 'Production') {
    logModuleCall('nfeio_serviceinvoices', 'callback_error_development', 'Ambiente Development ativo mas recebendo notas de Production', $payload, $module);
    // informa que requisição é inválida
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/400
    http_response_code(400);
    exit();
} elseif ($environment == '' && $nf_environment == 'Development') {
    logModuleCall('nfeio_serviceinvoices', 'callback_error_production', 'Ambiente Production ativo mas recebendo notas de Development', $payload, $module);
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/400
    http_response_code(400);
    exit();
}
//fim verificar o ambiente

// total de notas locais existentes para NF
$totalNfLocal = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $nf_id)->count();

//verificar se a nfe existe na tabela
if ($totalNfLocal == 0) {
    logModuleCall('nfeio_serviceinvoices', 'callback_error', 'Nota Fiscal não existe no banco local', $payload);

    // informa que informação não foi encontrada
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/404
    http_response_code(404);
    exit();
}
//fim verificar se a nfe existe na tabela

// seleciona as informações da nota local
$nfData = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $nf_id)
    ->get(
        [
            'id',
            'invoice_id',
            'user_id',
            'nfe_id',
            'status',
            'services_amount',
            'environment',
            'flow_status',
            'pdf',
            'created_at',
            'updated_at'
        ]
    );

foreach ($nfData as $key => $value) {
    $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
}
$nfe = $nfe_for_invoice['0'];

if ((string)$nfe['nfe_id'] === (string)$nf_id and $nfe['status'] !== (string)$nf_status) {
    $new_nfe = [
        'invoice_id' => $nfe['invoice_id'],
        'user_id' => $nfe['user_id'],
        'nfe_id' => $nfe['nfe_id'],
        'status' => $nf_status,
        'services_amount' => $nfe['services_amount'],
        'environment' => $nfe['environment'],
        'flow_status' => $nf_flow_status,
        'pdf' => $nfe['pdf'],
        'created_at' => $nfe['created_at'],
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    try {
        $save_nfe = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $nf_id)->update($new_nfe);
        logModuleCall('nfeio_serviceinvoices', 'callback_success', $payload, $save_nfe);
    } catch (\Exception $e) {
        logModuleCall('nfeio_serviceinvoices', 'callback_error', "Erro ao atualizar a nota no banco de dados \n\n Nota: \n {$new_nfe} Callback: \n {$payload}", $e->getMessage());
        // informa que a requisição falhou
        // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/500
        http_response_code(500);
    }

    // informa que a requisição foi aceita
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/202
    http_response_code(202);
} else {
    logModuleCall('nfeio_serviceinvoices', 'callback', 'Nenhuma informação foi alterada', [
        'nfe' => $nfe, 'payload' => $payload
    ]);
    // retorna 200 para informar que a requisição foi recebida
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/200
    http_response_code(200);
    exit();
}
