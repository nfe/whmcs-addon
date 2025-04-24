<?php

namespace NFEioServiceInvoices\Models\ClientCompany;

use NFEioServiceInvoices\Helpers\Timestamp;
use WHMCS\Database\Capsule;

/**
 * Classe responsável pela definição do modelo de dados
 * para associacao de clientes a empresas emissoras,
 * quando operando em multiempresa.
 *
 * @see https://github.com/nfe/whmcs-addon/issues/163
 * @since 3.0
 * @version 3.0
 * @author Andre Kutianski <andre@mimirtech.co>
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{

    public $tableName = 'mod_nfeio_si_clients_companies';
    public $fieldDeclaration = array(
        'id',
        'client_id',
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

    /**
     * Retorna o company_id associado ao client_id ou nulo
     * @param $clientId int ID do cliente no WHMCS
     *
     */
    public function getCompanyByClientId($clientId)
    {
        $company = Capsule::table($this->tableName())
            ->where('client_id', '=', $clientId)
            ->first();

        if ($company) {
            return $company->company_id;
        }

        return null;
    }

    /**
     * Cria a tabela de dados deste repositorio
     * @return void
     */
    public function createTable()
    {
        if (!\WHMCS\Database\Capsule::schema()->hasTable($this->tableName)) {
            \WHMCS\Database\Capsule::schema()->create($this->tableName, function ($table) {
                $table->increments('id');
                // id do cliente
                $table->integer('client_id');
                // id da empresa associada
                $table->string('company_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Obtém todos os registros de associação entre clientes e empresas emissoras.
     *
     * @return \Illuminate\Support\Collection Lista de registros contendo informações
     *                                         do cliente e da empresa associada.
     */
    public function getAll()
    {
        $companyRepo = new \NFEioServiceInvoices\Models\Company\Repository();

        return Capsule::table('tblclients')
            ->join($this->tableName(), 'tblclients.id', '=', "{$this->tableName()}.client_id")
            ->join($companyRepo->tableName(), "{$this->tableName()}.company_id", '=', "{$companyRepo->tableName()}.company_id")
            ->orderBy("{$this->tableName()}.id", 'desc')
            ->select("{$this->tableName()}.client_id",
                'tblclients.firstname as client_firstname',
                'tblclients.lastname as client_lastname',
                'tblclients.companyname as client_companyname',
                "{$this->tableName()}.company_id",
                "{$this->tableName()}.id as record_id",
                "{$companyRepo->tableName()}.company_name",
                "{$companyRepo->tableName()}.tax_number as company_tax_number",
            )
            ->get();


    }

    /**
     * Cria uma nova associacao de cliente a empresa emissora
     *
     * @param $clientId ID do cliente
     * @param $companyId ID da empresa emissora
     * @version 3.0
     */
    public function new($clientId, $companyId)
    {
        $data = [
            'client_id' => $clientId,
            'company_id' => $companyId,
            'created_at' => Timestamp::currentTimestamp(),
            'updated_at' => Timestamp::currentTimestamp(),

        ];

        // Verifica se a associacao ja existe
        $exists = Capsule::table($this->tableName())
            ->where('client_id', $clientId)
            ->where('company_id', $companyId)
            ->exists();

        if ($exists) {
            return array(
                'status' => false,
                'message' => 'Associacao ja existe',
            );
        }

        try {

            Capsule::table($this->tableName())
                ->insert($data);
            return array(
                'status' => true,
                'message' => 'Associacao criada com sucesso',
            );

        } catch (\Exception $exception) {
            return array(
                'status' => false,
                'message' => $exception->getMessage(),
            );
        }
    }

    /**
     * Exclui um registro de associação entre cliente e empresa emissora.
     *
     * @param int $recordId ID do registro a ser excluído.
     * @return array|string Retorna um array com o status e a mensagem de sucesso,
     *                      ou uma string com a mensagem de erro em caso de exceção.
     */
    public function delete($recordId)
    {
        try {

            $result = \WHMCS\Database\Capsule::table($this->tableName())
                ->where('id', '=', $recordId)
                ->delete();

            return array(
                'status' => true,
                'message' => 'Registro excluído com sucesso.'
            );
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }


}