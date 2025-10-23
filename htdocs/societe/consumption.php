<?php
/* Copyright (C) 2012-2013 Philippe Berthet     <berthet@systune.be>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015-2017 Ferran Marcet		<fmarcet@2byte.es>
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
 *	\file       htdocs/societe/consumption.php
 *  \ingroup    societe
 *	\brief      Add a tab on thirdparty view to list all products/services bought or sells by thirdparty
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');
$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

// Sort & Order fields
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='dateprint';

// Search fields
$sref=GETPOST("sref");
$sprod_fulldescr=GETPOST("sprod_fulldescr");
$month	= GETPOST('month', 'int');
$year	= GETPOST('year', 'int');

$month2	= GETPOST('month2', 'int');
$year2	= GETPOST('year2', 'int');
$search_project_ref = GETPOST('search_project_ref', 'alpha');

// Clean up on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
{
    $sref='';
    $sprod_fulldescr='';
    $year='';
    $month='';
	$year2='';
    $month2='';
	$search_project_ref = '';
}
// Customer or supplier selected in drop box
$thirdTypeSelect = GETPOST("third_select_id");
$type_element = GETPOST('type_element')?GETPOST('type_element'):'';

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "orders", "suppliers", "propal", "interventions", "contracts", "products"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('consumptionthirdparty'));


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$productstatic=new Product($db);

$title = $langs->trans("Referers", $object->name);
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

if (empty($socid))
{
	dol_print_error($db);
	exit;
}

$head = societe_prepare_head($object);
dol_fiche_head($head, 'consumption', $langs->trans("ThirdParty"), -1, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
	print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

//if ($conf->agenda->enabled && $user->rights->agenda->myactions->read) $elementTypeArray['action']=$langs->transnoentitiesnoconv('Events');

if ($object->client)
{
	print '<tr><td class="titlefield">';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
	print '</td></tr>';
	$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."facture where fk_soc = ".$socid;
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);
	$nbFactsClient = $obj->nb;
	$thirdTypeArray['customer']=$langs->trans("customer");

	$elementTypeArray['productSales']=$langs->transnoentitiesnoconv('Ventas por Producto');

	if ($conf->propal->enabled && $user->rights->propal->lire) $elementTypeArray['propal']=$langs->transnoentitiesnoconv('Cotizaciones');
	if ($conf->commande->enabled && $user->rights->commande->lire) $elementTypeArray['order']=$langs->transnoentitiesnoconv('Orders');
	if ($conf->facture->enabled && $user->rights->facture->lire) $elementTypeArray['invoice']=$langs->transnoentitiesnoconv('Invoices');
	if ($conf->contrat->enabled && $user->rights->contrat->lire) $elementTypeArray['contract']=$langs->transnoentitiesnoconv('Contracts');
}

if ($conf->ficheinter->enabled && $user->rights->ficheinter->lire) $elementTypeArray['fichinter']=$langs->transnoentitiesnoconv('Interventions');
$elementTypeArray['ticket'] = 'Tickets del POS';

if ($object->fournisseur)
{
	print '<tr><td class="titlefield">';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
	print '</td></tr>';
	$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."commande_fournisseur where fk_soc = ".$socid;
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);
	$nbCmdsFourn = $obj->nb;
	$thirdTypeArray['supplier']=$langs->trans("supplier");
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire) $elementTypeArray['supplier_invoice']=$langs->transnoentitiesnoconv('SuppliersInvoices');
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire) $elementTypeArray['supplier_order']=$langs->transnoentitiesnoconv('SuppliersOrders');
	if ($conf->fournisseur->enabled && $user->rights->supplier_proposal->lire) $elementTypeArray['supplier_proposal']=$langs->transnoentitiesnoconv('SupplierProposals');
}
print '</table>';

print '</div>';

dol_fiche_end();
print '<br>';


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

$sql_select='';
/*if ($type_element == 'action')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	$documentstatic=new ActionComm($db);
	$sql_select = 'SELECT f.id as doc_id, f.id as doc_number, \'1\' as doc_type, f.datep as dateprint, ';
	$tables_from = MAIN_DB_PREFIX."actioncomm as f";
	$where = " WHERE rbl.parentid = f.id AND f.entity = ".$conf->entity;
	$dateprint = 'f.datep';
	$doc_number='f.id';
}*/

