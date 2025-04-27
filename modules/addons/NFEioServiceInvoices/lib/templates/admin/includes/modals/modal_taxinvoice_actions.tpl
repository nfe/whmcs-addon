<div class="modal fade" id="{$id}" tabindex="-1" role="dialog"
     aria-labelledby="actionConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Ação:
                    <mark><span class="text-uppercase" id="actionConfirmationModalLabel"></span></mark>
                </h5>
            </div>
            <div class="modal-body">
                <p>
                    Tem certeza de que deseja
                    <mark><span class="text-uppercase" id="modalAction"></span></mark>
                    a NFS-e com o número <mark><span id="modalNfeId"></span> </mark>
                    para a fatura <mark><span id="modalInvoiceId"></span></mark>?
                </p>
                <p><strong>Nota: </strong><span class="text-info">Esta ação <span id="modalActionDesc"></span></span>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmar Ação</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const moduleLink = '{$modulelink}';
</script>

{literal}
    <script type="text/javascript">
        $(document).ready(function () {
            // funcao responsavel por acionar o modal para confirmacao de acoes
            $('#actionConfirmationModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget) // Botao que acionou o modal
                var action = button.data('action') // a acao a ser executada
                var nfeId = button.data('nfeid') // numero da nfe
                var companyId = button.data('companyid') // id da empresa
                var invoiceId = button.data('invoiceid') // id da fatura
                var actionName = button.data('actionname') // nome da acao
                var actionDesc = button.data('actiondesc') // descricao da acao

                // Atualiza o modal com as informacoes da acao
                var modal = $(this)
                modal.find('#modalNfeId').text(nfeId)
                modal.find('#modalInvoiceId').text(invoiceId)
                modal.find('#modalAction').text(actionName)
                modal.find('#modalActionDesc').text(actionDesc)
                modal.find('#actionConfirmationModalLabel').text(actionName)

                // Acao de confirmacao do modal
                $('#confirmAction').off('click').click(function () {
                    var link = moduleLink + '&action=' + action + '&invoice_id=' + invoiceId + '&company_id=' + companyId;
                    // se action for emailNf, substitui invoiceId por nfeId
                    if (action === 'emailNf' || action === 'updateNfStatus') {
                        link = moduleLink + '&action=' + action + '&nfe_id=' + nfeId + '&company_id=' + companyId;
                    }
                    window.open(link, '_self');
                });
            });
        });
    </script>
{/literal}