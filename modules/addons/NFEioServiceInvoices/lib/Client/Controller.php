<?php

namespace NFEioServiceInvoices\Client;

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
        $currentUser = new \WHMCS\Authentication\CurrentUser();
        $client = $currentUser->client();
        if ($client) {
            $serviceInvoice = $this->findClientServiceInvoice($client);
            $nfeio = new Nfe();
            $pdf = $nfeio->getPdf($serviceInvoice->nfe_id, $serviceInvoice->company_id);
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
            $serviceInvoice = $this->findClientServiceInvoice($client);
            $nfeio = new Nfe();
            $xmlUrl = $nfeio->getXml($serviceInvoice->nfe_id, $serviceInvoice->company_id);

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
            header('Content-Disposition: attachment; filename="' . $serviceInvoice->nfe_id . '.xml"');
            header('Content-Length: ' . strlen($xmlContent));
            echo $xmlContent;
            exit;
        } else {
            http_response_code(401);
            return 'Usuário não autorizado.';
        }
    }

    /**
     * Busca a nota fiscal solicitada em $_GET['nfid'] garantindo que pertença ao cliente logado.
     *
     * A empresa emissora é obtida do registro local; o parâmetro $_GET['c'] é ignorado
     * e mantido na URL apenas por compatibilidade com templates (GHSA-3pwc-wpfp-qmmj).
     * Encerra a requisição com 400 se o ID for inválido ou 404 se a nota não existir
     * para o cliente, sem consultar a API da NFE.io.
     *
     * Método privado para não ser exposto como action pelo ClientDispatcher.
     *
     * @param   $client \WHMCS\User\Client cliente logado
     * @return  object registro local da nota fiscal pertencente ao cliente
     */
    private function findClientServiceInvoice($client)
    {
        $nfId = isset($_GET['nfid']) ? $_GET['nfid'] : '';

        if (!is_string($nfId) || !preg_match(self::NFE_ID_PATTERN, $nfId)) {
            http_response_code(400);
            echo 'Requisição inválida.';
            exit;
        }

        $serviceInvoicesRepo = new Repository();
        $serviceInvoice = $serviceInvoicesRepo->findByNfeIdForClient($nfId, $client->id);

        // o nfe_id do registro é revalidado pois é usado em headers da resposta
        if (!$serviceInvoice || !preg_match(self::NFE_ID_PATTERN, (string) $serviceInvoice->nfe_id)) {
            http_response_code(404);
            echo 'Nota fiscal não encontrada.';
            exit;
        }

        return $serviceInvoice;
    }
}
