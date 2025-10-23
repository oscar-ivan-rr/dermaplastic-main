<?php

require '../../main.inc.php';

$value = GETPOST('value', 'int');



$sql = "SELECT lcpt.nbjour AS days FROM llx_c_payment_term lcpt WHERE lcpt.rowid = ".$value;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);

echo json_encode(array('days' => $data->days));