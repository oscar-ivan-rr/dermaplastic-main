<?php
/**
 * Copyright (C) 2018    Andreu Bisquerra    <jove@bisquerra.com>
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
 *	\file       htdocs/takepos/invoice.php
 *	\ingroup    takepos
 *	\brief      Page to generate section with list of lines
 */

// if (! defined('NOREQUIREUSER'))    define('NOREQUIREUSER', '1');    // Not disabled cause need to load personalized language
// if (! defined('NOREQUIREDB'))        define('NOREQUIREDB', '1');        // Not disabled cause need to load personalized language
// if (! defined('NOREQUIRESOC'))        define('NOREQUIRESOC', '1');
// if (! defined('NOREQUIRETRAN'))        define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK')) { define('NOCSRFCHECK', '1'); }
if (!defined('NOTOKENRENEWAL')) { define('NOTOKENRENEWAL', '1'); }
if (!defined('NOREQUIREMENU')) { define('NOREQUIREMENU', '1'); }
if (!defined('NOREQUIREHTML')) { define('NOREQUIREHTML', '1'); }
if (!defined('NOREQUIREAJAX')) { define('NOREQUIREAJAX', '1'); }

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';

$langs->loadLangs(array("companies", "commercial", "bills", "cashdesk", "stocks"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$idproduct = GETPOST('idproduct', 'int');
$lotid = GETPOST('lotid', 'int');
$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Bar or Restaurant
$placeid = 0; // $placeid is ID of invoice
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'thirdpartylist';

if ($conf->global->TAKEPOS_PHONE_BASIC_LAYOUT == 1 && $conf->browser->layout == 'phone')
{
	// DIRECT LINK TO THIS PAGE FROM MOBILE AND NO TERMINAL SELECTED
	if ($_SESSION["takeposterminal"] == "")
	{
		if ($conf->global->TAKEPOS_NUM_TERMINALS == "1") $_SESSION["takeposterminal"] = 1;
		else
		{
			header("Location: takepos.php");
			exit;
		}
	}
	$mobilepage = GETPOST('mobilepage', 'alpha');
	$title = 'TakePOS - Dolibarr '.DOL_VERSION;
	if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = 'TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
	$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
	print '<link rel="stylesheet" href="css/pos.css">
	<link rel="stylesheet" href="css/colorbox.css" type="text/css" media="screen" />
	<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>';
}

/**
 * Abort invoice creationg with a given error message
 *
 * @param   string  $message        Message explaining the error to the user
 * @return	void
 */
function fail($message)
{
	header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
	die($message);
}

$number = GETPOST('number', 'alpha');
$idline = GETPOST('idline', 'int');
$desc = GETPOST('desc', 'alpha');
$pay = GETPOST('pay', 'alpha');
$amountofpayment = price2num(GETPOST('amount', 'alpha'));
$amountpaid= GETPOST('payment', 'alpha');

$invoiceid = GETPOST('invoiceid', 'int');

$paycode = $pay;
if ($pay == 'cash')   $paycode = 'LIQ'; // For backward compatibility
if ($pay == 'card')   $paycode = 'CB'; // For backward compatibility
if ($pay == 'cheque') $paycode = 'CHQ'; // For backward compatibility

// Retrieve paiementid
$sql = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND code = '".$db->escape($paycode)."'";
$resql = $db->query($sql);
$codes = $db->fetch_array($resql);
$paiementid = $codes[0];


$invoice = new Facture($db);
if ($invoiceid > 0)
{
    $ret = $invoice->fetch($invoiceid);
}
else
{
    $ret = $invoice->fetch('', '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')');
}
if ($ret > 0)
{
    $placeid = $invoice->id;
}

$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];

$soc = new Societe($db);
if ($invoice->socid > 0) $soc->fetch($invoice->socid);
else $soc->fetch($conf->global->$constforcompanyid);


/*
 * Actions
 */

if ($action == 'valid' && $user->rights->facture->creer) {
	if ($pay == "cash") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH' . $_SESSION["takeposterminal"]};            // For backward compatibility
	elseif ($pay == "card") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CB' . $_SESSION["takeposterminal"]};          // For backward compatibility
	elseif ($pay == "cheque") $bankaccount = $conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE' . $_SESSION["takeposterminal"]};    // For backward compatibility
	else {
		$accountname = "CASHDESK_ID_BANKACCOUNT_" . $pay . $_SESSION["takeposterminal"];
		$bankaccount = $conf->global->$accountname;
	}
	$now = dol_now('tzuser');
	$res = 0;

	$invoice = new Facture($db);
	$invoice->fetch($placeid);
	if ($invoice->total_ttc < 0) {
		$invoice->type = $invoice::TYPE_CREDIT_NOTE;
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "facture WHERE ";
		$sql .= "fk_soc = '" . $invoice->socid . "' ";
		$sql .= "AND type <> " . Facture::TYPE_CREDIT_NOTE . " ";
		$sql .= "AND fk_statut >= " . $invoice::STATUS_VALIDATED . " ";
		$sql .= "ORDER BY rowid DESC";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$fk_source = $obj->rowid;
			if ($fk_source == null) {
				fail($langs->transnoentitiesnoconv("NoPreviousBillForCustomer"));
			}
		} else {
			fail($langs->transnoentitiesnoconv("NoPreviousBillForCustomer"));
		}
		$invoice->fk_facture_source = $fk_source;
		$invoice->update($user);
	}

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK' . $_SESSION["takeposterminal"];
	if ($invoice->statut != Facture::STATUS_DRAFT) {
		//If invoice is validated but it is not fully paid is not error and make the payment
		if ($invoice->getRemainToPay() > 0) $res = 1;
		else {
			dol_syslog("Sale already validated");
			dol_htmloutput_errors($langs->trans("InvoiceIsAlreadyValidated", "TakePos"), null, 1);
		}
	} elseif (count($invoice->lines) == 0) {
		dol_syslog("Sale without lines");
		dol_htmloutput_errors($langs->trans("NoLinesToBill", "TakePos"), null, 1);
	} elseif (!empty($conf->stock->enabled) && $conf->global->$constantforkey != "1") {
		$savconst = $conf->global->STOCK_CALCULATE_ON_BILL;
		$conf->global->STOCK_CALCULATE_ON_BILL = 1;

		$constantforkey = 'CASHDESK_ID_WAREHOUSE' . $_SESSION["takeposterminal"];
		dol_syslog("Validate invoice with stock change into warehouse defined into constant " . $constantforkey . " = " . $conf->global->$constantforkey);
		$res = $invoice->validate($user, '', $conf->global->$constantforkey);

		$conf->global->STOCK_CALCULATE_ON_BILL = $savconst;
	} else {
		$res = $invoice->validate($user);
	}

	$remaintopay = $invoice->getRemainToPay();

	if ($pay == 'NC') {

		require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
		$discount = new DiscountAbsolute($db);

		$sql = "SELECT rc.rowid, rc.amount_ttc as amount FROM " . MAIN_DB_PREFIX . "societe_remise_except rc WHERE rc.fk_soc = " . $invoice->socid . " AND rc.entity = 1 AND discount_type = 0 AND (fk_facture_line IS NULL AND fk_facture IS NULL) ORDER BY amount_ttc DESC";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			$discount->fetch($obj->rowid);
			$fk_facture_source = $discount->fk_facture_source; 
			$payment_nc = new Paiement($db);
			if ($discount->amount_ttc > $remaintopay) {
				// Obtener categoría de cliente
				$sql = "SELECT label FROM llx_categorie lc JOIN llx_categorie_societe lcs ON lcs.fk_categorie = lc.rowid WHERE lcs.fk_soc =".$invoice->socid;
				$resql = $db->query($sql);
				$category = "";
				if($resql > 0){
					$obj = $db->fetch_object($resql);
					$category = $obj->label;
				}
				$newdiscount1 = new DiscountAbsolute($db);
				$newdiscount2 = new DiscountAbsolute($db);
				$newdiscount1->fk_facture_source = $discount->fk_facture_source;
				$newdiscount2->fk_facture_source = $discount->fk_facture_source;
				$newdiscount1->fk_facture = $discount->fk_facture;
				$newdiscount2->fk_facture = $discount->fk_facture;
				$newdiscount1->fk_facture_line = $discount->fk_facture_line;
				$newdiscount2->fk_facture_line = $discount->fk_facture_line;
				$newdiscount1->fk_invoice_supplier_source = $discount->fk_invoice_supplier_source;
				$newdiscount2->fk_invoice_supplier_source = $discount->fk_invoice_supplier_source;
				if ($discount->description == '(CREDIT_NOTE)' || $discount->description == '(DEPOSIT)') {
					$newdiscount1->description = $discount->description;
					$newdiscount2->description = $discount->description;
				} else {
					$newdiscount1->description = $discount->description . ' (1)';
					$newdiscount2->description = $discount->description . ' (2)';
				}

				$newdiscount1->fk_user = $discount->fk_user;
				$newdiscount2->fk_user = $discount->fk_user;
				$newdiscount1->fk_soc = $discount->fk_soc;
				$newdiscount2->fk_soc = $discount->fk_soc;
				$newdiscount1->discount_type = $discount->discount_type;
				$newdiscount2->discount_type = $discount->discount_type;
				$newdiscount1->datec = $discount->datec;
				$newdiscount2->datec = $discount->datec;
				$newdiscount1->tva_tx = $discount->tva_tx;
				$newdiscount2->tva_tx = $discount->tva_tx;
				$aux = price2num($discount->amount_ttc - $invoice->total_ttc);
				$newdiscount1->amount_ttc = price2num($discount->amount_ttc - $aux);
				$newdiscount2->amount_ttc = $aux;
				$newdiscount1->amount_ht = price2num($newdiscount1->amount_ttc / (1 + $newdiscount1->tva_tx / 100), 'MT');
				$newdiscount2->amount_ht = price2num($newdiscount2->amount_ttc / (1 + $newdiscount2->tva_tx / 100), 'MT');
				$newdiscount1->amount_tva = price2num($newdiscount1->amount_ttc - $newdiscount1->amount_ht);
				$newdiscount2->amount_tva = price2num($newdiscount2->amount_ttc - $newdiscount2->amount_ht);
				$db->begin();
				$fk_facture_source = $discount->fk_facture_source; 
				$discount->fk_facture_source = 0; // This is to delete only the require record (that we will recreate with two records) and not all family with same fk_facture_source
				// This is to delete only the require record (that we will recreate with two records) and not all family with same fk_invoice_supplier_source
				$discount->fk_invoice_supplier_source = 0;
				$res = $discount->delete($user);
				// Si la categoría es "Público en general", el crédito disponible quedará en $0
				if($category == "Público en general" || $category == "PÚBLICO EN GENERAL"){
					$newid1 = $newdiscount1->create($user);
					$newid2 = 1;
				} else {
					$newid1 = $newdiscount1->create($user);
					$newid2 = $newdiscount2->create($user);
				}
				if ($res > 0 && $newid1 > 0 && $newid2 > 0) {
					$db->commit();
					$result = $newdiscount1->link_to_invoice(0, $invoice->id);
					$result = $invoice->set_paid($user);
					if ($result < 0) setEventMessages($invoice->error, $invoice->errors, 'errors');
				} else {
					$db->rollback();
					setEventMessages($newdiscount1->error, $newdiscount1->errors, 'errors');
				}
				$remaintopay = $invoice->getRemainToPay(); // Recalculate remain to pay after the payment is recorded
				if ($remaintopay == 0) {
					//* ========== Set facture paiement method from the source invoice ==========

					$sql_mode_reglement = "SELECT f.fk_mode_reglement FROM " . MAIN_DB_PREFIX . "facture f ";
					$sql_mode_reglement .= "WHERE f.rowid = (SELECT f.fk_facture_source FROM " . MAIN_DB_PREFIX . "facture f WHERE f.rowid = " . $fk_facture_source . ")";
					$res_mode_reglement = $db->query($sql_mode_reglement);
					$obj_mode_reglement = $db->fetch_object($res_mode_reglement);
					$result_payment = $invoice->setPaymentMethods($obj_mode_reglement->fk_mode_reglement);
					if ($result_payment < 0)	dol_print_error($db, $object->error);

					//* ========== End Set facture paiement method from the source invoice ==========
					$payment_nc->addAmount($invoice->ref, 58,  $invoice->total_ttc);

					dol_syslog("Invoice is paid, so we set it to status Paid");
					$result = $invoice->set_paid($user);
					if ($result > 0) $invoice->paye = 1;
				} else {
					dol_syslog("Invoice is not paid, remain to pay = " . $remaintopay);
				}
			} else{
				$result = $discount->link_to_invoice(0, $invoice->id);
				$remaintopay -= $discount->amount_ttc;
				if ($result < 0) {
					setEventMessages($invoice->error, $invoice->errors, 'errors');
				}
				$remaintopay = $invoice->getRemainToPay(); // Recalculate remain to pay after the payment is recorded
				if ($remaintopay == 0) {
					dol_syslog("Invoice is paid, so we set it to status Paid");
					$result = $invoice->set_paid($user);
					if ($result > 0) $invoice->paye = 1;
				} else {
					dol_syslog("Invoice is not paid, remain to pay = " . $remaintopay);
				}

				//* ========== Set facture paiement method from the source invoice ==========

				$sql_mode_reglement = "SELECT f.fk_mode_reglement FROM " . MAIN_DB_PREFIX . "facture f ";
				$sql_mode_reglement .= "WHERE f.rowid = (SELECT f.fk_facture_source FROM " . MAIN_DB_PREFIX . "facture f WHERE f.rowid = " . $fk_facture_source . ")";
				$res_mode_reglement = $db->query($sql_mode_reglement);
				$obj_mode_reglement = $db->fetch_object($res_mode_reglement);
				$res_payment = $invoice->setPaymentMethods($obj_mode_reglement->fk_mode_reglement);
				if ($res_payment < 0)	dol_print_error($db, $object->error);

				//* ========== End Set facture paiement method from the source invoice ==========

				$payment_nc->addAmount($invoice->ref, 58,  $discount->amount_ttc);
			}
		}
	} else {
		// Add the payment
		if ($res >= 0 && $remaintopay > 0) {
			$payment = new Paiement($db);
			$payment->datepaye = $now;
			$sql = "UPDATE " . MAIN_DB_PREFIX . "facture SET datef = '" . date('Y-m-d', $payment->datepaye) . "' WHERE rowid = " . $invoice->id;
			$resql = $db->query($sql);
			$payment->fk_account = $bankaccount;
			$payment->amounts[$invoice->id] = $amountofpayment;

			// If user has not used change control, add total invoice payment
			if ($amountofpayment < $remaintopay && $amountofpayment > 0) {
				$payment->amounts[$invoice->id] = $amountofpayment;
			}else{
				$payment->amounts[$invoice->id] = $remaintopay;
			}

			$payment->paiementid = $paiementid;
			$payment->num_payment = $invoice->ref;

			$payment->create($user);
			$payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccount, '', '');

			if(!empty($amountpaid)) {
				$amount= $amountpaid;
			} else {
				$amount = $remaintopay;
			}

			$payment->addAmount($invoice->ref, $paiementid,  $amount);

			$remaintopay = $invoice->getRemainToPay(); // Recalculate remain to pay after the payment is recorded
			if ($remaintopay == 0) {
				dol_syslog("Invoice is paid, so we set it to status Paid");
				$result = $invoice->set_paid($user);
				if ($result > 0) $invoice->paye = 1;
			} else {
				dol_syslog("Invoice is not paid, remain to pay = " . $remaintopay);
			}
		} else {
			dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
		}
	}


	//* ========= Set facture paiement method from object paiement ==================
	$sql_facture_paiements = "SELECT lp.rowid as paiement_id, lp.amount, lp.fk_paiement as type_paiement";
	$sql_facture_paiements .= " FROM ". MAIN_DB_PREFIX . "paiement_facture pf";
	$sql_facture_paiements .= " LEFT JOIN " . MAIN_DB_PREFIX . "paiement lp ON pf.fk_paiement = lp.rowid";
	$sql_facture_paiements .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture lf ON lf.rowid = pf.fk_facture";
	$sql_facture_paiements .= " WHERE lf.rowid = " . $invoice->id;

	$resql_facture_paiements = $db->query($sql_facture_paiements);
	$facture_paiements = array();
	while ($obj = $db->fetch_object($resql_facture_paiements)) {
		array_push($facture_paiements, $obj);
	}
	$mayor_pago = 0;
	$mayor_pago_id = 0;
	foreach ($facture_paiements as $key => $value) {
		if($mayor_pago == $value->amount) $mayor_pago_id = 0;
		elseif ($value->amount > $mayor_pago) {
			$mayor_pago = $value->amount;
			$mayor_pago_id = $value->type_paiement;
		}
	}
	

	$sql_nc_paiements = "SELECT lap.amount";
	$sql_nc_paiements .= " FROM ". MAIN_DB_PREFIX . "amounts_paid lap";
	$sql_nc_paiements .= " WHERE lap.num_paiement = '" . $invoice->ref . "' AND lap.amount > ".$mayor_pago." AND lap.fk_paiement = 58";

	// If there is not a higher amount with a credit note, the payment method will be changed
	$resql_nc_paiements = $db->query($sql_nc_paiements);
	if($resql_nc_paiements->num_rows == 0){
		$result_payment = $invoice->setPaymentMethods($mayor_pago_id);
		if ($result_payment < 0) dol_print_error($db, $object->error);
	}
	//* ========= End Set facture paiement method from object paiement ==================
}

