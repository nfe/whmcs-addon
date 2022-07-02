# Módulo Nota Fiscal para WHMCS via NFE.io

> Automatize a emissão de notas fiscais no WHMCS com a [NFE.io][nfeio]!

A [NFE.io][nfeio] é um sistema de emissão de notas fiscais que automatiza a comunicação com as prefeituras. Com a [NFE.io][nfeio] você se livra de diversas tarefas chatas, melhorando o desempenho do seu negócio. E melhor, você economiza tempo e dinheiro.

Automatize a emissão de nota fiscal eletrônica de serviço diretamente em seu WHMCS através do **Módulo Nota Fiscal para WHMCS via NFE.io**. Com este módulo, é possível automatizar a rotina de geração e envio de NFSe para seus clientes quando eles realizam o pagamento de uma fatura referente a um pedido ou serviço recorrente, emitir notas a partir de faturas avulsas ou toda vez que um pagamento é recebido no WHMCS por exemplo.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-notas-fiscais.png)

> Este módulo é para emissão de NFS-e, Nota Fiscal de Serviço Eletrônica, não sendo possível emissão de nota de produto.

## Principais Recursos

* [x] Emissão automática de notas fiscais
* [x] Emissão manual de notas fiscais
* [x] Agendamento de emissão de notas fiscais
* [x] Cancelamento de nota fiscal quando fatura é cancelada
* [x] Configuração de código de serviço personalizado por produto
* [x] Painel intuitivo de visualização de notas emitidas
* [x] Botões de ação rápida para emissão, cancelamento e envio
* [x] Acompanhamento do status da emissão
* [x] Envio de nota fiscal por e-mail
* [x] Download de nota em PDF e XML

### Demais Recursos

* [x] Emite notas fiscais de forma sequencial, evitando sobrecargas nos sites das prefeituras.
* [x] salva o debug das chamadas à API NFE.io no log de Módulo do WHMCS para diagnóstico
* [x] seleciona nas configurações do módulo a opção de enviar o número inscrição municipal para a nota fiscal.
* [x] seleciona nas configurações do módulo a opção de enviar a nota fiscal por e-mail automaticamente.
* [x] valida CPF/CNPJ do cliente e não emite a nota fiscal caso inválido

## Antes de começar

Antes de realizar a instalação do módulo, leia com atenção as informações a seguir, elas são importantes para que todo o processo de instalação possa ocorrer sem problemas.

### Documentação

A documentação completa do módulo está disponível também em https://nfe.github.io/whmcs-addon/

### Compatibilidade

#### WHMCS

O módulo de emissão de nota fiscal para WHMCS da NFE.io é compatível com versões do WHMCS 8 ou superiores. A tabela com o "suporte de longo termo" do WHMCS pode ser acessada em https://docs.whmcs.com/Long_Term_Support#WHMCS_Version_.26_LTS_Schedule 

#### PHP

O módulo suporta PHP com versões superior a 7.2. Para visualizar a matrix de compatibilidade de PHP do WHMCS acesse https://docs.whmcs.com/PHP_Version_Support_Matrix

### Campos Personalizados

O módulo irá requerer os seguintes campos personalizados para o cliente:

| Campo | Criação | Preenchimento |
| :---: | :---: | :---: |
| CPF/CNPJ | Obrigatória | Obrigatório |
| Inscrição Municipal | Obrigatória | Opcional |

Na administração do WHMCS, crie um campo personalizado de cliente para registrar o CPF/CNPJ necessário para a emissão da NFSe e outro para a Inscrição Municipal.

**Caso já exista** um campo personalizado de cliente configurado e utilizado para registrar o número do documento (CPF/CNPJ), **não será necessário criar outro**.

O campo `Inscrição Estadual` é de criação obrigatória, mas de preenchimento opcional pelo cliente, necessário para emissão de notas para pessoa jurídica.

