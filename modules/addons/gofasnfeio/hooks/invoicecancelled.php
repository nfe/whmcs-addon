<?php
/**
 * Módulo Gofas NFE.io para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=12529
 * @copyright	2020 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=12313
 * @version		1.2.0
 */
if (!defined("WHMCS")){die();}
$params = gnfe_config();
if( $params['cancel_invoice_cancel_nfe'] ){
	$nfe_for_invoice = gnfe_get_local_nfe($vars['invoiceid'],array('nfe_id', 'status', 'services_amount', 'environment'));
	if( $nfe_for_invoice['status'] === (string)'Issued' ) {
		$invoice = localAPI('GetInvoice',  array('invoiceid' => $vars['invoiceid']), false);
		$delete_nfe = gnfe_delete_nfe($nfe_for_invoice['nfe_id']);
		if(!$delete_nfe->message) {
			$gnfe_update_nfe = gnfe_update_nfe((object)array('id'=>$nfe_for_invoice['nfe_id'], 'status'=>'Cancelled','servicesAmount'=>$nfe_for_invoice['services_amount'],'environment'=>$nfe_for_invoice['environment'], 'flow_status' => $nfe_for_invoice['flow_status'] ),$invoice['userid'],$vars['invoiceid'],'n/a',$nfe_for_invoice['created_at'],date("Y-m-d H:i:s"));
		}
	}
	if($params['debug']) {
		logModuleCall('gofas_nfeio', 'InvoiceCancelled', array('vars'=>$vars, 'params'=>$params,'nfe_for_invoice'=>$nfe_for_invoice,'invoice'=>$invoice, ), 'post', 
		array('nf'=>$nf,'delete_nfe'=>$delete_nfe,), 'replaceVars');
	}
}