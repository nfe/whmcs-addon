<?php

namespace NFEioServiceInvoices\Models\Aliquots;

use NFEioServiceInvoices\Helpers\Timestamp;
use WHMCS\Database\Capsule;

/**
 * Classe responsável pela definição do modelo de dados
 * para as aliquotas no módulo.
 *
 * @see https://github.com/nfe/whmcs-addon/issues/163
 * @since 2.1
 * @version 3.0
 * @author Mimir Tech https://github.com/mimirtechco
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{
    public $tableName = 'mod_nfeio_si_aliquots';
    public $fieldDeclaration = array(
        'id',
        'code_service',
        'iss_held',
        'company_id',
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

    /**
     * colecao com dados das alicotas e seus respectivos emissores
     *
     * @return \Illuminate\Support\Collection
     * @version 3.0
     */
    public function aliquotsDataTable()
    {
        $productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $companyRepo = new \NFEioServiceInvoices\Models\Company\Repository();
        return Capsule::table($this->tableName())
            ->leftJoin(
                $companyRepo->tableName(),
                "{$this->tableName()}.company_id",
                '=',
                "{$companyRepo->tableName()}.company_id"
            )
            ->select(
                "{$this->tableName()}.id as record_id",
                "{$this->tableName()}.iss_held",
                "{$this->tableName()}.code_service",
                "{$this->tableName()}.company_id",
                "{$companyRepo->tableName()}.company_name",
                "{$companyRepo->tableName()}.tax_number as company_tax_number"
            )
            ->get();
    }

    /**
     * Adiciona nova retencao de aliquota
     *
     * @param $serviceCode
     * @param $issHeld
     * @param $companyId
     * @return bool
     * @version 3.0
     * @since 2.1
     */
    public function new($serviceCode, $issHeld, $companyId)
    {
        $data = [
            'code_service' => $serviceCode,
            'iss_held' => $issHeld,
            'company_id' => $companyId,
            'created_at' => Timestamp::currentTimestamp(),
            'updated_at' => Timestamp::currentTimestamp()
        ];


        try {
            return Capsule::table($this->tableName)->insert($data);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
        return false;
    }

    /**
     * Edita a aliquota de retenção de ISS conforme o id
     *
     * @param $id int id do registro
     * @param $issHeld float valor de retenção de ISS
     * @version 3.0
     * @since 3.0
     */
    public function edit($id, $issHeld)
    {
        $data = [
            'iss_held' => $issHeld,
            'updated_at' => Timestamp::currentTimestamp(), // campo updated_at sempre atualizado
        ];
        try {
            $result = Capsule::table($this->tableName())
                ->where('id', '=', $id)
                ->update($data);
            return $result;
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
        return false;
    }

    /**
     * Remove a aliquota de retenção de ISS
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        return Capsule::table($this->tableName())->where('id', '=', $id)->delete();
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
     *
     * @return void
     */
    public function createAliquotsTable()
    {
        $db = Capsule::connection();
        $schema = Capsule::schema();

        if (!$schema->hasTable($this->tableName)) {
            $schema->create(
                $this->tableName,
                function ($table) {
                    $table->increments('id');
                    //codigo o serviço que será viculado
                    $table->string('code_service', 30);
                    // retenção de ISS
                    $table->float('iss_held', 5, 2)->nullable();
                    // company_id para multi empresa #163
                    $table->string('company_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();
                }
            );

            // Adiciona a coluna updated_at com a configuração de auto update #156
//            $db->statement(sprintf('ALTER TABLE %s CHANGE updated_at updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $this->tableName));
        }
    }

    /**
     * Retorna a aliquota de retenção de ISS com base no código do serviço
     *
     * @param  $serviceCode string código do serviço
     * @param  $companyId   string ID da empresa
     * @return mixed valor de retenção de ISS
     */
    public function getIssHeldByServiceCode($serviceCode, $companyId)
    {
        $issHeld = Capsule::table($this->tableName())
            ->where('code_service', '=', $serviceCode)
            ->where('company_id', '=', $companyId)
            ->value('iss_held');
        if (is_null($issHeld)) {
            return null;
        } else {
            return floatval($issHeld);
        }
    }

    /**
     * Rotina para atualização da quantidade máxima de caracteres permitidos para a coluna code_service.
     *
     * @see     https://github.com/nfe/whmcs-addon/issues/134
     * @version 2.2
     * @since   2.2
     * @author  Andre Bellafronte
     */
    public function update_servicecode_var_limit()
    {
        // verifica se a tabela existe
        if (Capsule::schema()->hasTable($this->tableName)) {
            // verifica se a coluna existe
            if (Capsule::schema()->hasColumn($this->tableName, 'code_service')) {
                $db = Capsule::connection();
                // atualiza o limite de caracteres para 30
                $db->statement("ALTER TABLE `mod_nfeio_si_aliquots` CHANGE `code_service` `code_service` VARCHAR(30) NULL");
            }
        }
    }
}
