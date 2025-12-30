<div class="modal fade" id="companyConfigModal" tabindex="-1"
     aria-labelledby="companyConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="companyConfigForm" action="{$modulelink}&action=AssociateCompany" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="companyConfigModalLabel">Definicoes da Empresa</h5>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label"
                               for="{$moduleFields.company_id.id}">{$moduleFields.company_id.label}
                            :</label>

                        <select class="form-control" name="{$moduleFields.company_id.name}"
                                id="{$moduleFields.company_id.id}"
                                aria-describedby="{$moduleFields.company_id.id}HelpBlock"
                                {if $moduleFields.company_id.required}required{/if} {if $moduleFields.company_id.disabled}disabled{/if} >
                            {foreach from=$companiesDropDown item=resultado key=id}
                                <option value="{$id}"
                                        {if $id == $company_id}selected{/if}>{$resultado}</option>
                            {/foreach}
                        </select>
                        <span class="help-block"
                              id="{$moduleFields.company_id.id}HelpBlock">{$moduleFields.company_id.description}</span>

                    </div>
                    {* /company_id *}
                    {* service_code *}
                    <div class="mb-3">
                        <label class="form-label"
                               for="service_code">{$moduleFields.service_code.label}
                            :</label>

                        <input
                                class="form-control"
                                type="text"
                                name="service_code"
                                id="service_code"
                                aria-describedby="service_codeHelpBlock"
                                required
                        >

                        <span class="help-block"
                              id="service_codeHelpBlock">
                            {$moduleFields.service_code.description}
                        </span>

                    </div>
                    {* /service_code *}

                    {* iss_held *}
                    <div class="mb-3">
                        <label class="form-label"
                               for="iss_held">{$moduleFields.iss_held.label}
                            :</label>

                        <div class="input-group">
                            <input
                                    class="form-control"
                                    type="text"
                                    name="iss_held"
                                    id="iss_held"
                                    size="3"
                                    aria-describedby="iss_heldHelpBlock"
                            >
                            <div class="input-group-addon">%</div>
                        </div>
                        <span class="help-block"
                              id="iss_heldHelpBlock">
                            {$moduleFields.iss_held.description}
                        </span>

                    </div>
                    {* /iss_held *}

                    <div class="alert alert-warning" role="alert">
                        <strong>Atenção:</strong> Apenas empresas do <strong>Lucro Real e Presumido</strong> são obrigadas a preencher os campos de NBS, Código de Operação e Código de Classificação Tributária.
                    </div>
                    {*Codigo NBS - RTC*}
                    <label for="nbsCode">Nomenclatura Brasileira de Serviços (NBS)</label>
                    <input type="text" class="form-control" name="nbs_code" id="nbsCode"
                           aria-describedby="nbs_codeHelpBlock"

                    >
                    <span class="help-block"
                          id="nbs_codeHelpBlock">
                            {$moduleFields.nbs_code.description}
                        </span>
                    {*Codigo NBS - RTC*}
                    {* Codigo de Operacao - RTC*}
                    <label for="operationCode">Indicador da Operação</label>
                    <input type="text" class="form-control" name="operation_indicator" id="operationCode"
                           aria-describedby="operation_indicatorHelpBlock"

                    >
                    <span class="help-block"
                          id="operation_indicatorHelpBlock">
                            {$moduleFields.operation_indicator.description}
                        </span>
                    {* Codigo de Operacao - RTC*}
                    {*Código de Classificação Tributária para o IBS/CBS*}
                    <label for="classCode">Código de Classificação Tributária</label>
                    <input type="text" class="form-control" name="class_code" id="classCode"
                           aria-describedby="class_codeHelpBlock" >
                    <span class="help-block"
                          id="class_codeHelpBlock">
                            {$moduleFields.class_code.description}
                        </span>
                    {*Código de Classificação Tributária para o IBS/CBS*}


                    {* default company *}
                    <div class="mb-3">
                        <label for="defaultCompany">Empresa Padrão:</label>
                        <input type="checkbox" class="form-check-input" id="company_default"
                               name="company_default"/>

                        <span class="help-block"
                              id="defaultCompanyHelpBlock">Definir empresa como padrão para emissões.</span>
                    </div>
                    {* default company *}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveCompanyConfig">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
{literal}
    <script>
        $(document).ready(function () {

            // Form validation
            function validateForm() {
                const serviceCode = $('#service_code').val();

                if (serviceCode) {
                    $('#saveCompanyConfig').prop('disabled', false);
                } else {
                    $('#saveCompanyConfig').prop('disabled', true);
                }
            }

            $('#service_code').on('change keyup', validateForm);
            // Handle form submission
            $('#companyConfigForm').on('submit', function() {
                $('#companyConfigModal').modal('hide');
                return true;
            });
        });
    </script>
{/literal}