if ($action == 'history')
{
    $placeid = (int) GETPOST('placeid', 'int');
    $invoice = new Facture($db);
    $invoice->fetch($placeid);
}

if (($action == "addline" || $action == "freezone") && $placeid == 0)
{
	$invoice->socid = $conf->global->$constforcompanyid;
	$invoice->date = dol_now('tzuser');
	$invoice->module_source = 'takepos';
	$invoice->pos_source = $_SESSION["takeposterminal"];

	if ($invoice->socid <= 0)
	{
		$langs->load('errors');
		dol_htmloutput_errors($langs->trans("ErrorModuleSetupNotComplete", "TakePos"), null, 1);
	}
	else
	{
		$placeid = $invoice->create($user);
		if ($placeid < 0)
		{
			dol_htmloutput_errors($invoice->error, $invoice->errors, 1);
		}
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture set ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' where rowid=".$placeid;
		$db->query($sql);
	}
}

if ($action == "addline") {
	$prod = new Product($db);
	$prod->fetch($idproduct);

	if ($prod->type == 1) { //Si el producto es un servicio
		$customer = new Societe($db);
		$customer->fetch($invoice->socid);

		$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);

		$price = $datapriceofproduct['pu_ht'];
		$price_ttc = $datapriceofproduct['pu_ttc'];
		$price_base_type = $datapriceofproduct['price_base_type'];
		$tva_tx = $datapriceofproduct['tva_tx'];
		$tva_npr = $datapriceofproduct['tva_npr'];

		$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
		$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);
		$descuento = 0;

		$idoflineadded = $invoice->addline($prod->description, $price, 1, $tva_tx, $localtax1_tx, $localtax2_tx, $idproduct, $descuento, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0);

		$invoice->fetch($placeid);
	} else {
		$constantforkey = 'CASHDESK_ID_WAREHOUSE' . $_SESSION["takeposterminal"];

		$batch_data = new ProductBatch($db);

		// Buscamos el lotid y el batch del producto
		$sql = "SELECT pb.rowid as batch_id, pb.batch, pb.eatby, pb.sellby, pb.qty, pl.rowid as lotid FROM " . MAIN_DB_PREFIX . "product_batch pb";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product_stock ps ON pb.fk_product_stock = ps.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_lot pl ON pb.batch = pl.batch AND pl.fk_product = ps.fk_product AND pl.eatby = pb.eatby AND pl.sellby = pb.sellby";
		$sql .= " WHERE ps.fk_product = " . $prod->id . " AND ps.fk_entrepot = " . $conf->global->$constantforkey . " AND pb.qty > 0";
		if($lotid) $sql .= " AND pl.rowid = " . $lotid;
		$sql .= " ORDER BY pb.eatby";
		$resql = $db->query($sql);
		
		for ($i = 0; $i < $db->num_rows($resql); $i++) {
			$obj = $db->fetch_object($resql);
			$batch = $obj->batch;
			$batch_id = $obj->batch_id;
			$sql2 = "SELECT SUM(qty) as qty FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_product = " . $idproduct . " AND fk_facture = " . $placeid . " AND rowid IN (SELECT fk_object FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE batch = '" . $batch . "')";
			$resql2 = $db->query($sql2);
			$obj2 = $db->fetch_object($resql2);
			if ($obj2->qty < $obj->qty) {
				$lotid = $lotid ? $lotid : $obj->lotid;
				break;
			}
		}

		$response = $batch_data->fetch(0, $lotid, $conf->global->$constantforkey);

		// Sacamos los batch relacionados con el producto
		$batchs = [];
		$batchs_id = [];
		$sql = "SELECT pb.batch, pb.rowid as batch_id FROM " . MAIN_DB_PREFIX . "product_batch as pb";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product_stock ps ON pb.fk_product_stock = ps.rowid";
		$sql .= " WHERE ps.fk_product = " . $idproduct . " AND ps.fk_entrepot = " . $conf->global->$constantforkey . " AND pb.qty > 0";
		$resql = $db->query($sql);
		while ($obj_batch = $db->fetch_object($resql)) {
			$batchs[] = $obj_batch->batch;
			$batchs_id[] = $obj_batch->batch_id;
		}

		// Sacamos la cantidad de productos para todos los lotes independientemente de la fecha de caducidad
		$sql = "SELECT SUM(pb.qty) as qty FROM " . MAIN_DB_PREFIX . "product_batch as pb";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product_stock ps ON pb.fk_product_stock = ps.rowid";
		$sql .= " WHERE ps.fk_product = " . $idproduct . " AND ps.fk_entrepot = " . $conf->global->$constantforkey . " AND pb.qty > 0";

		$resql = $db->query($sql);
		$obj_qty = $db->fetch_object($resql);
		$batch_data->qty = $obj_qty->qty;

		if ($user->rights->takepos->stock_verify) {
			//Suma de productos en la factura
			$sql = "SELECT SUM(qty) as qty FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_product = " . $idproduct . " AND fk_facture = " . $placeid;
			$sql .= " AND rowid IN (SELECT fk_object FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE batch IN ('" . implode("','", $batchs) . "'))";

			$resql2 = $db->query($sql);
			$obj = $db->fetch_object($resql2);


			//Verificamos si hay stock suficiente
			if ($batch_data->qty > 0 && $batch_data->qty > $obj->qty) {
				$customer = new Societe($db);
				$customer->fetch($invoice->socid);

				$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);

				$price = $datapriceofproduct['pu_ht'];
				$price_ttc = $datapriceofproduct['pu_ttc'];
				$price_base_type = $datapriceofproduct['price_base_type'];
				$tva_tx = $datapriceofproduct['tva_tx'];
				$tva_npr = $datapriceofproduct['tva_npr'];

				// Local Taxes
				$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
				$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

				$descuento = ($prod->temp_discount != 0) ? $prod->temp_discount : (($prod->desc_max != 0) ? (($prod->desc_max >= 10) ? $customer->remise_percent : $prod->desc_max) : 0);

				$idoflineadded = $invoice->addline($prod->description, $price, 1, $tva_tx, $localtax1_tx, $localtax2_tx, $idproduct, $descuento, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, 0, null, 0, '', [], 100, '', null, 0, $lotid);

				$prodadd = new FactureLigne($db);
				$prodadd->fetch($idoflineadded);
				$prod->fetch_optionals();
				$prodadd->fetch_optionals();

				// Copiar opciones específicas
				$prodadd->array_options['options_noidenticfdi'] = $prod->array_options['options_noidenticfdi'];
				$prodadd->array_options['options_objimp'] = $prod->array_options['options_objimp'];

				// Obtener y asignar el ID del médico
				$medicId = GETPOST('medic');
				$prodadd->array_options['options_medico'] = $medicId ? $medicId : 'N/A';

				if ($medicId != NULL) {
					$invoice->user = $medicId;
				}

				$prodadd->array_options['options_batch'] = $batch;
				$prodadd->array_options['options_batch_id'] = $batch_id;
				$prodadd->insertExtraFields();
				$prodadd->update($user);
				$invoice->fetch($placeid);
			} else
				dol_htmloutput_errors("El producto {$prod->ref} no cuenta con stock suficiente. Para ver disponibilidad de click <a onclick='StockCheck(\"{$prod->id}\")'>aqui</a> o <a onclick='SubstituteCheck(\"{$prod->id}\")'>visualizar sustitutos</a>", [], 1);
		} else {
			$customer = new Societe($db);
			$customer->fetch($invoice->socid);

			$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);

			$price = $datapriceofproduct['pu_ht'];
			$price_ttc = $datapriceofproduct['pu_ttc'];
			$price_base_type = $datapriceofproduct['price_base_type'];
			$tva_tx = $datapriceofproduct['tva_tx'];
			$tva_npr = $datapriceofproduct['tva_npr'];

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

			$descuento = ($prod->temp_discount != 0) ? $prod->temp_discount : (($prod->desc_max != 0) ? (($prod->desc_max >= 10) ? $customer->remise_percent : $prod->desc_max) : 0);

			$idoflineadded = $invoice->addline($prod->description, $price, 1, $tva_tx, $localtax1_tx, $localtax2_tx, $idproduct, $descuento, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, 0, null, 0, '', [], 100, '', null, 0, $lotid);

			$prodadd = new FactureLigne($db);
			$prodadd->fetch($idoflineadded);
			$prod->fetch_optionals();
			$prodadd->fetch_optionals();

			// Copiar opciones específicas
			$prodadd->array_options['options_noidenticfdi'] = $prod->array_options['options_noidenticfdi'];
			$prodadd->array_options['options_objimp'] = $prod->array_options['options_objimp'];

			// Obtener y asignar el ID del médico
			$medicId = GETPOST('medic');
			$prodadd->array_options['options_medico'] = $medicId ? $medicId : 'N/A';

			if ($medicId != NULL) {
				$invoice->user = $medicId;
			}

			$prodadd->array_options['options_batch'] = $batch;
			$prodadd->array_options['options_batchid'] = $batch_id;
			$prodadd->insertExtraFields();
			$prodadd->update($user);
			$invoice->fetch($placeid);
		}
	}
}

