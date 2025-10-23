<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

function generateRow($fk_product, $qty, $batch, $db)
{
	$product = new Product($db);
	$product->fetch($fk_product);
	$lot = new Productlot($db);
	$lot->fetch($batch);

	$rowId = "{$fk_product}_{$qty}_{$batch}";
	$qtyInputId = "qty_{$rowId}";
	$lotid = "lotid_{$lot->id}";

	$row = "<tr name=\"$rowId\" id=\"$rowId\">";

	// TD PRODUCT INFO
	$row .= "<td name=\"$fk_product\" id=\"$fk_product\" style=\"width: 40%;\">";
	$row .= $product->getNomUrl(1);
	$row .= '</td>';

	// TD PRODUCT BATCH
	$row .= "<td name=\"$batch\" id=\"$batch\" style=\"width: 30%;\">";
	$row .= $lot->getNomUrl(1);
	$row .= '</td>';

	// TD PRODUCT LOTID
	$row .= "<td name=\"$lotid\" id=\"$lotid\" style=\"width: 30%;\">";
	$row .= $lot->id;
	$row .= '</td>';

	// TD PRODUCT QTY
	$row .= "<td name=\"$qty\" id=\"$qty\" style=\"width: 30%;\">";
	$row .= "<input type=\"number\" name=\"qty_$fk_product\" id=\"$qtyInputId\" value=\"$qty\" min=\"0\">";
	$row .= '</td>';

	$row .= '</tr>';

	return $row;
}

$fk_product = GETPOST('fk_product', 'int');
$qty = GETPOST('qty', 'int');
$batch = GETPOST('batch', 'alpha');
$warehouse = GETPOST('warehouse', 'alpha');

echo generateRow($fk_product, $qty, $batch, $db);