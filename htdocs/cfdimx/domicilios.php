<?php
	
error_reporting(0);
date_default_timezone_set("America/Mexico_City");

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
if ($conf->projet->enabled) {
	require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
}



global $db;
$action	= GETPOST('action');

llxHeader('','','','','','','Domicilios','',0,0);

$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
$object = new Societe($db);
$object->fetch($id);
$head = societe_prepare_head($object);
dol_fiche_head($head, 'tabfactclient', $langs->trans("ThirdParty"),0,'company');

$socid=$_REQUEST["socid"];
$ctenombre='';
$rfc_receptor='';
//$id_receptor='';
$entity_receptor='';
$entity_receptor= $conf->entity;
if($socid!=''){
	$sql="SELECT b.nom,b.siren as rfc,b.rowid,b.entity FROM ".MAIN_DB_PREFIX."societe b 
			WHERE b.rowid='".$socid."'";
	$rest=$db->query($sql);
	//echo '1: '.$sql;
	$resu=$db->fetch_object($rest);
	$ctenombre=$resu->nom;
	$rfc_receptor=$resu->rfc;
	//$id_receptor=$resu->rowid;
}
if($_REQUEST['actualizar']){
	$tpdomicilio	= GETPOST('tpdomicilio2');
	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor SET receptor_delompio='".$_REQUEST['delompio']."', receptor_colonia='".$_REQUEST['colonianw']."', 
			receptor_calle='".$_REQUEST['calle']."', receptor_noext='".$_REQUEST['noext']."', receptor_noint='".$_REQUEST['noint']."', cod_municipio='".$_REQUEST['codmunicipio']."'
			WHERE tpdomicilio='".$tpdomicilio."' AND receptor_rfc='".$rfc_receptor."' AND entity_id=".$entity_receptor." AND fk_socid=".$socid;
	$r1=$db->query($sql);
	//echo $sql;
	//header('Location: domicilios.php?socid='.$socid);
	print '<script>window.location="domicilios.php?socid='.$socid.'";</script>';
}
if($_REQUEST['tpdomicilio']!='' && $_REQUEST['guardar']){
	$sql="SELECT count(*) as existe FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE tpdomicilio='".$_REQUEST['tpdomicilio']."' AND receptor_rfc='".$rfc_receptor."' AND entity_id=".$entity_receptor." AND fk_socid=".$socid;
	$r1=$db->query($sql);
	//echo '<BR>2: '.$sql;
	$r2=$db->fetch_object($r1);
	if($r2->existe>0){
		dol_htmloutput_errors('Error: Ya existe un domicilio con esa etiqueta');
	}else{
		$determinado=2;
		if($_REQUEST['guardar']){
			$sql="SELECT count(*) as existe FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE determinado='1' AND receptor_rfc='".$rfc_receptor."' AND entity_id=".$entity_receptor." AND fk_socid=".$socid;
			$r3=$db->query($sql);
			//echo '<BR>3: '.$sql;
			$r4=$db->fetch_object($r3);
			if($r4->existe<1){
				$determinado=1;
			}
			$sql="SELECT count(*) as existe FROM ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc='".$rfc_receptor."' AND entity_id=".$entity_receptor;
			$r5=$db->query($sql);
			$r6=$db->fetch_object($r5);
			//echo $r6->existe.': AQUI<br>';
			if($r6->existe<1){
				$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp (receptor_rfc, receptor_delompio, receptor_colonia, receptor_calle,
												receptor_noext, receptor_noint, entity_id)
					 VALUES ('".$rfc_receptor."', '".$_REQUEST['delompio']."', '".$_REQUEST['colonianw']."',
					 		'".$_REQUEST['calle']."','".$_REQUEST['noext']."','".$_REQUEST['noint']."',
					 				'".$entity_receptor."')";
				//echo $sql;
				$r5=$db->query($sql);
			}
			$sql2="SELECT receptor_id FROM ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc='".$rfc_receptor."'";
			$r5=$db->query($sql2);
			$r6=$db->fetch_object($r5);
			$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor (receptor_rfc, tpdomicilio, receptor_delompio, receptor_colonia, 
							receptor_calle, receptor_noext, receptor_noint, receptor_id, 
							entity_id,determinado,cod_municipio,fk_socid)
					 VALUES ('".$rfc_receptor."','".$_REQUEST['tpdomicilio']."','".$_REQUEST['delompio']."','".$_REQUEST['colonianw']."',
					 		'".$_REQUEST['calle']."','".$_REQUEST['noext']."','".$_REQUEST['noint']."','".$r6->receptor_id."',
					 			'".$entity_receptor."','".$determinado."','".$_REQUEST['codmunicipio']."',$socid)";
			$r5=$db->query($sql);
		}
	}
}
$tpdom='';
$rdelompio='';
$rcolonia='';
$rcalle='';
$rnoext='';
$rnoint='';
$rcodmunicipio='';		
if($action=='edit'){
	$tpd	= GETPOST('tpd');
	$sql='SELECT tpdomicilio, receptor_delompio, receptor_colonia, receptor_calle, receptor_noext, receptor_noint, determinado, cod_municipio 
		FROM '.MAIN_DB_PREFIX.'cfdimx_domicilios_receptor WHERE tpdomicilio="'.$tpd.'" AND receptor_rfc="'.$rfc_receptor.'"  AND entity_id='.$entity_receptor." AND fk_socid=".$socid;
	$ract=$db->query($sql);
	$ractu=$db->fetch_object($ract);
	$tpdom=$ractu->tpdomicilio;
	$rdelompio=$ractu->receptor_delompio;
	$rcolonia=$ractu->receptor_colonia;
	$rcalle=$ractu->receptor_calle;
	$rnoext=$ractu->receptor_noext;
	$rnoint=$ractu->receptor_noint;
	$rcodmunicipio=$ractu->cod_municipio;
}
if($action=='delete'){
	$tpd	= GETPOST('tpd');
	$sql='SELECT determinado FROM '.MAIN_DB_PREFIX.'cfdimx_domicilios_receptor WHERE tpdomicilio="'.$tpd.'" AND receptor_rfc="'.$rfc_receptor.'" AND entity_id='.$entity_receptor." AND fk_socid=".$socid;
	//echo $sql;
	$ract=$db->query($sql);
	$ractu=$db->fetch_object($ract);
	if($ractu->determinado=='1'){
		//header('Location: domicilios.php?socid='.$socid.'&det=ed');
		print '<script>window.location="domicilios.php?socid='.$socid.'&det=ed";</script>';
	}else{
		$sql='DELETE FROM '.MAIN_DB_PREFIX.'cfdimx_domicilios_receptor WHERE tpdomicilio="'.$tpd.'" AND receptor_rfc="'.$rfc_receptor.'" AND entity_id='.$entity_receptor." AND fk_socid=".$socid;
		$ract=$db->query($sql);
		//header('Location: domicilios.php?socid='.$socid.'');
		print '<script>window.location="domicilios.php?socid='.$socid.'";</script>';
	}
}
$ert = GETPOST('det');
if($ert!='' && $ert=='ed'){
	dol_htmloutput_errors('No puede eliminar el domicilio predeterminado.');
}
print "Empresa: ";
/* print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$socid.'">
		<img src="/dolibarr-3.6.2/htdocs/theme/eldy/img/object_company.png" border="0" alt="Mostar empresa: '.$ctenombre.'" 
		title="Mostar empresa: '.$ctenombre.'"></a> '; */
