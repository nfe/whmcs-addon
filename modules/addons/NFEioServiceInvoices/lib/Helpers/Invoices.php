<?php

namespace NFEioServiceInvoices\Helpers;

use WHMCS\Database\Capsule;

class Invoices
{
    /**
     * Obtém o status de uma fatura pelo seu ID.
     *
     * @param int $id O ID da fatura.
     * @return string O status da fatura (ex.: 'Paid', 'Unpaid', 'Cancelled').
     */
    public static function getInvoiceStatus($id)
    {
        return Capsule::table('tblinvoices')->where('id', '=', $id)->value('status');
    }

    /**
     * Obtém a URL completa para visualização da fatura.
     *
     * @param int $id ID da fatura.
     * @return string URL completa da fatura.
     */
    public static function getInvoiceViewUrl($id)
    {
        $systemUrl = \WHMCS\Config\Setting::getValue('SystemURL');
        $invoiceUrl = \WHMCS\Billing\Invoice::find($id)->getViewInvoiceUrl();

        return $systemUrl . $invoiceUrl;
    }

    /**
     * Gera a descrição do serviço para a nota fiscal.
     *
     * @param int $invoiceId ID da fatura.
     * @param string $description Descrição inicial do serviço.
     * @return string Descrição completa do serviço para a nota fiscal.
     *
     * O método utiliza configurações armazenadas para construir a descrição
     * da nota fiscal, incluindo informações opcionais como o número da fatura,
     * nome dos serviços, URL da fatura e uma descrição adicional personalizada.
     */
    public static function generateNfServiceDescription($invoiceId, $description)
    {
        $config = new \NFEioServiceInvoices\Configuration();
        $storage = new \WHMCSExpert\Addon\Storage($config->getStorageKey());
        $invoiceDetails = $storage->get('InvoiceDetails');
        $additionalDescription = $storage->get('descCustom');
        $invoiceUrl = (bool) $storage->get('send_invoice_url');

        switch ($invoiceDetails) {
            case 'Número da fatura':
                $description = "Nota referente a fatura #{$invoiceId}";
                break;
            case 'Número da fatura + Nome dos serviços':
                $description = "Nota referente a fatura #{$invoiceId}" . "\n" . $description;
                break;
        }

        if ($invoiceUrl) {
            $description = $description . "\n" . self::getInvoiceViewUrl($invoiceId);
        }

        if ($additionalDescription) {
            $description = $description . "\n" . $additionalDescription;
        }

        return $description;
    }

    /**
     * Calcula o valor de um tributo a partir de uma alíquota percentual.
     *
     * @param  float $amount valor base (ex.: total dos serviços)
     * @param  float $rate   alíquota em percentual (ex.: 0.65, 3.0)
     * @return float valor calculado, arredondado a 2 casas
     */
    public static function getTaxAmountByRate($amount, $rate)
    {
        return round(($amount * $rate) / 100, 2);
    }

    /**
     * Calcula o valor de retenção para o ISS
     *
     * @param  $amount
     * @param  $issHeld
     * @return float
     */
    public static function getIssHeldAmount($amount, $issHeld)
    {
        return self::getTaxAmountByRate($amount, $issHeld);
    }

