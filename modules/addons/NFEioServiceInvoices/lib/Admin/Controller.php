<?php

namespace NFEioServiceInvoices\Admin;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once dirname(dirname(__DIR__)) . DS . 'Loader.php';

use NFEioServiceInvoices\CustomFields;
use Smarty;
use WHMCS\Database\Capsule;
use Plasticbrain\FlashMessages\FlashMessages;
use WHMCS\Exception;
use WHMCSExpert\Template\Template;
use NFEioServiceInvoices\Addon;
use WHMCSExpert\Addon\Storage;
use NFEioServiceInvoices\Configuration;


class Controller
{
    /**
     * Exibe a página inicial do módulo com as notas fiscais existentes. Esta função renderiza a página inicial
     * do módulo, exibindo as notas fiscais.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return string|void Retorna o conteúdo renderizado do template ou nada em caso de erro.
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 2.0
     * @version 2.0
     */
    public function index($vars)
    {
        try {
            $template = new Template(Addon::getModuleTemplatesDir());
            $assetsURL = Addon::I()->getAssetsURL();
            $msg = new FlashMessages();
            $config = new \NFEioServiceInvoices\Configuration();
            $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
            $vars['dtData'] = $serviceInvoicesRepo->dataTable();
            $vars['assetsURL'] = $assetsURL;

            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars, false, true);

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('index', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Exibe a página de associação de clientes a empresas.
     *
     * Este método renderiza a página onde é possível associar clientes a empresas
     * disponíveis no sistema. Ele carrega as empresas disponíveis e os clientes
     * já associados, além de configurar as variáveis necessárias para o template.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return string Retorna o conteúdo renderizado do template.
     * @version 3.0
     * @since 3.0
     */
    public function associateClients($vars)
    {
        $msg = new FlashMessages();
        $template = new Template(Addon::getModuleTemplatesDir());
        $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
        $clientCompanyRepository = new \NFEioServiceInvoices\Models\ClientCompany\Repository();
        $assetsURL = Addon::I()->getAssetsURL();

        $availableCompanies = $companyRepository->getAll()->toArray();
        $associatedClients = $clientCompanyRepository->getAll()->toArray();

        $vars['assetsURL'] = $assetsURL;
        $vars['formAction'] = 'associateClientsSave';
        $vars['availableCompanies'] = $availableCompanies;
        $vars['associatedClients'] = $associatedClients;

        if ($msg->hasMessages()) {
            $msg->display();
        }

        return $template->fetch('associateclients', $vars);
    }

    /**
     * Salva a associação de um cliente a uma empresa.
     *
     * Este método processa os dados enviados via POST para associar um cliente a uma empresa.
     * Ele valida os campos obrigatórios, realiza a associação no repositório e registra
     * atividades no WHMCS. Em caso de erro, mensagens apropriadas são exibidas.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @version 3.0
     * @since 3.0
     */
    public function associateClientsSave($vars)
    {
        $msg = new FlashMessages();
        $data = $_POST ?? null;
        $company_id = $data['company'] ?? null;
        $client_id = $data['client_id'] ?? null;

        // verifica se os campos obrigatórios foram preenchidos
        if (is_null($company_id) || is_null($client_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=associateClients");
            return;
        }

        try {
            // inicializa o repositório de empresas
            $clientCompanyRepository = new \NFEioServiceInvoices\Models\ClientCompany\Repository();
            // associa o cliente a empresa
            $response = $clientCompanyRepository->new($client_id, $company_id);

            // verifica se houve erro na associação
            if (!$response['status']) {
                $msg->error("SQL error occurred: " . $response['error'], "{$vars['modulelink']}&action=associateClients");
            } else {
                // registra atividade no WHMCS
                logActivity("NFE.io: Client associated - " . $client_id, 0);

                $msg->success("Cliente associado com sucesso.", "{$vars['modulelink']}&action=associateClients");
            }
        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'associateClients',
                [
                    'company_id' => $company_id,
                    'client_id' => $client_id
                ],
                [
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode()
                ]
            );
            $msg->error("Error {$exception->getCode()} updating: " . $exception->getMessage(), "{$vars['modulelink']}&action=associateClients");
        }
    }

    /**
     * Remove a associação de um cliente a uma empresa.
     *
     * Este método processa a remoção de uma associação entre cliente e empresa
     * com base no ID do registro fornecido. Ele utiliza o repositório de associações
     * para realizar a exclusão e exibe mensagens de sucesso ou erro conforme o resultado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @since 3.0
     * @version 3.0
     */
    public function associateClientsRemove($vars)
    {
        $msg = new FlashMessages();
        $clientCompanyRepository = new \NFEioServiceInvoices\Models\ClientCompany\Repository();
        $data = $_POST ?? null;
        $record_id = $data['record_id'] ?? null;

        // remove a associação do cliente a empresa usando o id do registro
        $result = $clientCompanyRepository->delete($record_id);

        // verifica se houve erro na remoção
        if ($result['status']) {
            $msg->success($result['message'], "{$vars['modulelink']}&action=associateClients");
        } else {
            $msg->error("Erro ao excluir: {$result['message']}", "{$vars['modulelink']}&action=associateClients");
        }
    }

    /**
     * Realiza a busca de clientes no banco de dados com base em um termo de pesquisa.
     *
     * Este método é utilizado para buscar clientes no banco de dados com base em um termo
     * fornecido via requisição GET. Ele retorna os resultados em formato JSON.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void Retorna os resultados da busca em formato JSON ou uma mensagem de erro.
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @version 3.0
     * @since 3.0
     */
    public function searchClients($vars)
    {
        // define o cabeçalho de resposta como JSON
        header('Content-Type: application/json');

        try {
            // obtém o termo de pesquisa da requisição GET
            $searchTerm = $_GET['term'] ?? '';

            // verifica se o termo de pesquisa possui menos de 2 caracteres
            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                echo json_encode([]);
                return;
            }

            // realiza a busca de clientes no banco de dados
            $clients = Capsule::table('tblclients')
                ->select('id', 'firstname', 'lastname', 'companyname')
                ->where('firstname', 'like', '%' . $searchTerm . '%')
                ->orWhere('lastname', 'like', '%' . $searchTerm . '%')
                ->orWhere('companyname', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%')
                ->orderBy('firstname', 'asc')
                ->limit(3)
                ->get()
                ->toArray();

            // retorna os resultados em formato JSON
            echo json_encode($clients);
        } catch (\Exception $e) {
            // em caso de erro, retorna uma mensagem de erro em JSON
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Edita os dados de uma empresa associada ao módulo.
     *
     * Este método processa os dados enviados via POST para editar as informações
     * de uma empresa associada. Ele valida os campos obrigatórios, realiza a edição
     * no repositório e registra atividades no WHMCS. Em caso de erro, mensagens
     * apropriadas são exibidas.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 3.0
     * @version 3.0
     */
    public function companyEdit($vars)
    {
        $msg = new FlashMessages();
        $data = $_POST ?? null;
        $recordId = $data['id'] ?? null;
        $companyName = $data['company_name'] ?? null;
        $serviceCode = $data['service_code'] ?? null;
        $issHeld = $data['iss_held'] ?? null;
        $companyDefault = $data['default'];
        $nbsCode = $data['nbs_code'] ?? null;
        $operationCode = $data['operation_indicator'] ?? null;
        $classCode = $data['class_code'] ?? null;

        // verifica se os campos obrigatórios foram preenchidos
        if (is_null($recordId) || is_null($companyName) || is_null($serviceCode)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=configuration");
            return;
        }

        try {
            // inicializa o repositório de empresas
            $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
            // edita os dados da empresa
            $response = $companyRepository->edit(
                $recordId,
                $companyName,
                $serviceCode,
                $issHeld,
                $nbsCode,
                $operationCode,
                $classCode,
                $companyDefault
            );

            // verifica se houve erro na edição
            if (!$response['status']) {
                $msg->error("SQL error occurred: " . $response['error'], "{$vars['modulelink']}&action=configuration");
            } else {
                // registra atividade no WHMCS
                logActivity("NFE.io: Company updated - " . $recordId, 0);

                $msg->success("Empresa editada com sucesso.", "{$vars['modulelink']}&action=configuration");
            }
        } catch (\Exception $exception) {
            $msg->error("Error {$exception->getCode()} updating: " . $exception->getMessage(), "{$vars['modulelink']}&action=configuration");
        }
    }

    /**
     * Exclui uma empresa associada ao módulo.
     *
     * @param $vars
     * @return void
     * @version 3.0
     * @since 3.0
     */
    public function companyDelete($vars)
    {
        $msg = new FlashMessages();
        $data = $_POST ?? null;
        $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
        $company_id = $data['company_id'] ?? null;

        $result = $companyRepository->delete($company_id);

        if ($result['status']) {
            $msg->success($result['message'], "{$vars['modulelink']}&action=configuration");
        } else {
            $msg->error("Erro ao excluir: {$result['message']}", "{$vars['modulelink']}&action=configuration");
        }
    }

    /**
     * Associa uma empresa ao módulo.
     *
     * Este método processa os dados enviados via POST para associar uma empresa ao módulo.
     * Ele valida os campos obrigatórios, obtém os detalhes da empresa via API, salva os dados
     * no repositório e registra atividades no WHMCS. Em caso de erro, mensagens apropriadas
     * são exibidas e o erro é registrado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 3.0
     * @version 3.0
     */
    public function associateCompany($vars)
    {
        $msg = new FlashMessages();
        $data = $_POST ?? null;
        $company_id = $data['company_id'] ?? null;
        $service_code = $data['service_code'] ?? null;
        $iss_held = $data['iss_held'] ?? null;
        $company_default = $data['company_default'] ?? false;
        $nbs_code = $data['nbs_code'] ?? null;
        $operation_indicator = $data['operation_indicator'] ?? null;
        $class_code = $data['class_code'] ?? null;

        // converte o valor de company_default para booleano
        if ($company_default == 'on') {
            $company_default = true;
        }

        // verifica se os campos obrigatórios foram preenchidos
        if (is_null($company_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=configuration");
            return;
        }

        try {
            $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
            $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
            $company_details = $nfe->getCompanyDetails($company_id);
            $company_name = strtoupper($company_details->name);
            $company_taxnumber = $company_details->federalTaxNumber;

            // salva os dados da empresa
            $response = $companyRepository->save(
                (string) $company_id,
                (string) $company_taxnumber,
                $company_name,
                (string) $service_code,
                $iss_held,
                (string) $nbs_code,
                (string) $operation_indicator,
                (string) $class_code,
                $company_default,
            );

            // verifica se houve erro na associação
            if (!$response['status']) {
                $msg->error("SQL error occurred: " . $response['error'], "{$vars['modulelink']}&action=configuration");
            } else {
                // registra atividade no WHMCS
                logActivity("NFE.io: Company associated - " . $company_id, 0);

                $msg->success("Empresa associada com sucesso!", "{$vars['modulelink']}&action=configuration");
            }
        } catch (\Exception $exception) {
            // registra erro na chamada do módulo
            logModuleCall(
                'nfeio_serviceinvoices',
                'associateCompany',
                [
                    'company_id' => $company_id,
                    'service_code' => $service_code,
                    'iss_held' => $iss_held,
                    'nbs_code' => $nbs_code,
                    'operation_indicator' => $operation_indicator,
                    'class_code' => $class_code,
                    'default_company' => $company_default
                ],
                [
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode()
                ]
            );

            // exibe mensagem de erro
            $msg->error("Error {$exception->getCode()} updating: " . $exception->getMessage(), "{$vars['modulelink']}&action=configuration");
        }
    }

    /**
     * Exibe a página de configuração do módulo.
     *
     * Este método é responsável por renderizar a página de configuração do módulo,
     * associando variáveis padrão e personalizadas ao template. Ele também verifica
     * se há campos obrigatórios não preenchidos e filtra as empresas disponíveis
     * para exibição no dropdown.
     *
     * @param array $vars Parâmetros fornecidos pelo WHMCS.
     * @return string|void Retorna o conteúdo renderizado do template ou nada em caso de erro.
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 2.0
     * @version 3.0
     */
    public function configuration($vars)
    {
        try {
            $msg = new FlashMessages();
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
            $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);

            $assetsURL = Addon::I()->getAssetsURL();
            $moduleCallBackUrl = Addon::I()->getCallBackPath();
            $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
            $moduleFields = $moduleConfigurationRepo->getFields();
            $customFieldsClientsOptions = CustomFields::getClientFields();
            $availableCompanies = $nfe->companiesDropDownValues();
            $registeredCompanies = $companyRepository->getAll();

            // remove as empresas que já estão cadastradas
            $companiesDropDown = array_filter($availableCompanies, function ($key) use ($registeredCompanies) {
                return !in_array($key, array_column($registeredCompanies->toArray(), 'company_id'));
            }, ARRAY_FILTER_USE_KEY);

            $vars['customFieldsClientsOptions'] = $customFieldsClientsOptions;
            $vars['moduleFields'] = $moduleFields;
            $vars['companiesDropDown'] = $companiesDropDown;
            $vars['formAction'] = 'configurationSave';
            $vars['assetsURL'] = $assetsURL;
            $vars['moduleCallBackUrl'] = $moduleCallBackUrl;
            $vars['companies'] = $registeredCompanies;


            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('configuration', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Salva as configurações do módulo.
     *
     * Este método processa os dados enviados via POST para salvar as configurações
     * do módulo no armazenamento persistente. Ele valida os campos recebidos,
     * define valores padrão para campos ausentes e exibe mensagens de sucesso ou erro
     * conforme o resultado da operação.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 2.0
     * @version 3.0
     */
    public function configurationSave($vars)
    {

        $msg = new FlashMessages();
        $assetsURL = Addon::I()->getAssetsURL();
        $vars['assetsURL'] = $assetsURL;
        $moduleLink = $vars['modulelink'];
        $action = 'configuration';
        $config = new \NFEioServiceInvoices\Configuration();
        $storage = new \WHMCSExpert\Addon\Storage($config->getStorageKey());
        $post = isset($_POST) ? $_POST : null;

        // campos para atualização conforme post realizado
        $rps_number = isset($post['rps_number']) ? $post['rps_number'] : 'RPS administrado pela NFe.';
        $gnfe_email_nfe_config = isset($post['gnfe_email_nfe_config']) ? $post['gnfe_email_nfe_config'] : '';
        $issue_note_default_cond = isset($post['issue_note_default_cond']) ? $post['issue_note_default_cond'] : null;
        $issue_note_after = isset($post['issue_note_after']) ? $post['issue_note_after'] : '';
        $cancel_invoice_cancel_nfe = isset($post['cancel_invoice_cancel_nfe']) ? $post['cancel_invoice_cancel_nfe'] : '';
        $insc_municipal = isset($post['insc_municipal']) ? $post['insc_municipal'] : '';
        $cpf_camp = isset($post['cpf_camp']) ? $post['cpf_camp'] : '';
        $cnpj_camp = isset($post['cnpj_camp']) ? $post['cnpj_camp'] : '';
        $tax = isset($post['tax']) ? $post['tax'] : '';
        $invoice_details = isset($post['InvoiceDetails']) ? $post['InvoiceDetails'] : null;
        $send_invoice_url = isset($post['send_invoice_url']) ? $post['send_invoice_url'] : '';
        $desc_custom = isset($post['descCustom']) ? $post['descCustom'] : '';
        //$footer = isset($post['footer']) ? $post['footer'] : ' ';
        $iss_held = isset($post['iss_held']) ? $post['iss_held'] : 0;
        $discount_items = isset($post['discount_items']) ? $post['discount_items'] : '';

        // verifica cada campo e realiza a inserção das configurações no banco
        try {
            // campos do tipo checkbox sempre receberão <vazio> quando não definido (representa o false na opção )
            //
            // gnfe_email_nfe_config
            $storage->set('gnfe_email_nfe_config', $gnfe_email_nfe_config);
            // tax
            $storage->set('tax', $tax);
            // send_invoice_url
            $storage->set('send_invoice_url', $send_invoice_url);
            // cancel_invoice_cancel_nfe
            $storage->set('cancel_invoice_cancel_nfe', $cancel_invoice_cancel_nfe);
            // campos do tipo texto que quando vazio representa nulo
            //
            // issue_note_after
            $storage->set('issue_note_after', $issue_note_after);
            // descCustom
            $storage->set('descCustom', $desc_custom);
            // insc_municipal
            $storage->set('insc_municipal', $insc_municipal);
            // cpf_camp
            $storage->set('cpf_camp', $cpf_camp);
            // cnpj_camp
            $storage->set('cnpj_camp', $cnpj_camp);
            // iss held
            $storage->set('iss_held', $iss_held);
            // discount_items
            $storage->set('discount_items', $discount_items);

            if ($rps_number) {
                $storage->set('rps_number', $rps_number);
            }
            if ($issue_note_default_cond) {
                $storage->set('issue_note_default_cond', $issue_note_default_cond);
            }
            if ($invoice_details) {
                $storage->set('InvoiceDetails', $invoice_details);
            }
            //if ($footer) { $storage->set('footer', $footer); }

            $msg->success("Informações atualizadas com sucesso!", "{$moduleLink}&action={$action}");
        } catch (\Exception $exception) {
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$moduleLink}&action={$action}");
        }
    }

    /**
     * Realiza a busca de produtos no banco de dados com base em um termo de pesquisa.
     *
     * Este método é utilizado para buscar produtos no banco de dados com base em um termo
     * fornecido via requisição GET. Ele retorna os resultados em formato JSON.
     *
     * @return void Retorna os resultados da busca em formato JSON ou uma mensagem de erro.
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 3.0
     * @version 3.0
     */
    public function searchProducts()
    {

        // define o cabeçalho de resposta como JSON
        header('Content-Type: application/json');

        try {
            // obtém o termo de pesquisa da requisição GET
            $searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

            // verifica se o termo de pesquisa possui menos de 2 caracteres
            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                echo json_encode([]);
                return;
            }

            // realiza a busca de produtos no banco de dados
            $products = Capsule::table('tblproducts')
                ->select('id', 'name')
                ->where('name', 'like', '%' . $searchTerm . '%')
                ->orderBy('name', 'asc')
                ->limit(10)
                ->get()
                ->toArray();

            // retorna os resultados em formato JSON
            echo json_encode($products);
        } catch (\Exception $e) {
            // em caso de erro, retorna uma mensagem de erro em JSON
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Exibe a página de configuração de códigos de serviços e seus parâmetros.
     *
     * Este método é responsável por renderizar a página de configuração de códigos de serviços,
     * associando as variáveis necessárias ao template. Ele também verifica se há campos obrigatórios
     * não preenchidos e carrega as empresas disponíveis para exibição.
     *
     * @param array $vars Parâmetros fornecidos pelo WHMCS.
     * @return string|void Retorna o conteúdo renderizado do template ou nada em caso de erro.
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 2.0
     * @version 3.0
     */
    public function servicesCode($vars)
    {

        try {
            $msg = new FlashMessages();
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            $servicesCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
            $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
            $moduleFields = $moduleConfigurationRepo->getFields();
            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);
            // URL absoluta dos assets
            $assetsURL = Addon::I()->getAssetsURL();
            $availableCompanies = $companyRepository->getAll()->toArray();

            $vars['assetsURL'] = $assetsURL;
            $vars['dtData'] = $servicesCodeRepo->servicesCodeDataTable();
            // parametro para o atributo action dos formulários da página
            $vars['formAction'] = 'servicesCodeSave';
            // #163 Gera as URL para as requisicoes em json
            $vars['jsonUrl'] = Addon::I()->genJSONUrl('servicesCode');
            $vars['availableCompanies'] = $availableCompanies;
            $vars['moduleFields'] = $moduleFields;

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('servicescode', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Remove um código de serviço personalizado.
     *
     * Este método processa a remoção de um código de serviço com base no ID do registro fornecido.
     * Ele utiliza o repositório de códigos de serviços para realizar a exclusão e exibe mensagens
     * de sucesso ou erro conforme o resultado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 3.0
     * @version 3.0
     */
    public function serviceCodeRemove($vars)
    {
        $msg = new FlashMessages();
        $post = $_POST;
        $record_id = $post['record_id'] ?? null;

        // caso requisição não for POST ou não houver dados, retorna erro
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=servicesCode");
        }

        // caso $product_id ou $service_code estejam vazios, retorna erro
        if (is_null($record_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=servicesCode");
        }

        try {
            // inicializa o repositório de códigos de serviços
            $productCodeRepository = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            // remove o código de serviço usando o id do registro
            $productCodeRepository->delete($record_id);
            // retorna sucesso
            $msg->success('Código de serviço removido com sucesso.', "{$vars['modulelink']}&action=servicesCode");
        } catch (Exception $exception) {
            // retorna erro
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$vars['modulelink']}&action=servicesCode");
        }
    }

    /**
     * Salva os códigos de serviços personalizados.
     *
     * Este método processa os dados enviados via POST para salvar ou atualizar
     * os códigos de serviços associados a produtos. Ele valida os campos obrigatórios,
     * realiza a operação no repositório e exibe mensagens de sucesso ou erro conforme o resultado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 2.0
     * @version 3.0
     */
    public function servicesCodeSave($vars)
    {

        $msg = new FlashMessages();
        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $post = $_POST;
        $product_id = $post['product_id'] ?? null;
        $service_code = $post['service_code'] ?? null;
        $product_name = $post['product_name'] ?? null;
        $company_id = $post['company'] ?? null;
        $company_default = $post['company_default'] ?? null;
        $nbs_code = $post['nbs_code'] ?? null;
        $operation_indicator = $post['operation_indicator'] ?? null;
        $class_code = $post['class_code'] ?? null;

        // caso requisição não for POST ou não houver dados, retorna erro
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=servicesCode");
        }

        // caso $product_id, $service_code ou $product_name estejam vazios, retorna erro
        if (is_null($product_id) || is_null($service_code)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=servicesCode");
        }

        // salva os dados do código de serviço
        $response = $productCodeRepo->save($product_id, $service_code, $company_id, $nbs_code, $operation_indicator, $class_code);

        // verifica se houve erro na associação
        if ($response) {
            // registra atividade no WHMCS
            logActivity('NFE.io: Código de serviço atualizado - Produto: ' . $product_id . ' Código: ' . $service_code, 0);
            // retorna sucesso
            $msg->success("{$product_name} atualizado com sucesso.", "{$vars['modulelink']}&action=servicesCode");
        } else {
            // retorna erro
            $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=servicesCode");
        }
    }

    /**
     * Exibe a página de alíquotas de serviços.
     *
     * Este método é responsável por renderizar a página de alíquotas de serviços,
     * associando as variáveis necessárias ao template e filtrando os códigos de
     * serviços que já possuem alíquotas definidas.
     *
     * @param array $vars Parâmetros de configuração do módulo fornecidos pelo WHMCS.
     * @return string|void Retorna o conteúdo renderizado do template ou nada em caso de erro.
     * @throws \Exception Caso ocorra algum erro durante a execução.
     *
     * @since 2.1
     * @version 3.0
     */
    public function aliquots($vars)
    {
        try {
            $msg = new FlashMessages();
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            $servicesCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();

            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);

            // URL absoluta dos assets
            $assetsURL = Addon::I()->getAssetsURL();

            // carrega os dados da tabela de alíquotas
            $aliquots = $aliquotsRepo->aliquotsDataTable()->toArray();
            // carrega os dados da tabela de códigos de serviços
            $serviceCodes = $servicesCodeRepo->aliquotsCodesDataTable()->toArray();

            // remove todos os codigos de servicos que ja possuirem uma aliquota definida para um emissor
            $filteredServiceCodes = array_filter($serviceCodes, function ($service) use ($aliquots) {
                foreach ($aliquots as $aliquot) {
                    if ($service->service_code === $aliquot->code_service && $service->company_id === $aliquot->company_id) {
                        return false; // Exclude service codes with retention already defined
                    }
                }
                return true;
            });

            // action do formulário
            $vars['formAction'] = 'aliquotsSave';
            $vars['assetsURL'] = $assetsURL;
            $vars['dtData'] = $aliquots;
            $vars['dropdownServiceCodesAliquots'] = $filteredServiceCodes;

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('aliquots', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Remove uma alíquota de serviço.
     *
     * Este método processa a remoção de uma alíquota de serviço com base no ID do registro fornecido.
     * Ele utiliza o repositório de alíquotas para realizar a exclusão e exibe mensagens de sucesso ou erro
     * conforme o resultado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 3.0
     * @version 3.0
     */
    public function aliquotsRemove($vars)
    {
        $msg = new FlashMessages();
        $post = $_POST;
        $record_id = $post['id'] ?? null;

        // caso requisição não for POST ou não houver dados, retorna erro
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=aliquots");
        }

        // caso $product_id ou $service_code estejam vazios, retorna erro
        if (is_null($record_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=aliquots");
        }

        try {
            // inicializa o repositório de alíquotas
            $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
            // remove a alíquota usando o id do registro
            $aliquotsRepo->delete($record_id);
            // retorna sucesso
            $msg->success('Alíquota removida com sucesso.', "{$vars['modulelink']}&action=aliquots");
        } catch (Exception $exception) {
            // retorna erro
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$vars['modulelink']}&action=aliquots");
        }
    }

    /**
     * Salva uma nova alíquota de serviço.
     *
     * Este método processa os dados enviados via POST para salvar uma nova alíquota de serviço
     * associada a um código de serviço e uma empresa. Ele valida os campos obrigatórios,
     * realiza a operação no repositório e exibe mensagens de sucesso ou erro conforme o resultado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @throws \Exception Caso ocorra algum erro durante a execução.
     * @since 2.1
     * @version 3.0
     */
    public function aliquotsSave($vars)
    {
        $msg = new FlashMessages();
        $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
        $post = $_POST;
        $iss_held = $post['iss_held'] ?? null;
        $code_service = $post['service_code'] ?? null;
//        $record_id = $post['id'] ?? null;
        $company_id = $post['company_id'] ?? null;

        // caso requisição não for POST ou não houver dados, retorna erro
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=aliquots");
        }

        // caso iss_held ou company_id ou code_service forem nulos, retorna erro
        if (is_null($iss_held) || is_null($company_id) || is_null($code_service)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=aliquots");
        }

        // salva aliquota no banco de dados
        $response = $aliquotsRepo->new($code_service, $iss_held, $company_id);

        // verifica retorno da operacao
        if ($response) {
            $msg->success("Alíquota registrada com sucesso.", "{$vars['modulelink']}&action=aliquots");
        } else {
            $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=aliquots");
        }
    }

    /**
     * Edita uma alíquota de serviço.
     *
     * Este método processa os dados enviados via POST para editar uma alíquota de serviço
     * associada a um código de serviço e uma empresa. Ele valida os campos obrigatórios,
     * realiza a operação no repositório e exibe mensagens de sucesso ou erro conforme o resultado.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @since 2.1
     * @version 3.0
     */
    public function aliquotsEdit($vars)
    {
        $msg = new FlashMessages();
        $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
        $post = $_POST;

        $iss_held = $post['iss_held'] ?? null;
        $service_code = $post['service_code'] ?? null;
        $record_id = $post['record_id'] ?? null;
        $company_name = $post['company_name'] ?? null;

        // caso requisição não for POST ou não houver dados, retorna erro
        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=aliquots");
        }

        // caso iss_held ou ou record_id forem nulos, retorna erro
        if (is_null($iss_held) || is_null($record_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=aliquots");
        }

        // salva aliquota no banco de dados
        $response = $aliquotsRepo->edit($record_id, $iss_held);

        // verifica retorno da operacao
        if ($response) {
            $msg->success("Alíquota para código <strong>{$service_code}</strong> emissor <strong>{$company_name}</strong> editada com sucesso.", "{$vars['modulelink']}&action=aliquots");
        } else {
            $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=aliquots");
        }
    }

    /**
     * Reemite as notas fiscais para uma determinada fatura.
     *
     * Este método utiliza a API da NFE.io para reemitir todas as notas fiscais
     * associadas a uma fatura específica, identificada pelo ID da fatura.
     * Ele exibe mensagens de erro ou sucesso conforme o resultado da operação.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS.
     * @return void
     * @since 2.1
     * @version 2.1
     */
    public function reissueNf($vars)
    {
        $msg = new FlashMessages();
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $get = $_GET;
        $invoiceId = $get['invoice_id'];
        $moduleLink = $vars['modulelink'];
        $moduleAction = 'index';
        $redirectUrl = $moduleLink . '&action=' . $moduleAction;

        $response = $nfe->reissueNfSeriesByInvoiceId($invoiceId);

        if ($response['status'] != 'success') {
            $msg->error($response['message'], $redirectUrl);
        } else {
            $msg->info("Nota fiscal enviada para processamento.", $redirectUrl);
        }
    }

    /**
     * Cancela as notas fiscais associadas a uma fatura específica.
     *
     * Este método utiliza a API da NFE.io para cancelar todas as notas fiscais
     * associadas a uma fatura identificada pelo ID da fatura. Ele exibe mensagens
     * de sucesso ou informações adicionais conforme o resultado da operação.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS, incluindo o link do módulo.
     * @return void
     * @since 2.1
     * @version 2.1
     */
    public function cancelNf($vars)
    {
        $msg = new FlashMessages();
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $get = $_GET;
        $invoiceId = $get['invoice_id'];
        $moduleLink = $vars['modulelink'];
        $moduleAction = 'index';
        $redirectUrl = $moduleLink . '&action=' . $moduleAction;

        $response = $nfe->cancelNfSeriesByInvoiceId($invoiceId);

        if ($response['status'] == 'success') {
            $msg->success("Nota(s) fiscal(is) para fatura #{$invoiceId} canceladas. Sincronização do status pode demorar alguns minutos, por favor aguarde.", $redirectUrl);
        } elseif ($response['status'] == 'partial') {
            // Partial success - some NFs cancelled, some failed
            $failedIds = array_map(function ($f) {
                return $f['nfe_id'];
            }, $response['failures']);
            $failedList = implode(', ', $failedIds);
            $msg->warning("{$response['message']} Notas com falha: {$failedList}. Verifique se a empresa emissora foi alterada.", $redirectUrl);
        } elseif ($response['status'] == 'error') {
            // All NFs failed to cancel
            $failures = $response['failures'] ?? [];
            if (!empty($failures)) {
                $hasNotFoundError = array_reduce($failures, function ($carry, $f) {
                    return $carry || ($f['is_not_found'] ?? false);
                }, false);

                if ($hasNotFoundError) {
                    $msg->warning("Nota fiscal não encontrada na API. Verifique se a empresa emissora foi alterada.", $redirectUrl);
                } else {
                    $msg->error("Erro ao cancelar nota(s) fiscal(is) para fatura #{$invoiceId}. Verifique os logs para mais detalhes.", $redirectUrl);
                }
            } else {
                $msg->error($response['message'] ?? "Erro ao cancelar nota(s) fiscal(is).", $redirectUrl);
            }
        } else {
            $msg->info($response['message'], $redirectUrl);
        }
    }

    /**
     * Envia a nota fiscal por email ao cliente através da API da NFE.io.
     *
     * Este método utiliza a função legada para enviar a nota fiscal por email
     * com base no ID da nota fiscal fornecido. Ele verifica se o ID foi informado,
     * dispara o email e exibe mensagens de sucesso ou erro conforme o resultado.
     *
     * @param array $params Parâmetros fornecidos pelo WHMCS, incluindo o link do módulo.
     * @return void
     * @since 2.0
     * @version 2.0
     */
    public function emailNf($params)
    {
        $msg = new FlashMessages();
        $nfeio = new \NFEioServiceInvoices\NFEio\Nfe();
        $get = $_GET;
        $nfeioId = $get['nfe_id'];
        $companyId = $get['company_id'];
        $moduleLink = $params['modulelink'];
        $moduleAction = 'index';
        $redirectUrl = $moduleLink . '&action=' . $moduleAction;

        // verifica se o ID da nota fiscal está vazio
        if (empty($nfeioId) || empty($companyId)) {
            $msg->warning("Parametros incorretos.", $redirectUrl);
        }

        // dispara o email
        $response = $nfeio->sendNfeioEmail($nfeioId, $companyId);

        // verifica se houve erro no envio do email
        if (empty($response['error'])) {
            $msg->success("Nota fiscal enviada por email com sucesso.", $redirectUrl);
        } else {
            $msg->error($response['error'], $redirectUrl);
        }
    }

    /**
     * Atualiza o status de uma nota fiscal.
     *
     * Este método utiliza a API da NFE.io para buscar e atualizar o status de uma nota fiscal
     * no banco de dados local. Ele verifica se os parâmetros necessários foram fornecidos,
     * realiza a busca da nota fiscal na API e atualiza o status localmente.
     *
     * @param array $params Parâmetros fornecidos pelo WHMCS, incluindo o link do módulo.
     * @return void
     * @since 2.2
     * @version 2.2
     */
    public function updateNfStatus($params)
    {
        $msg = new FlashMessages();
        $moduleLink = $params['modulelink'];
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $nfeId = $_GET['nfe_id'];
        $companyId = $_GET['company_id'];

        // verifica se o ID da nota fiscal e o ID da empresa estão vazios
        if (empty($nfeId) && empty($companyId)) {
            $msg->warning("Nenhuma nota fiscal informada.", $moduleLink);
        }

        // busca a nota fiscal na API
        $invoice = $nfe->fetchNf($nfeId, $companyId);

        // verifica se houve erro na busca da nota fiscal
        if ($invoice['error']) {
            $msg->error("Erro ao buscar NF na API: {$invoice['error']}", $moduleLink);
        }

        // recebe os dados da nota fiscal
        $invoiceId = $invoice->id;
        $invoiceStatus = $invoice->status;
        $invoiceFlowStatus = $invoice->flowStatus;

        // atualiza o status da nota fiscal no banco de dados
        $result = $nfe->updateLocalNfeStatus($invoiceId, $invoiceStatus, $invoiceFlowStatus);

        if ($result) {
            $msg->success("Nota fiscal atualizada com sucesso.", $moduleLink);
        } else {
            $msg->error("Erro ao atualizar nota fiscal.", $moduleLink);
        }
    }

    /**
     * Support action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function support($vars)
    {
        try {
            Addon::I()->isAdmin(true);
            $template = new Template(Addon::getModuleTemplatesDir());
            $assetsURL = Addon::I()->getAssetsURL();
            $msg = new FlashMessages();

            if ($msg->hasMessages()) {
                $msg->display();
            }

            $vars['assetsURL'] = $assetsURL;

            return $template->fetch('support', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function about($vars)
    {
        Addon::I()->isAdmin(true);
        $template = new Template(Addon::getModuleTemplatesDir());
        $assetsURL = Addon::I()->getAssetsURL();
        $msg = new FlashMessages();

        if ($msg->hasMessages()) {
            $msg->display();
        }

        // Recuperar dados do webhook do Storage
        $config = new Configuration();
        $storage = new Storage($config->getStorageKey());
        $webhookId = $storage->get('webhook_id');
        $webhookSecret = $storage->get('webhook_secret');
        $lastVerified = $storage->get('webhook_last_verified_at');

        // Gerar URL do callback
        $callbackUrl = Addon::getCallBackPath();

        // Mascarar secret (apenas primeiros 8 caracteres)
        $secretMasked = null;
        if ($webhookSecret && strlen($webhookSecret) > 0) {
            $secretMasked = substr($webhookSecret, 0, 8) . '...';
        }

        // Formatar timestamp de última verificação
        $lastVerifiedFormatted = null;
        if ($lastVerified) {
            try {
                $dt = new \DateTime($lastVerified);
                $lastVerifiedFormatted = $dt->format('d/m/Y H:i:s');
            } catch (\Exception $e) {
                $lastVerifiedFormatted = $lastVerified; // fallback para valor original
            }
        }

        // Construir array de dados do webhook
        $vars['webhook'] = [
            'id' => $webhookId,
            'secret_masked' => $secretMasked,
            'url' => $callbackUrl,
            'last_verified' => $lastVerifiedFormatted,
            'configured' => !empty($webhookId)
        ];

        $vars['assetsURL'] = $assetsURL;

        return $template->fetch('about', $vars);
    }

    /**
     * Verifica o status do webhook na API NFE.io
     *
     * Este método realiza a verificação on-demand do webhook configurado,
     * validando sua existência, consistência de URL e status ativo na API NFE.io.
     * Registra logs detalhados e exibe mensagens de feedback via FlashMessages.
     *
     * @param array $vars Variáveis fornecidas pelo WHMCS
     * @return void Redireciona para a página Sobre após verificação
     * @throws \Exception Em caso de erros inesperados
     * @since 3.1.1
     * @version 3.1.1
     */
    public function verifyWebhook($vars)
    {
        // Verificar autenticação de administrador
        Addon::I()->isAdmin(true);

        $config = new Configuration();
        $storage = new Storage($config->getStorageKey());
        $msg = new FlashMessages();
        $redirectUrl = $vars['modulelink'] . '&action=about';

        // Recuperar webhook_id do Storage
        $webhookId = $storage->get('webhook_id');

        // Verificar se webhook está configurado
        if (empty($webhookId)) {
            $msg->info(
                'Webhook não configurado. Será criado automaticamente na primeira emissão de nota fiscal.',
                $redirectUrl
            );
            return;
        }

        try {
            // Instanciar classe Nfe e buscar webhook na API
            $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
            $webhook = $nfe->getWebhook($webhookId);

            // Verificar se webhook existe (não retornou erro)
            if (is_array($webhook) && isset($webhook['error'])) {
                // Webhook não encontrado na API
                $errorMessage = isset($webhook['message']) ? $webhook['message'] : 'Erro desconhecido';
                
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'webhook_verify_notfound',
                    ['webhook_id' => $webhookId],
                    $errorMessage
                );

                $msg->warning(
                    'Webhook não encontrado na API. Será recriado automaticamente na próxima emissão de nota fiscal.',
                    $redirectUrl
                );
                return;
            }

            // Webhook encontrado - validar configuração
            $callbackUrl = Addon::getCallBackPath();
            $apiWebhookUrl = isset($webhook->hooks->url) ? $webhook->hooks->url : null;

            // Verificar consistência de URL
            if ($apiWebhookUrl !== $callbackUrl) {
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'webhook_verify_url_mismatch',
                    [
                        'webhook_id' => $webhookId,
                        'local_url' => $callbackUrl,
                        'api_url' => $apiWebhookUrl
                    ],
                    $webhook
                );

                $msg->warning(
                    "URL do webhook não corresponde ao esperado.<br>" .
                    "API: <code>{$apiWebhookUrl}</code><br>" .
                    "Local: <code>{$callbackUrl}</code><br>" .
                    "Considere emitir uma nova NF para recriar o webhook.",
                    $redirectUrl
                );
                return;
            }

            // Verificar status do webhook (ativo/inativo/deleted)
            $webhookStatus = isset($webhook->hooks->status) ? $webhook->hooks->status : 'unknown';
            if (in_array(strtolower($webhookStatus), ['deleted', 'disabled', 'inactive'])) {
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'webhook_verify_error',
                    ['webhook_id' => $webhookId, 'status' => $webhookStatus],
                    $webhook
                );

                $msg->error(
                    "Webhook marcado como inativo na API. Status: {$webhookStatus}<br>" .
                    "Emita uma nova nota fiscal para recriar o webhook.",
                    $redirectUrl
                );
                return;
            }

            // Verificação bem-sucedida - atualizar timestamp
            $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
            $storage->set('webhook_last_verified_at', $now->format('c')); // ISO8601 com timezone

            logModuleCall(
                'nfeio_serviceinvoices',
                'webhook_verify_success',
                [
                    'webhook_id' => $webhookId,
                    'expected_url' => $callbackUrl
                ],
                $webhook
            );

            $msg->success(
                'Webhook verificado com sucesso! Configuração está correta na API NFE.io.',
                $redirectUrl
            );

        } catch (\Exception $e) {
            // Tratar erros de comunicação com API ou exceções inesperadas
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();

            // Verificar se é erro de autenticação
            if ($errorCode == 401 || $errorCode == 403) {
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'webhook_verify_error',
                    ['webhook_id' => $webhookId, 'error_code' => $errorCode],
                    ['error' => $errorMessage]
                );

                $msg->error(
                    'Erro de autenticação. Verifique se a API Key está correta na configuração do módulo.',
                    $redirectUrl
                );
                return;
            }

            // Erro genérico (timeout, connection, etc)
            logModuleCall(
                'nfeio_serviceinvoices',
                'webhook_verify_error',
                [
                    'webhook_id' => $webhookId,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage
                ],
                $e->getTraceAsString()
            );

            $msg->error(
                'Erro ao conectar com API NFE.io. Verifique sua conexão ou tente novamente mais tarde.<br>' .
                'Consulte os logs do módulo para mais detalhes.',
                $redirectUrl
            );
        }
    }
}
