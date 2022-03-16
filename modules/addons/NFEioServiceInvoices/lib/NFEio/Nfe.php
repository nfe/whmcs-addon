<?php

namespace NFEioServiceInvoices\NFEio;

use \WHMCS\Database\Capsule;

/**
 * Classe com métodos para manipulação de notas fiscais
 */

class Nfe
{
    /**
     * @var \NFEioServiceInvoices\Models\ServiceInvoices\Repository
     */
    private $serviceInvoicesRepo;
    /**
     * @var false|mixed|string
     */
    private $serviceInvoicesTable;
    /**
     * @var \NFEioServiceInvoices\Models\ProductCode\Repository
     */
    private $productCodeRepo;
    /**
     * @var \NFEioServiceInvoices\Configuration
     */
    private $moduleConfig;
    /**
     * @var \WHMCSExpert\Addon\Storage
     */
    private $storage;
    /**
     * @var \NFEioServiceInvoices\Legacy\Functions
     */
    private $legacyFunctions;


    public function __construct()
    {
        $this->productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->serviceInvoicesTable = $this->serviceInvoicesRepo->tableName();
        $this->moduleConfig = new \NFEioServiceInvoices\Configuration();
        $this->storage = new \WHMCSExpert\Addon\Storage($this->moduleConfig->getStorageKey());
        $this->legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
    }