if ($type_element == 'productSales')
{ 	
	$sql_select = " SELECT * FROM (
	
			SELECT sm.rowid as smRowid, f.rowid as doc_id, f.ref as doc_number, SUBSTRING(f.date_valid,1,10) as dateprint, f.fk_statut as status, (sm.value * -1) as prod_qty, c.total_ht as total_ht, c.subprice as subprice,
					p.ref as ref, p.rowid as fk_product, p.fk_product_type as fk_product_type, p.entity as pentity, p.label as product_label, 1 as tipo, pr.ref as proyecto, pr.rowid as id_proy,fd.devolucion
					FROM llx_stock_mouvement as sm, llx_expeditiondet as fd, llx_product as p, llx_commandedet as c, llx_expedition as f, llx_commande as cm
					LEFT JOIN llx_projet as pr ON pr.rowid = cm.fk_projet
						WHERE sm.fk_origin = f.rowid and sm.origintype = 'shipping'
							and fd.fk_expedition = f.rowid and c.fk_product = sm.fk_product 
							and sm.fk_product = p.rowid and fd.fk_origin_line = c.rowid
							and c.fk_commande = cm.rowid
							and f.fk_soc = ".$socid."  
				UNION ALL

				SELECT sm.rowid as smRowid, f.rowid as doc_id, f.ticketnumber as doc_number, f.date_ticket as dateprint, f.fk_statut as status, (sm.value * -1) as prod_qty, fd.total_ht as total_ht, fd.subprice as subprice,
					p.ref as ref, p.rowid as fk_product, p.fk_product_type as fk_product_type, p.entity as pentity, p.label as product_label, 2 as tipo, pr.ref as proyecto, pr.rowid as id_proy,1 as devolucion
				
					FROM llx_stock_mouvement as sm, llx_pos_ticketdet as fd, llx_product as p, llx_pos_ticket as f 
					LEFT JOIN llx_projet as pr ON pr.rowid = f.fk_projet
						WHERE sm.fk_origin = f.rowid and sm.origintype = 'ticket'
							and fd.fk_ticket = f.rowid and fd.fk_product = sm.fk_product 
							and sm.fk_product = p.rowid
							and f.fk_soc = ".$socid."  
				UNION ALL

				SELECT sm.rowid as smRowid, f.rowid as doc_id, f.ref as doc_number, f.datef as dateprint, f.fk_statut as status, (sm.value * -1) as prod_qty, fd.total_ht as total_ht, fd.subprice as subprice,
					p.ref as ref, p.rowid as fk_product, p.fk_product_type as fk_product_type, p.entity as pentity, p.label as product_label, 3 as tipo, pr.ref as proyecto, pr.rowid as id_proy,1 as devolucion
				
					FROM llx_stock_mouvement as sm, llx_facturedet as fd, llx_product as p, llx_facture as f
					LEFT JOIN llx_projet as pr ON pr.rowid = f.fk_projet
						WHERE sm.fk_origin = f.rowid and sm.origintype = 'facture'
							and fd.fk_facture = f.rowid and fd.fk_product = sm.fk_product 
							and sm.fk_product = p.rowid
							and f.fk_soc = ".$socid." 

				) a  WHERE 1 = 1 ";
				if ($sprod_fulldescr)//FILTAR PRODUCTO
				{
					$sql_select.= "AND (ref LIKE '%".$db->escape($sprod_fulldescr)."%'";
					$sql_select.= " OR product_label LIKE '%".$db->escape($sprod_fulldescr)."%'";
					$sql_select.=") ";
				}
				if ($sref) $sql_select.= " AND doc_number LIKE '%".$db->escape($sref)."%' "; //FILTRO REF

				if ($month && $year && $month2 && $year2){ //FILTRO FECHA
					$sql_select .= " AND dateprint BETWEEN '".$db->idate(dol_get_first_day($year, $month, false))."' AND '".$db->idate(dol_get_last_day($year2, $month2, false))."' ";
				}

				if ($search_project_ref != '') $sql_select .= natural_search("proyecto", $search_project_ref); //FILTRO PROYECTO
				$sql_select.= "GROUP BY smRowid ORDER BY ref ASC";
		
}

