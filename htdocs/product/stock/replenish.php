<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2018	Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		ATM Consulting		<support@atm-consulting.fr>
 * Copyright (C) 2019       Frédéric France     <frederic.france@netlogic.fr>
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
 *  \file       htdocs/product/stock/replenish.php
 *  \ingroup    stock
 *  \brief      Page to list stocks to replenish
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once './lib/replenishment.lib.php';
ini_set('display_errors', '0');
// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('stockreplenishlist'));

//checks if a product has been ordered

$action = GETPOST('action', 'alpha');
$sref = GETPOST('sref', 'alpha');
$snom = GETPOST('snom', 'nohtml');
$slocation = GETPOST('slocation', 'alpha');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$type = GETPOST('type', 'int');
$tobuy = GETPOST('tobuy', 'int');
$salert = GETPOST('salert', 'alpha');
$mode = GETPOST('mode', 'alpha') ?  GETPOST('mode', 'alpha') : 'virtual';
$draftorder = GETPOST('draftorder', 'alpha');
$alreadyordered = GETPOST('alreadyordered', 'alpha');
/** Filtered by Rotation */
$selectedSearchRotation = GETPOST('rotation_id', 'alpha');
/** Filtered by Warehouse */
$selectedSearchWarehouse = GETPOST('entrepot_id', 'alpha') ? GETPOST('entrepot_id', 'alpha') : $user->fk_warehouse;
$fourn_id = GETPOST('fourn_id', 'int');
$fk_supplier = GETPOST('fk_supplier', 'int');
$fk_entrepot = GETPOST('fk_entrepot', 'int');
$entrepot_id = GETPOST('entrepot_id', 'int') ? GETPOST('entrepot_id', 'int') : $user->fk_warehouse;
$texte = '';
$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
$searchCategoryProductList2 = GETPOST('search_category_product_list2', 'array');
$searchCategoryProductOperator = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
$catid = GETPOST('catid', 'int');

