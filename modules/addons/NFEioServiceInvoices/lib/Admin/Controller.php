<?php

namespace NFEioServiceInvoices\Admin;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once dirname(dirname(__DIR__)) . DS . 'Loader.php';

use NFEioServiceInvoices\CustomFields;
use NFEioServiceInvoices\Helpers\Versions;
use Smarty;
use WHMCS\Database\Capsule;
use Plasticbrain\FlashMessages\FlashMessages;
use WHMCS\Exception;
use WHMCSExpert\Template\Template;
use NFEioServiceInvoices\Addon;

use Tracy\Debugger;

Debugger::enable(Debugger::Development);

/**
 * Sample Admin Area Controller
 */
class Controller
{
    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     * @return string
     * @version 2.0
     * @since 2.0
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

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error(
                    "<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.",
                    '',
                    true
                );
            }

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('index', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function associateClients($vars)
    {
        $msg = new FlashMessages();
        $template = new Template(Addon::getModuleTemplatesDir());
        $assetsURL = Addon::I()->getAssetsURL();
        $vars['assetsURL'] = $assetsURL;
        $vars['formAction'] = 'associateClientsSave';
        $vars['jsonUrl'] = Addon::I()->genJSONUrl('associateClients');
        if ($msg->hasMessages()) {
            $msg->display();
        }
        return $template->fetch('associateclients', $vars);


    }

    /**
     * Edita os dados de uma empresa associada ao módulo.
     *
     * @param $vars
     * @return void
     * @version 3.0
     * @since 3.0
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


        if (is_null($recordId) || is_null($companyName) || is_null($serviceCode)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=configuration");
            return;
        }

        try {
            $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
            // edita os dados da empresa
            $response = $companyRepository->edit(
                $recordId,
                $companyName,
                $serviceCode,
                $issHeld,
                $companyDefault
            );
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
        $company_id = $data['company_id'] ?? null;
        $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
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
     * @param $vars
     * @return void
     * @version 3.0
     * @since 3.0
     */
    public function associateCompany($vars)
    {
        $msg = new FlashMessages();
        $data = $_POST ?? null;
        $company_id = $data['company_id'] ?? null;
        $service_code = $data['service_code'] ?? null;
        $iss_held = $data['iss_held'] ?? null;
        $company_default = $data['company_default'] ?? false;

        if ($company_default == 'on') {
            $company_default = true;
        }

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
                $company_id,
                $company_taxnumber,
                $company_name,
                $service_code,
                $iss_held,
                $company_default
            );

            if (!$response['status']) {
                $msg->error("SQL error occurred: " . $response['error'], "{$vars['modulelink']}&action=configuration");
            } else {
                // registra atividade no WHMCS
                logActivity("NFE.io: Company associated - " . $company_id, 0);
                $msg->success("Empresa associada com sucesso!", "{$vars['modulelink']}&action=configuration");
            }
        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'associateCompany',
                [
                    'company_id' => $company_id,
                    'service_code' => $service_code,
                    'iss_held' => $iss_held,
                    'default_company' => $company_default
                ],
                [
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode()
                ]
            );
            $msg->error("Error {$exception->getCode()} updating: " . $exception->getMessage(), "{$vars['modulelink']}&action=configuration");
        }
    }

    /**
     * Exibe a página de configuração do módulo associando qualquer variável padrão ou personalizada ao tpl.
     *
     * @param  $vars array parametros do WHMCS
     * @return string|void template de visualização com parametros
     * @version 3.0
     * @since 2.0
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
     * Salva as configurações do módulo
     *
     * @param $vars array Parametros do WHMCS
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
     * Busca de produtos via requisicoes ajax
     *
     * @return mixed JSON response with found products
     * @since 3.0
     * @version 3.0
     */
    public function searchProducts()
    {

        header('Content-Type: application/json');

        try {
            $searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

            if (empty($searchTerm) || strlen($searchTerm) < 2) {
                echo json_encode([]);
                return;
            }

            // Search products in the database
            $products = Capsule::table('tblproducts')
                ->select('id', 'name')
                ->where('name', 'like', '%' . $searchTerm . '%')
                ->orderBy('name', 'asc')
                ->limit(10)
                ->get()
                ->toArray();

            echo json_encode($products);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Exibe a página de configuração de código de serviços e seus parametros
     *
     * @param  $vars parametros do WHMCS
     * @return string|void template
     * @version 3.0
     * @since 2.0
     */
    public function servicesCode($vars)
    {

        try {
            $msg = new FlashMessages();
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            $servicesCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
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

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error(
                    "<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de nptas.",
                    '',
                    true
                );
            }

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('servicescode', $vars);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Remove um código de serviço personalizado
     *
     * @param $vars
     * @return void
     * @version 3.0
     * @since 3.0
     */
    public function serviceCodeRemove($vars)
    {
        $msg = new FlashMessages();
        $post = $_POST;
        $record_id = $post['record_id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=servicesCode");
        }

        // caso $product_id ou $service_code estejam vazios, retorna erro
        if (is_null($record_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=servicesCode");
        }

        try {
            $productCodeRepository = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            $productCodeRepository->delete($record_id);
            $msg->success('Código de serviço removido com sucesso.', "{$vars['modulelink']}&action=servicesCode");
        } catch (Exception $exception) {
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$vars['modulelink']}&action=servicesCode");
        }


    }

    /**
     * Salva os códigos de serviços personalizados
     *
     * @param $vars
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

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=servicesCode");
        }

        // caso $product_id, $service_code ou $product_name estejam vazios, retorna erro
        if (is_null($product_id) || is_null($service_code)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=servicesCode");
        }

        $response = $productCodeRepo->save($product_id, $service_code, $company_id);

        if ($response) {
            // registra atividade no WHMCS
            logActivity('NFE.io: Código de serviço atualizado - Produto: ' . $product_id . ' Código: ' . $service_code, 0);
            $msg->success("{$product_name} atualizado com sucesso.", "{$vars['modulelink']}&action=servicesCode");
        } else {
            $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=servicesCode");
        }

    }

    /**
     * Funções legadas da area administrativa
     *
     * @param $vars
     * @since 1.0
     */
    public function legacyFunctions($vars)
    {

        $msg = new FlashMessages();
        $functions = new \NFEioServiceInvoices\Legacy\Functions();
        $moduleLink = $vars['modulelink'];
        $moduleAction = 'index';
        $redirectUrl = $moduleLink . '&action=' . $moduleAction;
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();

        // create
        if ($_REQUEST['gnfe_create']) {
            $nfe_for_invoice = $functions->gnfe_get_local_nfe(
                $_REQUEST['invoice_id'],
                ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at', 'rpsSerialNumber']
            );
            if (!$nfe_for_invoice['id']) {
                $queue = $functions->gnfe_queue_nfe($_REQUEST['invoice_id'], true);
                if ($queue !== 'success') {
                    $msg->error("Erro ao salvar nota fiscal no DB: <b>{$queue}</b>", $redirectUrl);
                }
                if ($queue === 'success') {
                    $msg->success("Nota fiscal enviada para processamento", $redirectUrl);
                }
            } else {
                if ($queue !== 'success') {
                    $msg->error("Erro ao salvar nota fiscal no DB: nota fiscal já solicitada", $redirectUrl);
                }
            }
        }
        // reissue
        if ($_REQUEST['nfeio_reissue'] and (isset($_REQUEST['nfe_id']) and !empty($_REQUEST['nfe_id']))) {
            $nfId = $_REQUEST['nfe_id'];
            $result = $nfe->reissueNfbyId($nfId);

            if ($result === 'success') {
                $msg->success('NF reemitida com sucesso', $redirectUrl);
            } else {
                $msg->error("Erro ao reemitir NF: {$result}", $redirectUrl);
            }
        }

        // cancel
        if ($_REQUEST['gnfe_cancel']) {
            $delete_nfe = $functions->gnfe_delete_nfe($_REQUEST['gnfe_cancel']);
            $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
            if ($delete_nfe->message) {
                $response = $nfe->updateLocalNfeStatus($_REQUEST['gnfe_cancel'], 'Cancelled');

                logModuleCall('nfeio_serviceinvoices', 'cancel_nf', $_REQUEST['gnfe_cancel'], "NF API Response: \n {$delete_nfe->message} \n NF LOCAL Response: \n {$response}");

                $msg->warning("Nota fiscal cancelada, mas com aviso: {$delete_nfe->message}", $redirectUrl);
            } else {
                logModuleCall('nfeio_serviceinvoices', 'cancel_nf', $_REQUEST['gnfe_cancel'], $delete_nfe);

                $msg->success("Nota fiscal cancelada com sucesso", $redirectUrl);
            }
        }

        // email
        if ($_REQUEST['gnfe_email']) {
            $gnfe_email = $functions->gnfe_email_nfe($_REQUEST['gnfe_email']);
            if (!$gnfe_email->message) {
                $msg->info("Email Enviado com Sucesso", $redirectUrl);
            }
            if ($gnfe_email->message) {
                $msg->error($gnfe_email->message, $redirectUrl);
            }
        }

        // message
        if ($_REQUEST['gnfe_message']) {
            echo urldecode(base64_decode($_REQUEST['gnfe_message']));
        }
    }

    /**
     * @param $vars
     * @return string|void
     * @version 3.0
     * @since 2.1
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

            $aliquots = $aliquotsRepo->aliquotsDataTable()->toArray();
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
     * Remove uma alíquota de serviço
     *
     * @param $vars
     * @return void
     * @version 3.0
     * @since 3.0
     */
    public function aliquotsRemove($vars)
    {
        $msg = new FlashMessages();
        $post = $_POST;
        $record_id = $post['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=aliquots");
        }

        // caso $product_id ou $service_code estejam vazios, retorna erro
        if (is_null($record_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=aliquots");
        }

        try {
            $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
            $aliquotsRepo->delete($record_id);
            $msg->success('Alíquota removida com sucesso.', "{$vars['modulelink']}&action=aliquots");
        } catch (Exception $exception) {
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$vars['modulelink']}&action=aliquots");
        }
    }

    /**
     * @param $vars
     * @return void
     * @version 3.0
     * @since 2.1
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

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=aliquots");
        }

        if (is_null($iss_held) || is_null($company_id) || is_null($code_service)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=aliquots");
        }


        $response = $aliquotsRepo->new($code_service, $iss_held, $company_id);

        if ($response) {
            $msg->success("Alíquota registrada com sucesso.", "{$vars['modulelink']}&action=aliquots");
        } else {
            $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=aliquots");
        }

    }

    public function aliquotsEdit($vars)
    {
        $msg = new FlashMessages();
        $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
        $post = $_POST;

        $iss_held = $post['iss_held'] ?? null;
        $service_code = $post['service_code'] ?? null;
        $record_id = $post['record_id'] ?? null;
        $company_name = $post['company_name'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=aliquots");
        }

        if (is_null($iss_held) || is_null($record_id)) {
            $msg->error("Erro na submissão: campos obrigatórios não preenchidos", "{$vars['modulelink']}&action=aliquots");
        }

        $response = $aliquotsRepo->edit($record_id, $iss_held);

        if ($response) {
            $msg->success("Alíquota para código <strong>{$service_code}</strong> emissor <strong>{$company_name}</strong> editada com sucesso.", "{$vars['modulelink']}&action=aliquots");
        } else {
            $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=aliquots");
        }


    }

    /**
     * Reemite as notas fiscais para uma determinada fatura
     *
     * @param   $vars array variáveis do WHMCS
     * @author  Andre Bellafronte <andre@eunarede.com>
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
        } else {
            $msg->info($response['message'], $redirectUrl);
        }
    }

    /**
     * Envia a nota fiscal por email ao cliente através da API da NFE.io
     *
     * @param   $params array variáveis do WHMCS
     */
    public function emailNf($params)
    {
        $msg = new FlashMessages();
        $functions = new \NFEioServiceInvoices\Legacy\Functions();
        $get = $_GET;
        $nfId = $get['nfe_id'];
        $moduleLink = $params['modulelink'];
        $moduleAction = 'index';
        $redirectUrl = $moduleLink . '&action=' . $moduleAction;

        if (empty($nfId)) {
            $msg->warning("Nenhuma nota fiscal informada.", $redirectUrl);
        }

        $response = $functions->gnfe_email_nfe($nfId);

        if (empty($response->message)) {
            $msg->success("Nota fiscal enviada por email com sucesso.", $redirectUrl);
        } else {
            $msg->error($response->message, $redirectUrl);
        }
    }

    public function updateNfStatus($params)
    {
        $msg = new FlashMessages();
        $moduleLink = $params['modulelink'];
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $nfeId = $_GET['nfe_id'];

        if (empty($nfeId)) {
            $msg->warning("Nenhuma nota fiscal informada.", $moduleLink);
        }

        $invoice = $nfe->fetchNf($nfeId);

        if ($invoice['error']) {
            $msg->error("Erro ao buscar NF na API: {$invoice['error']}.", $moduleLink);
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

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error(
                    "<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.",
                    '',
                    true
                );
            }

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

        // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
        $oldVersion = Versions::getOldNfeioModuleVersion();
        // se tiver registro de versão antiga define mensagem
        if ($oldVersion) {
            $msg->error(
                "<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo a uma nova versão.
                Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.",
                '',
                true
            );
        }

        if ($msg->hasMessages()) {
            $msg->display();
        }

        $vars['assetsURL'] = $assetsURL;

        return $template->fetch('about', $vars);
    }
}
