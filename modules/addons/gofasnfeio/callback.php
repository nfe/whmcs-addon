<?php
/**
 * Módulo Gofas NFE.io para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=12529
 * @copyright	2020 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=12313
 * @version		1.1.2
 */
require_once __DIR__ . '/../../../init.php';
use WHMCS\Database\Capsule;
$post = json_decode(file_get_contents('php://input'), true);
if($post){
	require_once __DIR__ . '/functions.php';
	$params = array();
	foreach( Capsule::table('tbladdonmodules') -> where( 'module', '=', 'gofasnfeio' ) -> get( array( 'setting', 'value') ) as $settings ) {
		$params[$settings->setting] = $settings->value;
	}	
	foreach( Capsule::table('gofasnfeio')->where('nfe_id', '=', $post['id'])->
	get(array( 'invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at' )) as $key => $value ) {
			$nfe_for_invoice[$key]					= json_decode(json_encode($value), true);
	}
	$nfe = $nfe_for_invoice['0'];
	if((string)$nfe['nfe_id'] === (string)$post['id'] and $nfe['status'] !== (string)$post['status'] ){
		$new_nfe = array(
				'invoice_id'=>$nfe['invoice_id'],
				'user_id'=>$nfe['user_id'],
				'nfe_id'=>$nfe['nfe_id'],
				'status'=>$post['status'],
				'services_amount'=>$nfe['services_amount'],
				'environment'=>$nfe['environment'],
				'flow_status'=>$post['flowStatus'],
				'pdf'=>$nfe['pdf'],
				'created_at'=>$nfe['created_at'],
				'updated_at'=>date("Y-m-d H:i:s"),
			);
	try {
		$save_nfe = Capsule::table('gofasnfeio')->where('nfe_id', '=', $post['id'])->update($new_nfe);
	}
	catch (\Exception $e) {
		$e->getMessage();
	}	
	}
	if($params['debug']) {
		logModuleCall('gofas_nfeio', 'receive_callback', array('post'=>$post), 'post',  array('nfe_local'=>$nfe), 'replaceVars');
	}
	foreach( Capsule::table('gofasnfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->take(1)->get( array( 'invoice_id' )) as $waiting ) {
	foreach( Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get( array( 'id', 'userid', 'total' ) ) as $invoices ) {
		$invoice = localAPI('GetInvoice',  array('invoiceid' => $waiting->invoice_id), false);
		$client = localAPI('GetClientsDetails',array( 'clientid' => $invoice['userid'], 'stats' => false, ), false);
		foreach( $invoice['items']['item'] as $value){
			$line_items[]	= $value['description'];	
		}
		$customer = gnfe_customer($invoices->userid,$client);
		$company = gnfe_get_company();
		$postfields = array(
			'cityServiceCode' => $params['service_code'],
			'description'     => substr( implode("\n",$line_items),  0, 600),
			'servicesAmount'  => $invoice['total'],
			'borrower' => array(
				'federalTaxNumber' => $customer['document'],
				'name'             => $customer['name'],
				'email'            => $client_email,
				'address'          => array(
					'country'               => gnfe_country_code($client['countrycode']),
					'postalCode'            => preg_replace('/[^0-9]/', '', $client['postcode']),
					'street'                => str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1'])),
					'number'                => preg_replace('/[^0-9]/', '', $client['address1']),
					'additionalInformation' => '',
					'district'              => $client['address2'],
					'city' => array(
						'code' => gnfe_ibge(preg_replace("/[^0-9]/", "", $client['postcode'])),
						'name' => $client['city']
					),
					'state' => $client['state'],
					)
				),
				'rpsSerialNumber' => $company['companies']['rpsSerialNumber'],
				'rpsNumber' => (int)(($company['companies']['rpsNumber'])+1),
			);
			$nfe = gnfe_issue_nfe($postfields);
			if($nfe->message) {
				$error .= $nfe->message;				
			}
				
			if(!$nfe->message) {
				$gnfe_update_nfe = gnfe_update_nfe($nfe,$invoices->userid,$invoices->id,'n/a',date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
				if($gnfe_update_nfe and $gnfe_update_nfe !== 'success') {
					$error = $gnfe_update_nfe;
				}
			}
		}
		if($params['debug']) {
			logModuleCall('gofas_nfeio', 'after_receive_callback', array('$params'=>$params, '$datepaid'=>$datepaid, '$datepaid_to_issue'=>$datepaid_to_issue), 'post',  array('$processed_invoices'=>$processed_invoices, '$nfe'=>$nfe,'error'=>$error ), 'replaceVars');
		}
	}	
}