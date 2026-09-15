<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry	  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   \file       htdocs/product/note.php
 *   \brief      Tab for notes on products
 *   \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productstockentrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/functions.php';

// Load translation files required by the page
$langs->loadlangs(array('products', 'orders', 'bills', 'stocks', 'sendings', 'productbatch'));

$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$stocklimit = GETPOST('seuil_stock_alerte');
$desiredstock = GETPOST('desiredstock');
$cancel = GETPOST('cancel', 'alpha');
$fieldid = isset($_GET["ref"]) ? 'ref' : 'rowid';
$d_eatby = dol_mktime(0, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
$d_sellby = dol_mktime(0, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
$pdluoid = GETPOST('pdluoid', 'int');
$batchnumber = GETPOST('batch_number', 'san_alpha');
if (!empty($batchnumber)) {
	$batchnumber = trim($batchnumber);
}

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'thirdpartylist';

if ($contextpage == 'poslist') {
	$_GET['optioncss'] = 'print';
}

//Busqueda
$search_batch = GETPOST('search_batch', 'alpha');
$search_entrepot = GETPOST('search_entrepot', 'alpha');

$fecha = GETPOST('search_date');
$search_date = substr($fecha, 6, 4) . '' . substr($fecha, 3, 2) . '' . substr($fecha, 0, 2);


$date_eatby_day = GETPOST("date_eatby_day");
$date_eatby_month = GETPOST("date_eatby_month");
$date_eatby_year = GETPOST("date_eatby_year");

$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

if (!$sortfield)
	$sortfield = '';
if (!$sortorder)
	$sortorder = '';

// Security check
if ($user->socid)
	$socid = $user->socid;
$result = restrictedArea($user, 'produit&stock', $id, 'product&product', '', '', $fieldid);


$object = new Product($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
}

if (empty($id) && !empty($object->id))
	$id = $object->id;

$modulepart = 'product';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('stockproduct', 'card', $canvas);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('stockproductcard', 'globalcard'));

$arrayfields = array(
	'e.ref' => array('label' => 'Warehouse', 'checked' => 1),
	't.batch' => array('label' => 'Batch', 'checked' => 1),
	'sellby' => array('label' => 'SellByDate', 'checked' => 1),
	'eatby' => array('label' => 'EatByDate', 'checked' => 1),
	't.qty' => array('label' => 'Qty', 'checked' => 1),
);

/*
 * Actions
 */

if ($cancel)
	$action = '';

$parameters = array('id' => $id, 'ref' => $ref, 'objcanvas' => $objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Correct stock
if ($action == "correct_stock" && !$cancel) {
	if (!(GETPOST("id_entrepot") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'correction';
	}
	if (!GETPOST("nbpiece")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'correction';
	}

	$object = new Product($db);
	$result = $object->fetch($id);

	if ($object->hasbatch() && !$batchnumber) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
		$error++;
		$action = 'correction';
	}
	if (!GETPOST('eatby') && !GETPOST('eatbymonth') && !GETPOST('eatbyday') && !GETPOST('eatbyyear') || !GETPOST('sellby') && !GETPOST('sellbymonth') && !GETPOST('sellbyday') && !GETPOST('sellbyyear')) {
		if (!GETPOST('eatby')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("EatByDate")), null, 'errors');
		}
		if (!GETPOST('sellby')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("SellByDate")), null, 'errors');
		}
		$error++;
		$action = 'correction';
	} elseif (
		(GETPOST('eatby') && !checkdate($_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear'])) ||
		(GETPOST('sellby') && !checkdate($_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']))
	) {
		setEventMessages($langs->trans('Formato de fecha incorrecto'), [], 'errors');
		$error++;
		$action = 'correction';
	}

	if (!$error) {
		$priceunit = price2num(GETPOST("unitprice"));
		if (is_numeric(GETPOST("nbpiece")) && $id) {
			$origin_element = '';
			$origin_id = null;

			if (GETPOST('projectid', 'int')) {
				$origin_element = 'project';
				$origin_id = GETPOST('projectid', 'int');
			}

			if (empty($object)) {
				$object = new Product($db);
				$result = $object->fetch($id);
			}
			$result = $object->correct_stock_batch(
				$user,
				GETPOST("id_entrepot"),
				GETPOST("nbpiece"),
				GETPOST("mouvement"),
				GETPOST("label", 'san_alpha'),
				$priceunit,
				$d_eatby,
				$d_sellby,
				$batchnumber,
				GETPOST('inventorycode'),
				$origin_element,
				$origin_id
			); // We do not change value of stock for a correction

			if ($result > 0) {
				sendUpdateStockNotification($db,$object->id, GETPOST("id_entrepot"));

				if ($backtopage) {
					header("Location: " . $backtopage);
					exit;
				} else {
					header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $object->id);
					exit;
				}
			} else {
				sendUpdateStockNotification($db,$object->id, GETPOST("id_entrepot"));
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'correction';
			}
		}
	}
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && !$cancel) {
	if (!(GETPOST("id_entrepot", 'int') > 0) || !(GETPOST("id_entrepot_destination", 'int') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (!GETPOST("nbpiece", 'int')) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (GETPOST("id_entrepot", 'int') == GETPOST("id_entrepot_destination", 'int')) {
		setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
		$error++;
		$action = 'transfert';
	}

	$object = new Product($db);
	$result = $object->fetch($id);

	if ($object->hasbatch() && !$batchnumber) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (!GETPOST('eatby') && !GETPOST('eatbymonth') && !GETPOST('eatbyday') && !GETPOST('eatbyyear') || !GETPOST('sellby') && !GETPOST('sellbymonth') && !GETPOST('sellbyday') && !GETPOST('sellbyyear')) {
		if (!GETPOST('eatby')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("EatByDate")), null, 'errors');
		}
		if (!GETPOST('sellby')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("SellByDate")), null, 'errors');
		}
		$error++;
		$action = 'transfert';
	} elseif (
		(GETPOST('eatby') && !checkdate($_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear'])) ||
		(GETPOST('sellby') && !checkdate($_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']))
	) {
		setEventMessages($langs->trans('Formato de fecha incorrecto'), [], 'errors');
		$error++;
		$action = 'transfert';
	}

	if (!$error) {
		if ($id) {
			$object = new Product($db);
			$result = $object->fetch($id);

			$db->begin();

			$object->load_stock('novirtual'); // Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc = 0;
			if (isset($object->pmp))
				$pricesrc = $object->pmp;
			$pricedest = $pricesrc;

			$pdluo = new Productbatch($db);

			if ($pdluoid > 0) {
				$result = $pdluo->fetch($pdluoid);
				if ($result) {
					$srcwarehouseid = GETPOST('id_entrepot', 'int');
					$batch = $pdluo->batch;
					$eatby = $pdluo->eatby;
					$sellby = $pdluo->sellby;
				} else {
					setEventMessages($pdluo->error, $pdluo->errors, 'errors');
					$error++;
				}
			} else {
				$srcwarehouseid = GETPOST('id_entrepot', 'int');
				$batch = $batchnumber;
				$eatby = $d_eatby;
				$sellby = $d_sellby;
			}

			if (!$error) {
				// Remove stock
				$result1 = $object->correct_stock_batch(
					$user,
					$srcwarehouseid,
					GETPOST("nbpiece", 'int'),
					1,
					GETPOST("label", 'san_alpha'),
					$pricesrc,
					$eatby,
					$sellby,
					$batch,
					GETPOST('inventorycode')
				);
				if ($result1 < 0)
					$error++;
			}
			if (!$error) {
				// Add stock
				$result2 = $object->correct_stock_batch(
					$user,
					GETPOST("id_entrepot_destination", 'int'),
					GETPOST("nbpiece", 'int'),
					0,
					GETPOST("label", 'san_alpha'),
					$pricedest,
					$eatby,
					$sellby,
					$batch,
					GETPOST('inventorycode')
				);
				if ($result2 < 0)
					$error++;
			}

			if (!$error && $result1 >= 0 && $result2 >= 0) {
				$db->commit();
				sendUpdateStockNotification($db, $object->id, GETPOST("id_entrepot"));
				sendUpdateStockNotification($db, $object->id, GETPOST("id_entrepot_destination"));

				if ($backtopage) {
					header("Location: " . $backtopage);
					exit;
				} else {
					header("Location: batchexp.php?id=" . $object->id);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
				$action = 'transfert';
			}
		}
	}
}

// Update batch information
if ($action == 'updateline' && GETPOST('save') == $langs->trans('Save')) {
	$pdluo = new Productbatch($db);
	$result = $pdluo->fetch(GETPOST('pdluoid', 'int'));

	if ($result > 0) {
		if ($pdluo->id) {
			if ((!GETPOST("sellby")) && (!GETPOST("eatby")) && (!$batchnumber)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("atleast1batchfield")), null, 'errors');
			} else {
				$d_eatby = dol_mktime(0, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
				$d_sellby = dol_mktime(0, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
				$pdluo->batch = $batchnumber;
				$pdluo->eatby = $d_eatby;
				$pdluo->sellby = $d_sellby;
				$result = $pdluo->update($user);
				if ($result < 0) {
					setEventMessages($pdluo->error, $pdluo->errors, 'errors');
				}
			}
		} else {
			setEventMessages($langs->trans('BatchInformationNotfound'), null, 'errors');
		}
	} else {
		setEventMessages($pdluo->error, null, 'errors');
	}
	header("Location: product.php?id=" . $id);
	exit;
}


if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_batch = '';
	$search_warehouse = '';
	$date_eatby_day = '';
	$date_eatby_month = '';
	$date_eatby_year = '';
}

/*
 *	View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

if ($id > 0 || $ref) {
	$object = new Product($db);
	$result = $object->fetch($id, $ref);

	$sql1 = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut, ps.reel, ps.rowid as product_stock_id, p.pmp";
	$sql1 .= " FROM " . MAIN_DB_PREFIX . "entrepot as e,";
	$sql1 .= " " . MAIN_DB_PREFIX . "product_stock as ps";
	$sql1 .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = ps.fk_product";
	$sql1 .= " WHERE ps.reel != 0";
	$sql1 .= " AND ps.fk_entrepot = e.rowid";
	$sql1 .= " AND e.entity IN (" . getEntity('stock') . ")";
	$sql1 .= " AND ps.fk_product = " . $object->id;
	if ($search_entrepot > 0)
		$sql1 .= natural_search('e.rowid', $search_entrepot);
	if ($sortfield == 't.qty')
		$sql1 .= " ORDER BY ps.reel $sortorder";
	else
		$sql1 .= " ORDER BY e.ref ASC";

	$variants = $object->hasVariants();

	$object->load_stock();

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label, 16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product') . " " . $shortlabel . " - " . $langs->trans('Stock');
		$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service') . " " . $shortlabel . " - " . $langs->trans('Stock');
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl);

	if ($result > 0) {
		$head = product_prepare_head($object);
		$titre = $langs->trans("CardProduct" . $object->type);
		$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

		if ($contextpage != 'poslist') {
			dol_fiche_head($head, 'batch_exp', $titre, -1, $picto);
		}
		dol_htmloutput_events();

		$linkback = '<a href="' . DOL_URL_ROOT . '/product/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		$shownav = 1;
		if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL)))
			$shownav = 0;

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

		dol_fiche_end();
	}
	// Correct stock
	if ($action == "correction") {
		include DOL_DOCUMENT_ROOT . '/product/stock/tpl/stockcorrection.tpl.php';
		print '<br><br>';
	}

	// Transfer of units
	if ($action == "transfert") {
		include DOL_DOCUMENT_ROOT . '/product/stock/tpl/stocktransfer.tpl.php';
		print '<br><br>';
	}
} else {
	dol_print_error();
}

$parameters = array();

if ($contextpage != 'poslist') {

	print "<div class=\"tabsAction\">\n";
	// Permiso para agregar/eliminar lote
	if ($user->rights->stock->modify_lot) {
		print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=correction&type=batch">Movimiento especial</a>';
	}

	// Permiso para transferir lote
	if ($user->rights->stock->transfer_lot) {
		print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=transfert&type=batch">Transferir lote</a>';
	}
	print '</div>';

}
/*
 * Stock detail (by warehouse). May go down into batch details.
 */
print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
if ($optioncss != '')
	print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';

print '<div class="div-table-responsive">';


print '<table class="noborder centpercent">';
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">';
print $formproduct->selectWarehouses('', "search_entrepot", '', 1, 0, 0, "");
print '</td>';
print '<td class="liste_titre" colspan="2">';
print '<input class="flat" type="text" name="search_batch" value="' . dol_escape_htmltag($search_batch) . '">';
print '</td>';
print '<td class="liste_titre">';
print $form->selectDate($pdluo->eatby, 'eatby', '', '', 1, '', 1, 0);
print '</td>';
print '<td class="liste_titre">';
print $form->selectDate($pdluo->sellby, 'sellby', '', '', 1, '', 1, 0);
print '</td>';
print '<td class="liste_titre right">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

$param = "&id=" . $object->id;
print '<tr class="liste_titre">';
print '<th class="liste_titre">Almacen</th>';
if (!empty($arrayfields['t.batch']['checked']))
	print_liste_field_titre($arrayfields['t.batch']['label'], $_SERVER["PHP_SELF"], 't.batch', '', $param, 'colspan="2"', $sortfield, $sortorder);
if (!empty($arrayfields['eatby']['checked']))
	print_liste_field_titre($arrayfields['eatby']['label'], $_SERVER["PHP_SELF"], 'eatby', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['sellby']['checked']))
	print_liste_field_titre($arrayfields['sellby']['label'], $_SERVER["PHP_SELF"], 'sellby', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['t.qty']['checked']))
	print_liste_field_titre($arrayfields['t.qty']['label'], $_SERVER["PHP_SELF"], 't.qty', '', $param, '', $sortfield, $sortorder);
print '</tr>';

$entrepotstatic = new Entrepot($db);
$product_lot_static = new Productlot($db);

$total = 0;
$totalvalue = $totalvaluesell = 0;

$resql = $db->query($sql1);
if ($resql) {
	$num = $db->num_rows($resql);
	$total = $totalwithpmp;
	$i = 0;
	$var = false;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$entrepotstatic->id = $obj->rowid;
		$entrepotstatic->ref = $obj->ref;
		$entrepotstatic->libelle = $obj->ref;
		$entrepotstatic->label = $obj->ref;
		$entrepotstatic->lieu = $obj->lieu;
		$entrepotstatic->fk_parent = $obj->fk_parent;
		$entrepotstatic->statut = $obj->statut;

		$stock_real = price2num($obj->reel, 'MS');
		print '<tr class="oddeven">';
		print '<td>' . $entrepotstatic->getNomUrl(1) . '</td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td>' . $stock_real . ($stock_real < 0 ? ' ' . img_warning() : '') . '</td>';
		print '</tr>';
		$total += $obj->reel;
		if (price2num($object->pmp))
			$totalwithpmp += $obj->reel;
		$totalvalue = $totalvalue + ($object->pmp * $obj->reel);
		$totalvaluesell = $totalvaluesell + ($object->price * $obj->reel);
		// Batch Detail (agrupado por mismo lote + caducidad)
		if ($object->hasbatch()) {
			$details = Productbatch::findAll($db, $obj->product_stock_id, 1, $object->id, $sortfield, $sortorder, $search_batch, $search_date);
			if ($details < 0)
				dol_print_error($db);

			$grouped = array();
			foreach ($details as $pdluo) {
				if ($search_batch !== '' && stripos($pdluo->batch, $search_batch) === false) {
					continue;
				}
				$eatby_key = !empty($pdluo->eatby) ? dol_print_date($pdluo->eatby, '%Y%m%d') : '';
				$key = $pdluo->batch.'|'.$eatby_key;
				if (!isset($grouped[$key])) {
					$grouped[$key] = $pdluo;
				} else {
					$grouped[$key]->qty += $pdluo->qty;
					if (empty($grouped[$key]->lotid) && !empty($pdluo->lotid)) {
						$grouped[$key]->lotid = $pdluo->lotid;
						$grouped[$key]->id = $pdluo->id;
					}
				}
			}

			foreach ($grouped as $pdluo) {
				$product_lot_static->id = $pdluo->lotid;
				$product_lot_static->batch = $pdluo->batch;
				$product_lot_static->eatby = $pdluo->eatby;
				$product_lot_static->sellby = $pdluo->sellby;

				print '<tr><td>';
				print img_picto($langs->trans("Tranfer"), 'uparrow', 'class="hideonsmartphone"') . ' ';
				if ($user->rights->stock->show_all_warehouses || $entrepotstatic->id == $user->fk_warehouse) {
					if ($user->rights->stock->transfer_lot) {
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;id_entrepot=' . $entrepotstatic->id . '&amp;action=transfert&amp;pdluoid=' . $pdluo->id . '&amp;lotid=' . $pdluo->lotid . '">' . $langs->trans("TransferStock") . '</a>';
					}
					print '&nbsp;-&nbsp;';
					if ($user->rights->stock->modify_lot) {
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;id_entrepot=' . $entrepotstatic->id . '&amp;action=correction&amp;pdluoid=' . $pdluo->id . '&amp;lotid=' . $pdluo->lotid . '">' . $langs->trans("Movimiento especial") . '</a>';
					}
				}
				print '</td>';

				// Lote
				print '<td>';
				print '<span>' . $product_lot_static->getNomUrl(1) . '</span>';
				print '</td>';

				// Codigo de barras
				print '<td>';
				print '<span><a href="' . DOL_URL_ROOT . '/product/pdf_tags.php?id=' . $id . '&batch=' . $pdluo->lotid . '" target="_blank"><input class="button" type="button" value="Código de barras"/></a></span>';
				print '</td>';

				// Fecha de caducidad
				$warningMessage = '';
				if ($pdluo->eatby <= $refdate) {
					if ($pdluo->eatby <= strtotime($currentDate)) {
						$warningMessage = img_warning("Lote caducado") . ' ';
					} else {
						$warningMessage = img_warning("Próximo a caducar") . ' ';
					}
				}
				print '<td>' . $warningMessage . dol_print_date($pdluo->eatby, 'day') . '</td>';

				// Fecha de ingreso
				print '<td>' . dol_print_date($pdluo->sellby, 'day') . '</td>';

				// Cantidad
				print '<td>' . $pdluo->qty . ($pdluo->qty < 0 ? ' ' . img_warning() : '') . '</td>';
				print '</tr>';
			}
		}
		$i++;
	}
} else
	dol_print_error($db);

// Total line
print '<tr class="liste_total">';
print '<td class="right liste_total" colspan="5">' . $langs->trans("Total") . ':</td>';
print '<td class="liste_total">' . price2num($total, 'MS') . '</td>';
print "</tr>";

print "</table>";
print '</div>';
print '</form>';

print '</div>';

// End of page
llxFooter();
$db->close();
