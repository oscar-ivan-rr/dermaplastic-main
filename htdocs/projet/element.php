<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2016 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015-2019 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2016      Josep Lluís Amador   <joseplluis@lliuretic.cat>
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
 *      \file       htdocs/projet/element.php
 *      \ingroup    projet
 *		\brief      Page of project referrers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (! empty($conf->propal->enabled))		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled))		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->commande->enabled))		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->supplier_proposal->enabled)) require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
if (! empty($conf->fournisseur->enabled))	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->fournisseur->enabled))	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->contrat->enabled))		require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->ficheinter->enabled))	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->expedition->enabled))	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
if (! empty($conf->deplacement->enabled))	require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if (! empty($conf->expensereport->enabled))	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
if (! empty($conf->agenda->enabled))		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
if (! empty($conf->don->enabled))			require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
if (! empty($conf->loan->enabled))			require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
if (! empty($conf->loan->enabled))			require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
if (! empty($conf->stock->enabled))			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
if (! empty($conf->tax->enabled))			require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
if (! empty($conf->banque->enabled))		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
if (! empty($conf->salaries->enabled))		require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
if (! empty($conf->categorie->enabled))		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->pos->enabled))		require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'suppliers', 'compta'));
if (! empty($conf->facture->enabled))		$langs->load("bills");
if (! empty($conf->commande->enabled))		$langs->load("orders");
if (! empty($conf->propal->enabled))		$langs->load("propal");
if (! empty($conf->ficheinter->enabled))	$langs->load("interventions");
if (! empty($conf->deplacement->enabled))	$langs->load("trips");
if (! empty($conf->expensereport->enabled)) $langs->load("trips");
if (! empty($conf->don->enabled))			$langs->load("donations");
if (! empty($conf->loan->enabled))			$langs->load("loan");
if (! empty($conf->salaries->enabled))		$langs->load("salaries");

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$datesrfc=GETPOST('datesrfc');
$dateerfc=GETPOST('dateerfc');
$dates=dol_mktime(0, 0, 0, GETPOST('datesmonth'), GETPOST('datesday'), GETPOST('datesyear'));
$datee=dol_mktime(23, 59, 59, GETPOST('dateemonth'), GETPOST('dateeday'), GETPOST('dateeyear'));
if (empty($dates) && ! empty($datesrfc)) $dates=dol_stringtotime($datesrfc);
if (empty($datee) && ! empty($dateerfc)) $datee=dol_stringtotime($dateerfc);
if (! isset($_POST['datesrfc']) && ! isset($_POST['datesday']) && ! empty($conf->global->PROJECT_LINKED_ELEMENT_DEFAULT_FILTER_YEAR))
{
	$new=dol_now();
	$tmp=dol_getdate($new);
	//$datee=$now
	//$dates=dol_time_plus_duree($datee, -1, 'y');
	$dates=dol_get_first_day($tmp['year'], 1);
}
if ($id == '' && $ref == '')
{
	setEventMessage($langs->trans('ErrorBadParameters'), 'errors');
	header('Location: list.php');
	exit();
}

if(GETPOST('action') == "dev_done"){
	setEventMessages($langs->trans("dev_done"), null);
}

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once
if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();

// Security check
$socid=$object->socid;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet',  $object->id, 'projet&project');

$hookmanager->initHooks(array('projectOverview'));

print '
<script>
	var array_ids = [];
	//var array_ids_hijos = [];
	var array_ids_shipments = [];
</script>
';
/*
 *	View
 */

$title = $langs->trans("ProjectReferers").' - '.$object->ref.' '.$object->name;
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->ref.' '.$object->name.' - '.$langs->trans("ProjectReferers");
$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("", $langs->trans("Referers"), $help_url);

$form = new Form($db);
$formproject = new FormProjets($db);
$formfile = new FormFile($db);

$userstatic = new User($db);

// To verify role of users
$userAccess = $object->restrictedProjectArea($user);

$head = project_prepare_head($object);
dol_fiche_head($head, 'element', $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'));


// Project card

$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Title
$morehtmlref .= $object->title;
// Thirdparty
if ($object->thirdparty->id > 0)
{
    $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'project');
}
$morehtmlref .= '</div>';

// Define a complementary filter for search of next/prev ref.
if (!$user->rights->projet->all->lire)
{
    $objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
    $object->next_prev_filter = " te.rowid in (".(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
}

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Usage
// print '<tr><td class="tdtop">';
// print $langs->trans("Usage");
// print '</td>';
// print '<td>';
// if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
// {
// 	print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha')!=''?' checked="checked"':'') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
// 	$htmltext = $langs->trans("ProjectFollowOpportunity");
// 	print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
// 	print '<br>';
// }
// if (empty($conf->global->PROJECT_HIDE_TASKS))
// {
// 	print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha')!=''?' checked="checked"':'') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
// 	$htmltext = $langs->trans("ProjectFollowTasks");
// 	print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
// 	print '<br>';
// }
// if (! empty($conf->global->PROJECT_BILL_TIME_SPENT))
// {
// 	print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha')!=''?' checked="checked"':'') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
// 	$htmltext = $langs->trans("ProjectBillTimeDescription");
// 	print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
// 	print '<br>';
// }
// print '</td></tr>';

// Visibility
print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
if ($object->public) print $langs->trans('SharedProject');
else print $langs->trans('PrivateProject');
print '</td></tr>';

if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES))
{
    // Opportunity status
    print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
    $code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
    if ($code) print $langs->trans("OppStatus".$code);
    print '</td></tr>';

    // Opportunity percent
    print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
    if (strcmp($object->opp_percent, '')) print price($object->opp_percent, '', $langs, 1, 0).' %';
    print '</td></tr>';

    // Opportunity Amount
    print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
    if (strcmp($object->opp_amount, '')) print price($object->opp_amount, '', $langs, 1, 0, 0, $conf->currency);
    print '</td></tr>';
}

// Date start - end
print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
$start = dol_print_date($object->date_start, 'day');
print ($start ? $start : '?');
$end = dol_print_date($object->date_end, 'day');
print ' - ';
print ($end ? $end : '?');
if ($object->hasDelay()) print img_warning("Late");
print '</td></tr>';

