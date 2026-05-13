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
//ini_set('display_errors', '1');
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (!empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

// Security check
$result = restrictedArea($user, 'stock');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$msid = GETPOST('msid', 'int');
$product_id = GETPOST("product_id", 'int');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'movementlist';
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$backtopage = GETPOST('backtopage');

if ($contextpage == 'poslist')
{
    $_GET['optioncss'] = 'print';
}


// Security check
//$result=restrictedArea($user, 'stock', $id, 'entrepot&stock');
$result = restrictedArea($user, 'stock');

$idproduct = GETPOST('idproduct', 'int');
$year = GETPOST("year");
$month = GETPOST("month");
$search_ref = GETPOST('search_ref', 'alpha');
$search_movement = GETPOST("search_movement");
$search_product_barcode = trim(GETPOST("search_product_barcode"));
$search_product_ref = trim(GETPOST("search_product_ref"));
$search_product = trim(GETPOST("search_product"));
$search_warehouse = trim(GETPOST("search_warehouse"));
$search_inventorycode = trim(GETPOST("search_inventorycode"));
$search_user = trim(GETPOST("search_user"));
$search_batch = trim(GETPOST("search_batch"));
$search_qty = trim(GETPOST("search_qty"));
$search_type_mouvement = GETPOST('search_type_mouvement', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOST("page", 'int');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (!$sortfield) $sortfield = "m.rowid";
if (!$sortorder) $sortorder = "DESC";

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
    'm.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'm.datem'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
    'p.barcode'=>array('label'=>$langs->trans("Código de barras"), 'checked'=>1),
    'p.ref'=>array('label'=>$langs->trans("ProductRef"), 'checked'=>1, 'css'=>'maxwidth100'),
    'p.label'=>array('label'=>$langs->trans("ProductLabel"), 'checked'=>1),
    'm.batch'=>array('label'=>$langs->trans("BatchNumberShort"), 'checked'=>1, 'enabled'=>(!empty($conf->productbatch->enabled))),
    'pl.eatby'=>array('label'=>$langs->trans("EatByDate"), 'checked'=>0, 'enabled'=>(!empty($conf->productbatch->enabled))),
    'pl.sellby'=>array('label'=>$langs->trans("SellByDate"), 'checked'=>0, 'position'=>10, 'enabled'=>(!empty($conf->productbatch->enabled))),
    'e.ref'=>array('label'=>$langs->trans("Warehouse"), 'checked'=>1, 'enabled'=>(!$id > 0)), // If we are on specific warehouse, we hide it
    'm.fk_user_author'=>array('label'=>$langs->trans("Author"), 'checked'=>0),
    'm.inventorycode'=>array('label'=>$langs->trans("InventoryCodeShort"), 'checked'=>1),
    'm.label'=>array('label'=>$langs->trans("MovementLabel"), 'checked'=>1),
    'm.type_mouvement'=>array('label'=>$langs->trans("TypeMovement"), 'checked'=>1),
    'origin'=>array('label'=>$langs->trans("Origin"), 'checked'=>1),
	'm.value'=>array('label'=>$langs->trans("Qty"), 'checked'=>1),
	'm.price'=>array('label'=>$langs->trans("UnitPurchaseValue"), 'checked'=>0),
    'subtotal'=>array('label'=>$langs->trans("Subtotal"), 'checked'=>1),
	'tva'=>array('label'=>$langs->trans("IVA"), 'checked'=>1),
    'total'=>array('label'=>$langs->trans("Total"), 'checked'=>0),
	'm.fk_projet'=>array('label'=>$langs->trans('Project'), 'checked'=>0)
		//'m.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    //'m.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500)
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

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Do we click on purge search criteria ?
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
	{
	    $year = '';
	    $month = '';
	    $search_ref = '';
	    $search_movement = "";
	    $search_type_mouvement = "";
	    $search_inventorycode = "";
	    $search_product_ref = "";
	    $search_product_barcode = "";
	    $search_product = "";
	    $search_warehouse = "";
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
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}
//Imprimir PDF
if($action == 'printpdfcorrection'){
    $codetosearch = GETPOST('codetosearch');
    $dir = $conf->stock->dir_output."/stock_correction/". dol_sanitizeFileName($codetosearch);
    $cdir = scandir($dir);
    $file = "";
    if (is_array($cdir)) {
        foreach ($cdir as $key => $value) {
            if (substr($value,strlen($value)-4) == ".pdf" ){
                $file = $value;
            }
        }
    } else {
        error_log("Unable to read directory: $dir");
    }
    if ($file == ""){
        $sql = "SELECT p.barcode, p.ref as product_ref,p.label as product_label,p.rowid as product_id, m.value as qty,";
        $sql .= "  m.datem, e.lieu as entrepot_source_ref, u.firstname as user_firstname, u.lastname as user_lastname,u.login as user_login,";
        $sql .= " m.label,m.inventorycode as code";
        $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
        $sql .= " ".MAIN_DB_PREFIX."product as p,";
        $sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
        $sql .= " WHERE m.fk_product = p.rowid";
        $sql .= " AND m.fk_entrepot = e.rowid";
        $sql .= " AND e.entity IN (".getEntity('stock').")";
        if ($id > 0) $sql .= " AND e.rowid =".$id;

        $sql .= " AND m.inventorycode = '". $codetosearch."'";
        $resql = $db->query($sql);
        $masivecorrection = array();
        if($resql) {
            while ($obm = $db->fetch_object($resql)) {
                $line = new stdClass();
                $line->product_ref = $obm->product_ref;
                $line->product_label = $obm->product_label;
                $line->product_id = $obm->product_id;
                $line->qty = $obm->qty;
                $line->datem = $obm->datem;
                $line->entrepot_source_ref = $obm->entrepot_source_ref;
                $line->user_firstname = $obm->user_firstname;
                $line->user_lastname = $obm->user_lastname;
                $line->user_login = $obm->user_login;
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
                require(DOL_DOCUMENT_ROOT . '/core/modules/stock/doc/pdf_masivestockcorrection.modules.php');
                $largo = 250 + (sizeof($masivecorrection) * 10);
                $doc = new PDFMasiveStockCorrection($db, $largo);
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
                window.open('".DOL_URL_ROOT."/document.php?modulepart=stock&attachment=0&file=stock_correction%2F".urlencode(dol_sanitizeFileName($codetosearch))."%2F".urlencode($file)."&entity=1', '_blank');
            </script>";
    $action = '';
}
// Correct stock
if ($action == "correct_stock")
{
	$product = new Product($db);
	if (!empty($product_id)) $result = $product->fetch($product_id);

	$error = 0;

	if (empty($product_id))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		$action = 'correction';
	}
	if (!is_numeric($_POST["nbpiece"]))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$action = 'correction';
	}

	if (!$error)
    {
		$origin_element = '';
		$origin_id = null;

		if (GETPOST('projectid', 'int'))
		{
			$origin_element = 'project';
			$origin_id = GETPOST('projectid', 'int');
		}

        if ($product->hasbatch())
        {
        	$batch = GETPOST('batch_number', 'alphanohtml');

        	//$eatby=GETPOST('eatby');
        	//$sellby=GETPOST('sellby');
        	$eatby = dol_mktime(0, 0, 0, GETPOST('eatbymonth', 'int'), GETPOST('eatbyday', 'int'), GETPOST('eatbyyear', 'int'));
        	$sellby = dol_mktime(0, 0, 0, GETPOST('sellbymonth', 'int'), GETPOST('sellbyday', 'int'), GETPOST('sellbyyear', 'int'));

			$result = $product->correct_stock_batch(
	            $user,
	            $id,
	            GETPOST("nbpiece", 'int'),
	            GETPOST("mouvement"),
	            GETPOST("label", 'san_alpha'),
	            GETPOST('unitprice'),
	        	$eatby, $sellby, $batch,
				GETPOST('inventorycode', 'alphanohtml'),
	        	$origin_element,
	        	$origin_id
	        ); // We do not change value of stock for a correction
        }
        else
		{
			$result = $product->correct_stock(
	            $user,
	            $id,
	            GETPOST("nbpiece", 'int'),
	            GETPOST("mouvement"),
	            GETPOST("label", 'san_alpha'),
	            GETPOST('unitprice'),
				GETPOST('inventorycode', 'alphanohtml'),
	        	$origin_element,
	        	$origin_id
	        ); // We do not change value of stock for a correction
        }

        if ($result > 0)
        {
        	if ($backtopage)
        	{
	            header("Location: ".urldecode($backtopage));
        	}
        	else
        	{
	            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
        	}
            exit;
        }
        else
        {
            $error++;
            setEventMessages($product->error, $product->errors, 'errors');
            $action = 'correction';
        }
    }

    if (!$error) $action = '';
}
elseif($_POST['action']== 'correct_stock' && $cancel)
{
	header("Location: ".urldecode($backtopage).'&cancel=1');
	exit;
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && !$cancel)
{
	$product = new Product($db);
	if (!empty($product_id)) $result = $product->fetch($product_id);

    if (!(GETPOST("id_entrepot_destination", 'int') > 0))
    {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
        $error++;
        $action = 'transfert';
    }
	if (empty($product_id))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
		$action = 'transfert';
	}
    if (!GETPOST("nbpiece", 'int'))
    {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
        $error++;
        $action = 'transfert';
    }
    if ($id == GETPOST("id_entrepot_destination", 'int'))
    {
        setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
        $error++;
        $action = 'transfert';
    }

    if (!empty($conf->productbatch->enabled))
    {
        $product = new Product($db);
        $result = $product->fetch($product_id);

        if ($product->hasbatch() && !GETPOST("batch_number"))
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
            $object = new Entrepot($db);
            $result = $object->fetch($id);

            $db->begin();

            $product->load_stock('novirtual'); // Load array product->stock_warehouse

            // Define value of products moved
            $pricesrc = 0;
            if (isset($product->pmp)) $pricesrc = $product->pmp;
            $pricedest = $pricesrc;

            if ($product->hasbatch())
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
                    $srcwarehouseid = $id;
                    $batch = GETPOST('batch_number', 'alphanohtml');
                    $eatby = $d_eatby;
                    $sellby = $d_sellby;
                }

                if (!$error)
                {
                    // Remove stock
                    $result1 = $product->correct_stock_batch(
                        $user,
                        $srcwarehouseid,
                        GETPOST("nbpiece", 'int'),
                        1,
                        GETPOST("label", 'san_alpha'),
                        $pricesrc,
                        $eatby, $sellby, $batch,
                        GETPOST('inventorycode')
                        );
                    // Add stock
                    $result2 = $product->correct_stock_batch(
                        $user,
                        GETPOST("id_entrepot_destination", 'int'),
                        GETPOST("nbpiece", 'int'),
                        0,
                        GETPOST("label", 'san_alpha'),
                        $pricedest,
                        $eatby, $sellby, $batch,
                    	GETPOST('inventorycode', 'alphanohtml')
                        );
                }
            }
            else
            {
                // Remove stock
                $result1 = $product->correct_stock(
                    $user,
                    $id,
                    GETPOST("nbpiece"),
                    1,
                	GETPOST("label", 'san_alpha'),
                    $pricesrc,
                	GETPOST('inventorycode', 'alphanohtml')
                    );

                // Add stock
                $result2 = $product->correct_stock(
                    $user,
                    GETPOST("id_entrepot_destination"),
                    GETPOST("nbpiece"),
                    0,
                    GETPOST("label", 'san_alpha'),
                    $pricedest,
                	GETPOST('inventorycode', 'alphanohtml')
                    );
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
                    header("Location: movement_list.php?id=".$object->id);
                    exit;
                }
            }
            else
            {
                setEventMessages($product->error, $product->errors, 'errors');
                $db->rollback();
                $action = 'transfert';
            }
        }
    }
}

