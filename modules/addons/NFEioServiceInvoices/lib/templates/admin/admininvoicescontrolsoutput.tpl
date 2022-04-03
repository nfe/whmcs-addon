{function name=statusLabel}
    {if $data == 'Waiting'}
        <span class="label label-warning">Aguardando</span>
    {elseif $data == 'Created'}
        <span class="label label-info">Processando</span>
    {elseif $data == 'Issued'}
        <span class="label label-success">Emitida</span>
    {elseif $data == 'Cancelled'}
        <span class="label label-danger">Cancelada</span>
    {elseif $data == 'Error'}
        <span class="label label-danger">Falha ao Emitir</span>
    {elseif $data == 'Error_cep'}
        <span class="label label-danger">CEP do cliente inválido</span>
    {elseif $data == 'None'}
        <span class="label label-primary">Nenhum</span>
    {else}
        <span class="label label-danger">{$data}</span>
    {/if}
{/function}

{function name=disableButtonAction}
    {if $data == 'Cancelled'}
        disabled="true"
    {/if}
{/function}

<div class="row">
    <div class="col-sm-12">
        <hr>
    </div>
        {if $totalServiceInvoices > 0}
            <div class="col-sm-6">
                {if $totalServiceInvoices > {$serviceInvoicesQueryLimit}}
                    <span class="text-left pull-left"><strong>Nota Fiscal:</strong> exibindo <strong>{$serviceInvoicesQueryLimit}</strong> de <strong>{$totalServiceInvoices}</strong></span>
                {else}
                    <span class="text-left pull-left"><strong>Nota Fiscal</strong>: {$totalServiceInvoices} encontradas</span>
                {/if}
             </div>
        {elseif $invoiceStatus != 'Draft'}
            <div class="col-sm-12 center-block">
            <form action="" method="post">
                <input type="hidden" name="nfeiosi" value="create">
                <button type="submit" class="btn btn-primary">Emitir Nota Fiscal</button>
            </form>
            </div>
        {/if}

    {if $totalServiceInvoices > 0 && $localServiceInvoices}
        <div class="col-sm-12">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                    <th class="text-center">ID</th>
                    <th class="text-center">Valor</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Gerada Em</th>
                    <th class="text-center">Ações</th>
                    </thead>
                    <tbody>
                    {foreach from=$localServiceInvoices item=nota name=nota}
                        <tr>
                            <td class="text-center">{$nota->nfe_id}</td>
                            <td class="text-center">{$nota->services_amount}</td>
                            <td class="text-center">{statusLabel data=$nota->status}</td>
                            <td class="text-center">{$nota->created_at|date_format:"%d/%m/%Y %H:%M:%S"}</td>
                            <td>
                                <form action="" method="post" id="nfeio_frm_cancel_{$smarty.foreach.nf.iteration}">
                                    <input type="hidden" name="nfeiosi" value="cancel">
                                    <input type="hidden" name="nfeiosi_id" value="{$nota->nfe_id}">
                                </form>
                                <form action="" method="post" id="nfeio_frm_email_{$smarty.foreach.nf.iteration}">
                                    <input type="hidden" name="nfeiosi" value="email">
                                    <input type="hidden" name="nfeiosi_id" value="{$nota->nfe_id}">
                                </form>
                                <a class="btn btn-xs btn-success" href="https://app.nfe.io/companies/{$companyId}/service-invoices/{$nota->nfe_id}" target="_blank">Visualizar</a>
                                <button {disableButtonAction data=$nota->status} type="submit" class="btn btn-xs btn-danger" form="nfeio_frm_cancel_{$smarty.foreach.nf.iteration}">Cancelar</button>
                                <button {disableButtonAction data=$nota->status} type="submit" class="btn btn-xs btn-info" form="nfeio_frm_email_{$smarty.foreach.nf.iteration}">Enviar e-mail</button>

                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {/if}
</div>