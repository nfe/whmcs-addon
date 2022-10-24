<?php

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__.DS.'Loader.php';

use WHMCS\Database\Capsule;
use NFEioServiceInvoices\Legacy\Functions;
new NFEioServiceInvoices\Loader();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/405
    http_response_code(405);
    exit();
}

$post = json_decode(file_get_contents('php://input'), true);

if ($post) {
    $functions = new Functions();
    //require_once __DIR__ . '/functions.php';
    $params = $functions->gnfe_config();

    $environment = $params['NFEioEnvironment'];

    $nf_id = $post['id'];
    $nf_status = $post['status'];
    $nf_flow_status = $post['flowStatus'];
    $nf_environment = $post['environment'];

    // total de notas locais existentes para NF
    $totalNfLocal = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $nf_id)->count();


    //verificar o ambiente
    if ($environment == 'on' && $nf_environment == 'Production') {
        logModuleCall('NFEioServiceInvoices', 'callback_error_development', 'Ambiente Development ativo mas recebendo notas de Production', $post, $params);
        // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/403
        http_response_code(200);
        exit();
    } elseif ($environment == '' && $nf_environment == 'Development') {
        logModuleCall('NFEioServiceInvoices', 'callback_error_production', 'Ambiente Production ativo mas recebendo notas de Development', $post, $params);
        // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/403
        http_response_code(200);
        exit();
    }
    //fim verificar o ambiente

    //verificar se a nfe existe na tabela
    if ($totalNfLocal == 0 ) {
        logModuleCall('NFEioServiceInvoices', 'callback_error', 'Nota Fiscal não existe no banco local', $post);
        // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/404
        http_response_code(404);
        exit();
    }
    //fim verificar se a nfe existe na tabela

    // seleciona as informações da nota local
    $nfData = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $post['id'])
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

    if ((string) $nfe['nfe_id'] === (string) $post['id'] and $nfe['status'] !== (string) $post['status']) {
        $new_nfe = [
            'invoice_id' => $nfe['invoice_id'],
            'user_id' => $nfe['user_id'],
            'nfe_id' => $nfe['nfe_id'],
            'status' => $post['status'],
            'services_amount' => $nfe['services_amount'],
            'environment' => $nfe['environment'],
            'flow_status' => $post['flowStatus'],
            'pdf' => $nfe['pdf'],
            'created_at' => $nfe['created_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $save_nfe = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $post['id'])->update($new_nfe);
            logModuleCall('NFEioServiceInvoices', 'callback_success', $post, $save_nfe);

        } catch (\Exception $e) {
            logModuleCall('NFEioServiceInvoices', 'callback_error', "Erro ao atualizar a nota no banco de dados \n\n Nota: \n {$new_nfe} Callback: \n {$post}", $e->getMessage());
        }

        // garante retorno de cabeçalho na resposta
        // https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status/404
        http_response_code(200);
    }
}
