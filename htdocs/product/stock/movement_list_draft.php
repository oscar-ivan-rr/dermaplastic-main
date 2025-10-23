<?php
/* Copyright (C) 2001-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/product/stock/movement_list.php
 *	\ingroup    stock
 *	\brief      Page to list stock movements
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/functions.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));
if (!empty($conf->productbatch->enabled))
	$langs->load("productbatch");

// Security check
$canApprove = ($user->rights->produit->transfer_status && ($user->fk_warehouse || $user->admin));
if (!$canApprove) {
	if (!function_exists('accessforbidden')) {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
	}
	accessforbidden('No tiene acceso a esta función');
}


$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$msid = GETPOST('msid', 'int');
$product_id = GETPOST("product_id", 'int');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'movementlist';
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$backtopage = GETPOST('backtopage');

if (GETPOST('traspasoAuto') == 1) {
	$traspasoAuto = $_SESSION['traspasoAuto'];
	unset($_SESSION['traspasoAuto']);
}


foreach ($_POST as $k => $v) {
	if (substr($k, 0, 11) == 'custom_qty_') {
		$$k = $v;
		$rid = str_replace('custom_qty_', '', $k);
		$sql = 'UPDATE `llx_stock_mouvement_draft` SET `qty`=' . $v . ' WHERE `rowid`=' . $rid;
		$db->query($sql);
	}
}



$id = strlen($id) ? intval($id) : -1;

if ($id <= 0) {
	if (!$user->admin) {
		$id = $user->fk_warehouse;
	} else {
		$id = '';
	}
} elseif (!$user->admin && $id != $user->fk_warehouse) {
	$id = $user->fk_warehouse;
}




// Security check
//$result=restrictedArea($user, 'stock', $id, 'entrepot&stock');
//$result = restrictedArea($user, 'stock');

$idproduct = GETPOST('idproduct', 'int');
$year = GETPOST("year");
$month = GETPOST("month");
$search_ref = GETPOST('search_ref', 'alpha');
$search_movement = GETPOST("search_movement");
$search_product_ref = trim(GETPOST("search_product_ref"));
$search_product = trim(GETPOST("search_product"));
$search_warehouse_origin = trim(GETPOST("search_warehouse_origin"));
if (!$user->rights->stock->show_all_warehouses)
	$search_warehouse_destination = $user->fk_warehouse;
else
	$search_warehouse_destination = trim(GETPOST("search_warehouse_destination"));
$search_inventorycode = trim(GETPOST("search_inventorycode"));
$search_user = trim(GETPOST("search_user"));
$search_batch = trim(GETPOST("search_batch"));
$search_qty = trim(GETPOST("search_qty"));
$search_type_mouvement = GETPOST('search_type_mouvement', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : 500;
$page = GETPOST("page", 'int');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (!$sortfield)
	$sortfield = "m.datem";
if (!$sortorder)
	$sortorder = "DESC";

$pdluoid = GETPOST('pdluoid', 'int');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new MouvementStock($db);
$hookmanager->initHooks(array('movementlist'));
$extrafields = new ExtraFields($db);
$formfile = new FormFile($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$arrayfields = array(
	'm.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1),
	'origin' => array('label' => $langs->trans("Origin"), 'checked' => 1),
	'm.datem' => array('label' => $langs->trans("Date"), 'checked' => 1),
	'p.ref' => array('label' => $langs->trans("ProductRef"), 'checked' => 1, 'css' => 'maxwidth100'),
	'p.label' => array('label' => $langs->trans("ProductLabel"), 'checked' => 1),
	'm.warehouse_origin' => array('label' => $langs->trans("Almacen Origen"), 'checked' => 0),
	'm.warehouse_destination' => array('label' => $langs->trans("Almacen Destino"), 'checked' => 0),
	'm.batch' => array('label' => $langs->trans("BatchNumberShort"), 'checked' => 1, 'enabled' => (!empty($conf->productbatch->enabled))),
	'pl.eatby' => array('label' => $langs->trans("EatByDate"), 'checked' => 0, 'enabled' => (!empty($conf->productbatch->enabled))),
	'pl.sellby' => array('label' => $langs->trans("SellByDate"), 'checked' => 0, 'position' => 10, 'enabled' => (!empty($conf->productbatch->enabled))),
	'm.fk_user_author' => array('label' => $langs->trans("Author"), 'checked' => 0),
	'm.inventorycode' => array('label' => $langs->trans("InventoryCodeShort"), 'checked' => 0),
	'm.label' => array('label' => $langs->trans("MovementLabel"), 'checked' => 0),
	'm.qty' => array('label' => $langs->trans("Qty"), 'checked' => 1)
);

// Security check
if (!$user->rights->stock->mouvement->lire) {
	accessforbidden();
}

$permissiontoread = $user->rights->stock->mouvement->lire;
$permissiontoadd = $user->rights->stock->mouvement->creer;
$permissiontodelete = $user->rights->stock->mouvement->creer;	// There is no deletion permission for stock movement as we shoul dnever delete

$usercanread = $user->rights->stock->mouvement->lire;
$usercancreate = $user->rights->stock->mouvement->creer;
$usercandelete = $user->rights->stock->mouvement->creer;


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (
	!GETPOST('confirmmassaction', 'alpha')
	&& $massaction != 'presend'
	&& $massaction != 'confirm_presend'
	//	&&	$massaction != 'predelete'
//	&&	$massaction != 'confirm_predelete'
) {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	// Do we click on purge search criteria ?
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
	{
		$year = '';
		$month = '';
		$search_ref = '';
		$search_movement = "";
		$search_type_mouvement = "";
		$search_inventorycode = "";

		$search_barcode = "";

		$search_product_ref = "";
		$search_product = "";
		$search_warehouse_origin = "";
		$search_warehouse_destination = "";
		$search_user = "";
		$search_batch = "";
		$search_qty = '';
		$sall = "";
		$toselect = '';
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'MouvementStock';
	$objectlabel = 'MouvementStock';
	$uploaddir = $conf->stock->dir_output;
	include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
}

//Imprimir PDF
if ($action == 'printpdfcorrection') {
	$codetosearch = GETPOST('codetosearch');
	$dir = $conf->stock->dir_output . "/stock_correction/" . dol_sanitizeFileName($codetosearch);
	$cdir = scandir($dir);
	$file = "";
	foreach ($cdir as $key => $value) {
		if (substr($value, strlen($value) - 4) == ".pdf") {
			$file = $value;
		}
	}
	if ($file == "") {
		$sql = "SELECT p.ref as product_ref, p.label as product_label, p.rowid as product_id, SUM(m.qty) as qty,";
		$sql .= "  m.datem, e.lieu as entrepot_source_ref,";
		$sql .= " m.label,m.code";
		$sql .= " FROM " . MAIN_DB_PREFIX . "entrepot as e,";
		$sql .= " " . MAIN_DB_PREFIX . "product as p,";
		$sql .= " " . MAIN_DB_PREFIX . "stock_mouvement_draft as m";
		$sql .= " WHERE m.fk_product = p.rowid";
		$sql .= " AND m.fk_entrepot = e.rowid";
		$sql .= " AND e.entity IN (" . getEntity('stock') . ")";
		$sql .= " AND m.code = '" . $codetosearch . "'";
		$sql .= " GROUP BY p.rowid";
		$resql = $db->query($sql);
		$masivecorrection = array();
		if ($resql) {
			while ($obm = $db->fetch_object($resql)) {
				$line = new stdClass();
				$line->product_ref = $obm->product_ref;
				$line->product_label = $obm->product_label;
				$line->product_id = $obm->product_id;
				$line->qty = $obm->qty;
				$line->datem = $obm->datem;
				$line->entrepot_source_ref = $obm->entrepot_source_ref;
				if (strpos($obm->label, "(") === false) {
					$line->reason = '';
				} else {
					$start = strpos($obm->label, "(") + 1;
					$line->reason = substr($obm->label, $start, -1);
				}
				$line->label = $obm->label;
				$line->code = $obm->code;
				array_push($masivecorrection, $line);
			}


			//Aqui comenzar
			if (sizeof($masivecorrection) > 0) {
				require (DOL_DOCUMENT_ROOT . '/core/modules/stock/doc/pdf_draftmouvement.modules.php');
				$largo = 250 + (sizeof($masivecorrection) * 10);
				$doc = new PDFDraftMovement($db, $largo);
				$doc->write_file($masivecorrection, null, null);
				$dir = $conf->stock->dir_output . "/stock_correction/" . dol_sanitizeFileName($masivecorrection[0]->code);
				$cdir = scandir($dir);
				$file = "";
				foreach ($cdir as $key => $value) {
					if (substr($value, strlen($value) - 4) == ".pdf") {
						$file = $value;
					}
				}
			}
		}
	}
	print "<script> 
				window.open('" . DOL_URL_ROOT . "/document.php?modulepart=stock&attachment=0&file=stock_correction%2F" . urlencode(dol_sanitizeFileName($codetosearch)) . "%2F" . urlencode($file) . "&entity=1', '_blank');
			</script>";
	$action = '';
}

if ($action == 'rc_validate') {
	$chbxs = GETPOST('rc_chbx', 'array');
	foreach ($chbxs as $k => $v) {
		if (intval($v) != $v) {
			unset($chbxs[$k]);
		}
	}
	if (count($chbxs)) {
		$sql = 'SELECT * FROM `llx_stock_mouvement_draft` '
			. 'WHERE rowid IN (' . implode(',', $chbxs) . ')'
		;
		$res = $db->query($sql);
		if (!$res) {
			dol_print_error($db);
			die();
		}
		$row = $db->fetch_object($res);
		$todelete = array();
		while ($row) {
			$tst_prod = new Product($db);
			$tst_prod->fetch($row->fk_product);

			$pricesrc = 0;
			if (!empty($tst_prod->pmp))
				$pricesrc = $tst_prod->pmp;
			$move = $row->qty > 0 ? '0' : '1';
			$result1 = $tst_prod->correct_stock(
				$user,
				$row->fk_entrepot,
				abs($row->qty),
				$move,
				$row->label,
				$pricesrc,
				$row->code,
				$row->origin,
				$row->origin_id
			);
			if ($result1) {
				$todelete[] = $row->rowid;
				$codeAuto = $row->code;
			}

			$row = $db->fetch_object($res);
		}
		if (count($todelete)) {
			$sql = 'DELETE FROM `llx_stock_mouvement_draft` '
				. 'WHERE rowid IN (' . implode(',', $todelete) . ')'
			;
			if (!$db->query($sql)) {
				dol_print_error($db);
				die();
			}

			$dir = $conf->stock->dir_output . "/movement_draft/" . dol_sanitizeFileName($codeAuto);
			$cdir = scandir($dir);
			$file = "";
			foreach ($cdir as $key => $value) {
				if (substr($value, strlen($value) - 4) == ".pdf") {
					$file = $value;
				}
			}
			print "<script>
							if (confirm('Desea imprimir/descargar el PDF?')){ 
								window.open('" . DOL_URL_ROOT . "/document.php?modulepart=stock&attachment=0&file=movement_draft%2F" . urlencode(dol_sanitizeFileName($codeAuto)) . "%2F" . urlencode($file) . "&entity=1', '_blank');
								window.location.href;
							}
						</script>";
		}
	}
} elseif ($action == 'delete') {
	$chbxs = GETPOST('toselect', 'array');
	foreach ($chbxs as $k => $v) {
		if (intval($v) != $v) {
			unset($chbxs[$k]);
		}
	}
	if (count($chbxs)) {
		$sql = 'DELETE FROM `llx_stock_mouvement_draft` '
			. 'WHERE rowid IN (' . implode(',', $chbxs) . ')'
		;
		if (!$db->query($sql)) {
			dol_print_error($db);
			die();
		}
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit();
}


// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && !$cancel) {
	$chbxs = array_filter(GETPOST('rc_chbx', 'array'), 'ctype_digit');

	if (!empty($chbxs)) {
		$sql = 'SELECT smd.rowid AS mov_id, smd.qty, smd.code, smd.label, smd.origin, smd.origin_id,';
		$sql .= ' smd.datem, smd.batch, smd.eatby, smd.sellby,';
		$sql .= ' es.rowid AS entrepot_source_id, es.ref AS entrepot_source_ref,';
		$sql .= ' et.rowid AS entrepot_target_id, et.ref AS entrepot_target_ref,';
		$sql .= ' p.rowid AS product_id, p.ref AS product_ref, p.label AS product_label,';
		$sql .= ' u.rowid AS user_id, u.login AS user_login, u.firstname AS user_firstname,';
		$sql .= ' u.lastname AS user_lastname,';
		$sql .= ' IF(smd.origin=\'ticket\', t.ticketnumber, \'\') AS ticket';
		$sql .= ' FROM llx_stock_mouvement_draft AS smd';
		$sql .= ' LEFT JOIN llx_entrepot AS es ON es.rowid = smd.fk_entrepot';
		$sql .= ' LEFT JOIN llx_entrepot AS et ON et.rowid = smd.fk_entrepot_target';
		$sql .= ' LEFT JOIN llx_product AS p ON smd.fk_product = p.rowid';
		$sql .= ' LEFT JOIN llx_user AS u ON u.rowid = smd.fk_user';
		$sql .= ' LEFT JOIN llx_pos_ticket AS t ON t.rowid = smd.origin_id';
		$sql .= ' WHERE smd.rowid IN (\'' . implode('\',\'', $chbxs) . '\')';
		$sql .= ' ORDER BY smd.rowid ASC';

		$resql = $db->query($sql);
		if ($resql) {
			while ($row = $db->fetch_object($resql)) {
				$rows[] = $row;
			}
			$product = new Product($db);

			foreach ($rows as $key => $value) {
				$product->fetch($value->product_id);
		
				//Add stock
				$result = $product->correct_stock_batch(
					$user,
					$value->entrepot_target_id,
					$value->qty,
					0,
					$value->label,
					0,
					strtotime($value->eatby),
					strtotime($value->sellby),
					$value->batch,
					$value->code,
					$value->origin,
					$value->origin_id
				);
				
				// Si es un duplicado lo borramos
				if ($result == -2) {
					$sql = 'DELETE FROM llx_stock_mouvement_draft WHERE rowid = ' . $value->mov_id;
					$resql = $db->query($sql);
					if (!$resql) {
						dol_print_error($db);
					}
				} elseif ($result < 0) {
					setEventMessages($product->error, $product->errors, 'errors');
				}
		
				if (!$error && $result >= 0) {
					$db->commit();
					sendUpdateStockNotification($db, $value->product_id, $value->entrepot_target_id);
					$sql = "DELETE FROM llx_stock_mouvement_draft WHERE rowid = " . $value->mov_id;
					$resql = $db->query($sql);
					if (!$resql) {
						dol_print_error($db);
					}
				} else {
					setEventMessages($product->error, $product->errors, 'errors');
					$db->rollback();
				}
			}
		}
	}

	if (!$error) {
		if ($backtopage) {
			header("Location: " . $backtopage);
			exit;
		}
	}
}


/*
 * View
 */

