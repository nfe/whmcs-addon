<?php

use WHMCS\Database\Capsule;

Capsule::table('tblproductcode')->where('product_id', '=', $vars['pid'])->delete();
