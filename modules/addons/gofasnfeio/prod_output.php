<?php

if (!function_exists('product_output')) {
    function product_output($vars)
    {
        require_once __DIR__.'/functions.php';
        $params = gnfe_config();
        echo '<p>The date & time are currently '.date('Y-m-d H:i:s').'</p>';
    }
}