$productlot = new ProductLot($db);
$productstatic = new Product($db);
$warehouse_origin = new Entrepot($db);
$warehouse_destination = new Entrepot($db);
$movement = new MouvementStock($db);
$userstatic = new User($db);
$form = new Form($db);
$formother = new FormOther($db);
$formproduct = new FormProduct($db);
if (!empty($conf->projet->enabled))
	$formproject = new FormProjets($db);

$sql = "SELECT DISTINCT p.rowid, p.ref as product_ref, p.label as produit, p.tosell, p.tobuy, p.tobatch, p.fk_product_type as type,";
// $sql .= " p.entity, p.barcode, e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu, e.fk_parent, e.statut,";
$sql .= " m.fk_entrepot as warehouse_origin, m.fk_entrepot_target as warehouse_destination, ";
$sql .= " m.rowid as mid, m.qty as qty, m.datem, m.fk_user AS fk_user_author , m.label, m.code AS inventorycode, ";
$sql .= " m.origin_id AS fk_origin, m.origin AS origintype, '' AS batch, ";
$sql .= " if(m.qty > 0,0,1) as type_mouvement, u.login, u.photo, u.lastname, u.firstname ";
$sql .= " FROM " . MAIN_DB_PREFIX . "entrepot as e,";
$sql .= " " . MAIN_DB_PREFIX . "product as p,";
$sql .= " " . MAIN_DB_PREFIX . "stock_mouvement_draft as m";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']))
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef on (m.rowid = ef.fk_object)";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON m.fk_user = u.rowid";
$sql .= " WHERE m.fk_product = p.rowid";
if ($msid > 0)
	$sql .= " AND m.rowid = " . $msid;
