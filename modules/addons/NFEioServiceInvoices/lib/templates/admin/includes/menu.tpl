<nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#"><img src="{$assetsURL}/img/logo.png" alt="NFE"></a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="navbar">
      <ul class="nav navbar-nav">
        <li role="presentation" {if $smarty.get.action eq "index"}class="active"{/if}><a href="{$modulelink}&action=index"><span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span> Notas Fiscais (NFSe)</a></li>
        <li role="presentation" {if $smarty.get.action eq "servicesCode"}class="active"{/if}><a href="{$modulelink}&action=servicesCode"><span class="glyphicon glyphicon-equalizer" aria-hidden="true"></span> Código de Serviço</a></li>
        <li role="presentation" {if $smarty.get.action eq "aliquots"}class="active"{/if}><a href="{$modulelink}&action=aliquots"><span class="glyphicon glyphicon-scale" aria-hidden="true"></span> Alíquotas & Retenções</a></li>


      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li class="dropdown {if $smarty.get.action eq 'configuration' || $smarty.get.action eq 'associateClients'}active{/if}">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Configurações <b class="caret"></b>
          </a>
          <ul class="dropdown-menu">
            <li {if $smarty.get.action eq "configuration"}class="active"{/if}>
              <a href="{$modulelink}&action=configuration">Configurações do Módulo</a>
            </li>
            <li {if $smarty.get.action eq "associateClients"}class="active"{/if}>
              <a href="{$modulelink}&action=associateClients">Associar Clientes</a>
            </li>
          </ul>
        </li>
{*        <li role="presentation" {if $smarty.get.action eq "configuration"}class="active"{/if}><a href="{$modulelink}&action=configuration"><span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Configurações</a></li>*}
        <li role="presentation" {if $smarty.get.action eq "support"}class="active"{/if}><a href="{$modulelink}&action=support"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> Suporte</a></li>
        <li role="presentation" {if $smarty.get.action eq "about"}class="active"{/if}><a href="{$modulelink}&action=about"><span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> Sobre</a></li>

      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