if ($type_element == 'fichinter')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	$documentstatic=new Fichinter($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, \'1\' as doc_type, f.datec as dateprint, f.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."fichinter as f LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet as d ON d.fk_fichinter = f.rowid";	// Must use left join to work also with option that disable usage of lines.
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND f.entity = ".$conf->entity;
	$dateprint = 'f.datec';
	$doc_number='f.ref';
}
if ($type_element == 'invoice')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$documentstatic=new Facture($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, f.type as doc_type, f.datef as dateprint, f.fk_statut as status, f.paye as paid, ';
	$tables_from = MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."facturedet as d";
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_facture = f.rowid";
	$where.= " AND f.entity IN (".getEntity('invoice').")";
	$dateprint = 'f.datef';
	$doc_number='f.ref';
	$thirdTypeSelect='customer';
}
if ($type_element == 'propal')
{
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	$documentstatic=new Propal($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.datep as dateprint, c.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."propal as c,".MAIN_DB_PREFIX."propaldet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_propal = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.datep';
	$doc_number='c.ref';
	$thirdTypeSelect='customer';
}
if ($type_element == 'order')
{
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	$documentstatic=new Commande($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_commande as dateprint, c.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."commande as c,".MAIN_DB_PREFIX."commandedet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_commande = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.date_commande';
	$doc_number='c.ref';
	$thirdTypeSelect='customer';
}
if ($type_element == 'supplier_invoice')
{ 	// Supplier : Show products from invoices.
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	$documentstatic=new FactureFournisseur($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ref as doc_number, \'1\' as doc_type, f.datef as dateprint, f.fk_statut as status, f.paye as paid, ';
	$tables_from = MAIN_DB_PREFIX."facture_fourn as f,".MAIN_DB_PREFIX."facture_fourn_det as d";
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_facture_fourn = f.rowid";
	$where.= " AND f.entity = ".$conf->entity;
	$dateprint = 'f.datef';
	$doc_number='f.ref';
	$thirdTypeSelect='supplier';
}
if ($type_element == 'supplier_proposal')
{
    require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
    $documentstatic=new SupplierProposal($db);
    $sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_valid as dateprint, c.fk_statut as status, ';
    $tables_from = MAIN_DB_PREFIX."supplier_proposal as c,".MAIN_DB_PREFIX."supplier_proposaldet as d";
    $where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
    $where.= " AND d.fk_supplier_proposal = c.rowid";
    $where.= " AND c.entity = ".$conf->entity;
    $dateprint = 'c.date_valid';
    $doc_number='c.ref';
    $thirdTypeSelect='supplier';
}
if ($type_element == 'supplier_order')
{ 	// Supplier : Show products from orders.
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	$documentstatic=new CommandeFournisseur($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_valid as dateprint, c.fk_statut as status, ';
	$tables_from = MAIN_DB_PREFIX."commande_fournisseur as c,".MAIN_DB_PREFIX."commande_fournisseurdet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_commande = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.date_valid';
	$doc_number='c.ref';
	$thirdTypeSelect='supplier';
}
if ($type_element == 'contract')
{ 	// Order
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	$documentstatic=new Contrat($db);
	$documentstaticline=new ContratLigne($db);
	$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, \'1\' as doc_type, c.date_contrat as dateprint, d.statut as status, ';
	$tables_from = MAIN_DB_PREFIX."contrat as c,".MAIN_DB_PREFIX."contratdet as d";
	$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_contrat = c.rowid";
	$where.= " AND c.entity = ".$conf->entity;
	$dateprint = 'c.date_valid';
	$doc_number='c.ref';
	$thirdTypeSelect='customer';
}
if ($type_element == 'ticket')
{ 	// Customer : show products from invoices
	require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
	$documentstatic=new Ticket($db);
	$sql_select = 'SELECT f.rowid as doc_id, f.ticketnumber as doc_number, f.type as doc_type, f.date_creation as dateprint, f.fk_statut as status, f.paye as paid, ';
	$tables_from = MAIN_DB_PREFIX."pos_ticket as f,".MAIN_DB_PREFIX."pos_ticketdet as d";
	$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
	$where.= " AND d.fk_ticket = f.rowid";
	$where.= " AND f.entity IN (".getEntity('invoice').")";
	$dateprint = 'f.date_creation';
	$doc_number='f.ticketnumber';
	$thirdTypeSelect='customer';
}

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook

if (!empty($sql_select))
{
	$sql = $sql_select;

	if($type_element != 'productSales'){
	
		$sql.= ' d.description as description,';
		if ($type_element != 'fichinter' && $type_element != 'contract' && $type_element != 'supplier_proposal'&& $type_element != 'ticket') $sql.= ' d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.date_start, d.date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
		if ($type_element == 'supplier_proposal') $sql.= ' d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
		if ($type_element == 'contract') $sql.= ' d.label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, d.date_ouverture as date_start, d.date_cloture as date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
		if ($type_element == 'ticket') $sql.= ' NULL AS label, d.fk_product as product_id, d.fk_product as fk_product, d.info_bits, NULL as date_start, NULL as date_end, d.qty, d.qty as prod_qty, d.total_ht as total_ht, ';
		if ($type_element != 'fichinter') $sql.= ' p.ref as ref, p.rowid as prod_id, p.rowid as fk_product, p.fk_product_type as prod_type, p.fk_product_type as fk_product_type, p.entity as pentity,';
		$sql.= " s.rowid as socid ";
		if ($type_element != 'fichinter') $sql.= ", p.ref as prod_ref, p.label as product_label";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".$tables_from;
		if ($type_element != 'fichinter') $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON d.fk_product = p.rowid ';
		$sql.= $where;
		if ($type_element == 'ticket' )
		{
			$sql .= '  AND f.fk_facture IS NULL';
		}
		$sql.= dolSqlDateFilter($dateprint, 0, $month, $year);		//FILTRO FECHA
		if ($sref) $sql.= " AND ".$doc_number." LIKE '%".$db->escape($sref)."%'"; //FILTO REF

		if ($sprod_fulldescr)													//FILTR PRODUCTO
		{
			if (GETPOST('type_element') != 'productSales') $sql.= " AND (d.description LIKE '%".$db->escape($sprod_fulldescr)."%'";
			if (GETPOST('type_element') != 'fichinter') $sql.= " OR p.ref LIKE '%".$db->escape($sprod_fulldescr)."%'";
			if (GETPOST('type_element') != 'fichinter') $sql.= " OR p.label LIKE '%".$db->escape($sprod_fulldescr)."%'";
			$sql.=")";
		}

		$sql.= $db->order($sortfield, $sortorder);
	}
	

	$resql=$db->query($sql);
	$totalnboflines = $db->num_rows($resql);

	$sql.= $db->plimit($limit + 1, $offset);
}

$disabled=0;
$showempty=2;
if (empty($elementTypeArray) && ! $object->client && ! $object->fournisseur)
{
    $showempty=$langs->trans("ThirdpartyNotCustomerNotSupplierSoNoRef");
    $disabled=1;
}

// Define type of elements
$typeElementString = $form->selectarray("type_element", $elementTypeArray, GETPOST('type_element'), $showempty, 0, 0, '', 0, 0, $disabled, '', 'maxwidth150onsmartphone');
$button = '<input type="submit" class="button" name="button_third" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';

$param='';
$param.="&sref=".urlencode($sref);
$param.="&month=".urlencode($month);
$param.="&year=".urlencode($year);
$param.="&month2=".urlencode($month2);
$param.="&year2=".urlencode($year2);
$param.="&sprod_fulldescr=".urlencode($sprod_fulldescr);
$param.="&socid=".urlencode($socid);
$param.="&type_element=".urlencode($type_element);
$param.="&search_project_ref=".urlencode($search_project_ref);

$total_qty=0;
$subpriceCont = 0;

if ($sql_select)
{
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$num = $db->num_rows($resql);

	$param="&socid=".$socid."&type_element=".$type_element;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sprod_fulldescr) $param.= "&sprod_fulldescr=".urlencode($sprod_fulldescr);
	if ($sref) $param.= "&sref=".urlencode($sref);
	if ($month) $param.= "&month=".$month;
	if ($year) $param.= "&year=".$year;
	if ($month2) $param.= "&month2=".$month2;
	if ($year2) $param.= "&year2=".$year2;
	if ($search_project_ref) $param .= "&search_project_ref=".urlencode($search_project_ref);
	if ($optioncss != '') $param.='&optioncss='.$optioncss;

    print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '', 0, '', '', $limit);

    print '<div class="div-table-responsive-no-min">';
    print '<table class="liste centpercent">'."\n";

    // Filters
    print '<tr class="liste_titre">';
    print '<td class="liste_titre left">';
    print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
    print '</td>';
    print '<td class="liste_titre nowrap center">'; // date

	if($type_element == 'productSales'){
	print 'Desde: <br>';
    print $formother->select_month($month?$month:-1, 'month', 1, 0, 'valignmiddle');
    $formother->select_year($year?$year:-1, 'year', 1, 20, 1);
	print '<br>Hasta:<br>';
	print $formother->select_month($month2?$month2:-1, 'month2', 1, 0, 'valignmiddle');
    $formother->select_year($year2?$year2:-1, 'year2', 1, 20, 1);
	}else{
		print $formother->select_month($month?$month:-1, 'month', 1, 0, 'valignmiddle');
		$formother->select_year($year?$year:-1, 'year', 1, 20, 1);
	}

    print '</td>';
    print '<td class="liste_titre center">';
    print '</td>';
	print '<td class="liste_titre center">';
	if($type_element == 'productSales'){
		print '<input type="text" class="flat" size="6" name="search_project_ref" value="'.$search_project_ref.'">';
	}
    print '</td>';
    print '<td class="liste_titre left">';
    print '<input class="flat" type="text" name="sprod_fulldescr" size="15" value="'.dol_escape_htmltag($sprod_fulldescr).'">';
    print '</td>';
    print '<td class="liste_titre center">';
    print '</td>';
    print '<td class="liste_titre center">';
    print '</td>';
    print '<td class="liste_titre maxwidthsearch">';
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';
    print '</tr>';

    // Titles with sort buttons
    print '<tr class="liste_titre">';
	if ($type_element == 'productSales'){
		print_liste_field_titre('Ref', '', '', '', '', '', '', '', 'left ');
		print_liste_field_titre('Date', '', '', '', '', 'width="150"', '', '', 'center ');
		print_liste_field_titre('Status', '', '', '', '', '', '', '', 'center ');
		print_liste_field_titre('Project', '', '', '', '', '', '', '', 'center ');
		print_liste_field_titre('Product', '', '', '', '', '', '', '', 'left ');
		print_liste_field_titre('Quantity', '', '', '', '', '', '', '', 'right ');
		print_liste_field_titre('TotalHT', '', '', '', '', '', '', '', 'right ');
		print_liste_field_titre('UnitPrice', '', '', '', '', '', '', '', 'right ');
	}else{
		print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'doc_number', '', $param, '', $sortfield, $sortorder, 'left ');
		print_liste_field_titre('Date', $_SERVER['PHP_SELF'], 'dateprint', '', $param, 'width="150"', $sortfield, $sortorder, 'center ');
		print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'fk_statut', '', $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre('', '', '', '', '', '', '', '', 'center ');
		print_liste_field_titre('Product', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'left ');
		print_liste_field_titre('Quantity', $_SERVER['PHP_SELF'], 'prod_qty', '', $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre('TotalHT', $_SERVER['PHP_SELF'], 'total_ht', '', $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre('UnitPrice', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
	}
    
    print "</tr>\n";


	$i = 0;
	$prod = null;
	
	if ($type_element == 'productSales'){
		$colors = array();
		while (($objp = $db->fetch_object($resql)) && $i < min($num, $limit)){
			array_push($colors, $objp->fk_product);
			$i++;
		}
		$i = 0;
		$db->free($resql);
		$resql=$db->query($sql);
		if (!$resql) dol_print_error($db);
		$num = $db->num_rows($resql);

		$colorArray = array();
		foreach (array_count_values($colors) as $key => $value){
			if($value == 1){
				array_push($colorArray, $key);
			}
		}
	}
	$estilo = '';
	while (($objp = $db->fetch_object($resql)) && $i < min($num, $limit))
	{
		if ($type_element == 'productSales'){
			if($objp->tipo == 1){
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				$documentstatic=new Expedition($db);
			}
			if($objp->tipo == 2){
				require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
				$documentstatic=new Ticket($db);
			}
			if($objp->tipo == 3){
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$documentstatic=new Facture($db);
			}

			$projectstatic = new Project($db);
			$projectstatic->id = $objp->id_proy;
			$projectstatic->ref = $objp->proyecto;

			if(in_array($objp->fk_product,$colorArray)){
				$estilo = ' style="background-color:#ee9;" ';
			}else{
				$estilo = '';
			}
		}
	
		$documentstatic->id=$objp->doc_id;
		$documentstatic->ref=$objp->doc_number;
		$documentstatic->type=$objp->doc_type;
		$documentstatic->fk_statut=$objp->status;
		$documentstatic->fk_status=$objp->status;
		$documentstatic->statut=$objp->status;
		$documentstatic->status=$objp->status;
		$documentstatic->paye=$objp->paid;

		if (is_object($documentstaticline)) $documentstaticline->statut=$objp->status;

		if($prod !=  $objp->fk_product)
		{
			if (!is_null($prod) && $sumCt > 1)
			{
				echo '<tr class="odeven sumary'.$objp->fk_product.'"><td colspan="4" style="background-color:#ee9;"></td>'
				.'<td style="background-color:#ee9;">'.$text.'</td>'
				.'<td style="text-align:right;background-color:#ee9;">'.$sumCant.'</td>'
				.'<td style="text-align:right;background-color:#ee9;">'.number_format($sumSub,2,'.',',').'</td>'
				.'<td style="text-align:right;background-color:#ee9;">&nbsp;</td>'
				.'</tr>'
				;
			}
			$sumCant = 0;
			$sumSub = 0;
			$sumCt = 0;
			$prod = $objp->fk_product;
		}
		$sumCt++;
		if($objp->prod_qty>=0 || ( $objp->prod_qty< 0 && $objp->tipo!=1 && $objp->devolucion!=0) || ($objp->prod_qty< 0 && $objp->tipo==1 && $objp->devolucion!=0))
		{
			print '<tr class="oddeven detail'.$objp->fk_product.'">';
			print '<td class="nobordernopadding nowrap" '.$estilo.'>';
			print $documentstatic->getNomUrl(1);
			print '</td>';
			print '<td class="center" '.$estilo.'>'.dol_print_date($db->jdate($objp->dateprint), 'day').'</td>';

			// Status
			print '<td class="center" '.$estilo.'>';
			if ($type_element == 'contract')
			{
				print $documentstaticline->getLibStatut(2);
			}
			elseif ($type_element == 'ticket')
			{
				print $documentstatic->getLibStatut(1);
			}
			elseif ($type_element == 'productSales')
			{
				if($objp->tipo == 1){
					print $documentstatic->getLibStatut(2);
				}
				if($objp->tipo == 2){
					print $documentstatic->getLibStatut(1);
				}
				if($objp->tipo == 3){
					print $documentstatic->getLibStatut(2);
				}
			}
			else
			{
				print $documentstatic->getLibStatut(2);
			}
			print '</td>';

			//Project
			if ($type_element == 'productSales'){
				print '<td class="center" '.$estilo.'>';
				if ($objp->id_proy > 0){
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
			}else{
				print '<td></td>';
			}

			print '<td '.$estilo.'>';

			// Define text, description and type
			$text=''; $description=''; $type=0;

			// Code to show product duplicated from commonobject->printObjectLine
			if ($objp->fk_product > 0)
			{
				$product_static = new Product($db);

				$product_static->type=$objp->fk_product_type;
				$product_static->id=$objp->fk_product;
				$product_static->ref=$objp->ref;
				$product_static->entity=$objp->pentity;
				$text=$product_static->getNomUrl(1);
			}

			// Product
			if ($objp->fk_product > 0)
			{
				// Define output language
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
				{
					$prod = new Product($db);
					$prod->fetch($objp->fk_product);

					$outputlangs = $langs;
					$newlang='';
					if (empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang=GETPOST('lang_id', 'aZ09');
					if (empty($newlang)) $newlang=$object->default_lang;
					if (! empty($newlang))
					{
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
				}
				else
				{
					$label = $objp->product_label;
				}

				$text.= ' - '.(! empty($objp->label)?$objp->label:$label);
				$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($objp->description));
			}

			if (($objp->info_bits & 2) == 2) { ?>
				<a href="<?php echo DOL_URL_ROOT.'/comm/remx.php?id='.$object->id; ?>">
				<?php
				$txt='';
				print img_object($langs->trans("ShowReduc"), 'reduc').' ';
				if ($objp->description == '(DEPOSIT)') $txt=$langs->trans("Deposit");
				elseif ($objp->description == '(EXCESS RECEIVED)') $txt=$langs->trans("ExcessReceived");
				elseif ($objp->description == '(EXCESS PAID)') $txt=$langs->trans("ExcessPaid");
				//else $txt=$langs->trans("Discount");
				print $txt;
				?>
				</a>
				<?php
				if ($objp->description)
				{
					if ($objp->description == '(CREDIT_NOTE)' && $objp->fk_remise_except > 0)
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						echo ($txt?' - ':'').$langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
					}
					if ($objp->description == '(EXCESS RECEIVED)' && $objp->fk_remise_except > 0)
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						echo ($txt?' - ':'').$langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
					}
					elseif ($objp->description == '(EXCESS PAID)' && $objp->fk_remise_except > 0)
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						echo ($txt?' - ':'').$langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
					}
					elseif ($objp->description == '(DEPOSIT)' && $objp->fk_remise_except > 0)
					{
						$discount=new DiscountAbsolute($db);
						$discount->fetch($objp->fk_remise_except);
						echo ($txt?' - ':'').$langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
						// Add date of deposit
						if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec).')';
					}
					else
					{
						echo ($txt?' - ':'').dol_htmlentitiesbr($objp->description);
					}
				}
			}
			else
			{
				if ($objp->fk_product > 0) {
					echo $form->textwithtooltip($text, $description, 3, '', '', $i, 0, '');

					// Show range
					echo get_date_range($objp->date_start, $objp->date_end);

					// Add description in form
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
					{
						print (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}
				} else {
					if (! empty($objp->label) || ! empty($objp->description))
					{
						if ($type==1) $text = img_object($langs->trans('Service'), 'service');
						else $text = img_object($langs->trans('Product'), 'product');

						if (! empty($objp->label)) {
							$text.= ' <strong>'.$objp->label.'</strong>';
							echo $form->textwithtooltip($text, dol_htmlentitiesbr($objp->description), 3, '', '', $i, 0, '');
						} else {
							echo $text.' '.dol_htmlentitiesbr($objp->description);
						}
					}

					// Show range
					echo get_date_range($objp->date_start, $objp->date_end);
				}
			}
			print '</td>';

			//print '<td class="left">'.$prodreftxt.'</td>';
			if ($type_element == 'invoice' && $objp->doc_type == Facture::TYPE_CREDIT_NOTE) $objp->prod_qty=-($objp->prod_qty);

			print '<td class="right" '.$estilo.'>'.$objp->prod_qty.'</td>';

			if ($type_element == 'productSales' && $objp->tipo == 1){
				print '<td class="right" '.$estilo.'>'.price($objp->subprice*$objp->prod_qty).'</td>';
				$total_ht+=$objp->subprice*$objp->prod_qty;
				$sumSub +=$objp->subprice*$objp->prod_qty;
			}else{
				print '<td class="right" '.$estilo.'>'.price($objp->total_ht).'</td>';
				$total_ht+=$objp->total_ht;
				$sumSub +=$objp->total_ht;
			}

			if ($type_element == 'productSales' && $objp->tipo == 1){
				print '<td class="right" '.$estilo.'>'.price($objp->subprice).'</td>';
				$subpriceCont += $objp->subprice;
			}else{
				print '<td class="right" '.$estilo.'>'.price($objp->total_ht/(empty($objp->prod_qty)?1:$objp->prod_qty)).'</td>';
				$subpriceCont += $objp->total_ht/(empty($objp->prod_qty)?1:$objp->prod_qty);
			}

			print "</tr>\n";
			//TICKETS CANCELADOS EN VENTAS POR PRODUCTO NO TOMARLO
			if ($type_element == 'productSales' && $objp->tipo == 2 && $objp->status == 3){
				$objp->prod_qty = 0;
				$objp->total_ht = 0;
			}
			$sumCant += $objp->prod_qty;
			$total_qty+=$objp->prod_qty;

			$i++;
		}
	}
	

	print '<tr class="liste_total">';
	print '<td>' . $langs->trans('Total') . '</td>';
	print '<td colspan="4"></td>';
	print '<td class="right">' . $total_qty . '</td>';
	print '<td class="right">' . price($total_ht) . '</td>';
	print '<td class="right">' . price($subpriceCont) . '</td>';
	print "</table>";
	print '</div>';

	if ($num > $limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	}
	$db->free($resql);
}
elseif (empty($type_element) || $type_element == -1)
{
    print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

    print '<table class="liste centpercent">'."\n";
    // Titles with sort buttons
    print '<tr class="liste_titre">';
    print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'doc_number', '', $param, '', $sortfield, $sortorder, 'left ');
    print_liste_field_titre('Date', $_SERVER['PHP_SELF'], 'dateprint', '', $param, 'width="150"', $sortfield, $sortorder, 'center ');
    print_liste_field_titre('Status', $_SERVER['PHP_SELF'], 'fk_status', '', $param, '', $sortfield, $sortorder, 'center ');
    print_liste_field_titre('Product', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'left ');
    print_liste_field_titre('Quantity', $_SERVER['PHP_SELF'], 'prod_qty', '', $param, '', $sortfield, $sortorder, 'right ');
    print "</tr>\n";

	print '<tr class="oddeven"><td class="opacitymedium" colspan="5">'.$langs->trans("SelectElementAndClick", $langs->transnoentitiesnoconv("Search")).'</td></tr>';

	print "</table>";
}
else {
    print_barre_liste($langs->trans('ProductsIntoElements').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

    print '<table class="liste centpercent">'."\n";

	print '<tr class="oddeven"><td class="opacitymedium" colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';

	print "</table>";
}

print "</form>";

// End of page
llxFooter();
$db->close();
