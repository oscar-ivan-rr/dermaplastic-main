<?php
//require('../../../main.inc.php');
global $db,$conf;
include_once ("tools.php");

llxHeader('','','','','','','Facturacion Egresos','',0,0);
$nombre_empresa = getDataConst( "MAIN_INFO_SOCIETE_NOM", $tbl_prefix );

$mod_cfdi = validaModCFDI($tbl_prefix);
$tp_fact_default = 0;
$status_default=2;

if($_REQUEST["fini"] ){
	$fecha_actuali=''.$_REQUEST["fini"].'';
}else{
	$fecha_actuali=''.date("Y-m-d").'';
}
if($_REQUEST["ffin"] ){
	$fecha_actualf=$_REQUEST["ffin"];
}else{
	$fecha_actualf=date("Y-m-d");
}

$tipo_factura = "0";
if( $_REQUEST["tipo_factura"] >= 0 && $_REQUEST["tipo_factura"] <> "" ){
	$tipo_factura = $_REQUEST["tipo_factura"];
}
$tipo_divisa='0';
if( $_REQUEST["tipo_divisa"]){
	$tipo_divisa = $_REQUEST["tipo_divisa"];
}

$status = "2";
//echo "Fuera del if = ".$_REQUEST["status"]."*";
if ( $_REQUEST["status"] >= 0 && $_REQUEST["status"] <> "") {
	//echo "Entro por el if = ".$_REQUEST["status"]."*";
	$status = $_REQUEST["status"];
}

$fiscales ="3";
if($_REQUEST["fiscales"]){
	$fiscales=$_REQUEST["fiscales"];
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Documento sin título</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<style type="text/css">
table, th, td {
   font-size:12px;
}
</style>

</head>

<body>
<p>
<div align="center">
	<h3><strong>Facturaci&oacute;n Egresos</strong></h3>
</div>
</p>
<form method="post">
Fecha Inicio
<input type='date' name='fini' id='fini' value=<?=$fecha_actuali?> >
Fecha Fin
<input type='date' name='ffin' id='ffin' value=<?=$fecha_actualf?> >
Tipo Factura:
<select name="tipo_factura" >
    <option value="0" <?=getSelected(0, $tipo_factura)?>>Factura Estándar</option>
    <option value="1" <?=getSelected(1, $tipo_factura)?>>Factura Rectificada</option>
    <option value="2" <?=getSelected(2, $tipo_factura)?>>Nota de Crédito</option>
</select>
Status:
<select name="status" >
	<option value="0" <?=getSelected(0, $status)?>>Borrador</option>
	<option value="1" <?=getSelected(1, $status)?>>Pendiente de Pago</option>
	<option value="2" <?=getSelected(2, $status)?>>Pagada Total</option>
	<option value="3" <?=getSelected(3, $status)?>>Cancelada</option>
</select>
<?php 
if($conf->global->MAIN_MODULE_MULTIDIVISA){
?>
Divisa:
<select name="tipo_divisa" >
<option value='0' <?=getSelected(0, $tipo_divisa)?>>Todas</option>
<?php 
$sql="SELECT rowid,code FROM ".MAIN_DB_PREFIX."multidivisa_divisas WHERE entity=".$conf->entity;
$rd=$db->query($sql);
while($rdd=$db->fetch_object($rd)){
?>
  <option value="<?=$rdd->rowid?>" <?=getSelected($rdd->rowid, $tipo_divisa)?>> <?=$rdd->code?></option>
<?php }?>
 </select>
 <?php }?>

<!--<input type="submit" name="" value="Consultar" />-->
<input type="submit" value='Consultar'/>
</form>
<p style="font-size:12px">

<script>
/* 	$(document).ready(function(e) {
		$('a').click(function(e) {
			e.preventDefault();
			
			var id = $(this).attr('id');
	       	show_facturas( id );
		});
    }); */
	
	function show_facturas( id ) {
		var tipo_factura = <?php echo $tipo_factura; ?>;
		var tipo_divisa = <?php echo $tipo_divisa; ?>;
		var aq = document.getElementById('fini').value;
		var aqf = document.getElementById('ffin').value;
		var status = <?php echo $status; ?>;
		var fiscales = <?php echo $fiscales?>;
		
		//alert("anioqry="+aq+" id="+id+" tipo="+tipo_factura+" status="+status);
		
		//document.form1.select1[0]. value = "new value";
		//document.getElementById("mes").value = id; 
		$.ajax({
			data: { fini: aq, ffin: aqf, tf: tipo_factura,td:tipo_divisa, st : status, fs : fiscales },
			url:	"facturacion_ajax_egresos.php", 
			type:	"get",
			beforeSend: function() {
				//alert("Hola 1");
				$("#mostrar_facturacion").html("Generando Reporte...");
			}
		}).done(function(response){
			//alert("Hola 2");
			$("#mostrar_facturacion").html(response);
		}).fail(function() {
			alert( "error" );
		});
	}
</script>
<?php
//Obtener los Subtotales (Importes Sin Iva) y mostrarlo para cada mes.

$extra_sql="";
$extra_sql.=" AND f.type = " . $tipo_factura;
$extra_sql.=" AND f.rowid NOT IN (SELECT fk_facture_source FROM ".$tbl_prefix."facture_fourn WHERE type = ".$status.")";

$sql = "
SELECT
	MONTH(f.datef) as mes, sum(f.total_ttc) as suma_total
FROM ".$tbl_prefix."facture_fourn f, ".$tbl_prefix."societe s 
WHERE f.fk_statut = 2 AND f.fk_soc = s.rowid AND f.entity=".$conf->entity." AND (YEAR(f.datef) BETWEEN YEAR('".$fecha_actuali."') AND YEAR('".$fecha_actualf."') )".$extra_sql."
GROUP BY MONTH(f.datef)
ORDER BY MONTH(f.datef)";
//echo $sql;
/* for($i=1; $i<=12; $i++){
	echo "<strong>".getMesNombre($i).":</strong>$0&nbsp;";
} */
//echo $sql."<br>";

$mes_inicio = ($mes >= 1) ? $mes : 0;
$res = $db->query( $sql );
while( $rs = $db->fetch_array( $res ) ){
	$m = $rs["mes"];
	$mes_inicio = ($mes_inicio == 0) ? $rs["mes"] : $mes_inicio;
	echo "<strong><a href='#' id=$m>".getMesNombre($m).":</a> </strong> $".number_format($rs["suma_total"],2)."&nbsp;";
}
?>
</p>

<div id="mostrar_facturacion">
<?php 
	//include_once "facturacion_ajax.php"; 
?>
	<script>
		show_facturas( <?php echo $mes_inicio; ?> );
	</script>
</div>

</body>
</html>

<?php 
llxFooter();
$db->close();
?>
