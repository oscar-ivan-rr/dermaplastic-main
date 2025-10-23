<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/mouvementstock.class.php';
// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies', 'categories', 'productbatch'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$inventorycode = GETPOST('inventorycode', 'alpha');

// Security check
$result = restrictedArea($user, 'stock');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('warehousecard', 'globalcard'));

$object = new Entrepot($db);


$arrayfields = array(
    'm.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1),
    'm.datem' => array('label' => $langs->trans("Date"), 'checked' => 1),
    'm.fk_product' => array('label' => $langs->trans("ProductRef"), 'checked' => 1),
    'm.batch' => array('label' => $langs->trans("Batch"), 'checked' => 1),
    'm.eatby' => array('label' => $langs->trans("EatByDate"), 'checked' => 1),
    'm.fk_entrepot' => array('label' => $langs->trans("Warehouse"), 'checked' => 1),
    'm.value' => array('label' => $langs->trans("Quantity"), 'checked' => 1),
);

/*
 * Actions
 */

$error = 0;

$usercanread = (($user->rights->stock->lire));
$usercancreate = (($user->rights->stock->creer));
$usercandelete = (($user->rights->stock->supprimer));
/*
 * Sincronizar stock (Eliminar lotes recibidos duplicados)
 */
if ($action == 'confirm_sync') {
    $sqlsync = "SELECT DISTINCT rowid, COUNT(*) as count";
    $sqlsync .= " FROM " . MAIN_DB_PREFIX . "stock_mouvement WHERE inventorycode = " . $inventorycode;
    $sqlsync .= " AND fk_entrepot = " . $id . " AND value > 0";
    $sqlsync .= " GROUP BY fk_product, batch, value, inventorycode HAVING COUNT(*) > 1";

    $resqlsync = $db->query($sqlsync);

    if ($resqlsync && $db->num_rows($resqlsync)) {
        // Hacemos movimientos de stock para eliminar los lotes duplicados
        // Qa estuvo aqui
        while ($row = $db->fetch_object($resqlsync)) {
            $producto = new Product($db);
            $producto->fetch($row->fk_product);
            $producto->correct_stock_batch($user, $row->fk_entrepot, $row->value, 1, 'Eliminación de lote duplicado', 0, 0, 0, $row->batch, $search_inventorycode);

            // Eliminamos los movimientoas duplicados
            $sqldel = "DELETE FROM " . MAIN_DB_PREFIX . "stock_mouvement WHERE rowid = " . $row->rowid;
            $resqldel = $db->query($sqldel);
        }
    }
    $action == 'list';

    header("Location: " . DOL_URL_ROOT . '/product/stock/movement_card.php?id=' . $id . '&search_inventorycode=' . $inventorycode);
}

/*
 * View
 */
$form = new Form($db);
$object = new Entrepot($db);
$object->fetch($id);

$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("", "Sincronizacion de movimiento", $help_url);

dol_fiche_head(array(), '', "Sincronizacion de stock", -1, 'stock');

$linkback = '<a href="' . DOL_URL_ROOT . '/product/stock/movement_card.php?id=' . $id . '&search_inventorycode=' . $inventorycode . '">' . $langs->trans("Regresar al la ficha de movimiento") . '</a>';

print '<div style="vertical-align: middle">';
print '<div class="inline-block floatleft valignmiddle refid refidpadding">Codigo de movimiento:</div>';
print '<div class="pagination paginationref"><ul class="right"><li class="noborder litext">' . $linkback . '</li></ul></div>';
print '<div class="valignmiddle"><a href="' . DOL_URL_ROOT . '/product/stock/movement_card.php?id=' . $id . '&amp;search_inventorycode=' . $inventorycode . '">' . $inventorycode . '</a></div>';
print '</div>';
print '<br>';

