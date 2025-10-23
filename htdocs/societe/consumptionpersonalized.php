<?php
/* Copyright (C) 2012-2013 Philippe Berthet     <berthet@systune.be>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015	   Ferran Marcet		<fmarcet@2byte.es>
 *
 * Version V1.1 Initial version of Philippe Berthet
 * Version V2   Change to be compatible with 3.4 and enhanced to be more generic
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/consumption.php
 *  \ingroup    societe
 *	\brief      Add a tab on thirpdarty view to list all products/services bought or sells by thirdparty
 */

require("../mainpersonalized.inc.php");
//require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');
$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

// Sort & Order fields
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='dateprint';
$limit = $conf->liste_limit;

// Search fields
$sref=GETPOST("sref");
$refclient=GETPOST("refclient");
$sprod_fulldescr=GETPOST("sprod_fulldescr");
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$month2	= GETPOST('month2','int');
$year2	= GETPOST('year2','int');
$total = GETPOST("total");
$recibido = GETPOST("recibido");
$factstatus = GETPOST('factstatus','alpha');
$fichestatus = GETPOST('fichestatus');
$fiche_description = GETPOST('fiche_description','alpha');
$fiche_duration = GETPOST('$fiche_duration','alpha');

// Clean up on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $sref='';
    $refclient='';
    $sprod_fulldescr='';
    $year='';
    $month='';
    $year2='';
    $month2='';
    $total='';
    $recibido='';
    $factstatus='';
    $fichestatus='';
    $fiche_description='';
    $fiche_duration='';
}
// Customer or supplier selected in drop box
$thirdTypeSelect = GETPOST("third_select_id");
$type_element = GETPOST('type_element')?GETPOST('type_element'):'';


$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("suppliers");
$langs->load("propal");
$langs->load("interventions");
$langs->load("contracts");

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('consumptionthirdparty'));


/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$productstatic=new Product($db);

$title = $langs->trans("Referers",$object->name);
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

if (empty($socid))
{
	dol_print_error($db);
	exit;
}

dol_fiche_head($head, 'consumption', $langs->trans("ThirdParty"),0,'company');

print '<table class="border" width="100%">';
print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom');
print '</td></tr>';

// Alias names (commercial, trademark or alias names)
print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
print '<td colspan="3">'.$object->name_alias.'</td></tr>';

if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client) // cliente
{
	print '<tr><td>';
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
	if ($conf->pos->enabled ) $elementTypeArray['ticket']='Tickets';
	if ($conf->facture->enabled && $user->rights->facture->lire) $elementTypeArray['invoice']='Ventas';
	// Agregaremos la opcion de "Unidades/Vehiculos" en los objetos relacionados al cliente
}

print '</table>';

dol_fiche_end();
print '<br>';


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="socid" value="'.$socid.'">'."\n";


