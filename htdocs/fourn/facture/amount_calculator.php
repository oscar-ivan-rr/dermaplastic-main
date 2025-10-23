<?php

if ($_POST['id']){
    require_once '../../main.inc.php';
    require_once '../class/fournisseur.facture.class.php';
    $id = dol_sanitizeFileName($_POST['id']);
    $sql = 'SELECT f.multicurrency_total_ttc, f.total_ttc, f.discount_applied, s.earlypayment_discount ';
    $sql .= 'FROM ' . MAIN_DB_PREFIX . 'facture_fourn as f, '.MAIN_DB_PREFIX.'societe as s ';
    $sql .= 'WHERE f.rowid = ' . $id;
    $sql .= ' AND f.fk_soc = s.rowid';
    $sql .= ' LIMIT 1';
    $result = $db->query($sql);
    $response = '';
    if ($result) {
        $row = $db->fetch_object($result);
        $facturestatic = new FactureFournisseur($db);
        $facturestatic->fetch($id);
        $paiement = $facturestatic->getSommePaiement();
        $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
        $totaldeposits = $facturestatic->getSumDepositsUsed();
        $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
        if ($row->discount_applied > 0){
            $total = $row->total_ttc;
        }
        else {
            ($row->multicurrency_total_ttc > 0)? $total = $row->multicurrency_total_ttc : $total = $row->total_ttc;
        }
        if ($facturestatic->type != 2)
            $total_ttc = $total - $totalpay;
        else
            $total_ttc = $total + $totalpay;

        # Para Notas de Crédito el importe debe ser negativo
        if ($facturestatic->type == 2)
        {
            $total_ttc = $total_ttc * -1;
        }
        $pre_response = array(
            'amount' => $total_ttc,
            'discount' => ($row->earlypayment_discount==null?0:$row->earlypayment_discount)
        );
        $response = json_encode($pre_response);
        $db->free($result);
    }
    else {
        $pre_response = array('error' => 'Error en la consulta a la BD: '.$sql);
        $response = json_encode($pre_response);
    }
    echo $response;
}
else {
    echo 'error';
}

?>