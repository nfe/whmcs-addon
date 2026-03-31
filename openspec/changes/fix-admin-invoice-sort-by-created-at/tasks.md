## 1. Server-side

- [x] 1.1 Em `Repository::dataTable()`, trocar `->orderBy("{$this->tableName}.id", 'desc')` por `->orderBy("{$this->tableName}.created_at", 'desc')`

## 2. Template — ordenação interativa

- [x] 2.1 Na coluna "Gerado em" do template `index.tpl`, adicionar o atributo `data-order="{$nota->created_at}"` no elemento `<td>` correspondente
- [x] 2.2 Na inicialização do DataTables em `index.tpl`, trocar `order: [[0, "desc"]]` por `order: [[6, "desc"]]`

## 3. Validação

- [x] 3.1 Executar `php -l` nos arquivos PHP alterados para verificar sintaxe
- [ ] 3.2 Verificar manualmente no painel admin que a listagem exibe a nota mais recente no topo por padrão
- [ ] 3.3 Verificar que clicar no cabeçalho "Gerado em" ordena corretamente por data/hora
