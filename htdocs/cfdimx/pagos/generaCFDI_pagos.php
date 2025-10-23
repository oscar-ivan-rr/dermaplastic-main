<?php
	require('../../main.inc.php');
	// require('../conf.php');
	include('../lib/nusoap/lib/nusoap.php');
	include("../lib/phpqrcode/qrlib.php");
	require('../lib/numero_a_letra.php');
	require_once("../class/facturacfdimx.class.php");
	require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');

	global $conf;

	$version_cfdi_sat = $conf->global->CFDIMX_VERSION_SAT;
	$wscfdi           = $conf->global->MAIN_MODULE_CFDIMX_WS;
	$zona_horaria     = $conf->global->CFDIMX_HUSO_HORARIO;
	date_default_timezone_set($zona_horaria);

	$pagcid = GETPOST("pagcid");
	$facid  = GETPOST("facid");

	$serie="";
	$folio="";

	$pago = new Paiement($db);
	$pago->fetch($pagcid);

	function limpiar($String){
		$String = str_replace(array('á','à','â','ã','ª','ä'),"a",$String);
			$String = str_replace(array('Á','À','Â','Ã','Ä'),"A",$String);
			$String = str_replace(array('Í','Ì','Î','Ï'),"I",$String);
			$String = str_replace(array('í','ì','î','ï'),"i",$String);
			$String = str_replace(array('é','è','ê','ë'),"e",$String);
			$String = str_replace(array('É','È','Ê','Ë'),"E",$String);
			$String = str_replace(array('ó','ò','ô','õ','ö','º'),"o",$String);
			$String = str_replace(array('Ó','Ò','Ô','Õ','Ö'),"O",$String);
			$String = str_replace(array('ú','ù','û','ü'),"u",$String);
			$String = str_replace(array('Ú','Ù','Û','Ü'),"U",$String);
			$String = str_replace(array('[','^','´','`','¨','~',']'),"",$String);
			$String = str_replace("ç","c",$String);
			$String = str_replace("Ç","C",$String);
			//$String = str_replace("ñ","n",$String);
			//$String = str_replace("Ñ","N",$String);
			$String = str_replace("Ý","Y",$String);
			$String = str_replace("ý","y",$String);
			$String = str_replace("&aacute;","a",$String);
			$String = str_replace("&Aacute;","A",$String);
			$String = str_replace("&eacute;","e",$String);
			$String = str_replace("&Eacute;","E",$String);
			$String = str_replace("&iacute;","i",$String);
			$String = str_replace("&Iacute;","I",$String);
			$String = str_replace("&oacute;","o",$String);
			$String = str_replace("&Oacute;","O",$String);
			$String = str_replace("&uacute;","u",$String);
			$String = str_replace("&Uacute;","U",$String);
			$String = str_replace("'","",$String);
			$String = str_replace("\r\n"," ",$String);
			$String = str_replace("\r"," ",$String);
			$String = str_replace("\n"," ",$String);
		return $String;
	}

	$facture = new Facture($db);

	$separa = explode("-", $pago->ref);
	$serie = $separa[0];
	$folio = $separa[1];

	//Inicia Datos del Comprobante (Header)
	$header=array();
	if($serie != ""){
		$header["serie"]=trim(preg_replace("/ +/"," ",$serie));
	}

	if($folio != ""){
		$header["folio"]=trim(preg_replace("/ +/"," ",$folio));
	}

	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
	$sql .= " WHERE fk_facture = ".$facid." AND fk_paiement = ".$pagcid;

	$rq  = $db->query($sql);
	$respag=$db->fetch_object($rq);

	$fechap=str_replace(" ","T",$respag->fechaPago);

	$header["fecha"]             = date("Y-m-d")."T".date("H:i:s");
	$header["moneda"]            = "XXX";
	$header["subTotal"]          = 0;
	$header["total"]             = 0;
	$header["tipoDeComprobante"] = "P";
	$header["lugarExpedicion"]   = $conf->global->MAIN_INFO_SOCIETE_ZIP;

	if(strcmp($version_cfdi_sat, "4.0") == 0){
		$header["version"]     = "4.0";
		$header["exportacion"] = "01";
	}
	//Termina Datos del Comprobante (Header)

	//Inicia Datos del Emisor
	$emisor=array();
	$emisor["emisorRFC"]     = $conf->global->MAIN_INFO_SIREN;
	$razon_social_emisor     = $conf->global->CFDIMX_RAZON_SOCIAL;
	$emisor["nombre"]        = limpiar(trim(mb_strtoupper($razon_social_emisor)));
	$emisor["emisorRegimen"] = $conf->global->CFDIMX_REGIMEN_FISCAL;
	//Termina Datos del Emisor

	//Inicia Datos del Receptor
	$receptor=array();

	$sql_receptor  = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_relacion_facturas";
	$sql_receptor .= " WHERE fk_relacion_pagos = ".$respag->rowid;
	$res_receptor  = $db->query($sql_receptor);

	if($res_receptor){
		$num_rows_sql_receptor = $db->num_rows($res_receptor);

		if($num_rows_sql_receptor > 0){
			$obj_fac_rel = $db->fetch_object($res_receptor);

			$facture->fetch($obj_fac_rel->fk_facture);

			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe	WHERE rowid = ".$facture->socid;
			$req = $db->query($sql);
			$resocid = $db->fetch_object($req);

			$receptor["rfc"]     = $resocid->siren;
			$receptor["nombre"]  = $resocid->nom;
			$receptor["usoCFDI"] = $conf->global->CFDIMX_USOCFDI_PAGOS;
		}
	}

	if(strcmp($version_cfdi_sat, "4.0") == 0){
		$receptor = null;
		$objFacturaCFDI = new FacturaCFDI($db);
	    $objFacturaCFDI->entidad = $conf->entity;
	    $num_domicilio_fiscal = $objFacturaCFDI->getDomiciliosFiscalesCliente($facture->socid);

	    if($num_domicilio_fiscal > 0){
	        for ($i=0; $i < count($objFacturaCFDI->lista_domicilios); $i++) {
	            $receptor["rfc"] = $objFacturaCFDI->lista_domicilios[$i]->rfc;
	            $receptor["usoCFDI"] = trim(preg_replace("/ +/"," ", $conf->global->CFDIMX_USOCFDI_PAGOS));
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
	            }
	            //Termina Ajustes para Cliente Mostrador XAXX010101000
				break;
	        }
	    }
	}
	//Termina Datos del Receptor

	//Inicia Conceptos
	if(strcmp($version_cfdi_sat, "4.0") == 0){
		$conceptos = null;
	    $conceptos[0] = array(
			'descripcion'   => "Pago",
			'cantidad'      => 1,
			'valorUnitario' => 0,
			'importe'       => 0,
			'unidad'        => "ACT",
			'claveProdServ' => "84111506",
			'objetoImp'     => "01",
		);
	}else{
		$conceptos[0] = array(
			'descripcion'   => "Pago",
			'cantidad'      => 1,
			'valorUnitario' => 0,
			'importe'       => 0,
			'unidad'        => "ACT",
			'claveProdServ' => "84111506"
		);
	}
	//Termina Conceptos

	//Iinicia Datos WS
	$sql_ws = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config";
	$sql_ws .= " WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id=".$conf->entity;
	$res_ws = $db->query($sql_ws);

	$obj_ws = $db->fetch_object($res_ws);
	$modo_timbrado   = $obj_ws->modo_timbrado;
	$passwd_timbrado = $obj_ws->password_timbrado_txt;
	//Termina Datos Ws

	$pago_head = array();
	$pago_head["fechaPago"]   = $fechap;

	if($respag->formaDePago!=NULL && $respag->formaDePago!=""){
		$pago_head["formaDePagoP"] = $respag->formaDePago;
	}

	if($respag->monedaP!=NULL && $respag->monedaP != ""){
		$pago_head["monedaP"]     = $respag->monedaP;
	}

	if($respag->TipoCambioP!=NULL && $respag->TipoCambioP!=""){
		$pago_head["tipoCambioP"]=$respag->TipoCambioP;
	}

	if($respag->monto!=NULL && $respag->monto!=""){
		$pago_head["monto"]=str_replace(",", "", number_format($respag->monto,2));
	}

	if($respag->numOperacion!=NULL && $respag->numOperacion!=""){
		$pago_head["numOperacion"]=$respag->numOperacion;
	}
	if($respag->rfcEmisorCtaOrd!=NULL && $respag->rfcEmisorCtaOrd!=""){
		$pago_head["rfcEmisorCtaOrd"]=$respag->rfcEmisorCtaOrd;
	}
	if($respag->nomBancoOrdExt!=NULL && $respag->nomBancoOrdExt!=""){
		$pago_head["nomBancoOrdExt"]=$respag->nomBancoOrdExt;
	}
	if($respag->ctaOrdenante!=NULL && $respag->ctaOrdenante!=""){
		$pago_head["ctaOrdenante"]=$respag->ctaOrdenante;
	}
	if($respag->rfcEmisorCtaBen!=NULL && $respag->rfcEmisorCtaBen!=""){
		$pago_head["rfcEmisorCtaBen"]=$respag->rfcEmisorCtaBen;
	}
	if($respag->ctaBeneficiario!=NULL && $respag->ctaBeneficiario!=""){
		$pago_head["ctaBeneficiario"]=$respag->ctaBeneficiario;
	}
	if($respag->tipoCadPago!=NULL && $respag->tipoCadPago!=""){
		$pago_head["tipoCadPago"]=$respag->tipoCadPago;
	}
	if($respag->certPago!=NULL && $respag->certPago!=""){
		$pago_head["certPago"]=$respag->certPago;
	}
	if($respag->cadPago!=NULL && $respag->cadPago!=""){
		$pago_head["cadPago"]=$respag->cadPago;
	}
	if($respag->selloPago!=NULL && $respag->selloPago!=""){
		$pago_head["selloPago"]=$respag->selloPago;
	}

	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$respag->rowid;
	$rq  = $db->query($sql);

	$monto_total_pagos = 0;
	$docto_relacionados = array();

	$totalRetencionesIVA = 0;
	$importePRetIVA      = 0;

	$totalRetencionesISR = 0;
	$importePRetISR      = 0;

	$totalTrasladosBaseIVA0_Ex     = 0;
	$totalTrasladosImpuestoIVA0_Ex = 0;
	$basePIVA0_Ex                  = 0;
	$importePIVA0_Ex    		   = 0;

	$totalTrasladosBaseIVA0      = 0;
	$totalTrasladosImpuestoIVA0  = 0;
	$basePIVA0                   = 0;
	$importePIVA0    			 = 0;

	$totalTrasladosBaseIVA8      = 0;
	$totalTrasladosImpuestoIVA8  = 0;
	$basePIVA8                   = 0;
	$importePIVA8 				 = 0;

	$totalTrasladosBaseIVA16     = 0;
	$totalTrasladosImpuestoIVA16 = 0;
	$basePIVA16                  = 0;
	$importePIVA16				 = 0;

	while($resdocto=$db->fetch_object($rq)){
		$doc_rel = array();

		if($resdocto->idDocumento!=NULL && $resdocto->idDocumento!=""){
			$doc_rel["idDocumento"] = $resdocto->idDocumento;
		}

		if($resdocto->serie!=NULL && $resdocto->serie!=""){
			$doc_rel["serie"]=$resdocto->serie;
		}

		if($resdocto->folio!=NULL && $resdocto->folio!=""){
			$doc_rel["folio"]=$resdocto->folio;
		}

		if($resdocto->monedaDR!=NULL && $resdocto->monedaDR!=""){
			$doc_rel["monedaDR"]=$resdocto->monedaDR;
		}

		if($resdocto->tipoCambioDR!=NULL && $resdocto->tipoCambioDR!=""){
			$doc_rel["tipoCambioDR"]=$resdocto->tipoCambioDR;
		}

		if(strcmp($version_cfdi_sat, "4.0") == 0){

		}else{
			if($resdocto->metodoDePagoDR!=NULL && $resdocto->metodoDePagoDR!=""){
				$doc_rel["metodoDePagoDR"]=$resdocto->metodoDePagoDR;
			}
		}

		if($resdocto->numParcialidad!=NULL && $resdocto->numParcialidad!=""){
			$doc_rel["numParcialidad"]=$resdocto->numParcialidad;
		}
		if($resdocto->impSaldoAnt!=NULL && $resdocto->impSaldoAnt!=""){
			$doc_rel["impSaldoAnt"]=$resdocto->impSaldoAnt;
		}
		if($resdocto->impPagado!=NULL && $resdocto->impPagado!=""){
			$doc_rel["impPagado"]=$resdocto->impPagado;
		}
		if($resdocto->impSaldoInsoluto!=NULL && $resdocto->impSaldoInsoluto!=""){
			$doc_rel["impSaldoInsoluto"]=$resdocto->impSaldoInsoluto;
		}

		if(strcmp($version_cfdi_sat, "4.0") == 0){

			$doc_rel["equivalenciaDR"] = $resdocto->equivalencia;

			if($pago_head["tipoCambioP"] > 0){
				$monto_total_pagos += $resdocto->impPagado * $pago_head["tipoCambioP"];
			}else{
				$monto_total_pagos += $resdocto->impPagado;
			}

			$doc_rel["objetoImpDR"] = "01";
			$imp_docto_relacionados = array();

			##Inicia Retenciones DR
			$sql_retenciones = "";
			$sql_retenciones = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
			$sql_retenciones .= " WHERE";
			$sql_retenciones .= " entity = ".$conf->entity;
			$sql_retenciones .= " AND  fk_pago = ".$pago->id;
			$sql_retenciones .= " AND  uuid_doc_rel = '".$resdocto->idDocumento."'";
			$sql_retenciones .= " AND  tipo = 1";
			$res_sql_retenciones  = $db->query($sql_retenciones);
			$num_rows_retenciones = $db->num_rows($res_sql_retenciones);

			$lista_imp_retenciones  = array();

			if($num_rows_retenciones > 0){
				$doc_rel["objetoImpDR"] = "02";

				while($obj_impuestro_ret = $db->fetch_object($res_sql_traslados)){

					$impuesto_retenido = array();

					if($obj_impuestro_ret->base != "" && !is_null($obj_impuestro_ret->base)){
						$impuesto_retenido["baseDR"] = number_format(round($obj_impuestro_ret->base, 2), 2, '.', '');
					}

					if($obj_impuestro_ret->importe != "" && !is_null($obj_impuestro_ret->importe)){
						if(strcmp($obj_impuestro_ret->tipo_factor, "Exento") == 0){

						}else{
							$impuesto_retenido["importeDR"] = number_format(round($obj_impuestro_ret->importe, 2), 2, '.', '');
						}
					}

					if($obj_impuestro_ret->impuesto != "" && !is_null($obj_impuestro_ret->impuesto)){
						$impuesto_retenido["impuestoDR"] = $obj_impuestro_ret->impuesto;
					}

					if($obj_impuestro_ret->tasa_o_cuota != "" && !is_null($obj_impuestro_ret->tasa_o_cuota)){

						if(strcmp($obj_impuestro_ret->tipo_factor, "Exento") == 0){

						}else{
							$impuesto_retenido["tasaOCuotaDR"] = $obj_impuestro_ret->tasa_o_cuota;
						}

						if(strcmp($obj_impuestro_ret->impuesto,"001") == 0 ){
							if($pago_head["tipoCambioP"] > 0){
								$totalRetencionesISR += round($obj_impuestro_ret->importe * $pago_head["tipoCambioP"], 2);

								$importePRetISR += round($impuesto_retenido["importeDR"], 2);
							}
						}

						if(strcmp($obj_impuestro_ret->impuesto,"002") == 0 ){
							if($pago_head["tipoCambioP"] > 0){
								$totalRetencionesIVA += round($obj_impuestro_ret->importe * $pago_head["tipoCambioP"], 2);

								$importePRetIVA += round($impuesto_retenido["importeDR"], 2);
							}
						}
					}

					if($obj_impuestro_ret->tipo_factor != "" && !is_null($obj_impuestro_ret->tipo_factor)){
						$impuesto_retenido["tipoFactorDR"] = $obj_impuestro_ret->tipo_factor;
					}

					if($impuesto_retenido != null){
						array_push($lista_imp_retenciones, $impuesto_retenido);
						$imp_docto_relacionados["retencionesdr"] = $lista_imp_retenciones;
					}
				}

			}
			##Termina Retenciones DR

			##Inicia Traslados DR
			$sql_traslados = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
			$sql_traslados .= " WHERE";
			$sql_traslados .= " entity = ".$conf->entity;
			$sql_traslados .= " AND  fk_pago = ".$pago->id;
			$sql_traslados .= " AND  uuid_doc_rel = '".$resdocto->idDocumento."'";
			$sql_traslados .= " AND  tipo = 2";
			$res_sql_traslados  = $db->query($sql_traslados);
			$num_rows_traslados = $db->num_rows($res_sql_traslados);

			$lista_imp_traslados    = array();

			if($num_rows_traslados > 0){
				$doc_rel["objetoImpDR"] = "02";

				while($obj_impuestro_tras = $db->fetch_object($res_sql_traslados)){

					$impuestro_traslado = array();

					if($obj_impuestro_tras->base != "" && !is_null($obj_impuestro_tras->base)){
						$impuestro_traslado["baseDR"] = number_format(round($obj_impuestro_tras->base, 2), 2, '.', '');

					}

					if($obj_impuestro_tras->importe != "" && !is_null($obj_impuestro_tras->importe)){
						if(strcmp($obj_impuestro_tras->tipo_factor, "Exento") == 0){

						}else{
							$impuestro_traslado["importeDR"] = number_format(round($obj_impuestro_tras->importe, 2), 2, '.', '');
						}
					}

					if($obj_impuestro_tras->impuesto != "" && !is_null($obj_impuestro_tras->impuesto)){
						$impuestro_traslado["impuestoDR"] = $obj_impuestro_tras->impuesto;
					}

					if($obj_impuestro_tras->tasa_o_cuota != "" && !is_null($obj_impuestro_tras->tasa_o_cuota)){
						if(strcmp($obj_impuestro_tras->tipo_factor, "Exento") == 0){

						}else{
							$impuestro_traslado["tasaOCuotaDR"] = $obj_impuestro_tras->tasa_o_cuota;
						}

						if($obj_impuestro_tras->tasa_o_cuota == "0.000000"){
							// $totalTrasladosBaseIVA0     += $obj_impuestro_tras->base;
							// $totalTrasladosImpuestoIVA0 += $obj_impuestro_tras->importe;

							if($pago_head["tipoCambioP"] > 0){
								if(strcmp($obj_impuestro_tras->tipo_factor, "Exento") == 0){
									$totalTrasladosBaseIVA0_Ex     += round($obj_impuestro_tras->base * $pago_head["tipoCambioP"], 2);
									$totalTrasladosImpuestoIVA0_Ex += round($obj_impuestro_tras->importe * $pago_head["tipoCambioP"], 2);

									$basePIVA0_Ex    += round($impuestro_traslado["baseDR"], 2);
									$importePIVA0_Ex += round($impuestro_traslado["importeDR"], 2);
								}else{
									$totalTrasladosBaseIVA0     += round($obj_impuestro_tras->base * $pago_head["tipoCambioP"], 2);
									$totalTrasladosImpuestoIVA0 += round($obj_impuestro_tras->importe * $pago_head["tipoCambioP"], 2);

									$basePIVA0 += round($impuestro_traslado["baseDR"], 2);
									$importePIVA0 += round($impuestro_traslado["importeDR"], 2);
								}
							}
						}

						if($obj_impuestro_tras->tasa_o_cuota == "0.080000"){
							// $totalTrasladosBaseIVA8     += $obj_impuestro_tras->base;
							// $totalTrasladosImpuestoIVA8 += $obj_impuestro_tras->importe;

							if($pago_head["tipoCambioP"] > 0){
								$totalTrasladosBaseIVA8     += round($obj_impuestro_tras->base * $pago_head["tipoCambioP"], 2);
								$totalTrasladosImpuestoIVA8 += round($obj_impuestro_tras->importe * $pago_head["tipoCambioP"], 2);

								$basePIVA8 += round($impuestro_traslado["baseDR"], 2);
								$importePIVA8 += round($impuestro_traslado["importeDR"], 2);
							}
						}

						if($obj_impuestro_tras->tasa_o_cuota == "0.160000"){
							if($pago_head["tipoCambioP"] > 0){
								$totalTrasladosBaseIVA16     += round($obj_impuestro_tras->base * $pago_head["tipoCambioP"], 2);
								$totalTrasladosImpuestoIVA16 += round($obj_impuestro_tras->importe * $pago_head["tipoCambioP"], 2);

								$basePIVA16 += round($impuestro_traslado["baseDR"], 2);
								$importePIVA16 += round($impuestro_traslado["importeDR"], 2);
							}
						}
					}

					if($obj_impuestro_tras->tipo_factor != "" && !is_null($obj_impuestro_tras->tipo_factor)){
						$impuestro_traslado["tipoFactorDR"] = $obj_impuestro_tras->tipo_factor;
					}

					if($impuestro_traslado != null){
						array_push($lista_imp_traslados, $impuestro_traslado);
						$imp_docto_relacionados["trasladosdr"] = $lista_imp_traslados;
					}
				}
			}
			##Termina Traslados DR

			if($imp_docto_relacionados != null){
				$doc_rel["impuestosdr"] = $imp_docto_relacionados;
			}
		}

		array_push($docto_relacionados, $doc_rel);
	}

	if($docto_relacionados != null){
		$pago_head["doctoRelacionado"] = $docto_relacionados;
	}

	$impuestos_p             = array();
	$impuestos_p_retenciones = array();
	$impuestos_p_traslados   = array();

	if($monto_total_pagos > 0){
		$totales["montoTotalPagos"] = number_format($monto_total_pagos, 2, '.', '');
	}

	if($totalRetencionesISR > 0){
		if($pago_head["tipoCambioP"] > 0){
			$totalRetencionesISR = 0;
			$totalRetencionesISR = $importePRetISR * $pago_head["tipoCambioP"];
		}
		$totales["totalRetencionesISR"] = number_format(round($totalRetencionesISR, 2), 2, '.', '');

		$impuesto_p_local = array();
		// $impuesto_p_local["importeP"]  = number_format($totalRetencionesISR, 2, '.', '');
		$impuesto_p_local["importeP"]  = number_format($importePRetISR, 2, '.', '');
		$impuesto_p_local["impuestoP"]  = "001";

		array_push($impuestos_p_retenciones, $impuesto_p_local);
	}

	if($totalRetencionesIVA > 0){
		if($pago_head["tipoCambioP"] > 0){
			$totalRetencionesIVA = 0;
			$totalRetencionesIVA = $importePRetIVA * $pago_head["tipoCambioP"];
		}
		$totales["totalRetencionesIVA"] = number_format(round($totalRetencionesIVA, 2), 2, '.', '');

		$impuesto_p_local = array();
		// $impuesto_p_local["importeP"]  = number_format($totalRetencionesIVA, 2, '.', '');
		$impuesto_p_local["importeP"]  = number_format($importePRetIVA, 2, '.', '');
		$impuesto_p_local["impuestoP"]  = "002";

		array_push($impuestos_p_retenciones, $impuesto_p_local);
	}

	if($totalTrasladosBaseIVA0_Ex > 0){
		if($pago_head["tipoCambioP"] > 0){
			$totalTrasladosBaseIVA0_Ex = 0;
			$totalTrasladosBaseIVA0_Ex = $basePIVA0_Ex * $pago_head["tipoCambioP"];
			// $totalTrasladosImpuestoIVA0_Ex = 0;
			// $totalTrasladosImpuestoIVA0_Ex = $importePIVA0_Ex * $pago_head["tipoCambioP"];
		}

		$totales["totalTrasladosBaseIVAExento"] = number_format(round($totalTrasladosBaseIVA0_Ex, 2), 2, '.', '');
		// $totales["totalTrasladosImpuestoIVA0"] = number_format(round($totalTrasladosImpuestoIVA0, 2), 2, '.', '');

		$impuesto_p_local = array();
		// $impuesto_p_local["baseP"]  = number_format($totalTrasladosBaseIVA0, 2, '.', '');
		// $impuesto_p_local["importeP"]  = number_format($totalTrasladosImpuestoIVA0, 2, '.', '');
		$impuesto_p_local["baseP"]  = number_format($basePIVA0_Ex, 2, '.', '');
		// $impuesto_p_local["importeP"]  = number_format($importePIVA0, 2, '.', '');
		$impuesto_p_local["impuestoP"]  = "002";
		// $impuesto_p_local["tasaOCuotaP"]  = "0.000000";
		$impuesto_p_local["tipoFactorP"]  = "Exento";

		array_push($impuestos_p_traslados, $impuesto_p_local);
	}

	if($totalTrasladosBaseIVA0 > 0){
		if($pago_head["tipoCambioP"] > 0){
			$totalTrasladosBaseIVA0 = 0;
			$totalTrasladosBaseIVA0 = $basePIVA0 * $pago_head["tipoCambioP"];
			$totalTrasladosImpuestoIVA0 = 0;
			$totalTrasladosImpuestoIVA0 = $importePIVA0 * $pago_head["tipoCambioP"];
		}

		$totales["totalTrasladosBaseIVA0"] = number_format(round($totalTrasladosBaseIVA0, 2), 2, '.', '');
		$totales["totalTrasladosImpuestoIVA0"] = number_format(round($totalTrasladosImpuestoIVA0, 2), 2, '.', '');

		$impuesto_p_local = array();
		// $impuesto_p_local["baseP"]  = number_format($totalTrasladosBaseIVA0, 2, '.', '');
		// $impuesto_p_local["importeP"]  = number_format($totalTrasladosImpuestoIVA0, 2, '.', '');
		$impuesto_p_local["baseP"]  = number_format($basePIVA0, 2, '.', '');
		$impuesto_p_local["importeP"]  = number_format($importePIVA0, 2, '.', '');
		$impuesto_p_local["impuestoP"]  = "002";
		$impuesto_p_local["tasaOCuotaP"]  = "0.000000";
		$impuesto_p_local["tipoFactorP"]  = "Tasa";

		array_push($impuestos_p_traslados, $impuesto_p_local);
	}

	if($totalTrasladosBaseIVA8 > 0){
		if($pago_head["tipoCambioP"] > 0){
			$totalTrasladosBaseIVA8 = 0;
			$totalTrasladosBaseIVA8 = $basePIVA8 * $pago_head["tipoCambioP"];
			$totalTrasladosImpuestoIVA8 = 0;
			$totalTrasladosImpuestoIVA8 = $importePIVA8 * $pago_head["tipoCambioP"];
		}

		$totales["totalTrasladosBaseIVA8"] = number_format(round($totalTrasladosBaseIVA8, 2), 2, '.', '');
		$totales["totalTrasladosImpuestoIVA8"] = number_format(round($totalTrasladosImpuestoIVA8, 2), 2, '.', '');

		$impuesto_p_local = array();
		// $impuesto_p_local["baseP"]  = number_format($totalTrasladosBaseIVA8, 2, '.', '');
		// $impuesto_p_local["importeP"]  = number_format($totalTrasladosImpuestoIVA8, 2, '.', '');
		$impuesto_p_local["baseP"]  = number_format($basePIVA8, 2, '.', '');
		$impuesto_p_local["importeP"]  = number_format($importePIVA8, 2, '.', '');
		$impuesto_p_local["impuestoP"]  = "002";
		$impuesto_p_local["tasaOCuotaP"]  = "0.080000";
		$impuesto_p_local["tipoFactorP"]  = "Tasa";

		array_push($impuestos_p_traslados, $impuesto_p_local);
	}

	if($totalTrasladosBaseIVA16 > 0){
		if($pago_head["tipoCambioP"] > 0){
			$totalTrasladosBaseIVA16 = 0;
			$totalTrasladosBaseIVA16 = $basePIVA16 * $pago_head["tipoCambioP"];
			$totalTrasladosImpuestoIVA16 = 0;
			$totalTrasladosImpuestoIVA16 = $importePIVA16 * $pago_head["tipoCambioP"];
		}

		$totales["totalTrasladosBaseIVA16"] = number_format(round($totalTrasladosBaseIVA16, 2), 2, '.', '');
		$totales["totalTrasladosImpuestoIVA16"] = number_format(round($totalTrasladosImpuestoIVA16, 2), 2, '.', '');

		$impuesto_p_local = array();
		// $impuesto_p_local["baseP"]  = number_format($totalTrasladosBaseIVA16, 2, '.', '');
		// $impuesto_p_local["importeP"]  = number_format($totalTrasladosImpuestoIVA16, 2, '.', '');
		$impuesto_p_local["baseP"]  = number_format($basePIVA16, 2, '.', '');
		$impuesto_p_local["importeP"]  = number_format($importePIVA16, 2, '.', '');
		$impuesto_p_local["impuestoP"]  = "002";
		$impuesto_p_local["tasaOCuotaP"]  = "0.160000";
		$impuesto_p_local["tipoFactorP"]  = "Tasa";

		array_push($impuestos_p_traslados, $impuesto_p_local);
	}

	$impuestos_p["retenciones"] = $impuestos_p_retenciones;
	$impuestos_p["traslados"] = $impuestos_p_traslados;

	$pago_head["impuestosp"] = $impuestos_p;

	$pagos = array(
				"pago" => $pago_head,
				"totales" => $totales,
				"version" => "2.0"
			);

	$adicionales["pagos"] = $pagos;

	if(!is_null($respag->tipo_rel) && $respag->tipo_rel != -1){
		$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
		$sql_valida_cfdi_rel .= " WHERE fk_pago = " .$respag->fk_paiement;
		$res_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
		$valida_cfdi_rel     = $db->num_rows($res_valida_cfdi_rel);

		if($valida_cfdi_rel > 0){
			$list_uuid_rel = array();
			while($obj_uuid_rel = $db->fetch_object($res_valida_cfdi_rel)){
				$list_uuid_rel[] = array(
										"tipoRelacion"    => $respag->tipo_rel,
										"uuidRelacionado" => $obj_uuid_rel->uuid
								);
			}
			// print 'count :: '.count($list_uuid_rel).'<br>';
			if(count($list_uuid_rel) > 0){
				$adicionales["relacionado"] = $list_uuid_rel;
			}
		}
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

		// $prmsnd["logosmall"]=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;

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

		if(file_exists($conf->facture->dir_output."/".$pago->ref)){}else{
			mkdir($conf->facture->dir_output."/".$pago->ref,0700);
		}

		if( $result["return"]["rsp"]==1 ){

			$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);

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
			$result["return"]["version"]=isset($result["return"]["version"])?$result["return"]["version"]:"1.0";
			$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);
			$sqm="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
					SET
					xml='".$db->escape(utf8_decode($result["return"]["xml"]))."',
					cadena='".$db->escape(utf8_decode($result["return"]["cadenaOrig"]))."',
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

			if(!$rq){
				dol_print_error($db);
				die;
			}

			print "<script>window.location.href='pdf_pagos.php?pagcid=".$pago->id."'</script>";
		}else{
			if($result["return"]["rsp"]!=""){
				$_SESSION["errorCFDIP"] = utf8_encode($result["return"]["rsp"]." - ".$result["return"]["msg"]."<br><br>".$result["return"]["msgDetail"]);
			}else{
				$_SESSION["errorCFDIP"] = "No hubo respuesta para la peticion, intente nuevamente";
			}

			$file_xml = fopen ($conf->facture->dir_output."/".$pago->ref."/Pago_".$pago->ref.".xml", "w");
			fwrite($file_xml,utf8_encode($result["return"]["xml_estructura"]));
			fclose($file_xml);

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