$sql_select='';
if ($type_element == 'ticket')
{
	require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
	$documentstatic=new Ticket($db);
    $sql_select =' SELECT f.rowid AS doc_id, f.ticketnumber AS doc_number, s.name_alias AS ref_client, f.date_ticket as dateprint,';
    //$sql_select .=' f.date_lim_reglement AS lim_date, f.total_ttc AS imp_total, pf.amount AS recibido, f.fk_statut AS status';
    $sql_select .=' f.total_ttc AS imp_total, pf.amount AS recibido, f.fk_statut AS status';
    $sql_select .=' FROM '.MAIN_DB_PREFIX.'pos_ticket AS f';
    $sql_select .=' INNER JOIN '.MAIN_DB_PREFIX.'pos_ticketdet AS fd  ON fd.fk_ticket = f.rowid';
    $sql_select .=' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
    $sql_select .=' LEFT JOIN '.MAIN_DB_PREFIX.'pos_paiement_ticket AS pf ON pf.fk_ticket = f.rowid';
    $where =' WHERE s.rowid = '.$socid.' and (f.fk_statut = 1 or f.fk_statut = 2)';
    $dateprint = 'f.date_ticket';
    $doc_number='f.ticketnumber';
    $ref_client='s.name_alias';
    $columntotal='f.total_ttc';
    $columnrecibido='pf.amount';
    $columnstatus='f.fk_statut';
    $thirdTypeSelect='ticket';
    $element = 'ticket';
}
if ($type_element == 'invoice')
{
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$documentstatic=new Facture($db);
    $sql_select =' SELECT f.rowid AS doc_id, f.ref AS doc_number, s.name_alias AS ref_client, f.datef as dateprint,';
    $sql_select .=' f.date_lim_reglement AS lim_date, f.total_ttc AS imp_total, pf.amount AS recibido, f.fk_statut AS status';
    $sql_select .=' FROM '.MAIN_DB_PREFIX.'facture AS f';
    $sql_select .=' INNER JOIN '.MAIN_DB_PREFIX.'facturedet AS fd  ON fd.fk_facture = f.rowid';
    $sql_select .=' INNER JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
    $sql_select .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture AS pf ON pf.fk_facture = f.rowid';
    $where =' WHERE s.rowid = '.$socid;
    $dateprint = 'f.datef';
    $columndatelimit = 'f.date_lim_reglement';
    $doc_number='f.ref';
    $ref_client='s.name_alias';
    $columntotal='f.total_ttc';
    $columnrecibido='pf.amount';
    $columnstatus='f.fk_statut';
    $thirdTypeSelect='invoice';
    $element = 'facture';
}


$sql = $sql_select;
$sql.= $where;
if ($month > 0) {
	if ($year > 0) {
		$start = dol_mktime(0, 0, 0, $month, 1, $year);
		$end = dol_time_plus_duree($start,1,'m') - 1;
		$sql.= " AND ".$dateprint." BETWEEN '".$db->idate($start)."' AND '".$db->idate($end)."'";
	} else {
		$sql.= " AND date_format(".$dateprint.", '%m') = '".sprintf('%02d',$month)."'";
	}
} else if ($year > 0) {
	$start = dol_mktime(0, 0, 0, 1, 1, $year);
	$end = dol_time_plus_duree($start,1,'y') - 1;
	$sql.= " AND ".$dateprint." BETWEEN '".$db->idate($start)."' AND '".$db->idate($end)."'";
}

if ($month2 > 0) {
	if ($year2 > 0) {
		$start2 = dol_mktime(0, 0, 0, $month2, 1, $year2);
		$end2 = dol_time_plus_duree($start2,1,'m') - 1;
		$sql.= " AND ".$columndatelimit." BETWEEN '".$db->idate($start2)."' AND '".$db->idate($end2)."'";
	} else {
		$sql.= " AND date_format(".$columndatelimit.", '%m') = '".sprintf('%02d',$month2)."'";
	}
} else if ($year2 > 0) {
	$start2 = dol_mktime(0, 0, 0, 1, 1, $year2);
	$end2 = dol_time_plus_duree($start2,1,'y') - 1;
	$sql.= " AND ".$columndatelimit." BETWEEN '".$db->idate($start2)."' AND '".$db->idate($end2)."'";
}

if ($sref) $sql.= " AND ".$doc_number." LIKE '%".$sref."%'";
if ($refclient) $sql.= " AND ".$ref_client." LIKE '%".$refclient."%'";
if ($total) $sql.= " AND ".$columntotal." LIKE '%".$total."%'";
if ($recibido) $sql.= " AND ".$columnrecibido." LIKE '%".$recibido."%'";
if ($factstatus != '' && $factstatus >= 0) $sql.= " AND ".$columnstatus." = ".$db->escape($factstatus);

//ficheinter
if($fiche_description) $sql.= " AND ".$column_desc_fiche." LIKE '%".$fiche_description."%'";
if ($fichestatus != '' && $fichestatus >= 0) $sql.= " AND ".$culumn_status_fiche." = ".$fichestatus;