> **Atenção:** O módulo identificará automaticamente se o número de documento informado no campo personalizado se trata de CPF ou CNPJ e emitirá a nota em conformidade com o tipo de pessoa (física ou jurídica).

> **Dica:** é possível utilizar campos personalizados diferentes para preenchimento de CPF e CNPJ.

### Requisitos

Os requisitos a seguir são necessários para o funcionamento adequado do módulo e integração.

1. WHMCS versão 8 ou superior;
2. PHP 7.2 ou superior;
3. Chave de API da NFE.io;
4. Automação do WHMCS devidamente configurada ([https://docs.whmcs.com/Automation_Settings](https://docs.whmcs.com/Automation_Settings));
5. Tarefas cron do Sistema sendo executadas conforme recomendações do WHMCS [https://docs.whmcs.com/Crons#System_Cron](https://docs.whmcs.com/Crons#System_Cron).

## Instalação

Para instalar o módulo no WHMCS realize os seguintes passos.

### Baixar o módulo

Faça o download arquivo zip da última versão módulo neste link [https://github.com/nfe/whmcs-addon/releases/latest](https://github.com/nfe/whmcs-addon/releases/latest)

Veja a lista completa de versões em https://github.com/nfe/whmcs-addon/releases

### Descompactar o zip

Descompacte o zip baixado, o conteúdo do diretório extraído deve ser semelhante a este:

* modules
    * addons
        * NFEioServiceInvoices

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-01.png)

### Enviar arquivos para o WHMCS

Carregue o diretório `modules` existente no arquivo descompactado para o diretório de instalação do seu WHMCS.

Por exemplo, tendo o WHMCS instalado em `public_html`, carregue o diretório `modules` em `public_html`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-02.png)

> **Dica:** O arquivo descompactado já está na estrutura associada aos módulos addon do WHMCS `modules/addons`. Ao carregar o diretório `modules` você automaticamente carregará o diretório do módulo `modules/addons/NFEioServiceInvoices`.

### Ativar o módulo addon

Após realizar o carregamento dos arquivos do módulo, ele está disponível para ativação e configuração no WHMCS.

Veja a seguir os passos para ativação do módulo no WHMCS 8 e WHMCS 7.

#### WHMCS 8.X

Para ativar o módulo adicional no WHMCS versão 8.x vá até o ícone de chave no canto superior direito e clique em `Opções`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-03.png)

O campo de busca em `Opções` digite `addon` e acesse a opção `Módulos Addon`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-04.png)

Localize o módulo addon **NFE.io NFSe** e clique no botão `Ativar`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-05.png)

#### WHMCS 7.X

Para ativar o módulo adicional no WHMCS versão 7.x acesse o menu `Opções -> Módulos Addons`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-06.png)

Localize o módulo addon **NFE.io NFSe** e clique no botão `Ativar`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-05.png)

## Configuração do addon

