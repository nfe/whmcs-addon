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
if (!defined('WHMCS')) {
    die();
}
use WHMCS\Database\Capsule;
$params = gnfe_config();

if ( $params['issue_note_after'] and (int)$params['issue_note_after'] > 0 ) {
    foreach ( Capsule::table('tblinvoices')->where('status', '=', 'Paid')->get( ['id', 'userid', 'datepaid', 'total'] ) as $invoices ) {
        $datepaid = date('Ymd', strtotime($invoices->datepaid));
        $datepaid_to_issue_ = '-' . $params['issue_note_after'] . ' days';
        $datepaid_to_issue = date('Ymd', strtotime($datepaid_to_issue_));
        $nfe_for_invoice = gnfe_get_local_nfe($invoices->id,['nfe_id', 'status', 'services_amount', 'created_at']);
        $client = localAPI('GetClientsDetails',['clientid' => $invoices->userid, 'stats' => false, ], false);
        $invoice = localAPI('GetInvoice',  ['invoiceid' => $invoices->id], false);
        if ( (float)$invoices->total > (float)'0.00' and (int)$datepaid_to_issue >= (int)$datepaid ) {
            $processed_invoices[$invoices->id] = 'Paid on: ' . $datepaid;
            if (!$nfe_for_invoice['status'] or (string)$nfe_for_invoice['status'] === (string)'Error' or (string)$nfe_for_invoice['status'] === (string)'None') {
                foreach ( $invoice['items']['item'] as $value) {
                    $line_items[] = $value['description'];
                }
                $customer = gnfe_customer($invoices->userid,$client);
                if ($params['email_nfe']) {
                    $client_email = $client['email'];
                } elseif (!$params['email_nfe']) {
                    $client_email = $client['email'];
                }
                $company = gnfe_get_company();

                $cnpj = $client['customfields2'];
                $cpf = $client['customfields1'];
                $namePF = $client['fullname'];

                $numberId = $cnpj ? $cnpj : $cpf;
                $name = $cnpj ? $client['companyname'] : $name;

                $numberId = str_replace('.', '', $numberId);
                $numberId = str_replace('/', '', $numberId);
                $numberId = str_replace('-', '', $numberId);

                if (!strlen($customer['insc_municipal']) == 0) {
                    $postfields = [
                        'cityServiceCode' => $params['service_code'],
                        'description' => substr(implode("\n", $line_items), 0, 600),
                        'servicesAmount' => $invoices->total,
                        'borrower' => [
                            'federalTaxNumber' => $numberId,
                            'municipalTaxNumber' => $customer['insc_municipal'],
                            'name' => $name,
                            'email' => $client['email'],
                            'address' => [
                                'country' => gnfe_country_code($client['countrycode']),
                                'postalCode' => preg_replace('/[^0-9]/', '', $client['postcode']),
                                'street' => str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1'])),
                                'number' => preg_replace('/[^0-9]/', '', $client['address1']),
                                'additionalInformation' => '',
                                'district' => $client['address2'],
                                'city' => [
                                    'code' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode'])),
                                    'name' => $client['city']
                                ],
                                'state' => $client['state'],
                            ]
                        ],
                        'rpsSerialNumber' => $company['companies']['rpsSerialNumber'],
                        'rpsNumber' => (int)$company['companies']['rpsNumber'] + 1,
                    ];
                } else {
                    $postfields = [
                        'cityServiceCode' => $params['service_code'],
                        'description' => substr(implode("\n", $line_items), 0, 600),
                        'servicesAmount' => $invoices->total,
                        'borrower' => [
                            'federalTaxNumber' => $numberId,
                            'name' => $name,
                            'email' => $client['email'],
                            'address' => [
                                'country' => gnfe_country_code($client['countrycode']),
                                'postalCode' => preg_replace('/[^0-9]/', '', $client['postcode']),
                                'street' => str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1'])),
                                'number' => preg_replace('/[^0-9]/', '', $client['address1']),
                                'additionalInformation' => '',
                                'district' => $client['address2'],
                                'city' => [
                                    'code' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode'])),
                                    'name' => $client['city']
                                ],
                                'state' => $client['state'],
                            ]
                        ],
                        'rpsSerialNumber' => $company['companies']['rpsSerialNumber'],
                        'rpsNumber' => (int)$company['companies']['rpsNumber'] + 1,
                    ];
                }
                if ($params['debug']) {
                    logModuleCall('gofas_nfeio', 'dailycronjob',$postfields , '',  '', 'replaceVars');
                }
                $waiting = [];
                foreach ( Capsule::table('gofasnfeio')->where( 'status', '=', 'Waiting' )->get( ['invoice_id', 'status'] ) as $Waiting ) {
                    $waiting[] = $Waiting->invoice_id;
                }
                $queue = gnfe_queue_nfe($invoices->id);
                if ($queue !== 'success') {
                    $error .= $queue;
                }
                if ($queue === 'success') {
                }
            }
        }
    }
    if ($params['debug']) {
        logModuleCall('gofas_nfeio', 'dailycronjob', ['$params' => $params, '$datepaid' => $datepaid, '$datepaid_to_issue' => $datepaid_to_issue, 'gnfe_get_nfes' => gnfe_get_nfes()], 'post',  ['processed_invoices' => $processed_invoices, 'queue' => $queue, 'error' => $error], 'replaceVars');
    }
}