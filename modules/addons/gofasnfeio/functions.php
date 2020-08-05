<?php
/**
 * Módulo Gofas NFE.io para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=12529
 * @copyright	2020 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=12313
 * @version		1.1.3
 */
if (!defined("WHMCS")){die();}
use WHMCS\Database\Capsule;
// Get config
if( !function_exists('gnfe_config') ) {
	function gnfe_config($set = false) {
		$setting = array();
		foreach( Capsule::table('tbladdonmodules') -> where( 'module', '=', 'gofasnfeio' ) -> get( array( 'setting', 'value') ) as $settings ) {
			$setting[$settings->setting] = $settings->value;
		}
		if($set) {
			return $setting[$set];
		}
		return $setting;
	}
}


if( !function_exists('gnfe_customer') ) {
	function gnfe_customer($user_id,$client) {
		//Determine custom fields id
		$customfields = array();
		foreach( Capsule::table('tblcustomfields') -> where( 'type', '=', 'client' )  -> get( array( 'fieldname', 'id' ) ) as $customfield ) {
			$customfield_id					= $customfield->id;
			$customfield_name				= ' '.strtolower( $customfield->fieldname );
	
			// cpf
			if ( strpos( $customfield_name, 'cpf') and !strpos( $customfield_name, 'cnpj') ) {
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}	
			// cnpj
			if ( strpos( $customfield_name, 'cnpj') and !strpos( $customfield_name, 'cpf') ) {
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
			// cpf + cnpj
			if ( strpos( $customfield_name, 'cpf') and strpos( $customfield_name, 'cnpj') ) {
				foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
					$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
					$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
				}
			}
		}

		// Cliente possui CPF e CNPJ
		// CPF com 1 nº a menos, adiciona 0 antes do documento
		if ( strlen( $cpf_customfield_value ) === 10 ) {
			$cpf = '0'.$cpf_customfield_value;
		}
		// CPF com 11 dígitos
		elseif ( strlen( $cpf_customfield_value ) === 11) {
			$cpf = $cpf_customfield_value;
		}
		// CNPJ no campo de CPF com um dígito a menos
		elseif ( strlen( $cpf_customfield_value ) === 13 ) {
			$cpf = false; 
			$cnpj = '0'.$cpf_customfield_value;
		}
		// CNPJ no campo de CPF
		elseif ( strlen( $cpf_customfield_value ) === 14 ) {
			$cpf 				= false;
			$cnpj				= $cpf_customfield_value;
		}
		// cadastro não possui CPF
		elseif ( !$cpf_customfield_value || strlen( $cpf_customfield_value ) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen( $cpf_customfield_value ) !== 13 || strlen($cpf_customfield_value) !== 14 ) {	
			$cpf = false;
		}
		// CNPJ com 1 nº a menos, adiciona 0 antes do documento
		if ( strlen($cnpj_customfield_value) === 13 ) {
			$cnpj = '0'.$cnpj_customfield_value;
		}
		// CNPJ com nº de dígitos correto
		elseif ( strlen($cnpj_customfield_value) === 14 ) {
			$cnpj = $cnpj_customfield_value;
		}
		// Cliente não possui CNPJ
		elseif ( !$cnpj_customfield_value and strlen( $cnpj_customfield_value ) !== 14 and strlen($cnpj_customfield_value) !== 13 and strlen( $cpf_customfield_value ) !== 13 and strlen( $cpf_customfield_value ) !== 14  ) {
			$cnpj = false;
		}
		if ( ( $cpf and $cnpj ) or ( !$cpf and $cnpj ) ) {
			$custumer['doc_type']	= 2;
			$custumer['document']	= $cnpj;
			if ( $client['companyname'] ) {
				$custumer['name']	= $client['companyname'];
			}
			elseif ( !$client['companyname'] ) {
				$custumer['name']	= $client['firstname'].' '.$client['lastname'];
			}
		}
		elseif ( $cpf and !$cnpj ) {
			$custumer['doc_type']	= 1;
			$custumer['document']	= $cpf;
			$custumer['name']	= $client['firstname'].' '.$client['lastname'];
		}

		if (!$cpf and !$cnpj ) {
			$error = 'CPF e/ou CNPJ ausente.';
		}
		
		if(!$error) {
			return $custumer;
		}
		if($error) {
			return $custumer['error'] = $error;
		}
	}
}
if( !function_exists('gnfe_country_code') ) {
	function gnfe_country_code($country){
		$array = array('BD' => 'BGD', 'BE' => 'BEL', 'BF' => 'BFA', 'BG' => 'BGR', 'BA' => 'BIH', 'BB' => 'BRB', 'WF' => 'WLF', 'BL' => 'BLM', 'BM' => 'BMU', 'BN' => 'BRN', 'BO' => 'BOL', 'BH' => 'BHR', 'BI' => 'BDI', 'BJ' => 'BEN', 'BT' => 'BTN', 'JM' => 'JAM', 'BV' => 'BVT', 'BW' => 'BWA', 'WS' => 'WSM', 'BQ' => 'BES', 'BR' => 'BRA', 'BS' => 'BHS', 'JE' => 'JEY', 'BY' => 'BLR', 'BZ' => 'BLZ', 'RU' => 'RUS', 'RW' => 'RWA', 'RS' => 'SRB', 'TL' => 'TLS', 'RE' => 'REU', 'TM' => 'TKM', 'TJ' => 'TJK', 'RO' => 'ROU', 'TK' => 'TKL', 'GW' => 'GNB', 'GU' => 'GUM', 'GT' => 'GTM', 'GS' => 'SGS', 'GR' => 'GRC', 'GQ' => 'GNQ', 'GP' => 'GLP', 'JP' => 'JPN', 'GY' => 'GUY', 'GG' => 'GGY', 'GF' => 'GUF', 'GE' => 'GEO', 'GD' => 'GRD', 'GB' => 'GBR', 'GA' => 'GAB', 'SV' => 'SLV', 'GN' => 'GIN', 'GM' => 'GMB', 'GL' => 'GRL', 'GI' => 'GIB', 'GH' => 'GHA', 'OM' => 'OMN', 'TN' => 'TUN', 'JO' => 'JOR', 'HR' => 'HRV', 'HT' => 'HTI', 'HU' => 'HUN', 'HK' => 'HKG', 'HN' => 'HND', 'HM' => 'HMD', 'VE' => 'VEN', 'PR' => 'PRI', 'PS' => 'PSE', 'PW' => 'PLW', 'PT' => 'PRT', 'SJ' => 'SJM', 'PY' => 'PRY', 'IQ' => 'IRQ', 'PA' => 'PAN', 'PF' => 'PYF', 'PG' => 'PNG', 'PE' => 'PER', 'PK' => 'PAK', 'PH' => 'PHL', 'PN' => 'PCN', 'PL' => 'POL', 'PM' => 'SPM', 'ZM' => 'ZMB', 'EH' => 'ESH', 'EE' => 'EST', 'EG' => 'EGY', 'ZA' => 'ZAF', 'EC' => 'ECU', 'IT' => 'ITA', 'VN' => 'VNM', 'SB' => 'SLB', 'ET' => 'ETH', 'SO' => 'SOM', 'ZW' => 'ZWE', 'SA' => 'SAU', 'ES' => 'ESP', 'ER' => 'ERI', 'ME' => 'MNE', 'MD' => 'MDA', 'MG' => 'MDG', 'MF' => 'MAF', 'MA' => 'MAR', 'MC' => 'MCO', 'UZ' => 'UZB', 'MM' => 'MMR', 'ML' => 'MLI', 'MO' => 'MAC', 'MN' => 'MNG', 'MH' => 'MHL', 'MK' => 'MKD', 'MU' => 'MUS', 'MT' => 'MLT', 'MW' => 'MWI', 'MV' => 'MDV', 'MQ' => 'MTQ', 'MP' => 'MNP', 'MS' => 'MSR', 'MR' => 'MRT', 'IM' => 'IMN', 'UG' => 'UGA', 'TZ' => 'TZA', 'MY' => 'MYS', 'MX' => 'MEX', 'IL' => 'ISR', 'FR' => 'FRA', 'IO' => 'IOT', 'SH' => 'SHN', 'FI' => 'FIN', 'FJ' => 'FJI', 'FK' => 'FLK', 'FM' => 'FSM', 'FO' => 'FRO', 'NI' => 'NIC', 'NL' => 'NLD', 'NO' => 'NOR', 'NA' => 'NAM', 'VU' => 'VUT', 'NC' => 'NCL', 'NE' => 'NER', 'NF' => 'NFK', 'NG' => 'NGA', 'NZ' => 'NZL', 'NP' => 'NPL', 'NR' => 'NRU', 'NU' => 'NIU', 'CK' => 'COK', 'XK' => 'XKX', 'CI' => 'CIV', 'CH' => 'CHE', 'CO' => 'COL', 'CN' => 'CHN', 'CM' => 'CMR', 'CL' => 'CHL', 'CC' => 'CCK', 'CA' => 'CAN', 'CG' => 'COG', 'CF' => 'CAF', 'CD' => 'COD', 'CZ' => 'CZE', 'CY' => 'CYP', 'CX' => 'CXR', 'CR' => 'CRI', 'CW' => 'CUW', 'CV' => 'CPV', 'CU' => 'CUB', 'SZ' => 'SWZ', 'SY' => 'SYR', 'SX' => 'SXM', 'KG' => 'KGZ', 'KE' => 'KEN', 'SS' => 'SSD', 'SR' => 'SUR', 'KI' => 'KIR', 'KH' => 'KHM', 'KN' => 'KNA', 'KM' => 'COM', 'ST' => 'STP', 'SK' => 'SVK', 'KR' => 'KOR', 'SI' => 'SVN', 'KP' => 'PRK', 'KW' => 'KWT', 'SN' => 'SEN', 'SM' => 'SMR', 'SL' => 'SLE', 'SC' => 'SYC', 'KZ' => 'KAZ', 'KY' => 'CYM', 'SG' => 'SGP', 'SE' => 'SWE', 'SD' => 'SDN', 'DO' => 'DOM', 'DM' => 'DMA', 'DJ' => 'DJI', 'DK' => 'DNK', 'VG' => 'VGB', 'DE' => 'DEU', 'YE' => 'YEM', 'DZ' => 'DZA', 'US' => 'USA', 'UY' => 'URY', 'YT' => 'MYT', 'UM' => 'UMI', 'LB' => 'LBN', 'LC' => 'LCA', 'LA' => 'LAO', 'TV' => 'TUV', 'TW' => 'TWN', 'TT' => 'TTO', 'TR' => 'TUR', 'LK' => 'LKA', 'LI' => 'LIE', 'LV' => 'LVA', 'TO' => 'TON', 'LT' => 'LTU', 'LU' => 'LUX', 'LR' => 'LBR', 'LS' => 'LSO', 'TH' => 'THA', 'TF' => 'ATF', 'TG' => 'TGO', 'TD' => 'TCD', 'TC' => 'TCA', 'LY' => 'LBY', 'VA' => 'VAT', 'VC' => 'VCT', 'AE' => 'ARE', 'AD' => 'AND', 'AG' => 'ATG', 'AF' => 'AFG', 'AI' => 'AIA', 'VI' => 'VIR', 'IS' => 'ISL', 'IR' => 'IRN', 'AM' => 'ARM', 'AL' => 'ALB', 'AO' => 'AGO', 'AQ' => 'ATA', 'AS' => 'ASM', 'AR' => 'ARG', 'AU' => 'AUS', 'AT' => 'AUT', 'AW' => 'ABW', 'IN' => 'IND', 'AX' => 'ALA', 'AZ' => 'AZE', 'IE' => 'IRL', 'ID' => 'IDN', 'UA' => 'UKR', 'QA' => 'QAT', 'MZ' => 'MOZ');
		return $array[$country];
	}
}

