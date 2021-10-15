<?php

namespace NFEioServiceInvoices\Models\ClientConfiguration;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Classe responsável pela definição do modelo de dados
 * da tabela mod_nfeio_si_custom_configs
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{

    public $tableName = 'mod_nfeio_si_custom_configs';

    function getModelClass()
    {
        return __NAMESPACE__ . '\ClientConfiguration';
    }

    public function get()
    {
        return Capsule::table($this->tableName)->first();
    }

    public function dropProductCodeTable()
    {
        if (Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }

    /**
     * Cria a tabela mod_nfeio_si_custom_configs responsável por armazenar
     * os registros personalizados de emissão de nota para um cliente
     */
    public function createClientCustomConfigTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->create($this->tableName, function($table)
            {
                $table->increments('id');
                $table->integer('client_id');
                $table->string('key');
                $table->string('value');
            });
        }
    }

}