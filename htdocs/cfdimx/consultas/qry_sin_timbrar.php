<?php

$res = isset($res)?$res:null;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");

if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once("../conf.php");
//require_once '../class/facturecfdi.class.php';
//print 'hola'; die;

$title="Consultas Pre-Facturas";
llxHeader('',$title);
$form = new Form($db);
// --> DIXI Últimas facturas sin timbrar en periodo (72 hrs.)
$facturestatic = new Facture($db);
//$facturestaticSociete =new Societe($db);

print '
		<script type="text/javascript">
		function marcar(source)
		{
		checkboxes=document.getElementsByTagName("input"); //obtenemos todos los controles del tipo Input
		for(i=0;i<checkboxes.length;i++) //recoremos todos los controles
		{
		if(checkboxes[i].type == "checkbox") //solo si es un checkbox entramos
		{
		checkboxes[i].checked=source.checked; //si es un checkbox le damos el valor del checkbox que lo llam� (Marcar/Desmarcar Todos)
}
}
}
		</script>
		';

      $action = GETPOST('action');
      $confirm = GETPOST('confirm');

$mesg='';

if ($action == 'confirm_genUniFact' && $confirm == 'yes' && $user->rights->cfdimx->create)
{
	$array_checkbox = explode(",",GETPOST('checkbox'));
	$id = GETPOST('checkbox');
	$array_socid = explode(",",GETPOST('socid'));
	$socid=$array_socid[0];
	$object = new FactureCfdi($db);
	//origin=commande&originid=
	//$origin='commande';
	//$originid=$array_checkbox[0]; // For backward compatibility
	//include_once 'qry_sin_timbrar_.php';

	//var_dump($array_checkbox);
	//print '<br>';
	//var_dump($array_socid);
	//print '<br>';

	if ($object->fetch($id) > 0)
	{
		$result=$object->createFromClone($socid, $hookmanager);
		if ($result > 0)
		{
				
			$close_code='abandon';// default abandon, badcustomer
			$close_note='unfied desktop invoice of the day in rowid('.$result.')';//wherever you want
			if ($close_code)
			{
				$result = $object->set_canceled($user,$close_code,$close_note,$id);

				header("Location: ".$_SERVER['PHP_SELF'].'?facid='.$result);
				exit;
			}
			else
			{
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Reason")),'errors');
			}
				

		}
		else
		{
			$mesgs[]=$object->error;
			$action='';
		}
	}


	/*

	$result=1;

	if ($result > 0)
	{
	$mesg='<div class="ok">Se creo factura unificada</div>';
	//header('Location: index.php');
	//exit;
	}
	else
	{
	$mesg='<div class="error">'.$langs->trans("Error")." : ".$object->error.'</div>';
	}
	*/
}

$formconfirm='';

/*
 * Confirmation de la suppression de la commande
*/
if ($action == 'generaUniFact')
{
	 
	if (isset($_GET['checkbox'])){
		$array_comma_separated = implode(",",GETPOST('checkbox'));
		$socid_comma_separated = implode(",",GETPOST('socid'));

		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?checkbox='.$array_comma_separated.'&socid='.$socid_comma_separated, $langs->trans('UnifiedInvoice'), $langs->trans('ConfirmUnifiedInvoice'), 'confirm_genUniFact', '', 0, 1);
	}else{
		$error = 'debe selecciona por lo menos una factura de Cliente de Mostrador para unificar';
		$mesg='<div class="error">'.$langs->trans("Error")." : ".$error.'</div>';
	}
}

// Print form confirm
print $formconfirm;
print $mesg;



$fechaq_show = "";


if ($conf->facture->enabled && $user->rights->cfdimx->select)

