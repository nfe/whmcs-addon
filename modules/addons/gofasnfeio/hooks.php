<?php
/**
 * Módulo Gofas NFE.io para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=12529
 * @copyright	2020 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=12313
 * @version		1.2.1
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