{include file="includes/menu.tpl"}
<!-- Modal adicao empresa -->
{include file="includes/modals/modal_configuration_addcompany.tpl"}
<!-- Modal adicao empresa-->

<!-- Modal editar empresa-->
{include file="includes/modals/modal_configuration_editcompany.tpl"}
<!-- Modal editar empresa-->

<!-- Modal excluir empresa -->
{include file="includes/modals/modal_configuration_removecompany.tpl"}
<!-- Modal excluir empresa -->
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title text-left">Definicoes de Emissão</h3>
            </div>
            <div class="panel-body">

                {*informacoes cadastro emissor*}
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert alert-info">
                            <strong>Informações:</strong>
                            <ul>
                                <li>O nome da empresa é para identificação interna.</li>
                                <li>O código de serviço padrão é utilizado para emissão de NFe.</li>
                                <li>O campo "ISS Retido (%)" deve ser preenchido com o valor percentual.</li>
                                <li>O campo "Empresa Padrão" define a empresa padrão para emissão de NFe.</li>
                            </ul>
                        </div>

                    </div>
                </div>
                {*informacoes cadastro emissor*}

                <div class="row p-3">
                    <div class="col-sm-12">
                        {* botao que abrira modal para definicao do ID da empresa junto com codigo de servico principal e retencao de iss*}
                        {if $companiesDropDown|@count > 0}
                            <button type="button" class="btn btn-primary text-center" data-toggle="modal"
                                    data-target="#companyConfigModal">
                                Cadastrar Emissor
                            </button>
                        {/if}
                        {* /botao que abrira modal para definicao do ID da empresa junto com codigo de servico principal e retencao de iss*}
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-sm-12">
                        {if $companies|@count == 0}
                            <div class="alert alert-warning">
                                Nenhuma empresa está definida para emissão de NFe.
                            </div>
                        {else}
                            <!-- Table to display companies -->
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>CNPJ</th>
                                    <th>Nome</th>
                                    <th>Cód. Serv. Principal</th>
                                    <th>ISS Ret. (%)</th>
                                    <th>Padrão</th>
                                    <th>Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach from=$companies item=company}
                                    <tr>
                                        {*                                                <td>{$company->id}</td>*}
                                        <td>{$company->tax_number}</td>
                                        <td>{$company->company_name}</td>
                                        <td>{$company->service_code}</td>
                                        <td>{$company->iss_held}</td>
                                        <td>{if $company->default == 1}Sim{else}Não{/if}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary"
                                                    data-toggle="modal"
                                                    data-target="#editCompanyModal"
                                                    onclick='populateEditModal({$company|@json_encode})'>
                                                editar
                                            </button>
                                            {if $company->default == 0}
                                                <button type="button" class="btn btn-xs btn-danger"
                                                        data-toggle="modal"
                                                        data-target="#deleteCompanyModal"
                                                        onclick='populateDeleteModal({$company|@json_encode})'>
                                                    excluir
                                                </button>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        {/if}

                    </div>
                </div>
            </div>
        </div>
        {*painel definicoes globais*}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title text-left">Configurações Globais</h3>
            </div>
            <div class="panel-body">
                <form action="{$modulelink}&action={$formAction}" method="post" class="form-horizontal">

                    {* discount_items *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.discount_items.id}">{$moduleFields.discount_items.label}:</label>
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
                            <span class="help-block"
                                  id="{$moduleFields.discount_items.id}HelpBlock">{$moduleFields.discount_items.description}</span>
                        </div>
                    </div>
                    {* /discount_items *}
                    {* issue_note_default_cond *}
                    <div class="form-group">
                        <label class="control-label col-sm-4" for="{$moduleFields.issue_note_default_cond.id}"
                               aria-describedby="{$moduleFields.issue_note_default_cond.id}HelpBlock">{$moduleFields.issue_note_default_cond.label}
                            :</label>
                        <div class="col-sm-8">
                            <span class="help-block"
                                  id="{$moduleFields.issue_note_default_cond.id}HelpBlock">{$moduleFields.issue_note_default_cond.description}</span>
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
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.issue_note_after.id}">{$moduleFields.issue_note_after.label}:</label>
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
                            <span class="help-block"
                                  id="{$moduleFields.issue_note_after.id}HelpBlock">{$moduleFields.issue_note_after.description}</span>
                        </div>
                    </div>
                    {* /issue_note_after *}
                    {* cancel_invoice_cancel_nfe *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.cancel_invoice_cancel_nfe.id}">{$moduleFields.cancel_invoice_cancel_nfe.label}
                            :</label>
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
                                <span class="help-block"
                                      id="{$moduleFields.cancel_invoice_cancel_nfe.id}HelpBlock">{$moduleFields.cancel_invoice_cancel_nfe.description}</span>
                            </div>

                        </div>
                    </div>
                    {* cancel_invoice_cancel_nfe *}
                    {* insc_municipal *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.insc_municipal.id}">{$moduleFields.insc_municipal.label}:</label>

                        <div class="col-sm-8">
                            <select class="form-control" name="{$moduleFields.insc_municipal.name}"
                                    id="{$moduleFields.insc_municipal.id}">
                                <option value="">---</option>
                                {foreach from=$customFieldsClientsOptions item=resultado}
                                    <option value="{$resultado->id}"
                                            {if $resultado->id == $insc_municipal}selected{/if}>{$resultado->fieldname}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    {* /insc_municipal *}
                    {* cpf_camp *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.cpf_camp.id}">{$moduleFields.cpf_camp.label}:</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="{$moduleFields.cpf_camp.name}"
                                    id="{$moduleFields.cpf_camp.id}">
                                <option value="">---</option>
                                {foreach from=$customFieldsClientsOptions item=resultado}
                                    <option value="{$resultado->id}"
                                            {if $resultado->id == $cpf_camp}selected{/if}>{$resultado->fieldname}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    {* /cpf_camp *}
                    {* cnpj_camp *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.cnpj_camp.id}">{$moduleFields.cnpj_camp.label}:</label>
                        <div class="col-sm-8">
                            <select class="form-control" name="{$moduleFields.cnpj_camp.name}"
                                    id="{$moduleFields.cnpj_camp.id}">
                                <option value="">---</option>
                                {foreach from=$customFieldsClientsOptions item=resultado}
                                    <option value="{$resultado->id}"
                                            {if $resultado->id == $cnpj_camp}selected{/if}>{$resultado->fieldname}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    {* /cnpj_camp *}
                    {* InvoiceDetails *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.InvoiceDetails.id}">{$moduleFields.InvoiceDetails.label}:</label>
                        <div class="col-sm-8">
                            <span class="help-block"
                                  id="{$moduleFields.InvoiceDetails.id}HelpBlock">{$moduleFields.InvoiceDetails.description}</span>
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
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.send_invoice_url.id}">{$moduleFields.send_invoice_url.label}:</label>
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
                                <span class="help-block"
                                      id="{$moduleFields.send_invoice_url.id}HelpBlock">{$moduleFields.send_invoice_url.description}</span>

                            </div>

                        </div>
                    </div>
                    {* /send_invoice_url *}
                    {* gnfe_email_nfe_config *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.gnfe_email_nfe_config.id}">{$moduleFields.gnfe_email_nfe_config.label}
                            :</label>
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
                                <span class="help-block"
                                      id="{$moduleFields.gnfe_email_nfe_config.id}HelpBlock">{$moduleFields.gnfe_email_nfe_config.description}</span>
                            </div>
                        </div>
                    </div>
                    {* /gnfe_email_nfe_config *}
                    {* descCustom *}
                    <div class="form-group">
                        <label class="control-label col-sm-4"
                               for="{$moduleFields.descCustom.id}">{$moduleFields.descCustom.label}:</label>
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
                            <span class="help-block"
                                  id="{$moduleFields.descCustom.id}HelpBlock">{$moduleFields.descCustom.description}</span>
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
{literal}
    <script>
        // Populate edit modal with company data (company_id is readonly)
        function populateEditModal(company) {
            if (typeof company === 'string') {
                company = JSON.parse(company);
            }
            document.getElementById('editCompanyId').value = company.id;
            document.getElementById('editCompanyCompanyId').value = company.company_id;
            document.getElementById('editCompanyCompanyName').value = company.company_name;
            document.getElementById('editCompanyServiceCode').value = company.service_code;
            document.getElementById('editCompanyIssHeld').value = company.iss_held;
            document.getElementById('editCompanyDefault').value = company.default;
        }

        // Populate delete modal with the company id
        function populateDeleteModal(company) {
            if (typeof company === 'string') {
                company = JSON.parse(company);
            }
            document.getElementById('deleteCompanyId').value = company.company_id;
            document.getElementById('deleteCompanyName').innerText = company.company_name;
        }
    </script>
{/literal}