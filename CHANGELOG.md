## 2.1.0 - 2022-04-11

### Novo Recurso

#### Calculo de descontos existentes na fatura 

Agora é possível deduzir os descontos de uma fatura na nota fiscal. Quando uma fatura possuir um item de desconto ou item com valor negativo, o mesmo será deduzido do valor total da nota a ser emitida. Se uma fatura possuir vários itens de desconto para diferentes serviços, os descontos serão somados e descontados com base no grupo de código de serviço. Este recurso pode ser desativado na configuração do módulo (ativado por padrão) (#118).

#### Emissão de notas com itens consolidados

A partir desta versão, faturas que possuírem diferentes itens com mesmo código de serviço terão seus valores, descontos e descrições consolidadas para emissão em uma única nota fiscal. Se uma fatura possuir itens com diferentes códigos de serviços, os itens com mesmo código serão consolidados em diferentes notas fiscais (#119).

### Melhorado

* registro de logs para debug do callback (#55) (#116).
* Alíquotas e Retenções: agora o valor de retenção para ISS pode ser personalizado por código de serviço e não mais por produto. Com isso evita a possibilidade de produtos com mesmo código de serviço possuam diferentes alíquotas de retenção de ISS (#74). 

### Corrigido

* Possibilidade de clique nos botões de ações na administração mesmo estando desabilitado (#117)

## 2.1.0-beta.3 - 2022-04-06

### Corrigido

* **Alíquotas & Retenções**: Edição do campo retenção de ISS na configuração global, não estava persistindo as alterações (da62d68e81742190b124b63e62b4981a198d23c7).
* **Emissão de NF**: Itens de fatura com valor negativo gerava emissão no NF (#115).

### Melhorado

* **Administração**: Exibição da tabela com informações das notas na visualização da fatura pelo administrador (3837188e88d27183db8ee209edec8601707211d1).

### Refatorado

* **Cliente**: Exibição e download em PDF e XML das notas fiscais de uma fatura pelo cliente (#69).

**Full Changelog**: https://github.com/nfe/whmcs-addon/compare/v2.1.0-beta.2...v2.1.0-beta.3

## 2.1.0-beta.2 - 2022-03-18

### Novo recurso

* **Alíquotas & Retenções**: Agora é possível informar a alíquota de retenção do ISS para produtos/serviços em um novo menu na administração do módulo #74

### Corrigido

* **Emissão de NF**: Corrigido indicação de quando emitir a NF personalizada por cliente, a função responsável pela verificação de quando emitir poderia retornar nulo em algumas situações, fazendo com que a nota não fosse emitida conforme a condição desejada.
* Corrigido _namespace_ do banco de dados em algumas classes.

### Melhorado

* **Registro de logs**: Adicionado registro de log na rotina da cron do módulo para que o WHMCS mesmo que não haja notas a serem emitidas grave a informação, facilitando a depuração de eventuais problemas.
* **Migração da v1.4**:  Alterada lógica para compatibilidade quando realizado migração da v1.4 para a v2.1 devido à necessidade de inserção de novas colunas nas tabelas migradas. #112

**Full Changelog**: https://github.com/nfe/whmcs-addon/compare/v2.0.0...v2.1.0-beta.2

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
