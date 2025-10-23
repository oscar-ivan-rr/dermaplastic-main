<?php

	$res=0;

	if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");

	if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

	if (! $res) die("Include of main fails");

	//if(file_exists("lib/nusoap/lib/nusoap.php")) {include("/lib/nusoap/lib/nusoap.php");}else{print 'nusoap lib not found';}

	require_once("lib/nusoap/lib/nusoap.php");
	require_once("conf.php");

	// Load traductions files requiredby by page

	$langs->load("companies");

	$langs->load("other");

	// Get parameters

	$id			= GETPOST('id','int');
	$action		= GETPOST('action','alpha');
	$myparam	= GETPOST('myparam','alpha');


	// Protection if external user

	if ($user->societe_id > 0)

	{

		//accessforbidden();

	}

	/*******************************************************************

	* ACTIONS

	*

	* Put here all code to do according to value of "action" parameter

	********************************************************************/

	if ($action == 'add')

	{

		$myobject=new Skeleton_Class($db);

		$myobject->prop1=$_POST["field1"];

		$myobject->prop2=$_POST["field2"];

		$result=$myobject->create($user);

		if ($result > 0)

		{

			// Creation OK

		}
		else
		{

			// Creation KO

			$mesg=$myobject->error;

		}

	}


	/***************************************************

	* VIEW

	*

	* Put here all code to build page

	****************************************************/



	llxHeader('','CFDI','');

	$form=new Form($db);

	// Put here content of your page

	// Example 1 : Adding jquery code

	print '<script type="text/javascript" language="javascript">

	jQuery(document).ready(function() {

		function init_myfunc()

		{

			jQuery("#myid").removeAttr(\'disabled\');

			jQuery("#myid").attr(\'disabled\',\'disabled\');

		}

		init_myfunc();

		jQuery("#mybutton").click(function() {

			init_needroot();

		});

	});
	</script>';


	$facturestatic = new Facture($db);


	$doli_version = (int)DOL_VERSION;

	if($doli_version >= 12){
		if($doli_version >= 14){
			$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.total_ht AS total, f.total_ttc,';
		}else{
			$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.total, f.total_ttc,';
		}		
	}else{
		$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.amount, f.total, f.total_ttc,';
	}


	$sql.= ' f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut,';

	$sql.= ' s.nom, s.rowid as socid,';

	$sql.= ' SUM(pf.amount) as am';

	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";

	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';

	$sql.= " WHERE f.fk_soc = s.rowid";

	$sql.= " AND f.entity = ".$conf->entity;

	if($doli_version >= 12){
		if($doli_version >= 14){
			$sql.= ' GROUP BY f.rowid, f.ref, f.type, total, f.total_ttc,';
		}else{
			$sql.= ' GROUP BY f.rowid, f.ref, f.type, f.total, f.total_ttc,';
		}		
	}else{
		$sql.= ' GROUP BY f.rowid, f.ref, f.type, f.amount, f.total, f.total_ttc,';
	}

	$sql.= ' f.datef, f.datec, f.paye, f.fk_statut,';

	$sql.= ' s.nom, s.rowid';

	$sql.= " ORDER BY f.datef DESC, f.datec DESC";


	$MAXLIST=10;


	$resql=$db->query($sql);

	if ($resql)

	{

		$var=true;

		$num = $db->num_rows($resql);

		$i = 0;

		if ($num > 0)

		{

	print '<table width="100%" class="notopnoleftnoright">';

	print '<tr>';

	print '<td valign="top" width="20%" class="notopnoleft">';

	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id_menu='.$_GET['idmenu'].'&bus=1">';

		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		print '<table width="100%" class="noborder">';

		print "<tr class=\"liste_titre\">";

		print '<td colspan="2">Consulta por:</td></tr>';

		print '</tr>';

		print '<tr $bc[0]><td>&nbsp;UUID:</td><td><input type="text" name="sallUID" class="flat" size="18"></td></tr>';

		print '<tr $bc[0]><td>&nbsp;Folio:</td><td><input type="text" name="sallFOL" class="flat" size="18"></td></tr>';

	    print '<tr $bc[0]><td>&nbsp;Serie:</td><td><input type="text" name="sallSER" class="flat" size="18"></td></tr>';

	    print '<tr $bc[0]><td>&nbsp;'.$langs->trans('Date').':</td>';
	    print '<td>';

	    // Calendario para seleccionar la fecha
	           $form->select_date($object->date,'invoicedate',0,0,1,'form'.'invoicedate',1,1);

		print  '</td></tr>';

		print '<tr><td>&nbsp;<td></tr>';
		print '<tr><td colspan="2" align="center"><input type="submit" value="Consultar" class="button"></td></tr>';
		print '<tr><td>&nbsp;<td></tr>';

		print "</table></form><br>";



	if( $_REQUEST["bus"]==1 ){





		if( $_REQUEST["sallSER"]!="" ){

			$extra_qry.=" AND factura_serie LIKE '%".$_REQUEST["sallSER"]."%'";

		}



		if( $_REQUEST["sallFOL"]!="" ){

			$extra_qry.=" AND factura_folio LIKE '%".$_REQUEST["sallFOL"]."%'";

		}



		if( $_REQUEST["sallUID"]!="" ){

			$extra_qry.=" AND uuid = '".$_REQUEST["sallUID"]."'";

		}



		//EJEMPLO 2013-12-07T12:43:01 in DATABASE
		if( $_REQUEST["invoicedateyear"]!="" ){

			if(isset($_REQUEST["invoicedatemonth"])){
				if($_REQUEST["invoicedatemonth"]<10){
					$_REQUEST["invoicedatemonth"] = '0'.$_REQUEST["invoicedatemonth"];
				}
			}
			if(isset($_REQUEST["invoicedateday"])){
				if($_REQUEST["invoicedateday"]<10){
					$_REQUEST["invoicedateday"] = '0'.$_REQUEST["invoicedateday"];
				}
			}
			$fechaTimbrado  = $_REQUEST["invoicedateyear"].'-'.$_REQUEST["invoicedatemonth"].'-'.$_REQUEST["invoicedateday"];
			$fechaTimbrado .= 'T%';

			$extra_qry.=" AND fechaTimbrado LIKE '".$fechaTimbrado."'";

		}


		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';

			print "<td>Factura</td>";

			print "<td>UUID</td>";

			print "<td>Status</td>";

		print '</tr>';



		$ssql = "

		SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE entity_id = ".$_SESSION['dol_entity']."

		".$extra_qry."

		ORDER BY fechaTimbrado DESC";

		$ressql=$db->query($ssql);

		if ($ressql){

			 $nnum = $db->num_rows($ressql);

			 $i = 0;

			 if ($nnum){

				 while ($i < $nnum){

					 $oobj = $db->fetch_object($ressql);

					$var=!$var;

					if( $oobj->cancelado==0 ){ $status="Activo";  }else{ $status="Cancelada"; }

					print '<tr '.$bc[$var].'>';

						print "<td>&nbsp;<a href='../compta/facture.php?facid=".$oobj->fk_facture."'>".$oobj->factura_seriefolio."</a></td>";

						print "<td><a href='facture.php?facid=".$oobj->fk_facture."'>".$oobj->uuid."</a></td>";

						print "<td>".$status."</td>";

					print '</tr>';

					 $i++;

				 }

			 }

		}

		print "</table><br>";

	}


	print '</td>';



	// --> DIXI -->Todas

	print '<td valign="top" width="80%" class="notopnoleftnoright">';

			print '<table width="100%" class="noborder" >';

			$tableaushown=1;

			print '<tr>';
			print '<td>Últimos 10 Registros</td>';
			print '</tr>';

			// <td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->id.'"> Total: ('.$num.')</a>
			print '<tr class="liste_titre">';
			print '
					<td>Facturación</td>
					<td>Fecha Factura</td>
				    <td>Receptor</td>
					<td align="center">UUID</td>
	                <td align="right">Total: ('.$num.')</a>
					</td>
					<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a>
					</td>';
			print '</tr>';

		}
		$facturestaticSociete =new Societe($db);

		while ($i < $num && $i < $MAXLIST)

		{

			$objp = $db->fetch_object($resql);

			$var=!$var;

			print "<tr $bc[$var]>";

			print '<td>';

			$facturestatic->id=$objp->facid;

			$facturestatic->ref=$objp->ref;

			$facturestatic->type=$objp->type;

			print $facturestatic->getNomUrl(1);

			


			print '</td>';

			if ($objp->df > 0)

			{

				print "<td align=\"left\">".dol_print_date($db->jdate($objp->df),'day')."</td>\n";

			}

			else

			{

				print "<td align=\"right\"><b>!!!</b></td>\n";

			}

			//print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

			//Receptor
			print '<td>';

			$facturestaticSociete->id=$objp->socid;

			$facturestaticSociete->nom=$objp->nom;

	    	$facturestaticSociete->client=1;

			print $facturestaticSociete->getNomUrl(1,'',16);

			print '</td>';

	        //print $objp->statut;
			print "<td align=\"center\">".getLinkGeneraCFDI( $objp->statut, $objp->facid, $db )."</td>\n";



			// --> DIXI

			/*

			$ssq = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx WHERE factura_id = ".$objp->facid. "";

			$resqll=$db->query($ssq);

			$num_reg = $db->num_rows($resqll);

			if( $num_reg==0){

				print "<td align=\"right\"></td>\n";

			}else{

				print "<td align=\"right\"></td>\n";

			}

	        */



			print '<td align="right" nowrap="nowrap" colspan="2">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am))."</td>\n";

			print "</tr>\n";

			$i++;

		}

		$db->free($resql);



		if ($num > 0) print "</table><br>";





	// --> DIXI Últimas facturas sin timbrar en periodo (72 hrs.)

	if ($conf->facture->enabled && $user->rights->facture->lire)

	{

		$sql  = "SELECT f.ref, f.rowid, f.total_ttc, f.type,";

		$sql.= " s.nom, s.rowid as socid, f.fk_statut as statut";

		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";

		$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";

		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";

		$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut != 3";

		$sql.= " AND f.entity = ".$conf->entity;

		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;



		if ($socid){ $sql .= " AND f.fk_soc = $socid"; }

		// TIME

		$sql .= " AND f.datef >  NOW() - INTERVAL 72 HOUR  ";



		$sql .= " AND f.rowid NOT IN (SELECT fk_facture FROM  ".MAIN_DB_PREFIX."cfdimx)";



		$resql = $db->query($sql);



		if ( $resql )

		{

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



				$i = 0;

				$tot_ttc = 0;

				while ($i < $num && $i < 20)

				{

					$obj = $db->fetch_object($resql);

					print '<tr '.$bc[$var].'><td nowrap="nowrap">';

					$facturestatic->ref=$obj->ref;

					$facturestatic->id=$obj->rowid;

					$facturestatic->type=$obj->type;

					print $facturestatic->getNomUrl(1);

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

	/**

	 * Últimas 10 facturas timbradas

	 */

	if ($conf->facture->enabled && $user->rights->facture->lire)

	{

	  $sql = "

		SELECT

	              f.rowid as fac_rowid
				, f.ref
				, c.factura_id as cfdi_rowid
				, c.uuid
				, c.fecha_timbrado
				, f.datef
				, f.total_ttc
				, s.rowid as soc_rowid
				, s.nom

		FROM ".MAIN_DB_PREFIX."societe AS s,  ".MAIN_DB_PREFIX."facture AS f , ".MAIN_DB_PREFIX."cfdimx AS c

		WHERE f.rowid = c.fk_facture
		AND f.fk_soc = s.rowid
		AND c.entity_id = ".$_SESSION['dol_entity']."
		ORDER BY fecha_emision DESC LIMIT 10";

		$resql = $db->query($sql);

		if ( $resql )

		{

			$var = false;

			$num = $db->num_rows($resql);



			print '<table width="100%" class="noborder">';

			print '<tr>';

			print '<td colspan="5">Últimas 10 facturas timbradas</td></tr>';

			print '</tr>';

			print '<tr class="liste_titre">';

			print '<td>Factura</td>';

			print '<td>Fecha Factura</td>';

			print '<td>Fecha Timbrado</td>';

			print '<td>Receptor</td>';

			print '<td align="center">UUID</td>';

			print '<td align="right">Importe</td>';

			if ($num)

			{
				$companystatic=new Societe($db);
				$i = 0;

				$tot_ttc = 0;
				$facturestaticSocFT =new Societe($db);

				while ($i < $num && $i < 20)

				{

					$obj = $db->fetch_object($resql);

					print '<tr '.$bc[$var].'><td nowrap="nowrap">';

					// print '<a href="../compta/facture.php?facid='.$obj->fac_rowid.'">'.$obj->ref."</a>";
					$facturestatic->ref=$obj->ref;

					$facturestatic->id=$obj->fac_rowid;

					$facturestatic->type=$obj->type;
					
					print $facturestatic->getNomUrl(1);

					print '</td>';

					print '<td nowrap="nowrap">'.$obj->datef.'</td>';

					print '<td nowrap="nowrap">'.$obj->fecha_timbrado.'</td>';

					print '<td>';

					$facturestaticSocFT->id=$obj->soc_rowid;

					$facturestaticSocFT->nom=$obj->nom;

					$facturestaticSocFT->client=1;
					print $facturestaticSocFT->getNomUrl(1,'',16);

					print '</td>';

					print '<td nowrap="nowrap" align="center"><a href="facture.php?facid='.$obj->fac_rowid.'">'.$obj->uuid.'</a></td>';

					print '<td align="right" nowrap="nowrap">$'.number_format($obj->total_ttc,2).'</td>';

					print '</tr>';

					$tot_ttc+=$obj->total_ttc;

					$i++;

					$var=!$var;

				}

				print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';

				print '<td colspan="5" align="right">$'.price($tot_ttc).'</td>';

				print '</tr>';

			}

			else

			{
				//nuevoquery
				// **********************************
				// **********************************
				unset($sql);
				$sql  = " SELECT ";
	            $sql .= " f.ref ";
				$sql .= " ,c.uuid ";
				$sql .= " ,c.fecha_timbrado ";
				$sql .=	" ,f.datef ";
				$sql .= " ,f.total_ttc ";
				$sql .= " ,f.rowid ";
				$sql .= " ,s.nom ";
				$sql .= " ,s.rowid ";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe AS s,  ".MAIN_DB_PREFIX."facture AS f , ".MAIN_DB_PREFIX."cfdimx AS c ";
				$sql .= " WHERE f.rowid = c.fk_facture ";
				$sql .= " AND f.fk_soc = s.rowid ";
				$sql .= " AND c.entity_id = NULL ";
				//$sql .= " ORDER BY fecha_emision DESC LIMIT 10";
				 $res_entity = $db->query($sql);
				 $num_entity =  $db->num_rows($res_entity);
				if($num_entity){
					unset($sql);
					$sql  = " UPDATE ";
					$sql .= MAIN_DB_PREFIX."cfdimx ";
					$sql .= " SET ";
					$sql .= " entity_id = ".$_SESSION['dol_entity'];
					$res_entity = $db->query($sql);
					$num_entity =  $db->num_rows($res_entity);
					if($num_entity){
						Header('Location: '.$_SERVER["PHP_SELF"]);
					}

				}else{
					print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';
				}
				// **********************************
				// **********************************
			}

			print "</table><br>";

			$db->free($resql);

		}

		else

		{

			dol_print_error($db);

		}

	}



	print '</td></tr>';



	print '</table>';


	}
	else{

		dol_print_error($db);
	}


	// End of page

	llxFooter();

	$db->close();

?>
