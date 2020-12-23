<?php
use WHMCS\Database\Capsule;

if ($_GET['action'] === 'code_product') {
    $html_table = '';

    if ($_POST['product']) {
        $user = localAPI('GetAdminDetails',[]);
        try {
            $num = Capsule::table('tblproductcode')->where('product_id','=',$_POST['product'])->count();
            if ($num > 0) {
                $res = Capsule::table('tblproductcode')->where('product_id','=',$_POST['product'])->update(['code_service' => $_POST['code'], 'update_at' => date('Y-m-d H:i:s')]);
                if ($_POST['code'] == 0) {
                    $res = Capsule::table('tblproductcode')->where('product_id','=',$_POST['product'])->delete();
                }
            } else {
                if ($_POST['code'] != 0) {
                    $res = Capsule::table('tblproductcode')->insert(['code_service' => $_POST['code'], 'product_id' => $_POST['product'], 'create_at' => date('Y-m-d H:i:s'), 'ID_user' => $user['adminid']]);
                }
            }
            $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Código Salvo</div>';
            header_remove();
            header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&action=code_product&gnfe_message=' . base64_encode(urlencode($message)));
            exit;
        } catch (\Exception $e) {
            $e->getMessage();
            $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $e->getMessage() . '</div>';
            header_remove();
            header('Location: ' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&action=code_product&gnfe_message=' . base64_encode(urlencode($message)));
            exit;
        }
    }

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
        $created_at = $product->created_at ? date('d/m/Y', strtotime($product->created_at)) : '';
        $update_at = $product->update_at ? date('d/m/Y', strtotime($product->update_at)) : '';
        //depois linkar o id e o nome com a pagina do produto
        $html_table .= '<tr><td><a href="' . $gnfewhmcsadminurl . 'configproducts.php?action=edit&id=' . $product->id . '" target="blank">#' . $product->id . '</a></td>
                <td>' . $created_at . '</td>
                <td>' . $update_at . '</td>
                <td><a href="' . $gnfewhmcsadminurl . 'configproducts.php?action=edit&id=' . $product->id . '" target="blank">' . $product->name . '</a></td>
                <form action="" method="post">
                <td><input type="text" name="code" value="' . $product->code_service . '" style="width: 100%;"></td>
                
                <input type="hidden" class="product" name="product" value="' . $product->id . '">
                <td><input type="submit"  style="width: 100%;" value="Salvar"></td>
                </form>';
    }

    echo '
            <a href="' . $gnfewhmcsadminurl . 'addonmodules.php?module=gofasnfeio&action=nfeio" class="btn btn-primary" id="gnfe_cancel" title="NFE.oi">NFE.oi</a>
		<div><h3>Listagem produtos</h3>' . $nfes_total . ' Itens encontrados.<br>Exibindo de ' . $nfes_from . ' a ' . $nfes_to . '. Página ' . $nfes_page . ' de ' . $nfes_pages . '</div>
		<div class="tab-content admin-tabs">
					<table id="sortabletbl0" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
						<tbody>
							<tr>
								<th>ID</th>
								<th>Data de Criação do Produto</th>
								<th>Data de Alteração</th>
								<th>Nome do Produto</th>
								<th>Código de Serviço</th>
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