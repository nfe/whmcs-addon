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
        $template = new \WHMCSExpert\Template\Template(\NFEioServiceInvoices\Addon::getModuleTemplatesDir());
        $post = $_POST;
        $request = $post['nfeiosi'];
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
                $msg->success('Nota adicionada a fila para reemissão.', $urn . '&nfeioreissue=true');
            } else {
                $msg->error("Problemas ao tentar reemitir a nota: {$result['message']}", $urn . '&nfeioreissue=true');
            }
        }

        if ($request === 'cancel') {
            $result = $nfe->cancelNfSeriesByInvoiceId($this->invoiceId);
            if ($result['status'] === 'success') {
                $msg->info("Nota enviada para cancelamento, por favor aguarde.", $urn . '&nfeiocancel=true');
            } else {
                $msg->warning("Nota fiscal cancelada, mas com aviso: {$result['message']}.", $urn . '&nfeiocancel=true');
            }
        }

        if ($request === 'email') {
            $nfeId = $post['nfe_id'];
            $companyId = $post['company_id'];
            $result = $nfe->sendNfeioEmail($nfeId, $companyId);
            if (!$result['error']) {
                $msg->info("Nota enviada por e-mail com sucesso.");
            } else {
                $msg->error("Problemas ao enviar e-mail: {$result['error']}.");
            }
        }


        if ($msg->hasMessages()) {
            $msg->display();
        }

        return $template->display('admininvoicescontrolsoutput', $vars);
    }
}
