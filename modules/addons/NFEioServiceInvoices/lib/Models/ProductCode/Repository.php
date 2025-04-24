<?php

namespace NFEioServiceInvoices\Models\ProductCode;

use NFEioServiceInvoices\Helpers\Timestamp;
use WHMCS\Database\Capsule;

/**
 * Classe responsável pela definição do modelo de dados
 * da tabela mod_nfeio_si_productcode
 *
 * @since 2.0
 * @version 3.0
 * @author Mimir Tech https://github.com/mimirtechco
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{
    public $tableName = 'mod_nfeio_si_productcode';
    public $fieldDeclaration = array(
        'id',
        'product_id',
        'code_service',
        'iss_held',
        'company_id',
        'created_at',
        'updated_at',
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
     * e estrutura os dados para a dataTable, retornando apenas produtos cadastrados
     *
     */
    public function servicesCodeDataTable()
    {
        return Capsule::table('tblproducts')
            ->join($this->tableName(), 'tblproducts.id', '=', "{$this->tableName}.product_id")
            ->orderBy("{$this->tableName()}.id", 'desc')
            ->select('tblproducts.id as product_id',
                'tblproducts.name as product_name',
                "{$this->tableName()}.code_service",
                "{$this->tableName()}.id as record_id",
                "{$this->tableName()}.company_id as company_id"
            )
            ->get();
    }

    public function aliquotsCodesDataTable()
    {
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
                "{$this->tableName()}.code_service as service_code",
                "{$this->tableName()}.company_id",
                "{$companyRepo->tableName()}.company_name",
                "{$companyRepo->tableName()}.tax_number as company_tax_number"

            )
            ->orderBy("{$this->tableName()}.id", 'desc')
            ->groupBy(
                "{$this->tableName()}.code_service",
                "{$this->tableName()}.company_id"
            )
            ->get();

    }

    public function save($productId, $serviceCode, $companyId)
    {
        $data = [
            'product_id' => $productId,
            'code_service' => $serviceCode,
            'company_id' => $companyId,
            'ID_user' => 1,
            'updated_at' => Timestamp::currentTimestamp(),  // campo updated_at sempre atualizado
        ];

        // Se o registro não existir, adiciona o campo 'created_at'
        $exists = Capsule::table($this->tableName)
            ->where('product_id', $productId)
            ->where('company_id', $companyId)
            ->exists();
        if (!$exists) {
            $data['created_at'] = Timestamp::currentTimestamp();
        }

        try {
            return Capsule::table($this->tableName)->updateOrInsert(
                ['product_id' => $data['product_id'], 'company_id' => $data['company_id']],
                $data
            );
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function delete($data)
    {
        try {
            if (!empty($data['iss_held'])) {
                return Capsule::table($this->tableName)
                    ->where('product_id', '=', $data['product_id'])
                    ->update(['code_service' => null]);
            } else {
                return Capsule::table($this->tableName)
                    ->where('id', '=', $data)
                    ->delete();
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function resetRatesAndFees($data)
    {
        try {
            return Capsule::table($this->tableName)
                ->where('product_id', '=', $data['product_id'])
                ->update(['iss_held' => null]);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * Derruba a tabela
     */
    public function dropProductCodeTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }

    /**
     * Cria a tabela no banco de dados
     */
    public function createProductCodeTable()
    {
//        $db = Capsule::connection();
        $schema = Capsule::schema();

        if (!$schema->hasTable($this->tableName)) {
            $schema->create(
                $this->tableName,
                function ($table) {
                    $table->increments('id');
                    $table->integer('product_id');
                    $table->string('code_service', 30);
                    // company_id para multi empresa #163
                    $table->string('company_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();
                    $table->integer('ID_user');

                }
            );

            // Adiciona a coluna updated_at com a configuração de auto update #156
//            $db->statement(sprintf('ALTER TABLE %s CHANGE updated_at updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $this->tableName));

        }
    }

    /**
     * Retorna o código de serviço personalizado para um produto conforme o relid de um serviço e a empresa.
     *
     * @param $relId int o relid de um serviço (packageid)
     * @param $companyId string o company_id da empresa
     * @return mixed código de serviço se existir ou null
     * @version 3.0
     */
    public function getServiceCodeByRelId($relId, $companyId)
    {
        $productId = Capsule::table('tblhosting')
            ->where('id', '=', $relId)
            ->value('packageid');

        $serviceCode = Capsule::table($this->tableName)
            ->where('product_id', '=', $productId)
            ->where('company_id', '=', $companyId)
            ->value('code_service');

        return $serviceCode;
    }

    /**
     * Retorna o valor da alíquota de retenção de ISS para um produto de acordo com o relid de um serviço.
     *
     * @param  $relId int o relid de um produto/serviço (packageid)
     * @return float|null alíquota de retenção se existente (%)
     */
    public function getIssHeldByRelId($relId)
    {
        $productId = Capsule::table('tblhosting')->where('id', '=', $relId)->value('packageid');
        return Capsule::table($this->tableName)->where('product_id', '=', $productId)->value('iss_held');
    }

    public function upgrade_to_2_1_0()
    {
        // verifica se a tabela existe
        if (Capsule::schema()->hasTable($this->tableName)) {
            // se não houver coluna adiciona
            if (!Capsule::schema()->hasColumn($this->tableName, 'iss_held')) {
                Capsule::schema()->table(
                    $this->tableName,
                    function ($table) {
                        $table->float('iss_held', 5, 2)->after('code_service')->nullable();
                    }
                );
            }
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
                $db->statement("ALTER TABLE `mod_nfeio_si_productcode` CHANGE `code_service` `code_service` VARCHAR(30) NULL");
            }
        }
    }
}
