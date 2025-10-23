<?php
/* 
 * Daniel Molina
 * danmnvx@gmail.com
 */

/**
 *  \file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

set_time_limit(0);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/import.php';

$element       = GETPOST('element', 'alpha');
$type          = GETPOST("type", 'int');
$action        = GETPOST('action', 'alpha');
$delimiter     = GETPOST('delimiter', 'none', 2);
$currency      = GETPOST('currency', 'alpha');
$currency_rate = GETPOST('currency_rate', 'int');
$sellprice_cal = GETPOSTISSET('sellprice_cal');

$path = './logs/stock/notfoundimport/';
if (file_exists($path)) {
	$files = glob($path . '/*'); // get all file names
	foreach($files as $file){ // iterate files
		if(is_file($file)) unlink($file); // delete file
	}
}


// Security check
if ($element == 'product') $result = restrictedArea($user, 'produit');
// elseif ($type == '1') $result = restrictedArea($user, 'service');
elseif ($element == 'societe') $result = restrictedArea($user, 'societe', 0, '', '', '', '');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies', 'admin'));

/**
 * Actions
 */

if ($action == 'add') {
	$error = 0;
	if ($_FILES['file_xsl']['error'] == 4) {
		$error++;
		setEventMessage($langs->trans('FileNotSelected'), 'warnings');
	}

	if ($delimiter != ',' && $delimiter != ';') {
		$error++;
		setEventMessage($langs->trans('ErrorDelimiterFormat'), 'errors');
	}
	$file_name = $_FILES['file_xsl']['name'];
	
	// Clean currency rate
	if (empty($currency_rate)) $currency_rate = 1;

	if (!$error) {
		if (dol_add_file_process($conf->mycompany->dir_temp, 1, -1, 'file_xsl', '', null, '', 0, false) > 0) {
			$filepath = $conf->mycompany->dir_temp . "/$file_name";
	
			$import = new ImportExcel($db, intval($type), $filepath, $delimiter, $user, $currency_rate, $currency);
			$info = $import->importData();
			
			// TODO: Show inserts, updates and errors
            if($element == 'product') {//Para productos
                if ($info['inserts'] > 0) setEventMessage($langs->trans("AddedProducts", $info['inserts']));
                if ($info['updates'] > 0) setEventMessage($langs->trans("UpdatedProducts", $info['updates']));
                if ($info['warnings'] > 0) setEventMessage($langs->trans("WarningProducts", $info['warnings']), 'warnings');
                if ($info['errors'] > 0) setEventMessage($langs->trans("ErrorImportedProducts", $info['errors']), 'errors');
            }
            else if($element == 'societe') {//Para clientes
                if ($info['inserts'] > 0) setEventMessage($langs->trans("AddedSocietes", $info['inserts']));
                if ($info['updates'] > 0) setEventMessage($langs->trans("UpdatedSocietes", $info['updates']));
                if ($info['warnings'] > 0) setEventMessage($langs->trans("WarningSocietes", $info['warnings']), 'warnings');
                if ($info['errors'] > 0) setEventMessage($langs->trans("ErrorImportedSocietes", $info['errors']), 'errors');
            }
            else{//Para proveedores
                if ($info['inserts'] > 0) setEventMessage($langs->trans("AddedSuppliers", $info['inserts']));
                if ($info['updates'] > 0) setEventMessage($langs->trans("UpdatedSuppliers", $info['updates']));
                if ($info['warnings'] > 0) setEventMessage($langs->trans("WarningSuppliers", $info['warnings']), 'warnings');
                if ($info['errors'] > 0) setEventMessage($langs->trans("ErrorImportedSuppliers", $info['errors']), 'errors');
            }
			dol_delete_file($filepath, 0, 0, 0, null, false, 0);
	
			unset($import);
		}
	}
}

