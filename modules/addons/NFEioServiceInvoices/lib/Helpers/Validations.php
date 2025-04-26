<?php

namespace NFEioServiceInvoices\Helpers;

class Validations
{
    /**
     * Verifica se dado valor é um CPF válido usando calculo de verificação de CPF.
     * @param $cpf CPF a ser validado. Pode ser enviado com ou sem máscara.
     * @return bool true se CPF válido, senão false
     */
    public static function validateCPF($cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifica se dado valor é um CNPJ válido usando calculo de verificação de CNPJ.
     * @param $cnpj CNPJ a ser validado. Pode ser enviado com ou sem máscara.
     * @return bool true se CNPJ válido, senão false
     */
    public static function validateCNPJ($cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        for ($t = 12; $t < 14; $t++) {
            for ($d = 0, $p = $t - 7, $c = 0; $c < $t; $c++) {
                $d += $cnpj[$c] * $p;
                $p = ($p < 3) ? 9 : --$p;
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cnpj[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifica se o hash da assinatura de webhook é válido.
     *
     * @see https://nfe.io/docs/documentacao/webhooks/duvidas-frequentes/#2_Como_saber_se_o_webhook_que_recebi_e_da_NFEio
     *
     * @param string $secret O segredo usado na computação do hash.
     * @param mixed $payload O payload usado na computação do hash.
     * @param string $signature A assinatura a ser verificada.
     * @param string $algo O algoritmo de hash usado para a computação.
     *
     * @return bool True se a assinatura é válida, false caso contrário.
     */
    public static function webhookHashValid(string $secret, $payload, string $signature, string $algo = "sha1"): bool
    {
        $instance = new self();
        $hash = $instance->webhookComputeHash($algo, $secret, $payload);
        $signature = base64_decode($signature);
        return hash_equals($hash, $signature);
    }

    /**
     * Computa o hash usando o algoritmo e segredo especificados.
     *
     * @param string $algo O algoritmo de hash a ser usado.
     * @param string $secret O segredo a ser usado na computação do hash.
     * @param mixed $payload O payload a ser usado na computação do hash.
     * @param bool $bencode Define se o hash deve ser codificado em base64.
     *
     * @return string The computed hash.
     */
    private function webhookComputeHash(string $algo, string $secret, $payload, bool $bencode = false): string
    {
        $hex_hash = hash_hmac($algo, $payload, utf8_encode($secret));
        $result = $bencode ? base64_encode(hex2bin($hex_hash)) : hex2bin($hex_hash);
        logModuleCall('nfeio_serviceinvoices', 'webhook_hmac', [
            'algo' => $algo, 'secret' => $secret, 'payload' => $payload
        ], $result);

        return $result;
    }

    /**
     * Gera uma chave secreta criptograficamente segura.
     *
     * @param int $length Comprimento da chave em bytes. Padrão é 16.
     * @return string Chave secreta em formato hexadecimal.
     * @throws \Exception Se não for possível obter bytes aleatórios.
     */
    public static function generateSecretKey(int $length = 16): string
    {
        return bin2hex(random_bytes($length));

    }

}
