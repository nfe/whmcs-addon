<?php

namespace NFEioServiceInvoices\Helpers;

use \WHMCS\Database\Capsule;

/**
 * Classe de ajuda com métodos para verificações e comparações de versões utilizadas no módulo
 */
class Versions
{
    /**
     * Retorna a versão do WHMCS atualmente instalada.
     *
     * @return string Versão do WHMCS
     */
    public static function whmcs()
    {
        return \WHMCS\Config\Setting::getValue('Version');
    }

    /**
     * Verifica se o WHMCS instalado é versão 7.7 ou superior.
     *
     * @return bool true se maior que WHMCS 7.7, senão false
     */
    public static function isWhmcs7()
    {
        $whmcsVersion = self::whmcs();
        return version_compare($whmcsVersion, '7.7', 'gt');
    }

    /**
     * Verifica se o WHMCS instalado é versão 8.x
     *
     * @return bool true se versão 8.x
     */
    public static function isWhmcs8()
    {
        $whmcsVersion = self::whmcs();
        return version_compare($whmcsVersion, '8', 'ge');
    }

    /**
     * Verifica se existem registros na tabela  tbladdonmodules com a chave gofasnfeio.
     * Se houver qualquer registro retorna true, senão false.
     *
     * @return bool true para registros, false se não houver nenhum
     */
    public static function hasOldNfeioModule()
    {
        $query = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->count();

        if ($query) {
            return true;
        }

        return false;
    }

    /**
     * Retorna a versão do módulo do padrão anterior a 2.0 na chave gofasnfeio na tabela tbladdonmodules
     *
     * @return mixed versão do módulo anterior
     */
    public static function getOldNfeioModuleVersion()
    {
        return Capsule::table('tbladdonmodules')->where([['module', '=', 'gofasnfeio'], ['setting', '=', 'version']])->value('value');
    }

}