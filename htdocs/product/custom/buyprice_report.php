<?php

/**
 *  \file       htdocs/product/custom/supplier_report.php
 *  \ingroup    product
 *  \brief      reporte de compras a proveedor
 *  \author     Jesus Montalvo 
 */


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

date_default_timezone_set("GMT");
global $conf, $langs;
$langs->loadLangs(array('main', 'companies', 'bills', 'product', 'stocks', 'productbatch'));

$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
$searchCategoryProductList2 = GETPOST('search_category_product_list2', 'array');
$search_stock0 = GETPOST('search_stock0', 'int');

$action = GETPOST('action', 'alpha');

$warehouse_id = GETPOST('fk_warehouse', 'alpha') ? GETPOST('fk_warehouse', 'alpha') : $user->fk_warehouse;
$supplier = GETPOST('supplier', 'alpha');
$search_product = GETPOST('search_product', 'alpha');

$sqlexport   = GETPOST('sqlexport');

// Warehouse
if (!empty($warehouse_id)) {
    $sql_w = "SELECT e.ref as nom, e.lieu as ref, r.label as rfc_label FROM " . MAIN_DB_PREFIX . "entrepot e ";
    $sql_w .= "JOIN " . MAIN_DB_PREFIX . "c_rfc r ON e.fk_rfc = r.rowid WHERE e.rowid = '" . $warehouse_id . "'";
    $resql_w = $db->query($sql_w);
    $warehouse = $db->fetch_object($resql_w);
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOST("page", 'int');
if (!$sortorder) $sortorder = "DESC";

$titlepage = "Reporte de precio de compra promedio";

// SQL section

if ($action == 'buscar' && ($supplier && $supplier > 0)) {
    $sql = "SELECT p.rowid as product_id, p.ref as product, cf.rowid as order_id, cf.ref as order_ref, s.rowid as supplier_id, s.nom as supplier, e.rowid as warehouse_id, e.ref as warehouse, ps.reel as stock, cp.CategoryName as categories, SUM(cfd.qty) as order_qty";
    $sql .= ",ROUND((SUM(cfd.qty) * cfd.subprice) / SUM(cfd.qty), 2) as order_price";
    $sql .= ",IF(pe.objimp = 02, ROUND(SUM(cfd.qty) * cfd.subprice / SUM(cfd.qty) * 0.16, 2), 0) as iva";
    $sql .= ",ROUND(IF(pe.objimp = 02, (SUM(cfd.qty) * cfd.subprice) / SUM(cfd.qty) * 1.16, (SUM(cfd.qty) * cfd.subprice) / SUM(cfd.qty)), 2) as order_price_avg";
    $sql .= ",ROUND(IF(pe.objimp = 02, (SUM(cfd.qty) * cfd.subprice) / SUM(cfd.qty) * 1.16, (SUM(cfd.qty) * cfd.subprice) / SUM(cfd.qty)) * ps.reel, 2) as order_total";
    $sql .= ", p.barcode";
    $sql .= " FROM llx_product as p";
    $sql .= " LEFT JOIN llx_product_extrafields as pe ON p.rowid = pe.fk_object";
    $sql .= " LEFT JOIN llx_product_stock as ps ON p.rowid = ps.fk_product";
    $sql .= " LEFT JOIN llx_entrepot as e ON ps.fk_entrepot = e.rowid";
    $sql .= " LEFT JOIN llx_commande_fournisseurdet as cfd ON p.rowid = cfd.fk_product";
    $sql .= " LEFT JOIN llx_commande_fournisseur as cf ON cfd.fk_commande = cf.rowid";
    $sql .= " LEFT JOIN llx_societe as s ON cf.fk_soc = s.rowid";
    $sql .= " LEFT JOIN (SELECT cp.fk_product, GROUP_CONCAT(c.label SEPARATOR ', ') as CategoryName FROM llx_categorie_product cp LEFT JOIN llx_categorie c ON cp.fk_categorie = c.rowid GROUP BY cp.fk_product) as cp ON cp.fk_product = p.rowid";
    $sql .= " WHERE p.entity = 1";
    if ($searchCategoryProductList && !empty($searchCategoryProductList)) {
        $sql .= " AND p.rowid IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList) . "))";
    }
    if ($searchCategoryProductList2 && !empty($searchCategoryProductList2)) {
        $sql .= " AND p.rowid NOT IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList2) . "))";
    }
    if ($search_stock0 && $search_stock0 == 1) {
        $sql .= " AND ps.reel = 0";
    } else {
        $sql .= " AND ps.reel > 0";
    }
    if ($supplier && $supplier > 0) {
        $sql .= " AND s.rowid = " . $supplier;
    }
    if ($warehouse_id && $warehouse_id > 0) {
        $sql .= " AND e.rowid = " . $warehouse_id;
    }
    if ($search_product && !empty($search_product)) {
        $sql .= " AND (p.ref LIKE '%" . $search_product . "%' OR p.label LIKE '%" . $search_product . "%' OR p.barcode LIKE '%" . $search_product . "%')";
    }

    $sql .= " GROUP BY p.rowid";

    $sql .= " ORDER BY p.ref ASC";
    $resql = $db->query($sql);
    if (!$resql) {
        dol_print_error($db);
        exit;
    }
} else if ($action == 'buscar' && ($search_stock0 && $search_stock0 == 1)) {
    global $db;
    if (!$searchCategoryProductList || empty($searchCategoryProductList)) {
        $searchCategoryProductList = array('0' => 144);
    }
    if (!$searchCategoryProductList2 || empty($searchCategoryProductList2)) {
        $searchCategoryProductList2 = array('0' => 144);
    }
    $sql_existencias_cero = "SELECT DISTINCT p.rowid as rowid, p.ref, p.cost_price as price, p.price_ttc, IF(p.exentoiva = 02, p.price * 0.16, 0) as iva, p.categories, p.barcode";
    $sql_existencias_cero .= " FROM (SELECT DISTINCT p.rowid   as rowid, p.ref, p.price, p.price_ttc, p.cost_price, pe.objimp as exentoiva, cp.CategoryName as categories, p.barcode";
    $sql_existencias_cero .= " FROM llx_product as p LEFT JOIN llx_product_stock as ps ON ps.fk_product = p.rowid LEFT JOIN llx_product_extrafields as pe ON p.rowid = pe.fk_object";
    $sql_existencias_cero .= " LEFT JOIN (SELECT cp.fk_product, GROUP_CONCAT(c.label SEPARATOR ', ') as CategoryName FROM llx_categorie_product cp LEFT JOIN llx_categorie c ON cp.fk_categorie = c.rowid GROUP BY cp.fk_product) as cp ON cp.fk_product = p.rowid";
    $sql_existencias_cero .= " WHERE ps.fk_entrepot = " . $warehouse_id;
    if ($searchCategoryProductList && !empty($searchCategoryProductList)) {
        $sql_existencias_cero .= " AND p.rowid IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList) . "))";
    }
    if ($searchCategoryProductList2 && !empty($searchCategoryProductList2)) {
        $sql_existencias_cero .= " AND p.rowid NOT IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList2) . "))";
    }
    if ($search_product && !empty($search_product)) {
        $sql_existencias_cero .= " AND (p.ref LIKE '%" . $search_product . "%' OR p.label LIKE '%" . $search_product . "%' OR p.barcode LIKE '%" . $search_product . "%')";
    }
    $sql_existencias_cero .= " UNION";
    $sql_existencias_cero .= " SELECT DISTINCT p.rowid   as rowid, p.ref, p.price, p.price_ttc, p.cost_price, pe.objimp as exentoiva, cp.CategoryName as categories, p.barcode";
    $sql_existencias_cero .= " FROM llx_product as p";
    $sql_existencias_cero .= " LEFT JOIN llx_product_extrafields as pe ON p.rowid = pe.fk_object";
    $sql_existencias_cero .= " LEFT JOIN (SELECT cp.fk_product, GROUP_CONCAT(c.label SEPARATOR ', ') as CategoryName FROM llx_categorie_product cp LEFT JOIN llx_categorie c ON cp.fk_categorie = c.rowid GROUP BY cp.fk_product) as cp ON cp.fk_product = p.rowid";
    $sql_existencias_cero .= " WHERE NOT EXISTS (SELECT 1 FROM llx_product_stock as ps WHERE ps.fk_product = p.rowid AND ps.fk_entrepot = " . $warehouse_id . ")";
    if ($searchCategoryProductList && !empty($searchCategoryProductList)) {
        $sql_existencias_cero .= " AND p.rowid IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList) . "))";
    }
    if ($searchCategoryProductList2 && !empty($searchCategoryProductList2)) {
        $sql_existencias_cero .= " AND p.rowid NOT IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList2) . "))";
    }
    if ($search_product && !empty($search_product)) {
        $sql_existencias_cero .= " AND (p.ref LIKE '%" . $search_product . "%' OR p.label LIKE '%" . $search_product . "%' OR p.barcode LIKE '%" . $search_product . "%')";
    }
    $sql_existencias_cero .= " ) as p";
    $sql_existencias_cero .= " ORDER BY p.ref ASC";
    $resql_existencias_cero = $db->query($sql_existencias_cero);
    if (!$resql_existencias_cero) {
        dol_print_error($db);
        exit;
    }
} else if ($action == 'buscar') {

    $sql_all_products = "SELECT p.rowid as product_id FROM " . MAIN_DB_PREFIX . "product as p";
    $sql_all_products .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields as pe ON p.rowid = pe.fk_object";
    $sql_all_products .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps ON p.rowid = ps.fk_product";
    $sql_all_products .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON ps.fk_entrepot = e.rowid";
    $sql_all_products .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet as cfd ON p.rowid = cfd.fk_product";
    $sql_all_products .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cf ON cfd.fk_commande = cf.rowid";
    $sql_all_products .= " WHERE p.entity = 1";
    $sql_all_products .= " AND ps.reel > 0";
    $sql_all_products .= " AND e.rowid = " . $warehouse_id;
    if (!empty($conf->global->CEDIS_SUPPLIER)) $sql_all_products .= " AND cf.fk_soc <> " . $conf->global->CEDIS_SUPPLIER;
    // $sql_all_products .= " AND p.rowid = 286";
    // $sql_all_products .= " AND cf.fk_statut <> 0";
    if($search_category_product_list && !empty($search_category_product_list)) {
        $sql_all_products .= " AND p.rowid IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $search_category_product_list) . "))";
    }
    if($search_category_product_list2 && !empty($search_category_product_list2)) {
        $sql_all_products .= " AND p.rowid NOT IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $search_category_product_list2) . "))";
    }
    $sql_all_products .= " GROUP BY  p.rowid";
    $sql_all_products .= " ORDER BY p.rowid";

    $resql_all_products = $db->query($sql_all_products);
    if (!$resql_all_products) {
        dol_print_error($db);
        exit;
    }
    $datos = array();
    while ($row = $db->fetch_object($resql_all_products)) {
        $sql = "SELECT p.rowid as product_id, p.ref as product, cf.rowid as order_id, cf.ref as order_ref, e.rowid as warehouse_id, e.ref as warehouse, ps.reel as stock, IFNULl(cfd.qty, 1) as order_qty, IFNULL(cfd.subprice, p.cost_price) as order_price, s.rowid as supplier_id, p.barcode";
        $sql .= ",IF(pe.objimp = 02, (IFNULl(cfd.qty, 1) * IFNULL(cfd.subprice, p.cost_price)) / IFNULl(cfd.qty, 1) * 0.16,
          0) as iva";
        $sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields as pe ON p.rowid = pe.fk_object";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps ON p.rowid = ps.fk_product";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON ps.fk_entrepot = e.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet as cfd ON p.rowid = cfd.fk_product";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cf ON cfd.fk_commande = cf.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON cf.fk_soc = s.rowid AND s.fournisseur = 1";
        $sql .= " WHERE p.entity = 1";
        $sql .= " AND ps.reel > 0";
        $sql .= " AND e.rowid = " . $warehouse_id;
        $sql .= " AND p.rowid = " . $row->product_id;
        if(!empty($conf->global->CEDIS_SUPPLIER)) $sql .= " AND cf.fk_soc <> " . $conf->global->CEDIS_SUPPLIER;
        // $sql .= " AND p.rowid = 286";
        // $sql .= " AND cf.fk_statut <> 0";
        if ($search_product && !empty($search_product)) {
            $sql .= " AND (p.ref LIKE '%" . $search_product . "%' OR p.label LIKE '%" . $search_product . "%' OR p.barcode LIKE '%" . $search_product . "%')";
        }
        if ($searchCategoryProductList && !empty($searchCategoryProductList)) {
            $sql .= " AND p.rowid IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList) . "))";
        }
        if ($searchCategoryProductList2 && !empty($searchCategoryProductList2)) {
            $sql .= " AND p.rowid NOT IN (SELECT fk_product FROM llx_categorie_product WHERE fk_categorie IN (" . implode(',', $searchCategoryProductList2) . "))";
        }
        $sql .= " ORDER BY cf.date_commande DESC, cfd.subprice DESC";

        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
            exit;
        }

        $total_price = 0;
        $total_qty = 0;
        $total_price_avg = 0;
        $qty = 0;
        $iva = 0;
        $product_id_prev = 0;
        $num = $db->num_rows($resql);
        while ($row = $db->fetch_object($resql)) {
            if ($product_id_prev != $row->product_id) {
                $qty = 0;
                $total_price = 0;
                $total_qty = 0;
                $product_id_prev = $row->product_id;
                $iva = 0;
            }

            // Calculate the potential new total quantity
            $new_total_qty = $qty + $row->order_qty;

            // Check if the current quantity has already met or exceeded stock
            if ($qty >= $row->stock) {
                // Stop processing further orders for this product
                continue;
            } else {
                $qty = $new_total_qty;
                $total_price += $row->order_qty * $row->order_price;
                $total_qty += $row->order_qty;
            }

            $iva = ($total_price / $total_qty) * 0.16;

            // Store results in the datos array
            $datos[$row->product_id] = array(
                'product_id' => $row->product_id,
                'supplier_id' => $row->supplier_id,
                'order_id' => $row->order_id,
                'order_ref' => $row->order_ref,
                'warehouse_id' => $row->warehouse_id,
                'stock' => $row->stock,
                'order_qty' => $row->order_qty,
                'order_price' =>  $total_price / $total_qty,
                'total_price' => (($total_price / $total_qty) + $iva) * $row->stock,
                'total_qty' => $total_qty,
                'iva' => $iva,
                'order_price_avg' => ($total_price / $total_qty) + $iva,
                'barcode' => $row->barcode
            );
        }
    }
}


