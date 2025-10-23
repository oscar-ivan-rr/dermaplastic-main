<?php

	global $db,$conf;

	// if($conf->global->CFDIMX_HUSO_HORARIO == 0) { $zona_horaria = "America/Mexico_City"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 1) { $zona_horaria = "America/Tijuana"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 2) { $zona_horaria = "America/Chihuahua"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 3) { $zona_horaria = "America/Mexico_City"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 4) { $zona_horaria = "America/Cancun"; }

	$zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
	date_default_timezone_set($zona_horaria);

	require('../main.inc.php');
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
	require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
	require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
	require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
	require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
	require('conf.php');
	include('lib/nusoap/lib/nusoap.php');



	$langs->load('bills');
	$langs->load('companies');
	$langs->load('products');
	$langs->load('main');

	$facid=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
	$ref=GETPOST("ref");
	$object=new Facture($db);
	$paymentstatic=new Paiement($db);
	$bankaccountstatic = new Account($db);
	$form2=new Form($db);
	$object->fetch($facid,$ref);
	$facid=$object->id;
	$head = facture_prepare_head($object);

	llxHeader('','Pagos CFDI');
	dol_fiche_head($head, "tabfactpagosclt", 'CFDI', -1, 'bill');

	$action=GETPOST('action');
	$action_post=GETPOST('action_post');

	$soc = new Societe($db);
	$socid=$object->socid;
	if ($socid) $res=$soc->fetch($socid);
	$langs->load('bills');
	$langs->load('banks');
	$langs->load('companies');


	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/


	//Agregado para cancelación de complemento (Inicio)
	$form = new Form($db);
	$formconfirm='';

	// Confirmación de cancelación
	#Se tuvo que incluir la variable $action_post porque no se modificaba la variable $action en este archivo 
	#debido a la construcción del arreglo $head
	if ($action_post == 'cancel')
	{
		$id = GETPOST('id') != "" ? GETPOST('id') : GETPOST('facid');
		$pagoID = GETPOST('pagcid');
		$uuid = GETPOST('uuid');
		$rfc_emisor = GETPOST('rfc_emisor');
	    $text="¿Está seguro que desea cancelar este complemento de pago?";

	    $lista_motivos = array(
	            '01' => '01 - Comprobantes emitidos con errores con relación.',
	            '02' => '02 - Comprobantes emitidos con errores sin relación.',
	            '03' => '03 - No se llevó a cabo la operación.',
	            '04' => '04 - Operación nominativa relacionada en una factura global.'
	        );

	    $titulo = "<br><em>El Folio de Sustitución es obligatorio cuando el motivo es <b>01</b>.</em>";

	    $formquestion = array(
	        array('type' => 'hidden', 'name' => 'uuid_cancelar', 'id'=>'uuid_cancelar', 'value' => $uuid),
	        // array('type' => 'other', 'name' => 'titulo', 'id'=>'titulo', 'label' => $text),
	        array('type' => 'other', 'value' => '&nbsp;'),
	        array('type' => 'select', 'name' => 'motivo', 'id'=>'motivo', 'label' =>'Motivo', 'values' => $lista_motivos),
	        array('type' => 'text', 'name' => 'uuid_sustitucion', 'id'=>'uuid_sustitucion', 'label' =>'Folio de Sustitución' ),
	        array('type' => 'onecolumn', 'value' => $titulo)
	    );

	    $formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id='.$id.'&uuid='.$uuid.'&rfc='.$rfc_emisor.'&action_post=confirm_cancel&pago_id='.$pagoID,"¿Desea cancelar este CFDI?",'','',$formquestion, 0, 1, 270, 600);

	    print $formconfirm;
	}

	if ($action_post == 'success_msg') {
		setEventMessage("Complemento de pago cancelado correctamente.", 'mesgs');
	}

	if ($action_post == 'error_msg') {
		$msg_error = GETPOST('msg');
		setEventMessage($msg_error, 'errors');
	}

	//Accción para cancelar
	if($action_post == "confirm_cancel" && GETPOST('confirm') == "yes"){

		//Obtener fecha timbrado
		$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $conf->entity;
		//echo $sql;

		$resql=$db->query($sql);
		if ($resql){
			while ($obj = $db->fetch_object($resql)){
				$passwd_timbrado = $obj->password_timbrado_txt;
			}
		}

		//Se obtienen valores de los parámetros enviados
		$id = GETPOST('id');
		$uuid = GETPOST('uuid');
		$rfc = GETPOST('rfc');
		$pagoID = GETPOST('pago_id');

		$foliosustitucion = (GETPOST("uuid_sustitucion") != '' ? GETPOST("uuid_sustitucion") : '');

		$datos =  array(
				        "timbrado_usuario" => $rfc,
				        "timbrado_password" => $passwd_timbrado,
				        "uuid" => $uuid,
				        "motivo" => GETPOST("motivo"),
				        "foliosustitucion" => $foliosustitucion
				    );

	    // Nuevo Esquema de Cancelación 2022
	    $client = new nusoap_client($wscfdi, 'wsdl');
	    $resultado = $client->call("cancelar", $datos);

	    if($conf->global->CFDIMX_DEBUG_TIMBRADO == 1){
	    	print '<pre>Datos<br>'; print_r($datos); print '</pre>';
	    	print '<pre>Datos<br>'; print_r($resultado); print '</pre>';
	    }

	    if($resultado["return"] != ""){
	        if($resultado["return"]["httpStatusCode"] == 200){
	            $cancela_fact = 'UPDATE '.MAIN_DB_PREFIX.'paiement SET note="Complemento de pago cancelado" WHERE rowid = ' . $pagoID;
				//echo $cancela_fact."<br>";
				$r = $db->query( $cancela_fact );
				
				$cancela_update = "UPDATE  ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos SET cancelado = 1 WHERE fk_facture = " . $id;
				//echo $cancela_update."<br>";
				$r = $db->query( $cancela_update );

				//Se guarda el acuse de cancelación
				if(file_exists($conf->facture->dir_output."/".$object->ref)){

				}else{
					mkdir($conf->facture->dir_output."/".$object->ref,0700);
				}

				$file_xml = fopen ($conf->facture->dir_output."/".$object->ref."/acuse_cancelacion_Pago_".$uuid.".xml", "w");
				fwrite($file_xml,utf8_encode($resultado["return"]["acuse"]));
				fclose($file_xml);

				
				//$urltogo=DOL_URL_ROOT.'/cfdimx/pagos.php?facid='.$id;
	            //header("Location: ".$urltogo);
	            //exit;
				print '<script>location.href="?facid='.$id.'&action_post=success_msg";</script>';
	        }else{
	            $msg_cfdi_final = "Error al Cancelar el Complemento de Pagos<br><br>";
	            if($resultado["return"]["message"] != "")
	                $msg_cfdi_final .= $resultado["return"]["message"]."&nbsp;";

	            if($resultado["return"]["messageDetail"] != "")
	                $msg_cfdi_final .= $resultado["return"]["messageDetail"];

	            print '<script>location.href="?facid='.$id.'&action_post=error_msg&msg='.$msg_cfdi_final.'";</script>';
	        }
	    }else{
	        $msg_cfdi_final = "No hay respuesta para la cancelación con el SAT, favor de intentar mas tarde.";
			print '<script>location.href="?facid='.$id.'&action_post=error_msg&msg='.$msg_cfdi_final.'";</script>';
	    }		
	}
	//Agregado para cancelación de complemento (Fin)

	if(GETPOST("mesg")=="err1"){
		dol_htmloutput_errors("Error al registrar la informacion del Pago CFDI");
	}

	//$_SESSION["errorCFDIP"]="Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet labore eveniet laboriosam atque quasi sint ad animi voluptatem ut vitae, minima, recusandae velit, voluptates aspernatur quos mollitia nostrum rerum blanditiis.";
	if (GETPOST("msgerr")==1 && $_SESSION["errorCFDIP"]!="") {
	    // print '
	    // <table width="100%" style="border:0; border-radius: 10px; background-color:#d9edf7; margin-top:10px; margin-bottom:10px; padding: 5px;">
	    //     <tr>
	    //         <td style="color:black;" align="center"><strong>' . $_SESSION["errorCFDIP"] . '</strong></td>
	    //     </tr>
	    // </table>';
	    // $_SESSION["errorCFDIP"]="";

	    dol_htmloutput_errors($_SESSION["errorCFDIP"]);
	    $_SESSION["errorCFDIP"]="";
	}
	
	if(GETPOST('commit') == 1){
		setEventMessage("Complemento de Pago Timbrado correctamente.", 'mesgs');
	}

	$sqlc="SELECT uuid FROM ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture=".$facid;
	$rqc=$db->query($sqlc);
	$nrc=$db->num_rows($rqc);
	$uuid="";
	if($nrc>0){
		$rslc=$db->fetch_object($rqc);
		$uuid=$rslc->uuid;
	}

	print '<div class="error hideonsmartphone clearboth">';
		print '<strong>Para el Timbrado de Complementos de Pago se realiza desde la Ficha del Pago, para ingresar darle click a la Referencia del Pago.</strong>';
	print '</div>';

	print '<table class="noborder" width="100%">';
	// Ref
	print '<tr><td class="titlefield" >'.$langs->trans('Ref').' Factura</td>';
	print '<td colspan="3">';
	$morehtmlref='';
	print $form2->showrefnav($object,'ref','',1,'ref','ref',$morehtmlref);
	print '</td></tr>';
	// Third party
	print '<tr><td class="titlefield">'.$langs->trans('Company').'</td>';
	print '</td><td colspan="3">';
		print ' &nbsp;'.$soc->getNomUrl(1,'compta');
	print '</td></tr>';
	print '<tr><td class="titlefield"><strong>UUID de la factura</strong></td>';
	print '</td><td colspan="3">'.$uuid.'</td></tr>';
	print '</table><br>';

	if($action==""){
		print '<table class="noborder" width="100%">';
		$sign = 1;
		print '<tr class="liste_titre">';
			print '<td align="center">'.$langs->trans('Payments').'</td>';
			print '<td align="center">'.$langs->trans('Date').'</td>';
			print '<td align="center">'.$langs->trans('Type').'</td>';
			if (! empty($conf->banque->enabled)) {
				print '<td align="center">' . $langs->trans('BankAccount') . '</td>';
			}
			print '<td align="center">' . $langs->trans('Amount') . '</td>';
			print '<td align="center">Acción</td>';
			print '<td >&nbsp;</td>'; //Modificado para cancelación (se agregó id)
		print '</tr>';
		// Payments already done (from payment on this invoice)
		$sql = 'SELECT p.datep as dp, p.ref, p.num_paiement, p.rowid, p.fk_bank,';
		$sql .= ' c.code as payment_code, c.libelle as payment_label,';
		$sql .= ' pf.amount, pf.multicurrency_amount, ';
		$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_paiement as c, ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
		$sql .= ' WHERE pf.fk_facture = ' . $object->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
		$sql .= ' ORDER BY p.datep, p.tms';
		
		$result = $db->query($sql);
		
		@$num = $db->num_rows($result);
		$i = 0;

		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);
				$var = ! $var;
				print '<tr ' . $bc[$var] . '><td>';
				$paymentstatic->id = $objp->rowid;
				$paymentstatic->datepaye = $db->jdate($objp->dp);
				$paymentstatic->ref = $objp->ref;
				$paymentstatic->num_paiement = $objp->num_paiement;
				$paymentstatic->payment_code = $objp->payment_code;
				print $paymentstatic->getNomUrl(1);
				print '</td>';
				print '<td align="center">' . dol_print_date($db->jdate($objp->dp), 'day') . '</td>';
				$label = ($langs->trans("PaymentType" . $objp->payment_code) != ("PaymentType" . $objp->payment_code)) ? $langs->trans("PaymentType" . $objp->payment_code) : $objp->payment_label;
				print '<td align="center">' . $label . ' ' . $objp->num_paiement . '</td>';
				if (! empty($conf->banque->enabled)) {
					$bankaccountstatic->id = $objp->baid;
					$bankaccountstatic->ref = $objp->baref;
					$bankaccountstatic->label = $objp->baref;
					print '<td align="center">';
					if ($bankaccountstatic->id)
						print $bankaccountstatic->getNomUrl(1, 'transactions');
					print '</td>';
				}
				$pago = $objp->amount;
				if($objp->amount > $objp->multicurrency_amount){
					$pago = $objp->multicurrency_amount;
				}

				print '<td align="left">'. price($sign * $pago) . '</td>';
				$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid." AND fk_paiement=".$objp->rowid;
				//print $sql;
				$req=$db->query($sql);
				$nmr=$db->num_rows($req);
				$uuidP="";
				if($nmr>0){
					$rsl=$db->fetch_object($req);
					//Agregado para cancelación de complemento (inicio)
					$status_cancel = $rsl->cancelado;
					if ($status_cancel == 1) {
						print '<script>$( document ).ready(function() {
						    $("#td_last").after(\'<td>Estatus del comprobante</td>\');
						});
						</script>';
					}
					//Agregado para cancelación de complemento (Fin)
					if($rsl->uuid!="" && $rsl->uuid!=null){
						$uuidP=$rsl->uuid;
					}
				}
				$sqlpa="SELECT count(b.fk_paiement) as existe FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_relacion_facturas a, ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos b WHERE a.fk_facture=".$facid." AND fk_relacion_pagos=b.rowid AND b.fk_facture=0 AND b.fk_paiement=".$objp->rowid;
				//print $sqlpa;
				$rpago=$db->query($sqlpa);
				$rspago=$db->fetch_object($rpago);
				if($rspago->existe==1){
					print '<td align="right"><a href="pagosfacturas.php?id='.$objp->rowid.'">Pagos CFDI</a></td>';
				}
				else{
					if($uuidP==""){
						if($uuid!=""){
							// print '<td align="right"><a class="butAction" href="pagos.php?facid='.$object->id.'&pagcid='.$paymentstatic->id.'&action=cfdi">Genera CFDI</a></td>';
						}else{
							print '<td align="right">No se ha timbrado la Factura</td>';
						}
					}else{
						print '<td align="right"><a href="pagos.php?facid='.$object->id.'&pagcid='.$paymentstatic->id.'&action=cfdi1">'.$uuidP.'</a></td>';
					}
				}
				print '<td>&nbsp;</td>';
				//Agregado para cancelación de complemento (Inicio)
				if ($status_cancel == 1) {
					print '<td style="color: red;" align="center"><b>Cancelado</b></td>';
				}
				//Agregado para cancelación de complemento (Fin)
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr ' . $bc[false] . '><td colspan="' . $nbcols . '" class="opacitymedium">' . $langs->trans("None") . '</td><td></td><td></td></tr>';
		}
		// }
		print '</table>';
		$db->free($result);
	}

	if($action=="guardar"){
		// 	print "<pre>";
		// 	print_r($_REQUEST);
		// 	print "</pre>";
		$fechaaux=str_replace("/", "-", GETPOST('fechaPago'));
		$fechaPago=date("Y-m-d",strtotime($fechaaux));
		$fechaPago=$fechaPago." ".GETPOST('fechaPagohour').":".GETPOST("fechaPagomin").":00";
		$sql1="SELECT rowid FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".GETPOST('facid')." AND fk_paiement=".GETPOST('pagcid')." AND entity=".$conf->entity;
		$resq=$db->query($sql1);
		$numr=$db->num_rows($resq);
		if($numr==0){
			
			//print $fechaPago;
			$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
				   (fk_facture,
					fk_paiement,
					fechaPago,
					formaDePago,
					monedaP,
					TipoCambioP,
					monto,
					numOperacion,
					rfcEmisorCtaOrd,
					nomBancoOrdExt,
					ctaOrdenante,
					rfcEmisorCtaBen,
					ctaBeneficiario,
					tipoCadPago,
					certPago,
					cadPago,
					selloPago,
					entity) 
					VALUES (
					".GETPOST('facid').",
					".GETPOST('pagcid').",
					'".$fechaPago."',
					".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
					".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
					".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
					".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
					".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
					".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
					".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
					".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
					".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
					".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
					".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
					".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
					".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
					".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL').",
					".$conf->entity."
					)";
			
			//print "<br>".$sql."<br>";
			if($res=$db->query($sql)){
				$last=$db->last_insert_id(MAIN_DB_PREFIX."cfdimx_recepcion_pagos");
				$fk_recepago=$last;
				//print_r($fk_recepago);
				//$fk_recepago=1;
				
				$sql2="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
				(
				fk_recepago,
			     idDocumento,
			     serie,
			     folio,
			     monedaDR,
			     tipoCambioDR,
			     metodoDePagoDR,
			     numParcialidad,
			     impSaldoAnt,
			     impPagado,
			     impSaldoInsoluto,
			   	 entity,
			   	 equivalencia
			   	 )
				 VALUES (
						'".$fk_recepago."',
						".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
						".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
						".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
						".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
						".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
						".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
						".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
						".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
						".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
						".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL').",
						".$conf->entity.",
						".(GETPOST('equivalenciadr')!=''?"'".GETPOST('equivalenciadr')."'":'NULL')."
				 )";
				
				//print "<br>".$sql2."<br>";
				$res2=$db->query($sql2);
				print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
			}else{
				print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."&mesg=err1'</script>";
			}
		}else{
			$resultado=$db->fetch_object($resq);
			$sql3="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
					SET
					fechaPago='".$fechaPago."',
					formaDePago=".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
					monedaP=".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
					TipoCambioP=".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
					monto=".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
					numOperacion=".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
					rfcEmisorCtaOrd=".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
					nomBancoOrdExt=".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
					ctaOrdenante=".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
					rfcEmisorCtaBen=".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
					ctaBeneficiario=".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
					tipoCadPago=".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
					certPago=".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
					cadPago=".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
					selloPago=".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL')."
					WHERE rowid=".$resultado->rowid;		
			
			//print "<br>".$sql3;
			if($res=$db->query($sql3)){
				$sql4="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
						SET
						 idDocumento=".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
					     serie=".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
					     folio=".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
					     monedaDR=".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
					     tipoCambioDR=".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
					     metodoDePagoDR=".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
					     numParcialidad=".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
					     impSaldoAnt=".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
					     impPagado=".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
					     impSaldoInsoluto=".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL').",
					     equivalencia=".(GETPOST('equivalenciadr')!=''?"'".GETPOST('equivalenciadr')."'":'NULL')."
						WHERE fk_recepago=".$resultado->rowid;
				//print "<br>".$sql4;
				$res2=$db->query($sql4);
				print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
			}else{
				print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."&mesg=err1'</script>";
			}
			
		}
	}

	if($action=="cfdi"){
		$obpag = new Paiement($db);
		$obpag->fetch(GETPOST("pagcid"));
		$referencia_pago = $obpag->ref; #variable usada para el envio de correo
		print '<table class="noborder centpercent">'."\n";
		
		// Ref
		print '<tr><td class="titlefield">'.$langs->trans('Ref').' Pago</td><td colspan="3">';
		print $form->showrefnav($obpag, 'ref', $linkback, 0, 'ref', 'ref', '');
		print '</td></tr>';
		
		// Date payment
		print '<tr><td>'.$langs->trans("Date").'</td><td colspan="3">';
		print $form->editfieldval("Date",'datep',$obpag->date,$obpag,$user->rights->facture->paiement,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
		print '</td></tr>';
		
		// Payment type (VIR, LIQ, ...)
		$labeltype=$langs->trans("PaymentType".$obpag->type_code)!=("PaymentType".$obpag->type_code)?$langs->trans("PaymentType".$obpag->type_code):$obpag->type_libelle;
		print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$labeltype.'</td></tr>';
		
		// Payment numero
		print '<tr><td>'.$langs->trans("Number").'</td><td colspan="3">';
		print $form->editfieldval("Numero",'num_paiement',$obpag->numero,$obpag,$obpag->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
		print '</td></tr>';
		
		// Amount
		// print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($obpag->montant,'',$langs,0,0,-1,$conf->currency).'</td></tr>';
		$amount_header = $obpag->amount;
		$moneda_header = "MXN";
		$monedaa = "MXN";
		if($conf->global->MAIN_MODULE_MULTICURRENCY){
			if($obpag->amount > $obpag->multicurrency_amount){
				$amount_header = $obpag->multicurrency_amount;
				$moneda_header = "USD";
				$monedaa = "USD";
			}
		}

		print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($amount_header,'',$langs,0,0,-1,$conf->currency).'</td></tr>';
		
		// Note
		print '<tr><td class="tdtop">'.$langs->trans("Note").'</td><td colspan="3">';
		print $form->editfieldval("Note",'note',$obpag->note,$obpag,$user->rights->facture->paiement,'textarea');
		print '</td></tr>';
		
		$disable_delete = 0;
		// Bank account
		if (! empty($conf->banque->enabled))
		{
			if ($obpag->fk_account > 0)
			{
				$bankline=new AccountLine($db);
				$bankline->fetch($obpag->bank_line);
				if ($bankline->rappro)
				{
					$disable_delete = 1;
					$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
				}
		
				print '<tr>';
				print '<td>'.$langs->trans('BankTransactionLine').'</td>';
				print '<td colspan="3">';
				print $bankline->getNomUrl(1,0,'showconciliated');
				print '</td>';
				print '</tr>';
		
				print '<tr>';
				print '<td>'.$langs->trans('BankAccount').'</td>';
				print '<td colspan="3">';
				$accountstatic=new Account($db);
				$accountstatic->fetch($bankline->fk_account);
				$monedaa=$accountstatic->currency_code;
				print $accountstatic->getNomUrl(1);
				print '</td>';
				print '</tr>';
		
				if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0)
				{
					dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
					$bordereau = new RemiseCheque($db);
					$bordereau->fetch($bankline->fk_bordereau);
		
					print '<tr>';
					print '<td>'.$langs->trans('CheckReceipt').'</td>';
					print '<td colspan="3">';
					print $bordereau->getNomUrl(1);
					print '</td>';
					print '</tr>';
				}
			}
		}
		print '</table>';
		
		print '<br>';
		
		$datep="";
		$formpago="";
		$monedapago="";
		$tipocambio="";
		$numparcialidaddr="";
		$equivalenciadr="";
		
		if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
			$tipocambio=1;
			$numparcialidaddr=1;
			$equivalenciadr = 1;
		}

		if($conf->global->MAIN_MODULE_MULTICURRENCY){
			if($moneda_header == "USD"){
				$tipocambio = $object->array_options["options_tipodecambiocfdi"];
			}
		}
		$montop = $amount_header;
		
		$numoperacion="";
		$rfcemisorctaorigen="";
		$nombancoordenante="";
		$ctaordenante="";
		$rfcemisorctabeneficiario="";
		$ctabeneficiario="";
		$tipocadenapago="";
		$certificadopago="";
		$cadenaoriginal="";
		$sellopago="";
		
		$idDocumento="";
		$monedaDR="";
		$metodoPDR="";
		$docSerie="";
		$docFolio="";
		$tipocambiodr="";
		
		$impSaldoAnterior="";
		$impPagadodr="";
		$impSaldoInsoluto="";

		
		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".GETPOST("facid")." AND fk_paiement=".GETPOST("pagcid");
		//print $sql;
		$req=$db->query($sql);
		$nmr=$db->num_rows($req);
		if($nmr>0){
			$rsl=$db->fetch_object($req);
			$datep=strtotime($rsl->fechaPago);
			$formpago=$rsl->formaDePago;
			$monedapago=$rsl->monedaP;
			$tipocambio=$rsl->TipoCambioP;
			$montop=$rsl->monto;
			$numoperacion=$rsl->numOperacion;
			$rfcemisorctaorigen=$rsl->rfcEmisorCtaOrd;
			$nombancoordenante=$rsl->nomBancoOrdExt;
			$ctaordenante=$rsl->ctaOrdenante;
			$rfcemisorctabeneficiario=$rsl->rfcEmisorCtaBen;
			$ctabeneficiario=$rsl->ctaBeneficiario;
			$tipocadenapago=$rsl->tipoCadPago;
			$certificadopago=$rsl->certPago;
			$cadenaoriginal=$rsl->cadPago;
			$sellopago=$rsl->selloPago;
			$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$rsl->rowid;
			$req=$db->query($sql);
			$rsl=$db->fetch_object($req);
			$idDocumento=$rsl->idDocumento;
			$monedaDR=$rsl->monedaDR;
			$metodoPDR=$rsl->metodoDePagoDR;
			$docSerie=$rsl->serie;
			$docFolio=$rsl->folio;
			$tipocambiodr=$rsl->tipoCambioDR;
			$numparcialidaddr=$rsl->numParcialidad;
			$impSaldoAnterior=$rsl->impSaldoAnt;
			$impPagadodr=$rsl->impPagado;
			$impSaldoInsoluto=$rsl->impSaldoInsoluto;
			$equivalenciadr=$rsl->equivalencia;
		}else{
	        $invoice=new Facture($db);
	        $invoice->fetch(GETPOST("facid"));
	        $extrae=explode("-", $invoice->ref);
	        $alc=0;
	        if(count($extrae)>1){
	            $docSerie=$extrae["0"];
	            $docFolio=$extrae["1"];
	            $alc=1;
	        }else{
	            $docFolio=$extrae["0"];
	        }
	        // $impPagadodr=str_replace(array(",","-"), "",number_format($obpag->montant,2));
	        $impPagadodr=str_replace(array(",","-"), "",number_format($amount_header,2));

	        if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0 && $numparcialidaddr == 1){
	        	$impSaldoAnterior = str_replace(array(",","-"), "",number_format($amount_header,2));
	        	$impSaldoInsoluto = $impPagadodr - $impSaldoAnterior;
	        	$impSaldoInsoluto = str_replace(array(",","-"), "",number_format($impSaldoInsoluto,2));
	        }
	    }

		print '<form method="POST" action="pagos.php?action=guardar">';
		print '<input type="hidden" name="facid" value="'.GETPOST("facid").'">';
		print '<input type="hidden" name="pagcid" value="'.GETPOST("pagcid").'">';
		print '<table class="noborder">'."\n";
		$formp = new Form($db);
		print '<thead>';
			print '<tr class="list_titre">';
				print '<td colspan="4" align="center" ><strong>Timbrado de Pagos CFDI</strong></td>';
				//print '<tr><td class="titlefield" colspan="4" align="center"><strong>Timbrado de Pagos CFDI</strong> <button class="button" onclick="mosocultar()">Mostrar/Ocultar opcionales</button></td>';
			print '</tr>';
		print '</thead>';
		if($datep==""){
			$datep=$obpag->date;
		}

		//Forma de pago
		if($formpago==""){

			$sql="SELECT accountancy_code FROM ".MAIN_DB_PREFIX."c_paiement WHERE code='".$object->mode_reglement_code."'";
			//print $sql;
			$req=$db->query($sql);
			
			if($db->num_rows($req) > 0){
				$res=$db->fetch_object($req);
				$formpago=$res->accountancy_code;
			}else{
				$formpago="-1";
			}	
		}
		$sql='SELECT id, entity, code, libelle AS label, type, active, accountancy_code, module, position FROM '.MAIN_DB_PREFIX.'c_paiement WHERE active=1 AND (accountancy_code!="NULL" AND accountancy_code!="")';
		//echo $sql;
		$req=$db->query($sql);
		
		if ($db->num_rows($req) > 0) {
			$select_formas = '<select name="formpago">';
			if ($formpago != "-1") {
				$select_formas .= '<option value="-1">&nbsp;</option>';
			}else {
				$select_formas .= '<option value="-1" selected>&nbsp;</option>';
			}
			while($rs=$db->fetch_object($req)){
				$label=($langs->transnoentitiesnoconv("PaymentTypeShort".$rs->code)!=("PaymentTypeShort".$rs->code)?$langs->transnoentitiesnoconv("PaymentTypeShort".$rs->code):($rs->label!='-'?$rs->label:''));
				if ($formpago == $rs->accountancy_code) {
					$select_formas .= '<option value="'.$rs->accountancy_code.'" selected>'.$label.'</option>';
				}
				else {
					$select_formas .= '<option value="'.$rs->accountancy_code.'">'.$label.'</option>';
				}
			}
			$select_formas .= '</select>';
		}else {
			$select_formas = '<label style="color:red;">Debe <a href="'.DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=formaspago" title="Registrar">registrar</a> los códigos del catálgo del SAT para las formas de pago en la configuración del módulo.</label>';
		}
		//Forma de pago

		if($monedapago==""){
			if($monedaa){
				$monedapago=$monedaa;
			}
		}
		if($montop==""){
			$montop=str_replace(",","",number_format($obpag->montant,2));
		}else{
			$montop=str_replace(",","",number_format($montop,2));
		}

		if($idDocumento=="" && $monedaDR=="" && $metodoPDR==""){
			
			#Obtener UUID y la divisa del comprobante
			$sql='SELECT cfdi.uuid, cfdi.divisa FROM '.MAIN_DB_PREFIX.'cfdimx cfdi WHERE cfdi.fk_facture='.GETPOST("facid");
			//print $sql;
			$rq=$db->query($sql);
			if ($db->num_rows($rq) > 0) {
				$rs=$db->fetch_object($rq);
				$monedaDR=$rs->divisa;
				$idDocumento=$rs->uuid;
			}
			
			$sqlm="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'formpagcfdi'";
			$resqlv=$db->query($sqlm);
			if( $db->num_rows($resqlv) > 0 ){
				$sqlv="SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".GETPOST("facid");
				$rv=$db->query($sqlv);
				$vrs=$db->fetch_object($rv);
				$metodoPDR = $vrs->formpagcfdi;
			}else{
				$metodoPDR = "";
			}
		}
		
		print '<tr><td class="titlefield"><strong>Fecha de Pago</strong></td>';
		print '<td>';
		print '<input type="hidden" name="mos" id="mos" value="0">';
		$form->select_date($datep,'fechaPago',1,1,0,'nfechaPago');
		print '</td>';
		
		if($formpago=="-1"){
			$forma_msg = '<label style="color: red;">No ha seleccionado una forma de pago</label>';
		}
		
		print '<td class="titlefield"><strong>Forma de Pago</strong></td>';
		//print '<td><input type="text" name="formpago" value="'.$formpago.'" ></td></tr>';
		print '<td>'.$select_formas.'<br>'.$forma_msg.'</td></tr>';

		print '<tr><td class="titlefield"><strong>Moneda del Pago</strong></td>';
		print '<td><input type="text" name="monedapago" value="'.$monedapago.'" ></td>';
		
		print '<td class="titlefield"><strong>Monto</strong></td>';
		print '<td><input type="text" name="montop" value="'.$montop.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Tipo de cambio del Pago</td>';
		print '<td><input type="text" name="tipocambio" value="'.$tipocambio.'" ></td>';
		
		print '<td class="titlefield">Numero de operacion</td>';
		print '<td><input type="text" name="numoperacion" value="'.$numoperacion.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">RFC emisor cuenta ordenante</td>';
		print '<td><input type="text" name="rfcemisorctaorigen" value="'.$rfcemisorctaorigen.'" ></td>';
		
		print '<td class="titlefield">Nombre del banco ordenante (Extranjero)</td>';
		print '<td><input type="text" name="nombancoordenante" value="'.$nombancoordenante.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Cuenta Ordenante</td>';
		print '<td><input type="text" name="ctaordenante" value="'.$ctaordenante.'" ></td>';
		
		print '<td class="titlefield">RFC emisor cuenta beneficiario</td>';
		print '<td><input type="text" name="rfcemisorctabeneficiario" value="'.$rfcemisorctabeneficiario.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Cuenta beneficiario</td>';
		print '<td><input type="text" name="ctabeneficiario" value="'.$ctabeneficiario.'" ></td>';
		
		print '<td class="titlefield">Tipo cadena de pago</td>';
		print '<td><input type="text" name="tipocadenapago" value="'.$tipocadenapago.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Certificado del pago</td>';
		print '<td colspan="3"><textarea name="certificadopago" rows="4" cols="60">'.$certificadopago.'</textarea></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Cadena Original del comprobante pago</td>';
		print '<td colspan="3"><textarea name="cadenaoriginal" rows="4" cols="60">'.$cadenaoriginal.'</textarea></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Sello Pago</td>';
		print '<td colspan="3"><textarea name="sellopago" rows="4" cols="60">'.$sellopago.'</textarea></td></tr>';
		////////////////////////////////////////////////////////////////
		print '<tr><td class="titlefield" colspan="4" align="center" style="background-color:#dcdcdf;"><strong>Documento relacionado</strong></td></tr>';
		
		print '<tr><td class="titlefield"><strong>ID Documento</strong></td>';
		print '<td colspan="3"><input type="text" name="idDocumento" value="'.$idDocumento.'" size="40"></td></tr>';
		
		print '<tr><td class="titlefield"><strong>Moneda del Documento Relacionado</strong></td>';
		print '<td><input type="text" name="monedaDR" value="'.$monedaDR.'" ></td>';
		
		print '<td class="titlefield"><strong>Metodo de Pago Documento Relacionado</strong></td>';
		print '<td><input type="text" name="metodoPDR" value="'.$metodoPDR.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Serie</td>';
		print '<td><input type="text" name="docSerie" value="'.$docSerie.'" ></td>';
		
		print '<td class="titlefield">Folio</td>';
		print '<td><input type="text" name="docFolio" value="'.$docFolio.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Tipo Cambio Documento Relacionado</td>';
		print '<td><input type="text" name="tipocambiodr" value="'.$tipocambiodr.'" ></td>';
		
		print '<td class="titlefield">Numero de Parcialidad</td>';
		print '<td><input type="text" name="numparcialidaddr" value="'.$numparcialidaddr.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Importe Saldo Anterior</td>';
		print '<td><input type="text" name="impSaldoAnterior" value="'.$impSaldoAnterior.'" ></td>';
		
		print '<td class="titlefield">Importe Pagado</td>';
		print '<td><input type="text" name="impPagadodr" value="'.$impPagadodr.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Importe Saldo Insoluto</td>';
		print '<td><input type="text" name="impSaldoInsoluto" value="'.$impSaldoInsoluto.'" ></td>';

		print '<td class="titlefield">Equivalencia DR</td>';
		print '<td><input type="text" name="equivalenciadr" value="'.$equivalenciadr.'" ></td></tr>';
		
		print '<tr><td class="titlefield" colspan="4" align="center"><input type="submit" name="guardar" value="Guardar informacion" class="butAction"></td></tr>';
		
		print '</table>';
		print '</form>';

		$client = new nusoap_client($wscfdi, 'wsdl');
		$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));
		$status_clt = $result["return"]["status_cliente_id"];
		$status_clt_desc = $result["return"]["status_cliente_desc"];
		$folios_timbrados = $result["return"]["folios_timbrados"];
		$folios_adquiridos = $result["return"]["folios_adquiridos"];
		$folios_disponibles = $result["return"]["folios_disponibles"];
		$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $_SESSION['dol_entity']);
		if ($resql){
			$conf_num = $db->num_rows($resql);
			$i = 0;
			if ($conf_num){
				while ($i < $conf_num){
					$obj = $db->fetch_object($resql);
					if ($obj){
						$status_conf = $obj->status_conf;
						$modo_timbrado = $obj->modo_timbrado;
						$passwd_timbrado = $obj->password_timbrado_txt;
					}
					$i++;
				}
			}
		}

		print '<div align="center">';
		if($nmr==0){
			print '<label style="font-size:16px; font-weight: bold;">Es necesario que guarde la información del Pago para poder timbrar.</label>';
		}else{
			if($folios_disponibles>0 )
			{
				print '<br><div align="right"><a class="butAction" href="pagos/generaCFDI.php?facid='.GETPOST("facid").'&pagcid='.GETPOST("pagcid").'&action=generaCFDI">Generar CFDI</a></div>';
			}
		}
		print '</div><br>';

		print '<div class="fichecenter">';
			print '<div class="fichehalfright">';
				print '<table width="100%" style="height:50px;border: solid 1px #990000;border-collapse: collapse;">';
				print '<tr style="font-weight: bold;" class="liste_titre" style="background: #990000; color: white;"><td style="border: solid 1px #990000; background-color: #990000; color: white;" colspan="2" align="center">Datos de Timbrado</td></tr>';
			    $modo_timbrado_desc = ($modo_timbrado == 1) ? "Producción" : "Pruebas";
			    print '<tr><td style="font-weight: bold;">Modo de timbrado activo: </td><td>'.$modo_timbrado_desc.'</td></tr>';
			    print '<tr><td style="font-weight: bold;">Versión de CFDI Activa: </td><td>' . $conf->global->CFDIMX_VERSION_SAT . '</td></tr>';
			    print '<tr><td style="font-weight: bold;">Folios Disponibles: </td><td>'.$folios_disponibles.'</td></tr>';
			    print '<tr><td style="font-weight: bold;">Folios Timbrados: </td><td>'.$folios_timbrados.'</td></tr>';
			    print '</table>';
		    print '</div>';
	    print '</div>';
	}

	if($action=="cfdi1"){
		$obpag = new Paiement($db);
		$obpag->fetch(GETPOST("pagcid"));
		$referencia_pago = $obpag->ref; #variable usada para el envio de correo

		print '<table class="noborder centpercent">';

		// Ref
		print '<tr><td class="titlefield">'.$langs->trans('Ref').' Pago</td><td colspan="3">';
		print $form->showrefnav($obpag, 'ref', $linkback, 0, 'ref', 'ref', '');
		print '</td></tr>';

		// Date payment
		print '<tr><td>'.$langs->trans("Date").'</td><td colspan="3">';
		print $form->editfieldval("Date",'datep',$obpag->date,$obpag,$user->rights->facture->paiement,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
		print '</td></tr>';

		// Payment type (VIR, LIQ, ...)
		$labeltype=$langs->trans("PaymentType".$obpag->mode_reglement_code)!=("PaymentType".$obpag->mode_reglement_code)?$langs->trans("PaymentType".$obpag->mode_reglement_code):$obpag->type_libelle;
		print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$labeltype.'</td></tr>';

		// Payment numero
		print '<tr><td>'.$langs->trans("Number").'</td><td colspan="3">';
		print $form->editfieldval("Numero",'num_paiement',$obpag->numero,$obpag,$obpag->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
		print '</td></tr>';

		// Amount
		print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">$'.number_format($obpag->montant,2).'</td></tr>';

		// Note
		print '<tr><td class="tdtop">'.$langs->trans("Note").'</td><td colspan="3">';
		print $form->editfieldval("Note",'note',$obpag->note,$obpag,$user->rights->facture->paiement,'textarea');
		print '</td></tr>';

		$disable_delete = 0;
		// Bank account
		if (! empty($conf->banque->enabled))
		{
			if ($obpag->fk_account > 0)
			{
				$bankline=new AccountLine($db);
				$bankline->fetch($obpag->bank_line);
				if ($bankline->rappro)
				{
					$disable_delete = 1;
					$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
				}

				print '<tr>';
				print '<td>'.$langs->trans('BankTransactionLine').'</td>';
				print '<td colspan="3">';
				print $bankline->getNomUrl(1,0,'showconciliated');
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>'.$langs->trans('BankAccount').'</td>';
				print '<td colspan="3">';
				$accountstatic=new Account($db);
				$accountstatic->fetch($bankline->fk_account);
				$monedaa=$accountstatic->currency_code;
				print $accountstatic->getNomUrl(1);
				print '</td>';
				print '</tr>';

				if ($object->mode_reglement_code == 'CHQ' && $bankline->fk_bordereau > 0)
				{
					dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
					$bordereau = new RemiseCheque($db);
					$bordereau->fetch($bankline->fk_bordereau);

					print '<tr>';
					print '<td>'.$langs->trans('CheckReceipt').'</td>';
					print '<td colspan="3">';
					print $bordereau->getNomUrl(1);
					print '</td>';
					print '</tr>';
				}
			}
		}
		print '</table>';

		print '<br>';

		$datep="";
		$formpago="";
		$monedapago="";
		$tipocambio="";
		$montop="";

		$numoperacion="";
		$rfcemisorctaorigen="";
		$nombancoordenante="";
		$ctaordenante="";
		$rfcemisorctabeneficiario="";
		$ctabeneficiario="";
		$tipocadenapago="";
		$certificadopago="";
		$cadenaoriginal="";
		$sellopago="";

		$idDocumento="";
		$monedaDR="";
		$metodoPDR="";
		$docSerie="";
		$docFolio="";
		$tipocambiodr="";
		$numparcialidaddr="";
		$impSaldoAnterior="";
		$impPagadodr="";
		$impSaldoInsoluto="";
		$equivalenciadr="";
		$uuidP="";
		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".GETPOST("facid")." AND fk_paiement=".GETPOST("pagcid");
		//print $sql;
		$req=$db->query($sql);
		$nmr=$db->num_rows($req);
		if($nmr>0){
			$rsl=$db->fetch_object($req);
			$datep=strtotime($rsl->fechaPago);
			$formpago=$rsl->formaDePago;
			$monedapago=$rsl->monedaP;
			$tipocambio=$rsl->TipoCambioP;
			$montop=$rsl->monto;
			$numoperacion=$rsl->numOperacion;
			$rfcemisorctaorigen=$rsl->rfcEmisorCtaOrd;
			$nombancoordenante=$rsl->nomBancoOrdExt;
			$ctaordenante=$rsl->ctaOrdenante;
			$rfcemisorctabeneficiario=$rsl->rfcEmisorCtaBen;
			$ctabeneficiario=$rsl->ctaBeneficiario;
			$tipocadenapago=$rsl->tipoCadPago;
			$certificadopago=$rsl->certPago;
			$cadenaoriginal=$rsl->cadPago;
			$sellopago=$rsl->selloPago;
			$uuidP=$rsl->uuid;
			$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$rsl->rowid;
			$req=$db->query($sql);
			$rsl=$db->fetch_object($req);
			$idDocumento=$rsl->idDocumento;
			$monedaDR=$rsl->monedaDR;
			$metodoPDR=$rsl->metodoDePagoDR;
			$docSerie=$rsl->serie;
			$docFolio=$rsl->folio;
			$tipocambiodr=$rsl->tipoCambioDR;
			$numparcialidaddr=$rsl->numParcialidad;
			$impSaldoAnterior=$rsl->impSaldoAnt;
			$impPagadodr=$rsl->impPagado;
			$impSaldoInsoluto=$rsl->impSaldoInsoluto;
			$equivalenciadr=$rs1->equivalencia;
		}
		
		print '<input type="hidden" name="facid" value="'.GETPOST("facid").'">';
		print '<input type="hidden" name="pagcid" value="'.GETPOST("pagcid").'">';
		print '<table class="noborder centpercent">'."\n";
		$formp = new Form($db);
		print '<tr><td colspan="4" align="center" style="background-color:#dcdcdf;"><strong>Timbrado de Pagos CFDI</strong></td></tr>';

		print '<tr><td class="titlefield"><strong>Fecha de Pago</strong></td>';
		print '<td>';
		print '</td>';

		print '<td class="titlefield"><strong>Forma de Pago</strong></td>';
		print '<td>'.$formpago.'</td></tr>';

		print '<tr><td class="titlefield"><strong>Moneda del Pago</strong></td>';
		print '<td>'.$monedapago.'</td>';

		print '<td class="titlefield"><strong>Monto</strong></td>';
		print '<td>$'.number_format($montop,2).'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Tipo de cambio del Pago</td>';
		print '<td>'.$tipocambio.'</td>';

		print '<td class="titlefield">Número de operacion</td>';
		print '<td>'.$numoperacion.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">RFC emisor cuenta ordenante</td>';
		print '<td>'.$rfcemisorctaorigen.'</td>';

		print '<td class="titlefield">Nombre del banco ordenante (Extranjero)</td>';
		print '<td>'.$nombancoordenante.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Cuenta Ordenante</td>';
		print '<td>'.$ctaordenante.'</td>';

		print '<td class="titlefield">RFC emisor cuenta beneficiario</td>';
		print '<td>'.$rfcemisorctabeneficiario.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Cuenta beneficiario</td>';
		print '<td>'.$ctabeneficiario.'</td>';

		print '<td class="titlefield">Tipo cadena de pago</td>';
		print '<td>'.$tipocadenapago.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Certificado del pago</td>';
		print '<td colspan="3">'.$certificadopago.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Cadena Original del comprobante pago</td>';
		print '<td colspan="3">'.$cadenaoriginal.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Sello Pago</td>';
		print '<td colspan="3">'.$sellopago.'</td></tr>';
		////////////////////////////////////////////////////////////////
		print '<tr><td style="background-color:#dcdcdf;" colspan="4" align="center"><strong>Documento relacionado</strong></td></tr>';

		print '<tr><td class="titlefield"><strong>ID Documento</strong></td>';
		print '<td colspan="3">'.$idDocumento.'</td></tr>';

		print '<tr><td class="titlefield"><strong>Moneda del Documento Relacionado</strong></td>';
		print '<td>'.$monedaDR.'</td>';

		print '<td class="titlefield"><strong>Metodo de Pago Documento Relacionado</strong></td>';
		print '<td>'.$metodoPDR.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Serie</td>';
		print '<td>'.$docSerie.'</td>';

		print '<td class="titlefield">Folio</td>';
		print '<td>'.$docFolio.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Tipo Cambio Documento Relacionado</td>';
		print '<td>'.$tipocambiodr.'</td>';

		print '<td class="titlefield">Numero de Parcialidad</td>';
		print '<td>'.$numparcialidaddr.'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Importe Saldo Anterior</td>';
		print '<td>'.number_format($impSaldoAnterior,2).'</td>';

		print '<td class="titlefield">Importe Pagado</td>';
		print '<td>'.number_format($impPagadodr,2).'</td></tr>';

		print '<tr class="oculta"><td class="titlefield">Importe Saldo Insoluto</td>';
		print '<td>'.number_format($impSaldoInsoluto,2).'</td>';

		print '<td class="titlefield">Equivalencia DR</td>';
		print '<td>'.$equivalenciadr.'</td></tr>';
		print '</table>';
		$client = new nusoap_client($wscfdi, 'wsdl');
		$result = $client->call('validaCliente', array("rfc"=>$conf->global->MAIN_INFO_SIREN) );
		$status_clt = $result["return"]["status_cliente_id"];
		$status_clt_desc = $result["return"]["status_cliente_desc"];
		$folios_timbrados = $result["return"]["folios_timbrados"];
		$folios_adquiridos = $result["return"]["folios_adquiridos"];
		$folios_disponibles = $result["return"]["folios_disponibles"];
		
		$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $_SESSION['dol_entity']);
		if ($resql){
			$conf_num = $db->num_rows($resql);
			$i = 0;
			if ($conf_num){
				while ($i < $conf_num){
					$obj = $db->fetch_object($resql);
					if ($obj){
						$status_conf = $obj->status_conf;
						$modo_timbrado = $obj->modo_timbrado;
						$passwd_timbrado = $obj->password_timbrado_txt;
					}
					$i++;
				}
			}
		}

		dol_fiche_end();

		if (GETPOST('action2') != 'presend' && GETPOST('action2') != 'send') {
	    	
			print '<div class="tabsAction" width="100%">';

				// Send by mail
		        if ($user->rights->facture->invoice_advance->send) {
		            print '<div class="inline-block divButAction" style="height: 20px;"><a class="butAction" href="'.DOL_URL_ROOT.'/cfdimx/facture.php?id='.GETPOST('facid').'&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a></div>';
		        }

				if ($user->rights->cfdimx->create == 1) {
					$out.= '
					<div class="inline-block divButAction">
						<form action="pagos/regenpdf.php?facid='.$_REQUEST["facid"].'" method="post" >
							<input type="hidden" name="pagcid" value="'.$_REQUEST["pagcid"].'">
							<input type="hidden" name="rfc_emisor" value="'.$conf->global->MAIN_INFO_SIREN.'">
							<input type="submit" class="butAction" name="regenerarpdf" id="rgenerarpdf" value="Regenerar PDF" style="height: 36px;font-size: 10pt; border: 0;">
						</form>
					</div>';
			    }

				//Agregado para cancelación de complemento (Inicio)
			    if ($user->rights->cfdimx->delete == 1) {
					$sql='SELECT cancelado FROM '.MAIN_DB_PREFIX.'cfdimx_recepcion_pagos WHERE fk_facture='.GETPOST('facid').' AND fk_paiement='.GETPOST('pagcid');
					//echo $sql;

					$resql=$db->query($sql);
					if ($db->num_rows($resql) > 0) {
						while ($res = $db->fetch_object($resql)) {
							$status_cancel = $res->cancelado;
						}
					}

					if ($status_cancel == 0) {
						$out.= '
						<div class="inline-block divButAction">
							<form method="post">
								<input type="hidden" name="action_post" value="cancel">
								<input type="hidden" name="uuid" value="'.$uuidP.'">
								<input type="hidden" name="rfc_emisor" value="'.$conf->global->MAIN_INFO_SIREN.'">
								<input type="submit" class="butActionDelete" name="cancelaCFDIbtn" value="Cancelar CFDI" style="height: 36px;font-size: 10pt; border: 0;">
							</form>
						</div>';
					}
				}

				//Agregado para cancelación de complemento (Fin)
				print $out;
				$out="";
			print '</div>';


			print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
					print '<table width="100%" style="height:50px;border: solid 1px #990000;border-collapse: collapse;">';
					print '<tr style="font-weight: bold;" class="liste_titre" style="background: #990000; color: white;"><td style="border: solid 1px #990000; background-color: #990000; color: white;" colspan="2" align="center">Datos de Timbrado</td></tr>';
				    print '<tr><td style="font-weight: bold;">Pago Timbrado - UUID: </td><td>'.$uuidP.'</td></tr>';
				    $modo_timbrado_desc = ($modo_timbrado == 1) ? "Producción" : "Pruebas";
				    print '<tr><td style="font-weight: bold;">Modo de timbrado activo: </td><td>'.$modo_timbrado_desc.'</td></tr>';
				    print '<tr><td style="font-weight: bold;">Folios Disponibles: </td><td>'.$folios_disponibles.'</td></tr>';
				    print '<tr><td style="font-weight: bold;">Folios Timbrados: </td><td>'.$folios_timbrados.'</td></tr>';
				    print '</table>';
			    print '</div>';


			    print '<div class="fichehalfleft" style="margin-left: 15px;">';
				#Listado de archivos#
		        $filedir=DOL_DATA_ROOT.'/facture/'.$object->ref.'/';
		        $file_list=dol_dir_list($filedir,'files',0,'','\.meta$','date',SORT_DESC);	        

		        // Loop on each file found
		        if (is_array($file_list))
		        {
		            foreach($file_list as $file)
		            {
		            	if(strpos($file["name"], $uuidP)){

			                $aux_ext = explode(".", $file['name']);
			                $ext = $aux_ext[1];

			                if (in_array($ext, array("pdf", "png", "jpg", "jpge"))){
			                    $out_lupa = '<a class="pictopreview documentpreview" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&amp;attachment=0&amp;file='.$file['level1name'].'/'.$file['name'].'" mime="application/pdf" target="_blank"><span class="fa fa-search-plus" style="color: gray"></span></a>';
			                }
			                else {
			                    $out_lupa = "";
			                }
			                
			                $out_files.= '<tr>';
			                //Ruta antigua para eliminar
			                //<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$id.'&amp;action=remove_file&amp;file='.$file['name'].'&amp;file_dir='.$filedir.'&amp;entity='.$conf->entity.'"><img src="'.DOL_URL_ROOT.'/theme/eldy/img/delete.png" alt="" title="Eliminar" class="inline-block"></a>
			                $out_files.='
			                    <td class="minwidth200">
			                        <a class="documentdownload paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&file='.$file['level1name'].'/'.$file['name'].'" target="_blank"><i class="fa fa-file-pdf-o paddingright"></i>'.$file['name'].'</a>
			                        '.$out_lupa.'
			                    </td>
			                    <td align="right" class="nowrap">'.filesize($file['fullname']).' Bytes</td>
			                    <td align="right" class="nowrap">'.dol_print_date($file['date'], "%H:%M %d/%m/%Y").'</td>
			                    <!--<td align="right">
			                        <a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$id.'&amp;action=remove_file&amp;file='.$file['level1name'].'/'.$file['name'].'&amp;entity='.$conf->entity.'">
			                            <img src="'.DOL_URL_ROOT.'/theme/eldy/img/delete.png" alt="" title="Eliminar" class="inline-block">
			                        </a>
			                    </td>-->';
			                $out_files.= '</tr>';
			            }
		            }
		        }

		        print  '<div>
				        <table class="centpercent notopnoleftnoright" style="margin-bottom: 2px;">
		                    <tbody>
		                        <tr>
		                            <td class="nobordernopadding" valign="middle">
		                                <div class="" style="color: rgb(110,80,20);">Archivos vinculados</div>
		                            </td>
		                        </tr>
		                    </tbody>
		                </table>
		                <div class="div-table-responsive-no-min">
		                    <table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">
		                        <tbody>
			                        <tr class="liste_titre">
		                                <td colspan="5">
		                                    &nbsp;
		                                </td>
		                            </tr>
		                            '.$out_files.'
		                        </tbody>
		                    </table>
		                </div>
		            </div>';
		        print '</div><br><br>';
			print '</div>';
		}
	}

	llxFooter();
	$db->close();
?>