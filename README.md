[![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/07/14192856/Captura-de-Tela-2020-07-14-a%CC%80s-19.28.30.png)](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/07/14192856/Captura-de-Tela-2020-07-14-a%CC%80s-19.28.30.png)

# Módulo Gofas NFE.io para WHMCS
Automatize a emissão de notas fiscais com [WHMCS](https://goo.gl/gDXngY "WHMCS") e [NFE.io](https://nfe.io "NFE.io")!
A [NFE.io](https://nfe.io "NFE.io") é um sistema de emissão de nota fiscal que automatiza tarefas chatas e ainda te ajuda a ganhar performance, tempo e dinheiro. Foque no seu negócio, deixe toda burocracia de comunicação com as prefeituras com eles, especialistas em descomplicar.

------------
## CAPTURAS DE TELA
Clique nas imagens para ampliar

[![](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/config_screenshot-1.png)](https://s3.amazonaws.com/uploads.gofas.me/wp-content/uploads/2020/05/config_screenshot-1.png)
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

## REQUISITOS DO SISTEMA
- WHMCS versão 7.2.1 ou superior;
- PHP 5.6 ou superior
- Tarefas cron do WHMCS devem estar funcionando a cada 5 minutos, conforme descrito na documentação oficial (https://docs.whmcs.com/Crons);
- É necessário um portal de pagamento ativado e que a criação de faturas do WHMCS esteja funcional, sendo que as notas fiscais são emitidas no momento da criação ou após o pagamento das faturas geradas manual ou automaticamente pelo WHMCS.

## INSTALAÇÃO
1. Faça download do módulo [neste link](https://github.com/nfe/plugin-whmcs/archive/master.zip "neste link");
2. Descompacte o arquivo .zip;
3. Copie o diretório `/gofasnfeio/`, localizados na pasta `/modules/addons/` do arquivo recém descompactado, para a pasta `/modules/addons/` da instalação do WHMCS;

## PRÉ CONFIGURAÇÃO E ATIVAÇÃO
1. No painel administrativo do WHMCS, crie um campo personalizado de cliente para CPF e/ou CNPJ. Caso prefira, você pode criar dois campos distintos, sendo um campo apenas para CPF e outro campo apenas para CNPJ. O módulo identifica os campos do perfil do cliente automaticamente;
2. Ative o addon no painel administrativo do WHMCS, em Opções > Módulos Addon > Gofas NFE.io > Ativar.

## CONFIGURAÇÕES DO MÓDULO
1. API Key: (Obrigatório) Chave de acesso privada gerado na sua conta NFE.io, necessária para a autenticação das chamadas à API (Obter Api Key);
2. ID da Empresa: (Obrigatório) Nesse campo você deve indicar o ID da empresa ao qual serão associadas as notas fiscais geradas pelo WHMCS. (Obter ID da empresa);
3. Código de Serviço: (Obrigatório) O código de serviço varia de acordo com a categoria de tributação do negócio. Saiba mais sobre o código de serviço aqui;
4. Quando emitir NFE: Selecione se deseja que as notas fiscais sejam geradas quando a fatura é publicada ou quando a fatura é paga;
5. Agendar Emissão: Número de dias após o pagamento da fatura que as notas devem ser emitidas. Preencher essa opção desativa a opção anterior;
6. Cancelar NFE: Se essa opção está ativada, o módulo cancela a nota fiscal quando a fatura cancelada;
7. Debug: Marque essa opção para salvar informações de diagnóstico no Log de Módulo do WHMCS;
8. Controle de Acesso: Escolha os grupos de administradores ou operadores que terão permissão para acessar a lista de faturas gerada pelo módulo no menu Addons > Gofas NFE.io.

© 2020 [Gofas Software](https://gofas.net/whmcs/modulo-nfe-io-para-whmcs/)
