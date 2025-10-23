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
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT."/cron/class/cronjob.class.php";
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

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
$id_sw = GETPOST('id_sw', 'int');
$id_tw = GETPOST('id_tw', 'int');
$batch = GETPOST('batch');
$qty = GETPOST('qty');
$idline = GETPOST('idline');
$confirm=GETPOST('confirm', 'alpha');
$borrar=GETPOST('borrar_registros');
$search_ref =GETPOST('search_ref');
$search_label =GETPOST('search_label');
$search_warehouseO = trim(GETPOST("search_warehouseO"));
$search_warehouseD = trim(GETPOST("search_warehouseD"));
$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
$catid = GETPOST('catid', 'int');
//$draft = GETPOST('draft','int');
//if (!($draft>0))
//{
	$draft = 1;
//}

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1

if (!$sortfield) {
    $sortfield = 'p.ref';
}

if (!$sortorder) {
    $sortorder = 'ASC';
}
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;


if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_ref = "";
	$search_label = "";
	$search_warehouseO = "";
    $search_warehouseD = "";
	$searchCategoryProductList = array();
}


//$sql = "SELECT rowid, fk_product, qty, fk_entrepot_source, fk_entrepot_target, tms FROM stock_mouvement_auto";

$sql = "SELECT s.rowid, s.fk_product, p.ref, p.label, s.qty, s.fk_entrepot_source, s.fk_entrepot_target, s.tms 
			FROM stock_mouvement_auto as s, ".MAIN_DB_PREFIX."product as p ";
		if (!empty($searchCategoryProductList) || !empty($catid)){
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product ";
		} 
$sql .= " WHERE s.fk_product = p.rowid ";
if ($catid > 0)     $sql .= " AND cp.fk_categorie = ".$catid;
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
if ($catid > 0)     $sql .= " AND cp.fk_categorie = ".$catid;
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


if ($search_warehouseO != '' && $search_warehouseO != '-1')          $sql .= natural_search('s.fk_entrepot_source', $search_warehouseO, 2);
if ($search_warehouseD != '' && $search_warehouseD != '-1')          $sql .= natural_search('s.fk_entrepot_target', $search_warehouseD, 2);
if ($search_ref)    $sql .= natural_search('p.ref', $search_ref);
if ($search_label)    $sql .= natural_search('p.label', $search_label);

$restl = $db->query($sql);

$listofdata = array();
if ($restl) {
	while ($obj = $db->fetch_object($restl))
		$listofdata[] = $obj;
}
else setEventMessages($db->lasterror(), null, 'errors');

$draft_count = 0;
if ($action == 'createmovements' && $borrar!=null){
    $error = 0;
    $eliminados=0;
    if (!GETPOSTISSET("tsm")) {
        $error++;
        setEventMessages($langs->trans("ErrorSelectAtLeastOne"), null, 'errors');
    }
    else $rowids = GETPOST('tsm', 'none', 2); // Rowids to delete

    if (!$error) {
        foreach ($listofdata as $key => $val)	// Loop on each movement to do
        {
            if (array_key_exists($val->rowid, $rowids)) {
                $sql="DELETE  from stock_mouvement_auto where rowid = ".$val->rowid;
                $res=$db->query($sql);
                if ($res==true){
                  $eliminados++;
                }
            }
        }
        setEventMessages($langs->trans("Se eliminaron ".$eliminados. " registros"), null, 'mesgs');
        $action='';
        header("Location: ".$_SERVER["PHP_SELF"]);
    }
}

