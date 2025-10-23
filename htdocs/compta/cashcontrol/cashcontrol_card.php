<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra		<jove@bisquerra.com>
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
 *      \file       htdocs/compta/cashcontrol/cashcontrol_card.php
 *      \ingroup    cashdesk|takepos
 *      \brief      Page to show a cash fence
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->loadLangs(array("install", "cashdesk", "admin", "banks"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$categid = GETPOST('categid');
$label = GETPOST("label");

$now = dol_now('tzuser');
$syear = (GETPOSTISSET('closeyear') ?GETPOST('closeyear', 'int') : dol_print_date($now, "%Y"));
$smonth = (GETPOSTISSET('closemonth') ?GETPOST('closemonth', 'int') : dol_print_date($now, "%m"));
$sday = (GETPOSTISSET('closeday') ?GETPOST('closeday', 'int') : dol_print_date($now, "%d"));

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {$page = 0;}// If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {$sortfield = 'rowid';}
if (!$sortorder) {$sortorder = 'ASC';}

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'thirdpartylist';

if ($contextpage == 'poslist') {
	$_GET['optioncss'] = 'print';
}

$arrayofpaymentmode = array(
	'cash' => 'Cash',
	'debitcard' => 'Tarjeta de debito',
	'creditcard' => 'CreditCard',
	'transfer' => 'Transferencia'
);

$arrayofposavailable = array();

if (!empty($conf->takepos->enabled)) {
	$arrayofposavailable['takepos'] = $langs->trans('TakePOS').' (takepos)';
}
// TODO Add hook here to allow other POS to add themself

$object = new CashControl($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('cashcontrolcard', 'globalcard'));

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check
if ($user->socid > 0) {	// Protection if external user
	accessforbidden();
}
if (!$user->rights->cashdesk->run && !$user->rights->takepos->run) {
	accessforbidden();
}


/*
 * Actions
 */

$permissiontoadd = ($user->rights->cashdesk->run || $user->rights->takepos->run);
$permissiontodelete = ($user->rights->cashdesk->run || $user->rights->takepos->run) || ($permissiontoadd && $object->status == 0);
if (empty($backtopage)) {
	$backtopage = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.($id > 0 ? $id : '__ID__');
}
$backurlforlist = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_list.php';
$triggermodname = 'CACHCONTROL_MODIFY'; // Name of trigger action code to execute when we modify record

if (empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH) && empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH1)) {
	setEventMessages($langs->trans("CashDesk")." - ".$langs->trans("NotConfigured"), null, 'errors');
}


if (GETPOST('cancel', 'alpha')) {
	if ($action == 'valid') {
		$action = 'view';
	} elseif ($action == 'banktransfer'){
		$action = 'view';
		if ($contextpage == 'poslist'){
			print "
			<script>
			// Se abre el ticket para imprimirlo
			var url = '".dol_buildpath('/compta/cashcontrol/report.php', 1)."?id=".$id."&action=print';
			var w = window.open(url, 'cashcontrol', 'toolbar=no, menubar=no, scrollbars=yes, resizable=yes, width=800, height=600');
			w.focus();
			parent.window.location.reload();
			</script>";
			exit;
		}
	} else {
		$action = 'create';
	}
}

if ($action == "reopen") {
	$result = $object->setStatut($object::STATUS_DRAFT, null, '', 'CASHFENCE_REOPEN');
	if ($result < 0) {
		setEventMessages($object->error, $object->error, 'errors');
	}

	$action = 'view';
}

