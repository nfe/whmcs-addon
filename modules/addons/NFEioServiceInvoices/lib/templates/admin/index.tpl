{include file="includes/menu.tpl"}

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
    <span class="label label-default">{$data}</span>
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
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.js"></script>
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
                <th class="text-center">Data de Criação</th>
                <th class="text-center">Cliente</th>
                <th class="text-center">Valor</th>
                <th class="text-center">Status</th>
                <th class="text-center">Ações</th>
              </thead>
              <tbody>
                {foreach from=$dtData item=nota }
                  <tr>
                    <td class="text-center"><a href="invoices.php?action=edit&id={$nota->invoice_id}" target="_blank">{$nota->invoice_id}</a></td>
                    <td class="text-center">{$nota->nfe_id}</td>
                    <td class="text-center">{$nota->created_at|date_format:"%d/%m/%Y %H:%M"}</td>
                    <td>
                      <a href="clientssummary.php?userid={$nota->user_id}" target="_blank">
                        {$nota->firstname} {$nota->lastname}
                        {if $nota->companyname}
                          ({$nota->companyname})
                        {/if}
                      </a>
                    </td>
                    <td>R${$nota->services_amount}</td>
                    <td class="text-center">{statusLabel data=$nota->status}</td>
                    <td class="text-right">
                      <button {disableGenerateButtonAction data=$nota->status} onclick="goTo('{$modulelink}&action=legacyFunctions&invoice_id={$nota->invoice_id}&gnfe_create=yes', '_self');" class="btn btn-primary btn-sm" id="gnfe_generate">Emitir NFSe</button>
                      <button onclick="goTo('https://app.nfe.io/companies/{$company_id}/service-invoices/{$nota->nfe_id}', '_blank');" formtarget="_blank" class="btn btn-success btn-sm" id="gnfe_view">Visualizar</button>
                      <button {disableButtonAction data=$nota->status} onclick="goTo('{$modulelink}&action=legacyFunctions&invoice_id={$nota->invoice_id}&gnfe_cancel={$nota->nfe_id}&services_amount={$nota->services_amount}&environment={$nota->environment}&flow_status={$nota->flow_status}&user_id={$nota->user_id}&created_at={$nota->created_at}', '_self');" class="btn btn-danger btn-sm" id="gnfe_cancel">Cancelar NFSe</button>
                      <button {disableButtonAction data=$nota->status} onclick="goTo('{$modulelink}&action=legacyFunctions&gnfe_email={$nota->nfe_id}', '_self');" class="btn btn-info btn-sm" id="gnfe_email">Enviar e-mail</button>
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
    $(document).ready( function () {
      $('#serviceInvoicesTable').DataTable({
        language: {
          url: "https://cdn.datatables.net/plug-ins/1.11.3/i18n/pt_br.json"
        },
        order: [[0, "desc"]]
      });
    });
    function goTo(link, target) {
      window.open(link, target);
    }
  </script>
{/literal}