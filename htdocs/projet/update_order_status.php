<?php

require '../main.inc.php';
$id_shippments = json_decode(file_get_contents('php://input'), true);
for($i = 0; $i < count($id_shippments); $i++) {
    $sql = 'UPDATE '.MAIN_DB_PREFIX.'expedition SET fk_statut = 1 WHERE rowid="'.$id_shippments[$i]["id"].'";';
    $resSQL = $db->query($sql);
    echo $sql;
}
//echo  $resSQL;
?>