if ($action == "notsell"){
	$prod = new Product($db);
    $prod->fetch($idproduct);
	dol_htmloutput_errors("El producto " . $prod->getNomURL() . " ha sido descontinuado por el proveedor", null, 1);

}

if ($action == "freezone") {
    $customer = new Societe($db);
    $customer->fetch($invoice->socid);

    $tva_tx = get_default_tva($mysoc, $customer);

    // Local Taxes
    $localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
    $localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

    $invoice->addline($desc, $number, 1, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', 0, 0, 0, '', 'TTC', $number, 0, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0, $batch);
    $invoice->fetch($placeid);
}

if ($action == "addnote") {
    foreach ($invoice->lines as $line)
    {
        if ($line->id == $number)
		{
			$line->array_options['order_notes'] = $desc;
			$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
        }
    }
    $invoice->fetch($placeid);
}

if ($action == "deleteline") {
    if ($idline > 0 and $placeid > 0) { // If invoice exists and line selected. To avoid errors if deleted from another device or no line selected.
        $invoice->deleteline($idline);
        $invoice->fetch($placeid);

		// Eliminamos las lineas de extrafields
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE fk_object = " . $idline;
		$resql = $db->query($sql);
    }
    elseif ($placeid > 0) {             // If invoice exists but no line selected, proceed to delete last line.
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facturedet where fk_facture='".$placeid."' order by rowid DESC";
        $resql = $db->query($sql);
        $row = $db->fetch_array($resql);
        $deletelineid = $row[0];
        $invoice->deleteline($deletelineid);
        $invoice->fetch($placeid);
    }
}

if ($action == "delete") {
	// $placeid is the invoice id (it differs from place) and is defined if the place is set and the ref of invoice is '(PROV-POS'.$_SESSION["takeposterminal"].'-'.$place.')', so the fetch at begining of page works.
	if ($placeid > 0) {
        $result = $invoice->fetch($placeid);

        if ($result > 0 && $invoice->statut == Facture::STATUS_DRAFT)
        {
        	$db->begin();

        	// We delete the lines
			foreach ($invoice->lines as $line) {
				$res = $invoice->deleteline($line->id);
			}
        	// $sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_extrafields where fk_object = ".$placeid;
        	// $resql1 = $db->query($sql);
        	// $sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet where fk_facture = ".$placeid;
            // $resql2 = $db->query($sql);
			$sql = "UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".$conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]}." where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
			$resql3 = $db->query($sql);

			if ($res < 0) {
				setEventMessages($invoice->error, $invoice->errors, 'errors');
				$error ++;
			}

			if ($res >= 0 && $resql3)
            {
            	$db->commit();
            }
            else
            {
            	$db->rollback();
            }

            $invoice->fetch($placeid);
		} else {
			if (preg_match('/^[\(]?PROV/i', $invoice->ref)) {
				$constantforkey = 'CASHDESK_ID_WAREHOUSE' . $_SESSION["takeposterminal"];

				$invoice->set_unpaid($user);
				$invoice->setDraft($user, $conf->global->$constantforkey);

				$savconst = $conf->global->STOCK_CALCULATE_ON_BILL;
				$conf->global->STOCK_CALCULATE_ON_BILL = 1;
				
				$invoice->validate($user, '', $conf->global->$constantforkey);
				$invoice->set_paid($user);
				
				$conf->global->STOCK_CALCULATE_ON_BILL = $savconst;
				
				$sql = "UPDATE ".MAIN_DB_PREFIX."paiement set num_paiement='$invoice->ref' where rowid IN (SELECT fk_paiement FROM ".MAIN_DB_PREFIX."paiement_facture WHERE fk_facture = $placeid)";
				$db->query($sql);
			}
		}
    }
}

