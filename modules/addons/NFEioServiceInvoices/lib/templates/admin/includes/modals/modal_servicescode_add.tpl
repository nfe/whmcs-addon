<!-- Add Product Code Modal -->
<div class="modal fade" id="addProductCodeModal" tabindex="-1" role="dialog" aria-labelledby="addProductCodeModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addProductCodeModalLabel">Adicionar Código de Serviço</h4>
            </div>
            <form id="addProductCodeForm" action="{$modulelink}&action={$formAction}" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="product_search">Buscar Produto</label>
                        <input type="text" class="form-control" id="product_search"
                               placeholder="Digite para buscar produtos...">
                        <div id="product_results" class="list-group mt-2"
                             style="max-height: 200px; overflow-y: auto; display: none;"></div>
                    </div>

                    <div id="selected_product_info" style="display: none;">
                        <div class="alert alert-info">
                            <p><strong>Produto selecionado:</strong> <span id="selected_product_name"></span></p>
                            <p><strong>ID:</strong> <span id="selected_product_id"></span></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="companyDropdown">Selecione uma Empresa</label>
                        <select class="form-control" id="companyDropdown" name="company" required>
                            <option value="">Selecione uma empresa</option>
                            {foreach from=$availableCompanies item=company}
                                <option value="{$company->company_id}"
                                        data-name="{$company->company_name}"
                                        data-default="{$company->default}">
                                    {$company->company_name} {if $company->default} (padrão){/if}
                                </option>
                            {/foreach}
                        </select>
                        <!-- campos para os dados da empresa selecionada -->
                        <input type="hidden" id="company_name" name="company_name">
                        <input type="hidden" id="company_default" name="company_default">
                    </div>

                    <div class="form-group">
                        <label for="service_code">Código do Serviço</label>
                        <input type="text" class="form-control" id="service_code" name="service_code" required>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <strong>Atenção:</strong> Apenas empresas do <strong>Lucro Real e Presumido</strong> são obrigadas a preencher os campos de NBS, Código de Operação e Código de Classificação Tributária.
                    </div>
                    {*Codigo NBS - RTC*}
                    <label for="editCompanyCompanyNbsCode">Nomenclatura Brasileira de Serviços (NBS)</label>
                    <input type="text" class="form-control" name="nbs_code" id="editCompanyCompanyNbsCode"
                           aria-describedby="nbs_codeHelpBlock"

                    >
                    <span class="help-block"
                          id="nbs_codeHelpBlock">
                            {$moduleFields.nbs_code.description}
                        </span>
                    {*Codigo NBS - RTC*}
                    {* Codigo de Operacao - RTC*}
                    <label for="editCompanyOperationCode">Indicador da Operação</label>
                    <input type="text" class="form-control" name="operation_indicator" id="editCompanyOperationCode"
                           aria-describedby="operation_indicatorHelpBlock"

                    >
                    <span class="help-block"
                          id="operation_indicatorHelpBlock">
                            {$moduleFields.operation_indicator.description}
                        </span>
                    {* Codigo de Operacao - RTC*}
                    {*Código de Classificação Tributária para o IBS/CBS*}
                    <label for="editCompanyClassCode">Código de Classificação Tributária</label>
                    <input type="text" class="form-control" name="class_code" id="editCompanyClassCode"
                           aria-describedby="class_codeHelpBlock" >
                    <span class="help-block"
                          id="class_codeHelpBlock">
                            {$moduleFields.class_code.description}
                        </span>
                    {*Código de Classificação Tributária para o IBS/CBS*}

                    <input type="hidden" id="product_id" name="product_id">
                    <input type="hidden" id="product_name" name="product_name">
                    <input type="hidden" name="btnSave" value="true">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="saveProductCode" disabled>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
{literal}
    <script>

    </script>
{/literal}