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
require_once __DIR__ . '/../../../init.php';
use WHMCS\Database\Capsule;
$post = json_decode(file_get_contents('php://input'), true);
if ($post) {
    require_once __DIR__ . '/functions.php';
    $params = [];
    foreach ( Capsule::table('tbladdonmodules')->where( 'module', '=', 'gofasnfeio' )->get( ['setting', 'value'] ) as $settings ) {
        $params[$settings->setting] = $settings->value;
    }
    foreach ( Capsule::table('gofasnfeio')->where('nfe_id', '=', $post['id'])->
    get(['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at']) as $key => $value ) {
        $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
    }
    $nfe = $nfe_for_invoice['0'];
    if ((string)$nfe['nfe_id'] === (string)$post['id'] and $nfe['status'] !== (string)$post['status'] ) {
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
        logModuleCall('gofas_nfeio', 'receive_callback', ['post' => $post], 'post',  ['nfe_local' => $nfe], 'replaceVars');
    }
    foreach ( Capsule::table('gofasnfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->take(1)->get( ['invoice_id']) as $waiting ) {
        foreach ( Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get( ['id', 'userid', 'total'] ) as $invoices ) {
            $invoice = localAPI('GetInvoice',  ['invoiceid' => $waiting->invoice_id], false);
            $client = localAPI('GetClientsDetails',['clientid' => $invoice['userid'], 'stats' => false, ], false);
            foreach ( $invoice['items']['item'] as $value) {
                $line_items[] = $value['description'];
            }
            $customer = gnfe_customer($invoices->userid,$client);
            $gnfe_get_nfes = gnfe_get_nfes();
            if ( $params['rps_serial_number'] ) {
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
            if ($params['rps_number'] and (string)$params['rps_number'] !== (string)'zero') {
                $rps_number = $params['rps_number'];
            } elseif ((!$params['rps_number'] or (string)$params['rps_number'] === (string)'zero' ) and $gnfe_get_nfes['serviceInvoices']['0']['rpsNumber']) {
                $rps_number = $gnfe_get_nfes['serviceInvoices']['0']['rpsNumber'];
            } elseif (((string)$params['rps_number'] === (string)'zero' and !$gnfe_get_nfes['serviceInvoices']['0']['rpsNumber']) or (!$params['rps_number'] and !$gnfe_get_nfes['serviceInvoices']['0']['rpsNumber'])) {
                $rps_number = 0;
            }

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
                    'servicesAmount' => $invoice['total'],
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
                    'rpsSerialNumber' => $rps_serial_number,
                    'rpsNumber' => (int)$rps_number + 1,
                ];
            } else {
                $postfields = [
                    'cityServiceCode' => $params['service_code'],
                    'description' => substr(implode("\n", $line_items), 0, 600),
                    'servicesAmount' => $invoice['total'],
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
                    'rpsSerialNumber' => $rps_serial_number,
                    'rpsNumber' => (int)$rps_number + 1,
                ];
            }

            if ($params['debug']) {
                logModuleCall('gofas_nfeio', 'callback',$postfields , '',  '', 'replaceVars');
            }

            $nfe = gnfe_issue_nfe($postfields);
            if ($nfe->message) {
                $error .= $nfe->message;
            }
            if (!$nfe->message) {
                $gnfe_update_nfe = gnfe_update_nfe($nfe,$invoices->userid,$invoices->id,'n/a',date('Y-m-d H:i:s'),date('Y-m-d H:i:s'));
                if ($gnfe_update_nfe and $gnfe_update_nfe !== 'success') {
                    $error = $gnfe_update_nfe;
                }
                $update_rps = gnfe_update_rps($rps_serial_number_, $rps_number);
                if ($update_rps and $update_rps !== 'success') {
                    $error = $update_rps;
                }
            }
        }
        if ($params['debug']) {
            logModuleCall('gofas_nfeio', 'after_receive_callback', ['$params' => $params, '$datepaid' => $datepaid, '$datepaid_to_issue' => $datepaid_to_issue], 'post',  ['$processed_invoices' => $processed_invoices, '$nfe' => $nfe, 'error' => $error], 'replaceVars');
        }
    }
}