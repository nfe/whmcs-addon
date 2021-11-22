
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

## Como usar este Módulo

### Requisitos

- WHMCS versão 7.2 ou superior
- PHP 5.6 ou superior
- Tarefas cron do WHMCS devem estar funcionando a cada 5 minutos, conforme descrito na documentação oficial (https://docs.whmcs.com/Crons);
- É necessário um portal de pagamento ativado e que a criação de faturas do WHMCS esteja funcional, sendo que as notas fiscais são emitidas no momento da criação ou após o pagamento das faturas geradas manual ou automaticamente pelo WHMCS.

### Documentação

O módulo de emissão de notas fiscais para WHMCS via [NFE.io][nfeio] é simples de instalar como qualquer módulo adicional e possui uma documentação bem organizada para auxiliar o processo de instalação e configuração para você começar a emitir notas fiscais automaticamente em seu WHMCS!

* [Instalação][manual-instalacao]
* [Configuração][manual-configuracao]
* [Atualização][manual-atualizacao] (v1.4 para 2.0)


[nfeio]: https://nfe.io/
[manual-instalacao]: https://nfe.github.io/whmcs-addon/docs/instalacao
[manual-configuracao]: https://nfe.github.io/whmcs-addon/docs/configuracao
[manual-atualizacao]: https://nfe.github.io/whmcs-addon/docs/atualizacao