if ($action == "start") {
	$error = 0;
	if (!GETPOST('posmodule', 'alpha') || GETPOST('posmodule', 'alpha') == '-1') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Module")), null, 'errors');
		$action = 'create';
		$error++;
	}
	if (GETPOST('posnumber', 'alpha') == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CashDesk")), null, 'errors');
		$action = 'create';
		$error++;
	}
	if (!GETPOST('closeyear', 'alpha') || GETPOST('closeyear', 'alpha') == '-1') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Year")), null, 'errors');
		$action = 'create';
		$error++;
	}
} elseif ($action == "add") {
	if (GETPOST('opening', 'alpha') == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InitialBankBalance")), null, 'errors');
		$action = 'start';
		$error++;
	}
	$error = 0;
	foreach ($arrayofpaymentmode as $key => $val) {
		$object->$key = price2num(GETPOST($key.'_amount', 'alpha'));
	}

	if (!$error) {
		$object->day_close = GETPOST('closeday', 'int');
		$object->month_close = GETPOST('closemonth', 'int');
		$object->year_close = GETPOST('closeyear', 'int');

		$object->opening = price2num(GETPOST('opening', 'alpha'));
		$object->posmodule = GETPOST('posmodule', 'alpha');
		$object->posnumber = GETPOST('posnumber', 'alpha');

		$db->begin();

		$id = $object->create($user);

		if ($id > 0) {
			$db->commit();
			$action = "view";
		} else {
			$db->rollback;
			$action = "view";
		}
	}
	if ($contextpage == 'poslist') {
		print "
		<script>
			parent.window.location.reload();
		</script>";
		exit;
	}
}

if ($action == "valid") {	// validate = close
	$object->fetch($id);

	$db->begin();

	$tempcash = floatval(GETPOST('cash_amount', 'alpha')) + $object->opening;
	$object->cash = price2num($tempcash);
	$object->creditcard = price2num(GETPOST('creditcard_amount', 'alpha'));
	$object->debitcard = price2num(GETPOST('debitcard_amount', 'alpha'));
	$object->transfer = price2num(GETPOST('transfer_amount', 'alpha'));
	$object->intotal = price2num(GETPOST('in_amount', 'alpha'));
	$object->outtotal = price2num(GETPOST('out_amount', 'alpha'));

	$result = $object->update($user);

	$result = $object->valid($user);

	if ($result <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	} else {
		setEventMessages($langs->trans("CashFenceDone"), null);
		$db->commit();
	}

	$action = "transfer";
}

// Action to delete
if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
	$object->fetch($id);

	if (!($object->id > 0)) {
		dol_print_error('', 'Error, object must be fetched before being deleted');
		exit;
	}

	$result = $object->delete($user);
	if ($result > 0) {
		// Delete OK
		setEventMessages("RecordDeleted", null, 'mesgs');
		header("Location: ".$backurlforlist);
		exit;
	} else {
		if (!empty($object->errors)) {
			setEventMessages(null, $object->errors, 'errors');
		} else {
			setEventMessages($object->error, null, 'errors');
		}
	}
}

