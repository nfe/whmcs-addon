<?php

namespace NFEioServiceInvoices\Hooks;


class AdminInvoicesControlsOutput
{

    private $invoiceId;
    private $userId;
    private $invoiceSubTotal;
    private $invoiceTax;
    private $invoiceTax2;
    private $invoiceTotal;
    private $invoiceTaxRate;
    private $invoiceTaxRate2;

    public function __construct(array $vars)
    {
        $this->invoiceId = $vars['invoiceid'];
        $this->userId = $vars['userid'];
        $this->invoiceSubTotal = $vars['subtotal'];
        $this->invoiceTax = $vars['tax'];
        $this->invoiceTax2 = $vars['tax2'];
        $this->invoiceTotal = $vars['total'];
        $this->invoiceTaxRate = $vars['taxrate'];
        $this->invoiceTaxRate2 = $vars['taxrate2'];
        // indica ao módulo que as ações e caminhos usadas aqui serão executadas na area administrativa do WHMCS.
        \NFEioServiceInvoices\Addon::I()->isAdmin(true);
    }

    public function run()
    {
        $legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
        $template = new \WHMCSExpert\Template\Template(\NFEioServiceInvoices\Addon::getModuleTemplatesDir());
        $assetsURL = \NFEioServiceInvoices\Addon::I()->getAssetsURL();
        $request = $_POST['nfeiosi'];
        $msg = new \Plasticbrain\FlashMessages\FlashMessages;
        $config = new \NFEioServiceInvoices\Configuration();
        $storage = new \WHMCSExpert\Addon\Storage($config->getStorageKey());
        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $totalServiceInvoices = $serviceInvoicesRepo->getTotalById($this->invoiceId);
        $serviceInvoicesQueryLimit = $serviceInvoicesRepo->getLimit();
        $localServiceInvoices = $serviceInvoicesRepo->getServiceInvoicesById($this->invoiceId);
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();

        $vars = [
            'invoiceId' => $this->invoiceId,
            'invoiceStatus' => \NFEioServiceInvoices\Helpers\Invoices::getInvoiceStatus($this->invoiceId),
            'totalServiceInvoices' => $totalServiceInvoices,
            'serviceInvoicesQueryLimit' => $serviceInvoicesQueryLimit,
            'localServiceInvoices' => $localServiceInvoices,
            'companyId' => $storage->get('company_id')
        ];

        if ($request === 'create' && $totalServiceInvoices == 0) {
            $queue = $nfe->queue($this->invoiceId);
            //$queue = $legacyFunctions->gnfe_queue_nfe($this->invoiceId, true);
            if($queue['success']) {
                $msg->success('Nota adicionada a fila de criação.');
            } else {
                $msg->error("Problemas ao tentar criar a nota: {$queue['message']}");
            }
        }

        if ($request === 'cancel') {
            $nfeId = $_POST['nfeiosi_id'];
            $result = $legacyFunctions->gnfe_delete_nfe($nfeId);
            if (!$result->message) {
                logModuleCall('nfeioserviceinvoices', 'cancel_nf', $nfeId, $result);
                $msg->info("Nota cancelada com sucesso.");

            } else {
                $response = $nfe->updateLocalNfeStatus($nfeId, 'Cancelled');
                logModuleCall('nfeioserviceinvoices', 'cancel_nf', $nfeId, "NF API Response: \n {$result->message} \n NF LOCAL Response: \n {$response}");
                $msg->warning("Nota fiscal cancelada, mas com aviso: {$result->message}.");
            }
        }

        if ($request === 'email') {
            $nfeId = $_POST['nfeiosi_id'];
            $result = $legacyFunctions->gnfe_email_nfe($nfeId);
            if (!$result->message) {
                $msg->info("Nota enviada por e-mail com sucesso.");

            } else {
                $msg->error("Problemas ao enviar e-mail: {$result->message}.");
            }
        }


        if ($msg->hasMessages()) {
            $msg->display();
        }

        return $template->display('admininvoicescontrolsoutput', $vars);


    }


}