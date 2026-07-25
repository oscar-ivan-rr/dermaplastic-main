<?php
/* Copyright (C) 2013-2018 Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014	   Regis Houssin		<regis.houssin@inodbox.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/stock/massstockmove.php
 *  \ingroup    stock
 *  \brief      This page allows to select several products, then incoming warehouse and
 *  			outgoing warehouse and create all stock movements for this.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/functions.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';


// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders', 'productbatch'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');

//checks if a product has been ordered

$action = GETPOST('action', 'alpha');
$id_product = GETPOST('productid', 'int');
$searchproduct = preg_replace("/[^0-9]/", '', GETPOST('search_productid', 'alpha'));
$id_sw = GETPOST('id_sw', 'int');
$id_tw = GETPOST('id_tw', 'int');
$batch = GETPOST('batch');
$qty = GETPOST('qty');
$idline = GETPOST('idline');
$confirm = GETPOST('confirm', 'alpha');
$draft = 1;

$sortfield = GETPOST('sortfield', 'alpha') ?: 'p.ref';
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int') ?: 0; // If $page is not defined, or '' or -1

if (!$sortorder) {
	$sortorder = 'ASC';
}
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

$listofdata = [];
if (!empty($_SESSION['massstockmove']))
	$listofdata = json_decode($_SESSION['massstockmove'], true);
if (!empty($listofdata)) {
	foreach ($listofdata as $data) {
		if (!$id_sw && isset($data['id_sw']) && $data['id_sw'] != 0) {
			$id_sw = $data['id_sw'];
		}
		if (!$id_tw && isset($data['id_tw']) && $data['id_tw'] != 0) {
			$id_tw = $data['id_tw'];
		}
		if ($id_sw && $id_tw)
			break; // Si ambos id_sw e id_tw son diferentes de cero, termina el bucle
	}
} elseif (empty($listofdata) && !isset($id_product)) {
	$id_sw = 0;
	$id_tw = 0;
}
$canApprove = ($user->rights->produit->transfer_status && ($user->fk_warehouse || $user->admin));


$draft_count = 0;

if ($canApprove) {
	$sql = 'SELECT COUNT(rowid) as count FROM ' . MAIN_DB_PREFIX . 'stock_mouvement_draft';
	if (!$user->admin) {
		$sql .= ' WHERE fk_entrepot = ' . $user->fk_warehouse;
	}
	if (!$res = $db->query($sql)) {
		dol_print_error($db);
		die();
	}
	if ($rw = $db->fetch_object($res)) {
		$draft_count = $rw->count;
	}
}
/*
 * Actions
 */

