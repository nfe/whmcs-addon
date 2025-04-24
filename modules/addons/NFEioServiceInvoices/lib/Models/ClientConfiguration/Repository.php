<?php

namespace NFEioServiceInvoices\Models\ClientConfiguration;

use WHMCS\Database\Capsule;

/**
 * Repositório responsável por interagir com a tabela de configurações personalizadas
 * de clientes para o módulo NFE.io.
 *
 * @since 2.0
 * @version 2.0
 * @author Mimir Tech https://github.com/mimirtechco
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{
    public $tableName = 'mod_nfeio_si_custom_configs';
    public $fieldDeclaration = array(
        'client_id',
        'key',
        'value',
    );

    public function tableName()
    {
        return $this->tableName;
    }

    public function fieldDeclaration()
    {
        return $this->fieldDeclaration;
    }

    public function getModelClass()
    {
        return __NAMESPACE__ . '\Repository';
    }

    public function get()
    {
        return Capsule::table($this->tableName)->first();
    }

    public function dropProductCodeTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }

    /**
     * Cria a tabela mod_nfeio_si_custom_configs responsável por armazenar
     * os registros personalizados de emissão de nota para um cliente
     */
    public function createClientCustomConfigTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create(
                $this->tableName,
                function ($table) {
                    $table->increments('id');
                    $table->integer('client_id');
                    $table->string('key');
                    $table->string('value');
                }
            );
        }
    }

    public function getClientIssueCondition($clientId)
    {

            $value = Capsule::table($this->tableName)
                ->where(
                    [
                    ['client_id', '=' ,$clientId],
                    ['key', '=' ,'issue_nfe_cond']
                    ]
                )
                ->value('value');

        if (is_null($value) or $value === 'Seguir configuração do módulo NFE.io') {
            $issueCondition = 'seguir configuração do módulo nfe.io';
        } else {
            $issueCondition = strtolower($value);
        }

        logModuleCall('nfeio_serviceinvoices', "client_issue_condition", "Customer ID: {$clientId}", "{$issueCondition}" . ' - ' . $value);

        return $issueCondition;
    }
}
