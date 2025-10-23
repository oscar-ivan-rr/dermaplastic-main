<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2019	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Adolfo segura           <adolfo.segura@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet		    <fmarcet@2byte.es>
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
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (!empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies', 'errors'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$subaction = GETPOST('subaction', 'alpha', 2);

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST("search_ref", 'alpha');
$search_barcode = GETPOST("search_barcode", 'alpha');
$search_label = GETPOST("search_label", 'nohtml');
$search_type = GETPOST("search_type", 'int');
$search_sale = GETPOST("search_sale", 'int');
$search_vatrate = GETPOST("search_vatrate", 'alpha');
$searchCategoryProductOperator = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
$search_tosell = GETPOST("search_tosell", 'int');
$search_gain =GETPOST('search_gain');
$search_tobuy = GETPOST("search_tobuy", 'int');
$search_currency = GETPOST("search_currency", 'alpha');
$fourn_id = GETPOST("fourn_id", 'int');
$catid = GETPOST('catid', 'int');
$search_tobatch = GETPOST("search_tobatch", 'int');
$search_accountancy_code_sell = GETPOST("search_accountancy_code_sell", 'alpha');
$search_accountancy_code_sell_intra = GETPOST("search_accountancy_code_sell_intra", 'alpha');
$search_accountancy_code_sell_export = GETPOST("search_accountancy_code_sell_export", 'alpha');
$search_accountancy_code_buy = GETPOST("search_accountancy_code_buy", 'alpha');

//variable checkbox de alerta de lote próximo a caducar
$expiration_alert = GETPOST('expiration_alert');

//variables de busqueda de ultima fecha de modificacion stock
$search_dateStockyear = GETPOST("search_dateStockyear", "int");
$search_dateStockmonth = GETPOST("search_dateStockmonth", "int");
$search_dateStockday = GETPOST("search_dateStockday", "int");
$date_dateStock = dol_mktime(
	0,
	0,
	0,
	$search_dateStockmonth,
	$search_dateStockday,
	$search_dateStockyear
);
//variable checkbox de alerta
$month_alert = GETPOST('month_alert');

$optioncss = GETPOST('optioncss', 'alpha');
$type = GETPOST("type", "int");
//Para descuento masivo
$discount_percent = GETPOST("discount_percent", "int");
$product_select = GETPOST("product_select", "string");
//Gain
$con = utf8_encode(file_get_contents("sell_gain.json"));
$gain = json_decode($con, true);
//Show/hide child products
if (!empty($conf->variants->enabled) && !empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
	$show_childproducts = GETPOST('search_show_childproducts');
} else {
	$show_childproducts = '';
}

$diroutputmassaction = $conf->product->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = (GETPOST("page", 'int') ?GETPOST("page", 'int') : 0);
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "ASC";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'productservicelist';
if ((string) $type == '1') { $contextpage = 'servicelist'; if ($search_type == '') $search_type = '1'; }
if ((string) $type == '0') { $contextpage = 'productlist'; if ($search_type == '') $search_type = '0'; }

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new Product($db);
$hookmanager->initHooks(array('productservicelist'));
$extrafields = new ExtraFields($db);
$form = new Form($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('product');
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if($action == 'modify_gain' && $user->rights->produit->modify_gain){
    $gain = GETPOST('gain','float');
    $json = array("gain" => $gain);
    file_put_contents('sell_gain.json', json_encode($json));
    $action = 'list';
}
if (empty($action)) $action = 'list';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'list', $canvas);
}

// Security check
if ($search_type == '0') $result = restrictedArea($user, 'produit', '', '', '', '', '', $objcanvas);
elseif ($search_type == '1') $result = restrictedArea($user, 'service', '', '', '', '', '', $objcanvas);
else $result = restrictedArea($user, 'produit|service', '', '', '', '', '', $objcanvas);

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION))
{
	$virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

$currentDate = date('Y-m-d');
$refdate = strtotime('+3 months', strtotime($currentDate));
$refdate = date('Y-m-d' , $refdate);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>"Ref",
	'p.old_ref'=>"Ref. Vieja",
	'pfp.ref_fourn'=>"RefSupplier",
	'p.label'=>"ProductLabel",
	'p.description'=>"Description",
	"p.note"=>"Note",

);
// multilang
if (!empty($conf->global->MAIN_MULTILANGS))
{
	$fieldstosearchall['pl.label'] = 'ProductLabelTranslated';
	$fieldstosearchall['pl.description'] = 'ProductDescriptionTranslated';
	$fieldstosearchall['pl.note'] = 'ProductNoteTranslated';
}
if (!empty($conf->barcode->enabled)) {
	$fieldstosearchall['p.barcode'] = 'Gencod';
    $fieldstosearchall['pfp.barcode'] = 'GencodBuyPrice';
}
// Personalized search criterias. Example: $conf->global->PRODUCT_QUICKSEARCH_ON_FIELDS = 'p.ref=ProductRef;p.label=ProductLabel'
if (!empty($conf->global->PRODUCT_QUICKSEARCH_ON_FIELDS)) $fieldstosearchall = dolExplodeIntoArray($conf->global->PRODUCT_QUICKSEARCH_ON_FIELDS);

if (empty($conf->global->PRODUIT_MULTIPRICES))
{
	$titlesellprice = $langs->trans("SellingPrice");
	if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES))
	{
		$titlesellprice = $form->textwithpicto($langs->trans("SellingPrice"), $langs->trans("DefaultPriceRealPriceMayDependOnCustomer"));
	}
}

$isInEEC = isInEEC($mysoc);

