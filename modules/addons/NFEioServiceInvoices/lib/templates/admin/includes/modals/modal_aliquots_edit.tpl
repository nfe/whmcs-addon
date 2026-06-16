<!-- Modal Structure -->
<div class="modal fade" id="editAliquotModal" tabindex="-1" role="dialog" aria-labelledby="editAliquotModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editAliquotForm" action="{$modulelink}&action=aliquotsEdit" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAliquotModalLabel">Editar Alíquota</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Hidden Field for Record ID -->
                    <input type="hidden" name="record_id" id="editRecordId">
                    <div class="form-group">
                        <label for="editServiceCode">Emissor</label>
                        <input type="text" class="form-control" name="company_name" id="companyName" readonly>
                    </div>
                    <!-- Service Code Field -->
                    <div class="form-group">
                        <label for="editServiceCode">Código de Serviço</label>
                        <input type="text" class="form-control" name="service_code" id="editServiceCode" readonly>
                    </div>
                    <!-- ISS Retention Field -->
                    <div class="form-group">
                        <label for="editIssHeld">Retenção de ISS (%)</label>
                        <input type="text" class="form-control" name="iss_held" id="editIssHeld" placeholder="Ex.: 3.5" required>
                    </div>
                    <!-- PIS/COFINS e tipo de tributação - RTC (#203) -->
                    <div class="alert alert-info" role="alert">
                        <strong>Opcional:</strong> preencha apenas se precisar definir o cálculo de PIS/COFINS e o tipo de tributação (ISSQN) para este código de serviço.
                    </div>
                    <div class="form-group">
                        <label for="editPisRate">Alíquota de PIS (%)</label>
                        <input type="number" step="0.01" class="form-control" name="pis_rate" id="editPisRate" placeholder="Ex.: 0.65">
                    </div>
                    <div class="form-group">
                        <label for="editCofinsRate">Alíquota de COFINS (%)</label>
                        <input type="number" step="0.01" class="form-control" name="cofins_rate" id="editCofinsRate" placeholder="Ex.: 3.00">
                    </div>
                    <div class="form-group">
                        <label for="editTaxationType">Tipo de Tributação (ISSQN)</label>
                        <select class="form-control" name="taxation_type" id="editTaxationType">
                            <option value="">— Não informar (usa padrão da empresa) —</option>
                            {foreach from=$taxationTypes item=tt}
                                <option value="{$tt.value}">{$tt.label}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Open modal and populate fields with data
        $('.btn-edit').on('click', function () {
            const recordId = $(this).data('id');
            const serviceCode = $(this).data('code-service');
            const companyName = $(this).data('company-name');
            const companyTaxNumber = $(this).data('company-tax-number');
            const emitior = companyName + ' - ' + companyTaxNumber
            const issHeld = $(this).data('iss-held');
            const pisRate = $(this).data('pis-rate');
            const cofinsRate = $(this).data('cofins-rate');
            const taxationType = $(this).data('taxation-type');

            $('#editRecordId').val(recordId);
            $('#editServiceCode').val(serviceCode);
            $('#editIssHeld').val(issHeld);
            $('#editPisRate').val(pisRate);
            $('#editCofinsRate').val(cofinsRate);
            $('#editTaxationType').val(taxationType || '');
            $('#companyName').val(emitior);

            $('#editAliquotModal').modal('show');
        });

        // Validate ISS Retention Field
        $('#editIssHeld').on('input', function () {
            const value = $(this).val().replace(',', '.');
            $(this).val(value.replace(/[^0-9.]/g, ''));
        });
    });
</script>