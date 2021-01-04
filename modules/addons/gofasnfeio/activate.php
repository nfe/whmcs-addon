<?php

use WHMCS\Database\Capsule;

require_once __DIR__.'/functions.php';

if (!function_exists('gofasnfeio_activate')) {
    function gofasnfeio_activate()
    {
        $current_version = '1.2.5';
        $row = Capsule::table('tblconfiguration')->where('setting', '=', 'version_nfeio')->get(['value']);
        $version = $row[0]->value;
        if ($version != $current_version) {
            create_table_product_code();
            set_code_service_camp_gofasnfeio();
            set_custom_field_ini_date();

            Capsule::table('tblconfiguration')->insert(['setting' => 'version_nfeio', 'value' => $current_version, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }
}