if ($action == 'addline') {
	function fetchBatches($db, $product_id, $warehouse_id)
	{
		$sql = "SELECT pb.rowid FROM " . MAIN_DB_PREFIX . "product_batch pb";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "product_stock ps ON pb.fk_product_stock = ps.rowid";
		$sql .= " WHERE ps.fk_product = $product_id AND ps.fk_entrepot = $warehouse_id";
		$sql .= " ORDER BY pb.batch DESC";
		return $db->query($sql);
	}

	function updateLotesArray($db, $resql, $listofdata, $id_product, $id_sw, $id_tw)
	{
		$lotes = [];
		$batch_list = new Productbatch($db);

		while ($fesql = $db->fetch_object($resql)) {
			$batch_list->fetch($fesql->rowid);
			$lotes[] = [
				'lotid' => $batch_list->lotid,
				'batch' => $batch_list->batch,
				'qty' => $batch_list->qty,
				'eatby' => $batch_list->eatby,
				'sellby' => $batch_list->sellby
			];
		}

		foreach ($listofdata as $val) {
			if ($val['id_product'] == $id_product && $val['id_sw'] == $id_sw && $val['id_tw'] == $id_tw) {
				$transferqty = $val['qty'];
				foreach ($lotes as $key2 => $val2) {
					if ($val2['lotid'] == $val['batch']) {
						$lotes[$key2]['qty'] = $val2['qty'] - $transferqty;
						if ($lotes[$key2]['qty'] <= 0) {
							unset($lotes[$key2]);
						}
					}
				}
			}
		}
		return $lotes;
	}

	if ($searchproduct && !$id_product) {
		$batch_data = new Productbatch($db);
		$response = $batch_data->fetch(0, $searchproduct, $id_sw);

		if ($response > 0) {
			$id_product = $batch_data->fk_product;
			$batch = $batch_data->lotid;
			$eatby = $batch_data->eatby;
			$sellby = $batch_data->sellby;
			$reel = $batch_data->qty;

			$transferqty = array_reduce($listofdata, function ($carry, $item) use ($id_product, $id_sw, $id_tw, $batch) {
				return ($item['id_product'] == $id_product && $item['id_sw'] == $id_sw && $item['id_tw'] == $id_tw && $item['batch'] == $batch) ? $item['qty'] : $carry;
			}, 0);

			if ($reel < $transferqty + 1) {
				$prod_lot = new Productlot($db);
				$prod_lot->fetch($searchproduct);
				
				$resql = fetchBatches($db, $prod_lot->fk_product, $id_sw);
				
				if ($db->num_rows($resql) > 0) {
					$lotes = updateLotesArray($db, $resql, $listofdata, $id_product, $id_sw, $id_tw);
					if (count($lotes) > 0) {
						$action = '';
						$error++;
					} else {
						$error++;
						setEventMessage($langs->trans("No se encontraron mas lotes de este producto"), 'errors');
					}
				} else {
					$error++;
					setEventMessage($langs->trans("Error al agregar el lote, verifique las existencias"), 'errors');
				}
			} else {
				setEventMessage("Producto agregado correctamente", 'mesgs');
			}
			$qty = max($qty, 1);
		} else {
			$prod_lot = new Productlot($db);
			$result = $prod_lot->fetch($searchproduct);
			if ($result) {
				$id_product = $prod_lot->fk_product;

				$resql = fetchBatches($db, $prod_lot->fk_product, $id_sw);

				if ($db->num_rows($resql) > 0) {
					$lotes = updateLotesArray($db, $resql, $listofdata, $id_product, $id_sw, $id_tw);
					$action = '';
					$error++;
				} else {
					$error++;
					setEventMessage($langs->trans("Error al agregar el lote, verifique las existencias"), 'errors');
				}
			} else {
				$error++;
				setEventMessage($langs->trans("No se encontraron lotes relacionados al codigo de barras"), 'errors');
			}
		}
	} elseif ($id_product && !$batch) {
		$resql = fetchBatches($db, $id_product, $id_sw);
	
		if ($db->num_rows($resql) > 0) {
			$lotes = updateLotesArray($db, $resql, $listofdata, $id_product, $id_sw, $id_tw);
			$action = '';
			$error++;
		} else {
			$error++;
			setEventMessage($langs->trans("No se encontraron lotes relacionados al codigo de barras"), 'errors');
		}
	}
	if (!($id_product > 0)) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), 'errors');
	}
	if (!($id_sw > 0)) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), 'errors');
	}
	if (!($id_tw > 0)) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), 'errors');
	}
	if ($id_sw > 0 && $id_tw == $id_sw) {
		$error++;
		$langs->load("errors");
		setEventMessage($langs->trans("ErrorWarehouseMustDiffers"), 'errors');
	}
	if (!$qty && $batch) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Qty")), 'errors');
	}
	if ($error == 0) {
		$sql = "SELECT reel FROM " . MAIN_DB_PREFIX . "product_stock WHERE fk_product=" . $id_product;
		$stocksource = $stocktarget = 0;

		// Get stock source
		$resql = $db->query($sql . " AND fk_entrepot=" . $id_sw);
		if ($obj = $db->fetch_object($resql)) {
			$stocksource = $obj->reel ? $obj->reel : 0;
		}

		// Get stock target
		$resql = $db->query($sql . " AND fk_entrepot=" . $id_tw);
		if ($obj = $db->fetch_object($resql)) {
			$stocktarget = $obj->reel ? $obj->reel : 0;
		}

		if ($stocksource == 0) {
			$error++;
			setEventMessage($langs->trans("Error almacen origen  en 0"), 'errors');
		}

		if (($stocksource - $qty) < 0) {
			$error++;
			if ($stocksource > 0) {
				setEventMessage("Solo se pueden mover " . $stocksource . " productos", 'errors');
			}
		}
	}

	if (!$error) {
		$id = (count(array_keys($listofdata)) > 0) ? max(array_keys($listofdata)) + 1 : 1;
		//Comprobamos si ya existe la linea del mismo producto y lote
		if (count($listofdata) > 0) {
			$same = 0;
			foreach ($listofdata as $key => $val) {
				if ($val['id_product'] == $id_product && $val['id_sw'] == $id_sw && $val['id_tw'] == $id_tw && $val['batch'] == $batch) {
					$same++;
					$idclone = $key;
				}
			}
			if ($same > 0) {
				$listofdata[$idclone]['qty'] += $qty;
			} else {
				$listofdata[$id] = ['id' => $id, 'id_product' => $id_product, 'qty' => $qty, 'id_sw' => $id_sw, 'id_tw' => $id_tw, 'batch' => $batch, 'eatby' => $eatby, 'sellby' => $sellby];
			}
		} else {
			$listofdata[$id] = ['id' => $id, 'id_product' => $id_product, 'qty' => $qty, 'id_sw' => $id_sw, 'id_tw' => $id_tw, 'batch' => $batch, 'eatby' => $eatby, 'sellby' => $sellby];
		}
		$_SESSION['massstockmove'] = json_encode($listofdata);
		unset($id_product);
	}
}