if ($action == "updateqty")
{
	$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
	$warehouse = new Entrepot($db);
	$warehouse->fetch($conf->global->$constantforkey);
    foreach ($invoice->lines as $line)
    {
		if ($line->id == $idline){
			if ($user->rights->takepos->stock_verify){
				$prod = new Product($db);
				$prod->fetch($line->fk_product);
				$sql = "SELECT SUM(qty) as qty FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_product = ".$prod->id." AND fk_facture = ".$placeid;
				$resql = $db->query($sql);
				$obj = $db->fetch_object($resql);
				$batch = $line->array_options['options_batch'];

				$sum = "SELECT SUM(lpb.qty) AS qty FROM ".MAIN_DB_PREFIX."product_batch lpb
				LEFT JOIN ".MAIN_DB_PREFIX."product_stock lps ON lpb.fk_product_stock = lps.rowid
				WHERE lps.fk_entrepot = ".$warehouse->id." AND lps.fk_product = ".$line->fk_product." AND lpb.eatby >= CURRENT_DATE()";
				$resum = $db->query($sum);
				$objsum = $db->fetch_object($resum);
				$newnum = $objsum->qty;
				if ($number > $newnum) {
					dol_htmloutput_errors("Stock insuficiente, cantidad corregida automaticamente", null, 1);
					$number = $newnum;
				}
				// Asignacion de lotes
				$sqlbatch = "SELECT lpb.qty, lpb.eatby FROM ".MAIN_DB_PREFIX."product_batch lpb INNER JOIN ".MAIN_DB_PREFIX."product_stock lps ON lpb.fk_product_stock = lps.rowid";
				$sqlbatch .= " WHERE lpb.batch = '".$batch."' AND lps.fk_product = ".$line->fk_product." AND lps.fk_entrepot = ".$warehouse->id;
				$rebatch = $db->query($sqlbatch);
				$objb = $db->fetch_object($rebatch);

				$desc = $line->remise_percent;
				// Si tiene lote, revisamos que haya suficiente stock
				if ($number <= ($prod->stock_reel - $obj->qty + $line->qty)) {
					if ($number > ($objb->qty - $obj->qty + $line->qty)) {
						$aux1 = $objb->qty;
						$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $aux1, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
						$aux2 = $number - $aux1;
						$fecha = dol_print_date($objb->eatby, 'dayrfc');

						$customer = new Societe($db);
						$customer->fetch($invoice->socid);

						$datapriceofproduct = $prod->getSellPrice($mysoc, $customer, 0);

						$price = $datapriceofproduct['pu_ht'];
						$price_ttc = $datapriceofproduct['pu_ttc'];
						//$price_min = $datapriceofproduct['price_min'];
						$price_base_type = $datapriceofproduct['price_base_type'];
						$tva_tx = $datapriceofproduct['tva_tx'];
						$tva_npr = $datapriceofproduct['tva_npr'];

						// Local Taxes
						$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
						$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

						while ($aux2>0){
							$sql = 'SELECT lpb.batch, lpb.eatby, lpb.qty FROM '.MAIN_DB_PREFIX.'product_batch lpb INNER JOIN '.MAIN_DB_PREFIX.'product_stock lps ON lpb.fk_product_stock = lps.rowid';
							$sql .= ' WHERE lpb.eatby > "'.$fecha.'" AND lpb.qty >= 1 AND lps.fk_product = '.$prod->id.' AND lps.fk_entrepot = '.$warehouse->id.' ORDER BY lpb.eatby';
							$resql = $db->query($sql);
							if ($resql->num_rows > 0) {
								$obj = $db->fetch_object($resql);
								$fecha = dol_print_date($obj->eatby, 'dayrfc');
								if ($aux2>$obj->qty){
									$aux1 = $obj->qty;
									$aux2-= $obj->qty;
								} else {
									$aux1 = $aux2;
									$aux2=0;
								}
							} else {
								dol_htmloutput_errors("Stock insuficiente", null, 1);
								break;
							}
							$result = $invoice->addline($prod->description, $price, $aux1, $tva_tx, $localtax1_tx, $localtax2_tx, $prod->id, $desc, '', 0, 0, 0, '', $price_base_type, $price_ttc, $prod->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0, $obj->batch);
							$sql = "UPDATE " . MAIN_DB_PREFIX . "facturedet_extrafields SET medico='N/A' WHERE fk_object=" . $result;
							$resql = $db->query($sql);
						}
					} else {
						$result = $invoice->updateline($line->id, $line->desc, $line->subprice, $number, $desc, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
					}
				} else {
					dol_htmloutput_errors("Stock insuficiente", null, 1);
				}		
			} else $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $number, $desc, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
		}
	}
    $invoice->fetch($placeid);
}

if ($action == "updateprice")
{
    foreach ($invoice->lines as $line)
    {
        if ($line->id == $idline && $user->rights->takepos->edit_price) {
			$result = $invoice->updateline($line->id, $line->desc, $number, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'TTC', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
        } else {
			dol_htmloutput_errors("No tiene permiso para realizar esta acción", null, 1);
		}
    }

    $invoice->fetch($placeid);
}

function sortByPrice($a, $b) {
	$desc1 = 100-$a->remise_percent;
	$pu1 = $a->total_ttc * 100 / $desc1;

	$desc2 = 100-$b->remise_percent;
	$pu2 = $b->total_ttc * 100 / $desc2;

	return $pu1 > $pu2;
}

function getProductCategories($fkProduct, $invoice, $db) {
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$c = new Categorie($db);
	return $c->containing($fkProduct, Categorie::TYPE_PRODUCT, 'id');
}

function isCategoryFound($categoryId, $invoice, $db) {
	foreach ($invoice->lines as $line) {
		$categories = getProductCategories($line->fk_product, $invoice, $db);
		if(in_array($categoryId, $categories)) {
			return true;
		}
	}

	return false;
}

function clearDiscounts($lines, $db, $invoice) {
		foreach($lines as $line) {
			$prod = new Product($db);
			$prod->fetch($line->fk_product);
			$customer = new Societe($db);
			$customer->fetch($invoice->socid);
			$descuento = ($prod->temp_discount != 0) ? $prod->temp_discount : (($prod->desc_max != 0) ? (($prod->desc_max >= 10) ? $customer->remise_percent : $prod->desc_max) : 0);
	
			$invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $descuento, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);
		}
}

$category1Found = isCategoryFound('14', $invoice, $db); //COSMETICOS;
$category2Found = isCategoryFound('21', $invoice, $db); // LIMPIEZA;
$applyDiscontLines = array();
$applyDiscontLines2 = array();
$DISCOUNT = 15;
foreach ($invoice->lines as $line)
{
	$categories = getProductCategories($line->fk_product, $invoice, $db);
	if(in_array(14, $categories)) {
		$applyDiscontLines[] = $line;
	}else if(in_array(21, $categories)) {
		$applyDiscontLines2[] = $line;
	}
}
var_dump(count($applyDiscontLines));
var_dump(count($applyDiscontLines2));

clearDiscounts($applyDiscontLines, $db, $invoice);
clearDiscounts($applyDiscontLines2, $db, $invoice);
$invoice->fetch($placeid);

if($category1Found && $category2Found) {
	//APPLY CUSTOM DISCOUNT
	usort($applyDiscontLines, 'sortByPrice');
	usort($applyDiscontLines2, 'sortByPrice');

	$c = min(count($applyDiscontLines), count($applyDiscontLines2));

	if(count($applyDiscontLines) > 0 && count($applyDiscontLines2) > 0) {
		for ($i = 0; $i<$c; $i++)
		{
			$line1 = $applyDiscontLines[$i];
			$line2 = $applyDiscontLines2[$i];
	
			$invoice->updateline($line1->id, $line1->desc, $line1->subprice, $line1->qty, $DISCOUNT, $line1->date_start, $line1->date_end, $line1->tva_tx, $line1->localtax1_tx, $line1->localtax2_tx, 'HT', $line1->info_bits, $line1->product_type, $line1->fk_parent_line, 0, $line1->fk_fournprice, $line1->pa_ht, $line1->label, $line1->special_code, $line1->array_options, $line1->situation_percent, $line1->fk_unit);
			$invoice->updateline($line2->id, $line2->desc, $line2->subprice, $line2->qty, $DISCOUNT, $line2->date_start, $line2->date_end, $line2->tva_tx, $line2->localtax1_tx, $line2->localtax2_tx, 'HT', $line2->info_bits, $line2->product_type, $line2->fk_parent_line, 0, $line2->fk_fournprice, $line2->pa_ht, $line2->label, $line2->special_code, $line2->array_options, $line2->situation_percent, $line2->fk_unit);
		}
	}

	$invoice->fetch($placeid);
}

if ($action == "updatereduction")
{
    foreach ($invoice->lines as $line)
    {
		// $sql = "SELECT prod->desc_max FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$line->fk_product;
		// $resql = $db->query($sql);
		// $obj = $db->fetch_object($resql);
		// $prod->desc_max = $obj->prod->desc_max;
		// if ($number <= $prod->desc_max) {
        // 	if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $number, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);}
		// }else {
		// 	dol_htmloutput_errors("Descuento maximo superado, se aplico ".$prod->desc_max."% de descuento.", null, 1);
		// 	if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $prod->desc_max, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);}
		// }
		if ($line->id == $idline) { $result = $invoice->updateline($line->id, $line->desc, $line->subprice, $line->qty, $number, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit);}
	}

    $invoice->fetch($placeid);
}

if ($action == "order" and $placeid != 0)
{
    include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

    $headerorder = '<html><br><b>'.$langs->trans('Place').' '.$place.'<br><table width="65%"><thead><tr><th class="left">'.$langs->trans("Label").'</th><th class="right">'.$langs->trans("Qty").'</th></tr></thead><tbody>';
    $footerorder = '</tbody></table>'.dol_print_date(dol_now('tzuser'), 'dayhour').'<br></html>';
    $order_receipt_printer1 = "";
    $order_receipt_printer2 = "";
    $catsprinter1 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_1);
    $catsprinter2 = explode(';', $conf->global->TAKEPOS_PRINTED_CATEGORIES_2);
    foreach ($invoice->lines as $line)
    {
        if ($line->special_code == "4") {
        	continue;
        }
        $c = new Categorie($db);
        $existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
        $result = array_intersect($catsprinter1, $existing);
        $count = count($result);
        if ($count > 0) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='4' where rowid=".$line->id;
            $db->query($sql);
            $order_receipt_printer1 .= '<tr>'.$line->product_label.'<td class="right">'.$line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer1 .= "<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer1 .= '</td></tr>';
        }
    }

    foreach ($invoice->lines as $line)
    {
        if ($line->special_code == "4") {
        	continue;
        }
        $c = new Categorie($db);
        $existing = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
        $result = array_intersect($catsprinter2, $existing);
        $count = count($result);
        if ($count > 0) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."facturedet set special_code='4' where rowid=".$line->id;
            $db->query($sql);
            $order_receipt_printer2 .= '<tr>'.$line->product_label.'<td class="right">'.$line->qty;
			if (!empty($line->array_options['options_order_notes'])) $order_receipt_printer2 .= "<br>(".$line->array_options['options_order_notes'].")";
			$order_receipt_printer2 .= '</td></tr>';
        }
    }

    $invoice->fetch($placeid);
}

