
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

Este documento irá mostrar como instalar com sucesso o [Módulo Nota Fiscal para WHMCS via NFE.io](https://github.com/nfe/whmcs-addon). Ela irá guiar passo a passo por todo o processo de instalação.

* [Instalação][manual-instalacao]
* [Configuração][manual-configuracao]
* [Atualização][manual-atualizacao] (v1.4 para 2.0)


[nfeio]: https://nfe.io/
[manual-instalacao]: https://nfe.github.io/whmcs-addon/docs/instalacao
[manual-configuracao]: https://nfe.github.io/whmcs-addon/docs/configuracao
[manual-atualizacao]: https://nfe.github.io/whmcs-addon/docs/atualizacao