// Load variables for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->global->MAX_LIST_REPLENISH;
$sortfield = GETPOST('sortfield', 'alpha') ? GETPOST('sortfield', 'alpha') : 'fecha_mov';
$sortorder = GETPOST('sortorder', 'alpha') ? GETPOST('sortorder', 'alpha') : 'DESC';
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)
|| !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
|| !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)
|| !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION)
|| !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE))
{
	$virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

$usevirtualstock = !empty($conf->global->STOCK_USE_VIRTUAL_STOCK);
if ($mode == 'physical') $usevirtualstock = 0;
if ($mode == 'virtual') $usevirtualstock = 1;

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha') || isset($_POST['valid'])) // Both test are required to be compatible with all browsers
{
	$sref = '';
	$snom = '';
	$slocation = '';
	$sal = '';
	$salert = '';
	$draftorder = '';
	$alreadyordered = '';
}
if ($draftorder == 'on') $draftchecked = "checked";
if ($alreadyordered == 'on') $orderedchecked = "checked";

$sql1="SELECT rowid from ".MAIN_DB_PREFIX."societe where nom = 'Proveedor prueba reaprovisionamiento' AND name_alias = '(Test)'";
$result1=$db->query($sql1);
$proveedortest='';
if($db->num_rows($result1)>0){
	$data=$db->fetch_object($result1);
	$proveedortest=$data->rowid;
}

// Create orders
if ($action == 'order' && isset($_POST['valid']))
{
	// echo '<pre>';
	// print_r($_POST);
	// print_r('<br>Fourn: ' . GETPOST('fourn1', 'int') == 0);
	// echo '</pre>';
	// die();
	$linecount = GETPOST('linecount', 'int');
	$fk_supplier = GETPOST('fk_supplier', 'int');
	$fk_entrepot = GETPOST('entrepot_id', 'int');
	$box = 0;
	$errorQty = 0;
	$errorprovedor=0;
	unset($_POST['linecount']);
	if ($linecount > 0)
	{
		$db->begin();

		$suppliers = array();
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		$productsupplier = new ProductFournisseur($db);
		for ($i = 0; $i < $linecount; $i++)
		{
			if (GETPOST('choose'.$i, 'alpha') === 'on' && GETPOST('fourn'.$i, 'int') > 0)
			{
				//one line
				$box = $i;
				$supplierpriceid = GETPOST('fourn'.$i, 'int');
				//get all the parameters needed to create a line
				$qty = GETPOST('tobuy'.$i, 'int');
				$idprod = $productsupplier->get_buyprice($supplierpriceid, $qty);
				$res = $productsupplier->fetch($idprod);
				if ($res && $idprod > 0)
				{
					if ($qty)
					{
						//might need some value checks
						$line = new CommandeFournisseurLigne($db);
						$line->qty = $qty;
						$line->fk_product = $idprod;

						//$product = new Product($db);
						//$product->fetch($obj->fk_product);
						if (!empty($conf->global->MAIN_MULTILANGS))
						{
							$productsupplier->getMultiLangs();
						}

						// if we use supplier description of the products
						if (!empty($productsupplier->desc_supplier) && !empty($conf->global->PRODUIT_FOURN_TEXTS)) {
							$desc = $productsupplier->desc_supplier;
						} else {
							$desc = $productsupplier->description;
						}
						$line->desc = $desc;
						if (! empty($conf->global->MAIN_MULTILANGS))
						{
							// TODO Get desc in language of thirdparty
						}

						echo '<pre>';
						print_r($productsupplier);
						echo '</pre>';
						die();

						$line->tva_tx = $productsupplier->vatrate_supplier;
						$line->subprice = $productsupplier->fourn_pu;
						$line->total_ht = $productsupplier->fourn_pu * $qty;
						$tva = $line->tva_tx / 100;
						$line->total_tva = $line->total_ht * $tva;
						$line->total_ttc = $line->total_ht + $line->total_tva;
						$line->remise_percent = $productsupplier->remise_percent;
						$line->ref_fourn = $productsupplier->ref_supplier;
						$line->type = $productsupplier->type;
						$line->fk_unit = $productsupplier->fk_unit;
						$suppliers[$productsupplier->fourn_socid]['lines'][] = $line;
					}
				}
				elseif ($idprod == -1)
				{
					$errorQty++;
				}
				else
				{
					$error = $db->lasterror();
					dol_print_error($db);
				}

				unset($_POST['fourn'.$i]);
			}elseif(GETPOST('choose'.$i, 'alpha') === 'on' && GETPOST('fourn'.$i, 'int') == 0){
				//one line
				$box = $i;
				$proveedortest = $fk_supplier;
				$supplierpriceid = $proveedortest;
				//get all the parameters needed to create a line
				$qty = GETPOST('tobuy'.$i, 'int');
				$idprod = GETPOST('id'.$i);
				$res = $productsupplier->fetch($idprod);
				if ($res && $idprod > 0 && $supplierpriceid!=null)
				{
					if ($qty)
					{
						//might need some value checks
						$line = new CommandeFournisseurLigne($db);
						$line->qty = $qty;
						$line->fk_product = $idprod;

						//$product = new Product($db);
						//$product->fetch($obj->fk_product);
						if (!empty($conf->global->MAIN_MULTILANGS))
						{
							$productsupplier->getMultiLangs();
						}

						// if we use supplier description of the products
						if (!empty($productsupplier->desc_supplier) && !empty($conf->global->PRODUIT_FOURN_TEXTS)) {
							$desc = $productsupplier->desc_supplier;
						} else {
							$desc = $productsupplier->description;
						}
						$line->desc = $desc;
						if (! empty($conf->global->MAIN_MULTILANGS))
						{
							// TODO Get desc in language of thirdparty
						}

						$line->tva_tx = $productsupplier->tva_tx;
						$subprice = 0;
						if($fk_entrepot != $conf->global->CEDIS_WAREHOUSE){
							$subprice = $productsupplier->cost_price_sucursal;
						}else{
							$subprice = $productsupplier->cost_price;
						}
						$line->subprice = $subprice;
						$line->price = $subprice;
						$line->total_ht = $subprice * $qty;
						$tva = $line->tva_tx / 100;
						$line->total_tva = $line->total_ht * $tva;
						$line->total_ttc = $line->total_ht + $line->total_tva;
						$line->remise_percent = $productsupplier->remise_percent;
						$line->ref_fourn = $productsupplier->ref_supplier;
						$line->type = $productsupplier->type;
						$line->fk_unit = $productsupplier->fk_unit;
						$suppliers[$proveedortest]['lines'][] = $line;
					}
				}else
				{
					$errorprovedor++;
				}
				unset($_POST['fourn'.$i]);
			}
			unset($_POST[$i]);
		}

		//we now know how many orders we need and what lines they have
		$i = 0;
		$fail = 0;
		$orders = array();
		$suppliersid = array_keys($suppliers);
		foreach ($suppliers as $supplier)
		{
			$order = new CommandeFournisseur($db);
			// Check if an order for the supplier exists
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur";
			$sql .= " WHERE fk_soc = ".$suppliersid[$i];
			$sql .= " AND source = 42 AND fk_statut = 0";
			$sql .= " AND entity IN (".getEntity('commande_fournisseur').")";
			$sql .= " ORDER BY date_creation DESC";
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);
				$order->fetch($obj->rowid);
				foreach ($supplier['lines'] as $line) {
					$result = $order->addline(
						$line->desc,
						$line->subprice,
						$line->qty,
						$line->tva_tx,
						$line->localtax1_tx,
						$line->localtax2_tx,
						$line->fk_product,
						0,
						$line->ref_fourn,
						$line->remise_percent,
						'HT',
						0,
						$line->type,
						0,
						false,
						null,
						null,
						0,
						$line->fk_unit
					);
				}
				if ($result < 0) {
					$fail++;
					$msg = $langs->trans('OrderFail')."&nbsp;:&nbsp;";
					$msg .= $order->error;
					setEventMessages($msg, null, 'errors');
				} else {
					$id = $result;
				}
			} else {
				$order->socid = $suppliersid[$i];
				$order->fetch_thirdparty();
				//trick to know which orders have been generated this way
				$order->source = 42;
				foreach ($supplier['lines'] as $line) {
					$order->lines[] = $line;
				}
				$order->cond_reglement_id = $order->thirdparty->cond_reglement_supplier_id;
				$order->mode_reglement_id = $order->thirdparty->mode_reglement_supplier_id;
				$order->fk_entrepot = $user->fk_warehouse;
				$id = $order->create($user);
				if ($id < 0) {
					$fail++;
					$msg = $langs->trans('OrderFail')."&nbsp;:&nbsp;";
					$msg .= $order->error;
					setEventMessages($msg, null, 'errors');
				}
				$i++;
			}
		}

		if($fk_entrepot != $conf->global->CEDIS_WAREHOUSE){
			$orderCreated = new CommandeFournisseur($db);
			$orderCreated->fetch($id);
			$res_valid = $orderCreated->valid($user);
			if ($res_valid < 0) {
				$fail++;
				$msg = 'Error al validar la orden';
				setEventMessages($msg, null, 'errors');
			}
			$res_status = $orderCreated->setStatus($user, 2);
			if ($res_status < 0) {
				$fail++;
				$msg = 'Error al cambiar el estado de la orden';
				setEventMessages($msg, null, 'errors');
			}
		}

		if ($errorQty) setEventMessages($langs->trans('ErrorOrdersNotCreatedQtyTooLow'), null, 'warnings');
		if ($errorprovedor)setEventMessages($langs->trans('Seleccione un proveedor'), null, 'warnings');

		if (!$fail && $id)
		{
			$db->commit();

			setEventMessages($langs->trans('OrderCreated'), null, 'mesgs');
			header('Location: replenishorders.php');
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
	if ($box == 0)
	{
		setEventMessages($langs->trans('SelectProductWithNotNullQty'), null, 'warnings');
	}
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$prod = new Product($db);

$title = $langs->trans('Status');

if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sqldesiredtock = $db->ifsql("pse.desiredstock IS NULL", "p.desiredstock", "pse.desiredstock");
	$sqlalertstock = $db->ifsql("pse.seuil_stock_alerte IS NULL", "p.seuil_stock_alerte", "pse.seuil_stock_alerte");
} else {
	$sqldesiredtock = 'pw.desiredstock';
	$sqlalertstock = 'pw.seuil_stock_alerte';
}


$sql = 'SELECT p.rowid, p.ref, p.label, p.description, p.price,';
$sql .= ' p.price_ttc, p.price_base_type,p.fk_product_type,';
$sql .= ' p.tms as datem, p.duration, p.tobuy,';
$sql .= ' pw.desiredstock,p.desiredstock_principal,p.desiredstock_gpe ,pw.seuil_stock_alerte,ent.rowid as fk_entrepot, ent.ref as warehouse,';
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql .= ' pse.desiredstock as desiredstockpse, pse.seuil_stock_alerte as seuil_stock_alertepse,';
}
$sql .= ' '.$sqldesiredtock.' as desiredstockcombined, '.$sqlalertstock.' as seuil_stock_alertecombined,';
$sql .= ' pw.fk_product,';
$sql .= ' IFNULL(s.reel,0) AS stock_physique,';
// Virtual stock
$sql .= ' IFNULL(s.reel, 0) as virtual_stock,';
// Stock CEDIS
$sql .= ' IFNULL(stock_cedis.reel, 0) - (IFNULL(stats_commande.qty, 0) - IFNULL(stats_sending.qty, 0) * -1) AS stock_cedis,';
$sql .= ' IFNULL(pw.stock_max, 0) AS stock_max,';

