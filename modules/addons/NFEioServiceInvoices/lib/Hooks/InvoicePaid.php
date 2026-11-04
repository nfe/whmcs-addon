<?php

namespace NFEioServiceInvoices\Hooks;

class InvoicePaid
{
    private $invoiceId;
    private $config;

    public function __construct($vars)
    {
        $this->invoiceId = $vars['invoiceid'] ?: null;
        $this->config = new \NFEioServiceInvoices\Configuration();
    }

    public function run()
    {
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $storage = new \WHMCSExpert\Addon\Storage($this->config->getStorageKey());
        $invoiceData = \WHMCS\Billing\Invoice::find($this->invoiceId);
        $invoiceStatus = $invoiceData->status;
        // o critério usa o subtotal (itens antes do crédito): crédito é forma de pagamento,
        // e no WHMCS <= 8 o total é zerado quando a fatura é paga com crédito
        $invoiceSubtotal = $invoiceData->subtotal;
        $userId = $invoiceData->userid;
        $clientRepository = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $clientIssueCondition = $clientRepository->getClientIssueCondition($userId);
        $moduleIssueCondition = strtolower($storage->get('issue_note_default_cond'));
        $issueNoteAfter = $storage->get('issue_note_after');
        $generateTaxBill = false;
        $generateTaxBillWhen = 'quando a fatura é paga';

        if ($invoiceSubtotal > 0.00 and (!$issueNoteAfter or $issueNoteAfter == 0) and $invoiceStatus == 'Paid') {
            $generateTaxBill = true;
        }

        $data = [
            'invoiceID' => $this->invoiceId,
            'invoiceStatus' => $invoiceStatus,
            'invoiceSubtotal' => $invoiceSubtotal,
            'userID' => $userId,
            'clientIssueCondition' => $clientIssueCondition,
            'moduleIssueCondition' => $moduleIssueCondition,
            'issueNoteAfter' => $issueNoteAfter,
            'generateTaxBill' => $generateTaxBill,
            'generateTaxBillWhen' => $generateTaxBillWhen,
            'invoiceData' => $invoiceData,
        ];

        if ($clientIssueCondition == 'seguir configuração do módulo nfe.io' and $moduleIssueCondition == $generateTaxBillWhen and $generateTaxBill) {
            $queue = $nfe->queue($this->invoiceId);
            logModuleCall('nfeio_serviceinvoices', 'nf_invoice_paid', $data, $queue);
        }
        if ($clientIssueCondition == $generateTaxBillWhen and $generateTaxBill) {
            $queue = $nfe->queue($this->invoiceId);
            logModuleCall('nfeio_serviceinvoices', 'nf_invoice_paid', $data, $queue);
        }
    }
}
