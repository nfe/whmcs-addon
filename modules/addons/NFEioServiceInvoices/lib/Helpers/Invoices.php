<?php

namespace NFEioServiceInvoices\Helpers;

use NFEioServiceInvoices\Helpers\Invoices as InvoicesHelper;
use WHMCS\Database\Capsule;

class Invoices
{
    public static function getInvoiceStatus($id)
    {
        return Capsule::table('tblinvoices')->where('id', '=', $id)->value('status');
    }

    public static function getInvoiceViewUrl($id)
    {
        $systemUrl = \WHMCS\Config\Setting::getValue('SystemURL');
        $invoiceUrl = \WHMCS\Billing\Invoice::find($id)->getViewInvoiceUrl();

        return $systemUrl . $invoiceUrl;
    }

    public static function generateNfServiceDescription($invoiceId, $description)
    {
        $config = new \NFEioServiceInvoices\Configuration();
        $storage = new \WHMCSExpert\Addon\Storage($config->getStorageKey());
        $invoiceDetails = $storage->get('InvoiceDetails');
        $additionalDescription = $storage->get('descCustom');
        $invoiceUrl = (bool) $storage->get('send_invoice_url');

        switch ($invoiceDetails) {
            case 'Número da fatura':
                $description = "Nota referente a fatura #{$invoiceId}";
                break;
            case 'Número da fatura + Nome dos serviços':
                $description = "Nota referente a fatura #{$invoiceId}" . "\n" . $description;
                break;
        }

        if ($invoiceUrl) {
            $description = $description . "\n" . self::getInvoiceViewUrl($invoiceId);
        }

        if ($additionalDescription) {
            $description = $description . "\n" . $additionalDescription;
        }

        return $description;
    }

    /**
     * Calcula o valor de retenção para o ISS
     *
     * @param  $amount
     * @param  $issHeld
     * @return float
     */
    public static function getIssHeldAmount($amount, $issHeld)
    {
        $heldAmount = ($amount * $issHeld) / 100;

        return round($heldAmount, 2);
    }

    /**
     * Verifica se uma determinada fatura existe no banco de dados com base no seu ID.
     *
     * @param   $invoiceId integer ID da fatura
     * @return  bool true se tiver false se não existir
     * @version 2.1
     */
    public static function hasInvoice($invoiceId)
    {
        $result = Capsule::table('tblinvoices')->where('id', '=', $invoiceId)->exists();

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
