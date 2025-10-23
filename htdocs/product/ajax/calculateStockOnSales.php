<?php

require '../../main.inc.php';
$id_product = GETPOST('id', 'int');
$warehouse = GETPOST('warehouse', 'int');
$check = GETPOST('check', 'int');

// Creamos una query para verificar si existe el producto en el almacén
$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product_warehouse_properties WHERE fk_product = " . $id_product . " AND fk_entrepot = " . $warehouse;

// Ejecutamos la query
$result = $db->query($sql);

// Si existe el producto en el almacén, lo actualizamos
if ($result && $db->num_rows($result) > 0){
    $sql = "UPDATE " . MAIN_DB_PREFIX . "product_warehouse_properties SET calculateStock = " . $check . " WHERE fk_product = " . $id_product . " AND fk_entrepot = " . $warehouse;
}else{
    // Si no existe el producto en el almacén, lo creamos
    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_warehouse_properties (fk_product, fk_entrepot, calculateStock) VALUES (" . $id_product . ", " . $warehouse . ", " . $check . ")";
}

// Ejecutamos la query
$result = $db->query($sql);

if ($result)
    $msj = 'Se ha guardado correctamente';
else
    $msj = 'Ha ocurrido un error -> ' . $db->lasterror;

// Devolvemos el resultado
echo json_encode(array('msj' => $msj));