if ($action == 'addlotes') {
	$id = count($listofdata) > 0 ? max(array_keys($listofdata)) + 1 : 1;
	$batch = GETPOST('batch');
	$qty = GETPOST('qty');
	$maxqty = GETPOST('maxqty');
	$eatby = GETPOST('eatby');
	$sellby = GETPOST('sellby');
	$batchqty = array();

	// Create batch quantity array
	foreach ($batch as $key => $val) {
		$batchqty[] = ['batch' => $val, 'maxqty' => $maxqty[$key] + $qty[$key], 'qty' => $qty[$key], 'eatby' => $eatby[$key], 'sellby'=> $sellby[$key]];
	}

	// Process each batch quantity
	foreach ($batchqty as $key => $val) {
		if ($val['qty'] <= 0)
			continue;

		$idclone = null;

		// Check if the current batch quantity already exists in the list of data
		foreach ($listofdata as $data => $value) {
			if ($value['id_product'] == $id_product && $value['id_sw'] == $id_sw && $value['id_tw'] == $id_tw && $value['batch'] == $val['batch']) {
				$idclone = $data;
				break;
			}
		}

		// If the current batch quantity already exists in the list of data
		if ($idclone !== null) {
			if ($val['qty'] + $listofdata[$idclone]['qty'] <= $val['maxqty']) {
				$listofdata[$idclone]['qty'] += $val['qty'];
			} else {
				setEventMessage("La cantidad sobrepasa el maximo permitido", 'errors');
				$listofdata[$idclone]['qty'] = $val['maxqty'];
			}
		} else {
			// If the current batch quantity does not exist in the list of data, add it
			$listofdata[$id] = ['id' => $id, 'id_product' => $id_product, 'qty' => $val['qty'], 'id_sw' => $id_sw, 'id_tw' => $id_tw, 'batch' => $val['batch'], 'eatby' => $val['eatby'], 'sellby' => $val['sellby']];
			$id = max(array_keys($listofdata)) + 1;
		}
	}

	$_SESSION['massstockmove'] = json_encode($listofdata);
	unset($id_product);
}

if ($action == 'delline' && $idline != '') {
	if (!empty($listofdata[$idline]))
		unset($listofdata[$idline]);
	if (count($listofdata) > 0)
		$_SESSION['massstockmove'] = json_encode($listofdata);
	else
		unset($_SESSION['massstockmove']);
}

