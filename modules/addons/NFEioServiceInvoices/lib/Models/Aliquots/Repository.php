<?php

namespace NFEioServiceInvoices\Models\Aliquots;

use WHMCS\Database\Capsule;

class Repository extends \WHMCSExpert\mtLibs\models\Repository
{
    public $tableName = 'mod_nfeio_si_aliquots';
    public $fieldDeclaration = array(
        'id',
        'code_service',
        'iss_held',
        'created_at',
        'updated_at',
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

    public function get()
    {
        return Capsule::table($this->tableName())->select()->get();
    }

    public function aliquotsDataTable()
    {
        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        return Capsule::table($productCodeRepo->tableName())
            ->leftJoin($this->tableName(), "{$productCodeRepo->tableName()}.code_service", '=', "{$this->tableName()}.code_service")
            ->groupBy("{$productCodeRepo->tableName()}.code_service")
            ->select("{$this->tableName()}.id", "{$this->tableName()}.iss_held", "{$productCodeRepo->tableName()}.code_service")
            ->get();
    }

    public function save($data)
    {
        try {
            return Capsule::table($this->tableName)->updateOrInsert(
                [ 'code_service' => $data['code_service'] ],
                [
                    'iss_held' => $data['iss_held']
                ]
            );
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function delete($data)
    {
        return Capsule::table($this->tableName())->where('id', '=', $data['id'])->delete();
    }

    /**
     * Derruba a tabela
     */
    public function drop()
    {
        Capsule::schema()->dropIfExists($this->tableName);
    }

    /**
     * Cria a tabela mod_nfeio_si_aliquots para registro das aliquotas e retenções.
     * Esta tabela será responsável por conter todos os registros de aliquotas e retenções vinculadas aos
     * códigos de serviços personalizados.
     * @return void
     */
    public function createAliquotsTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName))
        {
            Capsule::schema()->create($this->tableName, function($table)
            {
                $table->increments('id');
                //codigo o serviço que será viculado
                $table->string('code_service', 10);
                // retenção de ISS
                $table->float('iss_held', 5, 2)->nullable();
                $table->timestamp('created_at');
                $table->timestamp('updated_at');
            });
        }
    }

    /**
     * Retorna a aliquota de retenção de ISS com base no código do serviço
     * @param $serviceCode string código do serviço
     * @return mixed valor de retenção de ISS
     */
    public function getIssHeldByServiceCode($serviceCode)
    {
        $issHeld = Capsule::table($this->tableName)->where('code_service', '=', $serviceCode)->value('iss_held');
        if (is_null($issHeld)) {
            return null;
        } else {
            return floatval($issHeld);
        }
    }
}