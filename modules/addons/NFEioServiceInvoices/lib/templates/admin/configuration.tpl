{include file="includes/menu.tpl"}
<div class="row">
<div class="col-md-8 col-md-offset-2">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title text-center">Configurações</h3>
    </div>
    <div class="panel-body">
      <form action="{$modulelink}&action={$formAction}" method="post" class="form-horizontal">
        {* api_key *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.api_key.id}">{$moduleFields.api_key.label}:</label>
          <div class="col-sm-8">
            <input
                    class="form-control"
                    type="{$moduleFields.api_key.type}"
                    name="{$moduleFields.api_key.name}"
                    id="{$moduleFields.api_key.id}"
                    aria-describedby="{$moduleFields.api_key.id}HelpBlock"
                    {if $moduleFields.api_key.required}required{/if}
                    {if $moduleFields.api_key.disabled}disabled{/if}
                    {if $api_key}value="{$api_key}"{/if}
            >
            <span class="help-block" id="{$moduleFields.api_key.id}HelpBlock">{$moduleFields.api_key.description}</span>
          </div>
        </div>
        {* /api_key *}
        {* company_id *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.company_id.id}">{$moduleFields.company_id.label}:</label>
          <div class="col-sm-8">
            <select class="form-control" name="{$moduleFields.company_id.name}" id="{$moduleFields.company_id.id}" aria-describedby="{$moduleFields.company_id.id}HelpBlock" {if $moduleFields.company_id.required}required{/if} {if $moduleFields.company_id.disabled}disabled{/if} >
              {foreach from=$companiesDropDown item=resultado key=id}
                <option value="{$id}" {if $id == $company_id}selected{/if}>{$resultado}</option>
              {/foreach}
            </select>
            <span class="help-block" id="{$moduleFields.company_id.id}HelpBlock">{$moduleFields.company_id.description}</span>
          </div>
        </div>
        {* /company_id *}
        {* service_code *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.service_code.id}">{$moduleFields.service_code.label}:</label>
          <div class="col-sm-3">
            <input
                    class="form-control"
                    type="{$moduleFields.service_code.type}"
                    name="{$moduleFields.service_code.name}"
                    id="{$moduleFields.service_code.id}"
                    aria-describedby="{$moduleFields.service_code.id}HelpBlock"
                    {if $moduleFields.service_code.required}required{/if}
                    {if $moduleFields.service_code.disabled}disabled{/if}
                    {if $service_code}value="{$service_code}"{/if}
            >
          </div>
          <div class="row">
            <div class="col-sm-8 col-sm-offset-4">
              <span class="help-block" id="{$moduleFields.service_code.id}HelpBlock">{$moduleFields.service_code.description}</span>

            </div>
          </div>
        </div>
        {* /service_code *}

        {* iss_held *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.iss_held.id}">{$moduleFields.iss_held.label}:</label>
          <div class="col-sm-2">
            <div class="input-group">
              <input
                      class="form-control"
                      type="{$moduleFields.iss_held.type}"
                      name="{$moduleFields.iss_held.name}"
                      id="{$moduleFields.iss_held.id}"
                      size="3"
                      aria-describedby="{$moduleFields.iss_held.id}HelpBlock"
                      {if $moduleFields.iss_held.required}required{/if}
                      {if $moduleFields.iss_held.disabled}disabled{/if}
                      {if $iss_held}value="{$iss_held}"{/if}
              >
              <div class="input-group-addon">%</div>
            </div>

          </div>
          <div class="row">
            <div class="col-sm-8 col-sm-offset-4">
              <span class="help-block" id="{$moduleFields.iss_held.id}HelpBlock">{$moduleFields.iss_held.description}</span>

            </div>
          </div>
        </div>
        {* /iss_held *}
        {* discount_items *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.discount_items.id}">{$moduleFields.discount_items.label}:</label>
          <div class="col-sm-8">
            <div class="checkbox">
              <label>
                <input
                        type="{$moduleFields.discount_items.type}"
                        name="{$moduleFields.discount_items.name}"
                        id="{$moduleFields.discount_items.id}"
                        aria-describedby="{$moduleFields.discount_items.id}HelpBlock"
                        {if $moduleFields.discount_items.required}required{/if}
                        {if $moduleFields.discount_items.disabled}disabled{/if}
                        {if $discount_items == 'on'}checked{/if}
                        {if $discount_items}value="{$discount_items}"{/if}
                >
                {$moduleFields.discount_items.label}
              </label>
            </div>
            <span class="help-block" id="{$moduleFields.discount_items.id}HelpBlock">{$moduleFields.discount_items.description}</span>
          </div>
        </div>
        {* /discount_items *}
        {* issue_note_default_cond *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.issue_note_default_cond.id}" aria-describedby="{$moduleFields.issue_note_default_cond.id}HelpBlock">{$moduleFields.issue_note_default_cond.label}:</label>
          <div class="col-sm-8">
            <span class="help-block" id="{$moduleFields.issue_note_default_cond.id}HelpBlock">{$moduleFields.issue_note_default_cond.description}</span>
            {if $moduleFields.issue_note_default_cond.type == 'radio' && isset($moduleFields.issue_note_default_cond.options)}
              {foreach from=$moduleFields.issue_note_default_cond.options item=option name=option}
                <div class="radio">
                  <label>
                    <input
                            type="radio"
                            name="{$moduleFields.issue_note_default_cond.name}"
                            id="{$moduleFields.issue_note_default_cond.name}_{$smarty.foreach.option.iteration}"
                            aria-describedby="{$moduleFields.issue_note_default_cond.id}HelpBlock"
                            value="{$option.value}"
                            {if $issue_note_default_cond == $option.value}checked{/if}
                    >
                    {$option.label}
                  </label>
                </div>
              {/foreach}
            {/if}
          </div>
        </div>
        {* issue_note_default_cond *}
        {* issue_note_after *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.issue_note_after.id}">{$moduleFields.issue_note_after.label}:</label>
          <div class="col-sm-8">
            <input
                    class="form-control"
                    type="{$moduleFields.issue_note_after.type}"
                    name="{$moduleFields.issue_note_after.name}"
                    id="{$moduleFields.issue_note_after.id}"
                    aria-describedby="{$moduleFields.issue_note_after.id}HelpBlock"
                    {if $moduleFields.issue_note_after.required}required{/if}
                    {if $moduleFields.issue_note_after.disabled}disabled{/if}
                    value="{$issue_note_after}"
            >
            <span class="help-block" id="{$moduleFields.issue_note_after.id}HelpBlock">{$moduleFields.issue_note_after.description}</span>
          </div>
        </div>
        {* /issue_note_after *}
        {* cancel_invoice_cancel_nfe *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.cancel_invoice_cancel_nfe.id}">{$moduleFields.cancel_invoice_cancel_nfe.label}:</label>
          <div class="col-sm-8">
            <div class="checkbox">
              <label>
                <input
                        type="{$moduleFields.cancel_invoice_cancel_nfe.type}"
                        name="{$moduleFields.cancel_invoice_cancel_nfe.name}"
                        id="{$moduleFields.cancel_invoice_cancel_nfe.id}"
                        aria-describedby="{$moduleFields.cancel_invoice_cancel_nfe.id}HelpBlock"
                        {if $moduleFields.cancel_invoice_cancel_nfe.required}required{/if}
                        {if $moduleFields.cancel_invoice_cancel_nfe.disabled}disabled{/if}
                        {if $cancel_invoice_cancel_nfe == 'on'}checked{/if}
                >
                {$moduleFields.cancel_invoice_cancel_nfe.label}
              </label>
              <span class="help-block" id="{$moduleFields.cancel_invoice_cancel_nfe.id}HelpBlock">{$moduleFields.cancel_invoice_cancel_nfe.description}</span>
            </div>

          </div>
        </div>
        {* cancel_invoice_cancel_nfe *}
        {* insc_municipal *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.insc_municipal.id}">{$moduleFields.insc_municipal.label}:</label>

          <div class="col-sm-8">
            <select class="form-control" name="{$moduleFields.insc_municipal.name}" id="{$moduleFields.insc_municipal.id}">
              <option value="">---</option>
              {foreach from=$customFieldsClientsOptions item=resultado}
                <option value="{$resultado->id}" {if $resultado->id == $insc_municipal}selected{/if}>{$resultado->fieldname}</option>
              {/foreach}
            </select>
          </div>
        </div>
        {* /insc_municipal *}
        {* cpf_camp *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.cpf_camp.id}">{$moduleFields.cpf_camp.label}:</label>
          <div class="col-sm-8">
            <select class="form-control" name="{$moduleFields.cpf_camp.name}" id="{$moduleFields.cpf_camp.id}">
              <option value="">---</option>
              {foreach from=$customFieldsClientsOptions item=resultado}
                <option value="{$resultado->id}" {if $resultado->id == $cpf_camp}selected{/if}>{$resultado->fieldname}</option>
              {/foreach}
            </select>
          </div>
        </div>
        {* /cpf_camp *}
        {* cnpj_camp *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.cnpj_camp.id}">{$moduleFields.cnpj_camp.label}:</label>
          <div class="col-sm-8">
            <select class="form-control" name="{$moduleFields.cnpj_camp.name}" id="{$moduleFields.cnpj_camp.id}">
              <option value="">---</option>
              {foreach from=$customFieldsClientsOptions item=resultado}
                <option value="{$resultado->id}" {if $resultado->id == $cnpj_camp}selected{/if}>{$resultado->fieldname}</option>
              {/foreach}
            </select>
          </div>
        </div>
        {* /cnpj_camp *}
        {* InvoiceDetails *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.InvoiceDetails.id}">{$moduleFields.InvoiceDetails.label}:</label>
          <div class="col-sm-8">
            <span class="help-block" id="{$moduleFields.InvoiceDetails.id}HelpBlock">{$moduleFields.InvoiceDetails.description}</span>
            {if $moduleFields.InvoiceDetails.type == 'radio' && isset($moduleFields.InvoiceDetails.options)}
              {foreach from=$moduleFields.InvoiceDetails.options item=option name=option}
                <div class="radio">
                  <label>
                    <input
                            type="radio"
                            name="{$moduleFields.InvoiceDetails.name}"
                            id="{$moduleFields.InvoiceDetails.name}_{$smarty.foreach.option.iteration}"
                            aria-describedby="{$moduleFields.InvoiceDetails.id}HelpBlock"
                            value="{$option.value}"
                            {if $InvoiceDetails == $option.value}checked{/if}
                    >
                    {$option.label}
                  </label>
                </div>
              {/foreach}
            {/if}
          </div>
        </div>
        {* /InvoiceDetails *}
        {* send_invoice_url *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.send_invoice_url.id}">{$moduleFields.send_invoice_url.label}:</label>
          <div class="col-sm-8">
            <div class="checkbox">
              <label>
                <input
                        type="{$moduleFields.send_invoice_url.type}"
                        name="{$moduleFields.send_invoice_url.name}"
                        id="{$moduleFields.send_invoice_url.id}"
                        aria-describedby="{$moduleFields.send_invoice_url.id}HelpBlock"
                        {if $moduleFields.send_invoice_url.required}required{/if}
                        {if $moduleFields.send_invoice_url.disabled}disabled{/if}
                        {if $send_invoice_url == 'on'}checked{/if}
                >
                {$moduleFields.send_invoice_url.label}
              </label>
              <span class="help-block" id="{$moduleFields.send_invoice_url.id}HelpBlock">{$moduleFields.send_invoice_url.description}</span>

            </div>

          </div>
        </div>
        {* /send_invoice_url *}
        {* gnfe_email_nfe_config *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.gnfe_email_nfe_config.id}">{$moduleFields.gnfe_email_nfe_config.label}:</label>
          <div class="col-sm-8">
            <div class="checkbox">
              <label>
                <input
                        type="{$moduleFields.gnfe_email_nfe_config.type}"
                        name="{$moduleFields.gnfe_email_nfe_config.name}"
                        id="{$moduleFields.gnfe_email_nfe_config.id}"
                        aria-describedby="{$moduleFields.gnfe_email_nfe_config.id}HelpBlock"
                        {if $moduleFields.gnfe_email_nfe_config.required}required{/if}
                        {if $moduleFields.gnfe_email_nfe_config.disabled}disabled{/if}
                        {if $gnfe_email_nfe_config == 'on'}checked{/if}
                >
                {$moduleFields.gnfe_email_nfe_config.label}
              </label>
              <span class="help-block" id="{$moduleFields.gnfe_email_nfe_config.id}HelpBlock">{$moduleFields.gnfe_email_nfe_config.description}</span>
            </div>
          </div>
        </div>
        {* /gnfe_email_nfe_config *}
        {* descCustom *}
        <div class="form-group">
          <label class="control-label col-sm-4" for="{$moduleFields.descCustom.id}">{$moduleFields.descCustom.label}:</label>
          <div class="col-sm-8">
            <input
                    class="form-control"
                    type="{$moduleFields.descCustom.type}"
                    name="{$moduleFields.descCustom.name}"
                    id="{$moduleFields.descCustom.id}"
                    aria-describedby="{$moduleFields.descCustom.id}HelpBlock"
                    {if $moduleFields.descCustom.required}required{/if}
                    {if $moduleFields.descCustom.disabled}disabled{/if}
                    value="{$descCustom}"
            >
            <span class="help-block" id="{$moduleFields.descCustom.id}HelpBlock">{$moduleFields.descCustom.description}</span>
          </div>
        </div>
        {* descCustom *}

        <div class="form-group">
          <div class="col-sm-offset-3 col-sm-6 text-center">
            <button class="btn btn-primary btn-lg btn-block" type="submit">Salvar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
</div>
