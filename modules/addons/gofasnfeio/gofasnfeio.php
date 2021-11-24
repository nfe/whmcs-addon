<?php

if (!defined('WHMCS')) {
    exit();
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/config.php';

require_once __DIR__ . '/output.php';

function gofasnfeio_upgrade($vars) {
    $currentlyInstalledVersion = $vars['version'];

    // v1.4.5
    // issue #99
    if (version_compare($currentlyInstalledVersion, '1.4.5', '<')) {
        if (Capsule::schema()->hasTable('tblproductcode')) {
            //coleta a descrição da tabela para saber o tipo de suas colunas
            $tableDescription = Capsule::select('describe tblproductcode');
            // filtra apenas a coluna desejada
            $serviceCodeColumn = array_filter($tableDescription, function ($var) {
                return($var->Field == 'code_service');
            });
            $serviceCodeColumnType = false;
            $serviceCodeColumnIsInt = false;
            // se $serviceCodeColumn for um array faz a magica
            if (is_array($serviceCodeColumn)) {
                // percorre o resultado do filtro e retorna apenas o tipo da coluna desejada
                foreach ($serviceCodeColumn as $column) {
                    if ($column->Field === 'code_service') {
                        $serviceCodeColumnType = $column->Type;
                    }
                }
                // verifica se coluna é do tipo int
                $serviceCodeColumnIsInt = preg_match_all('/^int/', $serviceCodeColumnType);
            }
            // se a coluna existir e for do tipo int, migra para o novo tipo
            if ($serviceCodeColumnIsInt) {
                // recria a tabela
                $pdo = Capsule::connection()->getPdo();
                $pdo->beginTransaction();
                try {
                    $statement = $pdo->prepare('ALTER TABLE tblproductcode
                    MODIFY COLUMN code_service varchar(20) NOT NULL');
                    $statement->execute();
                    $pdo->commit();
                } catch (\Exception $e) {
                    $pdo->rollBack();
                }

            }
        }
    }
}