// Budget
print '<tr><td>'.$langs->trans("Budget").'</td><td>';
if (strcmp($object->budget_amount, '')) print price($object->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
print '</td></tr>';

// Other attributes
$cols = 2;
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

print '</table>';

print '</div>';
print '<div class="fichehalfright">';
print '<div class="ficheaddleft">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield" width="100%">';

// Description
print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
print nl2br($object->description);
print '</td></tr>';

// Categories
if ($conf->categorie->enabled) {
    print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
    print $form->showCategories($object->id, 'project', 1);
    print "</td></tr>";
}

print '</table>';

print '</div>';
print '</div>';
print '</div>';

print '<div class="clearboth"></div>';

dol_fiche_end();

print '<br>';

/*
 * Referers types
 */

$listofreferent = array(
'propal'=>array(
	'name'=>"Proposals",
	'title'=>"ListProposalsAssociatedProject",
	'class'=>'Propal',
	'table'=>'propal',
    'datefieldname'=>'datep',
    'urlnew'=>DOL_URL_ROOT.'/comm/propal/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid,
    'lang'=>'propal',
    'buttonnew'=>'AddProp',
    'testnew'=>$user->rights->propal->creer,
	'test'=>$conf->propal->enabled && $user->rights->propale->lire),
'order'=>array(
	'name'=>"CustomersOrders",
	'title'=>"ListOrdersAssociatedProject",
	'class'=>'Commande',
	'table'=>'commande',
	'datefieldname'=>'date_commande',
    'urlnew'=>DOL_URL_ROOT.'/commande/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'orders',
    'buttonnew'=>'CreateOrder',
    'testnew'=>$user->rights->commande->creer,
    'test'=>$conf->commande->enabled && $user->rights->commande->lire),
'invoice'=>array(
	'name'=>"CustomersInvoices",
	'title'=>"ListInvoicesAssociatedProject",
	'class'=>'Facture',
	'margin'=>'add',
	'table'=>'facture',
	'datefieldname'=>'datef',
    'urlnew'=>DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'bills',
    'buttonnew'=>'CreateBill',
    'testnew'=>$user->rights->facture->creer,
    'test'=>$conf->facture->enabled && $user->rights->facture->lire),
/*'invoice_predefined'=>array(
	'name'=>"PredefinedInvoices",
	'title'=>"ListPredefinedInvoicesAssociatedProject",
	'class'=>'FactureRec',
	'table'=>'facture_rec',
	'datefieldname'=>'datec',
    'urlnew'=>DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'bills',
    'buttonnew'=>'CreateBill',
    'testnew'=>$user->rights->facture->creer,
    'test'=>$conf->facture->enabled && $user->rights->facture->lire),*/
'proposal_supplier'=>array(
	'name'=>"SuppliersProposals",
	'title'=>"ListSupplierProposalsAssociatedProject",
	'class'=>'SupplierProposal',
	'table'=>'supplier_proposal',
	'datefieldname'=>'date_valid',
	'urlnew'=>DOL_URL_ROOT.'/supplier_proposal/card.php?action=create&projectid='.$id, // No socid parameter here, the socid is often the customer and we create a supplier object
    'lang'=>'supplier_proposal',
    'buttonnew'=>'AddSupplierProposal',
    'testnew'=>$user->rights->supplier_proposal->creer,
    'test'=>$conf->supplier_proposal->enabled && $user->rights->supplier_proposal->lire),
'order_supplier'=>array(
	'name'=>"SuppliersOrders",
	'title'=>"ListSupplierOrdersAssociatedProject",
	'class'=>'CommandeFournisseur',
	'table'=>'commande_fournisseur',
	'datefieldname'=>'date_commande',
    'urlnew'=>DOL_URL_ROOT.'/fourn/commande/card.php?action=create&projectid='.$id, // No socid parameter here, the socid is often the customer and we create a supplier object
    'lang'=>'suppliers',
    'buttonnew'=>'AddSupplierOrder',
    'testnew'=>$user->rights->fournisseur->commande->creer,
    'test'=>$conf->supplier_order->enabled && $user->rights->fournisseur->commande->lire),
'invoice_supplier'=>array(
	'name'=>"BillsSuppliers",
	'title'=>"ListSupplierInvoicesAssociatedProject",
	'class'=>'FactureFournisseur',
	'margin'=>'minus',
	'table'=>'facture_fourn',
	'datefieldname'=>'datef',
	'urlnew'=>DOL_URL_ROOT.'/fourn/facture/card.php?action=create&projectid='.$id, // No socid parameter here, the socid is often the customer and we create a supplier object
    'lang'=>'suppliers',
    'buttonnew'=>'AddSupplierInvoice',
    'testnew'=>$user->rights->fournisseur->facture->creer,
    'test'=>$conf->supplier_invoice->enabled && $user->rights->fournisseur->facture->lire),
'contract'=>array(
	'name'=>"Contracts",
	'title'=>"ListContractAssociatedProject",
	'class'=>'Contrat',
	'table'=>'contrat',
	'datefieldname'=>'date_contrat',
    'urlnew'=>DOL_URL_ROOT.'/contrat/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'contracts',
    'buttonnew'=>'AddContract',
    'testnew'=>$user->rights->contrat->creer,
    'test'=>$conf->contrat->enabled && $user->rights->contrat->lire),
'intervention'=>array(
	'name'=>"Interventions",
	'title'=>"ListFichinterAssociatedProject",
	'class'=>'Fichinter',
	'table'=>'fichinter',
	'datefieldname'=>'date_valid',
	'disableamount'=>0,
	'margin'=>'minus',
    'urlnew'=>DOL_URL_ROOT.'/fichinter/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid,
    'lang'=>'interventions',
    'buttonnew'=>'AddIntervention',
    'testnew'=>$user->rights->ficheinter->creer,
    'test'=>$conf->ficheinter->enabled && $user->rights->ficheinter->lire),
'shipping'=>array(
    'name'=>"Shipments",
	'title'=>"ListShippingAssociatedProject",
	'class'=>'Expedition',
	'table'=>'expedition',
	'datefieldname'=>'date_valid',
    'margin'=>'add',
	'urlnew'=>DOL_URL_ROOT.'/expedition/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid,
	'lang'=>'sendings',
	'buttonnew'=>'CreateShipment',
	'testnew'=>0,
	'test'=>$conf->expedition->enabled && $user->rights->expedition->lire),
'trip'=>array(
	'name'=>"TripsAndExpenses",
	'title'=>"ListExpenseReportsAssociatedProject",
	'class'=>'Deplacement',
	'table'=>'deplacement',
	'datefieldname'=>'dated',
	'margin'=>'minus',
	'disableamount'=>1,
    'urlnew'=>DOL_URL_ROOT.'/deplacement/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'trips',
    'buttonnew'=>'AddTrip',
    'testnew'=>$user->rights->deplacement->creer,
    'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire),
'expensereport'=>array(
	'name'=>"ExpenseReports",
	'title'=>"ListExpenseReportsAssociatedProject",
	'class'=>'ExpenseReportLine',
	'table'=>'expensereport_det',
	'datefieldname'=>'date',
	'margin'=>'minus',
	'disableamount'=>0,
    'urlnew'=>DOL_URL_ROOT.'/expensereport/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'trips',
    'buttonnew'=>'AddTrip',
    'testnew'=>$user->rights->expensereport->creer,
    'test'=>$conf->expensereport->enabled && $user->rights->expensereport->lire),
'donation'=>array(
	'name'=>"Donation",
	'title'=>"ListDonationsAssociatedProject",
	'class'=>'Don',
	'margin'=>'add',
	'table'=>'don',
	'datefieldname'=>'datedon',
	'disableamount'=>0,
    'urlnew'=>DOL_URL_ROOT.'/don/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'donations',
    'buttonnew'=>'AddDonation',
    'testnew'=>$user->rights->don->creer,
    'test'=>$conf->don->enabled && $user->rights->don->lire),
'loan'=>array(
	'name'=>"Loan",
	'title'=>"ListLoanAssociatedProject",
	'class'=>'Loan',
	'margin'=>'add',
	'table'=>'loan',
	'datefieldname'=>'datestart',
	'disableamount'=>0,
    'urlnew'=>DOL_URL_ROOT.'/loan/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'loan',
    'buttonnew'=>'AddLoan',
    'testnew'=>$user->rights->loan->write,
    'test'=>$conf->loan->enabled && $user->rights->loan->read),
/*'chargesociales'=>array(
    'name'=>"SocialContribution",
    'title'=>"ListSocialContributionAssociatedProject",
    'class'=>'ChargeSociales',
    'margin'=>'minus',
    'table'=>'chargesociales',
    'datefieldname'=>'date_ech',
    'disableamount'=>0,
    'urlnew'=>DOL_URL_ROOT.'/compta/sociales/card.php?action=create&projectid='.$id,
    'lang'=>'compta',
    'buttonnew'=>'AddSocialContribution',
    'testnew'=>$user->rights->tax->charges->lire,
    'test'=>$conf->tax->enabled && $user->rights->tax->charges->lire),*/
'project_task'=>array(
	'name'=>"TaskTimeSpent",
	'title'=>"ListTaskTimeUserProject",
	'class'=>'Task',
	'margin'=>'minus',
	'table'=>'projet_task',
	'datefieldname'=>'task_date',
	'disableamount'=>0,
    'urlnew'=>DOL_URL_ROOT.'/projet/tasks/time.php?withproject=1&action=createtime&projectid='.$id,
    'buttonnew'=>'AddTimeSpent',
    'testnew'=>$user->rights->projet->creer,
    'test'=>($conf->projet->enabled && $user->rights->projet->lire && empty($conf->global->PROJECT_HIDE_TASKS))),
'stock_mouvement'=>array(
	'name'=>"MouvementStockAssociated",
	'title'=>"ListMouvementStockProject",
	'class'=>'MouvementStock',
	'margin'=>'minus',
	'table'=>'stock_mouvement',
	'datefieldname'=>'datem',
	'disableamount'=>0,
	'test'=>($conf->stock->enabled && $user->rights->stock->mouvement->lire && !empty($conf->global->STOCK_MOVEMENT_INTO_PROJECT_OVERVIEW))),
'salaries'=>array(
	'name'=>"Salaries",
	'title'=>"ListSalariesAssociatedProject",
	'class'=>'PaymentSalary',
	'table'=>'payment_salary',
	'datefieldname'=>'datev',
	'margin'=>'minus',
	'disableamount'=>0,
	'urlnew'=>DOL_URL_ROOT.'/salaries/card.php?action=create&projectid='.$id,
	'lang'=>'salaries',
	'buttonnew'=>'AddSalaryPayment',
	'testnew'=>$user->rights->salaries->write,
	'test'=>$conf->salaries->enabled && $user->rights->salaries->read),
'variouspayment'=>array(
	'name'=>"VariousPayments",
	'title'=>"ListVariousPaymentsAssociatedProject",
	'class'=>'PaymentVarious',
	'table'=>'payment_various',
	'datefieldname'=>'datev',
	'margin'=>'minus',
	'disableamount'=>0,
    'urlnew'=>DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&projectid='.$id,
    'lang'=>'banks',
    'buttonnew'=>'AddVariousPayment',
    'testnew'=>$user->rights->banque->modifier,
    'test'=>$conf->banque->enabled && $user->rights->banque->lire && empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT)),
/* No need for this, available on dedicated tab "Agenda/Events"
'agenda'=>array(
	'name'=>"Agenda",
	'title'=>"ListActionsAssociatedProject",
	'class'=>'ActionComm',
	'table'=>'actioncomm',
	'datefieldname'=>'datep',
	'disableamount'=>1,
    'urlnew'=>DOL_URL_ROOT.'/comm/action/card.php?action=create&projectid='.$id.'&socid='.$socid,
    'lang'=>'agenda',
    'buttonnew'=>'AddEvent',
    'testnew'=>$user->rights->agenda->myactions->create,
    'test'=>$conf->agenda->enabled && $user->rights->agenda->myactions->read),
*/
'tickets_projet'=>array(
        'name'=>"TicketDef",
        'title'=>"Lista de Ventas POS",
        'class'=>'Ticket',
        'table'=>'pos_ticket',
        'datefieldname'=>'date_creation',
        //'urlnew'=>DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid,
        //'lang'=>'bills',
        //'buttonnew'=>'CreateBill',
        'testnew'=>$user->rights->pos->backend,
        'test'=>$conf->pos->enabled && $user->rights->pos->backend)
);

$parameters=array('listofreferent'=>$listofreferent);
$resHook = $hookmanager->executeHooks('completeListOfReferent', $parameters, $object, $action);

if(!empty($hookmanager->resArray)) {
	$listofreferent = array_merge($listofreferent, $hookmanager->resArray);
}
//Si se realiza la devolucion
if($action == "update_qty")
{
	foreach($_POST as $key => $value)
	{
		if(strcmp($key,'cant_devol_') !== false)
		{
			$id_linea=str_replace('cant_devol_','',$key);
		}
		if($key != 'action' && $value != 0)
		{
			//idlinea con value y key diferente de action para solo recibir los id a actualizar
			$devolucion = 0;
			$sqlQty = "SELECT devolucion, qty FROM ".MAIN_DB_PREFIX."expeditiondet WHERE rowid = ".$id_linea."";
			$resqlQty = $db->query($sqlQty);
			if ($resqlQty){
				$objDev = $db->fetch_object($resqlDev);
				$devolucion = $objDev->devolucion + $value;
			}
			$objQty = $db->fetch_object($resqlQty);
			$newQty = $objDev->qty - $value;
			if($newQty >= 0){
				date_default_timezone_set("America/Mexico_City");
				$now = dol_now();
				$sql = "UPDATE ".MAIN_DB_PREFIX."expeditiondet SET qty = ".$newQty.", devolucion = ".$devolucion.", date_devolucion = '".$db->idate($now)."' WHERE rowid = ".$id_linea."";
				$resultDevol = $db->query($sql);
				if (! $resultDevol){
					setEventMessages($db->error, $db->errors, 'errors');
				}
				else
				{
					setEventMessages("Devolución realizada correctamente", null, 'mesgs');
				}
			}else{
				setEventMessages("La cantidad devuelta excede la cantidad enviada", null, 'errors');
			}
    		
		}
	}
}

if($action == "error_vacios")
{
	setEventMessages("Fecha no valida para generar PDF de devoluciones", null, 'errors');
}

if ($action=="addelement")
{
	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");
	$result=$object->update_element($tablename, $elementselectid);
	if ($result<0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}
elseif ($action == "unlink")
{
	$tablename = GETPOST("tablename", "aZ09");
    $projectField = GETPOSTISSET('projectfield') ? GETPOST('projectfield', 'aZ09') : 'fk_projet';
	$elementselectid = GETPOST("elementselect", "int");

	$result = $object->remove_element($tablename, $elementselectid, $projectField);
	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if($action == "qty_devol_error")
{
    setEventMessages("Cantidad a devolver excede cantidad enviada en devoluciones masivas", null, 'errors');
}


$elementuser = new User($db);



$showdatefilter = 0;
// Show the filter on date on top of element list
if (!$showdatefilter)
{
	print '<div class="center centpercent">';
    print '<form action="'.$_SERVER["PHP_SELF"].'?id=' . $object->id . '" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION["newtoken"].'">';
    print '<input type="hidden" name="tablename" value="'.$tablename.'">';
	print '<input type="hidden" name="action" value="view">';
	print '<table class="center"><tr>';
	print '<td>'.$langs->trans("From").' ';
	print $form->selectDate($dates, 'dates', 0, 0, 1, '', 1, 0);
	print '</td>';
	print '<td>'.$langs->trans("to").' ';
	print $form->selectDate($datee, 'datee', 0, 0, 1, '', 1, 0);
	print '</td>';
	print '<td>';
	print '<input type="submit" name="refresh" value="'.$langs->trans("Refresh").'" class="button">';
	print '</td>';
	print '</tr></table>';
	print '</form>';
	print '</div>';

	$showdatefilter++;
}



// Show balance for whole project

$langs->loadLangs(array("suppliers", "bills", "orders", "proposals", "margins","sendings"));

if (!empty($conf->stock->enabled)) $langs->load('stocks');

print load_fiche_titre($langs->trans("Profit"), '', 'title_accountancy');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="left" width="200">'.$langs->trans("Element").'</td>';
print '<td class="right" width="100">'.$langs->trans("Number").'</td>';
print '<td class="right" width="100">'.$langs->trans("AmountHT").'</td>';
print '<td class="right" width="100">'.$langs->trans("AmountTTC").'</td>';
print '</tr>';

foreach ($listofreferent as $key => $value)
{
	$name=$langs->trans($value['name']);
	$title=$value['title'];
	$classname=$value['class'];
	$tablename=$value['table'];
	$datefieldname=$value['datefieldname'];
	$qualified=$value['test'];
	$margin = $value['margin'];
	$project_field = $value['project_field'];
	if ($qualified && isset($margin))		// If this element must be included into profit calculation ($margin is 'minus' or 'plus')
	{
		$element = new $classname($db);

		$elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee, !empty($project_field)?$project_field:'fk_projet');

		if (is_array($elementarray) && count($elementarray) > 0)
		{
			$total_ht = 0;
			$total_ttc = 0;

			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$tmp=explode('_', $elementarray[$i]);
				$idofelement=$tmp[0];
				$idofelementuser=$tmp[1];

				$element->fetch($idofelement);
				if ($idofelementuser) $elementuser->fetch($idofelementuser);

				// Define if record must be used for total or not
				$qualifiedfortotal=true;
				if ($key == 'invoice')
				{
				    if (! empty($element->close_code) && $element->close_code == 'replaced') $qualifiedfortotal=false;	// Replacement invoice, do not include into total
				    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS) && $element->type == Facture::TYPE_DEPOSIT) $qualifiedfortotal=false;	// If hidden option to use deposits as payment (deprecated, not recommended to use this), deposits are not included
				}
				if ($key == 'propal')
				{
				    if ($element->statut == Propal::STATUS_NOTSIGNED) $qualifiedfortotal=false;	// Refused proposal must not be included in total
				}

				if ($tablename != 'expensereport_det' && method_exists($element, 'fetch_thirdparty')) $element->fetch_thirdparty();

				// Define $total_ht_by_line
				if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'payment_salary') $total_ht_by_line=$element->amount;
				elseif ($tablename == 'fichinter') $total_ht_by_line=$element->getAmount();
				elseif ($tablename == 'stock_mouvement') $total_ht_by_line=$element->price*abs($element->qty);
				elseif ($tablename == 'projet_task')
				{
					if ($idofelementuser)
					{
						$tmp = $element->getSumOfAmount($elementuser, $dates, $datee);
						$total_ht_by_line = price2num($tmp['amount'], 'MT');
					}
					else
					{
						$tmp = $element->getSumOfAmount('', $dates, $datee);
						$total_ht_by_line = price2num($tmp['amount'], 'MT');
					}
				}
                elseif ($key == 'loan'){
                    if((empty($dates) && empty($datee)) || (intval($dates) <= $element->datestart && intval($datee) >= $element->dateend)){
                        // Get total loan
                        $total_ht_by_line = -$element->capital;
                    }
				    else{
                        // Get loan schedule according to date filter
                        $total_ht_by_line = 0;
                        $loanScheduleStatic = new LoanSchedule($element->db);
                        $loanScheduleStatic->fetchAll($element->id);
                        if(!empty($loanScheduleStatic->lines)){
                            foreach($loanScheduleStatic->lines as $loanSchedule){
                                /**
                                 * @var $loanSchedule LoanSchedule
                                 */
                                if( ($loanSchedule->datep >= $dates && $loanSchedule->datep <= $datee) // dates filter is defined
                                    || !empty($dates) && empty($datee) && $loanSchedule->datep >= $dates && $loanSchedule->datep <= dol_now()
                                    || empty($dates) && !empty($datee) && $loanSchedule->datep <= $datee
                                ){
                                    $total_ht_by_line = -$loanSchedule->amount_capital;
                                }
                            }
                        }
                    }
                }
				else $total_ht_by_line = $element->total_ht;

				// Define $total_ttc_by_line
				if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'payment_salary') $total_ttc_by_line = $element->amount;
				elseif ($tablename == 'fichinter') $total_ttc_by_line = $element->getAmount();
				elseif ($tablename == 'stock_mouvement') $total_ttc_by_line = $element->price * abs($element->qty);
				elseif ($tablename == 'projet_task')
				{
					$defaultvat = get_default_tva($mysoc, $mysoc);
					$total_ttc_by_line = price2num($total_ht_by_line * (1 + ($defaultvat / 100)), 'MT');
				}
                elseif ($key == 'loan'){
                        $total_ttc_by_line = $total_ht_by_line; // For loan there is actually no taxe managed in Dolibarr
                }
				else $total_ttc_by_line = $element->total_ttc;

				// Change sign of $total_ht_by_line and $total_ttc_by_line for some cases
				if ($tablename == 'payment_various')
				{
			        if ($element->sens == 1)
			        {
			            $total_ht_by_line = -$total_ht_by_line;
			            $total_ttc_by_line = -$total_ttc_by_line;
			        }
				}

				// Add total if we have to
				if ($qualifiedfortotal)
				{
				    $total_ht = $total_ht + $total_ht_by_line;
				    $total_ttc = $total_ttc + $total_ttc_by_line;
				}
			}

			// Each element with at least one line is output
			$qualifiedforfinalprofit = true;
			if ($key == 'intervention' && empty($conf->global->PROJECT_INCLUDE_INTERVENTION_AMOUNT_IN_PROFIT)) $qualifiedforfinalprofit = false;
			//var_dump($key);

			// Calculate margin
			if ($qualifiedforfinalprofit)
			{
			    if ($margin != "add")
				{
					$total_ht = -$total_ht;
					$total_ttc = -$total_ttc;
				}

				$balance_ht += $total_ht;
				$balance_ttc += $total_ttc;
			}

			print '<tr class="oddeven">';
			// Module
			print '<td class="left">'.$name.'</td>';
			// Nb
			print '<td class="right">'.$i.'</td>';
			// Amount HT
			print '<td class="right">';
			if (!$qualifiedforfinalprofit) print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("NA"), $langs->trans("AmountOfInteventionNotIncludedByDefault")).'</span>';
			else print price($total_ht);
			print '</td>';
			// Amount TTC
			print '<td class="right">';
			if (!$qualifiedforfinalprofit) print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("NA"), $langs->trans("AmountOfInteventionNotIncludedByDefault")).'</span>';
			else print price($total_ttc);
			print '</td>';
			print '</tr>';
		}
	}
}
// and the final balance
print '<tr class="liste_total">';
print '<td class="right" colspan=2 >'.$langs->trans("Profit").'</td>';
print '<td class="right" >'.price(price2num($balance_ht, 'MT')).'</td>';
print '<td class="right" >'.price(price2num($balance_ttc, 'MT')).'</td>';
print '</tr>';

