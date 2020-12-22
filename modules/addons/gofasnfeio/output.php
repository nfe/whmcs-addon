<?php
use WHMCS\Database\Capsule;
if ( !function_exists('gofasnfeio_output') ) {
    function gofasnfeio_output($vars) {
        require_once __DIR__ . '/functions.php';
        $params = gnfe_config();
        foreach ( Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminurl')->get( ['value'] ) as $gnfewhmcsadminurl_ ) {
            $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
        }

        if ($_POST['product']) {
            $user = localAPI('GetAdminDetails',[]);
            $num = Capsule::table('tblproductcode')->where('product_id','=',$_POST['product'])->count();
            if ($num > 0) {
                $res = Capsule::table('tblproductcode')->where('product_id','=',$_POST['product'])->update(['code_service' => $_POST['code'], 'update_at' => date('Y-m-d H:i:s')]);
            } else {
                $res = Capsule::table('tblproductcode')->insert(['code_service' => $_POST['code'], 'product_id' => $_POST['product'], 'create_at' => date('Y-m-d H:i:s'), 'ID_user' => $user['adminid']]);
            }
        }

        if ($_GET['Prod_code'] === 'yes') {
            $html_table = '';

            foreach ( Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminurl')->get( ['value'] ) as $gnfewhmcsadminurl_ ) {
                $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
            }
            $nfes = [];
            foreach ( Capsule::table('tblproducts')->orderBy('id', 'desc')->get( ['id'] ) as $nfes_ ) {
                $nfes[] = $nfes_->id;
            }
            if ($_REQUEST['page']) {
                $nfes_page = (int)$_REQUEST['page'];
            } else {
                $nfes_page = 1;
            }
            if ($_REQUEST['take']) {
                $take = (int)$_REQUEST['take'];
            } else {
                $take = 10;
            }

            $nfs_keys = array_keys($nfes);
            $nfes_total = count($nfes);
            if ($take > $nfes_total) {
                $take = $nfes_total;
            }

            $nfes_pages = ceil($nfes_total / $take);
            $nfes_from_ = ( $nfes_page * $take ) - $take;
            $nfes_from = $nfs_keys[$nfes_from_ + 1];
            $nfes_to_ = ( $nfes_from + $take ) - 2;
            $nfes_to = $nfs_keys[$nfes_to_ + 1];
            $nfess = array_slice($nfes, $nfes_from_, $nfes_to);

            if ((int)$nfes_page === (int)$nfes_pages) {
                $nfes_to = $nfes_total;
                $nfess = array_slice($nfes, $nfes_from_, $nfes_to_);
            }
            if ((int)$take >= (int)$nfes_total) {
                $nfes_from = 1;
                $nfess = array_slice($nfes, $nfes_from_, $nfes_to);
            }
            // Pagination
            $i = 1;
            while ($i <= $nfes_pages ) {
                $page_num = $i++;

                if ( (int)$page_num !== (int)$nfes_page ) {
                    $tag = 'a ';
                    $a_style = '';
                    $li_class = 'class="enabled"';
                    $href = $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&page=' . $page_num;
                } elseif ( (int)$page_num === (int)$nfes_page ) {
                    $tag = 'span ';
                    $a_style = 'style="background: #337ab7; color: #fff"';
                    $li_class = 'class="disabled"';
                    $href = '';
                }
                $pagination_ .= '<li ' . $li_class . '><' . $tag . ' ' . $a_style . ' href="' . $href . '" ><strong>' . $page_num . '</strong></' . $tag . '></li>';
            }
            if ((int)$nfes_page === 1) {
                $preview_class = ' class="previous disabled" ';
                $preview_href = '';
                $preview_tag = 'span ';
            } else {
                $preview_class = ' class="previous" ';
                $preview_href = ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&page=' . ($nfes_page - 1) . '" ';
                $preview_tag = 'a ';
            }
            if ((int)$nfes_page === (int)$nfes_pages) {
                $next_class = ' class="next disabled" ';
                $next_href = '';
                $next_tag = 'span ';
            } else {
                $next_class = ' class="next" ';
                $next_href = ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&page=' . ($nfes_page + 1) . '" ';
                $next_tag = 'a ';
            }
            $pagination .= '<li ' . $preview_class . '><' . $preview_tag . ' ' . $preview_href . '>« Página anterior</' . $preview_tag . '></li>';
            $pagination .= $pagination_;
            $pagination .= '<li ' . $next_class . '><' . $next_tag . ' ' . $next_href . '>Próxima página »</' . $next_tag . '></li>';

            foreach ( Capsule::table('tblproducts')->
            leftJoin('tblproductcode', 'tblproducts.id', '=', 'tblproductcode.product_id')->
            orderBy('tblproducts.id', 'desc')->
            whereBetween('tblproducts.id', [end($nfess), reset($nfess)])->
            take($take)->
            get( ['tblproducts.id', 'tblproducts.name', 'tblproducts.created_at', 'tblproductcode.update_at', 'tblproductcode.code_service'] ) as $product) {
                $update_at = $product->updated_at ? date('d/m/Y', strtotime($product->updated_at)) : '';
                //depois linkar o id e o nome com a pagina do produto
                $html_table .= '<tr><td><a href="' . $gnfewhmcsadminurl . 'configproducts.php?action=edit&id=' . $product->id . '" target="blank">#' . $product->id . '</a></td>
                <td>' . date('d/m/Y', strtotime($product->created_at)) . '</td>
                <td>' . $update_at . '</td>
                <td><a href="' . $gnfewhmcsadminurl . 'configproducts.php?action=edit&id=' . $product->id . '" target="blank">' . $product->name . '</a></td>
                <form action="" method="post">
                <td><input type="text" name="code" value="' . $product->code_service . '" style="width: 100%;"></td>
                
                <input type="hidden" class="product" name="product" value="' . $product->id . '">
                <td><input type="submit"  style="width: 100%;" value="Salvar"></td>
                </form>';
            }

            echo '
            <a href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&Prod_code=not" class="btn btn-primary" id="gnfe_cancel" title="NFE.oi">NFE.oi</a>
		<div><h3>Listagem de notas fiscais</h3>' . $nfes_total . ' Itens encontrados.<br>Exibindo de ' . $nfes_from . ' a ' . $nfes_to . '. Página ' . $nfes_page . ' de ' . $nfes_pages . '</div>
		<div class="tab-content admin-tabs">
					<table id="sortabletbl0" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
						<tbody>
							<tr>
								<th>ID</th>
								<th>Data de Criação do Produto</th>
								<th>Data de Modificação do codigo</th>
								<th>Nome do Produto</th>
								<th>Codigo de Serviço</th>
								<th>Salvar</th>
							</tr>
							
								' . $html_table . '
							
						</tbody>
					</table>
				</div>
				
				<div class="text-center">
					<ul class="pagination">
						' . $pagination . '
					</ul>
				</div>
                ';
            return '';
        }

        $nfes = [];
        foreach ( Capsule::table('gofasnfeio')->orderBy('id', 'desc')->get( ['id'] ) as $nfes_ ) {
            $nfes[] = $nfes_->id;
        }
        if ($_REQUEST['page']) {
            $nfes_page = (int)$_REQUEST['page'];
        } else {
            $nfes_page = 1;
        }
        if ($_REQUEST['take']) {
            $take = (int)$_REQUEST['take'];
        } else {
            $take = 10;
        }

        $nfs_keys = array_keys($nfes);
        $nfes_total = count($nfes);
        if ($take > $nfes_total) {
            $take = $nfes_total;
        }

        $nfes_pages = ceil($nfes_total / $take);
        $nfes_from_ = ( $nfes_page * $take ) - $take;
        $nfes_from = $nfs_keys[$nfes_from_ + 1];
        $nfes_to_ = ( $nfes_from + $take ) - 2;
        $nfes_to = $nfs_keys[$nfes_to_ + 1];
        $nfess = array_slice($nfes, $nfes_from_, $nfes_to);

        if ((int)$nfes_page === (int)$nfes_pages) {
            $nfes_to = $nfes_total;
            $nfess = array_slice($nfes, $nfes_from_, $nfes_to_);
        }
        if ((int)$take >= (int)$nfes_total) {
            $nfes_from = 1;
            $nfess = array_slice($nfes, $nfes_from_, $nfes_to);
        }
        // Pagination
        $i = 1;
        while ($i <= $nfes_pages ) {
            $page_num = $i++;

            if ( (int)$page_num !== (int)$nfes_page ) {
                $tag = 'a ';
                $a_style = '';
                $li_class = 'class="enabled"';
                $href = $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&page=' . $page_num;
            } elseif ( (int)$page_num === (int)$nfes_page ) {
                $tag = 'span ';
                $a_style = 'style="background: #337ab7; color: #fff"';
                $li_class = 'class="disabled"';
                $href = '';
            }
            $pagination_ .= '<li ' . $li_class . '><' . $tag . ' ' . $a_style . ' href="' . $href . '" ><strong>' . $page_num . '</strong></' . $tag . '></li>';
        }
        if ((int)$nfes_page === 1) {
            $preview_class = ' class="previous disabled" ';
            $preview_href = '';
            $preview_tag = 'span ';
        } else {
            $preview_class = ' class="previous" ';
            $preview_href = ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&page=' . ($nfes_page - 1) . '" ';
            $preview_tag = 'a ';
        }
        if ((int)$nfes_page === (int)$nfes_pages) {
            $next_class = ' class="next disabled" ';
            $next_href = '';
            $next_tag = 'span ';
        } else {
            $next_class = ' class="next" ';
            $next_href = ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&page=' . ($nfes_page + 1) . '" ';
            $next_tag = 'a ';
        }
        $pagination .= '<li ' . $preview_class . '><' . $preview_tag . ' ' . $preview_href . '>« Página anterior</' . $preview_tag . '></li>';
        $pagination .= $pagination_;
        $pagination .= '<li ' . $next_class . '><' . $next_tag . ' ' . $next_href . '>Próxima página »</' . $next_tag . '></li>';

        foreach ( Capsule::table('gofasnfeio')->
			orderBy('id', 'desc')->
            whereBetween('id', [end($nfess), reset($nfess)])->
			take($take)->
			get( ['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at'] ) as $value ) {
            $client = localAPI('GetClientsDetails',['clientid' => $value->user_id, 'stats' => false, ], false);
            if ($value->status === 'Waiting') {
                $status = '<span style="color:#f0ad4e;">Aguardando</span>';
                $disabled = ['a' => 'disabled="disabled"', 'b' => 'disabled="disabled"', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
            }
            if ($value->status === 'Created') {
                $status = '<span style="color:#f0ad4e;">Processando</span>';
                $disabled = ['a' => 'disabled="disabled"', 'b' => '', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
            }
            if ($value->status === 'Issued') {
                $status = '<span style="color:#779500;">Emitida</span>';
                $disabled = ['a' => 'disabled="disabled"', 'b' => '', 'c' => '', 'd' => ''];
            }
            if ($value->status === 'Cancelled') {
                $status = '<span style="color:#c00;">Cancelada</span>';
                $disabled = ['a' => '', 'b' => '', 'c' => 'disabled="disabled"', 'd' => ''];
            }
            if ($value->status === 'Error') {
                $status = '<span style="color:#c00;">Falha ao Emitir</span>';
                $disabled = ['a' => '', 'b' => '', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
            }
            if ($value->status === 'None') {
                $status = '<span style="color:#f0ad4e;">Nenhum</span>';
                $disabled = ['a' => '', 'b' => '', 'c' => 'disabled="disabled"', 'd' => 'disabled="disabled"'];
            }
            $html_table .= '<tr><td><a href="' . $gnfewhmcsadminurl . 'invoices.php?action=edit&id=' . $value->invoice_id . '" target="blank">#' . $value->invoice_id . '</a></td>
								<td>' . date('d/m/Y', strtotime($value->created_at)) . '</td>
								<td><a href="' . $gnfewhmcsadminurl . 'clientssummary.php?userid=' . $value->user_id . '" target="blank">' . $client['fullname'] . '</a></td>
								<td>' . number_format( $value->services_amount,  2, ',', '.' ) . '</td>
								<td>' . $status . '</td>
								<td style="width: 420px;">
								<a ' . $disabled['a'] . ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&invoice_id=' . $value->invoice_id . '&gnfe_create=yes" class="btn btn-primary" id="gnfe_generate" title="Emitir Nota Fiscal">Emitir Nova</a>
								<a ' . $disabled['b'] . ' target="_blank" href="https://app.nfe.io/companies/' . $params['company_id'] . '/service-invoices/' . $value->nfe_id . '" title="Ver Nota Fiscal" class="btn btn-success">Visualizar</a>
								<a ' . $disabled['c'] . ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&invoice_id=' . $value->invoice_id . '&gnfe_cancel=' . $value->nfe_id . '&services_amount=' . $value->services_amount . '&environment=' . $value->environment . '&flow_status=' . $value->flow_status . '&user_id=' . $value->user_id . '&created_at=' . $value->created_at . '" class="btn btn-danger" id="gnfe_cancel" title="Cancelar Nota Fiscal">Cancelar</a>
								<a ' . $disabled['d'] . ' href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_email=' . $value->nfe_id . '" class="btn btn-primary" id="gnfe_cancel" title="Enviar Nota Fiscal por Email">Enviar por Email</a></td></tr>';
        }
        if ((int)$nfes_total > 0) {
            echo '
            <a href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&Prod_code=yes" class="btn btn-primary" id="gnfe_cancel" title="Codigo dos Produtos">Codigo dos Produtos</a>
		<div><h3>Listagem de notas fiscais</h3>' . $nfes_total . ' Itens encontrados.<br>Exibindo de ' . $nfes_from . ' a ' . $nfes_to . '. Página ' . $nfes_page . ' de ' . $nfes_pages . '</div>
		<div class="tab-content admin-tabs">
					<table id="sortabletbl0" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
						<tbody>
							<tr>
								<th>Fatura</th>
								<th>Data de Criação</th>
								<th>Cliente</th>
								<th>Valor (R$)</th>
								<th>Status</th>
								<th>Ações</th>
							</tr>
							
								' . $html_table . '
							
						</tbody>
					</table>
				</div>
				
				<div class="text-center">
					<ul class="pagination">
						' . $pagination . '
					</ul>
				</div>
				';
        } else {
            echo '
		<div>
			<h3>Nenhuma nota fiscal gerada até o momento</h3>
		</div>';
        }
        if ($_REQUEST['gnfe_create']) {
            $invoice = localAPI('GetInvoice',  ['invoiceid' => $_REQUEST['invoice_id']], false);
            $client = localAPI('GetClientsDetails',['clientid' => $invoice['userid'], 'stats' => false, ], false);
            $nfe_for_invoice = gnfe_get_local_nfe($_REQUEST['invoice_id'],['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'pdf', 'created_at', 'rpsSerialNumber']);
            $queue = gnfe_queue_nfe($_REQUEST['invoice_id'],true);
            if ($queue !== 'success') {
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">Erro ao salvar nota fiscal no DB: ' . $queue . '</div>';
                header_remove();
                header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_message=' . base64_encode(urlencode($message)));
                exit;
            }
            if ($queue === 'success') {
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Nota fiscal enviada para processamento</div>';
                header_remove();
                header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_message=' . base64_encode(urlencode($message)));
                exit;
            }
        }
        if ($_REQUEST['gnfe_cancel']) {
            $delete_nfe = gnfe_delete_nfe($_REQUEST['gnfe_cancel']);
            if (!$delete_nfe->message) {
                $gnfe_update_nfe = gnfe_update_nfe((object)['id' => $_REQUEST['gnfe_cancel'], 'status' => 'Cancelled', 'servicesAmount' => $_REQUEST['services_amount'], 'environment' => $_REQUEST['environment'], 'flow_status' => $_REQUEST['flow_status']],$_REQUEST['user_id'],$_REQUEST['invoice_id'],'n/a',$_REQUEST['created_at'],date('Y-m-d H:i:s') );
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Nota fiscal cancelada com sucesso</div>';
                header_remove();
                header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_message=' . base64_encode(urlencode($message)));
                exit;
            }
            if ($delete_nfe->message) {
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $delete_nfe->message . '</div>';
                header_remove();
                header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_message=' . base64_encode(urlencode($message)));
                exit;
            }
        }
        if ($_REQUEST['gnfe_email']) {
            $gnfe_email = gnfe_email_nfe($_REQUEST['gnfe_email']);
            if (!$gnfe_email->message) {
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Email Enviado com Sucesso</div>';
                header_remove();
                header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_message=' . base64_encode(urlencode($message)));
                exit;
            }
            if ($gnfe_email->message) {
                $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $gnfe_email->message . '</div>';
                header_remove();
                header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&gnfe_message=' . base64_encode(urlencode($message)));
                exit;
            }
        }
        if ($_REQUEST['gnfe_message']) {
            echo urldecode(base64_decode( $_REQUEST['gnfe_message'] ));
        }
    }
}