<?php

namespace NFEioServiceInvoices\Hooks;

class InvoiceCreation
{
    private $invoiceId;
    private $invoiceStatus;
    private $creationSource;
    private $config;

    public function __construct($vars)
    {
        $this->invoiceId = $vars['invoiceid'] ?: null;
        $this->invoiceStatus = $vars['status'] ?: null;
        $this->creationSource = $vars['source'] ?: null;
        $this->config = new \NFEioServiceInvoices\Configuration();
    }

    public function run()
    {
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $storage = new \WHMCSExpert\Addon\Storage($this->config->getStorageKey());
        $invoiceData = localAPI('GetInvoice', array('invoiceid' => $this->invoiceId));
        $userId = $invoiceData['userid'];
        $clientRepository = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $clientIssueCondition = $clientRepository->getClientIssueCondition($userId);
        $moduleIssueCondition = strtolower($storage->get('issue_note_default_cond'));
        $issueNoteAfter = $storage->get('issue_note_after');
        $generateTaxBill = false;
        $generateTaxBillWhen = 'quando a fatura é gerada';

        if ($invoiceData['total'] > 0.00 AND (!$issueNoteAfter OR $issueNoteAfter == 0) AND $this->invoiceStatus != 'Draft' ) {
            $generateTaxBill = true;
        }

        $data = [
            'invoiceID' => $this->invoiceId,
            'invoiceData' => $invoiceData,
            'userID' => $userId,
            'clientIssueCondition' => $clientIssueCondition,
            'moduleIssueCondition' =>$moduleIssueCondition,
            'issueNoteAfter' => $issueNoteAfter,
            'generateTaxBill' => $generateTaxBill,
            'generateTaxBillWhen' => $generateTaxBillWhen
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