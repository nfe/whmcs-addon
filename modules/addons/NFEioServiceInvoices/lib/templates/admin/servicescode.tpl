{include file="includes/menu.tpl"}
<script>
  let dtData = {$dtJsonData};
</script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.js"></script>
<div class="row">
  <div class="col-md-8 col-md-offset-2">
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title text-center">Código de Serviço</h3>
      </div>
      <div class="panel-body">
        <p>Gerencie os códigos de serviço individualmente por produto existente no WHMCS.</p>
        <div class="panel panel-default">
          <div class="panel-body">
            <table id="productCodeTable" class="table table-hover">
              <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Código do Serviço</th>
                <th>Ação</th>
              </tr>
              </thead>
              <tbody>
              {foreach from=$dtData item=produto }
                <tr>
                  <form action="{$modulelink}&action={$formAction}" method="post">
                    <td>{$produto->id}</td>
                    <td>{$produto->name}</td>
                    <td><input class="form-control" type="text" name="service_code" required value="{$produto->code_service}"></td>
                    <td>
                      <input type="hidden" name="product_id" value="{$produto->id}">
                      <input type="hidden" name="product_name" value="{$produto->name}">
                      <button type="submit" class="btn btn-success btn-sm" name="btnSave" value="true">Salvar Código</button>
                      {if $produto->code_service}
                      <button type="submit" class="btn btn-danger btn-sm" name="btnDelete" value="true">Excluir Código</button>
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