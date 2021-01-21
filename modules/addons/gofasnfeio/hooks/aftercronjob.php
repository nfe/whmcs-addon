<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

if ($params['debug']) {
    logModuleCall('gofas_nfeio', 'check', 'check', '', 'replaceVars');
}
$params = gnfe_config();

    foreach (Capsule::table('gofasnfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->get(['id', 'invoice_id', 'services_amount']) as $waiting) {
        // foreach (Capsule::table('gofasnfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->take(1)->get(['id', 'invoice_id', 'services_amount']) as $waiting) {
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);

        if ('Manualmente' !== $params['issue_note']) {
            $getQuery = Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
        } else {
            $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
        }
        if ($params['debug']) {
            logModuleCall('gofas_nfeio', 'getQuery', json_decode($getQuery), '', 'replaceVars');
            logModuleCall('gofas_nfeio', 'invoice_id', $waiting->invoice_id, '', 'replaceVars');
            logModuleCall('gofas_nfeio', 'dataAtual', $dataAtual, '', 'replaceVars');
            logModuleCall('gofas_nfeio', 'initial_date', $params['initial_date'], '', 'replaceVars');
        }

        foreach ($getQuery as $invoices) {
            foreach ($invoice['items']['item'] as $value) {
                $line_items[] = $value['description'];
            }
            $invoice = localAPI('GetInvoice', ['invoiceid' => $waiting->invoice_id], false);
            $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);
            foreach ($invoice['items']['item'] as $value) {
                $line_items[] = $value['description'];
            }
            $customer = gnfe_customer($invoices->userid, $client);
            $gnfe_get_nfes = gnfe_get_nfes();
            if ($params['rps_serial_number']) {
                $rps_serial_number = $params['rps_serial_number'];
                $rps_serial_number_ = false;
            } elseif (!$params['rps_serial_number'] and $gnfe_get_nfes['serviceInvoices']['0']['rpsSerialNumber']) {
                $rps_serial_number = $gnfe_get_nfes['serviceInvoices']['0']['rpsSerialNumber'];
                $rps_serial_number_ = $rps_serial_number;
            } elseif (!$params['rps_serial_number'] and !$gnfe_get_nfes['serviceInvoices']['0']['rpsSerialNumber']) {
                $rps_serial_number = 'IO';
                $rps_serial_number_ = $rps_serial_number;
            }
            if ($params['rps_number'] and (string) $params['rps_number'] !== (string) 'zero') {
                $rps_number = $params['rps_number'];
            } elseif ((!$params['rps_number'] or (string) $params['rps_number'] === (string) 'zero') and $gnfe_get_nfes['serviceInvoices']['0']['rpsNumber']) {
                $rps_number = $gnfe_get_nfes['serviceInvoices']['0']['rpsNumber'];
            } elseif (((string) $params['rps_number'] === (string) 'zero' and !$gnfe_get_nfes['serviceInvoices']['0']['rpsNumber']) or (!$params['rps_number'] and !$gnfe_get_nfes['serviceInvoices']['0']['rpsNumber'])) {
                $rps_number = 0;
            }

            if (2 == $customer['doc_type']) {
                $name = $client['companyname'];
            } elseif (1 == $customer['doc_type'] || 'CPF e/ou CNPJ ausente.' == $customer || !$customer['doc_type']) {
                $name = $client['fullname'];
            }
            $name = htmlspecialchars_decode($name);

            $service_code = $waiting->service_code ? $waiting->service_code : $params['service_code'];

            foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value']) as $gnfeWhmcsUrl) {
                $gnfeWhmcsUrl = $gnfeWhmcsUrl->value;
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
                    'servicesAmount' => $waiting->services_amount,
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
                    'rpsSerialNumber' => $rps_serial_number,
                    'rpsNumber' => (int) $rps_number + 1,
                ];
            } else {
                $postfields = [
                    'cityServiceCode' => $service_code,
                    'description' => $desc,
                    'servicesAmount' => $waiting->services_amount,
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
                    'rpsSerialNumber' => $rps_serial_number,
                    'rpsNumber' => (int) $rps_number + 1,
                ];
            }

            if ($params['debug']) {
                logModuleCall('gofas_nfeio', 'aftercronjob', $postfields, '', '', 'replaceVars');
            }
            $nfe = gnfe_issue_nfe($postfields);

            if ($nfe->message) {
                $error .= $nfe->message;
            }
            if (!$nfe->message) {
                $gnfe_update_nfe = gnfe_update_nfe($nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $waiting->id);
                if ($gnfe_update_nfe and 'success' !== $gnfe_update_nfe) {
                    $error = $gnfe_update_nfe;
                }
                $update_rps = gnfe_update_rps($rps_serial_number_, $rps_number);

                if ($update_rps and 'success' !== $update_rps) {
                    $error = $update_rps;
                }
            }
        }
        if ($params['debug']) {
            logModuleCall('gofas_nfeio', 'aftercronjob', ['$params' => $params, '$datepaid' => $datepaid, '$datepaid_to_issue' => $datepaid_to_issue], 'post', ['$processed_invoices' => $processed_invoices, '$nfe' => $nfe, 'error' => $error], 'replaceVars');
        }
    }
