<?php

namespace NFEioServiceInvoices\Models\Company;

/**
 * Classe responsável pela definição do modelo de dados
 * para cadastramento de multiplas empresas no módulo.
 * Esta tabela será responsável por conter todos os
 * registros de empresas vinculadas ao cliente.
 *
 * @see https://github.com/nfe/whmcs-addon/issues/163
 * @since 3.0
 * @version 3.0
 * @author Mimir Tech https://github.com/mimirtechco
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
        'nbs_code',
        'operation_indicator',
        'class_code',
        'tax_number',
        'default',
        'created_at',
        'updated_at'
    );

    protected $_limit = 10;

    public function getModelClass()
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
     * Obtém o código de serviço padrão associado a uma empresa específica.
     *
     * @param string $companyId O ID da empresa para a qual o código de serviço será recuperado.
     * @return string|null Retorna o código de serviço padrão ou null se não encontrado.
     */
    public function getDefaultServiceCodeByCompanyId($companyId)
    {
        $serviceCode = \WHMCS\Database\Capsule::table($this->tableName())
            ->where('company_id', '=', $companyId)
            ->value('service_code');

        return $serviceCode;
    }

    public function getDefaultNbsCodeByCompanyId($companyId)
    {
        $nbsCode = \WHMCS\Database\Capsule::table($this->tableName())
            ->where('company_id', '=', $companyId)
            ->value('nbs_code');

        return $nbsCode;
    }

    public function getDefaultOperationCodeByCompanyId($companyId)
    {
        $operationCode = \WHMCS\Database\Capsule::table($this->tableName())
            ->where('company_id', '=', $companyId)
            ->value('operation_indicator');

        return $operationCode;
    }

    public function getDefaultClassCodeByCompanyId($companyId)
    {
        $classCode = \WHMCS\Database\Capsule::table($this->tableName())
            ->where('company_id', '=', $companyId)
            ->value('class_code');

        return $classCode;
    }

    /**
     * Obtém a retenção de ISS padrão associada a uma empresa específica.
     *
     * @param string $companyId O ID da empresa para a qual a retenção de ISS será recuperada.
     * @return float|null Retorna a retenção de ISS padrão ou null se não encontrado.
     */
    public function getDefaultIssHeldByCompanyId($companyId)
    {
        $issHeld = \WHMCS\Database\Capsule::table($this->tableName())
            ->where('company_id', '=', $companyId)
            ->value('iss_held');

        return $issHeld;
    }

    /**
     * Obtém os dados da empresa padrão configurada no sistema.
     *
     * A empresa padrão é identificada pelo campo `default` com valor 1.
     * Caso ocorra algum erro durante a consulta, o erro será registrado
     * no log do módulo e o método retornará `null`.
     *
     * @return object|null Retorna os dados da empresa padrão como um objeto
     * ou `null` se não for encontrada ou em caso de erro.
     */
    public function getDefaultCompany()
    {
        try {
            $company = \WHMCS\Database\Capsule::table($this->tableName())
                ->where('default', '=', 1)
                ->first();
            return $company;
        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'get_default_company_error',
                ['error' => $exception->getMessage()]
            );
            return null;
        }
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
                $table->string('nbs_code', 30)->nullable();
                $table->string('operation_indicator', 30)->nullable();
                $table->string('class_code', 30)->nullable();
            });
        }
    }

    /**
     * Salva o registro de uma nova empresa ou atualiza um registro existente.
     *
     * @param string $companyId O ID da empresa.
     * @param string $taxNumber O número do CNPJ da empresa.
     * @param string $companyName O nome da empresa.
     * @param string $serviceCode O código de serviço padrão da empresa.
     * @param float $issHeld A retenção de ISS padrão da empresa.
     * @param string $nbs_code Código NBS do serviço.
     * @param string $operation_indicator Código de operação do serviço.
     * @param string $class_code Código de Classificação Tributária do serviço.
     * @param bool $default Define se a empresa será a padrão (true para sim, false para não).
     *
     * @return array Retorna um array com o status da operação e o resultado ou erro.
     */
    public function save($companyId, $taxNumber, $companyName, $serviceCode, $issHeld, $nbs_code, $operation_indicator, $class_code, $default = false)
    {
        try {
            $data = [
                'company_id' => $companyId,
                'tax_number' => $taxNumber,
                'company_name' => $companyName,
                'service_code' => $serviceCode,
                'iss_held' => $issHeld,
                'default' => $default,
                'updated_at' => \WHMCS\Database\Capsule::raw('NOW()'),
                'nbs_code' => $nbs_code,
                'operation_indicator' => $operation_indicator,
                'class_code' => $class_code
            ];

            $defaultExists = \WHMCS\Database\Capsule::table($this->tableName())->where('default', 1)->exists();

            if (!\WHMCS\Database\Capsule::table($this->tableName())->where('company_id', $companyId)->exists()) {
                $data['created_at'] = \WHMCS\Database\Capsule::raw('NOW()');
            }

            // se for default e um ja existir, remove o default de todas as outras empresas
            if ($default && $defaultExists) {
                \WHMCS\Database\Capsule::table($this->tableName())->where('default', 1)->update(['default' => 0]);
            }

            // se nao houver defaut e o default for 0, define o registro atual como default
            if (!$defaultExists && !$default) {
                $data['default'] = 1;
            }

            $result = \WHMCS\Database\Capsule::table($this->tableName())->updateOrInsert(
                ['company_id' => $companyId],
                $data
            );

            return ['status' => true, 'result' => $result];
        } catch (\Exception $exception) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'save_company_error',
                [
                    'company_id' => $companyId,
                    'service_code' => $serviceCode,
                    'iss_held' => $issHeld,
                    'default' => $default,
                    'company_name' => $companyName,
                    'tax_number' => $taxNumber,
                    'nbs_code' => $nbs_code,
                    'operation_indicator' => $operation_indicator,
                    'class_code' => $class_code
                ],
                ['error' => $exception->getMessage()]
            );
            return ['status' => false, 'error' => $exception->getMessage()];
        }
    }

    /**
     * Edita o registro de uma empresa existente.
     *
     * @param int $recordId ID do registro da empresa a ser editada.
     * @param string $companyName Nome da empresa.
     * @param string $serviceCode Código de serviço da empresa.
     * @param float $issHeld Retenção de ISS da empresa.
     * @param string $nbs_code Código NBS do serviço.
     * @param string $operation_indicator Código de operação do serviço.
     * @param string $class_code Código de Classificação Tributária do serviço.
     * @param bool $default Define se a empresa será a padrão (1 para sim, 0 para não).
     *
     * @return array Retorna um array com o status da operação e uma mensagem ou erro.
     */
    public function edit($recordId, $companyName, $serviceCode, $issHeld, $nbs_code, $operation_indicator, $class_code, $default)
    {
        // atualiza o registro da empresa
        try {
            $data = [
                'company_name' => $companyName,
                'service_code' => $serviceCode,
                'iss_held' => $issHeld,
                'default' => $default,
                'updated_at' => \WHMCS\Database\Capsule::raw('NOW()'),
                'nbs_code' => $nbs_code,
                'operation_indicator' => $operation_indicator,
                'class_code' => $class_code
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
                'message' => 'Empresa atualizada com sucesso.'
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

    /**
     * Exclui o registro de uma empresa com base no ID da empresa.
     *
     * Este método verifica se a empresa a ser excluída é a empresa padrão.
     * Caso seja, a exclusão não será permitida. Caso contrário, o registro
     * será removido do banco de dados.
     *
     * @param string $companyId O ID da empresa a ser excluída.
     * @return array|string Retorna um array com o status e a mensagem da operação
     * ou uma string com a mensagem de erro em caso de exceção.
     */
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
     * Retorna todos os registros de empresas cadastradas.
     *
     * Os registros são ordenados pelo campo `default` em ordem decrescente.
     *
     * @return \Illuminate\Support\Collection Coleção contendo os registros das empresas.
     */
    public function getAll()
    {
        return \WHMCS\Database\Capsule::table($this->tableName)
            ->orderBy('default', 'desc')
            ->select('id', 'company_id', 'tax_number', 'company_name', 'service_code', 'iss_held', 'nbs_code', 'operation_indicator', 'class_code', 'default')
            ->get();
    }
}
