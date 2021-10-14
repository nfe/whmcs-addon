<?php
/**
 * WHMCS SDK Sample Addon Module Hooks File
 *
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * This allows you to execute your own code in addition to, or sometimes even
 * instead of that which WHMCS executes by default.
 *
 * @see https://developers.whmcs.com/hooks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// ATENÇÃO! QUALQUER HOOK QUE SEJA NECESSÁRIO NO MODULO QUE ESTA SENDO DESENVOLVIDO
// DEVERA USAR A VERIFICAÇÃO DA LICENÇA ANTES DO SEU ACIONAMENTO.
// APENAS E TÃO SOMENTE COM A LICENÇA VÁLIDA É QUE SE PODERÁ EXECUTAR OS GATILHOS.
// COM ISSO EVITAMOS QUE O MODULO CONTINUE OPERANDO NORMALMENTE CASO A LICENÇA
// SEJA INVÁLIDA OU ESTEJA SUSPENSA.

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'Loader.php';

// Exemplo de Classes para utilização no Hooks

// use WHMCS\Database\Capsule;
// use WHMCS\ClientArea;

/**
 * Register a hook with WHMCS.
 *
 * This sample demonstrates triggering a service call when a change is made to
 * a client profile within WHMCS.
 *
 * For more information, please refer to https://developers.whmcs.com/hooks/
 *
 * add_hook(string $hookPointName, int $priority, string|array|Closure $function)
 */
// add_hook('ClientEdit', 1, function(array $params) {
//     try {
//         // Call the service's function, using the values provided by WHMCS in
//         // `$params`.
//     } catch (Exception $e) {
//         // Consider logging or reporting the error.
//     }
// });


