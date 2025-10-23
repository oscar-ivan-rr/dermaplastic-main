<?php

if ($_POST['id'] && $_POST['type']==0){
    require_once '../main.inc.php';
    require_once  './facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
    $id = dol_sanitizeFileName($_POST['id']);
    $sql = 'SELECT f.rowid as id, f.fk_soc, f.paye, f.type, f.fk_statut, f.close_code, f.multicurrency_total_ttc, f.total_ttc, s.earlypayment_discount ';
    $sql .= 'FROM ' . MAIN_DB_PREFIX . 'facture as f, '.MAIN_DB_PREFIX.'societe as s ';
    $sql .= 'WHERE f.rowid = ' . $id;
    $sql .= ' AND f.fk_soc = s.rowid';
    $sql .= ' LIMIT 1';
    $result = $db->query($sql);
    $response = '';
    if ($result) {
        $row = $db->fetch_object($result);
        $facturestatic = new Facture($db);
        $facturestatic->fetch($id);
        $paiement = $facturestatic->getSommePaiement();
        $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
        $totaldeposits = $facturestatic->getSumDepositsUsed();


        $totalpay = abs($paiement)+abs($totalcreditnotes)+abs($totaldeposits);
        $remain = abs($facturestatic->total_ttc) - abs($totalpay);

        if ($facturestatic->statut == 2  && $facturestatic->close_code == 'discount_vat') {		// If invoice closed with discount for anticipated payment
            $remain = 0;
        }


        $discount = new DiscountAbsolute($db);
        if ($facturestatic->type == 2 && $row->paye == 1) {
            $remaincreditnote = $discount->getAvailableDiscounts($row->fk_soc, '', 'rc.fk_facture_source='.$row->id);
            $remain = -$remaincreditnote;
        }

        //$total = $row->total_ttc;
        //$total_ttc = abs($total) - abs($totalpay);
        
        if ($remain >= -0.019 && $remain <= 0.011){
            $remain = 0;
        }
        
        if($facturestatic->total_ttc < 0 && $remain > 0){
            $remain = -$remain;
        }
        
        if ($row->type != '2')
        {
	        $pre_response = array(
	            'amount' => round($remain,2),
                'discount' => ($row->earlypayment_discount==null?0:$row->earlypayment_discount)
	        );
        }
        else
        {
	        $pre_response = array(
	            'amount' => $remain,
	            'discount' => '0'
	        );
        	
        }
        $response = json_encode($pre_response);
        $db->free($result);
    }
    else 
    {
        $pre_response = array('error' => 'Error en la consulta a la BD: '.$sql);
        $response = json_encode($pre_response);
    }
    echo $response;
}
elseif ($_POST['id'] && $_POST['type']==1)
{
    require_once '../main.inc.php';
    require_once  '../custom/pos/class/ticket.class.php';
    $id = dol_sanitizeFileName($_POST['id']);
    $sql =	 'SELECT f.rowid,f.type '."\r\n"
			.'		,f.total_ttc '."\r\n"
			.'		,s.earlypayment_discount '."\r\n"
			.'		,SUM(pt.amount) AS customer_pay '."\r\n"
			.'FROM 	 llx_pos_ticket AS `f` '."\r\n"
			.'LEFT JOIN llx_societe as s '."\r\n"
			.'  ON f.fk_soc = s.rowid '."\r\n"
			.'LEFT JOIN llx_pos_paiement_ticket AS pt '."\r\n"
			.'  ON f.rowid = pt.fk_ticket '."\r\n"
			.'LEFT JOIN llx_paiement AS p '."\r\n"
			.'  ON pt.fk_paiement = p.rowid '."\r\n"
			.'WHERE f.rowid =  '.$id.' '."\r\n"
			.'GROUP BY f.rowid '."\r\n"
			.'';
    $result = $db->query($sql);
    $response = '';
    if ($result) {
        $row = $db->fetch_object($result);
        $total = $row->total_ttc - $row->customer_pay;
        if ($row->type == '0')
        {
	        $pre_response = array(
	            'amount' => round($total,2),
	            'discount' => ($row->earlypayment_discount==null?0:$row->earlypayment_discount)
	        );
        }
        else
        {
	        $pre_response = array(
	            'amount' => round(($total*-1),2),
	            'discount' => '0'
	        );
        	
        }
        $response = json_encode($pre_response);
        $db->free($result);
    }
    else 
    {
        $pre_response = array('error' => 'Error en la consulta a la BD: '.$sql);
        $response = json_encode($pre_response);
    }
    echo $response;
    
}
else {
    echo 'error';
}

?>