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
                <th>Fatura</th>
                <th>Data de Criação</th>
                <th>Cliente</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Ações</th>
              </thead>
              <tbody>
                {foreach from=$dtData item=nota }
                  <tr>
                    <td>{$nota->invoice_id}</td>
                    <td>{$nota->created_at}</td>
                    <td>
                      {$nota->firstname} {$nota->lastname}
                      {if $nota->companyname}
                        ({$nota->companyname})
                      {/if}
                    </td>
                    <td>R${$nota->services_amount}</td>
                    <td>{statusLabel data=$nota->flow_status}</td>
                    <td>
                      <a href="{$modulelink}" class="btn btn-primary btn-sm">Emitir NFSe</a>
                      <a href="https://app.nfe.io/companies/{$company_id}/service-invoices/{$nota->nfe_id}" target="_blank" class="btn btn-success btn-sm">Visualizar</a>
                      <a href="{$modulelink}" class="btn btn-danger btn-sm">Cancelar NFSe</a>
                      <a href="{$modulelink}" class="btn btn-info btn-sm">Enviar e-mail</a>
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
  </script>
{/literal}