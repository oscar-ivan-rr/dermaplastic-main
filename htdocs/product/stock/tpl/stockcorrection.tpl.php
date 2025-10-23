<?php
/* Copyright (C) 2010-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *
 * $object must be defined
 * $backtopage
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

?>

<!-- BEGIN PHP TEMPLATE STOCKCORRECTION.TPL.PHP -->
<?php

$productref = '';
if ($object->element == 'product')
	$productref = $object->ref;

$langs->load("productbatch");

if (empty($id))
	$id = $object->id;

$pdluoid = GETPOST('pdluoid', 'int');

$pdluo = new Productbatch($db);

if ($pdluoid > 0) {
	$result = $pdluo->fetch($pdluoid);
}

print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			function init_price()
			{
				if (jQuery("#mouvement").val() == \'0\') jQuery("#unitprice").removeAttr("disabled");
				else jQuery("#unitprice").prop("disabled", true);
			}
			init_price();
			jQuery("#mouvement").change(function() {
				init_price();
			});
		});
		</script>';

// Estilos CSS para el botón deshabilitado y pantalla de carga
print '<style>
		.button_disabled {
			opacity: 0.6 !important;
			cursor: not-allowed !important;
			background-color: #cccccc !important;
			color: #666666 !important;
		}
		
		/* Pantalla de carga completa */
		#loading_overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.7);
			z-index: 9999;
			display: none;
			justify-content: center;
			align-items: center;
			flex-direction: column;
		}
		
		.loading_content {
			background-color: white;
			padding: 40px;
			border-radius: 10px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
			text-align: center;
			max-width: 400px;
			width: 90%;
		}
		
		.loading_spinner {
			border: 4px solid #f3f3f3;
			border-top: 4px solid #3498db;
			border-radius: 50%;
			width: 50px;
			height: 50px;
			animation: spin 2s linear infinite;
			margin: 0 auto 20px auto;
		}
		
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		
		.loading_text {
			font-size: 18px;
			font-weight: bold;
			color: #2c3e50;
			margin-bottom: 10px;
		}
		
		.loading_subtext {
			font-size: 14px;
			color: #7f8c8d;
			line-height: 1.5;
		}
		</style>';


print load_fiche_titre('Corrección de lotes', '', 'generic');

print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&type=batch" method="post" name="correct_stock_form">' . "\n";

dol_fiche_head();

print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="correct_stock">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
print '<table class="border centpercent">';

// Warehouse or product
print '<tr>';
if ($object->element == 'product') {
	print '<td class="fieldrequired">' . $langs->trans("Warehouse") . '</td>';
	print '<td colspan="3">';
	if ($user->rights->stock->show_all_warehouses) {
		print $formproduct->selectWarehouses((GETPOST("dwid") ? GETPOST("dwid", 'int') : (GETPOST('id_entrepot') ? GETPOST('id_entrepot', 'int') : ($object->element == 'product' && $object->fk_default_warehouse ? $object->fk_default_warehouse : 'ifone'))), 'id_entrepot', 'warehouseopen,warehouseinternal', 0, 0, 0, '', 0, 0, null, 'minwidth100');
		$warehouse_id = (GETPOST("dwid") ? GETPOST("dwid", 'int') : (GETPOST('id_entrepot') ? GETPOST('id_entrepot', 'int') : ($object->element == 'product' && $object->fk_default_warehouse ? $object->fk_default_warehouse : 'ifone')));
	} else {
		print $formproduct->selectWarehouses($user->fk_warehouse, 'id_entrepot', 'warehouseopen,warehouseinternal', 0, 1, 0, '', 0, 0, null, 'minwidth100');
		$warehouse_id = $user->fk_warehouse;
	}
	print ' &nbsp; <select name="mouvement" id="mouvement">';
	print '<option value="0">Entrada</option>';
	print '<option value="1"' . (GETPOST('mouvement') ? ' selected="selected"' : '') . '>Salida</option>';
	print '</select>';
	if ($warehouse_id == $conf->global->CEDIS_WAREHOUSE) {
		$prod = new Product($db);
		$prod->fetch($id);
		$prod->load_stock('novirtual');

		$physical_stock = $prod->stock_warehouse[$warehouse_id]->real;
		// Load stats for the product
		$prod->load_stats_commande(0, '1,2', 1, $warehouse_id);

		// Get stock to deliver
		$stock_to_deliver = $prod->stats_commande['qty'];

		// Load sending stats for the product
		$prod->load_stats_sending(0, '1,2', 1, '', $warehouse_id);

		// Get stock sent
		$stock_sent = $prod->stats_expedition['qty'];

		// Calculate remain stock
		$realstock = $physical_stock - ($stock_to_deliver - $stock_sent);

		print ' &nbsp; Stock fisico: <input class="maxwidth25" type="text" name="physical_stock" value="' . $physical_stock . '" disabled> Stock E-Commerce: <input class="maxwidth25" type="text" name="realstock" value="' . $realstock . '" disabled>';
	}
	print '</td>';
}
if ($object->element == 'stock') {
	print '<td class="fieldrequired">' . $langs->trans("Product") . '</td>';
	print '<td colspan="3">';
	$form->select_produits(GETPOST('product_id', 'int'), 'product_id', (empty($conf->global->STOCK_SUPPORTS_SERVICES) ? '0' : ''), 0, 0, -1, 2, '', 0, null, 0, 1, 0, 'maxwidth500');
	print ' &nbsp; <select name="mouvement" id="mouvement">';
	print '<option value="0">' . $langs->trans("Add") . '</option>';
	print '<option value="1"' . (GETPOST('mouvement') ? ' selected="selected"' : '') . '>' . $langs->trans("Delete") . '</option>';
	print '</select>';
	print '</td>';
}