// Definition of fields for lists
$arrayfields = array(
	'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	//'pfp.ref_fourn'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
	'p.old_ref'=>array('label'=>$langs->trans("Referencia Vieja"), 'checked'=>1, 'enabled'=>(! empty($conf->product->enabled))),
	'p.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
    'categoria'=>array('label'=>$langs->trans("Categoria"), 'checked'=>1),
	'p.fk_product_type'=>array('label'=>$langs->trans("Type"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && !empty($conf->service->enabled))),
	'p.barcode'=>array('label'=>$langs->trans("Gencod"), 'checked'=>1, 'enabled'=>(!empty($conf->barcode->enabled))),
	'p.duration'=>array('label'=>$langs->trans("Duration"), 'checked'=>($contextpage != 'productlist'), 'enabled'=>(!empty($conf->service->enabled) && (string) $type == '1')),
    'p.weight'=>array('label'=>$langs->trans("Weight"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled))),
    'p.length'=>array('label'=>$langs->trans("Length"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && !empty($conf->global->PRODUCT_DISABLE_SIZE))),
    'p.surface'=>array('label'=>$langs->trans("Surface"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && !empty($conf->global->PRODUCT_DISABLE_SURFACE))),
    'p.volume'=>array('label'=>$langs->trans("Volume"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && !empty($conf->global->PRODUCT_DISABLE_VOLUME))),
    'p.sellprice'=>array('label'=>$langs->trans("SellingPriceWithTTC"), 'checked'=>1, 'enabled'=>empty($conf->global->PRODUIT_MULTIPRICES)),
	'p.minbuyprice'=>array('label'=>$langs->trans("BuyingPriceMinShort"), 'checked'=>1, 'enabled'=>(!empty($user->rights->fournisseur->lire))),
	'p.gain'=>array('label'=>$langs->trans("Ganancia x PC"), 'checked'=>1, 'enabled'=>true),
	'p.numbuyprice'=>array('label'=>$langs->trans("BuyingPriceNumShort"), 'checked'=>0, 'enabled'=>(!empty($user->rights->fournisseur->lire))),
    'numpricesCurrency'=>array('label'=>$langs->trans("Moneda"), 'checked'=>1, 'enabled'=>($conf->global->PRODUIT_FOURN_MULTICURRENCY)),
    'p.tva_tx'=>array('label'=>$langs->trans("VATRate"), 'checked'=>0, 'enabled'=>(!empty($user->rights->fournisseur->lire))),
    'p.pmp'=>array('label'=>$langs->trans("PMPValueShort"), 'checked'=>0, 'enabled'=>(!empty($user->rights->fournisseur->lire))),
    'p.ueps'=>array('label'=>$langs->trans("UEPSValueShort"), 'checked'=>1, 'enabled'=>(!empty($user->rights->fournisseur->lire))), // UEPS Implementation
	'p.seuil_stock_alerte'=>array('label'=>$langs->trans("StockLimit"), 'checked'=>0, 'enabled'=>(!empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
	'p.desiredstock'=>array('label'=>$langs->trans("DesiredStock"), 'checked'=>1, 'enabled'=>(!empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
	'p.stock'=>array('label'=>$langs->trans("PhysicalStock"), 'checked'=>1, 'enabled'=>(!empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
	'stock_to_deliver'=>array('label'=>$langs->trans("StockToBeDelivered"), 'checked'=>1, 'enabled'=>(!empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service')),
	'stock_virtual'=>array('label'=>$langs->trans("VirtualStock"), 'checked'=>1, 'enabled'=>(!empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service' && $virtualdiffersfromphysical)),
	'sm.datem'=>array('label'=>$langs->trans("LastInventoryAjust"), 'checked'=>1, 'enabled'=>(!empty($user->rights->stock->date_last_mouvement_stock))),
	'p.tobatch'=>array('label'=>$langs->trans("ManageLotSerial"), 'checked'=>0, 'enabled'=>(!empty($conf->productbatch->enabled))),
	'p.accountancy_code_sell'=>array('label'=>$langs->trans("ProductAccountancySellCode"), 'checked'=>0, 'position'=>400),
    'p.accountancy_code_sell_intra'=>array('label'=>$langs->trans("ProductAccountancySellIntraCode"), 'checked'=>0, 'enabled'=>$isInEEC, 'position'=>401),
    'p.accountancy_code_sell_export'=>array('label'=>$langs->trans("ProductAccountancySellExportCode"), 'checked'=>0, 'position'=>402),
    'p.accountancy_code_buy'=>array('label'=>$langs->trans("ProductAccountancyBuyCode"), 'checked'=>0, 'position'=>403),
	'pl.eatby'=>array('label'=>$langs->trans("A caducar"), 'checked'=>1),
	'margen'=> array('label' => $langs->trans("% de Margen"), 'checked' => 1),
	'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'p.tosell'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Sell").')', 'checked'=>1, 'position'=>1000),
	'p.tobuy'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Buy").')', 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']))
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



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
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$sall = "";
		$search_ref = "";
		$search_label = "";
		$search_barcode = "";
        $searchCategoryProductOperator = 0;
        $searchCategoryProductList = array();
		$search_tosell = "";
		$search_gain = "";
		$search_tobuy = "";
		$search_vatrate = "";
		$search_tobatch = '';
		$expiration_alert = '';
		//$search_type='';						// There is 2 types of list: a list of product and a list of services. No list with both. So when we clear search criteria, we must keep the filter on type.

		$show_childproducts = '';
		$search_accountancy_code_sell = '';
		$search_accountancy_code_sell_intra = '';
		$search_accountancy_code_sell_export = '';
		$search_accountancy_code_buy = '';
		$search_array_options = array();
		$search_currency = '';

		$search_dateStockyear = "";
		$search_dateStockmonth = "";
		$search_dateStockday = "";
		$date_dateStock = "";
		$month_alert = "";
	}

	// Mass actions
	$objectclass = 'Product';
	if ((string) $search_type == '1') { $objectlabel = 'Services'; }
	if ((string) $search_type == '0') { $objectlabel = 'Products'; }

	$permissiontoread = $user->rights->produit->lire;
	$permissiontodelete = $user->rights->produit->supprimer;
	$uploaddir = $conf->product->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if ($subaction == 'applyrate') {
	$rate = GETPOST('currency_rate', 'int', 2);

	// Prevent invalid rate
	if (!empty($rate)) {
		// Update prices
		$num = ProductFournisseur::setRateForProducts(1 / $rate);
		// Messages
		if ($num > 0) setEventMessage($langs->trans("PricesUpdated", $num));
		elseif ($num == 0) setEventMessage($langs->trans("NotFoundPrices", 'USD'), 'warnings');
		elseif ($num == -1) setEventMessage($db->lasterror(), 'errors');
		else setEventMessage("Módulo de multimoneda no habilitado.", 'errors');
	}
	else setEventMessage($langs->trans("ErrorFieldMustBeANumeric", "Tasa de conversión de moneda"), 'errors');
}
//Descuento masivo
if($action == 'apply_discount' && $user->rights->categorie->apply_massive_discount)
{
    $product_select = json_decode($product_select);
    if ($discount_percent != '') {
        $product_discount = new Product($db);
        $decimal = (1 - $discount_percent / 100);
        $success = 0;
        foreach ($product_select as $prod) {
            $product_discount->fetch($prod);
            if ($product_discount->setDiscount($decimal) > 0)
                $success++;
        }
        setEventMessage($langs->trans("RecordsSaved", $success), $success > 0 ? 'mesgs' : 'warnings');
        $action = 'list';
    }
    else {
        setEventMessage($langs->trans('DiscountRequired'), 'errors');
        $massaction = 'massive_discount';
        $arrayofselected = $product_select;
    }
}
//Aplicar margen de ganancia
if($action == 'apply_gain')
{
    $product_select = json_decode($product_select);
    if (GETPOST("gain") != '') {
        $product_gain = new ProductFournisseur($db);
        foreach ($product_select as $prod) {
            $product_gain->fetch($prod);
            $precios_compra = 'SELECT fk_soc FROM '.MAIN_DB_PREFIX.'product_fournisseur_price WHERE fk_product = '.$prod.' ORDER BY rowid DESC LIMIT 1';
            $res = $db->query($precios_compra);
            if($db->num_rows($res) > 0) {
                $fourn = $db->fetch_object($res);
                if ($product_gain->updatePriceGain($fourn->fk_soc, GETPOST("gain"), $user))
                    $success++;
            }
            else{
                setEventMessage($langs->trans('DontHaveFourn',$product_gain->ref), 'errors');
            }
        }
        setEventMessage($langs->trans("RecordsSaved", $success), $success > 0 ? 'mesgs' : 'warnings');
        $action = 'list';
    }
    else {
        setEventMessage($langs->trans('GainRequired'), 'errors');
        $massaction = 'apply_gain';
        $arrayofselected = $product_select;
    }
}
/*
 * View
 */

$htmlother = new FormOther($db);

$title = $langs->trans("ProductsAndServices");

if ($search_type != '' && $search_type != '-1')
{
	if ($search_type == 1)
	{
		$texte = $langs->trans("Services");
	}
	else
	{
		$texte = $langs->trans("Products");
	}
}
else
{
	$texte = $langs->trans("ProductsAndServices");
}

$sql = 'SELECT DISTINCT p.rowid, p.ref, p.old_ref, p.label, p.fk_product_type, p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type, p.entity,';
$sql .= ' p.fk_product_type, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
$sql .= ' p.tobatch, p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export, p.accountancy_code_buy,';
$sql .= ' p.datec as date_creation, p.tms as date_update, p.pmp, p.stock,p.gain,';
$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.surface, p.surface_units, p.volume, p.volume_units, p.width, p.width_units, p.height, p.height_units, count(pfp.rowid) as numpricesCurrency,';
$sql .= ' MIN(pfp.unitprice) as minsellprice';
if (!empty($user->rights->stock->date_last_mouvement_stock)) $sql .= ', sm.datem, psm.datem as penultimo ';
if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
	$sql .= ', pac.rowid prod_comb_id';
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

//Margen 
$sql .= ', IFNULL(((p.price - p.cost_price) / p.cost_price) * 100, 0) as margen ';

$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object)";
if (!empty($searchCategoryProductList) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
// multilang
if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang()."'";

if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac.fk_product_child = p.rowid";
}

if (!empty($user->rights->stock->date_last_mouvement_stock)){
	//Ultima fecha de modificacion stock
	$sql .= " LEFT JOIN llx_stock_mouvement sm ON sm.rowid = ( SELECT b.rowid FROM ".MAIN_DB_PREFIX."stock_mouvement AS b WHERE b.fk_product = p.rowid AND b.label LIKE '%Corrección de Stock por Ajuste de Inventario%' ORDER BY b.rowid DESC LIMIT 1)";
	//Penultima fecha de modificacion stock
	$sql .= " LEFT JOIN llx_stock_mouvement psm ON psm.rowid = ( SELECT b.rowid FROM ".MAIN_DB_PREFIX."stock_mouvement AS b WHERE b.fk_product = p.rowid  AND b.label LIKE '%Corrección de Stock por Ajuste de Inventario%' ORDER BY b.rowid DESC LIMIT 1,1)";
}

if ($expiration_alert) $sql .= " JOIN llx_product_lot pl ON p.rowid = pl.fk_product";

$sql .= ' WHERE p.entity = ('.getEntity('product').')';
if ($expiration_alert) $sql .= " AND pl.eatby < '".$refdate."'";
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($search_type) && $search_type != '-1')
{
	if ($search_type == 1) $sql .= " AND p.fk_product_type = 1";
	else $sql .= " AND p.fk_product_type <> 1";
}

if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
	$sql .= " AND pac.rowid IS NULL";
}

if ($search_ref)     $sql .= natural_search('p.ref', $search_ref);
if ($search_label)   $sql .= natural_search('p.label', $search_label);
if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);
if (isset($search_tosell) && dol_strlen($search_tosell) > 0 && $search_tosell != -1) $sql .= " AND p.tosell = ".$db->escape($search_tosell);
if (isset($search_tobuy) && dol_strlen($search_tobuy) > 0 && $search_tobuy != -1)   $sql .= " AND p.tobuy = ".$db->escape($search_tobuy);
if (isset($search_currency) && dol_strlen($search_currency) > 0 && $search_currency != '')   $sql .= " AND pfp.multicurrency_code = '".$db->escape($search_currency)."'";
if ($search_vatrate) $sql .= natural_search('p.tva_tx', $search_vatrate);
if (dol_strlen($canvas) > 0)                    $sql .= " AND p.canvas = '".$db->escape($canvas)."'";
if ($catid > 0)     $sql .= " AND cp.fk_categorie = ".$catid;
if ($catid == -2)   $sql .= " AND cp.fk_categorie IS NULL";
if($search_gain != null) $sql .= " AND p.gain=".$search_gain;
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
if ($fourn_id > 0)  $sql .= " AND pfp.fk_soc = ".$fourn_id;
if ($search_tobatch != '' && $search_tobatch >= 0)   $sql .= " AND p.tobatch = ".$db->escape($search_tobatch);
if ($search_accountancy_code_sell)        $sql .= natural_search('p.accountancy_code_sell', $search_accountancy_code_sell);
if ($search_accountancy_code_sell_intra)  $sql .= natural_search('p.accountancy_code_sell_intra', $search_accountancy_code_sell_intra);
if ($search_accountancy_code_sell_export) $sql .= natural_search('p.accountancy_code_sell_export', $search_accountancy_code_sell_export);
if ($search_accountancy_code_buy)         $sql .= natural_search('p.accountancy_code_buy', $search_accountancy_code_buy);

//Filtro ultima fecha modificacion stock
if ($date_dateStock) 				$sql .= dolSqlDateFilter("sm.datem", $search_dateStockday, $search_dateStockmonth, $search_dateStockyear);
//Filtro alerta fecha modificacioon stock mayor a 2 
if ($month_alert == 'late_mov_stock') 	$sql .= ' AND TIMESTAMPDIFF(MONTH, psm.datem, sm.datem) < 2 ';

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type,";
$sql .= " p.fk_product_type, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,";
$sql .= ' p.datec, p.tms, p.entity, p.tobatch, p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export, p.accountancy_code_buy, p.pmp, p.stock,';
$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.surface, p.surface_units, p.volume, p.volume_units, p.width, p.width_units, p.height, p.height_units';

if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
	$sql .= ', pac.rowid';
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
//if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
$sql .= $db->order($sortfield, $sortorder);

//At this point we could replace query to show only custom data
if ($action == 'mas_vendidos' || $action == 'menos_vendidos') {
    $sql = "SELECT DISTINCT
	p.rowid, p.ref, p.old_ref, p.label, p.fk_product_type,	p.barcode,	p.price,	p.tva_tx,	p.price_ttc,	p.price_base_type,
	p.entity,	p.fk_product_type,	p.duration,	p.tosell,	p.tobuy,	p.seuil_stock_alerte,	p.desiredstock,	p.tobatch,
	p.accountancy_code_sell,	p.accountancy_code_sell_intra,	p.accountancy_code_sell_export,	p.accountancy_code_buy,
	p.datec AS date_creation,	p.tms AS date_update,	p.pmp,	p.stock,	p.weight,	p.weight_units,	p.length,	p.length_units,
	p.surface,	p.surface_units,	p.volume,	p.volume_units,	p.width,	p.width_units,	p.height,	p.height_units 
FROM
	".MAIN_DB_PREFIX."product AS p,
	".MAIN_DB_PREFIX."pos_ticketdet	td,
	".MAIN_DB_PREFIX."pos_ticket t
WHERE
	td.fk_product = p.rowid AND
	td.fk_ticket = t.rowid AND
	t.fk_statut = 1 AND date_creation BETWEEN ( CURRENT_DATE ( ) - INTERVAL 1 MONTH ) AND CURRENT_DATE ( ) 
GROUP BY
		td.fk_product 
ORDER BY
	count( td.fk_product ) ";
    if ($action == 'mas_vendidos')
        $sql.= " DESC ";
    else // menos_vendidos
        $sql.= " ASC ";
}
if ($action == 'sin_stock'){
    $sql = "SELECT DISTINCT p.rowid, p.ref, p.old_ref, p.label, p.fk_product_type,	p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type,
	        p.entity, p.fk_product_type, p.duration, p.tosell, p.tobuy,	p.seuil_stock_alerte, p.desiredstock, p.tobatch,
	        p.accountancy_code_sell, p.accountancy_code_sell_intra,	p.accountancy_code_sell_export,	p.accountancy_code_buy,
	        p.datec AS date_creation, p.tms AS date_update,	p.pmp, p.stock,	p.weight, p.weight_units, p.length,	p.length_units,
	        p.surface, p.surface_units,	p.volume, p.volume_units, p.width, p.width_units, p.height,	p.height_units 
            FROM ".MAIN_DB_PREFIX."product AS p, ".MAIN_DB_PREFIX."product_stock ps WHERE ps.fk_product = p.rowid GROUP BY fk_product HAVING SUM(ps.reel) <= 0";
}

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;
$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/product/card.php?id='.$id);
		exit;
	}

	$helpurl = '';
	if ($search_type != '')
	{
		if ($search_type == 0)
		{
			$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
		}
		elseif ($search_type == 1)
		{
			$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
		}
	}

	llxHeader('', $title, $helpurl, '');

	// Displays product removal confirmation
	if (GETPOST('delprod')) {
		setEventMessages($langs->trans("ProductDeleted", GETPOST('delprod')), null, 'mesgs');
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);
    if ($searchCategoryProductOperator == 1) $param .= "&search_category_product_operator=".urlencode($searchCategoryProductOperator);
    foreach ($searchCategoryProductList as $searchCategoryProduct) {
        $param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
    }
	// Próximo a caducar
	if ($expiration_alert == 1) $param .= "&expiration_alert=".urlencode($expiration_alert);
	if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
	if ($search_ref_supplier) $param = "&search_ref_supplier=".urlencode($search_ref_supplier);
	if ($search_barcode) $param .= ($search_barcode ? "&search_barcode=".urlencode($search_barcode) : "");
	if ($search_label) $param .= "&search_label=".urlencode($search_label);
	if ($search_tosell != '') $param .= "&search_tosell=".urlencode($search_tosell);
	if ($search_gain != '') $param .= "&search_gain=".urlencode($search_gain);
	if ($search_tobuy != '') $param .= "&search_tobuy=".urlencode($search_tobuy);
	if ($search_currency != '') $param .= "&search_currency=".urlencode($search_currency);
    if ($search_vatrate) $sql .= natural_search('p.tva_tx', $search_vatrate);
	if ($fourn_id > 0) $param .= ($fourn_id ? "&fourn_id=".$fourn_id : "");
	//if ($seach_categ) $param.=($search_categ?"&search_categ=".urlencode($search_categ):"");
	if ($show_childproducts) $param .= ($show_childproducts ? "&search_show_childproducts=".urlencode($show_childproducts) : "");
	if ($type != '') $param .= '&type='.urlencode($type);
	if ($search_type != '') $param .= '&search_type='.urlencode($search_type);
	if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);
	if ($search_tobatch) $param = "&search_ref_supplier=".urlencode($search_ref_supplier);
	if ($search_accountancy_code_sell) $param = "&search_accountancy_code_sell=".urlencode($search_accountancy_code_sell);
	if ($search_accountancy_code_sell_intra) $param = "&search_accountancy_code_sell_intra=".urlencode($search_accountancy_code_sell_intra);
	if ($search_accountancy_code_sell_export) $param = "&search_accountancy_code_sell_export=".urlencode($search_accountancy_code_sell_export);
	if ($search_accountancy_code_buy) $param = "&search_accountancy_code_buy=".urlencode($search_accountancy_code_buy);

	if ($search_dateStockday)     	$param .= '&search_dateStockday='.urlencode($search_dateStockday);
	if ($search_dateStockmonth)   	$param .= '&search_dateStockmonth='.urlencode($search_dateStockmonth);
	if ($search_dateStockyear)    	$param .= '&search_dateStockyear='.urlencode($search_dateStockyear);
	if ($month_alert)            	$param .= "&month_alert=".urlencode($month_alert);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
	    //'builddoc'=>$langs->trans("PDFMerge"),
	    //'presend'=>$langs->trans("SendByMail"),
	);
	//Aplicar descuento masivo
    if($user->rights->categorie->apply_massive_discount && $type == Product::TYPE_PRODUCT)
        $arrayofmassactions['massive_discount']="<span class='fa fa-dollar-sign paddingrightonly'></span>".$langs->trans("Aplicar descuento masivo");
    //Aplicar margen de ganancia masivo
    $arrayofmassactions['massive_gain']="<span class='fa fa-dollar-sign paddingrightonly'></span>".$langs->trans("Aplicar margen de ganancia masivo");

    $rightskey='produit';
	if ($type == Product::TYPE_SERVICE) $rightskey='service';
	if ($user->rights->{$rightskey}->supprimer) $arrayofmassactions['predelete'] = "<span class='fa fa-trash paddingrightonly'></span>".$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	if ($type === "") $perm = ($user->rights->produit->creer || $user->rights->service->creer);
	elseif ($type == Product::TYPE_SERVICE) $perm = $user->rights->service->creer;
	elseif ($type == Product::TYPE_PRODUCT) $perm = $user->rights->produit->creer;
	if ($perm)
	{
		$oldtype = $type;
		$params = array();
		if ($type === "") $params['forcenohideoftext'] = 1;
		if ($type === "") {
			$newcardbutton .= dolGetButtonTitle($langs->trans('NewProduct'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/product/card.php?action=create&type=0', '', 1, $params);
			$type = Product::TYPE_SERVICE;
		}
		$label = 'NewProduct';
		if ($type == Product::TYPE_SERVICE) $label = 'NewService';
		$newcardbutton .= dolGetButtonTitle($langs->trans($label), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/product/card.php?action=create&type='.$type, '', 1, $params);

		$type = $oldtype;

		// Action to update prices only with multicurrency enabled.
		$setRateCUrrency = ($conf->multicurrency->enabled || $conf->global->PRODUIT_FOURN_MULTICURRENCY) ? dolGetButtonTitle($langs->trans("NewRateCurrency"), '', 'fa fa-coins', '', 'setRate', 1) : '';
    }
    $gainButton = '';
    if($user->rights->produit->modify_gain && $type == Product::TYPE_PRODUCT)
    {
        $gainButton = dolGetButtonTitle($langs->trans("Porcentaje precio mínimo"), '', 'fa fa-percent', $_SERVER['PHP_SELF'].'?action=edit_gain&type='.$type, 'gain', 1);
    }

    $buttonInvetoryReview = dolGetButtonTitle($langs->trans("Mandar a Revisión"), '', 'fa fa-box', '#', 'inventoryreview', 1);
    $buttonInvetoryReview.= '<script>';
    $buttonInvetoryReview.= '$(document).ready(function(){';
    $buttonInvetoryReview.= '   if(localStorage.productst === undefined || localStorage.productst === null){
                                    localStorage.productst = "";
                                }';
    $buttonInvetoryReview.= '   var productst=localStorage.productst;';
    $buttonInvetoryReview.= '   $("#inventoryreview").on("click",function(){';
    $buttonInvetoryReview.= '       productst = localStorage.productst;';
    $buttonInvetoryReview.= '       productst = productst.substring(1, productst.length);';
    $buttonInvetoryReview.= '       url="'.DOL_URL_ROOT.'/product/custom/inventory_review.php?id='.$user->fk_warehouse.'&action=correctionm&productst="+productst;';
    $buttonInvetoryReview.= '       location.href=url;';
    $buttonInvetoryReview.= '   });';
    $buttonInvetoryReview.= '   $(".checkforselect").on("change",function (index) {';
    $buttonInvetoryReview.= '     if($(this).is(":checked")){';
    $buttonInvetoryReview.= '        productst+=","+$(this).val();';
    $buttonInvetoryReview.= '     }else{
                                        productst = localStorage.productst.replace(","+$(this).val(),"")
                                    }
                                    if (typeof(Storage) !== "undefined") {
                                        localStorage.productst = productst;
                                    }
                                });';
    $buttonInvetoryReview.= '});';
    $buttonInvetoryReview.= '</script>';
    //editar ganancia
    if($action == 'edit_gain' && $user->rights->produit->modify_gain)
    {
        print '<div class="fichethirdleft">';
        print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="modify_gain">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><th>Porcentaje precio mínimo</th><th></th></tr>';
        print '<tr>';
        print '<td class="center">';
        print '<input type="text" name="gain" value="'.$gain['gain'].'">%';
        print '<input type="hidden" name="type" value="'.$type.'">';
        print '</td>';
        print '<td class="center">';
        print '<input type="submit" class="butAction" value="Grabar">';
        print '</td>';
        print '</tr>';
        print '</table>';
        print '</form>';
        print '</div>';
    }
    if($massaction == 'massive_discount' && $user->rights->categorie->apply_massive_discount)
    {//Descuento masivo
        print '<div class="fichethirdleft">';
        print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="apply_discount">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><th>Porcentaje de descuento</th>';
        print '<th>';
        print '<input type="text" name="discount_percent">';
        print '<input type="hidden" name="product_select" value=\''.json_encode($arrayofselected).'\'>';
        print '<input type="hidden" name="type" value="'.$type.'">';
        print '</th>';
        print '<th>';
        print '<input type="submit" class="button" value="Grabar">';
        print '</th>';
        print '</tr>';
        print '</table>';
        print '</form>';
        print '</div>';
    }
    if($massaction == 'massive_gain')
    {//Aplicar margen de ganancia
        print '<div class="fichethirdleft">';
        print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="apply_gain">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><th>'.$langs->trans("Gain").'%</th>';
        print '<th>';
        print '<input type="text" name="gain">';
        print '<input type="hidden" name="product_select" value=\''.json_encode($arrayofselected).'\'>';
        print '<input type="hidden" name="type" value="'.$type.'">';
        print '</th>';
        print '<th>';
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</th>';
        print '</tr>';
        print '</table>';
        print '</form>';
        print '</div>';
    }
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	if (empty($arrayfields['p.fk_product_type']['checked'])) print '<input type="hidden" name="search_type" value="'.dol_escape_htmltag($search_type).'">';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'products', 0, $setRateCUrrency . $newcardbutton . $gainButton . $buttonInvetoryReview, '', $limit);

	$topicmail = "Information";
	$modelmail = "product";
	$objecttmp = new Product($db);
	$trackid = 'prod'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if (!empty($catid))
	{
		print "<div id='ways'>";
		$c = new Categorie($db);
		$ways = $c->print_all_ways(' &gt; ', 'product/list.php');
		print " &gt; ".$ways[0]."<br>\n";
		print "</div><br>";
	}

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	// Filter on categories
	$moreforfilter = '';
	if (!empty($conf->categorie->enabled))
	{
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('Categorias').': ';
		$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
        $categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
        $moreforfilter .= Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
        $moreforfilter .= ' <input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories');
		$moreforfilter .= '  <input type="checkbox" class="valignmiddle" name="expiration_alert" value="1"'.($expiration_alert == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('Próximos a caducar');
		$moreforfilter .= '</div>';
	}
	
	//Show/hide child products. Hidden by default
	if (!empty($conf->variants->enabled) && !empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= '<input type="checkbox" id="search_show_childproducts" name="search_show_childproducts"'.($show_childproducts ? 'checked="checked"' : '').'>';
		$moreforfilter .= ' <label for="search_show_childproducts">'.$langs->trans('ShowChildProducts').'</label>';
		$moreforfilter .= '</div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Lines with input filters
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.old_ref']['checked']))
	{
		print '<td class="liste_titre left">';
		//print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['pfp.ref_fourn']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref_supplier" size="8" value="'.dol_escape_htmltag($search_ref_supplier).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.label']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_label" size="12" style="width:100%;" value="'.dol_escape_htmltag($search_label).'">';
		print '</td>';
	}
    // Duration
    if (!empty($arrayfields['categoria']['checked']))
    {
        print '<td class="liste_titre">';
        print '</td>';
    }
	// Type
	if (!empty($arrayfields['p.fk_product_type']['checked']))
	{
		print '<td class="liste_titre left">';
		$array = array('-1'=>'&nbsp;', '0'=>$langs->trans('Product'), '1'=>$langs->trans('Service'));
		print $form->selectarray('search_type', $array, $search_type);
		print '</td>';
	}
	// Barcode
	if (!empty($arrayfields['p.barcode']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_barcode" size="6" value="'.dol_escape_htmltag($search_barcode).'">';
		print '</td>';
	}
	// A caducar
	if (!empty($arrayfields['pl.eatby']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// % de Margen
	if (!empty($arrayfields['margen']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Duration
	if (!empty($arrayfields['p.duration']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Weight
	if (!empty($arrayfields['p.weight']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Length
	if (!empty($arrayfields['p.length']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Surface
	if (!empty($arrayfields['p.surface']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Volume
	if (!empty($arrayfields['p.volume']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}

	// Sell price
	if (!empty($arrayfields['p.sellprice']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}
	// Minimum buying Price
	if (!empty($arrayfields['p.minbuyprice']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	//Ganancia x PC
	if (!empty($arrayfields['p.gain']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="right flat maxwidth50" type="text" name="search_gain" value="'.dol_escape_htmltag($search_gain).'">';
		print '</td>';
	}
	// Number buying Price
	if (!empty($arrayfields['p.numbuyprice']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Buying prices with currency
	if (!empty($arrayfields['numpricesCurrency']['checked'])) {
		print '<td class="liste_titre right">';
		print $form->selectMultiCurrency($search_currency, 'search_currency', 1);
		print '</td >';
	}
    // Sell price
    if (!empty($arrayfields['p.tva_tx']['checked']))
    {
        print '<td class="liste_titre right">';
        print '<input class="right flat maxwidth50" placeholder="%" type="text" name="search_vatrate" size="1" value="'.dol_escape_htmltag($search_vatrate).'">';
        print '</td>';
    }
	// WAP
	if (!empty($arrayfields['p.pmp']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// UEPS
	if (!empty($arrayfields['p.ueps']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Limit for alert
	if (!empty($arrayfields['p.seuil_stock_alerte']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Desired stock
	if (!empty($arrayfields['p.desiredstock']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Stock
	if (!empty($arrayfields['p.stock']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
	// Stock to deliver
	if (!empty($arrayfields['stock_to_deliver']['checked'])) print '<td class="liste_titre">&nbsp;</td>';
	// Stock virtual
	if (!empty($arrayfields['stock_virtual']['checked'])) print '<td class="liste_titre">&nbsp;</td>';

	//Ultima fecha modificacion stock
	if (!empty($arrayfields['sm.datem']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print $form->selectDate($date_dateStock, 'search_dateStock','', '', 1, "search_dateStock", 1, 0);
		print '<br><input type="checkbox" name="month_alert" value="late_mov_stock"'.($month_alert == 'late_mov_stock' ? ' checked' : '').'> '.$langs->trans("Alert");
		print '</td>';
	}

	// To batch
	if (!empty($arrayfields['p.tobatch']['checked'])) print '<td class="liste_titre center">'.$form->selectyesno($search_tobatch, '', '', '', 1).'</td>';
	// Accountancy code sell
	if (!empty($arrayfields['p.accountancy_code_sell']['checked']))        print '<td class="liste_titre"><input class="flat maxwidth75" type="text" name="search_accountancy_code_sell" value="'.dol_escape_htmltag($search_accountancy_code_sell).'"></td>';
	if (!empty($arrayfields['p.accountancy_code_sell_intra']['checked']))  print '<td class="liste_titre"><input class="flat maxwidth75" type="text" name="search_accountancy_code_sell_intra" value="'.dol_escape_htmltag($search_accountancy_code_sell_intra).'"></td>';
	if (!empty($arrayfields['p.accountancy_code_sell_export']['checked'])) print '<td class="liste_titre"><input class="flat maxwidth75" type="text" name="search_accountancy_code_sell_export" value="'.dol_escape_htmltag($search_accountancy_code_sell_export).'"></td>';
	// Accountancy code buy
	if (!empty($arrayfields['p.accountancy_code_buy']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" name="search_accountancy_code_buy" size="6" value="'.dol_escape_htmltag($search_accountancy_code_buy).'"></td>';
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['p.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['p.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (!empty($arrayfields['p.tosell']['checked']))
	{
		print '<td class="liste_titre right">';
		print $form->selectarray('search_tosell', array('0'=>$langs->trans('ProductStatusNotOnSellShort'), '1'=>$langs->trans('ProductStatusOnSellShort')), $search_tosell, 1);
		print '</td >';
	}
	if (!empty($arrayfields['p.tobuy']['checked']))
	{
		print '<td class="liste_titre right">';
		print $form->selectarray('search_tobuy', array('0'=>$langs->trans('ProductStatusNotOnBuyShort'), '1'=>$langs->trans('ProductStatusOnBuyShort')), $search_tobuy, 1);
		print '</td>';
	}
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['p.ref']['checked'])) {
        print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
    }
	if (!empty($arrayfields['p.old_ref']['checked'])) {
        print_liste_field_titre($arrayfields['p.old_ref']['label'], $_SERVER["PHP_SELF"], "p.old_ref", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['pfp.ref_fourn']['checked'])) {
        print_liste_field_titre($arrayfields['pfp.ref_fourn']['label'], $_SERVER["PHP_SELF"], "pfp.ref_fourn", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.label']['checked'])) {
        print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], "p.label", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['categoria']['checked'])) {
        print_liste_field_titre($arrayfields['categoria']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.fk_product_type']['checked'])) {
        print_liste_field_titre($arrayfields['p.fk_product_type']['label'], $_SERVER["PHP_SELF"], "p.fk_product_type", "", $param, "", $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.barcode']['checked'])) {
        print_liste_field_titre($arrayfields['p.barcode']['label'], $_SERVER["PHP_SELF"], "p.barcode", "", $param, "", $sortfield, $sortorder);
    }
	// Expiration Date
	if (!empty($arrayfields['pl.eatby']['checked'])){
		print_liste_field_titre($arrayfields['pl.eatby']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder, 'center ');
	}
	// % de margen
	if (!empty($arrayfields['margen']['checked'])) {
		print_liste_field_titre($arrayfields['margen']['label'], $_SERVER["PHP_SELF"], "margen", "", $param, "", $sortfield, $sortorder, 'center ');
	}
    if (!empty($arrayfields['p.duration']['checked'])) {
        print_liste_field_titre($arrayfields['p.duration']['label'], $_SERVER["PHP_SELF"], "p.duration", "", $param, '', $sortfield, $sortorder, 'center ');
    }
	if (!empty($arrayfields['p.weight']['checked']))  print_liste_field_titre($arrayfields['p.weight']['label'], $_SERVER["PHP_SELF"], "p.weight", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['p.length']['checked']))  print_liste_field_titre($arrayfields['p.length']['label'], $_SERVER["PHP_SELF"], "p.length", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['p.surface']['checked'])) print_liste_field_titre($arrayfields['p.surface']['label'], $_SERVER["PHP_SELF"], "p.surface", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['p.volume']['checked']))  print_liste_field_titre($arrayfields['p.volume']['label'], $_SERVER["PHP_SELF"], "p.volume", "", $param, '', $sortfield, $sortorder, 'center ');
    if (!empty($arrayfields['p.sellprice']['checked'])) {
        print_liste_field_titre($arrayfields['p.sellprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
    if (!empty($arrayfields['p.minbuyprice']['checked'])) {
        print_liste_field_titre($arrayfields['p.minbuyprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
    }
	if (!empty($arrayfields['p.gain']['checked'])) {
        print_liste_field_titre($arrayfields['p.gain']['label'], $_SERVER["PHP_SELF"], "p.gain", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.numbuyprice']['checked'])) {
        print_liste_field_titre($arrayfields['p.numbuyprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['numpricesCurrency']['checked'])) {
        print_liste_field_titre($arrayfields['numpricesCurrency']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.tva_tx']['checked'])) {
        print_liste_field_titre($arrayfields['p.tva_tx']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.pmp']['checked'])) {
        print_liste_field_titre($arrayfields['p.pmp']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.ueps']['checked'])) {
        print_liste_field_titre($arrayfields['p.ueps']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.seuil_stock_alerte']['checked'])) {
        print_liste_field_titre($arrayfields['p.seuil_stock_alerte']['label'], $_SERVER["PHP_SELF"], "p.seuil_stock_alerte", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.desiredstock']['checked'])) {
        print_liste_field_titre($arrayfields['p.desiredstock']['label'], $_SERVER["PHP_SELF"], "p.desiredstock", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.stock']['checked'])) {
        print_liste_field_titre($arrayfields['p.stock']['label'], $_SERVER["PHP_SELF"], "p.stock", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['stock_to_deliver']['checked'])) {
        print_liste_field_titre($arrayfields['stock_to_deliver']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
    }
	if (!empty($arrayfields['stock_virtual']['checked'])) {
        print_liste_field_titre($arrayfields['stock_virtual']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
    if (!empty($arrayfields['sm.datem']['checked'])) {
        print_liste_field_titre($arrayfields['sm.datem']['label'], $_SERVER["PHP_SELF"], "datem", "", $param, "", $sortfield, $sortorder, 'center ');
    }
	if (!empty($arrayfields['p.tobatch']['checked'])) {
        print_liste_field_titre($arrayfields['p.tobatch']['label'], $_SERVER["PHP_SELF"], "p.tobatch", "", $param, '', $sortfield, $sortorder, 'center ');
    }
	if (!empty($arrayfields['p.accountancy_code_sell']['checked'])) {
        print_liste_field_titre($arrayfields['p.accountancy_code_sell']['label'], $_SERVER["PHP_SELF"], "p.accountancy_code_sell", "", $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.accountancy_code_sell_intra']['checked'])) {
        print_liste_field_titre($arrayfields['p.accountancy_code_sell_intra']['label'], $_SERVER["PHP_SELF"], "p.accountancy_code_sell_intra", "", $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.accountancy_code_sell_export']['checked'])) {
        print_liste_field_titre($arrayfields['p.accountancy_code_sell_export']['label'], $_SERVER["PHP_SELF"], "p.accountancy_code_sell_export", "", $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['p.accountancy_code_buy']['checked'])) {
        print_liste_field_titre($arrayfields['p.accountancy_code_buy']['label'], $_SERVER["PHP_SELF"], "p.accountancy_code_buy", "", $param, '', $sortfield, $sortorder);
    }
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
    if (!empty($arrayfields['p.datec']['checked'])) {
        print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
    }
    if (!empty($arrayfields['p.tms']['checked'])) {
        print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
    }
    if (!empty($arrayfields['p.tosell']['checked'])) {
        print_liste_field_titre($arrayfields['p.tosell']['label'], $_SERVER["PHP_SELF"], "p.tosell", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    if (!empty($arrayfields['p.tobuy']['checked'])) {
        print_liste_field_titre($arrayfields['p.tobuy']['label'], $_SERVER["PHP_SELF"], "p.tobuy", "", $param, '', $sortfield, $sortorder, 'right ');
    }
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$product_static = new Product($db);
	$product_fourn = new ProductFournisseur($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		// Prox. a caducar
		$exp = 0;
		$sqlExpirationDate = "SELECT pl.fk_product , pl.eatby as expiration  FROM llx_product_lot pl JOIN llx_product lp ON lp.rowid = pl.fk_product WHERE lp.rowid = ".$obj->rowid."";
		$resqlED = $db->query($sqlExpirationDate);
		while($objED = $db->fetch_object($resqlED)){
			// Se valida si al menos una fecha califica como proxima a caducar
			if($objED -> expiration < $refdate){
				$exp ++;
				break;
			}
		}

		// Multilangs
		if (!empty($conf->global->MAIN_MULTILANGS))  // If multilang is enabled
		{
			$sql = "SELECT label";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql .= " WHERE fk_product=".$obj->rowid;
			$sql .= " AND lang='".$db->escape($langs->getDefaultLang())."'";
			$sql .= " LIMIT 1";

			$result = $db->query($sql);
			if ($result)
			{
				$objtp = $db->fetch_object($result);
				if (!empty($objtp->label)) $obj->label = $objtp->label;
			}
		}

		$product_static->id = $obj->rowid;
		$product_static->ref = $obj->ref;
		$product_static->ref_fourn = $obj->ref_supplier; // deprecated
		$product_static->ref_supplier = $obj->ref_supplier;
		$product_static->label = $obj->label;
		$product_static->type = $obj->fk_product_type;
		$product_static->status_buy = $obj->tobuy;
		$product_static->status     = $obj->tosell;
		$product_static->status_batch = $obj->tobatch;
		$product_static->entity = $obj->entity;
		$product_static->pmp = $obj->pmp;
		$product_static->accountancy_code_sell = $obj->accountancy_code_sell;
		$product_static->accountancy_code_sell_export = $obj->accountancy_code_sell_export;
		$product_static->accountancy_code_sell_intra = $obj->accountancy_code_sell_intra;
		$product_static->accountancy_code_buy = $obj->accountancy_code_buy;
		$product_static->length = $obj->length;
		$product_static->length_units = $obj->length_units;
		$product_static->width = $obj->width;
		$product_static->width_units = $obj->width_units;
		$product_static->height = $obj->height;
		$product_static->height_units = $obj->height_units;
		$product_static->weight = $obj->weight;
		$product_static->weight_units = $obj->weight_units;
		$product_static->volume = $obj->volume;
		$product_static->volume_units = $obj->volume_units;
		$product_static->surface = $obj->surface;
		$product_static->surface_units = $obj->surface_units;
		$product_static->numpricesCurrency = $obj->numpricesCurrency;
		$product_static->old_ref = $obj->old_ref;

		// STOCK_DISABLE_OPTIM_LOAD can be set to force load_stock whatever is permissions on stock.
		if ((!empty($conf->stock->enabled) && $user->rights->stock->lire && $search_type != 1) || !empty($conf->global->STOCK_DISABLE_OPTIM_LOAD))	// To optimize call of load_stock
		{
			if ($obj->fk_product_type != 1 || !empty($conf->global->STOCK_SUPPORTS_SERVICES))    // Not a service
			{
				$option = 'nobatch';
				if (empty($arrayfields['stock_virtual']['checked'])) $option .= ',novirtual';
				$product_static->load_stock($option); // Load stock_reel + stock_warehouse. This can also call load_virtual_stock()
			}
		}

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $product_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Ref
		if (!empty($arrayfields['p.old_ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $product_static->old_ref;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Ref supplier
		if (!empty($arrayfields['pfp.ref_fourn']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $product_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Label
		if (!empty($arrayfields['p.label']['checked']))
		{
			print '<td>'.dol_trunc($obj->label, 80).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
        // Categoria
        if (!empty($arrayfields['categoria']['checked']))
        {
            print '<td class="tdoverflowmax200">';
            print $form->showCategories($obj->rowid, 'product', 1);
            print '</td>';
        }

		// Type
		if (!empty($arrayfields['p.fk_product_type']['checked']))
		{
			print '<td>';
			if ($obj->fk_product_type == 0) print $langs->trans("Product");
			else print $langs->trans("Service");
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Barcode
		if (!empty($arrayfields['p.barcode']['checked']))
		{
			print '<td>'.$obj->barcode.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		//Caducidad
        if (!empty($arrayfields['pl.eatby']['checked']))
        {
			if($exp > 0)
			{
				print '<td class="center">';
				print img_warning("Próximo a caducar");
				print '</td>';
			}
			else{
				print '<td class="center"></td>';
			}
        }

		// % de Margen 
		if (!empty($arrayfields['margen']['checked']))
		{
			$margen = number_format($obj->margen, 2);
			$style = '';
			if($obj->margen < 0) $style = 'color:red;';
			else if($obj->margen > 0) $style = 'color:black;';
			print '<td class="center" style="'.$style.'">';
			print $margen . ' %';
			print '</td>';
		}

		// Duration
		if (!empty($arrayfields['p.duration']['checked']))
		{
			print '<td class="center nowraponall">';

			if (preg_match('/([^a-z]+)[a-z]$/i', $obj->duration))
			{
				$duration_value = substr($obj->duration, 0, dol_strlen($obj->duration) - 1);
				$duration_unit = substr($obj->duration, -1);

				if ((float) $duration_value > 1)
				{
				    $dur = array("i"=>$langs->trans("Minutes"), "h"=>$langs->trans("Hours"), "d"=>$langs->trans("Days"), "w"=>$langs->trans("Weeks"), "m"=>$langs->trans("Months"), "y"=>$langs->trans("Years"));
				}
				elseif ((float) $duration_value > 0)
				{
				    $dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hour"), "d"=>$langs->trans("Day"), "w"=>$langs->trans("Week"), "m"=>$langs->trans("Month"), "y"=>$langs->trans("Year"));
				}
				print $duration_value;
				print ((!empty($duration_unit) && isset($dur[$duration_unit]) && $duration_value != '') ? ' '.$langs->trans($dur[$duration_unit]) : '');
			}
			elseif (!preg_match('/^[a-z]$/i', $obj->duration))		// If duration is a simple char (like 's' of 'm'), we do not show value
			{
				print $obj->duration;
			}

			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Weight
		if (!empty($arrayfields['p.weight']['checked']))
		{
		    print '<td class="center">';
		    print $obj->weight;
		    print '</td>';
		    if (!$i) $totalarray['nbfield']++;
		}
		// Length
		if (!empty($arrayfields['p.length']['checked']))
		{
		    print '<td class="center">';
		    print $obj->length;
		    print '</td>';
		    if (!$i) $totalarray['nbfield']++;
		}
		// Surface
		if (!empty($arrayfields['p.surface']['checked']))
		{
		    print '<td class="center">';
		    print $obj->surface;
		    print '</td>';
		    if (!$i) $totalarray['nbfield']++;
		}
		// Volume
		if (!empty($arrayfields['p.volume']['checked']))
		{
		    print '<td class="center">';
		    print $obj->volume;
		    print '</td>';
		    if (!$i) $totalarray['nbfield']++;
		}

		// Sell price
		if (!empty($arrayfields['p.sellprice']['checked']))
		{
			print '<td class="right nowraponall">';
			if ($obj->tosell)
			{
				if ($obj->price_base_type == 'TTC') print price($obj->price_ttc);//.' '.$langs->trans("TTC");
				else print price($obj->price).' '.$langs->trans("HT");
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Better buy price
		if (!empty($arrayfields['p.minbuyprice']['checked']))
		{
			print  '<td class="right nowraponall">';
			if ($obj->tobuy && $obj->minsellprice != '')
			{
				//print price($obj->minsellprice).' '.$langs->trans("HT");
				if ($product_fourn->find_min_price_product_fournisseur($obj->rowid) > 0)
				{
					if ($product_fourn->product_fourn_price_id > 0)
					{
						if (!empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
						{
							$htmltext = $product_fourn->display_price_product_fournisseur(1, 1, 0, 1);
							print $form->textwithpicto(price($product_fourn->fourn_unitprice * (1 - $product_fourn->fourn_remise_percent / 100) - $product_fourn->fourn_remise).' '.$langs->trans("HT"), $htmltext);
						}
						else print price($product_fourn->fourn_unitprice).' '.$langs->trans("HT");
					}
				}
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		if (!empty($arrayfields['p.gain']['checked']))
		{
			print  '<td class="right nowraponall">';
			print $obj->gain.'%';
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Number of buy prices
		if (!empty($arrayfields['p.numbuyprice']['checked']))
		{
			print  '<td class="right">';
			if ($obj->tobuy)
			{
				if (count($productFournList = $product_fourn->list_product_fournisseur_price($obj->rowid)) > 0)
				{
					$htmltext = $product_fourn->display_price_product_fournisseur(1, 1, 0, 1, $productFournList);
					print $form->textwithpicto(count($productFournList), $htmltext);
				}
			}
			print '</td>';
		}

		// Currency
		if (!empty($arrayfields['numpricesCurrency']['checked']))
		{
			print '<td class="right nowrap">';

			$sql1="select multicurrency_code from llx_product_fournisseur_price where fk_product = ".$obj->rowid." and price=(select min(price) from llx_product_fournisseur_price where fk_product= ".$obj->rowid.") limit 1";
			$result1=$db->query($sql1);
			$data=$db->fetch_object($result1);
			print ($data->multicurrency_code) ? $data->multicurrency_code : '&nbsp;';
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

        // Sell Tax Rate
        if (!empty($arrayfields['p.tva_tx']['checked']))
        {
            print '<td class="right">';
            print vatrate($obj->tva_tx, true);
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

		// WAP
		if (!empty($arrayfields['p.pmp']['checked']))
		{
			print '<td class="nowrap right">';
			print price($product_static->pmp, 1, $langs);
			print '</td>';
		}
        // UEPS
        if (!empty($arrayfields['p.ueps']['checked']))
        {
            $sqlueps = 'SELECT price ';
            $sqlueps .= 'FROM '.MAIN_DB_PREFIX.'stock_mouvement ';
            $sqlueps .= 'WHERE fk_product = '.$product_static->id.' ';
            $sqlueps .= 'AND value > 0 ORDER BY datem DESC LIMIT 1';
            $resqlueps = $db->query($sqlueps);
            if($resqlueps){
                $objres = $db->fetch_object($resqlueps);
            }
            $ueps_value = price($objres->price*$product_static->stock_reel);
            print '<td class="nowrap right">';
            print $ueps_value;
            print '</td>';
        }

		// Limit alert
		if (!empty($arrayfields['p.seuil_stock_alerte']['checked']))
		{
			print '<td class="right">';
			if ($obj->fk_product_type != 1)
			{
				print $obj->seuil_stock_alerte;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Desired stock
		if (!empty($arrayfields['p.desiredstock']['checked']))
		{
			print '<td class="right">';
			if ($obj->fk_product_type != 1)
			{
				print $obj->desiredstock;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Stock real
		if (!empty($arrayfields['p.stock']['checked']))
		{
			print '<td class="right">';
			if ($obj->fk_product_type != 1)
			{
				if ($obj->seuil_stock_alerte != '' && $product_static->stock_reel < (float) $obj->seuil_stock_alerte) print img_warning($langs->trans("StockLowerThanLimit", $obj->seuil_stock_alerte)).' ';
				$physical_stock = ($product_static->stock_warehouse[1]->real > 0)? $product_static->stock_warehouse[1]->real : 0;
				$physical_stock += $product_static->stock_warehouse[2]->real;
				print $obj->stock;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Stock to be delivered
		if (!empty($arrayfields['stock_to_deliver']['checked']))
		{
			print '<td class="right">';
			if ($obj->fk_product_type != 1)
			{
				$stock_to_deliver = ($product_static->stock_warehouse[1]->real < 0)? $product_static->stock_warehouse[1]->real : 0;
				$result = $product_static->load_stats_commande(0, '1,2', 1);
				$stock_to_deliver = ($stock_to_deliver - $product_static->stats_commande['qty']) * -1;
				$result = $product_static->load_stats_sending(0, '2', 1, '1,2');
				$stock_to_deliver -= $product_static->stats_expedition['qty'];
				print $stock_to_deliver;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Stock virtual
		if (!empty($arrayfields['stock_virtual']['checked']))
		{
			$total_physical_stock = 0;
			$total_to_deliver = 0;
			$total_virtual_stock = 0;
			$stock_to_deliver = ($product_static->stock_reel < 0) ? $product_static->stock_reel : 0;
			$result = $product_static->load_stats_commande(0, '1,2', 1, $entrepotTemp);
			$stock_to_deliver = (($stock_to_deliver - $product_static->stats_commande['qty']) * -1);
			$stock_to_deliver -= $product_static->stats_expedition['qty'];

			// Virtual Stock
			$result = $product_static->load_stats_commande_fournisseur(0, '3,4', 1);
			$virtual_stock = $product_static->stock_reel - $stock_to_deliver + $product_static->stats_commande_fournisseur['qty'];

			$total_physical_stock += $product_static->stock_reel;
			$total_to_deliver += $stock_to_deliver;
			$total_virtual_stock += $virtual_stock; 

			$result = $product_static->load_stats_commande_fournisseur(0, '3,4', 1);
			$pedidosOC = $product_static->stats_commande_fournisseur['qty'];
			//Formula Stock Virtual
			$total_virtual = $total_physical_stock - $total_to_deliver;
			// $product_static->total_virtual_stock = $total_virtual;

			print '<td class="right">';
			if ($obj->fk_product_type != 1)
			{
				if ($obj->seuil_stock_alerte != '' && $product_static->stock_theorique < (float) $obj->seuil_stock_alerte) print img_warning($langs->trans("StockLowerThanLimit", $obj->seuil_stock_alerte)).' ';
				// print $product_static->total_virtual_stock;
				print $total_virtual;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Ultima moficacion movimiento stock
		if (!empty($arrayfields['sm.datem']['checked']))
		{
			print '<td class="center">';
			$firstDate  = new DateTime($obj->datem);
			$secondDate = new DateTime($obj->penultimo);
			$intvl = $firstDate->diff($secondDate);
			print dol_print_date($db->jdate($obj->datem), 'dayhour', 'tzuserrel');
			if( $intvl->m < 2 && $obj->penultimo ){
				print '&nbsp;';
				print img_warning($langs->trans("LastInventoryAjust2Months"));
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Lot/Serial
		if (!empty($arrayfields['p.tobatch']['checked']))
		{
			print '<td class="center">';
			print yn($obj->tobatch);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Accountancy code sell
		if (!empty($arrayfields['p.accountancy_code_sell']['checked']))
		{
			print '<td>'.$obj->accountancy_code_sell.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		if (!empty($arrayfields['p.accountancy_code_sell_intra']['checked']))
		{
		    print '<td>'.$obj->accountancy_code_sell_intra.'</td>';
		    if (!$i) $totalarray['nbfield']++;
		}
		if (!empty($arrayfields['p.accountancy_code_sell_export']['checked']))
		{
		    print '<td>'.$obj->accountancy_code_sell_export.'</td>';
		    if (!$i) $totalarray['nbfield']++;
		}
		// Accountancy code buy
		if (!empty($arrayfields['p.accountancy_code_buy']['checked']))
		{
			print '<td>'.$obj->accountancy_code_buy.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['p.tms']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Status (to sell)
		if (!empty($arrayfields['p.tosell']['checked']))
		{
			print '<td class="right nowrap">';
			if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
				print ajax_object_onoff($product_static, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
			} else {
				print $product_static->LibStatut($obj->tosell, 5, 0);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status (to buy)
		if (!empty($arrayfields['p.tobuy']['checked']))
		{
			print '<td class="right nowrap">';
			if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
				print ajax_object_onoff($product_static, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
			} else {
				print $product_static->LibStatut($obj->tobuy, 5, 1);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";
	print "</div>";
	print '</form>';

	print '<div id="dialog-form" title="'.$langs->trans("SetRate").'">';
	print '<table class="centpercent">';
	// Rate
	print '<tr><td>'.$langs->trans('TypeRate').'</td><td>';
	print '<input type="number" step="0.0001" id="wrapp_rate">';
	print '</td></tr>';
	print '</table></div>';

	?>
	<script>
	(function() {
		var btnRate = document.querySelector('#setRate');

		var dialog = $( "#dialog-form" ).dialog({
			autoOpen: false,
			modal: true,
			buttons: {
				Cancelar: function() {
					dialog.dialog( "close" );
				},
				Aplicar: function() {
					dialog.dialog( "close" );

					var wrapp = document.querySelector("#wrapp_rate");
					var form = $("form[name='formulaire']");

					form.append(`<input type="hidden" name="subaction" value="applyrate"><input type="number" name="currency_rate" value="${wrapp.value}">`);
					form.submit();
				}
			},
		});

		btnRate.onclick = function(e) {
			dialog.dialog( "open" );
		}
	})();
	</script>
	<?php
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
