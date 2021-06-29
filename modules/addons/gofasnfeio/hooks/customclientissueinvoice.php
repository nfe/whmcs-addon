<?php

use WHMCS\Database\Capsule;

if (gnfe_config('issue_note_default_cond') !== 'Manualmente' && Capsule::schema()->hasTable('mod_nfeio_custom_configs')){
    return ['Emitir nota fiscal quando' => gnfe_show_issue_invoice_conds($vars['userid'])];
}

return ['Módulo NFE.io' => 'Acesse a configuração do modulo para habilitar a emissão personalizada de nota fiscal.'];
