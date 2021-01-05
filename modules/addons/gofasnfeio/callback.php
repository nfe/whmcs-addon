<?php

require_once __DIR__.'/../../../init.php';
use WHMCS\Database\Capsule;

$post = json_decode(file_get_contents('php://input'), true);
logModuleCall('gofas_nfeio', 'callback', $post, '', '', 'replaceVars');

if ($post) {
    require_once __DIR__.'/functions.php';
    $params = [];
    foreach (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->get(['setting', 'value']) as $settings) {
        $params[$settings->setting] = $settings->value;
    }
    foreach (Capsule::table('gofasnfeio')->where('nfe_id', '=', $post['id'])->
    get(['id', 'invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at']) as $key => $value) {
        $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
    }
    $nfe = $nfe_for_invoice['0'];
    logModuleCall('gofas_nfeio', 'callback2', $nfe, '', '', 'replaceVars');

    if ((string) $nfe['nfe_id'] === (string) $post['id'] and $nfe['status'] !== (string) $post['status']) {
        $new_nfe = [
            'invoice_id' => $nfe['invoice_id'],
            'user_id' => $nfe['user_id'],
            'nfe_id' => $nfe['nfe_id'],
            'status' => $post['status'],
            'services_amount' => $nfe['services_amount'],
            'environment' => $nfe['environment'],
            'flow_status' => $post['flowStatus'],
            'pdf' => $nfe['pdf'],
            'created_at' => $nfe['created_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $save_nfe = Capsule::table('gofasnfeio')->where('nfe_id', '=', $post['id'])->update($new_nfe);
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }
    if ($params['debug']) {
        logModuleCall('gofas_nfeio', 'receive_callback', ['post' => $post], 'post', ['nfe_local' => $nfe], 'replaceVars');
    }

    foreach (Capsule::table('gofasnfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->take(1)->get(['id', 'invoice_id', 'service_code', 'services_amount']) as $waiting) {
        //$invoices[]				= $Waiting->invoice_id;
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);

        if ('Manualmente' !== $params['issue_note']) {
            $getQuery = Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
        } else {
            $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
        }

        foreach ($getQuery as $invoices) {
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
            ///
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

            foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value']) as $gnfewhmcsadminurl) {
                $gnfewhmcsadminurl = $gnfewhmcsadminurl->value;
            }
            $desc = 'Nota referente a fatura #'.$waiting->invoice_id.'  '.$gnfewhmcsadminurl.'viewinvoice.php?id='.$waiting->invoice_id.'     ';
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
                            'street' => str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1'])),
                            'number' => preg_replace('/[^0-9]/', '', $client['address1']),
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
                            'street' => str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1'])),
                            'number' => preg_replace('/[^0-9]/', '', $client['address1']),
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
                logModuleCall('gofas_nfeio', 'callback', $postfields, '', '', 'replaceVars');
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
            logModuleCall('gofas_nfeio', 'after_receive_callback', ['$params' => $params, '$datepaid' => $datepaid, '$datepaid_to_issue' => $datepaid_to_issue], 'post', ['$processed_invoices' => $processed_invoices, '$nfe' => $nfe, 'error' => $error], 'replaceVars');
        }
    }
}
