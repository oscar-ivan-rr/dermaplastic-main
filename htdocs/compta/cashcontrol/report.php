<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\brief      List of bank transactions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


$langs->load("bills");

$id = GETPOST('id', 'int');

$_GET['optioncss'] = "print";
$cashcontrol = new CashControl($db);
$cashcontrol->fetch($id);
$action = GETPOST('action', 'alpha');
if ($action == 'print') {
	print '<script>window.print();</script>';
}

$posmodule = $cashcontrol->posmodule;
$terminalid = $cashcontrol->posnumber;

/*
 * View
 */

llxHeader('', 'Corte de caja');


$almacenid = $conf->global->{"CASHDESK_ID_WAREHOUSE" . $terminalid};
$warehouse = new Entrepot($db);
$warehouse->fetch($almacenid);
$sql = "SELECT code, label FROM " . MAIN_DB_PREFIX . "c_rfc WHERE rowid = " . $warehouse->fk_rfc;
$resql = $db->query($sql);
$data = $db->fetch_object($resql);
print "<center>";
print '<h3>' . $data->label . '</h3>';
//Obtenemos los datos del almacen; telefono, rfc y direccion
print 'Direccion: ' . $warehouse->address;
print '<br>' . $warehouse->description;
print '<br>RFC: ' . $data->code;
print "<br><br>Corte de caja No. " . $cashcontrol->id;
print "<br>Apertura: " . dol_print_date($cashcontrol->date_creation, 'dayhour');
if ($cashcontrol->date_valid > $cashcontrol->date_creation) {
	print "<br>Cierre: " . dol_print_date($cashcontrol->date_valid, 'dayhour') . "</p></center>";
}

$sql = "SELECT u.firstname, u.lastname";
$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
$sql .= " WHERE u.rowid = " . $cashcontrol->fk_user_creat;
$resql = $db->fetch_object($db->query($sql));
$nombre = $resql->firstname . " " . $resql->lastname;

print "<br>Cajera/o: " . $nombre;
print "<br>Terminal: " . $conf->global->{"CASHDESK_NAME" . $cashcontrol->posnumber};

print '<div class="div-table-responsive">';
$sql = "SELECT f.rowid as facid, f.ref, pf.amount as amount, b.fk_account as bankid, cp.code";
$sql .= " FROM " . MAIN_DB_PREFIX . "paiement_facture as pf";
$sql .= " JOIN " . MAIN_DB_PREFIX . "facture as f ON pf.fk_facture = f.rowid";
$sql .= " JOIN " . MAIN_DB_PREFIX . "paiement as p ON p.rowid = pf.fk_paiement";
$sql .= " JOIN " . MAIN_DB_PREFIX . "c_paiement as cp ON cp.id = p.fk_paiement";
$sql .= " JOIN " . MAIN_DB_PREFIX . "bank as b ON p.fk_bank = b.rowid";
$sql .= " WHERE f.module_source = '" . $db->escape($posmodule) . "'";
$sql .= " AND f.pos_source IN (SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') FROM " . MAIN_DB_PREFIX . "const lc";
$sql .= " JOIN " . MAIN_DB_PREFIX . "user lu ON lc.value = lu.fk_warehouse";
$sql .= " WHERE lu.login = '" . $_SESSION["dol_login"] . "' AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%')";
$sql .= " AND f.paye = 1";
$sql .= " AND p.entity IN (" . getEntity('facture') . ")";
$sql .= " AND p.datep BETWEEN '" . $db->idate($cashcontrol->date_creation) . "' AND '" . $db->idate($cashcontrol->date_valid) . "'";

