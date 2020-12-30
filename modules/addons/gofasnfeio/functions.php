<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

// Get config
if (!function_exists('gnfe_config')) {
    function gnfe_config($set = false)
    {
        $setting = [];
        foreach (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->get(['setting', 'value']) as $settings) {
            $setting[$settings->setting] = $settings->value;
        }
        if ($set) {
            return $setting[$set];
        }

        return $setting;
    }
}
if (!function_exists('gnfe_customer')) {
    function gnfe_customer($user_id, $client)
    {
        //Determine custom fields id
        $customfields = [];
        foreach (Capsule::table('tblcustomfields')->where('type', '=', 'client')->get(['fieldname', 'id']) as $customfield) {
            $customfield_id = $customfield->id;
            $customfield_name = ' '.strtolower($customfield->fieldname);
            $insc_customfield_value = 'NF';
            // insc_municipal
            if ($customfield_id == gnfe_config('insc_municipal')) {
                foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                    $insc_customfield_value = $customfieldvalue->value;
                }
            }
            // cpf
            if (strpos($customfield_name, 'cpf') and !strpos($customfield_name, 'cnpj')) {
                foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                    $cpf_customfield_value = preg_replace('/[^0-9]/', '', $customfieldvalue->value);
                }
            }
            // cnpj
            if (strpos($customfield_name, 'cnpj') and !strpos($customfield_name, 'cpf')) {
                foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                    $cnpj_customfield_value = preg_replace('/[^0-9]/', '', $customfieldvalue->value);
                }
            }
            // cpf + cnpj
            if (strpos($customfield_name, 'cpf') and strpos($customfield_name, 'cnpj')) {
                foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                    $cpf_customfield_value = preg_replace('/[^0-9]/', '', $customfieldvalue->value);
                    $cnpj_customfield_value = preg_replace('/[^0-9]/', '', $customfieldvalue->value);
                }
            }
        }

        // Cliente possui CPF e CNPJ
        // CPF com 1 nº a menos, adiciona 0 antes do documento
        if (10 === strlen($cpf_customfield_value)) {
            $cpf = '0'.$cpf_customfield_value;
        }
        // CPF com 11 dígitos
        elseif (11 === strlen($cpf_customfield_value)) {
            $cpf = $cpf_customfield_value;
        }
        // CNPJ no campo de CPF com um dígito a menos
        elseif (13 === strlen($cpf_customfield_value)) {
            $cpf = false;
            $cnpj = '0'.$cpf_customfield_value;
        }
        // CNPJ no campo de CPF
        elseif (14 === strlen($cpf_customfield_value)) {
            $cpf = false;
            $cnpj = $cpf_customfield_value;
        }
        // cadastro não possui CPF
        elseif (!$cpf_customfield_value || 10 !== strlen($cpf_customfield_value) || 11 !== strlen($cpf_customfield_value) || 13 !== strlen($cpf_customfield_value) || 14 !== strlen($cpf_customfield_value)) {
            $cpf = false;
        }
        // CNPJ com 1 nº a menos, adiciona 0 antes do documento
        if (13 === strlen($cnpj_customfield_value)) {
            $cnpj = '0'.$cnpj_customfield_value;
        }
        // CNPJ com nº de dígitos correto
        elseif (14 === strlen($cnpj_customfield_value)) {
            $cnpj = $cnpj_customfield_value;
        }
        // Cliente não possui CNPJ
        elseif (!$cnpj_customfield_value and 14 !== strlen($cnpj_customfield_value) and 13 !== strlen($cnpj_customfield_value) and 13 !== strlen($cpf_customfield_value) and 14 !== strlen($cpf_customfield_value)) {
            $cnpj = false;
        }
        if (($cpf and $cnpj) or (!$cpf and $cnpj)) {
            $custumer['doc_type'] = 2;
            $custumer['document'] = $cnpj;
            if ($client['companyname']) {
                $custumer['name'] = $client['companyname'];
            } elseif (!$client['companyname']) {
                $custumer['name'] = $client['firstname'].' '.$client['lastname'];
            }
        } elseif ($cpf and !$cnpj) {
            $custumer['doc_type'] = 1;
            $custumer['document'] = $cpf;
            $custumer['name'] = $client['firstname'].' '.$client['lastname'];
        }
        if ('NF' != $insc_customfield_value) {
            $custumer['insc_municipal'] = $insc_customfield_value;
        }
        if (!$cpf and !$cnpj) {
            $error = 'CPF e/ou CNPJ ausente.';
        }
        if (!$error) {
            return $custumer;
        }
        if ($error) {
            return $custumer['error'] = $error;
        }
    }
}
if (!function_exists('gnfe_customfields')) {
    function gnfe_customfields()
    {
        //Determine custom fields id
        $customfields = [];
        foreach (Capsule::table('tblcustomfields')->where('type', '=', 'client')->get(['fieldname', 'id']) as $customfield) {
            $customfields[] = $customfield;
            $customfield_id = $customfield->id;
            $customfield_name = ' '.strtolower($customfield->fieldname);
        }

        return $customfields;
    }
}
if (!function_exists('gnfe_customfields_dropdow')) {
    function gnfe_customfields_dropdow()
    {
        //Determine custom fields id
        $customfields_array = [];
        foreach (Capsule::table('tblcustomfields')->where('type', '=', 'client')->get(['fieldname', 'id']) as $customfield) {
            $customfields_array[] = $customfield;
        }
        $customfields = json_decode(json_encode($customfields_array), true);
        if (!$customfields) {
            $dropFieldArray = ['0' => 'database error'];
        } elseif (count($customfields) >= 1) {
            $dropFieldArray = ['0' => 'selecione um campo'];
            foreach ($customfields as $key => $value) {
                $dropFieldArray[$value['id']] = $value['fieldname'];
            }
        } else {
            $dropFieldArray = ['0' => 'nothing to show'];
        }

        return $dropFieldArray;
    }
}
if (!function_exists('gnfe_country_code')) {
    function gnfe_country_code($country)
    {
        $array = ['BD' => 'BGD', 'BE' => 'BEL', 'BF' => 'BFA', 'BG' => 'BGR', 'BA' => 'BIH', 'BB' => 'BRB', 'WF' => 'WLF', 'BL' => 'BLM', 'BM' => 'BMU', 'BN' => 'BRN', 'BO' => 'BOL', 'BH' => 'BHR', 'BI' => 'BDI', 'BJ' => 'BEN', 'BT' => 'BTN', 'JM' => 'JAM', 'BV' => 'BVT', 'BW' => 'BWA', 'WS' => 'WSM', 'BQ' => 'BES', 'BR' => 'BRA', 'BS' => 'BHS', 'JE' => 'JEY', 'BY' => 'BLR', 'BZ' => 'BLZ', 'RU' => 'RUS', 'RW' => 'RWA', 'RS' => 'SRB', 'TL' => 'TLS', 'RE' => 'REU', 'TM' => 'TKM', 'TJ' => 'TJK', 'RO' => 'ROU', 'TK' => 'TKL', 'GW' => 'GNB', 'GU' => 'GUM', 'GT' => 'GTM', 'GS' => 'SGS', 'GR' => 'GRC', 'GQ' => 'GNQ', 'GP' => 'GLP', 'JP' => 'JPN', 'GY' => 'GUY', 'GG' => 'GGY', 'GF' => 'GUF', 'GE' => 'GEO', 'GD' => 'GRD', 'GB' => 'GBR', 'GA' => 'GAB', 'SV' => 'SLV', 'GN' => 'GIN', 'GM' => 'GMB', 'GL' => 'GRL', 'GI' => 'GIB', 'GH' => 'GHA', 'OM' => 'OMN', 'TN' => 'TUN', 'JO' => 'JOR', 'HR' => 'HRV', 'HT' => 'HTI', 'HU' => 'HUN', 'HK' => 'HKG', 'HN' => 'HND', 'HM' => 'HMD', 'VE' => 'VEN', 'PR' => 'PRI', 'PS' => 'PSE', 'PW' => 'PLW', 'PT' => 'PRT', 'SJ' => 'SJM', 'PY' => 'PRY', 'IQ' => 'IRQ', 'PA' => 'PAN', 'PF' => 'PYF', 'PG' => 'PNG', 'PE' => 'PER', 'PK' => 'PAK', 'PH' => 'PHL', 'PN' => 'PCN', 'PL' => 'POL', 'PM' => 'SPM', 'ZM' => 'ZMB', 'EH' => 'ESH', 'EE' => 'EST', 'EG' => 'EGY', 'ZA' => 'ZAF', 'EC' => 'ECU', 'IT' => 'ITA', 'VN' => 'VNM', 'SB' => 'SLB', 'ET' => 'ETH', 'SO' => 'SOM', 'ZW' => 'ZWE', 'SA' => 'SAU', 'ES' => 'ESP', 'ER' => 'ERI', 'ME' => 'MNE', 'MD' => 'MDA', 'MG' => 'MDG', 'MF' => 'MAF', 'MA' => 'MAR', 'MC' => 'MCO', 'UZ' => 'UZB', 'MM' => 'MMR', 'ML' => 'MLI', 'MO' => 'MAC', 'MN' => 'MNG', 'MH' => 'MHL', 'MK' => 'MKD', 'MU' => 'MUS', 'MT' => 'MLT', 'MW' => 'MWI', 'MV' => 'MDV', 'MQ' => 'MTQ', 'MP' => 'MNP', 'MS' => 'MSR', 'MR' => 'MRT', 'IM' => 'IMN', 'UG' => 'UGA', 'TZ' => 'TZA', 'MY' => 'MYS', 'MX' => 'MEX', 'IL' => 'ISR', 'FR' => 'FRA', 'IO' => 'IOT', 'SH' => 'SHN', 'FI' => 'FIN', 'FJ' => 'FJI', 'FK' => 'FLK', 'FM' => 'FSM', 'FO' => 'FRO', 'NI' => 'NIC', 'NL' => 'NLD', 'NO' => 'NOR', 'NA' => 'NAM', 'VU' => 'VUT', 'NC' => 'NCL', 'NE' => 'NER', 'NF' => 'NFK', 'NG' => 'NGA', 'NZ' => 'NZL', 'NP' => 'NPL', 'NR' => 'NRU', 'NU' => 'NIU', 'CK' => 'COK', 'XK' => 'XKX', 'CI' => 'CIV', 'CH' => 'CHE', 'CO' => 'COL', 'CN' => 'CHN', 'CM' => 'CMR', 'CL' => 'CHL', 'CC' => 'CCK', 'CA' => 'CAN', 'CG' => 'COG', 'CF' => 'CAF', 'CD' => 'COD', 'CZ' => 'CZE', 'CY' => 'CYP', 'CX' => 'CXR', 'CR' => 'CRI', 'CW' => 'CUW', 'CV' => 'CPV', 'CU' => 'CUB', 'SZ' => 'SWZ', 'SY' => 'SYR', 'SX' => 'SXM', 'KG' => 'KGZ', 'KE' => 'KEN', 'SS' => 'SSD', 'SR' => 'SUR', 'KI' => 'KIR', 'KH' => 'KHM', 'KN' => 'KNA', 'KM' => 'COM', 'ST' => 'STP', 'SK' => 'SVK', 'KR' => 'KOR', 'SI' => 'SVN', 'KP' => 'PRK', 'KW' => 'KWT', 'SN' => 'SEN', 'SM' => 'SMR', 'SL' => 'SLE', 'SC' => 'SYC', 'KZ' => 'KAZ', 'KY' => 'CYM', 'SG' => 'SGP', 'SE' => 'SWE', 'SD' => 'SDN', 'DO' => 'DOM', 'DM' => 'DMA', 'DJ' => 'DJI', 'DK' => 'DNK', 'VG' => 'VGB', 'DE' => 'DEU', 'YE' => 'YEM', 'DZ' => 'DZA', 'US' => 'USA', 'UY' => 'URY', 'YT' => 'MYT', 'UM' => 'UMI', 'LB' => 'LBN', 'LC' => 'LCA', 'LA' => 'LAO', 'TV' => 'TUV', 'TW' => 'TWN', 'TT' => 'TTO', 'TR' => 'TUR', 'LK' => 'LKA', 'LI' => 'LIE', 'LV' => 'LVA', 'TO' => 'TON', 'LT' => 'LTU', 'LU' => 'LUX', 'LR' => 'LBR', 'LS' => 'LSO', 'TH' => 'THA', 'TF' => 'ATF', 'TG' => 'TGO', 'TD' => 'TCD', 'TC' => 'TCA', 'LY' => 'LBY', 'VA' => 'VAT', 'VC' => 'VCT', 'AE' => 'ARE', 'AD' => 'AND', 'AG' => 'ATG', 'AF' => 'AFG', 'AI' => 'AIA', 'VI' => 'VIR', 'IS' => 'ISL', 'IR' => 'IRN', 'AM' => 'ARM', 'AL' => 'ALB', 'AO' => 'AGO', 'AQ' => 'ATA', 'AS' => 'ASM', 'AR' => 'ARG', 'AU' => 'AUS', 'AT' => 'AUT', 'AW' => 'ABW', 'IN' => 'IND', 'AX' => 'ALA', 'AZ' => 'AZE', 'IE' => 'IRL', 'ID' => 'IDN', 'UA' => 'UKR', 'QA' => 'QAT', 'MZ' => 'MOZ'];

        return $array[$country];
    }
}
if (!function_exists('gnfe_ibge')) {
    function gnfe_ibge($zip)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://open.nfe.io/v1/cities/'.$zip.'/postalcode');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        $city = json_decode(json_encode(json_decode($response)));

        return $city->city->code;
    }
}
if (!function_exists('gnfe_queue_nfe')) {
    function gnfe_queue_nfe($invoice_id, $create_all = false)
    {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoice_id], false);
        $itens = get_prodict_invoice($invoice_id);
        logModuleCall('gofas_nfeio', 'itens teste', $itens, '', '', 'replaceVars');

        if (!$itens) {
            foreach (Capsule::table('tblinvoiceitems')->where('invoiceid', '=', $invoice_id)->get(['userid', 'amount']) as $item_not_salle) {
                $data = [
                    'invoice_id' => $invoice_id,
                    'user_id' => $item_not_salle->userid,
                    'nfe_id' => 'waiting',
                    'status' => 'Waiting',
                    'services_amount' => $item_not_salle->amount,
                    'environment' => 'waiting',
                    'flow_status' => 'waiting',
                    'pdf' => 'waiting',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => 'waiting',
                    'rpsSerialNumber' => 'waiting',
                ];

                try {
                    $service_code_row = Capsule::table('gofasnfeio')->whereNull('service_code')->where('invoice_id', '=', $invoice_id)->get(['id', 'services_amount']);
                    logModuleCall('gofas_nfeio', 'service_code_row', $service_code_row, '', '', 'replaceVars');

                    if (1 == count($service_code_row)) {
                        $mountDB = floatval($service_code_row[0]->services_amount);
                        $mount_item = floatval($item_not_salle->amount);
                        $mount = $mountDB + $mount_item;
                        $update_nfe = Capsule::table('gofasnfeio')->where('id', '=', $service_code_row[0]->id)->update(['services_amount' => $mount]);
                    } else {
                        $save_nfe = Capsule::table('gofasnfeio')->insert($data);
                    }
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }

            return 'success';
        }
        foreach ($itens as $item) {
            $data = [
                'invoice_id' => $invoice_id,
                'user_id' => $invoice['userid'],
                'nfe_id' => 'waiting',
                'status' => 'Waiting',
                'services_amount' => $item['monthly'],
                'environment' => 'waiting',
                'flow_status' => 'waiting',
                'pdf' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => 'waiting',
                'rpsSerialNumber' => 'waiting',
                'service_code' => $item['value'],
            ];

            $nfe_for_invoice = gnfe_get_local_nfe($invoice_id, ['status']);
            if (!$nfe_for_invoice['status'] || $create_all) {
                $create_all = true;

                try {
                    $service_code_row = Capsule::table('gofasnfeio')->where('service_code', '=', $item['value'])->where('invoice_id', '=', $invoice_id)->get(['id', 'services_amount']);

                    if (1 == count($service_code_row)) {
                        $mountDB = floatval($service_code_row[0]->services_amount);
                        $mount_item = floatval($item['monthly']);
                        $mount = $mountDB + $mount_item;

                        $update_nfe = Capsule::table('gofasnfeio')->where('id', '=', $service_code_row[0]->id)->update(['services_amount' => $mount]);
                    } else {
                        $save_nfe = Capsule::table('gofasnfeio')->insert($data);
                    }
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            } elseif ((string) $nfe_for_invoice['status'] === (string) 'Cancelled' or (string) $nfe_for_invoice['status'] === (string) 'Error') {
                try {
                    $update_nfe = Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->update($data);
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        }

        return 'success';
    }
}

if (!function_exists('gnfe_queue_nfe_edit')) {
    function gnfe_queue_nfe_edit($invoice_id, $gofasnfeio_id)
    {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoice_id], false);
        $itens = get_prodict_invoice($invoice_id);

        foreach ($itens as $item) {
            $data = [
                'invoice_id' => $invoice_id,
                'user_id' => $invoice['userid'],
                'nfe_id' => 'waiting',
                'status' => 'Waiting',
                'services_amount' => $item['monthly'],
                'environment' => 'waiting',
                'flow_status' => 'waiting',
                'pdf' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => 'waiting',
                'rpsSerialNumber' => 'waiting',
                'service_code' => $item['value'],
            ];

            $nfe_for_invoice = gnfe_get_local_nfe($invoice_id, ['status']);

            if ((string) $nfe_for_invoice['status'] === (string) 'Cancelled' or (string) $nfe_for_invoice['status'] === (string) 'Error') {
                try {
                    $update_nfe = Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->where('id', '=', $gofasnfeio_id)->update($data);
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        }

        return 'success';
    }
}

// if (!function_exists('gnfe_queue_nfe_edit_all')) {
//     function gnfe_queue_nfe_edit_all($invoice_id)
//     {
//         $invoice = localAPI('GetInvoice', ['invoiceid' => $invoice_id], false);
//         $itens = get_prodict_invoice($invoice_id);
//         foreach ($itens as $item) {
//             $data = [
//                 'invoice_id' => $invoice_id,
//                 'user_id' => $invoice['userid'],
//                 'nfe_id' => 'waiting',
//                 'status' => 'Waiting',
//                 'services_amount' => $item['monthly'],
//                 'environment' => 'waiting',
//                 'flow_status' => 'waiting',
//                 'pdf' => 'waiting',
//                 'created_at' => date('Y-m-d H:i:s'),
//                 'updated_at' => 'waiting',
//                 'rpsSerialNumber' => 'waiting',
//                 'service_code' => $item['value'],
//             ];

//             $nfe_for_invoice = gnfe_get_local_nfe($invoice_id, ['status']);

//             if (!$nfe_for_invoice['status']) {
//                 try {
//                     $save_nfe = Capsule::table('gofasnfeio')->insert($data);
//                 } catch (\Exception $e) {
//                     return $e->getMessage();
//                 }
//             }
//         }

//         return 'success';
//     }
// }

if (!function_exists('gnfe_issue_nfe')) {
    function gnfe_issue_nfe($postfields)
    {
        $webhook_url = gnfe_whmcs_url().'modules/addons/gofasnfeio/callback.php';
        foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfe_webhook_id')->get(['value']) as $gnfe_webhook_id_) {
            $gnfe_webhook_id = $gnfe_webhook_id_->value;
        }
        if ($gnfe_webhook_id) {
            $check_webhook = gnfe_check_webhook($gnfe_webhook_id);
            if ($check_webhook['message']) {
                $error .= $check_webhook['message'];
            }
        }
        if ($gnfe_webhook_id and (string) $check_webhook['hooks']['url'] !== (string) $webhook_url) {
            $create_webhook = gnfe_create_webhook($webhook_url);
            if ($create_webhook['message']) {
                $error .= $create_webhook['message'];
            }
            if ($create_webhook['hooks']['id']) {
                try {
                    Capsule::table('tblconfiguration')->where('setting', 'gnfe_webhook_id')->update(['value' => $create_webhook['hooks']['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $error .= $e->getMessage();
                }
            }
            $delete_webhook = gnfe_delete_webhook($gnfe_webhook_id);
            if ($delete_webhook['message']) {
                $error .= $create_webhook['message'];
            }
        }
        if (!$gnfe_webhook_id) {
            $create_webhook = gnfe_create_webhook($webhook_url);
            if ($create_webhook['message']) {
                $error .= $create_webhook['message'];
            }
            if ($create_webhook['hooks']['id']) {
                try {
                    Capsule::table('tblconfiguration')->insert(['setting' => 'gnfe_webhook_id', 'value' => $create_webhook['hooks']['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $error .= $e->getMessage();
                }
            }
        }
        if (gnfe_config('debug')) {
            logModuleCall('gofas_nfeio', 'check_webhook', ['gnfe_webhook_id' => $gnfe_webhook_id, 'check_webhook' => $check_webhook, 'check_webhook_url' => $check_webhook['hooks']['url']], 'post', ['create_webhook' => $create_webhook, 'delete_webhook' => $delete_webhook, 'error' => $error], 'replaceVars');
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        logModuleCall('gofas_nfeio', 'resp', $response, '', '', 'replaceVars');

        return json_decode(json_encode(json_decode($response)));
    }
}
if (!function_exists('gnfe_get_nfe')) {
    function gnfe_get_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
if (!function_exists('gnfe_get_nfes')) {
    function gnfe_get_nfes()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices?pageCount=1&pageIndex=1');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}
if (!function_exists('gnfe_get_invoice_nfes')) {
    function gnfe_get_invoice_nfes($invoice_id)
    {
        $nfes = [];
        // foreach( Capsule::table('tbladdonmodules') -> where( 'module', '=', 'gofasnfeio' ) -> get( array( 'setting', 'value') ) as $settings ) {
        foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get(['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at', 'rpsSerialNumber', 'rpsNumber']) as $nfe) {
            $nfes = $nfe;
        }

        $checkfields = json_decode(json_encode($nfes), true);

        if (!$checkfields) {
            $fieldArray = ['status' => 'error', 'message' => 'database error'];
        } elseif (count($checkfields) >= 1) {
            $fieldArray = ['status' => 'success', 'result' => $checkfields];
        } else {
            $fieldArray = ['status' => 'error', 'message' => 'nothing to show'];
        }

        return $fieldArray;
    }
}
if (!function_exists('gnfe_delete_nfe')) {
    function gnfe_delete_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
if (!function_exists('gnfe_email_nfe')) {
    function gnfe_email_nfe($nf)
    {
        if ('on' == gnfe_config('gnfe_email_nfe_config')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/sendemail');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response);
        }
    }
}
if (!function_exists('gnfe_pdf_nfe')) {
    function gnfe_pdf_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/pdf');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/pdf', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        header('Content-type: application/pdf');
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}
if (!function_exists('gnfe_xml_nfe')) {
    function gnfe_xml_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/xml');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
if (!function_exists('gnfe_whmcs_url')) {
    function gnfe_whmcs_url()
    {
        foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsurl')->get(['value']) as $gnfewhmcsurl_) {
            $gnfewhmcsurl = $gnfewhmcsurl_->value;
        }

        return $gnfewhmcsurl;
    }
}

if (!function_exists('gnfe_xml_nfe')) {
    function gnfe_xml_nfe($nf)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/xml',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization:'.gnfe_config('api_key'),
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}

if (!function_exists('gnfe_whmcs_admin_url')) {
    function gnfe_whmcs_admin_url()
    {
        foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminurl')->get(['value']) as $gnfewhmcsadminurl_) {
            $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
        }

        return $gnfewhmcsadminurl;
    }
}

if (!function_exists('gnfe_save_nfe')) {
    function gnfe_save_nfe($nfe, $user_id, $invoice_id, $pdf, $created_at, $updated_at)
    {
        $data = [
            'invoice_id' => $invoice_id,
            'user_id' => $user_id,
            'nfe_id' => $nfe->id,
            'status' => $nfe->status,
            'services_amount' => $nfe->servicesAmount,
            'environment' => $nfe->environment,
            'flow_status' => $nfe->flowStatus,
            'pdf' => $pdf,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'rpsSerialNumber' => $nfe->rpsSerialNumber,
            'rpsNumber' => $nfe->rpsNumber,
        ];

        try {
            $save_nfe = Capsule::table('gofasnfeio')->insert($data);

            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
if (!function_exists('gnfe_update_nfe')) {
    function gnfe_update_nfe($nfe, $user_id, $invoice_id, $pdf, $created_at, $updated_at, $id_gofasnfeio = false)
    {
        $data = [
            'invoice_id' => $invoice_id,
            'user_id' => $user_id,
            'nfe_id' => $nfe->id,
            'status' => $nfe->status,
            'services_amount' => $nfe->servicesAmount,
            'environment' => $nfe->environment,
            'flow_status' => $nfe->flowStatus,
            'pdf' => $pdf,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'rpsSerialNumber' => $nfe->rpsSerialNumber,
            'rpsNumber' => $nfe->rpsNumber,
        ];
        logModuleCall('gofas_nfeio', '$data', $data, '', '', 'replaceVars');

        try {
            if (!$id_gofasnfeio) {
                $id = $invoice_id;
                $camp = 'invoice_id';
            } else {
                $id = $id_gofasnfeio;
                $camp = 'id';
            }
            $save_nfe = Capsule::table('gofasnfeio')->where($camp, '=', $id)->update($data);

            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
if (!function_exists('gnfe_update_rps')) {
    function gnfe_update_rps($rps_serial_number, $rps_number)
    {
        $setting = [];
        foreach (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->get(['setting', 'value']) as $settings) {
            $setting[$settings->setting] = $settings->value;
        }

        try {
            if ($rps_serial_number) {
                $update_rps_serial_number = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'rps_serial_number')->update(['value' => $rps_serial_number]);
            }
            if ($rps_number) {
                $update_serial_number = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'rps_number')->update(['value' => $rps_number + 1]);
            }

            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
if (!function_exists('gnfe_get_local_nfe')) {
    function gnfe_get_local_nfe($invoice_id, $values)
    {
        foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get($values) as $key => $value) {
            $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
        }

        return $nfe_for_invoice['0'];
    }
}
if (!function_exists('gnfe_check_webhook')) {
    function gnfe_check_webhook($id)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/hooks/'.$id);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode(json_encode(json_decode($response)), true);
    }
}
if (!function_exists('gnfe_create_webhook')) {
    function gnfe_create_webhook($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/hooks');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['url' => $url, 'contentType' => 'application/json', 'secret' => (string) time(), 'events' => ['issue', 'cancel'], 'status' => 'Active']));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode(json_encode(json_decode($response)), true);
    }
}
if (!function_exists('gnfe_delete_webhook')) {
    function gnfe_delete_webhook($id)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/hooks/'.$id);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode(json_encode(json_decode($response)), true);
    }
}
/*
 * @gnfe_nfe_flowStatus string
 * Possible values:
 * CancelFailed, IssueFailed, Issued, Cancelled, PullFromCityHall, WaitingCalculateTaxes,
 * WaitingDefineRpsNumber, WaitingSend, WaitingSendCancel, WaitingReturn, WaitingDownload
 */
if (!function_exists('gnfe_nfe_flowStatus')) {
    function gnfe_nfe_flowStatus($flowStatus)
    {
        if ('CancelFailed' === $flowStatus) {
            $status = 'Cancelado por Erro';
        }
        if ('IssueFailed' === $flowStatus) {
            $status = 'Falha ao Emitir';
        }
        if ('Issued' === $flowStatus) {
            $status = 'Emitida';
        }
        if ('Cancelled' === $flowStatus) {
            $status = 'Cancelada';
        }
        if ('PullFromCityHall' === $flowStatus) {
            $status = 'Obtendo da Prefeitura';
        }
        if ('WaitingCalculateTaxes' === $flowStatus) {
            $status = 'Aguardando Calcular Impostos';
        }
        if ('WaitingDefineRpsNumber' === $flowStatus) {
            $status = 'Aguardando Definir Número Rps';
        }
        if ('WaitingSend' === $flowStatus) {
            $status = 'Aguardando Enviar';
        }
        if ('WaitingSendCancel' === $flowStatus) {
            $status = 'Aguardando Cancelar Envio';
        }
        if ('WaitingReturn' === $flowStatus) {
            $status = 'Aguardando Retorno';
        }
        if ('WaitingDownload' === $flowStatus) {
            $status = 'Aguardando Download';
        }

        return $status;
    }
}

if (!function_exists('gnfe_get_company')) {
    function gnfe_get_company()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

if (!function_exists('set_custom_field_ini_date')) {
    function set_custom_field_ini_date()
    {
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);

        try {
            if (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'initial_date')->count() < 1) {
                Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'initial_date', 'value' => $dataAtual]);
            }
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }
}

if (!function_exists('gnfe_get_company')) {
    function gnfe_get_company()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

if (!function_exists('gnfe_customer_service_code')) {
    function gnfe_customer_service_code($item_id)
    {
        $customfields = [];
        foreach (Capsule::table('tblcustomfields')->where('type', '=', 'product')->where('fieldname', '=', 'Código de Serviço')->get(['fieldname', 'id']) as $customfield) {
            $customfield_id = $customfield->id;
            $insc_customfield_value = 'NF';
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $customfield_id)->where('relid', '=', $item_id)->get(['value']) as $customfieldvalue) {
                return $customfieldvalue->value;
            }
        }
    }
}

function get_prodict_invoice($invoice_id)
{
    $query = "SELECT DISTINCT tblhosting.packageid,tblpricing.monthly from  tblhosting
    INNER JOIN tblinvoiceitems ON tblinvoiceitems.relid = tblhosting.id 
    JOIN tblpricing ON tblpricing.relid = tblhosting.packageid
    LEFT JOIN tblproducts ON tblproducts.id = tblhosting.packageid 
    WHERE tblpricing.type = 'product'  AND tblinvoiceitems.invoiceid = :id";
    $pdo = Capsule::connection()->getPdo();
    $pdo->beginTransaction();
    $tax_check = gnfe_config('tax');
    $row = null;
    $list = [];

    if ('Sim' != $tax_check) {
        logModuleCall('gofas_nfeio', 'true get_prodict_invoice', $tax_check, '', '', 'replaceVars');

        $query .= 'AND tblproducts.tax = 1';
    } else {
        logModuleCall('gofas_nfeio', 'false get_prodict_invoice', $tax_check, '', '', 'replaceVars');

        Capsule::table('tblproducts')->update(['tax' => 1]);
    }

    try {
        $statement = $pdo->prepare($query);
        $statement->execute([':id' => $invoice_id]);
        $row = $statement->fetchAll();
        $pdo->commit();
    } catch (\Throwable $th) {
        $pdo->rollBack();
        logModuleCall('gofas_nfeio', 'erroGetProdictInvoice', $th, '', '', 'replaceVars');
    }
    foreach ($row as $item) {
        $pdo->beginTransaction();

        try {
            $list2 = [];
            $stmt = $pdo->prepare('SELECT * FROM tblproductcode WHERE product_id=:PROD');
            $stmt->execute([':PROD' => $item[0]]);
            $row = $stmt->fetchAll();
            $pdo->commit();
            $list2['item'] = $item['packageid'];
            $list2['value'] = $row[0]['code_service'];
            $list2['monthly'] = $item['monthly'];
            $list[] = $list2;
        } catch (\Throwable $th) {
            $pdo->rollBack();
            logModuleCall('gofas_nfeio', 'erroForeach', $th, '', '', 'replaceVars');
        }
    }

    return $list;
}

if (!function_exists('set_code_service_camp_gofasnfeio')) {
    function set_code_service_camp_gofasnfeio()
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare('ALTER TABLE gofasnfeio ADD service_code TEXT;
            ALTER TABLE gofasnfeio ADD monthly DECIMAL(16,2)');
            $statement->execute();
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }
    }
}
if (!function_exists('create_table_product_code')) {
    function create_table_product_code()
    {
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare('CREATE TABLE tblproductcode (
                    id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    product_id int(10) NOT NULL,
                    code_service int(10) NOT NULL,
                    create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    update_at TIMESTAMP NULL,
                    ID_user int(10) NOT NULL)');
            $statement->execute();
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }
    }
}

function update_table()
{
    $current_version = '1.2.5';
    $row = Capsule::table('tblconfiguration')->where('setting', '=', 'version_nfeio')->get(['value']);
    $version = $row[0];
    if ($version != $current_version) {
        create_table_product_code();
        set_code_service_camp_gofasnfeio();
    } else {
        Capsule::table('tblconfiguration')->insert(['setting' => 'version_nfeio', 'value' => $current_version, 'created_at' => date('Y-m-d H:i:s')]);
    }
}
