<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

function generateRow($product, $lot, $qty)
{
    $rowId = "{$product->id}_{$qty}_{$lot->id}";
    $qtyInputId = "qty_{$rowId}";

    $row = "<tr name=\"$rowId\" id=\"$rowId\">";

    // TD PRODUCT INFO
    $row .= "<td name=\"$product->id\" id=\"$product->id\" style=\"width: 40%;\">";
    $row .= $product->getNomUrl(1);
    $row .= '</td>';

    // TD PRODUCT BATCH
    $row .= "<td name=\"{$lot->id}\" id=\"{$lot->id}\" style=\"width: 30%;\">";
    $row .= $lot->getNomUrl(1);
    $row .= '</td>';

    // TD PRODUCT QTY
    $row .= "<td name=\"$qty\" id=\"$qty\" style=\"width: 30%;\">";
    $row .= "<input type=\"number\" name=\"qty_$product->id\" id=\"$qtyInputId\" value=\"$qty\" min=\"0\">";
    $row .= '</td>';

    $row .= '</tr>';

    return $row;
}

$fk_product = GETPOST('fk_product', 'int');
$qty = GETPOST('qty', 'int');
$batch = GETPOST('batch', 'alpha');
$eatby = GETPOST('eatby', 'alpha');
$sellby = GETPOST('sellby', 'alpha');


$product = new Product($db);
$product->fetch($fk_product);
$lot = new Productlot($db);
$lot->fetch('', $fk_product, $batch, $eatby, $sellby);

echo generateRow($product, $lot, $qty);