else if($action == 'update_stock'){
	global $db, $user, $conf, $langs;
	$error = 0;
	$warehouse = GETPOST('warehouse', 'int');

	if ($_FILES['file_xsl']['error'] == 4) {
		$error++;
		setEventMessage($langs->trans('FileNotSelected'), 'warnings');
	}

	if ($delimiter != ',' && $delimiter != ';') {
		$error++;
		setEventMessage($langs->trans('ErrorDelimiterFormat'), 'errors');
	}
	$file_name = $_FILES['file_xsl']['name'];

	// Clean currency rate
	if (empty($currency_rate)) $currency_rate = 1;

	if (!$error) {
		$productsNotFound = array();
		if (dol_add_file_process($conf->mycompany->dir_temp, 1, -1, 'file_xsl', '', null, '', 0, false) > 0) {
			$filepath = $conf->mycompany->dir_temp . "/$file_name";
			
			if(false === ($gestor = fopen($filepath, "r"))) {
				setEventMessage('Error al abrir el archivo', 'errors');
				return;
			}

			while (($register = fgetcsv($gestor, 1000, $delimiter)) !== FALSE) {
				// brincamos la primera linea que contiene los titulos de las columnas
				if ($register[0] == 'Codigo de barras') continue;
				if ($register[1] == 'Minimo') continue;
				if ($register[2] == 'Reorden') continue;
				if ($register[3] == 'Max') continue;
				if ($register[4] == 'Ubicacion' || $register[0] == 'Ubicación') continue;

				$barcode = trim($register[0]);
				$desiredstock = $register[1];
				$seuil_stock_alerte = $register[2];
				$stock_max = $register[3];
				$location = $register[4];

				if(empty($barcode)){
					$productsNotFound[] = array(
						'barcode' => $barcode,
						'desiredstock' => $desiredstock,
						'seuil_stock_alerte' => $seuil_stock_alerte,
						'stock_max' => $stock_max,
						'location' => $location
					);
					continue;
				}
				else if(!is_numeric($desiredstock) || !is_numeric($seuil_stock_alerte) || !is_numeric($stock_max)){
					$productsNotFound[] = array(
						'barcode' => $barcode,
						'desiredstock' => $desiredstock,
						'seuil_stock_alerte' => $seuil_stock_alerte,
						'stock_max' => $stock_max,
						'location' => $location
					);
					continue;
				}

				else if($desiredstock < 0 || $seuil_stock_alerte < 0 || $stock_max < 0){
					$productsNotFound[] = array(
						'barcode' => $barcode,
						'desiredstock' => $desiredstock,
						'seuil_stock_alerte' => $seuil_stock_alerte,
						'stock_max' => $stock_max,
						'location' => $location
					);
					continue;
				}

				$data = array(
					'barcode' => $barcode,
					'desiredstock' => $desiredstock,
					'seuil_stock_alerte' => $seuil_stock_alerte,
					'stock_max' => $stock_max,
					'location' => $location
				);

				$product = new Product($db);
				$product->fetch('','','',$barcode);

				if ($product->id) {
					if(!empty($location) || trim($location) != '') {
						foreach ($product->array_options as $key => $value) {
							if ($key == 'options_noidenticfdi') {
								$product->array_options[$key] = $location;
							}
						}
					}
					
					$cleanedBarcode = preg_replace('/[^a-zA-Z0-9]/', '', $barcode);
					$product->barcode = $cleanedBarcode;
					$res_update = $product->update($product->id, $user);

					if($res_update){
						$sql_search_stock = "SELECT * FROM llx_product_warehouse_properties WHERE fk_product = ".$product->id." and fk_entrepot = ".$warehouse;
						$resql_search_stock = $db->query($sql_search_stock);
						$numrows = $db->num_rows($resql_search_stock);
						if ($numrows == 0) {
							$sql_insert_stock = "INSERT INTO llx_product_warehouse_properties (fk_product, fk_entrepot, seuil_stock_alerte, desiredstock, stock_max) VALUES (".$product->id.", ".$warehouse.", $seuil_stock_alerte, $desiredstock, $stock_max)";
							$resql_insert_stock = $db->query($sql_insert_stock);
						}else{
							$sql_update_stock = "UPDATE llx_product_warehouse_properties SET seuil_stock_alerte = $seuil_stock_alerte, desiredstock = $desiredstock, stock_max = $stock_max WHERE fk_product = ".$product->id." and fk_entrepot = ".$warehouse;
							$resql_update_stock = $db->query($sql_update_stock);
						}
					}else{
						// si no se actualiza el producto se guarda en un array para guardar en un archivo txt
						$cleanedBarcode = preg_replace('/[^a-zA-Z0-9]/', '', $barcode);
						$productsNotFound[] = array(
							'barcode' => $cleanedBarcode,
							'desiredstock' => $desiredstock,
							'seuil_stock_alerte' => $seuil_stock_alerte,
							'stock_max' => $stock_max,
							'location' => $location
						);
					}
				}else{
					// si no se encuentra el producto se muestra se guarda en un array para guardar en un archivo txt
					$cleanedBarcode = preg_replace('/[^a-zA-Z0-9]/', '', $barcode);
					$productsNotFound[] = array(
						'barcode' => $cleanedBarcode,
						'desiredstock' => $desiredstock,
						'seuil_stock_alerte' => $seuil_stock_alerte,
						'stock_max' => $stock_max,
						'location' => utf8_encode($location)
					);
				}
			}
			fclose($gestor);
			// si hay productos no encontrados se guardan en un archivo txt
			if (count($productsNotFound) > 0) {
				$path = './logs/stock/notfoundimport/';
				$filename = $path . '/productos_no_encontrados.csv';
				$message = '';
				if (!file_exists($path)) {
					$res = mkdir($path, 0777, true);
					if (!$res) {
						$message = 'Error al crear directorio';
					}
				}
				$fp = fopen($filename, 'w');
				fputcsv($fp, array('Codigo de barras', 'Minimo', 'Reorden', 'Max', 'Ubicacion'));
				foreach ($productsNotFound as $product) {
					fputcsv($fp, $product);
				}
				fclose($fp);
				foreach ($productsNotFound as $product) {
					setEventMessage('Producto no actualizado: ' . $product['barcode'], 'errors');
				}
			}else{
				setEventMessage('Stock actualizado correctamente');
			}
			dol_delete_file($filepath, 0, 0, 0, null, false, 0);
			unset($import);
		}
	}
}
/*
 * View
 */

