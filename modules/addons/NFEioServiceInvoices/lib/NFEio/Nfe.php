<?php

namespace NFEioServiceInvoices\NFEio;

use WHMCS\Database\Capsule;

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

    public function companiesDropDownValues()
    {
        $companiesData = $this->getCompanies();

        if (!empty($companiesData->message) or empty($companiesData) or empty($companiesData['companies'])) {
            return false;
        }
        $companies = [];
        foreach ($companiesData['companies'] as $company) {
            $companies[$company->id] = $company->id . ' - ' . strtoupper($company->name);
        }

        return $companies;
    }

    /**
     * Retorna todas as empresas cadastradas para a chave API configurada.
     *
     * @see     https://nfe.io/docs/desenvolvedores/rest-api/nota-fiscal-de-servico-v1/#/Companies/Companies_Get
     * @version 2.2
     * @author  Andre Bellafronte <andre[@]eunarede[.]com>
     * @return  array|bool coleção com todas as empresas cadastradas na API.
     */
    public function getCompanies()
    {
        $apiKey = $this->storage->get('api_key');
        if (is_null($apiKey)) {
            return false;
        }

        \NFe_io::setApiKey($apiKey);
        $companies = \NFe_Company::search();

        return $companies->getAttributes();
    }

    /**
     * Prepara o item para ser transmitido
     *
     * @param  $userId      int ID do cliente
     * @param  $invoiceId   int ID da fatura
     * @param  $serviceCode string Código do serviço
     * @param  $item        object Item da fatura
     * @return array item preparado para transmissão
     */
    private function prepareItemsToTransmit($userId, $invoiceId, $serviceCode, $item)
    {

        // se descontos em itens estiver desabilitado e valor do item for igual ou menor a zero, retorna nada
        if ($this->storage->get('discount_items') != 'on' and floatval($item->amount) <= 0) {
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
     *
     * @param  $itemType string tipo do item
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

    /**
     * @param  $items
     * @param  $invoiceId
     * @param  $userId
     * @param  $reissue
     * @return array
     */
    private function buildItemsToTransmit($items, $invoiceId, $userId, $reissue = false)
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
            $nfData['nfe_external_id'] = $this->generateUniqueExternalId($userId, $invoiceId, $itemsTotal, $reissue);

            // verifica se há calculo de retenção de ISS personalizado
            $customIssHeld = $this->aliquotsRepo->getIssHeldByServiceCode($serviceCode);

            /**
             * se não houver retenção personalizada e houver retenção global diferente de zero, usa valor global
             * para cálculo.
             */
            if (is_null($customIssHeld) and $issHeld != 0) {
                $nfData['iss_held'] = \NFEioServiceInvoices\Helpers\Invoices::getIssHeldAmount($itemsTotal, $issHeld);
            }

            /**
             * se houver retenção personalizada e for diferente de zero, usa valor personalizado para cálculo.
             */
            if (!is_null($customIssHeld) and $customIssHeld != 0) {
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
     *
     * @param  $userId
     * @param  $invoiceId
     * @param  $itemsTotal
     * @return string
     */
    private function generateUniqueExternalId($userId, $invoiceId, $itemsTotal, $reissue = false)
    {
        $separator = '-';
        $prefix = 'WHMCS';

        // se o ID  a ser gerado for para uma reemissão da NF, retorna um padrão diferente
        // para não conflitar com qualquer ID já existente
        if ($reissue) {
            $suffix = 'REISSUE';
            // usa um timestamp para tornar cada reemissão unica para a criação do ID
            $dateTimeNow = date('Y-m-d H:i:s');
            $result = md5($prefix . $separator . $userId . $separator . $invoiceId . $separator . $itemsTotal . $separator . $suffix . $separator . $dateTimeNow);
        } else {
            $result = md5($prefix . $separator . $userId . $separator . $invoiceId . $separator . $itemsTotal);
        }

        return $result;
    }



    /**
     * Cria a nota fiscal com base na fatura e insere na fila (tabela) para emissão.
     *
     * @version 2.1.0
     * @author  Andre Bellafronte
     * @param   $invoiceId int|string ID da fatura do WHMCS
     * @param   $reissue   boolean informe 'true' quando for
     *                     reemissão
     * @return  array|bool[] status da inserção na fila
     */
    public function queue($invoiceId, $reissue = false)
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
            if ($item->relid != 0 and $this->allowedItemType($item->type)) {
                $customServiceCode = $this->productCodeRepo->getServiceCodeByRelId($item->relid);
                if ($customServiceCode) {
                    $serviceCode = $customServiceCode;
                }
            }

            // prepara o item e o adiciona em um array associativo com o código do serviço
            $itemsByServiceCode[$serviceCode][] = $this->prepareItemsToTransmit($userId, $invoiceId, $serviceCode, $item);
        }

        $nfToEmit = $this->buildItemsToTransmit($itemsByServiceCode, $invoiceId, $userId, $reissue);

        if (count($nfToEmit) > 0) {
            foreach ($nfToEmit as $nf) {
                /**
                 * verifica se existe um external_id igual
                 */
                $hasExternalId = Capsule::table($this->serviceInvoicesTable)->where('nfe_external_id', '=', $nf['nfe_external_id'])->first();

                // se já houver uma nota no banco local com o mesmo external_id pula a emissão de nota
                if (is_array($hasExternalId)) {
                    logModuleCall('nfeio_serviceinvoices', 'nf_queue', "Um external_id idêntico foi encontrado para {$nf['nfe_external_id']}, NF não adicionada para transmissão", $hasExternalId);
                    continue;
                }

                $result = Capsule::table($this->serviceInvoicesTable)->insert($nf);
                logModuleCall('nfeio_serviceinvoices', 'nf_queue', $nf, $result);
            }
        }

        return ['success' => true];
    }

    public function emit($data)
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
        $clientData = \WHMCS\User\Client::find($clientId);
        $customer = $this->legacyFunctions->gnfe_customer($clientId, $clientData);
        $emailNfeConfig = (bool) $this->storage->get('gnfe_email_nfe_config');
        $client_email = $emailNfeConfig ? $clientData->email : '';

        logModuleCall('nfeio_serviceinvoices', 'nf_emit_for_customer', $data, $customer);

        // se dados do cliente retornarem erro, atualiza status da NF e para emissao
        if ($customer['error']) {
            $this->legacyFunctions->update_status_nfe($invoiceId, 'Doc_Error');
            logModuleCall('nfeio_serviceinvoices', 'nf_emit_error', $data, $customer);
            return;
        }


//        if ($customer['doc_type'] == 2) {
//            if ($clientData->companyname != '') {
//                $name = $clientData->companyname;
//            } else {
//                $name = $clientData->fullname;
//            }
//        } elseif ($customer['doc_type'] == 1 || 'CPF e/ou CNPJ ausente.' == $customer || !$customer['doc_type']) {
//            $name = $clientData->fullname;
//        }
        $name = $customer['name'];

        //define address
        if (strpos($clientData->address1, ',')) {
            $array_adress = explode(',', $clientData->address1);
            $street = $array_adress[0];
            $number = $array_adress[1];
        } else {
            $street = str_replace(',', '', preg_replace('/[0-9]+/i', '', $clientData->address1));
            $number = preg_replace('/[^0-9]/', '', $clientData->address1);
        }

        if (empty($clientData->postcode)) {
            $this->legacyFunctions->update_status_nfe($invoiceId, 'Error_cep');
            return;
        }

        $ibgeCode = $this->legacyFunctions->gnfe_ibge(preg_replace('/[^0-9]/', '', $clientData->postcode));

        if ($ibgeCode['error']) {
            $this->legacyFunctions->update_status_nfe($invoiceId, 'Error_cep');
            return;
        }

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
                    'country' => $this->legacyFunctions->gnfe_country_code($clientData->country),
                    'postalCode' => preg_replace('/[^0-9]/', '', $clientData->postcode),
                    'street' => $street,
                    'number' => $number,
                    'additionalInformation' => '',
                    'district' => $clientData->address2,
                    'city' => [
                        'code' => $ibgeCode['code'],
                        'name' => $clientData->city,
                    ],
                    'state' => $clientData->state
                ]
            ]
        ];

        // adiciona o campo issAmountWithheld caso exista valor
        if (!empty($issAmountWithheld)) {
            $postData['issAmountWithheld'] = $issAmountWithheld;
        }

        $nfeResponse = $this->legacyFunctions->gnfe_issue_nfe($postData);

        if (!$nfeResponse->message) {
            $this->legacyFunctions->gnfe_update_nfe($nfeResponse, $clientId, $invoiceId, 'n/a', $nfDbId);
            logModuleCall('nfeio_serviceinvoices', 'nf_emit', $postData, $nfeResponse);
        } else {
            logModuleCall('nfeio_serviceinvoices', 'nf_emit_error', $postData, $nfeResponse);
        }
    }

    /**
     * Atualiza o status de uma NF no banco local
     *
     * @param   $nfRemoteId string ID remoto da NF (nfe_id)
     * @param   $status     string Status da NF
     * @param   $flowStatus string|null Status de fluxo da NF
     * @return  bool status da operação
     * @version 2.1.2
     */
    public function updateLocalNfeStatus(string $nfRemoteId, string $status, string $flowStatus = null): bool
    {
        $result = $this->serviceInvoicesRepo->updateNfStatusByNfeId($nfRemoteId, $status, $flowStatus);
        // caso sucesso registra log
        if ($result) {
            logModuleCall('nfeio_serviceinvoices', 'updateLocalNfeStatus', ['nfe_id' => $nfRemoteId, 'status' => $status, 'flow_status' => $flowStatus], $result);

        }

        return $result;
    }

    /**
     * Atualiza o status de uma NF no banco local pelo externalId
     *
     * @param $externalId string ID externo da NF (API externalId)
     * @param $status string O novo status da NF
     * @param $flowStatus string|null O novo status de fluxo da NF
     * @return bool status da operação
     */
    public function updateLocalNfeStatusByExternalId(string $externalId, string $status, string $flowStatus = null): bool
    {
        $result = $this->serviceInvoicesRepo->updateNfStatusByExternalId($externalId, $status, $flowStatus);
        // caso sucesso registra log
        if($result) {
            logModuleCall('nfeio_serviceinvoices', 'updateLocalNfeStatusByExternalId', ['nfe_external_id' => $externalId, 'status' => $status, 'flow_status' => $flowStatus], $result);
        }

        return $result;
    }

    /**
     * Reemite uma nota com base no ID original copiando os dados já existentes.
     *
     * @param   $nfId string ID da nota na NFE.io (nfe_id)
     * @return  string retorna sucesso ou mensagem de erro
     * @version v2.1
     */
    public function reissueNfbyId($nfId)
    {
        $_tableName = $this->serviceInvoicesRepo->tableName();
        $nfData = Capsule::table($_tableName)->where('nfe_id', '=', $nfId)->first();
        $userId = $nfData->user_id;
        $invoiceId = $nfData->invoice_id;
        $amount = $nfData->services_amount;
        $issHeld = $nfData->iss_held;
        $nfDescription = $nfData->nfe_description;
        $environment = $nfData->environment;
        $issueNoteConditions = $nfData->issue_note_conditions;
        $serviceCode = $nfData->service_code;
        $tics = $nfData->tics;
        $dateNow = date('Y-m-d H:i:s');
        // gera um novo ID externo unico para a reemissão do item/NF
        $externalUniqueId = $this->generateUniqueExternalId($userId, $invoiceId, $amount, true);

        $reissueNfData = [
            'invoice_id' => $invoiceId,
            'user_id' => $userId,
            'nfe_id' => 'waiting',
            'nfe_external_id' => $externalUniqueId,
            'status' => 'Waiting',
            'services_amount' => $amount,
            'iss_held' => $issHeld,
            'nfe_description' => $nfDescription,
            'environment' => $environment,
            'issue_note_conditions' => $issueNoteConditions,
            'flow_status' => 'waiting',
            'pdf' => 'waiting',
            'rpsSerialNumber' => 'waiting',
            'created_at' => $dateNow,
            'updated_at' => 'waiting',
            'service_code' => $serviceCode,
            'tics' => ' ',
        ];

        try {
            $result = Capsule::table($_tableName)->insert($reissueNfData);
            logModuleCall('nfeio_serviceinvoices', 'nf_reissue_series_by_nf', $reissueNfData, $result);
            return 'success';
        } catch (\Exception $e) {
            logModuleCall('nfeio_serviceinvoices', 'nf_reissue_series_by_nf_error', $reissueNfData, $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Realiza a reemissão da(s) nota(s) com base no ID da fatura.
     *
     * @version 2.1
     * @author  Andre Bellafronte <andre@eunarede.com>
     * @param   $invoiceId integer ID da fatura
     * @return  string[] status do resultado
     */
    public function reissueNfSeriesByInvoiceId($invoiceId)
    {

        // verifica se fatura existe
        $hasInvoice = \NFEioServiceInvoices\Helpers\Invoices::hasInvoice($invoiceId);
        if (!$hasInvoice) {
            return ['status' => 'error', 'message' => "Fatura #{$invoiceId} não localizada no WHMCS."];
        }

        // verifica se todas as notas já existentes para a fatura estão canceladas para permitir a reemissão da série
        $hasAllCancelled = $this->hasAllNfCancelled($invoiceId);
        if (!$hasAllCancelled) {
            return ['status' => 'error', 'message' => "Impossível reemitir fatura #{$invoiceId}: ainda existem notas que não foram canceladas para a mesma."];
        }

        $result = $this->queue($invoiceId, true);

        logModuleCall('nfeio_serviceinvoices', 'nf_reissue_series_by_invoice', $invoiceId, $result);

        return ['status' => 'success'];
    }

    /**
     * Verifica se a fatura informada possui todas as notas vinculadas com mesmo status 'Cancelled'.
     * Isso previne que notas sejam reemitidas se anterior não estiver cancelada.
     *
     * @version 2.1
     * @author  Andre Bellafronte <andre@eunarede.com>
     * @param   $invoiceId integer ID da fatura a ser verificado
     * @return  bool retorna `true` somente quando todas as notas existentes para a fatura possuírem status 'Cancelled'.
     */
    public function hasAllNfCancelled($invoiceId)
    {
        $status = [];
        $query = Capsule::table($this->serviceInvoicesTable)->where('invoice_id', $invoiceId)->distinct()->pluck('status');

        foreach ($query as $value) {
            $status[] = $value;
        }

        if (count($status) == 1 and in_array('Cancelled', $status)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cancela as notas fiscais existentes para uma fatura.
     *
     * @version 2.1
     * @author  Andre Bellafronte <andre@eunarede.com>
     * @param   $invoiceId integer ID da fatura
     * @return  string[] status e mensagem do resultado da operação.
     */
    public function cancelNfSeriesByInvoiceId($invoiceId)
    {
        $existingNf = Capsule::table($this->serviceInvoicesTable)->where('invoice_id', $invoiceId)->get();

        if (count($existingNf) > 0) {
            foreach ($existingNf as $nf) {
                $result = $this->legacyFunctions->gnfe_delete_nfe($nf->nfe_id);
                logModuleCall('nfeio_serviceinvoices', 'nf_cancel_series_by_invoice', $nf, $result);

                /*
                 API retorna 202 e nf no corpo quando nota em fila de cancelamento (WaitingSendCancel)
                 $message nao faz mais parte do objeto de resposta em qualquer outro status,
                 agora quando uma nota diferente de 'Issued' é cancelada, o corpo da resposta é
                 uma string informando o motivo. Ex.: 'service invoice status is 'Cancelled' but must be 'Issued''.
                */

                // caso retorne objeto, respeita os status que a API retorna
                if ($result->flowStatus && $result->status) {
                    $this->updateLocalNfeStatus($nf->nfe_id, $result->status, $result->flowStatus);
                } else {
                  // caso nao tenha objeto, forca cancelamento local com status flow personalizado
                    $this->updateLocalNfeStatus($nf->nfe_id, 'Cancelled', 'ApiNoResponse');
                }
            }
            return ['status' => 'success'];
        } else {
            logModuleCall(
                'nfeio_serviceinvoices',
                'nf_cancel_series_by_invoice',
                ['invoice ID' => $invoiceId],
                "Não existem notas para a fatura #{$invoiceId}."
            );
            return ['status' => 'info', 'message' => "Não existem notas para a fatura #{$invoiceId}."];
        }
    }

    public function fetchNf($nfId)
    {
        $apiKey = $this->storage->get('api_key');
        $companyId = $this->storage->get('company_id');

        try {
            \NFe_io::setApiKey($apiKey);
            $invoice = \NFe_ServiceInvoice::fetch($companyId, $nfId);
            return $invoice;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

    }
}
