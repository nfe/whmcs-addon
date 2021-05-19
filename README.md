# Módulo Nota Fiscal para WHMCS via NFE.io

Automatize a emissão de notas fiscais no WHMCS com a [NFE.io](https://nfe.io "NFE.io")!

A [NFE.io](https://nfe.io "NFE.io") é um sistema de emissão de notas fiscais que automatiza a comunicação com as prefeituras. Com a [NFE.io](https://nfe.io "NFE.io") você se livra de diversas tarefas chatas, melhorando o desempenho do seu negócio. E melhor, você economiza tempo e dinheiro.

---

## TELAS DO MÓDULO

_Configurações_
Após instalar entre no Admin do WHMC e acess as configurações. Dentro das opções de configurações pesquise por: "Módulos Addon". //whmcsdomain/admin/configaddonmods.php
Procure pelo módulo NFE.io, Para conseguir configura-lo é necessário Ativar o módulo. Após a ativação do móudlo, o botão "Configure" ficará disponível, clique no botão para acessar as configurações do módulo. Para informações detalhada de como configurar cada campo veja infos no link: https://github.com/LinkNacional/whmcs-addon/tree/issues#configura%C3%A7%C3%B5es-do-m%C3%B3dulo

[![](http://whmcs.linknacional.com.br/prints/img1.png)](http://whmcs.linknacional.com.br/prints/img1.png)

_Listagem de notas fiscais_
O módulo conta com uma listagem de notas fiscais, para acessar a ferramenta, dentro do admin do WHMCS no menu superior passe o mouse na opção "Addons" e clique na opção: NFE.io, irá visualizar uma listagem da situação das notas fiscais.
Caso a opção não esteja disponível no menu, verifique as configurações do módulo a opção "Controle de Acesso" e veja se tem permissão para visualizar o recurso.

[![Listagem de notas fiscais](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_list_screenshot.png "Listagem de notas fiscais")](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_list_screenshot.png "Listagem de notas fiscais")

_Configurações de Códigos de serviços_
Dentro da listagem de nota fiscal, possui a opção de listar e cadastrar os códigos de serviços. Se algum dos serviços ofertados possuir código de serviço diferente do definido nas configurações, esse é o local para definição do código do serviço individualmente.
[![Listagem de notas fiscais](http://whmcs.linknacional.com.br/prints/img2.png "Listagem de notas fiscais")](http://whmcs.linknacional.com.br/prints/img2.png "Listagem de notas fiscais")

_Visualização de Fatura via admin_
Dentro do admin do WHMCS é possível gerenciar a nota fiscal manualmente.
[![Ações na edição da fatura](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_invoice_screenshot.png "Ações na edição da fatura")](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_invoice_screenshot.png "Ações na edição da fatura")

## PRINCIPAIS FUNCIONALIDADES

✓ Emite notas fiscais automaticamente, quando a fatura é publicada, ou quando a fatura é paga.

✓ Emite notas fiscais manualmente.

✓ Permite agendar a emissão de notas fiscais para um determinado número de dias após a confirmação dos pagamentos.

✓ Emite notas fiscais de forma sequencial, evitando sobrecargas nos sites das prefeituras.

✓ Exibe o status da NFE e adiciona botões de ações relacionadas às notas na página de edição das faturas.

✓ Cancela a nota fiscal quando a fatura é cancelada (opcional).

✓ Exibe nas configurações do módulo quando há uma versão mais recente disponível para download.

✓ Opcionalmente, salva o debug das chamadas à API NFE.io no log de Módulo do WHMCS para diagnóstico e aprendizado.

✓ Opcionalmente, seleciona nas configurações do módulo a opção de enviar o número inscrição municipal para a nota fiscal.

✓ Opcionalmente, seleciona nas configurações do módulo a opção de enviar a nota fiscal por e-mail automaticamente.

## REQUISITOS DO SISTEMA

- WHMCS versão 7.2.1 ou superior;
- PHP 5.6 ou superior
- Tarefas cron do WHMCS devem estar funcionando a cada 5 minutos, conforme descrito na documentação oficial (https://docs.whmcs.com/Crons);
- É necessário um portal de pagamento ativado e que a criação de faturas do WHMCS esteja funcional, sendo que as notas fiscais são emitidas no momento da criação ou após o pagamento das faturas geradas manual ou automaticamente pelo WHMCS.

## INSTALAÇÃO

1. Faça download do módulo [neste link](https://github.com/nfe/whmcs-addon/archive/master.zip "neste link");
2. Descompacte o arquivo .zip;
3. Copie o diretório `/gofasnfeio/`, localizados na pasta `/modules/addons/` do arquivo recém descompactado, para a pasta `/modules/addons/` da instalação do WHMCS;

## ATUALIZAÇÃO

1. Faça download do módulo [neste link](https://github.com/nfe/whmcs-addon/archive/master.zip "neste link");
2. Descompacte o arquivo .zip;
3. Dentro da instalação do seu WHMCS remova a pasta `/modules/addons/gofasnfeio/`;
4. Copie o diretório `/gofasnfeio/`, localizados na pasta `/modules/addons/` do arquivo recém descompactado, para a pasta `/modules/addons/` da instalação do WHMCS;

## PRÉ CONFIGURAÇÃO E ATIVAÇÃO

1. No painel administrativo do WHMCS, crie um campo personalizado de cliente para CPF e/ou CNPJ. Caso prefira, você pode criar dois campos distintos, sendo um campo apenas para CPF e outro campo apenas para CNPJ. O módulo identifica os campos do perfil do cliente automaticamente;
2. Ative o addon no painel administrativo do WHMCS, em Opções > Módulos Addon > Gofas NFE.io > Ativar.

## CONFIGURAÇÕES DO MÓDULO

1. API Key: (Obrigatório) Chave de acesso privada gerado na sua conta NFE.io, necessária para a autenticação das chamadas à API (Obter Api Key);
2. ID da Empresa: (Obrigatório) Nesse campo você deve indicar o ID da empresa ao qual serão associadas as notas fiscais geradas pelo WHMCS. (Obter ID da empresa);
3. Código de Serviço Principal: (Obrigatório) O código de serviço varia de acordo com a categoria de tributação do negócio. Saiba mais sobre o código de serviço aqui;
4. Série do RPS: Valor padrão `IO`. Saiba mais em https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/;
5. Número do RPS: O número RPS da NFE mais recente gerada. Deixe em branco e o módulo irá preencher esse campo após a primeira emissão. Não altere o valor a menos que tenha certeza de como funciona essa opção. Saiba mais em https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/;
6. Quando emitir NFE: Selecione se deseja que as notas fiscais sejam geradas quando a fatura é publicada ou quando a fatura é paga;
7. Agendar Emissão: Número de dias após o pagamento da fatura que as notas devem ser emitidas. Preencher essa opção desativa a opção anterior;
8. Cancelar NFE: Se essa opção está ativada, o módulo cancela a nota fiscal quando a fatura cancelada;
9. Debug: Marque essa opção para salvar informações de diagnóstico no Log de Módulo do WHMCS;

10. Inscrição Municipal: Marque o campo personalizado definido para ser a Inscrição Municipal.
11. Aplicar imposto automaticamente em todos os produtos: Esta opção define que todos os serviços terão impostos aplicados, caso contrário a aplicação de imposto é selecionada de forma individual por serviço.
12. O que deve aparecer nos detalhes da fatura?: Define o que vai aparecer nos detalhes das NFE's emitidas.
13. Controle de Acesso: Escolha os grupos de administradores ou operadores que terão permissão para acessar a lista de faturas gerada pelo módulo no menu Addons > Gofas NFE.io.

## CONFIGURAÇÕES DOS PRODUTOS E SERVIÇOS

Os produtos podem ter configurações de código de serviço individuais:

Em Addons>NFE.oi>código dos Produtos é possivel configurar um código de serviço para cada produto/serviço cadastrado.

**_o código individual vai ter prioridade sobre o definido nas configurações do módulo._**

E também há configurações de aplicação do imposto:

Nas configurações do módulo como foi explicado anteriormente, há a opção de aplicar imposto automaticamente em todos os produtos, onde se marcados sim, todos os produtos/serviços cadastrados vão ser marcados para aplicar os impostos.

se desejar fazer essas configurações individualmente pode entrar em configurações>Produtos/Serviços e escolher o produto para configurar e marcar a caixa Aplicar Imposto.

## LINK DA NOTA EM PDF E XML

Para inserir um link da nota fiscal do PDF e XML, edite o arquivo viewinvoice.tpl da pasta do template do WHMCS, utilize o exemplo abaixo:

```
{if $status eq "Paid" || $clientsdetails.userid eq "6429"}<i class="fal fa-file-invoice" aria-hidden="true"></i> NOTA FISCAL  <a href="/modules/addons/gofasnfeio/pdf.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">PDF</a> | <a href="/modules/addons/gofasnfeio/xml.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">XML</a>{/if}
```

## EMISSÃO PERSONALIZADA DE NOTAS PARA CLIENTE

Para inserir uma opção personalizada de quando é emitido a NFE para cada cliente crie um campo personalizado em `Configurações > Campos Personalizados dos Clientes` com o nome `Emitir Nota Fiscal`,Tipos de campo `Lista de Opção` e em Selecionar Opções `nenhum (padrão do WHMCS) deve seguir a configuração do modulo.,Quando a Fatura é Gerada,Quando a Fatura é Paga`,como no exemplo:
[![](http://whmcs.linknacional.com.br/prints/campo_personalizado.png)](http://whmcs.linknacional.com.br/prints/campo_personalizado.png)

## CHANGELOG

#### IMPORTANTE: Ao atualizar, após substituir os arquivos pelos mais recentes, acesse as configurações do módulo no menu `Opções > Módulos Addon > Gofas NFE.io` do painel administrativo do WHMCS e clique em "Salvar Alterações". Isso garente que os novos parâmetros serão gravados corretamente no banco de dados.

### v1.3.2

- Ajuste para correção da emissão automática de notas quando pagas.

### v1.3.1

- ajuste para correção de retorno de callback.

### v1.3.0

- link para relatório do sistema legado
- botão para cancelar nota fiscal
- log, data e hora da emissão do log
- verificação de conexão com nfe
- verificação automática de campo RPS
- verificação de campo personalizado
- campo personalizado no cliente para emissão da nota

### v1.2.10

- correção enviar endereço de e-mail na nota

### v1.2.9

- criação de arquivo de debug
- verificação do retorno CEP
- validação de versão do modulo via github
- impedir emissão duplicada de nota fiscal de fatura

### v1.2.7

- envio do nome da empresa ao invés do nome pessoa física quando o CNPJ estiver definido
- criar nota fiscal de acordo com o código de serviço de cada serviço
- corrigido erro de caracteres especiais
- opção de criar nota individualmente por tipo de serviço
- emissão de nota fiscal a partir da data de instalação do módulo
- opção de descrição do serviço na nota: referente a fatura ou nome do serviço.
- ajuste de link das notas fiscais na fatura para abrir todas as notas.
- ajuste de instalação do módulo

### v1.2.6

- opção manual para criação de notas fiscais.

### v1.2.5

- criação de link na fatura para o XML da nota fiscal.

### v1.2.4

- Nova opção de configuração no disparo de nota fiscal automatica por e-mail.
- Ajustes com informações e links de suporte.

### v1.2.3

- Ajustes Garante que a nota não sera duplicada, criação de link da nota fiscal, opção de inscrição municipal.

### v1.2.2

- Garante que o rpsSeraiNumber não seja alterado quando já configurado manualmente.

#### v1.2.1

- Corrigido erro que alterava a série do RPS nas configurações de acordo com a série RPS das NFEs já geradas.

#### v1.2.0

- Novo campo nas configurações para informar a Série do RPS (RPS Serial Number). Será preenchido automaticamente na próxima emissão, caso não preenchido;
- Novo campo nas configurações para informar o número RPS (RPS Number). Caso não preenchido, será preenchido automaticamente na próxima emissão, após consultar a NFE mais recente gerada. Não sendo gerado ou configurado nenhum número RPS, o módulo irá configurar automaticamente com "1" o valor desse campo;

#### v1.1.3

- Agora o número RPS é obtido consultando a NFE mais recente gerada;

#### v1.1.2

- Melhoria na verificação de atualizações;

#### v1.1.1

- Obtém via API o rpsSerialNumber e rpsNumber da empresa antes de gerar cada nota fiscal;
- O rpsNumber da nova NFE a ser gerada sempre é "último rpsNumber + 1".

#### v1.0.1

- Corrigido bug ao salvar NFE no banco de dados na criação da fatura.

#### v1.0.0

- Lançamento.

© 2021 [Manutenção Link Nacional](https://www.linknacional.com.br/suporte-whmcs)
