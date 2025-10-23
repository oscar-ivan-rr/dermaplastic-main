<?php
if ($_POST['id']) {
    require_once '../../../main.inc.php';
    require_once '../class/ticket.class.php';
    $id = dol_sanitizeFileName($_POST['id']);
    $ticket = new Ticket($db);
    $result = $ticket->fetch($id);
    if($result > 0){
        $paiement = $ticket->getSommePaiement();
        $amount = $ticket->total_ttc - $paiement;
        $pre_response = array (
            'amount' => $amount
        );
    }
    else{
        $pre_response = array (
            'error' => true
        );
    }
    echo json_encode($pre_response);
}
