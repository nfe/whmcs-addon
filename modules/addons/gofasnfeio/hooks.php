<?php

if (!defined('WHMCS')) {
    exit();
}
//InvoiceCreation
add_hook('InvoiceCreation', 1, function ($vars) {
    require_once __DIR__.'/functions.php';

    require_once __DIR__.'/hooks/invoicecreation.php';
});
//InvoicePaid
 add_hook('InvoicePaid', 1, function ($vars) {
     require_once __DIR__.'/functions.php';

     require_once __DIR__.'/hooks/invoicepaid.php';
 });
//AdminInvoicesControlsOutput
add_hook('AdminInvoicesControlsOutput', 1, function ($vars) {
    require_once __DIR__.'/functions.php';

    require_once __DIR__.'/hooks/admininvoicescontrolsoutput.php';
});
add_hook('InvoiceCancelled', 1, function ($vars) {
    require_once __DIR__.'/functions.php';

    require_once __DIR__.'/hooks/invoicecancelled.php';
});
add_hook('DailyCronJob', 1, function ($vars) {
    require_once __DIR__.'/functions.php';

    require_once __DIR__.'/hooks/dailycronjob.php';
});
add_hook('AfterCronJob', 1, function ($vars) {
    require_once __DIR__.'/functions.php';

    require_once __DIR__.'/hooks/aftercronjob.php';
});
add_hook('ProductDelete', 1, function ($vars) {
    require_once __DIR__.'/functions.php';

    require_once __DIR__.'/hooks/productdelete.php';
});
