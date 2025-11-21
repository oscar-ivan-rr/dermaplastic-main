<?php
  require '../main.inc.php';
  require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

  ini_set('display_errors', '1');

  $sql = "SELECT rowid, price_ttc FROM llx_product WHERE exentoiva=1";
  $res = $db->query($sql);
  $product = new Product($db);
  //var_dump($user);
  while($item = $db->fetch_object($res)){
    $product->fetch($item->rowid);
    $product->updatePrice($item->price_ttc, 'HT', $user, '0');
    echo 'Actualizado: ' . $item->rowid . '<br/>';
  }
?>