print '<tr>';
print '<td class="fieldrequired">' . $langs->trans("NumberOfUnit") . '</td>';
print '<td colspan="3"><input name="nbpiece" id="nbpiece" size="10" value="' . GETPOST("nbpiece") . '"></td>';
print '</tr>';

// Serial / Eat-by date
print '<tr>';
print '<td' . ($object->element == 'stock' ? '' : ' class="fieldrequired"') . '>' . $langs->trans("batch_number") . '</td><td colspan="3">';
if ($pdluoid > 0) {
	// If form was opened for a specific pdluoid, field is disabled
	print '<input type="text" name="batch_number_bis" size="40" value="' . (GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch) . '"  readonly>';
	print '<input type="hidden" name="batch_number" value="' . (GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch) . '">';
} else {
	print '<input type="text" name="batch_number" size="40" value="' . (GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch) . '">';
}
print '</td>';
print '</tr>';
print '<tr>';
print '<td class="fieldrequired">' . $langs->trans("EatByDate") . '</td><td>';
$eatbyselected = dol_mktime(0, 0, 0, GETPOST('eatbymonth'), GETPOST('eatbyday'), GETPOST('eatbyyear'));
print $form->selectDate($eatbyselected ?: $pdluo->eatby ?: -1, 'eatby', '', '', 2, "", 1, $pdluo->eatby ? 0 : 1, $pdluoid ? 1 : 0);
print '</td>';
print '<td class="fieldrequired">' . $langs->trans("SellByDate") . '</td><td>';
$sellbyselected = dol_mktime(0, 0, 0, GETPOST('sellbymonth'), GETPOST('sellbyday'), GETPOST('sellbyyear'));
print $form->selectDate($sellbyselected ?: $pdluo->sellby ?: -1, 'sellby', '', '', 2, "", 1, $pdluo->eatby ? 0 : 1, $pdluoid ? 1 : 0);
print '</td>';
print '</tr>';

// Label of mouvement of id of inventory
$valformovementlabel = ((GETPOST("label") && (GETPOST('label') != $langs->trans("MovementCorrectStock", ''))) ? GETPOST("label") : $langs->trans("MovementCorrectStock", $productref));
print '<tr>';
print '<td>' . $langs->trans("MovementLabel") . '</td>';
print '<td>';
// Etiquetas para agregar lote
print '<select class="flat" name="label" id="label_add" style="width:180px;">';
$query = "SELECT rowid, label";
$query .= " FROM " . MAIN_DB_PREFIX . "c_movements_add";
$query .= " WHERE active = '1' ORDER BY label";
$requery = $db->query($query);
while ($obj = $db->fetch_object($requery)) {
	print '<option value="' . $obj->label . '">' . $obj->label . '</option>';
	$i++;
}
print '</select>';

