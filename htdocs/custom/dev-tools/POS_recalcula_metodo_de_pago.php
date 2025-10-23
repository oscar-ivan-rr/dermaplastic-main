<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../main.inc.php';
llxHeader("", 'Recalcular Método de Pago de Tickets Según sus Pagos Reales', '');

$sql =   'SELECT t.rowid, p.fk_paiement,t.fk_mode_reglement,p.amount '."\r\n"
        .'FROM llx_pos_paiement_ticket AS ppt '."\r\n"
        .'LEFT JOIN llx_paiement AS p '."\r\n"
        .'  ON p.rowid = ppt.fk_paiement '."\r\n"
        .'LEFT JOIN llx_pos_ticket AS t '."\r\n"
        .'  ON t.rowid = ppt.fk_ticket '."\r\n"
        .'ORDER BY t.rowid, p.amount DESC '."\r\n"
        .''
        ;
$res = $db->query($sql);

$last_row = -1;
$ct = 0;
$cm = 0;
while ($row= $db->fetch_object($res))
{
    if ($last_row == $row->rowid )
    {
        continue;
    }
    $ct++;
    $last_row = $row->rowid;
    if ($row->fk_paiement == $row->fk_mode_reglement)
    {
        continue;
    }
    $cm ++;
    $sql2 =  'UPDATE llx_pos_ticket  '."\r\n"
            .'SET fk_mode_reglement = '.$row->fk_paiement.'  '."\r\n"
            .'WHERE rowid = ' .$row->rowid .' '."\r\n"
            .''
            ;
    $db->query($sql2);
}

echo "Se actualizaron {$cm} tickets de un total de {$ct}";