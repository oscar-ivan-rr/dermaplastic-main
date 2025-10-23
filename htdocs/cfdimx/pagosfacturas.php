<?php
	require('../main.inc.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
	require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	// require('conf.php');
	require('class/pagos.class.php');
	include('lib/nusoap/lib/nusoap.php');
	require_once DOL_DOCUMENT_ROOT.'/cfdimx/js/pagos.js.php';

	global $db, $conf, $user;

	$wscfdi       = $conf->global->MAIN_MODULE_CFDIMX_WS;
	$zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

	$langs->load('bills');
	$langs->load('companies');
	$langs->load('products');
	$langs->load('main');

	$id          = GETPOST('id','int');
	$ref         = GETPOST('ref', 'alpha');
	$action      = GETPOST('action');
	$action_post = GETPOST('action_post');

	$object = new Paiement($db);
	$object->fetch($id, $ref);

	if($id == ""){
		$id = $object->id;
	}

	if(GETPOST('funcion_especial') == 1){
		$sql1="SELECT rowid FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$id." AND entity=".$conf->entity;
		$resq=$db->query($sql1);

		$resultado=$db->fetch_object($resq);

		$del_head_pago     = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
		$del_head_pago     .= " WHERE rowid =".$resultado->rowid;
		$res_del_head_pago = $db->query($del_head_pago);

		$del_pago_rel_fac      = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_relacion_facturas";
		$del_pago_rel_fac     .= " WHERE fk_relacion_pagos =".$resultado->rowid;
		$res_del_pago_rel_fac  = $db->query($del_pago_rel_fac);

		$del_docto_rel     = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado";
		$del_docto_rel     .= " WHERE fk_recepago=".$resultado->rowid;
		$res_del_docto_rel = $db->query($del_docto_rel);

		$del_impuestos_pago  = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
		$del_impuestos_pago .= " WHERE fk_pago=".$id;
		$res_impuestos_pago = $db->query($del_impuestos_pago);

		$del_uuid_rel      = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
		$del_uuid_rel     .= " WHERE fk_pago=".$id;
		$res_del_uuid_rel  = $db->query($del_uuid_rel);

		@header("Location:pagosfacturas.php?id=".$id."&reinicio_pago=1");
	}

	$pagos = new ComplementoPagos($db);

	llxHeader('','Complementos de Pago 2.0 - CFDI '.$conf->global->CFDIMX_VERSION_SAT);

	$head = payment_prepare_head($object);
	print dol_get_fiche_head($head, 'tabpaimentcfdi', $langs->trans("PaymentCustomerInvoice"), -1, 'payment');

	/*******************************************************************
	* ACTIONS
	*
	* Put here all code to do according to value of "action" parameter
	********************************************************************/

	if (GETPOST('action') == 'confirm_send_facture_mail' && GETPOST('confirm') == "yes") {
		echo "Espere un momento el sistema se redirecciona al formulario correcto...";
		print '<script>window.location.href = "'.DOL_URL_ROOT.'/cfdimx/facture.php?facid='.GETPOST('id_factura_seleccionada').'&action=presend&mode=init#formmailbeforetitle";</script>';
	}

	$form = new Form($db);
	$formconfirm='';

	// Confirmación de cancelación
	#
	if ($action_post == 'cancel')
	{
		$id = GETPOST('id') != "" ? GETPOST('id') : GETPOST('facid');
		//$pagoID = GETPOST('pagcid');
		$uuid = GETPOST('uuid');
		$rfc_emisor = GETPOST('rfc_emisor');
	    $text="¿Está seguro que desea cancelar este complemento de pago?";

	    $lista_motivos = array(
				'-1' => 'Selecciona el Motivo de Cancelación&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
				'01' => '01 - Comprobantes emitidos con errores con relación.',
				'02' => '02 - Comprobantes emitidos con errores sin relación.',
				'03' => '03 - No se llevó a cabo la operación.',
				'04' => '04 - Operación nominativa relacionada en una factura global.'
			);

		$titulo = "<br><em>El Folio de Sustitución es obligatorio cuando el motivo es <b>01</b>.</em>";
		$titulo .= "<br><br><strong>Nota:</strong> Se considera una solicitud de cancelación exitosa cuando regresa un valor de 200, sin embargo esto no asegura su cancelación.";

		$pregunta = "¿Desea enviar la solicitud de Cancelación de este Complemento de Pagos?";

	    $formquestion = array(
	        array('type' => 'hidden', 'name' => 'uuid_cancelar', 'id'=>'uuid_cancelar', 'value' => GETPOST("uuid")),
	        array('type' => 'hidden', 'name' => 'action_post', 'id'=>'action_post', 'value' => 'confirm_cancel'),
	        array('type' => 'other', 'value' => '&nbsp;'),
			array('type' => 'select', 'name' => 'motivo', 'id'=>'motivo', 'label' => 'Motivo', 'values' => $lista_motivos, 'select_show_empty' => 0),
			array('type' => 'text', 'name' => 'uuid_sustitucion', 'id'=>'uuid_sustitucion', 'label' =>'Folio de Sustitución', 'moreattr' => 'placeholder="d0645efd-4abb-4c24-a0d7-7986e82cfedf"', 'size' => 35),
			array('type' => 'onecolumn', 'value' => $titulo)
	    );

	    $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$id.'&uuid='.$uuid.'&rfc='.$rfc_emisor, $pregunta, '', 'confirm_cancel', $formquestion, 0, 1, 320, 650);

	    print $formconfirm;
	}

	if ($action_post == 'consultar_estatus') {
        $factura_tmp = new Facture($db);
		$factura_tmp->fetch($id);

		$pago = new Paiement($db);
		$pago->fetch($id);

		$amount_header = $pago->amount;
		if($conf->global->MAIN_MODULE_MULTICURRENCY){
			if($pago->amount > $pago->multicurrency_amount){
				$amount_header = $pago->multicurrency_amount;
			}
		}

		$sql           = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$_REQUEST['id'];
		$req           = $db->query($sql);
		$uuid_consulta = "";
		$nmr           = $db->num_rows($req);

		if($nmr>0){
			$val = $db->fetch_object($req);

			if($val->uuid!=NULL && $val->uuid!=null && trim($val->uuid)!=""){
				$uuid_consulta=$val->uuid;
			}
		}

		$pagos_busqueda = new ComplementoPagos($db);
		$obj_pagos_info = $pagos_busqueda->getDatosXML($pago->ref, $uuid_consulta);

        $titulo   = "Consultar Estatus CFDI";
        $emisor   = "<b>Emisor:</b> ".$obj_pagos_info["emisor_rfc"];
        $receptor = "<b>Receptor:</b> ".$obj_pagos_info["receptor_rfc"];
        $factura  = "<b>Pago:</b> ".$pago->ref;
        $uuid     = "<b>UUID:</b> ".$uuid_consulta;
        $total    = "<b>Total:</b> ".number_format($amount_header, 2);
        $pregunta = "¿Son correctos los Datos para la Consulta del Estatus del Complemento de Pagos?";

        $formquestion = array(
            array('type' => 'onecolumn', 'value' => $emisor),
            	array('type' => 'hidden', 'name' => 'emisor', 'id'=>'emisor', 'value' => $obj_pagos_info["emisor_rfc"]),
            array('type' => 'onecolumn', 'value' => $receptor),
            	array('type' => 'hidden', 'name' => 'receptor', 'id'=>'receptor', 'value' => $obj_pagos_info["receptor_rfc"]),
            array('type' => 'onecolumn', 'value' => $factura),
            array('type' => 'onecolumn', 'value' => $uuid),
            	array('type' => 'hidden', 'name' => 'uuid_c', 'id'=>'uuid_c', 'value' => $uuid_consulta),
            array('type' => 'onecolumn', 'value' => $total),
            	array('type' => 'hidden', 'name' => 'total', 'id'=>'total', 'value' => number_format($amount_header, 2)),
            	array('type' => 'hidden', 'name' => 'action_post', 'id'=>'action_post', 'value' => 'confirm_estatus'),
            array('type' => 'onecolumn', 'value' => $pregunta)
        );

        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $titulo, '', 'confirm_estatus', $formquestion, 0, 1, 320, 600);

		if($obj_pagos_info == null) {
			$msg_error = "Error 9001: No se encontró la información para la Consulta del Complemento.";
			setEventMessage($msg_error, 'errors');
		}else{
			print $formconfirm;
		}
    }

    if ($action_post == "confirm_estatus" && GETPOST('confirm') == "yes") {
        $sql_verificar_estatus = "";
        $sql_verificar_estatus = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_estatus";
        $sql_verificar_estatus .= " WHERE uuid = '".$_REQUEST['uuid_c']."'";
        $sql_verificar_estatus .= " AND entity = ".$conf->entity;
        $res_verificar_estatus = $db->query($sql_verificar_estatus);
        $num_verificar_estatus = $db->num_rows($res_verificar_estatus);

		$datos = array(
			"rfc_emisor"   => $_REQUEST['emisor'],
			"rfc_receptor" => $_REQUEST['receptor'],
			"total"        => 0,
			"uuid"         => $_REQUEST['uuid_c']
		);

		$client = new nusoap_client($wscfdi, 'wsdl');
        $result_status_factura = $client->call('getStatusFactura', $datos);

		$escancelable = $result_status_factura["return"]["esCancelable"];
		if($result_status_factura["return"]["estatusCancelacion"] != "" && !is_null($result_status_factura["return"]["estatusCancelacion"])){
			$escancelable = $result_status_factura["return"]["estatusCancelacion"];
		}

        if($num_verificar_estatus > 0){
            $obj_verificar_estatus = $db->fetch_object($res_verificar_estatus);

            $update_estatus = "";
            $update_estatus = "UPDATE ".MAIN_DB_PREFIX."cfdimx_estatus";
            $update_estatus .= " SET ";
            $update_estatus .= " estado = '".$db->escape(utf8_decode($result_status_factura["return"]["estado"]))."',";
            $update_estatus .= " escancelable = '".$db->escape(utf8_decode($escancelable))."',";
            $update_estatus .= " message = '".$db->escape(utf8_decode($result_status_factura["return"]["message"]))."',";
            $update_estatus .= " fk_user = '".$user->id."',";
            $update_estatus .= " fecha = now(), hora = now()";
            $update_estatus .= " WHERE rowid = ".$obj_verificar_estatus->rowid;

            if($obj_verificar_estatus->rowid > 0){
                $res_update = $db->query($update_estatus);

                print '<script>location.href="?id=' . $_REQUEST["id"] . '"</script>';
            }
        }else{
            $estado_cfdi            = $result_status_factura["return"]["estado"];
            $estatus_cancelacion    = $escancelable;

            $sql_insert = "";
            $sql_insert .= " INSERT INTO ".MAIN_DB_PREFIX."cfdimx_estatus";
            $sql_insert .= " (";
            $sql_insert .= " uuid, facid, entity, estado, escancelable, message, fk_user, fecha, hora";
            $sql_insert .= " )";
            $sql_insert .= " VALUES";
            $sql_insert .= " (";
                $sql_insert .= "'".$_REQUEST['uuid_c']."',";
                $sql_insert .= " 0,";
                $sql_insert .= "'".$conf->entity."',";
                $sql_insert .= "'".$db->escape(utf8_decode($result_status_factura["return"]["estado"]))."',";
                $sql_insert .= "'".$db->escape(utf8_decode($escancelable))."',";
                $sql_insert .= "'".$db->escape(utf8_decode($result_status_factura["return"]["message"]))."',";
                $sql_insert .= "'".$user->id."',";
                $sql_insert .= " now(), now()";
            $sql_insert .= " )";

            $res_insert = $db->query($sql_insert);

            print '<script>location.href="?id=' . $_REQUEST["id"] . '"</script>';
        }
    }

	if ($action_post == 'success_msg') {
		setEventMessage("Complemento de pago cancelado correctamente.", 'mesgs');
	}

	if ($action_post == 'error_msg') {
		$msg_error = GETPOST('msg');
		setEventMessage($msg_error, 'errors');
	}

	if($action_post == "clasificar_pago"){
		$titulo_head = "Clasificar Complemento de Pago";

		$formquestion = array(
			array('type' => 'hidden', 'name' => 'action_post', 'id'=>'action_post', 'value' => 'confirm_clasificar_pago'),
			'text' => '¿Desea cancelar el Complemento de Pago <b>'.$object->ref.'</b>?',
			array('type' => 'other', 'value' => '&nbsp;'),
			array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => 'Complemento de Pago Cancelado', 'morecss' => 'minwidth300')
		);

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$id, $titulo_head, '', '', $formquestion, "yes", 1, 200);

		print $formconfirm;
	}

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
		$motivo = GETPOST('motivo');
		//$pagoID = GETPOST('pago_id');

		$foliosustitucion = (GETPOST("uuid_sustitucion") != '' ? trim(GETPOST("uuid_sustitucion")) : '');

		// Nuevo Esquema de Cancelación 2022
		$datos = array(
			        "timbrado_usuario" => $rfc,
			        "timbrado_password" => $passwd_timbrado,
			        "uuid" => $uuid,
			        "motivo" => $motivo,
			        "foliosustitucion" => $foliosustitucion
			    );

		if($motivo != "" && $motivo != -1){
			$validacion = 1;
            if($motivo == "01"){
                $validacion = ($motivo == "01" && $foliosustitucion != "" ? 1 : 0);
            }

			if($validacion == 1){
				$client = new nusoap_client($wscfdi, 'wsdl');
	    		$resultado = $client->call("cancelar", $datos);

				if($conf->global->CFDIMX_DEBUG_TIMBRADO == 1){
					print '<pre>Datos<br>'; print_r($datos); print '</pre>';
					print '<pre>Resultado<br>'; print_r($resultado); print '</pre>';
				}

				if($resultado["return"] != ""){
					if($resultado["return"]["httpStatusCode"] == 200){
						//Se guarda el acuse de cancelación en un archivo
                        if(file_exists($conf->facture->dir_output."/".$object->ref)){

                        }else{
                            mkdir($conf->facture->dir_output."/".$object->ref,0700);
                        }

                        $fecha = date("Y-m-d")."_".date("H-i-s");
                        $archivo_acuse = "acuse_cancelacion_pago_".$fecha."_".$uuid.".xml";

                        $nombre_file_acuse = $conf->facture->dir_output."/".$object->ref."/".$archivo_acuse;
                        $file_acuse = fopen ($nombre_file_acuse, "w");
                        fwrite($file_acuse,utf8_encode($resultado["return"]["acuse"]));
                        fclose($file_acuse);

                        #Inicio para guardar la respuesta a la solicitud de Cancelacion
                        $sql_solicitud_canceacion = "";
                        $sql_solicitud_canceacion .= "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_solicitud_cancelacion_pagos";
                        $sql_solicitud_canceacion .= " (fk_pago, httpStatusCode, acuse, status, uuid, uuidStatusCode, message, messageDetail, fecha, hora, archivo)";
                        $sql_solicitud_canceacion .= " VALUES";
                        $sql_solicitud_canceacion .= " (";
                            $sql_solicitud_canceacion .= "'.$id.',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["httpStatusCode"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["acuse"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["status"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["uuid"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["uuidStatusCode"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["message"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["messageDetail"]))."',";
                            $sql_solicitud_canceacion .= " now(), now(),";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($archivo_acuse))."'";
                        $sql_solicitud_canceacion .= " )";

                        // print $sql_solicitud_canceacion;
                        $res_solicitud_canceacion = $db->query($sql_solicitud_canceacion);
                        #Termina para guardar la respuesta a la solicitud de Cancelacion

						print '<script>location.href="?id='.$id.'&action_post=success_msg";</script>';
					}else{
						$msg_cfdi_final = "Error al Cancelar El Complemento de Pagos<br><br><br>";
						if($resultado["return"]["message"] != "")
							$msg_cfdi_final .= $resultado["return"]["message"]."&nbsp;";

						if($resultado["return"]["messageDetail"] != "")
							$msg_cfdi_final .= $resultado["return"]["messageDetail"];

						print '<script>location.href="?id='.$id.'&action_post=error_msg&msg='.$msg_cfdi_final.'";</script>';
					}
				}else{
					$msg_cfdi_final = "No hay respuesta para la cancelación con el SAT, favor de intentar mas tarde.";
					print '<script>location.href="?id='.$id.'&action_post=error_msg&msg='.$msg_cfdi_final.'";</script>';
				}
			}else{
                $msg_cfdi_final = "Error 9001: El Folio de Sustitución esta vacío y es requerido cuando el Motivo es 01 - Comprobantes emitidos con errores con relación.";
				print '<script>location.href="?id='.$id.'&action_post=error_msg&msg='.$msg_cfdi_final.'";</script>';
            }
		}else{
            $msg_cfdi_final = "Error 9002: El Motivo de Cancelación es obligatorio.";
			print '<script>location.href="?id='.$id.'&action_post=error_msg&msg='.$msg_cfdi_final.'";</script>';
        }
	}

	if($action_post == "confirm_clasificar_pago" && GETPOST('confirm') == "yes"){
		print GETPOST('close_note');
		$nota = (GETPOST('close_note') != '' ? trim(GETPOST('close_note')) : 'Complemento de pago cancelado');

		$sql_cancela_pago = "UPDATE ".MAIN_DB_PREFIX."paiement SET note='".$nota."' WHERE rowid = ".$id;
		// print $sql_cancela_pago.'<br>';
		$res_cancela_pago = $db->query($sql_cancela_pago);

		$sql_cancela_rec_pago = "UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos SET cancelado = 1 WHERE fk_paiement = ".$id;
		// print $sql_cancela_rec_pago.'<br>';
		$res_cancela_rec_pago = $db->query( $sql_cancela_rec_pago);

		print '<script>location.href="?id='.$id.'&action_post=success_msg";</script>';
	}

	if(GETPOST("mesg")=="err1"){
		dol_htmloutput_errors("Error al registrar la informacion del Pago CFDI");
	}

	if (GETPOST("msgerr")==1 && $_SESSION["errorCFDIP"]!="") {
	    dol_htmloutput_errors($_SESSION["errorCFDIP"]);
	    $_SESSION["errorCFDIP"]="";

	    if($conf->global->CFDIMX_DEBUG_TIMBRADO == 1){
	    	print '<pre>header<br>'; print_r($_SESSION['header']); print '</pre>';
			print '<pre>conceptos<br>'; print_r($_SESSION['conceptos']); print '</pre>';
			print '<pre>emisor<br>'; print_r($_SESSION['emisor']); print '</pre>';
			print '<pre>receptor<br>'; print_r($_SESSION['receptor']); print '</pre>';
			print '<pre>rfc_emisor<br>'; print_r($_SESSION['rfc_emisor']); print '</pre>';
			print '<pre>passwd_timbrado<br>'; print_r($_SESSION['passwd_timbrado']); print '</pre>';
			print '<pre>adicionales<br>'; print_r($_SESSION['adicionales']); print '</pre>';
			print '<pre>resultado<br>'; print_r($_SESSION['resultado']); print '</pre>';
	    }
	}

	if(GETPOST('commit') == 1){
		setEventMessage("Complemento de Pago Timbrado correctamente.", 'mesgs');
	}

	if(GETPOST('regen_pdf') == 1){
		setEventMessage("PDF Regenerado correctamente.", 'mesgs');
	}

	if(GETPOST('reinicio_pago') == 1){
		setEventMessage("Se elimino correctamente la Información del Pago.", 'mesgs');
	}

	if(GETPOST("action")=="guardar"){
		setEventMessage("Se guardo correctamente la Información del Complemento.");
	}

	//Guardar inicio
	if($action=="guardar"){
		// print '<pre>'; print_r($_REQUEST); print '</pre>';
		// die;
		$tipo_relacion = GETPOST('tipo_relacion');
		$fechaaux=str_replace("/", "-", GETPOST('fechaPago'));
		$fechaPago=date("Y-m-d",strtotime($fechaaux));
		$fechaPago=$fechaPago." ".GETPOST('fechaPagohour').":".GETPOST("fechaPagomin").":00";
		$sql1="SELECT rowid FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$id." AND entity=".$conf->entity;
		$resq=$db->query($sql1);
		$numr=$db->num_rows($resq);
		if($numr==0){

			//print $fechaPago;
			$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
				   (
					   	fk_facture,
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
						entity,
						rel_facture,
						tipo_rel
					)
					VALUES (
						0,
						".$id.",
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
						".$conf->entity.",
						1,
						'".$tipo_relacion."'
					)";

			//print "<br>".$sql."<br>";exit();
			if($res=$db->query($sql)){
				$last=$db->last_insert_id(MAIN_DB_PREFIX."cfdimx_recepcion_pagos");
				$fk_recepago=$last;
				//print_r($fk_recepago);
				//$fk_recepago=1;
				$contar=count($_REQUEST["idFacturas"]);
				for($i=0;$i<$contar;$i++){
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
							".($_REQUEST['idDocumento'][$i]!=''?"'".$_REQUEST['idDocumento'][$i]."'":'NULL').",
							".($_REQUEST['docSerie'][$i]!=''?"'".$_REQUEST['docSerie'][$i]."'":'NULL').",
							".($_REQUEST['docFolio'][$i]!=''?"'".$_REQUEST['docFolio'][$i]."'":'NULL').",
							".($_REQUEST['monedaDR'][$i]!=''?"'".$_REQUEST['monedaDR'][$i]."'":'NULL').",
							".($_REQUEST['tipocambiodr'][$i]!=''?"'".$_REQUEST['tipocambiodr'][$i]."'":'NULL').",
							".($_REQUEST['metodoPDR'][$i]!=''?"'".$_REQUEST['metodoPDR'][$i]."'":'NULL').",
							".($_REQUEST['numparcialidaddr'][$i]!=''?"'".$_REQUEST['numparcialidaddr'][$i]."'":'NULL').",
							".($_REQUEST['impSaldoAnterior'][$i]!=''?"'".$_REQUEST['impSaldoAnterior'][$i]."'":'NULL').",
							".($_REQUEST['impPagadodr'][$i]!=''?"'".$_REQUEST['impPagadodr'][$i]."'":'NULL').",
							".($_REQUEST['impSaldoInsoluto'][$i]!=''?"'".$_REQUEST['impSaldoInsoluto'][$i]."'":'NULL').",
							".$conf->entity.",
							".($_REQUEST['equivalenciadr'][$i]!=''?"'".$_REQUEST['equivalenciadr'][$i]."'":'NULL')."
					 	)";
					$sql3="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_relacion_facturas (fk_facture,fk_relacion_pagos)
							VALUES ('".$_REQUEST["idFacturas"][$i]."','".$fk_recepago."')";

					//print "<br>".$sql2."<br>";
					//print "<br>".$sql3."<br>";
					$res2=$db->query($sql2);
					$res3=$db->query($sql3);
				}

				if(isset($_REQUEST["impuesto_trasret"]) && is_array($_REQUEST["impuesto_trasret"]) && count($_REQUEST["impuesto_trasret"]) > 0){
					for($i=0 ; $i < count($_REQUEST["impuesto_trasret"]); $i++){

						$sql_impuesto = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
						$sql_impuesto .= " (entity, fk_pago, uuid_doc_rel, base, impuesto, tipo_factor, tasa_o_cuota, importe, tipo, fk_user)";
						$sql_impuesto .= " VALUES";
						$sql_impuesto .= " (";
							$sql_impuesto .= " '".$conf->entity."',";
							$sql_impuesto .= " '".$id."',";
							$sql_impuesto .= " '".$_REQUEST["uuid_docto_rel"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["base_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["impuesto_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["tipofactor_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["tasacuota_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["importe_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["tipo_trasret"][$i]."',";
							$sql_impuesto .= " '".$user->id."'";
						$sql_impuesto .= " )";

						if($_REQUEST["impuesto_trasret"][$i] != ""){
							$res_impuesto = $db->query($sql_impuesto);
						}
					}
				}

				if(isset($_REQUEST["uuid_rel"]) && is_array($_REQUEST["uuid_rel"]) && count($_REQUEST["uuid_rel"]) > 0){
					for($z = 0 ; $z < count($_REQUEST["uuid_rel"]); $z++){
						if($_REQUEST["estado_uuid_rel"][$z] == 0){
							$sql_uuid_rel = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
							$sql_uuid_rel .= " (fk_pago, uuid, fk_pago_rel)";
							$sql_uuid_rel .= " VALUES";
							$sql_uuid_rel .= " (";
								$sql_uuid_rel .= "'".$id."',";
								$sql_uuid_rel .= "'".$_REQUEST["uuid_rel"][$z]."',";
								$sql_uuid_rel .= "'".$_REQUEST["fk_pago_rel"][$z]."'";
							$sql_uuid_rel .= " )";

							$res_uuid_rel = $db->query($sql_uuid_rel);
						}
					}
				}

				@header("Location:pagosfacturas.php?id=".$id);
				//print "<script>window.location.href='pagosfacturas.php?id=".$id."</script>";
			}else{
				@header("Location:pagosfacturas.php?id=".$id."&mesg=err1");
				print "<script>window.location.href='pagosfacturas.php?id=".$id."&mesg=err1'</script>";
			}
	 	}else{
	 		$resultado=$db->fetch_object($resq);

	 		$del_docto_rel     = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado";
	 		$del_docto_rel     .= " WHERE fk_recepago=".$resultado->rowid;
			$res_del_docto_rel = $db->query($del_docto_rel);

			$del_impuestos_pago  = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
	 		$del_impuestos_pago .= " WHERE fk_pago=".$id;
			$res_impuestos_pago = $db->query($del_impuestos_pago);

			$del_uuid_rel      = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
	 		$del_uuid_rel     .= " WHERE fk_pago=".$id;
			$res_del_uuid_rel  = $db->query($del_uuid_rel);

			$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
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
					selloPago=".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL').",
					tipo_rel='".$tipo_relacion."'
					WHERE rowid=".$resultado->rowid;

			//print "<br>".$sql."<br>";exit();
			if($res=$db->query($sql)){
				$fk_recepago=$resultado->rowid;
				//print_r($fk_recepago);
				//$fk_recepago=1;
				$contar=count($_REQUEST["idFacturas"]);
				for($i=0;$i<$contar;$i++){
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
						VALUES
							(
								'".$fk_recepago."',
								".($_REQUEST['idDocumento'][$i]!=''?"'".$_REQUEST['idDocumento'][$i]."'":'NULL').",
								".($_REQUEST['docSerie'][$i]!=''?"'".$_REQUEST['docSerie'][$i]."'":'NULL').",
								".($_REQUEST['docFolio'][$i]!=''?"'".$_REQUEST['docFolio'][$i]."'":'NULL').",
								".($_REQUEST['monedaDR'][$i]!=''?"'".$_REQUEST['monedaDR'][$i]."'":'NULL').",
								".($_REQUEST['tipocambiodr'][$i]!=''?"'".$_REQUEST['tipocambiodr'][$i]."'":'NULL').",
								".($_REQUEST['metodoPDR'][$i]!=''?"'".$_REQUEST['metodoPDR'][$i]."'":'NULL').",
								".($_REQUEST['numparcialidaddr'][$i]!=''?"'".$_REQUEST['numparcialidaddr'][$i]."'":'NULL').",
								".($_REQUEST['impSaldoAnterior'][$i]!=''?"'".$_REQUEST['impSaldoAnterior'][$i]."'":'NULL').",
								".($_REQUEST['impPagadodr'][$i]!=''?"'".$_REQUEST['impPagadodr'][$i]."'":'NULL').",
								".($_REQUEST['impSaldoInsoluto'][$i]!=''?"'".$_REQUEST['impSaldoInsoluto'][$i]."'":'NULL').",
								".$conf->entity.",
								".($_REQUEST['equivalenciadr'][$i]!=''?"'".$_REQUEST['equivalenciadr'][$i]."'":'NULL')."
						 )";
					$res2=$db->query($sql2);
				}

				if(isset($_REQUEST["impuesto_trasret"]) && is_array($_REQUEST["impuesto_trasret"]) && count($_REQUEST["impuesto_trasret"]) > 0){
					for($i=0 ; $i < count($_REQUEST["impuesto_trasret"]); $i++){

						$sql_impuesto = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
						$sql_impuesto .= " (entity, fk_pago, uuid_doc_rel, base, impuesto, tipo_factor, tasa_o_cuota, importe, tipo, fk_user)";
						$sql_impuesto .= " VALUES";
						$sql_impuesto .= " (";
							$sql_impuesto .= " '".$conf->entity."',";
							$sql_impuesto .= " '".$id."',";
							$sql_impuesto .= " '".$_REQUEST["uuid_docto_rel"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["base_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["impuesto_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["tipofactor_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["tasacuota_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["importe_trasret"][$i]."',";
							$sql_impuesto .= " '".$_REQUEST["tipo_trasret"][$i]."',";
							$sql_impuesto .= " '".$user->id."'";
						$sql_impuesto .= " )";

						if($_REQUEST["impuesto_trasret"][$i] != ""){
							$res_impuesto = $db->query($sql_impuesto);
						}
					}
				}

				if(isset($_REQUEST["uuid_rel"]) && is_array($_REQUEST["uuid_rel"]) && count($_REQUEST["uuid_rel"]) > 0){
					for($z = 0 ; $z < count($_REQUEST["uuid_rel"]); $z++){
						if($_REQUEST["estado_uuid_rel"][$z] == 0 && $_REQUEST["uuid_rel"][$z] != ""){
							$sql_uuid_rel = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
							$sql_uuid_rel .= " (fk_pago, uuid, fk_pago_rel)";
							$sql_uuid_rel .= " VALUES";
							$sql_uuid_rel .= " (";
								$sql_uuid_rel .= "'".$id."',";
								$sql_uuid_rel .= "'".$_REQUEST["uuid_rel"][$z]."',";
								$sql_uuid_rel .= "'".$_REQUEST["fk_pago_rel"][$z]."'";
							$sql_uuid_rel .= " )";

							$res_uuid_rel = $db->query($sql_uuid_rel);

							if(!$res_uuid_rel){
								dol_print_error($db);
							}
						}
					}
				}

				@header("Location:pagosfacturas.php?id=".$id);
				//print "<script>window.location.href='pagosfacturas.php?id=".$id."</script>";
			}else{
				@header("Location:pagosfacturas.php?id=".$id."&mesg=err1");
				//print "<script>window.location.href='pagosfacturas.php?id=".$id."&mesg=err1'</script>";
			}
	 	}
		$action = '';
	}
	//Guardar fin

	/***************************************************
	* VIEW
	*
	* Put here all code to build page
	****************************************************/

	#variable usada en el envio por correo
	$referencia_pago = $object->ref;

	///CABECERA INICIO
	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '');

	print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
			print '<table class="noborder">';
				print '<tr class="liste_titre">';
					print '<td colspan="2" style="text-align: center;">';
						print '<span class="fa fa-money"></span>&nbsp;';
						print '<strong>Datos del Pago</strong>';
					print '</td>';
				print '</tr>';

				// Date payment
				print '<tr>';
					print '<td>'.$langs->trans("Date").'</td>';
					print '<td>';
						print $form->editfieldval("Date", 'datep', $object->date, $object, $user->rights->facture->paiement, 'datehourpicker', '', null, $langs->trans('PaymentDateUpdateSucceeded'));
					print '</td>';
				print '</tr>';

				// Amount
				$amount_header       = $object->amount;
				$amount_multi_header = $object->multicurrency_amount;

				$moneda_header = "";

				print '<tr>';
					print '<td>'.$langs->trans('Amount').'</td>';
					print '<td>'.price($amount_header,0,'',1,-1,-1,$conf->currency).'</td>';
				print '</tr>';

				print '<tr>';
					print '<td class="fieldrequired"><strong>Uso CFDI</strong></td>';
					print '<td>';
						print $pagos->obtener_catalogo($conf->global->CFDIMX_USOCFDI_PAGOS, 'usocfdi', 1, 1);

						if($conf->global->CFDIMX_USOCFDI_PAGOS == -1){
							print '<div class="error hideonsmartphone clearboth">';
								print '<span class="fa fa-info-circle valignmiddle btnTitle-icon"></span>&nbsp;';
								print '<strong>';
									print 'Es necesario configurar el Uso CFDI, ';
									print 'da click <a href="admin/cfdimx.php?mod=configopcional">aquí</a> para configurarlo.';
								print '</strong>';
							print '</div>';
						}
					print '</td>';
				print '</tr>';

				$sql_check_cancel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
				$sql_check_cancel .= " WHERE fk_paiement = ".$id;
				$res_check_cancel = $db->query($sql_check_cancel);
				$num_check_cancel = $db->num_rows($res_check_cancel);
				$estado_cancel    = 0;

				if($num_check_cancel > 0){
					$obj_cancelado = $db->fetch_object($res_check_cancel);
					$estado_cancel = $obj_cancelado->cancelado;
				}

				print '<tr>';
					print '<td>Estatus</td>';
					print '<td>';
						if($estado_cancel == 1){
							print '<span class="badge badge-status8 badge-status">Cancelado</span>';
						}else{
							print '<span class="badge badge-status4 badge-status">Vigente</span>';
						}
					print '</td>';
				print '</tr>';
			print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';
			print '<table class="noborder">';
				$disable_delete = 0;
				// Bank account
				if (! empty($conf->banque->enabled))
				{
					if ($object->fk_account > 0)
					{
						$bankline=new AccountLine($db);
						$bankline->fetch($object->bank_line);
						if ($bankline->rappro)
						{
							$disable_delete = 1;
							$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
						}

						print '<tr class="liste_titre">';
							print '<td colspan="2" style="text-align: center;">';
								print '<span class="fa fa-bank"></span>&nbsp;';
								print '<strong>Datos Bancarios</strong>';
							print '</td>';
						print '</tr>';

						print '<tr>';
							print '<td>'.$langs->trans('BankAccount').'</td>';
							print '<td>';
								$accountstatic=new Account($db);
								$accountstatic->fetch($bankline->fk_account);
								print $accountstatic->getNomUrl(1);
							print '</td>';
						print '</tr>';

						print '<tr>';
							print '<td>'.$langs->trans('BankTransactionLine').'</td>';
							print '<td>';
								print $bankline->getNomUrl(1,0,'showconciliated');
							print '</td>';
						print '</tr>';
					}
				}
			print '</table>';
		print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$id;
	// print $sql;
	$req=$db->query($sql);
	$uuidP="";
	$nmr=$db->num_rows($req);
	if($nmr>0){
		$val=$rsl=$db->fetch_object($req);
		if($val->uuid!=NULL && $val->uuid!=null && trim($val->uuid)!=""){
			$action="cfdi1";
			$uuidP=$val->uuid;
		}
	}

	#Vista Preliminar
	if($action==""){

		$existepagos=0;
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
		$tipo_relacion = "";

		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$id;
		//print $sql;
		$req=$db->query($sql);
		$recepcion_pagos=$db->num_rows($req);
		$idrecpago=0;

		if($recepcion_pagos > 0){
			$rsl                      = $db->fetch_object($req);
			$idrecpago                = $rsl->rowid;
			$datep                    = strtotime($rsl->fechaPago);
			$formpago                 = $rsl->formaDePago;
			$monedapago               = $rsl->monedaP;
			$tipocambio               = $rsl->TipoCambioP;
			$montop                   = $rsl->monto;
			$numoperacion             = $rsl->numOperacion;
			$rfcemisorctaorigen       = $rsl->rfcEmisorCtaOrd;
			$nombancoordenante        = $rsl->nomBancoOrdExt;
			$ctaordenante             = $rsl->ctaOrdenante;
			$rfcemisorctabeneficiario = $rsl->rfcEmisorCtaBen;
			$ctabeneficiario          = $rsl->ctaBeneficiario;
			$tipocadenapago           = $rsl->tipoCadPago;
			$certificadopago          = $rsl->certPago;
			$cadenaoriginal           = $rsl->cadPago;
			$sellopago                = $rsl->selloPago;
			$existepagos              = 1;
			$tipo_relacion    		  = $rsl->tipo_rel;
		}else{
			##Inicia Consulta para ver si existe moneda
			$facturas_asociadas = $object->getBillsArray();
			$sql_control_moneda = "SELECT * FROM ".MAIN_DB_PREFIX."paiement_facture";
			$sql_control_moneda .= " WHERE";
				$sql_control_moneda .= " 	 fk_facture  = ".$facturas_asociadas[0];
				$sql_control_moneda .= " AND fk_paiement = ".$id;
			$sql_control_moneda .= " LIMIT 1";
			// print $sql_control_moneda;
			$res_control_moneda = $db->query($sql_control_moneda);
			$num_control_moneda = $db->num_rows($res_control_moneda);

			if($num_control_moneda > 0){
				$obj_control_moneda = $db->fetch_object($res_control_moneda);
				if(!is_null($obj_control_moneda->multicurrency_code) && $obj_control_moneda->multicurrency_code != ""){
					// $monedaa    = $obj_control_moneda->multicurrency_code;
					$monedapago = $obj_control_moneda->multicurrency_code;
				}
			}
			##Termina Consulta para ver si existe moneda
		}

		if($datep==""){
			$datep=$object->date;
		}

		//Formas pago
		if($formpago == ""){
			$sql_fpago = "SELECT accountancy_code FROM ".MAIN_DB_PREFIX."c_paiement WHERE code='".$object->type_code."'";
			$res_fpago = $db->query($sql_fpago);

			if($db->num_rows($res_fpago) > 0){
				$obj_res_fpago = $db->fetch_object($res_fpago);
				$formpago      = $obj_res_fpago->accountancy_code;
			}
		}


		$select_monedas     = "SELECT * FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity = ".$conf->entity;
		$res_select_monedas = $db->query($select_monedas);
		$num_select_monedas = $db->num_rows($res_select_monedas);

		if($num_select_monedas == 0){
			$monedapago = $conf->currency;
		}

		if($montop==""){
		}else{
			$montop=str_replace(",","",number_format($montop,2));
		}

		if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
			if($tipocambio == ""){
				if($monedapago == "MXN"){
					$tipocambio=1;
				}
			}
		}

		if($recepcion_pagos>0){
		}else{
			$montop = str_replace(",","",number_format($amount_header,2));
		}

		print '<form method="POST" action="pagosfacturas.php?id='.$id.'&action=guardar" id="formulario_guardar">';
			print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';

			print '<div class="fichecenter">';
				print '<table class="noborder" style="margin-bottom: auto;">';
					print '<tr class="liste_titre">';
						print '<td colspan="4" align="center" >';
							print '<span class="fa fa-money-check"></span>&nbsp;';
							print '<strong>CFDI Relacionados</strong>';
						print '</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Tipo Relación</td>';
						print '<td>';
							print $pagos->obtener_rel_pagos($tipo_relacion, 'tipo_relacion', 1);
						print '</td>';
					print '</tr>';

					$sql_list_uuid_rel  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
					$sql_list_uuid_rel .= " WHERE fk_pago = ".$id;
					// print $sql_list_uuid_rel;
					$res_list_uuid_rel  = $db->query($sql_list_uuid_rel);
					$num_list_uuid_rel  = $db->num_rows($res_list_uuid_rel);
					$lista_ids_pagos[]  = $id;

					if($num_list_uuid_rel > 0){
						while($obj_uuid_rel_tmp = $db->fetch_object($res_list_uuid_rel)){
							if(!is_null($obj_uuid_rel_tmp->fk_pago_rel) && $obj_uuid_rel_tmp->fk_pago_rel != ""){
								$lista_ids_pagos[] = $obj_uuid_rel_tmp->fk_pago_rel;
							}
						}
					}

					$sql_restriccion = implode(",", $lista_ids_pagos);

					print '<tr>';
						print '<td>Lista de Pagos</td>';
						print '<td>';
							print $pagos->obtener_rel_pagos($lista_pagos, 'lista_pagos', 2, 0, $sql_restriccion);
							print '&nbsp;&nbsp;';
							print '<button onclick="agregarPago(); return false;" class="butAction">Añadir</button>';
						print '</td>';
					print '</tr>';

					print '<tr>';
						$display_table = "none";
						if($num_list_uuid_rel > 0){
							$display_table = "block";
						}

						print '<input type="hidden" name="num_pagos_rel" id="num_pagos_rel" value="'.$num_list_uuid_rel.'">';

						print '<table class="noborder" style="display: '.$display_table.'; margin-bottom: auto;" id="lista_cfdi_rel">';
							print '<tr class="liste_titre">';
								print '<td>';
									print '<span class="fa fa-trash" title="Para eliminar un UUID seleccione el recuadro siguiente"></span>';
								print '</td>';
								print '<td><strong>UUID Relacionados</strong></td>';
							print '</tr>';

							if($num_list_uuid_rel > 0){
								$num_pagos = 1;
								$res_list_uuid_rel  = $db->query($sql_list_uuid_rel);
								while($obj_uuid_rel = $db->fetch_object($res_list_uuid_rel)){
									print '<tr>';
										print '<td>';
											print '<input type="checkbox" name="btn_del_pago_rel[]" id="btn_del_pago_rel"'.$num_pagos.'" onchange="cambiaPago('.$num_pagos.')">';
										print '</td>';
										print '<td>';

											print "<input type='hidden' name='estado_uuid_rel[]' id='estado_uuid_rel".$num_pagos."' value='0'>";
											print "<input type='hidden' name='fk_pago_rel[]' id='fk_pago_rel".$num_pagos."' value='".$obj_uuid_rel->fk_pago_rel."'>";
											print "<input type='hidden' name='uuid_rel[]' id='uuid_rel".$num_pagos."' value='".$obj_uuid_rel->uuid."'>";

											print  $obj_uuid_rel->uuid;
										print '</td>';
									print '</tr>';
									$num_pagos++;
								}
							}
						print '</table>';
					print '</tr>';
				print '</table>';
			print '</div>';

			print '<br>';

			print '<div class="fichecenter">';
				print '<table class="noborder" style="margin-bottom: auto;">';
					print '<tr class="liste_titre">';
						print '<td colspan="4" align="center" >';
							print '<span class="fa fa-money"></span>&nbsp;';
							print '<strong>Datos del Complemento de Pagos</strong>';
						print '</td>';
					print '</tr>';
				print '</table>';

				print '<div class="fichehalfleft">';
					print '<table class="noborder">';
						print '<thead>';
							print '<tr>';
								print '<td class="fieldrequired">Fecha</td>';
								print '<td>';
									print '<input type="hidden" name="mos" id="mos" value="0">';
									$form->select_date($datep,'fechaPago',1,1,0,'nfechaPago');
								print '</td>';
							print '</tr>';
						print '</thead>';

						print '<tr>';
							print '<td class="fieldrequired">Moneda</td>';
							print '<td>';
								if($num_select_monedas > 0){
									print $pagos->obtener_catalogo($monedapago, 'monedapago', 3);
								}else{
									print $pagos->obtener_catalogo($monedapago, 'monedapago', 4);
								}
							print '</td>';
						print '</tr>';

						print '<tr>';
							print '<td>Tipo de cambio</td>';
							print '<td><input type="text" name="tipocambio" id="tipocambio" value="'.$tipocambio.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>RFC Emisor Cuenta Ordenante</td>';
							print '<td><input type="text" name="rfcemisorctaorigen" value="'.$rfcemisorctaorigen.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>Cuenta Ordenante</td>';
							print '<td><input type="text" name="ctaordenante" value="'.$ctaordenante.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>Cuenta Beneficiario</td>';
							print '<td><input type="text" name="ctabeneficiario" value="'.$ctabeneficiario.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>Certificado del Pago</td>';
							print '<td>';
								print '<textarea name="certificadopago" rows="3" cols="35">';
									print $certificadopago;
								print '</textarea>';
							print '</td>';
						print '</tr>';

						print '<tr>';
							print '<td>Sello Pago</td>';
							print '<td>';
								print '<textarea name="sellopago" rows="3" cols="35">';
									print $sellopago;
								print '</textarea>';
							print '</td>';
						print '</tr>';

					print '</table>';
				print '</div>';

				print '<div class="fichehalfright">';
					print '<table class="noborder">';
						print '<thead>';
							print '<tr>';
								print '<td class="fieldrequired"><strong>Forma de Pago</strong></td>';
								print '<td>';
									print $pagos->obtener_catalogo($formpago, 'formpago', 2);
								print '</td>';
							print '</tr>';
						print '</thead>';

						print '<tr>';
							print '<td class="fieldrequired">Monto</td>';
							print '<td>';
								if($amount_header != $amount_multi_header){
									print $pagos->obtener_catalogo($montop, 'montop', 7, 0, $id);
								}else{
									print '<input type="text" name="montop" value="'.$montop.'" >';
								}
							print '</td>';
						print '</tr>';

						print '<tr>';
							print '<td>Numero de operacion</td>';
							print '<td><input type="text" name="numoperacion" value="'.$numoperacion.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>Nom. Banco Ordenante (Ext)</td>';
							print '<td><input type="text" name="nombancoordenante" value="'.$nombancoordenante.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>RFC Emisor Cuenta Beneficiario</td>';
							print '<td><input type="text" name="rfcemisorctabeneficiario" value="'.$rfcemisorctabeneficiario.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>Tipo Cadena de Pago</td>';
							print '<td><input type="text" name="tipocadenapago" value="'.$tipocadenapago.'" ></td>';
						print '</tr>';

						print '<tr>';
							print '<td>Cadena Original del comprobante pago</td>';
							print '<td>';
								print '<textarea name="cadenaoriginal" rows="3" cols="35">';
									print $cadenaoriginal;
								print '</textarea>';
							print '</td>';
						print '</tr>';

					print '</table>';
				print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, pf.multicurrency_amount, s.nom as name, s.rowid as socid';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
			$sql.= ' WHERE pf.fk_facture = f.rowid';
			$sql.= ' AND f.fk_soc = s.rowid';
			$sql.= ' AND f.entity = '.$conf->entity;
			$sql.= ' AND pf.fk_paiement = '.$object->id;
			$resql=$db->query($sql);

			if ($resql)
			{
				$num = $db->num_rows($resql);
				$thirdpartystatic=new Societe($db);
				$i = 0;
				$total = 0;

				$moreforfilter='';
				$valguarda=0;
				$var_contador = 0;

				if ($num > 0)
				{
					$var=True;

					while ($i < $num)
					{
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

						$objp = $db->fetch_object($resql);
						$var=!$var;
						$invoice=new Facture($db);
						$invoice->fetch($objp->facid);

						// sergi 23/feb/2022
						$monedafactura = $invoice->multicurrency_code;
						if ($monedafactura!='MXN'){
							$invoicetotal = $invoice->multicurrency_total_ttc;
							$paiement     = $invoice->getSommePaiement(1);
							$creditnotes  = $invoice->getSumCreditNotesUsed(1);
							$deposits     = $invoice->getSumDepositsUsed(1);
							$alreadypayed = price2num($paiement + $creditnotes + $deposits,'MT');
						}else{
							$invoicetotal = $invoice->total_ttc;
							$paiement     = $invoice->getSommePaiement();
							$creditnotes  = $invoice->getSumCreditNotesUsed();
							$deposits     = $invoice->getSumDepositsUsed();
							$alreadypayed = price2num($paiement + $creditnotes + $deposits,'MT');
						}
						$remaintopay=price2num($invoicetotal - $paiement - $creditnotes - $deposits,'MT');

						// equivalencia entre la moneda del pago y el de la factura
						$ayuda_equivalencia = "";
						if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
							if($monedapago == $monedafactura){
								$equivalenciadr = 1;
							}else{
								if($monedapago == ""){
									$ayuda_equivalencia = '1 ¿? = ¿XXX '.$monedafactura.'?';
								}else{
									$ayuda_equivalencia = '1 '.$monedapago.' = ¿XXX '.$monedafactura.'?';
								}
							}
						}

						$facid = $objp->facid;

						$thirdpartystatic->id=$objp->socid;
						$thirdpartystatic->name=$objp->name;

						$extrae=explode("-", $invoice->ref);
						$alc=0;
						if(count($extrae)>1){
							$docSerie=$extrae["0"];
							$docFolio=$extrae["1"];
							$alc=1;
						}else{
							$docFolio=$extrae["0"];
						}

						// $sqlval="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$objp->facid." AND fk_paiement=".$id;
						// $reqval=$db->query($sqlval);
						// $nmrval=$db->num_rows($reqval);
						// print $sqlval." :: ".$nmrval."<br>";
						// if($nmrval>0){
						// 	$existepagos=1;
						// }

						if($idrecpago > 0){
							if($alc==1){
								$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
										WHERE fk_recepago=".$idrecpago." AND serie='".$docSerie."' AND folio='".$docFolio."'";
							}else{
								$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
										WHERE fk_recepago=".$idrecpago." AND folio='".$docFolio."'";
							}
							// print $sql."<br>";
							$req=$db->query($sql);
							$nmr=$db->num_rows($req);
							if($nmr>0){
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
							}
						}

						if($idDocumento=="" && $monedaDR=="" && $metodoPDR=="" && $impPagadodr==""){
							//$sql="SELECT a.uuid, a.divisa, c.accountancy_code FROM ".MAIN_DB_PREFIX."cfdimx a, ".MAIN_DB_PREFIX."facture b,".MAIN_DB_PREFIX."c_paiement c WHERE a.fk_facture=".$invoice->id." AND a.fk_facture=b.rowid AND b.fk_mode_reglement=c.id"; //query original
							$sql='SELECT cfdi.uuid, cfdi.divisa FROM '.MAIN_DB_PREFIX.'cfdimx cfdi WHERE cfdi.fk_facture='.$invoice->id;
							//print $sql."<br>";
							$rq=$db->query($sql);
							if ($db->num_rows($rq) > 0) {
								$rs=$db->fetch_object($rq);
								$monedaDR=$rs->divisa;
								$idDocumento=$rs->uuid;
							}

							$sqlm="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'formpagcfdi'";
							$resqlv=$db->query($sqlm);
							if( $db->num_rows($resqlv) > 0 ){
								$sqlv="SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".$invoice->id;
								$rv=$db->query($sqlv);
								$vrs=$db->fetch_object($rv);
								$metodoPDR = $vrs->formpagcfdi;
							}

							if($monedaDR == "MXN"){
								$impPagadodr=str_replace(array(",","-"), "",number_format($objp->amount,2));
							}else{
								$impPagadodr=str_replace(array(",","-"), "",number_format($objp->multicurrency_amount,2));
							}

							if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
								$numparcialidaddr = 1;
								// sergi 23/feb/2022
								$impSaldoAnterior = str_replace(array(",","-"), "",number_format($invoicetotal,2));

								if($idrecpago <= 0){
									##Iinicio para verificar si tiene una parcialidad previa
									$sql_parcialidad = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado";
									$sql_parcialidad .= " WHERE idDocumento = '".$idDocumento."'";
									$sql_parcialidad .= " ORDER BY rowid DESC LIMIT 1";

									$res_parcialidad = $db->query($sql_parcialidad);
									$num_parcialidad = $db->num_rows($res_parcialidad);

									if($num_parcialidad > 0){
										$obj_documento = $db->fetch_object($res_parcialidad);
										$numparcialidaddr = $obj_documento->numParcialidad + 1;
										$impSaldoAnterior = str_replace(array(",","-"), "",number_format($obj_documento->impSaldoInsoluto,2));
									}
									##Termina para verificar si tiene una parcialidad previa
								}

								$impSaldoInsoluto = $impPagadodr - $impSaldoAnterior;
		        				$impSaldoInsoluto = str_replace(array(",","-"), "",number_format($impSaldoInsoluto,2));
							}
						}

						if($idDocumento=="" || $idDocumento==NULL){$valguarda=1;}

						#validacion de campos obligatorios
						$val_metodo_pago   = 'class="fieldrequired"';
						$val_imp_saldo_ant = '';
						$val_imp_saldo_ins = '';
						$val_equivalencia  = '';
						$val_num_par       = '';

						if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
							$val_metodo_pago   = '';
							$val_imp_saldo_ant = 'class="fieldrequired"';
							$val_imp_saldo_ins = 'class="fieldrequired"';
							$val_equivalencia  = 'class="fieldrequired"';
							$val_num_par       = 'class="fieldrequired"';
							$metodoPDR         = '';
						}

						##Inica Extracción de Información guardada
						$sql_impuestos_guardados = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
						$sql_impuestos_guardados .= " WHERE";
							$sql_impuestos_guardados .= " entity = ".$conf->entity;
							$sql_impuestos_guardados .= " AND fk_pago = ".$id;
							$sql_impuestos_guardados .= " AND uuid_doc_rel = '".$idDocumento."'";
						$sql_impuestos_guardados .= " ORDER BY tipo, impuesto ASC";
						// print $sql_impuestos_guardados;

						$res_impuestos_guardados = $db->query($sql_impuestos_guardados);
						$num_impuestos_guardados = $db->num_rows($res_impuestos_guardados);

						if($num_impuestos_guardados > 0){
							$impuestos_aplicados = array();
							while($obj_tmp_impuestos = $db->fetch_object($res_impuestos_guardados)){
								$info_impuesto =  array(
									"base"       => $obj_tmp_impuestos->base,
									"impuesto"   => $obj_tmp_impuestos->impuesto,
									"tipoFactor" => $obj_tmp_impuestos->tipo_factor,
									"tasaOCuota" => $obj_tmp_impuestos->tasa_o_cuota,
									"importe"    => $obj_tmp_impuestos->importe,
									"tipo"       => $obj_tmp_impuestos->tipo
								);

								array_push($impuestos_aplicados, $info_impuesto);
							}
						}else{
							$serie_folio_tmp = $docSerie."-".$docFolio;
							$impuestos_aplicados = $pagos->getImpuestosDoctoRel($serie_folio_tmp, $idDocumento);
						}
						// print '<pre>'; print_r($impuestos_aplicados); print '</pre>';
						##Termina Extracción de Información guardada

						print '<div class="fichecenter">';
							print '<table class="noborder" style="margin-bottom: auto;">';
								print '<tr class="liste_titre">';
									print '<td colspan="4" align="center" >';
										print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>&nbsp;';
										print '<strong>Documento Relacionado</strong>';
										print '&nbsp;';
										print '(<strong>'.$invoice->getNomUrl(1).'</strong>)';
									print '</td>';
								print '</tr>';

								// $valguarda =1;
								if($valguarda == 1){
									$mensaje = "La Factura ".$invoice->getNomUrl(1)." no esta Timbrada por lo cual no se puede realizar el Complemento de Pagos.";
									print '<tr>';
										print '<td colspan="4" align="center">';
											print '<div class="error hideonsmartphone clearboth">';
												print $mensaje;
											print '</div>';
										print '</td>';
									print '</tr>';
								}
							print '</table>';

							print '<input type="hidden" name="idFacturas[]" value="'.$invoice->id.'" >';
							print '<input type="hidden" name="docSerie[]" value="'.$docSerie.'">';
							print '<input type="hidden" name="docFolio[]" value="'.$docFolio.'">';

							print '<div class="fichehalfleft">';
								print '<table class="noborder">';
									print '<thead>';
										print '<tr>';
											print '<td class="fieldrequired">Moneda</td>';
											print '<td><input type="text" name="monedaDR[]" value="'.$monedaDR.'" class="monedadoc"></td>';
										print '</tr>';
									print '</thead>';

									print '<tr>';
										print '<td>Tipo de Cambio</td>';
										print '<td><input type="text" name="tipocambiodr[]" value="'.$tipocambiodr.'" ></td>';
									print '</tr>';

									print '<tr>';
										print '<td '.$val_imp_saldo_ant.'>Importe Saldo Anterior</td>';
										print '<td><input type="text" name="impSaldoAnterior[]" value="'.$impSaldoAnterior.'" ></td>';
									print '</tr>';

									print '<tr>';
										print '<td '.$val_imp_saldo_ins.'>Importe Saldo Insoluto</td>';
										print '<td><input type="text" name="impSaldoInsoluto[]" value="'.$impSaldoInsoluto.'" ></td>';
									print '</tr>';

								print '</table>';
							print '</div>';

							print '<div class="fichehalfright">';
								print '<table class="noborder">';

									print '<thead>';
										print '<tr>';
											print '<td class="fieldrequired">UUID</td>';
											print '<td>';
												print $idDocumento;
												print '<input type="hidden" name="idDocumento[]" value="'.$idDocumento.'" size="40">';
											print '</td>';
										print '</tr>';
									print '</thead>';

									print '<tr>';
										if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
											print '<input type="hidden" name="metodoPDR[]" value="'.$metodoPDR.'" >';
										}else{
											print '<td '.$val_metodo_pago.'>Método de Pago</td>';
											print '<td>';
												print '<input type="text" name="metodoPDR[]" value="'.$metodoPDR.'" >';
											print '</td>';
										}
									print '</tr>';

									print '<tr>';
										print '<td '.$val_num_par.'>Número de Parcialidad</td>';
										print '<td><input type="text" name="numparcialidaddr[]" value="'.$numparcialidaddr.'" ></td>';
									print '</tr>';

									print '<tr>';
										print '<td class="fieldrequired">Importe Pagado</td>';
										print '<td><input type="text" name="impPagadodr[]" value="'.$impPagadodr.'" ></td>';
									print '</tr>';

									print '<tr>';
										print '<td '.$val_equivalencia.'>Equivalencia</td>';
										print '<td><input type="text" name="equivalenciadr[]" value="'.$equivalenciadr.'" placeholder="'.$ayuda_equivalencia.'" title="'.$ayuda_equivalencia.'" class="equivalenciadoc" id="equivalenciadr'.$var_contador.'"></td>';
									print '</tr>';
								print '</table>';
							print '</div>';

							if($impuestos_aplicados != null){
								print '<div class="fichecenter">';
									print '<table class="noborder" style="margin-top: auto;">';
										print '<tr class="liste_titre">';
											print '<td colspan="6" align="center">';
												print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>&nbsp;';
												print '<strong>Impuestos Relacionados</strong>';
												print '&nbsp;';
												print '(<strong>'.$invoice->getNomUrl(1).'</strong>)';
											print '</td>';
										print '</tr>';

										print '<tbody>';

											print '<tr class="liste_titre" style="text-align: center;">';
												print '<th><strong>Tipo</strong></th>';
												print '<th><strong>Base</strong></th>';
												print '<th><strong>Impuesto</strong></th>';
												print '<th><strong>Tipo Factor</strong></th>';
												print '<th><strong>Tasa O Cuota</strong></th>';
												print '<th><strong>Importe</strong></th>';
											print '</tr>';

											foreach($impuestos_aplicados as $impuesto){
												$ajuste_monto = 0;
												$importe_tras_ret = $impuesto["importe"];
												$tasa_o_cuota     = $impuesto["tasaOCuota"];

												print '<tr>';
													print '<td>';
														print '<input type="hidden" name="tipo_trasret[]" id="tipo_trasret" value="'.$impuesto["tipo"].'">';
														if($impuesto["tipo"] == 1){
															print '<span class="badge badge-status8 badge-status">'.$langs->trans("TipoImp".$impuesto["tipo"]).'</span>';
														}else{
															print '<span class="badge badge-status4 badge-status">'.$langs->trans("TipoImp".$impuesto["tipo"]).'</span>';
														}
													print '</td>';

													print '<td>';
														print '<input type="hidden" name="uuid_docto_rel[]" id="uuid_docto_rel" value="'.$idDocumento.'">';
														print '<input type="text" name="base_trasret[]" id="base_trasret" value="'.$impuesto["base"].'" style="text-align: center;">';
													print '</td>';

													print '<td>';
														print '<input type="text" name="impuesto_trasret[]" id="impuesto_trasret" value="'.$impuesto["impuesto"].'" style="text-align: center;" title="'.$langs->trans("Imp".$impuesto["impuesto"]).'">';
													print '</td>';

													print '<td>';
														print '<input type="text" name="tipofactor_trasret[]" id="tipofactor_trasret" value="'.$impuesto["tipoFactor"].'" style="text-align: center;">';
													print '</td>';

													print '<td>';
														print '<input type="text" name="tasacuota_trasret[]" id="tasacuota_trasret" value="'.$tasa_o_cuota.'" style="text-align: center;">';
													print '</td>';

													print '<td>';
														print '<input type="text" name="importe_trasret[]" id="importe_trasret" value="'.$importe_tras_ret.'" style="text-align: center;">';
													print '</td>';
												print '</tr>';
											}

										print '</tbody>';
									print '</table>';
								print '</div>';
							}

							$var_contador++;

						print '</div>';

						$i++;
					}
				}
				$var=!$var;

				$db->free($resql);
			}

			print '<script type="text/javascript" language="javascript">';
				print '$(document).ready(function () {
				  			$("#monedapago").change(function() {
								var moneda_sel = document.getElementById("monedapago").value;

								console.log(moneda_sel);

								if(moneda_sel == "MXN"){
									document.getElementById("tipocambio").value = "1";
								}else{
									document.getElementById("tipocambio").value = "";
								}

								var monedas_documentos = document.getElementsByClassName("monedadoc");
								var equivalencia_documentos = document.getElementsByClassName("equivalenciadoc");

								for(var i = 0; i < monedas_documentos.length; i++ ){
									if(monedas_documentos[i].value != ""){
										if(moneda_sel == monedas_documentos[i].value){
											//equivalencia_documentos[i].value = "1";
											document.getElementById("equivalenciadr"+i).value = "1";
										}else{
											//var ayuda_equi = "1 "+monedas_documentos[i].value+" = ¿XXX "+moneda_sel+"?";
											var ayuda_equi = "1 "+moneda_sel+" = ¿XXX "+monedas_documentos[i].value+"?";
											document.getElementById("equivalenciadr"+i).placeholder = ayuda_equi;
											document.getElementById("equivalenciadr"+i).value = "";
										}
									}else{
										var ayuda_equi = "1 ¿XXX?"+" = "+monedas_documentos[i].value;
										document.getElementById("equivalenciadr"+i).placeholder = ayuda_equi;
										document.getElementById("equivalenciadr"+i).value = "";
									}
								}
							});
			  			});';
			print '</script>';

		print '</form>';

		// print $existepagos.'<br>';
		// print $valguarda.'<br>';

		if($existepagos != 1 && $valguarda != 1){
			print '<div class="warning hideonsmartphone clearboth">';
				print '<span class="fa fa-info-circle valignmiddle btnTitle-icon"></span>&nbsp;';
				print '<strong>Es necesario que guarde la información del Complemento de Pago para poder timbrar.</strong>';
			print '</div>';
		}

		if($valguarda >= 0){
			$client = new nusoap_client($wscfdi, 'wsdl');
			$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));

			$status_clt         = $result["return"]["status_cliente_id"];
			$status_clt_desc    = $result["return"]["status_cliente_desc"];
			$folios_timbrados   = $result["return"]["folios_timbrados"];
			$folios_adquiridos  = $result["return"]["folios_adquiridos"];
			$folios_disponibles = $result["return"]["folios_disponibles"];

			print '<div class="fichecenter">';
				print '<div class="tabsAction" width="100%">';

					if($user->rights->cfdimx->add_comp_fac_global == 1){
						if($existepagos != 1 && $valguarda != 1){
						}else{
							print '<div class="inline-block divButAction">';
								print '<form method="POST" action="pagosfacturas.php?id='.$id.'" method="post">';
									print '<input type="hidden" name="token" id="token" value="'.newToken().'">';
									print '<input type="hidden" name="funcion_especial" value="1">';
									print '<input type="submit" class="butAction" name="del_info_pago" id="del_info_pago" value="Eliminar Información" >';
								print '</form>';
							print '</div>';
						}
					}

					print '<div class="inline-block divButAction">';
						print '<input type="submit" name="guardar" value="Guardar informacion" class="butAction" form="formulario_guardar">';
					print '</div>';

					if($folios_disponibles > 0){
						if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
							if($existepagos == 1){
								print '<div class="inline-block divButAction">';
									print '<form method="POST" action="pagos/pdf_previo.php" method="post">';
										print '<input type="hidden" name="token" id="token" value="'.newToken().'">';
										print '<input type="hidden" name="pagcid" value="'.$id.'">';
										print '<input type="submit" class="butAction" name="preview_pdf" id="preview_pdf" value="Previsualizar PDF" >';
									print '</form>';
								print '</div>';

								print '<div class="inline-block divButAction">';
									print '<form method="POST" action="pagos/generaCFDI_pagos.php" method="post">';
										print '<input type="hidden" name="token" id="token" value="'.newToken().'">';
										print '<input type="hidden" name="facid" value="0">';
										print '<input type="hidden" name="pagcid" value="'.$id.'">';
										print '<input type="hidden" name="action" value="generaCFDI">';
										print '<input type="submit" class="butAction" name="preview_pdf" id="preview_pdf" value="Generar CFDI">';
									print '</form>';
								print '</div>';
							}
						}else{
							if($existepagos == 1){
								print '<div class="inline-block divButAction">';
									print '<a class="butAction" href="pagos/generaCFDI2.php?facid=0&pagcid='.$id.'&action=generaCFDI">Generar CFDI</a>';
								print '</div>';
							}
						}
					}
				print '</div>';
			print '</div>';

			if($existepagos == 1){
				print '<div class="fichecenter">';
					print '<div class="fichehalfleft">';
						$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $conf->entity;
						$resql=$db->query($sql);
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

						//Agregado para cancelación de complemento (Inicio)
						$sql='SELECT cancelado FROM '.MAIN_DB_PREFIX.'cfdimx_recepcion_pagos WHERE fk_paiement='.GETPOST('id');
						//echo $sql;

						$resql=$db->query($sql);
						if ($db->num_rows($resql) > 0) {
							while ($res = $db->fetch_object($resql)) {
								$status_cancel = $res->cancelado;
							}
						}

						print '<table class="noborder" width="100%">';
							#Datos de timbrado
							if((int)DOL_VERSION == 10){
								print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
									print '<td style="border: solid 1px #990000;" colspan="2" align="center">';
										print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>&nbsp;';
										print '<b>Datos de Timbrado</b>';
									print '</td>';
								print '</tr>';
							}else{
								print '<tr style="background: #990000; color: white;">';
									print '<td style="border: solid 1px #990000;" colspan="2" align="center">';
										print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>&nbsp;';
										print '<b>Datos de Timbrado</b>';
									print '</td>';
								print '</tr>';
							}

							print '<tr>';
								print '<td colspan="2" style="border: solid 1px #990000;">';
									print '<table width="100%" class="noborder">';
										print '<tr>';
											print '<td><strong>Estado CFDI</strong></td>';
											print '<td>Sin Timbrar</td>';
										print '</tr>';

										print '<tr>';
											print '<td><strong>UUID:</strong></td>';
											print '<td>' . $uuidP . '</td>';
										print '</tr>';

										$modo_timbrado_desc = ($modo_timbrado == 1) ? "Producción" : "Pruebas";
										print '<tr>';
											print '<td><strong>Modo de Timbrado Activo:</strong></td>';
											print '<td>' . $modo_timbrado_desc . '</td>';
										print '</tr>';

										print '<tr>';
											print '<td><strong>Versión de CFDI Activa:</strong></td>';
											print '<td>' . $conf->global->CFDIMX_VERSION_SAT . '</td>';
										print '</tr>';

										print '<tr>';
											print '<td><strong>Folios Disponibles:</strong></td>';
											print '<td>' . $folios_disponibles . '</td>';
										print '</tr>';

										print '<tr>';
											print '<td><strong>Folios Timbrados:</strong></td>';
											print '<td>' . $folios_timbrados . '</td>';
										print '</tr>';
									print '</table>';
								print '</td>';
							print '</tr>';
						print '</table>';
						print '<br>';
					print '</div>';

					#Listado de archivos#
					$out_files="";
					$filedir=DOL_DATA_ROOT.'/facture/'.$object->ref.'/';
					$file_list=dol_dir_list($filedir,'files',0,'','\.meta$','date',SORT_DESC);

					// Loop on each file found
					if (is_array($file_list))
					{
						foreach($file_list as $file)
						{
							$aux_ext = explode(".", $file['name']);
							$ext = $aux_ext[1];

							if (in_array($ext, array("pdf"))){
								$out_lupa = '<a class="pictopreview documentpreview" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&amp;attachment=0&amp;file='.$file['level1name'].'/'.$file['name'].'" mime="application/pdf" target="_blank"><span class="fa fa-search-plus" style="color: gray"></span></a>';
							}
							else {
								$out_lupa = "";
							}

							$out_files.= '<tr>';
							//Ruta antigua para eliminar
							$out_files.='
								<td class="minwidth200">
									<a class="documentdownload paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&file='.$file['level1name'].'/'.$file['name'].'" target="_blank"><i class="fa fa-file-pdf-o paddingright"></i>'.$file['name'].'</a>
									'.$out_lupa.'
								</td>
								<td align="right" class="nowrap">'.filesize($file['fullname']).' Bytes</td>
								<td align="right" class="nowrap">'.dol_print_date($file['date'], "%H:%M %d/%m/%Y").'</td>';
							$out_files.= '</tr>';
						}
					}

					if($out_files != ""){
						print '<div class="fichehalfright">';
							print '<form action="generaPDF_new.php?facid='.$id.'" id="builddoc_form" method="post">';
								print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;">';
									print '<tbody>';
										print '<tr>';
											print '<td class="nobordernopadding" valign="middle">';
												print '<div class="titre">Archivos vinculados</div>';
											print '</td>';
										print '</tr>';
									print '</tbody>';
								print '</table>';

								print '<div class="div-table-responsive-no-min">';
									print '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';
										print '<tbody>';
											print '<tr class="liste_titre">';
												print '<td colspan="3">';
													print '<div align="center" width="100%">';
														print '<strong>Documentos</strong>';
													print '</div>';
												print '</td>';
											print '</tr>';
											print $out_files;
										print '</tbody>';
									print '</table>';
								print '</div>';
							print '</form>';
							print '<br>';
						print '</div>';
					}
				print '</div>';
			}
		}

	}

	##Vista de un Pago Timbrado
	if($action=="cfdi1"){
		$datep="";
		$formpago="";
		$monedapago="";
		$tipocambio="";

		if($accountstatic->account_currency_code == "MXN"){
			$tipocambio = "1";
		}

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

		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$id;
		//print $sql;
		$req=$db->query($sql);
		$nmr=$db->num_rows($req);
		$idrecpago=0;
		if($nmr>0){
			$rsl=$db->fetch_object($req);
			$idrecpago=$rsl->rowid;
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
			$tipo_relacion=$rsl->tipo_rel;
		}

		$sql_list_uuid_rel  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
		$sql_list_uuid_rel .= " WHERE fk_pago = ".$id;
		// print $sql_list_uuid_rel;
		$res_list_uuid_rel  = $db->query($sql_list_uuid_rel);
		$num_list_uuid_rel  = $db->num_rows($res_list_uuid_rel);

		if($num_list_uuid_rel > 0){
			print '<div class="fichecenter">';
				print '<table class="noborder" style="margin-bottom: auto;">';
					print '<tr class="liste_titre">';
						print '<td colspan="4" align="center" >';
							print '<span class="fa fa-money"></span>&nbsp;';
							print '<strong>CFDI Relacionados</strong>';
						print '</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Tipo Relación</td>';
						print '<td>';
							print $pagos->obtener_rel_pagos($tipo_relacion, 'tipo_relacion', 1, 1);
						print '</td>';
					print '</tr>';

					$lista_uuid_rel_view = array();
					$num_rel_uuid = 1;
					while($obj_uuid_rel = $db->fetch_object($res_list_uuid_rel)){
						$lista_uuid_rel_view[] = $num_rel_uuid.".- ".$obj_uuid_rel->uuid;
						$num_rel_uuid++;
					}

					print '<tr>';
						print '<td>UUID Relacionados</td>';
						print '<td>';
							print implode("<br>", $lista_uuid_rel_view);
						print '</td>';
					print '</tr>';

				print '</table>';
			print '</div>';
			print '<br>';
		}

		print '<div class="fichecenter">';
			print '<table class="noborder" style="margin-bottom: auto;">';
				print '<tr class="liste_titre">';
					print '<td colspan="4" align="center" >';
						print '<span class="fa fa-money"></span>&nbsp;';
						print '<strong>Datos del Complemento de Pagos</strong>';
					print '</td>';
				print '</tr>';
			print '</table>';

			print '<div class="fichehalfleft">';
				print '<table class="noborder">';
					print '<thead>';
						print '<tr>';
							print '<td class="fieldrequired">Fecha</td>';
							print '<td>';
								print date("Y-m-d H:i:s",$datep);
							print '</td>';
						print '</tr>';
					print '</thead>';

					print '<tr>';
						print '<td class="fieldrequired">Moneda</td>';
						print '<td>';
							// if($num_select_monedas > 0){
							// 	print $pagos->obtener_catalogo($monedapago, 'monedapago', 3, 1);
							// }else{
								print $pagos->obtener_catalogo($monedapago, 'monedapago', 4, 1);
							// }
						print '</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Tipo de cambio</td>';
						print '<td>'.$tipocambio.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>RFC Emisor Cuenta Ordenante</td>';
						print '<td>'.$rfcemisorctaorigen.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Cuenta Ordenante</td>';
						print '<td>'.$ctaordenante.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Cuenta Beneficiario</td>';
						print '<td>'.$ctabeneficiario.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Certificado del Pago</td>';
						print '<td>';
							print $certificadopago;
						print '</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Sello Pago</td>';
						print '<td>';
							print $sellopago;
						print '</td>';
					print '</tr>';

				print '</table>';
			print '</div>';

			print '<div class="fichehalfright">';
				print '<table class="noborder">';
					print '<thead>';
						print '<tr>';
							print '<td class="fieldrequired"><strong>Forma de Pago</strong></td>';
							print '<td>';
								print $pagos->obtener_catalogo($formpago, 'formpago', 2, 1);
							print '</td>';
						print '</tr>';
					print '</thead>';

					print '<tr>';
						print '<td class="fieldrequired">Monto</td>';
						print '<td>'.price($montop, 0, '', 1, -1, -1, $monedapago).'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Numero de operacion</td>';
						print '<td>'.$numoperacion.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Nom. Banco Ordenante (Ext)</td>';
						print '<td>'.$nombancoordenante.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>RFC Emisor Cuenta Beneficiario</td>';
						print '<td>'.$rfcemisorctabeneficiario.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Tipo Cadena de Pago</td>';
						print '<td>'.$tipocadenapago.'</td>';
					print '</tr>';

					print '<tr>';
						print '<td>Cadena Original del comprobante pago</td>';
						print '<td>';
							print $cadenaoriginal;
						print '</td>';
					print '</tr>';

				print '</table>';
			print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, pf.multicurrency_amount, s.nom as name, s.rowid as socid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
		$sql.= ' WHERE pf.fk_facture = f.rowid';
		$sql.= ' AND f.fk_soc = s.rowid';
		$sql.= ' AND f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_paiement = '.$object->id;
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$thirdpartystatic = new Societe($db);
			$i = 0;
			$total = 0;

			$moreforfilter='';
			$valguarda=0;
			$var_contador = 0;

			if ($num > 0)
			{
				$var=True;

				while ($i < $num)
				{
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

					$objp = $db->fetch_object($resql);
					$var=!$var;
					$invoice=new Facture($db);
					$invoice->fetch($objp->facid);

					// sergi 23/feb/2022
					$monedafactura = $invoice->multicurrency_code;
					if ($monedafactura!='MXN'){
						$invoicetotal = $invoice->multicurrency_total_ttc;
						$paiement     = $invoice->getSommePaiement(1);
						$creditnotes  = $invoice->getSumCreditNotesUsed(1);
						$deposits     = $invoice->getSumDepositsUsed(1);
						$alreadypayed = price2num($paiement + $creditnotes + $deposits,'MT');
					}else{
						$invoicetotal = $invoice->total_ttc;
						$paiement     = $invoice->getSommePaiement();
						$creditnotes  = $invoice->getSumCreditNotesUsed();
						$deposits     = $invoice->getSumDepositsUsed();
						$alreadypayed = price2num($paiement + $creditnotes + $deposits,'MT');
					}
					$remaintopay=price2num($invoicetotal - $paiement - $creditnotes - $deposits,'MT');

					// equivalencia entre la moneda del pago y el de la factura
					$ayuda_equivalencia = "";
					if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
						if($monedapago == $monedafactura){
							$equivalenciadr = 1;
						}else{
							$ayuda_equivalencia = '1 '.$monedapago.' = ¿XXX '.$monedafactura.'?';
						}
					}

					$facid = $objp->facid;

					$thirdpartystatic->id=$objp->socid;
					$thirdpartystatic->name=$objp->name;

					$extrae=explode("-", $invoice->ref);
					$alc=0;
					if(count($extrae)>1){
						$docSerie=$extrae["0"];
						$docFolio=$extrae["1"];
						$alc=1;
					}else{
						$docFolio=$extrae["0"];
					}

					$sqlval="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$objp->facid." AND fk_paiement=".$id;
					$reqval=$db->query($sqlval);
					$nmrval=$db->num_rows($reqval);
					//print $sqlval." :: ".$nmrval."<br>";
					if($nmrval>0){
						$existepagos=1;
					}

					if($idrecpago>0){
						if($alc==1){
							$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
									WHERE fk_recepago=".$idrecpago." AND serie='".$docSerie."' AND folio='".$docFolio."'";
						}else{
							$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
									WHERE fk_recepago=".$idrecpago." AND folio='".$docFolio."'";
						}
						// print $sql."<br>";
						$req=$db->query($sql);
						$nmr=$db->num_rows($req);
						if($nmr>0){
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
						}
					}

					if($idDocumento=="" && $monedaDR=="" && $metodoPDR=="" && $impPagadodr==""){
						//$sql="SELECT a.uuid, a.divisa, c.accountancy_code FROM ".MAIN_DB_PREFIX."cfdimx a, ".MAIN_DB_PREFIX."facture b,".MAIN_DB_PREFIX."c_paiement c WHERE a.fk_facture=".$invoice->id." AND a.fk_facture=b.rowid AND b.fk_mode_reglement=c.id"; //query original
						$sql='SELECT cfdi.uuid, cfdi.divisa FROM '.MAIN_DB_PREFIX.'cfdimx cfdi WHERE cfdi.fk_facture='.$invoice->id;
						//print $sql."<br>";
						$rq=$db->query($sql);
						if ($db->num_rows($rq) > 0) {
							$rs=$db->fetch_object($rq);
							$monedaDR=$rs->divisa;
							$idDocumento=$rs->uuid;
						}

						$sqlm="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'formpagcfdi'";
						$resqlv=$db->query($sqlm);
						if( $db->num_rows($resqlv) > 0 ){
							$sqlv="SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".$invoice->id;
							$rv=$db->query($sqlv);
							$vrs=$db->fetch_object($rv);
							$metodoPDR = $vrs->formpagcfdi;
						}

						if($monedaDR == "MXN"){
							$impPagadodr=str_replace(array(",","-"), "",number_format($objp->amount,2));
						}else{
							$impPagadodr=str_replace(array(",","-"), "",number_format($objp->multicurrency_amount,2));
						}
						// print '<pre>'; print_r($objp); print '</pre>';

						if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
							$numparcialidaddr = 1;
							//$impSaldoAnterior = str_replace(array(",","-"), "",number_format($invoice->total_ttc,2));
							// sergi 23/feb/2022
							$impSaldoAnterior = str_replace(array(",","-"), "",number_format($invoicetotal,2));

							if($idrecpago <= 0){
								##Iinicio para verificar si tiene una parcialidad previa
								$sql_parcialidad = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado";
								$sql_parcialidad .= " WHERE idDocumento = '".$idDocumento."'";
								$sql_parcialidad .= " ORDER BY rowid DESC LIMIT 1";

								$res_parcialidad = $db->query($sql_parcialidad);
								$num_parcialidad = $db->num_rows($res_parcialidad);

								if($num_parcialidad > 0){
									$obj_documento = $db->fetch_object($res_parcialidad);
									$numparcialidaddr = $obj_documento->numParcialidad + 1;
									$impSaldoAnterior = str_replace(array(",","-"), "",number_format($obj_documento->impSaldoInsoluto,2));
								}
								##Termina para verificar si tiene una parcialidad previa
							}

							$impSaldoInsoluto = $impPagadodr - $impSaldoAnterior;
							$impSaldoInsoluto = str_replace(array(",","-"), "",number_format($impSaldoInsoluto,2));
						}
					}

					if($idDocumento=="" || $idDocumento==NULL){$valguarda=1;}

					#validacion de campos obligatorios
					$val_metodo_pago   = 'class="fieldrequired"';
					$val_imp_saldo_ant = '';
					$val_imp_saldo_ins = '';
					$val_equivalencia  = '';
					$val_num_par       = '';

					if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
						$val_metodo_pago   = '';
						$val_imp_saldo_ant = 'class="fieldrequired"';
						$val_imp_saldo_ins = 'class="fieldrequired"';
						$val_equivalencia  = 'class="fieldrequired"';
						$val_num_par       = 'class="fieldrequired"';
						$metodoPDR         = '';
					}

					##Inica Extracción de Información guardada
					$sql_impuestos_guardados = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
					$sql_impuestos_guardados .= " WHERE";
						$sql_impuestos_guardados .= " entity = ".$conf->entity;
						$sql_impuestos_guardados .= " AND fk_pago = ".$id;
						$sql_impuestos_guardados .= " AND uuid_doc_rel = '".$idDocumento."'";
					$sql_impuestos_guardados .= " ORDER BY tipo, impuesto ASC";
					// print $sql_impuestos_guardados;

					$res_impuestos_guardados = $db->query($sql_impuestos_guardados);
					$num_impuestos_guardados = $db->num_rows($res_impuestos_guardados);

					if($num_impuestos_guardados > 0){
						$impuestos_aplicados = array();
						while($obj_tmp_impuestos = $db->fetch_object($res_impuestos_guardados)){
							$info_impuesto =  array(
								"base"       => $obj_tmp_impuestos->base,
								"impuesto"   => $obj_tmp_impuestos->impuesto,
								"tipoFactor" => $obj_tmp_impuestos->tipo_factor,
								"tasaOCuota" => $obj_tmp_impuestos->tasa_o_cuota,
								"importe"    => $obj_tmp_impuestos->importe,
								"tipo"       => $obj_tmp_impuestos->tipo
							);

							array_push($impuestos_aplicados, $info_impuesto);
						}
					}
					// print '<pre>'; print_r($impuestos_aplicados); print '</pre>';
					##Termina Extracción de Información guardada

					print '<div class="fichecenter">';
						print '<table class="noborder" style="margin-bottom: auto;">';
							print '<tr class="liste_titre">';
								print '<td colspan="4" align="center" >';
									print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>&nbsp;';
									print '<strong>Documento Relacionado</strong>';
									print '&nbsp;';
									print '(<strong>'.$invoice->getNomUrl(1).'</strong>)';
								print '</td>';
							print '</tr>';
						print '</table>';

						print '<div class="fichehalfleft">';
							print '<table class="noborder">';
								print '<thead>';
									print '<tr>';
										print '<td class="fieldrequired">Moneda</td>';
										print '<td>';
											if($num_select_monedas > 0){
												print $pagos->obtener_catalogo($monedaDR, 'monedaDR', 3, 1);
											}else{
												print $pagos->obtener_catalogo($monedaDR, 'monedaDR', 4, 1);
											}
										print '</td>';
									print '</tr>';
								print '</thead>';

								print '<tr>';
									print '<td>Tipo de Cambio</td>';
									print '<td>'.$tipocambiodr.'</td>';
								print '</tr>';

								print '<tr>';
									print '<td '.$val_imp_saldo_ant.'>Importe Saldo Anterior</td>';
									print '<td>'.price($impSaldoAnterior, 0, '', 1, -1, -1, $monedaDR).'</td>';
								print '</tr>';

								print '<tr>';
									print '<td '.$val_imp_saldo_ins.'>Importe Saldo Insoluto</td>';
									print '<td>'.price($impSaldoInsoluto, 0, '', 1, -1, -1, $monedaDR).'</td>';
								print '</tr>';

							print '</table>';
						print '</div>';

						print '<div class="fichehalfright">';
							print '<table class="noborder">';
								print '<thead>';
									print '<tr>';
										print '<td class="fieldrequired">UUID</td>';
										print '<td>';
											print $idDocumento;
										print '</td>';
									print '</tr>';
								print '</thead>';

								print '<tr>';
									if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){

									}else{
										print '<td '.$val_metodo_pago.'>Método de Pago</td>';
										print '<td>';
											print $metodoPDR;
										print '</td>';
									}
								print '</tr>';

								print '<tr>';
									print '<td '.$val_num_par.'>Número de Parcialidad</td>';
									print '<td>'.$numparcialidaddr.'</td>';
								print '</tr>';

								print '<tr>';
									print '<td class="fieldrequired">Importe Pagado</td>';
									print '<td>'.price($impPagadodr, 0, '', 1, -1, -1, $monedaDR).'</td>';
								print '</tr>';

								print '<tr>';
									print '<td '.$val_equivalencia.'>Equivalencia</td>';
									print '<td>'.$equivalenciadr.'</td>';
								print '</tr>';

							print '</table>';
						print '</div>';

						if($impuestos_aplicados != null){
							print '<div class="fichecenter">';
								print '<table class="noborder" style="margin-top: auto;">';
									print '<tr class="liste_titre">';
										print '<td colspan="6" align="center">';
											print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>&nbsp;';
											print '<strong>Impuestos Relacionados</strong>';
											print '&nbsp;';
											print '(<strong>'.$invoice->getNomUrl(1).'</strong>)';
										print '</td>';
									print '</tr>';

									print '<tbody>';
										print '<tr class="liste_titre" style="text-align: center;">';
											print '<th><strong>Tipo</strong></th>';
											print '<th><strong>Base</strong></th>';
											print '<th><strong>Impuesto</strong></th>';
											print '<th><strong>Tipo Factor</strong></th>';
											print '<th><strong>Tasa O Cuota</strong></th>';
											print '<th><strong>Importe</strong></th>';
										print '</tr>';

										// print '<pre>'; print_r($impuestos_aplicados); print '</pre>';

										foreach($impuestos_aplicados as $impuesto){
											$ajuste_monto = 0;
											$importe_tras_ret = $impuesto["importe"];
											$tasa_o_cuota     = $impuesto["tasaOCuota"];

											print '<tr>';
												print '<td style="text-align: center;">';
													if($impuesto["tipo"] == 1){
														print '<span class="badge badge-status8 badge-status">'.$langs->trans("TipoImp".$impuesto["tipo"]).'</span>';
													}else{
														print '<span class="badge badge-status4 badge-status">'.$langs->trans("TipoImp".$impuesto["tipo"]).'</span>';
													}
												print '</td>';

												print '<td style="text-align: center;">'.price($impuesto["base"], 0, '', 1, -1, -1, $monedaDR).'</td>';
												print '<td style="text-align: center;">'.$langs->trans("Imp".$impuesto["impuesto"]).'</td>';
												print '<td style="text-align: center;">';
													print $impuesto["tipoFactor"];
												print '</td>';
												print '<td style="text-align: center;">';
													print $tasa_o_cuota;
												print '</td>';
												print '<td style="text-align: center;">'.price($importe_tras_ret, 0, '', 1, -1, -1, $monedaDR).'</td>';

											print '</tr>';
										}
									print '</tbody>';
								print '</table>';
							print '</div>';
						}
					print '</div>';

					$i++;
					$var_contador++;
				}
			}
			$var=!$var;

			$db->free($resql);
		}

		if (GETPOST('action') == 'presend') {
			$sql = 'SELECT pf.*, f.ref FROM '.MAIN_DB_PREFIX.'paiement_facture pf INNER JOIN '.MAIN_DB_PREFIX.'facture f ON pf.fk_facture=f.rowid WHERE pf.fk_paiement='.GETPOST('id');
	        $resql = $db->query($sql);
	        if ($db->num_rows($resql) > 0) {
	            while ($res = $db->fetch_object($resql)) {
					// echo "<pre>";
					// print_r($data);
					// echo "</pre>";
					$data[$res->fk_facture] = $res->ref;
	            }
	        }

			$form_questions = array(array('label'=> 'Seleccione la factura de la cual desea enviar sus documentos:','type'=> 'select', 'name'=> 'id_factura_seleccionada', 'values'=>$data));

			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.GETPOST('id'), $langs->trans('Pago asociado a varias facturas'), "", 'confirm_send_facture_mail', $form_questions, "0", 2);
			print $formconfirm;
		}

		if (GETPOST('action') != 'presend' && GETPOST('action') != 'send') {

	        $client = new nusoap_client($wscfdi, 'wsdl');
			$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));
			$status_clt = $result["return"]["status_cliente_id"];
			$status_clt_desc = $result["return"]["status_cliente_desc"];
			$folios_timbrados = $result["return"]["folios_timbrados"];
			$folios_adquiridos = $result["return"]["folios_adquiridos"];
			$folios_disponibles = $result["return"]["folios_disponibles"];

			$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $conf->entity;
			$resql = $db->query($sql);
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

		    if($uuidP != ""){
		    	$factura_tmp = new Facture($db);
				$factura_tmp->fetch($id);

				$cliente_tmp = new Societe($db);
				$cliente_tmp->fetch($factura_tmp->socid);

		        $sql_verificar_estatus = "";
		        $sql_verificar_estatus = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_estatus";
		        $sql_verificar_estatus .= " WHERE uuid = '".$uuidP."'";
		        // $sql_verificar_estatus .= " AND facid = ".$_REQUEST["facid"];
		        $sql_verificar_estatus .= " AND entity = ".$conf->entity;
		        $res_verificar_estatus = $db->query($sql_verificar_estatus);
		        $num_verificar_estatus = $db->num_rows($res_verificar_estatus);

		        if($num_verificar_estatus > 0){
		            $obj_verificar_estatus = $db->fetch_object($res_verificar_estatus);
		            $estado_cfdi            = $obj_verificar_estatus->estado;
		            $estatus_cancelacion    = $obj_verificar_estatus->escancelable;
		            $fecha_consulta_estatus = $obj_verificar_estatus->fecha."&nbsp;".$obj_verificar_estatus->hora;
		        }
		    }else{
		        $estado_cfdi            = "Sin timbrar";
		        $estatus_cancelacion    = "";
		        $fecha_consulta_estatus = "";
		    }

			print '<div class="fichecenter">';
				print '<div class="tabsAction">';

					// Send by mail
					if ($user->rights->facture->invoice_advance->send) {
						print '<div class="inline-block divButAction">';
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.GETPOST('id').'&action=presend">' . $langs->trans('SendMail') . '</a>';
						print '</div>';
					}

					if ($user->rights->cfdimx->delete == 1) {
						print '<div class="inline-block divButAction">';
							print '<a class="butAction" href="pagos/pdf_pagos.php?pagcid='.$id.'&action_pdf=1">Regenerar PDF</a>';
						print '</div>';
					}

					//Agregado para cancelación de complemento (Inicio)
					if ($user->rights->cfdimx->delete == 1) {
						if ($uuidP != "") {
							if($estado_cancel == 0){
								print '<div class="inline-block divButAction">';
									print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&rfc_emisor='.$conf->global->MAIN_INFO_SIREN.'&uuid='.$uuidP.'&action_post=cancel">Solicitar Cancelación CFDI</a>';
								print '</div>';
							}

							$sql_solicitud_canceacion = "";
                            $sql_solicitud_canceacion .= "SELECT count(*) AS solicitudes FROM ".MAIN_DB_PREFIX."cfdimx_solicitud_cancelacion_pago";
                            $sql_solicitud_canceacion .= " WHERE fk_pago = ".GETPOST('id');
                            $res_solicitud_canceacion = $db->query($sql_solicitud_canceacion);
                            $num_solicitudes = 0;

                            if($res_solicitud_canceacion){
                                $obj_solicitud   = $db->fetch_object($res_solicitud_canceacion);
                                $num_solicitudes = $obj_solicitud->solicitudes;
                            }

                            if($num_solicitudes > 0 && $estado_cancel == 0){
                                print '<div class="inline-block divButAction">';
                                    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id='.$id.'&action_post=clasificar_pago">';
                                        print "Clasificar 'Cancelado'";
                                    print '</a>';
                                print '</div>';
                            }
						}
					}
					//Agregado para cancelación de complemento (Fin)
					print $out;
					$out="";
				print '</div>';
			print '</div>';

			print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
					print '<table class="noborder" width="100%">';
	                    #Datos de timbrado
	                    if((int)DOL_VERSION == 10){
	                        print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
	                            print '<td style="border: solid 1px #990000;" colspan="2" align="center">';
									print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>&nbsp;';
	                                print '<b>Datos de Timbrado</b>';
	                            print '</td>';
	                        print '</tr>';
	                    }else{
	                        print '<tr style="background: #990000; color: white;">';
	                            print '<td style="border: solid 1px #990000;" colspan="2" align="center">';
									print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>&nbsp;';
	                                print '<b>Datos de Timbrado</b>';
	                            print '</td>';
	                        print '</tr>';
	                    }

	                    print '<tr>';
	                        print '<td colspan="2" style="border: solid 1px #990000;">';
	                            print '<table width="100%" class="noborder">';

									#
									if($num_verificar_estatus > 0){
		                                print '<tr>';
	                                        print '<td><strong>Estado CFDI</strong></td>';
	                                        print '<td>'.$estado_cfdi.'</td>';
	                                    print '</tr>';

	                                    print '<tr>';
	                                        print '<td><strong>Estatus de cancelación</strong></td>';
	                                        print '<td>'.$estatus_cancelacion.'</td>';
	                                    print '</tr>';

	                                    $titulo_consulta = "Consultar el Estatus del Complemento de Pagos";

                                        print '<tr>';
                                            print '<td><strong>Última Consulta de Estatus</strong></td>';
                                            print '<td>';
                                                print '<form method="post">';
													print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                                    print $fecha_consulta_estatus;
                                                    print '<input type="hidden" name="action_post" value="consultar_estatus">';
                                                    print '<input type="hidden" name="uuid_consulta" value="'.$uuidP.'">';
                                                    print '<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x" title="'.$titulo_consulta.'"><span class="fa fa-search"></span></button>';
                                                print '</form>';
                                            print '</td>';
                                        print '</tr>';
	                                }else{
	                                	$titulo_consulta = "Consultar el Estatus del Complemento de Pagos";

	                                	print '<tr>';
                                            print '<td><strong>Consultar Estatus</strong></td>';
                                            print '<td>';
                                                print '<form method="post">';
													print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                                    print $fecha_consulta_estatus;
                                                    print '<input type="hidden" name="action_post" value="consultar_estatus">';
                                                    print '<input type="hidden" name="uuid_consulta" value="'.$uuidP.'">';
                                                    print '<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x" title="'.$titulo_consulta.'"><span class="fa fa-search"></span></button>';
                                                print '</form>';
                                            print '</td>';
                                        print '</tr>';
	                                }

                                    print '<tr>';
	                                    print '<td><strong>UUID:</strong></td>';
	                                    print '<td>' . $uuidP . '</td>';
	                                print '</tr>';

	                                $modo_timbrado_desc = ($modo_timbrado == 1) ? "Producción" : "Pruebas";
	                                print '<tr>';
	                                    print '<td><strong>Modo de Timbrado Activo:</strong></td>';
	                                    print '<td>' . $modo_timbrado_desc . '</td>';
	                                print '</tr>';

	                                print '<tr>';
	                                    print '<td><strong>Versión de CFDI Activa:</strong></td>';
	                                    print '<td>' . $conf->global->CFDIMX_VERSION_SAT . '</td>';
	                                print '</tr>';

	                                print '<tr>';
	                                    print '<td><strong>Folios Disponibles:</strong></td>';
	                                    print '<td>' . $folios_disponibles . '</td>';
	                                print '</tr>';

	                                print '<tr>';
	                                    print '<td><strong>Folios Timbrados:</strong></td>';
	                                    print '<td>' . $folios_timbrados . '</td>';
	                                print '</tr>';
	                            print '</table>';
	                        print '</td>';
	                    print '</tr>';
	                print '</table>';
	                print '<br>';
				print '</div>';

				$sql_solicitud = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_solicitud_cancelacion_pago";
				$sql_solicitud .= " WHERE fk_pago = ".$id;
				$sql_solicitud .= " ORDER BY rowid DESC LIMIT 1";
				$res_solicitud = $db->query($sql_solicitud);
				$num_solicitud = $db->num_rows($res_solicitud);

				if($num_solicitud > 0){
					print '<div class="fichehalfright">';
						print '<table class="noborder" width="100%">';
							print '<tr class="liste_titre">';
								print '<td colspan="2" align="center" >';
									print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>&nbsp;';
									print '&nbsp;';
									print '<b>Solicitud de Cancelación</b>';
								print '</td>';
							print '</tr>';

							$obj_cancelacion = $db->fetch_object($res_solicitud);

							print '<tr>';
								print '<th style="background-color: #990000; color: white;"><strong>UUID</strong></th>';
								print '<td>'.$obj_cancelacion->uuid.'</td>';
							print '</tr>';

							print '<tr>';
								print '<th style="background-color: #990000; color: white;"><strong>Estatus</strong></th>';
								print '<td>'.$obj_cancelacion->uuidStatusCode.'</td>';
							print '</tr>';

							print '<tr>';
								print '<th style="background-color: #990000; color: white;"><strong>Acuse</strong></th>';
								print '<td>';
									$ruta = DOL_URL_ROOT.'/document.php?modulepart=facture&amp;file='.$object->ref.'/';
									$ruta .= $obj_cancelacion->archivo;

									print '<a class="documentdownload paddingright" href="'.$ruta.'" target="_blank">';
										print '<i class="fa fa-file-pdf-o paddingright"></i>';
										print $obj_cancelacion->archivo;
									print '</a>';
								print '</td>';
							print '</tr>';

							print '<tr>';
								print '<th style="background-color: #990000; color: white;"><strong>Fecha</strong></th>';
								print '<td>'.$obj_cancelacion->fecha.'&nbsp;'.$obj_cancelacion->hora.'</td>';
							print '</tr>';

						print '</table>';
					print '</div>';
				}

				if($num_solicitud > 0){
					print '<div class="fichecenter">';
						print '<div class="fichehalfleft">';
				}else{
					print '<div class="fichehalfright">';
				}
						#Listado de archivos#
						$out_files="";
						$filedir=DOL_DATA_ROOT.'/facture/'.$object->ref.'/';
						$file_list=dol_dir_list($filedir,'files',0,'','\.meta$','date',SORT_DESC);

						// Loop on each file found
						if (is_array($file_list))
						{
							foreach($file_list as $file)
							{
								$aux_ext = explode(".", $file['name']);
								$ext = $aux_ext[1];

								if (in_array($ext, array("pdf"))){
									$out_lupa = '<a class="pictopreview documentpreview" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&amp;attachment=0&amp;file='.$file['level1name'].'/'.$file['name'].'" mime="application/pdf" target="_blank"><span class="fa fa-search-plus" style="color: gray"></span></a>';
								}else {
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
									<td align="right" class="nowrap">'.dol_print_date($file['date'], "%H:%M %d/%m/%Y").'</td>';
								$out_files.= '</tr>';
							}
						}

						if($out_files != ""){
							print '<form action="generaPDF_new.php?facid='.$id.'" id="builddoc_form" method="post">';
								print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;">';
									print '<tbody>';
										print '<tr>';
											print '<td class="nobordernopadding" valign="middle">';
												print '<div class="titre">Archivos vinculados</div>';
											print '</td>';
										print '</tr>';
									print '</tbody>';
								print '</table>';

								print '<div class="div-table-responsive-no-min">';
									print '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';
										print '<tbody>';
											print '<tr class="liste_titre">';
												print '<td colspan="3">';
													print '<div align="center" width="100%">';
														print '<span class="fa fa-file-pdf valignmiddle btnTitle-icon"></span>&nbsp;';
														print '<strong>Documentos</strong>';
													print '</div>';
												print '</td>';
											print '</tr>';
											print $out_files;
										print '</tbody>';
									print '</table>';
								print '</div>';
							print '</form>';
						}
					print '</div>';
				if($num_solicitud > 0){
					print '</div>';
				}
			print '</div>';
		}
	}

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	llxFooter();
?>