// Etiquetas para eliminar lote
print '<select class="flat" name="label" id="label_delete" disabled style="display:none; width:180px;" >';
$query2 = "SELECT rowid, label";
$query2 .= " FROM " . MAIN_DB_PREFIX . "c_movements_delete";
$query2 .= " WHERE active = '1' ORDER BY label";
$requery2 = $db->query($query2);
while ($obj2 = $db->fetch_object($requery2)) {
	print '<option value="' . $obj2->label . '">' . $obj2->label . '</option>';
	$i++;
}
print '</select>';
print '</td>';
print '<td>' . $langs->trans("InventoryCode") . '</td><td><input class="maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="' . (isset($_POST["inventorycode"]) ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now('tzuser'), '%y%m%d%H%M%S')) . '"></td>';
print '</tr>';

print '</table>';

// Script para mostrar el select correspondiente de acuerdo al tipo de movimiento
print '<script>
			$(document).ready(function() {
				var $select = $("#mouvement").change(function() {
					var value = $(this).val();
					if(value == 0){
						// Add
						document.getElementById("label_add").style.display="unset";
						document.getElementById("label_delete").style.display="none";
						$("#label_add").prop("disabled", false);
						$("#label_delete").prop("disabled", true);
					} else{
						// Delete
						document.getElementById("label_delete").style.display="unset";
						document.getElementById("label_add").style.display="none";
						$("#label_delete").prop("disabled", false);
						$("#label_add").prop("disabled", true);
					}
				});
				var value = $("#id_entrepot").find("option:selected").text();
				var label = $("#entrepot_label").val(value);

				$("#id_entrepot").change(function() {
					var $this = $(this);
					var value = $this.find("option:selected").text();
					$("#entrepot_label").val(value);
					$("input[name=\'action\']").val("correction");
					$("form[name=\'correct_stock_form\']").submit();
					console.log(value);
				});
			});
		</script>';

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" name="save" id="save_button" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="submit" class="button" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '">';
print '</div>';

// Script para prevenir doble clic en el botón Guardar y mostrar pantalla de carga
print '<script>
		$(document).ready(function() {
			$("form[name=\'correct_stock_form\']").on("submit", function(e) {
				var saveButton = $("#save_button");
				var loadingOverlay = $("#loading_overlay");
				
				// Solo procesar si se hizo clic en el botón Guardar (no en Cancelar)
				if ($(document.activeElement).attr("name") === "save") {
					// Verificar si el botón ya está deshabilitado
					if (saveButton.prop("disabled")) {
						e.preventDefault();
						return false;
					}
					
					// Deshabilitar el botón y cambiar el texto
					saveButton.prop("disabled", true);
					saveButton.val("Procesando...");
					saveButton.addClass("button_disabled");
					
					// Mostrar pantalla de carga
					loadingOverlay.css("display", "flex");
					
					// Deshabilitar scroll del body
					$("body").css({
						"overflow": "hidden",
						"position": "fixed",
						"width": "100%"
					});
					
					// Opcional: Re-habilitar después de un tiempo si hay error
					setTimeout(function() {
						if (saveButton.prop("disabled")) {
							// Ocultar pantalla de carga
							loadingOverlay.hide();
							
							// Re-habilitar scroll del body
							$("body").css({
								"overflow": "",
								"position": "",
								"width": ""
							});
							
							// Re-habilitar botón
							saveButton.prop("disabled", false);
							saveButton.val("' . dol_escape_htmltag($langs->trans('Save')) . '");
							saveButton.removeClass("button_disabled");
							
							// Mostrar mensaje de error
							alert("El proceso ha tardado más de lo esperado. Por favor, verifique si el movimiento se registró correctamente antes de intentar nuevamente.");
						}
					}, 30000); // 30 segundos para operaciones de stock
				}
			});
			
			// Deshabilitar teclas de atajo comunes durante el procesamiento
			$(document).on("keydown", function(e) {
				if ($("#loading_overlay").is(":visible")) {
					// Deshabilitar F5, Ctrl+R, Ctrl+W, etc.
					if (e.keyCode === 116 || (e.ctrlKey && (e.keyCode === 82 || e.keyCode === 87))) {
						e.preventDefault();
						return false;
					}
				}
			});
		});
		</script>';

print '</form>';

// Pantalla de carga overlay
print '<div id="loading_overlay">
		<div class="loading_content">
			<div class="loading_spinner"></div>
			<div class="loading_text">Procesando corrección de stock...</div>
			<div class="loading_subtext">
				Por favor espere mientras se registra el movimiento.<br>
				No cierre esta ventana ni navegue a otra página.
			</div>
		</div>
	</div>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->