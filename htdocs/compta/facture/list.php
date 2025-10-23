<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2015 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry         <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry   <jfefe@aternatik.fr>
 * Copyright (C) 2015-2016 Ferran Marcet         <fmarcet@2byte.es>
 * Copyright (C) 2017      Josep Lluís Amador    <joseplluis@lliuretic.cat>
 * Copyright (C) 2018      Charlene Benke        <charlie@patas-monkey.com>
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
 *	\file       htdocs/compta/facture/list.php
 *	\ingroup    facture
 *	\brief      List of customer invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
if (!empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$projectid = (GETPOST('projectid') ?GETPOST('projectid', 'int') : 0);

$id = (GETPOST('id', 'int') ?GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$t_toselect = GETPOST('t_toselect', 'array');
$search_userf = GETPOST('search_user_search', 'int');

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'invoicelist';

if ($contextpage == 'poslist')
{
    $_GET['optioncss'] = 'print';
}

$lineid = GETPOST('lineid', 'int');
$userid = GETPOST('userid', 'int');
$search_product_category = GETPOST('search_product_category', 'int');
$search_ref = GETPOST('sf_ref') ?GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_refcustomer = GETPOST('search_refcustomer', 'alpha');
$search_type = is_numeric(GETPOST('search_type', 'int'))?GETPOST('search_type', 'int'):-1;
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_vat = GETPOST('search_montant_vat', 'alpha');
$search_montant_localtax1 = GETPOST('search_montant_localtax1', 'alpha');
$search_montant_localtax2 = GETPOST('search_montant_localtax2', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$search_status = GETPOST('search_status', 'intcomma');
$search_paymentmode = GETPOST('search_paymentmode', 'int');
$search_paymentterms = GETPOST('search_paymentterms', 'int');
$search_module_source = GETPOST('search_module_source', 'alpha');
$search_pos_source = GETPOST('search_pos_source', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = trim(GETPOST("search_state"));
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_user = GETPOST('search_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
/*$search_day = GETPOST('search_day', 'int');
$search_month = GETPOST('search_month', 'int');
$search_year	= GETPOST('search_year', 'int');*/
//Desde Hasta fecha factura
$date_start_facturemonth=GETPOST("date_start_facturemonth");
$date_start_factureday=GETPOST("date_start_factureday");
$date_start_factureyear=GETPOST("date_start_factureyear");
$date_end_facturemonth=GETPOST("date_end_facturemonth");
$date_end_factureday=GETPOST("date_end_factureday");
$date_end_factureyear=GETPOST("date_end_factureyear");
//Fecha de vencimiento
$date_start_limitmonth=GETPOST("date_start_limitmonth");
$date_start_limitday=GETPOST("date_start_limitday");
$date_start_limityear=GETPOST("date_start_limityear");
$date_end_limitmonth=GETPOST("date_end_limitmonth");
$date_end_limitday=GETPOST("date_end_limitday");
$date_end_limityear=GETPOST("date_end_limityear");
/*$search_day_lim		= GETPOST('search_day_lim', 'int');
$search_month_lim = GETPOST('search_month_lim', 'int');
$search_year_lim	= GETPOST('search_year_lim', 'int');*/
$search_categ_cus = trim(GETPOST("search_categ_cus", 'int'));
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');
/* Filtro de CFDI */
$cfdi_filter = GETPOST("cfdi_filter", 'alpha')? GETPOST("cfdi_filter", 'int') : '0';
//Busqueda por telefono
$search_phone = GETPOST('search_phone', 'alpha');

$option = GETPOST('option');
$con30 = GETPOST('con30');
$con60 = GETPOST('con60');
$con90 = GETPOST('con90');
$con91 = GETPOST('con91');
if ($option == 'late_discount') {
	$search_status = '1';
}
$filtre = GETPOST('filtre', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (!$sortorder && !empty($conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER) && $search_status == '1') $sortorder = $conf->global->INVOICE_DEFAULT_UNPAYED_SORT_ORDER;
if (!$sortorder) $sortorder = 'DESC';
if (!$sortfield) $sortfield = 'ref';
$pageprev = $page - 1;
$pagenext = $page + 1;

// Security check
$fieldid = (!empty($ref) ? 'ref' : 'rowid');
if (!empty($user->socid)) $socid = $user->socid;
$result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid);

$diroutputmassaction = $conf->facture->dir_output.'/temp/massgeneration/'.$user->id;

$object = new Facture($db);

$now = dol_now('tzuser');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Facture($db);
$hookmanager->initHooks(array('invoicelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('facture');

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref'=>'Ref',
	'f.ref_client'=>'RefCustomer',
	'pd.description'=>'Description',
	's.nom'=>"ThirdParty",
	'f.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["f.note_private"] = "NotePrivate";

$checkedtypetiers = 0;
$arrayfields = array(
	'f.ref'=>array('label'=>"Ref", 'checked'=>1, 'position'=>5),
	'f.ref_client'=>array('label'=>"RefCustomer", 'checked'=>1, 'position'=>10),
	'f.type'=>array('label'=>"Type", 'checked'=>0, 'position'=>15),
	'f.date'=>array('label'=>"DateInvoice", 'checked'=>1, 'position'=>20),
	'f.date_lim_reglement'=>array('label'=>"DateDue", 'checked'=>1, 'position'=>25),
	'f.date_closing'=>array('label'=>"DateClosing", 'checked'=>0, 'position'=>30),
    's.earlypayment_validity'=>array('label'=>'Vencimiento por pronto pago', 'checked'=>1),
	'p.ref'=>array('label'=>"ProjectRef", 'checked'=>1, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1), 'position'=>40),
	'p.title'=>array('label'=>"Etiqueta proyecto", 'checked'=>0, 'enabled'=>(empty($conf->projet->enabled) ? 0 : 1), 'position'=>40),
	's.nom'=>array('label'=>"ThirdPartyList", 'checked'=>1, 'position'=>50),
	's.phone'=>array('label'=>"Phone", 'checked'=>1, 'position'=>50),
	's.town'=>array('label'=>"Town", 'checked'=>1, 'position'=>55),
	's.zip'=>array('label'=>"Zip", 'checked'=>1, 'position'=>60),
	'state.nom'=>array('label'=>"StateShort", 'checked'=>0, 'position'=>65),
	'country.code_iso'=>array('label'=>"Country", 'checked'=>0, 'position'=>70),
	'typent.code'=>array('label'=>"TypeContCredi", 'checked'=>$checkedtypetiers, 'position'=>75),
	'f.fk_mode_reglement'=>array('label'=>"PaymentMode", 'checked'=>1, 'position'=>80),
	'f.fk_cond_reglement'=>array('label'=>"PaymentConditionsShort", 'checked'=>1, 'position'=>85),
	'f.module_source'=>array('label'=>"Module", 'checked'=>($contextpage == 'poslist' ? 1 : 0), 'enabled'=>($conf->cashdesk->enabled || $conf->takepos->enabled || $conf->global->INVOICE_SHOW_POS), 'position'=>90),
	'f.pos_source'=>array('label'=>"Terminal", 'checked'=>($contextpage == 'poslist' ? 1 : 0), 'enabled'=>($conf->cashdesk->enabled || $conf->takepos->enabled || $conf->global->INVOICE_SHOW_POS), 'position'=>91),
	'f.total_ht'=>array('label'=>"Subtotal con descuento", 'checked'=>1, 'position'=>95),
    'discount'=>array('label'=>"Discount", 'checked'=>1, 'position'=>140),
	'credit'=>array('label'=>"Tarjeta de crédito", 'checked'=>1, 'position'=>140),
	'debit'=>array('label'=>"Tarjeta de débito", 'checked'=>1, 'position'=>140),
	'transfer'=>array('label'=>"Transferencia", 'checked'=>1, 'position'=>140),
	'cash'=>array('label'=>"Efectivo", 'checked'=>1, 'position'=>140),
	'ht_without_discount'=>array('label'=>"Subtotal sin descuento", 'checked'=>1, 'position'=>140),
	'f.total_vat'=>array('label'=>"AmountVAT", 'checked'=>0, 'position'=>100),
	'f.total_localtax1'=>array('label'=>$langs->transcountry("AmountLT1", $mysoc->country_code), 'checked'=>0, 'enabled'=>($mysoc->localtax1_assuj == "1"), 'position'=>110),
	'f.total_localtax2'=>array('label'=>$langs->transcountry("AmountLT2", $mysoc->country_code), 'checked'=>0, 'enabled'=>($mysoc->localtax2_assuj == "1"), 'position'=>120),
	'f.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>0, 'position'=>130),
	'dynamount_payed'=>array('label'=>"Received", 'checked'=>0, 'position'=>140),
	'rtp'=>array('label'=>"Saldo", 'checked'=>0, 'position'=>147), // Not enabled by default because slow
	'margin'=>array('label'=>"Margin", 'checked'=>1, 'position'=>148),
	'f.fk_user_valid'=>array('label'=>"User", 'checked'=>1, 'position'=>150), // Not enabled by default because slow
	'f.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	'f.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500),
	'f.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000),
	'f.commission'=>array('label'=>"Comisión", 'checked'=>1, 'position'=>149),
);

if ($conf->global->INVOICE_USE_SITUATION && $conf->global->INVOICE_USE_SITUATION_RETAINED_WARRANTY)
{
	$arrayfields['f.retained_warranty'] = array('label'=>$langs->trans("RetainedWarranty"), 'checked'=>0, 'position'=>86);
}

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
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_user = '';
	$search_sale = '';
	$search_product_category = '';
	$search_ref = '';
	$search_refcustomer = '';
	$search_type = '';
	$search_project_ref = '';
	$search_project = '';
	$search_societe = '';
	$search_montant_ht = '';
	$search_montant_vat = '';
	$search_montant_localtax1 = '';
	$search_montant_localtax2 = '';
	$search_montant_ttc = '';
	$search_status = '';
	$search_paymentmode = '';
	$search_paymentterms = '';
	$search_module_source = '';
	$search_pos_source = '';
	$search_town = '';
	$search_zip = "";
	$search_state = "";
	$search_type = '';
	$search_country = '';
	$search_type_thirdparty = '';
	/*$search_day = '';
	$search_year = '';
	$search_month = '';*/
	//Fecha factura
    $date_start_factureday = '';
    $date_start_facturemonth = '';
    $date_start_factureyear = '';
    $date_end_factureday = '';
    $date_end_facturemonth = '';
    $date_end_factureyear = '';
	$option = '';
	$filter = '';
	//Fecha de vencimiento
    $date_start_limitday = '';
    $date_start_limitmonth = '';
    $date_start_limityear = '';
    $date_end_limitday = '';
    $date_end_limitmonth = '';
    $date_end_limityear = '';
	/*$search_day_lim = '';
	$search_year_lim = '';
	$search_month_lim = '';*/
	$toselect = '';
	$search_array_options = array();
	$search_categ_cus = 0;
	$cfdi_filter = '0';
	$search_phone = '';
}

if (empty($reshook))
{
	$objectclass = 'Facture';
	$objectlabel = 'Invoices';
	$permissiontoread = $user->rights->facture->lire;
	$permissiontoadd = $user->rights->facture->creer;
	$permissiontodelete = $user->rights->facture->supprimer;
	$uploaddir = $conf->facture->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if($massaction == "pay_commission"){
	$arrayofselected = is_array($toselect) ? $toselect : array();
	$status_closed = True;
	$total_commission = 0;
	foreach ($arrayofselected as $toselectid){
		$objecttmp = new Facture($db);
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0){
			if($objecttmp->statut != Facture::STATUS_CLOSED){
				$status_closed = False;
				$error++;
				setEventMessages("Cuentas por Cobrar con estado diferente a 'Pagado' ", null, 'errors');
				break;
			}else{
				$total_commission += price($objecttmp->commission);
			}
		}
	}
	$ids = implode(",", $toselect);
	if($status_closed){
		header("Location: ".DOL_URL_ROOT."/expensereport/card.php?action=create&leftmenu=expensereport&mainmenu=hrm&commission=".$total_commission."&ids=".$ids);
		exit;
	}
}

if ($massaction == 'withdrawrequest')
{
	$langs->load("withdrawals");

	if (!$user->rights->prelevement->bons->creer)
	{
		$error++;
		setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
	}
	else
	{
		//Checking error
		$error = 0;

		$arrayofselected = is_array($toselect) ? $toselect : array();
		//$t_arrayofselected = is_array($t_toselect) ? $t_toselect : array();
		$listofbills = array();
		foreach ($arrayofselected as $toselectid)
		{
			$objecttmp = new Facture($db);
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0)
			{
				$totalpaye = $objecttmp->getSommePaiement();
				$totalcreditnotes = $objecttmp->getSumCreditNotesUsed();
				$totaldeposits = $objecttmp->getSumDepositsUsed();
				$objecttmp->resteapayer = price2num($objecttmp->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');
				if ($objecttmp->paye || $objecttmp->resteapayer == 0) {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("AlreadyPaid"), $objecttmp->errors, 'errors');
				} elseif ($objecttmp->resteapayer < 0) {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("AmountMustBePositive"), $objecttmp->errors, 'errors');
				}
				if (!($objecttmp->statut > Facture::STATUS_DRAFT)) {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("Draft"), $objecttmp->errors, 'errors');
				}

				$rsql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
				$rsql .= " , pfd.date_traite as date_traite";
				$rsql .= " , pfd.amount";
				$rsql .= " , u.rowid as user_id, u.lastname, u.firstname, u.login";
				$rsql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
				$rsql .= " , ".MAIN_DB_PREFIX."user as u";
				$rsql .= " WHERE fk_facture = ".$objecttmp->id;
				$rsql .= " AND pfd.fk_user_demande = u.rowid";
				$rsql .= " AND pfd.traite = 0";
				$rsql .= " ORDER BY pfd.date_demande DESC";

				$result_sql = $db->query($rsql);
				if ($result_sql)
				{
					$numprlv = $db->num_rows($result_sql);
				}

				if ($numprlv > 0) {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("RequestAlreadyDone"), $objecttmp->errors, 'warnings');
				}
				elseif (!empty($objecttmp->mode_reglement_code) && $objecttmp->mode_reglement_code != 'PRE') {
					$error++;
					setEventMessages($objecttmp->ref.' '.$langs->trans("BadPaymentMethod"), $objecttmp->errors, 'errors');
				}
				else {
					$listofbills[] = $objecttmp; // $listofbills will only contains invoices with good payment method and no request already done
				}
			}
		}

		//Massive withdraw request for request with no errors
		if (!empty($listofbills))
		{
			$nbwithdrawrequestok = 0;
			foreach ($listofbills as $aBill)
			{
				$db->begin();
				$result = $aBill->demande_prelevement($user, $aBill->resteapayer);
				if ($result > 0)
				{
					$db->commit();
					$nbwithdrawrequestok++;
				}
				else
				{
					$db->rollback();
					setEventMessages($aBill->error, $aBill->errors, 'errors');
				}
			}
			if ($nbwithdrawrequestok > 0)
			{
				setEventMessages($langs->trans("WithdrawRequestsDone", $nbwithdrawrequestok), null, 'mesgs');
			}
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formmargin = new FormMargin($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic = new Account($db);
$facturestaticc = new Facture($db);
$formcompany = new FormCompany($db);
$thirdpartystatic = new Societe($db);
$ticketstatic = new Ticket($db);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql .= ' f.rowid as id, f.fk_user_valid, f.ref, f.ref_client, f.type, ';
$sql .= ' CASE WHEN f.type = 0 THEN 2 
			WHEN f.type = 1 THEN 1
			WHEN f.type = 2 THEN 3
			WHEN f.type = 3 THEN 0
			END AS tipo, 
';
$sql .= ' f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.fk_cond_reglement, f.total as total_ht, f.tva as total_vat, f.total_ttc,';
$sql .= ' f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,';
$sql .= ' f.datef as df, f.date_lim_reglement as datelimite, f.module_source, f.pos_source,';
$sql .= ' f.paye as paye, f.fk_statut, f.close_code,';
$sql .= ' f.datec as date_creation, f.tms as date_update, f.date_closing as date_closing,';
$sql .= ' f.retained_warranty, f.retained_warranty_date_limit, f.situation_final, f.situation_cycle_ref, f.situation_counter,';
$sql .= ' s.rowid as socid, s.nom as name, s.phone, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur, s.earlypayment_validity,';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= " country.code as country_code,";
$sql .= " p.rowid as project_id, p.ref as project_ref, p.title as project_label,";
// We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
// TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
if (!$sall) $sql .= ' SUM(pf.amount) as dynamount_payed';
if ($sall) $sql .= ' null as dynamount_payed';
if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ', 0 AS is_ticket ';
$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
if (!empty($search_categ_cus)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cc ON s.rowid = cc.fk_soc"; // We'll need this table joined to the select in order to filter by categ

$sql .= ', '.MAIN_DB_PREFIX.'facture as f';
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (f.rowid = ef.fk_object)";
if (!$sall) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
if ($sall || $search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
if ($search_product_category > 0) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (!$user->rights->societe->client->voir && !$socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
	$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
if(!($user->rights->stock->show_all_warehouses)){
	$sql.= " JOIN ".MAIN_DB_PREFIX."entrepot as e ON e.lieu = SUBSTRING(f.ref, 1, 2) ";
}
$sql .= ' WHERE f.fk_soc = s.rowid';
        //."  AND f.fk_statut NOT IN (0) "    ;
if(!($user->rights->stock->show_all_warehouses)){
	$sql.= " AND e.rowid = ".$user->fk_warehouse." ";
}
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($search_product_category > 0) $sql .= " AND cp.fk_categorie = ".$db->escape($search_product_category);
if ($socid > 0) $sql .= ' AND s.rowid = '.$socid;
if ($search_userf > 0) $sql .= ' AND f.fk_user_valid = '.$search_userf;
if ($userid)
{
	if ($userid == -1) $sql .= ' AND f.fk_user_valid IS NULL';
	else $sql .= ' AND f.fk_user_valid = '.$userid;
}
if ($filtre)
{
	$aFilter = explode(',', $filtre);
	foreach ($aFilter as $filter)
	{
		$filt = explode(':', $filter);
		$sql .= ' AND '.$db->escape(trim($filt[0])).' = '.$db->escape(trim($filt[1]));
	}
}
if ($search_ref) $sql .= natural_search('f.ref', $search_ref);
if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);


if ($search_type != '' && $search_type != '-1'){
	if($search_type == '99'){
		$sql .= " AND f.type IN (0,2)";
	}else{
		$sql .= " AND f.type IN (".$db->escape($search_type).")";
	}
} 
if ($search_project_ref) $sql .= natural_search('p.ref', $search_project_ref);
if ($search_project) $sql .= natural_search('p.title', $search_project);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_town)  $sql .= natural_search('s.town', $search_town);
if ($search_zip)   $sql .= natural_search("s.zip", $search_zip);
if ($search_state) $sql .= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$db->escape($search_country).')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$db->escape($search_type_thirdparty).')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_montant_ht != '') $sql .= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_vat != '') $sql .= natural_search('f.tva', $search_montant_vat, 1);
if ($search_montant_localtax1 != '') $sql .= natural_search('f.localtax1', $search_montant_localtax1, 1);
if ($search_montant_localtax2 != '') $sql .= natural_search('f.localtax2', $search_montant_localtax2, 1);
if ($search_montant_ttc != '') $sql .= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_categ_cus > 0) $sql .= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)   $sql .= " AND cc.fk_categorie IS NULL";
if ($search_status != '-1' && $search_status != '')
{
	if (is_numeric($search_status) && $search_status >= 0)
	{
		if ($search_status == '0') $sql .= " AND f.fk_statut = 0"; // draft
		if ($search_status == '1') $sql .= " AND f.fk_statut = 1"; // unpayed
		if ($search_status == '2') $sql .= " AND f.fk_statut = 2"; // payed     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
		if ($search_status == '3') $sql .= " AND f.fk_statut = 3"; // abandonned
	}
	else
	{
		$sql .= " AND f.fk_statut IN (".$db->escape($search_status).")"; // When search_status is '1,2' for example
	}
}
if (($search_paymentmode > 0) && ($search_paymentmode != '200'))  $sql .= " AND f.fk_mode_reglement = ".$db->escape($search_paymentmode);
if (($search_paymentmode > 0) && ($search_paymentmode == '200'))  $sql .= " AND f.fk_mode_reglement IN (54, 6, 2, 4)"; //(Tarjeta de Crédito, Tarjeta de Débito, Transferencia y Efectivo)
if ($search_paymentterms > 0) $sql .= " AND f.fk_cond_reglement = ".$db->escape($search_paymentterms);
if ($search_module_source)    $sql .= natural_search("f.module_source", $search_module_source);
if ($search_pos_source)       $sql .= natural_search("f.pos_source", $search_pos_source);
if ($search_phone)            $sql .= natural_search("s.phone", $search_phone);
//$sql .= dolSqlDateFilter("f.datef", $search_day, $search_month, $search_year);
//Fecha de factura
if($date_start_factureyear && !$date_end_factureyear)
    $sql.=" AND f.datef >= '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."'";
