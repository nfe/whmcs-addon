{include file="includes/menu.tpl"}
<div class="row">
<div class="col-md-6 col-md-offset-3">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title text-center">Sobre o Módulo</h3>
    </div>
    <div class="panel-body">
      <div class="center-block">
        <div class="media">
          <div class="media-left">
          <img src="{$assetsURL}/img/logo.png" alt="NFE">

          </div>
          <div class="media-body">
            <h4 class="media-heading">NFE.io NFSe</h4>
            <p>Automatize a emissão de nota fiscal eletrônica de serviço diretamente em seu WHMCS através do Módulo Nota Fiscal para WHMCS via NFE.io. Com este módulo, é possível automatizar a rotina de geração e envio de NFSe para seus clientes quando eles realizam o pagamento de uma fatura referente a um pedido ou serviço recorrente, emitir notas a partir de faturas avulsas ou toda vez que um pagamento é recebido no WHMCS por exemplo.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="panel panel-info">
    <div class="panel-heading">
      <h3 class="panel-title">Webhook de Callbacks</h3>
    </div>
    <div class="panel-body">
      <dl class="dl-horizontal">
        <dt>URL:</dt>
        <dd><code>{$webhook.url}</code></dd>
        
        <dt>ID:</dt>
        <dd><code>{$webhook.id|default:"Não configurado"}</code></dd>
        
        <dt>Secret:</dt>
        <dd><code>{$webhook.secret_masked|default:"N/A"}</code></dd>
        
        <dt>Última verificação:</dt>
        <dd>{$webhook.last_verified|default:"Nunca verificado"}</dd>
      </dl>
      
      <form method="post" action="{$modulelink}&action=verifyWebhook" style="margin-top: 15px;">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-refresh"></i> Verificar Status na API
        </button>
      </form>
    </div>
  </div>
</div>
</div>