// Force selected warehouse if user has default warehouse
$disableWarehouse = 0;
if (!$user->rights->stock->show_all_warehouses) {
    $search_warehouse = $user->fk_warehouse;
    $disableWarehouse = 1;
}

/*
 * View
 */

if ($contextpage == 'poslist'){
    $constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
	$almacen = new Entrepot($db);
	$almacen->fetch($conf->global->$constantforkey);
    $search_warehouse = $almacen->id;
    $search_type_mouvement = 1;
    $search_movement = "Apartado";
}

$productlot = new ProductLot($db);
$productstatic = new Product($db);
$warehousestatic = new Entrepot($db);
$movement = new MouvementStock($db);
$userstatic = new User($db);
$form = new Form($db);
$formother = new FormOther($db);
$formproduct = new FormProduct($db);
if (!empty($conf->projet->enabled)) $formproject = new FormProjets($db);

$sql = "SELECT p.rowid, p.barcode, p.ref as product_ref, p.label as produit, p.tosell, p.tobuy, p.tobatch, p.fk_product_type as type, p.entity,";
$sql .= " e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu, e.fk_parent, e.statut,";
$sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
$sql .= " m.batch, m.price, ABS(m.value * m.price) as total,";
$sql .= " IF(p.exentoiva = 0, ABS(m.price * 0.16 * m.value), 0) as tva, ABS(m.value * m.price) as subtotal,";
$sql .= " IF(p.exentoiva = 0, ABS((m.price * 0.16 * m.value) + (m.value * m.price)), ABS(m.value * m.price)) as total, ";
$sql .= " m.type_mouvement,";
$sql .= " m.fk_projet as fk_project,";
$sql .= " pl.rowid as lotid, pl.eatby, pl.sellby,";
$sql .= " u.login, u.photo, u.lastname, u.firstname";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
$sql .= " ".MAIN_DB_PREFIX."product as p,";
$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (m.rowid = ef.fk_object)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product AND m.sellby = pl.sellby AND m.eatby = pl.eatby";
$sql .= " WHERE m.fk_product = p.rowid";
if ($msid > 0) $sql .= " AND m.rowid = ".$msid;
$sql .= " AND m.fk_entrepot = e.rowid";
$sql .= " AND e.entity IN (".getEntity('stock').")";
if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql .= " AND p.fk_product_type = 0";
if ($id > 0) $sql .= " AND e.rowid ='".$id."'";
$sql .= dolSqlDateFilter('m.datem', 0, $month, $year);
if ($idproduct > 0) $sql .= " AND p.rowid = '".$idproduct."'";
if (!empty($search_ref))			$sql .= natural_search('m.rowid', $search_ref, 1);
if ($contextpage == 'poslist'){
    if (!empty($search_movement))      $sql .= " AND m.label ='". $search_movement."'";
}else if (!empty($search_movement))      $sql .= natural_search('m.label', $search_movement);
if (!empty($search_inventorycode)) $sql .= natural_search('m.inventorycode', $search_inventorycode);
if (!empty($search_product_ref))   $sql .= natural_search('p.ref', $search_product_ref);
if (!empty($search_product_barcode))   $sql .= natural_search('p.barcode', $search_product_barcode);
if (!empty($search_product))       $sql .= natural_search('p.label', $search_product);
if ($search_warehouse != '' && $search_warehouse != '-1')  $sql .= natural_search('e.rowid', $search_warehouse, 2);
if (!empty($search_user))          $sql .= natural_search('u.login', $search_user);
if (!empty($search_batch))         $sql .= natural_search('m.batch', $search_batch);
if ($search_qty != '')				$sql .= natural_search('m.value', $search_qty, 1);
if ($search_type_mouvement != '' && $search_type_mouvement != '-1')	$sql .= natural_search('m.type_mouvement', $search_type_mouvement, 2);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
$sqlCount = "SELECT COUNT(*) AS total FROM llx_stock_mouvement";
$r = $db->query($sqlCount);
$objTotal = $db->fetch_object($r);
$nbtotalofrecords = $objTotal->total;
if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
{
    $page = 0;
    $offset = 0;
}
// if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
// {
//     $result = $db->query($sql);
//     $nbtotalofrecords = $db->num_rows($result);
//     if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
//     {
//     	$page = 0;
//     	$offset = 0;
//     }
// }
if (empty($search_inventorycode))
{
	$sql .= $db->plimit($limit + 1, $offset);
}
else
{
	$limit = 0;
}