if ($action == 'banktransfer'){

	$bankid = GETPOST('bankid', 'int');
	$banktransferid = GETPOST('banktransferid', 'int');
	$amount = GETPOST('amount', 'alpha');
	$dateo = dol_now('tzuser');

	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

	$accountfrom=new Account($db);
	$accountfrom->fetch($bankid);
	
	//Validacion de saldo
	$sql = 'SELECT SUM(amount) as balance from '.MAIN_DB_PREFIX.'bank WHERE fk_account='.$bankid;
	$resql = $db->query($sql);
	if($resql){
		$balance = $db->fetch_object($resql);
		$balance = $balance->balance;
		if($amount > $balance){
			$error++;
			$mesgs = 'El balance de la cuenta '.$accountfrom->label.' ($'.price($balance).') es menor al monto a envíar';
			setEventMessages($mesgs, null, 'errors');
		}
	}

	$accountto=new Account($db);
	$accountto->fetch($banktransferid);

	if ($accountto->currency_code == $accountfrom->currency_code)
	{
		$amountto=$amount;
	}
	else
	{
		if (! $amountto)
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AmountTo")), null, 'errors');
		}
	}

	if (($accountto->id != $accountfrom->id) && empty($error))
	{
		$db->begin();

		$bank_line_id_from=0;
		$bank_line_id_to=0;
		$result=0;

		$typefrom='LIQ';
		$typeto='LIQ';

		if (! $error) $bank_line_id_from = $accountfrom->addline($dateo, $typefrom, 'Egreso por Corte de caja', -1*price2num($amount), '', '', $user);
		if (! ($bank_line_id_from > 0)) $error++;
		if (! $error) $bank_line_id_to = $accountto->addline($dateo, $typeto, 'Ingreso por Corte de caja', price2num($amountto), '', '', $user);
		if (! ($bank_line_id_to > 0)) $error++;

		if (! $error) $result=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
		if (! ($result > 0)) $error++;
		if (! $error) $result=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
		if (! ($result > 0)) $error++;

		if (! $error)
		{
			$mesgs = $langs->trans("TransferFromToDone", '<a href="bankentries_list.php?id='.$accountfrom->id.'&sortfield=b.datev,b.dateo,b.rowid&sortorder=desc">'.$accountfrom->label."</a>", '<a href="bankentries_list.php?id='.$accountto->id.'">'.$accountto->label."</a>", $amount, $langs->transnoentities("Currency".$conf->currency));
			// setEventMessages($mesgs, null, 'mesgs');
			$db->commit();
		}
		else
		{
			setEventMessages($accountfrom->error.' '.$accountto->error, null, 'errors');
			$db->rollback();
		}
	}
	else
	{
		$error++;
		setEventMessages($langs->trans("ErrorFromToAccountsMustDiffers"), null, 'errors');
	}

	if ($contextpage == 'poslist') {
		print "
		<script>
			// Se abre el ticket para imprimirlo
			var url = '".dol_buildpath('/compta/cashcontrol/report.php', 1)."?id=".$id."&action=print';
			var w = window.open(url, 'cashcontrol', 'toolbar=no, menubar=no, scrollbars=yes, resizable=yes, width=800, height=600');
			w.focus();
			parent.window.location.reload();
		</script>";
		exit;
	}

	$action = 'view';
}


/*
 * View
 */

$form = new Form($db);

$initialbalanceforterminal = array();
$theoricalamountforterminal = array();
$theoricalnbofinvoiceforterminal = array();
$inamount=$outamount=0;

