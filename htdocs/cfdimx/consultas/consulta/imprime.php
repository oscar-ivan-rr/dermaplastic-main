<?php 
//ssession_start();
require('../../../main.inc.php');
$excel=$_SESSION['tabla'];
$_SESSION['tabla']=''; 
$doc='';
if($_SESSION['doc']=='ingresos'){
	$doc='pagos_ingresos';
}
if($_SESSION['doc']=='facingresos'){
	$doc='facturas_ingresos';
}
if($_SESSION['doc']=='egresos'){
	$doc='pagos_egresos';
}
if($_SESSION['doc']=='facegresos'){
	$doc='facturas_egresos';
}
$dia=date('d');
$mes=date('m');
$anio=date('Y');
$doc.='_'.$dia.'-'.$mes.'-'.$anio.'.xls';
$_SESSION['doc']='';
echo $excel;
header("Content-type: application/ms-excel"); 
header("Content-disposition: attachment; filename=".$doc.""); 
exit;
?>