// Listado de movimientos duplicados
$sql = "SELECT m.rowid as mid, m.datem, m.fk_product, m.batch, m.eatby, m.fk_entrepot, m.value, COUNT(*) AS count";
$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement m LEFT JOIN ".MAIN_DB_PREFIX."product p ON m.fk_product = p.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot e ON m.fk_entrepot = e.rowid";
$sql .= " WHERE m.inventorycode = " . $inventorycode . " AND m.fk_entrepot = " . $id;
$sql .= " GROUP BY m.fk_product, m.batch, m.value, m.inventorycode HAVING COUNT(*) > 1";
$sql .= " ORDER BY m.inventorycode DESC, m.fk_product ASC";

$resql = $db->query($sql);

if ($resql) {
    if ((empty($action) || $action == 'list') && $id > 0) {
        print "<div class=\"tabsAction\">\n";
        if ($user->rights->stock->mouvement->sync) {
            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&inventorycode=' . $inventorycode . '&action=sync">' . $langs->trans("CorrectStock") . '</a>';
        }
        print '</div><br>';
    }

    if ($action == 'sync') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id . '&inventorycode=' . $inventorycode, 'Eliminar movimientos duplicados', '¿Desea continuar?', 'confirm_sync', '', 0, 1, 200);
    }

    // Print form confirm
    print $formconfirm;

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

    print '<tr class="liste_titre">';
    if (!empty($arrayfields['m.rowid']['checked']))
        print_liste_field_titre($arrayfields['m.rowid']['label'], $_SERVER["PHP_SELF"], 'm.rowid', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['m.datem']['checked']))
        print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER["PHP_SELF"], 'm.datem', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['m.fk_product']['checked']))
        print_liste_field_titre($arrayfields['m.fk_product']['label'], $_SERVER["PHP_SELF"], 'm.fk_product', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['m.batch']['checked']))
        print_liste_field_titre($arrayfields['m.batch']['label'], $_SERVER["PHP_SELF"], 'm.batch', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['m.eatby']['checked']))
        print_liste_field_titre($arrayfields['m.eatby']['label'], $_SERVER["PHP_SELF"], 'm.eatby', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['m.value']['checked']))
        print_liste_field_titre($arrayfields['m.value']['label'], $_SERVER["PHP_SELF"], 'm.value', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['m.fk_entrepot']['checked']))
        print_liste_field_titre($arrayfields['m.fk_entrepot']['label'], $_SERVER["PHP_SELF"], 'm.fk_entrepot', '', $param, '', $sortfield, $sortorder);
    print '</tr>';

    $product = new Product($db);
    $entrepot = new Entrepot($db);
    $num = $db->num_rows($resql);

    while ($objp = $db->fetch_object($resql)) {

        $product->fetch($objp->fk_product);
        $entrepot->fetch($objp->fk_entrepot);

        print '<tr class="oddeven">';
        // Id movement
        if (!empty($arrayfields['m.rowid']['checked'])) {
            // This is primary not movement id
            print '<td>' . $objp->mid . '</td>';
            if (!$i)
                $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.datem']['checked'])) {
            // Date
            print '<td>' . dol_print_date($db->jdate($objp->datem), 'dayhour') . '</td>';
            if (!$i)
                $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.fk_product']['checked'])) {
            // Product ref
            print '<td class="nowraponall">';
            print $product->getNomUrl(1, 'stock', 16);
            print "</td>\n";
            if (!$i)
                $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.batch']['checked'])) {
            print '<td class="nowraponall">';
            print $objp->batch; // the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
            print '</td>';
            if (!$i)
                $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.eatby']['checked'])) {
            print '<td class="nowraponall">';
            print dol_print_date($db->jdate($objp->eatby), 'day');
            print '</td>';
            if (!$i)
                $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.value']['checked'])) {
            print '<td class="nowraponall">';
            print $objp->value;
            print '</td>';
            if (!$i)
                $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['m.fk_entrepot']['checked'])) {
            print '<td class="nowraponall">';
            print $entrepot->getNomUrl(1, 'stock', 16);
            print '</td>';
            if (!$i)
                $totalarray['nbfield']++;
        }
        print '</tr>';
    }
    print '</table>';
    print '</div>';
} else {
    header("Location: " . DOL_URL_ROOT . '/product/stock/movement_card.php?id=' . $id . '&search_inventorycode=' . $inventorycode);
}

// End of page
llxFooter();
$db->close();
