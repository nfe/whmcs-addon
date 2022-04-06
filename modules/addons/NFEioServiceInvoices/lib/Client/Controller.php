<?php


namespace NFEioServiceInvoices\Client;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once(dirname(dirname(__DIR__)) . DS . 'Loader.php');

/**
 * Classe responsável pelos controllers da area do cliente
 * @version 2.1
 * @author Andre Bellafronte
 */
class Controller {

    /**
     * Método para download da NF em PDF na area do cliente.
     * @version 2.1
     * @author Andre Bellafronte
     * @param $vars array variável WHMCS
     * @return void PDF
     */
    public function downloadNfPdf($vars)
    {
        $currentUser = new \WHMCS\Authentication\CurrentUser;
        $client = $currentUser->client();
        if ($client) {
            $nfId = $_GET['nfid'];
            $legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
            $legacyFunctions->gnfe_pdf_nfe($nfId);
            exit();
        } else {
            http_response_code(401);

        }
    }

    /**
     * Método para download da NF em XML na area do cliente.
     * @version 2.1
     * @author Andre Bellafronte
     * @param $vars array variável WHMCS
     * @return void XML
     */
    public function downloadNfXml($vars)
    {
        $currentUser = new \WHMCS\Authentication\CurrentUser;
        $client = $currentUser->client();
        if($client) {
            $nfId = $_GET['nfid'];
            $legacyFunctions = new \NFEioServiceInvoices\Legacy\Functions();
            header('Content-type: application/xml');
            header("Content-Disposition: attachment; filename=".$nfId.".xml");
            echo $legacyFunctions->gnfe_xml_nfe($nfId);
            exit();
        } else {
            http_response_code(401);
        }

    }
}
