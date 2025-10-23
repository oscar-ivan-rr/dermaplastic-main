<?php
//require('../../../main.inc.php');
//session_start();
global $db,$conf;
include_once ("tools.php");
if( 1 ){

$fini = $_REQUEST["fini"];
$ffin = $_REQUEST["ffin"];

$extra_sql="";
if( $_REQUEST["tf"] >= 0 && $_REQUEST["tf"] <> "" ){
	$tipo_factura = $_REQUEST["tf"];
	$extra_sql.=" AND f.type = " . $tipo_factura;
}
if( $_REQUEST["td"] ){
	$tipo_divisa = $_REQUEST["td"];
}
$status = "2";
if ( $_REQUEST["st"] >= 0 && $_REQUEST["st"] <> "") {
	$status = $_REQUEST["st"];
	//$extra_sql.=" AND f.rowid NOT IN (SELECT fk_facture_source FROM ".$tbl_prefix."facture WHERE type = ".$status.")";
	$extra_sql.=" AND f.fk_statut='".$status."'";
}
if( $_REQUEST["tf"]=="*" ){

}
$fiscales="3";
if ($_REQUEST["fs"]){
	$fiscales=$_REQUEST["fs"];
}

$sql = "
SELECT 
	f.rowid id_factura,
	f.ref,
	f.datef,
	f.date_valid,
	f.total subtotal,
	s.nom tercero,
	f.tva iva,
	f.total_ttc total
FROM ".$tbl_prefix."facture f, ".$tbl_prefix."societe s
WHERE (f.datef BETWEEN '".$fini."' AND '".$ffin."' ) AND f.fk_soc = s.rowid AND f.entity=".$conf->entity."  ".$extra_sql."
ORDER BY f.datef DESC";
//echo $sql."<br>";
$qry = $db->query( $sql );
?>
<table style="width:100%">
<tr><td style="width:50%">
<table align="center" style="width:100%" >
<!-- <tr>
    <td align="center" colspan="2"><strong><?=strtoupper(getMesNombre($mes))?></strong></td>
</tr> -->
<tr class="liste_titre">
    <td align="center" colspan="4"><strong>Status Factura</strong></td>
</tr>
<tr class="pair">
	<td style="width:25%"><strong>Borrador:</strong><?=getRecordCount($tbl_prefix, "st", 0, $fini, $ffin); ?></td>
    <td style="width:25%"><strong>P. Pago:</strong><?=getRecordCount($tbl_prefix, "st", 1, $fini, $ffin); ?></td>
    <td style="width:25%"><strong>Pagadas:</strong><?=getRecordCount($tbl_prefix, "st", 2, $fini, $ffin); ?></td>
	<td style="width:25%"><strong>Canceladas:</strong><?=getRecordCount($tbl_prefix, "st", 3, $fini, $ffin); ?></td>
</tr>
</table>
</td><td style="width:50%">
<table align="center" style="width:100%" >
<!-- <tr>
    <td align="center" colspan="2"><strong><?=strtoupper(getMesNombre($mes))?></strong></td>
</tr> -->
<tr class="liste_titre">
    <td align="center" colspan="3"><strong>Tipo Factura</strong></td>
</tr>
<tr class="pair">
	<td style="width:33%" align="center"><strong>Est&aacute;ndar:</strong><?=getRecordCount($tbl_prefix, "tf", 0, $fini, $ffin); ?></td>
    <td style="width:33%" align="center"><strong>Rectificadas:</strong><?=getRecordCount($tbl_prefix, "tf", 1, $fini, $ffin); ?></td>
    <td style="width:33%" align="center"><strong>Notas de Cr&eacute;dito:</strong><?=getRecordCount($tbl_prefix, "tf", 2, $fini, $ffin) ?></td>
</tr>
</table>
</td></tr></table><br>
<?
$table='
<table>
<tr>
    <th>No. Factura</th>
	<th>Divisa</th>
    <th>Fecha Fact</th>
    <th>Fiscal</th>
    <th>Tercero</th>
    <th>Subtotal</th>
    <th>IVA</th>
    <th>Total</th>
    <th>Pagado</th>
</tr>
';
?>
<table style="width:100%" class="noborder">
<tr class="liste_titre">
	<th>No. Factura</th>
    <th>Divisa</th>
    <th>Fecha Fact</th>
    <th>Fiscal</th>
    <th>Tercero</th>
    <th>Subtotal</th>
    <th>IVA</th>
    <th>Total</th>
    <th>Pagado</th>
</tr>
<?php
$counter=0;
$mm=1;
$n=1;
while( $rs = $db->fetch_array( $qry ) ){
	$facnumber=$rs["ref"];
	$datef =$rs["datef"];
	$tercero = $rs["tercero"];
	$subtotal = $rs["subtotal"];
	$iva = $rs["iva"];
	$total = $rs["total"];
	$pagado = getTotalPagado( $rs["id_factura"], $tbl_prefix );
	
	
	
	$modulo = $counter%2;
	if($modulo==0){ $rowcolor="#FFFFCC"; }else{ $rowcolor="#FFFFFF"; }
	//if( $mod_cfdi>0 ){
	  $fiscal='';
		$vFiscal = getValidaFiscal($tbl_prefix, $rs["id_factura"]);
		if($vFiscal>0){
			$fiscal="Si";
		}else{
			$fiscal="No";
			/*
			if( $iva>0 && $vFiscal<1 ){
				$stAlertaFiscal='style="color:#F00; font-weight:bold"';
			}else{ $stAlertaFiscal='style=""'; }
			*/
		}
//}
		if($conf->global->MAIN_MODULE_MULTIDIVISA){
			$sql="SELECT IFNULL(divisa,NULL) as divisa
							FROM ".MAIN_DB_PREFIX."multidivisa_facture
							WHERE fk_object=".$rs["id_factura"];
			$rd=$db->query($sql);
			$rdd=$db->fetch_object($rd);
			if($rdd->divisa!=NULL){
				$divisa=$rdd->divisa;
			}else{
				$divisa=$conf->currency;
			}
		}else{
			if($conf->global->MAIN_MODULE_MULTICURRENCY){
				$sql="SELECT multicurrency_code AS divisa FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$rs["id_factura"];
				$ra=$db->query($sql);
				$rb=$db->fetch_object($ra);
				$divisa=$rb->divisa;
			}else{
				$tipo_divisa=0;
				$divisa=$conf->currency;
			}
		}
if($n==1){
	$nn="pair";
	$n=2;
}else{
	$n=1;
	$nn="impair";
}
if($tipo_divisa!=0){
$sql="SELECT rowid,code FROM ".MAIN_DB_PREFIX."multidivisa_divisas WHERE rowid=".$tipo_divisa;
$rr=$db->query($sql);
$rrd=$db->fetch_object($rr);
}
if($divisa==$rrd->code && $tipo_divisa!=0){
if($fiscales=="3"){
$table.='
 <tr>
	<td>'.$facnumber.'</td>
	<td>'.$divisa.'</td>
	<td>'.$datef.'</td>
    <td>'.$fiscal.'</td>
    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
    <td>$'.number_format($subtotal,2).'</td>
    <td>$'.number_format($iva,2).'</td>
    <td>$'.number_format($total,2).'</td>
    <td>$'.number_format($pagado,2).'</td>
</tr>
		';
?>
<tr class="<?=$nn?>">
	<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['id_factura']?>"><?=$facnumber?></a></td>
    <td ><?=$divisa?></td>
	<td ><?=$datef?></td>
    <td  <?=$stAlertaFiscal?>><?=$fiscal?></td>
    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
    <td >$<?=number_format($subtotal,2)?></td>
    <td  <?=$stAlertaFiscal?>>$<?=number_format($iva,2)?></td>
    <td >$<?=number_format($total,2)?></td>
    <td >$<?=number_format($pagado,2)?></td>
</tr>
<?php
$mm++;
$suma_subtotal = $suma_subtotal + $subtotal;
$suma_iva = $suma_iva + $iva;
$suma_total = $suma_total + $total;
$suma_pagado = $suma_pagado + $pagado;
}else{
	if($fiscales=='1' && $vFiscal>0){
		$table.='
		<tr>
		<td>'.$facnumber.'</td>
		<td>'.$divisa.'</td>
		<td>'.$datef.'</td>
	    <td>'.$fiscal.'</td>
	    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
	    <td>$'.number_format($subtotal,2).'</td>
	    <td>$'.number_format($iva,2).'</td>
	    <td>$'.number_format($total,2).'</td>
	    <td>$'.number_format($pagado,2).'</td>
		</tr>
		';
		?>
	<tr class="<?=$nn?>">
		<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['id_factura']?>"><?=$facnumber?></a></td>
	    <td ><?=$divisa?></td>
		<td ><?=$datef?></td>
	    <td  <?=$stAlertaFiscal?>><?=$fiscal?></td>
	    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
	    <td >$<?=number_format($subtotal,2)?></td>
	    <td  <?=$stAlertaFiscal?>>$<?=number_format($iva,2)?></td>
	    <td >$<?=number_format($total,2)?></td>
	    <td >$<?=number_format($pagado,2)?></td>
	</tr>
	<?php
	$mm++;
	$suma_subtotal = $suma_subtotal + $subtotal;
	$suma_iva = $suma_iva + $iva;
	$suma_total = $suma_total + $total;
	$suma_pagado = $suma_pagado + $pagado;
	}else{
		if($fiscales=='2' && $vFiscal==0){
			$table.='
			<tr">
				<td>'.$facnumber.'</td>
				<td>'.$divisa.'</td>
				<td>'.$datef.'</td>
			    <td>'.$fiscal.'</td>
			    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
			    <td>$'.number_format($subtotal,2).'</td>
			    <td>$'.number_format($iva,2).'</td>
			    <td>$'.number_format($total,2).'</td>
			    <td>$'.number_format($pagado,2).'</td>
			</tr>
			';
		?>
			<tr class="<?=$nn?>">
				<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['id_factura']?>"><?=$facnumber?></a></td>
			    <td ><?=$divisa?></td>
				<td ><?=$datef?></td>
			    <td  <?=$stAlertaFiscal?>><?=$fiscal?></td>
			    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
			    <td >$<?=number_format($subtotal,2)?></td>
			    <td  <?=$stAlertaFiscal?>>$<?=number_format($iva,2)?></td>
			    <td >$<?=number_format($total,2)?></td>
			    <td >$<?=number_format($pagado,2)?></td>
			</tr>
			<?php
			$mm++;
			$suma_subtotal = $suma_subtotal + $subtotal;
			$suma_iva = $suma_iva + $iva;
			$suma_total = $suma_total + $total;
			$suma_pagado = $suma_pagado + $pagado;
		}
	}
}
$counter++;
}
//print $tipo_divisa;
if($tipo_divisa==0){
	if($fiscales=="3"){
		$table.='
 <tr>
	<td>'.$facnumber.'</td>
	<td>'.$divisa.'</td>
	<td>'.$datef.'</td>
    <td>'.$fiscal.'</td>
    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
    <td>$'.number_format($subtotal,2).'</td>
    <td>$'.number_format($iva,2).'</td>
    <td>$'.number_format($total,2).'</td>
    <td>$'.number_format($pagado,2).'</td>
</tr>
		';
		?>
<tr class="<?=$nn?>">
	<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['id_factura']?>"><?=$facnumber?></a></td>
    <td ><?=$divisa?></td>
	<td ><?=$datef?></td>
    <td  <?=$stAlertaFiscal?>><?=$fiscal?></td>
    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
    <td >$<?=number_format($subtotal,2)?></td>
    <td  <?=$stAlertaFiscal?>>$<?=number_format($iva,2)?></td>
    <td >$<?=number_format($total,2)?></td>
    <td >$<?=number_format($pagado,2)?></td>
</tr>
<?php
$mm++;
$suma_subtotal = $suma_subtotal + $subtotal;
$suma_iva = $suma_iva + $iva;
$suma_total = $suma_total + $total;
$suma_pagado = $suma_pagado + $pagado;
}else{
	if($fiscales=='1' && $vFiscal>0){
		$table.='
		<tr>
		<td>'.$facnumber.'</td>
		<td>'.$divisa.'</td>
		<td>'.$datef.'</td>
	    <td>'.$fiscal.'</td>
	    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
	    <td>$'.number_format($subtotal,2).'</td>
	    <td>$'.number_format($iva,2).'</td>
	    <td>$'.number_format($total,2).'</td>
	    <td>$'.number_format($pagado,2).'</td>
		</tr>
		';
		?>
	<tr class="<?=$nn?>">
		<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['id_factura']?>"><?=$facnumber?></a></td>
	    <td ><?=$divisa?></td>
		<td ><?=$datef?></td>
	    <td  <?=$stAlertaFiscal?>><?=$fiscal?></td>
	    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
	    <td >$<?=number_format($subtotal,2)?></td>
	    <td  <?=$stAlertaFiscal?>>$<?=number_format($iva,2)?></td>
	    <td >$<?=number_format($total,2)?></td>
	    <td >$<?=number_format($pagado,2)?></td>
	</tr>
	<?php
	$mm++;
	$suma_subtotal = $suma_subtotal + $subtotal;
	$suma_iva = $suma_iva + $iva;
	$suma_total = $suma_total + $total;
	$suma_pagado = $suma_pagado + $pagado;
	}else{
		if($fiscales=='2' && $vFiscal==0){
			$table.='
			<tr">
				<td>'.$facnumber.'</td>
				<td>'.$divisa.'</td>
				<td>'.$datef.'</td>
			    <td>'.$fiscal.'</td>
			    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
			    <td>$'.number_format($subtotal,2).'</td>
			    <td>$'.number_format($iva,2).'</td>
			    <td>$'.number_format($total,2).'</td>
			    <td>$'.number_format($pagado,2).'</td>
			</tr>
			';
		?>
			<tr class="<?=$nn?>">
				<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['id_factura']?>"><?=$facnumber?></a></td>
			    <td ><?=$divisa?></td>
				<td ><?=$datef?></td>
			    <td  <?=$stAlertaFiscal?>><?=$fiscal?></td>
			    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
			    <td >$<?=number_format($subtotal,2)?></td>
			    <td  <?=$stAlertaFiscal?>>$<?=number_format($iva,2)?></td>
			    <td >$<?=number_format($total,2)?></td>
			    <td >$<?=number_format($pagado,2)?></td>
			</tr>
			<?php
			$mm++;
			$suma_subtotal = $suma_subtotal + $subtotal;
			$suma_iva = $suma_iva + $iva;
			$suma_total = $suma_total + $total;
			$suma_pagado = $suma_pagado + $pagado;
		}
	}
}
$counter++;
}
}
$table.='
<tr>
	<th colspan="5">Totales:</th>
	<th>$'.number_format($suma_subtotal,2).'</th>
	<th>$'.number_format($suma_iva,2).'</th>
	<th>$'.number_format($suma_total,2).'</th>
    <th>$'.number_format($suma_pagado,2).'</th>
</tr>
</table>		
';
?>
<tr class="liste_titre">
	<th colspan="5" align="right">Totales:</th>
	<th >$<?=number_format($suma_subtotal,2)?></th>
	<th >$<?=number_format($suma_iva,2)?></th>
	<th >$<?=number_format($suma_total,2)?></th>
    <th >$<?=number_format($suma_pagado,2)?></th>
</tr>
</table>


<div align="center" style="font-size:14px; padding:3px"><strong>Registros en Pantalla: <?=$mm-1?></strong></div>
<?php 
$_SESSION['tabla']=$table;
$_SESSION['doc']='facingresos';
echo '<form action="imprime.php" method="GET">';
echo '<input type="submit" value="Imprimir Excel">';
echo '</form>';
?>

<?php
}
?>