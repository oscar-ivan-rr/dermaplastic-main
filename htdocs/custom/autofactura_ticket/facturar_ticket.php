<?php
require_once '../../master.inc.php';
if(!empty($_POST['folio_ticket'])){

    $folio_ticket = dol_sanitizeFileName($_POST['folio_ticket']);

    require_once DOL_DOCUMENT_ROOT . '/custom/pos/class/ticket.class.php';
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
    ob_start();
    $ticket = new Ticket($db);
    $result = $ticket->fetch(0, $folio_ticket);
    if($result > 0){
        $user = new User($db);
        $user->fetch(1);
        $conf->global->MAIN_USE_ADVANCED_PERMS = true;
        $user->rights->facture->invoice_advance->validate = true;
        $facid = $ticket->create_facture();
        if($facid > 0){
            $facture = new Facture($db);
            $facture->fetch($facid);
            $pre_response = array(
                'folio_ticket' => $folio_ticket,
                'folio_factura' => $facture->ref,
                'resultado' => '1'
            );
        }
        else{
            $pre_response = array( 'resultado' => '-1' );
        }

    }
    else {
        $pre_response = array( 'resultado' => '-1' );
    }
    ob_get_clean();
    $response = json_encode($pre_response);
    echo $response;


}