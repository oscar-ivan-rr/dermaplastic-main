<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../main.inc.php';
llxHeader("", 'Tickets complementarios - Verificación de pagos', '');

# Limpieza de tabla que relaciona pagos con tickets
$sql	=	 'SELECT ABS(p.amount) as pamount, count(*) as records '."\r\n"
			.'from llx_paiement as p '."\r\n"
			.'LEFT JOIN llx_pos_paiement_ticket as pt '."\r\n"
			.'ON p.rowid = pt.fk_paiement '."\r\n"
			.'WHERE pt.rowid IS NOT NULL  '."\r\n"
			.'  AND p.note =\'Ticket Complementario\''."\r\n"
			.'GROUP BY pamount '."\r\n"
			//.'HAVING records > 1'."\r\n"
			.''."\r\n"
			;
			//die ($sql);
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die(__LINE__.' Error');
}
$toFixRows = array();
while ($row = $db->fetch_object($res))
{
	$sql2	=	 'SELECT p.rowid, p.amount, t.rowid as ticketid, t.ticketnumber,t.total_ttc  '."\r\n"
				.'from llx_paiement as p  '."\r\n"
				.'LEFT JOIN llx_pos_paiement_ticket AS pt  '."\r\n"
				.'  ON pt.fk_paiement = p.rowid  '."\r\n"
				.'LEFT JOIN llx_pos_ticket as t  '."\r\n"
				.'  on pt.fk_ticket = t.rowid  '."\r\n"
				.'WHERE p.amount = '.$row->pamount.' '."\r\n"
				.'   OR p.amount = -'.$row->pamount.' '."\r\n"
				;
	if (!$res2 = $db->query($sql2))
	{
		dol_print_error($db);
		die(__LINE__.' Error');
	}
	while ($row2 = $db->fetch_object($res2))
	{
		$toFixRows[]= $row2;
	}
}
echo "<h3>Se encontraron ".$db->num_rows($res)." registros</h3>";
echo '<table>';
echo '<tr>';
echo '<th>Ticket</th>';
echo '<th style="text-align:right;">Importe</th>';
echo '<th style="text-align:right;">Pagado</th>';
//echo '<th style="text-align:right;">Complementario</th>';
echo '</tr>';

foreach ($toFixRows as $fixRow)
{
	echo '<tr>';
	echo '<td>'
		.'<a target="_blank" href="'.DOL_URL_ROOT.'/custom/pos/backend/ticket.php?id='.$fixRow->ticketid.'">'
		.$fixRow->ticketnumber
		.'</a>'
		.'</td>';
	echo '<td style="text-align:right;">'.$fixRow->total_ttc.'</td>';
	echo '<td style="text-align:right;">'.$fixRow->amount.'</td>';	
}
echo '</table>';