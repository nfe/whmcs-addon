<?php

namespace NFEioServiceInvoices\Models\ProductCode;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Classe responsável pela definição do modelo de dados
 * da tabela mod_nfeio_si_productcode
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{

    public $tableName = 'mod_nfeio_si_productcode';

    function getModelClass()
    {
        return __NAMESPACE__ . '\ProductCode';
    }

    /**
     * verifica se table possui algum registro já inserido
     */
    public function get()
    {
        return Capsule::table($this->tableName)->first();
    }

    /**
     * Derruba a tabela
     */
    public function dropProductCodeTable()
    {
        if (Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }

    /**
     * Cria a tabela no banco de dados
     */
    public function createProductCodeTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->create($this->tableName, function($table)
            {
                $table->increments('id');
                $table->integer('product_id');
                $table->integer('code_service');
                $table->timestamp('create_at');
                $table->timestamp('update_at');
                $table->integer('ID_user');
            });
        }
    }
}