if ($action == 'createmovements') {
	//REGISTRAR TRANSFERENCIA
	$error = 0;
	$error_message = '';

	if (!GETPOST("label")) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired"), $langs->transnoentitiesnoconv("MovementLabel"), null, 'errors');
	}

	if (count($listofdata) == 0) {
		$error++;
		header("Location: " . $_SERVER["PHP_SELF"]);
	}

	// Add a file-based lock to prevent simultaneous stock transfers
	$lockFile = DOL_DATA_ROOT . '/stockmove_lock_' . session_id() . '.lock';
	
	// Try to acquire a lock
	$fp = fopen($lockFile, 'w+');
	if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
		// Could not get the lock, another process is already transferring
		setEventMessages($langs->trans("TransferInProgress"), null, 'warnings');
		if ($fp) fclose($fp);
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}

	$db->begin();

	if (!$error) {
		$product = new Product($db);
		$lote = new Productlot($db);

		// Consolida lineas duplicadas del mismo producto/lote/almacenes para que cada
		// combinacion genere un solo movimiento y un solo borrador (si quedan separadas,
		// el ticket y la recepcion las tratan como una sola pieza).
		// La clave usa el NOMBRE del lote y no su rowid: llx_product_lot puede tener
		// filas duplicadas del mismo lote y cada escaneo puede resolver un rowid distinto
		$consolidated = array();
		foreach ($listofdata as $val) {
			$lotname = $val['batch'];
			if ($val['batch'] > 0 && $lote->fetch($val['batch']) > 0) {
				$lotname = $lote->batch;
			}
			$k = $val['id_product'] . '-' . $val['id_sw'] . '-' . $val['id_tw'] . '-' . $lotname;
			if (isset($consolidated[$k])) {
				$consolidated[$k]['qty'] += $val['qty'];
			} else {
				$consolidated[$k] = $val;
			}
		}
		$listofdata = array_values($consolidated);

		foreach ($listofdata as $val) {
			$id_product = $val['id_product'];
			$id_sw = $val['id_sw'];
			$id_tw = $val['id_tw'];
			$qty = price2num($val['qty']);
			$batch = $val['batch'];
			$dlc = $val['eatby'];
			$dluo = $val['sellby'];

			// Validaciones para que no se pueda registrar una transferencia con un producto que no existe
			if(!$id_product){
				$error++;
				$error_message = "No se ha seleccionado un producto";
			}

			if($id_sw < 0){	
				$error++;
				$error_message = "No se ha seleccionado un almacen de origen";
			}

			if($id_tw < 0){
				$error++;
				$error_message = "No se ha seleccionado un almacen de destino";
			}

			if($id_sw == $id_tw){
				$error++;
				$error_message = "El almacen de origen y destino no pueden ser el mismo";
			}

			$product->fetch($id_product);

			$lote->fetch($batch);

			// Si el producto es del CEDIS, se verifica que haya stock suficiente
			if ($id_sw == $conf->global->CEDIS_WAREHOUSE) {
				$product->load_stock();
				$physical_stock = $product->stock_warehouse[$id_sw]->real;
				$product->load_stats_commande(0, '1,2', 1, $id_sw);
				$stock_to_deliver = $product->stats_commande['qty'];
				$product->load_stats_sending(0, '1,2', 1, '', $id_sw);
				$stock_sent = $product->stats_expedition['qty'];
				$realstock = $physical_stock - ($stock_to_deliver - $stock_sent);

				if ($realstock < $qty) {
					$error++;
					setEventMessage("Hay pedidos pendientes por surtir del {$product->ref} en el CEDIS", 'errors');
				}
			} else {
				$product->load_stock('novirtual');
			}

			if ($error == 0 && $id_sw !== $id_tw && is_numeric($qty) && !empty($id_product)) {
				$label = GETPOST("label");
				$codemove = GETPOST("codemove");

				$response = $product->correct_stock_batch($user, $id_sw, $qty, 1, $label, 0, $dlc, $dluo, $lote->batch, $codemove);

				$result = $product->create_draft_movement($user, $id_sw, $id_tw, $qty, 1, $label, $dlc, $dluo, $lote->batch, $codemove);

				if ($result < 0 || $response < 0) {
					setEventMessage("Error al registrar la transferencia del producto {$product->ref} del lote {$lote->batch}", 'errors');
					$error++;
					break;
				}
				sendUpdateStockNotification($db, $id_product, $id_sw);
			} else {
				$error++;
				setEventMessage($error_message, 'errors');
			}
		}
	}

	if (!$error) {
		unset($_SESSION['massstockmove']);

		$db->commit();
		
		// Release the lock
		if ($fp) {
			flock($fp, LOCK_UN);
			fclose($fp);
			@unlink($lockFile);
		}
		
		if ($draft) {
			setEventMessage($langs->trans("Movimiento guardado y el espera de autorización"), 'mesgs');
		} else {
			setEventMessage($langs->trans("StockMovementRecorded"), 'mesgs');
		}

		//PARA VALIDAR AUTOMATICAMENTE
		$chbxs = array();
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "stock_mouvement_draft WHERE fk_user = " . $user->id . "  AND label = '" . GETPOST("label") . "' AND code = '" . GETPOST("codemove") . "' AND qty < 0";
		$resql = $db->query($sql);

		if ($resql) {
			while ($rowT = $db->fetch_object($resql)) {
				$chbxs[] = $rowT->rowid;
			}
		}

		$_SESSION['traspasoAuto'] = $chbxs;

		header("Location: " . DOL_URL_ROOT . '/product/stock/movement_list_draft.php?action=printpdfcorrection&codetosearch=' . GETPOST("codemove"));
		exit;
	} else {
		$db->rollback();
		
		// Release the lock on error too
		if ($fp) {
			flock($fp, LOCK_UN);
			fclose($fp);
			@unlink($lockFile);
		}
		
		setEventMessages($error_message, 'errors');
	}
}




