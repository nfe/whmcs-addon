<!-- Delete Company Modal -->
<div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-labelledby="deleteCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="deleteCompanyForm" method="post" action="{$modulelink}&action=companyDelete">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCompanyModalLabel">Confirmar Exclusao</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir <strong id="deleteCompanyName"></strong>? </p>
                    <input type="hidden" name="company_id" id="deleteCompanyId">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Sim, Exclua</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>