print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$socid.'">'.$ctenombre.'</a>';

print "<table width='100%'><tr><td valign='top'>";
print "<table class='border' width='100%'>";
print "<form method='POST'>";
//print '<input type="hidden" name="facid" value="'.$facid.'">';
print "<tr>";
	print "<td colspan='2' align='center'>";
		print "<b>Agregar Nuevo Domicilio</b>";
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "*Etiqueta del Domicilio ";
	print "</td>";
	print "<td>";
	if($action=='edit'){
		$a='disabled';
	}else{
		$a='';
	}
		print '<input type="text" size="30" name="tpdomicilio" value="'.$tpdom.'" '.$a.' required>';
		print '<input type="hidden" name="tpdomicilio2" value="'.$tpdom.'">';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "*Delegaci&oacute;n o Municipio";
	print "</td>";
	print "<td>";
		print '<input type="text" size="30" name="delompio" value="'.$rdelompio.'" required>';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "Codigo del Municipio";
	print "</td>";
	print "<td>";
		print '<input type="text" size="30" name="codmunicipio" value="'.$rcodmunicipio.'">';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "*Colonia";
	print "</td>";
	print "<td>";
		print '<input type="text" size="30" name="colonianw" value="'.$rcolonia.'" required>';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "*Calle";
	print "</td>";
	print "<td>";
		print '<input type="text" size="30" name="calle" value="'.$rcalle.'" required>';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "*No. Exterior";
	print "</td>";
	print "<td>";
		print '<input type="text" size="30" name="noext" value="'.$rnoext.'" required>';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "No. Interior";
	print "</td>";
	print "<td>";
		print '<input type="text" size="30" name="noint" value="'.$rnoint.'" >';
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td colspan='2' align='center'>";
if($action=='edit'){
	print "<input type='submit' name='actualizar' value='Actualizar'>";
}else{
	if($rfc_receptor==''){
		print "El tercero no tiene un RFC asignado";
	}else{
		print "<input type='submit' name='guardar' value='Guardar'>";
	}
}
	print "</td>";
print "</tr>";
print "</form>";
print "</table>
		</td><td>";

