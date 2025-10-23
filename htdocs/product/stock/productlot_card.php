<?php
/* Copyright (C) 2007-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      All-3kcis       		 <contact@all-3kcis.fr>
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
 *   	\file       product/stock/productlot_card.php
 *		\ingroup    stock
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2016-05-17 12:22
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

// Load translation files required by the page
$langs->loadLangs(array('stocks', 'other', 'productbatch'));

// Get parameters
$id = GETPOST('id', 'int');
$action		= GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$batch  	= GETPOST('batch', 'alpha');
$productid  = GETPOST('productid', 'int');
$ref        = GETPOST('ref', 'alpha'); // ref is productid_batch

$search_entity = GETPOST('search_entity', 'int');
$search_fk_product = GETPOST('search_fk_product', 'int');
$search_batch = GETPOST('search_batch', 'alpha');
$search_fk_user_creat = GETPOST('search_fk_user_creat', 'int');
$search_fk_user_modif = GETPOST('search_fk_user_modif', 'int');
$search_import_key = GETPOST('search_import_key', 'int');

if (empty($action) && empty($id) && empty($ref)) $action = 'list';


// Protection if external user
if ($user->socid > 0)
{
    //accessforbidden();
}
//$result = restrictedArea($user, 'mymodule', $id);


$object = new ProductLot($db);
$extrafields = new ExtraFields($db);
$formfile = new FormFile($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id || $ref)
{
    if ($ref)
    {
        $tmp = explode('_', $ref);
        $productid = $tmp[0];
        $batch = $tmp[1];
    }
	$object->fetch($id, $productid, $batch);
	$object->ref = $object->batch; // For document management ( it use $object->ref)
}

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productlotcard', 'globalcard'));


$permissionnote = $user->rights->stock->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->stock->creer; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->stock->creer; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

$usercanread = $user->rights->produit->lire;
$usercancreate = $user->rights->produit->creer;
$usercandelete = $user->rights->produit->supprimer;

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
		if ($action == 'seteatby' && $user->rights->stock->creer)
	{
	    $newvalue = dol_mktime(12, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
		
		// VALIDACIÓN: Check if another lot with same batch, eatby and sellby already exists
		if ($newvalue) {
			$new_eatby_date = date('Y-m-d', $newvalue);
			$current_sellby_date = $object->sellby ? date('Y-m-d', $object->sellby) : null;
			
			$sql_check_duplicate = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "product_lot 
				WHERE fk_product = " . (int)$object->fk_product . " 
				AND batch = '" . $db->escape($object->batch) . "'
				AND DATE(eatby) = '" . $db->escape($new_eatby_date) . "'";
			
			if ($current_sellby_date) {
				$sql_check_duplicate .= " AND DATE(sellby) = '" . $db->escape($current_sellby_date) . "'";
			} else {
				$sql_check_duplicate .= " AND sellby IS NULL";
			}
			
			$sql_check_duplicate .= " AND rowid != " . (int)$object->id;
			
			$resql_check = $db->query($sql_check_duplicate);
			if ($resql_check) {
				$obj_check = $db->fetch_object($resql_check);
				if ($obj_check->count > 0) {
					setEventMessages("ALERTA: Ya existe un lote con el mismo nombre ('" . $object->batch . "'), fecha de caducidad y fecha de ingreso. No se puede modificar.", null, 'errors');
					$action = ''; // Cancel the action
				}
			}
		}
		
		if ($action == 'seteatby') { // Only proceed if validation passed
			// Get the specific product_batch IDs that should be updated BEFORE changing the lot
			$product_batch_ids = array();
			$sql_get_batch_ids = "SELECT pb.rowid FROM " . MAIN_DB_PREFIX . "product_batch as pb
				LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps ON pb.fk_product_stock = ps.rowid
				WHERE ps.fk_product = " . (int)$object->fk_product . " 
				AND pb.batch = '" . $db->escape($object->batch) . "'";
			
			if ($object->eatby) {
				$current_eatby = date('Y-m-d', $object->eatby);
				$sql_get_batch_ids .= " AND DATE(pb.eatby) = '" . $db->escape($current_eatby) . "'";
			} else {
				$sql_get_batch_ids .= " AND pb.eatby IS NULL";
			}

			if ($object->sellby) {
				$current_sellby = date('Y-m-d', $object->sellby);
				$sql_get_batch_ids .= " AND DATE(pb.sellby) = '" . $db->escape($current_sellby) . "'";
			} else {
				$sql_get_batch_ids .= " AND pb.sellby IS NULL";
			}
			
			$resql_ids = $db->query($sql_get_batch_ids);
			if ($resql_ids) {
				while ($obj = $db->fetch_object($resql_ids)) {
					$product_batch_ids[] = $obj->rowid;
				}
			}
			
			// Now update the lot
			$result = $object->setValueFrom('eatby', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
			if ($result < 0) dol_print_error($db, $object->error);

			// Update the specific product_batch records by ID
			if ($result >= 0 && $newvalue && !empty($product_batch_ids)) {
				$newdatetime = date('Y-m-d H:i:s', $newvalue);
				$ids_list = implode(',', array_map('intval', $product_batch_ids));
				
				$sql_update_product_batch = "UPDATE " . MAIN_DB_PREFIX . "product_batch 
					SET eatby = '" . $db->escape($newdatetime) . "'
					WHERE rowid IN (" . $ids_list . ")";
				
				$resql = $db->query($sql_update_product_batch);
				if (!$resql) {
					dol_print_error($db, $sql_update_product_batch);
				}
			}
		}
	}

	if ($action == 'setsellby' && $user->rights->stock->creer)
	{
	    $newvalue = dol_mktime(12, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
		
		// VALIDACIÓN: Check if another lot with same batch, eatby and sellby already exists
		if ($newvalue) {
			$new_sellby_date = date('Y-m-d', $newvalue);
			$current_eatby_date = $object->eatby ? date('Y-m-d', $object->eatby) : null;
			
			$sql_check_duplicate = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "product_lot 
				WHERE fk_product = " . (int)$object->fk_product . " 
				AND batch = '" . $db->escape($object->batch) . "'
				AND DATE(sellby) = '" . $db->escape($new_sellby_date) . "'";
			
			if ($current_eatby_date) {
				$sql_check_duplicate .= " AND DATE(eatby) = '" . $db->escape($current_eatby_date) . "'";
			} else {
				$sql_check_duplicate .= " AND eatby IS NULL";
			}
			
			$sql_check_duplicate .= " AND rowid != " . (int)$object->id;
			
			$resql_check = $db->query($sql_check_duplicate);
			if ($resql_check) {
				$obj_check = $db->fetch_object($resql_check);
				if ($obj_check->count > 0) {
					setEventMessages("ALERTA: Ya existe un lote con el mismo nombre ('" . $object->batch . "'), fecha de caducidad y fecha de ingreso. No se puede modificar.", null, 'errors');
					$action = ''; // Cancel the action
				}
			}
		}
		
		if ($action == 'setsellby') { // Only proceed if validation passed
			// Get the specific product_batch IDs that should be updated BEFORE changing the lot
			$product_batch_ids = array();
			$sql_get_batch_ids = "SELECT pb.rowid FROM " . MAIN_DB_PREFIX . "product_batch as pb
				LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps ON pb.fk_product_stock = ps.rowid
				WHERE ps.fk_product = " . (int)$object->fk_product . " 
				AND pb.batch = '" . $db->escape($object->batch) . "'";
			
			if ($object->sellby) {
				$current_sellby = date('Y-m-d', $object->sellby);
				$sql_get_batch_ids .= " AND DATE(pb.sellby) = '" . $db->escape($current_sellby) . "'";
			} else {
				$sql_get_batch_ids .= " AND pb.sellby IS NULL";
			}

			if ($object->eatby) {
				$current_eatby = date('Y-m-d', $object->eatby);
				$sql_get_batch_ids .= " AND DATE(pb.eatby) = '" . $db->escape($current_eatby) . "'";
			} else {
				$sql_get_batch_ids .= " AND pb.eatby IS NULL";
			}

			$resql_ids = $db->query($sql_get_batch_ids);
			if ($resql_ids) {
				while ($obj = $db->fetch_object($resql_ids)) {
					$product_batch_ids[] = $obj->rowid;
				}
			}
			
			// Now update the lot
			$result = $object->setValueFrom('sellby', $newvalue, '', null, 'date', '', $user, 'PRODUCTLOT_MODIFY');
			if ($result < 0) dol_print_error($db, $object->error);

			// Update the specific product_batch records by ID
			if ($result >= 0 && $newvalue && !empty($product_batch_ids)) {
				$newdatetime = date('Y-m-d H:i:s', $newvalue);
				$ids_list = implode(',', array_map('intval', $product_batch_ids));
				
				$sql_update_product_batch = "UPDATE " . MAIN_DB_PREFIX . "product_batch 
					SET sellby = '" . $db->escape($newdatetime) . "'
					WHERE rowid IN (" . $ids_list . ")";
				
				$resql = $db->query($sql_update_product_batch);
				if (!$resql) {
					dol_print_error($db, $sql_update_product_batch);
				}
			}
		}
	}

	if ($action == 'update_extras')
    {
    	$object->oldcopy = dol_clone($object);

    	// Fill array 'array_options' with data from update form
        $ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'none'));
        if ($ret < 0) $error++;

        if (!$error)
        {
            // Actions on extra fields
            $result = $object->insertExtraFields('PRODUCT_LOT_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
        }

        if ($error)
            $action = 'edit_extras';
    }

	// Action to add record
	if ($action == 'add')
	{
		if (GETPOST('cancel', 'alpha'))
		{
			$urltogo = $backtopage ? $backtopage : dol_buildpath('/stock/list.php', 1);
			header("Location: ".$urltogo);
			exit;
		}

		$error = 0;

		/* object_prop_getpost_prop */

    	$object->entity = GETPOST('entity', 'int');
    	$object->fk_product = GETPOST('fk_product', 'int');
    	$object->batch = GETPOST('batch', 'alpha');
    	$object->fk_user_creat = GETPOST('fk_user_creat', 'int');
    	$object->fk_user_modif = GETPOST('fk_user_modif', 'int');
    	$object->import_key = GETPOST('import_key', 'int');

		if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}

		if (!$error)
		{
			$result = $object->create($user);
			if ($result > 0)
			{
				// Creation OK
				$urltogo = $backtopage ? $backtopage : dol_buildpath('/stock/list.php', 1);
				header("Location: ".$urltogo);
				exit;
			}
			{
				// Creation KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else  setEventMessages($object->error, null, 'errors');
				$action = 'create';
			}
		}
		else
		{
			$action = 'create';
		}
	}

	// Cancel
	if ($action == 'update' && GETPOST('cancel', 'alpha')) $action = 'view';

	// Action to update record
	if ($action == 'update' && !GETPOST('cancel', 'alpha'))
	{
		$error = 0;

    	$object->entity = GETPOST('entity', 'int');
    	$object->fk_product = GETPOST('fk_product', 'int');
    	$object->batch = GETPOST('batch', 'alpha');
    	$object->fk_user_creat = GETPOST('fk_user_creat', 'int');
    	$object->fk_user_modif = GETPOST('fk_user_modif', 'int');
    	$object->import_key = GETPOST('import_key', 'int');

		if (empty($object->ref))
		{
			$error++;
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
		}

		if (!$error)
		{
			$result = $object->update($user);
			if ($result > 0)
			{
				$action = 'view';
			}
			else
			{
				// Creation KO
				if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
				else setEventMessages($object->error, null, 'errors');
				$action = 'edit';
			}
		}
		else
		{
			$action = 'edit';
		}
	}

	// Action to delete
	if ($action == 'confirm_delete')
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');
			header("Location: ".dol_buildpath('/stock/list.php', 1));
			exit;
		}
		else
		{
			if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}

	// Actions to build doc
    $upload_dir = $conf->productbatch->multidir_output[$conf->entity];
    $permissiontoadd = $usercancreate;
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}




