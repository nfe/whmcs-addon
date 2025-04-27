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
                        <label for="editCompanyServiceCode">Código de Servico Principal</label>
                        <input type="text" class="form-control" name="service_code" id="editCompanyServiceCode"
                               aria-describedby="service_codeHelpBlock"
                               required
                        >
                        <span class="help-block"
                              id="service_codeHelpBlock">
                            {$moduleFields.service_code.description}
                        </span>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </div>
        </form>
    </div>
</div>