<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

/**
 * Pega os dados da tabela tbladdonmodules do banco de dados da WHMCS.
 */
if (!function_exists('gnfe_config')) {
    function gnfe_config($set = false) {
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
    function gnfe_customer($user_id, $client) {
        //Determine custom fields id
        $CPF_id = gnfe_config('cpf_camp');
        $CNPJ_id = gnfe_config('cnpj_camp');
        $insc_municipal_id = gnfe_config('insc_municipal');

        $insc_customfield_value = 'NF';
        // insc_municipal
        if ($insc_municipal_id != 0) {
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $insc_municipal_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                $insc_customfield_value = $customfieldvalue->value;
            }
        }
        // cpf
        if ($CPF_id != 0) {
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $CPF_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                $cpf_customfield_value = preg_replace('/[^0-9]/', '', $customfieldvalue->value);
            }
        }
        //cnpj
        if ($CNPJ_id != 0) {
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $CNPJ_id)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                $cnpj_customfield_value = preg_replace('/[^0-9]/', '', $customfieldvalue->value);
            }
        }
        logModuleCall('gofas_nfeio', 'gnfe_customer-cpf', $cpf_customfield_value, '','', '');
        logModuleCall('gofas_nfeio', 'gnfe_customer-cnpj', $cnpj_customfield_value, '','', '');
        logModuleCall('gofas_nfeio', 'gnfe_customer-municipal', $insc_customfield_value, '','', '');

        // Cliente possui CPF e CNPJ
        // CPF com 1 nº a menos, adiciona 0 antes do documento
        if (strlen($cpf_customfield_value) === 10) {
            $cpf = '0' . $cpf_customfield_value;
        }
        // CPF com 11 dígitos
        elseif (strlen($cpf_customfield_value) === 11) {
            $cpf = $cpf_customfield_value;
        }
        // CNPJ no campo de CPF com um dígito a menos
        elseif (strlen($cpf_customfield_value) === 13) {
            $cpf = false;
            $cnpj = '0' . $cpf_customfield_value;
        }
        // CNPJ no campo de CPF
        elseif (strlen($cpf_customfield_value) === 14) {
            $cpf = false;
            $cnpj = $cpf_customfield_value;
        }
        // cadastro não possui CPF
        elseif (!$cpf_customfield_value || strlen($cpf_customfield_value) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen($cpf_customfield_value) != 13 || strlen($cpf_customfield_value) !== 14) {
            $cpf = false;
        }
        // CNPJ com 1 nº a menos, adiciona 0 antes do documento
        if (strlen($cnpj_customfield_value) === 13) {
            $cnpj = '0' . $cnpj_customfield_value;
        }
        // CNPJ com nº de dígitos correto
        elseif (strlen($cnpj_customfield_value) === 14) {
            $cnpj = $cnpj_customfield_value;
        }
        // Cliente não possui CNPJ
        elseif (!$cnpj_customfield_value and strlen($cnpj_customfield_value) !== 14 and strlen($cnpj_customfield_value) !== 13 and strlen($cpf_customfield_value) !== 13 and strlen($cpf_customfield_value) !== 14) {
            $cnpj = false;
        }
        if (($cpf and $cnpj) or (!$cpf and $cnpj)) {
            $custumer['doc_type'] = 2;
            $custumer['document'] = $cnpj;
            if ($client['companyname']) {
                $custumer['name'] = $client['companyname'];
            } elseif (!$client['companyname']) {
                $custumer['name'] = $client['firstname'] . ' ' . $client['lastname'];
            }
        } elseif ($cpf and !$cnpj) {
            $custumer['doc_type'] = 1;
            $custumer['document'] = $cpf;
            $custumer['name'] = $client['firstname'] . ' ' . $client['lastname'];
        }
        if ($insc_customfield_value != 'NF') {
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
    function gnfe_customfields() {
        //Determine custom fields id
        $customfields = [];
        foreach (Capsule::table('tblcustomfields')->where('type', '=', 'client')->get(['fieldname', 'id']) as $customfield) {
            $customfields[] = $customfield;
            $customfield_id = $customfield->id;
            $customfield_name = ' ' . strtolower($customfield->fieldname);
        }

        return $customfields;
    }
}
if (!function_exists('gnfe_customfields_dropdow')) {
    function gnfe_customfields_dropdow() {
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
    function gnfe_country_code($country) {
        $array = ['BD' => 'BGD', 'BE' => 'BEL', 'BF' => 'BFA', 'BG' => 'BGR', 'BA' => 'BIH', 'BB' => 'BRB', 'WF' => 'WLF', 'BL' => 'BLM', 'BM' => 'BMU', 'BN' => 'BRN', 'BO' => 'BOL', 'BH' => 'BHR', 'BI' => 'BDI', 'BJ' => 'BEN', 'BT' => 'BTN', 'JM' => 'JAM', 'BV' => 'BVT', 'BW' => 'BWA', 'WS' => 'WSM', 'BQ' => 'BES', 'BR' => 'BRA', 'BS' => 'BHS', 'JE' => 'JEY', 'BY' => 'BLR', 'BZ' => 'BLZ', 'RU' => 'RUS', 'RW' => 'RWA', 'RS' => 'SRB', 'TL' => 'TLS', 'RE' => 'REU', 'TM' => 'TKM', 'TJ' => 'TJK', 'RO' => 'ROU', 'TK' => 'TKL', 'GW' => 'GNB', 'GU' => 'GUM', 'GT' => 'GTM', 'GS' => 'SGS', 'GR' => 'GRC', 'GQ' => 'GNQ', 'GP' => 'GLP', 'JP' => 'JPN', 'GY' => 'GUY', 'GG' => 'GGY', 'GF' => 'GUF', 'GE' => 'GEO', 'GD' => 'GRD', 'GB' => 'GBR', 'GA' => 'GAB', 'SV' => 'SLV', 'GN' => 'GIN', 'GM' => 'GMB', 'GL' => 'GRL', 'GI' => 'GIB', 'GH' => 'GHA', 'OM' => 'OMN', 'TN' => 'TUN', 'JO' => 'JOR', 'HR' => 'HRV', 'HT' => 'HTI', 'HU' => 'HUN', 'HK' => 'HKG', 'HN' => 'HND', 'HM' => 'HMD', 'VE' => 'VEN', 'PR' => 'PRI', 'PS' => 'PSE', 'PW' => 'PLW', 'PT' => 'PRT', 'SJ' => 'SJM', 'PY' => 'PRY', 'IQ' => 'IRQ', 'PA' => 'PAN', 'PF' => 'PYF', 'PG' => 'PNG', 'PE' => 'PER', 'PK' => 'PAK', 'PH' => 'PHL', 'PN' => 'PCN', 'PL' => 'POL', 'PM' => 'SPM', 'ZM' => 'ZMB', 'EH' => 'ESH', 'EE' => 'EST', 'EG' => 'EGY', 'ZA' => 'ZAF', 'EC' => 'ECU', 'IT' => 'ITA', 'VN' => 'VNM', 'SB' => 'SLB', 'ET' => 'ETH', 'SO' => 'SOM', 'ZW' => 'ZWE', 'SA' => 'SAU', 'ES' => 'ESP', 'ER' => 'ERI', 'ME' => 'MNE', 'MD' => 'MDA', 'MG' => 'MDG', 'MF' => 'MAF', 'MA' => 'MAR', 'MC' => 'MCO', 'UZ' => 'UZB', 'MM' => 'MMR', 'ML' => 'MLI', 'MO' => 'MAC', 'MN' => 'MNG', 'MH' => 'MHL', 'MK' => 'MKD', 'MU' => 'MUS', 'MT' => 'MLT', 'MW' => 'MWI', 'MV' => 'MDV', 'MQ' => 'MTQ', 'MP' => 'MNP', 'MS' => 'MSR', 'MR' => 'MRT', 'IM' => 'IMN', 'UG' => 'UGA', 'TZ' => 'TZA', 'MY' => 'MYS', 'MX' => 'MEX', 'IL' => 'ISR', 'FR' => 'FRA', 'IO' => 'IOT', 'SH' => 'SHN', 'FI' => 'FIN', 'FJ' => 'FJI', 'FK' => 'FLK', 'FM' => 'FSM', 'FO' => 'FRO', 'NI' => 'NIC', 'NL' => 'NLD', 'NO' => 'NOR', 'NA' => 'NAM', 'VU' => 'VUT', 'NC' => 'NCL', 'NE' => 'NER', 'NF' => 'NFK', 'NG' => 'NGA', 'NZ' => 'NZL', 'NP' => 'NPL', 'NR' => 'NRU', 'NU' => 'NIU', 'CK' => 'COK', 'XK' => 'XKX', 'CI' => 'CIV', 'CH' => 'CHE', 'CO' => 'COL', 'CN' => 'CHN', 'CM' => 'CMR', 'CL' => 'CHL', 'CC' => 'CCK', 'CA' => 'CAN', 'CG' => 'COG', 'CF' => 'CAF', 'CD' => 'COD', 'CZ' => 'CZE', 'CY' => 'CYP', 'CX' => 'CXR', 'CR' => 'CRI', 'CW' => 'CUW', 'CV' => 'CPV', 'CU' => 'CUB', 'SZ' => 'SWZ', 'SY' => 'SYR', 'SX' => 'SXM', 'KG' => 'KGZ', 'KE' => 'KEN', 'SS' => 'SSD', 'SR' => 'SUR', 'KI' => 'KIR', 'KH' => 'KHM', 'KN' => 'KNA', 'KM' => 'COM', 'ST' => 'STP', 'SK' => 'SVK', 'KR' => 'KOR', 'SI' => 'SVN', 'KP' => 'PRK', 'KW' => 'KWT', 'SN' => 'SEN', 'SM' => 'SMR', 'SL' => 'SLE', 'SC' => 'SYC', 'KZ' => 'KAZ', 'KY' => 'CYM', 'SG' => 'SGP', 'SE' => 'SWE', 'SD' => 'SDN', 'DO' => 'DOM', 'DM' => 'DMA', 'DJ' => 'DJI', 'DK' => 'DNK', 'VG' => 'VGB', 'DE' => 'DEU', 'YE' => 'YEM', 'DZ' => 'DZA', 'US' => 'USA', 'UY' => 'URY', 'YT' => 'MYT', 'UM' => 'UMI', 'LB' => 'LBN', 'LC' => 'LCA', 'LA' => 'LAO', 'TV' => 'TUV', 'TW' => 'TWN', 'TT' => 'TTO', 'TR' => 'TUR', 'LK' => 'LKA', 'LI' => 'LIE', 'LV' => 'LVA', 'TO' => 'TON', 'LT' => 'LTU', 'LU' => 'LUX', 'LR' => 'LBR', 'LS' => 'LSO', 'TH' => 'THA', 'TF' => 'ATF', 'TG' => 'TGO', 'TD' => 'TCD', 'TC' => 'TCA', 'LY' => 'LBY', 'VA' => 'VAT', 'VC' => 'VCT', 'AE' => 'ARE', 'AD' => 'AND', 'AG' => 'ATG', 'AF' => 'AFG', 'AI' => 'AIA', 'VI' => 'VIR', 'IS' => 'ISL', 'IR' => 'IRN', 'AM' => 'ARM', 'AL' => 'ALB', 'AO' => 'AGO', 'AQ' => 'ATA', 'AS' => 'ASM', 'AR' => 'ARG', 'AU' => 'AUS', 'AT' => 'AUT', 'AW' => 'ABW', 'IN' => 'IND', 'AX' => 'ALA', 'AZ' => 'AZE', 'IE' => 'IRL', 'ID' => 'IDN', 'UA' => 'UKR', 'QA' => 'QAT', 'MZ' => 'MOZ'];

        return $array[$country];
    }
}
if (!function_exists('gnfe_ibge')) {
    function gnfe_ibge($zip) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://open.nfe.io/v1/cities/' . $zip . '/postalcode');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $city = json_decode(json_encode(json_decode($response)));

        if ($city->message || $err) {
            logModuleCall('gofas_nfeio', 'gnfe_ibge', $zip, $city->message, 'ERROR', '');
            return 'ERROR';
        } else {
            return $city->city->code;
        }
    }
}
if (!function_exists('gnfe_queue_nfe')) {
    function gnfe_queue_nfe($invoice_id, $create_all = false) {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoice_id], false);
        $itens = get_product_invoice($invoice_id);

        foreach ($itens as $item) {
            $data = [
                'invoice_id' => $invoice_id,
                'user_id' => $invoice['userid'],
                'nfe_id' => 'waiting',
                'status' => 'Waiting',
                'services_amount' => $item['amount'],
                'environment' => 'waiting',
                'flow_status' => 'waiting',
                'pdf' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => 'waiting',
                'rpsSerialNumber' => 'waiting',
                'service_code' => $item['code_service'],
            ];
            $nfe_for_invoice = gnfe_get_local_nfe($invoice_id, ['status']);

            if (!$nfe_for_invoice['status'] || $create_all) {
                $create_all = true;
                try {
                    $service_code_row = Capsule::table('gofasnfeio')->where('service_code', '=', $item['code_service'])->where('invoice_id', '=', $invoice_id)->where('status','=','waiting')->get(['id', 'services_amount']);

                    if (count($service_code_row) == 1) {
                        $mountDB = floatval($service_code_row[0]->services_amount);
                        $mount_item = floatval($item['amount']);
                        $mount = $mountDB + $mount_item;

                        $update_nfe = Capsule::table('gofasnfeio')->where('id', '=', $service_code_row[0]->id)->update(['services_amount' => $mount]);
                    } else {
                        $save_nfe = Capsule::table('gofasnfeio')->insert($data);
                    }
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        }
        return 'success';
    }
}

