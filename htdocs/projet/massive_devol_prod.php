<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
$devol_qty = json_decode(file_get_contents('php://input'), true);
$devolucion = 0;
for($i = 0; $i < count($devol_qty); $i++) {
    $id_expedition = $devol_qty[$i]['id'];
    $qty_devol = $devol_qty[$i]['qty'];
    $ref_prod = $devol_qty[$i]['prod_ref'];
    $sqlQty = 'SELECT devolucion, qty, fk_entrepot, fk_expedition FROM '.MAIN_DB_PREFIX.'expeditiondet WHERE rowid = '.$id_expedition;
    $resqlQty = $db->query($sqlQty);
    if ($resqlQty){
        $objDev = $db->fetch_object($resqlDev);
        $devolucion = $objDev->devolucion + $qty_devol;
        $warehouse = $objDev->fk_entrepot;
        $id_envio =$objDev->fk_expedition;
    }
    $objQty = $db->fetch_object($resqlQty);
    $newQty = $objDev->qty - $qty_devol;
    if($newQty >= 0)
    {
        date_default_timezone_set("America/Mexico_City");
        $now = dol_now();
        $productIncremento = new Product($db);
        $productIncremento->fetch('',$ref_prod);
        $incrementoQty = new MouvementStock($db);
        $date_devol = new DateTime();
        $date_devol->format('Y-m-d H:i:s');
        $date_devol->setTimestamp($now);
        $date_devol->add((new DateInterval('PT5H')));
        $result = $incrementoQty->reception($user, $productIncremento->id, $warehouse, $qty_devol, $productIncremento->price_ttc, "Devolución de Envio",
        '','','',$date_devol->getTimestamp());
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'expeditiondet SET qty = '.$newQty.', devolucion = '.$devolucion.', date_devolucion = "'.$db->idate($now).'" WHERE rowid = '.$id_expedition.';';
        //echo $sql;
        $resultDevol = $db->query($sql);

        $sqlEnvio = 'SELECT fk_projet FROM '.MAIN_DB_PREFIX.'expedition WHERE rowid='.$id_envio.';';
        echo $sqlEnvio;
        $resqlSend = $db->query($sqlEnvio);
        if ($resqlSend){
            $objsend = $db->fetch_object($resqlSend);
            $project = $objsend->fk_projet;
            $sqlLastMov = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'stock_mouvement order by datem desc limit 1;';
            echo $sqlLastMov;
            $resqlLastMov = $db->query($sqlLastMov);
            if ($resqlLastMov){
                $obMov = $db->fetch_object($resqlLastMov);
                $idLastMov = $obMov->rowid;
                $sqlSetData = 'UPDATE '.MAIN_DB_PREFIX.'stock_mouvement set fk_origin= '.$id_envio.', fk_projet= '.$project.', origintype="shipping" WHERE rowid='.$idLastMov.'';
                echo $sqlSetData;
                $resDone = $db->query($sqlSetData);
            }
        }
        //var_dump($resultDevol);
    }
}
// echo $resultDevol;
?>