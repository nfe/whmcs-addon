## 2.0

### Rebrand do Módulo

#### Migração

* Adicionada rotina que migra os dados da versão anterior a 2.0 na ativação do módulo. Desta forma não é preciso se preocupar na reconfiguração do módulo.
* Adicionada rotina que migra as configurações persoanlizada do cliente para emissão de nota fiscal.
* Adicionada rotina que migra as notas fiscais da tabela da versão anterior para a nova tabela no banco de dados.

#### Estrutura de diretórios
#### Nomenclatura do módulo

* Nomenclatura do módulo alterado para `NFEioServiceInvoices`

#### Nomenclatura de funções

* Alteração dos nomes das funções para padronização da nova chave que define a nomenclatura dos nomes conforme convenção do WHMCS
* Inclusão das funções básicas padrões conforme convencionado pelo WHMCS:
  * _config(): https://developers.whmcs.com/addon-modules/configuration/
  * _activate() e _deactivate():  https://developers.whmcs.com/addon-modules/installation-uninstallation/
  * _output(): https://developers.whmcs.com/addon-modules/admin-area-output/
  * _upgrade(): https://developers.whmcs.com/addon-modules/upgrades/
* Refatoração das funções para implantação do _Code Style Guide_ e POO

#### Nomenclatura de tabelas

* Organização das tabelas necessárias pelo módulo no banco de dados
* Padronização dos registros de configuração do módulo na tabela `tbladdonmodules` respeitando o padrão de dados do WHMCS: agora todas as configurações do módulo como campos personalizados, configurações de emissão de nota e etc se concentram nesta tabela de forma a organizar a padronizar os registros necessários na versão anterior do módulo que estavam presentes na própria e em `tblconfiguration` (não recomendada para armazenar configurações persoanlizadas de módulos adicionais)
* Padronização dos arquivos responsáveis por realizar a criação das tabelas do módulo e formatação para o padrão de manipulação de dados SQl com a biblioteca `illuminate/database` padrão do WHMCS.

#### Code Style Guide

* Implementado um code style guide no módulo (POO)
* PSR Coding Standards: reestruturação dos arquivos bases do módulo para inicio da padronização pelas especificações PSR:
  * Padrão de codificação básico PSR-1: http://www.php-fig.org/psr/psr-1/
  * Guia de estilo de codificação PSR-2: http://www.php-fig.org/psr/psr-2/
  * Autoloading Standard PSR-4: https://www.php-fig.org/psr/psr-4/
* Inclusão de comentários e descrições nas classes e funções utilizando `DocBlock` syntax