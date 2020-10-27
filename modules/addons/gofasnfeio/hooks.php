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
if (!defined("WHMCS")){die();}

//InvoiceCreation
add_hook('InvoiceCreation', 1, function($vars) {
	require_once __DIR__ . '/functions.php';
	require_once __DIR__.'/hooks/invoicecreation.php';
});

//InvoicePaid
add_hook('InvoicePaid', 1, function($vars) {
	require_once __DIR__ . '/functions.php';
	require_once __DIR__.'/hooks/invoicepaid.php';
});

//AdminInvoicesControlsOutput
add_hook('AdminInvoicesControlsOutput', 1, function($vars) {
	require_once __DIR__ . '/functions.php';
	require_once __DIR__.'/hooks/admininvoicescontrolsoutput.php';
});

add_hook('InvoiceCancelled', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
	require_once __DIR__.'/hooks/invoicecancelled.php';
});

add_hook('DailyCronJob', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
	require_once __DIR__.'/hooks/dailycronjob.php';
});

add_hook('AfterCronJob', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
	require_once __DIR__.'/hooks/aftercronjob.php';
});