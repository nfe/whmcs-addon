{function name=statusLabel}
    {if $data == 'Waiting'}
        <span class="label label-warning">Aguardando</span>
    {elseif $data == 'Created'}
        <span class="label label-info">Processando</span>
    {elseif $data == 'Issued'}
        <span class="label label-success">Emitida</span>
    {elseif $data == 'PullFromCityHall'}
        <span class="label label-info">Obtendo da Prefeitura</span>
    {elseif $data == 'WaitingCalculateTaxes'}
        <span class="label label-info">Calculando Impostos</span>
    {elseif $data == 'WaitingSend'}
        <span class="label label-info">Aguardando Envio</span>
    {elseif $data == 'WaitingReturn'}
        <span class="label label-info">Aguardando Retorno</span>
    {elseif $data == 'Cancelled'}
        <span class="label label-danger">Cancelada</span>
    {elseif $data == 'WaitingSendCancel'}
        <span class="label label-danger">Aguardando Cancelamento</span>
    {elseif $data == 'Error'}
        <span class="label label-danger">Falha ao Emitir</span>
    {elseif $data == 'Error_cep'}
        <span class="label label-danger">CEP do cliente inválido</span>
    {elseif $data == 'CancelFailed'}
        <span class="label label-danger">Cancelado por Erro</span>
    {elseif $data == 'IssueFailed'}
        <span class="label label-danger">Falha ao Emitir</span>
    {else}
        <span class="label label-default">Não Disponível</span>
    {/if}
{/function}

{if !empty($nfeIoTaxBills)}
    <div class="container-fluid invoice-container">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>NFS-e - Nota Fiscal de Serviços</strong></h3>
            </div>
            <div class="panel-body">
                <p>Notas fiscais disponíveis para a fatura #{$invoiceid}.</p>
                <div class="table-responsive">
                    <table class="table table-condensed">
                        <thead>
                        <tr>
                            <td><strong>ID</strong></td>
                            <td class="text-center"><strong>Valor</strong></td>
                            <td class="text-center"><strong>Status</strong></td>
                            <td class="text-center"><strong>Download</strong></td>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $nfeIoTaxBills name=nf item=nf}
                            <tr>
                                <td>{$nf.nfe_id}</td>
                                <td class="text-center">{$nf.amount}</td>
                                <td class="text-center">{statusLabel data=$nf.status_flow}</td>
                                <td class="text-center">
                                    {if $nf.status_flow eq 'Issued'}
                                        <a href="index.php?m=NFEioServiceInvoices&action=downloadNfPdf&nfid={$nf.nfe_id}" target="_blank" class="btn btn-primary btn-sm">PDF</a>
                                        <a href="index.php?m=NFEioServiceInvoices&action=downloadNfXml&nfid={$nf.nfe_id}" target="_blank" class="btn btn-primary btn-sm">XML</a>
                                    {/if}
                                </td>

                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
{/if}