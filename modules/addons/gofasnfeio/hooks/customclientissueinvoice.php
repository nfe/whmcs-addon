<?php

use WHMCS\Database\Capsule;
  
    if (Capsule::schema()->hasTable('mod_nfeio_custom_configs')) {
        return ['Emitir nota fiscal quando' => gnfe_show_issue_invoice_conds($vars['userid'])];
    } else {
        gnfe_verifyInstall();
        gnfe_insert_issue_nfe_cond_in_database();
        return [
            'Módulo NFE.io' => 'Atualize a página para exibir as informações.'
        ];
    }

