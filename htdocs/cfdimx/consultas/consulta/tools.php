<?php
require('../../../main.inc.php');
global $db,$conf;
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
$tbl_prefix=MAIN_DB_PREFIX;

function getSelected( $val1, $val2 ){
	$rs="no";
	if( $val1 == $val2 ){
		$rs = "selected";
	}
	return $rs;
}

function getMesNombre($mes){
	if( $mes=='1'){ $val='Enero'; }
	if( $mes=='2'){ $val='Febrero'; }
	if( $mes=='3'){ $val='Marzo'; }
	if( $mes=='4'){ $val='Abril'; }
	if( $mes=='5'){ $val='Mayo'; }
	if( $mes=='6'){ $val='Junio'; }
	if( $mes=='7'){ $val='Julio'; }
	if( $mes=='8'){ $val='Agosto'; }
	if( $mes=='9'){ $val='Septiembre'; }
	if( $mes=='10'){ $val='Octubre'; }
	if( $mes=='11'){ $val='Noviembre'; }
	if( $mes=='12'){ $val='Diciembre'; }
	return $val;
}

function getTotalPagado( $fk_facture, $prefix ){
	global $db;
	$sql = "
	SELECT SUM(amount) importe_pagado FROM ".$prefix."paiement_facture 
	WHERE fk_facture = ".$fk_facture."
	GROUP BY fk_facture";
	$qry = $db->query( $sql );
	$rs = $db->fetch_array( $qry );
	return $rs["importe_pagado"];
}

function getTotalPagadoEgresos( $fk_facture, $prefix ){
	global $db;
	$sql = "
	SELECT SUM(amount) importe_pagado FROM ".$prefix."paiementfourn_facturefourn
	WHERE fk_facturefourn = ".$fk_facture."
	GROUP BY fk_facturefourn";
	$qry = $db->query( $sql );
	$rs = $db->fetch_array( $qry );
	return $rs["importe_pagado"];
}

function getDataConst( $tp, $prefix ){
	global $db,$conf;
	$sql = "SELECT value FROM ".$prefix."const WHERE name = '".$tp."' AND entity=".$conf->entity;
	$qry = $db->query( $sql );
	$rs = $db->fetch_array($qry );
	return $rs["value"];
}

function validaModCFDI($prefix){
	global $db;
	$sql="show tables like '".$prefix."cfdimx'";
	$result = $db->query($sql) ;
	return $db->num_rows($result);
}

function getValidaFiscal($prefix, $fk_facture){
	global $db;
	$sql = "SELECT COUNT(*) total FROM ".$prefix."cfdimx 
	WHERE fk_facture = ".$fk_facture." AND cancelado = 0";
	$qry = $db->query($sql) ;
	$rs = $db->fetch_array($qry);
	$a=$rs["total"];
	return $a;
}

function formaPago( $tp ){
	//dol_syslog('ENTRA AQUI:'.$tp);
	if( $tp=="Virement" ){
		$str = "Transferencia";
	}else{
		if(substr($tp, 0, 3)=="Esp" && substr($tp, 5, 3)=="ces") {
			$str="Efectivo";
		}else{
			if(substr($tp,7,6)=="vement") {
				$str="Impuesto";
			}else{
				if($tp=='Carte Bancaire'){
					$str="Cuenta Bancaria";
				}else{
					if($tp=='Paiement en ligne'){
						$str="Pago en linea";
					}else{
						$str=$tp;
					}
				}
			}
		}
	}
	return $str;
}

function getRecordCount($prefix, $field, $value, $fini, $ffin)
{
	global $db,$conf;
	$sql = "
		SELECT count(*) as cant
		FROM ".MAIN_DB_PREFIX."facture f, ".MAIN_DB_PREFIX."societe s
		WHERE  f.fk_soc = s.rowid 
			AND f.entity=".$conf->entity." AND (f.datef BETWEEN '".$fini."' AND '".$ffin."' )	";
	if ($field == "tf") {
		$sql .= " AND f.type = ".$value;
	}
	if ($field == "st") {
		$sql .= " AND f.fk_statut= ".$value."";
	}
	$sql .= " ORDER BY f.datef";
	
	//echo "<br>".$sql."<br>";
	
	$qry = $db->query($sql) ;
	$rs = $db->fetch_array( $qry );
	$cant = $rs["cant"];
	return $cant;
}

function getRecordCountEgresos($prefix, $field, $value, $fini, $ffin)
{
	global $db,$conf;
	$sql = "
		SELECT count(*) as cant
		FROM ".MAIN_DB_PREFIX."facture_fourn f, ".MAIN_DB_PREFIX."societe s
		WHERE  f.fk_soc = s.rowid
			AND f.entity=".$conf->entity." AND (f.datef BETWEEN '".$fini."' AND '".$ffin."' )	";
	if ($field == "tf") {
		$sql .= " AND f.type = ".$value;
	}
	if ($field == "st") {
		$sql .= " AND f.fk_statut= ".$value."";
	}
	$sql .= " ORDER BY f.datef";

	//echo "<br>".$sql."<br>";

	$qry = $db->query($sql) ;
	$rs = $db->fetch_array( $qry );
	$cant = $rs["cant"];
	return $cant;
}
?>