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

// Estilos CSS para el botón deshabilitado y pantalla de carga
print '<style>
		.button_disabled {
			opacity: 0.6 !important;
			cursor: not-allowed !important;
			background-color: #cccccc !important;
			color: #666666 !important;
		}
		
		/* Pantalla de carga completa */
		#loading_overlay_transfer {
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
		
		.loading_content_transfer {
			background-color: white;
			padding: 40px;
			border-radius: 10px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
			text-align: center;
			max-width: 400px;
			width: 90%;
		}
		
		.loading_spinner_transfer {
			border: 4px solid #f3f3f3;
			border-top: 4px solid #3498db;
			border-radius: 50%;
			width: 50px;
			height: 50px;
			animation: spin_transfer 2s linear infinite;
			margin: 0 auto 20px auto;
		}
		
		@keyframes spin_transfer {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		
		.loading_text_transfer {
			font-size: 18px;
			font-weight: bold;
			color: #2c3e50;
			margin-bottom: 10px;
		}
		
		.loading_subtext_transfer {
			font-size: 14px;
			color: #7f8c8d;
			line-height: 1.5;
		}
		</style>';

print load_fiche_titre("Tranferencia de lotes", '', 'generic');

print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post" name="transfer_stock_form">' . "\n";

dol_fiche_head();

print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="transfert_stock">';
print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
if ($pdluoid) {
	print '<input type="hidden" name="pdluoid" value="' . $pdluoid . '">';
}
print '<table class="border centpercent">';

// Source warehouse or product
print '<tr>';
if ($object->element == 'product') {
	print '<td class="fieldrequired">' . $langs->trans("WarehouseSource") . '</td>';
	print '<td>';
	if ($user->rights->stock->show_all_warehouses)
		print $formproduct->selectWarehouses((GETPOST("dwid") ? GETPOST("dwid", 'int') : (GETPOST('id_entrepot') ? GETPOST('id_entrepot', 'int') : ($object->element == 'product' && $object->fk_default_warehouse ? $object->fk_default_warehouse : 'ifone'))), 'id_entrepot', 'warehouseopen,warehouseinternal', 1);
	else
		print $formproduct->selectWarehouses($user->fk_warehouse, 'id_entrepot', 'warehouseopen,warehouseinternal', 1, 1);
	print '</td>';
}
if ($object->element == 'stock') {
	print '<td class="fieldrequired">' . $langs->trans("Product") . '</td>';
	print '<td>';
	$form->select_produits(GETPOST('product_id', 'int'), 'product_id', (empty($conf->global->STOCK_SUPPORTS_SERVICES) ? '0' : ''), 0, 0, -1, 2, '', 0, null, 0, 1, 0, 'maxwidth500');
	print '</td>';
}

print '<td class="fieldrequired">' . $langs->trans("WarehouseTarget") . '</td><td>';
print $formproduct->selectWarehouses(GETPOST('id_entrepot_destination'), 'id_entrepot_destination', 'warehouseopen,warehouseinternal', 1);
print '</td></tr>';
print '<tr><td class="fieldrequired">' . $langs->trans("NumberOfUnit") . '</td><td colspan="3"><input type="text" name="nbpiece" size="10" value="' . dol_escape_htmltag(GETPOST("nbpiece")) . '"></td>';
print '</tr>';

// Serial / Eat-by date
// if (!empty($conf->productbatch->enabled) &&
// 		    (($object->element == 'product' && $object->hasbatch())
// 		    || ($object->element == 'stock'))
// 		)

print '<tr>';
print '<td' . ($object->element == 'stock' ? '' : ' class="fieldrequired"') . '>' . $langs->trans("batch_number") . '</td><td colspan="3">';
if ($pdluoid > 0) {
	// If form was opened for a specific pdluoid, field is disabled
	print '<input type="text" name="batch_number_bis" size="40" value="' . (GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch) . '" readonly>';
	print '<input type="hidden" name="batch_number" value="' . (GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch) . '">';
} else {
	print '<input type="text" name="batch_number" size="40" value="' . (GETPOST('batch_number') ? GETPOST('batch_number') : $pdluo->batch) . '">';
}
print '</td>';
print '</tr>';

print '<tr>';
print '<td class="fieldrequired">' . $langs->trans("EatByDate") . '</td><td>';
print $form->selectDate($d_eatby ?: $pdluo->eatby ?: -1, 'eatby', '', '', 2, "", 1, $pdluo->eatby ? 0 : 1, $pdluoid ? 1 : 0); // If form was opened for a specific pdluoid, field is disabled
print '</td>';
print '<td class="fieldrequired">' . $langs->trans("SellByDate") . '</td><td>';
print $form->selectDate($d_sellby ?: $pdluo->sellby ?: -1, 'sellby', '', '', 2, "", 1, $pdluo->eatby ? 0 : 1, $pdluoid ? 1 : 0); // If form was opened for a specific pdluoid, field is disabled
print '</td>';
print '</tr>';


// Label
print '<tr>';
print '<td>' . $langs->trans("MovementLabel") . '</td>';
print '<td>';
print '<select class="flat" name="label" id="label" style="width:180px;">';
$query = "SELECT rowid, label";
$query .= " FROM " . MAIN_DB_PREFIX . "c_batch_transfer";
$query .= " WHERE active = '1' ORDER BY label DESC";
$requery = $db->query($query);
while ($obj = $db->fetch_object($requery)) {
	print '<option value="' . $obj->label . '">' . $obj->label . '</option>';
}
print '</select>';
print '</td>';
print '<td>' . $langs->trans("InventoryCode") . '</td><td><input class="maxwidth100onsmartphone" name="inventorycode" id="inventorycode" value="' . (isset($_POST["inventorycode"]) ? GETPOST("inventorycode", 'alpha') : dol_print_date(dol_now('tzuser'), '%y%m%d%H%M%S')) . '"></td>';
print '</tr>';

print '</table>';

print '<input type="hidden" name="entrepot_label" id="entrepot_label">';
print '<script>
		$(document).ready(function() {
			var value = $("#id_entrepot").find("option:selected").text();
			var label = $("#entrepot_label").val(value);

			var $select = $("#id_entrepot").change(function() {
				var value = $("#id_entrepot").find("option:selected").text();
				var label = $("#entrepot_label").val(value);
				console.log(value);
			});

		});
		</script>';
dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" name="save" id="save_button_transfer" value="' . dol_escape_htmltag($langs->trans('Save')) . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="submit" class="button" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '">';
print '</div>';

// Script para prevenir doble clic en el botón Guardar y mostrar pantalla de carga
print '<script>
		$(document).ready(function() {
			$("form[name=\'transfer_stock_form\']").on("submit", function(e) {
				var saveButton = $("#save_button_transfer");
				var loadingOverlay = $("#loading_overlay_transfer");
				
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
							alert("El proceso ha tardado más de lo esperado. Por favor, verifique si la transferencia se realizó correctamente antes de intentar nuevamente.");
						}
					}, 30000); // 30 segundos para operaciones de stock
				}
			});
			
			// Deshabilitar teclas de atajo comunes durante el procesamiento
			$(document).on("keydown", function(e) {
				if ($("#loading_overlay_transfer").is(":visible")) {
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
print '<div id="loading_overlay_transfer">
		<div class="loading_content_transfer">
			<div class="loading_spinner_transfer"></div>
			<div class="loading_text_transfer">Procesando transferencia de lote...</div>
			<div class="loading_subtext_transfer">
				Por favor espere mientras se realiza la transferencia.<br>
				No cierre esta ventana ni navegue a otra página.
			</div>
		</div>
	</div>';
?>
<!-- END PHP STOCKCORRECTION.TPL.PHP -->