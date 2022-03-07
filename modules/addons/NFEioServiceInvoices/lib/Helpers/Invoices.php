<?php

namespace NFEioServiceInvoices\Helpers;

use WHMCS\Database\Capsule;

class Invoices
{

    public static function getInvoiceStatus($id)
    {
        return Capsule::table('tblinvoices')->where('id', '=', $id)->value('status');
    }

}