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

}