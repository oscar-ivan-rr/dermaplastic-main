<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Charlene Benke	    <charlie@patas-monkey.com>

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
 *	\file       htdocs/commande/list.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

// Load translation files required by the page
$langs->loadLangs(array("orders", 'sendings', 'deliveries', 'companies', 'compta', 'bills'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'orderlist';

$date_start_order = GETPOST("date_start_order", "int");
$date_start_orderday = GETPOST("date_start_orderday", "int");
$date_start_ordermonth = GETPOST("date_start_ordermonth", "int");
$date_start_orderyear = GETPOST("date_start_orderyear", "int");

$date_end_order = GETPOST("date_end_order", "int");
$date_end_orderday = GETPOST("date_end_orderday", "int");
$date_end_ordermonth = GETPOST("date_end_ordermonth", "int");
$date_end_orderyear = GETPOST("date_end_orderyear", "int");

$search_total_pieces = GETPOST('search_total_pieces', 'int');

$search_deliveryyear = GETPOST("search_deliveryyear", "int");
$search_deliverymonth = GETPOST("search_deliverymonth", "int");
$search_deliveryday = GETPOST("search_deliveryday", "int");
$search_product_category = GETPOST('search_product_category', 'int');
$search_ref = GETPOST('search_ref', 'alpha') != '' ?GETPOST('search_ref', 'alpha') : GETPOST('sref', 'alpha');
$search_ref_customer = GETPOST('search_ref_customer', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = trim(GETPOST("search_state"));
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$socid = GETPOST('socid', 'int');
$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_total_ht = GETPOST('search_total_ht', 'alpha');
$search_total_ttc = GETPOST('search_total_ttc', 'alpha');
$search_categ_cus = trim(GETPOST("search_categ_cus", 'int'));
$optioncss = GETPOST('optioncss', 'alpha');
$billed = GETPOST('billed', 'int');
$viewstatut = GETPOST('viewstatut', 'int');
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_shipments = GETPOST('search_shipments');
$search_ttc_shipments = GETPOST('search_ttc_shipments');
$search_comm_vs_ship = GETPOST('search_comm_vs_ship');

// Security check
$id = (GETPOST('orderid') ?GETPOST('orderid', 'int') : GETPOST('id', 'int'));
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'commande', $id, '');

$diroutputmassaction = $conf->commande->multidir_output[$conf->entity].'/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->global->MAX_LIST_ORDER_REPORT;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'c.ref';
if (!$sortorder) $sortorder = 'DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Commande($db);
$hookmanager->initHooks(array('orderlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('commande');
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'c.ref'=>'Ref',
	'c.ref_client'=>'RefCustomerOrder',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	's.name_alias'=>"AliasNameShort",
	'c.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["c.note_private"] = "NotePrivate";

$checkedtypetiers = 0;
$arrayfields = array(
	'c.ref'=>array('label'=>"Pedido", 'checked'=>1),
	//'c.ref_client'=>array('label'=>"RefCustomerOrder", 'checked'=>1),
    'p.ref'=>array('label'=>"Project", 'checked'=>1, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1)),
    //'p.title'=>array('label'=>"ProjectLabel", 'checked'=>0, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1)),
    's.nom'=>array('label'=>"Client", 'checked'=>1),
	//'s.town'=>array('label'=>"Town", 'checked'=>1),
	//'s.zip'=>array('label'=>"Zip", 'checked'=>1),
	//'state.nom'=>array('label'=>"StateShort", 'checked'=>0),
	//'country.code_iso'=>array('label'=>"Country", 'checked'=>0),
	//'typent.code'=>array('label'=>"ThirdPartyType", 'checked'=>$checkedtypetiers),
	'c.date_commande'=>array('label'=>"OrderDateShort", 'checked'=>1),
	'cd.total_qty'=>array('label'=>"Total de Pzs.", 'checked'=>0),
	// 'c.date_delivery'=>array('label'=>"DateDeliveryPlanned", 'checked'=>1, 'enabled'=>empty($conf->global->ORDER_DISABLE_DELIVERY_DATE)),
	'c.total_ht'=>array('label'=>"AmountHT", 'checked'=>1),
	'c.total_vat'=>array('label'=>"AmountVAT", 'checked'=>0),
	'c.total_ttc'=>array('label'=>"AmountTTCCommande", 'checked'=>0),
	// 'c.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	//'c.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
    // 'c.date_cloture'=>array('label'=>"DateClosing", 'checked'=>0, 'position'=>500),
	//'c.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
	//'c.facture'=>array('label'=>"Billed", 'checked'=>1, 'position'=>1000, 'enabled'=>(empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))),
	// 'shipments'=>array('label'=>"Shipments", 'checked'=>1, 'position'=>1000),
	// 'ttc_shipments'=>array('label'=>"ShipmentsTTC", 'checked'=>1, 'position'=>1000),
	// 'comm_vs_ship'=>array('label'=>"CommandeVsShipment", 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
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
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_categ = '';
		$search_user = '';
		$search_sale = '';
		$search_product_category = '';
		$search_ref = '';
		$search_ref_customer = '';
		$search_company = '';
		$search_town = '';
		$search_zip = "";
		$search_state = "";
		$search_type = '';
		$search_country = '';
		$search_type_thirdparty = '';
		$search_total_ht = '';
		$search_total_vat = '';
		$search_total_ttc = '';
		$search_orderyear = '';
		$search_ordermonth = '';
		$search_orderday = '';
		$search_deliveryday = '';
		$search_deliverymonth = '';
		$search_deliveryyear = '';
		$search_project_ref = '';
		$search_project = '';
		$viewstatut = '';
		$billed = '';
		$toselect = '';
		$search_array_options = array();
		$search_categ_cus = 0;
		$search_shipments = '';
		$search_ttc_shipments = '';
		$search_comm_vs_ship = '';

		$date_start_order = '';
		$date_start_orderday = '';
		$date_start_ordermonth = '';
		$date_start_orderyear = '';

		$date_end_order = '';
		$date_end_orderday = '';
		$date_end_ordermonth = '';
		$date_end_orderyear = '';

		$search_total_pieces = '';

	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
	 || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Commande';
	$objectlabel = 'Orders';
	$permissiontoread = $user->rights->commande->lire;
	$permissiontodelete = $user->rights->commande->supprimer;
	$uploaddir = $conf->commande->multidir_output[$conf->entity];
	$triggersendname = 'ORDER_SENTBYMAIL';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);
$formcompany = new FormCompany($db);
$projectstatic = new Project($db);

$title = $langs->trans("OrdersVsShipmentsReport");
$help_url = "EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
// llxHeader('',$title,$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql .= ' s.rowid as socid, s.nom as name, s.email, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= ' c.rowid, c.ref, c.total_ht, c.tva as total_tva, c.total_ttc, cd.total_qty, c.ref_client,';
$sql .= ' c.date_valid, c.date_commande, c.note_private, c.date_livraison as date_delivery, c.fk_statut, c.facture as billedTemp, ';

$sql .= ' IF(c.facture = 1,
						IF(c.billedRest NOT BETWEEN -0.019 AND 0.011 , CONCAT("Si(", TRUNCATE(c.billedRest,2), ")"), "Si"),
						"No"
						) AS billed, ';

$sql .= ' c.date_creation as date_creation, c.tms as date_update, c.date_cloture as date_cloture,';
$sql .= " p.rowid as project_id, p.ref as project_ref, p.title as project_label";
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label']))
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (!empty($search_categ_cus)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ
$sql .= ', '.MAIN_DB_PREFIX.'commande as c';
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ef on (c.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON c.rowid=pd.fk_commande';
if ($search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = c.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}

// Total pieces
$sql .= " LEFT JOIN (SELECT fk_commande, SUM(qty) as total_qty FROM " . MAIN_DB_PREFIX . "commandedet GROUP BY fk_commande) as cd
                   ON c.rowid = cd.fk_commande";

$sql .= ' WHERE c.fk_soc = s.rowid';
$sql .= ' AND c.entity IN ('.getEntity('commande').')';
if ($search_product_category > 0) $sql .= " AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql .= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($search_ref) $sql .= natural_search('c.ref', $search_ref);
if ($search_ref_customer) $sql .= natural_search('c.ref_client', $search_ref_customer);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

if ($billed == 3) $sql .= ' AND c.facture = 0';
if ($billed == 1) $sql .= ' AND c.facture = 1 AND c.billedRest BETWEEN  -0.019 AND 0.011 ';
if ($billed == 2) $sql .= ' AND c.facture = 1 AND c.billedRest NOT BETWEEN  -0.019 AND 0.011 ';


if ($viewstatut <> '')
{
	if ($viewstatut < 4 && $viewstatut > -3)
	{
		if ($viewstatut == 1 && empty($conf->expedition->enabled)) $sql .= ' AND c.fk_statut IN (1,2)'; // If module expedition disabled, we include order with status 'sending in process' into 'validated'
		elseif ($viewstatut == 3) $sql .= ' AND c.fk_statut = 3 AND c.facture = 0 ';
		else $sql .= ' AND c.fk_statut = '.$viewstatut; // brouillon, validee, en cours, annulee
	}
	if ($viewstatut == 4)
	{
		$sql .= ' AND c.facture = 1'; // invoice created
	}
	if ($viewstatut == -2)	// To process
	{
		//$sql.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
		$sql .= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))"; // If status is 2 and facture=1, it must be selected
	}
	if ($viewstatut == -3)	// Validado+Expedición en curso+Emitido+Facturado
	{
		$sql .= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND (c.facture = 0 OR (c.facture = 1 AND c.billedRest IS NOT NULL))))'; 
	}
	if ($viewstatut == -4)	// FACTURADO
	{
		if($billed == -1) 	$sql .= ' AND c.fk_statut = 3 AND c.facture = 1 AND c.billedRest IS NOT NULL';
		if($billed == 1)	$sql .= ' AND c.fk_statut = 3 AND c.facture = 1 AND c.billedRest BETWEEN  -0.019 AND 0.011'; //si
		if($billed == 2) 	$sql .= ' AND c.fk_statut = 3 AND c.facture = 1 AND c.billedRest NOT BETWEEN  -0.019 AND 0.011 '; //si(sxf)
		if($billed == 3) 	$sql .= ' AND c.fk_statut = -99 AND c.facture = -99'; //no
	}
}
$sql .= dolSqlDateFilter("c.date_commande", $search_orderday, $search_ordermonth, $search_orderyear);
$sql .= dolSqlDateFilter("c.date_livraison", $search_deliveryday, $search_deliverymonth, $search_deliveryyear);
if ($search_town)  $sql .= natural_search('s.town', $search_town);
if ($search_zip)   $sql .= natural_search("s.zip", $search_zip);
if ($search_state) $sql .= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_sale > 0) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$search_sale;
if ($search_user > 0) $sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".$search_user;
if ($search_total_ht != '') $sql .= natural_search('c.total_ht', $search_total_ht, 1);
if ($search_total_ttc != '') $sql .= natural_search('c.total_ttc', $search_total_ttc, 1);
if ($search_project_ref != '') $sql .= natural_search("p.ref", $search_project_ref);
if ($search_project != '') $sql .= natural_search("p.title", $search_project);
if ($search_categ_cus > 0) $sql .= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)   $sql .= " AND cc.fk_categorie IS NULL";
$sql .= " AND c.fk_statut <> -1";

