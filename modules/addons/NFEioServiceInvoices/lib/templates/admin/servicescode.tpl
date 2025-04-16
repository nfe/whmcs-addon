{include file="includes/menu.tpl"}

{* funcao do Smarty para associar coluna company_id de produtro com o nome da empresa disponivel em availableCompanies *}

{*{function name="getCompanyName" companyId="" availableCompanies=""}*}
{*    {assign var="companyName" value=""}*}
{*    {assign var="companyTaxNumber" value=""}*}
{*    {foreach from=$availableCompanies item=company}*}
{*        {if $company->company_id == $companyId}*}
{*            {assign var="companyName" value=$company->company_name}*}
{*            {assign var="companyTaxNumber" value=$company->tax_number}*}
{*            {break}*}
{*        {/if}*}
{*    {/foreach}*}
{*    {if $companyName == ""}*}
{*        {foreach from=$availableCompanies item=company}*}
{*            {if $company->default == 1}*}
{*                {assign var="companyName" value=$company->company_name}*}
{*                {assign var="companyTaxNumber" value=$company->tax_number}*}
{*                {break}*}
{*            {/if}*}
{*        {/foreach}*}
{*    {/if}*}
{*    {$companyName|default:"Empresa não encontrada"}*}
{*{/function}*}

{function name="getCompanyName" companyId="" availableCompanies=""}
    {assign var="companyName" value=""}
    {assign var="companyTaxNumber" value=""}
    {foreach from=$availableCompanies item=company}
        {if $company->company_id == $companyId}
            {assign var="companyName" value=$company->company_name}
            {assign var="companyTaxNumber" value=$company->tax_number}
            {break}
        {/if}
    {/foreach}
    {if $companyName == ""}
        {assign var="companyName" value="Empresa não encontrada"}
    {/if}
    {$companyTaxNumber|default:"N/A"} - {$companyName}
{/function}


<link rel="stylesheet" type="text/css"
      href="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.css"/>
<script type="text/javascript"
        src="https://cdn.datatables.net/v/bs/dt-1.11.3/af-2.3.7/b-2.0.1/fh-3.2.0/datatables.min.js"></script>
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title text-center">Código de Serviço</h3>
            </div>
            <div class="panel-body">
                <p>Gerencie os códigos de serviço individualmente por produto existente no WHMCS.</p>

                <!-- Button to add new product code -->
                <div style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                            data-target="#addProductCodeModal">
                        <i class="fa fa-plus"></i> Adicionar Código de Serviço
                    </button>
                </div>

                <div class="panel panel-default">
                    <div class="panel-body">
                        <table id="productCodeTable" class="table table-hover">
                            <thead>
                            <tr>

                                <th>Produto</th>
                                <th>Código do Serviço</th>
                                <th>Emissor</th>
                                <th>Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach from=$dtData item=produto }
                                {call name="getCompanyName" companyId=$produto->company_id availableCompanies=$availableCompanies assign="companyName" assign="companyTaxNumber"}
                                <tr>

                                    <td>{$produto->product_id} - {$produto->product_name}</td>
                                    <td>{$produto->code_service}</td>
                                    <td>{$companyTaxNumber}</td>
                                    <td>
                                        <form action="{$modulelink}&action=serviceCodeRemove" method="post"
                                              style="display:inline;">
                                            <input type="hidden" name="record_id" value="{$produto->record_id}">
                                            <button type="button" class="btn btn-danger btn-xs btn-delete"
                                                    data-id="{$produto->record_id}"
                                                    data-name="{$produto->product_name}">Excluir Código
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


{include file="includes/modals/modal_servicescode_add.tpl"}
{include file="includes/modals/modal_servicescode_remove.tpl"}

{literal}
<script>
    // Handle company selection
    document.addEventListener('DOMContentLoaded', function () {
        function validateForm() {
            const productId = document.getElementById('product_id').value.trim();
            const serviceCode = document.getElementById('service_code').value.trim();
            const company = document.getElementById('companyDropdown').value.trim();

            // Enable the button only if all fields are filled
            const isValid = productId && serviceCode && company;
            document.getElementById('saveProductCode').disabled = !isValid;

            console.log({ productId, serviceCode, company, isValid }); // Debugging
        }

        // Attach event listeners
        document.getElementById('product_search').addEventListener('keyup', validateForm);
        document.getElementById('service_code').addEventListener('keyup', validateForm);
        document.getElementById('companyDropdown').addEventListener('change', validateForm);

        // Ensure hidden fields are populated correctly
        document.getElementById('companyDropdown').addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('company_name').value = selectedOption.getAttribute('data-name') || '';
            document.getElementById('company_default').value = selectedOption.getAttribute('data-default') || '';
            validateForm();
        });
    });

    // Handle modal show event
    $(document).ready(function () {


        let formToSubmit;

        // Handle delete button click
        $(document).on('click', '.btn-delete', function () {
            const recordId = $(this).data('id');
            const productName = $(this).data('name');

            // Set the product name in the modal
            $('#productToDelete').text(productName);

            // Store the form to submit
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

        $('#product_search').on('keyup', function () {
            const searchTerm = $(this).val().trim();

            clearTimeout(searchTimeout);

            if (searchTerm.length < 2) {
                $('#product_results').hide();
                return;
            }

            searchTimeout = setTimeout(function () {
                // AJAX call to search for products
                $.ajax({
                    url: '{/literal}{$modulelink}{literal}',
                    method: 'GET',
                    data: {
                        action: 'searchProducts',
                        term: searchTerm
                    },
                    dataType: 'json',
                    success: function (response) {
                        displayProductResults(response);
                    },
                    error: function (xhr, status, error) {
                        console.error('Error searching products:', error);
                    }
                });
            }, 300);
        });

        function displayProductResults(products) {
            const resultsContainer = $('#product_results');
            resultsContainer.empty();

            if (products.length === 0) {
                resultsContainer.append('<div class="list-group-item">Nenhum produto encontrado</div>');
            } else {
                products.forEach(function (product) {
                    const item = $('<a href="#" class="list-group-item"></a>')
                        .text(product.name)
                        .attr('data-id', product.id)
                        .attr('data-name', product.name);

                    resultsContainer.append(item);
                });
            }

            resultsContainer.show();
        }

        // Handle product selection
        $(document).on('click', '#product_results a', function (e) {
            e.preventDefault();

            const productId = $(this).data('id');
            const productName = $(this).data('name');

            $('#product_id').val(productId);
            $('#product_name').val(productName);

            $('#selected_product_name').text(productName);
            $('#selected_product_id').text(productId);

            $('#selected_product_info').show();
            $('#product_results').hide();
            $('#product_search').val('');

            validateForm2();
        });

        // Form validation
        function validateForm2() {
            const productId = $('#product_id').val();
            const serviceCode = $('#service_code').val();
            const company = $('#company').val();

            if (productId && serviceCode && company) {
                $('#saveProductCode').prop('disabled', false);
            } else {
                $('#saveProductCode').prop('disabled', true);
            }
        }

        $('#service_code, #company').on('change keyup', validateForm);

        // Handle form submission
        $('#addProductCodeForm').on('submit', function () {
            $('#addProductCodeModal').modal('hide');
            return true;
        });
    });
</script>
{/literal}