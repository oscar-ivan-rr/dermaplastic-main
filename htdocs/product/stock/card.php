<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon Tosser			<simon@kornog-computing.com>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2016	    Francis Appels       	<francis.appels@yahoo.com>
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
 *	\file       htdocs/product/stock/card.php
 *	\ingroup    stock
 *	\brief      Page fiche entrepot
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies', 'categories'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "ASC";
$page = (GETPOST("page", 'int') ?GETPOST("page", 'int') : 0);
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "ASC";
$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
//Variable checkbox para filtro de existencias 0
$search_empty_stock = GETPOST('search_empty_stock');

$backtopage = GETPOST('backtopage', 'alpha');

// Security check
//$result=restrictedArea($user,'stock', $id, 'entrepot&stock');
$result = restrictedArea($user, 'stock');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('warehousecard', 'globalcard'));

$object = new Entrepot($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
    $ret = $object->fetch($id, $ref);
	//    if ($ret > 0)
	//        $ret = $object->fetch_thirdparty();
    if ($ret <= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    }
}


/*
 * Actions
 */

$error = 0;

$usercanread = (($user->rights->stock->lire));
$usercancreate = (($user->rights->stock->creer));
$usercandelete = (($user->rights->stock->supprimer));

$parameters = array('id'=>$id, 'ref'=>$ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	// Ajout entrepot
	if ($action == 'add' && $user->rights->stock->creer)
	{
		$object->ref         = GETPOST("ref");
		$object->fk_parent   = GETPOST("fk_parent");
		$object->libelle     = GETPOST("libelle");
		$object->description = GETPOST("desc");
		$object->statut      = GETPOST("statut");
		$object->lieu        = GETPOST("lieu");
		$object->address     = GETPOST("address");
		$object->zip         = GETPOST("zipcode");
		$object->town        = GETPOST("town");
		$object->country_id  = GETPOST("country_id");
		$object->fk_rfc  = GETPOST("fk_rfc");

		if (!empty($object->libelle))
		{
	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost(null, $object);
	        if ($ret < 0) {
	            $error++;
	            $action = 'create';
	        }

	        if (!$error) {
	            $id = $object->create($user);
	            if ($id > 0) {
	                setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

					$categories = GETPOST('categories', 'array');
					$object->setCategories($categories);
	                if (!empty($backtopage)) {
	                    header("Location: ".$backtopage);
	                    exit;
	                } else {
	                    header("Location: card.php?id=".$id);
	                    exit;
	                }
	            } else {
	                $action = 'create';
	                setEventMessages($object->error, $object->errors, 'errors');
	            }
	        }
		}
		else
		{
			setEventMessages($langs->trans("ErrorWarehouseRefRequired"), null, 'errors');
			$action = "create"; // Force retour sur page creation
		}
	}

	// Delete warehouse
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->stock->supprimer)
	{
		$object->fetch(GETPOST('id', 'int'));
		$result = $object->delete($user);
		if ($result > 0)
		{
		    setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header("Location: ".DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}

	if ($action == 'export_report') {

		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		setlocale(LC_ALL, 'es-MX.utf-8');
		header("Content-disposition: attachment; filename=\"Almacen de Sucursal.csv\"");
		$outputBuffer = fopen("php://output", 'w');
		
		$sql = "SELECT DISTINCT p.rowid as rowid, p.ubication, p.stock_min, p.reorden, p.stock_max, p.barcode, p.ref, p.produit, p.tobatch, p.type, p.ppmp, p.price, p.price_ttc, p.entity, p.value, p.weight, p.length, p.width, p.height, p.volume, p.identificacion FROM (";
		$sql .= "SELECT DISTINCT p.rowid as rowid, p.ubication, pw.desiredstock AS stock_min, pw.seuil_stock_alerte AS reorden, pw.stock_max, p.barcode, p.ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.pmp as ppmp, p.price, p.price_ttc, p.entity,";
		$sql .= " ps.reel as value, p.weight, p.length, p.width, p.height, p.volume, pe.noidenticfdi as identificacion";
		$sql .= " FROM llx_product as p LEFT JOIN llx_product_stock as ps ON  ps.fk_product = p.rowid ";
		$sql .= " LEFT JOIN llx_product_warehouse_properties as pw ON pw.fk_product = p.rowid AND ps.fk_entrepot=pw.fk_entrepot";
		$sql .= " LEFT JOIN llx_product_extrafields as pe ON  pe.fk_object = p.rowid ";
		$sql .= " WHERE ps.fk_entrepot = " . $object->id;

		if ($search_empty_stock == 1) {
			$sql .= " UNION ";
			$sql .= "SELECT DISTINCT p.rowid as rowid, p.ubication, pw.desiredstock AS stock_min, pw.seuil_stock_alerte AS reorden, pw.stock_max, p.barcode, p.ref, p.label as produit, p.tobatch, p.fk_product_type as type, ";
			$sql .= "p.pmp as ppmp, p.price, p.price_ttc, p.entity, NULL as value, p.weight, p.length, p.width, p.height, p.volume, NULL as identificacion FROM " . MAIN_DB_PREFIX . "product as p ";
			$sql .= " LEFT JOIN llx_product_warehouse_properties AS pw ON pw.fk_product = p.rowid AND pw.fk_entrepot='" . $object->id . "' ";
			$sql .= "WHERE NOT  EXISTS ";
			$sql .= "(SELECT 1 FROM " . MAIN_DB_PREFIX . "product_stock as ps WHERE ps.fk_product = p.rowid AND ps.fk_entrepot = '" . $object->id . "')";
		}
		$sql .= ") as p";
		if (!empty($searchCategoryProductList)) $sql .= ' JOIN ' . MAIN_DB_PREFIX . "categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
		$searchCategoryProductSqlList = array();
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
		$sql .= $db->order($sortfield, $sortorder);

		$result = $db->query($sql);
		if($db->num_rows($result)>0){
			$data = array();
			fputcsv($outputBuffer,array("Codigo de barras", "Producto","Categorias","Unidades", "Minimo", "Maximo", "Reorden", "P.U. UEPS","Importe UEPS","Precio de venta unitario","Valor de venta", utf8_decode("Ubicación"), "Peso", "Medidas (longitud, largo y alto)", "Volumen"),",");

			// Initialize total variables
			$total_units = 0;
			$total_pu_ueps = 0;
			$total_importe_ueps = 0;
			$total_precio_venta_unitario = 0;
			$total_valor_venta = 0;

			$langs->load("bills");
			$langs->load("dict");
			$form = new Form($db);
			while ($row= $db->fetch_object($result)){

				$x = array($row->barcode?$row->barcode:'');
				array_push($x, $row->ref?utf8_decode($row->ref):'nada');
				array_push($x,$form->showCategories($row->rowid, 'product', 1)?utf8_decode(str_replace("  ", ", ", str_replace("&gt;", ">", strip_tags($form->showCategories($row->rowid, 'product', 1))))):'');
				
				array_push($x,$row->value?$row->value:'');
				array_push($x,$row->stock_min?$row->stock_min:'0');
				array_push($x,$row->stock_max?$row->stock_max:'0');
				array_push($x,$row->reorden?$row->reorden:'0');

				$total_units += $row->value;

				// P.U UEPS
				if ($object->id != $conf->global->CEDIS_WAREHOUSE){
					$sql = 'SELECT cost_price_sucursal as cost_price';
				} else {
					$sql = 'SELECT cost_price';
				}
				$sql .= ' FROM '.MAIN_DB_PREFIX.'product';
				$sql .= " WHERE rowid = {$row->rowid};";
				$resqlueps_import = $db->query($sql);
				if($resqlueps_import){
					$objres = $db->fetch_object($resqlueps_import);
				}
				$pu_ueps = price($objres->cost_price)?price($objres->cost_price):'';
				array_push($x, $pu_ueps);
				$total_pu_ueps += $objres->cost_price;

				//UEPS Imort
				$ueps_value = price($objres->cost_price*$row->value);
				array_push($x,$ueps_value?$ueps_value:'');
				$total_importe_ueps += $objres->cost_price * $row->value;

				$pricemin = $row->price;
				$size = price2num($row->length, 'MT')." x ".price2num($row->width, 'MT')." x ".price2num($row->height, 'MT')." cm";
				$precio_venta_unitario = price(price2num($pricemin, 'MU'), 1)?price(price2num($pricemin, 'MU'), 1):'';
				array_push($x, $precio_venta_unitario);
				$total_precio_venta_unitario += price2num($pricemin, 'MU');

				$valor_venta = price(price2num($pricemin * $row->value, 'MT'), 1)?price(price2num($pricemin * $row->value, 'MT'), 1):'';
				array_push($x, $valor_venta);
				$total_valor_venta += price2num($pricemin * $row->value, 'MT');

				array_push($x,$row->ubication);
				array_push($x,price2num($row->weight, 'MT')." kg");
				array_push($x,$size);
				array_push($x,price2num($row->volume, 'MT')." cm3");

				fputcsv($outputBuffer,$x,",");
			}

			// Write totals row
			$totals = array(
				"Totales", "", "", 
				$total_units, 
				$total_pu_ueps, 
				$total_importe_ueps, 
				$total_precio_venta_unitario, 
				$total_valor_venta, 
				"", "", "", ""
			);
			fputcsv($outputBuffer, $totals, ",");

			fclose($outputBuffer);
			exit();
		} else {
			fclose($outputBuffer);
			exit();
		}
	}

	// Modification entrepot
	if ($action == 'update' && $cancel <> $langs->trans("Cancel"))
	{
		if ($object->fetch($id))
		{
			$object->libelle     = GETPOST("libelle");
			$object->fk_parent   = GETPOST("fk_parent");
			$object->description = GETPOST("desc");
			$object->statut      = GETPOST("statut");
			$object->lieu        = GETPOST("lieu");
			$object->address     = GETPOST("address");
			$object->zip         = GETPOST("zipcode");
			$object->town        = GETPOST("town");
			$object->country_id  = GETPOST("country_id");
			$object->fk_rfc  = GETPOST("fk_rfc");

	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost(null, $object);
	        if ($ret < 0)   $error++;

	        if (!$error) {
	            $ret = $object->update($id, $user);
	            if ($ret < 0)   $error++;
	        }

			if ($error) {
				$action = 'edit';
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);
	            $action = '';
	        }
		}
		else
		{
			$action = 'edit';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	elseif ($action == 'update_extras') {
	    $object->oldcopy = dol_clone($object);

	    // Fill array 'array_options' with data from update form
	    $ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'none'));
	    if ($ret < 0) $error++;
	    if (!$error) {
	        $result = $object->insertExtraFields();
	        if ($result < 0) {
	            setEventMessages($object->error, $object->errors, 'errors');
	            $error++;
	        }
	    }
	    if ($error) $action = 'edit_extras';
	}

	if ($cancel == $langs->trans("Cancel"))
	{
		$action = '';
	}


	// Actions to build doc
	$upload_dir = $conf->stock->dir_output;
	$permissiontoadd = $user->rights->stock->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$productstatic = new Product($db);
$form = new Form($db);
$formproduct = new FormProduct($db);
$formcompany = new FormCompany($db);
$formfile = new FormFile($db);

$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("", $langs->trans("WarehouseCard"), $help_url);


if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewWarehouse"));

	dol_set_focus('input[name="libelle"]');

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head();

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td><input name="libelle" size="20" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("LocationSummary").'</td><td><input name="lieu" size="40" value="'.(!empty($object->lieu) ? $object->lieu : '').'"></td></tr>';

	// Parent entrepot
	print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
	print $formproduct->selectWarehouses('', 'fk_parent', '', 1);
	print '</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	// Editeur wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('desc', (!empty($object->description) ? $object->description : ''), '', 180, 'dolibarr_notes', 'In', false, true, $conf->fckeditor->enabled, ROWS_5, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Address').'</td><td><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
	print (!empty($object->address) ? $object->address : '');
	print '</textarea></td></tr>';

	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').'</td><td>';
	print $formcompany->select_ziptown((!empty($object->zip) ? $object->zip : ''), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
	print '</td></tr>';
	print '<tr><td>'.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown((!empty($object->town) ? $object->town : ''), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
	print '</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans('Country').'</td><td>';
	print $form->select_country((!empty($object->country_id) ? $object->country_id : $mysoc->country_code), 'country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print '</td></tr>';
	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td>';
	print '<select name="statut" class="flat">';
	foreach ($object->statuts as $key => $value)
	{
		if ($key == 1)
		{
			print '<option value="'.$key.'" selected>'.$langs->trans($value).'</option>';
		}
		else
		{
			print '<option value="'.$key.'">'.$langs->trans($value).'</option>';
		}
	}
	print '</select>';
	print '</td></tr>';

	// RFC
	print '<tr><td>'.$langs->trans("RFC").'</td><td colspan="3">';
	print '<select class="flat" name="fk_rfc">';       
	$query = "SELECT rowid, code, label";
	$query .= " FROM ".MAIN_DB_PREFIX."c_rfc";
	$query .= " WHERE active = '1'";
	$requery = $db->query($query);
	
	$num = $db->num_rows($requery);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($requery);
			if ($selected && $selected == $obj->rowid)
			{
				print '<option value="'.$obj->rowid.'" selected>'.$obj->code.' - '.$obj->label.'</option>';
			}
			else
			{
				print '<option value="'.$obj->rowid.'">'.$obj->code.' - '.$obj->label.'</option>';
			}
			$i++;
		}
	}
	print '</select>';
	print '</td></tr>';

    // Other attributes
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	if ($conf->categorie->enabled) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_WAREHOUSE, '', 'parent', 64, 0, 1);
		print $form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, '', 0, '100%');
		print "</td></tr>";
	}
	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}
