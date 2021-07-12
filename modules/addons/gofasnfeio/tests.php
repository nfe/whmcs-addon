<?php

$_GET['nfe_id'] = 888;

require_once __DIR__ . '/createpdf.php';

$userId = WHMCS\Session::get("uid");

echo '<pre>';
print_r($userId);
echo '</pre><hr>';
