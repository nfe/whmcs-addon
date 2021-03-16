<?php

use WHMCS\Database\Capsule;

try {
    $delete = Capsule::table('tblproductcode')->where('product_id', '=', $vars['pid'])->delete();
    logModuleCall('gofas_nfeio', 'productdelete', 'product_id=' . $vars['pid'], $delete, 'OK', '');
} catch (Exception $e) {
    logModuleCall('gofas_nfeio', 'productdelete', 'product_id=' . $vars['pid'], $e->getMessage(), 'ERROR', '');
}
