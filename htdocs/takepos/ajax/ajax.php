<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
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
 *	\file       htdocs/takepos/ajax/ajax.php
 *	\brief      Ajax search component for TakePos. It search products of a category.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';

$category = GETPOST('category', 'alpha');
$action = GETPOST('action', 'alpha');
$term = GETPOST('term', 'alpha');
$id = GETPOST('id', 'int');
$fam = GETPOST('termfam', 'alpha');


/*
 * View
 */

if ($action == 'getProducts') {
	$object = new Categorie($db);
	$result = $object->fetch($category);
	if ($result > 0) {
		$prods = $object->getObjectsInCateg("product");
		// Removed properties we don't need
		if (is_array($prods) && count($prods) > 0) {
			foreach ($prods as $prod) {
				unset($prod->fields);
				unset($prod->db);
			}
		}
		echo json_encode($prods);
	} else {
		echo 'Failed to load category with id=' . $category;
	}
} elseif ($action == 'search' && $term != '') {
	$constantforkey = 'CASHDESK_ID_WAREHOUSE' . $_SESSION["takeposterminal"];

	$warehouse = new Entrepot($db);
	$warehouse->fetch($conf->global->$constantforkey);

	$batch_data = new Productbatch($db);
	$response = $batch_data->fetch(0, $term, $warehouse->id);
	$qty_facture_init = 0;

	if($id && $id > 0) {
		// Query to get the total quantity of the batch
		$sql_facture_qty = "SELECT SUM(ft.qty) AS qty_facture";
		$sql_facture_qty .= " FROM llx_product_batch AS t";
		$sql_facture_qty .= " INNER JOIN llx_product_stock w on t.fk_product_stock = w.rowid";
		$sql_facture_qty .= " LEFT JOIN llx_product_lot AS pl on pl.fk_product = w.fk_product AND pl.batch = t.batch AND pl.sellby = DATE(t.sellby) AND pl.eatby = DATE(t.eatby)";
		$sql_facture_qty .= " LEFT JOIN llx_facturedet_extrafields AS ftx on ftx.batch_id = t.rowid";
		$sql_facture_qty .= " LEFT JOIN llx_facturedet as ft ON ftx.fk_object = ft.rowid";
		$sql_facture_qty .= " WHERE pl.rowid = ".$term;
		$sql_facture_qty .= " AND w.fk_entrepot = ".$warehouse->id;
		$sql_facture_qty .= " AND ft.fk_facture = ".$id;

		$resql_facture_qty = $db->query($sql_facture_qty);
		$qty_facture_init = $db->fetch_object($resql_facture_qty)->qty_facture;
	}
	if($qty_facture_init >= $batch_data->qty) {
		echo '405';
		die();
	} 

	if ($response > 0) {
		echo json_encode($batch_data);
	} else {
		echo '404';
	}
} elseif ($action == "searchprod" && $term != "") {
	$term = GETPOST('term', 'alpha');
	if (strpos($term, '-')){ //Si el termino de busqueda contiene un guion, se toma solo el primer valor
		$term = explode('-', str_replace(' ', '', GETPOST('term', 'alpha')))[0];
	}

	// Define $filteroncategids, the filter on category ID if there is a Root category defined.
	$filteroncategids = '';
	if ($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0) {	// A root category is defined, we must filter on products inside this category tree
		$object = new Categorie($db);
		//$result = $object->fetch($conf->global->TAKEPOS_ROOT_CATEGORY_ID);
		$arrayofcateg = $object->get_full_arbo('product', $conf->global->TAKEPOS_ROOT_CATEGORY_ID, 1);
		if (is_array($arrayofcateg) && count($arrayofcateg) > 0) {
			foreach($arrayofcateg as $val)
			{
				$filteroncategids .= ($filteroncategids ? ', ' : '').$val['id'];
			}
		}
	}

    $sql = 'SELECT rowid, ref, label, tosell, tobuy FROM '.MAIN_DB_PREFIX.'product as p';
    $sql .= ' WHERE entity IN ('.getEntity('product').')';
    if ($filteroncategids) {
    	$sql.= ' AND rowid IN (SELECT DISTINCT fk_product FROM '.MAIN_DB_PREFIX.'categorie_product WHERE fk_categorie IN ('.$filteroncategids.'))';
    }
    // $sql .= ' AND tosell = 1';
    $sql .= natural_search(array('rowid', 'ref', 'label'), $term);
	// echo $sql;
    $resql = $db->query($sql);
	if ($resql)
	{
	    $rows = array();
	    while ($row = $db->fetch_object($resql)) {
	        $rows[] = $row;
	    }
	    echo json_encode($rows);
	}
	else {
		echo 'Failed to search product : '.$db->lasterror();
	}
} elseif ($action == "opendrawer" && $term != '') {
	require_once DOL_DOCUMENT_ROOT . '/core/class/dolreceiptprinter.class.php';
	$printer = new dolReceiptPrinter($db);
	// chek printer for terminal
	if ($conf->global->{'TAKEPOS_PRINTER_TO_USE' . $term} > 0) {
		$printer->initPrinter($conf->global->{'TAKEPOS_PRINTER_TO_USE' . $term});
		// open cashdrawer
		$printer->pulse();
		$printer->close();
	}
} elseif ($action == "printinvoiceticket" && $term != '' && $id > 0) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/dolreceiptprinter.class.php';
	require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
	$printer = new dolReceiptPrinter($db);
	// check printer for terminal
	if ($conf->global->{'TAKEPOS_PRINTER_TO_USE' . $term} > 0 && $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES' . $term} > 0) {
		$object = new Facture($db);
		$object->fetch($id);
		$ret = $printer->sendToPrinter($object, $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES' . $term}, $conf->global->{'TAKEPOS_PRINTER_TO_USE' . $term});
	}
} elseif ($action == "searchfam" && $fam != '') {
	$sql = 'SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE label LIKE "%' . $fam . '%" AND fk_parent = 0';
	$resql = $db->query($sql);
	$rows = array();
	while ($row = $db->fetch_object($resql)) {
		$rows[] = $row;
	}
	echo json_encode($rows);
} elseif ($action == "searchfam" && $fam == '') {
	$sql = 'SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE label > ""';
	$resql = $db->query($sql);
	$rows = array();
	while ($row = $db->fetch_object($resql)) {
		$rows[] = $row;
	}
	echo json_encode($rows);
} elseif ($action == "listdoctors") {
	$sql = 'SELECT lu.rowid, lu.firstname,lu.lastname FROM llx_user lu WHERE lu.rowid IN (SELECT luu.fk_user FROM llx_usergroup_user luu, llx_usergroup lug WHERE lug.nom LIKE "%medico%")';
	$resql = $db->query($sql);
	$rows = array();
	while ($row = $db->fetch_object($resql)) {
		$rows[] = $row;
	}
	echo json_encode($rows);
} elseif ($action == "terminals") {
	$sql = "SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') terms FROM ".MAIN_DB_PREFIX."const lc, ".MAIN_DB_PREFIX."user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '".$_SESSION["dol_login"]."'";
	$sql .= " AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%'";
	$resql = $db->query($sql);
	$rows = array();
	while ($row = $db->fetch_object($resql)){
		$sql2 = "SELECT lc.value, REPLACE(lc.name, 'CASHDESK_NAME', '') term FROM ".MAIN_DB_PREFIX."const lc WHERE lc.name LIKE 'CASHDESK_NAME".$row->terms."'";
		$resql2 = $db->query($sql2);
		$rows[] = $db->fetch_object($resql2);
	}
	echo json_encode($rows);
} elseif ($action == "setterminal") {
	$terminal = GETPOST('terminal', 'alpha');
	// Comprobar si el usuario actual ya tiene esta terminal asignada
	$sql = "SELECT rowid, terminal FROM ".MAIN_DB_PREFIX."user WHERE login = '".$_SESSION["dol_login"]."'";
	$resql = $db->query($sql);
	$user_current_terminal = "";
	if ($resql && $obj = $db->fetch_object($resql)) {
		$user_current_terminal = $obj->terminal;
	}
	
	if ($user_current_terminal == $terminal) {
		// El usuario ya tiene esta terminal asignada
		echo json_encode(array('success' => false, 'sameUser' => true, 'message' => 'Ya tienes asignada esta terminal', 'username' => $_SESSION["dol_login"], 'user_id' => $obj->rowid));
	} else {
		// Comprobar si la terminal está ocupada por otro usuario
		$sql = "SELECT login, firstname, lastname FROM ".MAIN_DB_PREFIX."user WHERE terminal = '".$terminal."' AND login != '".$_SESSION["dol_login"]."'";
		$resql = $db->query($sql);
		if ($resql->num_rows > 0) {
			$currentUser = $db->fetch_object($resql);
			$username = trim($currentUser->firstname . ' ' . $currentUser->lastname);
			// Use login as fallback if name is empty
			if (empty($username)) {
				$username = $currentUser->login;
			}
			echo json_encode(array('success' => false, 'sameUser' => false, 'username' => $username));
		} else {
			$sql = "UPDATE ".MAIN_DB_PREFIX."user SET terminal = '".$terminal."' WHERE login = '".$_SESSION["dol_login"]."'";
			$db->query($sql);
			echo json_encode(array('success' => true));
		}
	}
}
