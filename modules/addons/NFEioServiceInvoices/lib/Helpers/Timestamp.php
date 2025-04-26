<?php

namespace NFEioServiceInvoices\Helpers;

class Timestamp
{
    /**
     * Retorna a data e hora atual no formato de timestamp padrão.
     *
     * @see https://github.com/nfe/whmcs-addon/issues/156
     * @return string Data e hora atual no formato de timestamp.
     */
    public static function currentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