if( !function_exists('gnfe_ibge') ) {
	function gnfe_ibge($zip) {
		$curl = curl_init();
		curl_setopt ($curl, CURLOPT_URL, 'https://open.nfe.io/v1/cities/'.$zip.'/postalcode');
		curl_setopt ($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		$city = json_decode(json_encode(json_decode($response)));
		return $city->city->code;
	}
}

if( !function_exists('gnfe_queue_nfe') ) {
	function gnfe_queue_nfe($invoice_id){
		$invoice = localAPI('GetInvoice',  array('invoiceid' => $invoice_id), false);
		$data = array(
				'invoice_id'=>$invoice_id,
				'user_id'=>$invoice['userid'],
				'nfe_id'=>'waiting',
				'status'=>'Waiting',
				'services_amount'=>$invoice['total'],
				'environment'=>'waiting',
				'flow_status'=>'waiting',
				'pdf'=>'waiting',
				'created_at'=>date("Y-m-d H:i:s"),
				'updated_at'=>'waiting',
				'rpsSerialNumber'=>'waiting',
			);
		$nfe_for_invoice = gnfe_get_local_nfe($invoice_id,array('status'));
		if(!$nfe_for_invoice['status']) {
			try {
				$save_nfe = Capsule::table('gofasnfeio')->insert($data);
				return 'success';
			}
			catch (\Exception $e) {
				return $e->getMessage();
			}
		}
		elseif((string)$nfe_for_invoice['status'] === (string)'Cancelled' or (string)$nfe_for_invoice['status'] === (string)'Error') {
			
			try {
				$update_nfe = Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->update($data);
				return 'success';
			}
			catch (\Exception $e) {
				return $e->getMessage();
			}
		}
	}
}

if( !function_exists('gnfe_issue_nfe') ) {
	function gnfe_issue_nfe($postfields){
		
		$webhook_url = gnfe_whmcs_url().'modules/addons/gofasnfeio/callback.php';
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gnfe_webhook_id') -> get( array( 'value' ) ) as $gnfe_webhook_id_ ) {
			$gnfe_webhook_id				= $gnfe_webhook_id_->value;
		}

		if($gnfe_webhook_id){
			$check_webhook = gnfe_check_webhook($gnfe_webhook_id);
			if($check_webhook['message']) {
				$error .= $check_webhook['message'];
			}
		}
		if( $gnfe_webhook_id and (string)$check_webhook['hooks']['url'] !== (string)$webhook_url ){
			$create_webhook = gnfe_create_webhook($webhook_url);
			if($create_webhook['message']) {
				$error .= $create_webhook['message'];
			}
			if($create_webhook['hooks']['id']) {
				try {
					Capsule::table('tblconfiguration')->where( 'setting', 'gnfe_webhook_id')->update(array('value' => $create_webhook['hooks']['id'], 'created_at' =>   date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));
				}
				catch (\Exception $e) {
					$error .= $e->getMessage();
				}
			}
			$delete_webhook = gnfe_delete_webhook($gnfe_webhook_id);
			if($delete_webhook['message']) {
				$error .= $create_webhook['message'];
			}
		}
		if(!$gnfe_webhook_id){
			$create_webhook = gnfe_create_webhook($webhook_url);
			if($create_webhook['message']) {
				$error .= $create_webhook['message'];
			}
			if($create_webhook['hooks']['id']) {
				try {
					Capsule::table('tblconfiguration')->insert(array('setting' => 'gnfe_webhook_id', 'value' => $create_webhook['hooks']['id'], 'created_at' => date("Y-m-d H:i:s") , 'updated_at' => date("Y-m-d H:i:s")));
				}
				catch (\Exception $e) {
					$error .=$e->getMessage();
				}
			}
		}
		if(gnfe_config('debug')) {
			logModuleCall('gofas_nfeio', 'check_webhook', array('gnfe_webhook_id'=> $gnfe_webhook_id, 'check_webhook'=>$check_webhook,'check_webhook_url'=>$check_webhook['hooks']['url']), 'post',  array('create_webhook'=>$create_webhook, 'delete_webhook'=>$delete_webhook, 'error'=>$error), 'replaceVars');
		}
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($postfields));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode(json_encode(json_decode($response)));
	}
}

