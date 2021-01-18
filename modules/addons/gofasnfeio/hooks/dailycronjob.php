<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

$params = gnfe_config();
$data = getTodaysDate(false);
$dataAtual = toMySQLDate($data);

if ($params['debug']) {
    logModuleCall('gofas_nfeio', 'daily cron t', $params['issue_note_after'], '', 'replaceVars');
    logModuleCall('gofas_nfeio', 'daily cron issue_note', $params['issue_note'], '', 'replaceVars');
}
if ('Manualmente' !== $params['issue_note'] && $params['issue_note_after'] && (int) $params['issue_note_after'] > 0) {
    foreach (Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('status', '=', 'Paid')->get(['id', 'userid', 'datepaid', 'total']) as $invoices) {
        foreach (Capsule::table('gofasnfeio')->where('status', '=', 'Waiting')->where('invoice_id', '=', $invoices->id)->get(['id', 'invoice_id', 'service_code', 'monthly', 'services_amount']) as $nfeio) {
            $datepaid = date('Ymd', strtotime($invoices->datepaid));
            $datepaid_to_issue_ = '-'.$params['issue_note_after'].' days';
            $datepaid_to_issue = date('Ymd', strtotime($datepaid_to_issue_));
            $nfe_for_invoice = gnfe_get_local_nfe($invoices->id, ['nfe_id', 'status', 'services_amount', 'created_at']);
            $client = localAPI('GetClientsDetails', ['clientid' => $invoices->userid, 'stats' => false], false);
            $invoice = localAPI('GetInvoice', ['invoiceid' => $invoices->id], false);
            if ((float) $invoices->total > (float) '0.00' and (int) $datepaid_to_issue >= (int) $datepaid) {
                $processed_invoices[$invoices->id] = 'Paid on: '.$datepaid;
                if (!$nfe_for_invoice['status'] or (string) $nfe_for_invoice['status'] === (string) 'Error' or (string) $nfe_for_invoice['status'] === (string) 'None') {
                    foreach ($invoice['items']['item'] as $value) {
                        $line_items[] = $value['description'];
                    }
                    $customer = gnfe_customer($invoices->userid, $client);
                    /*if($params['email_nfe']) {
                    	$client_email = $client['email'];
                    }
                    elseif(!$params['email_nfe']) {
                    	$client_email = $client['email'];
                    }*/
                    $company = gnfe_get_company();

                    if (2 == $customer['doc_type']) {
                        $name = $client['companyname'];
                    } elseif (1 == $customer['doc_type'] || 'CPF e/ou CNPJ ausente.' == $customer || !$customer['doc_type']) {
                        $name = $client['fullname'];
                    }
                    $name = htmlspecialchars_decode($name);

                    $service_code = $nfeio->service_code ? $nfeio->service_code : $params['service_code'];

                    foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value']) as $gnfewhmcsadminurl) {
                        $gnfewhmcsadminurl = $gnfewhmcsadminurl->value;
                    }
                    if ('Número da fatura' == $params['InvoiceDetails']) {
                        $desc = 'Nota referente a fatura #'.$waiting->invoice_id.'  '.$gnfeWhmcsUrl.'viewinvoice.php?id='.$waiting->invoice_id.'     ';
                    } else {
                        $desc = substr(implode("\n", $line_items), 0, 600);
                    }
                    if (strpos($client['address1'], ',')) {
                        $array_adress=explode(",", $client['address1']);
                        $street = $array_adress[0];
                        $number=$array_adress[1];
                    } else {
                        $street = str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1']));
                        $number=preg_replace('/[^0-9]/', '', $client['address1']);
                    }

                    if (0 == !strlen($customer['insc_municipal'])) {
                        $postfields = [
                            'cityServiceCode' => $service_code,
                            'description' => $desc,
                            'servicesAmount' => $nfeio->services_amount,
                            'borrower' => [
                                'federalTaxNumber' => $customer['document'],
                                'municipalTaxNumber' => $customer['insc_municipal'],
                                'name' => $name,
                                'email' => $client['email'],
                                'address' => [
                                    'country' => gnfe_country_code($client['countrycode']),
                                    'postalCode' => preg_replace('/[^0-9]/', '', $client['postcode']),
                                    'street' => $street,
                                    'number' => $number,
                                    'additionalInformation' => '',
                                    'district' => $client['address2'],
                                    'city' => [
                                        'code' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode'])),
                                        'name' => $client['city'],
                                    ],
                                    'state' => $client['state'],
                                ],
                            ],
                            'rpsSerialNumber' => $company['companies']['rpsSerialNumber'],
                            'rpsNumber' => (int) $company['companies']['rpsNumber'] + 1,
                        ];
                    } else {
                        $postfields = [
                            'cityServiceCode' => $service_code,
                            'description' => $desc,
                            'servicesAmount' => $nfeio->services_amount,
                            'borrower' => [
                                'federalTaxNumber' => $customer['document'],
                                'name' => $name,
                                'email' => $client['email'],
                                'address' => [
                                    'country' => gnfe_country_code($client['countrycode']),
                                    'postalCode' => preg_replace('/[^0-9]/', '', $client['postcode']),
                                    'street' => $street,
                                    'number' => $number,
                                    'additionalInformation' => '',
                                    'district' => $client['address2'],
                                    'city' => [
                                        'code' => gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode'])),
                                        'name' => $client['city'],
                                    ],
                                    'state' => $client['state'],
                                ],
                            ],
                            'rpsSerialNumber' => $company['companies']['rpsSerialNumber'],
                            'rpsNumber' => (int) $company['companies']['rpsNumber'] + 1,
                        ];
                    }

                    if ($params['debug']) {
                        logModuleCall('gofas_nfeio', 'dailycronjob', $postfields, '', '', 'replaceVars');
                    }
                    $waiting = [];
                    foreach (Capsule::table('gofasnfeio')->where('status', '=', 'Waiting')->get(['invoice_id', 'status']) as $Waiting) {
                        $waiting[] = $Waiting->invoice_id;
                    }
                    $queue = gnfe_queue_nfe_edit($invoices->id, $nfeio->id);
                    if ('success' !== $queue) {
                        $error .= $queue;
                    }
                    if ('success' === $queue) {
                    }
                }
            }
        }
    }
    if ($params['debug']) {
        logModuleCall('gofas_nfeio', 'dailycronjob', ['$params' => $params, '$datepaid' => $datepaid, '$datepaid_to_issue' => $datepaid_to_issue, 'gnfe_get_nfes' => gnfe_get_nfes()], 'post', ['processed_invoices' => $processed_invoices, 'queue' => $queue, 'error' => $error], 'replaceVars');
    }
}