$resql = $db->query($sql);
//Sumamos el total de cada venta
$num = $db->num_rows($resql);
$totalpertype = array('cash' => 0, 'debitcard' => 0, 'creditcard' => 0, 'transfer' => 0);
$amountpertype = array('cash' => 0, 'debitcard' => 0, 'creditcard' => 0, 'transfer' => 0);
$i = 0;
while ($i < $num) {
	$objp = $db->fetch_object($resql);
	if ($objp->code == 'LIQ') {
		//Sumamos el total de ventas en efectivo
		$totalpertype['cash'] += $objp->amount;
		$amountpertype['cash']++;
	} elseif ($objp->code == 'CB') {
		//Sumamos el total de ventas con tarjeta de credito
		$totalpertype['creditcard'] += $objp->amount;
		$amountpertype['creditcard']++;
	} elseif ($objp->code == 'TD/C') {
		//Sumamos el total de ventas con tarjeta de debito
		$totalpertype['debitcard'] += $objp->amount;
		$amountpertype['debitcard']++;
	} elseif ($objp->code == 'VIR') {
		//Sumamos el total de ventas con transferencia
		$totalpertype['transfer'] += $objp->amount;
		$amountpertype['transfer']++;
	}
	$i++;
}
$movimientos = $cashcontrol->intotal + $cashcontrol->outtotal;
$cash = $totalpertype['cash'] + $movimientos;
$debitcard = $totalpertype['debitcard'];
$creditcard = $totalpertype['creditcard'];
$transfer = $totalpertype['transfer'];
$initialbalanceforterminal = array();
$theoricalamountforterminal = array();
$theoricalnbofinvoiceforterminal = array();
$arrayofpaymentmode = array(
	'cash' => 'Cash',
	'debitcard' => 'Tarjeta de debito',
	'creditcard' => 'CreditCard',
	'transfer' => 'Transferencia'
);

$account = "CASHDESK_ID_BANKACCOUNT_CASH" . $cashcontrol->posnumber;

$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "bank_categ WHERE label LIKE '%especial%'";
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$category_transaction = $obj->rowid;
}

$baseSql = "SELECT b.rowid, b.datec as dc, b.amount, b.label, b.fk_account, b.fk_type, ba.rowid as bankid, ba.ref as bankref,";
$baseSql .= " bb.balance, bb.balance_antes FROM " . MAIN_DB_PREFIX . "bank_class as l, " . MAIN_DB_PREFIX . "bank_account as ba, " . MAIN_DB_PREFIX . "bank as b";
$baseSql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_balance as bb ON bb.fk_bank = b.rowid WHERE b.fk_account = ba.rowid AND ba.entity IN (1)";
$baseSql .= " AND b.rowid = l.lineid AND l.fk_categ = $category_transaction AND ba.rowid = " . $conf->global->$account;
$baseSql .= " AND b.datec BETWEEN '" . $db->idate($cashcontrol->date_creation) . "' AND '" . $db->idate($cashcontrol->date_valid) . "'";
$baseSql .= " ORDER BY b.datec DESC";

$sqlin = str_replace("AND ba.rowid", "AND b.amount > 0 AND ba.rowid", $baseSql);
$resin = $db->query($sqlin);

$sqlout = str_replace("AND ba.rowid", "AND b.amount < 0 AND ba.rowid", $baseSql);
$resout = $db->query($sqlout);

//Sumamos el total teorico de cada tipo de pago
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
		dol_print_error($db, 'Value for key = ' . $key . ' not supported');
		exit;
	}
	$sql .= " AND p.datec BETWEEN '" . $db->idate($cashcontrol->date_creation) . "' AND '" . $db->idate($cashcontrol->date_valid) . "'";

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

print "<h3 style='text-align:center'> ========Efectivo======== </h3>";
print "<div style='text-align: right'><p>";
print "<br>Fondo de caja: " . price($cashcontrol->opening);
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
	print "<p style='text-align: right'>";
	print "<br>Total entradas y salidas: " . price($movimientos);
}
print "<p style='text-align: left'>Ventas en efectivo: " . $amountpertype['cash'];
print "<p style='text-align: right'>";
print "<br>Ventas: " . price($theoricalamountforterminal[$terminalid]['cash']);
print "<br>Total efectivo teórico: " . price($theoricalamountforterminal[$terminalid]['cash'] + $movimientos);
print "<br>Cantidad Real: " . price($cashcontrol->cash - $cashcontrol->opening);
$difcash = (($theoricalamountforterminal[$terminalid]['cash'] + $movimientos) - ($cashcontrol->cash - $cashcontrol->opening)) * -1;
$difcash == 0 ? print "<br><br>" : ($difcash > 0 ? print '<br class="amountremaintopay">Sobrante: ' . price($difcash) . '</span>' : print '<br class="amountremaintopay">Faltante: ' . price($difcash) . '</span>');

