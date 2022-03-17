<?php

namespace NFEioServiceInvoices;


use Plasticbrain\FlashMessages\FlashMessages;

final class Configuration extends \WHMCSExpert\mtLibs\process\AbstractConfiguration
{
    public $debug = false;

    public $systemName = 'NFEioServiceInvoices';

    public $moduleName = 'NFEioServiceInvoices';

    public $name = 'NFE.io NFSe';

    public $author = '<a title="NFE.io Nota Fiscal WHMCS" href="https://github.com/nfe/whmcs-addon/" target="_blank" ><img src="../modules/addons/NFEioServiceInvoices/logo.png"></a>';

    public $description = 'Módulo NFE.io para Notas Fiscais de Serviços';

    public $clientAreaName = 'Notas Fiscais';

    private $encryptHash = '';

    public $version = '2.1.0-beta.2';

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
                'FriendlyName' => 'API Key',
                'Type' => 'text',
                'Description' => '<a href="https://app.nfe.io/account/apikeys" style="text-decoration:underline;" target="_blank">Obter chave de acesso</a>',
            ],
            'company_id' => [
                'FriendlyName' => 'ID da Empresa',
                'Type' => 'text',
                'Description' => '<a href="https://app.nfe.io/companies/" style="text-decoration:underline;" target="_blank">Obter ID da empresa</a>',
            ],
            'service_code' => [
                'FriendlyName' => 'Código de Serviço Principal',
                'Type' => 'text',
                'Description' => '<a style="text-decoration:underline;" href="https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/#o-que-e-codigo-de-servico" target="_blank">O que é Código de Serviço?</a>',
            ],
            'NFEioEnvironment' => [
                'FriendlyName' => 'Ambiente de desenvolvimento',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Habilitar ambiente de desenvolvimento da NFE.io',
            ],
            'debug' => [
                'FriendlyName' => 'Debug',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Habilitar o modo debug do módulo',
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
        $productCodeRepo->upgrade_to_2_1_0();

        // rotinas de ativação da model ClientConfiguration (tabela custom_configs)
        $clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        // verifica e realiza possiveis migrações durante o processo de ativação para a model ClientConfiguration
        \NFEioServiceInvoices\Migrations\Migrations::migrateClientsConfigurations();
        // executa as rotinas de sql para a model ClientConfiguration
        $clientConfigurationRepo->createClientCustomConfigTable();

        // Migração das configurações do módulo versão inferior a 2
        \NFEioServiceInvoices\Migrations\Migrations::migrateConfigurations();

        // rotinas de ativação para as configurações do módulo
        $moduleConfigurationRepo = new Models\ModuleConfiguration\Repository();
        // inicia os valores padrões nas configurações do módulo
        $moduleConfigurationRepo->initDefaultValues();


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
            $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
            $productCodeRepo->upgrade_to_2_1_0();
        }
    }

}
