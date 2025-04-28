## v3.0.0
A versão 3.0.0 traz melhorias significativas, incluindo suporte a multiempresas, novas funcionalidades e aprimoramentos na usabilidade.

### Novos Recursos
#### Multiempresas
Agora, é possível configurar múltiplos emissores com suporte a multiempresas. Contas da NFe.io com mais de uma empresa cadastrada podem utilizar este recurso para definir diferentes emissores na configuração do módulo. Isso permite associar um cliente do WHMCS a um emissor específico, que será utilizado como emitente para as notas fiscais, independentemente da empresa padrão configurada para emissão.

![Configuração Multiempresas](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-06.png)

- **Emissor Padrão**: Configurações que utilizarem mais de um emissor cadastrado precisarão definir um como padrão. Este emissor será utilizado para todos os clientes que não possuírem uma associação personalizada.
  
- **Associar Clientes**: A nova tela "Associar Clientes" em Configurações permite a associação de um cliente a um emissor específico, garantindo que todos os produtos ou serviços faturados neste cliente tenham como emitente a empresa associada.

![Associar Clientes](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-13.png)

- **Códigos Personalizados**: Com o recurso multiempresa, os códigos personalizados agora podem ser associados a um emissor específico. Isso possibilita definir diferentes códigos de serviços personalizados para diferentes produtos e emissores. Ao cadastrar um novo código de serviço, será necessário selecionar a qual dos emissores cadastrados ele se destina.

![Códigos Personalizados](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-03.png)

- **Alíquotas**: As alíquotas dos códigos personalizados agora estão associadas a um emissor. Ao cadastrar uma nova alíquota, o emissor vinculado ao código de serviço selecionado será exibido.

![Alíquotas](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-10.png)

#### Detalhes da Nota
Uma nova opção "Detalhes" foi adicionada junto às ações da nota. Agora é possível visualizar mais detalhes da nota, como códigos ou mensagens retornadas da API de emissão.