if ($action == "apartar"){
	$error = 0;
	$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
	$warehouse = new Entrepot($db);	
	$warehouse->fetch($conf->global->$constantforkey);
	$wrhsapartado = new Entrepot($db);
	$wrhsapartado->fetch($conf->global->{'TAKEPOS_WAREHOUSE_RETURN'});
	$aux = 0;
	if ($invoice->lines){
		foreach ($invoice->lines as $line)
		{
			$object = new Product($db);
			$result = $object->fetch($line->fk_product);
			if ($result < 0) $error++;
			$result1 = $object->correct_stock_batch(
				$user,
				$warehouse->id,
				$line->qty,
				1,
				'Apartado',
				0,
				$line->array_options['options_expdate'],
				'',
				$line->array_options['options_batch'],
				(isset($_POST["inventorycode"]) ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now('tzuser'), '%y%m%d%H%M%S').$aux),
				'Apartado'
			);
			if ($result1 < 0) $error++;
			$result2 = $object->correct_stock_batch(
				$user,
				$wrhsapartado->id,
				$line->qty,
				0,
				'Apartado',
				0,
				$line->array_options['options_expdate'],
				$line->array_options['options_sellby'],
				$line->array_options['options_batch'],
				(isset($_POST["inventorycode"]) ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now('tzuser'), '%y%m%d%H%M%S').$aux),
				'Apartado'
			);
			if ($result2 < 0) $error++;

			$aux++;

			if (!$error){
				if ($placeid > 0) {
					$result = $invoice->fetch($placeid);
			
					if ($result > 0 && $invoice->statut == Facture::STATUS_DRAFT)
					{
						$db->begin();
			
						// We delete the lines
						$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_extrafields where fk_object = ".$placeid;
						$resql1 = $db->query($sql);
						$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet where fk_facture = ".$placeid;
						$resql2 = $db->query($sql);
						$sql = "UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".$conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]}." where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
						$resql3 = $db->query($sql);
			
						if ($resql1 && $resql2 && $resql3)
						{
							$db->commit();
						}
						else
						{
							$db->rollback();
						}
			
						$invoice->fetch($placeid);
					}
				}
			}
		}
	} else {
		$error++;
	}
	if (!$error){
		dol_htmloutput_mesg("Apartado realizado correctamente", '', 'ok');
	} else {
		dol_htmloutput_mesg("No hay productos para apartar -> " . $object->errors[0], '', 'warning');
	}
}

