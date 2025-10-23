<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
$facture = new Facture($db);
$commande = new Commande($db);
$propal = new Propal($db);
//FACTURAS PENDIENTES POR PAGAR Y BORRADORES
$sqlF = 'SELECT f.rowid,f.ref,f.fk_statut';
$sqlF.= ' FROM '.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'facturedet as fd';
$sqlF.= ' WHERE fd.fk_facture = f.rowid AND fd.total_tva = 0 AND fd.qty > 0 and f.fk_statut >= 0 and f.fk_statut < 2 AND f.paye = 0';
$sqlF.= ' AND (SELECT cf.fk_facture FROM llx_cfdimx as cf where cf.fk_facture=f.rowid) is null';
$sqlF.= ' GROUP BY f.rowid';
$resqlF = $db->query($sqlF);
if($resqlF){
    while($objF = $db->fetch_object($resqlF)){
        $valid = false;
        $facture->fetch($objF->rowid);
        $alreadypaid = $facture->getSommePaiement();
        $i = 0;
        if($objF->fk_statut > 0 && !($alreadypaid > 0)){
            $valid = true;
            $facture->setDraft($user);
        }
        foreach($facture->lines as $key => $line){
            if($line->tva_tx == 0){
                $result = $facture->updateline(
                    $line->id, 
                    $line->desc, 
                    $line->subprice, 
                    $line->qty, 
                    $line->remise_percent, 
                    $line->date_start, 
                    $line->date_end, 
                    16, 
                    $line->localtax1_tx, 
                    $line->localtax2_tx, 
                    'HT', 
                    $line->info_bits, 
                    $line->product_type, 
                    $line->fk_parent_line, 
                    0, 
                    $line->fk_fournprice, 
                    $line->pa_ht, 
                    $line->label, 
                    $line->special_code, 
                    $line->array_options, 
                    $line->situation_percent, 
                    $line->fk_unit, 
                    $line->pu_ht
                );
                if ($result >= 0)
                {
                    $i ++;
                }
                print '<br>';
                $i++;
            }
        }
        if($valid){
            $facture->validate($user);
        }
        print 'Se le añadieron IVA a '.$i.' productos de la factura '.$objF->ref.'<br>';
    }
}
//PEDIDOS SIN FACTURAS
$sqlC = 'SELECT c.rowid,c.ref,c.fk_statut';
$sqlC.= ' FROM llx_commande AS c, llx_commandedet as cd';
$sqlC.= ' WHERE cd.fk_commande = c.rowid AND cd.total_tva = 0 AND cd.qty > 0 and cd.subprice > 0 and c.fk_statut >= 0';
$sqlC.= ' AND (';
$sqlC.= ' c.rowid NOT IN (SELECT e.fk_source FROM llx_element_element AS e WHERE e.targettype = "facture" AND e.sourcetype = "commande")';
$sqlC.= ' AND c.rowid NOT IN (SELECT e.fk_target FROM llx_element_element AS e WHERE e.sourcetype = "facture" AND e.targettype = "commande"))';
$sqlC.= ' GROUP BY c.rowid';
$resqlC = $db->query($sqlC);
if($resqlC){
    while($objC = $db->fetch_object($resqlC)){
        $commande->fetch($objC->rowid);
        $i = 0;
        $valid = false;
        if($objC->fk_statut > 0){
            $valid = true;
            $commande->setDraft($user);
        }
        foreach($commande->lines as $key => $line){
            if($line->tva_tx == 0){
                $result = $commande->updateline(
                    $line->id, 
                    $line->desc, 
                    $line->subprice, 
                    $line->qty, 
                    $line->remise_percent, 
                    16, 
                    $line->localtax1_tx, 
                    $line->localtax2_tx, 
                    'HT', 
                    $line->info_bits, 
                    $line->date_start, 
                    $line->date_end, 
                    $line->product_type, 
                    $line->fk_parent_line, 
                    0, 
                    $line->fk_fournprice, 
                    $line->pa_ht, 
                    $line->label, 
                    $line->special_code, 
                    $line->array_options, 
                    $line->fk_unit, 
                    $line->multicurrency_subprice
                );
                if ($result >= 0)
                {
                    $i ++;
                }
            }
        }
        if($valid){
            $commande->valid($user);
        }
        print 'Se le añadieron IVA a '.$i.' productos de la pedido '.$objC->ref.'<br>';
    }
}
//COTIZACIONES SIN NADA RELACIONADO
$sqlP = 'SELECT p.rowid,p.ref,p.fk_statut';
$sqlP.= ' FROM llx_propal as p,llx_propaldet as pd';
$sqlP.= ' WHERE p.rowid = pd.fk_propal AND pd.total_tva = 0 AND pd.qty > 0 and pd.total_ht <> 0';
$sqlP.= ' AND p.rowid not in (SELECT e.fk_source FROM llx_element_element as e WHERE e.sourcetype="propal")';
$sqlP.= ' AND p.rowid not in (SELECT e.fk_target FROM llx_element_element as e WHERE e.targettype="propal")';
$sqlP.= ' GROUP BY p.rowid';
$resqlP = $db->query($sqlP);
if($resqlP){
    while($objP = $db->fetch_object($resqlP)){
        $propal->fetch($objP->rowid);
        $i = 0;
        $valid = false;
        if($objP->fk_statut > 0){
            $valid = true;
            $propal->setDraft($user);
        }
        foreach($propal->lines as $key => $line){
            if($line->tva_tx == 0){
                $result = $propal->updateline(
                    $line->id, 
                    $line->subprice, 
                    $line->qty, 
                    $line->remise_percent, 
                    16, 
                    $line->localtax1_tx, 
                    $line->localtax2_tx, 
                    $line->desc, 
                    'HT', 
                    $line->info_bits, 
                    $line->special_code, 
                    $line->fk_parent_line, 
                    $line->skip_update_total, 
                    $line->fk_fournprice, 
                    $line->pa_ht, 
                    $line->label, 
                    $line->product_type, 
                    $line->date_start, 
                    $line->date_end, 
                    $line->array_options, 
                    $line->fk_unit, 
                    $line->multicurrency_subprice, 
                    0, 
                    $Line->unit
                );
                if ($result >= 0)
                {
                    $i ++;
                }
            }
        }
        if($valid){
            $propal->valid($user);
        }
        print 'Se le añadieron IVA a '.$i.' productos de la cotizacion '.$objP->ref.'<br>';
    }
}
?>