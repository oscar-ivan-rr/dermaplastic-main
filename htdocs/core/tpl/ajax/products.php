<?php
  require '../../../main.inc.php';
  $outjson = array();
  $search = GETPOST('search', 'alpha');
  $sql = "SELECT ref, barcode FROM dermaplastic_test2.llx_product WHERE barcode LIKE '%$search%' LIMIT 20";
  $result = $db->query($sql);

  while($row = $db->fetch_object($result)) {
    $outjson[] = array(
      'label' => $row->ref . ' (' . $row->barcode . ')',
      'value' => $row->barcode
    );
  }

  print json_encode($outjson);
?>