// all stock, stock reorder and all stock max 
$sql .= ' all_stock.stock AS all_stock, all_stock_reorder.stock_reorder AS all_stock_reorder, all_stock_max.stock_max AS all_stock_max, st.datem AS fecha_mov,';

// Stock Alerts and Desired by Warehouse
$sql .= ' IF(pw.seuil_stock_alerte IS NULL, 0, pw.seuil_stock_alerte) as alerte_warehouse,';
$sql .= ' IF(pw.desiredstock IS NULL, 0, pw.desiredstock) as desiredstock_warehouse,';

// Row needed for HAVING clause
$sql .= ' pr.commandes_cli, pr.expeditions_cli, pr.commandes_fourn, pr.production_to_produce, pr.production_to_consume,';
$sql .= ' stats_commande.nb_customers AS nb_customers_commande, stats_commande.nb AS nb_commande,';
$sql .= ' stats_commande.nb_rows AS nb_rows_commande, stats_commande.qty AS qty_commande, stats_sending.nb_customers AS nb_customers_sending,';
$sql .= ' stats_sending.nb AS nb_sending,stats_sending.nb_rows AS nb_rows_sending, stats_sending.qty AS qty_sending';

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
// Stock Alerts and Desired by Warehouse
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_warehouse_properties pw ON p.rowid = pw.fk_product";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot AS ent ON pw.fk_entrepot = ent.rowid AND ent.entity IN('.getEntity('stock').')';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as s ON s.fk_product = p.rowid AND s.fk_entrepot = ent.rowid ';

// all stock, stock reorder and all stock max joins 
$sql .= ' LEFT JOIN (SELECT ps.fk_product, SUM(IF(ps.fk_entrepot <> 29 AND ps.fk_entrepot <> 20, ps.reel, 0)) AS stock FROM '. MAIN_DB_PREFIX . 'product_stock ps GROUP BY ps.fk_product) all_stock ON all_stock.fk_product = p.rowid';
$sql .= ' LEFT JOIN (SELECT pw.fk_product, SUM(IF(pw.fk_entrepot <> 29 AND pw.fk_entrepot <> 20, pw.seuil_stock_alerte, 0)) AS stock_reorder FROM '. MAIN_DB_PREFIX . 'product_warehouse_properties pw GROUP BY pw.fk_product) all_stock_reorder ON all_stock_reorder.fk_product = p.rowid';
$sql .= ' LEFT JOIN (SELECT pw.fk_product, SUM(IF(pw.fk_entrepot <> 29 AND pw.fk_entrepot <> 20, pw.stock_max, 0)) AS stock_max FROM '. MAIN_DB_PREFIX . 'product_warehouse_properties pw GROUP BY pw.fk_product) all_stock_max ON all_stock_max.fk_product = p.rowid';
$sql .= ' LEFT JOIN (SELECT st.fk_product, MAX(st.datem) AS datem FROM '. MAIN_DB_PREFIX . 'stock_mouvement st GROUP BY st.fk_product) st ON st.fk_product = p.rowid';


// JOINS for virtual stock, stock reorder, load stats commande and sending
$sql .= ' LEFT JOIN (SELECT fk_product, SUM(commandes_cli) AS commandes_cli, SUM(expeditions_cli) AS expeditions_cli, SUM(commandes_fourn) AS commandes_fourn, SUM(production_to_produce) AS production_to_produce, SUM(production_to_consume) AS production_to_consume FROM llx_product_replenish GROUP BY fk_product) pr ON pr.fk_product = p.rowid';
// Stock CEDIS
$sql .= ' LEFT JOIN (SELECT ps.fk_product, ps.reel FROM llx_product_stock ps JOIN llx_entrepot e ON ps.fk_entrepot = e.rowid WHERE e.rowid = 29 AND e.entity IN (1)) stock_cedis ON stock_cedis.fk_product = p.rowid';
$sql .= ' LEFT JOIN (SELECT cd.fk_product, COUNT(DISTINCT c.fk_soc) AS nb_customers, COUNT(DISTINCT c.rowid) AS nb, COUNT(cd.rowid) AS nb_rows, IFNULL(SUM(cd.qty), 0) AS qty FROM llx_commandedet cd LEFT JOIN llx_commande c ON cd.fk_commande = c.rowid WHERE c.entity IN (1) AND c.fk_statut in (1, 2) GROUP BY cd.fk_product) stats_commande ON stats_commande.fk_product = p.rowid';
$sql .= ' LEFT JOIN (SELECT cd.fk_product, COUNT(DISTINCT e.fk_soc) AS nb_customers, COUNT(DISTINCT e.rowid) AS nb, COUNT(ed.rowid) AS nb_rows, IFNULL(SUM(ed.qty), 0) AS qty FROM llx_expeditiondet ed LEFT JOIN llx_commandedet cd ON ed.fk_origin_line = cd.rowid LEFT JOIN llx_commande c ON c.rowid = cd.fk_commande LEFT JOIN llx_expedition e ON e.rowid = ed.fk_expedition WHERE e.entity IN (1) AND c.fk_statut in (1, 2) AND e.fk_statut IN (1, 2) GROUP BY cd.fk_product) stats_sending ON stats_sending.fk_product = p.rowid';

// if ($fk_supplier > 0) {
// 	$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price pfp ON (pfp.fk_product = p.rowid AND pfp.fk_soc = '.$fk_supplier.')';
// }

