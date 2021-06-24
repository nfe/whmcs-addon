<?php

use WHMCS\Database\Capsule;

/**
 * @var $vars vem do arquivo hooks.php.
 * @return string
 */

// function gnfe_get_client_issue_invoice_cond($vars) {
//     $whmcsCondition = strtolower(gnfe_config('issue_note_default_cond'));

//     $invoiceClientId = gnfe_get_local_nfe($vars['invoiceid'], 'user_id');
//     // $invoiceClientId = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']])['userid'];

//     $clientCondition = Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', '21')->where('relid', '=', $invoiceClientId)->get(['value']);
//     $clientCondition = strtolower($clientCondition[0]->value);

//     return !empty($clientCondition) ? $clientCondition : $whmcsCondition;
// }

// $vars['invoiceid'] = '1003';
// $issueInvoiceCondition = gnfe_get_client_issue_invoice_cond($vars);

// echo '<pre>';
// print_r($issueInvoiceCondition);
// echo '</pre><hr>';