if ($action == "create" || $action == "start" || $action == 'close') {
	if ($action == 'close') {
		$posmodule = $object->posmodule;
		$terminalid = $object->posnumber;
		$terminaltouse = $terminalid;

		$syear = $object->year_close;
		$smonth = $object->month_close;
		$sday = $object->day_close;
	} elseif (GETPOST('posnumber', 'alpha') != '' && GETPOST('posnumber', 'alpha') != '' && GETPOST('posnumber', 'alpha') != '-1') {
		$posmodule = GETPOST('posmodule', 'alpha');
		$terminalid = GETPOST('posnumber', 'alpha');
		$terminaltouse = $terminalid;

		if ($terminaltouse == '1' && $posmodule == 'cashdesk') {
			$terminaltouse = '';
		}

		if ($posmodule == 'cashdesk' && $terminaltouse != '' && $terminaltouse != '1') {
			$terminaltouse = '';
			setEventMessages($langs->trans("OnlyTerminal1IsAvailableForCashDeskModule"), null, 'errors');
			$error++;
		}
	}

	if ($terminalid != '') {
		// Calculate $initialbalanceforterminal for terminal 0
		foreach ($arrayofpaymentmode as $key => $val) {
			if ($key != 'cash') {
				$initialbalanceforterminal[$terminalid][$key] = 0;
				continue;
			}

			// Get the bank account dedicated to this point of sale module/terminal
			$vartouse = 'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse;
			$bankid = $conf->global->$vartouse;

			if ($bankid > 0) {
				$sql = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."bank";
				$sql .= " WHERE fk_account = ".((int) $bankid);
				$sql .= " AND dateo < '".$db->idate(!empty($object->date_valid) ? $object->date_valid : dol_now('tzuser'))."'";

				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$initialbalanceforterminal[$terminalid][$key] = $obj->total;
					}
				} else {
					dol_print_error($db);
				}
			} else {
				setEventMessages($langs->trans("SetupOfTerminalNotComplete", $terminaltouse), null, 'errors');
				$error++;
			}
		}

		$sqlin = "SELECT SUM(lb.amount) as total, COUNT(*) as nb";
		$sqlin .= " FROM ".MAIN_DB_PREFIX."bank lb";
		$sqlin .= " WHERE lb.amount > 0 AND lb.fk_account = ".((int) $bankid);
		$sqlin .= " AND lb.rowid IN (SELECT lpv.fk_bank FROM ".MAIN_DB_PREFIX."payment_various as lpv WHERE lpv.datec BETWEEN '".$db->idate($object->date_creation)."' AND '".$db->idate(!empty($object->date_valid) ? $object->date_valid : dol_now('tzuser'))."')";
		$resqlin = $db->fetch_object($db->query($sqlin));
		$inamount += $resqlin->total;

		$sqlout = "SELECT SUM(lb.amount) as total, COUNT(*) as nb";
		$sqlout .= " FROM ".MAIN_DB_PREFIX."bank lb";
		$sqlout .= " WHERE lb.amount < 0 AND lb.fk_account = ".((int) $bankid);
		$sqlout .= " AND lb.rowid IN (SELECT lpv.fk_bank FROM ".MAIN_DB_PREFIX."payment_various as lpv WHERE lpv.datec BETWEEN '".$db->idate($object->date_creation)."' AND '".$db->idate(!empty($object->date_valid) ? $object->date_valid : dol_now('tzuser'))."')";
		$resqlout = $db->fetch_object($db->query($sqlout));
		$outamount += $resqlout->total;

		// Calculate $theoricalamountforterminal
		foreach ($arrayofpaymentmode as $key => $val) {
			$sql = "SELECT SUM(pf.amount) as total, COUNT(*) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as cp";
			$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement";
			$sql .= " AND f.module_source = '".$db->escape($posmodule)."'";
			$sql .= " AND f.pos_source IN (SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') FROM ".MAIN_DB_PREFIX."const lc, ".MAIN_DB_PREFIX."user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '".$_SESSION["dol_login"]."' AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%')";
			$sql .= " AND f.paye = 1";
			$sql .= " AND p.entity IN (".getEntity('facture').")";
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
			$sql .= " AND p.datec BETWEEN '".$db->idate($object->date_creation)."' AND '".$db->idate(!empty($object->date_valid) ? $object->date_valid : dol_now('tzuser'))."'";

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
	}

	if ($action != 'close') {
		llxHeader('', $langs->trans("NewCashFence"));

		print load_fiche_titre($langs->trans("CashControl")." - ".$langs->trans("New"), '', 'cash-register');

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		if ($contextpage == 'poslist') {
			print '<input type="hidden" name="contextpage" value="poslist">';
			print '<input type="hidden" id="posmodule" name="posmodule" value="takepos"></td>';
			print '<input type="hidden" id="posnumber" name="posnumber" value="'.GETPOST('posnumber').'"></td>';
			print '<input type="hidden" id="closeyear" name="closeyear" value=""></td>';
		}
		if ($action == 'start' && GETPOST('posnumber', 'int') != '' && GETPOST('posnumber', 'int') != '' && GETPOST('posnumber', 'int') != '-1') {
			print '<input type="hidden" name="action" value="add">';
		} elseif ($action == 'close') {
			print '<input type="hidden" name="action" value="valid">';
			print '<input type="hidden" name="id" value="'.$id.'">';
		} else {
			print '<input type="hidden" name="action" value="start">';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		if ($contextpage != 'poslist'){
			print '<td>'.$langs->trans("Module").'</td>';
		}
		print '<td>'.$langs->trans("Terminal").'</td>';
		print '<td>'.$langs->trans("Year").'</td>';
		print '<td>'.$langs->trans("Month").'</td>';
		print '<td>'.$langs->trans("Day").'</td>';
		print '<td></td>';
		print "</tr>\n";

		$disabled = 0;
		$prefix = 'close';

		print '<tr class="oddeven">';
		if ($contextpage != 'poslist'){
			print '<td>'.$form->selectarray('posmodule', $arrayofposavailable, GETPOST('posmodule', 'alpha'), (count($arrayofposavailable) > 1 ? 1 : 0)).'</td>';
		}
		print '<td>';
			
		$array = array();
		$numterminals = max(1, $conf->global->TAKEPOS_NUM_TERMINALS);
		for ($i = 1; $i <= $numterminals; $i++) {
			$array[$i] = $i;
		}
		$selectedposnumber = 0;
		$showempty = 1;
		if ($conf->global->TAKEPOS_NUM_TERMINALS == '1') {
			$selectedposnumber = 1;
			$showempty = 0;
		}
		print $form->selectarray('posnumber', $array, GETPOSTISSET('posnumber') ?GETPOST('posnumber', 'int') : $selectedposnumber, $showempty,0,0,0,0,0,1);
		print '</td>';
		$sday = ltrim($sday,'0');
		$nextdate = dol_get_next_day($sday, $smonth, $syear);
		$nextnext = dol_get_next_day($nextdate['day'], $nextdate['month'], $nextdate['year']);
		// Year
		print '<td>';
		$retstring = '<select disabled class="flat valignmiddle maxwidth75imp" id="'.$prefix.'year_select" name="'.$prefix.'year">';
		for ($year = $syear - 10; $year < $syear + 10; $year++) {
			$retstring .= '<option value="'.$year.'"'.($year == $syear ? ' selected' : '').'>'.$year.'</option>';
		}
		$retstring .= "</select>\n";
		print $retstring;
		print '</td>';
		// Month
		print '<td>';
		$retstring = '<select'.($disabled ? ' disabled' : '').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'month" name="'.$prefix.'month">';
		$retstring .= '<option value="0"></option>';
		if ($nextdate['month']!= $smonth) {
			for ($month = $smonth; $month <= $smonth+1 ; $month++) {
				$retstring .= '<option value="'.$month.'"'.($month == $smonth ? ' selected' : '').'>';
				$retstring .= dol_print_date(mktime(0, 0, 0, $month, 1, 2000), "%B");
				$retstring .= "</option>";
			}
		}
		else{
			for ($month = $smonth; $month <= $smonth ; $month++) {
			$retstring .= '<option value="'.$month.'"'.($month == $smonth ? ' selected' : '').'>';
			$retstring .= dol_print_date(mktime(0, 0, 0, $month, 1, 2000), "%B");
			$retstring .= "</option>";
			}
		}
		$retstring .= "</select>";
		print $retstring;
		print '</td>';
		// Day
		print '<td>';
		$retstring = '<select'.($disabled ? ' disabled' : '').' class="flat valignmiddle maxwidth50imp" id="'.$prefix.'day" name="'.$prefix.'day">';
		$retstring .= '<option value="0" selected>&nbsp;</option>';
		$retstring .= '<option value="'.$sday.'" selected>'.$sday.'</option>';
		$retstring .= '<option value="'.$nextdate['day'].'">'.$nextdate['day'].'</option>';
		$retstring .= '<option value="'.$nextnext['day'].'">'.$nextnext['day'].'</option>';
		$retstring .= "</select>";
		print $retstring;
		print '</td>';
		print '<script>
		$(document).ready(function() {
			$("#closeyear").val($("#closeyear_select").val());
		});
		</script>';
		// Button Start
		print '<td>';
		if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1') {
			print '';
		} else {
			print '<input type="submit" name="add" class="button" value="'.$langs->trans("Start").'">';
		}
		print '</td>';
		print '</table>';
		print '</div>';

		// Table to see/enter balance
		if (($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1') || $action == 'close') {
			$posmodule = GETPOST('posmodule', 'alpha');
			$terminalid = GETPOST('posnumber', 'alpha');

			print '<br>';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td class="center">'.$langs->trans("InitialBankBalance");
			print '</td>';
			print '<td></td>';
			print '</tr>';

			print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td class="center">'.$langs->trans("Cash");
			print '</td>';
			print '<td></td>';
			print '</tr>';
			print '<tr>';
			// Initial amount
			print '<td>'.$langs->trans("TheoricalAmount").'</td>';
			print '<td class="center">';
			if ($action == 'close') {
				print price($theoricalamountforterminal[$terminalid]['cash']);
			} else {
				print price($initialbalanceforterminal[$terminalid]['cash']);
			}
			print '</td>';
			// Save
			print '<td></td>';
			print '</tr>';
			print '<tr>';
			print '<td>'.$langs->trans("RealAmount").'</td>';
			// Initial amount
			print '<td class="center">';
			print '<input ';
			if ($action == 'close') {
				print 'disabled '; // To close cash user can't set opening cash
			}
			print 'name="opening" type="text" class="maxwidth100 center" value="';
			if ($action == 'close') {
				$object->fetch($id);
				print $object->opening;
			} else {
				print (GETPOSTISSET('opening') ?price2num(GETPOST('opening', 'alpha')) : price($initialbalanceforterminal[$terminalid]['cash']));
			}
			print '">';
			print '</td>';
			// Save
			print '<td class="center">';
			print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
			if ($action == 'start') {
				print '<input type="submit" name="add" id="addopen" class="button button-save" value="'.$langs->trans("Save").'">';
			} elseif ($action == 'close') {
				print '<input type="submit" name="valid" class="button" value="'.$langs->trans("Validate").'">';
			}
			print '</td>';
			print '</tr>';

			print '</table>';
			print '</div>';
		}

		print '</form>';
		print '<script>
		$(document).ready(function() {
			$("input[type=submit][name=add]").click(function(e) {
				e.preventDefault(); // prevent the default click action
				$(this).prop("disabled", true); // disable the button immediately
				$(this).closest("form").submit(); // submit the form
			});
		});
		</script>';
	}
}

