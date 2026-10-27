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

        // só lista as notas se a fatura pertencer ao cliente logado (GHSA-3pwc-wpfp-qmmj)
        if (!$this->isInvoiceOwnedByCurrentClient()) {
            $vars['nfeIoTaxBills'] = $taxBills;
            return $vars;
        }

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

    /**
     * Verifica se a fatura exibida pertence ao cliente logado.
     *
     * Evita expor IDs de notas e empresas emissoras de outros clientes
     * caso o hook seja disparado para uma fatura de terceiros.
     *
     * @return bool true se a fatura pertencer ao cliente logado
     */
    private function isInvoiceOwnedByCurrentClient()
    {
        $currentUser = new \WHMCS\Authentication\CurrentUser();
        $client = $currentUser->client();

        if (!$client || empty($this->invoiceId)) {
            return false;
        }

        $invoiceUserId = Capsule::table('tblinvoices')->where('id', '=', $this->invoiceId)->value('userid');

        return $invoiceUserId !== null && (int) $invoiceUserId === (int) $client->id;
    }
}
