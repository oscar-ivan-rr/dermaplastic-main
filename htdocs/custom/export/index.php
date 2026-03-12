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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/import.php';
$element = GETPOST('element', 'alpha');
$type = GETPOST("type", 'int');
$action = GETPOST('action', 'alpha');

// Security check
if ($element == 'product') $result = restrictedArea($user, 'produit');
// elseif ($type == '1') $result = restrictedArea($user, 'service');
elseif ($element == 'societe') $result = restrictedArea($user, 'societe', 0, '', '', '', '');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies'));
if($action == 'export' && $element != 'product')
    $action = 'confirm_export';
/**
 * Actions
 */

if ($action == 'confirm_export') {
    if($type == '')
        $type = GETPOST('type_export');
    $all_col = GETPOST('all_columns_product');
    $columns = GETPOST('products_col');
    include_once './export.php';
}

/*
 * View
 */

$form = new Form($db);

$transAreaType = $langs->trans("Import");

$helpurl = '';
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
    $transAreaType = $langs->trans("ThirdPartiesArea");
    $helpurl = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Terceros';
}

llxHeader("", $langs->trans($title), $helpurl);

$linkback = "";
print load_fiche_titre($transAreaType, $linkback, $icon);
print '<div class="fichecenter">';

/*
 * View
 */
if($action == 'export')
{
    print '<div class="fichecenter">';

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Export") . '</th></tr>';
    print '<tr><td class="center" colspan="2">';
    print '<form name="import_elements" action="' . $_SERVER["PHP_SELF"] . '?element='.$element.'&type='.$type.'" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="confirm_export">';

    if ($element != 'product' && $type == '') print $form->selectarray('type_export', array(2 => $langs->trans('Customer'), 3 => $langs->trans('Supplier')), '', 0);
    else print '<input type="hidden" name="type_export" value="'.$type.'">';
    if($element == 'product'){
        print '<label name="label" for="all_columns_product">Todas las columnas</label>';
        print '&nbsp;&nbsp;';
        print '<input type="checkbox" name="all_columns_product" id="all_columns_product">';
        $data=array(
            'Ref Lion',
            'Ref Sae',
            'Descripcion (etiqueta)',
            'Stock mínimo',
            'Codigo de barras',
            'Precio de venta (con iva)',
            'Precio de compra',
            'Rotacion',
            'Ganancia',
            'Clave unidad (SAT)',
            'Clave SAT',
            'Descuento maximo',
            'Empaque',
            'Cantidad dentro del empaque',
            'Categoria',
            'Stock en sucursal',
            'Ubicacion',
            'Exentoiva',
            'Objeto impuesto',
            'Tasa de IVA'
        );

        print $form->multiselectarray("products_col", $data, '', '', 0, '', 0, '100%');
    }
    print '<br><br><div class="center">';
    print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Export') . '">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
    print '</div>';
    print '</form>';
    print '</td></tr>';
    print '</table>';
    print '</>';
}
else {
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Export") . '</th></tr>';
    print '<tr><td class="center" colspan="2">';
    print '<form name="import_elements" action="' . $_SERVER["PHP_SELF"] . '?element=' . $element . '&type=' . $type . '" method="POST">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="confirm_export">';

    if ($element != 'product' && $type == '') print $form->selectarray('type_export', array(2 => $langs->trans('Customer'), 3 => $langs->trans('Supplier')), '', 0);
    else print '<input type="hidden" name="type_export" value="' . $type . '">';

    print '<br><br><div class="center">';
    print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Export') . '">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
    print '</div>';
    print '</form>';
    print '</td></tr>';
    print '</table>';
    print '</>';

}
print '</div>';

$parameters = array('type' => $type, 'user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProductsServices', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
