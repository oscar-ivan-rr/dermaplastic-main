<?php
require('../main.inc.php');
global $conf;
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
$facid=GETPOST('id');
if(GETPOST('action')=="addish"){
	$idproddet=GETPOST('idprodet');
	$porcentajeish=GETPOST('porcentajeish');
	$sql="SELECT a.rowid,fk_product,description,total_ht
		FROM ".MAIN_DB_PREFIX."facturedet a
		WHERE a.fk_facture=".GETPOST('id')." AND rowid=".$idproddet;
	$ab=$db->query($sql);
	$abb=$db->fetch_object($ab);
	$ish=($porcentajeish/100)*$abb->total_ht;
	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facturedet_ish (fk_facture,fk_prodfacdet,porcentaje,importe)
			VALUES(".$facid.",".$idproddet.",".$porcentajeish.",".$ish.")";
	//print $sql;
	$ab=$db->query($sql);
	header('Location: formish.php?id='.$facid);
}
if(GETPOST('action')=="elimish"){
	$idproddet=GETPOST('idprodet');
	$sql="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_facturedet_ish WHERE fk_facture=".$facid." AND fk_prodfacdet=".$idproddet;
	$ab=$db->query($sql);
	header('Location: formish.php?id='.$facid);
}
$sql="SELECT a.rowid,a.fk_product,a.description,a.tva_tx,a.subprice,a.total_ht,a.total_tva,a.total_ttc
		,b.ref,b.label
		FROM ".MAIN_DB_PREFIX."facturedet a LEFT JOIN ".MAIN_DB_PREFIX."product b ON a.fk_product=b.rowid
		WHERE a.fk_facture=".GETPOST('id');
//print "".$sql;
print "<br>";
print "<table width='100%' class='noborder'>
		<tr class='liste_titre'><td>Descricion</td><td>Importe sin IVA</td><td>Porcentaje</td><td>&nbsp;</td><td>ISH</td></tr>";
$ar=$db->query($sql);
$j=1;
$totish=0;
while($arr=$db->fetch_object($ar)){
	if($j==1){
		$s=" class='pair' ";
		$j=2;
	}else{
		$s=" class='impair' ";
		$j=1;
	}
	print "<tr ".$s.">";
		$sql="SELECT IFNULL(porcentaje,NULL) as porcentaje, importe 
			FROM ".MAIN_DB_PREFIX."cfdimx_facturedet_ish 
			WHERE fk_facture=".$facid." AND fk_prodfacdet=".$arr->rowid;
		//print $sql;
		$ab=$db->query($sql);
		$abb=$db->fetch_object($ab);
		if($arr->ref==NULL){
			print "<td>".$arr->description."</td>";
		}else{
			print "<td>".$arr->ref."-".$arr->label."</td>";
		}
		print "<td>".$arr->total_ht."</td>";
		if($abb->porcentaje==NULL){
			print "<form action='formish.php?id=".$facid."&idprodet=".$arr->rowid."&action=addish' method='POST'>";
			print "<td><input type='text' value='3' id='porcentajeish' name='porcentajeish' size='2'>%</td>";
			print "<td><input type='submit' value='Agregar ISH' id='addish' name='addish'></td>";
			print "<td>&nbsp;</td>";
			print "</form>";
		}else{
			print "<form action='formish.php?id=".$facid."&idprodet=".$arr->rowid."&action=elimish' method='POST'>";
			print "<td><input type='text' value='".$abb->porcentaje."' id='porcentajeish' name='porcentajeish' size='2'>%</td>";
			print "<td><input type='submit' value='Eliminar ISH' id='elimish' name='elimish'></td>";
			print "<td>".number_format($abb->importe,2)."</td>";
			print "</form>";
			$totish=$totish+$abb->importe;
		}
	print "</tr>";
}
print "<tr><td colspan='4' align='right'><strong>Total ISH:</strong></td><td>".number_format($totish,2)."</td></tr>";
print "</table>";
print '<br><div align="right"><a href="javascript:window.opener.document.location.reload();self.close()"> 
		<strong>Cerrar</strong </a></div>';
?>