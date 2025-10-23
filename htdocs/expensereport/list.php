<?php
/* Copyright (C) 2003     	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004     	Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet	     <fmarcet@2byte.es>
 * Copyright (C) 2018       Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2019       Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2019       Frédéric France      <frederic.france@netlogic.fr>
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
 *	    \file       htdocs/expensereport/list.php
 *      \ingroup    expensereport
 *		\brief      list of expense reports
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'trips'));

$action      = GETPOST('action', 'aZ09');
$massaction  = GETPOST('massaction', 'alpha');
$show_files  = GETPOST('show_files', 'int');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect    = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'expensereportlist';

$childids = $user->getAllChildIds(1);

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'expensereport', '', '');
$id = GETPOST('id', 'int');
// If we are on the view of a specific user
if ($id > 0)
{
    $canread = 0;
    if ($id == $user->id) $canread = 1;
    if (!empty($user->rights->expensereport->readall)) $canread = 1;
    if (!empty($user->rights->expensereport->lire) && in_array($id, $childids)) $canread = 1;
    if (!$canread)
    {
        accessforbidden();
    }
}

$diroutputmassaction = $conf->expensereport->dir_output.'/temp/massgeneration/'.$user->id;


// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "d.date_debut";


$sall         = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref   = GETPOST('search_ref', 'alpha');
$search_referencia   = GETPOST('search_referencia', 'alpha');
$search_user  = GETPOST('search_user', 'int');
$search_amount_ht = GETPOST('search_amount_ht', 'alpha');
$search_amount_vat = GETPOST('search_amount_vat', 'alpha');
$search_amount_ttc = GETPOST('search_amount_ttc', 'alpha');
$search_status = (GETPOST('search_status', 'intcomma') != '' ?GETPOST('search_status', 'intcomma') : GETPOST('statut', 'intcomma'));
$month_start  = GETPOST("month_start", "int");
$year_start   = GETPOST("year_start", "int");
$day_start    = GETPOST("day_start", "int");
$day_end      = GETPOST("day_end", "int");
$month_end    = GETPOST("month_end", "int");
$year_end     = GETPOST("year_end", "int");
$optioncss    = GETPOST('optioncss', 'alpha');
$search_projet = GETPOST('search_projet', 'alpha');
$search_tipo = GETPOST('search_tipo', 'alpha');
if ($search_status == '') $search_status = -1;
if ($search_user == '') $search_user = -1;
$search_warehouse = GETPOST('search_warehouse', 'alpha');
// Start date
$date_start_smonth=GETPOST("date_start_smonth");
$date_start_sday=GETPOST("date_start_sday");
$date_start_syear=GETPOST("date_start_syear");
$date_end_smonth=GETPOST("date_end_smonth");
$date_end_sday=GETPOST("date_end_sday");
$date_end_syear=GETPOST("date_end_syear");
// Aprove date
$date_start_aprovemonth=GETPOST("date_start_aprovemonth");
$date_start_aproveday=GETPOST("date_start_aproveday");
$date_start_aproveyear=GETPOST("date_start_aproveyear");
$date_end_aprovemonth=GETPOST("date_end_aprovemonth");
$date_end_aproveday=GETPOST("date_end_aproveday");
$date_end_aproveyear=GETPOST("date_end_aproveyear");

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new ExpenseReport($db);
$hookmanager->initHooks(array('expensereportlist'));
$extrafields = new ExtraFields($db);
$formproduct = new FormProduct($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('expensereport');

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'd.ref'=>'Ref',
    'd.note_public'=>"NotePublic",
    'u.lastname'=>'Lastname',
    'u.firstname'=>"Firstname",
    'u.login'=>"Login",
);
if (empty($user->socid)) $fieldstosearchall["d.note_private"] = "NotePrivate";

$arrayfields = array(
    'd.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'd.referencia'=>array('label'=>$langs->trans("Reference"), 'checked'=>1),
	'd.fk_projet'=>array('label'=>$langs->trans("Project"), 'checked'=>1),
	'd.fk_c_type_fees'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
    'user'=>array('label'=>$langs->trans("User"), 'checked'=>1),
    'd.date_debut'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1),
    'd.date_fin'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
    'd.date_valid'=>array('label'=>$langs->trans("DateValidation"), 'checked'=>1),
    'd.date_approve'=>array('label'=>$langs->trans("DateApprove"), 'checked'=>1),
    'd.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'd.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>1),
    'd.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>1),
    'd.date_create'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'd.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'd.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'd.fk_warehouse'=>array('label'=>$langs->trans("Warehouse"), 'checked'=>1, 'position'=>1000),
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

$canedituser = (!empty($user->admin) || $user->rights->user->user->creer);

$objectuser = new User($db);


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

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
	    $search_ref = "";
		$search_referencia = "";
	    $search_user = "";
	    $search_amount_ht = "";
	    $search_amount_vat = "";
	    $search_amount_ttc = "";
	    $search_status = "";
	    $month_start = "";
	    $year_start = "";
	    $month_end = "";
	    $year_end = "";
	    $day_end = "";
	    $toselect = '';
		$search_projet = '';
		$search_tipo = '';
		$search_warehouse = '';
		// Start date
		$date_start_sday = '';
		$date_start_smonth = '';
		$date_start_syear = '';
		$date_end_sday = '';
		$date_end_smonth = '';
		$date_end_syear = '';
		// Aprove date
		$date_start_aproveday = '';
		$date_start_aprovemonth = '';
		$date_start_aproveyear = '';
		$date_end_aproveday = '';
		$date_end_aprovemonth = '';
		$date_end_aproveyear = '';
	    $search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
    $objectclass = 'ExpenseReport';
    $objectlabel = 'ExpenseReport';
    $permissiontoread = $user->rights->expensereport->lire;
    $permissiontodelete = $user->rights->expensereport->supprimer;
    $uploaddir = $conf->expensereport->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

    if ($action == 'update' && !$cancel)
    {
    	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    	if ($canedituser)    // Case we can edit all field
    	{
    		$error = 0;

    		if (!$error)
    		{
    			$objectuser->fetch($id);

    			$objectuser->oldcopy = clone $objectuser;

    			$db->begin();

    			$objectuser->default_range = GETPOST('default_range');
    			$objectuser->default_c_exp_tax_cat = GETPOST('default_c_exp_tax_cat');

    			if (!$error) {
    				$ret = $objectuser->update($user);
    				if ($ret < 0) {
    					$error++;
    					if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
    						$langs->load("errors");
    						setEventMessages($langs->trans("ErrorLoginAlreadyExists", $objectuser->login), null, 'errors');
    					}
    					else
    					{
    						setEventMessages($objectuser->error, $objectuser->errors, 'errors');
    					}
    				}
    			}

    			if (!$error && !count($objectuser->errors)) {
    				setEventMessages($langs->trans("UserModified"), null, 'mesgs');
    				$db->commit();
    			}
    			else {
    				$db->rollback();
    			}
    		}
    	}
    }
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$projectstatic = new Project($db);

$fuser = new User($db);

$title = $langs->trans("ListOfTrips");
llxHeader('', $title);

$max_year = 5;
$min_year = 10;

// Get current user id
$user_id = $user->id;

if ($id > 0)
{
	// Charge utilisateur edite
	$fuser->fetch($id, '', '', 1);
	$fuser->getrights();
	$user_id = $fuser->id;

	$search_user = $user_id;
}

$sql = "SELECT d.rowid, d.ref, d.referencia, d.fk_user_author, d.total_ht, d.total_tva, d.total_ttc, d.fk_statut as status,";
$sql .= " d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve, d.note_private, d.note_public, d.fk_warehouse, ent.ref as warehouse,";
$sql .= " u.rowid as id_user, u.firstname, u.lastname, u.login, u.email, u.statut, u.photo, d.fk_projet, pr.ref as project_ref, pr.title as project_label, tf.label as tipo";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as d";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as pr ON pr.rowid = d.fk_projet";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_fees as tf ON tf.id = d.fk_c_type_fees";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent ON ent.rowid = d.fk_warehouse";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (d.rowid = ef.fk_object)";
$sql .= ", ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE d.fk_user_author = u.rowid AND d.entity IN (".getEntity('expensereport').")";
// Search all
if (!empty($sall)) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
// Ref
if (!empty($search_ref)) $sql .= natural_search('d.ref', $search_ref);
// Referencia
if (!empty($search_referencia)) $sql .= natural_search('d.referencia', $search_referencia);
// Proyecto
if (!empty($search_projet)) $sql .= natural_search('pr.ref', $search_projet);
// Tipo
if (!empty($search_tipo)) $sql .= natural_search('tf.label', $search_tipo);
// Start date
if($date_start_syear && !$date_end_syear)
    $sql.=" AND d.date_debut >= '".$date_start_syear."-".$date_start_smonth."-".$date_start_sday."'";
else if (!$date_start_syear && $date_end_syear)
    $sql.=" AND d.date_debut <= '".$date_end_syear."-".$date_end_smonth."-".$date_end_sday."'";
else if ($date_start_syear && $date_end_syear)
    $sql.=" AND d.date_debut BETWEEN '".$date_start_syear."-".$date_start_smonth."-".$date_start_sday."' AND '".$date_end_syear."-".$date_end_smonth."-".$date_end_sday."'";
// Aprove date
if($date_start_aproveyear && !$date_end_aproveyear)
    $sql.=" AND DATE(d.date_approve) >= '".$date_start_aproveyear."-".$date_start_aprovemonth."-".$date_start_aproveday."'";
else if (!$date_start_aproveyear && $date_end_aproveyear)
    $sql.=" AND  DATE(d.date_approve) <= '".$date_end_aproveyear."-".$date_end_aprovemonth."-".$date_end_aproveday."'";
else if ($date_start_aproveyear && $date_end_aproveyear)
    $sql.=" AND  DATE(d.date_approve) BETWEEN '".$date_start_aproveyear."-".$date_start_aprovemonth."-".$date_start_aproveday."' AND '".$date_end_aproveyear."-".$date_end_aprovemonth."-".$date_end_aproveday."'";

// Date End
$sql .= dolSqlDateFilter("d.date_fin", $day_end, $month_end, $year_end);

if ($search_amount_ht != '') $sql .= natural_search('d.total_ht', $search_amount_ht, 1);
if ($search_amount_ttc != '') $sql .= natural_search('d.total_ttc', $search_amount_ttc, 1);
// User
if ($search_user != '' && $search_user >= 0) $sql .= " AND u.rowid = '".$db->escape($search_user)."'";
// Status
if ($search_status != '' && $search_status >= 0) $sql .= " AND d.fk_statut IN (".$db->escape($search_status).")";
// RESTRICT RIGHTS
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)
    && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->expensereport->writeall_advance)))
{
	$sql .= " AND d.fk_user_author IN (".join(',', $childids).")\n";
}
if ($search_warehouse > 0) $sql .= " AND d.fk_warehouse = '".$search_warehouse."' ";
if(!$user->rights->stock->show_all_warehouses) $sql.= " AND d.fk_warehouse = ".$user->fk_warehouse."";
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
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall)					$param .= "&sall=".urlencode($sall);
	if ($search_ref)			$param .= "&search_ref=".urlencode($search_ref);
	if ($search_referencia)		$param .= "&search_referencia=".urlencode($search_referencia);
	if ($search_projet)			$param .= "&search_projet=".urlencode($search_projet);
	if ($search_tipo)			$param .= "&search_tipo=".urlencode($search_tipo);
	if ($search_user)			$param .= "&search_user=".urlencode($search_user);
	if ($search_amount_ht)		$param .= "&search_amount_ht=".urlencode($search_amount_ht);
	if ($search_amount_ttc)		$param .= "&search_amount_ttc=".urlencode($search_amount_ttc);
	if ($search_status >= 0)  	$param .= "&search_status=".urlencode($search_status);
	if ($optioncss != '')       $param .= '&optioncss='.urlencode($optioncss);
	if ($search_warehouse > 0)  $param .= "&search_warehouse=".urlencode($search_warehouse);
	// Date start
    if ($date_start_sday)         $param .= '&date_start_sday='.urlencode($date_start_sday);
    if ($date_start_smonth)       $param .= '&date_start_smonth='.urlencode($date_start_smonth);
    if ($date_start_syear)        $param .= '&date_start_syear='.urlencode($date_start_syear);
    if ($date_end_sday)         $param .= '&date_end_sday='.urlencode($date_end_sday);
    if ($date_end_smonth)       $param .= '&date_end_smonth='.urlencode($date_end_smonth);
    if ($date_end_syear)        $param .= '&date_end_syear='.urlencode($date_end_syear);
	// Aprove start
    if ($date_start_aproveday)         $param .= '&date_start_aproveday='.urlencode($date_start_aproveday);
    if ($date_start_aprovemonth)       $param .= '&date_start_aprovemonth='.urlencode($date_start_aprovemonth);
    if ($date_start_aproveyear)        $param .= '&date_start_aproveyear='.urlencode($date_start_aproveyear);
    if ($date_end_aproveday)         $param .= '&date_end_aproveday='.urlencode($date_end_aproveday);
    if ($date_end_aprovemonth)       $param .= '&date_end_aprovemonth='.urlencode($date_end_aprovemonth);
    if ($date_end_aproveyear)        $param .= '&date_end_aproveyear='.urlencode($date_end_aproveyear);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
	    'builddoc'=>$langs->trans("PDFMerge"),
	    'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->expensereport->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Lines of title fields
	print '<form id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="'.($action == 'edit' ? 'update' : 'list').'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	if ($id > 0) print '<input type="hidden" name="id" value="'.$id.'">';

	if ($id > 0)		// For user tab
	{
		$title = $langs->trans("User");
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		$head = user_prepare_head($fuser);

		dol_fiche_head($head, 'expensereport', $title, -1, 'user');

		dol_banner_tab($fuser, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		if (!empty($conf->global->MAIN_USE_EXPENSE_IK))
		{
			print '<table class="border centpercent">';

			if ($action == 'edit')
			{
				print '<tr><td class="titlefield">'.$langs->trans("DefaultCategoryCar").'</td>';
				print '<td>';
				print $form->selectExpenseCategories($fuser->default_c_exp_tax_cat, 'default_c_exp_tax_cat', 1);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("DefaultRangeNumber").'</td>';
				print '<td>';
				$maxRangeNum = ExpenseReportIk::getMaxRangeNumber($fuser->default_c_exp_tax_cat);
				print $form->selectarray('default_range', range(0, $maxRangeNum), $fuser->default_range);
				print '</td></tr>';
			}
			else
			{
				print '<tr><td class="titlefield">'.$langs->trans("DefaultCategoryCar").'</td>';
				print '<td class="fk_c_exp_tax_cat">';
				print dol_getIdFromCode($db, $fuser->default_c_exp_tax_cat, 'c_exp_tax_cat', 'rowid', 'label');
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("DefaultRangeNumber").'</td>';
				print '<td>';
				print $fuser->default_range;
				print '</td></tr>';
			}

			print '</table>';
		}

		print '</div>';

		/*if (empty($conf->global->HOLIDAY_HIDE_BALANCE))
		{
			print '<div class="underbanner clearboth"></div>';

			print '<br>';

			showMyBalance($holiday, $user_id);
		}*/

		dol_fiche_end();

		if ($action != 'edit')
		{
			print '<div class="tabsAction">';

			if (!empty($conf->global->MAIN_USE_EXPENSE_IK))
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$user_id.'" class="butAction">'.$langs->trans("Modify").'</a>';
			}

			$childids = $user->getAllChildIds(1);

			$canedit = ((in_array($user_id, $childids) && $user->rights->expensereport->creer)
				|| ($conf->global->MAIN_USE_ADVANCED_PERMS && $user->rights->expensereport->writeall_advance));

			// Buttons for actions
			if ($canedit)
			{
				print '<a href="'.DOL_URL_ROOT.'/expensereport/card.php?action=create&fk_user_author='.$fuser->id.'" class="butAction">'.$langs->trans("AddTrip").'</a>';
			}

			print '</div>';
		}
		else
		{
			print '<div class="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '</div><br>';
		}
	}
	else
	{
		$title = $langs->trans("ListTripsAndExpenses");

		$newcardbutton = '';
		$newcardbutton .= dolGetButtonTitle($langs->trans('Exportar Listado'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/expensereport/export_csv.php');
		if ($user->rights->expensereport->creer)
		{
            $newcardbutton .= dolGetButtonTitle($langs->trans('NewTrip'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/expensereport/card.php?action=create');
		}

		print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, $newcardbutton, '', $limit);
	}

	$topicmail = "SendExpenseReport";
	$modelmail = "expensereport";
	$objecttmp = new ExpenseReport($db);
	$trackid = 'exp'.$object->id;
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
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Filters
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['d.ref']['checked']))
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" size="15" type="text" name="search_ref" value="'.$search_ref.'">';
        print '</td>';
	}

	//Referencia
	if (!empty($arrayfields['d.referencia']['checked']))
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" size="15" type="text" name="search_referencia" value="'.$search_referencia.'">';
        print '</td>';
	}

	//Proyecto
	if (!empty($arrayfields['d.fk_projet']['checked']))
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" size="15" type="text" name="search_projet" value="'.$search_projet.'">';
        print '</td>';
	}

	//Tipo
	if (!empty($arrayfields['d.fk_c_type_fees']['checked']))
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" size="15" type="text" name="search_tipo" value="'.$search_tipo.'">';
        print '</td>';
	}

	// Almacén
	if (!empty($arrayfields['d.fk_warehouse']['checked']))
	{
		print '<td class="liste_titre">';
		if($user->rights->stock->show_all_warehouses){
			print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen', 1);
		}
		print '</td>';
	}

	// User
	if (!empty($arrayfields['user']['checked']))
	{
        if ($user->rights->expensereport->readall || $user->rights->expensereport->lire_tous)
    	{
    		print '<td class="liste_titre maxwidthonspartphone" align="left">';
    		print $form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
    		print '</td>';
    	} else {
    		print '<td class="liste_titre">&nbsp;</td>';
    	}
	}
	// Start date
	if (!empty($arrayfields['d.date_debut']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="center">';
		if($date_start_syear)$date_start_s=dol_mktime(0,0,0,$date_start_smonth,$date_start_sday,$date_start_syear);
		else $date_start_s=null;
        if($date_end_syear)$date_end_s=dol_mktime(0,0,0,$date_end_smonth,$date_end_sday,$date_end_syear);
        else $date_end_s=null;
        print "Desde<br>".$form->select_date($date_start_s,'date_start_s',0,0,0,'',1,0,1);
        print "<br>Hasta<br>".$form->select_date($date_end_s,'date_end_s',0,0,0,'',1,0,1);
		print '</td>';
	}
	// Date end
	if (!empty($arrayfields['d.date_fin']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
		if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
			print '<input class="flat width25" type="text" maxlength="2" name="day_end" value="'.dol_escape_htmltag($day_end).'">';
		}
		print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
    	$formother->select_year($year_end, 'year_end', 1, $min_year, $max_year);
    	print '</td>';
    }
	// Date valid
	if (!empty($arrayfields['d.date_valid']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
    	//print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
    	//$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
    	print '</td>';
    }
	// Date approve
	if (!empty($arrayfields['d.date_approve']['checked']))
	{
    	print '<td class="liste_titre nowraponall" align="center">';
		if($date_start_aproveyear)$date_start_aprove=dol_mktime(0,0,0,$date_start_aprovemonth,$date_start_aproveday,$date_start_aproveyear);
		else $date_start_aprove=null;
        if($date_end_aproveyear)$date_end_aprove=dol_mktime(0,0,0,$date_end_aprovemonth,$date_end_aproveday,$date_end_aproveyear);
        else $date_end_aprove=null;
        print "Desde<br>".$form->select_date($date_start_aprove,'date_start_aprove',0,0,0,'',1,0,1);
        print "<br>Hasta<br>".$form->select_date($date_end_aprove,'date_end_aprove',0,0,0,'',1,0,1);
		print '</td>';
    }
    // Amount with no tax
	if (!empty($arrayfields['d.total_ht']['checked']))
	{
    	print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_ht" value="'.$search_amount_ht.'"></td>';
	}
	if (!empty($arrayfields['d.total_vat']['checked']))
	{
	    print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_vat" value="'.$search_amount_vat.'"></td>';
	}
	// Amount with all taxes
	if (!empty($arrayfields['d.total_ttc']['checked']))
	{
	    print '<td class="liste_titre right"><input class="flat" type="text" size="5" name="search_amount_ttc" value="'.$search_amount_ttc.'"></td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['d.date_create']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['d.tms']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Status
	if (!empty($arrayfields['d.fk_statut']['checked']))
	{
    	print '<td class="liste_titre right">';
    	select_expensereport_statut($search_status, 'search_status', 1, 1);
    	print '</td>';
	}
	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['d.ref']['checked']))                  print_liste_field_titre($arrayfields['d.ref']['label'], $_SERVER["PHP_SELF"], "d.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['d.referencia']['checked']))           print_liste_field_titre($arrayfields['d.referencia']['label'], $_SERVER["PHP_SELF"], "d.referencia", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['d.fk_projet']['checked']))            print_liste_field_titre($arrayfields['d.fk_projet']['label'], $_SERVER["PHP_SELF"], "d.fk_projet", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['d.fk_c_type_fees']['checked']))       print_liste_field_titre($arrayfields['d.fk_c_type_fees']['label'], $_SERVER["PHP_SELF"], "d.fk_c_type_fees", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['d.fk_warehouse']['checked']))         print_liste_field_titre($arrayfields['d.fk_warehouse']['label'], $_SERVER["PHP_SELF"], "ent.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['user']['checked']))                   print_liste_field_titre($arrayfields['user']['label'], $_SERVER["PHP_SELF"], "u.lastname", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['d.date_debut']['checked']))           print_liste_field_titre($arrayfields['d.date_debut']['label'], $_SERVER["PHP_SELF"], "d.date_debut", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.date_fin']['checked']))             print_liste_field_titre($arrayfields['d.date_fin']['label'], $_SERVER["PHP_SELF"], "d.date_fin", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.date_valid']['checked']))           print_liste_field_titre($arrayfields['d.date_valid']['label'], $_SERVER["PHP_SELF"], "d.date_valid", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.date_approve']['checked']))         print_liste_field_titre($arrayfields['d.date_approve']['label'], $_SERVER["PHP_SELF"], "d.date_approve", "", $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.total_ht']['checked']))             print_liste_field_titre($arrayfields['d.total_ht']['label'], $_SERVER["PHP_SELF"], "d.total_ht", "", $param, 'align="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.total_vat']['checked']))            print_liste_field_titre($arrayfields['d.total_vat']['label'], $_SERVER["PHP_SELF"], "d.total_tva", "", $param, 'align="right"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.total_ttc']['checked']))            print_liste_field_titre($arrayfields['d.total_ttc']['label'], $_SERVER["PHP_SELF"], "d.total_ttc", "", $param, 'align="right"', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['d.date_create']['checked'])) print_liste_field_titre($arrayfields['d.date_create']['label'], $_SERVER["PHP_SELF"], "d.date_create", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.tms']['checked']))         print_liste_field_titre($arrayfields['d.tms']['label'], $_SERVER["PHP_SELF"], "d.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
	if (!empty($arrayfields['d.fk_statut']['checked']))   print_liste_field_titre($arrayfields['d.fk_statut']['label'], $_SERVER["PHP_SELF"], "d.fk_statut", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	$total_total_ht = 0;
	$total_total_ttc = 0;
	$total_total_tva = 0;

	$expensereportstatic = new ExpenseReport($db);
	$usertmp = new User($db);

	if ($num > 0)
	{
        $i = 0;
    	$totalarray = array();
 	    while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($resql);

			$expensereportstatic->id = $obj->rowid;
			$expensereportstatic->ref = $obj->ref;

			$expensereportstatic->fk_projet = $obj->fk_projet;
			$projectstatic->id = $obj->fk_projet;
			$projectstatic->ref = $obj->project_ref;
			$projectstatic->title = $obj->project_label;
			
			$expensereportstatic->status = $obj->status;
			$expensereportstatic->date_debut = $db->jdate($obj->date_debut);
			$expensereportstatic->date_fin = $db->jdate($obj->date_fin);
			$expensereportstatic->date_create = $db->jdate($obj->date_create);
			$expensereportstatic->date_modif = $db->jdate($obj->date_modif);
			$expensereportstatic->date_valid = $db->jdate($obj->date_valid);
			$expensereportstatic->date_approve = $db->jdate($obj->date_approve);
			$expensereportstatic->note_private = $obj->note_private;
			$expensereportstatic->note_public = $obj->note_public;


			print '<tr class="oddeven">';
			// Ref
			if (!empty($arrayfields['d.ref']['checked'])) {
    			print '<td>';
                print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                print '<td class="nobordernopadding nowrap">';
    			print $expensereportstatic->getNomUrl(1);
    			print '</td>';
    			// Warning late icon and note
        		print '<td class="nobordernopadding nowrap">';
    			if ($expensereportstatic->status == 2 && $expensereportstatic->hasDelay('toappove')) print img_warning($langs->trans("Late"));
    			if ($expensereportstatic->status == 5 && $expensereportstatic->hasDelay('topay')) print img_warning($langs->trans("Late"));
    			if (!empty($obj->note_private) || !empty($obj->note_public))
    			{
    			    print ' <span class="note">';
    			    print '<a href="'.DOL_URL_ROOT.'/expensereport/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"), 'object_generic').'</a>';
    			    print '</span>';
    			}
    			print '</td>';
    			print '<td width="16" class="nobordernopadding hideonsmartphone right">';
    			$filename=dol_sanitizeFileName($obj->ref);
    			$filedir=$conf->expensereport->dir_output . '/' . dol_sanitizeFileName($obj->ref);
    			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
    			print $formfile->getDocumentsLink($expensereportstatic->element, $filename, $filedir);
    			print '</td>';
    			print '</tr></table>';
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
			}

			//Referencia
			if (!empty($arrayfields['d.referencia']['checked'])){
				print '<td class="nowrap">';
				print $obj->referencia;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			
			//Proyecto
			if (!empty($arrayfields['d.fk_projet']['checked'])){
				// Project ref
				print '<td class="nowrap">';
				if ($obj->fk_projet > 0) {
					print $projectstatic->getNomUrl(1);
				}
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			//Tipo
			if (!empty($arrayfields['d.fk_c_type_fees']['checked'])){
				// Project ref
				print '<td class="nowrap">';
				print $obj->tipo;
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}

			// Almacén
			if (! empty($arrayfields['d.fk_warehouse']['checked']))
			{
				print "<td>".$obj->warehouse."</td>\n";
				if (! $i) $totalarray['nbfield']++;
			}

			// User
			if (! empty($arrayfields['user']['checked'])) {
			    print '<td class="left">';
    			$usertmp->id=$obj->id_user;
    			$usertmp->lastname=$obj->lastname;
    			$usertmp->firstname=$obj->firstname;
    			$usertmp->login=$obj->login;
    			$usertmp->statut=$obj->statut;
    			$usertmp->photo=$obj->photo;
    			$usertmp->email=$obj->email;
    			print $usertmp->getNomUrl(-1);
    			print '</td>';
    			if (! $i) $totalarray['nbfield']++;
			}
			// Start date
			if (! empty($arrayfields['d.date_debut']['checked'])) {
                print '<td class="center">'.($obj->date_debut > 0 ? dol_print_date($db->jdate($obj->date_debut), 'day') : '').'</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // End date
			if (! empty($arrayfields['d.date_fin']['checked'])) {
			    print '<td class="center">'.($obj->date_fin > 0 ? dol_print_date($db->jdate($obj->date_fin), 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Date validation
			if (! empty($arrayfields['d.date_valid']['checked'])) {
			    print '<td class="center">'.($obj->date_valid > 0 ? dol_print_date($db->jdate($obj->date_valid), 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Date approval
			if (! empty($arrayfields['d.date_approve']['checked'])) {
			    print '<td class="center">'.($obj->date_approve > 0 ? dol_print_date($db->jdate($obj->date_approve), 'day') : '').'</td>';
			    if (! $i) $totalarray['nbfield']++;
			}
			// Amount HT
            if (! empty($arrayfields['d.total_ht']['checked']))
            {
    		      print '<td class="right">'.price($obj->total_ht)."</td>\n";
    		      if (! $i) $totalarray['nbfield']++;
    		      if (! $i) $totalarray['pos'][$totalarray['nbfield']]='d.total_ht';
    		      $totalarray['val']['d.total_ht'] += $obj->total_ht;
            }
            // Amount VAT
            if (! empty($arrayfields['d.total_vat']['checked']))
            {
                print '<td class="right">'.price($obj->total_tva)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['pos'][$totalarray['nbfield']]='d.total_tva';
                $totalarray['val']['d.total_tva'] += $obj->total_tva;
            }
            // Amount TTC
            if (! empty($arrayfields['d.total_ttc']['checked']))
            {
                print '<td class="right">'.price($obj->total_ttc)."</td>\n";
                if (! $i) $totalarray['nbfield']++;
                if (! $i) $totalarray['pos'][$totalarray['nbfield']]='d.total_ttc';
                $totalarray['val']['d.total_ttc'] += $obj->total_ttc;
            }

            // Extra fields
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
            // Fields from hook
            $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
            $reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Date creation
            if (! empty($arrayfields['d.date_create']['checked']))
            {
                print '<td class="nowrap center">';
                print dol_print_date($db->jdate($obj->date_create), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Date modification
            if (! empty($arrayfields['d.tms']['checked']))
            {
                print '<td class="nowrap center">';
                print dol_print_date($db->jdate($obj->date_modif), 'dayhour');
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Status
            if (! empty($arrayfields['d.fk_statut']['checked']))
            {
                print '<td class="nowrap right">'.$expensereportstatic->getLibStatut(5).'</td>';
                if (! $i) $totalarray['nbfield']++;
            }
            // Action column
            print '<td class="nowrap center">';
            if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            {
                $selected=0;
                if (in_array($obj->rowid, $arrayofselected)) $selected=1;
                print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;

			print "</tr>\n";

			$total_total_ht = $total_total_ht + $obj->total_ht;
			$total_total_tva = $total_total_tva + $obj->total_tva;
			$total_total_ttc = $total_total_ttc + $obj->total_ttc;

			$i++;
		}
	}
	else
	{
		$colspan = 1;
		foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
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

	if (empty($id))
	{
		$hidegeneratedfilelistifempty = 1;
		if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty = 0;

		// Show list of available documents
		$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
		$urlsource .= str_replace('&amp;', '&', $param);

		$filedir = $diroutputmassaction;
		$genallowed = $user->rights->expensereport->lire;
		$delallowed = $user->rights->expensereport->creer;

		print $formfile->showdocuments('massfilesarea_expensereport', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
	}
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