print "<table class='border' width='100%'>";
print "<tr>";
print "<td colspan='4' align='center'>";
print "<b>Lista de Domicilios</b>";
print "</td>";
print "</tr>";
print "<tr>";
print "<td align='center'>";
print "<b>Predeterminado</b>";
print "</td>";
print "<td colspan='3' align='center'>";
print "<b>DOMICILIO</b>";
print "</td>";
print "</tr>";
if($_REQUEST['actualizap']){
	$sql="SELECT receptor_delompio, receptor_colonia, receptor_calle, receptor_noext, receptor_noint
		FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE receptor_rfc='".$rfc_receptor."' AND tpdomicilio='".$_REQUEST['seldomicilio']."' AND entity_id=".$entity_receptor." AND fk_socid=".$socid;
	$ra1=$db->query($sql);
	$ra2=$db->fetch_object($ra1);

	$sql="SELECT count(*) as existe FROM ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc='".$rfc_receptor."' AND entity_id=".$entity_receptor;
	$rs1=$db->query($sql);
	$rs2=$db->fetch_object($rs1);
	if($rs2->existe>0){
		$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp SET receptor_delompio='".$ra2->receptor_delompio."', receptor_colonia='".$ra2->receptor_colonia."', 
				receptor_calle='".$ra2->receptor_calle."', receptor_noext='".$ra2->receptor_noext."', receptor_noint='".$ra2->receptor_noint."' 
				WHERE receptor_rfc='".$rfc_receptor."' AND entity_id=".$conf->entity;
		$rs1=$db->query($sql);
	}else{
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp (receptor_rfc,receptor_delompio, receptor_colonia, receptor_calle, receptor_noext, 
				  receptor_noint, entity_id) 
				VALUES('".$rfc_receptor."', '".$ra2->receptor_delompio."', '".$ra2->receptor_colonia."', '".$ra2->receptor_calle."', 
						'".$ra2->receptor_noext."', '".$ra2->receptor_noint."', '".$entity_receptor."')";
		$rs1=$db->query($sql);
	}
	//echo $sql;
	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor SET determinado=2 WHERE receptor_rfc='".$rfc_receptor."' AND entity_id=".$conf->entity." AND fk_socid=".$socid;
	$rs1=$db->query($sql);
	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor SET determinado=1 WHERE receptor_rfc='".$rfc_receptor."' AND tpdomicilio='".$_REQUEST['seldomicilio']."' AND entity_id=".$conf->entity." AND fk_socid=".$socid;
	$rs1=$db->query($sql);
}
$sql="SELECT tpdomicilio, receptor_delompio, receptor_colonia, receptor_calle, receptor_noext, receptor_noint, determinado ,cod_municipio
		FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE receptor_rfc='".$rfc_receptor."' AND entity_id=".$conf->entity." AND fk_socid=".$socid;
//echo $sql;
$rs1=$db->query($sql);
$rsnum=$db->num_rows($rs1);
print "<form method='POST'>";
while ($rs2=$db->fetch_object($rs1)){
print "<tr>";
print "<td rowspan='7' align='center'>";
if($rs2->determinado==1){
	print "<input type='radio' name='seldomicilio' value='".$rs2->tpdomicilio."' checked>";
}else{
	print "<input type='radio' name='seldomicilio' value='".$rs2->tpdomicilio."'>";
}
print "</td>";
print "<td colspan='2'><b>";
print $rs2->tpdomicilio;
print "</b></td>";
print "<td rowspan='7' align='center'>";
	print "<input type='button' name='editar' value='Editar' onclick=\"window.location.href='domicilios.php?socid=".$socid."&action=edit&tpd=".$rs2->tpdomicilio."'\" style='width:60px;'><br>";
	print "<input type='button' name='borrar' value='Eliminar' onclick=\"if (confirm('Desea eliminar el domicilio?')) window.location.href='domicilios.php?socid=".$socid."&action=delete&tpd=".$rs2->tpdomicilio."';\"  style='width:60px;'>";
print "</td>";
print "</tr>";
print "<tr>";
print "<td>";
print "Delegaci&oacute;n o Municipio";
print "</td>";
print "<td>";
print $rs2->receptor_delompio;
print "</td>";
print "</tr>";
print "<tr>";
print "<td>";
print "Codigo del Municipio";
print "</td>";
print "<td>";
print $rs2->cod_municipio;
print "</td>";
print "</tr>";
print "<tr>";
print "<td>";
print "Colonia";
print "</td>";
print "<td>";
print $rs2->receptor_colonia;
print "</td>";
print "</tr>";
print "<tr>";
print "<td>";
print "Calle";
print "</td>";
print "<td>";
print $rs2->receptor_calle;
print "</td>";
print "</tr>";
print "<tr>";
print "<td>";
print "No. Exterior";
print "</td>";
print "<td>";
print $rs2->receptor_noext;
print "</td>";
print "</tr>";
print "<tr>";
print "<td>";
print "No. Interior";
print "</td>";
print "<td>";
print $rs2->receptor_noint;
print "</td>";
print "</tr>";
}
print "<td align='center' colspan='4'>";
if($rsnum<1){}else{
print "<input type='submit' name='actualizap' value='Actualizar Predeterminado'>";
}
print "</td>";
print "</tr>";
print "</form>";
print "</table>";
print "</td></tr>";
print "</table>";

llxFooter();
$db->close();
