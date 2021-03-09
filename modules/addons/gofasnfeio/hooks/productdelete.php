<?php

use WHMCS\Database\Capsule;

try {
    Capsule::table('tblproductcode')->where('product_id', '=', $vars['pid'])->delete();
} catch (Exception $e) {
    save_error_remote_log('','',$e->getMessage());
}
