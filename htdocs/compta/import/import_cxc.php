<?php
/*
 * Daniel Molina
 * danmnvx@gmail.com
 *
 */

/**
 *  \file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/import.php';





$action        = GETPOST('action', 'alpha');
$delimiter     = GETPOST('delimiter', 'none', 2);

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



    if (!$error) {
        if (dol_add_file_process($conf->mycompany->dir_temp, 1, -1, 'file_xsl', '', null, '', 0, false) > 0) {
            $filepath = $conf->mycompany->dir_temp . "/$file_name";

            $import = new ImportExcel($db,4, $filepath, $delimiter, $user, '', '');
            $info = $import->importData();

            // TODO: Show inserts, updates and errors

            if ($info['inserts'] > 0) setEventMessage($langs->trans("Facturas agregadas ".$info['inserts'], $info['inserts']));
            if ($info['updates'] > 0) setEventMessage($langs->trans("Facturas actualizada ".$info['updates'], $info['updates']));
            if ($info['warnings'] > 0) setEventMessage($langs->trans("Errores en facturas", $info['warnings']), 'warnings');
            //if ($info['errors'] > 0) setEventMessage($langs->trans("Error al importar factura", $info['errors']), 'errors');

            dol_delete_file($filepath, 0, 0, 0, null, false, 0);

            unset($import);
        }
    }
}

/*
 * View
 */

$form = new Form($db);

$transAreaType = $langs->trans("Importación cuentas por cobrar externas");

$helpurl = '';
$title = '';
llxHeader('','','');
print load_fiche_titre($transAreaType, '', '');
print '<div class="fichecenter">';
/*
 * View
 */
print '<div class="div-table-responsive-no-min">';
print '<form name="import_elements" action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Import") . '</th></tr>';
// print '<tr><td class="center" colspan="2">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="add">';
// File type
print '<tr><td>Tipo de CSV: </td><td>';
print $form->selectarray('delimiter', array(',' => $langs->trans('CsvWithComma'), ';' => $langs->trans('CsvWithSemicolon')));
print '</td></tr>';
// File
print '<tr><td>Seleccione archivo CSV para importar:</td><td><input type="file" name="file_xsl" accept=".csv"></td></tr>';
print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Import') . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
print '</div>';
print '</form>';
print '</div>';
// End of page

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



?>
<br />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype='multipart/form-data' method="post"> 
	<table class="noborder centpercent">
		<tr class="liste_titre">
			<th colspan="2">Importar PDFs de Facturas</th>
		</tr>
		<tr>
			<td>Seleccionar archivo ZIP para importar.</td>
			<td>
				<input type='file' name='zipImages' size='10' accept=".zip" />
<?php
if ($action == 'prod_images')
{
	require_once DOL_DOCUMENT_ROOT.'/custom/import/class/images.import.php';
    $error = 0;
    if ($_FILES['file_zip']['error'] == 4) {
        $error++;
        setEventMessage($langs->trans('FileNotSelected'), 'warnings');
    }
        //die (var_dump($_FILES));

    $file_name = $_FILES['file_xsl']['name'];

    $imported_images_count = importInvoicePdfs();

    echo '<p>Importación Terminada</p>';
    echo "<p> $imported_images_count imagen(es) subida(s)</p>";
}
 
?>
				
			</td>
		</tr>
	</table>
	<input type="hidden" name="element" value="<?php echo $element; ?>" />
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
    <input type="hidden" name="action" value="prod_images" />	
	<br />
	<div class="center">
		<input type="submit" class="button" name="bouton" value="<?php echo $langs->trans('Import'); ?>" />
		<input type="button" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>" onclick="return false;" style="visibility: hidden;" />
	</div>
</form>

<br />
<?php 
llxFooter();
$db->close();
?>