<?php

namespace NFEioServiceInvoices\Migrations;

use NFEioServiceInvoices\Configuration;
use NFEioServiceInvoices\Helpers\Versions;
use Illuminate\Database\Capsule\Manager as Capsule;
use WHMCSExpert\Addon\Storage;

class Migrations
{
    /**
     * Migra as configurações se existentes da versão anterior a 2.
     * @return bool true para migrado, false para nada migrado ou sem campos antigos
     */
    public static function migrateConfigurations()
    {
        // verifica se existem registros da versão anterior do módulo no banco de dados
        // se houver, tenta a migração
        if (Versions::hasOldNfeioModule())
        {

            $moduleConfigurationRepo = new \NFEioServiceInvoices\Models\ModuleConfiguration\Repository();
            $config = new Configuration();
            $storage = new Storage($config->getStorageKey());

            try {

                // seleciona os antigos registros de configuração
                $query = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->select('setting', 'value')->get();
                $recordsAsKey = [];

                // transforma os resultados da query em chave => valor
                foreach ($query as $value)
                {
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
}