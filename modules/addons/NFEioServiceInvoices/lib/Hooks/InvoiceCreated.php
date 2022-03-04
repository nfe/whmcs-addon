<?php

namespace NFEioServiceInvoices\Hooks;

class InvoiceCreated
{
    private $invoiceId;
    private $invoiceStatus;
    private $creationSource;
    private $config;
    private $legacyFunctions;

    public function __construct($vars)
    {
        $this->invoiceId = $vars['invoiceid'] ?: null;
        $this->invoiceStatus = $vars['status'] ?: null;
        $this->creationSource = $vars['source'] ?: null;
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
    }

    public function createTaxBill()
    {
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

        if ( ($clientIssueCondition === $generateTaxBillWhen OR $moduleIssueCondition === $generateTaxBillWhen) AND $generateTaxBill) {
            $queue = $this->legacyFunctions->gnfe_queue_nfe($this->invoiceId, true);
        }

        logModuleCall('NFEioServiceInvoices', __FUNCTION__, $invoiceData, $queue);

    }
}