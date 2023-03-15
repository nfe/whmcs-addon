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
        $invoiceTotal = $invoiceData->total;
        $userId = $invoiceData->userid;
        $clientRepository = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $clientIssueCondition = $clientRepository->getClientIssueCondition($userId);
        $moduleIssueCondition = strtolower($storage->get('issue_note_default_cond'));
        $issueNoteAfter = $storage->get('issue_note_after');
        $generateTaxBill = false;
        $generateTaxBillWhen = 'quando a fatura é paga';

        if ($invoiceTotal > 0.00 AND (!$issueNoteAfter OR $issueNoteAfter == 0) AND $invoiceStatus == 'Paid' ) {
            $generateTaxBill = true;
        }

        $data = [
            'invoiceID' => $this->invoiceId,
            'invoiceStatus' => $invoiceStatus,
            'userID' => $userId,
            'clientIssueCondition' => $clientIssueCondition,
            'moduleIssueCondition' =>$moduleIssueCondition,
            'issueNoteAfter' => $issueNoteAfter,
            'generateTaxBill' => $generateTaxBill,
            'generateTaxBillWhen' => $generateTaxBillWhen,
            'invoiceData' => $invoiceData,
        ];

        if ($clientIssueCondition == 'seguir configuração do módulo nfe.io' AND $moduleIssueCondition == $generateTaxBillWhen AND $generateTaxBill) {
            $queue = $nfe->queue($this->invoiceId);
            logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, $data, $queue);
        }
        if ($clientIssueCondition == $generateTaxBillWhen AND $generateTaxBill) {
            $queue = $nfe->queue($this->invoiceId);
            logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, $data, $queue);
        }

    }
}