if ($action == 'liberarapartado')
{
    $sql = 'SELECT ';
	$sql .= 'p.rowid,';
	$sql .= 'm.active,';
	$sql .= 'p.barcode,';
	$sql .= 'p.ref as product_ref,';
	$sql .= 'p.label as produit,';
	$sql .= 'p.tosell,';
	$sql .= 'p.tobuy,';
	$sql .= 'p.tobatch,';
	$sql .= 'p.fk_product_type as type,';
	$sql .= 'p.entity,';
	$sql .= 'e.ref as warehouse_ref,';
	$sql .= 'e.rowid as entrepot_id,';
	$sql .= 'e.lieu,';
	$sql .= 'e.fk_parent,';
	$sql .= 'e.statut,';
	$sql .= 'm.rowid as mid,';
	$sql .= 'm.value as qty,';
	$sql .= 'm.datem,';
	$sql .= 'm.fk_user_author,';
	$sql .= 'm.label,';
	$sql .= 'm.inventorycode,';
	$sql .= 'm.fk_origin,';
	$sql .= 'm.origintype,';
	$sql .= 'm.batch,';
	$sql .= 'm.price,';
	$sql .= 'ABS(m.value * m.price) as total,';
	$sql .= 'IF(p.exentoiva = 0,';
	$sql .= 'ABS(m.price * 0.16 * m.value),';
	$sql .= '0) as tva,';
	$sql .= 'ABS(m.value * m.price) as subtotal,';
	$sql .= 'IF(p.exentoiva = 0,';
	$sql .= 'ABS((m.price * 0.16 * m.value) + (m.value * m.price)),';
	$sql .= 'ABS(m.value * m.price)) as total,';
	$sql .= 'm.type_mouvement,';
	$sql .= 'm.fk_projet as fk_project,';
	$sql .= 'm.eatby,';
	$sql .= 'm.sellby,';
	$sql .= 'u.login,';
	$sql .= 'u.photo,';
	$sql .= 'u.lastname,';
	$sql .= 'u.firstname ';
    $sql .= 'FROM llx_stock_mouvement m ';
    $sql .= 'LEFT JOIN llx_user u ON m.fk_user_author = u.rowid ';
    $sql .= 'LEFT JOIN llx_product_stock ps ON m.fk_product = ps.fk_product AND m.fk_entrepot = ps.fk_entrepot ';
    $sql .= 'LEFT JOIN llx_product p ON m.fk_product = p.rowid ';
    $sql .= 'LEFT JOIN llx_entrepot e ON m.fk_entrepot = e.rowid ';
    $sql .= 'WHERE ';
    $sql .= "	m.label = 'Apartado' ";
    $sql .= " AND e.rowid = '".$user->fk_warehouse. "'";
    $sql .= natural_search('m.type_mouvement', $search_type_mouvement, 2);
    $sql .= '	AND m.active = 1 ';
    $sql .= 'ORDER BY ';
    $sql .= '	m.rowid DESC ';
    $sql .= 'LIMIT 26  ';
}
//print $sql;