print "</table>";


print '<br><br>';
print '<br>';



// Detail
foreach ($listofreferent as $key => $value)
{
	$title = $value['title'];
	$classname = $value['class'];
	$tablename = $value['table'];
	$datefieldname = $value['datefieldname'];
	$qualified = $value['test'];
	$langtoload = $value['lang'];
	$urlnew = $value['urlnew'];
	$buttonnew = $value['buttonnew'];
  $testnew = $value['testnew'];
	$project_field = $value['project_field'];

	$exclude_select_element = array('payment_various');
	if (!empty($value['exclude_select_element'])) $exclude_select_element[] = $value['exclude_select_element'];

	if ($qualified)
	{
		// If we want the project task array to have details of users
		//if ($key == 'project_task') $key = 'project_task_time';

	    if ($langtoload) $langs->load($langtoload);

		$element = new $classname($db);

		$addform='';

		$idtofilterthirdparty=0;
		$array_of_element_linkable_with_different_thirdparty = array('facture_fourn', 'commande_fournisseur');
		if (! in_array($tablename, $array_of_element_linkable_with_different_thirdparty))
		{
		    $idtofilterthirdparty=$object->thirdparty->id;
		    if (! empty($conf->global->PROJECT_OTHER_THIRDPARTY_ID_TO_ADD_ELEMENTS)) $idtofilterthirdparty.=','.$conf->global->PROJECT_OTHER_THIRDPARTY_ID_TO_ADD_ELEMENTS;
		}

       	if (empty($conf->global->PROJECT_LINK_ON_OVERWIEW_DISABLED) && $idtofilterthirdparty && !in_array($tablename, $exclude_select_element))
       	{
			$selectList=$formproject->select_element($tablename, $idtofilterthirdparty, 'minwidth300', -2, !empty($project_field)?$project_field:'fk_projet');
			if ($selectList<0)
			{
				setEventMessages($formproject->error, $formproject->errors, 'errors');
			}
			elseif($selectList)
			{
				// Define form with the combo list of elements to link
			    $addform.='<div class="inline-block valignmiddle">';
			    $addform.='<form action="'.$_SERVER["PHP_SELF"].'?id=' . $object->id . '" method="post">';
			    $addform.='<input type="hidden" name="token" value="'.$_SESSION["newtoken"].'">';
			    $addform.='<input type="hidden" name="tablename" value="'.$tablename.'">';
				$addform.='<input type="hidden" name="action" value="addelement">';
				$addform.='<input type="hidden" name="datesrfc" value="'.dol_print_date($dates, 'dayhourrfc').'">';
				$addform.='<input type="hidden" name="dateerfc" value="'.dol_print_date($datee, 'dayhourrfc').'">';
				$addform.='<table><tr><td>'.$langs->trans("SelectElement").'</td></tr>';
				$addform.='<tr><td>'.$selectList.'</td></tr>';
				$addform.='<tr><td><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("AddElement")).'"></td>';
				$addform.='</tr></table>';
				$addform.='</form>';
				$addform.='</div>';
			}
		}
		if (empty($conf->global->PROJECT_CREATE_ON_OVERVIEW_DISABLED) && $urlnew)
		{
			$addform.='<div class="inline-block valignmiddle">';
			if ($testnew) $addform.='<a class="buttonxxx" href="'.$urlnew.'"><span class="valignmiddle text-plus-circle">'.($buttonnew?$langs->trans($buttonnew):$langs->trans("Create")).'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
			elseif (empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)) {
				$addform.='<a class="buttonxxx buttonRefused" disabled="disabled" href="#"><span class="valignmiddle text-plus-circle">'.($buttonnew?$langs->trans($buttonnew):$langs->trans("Create")).'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
			}
            $addform.='<div>';
		}

		if($key == 'shipping'){
			print load_fiche_titre($langs->trans($title), $addform, '',0,'buscar');
		}else{
			print load_fiche_titre($langs->trans($title), $addform, '');
		}
		
		//Filtro de Productos para Envios
		if($key == 'shipping'){
			
			$count_shipping = 0;

			//Búsqueda de Todos los Productos
			// $sql = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."product ORDER BY rowid DESC";
			$sql .= 'SELECT p.rowid, p.ref, p.label FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'expeditiondet as ed, ';
			$sql .= MAIN_DB_PREFIX.'expedition as e, '.MAIN_DB_PREFIX.'commandedet as c ';
			$sql .= 'WHERE p.rowid = c.fk_product ';
			$sql .= 'AND c.rowid = ed.fk_origin_line ';
			$sql .= 'AND ed.fk_expedition = e.rowid ';
			$sql .= 'AND e.fk_projet = '.$object->id;
			$products_ref = array();
			$products_label = array();
			if($res = $db->query($sql)){
				while($obj = $db->fetch_object($res)){
					$products_ref[$obj->rowid] = $obj->ref;
					$products_label[$obj->rowid] = substr($obj->label,0,20);
				}
			}

			print '<form action="'.$_SERVER["PHP_SELF"].'?id=' . $object->id . '#buscar" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION["newtoken"].'">';
			print '<input type="hidden" name="tablename" value="'.$tablename.'">';
			print '<input type="hidden" name="action" value="search_product_ship">';
			
			print '<span> Buscar Producto </span>';
			print '<div style="width: 100%; margin-top: 2%;">';
			print '<span class="left"> Ref: </span>';
			print $form->multiselectarray('product_shipping', $products_ref, GETPOST('product_shipping'), 0, 0, '', 0, '20%');
			print '<span class="left" style="margin-left:2%;"> Etiqueta: </span>';
			print $form->multiselectarray('product_shipping_label', $products_label, GETPOST('product_shipping_label'), 0, 0, '', 0, '20%');
			print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Search")).'"/>';


			print '</div>';

			print '</form>';
			print '<br>';

			print '<script>
			$( document ).ready(function() {
				$("#product_shipping").on("change",function(e){
					// console.log($("#product_shipping").find(":selected").text());
					if($("#product_shipping").find(":selected").text().length > 0)
					{
						$("#product_shipping_label").prop( "disabled", true );
					}
					else
					{
						$("#product_shipping_label").prop( "disabled", false );
					}
				});
				$("#product_shipping_label").on("change",function(e){
					// console.log($("#product_shipping_label").find(":selected").text());
					if($("#product_shipping_label").find(":selected").text().length > 0)
					{
						$("#product_shipping").prop( "disabled", true );
					}
					else
					{
						$("#product_shipping").prop( "disabled", false );
					}
				});
			}); 
			</script>';
		}
		print '
		<script>
			$("#search_product").on("click", function(event){
				localStorage.setItem("scrollpos", window.scrollY);
			}); 
			document.body.onload = function() {
				var scrollpos = localStorage.getItem("scrollpos");
            	if (scrollpos) window.scrollTo(0, scrollpos);
			  }			  
		</script>';

    if ($title == 'ListShippingAssociatedProject') {
      // print 'soy una lista de envios asociados a proyecto';
	  
      print '<div style="margin-bottom: 2%; display: flex; float: right; width: 30%;" class="right"><button id="generar_pdf" class="butAction">
        Generar PDF devoluciones
      </button></div>';
	  	print '<table style="float:right">';
		print '<tr>';
		print '<td>';
		print 'Desde'.$form->select_date($date_start_end,"filter_desde",0,0,0,"",1,0,1);
		print 'Hasta'.$form->select_date($date_end_end,"filter_hasta",0,0,0,"",1,0,1);
		print '</td>';
		print '</tr>';
		print '</table>';
		print '
		<script>
			
			$("#generar_pdf").on("click", function(event){
				console.log("SE DA CLICK EN GENERAR PDF");
				let desde = $("#filter_desde").val();
				let hasta = $("#filter_hasta").val();

				if((desde != "" && hasta != "") || (desde == "" && hasta == "")){
					let url = new URL("'.DOL_MAIN_URL_ROOT.'/projet/doc/pdf-enviodevoluciones.php?id=1&desde=0&hasta=0");
					url.searchParams.set("desde", encodeURIComponent(desde));
					url.searchParams.set("hasta", encodeURIComponent(hasta));
					location.href = url;
				}else{
					window.location.href = "'.DOL_URL_ROOT.'/projet/element.php?id='.$id.'&action=error_vacios";
				}
				
			});
		</script>
	  ';
    }

		if($key == 'shipping'){
			print '<table style = "display: flex; float: left; width: 70%;"> <tr> <td>';
			print '<div class="left"> <button class="button" style="background: red; color: white; border-radius: 25px; border-collapse: separate;"" id="btn_devolv_todos" name="btn_devolv_todos">'.$langs->trans("Generate_Reject").'</a> </div> </td>';
			print '<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>&nbsp</td>
			<td>
			<div id="aceptar_cancelar_todo" class="left" style = "display: none;">
			<button class="button" style="background: green; color: white; border-radius: 25px; border-collapse: separate;" id="btn_confirmar_todos" name="btn_confirmar_todos">'.$langs->trans("Confirm").'</a>
			<button class="button" style="background: red; color: white; border-radius: 25px; border-collapse: separate;" id="btn_cancelar_todos" name="btn_cancelar_todos">'.$langs->trans("Cancel").'</a>
			</div> </td> </tr> </table>';
		}

		print "\n".'<!-- Table for tablename = '.$tablename.' -->'."\n";
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		// Remove link column
		print '<td style="width: 24px"></td>';
		// Dropdown envios
		if($tablename == "expedition") print '<td> </td>';
		// Ref
		print '<td'.(($tablename != 'actioncomm' && $tablename != 'projet_task') ? ' style="width: 200px"' : '').'>'.$langs->trans("Ref").'</td>';
		if($key == 'shipping' && $tablename != "expedition"){
			//Toggle para Display de Productos
			print '<td>&nbsp;</td>';
		}
		// Date
		print '<td'.(($tablename != 'actioncomm' && $tablename != 'projet_task') ? ' style="width: 200px"' : '').' class="left">';
		if (in_array($tablename, array('projet_task'))) print $langs->trans("TimeSpent");
		if (!in_array($tablename, array('projet_task'))) print $langs->trans("Date");
		print '</td>';
		// Thirdparty or user
		print '<td class="left">';
		if (in_array($tablename, array('projet_task')) && $key == 'project_task') print ''; // if $key == 'project_task', we don't want details per user
		elseif (in_array($tablename, array('payment_various'))) print ''; // if $key == 'payment_various', we don't have any thirdparty
		elseif (in_array($tablename, array('expensereport_det', 'don', 'projet_task', 'stock_mouvement', 'payment_salary'))) print $langs->trans("User");
		else print $langs->trans("ThirdParty");
		print '</td>';
		// Amount HT
		//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="120">'.$langs->trans("AmountHT").'</td>';
		//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td class="right" width="120">'.$langs->trans("Amount").'</td>';
        if ($key == 'loan') print '<td class="left" width="120">'.$langs->trans("LoanCapital").'</td>';
		elseif (empty($value['disableamount'])) print '<td class="left" width="120">'.$langs->trans("AmountHT").'</td>';
		else print '<td width="120"></td>';
		// Amount TTC
		//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="120">'.$langs->trans("AmountTTC").'</td>';
        if ($key == 'loan') print '<td class="left" width="120">'.$langs->trans("RemainderToPay").'</td>';
        elseif (empty($value['disableamount'])) print '<td class="left" width="120">'.$langs->trans("AmountTTC").'</td>';
		else print '<td width="120"></td>';
		// Status
		if (in_array($tablename, array('projet_task'))) print '<td class="left" width="200">'.$langs->trans("ProgressDeclared").'</td>';
		else print '<td class="left" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';

		$elementarray = $object->get_element_list($key, $tablename, $datefieldname, $dates, $datee, !empty($project_field) ? $project_field : 'fk_projet');
		if (is_array($elementarray) && count($elementarray) > 0)
		{
			$total_ht = 0;
			$total_ttc = 0;

			$total_ht_by_third = 0;
			$total_ttc_by_third = 0;

			$saved_third_id = 0;
			$breakline = '';

			if (canApplySubtotalOn($tablename))
			{
				// Sort
				$elementarray = sortElementsByClientName($elementarray);
			}

			$num = count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$tmp = explode('_', $elementarray[$i]);
				$idofelement = $tmp[0];
				$idofelementuser = $tmp[1];

				$element->fetch($idofelement);

				//Filtrado de Productos Pertenecientes a un Envío
				if($key == 'shipping'){

					$shipment_products = array();

					foreach($element->lines as $line){
						array_push($shipment_products, $line->fk_product);
					}
					
					if(!empty(GETPOST('product_shipping_label'))){
						$coincidiences = array_intersect($shipment_products, GETPOST('product_shipping_label'));
						if(count($coincidiences) <= 0){
							continue;
						}
						$count_shipping++;
					}

					if(!empty(GETPOST('product_shipping'))){
						$coincidiences = array_intersect($shipment_products, GETPOST('product_shipping'));
						if(count($coincidiences) <= 0){
							continue;
						}
						$count_shipping++;
					}
				}

				if ($idofelementuser) $elementuser->fetch($idofelementuser);

				// Special cases
				if ($tablename != 'expensereport_det')
				{
					if (method_exists($element, 'fetch_thirdparty')) $element->fetch_thirdparty();
				}
				else
				{
					$expensereport = new ExpenseReport($db);
					$expensereport->fetch($element->fk_expensereport);
				}

				//print 'xxx'.$tablename.'yyy'.$classname;

				if ($breakline && $saved_third_id != $element->thirdparty->id)
				{
					print $breakline;

					$saved_third_id = $element->thirdparty->id;
					$breakline = '';

					$total_ht_by_third = 0;
					$total_ttc_by_third = 0;
				}
				$saved_third_id = $element->thirdparty->id;

				$qualifiedfortotal = true;
				if ($key == 'invoice')
				{
					if (!empty($element->close_code) && $element->close_code == 'replaced') $qualifiedfortotal = false; // Replacement invoice, do not include into total
				}

				print '<tr class="oddeven">';
				// dropdown de pedidos
				if($tablename == "expedition")
				{
					print '<td class="dropdown-toggle" id="collapse_'.$element->id.'" name="collapse_'.$element->id.'" style="cursor: pointer;  word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 24px;"></td>';
					$expedition_list = new Expedition($db);
				}

				// Remove link
				print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 24px;">';
				if ($tablename != 'projet_task' && $tablename != 'stock_mouvement')
				{
					if (empty($conf->global->PROJECT_DISABLE_UNLINK_FROM_OVERVIEW) || $user->admin)		// PROJECT_DISABLE_UNLINK_FROM_OVERVIEW is empty by defaut, so this test true
					{
						print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' .  $object->id . '&action=unlink&tablename=' . $tablename . '&elementselect=' . $element->id . ($project_field ? '&projectfield=' . $project_field : '') . '" class="reposition">';
						print img_picto($langs->trans('Unlink'), 'unlink');
						print '</a>';
					}
				}
				print "</td>\n";

				// Ref
				print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 400px;" class="left">';
				if ($tablename == 'expensereport_det')
				{
					print $expensereport->getNomUrl(1);
				}
				else
				{
					// Show ref with link
					if ($element instanceof Task)
					{
						print $element->getNomUrl(1, 'withproject', 'time');
						print ' - '.dol_trunc($element->label, 48);
					}
					elseif ($key == 'loan'){
                        print $element->getNomUrl(1);
                        print ' - '.dol_trunc($element->label, 48);
                    }
					else print $element->getNomUrl(1);

					$element_doc = $element->element;
					$filename = dol_sanitizeFileName($element->ref);
					$filedir = $conf->{$element_doc}->multidir_output[$element->entity].'/'.dol_sanitizeFileName($element->ref);

					if ($element_doc === 'order_supplier') {
						$element_doc = 'commande_fournisseur';
						$filedir = $conf->fournisseur->commande->multidir_output[$element->entity].'/'.dol_sanitizeFileName($element->ref);
					}
					elseif ($element_doc === 'invoice_supplier') {
						$element_doc = 'facture_fournisseur';
						$filename = get_exdir($element->id, 2, 0, 0, $element, 'product').dol_sanitizeFileName($element->ref);
						$filedir = $conf->fournisseur->facture->multidir_output[$element->entity].'/'.get_exdir($element->id, 2, 0, 0, $element, 'invoice_supplier').dol_sanitizeFileName($element->ref);
					}

					print '<div class="inline-block valignmiddle">'.$formfile->getDocumentsLink($element_doc, $filename, $filedir).'</div>';

					// Show supplier ref
					if (!empty($element->ref_supplier)) print ' - '.$element->ref_supplier;
					// Show customer ref
					if (!empty($element->ref_customer)) print ' - '.$element->ref_customer;
				}

				print "</td>\n";
				

				if($key == 'shipping' && $tablename != "expedition"){
					//Toggle para Display de Productos
					print '<td class="dropdown-toggle right" id="extra-toggle-'.$element->id.'" style="cursor: pointer;"> </td>';
				}

				// Date or TimeSpent
				$date = ''; $total_time_by_line = null;
				if ($tablename == 'expensereport_det') $date = $element->date; // No draft status on lines
				elseif ($tablename == 'stock_mouvement') $date = $element->datem;
				elseif ($tablename == 'payment_salary') $date = $element->datev;
				elseif ($tablename == 'payment_various') $date = $element->datev;
				elseif ($tablename == 'chargesociales') $date = $element->date_ech;
				elseif (!empty($element->status) || !empty($element->statut) || !empty($element->fk_status))
				{
				    if ($tablename == 'don') $date = $element->datedon;
				    if ($tablename == 'commande_fournisseur' || $tablename == 'supplier_order')
    				{
    				    $date = ($element->date_commande ? $element->date_commande : $element->date_valid);
    				}
    				elseif ($tablename == 'supplier_proposal') $date = $element->date_validation; // There is no other date for this
    				elseif ($tablename == 'fichinter') $date = $element->datev; // There is no other date for this
    				elseif ($tablename == 'projet_task') $date = ''; // We show no date. Showing date of beginning of task make user think it is date of time consumed
					else
    				{
    					$date = $element->date; // invoice, ...
    					if (empty($date)) $date = $element->date_contrat;
    					if (empty($date)) $date = $element->datev;
    				}
				}
                elseif ($key == 'loan'){
                    $date = $element->datestart;
                }

				print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 200px;" class="left">';
				if ($tablename == 'actioncomm')
				{
				    print dol_print_date($element->datep, 'dayhour');
				    if ($element->datef && $element->datef > $element->datep) print " - ".dol_print_date($element->datef, 'dayhour');
				}
				elseif (in_array($tablename, array('projet_task')))
				{
				    $tmpprojtime = $element->getSumOfAmount($elementuser, $dates, $datee); // $element is a task. $elementuser may be empty
                    print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$idofelement.'&withproject=1">';
				    print convertSecondToTime($tmpprojtime['nbseconds'], 'allhourmin');
                	print '</a>';
				    $total_time_by_line = $tmpprojtime['nbseconds'];
				}
				else print dol_print_date($date, 'day');
				print '</td>';

				// Third party or user
                print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 200px;" class="left">';
                if (is_object($element->thirdparty)) {
                    print $element->thirdparty->getNomUrl(1, '', 48);
                } elseif ($tablename == 'expensereport_det') {
                	$tmpuser = new User($db);
                	$tmpuser->fetch($expensereport->fk_user_author);
                	print $tmpuser->getNomUrl(1, '', 48);
                }
				elseif ($tablename == 'payment_salary')
				{
					$tmpuser = new User($db);
					$tmpuser->fetch($element->fk_user);
					print $tmpuser->getNomUrl(1, '', 48);
				}
				elseif ($tablename == 'don' || $tablename == 'stock_mouvement')
                {
                	if ($element->fk_user_author > 0)
                	{
	                	$tmpuser2 = new User($db);
	                	$tmpuser2->fetch($element->fk_user_author);
	                	print $tmpuser2->getNomUrl(1, '', 48);
                	}
                }
                elseif ($tablename == 'projet_task' && $key == 'project_task_time')	// if $key == 'project_task', we don't want details per user
                {
                    print $elementuser->getNomUrl(1);
                }
				print '</td>';

                // Amount without tax
				$warning = '';
				if (empty($value['disableamount']))
				{
				    $total_ht_by_line = null;
				    $othermessage = '';
					if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'payment_salary') $total_ht_by_line = $element->amount;
					elseif ($tablename == 'fichinter') $total_ht_by_line = $element->getAmount();
					elseif ($tablename == 'stock_mouvement') $total_ht_by_line = $element->price * abs($element->qty);
					elseif (in_array($tablename, array('projet_task')))
					{
					    if (!empty($conf->salaries->enabled))
					    {
        				    // TODO Permission to read daily rate to show value
					        $total_ht_by_line = price2num($tmpprojtime['amount'], 'MT');
    						if ($tmpprojtime['nblinesnull'] > 0)
    						{
    							$langs->load("errors");
    							$warning = $langs->trans("WarningSomeLinesWithNullHourlyRate", $conf->currency);
    						}
					    }
					    else
					    {
					        $othermessage = $form->textwithpicto($langs->trans("NotAvailable"), $langs->trans("ModuleSalaryToDefineHourlyRateMustBeEnabled"));
					    }
					}
                    elseif ($key == 'loan') $total_ht_by_line = $element->capital;
					else
					{
						$total_ht_by_line = $element->total_ht;
					}

					// Change sign of $total_ht_by_line and $total_ttc_by_line for some cases
					if ($tablename == 'payment_various')
					{
					    if ($element->sens == 0)
					    {
					        $total_ht_by_line = -$total_ht_by_line;
					    }
					}

					print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 200px;" class="left">';
					if ($othermessage) print $othermessage;
					if (isset($total_ht_by_line))
					{
						if (! $qualifiedfortotal) print '<strike>';
						print price($total_ht_by_line);
						if (! $qualifiedfortotal) print '</strike>';
					}
					if ($warning) print ' '.img_warning($warning);
					print '</td>';
				}
				else print '<td></td>';

                // Amount inc tax
				if (empty($value['disableamount']))
				{
				    $total_ttc_by_line = null;
					if ($tablename == 'don' || $tablename == 'chargesociales' || $tablename == 'payment_various' || $tablename == 'payment_salary') $total_ttc_by_line = $element->amount;
					elseif ($tablename == 'fichinter') $total_ttc_by_line = $element->getAmount();
					elseif ($tablename == 'stock_mouvement') $total_ttc_by_line = $element->price * abs($element->qty);
					elseif ($tablename == 'projet_task')
					{
					    if (!empty($conf->salaries->enabled))
					    {
					        // TODO Permission to read daily rate
    						$defaultvat = get_default_tva($mysoc, $mysoc);
    						$total_ttc_by_line = price2num($total_ht_by_line * (1 + ($defaultvat / 100)), 'MT');
					    }
					    else
					    {
					        $othermessage = $form->textwithpicto($langs->trans("NotAvailable"), $langs->trans("ModuleSalaryToDefineHourlyRateMustBeEnabled"));
					    }
					}
                    elseif ($key == 'loan') $total_ttc_by_line = $element->capital - $element->getSumPayment();
					else
					{
						$total_ttc_by_line = $element->total_ttc;
					}

					// Change sign of $total_ht_by_line and $total_ttc_by_line for some cases
					if ($tablename == 'payment_various')
					{
					    if ($element->sens == 0)
					    {
					        $total_ttc_by_line = -$total_ttc_by_line;
					    }
					}

					print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 200px;" class="left">';
					if ($othermessage) print $othermessage;
					if (isset($total_ttc_by_line))
					{
						if (! $qualifiedfortotal) print '<strike>';
						print price($total_ttc_by_line);
						if (! $qualifiedfortotal) print '</strike>';
					}
					if ($warning) print ' '.img_warning($warning);
					print '</td>';
				}
				else print '<td></td>';

				// Status
				print '<td style=" word-wrap: break-word; overflow: hidden; table-layout:fixed; width: 200px;" class="left">';
				if ($tablename == 'expensereport_det')
				{
					print $expensereport->getLibStatut(5);
				}
				elseif ($element instanceof CommonInvoice)
				{
					//This applies for Facture and FactureFournisseur
					print $element->getLibStatut(5, $element->getSommePaiement());
				}
				elseif ($element instanceof Task)
				{
					if ($element->progress != '')
					{
						print $element->progress.' %';
					}
				}
				elseif ($tablename == 'stock_mouvement')
				{
					print $element->getLibStatut(3);
				}
				else
				{
					print $element->getLibStatut(5);
				}
				print '</td>';

				print '</tr>';
				//Array que guarda los id de los productos asociados al pedido para generar inputs
				print '<script>
					//var array_ids = [];
					var json_modificaciones = []; //json para guardar los productos por envio 
					var array_'.$element->id.' = new Array();
					</script>';
				if($tablename == "expedition") //&& False)
				{
					//Se guarda en un array todos los id que aparecen una unica vez
					print '
						<script>
						if (!array_ids.includes('.$element->id.')) {
							array_ids.push('.$element->id.');
							//console.log('.$element->id.');
						}
						// item = {}; // item del json para asignar productos por envio
						// item["envio"] = '.$element->id.';
						// item["productos"] = {};
						</script>';
					//dropdown solo para pedidos
					$expedition_list->fetch($element->id);
					$expedition_list->fetch_lines();
					print '<tr id="tr-toggle_'.$element->id.'" style="display: none;"><table class="noborder centpercent"><tr><td style="width:100%">';
					print '<div id="area-toggle_'.$element->id.'" style="display: none;">';
					print '<form class="nocellnopadd" id="devolv_qty_form_'.$element->id.'" name="devolv_qty_form_'.$element->id.'" action="'.$_SERVER["PHP_SELF"] . '?id=' .  $object->id .'" method="POST">';
					print '<input type="hidden" name="action" value="update_qty">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<table style="table-layout: fixed; width: 100%;" class="noborder centpercent">';
					print '<th style="overflow: hidden; white-space: nowrap; width: 200px;" class="left liste_titre">Productos</th>';
					print '<th>&nbsp;</th>';
					print '<th>&nbsp;</th>';
					print '<th style="overflow: hidden; white-space: nowrap; width: 200px;" class="left liste_titre">Cant. Pedida</th>';
					print '<th>&nbsp;</th>';
					print '<th>&nbsp;</th>';
					print '<th style="overflow: hidden; white-space: nowrap; width: 200px;" class="left liste_titre">Cant. Enviada</th>';
					print '<th>&nbsp;</th>';
					print '<th>&nbsp;</th>';
					print '<th style="overflow: hidden; white-space: nowrap; width: 200px;" class="left liste_titre">Con devolución</th>';
					print '<th>&nbsp;</th>';
					print '<th>&nbsp;</th>';
					print '<th id="dev_qty_title_'.$element->id.'" style="overflow: hidden; white-space: nowrap; width: 200px;" class="left liste_titre">&nbsp;</th>';
					print '<th>&nbsp;</th>';
					print '<th>&nbsp;</th>';
					print '<th style="overflow: hidden; white-space: nowrap; width: 200px;" id="secc_btn_devolv_'.$element->id.'" name="secc_btn_devolv_'.$element->id.'">
					<a style="color: red;" class="butActionDelete" id="btn_devolv_'.$element->id.'" name="btn_devolv_'.$element->id.'">'.$langs->trans("Reject").'</a></th>';
					$product = new Product($db);
					//se guaran los id de los productos en un array
					print '<script>
					array_'.$element->id.'.push('.$line->id.');
					// console.log('.$line->id.');
					</script>';
					$j=0;
					foreach ($expedition_list->lines as $line) {
						//Fecth to the product line
						$product->fetch($line->fk_product);
						print '<script> //arreglo de productos por pedido
						// item["productos"]["producto_"'.$line->id.'] = '.$element->id.';
						</script>'; 
						//Query to get return qty
						$sqlDev = "SELECT devolucion FROM ".MAIN_DB_PREFIX."expeditiondet WHERE rowid = ".$line->id."";
						$resqlDev = $db->query($sqlDev);
						$objDev = 0;
						if ($resqlDev){
							$objDev = $db->fetch_object($resqlDev);
						}else{
							$objDev = "Verificar manualmente";
						}
						print '<tr>';
						print '<td id="prod_ref_'.$line->id.'" name="prod_ref_'.$line->id.'" style="overflow: hidden; white-space: nowrap; width: 200px;">'.$product->getNomUrl(1).'</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td style="overflow: hidden; white-space: nowrap; width: 200px;">'.$line->qty_asked.'</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td id="qty_send_'.$line->id.'" style="overflow: hidden; white-space: nowrap; width: 200px;">'.$line->qty.'</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td style="overflow: hidden; white-space: nowrap; width: 200px;">'.$objDev->devolucion.'</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td style="overflow: hidden; white-space: nowrap; width: 200px;" id="edit_qty_'.$line->id.'" name="edit_qty_'.$line->id.'">&nbsp;</td>';
						//se guaran los id de los productos en un array
						print '<script>
						// if(!array_ids_hijos.include('.$element->id.'){
						// 	let envios = {
						// 		id_padre: '.$element->id.',
						// 		id_hijos: {
						// 				{
										
						// 				}
						// 		}
						// 	}
						// 	array_ids_hijos.push();
						
						// }else{
						
						// }
                        
						//Obtenemos el index de la venta
						var index = array_ids_shipments.findIndex((shipment) => shipment.id == '.$element->id.');

						//Si la venta no existe la agregamos al arreglo
						if(index == -1) {
							array_ids_shipments.push({
								id: '.$element->id.',
								products: []
							});
							index = array_ids_shipments.length - 1;
						}

						// array_ids_shipments.findIndex((shipment) => shipment.id == '.$element->id.').products.push('.$line->id.');
						array_ids_shipments[index].products.push('.$line->id.');

						//Agregamos el producto a la venta
						// array_ids_shipments[index].products.push({
						// 	//Aquí van los datos de los productos
						// 	array_ids_shipments.findIndex((shipment) => shipment.id == '.$element->id.').products.push('.$line->id.');
						// });
						
						//console.log('.$element->id.');
						//console.log('.$line->id.');
						array_'.$element->id.'.push('.$line->id.');
						// console.log('.$line->id.');
						</script>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '</tr>';

						// Almacenar datos para generar pdf de envíos con devolución
						$isShipAssoc = ($title == 'ListShippingAssociatedProject') ? true : false ;
						$hasDev = ($objDev->devolucion > 0) ? true : false ;

						if ($isShipAssoc && $hasDev) {
							$proyecto['productos'][$element->ref]['envios'][$product->ref]['desc'] = $element->ref_customer;
							$proyecto['productos'][$element->ref]['envios'][$product->ref]['qty_sent'] = $line->qty_asked;
							$proyecto['productos'][$element->ref]['envios'][$product->ref]['qty_dev'] = $objDev->devolucion;
						}

						if ($isShipAssoc && $hasDev) {
							//Query to get Ref of commande
							$sql = 'select ref from llx_commande where rowid = '.$element->origin_id;
							$resql = $db->query($sql);
							if($resql){
								$res = $db->fetch_object($resql);
								$proyecto['productos'][$element->ref]['envios'][$product->ref]['ref_commande'] = $res->ref;
							}else{
								$proyecto['productos'][$element->ref]['envios'][$product->ref]['ref_commande'] = ' - ';
							}
							//Query to get Date of devoluciones
							$sql = 'SELECT date_devolucion FROM llx_expeditiondet where fk_expedition = '.$element->id.' AND rowid = '.$line->id;
							$resql = $db->query($sql);
							if($resql){
								
								$res = $db->fetch_object($resql);
								$proyecto['productos'][$element->ref]['envios'][$product->ref]['date_devolucion'] = str_replace('-','/',$res->date_devolucion);
							}else{
								$proyecto['productos'][$element->ref]['envios'][$product->ref]['date_devolucion'] = ' - ';
							}
							$proyecto['productos'][$element->ref]['envios'][$product->ref]['desc'] = $product->label;
							$proyecto['productos'][$element->ref]['envios'][$product->ref]['qty_sent'] = $line->qty_asked;
							$proyecto['productos'][$element->ref]['envios'][$product->ref]['qty_dev'] = $objDev->devolucion;
						}
			
						$j++;
					}
					print '<script>
					// console.log(item);
					// json_modificaciones.push(item);
					</script>';
					//Seccion de botones confirmar o eliminar
					print '<tr>
					<td bgcolor="white" style="display: none; border:none;" id="secc_btn_confirm_'.$element->id.'"></td>
					<td bgcolor="white" style="display: none; border:none;" id="secc_btn_cancelar_'.$element->id.'"></td>
					</tr>';
					//print '<div id="table 1"></div>';
					print '</table>';
					print '</form>';
					print '</div>';
					print '</td></tr>';
					print '</table>';
					//print '<div id="table 2"></div>';
					print '</tr><table style="width:100%">';
					print '<script>
					$(document).ready(function() {
						$("#collapse_'.$element->id.'").on("click", function(event) {
							if($(this).parent().hasClass("open"))
								$("#tr-toggle_'.$element->id.'").css("display","none");
							else
								$("#tr-toggle_'.$element->id.'").css("display","block");
								$("#area-toggle_'.$element->id.'").fadeToggle("fast");
								$(this).parent().toggleClass("open");
							var top = $(event.target).offset().top;
							$(window).scrollTop(top - 200);

						});
						$("#btn_devolv_'.$element->id.'").on("click", function(event) {
							
							for( var i = 0 ; i <= array_'.$element->id.'.length+1; i++ )
							{
								//var tmp_tag = array_'.$element->id.'.pop();
								var tmp_tag = array_'.$element->id.'[i];
								//console.log(tmp_tag);
								$("#edit_qty_"+tmp_tag).html("<input type=\"number\" id=\"cant_devol_"+tmp_tag+"\" name=\"cant_devol_"+tmp_tag+"\" value=\"0\" min=\"0\" max=\"100\" width=\"3\">");
								
							}
							$("#secc_btn_devolv_'.$element->id.'").html("");
							$("#secc_btn_confirm_'.$element->id.'").css("display","table-cell");
							$("#secc_btn_cancelar_'.$element->id.'").css("display","table-cell");
							$("#secc_btn_confirm_'.$element->id.'").html("<a class=\"butAction\" id=\"btn_confirmar_'.$element->id.'\" name=\"btn_confirmar_'.$element->id.'\">'.$langs->trans("Confirm").'</a>");
							$("#secc_btn_cancelar_'.$element->id.'").html("<a class=\"butAction\" id=\"btn_cancelar_'.$element->id.'\" name=\"btn_cancelar_'.$element->id.'\">'.$langs->trans("Cancel").'</a>");
							$("#dev_qty_title_'.$element->id.'").html("Cantidad a Devolver");

							
						});
						$("#secc_btn_cancelar_'.$element->id.'").on("click", function(event) {
							window.location.hash = "#collapse_'.$element->id.'";
    						window.location.reload(true);
						});
						$("#secc_btn_confirm_'.$element->id.'").on("click", function(event) {
							let id_send = '.$element->id.';
							let data = {
								"id": id_send
							};
							//console.log(id_send);
							$.ajax({
								data: {
									"id": id_send
								},
								url: "update_order_status.php",
								type: "POST",
								dataType: "json",
								success: function (data){
									//console.log(data);
								}
							});
							$("#devolv_qty_form_'.$element->id.'").submit();
						});
					});
					</script>';
				}
				if ($qualifiedfortotal)
				{
					$total_ht = $total_ht + $total_ht_by_line;
					$total_ttc = $total_ttc + $total_ttc_by_line;

					$total_ht_by_third += $total_ht_by_line;
					$total_ttc_by_third += $total_ttc_by_line;

					$total_time = $total_time + $total_time_by_line;
				}

				if (canApplySubtotalOn($tablename))
				{
					$breakline = '<tr class="liste_total liste_sub_total">';
					$breakline .= '<td colspan="2">';
					$breakline .= '</td>';
					$breakline .= '<td>';
					$breakline .= '</td>';
					$breakline .= '<td class="right">';
					$breakline .= $langs->trans('SubTotal').' : ';
					if (is_object($element->thirdparty)) $breakline .= $element->thirdparty->getNomUrl(0, '', 48);
					$breakline .= '</td>';
					$breakline .= '<td class="right">'.price($total_ht_by_third).'</td>';
					$breakline .= '<td class="right">'.price($total_ttc_by_third).'</td>';
					$breakline .= '<td></td>';
					$breakline .= '</tr>';
				}

				//Tabla de Datos de Producto sobre el Envío
				if($key == 'shipping'){

					$product = new Product($db);

					print '<tr class="liste_total liste_sub_total liste_titre drag drop oddeven parent_'.$element->id.'">';

					print '<td>&nbsp</td>';
					print '<td class="left"> Productos </td>';
					print '<td>&nbsp</td>';
					print '<td> Cant. Pedida </td>';
					print '<td> Cant. en otros Envíos </td>';
					print '<td class="right"> Cant. Enviada </td>';
					print '<td>&nbsp</td>';
					print '<td>&nbsp</td>';
					
					print '</tr>';

					// Get list of products already sent for same source object into $alreadysent
					$alreadysent = array();
					if ($element->origin && $element->origin_id > 0)
					{
						$sqlAlready = "SELECT obj.rowid, obj.fk_product, obj.label, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked, obj.date_start, obj.date_end";
						$sqlAlready .= ", ed.rowid as shipmentline_id, ed.qty as qty_shipped, ed.fk_expedition as expedition_id, ed.fk_origin_line, ed.fk_entrepot";
						$sqlAlready .= ", e.rowid as shipment_id, e.ref as shipment_ref, e.date_creation, e.date_valid, e.date_delivery, e.date_expedition";
						//if ($conf->livraison_bon->enabled) $sqlAlready .= ", l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received";
						$sqlAlready .= ', p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, p.tobatch as product_tobatch';
						$sqlAlready .= ', p.description as product_desc';
						$sqlAlready .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
						$sqlAlready .= ", ".MAIN_DB_PREFIX."expedition as e";
						$sqlAlready .= ", ".MAIN_DB_PREFIX.$element->origin."det as obj";
						//if ($conf->livraison_bon->enabled) $sqlAlready .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."livraisondet as ld ON ld.fk_livraison = l.rowid  AND obj.rowid = ld.fk_origin_line";
						$sqlAlready .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
						$sqlAlready .= " WHERE e.entity IN (".getEntity('expedition').")";
						$sqlAlready .= " AND obj.fk_".$element->origin." = ".$element->origin_id;
						$sqlAlready .= " AND obj.rowid = ed.fk_origin_line";
						$sqlAlready .= " AND ed.fk_expedition = e.rowid";
						//if ($filter) $sqlAlready.= $filter;
						$sqlAlready .= " ORDER BY obj.fk_product";

						dol_syslog("get list of shipment lines", LOG_DEBUG);
						$resqlAlready = $db->query($sqlAlready);
						if ($resqlAlready)
						{
							$numAlready = $db->num_rows($resqlAlready);
							$iAlready = 0;

							while ($iAlready < $numAlready)
							{
								$obj = $db->fetch_object($resqlAlready);
								if ($obj)
								{
									// $obj->rowid is rowid in $origin."det" table
									$alreadysent[$obj->rowid][$obj->shipmentline_id] = array('shipment_ref'=>$obj->shipment_ref, 'shipment_id'=>$obj->shipment_id, 'warehouse'=>$obj->fk_entrepot, 'qty_shipped'=>$obj->qty_shipped, 'date_valid'=>$db->jdate($obj->date_valid), 'date_delivery'=>$db->jdate($obj->date_delivery));
								}
								$iAlready++;
							}
						}
						//var_dump($alreadysent);
					}

					foreach($element->lines as $line){
						
						$countAlreadySent = 0;
						foreach($alreadysent as $lineSent){
							foreach($lineSent as $k => $info){
								if($line->id == $k){
									$countAlreadySent++;
								}
							}
						}

						$countAlreadySent--;

						$product->fetch($line->fk_product);

						print '<tr class="drag drop oddeven parent_'.$element->id.'">';

						print '<td>&nbsp</td>';
						print '<td class="left"> '.$product->getNomUrl(1,'',0,-1,0,0,false).' </td>';
						print '<td>&nbsp</td>';
						print '<td class="center"> '.$line->qty_asked.'</td>';
						print '<td class="center"> '.($countAlreadySent > 0 ? $countAlreadySent : '').' </td>';
						print '<td class="right"> '.$line->qty_shipped.'</td>';
						print '<td>&nbsp</td>';
						print '<td>&nbsp</td>';

						print '</tr>';

					}

					print '<script>
					$(document).ready(function() {
						$("#extra-toggle-'.$element->id.'").on("click", function(event) {
							$(".parent_'.$element->id.'").fadeToggle("slow");
							$(this).parent().toggleClass("open");
						});

						$(".parent_'.$element->id.'").hide();
					});
					</script>';

				}
			}

			if ($breakline) print $breakline;

			// Total
			$colspan = 4;
			if (in_array($tablename, array('projet_task'))) $colspan = 2;
			print '<tr class="liste_total"><td colspan="'.$colspan.'">'.$langs->trans("Number").': '.( ($key == 'shipping' && !empty(GETPOST('product_shipping')) || !empty(GETPOST('product_shipping_label')) ) ? $count_shipping : $i).'</td>';
			if (in_array($tablename, array('projet_task')))
			{
    			print '<td class="center">';
    			print convertSecondToTime($total_time, 'allhourmin');
    			print '</td>';
    			print '<td>';
    			print '</td>';
			}
			//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td class="right" width="100">'.$langs->trans("Total").' : '.price($total_ht).'</td>';
			print '<td class="right">';
			if (empty($value['disableamount']))
			{
                if ($key == 'loan') print $langs->trans("Total").' '.$langs->trans("LoanCapital").' : '.price($total_ttc);
                elseif ($tablename != 'projet_task' || !empty($conf->salaries->enabled)) print ''.$langs->trans("TotalHT").' : '.price($total_ht);
			}
			print '</td>';
			//if (empty($value['disableamount']) && ! in_array($tablename, array('projet_task'))) print '<td class="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
			//elseif (empty($value['disableamount']) && in_array($tablename, array('projet_task'))) print '<td class="right" width="100"></td>';
			print '<td class="right">';
			if (empty($value['disableamount']))
			{
                if ($key == 'loan') print $langs->trans("Total").' '.$langs->trans("RemainderToPay").' : '.price($total_ttc);
			    elseif ($tablename != 'projet_task' || !empty($conf->salaries->enabled)) print $langs->trans("TotalTTC").' : '.price($total_ttc);
			}
			print '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			if (!is_array($elementarray))	// error
			{
				print $elementarray;
			}
		}
		//print '<div id="table 3"></div>';
		print "</table>";
		print "<br>\n";
	}

  $proyecto['ref_proyecto'] = $object->ref;
  $proyecto['code_client'] = $object->thirdparty->code_client;
  $proyecto['nom'] = $object->thirdparty->nom;
  $proyecto['address'] = $object->thirdparty->address;
  $proyecto['zip'] = $object->thirdparty->zip;
  $proyecto['town'] = $object->thirdparty->town;
  $proyecto['phone'] = $object->thirdparty->phone;
  $proyecto['email'] = $object->thirdparty->email;
  $proyecto['date_start'] = $object->date_start;

  session_start();
  $_SESSION['proyecto'] = $proyecto;
}
print '
<script>
	$("#btn_devolv_todos").on("click", function(event) {
		console.log("clic en devolucion masiva");
		//console.log(array_ids_shipments);
		// Dropdown a todos los elementos en la lista
		for (var i = 0; i < array_ids.length; i++) {
			$("#tr-toggle_"+array_ids[i]).css("display","block");
			$("#area-toggle_"+array_ids[i]).css("display","block");
			$("#btn_devolv_"+array_ids[i]).click();
			$("#secc_btn_cancelar_"+array_ids[i]).css("display","none");
			$("#secc_btn_confirm_"+array_ids[i]).css("display","none");
		}
		//Muestra los botones confirmar y cancelar
		$("#aceptar_cancelar_todo").css("display","block");
		document.location.href = "#buscar";
	});
	$("#btn_confirmar_todos").on("click", function(event) {
		var flag_incorrecto = false;
		event.preventDefault();
		console.log("entra a conf todos");
		var cont_dev =  0;
		for (var i = 0; i < array_ids_shipments.length; i++){

			var obj = array_ids_shipments[i];

			//Array de Ids de productos
			var value = obj["products"];
			
			for (var key in value){
				var id_prod = value[key];
				let cant_devol = $("#cant_devol_"+id_prod).val();
				let qty_send =parseInt($("#qty_send_"+id_prod).text());
				let comp = qty_send - cant_devol;
				if(cant_devol > 0){
					if(comp < 0)
					{
						window.location = "'.DOL_URL_ROOT.'/projet/element.php?id='.$id.'&action=qty_devol_error#buscar";
						//alert("Si entra");
						flag_incorrecto = true;
						break;
					}
				}
			}
			if(flag_incorrecto)
			{
				break;
			}

		}
		//Array para almacenar los ids y la cantidad a devoler de cada producto
		var devol_qty = [];
		//Array de id de los envios a cambiar de status
		var devol_shippment = [];
		if(!flag_incorrecto){
			for (var i = 0; i < array_ids_shipments.length; i++){
				var obj = array_ids_shipments[i];

				var flag_dev = false;
	
				//id del envio
				var id_shipm = obj.id;
	
				//Array de Ids de productos
				var value = obj["products"];

				for (var key in value){
					var id_prod = value[key];
					//console.log(id_prod);
					let cant_devol = $("#cant_devol_"+id_prod).val();
					let ref_prod = $("#prod_ref_"+id_prod).text();
					//Si encuentra un input con una devolucion a realizar manda la peticion para realizar la devolucion
					if(cant_devol > 0){
						cont_dev++;
						flag_dev = true;
						
						devol_qty.push(
							{"id": id_prod,
							 "qty": cant_devol,
							 "prod_ref": ref_prod
							});
						console.log(id_prod);
						console.log(obj.id);
						//console.log(flag_dev)
					} 
				}
				//Si al menos un producto del envio tiene devolucion, se va a cambiar el estado de la ficha de envio
				if(flag_dev){
					devol_shippment.push({"id": id_shipm});
					//Cambiar las cantidades
					console.log(devol_qty);
				}
			}
			if(cont_dev > 0){
				$.ajax({
					data: JSON.stringify(devol_qty),
					url: "massive_devol_prod.php",
					type: "POST",
					dataType: "json",
					async: false,
					success: function (data){
					//console.log(data);
					}
				});
				$.ajax({
					data: JSON.stringify(devol_shippment),
					url: "update_order_status.php",
					type: "POST",
					dataType: "json",
					async: false,
					success: function (data){
					//console.log(data);
					}
				});
				let add_url=""; 
				if(window.location.origin == "http://localhost"){
					add_url = "/cezac";
				}
				window.location.href = window.location.origin + add_url + "/projet/element.php?id='.$id.'&action=dev_done#buscar";
				setTimeout(() => {
					//console.log("recarga");
					window.location.href = window.location.origin + add_url + "/projet/element.php?id='.$id.'&action=dev_done&rc=1#buscar";
				  }, 1000);
				//window.location.href = window.location.origin + add_url + "/projet/element.php?id='.$id.'&action=dev_done#buscar";
			}else{
				document.location.reload(true);
			}
			document.location.reload(true);
		}		
	});
	$("#btn_cancelar_todos").on("click",function(){
		if(window.location.href.split("?")[1] == "id='.$id.'#buscar"){
			window.location.reload();
		}else{
			window.location = window.location.href.split("?")[0]+"?id='.$id.'#buscar";
		}
	});