Após ativar o [Módulo Nota Fiscal para WHMCS via NFE.io](https://github.com/nfe/whmcs-addon) as seguintes opções devem ser configuradas.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-instalacao-07.png)

#### API Key

> campo obrigatório

Chave de acesso privada gerado na sua conta NFE.io, necessária para a autenticação das chamadas à API.

Obtenha uma chave de acesso a API neste link [https://app.nfe.io/account/apikeys](https://app.nfe.io/account/apikeys)

#### ID da Empresa

> campo obrigatório

Informe o ID da empresa ao qual serão associadas as notas fiscais gerados pelo WHMCS.

Obtenha o ID da empresa neste link [https://app.nfe.io/companies/](https://app.nfe.io/account/apikeys)

#### Código do Serviço Principal

> campo obrigatório

Código de serviço que será usado como padrão para geração das notas fiscais pelo WHMCS. Este código irá variar de acordo com a categoria de tributação do negócio no município.

Saiba mais sobre o código de serviço neste link [https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/#o-que-e-codigo-de-servico](https://app.nfe.io/account/apikeys)

#### Ambiente de desenvolvimento

Emite as notas em modo "depuragem" sem valor real no lado da NFE.io, **ative apenas em caso de necessidade ou homologação**.

#### Debug

Marque essa opção para salvar informações de diagnóstico no Log de Módulo do WHMCS, **ative apenas em caso de necessidade**.

#### Controle de Acesso

Escolha os grupos de administradores ou  operadores que terão para acessar o módulo.

> **dica:** informe todos os grupos de operadores que precisem acessar e operar o módulo.

## Configuração

Após a instalação e configuração inicial do addon como chave de API e código da empresa, é necessário realizar as configurações avançadas e rotinas de emissão das notas fiscais. Para isso acesse `Addons -> NFE.io NFSe -> Configurações`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-05.png)

As configurações disponíveis estão descritas a seguir.

### API Key

Chave de acesso privada gerado na sua conta NFE.io, necessária para a autenticação das chamadas à API.

> Configurado na etapa de instalação do módulo.

### ID da Empresa

ID da empresa ao qual serão associadas as notas fiscais gerados pelo WHMCS.

> Configurado na etapa de instalação do módulo.

### Código de Serviço Principal

Código de serviço que será usado como padrão para geração das notas fiscais pelo WHMCS.

> Configurado na etapa de instalação do módulo.

### RPS

Campo legado RPS.

### Disparar e-mail com a nota

Habilita a opção de envio da nota fiscal por e-mail ao cliente.

> O e-mail será enviado para o endereço principal cadastrado no perfil do cliente diretamente pela NFE.io.

### Quando emitir NFE

Configuração global para emissão das nots ficais pelo WHMCS, as opções disponíveis são.

#### Quando a fatura é gerada

A NFSe será emitida assim que uma fatura seja publicada, ou seja, esteja disponível para o cliente.

#### Quando a fatura é paga

A NFSe será emitida apenas quando a fatura registrar um pagamento. Esse pagamento poderá ser registrado por qualquer portal de pagamento dentro do fluxo transacional padrão do WHMCS ou manualmente ao adicionar um pagamento em uma fatura.

### Agendar Emissão

Número de dias após o pagamento da fatura que as notas devem ser emitidas. Informe quantos dias após o registro do pagamento em uma fatura a NFSe será emitida.

**Atenção:** agendar emissão de notas desativa a configuração **Quando emitir NFE**.

### Cancelar NFE Quando Cancelar Fatura

Marque esta opção para cancelar automaticamente uma nota quando a fatura associada é cancelada.

### Inscrição Municipal

Selecione o campo personalizado criado anteriormente que será responsável por registrar o número de inscrição municipal do cliente.

### Campo Personalizado CPF

Selecione o campo personalizado criado anteriormente que será responsável pelo CPF do cliente. Este campo poderá ser o mesmo para CPF e CNPJ.

### Campo Personalizado CNPJ

Selecione o campo personalizado criado anteriormente que será responsável pelo CNPJ do cliente. Selecione o mesmo campo personalizado do CPF caso seja um campo único para ambos os números de documento (CPF/CNPJ).

### Aplicar Impostos em todos os produtos

Esta opção define que todos os serviços terão impostos aplicados, caso contrário a aplicação de imposto é selecionada de forma individual por serviço.

### Descrição da NFSe

Selecione a informação que será exibida no campo de descrição da nota fiscal.

#### Número da fatura

Exibe apenas o número da fatura vinculada a NFSe.

#### Nome dos serviços

Exibe o nome de todos os serviços vinculados a fatura.

#### Número da fatura + Nome dos Serviços

Exibe o número da fatura em uma linha e o nome de todos os serviços vinculados a fatura em outra linha.

### Exibir Link da Fatura na NFSe

Exibe o link da fatura juntamente com a descrição da NFSe na mensagem da nota.

### Descrição Adicional

Campo livre para informação adicional que será exibida no campo mensagem da nota fiscal.

### Configurações dos Produtos/Serviços

Os produtos podem ter configurações de código de serviço individuais. É possível definir os códigos de serviços personalizado por produto em `Addons -> NFE.io NFSe -> Código de Serviço`

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-02.png)

Para definir um código de serviço personalizado, localize o produto/serviço desejado e no campo `Código do Serviço` informe o código de serviço desejado, em seguida clique no botão `Salvar Código`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-03.png)

> **Dica:** para alterar um código basta alterar o desejado e clicar no botão `Salvar Código` referente.

Para excluir um código personalizado de um produto, e voltar a utilizar a configuração global, localize o produto desejado e clique no botão `Excluir Código`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-04.png)