$resql = $db->query($sql);

if (!empty($search_inventorycode)) $limit = $db->num_rows($resql);

if ($resql)
{
	$product = new Product($db);
	$object = new Entrepot($db);

	if ($idproduct > 0)
    {
        $product->fetch($idproduct);
    }
    if ($id > 0 || $ref)
    {
        $result = $object->fetch($id, $ref);
        if ($result < 0)
        {
            dol_print_error($db);
        }
    }

    $num = $db->num_rows($resql);

    $arrayofselected = is_array($toselect) ? $toselect : array();


    $i = 0;
    $help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
    if ($msid) $texte = $langs->trans('StockMovementForId', $msid);
	else
	{
		$texte = $langs->trans("ListOfStockMovements");
		if ($id) $texte .= ' ('.$langs->trans("ForThisWarehouse").')';
	}
    llxHeader("", $texte, $help_url);

    /*
     * Show tab only if we ask a particular warehouse
     */
    if ($object->id > 0)
    {
        $head = stock_prepare_head($object);

        dol_fiche_head($head, 'movements', $langs->trans("Warehouse"), -1, 'stock');


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

        print '<tr>';

        // Description
        print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>'.dol_htmlentitiesbr($object->description).'</td></tr>';

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

        print '</table>';

        print '</div>';
        print '<div class="fichehalfright">';
        print '<div class="ficheaddleft">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border centpercent">';

        // Value
        if ($user->rights->stock->show_pmp) {
            print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
            print price((empty($calcproducts['value']) ? '0' : price2num($calcproducts['value'], 'MT')), 0, $langs, 0, -1, -1, $conf->currency);
            print "</td></tr>";
        }

        // Last movement
        $sql = "SELECT MAX(m.datem) as datem";
        $sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
        $sql .= " WHERE m.fk_entrepot = '".$object->id."'";
        $resqlbis = $db->query($sql);
        if ($resqlbis)
        {
            $objbis = $db->fetch_object($resqlbis);
            $lastmovementdate = $db->jdate($objbis->datem);
        }
        else
        {
            dol_print_error($db);
        }

        print '<tr><td>'.$langs->trans("LastMovement").'</td><td>';
        if ($lastmovementdate)
        {
            print dol_print_date($lastmovementdate, 'dayhour', 'tzuserrel');
        }
        else
        {
            print $langs->trans("None");
        }
        print "</td></tr>";

        print "</table>";

        print '</div>';
        print '</div>';
        print '</div>';

        print '<div class="clearboth"></div>';

        dol_fiche_end();
    }


	/*
	 * Correct stock
	 */
	if ($action == "correction")
	{
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stockcorrection.tpl.php';
		print '<br>';
	}

	/*
	 * Transfer of units
	 */
	if ($action == "transfert")
	{
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stocktransfer.tpl.php';
		print '<br>';
	}


    /* ************************************************************************** */
    /*                                                                            */
    /* Barre d'action                                                             */
    /*                                                                            */
    /* ************************************************************************** */

    if ((empty($action) || $action == 'list') && $id > 0)
    {
        print "<div class=\"tabsAction\">\n";

        if ($user->rights->stock->mouvement->creer)
        {
            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=correction">'.$langs->trans("CorrectStock").'</a>';
        }

        if ($user->rights->stock->mouvement->creer)
        {
            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert">'.$langs->trans("TransferStock").'</a>';
        }

        print '</div><br>';
    }

    $param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
    if ($id > 0)                 $param .= '&id='.urlencode($id);
    if ($search_movement)        $param .= '&search_movement='.urlencode($search_movement);
    if ($search_inventorycode)   $param .= '&search_inventorycode='.urlencode($search_inventorycode);
    if ($search_type_mouvement)	 $param .= '&search_type_mouvement='.urlencode($search_type_mouvement);
    if ($search_product_ref)     $param .= '&search_product_ref='.urlencode($search_product_ref);
    if ($search_product_barcode)     $param .= '&search_product_barcode='.urlencode($search_product_barcode);
    if ($search_product)         $param .= '&search_product='.urlencode($search_product);
    if ($search_batch)           $param .= '&search_batch='.urlencode($search_batch);
    if ($search_warehouse > 0)   $param .= '&search_warehouse='.urlencode($search_warehouse);
    if ($search_user)            $param .= '&search_user='.urlencode($search_user);
    if ($idproduct > 0)          $param .= '&idproduct='.urlencode($idproduct);
    if ($sql)                    $param .= '&sql='.urlencode($sql);
    // Add $param from extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
	//    'presend'=>$langs->trans("SendByMail"),
	//    'builddoc'=>$langs->trans("PDFMerge"),
	);
	// By default, we should never accept deletion of stock movement.
	if (! empty($conf->global->STOCK_ALLOW_DELETE_OF_MOVEMENT) && $permissiontodelete && $user->rights->stock->mouvement->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

    // Mandar la sql en caso de que se quiera exportar el listado
    print '<form method="POST" id="FormularioExportacion" action="export_csv.php">';
    print '<input type="hidden" id="limit" name="limit" value="'.$limit.'"/>';
    print '<input type="hidden" id="sql" name="sql" value="'.$sql.'"/>';
    print '</form>';

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="type" value="'.$type.'">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
    print '<input type="hidden" name="idproduct" value="'.$idproduct.'">';
    if ($id > 0) print '<input type="hidden" name="id" value="'.$id.'">';

    $newcardbutton = '';
    $newcardbutton .= dolGetButtonTitle($langs->trans('Exportar Listado'), '', 'fa fa-plus-circle', '', "exportar" , 1);


    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, $newcardbutton, '', $limit);
    
    print '<script language="javascript">
    $(document).ready(function() {
        $("#exportar").on("click",function() {
            $("#FormularioExportacion").submit();
        });
    });
    </script>
	';
    // Add code for pre mass action (confirmation or email presend form)
    $topicmail = "SendStockMovement";
    $modelmail = "movementstock";
    $objecttmp = new MouvementStock($db);
    $trackid = 'mov'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
    {
        foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
        print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
    }

    $moreforfilter = '';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if (!empty($moreforfilter))
	{
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
	    print $moreforfilter;
	    print '</div>';
	}

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

    // Fields title search
    print '<tr class="liste_titre_filter">';
    if (!empty($arrayfields['m.rowid']['checked']))
    {
	    // Ref
	    print '<td class="liste_titre left">';
	    print '<input class="flat maxwidth25" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	    print '</td>';
    }
    if (!empty($arrayfields['m.datem']['checked']))
    {
    	// Date
    	print '<td class="liste_titre nowraponall">';
	    print '<input class="flat" type="text" size="2" maxlength="2" placeholder="'.dol_escape_htmltag($langs->trans("Month")).'" name="month" value="'.$month.'">';
    	if (empty($conf->productbatch->enabled)) print '&nbsp;';
	    //else print '<br>';
	    $syear = $year ? $year : -1;
	    print '<input class="flat maxwidth50" type="text" maxlength="4" placeholder="'.dol_escape_htmltag($langs->trans("Year")).'" name="year" value="'.($syear > 0 ? $syear : '').'">';
	    //print $formother->selectyear($syear,'year',1, 20, 5);
	    print '</td>';
    }
    if (!empty($arrayfields['p.barcode']['checked']))
    {
    }
    // Product barcode
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75" type="text" name="search_product_barcode" value="'.dol_escape_htmltag($search_product_barcode).'">';
	print '</td>';
    if (!empty($arrayfields['p.ref']['checked']))
    {
	    // Product Ref
	    print '<td class="liste_titre left">';
	    print '<input class="flat maxwidth75" type="text" name="search_product_ref" value="'.dol_escape_htmltag($idproduct ? $product->ref : $search_product_ref).'">';
	    print '</td>';
    }
    if (!empty($arrayfields['p.label']['checked']))
    {
	    // Product label
	    print '<td class="liste_titre left">';
	    print '<input class="flat maxwidth100" type="text" name="search_product" value="'.dol_escape_htmltag($idproduct ? $product->label : $search_product).'">';
	    print '</td>';
    }
    // Batch
    if (!empty($arrayfields['m.batch']['checked']))
    {
    	print '<td class="liste_titre center"><input class="flat maxwidth75" type="text" name="search_batch" value="'.dol_escape_htmltag($search_batch).'"></td>';
	}
    if (!empty($arrayfields['pl.eatby']['checked']))
    {
	    print '<td class="liste_titre left">';
	    print '</td>';
    }
    if (!empty($arrayfields['pl.sellby']['checked']))
    {
	    print '<td class="liste_titre left">';
	    print '</td>';
    }
    // Warehouse
    if (!empty($arrayfields['e.ref']['checked']))
    {
        print '<td class="liste_titre maxwidthonsmartphone left">';
        //print '<input class="flat" type="text" size="8" name="search_warehouse" value="'.($search_warehouse).'">';
        print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, $disableWarehouse, 0, '', 0, 0, null, 'maxwidth200');
        print '</td>';
    }
    if (!empty($arrayfields['m.fk_user_author']['checked']))
    {
	    // Author
	    print '<td class="liste_titre left">';
	    print '<input class="flat" type="text" size="6" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
	    print '</td>';
    }
    if (!empty($arrayfields['m.inventorycode']['checked']))
    {
	    // Inventory code
	    print '<td class="liste_titre left">';
	    print '<input class="flat" type="text" size="4" name="search_inventorycode" value="'.dol_escape_htmltag($search_inventorycode).'">';
	    print '</td>';
    }
    if (!empty($arrayfields['m.label']['checked']))
    {
	    //Label of movement
        $labels = array();
        // Array usado para comparar
        $add = array();
        $i = 0;
	    print '<td class="liste_titre left">';

        // Array de etiquetas para agregar lote
        $query = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."c_movements_add WHERE active = '1' ORDER BY label";
		$requery = $db->query($query);
		while ($obj = $db->fetch_object($requery))
		{
			$labels[$i] = $obj->label;
            $add[$i] = $obj->label;
            $i++;
		}

        // Array de etiquetas para eliminar lote              
		$query2 = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."c_movements_delete WHERE active = '1' ORDER BY label";
		$requery2 = $db->query($query2);
		while ($obj2 = $db->fetch_object($requery2))
		{
            $exist = 0;
            // Comparar si la etiqueta existe en el arreglo anterior y no repetirlas
            for ($x=0; $x < count($add); $x++) { 
                if($add[$x] == $obj2->label)  $exist++;
            }
            if($exist == 0){
                $labels[$i] = $obj2->label;
                $i ++;
            }
		}

        // Array de etiquetas para transferir lote              
		$query3 = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."c_batch_transfer WHERE active = '1' ORDER BY label";
		$requery3 = $db->query($query3);
		while ($obj3 = $db->fetch_object($requery3))
		{
            $labels[$i] = $obj3->label;
            $i ++;
		}

		print '<select class="flat" name="search_movement" id="search_movement" style="width:180px;">';   
        print '<option value="'.dol_escape_htmltag($search_mouvement).'"></option>';            
		for ($x=0; $x < count($labels) ; $x++) { 
            print '<option value="'.$labels[$x].'">'.$labels[$x].'</option>';

        }
        // Etiquetas no incluidas en diccionarios
        print '<option value="correcion de stock">Corrección de stock</option>';
        print '<option value="factura validada">Factura validada</option>';
        print '<option value="regresar factura">Regresar factura al estado de borrador</option>';
        print '<option value="Recepción del pedido a proveedor">Recepción del pedido a proveedor</option>';
		print '</select>';
	    print '</td>';
    }
	if (!empty($arrayfields['m.type_mouvement']['checked']))
    {
	    // Type of movement
	    print '<td class="liste_titre center">';
	    //print '<input class="flat" type="text" size="3" name="search_type_mouvement" value="'.dol_escape_htmltag($search_type_mouvement).'">';
		print '<select id="search_type_mouvement" name="search_type_mouvement" class="maxwidth150">';
		print '<option value="" '.(($search_type_mouvement == "") ? 'selected="selected"' : '').'></option>';
		print '<option value="0" '.(($search_type_mouvement == "0") ? 'selected="selected"' : '').'>'.$langs->trans('StockIncreaseAfterCorrectTransfer').'</option>';
		print '<option value="1" '.(($search_type_mouvement == "1") ? 'selected="selected"' : '').'>'.$langs->trans('StockDecreaseAfterCorrectTransfer').'</option>';
		print '<option value="2" '.(($search_type_mouvement == "2") ? 'selected="selected"' : '').'>'.$langs->trans('StockDecrease').'</option>';
		print '<option value="3" '.(($search_type_mouvement == "3") ? 'selected="selected"' : '').'>'.$langs->trans('StockIncrease').'</option>';
		print '</select>';
		print ajax_combobox('search_type_mouvement');
		// TODO: add new function $formentrepot->selectTypeOfMovement(...) like
		// print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
	    print '</td>';
    }
    if (!empty($arrayfields['origin']['checked']))
    {
	    // Origin of movement
	    print '<td class="liste_titre left">';
	    print '&nbsp; ';
	    print '</td>';
    }
    if (!empty($arrayfields['m.value']['checked']))
    {
	    // Qty
	    print '<td class="liste_titre right">';
	    print '<input class="flat" type="text" size="4" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
        print '</td>';
        
    }
    if (!empty($arrayfields['m.price']['checked']))
    {
    	// Price
    	print '<td class="liste_titre" align="left">';
    	print '&nbsp; ';
    	print '</td>';
    }
    if (!empty($arrayfields['subtotal']['checked']))
    {
    	// Subtotal
    	print '<td class="liste_titre left">';
    	print '&nbsp; ';
    	print '</td>';
    }
    if (!empty($arrayfields['tva']['checked']))
    {
    	// IVA
    	print '<td class="liste_titre left">';
    	print '&nbsp; ';
    	print '</td>';
    }
    if (!empty($arrayfields['total']['checked']))
    {
    	// Total
    	print '<td class="liste_titre" align="left">';
    	print '&nbsp; ';
    	print '</td>';
    }
    if (!empty($arrayfields['m.fk_projet']['checked']))
    {
    	// fk_project
    	print '<td class="liste_titre" align="left">';
    	print '&nbsp; ';
    	print '</td>';
    }


    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['m.datec']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['m.tms']['checked']))
	{
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
    if (!empty($arrayfields['m.rowid']['checked'])) {
        print_liste_field_titre($arrayfields['m.rowid']['label'], $_SERVER["PHP_SELF"], 'm.rowid', '', $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['m.datem']['checked'])) {
        print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER["PHP_SELF"], 'm.datem', '', $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.barcode']['checked'])) {
    }
    print_liste_field_titre($arrayfields['p.barcode']['label'], $_SERVER["PHP_SELF"], 'p.barcode', '', $param, '', $sortfield, $sortorder);
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
    if (!empty($arrayfields['e.ref']['checked'])) {
        // We are on a specific warehouse card, no filter on other should be possible
        print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['m.fk_user_author']['checked'])) {
        print_liste_field_titre($arrayfields['m.fk_user_author']['label'], $_SERVER["PHP_SELF"], "m.fk_user_author", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['m.inventorycode']['checked'])) {
        print_liste_field_titre($arrayfields['m.inventorycode']['label'], $_SERVER["PHP_SELF"], "m.inventorycode", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['m.label']['checked'])) {
        print_liste_field_titre($arrayfields['m.label']['label'], $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['m.type_mouvement']['checked'])) {
        print_liste_field_titre($arrayfields['m.type_mouvement']['label'], $_SERVER["PHP_SELF"], "m.type_mouvement", "", $param, '', $sortfield, $sortorder, 'center ');
    }
    if (!empty($arrayfields['origin']['checked'])) {
        print_liste_field_titre($arrayfields['origin']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['m.value']['checked'])) {
        print_liste_field_titre($arrayfields['m.value']['label'], $_SERVER["PHP_SELF"], "m.value", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['m.price']['checked'])) {
        print_liste_field_titre($arrayfields['m.price']['label'], $_SERVER["PHP_SELF"], "m.price", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['subtotal']['checked']))
		print_liste_field_titre($arrayfields['subtotal']['label'], $_SERVER["PHP_SELF"], "subtotal", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['tva']['checked']))
		print_liste_field_titre($arrayfields['tva']['label'], $_SERVER["PHP_SELF"], "tva", "", $param, '', $sortfield, $sortorder, 'right ');
    if (!empty($arrayfields['total']['checked'])) {
        print_liste_field_titre($arrayfields['total']['label'], $_SERVER["PHP_SELF"], "total", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['m.fk_projet']['checked'])) {
        print_liste_field_titre($arrayfields['m.fk_projet']['label'], $_SERVER["PHP_SELF"], "m.fk_projet", "", $param, 'align="right"', $sortfield, $sortorder);
    }

    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
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

    $i = 0;
    $totalarray = array();
    $stk[1] = false;
    $stk[2] = false;
    while ($i < min($num, $limit))
    {
        $objp = $db->fetch_object($resql);

        $userstatic->id = $objp->fk_user_author;
        $userstatic->login = $objp->login;
        $userstatic->lastname = $objp->lastname;
        $userstatic->firstname = $objp->firstname;
        $userstatic->photo = $objp->photo;

        $productstatic->id = $objp->rowid;
        $productstatic->ref = $objp->product_ref;
        $productstatic->label = $objp->produit;
        $productstatic->type = $objp->type;
        $productstatic->entity = $objp->entity;
        $productstatic->status = $objp->tosell;
        $productstatic->status_buy = $objp->tobuy;
        $productstatic->status_batch = $objp->tobatch;
        $productstatic->barcode = $objp->barcode;

        $productlot->id = $objp->lotid;
        $productlot->batch = $objp->batch;
        $productlot->eatby = $objp->eatby;
        $productlot->sellby = $objp->sellby;

        $warehousestatic->id = $objp->entrepot_id;
        $warehousestatic->ref = $objp->warehouse_ref;
        $warehousestatic->libelle = $objp->warehouse_ref; // deprecated
        $warehousestatic->label = $objp->warehouse_ref;
        $warehousestatic->lieu = $objp->lieu;
        $warehousestatic->fk_parent = $objp->fk_parent;
        $warehousestatic->statut = $objp->statut;

        $arrayofuniqueproduct[$objp->rowid] = $objp->produit;
		if (!empty($objp->fk_origin)) {
			$origin = $movement->get_origin($objp->fk_origin, $objp->origintype);
		} else {
			$origin = '';
		}
        print '<script>
            function deleteApartado(idproduct, qty, batch, sellby, eatby, mid){
                if (confirm("¿Está seguro de que desea liberar este apartado?")){
                    location.href = "../../takepos/invoice.php?action=liberarapartado&contextpage=poslist&place='.$place.'&idproduct="+idproduct+"&qty="+qty+"&batch="+batch+"&sellby="+sellby+"&eatby="+eatby+"&mid="+mid;
                }
            }
        </script>';

        print '<tr class="oddeven"';
        if ($contextpage == 'poslist')
        {
            $place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Bar or Restaurant
            print ' onclick="deleteApartado('.$productstatic->id.', '.$objp->qty.', \''.$productlot->batch.'\', \''.$productlot->sellby.'\', \''.$productlot->eatby.'\', '.$objp->mid.')"';
        }
        print '>';
        // Id movement
        if (!empty($arrayfields['m.rowid']['checked']))
        {
        	print '<td>'.$objp->mid.'</td>'; // This is primary not movement id
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.datem']['checked']))
        {
	        // Date
	        print '<td class="nowraponall">'.dol_print_date($db->jdate($objp->datem), 'dayhour').'</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        print '<td class="nowraponall">';
	    print $productstatic->barcode;
        print "</td>\n";
        if (!$i) $totalarray['nbfield']++;
        if (!empty($arrayfields['p.ref']['checked']))
        {
	        // Product ref
	        print '<td class="nowraponall">';
	        print $productstatic->getNomUrl(1, 'stock', 16);
	        print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['p.label']['checked']))
        {
	        // Product label
	        print '<td>';
	        /*$productstatic->id=$objp->rowid;
	        $productstatic->ref=$objp->produit;
	        $productstatic->type=$objp->type;
	        print $productstatic->getNomUrl(1,'',16);*/
	        print $productstatic->label;
	        print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.batch']['checked']))
        {
	    	print '<td class="center nowraponall">';
	    	if ($productlot->id > 0) print $productlot->getNomUrl(1);
	    	else print $productlot->batch; // the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
	    	print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['pl.eatby']['checked']))
        {
        	print '<td class="center">'.dol_print_date($objp->eatby, 'day').'</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['pl.sellby']['checked']))
        {
        	print '<td class="center">'.dol_print_date($objp->sellby, 'day').'</td>';
            if (!$i) $totalarray['nbfield']++;
		}
        // Warehouse
        if (!empty($arrayfields['e.ref']['checked']))
		{
            print '<td>';
            print $warehousestatic->getNomUrl(1);
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
		}
        // Author
        if (!empty($arrayfields['m.fk_user_author']['checked']))
        {
	        print '<td class="tdoverflowmax100">';
	        print $userstatic->getNomUrl(-1);
	        print "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.inventorycode']['checked']))
        {
	        // Inventory code
	        print '<td>'.'<a href="'
								.DOL_URL_ROOT.'/product/stock/movement_card.php'
								.'?id='.$objp->entrepot_id
								.'&amp;search_inventorycode='.$objp->inventorycode
							    .'&amp;search_type_mouvement='.$objp->type_mouvement
						.'">'
							.$objp->inventorycode
						.'</a>'
					.'</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.label']['checked']))
        {
            // Label of movement
        	print '<td class="tdoverflowmax100aaa">'.$objp->label.'</td>';
            if (!$i) $totalarray['nbfield']++;
        }
		if (!empty($arrayfields['m.type_mouvement']['checked']))
        {
            // Type of movement
            switch ($objp->type_mouvement) {
                case "0":
                    print '<td class="center">'.$langs->trans('StockIncreaseAfterCorrectTransfer').'</td>';
                    break;
                case "1":
                    print '<td class="center">'.$langs->trans('StockDecreaseAfterCorrectTransfer').'</td>';
                    break;
                case "2":
                    print '<td class="center">'.$langs->trans('StockDecrease').'</td>';
                    break;
                case "3":
                    print '<td class="center">'.$langs->trans('StockIncrease').'</td>';
                    break;
            }
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['origin']['checked']))
        {
        	// Origin of movement
        	print '<td class="nowraponall">'.$origin.'</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.value']['checked']))
        {
	        // Qty
	        print '<td class="right">';
            if ($contextpage == 'poslist'){
                print $objp->qty*-1;
            } else{
                if ($objp->qty > 0) print '+';
                print $objp->qty;
            }
            print '</td>';

            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'm.value';
            $totalarray['val']['m.value'] += $objp->qty;
	        
        }
        if (!empty($arrayfields['m.price']['checked']))
        {
        	// Price
        	print '<td class="right">';
        	if ($objp->price != 0) print price($objp->price);
        	print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['subtotal']['checked']))
        {
        	// Subtotal
        	print '<td class="right">';
        	print price($objp->subtotal);
        	print '</td>';
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'subtotal';
			$totalarray['val']['subtotal'] += $objp->subtotal;
        }
        if (!empty($arrayfields['tva']['checked']))
        {
        	// IVA
        	print '<td class="right">';
        	print price($objp->tva);
        	print '</td>';
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'tva';
			$totalarray['val']['tva'] += $objp->tva;
        }
        if (!empty($arrayfields['total']['checked']))
        {
        	// Total
        	print '<td class="right">';
        	if ($objp->total != 0) print price($objp->total);
        	print '</td>';
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'total';
			$totalarray['val']['total'] += abs($objp->total);
        }
        if (!empty($arrayfields['m.fk_projet']['checked']))
        {
        	// fk_project
        	print '<td align="right">';
        	if ($objp->fk_project != 0) print $movement->get_origin($objp->fk_project, 'project');
        	print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Action column
        if ($contextpage != 'poslist'){
            print '<td class="nowrap center">';
            //Imprimir PDF
            if($objp->inventorycode != null && $contextpage != 'poslist') {
                print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&action=printpdfcorrection&codetosearch=' . $objp->inventorycode . '" title="Imprimir comprobante"><i class="far fa-file-pdf fa-lg"></i></a>';
            }
            if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected = 0;
                if (in_array($objp->mid, $arrayofselected)) $selected = 1;
                print '<input id="cb'.$objp->mid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$objp->mid.'"'.($selected ? ' checked="checked"' : '').'>';
            }
            print '</td>';
        } else {
            print '<td class="nowrap center">';
            print '</td>';
        }
        if (!$i) $totalarray['nbfield']++;

        print "</tr>\n";
        $i++;
    }
     // Show total line
     print '<tr class="liste_total">';
     $i=0;
     while ($i < $totalarray['nbfield'])
     {
         $i++;
         
         if (! empty($totalarray['pos'][$i])){  
            if($totalarray['pos'][$i] == 'm.value')     print '<td class="right">'.$totalarray['val'][$totalarray['pos'][$i]].'</td>';
            else print '<td class="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
        }
         else
         {
             if ($i == 1)
             {
                 if ($num < $limit) print '<td class="left">'.$langs->trans("Total").'</td>';
                 else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
             }
             else print '<td></td>';
         }
     }
     print '</tr>';
    $db->free($resql);

    print "</table>";
    print '</div>';

    print "</form>";


    // Add number of product when there is a filter on period
    if (count($arrayofuniqueproduct) == 1 && is_numeric($year))
    {
        print "<br>";

        $productidselected = 0;
    	foreach ($arrayofuniqueproduct as $key => $val)
    	{
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
    	print ': '.$balancebefore;
    	print "<br>\n";
    	//print '</td></tr>';
    	//print '<tr class="total"><td class="liste_total">';
    	print $langs->trans("NbOfProductAfterPeriod", $productlabelselected, dol_print_date($dateafter, 'day', 'gmt'));
    	//print '</td>';
    	//print '<td class="liste_total right" colspan="6">';
    	print ': '.$balanceafter;
    	print "<br>\n";
    	//print '</td></tr>';
    }
}
else
{
    dol_print_error($db);
}


// End of page
llxFooter();
$db->close();