//if ($sprod_fulldescr) $sql.= " AND (d.description LIKE '%".$sprod_fulldescr."%' OR p.label LIKE '%".$sprod_fulldescr."%')";
if ($type_element == 'invoice' || $type_element == 'ticket') {
  $sql .=' GROUP BY f.rowid';
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);

// Define type of elements
$typeElementString = $form->selectarray("type_element", $elementTypeArray, GETPOST('type_element'), 2);
$button = '<input type="submit" class="button" name="button_third" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
$param="&amp;sref=".$sref."&amp;refclient=".$refclient."&amp;month=".$month."&amp;year=".$year."&amp;month2=".$month2."&amp;year2=".$year2."&amp;total=".$total."&amp;recibido=".$recibido."&amp;fiche_description=".$fiche_description."&amp;fiche_duration=".$fiche_duration."&amp;sprod_fulldescr=".$sprod_fulldescr."&amp;socid=".$socid."&amp;type_element=".$type_element."&amp;fichestatus=".urldecode($fichestatus);

print_barre_liste($langs->trans('Vinculación').' '.$typeElementString.' '.$button, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, '', '');

if ($sql_select)
{
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);
}

if ($type_element == 'invoice') {
  print '<table class="liste" width="100%">'."\n";
  // Titles with sort buttons
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'doc_number','',$param,'align="left"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans('Ref Cliente'),$_SERVER['PHP_SELF'],'ref_client','',$param,'align="left"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans('Fecha de Creacion'),$_SERVER['PHP_SELF'],'dateprint','',$param,'align="center" width="150"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans('Fecha de Vencimiento'),$_SERVER['PHP_SELF'],'lim_date','',$param,'align="center" width="150"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans('Importe Total'),$_SERVER['PHP_SELF'],'imp_total','',$param,'align="center" width="150"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans('Recibido'),$_SERVER['PHP_SELF'],'recibido','',$param,'align="center" width="150"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'status','',$param,'align="center"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans(''),$_SERVER['PHP_SELF'],'','',$param,'align="center"',$sortfield,$sortorder);
  print "</tr>\n";
  // Filters
  print '<tr class="liste_titre">';
  print '<td class="liste_titre" align="left">';
  print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
  print '</td>';
  //column ref client
  print '<td class="liste_titre" align="left">';
  print '<input class="flat" type="text" name="refclient" size="8" value="'.$refclient.'">';
  print '</td>';
  // date
  print '<td class="liste_titre nowrap">';
  print $formother->select_month($month?$month:-1,'month',1);
  $formother->select_year($year?$year:-1,'year',1, 20, 1);
  // column date limit
  print '<td class="liste_titre nowrap">';
  print $formother->select_month($month2?$month2:-1,'month2',1);
  $formother->select_year($year2?$year2:-1,'year2',1, 20, 1);
  // column total
  print '<td class="liste_titre" align="center">';
  print '<input class="flat" type="text" name="total" size="8" value="'.$total.'">';
  print '</td>';
  // column recibido
  print '<td class="liste_titre" align="center">';
  print '<input class="flat" type="text" name="recibido" size="8" value="'.$recibido.'">';
  print '</td>';
  // column status
  print '<td class="liste_titre" align="center">';
  $liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
  print $form->selectarray('factstatus', $liststatus, $factstatus, 1);
  print '</td>';
  print '<td class="liste_titre" align="right">';
  print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
  print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("resetFilters")).'" title="'.dol_escape_htmltag($langs->trans("resetFilters")).'">';
  print '</td>';
  print '</tr>';
}
else if($type_element == 'ticket')
{
    print '<table class="liste" width="100%">'."\n";
    // Titles with sort buttons
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'doc_number','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Ref Cliente'),$_SERVER['PHP_SELF'],'ref_client','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Fecha de Creacion'),$_SERVER['PHP_SELF'],'dateprint','',$param,'align="center" width="150"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Importe Total'),$_SERVER['PHP_SELF'],'imp_total','',$param,'align="center" width="150"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Recibido'),$_SERVER['PHP_SELF'],'recibido','',$param,'align="center" width="150"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'status','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans(''),$_SERVER['PHP_SELF'],'','',$param,'align="center"',$sortfield,$sortorder);
    print "</tr>\n";
    // Filters
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
    print '</td>';
    //column ref client
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="refclient" size="8" value="'.$refclient.'">';
    print '</td>';
    // date
    print '<td class="liste_titre nowrap">';
    print $formother->select_month($month?$month:-1,'month',1);
    $formother->select_year($year?$year:-1,'year',1, 20, 1);
    // column total
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" type="text" name="total" size="8" value="'.$total.'">';
    print '</td>';
    // column recibido
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" type="text" name="recibido" size="8" value="'.$recibido.'">';
    print '</td>';
    // column status
    print '<td class="liste_titre" align="center">';
    $liststatus=array('1'=>'Cerrado', '2'=>'Procesado');
    print $form->selectarray('factstatus', $liststatus, $factstatus, 1);
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("resetFilters")).'" title="'.dol_escape_htmltag($langs->trans("resetFilters")).'">';
    print '</td>';
    print '</tr>';
}
if ($sql_select)
{
	$var=true;
	$num = $db->num_rows($resql);
	$i = 0;
	while (($objp = $db->fetch_object($resql)) && $i < $conf->liste_limit )
	{
		$documentstatic->id=$objp->doc_id;
		$documentstatic->ref=$objp->doc_number;
        $documentstatic->client=$objp->ref_client;
		$documentstatic->type=$objp->doc_type;
        $documentstatic->type=$objp->imp_total;
        $documentstatic->type=$objp->recibido;
		$documentstatic->fk_statut=$objp->status;
		$documentstatic->fk_status=$objp->status;
		$documentstatic->statut=$objp->status;
		$documentstatic->status=$objp->status;
		$documentstatic->paye=$objp->paid;
		if (is_object($documentstaticline)) $documentstaticline->statut=$objp->status;
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td class="nobordernopadding nowrap" width="100">'.$objp->doc_number;
        if($type_element == 'invoice'){
            $filename=dol_sanitizeFileName($objp->doc_number);
            $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->doc_number);
            $urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->doc_id;
            print $formfile->getDocumentsLink($element, $filename, $filedir);
            print '</td>';
            print '<td align="center" width="80">'.$objp->ref_client.'</td>';
            print '<td align="center" width="80">'.dol_print_date($db->jdate($objp->dateprint),'day').'</td>';
            print '<td align="center" width="80">'.dol_print_date($db->jdate($objp->lim_date),'day').'</td>';
            print '<td align="center" width="80">'.price($objp->imp_total).'</td>';
            print '<td align="center" width="80">'.price($objp->recibido).'</td>';
            print '<td align="center">';
            print $documentstatic->getLibStatut(2);
            print '</td>';
            print '<td align="center" width="80">&nbsp;</td>';
        }
        if($type_element == 'ticket'){
            $filename=dol_sanitizeFileName($objp->doc_number);
            $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->doc_number);
            $urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->doc_id;
            print $formfile->getDocumentsLink($element, $filename, $filedir);
            print '</td>';
            print '<td align="center" width="80">'.$objp->ref_client.'</td>';
            print '<td align="center" width="80">'.dol_print_date($db->jdate($objp->dateprint),'day').'</td>';
            print '<td align="center" width="80">'.price($objp->imp_total).'</td>';
            print '<td align="center" width="80">'.price($objp->recibido).'</td>';
            print '<td align="center">';
            print $documentstatic->LibStatut($objp->status,1);
            print '</td>';
            print '<td align="center" width="80">&nbsp;</td>';
        }
        print '</tr>';
		$i++;
	}
	if ($num > $conf->liste_limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);
	}
	$db->free($resql);
}
else if (empty($type_element) || $type_element == -1)
{
	print '<tr '.$bc[0].'><td colspan="5">'.$langs->trans("SelectElementAndClickRefresh").'</td></tr>';
}
else {
	print '<tr '.$bc[0].'><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
}

print "</table>";
print "</form>";

llxFooter();

$db->close();
