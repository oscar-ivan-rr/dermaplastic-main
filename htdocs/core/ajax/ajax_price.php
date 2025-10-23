<?php

require '../../main.inc.php';

$id = GETPOST('soc', 'int');
$action = GETPOST('action', 'alpha');



$sql = "SELECT cost_price FROM llx_product  WHERE rowid = " . $id;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);
    $precio =$data->cost_price;
    $precio= number_format($precio,2);

echo json_encode(array('precio' => $precio));