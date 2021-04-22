<?php
if (!defined('WHMCS')) {
    die('Esse arquivo não pode ser acessado diretamente.');
}
use WHMCS\Database\Capsule;

 foreach (Capsule::table('tblconfiguration')->where('setting', '=', 'gnfewhmcsadminurl')->get(['value']) as $gnfewhmcsadminurl_) {
     $gnfewhmcsadminurl = $gnfewhmcsadminurl_->value;
 }
 ?>
<div style="margin-bottom: 1%;">
    <a href="<?php echo $gnfewhmcsadminurl; ?>addonmodules.php?module=gofasnfeio&action=code_product" class="btn btn-primary" id="gnfe_cancel" title="Código de Serviços">Código de Serviços</a>
    <a href="<?php echo $gnfewhmcsadminurl; ?>addonmodules.php?module=gofasnfeio&action=nfeio" class="btn btn-primary" id="gnfe_cancel" title="NFE.oi">NFE.oi</a>
</div>
<ul class="nav nav-tabs admin-tabs" role="tablist">
	<li class="<?php if (!$_GET['aba']) {
     echo 'active';
 } ?>"><a class="tab-top">Notas Fiscais</a></li>
</ul>
<div class="tab-content admin-tabs"><div class="tab-pane active">

<?php	
        if ($_GET['acao'] == 'emitir') {
            $sql = mysql_query("SELECT i.id AS id, i.total AS total, c.id AS cliente_id, c.firstname AS firstname, c.lastname AS lastname, c.companyname AS companyname, c.email AS email, c.country AS country, c.postcode AS postcode, c.address1 AS address1, c.address2 AS address2, c.city AS city, c.state AS state FROM tblinvoices i, tblclients c WHERE i.userid = c.id AND i.id = '" . $_GET['cod'] . "'");
            $row = mysql_fetch_array($sql);

            if ($row['total'] > '0.00') {
                $descricao = str_replace('{fatura_id}', $row['id'], nfeio_configModulo('item_resumo'));

                if ($row['companyname']) {
                    $nome = $row['companyname'];
                } else {
                    $nome = $row['firstname'] . ' ' . $row['lastname'];
                }

                $dados = [
                    'cityServiceCode' => nfeio_configModulo('cityServiceCode'),
                    'description' => $descricao,
                    'servicesAmount' => $row['total'],
                    'borrower' => [
                        'federalTaxNumber' => preg_replace('/[^0-9]/', '', nfeio_ValorCampo(nfeio_configModulo('input_doc'), $row['cliente_id'])),
                        'name' => $nome,
                        'email' => $row['email'],
                        'address' => [
                            'country' => nfeio_SiglaPais($row['country']),
                            'postalCode' => preg_replace('/[^0-9]/', '', $row['postcode']),
                            'street' => $row['address1'],
                            'number' => nfeio_ValorCampo(nfeio_configModulo('input_num'), $row['cliente_id']),
                            'additionalInformation' => nfeio_ValorCampo(nfeio_configModulo('input_complemento'), $row['cliente_id']),
                            'district' => $row['address2'],
                            'city' => [
                                'code' => nfeio_CodIBGE(preg_replace('/[^0-9]/', '', $row['postcode'])),
                                'name' => $row['city']
                            ],
                            'state' => $row['state']
                        ]
                    ]
                ];

                $nfeio_emitirNF = nfeio_emitirNF($dados);

                if ($nfeio_emitirNF->flowStatus) {
                    $msgRetorno = $nfeio_emitirNF->flowStatus;
                } else {
                    $msgRetorno = $nfeio_emitirNF->message;
                }

                $query = "INSERT INTO mod_nfeio (cliente, fatura, nf, emissao, valor, status, retorno, msg) VALUES ('" . $row['cliente_id'] . "', '" . $row['id'] . "', '" . $nfeio_emitirNF->id . "', NOW(), '" . $row['total'] . "', '" . $nfeio_emitirNF->status . "', '" . serialize($nfeio_emitirNF) . "', '" . $msgRetorno . "')";
                $result = full_query($query);
            }

            echo "<meta HTTP-EQUIV='Refresh' CONTENT='0;URL=" . $vars['modulelink'] . "'>";
        }

        if ($_GET['acao'] == 'reemitir') {
            $sql = mysql_query("SELECT i.id AS id, i.total AS total, c.id AS cliente_id, c.firstname AS firstname, c.lastname AS lastname, c.companyname AS companyname, c.email AS email, c.country AS country, c.postcode AS postcode, c.address1 AS address1, c.address2 AS address2, c.city AS city, c.state AS state FROM tblinvoices i, tblclients c WHERE i.userid = c.id AND i.id = '" . $_GET['cod'] . "'");
            $row = mysql_fetch_array($sql);

            if ($row['total'] > '0.00') {
                $descricao = str_replace('{fatura_id}', $row['id'], nfeio_configModulo('item_resumo'));

                if ($row['companyname']) {
                    $nome = $row['companyname'];
                } else {
                    $nome = $row['firstname'] . ' ' . $row['lastname'];
                }

                $dados = [
                    'cityServiceCode' => nfeio_configModulo('cityServiceCode'),
                    'description' => $descricao,
                    'servicesAmount' => $row['total'],
                    'borrower' => [
                        'federalTaxNumber' => preg_replace('/[^0-9]/', '', nfeio_ValorCampo(nfeio_configModulo('input_doc'), $row['cliente_id'])),
                        'name' => $nome,
                        'email' => $row['email'],
                        'address' => [
                            'country' => nfeio_SiglaPais($row['country']),
                            'postalCode' => preg_replace('/[^0-9]/', '', $row['postcode']),
                            'street' => $row['address1'],
                            'number' => nfeio_ValorCampo(nfeio_configModulo('input_num'), $row['cliente_id']),
                            'additionalInformation' => nfeio_ValorCampo(nfeio_configModulo('input_complemento'), $row['cliente_id']),
                            'district' => $row['address2'],
                            'city' => [
                                'code' => nfeio_CodIBGE(preg_replace('/[^0-9]/', '', $row['postcode'])),
                                'name' => $row['city']
                            ],
                            'state' => $row['state']
                        ]
                    ],
                    'issAmountWithheld' => '',
                    'cnaeCode' => ''
                ];

                $nfeio_emitirNF = nfeio_emitirNF($dados);

                if ($nfeio_emitirNF->flowStatus) {
                    $msgRetorno = $nfeio_emitirNF->flowStatus;
                } else {
                    $msgRetorno = $nfeio_emitirNF->message;
                }

                $query = "UPDATE mod_nfeio SET cliente='" . $row['cliente_id'] . "', nf='" . $nfeio_emitirNF->id . "', emissao=NOW(), valor='" . $row['total'] . "', status='" . $nfeio_emitirNF->status . "', retorno='" . serialize($nfeio_emitirNF) . "', msg='" . $msgRetorno . "' WHERE fatura='" . $_GET['cod'] . "'";
                $result = full_query($query);
            }		

            echo "<meta HTTP-EQUIV='Refresh' CONTENT='0;URL=" . $vars['modulelink'] . "'>";
        } ?>
