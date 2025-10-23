<?php
require_once '../../master.inc.php';
if(!empty($_POST['folio_ticket']) && !empty($_POST['monto'])){
    global $db;

    $folioTicket = dol_sanitizeFileName($_POST['folio_ticket']);
    $monto = dol_sanitizeFileName($_POST['monto']);

    $sql_facture = "SELECT ref, paye, total_ttc, DATE_FORMAT(LAST_DAY(f.datef), '%Y-%m-%d') as fecha_fin_mes,
       DATE_FORMAT(NOW(), '%Y-%m-%d') as fecha_hoy,
       DATE_FORMAT(CONVERT_TZ(NOW(), 'UTC', 'America/Mexico_City'), '%H:%i:%s') as hora_actual FROM " . MAIN_DB_PREFIX . "facture as f WHERE ref = '$folioTicket' AND total_ttc BETWEEN ($monto - 0.5) AND ($monto + 0.5)";
    $result = $db->query($sql_facture);
    $facture = $db->fetch_object($result);

    if($result > 0){
        if($facture->paye == 1){
            $fecha_fin_mes_mas_uno = date('Y-m-d', strtotime($facture->fecha_fin_mes . ' + 1 day'));
            
            if($facture->fecha_hoy > $fecha_fin_mes_mas_uno && $facture->hora_actual >= '00:00:00'){
                $pre_response = array(
                    'resultado' => '4'
                );
            }else{
                $pre_response = array(
                    'folio_ticket' => $facture->ref,
                    'folio_factura' => $facture->ref,
                    'monto' => $facture->total_ttc,
                    'resultado' => '1'
                );
            }
        }
        else {
            $pre_response = array(
                'folio_ticket' => $facture->ref,
                'resultado' => '3'
            );
        }
        


    }
    else {
        $pre_response = array( 'resultado' => '-1' );
    }
    $response = json_encode($pre_response);
    echo $response;

}