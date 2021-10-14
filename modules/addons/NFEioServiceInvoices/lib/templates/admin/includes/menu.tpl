{* <ul class="nav nav-tabs">
  <li role="presentation" {if $smarty.get.action eq "index"}class="active"{/if}><a href="{$modulelink}&action=index">{$LANG.menu_home}</a></li>
  <li role="presentation" {if $smarty.get.action eq "customfields"}class="active"{/if}><a href="{$modulelink}&action=customfields">{$LANG.menu_customfields}</a></li>
  <li role="presentation" {if $smarty.get.action eq "autofillzip"}class="active"{/if}><a href="{$modulelink}&action=autofillzip">{$LANG.menu_autofillzip}</a></li>
  <li role="presentation" {if $smarty.get.action eq "phonenumbermask"}class="active"{/if}><a href="{$modulelink}&action=phonenumbermask">{$LANG.menu_phonenumbermask}</a></li>
  <li role="presentation" {if $smarty.get.action eq "checkdocument"}class="active"{/if}><a href="{$modulelink}&action=checkdocument">{$LANG.menu_checkdocument}</a></li>
  <li role="presentation" {if $smarty.get.action eq "support"}class="active"{/if}><a href="{$modulelink}&action=support">{$LANG.menu_support}</a></li>
  <li role="presentation" {if $smarty.get.action eq "about"}class="active"{/if}><a href="{$modulelink}&action=about">{$LANG.menu_about}</a></li>
</ul>
<div style="margin-bottom:20px;"></div> *}
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
        <li role="presentation" {if $smarty.get.action eq "index"}class="active"{/if}><a href="{$modulelink}&action=index">{$_lang.menu_home}</a></li>
        <li role="presentation" {if $smarty.get.action eq "servicesCode"}class="active"{/if}><a href="{$modulelink}&action=servicesCode">{$_lang.menu_services_code}</a></li>


      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li role="presentation" {if $smarty.get.action eq "configuration"}class="active"{/if}><a href="{$modulelink}&action=configuration">{$_lang.menu_configuration}</a></li>
        <li role="presentation" {if $smarty.get.action eq "support"}class="active"{/if}><a href="{$modulelink}&action=support">{$_lang.menu_support}</a></li>
        <li role="presentation" {if $smarty.get.action eq "about"}class="active"{/if}><a href="{$modulelink}&action=about">{$_lang.menu_about}</a></li>

      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>
