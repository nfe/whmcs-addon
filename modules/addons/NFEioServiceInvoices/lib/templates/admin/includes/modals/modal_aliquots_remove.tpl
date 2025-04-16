<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmRemoveModal" tabindex="-1" role="dialog" aria-labelledby="confirmRemoveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="removeAliquotsForm" action="{$modulelink}&action=aliquotsRemove" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmRemoveModalLabel">Confirmar Remoção</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza de que deseja remover a alíquota para:</p>
                    <ul>
                        <li>Código de Serviço: <strong id="modalCodeService"></strong></li>
                        <li>Emissor: <strong id="modalCompanyName"></strong></li>
                    </ul>
                    <input type="hidden" name="code_service" id="formCodeService">
                    <input type="hidden" name="id" id="formId">
                    <input type="hidden" name="company_id" id="formCompanyId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Remover</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Populate modal fields with data from the button
        $('.btn-remove').on('click', function () {
            const codeService = $(this).data('code-service');
            const companyName = $(this).data('company-name');
            const id = $(this).data('id');

            $('#modalCodeService').text(codeService);
            $('#modalCompanyName').text(companyName);
            $('#formCodeService').val(codeService);
            $('#formId').val(id);
        });
    });
</script>