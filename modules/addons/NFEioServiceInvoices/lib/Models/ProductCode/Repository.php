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
    public $fieldDeclaration = array(
        'id',
        'product_id',
        'code_service',
        'create_at',
        'update_at',
        'ID_user',
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
     * Realiza um join entre produtos e códigos personalizados de serviços
     * e estrutura os dados para a dataTable
     * @return array
     */
    public function dataTable()
    {
        return Capsule::table('tblproducts')
            ->leftJoin($this->tableName, 'tblproducts.id', '=', $this->tableName.'.product_id')
            ->orderByDesc('tblproducts.id')
            ->select('tblproducts.id', 'tblproducts.name', $this->tableName.'.code_service')
            ->get()
            ->toArray();
    }

    public function save($data)
    {
        /*if (!in_array( 'product_id', $data) && !in_array('service_code', $data)) {
            return false;
        }*/

        try {
            return Capsule::table($this->tableName)->updateOrInsert(
                [ 'product_id' => $data['product_id'] ],
                [
                    'code_service' => $data['service_code'],
                    'ID_user' => 1,
                ]
            );
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function delete($data)
    {
        try {
            return Capsule::table($this->tableName)
                ->where('product_id', '=',  $data['product_id'])
                ->delete();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
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