print "</div>";

print "<h3 style='text-align:center'> ========Tarjeta======== </h3>";
$numtarjeta = $amountpertype['debitcard'] + $amountpertype['creditcard'];
print "<p style='text-align: left'>Ventas con tarjeta: " . $numtarjeta;
print "<div style='text-align: right'><p>";
print "Tarjeta débito teórico: " . price($theoricalamountforterminal[$terminalid]['debitcard']);
print "<br>Cantidad Real: " . price($cashcontrol->debitcard);
$diftdc = ($theoricalamountforterminal[$terminalid]['debitcard'] - $cashcontrol->debitcard) * -1;
$diftdc == 0 ? print "<br><br>" : ($diftdc > 0 ? print '<br class="amountremaintopay">Sobrante: ' . price($diftdc) . '</span>' : print '<br class="amountremaintopay">Faltante: ' . price($diftdc) . '</span>');
print "<br>_______________________________<br>";
print "<br>Tarjeta crédito teórico: " . price($theoricalamountforterminal[$terminalid]['creditcard']);
print "<br>Cantidad Real: " . price($cashcontrol->creditcard);
$difcard = ($theoricalamountforterminal[$terminalid]['creditcard'] - $cashcontrol->creditcard) * -1;
$difcard == 0 ? print "<br><br>" : ($difcard > 0 ? print '<br class="amountremaintopay">Sobrante: ' . price($difcard) . '</span>' : print '<br class="amountremaintopay">Faltante: ' . price($difcard) . '</span>');
print "<br><br>";
print "</div>";

print "<h3 style='text-align:center'> ========Transferencia======== </h3>";
print "<p style='text-align: left'>Ventas con transferencia: " . $amountpertype['transfer'];
print "<div style='text-align: right'><p>";
print "Transferencia teórico: " . price($theoricalamountforterminal[$terminalid]['transfer']);
print "<br>Cantidad Real: " . price($cashcontrol->transfer);
$diftrans = ($theoricalamountforterminal[$terminalid]['transfer'] - $cashcontrol->transfer) * -1;
$diftrans == 0 ? print "<br><br>" : ($diftrans > 0 ? print '<br class="amountremaintopay">Sobrante: ' . price($diftrans) . '</span>' : print '<br class="amountremaintopay">Faltante: ' . price($diftrans) . '</span>');
print "</div>";

print "<h3 style='text-align:center'> ======Total del día====== </h3>";
print "<div style='text-align: right'><p>";
$theoricaltotal = $theoricalamountforterminal[$terminalid]['cash'] + $theoricalamountforterminal[$terminalid]['debitcard'] + $theoricalamountforterminal[$terminalid]['creditcard'] + $theoricalamountforterminal[$terminalid]['transfer'] + $movimientos;
$realtotal = $cashcontrol->cash + $cashcontrol->debitcard + $cashcontrol->creditcard + $cashcontrol->transfer - $cashcontrol->opening;
print '<br>Total teorico: ' . price($theoricaltotal) . '<br>';
print '<br>Total real: ' . price($realtotal) . '<br>';
$diftotal = ($theoricaltotal - $realtotal) * -1;
$diftotal == 0 ? print "<br><br>" : ($diftotal > 0 ? print '<br class="amountremaintopay">Sobrante: ' . price($diftotal) . '</span>' : print '<br class="amountremaintopay">Faltante: ' . price($diftotal) . '</span>');
print "</p></div>";

llxFooter();

$db->close();