else if (!$date_start_factureyear && $date_end_factureyear)
    $sql.=" AND f.datef <= '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
else if ($date_start_factureyear && $date_end_factureyear)
    $sql.=" AND f.datef BETWEEN '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."' AND '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
//$sql .= dolSqlDateFilter("f.date_lim_reglement", $search_day_lim, $search_month_lim, $search_year_lim);
//Fecha de vencimiento
if($date_start_limityear && !$date_end_limityear)
    $sql.=" AND f.date_lim_reglement >= '".$date_start_limityear."-".$date_start_limitmonth."-".$date_start_limitday."'";
else if (!$date_start_limityear && $date_end_limityear)
    $sql.=" AND f.date_lim_reglement <= '".$date_end_limityear."-".$date_end_limitmonth."-".$date_end_limitday."'";
else if ($date_start_limityear && $date_end_limityear)
    $sql.=" AND f.date_lim_reglement BETWEEN '".$date_start_limityear."-".$date_start_limitmonth."-".$date_start_limitday."' AND '".$date_end_limityear."-".$date_end_limitmonth."-".$date_end_limitday."'";

if ($option == 'late_discount')
{
	$by_time = array();

	 $late_date = "'".$db->idate(dol_now('tzuser') - $conf->facture->client->warning_delay)."'";
	
	if ($con30) $by_time[] = " f.date_lim_reglement BETWEEN DATE_SUB({$late_date}, INTERVAL 30   DAY) AND {$late_date}";
	if ($con60) $by_time[] = " f.date_lim_reglement BETWEEN DATE_SUB({$late_date}, INTERVAL 60   DAY) AND DATE_SUB({$late_date}, INTERVAL 31 DAY)";
	if ($con90) $by_time[] = " f.date_lim_reglement BETWEEN DATE_SUB({$late_date}, INTERVAL 90   DAY) AND DATE_SUB({$late_date}, INTERVAL 61 DAY)";
	if ($con91) $by_time[] = " f.date_lim_reglement BETWEEN DATE_SUB({$late_date}, INTERVAL 9000 DAY) AND DATE_SUB({$late_date}, INTERVAL 91 DAY)";;
	if (count($by_time))
	{
		$sql .= ' AND ('.implode(' OR ',$by_time).')';
	}

}
else
{
	$by_time = array();
	
	if ($con30) $by_time[] = " f.date_lim_reglement BETWEEN DATE(NOW()) AND DATE_ADD(DATE(NOW()), INTERVAL 30 DAY)";
	if ($con60) $by_time[] = " f.date_lim_reglement BETWEEN DATE_ADD(DATE(NOW()), INTERVAL 31 DAY) AND DATE_ADD(DATE(NOW()), INTERVAL 60 DAY)";
	if ($con90) $by_time[] = " f.date_lim_reglement BETWEEN DATE_ADD(DATE(NOW()), INTERVAL 61 DAY) AND DATE_ADD(DATE(NOW()), INTERVAL 90 DAY)";
	if ($con91) $by_time[] = " f.date_lim_reglement BETWEEN DATE_ADD(DATE(NOW()), INTERVAL 91 DAY) AND DATE_ADD(DATE(NOW()), INTERVAL 9000 DAY)";;
	if (count($by_time))
	{
		$sql .= ' AND ('.implode(' OR ',$by_time).')';
	}elseif($option == 'late'){
        $sql .= " AND f.date_lim_reglement < DATE(NOW()) AND f.paye = 0 AND f.fk_statut = 1";
    }
}
if ($search_sale > 0)  $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".(int) $search_sale;
if ($search_user > 0)
{
	$sql .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
/** Filtrando por estado de timbrado */
if($cfdi_filter == '1'){
	$sql .= " AND f.rowid IN (SELECT `fk_facture` FROM `".MAIN_DB_PREFIX."cfdimx` WHERE `cancelado` = 0)";
}
else if($cfdi_filter == '2'){
	$sql .= " AND f.rowid IN (SELECT `fk_facture` FROM `".MAIN_DB_PREFIX."cfdimx` WHERE `cancelado` = 1)";
}


// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

if (!$sall)
{
	$sql .= ' GROUP BY f.rowid, f.ref, ref_client, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.fk_cond_reglement, f.total, f.tva, f.total_ttc,';
	$sql .= ' f.localtax1, f.localtax2,';
	$sql .= ' f.datef, f.date_lim_reglement, f.module_source, f.pos_source,';
	$sql .= ' f.paye, f.fk_statut, f.close_code,';
	$sql .= ' f.datec, f.tms, f.date_closing,';
	$sql .= ' f.retained_warranty, f.retained_warranty_date_limit, f.situation_final, f.situation_cycle_ref, f.situation_counter,';
	$sql .= ' s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,';
	$sql .= ' typent.code,';
	$sql .= ' state.code_departement, state.nom,';
	$sql .= ' country.code,';
	$sql .= " p.rowid, p.ref, p.title";
	if ($search_categ_cus) $sql .= ", cc.fk_categorie, cc.fk_soc";
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
	}
}
else
{
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}


