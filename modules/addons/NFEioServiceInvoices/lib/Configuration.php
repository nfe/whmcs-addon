<?php

namespace NFEioServiceInvoices;

use Plasticbrain\FlashMessages\FlashMessages;

final class Configuration extends \WHMCSExpert\mtLibs\process\AbstractConfiguration
{
    public $debug = false;

    public $systemName = 'NFEioServiceInvoices';

    public $moduleName = 'NFEioServiceInvoices';

    public $name = 'NFE.io NFSe';

    public $author = '<a title="NFE.io Nota Fiscal WHMCS" href="https://github.com/nfe/whmcs-addon/" target="_blank" ><img src="data:image/png;base64,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"></a>';

    public $description = 'Módulo NFE.io para Notas Fiscais de Serviços';

    public $clientAreaName = 'Notas Fiscais';

    private $encryptHash = '';

    public $version = '3.0.0';

    public $tablePrefix = 'mod_nfeio_si_';

    public $storageKey = 'NFEioServiceInvoices';

    public function __construct()
    {
        $this->setStorageKey($this->storageKey);
        $this->setModuleName($this->moduleName);
        $this->setSystemName($this->systemName);
        $this->setName($this->name);
        $this->setDescription($this->description);
        $this->setClientAreaName($this->clientAreaName);
        $this->setVersion($this->version);
        $this->setTablePrefix($this->tablePrefix);
    }

    /**
     * Addon module visible in module
     *
     * @return array
     */
    public function getAddonMenu()
    {
        return array(
            'apiConfiguration' => array(
                'icon' => 'fa fa-key',
            ),
            'productsCreator' => array(
                'icon' => 'fa fa-magic',
            ),
            'productsConfiguration' => array(
                'icon' => 'fa fa-edit',
            ),
            'importSSLOrder' => array(
                'icon' => 'fa fa-download',
            ),
            'userCommissions' => array(
                'icon' => 'fa fa-user-plus',
            ),
        );
    }

    /**
     * Addon module visible in client area
     *
     * @return array
     */
    public function getClienMenu()
    {
        return array(
            'Orders' => array(
                'icon' => 'glyphicon glyphicon-home'
            ),
            /* 'shared'     => array
              (
              'icon' => 'fa fa-key'
              ),
              'product'    => array
              (
              'icon' => 'fa fa-key'
              ),
              'categories' => array
              (
              'icon' => 'glyphicon glyphicon-th-list'
              ) */
        );
    }

    /**
     * Provisioning menu visible in admin area
     *
     * @return array
     */
    public function getServerMenu()
    {
        return array(
            'configuration' => array(
                'icon' => 'glyphicon glyphicon-cog'
            )
        );
    }

    /**
     * Return names of WHMCS product config fields
     * required if you want to use default WHMCS product configuration
     * max 20 fields
     *
     * if you want to use own product configuration use example
     * /models/customWHMCS/product to define own configuration model
     *
     * @return array
     */
    public function getServerWHMCSConfig()
    {
        return array(
            'text_name'
        , 'text_name2'
        , 'checkbox_name'
        , 'onoff'
        , 'pass'
        , 'some_option'
        , 'some_option2'
        , 'radio_field'
        );
    }

    /**
     * Addon module configuration visible in admin area. This is standard WHMCS configuration
     *
     * @return array
     */
    public function getAddonWHMCSConfig()
    {
        return [
            'api_key' => [
                'FriendlyName' => 'Chave de Acesso',
                'Type' => 'text',
                'Description' => '<a href="https://app.nfe.io/account/apikeys" style="text-decoration:underline;" target="_blank">Obter chave de acesso</a>',
            ],
            'NFEioEnvironment' => [
                'FriendlyName' => 'Ambiente de Desenvolvimento',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Habilitar o módulo em ambiente de desenvolvimento.',
            ],
            'debug' => [
                'FriendlyName' => 'Modo Depuração',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Habilitar o módulo em modo depuração (debug).',
            ],
        ];
    }