if (!empty($searchCategoryProductList) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' WHERE p.entity IN ('.getEntity('product').') AND pw.fk_entrepot = ent.rowid';
if ($sall) $sql .= natural_search(array('p.ref', 'p.label', array('p.location_matriz','p.location_matriz2', 'p.location_gpe','p.location_gpe2'), 'p.description', 'p.note'), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type)) {
	if ($type == 1) {
		$sql .= ' AND p.fk_product_type = 1';
	} else {
		$sql .= ' AND p.fk_product_type <> 1';
	}
}if ($catid > 0)	 $sql .= " AND cp.fk_categorie = ".$catid;
if ($catid == -2)   $sql .= " AND cp.fk_categorie IS NULL";
$searchCategoryProductSqlList = array();
if ($searchCategoryProductOperator == 1) {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie = ".$db->escape($searchCategoryProduct);
		}
	}
	if (!empty($searchCategoryProductSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
	}
} else {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$searchCategoryProduct.")";
		}
	}
	if (!empty($searchCategoryProductSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
	}
}
//categorie
if ($catid > 0)	 $sql .= " AND cp.fk_categorie = ".$catid;
if ($catid == -2)   $sql .= " AND cp.fk_categorie IS NULL";
$searchCategoryProductSqlList = array();
if ($searchCategoryProductOperator == 1) {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie = ".$db->escape($searchCategoryProduct);
		}
	}
	if (!empty($searchCategoryProductSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
	}
} else {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$searchCategoryProduct.")";
		}
	}
	if (!empty($searchCategoryProductSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
	}
}
/** Filtering by Country / Rotation */
 if(!empty($selectedSearchRotation)) $sql .= ' AND p.rotation = "'.$db->escape($selectedSearchRotation).'"';

 /** Filtering by Warehouse */
 if($user->rights->stock->show_all_warehouses){
 	if(!empty($selectedSearchWarehouse)) $sql .= ' AND ent.rowid = "'.$db->escape($selectedSearchWarehouse).'"';
 }else{
	$entrepot = new Entrepot($db);
	$entrepot->fetch($user->fk_warehouse);
	$sql .= ' AND ent.rowid = "'.$db->escape($entrepot->id).'"';
 }

if ($sref) $sql .= natural_search('p.ref', $sref);
if ($snom) $sql .= natural_search('p.label', $snom);
if ($slocation) $sql .= natural_search(array('p.location_matriz','p.location_matriz2', 'p.location_gpe','p.location_gpe2'), $slocation);
$sql .= ' AND p.tobuy = 1';
if (!empty($canvas)) $sql .= ' AND p.canvas = "'.$db->escape($canvas).'"';
$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.description, p.price';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql .= ', p.duration, p.tobuy';
$sql .= ', pw.desiredstock';
$sql .= ', pw.seuil_stock_alerte';
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0) {
	$sql .= ', pse.desiredstock';
	$sql .= ', pse.seuil_stock_alerte';
}
$sql .= ', fecha_mov';
$sql .= ', pw.fk_product, s.reel, ent.rowid,pw.stock_max';
if($entrepot_id > 0 && ($entrepot_id == $conf->global->CEDIS_WAREHOUSE)){
	$sql .= ' HAVING ((virtual_stock > 0 OR stock_physique > 0) AND stock_cedis > 0)';
}else{
	$sql .= ' HAVING ((virtual_stock >= 0 OR stock_physique >= 0) AND stock_cedis > 0)';
}
//? Ignorar los productos que tienen envios pendientes, pedidios y facturas
// $sql .= ' AND ((pw.desiredstock >= 0 AND (pw.desiredstock >= stock_physique - (pr.commandes_cli - pr.expeditions_cli) + (pr.commandes_fourn - 0) + (pr.production_to_produce - pr.production_to_consume)))';
// $sql .= ' OR (pw.seuil_stock_alerte >= 0 AND (pw.seuil_stock_alerte >= stock_physique - (pr.commandes_cli - pr.expeditions_cli) + (pr.commandes_fourn - 0) + (pr.production_to_produce - pr.production_to_consume))))';

// If $objp->stock_max - ($stock + $objp->all_stock) < 0, then not show
if($entrepot_id > 0 && ($entrepot_id == $conf->global->CEDIS_WAREHOUSE)){
	$sql .= ' AND (IFNULL(pw.stock_max, 0) - (IFNULL(s.reel, 0) + all_stock.stock)) > 0';
}else{
	$sql .= ' AND (IFNULL(pw.stock_max, 0) - IFNULL(s.reel, 0)) > 0';
}

if ($usevirtualstock)
{

	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql .= ' AND (pw.desiredstock >= 0 AND (pw.desiredstock >= stock_physique))';
		$alertchecked = 'checked';
	}
} else {
	if ($salert == 'on')	// Option to see when stock is lower than alert
	{
		$sql .= ' AND (stock_physique <= pw.desiredstock)';
		$alertchecked = 'checked';
	}
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

$rc_total_pages = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$rc_total_pages = $db->num_rows($result);
	if (($page * $limit) > $rc_total_pages)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);
if (empty($resql))
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);
$i = 0;

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
$helpurl .= 'ES:M&oacute;dulo_Stocks';

llxHeader('', $title, $helpurl, '');

$head = array();
$head[0][0] = DOL_URL_ROOT.'/product/stock/replenish.php';
$head[0][1] = $title;
$head[0][2] = 'replenish';
$head[1][0] = DOL_URL_ROOT.'/product/stock/replenishreport.php';
$head[1][1] = $langs->trans("Reporte de reaprovisionamiento");
$head[1][2] = 'replenishreport';
$head[2][0] = DOL_URL_ROOT.'/product/stock/replenishorders.php';
$head[2][1] = $langs->trans("ReplenishmentOrders");
$head[2][2] = 'replenishorders';


$sqldate = "SELECT datelastrun from " . MAIN_DB_PREFIX . "cronjob WHERE methodename='updateStockReplenish'";
$resqldate = $db->query($sqldate);
$datelastrun = $db->fetch_object($resqldate)->datelastrun;
print '<table style="width: 100%;"><tr>';
print '<td>';
print load_fiche_titre($langs->trans('Replenishment'), '', 'generic');
print '</td>';
print '<td class="right" style="padding-right: 6vw">
		<h3>Fecha de última ejecución<br><span style="color: blue;">'.$datelastrun.'</span></h3>
	</td>';
print '</tr></table>';

dol_fiche_head($head, 'replenish', '', -1, '');

print $langs->trans("ReplenishmentStatusDesc").'<br>'."\n";
// if ($usevirtualstock == 1)
// {
// 	print $langs->trans("CurentSelectionMode").': ';
// 	print $langs->trans("CurentlyUsingVirtualStock").' - ';
// 	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=physical&fk_supplier='.$fk_supplier.'&fk_entrepot='.$fk_entrepot.'">'.$langs->trans("UsePhysicalStock").'</a><br>';
// }
// if ($usevirtualstock == 0)
// {
// 	print $langs->trans("CurentSelectionMode").': ';
// 	print $langs->trans("CurentlyUsingPhysicalStock").' - ';
// 	print '<a href="'.$_SERVER["PHP_SELF"].'?mode=virtual&fk_supplier='.$fk_supplier.'&fk_entrepot='.$fk_entrepot.'">'.$langs->trans("UseVirtualStock").'</a><br>';
// }
print '<br>'."\n";