if ($action == "liberarapartado") {
	$error = 0;
	$constantforkey = 'CASHDESK_ID_WAREHOUSE' . $_SESSION["takeposterminal"];
	$warehouse = new Entrepot($db);
	$warehouse->fetch($conf->global->$constantforkey);
	$wrhsapartado = new Entrepot($db);
	$wrhsapartado->fetch($conf->global->{'TAKEPOS_WAREHOUSE_RETURN'});
	if(!$idproduct) $idproduct = GETPOST('idproduct', 'int');
	$object = new Product($db);
	$result = $object->fetch($idproduct);

	$batch = GETPOST('batch', 'alpha');
	$eatby = GETPOST('eatby');
	$sellby = GETPOST('sellby');


	$qty = GETPOST('qty', 'int') * -1;

	$result1 = $object->correct_stock_batch(
		$user,
		$wrhsapartado->id,
		$qty,
		1,
		'Liberación de apartado',
		0,
		$eatby,
		$sellby,
		$batch,
		(isset($_POST["inventorycode"]) ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now('tzuser'), '%y%m%d%H%M%S'))
	);
	if ($result1 < 0) $error++;
	$result2 = $object->correct_stock_batch(
		$user,
		$warehouse->id,
		$qty,
		0,
		'Liberación de apartado',
		0,
		$eatby,
		$sellby,
		$batch,
		(isset($_POST["inventorycode"]) ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now('tzuser'), '%y%m%d%H%M%S'))
	);
	if ($result2 < 0) $error++;

	if (!$error) {
		$sql = "UPDATE " . MAIN_DB_PREFIX . "stock_mouvement SET active = 0 WHERE fk_product = " . $idproduct . " AND fk_entrepot = " . $warehouse->id . " AND label = 'Apartado'";
		$resql = $db->query($sql);
		if(!$resql) {
			dol_htmloutput_errors("Error al actualizar el stock de apartado a active = 0", null, 1);
		}

		$customer = new Societe($db);
		$customer->fetch($invoice->socid);

		$datapriceofproduct = $object->getSellPrice($mysoc, $customer, 0);

		$price = $datapriceofproduct['pu_ht'];
		$price_ttc = $datapriceofproduct['pu_ttc'];
		$price_base_type = $datapriceofproduct['price_base_type'];
		$tva_tx = $datapriceofproduct['tva_tx'];
		$tva_npr = $datapriceofproduct['tva_npr'];
		
		// Local Taxes
		$localtax1_tx = get_localtax($tva_tx, 1, $customer, $mysoc, $tva_npr);
		$localtax2_tx = get_localtax($tva_tx, 2, $customer, $mysoc, $tva_npr);

		$sql = 'SELECT lpb.batch, lpb.eatby, lpb.qty FROM '.MAIN_DB_PREFIX.'product_batch lpb INNER JOIN '.MAIN_DB_PREFIX.'product_stock lps ON lpb.fk_product_stock = lps.rowid
		WHERE lpb.eatby >= CURRENT_DATE() AND lpb.qty >= 1 AND lps.fk_product = '.$object->id.' AND lps.fk_entrepot = '.$warehouse->id.' ORDER BY lpb.eatby';
		$resql = $db->query($sql);
		//Se van asignando los lotes por orden de caducidad y cantidad
		for ($i = 0; $i < $db->num_rows($resql); $i++){
			$reslote = $db->fetch_object($resql);
			$lote = $reslote->batch;
			$sql2 = "SELECT SUM(qty) as qty FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_product = ".$idproduct." AND fk_facture = ".$placeid." AND rowid IN (SELECT fk_object FROM ".MAIN_DB_PREFIX."facturedet_extrafields WHERE batch = '".$lote."')";
			$resql2 = $db->query($sql2);
			$fesql2 = $db->fetch_object($resql2);
			if ($total_qty + $fesql2->qty <= $reslote->qty - 1){
				$batch = $lote;
				break;
			}
		}
		
		$idoflineadded = $invoice->addline($object->description, $price, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idproduct, $customer->remise_percent, '', 0, 0, 0, '', $price_base_type, $price_ttc, $object->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', null, 0, $batch);
		
		$invoice->fetch($placeid);
	}
	print '<script>
	parent.$.colorbox.close();
	</script>';
}

