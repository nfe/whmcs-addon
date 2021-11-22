<?php

namespace NFEioServiceInvoices\Models\ServiceInvoices;

use Illuminate\Database\Capsule\Manager as Capsule;


/**
 * Classe responsável pela definição do modelo de dados
 * da tabela mod_nfeio_si_serviceinvoices
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{

    public $tableName = 'mod_nfeio_si_serviceinvoices';
    public $fieldDeclaration = array(
        'invoice_id',
        'user_id',
        'nfe_id',
        'status',
        'services_amount',
        'environment',
        'issue_note_conditions',
        'flow_status',
        'pdf',
        'rpsSerialNumber',
        'rpsNumber',
        'created_at',
        'updated_at',
        'service_code',
        'tics',
    );

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
     * verifica se table possui algum registro já inserido
     */
    public function get()
    {
        return Capsule::table($this->tableName)->first();
    }

    /**
     * Realiza um join entre produtos e códigos personalizados de serviços
     * e estrutura os dados para a dataTable
     * @return array
     */
    public function dataTable()
    {
        return Capsule::table($this->tableName)
            ->leftJoin('tblclients', "{$this->tableName}.user_id", '=', 'tblclients.id')
            ->orderBy("{$this->tableName}.id", 'desc')
            ->select("{$this->tableName}.*", 'tblclients.firstname', 'tblclients.lastname', 'tblclients.companyname')
            ->get();
    }

    /**
     * Cria a tabela no banco de dados
     */
    public function createServiceInvoicesTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->create($this->tableName, function ($table)
            {
                // incremented id
                $table->increments('id');
                // whmcs info
                $table->string('invoice_id');
                $table->string('user_id');
                $table->string('nfe_id');
                $table->string('status');
                $table->decimal('services_amount',$precision = 16,$scale = 2);
                $table->string('environment');
                $table->string('issue_note_conditions');
                $table->string('flow_status');
                $table->string('pdf');
                $table->string('rpsSerialNumber');
                $table->string('rpsNumber');
                $table->timestamp('created_at');
                $table->timestamp('updated_at');
                $table->string('service_code')->nullable(true);
                $table->string('tics')->nullable(true);
            });
        }
    }

    /**
     * Derruba a tabela
     */
    public function dropServiceInvoicesTable()
    {
        if (Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }
}