<?php

/**
 *	Product Stock Replenish Report
 *
 *	@package	dolibarr
 *	@subpackage	product
 *	@version	$Id: export_replenishreport.php,v 1.0 2024-07-29 12:00:00Z
 *	@author		Jesus Montalvo
 **/
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Aumentar la memoria y el tiempo de ejecución
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once './lib/replenishment.lib.php';

$prod = new Product($db);
$form = new Form($db);
$categorie = new Categorie($db);
$entrepot = new Entrepot($db);

$sql = base64_decode($_POST['sql']);
$sql = substr($sql, 0, strrpos($sql, 'LIMIT'));

$limit = $_POST['limit'];
$file = 'ReplenishReport.xls';
$sql .= ' LIMIT ' . $limit;
$resql = $db->query($sql);
$nbrows = $db->num_rows($resql);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $file . '"');

header('Cache-Control: max-age=0');
header('Expires: 0');

$table = '';
$columns = 0;

$table .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table class="tagtable liste listwithfilterbefore" >' . "\n";
$table .= '<tr class="liste_titre">';
// Ref.	Código de barras, Categorias, Almacén, Stock Requerido, Sucursal, Stock CEDIS, Diferencia
$table .= '<th>Referencia</th>';
$table .= '<th>Código de barras</th>';
$table .= '<th>Categorias</th>';
$table .= '<th>Almacén</th>';
$table .= '<th>Stock Requerido Sucursal</th>';
$table .= '<th>Stock CEDIS</th>';
$table .= '<th>Diferencia</th>';
$table .= '</tr>';
$table .= '<tbody>';

$i = 0;

while ($i < ($limit ? min($nbrows, $limit) : $nbrows)) {
    $obj = $db->fetch_object($resql);
    $required_stock = $obj->required_stock;

    $prod->fetch($obj->rowid);
    $entrepot->fetch($obj->fk_entrepot);

    $arrCategorie = $categorie->containing($prod->id, 'product');
    $categories = '';
    foreach ($arrCategorie as $categ) {
        $categories .= $categ->label . ', ';
    }
    $categories = substr($categories, 0, -2);

    $table .= '<tr class="oddeven">';
    $table .= '<td>' . $prod->ref . '</td>';
    $table .= '<td>' . $prod->barcode . '</td>';
    $table .= '<td>' . $categories . '</td>';

    $table .= '<td>' . $entrepot->label . '</td>';
    $table .= '<td>' . $obj->required_stock . '</td>';
    $table .= '<td>' . $obj->stock_cedis . '</td>';
    $table .= '<td>' . $obj->diff_stock . '</td>';
    $table .= '</tr>';

    $i++;
}
$table .= '</tbody>';
$table .= '</table>';
$db->free($resql);

echo $table;