{
	$sql = "SELECT f.ref, f.rowid, f.total_ttc, f.type,";
	$sql.= " s.nom, s.rowid as socid, f.fk_statut as statut";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut != 3";
		$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	if ($socid){
		$sql .= " AND f.fk_soc = $socid";
	}
	// TIME
	$sql .= " AND f.datef >  NOW() - INTERVAL 72 HOUR  ";
	$sql .= " AND f.rowid NOT IN (SELECT fk_facture FROM ".MAIN_DB_PREFIX."cfdimx)";

	if( $_REQUEST["envqry"]=="Consultar" ){
		
		if( $_REQUEST["datec"]!="" ){
			$fechat = explode("/",$_REQUEST["datec"]);
			$dia=$fechat[0];
			$mes=$fechat[1];
			$anio=$fechat[2];
			$fechaq_show = mktime(0,0,0,$mes,$dia,$anio);
			$sql.= ' AND f.datec like "'.$anio."-".$mes."-".$dia.'%"';
		}
	
		if( $_REQUEST["ref"]!="" ){
			$sql.=" AND f.ref LIKE '%".$_REQUEST["ref"]."%'";
		}
	}

	$resql = $db->query($sql);



	if ( $resql )

	{

		//Buscador
		print '<form id="searcher" name="searcher" action="'.$_SERVER['PHP_SELF'].'" method="post">';

		print "<strong>Filtrar por:</strong><br>";
		print "<strong>Fecha Timbrado:</strong> ";
		$form->select_date($fechaq_show,'datec','','','',"add",1,1);
		print "&nbsp;";
		print '<strong>NoFactura:</strong> <input type="text" name="ref" value="'.$_REQUEST["ref"].'">';
		print "&nbsp;";
		//print '<strong>Folio:</strong> <input type="text" name="folio" value="'.$_REQUEST["folio"].'">';
		print "&nbsp;";
		//print '<strong>UUID:</strong> <input type="text" name="UUID" size="50">';
		print '&nbsp;<input type="submit" name="envqry" value="Consultar">';
		print "</form>";
		print "<p></p>";
		print '</form>';
		//Buscador
		$var = false;

		$num = $db->num_rows($resql);



		print '<table class="noborder" width="100%">';
		print '<tr>';

		print '<td colspan="4">Últimas facturas sin timbrar en periodo de 72 hrs.</td></tr>';

		print '</tr>';

		print '<tr class="liste_titre">';

		print '<td>Facturación</td>';
		print '<td>Receptor</td>';
		print '<td align="center">UUID</td>';
		print '<td align="right">Importe</td>';

		print '</tr>';

		if ($num)

		{

			$companystatic=new Societe($db);
			if($conf->global->MAIN_INFO_CFDI_SHOW_NUM_ROWS_INTO_72H){
				$num2 = $conf->global->MAIN_INFO_CFDI_SHOW_NUM_ROWS_INTO_72H;
			}else{
				$num2 = $num;
			}


			$i = 0;

			$tot_ttc = 0;

			while ($i < $num && $i < 20)

			{

				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'><td nowrap="nowrap">';

				$facturestatic->ref=$obj->ref;

				$facturestatic->id=$obj->rowid;

				$facturestatic->type=$obj->type;

				print $facturestatic->getNomUrl(1,'');

				print '</td>';



				print '<td nowrap="nowrap">';

				$companystatic->id=$obj->socid;

				$companystatic->nom=$obj->nom;

				$companystatic->client=1;

				print $companystatic->getNomUrl(1,'',16);

				print '</td>';



				print '<td align="center">'.getLinkGeneraCFDI( $obj->statut, $obj->rowid, $db ).'</td>';



				print '<td align="right" nowrap="nowrap">'.price($obj->total_ttc).'</td>';

				print '</tr>';

				$tot_ttc+=$obj->total_ttc;

				$i++;

				$var=!$var;

			}



			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';

			print '<td colspan="3" align="right">'.price($tot_ttc).'</td>';

			print '</tr>';

		}

		else

		{

			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';

		}

		print "</table><br>";

		$db->free($resql);

	}

	else

	{

		dol_print_error($db);

	}

}

/*
 * Facturas dentro del dia para generar una sola factura
*/

$fechaq_show = "";
// --> DIXI Últimas facturas sin timbrar en periodo (72 hrs.)
$facturestatic = new Facture($db);
//$facturestaticSociete =new Societe($db);
require_once("../conf.php");

if ($conf->facture->enabled && $user->rights->cfdimx->select)

