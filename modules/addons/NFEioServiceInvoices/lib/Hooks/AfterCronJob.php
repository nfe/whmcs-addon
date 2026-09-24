<?php

namespace NFEioServiceInvoices\Hooks;

use NFEioServiceInvoices\Helpers\Timestamp;
use WHMCS\Database\Capsule;

/**
 * Classe com execução das rotinas para o gatilho aftercronjob
 *
 * @see     https://developers.whmcs.com/hooks-reference/cron/#aftercronjob
 * @author  Andre Bellafronte
 * @version 2.1.0
 */
class AfterCronJob
{
    const STATUS_CHECK_MIN_AGE_MINUTES = 15;

    const STATUS_CHECK_DEFAULT_LIMIT = 50;

    /**
     * @var \NFEioServiceInvoices\Configuration
     */
    private $config;
    /**
     * @var \NFEioServiceInvoices\Models\ServiceInvoices\Repository
     */
    private $serviceInvoicesRepo;
    /**
     * @var \NFEioServiceInvoices\NFEio\Nfe
     */
    private $nf;

    public function __construct()
    {
        $this->config = new \NFEioServiceInvoices\Configuration();
        $this->serviceInvoicesRepo = new \NFEioServiceInvoices\Models\ServiceInvoices\Repository();
        $this->nf = new \NFEioServiceInvoices\NFEio\Nfe();
    }

    public function run()
    {
        $storageKey = $this->config->getStorageKey();
        $serviceInvoicesTable = $this->serviceInvoicesRepo->tableName();
        $storage = new \WHMCSExpert\Addon\Storage($storageKey);
        $dataAtual = Timestamp::currentTimestamp();
        // caso não exista valor para initial_date inicia define data que garanta a execução da rotina
        $initialDate = (!empty($storage->get('initial_date'))) ? $storage->get('initial_date') : '1970-01-01 00:00:00';

        // atualiza a data da ultima cron
        $storage->set('last_cron', $dataAtual);

        $hasNfWaiting = Capsule::table($serviceInvoicesTable)->whereBetween('created_at', [$initialDate, $dataAtual])->where('status', '=', 'Waiting')->count();
        logModuleCall(
            'nfeio_serviceinvoices',
            'hook_aftercronjob_1',
            "{$hasNfWaiting} notas a serem geradas",
            array(
                [
                    'total de notas' => $hasNfWaiting,
                    'data atual' => $dataAtual,
                    'data inicial' => $initialDate,
                ]
            )
        );

        if ($hasNfWaiting) {
            $queryNf = Capsule::table($serviceInvoicesTable)
                ->orderBy('id', 'desc')
                ->whereBetween('created_at', [$initialDate, $dataAtual])
                ->where('status', '=', 'Waiting')
                ->get();

            logModuleCall('nfeio_serviceinvoices', 'hook_aftercronjob_2', "{$hasNfWaiting} notas a serem geradas", $queryNf);


            foreach ($queryNf as $invoice) {

                $this->nf->emit($invoice);

            }

        }

        $this->checkPendingNfStatus($storage);
    }

    private function checkPendingNfStatus($storage)
    {
        if ($storage->get('check_nf_status') !== 'on') {
            return;
        }

        $limit = (int)$storage->get('check_nf_status_limit');

        if ($limit <= 0) {
            $limit = self::STATUS_CHECK_DEFAULT_LIMIT;
        }

        $result = $this->nf->syncPendingStatuses($limit, self::STATUS_CHECK_MIN_AGE_MINUTES);

        logModuleCall(
            'nfeio_serviceinvoices',
            'hook_aftercronjob_status_check',
            ['limite' => $limit, 'idade minima em minutos' => self::STATUS_CHECK_MIN_AGE_MINUTES],
            $result
        );
    }
}
