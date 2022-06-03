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
    /**
     * @var \NFEioServiceInvoices\Models\Aliquots\Repository
     */
    private $aliquotsRepo;


    public function __construct()
    {
        $this->productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->serviceInvoicesTable = $this->serviceInvoicesRepo->tableName();
        $this->moduleConfig = new \NFEioServiceInvoices\Configuration();
        $this->storage = new \WHMCSExpert\Addon\Storage($this->moduleConfig->getStorageKey());
        $this->legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
        $this->aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
    }

    /**
     * Prepara o item para ser transmitido
     *
     * @param $userId int ID do cliente
     * @param $invoiceId int ID da fatura
     * @param $serviceCode string Código do serviço
     * @param $item object Item da fatura
     * @return array item preparado para transmissão
     */
    private function prepareItemsToTransmit($userId, $invoiceId, $serviceCode, $item)
    {

        // se descontos em itens estiver desabilitado e valor do item for igual ou menor a zero, retorna nada
        if ($this->storage->get('discount_items') != 'on' AND floatval($item->amount) <= 0) {
            return array();
        }

        return array(
            'userId' => $userId,
            'invoiceId' => $invoiceId,
            'itemId' => $item->id,
            'itemRelId' => $item->relid,
            'itemType' => $item->type,
            'itemDescription' => $item->description,
            'itemAmount' => floatval($item->amount),
            'itemServiceCode' => $serviceCode,
        );


    }

    /**
     * Verifica se o tipo de item informado é um dos tipos permitidos a terem um código de serviço personalizado.
     * Atualmente os códigos de serviços personalizados são configurados apenas para produtos/serviços, setup
     * e códigos promocionais (pois possuem relid do produto pai). Itens como dominios, addons e etc não possuem
     * código de serviço personalizado.
     * @param $itemType string tipo do item
     * @return bool true caso permitido, false não permitido
     */
    private function allowedItemType($itemType)
    {
        $allowed = false;

        switch ($itemType) {
            case 'Setup':
            case 'Hosting':
            case 'PromoHosting':
                $allowed = true;
                break;
        }

        return $allowed;
    }

    private function buildItemsToTransmit($items, $invoiceId, $userId)
    {
        // ISS padrão
        $issHeld = floatval($this->storage->get('iss_held'));
        $result = [];

        // percorre $items para construir os itens a serem emitidos
        foreach ($items as $serviceCode => $item) {

            // é possível que item tenha coleções vazias devido a remoção de itens de desconto
            // então é necessário limpar a coleção dos elementos vazios
            array_filter($item);
            $itemsDescription = '';
            $itemsTotal = 0;
            $nfData = [
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'nfe_id' => 'waiting',
                'status' => 'Waiting',
                'environment' => 'waiting',
                'flow_status' => 'waiting',
                'pdf' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => 'waiting',
                'rpsSerialNumber' => 'waiting',
                'service_code' => $serviceCode,
            ];


            // percorre cada item para realizar as agregações e somatórias
            foreach ($item as $value) {
                // concatena todas as descrições dos itens
                $itemsDescription = $itemsDescription . $value['itemDescription'] . "\n";
                // soma os valores de cada item para o total da nota
                $itemsTotal = $itemsTotal + $value['itemAmount'];
            }
            // adiciona a descrição da nota no formato parametrizado
            $nfData['nfe_description'] = \NFEioServiceInvoices\Helpers\Invoices::generateNfServiceDescription($invoiceId, $itemsDescription);
            // adiciona o valor total calculado para os itens
            $nfData['services_amount'] = $itemsTotal;
            // gera id unico externo
            $nfData['nfe_external_id'] = $this->generateUniqueExternalId($userId, $invoiceId, $itemsTotal);

            // verifica se há calculo de retenção de ISS personalizado
            $customIssHeld = $this->aliquotsRepo->getIssHeldByServiceCode($serviceCode);

            /**
             * se não houver retenção personalizada e houver retenção global diferente de zero, usa valor global
             * para cálculo.
             */
            if (is_null($customIssHeld) AND $issHeld != 0) {
                $nfData['iss_held'] = \NFEioServiceInvoices\Helpers\Invoices::getIssHeldAmount($itemsTotal, $issHeld);
            }

            /**
             * se houver retenção personalizada e for diferente de zero, usa valor personalizado para cálculo.
             */
            if (!is_null($customIssHeld) AND $customIssHeld != 0) {
                $nfData['iss_held'] = \NFEioServiceInvoices\Helpers\Invoices::getIssHeldAmount($itemsTotal, $customIssHeld);
            }
            // se valor total dos itens for maior que zero adiciona as informações para retorno
            if ($itemsTotal > 0) {
                $result[] = $nfData;
            }

        }

        return $result;

    }

    /**
     * Gera um ID único para cada nota. O valor resultante é o md5 da combinação da constante inicial WHMCS
     * seguido do ID do usuário, ID da fatura e total dos itens.
     * Nesta lógica cada conjunto de itens faturado possuirá um ID único evitando que seja inserido na fila
     * de emissão itens que porventura já tenham sido transmitidos ou gerados.
     * Estrutura: WHMCS-[USER_ID]-[INVOICE_ID]-[TOTAL]
     * Exemplo: WHMCS-13-113-131
     * Resultado: número hexadecimal de 32 caracteres
     * @param $userId
     * @param $invoiceId
     * @param $itemsTotal
     * @return string
     */
    private function generateUniqueExternalId($userId, $invoiceId, $itemsTotal)
    {
        return md5('WHMCS-' . $userId . '-' . $invoiceId . '-' . $itemsTotal);
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
        $defaultServiceCode = $this->storage->get('service_code');
        $itemsByServiceCode = [];

        // percorre cada item da fatura para preparar as agregações de items por tipo de serviço
        foreach ($invoiceItems as $item) {

            // código do serviço recebe o valor padrão
            $serviceCode = $defaultServiceCode;

            // se o item for juros/mora automática do WHMCS, não considera para fins de cálculo de nota
            if ($item->type === 'LateFee') {
                continue;
            }

            // se o item tiver um 'relid' e seu tipo for uns dos permitidos verifica se tem código personalizado
            if ($item->relid != 0 AND $this->allowedItemType($item->type)) {
                $customServiceCode = $this->productCodeRepo->getServiceCodeByRelId($item->relid);
                if ($customServiceCode) {
                    $serviceCode = $customServiceCode;
                }
            }

            // prepara o item e o adiciona em um array associativo com o código do serviço
            $itemsByServiceCode[$serviceCode][] = $this->prepareItemsToTransmit($userId, $invoiceId, $serviceCode, $item);


        }

        $nfToEmit = $this->buildItemsToTransmit($itemsByServiceCode, $invoiceId, $userId);

        if (count($nfToEmit) > 0) {
            foreach ($nfToEmit as $nf) {
                /**
                 * verifica se existe um external_id igual
                 */
                $hasExternalId = Capsule::table($this->serviceInvoicesTable)->where('nfe_external_id', '=', $nf['nfe_external_id'])->first();

                // se já houver uma nota no banco local com o mesmo external_id pula a emissão de nota
                if ( is_array($hasExternalId) ) {
                    logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, "Um external_id idêntico foi encontrado para {$nf['nfe_external_id']}, NF não adicionada para transmissão", $hasExternalId);
                    continue;
                }

                $result = Capsule::table($this->serviceInvoicesTable)->insert($nf);
                logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, $nf, $result);

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
                logModuleCall('NFEioServiceInvoices', 'transmit_nf_success', $postData, $nfeResponse);
            } else {
                logModuleCall('NFEioServiceInvoices', 'transmit_nf_error', $postData, $nfeResponse);

            }

        }

    }

    /**
     * Atualiza o status de uma NF no banco local
     * @param $nfRemoteId string ID remoto da NF (nfe_id)
     * @param $status string Status da NF
     * @return string 'success' para sucesso
     * @version 2.1.2
     */
    public function updateLocalNfeStatus($nfRemoteId, $status) {

        $_tableName = $this->serviceInvoicesRepo->tableName();

        try {
            Capsule::table($_tableName)->where('nfe_id', '=', $nfRemoteId)->update(['status' => $status]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return 'success';

    }
}