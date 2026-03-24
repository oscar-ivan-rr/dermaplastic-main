<?php
ini_set('display_errors', '1');
ini_set('memori_limit', '1G');
ini_set('max_execution_time', 0);
require 'main.inc.php';
$handle = fopen('stocks.csv','r');

$rows = array();
$warahouses = array('key' => '1');
$sql = "SELECT * FROM llx_entrepot";
$result = $db->query($sql);
while($row = $db->fetch_object($result)) {
  $warahouses[$row->ref] = $row->rowid;
}

foreach($warehouses as $w => $key) {
  echo $w . ' ' . $key . '<br/>';
}

while ( ($data = fgetcsv($handle, 100, ',') ) !== FALSE ) {
  $rows[] = $data;
}

$c = count($rows);
for($i = 1; $i<$c; $i++) {
  $line = $rows[$i];
  $barcode = $line[1];
  $min = $line[2];
  $reorden = $line[3];
  $max = $line[4];
  $sql = "SELECT rowid FROM llx_product WHERE barcode='$barcode' LIMIT 1";
  $product = $db->fetch_object($db->query($sql));
  $warehouseId = $warahouses[$line[0]];
  $productId = $product->rowid;
  $sql = "UPDATE llx_product_warehouse_properties SET desiredstock=$min, seuil_stock_alerte=$reorden, stock_max=$max WHERE fk_product=$productId AND fk_entrepot=$warehouseId";
  $db->query($sql);
  echo $barcode . '<br/>';
}
?>