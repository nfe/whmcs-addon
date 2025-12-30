<!-- Edit Company Modal -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editCompanyForm" method="post" action="{$modulelink}&action=companyEdit">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCompanyModalLabel">Editar Empresa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Hidden field for internal id -->
                    <input type="hidden" name="id" id="editCompanyId">
                    <div class="form-group">
                        <label for="editCompanyCompanyId">Empresa ID</label>
                        <input type="text" class="form-control" name="company_id" id="editCompanyCompanyId" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editCompanyCompanyName">Nome</label>
                        <input type="text" class="form-control" name="company_name" id="editCompanyCompanyName"
                               aria-describedby="editCompanyCompanyNameHelpBlock" required
                        >
                        <span class="help-block" id="editCompanyCompanyNameHelpBlock">O nome da empresa é usado apenas para referência interna.</span>
                    </div>
                    <div class="form-group">
                        <label for="editCompanyIssHeld">Retencao ISS (%)</label>
                        <input type="number" class="form-control" name="iss_held"
                               id="editCompanyIssHeld"
                               aria-describedby="iss_heldHelpBlock"
                        >
                        <span class="help-block"
                              id="iss_heldHelpBlock">
                            {$moduleFields.iss_held.description}
                        </span>
                    </div>
                    <div class="form-group">
                        <label for="editCompanyDefault">Empresa Padrão</label>
                        <select class="form-control" name="default" id="editCompanyDefault">
                            <option value="0">Não</option>
                            <option value="1">Sim</option>
                        </select>
                        <span class="help-block"
                              id="defaultHelpBlock">
                            Definir empresa como padrão para emissão de NFe.

                        </span>
                    </div>
                    <div class="form-group">
                        <label for="editCompanyServiceCode">Código de Servico Principal</label>
                        <input type="text" class="form-control" name="service_code" id="editCompanyServiceCode"
                               aria-describedby="service_codeHelpBlock"
                               required
                        >
                        <span class="help-block"
                              id="service_codeHelpBlock">
                            {$moduleFields.service_code.description}
                        </span>
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
                        <label for="editCompanyOperationIndicator">Indicador da Operação</label>
                        <input type="text" class="form-control" name="operation_indicator" id="editCompanyOperationIndicator"
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

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</div>