<?php

namespace NFEioServiceInvoices\Hooks;

/**
 * Class InvoiceCreation
 * Classe responsável por executar ações quando uma fatura é criada.
 *
 * @author  Andre Bellafronte
 * @package NFEioServiceInvoices\Hooks
 */
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
        $invoiceData = \WHMCS\Billing\Invoice::find($this->invoiceId);
        $userId = $invoiceData->userid;
        $clientRepository = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $clientIssueCondition = $clientRepository->getClientIssueCondition($userId);
        $moduleIssueCondition = strtolower($storage->get('issue_note_default_cond'));
        $issueNoteAfter = $storage->get('issue_note_after');
        $generateTaxBill = false;
        $generateTaxBillWhen = 'quando a fatura é gerada';

        if ($invoiceData->total > 0.00 and (!$issueNoteAfter or $issueNoteAfter == 0) and $this->invoiceStatus != 'Draft') {
            $generateTaxBill = true;
        }

        $data = [
            'invoiceID' => $this->invoiceId,
            'invoiceData' => $invoiceData,
            'userID' => $userId,
            'clientIssueCondition' => $clientIssueCondition,
            'moduleIssueCondition' => $moduleIssueCondition,
            'issueNoteAfter' => $issueNoteAfter,
            'generateTaxBill' => $generateTaxBill,
            'generateTaxBillWhen' => $generateTaxBillWhen
        ];

        if ($clientIssueCondition == 'seguir configuração do módulo nfe.io' and $moduleIssueCondition == $generateTaxBillWhen and $generateTaxBill) {
            $queue = $nfe->queue($this->invoiceId);
            logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, $data, $queue);
        }
        if ($clientIssueCondition == $generateTaxBillWhen and $generateTaxBill) {
            $queue = $nfe->queue($this->invoiceId);
            logModuleCall('NFEioServiceInvoices', __CLASS__ . __FUNCTION__, $data, $queue);
        }
    }
}