$param = '';
if ($dayinicio)      $param .= '&inicio_day=' . urlencode($dayinicio);
if ($monthinicio)    $param .= '&inicio_month=' . urlencode($monthinicio);
if ($yearinicio)     $param .= '&inicio_year=' . urlencode($yearinicio);
if ($dayfinal)       $param .= '&final_day=' . urlencode($dayfinal);
if ($monthfinal)     $param .= '&final_month=' . urlencode($monthfinal);
if ($yearfinal)      $param .= '&final_year=' . urlencode($yearfinal);
if ($warehouse_id)      $param .= '&fk_warehouse=' . urlencode($warehouse_id);

/*
 * View
 */
llxHeader("", $titlepage);

print load_fiche_titre($titlepage, $linkback = "");
print '<div class="fichecenter">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="8">' . $langs->trans("Precio de compra") . '</th>';
print '</tr>';
print '<form name="searchFormList" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="buscar">';

$formproduct = new FormProduct($db);
$form = new Form($db);
print '<tr>';
print '<td>' . $langs->trans('Categoria:') . '</td>';
print '<td colspan="5">';
$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
$categoriesProductArr[-2] = '- ' . $langs->trans('NotCategorized') . ' -';
print $form->multiselectarray('search_category_product_list', $categoriesProductArr, ($searchCategoryProductList ? $searchCategoryProductList : $searchCategoryProductList2), 0, 0, 'minwidth300');
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Product') . ': ';
print img_info($langs->trans('Búsqueda por referencia, nombre o código de barras'));
print '</td>';
print '<td colspan="5">';
print '<input type="text" name="search_product" value="' . $search_product . '" size="30" />';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Existencias 0') . ': ';
print img_info($langs->trans('Si está marcado, se mostrarán los productos con existencias en 0'));
print '</td>';
print '<td colspan="5">';
print '<input type="checkbox" name="search_stock0" value="1" ' . ($search_stock0 ? 'checked="checked"' : '') . ' />';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Supplier') . ': ';
print img_info($langs->trans('Búsqueda por proveedor'));
print '</td>';
print '<td colspan="5">';
print $form->select_company((empty($supplier) ? '' : $supplier), 'supplier', 's.fournisseur=1', 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Warehouse') . ': ';
print '</td>';
print '<td colspan="5">';
print $formproduct->selectWarehouses($warehouse_id, 'fk_warehouse', '', 0);
print '</td>';
print '</tr>';

print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans('Search') . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="clearQueryParamsAndReload()">';
print '<script>
    function clearQueryParamsAndReload() {
        const url = window.location.href.split("?")[0];
        history.replaceState(null, null, url);
        location.reload();
    }
</script>';
print '</div>';
print '</form>';
print '<br>';
print '<br>';
print '<br>';





if ($action == 'buscar' && ($supplier && $supplier > 0)) {

    print '<table width="auto" style="position: relative; bottom: 40px;"><tr><td>';
    print '<form method="POST" id="FormularioExportacion" action="export_buyprice_report.php">';
    print '<input type="hidden" id="sqlexport" name="sqlexport" value="' . base64_encode($sql) . '"/>';
    print '<input type="hidden" id="nom" name="nom" value="' . $warehouse->nom . '"/>';
    print '<input type="hidden" id="rfc_label" name="rfc_label" value="' . $warehouse->rfc_label . '"/>';
    print '<input type="hidden" id="action" name="action" value="' . $action . '"/>';
    print '<input type="hidden" id="case" name="case" value="supplier"/>';
    print '</form>';
    print '<a name="exportar" id="exportar"><img src="./img/xlsx.png" alt="" style="position: relative; bottom: 10px; cursor: pointer;"></a>';
    print '<script language="javascript">
                $(document).ready(function() {
                    $("#exportar").on("click",function() {
                        $("#FormularioExportacion").submit();
                    });
                });
            </script>';
    print '</td></tr></table>';

    print '<table class="liste" style="position: relative; bottom: 30px;">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Product"));
    print_liste_field_titre($langs->trans("Supplier"));
    print_liste_field_titre($langs->trans("Categories"));
    print_liste_field_titre($langs->trans("Cant."));
    print_liste_field_titre($langs->trans("P.U. Promedio"));
    print_liste_field_titre($langs->trans("IVA"));
    print_liste_field_titre($langs->trans("Total Unitario"));
    print_liste_field_titre($langs->trans("Total"));
    print '</tr>';
    if ($resql > 0) {
        $iva_total = 0;
        $total = 0;
        while ($row = $db->fetch_object($resql)) {
            $soc = new Societe($db);
            $soc->fetch($row->supplier_id);
            $product = new Product($db);
            $product->fetch($row->product_id);

            $iva_total += $row->iva;
            $total += $row->total;

            // Product
            print "<td>" . $product->getNomUrl(1) . "</td>";
            // Supplier
            print "<td>" . $soc->getNomUrl(1) .  "</td>";
            // Categories
            print "<td>" . $form->showCategories($product->id, 'product', 1) .  "</td>";
            // Quantity
            print "<td>" . $row->stock .  "</td>";
            // PU Promedio
            print "<td>" . $row->order_price .  "</td>";
            // iva
            print "<td align='left'>" . $row->iva . "</td>";
            // Total Unitario
            print "<td align='left'>" . $row->order_price_avg . "</td>";
            // Total
            print "<td align='left'>" . $row->order_total . "</td>";
            print "</tr>";
        }
    }
    print '</table>';
} else if ($action == 'buscar' && ($search_stock0 && $search_stock0 == 1)) {
    print '<table width="auto" style="position: relative; bottom: 40px;"><tr><td>';
    print '<form method="POST" id="FormularioExportacion" action="export_buyprice_report.php">';
    print '<input type="hidden" id="sqlexport" name="sqlexport" value="' . base64_encode($sql_existencias_cero) . '"/>';
    // Action 
    print '<input type="hidden" id="action" name="action" value="' . $action . '"/>';
    print '<input type="hidden" id="case" name="case" value="existencias_cero"/>';
    print '<input type="hidden" id="nom" name="nom" value="' . $warehouse->nom . '"/>';
    print '<input type="hidden" id="rfc_label" name="rfc_label" value="' . $warehouse->rfc_label . '"/>';
    print '</form>';
    print '<a name="exportar" id="exportar"><img src="./img/xlsx.png" alt="" style="position: relative; bottom: 10px; cursor: pointer;"></a>';
    print '<script language="javascript">
                $(document).ready(function() {
                    $("#exportar").on("click",function() {
                        $("#FormularioExportacion").submit();
                    });
                });
            </script>';
    print '</td></tr></table>';

    print '<table class="liste" style="position: relative; bottom: 30px;">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Product"));
    print_liste_field_titre($langs->trans("Supplier"));
    print_liste_field_titre($langs->trans("Categories"));
    print_liste_field_titre($langs->trans("Cant."));
    print_liste_field_titre($langs->trans("P.U. Promedio"));
    print_liste_field_titre($langs->trans("IVA"));
    print_liste_field_titre($langs->trans("Total Unitario"));
    print_liste_field_titre($langs->trans("Total"));
    print '</tr>';

    if ($resql_existencias_cero > 0) {
        $iva_total = 0;
        $total = 0;
        while ($row = $db->fetch_object($resql_existencias_cero)) {
            $product = new Product($db);
            $product->fetch($row->rowid);

            // Product
            print "<td>" . $product->getNomUrl(1) . "</td>";
            // Supplier
            print "<td></td>";
            // Categories
            print "<td>" . $form->showCategories($product->id, 'product', 1) .  "</td>";
            // Quantity
            print "<td>" . 0 .  "</td>";
            // PU Promedio
            print "<td>" . price($row->price) .  "</td>";
            // iva
            print "<td align='left'>" . number_format($row->iva, 2) . "</td>";
            // Total Unitario
            print "<td align='left'>" . price($row->price + $row->iva) . "</td>";
            // Total
            print "<td align='left'>" . price($row->price + $row->iva) . "</td>";
            print "</tr>";
        }
    }
} else if ($action == 'buscar') {
    print '<table width="auto" style="position: relative; bottom: 40px;"><tr><td>';
    print '<form method="POST" id="FormularioExportacion" action="export_buyprice_report.php">';
    print '<input type="hidden" id="datos" name="datos" value=\'' . htmlspecialchars(json_encode($datos), ENT_QUOTES, 'UTF-8') . '\'/>';    // Action
    print '<input type="hidden" id="action" name="action" value="' . $action . '"/>';
    print '<input type="hidden" id="case" name="case" value="all"/>';
    print '<input type="hidden" id="nom" name="nom" value="' . $warehouse->nom . '"/>';
    print '<input type="hidden" id="rfc_label" name="rfc_label" value="' . $warehouse->rfc_label . '"/>';
    print '</form>';
    print '<a name="exportar" id="exportar"><img src="./img/xlsx.png" alt="" style="position: relative; bottom: 10px; cursor: pointer;"></a>';
    print '<script language="javascript">
                $(document).ready(function() {
                    $("#exportar").on("click",function() {
                        $("#FormularioExportacion").submit();
                    });
                });
            </script>';
    print '</td></tr></table>';

    print '<table class="liste" style="position: relative; bottom: 30px;">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Product"));
    print_liste_field_titre($langs->trans("Supplier"));
    print_liste_field_titre($langs->trans("Categories"));
    print_liste_field_titre($langs->trans("Cant."));
    print_liste_field_titre($langs->trans("P.U. Promedio"));
    print_liste_field_titre($langs->trans("IVA"));
    print_liste_field_titre($langs->trans("Total Unitario"));
    print_liste_field_titre($langs->trans("Total"));
    print '</tr>';

    if ($datos && count($datos) > 0) {
        foreach ($datos as $row) {
            $product = new Product($db);
            $product->fetch($row['product_id']);

            $soc = new Societe($db);
            $soc->fetch($row['supplier_id']);

            // Product
            print "<td>" . $product->getNomUrl(1) . "</td>";
            // Supplier
            print "<td></td>";
            // Categories
            print "<td>" . $form->showCategories($product->id, 'product', 1) .  "</td>";
            // Quantity
            print "<td>" . $row['stock'] .  "</td>";
            // PU Promedio
            print "<td>" . price($row['order_price']) .  "</td>";
            // iva
            print "<td align='left'>" . number_format($row['iva'], 4) . "</td>";
            // Total Unitario
            print "<td align='left'>" . price($row['order_price_avg']) . "</td>";
            // Total
            print "<td align='left'>" . price($row['total_price']) . "</td>";
            print "</tr>";
        }
    }
    print '</table>';
}

// End of page
llxFooter();
$db->close();