$sectionwithinvoicelink = '';
if ($action == "valid" || $action == "history" || ($action == "delete" && $invoice->statut == Facture::STATUS_VALIDATED))
{
    $sectionwithinvoicelink .= '<!-- Section with invoice link -->'."\n";
    $sectionwithinvoicelink .= '<span style="font-size:120%;" class="center">';
    $sectionwithinvoicelink .= $invoice->getNomUrl(1, '', 0, 0, '', 0, 0, -1, '_backoffice')." - ";
    $remaintopay = $invoice->getRemainToPay();
    if ($remaintopay > 0)
    {
        $sectionwithinvoicelink .= $langs->trans('RemainToPay').': <span class="amountremaintopay" style="font-size: unset">'.price($remaintopay, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
    }
    else
    {
        if ($invoice->paye) $sectionwithinvoicelink .= '<span class="amountpaymentcomplete" style="font-size: unset">'.$langs->trans("Paid").'</span>';
        else $sectionwithinvoicelink .= $langs->trans('BillShortStatusValidated');
    }
    $sectionwithinvoicelink .= '</span>';
	if ($remaintopay <= 0) {
		if ($conf->global->TAKEPOSCONNECTOR) {
			$sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="TakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
		} elseif ($conf->global->TAKEPOS_DOLIBARR_PRINTER) {
			$sectionwithinvoicelink .= ' <button id="buttonprint" type="button" onclick="DolibarrTakeposPrinting('.$placeid.');">'.$langs->trans('PrintTicket').'</button>';
		} else {
			$currentterminal = "TAKEPOS_NUM_TICKETS_TO_PRINT".$_SESSION["takeposterminal"];
			$sectionwithinvoicelink .= ' <button id="buttonprint" class="invoice" type="button" onclick="Print('.$placeid.','.$conf->global->$currentterminal.');">'.$langs->trans('PrintTicket').'</button>';
		}
		if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
		{
			$sectionwithinvoicelink .= ' <button id="buttonsend" type="button" onclick="SendTicket('.$placeid.');">'.$langs->trans('SendTicket').'</button>';
		}

		if ($conf->global->TAKEPOS_AUTO_PRINT_TICKETS) $sectionwithinvoicelink .= '<script language="javascript">$("#buttonprint").click();</script>';
	}

	print '<script>
	UnlockButtons();
	</script>';
}


/*
 * View
 */

$form = new Form($db);

?>
<script>
var selectedline=0;
var selectedtext="";
var placeid=<?php echo ($placeid > 0 ? $placeid : 0); ?>;
$(document).ready(function() {
	var idoflineadded = <?php echo ($idoflineadded ? $idoflineadded : 0); ?>;

    $('.posinvoiceline').click(function(){
    	console.log("Click done on "+this.id);
        $('.posinvoiceline').removeClass("selected");
        $(this).addClass("selected");
        if (selectedline==this.id) return; // If is already selected
        else selectedline=this.id;
        selectedtext=$('#'+selectedline).find("td:first").html();
    });

    /* Autoselect the line */
    if (idoflineadded > 0)
    {
        console.log("Auto select "+idoflineadded);
        $('.posinvoiceline#'+idoflineadded).click();
    }
<?php

if ($action == "order" and $order_receipt_printer1 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
        data: '<?php
        print $headerorder.$order_receipt_printer1.$footerorder; ?>'
    });
    <?php
}

if ($action == "order" and $order_receipt_printer2 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print2',
        data: '<?php
        print $headerorder.$order_receipt_printer2.$footerorder; ?>'
    });
    <?php
}

// Set focus to search field
if ($action == "search" || $action == "valid") {
    ?>
	parent.setFocusOnSearchField();
    <?php
}


if ($action == "temp" and $ticket_printer1 != "") {
    ?>
    $.ajax({
        type: "POST",
        url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
        data: '<?php
        print $header_soc.$header_ticket.$body_ticket.$ticket_printer1.$ticket_total.$footer_ticket; ?>'
    });
    <?php
}

if ($action == "search") {
    ?>
    $('#search').focus();
    <?php
}

?>

});

function SendTicket(id)
{
    console.log("Open box to select the Print/Send form");
    $.colorbox({href:"send.php?facid="+id, width:"90%", height:"50%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("SendTicket"); ?>"});
}

function Print(id, numtickets = 1){
	console.log(numtickets);
    $.colorbox({href:"receipt.php?facid="+id+"&numtickets="+numtickets, width:"40%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("PrintTicket"); ?>"});
}

function TakeposPrinting(id){
    var receipt;
    $.get("receipt.php?facid="+id, function(data, status){
        receipt=data.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '');
        $.ajax({
            type: "POST",
            url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER; ?>:8111/print',
            data: receipt
        });
    });
}
function DolibarrTakeposPrinting(id) {
    console.log('Printing invoice ticket ' + id)
    $.ajax({
        type: "GET",
        url: "<?php print dol_buildpath('/takepos/ajax/ajax.php', 1).'?action=printinvoiceticket&term='.$_SESSION["takeposterminal"].'&id='; ?>" + id,
    });
}

function StockCheck(id){
	$.colorbox({href:"../product/batchexp.php?contextpage=poslist&id="+id, width:"80%", height:"90%", transition:"none", iframe:"true", title:"Stock" });
}

function SubstituteCheck(id){
	$.colorbox({href:"../product/substitutes.php?contextpage=poslist&id="+id, width:"70%", height:"80%", transition:"none", iframe:"true", title:"Sustitutos" });
}
</script>

<?php
// Add again js for footer because this content is injected into takepos.php page so all init
// for tooltip and other js beautifiers must be reexecuted too.
if (!empty($conf->use_javascript_ajax))
{
    print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
    print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext ? '&'.$ext : '').'"></script>'."\n";
}


print '<div class="div-table-responsive-no-min invoice">';
print '<table id="tablelines" class="noborder noshadow postablelines" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';
print '<td class="linecoldescription">';
print '<span style="font-size:120%;" class="right">';
if ($conf->global->TAKEPOS_BAR_RESTAURANT)
{
    $sql = "SELECT floor, label FROM ".MAIN_DB_PREFIX."takepos_floor_tables where rowid=".((int) $place);
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $label = $obj->label;
        $floor = $obj->floor;
    }
	// In phone version only show when is invoice page
	if ($mobilepage == "invoice" || $mobilepage == "") {
		print $langs->trans('Place')." <b>".$label."</b> - ";
		print $langs->trans('Floor')." <b>".$floor."</b> - ";
	}
}
// In phone version only show when is invoice page
if ($mobilepage == "invoice" || $mobilepage == "") {
	print $langs->trans('TotalTTC');
	print ' : <b>'.price($invoice->total_ttc, 1, '', 1, -1, -1, $conf->currency).'</b></span>';
	print '<br><input type="hidden" name="socid" id="socid" value="'.$invoice->socid.'">';
	print '<br><input type="hidden" name="invoiceid" id="invoiceid" value="'.$invoice->id.'">'.$sectionwithinvoicelink;
	print '</td>';
}
if ($_SESSION["basiclayout"] != 1)
{
	print '<td class="linecolqty right">'.$langs->trans('ReductionShort').'</td>';
	print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';
	print '<td class="linecolqty right">'.$langs->trans('P.U.').'</td>';
	print '<td class="linecolht right nowraponall">'.$langs->trans('TotalTTCShort').'</td>';
}
print "</tr>\n";