> **Dica:** use o campo `Pesquisar` localizado no canto superior da tabela para pesquisar os produtos desejados pelo nome ou ID.

### Emissão Personalizada por cliente

É possível definir uma **opção de emissão personalizada por cliente**, esta opção de emissão sobrescreve a configuração global de emissão configurada.

Para inserir uma opção personalizada de emissão, acesse o perfil do cliente desejado e localize o campo `Emitir nota fiscal quando` e selecione uma das opções de emissão da lista, como exemplificado na imagem a seguir.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-01.png)

### Link da nota na fatura

Para inserir um link da nota fiscal do PDF e XML, edite o arquivo `viewinvoice.tpl` da pasta do template do WHMCS, utilize o exemplo abaixo:

```xhtml
{if $status eq "Paid" || $clientsdetails.userid eq "6429"}<i class="fal fa-file-invoice" aria-hidden="true"></i> NOTA FISCAL  <a href="/modules/addons/gofasnfeio/pdf.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">PDF</a> | <a href="/modules/addons/gofasnfeio/xml.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">XML</a>{/if}

```

## Atualização

Este documento irá mostrar como atualizar e migrar com sucesso o [Módulo Nota Fiscal para WHMCS via NFE.io](https://github.com/nfe/whmcs-addon) para a **versão 2.0**. Ela irá guiar passo a passo por todo o processo de atualização e migração necessários.

> Este documento visa auxiliar no processo de atualização do módulo da versão v1.4 para a versão v2.0

> **ATENÇÃO:** Sempre realize um backup por segurança, tanto do seu WHMCS quanto do seu banco e dados antes de realizar qualquer migração.

### Ativando as versões em paralelo

A versão 2.0 do módulo possui uma nova estrutura de diretórios, o que possibilita uma ativação em paralelo a versão anterior permitindo assim uma migração rápida e transparente. Ao ativar a nova versão em paralelo, o módulo irá buscar todas as informações da versão anterior e irá importa-las automaticamente.

Então é crucial para que o processo de atualização e migração ocorra adequadamente a **ativação em paralelo das duas versões do módulo**.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-atualizacao-01.png)

**Não desative** o módulo antigo **antes de concluir** a migração/atualização.

### Configuração

Ao ativar a nova versão, todas as configurações globais do módulo serão automaticamente migradas. Configurações como API Key e ID da empresa já poderão ser visíveis como exemplificado na imagem a seguir.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-atualizacao-02.png)

As configurações migradas automaticamente da versão anterior serão:

* API Key
* ID da Empresa
* Código de Serviço Principal
* Informações de depuragem (debug)
* RPS (legado)
* Disparar e-mail com a nota
* Quando emitir NFE
* Quando emitir NFE
* Cancelar NFE Quando Cancelar Fatura
* Informações do campo personalizado para Campo Inscrição Municipal
* Informações do campo personalizado para Campo Personalizado CPF
* Informações do campo personalizado para Campo Personalizado CNPJ
* Aplicar Impostos em todos os produtos
* Descrição da NFSe
* Exibir Link da Fatura na NFSe
* Descrição Adicional

As demais configurações migradas poderão ser verificadas acessando o módulo em `Addons -> NFE.io NFSe -> Configurações`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-atualizacao-03.png)

### Migrando as notas fiscais