print '<form name="formFilterWarehouse" method="GET" action="">';
print '<input type="hidden" name="action" value="filter">';
print '<input type="hidden" name="sref" value="'.$sref.'">';
print '<input type="hidden" name="entrepot_id" value="'.$selectedSearchWarehouse.'">';
print '<input type="hidden" name="search_category_product_operator" value="'.$searchCategoryProductOperator.'">';
print '<input type="hidden" name="rotation_id" value="'.$selectedSearchRotation.'">';
print '<input type="hidden" name="snom" value="'.$snom.'">';
print '<input type="hidden" name="slocation" value="'.$slocation.'">';
print '<input type="hidden" name="salert" value="'.$salert.'">';
print '<input type="hidden" name="draftorder" value="'.$draftorder.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE))
{
	print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
	print $langs->trans('Warehouse').' '.$formproduct->selectWarehouses($fk_entrepot, 'fk_entrepot', '', 1);
	print '</div>';
}
$filter_warehouse = "";
$show_empty_warehouse = 0;
if($entrepot_id > 0 && ($entrepot_id != $conf->global->CEDIS_WAREHOUSE)){
	$filter_warehouse = "fournisseur=1 AND nom='CEDIS'";
}else{
	$filter_warehouse = "fournisseur=1";
	$show_empty_warehouse = 1;
}

print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
print $langs->trans('Supplier').' '.$form->select_company($fk_supplier, 'fk_supplier', $filter_warehouse, $show_empty_warehouse);
print '</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) print $hookmanager->resPrint;

print '<div class="inline-block valignmiddle">';
print '<input class="button" type="submit" name="valid" value="'.$langs->trans('Select').'">';
print '</div>';
print '<div hidden>';
print $langs->trans('Categorias').': ';
$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
$categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
print $form->multiselectarray('search_category_product_list2', $categoriesProductArr, ($searchCategoryProductList ? $searchCategoryProductList: $searchCategoryProductList2), 0, 0, 'minwidth300');
print '</div>';

print '<div>';
if ($sref || $snom || $slocation || $sall || $salert || $draftorder || GETPOST('search', 'alpha')) {
	$filters = '&sref='.$sref.'&snom='.$snom.'&slocation='.$slocation;
	$filters .= '&sall='.$sall;
	$filters .= '&salert='.$salert;
	$filters .= '&draftorder='.$draftorder;
	$filters .= '&alreadyordered='.$alreadyordered;
	$filters .= '&mode='.$mode;
	$filters .= '&fk_supplier='.$fk_supplier;
	$filters .= '&fk_entrepot='.$fk_entrepot;
	$filters .= '&entrepot_id='.$selectedSearchWarehouse;
	$filters .= '&rotation_id='.$selectedSearchRotation;
	$filters .= '&search_category_product_operator='.$searchCategoryProductOperator;
	foreach ($searchCategoryProductList2 as $searchCategoryProduct) {
        $filters .= "&search_category_product_list2[]=".urlencode($searchCategoryProduct2);
    }
	if ($limit > 0 && $limit != $conf->liste_limit) $filters .= '&limit='.urlencode($limit);
	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $filters, $sortfield, $sortorder, '', $num, $rc_total_pages, 'products', 0, '', '', $limit);
} else {
	$filters = '&sref='.$sref.'&snom='.$snom.'&slocation='.$slocation;
	$filters .= '&fourn_id='.$fourn_id;
	$filters .= (isset($type) ? '&type='.$type : '');
	$filters .= '&='.$salert;
	$filters .= '&draftorder='.$draftorder;
	$filters .= '&alreadyordered='.$alreadyordered;
	$filters .= '&mode='.$mode;
	$filters .= '&fk_supplier='.$fk_supplier;
	$filters .= '&entrepot_id='.$selectedSearchWarehouse;
	$filters .= '&rotation_id='.$selectedSearchRotation;
	$filters .= '&search_category_product_operator='.$searchCategoryProductOperator;
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
        $filters .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
    }
	foreach ($searchCategoryProductList2 as $searchCategoryProduct) {
        $filters .= "&search_category_product_list2[]=".urlencode($searchCategoryProduct2);
    }
	if ($limit > 0 && $limit != $conf->liste_limit) $filters .= '&limit='.urlencode($limit);
	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $filters, $sortfield, $sortorder, '', $num, $rc_total_pages, 'products', 0, '', '', $limit);
}
print '</div>';

print '</form>';

print '<div class="div-table-responsive" style="max-width: 85vw;">';
print '<table class="liste centpercent">';

$param = (isset($type) ? '&type='.$type : '');
$param .= '&fourn_id='.$fourn_id.'&snom='.$snom.'&slocation='.$slocation.'&salert='.$salert.'&draftorder='.$draftorder.'&alreadyordered='.$alreadyordered;
$param .= '&sref='.$sref;
$param .= '&mode='.$mode;
$param .= '&fk_supplier='.$fk_supplier;
$param .= '&fk_entrepot='.$fk_entrepot;
$param .= '&entrepot_id='.$selectedSearchWarehouse;
$param .= '&rotation_id='.$selectedSearchRotation;
$param .= '&search_category_product_operator='.$searchCategoryProductOperator;
foreach ($searchCategoryProductList as $searchCategoryProduct) {
	$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
}
foreach ($searchCategoryProductList2 as $searchCategoryProduct) {
	$param .= "&search_category_product_list2[]=".urlencode($searchCategoryProduct2);
}
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);

$stocklabel = $langs->trans('Stock');
if ($usevirtualstock == 1){
	if($user->fk_warehouse == $conf->global->CEDIS_WAREHOUSE)
		$stocklabel = $langs->trans('VirtualStock');
	else
		$stocklabel = $langs->trans('Stock');
} 
	
