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

1. WHMCS versão 7.2 ou superior;
2. PHP 5.6 ou superior;
3. Chave de API da NFE.io;
4. Automação do WHMCS devidamente configurada ([https://docs.whmcs.com/Automation_Settings](https://docs.whmcs.com/Automation_Settings));
5. Tarefas cron do Sistema sendo executadas conforme recomendações do WHMCS [https://docs.whmcs.com/Crons#System_Cron](https://docs.whmcs.com/Crons#System_Cron).

## Instalação

Para instalar o módulo no WHMCS realize os seguintes passos.

### Baixar o módulo

Faça o download arquivo zip da última versão módulo neste link [https://github.com/nfe/whmcs-addon/releases/latest](https://github.com/nfe/whmcs-addon/releases/latest)

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

## Configurações dos Produtos/Serviços

Os produtos podem ter configurações de código de serviço individuais. É possível definir os códigos de serviços personalizado por produto em `Addons -> NFE.io NFSe -> Código de Serviço`

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-02.png)

Para definir um código de serviço personalizado, localize o produto/serviço desejado e no campo `Código do Serviço` informe o código de serviço desejado, em seguida clique no botão `Salvar Código`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-03.png)

> **Dica:** para alterar um código basta alterar o desejado e clicar no botão `Salvar Código` referente.

Para excluir um código personalizado de um produto, e voltar a utilizar a configuração global, localize o produto desejado e clique no botão `Excluir Código`.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-04.png)

> **Dica:** use o campo `Pesquisar` localizado no canto superior da tabela para pesquisar os produtos desejados pelo nome ou ID.

## Emissão Personalizada por cliente

É possível definir uma **opção de emissão personalizada por cliente**, esta opção de emissão sobrescreve a configuração global de emissão configurada.

Para inserir uma opção personalizada de emissão, acesse o perfil do cliente desejado e localize o campo `Emitir nota fiscal quando` e selecione uma das opções de emissão da lista, como exemplificado na imagem a seguir.

![](https://nfe.github.io/whmcs-addon/assets/img/nfeio-whmcs-docs-configuracao-01.png)

## Link da nota na fatura

Para inserir um link da nota fiscal do PDF e XML, edite o arquivo `viewinvoice.tpl` da pasta do template do WHMCS, utilize o exemplo abaixo:

```xhtml
{if $status eq "Paid" || $clientsdetails.userid eq "6429"}<i class="fal fa-file-invoice" aria-hidden="true"></i> NOTA FISCAL  <a href="/modules/addons/gofasnfeio/pdf.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">PDF</a> | <a href="/modules/addons/gofasnfeio/xml.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">XML</a>{/if}

```

[nfeio]: https://nfe.io/
[manual-instalacao]: https://nfe.github.io/whmcs-addon/docs/instalacao
[manual-configuracao]: https://nfe.github.io/whmcs-addon/docs/configuracao
[manual-atualizacao]: https://nfe.github.io/whmcs-addon/docs/atualizacao