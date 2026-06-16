<?php

namespace NFEioServiceInvoices\Helpers;

/**
 * Fonte única dos valores do enum `taxationType` (regime de tributação do ISSQN)
 * aceitos pela emissão de NFS-e da Reforma Tributária (RTC).
 *
 * Os valores são copiados verbatim do contrato `service-invoice-rtc-v1.yaml`.
 * Atenção à grafia exata (ex.: `ObjectiveImune`, sem duplo "m") — precisa casar
 * com a API. Esta classe alimenta o dropdown de configuração e a validação dos
 * controllers; o banco guarda apenas o valor escolhido.
 *
 * @see https://github.com/nfe/whmcs-addon/issues/203
 * @see https://nfe.io/docs/api/service-invoice-rtc-v1.yaml
 */
class TaxationType
{
    /**
     * Valor assumido pela API quando `taxationType` não é informado.
     */
    const DEFAULT_VALUE = 'WithinCity';

    /**
     * @var string[] valores válidos do enum (verbatim do contrato RTC)
     */
    private static $values = [
        'None',
        'WithinCity',
        'OutsideCity',
        'Export',
        'Free',
        'Immune',
        'SuspendedCourtDecision',
        'SuspendedAdministrativeProcedure',
        'OutsideCityFree',
        'OutsideCityImmune',
        'OutsideCitySuspended',
        'OutsideCitySuspendedAdministrativeProcedure',
        'ObjectiveImune',
    ];

    /**
     * @var array<string,string> rótulos (pt-BR) para exibição no dropdown
     */
    private static $labels = [
        'None' => 'Nenhum',
        'WithinCity' => 'Tributável no município',
        'OutsideCity' => 'Tributável fora do município',
        'Export' => 'Exportação de serviço',
        'Free' => 'Isento',
        'Immune' => 'Imune',
        'SuspendedCourtDecision' => 'Suspenso por decisão judicial',
        'SuspendedAdministrativeProcedure' => 'Suspenso por processo administrativo',
        'OutsideCityFree' => 'Fora do município / Isento',
        'OutsideCityImmune' => 'Fora do município / Imune',
        'OutsideCitySuspended' => 'Fora do município / Suspenso',
        'OutsideCitySuspendedAdministrativeProcedure' => 'Fora do município / Suspenso (proc. administrativo)',
        'ObjectiveImune' => 'Imune objetivo',
    ];

    /**
     * Retorna todos os valores válidos do enum.
     *
     * @return string[]
     */
    public static function all()
    {
        return self::$values;
    }

    /**
     * Verifica se um valor é um `taxationType` válido.
     *
     * @param  mixed $value
     * @return bool
     */
    public static function isValid($value)
    {
        return in_array($value, self::$values, true);
    }

    /**
     * Retorna as opções no formato consumido pelos templates de dropdown.
     *
     * @return array[] lista de ['value' => string, 'label' => string]
     */
    public static function options()
    {
        $options = [];
        foreach (self::$values as $value) {
            $options[] = [
                'value' => $value,
                'label' => isset(self::$labels[$value]) ? self::$labels[$value] : $value,
            ];
        }

        return $options;
    }
}
