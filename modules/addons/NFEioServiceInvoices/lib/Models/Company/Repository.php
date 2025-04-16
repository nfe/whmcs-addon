<?php

namespace NFEioServiceInvoices\Models\Company;

/**
 * Classe responsável pela definição do modelo de dados
 * para cadastramento de multiplas empresas no módulo.
 * Esta tabela será responsável por conter todos os
 * registros de empresas vinculadas ao cliente.
 *
 * @see https://github.com/nfe/whmcs-addon/issues/163
 * @since 2.3.0
 * @version 2.3.0
 * @author Andre Kutianski <andre@mimirtech.co>
 */
class Repository extends \WHMCSExpert\mtLibs\models\Repository
{

    public $tableName = 'mod_nfeio_si_companies';

    public $fieldDeclaration = array(
        'id',
        'company_id',
        'company_name',
        'service_code',
        'iss_held',
        'default',
        'created_at',
        'updated_at'
    );

    protected $_limit = 10;

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
     * Cria a tabela mod_nfeio_si_companies responsável por armazenar
     * os registros de empresas configuradas no módulo.
     * @return void
     */
    public function createTable()
    {
        if (!\WHMCS\Database\Capsule::schema()->hasTable($this->tableName)) {
            \WHMCS\Database\Capsule::schema()->create($this->tableName, function ($table) {
                $table->increments('id');
                // id da empresana nfe.io
                $table->string('company_id');
                // cnpj da empresa
                $table->string('tax_number', 20);
                // nome da empresa na nfe.io
                $table->string('company_name');
                // codigo de servicao padrao para a empresa
                $table->string('service_code', 30);
                // retencao de isso padrao para a empresa
                $table->float('iss_held', 5, 2)->nullable();
                // campo para definir se a empresa é padrão para emissao
                $table->boolean('default')->default(0);
                // timestamp de criacao e edicao
                $table->timestamps();
            });
        }

    }

    /**
     * Salva o registro de uma nova empresa
     * @param $companyId
     * @param $companyName
     * @param $serviceCode
     * @param $issHeld
     * @param $default
     *
     */
    public function save($companyId, $taxNumber, $companyName, $serviceCode, $issHeld, $default = false)
    {
        // insere ou atualiza registro da empresa
        try {
            $data = [
                'company_id' => $companyId,
                'tax_number' => $taxNumber,
                'company_name' => $companyName,
                'service_code' => $serviceCode,
                'iss_held' => $issHeld,
                'default' => $default,
                'updated_at' => \WHMCS\Database\Capsule::raw('NOW()')
            ];

            if (!\WHMCS\Database\Capsule::table($this->tableName)->where('company_id', '=', $companyId)->exists()) {
                $data['created_at'] = \WHMCS\Database\Capsule::raw('NOW()');
            }

            // se data['default'] for igual a 1, remove o default de todas as outras empresas
            if ($default == 1) {
                \WHMCS\Database\Capsule::table($this->tableName)->where('default', '=', 1)->update(['default' => 0]);
            }

            $result = \WHMCS\Database\Capsule::table($this->tableName)->updateOrInsert(
                ['company_id' => $companyId],
                $data
            );
            return ['status' => true, 'result' => $result];

        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'save_company_error',
                ['company_id' => $companyId, 'service_code' => $serviceCode],
                ['error' => $exception->getMessage()]
            );
            return ['status' => false, 'error' => $exception->getMessage()];
        }
    }

//    public function delete($companyId)
//    {
//        // se empresa for default, nao permite a exclusao
//        $default = \WHMCS\Database\Capsule::table($this->tableName)->where('company_id', '=', $companyId)->value('default');
//        if ($default == 1) {
//            return 'Não é possível excluir a empresa padrão.';
//        }
//        // remove o registro da empresa
//        return \WHMCS\Database\Capsule::table($this->tableName)->where('company_id', '=', $companyId)->delete();
//    }


    public function edit($recordId, $companyName, $serviceCode, $issHeld, $default)
    {
        // atualiza o registro da empresa
        try {
            $data = [
                'company_name' => $companyName,
                'service_code' => $serviceCode,
                'iss_held' => $issHeld,
                'default' => $default,
                'updated_at' => \WHMCS\Database\Capsule::raw('NOW()')
            ];

            if ($default == 1) {
                \WHMCS\Database\Capsule::table($this->tableName)->where('default', '=', 1)->update(['default' => 0]);
            }

            //  se default for 0 mas só existe uma empresa cadastrada, forca a ser empresa padrao (nao pode existir uma unica empresa sem ser a padrao)
            $count = \WHMCS\Database\Capsule::table($this->tableName)->where('default', '=', 0)->count();
            if ($count == 0 && $default == 0) {
                $data['default'] = 1;
            }

            $result = \WHMCS\Database\Capsule::table($this->tableName)->where('id', '=', $recordId)->update($data);
            logModuleCall(
                'nfeio_serviceinvoices',
                'edit_company',
                ['record_id' => $recordId],
                ['result' => $result]
            );
            return array(
                'status' => true,
                'message' => 'Empresa editada com sucesso.'
            );
        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'edit_company_error',
                ['record_id' => $recordId],
                ['error' => $exception->getMessage()]
            );
            return ['status' => false, 'error' => $exception->getMessage()];
        }
    }

    public function delete($companyId)
    {
        try {
            // se empresa for default, nao permite a exclusao
            $default = \WHMCS\Database\Capsule::table($this->tableName)
                ->where('company_id', '=', $companyId)
                ->value('default');
            if ($default == 1) {
                return array(
                    'status' => false,
                    'message' => 'Não é possível excluir a empresa padrão.'
                );
            }
            // remove o registro da empresa
            $result = \WHMCS\Database\Capsule::table($this->tableName)
                ->where('company_id', '=', $companyId)
                ->delete();

            logModuleCall(
                'nfeio_serviceinvoices',
                'delete_company',
                "Empresa {$companyId} excluída com sucesso",
                $result
            );

            return array(
                'status' => true,
                'message' => 'Empresa excluída com sucesso.'
            );
        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'delete_company_error',
                "Erro ao excluir empresa {$companyId}: {$exception->getMessage()}",
                array(
                    'company_id' => $companyId,
                    'error' => $exception->getMessage()
                )
            );
            return $exception->getMessage();
        }
    }


    /**
     * Retorna todos os registros de empresas cadastradas
     * ordenadas por default.
     * @return \Illuminate\Support\Collection
     */
    public function getAll()
    {
        return \WHMCS\Database\Capsule::table($this->tableName)
            ->orderBy('default', 'desc')
            ->select('id', 'company_id', 'tax_number', 'company_name', 'service_code', 'iss_held', 'default')
            ->get();
    }
}