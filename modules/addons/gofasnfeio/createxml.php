<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/functions.php';

use WHMCS\Database\Capsule;

$invoice_id = $_GET['nfe_id'];
	$row = Capsule::table('gofasnfeio')->where('id', '=', $invoice_id)->get(['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at', 'id']);
	$nfe = $row[0];
	if ((string) $nfe->status === (string) 'Issued') {
		$nfe_xml_for_invoice = gnfe_xml_nfe($nfe->nfe_id);
		header('Content-Type:text/xml');
		echo $nfe_xml_for_invoice;
	} else {
		echo 'Sem permissão';
	}

	exit();

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