if ($usevirtualstock == 0) $stocklabel = $langs->trans('PhysicalStock');
if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE) && $fk_entrepot > 0)
{
	$stocklabel .= ' ('.$langs->trans("AllWarehouses").')';
}
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">'.
	'<input type="hidden" name="token" value="'.newToken().'">'.
	'<input type="hidden" name="fk_supplier" value="'.$fk_supplier.'">'.
	'<input type="hidden" name="fk_entrepot" value="'.$fk_entrepot.'">'.
	'<input type="hidden" name="sortfield" value="'.$sortfield.'">'.
	'<input type="hidden" name="sortorder" value="'.$sortorder.'">'.
	'<input type="hidden" name="type" value="'.$type.'">'.
	'<input type="hidden" name="limit" value="'.$limit.'">'.
	'<input type="hidden" name="linecount" value="'.$num.'">'.
	'<input type="hidden" name="action" value="order">'.
	'<input type="hidden" name="mode" value="'.$mode.'">';

// First line of filters
print '<tr class="liste_titre"><td colspan="11">';
// Filter on categories
if (!empty($conf->categorie->enabled))
{
	print '<div class="divsearchfield">';
	print $langs->trans('Categorias').': ';
	$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
	$categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
	print $form->multiselectarray('search_category_product_list', $categoriesProductArr, ($searchCategoryProductList ? $searchCategoryProductList : $searchCategoryProductList2), 0, 0, 'minwidth300');
	print ' <input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories');
	print '</div>';
}
// Filter by Rotation
$rotation = [0 => ''];
$sql = "SELECT code, label FROM ".MAIN_DB_PREFIX."c_product_rotation WHERE active = 1";
$res = $db->query($sql);
while($item = $db->fetch_object($res)){
	$rotation[$item->code] = $item->label;
}
print '<div class="divsearchfield" style="margin-top: 8px;">';
print $langs->trans('Rotation').': ';
print $form->selectarray('rotation_id', $rotation, $selectedSearchRotation);
print '</div>';

// Filter by Warehouse
$warehouse = [0 => ''];
$sql = "SELECT rowid, ref, fk_parent FROM ".MAIN_DB_PREFIX."entrepot WHERE statut = 1";
$formproduct = new FormProduct($db);
$formproduct->loadWarehouses();
$res = $db->query($sql);

foreach($formproduct->cache_warehouses as $rowid => $w) {
	$warehouse[$rowid] = $w['full_label'];
}

if($user->rights->stock->show_all_warehouses){
	print '<div class="divsearchfield" style="margin-top: 8px;">';
	print $langs->trans('Warehouse') . ': ';
	print $form->selectarray('entrepot_id', $warehouse, $selectedSearchWarehouse);
	print '</div>';
}else{
	$entrepot = new Entrepot($db);
	$entrepot->fetch($user->fk_warehouse);
	print '<div class="divsearchfield" style="margin-top: 8px;">';
	print '<input type="hidden" name="entrepot_id" value="'.$entrepot->id.'">';
	print $langs->trans('Warehouse') . ': ';
	$warehouse = array();
	$warehouse[$entrepot->id] = $entrepot->ref;
	print $form->selectarray('entrepot_id', $warehouse, $selectedSearchWarehouse);
	print '</div>';
}
print '</td></tr>';

// Fields title search
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre"><input class="flat" type="text" name="sref" size="8" value="'.dol_escape_htmltag($sref).'"></td>';
print '<td class="liste_titre"><input class="flat" type="text" name="slocation" size="8" value="'.dol_escape_htmltag($slocation).'"></td>';
if (!empty($conf->service->enabled) && $type == 1) print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre" colspan=2>&nbsp;</td>';
print '<td class="liste_titre right">&nbsp;</td>';
print '<td class="liste_titre right">
	<div style="display: grid; grid-template-columns: 5fr 1fr; grid-auto-flow: row;">
		<div style="text-align: center;">'.$langs->trans('AlertOnly').'</div>
		<div style="align-self: center;"><input type="checkbox" id="salert" name="salert" '.(!empty($alertchecked) ? $alertchecked : '').'></div>
	</div>
</td>';

// Fields from hook
$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<td class="liste_titre maxwidthsearch right" colspan="3">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

// Lines of title
print '<tr class="liste_titre">';
print_liste_field_titre('<input type="checkbox" onClick="toggle(this)" id="all_checks" />', $_SERVER["PHP_SELF"], '');
print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre('Categorias', $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);
print '<th class="liste_titre">Almacén</th>';
print '<th class="liste_titre">Stock CEDIS</th>';
if($entrepot_id > 0 && ($entrepot_id == $conf->global->CEDIS_WAREHOUSE)){
	print '<th class="liste_titre">Stock Sucursales</th>';
}
print '<th class="liste_titre">Stock de Reórden</th>';
// if (!empty($conf->service->enabled) && $type == 1) print_liste_field_titre('Duration', $_SERVER["PHP_SELF"], 'p.duration', $param, '', '', $sortfield, $sortorder, 'center ');
// print_liste_field_titre('DesiredStock', $_SERVER["PHP_SELF"], 'p.desiredstock', $param, '', '', $sortfield, $sortorder, 'right ');
// print_liste_field_titre('StockLimitShort', $_SERVER["PHP_SELF"], 'p.seuil_stock_alerte', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], 'stock_physique', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('Stock Máximo', $_SERVER["PHP_SELF"], 'all_stock_max', $param, '', '', $sortfield, $sortorder, 'right ');
// print_liste_field_titre('Ordered', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('StockToBuy', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('SupplierRef', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');

