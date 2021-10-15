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

    function getModelClass()
    {
        return __NAMESPACE__ . '\ServiceInvoices';
    }

    /**
     * verifica se table possui algum registro já inserido
     */
    public function get()
    {
        return Capsule::table($this->tableName)->first();
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
                $table->date('created_at');
                $table->date('updated_at');
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