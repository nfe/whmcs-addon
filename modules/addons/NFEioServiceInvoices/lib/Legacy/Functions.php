<?php

namespace NFEioServiceInvoices\Legacy;

use NFEioServiceInvoices\Helpers\Timestamp;
use WHMCS\Database\Capsule;
use NFEioServiceInvoices\Addon;
use NFEioServiceInvoices\Helpers\Validations;
use WHMCSExpert\Addon\Storage;

class Functions
{
    function gnfe_config($set = false)
    {

        $_storageKey = Addon::I()->configuration()->storageKey;

        if ($set === false) {
            $setting = [];

            foreach (Capsule::table('tbladdonmodules')->where('module', '=', $_storageKey)->get(['setting', 'value']) as $settings) {
                $setting[$settings->setting] = $settings->value;
            }

            return $setting;
        } else {
            return Capsule::table('tbladdonmodules')
                ->where('module', '=', Addon::I()->configuration()->storageKey)
                ->where('setting', '=', $set)
                ->get(['value'])[0]->value;
        }
    }

    public function gnfe_customer($user_id, $client): array
    {
        //Determine custom fields id
        $cpfCustomFieldId = $this->gnfe_config('cpf_camp');
        $cnpjCustomFieldId = $this->gnfe_config('cnpj_camp');
        $inscMunicipalCustomFieldId = $this->gnfe_config('insc_municipal');

//        $insc_customfield_value = 'NF';
        $inscMunicipalCustomFieldValue = false;
        // inicia array de retorno
        $result = [];

        /**
         * Este bloco de código verifica se os IDs dos campos personalizados para CPF e CNPJ estão definidos.
         * Se ambos os IDs dos campos personalizados CPF e CNPJ não estiverem definidos (ou seja, eles são 0),
         * ele define uma bandeira de erro e retorna uma mensagem de erro.
         *
         * @return array Se ocorrer um erro, retorna um array associativo com 'error' definido como true e 'message'
         * contendo a mensagem de erro.
         */
        if ($cpfCustomFieldId == 0 && $cnpjCustomFieldId == 0) {
            $error = true;
            $message = 'Campos para CPF e CNPJ não configurados.';
            $result['error'] = $error;
            $result['message'] = $message;
            return $result;
        }

        // insc_municipal
        if ($inscMunicipalCustomFieldId != 0) {
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $inscMunicipalCustomFieldId)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                $inscMunicipalCustomFieldValue = $customfieldvalue->value;
            }
        }
        // cpf
        if ($cpfCustomFieldId != 0) {
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $cpfCustomFieldId)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                $cpfCustomFieldValue = $customfieldvalue->value;
            }
        }
        //cnpj
        if ($cnpjCustomFieldId != 0) {
            foreach (Capsule::table('tblcustomfieldsvalues')->where('fieldid', '=', $cnpjCustomFieldId)->where('relid', '=', $user_id)->get(['value']) as $customfieldvalue) {
                $cnpjCustomFieldValue = $customfieldvalue->value;
            }
        }

        $cpfIsValid = Validations::validateCPF($cpfCustomFieldValue);
        $cnpjIsValid = Validations::validateCNPJ($cnpjCustomFieldValue);

        if (!$cpfIsValid && !$cnpjIsValid) {
            $error = true;
            $message = 'Documento cadastrado não é um CPF ou CNPJ válido.';
            $result['error'] = $error;
            $result['message'] = $message;
            return $result;
        }

        $cpf = preg_replace('/[^0-9]/', '', $cpfCustomFieldValue);
        $cnpj = preg_replace('/[^0-9]/', '', $cnpjCustomFieldValue);
        $inscMunicipal = $inscMunicipalCustomFieldValue && trim($inscMunicipalCustomFieldValue);

        // adiciona a inscricao municipal ao retorno apenas se existir um valor registrado e documento for CNPJ
        if ($inscMunicipal && $cnpjIsValid) {
            $result['insc_municipal'] = $inscMunicipalCustomFieldValue;
        }

        if ($cpfIsValid) {
            $result['success'] = true;
            $result['doc_type'] = 1;
            $result['document'] = $cpf;
            $result['name'] = $client->firstname . ' ' . $client->lastname;
        } elseif ($cnpjIsValid) {
            $result['success'] = true;
            $result['doc_type'] = 2;
            $result['document'] = $cnpj;
            $result['name'] = $client->companyname ? $client->companyname : $client->firstname . ' ' . $client->lastname;
        } else {
            $result['error'] = true;
            $result['message'] = 'Documento cadastrado não é um CPF ou CNPJ válido.';
        }

        return $result;
    }

    function gnfe_customfields()
    {
        //Determine custom fields id
        $customfields = [];
        foreach (Capsule::table('tblcustomfields')->where('type', '=', 'client')->get(['fieldname', 'id']) as $customfield) {
            $customfields[] = $customfield;
            $customfield_id = $customfield->id;
            $customfield_name = ' ' . strtolower($customfield->fieldname);
        }

        return $customfields;
    }

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

    function gnfe_country_code($country)
    {
        $array = ['BD' => 'BGD', 'BE' => 'BEL', 'BF' => 'BFA', 'BG' => 'BGR', 'BA' => 'BIH', 'BB' => 'BRB', 'WF' => 'WLF', 'BL' => 'BLM', 'BM' => 'BMU', 'BN' => 'BRN', 'BO' => 'BOL', 'BH' => 'BHR', 'BI' => 'BDI', 'BJ' => 'BEN', 'BT' => 'BTN', 'JM' => 'JAM', 'BV' => 'BVT', 'BW' => 'BWA', 'WS' => 'WSM', 'BQ' => 'BES', 'BR' => 'BRA', 'BS' => 'BHS', 'JE' => 'JEY', 'BY' => 'BLR', 'BZ' => 'BLZ', 'RU' => 'RUS', 'RW' => 'RWA', 'RS' => 'SRB', 'TL' => 'TLS', 'RE' => 'REU', 'TM' => 'TKM', 'TJ' => 'TJK', 'RO' => 'ROU', 'TK' => 'TKL', 'GW' => 'GNB', 'GU' => 'GUM', 'GT' => 'GTM', 'GS' => 'SGS', 'GR' => 'GRC', 'GQ' => 'GNQ', 'GP' => 'GLP', 'JP' => 'JPN', 'GY' => 'GUY', 'GG' => 'GGY', 'GF' => 'GUF', 'GE' => 'GEO', 'GD' => 'GRD', 'GB' => 'GBR', 'GA' => 'GAB', 'SV' => 'SLV', 'GN' => 'GIN', 'GM' => 'GMB', 'GL' => 'GRL', 'GI' => 'GIB', 'GH' => 'GHA', 'OM' => 'OMN', 'TN' => 'TUN', 'JO' => 'JOR', 'HR' => 'HRV', 'HT' => 'HTI', 'HU' => 'HUN', 'HK' => 'HKG', 'HN' => 'HND', 'HM' => 'HMD', 'VE' => 'VEN', 'PR' => 'PRI', 'PS' => 'PSE', 'PW' => 'PLW', 'PT' => 'PRT', 'SJ' => 'SJM', 'PY' => 'PRY', 'IQ' => 'IRQ', 'PA' => 'PAN', 'PF' => 'PYF', 'PG' => 'PNG', 'PE' => 'PER', 'PK' => 'PAK', 'PH' => 'PHL', 'PN' => 'PCN', 'PL' => 'POL', 'PM' => 'SPM', 'ZM' => 'ZMB', 'EH' => 'ESH', 'EE' => 'EST', 'EG' => 'EGY', 'ZA' => 'ZAF', 'EC' => 'ECU', 'IT' => 'ITA', 'VN' => 'VNM', 'SB' => 'SLB', 'ET' => 'ETH', 'SO' => 'SOM', 'ZW' => 'ZWE', 'SA' => 'SAU', 'ES' => 'ESP', 'ER' => 'ERI', 'ME' => 'MNE', 'MD' => 'MDA', 'MG' => 'MDG', 'MF' => 'MAF', 'MA' => 'MAR', 'MC' => 'MCO', 'UZ' => 'UZB', 'MM' => 'MMR', 'ML' => 'MLI', 'MO' => 'MAC', 'MN' => 'MNG', 'MH' => 'MHL', 'MK' => 'MKD', 'MU' => 'MUS', 'MT' => 'MLT', 'MW' => 'MWI', 'MV' => 'MDV', 'MQ' => 'MTQ', 'MP' => 'MNP', 'MS' => 'MSR', 'MR' => 'MRT', 'IM' => 'IMN', 'UG' => 'UGA', 'TZ' => 'TZA', 'MY' => 'MYS', 'MX' => 'MEX', 'IL' => 'ISR', 'FR' => 'FRA', 'IO' => 'IOT', 'SH' => 'SHN', 'FI' => 'FIN', 'FJ' => 'FJI', 'FK' => 'FLK', 'FM' => 'FSM', 'FO' => 'FRO', 'NI' => 'NIC', 'NL' => 'NLD', 'NO' => 'NOR', 'NA' => 'NAM', 'VU' => 'VUT', 'NC' => 'NCL', 'NE' => 'NER', 'NF' => 'NFK', 'NG' => 'NGA', 'NZ' => 'NZL', 'NP' => 'NPL', 'NR' => 'NRU', 'NU' => 'NIU', 'CK' => 'COK', 'XK' => 'XKX', 'CI' => 'CIV', 'CH' => 'CHE', 'CO' => 'COL', 'CN' => 'CHN', 'CM' => 'CMR', 'CL' => 'CHL', 'CC' => 'CCK', 'CA' => 'CAN', 'CG' => 'COG', 'CF' => 'CAF', 'CD' => 'COD', 'CZ' => 'CZE', 'CY' => 'CYP', 'CX' => 'CXR', 'CR' => 'CRI', 'CW' => 'CUW', 'CV' => 'CPV', 'CU' => 'CUB', 'SZ' => 'SWZ', 'SY' => 'SYR', 'SX' => 'SXM', 'KG' => 'KGZ', 'KE' => 'KEN', 'SS' => 'SSD', 'SR' => 'SUR', 'KI' => 'KIR', 'KH' => 'KHM', 'KN' => 'KNA', 'KM' => 'COM', 'ST' => 'STP', 'SK' => 'SVK', 'KR' => 'KOR', 'SI' => 'SVN', 'KP' => 'PRK', 'KW' => 'KWT', 'SN' => 'SEN', 'SM' => 'SMR', 'SL' => 'SLE', 'SC' => 'SYC', 'KZ' => 'KAZ', 'KY' => 'CYM', 'SG' => 'SGP', 'SE' => 'SWE', 'SD' => 'SDN', 'DO' => 'DOM', 'DM' => 'DMA', 'DJ' => 'DJI', 'DK' => 'DNK', 'VG' => 'VGB', 'DE' => 'DEU', 'YE' => 'YEM', 'DZ' => 'DZA', 'US' => 'USA', 'UY' => 'URY', 'YT' => 'MYT', 'UM' => 'UMI', 'LB' => 'LBN', 'LC' => 'LCA', 'LA' => 'LAO', 'TV' => 'TUV', 'TW' => 'TWN', 'TT' => 'TTO', 'TR' => 'TUR', 'LK' => 'LKA', 'LI' => 'LIE', 'LV' => 'LVA', 'TO' => 'TON', 'LT' => 'LTU', 'LU' => 'LUX', 'LR' => 'LBR', 'LS' => 'LSO', 'TH' => 'THA', 'TF' => 'ATF', 'TG' => 'TGO', 'TD' => 'TCD', 'TC' => 'TCA', 'LY' => 'LBY', 'VA' => 'VAT', 'VC' => 'VCT', 'AE' => 'ARE', 'AD' => 'AND', 'AG' => 'ATG', 'AF' => 'AFG', 'AI' => 'AIA', 'VI' => 'VIR', 'IS' => 'ISL', 'IR' => 'IRN', 'AM' => 'ARM', 'AL' => 'ALB', 'AO' => 'AGO', 'AQ' => 'ATA', 'AS' => 'ASM', 'AR' => 'ARG', 'AU' => 'AUS', 'AT' => 'AUT', 'AW' => 'ABW', 'IN' => 'IND', 'AX' => 'ALA', 'AZ' => 'AZE', 'IE' => 'IRL', 'ID' => 'IDN', 'UA' => 'UKR', 'QA' => 'QAT', 'MZ' => 'MOZ'];

        return $array[$country];
    }

    function gnfe_ibge($zip)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://open.nfe.io/v1/cities/' . $zip . '/postalcode');
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        $data = json_decode($response);
        $result = [];

        // se curl apresentar erro retorna imediatamente
        if ($error) {
            $result['error'] = true;
            $result['message'] = $error;
            return $result;
        }

        if ($data->city->code) {
            $result['code'] = $data->city->code;
            $result['success'] = true;
        } else {
            $result['error'] = true;
            $result['message'] = $data->message;
            logModuleCall('nfeio_serviceinvoices', 'ibge_error', $zip, array('response' => $response, 'error' => $error));
        }

        return $result;
    }

    function gnfe_queue_nfe($invoice_id, $create_all = false)
    {
        $invoice = \WHMCS\Billing\Invoice::find($invoice_id);
        $itens = $this->get_product_invoice($invoice_id);
        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $_tableName = $serviceInvoicesRepo->tableName();

        foreach ($itens as $item) {
            $data = [
                'invoice_id' => $invoice_id,
                'user_id' => $invoice->userid,
                'nfe_id' => 'waiting',
                'status' => 'Waiting',
                'services_amount' => $item['amount'],
                'environment' => 'waiting',
                'flow_status' => 'waiting',
                'pdf' => 'waiting',
                'rpsSerialNumber' => 'waiting',
                'service_code' => $item['code_service'],
            ];
            $nfe_for_invoice = $this->gnfe_get_local_nfe($invoice_id, ['status']);

            if (!$nfe_for_invoice['status'] || $create_all) {
                $create_all = true;
                try {
                    $service_code_row = Capsule::table($_tableName)->where('service_code', '=', $item['code_service'])->where('invoice_id', '=', $invoice_id)->where('status', '=', 'waiting')->get(['id', 'services_amount']);

                    if (count($service_code_row) == 1) {
                        $mountDB = floatval($service_code_row[0]->services_amount);
                        $mount_item = floatval($item['amount']);
                        $mount = $mountDB + $mount_item;

                        Capsule::table($_tableName)->where('id', '=', $service_code_row[0]->id)->update(['services_amount' => $mount]);
                    } else {
                        Capsule::table($_tableName)->insert($data);
                    }
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        }
        return 'success';
    }

    function gnfe_issue_nfe($postfields, $companyId)
    {
        $webhook_url = Addon::getCallBackPath();
        $webhook_id = $this->gnfe_config('webhook_id');
        $_storageKey = Addon::I()->configuration()->storageKey;
        $storage = new Storage($_storageKey);
        $nfeio = new \NFEioServiceInvoices\NFEio\Nfe();


        // Verifica se o webhook existe e é válido, senão cria
        $webhook = $webhook_id ? $nfeio->getWebhook($webhook_id) : null;
        if (!$webhook || $webhook->hooks->url !== $webhook_url) {
            $newHook = $nfeio->createWebhook($webhook_url);
            if (!$newHook) {
                return (object)['message' => 'Erro ao criar novo webhook'];
            }
            $storage->set('webhook_id', $newHook->hooks->id);
            $storage->set('webhook_secret', $newHook->hooks->secret);
        }


        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . $companyId . '/serviceinvoices');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . $this->gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postfields));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);


        if ($error) {
            logModuleCall('nfeio_serviceinvoices', 'nf_issue_curl_error', $postfields, ['error' => $error, 'response' => $response, 'info' => $info], '', '');
            return (object)['message' => $error, 'info' => $info];
        } else {
            logModuleCall('nfeio_serviceinvoices', 'nf_issue_curl_success', $postfields, $response, json_decode($response, true), '');
            return json_decode(json_encode(json_decode($response)));
        }
    }


    function gnfe_delete_nfe($nf, $companyId)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . $companyId . '/serviceinvoices/' . $nf);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . $this->gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $response = curl_exec($curl);
        curl_close($curl);

        logModuleCall('nfeio_serviceinvoices', 'delete_nfe', $nf, $response, json_decode($response, true), '');

        return json_decode($response);
    }

    function gnfe_email_nfe($nf)
    {
        if ('on' == $this->gnfe_config('gnfe_email_nfe_config')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . $this->gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/sendemail');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Accept: application/json', 'Authorization: ' . $this->gnfe_config('api_key')]);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            $response = curl_exec($curl);
            curl_close($curl);

            logModuleCall('nfeio_serviceinvoices', 'email_nfe', $nf, $response, json_decode($response, true), '');

            return json_decode($response);
        }
    }

    function gnfe_pdf_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . $this->gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/pdf');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: text/json', 'Authorization: ' . $this->gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        $result = curl_exec($curl);
        curl_close($curl);
        header('Content-type: application/pdf');
        header("Content-Disposition: attachment; filename=" . $nf . ".pdf");

        echo $result;
    }

    function gnfe_xml_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/' . $this->gnfe_config('company_id') . '/serviceinvoices/' . $nf . '/xml');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json', 'Authorization: ' . $this->gnfe_config('api_key')]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        $result = curl_exec($curl);
        curl_close($curl);
        //header('Content-type: application/xml');
        //header("Content-Disposition: attachment; filename=".$nf.".xml");
        //$file = file_put_contents("{$nf}.xml", $result);

        return $result;
    }

    function gnfe_update_nfe($nfe, $user_id, $invoice_id, $pdf, $id_gofasnfeio = false)
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
            'rpsSerialNumber' => $nfe->rpsSerialNumber,
            'rpsNumber' => $nfe->rpsNumber,
        ];

        try {
            $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
            $_tableName = $serviceInvoicesRepo->tableName();

            if (!$id_gofasnfeio) {
                $id = $invoice_id;
                $camp = 'nfe_id';
            } else {
                $id = $id_gofasnfeio;
                $camp = 'id';
            }
            $save_nfe = Capsule::table($_tableName)->where($camp, '=', $id)->update($data);

            logModuleCall('nfeio_serviceinvoices', 'update_nfe', $data, $save_nfe, '', '');

            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Returns the data of a invoice from the local WHMCS database.
     *
     * @return string
     * @var    $values
     * @var    $invoice_id
     */
    function gnfe_get_local_nfe($invoice_id, $values)
    {

        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $_tableName = $serviceInvoicesRepo->tableName();

        foreach (Capsule::table($_tableName)->where('invoice_id', '=', $invoice_id)->orderBy('id', 'desc')->get($values) as $key => $value) {
            $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
        }
        return $nfe_for_invoice['0'];
    }


    /**
     * @gnfe_nfe_flowStatus string
     * Possible values:
     * CancelFailed, IssueFailed, Issued, Cancelled, PullFromCityHall, WaitingCalculateTaxes,
     * WaitingDefineRpsNumber, WaitingSend, WaitingSendCancel, WaitingReturn, WaitingDownload
     * @param               $flowStatus
     * @return              string
     */
    function gnfe_nfe_flowStatus($flowStatus)
    {
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

    function get_product_invoice($invoice_id)
    {
        $query = 'SELECT tblinvoiceitems.invoiceid ,tblinvoiceitems.type ,tblinvoiceitems.relid,
    tblinvoiceitems.description,tblinvoiceitems.amount FROM tblinvoiceitems WHERE tblinvoiceitems.invoiceid = :INVOICEID';

        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute([':INVOICEID' => $invoice_id]);
        $row = $statement->fetchAll();
        $pdo->commit();

        $tax_check = $this->gnfe_config('tax');
        foreach ($row as $item) {
            $hosting_id = $item['relid'];

            if ($item['type'] == 'Hosting') {
                $query = 'SELECT tblhosting.billingcycle ,tblhosting.id,tblproductcode.code_service ,tblhosting.packageid,tblhosting.id FROM tblhosting
            LEFT JOIN tblproducts ON tblproducts.id = tblhosting.packageid
            LEFT JOIN tblproductcode ON tblhosting.packageid = tblproductcode.product_id
            WHERE tblhosting.id = :HOSTING';

                if (!$tax_check) {
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

    function update_status_nfe($invoice_id, $status)
    {

        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $_tableName = $serviceInvoicesRepo->tableName();

        try {
            $return = Capsule::table($_tableName)->where('invoice_id', '=', $invoice_id)->update([
                'status' => $status,
                'updated_at' => Timestamp::currentTimestamp(),
            ]);
            return $return;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * @return string
     * @var    string $invoiceId vem do arquivo hooks.php.
     */
    function gnfe_get_client_issue_invoice_cond_from_invoice_id($invoiceId)
    {

        $clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $_table = $clientConfigurationRepo->tableName();


        $clientId = \WHMCS\Billing\Invoice::find($invoiceId)->client->id;

        $clientCond = Capsule::table($_table)
            ->where('client_id', '=', $clientId)
            ->where('key', '=', 'issue_nfe_cond')
            ->get(['value'])[0]->value;
        $clientCond = strtolower($clientCond);

        if ($clientCond !== null && $clientCond !== 'seguir configuração do módulo nfe.io') {
            return $clientCond;
        }

        return 'seguir configuração do módulo nfe.io';
    }

    /**
     * Returns a <select> HTML which is used only by the AdminClientProfileTabFields hook
     * in the file hooks.php.
     *
     * @return string
     * @var    int
     */
    function gnfe_show_issue_invoice_conds($clientId)
    {

        $clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $tableServiceInvoices = $serviceInvoicesRepo->tableName();
        $tableClientConfiguration = $clientConfigurationRepo->tableName();
        $_storageKey = Addon::I()->configuration()->storageKey;

        $conditions = Capsule::table('tbladdonmodules')->where('module', '=', $_storageKey)->where('setting', '=', 'issue_note_conditions')->get(['value'])[0]->value;
        $conditions = explode(',', $conditions);

        $previousClientCond = Capsule::table($tableClientConfiguration)->where('client_id', '=', $clientId)->first()->value;

        $select = '<select name="issue_note_cond" class="form-control select-inline">';

        // Sets the previous issue condition in the first index of array $conditions.
        // in order to the previous condition be showed in the client prifile.
        if ($previousClientCond != null) {
            $previousCondKey = array_search($previousClientCond, $conditions);
            unset($conditions[$previousCondKey]);
            $select .= '<option value="' . $previousClientCond . '">' . $previousClientCond . '</option>';
        } else {
            $defaultCond = 'Seguir configuração do módulo NFE.io';
            $defaultCondKey = array_search($defaultCond, $conditions);
            unset($conditions[$defaultCondKey]);
            $select .= '<option value="Seguir configuração do módulo NFE.io">Seguir configuração do módulo NFE.io</option>';
        }

        foreach ($conditions as $cond) {
            $select .= '<option value="' . $cond . '">' . $cond . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

    /**
     * Insert the clientId and his condition of sending invoice in the table mod_nfeio_custom_configs.
     *
     * @var $client int
     * @var $invoiceCond string
     */
    function gnfe_save_client_issue_invoice_cond($clientId, $newCond)
    {

        $clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $_tableName = $clientConfigurationRepo->tableName();

        // pega o primeiro registro disponível em mod_nfeio_custom_configs para o ID do cliente
        $clientCustomConfig = Capsule::table($_tableName)->where('client_id', $clientId)->first();

        // caso cliente já possua uma configuração personalizada, atualiza baseado no ID do registro já encontrado
        // na tabela mod_nfeio_custom_configs
        if ($clientCustomConfig) {
            Capsule::table($_tableName)
                ->where('id', $clientCustomConfig->id)
                ->update(['value' => $newCond]);
        } else {
            // senão insere um novo
            Capsule::table($_tableName)->insert(
                [
                    'key' => 'issue_nfe_cond',
                    'client_id' => $clientId,
                    'value' => $newCond
                ]
            );
        }
    }

    /**
     * Inserts the conditions of sending invoices in the database.
     */
    function gnfe_insert_issue_nfe_cond_in_database()
    {

        $_storageKey = Addon::I()->configuration()->storageKey;
        $conditions = 'Quando a fatura é gerada,Quando a fatura é paga,Seguir configuração do módulo NFE.io';

        $previousConditions = Capsule::table('tbladdonmodules')
            ->where('module', '=', $_storageKey)
            ->where('setting', '=', 'issue_note_conditions')
            ->get(['value'])[0]->value;

        if (count($previousConditions) <= 0 || $previousConditions != $conditions) {
            Capsule::table('tbladdonmodules')
                ->where('module', '=', $_storageKey)
                ->where('setting', '=', 'issue_note_conditions')
                ->update(['value' => $conditions]);
        }

        if (count($previousConditions) <= 0) {
            Capsule::table('tbladdonmodules')->insert(['module' => $_storageKey, 'setting' => 'issue_note_conditions', 'value' => $conditions]);
        }
    }


    function createRequestFromAPI(
        $service_code,
        $desc,
        $services_amount,
        $document,
        $insc_municipal = '',
        $name,
        $email,
        $countrycode,
        $postcode,
        $street,
        $number,
        $address2,
        $code,
        $city,
        $state
    )
    {
        $postfields = [
            'cityServiceCode' => $service_code,
            'description' => $desc,
            'servicesAmount' => $services_amount,
            'borrower' => [
                'federalTaxNumber' => $document,
                'municipalTaxNumber' => $insc_municipal,
                'name' => $name,
                'email' => $email,
                'address' => [
                    'country' => $this->gnfe_country_code($countrycode),
                    'postalCode' => preg_replace('/[^0-9]/', '', $postcode),
                    'street' => $street,
                    'number' => $number,
                    'additionalInformation' => '',
                    'district' => $address2,
                    'city' => [
                        'code' => $code,
                        'name' => $city,
                    ],
                    'state' => $state
                ]
            ]
        ];
        strlen($insc_municipal) == 0 ? '' : $postfields['borrower']['municipalTaxNumber'] = $insc_municipal;
        return $postfields;
    }
}
