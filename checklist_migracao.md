# Checklist de Migração (dev)

Checklist com os itens primordiais a serem implementados e verificados para permitir uma migração segura e sem atritos entre a versão anterior a 2.0 (antiga estrutura).

## Check

* [ ] webhooks: webhooks atuais devem apontar para novo endereço
* [x] migrar configurações: migrar as configurações atuais do antigo módulo para o novo e evitar configuração
* [ ] migrar notas: migrar os dados atuais das notas da tabela _gofasnfeio_ para _mod_nfeio_si_serviceinvoices_
* [ ] migrar produtos: migrar os registros dos códigos de serviços personalizado dos produtos da tabela _tblproductcode_ para _mod_nfeio_si_productcode_
* [x] migrar clientes: migrar os registros de emissão de nf personalizados dos clientes da tabela _mod_nfeio_custom_configs_ para _mod_nfeio_si_custom_configs_
* [x] campos obrigatórios: verificar se os campos obrigatórios estão preenchidos e alertar
* [x] estrutura de tabelas: obedecer a estrutura atual das tabelas para menor atrito na migração