// $sql .= " AND m.fk_entrepot = e.rowid";
// $sql .= " AND e.entity IN (" . getEntity('stock') . ")";
if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
	$sql .= " AND p.fk_product_type = 0";
// if ($id > 0) $sql .= " AND e.rowid ='" . $id . "'";
$sql .= dolSqlDateFilter('m.datem', 0, $month, $year);
if ($idproduct > 0)
	$sql .= " AND p.rowid = '" . $idproduct . "'";
if (!empty($search_ref))
	$sql .= natural_search('m.rowid', $search_ref, 1);
if (!empty($search_movement))
	$sql .= natural_search('m.label', $search_movement);
if (!empty($search_inventorycode))
	$sql .= natural_search('m.code', $search_inventorycode);

if (!empty($search_barcode))
	$sql .= natural_search('p.barcode', $search_barcode);

if (!empty($search_product_ref))
	$sql .= natural_search('p.ref', $search_product_ref);
if (!empty($search_product))
	$sql .= natural_search('p.label', $search_product);
if (!$user->rights->stock->show_all_warehouses)
	$sql .= " AND m.fk_entrepot_target = " . $user->fk_warehouse . "";
if ($search_warehouse_origin != '' && $search_warehouse_origin != '-1')
	$sql .= natural_search('m.fk_entrepot', $search_warehouse_origin, 2);
if ($search_warehouse_destination != '' && $search_warehouse_destination != '-1')
	$sql .= natural_search('m.fk_entrepot_target', $search_warehouse_destination, 2);
