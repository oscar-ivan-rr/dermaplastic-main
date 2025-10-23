<?php

require '../../main.inc.php';

$fk_entrepot = GETPOST('fk_entrepot', 'int');
$action = GETPOST('action', 'alpha');



$sql = "SELECT lcr.code as rfc FROM llx_c_rfc lcr JOIN llx_entrepot le ON lcr.rowid = le.fk_rfc WHERE le.rowid = ".$fk_entrepot;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);

echo json_encode(array('rfc' => $data->rfc));