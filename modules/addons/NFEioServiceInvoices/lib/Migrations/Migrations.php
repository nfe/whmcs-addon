<?php

namespace NFEioServiceInvoices\Migrations;

use NFEioServiceInvoices\Configuration;
use NFEioServiceInvoices\Helpers\Versions;
use WHMCS\Database\Capsule;
use WHMCSExpert\Addon\Storage;

class Migrations
{
    /**
     * Migra as configurações se existentes da versão anterior a 2.
     *
     * @return bool true para migrado, false para nada migrado ou sem campos antigos
     */
    public static function migrateConfigurations()
    {
        // verifica se existem registros da versão anterior do módulo no banco de dados
        // se houver, tenta a migração
        if (Versions::hasOldNfeioModule()) {
            $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
            $config = new Configuration();
            $storage = new Storage($config->getStorageKey());

            try {
                // seleciona os antigos registros de configuração
                $query = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->select('setting', 'value')->get();
                $recordsAsKey = [];

                // transforma os resultados da query em chave => valor
                foreach ($query as $value) {
                    $recordsAsKey[$value->setting] = $value->value;
                }

                // calcula a interseção entre os registros existentes e os campos de migração
                $fieldsToMigrate = array_intersect_key($recordsAsKey, $moduleConfigurationRepo->getMigrationFields());

                // verifica se $fieldsToMigrate possui itens e então os percorre para inserção
                if (count($fieldsToMigrate) > 0) {
                    foreach ($fieldsToMigrate as $key => $value) {
                        // converte Sim/Não para 'on' e ''
                        if ($key == 'tax') {
                            if ($value == 'Não') {
                                $value = '';
                            }
                            if ($value == 'Sim') {
                                $value = 'on';
                            }
                        }
                        // converte Sim/Não para 'on' e ''
                        if ($key == 'send_invoice_url') {
                            if ($value == 'Não') {
                                $value = '';
                            }
                            if ($value == 'Sim') {
                                $value = 'on';
                            }
                        }

                        // se já não houver chave, seta a da migração
                        if (!$storage->has($key)) {
                            $storage->set($key, $value);
                        }
                    }
                }
            } catch (\Exception $exception) {
                echo $exception->getMessage();
            }

            return true;
        }

        // se não tiver registros antigos retorna false (nada a migrar)
        return false;
    }

    /**
     * Migra as configurações personalizadas dos clientes da tabela mod_nfeio_custom_configs (versões anterior a 2).
     */
    public static function migrateClientsConfigurations()
    {

        // verifica se existem registros de versão anterior do módulo no banco de dados
        if (Versions::hasOldNfeioModule()) {
            try {
                // se a tabela mod_nfeio_custom_configs não existir não há o ser que migrar
                if (!Capsule::schema()->hasTable('mod_nfeio_custom_configs')) {
                    return false;
                }
                // se não houver registros na tabela mod_nfeio_custom_configs não há o que ser migrado
                if (!Capsule::table('mod_nfeio_custom_configs')->count()) {
                    return false;
                }
                // se a nova tabela já existir e possuir registros, não migra nada
                if (
                    Capsule::schema()->hasTable('mod_nfeio_si_custom_configs')
                    && Capsule::table('mod_nfeio_si_custom_configs')->count()
                ) {
                    return false;
                }

                // não existir a nova tabela destino mod_nfeio_si_custom_configs
                if (!Capsule::schema()->hasTable('mod_nfeio_si_custom_configs')) {
                    // copia a antiga tabela mod_nfeio_custom_configs e renomeia para o novo nome
                    $db = Capsule::connection();
                    $db->statement('CREATE TABLE mod_nfeio_si_custom_configs LIKE mod_nfeio_custom_configs');
                    $db->statement('INSERT mod_nfeio_si_custom_configs SELECT * FROM mod_nfeio_custom_configs');

                    return true;
                }

                return false;
            } catch (\Exception $exception) {
                echo $exception->getMessage();
            }
        }

        // se não tiver registros returna false pra migração
        return false;
    }