if (!empty($search_user))
	$sql .= natural_search('u.login', $search_user);
if (!empty($search_batch))
	$sql .= natural_search('m.batch', $search_batch);
if ($search_qty != '')
	$sql .= natural_search('m.qty', $search_qty, 1);
if ($search_type_mouvement != '' && $search_type_mouvement != '-1')
	$sql .= natural_search('m.type_mouvement', $search_type_mouvement, 2);
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);
//die(($sql));
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

if (empty($search_inventorycode)) {
	$sql .= $db->plimit($limit + 1, $offset);
} else {
	$limit = 0;
}

$resql = $db->query($sql);

if (!empty($search_inventorycode))
	$limit = $db->num_rows($resql);

if ($resql) {
	$product = new Product($db);
	$object = new Entrepot($db);

	if ($idproduct > 0) {
		$product->fetch($idproduct);
	}
	if ($id > 0 || $ref) {
		$result = $object->fetch($id, $ref);
		if ($result < 0) {
			dol_print_error($db);
		}
	}

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();


	$i = 0;
	$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	if ($msid)
		$texte = $langs->trans('StockMovementForId', $msid) . ' PENDIENTE';
	else {
		$texte = $langs->trans("ListOfStockMovements") . ' PENDIENTES';
		if ($id)
			$texte .= ' (' . $langs->trans("ForThisWarehouse") . ')';
	}
	llxHeader("", $texte, $help_url);

	/*
	 * Show tab only if we ask a particular warehouse
	 */
	if ($object->id > 0) {
		$head = array();// stock_prepare_head($object);



		dol_fiche_head($head, 'movements', $langs->trans("Warehouse"), -1, 'stock');


		$linkback = '<a href="' . DOL_URL_ROOT . '/product/stock/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= $langs->trans("LocationSummary") . ' : ' . $object->lieu;
		$morehtmlref .= '</div>';

		$shownav = 1;
		if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL)))
			$shownav = 0;

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);



		print '<div class="clearboth"></div>';

		dol_fiche_end();
	}


	/*
	 * Correct stock
	 */
	if ($action == "correction") {
		include DOL_DOCUMENT_ROOT . '/product/stock/tpl/stockcorrection.tpl.php';
		print '<br>';
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	if (false && (empty($action) || $action == 'list') && $id > 0) {
		print "<div class=\"tabsAction\">\n";

		if ($user->rights->stock->mouvement->creer) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=correction">' . $langs->trans("CorrectStock") . '</a>';
		}

		if ($user->rights->stock->mouvement->creer) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=transfert">' . $langs->trans("TransferStock") . '</a>';
		}

		print '</div><br>';
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"])
		$param .= '&contextpage=' . urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit)
		$param .= '&limit=' . urlencode($limit);
	if ($id > 0)
		$param .= '&id=' . urlencode($id);
	if ($search_movement)
		$param .= '&search_movement=' . urlencode($search_movement);
	if ($search_inventorycode)
		$param .= '&search_inventorycode=' . urlencode($search_inventorycode);
	if ($search_type_mouvement)
		$param .= '&search_type_mouvement=' . urlencode($search_type_mouvement);
	if ($search_product_ref)
		$param .= '&search_product_ref=' . urlencode($search_product_ref);
	if ($search_product)
		$param .= '&search_product=' . urlencode($search_product);
	if ($search_batch)
		$param .= '&search_batch=' . urlencode($search_batch);
	if ($search_warehouse_origin > 0)
		$param .= '&search_warehouse_origin=' . urlencode($search_warehouse_origin);
	if ($search_warehouse_destination > 0)
		$param .= '&search_warehouse_destination=' . urlencode($search_warehouse_destination);
	if ($search_user)
		$param .= '&search_user=' . urlencode($search_user);
	if ($idproduct > 0)
		$param .= '&idproduct=' . urlencode($idproduct);
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		//    'presend'=>$langs->trans("SendByMail"),
		//    'builddoc'=>$langs->trans("PDFMerge"),
	);
	// By default, we should never accept deletion of stock movement.
	if (!empty($conf->global->STOCK_ALLOW_DELETE_OF_MOVEMENT) && $permissiontodelete)
		$arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>' . $langs->trans("Delete");
	if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete')))
		$arrayofmassactions = array();
	// $massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '')
		print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<input type="hidden" name="page" value="' . $page . '">';
	print '<input type="hidden" name="type" value="' . $type . '">';
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
	if ($id > 0)
		print '<input type="hidden" name="id" value="' . $id . '">';

	if ($id > 0)
		print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);
	else
		print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);

	// Add code for pre mass action (confirmation or email presend form)
	$topicmail = "SendStockMovement";
	$modelmail = "movementstock";
	$objecttmp = new MouvementStock($db);
	$trackid = 'mov' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

	if ($sall) {
		foreach ($fieldstosearchall as $key => $val)
			$fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall) . '</div>';
	}

	$moreforfilter = '';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook))
		$moreforfilter .= $hookmanager->resPrint;
	else
		$moreforfilter = $hookmanager->resPrint;

	if (!empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

	// Fields title search
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['m.rowid']['checked'])) {
		// Checkbox
		print '<td class="liste_titre left">';
		print '<input class="fla" type="checkbox" name="rc_check_movs" id="rc_check_movs" value="1">';
		print '</td>';

		// Ref
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth25" type="text" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '">';
		print '</td>';
	}

	if (!empty($arrayfields['p.barcode']['checked'])) {
		// Barcode
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="4" name="search_barcode" id="barcode" value="' . dol_escape_htmltag($search_barcode) . '">';

		print '<script>
		$(document).ready(function(){
			$("#barcode").focus();
			$("#barcode").on("input",function(){
				$.ajax({
					method: "POST",
					url: "./ajax_movement.php",
					data: {
						"barcode": $(this).val()
					},
					success: function (data) {
						$(".rc_product_checkbox").each(function(){
							if( data.indexOf($(this).val()) != -1 ){
								$(this).prop("checked", true);
							}
						});
						$("#barcode").val("");
					}
				});
			});
		});
		</script>';

		print '</td>';
	}

	if (!empty($arrayfields['origin']['checked'])) {
		// Origin of movement
		print '<td class="liste_titre left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.datem']['checked'])) {
		// Date
		print '<td class="liste_titre nowraponall">';
		print '<input class="flat" type="text" size="2" maxlength="2" placeholder="' . dol_escape_htmltag($langs->trans("Month")) . '" name="month" value="' . $month . '">';
		if (empty($conf->productbatch->enabled))
			print '&nbsp;';
		//else print '<br>';
		$syear = $year ? $year : -1;
		print '<input class="flat maxwidth50" type="text" maxlength="4" placeholder="' . dol_escape_htmltag($langs->trans("Year")) . '" name="year" value="' . ($syear > 0 ? $syear : '') . '">';
		//print $formother->selectyear($syear,'year',1, 20, 5);
		print '</td>';
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		// Product Ref
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth75" type="text" name="search_product_ref" value="' . dol_escape_htmltag($idproduct ? $product->ref : $search_product_ref) . '">';
		print '</td>';
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		// Product label
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth100" type="text" name="search_product" value="' . dol_escape_htmltag($idproduct ? $product->label : $search_product) . '">';
		print '</td>';
	}
	// Batch
	if (!empty($arrayfields['m.batch']['checked'])) {
		print '<td class="liste_titre center"><input class="flat maxwidth75" type="text" name="search_batch" value="' . dol_escape_htmltag($search_batch) . '"></td>';
	}
	if (!empty($arrayfields['pl.eatby']['checked'])) {
		print '<td class="liste_titre left">';
		print '</td>';
	}
	if (!empty($arrayfields['pl.sellby']['checked'])) {
		print '<td class="liste_titre left">';
		print '</td>';
	}
	// Warehouse
	if (!empty($arrayfields['m.warehouse_origin']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone left">';
		print $formproduct->selectWarehouses($search_warehouse_origin, 'search_warehouse_origin', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	if (!empty($arrayfields['m.warehouse_destination']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone left">';
		print $formproduct->selectWarehouses($search_warehouse_destination, 'search_warehouse_destination', 'warehouseopen,warehouseinternal', 1, $user->rights->stock->show_all_warehouses ? 0 : 1, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	if (!empty($arrayfields['m.fk_user_author']['checked'])) {
		// Author
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="6" name="search_user" value="' . dol_escape_htmltag($search_user) . '">';
		print '</td>';
	}
	if (!empty($arrayfields['m.inventorycode']['checked'])) {
		// Inventory code
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="4" name="search_inventorycode" value="' . dol_escape_htmltag($search_inventorycode) . '">';
		print '</td>';
	}
	if (!empty($arrayfields['m.label']['checked'])) {
		// Label of movement
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="8" name="search_movement" value="' . dol_escape_htmltag($search_movement) . '">';
		print '</td>';
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		// Type of movement
		print '<td class="liste_titre center">';
		//print '<input class="flat" type="text" size="3" name="search_type_mouvement" value="'.dol_escape_htmltag($search_type_mouvement).'">';
		print '<select id="search_type_mouvement" name="search_type_mouvement" class="maxwidth150">';
		print '<option value="" ' . (($search_type_mouvement == "") ? 'selected="selected"' : '') . '></option>';
		print '<option value="0" ' . (($search_type_mouvement == "0") ? 'selected="selected"' : '') . '>' . $langs->trans('StockIncreaseAfterCorrectTransfer') . '</option>';
		print '<option value="1" ' . (($search_type_mouvement == "1") ? 'selected="selected"' : '') . '>' . $langs->trans('StockDecreaseAfterCorrectTransfer') . '</option>';
		print '<option value="2" ' . (($search_type_mouvement == "2") ? 'selected="selected"' : '') . '>' . $langs->trans('StockDecrease') . '</option>';
		print '<option value="3" ' . (($search_type_mouvement == "3") ? 'selected="selected"' : '') . '>' . $langs->trans('StockIncrease') . '</option>';
		print '</select>';
		print ajax_combobox('search_type_mouvement');
		// TODO: add new function $formentrepot->selectTypeOfMovement(...) like
		// print $formproduct->selectWarehouses($search_warehouse_origin, 'search_warehouse_origin', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	// origen original
	if (!empty($arrayfields['m.qty']['checked'])) {
		// Qty
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_qty" value="' . dol_escape_htmltag($search_qty) . '">';
		print '</td>';
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		// Price
		print '<td class="liste_titre" align="left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.fk_projet']['checked'])) {
		// fk_project
		print '<td class="liste_titre" align="left">';
		print '&nbsp; ';
		print '</td>';
	}


	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['m.datec']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['m.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Actions
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<th>'
		. '<div class="nowrap">'
		. '<button title="Aprobar Registros" type="button" class="liste_titre button_rc_apply" name="button_rc_apply" value="x" style="border:unset;background-color:unset;display:none;">'
		. '<span class="fa fa-floppy-o"></span>'
		. '</button>'
		. '<button title="Aprobar Transferencias" type="button" class="liste_titre button_rc" name="button_rc" value="x" style="border:unset;background-color:unset;">'
		. '<span class="far fa-check-circle fa-2x" style="cursor:pointer;"></span>'
		. '</button>'
		. '<button title="Firmar Digital" type="button" class="liste_titre button_rc_sign" name="button_rc_sign" value="x" style="border:unset;background-color:unset;display:none;">'
		. '<span class="fa fa-edit"></span>'
		. '</button>'
		. '<button title="Elimninar Registros" type="button" class="liste_titre button_rc_delete" name="button_rc_delete" value="x" style="border:unset;background-color:unset;display:none;">'
		. '<span class="fa fa-trash"></span>'
		. '</button>'
		. '</div>'
		. '</th>';
	if (!empty($arrayfields['m.rowid']['checked'])) {
		print_liste_field_titre($arrayfields['m.rowid']['label'], $_SERVER["PHP_SELF"], 'm.rowid', '', $param, '', $sortfield, $sortorder);
	}

	if (!empty($arrayfields['p.barcode']['checked'])) {
		print_liste_field_titre($arrayfields['p.barcode']['label'], $_SERVER["PHP_SELF"], "p.barcode", "", $param, "", $sortfield, $sortorder);
	}

	if (!empty($arrayfields['origin']['checked'])) {
		print_liste_field_titre($arrayfields['origin']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.datem']['checked'])) {
		print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER["PHP_SELF"], 'm.datem', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.batch']['checked'])) {
		print_liste_field_titre($arrayfields['m.batch']['label'], $_SERVER["PHP_SELF"], 'm.batch', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['pl.eatby']['checked'])) {
		print_liste_field_titre($arrayfields['pl.eatby']['label'], $_SERVER["PHP_SELF"], 'pl.eatby', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['pl.sellby']['checked'])) {
		print_liste_field_titre($arrayfields['pl.sellby']['label'], $_SERVER["PHP_SELF"], 'pl.sellby', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['m.warehouse_origin']['checked'])) {
		print_liste_field_titre($arrayfields['m.warehouse_origin']['label'], $_SERVER["PHP_SELF"], "m.fk_entrepot", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.warehouse_destination']['checked'])) {
		print_liste_field_titre($arrayfields['m.warehouse_destination']['label'], $_SERVER["PHP_SELF"], "m.fk_entrepot_target", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.fk_user_author']['checked'])) {
		print_liste_field_titre($arrayfields['m.fk_user_author']['label'], $_SERVER["PHP_SELF"], "m.fk_user", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.inventorycode']['checked'])) {
		print_liste_field_titre($arrayfields['m.inventorycode']['label'], $_SERVER["PHP_SELF"], "m.code", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.label']['checked'])) {
		print_liste_field_titre($arrayfields['m.label']['label'], $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		print_liste_field_titre($arrayfields['m.type_mouvement']['label'], $_SERVER["PHP_SELF"], "m.type_mouvement", "", $param, '', $sortfield, $sortorder, 'center ');
	}

	// origen original

	if (!empty($arrayfields['m.qty']['checked'])) {
		print_liste_field_titre($arrayfields['m.qty']['label'], $_SERVER["PHP_SELF"], "m.qty", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		print_liste_field_titre($arrayfields['m.price']['label'], $_SERVER["PHP_SELF"], "m.price", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['m.fk_projet']['checked'])) {
		print_liste_field_titre($arrayfields['m.fk_projet']['label'], $_SERVER["PHP_SELF"], "m.fk_projet", "", $param, 'align="right"', $sortfield, $sortorder);
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['m.datec']['checked'])) {
		print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['m.tms']['checked'])) {
		print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$arrayofuniqueproduct = array();

	$totalarray = array();
	$dirs = array();
	$totalqty = 0;
	$totalprice = 0;
	while ($objp = $db->fetch_object($resql)) {

		$dir = $conf->stock->dir_output . "/movement_draft/" . dol_sanitizeFileName($objp->inventorycode);
		$cdir = scandir($dir);
		$ffound = array();
		foreach ($cdir as $key => $value) {
			$patrn = '/' . preg_quote(dol_sanitizeFileName($objp->inventorycode), '/') . '\_[0-9_]{0,}' . $objp->rowid . '/';
			if (preg_match($patrn, $value) && substr($value, -4) == '.pdf') {
				$ffound[] = $value;
			}
		}
		if (count($ffound) > 1) {
			$sfiles = array();
			foreach ($ffound as $tsFile) {
				$sfiles[$tsFile] = filemtime($dir . '/' . $tsFile);
			}
			arsort($sfiles);
			$ffound = array_keys($sfiles);
		}

		$userstatic->id = $objp->fk_user_author;
		$userstatic->login = $objp->login;
		$userstatic->lastname = $objp->lastname;
		$userstatic->firstname = $objp->firstname;
		$userstatic->photo = $objp->photo;

		$productstatic->id = $objp->rowid;
		$productstatic->ref = $objp->product_ref;
		$productstatic->label = $objp->produit;
		$productstatic->type = $objp->type;
		$productstatic->status = $objp->tosell;
		$productstatic->status_buy = $objp->tobuy;
		$productstatic->status_batch = $objp->tobatch;

		$productlot->id = $objp->lotid;
		$productlot->batch = $objp->batch;
		$productlot->eatby = $objp->eatby;
		$productlot->sellby = $objp->sellby;

		$warehouse_origin->fetch($objp->warehouse_origin);
		$warehouse_destination->fetch($objp->warehouse_destination);

		$arrayofuniqueproduct[$objp->rowid] = $objp->produit;
		if (!empty($objp->fk_origin)) {
			$origin = $movement->get_origin($objp->fk_origin, $objp->origintype);
		} else {
			$origin = '';
		}

		$totalqty += $objp->qty;
		$totalprice += $objp->price;

		print '<tr class="oddeven">';
		print '<td>';
		if (in_array($objp->mid, $chbxs)) {
			$cheked = ' checked="checked" ';
		} else {
			$cheked = '';
		}
		if (in_array($objp->mid, $traspasoAuto)) {
			$cheked = ' checked="checked" ';
		}
		print '<input type="checkbox" value="' . $objp->mid . '" name="rc_chbx[]" class="rc_product_checkbox"' . $cheked . ' />';
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=printpdfcorrection&codetosearch=' . $objp->inventorycode . '" title="Imprimir comprobante">  <i class="far fa-file-pdf fa-lg"></i></a>';
		print '</td>';
		// Id movement
		if (!empty($arrayfields['m.rowid']['checked'])) {
			print '<td>' . $objp->mid . '</td>'; // This is primary not movement id
		}

		if (!empty($arrayfields['origin']['checked'])) {
			// Origin of movement
			//print '<td class="nowraponall">'.$origin.'</td>';
			print '<td>' . '<a href="'
				. DOL_URL_ROOT . '/product/stock/movement_card.php'
				. '?id=' . ($objp->entrepot_id == 1 ? 2 : 1)
				. '&amp;search_inventorycode=' . $objp->inventorycode
				. '">'
				. $objp->inventorycode
				. '</a>'
				. '</td>';
		}
		if (!empty($arrayfields['m.datem']['checked'])) {
			// Date
			print '<td class="nowraponall">' . dol_print_date($db->jdate($objp->datem), 'dayhour', 'tzuserrel') . '</td>';
		}
		if (!empty($arrayfields['p.ref']['checked'])) {
			// Product ref
			print '<td class="nowraponall">';
			print $productstatic->getNomUrl(1, 'stock', 16);
			print '</td>';
		}
		if (!empty($arrayfields['p.label']['checked'])) {
			// Product label
			print '<td>';
			/*$productstatic->id=$objp->rowid;
			   $productstatic->ref=$objp->produit;
			   $productstatic->type=$objp->type;
			   print $productstatic->getNomUrl(1,'',16);*/
			print $productstatic->label;
			print '</td>';
		}
		if (!empty($arrayfields['m.batch']['checked'])) {
			print '<td class="center nowraponall">';
			if ($productlot->id > 0)
				print $productlot->getNomUrl(1);
			else
				print $productlot->batch; // the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
			print '</td>';
		}
		if (!empty($arrayfields['pl.eatby']['checked'])) {
			print '<td class="center">' . dol_print_date($objp->eatby, 'day') . '</td>';
		}
		if (!empty($arrayfields['pl.sellby']['checked'])) {
			print '<td class="center">' . dol_print_date($objp->sellby, 'day') . '</td>';
		}
		// Warehouse
		if (!empty($arrayfields['m.warehouse_origin']['checked'])) {
			print '<td>';
			print $warehouse_origin->getNomUrl(1);
			print '</td>';
		}
		if (!empty($arrayfields['m.warehouse_destination']['checked'])) {
			print '<td>';
			print $warehouse_destination->getNomUrl(1);
			print '</td>';
		}
		// Author
		if (!empty($arrayfields['m.fk_user_author']['checked'])) {
			print '<td class="tdoverflowmax100">';
			print $userstatic->getNomUrl(-1);
			print '</td>';
		}
		if (!empty($arrayfields['m.inventorycode']['checked'])) {
			// Inventory code
			print '<td>' . '<a href="'
				. DOL_URL_ROOT . '/product/stock/movement_card.php'
				. '?id=' . $objp->entrepot_id
				. '&amp;search_inventorycode=' . $objp->inventorycode
				. '&amp;search_type_mouvement=' . $objp->type_mouvement
				. '">'
				. $objp->inventorycode
				. '</a>'
				. '</td>';
		}
		if (!empty($arrayfields['m.label']['checked'])) {
			// Label of movement
			print '<td class="tdoverflowmax100aaa">' . $objp->label . '</td>';
		}
		if (!empty($arrayfields['m.type_mouvement']['checked'])) {
			// Type of movement
			switch ($objp->type_mouvement) {
				case "0":
					print '<td class="center">' . $langs->trans('StockIncreaseAfterCorrectTransfer') . '</td>';
					break;
				case "1":
					print '<td class="center">' . $langs->trans('StockDecreaseAfterCorrectTransfer') . '</td>';
					break;
				case "2":
					print '<td class="center">' . $langs->trans('StockDecrease') . '</td>';
					break;
				case "3":
					print '<td class="center">' . $langs->trans('StockIncrease') . '</td>';
					break;
			}
		}

		// origen original

		if (!empty($arrayfields['m.qty']['checked'])) {
			// Qty
			print '<td class="right">';
			print $objp->qty;
			print '</td>';
		}
		if (!empty($arrayfields['m.price']['checked'])) {
			// Price
			print '<td class="right">';
			if ($objp->price != 0)
				print price($objp->price);
			print '</td>';
		}
		if (!empty($arrayfields['m.fk_projet']['checked'])) {
			// fk_project
			print '<td align="right">';
			if ($objp->fk_project != 0)
				print $movement->get_origin($objp->fk_project, 'project');
			print '</td>';
		}
		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($objp->mid, $arrayofselected))
				$selected = 1;
			print '<input id="cb' . $objp->mid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $objp->mid . '"' . ($selected ? ' checked="checked"' : '') . '>';
		}
		print '</td>';
		if (!$i)
			$totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	if (!empty($arrayfields['m.qty']['checked'])) {
		$colspan = 0;
		foreach ($arrayfields as $field) {
			if ($field['checked']) {
				$colspan++;
			}
		}
		print '<tr class="total">';
		print '<td colspan="' . $colspan . '">';
		print '<b>' . $langs->trans('Total') . '</b>';
		print '</td>';
		print '<td class="right">';
		print '<b>' . $totalqty . '</b>';
		print '</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
	}

	$db->free($resql);

	print "</table>";
	print '</div>';
	print "</form>";

	// Add number of product when there is a filter on period
	if (count($arrayofuniqueproduct) == 1 && is_numeric($year)) {
		print "<br>";

		$productidselected = 0;
		foreach ($arrayofuniqueproduct as $key => $val) {
			$productidselected = $key;
			$productlabelselected = $val;
		}
		$datebefore = dol_get_first_day($year ? $year : strftime("%Y", time()), $month ? $month : 1, true);
		$dateafter = dol_get_last_day($year ? $year : strftime("%Y", time()), $month ? $month : 12, true);
		$balancebefore = $movement->calculateBalanceForProductBefore($productidselected, $datebefore);
		$balanceafter = $movement->calculateBalanceForProductBefore($productidselected, $dateafter);

		//print '<tr class="total"><td class="liste_total">';
		print $langs->trans("NbOfProductBeforePeriod", $productlabelselected, dol_print_date($datebefore, 'day', 'gmt'));
		//print '</td>';
		//print '<td class="liste_total right" colspan="6">';
		print ': ' . $balancebefore;
		print "<br>\n";
		//print '</td></tr>';
		//print '<tr class="total"><td class="liste_total">';
		print $langs->trans("NbOfProductAfterPeriod", $productlabelselected, dol_print_date($dateafter, 'day', 'gmt'));
		//print '</td>';
		//print '<td class="liste_total right" colspan="6">';
		print ': ' . $balanceafter;
		print "<br>\n";
		//print '</td></tr>';
	}
} else {
	dol_print_error($db);
}

?>
<script>
	var rc_fake_form_url = '<?php print $_SERVER['PHP_SELF']; ?>';
	var rc_sign_form_url = '<?php print DOL_URL_ROOT; ?>/product/stock/signature.php';
	var rc_this_id = '<?php print implode(',', $toCreate); ?>';
	var util = {};
	var traspasoAuto = '<?php print $traspasoAuto; ?>';
	util.post = function (url, fields) {
		var $form = $('<form>', {
			action: url,
			method: 'post'
		});
		$.each(fields, function (key, val) {
			if (Array.isArray(val)) {
				val.forEach(function (ai, ae) {
					$('<input>').attr({
						type: "hidden",
						name: key + '[]',
						value: ai
					}).appendTo($form);
				});
			}
			else {
				$('<input>').attr({
					type: "hidden",
					name: key,
					value: val
				}).appendTo($form);
			}
		});
		$form.appendTo('body').submit();
	}
	$(document).ready(function () {
		// rc_product_checkbox class of checkboxes
		// check / uncheck all change
		$('#rc_check_movs').change(function () {
			var cv = $('#rc_check_movs').is(":checked");
			$('.rc_product_checkbox').prop("checked", cv);
			ls_tpv_switch_line_checkbox();
		});
		$('.rc_product_checkbox').change(function () {
			ls_tpv_switch_line_checkbox();
		});
		$('.button_rc_sign').click(function () {
			var ch = rc_check_checked(1);
			if (ch > 0) {
				rc_submit_fake_form('rc_sign');
			}
		});
		$('.button_rc_apply').click(function () {
			var ch = rc_check_checked(0);
			if (ch > 0) {
				var re = ' registro seleccionado'
				if (ch > 1) {
					re = ' registros seleccionados.'
				}
				rc_submit_fake_form('rc_validate');
			}
		});
		$('.button_rc').click(function () {
			var ch = rc_check_checked(0);
			if (ch > 0) {
				var re = ' registro seleccionado'
				if (ch > 1) {
					re = ' registros seleccionados.'
				}

				if (confirm("Confirme que desea GENERAR los movimientos de " + ch + re)) {
					rc_submit_fake_form('transfert_stock');
				}
				else {
					// Nothing to do
				}
			}
		});
		if (traspasoAuto) {
			//if (confirm("Confirme que desea GENERAR la Transferencia")){
			rc_submit_fake_form('transfert_stock');
			//}
		}
		$('.button_rc_delete').click(function () {
			var ch = rc_check_checked(2);
			if (ch > 0) {
				var re = ' elemento seleccionado.'
				if (ch > 1) {
					re = ' elementos seleccionados.'
				}

				if (confirm("Confirme que desea ELIMINAR " + ch + re)) {
					rc_submit_fake_form('rc_delete');
				}
				else {
					// Nothing to do
				}
			}
		});

		<?php if ($action == 'rc_create_pdf'): ?>
			$('.button_rc_sign').click();
		<?php endif; ?>
		<?php if ($action == 'sign_doc'): ?>
			$('.button_rc_apply').click();
		<?php endif; ?>




	});

	function rc_submit_fake_form(a) {
		console.log('rc_submit_fake_form (a) -> ', a);
		var fields = {};
		$('input').each(function (i, e) {
			var nm = $(e).attr('name');
			var vl = $(e).val();
			switch ($(e).attr('type')) {
				case 'checkbox':
					if ($(e).is(":checked") && typeof nm != 'undefined' && vl.length > 0) {
						if (nm.slice(-2) == '[]') {
							var nnm = nm.substr(0, nm.length - 2);
							if (typeof fields[nnm] == 'undefined') {
								fields[nnm] = [];
							}
							fields[nnm][fields[nnm].length] = vl;

						}
						else {
							fields[nm] = vl;
						}
					}
					break;
				default:
					if (typeof nm != 'undefined' && vl.length > 0) {
						if (nm.slice(-2) == '[]') {
							var nnm = nm.substr(0, nm.length - 2);
							if (typeof fields[nnm] == 'undefined') {
								fields[nnm] = [];
							}
							fields[nnm][fields[nnm].length] = $(e).val();

						}
						else {
							fields[nm] = $(e).val();
						}
					}
					break;

			}
		});
		fields['action'] = a;
		if (a == 'xrc_create_pdf') {
			window.location.href = rc_sign_form_url + '?id=' + rc_this_id;
		}
		if (a == 'rc_delete') {
			console.log("rc delete");
			// window.location.href=rc_fake_form_url+'?id='+rc_this_id+'&action=rc_delete';
		}
		else {
			util.post(rc_fake_form_url, fields);
		}
	}


	function rc_check_checked(t) {
		var ch = 0;
		switch (t) {
			case 0:
				ch = ls_tpv_switch_line_checkbox();
				break;
			case 1:
				$('.recibos_pdf').each(function (i, e) {
					if ($(e).is(":checked")) {
						ch++;
					}
				});
			case 2:
				ch = ls_tpv_switch_line_checkbox();
				$('.recibos_pdf').each(function (i, e) {
					if ($(e).is(":checked")) {
						ch++;
					}
				});
		}
		if (ch == 0) {
			alert('Seleccione al menos una opción.');
		}
		return ch;
	}

	function ls_tpv_switch_line_checkbox() {
		var cv = $('#rc_check_movs').is(":checked");
		var ch = 0;
		var un = 0;
		var ct = 0
		$('.rc_product_checkbox').each(function (i, e) {
			if ($(e).is(":checked")) {
				ch++;
			}
			else {
				un++;
			}
			ct++;
		});
		if (ct == 0) {
			$('#rc_check_movs').prop("checked", false);
		}
		else if (ct == ch) {
			$('#rc_check_movs').prop("checked", true);
		}
		else if (ct == un) {
			$('#rc_check_movs').prop("checked", false);
		}
		else {
			$('#rc_check_movs').prop("checked", false);
		}
		return ch;
	}

	function pdfChBx(id) {
		var cl = $('#' + id).attr('class').replace('recibos_pdf ', '');
		var ch = $('#' + id).is(":checked");
		$('.' + cl).each(function (i, e) {
			var lid = $(e).attr('id');
			$('#' + lid).unbind('change');
			$('#' + lid).prop('checked', ch);
			$('#' + lid).on('change', function () { pdfChBx(lid); });
		});
	}
</script>

<?php

// End of page
llxFooter();
$db->close();
?>