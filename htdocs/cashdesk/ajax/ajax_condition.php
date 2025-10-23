<?php
require '../../main.inc.php';
$idCondition = $_POST['id'];
$idNote = $_POST['idNote'];
$idCondition_edit = $_POST['idCondition_edit'];
$idProd = $_POST['idProd'];
$new = $_POST['new'];
$use_condition = $_POST['use_condition'];
$additional_products = $_POST['additional_products'];
$msg = "";

// Realizar las operaciones necesarias con la base de datos
if(isset($idCondition)){
    $sql = "DELETE FROM llx_condition WHERE rowid='".$idCondition."'";
    $resql = $db->query($sql);
    if($resql > 0){
        $msg = "Padecimiento eliminado correctamente";
    }
}

if(isset($idNote)){
    $sql = "DELETE FROM llx_condition_note WHERE rowid='".$idNote."'";
    $resql = $db->query($sql);
    if($resql > 0){
        $msg = "Nota eliminada correctamente";
    }
}

if(isset($idCondition_edit) && isset($idProd)){

    $sql = "DELETE FROM llx_condition_product WHERE fk_product='".$idProd."' AND fk_condition='".$idCondition_edit."'";
    $resql = $db->query($sql);
    if($resql > 0){
        $msg = "Producto eliminado correctamente";
    }
}

if(isset($new)){
    unset($_SESSION['serObjFacturation']);
    unset($_SESSION['poscart']);
    unset($_SESSION['condition_id']);
    unset($_SESSION['use_condition']);
    unset($_SESSION['additional_products']);
    $msg = "Limpieza de variables exitosa";
}

if(isset($use_condition)){
    $_SESSION['use_condition'] = $use_condition;
    $msg = "Limpieza de variables exitosa";
}

if(isset($additional_products)){
    $_SESSION['additional_products'] = $additional_products;
    $msg = "Productos adicionales guardados exitosamente";
}

echo json_encode(array('msg' => $msg));

?>