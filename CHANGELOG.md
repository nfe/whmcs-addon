## 2.1.0-beta - 2022-03-14

### Novo recurso

- Agora ao criar uma nota é gerado um identificador externo único para evitar a criação de NF em duplicidade (#110).

### Corrigido

- Corrigido lógica de emissão para gerar notas distintas para cada item de uma fatura, manual e automático (#108).
- Corrigido problema no WHMCS 8.2 que somente a nota da primeira fatura era gerada quando executado a cron (#108).
- Corrigido layout dos itens do módulo ao acessar uma fatura pelo admin do WHMCS (#109).

## 2.0.2 - 2021-01-27
### Corrigido
- Corrige a exibição do dropdown "Emitir nota fiscal quando" #107
- Corrige o erro quando o administrador tenta salvar uma edição do perfil do cliente, consequência da ausência do dropdown "Emitir nota fiscal quando" #106

## 2.0.1

* Refatoração do processo de emissão de notas automaticamente conforme a questão #103

## 2.0.0

### Rebrand do Módulo

#### Interfaces

* Nova interface de configuração
* Nova interface de edição de código de serviço
* Nova interface de gestão de notas fiscais

#### Migração

* Adicionada rotina que migra os dados da versão anterior a 2.0 na ativação do módulo. Desta forma não é preciso se preocupar na reconfiguração do módulo.
* Adicionada rotina que migra as configurações persoanlizada do cliente para emissão de nota fiscal.
* Adicionada rotina que migra as notas fiscais da tabela da versão anterior para a nova tabela no banco de dados.
* Adicionada rotina de migração dos códigos de serviço personalizados por produto para a nova tabela.

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