</script>

';
// Enhance with select2
if ($conf->use_javascript_ajax)
{
	include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
	$comboenhancement = ajax_combobox('.elementselect');
	$out .= $comboenhancement;

	print $comboenhancement;
}



// End of page
llxFooter();
$db->close();



/**
 * Return if we should do a group by customer with sub-total
 *
 * @param 	string	$tablename		Name of table
 * @return	boolean					True to tell to make a group by sub-total
 */
function canApplySubtotalOn($tablename)
{
	global $conf;

	if (empty($conf->global->PROJECT_ADD_SUBTOTAL_LINES)) return false;
	return in_array($tablename, array('facture_fourn', 'commande_fournisseur'));
}

/**
 * sortElementsByClientName
 *
 * @param 	array		$elementarray	Element array
 * @return	array						Element array sorted
 */
function sortElementsByClientName($elementarray)
{
	global $db, $classname;

	$element = new $classname($db);

	$clientname = array();
	foreach ($elementarray as $key => $id)	// id = id of object
	{
		if (empty($clientname[$id]))
		{
			$element->fetch($id);
			$element->fetch_thirdparty();

			$clientname[$id] = $element->thirdparty->name;
		}
	}

	//var_dump($clientname);
	asort($clientname); // sort on name

	$elementarray = array();
	foreach ($clientname as $id => $name)
	{
		$elementarray[] = $id;
	}

	return $elementarray;
}
