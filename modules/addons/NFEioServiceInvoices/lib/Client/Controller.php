<?php

namespace NFEioServiceInvoices\Client;

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
        $currentUser = new \WHMCS\Authentication\CurrentUser();
        $client = $currentUser->client();
        if ($client) {
            $nfId = $_GET['nfid'];
            $companyId = $_GET['c'];
            $nfeio = new Nfe();
            $pdf = $nfeio->getPdf($nfId, $companyId);
            header('Location:' . $pdf);
            exit;
        } else {
            http_response_code(401);
            return 'Usuário não autorizado.';
        }
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
        $currentUser = new \WHMCS\Authentication\CurrentUser();
        $client = $currentUser->client();
        if ($client) {
            $nfId = $_GET['nfid'];
            $companyId = $_GET['c'];
            $nfeio = new Nfe();
            $xmlUrl = $nfeio->getXml($nfId, $companyId);

            // Faz o download do conteúdo do XML
            $xmlContent = file_get_contents($xmlUrl);

            // Verifica se o conteúdo foi baixado corretamente
            if ($xmlContent === false) {
                http_response_code(500);
                echo 'Erro: Não foi possível baixar o XML.';
                exit;
            }

            // Força o download do XML
            header('Content-Type: application/xml');
            header('Content-Disposition: attachment; filename="' . $nfId . '.xml"');
            header('Content-Length: ' . strlen($xmlContent));
            echo $xmlContent;
            exit;
        } else {
            http_response_code(401);
            return 'Usuário não autorizado.';
        }
    }
}
