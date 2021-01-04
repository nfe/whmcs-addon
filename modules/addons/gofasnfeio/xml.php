
<?php
require_once __DIR__.'/../../../init.php';
use WHMCS\Database\Capsule;

$invoice_id = $_GET['invoice_id'];

if ($invoice_id) {
    foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value']) as $gnfewhmcsadminurl_) {
        $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
    }
    foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get(['id', 'invoice_id']) as $nfe) {
        $url = $gnfewhmcsadminurl.'modules/addons/gofasnfeio/createxml.php?nfe_id='.$nfe->id;
        echo "<script type='text/javascript' language='Javascript'>window.open('".$url."');</script>";
    }
}
echo "<script type='text/javascript' language='Javascript'>window.location.href = '".$gnfewhmcsadminurl.'viewinvoice.php?id='.$invoice_id."';</script>";
