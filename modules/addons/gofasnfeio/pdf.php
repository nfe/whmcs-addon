<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/functions.php';

use WHMCS\Database\Capsule;

isset($_SESSION['uid']) or exit.

$invoice_id = $_GET['invoice_id'];
$userId = $_SESSION['uid'];

nfeio_user_owns_invoice($userId, $invoice_id) or exit;

if ($invoice_id) {
    foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value']) as $gnfewhmcsadminurl_) {
        $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
    }
    foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get(['id', 'invoice_id']) as $nfe) {

        $row = Capsule::table('gofasnfeio')->where('id', '=', $nfe->id)->get(['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at', 'id']);
        $nfe = $row[0];

        if ((string) $nfe->status === (string) 'Issued') {
            $nfe_for_invoice = gnfe_pdf_nfe($nfe->nfe_id);
            header('Content-type: application/pdf');
            echo $nfe_for_invoice;
        } else {
            echo 'Not Found';
        }
        echo "<script type='text/javascript' language='Javascript'>window.open('" . $url . "');</script>";
    }
}

function gnfe_pdf_nfe($nf)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/pdf');
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/pdf', 'Authorization: ' . gnfe_config('api_key')]);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}
