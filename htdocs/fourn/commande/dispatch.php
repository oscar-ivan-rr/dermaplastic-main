<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2019 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2016      Florian Henry        <florian.henry@atm-consulting.fr>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under the	terms of the GNU General Public	License	as published by
 * the Free Software Foundation; either	version	2 of the License, or
 * (at your option) any later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file htdocs/fourn/commande/dispatch.php
 * \ingroup commande
 * \brief Page to dispatch receiving
 * 
 * Special handling for gift products:
 * - Products with a price <= 0.2 are considered as gift products
 * - Gift products are excluded from stock movements
 * - Gift products are excluded from the calculation of order status (received/partially received)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/functions.php';
if (!empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';



// Load translation files required by the page
$langs->loadLangs(array("bills", "orders", "sendings", "companies", "deliveries", "products", "stocks", "receptions", "productbatch"));

// if (!empty($conf->productbatch->enabled))
// 	$langs->load('productbatch');

	// Security check
$id = GETPOST("id", 'int');
$ref = GETPOST('ref');
$lineid = GETPOST('lineid', 'int');
$action = GETPOST('action', 'aZ09');
if ($user->socid)
	$socid = $user->socid;
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

if (empty($conf->stock->enabled)) {
	accessforbidden();
}

$hookmanager->initHooks(array('ordersupplierdispatch'));

// Recuperation de l'id de projet
$projectid = 0;
if ($_GET["projectid"])
	$projectid = GETPOST("projectid", 'int');

$object = new CommandeFournisseur($db);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$result = $object->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($action == 'checkdispatchline' && !((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error = 0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (!$result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (!$error)
	{
		$result = $supplierorderdispatch->setStatut(1);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}

	if (!$error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'uncheckdispatchline' && !((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error = 0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (!$result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (!$error)
	{
		$result = $supplierorderdispatch->setStatut(0);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'denydispatchline' && !((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error = 0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (!$result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (!$error)
	{
		$result = $supplierorderdispatch->setStatut(2);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
			$action = '';
		}
	}
	if (!$error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if($action == 'createOC' && $user->rights->fournisseur->commande->receptionner){
	
	$ocLine = GETPOST('oc');
	$lines = explode(",", GETPOST('lines'));
	$qties = explode(",", GETPOST('qty'));

	if(empty($ocLine)){
		setEventMessages($langs->trans('OCTargetMissing'), null, 'errors');
		$error++;
	}

	if(empty(GETPOST('lines'))){
		setEventMessages($langs->trans('LinesMissing'), null, 'errors');
		$error++;	
	}else{
		if(empty(str_replace(",","",GETPOST('qty')))){
			setEventMessages($langs->trans('QtyMissing'), null, 'errors');
			$error++;
		}
	}

	if(!$error){
		//Formato de Array
		unset($lines[count($lines)-1]);
		unset($qties[count($qties)-1]);
		foreach($lines as $k => $line){
			$exp = explode("_", $line);
			$lines[$exp[0]] = $exp[1];
			$qties[$exp[0]] = $qties[$k];
			unset($lines[$k]);
			unset($qties[$k]);
		}

		$lineObj = new Product($db);
		$ocObj = new CommandeFournisseur($db);
		$ocObj->fetch($ocLine);

		foreach($object->lines as $lineOrig){
			//Si está alguno de los hijos está seleccionado, se añadirá al OC de Destino
			if(array_key_exists($lineOrig->fk_product, $lines)){
				$qty = $qties[$lineOrig->fk_product];
				$res = $ocObj->addline($lineOrig->ref, $lineOrig->pu_ht, $qty, $lineOrig->tva_tx, 0.0, 0.0, $lineOrig->fk_product,
								0, '', 0.0, 'HT', 0.0, 0, 0, false, null, null, 0, $lineOrig->fk_unit);
				if($res <= 0){
					$errLine++;
				}else{ //Si se pudo transferir a la OC de destino, se actualiza la descripción del Producto (En ["Ref de la OC. de Destino"])
					$sqlDesc = "UPDATE ".MAIN_DB_PREFIX."product SET fk_oc = ".$ocObj->id." WHERE rowid = ".$lineOrig->fk_product;
					$resDesc = $db->query($sqlDesc);
					if(!$resDesc){
						setEventMessages($langs->trans('ErrorDescription'), null, 'errors');
					}
				}
			}
		}

		//Si no hubo errores al añadir lines al nuevo OC, se genera registro de Objetos Relacionados
		if(!$errLine){
			$sqlVal = "SELECT rowid FROM ".MAIN_DB_PREFIX."element_element";
			$sqlVal .= " WHERE fk_source = ".$object->id." AND sourcetype = 'order_supplier' AND fk_target = ".$ocLine." AND targettype = 'order_supplier'";
			if($resVal = $db->query($sqlVal)){
				if($db->num_rows($resVal) == 0){
					$sqlElement = "INSERT INTO ".MAIN_DB_PREFIX."element_element (fk_source, sourcetype, fk_target, targettype)";
					$sqlElement .= " VALUES (".$object->id.", 'order_supplier', ".$ocLine.", 'order_supplier')";
					$res = $db->query($sqlElement);
					if(!$res){
						setEventMessages($langs->trans('ErrorElement'), null, 'errors');
					}
				}
			}
		}
	}	
}

if ($action == 'dispatch' && $user->rights->fournisseur->commande->receptionner) {
	$error = 0;

	// Add a file-based lock to prevent simultaneous dispatch operations
	$lockFile = DOL_DATA_ROOT . '/dispatch_lock_' . $id . '.lock';
	
	// Try to acquire a lock
	$fp = fopen($lockFile, 'w+');
	if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
		// Could not get the lock, another process is already dispatching
		setEventMessages($langs->trans("DispatchInProgress"), null, 'warnings');
		if ($fp) fclose($fp);
		header("Location: dispatch.php?id=".$id);
		exit();
	}
	
	$db->begin();

	$pos = 0;

	$lineprod = array();
	$qtyline = array();
	foreach ($_POST as $key => $value) {
		if (explode('_', $key)[0] == 'lineprod') {
			$lineprod[] = $value;
		}
		if (explode('_', $key)[1] == 'ordered') {
			$ordered[explode('_', $key)[3]] = (int)$value;
		}
		if (explode('_', $key)[1] == 'dispatched') {
			$dispatched[explode('_', $key)[3]] = (int)$value;
		}
	}

	foreach ($_POST as $key => $value) {
		if (explode('_', $key)[0] == 'qty' && is_numeric(explode('_', $key)[1])) {
			foreach ($lineprod as $key2 => $value2) {
				if (explode('_', $key)[2] == $value2) {
					$qtyline[$value2] += (int)$value;
					if ($qtyline[$value2] > $ordered[$value2] - $dispatched[$value2]) {
						$error++;
						setEventMessages("La cantidad a recibir no puede ser mayor a la cantidad ordenada", null, 'errors');
					}
				}
			}
		}
	}

	foreach ($_POST as $key => $value)
	{		
		if (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg))
		{
			$pos++;

			// eat-by date dispatch
			// $numline=$reg[2] + 1; // line of product
			$numline = $pos;
			$prod = 'product_batch_'.$reg[1].'_'.$reg[2];
			$qty = 'qty_'.$reg[1].'_'.$reg[2];
			$ent = 'entrepot_'.$reg[1].'_'.$reg[2];
			$pu = 'pu_'.$reg[1].'_'.$reg[2];
			$fk_commandefourndet = 'fk_commandefourndet_'.$reg[1].'_'.$reg[2];
			$lot = 'lot_number_'.$reg[1].'_'.$reg[2];
			$dDLUO = dol_mktime(12, 0, 0, $_POST['dluo_'.$reg[1].'_'.$reg[2].'month'], $_POST['dluo_'.$reg[1].'_'.$reg[2].'day'], $_POST['dluo_'.$reg[1].'_'.$reg[2].'year']);
			$dDLC = dol_mktime(12, 0, 0, $_POST['dlc_'.$reg[1].'_'.$reg[2].'month'], $_POST['dlc_'.$reg[1].'_'.$reg[2].'day'], $_POST['dlc_'.$reg[1].'_'.$reg[2].'year']);

			// We ask to move a qty
			if (GETPOST($qty) > 0) {
				if (!(GETPOST($ent, 'int') > 0)) {
					dol_syslog('No dispatch for line '.$key.' as no warehouse was chosen.');
					$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' '.($numline).'-'.($reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}

				if (!(GETPOST($lot, 'alpha') && $dDLC)) {
					dol_syslog('No dispatch for line '.$key.' as serial/eat-by/sellby date are not set');
					$text = $langs->transnoentities('atleast1batchfield').', '.$langs->transnoentities('Line').' '.($numline).'-'.($reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error++;
				}

				if (!$error) {
					// Get the commandefourndet_id 
					$commandefourndet_id = GETPOST($fk_commandefourndet, 'int');
					
					if (empty($commandefourndet_id)) {
						$commandefourndet_id = 0; // Set to 0 if not available
					}
					
					// Check if this is a gift product (price <= 0.2)
					$isGift = false;
					$giftPriceThreshold = 0.2; // Products with price <= 0.2 are considered gifts
					
					if ($commandefourndet_id > 0) {
						$sql = "SELECT subprice, total_ttc FROM ".MAIN_DB_PREFIX."commande_fournisseurdet WHERE rowid = ".$commandefourndet_id;
						$resql = $db->query($sql);
						if ($resql && $obj = $db->fetch_object($resql)) {
							if (($obj->subprice <= $giftPriceThreshold || $obj->total_ttc == 0) && $obj->qty == 1) {
								$isGift = true;
								// Mark as gift but don't skip - we want to include gift products in stock movements
							}
						}
					}
					
					// Add a comment indicating if this is a gift product
					$comment = GETPOST('comment');
					if ($isGift) {
						$comment = $langs->trans("GiftProduct") . " - " . $comment;
					}
					
					$result = $object->dispatchProduct($user, GETPOST($prod, 'int'), GETPOST($qty), GETPOST($ent, 'int'), GETPOST($pu), $comment, $dDLC, $dDLUO, preg_replace("/[^a-zA-Z0-9]+/", "-", GETPOST($lot, 'alpha')), $commandefourndet_id, $notrigger, $isGift);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				}
			}
		}
	}

	if (!$notrigger && !$error) {
		global $conf, $langs, $user;
		// Call trigger

		$result = $object->call_trigger('ORDER_SUPPLIER_DISPATCH', $user);
		// End call triggers

		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}

	if ($result >= 0 && !$error) {
		$db->commit();
		
		// Release the lock
		if ($fp) {
			flock($fp, LOCK_UN);
			fclose($fp);
			@unlink($lockFile);
		}
		
		header("Location: dispatch.php?id=".$id);
		exit();
	} else {
		$db->rollback();
		
		// Release the lock on error too
		if ($fp) {
			flock($fp, LOCK_UN);
			fclose($fp);
			@unlink($lockFile);
		}
	}
}

/*
 * View
 */

$now = dol_now('tzuser');

$form = new Form($db);
$formproduct = new FormProduct($db);
$warehouse_static = new Entrepot($db);
$supplierorderdispatch = new CommandeFournisseurDispatch($db);

$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $langs->trans("Order"), $help_url, '', 0, 0, array('/fourn/js/lib_dispatch.js'));

if ($object->statuts != CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
	// We don't need this code anymore as we've implemented a more accurate check later
	// The status will be updated based on the comparison between ordered and received quantities
}

if ($id > 0 || !empty($ref)) {
	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = ordersupplier_prepare_head($object);

	$title = $langs->trans("SupplierOrder");
	dol_fiche_head($head, 'dispatch', $title, -1, 'order');


	// Supplier order card

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref supplier
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
	// Project
	if (!empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref .= '<br>'.$langs->trans('Project').' ';
	    if ($user->rights->fournisseur->commande->creer)
	    {
	        if ($action != 'classify') {
	            //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				$morehtmlref .= ' : ';
            }
	        if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
	    } else {
	        if (!empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
	            $morehtmlref .= $proj->ref;
	            $morehtmlref .= '</a>';
	        } else {
	            $morehtmlref .= '';
	        }
	    }
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Date
	if ($object->date_commande) {
		print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td>';
		print dol_print_date($object->date, "dayhour")."\n";
		print "</td></tr>";
	}
	
	if ($object->methode_commande_id > 0) {
		if ($object->methode_commande) {
			print '<tr><td>'.$langs->trans("Method").'</td><td>'.$object->getInputMethod().'</td></tr>';
		}
	}

	// Author
	print '<tr><td class="titlefield">'.$langs->trans("AuthorRequest").'</td>';
	print '<td>'.$author->getNomUrl(1, '', 0, 0, 0).'</td>';
	print '</tr>';

    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

	print "</table>";

	print '</div>';

	// if ($mesg) print $mesg;
	print '<br>';

	$disabled = 1;
	if (!empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) $disabled = 0;

	// Line of orders
	if ($object->statut <= CommandeFournisseur::STATUS_ACCEPTED || $object->statut >= CommandeFournisseur::STATUS_CANCELED) {
		print '<br><span class="opacitymedium">'.$langs->trans("OrderStatusNotReadyToDispatch").'</span>';
	}

	if ($object->statut == CommandeFournisseur::STATUS_ORDERSENT
		|| $object->statut == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY
		|| $object->statut == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
		$entrepot = new Entrepot($db);
		$listwarehouses = $entrepot->list_array(1);

		print '<style>
		.processing-overlay {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.5);
			z-index: 9999;
		}
		.processing-message {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			background-color: white;
			padding: 20px;
			border-radius: 5px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
			text-align: center;
		}
		.processing-spinner {
			border: 6px solid #f3f3f3;
			border-top: 6px solid #3498db;
			border-radius: 50%;
			width: 50px;
			height: 50px;
			margin: 0 auto 15px auto;
			animation: spin 2s linear infinite;
		}
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		</style>

		<div id="processingOverlay" class="processing-overlay">
			<div class="processing-message">
				<div class="processing-spinner"></div>
				<div id="processingText">'.$langs->trans("ProcessingRequest").'</div>
			</div>
		</div>';

		if (empty($conf->reception->enabled))
			print '<form method="POST" action="dispatch.php?id='.$object->id.'" id="dispatchForm" onsubmit="showProcessingMessage();">';
		else 
			print '<form method="post" action="'.dol_buildpath('/reception/card.php', 1).'?originid='.$object->id.'&origin=supplierorder" id="dispatchForm" onsubmit="showProcessingMessage();">';

		print '<input type="hidden" name="token" value="'.newToken().'">';
		if (empty($conf->reception->enabled))
			print '<input type="hidden" name="action" value="dispatch">';
		else 
			print '<input type="hidden" name="action" value="create">';

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		// Set $products_dispatched with qty dispatched for each product id
		$products_dispatched = array();
		$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as l on l.rowid = cfd.fk_commandefourndet";
		$sql .= " WHERE cfd.fk_commande = ".$object->id;
		$sql .= " GROUP BY l.rowid, cfd.fk_product";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($i < $num) {
					$objd = $db->fetch_object($resql);
					$products_dispatched[$objd->rowid] = price2num($objd->qty, 5);
					$i++;
				}
			}
			$db->free($resql);
		}

		$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.total_ttc, l.remise_percent, l.ref AS sref, SUM(l.qty) as qty,";
		$sql .= " p.ref, p.label, p.tobatch, p.fk_default_warehouse";

        // Enable hooks to alter the SQL query (SELECT)
        $parameters = array();
        $reshook = $hookmanager->executeHooks(
            'printFieldListSelect',
            $parameters,
            $object,
            $action
        );
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        $sql .= $hookmanager->resPrint;

		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product=p.rowid";
		$sql .= " WHERE l.fk_commande = ".$object->id;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			$sql .= " AND l.product_type = 0";

        // Enable hooks to alter the SQL query (WHERE)
        $parameters = array();
        $reshook = $hookmanager->executeHooks(
            'printFieldListWhere',
            $parameters,
            $object,
            $action
        );
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        $sql .= $hookmanager->resPrint;

		$sql .= " GROUP BY p.ref, p.label, p.tobatch, l.rowid, l.fk_product, l.subprice, l.remise_percent, p.fk_default_warehouse"; // Calculation of amount dispatched is done per fk_product so we must group by fk_product
		$sql .= " ORDER BY l.rowid";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				print '<tr class="liste_titre">';

				//Checkbox General (Todos los Productos)
				if(!isInternalHubSupplier($object->socid)) print '<td ><input type="checkbox" name="checkAll" id="checkAll"></td>';

				print '<td>'.$langs->trans("Description").'</td>';
				// if (!empty($conf->productbatch->enabled))
				// {
					print '<td class="dispatch_batch_number_title">'.$langs->trans("batch_number").'</td>';
					print '<td class="dispatch_dluo_title">'.$langs->trans("EatByDate").'</td>';
					print '<td class="dispatch_dlc_title">'.$langs->trans("SellByDate").'</td>';
				// }
				// else
				// {
				// 	print '<td></td>';
				// 	print '<td></td>';
				// 	print '<td></td>';
				// }
				print '<td class="right">'.$langs->trans("SupplierRef").'</td>';
				print '<td class="right">'.$langs->trans("QtyOrdered").'</td>';
				print '<td class="right">'.$langs->trans("QtyDispatchedShort").'</td>';
				print '<td class="right">'.$langs->trans("QtyToReceiveShort").'</td>';
				print '<td width="32"></td>';

				if (!empty($conf->global->SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT)) {
					if (empty($conf->multicurrency->enabled) && empty($conf->dynamicprices->enabled)) {
						print '<td class="right">'.$langs->trans("Price").'</td>';
						print '<td class="right">'.$langs->trans("ReductionShort").' (%)</td>';
						print '<td class="right">'.$langs->trans("UpdatePrice").'</td>';
					}
				}

				print '<td align="right">'.$langs->trans("Warehouse").'</td>';

                // Enable hooks to append additional columns
                $parameters = array();
                $reshook = $hookmanager->executeHooks(
                    'printFieldListTitle',
                    $parameters,
                    $object,
                    $action
                );
                if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                print $hookmanager->resPrint;

				print "</tr>\n";
			}

			$nbfreeproduct = 0; // Nb of lins of free products/services
			$nbproduct = 0; // Nb of predefined product lines to dispatch (already done or not) if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is off (default)
									// or nb of line that remain to dispatch if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is on.

			$prodObj = new Product($db);

			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				// On n'affiche pas les produits libres
				if (!$objp->fk_product > 0) {
					$nbfreeproduct++;
				} else {
					$remaintodispatch = price2num($objp->qty - ((float) $products_dispatched[$objp->rowid]), 5); // Calculation of dispatched
					if ($remaintodispatch < 0)
						$remaintodispatch = 0;

					if ($remaintodispatch) {
						$nbproduct++;

						$suffix = '_0_' . $i;

						print "\n";
						print '<!-- Line to dispatch '.$suffix.' -->'."\n";
						// hidden fields for js function
						print '<input id="qty_ordered'.$suffix.'" name="qty_ordered'.$suffix.'" type="hidden" value="'.$objp->qty.'">';
						print '<input id="qty_dispatched'.$suffix.'" name="qty_dispatched'.$suffix.'" type="hidden" value="'.(float) $products_dispatched[$objp->rowid].'">';
						$lineprod = explode('_', $suffix)[2];
						print '<input type="hidden" name="lineprod_'.$lineprod.'" value="'.$lineprod.'">';
						print '<tr class="oddeven">';

						//Validación si un Producto tiene Relacionado una OC en su descripción
						$prodObj->fetch($objp->fk_product);
						$ocOrig = new CommandeFournisseur($db);
						$ocOrig->fetch($prodObj->fk_oc);

						$linktoprod = '<a href="' . DOL_URL_ROOT . '/product/fournisseurs.php?id=' . $objp->fk_product . '">' . img_object($langs->trans("ShowProduct"), 'product') . ' ' . $objp->ref . '</a>';
						$linktoprod .= ' - ' . $objp->label . "\n";

						// Check if this is a gift product
						$isGift = false;
						if ($objp->subprice <= 0.2 || $objp->total_ttc == 0) {
							$isGift = true;
							$linktoprod .= ' <span class="badge badge-success">' . $langs->trans("GiftProduct") . '</span>';
						}

						//Se concatena Descripción con OC
						if ($ocOrig->id > 0) {
							$linktoprod .= '<a href="' . DOL_URL_ROOT . '/fourn/commande/card.php?id=' . $ocOrig->id . '">' . '(En ' . $ocOrig->ref . ')</a>';
						}

						//Checkbox Individual para Cada Producto
						if(!isInternalHubSupplier($object->socid)){
							print '<td ><input type="checkbox" class="chkInd" name="' . $objp->fk_product . '_' . $objp->qty . '" value="' . $objp->fk_product . '" ';
							//Si su cantidad a enviar es >= a 0, o tiene relacionado un OC en su descripción, no se puede seleccionar
							if ($remaintodispatch <= 0 || $ocOrig->id > 0) {
								print 'disabled';
							}
							print '></td>';
						} 


						print '<td>';
						print $linktoprod;
						print "</td>";
						print '<td class="dispatch_batch_number"></td>';
						print '<td class="dispatch_dluo"></td>';
						print '<td class="dispatch_dlc"></td>';

						// Define unit price for PMP calculation
						$up_ht_disc = $objp->subprice;
						if (!empty($objp->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP))
							$up_ht_disc = price2num($up_ht_disc * (100 - $objp->remise_percent) / 100, 'MU');

						// Supplier ref
						print '<td class="right">' . $objp->sref . '</td>';

						// Qty ordered
						print '<td class="right" id="qtyOrder' . $suffix . '" name="qtyOrder' . $suffix . '">' . $objp->qty . '</td>';

						// Already dispatched
						print '<td class="right" id="qtyDisp' . $suffix . '" name="qtyDisp' . $suffix . '">' . $products_dispatched[$objp->rowid] . '</td>';

						$type = 'batch';
						print '<td class="right">';
						print '</td>'; // Qty to dispatch
						print '<td>';
						print '</td>'; // Dispatch column
						print '<td></td>'; // Warehouse column

						// Enable hooks to append additional columns
						$parameters = array(
							'is_information_row' => true, // allows hook to distinguish between the
							// rows with information and the rows with
							// dispatch form input
							'objp' => $objp
						);
						$reshook = $hookmanager->executeHooks(
							'printFieldListValue',
							$parameters,
							$object,
							$action
						);
						if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						print $hookmanager->resPrint;

						print '</tr>';

						if(!isInternalHubSupplier($object->socid)){

							print '<tr class="oddeven" name="' . $type . $suffix . '">';
							print '<td>';
							print '<input name="fk_commandefourndet' . $suffix . '" type="hidden" value="' . $objp->rowid . '">';
							print '<input name="product_batch' . $suffix . '" type="hidden" value="' . $objp->fk_product . '">';
	
							print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
							if (!empty($conf->global->SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT)) // Not tested !
							{
								print $langs->trans("BuyingPrice") . ': <input class="maxwidth75" name="pu' . $suffix . '" type="text" value="' . price2num($up_ht_disc, 'MU') . '">';
							} else {
								print '<input class="maxwidth75" name="pu' . $suffix . '" type="hidden" value="' . price2num($up_ht_disc, 'MU') . '">';
							}
	
							print '</td>';
	
							print '<td></td>';
							print '<td>';
							print '<input type="text" class="inputlotnumber quatrevingtquinzepercent" id="lot_number' . $suffix . '" name="lot_number' . $suffix . '" value="' . GETPOST('lot_number' . $suffix) . '">';
							print '</td>';
							print '<td class="nowraponall">';
							$dlcdatesuffix = dol_mktime(0, 0, 0, GETPOST('dlc' . $suffix . 'month'), GETPOST('dlc' . $suffix . 'day'), GETPOST('dlc' . $suffix . 'year'));
							print $form->selectDate($dlcdatesuffix, 'dlc' . $suffix, '', '', 1, '');
							print '</td>';
							print '<td class="nowraponall">';
							$now = dol_now('tzuser'); // Obtiene timestamp en la zona horaria del usuario
							$dluodatesuffix = dol_mktime(0, 0, 0, GETPOST('dluo' . $suffix . 'month'), GETPOST('dluo' . $suffix . 'day'), GETPOST('dluo' . $suffix . 'year'));
							print $form->selectDate($now, 'dluo' . $suffix, '', '', 1, '');
							print '</td>';
							print '<td colspan="3">&nbsp</td>'; // Supplier ref + Qty ordered + qty already dispatched
							// Qty to dispatch
							print '<td class="right">';
							print '<input id="qty' . $suffix . '" name="qty' . $suffix . '" type="number" class="width50 right" value="0">';
							print '</td>';
	
							print '<script>
									$(document).ready(function() {
										$("input[name^=\'qty_\'][name$=\''.$i.'\']").on("change", function() {
											// Find qtyOrder that corresponds to this input
											let qorder = Number($("td[name^=\'qtyOrder\'][name$=\''.$i.'\']").text());
											let qdisp = Number($("td[name^=\'qtyDisp\'][name$=\''.$i.'\']").text());
											let sum = Array.from(document.querySelectorAll(\'input\'))
											.filter(input => input.name.startsWith(\'qty_\') && input.name.endsWith(\''.$i.'\') && !input.name.includes(\'qty_dispatched\') && !input.name.includes(\'qty_ordered\'))
											.reduce((total, input) => total + Number(input.value), 0);
											sum += qdisp;
											console.log(\'sum = \'+sum);
											if (sum > qorder) {
												alert("La cantidad a recibir no puede ser mayor que la cantidad pedida.");
												// Se resta 1 al ultimo input modificado
												$(this).val(Number($(this).val()) - 1);
											}
										});
									});
							</script>';
	
							print '<td>';
							$type = 'batch';
							print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
							print '</td>';
	
							if (!empty($conf->global->SUPPLIER_ORDER_CAN_UPDATE_BUYINGPRICE_DURING_RECEIPT)) {
								if (empty($conf->multicurrency->enabled) && empty($conf->dynamicprices->enabled)) {
									// Price
									print '<td class="right">';
									print '<input id="pu' . $suffix . '" name="pu' . $suffix . '" type="text" size="8" value="' . price((GETPOST('pu' . $suffix) != '' ? GETPOST('pu' . $suffix) : $up_ht_disc)) . '">';
									print '</td>';
	
									// Discount
									print '<td class="right">';
									print '<input id="pu' . $suffix . '" name="dto' . $suffix . '" type="text" size="8" value="' . (GETPOST('dto' . $suffix) != '' ? GETPOST('dto' . $suffix) : '') . '">';
									print '</td>';
	
									// Save price
									print '<td class="center">';
									print '<input class="flat checkformerge" type="checkbox" name="saveprice' . $suffix . '" value="' . (GETPOST('saveprice' . $suffix) != '' ? GETPOST('saveprice' . $suffix) : '') . '">';
									print '</td>';
								}
							}
	
							// Warehouse
							print '<td class="right">';
							if (count($listwarehouses) > 1) {
								if ($user->rights->stock->show_all_warehouses) {
									print $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix) ? GETPOST("entrepot" . $suffix) : ($objp->fk_default_warehouse ? $objp->fk_default_warehouse : ''), "entrepot" . $suffix, '', 1, 0, $objp->fk_product, '', 1, 0, null, 'csswarehouse' . $suffix);	
								}
								else
									print $formproduct->selectWarehousesOC($user->fk_warehouse, "entrepot" . $suffix, $objp->fk_product, '', 1, 1, 1, null, 'csswarehouse' . $suffix);
							} elseif (count($listwarehouses) == 1) {
								print $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix) ? GETPOST("entrepot" . $suffix) : ($objp->fk_default_warehouse ? $objp->fk_default_warehouse : ''), "entrepot" . $suffix, '', 0, 0, $objp->fk_product, '', 1, 0, null, 'csswarehouse' . $suffix);
							} else {
								$langs->load("errors");
								print $langs->trans("ErrorNoWarehouseDefined");
							}
							print "</td>\n";
	
							// Enable hooks to append additional columns
							$parameters = array(
								'is_information_row' => false // this is a dispatch form row
							);
							$reshook = $hookmanager->executeHooks(
								'printFieldListValue',
								$parameters,
								$object,
								$action
							);
							if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
							print $hookmanager->resPrint;
	
							print "</tr>\n";
						} 
					}
				}
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print "</table>\n";
		print '</div>';

		if ($nbproduct && !isInternalHubSupplier($object->socid)) { 
			print '<div class="center">';
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			// modified by hook
			if (empty($reshook)) {
				empty($conf->reception->enabled) ? $dispatchBt = $langs->trans("DispatchVerb") : $dispatchBt = $langs->trans("Receive");

				print '<br><input type="submit" class="button" name="dispatch" value="' . dol_escape_htmltag($dispatchBt) . '"';
				if (count($listwarehouses) <= 0)
					print ' disabled';
				print '>';

				if (empty($conf->reception->enabled)) {
					print $langs->trans("Comment") . ' : ';
					print '<input type="text" class="minwidth400" maxlength="128" name="comment" value="';
					print $_POST["comment"] ? GETPOST("comment") : $langs->trans("DispatchSupplierOrder", $object->ref);
					print '" class="flat"><br>';
				}
			}
			print '</div>';
		}

		// Message if nothing to dispatch
		if (!$nbproduct) {
			//print "<br>\n";
			if (empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED))
				print '<div class="opacitymedium">' . $langs->trans("NoPredefinedProductToDispatch") . '</div>'; // No predefined line at all
			else
				print '<div class="opacitymedium">' . $langs->trans("NoMorePredefinedProductToDispatch") . '</div>'; // No predefined line that remain to be dispatched.

			$createOC = $langs->trans("createOC");

			//Botón Creación de OCs
			print '<div class="tabsAction right">';
			print '<a id="createOCButton" class="butAction" onclick="return createOCS();" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;">' . $createOC . '</a>';
			// Boton Imprimir etiqutas
			print '<a id="printTagsButton" class="butAction" style="display:none;" onclick="return printTags();" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">' . $langs->trans("PrintTag") . '</a>';
			// Checkbox para cerrar orden de compra
			print '<br>';
			$checkboxlabel = $langs->trans("CloseReceivedSupplierOrdersAutomatically", $langs->transnoentitiesnoconv('StatusOrderReceivedAll'));
			print '<input type="checkbox" checked="checked" name="closeopenorder"> ' . $checkboxlabel;
			print '</div>';

			//Modal Creación de OCs
			print '<div hidden id="createOC_line_dialog" name="createOC_line_dialog" title="' . $createOC . '">';
			print '<p> Seleccionar una Orden de Compra </p>';
			print $form->select_oc(GETPOST('select_oc'));
			print '<table></table>';
			print '</div>';

			print '<script>';
			print
			"
				function createOCS(){
					$( \"#createOC_line_dialog\" ).dialog({
						autoOpen:false,
						width:400,
						height:200,
						buttons: [
							{
								text: \"Si\",
								click: function() {
										var l = '" . DOL_URL_ROOT . "/fourn/commande/dispatch.php?id=" . $id . "&action=createOC&lines=';
										var q = [];
										var contQty = 0;
										var linkQty = '&qty='
										$('.chkInd').each(function(i,e){
											if($(e).is(':checked'))
											{
												l += $(e).attr('name')+',';
												linkQty += $('#qty_0_'+contQty).val()+',';
											}
											contQty++;
										});
										const oc = $('#selectselect_oc').val();
										l += '&oc='+oc;
										l += linkQty;
										window.location.href = l;
								}
							},
							{
								text: \"No\",
								click: function() {
										$( this ).dialog( \"close\" );
									}
							}
							
						]
					});
					
					$( \"#createOC_line_dialog\" ).dialog(\"open\");
					return false;
				}
				";
			print '</script>';
		}
		print '</form>';
	}

	dol_fiche_end();


	// List of lines already dispatched
	$sql = "SELECT p.ref, p.label,";
	$sql .= " e.rowid as warehouse_id, e.ref as entrepot,";
	$sql .= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status, cfd.datec, cfd.fk_commandefourndet,";
	$sql .= " l.subprice, l.total_ttc";
	if ($conf->reception->enabled)$sql .= " ,cfd.fk_reception, r.date_delivery";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p,";
	$sql .= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON cfd.fk_entrepot = e.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as l ON cfd.fk_commandefourndet = l.rowid";
	if ($conf->reception->enabled)$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."reception as r ON cfd.fk_reception = r.rowid";
	$sql .= " WHERE cfd.fk_commande = ".$object->id;
	$sql .= " AND cfd.fk_product = p.rowid";
	$sql .= " ORDER BY cfd.rowid ASC";

	$resql = $db->query($sql);
	$aux = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num > 0) {
			$totalReceived = array();
			$totalOrdered = array();
			
			$giftPriceThreshold = 0.2; // Products with price <= 0.2 are considered gifts
			
			$sqlOrdered = "SELECT l.fk_product, SUM(l.qty) as qty_ordered FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
			$sqlOrdered .= " WHERE l.fk_commande = ".$object->id;
			$sqlOrdered .= " GROUP BY l.fk_product";
			$resOrdered = $db->query($sqlOrdered);
			if ($resOrdered) {
				while ($objOrdered = $db->fetch_object($resOrdered)) {
					$totalOrdered[$objOrdered->fk_product] = $objOrdered->qty_ordered;
				}
			}
			
			$sqlReceived = "SELECT cfd.fk_product, SUM(cfd.qty) as qty_received 
                            FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd
                            JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as l ON (cfd.fk_commandefourndet = l.rowid)";
			$sqlReceived .= " WHERE cfd.fk_commande = ".$object->id;
			$sqlReceived .= " GROUP BY cfd.fk_product";
			$resReceived = $db->query($sqlReceived);
			if ($resReceived) {
				while ($objReceived = $db->fetch_object($resReceived)) {
					$totalReceived[$objReceived->fk_product] = $objReceived->qty_received;
				}
			}
			
			$allProductsReceived = true;
			foreach ($totalOrdered as $productId => $orderedQty) {
				$receivedQty = isset($totalReceived[$productId]) ? $totalReceived[$productId] : 0;
				if ($receivedQty < $orderedQty) {
					$allProductsReceived = false;
					break;
				}
			}
			
			if ($allProductsReceived) {
				$comentario = $langs->trans("DispatchSupplierOrder", $object->ref);
				$result = $object->calcAndSetStatusDispatch($user, 1, $comentario);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			} else {
				$comentario = $langs->trans("DispatchSupplierOrder", $object->ref);
				$result = $object->calcAndSetStatusDispatch($user, 0, $comentario);
				if ($result < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}

			//Modal Impresión de Etiquetas
			print '<div hidden id="printTags_line_dialog" name="printTags_line_dialog" title="'.$langs->trans("PrintTag").'">';
			print '<p> ¿Desea Confirmar las Cantidades a Imprimir? </p>';
			print '<table class="centpercent noborder" id="tableTags" name="tableTags">';
			print '<tr class="liste_titre"> <th style="width: 70%;"> Producto </th> <th>Lote</th> <th>Fecha de Caducidad</th> <th> Etiquetas a Imprimir </th> </tr>';
			print '</table>';
			print '</div>';
			
			//Modal Asignación de Lotes
			print '<div hidden id="batch_line_dialog" name="batch_line_dialog" title="Asignación lotes">';
			print '<p> Asigne un numero de lote y la fecha de caducidad para cada uno de los productos recibidos. </p>';
			print ' <div id="batchalert" style="display:none;" class="alert">
						<span class="closebtn" onclick="this.parentElement.style.display="none";">&times;</span>
						Error: No se puede asignar uno de los lotes debido a que ya existe uno con el mismo numero y fecha de caducidad.
					</div> ';
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";
			print '<input type="hidden" name="action" value="correct_stock">';
			print '<input type="hidden" id="numlines" value="'.$num.'">';
			print '<table class="centpercent noborder" id="tableBatchs" name="tableBatchs">';
			print '<tr class="liste_titre"> <th> Producto </th> <th> Almacen </th> <th> Cantidad </th> <th> Lote </th> <th> Fecha de caducidad </th> <th> Fecha de ingreso </th> </tr>';

			print '</table>';
			print '</form>';
			print '</div>';

			print '<script>';
			print "
			var ajaxCounterTags = 0;
			var ajaxCounterBatch = 0;
				function printTags(){
					$( \"#printTags_line_dialog\" ).dialog({
						autoOpen:false,
						width:800,
						height:400,
						buttons: [
							{
								text: \"Confirmar\",
								id: \"confirmButton\",
								click: function() {

									//Send Tags									
									let l = '" . DOL_URL_ROOT . "/fourn/commande/pdf_tags.php';
									let tags = '';
									$('#tableTags tr').each(function(i,e){
										if(i > 0){
											const fk_prod = $(e).attr(\"name\").split('_')[0];
											const qty = $(e).attr(\"name\").split('_')[1];
											const batch = $(e).attr(\"name\").split('_')[2];
											tags += fk_prod +'_'+ qty +'_'+ batch +',';
										}
									});
									l += '?tags=' + tags;

									Object.assign(document.createElement('a'), { target: '_blank', href: l}).click();
								}
							},
							{
								text: \"Cancelar\",
								click: function() {
										$( this ).dialog( \"close\" );
									}
							}
							
						],
						open: function(event, ui) {
							// Deshabilitar el botón de Confirmar al abrir el diálogo si las peticiones ajax no han finalizado
							if (ajaxCounterTags == 0 || ajaxCounterTags < $(\".chkIndPrint:checked\").length) {
								$(\"#confirmButton\").hide();
							}
						},
						close: function(event, ui) {
							location.reload();
						}
					});
					
					$( \"#printTags_line_dialog\" ).dialog(\"open\");
					return false;
				}

				//Ajax para asignar lotes
				function batchAsign(){
					$( \"#batch_line_dialog\" ).dialog({
						autoOpen:false,
						width:1000,
						height:400,
						buttons: [
							{
								text: \"Confirmar\",
								id: \"confirmButton\",
								click: function() {
									//Deshabilitamos el boton para evitar multiples clicks
									$(\"#confirmButton\").prop(\"disabled\", true);
									
									// Show processing message
									document.getElementById(\"processingOverlay\").style.display = \"block\";
									document.getElementById(\"processingText\").innerHTML = \"" . $langs->trans("ProcessingRequest") . "<br>" . $langs->trans("PleaseWait") . "\";
									
									const batchData = $('#tableBatchs tr:gt(0)').map(function(i, e) {
										const td = $(e).find('td');
										return {
											idCommande : '" . $id . "',
											idProd: td.eq(0).attr(\"name\"),
											idWarehouse: td.eq(1).attr(\"name\"),
											qty: td.eq(2).attr(\"name\"),
											batch: td.eq(3).attr(\"name\"),
											eatby: td.eq(4).attr(\"name\"),
											sellby: td.eq(5).attr(\"name\"),
											comments: td.eq(6).attr(\"name\"),
											fk_commandefourndet: td.eq(7).attr(\"name\") || 0
										};
									}).get();
									
									$.ajax({
										url: '" . DOL_URL_ROOT . "/fourn/commande/ajax/ajaxbatch.php',
										type: 'POST',
										dataType: 'json',
										data: {
											batchData: batchData,
										},
										success: function(data){
											// Hide processing message
											document.getElementById(\"processingOverlay\").style.display = \"none\";
											
											if(data.success == true ){
												$( '#batch_line_dialog' ).dialog('close');
												$( '#batch_line_dialog' ).dialog('destroy');
												$( '#batch_line_dialog' ).remove();
												parent.location.reload();
											}
											else{
												$(\"#confirmButton\").prop(\"disabled\", false);
												$( '#batchalert' ).css('display','block');
											}
										},
										error: function() {
											// Hide processing message
											document.getElementById(\"processingOverlay\").style.display = \"none\";
											$(\"#confirmButton\").prop(\"disabled\", false);
											$( '#batchalert' ).css('display','block');
										}
									});
								}
							},
							{
								text: \"Cancelar\",
								click: function() {
										$( this ).dialog( \"close\" );
									}
							}
							
						],
						open: function(event, ui) {
							// Deshabilitar el botón de Confirmar al abrir el diálogo si las peticiones ajax no han finalizado
							if (ajaxCounterBatch==0 || ajaxCounterBatch < $(\".chkIndDispatch:checked\").length) {
								$(\"#confirmButton\").hide();
							}
						},
						close: function(event, ui) {
							location.reload();
						}
					});
					
					$( \"#batch_line_dialog\" ).dialog(\"open\");
					return false;
				}
				$('#printTagsButton').css('display','initial');

				// Get all elements with class='closebtn'
				var close = document.getElementsByClassName('closebtn');
				var i;

				// Loop through all close buttons
				for (i = 0; i < close.length; i++) {
					// When someone clicks on a close button
					close[i].onclick = function(){

						// Get the parent of <span class='closebtn'> (<div class='alert'>)
						var div = this.parentElement;

						// Set the opacity of div to 0 (transparent)
						div.style.opacity = '0';

						// Hide the div after 600ms (the same amount of milliseconds it takes to fade out)
						setTimeout(function(){ div.style.display = 'none'; }, 600);
					}
				}";
			print '</script>';

			print '<style>
				.alert {
				padding: 20px;
				background-color: #f44336; /* Red */
				color: white;
				margin-bottom: 15px;
				opacity: 1;
				transition: opacity 0.6s; /* 600ms to fade out */
				}
				/* The close button */
				.closebtn {
				margin-left: 15px;
				color: white;
				font-weight: bold;
				float: right;
				font-size: 22px;
				line-height: 20px;
				cursor: pointer;
				transition: 0.3s;
				}

				/* When moving the mouse over the close button */
				.closebtn:hover {
				color: black;
				}
			</style>';

			print load_fiche_titre($langs->trans("ReceivingForSameOrder"));

			print '<div class="div-table-responsive">';
			print '<table id="dispatch_received_products" class="noborder centpercent">';

			print '<tr class="liste_titre">';

			//Checkbox General (Todos los Productos Dispatch)
			print '<td ><input type="checkbox" name="checkAllDispatch" id="checkAllDispatch"></td>';

			if ($conf->reception->enabled)print '<td>'.$langs->trans("Reception").'</td>';

			print '<td>'.$langs->trans("Product").'</td>';
			// print '<td>'.$langs->trans("DateCreation").'</td>';
			print '<td>'.$langs->trans("DateDeliveryPlanned").'</td>';
			// if (!empty($conf->productbatch->enabled)) {
				print '<td class="dispatch_batch_number_title">'.$langs->trans("batch_number").'</td>';
				print '<td class="dispatch_dluo_title">'.$langs->trans("EatByDate").'</td>';
				print '<td class="dispatch_dlc_title">'.$langs->trans("SellByDate").'</td>';
			// }
			print '<td class="right">'.$langs->trans("QtyDispatched").'</td>';
			print '<td></td>';
			print '<td>'.$langs->trans("Warehouse").'</td>';
			print '<td>'.$langs->trans("Comment").' </td>';
			print '<td class="right"><a id="batchAsignButton" style=" padding-right: 10px;" class="button" onclick="return batchAsign();" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">Asignar lotes</a></td>';

			// Status
			if (!empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS) && empty($reception->rowid)) {
				print '<td class="center" colspan="2">'.$langs->trans("Status").'</td>';
			}
			elseif (!empty($conf->reception->enabled)) {
				print '<td class="center"></td>';
			}

			print '<td class="center"></td>';

			print "</tr>\n";

			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				print "<tr ".$bc[$var].">";

				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."stock_mouvement WHERE fk_product = ".$objp->fk_product." AND batch = '".$objp->batch."' AND fk_entrepot = ".$objp->warehouse_id."  AND label = '".$objp->comment."' AND value = ". $objp->qty ;
				$res = $db->query($sql);
				//Checkbox Individual para Cada Producto
				if ($db->num_rows($res) > 0) {
					print '<td><input type="checkbox" class="chkIndPrint" name="' . $objp->fk_product . '_' . $objp->qty . '_' . $objp->eatby . '_' . $objp->sellby . '_' . $objp->batch . '_' . $objp->warehouse_id . '_' . $objp->entrepot . '_' . $objp->comment . '_' . $objp->fk_commandefourndet . '" value="' . $objp->fk_product . '"></td>';
				}else {
					print '<td><input type="checkbox" class="chkIndDispatch" name="' . $objp->fk_product . '_' . $objp->qty . '_' . $objp->eatby . '_' . $objp->sellby . '_' . $objp->batch . '_' . $objp->warehouse_id . '_' . $objp->entrepot . '_' . $objp->comment . '_' . $objp->fk_commandefourndet . '" value="' . $objp->fk_product . '"></td>';
				}

				if (!empty($conf->reception->enabled)) {
					print '<td>';
					if (!empty($objp->fk_reception)) {
						$reception = new Reception($db);
						$reception->fetch($objp->fk_reception);
						print $reception->getNomUrl(1);
					}

					print "</td>";
				}

				print '<td>';
				print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"), 'product').' '.$objp->ref.'</a>';
				print ' - '.$objp->label;
				
				// Check if this is a gift product
				if ((isset($objp->subprice) && ($objp->subprice <= 0.2)) || (isset($objp->total_ttc) && $objp->total_ttc == 0)) {
					print ' <span class="badge badge-success">' . $langs->trans("GiftProduct") . '</span>';
				}
				
				print "</td>\n";
				print '<td>'.dol_print_date($db->jdate($objp->datec), 'day').'</td>';
				// print '<td>'.dol_print_date($db->jdate($objp->date_delivery), 'day').'</td>';

				// if (!empty($conf->productbatch->enabled)) {
					print '<td class="dispatch_batch_number">'.$objp->batch.'</td>';
					print '<td class="dispatch_dluo">'.dol_print_date($db->jdate($objp->eatby), 'day').'</td>';
					print '<td class="dispatch_dlc">'.dol_print_date($db->jdate($objp->sellby), 'day').'</td>';
				// }

				// Qty
				print '<td class="right">'.$objp->qty.'</td>';
				print '<td>&nbsp;</td>';

				// Warehouse
				print '<td>';
				$warehouse_static->id = $objp->warehouse_id;
				$warehouse_static->libelle = $objp->entrepot;
				print $warehouse_static->getNomUrl(1);
				print '</td>';

				// Comment
				print '<td colspan="2" class="tdoverflowmax300" style="white-space: pre;">'.$objp->comment.'</td>';

				// Status
				if (!empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS) && empty($reception->rowid)) {
					print '<td class="right">';
					$supplierorderdispatch->status = (empty($objp->status) ? 0 : $objp->status);
					// print $supplierorderdispatch->status;
					print $supplierorderdispatch->getLibStatut(5);
					print '</td>';

					// Add button to check/uncheck disaptching
					print '<td class="center">';
					if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check)))
					{
						if (empty($objp->status)) {
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Approve").'</a>';
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Deny").'</a>';
						} else {
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Disapprove").'</a>';
							print '<a class="button buttonRefused" href="#">'.$langs->trans("Deny").'</a>';
						}
					} else {
						$disabled = '';
						if ($object->statut == 5)
							$disabled = 1;
						if (empty($objp->status)) {
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=checkdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Approve").'</a>';
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=denydispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Deny").'</a>';
						}
						if ($objp->status == 1) {
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=uncheckdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Reinit").'</a>';
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=denydispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Deny").'</a>';
						}
						if ($objp->status == 2) {
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=uncheckdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Reinit").'</a>';
							print '<a class="button'.($disabled ? ' buttonRefused' : '').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=checkdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Approve").'</a>';
						}
					}
					print '</td>';
				} elseif (!empty($conf->reception->enabled)) {
					print '<td class="right">';
					if (!empty($reception->id)) {
						print $reception->getLibStatut(5);
					}
					print '</td>';
				}

				print '<td class="center"></td>';

				print "</tr>\n";

				$i++;
			}
			$db->free($resql);

			print "</table>\n";
			print '</div>';
		}
	} else {
		dol_print_error($db);
	}
}

//Funcionalidad Checkbox General
print '<script>';
print '
$(document).ready(function(){
	var checkBatch = $(".chkIndDispatch:checked").length;
	$("#checkAll").click(function(){

		let isChecked = false;
		if($(this).is(":checked")){
			isChecked = true;
		}
		
		$(".chkInd").each(function(i,e){
			if(!$(e).prop("disabled"))
			{
				$(e).prop("checked", isChecked);
			}
		});

	});

	$("#checkAllDispatch").click(function(){

		let isChecked = false;
		if($(this).is(":checked")){
			isChecked = true;
		}
		
		$(".chkIndDispatch").each(function(i,e){
			if ( isChecked ){
				$(e).prop("checked", !isChecked);
				$(e).change();
			} else {
				$("#confirmButton").hide();
			}
			if(!$(e).prop("disabled"))
			{
				$(e).prop("checked", isChecked);
				$(e).change();
			}
		});

		$(".chkIndPrint").each(function(i,e){
			if ( isChecked ){
				$(e).prop("checked", !isChecked);
				$(e).change();
			} else {
				$("#confirmButton").hide();
			}
			if (!$(e).prop("disabled")) 
			{
				$(e).prop("checked", isChecked);
				$(e).change();
			}
		});

	});

	$(".chkIndDispatch").change(function(){
		const fk_product = $(this).attr("name").split("_")[0];
		const qty = $(this).attr("name").split("_")[1];
		const eatby = $(this).attr("name").split("_")[2];
		const sellby = $(this).attr("name").split("_")[3];
		const batch = $(this).attr("name").split("_")[4];
		const warehouse = $(this).attr("name").split("_")[5];
		const entrepot = $(this).attr("name").split("_")[6];
		const comment = $(this).attr("name").split("_")[7];
		const fk_commandefourndet = $(this).attr("name").split("_")[8] || 0;
		const tableBatchs = $("#tableBatchs");
		if($(this).is(":checked")){
			$.ajax({
				method: "POST",
				url: "' . DOL_URL_ROOT . '/fourn/commande/ajax/ajaxrows.php",
				data: { 
					fk_product: fk_product,
					qty: qty,
					eatby: eatby,
					sellby: sellby,
					batch: batch,
					warehouse: warehouse,
					entrepot: entrepot,
					comment: comment,
					fk_commandefourndet: fk_commandefourndet
				}
			}).done(function( msg ) {					
				// Append to Table of Tags
				tableBatchs.append(msg);

				ajaxCounterBatch++;
				// Verificar si todas las solicitudes Ajax se han completado
				if (ajaxCounterBatch === $(".chkIndDispatch:checked").length) {
					$("#confirmButton").show();
				}
			}).fail(function(xhr, status, error) {
				checkBatch--;
			});
		}else{
			if(ajaxCounterBatch > 0) ajaxCounterBatch--;
			// Verificar si todas las solicitudes Ajax se han completado
			if ($(".chkIndDispatch:checked").length === 0) {
				$("#confirmButton").hide();
			}
			//Se eliminan las filas cuando se quita el check
			tableBatchs.find("tr").each(function(i,e){
				if ($(e).attr("name") == fk_product + "_" + batch + "_" + qty) {
					$(e).remove();
				}
			});
		}
	});


	$(".chkIndPrint").change(function(){
		const fk_product = $(this).attr("name").split("_")[0];
		const qty = $(this).attr("name").split("_")[1];
		const eatby = $(this).attr("name").split("_")[2];
		const sellby = $(this).attr("name").split("_")[3];
		const batch = $(this).attr("name").split("_")[4];
		const warehouse = $(this).attr("name").split("_")[5];
		const entrepot = $(this).attr("name").split("_")[6];
		const comment = $(this).attr("name").split("_")[7];
		const tableTags = $("#tableTags");
		if($(this).is(":checked")){
			$.ajax({
				method: "POST",
				url: "ajaxProdURL.php",
				data: { 
					fk_product: fk_product,
					qty: qty,
					eatby: eatby,
					sellby: sellby,
					batch: batch
				}
			}).done(function( msg ) {					
				// Append to Table of Tags
				tableTags.append(msg);

				ajaxCounterTags++;

				// Verificar si todas las solicitudes Ajax se han completado
				if (ajaxCounterTags === $(".chkIndPrint:checked").length) {
					$("#confirmButton").show();
				}
			});
		}else{
			if(ajaxCounterTags > 0) ajaxCounterTags--;
			// Verificar si todas las solicitudes Ajax se han completado
			if ($(".chkIndPrint:checked").length === 0) {
				$("#confirmButton").hide();
			}
			//Se eliminan las filas cuando se quita el check
			tableTags.find("tr").each(function(i,e){
				if ($(e).attr("name") == fk_product + "_" + batch + "_" + qty) {
					$(e).remove();
				}
			});
		}
	});
});
';
print '</script>';

// End of page
llxFooter();
$db->close();

print '<script>
function showProcessingMessage() {
	document.getElementById("processingOverlay").style.display = "block";
	document.getElementById("processingText").innerHTML = "'.$langs->trans("ProcessingRequest").'<br>'.$langs->trans("PleaseWait").'";
	return true;
}
</script>';

// Add the processing overlay right after the closing form tag
print '
<div id="processingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:9999;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background-color:white; padding:20px; border-radius:5px; text-align:center;">
        <div class="loader" style="border: 16px solid #f3f3f3; border-top: 16px solid #3498db; border-radius: 50%; width: 80px; height: 80px; margin:0 auto; animation: spin 2s linear infinite;"></div>
        <div id="processingText" style="margin-top:15px; font-weight:bold;"></div>
    </div>
</div>
<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
<script>
document.getElementById("dispatch").addEventListener("submit", function() {
    document.getElementById("processingOverlay").style.display = "block";
    document.getElementById("processingText").innerHTML = "' . $langs->trans("ProcessingRequest") . '<br>' . $langs->trans("PleaseWait") . '";
});

function showProcessingMessage() {
    document.getElementById("processingOverlay").style.display = "block";
    document.getElementById("processingText").innerHTML = "' . $langs->trans("ProcessingRequest") . '<br>' . $langs->trans("PleaseWait") . '";
}
</script>';

$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Actions buttons
print '<div class="tabsAction">';
$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
