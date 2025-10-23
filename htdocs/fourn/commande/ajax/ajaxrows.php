<?php

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';

$fk_product = GETPOST('fk_product', 'int');
$qty = GETPOST('qty', 'int');
$batch = GETPOST('batch', 'alpha');
$eatby = GETPOST('eatby', 'alpha');
$sellby = GETPOST('sellby', 'alpha');
$warehouse = GETPOST('warehouse', 'int');
$entrepot = GETPOST('entrepot', 'alpha');
$comment = GETPOST('comment', 'alpha');
$fk_commandefourndet = GETPOST('fk_commandefourndet', 'int') ?: 0;

$product = new Product($db);
$warehouse_static = new Entrepot($db);
$product->fetch($fk_product);

$row = '<tr name="' . $fk_product . '_' . $batch . '_' . $qty . '" id="' . $fk_product . '_' . $batch . '_' . $qty . '">';

//TD PRODUCT INFO
$row .= '<td name="' . $fk_product . '" id="' . $fk_product . '">';
$row .= $product->getNomUrl(1);
$row .= '</td>';

//TD WAREHOUSE
$row .= '<td name="' . $warehouse . '" id="' . $warehouse . '">';
$warehouse_static->id = $warehouse;
$warehouse_static->libelle = $entrepot;
$row .= $warehouse_static->getNomUrl(1);
$row .= '</td>';

//TD PRODUCT QTY
$row .= '<td name="' . $qty . '" id="' . $qty . '">';
$row .= $qty;
$row .= '</td>';

//TD PRODUCT BATCH
$row .= '<td name="' . $batch . '" id="' . $batch . '">';
$row .= $batch;
$row .= '</td>';

//TD PRODUCT EATBY
$row .= '<td name="' . $eatby . '" id="' . $eatby . '">';
$row .= $eatby;
$row .= '</td>';

//TD PRODUCT SELBY
$row .= '<td name="' . $sellby . '" id="' . $sellby . '">';
$row .= $sellby;
$row .= '</td>';

//TD PRODUCT COMMENT
$row .= '<td name="' . $comment . '" id="' . $comment . '" hidden></td>';

//TD COMMANDEFOURNDET ID (hidden)
$row .= '<td name="' . $fk_commandefourndet . '" id="' . $fk_commandefourndet . '" hidden></td>';

$row .= '</tr>';

echo $row;