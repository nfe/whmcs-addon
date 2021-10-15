<?php

namespace NFEioServiceInvoices\Admin;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once(dirname(dirname(__DIR__)) . DS . 'Loader.php');

use NFEioServiceInvoices\CustomFields;
use NFEioServiceInvoices\Helpers\Versions;
use NFEioServiceInvoices\Migrations\Migrations;
use NFEioServiceInvoices\Models\ModuleConfiguration\Repository;
use Smarty;
use WHMCS\Database\Capsule;
use Plasticbrain\FlashMessages\FlashMessages;
use WHMCSExpert\Template\Template;
use NFEioServiceInvoices\Addon;
use NFEioServiceInvoices\Configuration;

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

            Addon::I()->isAdmin(true);
            $template = new Template(Addon::getModuleTemplatesDir());
            $assetsURL = Addon::I()->getAssetsURL();
            $msg = new FlashMessages;
            $config = new Configuration();

            $vars['assetsURL'] = $assetsURL;

            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars, false, true);

            if ($msg->hasMessages()) {
                $msg->display();
            }

            d($vars);

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


            //\NFEioServiceInvoices\Addon::I()->isAdmin(true);
            //d(\NFEioServiceInvoices\Addon::I());
            //d(\NFEioServiceInvoices\Addon::I()->genJSONUrl('test'));
            $msg = new FlashMessages;
            $template = new Template(Addon::getModuleTemplatesDir());
            $config = new \NFEioServiceInvoices\Configuration();
            // metodo para verificar se existe algum campo obrigatório não preenchido.
            $config->verifyMandatoryFields($vars);
            $assetsURL = Addon::I()->getAssetsURL();
            $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
            $moduleFields = $moduleConfigurationRepo->getFields();
            $customFieldsClientsOptions = CustomFields::getClientFields();
            $vars['customFieldsClientsOptions'] = $customFieldsClientsOptions;
            $vars['moduleFields'] = $moduleFields;
            $vars['formAction'] = 'configurationSave';
            $vars['assetsURL'] = $assetsURL;

            //d($moduleConfigurationRepo->getMandatoryFields());
            //d($moduleConfigurationRepo->getMandatoryFieldsKeys());
            //d($moduleConfigurationRepo->hasMandatoryFields($vars));
            //d($moduleConfigurationRepo->missingMandatoryFields($vars));


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
        //$moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
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

            $msg->success("Informações atualizadas com sucesso!", "{$moduleLink}&action={$action}");

        } catch (\Exception $exception) {
            $msg->error("Erro {$exception->getCode()} ao atualizar: {$exception->getMessage()}", "{$moduleLink}&action={$action}");
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

        if ($msg->hasMessages()) {
            $msg->display();
        }

        $vars['assetsURL'] = $assetsURL;

        return $template->fetch('about', $vars);

    }
}
