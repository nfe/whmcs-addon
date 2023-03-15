<?php

namespace NFEioServiceInvoices\Models\ModuleConfiguration;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Classe responsável pela definição do modelo de dados dos registros personalizados de configuração do módulo
 * apresentados ao administrador WHMCS na area de configuração do módulo.
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{

    public $tableName = 'tbladdonmodules';

    public $fieldDeclaration = array(
        'api_key',
        'company_id',
        'service_code',
        'issue_note_conditions',
        'rps_number',
        'gnfe_email_nfe_config',
        'initial_date',
        'last_cron',
        'intro',
        'issue_note_default_cond',
        'issue_note_after',
        'cancel_invoice_cancel_nfe',
        'debug',
        'insc_municipal',
        'cpf_camp',
        'cnpj_camp',
        'tax',
        'InvoiceDetails',
        'send_invoice_url',
        'descCustom',
        'footer',
        'iss_held',
        'discount_items'
    );

    /**
     * @var string[] coleção com as chaves de configuração do módulo existentes para configuração
     */
    public $model = array(
        'api_key',
        'company_id',
        'service_code',
        'issue_note_conditions',
        'rps_number',
        'gnfe_email_nfe_config',
        'initial_date',
        'last_cron',
        'intro',
        'issue_note_default_cond',
        'issue_note_after',
        'cancel_invoice_cancel_nfe',
        'debug',
        'insc_municipal',
        'cpf_camp',
        'cnpj_camp',
        'tax',
        'InvoiceDetails',
        'send_invoice_url',
        'descCustom',
        'footer',
        'iss_held',
        'discount_items'
    );

    /**
     * @var string[] campos mandatários de configuração do módulo.
     */
    public $mandatoryFields = array(
        'api_key',
        'company_id',
        'service_code',
        'issue_note_default_cond',
        'insc_municipal',
        'cpf_camp',
        'cnpj_camp',
    );

    /**
     * @var array coleção com os campos e metadados de campos do módulo existentes para configuração
     */
    public $fields = array(
        'api_key' => [
            'type' => 'text',
            'label' => 'Chave de Acesso',
            'name' => 'api_key',
            'id' => 'apiKey_Field',
            'required' => true,
            'disabled' => true,
            'description' => 'Chave de Acesso (API Key) da sua conta NFE.io',
        ],
        'company_id' => [
            'type' => 'dropdown',
            'label' => 'Empresa ID',
            'name' => 'company_id',
            'id' => 'companyId_Field',
            'required' => true,
            'disabled' => false,
            'description' => 'Selecione o Prestador (empresa emissora) da NFSe.',
        ],
        'service_code' => [
            'type' => 'text',
            'label' => 'Código de Serviço Principal',
            'name' => 'service_code',
            'id' => 'serviceCode_Field',
            'required' => true,
            'disabled' => false,
            'description' => 'Informe o Código de Serviço Municipal. Este código será usado por padrão para emissão das notas.',
        ],
        'rps_number' => [
            'type' => 'text',
            'label' => 'RPS (legado)',
            'name' => 'rps_number',
            'id' => 'rpsNumber_Field',
            'required' => false,
            'disabled' => true,
            'description' => 'RPS (legado)',
        ],
        'gnfe_email_nfe_config' => [
            'type' => 'checkbox',
            'label' => 'Enviar e-mail',
            'name' => 'gnfe_email_nfe_config',
            'id' => 'gnfeEmailNfeConfig_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Enviar e-mail com nota fiscal via NFE.io para o cliente.',
        ],
        'intro' => [
            'type' => 'text',
            'label' => 'Intro (legado)',
            'name' => 'intro',
            'id' => 'intro_Field',
            'required' => false,
            'disabled' => true,
            'description' => 'Legado',
        ],
        'issue_note_default_cond' => [
            'type' => 'radio',
            'label' => 'Emitir NFE',
            'name' => 'issue_note_default_cond',
            'id' => 'issueNoteDefaultCond_Field',
            'required' => true,
            'disabled' => false,
            'description' => 'Quando você deseja que a NFSe seja emitida?',
            'options' => [
                [
                    'label' => 'Quando a fatura é gerada',
                    'value' => 'Quando a fatura é gerada',
                ],
                [
                    'label' => 'Quando a fatura é paga',
                    'value' => 'Quando a fatura é paga',
                ],
                [
                    'label' => 'Manualmente',
                    'value' => 'Manualmente',
                ],
            ]
        ],
        'issue_note_after' => [
            'type' => 'text',
            'label' => 'Agendar Emissão',
            'name' => 'issue_note_after',
            'id' => 'issueNoteAfter_Field',
            'required' => false,
            'disabled' => false,
            'description' => "Número de dias após o pagamento da fatura que as notas devem ser emitidas. Preencher essa opção desativa a opção <b>Quando emitir NFE</b>.",
        ],
        'cancel_invoice_cancel_nfe' => [
            'type' => 'checkbox',
            'label' => 'Cancelar NFE ao Cancelar Fatura',
            'name' => 'cancel_invoice_cancel_nfe',
            'id' => 'cancelInvoiceCancelNfe_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Cancelar a nota fiscal quando a fatura vinculada é cancelada.',
        ],
        'insc_municipal' => [
            'type' => 'dropdown',
            'label' => 'Inscrição Municipal',
            'name' => 'insc_municipal',
            'id' => 'inscMunicipal_Field',
            'required' => true,
            'disabled' => false,
            'description' => 'Informe o campo personalizado referente a Inscrição Municipal.',
        ],
        'cpf_camp' => [
            'type' => 'dropdown',
            'label' => 'Campo Personalizado CPF',
            'name' => 'cpf_camp',
            'id' => 'cpfCamp_Field',
            'required' => true,
            'disabled' => false,
            'description' => 'Informe o campo personalizado referente ao CPF.',
        ],
        'cnpj_camp' => [
            'type' => 'dropdown',
            'label' => 'Campo Personalizado CNPJ',
            'name' => 'cnpj_camp',
            'id' => 'cnpjCamp_Field',
            'required' => true,
            'disabled' => false,
            'description' => 'Informe o campo personalizado referente ao CNPJ (pode ser o mesmo que campo CPF).',
        ],
        'tax' => [
            'type' => 'checkbox',
            'label' => 'Aplicar Impostos em todos os produtos',
            'name' => 'tax',
            'id' => 'tax_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Aplicar imposto automaticamente em todos os produtos?',
        ],
        'InvoiceDetails' => [
            'type' => 'radio',
            'label' => 'Descrição da NFSe',
            'name' => 'InvoiceDetails',
            'id' => 'InvoiceDetails_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'O que deve aparecer na descrição da NFSe?',
            'options' => [
                [
                    'label' => 'Número da fatura',
                    'value' => 'Número da fatura',
                ],
                [
                    'label' => 'Nome dos serviços',
                    'value' => 'Nome dos serviços',
                ],
                [
                    'label' => 'Número da fatura + Nome dos serviços',
                    'value' => 'Número da fatura + Nome dos serviços',
                ],
            ],
        ],
        'send_invoice_url' => [
            'type' => 'checkbox',
            'label' => 'Link da Fatura na NFSe',
            'name' => 'send_invoice_url',
            'id' => 'sendInvoiceUrl_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Incluir o link da fatura na descrição da nota fiscal.',
        ],
        'descCustom' => [
            'type' => 'text',
            'label' => 'Descrição Adicional',
            'name' => 'descCustom',
            'id' => 'descCustom_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Adicione uma informação personalizada na nota fiscal. Esta informação será exibida após a descrição.',
        ],
        'footer' => [
            'type' => 'text',
            'label' => 'Footer (legado)',
            'name' => 'footer',
            'id' => 'footer_Field',
            'required' => false,
            'disabled' => true,
            'description' => 'Legado',
        ],
        'iss_held' => [
            'type' => 'text',
            'label' => 'Retenção de ISS',
            'name' => 'iss_held',
            'id' => 'issHeld_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Alíquota (%) padrão de retenção de ISS. Será aplicado a todos os produtos/serviços.',
        ],
        'discount_items' => [
            'type' => 'checkbox',
            'label' => 'Deduzir descontos da fatura na NF',
            'name' => 'discount_items',
            'id' => 'discountItems_Field',
            'required' => false,
            'disabled' => false,
            'description' => 'Deduzir descontos/abatimentos existentes na fatura do valor total da nota a ser emitida.',
        ],
    );

    /**
     * Campos que podem ser migrados de versões anteriores a 2.0
     * @var array campos que podem ser migrados
     */
    public $migrationFields = array(
        'api_key',
        'company_id',
        'service_code',
        'issue_note_default_cond',
        'issue_note_conditions',
        'insc_municipal',
        'cpf_camp',
        'cnpj_camp',
        'rps_number',
        'gnfe_email_nfe_config',
        'initial_date',
        'last_cron',
        'issue_note_after',
        'cancel_invoice_cancel_nfe',
        'debug',
        'tax',
        'InvoiceDetails',
        'send_invoice_url',
        'descCustom',
        'NFEioEnvironment',
    );

    public $serviceInvoicesIssueConditions = 'Quando a fatura é gerada,Quando a fatura é paga,Seguir configuração do módulo NFE.io';

    /**
     * Retorna coleção dos campos que podem ser migrados como chaves.
     *
     * @return array campos possíveis de migração
     * @example 'nome_campo' => true
     */
    public function getMigrationFields()
    {
        $fields = [];

        foreach ($this->migrationFields as $key) {
            $fields[$key] = true;
        }
        return $fields;
    }

    function getModelClass()
    {
        return __NAMESPACE__ . '\Repository';
    }

    public function fieldDeclaration()
    {
        return $this->fieldDeclaration;
    }

    public function tableName()
    {
        return $this->tableName;
    }

    /**
     * Responsável por iniciar alguns valores padrões nas chaves do módulo
     */
    public function initDefaultValues()
    {


        $functions = new \NFEioServiceInvoices\Legacy\Functions();
        $config = new \NFEioServiceInvoices\Configuration();
        $storageKey = $config->getStorageKey();
        $storage = new \WHMCSExpert\Addon\Storage($storageKey);

        // inicia valores para chave issue_note_conditions
        $storage->set('issue_note_conditions', $this->serviceInvoicesIssueConditions);
        // $functions->gnfe_insert_issue_nfe_cond_in_database();
        // define 'on' como padrão para discount_items
        $storage->set('discount_items', 'on');
        // inicia valor para a chave initial_date
        $date = date('Y-m-d H:i:s');
        $storage->set('initial_date', $date);

    }

    /**
     * Retorna a lista com as chaves do modelo de dados dos campos de configuração
     *
     * @return array chaves do modelo de dados
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Retorna todos os campos existentes para configuração
     * @return array|array[] campos existentes para configuração
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Retorna a coleção de campos mandatários de configuração/preenchimento do módulo
     * @return array array com os nomes dos campos obrigatórios
     */
    public function getMandatoryFields()
    {
        return array_intersect_key($this->getFields(), $this->getMandatoryFieldsKeys());
    }

    /**
     * Retorna os campos mandatários como chaves da coleção
     * @return array campos mandatários
     * @example [api_key => true, company_id => true]
     */
    public function getMandatoryFieldsKeys()
    {
        $fields = [];
        foreach ($this->mandatoryFields as $mandatoryField) {
            $fields[$mandatoryField] = true;
        }

        return $fields;
    }

    /**
     * Verifica se a coleção informada possui campos mandatários e os retorna com seus respectivos valores
     * @param array $vars coleção a ser verificada
     * @return array|null campos mandatários existentes ou null caso $vars não seja um array
     */
    public function hasMandatoryFields($vars)
    {

        if (!is_array($vars)) {
            return null;
        }

        return array_intersect_key($vars, $this->getMandatoryFieldsKeys());
    }

    /**
     * Computa e retorna os campos mandatários que não possuem um valor configurado com base no array fornecido
     * @param $vars array coleção de dados a ser verificada da ausência dos dados mandatários
     * @return  array|false retorna os campos mandatários ausentes
     */
    public function missingMandatoryFields($vars)
    {
        if (!is_array($vars)) {
            return false;
        }

        return array_diff_key($this->getMandatoryFields(), $vars);

    }

    public function seed_service_invoices_issue_conditions()
    {
        $previousConditions = $this->get('issue_note_conditions');
    }
}