<?php

require '../../main.inc.php';

if (empty($user->rights->stock->edit_minmax)) {
	echo json_encode(array('result' => false, 'msj' => 'Sin permiso para editar mínimo/máximo'));
	exit;
}

$id_product = GETPOST('id', 'int');
$stock_max = GETPOST('stock_max', 'int');
$id_entrepot = GETPOST('id_entrepot', 'int');

// Creamos una query para verificar si existe el producto en el almacén
$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product_warehouse_properties WHERE fk_product = " . $id_product . " AND fk_entrepot = " . $id_entrepot;

// Ejecutamos la query
$result = $db->query($sql);

// Si existe el producto en el almacén, lo actualizamos
if ($result && $db->num_rows($result) > 0){
    $sql = "UPDATE " . MAIN_DB_PREFIX . "product_warehouse_properties SET stock_max = " . $stock_max . " WHERE fk_product = " . $id_product . " AND fk_entrepot = " . $id_entrepot;
}else{
    // Si no existe el producto en el almacén, lo creamos
    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_warehouse_properties (fk_product, fk_entrepot, stock_max) VALUES (" . $id_product . ", " . $id_entrepot . ", " . $stock_max . ")";
}

// Ejecutamos la query
$result = $db->query($sql);
if ($result)
    $msj = 'Se ha guardado correctamente';
else
    $msj = 'Ha ocurrido un error -> ' . $db->lasterror;

// Sacamos la sumatoria de las alertas de stock general de todos los almacenes del producto y la guardamos en la tabla de productos en el campo stock_max
$sql = "SELECT SUM(stock_max) AS total FROM " . MAIN_DB_PREFIX . "product_warehouse_properties WHERE fk_product = " . $id_product;
// Ejecutamos la query
$result = $db->query($sql);
// Si se ejecutó correctamente y hay resultados obtenidos almacenamos ese resultado en la variable $total y lo guardamos en la tabla de productos en el campo stock_max
if ($result && $db->num_rows($result) > 0){
    $obj = $db->fetch_object($result);
    $total = $obj->total;
    $sql = "UPDATE " . MAIN_DB_PREFIX . "product SET stock_max = " . $total . " WHERE rowid = " . $id_product;
    $result = $db->query($sql);
}

// Devolvemos el resultado
echo json_encode(array('result' => $result, 'msj' => $msj));
