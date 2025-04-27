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

    /**
     * Retorna o código ISO 3166-1 alpha-3 para um país dado seu código ISO 3166-1 alpha-2.
     *
     * @param string $country Código do país no formato ISO alpha-2.
     * @return string|null Código do país no formato ISO alpha-3 ou null se não encontrado.
     */
    public static function countryCode(string $country): ?string
    {
        return [
            'BD'=>'BGD','BE'=>'BEL','BF'=>'BFA','BG'=>'BGR','BA'=>'BIH','BB'=>'BRB','WF'=>'WLF','BL'=>'BLM',
            'BM'=>'BMU','BN'=>'BRN','BO'=>'BOL','BH'=>'BHR','BI'=>'BDI','BJ'=>'BEN','BT'=>'BTN','JM'=>'JAM',
            'BV'=>'BVT','BW'=>'BWA','WS'=>'WSM','BQ'=>'BES','BR'=>'BRA','BS'=>'BHS','JE'=>'JEY','BY'=>'BLR',
            'BZ'=>'BLZ','RU'=>'RUS','RW'=>'RWA','RS'=>'SRB','TL'=>'TLS','RE'=>'REU','TM'=>'TKM','TJ'=>'TJK',
            'RO'=>'ROU','TK'=>'TKL','GW'=>'GNB','GU'=>'GUM','GT'=>'GTM','GS'=>'SGS','GR'=>'GRC','GQ'=>'GNQ',
            'GP'=>'GLP','JP'=>'JPN','GY'=>'GUY','GG'=>'GGY','GF'=>'GUF','GE'=>'GEO','GD'=>'GRD','GB'=>'GBR',
            'GA'=>'GAB','SV'=>'SLV','GN'=>'GIN','GM'=>'GMB','GL'=>'GRL','GI'=>'GIB','GH'=>'GHA','OM'=>'OMN',
            'TN'=>'TUN','JO'=>'JOR','HR'=>'HRV','HT'=>'HTI','HU'=>'HUN','HK'=>'HKG','HN'=>'HND','HM'=>'HMD',
            'VE'=>'VEN','PR'=>'PRI','PS'=>'PSE','PW'=>'PLW','PT'=>'PRT','SJ'=>'SJM','PY'=>'PRY','IQ'=>'IRQ',
            'PA'=>'PAN','PF'=>'PYF','PG'=>'PNG','PE'=>'PER','PK'=>'PAK','PH'=>'PHL','PN'=>'PCN','PL'=>'POL',
            'PM'=>'SPM','ZM'=>'ZMB','EH'=>'ESH','EE'=>'EST','EG'=>'EGY','ZA'=>'ZAF','EC'=>'ECU','IT'=>'ITA',
            'VN'=>'VNM','SB'=>'SLB','ET'=>'ETH','SO'=>'SOM','ZW'=>'ZWE','SA'=>'SAU','ES'=>'ESP','ER'=>'ERI',
            'ME'=>'MNE','MD'=>'MDA','MG'=>'MDG','MF'=>'MAF','MA'=>'MAR','MC'=>'MCO','UZ'=>'UZB','MM'=>'MMR',
            'ML'=>'MLI','MO'=>'MAC','MN'=>'MNG','MH'=>'MHL','MK'=>'MKD','MU'=>'MUS','MT'=>'MLT','MW'=>'MWI',
            'MV'=>'MDV','MQ'=>'MTQ','MP'=>'MNP','MS'=>'MSR','MR'=>'MRT','IM'=>'IMN','UG'=>'UGA','TZ'=>'TZA',
            'MY'=>'MYS','MX'=>'MEX','IL'=>'ISR','FR'=>'FRA','IO'=>'IOT','SH'=>'SHN','FI'=>'FIN','FJ'=>'FJI',
            'FK'=>'FLK','FM'=>'FSM','FO'=>'FRO','NI'=>'NIC','NL'=>'NLD','NO'=>'NOR','NA'=>'NAM','VU'=>'VUT',
            'NC'=>'NCL','NE'=>'NER','NF'=>'NFK','NG'=>'NGA','NZ'=>'NZL','NP'=>'NPL','NR'=>'NRU','NU'=>'NIU',
            'CK'=>'COK','XK'=>'XKX','CI'=>'CIV','CH'=>'CHE','CO'=>'COL','CN'=>'CHN','CM'=>'CMR','CL'=>'CHL',
            'CC'=>'CCK','CA'=>'CAN','CG'=>'COG','CF'=>'CAF','CD'=>'COD','CZ'=>'CZE','CY'=>'CYP','CX'=>'CXR',
            'CR'=>'CRI','CW'=>'CUW','CV'=>'CPV','CU'=>'CUB','SZ'=>'SWZ','SY'=>'SYR','SX'=>'SXM','KG'=>'KGZ',
            'KE'=>'KEN','SS'=>'SSD','SR'=>'SUR','KI'=>'KIR','KH'=>'KHM','KN'=>'KNA','KM'=>'COM','ST'=>'STP',
            'SK'=>'SVK','KR'=>'KOR','SI'=>'SVN','KP'=>'PRK','KW'=>'KWT','SN'=>'SEN','SM'=>'SMR','SL'=>'SLE',
            'SC'=>'SYC','KZ'=>'KAZ','KY'=>'CYM','SG'=>'SGP','SE'=>'SWE','SD'=>'SDN','DO'=>'DOM','DM'=>'DMA',
            'DJ'=>'DJI','DK'=>'DNK','VG'=>'VGB','DE'=>'DEU','YE'=>'YEM','DZ'=>'DZA','US'=>'USA','UY'=>'URY',
            'YT'=>'MYT','UM'=>'UMI','LB'=>'LBN','LC'=>'LCA','LA'=>'LAO','TV'=>'TUV','TW'=>'TWN','TT'=>'TTO',
            'TR'=>'TUR','LK'=>'LKA','LI'=>'LIE','LV'=>'LVA','TO'=>'TON','LT'=>'LTU','LU'=>'LUX','LR'=>'LBR',
            'LS'=>'LSO','TH'=>'THA','TF'=>'ATF','TG'=>'TGO','TD'=>'TCD','TC'=>'TCA','LY'=>'LBY','VA'=>'VAT',
            'VC'=>'VCT','AE'=>'ARE','AD'=>'AND','AG'=>'ATG','AF'=>'AFG','AI'=>'AIA','VI'=>'VIR','IS'=>'ISL',
            'IR'=>'IRN','AM'=>'ARM','AL'=>'ALB','AO'=>'AGO','AQ'=>'ATA','AS'=>'ASM','AR'=>'ARG','AU'=>'AUS',
            'AT'=>'AUT','AW'=>'ABW','IN'=>'IND','AX'=>'ALA','AZ'=>'AZE','IE'=>'IRL','ID'=>'IDN','UA'=>'UKR',
            'QA'=>'QAT','MZ'=>'MOZ'
        ][$country] ?? null;
    }

    /**
     * Remove caracteres não numéricos de um CEP e garante 8 dígitos.
     *
     * @param string $postcode CEP a ser sanitizado. Pode conter máscara ou outros caracteres.
     * @return string|false Retorna o CEP com apenas 8 dígitos ou false se o comprimento for inválido.
     */
    public static function sanitizePostCode($postcode)
    {
        // Remove caracteres não numéricos
        $postcode = preg_replace('/\D/', '', $postcode);

        // Verifica se o comprimento é 8 ou 9
        if (strlen($postcode) !== 8 && strlen($postcode) !== 9) {
            return false;
        }

        // Se o comprimento for 9, remova o último caractere
        if (strlen($postcode) === 9) {
            $postcode = substr($postcode, 0, -1);
        }

        return $postcode;
    }

}
