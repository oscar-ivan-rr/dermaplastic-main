<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador.gpcsolutions.fr>
 * Copyright (C) 2013-2018 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014-2015 Cédric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/product/stock/product.php
 *	\ingroup    product stock
 *	\brief      Page to list detailed stock of a product
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productstockentrepot.class.php';
if (!empty($conf->productbatch->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
}
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

if (!empty($conf->variants->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
}

// Load translation files required by the page
$langs->loadlangs(array('products', 'orders', 'bills', 'stocks', 'sendings'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$stocklimit = GETPOST('seuil_stock_alerte');
$desiredstock = GETPOST('desiredstock');
$revicedstock = GETPOST('revicedstock');
$desiredstockma = GETPOST('desiredstockma');
$desiredstockgpe = GETPOST('desiredstockgpe');
$cancel = GETPOST('cancel', 'alpha');
$fieldid = isset($_GET["ref"]) ? 'ref' : 'rowid';
$d_eatby = dol_mktime(0, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
$d_sellby = dol_mktime(0, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
$pdluoid = GETPOST('pdluoid', 'int');
$batchnumber = GETPOST('batch_number', 'san_alpha');
if (!empty($batchnumber)) {
	$batchnumber = trim($batchnumber);
}

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'produit&stock', $id, 'product&product', '', '', $fieldid);


$object = new Product($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || !empty($ref))
{
    $result = $object->fetch($id, $ref);
}

if (empty($id) && !empty($object->id)) $id = $object->id;

$modulepart = 'product';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('stockproduct', 'card', $canvas);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('stockproductcard', 'globalcard'));


/*
 *	Actions
 */

if ($cancel) $action = '';

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($action == 'addlimitstockwarehouse' && !empty($user->rights->produit->creer))
{
	$seuil_stock_alerte = GETPOST('seuil_stock_alerte');
	$desiredstock = GETPOST('desiredstock');

	$maj_ok = true;
	if ($seuil_stock_alerte == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("StockLimit")), null, 'errors');
		$maj_ok = false;
	}
	if ($desiredstock == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DesiredStock")), null, 'errors');
		$maj_ok = false;
	}

	if ($maj_ok) {
		$pse = new ProductStockEntrepot($db);
		if ($pse->fetch(0, $id, GETPOST('fk_entrepot', 'int')) > 0) {
			// Update
			$pse->seuil_stock_alerte = $seuil_stock_alerte;
			$pse->desiredstock = $desiredstock;
			if ($pse->update($user) > 0) setEventMessages($langs->trans('ProductStockWarehouseUpdated'), null, 'mesgs');
		} else {
			// Create
			$pse->fk_entrepot = GETPOST('fk_entrepot');
			$pse->fk_product  	 	 = $id;
			$pse->seuil_stock_alerte = GETPOST('seuil_stock_alerte');
			$pse->desiredstock  	 = GETPOST('desiredstock');
			if ($pse->create($user) > 0) setEventMessages($langs->trans('ProductStockWarehouseCreated'), null, 'mesgs');
		}
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
	exit;
}

if ($action == 'delete_productstockwarehouse' && !empty($user->rights->produit->creer))
{
	$pse = new ProductStockEntrepot($db);

	$pse->fetch(GETPOST('fk_productstockwarehouse', 'int'));
	if ($pse->delete($user) > 0) setEventMessages($langs->trans('ProductStockWarehouseDeleted'), null, 'mesgs');

	$action = '';
}

// Set stock limit
if ($action == 'setseuil_stock_alerte' && !empty($user->rights->stock->edit_reorder))
{
    $object = new Product($db);
    $result = $object->fetch($id);
	$object->seuil_stock_alerte = $stocklimit;
    $result = $object->update($object->id, $user, 0, 'update');
    if ($result < 0)
    	setEventMessages($object->error, $object->errors, 'errors');
    //else
    //	setEventMessages($lans->trans("SavedRecordSuccessfully"), null, 'mesgs');
    $action = '';
}
if ($action == 'setrevicedstock' && !empty($user->rights->produit->creer))
{
    $object = new Product($db);
    $result = $object->fetch($id);
    $object->revicedstock = $revicedstock;
    $result = $object->update($object->id, $user, 0, 'update');
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
    //else
    //	setEventMessages($lans->trans("SavedRecordSuccessfully"), null, 'mesgs');
    $action = '';
}
if ($action == 'setdesiredstockgpe' && !empty($user->rights->produit->creer))
{
    $object = new Product($db);
    $result = $object->fetch($id);
    $object->desiredstock_gpe = $desiredstockgpe;
	$object->desiredstock = $desiredstockgpe + $object->desiredstock_principal;
    $result = $object->update($object->id, $user, 0, 'update');
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
    //else
    //	setEventMessages($lans->trans("SavedRecordSuccessfully"), null, 'mesgs');
    $action = '';
}
if ($action == 'setdesiredstockma' && !empty($user->rights->produit->creer))
{
    $object = new Product($db);
    $result = $object->fetch($id);
	$object->desiredstock_principal = $desiredstockma;
	$object->desiredstock = $desiredstockma + $object->desiredstock_gpe;
    $result = $object->update($object->id, $user, 0, 'update');
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
    //else
    //	setEventMessages($lans->trans("SavedRecordSuccessfully"), null, 'mesgs');
    $action = '';
}
// Set desired stock
if ($action == 'setdesiredstock' && !empty($user->rights->stock->edit_minmax))
{
    $object = new Product($db);
    $result = $object->fetch($id);
    $object->desiredstock = $desiredstock;
    $result = $object->update($object->id, $user, 0, 'update');
    if ($result < 0)
    	setEventMessages($object->error, $object->errors, 'errors');
    $action = '';
}

// Set warehouse percentages
if ($action == 'setwh_percent' && !empty($user->rights->produit->creer)) {
	$object = new Product($db);
	$result = $object->fetch($id);
    $result = $object->checkPercentages(GETPOST('wh1percent', 'int', 2), GETPOST('wh2percent', 'int', 2));
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
    $action = '';
}

// Set warehouse limits
if ($action == 'setwh_limit' && !empty($user->rights->produit->creer)) {
	$object = new Product($db);
	$result = $object->fetch($id);
    $result = $object->checkLimits(GETPOST('wh1_limit', 'int', 2), GETPOST('wh2_limit', 'int', 2));
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
    $action = '';
}


// Correct stock
if ($action == "correct_stock" && !$cancel)
{
	if (!(GETPOST("id_entrepot") > 0))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'correction';
	}
	if (!GETPOST("nbpiece"))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'correction';
	}

	if (!empty($conf->productbatch->enabled))
	{
		$object = new Product($db);
		$result = $object->fetch($id);

		if ($object->hasbatch() && !$batchnumber)
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
			$error++;
			$action = 'correction';
		}
	}

	if (!$error)
	{
		$priceunit = price2num(GETPOST("unitprice"));
		if (is_numeric(GETPOST("nbpiece")) && $id)
		{
			$origin_element = '';
			$origin_id = null;

			if (GETPOST('projectid', 'int'))
			{
				$origin_element = 'project';
				$origin_id = GETPOST('projectid', 'int');
			}

			if (empty($object)) {
				$object = new Product($db);
				$result = $object->fetch($id);
			}
			if ($object->hasbatch())
			{
				$result = $object->correct_stock_batch(
					$user,
					GETPOST("id_entrepot"),
					GETPOST("nbpiece"),
					GETPOST("mouvement"),
					GETPOST("label"), // label movement
					$priceunit,
					$d_eatby,
					$d_sellby,
					$batchnumber,
					GETPOST('inventorycode'),
					$origin_element,
					$origin_id
				); // We do not change value of stock for a correction
			}
			else
			{
				$result = $object->correct_stock(
		    		$user,
		    		GETPOST("id_entrepot"),
		    		GETPOST("nbpiece"),
		    		GETPOST("mouvement"),
		    		GETPOST("label"),
		    		$priceunit,
					GETPOST('inventorycode'),
					$origin_element,
					$origin_id
				); // We do not change value of stock for a correction
			}

			if ($result > 0)
			{
				if ($backtopage)
				{
					header("Location: ".$backtopage);
					exit;
				}
				else
				{
	            	header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
					exit;
				}
			}
			else
			{
			    setEventMessages($object->error, $object->errors, 'errors');
			    $action = 'correction';
			}
		}
	}
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && !$cancel)
{
	if (!(GETPOST("id_entrepot", 'int') > 0) || !(GETPOST("id_entrepot_destination", 'int') > 0))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (!GETPOST("nbpiece", 'int'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (GETPOST("id_entrepot", 'int') == GETPOST("id_entrepot_destination", 'int'))
	{
		setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (!empty($conf->productbatch->enabled))
	{
	    $object = new Product($db);
	    $result = $object->fetch($id);

	    if ($object->hasbatch() && !$batchnumber)
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
	        $error++;
	        $action = 'transfert';
	    }
	}

	if (!$error)
	{
		if ($id)
		{
			$object = new Product($db);
			$result = $object->fetch($id);

			$db->begin();

			$object->load_stock('novirtual'); // Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc = 0;
			if (isset($object->pmp)) $pricesrc = $object->pmp;
			$pricedest = $pricesrc;

			if ($object->hasbatch())
			{
				$pdluo = new Productbatch($db);

				if ($pdluoid > 0)
				{
					$result = $pdluo->fetch($pdluoid);
					if ($result)
					{
						$srcwarehouseid = $pdluo->warehouseid;
						$batch = $pdluo->batch;
						$eatby = $pdluo->eatby;
						$sellby = $pdluo->sellby;
					}
					else
					{
						setEventMessages($pdluo->error, $pdluo->errors, 'errors');
						$error++;
					}
				}
				else
				{
					$srcwarehouseid = GETPOST('id_entrepot', 'int');
					$batch = $batchnumber;
					$eatby = $d_eatby;
					$sellby = $d_sellby;
				}

				if (!$error)
				{
					// Remove stock
					$result1 = $object->correct_stock_batch(
						$user,
						$srcwarehouseid,
						GETPOST("nbpiece", 'int'),
						1,
						GETPOST("label", 'san_alpha'),
						$pricesrc,
						$eatby, $sellby, $batch,
						GETPOST('inventorycode')
					);
					if ($result1 < 0) $error++;
				}
				if (!$error)
				{
					// Add stock
					$result2 = $object->correct_stock_batch(
						$user,
						GETPOST("id_entrepot_destination", 'int'),
						GETPOST("nbpiece", 'int'),
						0,
						GETPOST("label", 'san_alpha'),
						$pricedest,
						$eatby, $sellby, $batch,
						GETPOST('inventorycode')
					);
					if ($result2 < 0) $error++;
				}
			}
			else
			{
				if (!$error)
				{
    			    // Remove stock
    				$result1 = $object->correct_stock(
    					$user,
    					GETPOST("id_entrepot"),
    					GETPOST("nbpiece"),
    					1,
    					GETPOST("label"),
    					$pricesrc,
    					GETPOST('inventorycode')
    				);
    				if ($result1 < 0) $error++;
				}
				if (!$error)
				{
    				// Add stock
    				$result2 = $object->correct_stock(
    					$user,
    					GETPOST("id_entrepot_destination"),
    					GETPOST("nbpiece"),
    					0,
    					GETPOST("label"),
    					$pricedest,
    					GETPOST('inventorycode')
    				);
    				if ($result2 < 0) $error++;
				}
			}


			if (!$error && $result1 >= 0 && $result2 >= 0)
			{
				$db->commit();

				if ($backtopage)
				{
					header("Location: ".$backtopage);
					exit;
				}
				else
				{
					header("Location: product.php?id=".$object->id);
					exit;
				}
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
				$action = 'transfert';
			}
		}
	}
}

// Update batch information
if ($action == 'updateline' && GETPOST('save') == $langs->trans('Save'))
{
    $pdluo = new Productbatch($db);
    $result = $pdluo->fetch(GETPOST('pdluoid', 'int'));

    if ($result > 0)
    {
        if ($pdluo->id)
        {
            if ((!GETPOST("sellby")) && (!GETPOST("eatby")) && (!$batchnumber)) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("atleast1batchfield")), null, 'errors');
            }
            else
            {
                $d_eatby = dol_mktime(0, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
                $d_sellby = dol_mktime(0, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
                $pdluo->batch = $batchnumber;
                $pdluo->eatby = $d_eatby;
                $pdluo->sellby = $d_sellby;
                $result = $pdluo->update($user);
                if ($result < 0)
                {
                    setEventMessages($pdluo->error, $pdluo->errors, 'errors');
                }
            }
        }
        else
        {
            setEventMessages($langs->trans('BatchInformationNotfound'), null, 'errors');
        }
    }
    else
    {
        setEventMessages($pdluo->error, null, 'errors');
    }
    header("Location: product.php?id=".$id);
    exit;
}



/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
if (!empty($conf->projet->enabled)) $formproject = new FormProjets($db);

if ($id > 0 || $ref)
{
	$object = new Product($db);
	$result = $object->fetch($id, $ref);

	$variants = $object->hasVariants();

	$object->load_stock();

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label, 16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
	{
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Stock');
		$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
	{
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Stock');
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl);

	if ($result > 0)
	{
		$head = product_prepare_head($object);
		$titre = $langs->trans("CardProduct".$object->type);
		$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

		dol_fiche_head($head, 'stock', $titre, -1, $picto);

		dol_htmloutput_events();

        $linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        $shownav = 1;
        if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

        dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

        print '<div class="fichecenter">';

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';

		if (!$variants) {
			if ($conf->productbatch->enabled) {
				print '<tr><td class="titlefield">'.$langs->trans("ManageLotSerial").'</td><td>';
				print $object->getLibStatut(0, 2);
				print '</td></tr>';
			}

			// PMP
			// print '<tr><td class="titlefield">'.$langs->trans("AverageUnitPricePMP").'</td>';

            // Replacing PMP for UEPS
            print '<tr><td class="titlefield">'.$langs->trans("LastEntryFirstDeparturesUEPSShort").'</td>';
			print '<td>';
			// if ($object->pmp > 0) print price($object->pmp).' '.$langs->trans("HT");
            $sqlueps = 'SELECT price ';
            $sqlueps .= 'FROM '.MAIN_DB_PREFIX.'stock_mouvement ';
            $sqlueps .= 'WHERE fk_product = '.$object->id.' ';
            $sqlueps .= 'AND value > 0 ORDER BY datem DESC LIMIT 1';
            $resqlueps = $db->query($sqlueps);
            if($resqlueps){
                $objres = $db->fetch_object($resqlueps);
            }
            $ueps_value = price($objres->price*$object->stock_reel);
            print $ueps_value;
			print '</td>';
			print '</tr>';

			// Minimum Price
			print '<tr><td>'.$langs->trans("BuyingPriceMin").'</td>';
			print '<td>';
			$product_fourn = new ProductFournisseur($db);
			if ($product_fourn->find_min_price_product_fournisseur($object->id) > 0) {
				if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur();
				else print $langs->trans("NotDefined");
			}
			print '</td></tr>';

			if (empty($conf->global->PRODUIT_MULTIPRICES)) {
				// Price
				print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
				if ($object->price_base_type == 'TTC') {
					print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
				} else {
					print price($object->price).' '.$langs->trans($object->price_base_type);
				}
				print '</td></tr>';

				// Price minimum
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($object->price_base_type == 'TTC') {
					print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
				} else {
					print price($object->price_min).' '.$langs->trans($object->price_base_type);
				}
				print '</td></tr>';
			} else {
				// Price
				print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
				print $langs->trans("Variable");
				print '</td></tr>';

				// Price minimum
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				print $langs->trans("Variable");
				print '</td></tr>';
			}

			// Desired stock

			print '<tr><td>'.$langs->trans("DesiredStockGnral").'</td>';
			// print '<tr><td>';
			// $form->editfieldkey($form->textwithpicto($langs->trans("DesiredStockGnral"), $langs->trans("DesiredStockDesc"), 1), 'desiredstock', $object->desiredstock, $object, $user->rights->produit->creer);
			print '<td>';
			print $form->editfieldval("DesiredStock", 'desiredstock', $object->desiredstock, $object, !empty($user->rights->stock->edit_minmax), 'string');
			print '</td></tr>';

			// Stock alert threshold
			print '<tr><td>'.$langs->trans("StockLimitGnral").'</td>';
			print '<td>';
			// print '<tr><td>'.$form->editfieldkey($form->textwithpicto($langs->trans("StockLimitGnral"), $langs->trans("StockLimitDesc"), 1), 'seuil_stock_alerte', $object->seuil_stock_alerte, $object, $user->rights->produit->creer).'</td><td>';
			print $form->editfieldval("StockLimit", 'seuil_stock_alerte', $object->seuil_stock_alerte, $object, !empty($user->rights->stock->edit_reorder), 'string');
			print '</td></tr>';

			// Hook formObject
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			

			// Real stock
			$text_stock_options = $langs->trans("RealStockDesc").'<br>';
			$text_stock_options .= $langs->trans("RealStockWillAutomaticallyWhen").'<br>';
			$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE) ? $langs->trans("DeStockOnShipment").'<br>' : '');
			$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) ? $langs->trans("DeStockOnValidateOrder").'<br>' : '');
			$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_BILL) ? $langs->trans("DeStockOnBill").'<br>' : '');
			$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) ? $langs->trans("ReStockOnBill").'<br>' : '');
			$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) ? $langs->trans("ReStockOnValidateOrder").'<br>' : '');
			$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) ? $langs->trans("ReStockOnDispatchOrder").'<br>' : '');
       		$text_stock_options .= (!empty($conf->global->STOCK_CALCULATE_ON_RECEPTION) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE) ? $langs->trans("StockOnReception").'<br>' : '');

			print '<tr><td>';
			print $form->textwithpicto($langs->trans("PhysicalStock"), $text_stock_options, 1);
			print '</td>';
			$stock_to_show = $object->stock_reel <0? 0:$object->stock_reel;
			print '<td>'.price2num($stock_to_show, 'MS');
			if ($object->seuil_stock_alerte != '' && ($object->stock_reel < $object->seuil_stock_alerte)) 
				print ' '.img_warning($langs->trans("StockLowerThanLimit", $object->seuil_stock_alerte));
			print '</td>';
			print '</tr>';

			$stocktheo = price2num($object->stock_theorique, 'MS');

			$found = 0;
			$helpondiff = '<strong>'.$langs->trans("StockDiffPhysicTeoric").':</strong><br>';
			// Number of customer orders running
			if (!empty($conf->commande->enabled)) {
				if ($found) $helpondiff .= '<br>'; else $found = 1;
				$helpondiff .= $langs->trans("ProductQtyInCustomersOrdersRunning").': '.$object->stats_commande['qty'];
				$commande_open = $object->stats_commande['qty'];
				$result = $object->load_stats_commande(0, '0', 1);
				if ($result < 0) dol_print_error($db, $object->error);
				$helpondiff .= ' ('.$langs->trans("ProductQtyInDraft").': '.$object->stats_commande['qty'].')';
			}

			// Number of product from customer order already sent (partial shipping)
			if (!empty($conf->expedition->enabled)) {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $filterShipmentStatus = '';
                if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
                    $filterShipmentStatus = Expedition::STATUS_VALIDATED  . ',' . Expedition::STATUS_CLOSED;
                } elseif (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
                    $filterShipmentStatus = Expedition::STATUS_CLOSED;
                }
				if ($found) $helpondiff .= '<br>'; else $found = 1;
				$result = $object->load_stats_sending(0, '2', 1, $filterShipmentStatus);
				$helpondiff .= $langs->trans("ProductQtyInShipmentAlreadySent").': '.$object->stats_expedition['qty'];
			}

			// Number of supplier order running
			if (!empty($conf->fournisseur->enabled)) {
				if ($found) $helpondiff .= '<br>'; else $found = 1;
				$result = $object->load_stats_commande_fournisseur(0, '3,4', 1);
				$helpondiff .= $langs->trans("ProductQtyInSuppliersOrdersRunning").': '.$object->stats_commande_fournisseur['qty']; 
				$result = $object->load_stats_commande_fournisseur(0, '0,1,2', 1);
				if ($result < 0) dol_print_error($db, $object->error);
				$helpondiff .= ' ('.$langs->trans("ProductQtyInDraftOrWaitingApproved").': '.$object->stats_commande_fournisseur['qty'].')';
			}

				//STOCK VIRTUAL
				$sql = "SELECT rowid, ref, lieu, fk_parent, statut from ".MAIN_DB_PREFIX."entrepot WHERE entity IN (1) ORDER BY ref";
				$res = $db->query($sql);
	
				$j = 0;
				$total_physical_stock = 0;
				$total_to_deliver = 0;
				$total_virtual_stock = 0;
				$numentr = $db->num_rows($res);
				while($j < $numentr){
					$objentrepot = $db->fetch_object($res);
					$sql = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut, ps.reel, ps.rowid as product_stock_id, p.pmp";
					$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
					$sql .= " ".MAIN_DB_PREFIX."product_stock as ps";
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
					$sql .= " WHERE ps.reel != 0";
					$sql .= " AND ps.fk_entrepot = e.rowid";
					$sql .= " AND e.rowid = $objentrepot->rowid";
					$sql .= " AND e.entity IN (".getEntity('stock').")";
					$sql .= " AND ps.fk_product = ".$object->id;
					$sql .= " ORDER BY e.ref";

					$entrepotstatic = new Entrepot($db);
					$product_lot_static = new Productlot($db);

					$total = 0;
					$totalvalue = $totalvaluesell = 0;

					$resql = $db->query($sql);
					if ($db->num_rows($resq) > 0) {
						$total = $totalwithpmp;
						$var = false;
						$obj = $db->fetch_object($resql);

						$entrepotstatic->id = $obj->rowid;
						$entrepotstatic->ref = $obj->ref;
						$entrepotstatic->libelle = $obj->ref;
						$entrepotstatic->label = $obj->ref;
						$entrepotstatic->lieu = $obj->lieu;
						$entrepotstatic->fk_parent = $obj->fk_parent;
						$entrepotstatic->statut = $obj->statut;

						// Warehouse desired stock
						if ($entrepotstatic->id == 1)
							$stock_desired_entrepot = $object->desiredstock_principal;
						elseif ($entrepotstatic->id == 2)
							$stock_desired_entrepot = $object->desiredstock_gpe;
						else
							$stock_desired_entrepot = 0;

						$stock_real = price2num($obj->reel, 'MS');
					}
					else{
						$total = $totalwithpmp;
						$var = false;
						$obj = $db->fetch_object($resql);

						$entrepotstatic->id = $objentrepot->rowid;
						$entrepotstatic->ref = $objentrepot->ref;
						$entrepotstatic->libelle = $objentrepot->ref;
						$entrepotstatic->label = $objentrepot->ref;
						$entrepotstatic->lieu = $objentrepot->lieu;
						$entrepotstatic->fk_parent = $objentrepot->fk_parent;
						$entrepotstatic->statut = $objentrepot->statut;
						// Warehouse desired stock
						if ($entrepotstatic->id == 1)
							$stock_desired_entrepot = $object->desiredstock_principal;
						elseif ($entrepotstatic->id == 2)
							$stock_desired_entrepot = $object->desiredstock_gpe;
						else
							$stock_desired_entrepot = 0;

						$stock_real = price2num(0, 'MS');
					}
	
					$physical_stock = ($stock_real > 0)? $stock_real : 0;
	
					$stock_to_deliver = ($stock_real < 0)? $stock_real : 0;
					
					$result = $object->load_stats_commande(0, '1,2', 1, $entrepotstatic->id);

					$stock_to_deliver = (($stock_to_deliver - $object->stats_commande['qty']) * -1);

					$result = $object->load_stats_sending(0, '2', 1, $filterShipmentStatus,$entrepotstatic->id);
					$stock_to_deliver -= $object->stats_expedition['qty'];
	
					// Virtual Stock
					$result = $object->load_stats_commande_fournisseur(0, '3,4', 1);
					$virtual_stock = $physical_stock - $stock_to_deliver;
						
					$total_physical_stock += $physical_stock;
					$total_to_deliver += $stock_to_deliver;
					$total_virtual_stock += $virtual_stock;
					$j++;
				}
				$result = $object->load_stats_commande_fournisseur(0, '3,4', 1);
				$pedidosOC = $object->stats_commande_fournisseur['qty'];
				//Formula Stock Virtual
				$total_virtual = $total_physical_stock - $total_to_deliver;

				require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $filterShipmentStatus = '';
                if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)) {
                    $filterShipmentStatus = Expedition::STATUS_VALIDATED  . ',' . Expedition::STATUS_CLOSED;
                } elseif (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
                    $filterShipmentStatus = Expedition::STATUS_CLOSED;
                }
				$result = $object->load_stats_sending(0, '2', 1, $filterShipmentStatus);
				$pedidosEnvio = $object->stats_expedition['qty'];

			//Cantidad de Stock por Surtir (POS)
			$helpondiff .= '<br>'.$langs->trans("Cantidad de Stock por Surtir (POS)").': '.$object->getQtyToStock();

			// Number of product from supplier order already received (partial receipt)
			if (!empty($conf->fournisseur->enabled)) {
				if ($found) $helpondiff .= '<br>'; else $found = 1;
				$helpondiff .= $langs->trans("ProductQtyInSuppliersShipmentAlreadyRecevied").': '.$object->stats_reception['qty'];
			}

			// Number of product in production
			if (!empty($conf->mrp->enabled)) {
				if ($found) $helpondiff .= '<br>'; else $found = 1;
				$helpondiff .= $langs->trans("ProductQtyToConsumeByMO").': '.$object->stats_mrptoconsume['qty'].'<br>';
				$helpondiff .= $langs->trans("ProductQtyToProduceByMO").': '.$object->stats_mrptoproduce['qty'];
			}


			// Calculating a theorical value
			print '<tr><td>';
			print $form->textwithpicto($langs->trans("VirtualStock"), $langs->trans("VirtualStockDesc"));
			print '</td>';
			print "<td>";
			//print (empty($stocktheo)?0:$stocktheo);
			//print $form->textwithpicto((empty($stocktheo) ? 0 : $stocktheo), $helpondiff);
			print $form->textwithpicto($total_virtual, $helpondiff);
			// if ($object->seuil_stock_alerte != '' && ($object->stock_theorique < $object->seuil_stock_alerte)) print ' '.img_warning($langs->trans("StockLowerThanLimit", $object->seuil_stock_alerte));
			print '</td>';
			print '</tr>';

            //Stock revisado
            print '<tr><td>'.$form->editfieldkey($form->textwithpicto($langs->trans("RevicedStock"), $langs->trans("StockLimitDesc"), 1), 'revicedstock', $object->revicedstock, $object, $user->rights->produit->creer).'</td><td>';
            print $form->editfieldval("RevisedStock", 'revicedstock', $object->revicedstock, $object, $user->rights->produit->creer, 'string');
            print '</td></tr>';

            //Stock apartado
            $sql =	 'SELECT SUM(td.qty) AS apartados '."\r\n"
            		.'FROM llx_pos_ticket AS t '."\r\n"
            		.'LEFT JOIN llx_pos_ticketdet AS td '."\r\n"
            		.'  ON t.rowid = td.fk_ticket'."\r\n"
            		.'WHERE td.fk_product='.$id."\r\n"
            		.'  AND td.ls_warehouse_status IN (7,10) '."\r\n"
					;
			if (!$resApart = $db->query($sql))
			{
				dol_print_error($db);
				die();
			}
			$apart = '';
			if ($dbApart = $db->fetch_object($resApart))
			{
				$apart = $dbApart->apartados;
			}
			if(!strlen($apart))
			{
				$apart = '0';
			}
			print '<tr><td>Stock apartado</td><td>'.$apart.'</td></tr>';

			// echo '<pre>';
			// print_r($object);
			// echo '</pre>';
			// die();
			
			// //Stock deseado en Almacen Matriz
            // print '<tr><td>'.$form->editfieldkey($form->textwithpicto($langs->trans("DesiredStockMatriz"), $langs->trans("StockLimitDesc"), 1), 'desiredstockma', $object->desiredstock_principal, $object, $user->rights->produit->creer).'</td><td>';
            // print $form->editfieldval("DesiredStockMatriz", 'desiredstockma', $object->desiredstock_principal, $object, $user->rights->produit->creer, 'string');
            // print '</td></tr>';

            // //Stock deseado en Almacen GPE
            // print '<tr><td>'.$form->editfieldkey($form->textwithpicto($langs->trans("DesiredStockGPE"), $langs->trans("StockLimitDesc"), 1), 'desiredstockgpe', $object->desiredstock_gpe, $object, $user->rights->produit->creer).'</td><td>';
            // print $form->editfieldval("DesiredStockGPE", 'desiredstockgpe', $object->desiredstock_gpe, $object, $user->rights->produit->creer, 'string');
            // print '</td></tr>';

			/**
			 * Stock limits
			 */
			// if ($action != 'editwh_limit') {
			// 	// Matriz
			// 	print '<tr><td>';
			// 	print $form->editfieldkey($form->textwithpicto($langs->trans("MatrizLimitStock"), $langs->trans("HelpWarehouseLimit"), 1), 'wh_limit', $object->wh1_limit, $object, $user->rights->produit->creer) . '</td><td>';
			// 	print $object->wh1_limit;
			// 	print '</td></tr>';
	
			// 	// Gdpe
			// 	print '<tr><td>';
			// 	print $form->editfieldkey($form->textwithpicto($langs->trans("SecondWarehouseLimit"), $langs->trans("HelpWarehouseLimit"), 1), 'wh_limit', $object->wh2_limit, $object, $user->rights->produit->creer) . '</td><td>';
			// 	print $object->wh2_limit;
			// 	print '</td></tr>';
			// }
			// else {
			// 	print '<tr><td>';
			// 	print $form->editfieldkey($form->textwithpicto($langs->trans("StockLimit"), $langs->trans("HelpSetWarehouseLimit"), 1), 'wh_limit', $object->wh1_limit, $object, $user->rights->produit->creer) . '</td><td>';
			// 	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
			// 	print '<input type="hidden" name="action" value="setwh_limit">';
			// 	print '<input type="hidden" name="token" value="'.newToken().'">';
			// 	print '<input type="hidden" name="id" value="'.$object->id.'">';
			// 	print '<span>'. $langs->trans('MatrizLimitStock') .':</span><input type="text" name="wh1_limit" size="5" value="'.$object->wh1_limit.'"><br>';
			// 	print '<span>'. $langs->trans('SecondWarehouseLimit') .':</span><input type="text" name="wh2_limit" size="5" value="'.$object->wh2_limit.'"><br>';
			// 	print '<input type="submit" class="button'.(empty($notabletag) ? '' : ' ').'" name="modify" value="'.$langs->trans("Modify").'">';
			// 	print '<input type="submit" class="button'.(empty($notabletag) ? '' : ' ').'" name="cancel" value="'.$langs->trans("Cancel").'">';
			// 	print '</td>';
			// }

			/**
			 * Stock percentages
			 */
			// if ($action != 'editwh_percent') {

			// 	//Collapse
			// 	print '<tr><td class="dropdown-toggle" id="extra-toggle" style="cursor: pointer;">Porcentajes de Stock</td><td colspan="4">';
			// 	print '<div id="area-toggle" style="display: none;">';
			// 	print '<table>';

			// 		// Matriz
			// 		print '<tr><td>';
			// 		print $form->editfieldkey($form->textwithpicto($langs->trans("MatrizPercent"), $langs->trans("HelpWarehousePercent"), 1), 'wh_percent', $object->wh1percent, $object, $user->rights->produit->creer) . '</td><td>';
			// 		print $object->wh1percent;
			// 		print '</td></tr>';
		
			// 		// Gdpe
			// 		print '<tr><td>';
			// 		print $form->editfieldkey($form->textwithpicto($langs->trans("SecondWarehousePercent"), $langs->trans("HelpWarehousePercent"), 1), 'wh_percent', $object->wh2percent, $object, $user->rights->produit->creer) . '</td><td>';
			// 		print $object->wh2percent;
			// 		print '</td></tr>';

			// 	print '</table>';
			// 	print '</div>';
			// 	print '
			// 		<script>
			// 			$(document).ready(function() {
			// 				$("#extra-toggle").on("click", function(event) {
			// 					$("#area-toggle").fadeToggle("slow");
			// 					$(this).parent().toggleClass("open");
			// 				});
			// 			});
			// 		</script>
			// 		';
			// }
			// else {
			// 	print '<tr><td>';
			// 	print $form->editfieldkey($form->textwithpicto($langs->trans("StockPercents"), $langs->trans("HelpSetWarehousePercent"), 1), 'wh_percent', $object->wh1percent, $object, $user->rights->produit->creer) . '</td><td>';
			// 	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
			// 	print '<input type="hidden" name="action" value="setwh_percent">';
			// 	print '<input type="hidden" name="token" value="'.newToken().'">';
			// 	print '<input type="hidden" name="id" value="'.$object->id.'">';
			// 	print '<span>'. $langs->trans('MatrizPercent') .':</span><input type="text" name="wh1percent" size="5" value="'.$object->wh1percent.'"><br>';
			// 	print '<span>'. $langs->trans('SecondWarehousePercent') .':</span><input type="text" name="wh2percent" size="5" value="'.$object->wh2percent.'"><br>';
			// 	print '<input type="submit" class="button'.(empty($notabletag) ? '' : ' ').'" name="modify" value="'.$langs->trans("Modify").'">';
			// 	print '<input type="submit" class="button'.(empty($notabletag) ? '' : ' ').'" name="cancel" value="'.$langs->trans("Cancel").'">';
			// 	print '</td>';
			// }

			// Last movement
			if (!empty($user->rights->stock->mouvement->lire))
			{
				$sql = "SELECT max(m.datem) as datem";
				$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
				$sql .= " WHERE m.fk_product = '".$object->id."'";
				$resqlbis = $db->query($sql);
				if ($resqlbis) {
					$obj = $db->fetch_object($resqlbis);
					$lastmovementdate = $db->jdate($obj->datem);
				} else {
					dol_print_error($db);
				}
				print '<tr><td class="tdtop">'.$langs->trans("LastMovement").'</td><td>';
				if ($lastmovementdate) {
					print dol_print_date($lastmovementdate, 'dayhour').' ';
					print '(<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$object->id.'">'.$langs->trans("FullList").'</a>)';
				} else {
					print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$object->id.'">'.$langs->trans("None").'</a>';
				}
				print "</td></tr>";
			}

			 //Último Ajuste de Inventario
			 if($object->isProduct())
			 {
				 $sqlMouvement = "SELECT datem FROM ".MAIN_DB_PREFIX."stock_mouvement WHERE fk_product = ".$object->id;
				 $sqlMouvement .= " AND label LIKE '%Corrección de Stock por Ajuste de Inventario%' ";
				 $sqlMouvement .= " ORDER BY rowid DESC LIMIT 1";
				 $resqlMouvement = $db->query($sqlMouvement);
				 if ($resqlMouvement)
				 {
					 $objMouvement = $db->fetch_object($resqlMouvement);
				 }
				 print '<tr><td>'.$langs->trans("LastInventoryAjust").'</td><td colspan="2">';
				 print dol_print_date($db->jdate($objMouvement->datem), 'dayhour', 'tzuserrel');
				 print '</td></tr>';
			 }
		}
		print "</table>";

        print '</div>';
        print '<div style="clear:both"></div>';

		dol_fiche_end();
	}

	// Correct stock
	if ($action == "correction")
	{
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stockcorrection.tpl.php';
		print '<br><br>';
	}

	// Transfer of units
	if ($action == "transfert")
	{
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stocktransfer.tpl.php';
		print '<br><br>';
	}
}
else
{
	dol_print_error();
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

$parameters = array();


/**
 * TODO: Se crean las vistas para la tabla de stock de productos por almacén
 */

if (!$variants) {
	/*
	 * Stock detail (by warehouse). May go down into batch details.
	 */

	print '<div class="div-table-responsive">';
	print '<br>';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td colspan="4">'.$langs->trans("Warehouse").'</td>';
	print '<td class="right">'.$langs->trans("PhysicalStock").'</td>';
	print '<td class="right">'.$langs->trans("VirtualStock").'</td>';
	// print '<td class="right">'.$langs->trans("AverageUnitPricePMPShort").'</td>'; //
    print '<td class="right">'.$langs->trans("Mínimo").'</td>'; // Replacing PMP for UEPS
	print '<td class="right">'.$langs->trans("Reórden").'</td>';
	print '<td class="right">'.$langs->trans("Máximo").'</td>';
	print '<td class="right">'.$langs->trans("SellPriceMin").'</td>';
	print '<td class="right">'.$langs->trans("EstimatedStockValueSellShort").'</td>';
	print '<td class="right">Calcular automáticamente</td>';
	print '</tr>';
	if ((!empty($conf->productbatch->enabled)) && $object->hasbatch()) {
		print '<tr class="liste_titre"><td width="10%"></td>';
		print '<td class="right" width="10%">'.$langs->trans("batch_number").'</td>';
		print '<td class="center" width="10%">'.$langs->trans("EatByDate").'</td>';
		print '<td class="center" width="10%">'.$langs->trans("SellByDate").'</td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';
	}

	$sql = "SELECT rowid, ref, lieu, fk_parent, statut from ".MAIN_DB_PREFIX."entrepot WHERE entity IN (1) ORDER BY ref";
	$res = $db->query($sql);
	
	$j = 0;
	$total_physical_stock = 0;
	$total_to_deliver = 0;
	$total_virtual_stock = 0;
	$numentr = $db->num_rows($res);
	while($j < $numentr){
		$objentrepot = $db->fetch_object($res);
		$sql = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut, ps.reel, ps.rowid as product_stock_id, p.pmp";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
		$sql .= " ".MAIN_DB_PREFIX."product_stock as ps";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
		$sql .= " WHERE ps.reel != 0";
		$sql .= " AND ps.fk_entrepot = e.rowid";
		$sql .= " AND e.rowid = $objentrepot->rowid";
		$sql .= " AND e.entity IN (".getEntity('stock').")";
		$sql .= " AND ps.fk_product = ".$object->id;
		$sql .= " ORDER BY e.ref";
		// var_dump($sql);
		$entrepotstatic = new Entrepot($db);
		$product_lot_static = new Productlot($db);

		$total = 0;
		$totalvalue = $totalvaluesell = 0;

		$resql = $db->query($sql);
		if ($db->num_rows($resq) > 0) {
			$total = $totalwithpmp;
			$var = false;
			$obj = $db->fetch_object($resql);

			$entrepotstatic->id = $obj->rowid;
			$entrepotstatic->ref = $obj->ref;
			$entrepotstatic->libelle = $obj->ref;
			$entrepotstatic->label = $obj->ref;
			$entrepotstatic->lieu = $obj->lieu;
			$entrepotstatic->fk_parent = $obj->fk_parent;
			$entrepotstatic->statut = $obj->statut;

			// Warehouse desired stock
			if ($entrepotstatic->id == 1)
				$stock_desired_entrepot = $object->desiredstock_principal;
			elseif ($entrepotstatic->id == 2)
				$stock_desired_entrepot = $object->desiredstock_gpe;
			else
				$stock_desired_entrepot = 0;

			$stock_real = price2num($obj->reel, 'MS');
		}
		else{
			$total = $totalwithpmp;
			$var = false;
			$obj = $db->fetch_object($resql);

			$entrepotstatic->id = $objentrepot->rowid;
			$entrepotstatic->ref = $objentrepot->ref;
			$entrepotstatic->libelle = $objentrepot->ref;
			$entrepotstatic->label = $objentrepot->ref;
			$entrepotstatic->lieu = $objentrepot->lieu;
			$entrepotstatic->fk_parent = $objentrepot->fk_parent;
			$entrepotstatic->statut = $objentrepot->statut;
			// Warehouse desired stock
			if ($entrepotstatic->id == 1)
				$stock_desired_entrepot = $object->desiredstock_principal;
			elseif ($entrepotstatic->id == 2)
				$stock_desired_entrepot = $object->desiredstock_gpe;
			else
				$stock_desired_entrepot = 0;

			$stock_real = price2num(0, 'MS');
		}
		print '<tr class="oddeven">';
		print '<td colspan="4">'.$entrepotstatic->getNomUrl(1).'</td>';
		// Physical Stock
		$physical_stock = ($stock_real > 0)? $stock_real : 0;
		print '<td class="right">';
		/** Limit for alerts */
		$limit_for_alert = ($entrepotstatic->id == 1)? $object->wh1_limit : $object->wh2_limit;
		if ($physical_stock < $limit_for_alert)
			print img_warning($langs->trans("StockLowerThanDesiredWarehouse", $stock_desired_entrepot));
		
		print $physical_stock.'</td>';

		$stock_to_deliver = ($stock_real < 0)? $stock_real : 0;


		$result = $object->load_stats_commande(0, '1,2', 1, $objentrepot->rowid);
		$stock_to_deliver = (($stock_to_deliver - $object->stats_commande['qty']) * -1);
		$result = $object->load_stats_sending(0, '2', 1, $filterShipmentStatus, $objentrepot->rowid);
		$stock_to_deliver -= $object->stats_expedition['qty'];
		//}
		//else{
		//	$stock_to_deliver = 0;
		//}		

		// Virtual Stock
		//if($entrepotstatic->id == 1){
			// $result = $object->load_stats_commande_fournisseur(0, '3,4', 1);
			// $virtual_stock = $object->stats_commande_fournisseur['qty'] - $stock_to_deliver;
			
			//$virtual_stock = $object->stock_theorique;

		
		$virtual_stock = $physical_stock - $stock_to_deliver;
		//}
		//else{
		//	$virtual_stock = 0;
		//}
		print '<td class="right">'.$virtual_stock.'</td>';

		// PMP
		// print '<td class="right">'.(price2num($object->pmp) ? price2num($object->pmp, 'MU') : '').'</td>';
		// UEPS
		$sqlueps = 'SELECT price ';
		$sqlueps .= 'FROM '.MAIN_DB_PREFIX.'stock_mouvement ';
		$sqlueps .= 'WHERE fk_product = '.$object->id.' ';
		$sqlueps .= 'AND value > 0 ORDER BY datem DESC LIMIT 1';
		$resqlueps = $db->query($sqlueps);
		if($resqlueps){
			$objres = $db->fetch_object($resqlueps);
		}
		$ueps_value = price($objres->price*$stock_real);
		/**
		 * FIXME: Se debe cambiar por el valor deseado de stock y debe ser un campo editable en la ficha del producto
		 * 
		 */
		$stock_desired = 0;
		// Obtenemos el valor deseado de stock de la tabla llx_product_warehouse_properties para el producto y el almacén
		$sql = 'SELECT desiredstock FROM '.MAIN_DB_PREFIX.'product_warehouse_properties WHERE fk_product = '.$object->id.' AND fk_entrepot = '.$entrepotstatic->id;
		// Lo ejecutamos
		$resql = $db->query($sql);
		// Si se ejecutó correctamente
		if($resql){
			// Obtenemos el objeto con el resultado
			$obj = $db->fetch_object($resql);
			// Si el resultado no es nulo
			if($obj){
				// Obtenemos el valor deseado de stock
				$stock_desired = $obj->desiredstock;
			}
		}
		// Se agrega la funcionalidad de tenga un boton para editar y al darle click se muestre un input para editar el valor con un boton de guardar y cancelar
		print '<td class="right">';
		print '<input type="text" id="stock_desired_'.$entrepotstatic->id.'" value="'.$stock_desired.'" style="display:none; width: 50px;" />';
		print '<span id="stock_desired_span_'.$entrepotstatic->id.'">'.($stock_desired ? $stock_desired : 0).'</span>';
		if (!empty($user->rights->stock->edit_minmax)) {
			print '<a href="javascript:void(0);" onclick="editStockDesired('.$entrepotstatic->id.')" id="edit_stock_desired_'.$entrepotstatic->id.'" style="display:inline-block; margin-left: 5px;"><i class="fa fa-pencil"></i></a>';
			print '<a href="javascript:void(0);" onclick="saveStockDesired('.$entrepotstatic->id.')" id="save_stock_desired_'.$entrepotstatic->id.'" style="display:none; margin-left: 5px;"><i class="fa fa-save"></i></a>';
			print '<a href="javascript:void(0);" onclick="cancelStockDesired('.$entrepotstatic->id.')" id="cancel_stock_desired_'.$entrepotstatic->id.'" style="display:none; margin-left: 5px;"><i class="fa fa-times"></i></a>';
		}
		print '</td>';

		// creamos la funcionalidad en js para editar el valor del stock deseado
		print '<script type="text/javascript">
			function editStockDesired(id){
				$("#stock_desired_"+id).show();
				$("#stock_desired_span_"+id).hide();
				$("#edit_stock_desired_"+id).hide();
				$("#save_stock_desired_"+id).show();
				$("#cancel_stock_desired_"+id).show();
			}
			function saveStockDesired(id){
				var stock_desired = $("#stock_desired_"+id).val();
				// validamos que no sea negativo el valor del stock deseado y que sea un numero entero y no un decimal o un caracter no numerico
				if(stock_desired < 0 || !Number.isInteger(Number(stock_desired))){
					alert("El valor del stock deseado debe ser un número entero positivo");
					return false;
				}
				$.ajax({
					url: "'.DOL_URL_ROOT.'/product/ajax/saveStockDesired.php",
					type: "POST",
					data: {id: '.$object->id.', stock_desired: stock_desired, id_entrepot: id},
					success: function(data){
						$("#stock_desired_span_"+id).html(stock_desired);
						$("#stock_desired_"+id).hide();
						$("#stock_desired_span_"+id).show();
						$("#edit_stock_desired_"+id).show();
						$("#save_stock_desired_"+id).hide();
						$("#cancel_stock_desired_"+id).hide();
						// window.location.reload();
					}
				});
			}
			function cancelStockDesired(id){
				$("#stock_desired_"+id).hide();
				$("#stock_desired_span_"+id).show();
				$("#edit_stock_desired_"+id).show();
				$("#save_stock_desired_"+id).hide();
				$("#cancel_stock_desired_"+id).hide();
			}
		</script>';

		// Hacemos lo mismo para el campo de limite de stock para alertas 
		$stock_alert = 0;
		// Obtenemos las aleertas de stock de la tabla llx_product_warehouse_properties para el producto y el almacén
		$sql = 'SELECT seuil_stock_alerte FROM '.MAIN_DB_PREFIX.'product_warehouse_properties WHERE fk_product = '.$object->id.' AND fk_entrepot = '.$entrepotstatic->id;
		// Lo ejecutamos
		$resql = $db->query($sql);
		// Si se ejecutó correctamente
		if($resql){
			// Obtenemos el objeto con el resultado
			$obj = $db->fetch_object($resql);
			// Si el resultado no es nulo
			if($obj){
				// Obtenemos el valor deseado de stock
				$stock_alert = $obj->seuil_stock_alerte;
			}
		}
		print '<td class="right">';
		print '<input type="text" id="stock_alert_'.$entrepotstatic->id.'" value="'.$stock_alert.'" style="display:none; width: 50px;" />';
		print '<span id="stock_alert_span_'.$entrepotstatic->id.'">'.($stock_alert ? $stock_alert : 0).'</span>';
		if (!empty($user->rights->stock->edit_reorder)) {
			print '<a href="javascript:void(0);" onclick="editStockAlert('.$entrepotstatic->id.')" id="edit_stock_alert_'.$entrepotstatic->id.'" style="display:inline-block; margin-left: 5px;"><i class="fa fa-pencil"></i></a>';
			print '<a href="javascript:void(0);" onclick="saveStockAlert('.$entrepotstatic->id.')" id="save_stock_alert_'.$entrepotstatic->id.'" style="display:none; margin-left: 5px;"><i class="fa fa-save"></i></a>';
			print '<a href="javascript:void(0);" onclick="cancelStockAlert('.$entrepotstatic->id.')" id="cancel_stock_alert_'.$entrepotstatic->id.'" style="display:none; margin-left: 5px;"><i class="fa fa-times"></i></a>';
		}
		print '</td>';

		// creamos la funcionalidad en js para editar el valor del stock alert
		print '<script type="text/javascript">
			function editStockAlert(id){
				$("#stock_alert_"+id).show();
				$("#stock_alert_span_"+id).hide();
				$("#edit_stock_alert_"+id).hide();
				$("#save_stock_alert_"+id).show();
				$("#cancel_stock_alert_"+id).show();
			}
			function saveStockAlert(id){
				var stock_alert = $("#stock_alert_"+id).val();
				// validamos que no sea negativo el valor del stock deseado y que sea un numero entero y no un decimal o un caracter no numerico
				if(stock_alert < 0 || !Number.isInteger(Number(stock_alert))){
					alert("El valor del stock alert debe ser un número entero positivo");
					return false;
				}
				$.ajax({
					url: "'.DOL_URL_ROOT.'/product/ajax/saveStockAlert.php",
					type: "POST",
					data: {id: '.$object->id.', stock_alert: stock_alert, id_entrepot: id},
					success: function(data){
						$("#stock_alert_span_"+id).html(stock_alert);
						$("#stock_alert_"+id).hide();
						$("#stock_alert_span_"+id).show();
						$("#edit_stock_alert_"+id).show();
						$("#save_stock_alert_"+id).hide();
						$("#cancel_stock_alert_"+id).hide();
						// window.location.reload();
					}
				});
			}
			function cancelStockAlert(id){
				$("#stock_alert_"+id).hide();
				$("#stock_alert_span_"+id).show();
				$("#edit_stock_alert_"+id).show();
				$("#save_stock_alert_"+id).hide();
				$("#cancel_stock_alert_"+id).hide();
			}
		</script>';

		// Max value stock
		// cantidad de stock que no debe sobrepasar los niveles de stock ya que existiría un sobre inventario
		$stock_max = 0;
		$sql = 'SELECT stock_max FROM '.MAIN_DB_PREFIX.'product_warehouse_properties WHERE fk_product = '.$object->id.' AND fk_entrepot = '.$entrepotstatic->id;
		$resql = $db->query($sql);
		if($resql){
			$obj = $db->fetch_object($resql);
			if($obj){
				$stock_max = $obj->stock_max;
			}
		}


		print '<td class="right">';
		print '<input type="text" id="stock_max_'.$entrepotstatic->id.'" value="'.$stock_max.'" style="display:none; width: 50px;" />';
		print '<span id="stock_max_span_'.$entrepotstatic->id.'">'.($stock_max ? $stock_max : 0).'</span>';
		if (!empty($user->rights->stock->edit_minmax)) {
			print '<a href="javascript:void(0);" onclick="editStockMax('.$entrepotstatic->id.')" id="edit_stock_max_'.$entrepotstatic->id.'" style="display:inline-block; margin-left: 5px;"><i class="fa fa-pencil"></i></a>';
			print '<a href="javascript:void(0);" onclick="saveStockMax('.$entrepotstatic->id.')" id="save_stock_max_'.$entrepotstatic->id.'" style="display:none; margin-left: 5px;"><i class="fa fa-save"></i></a>';
			print '<a href="javascript:void(0);" onclick="cancelStockMax('.$entrepotstatic->id.')" id="cancel_stock_max_'.$entrepotstatic->id.'" style="display:none; margin-left: 5px;"><i class="fa fa-times"></i></a>';
		}
		print '</td>';

		// creamos la funcionalidad en js para editar el valor del stock max
		print '<script type="text/javascript">
			function editStockMax(id){
				$("#stock_max_"+id).show();
				$("#stock_max_span_"+id).hide();
				$("#edit_stock_max_"+id).hide();
				$("#save_stock_max_"+id).show();
				$("#cancel_stock_max_"+id).show();
			}
			function saveStockMax(id){
				var stock_max = $("#stock_max_"+id).val();
				// validamos que no sea negativo el valor del stock deseado y que sea un numero entero y no un decimal o un caracter no numerico
				if(stock_max < 0 || !Number.isInteger(Number(stock_max))){
					alert("El valor del stock max debe ser un número entero positivo");
					return false;
				}
				$.ajax({
					url: "'.DOL_URL_ROOT.'/product/ajax/saveStockMax.php",
					type: "POST",
					data: {id: '.$object->id.', stock_max: stock_max, id_entrepot: id},
					success: function(data){
						$("#stock_max_span_"+id).html(stock_max);
						$("#stock_max_"+id).hide();
						$("#stock_max_span_"+id).show();
						$("#edit_stock_max_"+id).show();
						$("#save_stock_max_"+id).hide();
						$("#cancel_stock_max_"+id).hide();
						// window.location.reload();
					}
				});
			}
			function cancelStockMax(id){
				$("#stock_max_"+id).hide();
				$("#stock_max_span_"+id).show();
				$("#edit_stock_max_"+id).show();
				$("#save_stock_max_"+id).hide();
				$("#cancel_stock_max_"+id).hide();
			}
		</script>';
		
		// Sell price
		print '<td class="right">';
		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($object->price, 'MU'), 1);
		else print $langs->trans("Variable");
		print '</td>';
		// Value sell
		print '<td class="right">';
		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($object->price * $obj->reel, 'MT'), 1).'</td>';
		else print $langs->trans("Variable");
		
		$checked = 'checked';
		// Obtenemos la propiedad de calculateStock de la tabla llx_product_warehouse_properties para el producto y el almacén
		$sql = 'SELECT calculateStock FROM '.MAIN_DB_PREFIX.'product_warehouse_properties WHERE fk_product = '.$object->id.' AND fk_entrepot = '.$entrepotstatic->id;
		// Lo ejecutamos
		$resql = $db->query($sql);
		// Si se ejecutó correctamente
		if($resql){
			// Obtenemos el objeto con el resultado
			$obj = $db->fetch_object($resql);
			// Si el resultado no es nulo
			if($obj){
				// Cambiamos la propiedad por default del checkbox
				if($obj->calculateStock==0) $checked = '';
			}
		}
		print '<td class="right">';
		print '<input type="checkbox" id="checkbox" value="'.$entrepotstatic->id.'" '.$checked.'>';
		print '</td>';

		print '</tr>';

		$total += $obj->reel;
		if (price2num($object->pmp)) $totalwithpmp += $obj->reel;
		$totalvalue = $totalvalue + ($object->pmp * $obj->reel);
		$totalvaluesell = $totalvaluesell + ($object->price * $obj->reel);
		// Batch Detail
		if ((!empty($conf->productbatch->enabled)) && $object->hasbatch()) {
			$details = Productbatch::findAll($db, $obj->product_stock_id, 0, $object->id);
			if ($details < 0) dol_print_error($db);
			foreach ($details as $pdluo) {
				$product_lot_static->id = $pdluo->lotid;
				$product_lot_static->batch = $pdluo->batch;
				$product_lot_static->eatby = $pdluo->eatby;
				$product_lot_static->sellby = $pdluo->sellby;

				if ($action == 'editline' && GETPOST('lineid', 'int') == $pdluo->id) { //Current line edit
					print "\n".'<tr>';
					print '<td colspan="9">';
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
					print '<input type="hidden" name="pdluoid" value="'.$pdluo->id.'"><input type="hidden" name="action" value="updateline"><input type="hidden" name="id" value="'.$id.'"><table class="noborder centpercent"><tr><td width="10%"></td>';
					print '<td class="right" width="10%"><input type="text" name="batch_number" value="'.$pdluo->batch.'"></td>';
					print '<td class="center" width="10%">';
					print $form->selectDate($pdluo->eatby, 'eatby', '', '', 1, '', 1, 0);
					print '</td>';
					print '<td class="center" width="10%">';
					print $form->selectDate($pdluo->sellby, 'sellby', '', '', 1, '', 1, 0);
					print '</td>';
					print '<td class="right" width="10%">'.$pdluo->qty.($pdluo->qty < 0 ? ' '.img_warning() : '').'</td>';
					print '<td colspan="4"><input type="submit" class="button" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
					print '<input type="submit" class="button" id="cancellinebutton" name="Cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
					print '</table>';
					print '</form>';
					print '</td></tr>';
				} else {
					print "\n".'<tr><td class="right">';
					print img_picto($langs->trans("Tranfer"), 'uparrow', 'class="hideonsmartphone"').' ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;id_entrepot='.$entrepotstatic->id.'&amp;action=transfert&amp;pdluoid='.$pdluo->id.'">'.$langs->trans("TransferStock").'</a>';
					// Disabled, because edition of stock content must use the "Correct stock menu".
					// Do not use this, or data will be wrong (bad tracking of movement label, inventory code, ...
					//print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=editline&amp;lineid='.$pdluo->id.'#'.$pdluo->id.'">';
					//print img_edit().'</a></td>';
					print '<td class="right">';
					print $product_lot_static->getNomUrl(1);
					print '</td>';
					print '<td class="center">'.dol_print_date($pdluo->eatby, 'day').'</td>';
					print '<td class="center">'.dol_print_date($pdluo->sellby, 'day').'</td>';
					print '<td class="right">'.$pdluo->qty.($pdluo->qty < 0 ? ' '.img_warning() : '').'</td>';
					print '<td colspan="4"></td>';
					print '</tr>';
				}
			}
		}
		$total_physical_stock += $physical_stock;
		$total_to_deliver += $stock_to_deliver;
		$total_virtual_stock += $virtual_stock;
		$j++;
	}

	
	// Script para guardar el valor de checkboxes seleccionados
	print '<script type="text/javascript">	
		// Seleccionamos todos los checkboxes 
		var checkboxes = document.querySelectorAll("input[id^=checkbox]");

		// Usamos Array.forEach para agregar un event listener a cada checkbox.
		checkboxes.forEach(function(checkbox) {
			checkbox.addEventListener("change", function() {
				var check = 0;
				if(this.checked){
					check = 1;
				}
				$.ajax({
					url: "'.DOL_URL_ROOT.'/product/ajax/calculateStockOnSales.php",
					type : "POST",
					data: {id: '.$object->id.', warehouse: this.value, check: check},
				});
			});
		});
	</script>';
	// Total line
	print '<tr class="liste_total"><td class="right liste_total" colspan="4">'.$langs->trans("Total").':</td>';
	print '<td class="liste_total right">'.price2num($total_physical_stock, 'MS').'</td>';
	print '<td class="liste_total right">'.price2num($total_virtual_stock, 'MS').'</td>';
	print '<td class="liste_total right"></td>';
	print '<td class="liste_total right">';
	print ($totalwithpmp ? price(price2num($totalvalue / $totalwithpmp, 'MU')) : '&nbsp;'); // This value may have rounding errors
	print '</td>';
	// Value purchase
	print '<td class="liste_total right">';
	print $totalvalue ? price(price2num($totalvalue, 'MT'), 1) : '&nbsp;';
	print '</td>';
	print '<td class="liste_total right">';
	if (empty($conf->global->PRODUIT_MULTIPRICES)) print ($total ? price($totalvaluesell / $total, 1) : '&nbsp;');
	else print $langs->trans("Variable");
	print '</td>';
	// Value to sell
	print '<td class="liste_total right">';
	if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($totalvaluesell, 'MT'), 1);
	else print $langs->trans("Variable");
	print '</td>';
	print "</tr>";

	print "</table>";
	print '</div>';

	if (!empty($conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE)) {
		print '<br><br>';
		print load_fiche_titre($langs->trans('AddNewProductStockWarehouse'));

		if (!empty($user->rights->produit->creer)) {
			print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			print '<input type="hidden" name="action" value="addlimitstockwarehouse">';
			print '<input type="hidden" name="id" value="'.$id.'">';
		}
		print '<table class="noborder centpercent">';
		if (!empty($user->rights->produit->creer)) {
			print '<tr class="liste_titre"><td width="40%">'.$formproduct->selectWarehouses('', 'fk_entrepot').'</td>';
			print '<td class="right"><input name="seuil_stock_alerte" type="text" placeholder="'.$langs->trans("StockLimit").'" /></td>';
			print '<td class="right"><input name="desiredstock" type="text" placeholder="'.$langs->trans("DesiredStock").'" /></td>';
			print '<td class="right"><input type="submit" value="'.$langs->trans('Save').'" class="button" /></td>';
			print '</tr>';
		} else {
			print '<tr class="liste_titre"><td width="40%">'.$langs->trans("Warehouse").'</td>';
			print '<td class="right">'.$langs->trans("StockLimit").'</td>';
			print '<td class="right">'.$langs->trans("DesiredStock").'</td>';
			print '</tr>';
		}

		$pse = new ProductStockEntrepot($db);
		$lines = $pse->fetchAll($id);

		if (!empty($lines)) {
			$var = false;
			foreach ($lines as $line) {
				$ent = new Entrepot($db);
				$ent->fetch($line['fk_entrepot']);
				print '<tr class="oddeven"><td width="40%">'.$ent->getNomUrl(3).'</td>';
				print '<td class="right">'.$line['seuil_stock_alerte'].'</td>';
				print '<td class="right">'.$line['desiredstock'].'</td>';
				if (!empty($user->rights->produit->creer)) {
					print '<td class="right"><a href="?id='.$id.'&fk_productstockwarehouse='.$line['id'].'&action=delete_productstockwarehouse">'.img_delete().'</a></td>';
				}
				print '</tr>';
			}
		}

		print "</table>";

		if (!empty($user->rights->produit->creer)) {
			print '</form>';
		}
	}
} else {
	// List of variants
	include_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
	include_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
	$prodstatic = new Product($db);
	$prodcomb = new ProductCombination($db);
	$comb2val = new ProductCombination2ValuePair($db);
	$productCombinations = $prodcomb->fetchAllByFkProductParent($object->id);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="massaction">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	// load variants
	$title = $langs->trans("ProductCombinations");

	print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0);

	print '<div class="div-table-responsive">';
	?>
	<table class="liste">
		<tr class="liste_titre">
			<td class="liste_titre"><?php echo $langs->trans('Product') ?></td>
			<td class="liste_titre"><?php echo $langs->trans('Combination') ?></td>
			<td class="liste_titre center"><?php echo $langs->trans('OnSell') ?></td>
			<td class="liste_titre center"><?php echo $langs->trans('OnBuy') ?></td>
			<td class="liste_titre right"><?php echo $langs->trans('Stock') ?></td>
			<td class="liste_titre"></td>
		</tr>
		<?php

		if (count($productCombinations))
		{
			$stock_total = 0;
			foreach ($productCombinations as $currcomb)
			{
				$prodstatic->fetch($currcomb->fk_product_child);
				$prodstatic->load_stock();
				$stock_total += $prodstatic->stock_reel;
				?>
				<tr class="oddeven">
					<td><?php echo $prodstatic->getNomUrl(1) ?></td>
					<td>
						<?php

						$productCombination2ValuePairs = $comb2val->fetchByFkCombination($currcomb->id);
						$iMax = count($productCombination2ValuePairs);

						for ($i = 0; $i < $iMax; $i++) {
							echo dol_htmlentities($productCombination2ValuePairs[$i]);

							if ($i !== ($iMax - 1)) {
								echo ', ';
							}
						} ?>
					</td>
					<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 0) ?></td>
					<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 1) ?></td>
					<td class="right"><?php echo $prodstatic->stock_reel ?></td>
					<td class="right">
						<a class="paddingleft paddingright" href="<?php echo dol_buildpath('/product/stock/product.php?id='.$currcomb->fk_product_child, 2) ?>"><?php echo img_edit() ?></a>
					</td>
					<?php
					?>
				</tr>
				<?php
			}

			print '<tr class="liste_total">';
			print '<td colspan="4" class="left">'.$langs->trans("Total").'</td>';
			print '<td class="right">'.$stock_total.'</td>';
			print '<td></td>';
			print '</tr>';
		}
		else
		{
			print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		?>
	</table>

	<?php
	print '</div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