/*
 * View
 */

$now = dol_now('tzuser');

$form = new Form($db);
$formproduct = new FormProduct($db);
$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);
$warehousestatict = new Entrepot($db);
$lot = new Productlot($db);

$title = $langs->trans('MassMovement');

llxHeader('', $title);
print_barre_liste($langs->trans("MassStockTransferShort"), 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, '', 'generic', 0, $actionToCron, '', -1, 1, 1);

$titletoadd = $langs->trans("Select");
$buttonrecord = $langs->trans("RecordMovement");
$titletoaddnoent = $langs->transnoentitiesnoconv("Select");
$buttonrecordnoent = $langs->transnoentitiesnoconv("RecordMovement");
if ($canApprove && $draft_count) {
	echo '<div>';
	echo '<div style="width:70%;display:inline-block;vertical-align:top;">';
	print '<span class="opacitymedium">' . $langs->trans("SelectProductInAndOutWareHouse", $titletoaddnoent, $buttonrecordnoent) . '</span><br>';
	echo '</div>';
	echo '<div style="width:29%;display:inline-block;vertical-align:top;float:right;text-align:right;">';
	echo '<a class="btnTitle classfortooltip" '
		. 'title="' . 'Hay ' . $draft_count . ' trasferencias por aprobar' . '" '
		. 'href="' . DOL_URL_ROOT . '/product/stock/movement_list_draft.php" '
		. 'target="_blank" '
		. 'style="width:209px;">'
		. '<span class="fa fa-arrow-circle-down valignmiddle btnTitle-icon"></span>'
		. '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'
		. 'Trasferencias por aprobar (' . $draft_count . ')'
		. '</span>'
		. '</a>';
	echo '</div>';
	echo '</div>';
} else {
	print '<span class="opacitymedium">' . $langs->trans("SelectProductInAndOutWareHouse", $titletoaddnoent, $buttonrecordnoent) . '</span><br>';
}
if (count($lotes) > 0) {
	// Se muestra un dialog box con los lotes disponibles
	print '<script>
	$(document).ready(function() {
		if ($("#productid").val() != 0) {	
			$("#prodExpiration_dialog").dialog({
				autoOpen: false,
				height: 400,
				width: 600,
				modal: true
			});
			$("#prodExpiration_dialog").dialog("open");
		}
	});
	</script>';
}