    /**
     * Migra os registros de notas fiscais da tabela gofasnfeio para a nova tabela mod_nfeio_si_serviceinvoices
     */
    public static function migrateServiceInvoices()
    {
        // verifica se existem registros de versão anterior do módulo no banco de dados
        if (Versions::hasOldNfeioModule()) {
            try {
                // se a tabela gofasnfeio não existir não há o ser que migrar
                if (!Capsule::schema()->hasTable('gofasnfeio')) {
                    return false;
                }
                // se não houver registros na tabela gofasnfeio não há o que ser migrado
                if (!Capsule::table('gofasnfeio')->count()) {
                    return false;
                }
                // se a nova tabela já existir e possuir registros, não migra nada
                if (
                    Capsule::schema()->hasTable('mod_nfeio_si_serviceinvoices')
                    && Capsule::table('mod_nfeio_si_serviceinvoices')->count()
                ) {
                    return false;
                }

                // não existir a nova tabela destino mod_nfeio_si_serviceinvoices
                if (!Capsule::schema()->hasTable('mod_nfeio_si_serviceinvoices')) {
                    // copia a antiga tabela gofasnfeio e renomeia para o novo nome
                    $db = Capsule::connection();
                    $db->statement('CREATE TABLE mod_nfeio_si_serviceinvoices LIKE gofasnfeio');
                    $db->statement('INSERT mod_nfeio_si_serviceinvoices SELECT * FROM gofasnfeio');

                    return true;
                }

                return false;
            } catch (\Exception $exception) {
                echo $exception->getMessage();
            }
        }

        return false;
    }

    public static function migrateProductCodes()
    {
        // verifica se existem registros de versão anterior do módulo no banco de dados
        if (Versions::hasOldNfeioModule()) {
            try {
                // se a tabela tblproductcode não existir não há o ser que migrar
                if (!Capsule::schema()->hasTable('tblproductcode')) {
                    return false;
                }
                // se não houver registros na tabela tblproductcode não há o que ser migrado
                if (!Capsule::table('tblproductcode')->count()) {
                    return false;
                }
                // se a nova tabela já existir e possuir registros, não migra nada
                if (
                    Capsule::schema()->hasTable('mod_nfeio_si_productcode')
                    && Capsule::table('mod_nfeio_si_productcode')->count()
                ) {
                    return false;
                }

                // copia a antiga tabela tblproductcode e renomeia para o novo nome
                if (!Capsule::schema()->hasTable('mod_nfeio_si_productcode')) {
                    // copia a antiga tabela tblproductcode e renomeia para o novo nome
                    $db = Capsule::connection();
                    $db->statement('CREATE TABLE mod_nfeio_si_productcode LIKE tblproductcode');
                    $db->statement('INSERT mod_nfeio_si_productcode SELECT * FROM tblproductcode');

                    return true;
                }
            } catch (\Exception $exception) {
                echo $exception->getMessage();
            }
        }

        return false;
    }

