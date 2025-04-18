{include file="includes/menu.tpl"}
<link rel="stylesheet" type="text/css"
      href="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.css"/>
<script type="text/javascript"
        src="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.js"></script>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title text-center">Clientes Associados</h3>
            </div>
            <div class="panel-body">
                <p>
                    Associe clientes a emissores especificos. Quando um cliente estiver associado a um emissor, todos os
                    pedidos
                    e faturas do cliente serão emitidos com o emissor associado.
                </p>

                <!-- Button to add new product code -->
                <div style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-primary" data-toggle="modal"
                            data-target="#addProductCodeModal">
                        <i class="fa fa-plus"></i> Associar Cliente
                    </button>
                </div>

                <div class="panel panel-default">
                    <div class="panel-body">
                        <table id="productCodeTable" class="table table-hover">
                            <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Emissor</th>
                                <th>Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach from=$associatedClients item=client }
                                <tr>
                                    <td>
                                        {$client->client_id} - {$client->client_firstname} {$client->client_lastname}
                                        {if $client->client_companyname}
                                            <small>({$client->client_companyname})</small>
                                        {/if}
                                    </td>
                                    <td>
                                        {$client->company_tax_number} -
                                        {$client->company_name}
                                    </td>
                                    <td>
                                        <form action="{$modulelink}&action=associateClientsRemove" method="post"
                                              style="display:inline;">
                                            <input type="hidden" name="record_id" value="{$client->record_id}">
                                            <button type="button"
                                                    class="btn btn-danger btn-xs btn-delete"
                                                    data-id="{$client->record_id}"
                                                    id="btnDeleteAssociation"
                                                    data-company-name="{$client->company_tax_number} - {$client->company_name}"
                                                    data-client-name="{$client->client_id} - {$client->client_firstname} {$client->client_lastname} {if $client->client_companyname}({$client->client_companyname}){/if}"
                                            >Excluir
                                            </button>
                                        </form>
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


{*modal adicionar associacao*}
{include file="includes/modals/modal_associateclients_add.tpl"}
{*modal confirmar exclusao*}
{include file="includes/modals/modal_associateclients_remove.tpl"}



{literal}
<script>


    // Handle modal show event
    $(document).ready(function () {

        let formToSubmit;

        // Handle delete button click
        $(document).on('click', '#btnDeleteAssociation', function () {
            const recordId = $(this).data('id');
            const clientName = $(this).data('client-name');
            const companyName = $(this).data('company-name');

            // Set the product name in the modal
            $('#clientData').text(clientName);
            $('#companyData').text(companyName);

            // Seleciona o atributo form mais proximo do responsavel pela acao (.btn-delete)
            formToSubmit = $(this).closest('form');

            // Show the confirmation modal
            $('#confirmDeleteModal').modal('show');
        });

        // Handle confirm delete button click
        $('#confirmDeleteButton').on('click', function () {
            // Submit the form
            formToSubmit.submit();
        });

        // Initialize DataTable
        $('#productCodeTable').DataTable({
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.11.3/i18n/pt_br.json"
            },
            order: [[0, "desc"]]
        });

        // Product search functionality
        let searchTimeout;

        $('#client_search').on('keyup', function () {
            const searchTerm = $(this).val().trim();

            clearTimeout(searchTimeout);

            if (searchTerm.length < 2) {
                $('#client_results').hide();
                return;
            }

            searchTimeout = setTimeout(function () {
                // AJAX call to search for products
                $.ajax({
                    url: '{/literal}{$modulelink}{literal}',
                    method: 'GET',
                    data: {
                        action: 'searchClients',
                        term: searchTerm
                    },
                    dataType: 'json',
                    success: function (response) {
                        displayProductResults(response);
                    },
                    error: function (xhr, status, error) {
                        console.error('Erro ao buscar clientes:', error);
                    }
                });
            }, 300);
        });

        function displayProductResults(clients) {
            const resultsContainer = $('#client_results');
            resultsContainer.empty();

            if (clients.length === 0) {
                resultsContainer.append('<div class="list-group-item">Nenhum cliente encontrado</div>');
            } else {
                clients.forEach(function (client) {
                    const item = $('<a href="#" class="list-group-item"></a>')
                        .text(client.firstname + ' ' + client.lastname)
                        .attr('data-id', client.id)
                        .attr('data-name', client.firstname + ' ' + client.lastname)

                    resultsContainer.append(item);
                });
            }

            resultsContainer.show();
        }

        // Handle product selection
        $(document).on('click', '#client_results a', function (e) {
            e.preventDefault();

            const clientId = $(this).data('id');
            const clientName = $(this).data('name');

            $('#client_id').val(clientId);
            $('#client_name').val(clientName);

            $('#selected_client_name').text(clientName);
            $('#selected_client_id').text(clientId);

            $('#selected_client_info').show();
            $('#client_results').hide();
            $('#client_search').val('');

            validateForm();
        });

        // Form validation
        function validateForm() {
            const clientId = $('#client_id').val();


            if (clientId) {
                $('#saveProductCode').prop('disabled', false);
            } else {
                $('#saveProductCode').prop('disabled', true);
            }
        }

        $('#company').on('change keyup', validateForm);

        // Handle form submission
        $('#addProductCodeForm').on('submit', function () {
            $('#addProductCodeModal').modal('hide');
            return true;
        });
    });
</script>
{/literal}