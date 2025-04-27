{include file="includes/menu.tpl"}
<link href="https://cdn.datatables.net/v/bs/dt-2.2.2/datatables.min.css"
      rel="stylesheet"
      integrity="sha384-xd6yqpSXZRZVl62sBIxyT2i4xVlfaxWVjVQB7qsVte0qEr3iepsBrLi/awgmIoPV"
      crossorigin="anonymous">

<script src="https://cdn.datatables.net/v/bs/dt-2.2.2/datatables.min.js"
        integrity="sha384-KsmaH+vFCWsWkBqzoXM7HmafapkguLKrj9aRyWzIIaUDqRN99PP25wJUm7ZE+KP3"
        crossorigin="anonymous"></script>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title text-center">Alíquotas & Retenções</h3>
            </div>
            <div class="panel-body">
                <p>Personalize alíquotas e retenções de impostos para os códigos de serviços personalizados.</p>
                <div class="alert alert-info">
                    <p><strong>Informações:</strong></p>
                    <ul>
                        <li>Códigos de serviços <strong>em branco</strong> (vazio) incidirá a alíquota padrão caso
                            configurada.
                        </li>
                        <li>Códigos de serviços com valor de alíquota <strong>0 (zero)</strong> não sofrerá retenção,
                            mesmo havendo uma alíquota padrão configurada.
                        </li>
                        <li>O mesmo código de serviço pode ser utilizado por mais de um emissor.</li>
                        <li>Diferentes emissores poderão ter diferentes alíquotas para o mesmo código de serviço.</li>
                    </ul>
                </div>
                <div style="margin-bottom: 20px;" >
                    <!-- botao adicionar aliquotas -->
                    {if $dropdownServiceCodesAliquots|@count >= 1}
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#addAliquotModal">
                            <i class="fa fa-plus"></i> Adicionar Retenção
                        </button>
                    {/if}
                    <!-- botao adicionar aliquotas -->
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <table id="productCodeTable" class="table table-hover mt-3">
                            <thead>
                            <tr>
                                <th>Código do Serviço</th>
                                <th>ISS Retido (%)</th>
                                <th>Emissor</th>
                                <th class="text-center">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach from=$dtData item=produto }
                                <tr>
                                    <td>{$produto->code_service}</td>
                                    <td>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <input class="form-control" type="text" name="iss_held" id="issHeld"
                                                       size="5" value="{$produto->iss_held}" disabled>
                                                <div class="input-group-addon">%</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{$produto->company_tax_number} - {$produto->company_name}</td>
                                    <td>

                                        <button type="button" class="btn btn-primary btn-xs btn-edit"
                                                data-id="{$produto->record_id}"
                                                data-code-service="{$produto->code_service}"
                                                data-iss-held="{$produto->iss_held}"
                                                data-company-name="{$produto->company_name}"
                                                data-company-tax-number="{$produto->company_tax_number}"
                                        >
                                            Editar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs btn-remove"
                                                data-toggle="modal"
                                                data-target="#confirmRemoveModal"
                                                data-code-service="{$produto->code_service}"
                                                data-company-name="{$produto->company_name}"
                                                data-id="{$produto->record_id}"
                                        >
                                            Remover
                                        </button>

                                    </td>

                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="includes/modals/modal_aliquots_remove.tpl"}
{include file="includes/modals/modal_aliquots_add.tpl" }
{include file="includes/modals/modal_aliquots_edit.tpl" }

{literal}
    <script>
        $(document).ready(function () {
            $('#productCodeTable').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.11.3/i18n/pt_br.json"
                },
                order: [[0, "desc"]]
            });
        });
    </script>
{/literal}