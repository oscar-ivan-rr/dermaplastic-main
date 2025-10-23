<?php

/**
 * @author lolkittens
 * @copyright 2020
 */


$sql= "select s.rowid,s.entity,s.status,s.fournisseur,s.client, s.nom, s.code_client,s.siren, sum(f.total) as subtotal, "
	 ."sum(f.tva) as importe_iva ,sum(f.total_ttc) as total /*, sum(pa.amount) as pago*/ "
	 ."from llx_facture as f join llx_societe as s on f.fk_soc=s.rowid "
	 ."/* left join llx_paiement_facture as pa "
	 ."on f.rowid = pa.fk_facture */ "
	 ."where f.entity ";
	 
//$sql .= (!is_null($rc_status)) ? ' AND f.fk_statut =  '.$rc_status:'';

    //$sql.=" AND f.datef BETWEEN DATE( DATE_SUB( NOW() , INTERVAL 30 DAY ) ) AND DATE(NOW())";


$sql.= " group by f.fk_soc";
    $sql.= " ORDER BY total DESC";
    
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die();
}

$cnt = 0;
while($row = $db->fetch_object($res))
{
	if ($cnt < 5)
	{
		if (strlen($row->nom) > 30)
		{
			$row->nom = substr($row->nom,0,29).'&#8230;';
		}
		$rows[$cnt] = array($row->nom,$row->total);
	}
	else
	{
		if (!isset($rows[5]))
		{
			$rows[5] = array('Otros',$row->total);			
		}
		else
		{
			$rows[5][1] = $rows[5][1] + $row->total;
		}
	}
	$cnt++;
}



    include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
    $dolgraph = new DolGraph();
	$dolgraph->SetData($rows);
	$dolgraph->setShowLegend(1);
	$dolgraph->setShowPercent(1);
	$dolgraph->SetType(array('pie'));
	$dolgraph->setWidth('100%');
	$dolgraph->draw('idgraphcxcalltime');



?>
<div class="div-table-responsive-no-min">
	<table class="noborder nohover centpercent">
		<tr class="liste_titre"><th colspan="2">Clientes con mayores CxC en Total en el sistema</th></tr>
		<tr>
			<td class="center" colspan="2">
				<?php echo $dolgraph->show(); ?>
			</td>
		</tr>
	</table>
</div>