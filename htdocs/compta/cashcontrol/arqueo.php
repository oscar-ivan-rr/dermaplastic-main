<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry        <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Andreu Bisquerra	 <jove@bisquerra.com>
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
 *	\file       htdocs/compta/cashcontrol/report.php
 *	\ingroup    cashdesk|takepos
 *	\brief      List of sales from POS
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1'); // Disable browser notification
}

$_GET['optioncss'] = "print";

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


$langs->loadLangs(array("bills", "banks"));

$id = GETPOST('id', 'int');

$object = new CashControl($db);
$object->fetch($id);

$posmodule = $object->posmodule;
$terminalid = $object->posnumber;

$initialbalanceforterminal = array();
$theoricalamountforterminal = array();
$theoricalnbofinvoiceforterminal = array();
$arrayofpaymentmode = array(
	'cash' => 'Cash',
	'debitcard' => 'Tarjeta de debito',
	'creditcard' => 'CreditCard',
	'transfer' => 'Transferencia'
);

// Security check
if ($user->socid > 0) { // Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
if (empty($user->rights->cashdesk->run) && empty($user->rights->takepos->run)) {
	accessforbidden();
}

/*
 * View
 */

llxHeader('', 'Arqueo de caja');

$sql = "SELECT f.rowid as facid, f.ref, f.datef as do, pf.amount as amount, b.fk_account as bankid, cp.code";
$sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement as p, " . MAIN_DB_PREFIX . "c_paiement as cp, " . MAIN_DB_PREFIX . "bank as b";
$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement AND p.fk_bank = b.rowid";
$sql .= " AND f.module_source = '" . $db->escape($posmodule) . "'";
$sql .= " AND f.pos_source IN (SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') FROM " . MAIN_DB_PREFIX . "const lc, " . MAIN_DB_PREFIX . "user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '" . $_SESSION["dol_login"] . "' AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%')";
$sql .= " AND f.paye = 1";
$sql .= " AND p.entity = " . $conf->entity; // Never share entities for features related to accountancy
$sql .= " AND p.datep BETWEEN '" . $db->idate($object->date_creation) . "'";
$sql .= " AND '" . $db->idate($object->date_valid ? $object->date_valid : dol_now('tzuser')) . "'";

$sql2 = "SELECT rowid FROM " . MAIN_DB_PREFIX . "bank_categ WHERE label LIKE '%especial%'";
$resql2 = $db->query($sql2);
if ($resql2) {
	$obj2 = $db->fetch_object($resql2);
	$category_transaction = $obj2->rowid;
}

$account = "CASHDESK_ID_BANKACCOUNT_CASH" . $object->posnumber;

$sqlem = "SELECT SUM(lb.amount) as total FROM " . MAIN_DB_PREFIX . "bank lb RIGHT JOIN " . MAIN_DB_PREFIX . "bank_class lbc ON lb.rowid = lbc.lineid WHERE lb.fk_account = " . $conf->global->$account;
$sqlem .= " AND lbc.fk_categ = " . $category_transaction . " AND lb.fk_type = 'LIQ' AND lb.datec BETWEEN '" . $db->idate($object->date_creation) . "'";
$sqlem .= " AND '" . $db->idate($object->date_valid ? $object->date_valid : dol_now('tzuser')) . "'";
$resem = $db->query($sqlem);
$res = $db->fetch_object($resem);

$sqlin = "SELECT b.rowid, b.datec as dc, b.amount, b.label, b.fk_account, b.fk_type, ba.rowid as bankid, ba.ref as bankref,";
$sqlin .= " bb.balance, bb.balance_antes FROM llx_bank_class as l, llx_bank_account as ba, llx_bank as b";
$sqlin .= " LEFT JOIN llx_bank_balance as bb ON bb.fk_bank = b.rowid WHERE b.fk_account = ba.rowid AND ba.entity IN (1)";
$sqlin .= " AND b.rowid = l.lineid AND l.fk_categ = $category_transaction AND b.amount > 0 AND ba.rowid = " . $conf->global->$account;
$sqlin .= " AND b.datec BETWEEN '" . $db->idate($object->date_creation) . "' AND CURRENT_TIMESTAMP()";
$sqlin .= " ORDER BY b.datec DESC";
$resin = $db->query($sqlin);

$sqlout = "SELECT b.rowid, b.datec as dc, b.amount, b.label, b.fk_account, b.fk_type, ba.rowid as bankid, ba.ref as bankref,";
$sqlout .= " bb.balance, bb.balance_antes FROM llx_bank_class as l, llx_bank_account as ba, llx_bank as b";
$sqlout .= " LEFT JOIN llx_bank_balance as bb ON bb.fk_bank = b.rowid WHERE b.fk_account = ba.rowid AND ba.entity IN (1)";
$sqlout .= " AND b.rowid = l.lineid AND l.fk_categ = $category_transaction AND b.amount < 0 AND ba.rowid = " . $conf->global->$account;
$sqlout .= " AND b.datec BETWEEN '" . $db->idate($object->date_creation) . "' AND CURRENT_TIMESTAMP()";
$sqlout .= " ORDER BY b.datec DESC";
$resout = $db->query($sqlout);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	foreach ($arrayofpaymentmode as $key => $val) {
		$sql = "SELECT SUM(pf.amount) as total, COUNT(*) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf, " . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "paiement as p, " . MAIN_DB_PREFIX . "c_paiement as cp";
		$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement";
		$sql .= " AND f.module_source = '" . $db->escape($posmodule) . "'";
		$sql .= " AND f.pos_source IN (SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') FROM " . MAIN_DB_PREFIX . "const lc, " . MAIN_DB_PREFIX . "user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '" . $_SESSION["dol_login"] . "' AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%')";
		$sql .= " AND f.paye = 1";
		$sql .= " AND p.entity IN (" . getEntity('facture') . ")";
		if ($key == 'cash') {
			$sql .= " AND cp.code = 'LIQ'";
		} elseif ($key == 'debitcard') {
			$sql .= " AND cp.code = 'TD/C'";
		} elseif ($key == 'creditcard') {
			$sql .= " AND cp.code = 'CB'";
		} elseif ($key == 'transfer') {
			$sql .= " AND cp.code = 'VIR'";
		} else {
			dol_print_error('Value for key = ' . $key . ' not supported');
			exit;
		}
		$sql .= " AND p.datec BETWEEN '" . $db->idate($object->date_creation) . "' AND '" . $db->idate($object->date_valid ? $object->date_valid : dol_now('tzuser')) . "'";

		$resql = $db->query($sql);
		if ($resql) {
			$theoricalamountforterminal[$terminalid][$key] = $initialbalanceforterminal[$terminalid][$key];
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$theoricalamountforterminal[$terminalid][$key] = price2num($theoricalamountforterminal[$terminalid][$key] + $obj->total);
				$theoricalnbofinvoiceforterminal[$terminalid][$key] = $obj->nb;
			}
		} else {
			dol_print_error($db);
		}
	}

	$cash = $theoricalamountforterminal[$terminalid]['cash'];
	$debitcard = $theoricalamountforterminal[$terminalid]['debitcard'];
	$creditcard = $theoricalamountforterminal[$terminalid]['creditcard'];
	$transfer = $theoricalamountforterminal[$terminalid]['transfer'];

	print '<center>';
	print '<h2>';
	print "Arqueo de caja";
	print "</h2>";
	$userauthor = $object->fk_user_valid;
	if (empty($userauthor)) {
		$userauthor = $object->fk_user_creat;
	}

	$uservalid = new User($db);
	if ($userauthor > 0) {
		$uservalid->fetch($userauthor);
		print $langs->trans("User") . ': ' . $uservalid->getFullName($langs);
	}
	print "<br>Apertura: " . dol_print_date($object->date_creation, 'dayhour');
	$object->date_valid ? print "<br>Cierre: " . dol_print_date($object->date_valid, 'dayhour') . "</p></center>" : print "</p></center>";

	print "<div style='text-align: right'><h2>";
	print "Fondo de caja :" . ' <div class="inline-block amount width100">' . price($object->opening) . '</div>';
	print "</h2></div>";

	if ($db->num_rows($resin) > 0 || $db->num_rows($resout) > 0) {
		print "<p style='text-align:center'>......................................................";
		if ($db->num_rows($resin) > 0) {
			print "<p style='text-align: left'>Entradas de efectivo: " . $db->num_rows($resin);
			print "<div style='text-align: right'><p>";
			while ($objin = $db->fetch_object($resin)) {
				print "<p>" . $objin->label . " +" . price($objin->amount);
			}
		}
		if ($db->num_rows($resout) > 0) {
			print "<p style='text-align: left'>Salidas de efectivo: " . $db->num_rows($resout);
			print "<div style='text-align: right'><p>";
			while ($objout = $db->fetch_object($resout)) {
				print "<p>" . $objout->label . " " . price($objout->amount);
			}
		}
		print "<p style='text-align:center'>......................................................";
		print "<p style='text-align: right'>Total entradas y salidas: " . price($res->total);
	}
	print "<p>Ventas: " . price($cash);
	$cash += $res->total;
	print '<div style="text-align: right">';
	print '<h2>';
	print $langs->trans("Cash") . ' : <div class="inline-block amount width100">' . price($cash) . '</div>';
	print "<br>";
	print $langs->trans("PaymentTypeDC") . ' : <div class="inline-block amount width100">' . price($debitcard) . '</div>';
	print "<br>";
	print $langs->trans("PaymentTypeCB") . ' : <div class="inline-block amount width100">' . price($creditcard) . '</div>';
	print "<br>";
	print $langs->trans("PaymentTypeVIR") . ' : <div class="inline-block amount width100">' . price($transfer) . '</div>';
	print "<br>";
	print $langs->trans("Total") . ' : <div class="inline-block amount width100">' . price($cash + $debitcard + $creditcard + $transfer) . '</div>';

	print '</h2>';
	print '</div>';

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();

$db->close();