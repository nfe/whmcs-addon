# Módulo Nota Fiscal para WHMCS via NFE.io
Automatize a emissão de notas fiscais no WHMCS com a [NFE.io](https://nfe.io "NFE.io")!

A [NFE.io](https://nfe.io "NFE.io") é um sistema de emissão de notas fiscais que automatiza a comunicação com as prefeituras. Com a [NFE.io](https://nfe.io "NFE.io") você se livra de diversas tarefas chatas, melhorando o desempenho do seu negócio. E melhor, você economiza tempo e dinheiro.

------------
## CAPTURAS DE TELA
Clique nas imagens para ampliar

[![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/08/26153535/config_screenshot.png)](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/08/26153535/config_screenshot.png)
*Configurações*

[![Listagem de notas fiscais](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_list_screenshot.png "Listagem de notas fiscais")](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_list_screenshot.png "Listagem de notas fiscais")
*Listagem de notas fiscais*

[![Ações na edição da fatura](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_invoice_screenshot.png "Ações na edição da fatura")](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/nfe_invoice_screenshot.png "Ações na edição da fatura")
*Ações na edição da fatura*

## PRINCIPAIS FUNCIONALIDADES
✓ Emite notas fiscais automaticamente, quando a fatura é publicada, ou quando a fatura é paga.

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

## PRÉ CONFIGURAÇÃO E ATIVAÇÃO
1. No painel administrativo do WHMCS, crie um campo personalizado de cliente para CPF e/ou CNPJ. Caso prefira, você pode criar dois campos distintos, sendo um campo apenas para CPF e outro campo apenas para CNPJ. O módulo identifica os campos do perfil do cliente automaticamente;
2. Ative o addon no painel administrativo do WHMCS, em Opções > Módulos Addon > Gofas NFE.io > Ativar.

## CONFIGURAÇÕES DO MÓDULO
1. API Key: (Obrigatório) Chave de acesso privada gerado na sua conta NFE.io, necessária para a autenticação das chamadas à API (Obter Api Key);
2. ID da Empresa: (Obrigatório) Nesse campo você deve indicar o ID da empresa ao qual serão associadas as notas fiscais geradas pelo WHMCS. (Obter ID da empresa);
3. Código de Serviço: (Obrigatório) O código de serviço varia de acordo com a categoria de tributação do negócio. Saiba mais sobre o código de serviço aqui;
4. Série do RPS: Valor padrão `IO`. Saiba mais em https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/;
5. Número do RPS: O número RPS da NFE mais recente gerada. Deixe em branco e o módulo irá preencher esse campo após a primeira emissão. Não altere o valor a menos que tenha certeza de como funciona essa opção. Saiba mais em https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/;
6. Quando emitir NFE: Selecione se deseja que as notas fiscais sejam geradas quando a fatura é publicada ou quando a fatura é paga;
7. Agendar Emissão: Número de dias após o pagamento da fatura que as notas devem ser emitidas. Preencher essa opção desativa a opção anterior;
8. Cancelar NFE: Se essa opção está ativada, o módulo cancela a nota fiscal quando a fatura cancelada;
9. Debug: Marque essa opção para salvar informações de diagnóstico no Log de Módulo do WHMCS;
10. Controle de Acesso: Escolha os grupos de administradores ou operadores que terão permissão para acessar a lista de faturas gerada pelo módulo no menu Addons > Gofas NFE.io.

## LINK DA NOTA EM PDF E XML
Para inserir um link da nota fiscal do PDF e XML direto na fatura do template do WHMCS, utilize o exemplo abaixo:
```
{if $status eq "Paid" || $clientsdetails.userid eq "6429"}<i class="fal fa-file-invoice" aria-hidden="true"></i> NOTA FISCAL  <a href="/modules/addons/gofasnfeio/pdf.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">PDF</a> | <a href="/modules/addons/gofasnfeio/xml.php?invoice_id={$invoiceid}" target="_blank" class="btn btn-link" tite="Nota Fiscal disponível 24 horas após confirmação de pagamento.">XML</a>{/if}
```

## CHANGELOG
#### IMPORTANTE: Ao atualizar, após substituir os arquivos pelos mais recentes, acesse as configurações do módulo no menu `Opções > Módulos Addon > Gofas NFE.io` do painel administrativo do WHMCS e clique em "Salvar Alterações". Isso garente que os novos parâmetros serão gravados corretamente no banco de dados.
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

© 2020 [Gofas Software](https://gofas.net/whmcs/modulo-nfe-io-para-whmcs/)
