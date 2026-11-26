<?php

namespace NFEioServiceInvoices\Client;

use NFEioServiceInvoices\Helpers\Auth;
use NFEioServiceInvoices\Models\ServiceInvoices\Repository;
use NFEioServiceInvoices\NFEio\Nfe;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once dirname(dirname(__DIR__)) . DS . 'Loader.php';

/**
 * Classe responsável pelos controllers da area do cliente
 *
 * @since 2.1
 * @version 3.0
 * @author  Andre Bellafronte
 */
class Controller
{
    /**
     * Formato esperado do ID da nota fiscal na NFE.io (ObjectId hexadecimal de 24 caracteres).
     */
    const NFE_ID_PATTERN = '/^[a-f0-9]{24}$/i';

    /**
     * Método para download da NF em PDF na area do cliente.
     *
     * @param   $vars array variável WHMCS
     * @return  void PDF
     * @since 2.1
     * @version 3.0
     * @author  Andre Bellafronte
     */
    public function downloadNfPdf($vars)
    {
        $serviceInvoice = $this->resolveServiceInvoiceForDownload();
        $nfeio = new Nfe();
        $pdf = $nfeio->getPdf($serviceInvoice->nfe_id, $serviceInvoice->company_id);

        if (empty($pdf) || is_array($pdf) || !is_string($pdf)) {
            $detail = is_array($pdf) && !empty($pdf['error']) ? $pdf['error'] : 'resposta vazia';
            logModuleCall(
                'nfeio_serviceinvoices',
                'download_nf_pdf_error',
                [
                    'nfe_id' => $serviceInvoice->nfe_id,
                    'company_id' => $serviceInvoice->company_id,
                ],
                $detail
            );
            http_response_code(502);
            echo 'Não foi possível obter o PDF da nota fiscal na NFE.io.';
            exit;
        }

        header('Location: ' . $pdf);
        exit;
    }

    /**
     * Método para download da NF em XML na area do cliente.
     *
     * @param   $vars array variável WHMCS
     * @return  void XML
     * @since 2.1
     * @version 3.0
     * @author  Andre Bellafronte
     */
    public function downloadNfXml($vars)
    {
        $serviceInvoice = $this->resolveServiceInvoiceForDownload();
        $nfeio = new Nfe();
        $xmlUrl = $nfeio->getXml($serviceInvoice->nfe_id, $serviceInvoice->company_id);

        if (empty($xmlUrl) || is_array($xmlUrl) || !is_string($xmlUrl)) {
            $detail = is_array($xmlUrl) && !empty($xmlUrl['error']) ? $xmlUrl['error'] : 'resposta vazia';
            logModuleCall(
                'nfeio_serviceinvoices',
                'download_nf_xml_error',
                [
                    'nfe_id' => $serviceInvoice->nfe_id,
                    'company_id' => $serviceInvoice->company_id,
                ],
                $detail
            );
            http_response_code(502);
            echo 'Não foi possível obter o XML da nota fiscal na NFE.io.';
            exit;
        }

        $xmlContent = @file_get_contents($xmlUrl);

        if ($xmlContent === false) {
            http_response_code(500);
            echo 'Erro: Não foi possível baixar o XML.';
            exit;
        }

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="' . $serviceInvoice->nfe_id . '.xml"');
        header('Content-Length: ' . strlen($xmlContent));
        echo $xmlContent;
        exit;
    }

    /**
     * Resolve a nota local para download: cliente dono (via user_id ou fatura)
     * ou administrador autenticado (links nas Notas no painel admin).
     *
     * @return object
     */
    private function resolveServiceInvoiceForDownload()
    {
        $nfId = isset($_GET['nfid']) ? $_GET['nfid'] : '';

        if (!is_string($nfId) || !preg_match(self::NFE_ID_PATTERN, $nfId)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            exit;
        }

        $serviceInvoicesRepo = new Repository();
        $client = Auth::client();

        if ($client && !empty($client->id)) {
            $serviceInvoice = $serviceInvoicesRepo->findByNfeIdForClient($nfId, $client->id);
            if ($serviceInvoice && preg_match(self::NFE_ID_PATTERN, (string) $serviceInvoice->nfe_id)) {
                return $serviceInvoice;
            }
        }

        // Admin logado: permite baixar pelos links gravados em tblinvoices.notes
        if ($this->isAdminLoggedIn()) {
            $serviceInvoice = $serviceInvoicesRepo->findByNfeId($nfId);
            if ($serviceInvoice && preg_match(self::NFE_ID_PATTERN, (string) $serviceInvoice->nfe_id)) {
                return $serviceInvoice;
            }
        }

        if (!$client || empty($client->id)) {
            http_response_code(401);
            echo 'Usuário não autorizado.';
            exit;
        }

        http_response_code(404);
        echo 'Nota fiscal não encontrada.';
        exit;
    }

    /**
     * @return bool
     */
    private function isAdminLoggedIn()
    {
        if (!empty($_SESSION['adminid'])) {
            return true;
        }

        if (class_exists('\\WHMCS\\Authentication\\CurrentUser')) {
            try {
                $currentUser = new \WHMCS\Authentication\CurrentUser();
                if (method_exists($currentUser, 'isAuthenticatedAdmin') && $currentUser->isAuthenticatedAdmin()) {
                    return true;
                }
                if (method_exists($currentUser, 'admin') && $currentUser->admin()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ignora e considera não-admin
            }
        }

        return false;
    }
}