$sql2 = "SELECT f.rowid as id, f.fk_user_valid, f.ticketnumber as ref, null as ref_client, f.type, 99 as tipo, f.note, f.note_public, null as increment, f.fk_mode_reglement, s.cond_reglement as fk_cond_reglement,";
$sql2 .= " IF(f.type = 0, f.total_ht, (f.total_ht*-1)) as total_ht, IF(f.type = 0, f.tva, (f.tva*-1)) as total_vat, IF(f.type = 0, f.total_ttc, (f.total_ttc*-1)) AS total_ttc, f.localtax1 as total_localtax1,";
$sql2 .= " f.localtax2 as total_localtax2, f.date_creation as df, null as datelimite, null as module_source, null as pos_source, f.paye as paye, f.fk_statut, f.fk_statut as close_code, f.date_creation as date_creation,";
$sql2 .= " f.tms as date_update, f.date_creation as date_closing, null as retained_warranty, null as retained_warranty_date_limit, null as situation_final, null as situation_cycle_ref, null as situation_counter,";
$sql2 .= " s.rowid as socid, s.nom as name, s.phone, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,";
$sql2 .= " s.earlypayment_validity, typent.code as typent_code, state.code_departement as state_code, state.nom as state_name, country.code as country_code, p.rowid as project_id, p.ref as project_ref, p.title as project_label,";
$sql2 .= " SUM(pf.amount) as dynamount_payed, ef.formpagcfdi as options_formpagcfdi, ef.usocfdi as options_usocfdi, ef.cfdidoctiporelacion as options_cfdidoctiporelacion, ef.tipodecambiocfdi as options_tipodecambiocfdi,";
$sql2 .= " ef.clave_expor as options_clave_expor, 1 AS is_ticket FROM llx_societe as s LEFT JOIN llx_c_country as country on (country.rowid = s.fk_pays) LEFT JOIN llx_c_typent as typent on (typent.id = s.fk_typent)";
$sql2 .= " LEFT JOIN llx_c_departements as state on (state.rowid = s.fk_departement), llx_pos_ticket as f LEFT JOIN llx_facture_extrafields as ef on (f.rowid = ef.fk_object) LEFT JOIN llx_paiement_facture as pf ON pf.fk_facture = f.rowid";
$sql2 .= " LEFT JOIN llx_projet as p ON p.rowid = f.fk_projet WHERE f.fk_soc = s.rowid AND f.fk_facture IS NULL AND f.entity IN (1) AND (f.total_ttc != 0 OR f.fk_statut != 1)";

if (!$user->rights->societe->client->voir && !$socid) $sql2 .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($search_product_category > 0) $sql2 .= " AND cp.fk_categorie = ".$db->escape($search_product_category);
if ($socid > 0) $sql2 .= ' AND s.rowid = '.$socid;
if ($search_userf > 0) $sql2 .= ' AND f.fk_user_valid = '.$search_userf;
if ($userid)
{
	if ($userid == -1) $sql2 .= ' AND f.fk_user_valid IS NULL';
	else $sql2 .= ' AND f.fk_user_valid = '.$userid;
}
if ($filtre)
{
	$aFilter = explode(',', $filtre);
	foreach ($aFilter as $filter)
	{
		$filt = explode(':', $filter);
		$sql2 .= ' AND '.$db->escape(trim($filt[0])).' = '.$db->escape(trim($filt[1]));
	}
}
if ($search_ref) $sql2 .= natural_search('f.ticketnumber', $search_ref);
if ($search_refcustomer) $sql2 .= natural_search('NULL', $search_refcustomer);
//if ($search_type != '' && $search_type != '-1') $sql2 .= " AND f.type IN (".$db->escape($search_type).")";
if ($search_project_ref) $sql2 .= natural_search('p.ref', $search_project_ref);
if ($search_project) $sql2 .= natural_search('p.title', $search_project);
if ($search_societe) $sql2 .= natural_search('s.nom', $search_societe);
if ($search_town)  $sql2 .= natural_search('s.town', $search_town);
if ($search_zip)   $sql2 .= natural_search("s.zip", $search_zip);
if ($search_state) $sql2 .= natural_search("state.nom", $search_state);
if ($search_country) $sql2 .= " AND s.fk_pays IN (".$db->escape($search_country).')';
if ($search_type_thirdparty) $sql2 .= " AND s.fk_typent IN (".$db->escape($search_type_thirdparty).')';
if ($search_company) $sql2 .= natural_search('s.nom', $search_company);
if ($search_montant_ht != '') $sql2 .= natural_search('f.total_ht', $search_montant_ht, 1);
if ($search_montant_vat != '') $sql2 .= natural_search('f.tva', $search_montant_vat, 1);
if ($search_montant_localtax1 != '') $sql2 .= natural_search('f.localtax1', $search_montant_localtax1, 1);
if ($search_montant_localtax2 != '') $sql2 .= natural_search('f.localtax2', $search_montant_localtax2, 1);
if ($search_montant_ttc != '') $sql2 .= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_categ_cus > 0) $sql2 .= " AND cc.fk_categorie = ".$db->escape($search_categ_cus);
if ($search_categ_cus == -2)   $sql2 .= " AND cc.fk_categorie IS NULL";
if ($search_status != '-1' && $search_status != '')
{
	if (is_numeric($search_status) && $search_status >= 0)
	{
		if ($search_status == '0') $sql2 .= " AND f.fk_statut = 0"; // draft
		if ($search_status == '1') $sql2 .= " AND f.fk_statut IN (1,2) AND (f.paye = 0 OR f.difpayment > 0)"; // unpayed
		if ($search_status == '2') $sql2 .= " AND f.fk_statut IN (1,2) AND (f.paye = 1 OR f.difpayment = 0)"; // payed     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
		if ($search_status == '3') $sql2 .= " AND f.fk_statut = 3"; // abandonned
		//if ($search_status == '1,2') $sql2 .= " AND f.fk_statut IN (1,2)"; // abandonned
	}
	else
	{
		$sql2 .= " AND f.fk_statut NOT IN (0,3)"; // When search_status is '1,2' for example
	}
}
if (($search_paymentmode > 0) && ($search_paymentmode != '200'))  $sql2 .= " AND f.fk_mode_reglement = ".$db->escape($search_paymentmode);
if (($search_paymentmode > 0) && ($search_paymentmode == '200'))  $sql2 .= " AND f.fk_mode_reglement IN (54, 6, 2, 4)"; //(Tarjeta de Crédito, Tarjeta de Débito, Transferencia y Efectivo)
if ($search_paymentterms > 0) $sql2 .= " AND null = ".$db->escape($search_paymentterms);
if ($search_module_source)    $sql2 .= natural_search("f.module_source", $search_module_source);
if ($search_pos_source)       $sql2 .= natural_search("f.pos_source", $search_pos_source);
if ($search_phone)            $sql2 .= natural_search("s.phone", $search_phone);
//$sql .= dolSqlDateFilter("f.datef", $search_day, $search_month, $search_year);
//Fecha de factura
if($date_start_factureyear && !$date_end_factureyear)
    $sql2.=" AND f.date_ticket >= '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."'";
else if (!$date_start_factureyear && $date_end_factureyear)
    $sql2.=" AND f.date_ticket <= '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
else if ($date_start_factureyear && $date_end_factureyear)
    $sql2.=" AND f.date_ticket BETWEEN '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."' AND '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
//$sql .= dolSqlDateFilter("f.date_lim_reglement", $search_day_lim, $search_month_lim, $search_year_lim);
//Fecha de vencimiento
if($date_start_limityear && !$date_end_limityear)
    $sql2.=" AND f.date_closed >= '".$date_start_limityear."-".$date_start_limitmonth."-".$date_start_limitday."'";
else if (!$date_start_limityear && $date_end_limityear)
    $sql2.=" AND f.date_closed <= '".$date_end_limityear."-".$date_end_limitmonth."-".$date_end_limitday."'";
else if ($date_start_limityear && $date_end_limityear)
    $sql2.=" AND f.date_closed BETWEEN '".$date_start_limityear."-".$date_start_limitmonth."-".$date_start_limitday."' AND '".$date_end_limityear."-".$date_end_limitmonth."-".$date_end_limitday."'";

