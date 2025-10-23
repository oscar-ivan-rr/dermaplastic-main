<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../main.inc.php';
llxHeader("", 'Recalcular Estado de Pagado de Tickets Según sus Pagos Reales', '');


$sql	=	 'UPDATE llx_pos_ticket AS t '."\r\n"
			.'LEFT JOIN llx_facture AS f '."\r\n"
			.'on t.fk_facture = f.rowid '."\r\n"
			.'SET t.fk_facture = NULL '."\r\n"
			.'WHERE t.fk_facture IS NOT NULL '."\r\n"
			.'  AND f.rowid IS NULL '."\r\n"
			.'   OR f.fk_statut IN (3) '."\r\n"
			.''
			;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die(__LINE__.' Error');
}
echo '<p>Se econtraron '.$db->affected_rows($res).' tickets con factura borrada.</p>';



$sql	=	 'SELECT pt.rowid '."\r\n"
			.'FROM llx_pos_paiement_ticket AS pt '."\r\n"
			.'LEFT JOIN llx_paiement AS p '."\r\n"
			.'  ON pt.fk_paiement = p.rowid '."\r\n"
			.'WHERE p.rowid IS NULL'."\r\n"
			;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die(__LINE__.' Error');
}
$idx	=	array();
while ($row = $db->fetch_object($res))
{
	$idx[]	= $row->rowid;
}

if (count($idx))
{
	$sql	=	 'DELETE FROM llx_pos_paiement_ticket WHERE rowid IN ('.implode(',',$idx).')';
	if (!$db->query($sql))
	{
		dol_print_error($db);
		die(__LINE__.' Error');
	}
	echo '<p>Se encontraron '.(count($idx)).' pagos borrados.</p>';
}
else
{
	echo '<p>No se encontraron pagos borrados.</p>';
}


$sql =	 'UPDATE llx_pos_ticket '."\r\n"
		.'SET '."\r\n"
		.'paye = 0'."\r\n"
		.',difpayment = total_ttc '."\r\n"
		.',customer_pay = 0 '."\r\n"
		;
if (!$res2 = $db->query($sql))
{
	dol_print_error($db);
	die(__LINE__.' Error');
}
echo '<p>Se reinició es estatus de '.$db->affected_rows($res).' tickets.</p>';





$sql	=	 'SELECT t.ticketnumber '."\r\n"
			.'		,t.fk_statut '."\r\n"
			.'		,t.type '."\r\n"
			.'		,p.ref '."\r\n"
			.'		,p.rowid as pid '."\r\n"
			.'		,t.rowid '."\r\n"
			.'		,t.paye '."\r\n"
			.'		,t.total_ttc '."\r\n"
			.'		,t.difpayment '."\r\n"
			.'		,t.discount_applied '."\r\n"
			.'		,pt.amount AS pamount '."\r\n"
			.'FROM llx_pos_ticket AS t '."\r\n"
			.'LEFT JOIN llx_pos_paiement_ticket AS pt '."\r\n"
			.'  ON t.rowid = pt.fk_ticket '."\r\n"
			.'LEFT JOIN llx_paiement AS p '."\r\n"
			.'  ON p.rowid = pt.fk_paiement '."\r\n"
			.' WHERE t.fk_statut <>0 AND t.fk_statut IS NOT NULL '."\r\n"
			//.'GROUP BY t.ticketnumber '."\r\n"
			.'ORDER BY t.ticketnumber ASC, p.rowid ASC'
			;
			
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die(__LINE__.' Error');
}


$saldos = array();
$lastbad = null;
$i = 0;
?>
<script src="js/jquery.fixedTableHeader.js"></script>
<table class="example noborder" style="width: 100%;">
	<tr>
		<th style="text-align: right;">#</th>
		<th>Tiket</th>
		<th>Tipo</th>
		<th>Status</th>
		<th>Pagado</th>
		<th style="text-align: right;">Importe</th>
		<th>Pago</th>
		<th style="text-align: right;">Pago $</th>
		<th style="text-align: right;">Saldo</th>
		<th style="text-align: right;">Saldo DB</th>
		<th>N.St.Pagado</th>
		<th style="text-align: right;">Nuevo Saldo</th>
	</tr>
	<?php while ($row = $db->fetch_object($res)): ?>
	<?php	
			if (!isset($saldos[$row->rowid]))
			{
				$saldos[$row->rowid]= $row->total_ttc;
			}
			if (is_numeric($row->pamount) && $row->pamount <> 0 )
			{
				$factor = ($row->type == 0)? 1: -1;
				$saldos[$row->rowid] -= $row->pamount*$factor;
			}
			$rclass = ''; 
			$lastbad = $row->rowid;
			$i++;
	?>
	<tr<?php echo ($row->type == '1')? ' style="background-color:#fbb;'.$rclass.'"':' style="'.$rclass.'"'; ?>>
		<td style="text-align: right;"><?php echo $i; ?></td>
		<td><a target="_blank" href="<?php echo DOL_URL_ROOT;?>/custom/pos/backend/ticket.php?id=<?php echo $row->rowid; ?>"><?php echo $row->ticketnumber ?></a></td>
		<td><?php switch ($row->type){case 0: echo 'Venta';break;case 1: echo 'Devolución';break;default: echo '<span stye="color:#900;">No Identificado</span>';break;} ?></td>
		<td><?php switch ($row->fk_statut){case 0: echo 'Borrador';break;case 1: echo 'Cerrado';break;case 2: echo 'Procesado';break;case 3: echo 'Cancelado';break;default: echo '<span stye="color:#900;">No Identificado</span>'.var_dump($row->fk_statut);break;} ?></td>
		<td><?php switch ($row->paye){case 0: echo 'NO';break;case 1: echo 'SI';break;default: echo '<span stye="color:#900;">No Identificado</span>';break;} ?></td>
		<td style="text-align: right;"><?php echo number_format($row->total_ttc,4,'.',','); ?></td>
		<td><a target="_blank" href="<?php echo DOL_URL_ROOT;?>/compta/paiement/card.php?id=<?php echo $row->pid;?>"><?php echo $row->ref;?></a></td>
		<td style="text-align: right;"><?php echo number_format($row->pamount,4,'.',','); ?></td>
		<td style="text-align: right;"><?php echo number_format($saldos[$row->rowid],4,'.',','); ?></td>
		<td style="text-align: right;"><?php echo number_format($row->difpayment,4,'.',','); ?></td>
		<td><?php echo ((abs($saldos[$row->rowid]) <=0.019)?'Si':'NO');  ?></td>
		<td style="text-align: right;"><?php echo number_format(((abs($saldos[$row->rowid]) <=0.019)?'0':$saldos[$row->rowid]),4,'.',','); ?></td>
		<td><?php ?></td>
	</tr>
	<?php
		$sql =	 'UPDATE llx_pos_ticket '."\r\n"
				.'SET '."\r\n"
				.'paye = '.((abs($saldos[$row->rowid]) <=0.109)?'1':'0')."\r\n"
				.',difpayment = '.((abs($saldos[$row->rowid]) <=0.109)?'0':$saldos[$row->rowid])."\r\n"
				.',customer_pay = customer_pay + '.($row->pamount?$row->pamount:'0')."\r\n"
				.'WHERE rowid = '.$row->rowid."\r\n"
				;
		//echo $sql;
		//if (false)
		if (!$res1 = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}
	?>
	<?php endwhile; ?>
</table>

    <script>
        jQuery(($) => {
            $(".example").fixedTableHeader();
        })
    </script>