if ($action == 'createmovements' && $borrar==null)
{
	$error = 0;

	// Obligatory fields
	if (!GETPOST("codemove")) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InventoryCode")), null, 'errors');
	}

	if (!GETPOST("label"))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MovementLabel")), null, 'errors');
	}

	if (!GETPOSTISSET("tsm")) {
		$error++;
		setEventMessages($langs->trans("ErrorSelectAtLeastOne"), null, 'errors');
	}
	else $rowids = GETPOST('tsm', 'none', 2); // Rowids to transfer

	if (!$error)
	{
		$db->begin();
	
		// Entries to delete from DB
		$recordsToDel = array();

		$product = new Product($db);

		foreach ($listofdata as $key => $val)	// Loop on each movement to do
		{
			// If rowid exist in rowids to transfer
			if (array_key_exists($val->rowid, $rowids)) {
				$id = $val->rowid;
				$id_product = $val->fk_product;
				$id_sw = $val->fk_entrepot_source;
				$id_tw = $val->fk_entrepot_target;
				$qty = price2num($val->qty);
				$dlc = -1;
				$dluo = -1;
	
				if (!$error && $id_sw <> $id_tw && is_numeric($qty) && $id_product)
				{
					$result = $product->fetch($id_product);
	
					$product->load_stock('novirtual'); // Load array product->stock_warehouse
	
					// Define value of products moved
					$pricesrc = 0;
					if (!empty($product->pmp)) $pricesrc = $product->pmp;
					$pricedest = $pricesrc;
	
					//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;
	
					if (empty($conf->productbatch->enabled) || !$product->hasbatch())		// If product does not need lot/serial
					{
						// Remove stock
						$result1 = $product->correct_stock(
							$user,
							$id_sw,
							$qty,
							1,
							GETPOST("label"),
							$pricesrc,
							GETPOST("codemove"),
							'MassStockAuto',
							1,
							$draft,
							$id_tw
						);
						if ($result1 < 0)
						{
							$error++;
							setEventMessages($product->errors, $product->errorss, 'errors');
						}
	
						// Add stock
						$result2 = $product->correct_stock(
							$user,
							$id_tw,
							$qty,
							0,
							GETPOST("label"),
							$pricedest,
							GETPOST("codemove"),
							'MassStockAuto',
							1,
							$draft,
							$id_sw
						);
						if ($result2 < 0)
						{
							$error++;
							setEventMessages($product->errors, $product->errorss, 'errors');
						}
					}
					else
					{
						$arraybatchinfo = $product->loadBatchInfo($batch);
						if (count($arraybatchinfo) > 0)
						{
							$firstrecord = array_shift($arraybatchinfo);
							$dlc = $firstrecord['eatby'];
							$dluo = $firstrecord['sellby'];
							//var_dump($batch); var_dump($arraybatchinfo); var_dump($firstrecord); var_dump($dlc); var_dump($dluo); exit;
						}
						else
						{
							$dlc = '';
							$dluo = '';
						}
	
						// Remove stock
						$result1 = $product->correct_stock_batch(
							$user,
							$id_sw,
							$qty,
							1,
							GETPOST("label"),
							$pricesrc,
							$dlc,
							$dluo,
							'',
							GETPOST("codemove"),
							'MassStockAuto'
						);
						if ($result1 < 0)
						{
							$error++;
							setEventMessages($product->errors, $product->errorss, 'errors');
						}
	
						// Add stock
						$result2 = $product->correct_stock_batch(
							$user,
							$id_tw,
							$qty,
							0,
							GETPOST("label"),
							$pricedest,
							$dlc,
							$dluo,
							'',
							GETPOST("codemove"),
							'MassStockAuto'
						);
						if ($result2 < 0)
						{
							$error++;
							setEventMessages($product->errors, $product->errorss, 'errors');
						}
					}
	
					// Add rowid to entries to delete.
					if (!$error) $recordsToDel [] = "rowid = $val->rowid";
				}
				else
				{
					// dol_print_error('',"Bad value saved into sessions");
					$error++;
				}
			}
		}

		if (!$error)
		{
			// Delete rowis from DB
			if (sizeof($recordsToDel) > 0) {
				$sqldel = "DELETE FROM stock_mouvement_auto WHERE " . implode(" OR ", $recordsToDel);
				$resdel = $db->query($sqldel);

				// Query success
				if ($resdel) {
					$db->commit();
					if($draft) {
						setEventMessages($langs->trans("Movimiento guardado y en espera de autorización"), null, 'mesgs');
						header("Location: ".DOL_URL_ROOT.'/product/stock/movement_list_draft.php'); // Redirect to avoid pb when using back
						exit;
					}
					else {
						$codemovement = GETPOST("codemove");
						setEventMessages($langs->trans("StockMovementRecorded"), null, 'mesgs');
						header("Location: ".DOL_URL_ROOT."/product/stock/movement_card.php?id=$id_sw&search_inventorycode=$codemovement&search_type_mouvement=1"); // Redirect to avoid pb when using back
						exit;
					}
				}
				// Query error
				else {
					$db->rollback();
					setEventMessages($db->lasterror(), null, 'errors');
				}
			}
			// Nothing to transfer
			else {
				$db->rollback();
				setEventMessages($langs->trans("ErrorNoEntriesToTransfer"), null, 'errors');
			}
		}
		// Internal error
		else
		{
			$db->rollback();
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}
//Aqui va el permiso para ejecutarlo
if ($action == 'confirm_execute' && $confirm == "yes"  && $user->rights->stock->execute_crjob)
{
    $object = new Cronjob($db);
    $sql3="SELECT rowid from ".MAIN_DB_PREFIX."cronjob where methodename='massStockAutomatic'";
    $resql3=$db->query($sql3);
    if ($db->num_rows($resql3) >= 1){
        $obj2=$db->fetch_object($resql3);
        $idcron=$obj2->rowid;
        $result=$object->fetch($idcron);

        $now = dol_now();   // Date we start

        $result=$object->run_jobs($user->login);

        if ($result < 0)
        {
            setEventMessages($object->error, $object->errors, 'errors');
            $action='';
        }
        else
        {
            $res = $object->reprogram_jobs($user->login, $now);
            if ($res > 0)
            {
                if ($object->lastresult > 0) setEventMessages($langs->trans("JobFinished"), null, 'warnings');
                else setEventMessages($langs->trans("JobFinished"), null, 'mesgs');
                $action='';
                header("Location: ".$_SERVER["PHP_SELF"]);
            }
            else
            {
                setEventMessages($object->error, $object->errors, 'errors');
                $action='';
            }
        }
    }else{
        setEventMessages("No existe alguna tarea programada para esta funcion", null,'errors');
    }
}

/*
elseif ($action == 'set_tms_percents') {
	$tms_w1 = GETPOST('tmsp_wh1', 'int', 2);
	$tms_w2 = GETPOST('tmsp_wh2', 'int', 2);

	// Required fields
	if (empty($tms_w1) || empty($tms_w2)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Percent")), null, 'errors');
	}

	// Check total
	if (($tms_w1 + $tms_w2) != 100) {
		$error++;
		setEventMessages($langs->trans("ErrorTotalTmsPercent"), null, 'errors');
	}

	$tms = array($tms_w1, $tms_w2);

	if (!$error) {
		$entrepot = new Entrepot($db);
		// Update tms for every warehouse
		foreach([1, 2] as $index => $entrepotid) {
			if ($entrepot->fetch($entrepotid) > 0) {
				if (!$entrepot->updateTransferPercent($tms[$index])) {
					$error++;
					break;
				}
			}
		}

		unset ($entrepot);

		// Success
		if (!$error) setEventMessages($langs->trans("TmsPercentsUpdated"), null);
		// Error
		else setEventMessages($langs->trans("ErrorTmsPercent"), null, 'errors');
	}

}
*/

/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formproduct = new FormProduct($db);
$productstatic = new Product($db);
$warehousestatics = new Entrepot($db);
$warehousestatict = new Entrepot($db);


$title = $langs->trans('MassMovement');
$linkback = '<a href="'.DOL_URL_ROOT . '/product/stock/massstockmove.php">'.$langs->trans("BackToStockTransfer").'</a>';

llxHeader('', $title);

print load_fiche_titre($langs->trans("AutoMassStock", sizeof($listofdata)), $linkback);
if ($action=='start_cron'){
    print $form->formconfirm($_SERVER['PHP_SELF'], "Transferencia Interna", "¿Esta seguro de ejecutar el cálculo de Transferencias Interna entre Almacenes?", "confirm_execute", '', '', 1);

    $action='';
}

// Warehouse percente of automated transfers
/*
print '<div class="div-table-responsive-no-min">';
print '<table class="liste centpercent">';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="wh_percents">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_tms_percents">';
print '<input type="hidden" name="draft" value="'.$draft.'">';
print '<tr class="liste_titre">';
print getTitleFieldOfList($langs->trans('Warehouse'), 0, '', '', '', '', '', '', '', 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Percent'), 0, '', '', '', '', '', '', '', 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Warehouse'), 0, '', '', '', '', '', '', '', 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Percent'), 0, '', '', '', '', '', '', '', 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList('', 0);
print '</tr>';

$warehousestatics->fetch(1);
print '<tr class="oddeven"><td>'.$warehousestatics->getNomUrl(1).'</td>';
print '<td><input type="number" min="1" max="99" name="tmsp_wh1" value="'.$warehousestatics->tms_percent.'"></td>';

$warehousestatict->fetch(2);
print '<td>'.$warehousestatict->getNomUrl(1).'</td>';
print '<td><input type="number" min="1" max="99" name="tmsp_wh2" value="'.$warehousestatict->tms_percent.'"></td>';

print '<td class="center"><input type="submit" class="button" name="save_tms" value="'.dol_escape_htmltag($langs->trans("Save")).'"></td></tr>';

print '</form>';
print '</table>';
print '</div><br><br>';
*/

// Transfers calculated
$buttonrecord = $langs->trans("RecordMovement");


    echo '<div>';
    echo '<div style="width:70%;display:inline-block;vertical-align:top;">';
    print '<span class="opacitymedium">'.$langs->trans("SelectAutomatedTransfers").'</span><br>';
    echo '</div>';
if ($user->rights->stock->execute_crjob) {
    echo '<div style="width:29%;display:inline-block;vertical-align:top;float:right;text-align:right;">';
    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="formulaire">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="start_cron">';
    print '<button class="btnTitle classfortooltip" '
        . 'style="width:209px;">'
        . '<span class="fa fa-play-circle  valignmiddle btnTitle-icon" ></span>'
        . '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone">'
        . 'Ejecución de Cálculo'
        . '</span>'
        . '</button>';
    print '</form>';
    echo '</div>';
}
    echo '</div>';
    print '<br>' . "\n";


print '<br>'."\n";

print '<div class="div-table-responsive-no-min">';
print '<table class="liste centpercent">';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire2">';

// Lines for filters fields
print '<tr class="liste_titre_filter">';
print '<td></td>';
//BUSCADOR POR REF
print '<td class="liste_titre">';
print '<input class="flat" type="text" size="10" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';
//BUSCADOR POR ETIQUETA
print '<td class="liste_titre">';
print '<input class="flat" type="text" size="38" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
print '</td>';
print '<td></td>';
//BUSCADOR POR ALAMCEN DE ORIGEN
print '<td class="liste_titre maxwidthonsmartphone left">';
print $formproduct->selectWarehouses($search_warehouseO, 'search_warehouseO', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
print '</td>';

//BUSCADOR POR ALAMCEN DE DESTINO
print '<td class="liste_titre maxwidthonsmartphone left" colspan="3">';
print $formproduct->selectWarehouses($search_warehouseD, 'search_warehouseD', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
print '</td>';

print '<td class="liste_titre">';
$searchpicto=$form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';

print "</tr>\n";
//BUSCADOR POR CATEGORIAS
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print '<div class="divsearchfield">';
    print $langs->trans('Categorias').': ';
    $categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
    $categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
    print Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
    print ' <input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories');
    print '</div>';
    print '</div>';
$param = '';

print '<tr class="liste_titre">';
print getTitleFieldOfList('', 0);
print getTitleFieldOfList($langs->trans('Ref'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Label'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Category'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
if ($conf->productbatch->enabled) {
	print getTitleFieldOfList($langs->trans('Batch'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
}
print getTitleFieldOfList($langs->trans('WarehouseSource'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('WarehouseTarget'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Qty'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Usuario'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print getTitleFieldOfList($langs->trans('Fecha'), 0, $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'center tagtd maxwidthonsmartphone ');
print '</tr>';

foreach ($listofdata as $val)
{
	$productstatic->fetch($val->fk_product);
	$warehousestatics->fetch($val->fk_entrepot_source);
	$warehousestatict->fetch($val->fk_entrepot_target);

	print '<tr class="oddeven">';
    print '<td><input type="checkbox" name="tsm['.$val->rowid.']"></td>';
	print '<td>';
	print $productstatic->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $productstatic->label;
	print '</td>';
	print '<td class="tdoverflowmax200">';
	print $form->showCategories($val->fk_product, 'product', 1);
	print '</td>';
	print '<td>';
	print $warehousestatics->getNomUrl(1);
	print '</td>';
	print '<td>';
	print $warehousestatict->getNomUrl(1);
	print '</td>';
	print '<td class="center">'.$val->qty.'</td>';
	print '<td class="center">'.$user->login.'</td>';
	print '<td class="center">'.$val->tms.'</td>';

	print '</tr>';
}

print '</table>';
print '</div>';

print '<br>';

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="createmovements">';
print '<input type="hidden" name="draft" value="'.$draft.'">';

// Button to record mass movement
$codemove = (isset($_POST["codemove"]) ?GETPOST("codemove", 'alpha') : dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
$labelmovement = GETPOST("label") ?GETPOST('label') : $langs->trans("StockInternTransfer").' '.dol_print_date($now, '%Y-%m-%d %H:%M');

print '<table class="noborder centpercent">';
	print '<tr>';
	print '<td class="titlefield fieldrequired">'.$langs->trans("InventoryCode").'</td>';
	print '<td>';
	print '<input type="text" name="codemove" size="15" value="'.dol_escape_htmltag($codemove).'">';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>'.$langs->trans("MovementLabel").'</td>';
	print '<td>';
	print '<input type="text" name="label" class="quatrevingtpercent" value="'.dol_escape_htmltag($labelmovement).'" readonly>';
	print '</td>';
	print '</tr>';
print '</table><br>';

$disabled = (sizeof($listofdata) > 0) ? '' : 'disabled';
print '<table class="noborder centpercent">';
print '<tr>';
print '<td align="center"><input class="button" type="submit" name="valid" value="'.dol_escape_htmltag($buttonrecord).'" '.$disabled.' autofocus></td>';
print '<td align="center" ><input class="button" type="submit" name="borrar_registros" id="borrar_registros" value="Borrar Registros" style="background: #f19a9a;"></td>';
print '</tr>';
print '</table>';


//print '<div class="center"><input class="button" type="submit" name="valid" value="'.dol_escape_htmltag($buttonrecord).'" '.$disabled.'></div>';
//print '<div class="center"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
