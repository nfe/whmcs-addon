{include file="includes/menu.tpl"}
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.js"></script>
<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title text-center">Alíquotas & Retenções</h3>
      </div>
      <div class="panel-body">
        <p>Gerencie as alíquotas e retenções de impostos por cada produto/serviço. Valores personalizados substitui valores globais.</p>
        <p><strong>Dica:</strong> para desativar o valor global para um produto informe o valor 0 (zero).</p>
        <div class="panel panel-default">
          <div class="panel-body">
            <table id="productCodeTable" class="table table-hover">
              <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Código do Serviço</th>
                <th>ISS Retido %</th>
                <th class="text-center">Ações</th>
              </tr>
              </thead>
              <tbody>
              {foreach from=$dtData item=produto }
                <tr>
                  <form action="{$modulelink}&action={$formAction}" method="post" class="form-inline">
                    <td>{$produto->id}</td>
                    <td>{$produto->name}</td>
                    <td>{$produto->code_service}</td>
                    <td>
                      <div class="form-group">
                        <div class="input-group">
                          <input class="form-control" type="text" name="iss_held" id="issHeld" size="5" value="{$produto->iss_held}">
                          <div class="input-group-addon">%</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <input type="hidden" name="service_code" value="{$produto->code_service}">
                      <input type="hidden" name="product_id" value="{$produto->id}">
                      <input type="hidden" name="product_name" value="{$produto->name}">
                      <button type="submit" class="btn btn-success btn-sm" name="btnSave" value="true">Salvar Alíquotas</button>
                      {if $produto->iss_held}
                      <button type="submit" class="btn btn-danger btn-sm" name="btnDelete" value="true">Limpar Alíquotas</button>
                      {/if}
                    </td>
                  </form>

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
    $('#productCodeTable').DataTable({
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.11.3/i18n/pt_br.json"
      },
      order: [[0, "desc"]]
    });
  });
</script>
{/literal}