<table id="sortabletbl0" class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3"><tr><th>Fatura</th><th>Data da emissão</th><th>Tomador</th><th>Valor (R$)</th><th>Status</th><th>Ações</th></tr>
	
<?php
	$sql = mysql_query('SELECT m.fatura AS fatura, m.nf AS nf, m.retorno AS retorno, m.emissao AS emissao, m.valor AS valor, m.status AS status, m.msg AS msg, c.id AS cliente_id, c.firstname AS nome, c.lastname AS sobrenome FROM mod_nfeio m, tblclients c WHERE m.cliente = c.id ORDER BY m.id DESC');
        if (mysql_num_rows($sql)) {
            while ($row = mysql_fetch_array($sql)) {
                if ($row['status'] == 'Issued') {
                    $status = 'Emitida';
                    $status_cor = 'paid';
                } elseif ($row['status'] == 'Created') {
                    $status = 'Processando';
                    $status_cor = 'pending';
                } elseif ($row['status'] == 'WaitingCalculateTaxes') {
                    $status = 'Calculando Taxas';
                    $status_cor = 'pending';
                } elseif ($row['status'] == 'WaitingDefineRpsNumber') {
                    $status = 'Definindo RPS';
                    $status_cor = 'pending';
                } elseif ($row['status'] == 'WaitingSendCancel') {
                    $status = 'Cancelando';
                    $status_cor = 'pending';
                } elseif ($row['status'] == 'Cancelled') {
                    $status = 'Cancelada';
                    $status_cor = 'cancelled';
                } else {
                    $status = 'Erro';
                    $status_cor = 'closed';
                }

                echo '<tr>';
                echo '<td><a href="invoices.php?action=edit&id=' . $row['fatura'] . '" target="blank">#' . $row['fatura'] . '</a></td>';
                echo '<td><center>' . date('d/m/Y', strtotime($row['emissao'])) . '</center></td>';
                echo '<td><a href="clientssummary.php?userid=' . $row['cliente_id'] . '" target="blank">' . $row['nome'] . ' ' . $row['sobrenome'] . '</a></td>';
                echo '<td><center>' . number_format($row['valor'], 2, ',', '.') . '</center></td>';
                echo '<td><center><span class="label ' . $status_cor . '" title="' . $row['msg'] . '">' . $status . '</span></center></td>';
                echo '<td><center>';
                if ($status == 'Erro') {
                    echo '<a alt="Tentar Novamente" title="Tentar Novamente" class="btn btn-sm btn-default" href="' . $vars['modulelink'] . '&acao=reemitir&cod=' . $row['fatura'] . '"><i class="fa fa-refresh"></i></a>&nbsp;';
                }
                echo '<a alt="Acessar NFe.io" title="Acessar NFe.io" class="btn btn-sm btn-primary" href="https://app.nfe.io/service-invoices/' . $vars['empresa_id'] . '" target="blank"><i class="fa fa-globe"></i></a>';
                echo '</center></tr>';
            }
        } else {
            echo '<tr><td colspan="6">Nenhum resultado</td></tr>';
        }

        echo '</table>';

    echo '</div></div>';