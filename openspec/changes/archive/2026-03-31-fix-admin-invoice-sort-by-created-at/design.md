## Context

A listagem de notas fiscais no painel admin (`addonmodules.php?module=NFEioServiceInvoices`) carrega todos os registros via `Repository::dataTable()` e os exibe em uma tabela HTML controlada pelo DataTables (client-side). Atualmente:

- O server-side ordena por `id DESC`, que reflete ordem de inserção, não de criação da nota
- O DataTables inicializa com `order: [[0, "desc"]]`, aplicando sort pela coluna "Fatura" (invoice_id)
- A coluna "Gerado em" exibe datas no formato `dd/mm/yyyy` — que não é ordenável lexicograficamente, então o clique nela produz resultado incorreto

O campo `created_at` já existe na tabela `mod_nfeio_si_serviceinvoices` e já é selecionado pelo `dataTable()`. O `<abbr>` já expõe o timestamp ISO no atributo `title`, mas o DataTables não o utiliza para ordenação.

## Goals / Non-Goals

**Goals:**
- Ordenação padrão da tabela por `created_at DESC` (nota mais recente primeiro)
- Clique na coluna "Gerado em" ordenar corretamente por data/hora

**Non-Goals:**
- Não mudar para DataTables server-side (AJAX)
- Não alterar paginação ou filtros existentes
- Não modificar nenhum outro comportamento da listagem

## Decisions

### 1. Ordenar por `created_at` no server-side também

**Decisão**: trocar `orderBy("{$tableName}.id", 'desc')` por `orderBy("{$tableName}.created_at", 'desc')` em `Repository::dataTable()`.

**Racional**: Mantém consistência entre a ordem retornada pelo banco e a ordem exibida pelo DataTables. Em cenários com JavaScript desabilitado (ou falha de carregamento), a tabela já exibiria a ordem correta.

**Alternativa descartada**: manter `orderBy id` no banco e apenas corrigir o DataTables. Funcionaria visualmente, mas seria incoerente do servidor.

---

### 2. Usar `data-order` no `<td>` para ordenação correta no DataTables

**Decisão**: adicionar `data-order="{$nota->created_at}"` no elemento `<td>` da coluna "Gerado em".

**Racional**: O DataTables suporta nativamente o atributo `data-order` em células, usando seu valor como chave de ordenação em vez do conteúdo visível. O valor ISO `YYYY-MM-DD HH:MM:SS` é ordenável lexicograficamente, resolvendo o bug com datas em `dd/mm/yyyy`.

**Alternativa descartada**: usar o plugin `datetime` do DataTables para parse de datas. Adicionaria dependência externa desnecessária quando `data-order` resolve o problema sem custo.

---

### 3. Referenciar a coluna "Gerado em" pelo índice 6

**Decisão**: trocar `order: [[0, "desc"]]` por `order: [[6, "desc"]]` na inicialização do DataTables.

**Racional**: A coluna "Gerado em" está na posição 6 (0-indexed) na tabela atual. Esta é a abordagem mais simples e compatível com a versão do DataTables já em uso (2.2.2).

**Risco conhecido**: índice hardcoded — ver seção de Riscos.

## Risks / Trade-offs

**[Risco] Índice hardcoded da coluna** → Se uma nova coluna for inserida antes de "Gerado em", o índice `6` ficará errado e a ordenação padrão quebrará silenciosamente.
*Mitigação*: documentar no comentário do template; considerar `name`-based ordering em refatoração futura.

**[Risco] `created_at` nulo em registros antigos** → Registros inseridos antes da coluna `created_at` existir podem ter valor `NULL`, aparecendo no topo ou final dependendo do banco.
*Mitigação*: sem ação necessária — o `DataTables` trata `NULL` como string vazia, posicionando-os ao final; aceitável.
