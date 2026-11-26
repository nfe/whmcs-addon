<?php

namespace NFEioServiceInvoices\Helpers;

use WHMCS\Database\Capsule;

/**
 * Obtém o cliente autenticado de forma compatível com WHMCS 7.x e 8+.
 *
 * WHMCS 8+ expõe \WHMCS\Authentication\CurrentUser; no WHMCS 7 a sessão
 * do cliente fica em $_SESSION['uid'].
 */
class Auth
{
    /**
     * @return \WHMCS\User\Client|object|null
     */
    public static function client()
    {
        if (class_exists('\\WHMCS\\Authentication\\CurrentUser')) {
            try {
                $currentUser = new \WHMCS\Authentication\CurrentUser();
                $client = $currentUser->client();
                if ($client) {
                    return $client;
                }
            } catch (\Throwable $e) {
                // fallback para sessão do WHMCS 7
            }
        }

        $clientId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
        if ($clientId <= 0) {
            return null;
        }

        if (class_exists('\\WHMCS\\User\\Client')) {
            $client = \WHMCS\User\Client::find($clientId);
            if ($client) {
                return $client;
            }
        }

        $row = Capsule::table('tblclients')->where('id', '=', $clientId)->first();
        if (!$row) {
            return null;
        }

        return (object) array(
            'id' => (int) $row->id,
        );
    }
}
