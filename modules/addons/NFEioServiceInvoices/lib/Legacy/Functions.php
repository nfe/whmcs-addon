<?php

namespace NFEioServiceInvoices\Legacy;

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

}
