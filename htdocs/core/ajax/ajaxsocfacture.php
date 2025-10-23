<?php

require '../../main.inc.php';

$id = GETPOST('soc', 'int');
$action = GETPOST('action', 'alpha');

//SQL
/*$sql = "SELECT s.cond_reglement,s.mode_reglement,s.fk_account,e.formpagcfdi,e.usocfdi FROM " . MAIN_DB_PREFIX . "societe as s INNER JOIN  " . MAIN_DB_PREFIX . "societe_extrafields as e on s.rowid=e.fk_object WHERE s.rowid = " . $id;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);

echo json_encode(array('cond' => $data->cond_reglement, 'mode' => $data->mode_reglement, 'bank' => $data->fk_account, 'pag' => $data->formpagcfdi, 'uso' => $data->usocfdi));*/
//Se busca primero la informaciòn del cliente
$sql = "SELECT s.cond_reglement,s.mode_reglement,s.fk_account FROM " . MAIN_DB_PREFIX . "societe as s WHERE s.rowid = " . $id;
$result = $db->query($sql);
if ($result)
    $data = $db->fetch_object($result);
    //La segunda consulta se hace por si no tiene informaciòn de CFDI, al separarla no pone la informaciòn del cliente en null
    $sql2 ="SELECT e.formpagcfdi,e.usocfdi FROM " . MAIN_DB_PREFIX . "societe_extrafields as e WHERE e.fk_object =".$id;
    $result2 = $db->query($sql2);
    if ($result2)
        $data2 = $db->fetch_object($result2);

echo json_encode(array('cond' => $data->cond_reglement, 'mode' => $data->mode_reglement, 'bank' => $data->fk_account, 'pag' => $data2->formpagcfdi, 'uso' => $data2->usocfdi));