if ($option == 'late_discount')
{
	$by_time = array();

	 $late_date = "'".$db->idate(dol_now('tzuser') - $conf->facture->client->warning_delay)."'";
	
	if ($con30) $by_time[] = " f.date_closed BETWEEN DATE_SUB({$late_date}, INTERVAL 30   DAY) AND {$late_date}";
	if ($con60) $by_time[] = " f.date_closed BETWEEN DATE_SUB({$late_date}, INTERVAL 60   DAY) AND DATE_SUB({$late_date}, INTERVAL 31 DAY)";
	if ($con90) $by_time[] = " f.date_closed BETWEEN DATE_SUB({$late_date}, INTERVAL 90   DAY) AND DATE_SUB({$late_date}, INTERVAL 61 DAY)";
	if ($con91) $by_time[] = " f.date_closed BETWEEN DATE_SUB({$late_date}, INTERVAL 9000 DAY) AND DATE_SUB({$late_date}, INTERVAL 91 DAY)";;
	if (count($by_time))
	{
		$sql2 .= ' AND ('.implode(' OR ',$by_time).')';
	}

}
else
{
	$by_time = array();
	
	if ($con30) $by_time[] = " f.date_closed BETWEEN DATE(NOW()) AND DATE_ADD(DATE(NOW()), INTERVAL 30 DAY)";
	if ($con60) $by_time[] = " f.date_closed BETWEEN DATE_ADD(DATE(NOW()), INTERVAL 31 DAY) AND DATE_ADD(DATE(NOW()), INTERVAL 60 DAY)";
	if ($con90) $by_time[] = " f.date_closed BETWEEN DATE_ADD(DATE(NOW()), INTERVAL 61 DAY) AND DATE_ADD(DATE(NOW()), INTERVAL 90 DAY)";
	if ($con91) $by_time[] = " f.date_closed BETWEEN DATE_ADD(DATE(NOW()), INTERVAL 91 DAY) AND DATE_ADD(DATE(NOW()), INTERVAL 9000 DAY)";;
	if (count($by_time))
	{
		$sql2 .= ' AND ('.implode(' OR ',$by_time).')';
	}elseif($option == 'late'){
        $sql2 .= " AND f.date_closed < DATE(NOW()) AND f.paye = 0 AND f.fk_statut = 1";
    }
}
if ($search_sale > 0)  $sql2 .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".(int) $search_sale;
if ($search_user > 0)
{
	$sql2 .= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql2 .= $hookmanager->resPrint;

if (!$sall)
{
	$sql2 .= " GROUP BY f.rowid, f.ticketnumber, ref_client, f.type, f.note, f.note_public, f.fk_mode_reglement, f.total_ttc, f.tva, f.total_ttc, f.localtax1, f.localtax2, f.date_creation, f.paye, f.fk_statut,";
	$sql2 .= " s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, typent.code, state.code_departement, state.nom, country.code, p.rowid, p.ref, p.title, ef.formpagcfdi, ef.usocfdi";
	if ($search_categ_cus) $sql2 .= ", cc.fk_categorie, cc.fk_soc";
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) 
        $sql2 .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
	}
}
else
{
    $fieldstosearchall2 = array(
        'f.ticketnumber' => 'Ref',
        "'".$sall."'" => 'RefCustomer',
        "'".$sall."'" => 'Description',
        's.nom' => 'ThirdParty',
        'f.note_public' => 'NotePublic',
        'f.note' => 'NotePrivate'
    );
	$sql2 .= natural_search(array_keys($fieldstosearchall2), $sall);
}

$listfield = explode(',', $sortfield);
$listorder = explode(',', $sortorder);

if (($search_type == -1) && ($cfdi_filter != '1' && $cfdi_filter != '2'))
{
	if ($sortfield == 'ref')
		$sql .= " ORDER BY CAST(SUBSTRING(f.ref, LOCATE('-', f.ref)+1) AS UNSIGNED) $sortorder ";
	else
		$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($limit + 1, $offset);
	$sql2 .= $db->plimit($limit + 1, $offset);
	// $sql = "({$sql}) UNION ({$sql2}) ";
}
elseif ($search_type == 9999)
{
	if ($sortfield == 'ref')
		$sql2 .= " ORDER BY CAST(SUBSTRING(f.ticketnumber, LOCATE('-', f.ticketnumber)+1) AS UNSIGNED) $sortorder ";
	else
		$sql2 .= $db->order($sortfield, $sortorder);
	$sql2 .= $db->plimit($limit + 1, $offset);
	$sql = "{$sql2} ";
	$sorts = array();
	foreach ($listfield as $key => $value)
	{
		$sorts[] = $listfield[$key].' '.($listorder[$key] ? $listorder[$key] : 'DESC');
	}
	//$sql .= 'ORDER BY '.implode(',',$sorts);
}
else
{
	if ($sortfield == 'ref')
		$sql .= " ORDER BY CAST(SUBSTRING(f.ref, LOCATE('-', f.ref)+1) AS UNSIGNED) $sortorder ";
	else
		$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($limit + 1, $offset);
	$sql = "({$sql}) ";
	foreach ($listfield as $key => $value)
	{
		$sorts[] = $listfield[$key].' '.($listorder[$key] ? $listorder[$key] : 'DESC');
	}
	//$sql .= 'ORDER BY '.implode(',',$sorts);
}

if( $sortfield == 'ef.formpagcfdi')	$sortfield = 'options_formpagcfdi';
if( $sortfield == 'ef.usocfdi')	$sortfield = 'options_usocfdi';


$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
} else {
	$resql = $db->query($sql);
}

