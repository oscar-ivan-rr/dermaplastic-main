<?php
//require('../../../main.inc.php');

include("tools.php");

llxHeader('','','','','','','Ingresos','',0,0);
//session_start();
global $db,$conf;
$nombre_empresa = getDataConst( "MAIN_INFO_SOCIETE_NOM", $tbl_prefix );


if($_REQUEST["fini"] ){
	$fecha_actuali=$_REQUEST["fini"];
}else{
	$fecha_actuali=date("Y-m-d");
}
if($_REQUEST["ffin"] ){
	$fecha_actualf=$_REQUEST["ffin"];
}else{
	$fecha_actualf=date("Y-m-d");
}

if( $_REQUEST["bank"]){
	$banco = $_REQUEST["bank"];
}
if( $_REQUEST["forma_pago"]){
	$fpago = $_REQUEST["forma_pago"];
}
$mpagos='Todos';
if($_REQUEST["mpagos"]){
	$mpagos=$_REQUEST["mpagos"];
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Documento sin título</title>
<style type="text/css">
table, th, td {
   font-size:12px;
}
</style>

</head>

<body>
<p>
<div align="center">
	<h3><strong>Pagos Ingresos</strong></h3>
</div>
</p>
<form method="post" action="">
Fecha Inicio
<input type='date' name='fini' value=<?=$fecha_actuali?> >
Fecha Fin
<input type='date' name='ffin' value=<?=$fecha_actualf?> >

Banco:
<select name="bank" >
   <option value="Todos" <?=getSelected("Todos", $banco)?>>Todos</option>
   <?php 
   $sql1="SELECT label FROM ".MAIN_DB_PREFIX."bank_account WHERE entity=".$conf->entity;
   $qry2 = $db->query($sql1);
   while($rs = $db->fetch_array($qry2) ){
   ?>
   <option value="<?=$rs["label"]?>" <?=getSelected($rs["label"], $banco)?>><?=$rs["label"]?></option>
   <?php
   }
   ?>
</select>
Forma de Pago:
<select name="forma_pago" >
   <option value="Todos" <?=getSelected("Todos", $fpago)?>>Todos</option>
   <?php 
   $sql2="SELECT libelle FROM ".MAIN_DB_PREFIX."c_paiement";
   $qry3 = $db->query($sql2);
   while($rs = $db->fetch_array($qry3) ){
   ?>
   <option value="<?=utf8_encode($rs["libelle"])?>" <?=getSelected(utf8_encode($rs["libelle"]), $fpago)?>><?=formaPago($rs["libelle"])?></option>
   <?php
   }
   ?>
</select>

Mostrar Pagos:
<select name="mpagos" >
	<option value="Todos" <?=getSelected('Todos', $mpagos)?>>Todos</option>
	<option value="1" <?=getSelected('1', $mpagos)?>>Con Factura</option>
	<option value="2" <?=getSelected('2', $mpagos)?>>Sin Factura</option>
</select>
<input type="submit" value='Consultar'/>
</form>


<?php
if( 1){

$extra_sql="";
$estra_union="";
if( !$_REQUEST["tipo_factura"] || $_REQUEST["tipo_factura"]==0 ){ 
	//$extra_sql.=" AND f.type = 0";
	//$extra_sql.=" AND f.rowid NOT IN (SELECT fk_facture_source FROM ".$tbl_prefix."facture WHERE type = 2)";
}
if($banco=="Todos"){
}else{
	$extra_sql.=" AND ba.label='".$banco."'";
	$estra_union.=' AND b.label="'.$banco.'"';
}
if($fpago=="Todos"){
}else{
	$extra_sql.=" AND cp.libelle='".utf8_decode($fpago)."'";
	$estra_union.=" AND c.libelle='".utf8_decode($fpago)."'";
}

$sql = "
SELECT
	cp.libelle forma_pago,
	ba.label banco, 
	f.ref,
	s.nom tercero,
	p.datep fecha_pago,
	p.amount importe,
	ba.currency_code,
	p.rowid as refpag,
	f.rowid as idfac
FROM 
	".$tbl_prefix."paiement p, 
	".$tbl_prefix."paiement_facture pf,
	".$tbl_prefix."facture f, 
	".$tbl_prefix."societe s,
	".$tbl_prefix."bank b,
	".$tbl_prefix."bank_account ba,
	".$tbl_prefix."c_paiement cp
WHERE p.rowid = pf.fk_paiement
AND f.rowid = pf.fk_facture
AND b.rowid = p.fk_bank
AND b.fk_account = ba.rowid
AND cp.id = p.fk_paiement
AND (p.datep BETWEEN '".$fecha_actuali."' AND '".$fecha_actualf."' )
AND f.fk_soc = s.rowid
AND f.fk_statut = 2
AND f.entity=".$conf->entity." 
" . $extra_sql;
//echo $sql;
if($mpagos=='Todos'){
	$sql.="UNION
SELECT c.libelle as forma_pago, b.label banco, ' Caja chica ' ref, ' - ' as tercero,
a.datev as fecha_pago, ABS(a.amount) as importe,b.currency_code,NULL  as refpag, NULL as idfac
FROM ".MAIN_DB_PREFIX."bank a, ".MAIN_DB_PREFIX."bank_account b,".MAIN_DB_PREFIX."c_paiement c
WHERE a.fk_account=b.rowid AND a.fk_type=c.code AND (a.datev BETWEEN '".$fecha_actuali."' AND '".$fecha_actualf."' ) AND a.amount>=0
	  AND a.rowid NOT IN (SELECT fk_bank FROM ".MAIN_DB_PREFIX."paiement) AND b.entity=".$conf->entity." ".$estra_union;
}
if($mpagos=='2'){
	$sql="
SELECT c.libelle as forma_pago, b.label banco, ' Caja chica ' ref, ' - ' as tercero,
a.datev as fecha_pago, ABS(a.amount) as importe,b.currency_code,NULL  as refpag, NULL as idfac
FROM ".MAIN_DB_PREFIX."bank a, ".MAIN_DB_PREFIX."bank_account b,".MAIN_DB_PREFIX."c_paiement c
WHERE a.fk_account=b.rowid AND a.fk_type=c.code AND (a.datev BETWEEN '".$fecha_actuali."' AND '".$fecha_actualf."' ) AND a.amount>=0
	  AND a.rowid NOT IN (SELECT fk_bank FROM ".MAIN_DB_PREFIX."paiement) AND b.entity=".$conf->entity." ".$estra_union;
}
$sql.=" ORDER BY fecha_pago DESC";
$qry = $db->query($sql);
//echo $sql;
?>
<table style="width:100%" class="noborder">
<tr class="liste_titre">
	<th>No. Factura</th>
    <th>Tercero</th>
    <th>Fecha Pago</th>
    <th>Banco</th>
    <th>Ref. Pago</th>
    <th>Divisa</th>
	<th>Forma de Pago</th>
    <th>Importe</th>
</tr>
<?php
$table='
<table>
<tr>
	<th>No. Factura</th>
    <th>Tercero</th>
    <th>Fecha Pago</th>
    <th>Banco</th>
	<th>Ref. Pago</th>
	<th>Divisa</th>
	<th>Forma de Pago</th>
    <th>Importe</th>
</tr>
';
$counter=0;
$mm=1;

$n=1;
while( $rs = $db->fetch_array( $qry ) ){

$modulo = $counter%2;
if($modulo==0){ $rowcolor="#FFFFCC"; }else{ $rowcolor="#FFFFFF"; }

$facnumber=$rs["facnumber"];
$split_fpago = $rs["fecha_pago"];
$importe = $rs["importe"];
$tercero = $rs["tercero"];
$fecha_pago = explode(" ", $split_fpago);
$suma_importe = $suma_importe + $importe;
if($n==1){
	$nn="pair";
	$n=2;
}else{
	$n=1;
	$nn="impair";
}
?>
<tr class="<?=$nn?>">
    <? if($rs["idfac"]==NULL){?>
	<td ><?=$facnumber?></td>
	<? }else{?>
	<td ><a href="<?=DOL_MAIN_URL_ROOT.'/compta/facture.php?facid='.$rs['idfac']?>"><?=$facnumber?></a></td>
    <? }?>
    <td ><?=substr(strtoupper(utf8_encode($tercero)),0,60)?>&nbsp;</td>
    <td ><?=$fecha_pago[0]?></td>
    <td><?=strtoupper(utf8_encode($rs["banco"]))?></td>
    <? if($rs["refpag"]!=NULL){?>
    <td><a href="<?=DOL_MAIN_URL_ROOT.'/compta/paiement/card.php?id='.$rs["refpag"]?>"><?=$rs["refpag"]?></a></td>
    <? }else{?>
    <td> - </td>
    <? } ?>
    <td><?=$rs["currency_code"]?></td>
	<td><?=strtoupper(formaPago($rs["forma_pago"]))?></td>
	
    <td >$<?=number_format($importe,2)?></td>
</tr>
<?php
$table.='
<tr>
	<td>'.$facnumber.'</td>
    <td>'.substr(strtoupper(utf8_encode($tercero)),0,60).'&nbsp;</td>
    <td>'.$fecha_pago[0].'</td>
    <td>'.strtoupper(utf8_encode($rs["banco"])).'</td>
    <td>'.$rs["refpag"].'</td>
    <td>'.$rs["currency_code"].'</td>
	<td>'.strtoupper(formaPago($rs["forma_pago"])).'</td>
    <td>$'.number_format($importe,2).'</td>
</tr>
';
$counter++;
$mm++;
}
$table.='
<tr>
	<th colspan="6"><strong>Totales:</strong></th>
	<td><strong>$'.number_format($suma_importe,2).'</strong></td>
</tr>
</table>
';
?>
<tr class="liste_titre">
	<th colspan="7" align="right"><strong>Totales:</strong></th>
	<td ><strong>$<?=number_format($suma_importe,2)?></strong></td>
</tr>
</table>
<br>
<?php
}

$_SESSION['tabla']=$table;
$_SESSION['doc']='ingresos';
echo '<form action="imprime.php" method="GET">';
echo '<input type="submit" value="Imprimir Excel">';
echo '</form>';
?>

</body>
</html>
<?php 
llxFooter();
$db->close();
?>