else
{
	$totalpriceiva = 0;
	$totalprice_no_iva = 0;
	$totalpricecost_iva = 0;
	$totalpricecost_no_iva = 0;

	if ($object->id != $conf->global->CEDIS_WAREHOUSE) {
		$sql2 = "SELECT DISTINCT p.rowid, (p.cost_price_sucursal * ps.reel) as total , (p.price * ps.reel) as total_sell, p.exentoiva FROM llx_product as p LEFT JOIN llx_product_stock as ps ON  ps.fk_product = p.rowid ";
	} else {
		$sql2 = "SELECT DISTINCT p.rowid, (p.cost_price * ps.reel) as total , (p.price * ps.reel) as total_sell, p.exentoiva FROM llx_product as p LEFT JOIN llx_product_stock as ps ON  ps.fk_product = p.rowid ";
	}

	$sql2 .= " WHERE ps.fk_entrepot = " . $object->id;
	$resql2 = $db->query($sql2);
	while ($cost_price = $db->fetch_object($resql2)) {
		if($cost_price->exentoiva == 0){
			$totalpriceiva += $cost_price->total_sell;
			$totalpricecost_iva += $cost_price->total;
		} else {
			$totalprice_no_iva += $cost_price->total_sell;
			$totalpricecost_no_iva += $cost_price->total;
		}
	}
	$iva_price_cost = $totalpricecost_iva * 0.16;
	$total_price_cost = $totalpricecost_iva + $totalpricecost_no_iva + $iva_price_cost;
	$iva_price = $totalpriceiva * 0.16;
	$total_price = $totalpriceiva + $totalprice_no_iva + $iva_price;
    $id = GETPOST("id", 'int');
	if ($id > 0 || $ref)
	{
		$object = new Entrepot($db);
		$result = $object->fetch($id, $ref);
		if ($result <= 0)
		{
			print 'No record found';
			exit;
		}

		/*
		 * Affichage fiche
		 */
		if ($action <> 'edit' && $action <> 're-edit')
		{
			$head = stock_prepare_head($object);

			dol_fiche_head($head, 'card', $langs->trans("Warehouse"), -1, 'stock');

			$formconfirm = '';

			// Confirm delete warehouse
			if ($action == 'delete')
			{
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteAWarehouse"), $langs->trans("ConfirmDeleteWarehouse", $object->label), "confirm_delete", '', 0, 2);
			}

			// Call Hook formConfirm
			$parameters = array('formConfirm' => $formconfirm);
			$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
			elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

			// Print form confirm
			print $formconfirm;

			// Warehouse card
			// Query para obtener el RFC y razón social relacionado al almacén
			$queryrfc = "SELECT lcr.code as rfc, lcr.label as rfcdes 
			FROM llx_c_rfc lcr WHERE lcr.rowid=".$object->fk_rfc."";
			$requeryrfc = $db->query($queryrfc);
			$objrfc = $db->fetch_object($requeryrfc);
			$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			$morehtmlref .= $langs->trans("LocationSummary").' : '.$object->lieu;
        	$morehtmlref .= '</div>';

            $shownav = 1;
            if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

        	dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);

        	print '<div class="fichecenter">';
        	print '<div class="fichehalfleft">';
        	print '<div class="underbanner clearboth"></div>';

        	print '<table class="border centpercent">';

			// Parent entrepot
			$parentwarehouse = new Entrepot($db);
			if (!empty($object->fk_parent) && $parentwarehouse->fetch($object->fk_parent) > 0) {
				print '<tr><td>'.$langs->trans("ParentWarehouse").'</td><td>';
				print $parentwarehouse->getNomUrl(3);
				print '</td></tr>';
			}

			// Description
			print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>'.nl2br($object->description).'</td></tr>';

			$calcproductsunique = $object->nb_different_products();
			$calcproducts = $object->nb_products();

			// Total nb of different products
			print '<tr><td>'.$langs->trans("NumberOfDifferentProducts").'</td><td>';
			print empty($calcproductsunique['nb']) ? '0' : $calcproductsunique['nb'];
			print "</td></tr>";

			// Nb of products
			print '<tr><td>'.$langs->trans("NumberOfProducts").'</td><td>';
            $valtoshow = price2num($calcproducts['nb'], 'MS');
            print empty($valtoshow) ? '0' : $valtoshow;
			print "</td></tr>";

			// RFC 
			print '<tr><td>'.$langs->trans("RFC").'</td><td>';
			print $objrfc->rfc;
			print "</td></tr>";
			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="ficheaddleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent">';

			// Value
			if ($user->rights->stock->show_pmp) {
				print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
				print price(($total_price_cost <= 0) ? '0' : price2num($total_price_cost, 'MT'), 0, $langs, 0, 0, -1, $conf->currency);
				print "</td></tr>";
			}

			// Last movement
			if (!empty($user->rights->stock->mouvement->lire)) {
				$sql = "SELECT max(m.datem) as datem";
				$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
				$sql .= " WHERE m.fk_entrepot = '".$object->id."'";
				$resqlbis = $db->query($sql);
				if ($resqlbis) {
					$obj = $db->fetch_object($resqlbis);
					$lastmovementdate = $db->jdate($obj->datem);
				} else {
					dol_print_error($db);
				}
				print '<tr><td>'.$langs->trans("LastMovement").'</td><td>';
				if ($lastmovementdate) {
					print dol_print_date($lastmovementdate, 'dayhour').' ';
					print '(<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?id='.$object->id.'">'.$langs->trans("FullList").'</a>)';
				} else {
					print $langs->trans("None");
				}
				print "</td></tr>";
			}

            // Other attributes
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
			// Categories
			if ($conf->categorie->enabled) {
				print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td colspan="3">';
				print $form->showCategories($object->id, 'warehouse', 1);
				print "</td></tr>";
			}

			// Razón social
			print '<tr><td>'.$langs->trans("Razón social").'</td><td>';
			print $objrfc->rfcdes;
			print "</td></tr>";
			print "</table>";

			print '</div>';
			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			dol_fiche_end();


			/* ************************************************************************** */
			/*                                                                            */
			/* Barre d'action                                                             */
			/*                                                                            */
			/* ************************************************************************** */

			print "<div class=\"tabsAction\">\n";

			$param = "";
			foreach ($searchCategoryProductList as $searchCategoryProduct) {
				$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
			}
			if ($search_empty_stock == 1) $param .= "&search_empty_stock=" . urlencode($search_empty_stock);
			if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
			$sort = "&sortfield=" . $sortfield;
			$order = "&sortorder=" . $sortorder;
			$action = "";

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook))
			{
				if (empty($action))
				{
					print "<a class=\"butAction\" href=\"export_ticket.php?id=".$object->id.$param.$sort.$order."\">".$langs->trans("Exportar Listado (Ticket)")."</a>";
					print "<a class=\"butAction\" href=\"card.php?action=export_report&id=".$object->id.$param."\">".$langs->trans("Exportar Listado")."</a>";

					if ($user->rights->stock->creer)
						print "<a class=\"butAction\" href=\"card.php?action=edit&id=".$object->id."\">".$langs->trans("Modify")."</a>";
					else
						print "<a class=\"butActionRefused classfortooltip\" href=\"#\">".$langs->trans("Modify")."</a>";

					if ($user->rights->stock->supprimer)
						print "<a class=\"butActionDelete\" href=\"card.php?action=delete&id=".$object->id."\">".$langs->trans("Delete")."</a>";
					else
						print "<a class=\"butActionRefused classfortooltip\" href=\"#\">".$langs->trans("Delete")."</a>";
				}
			}

			print "</div>";


			/* ************************************************************************** */
			/*                                                                            */
			/* Affichage de la liste des produits de l'entrepot                           */
			/*                                                                            */
			/* ************************************************************************** */
			print '<br>';


			$totalunit = 0;
			$totalvalue = $totalvaluesell = 0;
			$totalunitsell = 0;
			$totalueps = 0;
			$totalunitprice = 0;

			$sql = "SELECT DISTINCT p.rowid as rowid, p.ref, p.ubication,  p.stock_min, p.reorden, p.stock_max, p.produit, p.tobatch, p.type, p.pmp, p.price, p.price_ttc, p.entity, p.value, p.exentoiva FROM (";
			if ($object->id != $conf->global->CEDIS_WAREHOUSE){
				$sql .= "SELECT DISTINCT p.rowid as rowid, p.ref, p.ubication, pw.desiredstock AS stock_min, pw.seuil_stock_alerte AS reorden, pw.stock_max, p.label as produit, p.tobatch, p.fk_product_type as type, p.pmp, p.price, p.price_ttc, p.entity, p.cost_price_sucursal as cost_price, p.exentoiva, ";
			} else {
				$sql .= "SELECT DISTINCT p.rowid as rowid, p.ref, p.ubication, pw.desiredstock AS stock_min, pw.seuil_stock_alerte AS reorden, pw.stock_max, p.label as produit, p.tobatch, p.fk_product_type as type, p.pmp, p.price, p.price_ttc, p.entity, p.cost_price, p.exentoiva, ";
			}
			$sql .= " ps.reel as value";
			$sql .= " FROM llx_product as p LEFT JOIN llx_product_stock as ps ON  ps.fk_product = p.rowid ";
			$sql .= " LEFT JOIN llx_product_warehouse_properties as pw ON pw.fk_product = p.rowid AND ps.fk_entrepot=pw.fk_entrepot";
			// agregar join para stocks min y max
			$sql .= " WHERE ps.fk_entrepot = " . $object->id;

			if ($search_empty_stock == 1) {
				$sql .= " UNION ";
				$sql .= "SELECT DISTINCT p.rowid as rowid, p.ref, p.ubication, pw.desiredstock AS stock_min, pw.seuil_stock_alerte AS reorden, pw.stock_max, p.label as produit, p.tobatch, p.fk_product_type as type, ";
				if ($object->id != $conf->global->CEDIS_WAREHOUSE){
					$sql .= "p.pmp, p.price, p.price_ttc, p.entity, p.cost_price_sucursal as cost_price, p.exentoiva, NULL as value FROM " . MAIN_DB_PREFIX . "product as p ";
				} else {
					$sql .= "p.pmp, p.price, p.price_ttc, p.entity, p.cost_price, p.exentoiva, NULL as value FROM " . MAIN_DB_PREFIX . "product as p ";
				}
				$sql .= " LEFT JOIN llx_product_warehouse_properties AS pw ON pw.fk_product = p.rowid AND pw.fk_entrepot='" . $object->id . "' ";
				$sql .= "WHERE NOT  EXISTS ";
				$sql .= "(SELECT 1 FROM " . MAIN_DB_PREFIX . "product_stock as ps WHERE ps.fk_product = p.rowid AND ps.fk_entrepot = '" . $object->id . "')";
			}
			$sql .= ") as p";
			if (!empty($searchCategoryProductList)) $sql .= ' JOIN ' . MAIN_DB_PREFIX . "categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
			$searchCategoryProductSqlList = array();
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
			$sql .= $db->order($sortfield, $sortorder);
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
			$sql .= $db->plimit($limit + 1, $offset);
			dol_syslog('List products', LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post" name="formulaire">';
				print '<input type="hidden" name="token" value="' . newToken() . '">';
				print '<input type="hidden" name="id" value="'.$id.'">';
				print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
				print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
				print '<input type="hidden" name="page" value="' . $page . '">';
				print '<input type="hidden" name="type" value="'.$type.'">';
				$param .= '&id='.$id.'';
				print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'products', 0, '', '', $limit);

				print '<table class="noborder centpercent">';
				print "<tr class=\"liste_titre\">";
				print_liste_field_titre("Product", "", "p.ref", "&amp;id=" . $id, $param, "", $sortfield, $sortorder);
				print_liste_field_titre("Ubicación", "", "p.ubication", "&amp;id=" . $id, $param, "", $sortfield, $sortorder);
				// Categories Filter
				print '<td>';
				print '<div class="divsearchfield">';
				print $langs->trans('Categorias') . ': ';
				$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
				$categoriesProductArr[-2] = '- ' . $langs->trans('NotCategorized') . ' -';
				print Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
				print '</div>';
				print '</td>';

				// Label
				// print_liste_field_titre("Label", "", "p.label", "&amp;id=".$id, "", "", $sortfield, $sortorder);
				print_liste_field_titre("Units", "", "p.value", "&amp;id=" . $id, "", '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("Minimo", "", "p.stock_min", "&amp;id=" . $id, $param, "", $sortfield, $sortorder);
				print_liste_field_titre("Maximo", "", "p.stock_max", "&amp;id=" . $id, $param, "", $sortfield, $sortorder);
				print_liste_field_titre("Reorden", "", "p.reorden", "&amp;id=" . $id, $param, "", $sortfield, $sortorder);
				print_liste_field_titre("PUUEPS", "", "p.cost_price", "&amp;id=" . $id, "", '', $sortfield, $sortorder, 'right ');

				//            print_liste_field_titre("AverageUnitPricePMPShort", "", "p.pmp", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("ImportUEPS", "", "p.pmp", "&amp;id=" . $id, "", '', $sortfield, $sortorder, 'right ');
				if ($user->rights->stock->show_pmp)
					//print_liste_field_titre("EstimatedStockValueShort", "", "", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
					if (empty($conf->global->PRODUIT_MULTIPRICES)) {
						print_liste_field_titre("SellPriceMin", "", "p.price", "&amp;id=" . $id, "", '', $sortfield, $sortorder, 'right ');
					}
				if (empty($conf->global->PRODUIT_MULTIPRICES)) {
					print_liste_field_titre("EstimatedStockValueSellShort", "", "", "&amp;id=" . $id, "", '', $sortfield, $sortorder, 'right ');
				}
				print '<td class="center" colspan="2">Existencias 0 ';
				print '<input type="checkbox" class="valignmiddle" name="search_empty_stock" value="1" ' . ($search_empty_stock == 1 ? ' checked="checked"' : '') . '/>';
				print '</td>';
				print '<td>' . $form->showFilterButtons() . '</td>';
				print '</form>';
				print "</tr>\n";
				while ($i < min($num, $limit)) {
					$objp = $db->fetch_object($resql);

					// Multilangs
					if (!empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
					{
						$sql = "SELECT label";
						$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sql .= " WHERE fk_product=".$objp->rowid;
						$sql .= " AND lang='".$langs->getDefaultLang()."'";
						$sql .= " LIMIT 1";

						$result = $db->query($sql);
						if ($result)
						{
							$objtp = $db->fetch_object($result);
							if ($objtp->label != '') $objp->produit = $objtp->label;
						}
					}


					//print '<td>'.dol_print_date($objp->datem).'</td>';
					print '<tr class="oddeven">';
					print "<td>";
					$productstatic->id = $objp->rowid;
					$productstatic->ref = $objp->ref;
					$productstatic->ubication = $objp->ubication;
					$productstatic->label = $objp->produit;
					$productstatic->type = $objp->type;
					$productstatic->entity = $objp->entity;
					$productstatic->status_batch = $objp->tobatch;
					print $productstatic->getNomUrl(1, 'stock', 16);
					print '</td>';

					// Label
					print '<td>'.$objp->ubication.'</td>';

					// Categories
					print '<td class="tdoverflowmax200">';
					print $form->showCategories($objp->rowid, 'product', 1);
					print '</td>';


					print '<td class="right">';
					$valtoshow = price(price2num($objp->value, 'MS'), 0, '', 0, 0); // TODO replace with a qty() function
					print empty($valtoshow) ? '0' : $valtoshow;
					print '</td>';

					$min = $objp->stock_min ? $objp->stock_min : 0;
					$max = $objp->stock_max ? $objp->stock_max : 0;
					$reorden = $objp->reorden ? $objp->reorden : 0;

					print '<td class="center">'.$min.'</td>';
					print '<td class="center">'.$max.'</td>';
					print '<td class="center">'.$reorden.'</td>';

					$totalunit += $objp->value;

					// P.U UEPS
					if ($object->id != $conf->global->CEDIS_WAREHOUSE){
						$sql = 'SELECT cost_price_sucursal as cost_price';
					} else {
						$sql = 'SELECT cost_price ';
					}
                    $sql .= ' FROM '.MAIN_DB_PREFIX.'product ';
                    $sql .= ' WHERE rowid = '.$productstatic->id.' ;';
                    $resqlueps_import = $db->query($sql);
                    if($resqlueps_import){
                        $objres = $db->fetch_object($resqlueps_import);
                    }
					print '<td class="right">'.price($objres->cost_price).'</td>';
					$totalunitprice += price2num($objres->cost_price, 'MT');

                    // Price buy PMP
//					print '<td class="right">'.price(price2num($objp->ppmp, 'MU')).'</td>';
                    // Implementation of UEPS
                    // $sqlueps = 'SELECT price ';
                    // $sqlueps .= 'FROM '.MAIN_DB_PREFIX.'stock_mouvement ';
                    // $sqlueps .= 'WHERE fk_product = '.$productstatic->id.' ';
                    // $sqlueps .= 'AND value > 0 ORDER BY datem DESC LIMIT 1';
                    // $resqlueps = $db->query($sqlueps);
                    // if($resqlueps){
                    //     $objres = $db->fetch_object($resqlueps);
                    // }
					
					//UEPS Imort
                    $ueps_value = price($objres->cost_price*$objp->value);
                    print '<td class="right">'.$ueps_value.'</td>';
					$totalueps += price2num($ueps_value, 'MT');
                    // END UEPS

					// Total PMP
					// if ($user->rights->stock->show_pmp) {
					// 	print '<td class="right">'.price(price2num($objp->ppmp * $objp->value, 'MT')).'</td>';
					// 	$totalvalue += price2num($objp->ppmp * $objp->value, 'MT');
					// }

                    // Price sell min
                    if (empty($conf->global->PRODUIT_MULTIPRICES))
                    {
                        $pricemin = $objp->price;
                        print '<td class="right">';
                        print price(price2num($pricemin, 'MU'), 1);
                        print '</td>';
                        // Total sell min
                        print '<td class="right">';
                        print price(price2num($pricemin * $objp->value, 'MT'), 1);
                        print '</td>';
                    }
					$totalunitsell += price2num($pricemin, 'MT');
                    $totalvaluesell += price2num($pricemin * $objp->value, 'MT');


                    if ($user->rights->stock->mouvement->creer)
					{
						print '<td class="center"><a href="'.DOL_URL_ROOT.'/product/stock/product.php?dwid='.$object->id.'&id='.$objp->rowid.'&action=transfert&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$id).'">';
						print img_picto($langs->trans("StockMovement"), 'uparrow.png', 'class="hideonsmartphone"').' '.$langs->trans("StockMovement");
						print "</a></td>";
					}

					if ($user->rights->stock->creer)
					{
						print '<td class="center"><a href="'.DOL_URL_ROOT.'/product/stock/product.php?dwid='.$object->id.'&id='.$objp->rowid.'&action=correction&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$id).'">';
						print $langs->trans("StockCorrection");
						print "</a></td>";
					}
					print '<td class="liste_total">&nbsp;</td>';
					print "</tr>";
					$i++;
				}
				$db->free($resql);

				print '<tr class="liste_total"><td class="liste_total" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td class="liste_total right">';
				$valtoshow = price(price2num($totalunit, 'MS'));
				print empty($valtoshow) ? '0' : $valtoshow;
				print '</td>';
				print '<td class="liste_total right">'.price($totalunitprice).'</td>';// Total UEPS
				if ($user->rights->stock->show_pmp)
                	print '<td class="liste_total right">'.price(price2num($totalueps, 'MT')).'</td>';
                if (empty($conf->global->PRODUIT_MULTIPRICES))
                {
                    print '<td class="liste_total right">'.price($totalunitsell).'</td>';
                    print '<td class="liste_total right">'.price(price2num($totalvaluesell, 'MT')).'</td>';
                }
                print '<td class="liste_total">&nbsp;</td>';
				print '<td class="liste_total">&nbsp;</td>';
				print '<td class="liste_total">&nbsp;</td>';
				print '</tr>';

				print '<table class="right" style="margin-top: 20px; align-items: right;width:75%;border: 0px;border-bottom: none; border-collapse: collapse;border-spacing: 0;border-top: none;">';
				print '<tr>';
				print '<td style="color:#1c2645;">';
				print '<b>PRECIOS DE COMPRA </b>';
				print '</td>';
				print '<td></td>';
				print '<td style="color:#1c2645;">';
				print '<b>PRECIOS DE VENTA </b>';
				print '</td>';
				print '<td></td>';
				print '</tr>';
				print '<tr>';
				print '<td>';
				print '<b>Total de productos con IVA: </b>';
				print '</td>';
				print '<td >';
				print '<b>'.price(price2num($totalpricecost_iva, 'MT')) .'</b>';
				print '</td>';
				print '<td >';
				print '<b>Total de productos con IVA: </b>';
				print '</td>';
				print '<td >';
				print '<b>'.price(price2num($totalpriceiva, 'MT')) .'</b>';
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td>';
				print '<b>Total de productos sin IVA: </b>';
				print '</td>';
				print '<td>';
				print '<b>'.price(price2num($totalpricecost_no_iva, 'MT')) .'</b>';
				print '</td>';
				print '<td>';
				print '<b>Total de productos sin IVA: </b>';
				print '</td>';
				print '<td>';
				print '<b>'.price(price2num($totalprice_no_iva, 'MT')) .'</b>';
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td>';
				print '<b>Total de IVA: </b>';
				print '</td>';
				print '<td>';
				print '<b>'.price(price2num($iva_price_cost, 'MT')) .'</b>';
				print '</td>';
				print '<td>';
				print '<b>Total de IVA: </b>';
				print '</td>';
				print '<td>';
				print '<b>'.price(price2num($iva_price, 'MT')) .'</b>';
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td>';
				print '<b>Total: </b>';
				print '</td>';
				print '<td>';
				print '<b>'.price(price2num($total_price_cost, 'MT')) .'</b>';
				print '</td>';
				print '<td>';
				print '<b>Total: </b>';
				print '</td>';
				print '<td>';
				print '<b>'.price(price2num($total_price, 'MT')) .'</b>';
				print '</td>';
				print '</tr>';
				print '</table>';
			}
			else
			{
				dol_print_error($db);
			}
			print "</table>\n";
		}


		/*
		 * Edition fiche
		 */
		if ($action == 'edit' || $action == 're-edit')
		{
			$langs->trans("WarehouseEdit");

			print '<form action="card.php" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			$head = stock_prepare_head($object);

			dol_fiche_head($head, 'card', $langs->trans("Warehouse"), 0, 'stock');

			print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td><input name="libelle" size="20" value="'.$object->label.'"></td></tr>';

			print '<tr><td>'.$langs->trans("LocationSummary").'</td><td><input name="lieu" size="40" value="'.$object->lieu.'"></td></tr>';

			// Parent entrepot
			print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
			print $formproduct->selectWarehouses($object->fk_parent, 'fk_parent', '', 1);
			print '</td></tr>';

			// Description
			print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
			// Editeur wysiwyg
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor = new DolEditor('desc', $object->description, '', 180, 'dolibarr_notes', 'In', false, true, $conf->fckeditor->enabled, ROWS_5, '90%');
			$doleditor->Create();
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('Address').'</td><td><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
			print $object->address;
			print '</textarea></td></tr>';

			// Zip / Town
			print '<tr><td>'.$langs->trans('Zip').'</td><td>';
			print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
			print '</td></tr>';
			print '<tr><td>'.$langs->trans('Town').'</td><td>';
			print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print '</td></tr>';

			// Country
			print '<tr><td>'.$langs->trans('Country').'</td><td>';
			print $form->select_country($object->country_id ? $object->country_id : $mysoc->country_code, 'country_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			print '</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td><td>';
			print '<select name="statut" class="flat">';
			foreach ($object->statuts as $key => $value)
			{
				if ($key == $object->statut)
				{
					print '<option value="'.$key.'" selected>'.$langs->trans($value).'</option>';
				}
				else
				{
					print '<option value="'.$key.'">'.$langs->trans($value).'</option>';
				}
			}
			print '</select>';
			print '</td></tr>';
			
			// RFC
			$query = "SELECT rowid, code, label";
			$query .= " FROM ".MAIN_DB_PREFIX."c_rfc";
			$query .= " WHERE active = '1'";
			$requery = $db->query($query);
			print '<tr><td>'.$langs->trans("RFC").'</td><td colspan="3">';
			if ($user->rights->produit->modify_rfc){
				print '<select class="flat" name="fk_rfc">';           
				$num = $db->num_rows($requery);
				$i = 0;
				if ($num)
				{
					while ($i < $num)
					{
						$obj = $db->fetch_object($requery);
						if ($obj->rowid != $object->fk_rfc)
						{
							print '<option value="'.$obj->rowid.'">'.$obj->code.' - '.$obj->label.'</option>';
						}else{
							print '<option value="'.$obj->rowid.'" selected>'.$obj->code.' - '.$obj->label.'</option>'; 
						}
						$i++;
					}
				}
				print '</select>';
				print '</td></tr>';
			} else {
				print '<input type="hidden" name="fk_rfc" value="'.$object->fk_rfc.'">';
				while ($obj = $db->fetch_object($requery))
					{
						if ($obj->rowid == $object->fk_rfc)
							print '<label>'.$obj->code.' - '.$obj->label.'</label>';
						$i++;
					}
				print '</td></tr>';
			}
			
            // Other attributes
            $parameters = array('colspan' => ' colspan="3"', 'cols' => '3');
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook))
            {
                print $object->showOptionals($extrafields, 'edit', $parameters);
            }
			// Tags-Categories
			if ($conf->categorie->enabled)
			{
				print '<tr><td class="tdtop">'.$langs->trans("Categories").'</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_WAREHOUSE, '', 'parent', 64, 0, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id, Categorie::TYPE_WAREHOUSE);
				$arrayselected = array();
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
				print "</td></tr>";
			}
			print '</table>';

			dol_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		}
	}
}

