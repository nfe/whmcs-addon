<?php

use WHMCS\Database\Capsule;

if (gnfe_config('issue_note_default_cond') !== 'Manualmente') {
    gnfe_insert_issue_nfe_cond_in_database();

    if (Capsule::schema()->hasTable('mod_nfeio_custom_configs')) {
        return ['Emitir nota fiscal quando' => gnfe_show_issue_invoice_conds($vars['userid'])];
    } else {
        gnfe_verifyInstall();
        return [
            'Módulo NFE.io' => 'Atualize a página para exibir as informações.'
        ];
    }
}
