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
        if ($params['cancel_invoice_cancel_nfe']) {
            $nfe_for_invoice = $this->functions->gnfe_get_local_nfe($vars['invoiceid'], ['nfe_id', 'status', 'services_amount', 'environment']);
            if ($nfe_for_invoice['status'] === (string) 'Issued') {
                $invoice = \WHMCS\Billing\Invoice::find($vars['invoiceid']);
                $clientId = $invoice->userid;
                $delete_nfe = $this->functions->gnfe_delete_nfe($nfe_for_invoice['nfe_id']);
                if (!$delete_nfe->message) {
                    logModuleCall('nfeio_serviceinvoices', 'nf_canceled', $nfe_for_invoice['nfe_id'], $delete_nfe);
                    $gnfe_update_nfe = $this->functions->gnfe_update_nfe((object) ['id' => $nfe_for_invoice['nfe_id'], 'status' => 'Cancelled', 'servicesAmount' => $nfe_for_invoice['services_amount'], 'environment' => $nfe_for_invoice['environment'], 'flow_status' => $nfe_for_invoice['flow_status']], $clientId, $vars['invoiceid'], 'n/a', $nfe_for_invoice['created_at'], date('Y-m-d H:i:s'));
                } else {
                    logModuleCall('nfeio_serviceinvoices', 'nf_canceled_error', $nfe_for_invoice['nfe_id'], $delete_nfe);
                }
            }
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
                $result =  ['Emitir nota fiscal quando' => $this->functions->gnfe_show_issue_invoice_conds($vars['userid'])];
            } else {
                $result =  ['Módulo NFE.io' => 'Não existem opções'];
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        return $result;
    }
}
