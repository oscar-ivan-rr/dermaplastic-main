<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../main.inc.php';
llxHeader("", 'Recalcular Estado de Pagado de Tickets Según sus Pagos Reales', '');

# Limpieza de tabla que relaciona pagos con tickets
$sql	=	 'SELECT t.rowid, t.ticketnumber, t.total_ttc,t.paye,t.difpayment, SUM(pt.amount) AS payments,p.statut '."\r\n"
			.'FROM llx_pos_ticket AS t '."\r\n"
			.'LEFT JOIN llx_pos_paiement_ticket AS pt '."\r\n"
			.'  ON t.rowid = pt.fk_ticket '."\r\n"
			.'LEFT JOIN llx_paiement AS p '."\r\n"
			.'  ON p.rowid = pt.fk_paiement '."\r\n"
			.'WHERE (t.paye = 0 '."\r\n"
			.'   OR t.difpayment > 0) '."\r\n"
			.'	 AND p.rowid IS NOT NULL '."\r\n"
			.'GROUP BY t.rowid '."\r\n"
			.'HAVING (total_ttc - payments) <= 0.01 '."\r\n"
			.''."\r\n"
			;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die(__LINE__.' Error');
}
echo "<h3>Se encontraron ".$db->num_rows($res)." registros</h3>";
echo '<table>';
echo '<tr>';
echo '<th>Ticket</th>';
echo '<th style="text-align:right;">Importe</th>';
echo '<th style="text-align:right;">Pagado</th>';
echo '<th style="text-align:right;">Por Pagar (DB)</th>';
echo '<th style="text-align:right;">Status (DB)</th>';
echo '<th style="text-align:right;">Nuevo Pagar</th>';
echo '<th style="text-align:right;">Nuevo Status</th>';
echo '</tr>';
while($fixRow = $db->fetch_object($res))
{
	$stillToPay = $fixRow->total_ttc - $fixRow->payments;
	if ($stillToPay >= -0.011 && $stillToPay <= 0.011)
	{
		$stillToPay = 0;
	}
	//die (var_dump($stillToPay,($stillToPay >= -0.011 && $stillToPay <= 0.011)));
	$fix2Sql =	 "UPDATE llx_pos_ticket "."\r\n"
				."SET paye = 1 "."\r\n"
				."	 ,difpayment = ".($stillToPay)." "."\r\n"
				."WHERE rowid = ".$fixRow->rowid." "."\r\n"
				.""
				;
	if (!$db->query($fix2Sql))
	{
		dol_print_error($db);
		die(__LINE__.' Error');
	}
	echo '<tr>';
	echo '<td>'.$fixRow->ticketnumber.'</td>';
	echo '<td style="text-align:right;">'.$fixRow->total_ttc.'</td>';
	echo '<td style="text-align:right;">'.$fixRow->payments.'</td>';	
	echo '<td style="text-align:right;">'.$fixRow->difpayment.'</td>';
	echo '<td style="text-align:right;">'.$fixRow->paye.'</td>';
	echo '<td style="text-align:right;">'.$stillToPay.'</td>';
	echo '<td style="text-align:right;">1</td>';
}
echo '</table>';