<?php

if (!defined('WHMCS')) {
	exit();
}
use WHMCS\Database\Capsule;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/update.php';

if (!function_exists('gofasnfeio_config')) {
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

		gnfe_verifyInstall();

		try {
			gnfe_insert_issue_nfe_cond_in_database();
		} catch (\Throwable $th) {
		}

		// --------------------------------------------------------------------------------------------

		// Versão do módulo deste arquivo.
		$module_version = '1.4.0';
		$module_version_int = (int) preg_replace('/[^0-9]/', '', $module_version);

		// Versão do módulo que consta no banco de dados.
		$previous_version = gnfe_config('module_version');

		// A versão do módulo precisa ser inserida no tabela do banco de dados, porque ela não existia no banco de dados até agora.
		if (!$previous_version) {
			Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'module_version', 'value' => $module_version]);
			$previous_version = $module_version;
		}
		$previous_version_int = (int) preg_replace('/[^0-9]/', '', $previous_version);

		$update_denied = '';
		// Se module_version e previous_version não forem iguais, quer dizer que houve uma atualização do módulo.
		if ($module_version_int !== $previous_version_int) {
			// Pega apenas as versões MAJOR.MINOR do módulo instalado e da versão do módulo no banco de dados.
			$previous_major_minor_version = intval(substr($previous_version_int, 0, 2));
			$module_major_minor_version = intval(substr($module_version_int, 0, 2));

			// Atende a diretriz "Exclusivamente a partir da versão 1.5.0 não será permitido atualização do modulo por versões inferiores a 1.4.0.".
			// Se a versão do módulo for igual a versão do módulo no
			// banco de dados mais 1, então a atualização é permitida.
			if ($module_major_minor_version === $previous_major_minor_version + 1) {
				Capsule::table('tbladdonmodules')->where('module', 'gofasnfeio')->where('setting', 'module_version')->update(['value' => $module_version]);
			} else {
				$update_denied = <<<EOT
                    <p style="font-size: 14px; color: red;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Você está tentando instalar a versão $module_version e a versão anteriormente instalada foi a $previous_version
                    </p>
                    <p style="font-size: 14px; color: red;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Entretanto, só é permitida a atualização do módulo para frente e de uma em uma versão. Ex.: 1.3.0 para 1.4.0, 1.4.3 para 1.5.0
                    </p>
                EOT;
				$module_version = $previous_version;
			}
		}

		// --------------------------------------------------------------------------------------------

		// Verifica se há atualizações disponíveis.
		$available_update_ = gnfe_verify_module_updates();
		$available_version_int = (int) preg_replace('/[^0-9]/', '', str_replace('v', '', $available_update_));

		if ($available_version_int <= $module_version_int) { // $available_version_int <= $module_version_int
			$available_update_message = '<p style="font-size: 14px;color:green;"><i class="fas fa-check-square"></i> Você está executando a versão mais recente do módulo.</p>';
		} else {
			$available_update_message = '<p style="font-size: 14px;color:red;"><i class="fas fa-exclamation-triangle"></i> Nova versão disponível no <a style="color:#CC0000;text-decoration:underline;" href="https://github.com/nfe/whmcs-addon/releases" target="_blank">Github</a></p>';
		}

		// --------------------------------------------------------------------------------------------

		// Atende à diretriz "Na versão superior a 1.4.0, ou seja na 1.5.0 este recurso de migração deve esta desabilitado."
		// e a "Exclusivamente na versão 1.4.0 será realizada a migração da RPS do módulo para a NFE."
		// Verifica se a versão dos arquivos do módulo corresponde a versão do módulo no banco de dados.
		if ($module_version_int >= 140 && $module_version_int < 150 && $previous_version_int >= 140 && $previous_version_int < 150) {

			// Verifica se a configuração rps_number existe no banco de dados.
			if (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'rps_number')->count() == 0) {
				try {
					$nfe_rps = gnfe_get_nfes()['rpsNumber'];
					Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'rps_number', 'value' => $nfe_rps]);
				} catch (Exception $e) {
					logModuleCall('gofas_nfeio', 'gofasnfeio_config', '', $e->getMessage(), '', '');
				}
			}

			$whmcs_rps = gnfe_config('rps_number');

			if (is_numeric($whmcs_rps) || $whmcs_rps == '') {
				$company_data = gnfe_get_company_info();

				if (isset($company_data['error'])) {
					logModuleCall('gofas_nfeio', 'gnfe_get_company_info', '', $company_data['error'], '', '');
				} else {
					gnfe_put_rps($company_data, $whmcs_rps); // Transfere a tratativa do RPS para a NFe.
				}
			}
		}

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
		create_table_product_code();

		if (version_compare($previous_version, '1.2.7', '<')) {
			set_code_service_camp_gofasnfeio();
			set_custom_field_ini_date();
		}

		$intro = ['intro' => [
			'FriendlyName' => '',
			'Description' => '<h4 style="padding-top: 5px;">Módulo Nota Fiscal NFE.io para WHMCS v' . $module_version . '</h4>',
		]];

		$intro['intro']['Description'] .= '<p>' . $available_update_message . '</p>';
		$intro['intro']['Description'] .= '<p>' . $update_denied . '</p>';

		$intro['intro']['Description'] .=
			'<a style="text-decoration:underline;" href="https://app.nfe.io/companies/edit/fiscal/' . gnfe_config('company_id') . '" target="_blank">
                Consultar: RPS | Série
            </a>';

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

		$issue_note_default_cond = ['issue_note_default_cond' => [
			'FriendlyName' => 'Quando emitir NFE',
			'Type' => 'radio',
			'Options' => 'Quando a fatura é gerada,Quando a fatura é paga,Manualmente',
			'Default' => 'Manualmente',
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

		$send_invoice_url = ['send_invoice_url' => [
			'FriendlyName' => 'Exibir link da fatura na nota fiscal?',
			'Type' => 'radio',
			'Options' => 'Sim,Não',
			'Default' => 'Não',
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

		$fields = array_merge($intro, $api_key, $company_id, $service_code, $issue_note_default_cond, $issue_note_after, $gnfe_email_nfe_config, $development_, $cancel_invoice_cancel_nfe, $debug, $insc_municipal, $cpf, $cnpj, $tax, $invoiceDetails, $send_invoice_url, $desc_custom, $footer);
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