Ao ativar o novo módulo, as informações das notas fiscais emitidas a partir da versão anterior serão migradas automaticamente.

Todas as notas existentes estarão visíveis ao acessar o módulo em  `Addons -> NFE.io NFSe`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-notas-fiscais.png)

### Migrando os códigos de serviços

Os códigos de serviços personalizados serão migrados automaticamente e poderão ser verificados acessando o módulo em `Addons -> NFE.io NFSe -> Códigos de Serviços`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-atualizacao-04.png)

### Migrando as definições dos usuários

As configurações personalizadas de emissão de notas para os clientes também será migrada e todas as rotinas existentes de emissão para seus clientes serão mantidas.

### Verificando tudo

Por precaução, **antes de desativar a versão antiga** do módulo, faça uma verificação completa. Verifique se as configurações migradas estão corretas, verifique se as notas fiscais estão sendo listadas adequadamente e se os códigos dos serviços configurados condizem com os existentes na configuração do módulo antigo.

Fazendo esta verificação antes de seguir com a desativação e exclusão do módulo antigo ajudará a evitar problemas que não poderão ser revertidos após as próximas etapas.

### Desativando a versão anterior (1.4)

Após conferir a configurações do módulo e as notas ficais, tudo parecendo certo, você poderá desativar o módulo.

Para desativar o módulo **NFE.io v1.4.x** vá para `Configurações -> Módulos Addons` no WHMCS v7.x ou `Opções -> Módulos Addons` no WHMCS v8.x.

Localize o módulo antigo, **verifique a versão que deve ser desativada**, você deverá desativar a versão ****v1.4.x**** (sendo x qualquer versão menor como 1.4.1, 1.4.4 etc). Veja a imagem a seguir.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-atualizacao-05.png)

### Excluindo o módulo anterior (v1.4)

Após desativar o módulo **NFE.io v1.4.x**, será necessário **remover o diretório** `gofasnfeio` existente dentro de `modules/addons` como última etapa da atualização para a versão 2.0.

Para isso, utilize seu cliente FTP preferido para acessar o WHMCS, navegue até o diretório `seu_whmcs/modules/addons` para visualizar os módulos adicionais existentes em seu WHMCS e localize o diretório nomeado `gofasnfeio` como demonstrado na imagem a seguir.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-atualizacao-06.png)

Após localizar o diretório, **exclua-o**.

Pronto! Seu módulo de emissão de notas fiscais no WHMCS via NFE.io está atualizado para a versão 2.0!

#### Tabelas do Banco de Dados

Este processo de atualização, por segurança, **não exclui ou manipula** as tabelas no banco de dados utilizado pela versão anterior. A versão 2.0 copia todas as informações para novas tabelas e mantém as originais intactas, e a desativação do módulo não aciona nenhuma ação de exclusão. Então **caso você tenha tido algum problema** e precise voltar o módulo para uma versão anterior a atualização, basta desativar a versão 2.0 e **reenviar os arquivos da versão originalmente em uso**.

Veja a lista a seguir das tabelas do banco de dados usadas pela versão anterior, caso você desejar fazer um backup manual ou exclui-las no futuro.

* `gofasnfeio`: todos os registros de notas fiscais já emitidas pelo módulo.
* `mod_nfeio_custom_configs`: contém todos os registros das configurações personalizadas de emissão de notas para os clientes.
* `tblproductcode`: possui todos os registros de códigos de serviços personalizados associados aos produtos/serviços.

> `tblproductcode` possui um nome muito similar as tabelas padrões do WHMCS, mas ela é uma tabela personalizada e nenhum componente ou função nativas do WHMCS dependem dela.

[nfeio]: https://nfe.io/
[manual-instalacao]: https://nfe.github.io/whmcs-addon/docs/instalacao
[manual-configuracao]: https://nfe.github.io/whmcs-addon/docs/configuracao
[manual-atualizacao]: https://nfe.github.io/whmcs-addon/docs/atualizacao