/*
 * View
 */

llxHeader('', 'ProductLot', '');

$form = new Form($db);


// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("Batch"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head();

	print '<table class="border centpercent">'."\n";
	// print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="flat" type="text" size="36" name="label" value="'.$label.'"></td></tr>';
	//
    print '<tr><td class="fieldrequired">'.$langs->trans("Fieldfk_product").'</td><td><input class="flat" type="text" name="fk_product" value="'.GETPOST('fk_product').'"></td></tr>';
    print '<tr><td class="fieldrequired">'.$langs->trans("Fieldbatch").'</td><td><input class="flat" type="text" name="batch" value="'.GETPOST('batch').'"></td></tr>';
    print '<tr><td class="fieldrequired">'.$langs->trans("Fieldfk_user_creat").'</td><td><input class="flat" type="text" name="fk_user_creat" value="'.GETPOST('fk_user_creat').'"></td></tr>';
    print '<tr><td class="fieldrequired">'.$langs->trans("Fieldfk_user_modif").'</td><td><input class="flat" type="text" name="fk_user_modif" value="'.GETPOST('fk_user_modif').'"></td></tr>';
    print '<tr><td class="fieldrequired">'.$langs->trans("Fieldimport_key").'</td><td><input class="flat" type="text" name="import_key" value="'.GETPOST('import_key').'"></td></tr>';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("Create").'"> &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

	print '</form>';
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_optionals();

    //print load_fiche_titre($langs->trans("Batch"));

    $head = productlot_prepare_head($object);
	dol_fiche_head($head, 'card', $langs->trans("Batch"), -1, 'barcode');


	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBatch'), $langs->trans('ConfirmDeleteBatch'), 'confirm_delete', '', 0, 1);
		print $formconfirm;
	}


	$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/productlot_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $shownav = 1;
    if ($user->socid && !in_array('batch', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

	dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid', 'batch');

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">'."\n";

	// Product
    print '<tr><td class="titlefield">'.$langs->trans("Product").'</td><td>';
    $producttmp = new Product($db);
    $producttmp->fetch($object->fk_product);
    print $producttmp->getNomUrl(1, 'stock');
    print '</td></tr>';

    // Eat by
    print '<tr><td>';
    print $form->editfieldkey($langs->trans('EatByDate'), 'eatby', $object->eatby, $object, $user->rights->stock->creer, 'datepicker');
    print '</td><td>';
    print $form->editfieldval($langs->trans('EatByDate'), 'eatby', $object->eatby, $object, $user->rights->stock->creer, 'datepicker');
    print '</td>';
    print '</tr>';

    // Sell by
    print '<tr><td>';
    print $form->editfieldkey($langs->trans('SellByDate'), 'sellby', $object->sellby, $object, $user->rights->stock->creer, 'datepicker');
    print '</td><td>';
    print $form->editfieldval($langs->trans('SellByDate'), 'sellby', $object->sellby, $object, $user->rights->stock->creer, 'datepicker');
    print '</td>';
    print '</tr>';

    // JavaScript validation for date fields
    print '<script type="text/javascript">
    $(document).ready(function() {
        // Function to validate and protect date fields
        function setupDateFieldValidation() {
            // Make date input fields readonly to prevent manual editing
            $("input[name=\'eatby\'], input[name=\'sellby\']").each(function() {
                $(this).attr("readonly", true);
                $(this).css("background-color", "#f8f9fa");
                $(this).attr("placeholder", "Seleccione fecha usando el calendario");
            });
            
            // Validate on form submission
            $("form").on("submit", function(e) {
                var isValid = true;
                var errorMessages = [];
                
                // Check eatby field
                var eatbyInput = $("input[name=\'eatby\']");
                if (eatbyInput.length > 0) {
                    var eatbyValue = eatbyInput.val().trim();
                    if (eatbyValue === "" || eatbyValue === null) {
                        isValid = false;
                        errorMessages.push("La fecha de caducidad no puede estar vacía");
                        eatbyInput.css("border", "2px solid red");
                    } else if (!isValidDate(eatbyValue)) {
                        isValid = false;
                        errorMessages.push("La fecha de caducidad no es válida");
                        eatbyInput.css("border", "2px solid red");
                    } else {
                        eatbyInput.css("border", "");
                    }
                }
                
                // Check sellby field
                var sellbyInput = $("input[name=\'sellby\']");
                if (sellbyInput.length > 0) {
                    var sellbyValue = sellbyInput.val().trim();
                    if (sellbyValue === "" || sellbyValue === null) {
                        isValid = false;
                        errorMessages.push("La fecha de venta no puede estar vacía");
                        sellbyInput.css("border", "2px solid red");
                    } else if (!isValidDate(sellbyValue)) {
                        isValid = false;
                        errorMessages.push("La fecha de venta no es válida");
                        sellbyInput.css("border", "2px solid red");
                    } else {
                        sellbyInput.css("border", "");
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert("Error de validación:\\n" + errorMessages.join("\\n"));
                    return false;
                }
            });
            
            // Validate date format function
            function isValidDate(dateString) {
                if (!dateString) return false;
                
                // Try different date formats that Dolibarr might use
                var formats = [
                    /^\d{4}-\d{2}-\d{2}$/,     // YYYY-MM-DD
                    /^\d{2}\/\d{2}\/\d{4}$/,   // DD/MM/YYYY
                    /^\d{2}-\d{2}-\d{4}$/      // DD-MM-YYYY
                ];
                
                var isValidFormat = formats.some(function(format) {
                    return format.test(dateString);
                });
                
                if (!isValidFormat) return false;
                
                // Try to parse the date to make sure it\'s actually valid
                var date = new Date(dateString);
                if (dateString.includes("/")) {
                    // Handle DD/MM/YYYY format
                    var parts = dateString.split("/");
                    date = new Date(parts[2], parts[1] - 1, parts[0]);
                } else if (dateString.includes("-") && dateString.length === 10 && dateString.indexOf("-") === 2) {
                    // Handle DD-MM-YYYY format
                    var parts = dateString.split("-");
                    date = new Date(parts[2], parts[1] - 1, parts[0]);
                }
                
                return date instanceof Date && !isNaN(date.getTime());
            }
            
            // Force calendar usage - disable manual typing
            $("input[name=\'eatby\'], input[name=\'sellby\']").on("keydown keypress keyup", function(e) {
                // Allow only tab, escape, and navigation keys
                if (e.keyCode === 9 || e.keyCode === 27 || 
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return true;
                }
                e.preventDefault();
                return false;
            });
            
            // Show tooltip on focus
            $("input[name=\'eatby\'], input[name=\'sellby\']").on("focus", function() {
                $(this).attr("title", "Use el icono del calendario para seleccionar la fecha. La edición manual está deshabilitada.");
            });
        }
        
        // Call setup function
        setupDateFieldValidation();
        
        // Re-apply validation if AJAX updates occur
        $(document).ajaxComplete(function() {
            setTimeout(setupDateFieldValidation, 100);
        });
    });
    </script>';

    // Other attributes
    $cols = 2;
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';

	dol_fiche_end();


	// Buttons
	print '<div class="tabsAction">'."\n";
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	if (empty($reshook))
	{
		/*TODO      if ($user->rights->stock->lire)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
		}

		if ($user->rights->stock->supprimer)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>'."\n";
		}
		*/
	}
	print '</div>'."\n";


	print '<a href="'.DOL_URL_ROOT.'/product/reassortlot.php?sref='.urlencode($producttmp->ref).'&search_batch='.urlencode($object->batch).'">'.$langs->trans("ShowCurrentStockOfLot").'</a><br>';
	print '<br>';
	print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?search_product_ref='.urlencode($producttmp->ref).'&search_batch='.urlencode($object->batch).'">'.$langs->trans("ShowLogOfMovementIfLot").'</a><br>';

	print '<br>';
}



/*
 * Documents generes
 */

if (empty($action))
{
    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<a name="builddoc"></a>'; // ancre

    // Documents
	$filedir = $conf->productbatch->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'product_batch').dol_sanitizeFileName($object->ref);
    $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
    $genallowed = $usercanread;
    $delallowed = $usercancreate;

    print $formfile->showdocuments('product_batch', dol_sanitizeFileName($object->ref), $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $object->default_lang, '', $object);
    $somethingshown = $formfile->numoffiles;

    print '</div>';
}

// End of page
llxFooter();
$db->close();
