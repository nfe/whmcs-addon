## Why

A listagem de notas fiscais no painel administrativo (`addonmodules.php?module=NFEioServiceInvoices`) está ordenada por ID interno ao invés de data de criação da nota, dificultando a visualização das últimas emissões e tentativas com seus respectivos status. Além disso, o clique na coluna "Gerado em" não ordena corretamente porque a data é exibida em formato `dd/mm/yyyy`, que não é ordenável lexicograficamente.

Referência: [issue #142](https://github.com/nfe/whmcs-addon/issues/142)

## What Changes

- A ordenação padrão da tabela de notas fiscais passa a ser por `created_at DESC` (data de criação, mais recente primeiro), tanto no server-side (query SQL) quanto no client-side (configuração do DataTables)
- A coluna "Gerado em" passa a expor o valor ISO (`YYYY-MM-DD HH:MM:SS`) no atributo `data-order` do `<td>`, permitindo que o DataTables ordene corretamente por data ao clicar no cabeçalho

## Capabilities

### New Capabilities

*(nenhuma — esta change é exclusivamente um fix de comportamento existente)*

### Modified Capabilities

- `admin-invoice-list`: comportamento de ordenação padrão e interativa da listagem de notas fiscais no admin

## Impact

- `modules/addons/NFEioServiceInvoices/lib/Models/ServiceInvoices/Repository.php` — método `dataTable()`: trocar `orderBy id DESC` por `orderBy created_at DESC`
- `modules/addons/NFEioServiceInvoices/lib/templates/admin/index.tpl` — coluna "Gerado em": adicionar `data-order="{$nota->created_at}"` no `<td>`
- `modules/addons/NFEioServiceInvoices/lib/templates/admin/index.tpl` — inicialização do DataTables: trocar `order: [[0, "desc"]]` por `order: [[6, "desc"]]`
- Sem mudança de schema, sem migração de dados, sem impacto em outras telas ou hooks
