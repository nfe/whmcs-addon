{include file="includes/menu.tpl"}

{function name=statusLabel}
    {if $data == 'Waiting'}
        <span class="label label-warning">Aguardando</span>
    {elseif $data == 'Created'}
        <span class="label label-info">Criada</span>
    {elseif $data == 'Issued'}
        <span class="label label-success">Emitida</span>
    {elseif $data == 'Cancelled'}
        <span class="label label-danger">Cancelada</span>
    {elseif $data == 'Error'}
        <span class="label label-danger">Erro</span>
    {elseif $data == 'Error_cep'}
        <span class="label label-danger">CEP do cliente inválido</span>
    {elseif $data == 'None'}
        <span class="label label-primary">Não Disponível</span>
    {else}
        <span class="label label-danger">{$data}</span>
    {/if}
{/function}

{*https://nfe.io/docs/https/nfeio/docs/documentacao/nota-fiscal-servico-eletronica/duvidas/como-saber-se-sua-nota-fiscal-de-servico-foi-emitida-pela-api/*}
{function name=flowStatus}
    {if $data == 'Issued'}
        Nota emitida
    {elseif $data == 'Cancelled'}
        Nota cancelada
    {elseif $data == 'waiting'}
        Fila de emissão
    {elseif $data == 'WaitingCalculateTaxes'}
        Calculando impostos da nota
    {elseif $data == 'CancelFailed'}
        Nota não foi cancelada com sucesso
    {elseif $data == 'IssueFailed'}
        Erro ao emitir a nota
    {elseif $data == 'PullFromCityHall'}
        PullFromCityHall
    {elseif $data == 'WaitingDefineRpsNumber'}
        Definindo número de RPS da nota
    {elseif $data == 'WaitingSend'}
        Nota enviada para emissão na prefeitura e aguardando confirmação de recebimento da mesma
    {elseif $data == 'WaitingSendCancel'}
        Nota enviada para cancelamento na prefeitura e aguardando confirmação de recebimento da mesma
    {elseif $data == 'WaitingReturn'}
        Aguardando retorno da prefeitura com confirmação de nota emitida
    {elseif $data == 'WaitingDownload'}
        Aguardando download do PDF da nota
    {elseif $data == 'waiting'}
        Aguardando na fila para processamento
    {else}
        {$data}
    {/if}
{/function}

{function name=disableButtonAction}
    {if $data == 'Cancelled' OR $data == 'Error' OR $data == 'IssueFailed'}
        disabled="true"
    {/if}
{/function}
{function name=disableGenerateButtonAction}
    {if $data != 'Cancelled'}
        disabled="true"
    {/if}
{/function}
{function name=disableCancelButtonAction}
    {if $data == 'Cancelled'}
        disabled="true"
    {/if}
{/function}
<link href="https://cdn.datatables.net/v/bs/dt-2.2.2/datatables.min.css"
      rel="stylesheet"
      integrity="sha384-xd6yqpSXZRZVl62sBIxyT2i4xVlfaxWVjVQB7qsVte0qEr3iepsBrLi/awgmIoPV"
      crossorigin="anonymous">

<script src="https://cdn.datatables.net/v/bs/dt-2.2.2/datatables.min.js"
        integrity="sha384-KsmaH+vFCWsWkBqzoXM7HmafapkguLKrj9aRyWzIIaUDqRN99PP25wJUm7ZE+KP3"
        crossorigin="anonymous"></script>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title text-center">Notas Fiscais</h3>
            </div>
            <div class="panel-body">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <table class="table table-hover" id="serviceInvoicesTable">
                            <thead>
                            <th class="text-center">Fatura</th>
                            <th class="text-center">NFe.io ID</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Cliente</th>
                            <th class="text-center">Valor</th>
                            <th class="text-center">Emissor</th>
                            <th class="text-center">Gerado em</th>
                            <th class="text-center">Ações</th>
                            </thead>
                            <tbody>
                            {foreach from=$dtData item=nota }
                                <tr>
                                    <td class="text-center"><a href="invoices.php?action=edit&id={$nota->invoice_id}"
                                                               target="_blank">{$nota->invoice_id}</a></td>
                                    <td class="text-center">{$nota->nfe_id}</td>
                                    <td class="text-center">
                                        <div>
                                            <abbr title="{flowStatus data=$nota->flow_status}">
                                                {statusLabel data=$nota->status}
                                            </abbr>
                                        </div>


                                    </td>


                                    <td>
                                        <a href="clientssummary.php?userid={$nota->user_id}" target="_blank">
                                            {if $nota->companyname}
                                                ({$nota->companyname})
                                            {else}
                                                {$nota->firstname} {$nota->lastname}
                                            {/if}
                                        </a>
                                    </td>
                                    <td>R${$nota->services_amount}</td>
                                    <td class="text-center">{$nota->emissor_tax_number} - {$nota->emissor_name}</td>
                                    <td class="text-center"><abbr
                                                title="{$nota->created_at}">{$nota->created_at|date_format:"%d/%m/%Y"}</abbr>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button
                                                    class="btn btn-default btn-xs"
                                                    data-toggle="modal"
                                                    data-target="#modalInvoiceDetails"
                                                    data-nfeid="{$nota->nfe_id}"
                                                    data-invoiceid="{$nota->invoice_id}"
                                                    data-status='{statusLabel data=$nota->status}'
                                                    data-flowstatus='{flowStatus data=$nota->flow_status}'
                                                    data-createdat="{$nota->created_at|date_format:'%d/%m/%Y %H:%M'}"
                                                    data-client="{$nota->firstname} {$nota->lastname} ({$nota->companyname})"
                                                    data-amount="R${$nota->services_amount}"
                                                    data-companyid="{$nota->emissor_tax_number} - {$nota->emissor_name}"
                                                    data-servicecode="{$nota->service_code}"
                                                    data-condition="{$nota->issue_note_conditions}"
                                            >
                                                Detalhes
                                            </button>
                                            <button class="btn btn-default dropdown-toggle btn-xs" type="button"
                                                    id="dropdownMenuActions"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Ações
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuActions">
                                                <li>
{*                                                    <div class="btn-group-vertical" role="group" aria-label="...">*}
                                                        <button
                                                                class="btn btn-default btn-xs btn-block"
                                                                id="btnUpdate"
                                                                data-toggle="modal"
                                                                data-target="#actionConfirmationModal"
                                                                data-nfeid="{$nota->nfe_id}"
                                                                data-invoiceid="{$nota->invoice_id}"
                                                                data-companyid="{$nota->company_id}"
                                                                data-action="updateNfStatus"
                                                                data-actionname="atualizar status"
                                                                data-actiondesc="atualiza o status da nota fiscal de serviço"
                                                        >
                                                            Atualizar
                                                        </button>
                                                        <button onclick="goTo('https://app.nfe.io/companies/{$nota->company_id}/service-invoices/{$nota->nfe_id}', '_blank');"
                                                                formtarget="_blank"
                                                                class="btn btn-success btn-xs btn-block"
                                                                id="gnfe_view">
                                                            Visualizar
                                                        </button>
                                                        <button {disableGenerateButtonAction data=$nota->status}
                                                                class="btn btn-primary btn-xs btn-block"
                                                                id="btnReissue"
                                                                data-toggle="modal"
                                                                data-target="#actionConfirmationModal"
                                                                data-nfeid="{$nota->nfe_id}"
                                                                data-invoiceid="{$nota->invoice_id}"
                                                                data-action="reissueNf"
                                                                data-actionname="reemitir"
                                                                data-actiondesc="reemite a nota fiscal de serviço"
                                                        >
                                                            Reemitir NFSe
                                                        </button>
                                                        <button {disableButtonAction data=$nota->status}
                                                                class="btn btn-info btn-xs btn-block"
                                                                id="gnfe_email"
                                                                data-toggle="modal"
                                                                data-target="#actionConfirmationModal"
                                                                data-nfeid="{$nota->nfe_id}"
                                                                data-invoiceid="{$nota->invoice_id}"
                                                                data-action="emailNf"
                                                                data-actionname="enviar e-mail"
                                                                data-actiondesc="envia o e-mail com a nota fiscal de serviço. O e-mail é enviado pela plataforma da NFE.io"
                                                        >
                                                            Enviar e-mail
                                                        </button>
                                                        <button {disableCancelButtonAction data=$nota->status}
                                                                class="btn btn-danger btn-xs btn-block"
                                                                id="btnCancel"
                                                                data-toggle="modal"
                                                                data-target="#actionConfirmationModal"
                                                                data-nfeid="{$nota->nfe_id}"
                                                                data-invoiceid="{$nota->invoice_id}"
                                                                data-action="cancelNf"
                                                                data-actionname="cancelar"
                                                                data-actiondesc="cancela toda a serie de notas fiscais de serviço para a mesma fatura"
                                                        >
                                                            Cancelar NFSe
                                                        </button>
{*                                                    </div>*}
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{literal}
    <script>
        $(document).ready(function () {

            $('#serviceInvoicesTable').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.11.3/i18n/pt_br.json"
                },
                order: [[0, "desc"]]
            });
            // botao de confirmacao do cancelamento da nota
            $('#confirmCancel').click(function () {
                var link = '{$modulelink}&action=cancelNf&invoice_id={$nota->invoice_id}';
                window.open(link, '_self');
            });

        });

        function goTo(link, target) {
            window.open(link, target);
        }
    </script>
{/literal}

{include file="includes/modals/modal_taxinvoice_actions.tpl" id="actionConfirmationModal" modulelink=$modulelink}
{include file="includes/modals/modal_taxinvoice_details.tpl" id="modalInvoiceDetails"}