    /**
     * Cria a nota fiscal com base na fatura e insere na fila (tabela) para emissão.
     *
     * @version 2.1.0
     * @author Andre Bellafronte
     * @param $invoiceId int|string ID da fatura do WHMCS
     * @return array|bool[] status da inserção na fila
     */
    public function queue($invoiceId, $force = false)
    {
        $invoiceData = \WHMCS\Billing\Invoice::find($invoiceId);
        $invoiceItems = $invoiceData->items()->get();
        $clientData = $invoiceData->client()->get();
        $userId = $clientData[0]['id'];
        $serviceCode = $this->storage->get('service_code');
        $issHeld = $this->storage->get('iss_held');
        $hasInvoices = $this->serviceInvoicesRepo->hasInvoices($invoiceId);
        $totalById = $this->serviceInvoicesRepo->getTotalById($invoiceId);

        // se já houverem notas geradas para esta fatura não faça nada
        if ($hasInvoices) {
            logModuleCall('NFEioServiceInvoices', __CLASS__ . '-' . __FUNCTION__, "Fatura: {$invoiceId}", "Já possuí NF gerada: {$hasInvoices} - {$totalById}.");
            return [
              'success' => true,
              'alreadyHasNf' => true
            ];
        }

        // percorre cada item da fatura e insere na fila de emissão
        foreach ($invoiceItems as $item) {

            // se o item for juros/mora automática do WHMCS pula a emissão de nota
            if ($item->type === 'LateFee') {
                continue;
            }

            try {
                //
                /**
                 * Gera um ID único para cada nota. O valor resultante é uma combinação simples da constante inicial WHMCS
                 * seguido do ID do usuário, ID da fatura e ID do item da fatura.
                 * Nesta lógica cada item faturado possuirá um ID único mas totalmente reproduzível novamente para verificação
                 * ou validação e evitando que seja inserido na fila de emissão itens que porventura já tenham sido faturados
                 * ou gerados.
                 * Estrutura: WHMCS-[USER_ID]-[INVOICE_ID]-[ITEM_ID]
                 * Exemplo: WHMCS-12-113-605
                 */
                $uniqueExternalId = 'WHMCS-' . $userId . '-' . $invoiceId . '-' . $item->id;
                /**
                 * verifica se existe um external_id igual
                 */
                $hasExternalId = Capsule::table($this->serviceInvoicesTable)->where('nfe_external_id', '=', $uniqueExternalId)->first();

                // se já houver uma nota no banco local com o mesmo external_id pula a emissão de nota
                if ( is_array($hasExternalId) ) {
                    continue;
                }

                if ($item->relid != 0 AND $item->type != 'Item') {
                    $customServiceCode = $this->productCodeRepo->getServiceCodeByRelId($item->relid);
                    if ($customServiceCode) {
                        $serviceCode = $customServiceCode;
                    }
                }

                $serviceDescription = \NFEioServiceInvoices\Helpers\Invoices::generateNfServiceDescription($invoiceId, $item->description);

                $data = [
                    'invoice_id' => $invoiceId,
                    'user_id' => $userId,
                    'nfe_id' => 'waiting',
                    'nfe_external_id' => $uniqueExternalId,
                    'status' => 'Waiting',
                    'services_amount' => $item->amount,
                    'nfe_description' => $serviceDescription,
                    'environment' => 'waiting',
                    'flow_status' => 'waiting',
                    'pdf' => 'waiting',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => 'waiting',
                    'rpsSerialNumber' => 'waiting',
                    'service_code' => $serviceCode,
                ];

                // verifica se há calculo de retenção de ISS personalizado
                $customIssHeld = $this->productCodeRepo->getIssHeldByRelId($item->relid);

                /**
                 * se não houver retenção personalizada e houver retenção global diferente de zero, usa valor global
                 * para cálculo.
                 */
                if (empty($customIssHeld) AND (!empty($issHeld) AND $issHeld != 0) ) {
                    $data['iss_held'] = \NFEioServiceInvoices\Helpers\Invoices::getIssHeldAmount($item->amount, $issHeld);
                }

                /**
                 * se houver retenção personalizada e for diferente de zero, usa valor personalizado para cálculo.
                 */
                if (!empty($customIssHeld) AND $customIssHeld != 0) {
                    $data['iss_held'] = \NFEioServiceInvoices\Helpers\Invoices::getIssHeldAmount($item->amount, $customIssHeld);
                }

                $result = Capsule::table($this->serviceInvoicesTable)->insert($data);

                logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, $data, $result);

            } catch (\Exception $exception) {
                logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, '', $exception->getMessage());

                return ['success' => false, 'message' => $exception->getMessage()];

            }
        }

        return ['success' => true];

    }

    public function emit( $data )
    {

        $nfDbId = $data->id;
        $invoiceId = $data->invoice_id;
        $clientId = $data->user_id;
        $externalId = $data->nfe_external_id;
        $amount = $data->services_amount;
        $serviceCode = $data->service_code;
        $issAmountWithheld = $data->iss_held;
        $description = $data->nfe_description;
        $environment = $data->environment;

        $clientData = localAPI('GetClientsDetails', ['clientid' => $clientId]);
        $customer = $this->legacyFunctions->gnfe_customer($clientId, $clientData);

        $emailNfeConfig = (bool) $this->storage->get('gnfe_email_nfe_config');
        $client_email = $emailNfeConfig ? $clientData['email'] : '';

        if ($customer['doc_type'] == 2) {
            if ($clientData['companyname'] != '') {
                $name = $clientData['companyname'];
            } else {
                $name = $clientData['fullname'];
            }
        } elseif ($customer['doc_type'] == 1 || 'CPF e/ou CNPJ ausente.' == $customer || !$customer['doc_type']) {
            $name = $clientData['fullname'];
        }
        $name = htmlspecialchars_decode($name);

        //define address
        if (strpos($clientData['address1'], ',')) {
            $array_adress = explode(',', $clientData['address1']);
            $street = $array_adress[0];
            $number = $array_adress[1];
        } else {
            $street = str_replace(',', '', preg_replace('/[0-9]+/i', '', $clientData['address1']));
            $number = preg_replace('/[^0-9]/', '', $clientData['address1']);
        }

        $ibgeCode = $this->legacyFunctions->gnfe_ibge(preg_replace('/[^0-9]/', '', $clientData['postcode']));

        if ($ibgeCode == 'ERROR') {
            $this->legacyFunctions->update_status_nfe($invoiceId, 'Error_cep');
        } else {

            //strlen($insc_municipal) == 0 ? '' : $postfields['borrower']['municipalTaxNumber'] = $insc_municipal;

            $postData = [
                'cityServiceCode' => $serviceCode,
                'description' => $description,
                'servicesAmount' => $amount,
                'externalId' => $externalId,
                'borrower' => [
                    'federalTaxNumber' => $customer['document'],
                    'municipalTaxNumber' => $customer['insc_municipal'],
                    'name' => $name,
                    'email' => $client_email,
                    'address' => [
                        'country' => $this->legacyFunctions->gnfe_country_code($clientData['countrycode']),
                        'postalCode' => preg_replace('/[^0-9]/', '', $clientData['postcode']),
                        'street' => $street,
                        'number' => $number,
                        'additionalInformation' => '',
                        'district' => $clientData['address2'],
                        'city' => [
                            'code' => $ibgeCode,
                            'name' => $clientData['city'],
                        ],
                        'state' => $clientData['state']
                    ]
                ]
            ];

            // adiciona o campo issAmountWithheld caso exista valor
            if (!empty($issAmountWithheld)) {
                $postData['issAmountWithheld'] = $issAmountWithheld;
            }

            $nfeResponse = $this->legacyFunctions->gnfe_issue_nfe($postData);

            if (!$nfeResponse->message) {
                $gnfe_update_nfe = $this->legacyFunctions->gnfe_update_nfe($nfeResponse, $clientId, $invoiceId, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $nfDbId);
                logModuleCall('gofas_nfeio', 'sendNFE', $postData, $nfeResponse, 'OK', '');
            } else {
                logModuleCall('gofas_nfeio', 'sendNFE', $postData, $nfeResponse, 'ERROR', '');

            }

        }

    }
}