    /**
     * Verifica se uma determinada fatura existe no banco de dados com base no seu ID.
     *
     * @param   $invoiceId integer ID da fatura
     * @return  bool true se tiver false se não existir
     * @version 2.1
     */
    public static function hasInvoice($invoiceId)
    {
        $result = Capsule::table('tblinvoices')->where('id', '=', $invoiceId)->exists();

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Marcadores do bloco de links NFS-e gravado em tblinvoices.notes.
     * Permite substituir só o trecho gerado pelo módulo sem apagar notas manuais.
     */
    const NF_NOTES_BEGIN = '=== NFS-e ===';
    const NF_NOTES_END = '=== Fim NFS-e ===';

    /**
     * Monta a URL do download de PDF/XML na área do cliente.
     *
     * @param string $action downloadNfPdf|downloadNfXml
     * @param string $nfeId
     * @param string $companyId
     * @return string
     */
    public static function getClientNfDownloadUrl($action, $nfeId, $companyId)
    {
        $systemUrl = rtrim((string) \WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $query = http_build_query([
            'm' => 'NFEioServiceInvoices',
            'action' => $action,
            'nfid' => $nfeId,
            'c' => $companyId,
        ]);

        return $systemUrl . '/index.php?' . $query;
    }

    /**
     * Monta o bloco HTML/texto com links PDF (e XML) das NFS-e emitidas da fatura.
     *
     * @param int $invoiceId
     * @return string bloco completo com marcadores, ou string vazia se não houver NF emitida
     */
    public static function buildNfNotesBlock($invoiceId)
    {
        $tableName = (new \NFEioServiceInvoices\Models\ServiceInvoices\Repository())->tableName();
        $issued = Capsule::table($tableName)
            ->where('invoice_id', '=', $invoiceId)
            ->where('status', '=', 'Issued')
            ->where('nfe_id', '!=', 'waiting')
            ->where('nfe_id', '!=', '')
            ->orderBy('id', 'asc')
            ->get();

        if (is_object($issued) && method_exists($issued, 'all')) {
            $issued = $issued->all();
        } elseif ($issued instanceof \Traversable) {
            $issued = iterator_to_array($issued);
        } elseif (!is_array($issued)) {
            $issued = $issued ? array($issued) : array();
        }

        if (count($issued) === 0) {
            return '';
        }

        $lines = array(self::NF_NOTES_BEGIN);
        $lines[] = 'Nota Fiscal de Serviços (NFS-e) disponível para download:';

        $index = 1;
        $total = count($issued);
        foreach ($issued as $nf) {
            $nfeId = is_array($nf) ? $nf['nfe_id'] : $nf->nfe_id;
            $companyId = is_array($nf) ? $nf['company_id'] : $nf->company_id;
            $pdfUrl = self::getClientNfDownloadUrl('downloadNfPdf', $nfeId, $companyId);
            $xmlUrl = self::getClientNfDownloadUrl('downloadNfXml', $nfeId, $companyId);
            $prefix = $total > 1 ? ($index . ') ') : '';
            $lines[] = $prefix
                . '<a href="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">Baixar PDF</a>'
                . ' | '
                . '<a href="' . htmlspecialchars($xmlUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">Baixar XML</a>';
            $index++;
        }

        $lines[] = self::NF_NOTES_END;

        return implode("\n", $lines);
    }

    /**
     * Insere/atualiza/remove o bloco NFS-e em tblinvoices.notes sem apagar notas manuais.
     *
     * @param int $invoiceId
     * @return bool true se o campo notes foi alterado
     */
    public static function syncNfNotesToInvoice($invoiceId)
    {
        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0 || !self::hasInvoice($invoiceId)) {
            return false;
        }

        $currentNotes = (string) Capsule::table('tblinvoices')->where('id', '=', $invoiceId)->value('notes');
        $block = self::buildNfNotesBlock($invoiceId);
        $newNotes = self::replaceNfNotesBlock($currentNotes, $block);

        if ($newNotes === $currentNotes) {
            return false;
        }

        Capsule::table('tblinvoices')->where('id', '=', $invoiceId)->update(array('notes' => $newNotes));

        logModuleCall(
            'nfeio_serviceinvoices',
            'sync_nf_notes_to_invoice',
            array('invoice_id' => $invoiceId),
            array('notes' => $newNotes)
        );

        return true;
    }

    /**
     * Substitui (ou remove) o bloco marcado do módulo dentro do texto de notas.
     *
     * @param string $currentNotes
     * @param string $block bloco novo completo, ou vazio para remover
     * @return string
     */
    public static function replaceNfNotesBlock($currentNotes, $block)
    {
        $currentNotes = (string) $currentNotes;
        $begin = preg_quote(self::NF_NOTES_BEGIN, '/');
        $end = preg_quote(self::NF_NOTES_END, '/');
        $pattern = '/(?:\r\n|\n|\r)*' . $begin . '.*?' . $end . '(?:\r\n|\n|\r)*/s';

        $withoutBlock = trim(preg_replace($pattern, '', $currentNotes));

        if ($block === '') {
            return $withoutBlock;
        }

        if ($withoutBlock === '') {
            return $block;
        }

        return rtrim($withoutBlock) . "\n\n" . $block;
    }

    /**
     * Garante o bloco NFS-e nas notas exibidas na área do cliente (sem depender só do DB).
     *
     * @param string $currentNotes
     * @param int $invoiceId
     * @return string
     */
    public static function mergeNfNotesForDisplay($currentNotes, $invoiceId)
    {
        $block = self::buildNfNotesBlock($invoiceId);
        return self::replaceNfNotesBlock((string) $currentNotes, $block);
    }
}