![Detalhes da Nota](https://nfe.github.io/whmcs-addon/assets/changelogs/b7ec7e81-b11a-43c5-b011-17d6ed468fd1.png)

### Melhorias
#### Visualização de Notas
A tabela de visualização de notas emitidas pelo módulo recebeu uma reorganização das colunas e ações, visando adequar as informações e melhorar o layout das ações disponíveis. Os diferentes botões de ações foram agrupados em um sub-menu para uma melhor exibição.

![Visualização de Notas](https://nfe.github.io/whmcs-addon/assets/changelogs/617177f2-3830-4915-8d3c-e4e6d9755fe5.png)

#### Códigos Personalizados
Foram introduzidas melhorias na gestão dos códigos personalizados dos produtos, incluindo novas janelas de cadastramento e confirmações na exclusão de registros.

#### Alíquotas
Melhorias na gestão de alíquotas foram implementadas. Agora, além de novas janelas para cadastramento e exclusão, a tabela exibirá apenas as alíquotas cadastradas, ao contrário da versão anterior que mostrava todos os códigos de serviços personalizados, mesmo sem uma alíquota cadastrada.

#### Demais Melhorias
- Alguns métodos que dependiam de código legado foram reescritos para melhor manutenção.
- Melhorias no código de tratamento do callback para maior legibilidade e performance.
- Registro do número da RPS retornado na emissão da NF no banco de dados do módulo.
- Os processos de download do PDF e XML foram atualizados, agora retornando os arquivos pelo SDK da NFe.io.

### Notas sobre Atualização e Migração
- Algumas reestruturações removeram a dependência de arquivos de versões legadas. É recomendado a exclusão do diretório `NFEioServiceInvoices` existente no WHMCS antes de realizar o upload novamente do diretório.
- Esta versão implementa novas colunas nas tabelas existentes, além de novas que serão responsáveis pelos dados das empresas emissoras cadastradas e pela associação de clientes.
- **Importante**: Sempre realize um backup antes de qualquer atualização.

## 2.2.0 - 2024-06-13

### Novos Recursos

#### Validações para CPF e CNPJ

Foi incluída uma nova classe de auxílio `Validations` no módulo `NFEioServiceInvoices`. Essa classe contém métodos para validar CPFs e CNPJs com base no algoritmo de validação, conforme regras de verificação, ao invés da definição de validade de documento pelo tamanho de caracteres, garantindo a integridade dos dados tratados.

Agora quando o número de documento não for válido, seja um CPF ou CNPJ, o processo de emissão da nota será encerrado e uma mensagem com o motivo será mostrado ao usuário, além de um registro detalhado no log do módulo quando em modo depuração.

![Screenshot of WHMCS - NFE io NFSe (1)](https://github.com/nfe/whmcs-addon/assets/5316107/87fd4fa6-b318-4f1a-84ba-474a301b64b9)

![Screenshot of WHMCS - System Module Debug Log](https://github.com/nfe/whmcs-addon/assets/5316107/0248dab5-fc84-487f-a398-98f6759b05e9)

#### Validação e manipulação de webhook (hmac)

Foi adicionado um novo método de validação de webhook na classe de validação e uma nova manipulação de webhook no arquivo callback. Agora, é possível verificar a assinatura do [webhook (HMAC)](https://nfe.io/docs/documentacao/webhooks/duvidas-frequentes/#2_Como_saber_se_o_webhook_que_recebi_e_da_NFEio) e certificar-se de que é de uma fonte confiável antes de processá-lo.

Também foi melhorado o retorno de erros e códigos de respostas para as chamadas da API ao callback, com isso é possível permitir que a API realize novas tentativas de envio em casos de impossibilidade do processamento na primeira chamada, evitando que atualizações de informações no módulo não sejam prejudicadas por qualquer impossibilidade momentânea do módulo em escutar os retornos.

#### Opção para atualização da nota

Foi adicionado na interface do administrador uma nova opção que permite a atualização do status da nota de forma manual. Com isso, é possível buscar as informações diretamente na API para refletir estas informações no WHMCS.
Este recurso é útil em casos onde as informações do status da nota não foram sincronizados com o módulo.

![image](https://github.com/nfe/whmcs-addon/assets/5316107/edc51efb-7dc5-421c-8839-c8c90570fbe0)

### Melhorias

#### Tratamento de status do cancelamento da nota via API

Refatorado a manipulação do status da nota quando é realizada cancelamento para atender a estrutura atual de retorno da API de cancelamento. Antes, ao cancelar uma nota via API, o objeto de retorno possuía um atributo "message" onde o processo de atualização de notas cancelava se baseava. Agora, a API de cancelamento está retornando a nf no objeto e código de status 202. Devido a isso as notas canceladas não estavam sendo registradas adequadamente.

![Screenshot from 2024-04-27 21-37-02](https://github.com/nfe/whmcs-addon/assets/5316107/2ba93cb4-7da6-4281-bf76-fcf432ab7760)

#### Melhoria no tratamento de erros e registro de status do fluxo

Foi adicionado o registro do valor de flowStatus dos retornos do webhook, garantindo que em cenários de falha ou problemas, a informação deste atributo esteja registrada corretamente para solução de problemas. Com isso será possível analisar as mensagens de retorno de forma mais eficiente e também utilizar seus valores para processamento nas rotinas de mensagens de retorno implementadas nestas atualizações.

![image](https://github.com/nfe/whmcs-addon/assets/5316107/f9284f84-9215-4f8d-9d30-a7d581ec4f7d)

Também foi padronizado os identificadores e adicionado novos registros de log para permitir uma melhor depuração, tanto do retorno do webhook quanto em rotinas internas do módulo.

**NOTA:** A mensagem _ApiNoResponse_ é uma mensagem interna utilizada para identificar quando uma ação de cancelamento não retornou o devido status pela nota já se encontrar cancelada na API ou tiver sua emissão não concluída e o usuário tenta cancelar da mesma forma.

#### Modais de confirmação

Foi adicionado modais de confirmação para as ações de cancelamento e reemissão de notas fiscais. 
Agora, ao realizar uma destas ações, o usuário será questionado se deseja prosseguir com a ação.

### Correções

#### Corrigido problema no registro de timestamp das informações no banco de dados

Foi corrigido um problema na definicao do tipo de valor padrao para os campo de created_at e updated_at nas tabelas do módulo. As informações nao estavam sendo registradas devidamente por algumas definicoes manuais de data de rotinas legadas e também devido a falta de uma definicao de valores padroes para estas colunas do tipo timestamp que poderia gerar uma atribuição equivocada de valores nestes campos.

Detalhes da análise e correcao podem ser encontradas na questao 156 https://github.com/nfe/whmcs-addon/issues/156

#### Corrigido problema de re-emissão duplicada na fatura

Foi corrigido uma condição que poderia levar a emissão duplicada de notas quando administrador tenta gerar novas notas
a partir da visualização de uma fatura. #160

## 2.1.8 - 2023-03-15

### Correções

* Adicionado compatibilidade com PHP 8.x e WHMCS 8.6 #146 #149

## 2.1.7 - 2023-02-08

### Correções

* [BUG] Checagem e criação de webhook

## 2.1.6 - 2022-10-24

### Correções

* [BUG] Nota Fiscal com status "processando" quando emissão falha #143
* Melhora a exibição das mensagens de status na interface web para os administradores.

## 2.1.5 - 2022-09-24

### Correções

* Corrige problema de valor para _initial_date_ retornando nulo, o que impedia a seleção das notas que estão em fila para emissão ocorrer como esperado #139.

## 2.1.4 - 2022-09-01

### Melhorias

* Dropdown de seleção de empresas #72 by @andrekutianski in #131

### Correções

* Em algumas situações, quando a nota local não era sincronizada com a API para emissão e fosse realizada a tentativa de cancelar no WHMCS para uma nova reemissão, um erro era retornado pelo motivo da API não a encontrar para cancelamento (retorno nulo). Agora, quando a resposta de cancelamento da API for nula, a nota local será marcada como cancelada. #133
* Corrige rotina de emissão manual quando initial_date está ausente #132
* Aumenta quantidade máxima de caracteres para código de serviço personalizado #134

### Estilo

* Melhorado nomes e descrição de campos de configuração para refletirem nomes de campos da NFE.io

## 2.1.3 - 2022-06-07

### Correção

* Atualizada a lógica de reemissão das notas ficais, agora quando um administrador realizar a ação a nova nota receberá os dados mais recentes tanto da fatura quanto do cliente. Isso possibilita com que qualquer atualização nestas informações reflita na nota reemitida. (#125)
* Atualizada a lógica de cancelamento das notas ficais. Agora, caso uma fatura possua mais de uma NF, todas serão canceladas. Anteriormente o cancelamento era realizado apenas na nota específica. Com isso é possível cancelar e reemitir notas com informações recentes da fatura do cliente. (#125)
* Adicionado botões com as ações para cancelamento da nota fiscal e reemissão da série na visualizaçã/edição de uma fatura pelo administrador.

### Atualização

* Atualizado pacotes e dependências do composer.

## 2.1.2 - 2022-06-04

### Correção

* Corrige inconsistência nos comandos de cancelamento de nota fiscal do módulo que não permitiam a alteração do status caso houvesse erro na emissão. (#125)
* Corrigido opção para possibilitar que uma nota cancelada possa ser reemitida. Este recurso emite uma nova nota com as mesmas informações da anterior (caso a fatura ou item tenha sido atualizado, essas informações NÃO refletirão na NF reemitida). (#125)

### Melhoria

* Melhoria na diagramação das informações e inserção da coluna para exibição do ID da nota na NFE.io para facilitar identificação.

## 2.1.1 - 2022-05-31

### Correção

* Corrigido erro de `SQLSTATE` ao acessar página de configuração de códigos de serviços #122

## 2.1.0 - 2022-04-11

### Novo Recurso

#### Calculo de descontos existentes na fatura 

Agora é possível deduzir os descontos de uma fatura na nota fiscal. Quando uma fatura possuir um item de desconto ou item com valor negativo, o mesmo será deduzido do valor total da nota a ser emitida. Se uma fatura possuir vários itens de desconto para diferentes serviços, os descontos serão somados e descontados com base no grupo de código de serviço. Este recurso pode ser desativado na configuração do módulo (ativado por padrão) (#118).

#### Emissão de notas com itens consolidados

A partir desta versão, faturas que possuírem diferentes itens com mesmo código de serviço terão seus valores, descontos e descrições consolidadas para emissão em uma única nota fiscal. Se uma fatura possuir itens com diferentes códigos de serviços, os itens com mesmo código serão consolidados em diferentes notas fiscais (#119).

### Melhorado

* Logs: melhorado registro de logs para debug do callback (#55)(#116).
* Alíquotas e Retenções: Agora o valor de retenção para ISS pode ser personalizado por código de serviço e não mais por produto. Com isso evita a possibilidade de produtos com mesmo código de serviço possuam diferentes alíquotas de retenção de ISS (#74).
* Emitir NF para itens faturáveis: A partir desta versão é possível emitir nota fiscal para qualquer tipo de item faturável (hora ou item). Itens faturáveis receberão as configurações de código de serviço, alíquotas e descontos padrões do módulo (#111).
* Emissão em duplicidade: Melhorado mecanismo que evita a geração de notas fiscais duplicadas para a mesma fatura (#110).

### Corrigido

* Possibilidade de clique nos botões de ações na administração mesmo estando desabilitado (#117)

### Removido

* WHMCS 7: Removida compatibilidade com WHMCS 7.

#### Documentação

Documentação do módulo atualizada https://nfe.github.io/whmcs-addon/

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