if ($_SESSION["basiclayout"] == 1)
{
	if ($mobilepage == "cats")
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categorie = new Categorie($db);
        $categories = $categorie->get_full_arbo('product');
		$htmlforlines = '';
        foreach ($categories as $row) {
			$htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			$htmlforlines .= '" onclick="LoadProducts('.$row['id'].');">';
			$htmlforlines .= '<td class="left">';
			$htmlforlines .= $row['label'];
			$htmlforlines .= '</td>';
			$htmlforlines .= '</tr>'."\n";
		}
		$htmlforlines .= '</table>';
		$htmlforlines .= '</table>';
		print $htmlforlines;
	}

	if ($mobilepage == "products")
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$object = new Categorie($db);
		$catid = GETPOST('catid', 'int');
		$result = $object->fetch($catid);
		$prods = $object->getObjectsInCateg("product");
		$htmlforlines = '';
		foreach ($prods as $row) {
			$htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			$htmlforlines .= '" onclick="AddProduct(\''.$place.'\', '.$row->id.')">';
			$htmlforlines .= '<td class="left">';
			$htmlforlines .= $row->label;
			$htmlforlines .= '</td>';
			$htmlforlines .= '</tr>'."\n";
		}
		$htmlforlines .= '</table>';
		print $htmlforlines;
	}

	if ($mobilepage == "places")
	{
		$sql = "SELECT rowid, entity, label, leftpos, toppos, floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables";
		$resql = $db->query($sql);
		$rows = array();
		$htmlforlines = '';
		while ($row = $db->fetch_array($resql)) {
			$rows[] = $row;
			$htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
			$htmlforlines .= '" onclick="LoadPlace(\''.$row['label'].'\')">';
			$htmlforlines .= '<td class="left">';
			$htmlforlines .= $row['label'];
			$htmlforlines .= '</td>';
			$htmlforlines .= '</tr>'."\n";
		}
		$htmlforlines .= '</table>';
		print $htmlforlines;
	}
}

if ($placeid > 0)
{
	//In Phone basic layout hide some content depends situation
	if ($_SESSION["basiclayout"] == 1 && $mobilepage != "invoice" && $action != "order") return;

    if (is_array($invoice->lines) && count($invoice->lines))
    {
        $tmplines = array_reverse($invoice->lines);
        foreach ($tmplines as $line)
        {
            $htmlforlines = '';

            $htmlforlines .= '<tr class="drag drop oddeven posinvoiceline';
            if ($line->special_code == "4") {
                $htmlforlines .= ' order';
            }
            $htmlforlines .= '" id="'.$line->id.'">';
            $htmlforlines .= '<td class="left">';
            //if ($line->product_label) $htmlforlines.= '<b>'.$line->product_label.'</b>';
            if (isset($line->product_type))
            {
                if (empty($line->product_type)) $htmlforlines .= img_object('', 'product').' ';
                else $htmlforlines .= img_object('', 'service').' ';
            }
            if ($line->product_label) $htmlforlines .= $line->product_label;
            if ($line->product_label && $line->desc) $htmlforlines .= '<br>';
            if ($line->product_label != $line->desc)
            {
                $firstline = dolGetFirstLineOfText($line->desc);
                if ($firstline != $line->desc)
                {
                    $htmlforlines .= $form->textwithpicto(dolGetFirstLineOfText($line->desc), $line->desc);
                }
                else
                {
                    $htmlforlines .= $line->desc;
                }
            }
            if (!empty($line->array_options['options_order_notes'])) $htmlforlines .= "<br>(".$line->array_options['options_order_notes'].")";
            if ($_SESSION["basiclayout"] != 1)
			{
				$moreinfo = '';
				$moreinfo .= $langs->transcountry("TotalHT", $mysoc->country_code).': '.price($line->total_ht);
				if ($line->vat_src_code) $moreinfo .= '<br>'.$langs->trans("VATCode").': '.$line->vat_src_code;
				$moreinfo .= '<br>'.$langs->transcountry("TotalVAT", $mysoc->country_code).': '.price($line->total_vat);
				//$moreinfo .= '<br>'.$langs->transcountry("VATRate", $mysoc->country_code).': '.price($line->);
				$moreinfo .= '<br>'.$langs->transcountry("TotalLT1", $mysoc->country_code).': '.price($line->total_localtax1);
				$moreinfo .= '<br>'.$langs->transcountry("TotalLT2", $mysoc->country_code).': '.price($line->total_localtax2);
				$moreinfo .= '<br>'.$langs->transcountry("TotalTTC", $mysoc->country_code).': '.price($line->total_ttc);
				//$moreinfo .= $langs->trans("TotalHT").': '.$line->total_ht;

				$htmlforlines .= '</td>';
				$htmlforlines .= '<td class="right">'.vatrate($line->remise_percent, true).'</td>';
				$htmlforlines .= '<td class="right">'.$line->qty.'</td>';
				$desc = 100-$line->remise_percent;
				$pu = $line->total_ttc * 100 / $desc;
				$htmlforlines .= '<td class="right">'.price($pu/$line->qty, 0, "", 1, 2).'</td>';
				$htmlforlines .= '<td class="right classfortooltip" title="'.$moreinfo.'">'.price($line->total_ttc).'</td>';
			}
			$htmlforlines .= '</tr>'."\n";

            print $htmlforlines;
        }
    }
    else
    {
        print '<tr class="drag drop oddeven"><td class="left"><span class="opacitymedium">'.$langs->trans("Empty").'</span></td><td></td><td></td><td></td><td></td></tr>';
    }
}
else {      // No invoice generated yet
    print '<tr class="drag drop oddeven"><td class="left"><span class="opacitymedium">'.$langs->trans("Empty").'</span></td><td></td><td></td><td></td><td></td></tr>';
}

print '</table>';


// if ($invoice->socid != $conf->global->$constforcompanyid)
// {
    print '<!-- Show customer -->';
    print '<p class="right">';
    print $langs->trans("Customer").': '.$soc->name;

	$constantforkey = 'CASHDESK_NO_DECREASE_STOCK'.$_SESSION["takeposterminal"];
	if (!empty($conf->stock->enabled) && $conf->global->$constantforkey != "1")
	{
		$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
		$warehouse = new Entrepot($db);
		$warehouse->fetch($conf->global->$constantforkey);
		print '<br>'.$langs->trans("Warehouse").': '.$warehouse->ref;
	}

    // Module Adherent
    if (!empty($conf->adherent->enabled))
    {
    	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
    	$langs->load("members");
    	print '<br>'.$langs->trans("Member").': ';
    	$adh = new Adherent($db);
    	$result = $adh->fetch('', '', $invoice->socid);
    	if ($result > 0)
		{
		    $adh->ref = $adh->getFullName($langs);
		    print $adh->getFullName($langs);
		    print '<br>'.$langs->trans("Type").': '.$adh->type;
			if ($adh->datefin)
			{
				print '<br>'.$langs->trans("SubscriptionEndDate").': '.dol_print_date($adh->datefin, 'day');
				if ($adh->hasDelay()) {
					print " ".img_warning($langs->trans("Late"));
				}
			}
			else
			{
				print '<br>'.$langs->trans("SubscriptionNotReceived");
				if ($adh->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
		}
		else
		{
   			print '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
		}
	}
	print '</p>';
// }

if ($action == "search")
{
    print '<center>
	<input type="text" id="search" name="search" onkeyup="Search2();" name="search" style="width:80%;font-size: 150%;" placeholder=' . $langs->trans('Search').'
	</center>';
}

print '</div>';