// Filter by date commande start and end
if($date_start_orderyear && !$date_end_orderyear)
	$sql .= " AND c.date_commande >= '".$date_start_orderyear."-".$date_start_ordermonth."-".$date_start_orderday."'";
elseif(!$date_start_orderyear && $date_end_orderyear)
	$sql .= " AND c.date_commande <= '".$date_end_orderyear."-".$date_end_ordermonth."-".$date_end_orderday."'";
elseif($date_start_orderyear && $date_end_orderyear)
	$sql .= " AND c.date_commande BETWEEN '".$date_start_orderyear."-".$date_start_ordermonth."-".$date_start_orderday."' AND '".$date_end_orderyear."-".$date_end_ordermonth."-".$date_end_orderday."'";

// Filter total pieces
if($search_total_pieces) $sql .= " AND cd.total_qty = ".$search_total_pieces;

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
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
	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('OrdersVsShipmentsReport').' - '.$soc->name;
		if (empty($search_company)) $search_company = $soc->name;
	}
	else
	{
		$title = $langs->trans('OrdersVsShipmentsReport');
	}
	if (strval($viewstatut) == '0')
	$title .= ' - '.$langs->trans('StatusOrderDraftShort');
	if ($viewstatut == 1)
	$title .= ' - '.$langs->trans('StatusOrderValidatedShort');
	if ($viewstatut == 2)
	$title .= ' - '.$langs->trans('StatusOrderSentShort');
	if ($viewstatut == 3)
	$title .= ' - '.$langs->trans('StatusOrderToBillShort');
	if ($viewstatut == 4)
	$title .= ' - '.$langs->trans('StatusOrderProcessedShort');
	if ($viewstatut == -1)
	$title .= ' - '.$langs->trans('StatusOrderCanceledShort');
	if ($viewstatut == -2)
	$title .= ' - '.$langs->trans('StatusOrderToProcessShort');
	if ($viewstatut == -3)
	$title .= ' - '.$langs->trans('StatusOrderValidated').', '.(empty($conf->expedition->enabled) ? '' : $langs->trans("StatusOrderSent").', ').$langs->trans('StatusOrderToBill').', '.$langs->trans('StatusOrderFacture');
	if ($viewstatut == -4)
	$title .= ' - '.$langs->trans('StatusOrderFacture');

	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/commande/card.php?id='.$id);
		exit;
	}

	llxHeader('', $title, $help_url);

	$param = '';

	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall)					$param .= '&sall='.urlencode($sall);
	if ($socid > 0)             $param .= '&socid='.urlencode($socid);
	if ($viewstatut != '')      $param .= '&viewstatut='.urlencode($viewstatut);
	if ($search_orderday)      		$param .= '&search_orderday='.urlencode($search_orderday);
	if ($search_ordermonth)      		$param .= '&search_ordermonth='.urlencode($search_ordermonth);
	if ($search_orderyear)       		$param .= '&search_orderyear='.urlencode($search_orderyear);
	if ($search_deliveryday)   		$param .= '&search_deliveryday='.urlencode($search_deliveryday);
	if ($search_deliverymonth)   		$param .= '&search_deliverymonth='.urlencode($search_deliverymonth);
	if ($search_deliveryyear)    		$param .= '&search_deliveryyear='.urlencode($search_deliveryyear);
	if ($search_ref)      		$param .= '&search_ref='.urlencode($search_ref);
	if ($search_company)  		$param .= '&search_company='.urlencode($search_company);
	if ($search_ref_customer)	$param .= '&search_ref_customer='.urlencode($search_ref_customer);
	if ($search_user > 0) 		$param .= '&search_user='.urlencode($search_user);
	if ($search_sale > 0) 		$param .= '&search_sale='.urlencode($search_sale);
	if ($search_total_ht != '') $param .= '&search_total_ht='.urlencode($search_total_ht);
	if ($search_total_vat != '')  $param .= '&search_total_vat='.urlencode($search_total_vat);
	if ($search_total_ttc != '')  $param .= '&search_total_ttc='.urlencode($search_total_ttc);
	if ($search_project_ref >= 0) $param .= "&search_project_ref=".urlencode($search_project_ref);
	if ($search_town != '')       $param .= '&search_town='.urlencode($search_town);
	if ($search_zip != '')        $param .= '&search_zip='.urlencode($search_zip);
	if ($search_state != '')      $param .= '&search_state='.urlencode($search_state);
	if ($search_country != '')    $param .= '&search_country='.urlencode($search_country);
	if ($search_type_thirdparty != '')  $param .= '&search_type_thirdparty='.urlencode($search_type_thirdparty);
	if ($search_product_category != '') $param .= '&search_product_category='.urlencode($search_product_category);
	if ($search_categ_cus > 0)          $param .= '&search_categ_cus='.urlencode($search_categ_cus);
	if ($show_files)            $param .= '&show_files='.urlencode($show_files);
	if ($optioncss != '')       $param .= '&optioncss='.urlencode($optioncss);
	if ($billed != '')			$param .= '&billed='.urlencode($billed);
	if ($search_shipments != '')    $param .= '&search_shipments='.urlencode($search_shipments);
	if ($search_comm_vs_ship != '') $param .= '&search_comm_vs_ship'.urlencode($search_comm_vs_ship);
	// Date order start and end
	if ($date_start_orderday) $param .= '&date_start_orderday='.urlencode($date_start_orderday);
	if ($date_start_ordermonth) $param .= '&date_start_ordermonth='.urlencode($date_start_ordermonth);
	if ($date_start_orderyear) $param .= '&date_start_orderyear='.urlencode($date_start_orderyear);
	if ($date_end_orderday) $param .= '&date_end_orderday='.urlencode($date_end_orderday);
	if ($date_end_ordermonth) $param .= '&date_end_ordermonth='.urlencode($date_end_ordermonth);
	if ($date_end_orderyear) $param .= '&date_end_orderyear='.urlencode($date_end_orderyear);

	if($search_total_pieces) $param .= '&search_total_pieces='.urlencode($search_total_pieces);


	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
		'builddoc'=>$langs->trans("PDFMerge"),
		'cancelorders'=>$langs->trans("Cancel"),
	    'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->facture->creer) $arrayofmassactions['createbills'] = $langs->trans("CreateInvoiceForThisCustomer");
	if ($user->rights->commande->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete', 'createbills'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	
	//$newcardbutton .= dolGetButtonTitle($langs->trans('FactureMassive'), '', 'fa fa-play', DOL_URL_ROOT.'/commande/list.php?massaction=facturemassive');

	/*if ($contextpage == 'orderlist' && $user->rights->commande->creer)
	{
        $newcardbutton .= dolGetButtonTitle($langs->trans('NewOrder'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/commande/card.php?action=create');
    }*/

	// Lines of title fields
	print '<form method="POST" id="FormularioExportacion" action="orders_shipments_report.php">';
    print '<input type="hidden" name="action" value="generate_report">';
	print '<input type="hidden" name="sqlexport" value="'.base64_encode($sql).'">';
    print '</form>';

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'commercial', 0, $newcardbutton, '', $limit);
	print '<table width="100%">';
    print '<tr>';
    print '<td align="left">';
    print '<a name="exportar" id="exportar"><img src="../compta/facture/img/xlsx.png" alt=""></a>';
    print '<script language="javascript">
				$(document).ready(function() {
					$("#exportar").on("click",function() {
						$("#FormularioExportacion").submit();
					});
				});
    		</script>';
    print '</td>';
    print '</tr>';
    print '</table>';

	$topicmail = "SendOrderRef";
	$modelmail = "order_send";
	$objecttmp = new Commande($db);
	$trackid = 'ord'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($massaction == 'createbills')
	{
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createbills">';

		print '<table class="noborder" width="100%" >';
		print '<tr>';
		print '<td class="titlefield">';
		print $langs->trans('DateInvoice');
		print '</td>';
		print '<td>';
		print $form->selectDate('', '', '', '', '', '', 1, 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('CreateOneBillByThirdNewtrans');
		print '</td>';
		print '<td>';
		print $form->selectyesno('createbills_onebythird', '', 1);
		print '</td>';
		print '</tr>';
		/*print '<tr>';
		print '<td>';
		print $langs->trans('ValidateInvoices');
		print '</td>';
		print '<td>';
		if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_BILL))
		{
			print $form->selectyesno('validate_invoices', 0, 1, 1);
			print ' ('.$langs->trans("AutoValidationNotPossibleWhenStockIsDecreasedOnInvoiceValidation").')';
		}
		else
		{
			print $form->selectyesno('validate_invoices', 0, 1);
		}
		if (!empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER)) print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("IfValidateInvoiceIsNoOrderStayUnbilled").'</span>';
		else print ' &nbsp; &nbsp; <span class="opacitymedium">'.$langs->trans("OptionToSetOrderBilledNotEnabled").'</span>';
		print '</td>';
		print '</tr>';*/
		print '</table>';

		print '<br>';
		print '<div class="center">';
		print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisCustomer').'">  ';
		print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
		print '</div>';
		print '<br>';
	}

	if(GETPOST('massaction') == 'facturemassive'){
		//Permisos Facturación Masiva
		if($user->rights->facture->facture_pendant == '1'){
			$object->factureMassive();
		}else{
			setEventMessage("No Cuenta con Permisos para Facturación Masiva", "errors");
		}
	}

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$moreforfilter = '';

 	// If the user can view prospects other than his'
 	/*if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
		$moreforfilter .= '<div class="divsearchfield">';
 		$moreforfilter .= $langs->trans('ThirdPartiesOfSaleRepresentative').': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	 	$moreforfilter .= '</div>';
 	}
	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('LinkedToSpecificUsers').': ';
		$moreforfilter .= $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
	 	$moreforfilter .= '</div>';
	}
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('IncludingProductWithTag').': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter .= $form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, 'maxwidth300', 1);
		$moreforfilter .= '</div>';
	}
	if (!empty($conf->categorie->enabled))
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter .= '<div class="divsearchfield">';
	 	$moreforfilter .= $langs->trans('CustomersProspectsCategoriesShort').': ';
		$moreforfilter .= $formother->select_categories('customer', $search_categ_cus, 'search_categ_cus', 1);
	 	$moreforfilter .= '</div>';
	}*/
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
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table id="table_report" name="table_report" class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	
	// Thirpdarty
	if (!empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="40" name="search_company" value="'.$search_company.'">';
		print '</td>';
	}
	// Project ref
	if (!empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project_ref" value="'.$search_project_ref.'"></td>';
	}
	// Ref
	if (!empty($arrayfields['c.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="18" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}
	// Ref customer
	if (!empty($arrayfields['c.ref_client']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="10" name="search_ref_customer" value="'.$search_ref_customer.'">';
		print '</td>';
	}
	// Project title
	if (!empty($arrayfields['p.title']['checked']))
	{
	    print '<td class="liste_titre"><input type="text" class="flat" size="6" name="search_project" value="'.$search_project.'"></td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (!empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';
	// State
	if (!empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
		print '</td>';
	}
	// Country
	if (!empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (!empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone" align="center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT));
		print '</td>';
	}
	// Date order
	if (!empty($arrayfields['c.date_commande']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if($date_start_orderyear) 
			$date_start_order = dol_mktime(0, 0, 0, $date_start_ordermonth, $date_start_orderday, $date_start_orderyear);
		else
			$date_start_order = null;
		if($date_end_orderyear)
			$date_end_order = dol_mktime(0, 0, 0, $date_end_ordermonth, $date_end_orderday, $date_end_orderyear);
		else
			$date_end_order = null;
		print "<span>Desde</span><br>".$form->select_date($date_start_order,'date_start_order',0,0,0,'',1,0,1);
        print "<br><span>Hasta</span><br>".$form->select_date($date_end_order,'date_end_order',0,0,0,'',1,0,1);
		print '</td>';
	}

	// Total de Piezas
	if (!empty($arrayfields['cd.total_qty']['checked']))
	{
		print '<td class="liste_titre center">';
		print '<input class="flat" type="text" size="4" name="search_total_pieces" value="'.$search_total_pieces.'">';
		print '</td>';
	}

	if (!empty($arrayfields['c.date_delivery']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_deliveryday" value="'.$search_deliveryday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_deliverymonth" value="'.$search_deliverymonth.'">';
		$formother->select_year($search_deliveryyear ? $search_deliveryyear : -1, 'search_deliveryyear', 1, 20, 5);
		print '</td>';
	}
	if (!empty($arrayfields['c.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_total_ht" value="'.$search_total_ht.'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.total_vat']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_total_vat" value="'.$search_total_vat.'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
		print '</td>';
	}
	if (!empty($arrayfields['shipments']['checked']))
	{
		// Shipments
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_shipments" value="'.$search_shipments.'">';
		print '</td>';
	}
	if (!empty($arrayfields['ttc_shipments']['checked']))
	{
		// Shipments Importe
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_ttc_shipments" value="'.$search_ttc_shipments.'">';
		print '</td>';
	}
	if (!empty($arrayfields['comm_vs_ship']['checked']))
	{
		// Shipments Importe
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="5" name="search_comm_vs_ship" value="'.$search_comm_vs_ship.'">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['c.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['c.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date cloture
	if (!empty($arrayfields['c.date_cloture']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['c.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		$liststatus = array(
			Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraftShort"),
			Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"),
			Commande::STATUS_SHIPMENTONPROCESS=>$langs->trans("StatusOrderSentShort"),
			Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"),
			-3=>$langs->trans("StatusOrderValidatedShort").'+'.$langs->trans("StatusOrderSentShort").'+'.$langs->trans("StatusOrderDelivered").'+'.$langs->trans("StatusOrderFacture"),
			-4=>$langs->trans("StatusOrderFacture"),
			Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceledShort")
		);
		print $form->selectarray('viewstatut', $liststatus, $viewstatut, -5, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
		print '</td>';
	}
	// Status billed
	if (!empty($arrayfields['c.facture']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		//print $form->selectyesno('billed', $billed, 1, 0, 1);
		print ' <select class="flat" id="billed" name="billed">
					<option value="-1" '.($billed == -1 ?'selected':'').'>&nbsp;</option>
					<option value="3" '.($billed == 3 ?'selected':'').'>No</option>
					<option value="1" '.($billed == 1 ?'selected':'').'>Sí</option>
					<option value="2" '.($billed == 2 ?'selected':'').'>Sí(SxF)</option>
				</select> ';
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (!empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.ref']['checked'])) 	          print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['c.ref']['checked']))            print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], 'c.ref', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['c.ref_client']['checked']))     print_liste_field_titre($arrayfields['c.ref_client']['label'], $_SERVER["PHP_SELF"], 'c.ref_client', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.title']['checked'])) 	      print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], "p.title", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['c.date_commande']['checked']))  print_liste_field_titre($arrayfields['c.date_commande']['label'], $_SERVER["PHP_SELF"], 'c.date_commande', '', $param, '', $sortfield, $sortorder, 'center ');
	// Total de piezas
	if(!empty($arrayfields['cd.total_qty']['checked']))      print_liste_field_titre($arrayfields['cd.total_qty']['label'], $_SERVER["PHP_SELF"], 'cd.total_qty', '', $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['c.date_delivery']['checked']))  print_liste_field_titre($arrayfields['c.date_delivery']['label'], $_SERVER["PHP_SELF"], 'c.date_livraison', '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['c.total_ht']['checked']))       print_liste_field_titre($arrayfields['c.total_ht']['label'], $_SERVER["PHP_SELF"], 'c.total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['c.total_vat']['checked']))      print_liste_field_titre($arrayfields['c.total_vat']['label'], $_SERVER["PHP_SELF"], 'c.tva', '', $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['c.total_ttc']['checked']))      print_liste_field_titre($arrayfields['c.total_ttc']['label'], $_SERVER["PHP_SELF"], 'c.total_ttc', '', $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['shipments']['checked']))		 print_liste_field_titre($arrayfields['shipments']['label'], "", '', '', $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['ttc_shipments']['checked']))	 print_liste_field_titre($arrayfields['ttc_shipments']['label'], "", '', '', $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['comm_vs_ship']['checked']))	 print_liste_field_titre($arrayfields['comm_vs_ship']['label'], "", '', '', $param, '', $sortfield, $sortorder, 'right ');
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['c.datec']['checked']))     print_liste_field_titre($arrayfields['c.datec']['label'], $_SERVER["PHP_SELF"], "c.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['c.tms']['checked']))       print_liste_field_titre($arrayfields['c.tms']['label'], $_SERVER["PHP_SELF"], "c.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['c.date_cloture']['checked']))       print_liste_field_titre($arrayfields['c.date_cloture']['label'], $_SERVER["PHP_SELF"], "c.date_cloture", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['c.fk_statut']['checked'])) print_liste_field_titre($arrayfields['c.fk_statut']['label'], $_SERVER["PHP_SELF"], "c.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['c.facture']['checked']))   print_liste_field_titre($arrayfields['c.facture']['label'], $_SERVER["PHP_SELF"], 'c.facture', '', $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
	print '</tr>'."\n";

	$total = 0;
	$subtotal = 0;
	$productstat_cache = array();
	$getNomUrl_cache = array();

	$generic_commande = new Commande($db);
	$generic_product = new Product($db);

	/**
	 * SQL Like operator in PHP.
	 * Returns TRUE if match else FALSE.
	 * @param string $pattern
	 * @param string $subject
	 * @return bool
	 */
	function like_match($pattern, $subject)
	{
		$pattern = str_replace('%', '.*', preg_quote($pattern, '/'));
		return (bool) preg_match("/^{$pattern}$/i", $subject);
	}

	$i = 0;
	$totalarray = array();
	$column_counter = 0;
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$notshippable = 0;
		$warning = 0;
		$text_info = '';
		$text_warning = '';
		$nbprod = 0;

        $companystatic->id = $obj->socid;
		$companystatic->code_client = $obj->code_client;
		$companystatic->name = $obj->name;
		$companystatic->client = $obj->client;
		$companystatic->email = $obj->email;
		if (!isset($getNomUrl_cache[$obj->socid])) {
		    $getNomUrl_cache[$obj->socid] = $companystatic->getNomUrl(1, 'customer');
		}

		$generic_commande->id = $obj->rowid;
		$generic_commande->ref = $obj->ref;
		$generic_commande->statut = $obj->fk_statut;
		$generic_commande->date = $db->jdate($obj->date_commande);
		$generic_commande->date_livraison = $db->jdate($obj->date_delivery);
		$generic_commande->ref_client = $obj->ref_client;
		$generic_commande->total_ht = $obj->total_ht;
		$generic_commande->total_tva = $obj->total_tva;
		$generic_commande->total_ttc = $obj->total_ttc;
		$generic_commande->note_public = $obj->note_public;
		$generic_commande->note_private = $obj->note_private;

		$projectstatic->id = $obj->project_id;
		$projectstatic->ref = $obj->project_ref;
		$projectstatic->title = $obj->project_label;

		//Array de Envíos que Coinciden en una Búsqueda
		$shipments_search = array();

		//Obtención de Envíos Relacionados a un Pedido a Cliente
		$sqlShipments = "SELECT DISTINCT e.rowid, e.ref
		FROM llx_element_element ee
		LEFT JOIN llx_expedition e ON ( e.rowid = ee.fk_target AND ee.targettype = 'shipping' )
		WHERE ee.fk_source = ".$generic_commande->id." AND ee.sourcetype = 'commande' AND e.rowid IS NOT NULL
		";

		//Búsqueda de Envíos
		if(!empty($search_shipments)) $sqlShipments .= " AND e.ref LIKE '%".$search_shipments."%'";

		$numShipments = 0;
		if($resShipments = $db->query($sqlShipments)){
			$numShipments = $db->num_rows($resShipments);
		}

		//Búsqueda de Importe de Envío
		$numTTC = 0;
		if(!empty($search_ttc_shipments) && $numShipments == '1'){
			$objShip = $db->fetch_object($resShipments);
			$sqlTTC = "SELECT res_total_ttc.* FROM (SELECT SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee WHERE ee.targettype = 'shipping' AND ee.sourcetype = 'commande' AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND e.rowid = ".$objShip->rowid.") as res_total_ttc WHERE total_ttc LIKE '".$search_ttc_shipments."%'";
			
			if($resTTC = $db->query($sqlTTC)){
				$numTTC = $db->num_rows($resTTC);
			}			
		}

		if(!empty($search_ttc_shipments) && $numShipments > '1'){
			$counter = 0;
			while($objShip = $db->fetch_object($resShipments)){
				$sqlTTC = "SELECT res_total_ttc.* FROM (SELECT SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee WHERE ee.targettype = 'shipping' AND ee.sourcetype = 'commande' AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND e.rowid = ".$objShip->rowid.") as res_total_ttc WHERE total_ttc LIKE '".$search_ttc_shipments."%'";
			
				if($resTTC = $db->query($sqlTTC)){
					$numTTC = $db->num_rows($resTTC);

					//Tiene un Importe como el que se Busca
					if($numTTC > 0){
						array_push($shipments_search, $objShip->rowid);
						$counter++;
					}
				}
			}
			$numShipments = $counter;
			$numTTC = $counter;				
		}

		//Búsqueda de Diferencia de Comm vs Ship
		$diffCommShip = 0;
		$comm_ship_match = false;
		if(!empty($search_comm_vs_ship) && $numShipments == '1'){
			$objCommVsShip = $db->fetch_object($resShipments);
			$sqlCommVsShip = "SELECT SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee WHERE ee.targettype = 'shipping' AND ee.sourcetype = 'commande' AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND e.rowid = ".$objCommVsShip->rowid;
			
			if($resCommVsShip = $db->query($sqlCommVsShip)){
				$diffCommShip = str_replace(",", "", (price($generic_commande->total_ttc - ($db->fetch_object($resCommVsShip)->total_ttc))));
			}

			$comm_ship_match = like_match($search_comm_vs_ship.'%', $diffCommShip);
		}

		if(!empty($search_comm_vs_ship) && $numShipments > '1'){
			$counter_comm_vs_ship = 0;

			if($resShipments = $db->query($sqlShipments)){

				$shipments_search = array();
				
				while($objCommVsShip = $db->fetch_object($resShipments)){
					$sqlCommVsShip = "SELECT SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee WHERE ee.targettype = 'shipping' AND ee.sourcetype = 'commande' AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid AND ed.fk_origin_line = cd.rowid AND e.rowid = ".$objCommVsShip->rowid;
	
					if($resCommVsShip = $db->query($sqlCommVsShip)){
						$diffCommShip = str_replace(",", "", (price($generic_commande->total_ttc - ($db->fetch_object($resCommVsShip)->total_ttc))));
	
						if(like_match($search_comm_vs_ship.'%', $diffCommShip)){
							array_push($shipments_search, $objCommVsShip->rowid);
							$counter_comm_vs_ship++;
						}
					}
				}

				$numShipments = $counter_comm_vs_ship;
				$numTTC = $counter_comm_vs_ship;
			}
		}

		if(((!(!empty($search_shipments) && $numShipments < '1')) && (!(!empty($search_ttc_shipments) && $numTTC < '1')) && ((!empty($search_comm_vs_ship) && ( like_match($search_comm_vs_ship.'%', $diffCommShip) || $counter_comm_vs_ship > 0)) || empty($search_comm_vs_ship)) )){

			print '<tr class="oddeven">';

			// Third party
			if (!empty($arrayfields['s.nom']['checked']))
			{
				print '<td class="tdoverflowmax200">';
				print $getNomUrl_cache[$obj->socid];

				// If module invoices enabled and user with invoice creation permissions
				if (!empty($conf->facture->enabled) && !empty($conf->global->ORDER_BILLING_ALL_CUSTOMER))
				{
					if ($user->rights->facture->creer)
					{
						if (($obj->fk_statut > 0 && $obj->fk_statut < 3) || ($obj->fk_statut == 3 && $obj->billedTemp == 0))
						{
							print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$companystatic->id.'">';
							print img_picto($langs->trans("CreateInvoiceForThisCustomer").' : '.$companystatic->name, 'object_bill', 'hideonsmartphone').'</a>';
						}
					}
				}
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			
			// Project ref
			if (!empty($arrayfields['p.ref']['checked']))
			{
				print '<td class="nowrap">';
				if ($obj->project_id > 0)
				{
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Ref
			if (!empty($arrayfields['c.ref']['checked']))
			{
				print '<td class="nowrap">';

				$generic_commande->getLinesArray();		// This set ->lines

				print $generic_commande->getNomUrl(1, ($viewstatut != 2 ? 0 : $obj->fk_statut), 0, 0, 0, 1, 1);

				// Show shippable Icon (create subloop, so may be slow)
				if ($conf->stock->enabled)
				{
					$langs->load("stocks");
					if (($obj->fk_statut > 0) && ($obj->fk_statut < 3))
					{
						$numlines = count($generic_commande->lines); // Loop on each line of order
						for ($lig = 0; $lig < $numlines; $lig++)
						{
							if ($generic_commande->lines[$lig]->product_type == 0 && $generic_commande->lines[$lig]->fk_product > 0)  // If line is a product and not a service
							{
								$nbprod++; // order contains real products
								$generic_product->id = $generic_commande->lines[$lig]->fk_product;

								// Get local and virtual stock and store it into cache
								if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product])) {
									$generic_product->load_stock('nobatch');
									//$generic_product->load_virtual_stock();   Already included into load_stock
									$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_reel;
									$productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
								} else {
									$generic_product->stock_reel = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'];
									$generic_product->stock_theorique = $productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
								}

								if (empty($conf->global->SHIPPABLE_ORDER_ICON_IN_LIST))  // Default code. Default is when this option is not set, setting it create strange result
								{
									$text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
									$text_info .= ' - '.$langs->trans("Stock").': '.$generic_product->stock_reel;
									$text_info .= ' - '.$langs->trans("VirtualStock").': '.$generic_product->stock_theorique;
									$text_info .= '<br>';

									if ($generic_commande->lines[$lig]->qty > $generic_product->stock_reel)
									{
										$notshippable++;
									}
								}
								else {  // Detailed code, looks bugged
									// stock order and stock order_supplier
									$stock_order = 0;
									$stock_order_supplier = 0;
									if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE))    // What about other options ?
									{
										if (!empty($conf->commande->enabled))
										{
											if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'])) {
												$generic_product->load_stats_commande(0, '1,2');
												$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'] = $generic_product->stats_commande['qty'];
											} else {
												$generic_product->stats_commande['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'];
											}
											$stock_order = $generic_product->stats_commande['qty'];
										}
										if (!empty($conf->fournisseur->enabled))
										{
											if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'])) {
												$generic_product->load_stats_commande_fournisseur(0, '3');
												$productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'] = $generic_product->stats_commande_fournisseur['qty'];
											} else {
												$generic_product->stats_commande_fournisseur['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'];
											}
											$stock_order_supplier = $generic_product->stats_commande_fournisseur['qty'];
										}
									}
									$text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
									$text_stock_reel = $generic_product->stock_reel.'/'.$stock_order;
									if ($stock_order > $generic_product->stock_reel && !($generic_product->stock_reel < $generic_commande->lines[$lig]->qty)) {
										$warning++;
										$text_warning .= '<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
									}
									if ($generic_product->stock_reel < $generic_commande->lines[$lig]->qty) {
										$notshippable++;
										$text_info .= '<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
									} else {
										$text_info .= '<span class="ok">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
									}
									if (!empty($conf->fournisseur->enabled)) {
										$text_info .= '&nbsp;'.$langs->trans('SupplierOrder').'&nbsp;:&nbsp;'.$stock_order_supplier.'<br>';
									} else {
										$text_info .= '<br>';
									}
								}
							}
						}
						if ($notshippable == 0) {
							$text_icon = img_picto('', 'dolly', '', false, 0, 0, '', 'green paddingleft');
							$text_info = $langs->trans('Shippable').'<br>'.$text_info;
						} else {
							$text_icon = img_picto('', 'dolly', '', false, 0, 0, '', 'error paddingleft');
							$text_info = $langs->trans('NonShippable').'<br>'.$text_info;
						}
					}

					if ($nbprod)
					{
						print $form->textwithtooltip('', $text_info, 2, 1, $text_icon, '', 2);
					}
					if ($warning) {     // Always false in default mode
						print $form->textwithtooltip('', $langs->trans('NotEnoughForAllOrders').'<br>'.$text_warning, 2, 1, img_picto('', 'error'), '', 2);
					}
				}

				// Warning late icon and note
				if ($generic_commande->hasDelay()) {
					print img_picto($langs->trans("Late").' : '.$generic_commande->showDelay(), "warning");
				}

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->commande->multidir_output[$conf->entity].'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);

				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Ref customer
			if (!empty($arrayfields['c.ref_client']['checked']))
			{
				print '<td class="nowrap tdoverflowmax200">'.$obj->ref_client.'</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Project label
			if (!empty($arrayfields['p.title']['checked']))
			{
				print '<td class="nowrap">';
				if ($obj->project_id > 0)
				{
					print $projectstatic->title;
				}
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Town
			if (!empty($arrayfields['s.town']['checked']))
			{
				print '<td class="nocellnopadd">';
				print $obj->town;
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Zip
			if (!empty($arrayfields['s.zip']['checked']))
			{
				print '<td class="nocellnopadd">';
				print $obj->zip;
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// State
			if (!empty($arrayfields['state.nom']['checked']))
			{
				print "<td>".$obj->state_name."</td>\n";
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Country
			if (!empty($arrayfields['country.code_iso']['checked']))
			{
				print '<td class="center">';
				$tmparray = getCountry($obj->fk_pays, 'all');
				print $tmparray['label'];
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Type ent
			if (!empty($arrayfields['typent.code']['checked']))
			{
				print '<td class="center">';
				if (count($typenArray) == 0) $typenArray = $formcompany->typent_array(1);
				print $typenArray[$obj->typent_code];
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Order date
			if (!empty($arrayfields['c.date_commande']['checked']))
			{
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_commande), 'day');
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Total de piezas
			if(!empty($arrayfields['cd.total_qty']['checked']))
			{
				print '<td class="center">';
				print $obj->total_qty;
				print '</td>';
				if(!$column_counter) $totalarray['pos'][$totalarray['nbfield']] = 'cd.total_qty';
				$totalarray['val']['cd.total_qty'] += $obj->total_qty;
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Plannned date of delivery
			if (!empty($arrayfields['c.date_delivery']['checked']))
			{
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_delivery), 'day');
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Amount HT
			if (!empty($arrayfields['c.total_ht']['checked']))
			{
				print '<td class="right">'.price($obj->total_ht)."</td>\n";
				if (!$column_counter) $totalarray['pos'][$totalarray['nbfield']] = 'c.total_ht';
				$totalarray['val']['c.total_ht'] += $obj->total_ht;
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Amount VAT
			if (!empty($arrayfields['c.total_vat']['checked']))
			{
				print '<td class="right">'.price($obj->total_tva)."</td>\n";
				if (!$column_counter) $totalarray['pos'][$totalarray['nbfield']] = 'c.total_tva';
				$totalarray['val']['c.total_tva'] += $obj->total_tva;
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Amount TTC
			if (!empty($arrayfields['c.total_ttc']['checked']))
			{
				print '<td class="right">'.price($obj->total_ttc)."</td>\n";
				if (!$column_counter) $totalarray['pos'][$totalarray['nbfield']] = 'c.total_ttc';
				$totalarray['val']['c.total_ttc'] += $obj->total_ttc;
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Shipments
			if (!empty($arrayfields['shipments']['checked']))
			{
				print '<td class="right '.(($numShipments > '1') ? 'dropdown-toggle right" id="extra-toggle-'.$generic_commande->id.'" style="cursor: pointer;"' : '"').'>';
				
				if($numShipments == '1'){

					if(count($shipments_search) == '1'){
						$sqlShipments .= " AND e.rowid = ".$shipments_search[0];
					}
					$resShips = $db->query($sqlShipments);
					$objShipments = $db->fetch_object($resShips);
					if(!is_null($objShipments->rowid)){
						$shipment = new Expedition($db);
						$shipment->fetch($objShipments->rowid);
						print $shipment->getNomUrl(1);
					}			
				}

				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			$importe_envios = 0;
			if($numShipments == '1'){
				if($resShipments = $db->query($sqlShipments)){
					$obj_ship = $db->fetch_object($resShipments);
				}
				
				$sqlTotal = "SELECT SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc
							FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee
								WHERE ee.targettype = 'shipping' AND ee.sourcetype = 'commande' AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid
									AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid
									AND ed.fk_origin_line = cd.rowid
									AND e.rowid = ".$obj_ship->rowid;
				
				if($resTotal = $db->query($sqlTotal)){
					$obj_ship = $db->fetch_object($resTotal);
				}

				$importe_envios = $obj_ship->total_ttc;
				
			}
			
			// Shipments Importe
			if (!empty($arrayfields['ttc_shipments']['checked']))
			{
				print '<td class="right" id="importe_total_'.$generic_commande->id.'">';
				
				if($importe_envios > 0){
					print '$'.price($importe_envios);
				}
				
				print '</td>';

				if (!$column_counter) $totalarray['pos'][$totalarray['nbfield']] = 'ttc_shipments';
				$totalarray['val']['ttc_shipments'] += $importe_envios;
				if (!$column_counter) $totalarray['nbfield']++;
			}

			//Comm vs Ship
			if (!empty($arrayfields['comm_vs_ship']['checked'])){
				print '<td class="right" id="importe_pedido_'.$generic_commande->id.'">';
				$dif_comm_ship = 0;
				if($numShipments == '1'){
					$dif_comm_ship = $obj->total_ttc - $importe_envios;
					print '$'.price($dif_comm_ship);
				}
				print '</td>';

				if (!$column_counter) $totalarray['pos'][$totalarray['nbfield']] = 'comm_vs_ship';
				$totalarray['val']['comm_vs_ship'] += $dif_comm_ship;
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (!empty($arrayfields['c.datec']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Date modification
			if (!empty($arrayfields['c.tms']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Date cloture
			if (!empty($arrayfields['c.date_cloture']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_cloture), 'dayhour', 'tzuser');
				print '</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Status
			if (!empty($arrayfields['c.fk_statut']['checked']))
			{
				print '<td class="nowrap right">'.$generic_commande->LibStatut($obj->fk_statut, $obj->billedTemp, 5, 1).'</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}
			// Billed
			if (!empty($arrayfields['c.facture']['checked']))
			{
				//print '<td class="center">'.yn($obj->billed).'</td>';
				print '<td class="center">'.$obj->billed.'</td>';
				if (!$column_counter) $totalarray['nbfield']++;
			}

			// Action column
			print '<td class="nowrap" align="center">';
			if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			{
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			
			if (!$column_counter) $totalarray['nbfield']++;

			print "</tr>\n";
			$total_dropdowm_expeditions = 0;
			$orders_in_dropdows=false;
			if($numShipments > '1'){
				$orders_in_dropdows = true;
				if($resShipments = $db->query($sqlShipments)){

					while($obj_shipment = $db->fetch_object($resShipments)){

						if(!is_null($obj_shipment->rowid)){
								
							if(count($shipments_search) > 0){
								if(!in_array($obj_shipment->rowid, $shipments_search)){
									continue;
								}
							}
	
							print '<tr class="parent_'.$generic_commande->id.'">';
	
							if (!empty($arrayfields['s.nom']['checked'])){
								print '<td>&nbsp;</td>';
							}
			
							if (!empty($arrayfields['p.ref']['checked'])){
								print '<td>&nbsp;</td>';
							}
			
							if (!empty($arrayfields['c.ref']['checked'])){
								print '<td>&nbsp;</td>';
							}
			
							if (!empty($arrayfields['c.total_ttc']['checked'])){
								print '<td>&nbsp;</td>';
							}
							
							$shipment = new Expedition($db);
							$shipment->fetch($obj_shipment->rowid);
							print '<td class="right">'.$shipment->getNomUrl(1).'</td>';
	
							print '<td class="right">';
	
							$sqlTotal = "SELECT SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc
										FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee
											WHERE ee.targettype = 'shipping' AND ee.sourcetype = 'commande' AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid
												AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid
												AND ed.fk_origin_line = cd.rowid
												AND e.rowid = ".$obj_shipment->rowid;
							
							if($resTotal = $db->query($sqlTotal)){
								$obj_shipment_ttc = $db->fetch_object($resTotal);
							}
	
							$importe_envios = $obj_shipment_ttc->total_ttc;
							
							print '$'.price($importe_envios);
							$total_dropdowm_expeditions+=$importe_envios;
	
							print '</td>';
	
							$totalarray['val']['ttc_shipments'] += $importe_envios;
	
							//Comm vs Ship
							print '<td class="right">';
							
							$dif_comm_ship = 0;
							$dif_comm_ship = ($obj->total_ttc - $importe_envios);
							if($orders_in_dropdows == true)
							{
								$diff_dropdown_comm_ship = $obj->total_ttc - $total_dropdowm_expeditions;
								if($diff_dropdown_comm_ship < 0) {$diff_dropdown_comm_ship = $diff_dropdown_comm_ship * -1;}
								print '<script>
								$(document).ready(function() {
									$("#importe_pedido_'.$generic_commande->id.'").html("<p>$'.price($diff_dropdown_comm_ship).'</p>");
								});
								</script>';
							}
							else
							{
								print '$'.price($dif_comm_ship);
							}
	
							print '</td>';

							$totalarray['val']['comm_vs_ship'] += $dif_comm_ship;
	
							print '<td>&nbsp;</td>';
			
							print '</tr>';
	
						}
						
					}
					print '<script>
						$(document).ready(function() {
							$("#importe_total_'.$generic_commande->id.'").html("<p>'.price($total_dropdowm_expeditions).'</p>");
						});
					</script>';
				}
			}

			print '<script>
						$(document).ready(function() {
							$("#extra-toggle-'.$generic_commande->id.'").on("click", function(event) {
								$(".parent_'.$generic_commande->id.'").fadeToggle("slow");
								$(this).parent().toggleClass("open");
							});

							$(".parent_'.$generic_commande->id.'").hide();
						});
					</script>';

			$total += $obj->total_ht;
			$subtotal += $obj->total_ht;

			$column_counter++;
		}
		$i++;
	}

	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print '</form>'."\n";

	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $user->rights->commande->lire;
	$delallowed = $user->rights->commande->creer;

	print $formfile->showdocuments('massfilesarea_orders', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
