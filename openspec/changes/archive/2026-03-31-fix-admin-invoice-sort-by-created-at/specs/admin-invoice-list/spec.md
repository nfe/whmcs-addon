## ADDED Requirements

### Requirement: Ordenação padrão por data de criação
A listagem de notas fiscais no painel admin SHALL exibir os registros ordenados por `created_at` de forma decrescente (nota mais recente primeiro) como comportamento padrão, tanto no retorno da query SQL quanto na inicialização da tabela no frontend.

#### Scenario: Acesso inicial à listagem
- **WHEN** o administrador acessa `addonmodules.php?module=NFEioServiceInvoices`
- **THEN** a tabela de notas fiscais SHALL exibir a nota com `created_at` mais recente na primeira linha

#### Scenario: Notas sem JavaScript
- **WHEN** a página é carregada sem JavaScript habilitado
- **THEN** a tabela SHALL ainda exibir os registros em ordem decrescente de `created_at`, pois a query do servidor já retorna nessa ordem

---

### Requirement: Ordenação interativa correta por data de criação
A coluna "Gerado em" da tabela de notas fiscais SHALL ser ordenável corretamente pelo DataTables ao clicar no cabeçalho da coluna, respeitando a ordem cronológica real independentemente do formato de exibição da data.

#### Scenario: Clique em "Gerado em" para ordenar crescente
- **WHEN** o administrador clica no cabeçalho "Gerado em" uma vez
- **THEN** a tabela SHALL reordenar exibindo a nota com `created_at` mais antiga na primeira linha

#### Scenario: Clique em "Gerado em" para ordenar decrescente
- **WHEN** o administrador clica no cabeçalho "Gerado em" duas vezes
- **THEN** a tabela SHALL reordenar exibindo a nota com `created_at` mais recente na primeira linha

#### Scenario: Datas com mesmo dia mas horários distintos
- **WHEN** existem notas criadas no mesmo dia mas em horários diferentes
- **THEN** a ordenação SHALL respeitar o horário (`HH:MM:SS`), não apenas a data
