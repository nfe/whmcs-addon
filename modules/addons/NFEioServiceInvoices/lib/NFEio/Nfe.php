<?php

namespace NFEioServiceInvoices\NFEio;

use NFEioServiceInvoices\Helpers\Timestamp;
use NFEioServiceInvoices\Helpers\Validations;
use WHMCS\Database\Capsule;

/**
 * Classe com métodos para manipulação de notas fiscais
 *
 * @version 3.0
 * @since 2.0
 * @author Mimir Tech https://github.com/mimirtechco
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
     * Executa uma requisição cURL para a API da NFE.io.
     *
     * @param string $uri O endpoint URI a ser anexado à URL base da API.
     * @param string $method O método HTTP a ser utilizado na requisição (padrão: 'GET').
     * @param array|null $data Os dados a serem enviados no corpo da requisição (para métodos POST/PUT).
     * @param int $timeout O tempo limite para a requisição cURL em segundos (padrão: 3).
     * @return array Um array associativo contendo:
     *               - 'response': O corpo da resposta da API.
     *               - 'error': Mensagem de erro da requisição cURL, se houver.
     *               - 'info': Informações adicionais sobre a requisição cURL.
     */
    private function executeCurl($uri, $method = 'GET', $data = null, $timeout = 3)
    {
        $headers = [
            'Content-Type: text/json',
            'Accept: application/json',
            'Authorization: ' . $this->storage->get('api_key'),
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/' . $uri);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        return [
            'response' => $response,
            'error' => $error,
            'info' => $info,
        ];
    }

    /**
     * Envia um e-mail relacionado a uma nota fiscal específica na NFE.io.
     *
     * @param string $nfeioId O ID da nota fiscal na NFE.io.
     * @param string $companyId O ID da empresa associada à nota fiscal.
     * @return array Retorna a resposta da API contendo os detalhes do envio do e-mail.
     */
    public function sendNfeioEmail($nfeioId, $companyId)
    {
        $uri = 'companies/' . $companyId . '/serviceinvoices/' . $nfeioId . '/sendemail';
        $response = $this->executeCurl($uri, 'PUT');
        logModuleCall('nfeio_serviceinvoices', 'email_nfe', $nfeioId, $response);

        return $response;

    }

    private function apiAuth()
    {
        $apiKey = $this->storage->get('api_key');
        if (is_null($apiKey)) {
            return false;
        }

        \NFe_io::setApiKey($apiKey);
    }

    /**
     * Retorna uma lista de valores para um dropdown contendo as empresas cadastradas.
     *
     * @return array|bool Retorna um array associativo com o ID da empresa como chave
     *                    e o CNPJ e nome da empresa como valor, ou `false` caso não
     *                    existam empresas cadastradas ou ocorra algum erro.
     */
    public function companiesDropDownValues()
    {
        $companiesData = $this->getCompanies();

        if (!empty($companiesData->message) or empty($companiesData) or empty($companiesData['companies'])) {
            return false;
        }
        $companies = [];
        foreach ($companiesData['companies'] as $company) {
            $companies[$company->id] = $company->federalTaxNumber . ' - ' . strtoupper($company->name);
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
     * Retorna o nome da empresa conforme o ID informado.
     * @param $companyId
     * @return string
     */
    public function getCompanyName($companyId)
    {

        $this->apiAuth();
        $response = \NFe_Company::fetch($companyId);
        $company = $response->getAttributes();

        if (isset($company['companies']) && is_object($company['companies'])) {
            return strtoupper($company['companies']->name);
        }
        // se não encontrar a empresa retorna vazio
        return '';
    }

    /**
     * Retorna os detalhes de uma empresa com base no ID informado.
     *
     * @param int|string $companyId O ID da empresa a ser buscada.
     * @return object|string Retorna um objeto com os detalhes da empresa ou uma string vazia
     *                       caso a empresa não seja encontrada.
     */
    public function getCompanyDetails($companyId)
    {
        $this->apiAuth();
        $response = \NFe_Company::fetch($companyId);
        $company = $response->getAttributes();

        if (isset($company['companies']) && is_object($company['companies'])) {
            return $company['companies'];
        }
        // se não encontrar a empresa retorna vazio
        return '';
    }

    /**
     * Prepara o item para ser transmitido
     *
     * @param  $userId      int ID do cliente
     * @param  $invoiceId   int ID da fatura
     * @param  $serviceCode string Código do serviço
     * @param  $nbsCode string Código NBS
     * @param  $operationCode string Código da operação
     * @param  $classCode string Código da classificação tributária
     * @param  $item        object Item da fatura
     * @return array item preparado para transmissão
     */
    private function prepareItemsToTransmit($userId, $invoiceId, $serviceCode, $nbsCode, $operationCode, $classCode, $item)
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
            'itemNbsCode' => $nbsCode,
            'itemOperationCode' => $operationCode,
            'itemClassCode' => $classCode,
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
     * Constrói os itens a serem transmitidos para emissão de notas fiscais.
     *
     * Este método percorre os itens fornecidos, realiza agregações e somatórias,
     * e prepara os dados necessários para a transmissão das notas fiscais.
     *
     * @param array $items Coleção de itens agrupados por código de serviço.
     * @param int|string $invoiceId ID da fatura associada.
     * @param int|string $userId ID do usuário associado.
     * @param int|string $companyId ID da empresa emissora.
     * @param float $issHeldDefault Valor padrão de retenção de ISS.
     * @param string $nbsCode Código NBS padrão.
     * @param string $operationIndicator Indicador de operação padrão.
     * @param string $classCode Código de classificação tributária padrão.
     * @param bool $reissue Indica se é uma reemissão de nota fiscal.
     * @return array Retorna uma lista de itens preparados para transmissão.
     */
    private function buildItemsToTransmit(
        $items,
        $invoiceId,
        $userId,
        $companyId,
        $issHeldDefault,
        $nbsCode,
        $operationIndicator,
        $classCode,
        $reissue = false
    )
    {
        // ISS padrão
        $issHeld = $issHeldDefault;
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
                'rpsSerialNumber' => 'waiting',
                'company_id' => $companyId,
                'service_code' => $serviceCode,
                'nbs_code' => $nbsCode,
                'operation_indicator' => $operationIndicator,
                'class_code' => $classCode,
            ];


            // percorre cada item para realizar as agregações e somatórias
            foreach ($item as $value) {
                // concatena todas as descrições dos itens
                $itemsDescription = $itemsDescription . $value['itemDescription'] . "\n";
                // soma os valores de cada item para o total da nota
                $itemsTotal = $itemsTotal + $value['itemAmount'];
            }
            // adiciona a descrição da nota no formato parametrizado
            // phpcs:ignore Generic.Files.LineLength.TooLong
            $nfData['nfe_description'] = \NFEioServiceInvoices\Helpers\Invoices::generateNfServiceDescription($invoiceId, $itemsDescription);
            // adiciona o valor total calculado para os itens
            $nfData['services_amount'] = $itemsTotal;
            // gera id unico externo
            // phpcs:ignore Generic.Files.LineLength.TooLong
            $nfData['nfe_external_id'] = $this->generateUniqueExternalId($userId, $invoiceId, $itemsTotal, $companyId, $serviceCode, $reissue);

            // verifica se há calculo de retenção de ISS personalizado
            $customIssHeld = $this->aliquotsRepo->getIssHeldByServiceCode($serviceCode, $companyId);

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
            // phpcs:ignore Generic.Files.LineLength.TooLong
            if (!is_null($customIssHeld) and $customIssHeld != 0) {
                // phpcs:ignore Generic.Files.LineLength.TooLong
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
     * seguido do ID do usuário, ID da fatura, ID da empresa, Código do servico e total dos itens.
     * Nesta lógica cada conjunto de itens faturado possuirá um ID único evitando que seja inserido na fila
     * de emissão itens que porventura já tenham sido transmitidos ou gerados.
     * Estrutura: WHMCS-[USER_ID]-[INVOICE_ID]-[COMPANY_ID]-[SERVICE_CODE]-[TOTAL]
     * Exemplo: WHMCS-15-123-a15t...-0103-321
     * Resultado: número hexadecimal de 32 caracteres
     *
     * @param  $userId
     * @param  $invoiceId
     * @param  $itemsTotal
     * @return string
     */
    private function generateUniqueExternalId($userId, $invoiceId, $itemsTotal, $companyId, $serviceCode, $reissue = false)
    {
        $separator = '-';
        $prefix = 'WHMCS';

        // se o ID  a ser gerado for para uma reemissão da NF, retorna um padrão diferente
        // para não conflitar com qualquer ID já existente
        if ($reissue) {
            $suffix = 'REISSUE';
            // usa um timestamp para tornar cada reemissão unica para a criação do ID
            $dateTimeNow = date('Y-m-d H:i:s');
            $result = md5($prefix . $separator . $userId . $separator . $invoiceId . $separator . $companyId . $separator . $serviceCode . $separator . $itemsTotal . $separator . $suffix . $separator . $dateTimeNow);
        } else {
            $result = md5($prefix . $separator . $userId . $separator . $invoiceId . $separator . $companyId . $separator . $serviceCode . $separator . $itemsTotal);
        }

        return $result;
    }


    /**
     * Cria a nota fiscal com base na fatura e insere na fila (tabela) para emissão.
     *
     * @param   $invoiceId int|string ID da fatura do WHMCS
     * @param   $reissue   boolean informe 'true' quando for
     *                     reemissão
     * @return  array|bool[] status da inserção na fila
     * @author  Andre Bellafronte
     * @version 2.1.0
     */
    public function queue($invoiceId, $reissue = false)
    {
        $invoiceData = \WHMCS\Billing\Invoice::find($invoiceId);
        $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
        $clientCompanyRepository = new \NFEioServiceInvoices\Models\ClientCompany\Repository();

        $defaultCompany = $companyRepository->getDefaultCompany();
        $invoiceItems = $invoiceData->items()->get();
        $clientData = $invoiceData->client()->get();

        $clientId = $clientData[0]['id'];
        $clientCompanyId = $clientCompanyRepository->getCompanyByClientId($clientId);

        // se cliente possuir empresa associada, utiliza a empresa associada, senao usa a empresa padrão
        // #163
        if ($clientCompanyId) {
            // define a empresa emissora como a empresa associada ao cliente
            $companyId = $clientCompanyId;
            // recupera o codigo de servico padrao da empresa associada ao cliente
            $defaultServiceCode = $companyRepository->getDefaultServiceCodeByCompanyId($clientCompanyId);
            $defaultNbsCode = $companyRepository->getDefaultNbsCodeByCompanyId($clientCompanyId);
            $defaultOperationIndicator = $companyRepository->getDefaultOperationCodeByCompanyId($clientCompanyId);
            $defaultClassCode = $companyRepository->getDefaultClassCodeByCompanyId($clientCompanyId);
            // recupera o iss retencao padrao da empresa associada ao cliente
            $issHeld = $companyRepository->getDefaultIssHeldByCompanyId($clientCompanyId);
        } else {
            // dados da empresa padrao
            $companyId = $defaultCompany->company_id;
            $defaultServiceCode = $defaultCompany->service_code;
            $defaultNbsCode = $defaultCompany->nbs_code;
            $defaultOperationIndicator = $defaultCompany->operation_indicator;
            $defaultClassCode = $defaultCompany->class_code;
            $issHeld = $defaultCompany->iss_held;
        }

        // $defaultServiceCode = $this->storage->get('service_code');
        $itemsByServiceCode = [];

        // percorre cada item da fatura para preparar as agregações de items por tipo de serviço
        foreach ($invoiceItems as $item) {
            // essencial que código do serviço receba o valor padrão
            // para cada passada do laco
            $serviceCode = $defaultServiceCode;
            $nbsCode = $defaultNbsCode;
            $operationIndicator = $defaultOperationIndicator;
            $classCode = $defaultClassCode;

            // se o item for juros/mora automática do WHMCS, não considera para fins de cálculo de nota
            if ($item->type === 'LateFee') {
                continue;
            }

            // se o item tiver um 'relid' e seu tipo for uns dos permitidos verifica se tem código personalizado
            if ($item->relid != 0 and $this->allowedItemType($item->type)) {
                $customServiceCode = $this->productCodeRepo->getServiceCodeByRelId($item->relid, $companyId);
                if ($customServiceCode) {
                    $serviceCode = $customServiceCode;
                    $nbsCode = $this->productCodeRepo->getNbsCodeByRelId($item->relid, $companyId);
                    $operationIndicator = $this->productCodeRepo->getOperationCodeByRelId($item->relid, $companyId);
                    $classCode = $this->productCodeRepo->getClassCodeByRelId($item->relid, $companyId);
                }
            }

            // prepara o item e o adiciona em um array associativo com o código do serviço
            // phpcs:ignore Generic.Files.LineLength.TooLong
            $itemsByServiceCode[$serviceCode][] = $this->prepareItemsToTransmit(
                $clientId,
                $invoiceId,
                $serviceCode,
                $nbsCode,
                $operationIndicator,
                $classCode,
                $item
            );
        }

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $nfToEmit = $this->buildItemsToTransmit(
            $itemsByServiceCode,
            $invoiceId,
            $clientId,
            $companyId,
            $issHeld,
            $nbsCode,
            $operationIndicator,
            $classCode,
            $reissue
        );

        if (count($nfToEmit) > 0) {
            foreach ($nfToEmit as $nf) {
                /**
                 * verifica se existe um external_id igual
                 */
                $hasExternalId = Capsule::table($this->serviceInvoicesTable)
                    ->where('nfe_external_id', '=', $nf['nfe_external_id'])->first();

                // se já houver uma nota no banco local com o mesmo external_id pula a emissão de nota
                if (is_array($hasExternalId)) {
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    logModuleCall(
                        'nfeio_serviceinvoices',
                        'nf_queue',
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "Um external_id idêntico foi encontrado para {$nf['nfe_external_id']}, NF não adicionada para transmissão",
                        $hasExternalId
                    );
                    continue;
                }

                // timestamps
                $nf['created_at'] = Timestamp::currentTimestamp();
                $nf['updated_at'] = Timestamp::currentTimestamp();

//                $result = Capsule::table($this->serviceInvoicesTable)->insert($nf);
                $result = $this->serviceInvoicesRepo->create($nf);
                logModuleCall('nfeio_serviceinvoices', 'nf_queue', $nf, $result);
            }
        }

        return ['success' => true];
    }

    /**
     * Emite uma nota fiscal com base nos dados fornecidos.
     *
     * Este método realiza a emissão de uma nota fiscal utilizando os dados do cliente,
     * da fatura e da empresa fornecidos. Ele também valida os dados do cliente e
     * atualiza o status da nota fiscal no banco de dados local em caso de erro.
     *
     * @param object $data Objeto contendo os dados necessários para a emissão da nota fiscal:
     *                     - id: ID da nota fiscal no banco local.
     *                     - invoice_id: ID da fatura associada.
     *                     - user_id: ID do cliente.
     *                     - nfe_external_id: ID externo da nota fiscal.
     *                     - services_amount: Valor total dos serviços.
     *                     - service_code: Código do serviço.
     *                     - iss_held: Valor de ISS retido (opcional).
     *                     - company_id: ID da empresa emissora.
     *                     - nfe_description: Descrição da nota fiscal.
     *                     - environment: Ambiente de emissão (ex.: produção ou homologação).
     * @return void
     */
    public function emit($data)
    {

        $nfDbId = $data->id;
        $invoiceId = $data->invoice_id;
        $clientId = $data->user_id;
        $externalId = $data->nfe_external_id;
        $amount = $data->services_amount;
        $serviceCode = $data->service_code;
        $nbsCode = $data->nbs_code;
        $operationCode = $data->operation_indicator;
        $classCode = $data->class_code;
        $issAmountWithheld = $data->iss_held;
        $companyId = $data->company_id;
        $description = $data->nfe_description;
        $environment = $data->environment;
        $clientData = \WHMCS\User\Client::find($clientId);
        $customer = $this->legacyFunctions->gnfe_customer($clientId, $clientData);
        $emailNfeConfig = (bool)$this->storage->get('gnfe_email_nfe_config');
        $client_email = $emailNfeConfig ? $clientData->email : '';
        $client_postcode  = Validations::sanitizePostCode($clientData->postcode);

        logModuleCall('nfeio_serviceinvoices', 'nf_emit_for_customer', $data, $customer);

        // se dados do cliente retornarem erro, atualiza status da NF e para emissao
        if ($customer['error']) {
            $this->updateLocalNfeStatusByExternalId($externalId, 'Doc_Error');
            logModuleCall('nfeio_serviceinvoices', 'nf_emit_error', $data, $customer);
            return;
        }

        /**
        Esse trecho separa `address1` em **logradouro** e **número** de duas formas:
        1. Quando existe vírgula
            - Usa `strpos` para detectar vírgula
            - `explode(',', …)` divide a string em dois pedaços
        2. Quando não há vírgula
            - Remove dígitos via `preg_replace('/[0-9]+/i', '', …)` para obter o logradouro
            - Extrai apenas números com `preg_replace('/[^0-9]/', '', …)` para obter o número
         */
        if (strpos($clientData->address1, ',')) {
            $array_adress = explode(',', $clientData->address1);
            $street = $array_adress[0];
            $number = $array_adress[1];
        } else {
            $street = str_replace(',', '', preg_replace(
                '/[0-9]+/i',
                '',
                $clientData->address1
            ));
            $number = preg_replace('/[^0-9]/', '', $clientData->address1);
        }

        // se cliente não possuir um CEP válido, atualiza o status da NF e para emissão
        if (!$client_postcode) {
            $this->updateLocalNfeStatusByExternalId($externalId, 'Error_cep', 'CEP inválido');
            return;
        }

        $ibgeCode = $this->legacyFunctions->gnfe_ibge($client_postcode);

        if ($ibgeCode['error']) {
            $this->updateLocalNfeStatusByExternalId($externalId, 'Error_cep');
            return;
        }

        $postData = [
            'cityServiceCode' => $serviceCode,
            'description' => $description,
            'servicesAmount' => $amount,
            'externalId' => $externalId,
            'nbsCode' => $nbsCode,
            'borrower' => [
                'federalTaxNumber' => $customer['document'],
                'municipalTaxNumber' => $customer['insc_municipal'],
                'name' => $customer['name'],
                'email' => $client_email,
                'address' => [
                    'country' => Validations::countryCode($clientData->country),
                    'postalCode' => $client_postcode,
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
            ],
            'IbsCbs' => [
                'operationIndicator' => $operationCode,
                'classCode' => $classCode,
            ]
        ];

        // adiciona o campo issAmountWithheld caso exista valor
        if (!empty($issAmountWithheld)) {
            $postData['issAmountWithheld'] = $issAmountWithheld;
        }

        $nfeResponse = $this->legacyFunctions->gnfe_issue_nfe($postData, $companyId);

        if (!$nfeResponse->message) {
            $this->serviceInvoicesRepo->updateServiceInvoice($externalId, $nfeResponse);
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
            logModuleCall(
                'nfeio_serviceinvoices',
                'updateLocalNfeStatus',
                ['nfe_id' => $nfRemoteId, 'status' => $status, 'flow_status' => $flowStatus],
                $result
            );
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
    // phpcs:ignore Generic.Files.LineLength.TooLong
    public function updateLocalNfeStatusByExternalId(string $externalId, string $status, string $flowStatus = null): bool
    {
        $result = $this->serviceInvoicesRepo->updateNfStatusByExternalId($externalId, $status, $flowStatus);
        // caso sucesso registra log
        if ($result) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'updateLocalNfeStatusByExternalId',
                ['nfe_external_id' => $externalId, 'status' => $status, 'flow_status' => $flowStatus],
                $result
            );
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
        $companyId = $nfData->company_id;
        $tics = $nfData->tics;
        $dateNow = date('Y-m-d H:i:s');
        // gera um novo ID externo unico para a reemissão do item/NF
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $externalUniqueId = $this->generateUniqueExternalId($userId, $invoiceId, $amount, $companyId, $serviceCode, true);

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
            'company_id' => $companyId,
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
     * @param   $invoiceId integer ID da fatura
     * @return  string[] status do resultado
     * @version 2.1
     * @author  Andre Bellafronte <andre@eunarede.com>
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
        // phpcs:ignore Generic.Files.LineLength.TooLong
        if (!$hasAllCancelled) {
            return [
                'status' => 'error',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'message' => "Impossível reemitir fatura #{$invoiceId}: ainda existem notas que não foram canceladas para a mesma."];
        }

        $result = $this->queue($invoiceId, true);

        logModuleCall('nfeio_serviceinvoices', 'nf_reissue_series_by_invoice', $invoiceId, $result);

        return ['status' => 'success'];
    }

    /**
     * Verifica se a fatura informada possui todas as notas vinculadas com status que permite reemissão.
     * Isso previne que notas sejam reemitidas se anterior não estiver cancelada ou com falha no cancelamento.
     *
     * @param   $invoiceId integer ID da fatura a ser verificado
     * @return  bool retorna `true` quando todas as notas existentes para a fatura possuírem status 'Cancelled' ou 'CancelFailed'.
     * @version 3.2
     * @author  Andre Bellafronte <andre@eunarede.com>
     */
    public function hasAllNfCancelled($invoiceId)
    {
        $allowedStatuses = ['Cancelled', 'CancelFailed'];
        $query = Capsule::table($this->serviceInvoicesTable)->where('invoice_id', $invoiceId)
            ->distinct()->pluck('status');

        foreach ($query as $status) {
            if (!in_array($status, $allowedStatuses)) {
                return false;
            }
        }

        return count($query) > 0;
    }

    /**
     * Cancela as notas fiscais existentes para uma fatura.
     *
     * @param   $invoiceId integer ID da fatura
     * @return  string[] status e mensagem do resultado da operação.
     * @version 2.1
     * @author  Andre Bellafronte <andre@eunarede.com>
     */
    public function cancelNfSeriesByInvoiceId($invoiceId)
    {
        $existingNf = Capsule::table($this->serviceInvoicesTable)->where('invoice_id', $invoiceId)->get();

        if (count($existingNf) > 0) {

            $this->apiAuth();

            $successCount = 0;
            $failures = [];

            foreach ($existingNf as $nf) {
                try {
                    $invoice = \NFe_ServiceInvoice::fetch($nf->company_id, $nf->nfe_id);
                    $invoice->cancel();
                    $this->updateLocalNfeStatus($nf->nfe_id, 'Cancelled', 'ApiNoResponse');
                    $successCount++;

                    logModuleCall('nfeio_serviceinvoices', 'nf_cancel_series_by_invoice', $nf, $invoice);
                } catch (\Exception $e) {
                    // Check if it's a NFeObjectNotFound error (invoice not found in API)
                    $isNotFound = strpos($e->getMessage(), 'NFeObjectNotFound') !== false
                        || strpos($e->getMessage(), 'não encontrado') !== false;

                    // Determine the appropriate flow_status message
                    $flowStatusMsg = $isNotFound
                        ? 'Nota não encontrada na API.'
                        : $e->getMessage();

                    $failures[] = [
                        'nfe_id' => $nf->nfe_id,
                        'company_id' => $nf->company_id,
                        'invoice_id' => $invoiceId,
                        'error' => $e->getMessage(),
                        'is_not_found' => $isNotFound,
                    ];

                    // Update local status to 'CancelFailed' to indicate cancellation failure visually
                    $this->updateLocalNfeStatus($nf->nfe_id, 'CancelFailed', $flowStatusMsg);

                    logModuleCall(
                        'nfeio_serviceinvoices',
                        'nf_cancel_error',
                        [
                            'invoice_id' => $invoiceId,
                            'nfe_id' => $nf->nfe_id,
                            'company_id' => $nf->company_id,
                        ],
                        ['error' => $e->getMessage()]
                    );
                }
            }

            // Determine response status based on results
            $totalNfs = count($existingNf);
            if ($successCount === $totalNfs) {
                return ['status' => 'success'];
            } elseif ($successCount > 0) {
                return [
                    'status' => 'partial',
                    'message' => "Cancelamento parcial: {$successCount} de {$totalNfs} nota(s) cancelada(s).",
                    'failures' => $failures,
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => "Não foi possível cancelar as notas fiscais.",
                    'failures' => $failures,
                ];
            }
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

    /**
     * Busca os detalhes de uma nota fiscal específica na API NFE.io.
     *
     * @param string $nfId O ID da nota fiscal a ser buscada.
     * @param string $companyId O ID da empresa associada à nota fiscal.
     * @return object|array Retorna o objeto da nota fiscal ou um array com a mensagem de erro em caso de falha.
     */
    public function fetchNf($nfId, $companyId)
    {
        $apiKey = $this->storage->get('api_key');

        try {
            \NFe_io::setApiKey($apiKey);
            $invoice = \NFe_ServiceInvoice::fetch($companyId, $nfId);
            return $invoice;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Cria um webhook na NFE.io para receber eventos específicos.
     *
     * @param string $url URL que receberá as notificações do webhook.
     * @return \NFe_Webhook|array Retorna a instância do webhook criado ou um array com chave 'error' em caso de falha.
     * @throws \Exception
     */
    public function createWebhook($url)
    {
        $this->apiAuth();
        $data = [
            'url' => $url,
            'contentType' => 'application/json',
            'secret' => Validations::generateSecretKey(),
            'events' => ['issue', 'cancel', 'WaitingCalculateTaxes'],
            'status' => 'active',
        ];

        try {
            $hook = \NFe_Webhook::create($data);
            logModuleCall('nfeio_serviceinvoices', 'create_webhook', $data, $hook);
            return $hook;
        } catch (\Exception $exception) {
            logModuleCall('nfeio_serviceinvoices', 'create_webhook_error', $data, $exception->getMessage());
            return ['error' => $exception->getMessage()];
        }
    }

    /**
     * Recupera um webhook existente na NFE.io.
     *
     * @param string $webhookId ID do webhook a ser buscado.
     * @return \NFe_Webhook|array Retorna a instância do webhook ou um array com chave 'error' em caso de falha.
     */
    public function getWebhook(string $webhookId)
    {
        $this->apiAuth();

        try {
            $webhook = \NFe_Webhook::fetch($webhookId);
            logModuleCall('nfeio_serviceinvoices', 'get_webhook', $webhookId, $webhook);
            return $webhook;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            logModuleCall('nfeio_serviceinvoices', 'get_webhook_error', $webhookId, $error);
            return ['error' => $error];
        }
    }

    /**
     * Recupera o PDF de uma nota fiscal específica na NFE.io.
     *
     * @param string $nfeioId   ID da nota fiscal na NFE.io.
     * @param string $companyId ID da empresa associada à nota fiscal.
     * @return mixed Conteúdo binário do PDF em caso de sucesso, ou array com chave 'error' em caso de falha.
     */
    public function getPdf($nfeioId, $companyId)
    {
        $this->apiAuth();
        try {
            return \NFe_ServiceInvoice::pdf($companyId, $nfeioId);
        } catch (\Exception $e) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'get_nfe_pdf',
                ['nfeio_id' => $nfeioId, 'company_id' => $companyId],
                $e->getMessage()
            );
            return ['error' => $e->getMessage()];
        }

    }

    /**
     * Recupera o XML de uma nota fiscal específica na NFE.io.
     *
     * Este método assegura a autenticação da API, tenta obter o XML através do SDK
     * e captura exceções para registrar logs de erro e retornar uma resposta padronizada.
     *
     * @param string $nfeioId   ID da nota fiscal na NFE.io.
     * @param string $companyId ID da empresa associada à nota fiscal.
     *
     * @return mixed Conteúdo XML em caso de sucesso, ou array com chave 'error' em caso de falha.
     */
    public function getXml($nfeioId, $companyId)
    {
        $this->apiAuth();
        try {
            return \NFe_ServiceInvoice::xml($companyId, $nfeioId);
        } catch (\Exception $e) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'get_nfe_xml',
                ['nfeio_id' => $nfeioId, 'company_id' => $companyId],
                $e->getMessage()
            );
            return ['error' => $e->getMessage()];
        }
    }
}