    /**
     * Verifica se todos os campos mandatários estão preenchidos ou redireciona para ação 'Configuration'
     * com mensagem de erro dos campos ausentes.
     *
     * @param $vars
     */
    public function verifyMandatoryFields($vars, $returnMissingFields = false, $redirect = false)
    {
        $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
        $mandatoryFields = $moduleConfigurationRepo->getMandatoryFields();
        $missingFields = $moduleConfigurationRepo->missingMandatoryFields($vars);
        $presentFields = $moduleConfigurationRepo->hasMandatoryFields($vars);
        $emptyFields = [];

        foreach ($presentFields as $key => $value) {
            if ($value === '') {
                //$emptyFields[$key] = $mandatoryFields[$key];
                $missingFields[$key] = $mandatoryFields[$key];
            }
        }

        if (count($missingFields) > 0) {
            $msg = new FlashMessages();
            if ($redirect) {
                $msg->warning("Você foi redirecionado para o menu <b>Configurações</b>", "{$vars['modulelink']}&action=Configuration");
            } else {
                foreach ($missingFields as $key => $value) {
                    $msg->error("Campo obrigatório <b>{$value['label']}</b> está ausente.", null, true);

                    /*if ($redirect && $returnMissingFields === false) {
                        end($missingFields);
                        if ($key === key($missingFields)) {
                            $msg->warning("Você foi redirecionado para o menu <b>Configurações</b>", "{$vars['modulelink']}&action=Configuration");
                        }
                    }*/
                }
            }
        }

        if ($returnMissingFields) {
            return $missingFields;
        }
    }

    /**
     * Rotinas executadas durante a ativação do módulo
     */
    public function activate()
    {
        // Rotinas de ativação da model serviceInvoices (tabela serviceinvoices)
        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        // verifica e realiza possiveis migrações durante o processo de ativação para a model ServiceInvoices
        \NFEioServiceInvoices\Migrations\Migrations::migrateServiceInvoices();
        // executa as rotinas de sql para a model ServiceInvoices
        $serviceInvoicesRepo->createServiceInvoicesTable();
        // garante que em uma migração de v1.4 para v2.1 as novas colunas estejam presentes
        $serviceInvoicesRepo->upgrade_to_2_1_0();

        // rotinas de ativação da model ProductCode (tabela productcode)
        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        // verifica e realiza possiveis migrações durante o processo de ativação para a model ProductCode
        \NFEioServiceInvoices\Migrations\Migrations::migrateProductCodes();
        // executa as rotinas de sql para a model ProductCode
        $productCodeRepo->createProductCodeTable();
        //$productCodeRepo->upgrade_to_2_1_0();

        // rotinas de ativação da model ClientConfiguration (tabela custom_configs)
        $clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        // verifica e realiza possiveis migrações durante o processo de ativação para a model ClientConfiguration
        \NFEioServiceInvoices\Migrations\Migrations::migrateClientsConfigurations();
        // executa as rotinas de sql para a model ClientConfiguration
        $clientConfigurationRepo->createClientCustomConfigTable();

        // Aliquots Model
        $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
        // cria a tabela para retenção de aliquotas
        $aliquotsRepo->createAliquotsTable();

        // Migração das configurações do módulo versão inferior a 2
        \NFEioServiceInvoices\Migrations\Migrations::migrateConfigurations();

        // rotinas de ativação para as configurações do módulo
        $moduleConfigurationRepo = new Models\ModuleConfiguration\Repository();
        // inicia os valores padrões nas configurações do módulo
        $moduleConfigurationRepo->initDefaultValues();

        // v3.0.0
        // inicia tabela para armazenar as empresas
        $companyRepository = new Models\Company\Repository();
        $companyRepository->createTable();

        // v3.0.0
        // inicia tabela de associacao de client a company_id
        $clientCompanyRepository = new Models\ClientCompany\Repository();
        $clientCompanyRepository->createTable();
    }

    public function deactivate()
    {
        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        // não derruba as tabelas de notas ao desativar o módulo por segurança
        // $serviceInvoicesRepo->dropServiceInvoicesTable();

        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        // não derruba as tabelas de código de serviços personalizados ao desativar por segurança
        // $productCodeRepo->dropProductCodeTable();

        $clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        // não derruba a tabela com configurações persoanlizadas de emissão por segurança
        // $clientConfigurationRepo->dropProductCodeTable();
    }