    /**
     * Creates and executes an SQL statement to alter a column in the specified table.
     *
     * @param PDO $pdo The PDO object for database connection
     * @param string $columnName The name of the column to be altered
     * @return void
     */
    private function createAlterColumnTimestampStatement($pdo, $columnName, $tableName)
    {
        $statement = $pdo->prepare(
            sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s TIMESTAMP NULL',
                $tableName,
                $columnName
            )
        );
        $statement->execute();
    }

    /**
     *
     * Atualiza as colunas de timestamp na tabela de notas fiscais de serviço.
     * Define a coluna `created_at` com o valor do timestamp atual, e
     * a coluna `updated_at` com o valor do timestamp atual em caso de atualização.
     */
    public static function migrateTimestampColumns(string $tableName)
    {
        if (Capsule::schema()->hasTable($tableName)) {
            $pdo = Capsule::connection()->getPdo();
            $pdo->beginTransaction();
            try {
                $self = new self();
                $self->createAlterColumnTimestampStatement($pdo, 'created_at', $tableName);
                $self->createAlterColumnTimestampStatement($pdo, 'updated_at', $tableName);
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                    logModuleCall(
                        'nfeio_serviceinvoices',
                        'migrateTimestampColumns',
                        $tableName,
                        'success'
                    );
                }
            } catch (\Exception $e) {
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'migrateTimestampColumns',
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }
    }

    /**
     * Altera as colunas da tabela mod_nfeio_si_productcode referente ao timestamp
     * para created_at e updated_at.
     *
     * @return void
     */
    public static function changeProductCodeTimestampColumnsName()
    {

        if (Capsule::schema()->hasTable('mod_nfeio_si_productcode')) {
            // verifica se a coluna create_at e update_at já foram migradas (existem)
            $columns = Capsule::schema()->getColumnListing('mod_nfeio_si_productcode');
            if (!in_array('create_at', $columns) || !in_array('update_at', $columns)) {
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'changeProductCodeTimestampColumnsName',
                    'nothing to do, columns already exist',
                    ''
                );
                return;
            }

            $pdo = Capsule::connection()->getPdo();
            $pdo->beginTransaction();
            try {
                $st1 = $pdo->prepare('ALTER TABLE mod_nfeio_si_productcode CHANGE create_at created_at TIMESTAMP');
                $st2 = $pdo->prepare('ALTER TABLE mod_nfeio_si_productcode CHANGE update_at updated_at TIMESTAMP');
                $st1->execute();
                $st2->execute();
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (\Exception $e) {
                logModuleCall(
                    'nfeio_serviceinvoices',
                    'changeProductCodeTimestampColumnsName_error',
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            }
        }
    }

    /**
     * Adiciona a coluna company_id à tabela especificada.
     *
     * Verifica se a tabela existe e se a coluna ainda não existe antes de
     * iniciar uma transação para adicioná-la.
     *
     * @param string $tableName Nome da tabela a ser alterada.
     * @return void
     * @see https://github.com/nfe/whmcs-addon/issues/163
     * @version 3.0
     * @since 3.0
     * @author Andre Kutianski <andre@mimirtech.co>
     */
    public static function addCompanyIdColumn(string $tableName)
    {
        if (!Capsule::schema()->hasTable($tableName)) {
            return;
        }

        // verifica se a coluna company_id já existe para evitar transaction desnecessária
        $columns = Capsule::schema()->getColumnListing($tableName);
        if (in_array('company_id', $columns)) {
            logModuleCall(
                'nfeio_serviceinvoices',
                'addCompanyIdColumn',
                $tableName,
                'column company_id already exists'
            );
            return;
        }

        $pdo = Capsule::connection()->getPdo();
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare(
                sprintf('ALTER TABLE %s ADD COLUMN company_id VARCHAR(255) NULL', $tableName)
            );
            $statement->execute();

            if ($pdo->inTransaction()) {
                $pdo->commit();

                logModuleCall(
                    'nfeio_serviceinvoices',
                    'addCompanyIdColumn',
                    $tableName,
                    'success'
                );
            }
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            logModuleCall(
                'nfeio_serviceinvoices',
                'addCompanyIdColumn',
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
    }

    /**
     * Adiciona o company_id existente na coluna company_id de
     * cada registro existente na tabela especificada.
     *
     * @version 3.0
     * @param $companyId
     * @param $tableName
     * @return void
     */
    public static function addCompanyIdRecord($companyId, $tableName)
    {
        if (!Capsule::schema()->hasTable($tableName)) {
            return;
        }

        $pdo = Capsule::connection()->getPdo();
        try {
            $pdo->beginTransaction();

            // Atualiza todos os registros da tabela com o company_id fornecido
            $statement = $pdo->prepare(
                sprintf('UPDATE %s SET company_id = :company_id', $tableName)
            );
            $statement->bindParam(':company_id', $companyId);
            $statement->execute();

            if ($pdo->inTransaction()) {
                $pdo->commit();

                logModuleCall(
                    'nfeio_serviceinvoices',
                    'addCompanyIdRecord',
                    $tableName,
                    'success'
                );
            }
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            logModuleCall(
                'nfeio_serviceinvoices',
                'addCompanyIdRecord',
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
    }
}
