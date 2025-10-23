<?php
    require '../../main.inc.php';
    $barcode = GETPOST("barcode");

    $sql = "SELECT m.rowid ";
    $sql .= " FROM llx_product AS p, llx_stock_mouvement_draft AS m ";
    $sql .= " WHERE p.rowid = m.fk_product ";
    $sql .= " AND p.barcode = '".$barcode."'";

    $idTrans = array();
    $resql = $db->query($sql);
    if ($resql){
        while ($objTrans = $db->fetch_object($resql)) {
            $idTrans[] = $objTrans->rowid;
        }
    }
    echo json_encode($idTrans);
?>