if (!function_exists('gnfe_queue_nfe_edit')) {
    function gnfe_queue_nfe_edit($invoice_id, $gofasnfeio_id) {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoice_id], false);
        $itens = get_product_invoice($invoice_id);

        foreach ($itens as $item) {
            $data = [
                'invoice_id' => $invoice_id,
                'user_id' => $invoice['userid'],
                'nfe_id' => 'waiting',
                'status' => 'Waiting',
                'services_amount' => $item['amount'],
                'environment' => 'waiting',
                'flow_status' => 'waiting',
                'pdf' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => 'waiting',
                'rpsSerialNumber' => 'waiting',
                'service_code' => $item['code_service'],
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

if (!function_exists('gnfe_issue_nfe')) {
    function gnfe_issue_nfe($postfields) {
        $webhook_url = gnfe_whmcs_url() . 'modules/addons/gofasnfeio/callback.php';
        foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfe_webhook_id')->get(['value']) as $gnfe_webhook_id_) {
            $gnfe_webhook_id = $gnfe_webhook_id_->value;
        }
        if ($gnfe_webhook_id) {
            $check_webhook = gnfe_check_webhook($gnfe_webhook_id);
            $error = '';
            if ($check_webhook['message']) {
                logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - check_webhook', $gnfe_webhook_id, $check_webhook['message'], 'ERROR', '');
            }
        }
        if ($gnfe_webhook_id and (string) $check_webhook['hooks']['url'] !== (string) $webhook_url) {
            $create_webhook = gnfe_create_webhook($webhook_url);
            if ($create_webhook['message']) {
                logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - gnfe_create_webhook', $webhook_url, $create_webhook['message'], 'ERROR', '');
            }
            if ($create_webhook['hooks']['id']) {
                try {
                    Capsule::table('tblconfiguration')->where('setting', 'gnfe_webhook_id')->update(['value' => $create_webhook['hooks']['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (Exception $e) {
                    logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - Capsule::table(tblconfiguration) update', '', $e->getMessage(), 'ERROR', '');
                }
            }
            $delete_webhook = gnfe_delete_webhook($gnfe_webhook_id);
            if ($delete_webhook['message']) {
                logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - gnfe_delete_webhook', $gnfe_webhook_id, $delete_webhook, 'ERROR', '');
            }
        }
        if (!$gnfe_webhook_id) {
            $create_webhook = gnfe_create_webhook($webhook_url);
            if ($create_webhook['message']) {
                logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - gnfe_create_webhook', $webhook_url, $create_webhook, 'ERROR', '');
            }
            if ($create_webhook['hooks']['id']) {
                try {
                    Capsule::table('tblconfiguration')->insert(['setting' => 'gnfe_webhook_id', 'value' => $create_webhook['hooks']['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (Exception $e) {
                    logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - Capsule::table(tblconfiguration) insert', '', $e->getMessage(), 'ERROR', '');
                }
            }
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - curl_init', $error, $info, '', '');
        logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - CURLOPT_POSTFIELDS', json_encode($postfields), '', '', '');
        if ($err) {
            return (object) ['message' => $err, 'info' => $info];
        } else {
            return json_decode(json_encode(json_decode($response)));
        }
    }
}
if (!function_exists('gnfe_get_nfe')) {
    function gnfe_get_nfe($nf) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}

/**
 * Retorna os dados necessários na função gnfe_put_rps().
 * 
 * @return array
 */
if (!function_exists('gnfe_get_company_info')) {
    function gnfe_get_company_info() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = json_decode(curl_exec($curl), true);
        $response = $response['companies'];
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return array(
                'name' => $response['name'],
                'federalTaxNumber' => $response['federalTaxNumber'],
                'address' => $response['address'],
                'rpsNumber' => $response['rpsNumber']
            );
        } else {
            return array(
                'error' => true,
                'response' =>
                    'Erro: ' . $httpCode . '|'
                    . ' Resposta: ' . $response . '|'
                    . ' Consulte: https://nfe.io/docs/desenvolvedores/rest-api/nota-fiscal-de-servico-v1/#/Companies/Companies_Get'
            );
        }
    }
}

/**
 * Responsável por enviar o último RPS para a NFe.
 * Os parâmetros vem da função gnfe_get_company_info().
 * 
 * @param int $rpsNumber
 * 
 * @return void
 */
if (!function_exists('gnfe_put_rps')) {
    function gnfe_put_rps($company, $rpsNumber) {
        $requestBody = [
            "name"=>$company['name'],
            "federalTaxNumber"=> $company['federalTaxNumber'],
            "address"=> [
                "country"=> $company['address']['country'],
                "postalCode"=> $company['address']['postalCode'],
                "street"=> $company['address']['street'],
                "number"=> $company['address']['number'],
                "additionalInformation"=> $company['address']['additionalInformation'],
                "district"=> $company['address']['district'],
                "city"=> [
                    "code"=> $company['address']['city']['code'],
                    "name"=> $company['address']['city']['name']
                ],
                "state"=> $company['address']['state']
            ],
            "rpsNumber"=> $rpsNumber + 1
        ];
        $requestBody = json_encode($requestBody);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        $response = json_decode(curl_exec($curl), true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200) {
            $response =
                ' Http code: ' . $httpCode . '|' .
                ' Resposta: ' . $response . '|' .
                ' Consulte: https://nfe.io/docs/desenvolvedores/rest-api/nota-fiscal-de-servico-v1/#/Companies/Companies_Put';
            logModuleCall('gofas_nfeio', 'gnfe_put_rps', $requestBody, $response, '', '');
        } else {
            $nfe_rps = gnfe_get_nfes()['rpsNumber'];
            $whmcs_rps = Capsule::table('tbladdonmodules')->where('module','=','gofasnfeio')->where('setting','=','module_version')->get(['value'])[0]->value;

            if ($nfe_rps >= $whmcs_rps) {
                Capsule::table('tbladdonmodules')->where('module', 'gofasnfeio')->where('setting', 'rps_number')->update(['value' => '-1']);
            } else {
                logModuleCall('gofas_nfeio', 'gnfe_put_rps', $requestBody, 'Erro ao tentar passar tratativa de RPS para NFe. ' . $response, '', '');
            }
        }
    }
}

/**
 * Pega o RPS da última nota fiscal emitida.
 * Se o RPS da última nota fiscal emitida for igual a 0,
 * quer dizer que a nota está aguardando algum processo interno da NFe,
 * portanto a função retornará o RPS da penúltima nota fiscal emitida e soma mais um.
 */
if (!function_exists('gnfe_get_rps')) {
    function gnfe_get_rps() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices?pageCount=1&pageIndex=1');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $invoices = curl_exec($curl);
        $invoices = json_decode($invoices, true)['serviceInvoices'];
        curl_close($curl);

        $lastInvoiceRPS = $invoices[0]['rpsNumber'];

        if ($lastInvoiceRPS === 0) {
            $lastInvoiceRPS = $invoices[1]['rpsNumber'] + 1;
        }

        return $lastInvoiceRPS;
    }
}

if (!function_exists('gnfe_test_connection')) {
    function gnfe_test_connection() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $err = curl_error($curl);
        logModuleCall('gofas_nfeio', 'gnfe_issue_nfe - curl_init', $err, $info, '', '');
        curl_close($curl);

        return $info;
    }
}
/**
 * Pega o JSON da última nota fiscal emitida do banco de dados da NFe.
 */
if (!function_exists('gnfe_get_nfes')) {
    function gnfe_get_nfes() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices?pageCount=1&pageIndex=1');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true)['serviceInvoices']['0'];
    }
}

if (!function_exists('gnfe_get_invoice_nfes')) {
    function gnfe_get_invoice_nfes($invoice_id) {
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
    function gnfe_delete_nfe($nf) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
if (!function_exists('gnfe_email_nfe')) {
    function gnfe_email_nfe($nf) {
        if ('on' == gnfe_config('gnfe_email_nfe_config')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/sendemail');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
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
    function gnfe_pdf_nfe($nf) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/pdf');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/pdf', 'Authorization: ' . gnfe_config('api_key')]);
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
    function gnfe_xml_nfe($nf) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/xml');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
if (!function_exists('gnfe_whmcs_url')) {
    function gnfe_whmcs_url() {
        foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsurl')->get(['value']) as $gnfewhmcsurl_) {
            $gnfewhmcsurl = $gnfewhmcsurl_->value;
        }

        return $gnfewhmcsurl;
    }
}

if (!function_exists('gnfe_xml_nfe')) {
    function gnfe_xml_nfe($nf) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/xml',
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
                'Authorization:' . gnfe_config('api_key'),
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}

if (!function_exists('gnfe_whmcs_admin_url')) {
    function gnfe_whmcs_admin_url() {
        foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminurl')->get(['value']) as $gnfewhmcsadminurl_) {
            $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
        }

        return $gnfewhmcsadminurl;
    }
}

if (!function_exists('gnfe_save_nfe')) {
    function gnfe_save_nfe($nfe, $user_id, $invoice_id, $pdf, $created_at, $updated_at) {
        if ($nfe->servicesAmount == -1) {
            return;
        }
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
    function gnfe_update_nfe($nfe, $user_id, $invoice_id, $pdf, $created_at, $updated_at, $id_gofasnfeio = false) {
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
    function gnfe_update_rps($rps_serial_number, $rps_number) {
        // Para que serve este array?
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
    function gnfe_get_local_nfe($invoice_id, $values) {
        foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->orderBy('id', 'desc')->get($values) as $key => $value) {
            $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
        }
        return $nfe_for_invoice['0'];
    }
}
if (!function_exists('gnfe_check_webhook')) {
    function gnfe_check_webhook($id) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/hooks/' . $id);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode(json_encode(json_decode($response)), true);
    }
}
if (!function_exists('gnfe_create_webhook')) {
    function gnfe_create_webhook($url) {
        try {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/hooks');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: aplication/json', 'Authorization: ' . gnfe_config('api_key')]);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['url' => $url, 'contentType' => 'application/json', 'secret' => (string)time(), 'events' => ['issue', 'cancel', 'WaitingCalculateTaxes'], 'status' => 'Active',  ]));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($curl);
            curl_close($curl);
        } catch (Exception $th) {
        }
        return json_decode(json_encode(json_decode($response)), true);
    }
}

if (!function_exists('gnfe_delete_webhook')) {
    function gnfe_delete_webhook($id) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/hooks/' . $id);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
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
    function gnfe_nfe_flowStatus($flowStatus) {
        if ($flowStatus === 'CancelFailed') {
            $status = 'Cancelado por Erro';
        }
        if ($flowStatus === 'IssueFailed') {
            $status = 'Falha ao Emitir';
        }
        if ($flowStatus === 'Issued') {
            $status = 'Emitida';
        }
        if ($flowStatus === 'Cancelled') {
            $status = 'Cancelada';
        }
        if ($flowStatus === 'PullFromCityHall') {
            $status = 'Obtendo da Prefeitura';
        }
        if ($flowStatus === 'WaitingCalculateTaxes') {
            $status = 'Aguardando Calcular Impostos';
        }
        if ($flowStatus === 'WaitingDefineRpsNumber') {
            $status = 'Aguardando Definir Número Rps';
        }
        if ($flowStatus === 'WaitingSend') {
            $status = 'Aguardando Enviar';
        }
        if ($flowStatus === 'WaitingSendCancel') {
            $status = 'Aguardando Cancelar Envio';
        }
        if ($flowStatus === 'WaitingReturn') {
            $status = 'Aguardando Retorno';
        }
        if ($flowStatus === 'WaitingDownload') {
            $status = 'Aguardando Download';
        }

        return $status;
    }
}

if (!function_exists('gnfe_get_company')) {
    function gnfe_get_company() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . gnfe_config('company_id'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

if (!function_exists('gnfe_customer_service_code')) {
    function gnfe_customer_service_code($item_id) {
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

function get_product_invoice($invoice_id) {
    $query = 'SELECT tblinvoiceitems.invoiceid ,tblinvoiceitems.type ,tblinvoiceitems.relid,
    tblinvoiceitems.description,tblinvoiceitems.amount FROM tblinvoiceitems WHERE tblinvoiceitems.invoiceid = :INVOICEID';

    $pdo = Capsule::connection()->getPdo();
    $pdo->beginTransaction();
    $statement = $pdo->prepare($query);
    $statement->execute([':INVOICEID' => $invoice_id]);
    $row = $statement->fetchAll();
    $pdo->commit();

    $tax_check = gnfe_config('tax');
    foreach ($row as $item) {
        $hosting_id = $item['relid'];

        if ($item['type'] == 'Hosting') {
            $query = 'SELECT tblhosting.billingcycle ,tblhosting.id,tblproductcode.code_service ,tblhosting.packageid,tblhosting.id FROM tblhosting
            LEFT JOIN tblproducts ON tblproducts.id = tblhosting.packageid 
            LEFT JOIN tblproductcode ON tblhosting.packageid = tblproductcode.product_id
            WHERE tblhosting.id = :HOSTING';

            if ($tax_check == 'Não') {
                $query .= ' AND tblproducts.tax = 1';
            } else {
                Capsule::table('tblproducts')->update(['tax' => 1]);
            }

            $pdo->beginTransaction();
            $statement = $pdo->prepare($query);
            $statement->execute([':HOSTING' => $hosting_id]);
            $product = $statement->fetchAll();
            $pdo->commit();

            if ($product) {
                $product_array['id_product'] = $product[0]['packageid'];
                $product_array['code_service'] = $product[0]['code_service'];
                $product_array['amount'] = $item['amount'];
                $products_details[] = $product_array;
            }
        } else {
            $product_array['id_product'] = $item['packageid'];
            $product_array['code_service'] = null;
            $product_array['amount'] = $item['amount'];
            $products_details[] = $product_array;
        }
    }

    return $products_details;
}

function dowload_doc_log() {
    $days = 5;

    $configs = [];
    foreach (Capsule::table('tbladdonmodules')->where('module','=','gofasnfeio')->get(['setting', 'value']) as $row) {
        $configs[$row->setting] = $row->value;
    }

    $lastCron = Capsule::table('tbladdonmodules')->where('setting', '=' ,'last_cron')->get(['value'])[0];

    $results = localAPI('WhmcsDetails');
    $v = $results['whmcs']['version'];
    $actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    $text = '-|date' . PHP_EOL . '-|action' . PHP_EOL . '-|request' . PHP_EOL . '-|response' . PHP_EOL . '-|status' . PHP_EOL;
    $text .= 'version =' . $v . PHP_EOL . 'date emission =' . date('Y-m-d H:i:s') . PHP_EOL . 'url =' . $actual_link . PHP_EOL . 'conf_module = ' . json_encode($configs) . PHP_EOL . 'last_cron = ' . $lastCron->value . PHP_EOL;

    $dataAtual = toMySQLDate(getTodaysDate(false)) . ' 23:59:59';
    $dataAnterior = date('Y-m-d',mktime (0, 0, 0, date('m'), date('d') - $days,  date('Y'))) . ' 23:59:59';

    foreach (Capsule::table('tblmodulelog')->where('module','=','gofas_nfeio')->orderBy('date')->whereBetween('date', [$dataAnterior, $dataAtual])->get(['date', 'action', 'request', 'response', 'arrdata']) as $log) {
        $text .= PHP_EOL . '==========================================================================================================================================' . PHP_EOL;
        $text .= '-|date = ' . $log->date . PHP_EOL . '-|action = ' . $log->action . PHP_EOL . '-|request = ' . ($log->request) . PHP_EOL . '-|response = ' . ($log->response) . PHP_EOL . '-|status = ' . ($log->arrdata);
    }
    $text .= PHP_EOL . '====================================================================FIM DO ARQUIVO======================================================================' . PHP_EOL;

    header('Content-type: text/plain');
    header('Content-Disposition: attachment; filename="default-filename.txt"');
    print $text;
    exit();
}

function update_status_nfe($invoice_id,$status) {
    try {
        $return = Capsule::table('gofasnfeio')->where('invoice_id','=',$invoice_id)->update(['status' => $status]);
        return $return;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function verifyIssueFromUser($vars) {
    $results = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);
    $results = localAPI('GetClientsDetails', ['clientid' => $results['userid']], '');
    foreach ($results['customfields'] as $key => $value) {
        $issueNfeUser = $value['value'];
    }
    return $issueNfeUser;
}