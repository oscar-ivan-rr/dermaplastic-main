<?php
//error_reporting(E_ERROR);
$res = isset($res)?$res:null;

//if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
require '../../main.inc.php';
$form = new Form($db);

$fechaq_show = "";

if( $_REQUEST["envqry"]=="Consultar" ){
	if( $_REQUEST["ftimbrado"]!="" ){
		$fechat = explode("/",$_REQUEST["ftimbrado"]);
		$dia=$fechat[0];
		$mes=$fechat[1];
		$anio=$fechat[2];
		$fechaq_show = mktime(0,0,0,$mes,$dia,$anio);
		$extra_qry.= ' AND fecha_timbrado = "'.$anio."-".$mes."-".$dia.'"';
	}
	
	if( $_REQUEST["serie"]!="" ){
		$extra_qry.=" AND factura_serie LIKE '%".$_REQUEST["serie"]."%'";
	}

	if( $_REQUEST["folio"]!="" ){
		$extra_qry.=" AND factura_folio LIKE '%".$_REQUEST["folio"]."%'";
	}
	
}

$title="Consultas CFDI";
llxHeader('',$title);

$var=True;

echo '<h3 align="center">FACTURAS TIMBRADAS</h3>';

print '<form method="post">';

print "<strong>Filtrar por:</strong><br>";
print "<strong>Fecha Timbrado:</strong> ";
$form->select_date($fechaq_show,'ftimbrado','','','',"add",1,1);
print "&nbsp;";
print '<strong>Serie:</strong> <input type="text" name="serie" value="'.$_REQUEST["serie"].'">';
print "&nbsp;";
print '<strong>Folio:</strong> <input type="text" name="folio" value="'.$_REQUEST["folio"].'">';
print "&nbsp;";
print '<strong>UUID:</strong> <input type="text" name="UUID" size="50">';
print '&nbsp;<input type="submit" name="envqry" value="Consultar">';
print "</form>";
print "<p></p>";


print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
		print "<td>Factura</td>";
		print "<td>Fecha Timbrado</td>";
		print "<td>Divisa</td>";
		print "<td>Tipo de Documento</td>";
		print "<td>UUID</td>";
		print "<td>Status</td>";
		print "<td>Tipo</td>";
	print '</tr>';
	
	$sql = "
	SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE entity_id = ".$_SESSION['dol_entity']." ".$extra_qry."
	ORDER BY fechaTimbrado DESC";
	//print $sql;
	$resql=$db->query($sql);
	if ($resql){
		 $num = $db->num_rows($resql);
		 $i = 0;
		 if ($num){
			 while ($i < $num){
				 $obj = $db->fetch_object($resql);
				$var=!$var;
				if( $obj->tipo_timbrado==1 ){ $tipo="Produccion";  }else{ $tipo="Pruebas"; }
				if( $obj->cancelado==0 ){ $status="Activo";  }else{ $status="Cancelada"; }
				if($conf->global->MAIN_MODULE_MULTIDIVISA){
					$sql="SELECT IFNULL(divisa,NULL) as divisa
							FROM ".MAIN_DB_PREFIX."multidivisa_facture
							WHERE fk_object=".$obj->fk_facture;
					$rd=$db->query($sql);
					$rdd=$db->fetch_object($rd);
					if($rdd->divisa!=NULL){
						$divisa=$rdd->divisa;
					}else{
						$divisa=$conf->currency;
					}
				}else{
					if($conf->global->MAIN_MODULE_MULTICURRENCY){
						$sql="SELECT multicurrency_code AS divisa FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$obj->fk_facture;
						$ra=$db->query($sql);
						$rb=$db->fetch_object($ra);
						$divisa=$rb->divisa;
					}else{
						$divisa=$conf->currency;
					}
				}			
				$sql="SELECT IFNULL(tipo_document,NULL) as tipo_document
				FROM ".MAIN_DB_PREFIX."cfdimx_type_document
				WHERE fk_facture=".$obj->fk_facture;
				//print $sql."<br>";
				$resp=$db->query($sql);
				$respp=$db->fetch_object($resp);
				if($respp->tipo_document!=NULL){
					if($respp->tipo_document==1){
						$tipo_doc="Factura Estandar";
					}
					if($respp->tipo_document==2){
						$tipo_doc="Recibo de Honorarios";
					}
					if($respp->tipo_document==3){
						$tipo_doc="Recibo de Arrendamiento";
					}
					if($respp->tipo_document==4){
						$tipo_doc="Nota de Credito";
					}
				}else{
					$sql="SELECT type FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$obj->fk_facture;
					//print $sql;
					$resp=$db->query($sql);
					$respp=$db->fetch_object($resp);
					if($respp->type==2){
						$tipo_doc="Nota de Credito";
					}else{
						$tipo_doc="Factura Estandar";
					}
				}	
				print '<tr '.$bc[$var].'>';
					print "<td>&nbsp;<a href='../../cfdimx/facture.php?facid=".$obj->fk_facture."'>".$obj->factura_seriefolio."</a></td>";
					print "<td>".$obj->fecha_timbrado."</td>";
					print "<td>".$divisa."</td>";
					print "<td>".$tipo_doc."</td>";
					print "<td><a href='../facture.php?facid=".$obj->fk_facture."'>".$obj->uuid."</a></td>";
					print "<td>".$status."</td>";
					print "<td>".$tipo."</td>";
				print '</tr>';
				 $i++;
			 }
		 }
	}

print "</table>";

llxFooter();
$db->close();
?>