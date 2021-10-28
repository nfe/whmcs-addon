<?php

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

require_once __DIR__ . '/../../../init.php';
require_once __DIR__.DS.'Loader.php';

use WHMCS\Database\Capsule;
use NFEioServiceInvoices\Legacy\Functions;
new NFEioServiceInvoices\Loader();

$post = json_decode(file_get_contents('php://input'), true);

if ($post) {
    $functions = new Functions();
    //require_once __DIR__ . '/functions.php';
    $params = $functions->gnfe_config();

    //verificar o ambiente
    if ($params['NFEioEnvironment'] == 'on' && $post['environment'] == 'Production') {
        return '';
    } elseif ($params['NFEioEnvironment'] == '' && $post['environment'] == 'Development') {
        return '';
    }
    //fim verificar o ambiente

    //verificar se a nfe existe na tabela
    if (Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $post['id'])->count() == 0 ) {
        return '';
    }
    //fim verificar se a nfe existe na tabela

    foreach (Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $post['id'])->
    get(['id', 'invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at']) as $key => $value) {
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
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }
    $invoice_id = Capsule::table('mod_nfeio_si_serviceinvoices')->where('nfe_id', '=', $post['id'])->get(['invoice_id'])[0];

    if ($post['status'] == 'Error') {
        logModuleCall('gofas_nfeio', 'callback', '', $post, 'ERROR', '');
    } else {
        logModuleCall('gofas_nfeio', 'callback', '', $post, 'OK', '');
    }
}