    public function upgrade($vars)
    {
        $currentlyInstalledVersion = $vars['version'];
        // upgrade to 2.1
        if (version_compare($currentlyInstalledVersion, '2.1.0', 'lt')) {
            $serviceInvoiceRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
            $serviceInvoiceRepo->upgrade_to_2_1_0();
            $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
            $aliquotsRepo->createAliquotsTable();
        }
        // versões menores ou iguais a 2.1.3
        if (version_compare($currentlyInstalledVersion, '2.1.3', 'le')) {
            $productRepo = new Models\ProductCode\Repository();
            $aliquotsRepo = new \NFEioServiceInvoices\Models\Aliquots\Repository();
            $serviceInvoiceRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();

            $productRepo->update_servicecode_var_limit();
            $aliquotsRepo->update_servicecode_var_limit();
            $serviceInvoiceRepo->update_servicecode_var_limit();
            /**
             * @see https://github.com/nfe/whmcs-addon/issues/134
             */
        }

        /**
         * Atualiza as colunas de timestamp para a versão inferior a 2.1.8
         * nas tabelas informadas.
         *
         * @see https://github.com/nfe/whmcs-addon/issues/156
         */
        if(version_compare($currentlyInstalledVersion, '2.1.8', 'le')) {

            // atualiza o nome da coluna de timestamp para a tabela productcode
            \NFEioServiceInvoices\Migrations\Migrations::changeProductCodeTimestampColumnsName();

            // altera as colunas de timestamp para as tabelas
            \NFEioServiceInvoices\Migrations\Migrations::migrateTimestampColumns('mod_nfeio_si_productcode');
            \NFEioServiceInvoices\Migrations\Migrations::migrateTimestampColumns('mod_nfeio_si_serviceinvoices');
            \NFEioServiceInvoices\Migrations\Migrations::migrateTimestampColumns('mod_nfeio_si_aliquots');

        }

        /**
         * Atualiza tabelas para versao com suporte a muiltiempresa.
         * e realiza migracoes necessarias.
         *
         * @see https://github.com/nfe/whmcs-addon/issues/163
         * @version 3.0
         */
        if (version_compare($currentlyInstalledVersion, '3.0.0', 'lt')) {

            // inicia tabela para armazenar as empresas
            $companyRepository = new Models\Company\Repository();
            $companyRepository->createTable();
            // coleta todos os dados vinculados a empresa já configurada
            $companyData = Addon::I()->loadAddonData();
            $company_id = $companyData->company_id;
            // busca o nome da empresa na API da NFe.io conforme o company_id já existente
            $nfeio = new \NFEioServiceInvoices\NFEio\Nfe();
            $company_details = $nfeio->getCompanyDetails($company_id);
            $company_name = strtoupper($company_details->name);
            $company_taxnumber = $company_details->federalTaxNumber;
            // salva os dados da empresa emissora existente na nova tabela
            $companyRepository->save(
                $company_id,
                $company_taxnumber,
                $company_name,
                $companyData->service_code,
                $companyData->iss_held,
                true
            );

            // inicia tabela de associacao de client a company_id
            $clientCompanyRepository = new Models\ClientCompany\Repository();
            $clientCompanyRepository->createTable();

            // altera as tabelas necessárias com a nova coluna company_id
            // codigos de produtos
            \NFEioServiceInvoices\Migrations\Migrations::addCompanyIdColumn('mod_nfeio_si_productcode');
            // aliquotas
            \NFEioServiceInvoices\Migrations\Migrations::addCompanyIdColumn('mod_nfeio_si_aliquots');
            // notas fiscais
            \NFEioServiceInvoices\Migrations\Migrations::addCompanyIdColumn('mod_nfeio_si_serviceinvoices');

            // insere o company_id na coluna dos registros já existentes na tabela
            // 'mod_nfeio_si_productcode'
            \NFEioServiceInvoices\Migrations\Migrations::addCompanyIdRecord($company_id, 'mod_nfeio_si_productcode');
            // 'mod_nfeio_si_aliquots'
            \NFEioServiceInvoices\Migrations\Migrations::addCompanyIdRecord($company_id, 'mod_nfeio_si_aliquots');
            // mod_nfeio_si_serviceinvoices
            \NFEioServiceInvoices\Migrations\Migrations::addCompanyIdRecord($company_id, 'mod_nfeio_si_serviceinvoices');

        }
    }
}
