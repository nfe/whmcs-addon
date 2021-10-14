<?php

namespace NFEioServiceInvoices;

use WHMCS\Database\Capsule;
use WHMCSExpert as main;
use NFEioServiceInvoices\Configuration;

/**
 * Description of Addon
 *
 *
 * @SuppressWarnings(PHPMD)
 */
class Addon extends \WHMCSExpert\mtLibs\process\AbstractMainDriver
{

    public function loadAddonData() {

        $response = new \stdClass();

        $result = Capsule::table('tbladdonmodules')
            ->where('module', self::I()->configuration()->systemName)
            ->select('setting', 'value')
            ->get();

        if (!empty($result)) {
            foreach ($result as $row) {
                $response->{$row->setting} = $row->value;
            }
        }

        return $response;
    }

    /**
     * Load Addon WHMCS Configuration
     *
     *
     */
    public function loadAddonConfiguration()
    {

        $response = new \stdClass();

        $result = Capsule::table('tbladdonmodules')
            ->where('module', self::I()->configuration()->systemName)
            ->select('setting', 'value')
            ->get();
        if (!empty($result)) {
            foreach ($result as $row) {
                $response->{$row->setting} = $row->value;
            }
        }

        return $response;
        // while ($row = $result) {
        //     $this->configuration()->{$row['setting']} = $row['value'];
        // }
    }

    /**
     * Return Tempalates Path
     *
     * @param boolean $relative
     * @return string
     */
    public static function getModuleTemplatesDir($relative = false)
    {
        $dir = ($relative)?'':(__DIR__.DS);

        $dir .= 'templates'.DS;

        if (self::I()->isAdmin()) {
            return $dir.'admin';
        } else {
            $template = $GLOBALS['CONFIG']['Template'];

            if (file_exists(__DIR__ . DS .DS.$template)) {
                return $dir.'clientarea'.DS.$template;
            } else {
                return $dir.'clientarea';
            }
        }
    }

    public function getCon()
    {
        return self::I()->configuration();
    }

    public function getAssetsURL()
    {
        if ($this->isAdmin()) {
            return '../modules/addons/'.self::I()->configuration()->systemName.'/lib/'.self::getModuleTemplatesDir(true).'/assets';
        } else {
            return 'modules/addons/'.self::I()->configuration()->systemName.'/'.self::getModuleTemplatesDir(true).'/assets';
        }
    }

    public function getType()
    {
        return 'addon';
    }

    public static function getMainDIR()
    {
        return __DIR__;
    }

    public static function getUrl($page = null, $action=null, $params = array())
    {
        if (self::I()->isAdmin()) {
            $url = 'addonmodules.php?module='.self::I()->configuration()->systemName;
        } else {
            $url = 'index.php?m='.self::I()->configuration()->systemName;
        }

        if ($page) {
            $url .= '&page='.$page;
            if ($action) {
                $url .= '&action='.$action;
            }

            if ($params) {
                $url .= '&'.http_build_query($params);
            }
        }

        if ($action) {
            $url .= '&action='.$action;
        }

        return $url;
    }

    public static function genCustomPageUrl($page = null, $action=null, $params = array())
    {
        if (self::I()->isAdmin()) {
            $url = 'addonmodules.php?module='.self::I()->configuration()->systemName.'&customPage=1';
        } else {
            $url = 'index.php?m='.self::I()->configuration()->systemName.'&customPage=1';
        }

        if ($page) {
            $url .= '&mg-page='.$page;
        }

        if ($action) {
            $url .= '&mg-action='.$action;
        }

        if ($params) {
            $url .= '&'.http_build_query($params);
        }

        return $url;
    }

    public static function genJSONUrl($page)
    {
        if (self::I()->isAdmin()) {
            return 'addonmodules.php?module='.self::I()->configuration()->systemName.'&json=1&mg-page='.$page;
        } else {
            return 'index.php?m='.self::I()->configuration()->systemName.'&json=1&mg-page='.$page;
        }
    }

    public static function config()
    {
        $config = new \NFEioServiceInvoices\Configuration();

        return array(
            'name'           =>  $config->getName(),
            'description'   =>  $config->getDescription(),
            'version'       =>  $config->getVersion(),
            'author'        =>  $config->getAuthor(),
            'fields'        =>  $config->getAddonWHMCSConfig(),
        );
    }

    public static function activate()
    {
        try {
            self::I(true)->configuration()->activate();

            return array(
                'status'=>'success'
            );
        } catch (\Exception $ex) {
            return array(
                'status'        => 'error'
            ,'description'  => $ex->getMessage()
            );
        }
    }

    public static function deactivate()
    {
        try {
            self::I(true)->configuration()->deactivate();

            return array(
                'status'=>'success'
            );
        } catch (\Exception $ex) {
            return array(
                'status'        => 'error'
            ,'description'  => $ex->getMessage()
            );
        }
    }



}
