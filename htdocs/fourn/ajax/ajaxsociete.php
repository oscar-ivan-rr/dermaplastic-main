<?php

require '../../main.inc.php';

$id = GETPOST('soc', 'int');
$action = GETPOST('action', 'alpha');



$sql = "SELECT s.name_alias, s.mode_reglement_supplier,s.cond_reglement_supplier FROM " . MAIN_DB_PREFIX . "societe as s WHERE s.rowid = " . $id;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);

echo json_encode(array('alias' => $data->name_alias, 'mode'=>$data->mode_reglement_supplier, 'cond' => $data->cond_reglement_supplier));