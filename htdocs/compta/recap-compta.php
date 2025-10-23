<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017      Pierre-Henry Favre   <support@atm-consulting.fr>
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
 *  \file       htdocs/compta/recap-compta.php
 *	\ingroup    compta
 *  \brief      Page de fiche recap customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

// Load translation files required by the page
$langs->load("companies");
if (!empty($conf->facture->enabled)) $langs->load("bills");

$id = GETPOST('id') ?GETPOST('id', 'int') : GETPOST('socid', 'int');

// Security check
if ($user->socid) $id = $user->socid;
$result = restrictedArea($user, 'societe', $id, '&societe');

$object = new Societe($db);
if ($id > 0) $object->fetch($id);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('recapcomptacard', 'globalcard'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "f.datef,f.rowid"; // Set here default search field
if (!$sortorder) $sortorder = "DESC";


$arrayfields = array(
    'f.datef'=>array('label'=>"Date", 'checked'=>1),
    //...
);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('supplierbalencelist', 'globalcard'));

/*
 * Actions
 */
$parameters = array('socid' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object); // Note that $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// None


/*
 *	View
 */

$form = new Form($db);
$userstatic = new User($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Summary");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name.' - '.$langs->trans("Symmary");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url);
if ($id > 0)
{
    $param = '';
    if ($id > 0) $param .= '&socid='.$id;

    $head = societe_prepare_head($object);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"), 0, 'company');
	dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 1);
	dol_fiche_end();

	if (!empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		// Invoice list
		print load_fiche_titre($langs->trans("CustomerPreview"));

		print '<table class="noborder tagtable liste centpercent">';
		print '<tr class="liste_titre">';
        //if (!empty($arrayfields['f.datef']['checked']))  print_liste_field_titre($arrayfields['f.datef']['label'], $_SERVER["PHP_SELF"], "f.datef", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Element").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td>'.$langs->trans("Debit").'</td>';
		print '<td>'.$langs->trans("Credit").'</td>';
		print '<td>'.$langs->trans("Balance").'</td>';
		print '<td>'.$langs->trans("Author").'</td>';
		print '</tr>';

		$TData = array();

		//QUERY TICKETS
		$sql  = "SELECT pt.rowid, pt.date_ticket as date, pt.ticketnumber as ref, pt.fk_statut as statut, pt.total_ttc as total, pt.type , pt.fk_user_author as autor, s.login";
		$sql .= "	FROM llx_pos_ticket as pt , llx_user as s";
		$sql .= "		WHERE pt.fk_soc = ".$object->id." AND pt.entity IN (1) AND pt.fk_user_author = s.rowid";
		$sql .= "		AND pt.fk_statut IN (1,2) AND pt.fk_facture is null AND pt.paye = 0 ";
		$sql .= "		AND (pt.total_ttc != 0 OR pt.fk_statut != 1)";
				
		$sql .= "	UNION ";
		//QUERY FACTURAS
		$sql .= "SELECT f.rowid, f.datef as date, f.ref as ref, f.fk_statut as statut, f.total_ttc as total, f.type , f.fk_user_author as autor, s.login";
		$sql .= "	FROM llx_facture as f , llx_user as s";
		$sql .= "		WHERE f.fk_soc = ".$object->id." AND f.entity IN (1) AND f.fk_user_author = s.rowid";
		$sql .= "		AND f.fk_statut = 1 AND f.type IN (0,2) AND f.paye = 0";
		
		/*
		$sql .= "		UNION ";
		//QUERYS PEDIDOS
		$sql .= "	SELECT  c.rowid, c.date_commande as date, c.ref as ref, c.fk_statut as statut, c.total_ttc as total, 1 , c.fk_user_author as autor, s.login";
		$sql .= "	FROM llx_user as s , llx_commande AS c ";
		$sql .= "	LEFT JOIN llx_element_element AS ee ON (c.rowid = ee.fk_source AND ee.sourcetype='commande') ";
		$sql .= "    LEFT JOIN llx_facture AS f ON ee.fk_target = f.rowid AND ee.targettype='facture' ";
		$sql .= "		WHERE c.fk_soc = ".$object->id." AND f.fk_statut IN (0,3) AND c.fk_user_author = s.rowid";

		$sql .= "		UNION ";
		
		$sql .= " SELECT c.rowid, c.date_commande as date, c.ref as ref, c.fk_statut as statut, c.total_ttc as total, 1 , c.fk_user_author as autor, s.login";
		$sql .= "	FROM llx_user as s, llx_commande as c ";
		$sql .= "		WHERE c.fk_soc = ".$object->id." AND c.entity IN (1)  AND c.fk_user_author = s.rowid ";
		$sql .= "			AND c.fk_statut > 0";
		$sql .= "		AND (c.rowid  NOT IN (SELECT e.fk_source FROM llx_element_element AS e WHERE e.targettype = 'facture' AND e.sourcetype = 'commande') ";
		$sql .= "		AND c.rowid  NOT IN (SELECT e.fk_target FROM llx_element_element AS e WHERE e.sourcetype = 'facture' AND e.targettype = 'commande') )";
		*/
		
		$sql .= " ORDER BY date";
		$resql = $db->query($sql);

		if ($resql)
		{
			$num = $db->num_rows($resql);

			$totalDebit = 0;

			while ( $obj = $db->fetch_object($resql) ) {
				
				$fac = new Facture($db);
				$tic = new Ticket($db);
				//$ped = new Commande($db);

				$retFac = $fac->fetch('',$obj->ref);
				$retTic = $tic->fetch('',$obj->ref);
				//$retPed = $ped->fetch('',$obj->ref);

				print '<tr class="oddeven '.$html_class.'">';

				print '<td>'.$obj->date."</td>\n";

				if ($retFac > 0){
					print '<td>'.$fac->getNomUrl(1)."</td>\n";
					$totalpaye = $fac->getSommePaiement();
					print "<td>Factura</td>\n";
					print '<td>'.$fac->getLibStatut(2,$totalpaye)."</td>\n";
					$paiement = $fac->getSommePaiement();
					$creditnotes = $fac->getSumCreditNotesUsed();
					$deposits = $fac->getSumDepositsUsed();
					if(!$paiement) $paiement = 0;
					if(!$creditnotes) $creditnotes = 0;
					if(!$deposits) $deposits = 0;
					$pago = $paiement + $creditnotes + $deposits;
					$total = $obj->total;
					$saldo = $total - $pago;
				}

				if ($retTic > 0){
					print '<td>'.$tic->getNomUrl(1)."</td>\n";
					print "<td>Ticket</td>\n";
					print '<td>'.$tic->getLibStatut(1)."</td>\n";
					$pago = $tic->getSommePaiement();
					if(!$pago) $pago = 0;
					if($obj->type == 0){
						$total = $obj->total;
						$saldo = $total - $pago;
					}else{
						$total = $obj->total * -1;
						$saldo = $total + $pago;
					}
				}
				/*
				if ($retPed > 0){
					print '<td>'.$ped->getNomUrl(1)."</td>\n";
					print "<td>Pedido</td>\n";
					print '<td>'.$ped->getLibStatut(2)."</td>\n";
					$pago = 0;
					$total = $obj->total;
					$saldo = $total - $pago;
				}
				*/
				

				print '<td class="left">'.price($total).'</td>';
				print '<td class="left">'.price($pago).'</td>';
				print '<td class="left">'.price($saldo).'</td>';

				$debe += $total;
				$pagos += $pago;
				

				$userstatic->id = $obj->autor;
				$userstatic->login = $obj->login;
				print '<td class="left">'.$userstatic->getLoginUrl(1).'</td>';

				print "</tr>\n";
			}
		}
					
		print '<tr class="liste_total">';
		print '<td colspan="4">&nbsp;</td>';
		print '<td>'.price($debe).'</td>';
		print '<td>'.price($pagos).'</td>';
		print '<td>'.price(price2num($debe - $pagos, 'MT')).'</td>';
		print '<td></td>';
		print "</tr>\n";
	}
	print "</table>";
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();

/*



		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

			// Boucle sur chaque facture
			for ($i = 0; $i < $num; $i++)
			{
				$objf = $db->fetch_object($resql);

				$fac = new Facture($db);
				$ret = $fac->fetch($objf->facid);
				if ($ret < 0)
				{
					print $fac->error."<br>";
					continue;
				}
				$totalpaye = $fac->getSommePaiement();

				$userstatic->id = $objf->userid;
				$userstatic->login = $objf->login;

				$values = array(
					'fk_facture' => $objf->facid,
					'date' => $fac->date,
					'datefieldforsort' => $fac->date.'-'.$fac->ref,
					'link' => $fac->getNomUrl(1),
					'status' => $fac->getLibStatut(2, $totalpaye),
					'amount' => $fac->total_ttc,
					'author' => $userstatic->getLoginUrl(1)
				);

				$parameters = array('socid' => $id, 'values' => &$values, 'fac' => $fac, 'userstatic' => $userstatic);
				$reshook = $hookmanager->executeHooks('facdao', $parameters, $object); // Note that $parameters['values'] and $object may have been modified by some hooks
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

				$TData[] = $values;

				// Paiements
				$sql = "SELECT p.rowid, p.datep as dp, pf.amount, p.statut,";
				$sql .= " p.fk_user_creat, u.login, u.rowid as userid";
				$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf,";
				$sql .= " ".MAIN_DB_PREFIX."paiement as p";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_creat = u.rowid";
				$sql .= " WHERE pf.fk_paiement = p.rowid";
				$sql .= " AND p.entity = ".$conf->entity;
				$sql .= " AND pf.fk_facture = ".$fac->id;
				$sql .= " ORDER BY p.datep ASC, p.rowid ASC";

				$resqlp = $db->query($sql);
				if ($resqlp)
				{
					$nump = $db->num_rows($resqlp);
					$j = 0;

					while ($j < $nump)
					{
						$objp = $db->fetch_object($resqlp);

						$paymentstatic = new Paiement($db);
						$paymentstatic->id = $objp->rowid;

						$userstatic->id = $objp->userid;
						$userstatic->login = $objp->login;

						$values = array(
						'fk_paiement' => $objp->rowid,
							'date' => $db->jdate($objp->dp),
							'datefieldforsort' => $db->jdate($objp->dp).'-'.$fac->ref,
							'link' => $langs->trans("Payment").' '.$paymentstatic->getNomUrl(1),
							'status' => '',
							'amount' => -$objp->amount,
							'author' => $userstatic->getLoginUrl(1)
						);

						$parameters = array('socid' => $id, 'values' => &$values, 'fac' => $fac, 'userstatic' => $userstatic, 'paymentstatic' => $paymentstatic);
						$reshook = $hookmanager->executeHooks('paydao', $parameters, $object); // Note that $parameters['values'] and $object may have been modified by some hooks
						if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

						$TData[] = $values;

						$j++;
					}

					$db->free($resqlp);
				}
				else
				{
					dol_print_error($db);
				}
			}
		}
		else
		{
			dol_print_error($db);
		}

		if (empty($TData)) {
			print '<tr class="oddeven"><td colspan="7">'.$langs->trans("NoInvoice").'</td></tr>';
		} else {
			// Sort array by date ASC to calucalte balance
			$TData = dol_sort_array($TData, 'datefieldforsort', 'ASC');

			// Balance calculation
			$balance = 0;
			foreach ($TData as &$data1) {
				$balance += $data1['amount'];
				$data1['balance'] += $balance;
			}

			// Resorte array to have elements on the required $sortorder
			$TData = dol_sort_array($TData, 'datefieldforsort', $sortorder);

			$totalDebit = 0;
			$totalCredit = 0;

			// Display array
			foreach ($TData as $data) {
				$html_class = '';
				if (!empty($data['fk_facture'])) $html_class = 'facid-'.$data['fk_facture'];
				elseif (!empty($data['fk_paiement'])) $html_class = 'payid-'.$data['fk_paiement'];

				print '<tr class="oddeven '.$html_class.'">';

				print "<td class=\"center\">";
				if (!empty($data['fk_facture'])) print dol_print_date($data['date'], 'day');
				elseif (!empty($data['fk_paiement'])) print dol_print_date($data['date'], 'dayhour');
				print "</td>\n";

				print '<td>'.$data['link']."</td>\n";

				print '<td class="left">'.$data['status'].'</td>';

				print '<td class="right">'.(($data['amount'] > 0) ? price(abs($data['amount'])) : '')."</td>\n";

				$totalDebit += ($data['amount'] > 0) ? abs($data['amount']) : 0;

				print '<td class="right">'.(($data['amount'] > 0) ? '' : price(abs($data['amount'])))."</td>\n";
				$totalCredit += ($data['amount'] > 0) ? 0 : abs($data['amount']);

				// Balance
				print '<td class="right">'.price($data['balance'])."</td>\n";

				// Author
				print '<td class="nowrap right">';
				print $data['author'];
				print '</td>';

				print "</tr>\n";
			}

			print '<tr class="liste_total">';
			print '<td colspan="3">&nbsp;</td>';
			print '<td class="right">'.price($totalDebit).'</td>';
			print '<td class="right">'.price($totalCredit).'</td>';
			print '<td class="right">'.price(price2num($totalDebit - $totalCredit, 'MT')).'</td>';
			print '<td></td>';
			print "</tr>\n";
		}

		print "</table>";
	}
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
*/