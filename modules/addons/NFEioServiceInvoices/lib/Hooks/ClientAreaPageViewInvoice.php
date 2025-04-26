<?php

namespace NFEioServiceInvoices\Hooks;

use WHMCS\Database\Capsule;

class ClientAreaPageViewInvoice
{
    /**
     * @var \NFEioServiceInvoices\Configuration
     */
    private $config;
    /**
     * @var \NFEioServiceInvoices\Models\ServiceInvoices\Repository
     */
    private $serviceInvoicesRepo;
    /**
     * @var \NFEioServiceInvoices\NFEio\Nfe
     */
    private $nf;

    private $invoiceId;

    public function __construct(array $vars)
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->nf = new \NFEioServiceInvoices\NFEio\Nfe();
        $this->invoiceId = $vars['invoiceid'];
    }

    public function run()
    {
        $vars = [];
        $taxBills = [];

        $localServiceInvoices = $this->serviceInvoicesRepo->getServiceInvoicesById($this->invoiceId);

        foreach ($localServiceInvoices as $nf) {
            $taxBills[] = [
                'id' => $nf->id,
                'nfe_id' => $nf->nfe_id,
                'status' => $nf->status,
                'status_flow' => $nf->flow_status,
                'amount' => $nf->services_amount,
                'company_id' => $nf->company_id,
            ];
        }

        $vars['nfeIoTaxBills'] = $taxBills;


        return $vars;
    }
}