// Dialogo para agregar lotes
print '<div hidden id="prodExpiration_dialog" name="prodExpiration_dialog" title="Fecha de caducidad">';
print '<div class="center">';
if (count($lotes) > 0) {
	$product = new Product($db);
	$product->fetch($id_product);

	if ($id_sw == $conf->global->CEDIS_WAREHOUSE) {
		// Load stock for the product
		$product->load_stock();

		// Get physical stock
		$physical_stock = $product->stock_warehouse[$id_sw]->real;

		// Load stats for the product
		$product->load_stats_commande(0, '1,2', 1, $id_sw);

		// Get stock to deliver
		$stock_to_deliver = $product->stats_commande['qty'];

		// Load sending stats for the product
		$product->load_stats_sending(0, '1,2', 1, '', $id_sw);

		// Get stock sent
		$stock_sent = $product->stats_expedition['qty'];

		// Calculate remain stock
		$realstock = $physical_stock - ($stock_to_deliver - $stock_sent);
	}
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="formprod">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="addlotes">';
	print '<input type="hidden" name="productid" value="' . $id_product . '">';
	print '<input type="hidden" name="id_sw" value="' . $id_sw . '">';
	print '<input type="hidden" name="id_tw" value="' . $id_tw . '">';
	print '<table class="liste centpercent" id="lotes_lines">';
	print '<tr>';
	print '<td colspan="5"><b>Stock disponible para enviar: ' . $realstock . ' </b></td></tr>';
	print '<tr>';
	print '<th>Lote</th>';
	print '<th>Cantidad disponible</th>';
	print '<th>Fecha de caducidad</th>';
	print '<th>Fecha de ingreso</th>';
	print '<th>Cantidad a trasladar</th>';
	print '</tr>';
	foreach ($lotes as $key => $val) {
		if ($val['qty'] <= 0)
			continue;
		print '<tr>';
		print '<td>' . $val['batch'] . '</td>';
		print '<td>' . $val['qty'] . '</td>';
		print '<td>' . dol_print_date($val['eatby'], 'dayrfc') . '</td>';
		print '<td>' . dol_print_date($val['sellby'], 'dayrfc') . '</td>';
		print '<td><input type="number" name="qty[]" value="0" min="0" max="' . $val['qty'] . '"></td>';
		print '<input type="hidden" name="batch[]" value="' . $val['lotid'] . '">';
		print '<input type="hidden" name="maxqty[]" value="' . $val['qty'] . '">';
		print '<input type="hidden" name="eatby[]" value="' . $val['eatby'] . '">';
		print '<input type="hidden" name="sellby[]" value="' . $val['sellby'] . '">';
		print '</tr>';
	}
	print '</table>';
	print '<input type="submit" class="button" name="addlotes" value="Anadir lotes">';
	print '</form>';
} else {
	// Si no hay lotes disponibles, se muestra un mensaje en modo de alerta
	print '<div class="error">';
	print '<p>No hay lotes disponibles para este producto</p>';
	print '</div>';
}
print '</div>';
print '</div>';

print '<br>' . "\n";

// Form to add a line
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="addline">';
print '<input type="hidden" name="draft" value="' . $draft . '">';