{
	$maxpesos = 100;
	$server_date = date('Y-m-d');
	$code_client_mostrador = 'XAXX010101000';
	$fk_status = 1;


	$sql  = "SELECT f.ref, f.rowid, f.total_ttc, f.type,";

	$sql.= " s.nom, s.rowid as socid, f.fk_statut as statut, DATE_FORMAT(f.datef,'%Y %m %d') as datef, s.code_client ";

	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";

	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";

	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";

	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut != 3";

	$sql.= " AND f.entity = ".$conf->entity;

	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;



	if ($socid){
		$sql .= " AND f.fk_soc = $socid";
	}

	// TIME

	$sql .= " AND DATE_FORMAT(f.datef,'%Y-%m-%d') = '".$server_date."'";//DATE_FORMAT(NOW(),'%Y-%m-%d')  ";



	$sql .= " AND f.rowid NOT IN (SELECT fk_facture FROM ".MAIN_DB_PREFIX."cfdimx)";

	$sql .= " AND f.total_ttc < ".$maxpesos;

	$sql .= " AND s.code_client = '".$code_client_mostrador."'";

	$sql .= " AND f.fk_statut = ".$fk_status;

	$sql .= " ORDER BY s.code_client desc";

	$resql = $db->query($sql);



	if ( $resql )

	{

		$var = false;

		$num = $db->num_rows($resql);


		print '<form name="unifica" action="'.$_SERVER['PHP_SELF'].'" method="get">';
		print '<input type="hidden" name="action" id="action" value="generaUniFact">';
		print '<table class="noborder" width="100%">';
		print '<tr>';

		print '<td colspan="4">Últimas facturas (Cliente de Mostrador) sin timbrar del dia '.$server_date.', menor a $'.$maxpesos.' MN.</td></tr>';

		print '</tr>';

		print '<tr class="liste_titre">';

		print '<td>Facturación</td>';
		print '<td>Receptor</td>';
		print '<td align="center">rfc</td>';
		print '<td align="center">fecha</td>';
		print '<td align="right">Importe</td>';
		print '<td align="right">ChK</td>';

		print '</tr>';

		if ($num)

		{

			$companystatic=new Societe($db);
			if($conf->global->MAIN_INFO_CFDI_SHOW_NUM_ROWS_MOSTRADOR_TODAY){
				$num2 = $conf->global->MAIN_INFO_CFDI_SHOW_NUM_ROWS_MOSTRADOR_TODAY;
			}else{
				$num2 = $num;
			}


			$i = 0;

			$tot_ttc = 0;

			while ($i < $num && $i < 20)

			{

				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'><td nowrap="nowrap">';

				$facturestatic->ref=$obj->ref;

				$facturestatic->id=$obj->rowid;

				$facturestatic->type=$obj->type;

				print $facturestatic->getNomUrl(1,'');

				print '</td>';

				print '<td nowrap="nowrap">';

				$companystatic->id=$obj->socid;

				$companystatic->nom=$obj->nom;

				$companystatic->client=1;

				print $companystatic->getNomUrl(1,'',16);

				print '</td>';

				print '<td align="center">'.$obj->code_client.'</td>';

				print '<td align="center" nowrap="nowrap">'.$obj->datef.'</td>';

				print '<td align="right" nowrap="nowrap">'.price($obj->total_ttc).'</td>';

				print '<td align="right" nowrap="nowrap"><input type="checkbox" name="checkbox['.$facturestatic->id.']" id="checkbox['.$facturestatic->id.']" value="'.$facturestatic->id.'" checked />';
				print '<input type="hidden" id="socid['.$obj->socid.']" name="socid['.$obj->socid.']" value="'.$obj->socid.'">';
				print '</td>';
				print '</tr>';

				$tot_ttc+=$obj->total_ttc;

				$i++;

				$var=!$var;

			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';

			print '<td colspan="4" align="right">'.price($tot_ttc).'</td>';
				
			print '<td colspan="1" align="right">
						
					</td>';

			print '</tr>';
			print '<tr>';
			print '<td colspan="5" align="right">';
			print '</td>';
			print '<td colspan="1" align="right">';
			print '</td>';
			print '</tr>';
			print '</tr>';
			print '<tr>';
			print '<td colspan="6" align="right"> Marcar/Desmarcar Todos <input type="checkbox" onclick="marcar(this);" checked />';
			print '</td>';
			print '</tr>';
			print '<tr>';
			print '<td  colspan="4" align="right">';
			print '</td>';
			print '<td colspan="2" align="right"> <br><br>';
			//print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=generaUificado">UNIFICA</a>'."<br>".$msg_dom_receptor." ".$msg_mail;//AMM boton generar CFDI
				
			print '<input type="submit" class="butAction" value="UNIFICA">';
			print '<br><br></td>';
			print '</tr>';
		}

		else

		{

			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';

		}

		print "</table><br>";
		print '</form>';
		$db->free($resql);

	}

	else

	{

		dol_print_error($db);

	}

}
llxFooter();
$db->close();
?>