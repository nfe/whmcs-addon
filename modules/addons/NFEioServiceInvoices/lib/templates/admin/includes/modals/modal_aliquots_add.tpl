<!-- Modal Structure -->
<div class="modal fade" id="addAliquotModal" tabindex="-1" role="dialog" aria-labelledby="addAliquotModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addAliquotForm" action="{$modulelink}&action=aliquotsSave" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAliquotModalLabel">Adicionar Nova Alíquota</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Dropdown for Service Codes -->
                    <div class="form-group">
                        <label for="serviceCodeSelect">Selecione o Código de Serviço</label>
                        <select class="form-control" id="serviceCodeSelect" name="service_code" required>
                            <option value="" disabled selected>Selecione...</option>
                            {foreach from=$dropdownServiceCodesAliquots item=service}
                                <option value="{$service->service_code}" data-company-id="{$service->company_id}">
                                    {$service->service_code} - {$service->company_name} ({$service->company_tax_number})
                                </option>
                            {/foreach}
                        </select>
                    </div>
                    <!-- Hidden Field for Company ID -->
                    <input type="hidden" name="company_id" id="selectedCompanyId">
                    <!-- ISS Retention Field -->
                    <div class="form-group">
                        <label for="issHeld">Retenção de ISS (%)</label>
                        <input type="text" class="form-control" name="iss_held" id="issHeld" placeholder="Ex.: 3.5" required>
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
        // Update hidden company_id field when a service code is selected
        $('#serviceCodeSelect').on('change', function () {
            const selectedOption = $(this).find(':selected');
            const companyId = selectedOption.data('company-id');
            $('#selectedCompanyId').val(companyId);
        });

        // Validate ISS Retention Field
        $('#issHeld').on('input', function () {
            const value = $(this).val().replace(',', '.');
            $(this).val(value.replace(/[^0-9.]/g, ''));
        });
    });
</script>