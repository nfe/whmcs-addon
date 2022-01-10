<?php

defined('WHMCS') or exit;

add_hook('InvoiceCreation', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/sendNFE.php';
    require_once __DIR__ . '/hooks/invoicecreation.php';
});

 add_hook('InvoicePaid', 1, function ($vars) {
     require_once __DIR__ . '/functions.php';
     require_once __DIR__ . '/hooks/invoicepaid.php';
 });

add_hook('AdminInvoicesControlsOutput', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/hooks/admininvoicescontrolsoutput.php';
});

add_hook('InvoiceCancelled', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/hooks/invoicecancelled.php';
});

add_hook('DailyCronJob', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/sendNFE.php';
    require_once __DIR__ . '/hooks/dailycronjob.php';
});

add_hook('AfterCronJob', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/sendNFE.php';
    require_once __DIR__ . '/hooks/aftercronjob.php';
});

add_hook('ProductDelete', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/hooks/productdelete.php';
});

add_hook('AdminClientProfileTabFields', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/update.php';
    return require_once __DIR__ . '/hooks/customclientissueinvoice.php';
});

add_hook('AdminClientProfileTabFieldsSave', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
    gnfe_save_client_issue_invoice_cond($vars['userid'], $_REQUEST['issue_note_cond']);
});
