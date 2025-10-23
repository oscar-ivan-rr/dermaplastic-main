<?php
$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

$limit = 25;
$action = GETPOST('action');
if($action == 'search_ticket'){
    $name_client = GETPOST('name_client');
    $tiket_number = GETPOST('ticket_num');
    $sql = "SELECT t.ticketnumber,s.nom,t.rowid FROM ".MAIN_DB_PREFIX."pos_ticket as t";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON t.fk_soc = s.rowid";
    $sql.= " WHERE t.fk_facture is null";
    if($tiket_number != "")
        $sql.= " AND t.ticketnumber like '%".$tiket_number."%'";
    if($name_client != "")
        $sql.= " AND s.rowid =".$name_client;
    $sql.= " ORDER BY t.rowid DESC LIMIT ".$limit;
    $resql = $db->query($sql);
    $return = array();
    if($resql){
        while($object = $db->fetch_object($resql)){
            array_push($return,array('ticketnumber'=>$object->ticketnumber, 'nom' => $object->nom, 'rowid' =>$object->rowid));
        }
    }
    echo json_encode($return);
}elseif ($action == 'search_client'){
    $name_client = GETPOST('name_client');
    $tiket_number = GETPOST('ticket_num');
    $sql = "SELECT t.ticketnumber,s.nom,t.rowid FROM ".MAIN_DB_PREFIX."pos_ticket as t";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON t.fk_soc = s.rowid";
    $sql.= " WHERE t.fk_facture is null";
    if($tiket_number != "")
        $sql.= " AND t.ticketnumber like '%".$tiket_number."%'";
    if($name_client != "")
        $sql.= " AND s.rowid =".$name_client;
    $sql.= " ORDER BY t.rowid DESC LIMIT ".$limit;
    $resql = $db->query($sql);
    $return = array();
    if($resql){
        while($object = $db->fetch_object($resql)){
            array_push($return,array('ticketnumber'=>$object->ticketnumber, 'nom' => $object->nom, 'rowid' =>$object->rowid));
        }
    }
    echo json_encode($return);
}elseif ($action == 'changeclient'){
    $checks = GETPOST('checks','array');
    $newClient = GETPOST('newclient');
    $error = 0;
    foreach ($checks as $key => $val){
        $sql = "SELECT s.nom,t.note FROM ".MAIN_DB_PREFIX."pos_ticket as t";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON t.fk_soc = s.rowid";
        $sql.= " WHERE t.rowid =".$val;
        $resql = $db->query($sql);
        $object = $db->fetch_object($resql);
        $note = '';
        if($object->note){
            $note = $object->note.', este ticket antes le pertenecia a '.$object->nom;
        }else{
            $note = 'Este ticket antes le pertenecia a '.$object->nom;
        }
        $change = "UPDATE ".MAIN_DB_PREFIX."pos_ticket SET fk_soc=".$newClient.",note='".$note."' WHERE rowid=".$val;
        $res = $db->query($change);
        if(!$res){
            $error++;
        }
    }
    echo $error;

}