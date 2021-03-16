<?php
if (!defined('WHMCS')) {
    exit();
}

use WHMCS\Database\Capsule;

//===================================================================================
//                      CREATE TABLES
if (!function_exists('gnfe_verifyInstall')) {
    function gnfe_verifyInstall() {
        if (!Capsule::schema()->hasTable('gofasnfeio')) {
            try {
                Capsule::schema()->create('gofasnfeio', function ($table) {
                    // incremented id
                    $table->increments('id');
                    // whmcs info
                    $table->string('invoice_id');
                    $table->string('user_id');
                    $table->string('nfe_id');
                    $table->string('status');
                    $table->decimal('services_amount',$precision = 16,$scale = 2);
                    $table->string('environment');
                    $table->string('flow_status');
                    $table->string('pdf');
                    $table->string('rpsSerialNumber');
                    $table->string('rpsNumber');
                    $table->string('created_at');
                    $table->string('updated_at');
                    $table->string('service_code')->nullable(true);
                    $table->string('tics')->nullable(true);
                });
            } catch (\Exception $e) {
                $error .= "Não foi possível criar a tabela do módulo no banco de dados: {$e->getMessage()}";
            }
        }
        // Added in v 1 dot 1 dot 3
        if (!Capsule::schema()->hasColumn('gofasnfeio', 'rpsNumber')) {
            try {
                Capsule::schema()->table('gofasnfeio', function ($table) {
                    $table->string('rpsNumber');
                });
            } catch (\Exception $e) {
                $error .= "Não foi possível atualizar a tabela do módulo no banco de dados: {$e->getMessage()}";
            }
        }

        if (!$error) {
            return ['sucess' => 1];
        }
        if ($error) {
            return ['error' => $error];
        }
    }
}

if (!function_exists('create_table_product_code')) {
    function create_table_product_code() {
        if (Capsule::schema()->hasTable('tblproductcode')) {
            return '';
        }

        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare('CREATE TABLE tblproductcode (
                    id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    product_id int(10) NOT NULL,
                    code_service int(10) NOT NULL,
                    create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    update_at TIMESTAMP NULL,
                    ID_user int(10) NOT NULL)');
            $statement->execute();
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
        }
    }
}
//===================================================================================

if (!function_exists('set_code_service_camp_gofasnfeio')) {
    function set_code_service_camp_gofasnfeio() {
        if (!Capsule::schema()->hasColumn('gofasnfeio', 'service_code')) {
            $pdo = Capsule::connection()->getPdo();
            $pdo->beginTransaction();

            try {
                $statement = $pdo->prepare('ALTER TABLE gofasnfeio ADD service_code TEXT;');
                $statement->execute();
                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
            }
        }
        if (!Capsule::schema()->hasColumn('gofasnfeio', 'services_amount')) {
            if (!Capsule::schema()->hasColumn('gofasnfeio', 'services_amount')) {
                $pdo = Capsule::connection()->getPdo();
                $pdo->beginTransaction();
                try {
                    $statement = $pdo->prepare('ALTER TABLE gofasnfeio ADD services_amount DECIMAL(16,2)');
                    $statement->execute();
                    $pdo->commit();
                } catch (\Exception $e) {
                    $pdo->rollBack();
                }
            }
        }
    }
}

if (!function_exists('set_custom_field_ini_date')) {
    function set_custom_field_ini_date() {
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);

        try {
            if (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'initial_date')->count() < 1) {
                Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio', 'setting' => 'initial_date', 'value' => $dataAtual]);
            }
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }
}