if( !function_exists('gnfe_get_nfe') ) {
	function gnfe_get_nfe($nf){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/companies/".gnfe_config('company_id')."/serviceinvoices/".$nf);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response);
	}
}
if( !function_exists('gnfe_get_nfes') ) {
	function gnfe_get_nfes(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/companies/".gnfe_config('company_id')."/serviceinvoices?pageCount=1&pageIndex=1");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response, true);
	}
}

if( !function_exists('gnfe_delete_nfe') ) {
	function gnfe_delete_nfe($nf){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/companies/".gnfe_config('company_id')."/serviceinvoices/".$nf);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response);
	}
}

if( !function_exists('gnfe_email_nfe') ) {
	function gnfe_email_nfe($nf){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/sendemail');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response);
	}
}

if( !function_exists('gnfe_pdf_nfe') ) {
	function gnfe_pdf_nfe($nf){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/pdf');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/pdf', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response);
	}
}
if( !function_exists('gnfe_xml_nfe') ) {
	function gnfe_xml_nfe($nf){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.gnfe_config('company_id').'/serviceinvoices/'.$nf.'/xml');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response);
	}
}
if( !function_exists('gnfe_whmcs_url') ) {
	function gnfe_whmcs_url(){
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gnfewhmcsurl') -> get( array('value') ) as $gnfewhmcsurl_ ) {
			$gnfewhmcsurl					= $gnfewhmcsurl_->value;
		}
		return $gnfewhmcsurl;
	}
}
if( !function_exists('gnfe_whmcs_admin_url') ) {
	function gnfe_whmcs_admin_url(){
		foreach( Capsule::table('tblconfiguration') -> where('setting', '=', 'gnfewhmcsadminurl') -> get( array('value') ) as $gnfewhmcsadminurl_ ) {
			$gnfewhmcsadminurl				= $gnfewhmcsadminurl_->value;
		}
		return $gnfewhmcsadminurl;
	}
}
if( !function_exists('gnfe_save_nfe') ) {
	function gnfe_save_nfe($nfe,$user_id,$invoice_id,$pdf,$created_at,$updated_at) {
			$data = array(
				'invoice_id'=>$invoice_id,
				'user_id'=>$user_id,
				'nfe_id'=>$nfe->id,
				'status'=>$nfe->status,
				'services_amount'=>$nfe->servicesAmount,
				'environment'=>$nfe->environment,
				'flow_status'=>$nfe->flowStatus,
				'pdf'=>$pdf,
				'created_at'=>$created_at,
				'updated_at'=>$updated_at,
				'rpsSerialNumber'=>$nfe->rpsSerialNumber,
				'rpsNumber'=>$nfe->rpsNumber,
			);
	try {
		$save_nfe = Capsule::table('gofasnfeio')->insert($data);
		return 'success';
	}
	catch (\Exception $e) {
		return $e->getMessage();
	}
}}
if( !function_exists('gnfe_update_nfe') ) {
	function gnfe_update_nfe($nfe,$user_id,$invoice_id,$pdf,$created_at,$updated_at) {
			$data = array(
				'invoice_id'=>$invoice_id,
				'user_id'=>$user_id,
				'nfe_id'=>$nfe->id,
				'status'=>$nfe->status,
				'services_amount'=>$nfe->servicesAmount,
				'environment'=>$nfe->environment,
				'flow_status'=>$nfe->flowStatus,
				'pdf'=>$pdf,
				'created_at'=>$created_at,
				'updated_at'=>$updated_at,
				'rpsSerialNumber'=>$nfe->rpsSerialNumber,
				'rpsNumber'=>$nfe->rpsNumber,
			);
	try {
		$save_nfe = Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->update($data);
		return 'success';
	}
	catch (\Exception $e) {
		return $e->getMessage();
	}
}}
if( !function_exists('gnfe_get_local_nfe') ) {
	function gnfe_get_local_nfe($invoice_id, $values ) {
		foreach( Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->get($values) as $key => $value ) {
			$nfe_for_invoice[$key]					= json_decode(json_encode($value), true);
		}
		return $nfe_for_invoice['0'];
	}
}

