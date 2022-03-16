<?php

namespace NFEioServiceInvoices\Admin;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once(dirname(dirname(__DIR__)) . DS . 'Loader.php');

use NFEioServiceInvoices\CustomFields;
use NFEioServiceInvoices\Helpers\Versions;
use Smarty;
use WHMCS\Database\Capsule;
use Plasticbrain\FlashMessages\FlashMessages;
use WHMCSExpert\Template\Template;
use \NFEioServiceInvoices\Addon;


/**
 * Sample Admin Area Controller
 */
class Controller {


    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function index($vars)
    {
        try {

            $template = new Template(Addon::getModuleTemplatesDir());
            $assetsURL = Addon::I()->getAssetsURL();
            $msg = new FlashMessages;
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
                $msg->error("<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.", '', true);
            }

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('index', $vars);

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Exibe a página de configuração do módulo associando qualquer variável padrão ou personalizada ao tpl.
     *
     * @param $vars array parametros do WHMCS
     * @return string|void template de visualização com parametros
     */
    public function configuration($vars)
    {
        try {

            $msg = new FlashMessages;
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);
            $assetsURL = Addon::I()->getAssetsURL();
            $moduleCallBackUrl = Addon::I()->getCallBackPath();
            $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
            $moduleFields = $moduleConfigurationRepo->getFields();
            $customFieldsClientsOptions = CustomFields::getClientFields();
            $vars['customFieldsClientsOptions'] = $customFieldsClientsOptions;
            $vars['moduleFields'] = $moduleFields;
            $vars['formAction'] = 'configurationSave';
            $vars['assetsURL'] = $assetsURL;
            $vars['moduleCallBackUrl'] = $moduleCallBackUrl;

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error("<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.", '', true);
            }

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
     * @param $vars array Parametros do WHMCS
     */
    public function configurationSave($vars)
    {

        $msg = new FlashMessages;
        $assetsURL = Addon::I()->getAssetsURL();
        $vars['assetsURL'] = $assetsURL;
        $moduleLink = $vars['modulelink'];
        $action = 'configuration';
        $config = new \NFEioServiceInvoices\Configuration();
        $storage = new \WHMCSExpert\Addon\Storage($config->getStorageKey());
        $post = isset($_POST) ? $_POST : null;

        // campos para atualização conforme post realizado
        $api_key = isset($post['api_key']) ? $post['api_key'] : null;
        $company_id = isset($post['company_id']) ? $post['company_id'] : null;
        $service_code = isset($post['service_code']) ? $post['service_code'] : null;
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
        $iss_held = isset($post['iss_held']) ? $post['iss_held'] : '';

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

            if ($api_key) { $storage->set('api_key', $api_key); }
            if ($company_id) { $storage->set('company_id', $company_id); }
            if ($service_code) { $storage->set('service_code', $service_code); }
            if ($rps_number) { $storage->set('rps_number', $rps_number); }
            if ($issue_note_default_cond) { $storage->set('issue_note_default_cond', $issue_note_default_cond); }
            if ($invoice_details) { $storage->set('InvoiceDetails', $invoice_details); }
            //if ($footer) { $storage->set('footer', $footer); }
            if ($iss_held) { $storage->set('iss_held', $iss_held); }

            $msg->success("Informações atualizadas com sucesso!", "{$moduleLink}&action={$action}");

        } catch (\Exception $exception) {
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$moduleLink}&action={$action}");
        }

    }

    /**
     * Exibe a página de configuração de código de serviços e seus parametros
     * @param $vars parametros do WHMCS
     * @return string|void template
     */
    public function servicesCode($vars)
    {
        try {


            $msg = new FlashMessages;
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            $servicesCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);
            // URL absoluta dos assets
            $assetsURL = Addon::I()->getAssetsURL();

            $vars['assetsURL'] = $assetsURL;
            $vars['dtData'] = $servicesCodeRepo->dataTable();
            // parametro para o atributo action dos formulários da página
            $vars['formAction'] = 'servicesCodeSave';

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error("<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de nptas.", '', true);
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
     * Salva os códigos de serviços do post
     * @param $vars
     */
    public function servicesCodeSave($vars)
    {

        $msg = new FlashMessages();
        $post = $_POST;

        if (!isset($post) && !is_array($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=servicesCode");
        }

        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();

        if ($post['btnSave'] === 'true') {
            $response = $productCodeRepo->save($post);
            if ($response) {
                $msg->success("{$post['product_name']} atualizado com sucesso.", "{$vars['modulelink']}&action=servicesCode");
            } else {
                $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=servicesCode");
            }
        }

        if ($post['btnDelete'] === 'true') {
            $productCodeRepo->delete($post);
            $msg->warning("Código {$post['service_code']} para {$post['product_name']} removido.", "{$vars['modulelink']}&action=servicesCode");

        }



    }

    /**
     * Funções legadas da area administrativa
     * @param $vars
     */
    public function legacyFunctions($vars)
    {

        $msg = new FlashMessages();
        $functions = new \NFEioServiceInvoices\Legacy\Functions();
        $moduleLink = $vars['modulelink'];
        $moduleAction = 'index';
        $redirectUrl = $moduleLink.'&action='.$moduleAction;

        // create
        if ($_REQUEST['gnfe_create']) {
            $invoice = localAPI('GetInvoice', ['invoiceid' => $_REQUEST['invoice_id']], false);
            $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);
            $nfe_for_invoice = $functions->gnfe_get_local_nfe($_REQUEST['invoice_id'], ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at', 'rpsSerialNumber']);
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

        // cancel
        if ($_REQUEST['gnfe_cancel']) {
            $delete_nfe = $functions->gnfe_delete_nfe($_REQUEST['gnfe_cancel']);
            if (!$delete_nfe->message) {
                $gnfe_update_nfe = $functions->gnfe_update_nfe((object) ['id' => $_REQUEST['gnfe_cancel'], 'status' => 'Cancelled', 'servicesAmount' => $_REQUEST['services_amount'], 'environment' => $_REQUEST['environment'], 'flow_status' => $_REQUEST['flow_status']], $_REQUEST['user_id'], $_REQUEST['invoice_id'], 'n/a', $_REQUEST['created_at'], date('Y-m-d H:i:s'));
                $msg->success("Nota fiscal cancelada com sucesso", $redirectUrl);
            }
            if ($delete_nfe->message) {
                $msg->error($delete_nfe->message, $redirectUrl);
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

    public function ratesAndFees($vars)
    {
        try {

            $msg = new FlashMessages;
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            $servicesCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);
            // URL absoluta dos assets
            $assetsURL = Addon::I()->getAssetsURL();
            $vars['assetsURL'] = $assetsURL;
            $vars['dtData'] = $servicesCodeRepo->dataTable();
            // parametro para o atributo action do formulário principal da página
            $vars['formAction'] = 'ratesAndFeesSave';

            //d(version_compare('2.1.0-beta.2', '2.1.0', "lt"));

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error("<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de nptas.", '', true);
            }

            if ($msg->hasMessages()) {
                $msg->display();
            }

            return $template->fetch('ratesfees', $vars);

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function ratesAndFeesSave($vars)
    {
        $msg = new FlashMessages();
        $post = $_POST;
        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();

        if (!isset($post) && !is_array($post)) {
            $msg->error("Erro na submissão: dados inválidos", "{$vars['modulelink']}&action=ratesAndFees");
        }


        if ($post['btnSave'] === 'true') {
            $response = $productCodeRepo->save($post);
            if ($response) {
                $msg->success("{$post['product_name']} atualizado com sucesso.", "{$vars['modulelink']}&action=ratesAndFees");
            } else {
                $msg->info("Nenhuma alteração realizada.", "{$vars['modulelink']}&action=ratesAndFees");
            }
        }

        if ($post['btnDelete'] === 'true') {
            $productCodeRepo->resetRatesAndFees($post);
            $msg->warning("Alíquotas para {$post['product_name']} removidas.", "{$vars['modulelink']}&action=ratesAndFees");

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
            $msg = new FlashMessages;

            // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
            $oldVersion = Versions::getOldNfeioModuleVersion();
            // se tiver registro de versão antiga define mensagem
            if ($oldVersion) {
                $msg->error("<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo com uma nova versão.
                <br> Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.", '', true);
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

        $msg = new FlashMessages;

        // procuro pelo registro de versão da estrutura legada para avisar o admin para não rodar duas versões
        $oldVersion = Versions::getOldNfeioModuleVersion();
        // se tiver registro de versão antiga define mensagem
        if ($oldVersion) {
            $msg->error("<b>Atenção:</b> Você está rodando uma versão antiga do módulo ({$oldVersion}) em paralelo a uma nova versão.
                Caso você tenha acabado de concluir uma migração para a última versão, <b>desative a versão anterior e remova o antigo diretório <i>addons/gofasnfe</i> imediatamente</b> para evitar duplicidade na geração de notas.", '', true);
        }

        if ($msg->hasMessages()) {
            $msg->display();
        }

        $vars['assetsURL'] = $assetsURL;

        return $template->fetch('about', $vars);

    }
}
