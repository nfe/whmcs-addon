<?php
/**
 * Módulo Nota Fiscal NFE.io para WHMCS
 * @author		Original Author Mauricio Gofas | gofas.net
 * @author		Updated by Link Nacional
 * @see			https://github.com/nfe/whmcs-addon/
 * @copyright	2020 https://github.com/nfe/whmcs-addon/
 * @license		https://gofas.net?p=9340
 * @support		https://github.com/nfe/whmcs-addon/issues
 * @version		1.2.4
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//require_once '../vendor/autoload.php';
//header("Content-type: application/json; charset=utf-8");
//header('Content-type: application/pdf');
//require dirname(__DIR__) . '/vendor/autoload.php';
//require_once __DIR__ . '/vendor/autoload.php';


require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

$invoice_id = $_GET['invoice_id'];

//echo $invoice_id;

if ($invoice_id) {

    require_once __DIR__ . '/functions.php';

    foreach( Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get(array( 'invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at' )) as $key => $value ) {
        $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
    }

    $nfe = $nfe_for_invoice['0'];

//    echo '<pre>$nfe';
//    print_r($nfe);
//    echo '</pre>';
//
//
//    echo '<pre>$nfe';
//    echo $_SESSION['uuid'];
//    print_r($_SESSION);
//    echo '</pre>';

    if((string)$nfe['user_id'] == (string)$_SESSION['uid']){

        if((string)$nfe['status'] === (string)'Issued') {
            $nfe_for_invoice = gnfe_pdf_nfe($nfe['nfe_id']);

            echo $nfe_for_invoice;

        }else{
            echo 'Not Found';
        }

    }else{
        echo 'Not Found';
    }

    exit();
}