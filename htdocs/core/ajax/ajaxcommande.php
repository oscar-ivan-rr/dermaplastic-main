<?php

require '../../main.inc.php';

$id = GETPOST('soc', 'int');
$action = GETPOST('action', 'alpha');



$sql = "SELECT s.cond_reglement FROM " . MAIN_DB_PREFIX . "societe as s WHERE s.rowid = " . $id;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);

echo json_encode(array('cond' => $data->cond_reglement));