<?php

namespace NFEioServiceInvoices\Helpers;

use WHMCS\Database\Capsule;

class Invoices
{
    /**
     * Obtém o status de uma fatura pelo seu ID.
     *
     * @param int $id O ID da fatura.
     * @return string O status da fatura (ex.: 'Paid', 'Unpaid', 'Cancelled').
     */
    public static function getInvoiceStatus($id)
    {
        return Capsule::table('tblinvoices')->where('id', '=', $id)->value('status');
    }

    /**
     * Obtém a URL completa para visualização da fatura.
     *
     * @param int $id ID da fatura.
     * @return string URL completa da fatura.
     */
    public static function getInvoiceViewUrl($id)
    {
        $systemUrl = \WHMCS\Config\Setting::getValue('SystemURL');
        $invoiceUrl = \WHMCS\Billing\Invoice::find($id)->getViewInvoiceUrl();

        return $systemUrl . $invoiceUrl;
    }

    /**
     * Gera a descrição do serviço para a nota fiscal.
     *
     * @param int $invoiceId ID da fatura.
     * @param string $description Descrição inicial do serviço.
     * @return string Descrição completa do serviço para a nota fiscal.
     *
     * O método utiliza configurações armazenadas para construir a descrição
     * da nota fiscal, incluindo informações opcionais como o número da fatura,
     * nome dos serviços, URL da fatura e uma descrição adicional personalizada.
     */
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
