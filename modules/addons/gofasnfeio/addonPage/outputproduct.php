<?php

use WHMCS\Database\Capsule;

    $html_table = '';
    if ($_POST['product']) {
        $user = localAPI('GetAdminDetails', []);

        try {
            $num = Capsule::table('tblproductcode')->where('product_id', '=', $_POST['product'])->count();
            if ($num > 0) {
                $res = Capsule::table('tblproductcode')->where('product_id', '=', $_POST['product'])->update(['code_service' => $_POST['code'], 'update_at' => date('Y-m-d H:i:s')]);
                if ($_POST['code'] == 0) {
                    $res = Capsule::table('tblproductcode')->where('product_id', '=', $_POST['product'])->delete();
                }
            } else {
                if ($_POST['code'] != 0) {
                    $res = Capsule::table('tblproductcode')->insert(['code_service' => $_POST['code'], 'product_id' => $_POST['product'], 'create_at' => date('Y-m-d H:i:s'), 'ID_user' => $user['adminid']]);
                }
            }
            $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #5cb85c;color: #ffffff;padding: 5px;text-align: center;">Código Salvo</div>';
            header_remove();
            header('Location: addonmodules.php?module=gofasnfeio&action=code_product&gnfe_message=' . base64_encode(urlencode($message)));

            exit;
        } catch (\Exception $e) {
            $e->getMessage();
            $message = '<div style="position:absolute;top: -5px;width: 50%;left: 25%;background: #d9534f;color: #ffffff;padding: 5px;text-align: center;">' . $e->getMessage() . '</div>';
            header_remove();
            header('Location: addonmodules.php?module=gofasnfeio&action=code_product&gnfe_message=' . base64_encode(urlencode($message)));

            exit;
        }
    }

    $nfes = [];
    foreach (Capsule::table('tblproducts')->orderBy('id', 'desc')->get(['id']) as $nfes_) {
        $nfes[] = $nfes_->id;
    }

    foreach (Capsule::table('tblproducts')->
            leftJoin('tblproductcode', 'tblproducts.id', '=', 'tblproductcode.product_id')->
            orderBy('tblproducts.id', 'desc')->
            get(['tblproducts.id', 'tblproducts.name', 'tblproducts.created_at', 'tblproductcode.update_at', 'tblproductcode.code_service']) as $product) {
        $created_at = $product->created_at ? date('d/m/Y', strtotime($product->created_at)) : '';
        $update_at = $product->update_at ? date('d/m/Y', strtotime($product->update_at)) : '';

        $html_table .= '<tr><td><a href="configproducts.php?action=edit&id=' . $product->id . '" target="blank">#' . $product->id . '</a></td>
                <td style="text-align: center; vertical-align: middle;">' . $created_at . '</td>
                <td style="text-align: center; vertical-align: middle;">' . $update_at . '</td>
                <td><a href="configproducts.php?action=edit&id=' . $product->id . '" target="blank">' . $product->name . '</a></td>
                <form action="" method="post">
                <td><input type="text" name="code" value="' . $product->code_service . '" style="width: 100%;"></td>
                
                <input type="hidden" class="product" name="product" value="' . $product->id . '">
                <td><input type="submit"  style="width: 100%;" value="Salvar"></td>
                </form>';
    }

    echo '
            <a href="addonmodules.php?module=gofasnfeio&action=nfeio" class="btn btn-primary" id="gnfe_cancel" title="NFE.io">NFE.io</a>
            <a href="addonmodules.php?module=gofasnfeio&action=nfeio_legacy" class="btn btn-primary" title="Sistema legado">Sistema legado</a>
		
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
                ';
                if ($_REQUEST['gnfe_message']) {
                    echo urldecode(base64_decode($_REQUEST['gnfe_message']));
                }
