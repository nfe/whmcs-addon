<div class="modal fade" id="modalInvoiceDetails" tabindex="-1" role="dialog" aria-labelledby="modalInvoiceDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalInvoiceDetailsLabel">Detalhes da Nota Fiscal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                    <tr>
                        <th>Fatura</th>
                        <td id="detailInvoiceId"></td>
                    </tr>
                    <tr>
                        <th>NFe.io ID</th>
                        <td id="detailNfeId"></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td id="detailStatus"></td>
                    </tr>
                    <tr>
                        <th>Status do Fluxo</th>
                        <td id="detailFlowStatus"></td>
                    </tr>
                    <tr>
                        <th>Condição</th>
                        <td id="detailConditions" class="bg-warning"></td>
                    </tr>
                    <tr>
                        <th>Data de Criação</th>
                        <td id="detailCreatedAt"></td>
                    </tr>
                    <tr>
                        <th>Cliente</th>
                        <td id="detailClient"></td>
                    </tr>
                    <tr>
                        <th>Valor</th>
                        <td id="detailAmount"></td>
                    </tr>
                    <tr>
                        <th>Emissor</th>
                        <td id="detailCompanyId"></td>
                    </tr>
                    <tr>
                        <th>Código de Serviço</th>
                        <td id="detailServiceCode"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#modalInvoiceDetails').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botão que acionou o modal
            var nfeId = button.data('nfeid');
            var invoiceId = button.data('invoiceid');
            var status = button.data('status');
            var flowStatus = button.data('flowstatus');
            var createdAt = button.data('createdat');
            var client = button.data('client');
            var amount = button.data('amount');
            var companyId = button.data('companyid');
            var serviceCode = button.data('servicecode');
            var condition = button.data('condition');

            // se condition nao possuir valor esconde a linha
            if (condition === '') {
                $('#detailConditions').closest('tr').hide();
            } else {
                $('#detailConditions').closest('tr').show();
            }

            // Preenche os campos do modal
            var modal = $(this);
            modal.find('#detailInvoiceId').text(invoiceId);
            modal.find('#detailNfeId').text(nfeId);
            modal.find('#detailStatus').html(status);
            modal.find('#detailFlowStatus').text(flowStatus);
            modal.find('#detailCreatedAt').text(createdAt);
            modal.find('#detailClient').text(client);
            modal.find('#detailAmount').text(amount);
            modal.find('#detailCompanyId').text(companyId);
            modal.find('#detailServiceCode').text(serviceCode);
            modal.find('#detailConditions').text(condition);
        });
    });
</script>