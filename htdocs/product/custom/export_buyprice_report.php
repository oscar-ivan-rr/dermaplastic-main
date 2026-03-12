<?php

/**
 * Export buy price report
 * @package    Product
 * @subpackage Custom
 * @author     Jesus Montalvo
 * @version    1.0
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

global $db, $conf, $langs, $user;

$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));
$sql = base64_decode(GETPOST('sqlexport', 'alpha'));
$action = GETPOST('action', 'alpha');
$case = GETPOST('case', 'alpha');
$name_warehouse = GETPOST('nom', 'alpha');

header('Content-type: application/vnd.ms-excel charset=iso-8859-1');
header('Content-disposition: attachment; filename="Reporte precio de compra.xls"');
header("Pragma: no-cache");
header("Expires: 0");

$table = "&nbsp;";
$table .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8;">';
$table .= '<table class="liste" style="position: relative; bottom: 30px; font-size: 1.3em;">';
// $table .= '<tr>';
// $table .= '<th colspan="16" style="background-color: #9BC2E6;">Reporte de precio de compra</th>';
// $table .= '</tr>';
// $table .= '<tr><th></th></tr>';
$table .= '<tr>';
$table .= '<th style="border: 2px solid black;">Producto</th>';
$table .= '<th style="border: 2px solid black;">SKU</th>';
$table .= '<th style="border: 2px solid black;">Almacen</th>';
$table .= '<th style="border: 2px solid black;">Proveedor</th>';
$table .= '<th style="border: 2px solid black;">Etiquetas/Categorías</th>';
$table .= '<th style="border: 2px solid black;">Cant.</th>';
$table .= '<th style="border: 2px solid black;">P.U. Promedio</th>';
$table .= '<th style="border: 2px solid black;">IVA</th>';
$table .= '<th style="border: 2px solid black;">Total Unitario</th>';
$table .= '<th style="border: 2px solid black;">Total</th>';
$table .= '</tr>';

if ($action == 'buscar' && $case == 'supplier') {
    $resql = $db->query($sql);
    if ($resql > 0) {
        $iva_total = 0;
        $total = 0;
        while ($row = $db->fetch_object($resql)) {
            $iva_total += $row->iva;
            $total += $row->total;
            $table .= '<tr>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= $row->product . '</td>';
            $table .= '<td style="border: 1px solid gray; text-align: center;widht: 50px;">';
            $table .= trim($row->barcode) . '</td>';
            $table .= '<td style="border: 1px solid gray; text-align: center;widht: 50px;">';
            $table .= trim($row->warehouse) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= $row->supplier . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= $row->categories . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= $row->stock . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->order_price) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->iva) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->order_price_avg) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->order_total) . '</td>';
            $table .= '</tr>';
        }
    }
} else if ($action == 'buscar' && $case == 'existencias_cero') {
    $resql = $db->query($sql);
    if ($resql > 0) {
        $iva_total = 0;
        $total = 0;
        while ($row = $db->fetch_object($resql)) {
            $iva_total += $row->iva;
            $total += $row->total;
            $table .= '<tr>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= $row->ref . '</td>';
            $table .= '<td style="border: 1px solid gray; text-align: center;">';
            $table .= trim($row->barcode) . '</td>';
            $table .= '<td style="border: 1px solid gray; text-align: center;widht: 50px;">';
            $table .= trim($row->warehouse) . '</td>';
            $table .= '<td style="border: 1px solid gray;"></td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= $row->categories . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= 0 . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->price) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->iva) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->price + $row->iva) . '</td>';
            $table .= '<td style="border: 1px solid gray;">';
            $table .= price($row->price + $row->iva) . '</td>';
            $table .= '</tr>';
        }
    }
} else if ($action == 'buscar' && $case == 'all') {
    $datos = json_decode($_POST['datos'], true);
    $iva_total = 0;
    $total = 0;
    foreach ($datos as $row) {
        $product = new Product($db);
        $product->fetch($row['product_id']);
        $iva_total += $row['iva'];
        $total += $row['total_price'];
        $table .= '<tr>';
        $table .= '<td style="border: 1px solid gray;">';
        $table .= $product->ref . '</td>';
        $table .= '<td style="border: 1px solid gray; text-align: center;">';
        $table .= trim($product->barcode) . '</td>';
        $table .= '<td style="border: 1px solid gray; text-align: center;">';
        $table .= $row['warehouse'] . '</td>';
        $table .= '<td style="border: 1px solid gray;"></td>';
        $sql_cat = "SELECT c.label FROM llx_product as p";
        $sql_cat .= " LEFT JOIN llx_categorie_product as cp ON p.rowid = cp.fk_product";
        $sql_cat .= " LEFT JOIN llx_categorie as c ON cp.fk_categorie = c.rowid";
        $sql_cat .= " WHERE p.rowid = " . $row['product_id'];
        $resql_cat = $db->query($sql_cat);
        $categories = '';
        while ($row_cat = $db->fetch_object($resql_cat)) {
            $categories .= $row_cat->label . ', ';
        }
        $categories = substr($categories, 0, -2);
        $table .= '<td style="border: 1px solid gray;">';
        $table .= $categories . '</td>';
        $table .= '<td style="border: 1px solid gray;">';
        $table .= $row['stock'] . '</td>';
        $table .= '<td style="border: 1px solid gray;">';
        $table .= price($row['order_price']) . '</td>';
        $table .= '<td style="border: 1px solid gray;">';
        $table .= price($row['iva']) . '</td>';
        $table .= '<td style="border: 1px solid gray;">';
        $table .= price($row['order_price_avg']) . '</td>';
        $table .= '<td style="border: 1px solid gray;">';
        $table .= price($row['total_price']) . '</td>';
        $table .= '</tr>';
    }
}
$table .= '</table>';
$table = utf8_decode($table);
echo $table;
