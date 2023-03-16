<?php

namespace NFEioServiceInvoices\Hooks;

class AdminInvoicesControlsOutput
{
    private $invoiceId;

    public function __construct(array $vars)
    {
        $this->invoiceId = $vars['invoiceid'];
        // indica ao módulo que as ações e caminhos usadas aqui serão executadas na area administrativa do WHMCS.
        \NFEioServiceInvoices\Addon::I()->isAdmin(true);
    }

    public function run()
    {
        $whmcs = \WHMCS\Application::getInstance();
        $legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
        $template = new \WHMCSExpert\Template\Template(\NFEioServiceInvoices\Addon::getModuleTemplatesDir());
        $assetsURL = \NFEioServiceInvoices\Addon::I()->getAssetsURL();
        $post = $_POST;
        $request = $post['nfeiosi'];
        //$request = $whmcs->get_req_var("nfeiosi");
        $msg = new \Plasticbrain\FlashMessages\FlashMessages();
        $config = new \NFEioServiceInvoices\Configuration();
        $storage = new \WHMCSExpert\Addon\Storage($config->getStorageKey());
        $serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $nfe = new \NFEioServiceInvoices\NFEio\Nfe();
        $totalServiceInvoices = $serviceInvoicesRepo->getTotalById($this->invoiceId);
        $serviceInvoicesQueryLimit = $serviceInvoicesRepo->getLimit();
        $localServiceInvoices = $serviceInvoicesRepo->getServiceInvoicesById($this->invoiceId);
        $urn = $whmcs->getPhpSelf() . '?action=edit&id=' . $this->invoiceId;
        $hasAllNfCancelled = $nfe->hasAllNfCancelled($this->invoiceId);

        $vars = [
            'invoiceId' => $this->invoiceId,
            'invoiceStatus' => \NFEioServiceInvoices\Helpers\Invoices::getInvoiceStatus($this->invoiceId),
            'totalServiceInvoices' => $totalServiceInvoices,
            'serviceInvoicesQueryLimit' => $serviceInvoicesQueryLimit,
            'localServiceInvoices' => $localServiceInvoices,
            'companyId' => $storage->get('company_id'),
            'urn' => $urn,
            'hasAllNfCancelled' => $hasAllNfCancelled
        ];

        if ($request === 'create' && $totalServiceInvoices == 0) {
            $queue = $nfe->queue($this->invoiceId);
            if ($queue['success']) {
                $msg->success('Nota adicionada a fila de emissão.');
            } else {
                $msg->error("Problemas ao tentar criar a nota: {$queue['message']}");
            }
        }

        if ($request === 'reissue') {
            $result = $nfe->queue($this->invoiceId, true);
            if ($result['success']) {
                $msg->success('Nota adicionada a fila para reemissão.');
            } else {
                $msg->error("Problemas ao tentar reemitir a nota: {$result['message']}");
            }
        }

        if ($request === 'cancel') {
            $result = $nfe->cancelNfSeriesByInvoiceId($this->invoiceId);
            if ($result['status'] === 'success') {
                $msg->info("Nota enviada para cancelamento, por favor aguarde.");
            } else {
                $msg->warning("Nota fiscal cancelada, mas com aviso: {$result['message']}.");
            }
        }

        if ($request === 'email') {
            $nfeId = $post['nfe_id'];
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
