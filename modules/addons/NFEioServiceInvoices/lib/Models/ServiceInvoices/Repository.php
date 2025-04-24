<?php

namespace NFEioServiceInvoices\Models\ServiceInvoices;

use Illuminate\Database\Capsule\Manager as Capsule;
use NFEioServiceInvoices\Helpers\Timestamp;

/**
 * Classe responsável pela definição do modelo de dados e operações
 * na tabela mod_nfeio_si_serviceinvoices
 *
 * @version 3.0
 * @since 2.0
 * @author Mimir Tech https://github.com/mimirtechco
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{
    public $tableName = 'mod_nfeio_si_serviceinvoices';
    public $fieldDeclaration = array(
        'invoice_id',
        'user_id',
        'nfe_id',
        'nfeio_external_id',
        'status',
        'services_amount',
        'iss_held',
        'description',
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
        'company_id',
    );
    protected $_limit = 10;

    /**
     * Define o máximo limite de registros de uma consulta
     *
     * @param null $limit
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
    }

    /**
     * Retorna o máximo limite definido de registros para uma consulta
     *
     * @return null
     */
    public function getLimit()
    {
        return $this->_limit;
    }

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
     */
    public function dataTable()
    {
        $companyRepository = new \NFEioServiceInvoices\Models\Company\Repository();
        return Capsule::table($this->tableName)
            ->leftJoin('tblclients', "{$this->tableName()}.user_id", '=', 'tblclients.id')
            ->leftJoin("{$companyRepository->tableName()}", "{$this->tableName()}.company_id", '=', "{$companyRepository->tableName()}.company_id")
            ->orderBy("{$this->tableName}.id", 'desc')
            ->select(
                "{$this->tableName}.*",
                'tblclients.firstname',
                'tblclients.lastname',
                'tblclients.companyname',
                "{$companyRepository->tableName()}.company_name as emissor_name",
                "{$companyRepository->tableName()}.tax_number as emissor_tax_number",
            )
            ->get();
    }

    /**
     * Cria a tabela no banco de dados
     */
    public function createServiceInvoicesTable()
    {
        $db = Capsule::connection();
        $schema = Capsule::schema();

        if (!$schema->hasTable($this->tableName)) {
            $schema->create(
                $this->tableName,
                function ($table) {
                    // incremented id
                    $table->increments('id');
                    // whmcs info
                    $table->string('invoice_id');
                    $table->string('user_id');
                    $table->string('nfe_id');
                    $table->string('nfe_external_id');
                    $table->string('status');
                    $table->decimal('services_amount', $precision = 16, $scale = 2);
                    $table->decimal('iss_held', 16, 2);
                    $table->text('nfe_description');
                    $table->string('environment');
                    $table->string('issue_note_conditions');
                    $table->string('flow_status');
                    $table->string('pdf');
                    $table->string('rpsSerialNumber');
                    $table->string('rpsNumber');
                    // company_id para multi empresa #163
                    $table->string('company_id')->nullable();
                    $table->timestamp('created_at')->nullable();
                    $table->timestamp('updated_at')->nullable();
                    $table->string('service_code', 30)->nullable(true);
                    $table->string('tics')->nullable(true);

                }
            );
        }

        // Adiciona a coluna updated_at com a configuração de auto update #156
//        $db->statement(sprintf('ALTER TABLE %s CHANGE updated_at updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $this->tableName));

    }

    /**
     * Derruba a tabela
     */
    public function dropServiceInvoicesTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }

    /**
     * Retorna as notas locais existentes para uma determinada fatura
     *
     * @param  $id string ID da Fatura
     * @return \Illuminate\Support\Collection dados da nota local
     */
    public function getServiceInvoicesById($id)
    {
        return Capsule::table($this->tableName)
            ->where('invoice_id', '=', $id)
            ->orderBy('id', 'desc')
            ->limit($this->_limit)
            ->get();
    }

    /**
     * Retorna o total de notas locais registradas para uma determinada fatura.
     *
     * @param  $id string ID da fatura
     * @return int total de registros encontrados
     */
    public function getTotalById($id)
    {
        return Capsule::table($this->tableName)
            ->where('invoice_id', '=', $id)
            ->count();
    }

    public function hasInvoices($id)
    {
        $response = false;
        $total = self::getTotalById($id);

        if ($total > 0) {
            $response = true;
        }
        return $response;
    }

    /**
     * Atualiza as colunas necessarias para a versão 2.1.0
     *
     * @return  void
     * @version 2.1.0
     * @author  Andre Bellafronte
     */
    public function upgrade_to_2_1_0()
    {
        // verifica se a tabela existe antes de qualquer procedimento
        if (Capsule::schema()->hasTable($this->tableName)) {
            // adiciona nova columa nfe_external_id
            if (!Capsule::schema()->hasColumn($this->tableName, 'nfe_external_id')) {
                Capsule::schema()->table(
                    $this->tableName,
                    function ($table) {
                        $table->string('nfe_external_id')->after('nfe_id')->nullable();
                    }
                );
            }
            // adiciona nova coluna nfe_description
            if (!Capsule::schema()->hasColumn($this->tableName, 'nfe_description')) {
                Capsule::schema()->table(
                    $this->tableName,
                    function ($table) {
                        $table->text('nfe_description')->after('services_amount')->nullable();
                    }
                );
            }
            // adiciona nova coluna iss_held que conterá o valor em R$ da retenção do ISS para a NF
            if (!Capsule::schema()->hasColumn($this->tableName, 'iss_held')) {
                Capsule::schema()->table(
                    $this->tableName,
                    function ($table) {
                        $table->decimal('iss_held', 16, 2)->after('services_amount')->nullable();
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
            if (Capsule::schema()->hasColumn($this->tableName, 'service_code')) {
                $db = Capsule::connection();
                // atualiza o limite de caracteres para 30
                $db->statement("ALTER TABLE `mod_nfeio_si_serviceinvoices` CHANGE `service_code` `service_code` VARCHAR(30) NULL");
            }
        }
    }

    /**
     * Atualiza o status e flow status de uma NF (Nota Fiscal) pelo seu external id
     *
     * @param string $externalId ID externo da Nf
     * @param string $status O novo status da Nf
     * @param string|null $flowStatus O novo flow status da Nf (opcional)
     *
     * @return bool Retorna o número de linhas afetadas ou false em caso de erro
     */
    public function updateNfStatusByExternalId($externalId, $status, $flowStatus = null)
    {
        if (is_null($externalId) || is_null($status)) {
            throw new \InvalidArgumentException('Invalid argument values.');
        }

        $data = [
            'status' => $status,
        ];

        if ($flowStatus) {
            $data['flow_status'] = $flowStatus;
        }

        // adiciona a data de atualização #156
        $data['updated_at'] = Timestamp::currentTimestamp();

        try {
            Capsule::table($this->tableName)
                ->where('nfe_external_id', $externalId)
                ->update($data);
            return true;
        } catch (\Exception $e) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'updateNfStatusByExternalId_error',
                $data,
                $e->getMessage(),
                $e->getTraceAsString()
            );
            return false;
        }

    }

    /**
     * Atualiza o status e flow status de uma NF (Nota Fiscal) pelo seu id
     *
     * @param $nfeId string ID da Nf
     * @param $status string O novo status da Nf
     * @param $flowStatus string|null O novo flow status da Nf (opcional)
     * @return bool|int Retorna o número de linhas afetadas ou false em caso de erro
     */
    public function updateNfStatusByNfeId($nfeId, $status, $flowStatus = null)
    {
        if (is_null($nfeId) || is_null($status)) {
            throw new \InvalidArgumentException('Invalid argument values.');
        }

        $data = [
            'status' => $status,
        ];

        if ($flowStatus) {
            $data['flow_status'] = $flowStatus;
        }

        // adiciona a data de atualização #156
        $data['updated_at'] = Timestamp::currentTimestamp();

        try {
            Capsule::table($this->tableName)
                ->where('nfe_id', $nfeId)
                ->update($data);

            return true;
        } catch (\Exception $e) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'updateNfStatusByNfeId_error',
                $data,
                $e->getMessage(),
                $e->getTraceAsString()
            );
            return false;
        }

    }

}
