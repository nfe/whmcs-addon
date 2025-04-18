<!-- Associar cliente Modal -->
<div class="modal fade" id="addProductCodeModal" tabindex="-1" role="dialog" aria-labelledby="addProductCodeModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addProductCodeModalLabel">Associar cliente a emissor</h4>
            </div>
            <form id="addProductCodeForm" action="{$modulelink}&action={$formAction}" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="client_search">Buscar Cliente</label>
                        <input type="text" class="form-control" id="client_search"
                               placeholder="Digite para buscar clientes...">
                        <span class="help-block">Digite nome, email ou empresa</span>
                        <div id="client_results" class="list-group mt-2"
                             style="max-height: 200px; overflow-y: auto; display: none;"></div>
                    </div>

                    <div id="selected_client_info" style="display: none;">
                        <div class="alert alert-info">
                            <p><strong>Cliente:</strong> <span id="selected_client_name"></span></p>
                            <p><strong>ID:</strong> <span id="selected_client_id"></span></p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="company">Empresa</label>
                        <select class="form-control" id="company" name="company">
                            <option value="">Selecione uma empresa</option>
                            {foreach from=$availableCompanies item=company}
                                <option value="{$company->company_id}"
                                        data-name="{$company->company_name}"
                                        data-default="{$company->default}">
                                    {$company->company_name} {if $company->default} (padrão){/if}
                                </option>
                            {/foreach}
                        </select>
                    </div>


                    <input type="hidden" id="client_id" name="client_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="saveProductCode" disabled>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>