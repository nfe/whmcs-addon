<?php
require_once __DIR__ . '/../../../init.php';
use WHMCS\Database\Capsule;
$invoice_id = $_GET['invoice_id'];

if ($invoice_id) {
    require_once __DIR__ . '/functions.php';
    foreach( Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get(array( 'invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at' )) as $key => $value ) {
        $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
    }
    $nfe = $nfe_for_invoice['0'];
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