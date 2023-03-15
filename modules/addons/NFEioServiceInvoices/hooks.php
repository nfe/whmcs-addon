<?php
/**
 * WHMCS SDK Sample Addon Module Hooks File
 *
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * This allows you to execute your own code in addition to, or sometimes even
 * instead of that which WHMCS executes by default.
 *
 * @see https://developers.whmcs.com/hooks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license   http://www.whmcs.com/license/ WHMCS Eula
 */


if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__ . DS . 'Loader.php';


add_hook(
    'InvoiceCreation', 1, function ($vars) {
        $hook = new \NFEioServiceInvoices\Hooks\InvoiceCreation($vars);
        $hook->run();
    }
);

add_hook(
    'InvoicePaid', 1, function ($vars) {
        $hook = new \NFEioServiceInvoices\Hooks\InvoicePaid($vars);
        $hook->run();
    }
);

add_hook(
    'InvoiceCancelled', 1, function ($vars) {
        $legacyHooks = new \NFEioServiceInvoices\Legacy\Hooks();
        $legacyHooks->invoicecancelled($vars);
    }
);

add_hook(
    'DailyCronJob', 1, function ($vars) {
        $hook = new \NFEioServiceInvoices\Hooks\DailyCronJob();
        $hook->run();
    }
);

add_hook(
    'AfterCronJob', 1, function ($vars) {
        $hook = new \NFEioServiceInvoices\Hooks\AfterCronJob();
        $hook->run();
    }
);

add_hook(
    'ProductDelete', 1, function ($vars) {
        $legacyHooks = new \NFEioServiceInvoices\Legacy\Hooks();
        $legacyHooks->productdelete($vars);
    }
);

add_hook(
    'AdminClientProfileTabFieldsSave', 1, function ($vars) {
        $functions = new \NFEioServiceInvoices\Legacy\Functions();
        $functions->gnfe_save_client_issue_invoice_cond($vars['userid'], $_REQUEST['issue_note_cond']);
    }
);

add_hook(
    'AdminClientProfileTabFields', 1, function ($vars) {
        $legacyHooks = new \NFEioServiceInvoices\Legacy\Hooks();
        return $legacyHooks->customclientissueinvoice($vars);
    }
);

add_hook(
    'AdminInvoicesControlsOutput', 1, function ($vars) {
        $hook = new \NFEioServiceInvoices\Hooks\AdminInvoicesControlsOutput($vars);
        $hook->run();

    }
);

add_hook(
    'ClientAreaPageViewInvoice', 1, function ($vars) {
        $hook = new \NFEioServiceInvoices\Hooks\ClientAreaPageViewInvoice($vars);
        return $hook->run();
    }
);