/*
 * Documents generes
 */

if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
	$modulepart = 'stock';

	if ($action != 'create' && $action != 'edit' && $action != 'delete')
	{
		print '<br/>';
	    print '<div class="fichecenter"><div class="fichehalfleft">';
	    print '<a name="builddoc"></a>'; // ancre

	    // Documents
	    $objectref = dol_sanitizeFileName($object->ref);
	    $relativepath = $comref.'/'.$objectref.'.pdf';
	    $filedir = $conf->stock->dir_output.'/'.$objectref;
	    $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	    $genallowed = $usercanread;
	    $delallowed = $usercancreate;
	    $modulepart = 'stock';

	    print $formfile->showdocuments($modulepart, $object->ref, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $object->default_lang, '', $object);
	    $somethingshown = $formfile->numoffiles;

	    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	    $MAXEVENT = 10;

	    $morehtmlright = '<a href="'.DOL_URL_ROOT.'/product/agenda.php?id='.$object->id.'">';
	    $morehtmlright .= $langs->trans("SeeAll");
	    $morehtmlright .= '</a>';

	    // List of actions on element
	    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	    $formactions = new FormActions($db);
	    $somethingshown = $formactions->showactions($object, 'stock', 0, 1, '', $MAXEVENT, '', $morehtmlright); // Show all action for product

	    print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