print '<div class="div-table-responsive-no-min">';
print '<table class="liste centpercent">';
//print '<div class="tagtable centpercent">';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', 'colspan="5"', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print '</tr>';
// In warehouse
print '<tr>';
print '<td>';
print $formproduct->selectWarehouses($id_sw, 'id_sw', 'warehouseopen,warehouseinternal', 1, $id_sw > 0 ? 1 : 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200', '', 1, false, 'e.ref', !$user->rights->stock->show_all_warehouses ? true : false);
print '</td>';
// Out warehouse
print '<td colspan="5">';
print $formproduct->selectWarehouses($id_tw, 'id_tw', 'warehouseopen,warehouseinternal', 1, $id_tw > 0 ? 1 : 0, 0, '', 0, 0, array(), 'minwidth200imp maxwidth200');
print '</td>';
print '</tr>';

$param = '';

print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('ProductRef'), 0, $_SERVER["PHP_SELF"], '', $param, '', 'colspan="3"', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList('', 0, '', '', '', '', 'colspan="3"');
print '</tr>';


print '<tr class="oddeven">';
// Product
print '<td class="titlefield" colspan="3">';
$filtertype = 0;
if (!empty($conf->global->STOCK_SUPPORTS_SERVICES))
	$filtertype = '';
if ($conf->global->PRODUIT_LIMIT_SIZE <= 0) {
	$limit = '';
} else {
	$limit = $conf->global->PRODUIT_LIMIT_SIZE;
}

$form->select_produits($id_product, 'productid', $filtertype, $limit, 0, -1, 2, '', 0, array(), 0, '1', 0, 'minwidth200imp maxwidth300', 1);
print '</td>';
print '<td class="right" colspan="3"><input type="submit" class="button" name="addline" value="' . dol_escape_htmltag($titletoadd) . '"></td>';
print '</tr>';
print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('ProductRef'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Batch'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print getTitleFieldOfList('', 0);
print '</tr>';
//cambiamos el orden de listofdata para poner los lotes mas recientes primero por id 
usort($listofdata, function($a, $b) {
	return $b['id'] - $a['id'];
});

$total_lotes = 0;
foreach ($listofdata as $key => $val) {
	$total_lotes += $val['qty'];
}

print '<tr class="oddeven">';
print '<td colspan="4"></td>';
print '<td class="center">' . $total_lotes . '</td>';
print '<td colspan="2"></td>';
print '</tr>';
foreach ($listofdata as $key => $val) {
	$productstatic->fetch($val['id_product']);
	$warehousestatics->fetch($val['id_sw']);
	$warehousestatict->fetch($val['id_tw']);
	$lot->fetch($val['batch']);

	print '<tr class="oddeven">';
	print '<td>';
	print $productstatic->getNomUrl(1) . ' - ' . $productstatic->label;
	print '</td>';
	print '<td>';
	print $lot->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $warehousestatics->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $warehousestatict->getNomUrl(1);
	print '</td>';
	print '<td class="center">' . $val['qty'] . '</td>';
	print '<td class="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=delline&idline=' . $val['id'] . '&draft=' . $draft . '">' . img_delete($langs->trans("Remove")) . '</a></td>';
	print '</tr>';
}

print '</table>';
print '</div>';

print '</form>';


print '<br>';


print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="formulaire2" id="formMassStock">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="createmovements">';
print '<input type="hidden" name="draft" value="' . $draft . '">';

// Button to record mass movement
$codemove = (isset($_POST["codemove"]) ? GETPOST("codemove", 'alpha') : dol_print_date($now, '%Y%m%d%H%M%S'));
$labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("StockTransfer") . ' ' . dol_print_date($now, '%Y-%m-%d %H:%M');

print '<table class="noborder centpercent">';
print '<tr>';
print '<td class="titlefield fieldrequired">' . $langs->trans("InventoryCode") . '</td>';
print '<td>';
print '<input type="hidden" name="codemove" value="' . dol_escape_htmltag($codemove) . '">';
print '</td>';
print '</tr>';
print '<tr>';
print '<td>' . $langs->trans("MovementLabel") . '</td>';
print '<td>';
print '<input type="text" name="label" class="quatrevingtpercent" value="' . dol_escape_htmltag($labelmovement) . '">';
print '</td>';
print '</tr>';
print '</table><br>';

// Add the processing overlay
print '
<div id="processingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:9999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background-color:white; padding:20px; border-radius:5px; text-align:center;">
        <div class="loader" style="border: 16px solid #f3f3f3; border-top: 16px solid #3498db; border-radius: 50%; width: 80px; height: 80px; margin:0 auto; animation: spin 2s linear infinite;"></div>
        <div id="processingText" style="margin-top:15px; font-weight:bold;">'.$langs->trans("ProcessingRequest").'<br>'.$langs->trans("PleaseWait").'</div>
    </div>
</div>
<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>';

print '<div class="center"><input class="button" type="button" id="btnAccept" name="valid" value="' . dol_escape_htmltag($buttonrecord) . '"></div>';

print '</form>';

//Alerta de Confirmación para Registrar Transferencia
print '<script>
	$(document).ready(function () {
		var input = document.getElementById("search_productid");
		input.focus();
		input.select();
		
		$( "#btnAccept" ).click(function() {
			if (confirm("¿Está seguro de querer Registrar esta Transferencia?")) {
				// Show processing overlay
				document.getElementById("processingOverlay").style.display = "block";
				$("#formMassStock").submit();
			}
		});
	});

	function showProcessingMessage() {
		document.getElementById("processingOverlay").style.display = "block";
		document.getElementById("processingText").innerHTML = "'.$langs->trans("ProcessingRequest").'<br>'.$langs->trans("PleaseWait").'";
	}
</script>';


// End of page
llxFooter();
$db->close();