if (empty($action) || $action == "view" || $action == "close" || $action == 'transfer') {
	$result = $object->fetch($id);

	llxHeader('', $langs->trans("CashControl"));

	if ($result <= 0) {
		print $langs->trans("ErrorRecordNotFound");
	} else {
		$head = array();
		$head[0][0] = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.$object->id;
		$head[0][1] = $langs->trans("CashControl");
		$head[0][2] = 'cashcontrol';

		print dol_get_fiche_head($head, 'cashcontrol', $langs->trans("CashControl"), -1, 'account');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= '</div>';


		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		print '<tr><td class="titlefield nowrap">';
		print $langs->trans("Ref");
		print '</td><td>';
		print $id;
		print '</td></tr>';

		print '<tr><td valign="middle">'.$langs->trans("Module").'</td><td>';
		print $object->posmodule;
		print "</td></tr>";

		print '<tr><td valign="middle">'.$langs->trans("Terminal").'</td><td>';
		print $object->posnumber;
		print "</td></tr>";

		print '<tr><td class="nowrap">';
		print $langs->trans("Period");
		print '</td><td>';
		print 
		print dol_print_date($object->date_creation, 'dayhour');
		$object->date_valid ? print ' - '.dol_print_date($object->date_valid, 'dayhour') : '';
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		print '<tr><td class="titlefield nowrap">';
		print $langs->trans("DateCreationShort");
		print '</td><td>';
		print dol_print_date($object->date_creation, 'dayhour');
		print '</td></tr>';

		print '<tr><td valign="middle">'.$langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").'</td><td>';
		print price($object->opening, 0, $langs, 1, -1, -1, $conf->currency);
		print "</td></tr>";
		foreach ($arrayofpaymentmode as $key => $val) {
			print '<tr><td valign="middle">'.$langs->trans($val).'</td><td>';
			if ($key == 'cash'){
				print price($object->$key-$object->opening, 0, $langs, 1, -1, -1, $conf->currency);
			}else{
				print price($object->$key, 0, $langs, 1, -1, -1, $conf->currency);
			}
			print "</td></tr>";
		}

		print "</table>\n";

		print '</div></div>';
		print '<div style="clear:both"></div>';

		print dol_get_fiche_end();

		if ($action != 'close') {
			print '<div class="tabsAction">';

			if ($object->status == CashControl::STATUS_DRAFT) {
				print '<div class="inline-block divButAction"><a target="_blank" rel="noopener noreferrer" class="butAction" href="arqueo.php?id='.((int) $id).'">'.$langs->trans('Arqueo').'</a></div>';
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).'&action=close&token='.newToken().'&contextpage='.$contextpage.'">'.$langs->trans('Close').'</a></div>';
				// print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).'&action=confirm_delete&token='.newToken().'">'.$langs->trans('Delete').'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a target="_blank" rel="noopener noreferrer" class="butAction" href="report.php?id='.((int) $id).'">'.$langs->trans('PrintTicket').'</a></div>';
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).'&action=reopen&token='.newToken().'">'.$langs->trans('Edit').'</a></div>';
			}

			print '</div>';
			if ($action == 'transfer'){
				print '<div class="div-table-responsive-no-min">';
				print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="banktransfer">';
				print '<input type="hidden" name="id" value="'.$id.'">';
				if ($contextpage == 'poslist') {
					print '<input type="hidden" name="contextpage" value="poslist">';
				}
				
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<td>Cuenta origen</td>';
				print '<td>Cuenta destino</td>';
				print '<td>Total en cuenta</td>';
				print '<td>Importe</td>';
				print '</tr>';
				print '<tr>';
				print '<td>';
				$bankacc = "CASHDESK_ID_BANKACCOUNT_CASH".$object->posnumber;
				$bankid = $conf->global->$bankacc;
				print '<input type="hidden" name="bankid" value="'.$bankid.'">';
				$bank = new Account($db);
				$bank->fetch($bankid);
				print $bank->getNomUrl(1);
				print '</td>';
				$banktransferid = "CASHDESK_ID_BANKACCOUNT_FINALTRANSFER".$object->posnumber;
				$bkid = $conf->global->$banktransferid;
				print '<input type="hidden" name="banktransferid" value="'.$bkid.'">';
				$banktransfer = new Account($db);
				$banktransfer->fetch($bkid);

				print '<td>';
				print $banktransfer->getNomUrl(1);
				print '</td>';
				$sql = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."bank";
				$sql .= " WHERE fk_account = ".((int) $bankid);
				$resql = $db->query($sql);
				$obj = $db->fetch_object($resql);
				print '<td>';
				print price($obj->total);
				print '</td>';
				print '<td>';
				$totransfer = $obj->total-$bank->min_allowed;
				print '<input type="number" name="amount" step=".01" value="'.round($totransfer, 2).'">';
				print '</td>';
				print '</tr>';
				print '</table>';
				print '<div class="right">';
				print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
				print '<input type="submit" name="transfer" class="button" value="Aceptar">';
				print '</div>';
				print '</form>';
				print '</div>';
			}
		} else {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formclose">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			if ($contextpage == 'poslist') {
				print '<input type="hidden" name="contextpage" value="poslist">';
			}
			if ($action == 'start' && GETPOST('posnumber', 'int') != '' && GETPOST('posnumber', 'int') != '' && GETPOST('posnumber', 'int') != '-1') {
				print '<input type="hidden" name="action" value="add">';
			} elseif ($action == 'close') {
				print '<input type="hidden" name="action" value="valid">';
				print '<input type="hidden" name="id" value="'.$id.'">';
			} else {
				print '<input type="hidden" name="action" value="start">';
			}
			// Table to see/enter balance
			if (($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1') || $action == 'close') {
				$posmodule = $object->posmodule;
				$terminalid = $object->posnumber;

				print '<input type="hidden" name="in_amount" value="'.$inamount.'">';
				print '<input type="hidden" name="out_amount" value="'.$outamount.'">';

				print '<br>';
				
				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent">';

				print '<tr class="liste_titre">';
				print '<td class="center" colspan="2">'.$langs->trans("InitialBankBalance");
				print '</td>';

				print '<td align="center" class="hide0" colspan="5">';
				print $langs->trans("AmountAtEndOfPeriod");
				print '</td>';
				print '</tr>';

				print '<tr class="liste_titre">';
				print '<td class="center" colspan="2">'.$langs->trans("Cash");
				print '</td>';
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>'.$langs->trans($val);
					print '</td>';
					$i++;
				}
				print '<td></td>';
				print '</tr>';

				print '<tr>';
				// Initial amount
				print '<td colspan="2">'.$langs->trans("NbOfInvoices").'</td>';
				// Amount per payment type
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
					print $theoricalnbofinvoiceforterminal[$terminalid][$key];
					print '</td>';
					$i++;
				}
				// Save
				print '<td align="center"></td>';
				print '</tr>';

				print '<tr>';
				// Initial amount
				print '<td>'.$langs->trans("TheoricalAmount").'</td>';
				print '<td class="center">';
				print price($object->opening).'<br>';
				print '</td>';
				// Amount per payment type
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
					if ($key == 'cash') {
						$inout = $resqlin->total + $resqlout->total;
						$deltaforcash = ($object->opening - $initialbalanceforterminal[$terminalid]['cash']);
						print price($theoricalamountforterminal[$terminalid][$key] + $deltaforcash + $inout - $object->opening).'<br>';
					} else {
						print price($theoricalamountforterminal[$terminalid][$key]).'<br>';
					}
					print '</td>';
					$i++;
				}
				// Save
				print '<td align="center"></td>';
				print '</tr>';

				print '<tr>';
				print '<td>'.$langs->trans("RealAmount").'</td>';
				// Initial amount
				print '<td class="center">';
				print '<input ';
				if ($action == 'close') {
					print 'disabled '; // To close cash user can't set opening cash
				}
				print 'name="opening" type="text" class="maxwidth100 center" value="';
				if ($action == 'close') {
					$object->fetch($id);
					print $object->opening;
				} else {
					print (GETPOSTISSET('opening') ?price2num(GETPOST('opening', 'alpha')) : price($initialbalanceforterminal[$terminalid]['cash']));
				}
				print '">';
				print '</td>';
				// Amount per payment type
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
					print '<input ';
					if ($action == 'start') {
						print 'disabled '; // To start cash user only can set opening cash
					}
					$amount = $key == 'cash' 
						? ($object->$key ? $object->$key + $deltaforcash + $inout - $object->opening 
						: $theoricalamountforterminal[$terminalid][$key] + $deltaforcash + $inout - $object->opening) 
						: ($object->$key ? $object->$key : $theoricalamountforterminal[$terminalid][$key]);
					print 'name="'.$key.'_amount" type="text"'.($key == 'cash' ? ' autofocus' : '').' class="maxwidth100 center" value="'.price2num($amount).'">';
					print '</td>';
					$i++;
				}
				// Save
				print '<td class="center">';
				print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
				if ($action == 'start') {
					print '<input type="submit" id="addclose" name="add" class="button button-save" value="'.$langs->trans("Save").'">';
				} elseif ($action == 'close') {
					print '<input type="submit" name="valid" class="button" value="'.$langs->trans("Close").'">';
				}
				print '</td>';
				print '</tr>';

				print '</table>';
				print '</div>';
			}

			print '</form>';
		}
	}
}

// End of page
llxFooter();
$db->close();