if( !function_exists('gnfe_check_webhook') ) {
	function gnfe_check_webhook($id) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/hooks/".$id);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode(json_encode(json_decode($response)), true);
	}
}

if( !function_exists('gnfe_create_webhook') ) {
	function gnfe_create_webhook($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/hooks");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode(array('url'=> $url, 'contentType'=> 'application/json', 'secret'=> (string)time(), 'events'=>array('issue', 'cancel'), 'status'=>'Active',  )));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode(json_encode(json_decode($response)), true);
	}
}
if( !function_exists('gnfe_delete_webhook') ) {
	function gnfe_delete_webhook($id) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/hooks/".$id);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode(json_encode(json_decode($response)), true);
	}
}
/**
 * @gnfe_nfe_flowStatus string
 * Possible values:
 * CancelFailed, IssueFailed, Issued, Cancelled, PullFromCityHall, WaitingCalculateTaxes,
 * WaitingDefineRpsNumber, WaitingSend, WaitingSendCancel, WaitingReturn, WaitingDownload
 *
 */
if( !function_exists('gnfe_nfe_flowStatus') ) {
	function gnfe_nfe_flowStatus($flowStatus) {
		if($flowStatus === 'CancelFailed'){
			$status = 'Cancelado por Erro';
		}
		if($flowStatus === 'IssueFailed'){
			$status = 'Falha ao Emitir';
		}
		if($flowStatus === 'Issued'){
			$status = 'Emitida';
		}
		if($flowStatus === 'Cancelled'){
			$status = 'Cancelada';
		}
		if($flowStatus === 'PullFromCityHall'){
			$status = 'Obtendo da Prefeitura';
		}
		if($flowStatus === 'WaitingCalculateTaxes'){
			$status = 'Aguardando Calcular Impostos';
		}
		if($flowStatus === 'WaitingDefineRpsNumber'){
			$status = 'Aguardando Definir Número Rps';
		}
		if($flowStatus === 'WaitingSend'){
			$status = 'Aguardando Enviar';
		}
		if($flowStatus === 'WaitingSendCancel'){
			$status = 'Aguardando Cancelar Envio';
		}
		if($flowStatus === 'WaitingReturn'){
			$status = 'Aguardando Retorno';
		}
		if($flowStatus === 'WaitingDownload'){
			$status = 'Aguardando Download';
		}
		
		return $status;
	}
}

if( !function_exists('gnfe_get_company') ) {
	function gnfe_get_company(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://api.nfe.io/v1/companies/".gnfe_config('company_id'));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'Accept: application/json', 'Authorization: '.gnfe_config('api_key')));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($curl);
		curl_close ($curl);
		return json_decode($response, true);
	}
}