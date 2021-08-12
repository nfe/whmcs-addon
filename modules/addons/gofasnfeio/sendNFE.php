<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

function emitNFE($invoices,$nfeio) {
    $invoice = localAPI('GetInvoice', ['invoiceid' => $invoices->id], false);
    $client = localAPI('GetClientsDetails', ['clientid' => $invoices->userid], false);

    $params = gnfe_config();

    //create second option from description nfe
    foreach ($invoice['items']['item'] as $value) {
        $line_items[] = $value['description'];
    }

    //  CPF/CNPJ/NAME
    $customer = gnfe_customer($invoices->userid, $client);
    logModuleCall('gofas_nfeio', 'gnfe_customer', $customer, '','', '');

    if ($customer['doc_type'] == 2) {
        if ($client['companyname'] != '') {
            $name = $client['companyname'];
        } else {
            $name = $client['fullname'];
        }
    } elseif ($customer['doc_type'] == 1 || 'CPF e/ou CNPJ ausente.' == $customer || !$customer['doc_type']) {
        $name = $client['fullname'];
    }
    $name = htmlspecialchars_decode($name);

    //service_code
    $service_code = $nfeio->service_code ? $nfeio->service_code : $params['service_code'];

    //description nfe
    if ($params['InvoiceDetails'] == 'Número da fatura') {
        $gnfeWhmcsUrl = Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value'])[0]->value;

        $desc = 'Nota referente a fatura #' . $invoices->id . '  ';
        if ($params['send_invoice_url'] === 'Sim') {
            $desc .= $gnfeWhmcsUrl . 'viewinvoice.php?id=' . $invoices->id;
        }
        $desc .= ' ' . $params['descCustom'];

        $gnfeWhmcsUrl = Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value'])[0]->value;

    } elseif ($params['InvoiceDetails'] == 'Nome dos serviços') {
        $desc = substr(implode("\n", $line_items), 0, 600) . ' ' . $params['descCustom'];
    } elseif ($params['InvoiceDetails'] == 'Número da fatura + Nome dos serviços') {
        $gnfeWhmcsUrl = Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value'])[0]->value;
        $desc = 'Nota referente a fatura #' . $invoices->id . '  ';
        if ($params['send_invoice_url'] === 'Sim') {
            $desc .= $gnfeWhmcsUrl . 'viewinvoice.php?id=' . $invoices->id;
        }
        $desc .= ' | ' . substr(implode("\n", $line_items), 0, 600) . ' '. $params['descCustom'];
    }

    logModuleCall('gofas_nfeio', 'description-descCustom', $params['descCustom'], '','', '');
    logModuleCall('gofas_nfeio', 'description-InvoiceDetails', $params['InvoiceDetails'], '','', '');
    logModuleCall('gofas_nfeio', 'description', $params, '','', '');

    //define address
    if (strpos($client['address1'], ',')) {
        $array_adress = explode(',', $client['address1']);
        $street = $array_adress[0];
        $number = $array_adress[1];
    } else {
        $street = str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1']));
        $number = preg_replace('/[^0-9]/', '', $client['address1']);
    }

    if ($params['gnfe_email_nfe_config'] == 'on') {
        $client_email = $client['email'];
    } else {
        $client_email = '';
    }

    logModuleCall('gofas_nfeio', 'sendNFE - customer', $customer, '','', '');
    $code = gnfe_ibge(preg_replace('/[^0-9]/', '', $client['postcode']));
    if ($code == 'ERROR') {
        logModuleCall('gofas_nfeio', 'sendNFE - gnfe_ibge', $customer, '','ERROR', '');
        update_status_nfe($nfeio->invoice_id,'Error_cep');
    } else {
        //cria o array do request
        $postfields = createRequestFromAPI($service_code,$desc,$nfeio->services_amount,$customer['document'],$customer['insc_municipal'],
        $name,$client_email,$client['countrycode'],$client['postcode'],$street,$number,$client['address2'],
        $code,$client['city'],$client['state']);

        //envia o requisição
        $nfe = gnfe_issue_nfe($postfields);

        if ($nfe->message) {
            logModuleCall('gofas_nfeio', 'sendNFE', $postfields, $nfe, 'ERROR', '');
        }
        if (!$nfe->message) {
            logModuleCall('gofas_nfeio', 'sendNFE', $postfields, $nfe, 'OK', '');
            $gnfe_update_nfe = gnfe_update_nfe($nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $nfeio->id);

            if ($gnfe_update_nfe && $gnfe_update_nfe !== 'success') {
                logModuleCall('gofas_nfeio', 'sendNFE - gnfe_update_nfe', [$nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $nfeio->id], $gnfe_update_nfe, 'ERROR', '');
            }
        }
    }
}

function createRequestFromAPI($service_code,$desc,$services_amount,$document,$insc_municipal = '',
$name,$email,$countrycode,$postcode,$street,$number,$address2,$code,$city,$state) {
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
                'country' => gnfe_country_code($countrycode),
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