// Hook fields
$parameters = array('param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</tr>\n";
if($fk_supplier) {
	while ($i < ($limit ? min($num, $limit) : $num))
	{
		$objp = $db->fetch_object($resql);

		if (!empty($conf->global->STOCK_SUPPORTS_SERVICES) || $objp->fk_product_type == 0)
		{
			$prod->fetch($objp->rowid);
			$prod->load_stock('warehouseopen, warehouseinternal');

			$ordered = $prod->stats_commande_fournisseur['qty'] - $prod->stats_reception['qty'];

			// Show only with $ordered > 0  if isset $orderedchecked
			if (!isset($orderedchecked) || $ordered > 0) {
				// Multilangs
				if (!empty($conf->global->MAIN_MULTILANGS))
				{
					$sql = 'SELECT label,description';
					$sql .= ' FROM '.MAIN_DB_PREFIX.'product_lang';
					$sql .= ' WHERE fk_product = '.$objp->rowid;
					$sql .= ' AND lang = "'.$langs->getDefaultLang().'"';
					$sql .= ' LIMIT 1';

					$resqlm = $db->query($sql);
					if ($resqlm)
					{
						$objtp = $db->fetch_object($resqlm);
						if (!empty($objtp->description)) $objp->description = $objtp->description;
						if (!empty($objtp->label)) $objp->label = $objtp->label;
					}
				}

				$total_physical_stock = 0;
				$total_to_deliver = 0;
				$total_virtual_stock = 0;

				// Virtual Stock
				$result = $prod->load_stats_commande_fournisseur(0, '3,4', 1);
				$virtual_stock = $prod->stock_reel - $stock_to_deliver + $prod->stats_commande_fournisseur['qty'];

				$total_physical_stock += $prod->stock_reel;
				$total_to_deliver += $stock_to_deliver;
				$total_virtual_stock += $virtual_stock; 

				$result = $prod->load_stats_commande_fournisseur(0, '3,4', 1);
				$pedidosOC = $prod->stats_commande_fournisseur['qty'];
				//Formula Stock Virtual
				$total_virtual = $total_physical_stock - $total_to_deliver;

				if ($usevirtualstock)
				{
					// If option to increase/decrease is not on an object validation, virtual stock may differs from physical stock.
					$stock = $total_virtual;
				}
				else
				{
					$stock = $prod->stock_reel;
				}

				// Force call prod->load_stats_xxx to choose status to count (otherwise it is loaded by load_stock function)
				if (isset($draftchecked)) {
					$result = $prod->load_stats_commande_fournisseur(0, '0,1,2,3,4');
				} else {
					$result = $prod->load_stats_commande_fournisseur(0, '1,2,3,4');
				}

				$result = $prod->load_stats_reception(0, '4');

				//print $prod->stats_commande_fournisseur['qty'].'<br>'."\n";
				//print $prod->stats_reception['qty'];

				// $desiredstock = ($objp->desiredstockpse ? $objp->desiredstockpse : $objp->desiredstock);
				$desiredstock = ($objp->desiredstock ? $objp->desiredstock : 0); // Changed to take only main stock into count

				$alertstock = ($objp->seuil_stock_alertepse ? $objp->seuil_stock_alertepse : $objp->seuil_stock_alerte);
				$desiredstock_principal = ($objp->desiredstock_principal ? $objp->desiredstock_principal : 0);
				$desiredstock_gpe = ($objp->desiredstock_gpe ? $objp->desiredstock_gpe : $objp->desiredstock_gpe);

				$warning = '';
				// Rojo: si el stock físico en almacén es igual o menor al stock mínimo
				if (($objp->stock_physique <= $objp->desiredstock) || $objp->stock_physique < 0)
				{
					$warning = img_warning($langs->trans('StockTooLow')).' ';
				}
				// Naranja: si el stock físico en el almacén es igual o menor al stock de reorden y mayor al stock mínimo.
				if (($objp->stock_physique <= $objp->seuil_stock_alerte) && ($objp->stock_physique > $objp->desiredstock))
				{
					$warning = '<i class="fa fa-exclamation-triangle" aria-hidden="true" style="color: orange; font-size:24px;" title="Stock bajo"></i> ';
				}

				//depending on conf, use either physical stock or
				//virtual stock to compute the stock to buy value
				$stocktobuy = max(max($desiredstock, $alertstock) - $stock - $ordered, 0);
				$disabled = '';
				if ($ordered > 0)
				{
					$stockforcompare = $usevirtualstock ? $stock : $stock + $ordered;
					if ($stockforcompare >= $desiredstock)
					{
						$picto = img_picto('', './img/yes', '', 1);
						$disabled = 'disabled';
					}
					else {
						$picto = img_picto('', './img/no', '', 1);
					}
				} else {
					//$picto = img_help('',$langs->trans("NoPendingReceptionOnSupplierOrder"));
					$picto = img_picto($langs->trans("NoPendingReceptionOnSupplierOrder"), './img/no', '', 1);
				}

				print '<tr class="oddeven">';

				// Checkbox
				print '<td>';
				print '<input type="checkbox" class="check" name="choose'.$i.'">';
				print '</td>';

				// Ref
				print '<td hidden><input hidden name="id'.$i.'" value="'.$prod->id.'"></td>';
				print '<td class="nowrap minwidth100">'.$prod->getNomUrl(1, '').'</td>';

				// Categorías
				print '<td class="minwidth200">';
				print $form->showCategories($objp->rowid, 'product', 1);
				print '</td>';

				// Almaén
				print '<td class="minwidth100">';
				$entrepot = new Entrepot($db);
				$entrepot->fetch($objp->fk_entrepot);
				print $entrepot->getNomUrl(1, '');
				print '</td>';

				// Stock CEDIS
				print '<td class="minwidth100">';
				print '<input type="hidden" id="cedis_stock'.$i.'" name="cedis_stock'.$i.'" value="'.$objp->stock_cedis.'">';
				print $objp->stock_cedis;

				print '</td>';

				// Stock Sucursales
				if($entrepot_id > 0 && ($entrepot_id == $conf->global->CEDIS_WAREHOUSE)){
					print '<td class="minwidth100">' . $objp->all_stock . '</td>';
				}

				// Stock de Reórden
				print '<td class="minwidth100">' . $objp->seuil_stock_alerte . '</td>';

				// Stock deseado
				// print '<td class="right" style="min-width: 75px;">'.($selectedSearchWarehouse ? $objp->desiredstock_warehouse : $desiredstock).'</td>';

				//Stock deseado en almacen Matriz
				// print '<td class="right" style="min-width: 75px;">'.$desiredstock_principal.'</td>';

				//Stock deseado en almacen Guadalupe
				// print '<td class="right" style="min-width: 75px;">'.$desiredstock_gpe.'</td>';

				// Limit stock for alert
				// print '<td class="right" style="min-width: 75px;">'. ($selectedSearchWarehouse ? $objp->alerte_warehouse : $alertstock).'</td>';

				// Current stock (all warehouses) (stock virtual)
				$virtual_stock = $objp->virtual_stock;
				$physical_stock = $objp->stock_physique;
				$stock = 0;
				if($entrepot_id > 0 && ($entrepot_id == $conf->global->CEDIS_WAREHOUSE)){
					$stock = $objp->stock_cedis;
				}else{
					$stock = $objp->virtual_stock;
				}
				print '<td class="right minwidth100">'.  ($warning. $stock) .'</td>';

				// Stock máximo
				print '<td class="right minwidth100">'. $objp->stock_max .'</td>';
				
				// Stock pedido
				// print '<td class="right" style="min-width: 120px;"><a href="replenishorders.php?sproduct='.$prod->id.'">'.$ordered.'</a> '.$picto.'</td>';


				// Stock a pedir
				$stocktobuy = $objp->stock_max - $stock;
				if($mode == 'virtual') {
					if($objp->fk_entrepot != $conf->global->CEDIS_WAREHOUSE){
						if(($stocktobuy <= $objp->stock_cedis) && ($stocktobuy >= 0)) $stocktobuy = $objp->stock_max - $stock;
						else if($objp->stock_max <= 0) $stocktobuy = 0;
						else $stocktobuy = $objp->stock_cedis;
					}else{
						$stocktobuy = $objp->stock_max - ($stock + $objp->all_stock);
						if($stocktobuy < 0) $stocktobuy = 0;

					}
				} 
				// else {
				// 	if($objp->fk_entrepot != $conf->global->CEDIS_WAREHOUSE){
				// 		if(($stocktobuy <= $objp->stock_cedis) && ($stocktobuy > 0)) $stocktobuy = $objp->stock_max - $objp->stock_physique;
				// 		else if($objp->stock_max <= 0) $stocktobuy = 0;
				// 		else $stocktobuy = $objp->stock_cedis;
				// 	}else{
				// 		if($stocktobuy <= 0) $stocktobuy = 0;
				// 	}
				// }

				$functions_no_cedis = "";
				$disabledQuantity = '';
				if($objp->fk_entrepot != $conf->global->CEDIS_WAREHOUSE) $functions_no_cedis = "oninput='validarNum(".$i.")' onchange='limpiarInput()'";
				if($functions_no_cedis) {
					$disabledQuantity = 'readonly';
				}
				print '<td class="right" style="min-width: 120px;"><input type="text" '. $disabledQuantity .'  id="tobuy'.$i.'" ' . $functions_no_cedis . ' size="4" name="tobuy'.$i.'" value="'.$stocktobuy.'"></td>';

				
				print "
				<script>
					function validarNum(index) {
						var input = document.getElementById('tobuy' + index);
						var input_stock = document.getElementById('cedis_stock' + index);
						var valor = parseFloat(input.value); // Convertir a número
						var valor_cedis = parseFloat(input_stock.value); // Convertir a número

						valor = isNaN(valor) ? 0 : valor; // Manejar el caso en que no sea un número
						input.value = valor;

						if (valor > valor_cedis) {
							var span = document.createElement('span');
							span.style.color = 'red';
							span.innerHTML = 'La cantidad a pedir no puede ser mayor que el stock en CEDIS.';

							if (input.parentNode.lastChild.tagName == 'SPAN') {
								input.parentNode.removeChild(input.parentNode.lastChild);
							}

							if (input.parentNode.lastChild.tagName == 'BR') {
								input.parentNode.removeChild(input.parentNode.lastChild);
							}

							input.parentNode.appendChild(document.createElement('br'));
							input.parentNode.appendChild(span);

							input.value = valor_cedis;

							if(valor_cedis > 0) input.value = valor_cedis;
							else input.value = 0;
						}else{
							// Validamos que no exista un salto de línea
							if (input.parentNode.lastChild.tagName == 'BR') {
								input.parentNode.removeChild(input.parentNode.lastChild);
							}
							// Antes de insertar validamos que no exista ya el span
							if (input.parentNode.lastChild.tagName == 'SPAN') {
								input.parentNode.removeChild(input.parentNode.lastChild);
							}
						}
					}
				</script>";		

				// Ref. alterna
				if($fk_supplier > 0){
					$sql = " SELECT  s.nom, s.rowid, pfp.ref_fourn, p.cost_price as unitprice, pfp.remise_percent, p.cost_price_sucursal ";
					$sql .= " FROM ".MAIN_DB_PREFIX."societe AS s ";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price AS pfp ON pfp.fk_soc = s.rowid ";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid = pfp.fk_product ";
					$sql .= " WHERE pfp.fk_product = ".$prod->id;
					
					$res = $db->query($sql);
					$fournisseur = $db->fetch_object($res);

					$fourn = new Fournisseur($db);
					$fourn->fetch($fk_supplier);					

					if($user->fk_warehouse != $conf->global->CEDIS_WAREHOUSE){
						print '<td class="right" style="min-width: 140px;">';
						print $fourn->getNomUrl(1).' - '. "$" . price($fournisseur->cost_price_sucursal);  
						print '</td>';
					}else{
						print '<td class="right" style="min-width: 140px;">';
						print $fourn->getNomUrl(1).' - '. "$" . price($fournisseur->unitprice);  
						print '</td>';
					}
				}else{
					print '<td class="right" style="min-width: 140px;">';
					print '<span><b>No hay proveedor seleccionado</b></span>';
					print '</td>';
				}

				// Fields from hook
				$parameters = array('objp'=>$objp);
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				print '</tr>';	
			}
		}
		$i++;
	}
}

$parameters = array('sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print '</div>';

$db->free($resql);

dol_fiche_end();


$value = $langs->trans("CreateOrders");
print '<div class="center"><input class="button" type="submit" name="valid" value="'.$value.'"></div>';


print '</form>';


// TODO Replace this with jquery
print '
<script type="text/javascript">
	// when load the page check all checkbox
	 window.onload = function() {
	 	let all_checks = document.getElementById("all_checks");
		all_checks.checked = true;
		toggle(all_checks);
    };



	function toggle(source)
	{
		checkboxes = document.getElementsByClassName("check");
		for (var i=0; i < checkboxes.length;i++) {
			if (!checkboxes[i].disabled) {
				checkboxes[i].checked = source.checked;
			}
		}
	}
</script>';


llxFooter();

$db->close();

/**
 *  Regresa rowid y tickets de venta POS del producto
 *
 *	@param  object  $db         Base de datos
 *	@param  int     $fk_product Producto
 *  @return array               Lista de tickets POS
 */
function rc_getTickets(&$db, $fk_product)
{
	$sql =  'SELECT t.rowid,t.ticketnumber '
            .'FROM llx_pos_ticket AS t '
			.'LEFT JOIN llx_pos_ticketdet AS td '
			.'  ON t.rowid=td.fk_ticket '
			.'WHERE td.fk_product='.$fk_product.' '
			.'  AND t.fk_statut IN(1,2) '
			.'  AND td.ls_warehouse_status NOT IN(6)'
			.'  AND t.type=0 ';
	if (!$res = $db->query($sql))
	{
		dol_print_error($db);
		die();
	}
	$rows = array();
	while ($row = $db->fetch_object($res))
	{
		$rows[$row->rowid] = $row->ticketnumber;
	}
	return $rows;
}
