<?php

namespace NFEioServiceInvoices\Legacy;

use WHMCS\Database\Capsule;

class Hooks
{
    private $config;
    private $functions;
    private $serviceInvoicesRepo;
    private $productCodeRepo;
    private $clientConfigurationRepo;
    /**
     * @var \NFEioServiceInvoices\NFEio\Nfe
     */
    private $nf;

    public function __construct()
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->functions = new \NFEioServiceInvoices\Legacy\Functions();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->productCodeRepo = new \NFEioServiceInvoices\Models\ProductCode\Repository();
        $this->clientConfigurationRepo = new \NFEioServiceInvoices\Models\ClientConfiguration\Repository();
        $this->nf = new \NFEioServiceInvoices\NFEio\Nfe();
    }

    function invoicecancelled($vars)
    {
        $params = $this->functions->gnfe_config();
        $invoiceId = $vars['invoiceid'];
        if ($params['cancel_invoice_cancel_nfe']) {
            $this->nf->cancelNfSeriesByInvoiceId($invoiceId);
        }
    }

    function productdelete($vars)
    {
        $productCodeTable = $this->productCodeRepo->tableName();
        try {
            $delete = Capsule::table($productCodeTable)->where('product_id', '=', $vars['pid'])->delete();
            logModuleCall('nfeio_serviceinvoices', 'nf_product_delete', 'product_id=' . $vars['pid'], $delete, 'OK', '');
        } catch (Exception $e) {
            logModuleCall('nfeio_serviceinvoices', 'nf_product_delete_error', $vars['pid'], $e->getMessage());
        }
    }

    function customclientissueinvoice($vars)
    {
        $_table = $this->clientConfigurationRepo->tableName();
        $result = [];

        try {
            if (Capsule::schema()->hasTable($_table)) {
                $result = ['Emitir nota fiscal quando?' => $this->functions->gnfe_show_issue_invoice_conds($vars['userid'])];
            } else {
                $result = ['Módulo NFE.io' => 'Não existem opções'];
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        return $result;
    }
}
