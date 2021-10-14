<?php

namespace NFEioServiceInvoices;

use Illuminate\Database\Capsule\Manager as Capsule;

class CustomFields
{

    private static function queryCF(array $select, array $where)
    {
        return Capsule::table('tblcustomfields')->select($select)->where($where)->get();

    }

    public static function getClientFields()
    {
        $select = array(
            'id',
            'fieldname'
        );
        $where = array(
            [
                'type',
                'client'
            ]
        );
        return self::queryCF($select, $where);
        //return Capsule::table('tblcustomfields')->select('id', 'relid', 'fieldname')->where('type', 'client')->get();
    }

    public static function getClientFieldsAsKeys()
    {
        $cf = self::getClientFields();
        $fields = [];

        foreach ($cf as $f) {
            $fields[$f->id] = $f->fieldname;
        }

        return $fields;
    }


}