$form = new Form($db);

$transAreaType = $langs->trans("Import");

$helpurl = '';
$title = 'Importación';
if ($element == 'product')
{
	$icon = 'products';
	$title = 'ProductsAndServices';
	$transAreaType = $langs->trans("ProductsAndServicesImport");
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
elseif ($element == 'societe')
{
	$icon = 'companies';
	$title = 'ThirdParties';
	$transAreaType = $langs->trans("ThirdPartiesImport");
	$helpurl = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Terceros';
}

llxHeader("", $langs->trans($title), $helpurl);

$linkback = "";
print load_fiche_titre($transAreaType, $linkback, $icon);


print '<div class="fichecenter">';

/*
 * View
 */
print '<div class="div-table-responsive-no-min">';
print '<form name="import_elements" action="' . $_SERVER["PHP_SELF"] . '?element='.$element.'&type='.$type.'" method="POST" enctype="multipart/form-data">';
//print '<form name="import_elements" action="' . DOL_URL_ROOT . '/custom/import/products.php" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Import") . '</th></tr>';
// print '<tr><td class="center" colspan="2">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="add">';

// Type of societe
if ($element != 'product' && $type == '') {
	print '<tr><td>'.$langs->trans('ThirdParty').'</td><td>';
	print $form->selectarray('type_import', array(2 => $langs->trans('Customer'), 3 => $langs->trans('Supplier')), '', 0);
	print '</td></tr>';
}
else print '<input type="hidden" name="type_import" value="'.$type.'">';

// File type
print '<tr><td>Tipo de CSV: </td><td>';
print $form->selectarray('delimiter', array(',' => $langs->trans('CsvWithComma'), ';' => $langs->trans('CsvWithSemicolon')));
print '</td></tr>';

// File
print '<tr><td>Seleccione archivo CSV para importar:</td><td><input type="file" name="file_xsl" accept=".csv"></td></tr>';

// Currency (products)
if ($element == 'product') {
	// Currency
	print '<tr><td>'.$langs->trans('Currency').'</td><td>';
	print $form->selectMultiCurrency(!empty($currency) ? $currency : $conf->currency, 'currency', 0);
	print '</td></tr>';

	// Rate
	print '<tr><td>'.$langs->trans('TypeRate').'</td><td>';
	print '<input type="number" step="0.0001" name="currency_rate" id="currency_rate" required '.($currency == 'MXN' || empty($currency) ? "disabled" : "") .'>';
	print '</td></tr>';

	// Sell price calculated
	print '<tr><td>'.$langs->trans('CalPriceSell').'</td><td>';
	print '<input type="checkbox" name="sellprice_cal" id="sellprice_cal">';
	print '</td></tr>';
}

print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Import') . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
print '</div>';
print '</form>';

print '</div>';

?>

<script>
(function () {
	let currencySelect = document.querySelector('#currency');
	let rateInput = document.querySelector('#currency_rate');

	currencySelect.onchange = function () {
		var elem = (typeof this.selectedIndex === "undefined" ? window.event.srcElement : this);
		var value = elem.value || elem.options[elem.selectedIndex].value;
		
		if (value == "MXN") rateInput.setAttribute('disabled', 'disabled');
		else rateInput.removeAttribute('disabled');
	}
})();
</script>
<?php

// Split categories warnings
$infoCats = array();
if (isset($info['msgs']['warning']['_$cats?'])) {
	$infoCats = $info['msgs']['warning']['_$cats?'];
	unset($info['msgs']['warning']['_$cats?']);
}

$msg = '';
// Products warnings and errors.
if (!empty($info['msgs'])) {
	foreach($info['msgs'] as $type => $indexes) {
		foreach($indexes as $reference => $occurrences) {
			// Prod ref
			$msg .= "<div class='$type'><h3>$reference</h3>";
			foreach($occurrences as $key => $occ) {
				// Error
				$msg .= '<p><strong>'.$occ['label'].'</strong></p>';
				// Show db error only for error types.
				if ($type === 'error') $msg .= '<p>'.$occ['db'].'</p>';
			}
			$msg .= "</div>";
		}
	}
}

// Error with categories
if (sizeof($infoCats) > 0) {
	$msg .= '<h2>'.$langs->trans("Categories").'</h2>';
	foreach ($infoCats as $key => $occ) {
		// Cat ref
		$msg .= "<div class='warning'><h3>$key</h3>";
		// Error
		$msg .= '<p><strong>'.$occ['label'].'</strong></p>';
		// Prods that belong to category
		$msg .= array_reduce($occ['prods'], function($carry, $prod) { return $carry .= "<br><strong>$prod</strong>"; }, $langs->trans("Products").":");
		$msg .= "</div>";
	}

}

// Show alerts
print '<div>' . $msg . '</div>';

$parameters = array('type' => $type, 'user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProductsServices', $parameters, $object); // Note that $action and $object may have been modified by hook

// Actualizar stock deseado y limite de stock
if ($element == 'product') {
    print '<br>';
    print '<div class="div-table-responsive-no-min">';
    print '<form name="import_elements" action="' . $_SERVER["PHP_SELF"] . '?element='.$element.'&type='.$type.'" method="POST" enctype="multipart/form-data">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="2">Actualizar stock deseado y limite de stock</th></tr>';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="update_stock">';

    // File type
    print '<tr><td>Tipo de CSV: </td><td>';
    print $form->selectarray('delimiter', array(',' => $langs->trans('CsvWithComma'), ';' => $langs->trans('CsvWithSemicolon')));
    print '</td></tr>';
	// File
    print '<tr><td>Seleccione archivo CSV para importar:</td><td><input type="file" name="file_xsl" accept=".csv"></td></tr>';
	
	// Entrepot (almacen)
	print '<tr><td>'.$langs->trans('Warehouse').'</td><td>';
	$sql_warehouse = "SELECT rowid, ref FROM ".MAIN_DB_PREFIX."entrepot";
	$resql_warehouse = $db->query($sql_warehouse);
	$selected_warehouse = $user->fk_warehouse ? $user->fk_warehouse : 29;
	$label_warehouse = "";
	while ($obj = $db->fetch_object($resql_warehouse)) {
		$selected = ($obj->rowid == $selected_warehouse) ? 1 : 0;
		if ($selected) $label_warehouse = $obj->ref;
	}
	print '<input type="hidden" name="warehouse" value="'.$selected_warehouse.'">';
	print '<h4><strong>' . $label_warehouse . '</strong></h4>';
	print '</td></tr>';

	// Errors file (products not found)
	$path = './logs/stock/notfoundimport/';
	$filename = $path . '/productos_no_encontrados.csv';
	if (file_exists($filename)) {
		print '<tr><td id="txtProductsNotFound">Productos no actualizados</td><td>';
		print '<a href="' . $filename . '" download>Descargar archivo</a>';
		print '</td></tr>';

		print '<style>
			#txtProductsNotFound {
				color: red;
				font-weight: bold;
			}

			#txtProductsNotFound a {
				color: red;
				font-weight: bold;
			}
		</style>';
	}

    print '</table>';
    print '<br><div class="center">';
    print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Import') . '">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
    print '</div>';
    print '</form>';

    print '</div>';
}
// End of page
llxFooter();
$db->close();
