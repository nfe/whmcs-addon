<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/update.php';

if (!function_exists('gofasnfeio_config')) {
    if (!function_exists('gnfe_customfields_dropdow')) {
        function gnfe_customfields_dropdow() {
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

            //  return $dropFieldArray;
        }
    }

    function gnfe_verify_module_updates() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.github.com/repos/nfe/whmcs-addon/releases');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json', 'User-Agent: whmcs_nfeio']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if ($info['http_code'] == 200) {
            return json_decode($response)[0]->tag_name;
        } else {
            return '';
        }
    }

    function gofasnfeio_config() {
        if ($_GET['doc_log']) {
            dowload_doc_log();
        }

        // --------------------------------------------------------------------------------------------

        // Versão do módulo instalado.
        $module_version = '1.4.0';
        $module_version_int = (int) preg_replace('/[^0-9]/', '', $module_version);

        // Versão do módulo que consta no banco de dados.
        // Ou seja, se module_version e previous_version não
        // forem iguais, quer dizer que houve uma atualização do módulo.
        $previous_version = Capsule::table('tbladdonmodules')->where('module','=','gofasnfeio')->where('setting','=','module_version')->get(['value'])[0]->value;
        $previous_version_int = (int) preg_replace('/[^0-9]/', '', $previous_version);

        

        // --------------------------------------------------------------------------------------------

        // Verifica se há atualizações disponíveis.
        $available_update_ = gnfe_verify_module_updates();
        $available_version_int = (int) preg_replace('/[^0-9]/', '', str_replace('v','',$available_update_));

        if ($available_version_int <= $module_version_int) { // $available_version_int <= $module_version_int
            $available_update_message = '<p style="font-size: 14px;color:green;"><i class="fas fa-check-square"></i> Você está executando a versão mais recente do módulo.</p>';
        } else {
            $available_update_message = '<p style="font-size: 14px;color:red;"><i class="fas fa-exclamation-triangle"></i> Nova versão disponível no <a style="color:#CC0000;text-decoration:underline;" href="https://github.com/nfe/whmcs-addon/releases" target="_blank">Github</a></p>';
        }

        // --------------------------------------------------------------------------------------------
        // 1º Verificar se o RPS no WHMCS tá diferente de -1
            // Verificar se a row do campo rps_number_change_nfe está no banco.

        // 2º Verificar se o campo de configuração tá igual a true
            // Se tiver, atualizar a RPS de acordo com o número do WHMCS

        // 3º Verificar se foi concluído com sucesso

        // 4º Verificar se o RPS na NFe tá maior ou igual ao WHMCS
            // Se tiver alterar o campo do WHMCS para -1

        if ($module_version_int < 150) {
            $gnfe_get_nfes = gnfe_get_nfes();
            $rps_number = $gnfe_get_nfes['rpsNumber'];

            // Verifica se a configuração rps_number existe no banco de dados.
            if (Capsule::table('tbladdonmodules')->where('setting','=','rps_number')->count() == 0) {
                echo 'RPS FALTANDO FOI INSERIDO NO DB';
                Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'rps_number', 'value' => $rps_number]);
            }

            $whmcsRPS = gnfe_config()['rps_number'];

            if ($whmcsRPS != -1)  {
                $companyData = gnfe_get_company_info();

                if (isset($companyData['error'])) {
                    logModuleCall('gofas_nfeio', 'gnfe_get_company_info', '', $companyData['response'], '', '');
                    echo 'ERRO AO PEGAR OS DADOS DA EMPRESA';
                } else {
                    // Transfere a tratativa do RPS para a NFe.
                    gnfe_put_rps($companyData, $whmcsRPS);
                }
            }
        }

        /**
         * Versão do Davi, com o campo rps_number_change_nfe nas configurações do módulo.
         * $whmcsRPS = gnfe_config()['rps_number'];
         * $rpsNumberChangeNfe = Capsule::table('tbladdonmodules')->where('module','=','gofasnfeio')->where('setting','=','rps_number_change_nfe')->get(['value'])[0]->value;
         * if ($whmcsRPS !== -1 && $rpsNumberChangeNfe)  {
         *     $companyData = gnfe_get_company_info();
         *     if (isset($companyData['error'])) {
         *         logModuleCall('gofas_nfeio', 'gnfe_get_company_info', '', $companyData['response'], '', '');
         *         echo 'ERRO AO PEGAR OS DADOS DA EMPRESA';
         *     } else {
         *         gnfe_put_rps($companyData, $whmcsRPS); // Atualiza a RPS.
         *         Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'rps_number', 'value' => -1]);
         *         echo 'RPS ATUALIZADO.';
         *     }
         * }
        */
        // --------------------------------------------------------------------------------------------

        /// REMOVER VERIFICAÇÃO APÓS VERSÃO 2.0
        $verificarEmail = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'gnfe_email_nfe_config')->count();
        if (empty($verificarEmail)) {
            // echo "vazio";
            try {
                Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'gnfe_email_nfe_config', 'value' => 'on']);
            } catch (\Exception $e) {
                $e->getMessage();
            }
        }////// FIM VERIFICAÇÃO

        $actual_link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        if (stripos($actual_link, '/configaddonmods.php')) {
            $whmcs_url__ = str_replace('\\', '/', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . substr(getcwd(), strlen($_SERVER['DOCUMENT_ROOT'])));
            $admin_url = $whmcs_url__ . '/';
            $vtokens = explode('/', $actual_link);
            $whmcs_admin_path = '/' . $vtokens[sizeof($vtokens) - 2] . '/';
            $whmcs_url = str_replace($whmcs_admin_path, '', $admin_url) . '/';
            foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsurl')->get(['value', 'created_at']) as $gnfewhmcsurl_) {
                $gnfewhmcsurl = $gnfewhmcsurl_->value;
                $gnfewhmcsurl_created_at = $gnfewhmcsurl_->created_at;
            }
            foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfe_email_nfe')->get(['value']) as $gnfe_email_nfe_) {
                $gnfe_email_nfe = $gnfewhmcsurl_->value;
            }

            foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminurl')->get(['value', 'created_at']) as $gnfewhmcsadminurl_) {
                $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
                $gnfewhmcsadminurl_created_at = $gnfewhmcsurl_->created_at;
            }
            foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminpath')->get(['value', 'created_at']) as $gnfewhmcsadminpath_) {
                $gnfewhmcsadminpath = $gnfewhmcsadminpath_->value;
                $gnfewhmcsadminpath_created_at = $gnfewhmcsurl_->created_at;
            }
            if (!$gnfe_email_nfe) {
                try {
                    Capsule::table('tblconfiguration')->insert(['setting' => 'gnfe_email_nfe', 'value' => 'Active', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }
            }
            if (!$gnfewhmcsurl) {
                // Set config
                try {
                    Capsule::table('tblconfiguration')->insert(['setting' => 'gnfewhmcsurl', 'value' => $whmcs_url, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }

                try {
                    Capsule::table('tblconfiguration')->insert(['setting' => 'gnfewhmcsadminurl', 'value' => $admin_url, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }

                try {
                    Capsule::table('tblconfiguration')->insert(['setting' => 'gnfewhmcsadminpath', 'value' => $whmcs_admin_path, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }
            }
            // Update Settings
            if ($gnfewhmcsurl and ($whmcs_url !== $gnfewhmcsurl)) {
                try {
                    Capsule::table('tblconfiguration')->where('setting', 'gnfewhmcsurl')->update(['value' => $whmcs_url, 'created_at' => $gnfewhmcsurl_created_at, 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }
            }
            if ($gnfewhmcsadminurl and ($admin_url !== $gnfewhmcsadminurl)) {
                try {
                    Capsule::table('tblconfiguration')->where('setting', 'gnfewhmcsadminurl')->update(['value' => $admin_url, 'created_at' => $gnfewhmcsadminurl_created_at, 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }
            }
            if ($gnfewhmcsadminpath and ($whmcs_admin_path !== $gnfewhmcsadminpath)) {
                try {
                    Capsule::table('tblconfiguration')->where('setting', 'gnfewhmcsadminpath')->update(['value' => $whmcs_admin_path, 'created_at' => $gnfewhmcsadminpath_created_at, 'updated_at' => date('Y-m-d H:i:s')]);
                } catch (\Exception $e) {
                    $e->getMessage();
                }
            }
        }

        //create tables
        gnfe_verifyInstall();
        create_table_product_code();

        if (version_compare($previous_version,'1.2.7','<')) {
            set_code_service_camp_gofasnfeio();
            set_custom_field_ini_date();
        }
        
        $intro = ['intro' => [
            'FriendlyName' => '',
            'Description' => '<h4 style="padding-top: 5px;">Módulo Nota Fiscal NFE.io para WHMCS v' . $module_version . '</h4>
					' . $available_update_message . $update_denied,
        ]];

        $api_key = ['api_key' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Description' => '<a href="https://app.nfe.io/account/apikeys" style="text-decoration:underline;" target="_blank">Obter chave de acesso</a>',
        ]];

        $company_id = ['company_id' => [
            'FriendlyName' => 'ID da Empresa',
            'Type' => 'text',
            'Description' => '<a href="https://app.nfe.io/companies/" style="text-decoration:underline;" target="_blank">Obter ID da empresa</a>',
        ]];

        $service_code = ['service_code' => [
            'FriendlyName' => 'Código de Serviço Principal',
            'Type' => 'text',
            'Description' => '<a style="text-decoration:underline;" href="https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/#o-que-e-codigo-de-servico" target="_blank">O que é Código de Serviço?</a>',
        ]];

        $rps_serial_number = ['rps_serial_number' => [
            'FriendlyName' => 'Série do RPS',
            'Type' => 'text',
            'Default' => 'IO',
            'Description' => '<a style="text-decoration:underline;" href="https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/" target="_blank">Saiba mais</a>',
        ]];

        $rps_number_camp = ['rps_number' => [
            'FriendlyName' => 'Número do RPS',
            'Type' => 'text',
            'Disabled' => 'true',
            'Description' => 'RPS atualizada de acordo com última nota fiscal emitida, clique no botão salvar alterações para atualizar automaticamente.',
        ]];

        // $rps_number_change_nfe = ['rps_number_change_nfe' => [
        //     'FriendlyName' => 'Alterar a tratativa de RPS',
        //     'Type' => 'radio',
        //     'Options' => 'true',
        //     'Default' => 'false',
        //     'Description' => 'Ao selecionar esta opção, a tratativa da RPS será realizada pela NFe.io em defintiivo.'
        // ]];

        $issue_note = ['issue_note' => [
            'FriendlyName' => 'Quando emitir NFE',
            'Type' => 'radio',
            'Options' => 'Manualmente,Quando a Fatura é Gerada,Quando a Fatura é Paga',
            'Default' => 'Quando a Fatura é Paga',
        ]];

        $issue_note_after = ['issue_note_after' => [
            'FriendlyName' => 'Agendar Emissão',
            'Type' => 'text',
            'Default' => '',
            'Description' => '<br>Número de dias após o pagamento da fatura que as notas devem ser emitidas. <span style="color:#c00">Preencher essa opção desativa a opção anterior.</span>',
        ]];

        $gnfe_email_nfe_config = ['gnfe_email_nfe_config' => [
            'FriendlyName' => 'Disparar e-mail com a nota',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Permitir o disparo da nota fiscal via NFE.io para o e-mail do usuário.',
        ]];

        $cancel_invoice_cancel_nfe = ['cancel_invoice_cancel_nfe' => [
            'FriendlyName' => 'Cancelar NFE',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Cancela a nota fiscal quando a fatura cancelada',
        ]];

        $debug = ['debug' => [
            'FriendlyName' => 'Debug',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Marque essa opção para salvar informações de diagnóstico no <a target="_blank" style="text-decoration:underline;" href="' . $admin_url . 'systemmodulelog.php">Log de Módulo</a> | Emitir documento de log <a target="_blank" href="' . $admin_url . 'configaddonmods.php?doc_log=true" style="text-decoration:underline;">AQUI</a>',
        ]];

        $insc_municipal = ['insc_municipal' => [
            'FriendlyName' => 'Inscrição Municipal',
            'Type' => 'dropdown',
            'Options' => gnfe_customfields_dropdow('Insc_municipal'),
            'Description' => 'Escolha o campo personalizado de Inscrição Municipal',
        ]];

        $cpf = ['cpf_camp' => [
            'FriendlyName' => 'CPF ',
            'Type' => 'dropdown',
            'Options' => gnfe_customfields_dropdow('Cpf'),
            'Description' => 'Escolha o campo personalizado do CPF',
        ]];

        $cnpj = ['cnpj_camp' => [
            'FriendlyName' => 'CNPJ',
            'Type' => 'dropdown',
            'Options' => gnfe_customfields_dropdow('Cnpj'),
            'Description' => 'Escolha o campo personalizado do CNPJ', ]];

        $tax = ['tax' => [
            'FriendlyName' => 'Aplicar imposto automaticamente em todos os produtos ?',
            'Type' => 'radio',
            'Options' => 'Sim,Não',
            'Default' => 'Sim',
        ]];

        $invoiceDetails = ['InvoiceDetails' => [
            'FriendlyName' => 'O que deve aparecer nos detalhes da fatura ?',
            'Type' => 'radio',
            'Options' => 'Número da fatura,Nome dos serviços,Número da fatura + Nome dos serviços',
            'Default' => 'Número da fatura',
        ]];

        $desc_custom = ['descCustom' => [
            'FriendlyName' => 'Adicione uma informação personalizada na nota fiscal:',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Esta informação será acrescida após detalhes da fatura.',
        ]];

        $development_ = ['NFEioEnvironment' => [
            'FriendlyName' => 'Ambiente de desenvolvimento',
            'Type' => 'yesno',
            'Default' => '',
            'Description' => 'Habilitar ambiente de desenvolvimento',
        ]];

        $footer = ['footer' => [
            'FriendlyName' => '',
            'Description' => '&copy; ' . date('Y') . ' <a target="_blank" title="Para suporte utilize o github" href="https://github.com/nfe/whmcs-addon/issues">Suporte módulo</a>',
        ]];

        $fields = array_merge($intro, $api_key, $company_id, $service_code, $rps_serial_number, $rps_number_camp, $issue_note, $issue_note_after, $gnfe_email_nfe_config,$development_, $cancel_invoice_cancel_nfe, $debug, $insc_municipal,$cpf,$cnpj, $tax, $invoiceDetails,$desc_custom, $footer);
        // $fields = array_merge($intro, $api_key, $company_id, $service_code, $rps_serial_number, $rps_number_camp, $rps_number_change_nfe, $issue_note, $issue_note_after, $gnfe_email_nfe_config,$development_, $cancel_invoice_cancel_nfe, $debug, $insc_municipal,$cpf,$cnpj, $tax, $invoiceDetails,$desc_custom, $footer);
        $configarray = [
            'name' => 'NFE.io',
            'description' => 'Módulo Nota Fiscal NFE.io para WHMCS',
            'version' => $module_version,
            'author' => '<a title="NFE.io Nota Fiscal WHMCS" href="https://github.com/nfe/whmcs-addon/" target="_blank" ><img src="' . $whmcs_url . 'modules/addons/gofasnfeio/lib/logo.png"></a>',
            'fields' => $fields,
        ];

        return $configarray;
    }
}
