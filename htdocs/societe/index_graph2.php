<?php

/**
 * @author lolkittens
 * @copyright 2020
 */




$sql= "select s.rowid,s.entity,s.status,s.fournisseur,s.client,s.nom, s.code_fournisseur, s.siren, "
	 ."sum(f.total_ht) as subtotal, sum(f.total_tva) as importe_iva ,sum(f.total_ttc) as total /*,  "
	 ."sum(pa.amount) as pago */ "
	 ."from llx_facture_fourn as f  "
	 ."join llx_societe as s  "
	 ."on f.fk_soc=s.rowid  "
	 ."/* left join llx_paiementfourn_facturefourn as pa  "
	 ."on f.rowid = pa.fk_paiementfourn */ "
	 ."where f.entity";
//$sql .= (!is_null($rc_status)) ? ' AND f.fk_statut =  '.$rc_status:'';

    $sql.=" AND f.datef BETWEEN DATE( DATE_SUB( NOW() , INTERVAL 30 DAY ) ) AND DATE(NOW())";

if ($search_nom)           $sql.= natural_search("nom", $search_nom);
if ($search_customer_code) $sql.= natural_search("code_fournisseur", $search_customer_code);
if (strlen($search_idprof1)) $sql.= natural_search("s.siren", $search_idprof1);
//$sql.= " AND s.client = 1";
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
	$dolgraph->draw('idgraphcxp');



?>
<div class="div-table-responsive-no-min">
	<table class="noborder nohover centpercent">
		<tr class="liste_titre"><th colspan="2">Proveedores con mayores CxP en los últimos 30 días</th></tr>
		<tr>
			<td class="center" colspan="2">
				<?php echo $dolgraph->show(); ?>
			</td>
		</tr>
	</table>
</div>