if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();
    $t_arrayofselected = is_array($t_toselect) ? $t_toselect : array();

	// Code for mass payment
    if ( $massaction == 'generate_payment' ) {
        // Updating the societe discount if changed
        if(GETPOST('discount_applied')){
            $temporal_client = new Societe($db);
            if (count($arrayofselected))
            {
                $temporal_facture = new Facture($db);
                $temporal_facture->fetch($arrayofselected[0]);
            }
            elseif(count($t_arrayofselected))
            {
                $temporal_facture = new Ticket($db);
                $temporal_facture->fetch($t_arrayofselected[0]);
            }
            
            $temporal_client->fetch($temporal_facture->socid);
            $temporal_client->earlypayment_discount = GETPOST('discount_applied');
            $temporal_client->update($temporal_client->id);
        }

        // CODE FOR THE SUBMIT OF PAYMENTS
        $strout = '';
        $iterator = 0;
        foreach($arrayofselected as $item){
            if($iterator == 0){
                $strout .= '?facid='.$item.'&action=create&masspaiement=true';
            }
            $strout .= '&facid'.$item.'='.$item;
            $iterator++;
        }
        foreach($t_arrayofselected as $item)
        {
            if($iterator == 0){
                $strout .= '?tktid='.$item.'&action=create&masspaiement=true';
            }
            $strout .= '&tktid'.$item.'='.$item;
            $iterator++;
        }
        header('Location: '.DOL_MAIN_URL_ROOT.'/compta/paiement.php'.$strout);
    }
    // End of cmp
	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->id;

		header("Location: ".DOL_URL_ROOT.'/compta/facture/card.php?facid='.$id);
		exit;
	}

	llxHeader('', $langs->trans('CustomersInvoices'), 'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		if (empty($search_societe)) $search_societe = $soc->name;
	}

	$param = '&socid='.$socid;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall)				 $param .= '&sall='.urlencode($sall);
	/*if ($search_day)         $param .= '&search_day='.urlencode($search_day);
	if ($search_month)       $param .= '&search_month='.urlencode($search_month);
	if ($search_year)        $param .= '&search_year='.urlencode($search_year);*/
	//Fecha factura
    if ($date_start_factureday)         $param .= '&date_start_factureday='.urlencode($date_start_factureday);
    if ($date_start_facturemonth)       $param .= '&date_start_facturemonth='.urlencode($date_start_facturemonth);
    if ($date_start_factureyear)        $param .= '&date_start_factureyear='.urlencode($date_start_factureyear);
    if ($date_end_factureday)         $param .= '&date_end_factureday='.urlencode($date_end_factureday);
    if ($date_end_facturemonth)       $param .= '&date_end_facturemonth='.urlencode($date_end_facturemonth);
    if ($date_end_factureyear)        $param .= '&date_end_factureyear='.urlencode($date_end_factureyear);
	/*if ($search_day_lim)     $param .= '&search_day_lim='.urlencode($search_day_lim);
	if ($search_month_lim)   $param .= '&search_month_lim='.urlencode($search_month_lim);
	if ($search_year_lim)    $param .= '&search_year_lim='.urlencode($search_year_lim);*/
	//Fecha de vencimiento
    if ($date_start_limitday)         $param .= '&date_start_limitday='.urlencode($date_start_limitday);
    if ($date_start_limitmonth)       $param .= '&date_start_limitmonth='.urlencode($date_start_limitmonth);
    if ($date_start_limityear)        $param .= '&date_start_limityear='.urlencode($date_start_limityear);
    if ($date_end_limitday)         $param .= '&date_end_limitday='.urlencode($date_end_limitday);
    if ($date_end_limitmonth)       $param .= '&date_end_limitmonth='.urlencode($date_end_limitmonth);
    if ($date_end_limityear)        $param .= '&date_end_limityear='.urlencode($date_end_limityear);
	if ($search_ref)         $param .= '&search_ref='.urlencode($search_ref);
	if ($search_refcustomer) $param .= '&search_refcustomer='.urlencode($search_refcustomer);
	if ($search_project_ref) $param .= '&search_project_ref='.urlencode($search_project_ref);
	if ($search_project)     $param .= '&search_project='.urlencode($search_project);
	if ($search_type != '')  $param .= '&search_type='.urlencode($search_type);
	if ($search_societe)     $param .= '&search_societe='.urlencode($search_societe);
	if ($search_town)        $param .= '&search_town='.urlencode($search_town);
	if ($search_zip)         $param .= '&search_zip='.urlencode($search_zip);
	if ($search_sale > 0)    $param .= '&search_sale='.urlencode($search_sale);
	if ($search_user > 0)    $param .= '&search_user='.urlencode($search_user);
	if ($search_userf > 0)    $param .= '&search_userf='.urlencode($search_userf);
	if ($search_product_category > 0)   $param .= '&search_product_category='.urlencode($search_product_category);
	if ($search_montant_ht != '')  $param .= '&search_montant_ht='.urlencode($search_montant_ht);
	if ($search_montant_vat != '')  $param .= '&search_montant_vat='.urlencode($search_montant_vat);
	if ($search_montant_localtax1 != '')  $param .= '&search_montant_localtax1='.urlencode($search_montant_localtax1);
	if ($search_montant_localtax2 != '')  $param .= '&search_montant_localtax2='.urlencode($search_montant_localtax2);
	if ($search_montant_ttc != '') $param .= '&search_montant_ttc='.urlencode($search_montant_ttc);
	if ($search_status != '') $param .= '&search_status='.urlencode($search_status);
	if ($search_paymentmode > 0) $param .= '&search_paymentmode='.urlencode($search_paymentmode);
	if ($search_paymentterms > 0) $param .= '&search_paymentterms='.urlencode($search_paymentterms);
	if ($search_module_source)  $param .= '&search_module_source='.urlencode($search_module_source);
	if ($search_pos_source)  $param .= '&search_pos_source='.urlencode($search_pos_source);
	if ($show_files)         $param .= '&show_files='.urlencode($show_files);
	if ($option)             $param .= "&search_option=".urlencode($option);
	if ($con30)             $param .= "&con30=".urlencode($con30);
	if ($con60)             $param .= "&con60=".urlencode($con60);
	if ($con90)             $param .= "&con90=".urlencode($con90);
	if ($con91)             $param .= "&con91=".urlencode($con91);
	if ($optioncss != '')    $param .= '&optioncss='.urlencode($optioncss);
	if ($search_categ_cus > 0) $param .= '&search_categ_cus='.urlencode($search_categ_cus);
	if ($search_phone)       $param .= '&search_phone='.urlencode($search_phone);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$arrayofmassactions = array(
	    'generate_payment'=>'Pagar Seleccion',
		'validate'=>$langs->trans("Validate"),
		//'generate_doc'=>$langs->trans("ReGeneratePDF"),
		//'builddoc'=>$langs->trans("PDFMerge"),
		//'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->facture->pay_commission) {
		$arrayofmassactions['pay_commission'] = "Pagar Comisión";
	}

	if ($conf->prelevement->enabled) {
        	$langs->load("withdrawals");
        	$arrayofmassactions['withdrawrequest'] = $langs->trans("MakeWithdrawRequest");
	}
	if ($user->rights->facture->supprimer) {
		if (!empty($conf->global->INVOICE_CAN_REMOVE_DRAFT_ONLY)) {
        	$arrayofmassactions['predeletedraft'] = $langs->trans("Deletedraft");
		}
        elseif (!empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED)) {	// mass deletion never possible on invoices on such situation
            $arrayofmassactions['predelete'] = $langs->trans("Delete");
        }
    }
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	if ($user->rights->facture->creer && $contextpage != 'poslist')
	{
        $newcardbutton .= dolGetButtonTitle($langs->trans('NewBill'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/facture/card.php?action=create');
	}
	$i = 0;
	print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print_barre_liste($langs->trans('BillsCustomers').' '.($socid ? ' '.$soc->name : ''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'invoicing', 0, $newcardbutton, '', $limit);
    print '<table width="100%">';
    print '<tr>';
    print '<td align="right">';
	print '<a class="butAction" name="exportarT" id="exportarT">Exportar listado (ticket)</a>';
	print '<script language="javascript">
                $(document).ready(function() {
                    $("#exportarT").on("click",function() {
                        $("#FormularioExportacion2").submit();
                    });
                });
            </script>';
    print '<a class="butAction" name="exportar" id="exportar">Exportar listado</a>';
    $script =   '<script language="javascript">
                $(document).ready(function() {
                    $("#exportar").on("click",function() {
                        $("#FormularioExportacion").submit();
                    });
                });
                </script>';
    print $script;
    print '</td>';
    print '</tr>';
    print '</table>';
	$topicmail = "SendBillRef";
	$modelmail = "facture_send";
	$objecttmp = new Facture($db);
	$trackid = 'inv'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

 	// If the user can view prospects other than his'
	$moreforfilter = '';
 	/*if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
 		$moreforfilter .= '<div class="divsearchfield">';
 		$moreforfilter .= $langs->trans('ThirdPartiesOfSaleRepresentative').': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	 	$moreforfilter .= '</div>';
 	}
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
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

	if ($moreforfilter)
	{
   		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton && $contextpage != 'poslist') $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'" >'."\n";

	// Filters lines
	print '<tr class="liste_titre_filter">';
	// Ref
	if (!empty($arrayfields['f.ref']['checked']))
	{
		print '<td class="liste_titre" align="left" colspan="2">';
		print '<input class="flat maxwidth60imp" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	// Ref customer
	if (!empty($arrayfields['f.ref_client']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth60imp" type="text" name="search_refcustomer" value="'.dol_escape_htmltag($search_refcustomer).'">';
		print '</td>';
	}
	// Type
	if (!empty($arrayfields['f.type']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone">';
		$listtype = array(
			Facture::TYPE_STANDARD=>$langs->trans("InvoiceStandard"),
			Facture::TYPE_REPLACEMENT=>$langs->trans("InvoiceReplacement"),
			Facture::TYPE_CREDIT_NOTE=>$langs->trans("InvoiceAvoir"),
			Facture::TYPE_DEPOSIT=>$langs->trans("InvoiceDeposit"),
			9999=>'Ticket del POS'
		);
		if (!empty($conf->global->INVOICE_USE_SITUATION))
		{
			$listtype[Facture::TYPE_SITUATION] = $langs->trans("InvoiceSituation");
		}
		//$listtype[Facture::TYPE_PROFORMA]=$langs->trans("InvoiceProForma");     // A proformat invoice is not an invoice but must be an order.
		print $form->selectarray('search_type', $listtype, $search_type, 1, 0, 0, '', 0, 0, 0, 'ASC', 'maxwidth100');
		print '</td>';
	}
    // Thirpdarty
    if (!empty($arrayfields['s.nom']['checked']))
    {
        print '<td class="liste_titre"><input class="flat" size="50" type="text" name="search_societe" value="'.$search_societe.'"></td>';
    }
    // Phone
    if (!empty($arrayfields['s.phone']['checked']))
    {
        print '<td class="liste_titre"><input class="flat" size="10" type="text" name="search_phone" value="'.$search_phone.'"></td>';
    }
	// Date invoice
	if (!empty($arrayfields['f.date']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		/*if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.dol_escape_htmltag($search_day).'">';
		print '<input class="flat valignmiddle width25" type="text" size="1" maxlength="2" name="search_month" value="'.dol_escape_htmltag($search_month).'">';
		$formother->select_year($search_year ? $search_year : -1, 'search_year', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');*/
		if($date_start_factureyear)$date_start_facture=dol_mktime(0,0,0,$date_start_facturemonth,$date_start_factureday,$date_start_factureyear);
		else $date_start_facture=null;
        if($date_end_factureyear)$date_end_facture=dol_mktime(0,0,0,$date_end_facturemonth,$date_end_factureday,$date_end_factureyear);
        else $date_end_facture=null;
        print "Desde<br>".$form->select_date($date_start_facture,'date_start_facture',0,0,0,'',1,0,1);
        print "<br>Hasta<br>".$form->select_date($date_end_facture,'date_end_facture',0,0,0,'',1,0,1);
		print '</td>';
	}
	// Date due
	if (!empty($arrayfields['f.date_lim_reglement']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		/*if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day_lim" value="'.dol_escape_htmltag($search_day_lim).'">';
		print '<input class="flat valignmiddle width25" type="text" size="1" maxlength="2" name="search_month_lim" value="'.dol_escape_htmltag($search_month_lim).'">';
		$formother->select_year($search_year_lim ? $search_year_lim : -1, 'search_year_lim', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');*/
        if($date_start_limityear)$date_start_limit=dol_mktime(0,0,0,$date_start_limitmonth,$date_start_limitday,$date_start_limityear);
        else $date_start_limit=null;
        if($date_end_limityear)$date_end_limit=dol_mktime(0,0,0,$date_end_limitmonth,$date_end_limitday,$date_end_limityear);
        else $date_end_limit=null;
        print '<table>';
        print '<tr>';
        print '<td>';
		print "Desde<br>".$form->select_date($date_start_limit,'date_start_limit',0,0,0,'',1,0,1);
        print "<br>Hasta<br>".$form->select_date($date_end_limit,'date_end_limit',0,0,0,'',1,0,1);
        print '<br><input type="checkbox" name="option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
        print '</td>';
        print '<td>';
        if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="day_lim" value="'.dol_escape_htmltag($date_start_limitday).'">';
        print '<br><input type="checkbox" name="option" value="late_discount"'.($option == 'late_discount' ? ' checked' : '').'> '.$langs->trans("Late");
        print '<br><input type="checkbox" name="con30" value="late"'.($con30? ' checked' : '').'> 30&nbsp;&nbsp;';
        print '<br><input type="checkbox" name="con60" value="late"'.($con60? ' checked' : '').'> 60&nbsp;&nbsp;';
        print '<br><input type="checkbox" name="con90" value="late"'.($con90? ' checked' : '').'> 90&nbsp;&nbsp;';
        print '<br><input type="checkbox" name="con91" value="late"'.($con91? ' checked' : '').'> 91+';
        print '</td>';
        print '</tr>';
        print '</table>';
        print '</td>';
	}
    // Date for discount validity
    if (!empty($arrayfields['s.earlypayment_validity']['checked']))
    {
        print '<td class="liste_titre nowraponall center">';
        print '&nbsp;';
        print '</td>';
    }
	// Project ref
	if (!empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre"><input class="flat" type="text" name="search_project_ref" value="'.$search_project_ref.'"></td>';
	}
	// Project label
	if (!empty($arrayfields['p.title']['checked']))
	{
	    print '<td class="liste_titre"><input class="flat" type="text" name="search_project" value="'.$search_project.'"></td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
	// Zip
	if (!empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
	// State
	if (!empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="8" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
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
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT), 'maxwidth100');
		print '</td>';
	}
	// Payment mode
	if (!empty($arrayfields['f.fk_mode_reglement']['checked']))
	{
		print '<td class="liste_titre">';
		$form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 20, 1, '', 1);
		print '</td>';
	}
	// Payment terms
	if (!empty($arrayfields['f.fk_cond_reglement']['checked']))
	{
		print '<td class="liste_titre">';
		$form->select_conditions_paiements($search_paymentterms, 'search_paymentterms', -1, 1, 1);
		print '</td>';
	}
	// Module source
	if (!empty($arrayfields['f.module_source']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth75" type="text" name="search_module_source" value="'.dol_escape_htmltag($search_module_source).'">';
		print '</td>';
	}
	// POS Terminal
	if (!empty($arrayfields['f.pos_source']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_pos_source" value="'.dol_escape_htmltag($search_pos_source).'">';
		print '</td>';
	}
	if (!empty($arrayfields['ht_without_discount']['checked']))
    {
        // Amount without discount
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
    if (!empty($arrayfields['discount']['checked']))
    {
        // Discount
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
	if (!empty($arrayfields['f.total_ht']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="8" name="search_montant_ht" value="'.dol_escape_htmltag($search_montant_ht).'">';
		print '</td>';
	}
	if (!empty($arrayfields['f.total_vat']['checked']))
	{
		// Amount VAT
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="8" name="search_montant_vat" value="'.dol_escape_htmltag($search_montant_vat).'">';
		print '</td>';
	}
	if (!empty($arrayfields['credit']['checked']))
    {
        // Credit card amount
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
	if (!empty($arrayfields['debit']['checked']))
    {
        // Debit card amount
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
	if (!empty($arrayfields['transfer']['checked']))
    {
        // Transfer amount
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
	if (!empty($arrayfields['cash']['checked']))
    {
        // Cash amount
        print '<td class="liste_titre" align="right">';
        print '</td>';
    }
	if (!empty($arrayfields['f.total_localtax1']['checked']))
	{
		// Localtax1
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_montant_localtax1" value="'.$search_montant_localtax1.'">';
		print '</td>';
	}
	if (!empty($arrayfields['f.total_localtax2']['checked']))
	{
		// Localtax2
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_montant_localtax2" value="'.$search_montant_localtax2.'">';
		print '</td>';
	}
	if (!empty($arrayfields['f.total_ttc']['checked']))
	{
		// Amount
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="8" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
		print '</td>';
	}

	if (!empty($arrayfields['f.retained_warranty']['checked']))
	{
	    print '<td class="liste_titre" align="right">';
	    print '</td>';
	}

	if (!empty($arrayfields['dynamount_payed']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}
	if (!empty($arrayfields['rtp']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}
	// Margen
	if (!empty($arrayfields['margin']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		print '</td>';
	}
	if (!empty($arrayfields['f.commission']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	if(!empty($arrayfields['f.fk_user_valid']['checked']))
    {
        print '<td class="liste_titre">';
        print $form->select_dolusers($search_userf, 'search_user_search', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
        print '</td>';
    }

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['f.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['f.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
    if (!empty($arrayfields['f.date_closing']['checked']))
    {
        print '<td class="liste_titre">';
        print '</td>';
    }
	// Status
	if (!empty($arrayfields['f.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		$liststatus = array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '1,2'=>$langs->trans("BillShortStatusNotPaid").'+'.$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
		print $form->selectarray('search_status', $liststatus, $search_status, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['f.ref']['checked'])){
		$icon_attribs = [
			'0' => ['color' => '', 'icon' => 'fa-circle-o', 'title' => '<b>Sin filtro</b>'],
			'1' => ['color' => 'color:#090;', 'icon' => 'fa-check-circle-o', 'title' => 'Filtrando: <b>Facturas Timbradas</b>'],
			'2' => ['color' => 'color:#900;', 'icon' => 'fa-times-circle-o', 'title' => 'Filtrando: <b>Facturas con Timbrado Cancelado</b>'],
		];
		print '<th align="left" style="width: 5px !important;">';
		print '<i style="opacity:70%;'.$icon_attribs[$cfdi_filter]['color'].'" id="cfdi_filter_button" class="classfortooltip fa '.$icon_attribs[$cfdi_filter]['icon'].'" title="'.$icon_attribs[$cfdi_filter]['title'].'"></i> ';
		print '<input id="cfdi_filter" name="cfdi_filter" type="hidden" value="'.$cfdi_filter.'"/>';
		print '</th>';
		
		print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER['PHP_SELF'], 'ref', '', $param, 'style="width: 99%"', $sortfield, $sortorder);
	}       
	if (!empty($arrayfields['f.ref_client']['checked']))         print_liste_field_titre($arrayfields['f.ref_client']['label'], $_SERVER["PHP_SELF"], 'ref_client', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['f.type']['checked']))               print_liste_field_titre($arrayfields['f.type']['label'], $_SERVER["PHP_SELF"], 'tipo', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['s.nom']['checked']))                print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER['PHP_SELF'], 'name', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['s.phone']['checked']))                print_liste_field_titre($arrayfields['s.phone']['label'], $_SERVER['PHP_SELF'], 'phone', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['f.date']['checked']))               print_liste_field_titre($arrayfields['f.date']['label'], $_SERVER['PHP_SELF'], 'df', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.date_lim_reglement']['checked'])) print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], "datelimite", '', $param, 'align="center"', $sortfield, $sortorder);
    if (!empty($arrayfields['s.earlypayment_validity']['checked'])) print_liste_field_titre($arrayfields['s.earlypayment_validity']['label'], $_SERVER['PHP_SELF'], "earlypayment_validity", '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['p.ref']['checked']))                print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER['PHP_SELF'], "project_ref", '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['p.title']['checked']))              print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER['PHP_SELF'], "project_label", '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.town']['checked']))               print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 'town', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.zip']['checked']))                print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 'zip', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['state.nom']['checked']))            print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state_name", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['country.code_iso']['checked']))     print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country_code", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['typent.code']['checked']))          print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent_code", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.fk_mode_reglement']['checked']))  print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "fk_mode_reglement", "", $param, "", $sortfield, $sortorder);
	if (!empty($arrayfields['f.fk_cond_reglement']['checked']))  print_liste_field_titre($arrayfields['f.fk_cond_reglement']['label'], $_SERVER["PHP_SELF"], "fk_cond_reglement", "", $param, "", $sortfield, $sortorder);
	if (!empty($arrayfields['f.module_source']['checked']))      print_liste_field_titre($arrayfields['f.module_source']['label'], $_SERVER["PHP_SELF"], "module_source", "", $param, "", $sortfield, $sortorder);
	if (!empty($arrayfields['f.pos_source']['checked']))         print_liste_field_titre($arrayfields['f.pos_source']['label'], $_SERVER["PHP_SELF"], "pos_source", "", $param, "", $sortfield, $sortorder);
	if (!empty($arrayfields['ht_without_discount']['checked']))  print_liste_field_titre($arrayfields['ht_without_discount']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
    if (!empty($arrayfields['discount']['checked']))             print_liste_field_titre($arrayfields['discount']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.total_ht']['checked']))           print_liste_field_titre($arrayfields['f.total_ht']['label'], $_SERVER['PHP_SELF'], 'total_ht', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.total_vat']['checked']))          print_liste_field_titre($arrayfields['f.total_vat']['label'], $_SERVER['PHP_SELF'], 'total_vat', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['credit']['checked']))  			 print_liste_field_titre($arrayfields['credit']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['debit']['checked']))  				 print_liste_field_titre($arrayfields['debit']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['transfer']['checked']))  			 print_liste_field_titre($arrayfields['transfer']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['cash']['checked']))  				 print_liste_field_titre($arrayfields['cash']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.total_localtax1']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax1']['label'], $_SERVER['PHP_SELF'], 'total_localtax1', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.total_localtax2']['checked']))    print_liste_field_titre($arrayfields['f.total_localtax2']['label'], $_SERVER['PHP_SELF'], 'total_localtax2', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.total_ttc']['checked']))          print_liste_field_titre($arrayfields['f.total_ttc']['label'], $_SERVER['PHP_SELF'], 'total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.retained_warranty']['checked']))  print_liste_field_titre($arrayfields['f.retained_warranty']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'align="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['dynamount_payed']['checked']))      print_liste_field_titre($arrayfields['dynamount_payed']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['rtp']['checked']))                  print_liste_field_titre($arrayfields['rtp']['label'], '', '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['margin']['checked']))               print_liste_field_titre($arrayfields['margin']['label'],'' , '', '', $param, 'class="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.commission']['checked']))     	 print_liste_field_titre($arrayfields['f.commission']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'class=""', $sortfield, $sortorder);
	if (!empty($arrayfields['f.fk_user_valid']['checked']))     print_liste_field_titre($arrayfields['f.fk_user_valid']['label'], $_SERVER['PHP_SELF'], 'fk_user_valid', '', $param, 'class=""', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['f.datec']['checked']))     print_liste_field_titre($arrayfields['f.datec']['label'], $_SERVER["PHP_SELF"], "date_creation", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.tms']['checked']))       print_liste_field_titre($arrayfields['f.tms']['label'], $_SERVER["PHP_SELF"], "date_update", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.date_closing']['checked']))       print_liste_field_titre($arrayfields['f.date_closing']['label'], $_SERVER["PHP_SELF"], "date_closing", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['f.fk_statut']['checked'])) print_liste_field_titre($arrayfields['f.fk_statut']['label'], $_SERVER["PHP_SELF"], "fk_statut", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	$projectstatic = new Project($db);
	$discount = new DiscountAbsolute($db);
    include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
    $userstatic = new User($db);

	if ($num > 0)
	{
		// Actualizar tabla de estados de timbrado de factura
		$sql_timb = "INSERT INTO llx_cfdimx_cancelado (factura_id, factura_seriefolio, fk_facture, cancelado)
			SELECT factura_id, factura_seriefolio, fk_facture, cancelado FROM llx_cfdimx
			ON DUPLICATE KEY UPDATE factura_id = VALUES(factura_id), factura_seriefolio = VALUES(factura_seriefolio), fk_facture = VALUES(fk_facture), cancelado = VALUES(cancelado); ";
		$resql_timb = $db->query($sql_timb);

		$i = 0;
		$totalarray = array();
        $total_remaintopay = 0;
		$total_remaintopayCredit = 0;
		$res_margin = 0;
		$count_margin = 0;
		while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($resql);
			
			if ($obj->is_ticket)
			{
				$facturestatic = $ticketstatic;
                if ($obj->type !=0)
                {
                    //$obj->total_ttc = $obj->total_ttc *-1; 
                }
			}
			else
			{
				$facturestatic = $facturestaticc;
			}

			$datelimit = $db->jdate($obj->datelimite);

			$facturestatic->id = $obj->id;
			$facturestatic->ref = $obj->ref;
			$facturestatic->ref_client = $obj->ref_client;
			$facturestatic->type = $obj->type;
            $facturestatic->total_ht = $obj->total_ht;
            $facturestatic->total_tva = $obj->total_vat;
            $facturestatic->total_ttc = $obj->total_ttc;
			$facturestatic->statut = $obj->fk_statut;
			$facturestatic->close_code = $obj->close_code;
			$facturestatic->total_ttc = $obj->total_ttc;
            $facturestatic->paye = $obj->paye;
            $facturestatic->fk_soc = $obj->fk_soc;
			$facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);
			$facturestatic->note_public = $obj->note_public;
			$facturestatic->note_private = $obj->note_private;
			if ($conf->global->INVOICE_USE_SITUATION && $conf->global->INVOICE_USE_SITUATION_RETAINED_WARRANTY)
			{
			     $facturestatic->retained_warranty = $obj->retained_warranty;
			     $facturestatic->retained_warranty_date_limit = $obj->retained_warranty_date_limit;
			     $facturestatic->situation_final = $obj->retained_warranty_date_limit;
			     $facturestatic->situation_final = $obj->retained_warranty_date_limit;
			     $facturestatic->situation_cycle_ref = $obj->situation_cycle_ref;
			     $facturestatic->situation_counter = $obj->situation_counter;
			}
			$thirdpartystatic->id = $obj->socid;
			$thirdpartystatic->name = $obj->name;
			$thirdpartystatic->client = $obj->client;
			$thirdpartystatic->fournisseur = $obj->fournisseur;
			$thirdpartystatic->code_client = $obj->code_client;
			$thirdpartystatic->code_compta_client = $obj->code_compta_client;
			$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
			$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
			$thirdpartystatic->email = $obj->email;
			$thirdpartystatic->country_code = $obj->country_code;

			$projectstatic->id = $obj->project_id;
			$projectstatic->ref = $obj->project_ref;
			$projectstatic->title = $obj->project_label;

			$paiement = $facturestatic->getSommePaiement();

			// Get amount for each payment method
			$cash = 0;
			$debit = 0;
			$credit = 0;
			$transfer = 0;
			$paiements = $facturestatic->getListOfPayments();
			foreach ($paiements as $payment) {
				switch ($payment['type']) {
					case 'LIQ':
						$cash += $payment['amount'];
						break;
					case 'TD/C':
						$debit += $payment['amount'];
						break;
					case 'CB':
						$credit += $payment['amount'];
						break;
					case 'VIR':
						$transfer += $payment['amount'];
						break;
				}
			}

			if (method_exists($facturestatic,'getSumCreditNotesUsed'))
			{
				$totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
			}
			else
			{
				$totalcreditnotes = 0;
			}
			if (method_exists($facturestatic,'getSumDepositsUsed'))
			{
				$totaldeposits = $facturestatic->getSumDepositsUsed();
			}
			else
			{
				$totaldeposits = 0;
			}
			$totaldeposits = $facturestatic->getSumDepositsUsed();
			$totalpay = $paiement + $totalcreditnotes + $totaldeposits;

			$remaintopay = price2num($facturestatic->total_ttc - $totalpay);

			if ($facturestatic->statut == Facture::STATUS_CLOSED && $facturestatic->close_code == 'discount_vat') {		// If invoice closed with discount for anticipated payment
				$remaintopay = 0;
			}
			if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {		// If credit note closed, we take into account the amount not yet consummed
				$remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source='.$facturestatic->id);
				$remaintopay = -$remaincreditnote;
				$totalpay = price2num($facturestatic->total_ttc - $remaintopay);
			}
			
			$banFila = false;
			if ($remaintopay >= -0.019 && $remaintopay <= 0.011 && ($search_status === '1')){
				$banFila = true;
			}

            print '<tr class="oddeven"';
            if ($contextpage == 'poslist')
            {
                print ' onclick="parent.$(\'#poslines\').load(\'invoice.php?action=history&placeid='.$obj->id.'\', function() {parent.$.colorbox.close();});"';
            }
			
			if($banFila){
				print ' style="display:none;" >';
			}else{
				print '>';
			}

			if (!empty($arrayfields['f.ref']['checked']))
			{
				print '<td class="nowrap" colspan="2">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';

				print '<td class="nobordernopadding nowraponall">';
                if ($contextpage == 'poslist')
                {
                    print $obj->ref;
                }
                else
                {
                    print $facturestatic->getNomUrl(1, '', 200, 0, '', 1, 1);
                }
				print empty($obj->increment) ? '' : ' ('.$obj->increment.')';

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->id;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td>';
				print '</tr>';
				print '</table>';

				print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}

			// Customer ref
			if (!empty($arrayfields['f.ref_client']['checked']))
			{
				print '<td class="nowrap tdoverflowmax200">';
				print $obj->ref_client;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Type
			if (!empty($arrayfields['f.type']['checked']))
			{
				if ($obj->is_ticket)
				{
				print '<td class="nowrap">';
				print 'Tiket del POS';
				print "</td>";
					
				}
				else
				{
				print '<td class="nowrap">';
				print $facturestatic->getLibType();
				print "</td>";
				}
				
				if (!$i) $totalarray['nbfield']++;
			}

            // Third party
            if (!empty($arrayfields['s.nom']['checked']))
            {
                print '<td class="tdoverflowmax200">';
                if ($contextpage == 'poslist')
                {
                    print $thirdpartystatic->name;
                }
                else
                {
                    print $thirdpartystatic->getNomUrl(1, 'customer');
                }
                print '</td>';
                if (!$i) $totalarray['nbfield']++;
            }

            //Phone
            if (!empty($arrayfields['s.phone']['checked']))
            {
                print '<td class="tdoverflowmax200">';
                print $obj->phone;
                print '</td>';
                if (!$i) $totalarray['nbfield']++;
            }

			// Date
			if (!empty($arrayfields['f.date']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->df), 'day');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Date limit
			if (!empty($arrayfields['f.date_lim_reglement']['checked']))
			{
				print '<td align="center" class="nowrap">'.dol_print_date($datelimit, 'day');
				if (method_exists($facturestatic,'hasDelay') && $facturestatic->hasDelay())
				{
				    print img_warning($langs->trans('Alert').' - '.$langs->trans('LateList'));
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
            //Date valid discount
            if (!empty($arrayfields['s.earlypayment_validity']['checked']))
            {
                $thirdpartystatic->fetch($thirdpartystatic->id);
                if($thirdpartystatic->earlypayment_discount > 0){
                    $date_invoiced = date("d-m-Y",strtotime($obj->df));

                    if($thirdpartystatic->earlypayment_validity) $agregated_days = " + ".$thirdpartystatic->earlypayment_validity." days";
                    else $agregated_days = '';

                    $date_valid = date("d/m/Y",strtotime($date_invoiced.$agregated_days));

                    print '<td class="center nowrap">'.$date_valid;
//                    print img_info($thirdparty->earlypayment_discount.'%');
                    print '<span style="font-size: 12px;"> '.$thirdpartystatic->earlypayment_discount.'%</span>';
                    if (strtotime($date_valid) > strtotime(date("d/m/Y")))
                    {
                        print img_error($langs->trans('Late'));
                    }
                    print '</td>';
                }
                else {
                    print '<td class="center nowrap">Sin descuento</td>';
                }
                if (! $i) $totalarray['nbfield']++;
            }

			// Project ref
			if (!empty($arrayfields['p.ref']['checked']))
			{
				print '<td class="nocellnopadd nowrap">';
				if ($obj->project_id > 0)
				{
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Project title
			if (!empty($arrayfields['p.title']['checked']))
			{
			    print '<td class="nowrap">';
			    if ($obj->project_id > 0)
			    {
			        print $projectstatic->title;
			    }
			    print '</td>';
			    if (!$i) $totalarray['nbfield']++;
			}

			// Town
			if (!empty($arrayfields['s.town']['checked']))
			{
				print '<td>';
				print $obj->town;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// Zip
			if (!empty($arrayfields['s.zip']['checked']))
			{
				print '<td>';
				print $obj->zip;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// State
			if (!empty($arrayfields['state.nom']['checked']))
			{
				print "<td>".$obj->state_name."</td>\n";
				if (!$i) $totalarray['nbfield']++;
			}
			// Country
			if (!empty($arrayfields['country.code_iso']['checked']))
			{
				print '<td class="center">';
				$tmparray = getCountry($obj->fk_pays, 'all');
				print $tmparray['label'];
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// Type ent
			if (!empty($arrayfields['typent.code']['checked']))
			{
				print '<td class="center">';
				if (!is_array($typenArray) || count($typenArray) == 0) $typenArray = $formcompany->typent_array(1);
				print $typenArray[$obj->typent_code];
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// Staff
			if (!empty($arrayfields['staff.code']['checked']))
			{
				print '<td class="center">';
				if (!is_array($staffArray) || count($staffArray) == 0) $staffArray = $formcompany->effectif_array(1);
				print $staffArray[$obj->staff_code];
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Payment mode
			if (!empty($arrayfields['f.fk_mode_reglement']['checked']))
			{
				print '<td>';
				$form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Payment terms
			if (!empty($arrayfields['f.fk_cond_reglement']['checked']))
			{
				print '<td>';
				$form->form_conditions_reglement($_SERVER['PHP_SELF'], $obj->fk_cond_reglement, 'none');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Module Source
			if (!empty($arrayfields['f.module_source']['checked']))
			{
				print '<td>';
				print $obj->module_source;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// POS Terminal
			if (!empty($arrayfields['f.pos_source']['checked']))
			{
				print '<td>';
				print $obj->pos_source;
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Amount without discount
            if (!empty($arrayfields['ht_without_discount']['checked']))
            {
                $facturestatic->fetch($facturestatic->id);
				$amount = $facturestatic->getDiscount() + $obj->total_ht;
                print '<td class="right nowrap">'.price($amount)."</td>\n";
                if (!$i) $totalarray['nbfield']++;
                if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'ht_without_discount';
                if(!$banFila)
				  	$totalarray['val']['ht_without_discount'] += ($facturestatic->getDiscount()+$obj->total_ht);
            }
            // Discount
            if (!empty($arrayfields['discount']['checked']))
            {
                $facturestatic->fetch($facturestatic->id);
                print '<td class="right nowrap">'.price($facturestatic->getDiscount())."</td>\n";
                if (!$i) $totalarray['nbfield']++;
                if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'discount';
                if(!$banFila)
				  	$totalarray['val']['discount'] += $facturestatic->getDiscount();
            }
			// Amount HT
			if (!empty($arrayfields['f.total_ht']['checked']))
			{
				print '<td class="right nowrap">'.price($obj->total_ht)."</td>\n";
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ht';
				if(!$banFila)
					$totalarray['val']['f.total_ht'] += $obj->total_ht;
			}
			// Amount VAT
			if (!empty($arrayfields['f.total_vat']['checked']))
			{
				print '<td class="right nowrap">'.price($obj->total_vat)."</td>\n";
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_vat';
				if(!$banFila)
				  	$totalarray['val']['f.total_vat'] += $obj->total_vat;
			}

			// Credit card amount
            if (!empty($arrayfields['credit']['checked']))
            {
                print '<td class="right nowrap">'.($credit > 0 ? price($credit) : '')."</td>\n";
                if (!$i) $totalarray['nbfield']++;
                if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'credit';
                if(!$banFila)
				  	$totalarray['val']['credit'] += $credit;
            }
			// Debit card amount
            if (!empty($arrayfields['debit']['checked']))
            {
                $facturestatic->fetch($facturestatic->id);
                print '<td class="right nowrap">'.($debit > 0 ? price($debit) : '')."</td>\n";
                if (!$i) $totalarray['nbfield']++;
                if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'debit';
                if(!$banFila)
				  	$totalarray['val']['debit'] += $debit;
            }
			// Transfer amount
            if (!empty($arrayfields['transfer']['checked']))
            {
                $facturestatic->fetch($facturestatic->id);
                print '<td class="right nowrap">'.($transfer > 0 ? price($transfer) : '')."</td>\n";
                if (!$i) $totalarray['nbfield']++;
                if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'transfer';
                if(!$banFila)
				  	$totalarray['val']['transfer'] += $transfer;
            }
			// Cash discount
            if (!empty($arrayfields['cash']['checked']))
            {
                $facturestatic->fetch($facturestatic->id);
                print '<td class="right nowrap">'.($cash > 0 ? price($cash) : '')."</td>\n";
                if (!$i) $totalarray['nbfield']++;
                if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'cash';
                if(!$banFila)
				  	$totalarray['val']['cash'] += $cash;
            }
			
			// Amount LocalTax1
			if (!empty($arrayfields['f.total_localtax1']['checked']))
			{
				print '<td class="right nowrap">'.price($obj->total_localtax1)."</td>\n";
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_localtax1';
				if(!$banFila)
				  	$totalarray['val']['f.total_localtax1'] += $obj->total_localtax1;
			}
			// Amount LocalTax2
			if (!empty($arrayfields['f.total_localtax2']['checked']))
			{
				print '<td class="right nowrap">'.price($obj->total_localtax2)."</td>\n";
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_localtax2';
				if(!$banFila)
				  	$totalarray['val']['f.total_localtax2'] += $obj->total_localtax2;
			}
			// Amount TTC
			if (!empty($arrayfields['f.total_ttc']['checked']))
			{
				print '<td class="right nowrap">'.price($obj->total_ttc)."</td>\n";
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
				if(!$banFila)
				  	$totalarray['val']['f.total_ttc'] += $obj->total_ttc;
			}

			if (!empty($arrayfields['f.retained_warranty']['checked']))
			{
			    print '<td align="right">'.(!empty($obj->retained_warranty) ?price($obj->retained_warranty).'%' : '&nbsp;').'</td>';
			}

			if (!empty($arrayfields['dynamount_payed']['checked']))
			{
				print '<td class="right nowrap">'.(!empty($totalpay) ?price($totalpay, 0, $langs) : '&nbsp;').'</td>'; // TODO Use a denormalized field
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totalam';
				if(!$banFila)
				  	$totalarray['val']['totalam'] += $totalpay;
			}

			// Pending amount
			if (!empty($arrayfields['rtp']['checked']))
			{
				print '<td class="right nowrap">';
				if ($remaintopay >= -0.019 && $remaintopay <= 0.011){
					$remaintopay = 0;
				}
				print (!empty($remaintopay) ? price($remaintopay, 0, $langs) : '&nbsp;');
				print '</td>'; // TODO Use a denormalized field
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'rtp';
                //No considera el saldo de los cancelados en el total de Saldo
				//if($obj->fk_statut != 3)
				if(!$banFila){
						$totalarray['val']['rtp'] += $remaintopay;
					if($remaintopay < 0){
						$total_remaintopayCredit += $remaintopay;
					}else{
						$total_remaintopay += $remaintopay;
					}
				}
			}
			//Margen
			if (!empty($arrayfields['margin']['checked']))
            {				
                print '<td class=" right nowrap">';
				if ($obj->is_ticket){
					//Validacion precio de compra por producto antes de la actualizacion
					$banPa = true;
					$sqlPa = "SELECT buy_price_ht FROM llx_pos_ticketdet WHERE fk_ticket = ".$facturestatic->id;
					$resultPa = $db->query($sqlPa);
					if ($resultPa){
						$numPa = $db->num_rows($resultPa);
						$iPa = 0;
						while ($iPa < $numPa)
						{
							$objPa = $db->fetch_object($resultPa);
							if(is_null($objPa->buy_price_ht)){
								$banPa = false;
								break;
							}
							$iPa ++;
						}
					}
					if ($numPa > 0 && $banPa) {
						$facturestatic->fetch_lines();
						$margen = $formmargin->getMarginInfosArray($facturestatic);
						$margin = price($margen['total_mark_rate'], null, null, null, null, 2);
						print (($margen['total_mark_rate'] == '') ? '' : $margin.'%');
						$res_margin += $margin;
					}else{
						$margin = 0;
						$res_margin += $margin;
					}
				}else{
					$facturestatic->fetch_lines();
					$margen = $formmargin->getMarginInfosArray($facturestatic);
					$margin = price($margen['total_mark_rate'], null, null, null, null, 2);
					print (($margen['total_mark_rate'] == '') ? '' : $margin.'%');
					$res_margin += $margin;
				}
				$count_margin += 1;
                print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'margin';
            }
			if (!empty($arrayfields['f.commission']['checked']))
			{
				$objecttmp->fetch($obj->id);
				print '<td class="nowrap">';
				print price($objecttmp->commission);
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
            if (!empty($arrayfields['f.fk_user_valid']['checked']))
            {
                $userstatic->fetch($obj->fk_user_valid);
                print '<td class="nowrap">';
                print $userstatic->getNomUrl();
                print '</td>';
				if (!$i) $totalarray['nbfield']++;
            }

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (!empty($arrayfields['f.datec']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// Date modification
			if (!empty($arrayfields['f.tms']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// Date closing
			if (!empty($arrayfields['f.date_closing']['checked']))
			{
				print '<td align="center" class="nowrap">';
				print dol_print_date($db->jdate($obj->date_closing), 'dayhour', 'tzuser');
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			// Status
			if (!empty($arrayfields['f.fk_statut']['checked']))
			{
                if (!$obj->is_ticket)
                {
    				print '<td class="nowrap right">';
    				print $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 5, $paiement, $obj->type);
    				print "</td>";
    				if (!$i) $totalarray['nbfield']++;
                }
                else
                {
    				print '<td class="nowrap right">';
    				print $facturestatic->LibStatut($obj->fk_statut, 1);
    				print "</td>";
    				if (!$i) $totalarray['nbfield']++;
                }
			}

			// Action column
			print '<td class="nowrap" align="center">';
			if (($massactionbutton || $massaction) && $contextpage != 'poslist')   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			{
				if ($obj->is_ticket)
				{
					$selected = 0;
					if (is_array($t_arrayofselected) && in_array($obj->id, $t_arrayofselected)) $selected = 1;
					print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="t_toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
				}
				else
				{
					$selected = 0;
					if (in_array($obj->id, $arrayofselected)) $selected = 1;
					print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
				}
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;

			print "</tr>\n";

			$i++;
		}
        print '<input type="hidden" value="'.$total_remaintopay.'" id="totalResToPay" name="totalResToPay">';
		print '<input type="hidden" value="'.$total_remaintopayCredit.'" id="totalResToPayCredit" name="totalResToPayCredit">';
		//promedio de margen de ganancia
		if (!empty($arrayfields['margin']['checked'])){
			$totalarray['val']['margin'] = $res_margin / $count_margin;
		}
		// Show total line
		include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
	}

    print '
	    <script>
			let it = 0;
			let facturasnormales = 0;
	        $("#checkallactions").change(function() {
                if($(this).is(\':checked\')){
                    let restopay = parseFloat($("#totalResToPay").val()).toFixed(2);
					let restopayCredit = parseFloat($("#totalResToPayCredit").val()).toFixed(2);
                    document.getElementById("total_amount").innerText = restopay;
					document.getElementById("total_credit").innerText = restopayCredit;
                }
                else{
                    let restopay = "0.00";
                    document.getElementById("total_amount").innerText = restopay;
                    document.getElementById("total_credit").innerText = restopay;
                }
            });
            $("#discount_input").keypress(function(e) {
                if(e.which == 13) {
                  applyDiscount();
                }
            });
            $(".checkforselect").change(function(){
                var data = [];
                $(".checkforselect").each(function(i,e){
                    if ($(e).is(":checked"))
                    {
                        var id = $(e).attr("id").replace("cb","");
                        data.push(id);
                    }
                });
                $("#factures").val(JSON.stringify(data));
            });
	        $(\'input[type="checkbox"]\').click(function(){
	            let checked = false;
				let inp = $(this);
                if($(this).prop("checked") == true){
                    checked = true;
                }
                let id = $(this).prop("value");
                let nm = $(this).prop("name");
                let ty = 0;
                //console.log(nm.substring(0,3));
                if (nm.substring(0,3) == "t_t")
                {
                    ty = 1;
                }
                let data = {
                    "id": id,
                    "type":ty
                };
                let url = "'.DOL_MAIN_URL_ROOT.'/compta/amount_calculator.php";
                let total_amount = document.getElementById("total_amount").innerText;
                let total_credit = document.getElementById("total_credit").innerText;
                $.ajax({
                    type: "POST",
                    url: url,
                    data: data,
                    dataType: "json",
                    success: function (data){
                        let amount = parseFloat(data.amount).toFixed(2);
                        
						if(amount < 0) it++;
						else facturasnormales ++;
						if(!checked){console.log(amount < 0);
							if(amount < 0) it-=2;
							else facturasnormales-=2;
						}
						let discount = parseInt(data.discount);
						//if(it <= 1){
							if (amount < 0)
							{
								let rc_element = \'total_credit\';
							}
							else
							{
								let rc_element = \'total_amount\';
							}
							if(!isNaN(amount) && !isNaN(discount) && amount>0){
								let total = 0.0
								if (checked) {
									total = parseFloat(total_amount) + parseFloat(amount);
									document.getElementById("discount_input").value = discount;
									console.log(total_amount+" + "+amount+" = "+total);
								}
								else {
									total = parseFloat(total_amount) - parseFloat(amount);
									document.getElementById("discount_input").value = "";
									console.log(total_amount+" - "+amount+" = "+total);
								}
								total = total.toFixed(2);
								document.getElementById("total_amount").innerText = "";
								document.getElementById("total_amount").innerText = total;
							}
							if(!isNaN(amount) && !isNaN(discount) && amount<0){
								let total = 0.0
								if (checked) {
									total = parseFloat(total_credit) + parseFloat(amount);
									document.getElementById("discount_input").value = discount;
									console.log(total_credit+" + "+amount+" = "+total);
								}
								else {
									total = parseFloat(total_credit) - parseFloat(amount);
									document.getElementById("discount_input").value = "";
									console.log(total_credit+" - "+amount+" = "+total);
								}
								total = total.toFixed(2);
								document.getElementById("total_credit").innerText = "";
								document.getElementById("total_credit").innerText = total;
							}
						//}
						console.log(it);console.log(facturasnormales);
						/*if(it > 0 && facturasnormales > 1){
							total_amount = document.getElementById("total_amount").innerText;
                			total_credit = document.getElementById("total_credit").innerText;
							inp.prop("checked", false);console.log(parseFloat(amount)< 0.00);
							if(amount < 0) it--;
							else facturasnormales --;

							if(!isNaN(amount) && !isNaN(discount) && amount>0){
								let total = 0.0
								total = parseFloat(total_amount) - parseFloat(amount);
								document.getElementById("discount_input").value = "";
								console.log(total_amount+" - "+amount+" = "+total);
								total = total.toFixed(2);
								document.getElementById("total_amount").innerText = "";
								document.getElementById("total_amount").innerText = total;
							}
							if(!isNaN(amount) && !isNaN(discount) && amount<0){
								let total = 0.0
								total = parseFloat(total_credit) - parseFloat(amount);
								document.getElementById("discount_input").value = "";
								console.log(total_credit+" - "+amount+" = "+total);
								total = total.toFixed(2);
								document.getElementById("total_credit").innerText = "";
								document.getElementById("total_credit").innerText = total;
							}

							console.log(it);console.log(facturasnormales);
							alert("Solo se permite una factura por nota(s) de crédito");
						}*/
                    },
                    error: function (err){
                        console.log(err);
                    }
                });
            });
	        function applyDiscount(){
	            let total_amount = parseFloat(document.getElementById("total_amount").innerText).toFixed(2);
	            let total_credit = parseFloat(document.getElementById("total_credit").innerText).toFixed(2);
	            let discountApplied = parseFloat(document.getElementById("discount_input").value).toFixed(2);
	            let amountDiscounted = (total_amount * discountApplied / 100) ;
	            let total_discount = total_amount - amountDiscounted;
	            let total_disc_credit = parseFloat(total_discount) + parseFloat(total_credit);
	            document.getElementById("total_discount").innerText = total_disc_credit.toFixed(2);
                document.getElementById("discount_applied").value = discountApplied;
                document.getElementById("total_amount_disc").innerText = total_discount.toFixed(2);
                
                
			};
			
			$("#cfdi_filter_button").click(function(){
				let state = $("#cfdi_filter").val()
				if(state === "0"){
					$("#cfdi_filter").val("1")
					$(this).attr("title", "Filtrando: <b>Facturas Timbradas</b>")
					$(this).attr("style", "opacity:70%;color:#090;")
					$(this).removeClass("fa-circle-o")
					$(this).addClass("fa-check-circle-o")
				}
				else if(state === "1"){
					$("#cfdi_filter").val("2")
					$(this).attr("title", "Filtrando: <b>Facturas con Timbrado Cancelado</b>")
					$(this).attr("style", "opacity:70%;color:#900;")
					$(this).removeClass("fa-check-circle-o")
					$(this).addClass("fa-times-circle-o")
				}
				else if(state === "2"){
					$("#cfdi_filter").val("0")
					$(this).attr("title", "<b>Sin Filtro</b>")
					$(this).attr("style", "opacity:70%;")
					$(this).removeClass("fa-times-circle-o")
					$(this).addClass("fa-circle-o")
				}
				
			})


        </script>
	';

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>\n";
	print '</div>';
    // Hidden input for discount to apply
    print '<input type="hidden" value="" name="discount_applied" id="discount_applied"/>';
    print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'invoicing', 0, '', '', '');
	print "</form>\n";
	print '<form method="POST" id="FormularioExportacion" action="export_table.php">';
    print '<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" value=\''.json_encode($arrayfields).'\'/>';
    print '<input type="hidden" id="sql" name="sql" value="'.$sql.'"/>';
    print '<input type="hidden" id="factures" name="factures"/>';
    print '</form>';

	print '<form method="POST" id="FormularioExportacion2" action="export_table_ticket.php">';
    print '<input type="hidden" id="datos_a_enviar" name="datos_a_enviar" value=\''.json_encode($arrayfields).'\'/>';
    print '<input type="hidden" id="sql" name="sql" value="'.$sql.'"/>';
	//Desde Hasta fecha factura
    print '<input type="hidden" id="date_start_facturemonth" name="date_start_facturemonth" value="'.$date_start_facturemonth.'"/>';
    print '<input type="hidden" id="date_start_factureday" name="date_start_factureday" value="'.$date_start_factureday.'"/>';
    print '<input type="hidden" id="date_start_factureyear" name="date_start_factureyear" value="'.$date_start_factureyear.'"/>';
    print '<input type="hidden" id="date_end_facturemonth" name="date_end_facturemonth" value="'.$date_end_facturemonth.'"/>';
    print '<input type="hidden" id="date_end_factureday" name="date_end_factureday" value="'.$date_end_factureday.'"/>';
    print '<input type="hidden" id="date_end_factureyear" name="date_end_factureyear" value="'.$date_end_factureyear.'"/>';
    print '<input type="hidden" id="limit" name="limit" value="'.$limit.'"/>';
    print '<input type="hidden" id="factures" name="factures"/>';
    print '</form>';
	
    print '<div style="font-size:16px; width:450px;">
            <strong style="">Monto a pagar: $ <span id="total_amount" style="float:right;margin-right:70px;">0.00</span></strong><br>
            <strong style=""><span style="font-size:0.8em;font-weight:normal;">(-)</span> Descuento: % <input type="number" value="0" id="discount_input" name="discount_input"><button onclick="applyDiscount()">Aplicar</button></strong><br>
            <strong style="color: darkblue;">Monto a pagar con Desc.: $ <span id="total_amount_disc" style="float:right;margin-right:70px;">0.00</span></strong><br>
            <strong style="color: #900;"><span style="font-size:0.8em;font-weight:normal;">(-)</span> Notas de Crédito: $ <span id="total_credit" style="float:right;margin-right:70px;">0.00</span></strong><br>
            <strong style="color: green;">Total con descuento: $ <span id="total_discount" style="float:right;margin-right:70px;">0.00</span></strong><br>
        </div>';

	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $user->rights->facture->lire;
	$delallowed = $user->rights->facture->creer;
	$title = '';

	print $formfile->showdocuments('massfilesarea_invoices', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);

	?>
	<script>
		$(document).ready(function(){
			$("input[name='confirmmassaction']").click(function(e){
				if($("select[name='massaction']").val() == 'generate_payment'){
					if(it > 0 && facturasnormales > 1){
						alert("Solo se permite una factura por nota(s) de crédito");
						return false;
					}
				}
			});
		});
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
