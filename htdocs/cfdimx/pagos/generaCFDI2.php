<?php
	//date_default_timezone_set("America/Mexico_City"); //Codigo viejo huso horario
	require('../../main.inc.php');
	require('../conf.php');
	include('../lib/nusoap/lib/nusoap.php');
	include("../lib/phpqrcode/qrlib.php");
	require('../lib/numero_a_letra.php');
	require_once("../class/facturacfdimx.class.php");
	require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
	// session_start();

	global $conf;

	//Codigo nuevo huso horario
	// if($conf->global->CFDIMX_HUSO_HORARIO == 0) { $zona_horaria = "America/Mexico_City"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 1) { $zona_horaria = "America/Tijuana"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 2) { $zona_horaria = "America/Chihuahua"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 3) { $zona_horaria = "America/Mexico_City"; }
	// if($conf->global->CFDIMX_HUSO_HORARIO == 4) { $zona_horaria = "America/Cancun"; }

	$version_cfdi_sat = $conf->global->CFDIMX_VERSION_SAT;

	$zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
	date_default_timezone_set($zona_horaria);

	$pagcid=GETPOST("pagcid");
	$facid=GETPOST("facid");

	$serie="";
	$folio="";

	$pago=new Paiement($db);
	$pago->fetch($pagcid);
	$facture=new Facture($db);
	$facture->fetch($facid);
	//print_r($pago);
	//print $pago->ref;
	$separa=explode("-", $pago->ref);
	$serie=$separa[0];
	$folio=$separa[1];
	//DATOS DEL HEADER DEL COMPROBANTE
	$header=array();
	if( $serie!="" ){
		$header["serie"]=trim(preg_replace("/ +/"," ",$serie));
	}
	if( $folio!="" ){
		$header["folio"]=trim(preg_replace("/ +/"," ",$folio));
	}

	$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid." AND fk_paiement=".$pagcid;
	$rq=$db->query($sql);
	$respag=$db->fetch_object($rq);

	$fechap=str_replace(" ","T",$respag->fechaPago);
	$header["fecha"]=date("Y-m-d")."T".date("H:i:s");//$fechap;
	$header["subTotal"]=0;
	$header["moneda"]="XXX";
	$header["total"]=0;
	$header["tipoDeComprobante"]="P";
	$header["lugarExpedicion"]=$conf->global->MAIN_INFO_SOCIETE_ZIP;

	//DATOS DEL EMISOR
	$emisor=array();
	// $regimen = $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
	$regimen = $conf->global->CFDIMX_REGIMEN_FISCAL;
	
	$emisor["emisorRFC"]=$conf->global->MAIN_INFO_SIREN;
	// $emisor["nombre"]=$conf->global->MAIN_INFO_SOCIETE_NOM;
	// $razon_social_emisor = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$razon_social_emisor = $conf->global->CFDIMX_RAZON_SOCIAL;
	$emisor["nombre"] = trim(mb_strtoupper($razon_social_emisor));
	$emisor["emisorRegimen"]=$regimen;

	//DATOS DEL RECEPTOR
	$sqlfc="SELECT fk_facture FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_relacion_facturas WHERE fk_relacion_pagos=".$respag->rowid;
	$rqfc=$db->query($sqlfc);
	$redfc=$db->fetch_object($rqfc);
	$facture->fetch($redfc->fk_facture);
	$receptor=array();
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe	WHERE rowid = ".$facture->socid;
	$req=$db->query($sql);
	$val_tmp = 0;
	if($req){
		$resocid=$db->fetch_object($req);	
		$receptor["rfc"]=$resocid->siren;
		$receptor["nombre"]=$resocid->nom;
	}else{
		$val_tmp = 1;
	}

	$factura_usocfdi = $conf->global->CFDIMX_USOCFDI_PAGOS;
	$receptor["usoCFDI"] = $factura_usocfdi;

	// if($factura_usocfdi!=""){
	// 	$receptor["usoCFDI"]=trim(preg_replace("/ +/"," ",$factura_usocfdi));
	// }
	$conceptos[0] = array(
			'descripcion' =>"Pago",
			'cantidad' =>1,
			'valorUnitario'=>0,
			'importe'=>0,
			'unidad'=>"ACT",
			'claveProdServ'=>"84111506"
	);

	//Inicia Nueva obtencion de Datos Fiscales Emisor
	if(strcmp($version_cfdi_sat, "4.0") == 0){
		$receptor = null;
		$objFacturaCFDI = new FacturaCFDI($db);
	    $objFacturaCFDI->entidad = $conf->entity;
	    $num_domicilio_fiscal = $objFacturaCFDI->getDomiciliosFiscalesCliente($facture->socid);

	    if($num_domicilio_fiscal > 0){
	        for ($i=0; $i < count($objFacturaCFDI->lista_domicilios); $i++) { 
	            // print '<pre>'; print_r($objFacturaCFDI->lista_domicilios[$i]); print '</pre>';
	            // $soc_rfc = $objFacturaCFDI->lista_domicilios[$i]->rfc;

	            $receptor["rfc"] = $objFacturaCFDI->lista_domicilios[$i]->rfc;
	            $receptor["usoCFDI"] = trim(preg_replace("/ +/"," ",$factura_usocfdi));
	            $receptor["nombre"] = utf8_decode($objFacturaCFDI->lista_domicilios[$i]->nombre);
	            $receptor["codigoPostal"] = $objFacturaCFDI->lista_domicilios[$i]->cp;
	            $receptor["regimenFiscal"] = $objFacturaCFDI->lista_domicilios[$i]->regimenfiscal;

	            if($objFacturaCFDI->lista_domicilios[$i]->numregidtrib != "" && $objFacturaCFDI->lista_domicilios[$i]->numregidtrib != null){
	            	$receptor["numRegIdTrib"]= $objFacturaCFDI->lista_domicilios[$i]->numregidtrib;
	            }

	            if($objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal != "" && $objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal != null && $objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal != -1){
	            	$receptor["residenciaFiscal"]= $objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal;
	            }

	            //Inicia Ajustes para Cliente Mostrador XAXX010101000, XEXX010101000
	            if(strcmp($receptor["rfc"], "XAXX010101000") == 0 || strcmp($receptor["rfc"], "XEXX010101000") == 0){ 
	            	$receptor["codigoPostal"]  = $header["lugarExpedicion"];
	            	$receptor["regimenFiscal"] = 616;
	            	// $receptor["usoCFDI"] = "P01";
	            }
	            //Termina Ajustes para Cliente Mostrador XAXX010101000
				break;
	        }
	    }

	    $conceptos = null;
	    $conceptos[0] = array(
			'descripcion' =>"Pago",
			'cantidad' =>1,
			'valorUnitario'=>0,
			'importe'=>0,
			'unidad'=>"ACT",
			'claveProdServ'=>"84111506",
			'objetoImp'=>"01",
		);

		$header["version"] = "4.0";
		$header["exportacion"] = "01";
	}	

	// 'importeImpuesto'=>NULL,
	// 'impuesto'=>NULL,// IVA == 002
	// 'tasa'=>NULL,
	$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id=".$conf->entity);
	$obj = $db->fetch_object($resql);
	$modo_timbrado = $obj->modo_timbrado;
	$passwd_timbrado = $obj->password_timbrado_txt;

	$pagos=array();
	$pagos[0]["fechaPago"]=$fechap;
	$pagos[0]["formaDePago"]=$respag->formaDePago;
	$pagos[0]["monedaP"]=$respag->monedaP;

	if($respag->TipoCambioP!=NULL && $respag->TipoCambioP!=""){
		$pagos[0]["tipoCambioP"]=$respag->TipoCambioP;
	}
	$pagos[0]["monto"]=str_replace(",", "", number_format($respag->monto,2));
	if($respag->numOperacion!=NULL && $respag->numOperacion!=""){
		$pagos[0]["numOperacion"]=$respag->numOperacion;
	}
	if($respag->rfcEmisorCtaOrd!=NULL && $respag->rfcEmisorCtaOrd!=""){
		$pagos[0]["rfcEmisorCtaOrd"]=$respag->rfcEmisorCtaOrd;
	}
	if($respag->nomBancoOrdExt!=NULL && $respag->nomBancoOrdExt!=""){
		$pagos[0]["nomBancoOrdExt"]=$respag->nomBancoOrdExt;
	}
	if($respag->ctaOrdenante!=NULL && $respag->ctaOrdenante!=""){
		$pagos[0]["ctaOrdenante"]=$respag->ctaOrdenante;
	}
	if($respag->rfcEmisorCtaBen!=NULL && $respag->rfcEmisorCtaBen!=""){
		$pagos[0]["rfcEmisorCtaBen"]=$respag->rfcEmisorCtaBen;
	}
	if($respag->ctaBeneficiario!=NULL && $respag->ctaBeneficiario!=""){
		$pagos[0]["ctaBeneficiario"]=$respag->ctaBeneficiario;
	}
	if($respag->tipoCadPago!=NULL && $respag->tipoCadPago!=""){
		$pagos[0]["tipoCadPago"]=$respag->tipoCadPago;
	}
	if($respag->certPago!=NULL && $respag->certPago!=""){
		$pagos[0]["certPago"]=$respag->certPago;
	}
	if($respag->cadPago!=NULL && $respag->cadPago!=""){
		$pagos[0]["cadPago"]=$respag->cadPago;
	}
	if($respag->selloPago!=NULL && $respag->selloPago!=""){
		$pagos[0]["selloPago"]=$respag->selloPago;
	}	

	$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$respag->rowid;
	$rq=$db->query($sql);
	$ci=0;
	$monto_total_pagos = 0;
	while($resdocto=$db->fetch_object($rq)){
		$pagos[$ci]["idDocumento"]=$resdocto->idDocumento;
		if($resdocto->serie!=NULL && $resdocto->serie!=""){
			$pagos[$ci]["serie"]=$resdocto->serie;
		}
		if($resdocto->folio!=NULL && $resdocto->folio!=""){
			$pagos[$ci]["folio"]=$resdocto->folio;
		}
		$pagos[$ci]["monedaDR"]=$resdocto->monedaDR;
		if($resdocto->tipoCambioDR!=NULL && $resdocto->tipoCambioDR!=""){
			$pagos[$ci]["tipoCambioDR"]=$resdocto->tipoCambioDR;
		}
		// $pagos[$ci]["metodoDePagoDR"]=$resdocto->metodoDePagoDR;

		if(strcmp($version_cfdi_sat, "4.0") == 0){
			// $pagos["montoTotalPagos"] = $resdocto->impPagado;
			$pagos[$ci]["objetoImpDR"] = "01";
			$pagos[$ci]["equivalenciaDR"] = $resdocto->equivalencia;
			if($pagos[0]["tipoCambioP"] > 0){
				$monto_total_pagos += $resdocto->impPagado * $pagos[0]["tipoCambioP"];
			}else{
				$monto_total_pagos += $resdocto->impPagado;
			}
		}else{
			$pagos[$ci]["metodoDePagoDR"]=$resdocto->metodoDePagoDR;
		}

		if($resdocto->numParcialidad!=NULL && $resdocto->numParcialidad!=""){
			$pagos[$ci]["numParcialidad"]=$resdocto->numParcialidad;
		}
		if($resdocto->impSaldoAnt!=NULL && $resdocto->impSaldoAnt!=""){
			$pagos[$ci]["impSaldoAnt"]=$resdocto->impSaldoAnt;
		}
		if($resdocto->impPagado!=NULL && $resdocto->impPagado!=""){
			$pagos[$ci]["impPagado"]=$resdocto->impPagado;
		}
		if($resdocto->impSaldoInsoluto!=NULL && $resdocto->impSaldoInsoluto!=""){
			$pagos[$ci]["impSaldoInsoluto"]=$resdocto->impSaldoInsoluto;
		}
		$ci++;
	}

	if(strcmp($version_cfdi_sat, "4.0") == 0){
		$pagos[0]["montoTotalPagos"] = number_format($monto_total_pagos, 2, '.', '');
	}

	$adicionales["repagos"]=$pagos;

	if($val_tmp == 1){
		$receptor = null;
	}
	
	if(
		$header != null && $conceptos != null && $emisor != null && $receptor != null && $conf->global->MAIN_INFO_SIREN != null &&
		$passwd_timbrado != null && $adicionales != null
	){
		$client = new nusoap_client($wscfdi, 'wsdl');

		$datos = array(
						"comprobante"=>$header,
						"conceptos"=>$conceptos,
						"emisor"=>$emisor,
						"receptor"=>$receptor,
						"timbrado_usuario"=>$conf->global->MAIN_INFO_SIREN,
						"timbrado_password"=>$passwd_timbrado,
						"adicionales"=>$adicionales
				);

		$result = $client->call("timbraCFDI", $datos);

		$prmsnd["logosmall"]=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;

		if($conf->global->CFDIMX_DEBUG_TIMBRADO == 1){
			$_SESSION['header']          = $header;
			$_SESSION['conceptos']       = $conceptos;
			$_SESSION['emisor']          = $emisor;
			$_SESSION['receptor']        = $receptor;
			$_SESSION['rfc_emisor']      = $rfc_emisor;
			$_SESSION['passwd_timbrado'] = $passwd_timbrado;
			$_SESSION['adicionales']     = $adicionales;
			$_SESSION['resultado']       = $result;
		}

		if( $result["return"]["rsp"]==1 ){

			$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);

			if(file_exists($conf->facture->dir_output."/".$pago->ref)){}else{
				mkdir($conf->facture->dir_output."/".$pago->ref,0700);
			}

			$file_xml = fopen ($conf->facture->dir_output."/".$pago->ref."/Pago_".$result["return"]["uuid"].".xml", "w");
			fwrite($file_xml,utf8_encode($result["return"]["xml"]));
			fclose($file_xml);
			$file_xml_str = $conf->facture->dir_output."/".$pago->ref."/Pago_".$result["return"]["uuid"].".xml";
			try{
				$the_xml = file_get_contents($file_xml_str);
				$sxe = new SimpleXMLElement($the_xml);
				$ns = $sxe->getNamespaces(true);
				$sxe->registerXPathNamespace('t', $ns['cfdi']);
				foreach ($sxe->xpath('//t:Comprobante') as $tfd) {
					$noCertificado = "{$tfd['NoCertificado']}";
				}
			}catch(Exception $e){
				echo $e->getMessage()."<br>";
			}
			$result["return"]["version"]=isset($result["return"]["version"])?$result["return"]["version"]:"1.0";// AMM solucion provicional
			$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);
			$sqm="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
					SET
					xml='".$db->escape($result["return"]["xml"])."',
					cadena='".$db->escape($result["return"]["cadenaOrig"])."',
					version='".$result["return"]["version"]."',
					selloCFD='".$db->escape($result["return"]["selloCFD"])."',
					certificado='".$db->escape($result["return"]["certSAT"])."',
					sello='".$db->escape($result["return"]["selloSAT"])."',
					certEmisor='".$noCertificado."',
					uuid='".$result["return"]["uuid"]."',
					fecha_emision='".$separa_ftimbrado[0]."',
					hora_emision='".$separa_ftimbrado[1]."'
					WHERE fk_facture=0 AND fk_paiement=".$pago->id;
			$rq=$db->query($sqm);
			$sqlm="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=0 AND fk_paiement=".$pago->id;
			$rq=$db->query($sqlm);
			$obj=$db->fetch_object($rq);
			$fechap=str_replace(" ","T",$obj->fechaPago);

			$prmsnd["version"] = $obj->version;
			$prmsnd["uuid"] = $obj->uuid;
			$prmsnd["cadena"] = $obj->cadena;
			$prmsnd["selloCFD"] = $obj->selloCFD;
			$prmsnd["selloSAT"] = $obj->sello;
			$prmsnd["fechaTimbrado"] = $result["return"]["fechaTimbrado"];
			$prmsnd["certificado"] = $obj->certificado;
			$prmsnd["certEmisor"] = $obj->certEmisor;
			$prmsnd["fechaEmision"] =$fechap;
			$prmsnd["coccds"] = "||".$prmsnd["version"]."|".$prmsnd["uuid"]."|".$prmsnd["fechaTimbrado"]."|".$prmsnd["selloCFD"]."|".$obj->certificado."||";
			//$prmsnd["coccds"] = "||".$prmsnd["version"]."|".$prmsnd["uuid"]."|".$prmsnd["fechaTimbrado"]."|".$prmsnd["selloCFD"]."|".$prmsnd["selloSAT"]."||";
			include("generaPDF2.php");
			print "<script>window.location.href='../pagosfacturas.php?id=".$pago->id."&commit=1'</script>";
		}else{
			if($result["return"]["rsp"]!=""){
				//$_SESSION["errorCFDIP"] = $result["return"]["rsp"]." - ".$result["return"]["msg"];
				$_SESSION["errorCFDIP"] = utf8_encode($result["return"]["rsp"]." - ".$result["return"]["msg"]."<br><br>".$result["return"]["msgDetail"]);
			}else{
				//primera vuelta
				$_SESSION["errorCFDIP"] = "No hubo respuesta para la peticion, intente nuevamente";
			}

			if(file_exists($conf->facture->dir_output."/".$pago->ref)){}else{
				mkdir($conf->facture->dir_output."/".$pago->ref,0700);
			}			
			// $pdf->Output($conf->facture->dir_output."/".$pago->ref."/Pago_".$pago->ref.".pdf","F");

			$file_xml = fopen ($conf->facture->dir_output."/".$pago->ref."/Pago_".$pago->ref.".xml", "w");
			fwrite($file_xml,utf8_encode($result["return"]["xml_estructura"]));
			fclose($file_xml);

			// $pdf->Output($conf->facture->dir_output."/".$pago->ref."/Pago_".$prmsnd["uuid"].".pdf","F");

			print "<script>window.location.href='../pagosfacturas.php?id=".$pago->id."&msgerr=1'</script>";
		}
	}else{
		$mensaje = "";

		if($header == null){
			$mensaje .= "La información del Comprobante esta vacía y es obligatoria.<br>";
		}

		if($conceptos == null){
			$mensaje .= "La información de los Conceptos esta vacía y es obligatoria.<br>";
		}

		if($emisor == null){
			$mensaje .= "La información del Emisor esta vacía y es obligatoria.<br>";
		}

		if($receptor == null){
			$mensaje .= "La información del Receptor (Cliente) esta vacía y es obligatoria.<br>";

			if(strcmp($version_cfdi_sat, "4.0") == 0){
				$msg_cfdi_final .= " Para el Timbrado de CFDI 4.0 la información del Receptor (Cliente) se toma de la Pestaña Domicilio Fiscal.";
			}
		}

		if($conf->global->MAIN_INFO_SIREN == null){
			$mensaje .= "El usuario de Timbrado esta vacío y es obligatorio.<br>";
		}

		if($passwd_timbrado == null){
			$mensaje .= "La Contraseña de Timbrado esta vacía y es obligatoria.<br>";
		}

		if($adicionales == null){
			$mensaje .= "La información del Pago esta vacía y es obligatoria.<br>";
		}

		$_SESSION["errorCFDIP"] = $mensaje;

		print "<script>window.location.href='../pagosfacturas.php?id=".$pago->id."&msgerr=1'</script>";
	}


	// print "<br><br>";
	// print_r($header);
	// print "<br><br>";
	// print_r($emisor);
	// print "<br><br>";
	// print_r($receptor);
	// print "<br><br>";
	// print_r($conceptos);
	// print "<br><br>";
	// print_r($pagos);