<?php
date_default_timezone_set("America/Mexico_City");
include('lib/nusoap/lib/nusoap.php');
include("lib/phpqrcode/qrlib.php");
require('lib/numero_a_letra.php');
try{
	set_time_limit(150);
}catch(Exception $e){
	$msg_cfdi_final = "Error:".$e->getMessage();
}
if( $modo_timbrado=="" ){
	$modotimb=2;
}else{
	$modotimb = $modo_timbrado;
}

$cfdi_decimal = $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL?$conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL:2;
$id = $facid;
//Datos de la factura
$sql= " SELECT * FROM ".MAIN_DB_PREFIX ."facture WHERE rowid = ".$facid;
$resql=$db->query($sql);
if ($resql){
	$num_fact = $db->num_rows($resql);
	$i=0;
	if($num_fact){
		while ($i < $num_fact){
			$obj = $db->fetch_object($resql);
			if($conf->global->MAIN_MODULE_MULTICURRENCY){
				$obj->total=$obj->multicurrency_total_ht;
				$obj->tva=$obj->multicurrency_total_tva;
				$obj->total_ttc=$obj->multicurrency_total_ttc;
				//$object->multicurrency_total_ht;
				//$object->multicurrency_total_ttc;
				//$object->multicurrency_total_tva;
			}
			$facref = $obj->ref;
			$separafac = explode("-", $facref);
			$serie=$separafac[0];
			$folio=$separafac[1];
			if($separafac[1]=='' || $separafac[1]==NULL || $separafac[1]==null){
				$serie="";
				$folio=$separafac[0];
			}
			$observaciones = $obj->note_public;
			$factura_tipo = $obj->type;
			$cliente_id = $obj->fk_soc;
			$factura_id = $obj->rowid;
			$factura_iva = str_replace(",", "", number_format($obj->tva,2));
			$factura_subtotal = str_replace(",", "", number_format($obj->total,2));
			//$factura_total = str_replace(",", "", number_format($obj->total_ttc,2));
			//$factura_total_origen = str_replace(",", "", number_format($obj->total_ttc,2));
			$factura_total = str_replace(",", "", number_format(($factura_iva+$factura_subtotal),2));
			$factura_total_origen = str_replace(",", "", number_format(($factura_iva+$factura_subtotal),2));
			$factura_fecha = $obj->datef;
			$factura_fechac_unix = strtotime( $obj->datec );
			$factura_hora = date("H:i:s", $factura_fechac_unix);
			$fecha_factura = $factura_fecha."T".$factura_hora;
			$factura_formapago_id = $obj->fk_mode_reglement;
			$factura_condicionpago_id = $obj->fk_cond_reglement;
			$formaPago = getFormasPago( $factura_formapago_id, $db );
			$condPago = getCondicionesPago( $factura_condicionpago_id, $db );
			
			$sqlm="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'formpagcfdi'";
			$resqlv=$db->query($sqlm);
			$existe_form = $db->num_rows($resqlv);
			if($existe_form>0){
				$sqlv="SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".$facid;
				$rv=$db->query($sqlv);
				$vrs=$db->fetch_object($rv);//$factura_formapago cambio por $factura_metodopago
				$factura_metodopago=$vrs->formpagcfdi;
			}else{
				$factura_metodopago="No identificado";
			}
			$sqlm2="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'usocfdi'";
			$resqlv2=$db->query($sqlm2);
			$existe_usocfdi = $db->num_rows($resqlv2);
			if($existe_usocfdi>0){
				$sqlv="SELECT usocfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".$facid; //esta linea remplaz� a la siguiente linea
				//$sqlv="SELECT fe.usocfdi, catuso.label FROM ".MAIN_DB_PREFIX."facture_extrafields fe INNER JOIN ".MAIN_DB_PREFIX."c_cfdimx_uso_cfdi catuso ON fe.usocfdi=catuso.rowid WHERE fe.fk_object=".$facid;

				$rv=$db->query($sqlv);
				$vrs=$db->fetch_object($rv);//$factura_formapago cambio por $factura_metodopago

				$factura_usocfdi=$vrs->usocfdi; //Se remplazo por la siguiente linea
				//$factura_usocfdi=$vrs->label;
			}else{
				$factura_usocfdi="G03";
			}

			//$factura_formapago = ($langs->trans("PaymentTypeShort".$formaPago["code"])!=("PaymentTypeShort".$formaPago["code"])?$langs->trans("PaymentTypeShort".$formaPago["code"]):($formaPago["tipo_pago"]));
			$sqlb="SELECT accountancy_code as acon FROM ".MAIN_DB_PREFIX."c_paiement WHERE code = '".$formaPago["code"]."'";
			$vs=$db->query($sqlb);
			$brs=$db->num_rows($vs);
			if($brs>0){
				$bns=$db->fetch_object($brs);
				if($bns->acon!=NULL && $bns->acon!=''){
					$codmet=$bns->acon;
				}else{
					$codmet=99;
				}
			}else{
				$codmet=99;
			}
			$smq="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement,".MAIN_DB_PREFIX."c_paiement
            				WHERE fk_facture=".$facid." AND fk_c_paiement=id";
			$mqs=$db->query($smq);
			$mnrw=$db->num_rows($mqs);
			if($mnrw>0){
				while($mrs=$db->fetch_object($mqs)){
					if($mrs->accountancy_code=='' || $mrs->accountancy_code== null){
						$codpa=99;
					}else{
						$codpa=$mrs->accountancy_code;
					}
					$codmet=$codmet.",".$codpa;
				}
			}//$factura_formapago
			//$factura_metodopago=$formaPago["code"];
			$factura_formapago=$codmet;
			//$factura_metodopago2 = ($langs->trans("PaymentTypeShort".$formaPago["code"])!=("PaymentTypeShort".$formaPago["code"])?$langs->trans("PaymentTypeShort".$formaPago["code"]):($formaPago["tipo_pago"]));
			$factura_condicionpago = html_entity_decode(($langs->trans("PaymentConditionShort".$condPago["code"])!=("PaymentConditionShort".$condPago["code"])?$langs->trans("PaymentConditionShort".$condPago["code"]):($condPago["condicion_pago"])));
			$i++;
		}
	}
}

$cuenta = getU4DigCta( $cliente_id, $db );
$datareceptor_main = getDataCliente( $db, $cliente_id );
//$datareceptor_comp = get_data_receptor( $db, $cliente_id );
$sqld="SELECT *
		FROM ".MAIN_DB_PREFIX."cfdimx_descuentos
		WHERE entity_id=".$conf->entity;
$rqsd=$db->query($sqld);
$nrd=$db->num_rows($rqsd);
$descumostrar=1;
if($nrd>0){
	$rsd=$db->fetch_object($rqsd);
	if($rsd->mostrar==1){
		$descumostrar=1;
	}else{
		$descumostrar=2;
	}
}

$sqn="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
					,tipocambio,certificadoorigen,subdivision,totalusd
			FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
			WHERE fk_facture=".$facid;
$rqn=$db->query($sqn);
$numrn=$db->num_rows($rqn);
$entraconcepto='';
if($numrn>0){
 $entraconcepto='SI';
}
//Conceptos
$sql  = " SELECT *, truncate(`tva_tx`,".$cfdi_decimal.") as tva_tx, `total_tva` as total_tva";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet  WHERE qty > 0 AND fk_facture =".$facid;
if($conf->global->MAIN_MODULE_MULTICURRENCY){
	$sql  = " SELECT *, truncate(`tva_tx`,$cfdi_decimal) as tva_tx, `multicurrency_total_tva` as total_tva";
	$sql .= " ,multicurrency_total_ht as total_ht, multicurrency_total_ttc as total_ttc, multicurrency_subprice as subprice";
	$sql .= " FROM ".MAIN_DB_PREFIX ."facturedet  WHERE qty > 0 AND fk_facture =".$facid;
}
$descheader=0;
$resql=$db->query($sql);
$auxsubtotal=0;
if ($resql){
	$num_detalle_fact = $db->num_rows($resql);
	$i=0;
	/***Lotes****/
	$lote="NO";
	if($conf->global->MAIN_MODULE_PRODUCTBATCH){
		$sql1="SELECT ifnull(fk_source,null) as fk_source
				FROM ".MAIN_DB_PREFIX."element_element
				WHERE fk_target=".$facid." AND targettype='facture' AND sourcetype='commande'";
		//dol_syslog("MI QUERY 1::".$sql1);
		$rlo=$db->query($sql1);
		$rlot=$db->fetch_object($rlo);
		//dol_syslog("MI QUERY 1RES::".$rlot->fk_source);
		if($rlot->fk_source!=NULL && $rlot->fk_source!=null && $rlot->fk_source>0){
			$sql2="SELECT ifnull(fk_target,null) as fk_target
					FROM ".MAIN_DB_PREFIX."element_element
					WHERE fk_source=".$rlot->fk_source." AND sourcetype='commande' AND targettype='shipping'";
			//dol_syslog("MI QUERY 2::".$sql2);
			$rlo2=$db->query($sql2);
			$rlot2=$db->fetch_object($rlo2);
			//dol_syslog("MI QUERY 2RES::".$rlot2->fk_target);
			if($rlot2->fk_target!=NULL && $rlot2->fk_target!=null && $rlot2->fk_target>0){
				$lote=$rlot2->fk_target;
			}else{
				$lote="NO";
			}
		}else{
			$lote="NO";
		}
	}
	/***Lotes****/
	if($num_detalle_fact){
		while ($i < $num_detalle_fact){
			$obj = $db->fetch_object($resql);
		    $desc='';
		    $ref_add=1;
		    $label_add=1;
		    $description_add=1;

            unset($producto);
		    if($obj->fk_product != ""){
		    	$producto = getProducto( $obj->fk_product, $db );
		    }

		    if($ref_add == 1 && $producto['ref'] !="" ){
		    	$desc .= utf8_decode($producto['ref']);
		    }
		    if($label_add == 1 && $producto['label'] != ""){
		    	if($desc != ""){$desc .= ' - ';}
		    	$desc .= ($producto['label']);
		    }

		    if($description_add == 1 && $obj->description != ""){
		    	if($desc != ""){$desc .= ' - ';}
		    	if(strpos(utf8_decode($obj->description),"'")){
		    		$msg_cfdi_final="<a style='color:red'>ERROR: uno de los valores cuenta con un caracter no permitido ( ' )</a>";
		    		return $msg_cfdi_final;
		    	}
		    	$desc .= ($obj->description);
		    	$desc= limpiar($desc);
		    }
		    
		    if($lote!="NO" && $obj->fk_product != ""){
		    	$sql3="SELECT ifnull(fk_product,null) as fk_product, batch, eatby,(value * -1) as qty
						FROM ".MAIN_DB_PREFIX."stock_mouvement
						WHERE fk_product=".$obj->fk_product." AND fk_origin=".$lote." AND origintype='shipping'";
		    	dol_syslog("MI QUERY 3::".$sql3);
		    	$rlo3=$db->query($sql3);
		    	while($rlot3=$db->fetch_object($rlo3)){
		    		if($rlot3->fk_product!=NULL && (trim($rlot3->batch)!="" && $rlot3->batch!=NULL)){
		    			$desc .= " - Cantidad: ".$rlot3->qty." Lote: ".$rlot3->batch." Cad: ".$rlot3->eatby;
		    		}
		    	}
		    }

			if($obj->fk_product){
				$unidad = getUnidadMedida( $db, $obj->fk_product );
			}else{
				$sqlm = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."facturedet_extrafields'";
				$resqlm=$db->query($sqlm);
				$existe_tabla = $db->num_rows($resqlm);
				if( $existe_tabla>0 ){
					$sqlm = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facturedet_extrafields LIKE 'umed'";
					$resqlm=$db->query($sqlm);
					$existe_umed = $db->num_rows($resqlm);
					if( $existe_umed > 0 ){
						$sqlm = "SELECT * FROM ".MAIN_DB_PREFIX."facturedet_extrafields WHERE fk_object = " . $obj->rowid;
						$resqlm=$db->query($sqlm);
						$objm = $db->fetch_object($resqlm);
						if( $objm->umed!="" && $objm->umed!=0){ $unidad = utf8_decode($objm->umed); }else{ $unidad = "NA"; }
					}else{
						$unidad = "ZZ";
					}
				}else{
					$unidad = "ZZ";
				}
			}
			
			if($obj->fk_product){
				$claveprodserv = getclaveprodserv( $db, $obj->fk_product );
			}else{
				$sqlm = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."facturedet_extrafields'";
				$resqlm=$db->query($sqlm);
				$existe_tabla = $db->num_rows($resqlm);
				if( $existe_tabla>0 ){
					$sqlm = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facturedet_extrafields LIKE 'claveprodserv'";
					$resqlm=$db->query($sqlm);
					$existe_claveprodserv = $db->num_rows($resqlm);
					if( $existe_claveprodserv > 0 ){
						$sqlm = "SELECT claveprodserv FROM ".MAIN_DB_PREFIX."facturedet_extrafields WHERE fk_object = " . $obj->rowid;
						$resqlm=$db->query($sqlm);
						$objm = $db->fetch_object($resqlm);
						if( $objm->claveprodserv!="" && $objm->claveprodserv!=0 ){ $claveprodserv = utf8_decode($objm->claveprodserv); }else{ $claveprodserv = "01010101"; }
					}else{
						$claveprodserv = "01010101";
					}
				}else{
					$claveprodserv = "01010101";
				}
			}
			if($obj->fk_product){
				$noIdentificacion = getnoIdentificacion( $db, $obj->fk_product );
			}else{
				$sqlm = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."facturedet_extrafields'";
				$resqlm=$db->query($sqlm);
				$existe_tabla = $db->num_rows($resqlm);
				if( $existe_tabla>0 ){
					$sqlm = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facturedet_extrafields LIKE 'noidenticfdi'";
					$resqlm=$db->query($sqlm);
					$existe_noidenticfdi = $db->num_rows($resqlm);
					if( $existe_noidenticfdi > 0 ){
						$sqlm = "SELECT noidenticfdi FROM ".MAIN_DB_PREFIX."facturedet_extrafields WHERE fk_object = " . $obj->rowid;
						$resqlm=$db->query($sqlm);
						$objm = $db->fetch_object($resqlm);
						if( $objm->noidenticfdi!="" && $objm->noidenticfdi!=NULL && $objm->noidenticfdi!=null){ $noIdentificacion = utf8_decode($objm->noidenticfdi); 
						}else{ $noIdentificacion = NULL; }
					}else{
						$noIdentificacion = NULL;
					}
				}else{
					$noIdentificacion = NULL;
				}
			}
			
			$cuentapredial=NULL;
			if($obj->fk_product){
				$cuentapredial = getCuentaPredial( $db, $obj->fk_product );
			}else{
				$sqlm = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."facturedet_extrafields'";
				$resqlm=$db->query($sqlm);
				$existe_predial = $db->num_rows($resqlm);
				if($existe_predial>0 ){
					$sqlm = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facturedet_extrafields LIKE 'cuentapredial'";
					$resqlm=$db->query($sqlm);
					$existepredial = $db->num_rows($resqlm);
					if($existepredial > 0 ){
						$sqlm = "SELECT cuentapredial FROM ".MAIN_DB_PREFIX."facturedet_extrafields WHERE fk_object = " . $obj->rowid;
						$resqlm=$db->query($sqlm);
						$objm = $db->fetch_object($resqlm);
						if( $objm->cuentapredial!="" && $objm->cuentapredial!=NULL && $objm->cuentapredial!=null){ $cuentapredial = $objm->cuentapredial;
						}else{ $cuentapredial = NULL; }
					}else{
						$cuentapredial = NULL;
					}
				}else{
					$cuentapredial = NULL;
				}
			}
			
			$descprodl=null;
			if($obj->remise_percent!=0  && $descumostrar==1){
				$descuento=$obj->remise_percent/100;
				$descuento2=($obj->subprice*$obj->qty)*$descuento;
				$descuento2=str_replace(array("-",","), "",number_format($descuento2,2));
				$descprodl=str_replace(array("-",","), "",number_format($descuento2,2));
				$descheader=$descheader+$descuento2;
				//$auxvuni=$obj->subprice-$descuento2;
				$total_vuni = number_format($obj->subprice,$cfdi_decimal);
				$total_ttc = number_format($obj->subprice*$obj->qty,$cfdi_decimal);
			}else{
				if($obj->remise_percent!=0  && $descumostrar==2){
					$descuento=$obj->total_ht/$obj->qty;
					//$descuento2=($obj->subprice*$obj->qty)*$descuento;
					//$auxvuni=$obj->subprice-$descuento2;
					$total_vuni = number_format($descuento,$cfdi_decimal);
					$total_ttc = number_format($obj->total_ht,$cfdi_decimal);
				}else{
					$total_vuni = number_format($obj->subprice,$cfdi_decimal);
					$total_ttc = number_format($obj->total_ht,$cfdi_decimal);
				}
			}
			if($factura_tipo==2){
				$vowels = array(",", "-");
			}else{
				$vowels = array(",");
			}
			$retenBase=null;
			$retenImporte=null;
			$retenTasa=null;
			$retenTipoFactor=null;
			$retencImpuesto=null;
			$sqlret="SELECT base,impuesto,tipo_factor,tasa,importe FROM ".MAIN_DB_PREFIX."cfdimx_retencionesdet WHERE factura_id=".$facid." AND fk_facturedet=".$obj->rowid." AND impuesto='002'";
			$rqret=$db->query($sqlret);
			$nret=$db->num_rows($rqret);
			if($nret>0){
				$rsret=$db->fetch_object($rqret);
				$retenBase=str_replace($vowels, "",number_format($rsret->base,2));
				$retenImporte=str_replace($vowels, "",number_format($rsret->importe,2));
				$retenTasa=$rsret->tasa;
				$retenTipoFactor=$rsret->tipo_factor;
				$retencImpuesto=$rsret->impuesto;
			}
			$retenBaseISR=null;
			$retenImporteISR=null;
			$retenTasaISR=null;
			$retenTipoFactorISR=null;
			$retencImpuestoISR=null;
			$sqlret="SELECT base,impuesto,tipo_factor,tasa,importe FROM ".MAIN_DB_PREFIX."cfdimx_retencionesdet WHERE factura_id=".$facid." AND fk_facturedet=".$obj->rowid." AND impuesto='001'";
			$rqret=$db->query($sqlret);
			$nret=$db->num_rows($rqret);
			if($nret>0){
				$rsret=$db->fetch_object($rqret);
				$retenBaseISR=str_replace($vowels, "",number_format($rsret->base,2));
				$retenImporteISR=str_replace($vowels, "",number_format($rsret->importe,2));
				$retenTasaISR=$rsret->tasa;
				$retenTipoFactorISR=$rsret->tipo_factor;
				$retencImpuestoISR=$rsret->impuesto;
			}
			
			if($entraconcepto=='SI'){
				$sqln2="SELECT preciousd,noidentificacion,unidadcext
				FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero_mercancia a
				WHERE a.fk_facture=".$facid." AND a.fk_facturedet=".$obj->rowid;
				$reqsn2=$db->query($sqln2);
				$numrn2=$db->num_rows($reqsn2);
				$noiden=0;
				$unidadcext=null;
				if($numrn2>0){
					$resuln2=$db->fetch_object($reqsn2);
					$noiden=$resuln2->noidentificacion;
					$unidadcext=$resuln2->unidadcext;
				}
				$conceptos[$i] = array(
						'descripcion' =>$desc,
						'cantidad' =>str_replace($vowels, "",number_format($obj->qty,2)),
						'valorUnitario'=>str_replace($vowels, "", $total_vuni),
						'importe'=>str_replace($vowels, "", $total_ttc),
						'importeImpuesto'=>str_replace($vowels, "",round(($obj->total_tva),6)),
						'impuesto'=>'002',// IVA == 002
						'tasa'=>number_format(($obj->tva_tx/100),6),
						'unidad'=>$unidad,
						'noIdentificacion'=>$noiden,
						'tipoFactor'=>"Tasa",
						'claveProdServ'=>$claveprodserv,
						'base'=>str_replace($vowels, "", number_format($obj->total_ht,2)),
						'retenBase'=>$retenBase,
						'retenImporte'=>$retenImporte,  
						'retenTasa'=>number_format($retenTasa,6), 
						'retenTipoFactor'=>$retenTipoFactor,  
						'retencImpuesto'=>$retencImpuesto,
						'retenBaseISR'=>$retenBaseISR,
						'retenImporteISR'=>$retenImporteISR,
						'retenTasaISR'=>number_format($retenTasaISR,6),
						'retenTipoFactorISR'=>$retenTipoFactorISR,
						'retencImpuestoISR'=>$retencImpuestoISR,
						'descuento'=>$descprodl,
						'cuentaPredial'=>$cuentapredial
				);
				$conceptos2[$i] = array(
						'descripcion' =>$desc,
						'cantidad' =>str_replace($vowels, "",number_format($obj->qty,2)),
						'valorUnitario'=>str_replace($vowels, "", $total_vuni),
						'importe'=>str_replace($vowels, "", $total_ttc),
						'importeImpuesto'=>str_replace($vowels, "",round(($obj->total_tva),6)),
						'impuesto'=>'002',// IVA == 002
						'tasa'=>$obj->tva_tx,
						'unidad'=>$unidad,
						'noIdentificacion'=>$noiden,
						'tipoFactor'=>"Tasa",
						'claveProdServ'=>$claveprodserv,
						'retenBase'=>$retenBase, 
						'retenImporte'=>$retenImporte,  
						'retenTasa'=>$retenTasa, 
						'retenTipoFactor'=>$retenTipoFactor,  
						'retencImpuesto'=>$retencImpuesto,
						'retenBaseISR'=>$retenBaseISR,
						'retenImporteISR'=>$retenImporteISR,
						'retenTasaISR'=>$retenTasaISR,
						'retenTipoFactorISR'=>$retenTipoFactorISR,
						'retencImpuestoISR'=>$retencImpuestoISR
				);
			}else{
				$conceptos[$i] = array(
					'descripcion' =>$desc,
					'cantidad' =>$obj->qty,
					'valorUnitario'=>str_replace($vowels, "", $total_vuni),
					'importe'=>str_replace($vowels, "", $total_ttc),
					'importeImpuesto'=>str_replace($vowels, "",round(($obj->total_tva),6)),
					'impuesto'=>'002',// IVA == 002
					'tasa'=>number_format(($obj->tva_tx/100),6),
					'unidad'=>$unidad,
					'tipoFactor'=>"Tasa",
					'claveProdServ'=>$claveprodserv,
					'base'=>str_replace($vowels, "", number_format($obj->total_ht,2)),
					'retenBase'=>$retenBase, 
					'retenImporte'=>$retenImporte,  
					'retenTasa'=>number_format($retenTasa,6), 
					'retenTipoFactor'=>$retenTipoFactor,  
					'retencImpuesto'=>$retencImpuesto,
					'retenBaseISR'=>$retenBaseISR,
					'retenImporteISR'=>$retenImporteISR,
					'retenTasaISR'=>number_format($retenTasaISR,6),
					'retenTipoFactorISR'=>$retenTipoFactorISR,
					'retencImpuestoISR'=>$retencImpuestoISR,
						'descuento'=>$descprodl,
						'cuentaPredial'=>$cuentapredial
				);
				$conceptos2[$i] = array(
						'descripcion' =>$desc,
						'cantidad' =>$obj->qty,
						'valorUnitario'=>str_replace($vowels, "", $total_vuni),
						'importe'=>str_replace($vowels, "", $total_ttc),
						'importeImpuesto'=>str_replace($vowels, "",round(($obj->total_tva),6)),
						'impuesto'=>'002',// IVA == 002
						'tasa'=>$obj->tva_tx,
						'unidad'=>$unidad,
						'tipoFactor'=>"Tasa",
						'claveProdServ'=>$claveprodserv,
						'base'=>str_replace($vowels, "", $total_ttc)
				);
			}
			$auxsubtotal+=str_replace($vowels, "",$total_ttc);
			$i++;
		}
	}
}

//Datos complementarios del emisor
// Si usuario tiene almacen, extraer datos de almacen, si no de emisor predeterminado
if ($user->fk_warehouse > 0) {
	$emisorsql = "SELECT edc.emisor_delompio, edc.emisor_colonia, edc.emisor_calle, edc.emisor_noext, edc.emisor_noint";
	$emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
	$emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
	$emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
	$emisorsql .= " WHERE e.rowid = " . $user->fk_warehouse;

	$reemisorsql = $db->query($emisorsql);

	if ($reemisorsql) {
		$emisor_almacen = $db->fetch_object($emisorsql);
	}

	$emisor_delompio = $emisor_almacen->emisor_delompio;
	$col_emisor      = $emisor_almacen->emisor_colonia;
	$emisor_calle    = $emisor_almacen->emisor_calle;
	$emisor_noint    = $emisor_almacen->emisor_noint;
	$emisor_noext    = $emisor_almacen->emisor_noext;
} else {
	// Priorizar emisor seleccionado en ficha, si no hay seleccion, usar predeterminado
	$selectedEmisor = $_SESSION['selected_emisor'] ? $_SESSION['selected_emisor'] : $conf->global->MAIN_INFO_SIREN;
	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp WHERE emisor_rfc = '" . $selectedEmisor . "' AND entity_id = " . $conf->entity;
	$resql = $db->query($sql);
	if ($resql) {
		$num_emisor_datacomp = $db->num_rows($resql);
		$i = 0;
		if ($num_emisor_datacomp) {
			while ($i < $num_emisor_datacomp) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$sqn = "SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
					FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero
					WHERE fk_facture=" . $facid;
					$rqn = $db->query($sqn);
					$numrn = $db->num_rows($rqn);
					if ($numrn > 0) {
						$emisor_delompio = $obj->cod_municipio;
						$col_emisor = $obj->cod_colonia;
					} else {
						$emisor_delompio = utf8_decode($obj->emisor_delompio); // Convertir a Colonia
						$col_emisor = limpiar(html_entity_decode($obj->emisor_colonia));
					}
					$emisor_calle = utf8_decode($obj->emisor_calle);
					$emisor_noint = utf8_decode($obj->emisor_noint);
					$emisor_noext = utf8_decode($obj->emisor_noext);
				}
				$i++;
			}
		}
	}
}

//Datos complementarios del receptor
//if($_REQUEST['tpdomi']){
$resql=$db->query("SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE tpdomicilio='".$_REQUEST['tpdomi']."' AND receptor_rfc = '".$datareceptor_main["rfc"]."' AND entity_id = ".$conf->entity." AND fk_socid=".$datareceptor_main["rowid"]);
dol_syslog("DOMICILIOS:: SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE tpdomicilio='".$_REQUEST['tpdomi']."' AND receptor_rfc = '".$datareceptor_main["rfc"]."' AND entity_id = ".$conf->entity);
// }else{
// 	$resql=$db->query("SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc = '".$datareceptor_main["rfc"]."' AND entity_id = ".$_SESSION['dol_entity']);
//}
if ($resql){
	 $num_emisor_datacomp = $db->num_rows($resql);
	 $i = 0;
	 if ($num_emisor_datacomp){
		 while ($i < $num_emisor_datacomp){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
			 	 $receptor_cod_municipio = $obj->cod_municipio;
				 $receptor_delompio = $obj->receptor_delompio;
				 $receptor_colonia = utf8_decode($obj->receptor_colonia);
				 $receptor_calle = utf8_decode($obj->receptor_calle);
				 $receptor_noint = utf8_decode($obj->receptor_noint);
				 $receptor_noext = utf8_decode($obj->receptor_noext);
			 }
			 $i++;
		 }
	 }
}

//retenciones
$resql=$db->query("SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_retenciones WHERE fk_facture = ".$facid);
if ($resql){
	 $tot_ret = $db->num_rows($resql);
	 $i = 0;
	 if ($tot_ret){
		 while ($i < $tot_ret){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
			 	if($factura_tipo==2){
			 		$vowels = array(",", "-");
			 	}else{
			 		$vowels = array(",");
			 	}
			 	$retenclave="";
			 	if($obj->impuesto=="IVA"){
			 		$retenclave="002";
			 	}else{
				 	if($obj->impuesto=="ISR"){
				 		$retenclave="001";
				 	}else{
				 		$retenclave=$obj->impuesto;
				 	}
			 	}
				$retenciones[$i]= array(
					"impuesto"=>trim(preg_replace("/ +/"," ",$retenclave)),
					"importe"=>str_replace($vowels, "",number_format(($obj->importe),2))
				);
				$retenciones2[$i]= array(
						"impuesto"=>trim(preg_replace("/ +/"," ",$retenclave)),
						"importe"=>str_replace($vowels, "",number_format($obj->importe,2))
				);
				/* $sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facturedet (fk_facture,impuesto,importe)
				   VALUES (".$facid.",'".$obj->impuesto."',".number_format($obj->importe,2).")";
				$rt=$db->query($sql); */
			 }
			 $i++;
		 }
	 }
}

//Retenciones locales Parte 1
$sqm="SELECT COUNT(*) AS count FROM information_schema.tables
					WHERE table_schema = '".$db->database_name."' 
					 AND table_name = '".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales'";
$rqm=$db->query($sqm);
$rqsm=$db->fetch_object($rqm);
$total_retlocal=0;
if($rqsm>0){
	$resqm=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $facid);
	if ($resqm){
		$cfdi_m = $db->num_rows($resqm);
		$m = 0;
		if ($cfdi_m>0){
			while ($m < $cfdi_m){
				$obm = $db->fetch_object($resqm);
				$total_retlocal=str_replace(",", "", number_format(($total_retlocal+$obm->importe),2));
				$m++;
			}
		}
	}
}

//ISH 
if(1){
	$impuestoslocales=array();
	$impuestoish="NO";
	$sql="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'prodcfish'";
	$resql=$db->query($sql);
	$existe_ish = $db->num_rows($resql);
	$totalish=0;
	$imporcen='';
	if( $existe_ish > 0 ){
		if($conf->global->MAIN_MODULE_MULTICURRENCY){
			$sql="SELECT a.fk_product,a.multicurrency_total_ht as total_ht,b.prodcfish,((b.prodcfish/100)*a.multicurrency_total_ht) as impish,c.ref,c.label
							FROM ".MAIN_DB_PREFIX."facturedet a,
							(SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b,
									".MAIN_DB_PREFIX."product c
							WHERE a.fk_facture=".$id." AND
								a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
		}else{
			$sql="SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label
							FROM ".MAIN_DB_PREFIX."facturedet a,
							(SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b,
									".MAIN_DB_PREFIX."product c
							WHERE a.fk_facture=".$facid." AND
								a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
		}
		$ass=$db->query($sql);
		$asf=$db->num_rows($ass);
		if($asf>0){
			while($asd=$db->fetch_object($ass)){
				$totalish=$totalish+$asd->impish;
				$imporcen=$asd->prodcfish;
			}
		}
	}
	if($totalish>0){
		$totalish=str_replace(",", "", number_format($totalish,2));
		$impuestoish=$totalish;
		$factura_total=$factura_total+$totalish-$total_retlocal;
		$factura_total=str_replace(",", "", number_format($factura_total,2));
		$sql="SELECT count(*) as exist FROM ".MAIN_DB_PREFIX."cfdimx_facturedet WHERE fk_facture=".$facid." AND impuesto='ISH'";
		$ass=$db->query($sql);
		$asd=$db->fetch_object($ass);
		if($asd->exist==0){
			$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facturedet (fk_facture,impuesto,importe) 
				   VALUES ('".$id."','ISH','".$totalish."')";
			$ass=$db->query($sql);
// 			$sql="UPDATE ".MAIN_DB_PREFIX."facture SET total_ttc=total_ttc+".$totalish."-".$total_retlocal." WHERE rowid=".$facid;
// 			$ass=$db->query($sql);
// 			$factura_total=$factura_total+$totalish-$total_retlocal;
// 			$factura_total=str_replace(",", "", number_format($factura_total,2));
			$i=0;
			
		}
		
	}
	if($impuestoish!="NO"){
		if($factura_tipo==2){
			$vowels = array(",", "-");
		}else{
			$vowels = array(",");
		}
		$impuestoish=str_replace($vowels, "", number_format($impuestoish,2));
		$totalish=str_replace($vowels, "", number_format($totalish,2));
		$impuestoslocales[$i]= array(
				"totalDeRetenciones"=>$total_retlocal,
				"totalDeTraslados"=>"".($totalish),
				"tasadeTraslado"=>"".$imporcen,
				"impLocTrasladado"=>"ISH",
				"importe"=>"".($totalish));
	}
}
//print $factura_total;exit();
//Retencion local parte 2
if($total_retlocal>0){
	$n=0;
	if($impuestoish!="NO"){
		$n=1;
	}else{
// 		$sql="UPDATE ".MAIN_DB_PREFIX."facture SET total_ttc=total_ttc-".$total_retlocal." WHERE rowid=".$facid;
// 		$ass=$db->query($sql);
		$factura_total=$factura_total-$total_retlocal;
		$factura_total=str_replace(",", "", number_format($factura_total,2));
	}
	$resqm=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $facid);
	if ($resqm){
		$cfdi_m = $db->num_rows($resqm);
		$m = 0;
		if ($cfdi_m>0){
			while ($m < $cfdi_m){
				$obm = $db->fetch_object($resqm);
				if($n==0){
					$impuestoslocales[$n]= array(
							"totalDeRetenciones"=>$total_retlocal,
							"totalDeTraslados"=>'0.00',
							"tasadeRetencion"=>"".str_replace(',', '',number_format($obm->tasa,2)),
							"impLocRetenido"=>$obm->codigo,
							"importeRetenido"=>"".str_replace(',', '',number_format($obm->importe,2)));
				}else{
					$impuestoslocales[$n]= array(
							"tasadeRetencion"=>"".str_replace(',', '',number_format($obm->tasa,2)),
							"impLocRetenido"=>$obm->codigo,
							"importeRetenido"=>"".str_replace(',', '',number_format($obm->importe,2)));
				}
				$n++;
				$m++;
			}
		}
	}
}

// AMM evaluar
// se modifica para multidivisa
if($conf->global->MAIN_MODULE_MULTIDIVISA){
	/* require_once DOL_DOCUMENT_ROOT.'/multidivisa/main.inc.php';//Addon multimoneda */
	$sql="SELECT divisa FROM ".MAIN_DB_PREFIX."multidivisa_facture WHERE fk_object=".$facid;
	$ra=$db->query($sql);
	$rb=$db->fetch_object($ra);
	$moneda=$rb->divisa;
}else{
	if($conf->global->MAIN_MODULE_MULTICURRENCY){
		$sql="SELECT multicurrency_code AS divisa FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$facid;
		$ra=$db->query($sql);
		$rb=$db->fetch_object($ra); 
		$moneda=$rb->divisa;
	}else{
		$moneda = !empty($osd)?$osd:$conf->currency;
	}
}
// $moneda = !empty($osd)?$osd:$conf->currency;

//$fecha_emison = date("Y-m-d");
$hora_emision = date("H:i:s");
$fecha_emison=$factura_fecha;
//$hora_emision=$factura_hora;

if( $factura_tipo==2 ){
	$tipoComprobante="E";
	//$tipoComprobante="egreso"; //Nota de credito
}else{
	$tipoComprobante="I";
	//$tipoComprobante="ingreso"; //factura
}

// DATOS DEL EMISOR
if ($user->fk_warehouse > 0) {
	$emisorsql = "SELECT edc.emisor_rfc, edc.razon_social, edc.regimen, edc.pais, edc.estado, edc.codigo_postal";
	$emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
	$emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
	$emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
	$emisorsql .= " WHERE e.rowid = " . $user->fk_warehouse;

	$reemisorsql = $db->query($emisorsql);

	if ($reemisorsql) {
		$emisor_almacen = $db->fetch_object($emisorsql);
	}

	$rfc_emisor          = $emisor_almacen->emisor_rfc;
	$razon_social_emisor = utf8_decode(utf8_encode($emisor_almacen->razon_social));
	$regimen             = $emisor_almacen->regimen;
	$separa_pais         = explode(":", $emisor_almacen->pais);
	$pais                = utf8_decode($separa_pais[2]);
	$separa_estado       = explode(':', $emisor_almacen->estado);
	$estado_emisor       = getState($separa_estado[0]);
	$estado_emisor       = utf8_decode($estado_emisor);
	$cp                  = $emisor_almacen->codigo_postal;
} else {
	$rfc_emisor          = $conf->global->MAIN_INFO_SIREN;
	$razon_social_emisor = utf8_decode(utf8_encode($conf->global->CFDIMX_RAZON_SOCIAL));
	$regimen             = $conf->global->CFDIMX_REGIMEN_FISCAL;
	$separa_pais         = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
	$pais                = utf8_decode($separa_pais[2]);
	$separa_estado       = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
	$estado_emisor       = getState($separa_estado[0]);
	$estado_emisor       = utf8_decode($estado_emisor);
	$cp                  = $conf->global->MAIN_INFO_SOCIETE_ZIP;
}


//DATOS DEL HEADER DEL COMPROBANTE
$header=array();


$sqn="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion,tipocambio
FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
WHERE fk_facture=".$facid;
$rqn=$db->query($sqn);
$numrn=$db->num_rows($rqn);
if($numrn>0){
	$rrn=$db->fetch_object($sqn);
	$sqn="SELECT code_iso FROM ".MAIN_DB_PREFIX."c_country WHERE rowid=".$separa_pais[0];
	$rqn=$db->query($sqn);
	$rsn=$db->fetch_object($rqn);
	$pais=$rsn->code_iso;

	$sqn="SELECT code_departement FROM ".MAIN_DB_PREFIX."c_departements WHERE rowid=".explode(':',$conf->global->MAIN_INFO_SOCIETE_STATE)[0];
	$rqn=$db->query($sqn);
	$rsn=$db->fetch_object($rqn);
	$estado_emisor=$rsn->code_departement;

	$header["tipoCambio"]=(str_replace(array(",", "-"), "",$rrn->tipocambio));
}

$header["fecha"]=$fecha_emison."T".$hora_emision;
if($factura_tipo==2){
	$vowels = array(",", "-");
}else{
	$vowels = array(",");
}
$header["subTotal"]=(str_replace($vowels, "", number_format($factura_subtotal,2)));

$header["total"]=(str_replace($vowels, "", number_format($factura_total,2)));
if($descheader!=0){
	$auxcp1=str_replace($vowels, "", number_format($descheader,2));
	//$auxcp1=str_replace($vowels, "", truncateFloat($descheader,2));
	//$auxcp2=str_replace($vowels, "", number_format($factura_subtotal,2));
	//$sumcp3=$auxcp1+$auxcp2;
	
	$header["subTotal"]=(str_replace($vowels, "", number_format($auxsubtotal,2)));
	$header["descuento"]=(str_replace($vowels, "", number_format($auxcp1,2)));
	//$header["descuento"]=(str_replace($vowels, "", number_format($descheader,2)));
	$auxcpp1=str_replace($vowels, "", number_format($auxsubtotal,2));
	$auxcpp2=str_replace($vowels, "", number_format($factura_iva,2));
	$sumcpp3=$auxsubtotal-$auxcp1+$auxcpp2;
// 	print $auxsubtotal."::Sub<br>";
// 	print $auxcp1."::descuento<br>";
// 	print $sumcpp3."::Sub<br>";exit();
	$factura_total=$sumcpp3;
	$header["total"]=(str_replace($vowels, "", number_format($sumcpp3,2)));
}
$header["tipoDeComprobante"]=$tipoComprobante;
$header["lugarExpedicion"]=limpiar($lugar_exp);
if($factura_formapago==NULL || $factura_formapago==null || $factura_formapago==''){
	$factura_formapago='No identificado';
}
if($factura_condicionpago==NULL || $factura_condicionpago==null || $factura_condicionpago==''){
	$factura_condicionpago='No identificado';
}
// $header["formaDePago"]=$factura_formapago;
// $header["metodoDePago"]=limpiar(html_entity_decode($factura_condicionpago));
$header["formaDePago"]=limpiar(html_entity_decode($factura_formapago));
$header["condicionesDePago"]=limpiar(html_entity_decode($factura_condicionpago));
$header["metodoDePago"]=$factura_metodopago;
//$header["metodoDePago2"]=$factura_metodopago2;
/* if($tipoComprobante=="egreso"){
	$header["formaDePago"]=limpiar(html_entity_decode($factura_condicionpago));
	$header["metodoDePago"]=$factura_formapago;
} */

//COMPLEMENTARIOS HEADER
$parametros=array();
if( $moneda!="" ){
	$header["moneda"]=trim(preg_replace("/ +/"," ",$moneda));
}
$sqlk = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'tipodecambiocfdi'";
$resqlk=$db->query($sqlk);
$existeTC = $db->num_rows($resqlk);
$banExtratipo=0;
if($existeTC > 0 ){
	$sqlk="SELECT tipodecambiocfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".$id;
	$resqlk=$db->query($sqlk);
	$resk=$db->fetch_object($resqlk);
	if($resk->tipodecambiocfdi!=NULL && $resk->tipodecambiocfdi!=null && $resk->tipodecambiocfdi!=""){
		$header["tipoCambio"]=$resk->tipodecambiocfdi;
		$banExtratipo=1;
	}
}
if($conf->global->MAIN_MODULE_MULTIDIVISA && $banExtratipo==0){
	$sql="SELECT IFNULL(tipo_cambio,null) as tipo_cambio
			FROM ".MAIN_DB_PREFIX."multidivisa
			WHERE fk_document=".$id." AND type_document='facture' AND entity=".$conf->entity." ORDER BY rowid DESC LIMIT 1";
	$rt=$db->query($sql);
	$rts=$db->fetch_object($rt);
	if($rts->tipo_cambio!=NULL){
		$header["tipoCambio"]=$rts->tipo_cambio;
	}
}
if( $serie!="" ){
	$header["serie"]=trim(preg_replace("/ +/"," ",$serie));
}
if( $folio!="" ){
	$header["folio"]=trim(preg_replace("/ +/"," ",$folio));
}
if( $cuenta!="" ){
	$header["numCtaPago"]=trim(preg_replace("/ +/"," ",$cuenta));
}

//ADICIONALES
$adicionales=array();
$adicionales["servicio_id"]="2";

$sqm1 = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'cfdidoctiporelacion'";
$resqlm1=$db->query($sqm1);
$existe_1 = $db->num_rows($resqlm1);
$sqm2 = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'cfdidocuuid'";
$resqlm2=$db->query($sqm2);
$existe_2 = $db->num_rows($resqlm2);
if( $existe_1 > 0 && $existe_2>0){
	$sqlm = "SELECT cfdidoctiporelacion,cfdidocuuid FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
	$resqlm=$db->query($sqlm);
	$objm = $db->fetch_object($resqlm);
	if($objm->cfdidoctiporelacion!="" && $objm->cfdidoctiporelacion!=NULL && $objm->cfdidoctiporelacion!=null && $objm->cfdidocuuid!="" && $objm->cfdidocuuid!=NULL && $objm->cfdidocuuid!=null){ 
		$docrelacionado[0]=array("tipoRelacion"=>$objm->cfdidoctiporelacion,
			 "uuidRelacionado"=>$objm->cfdidocuuid
			 );
		$adicionales["relacionado"]=$docrelacionado;
	}
}

if( $retenciones!="" ){
	$adicionales["retenciones"]=$retenciones;
	$adicionales2["retenciones"]=$retenciones2;
	// realizamos la resta de las retenciones en este caso se aplicara directamente al total
	$index = count($adicionales ["retenciones"]);
	$suma_retenciones = 0;
	for ($ir = 0; $ir < $index; $ir++) {
		$suma_retenciones = $suma_retenciones + $adicionales2 ["retenciones"][$ir]["importe"];
	}
// 	$sql="UPDATE ".MAIN_DB_PREFIX."facture SET total_ttc=total_ttc-".$suma_retenciones." WHERE rowid=".$id;
// 	$ass=$db->query($sql);
	$factura_total = ($factura_total - $suma_retenciones);
	if($factura_tipo==2){
		$vowels = array(",", "-");
	}else{
		$vowels = array(",");
	}
	$header["total"]=(str_replace($vowels, "", number_format($factura_total,2)));
}
if($impuestoish!="NO" || $total_retlocal>0 ){
	$adicionales["ilocales"]=$impuestoslocales;
	//dol_syslog("ADICIONALES ILOCALES::".$adicionales["ilocales"]);
}
$sqn="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
		,tipocambio,certificadoorigen,subdivision,totalusd
FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
WHERE fk_facture=".$facid;
$rqn=$db->query($sqn);
$numrn=$db->num_rows($rqn);
if($numrn>0){
	$rsqn=$db->fetch_object($rqn);
	if($rsqn->clv_pedimento=='' || $rsqn->clv_pedimento==null || $rsqn->clv_pedimento==NULL){
		$rsqn->clv_pedimento=null;
	}
	if($rsqn->no_exportador=='' || $rsqn->no_exportador==null || $rsqn->no_exportador==NULL){
		$rsqn->no_exportador=null;
	}
	if($rsqn->incoterm=='' || $rsqn->incoterm==null || $rsqn->incoterm==NULL){
		$rsqn->incoterm=null;
	}
	if($rsqn->observaciones=='' || $rsqn->observaciones==null || $rsqn->observaciones==NULL){
		$rsqn->observaciones=null;
	}
	if($rsqn->tipocambio=='' || $rsqn->tipocambio==null || $rsqn->tipocambio==NULL){
		$rsqn->tipocambio=null;
	}
	if($rsqn->certificadoorigen=='' || $rsqn->certificadoorigen==null || $rsqn->certificadoorigen==NULL){
		$rsqn->certificadoorigen=null;
	}
	if($rsqn->subdivision=='' || $rsqn->subdivision==null || $rsqn->subdivision==NULL){
		$rsqn->subdivision=null;
	}
	if($rsqn->totalusd=='' || $rsqn->totalusd==null || $rsqn->totalusd==NULL){
		$rsqn->totalusd=null;
	}
	$facex=new Facture($db);
	$facex->fetch($facid);
	$socex=new Societe($db);
	$socex->fetch($facex->socid);
	$sqlex="SELECT code_iso FROM ".MAIN_DB_PREFIX."c_country WHERE code='".$socex->country_code."'";
	$rqex=$db->query($sqlex);
	$rslex=$db->fetch_object($rqex);
	$sqln2="SELECT preciousd,noidentificacion,fraccion_arancelaria,unidadcext,valor_unitario,cantidad_aduana,marca
		FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero_mercancia a, ".MAIN_DB_PREFIX."facturedet b
		WHERE a.fk_facture=".$facid." AND a.fk_facture=b.fk_facture AND a.fk_facturedet=b.rowid"; 
	$reqsn2=$db->query($sqln2);
	$numrn2=$db->num_rows($reqsn2);
	$cona=0;
	if($numrn2>0){
		while($rsqn2=$db->fetch_object($reqsn2)){
			if($rsqn2->preciousd!='' && $rsqn2->noidentificacion!=''){
				if($rsqn2->unidadcext!=null && trim($rsqn2->unidadcext)!=""){$unidadcext=$rsqn2->unidadcext;}else{$unidadcext=null;}
				if($rsqn2->valor_unitario!=null && trim($rsqn2->valor_unitario)!=""){$valor_unitario=$rsqn2->valor_unitario;}else{$valor_unitario=null;}
				if($rsqn2->cantidad_aduana!=null && trim($rsqn2->cantidad_aduana)!=""){$cantidad_aduana=$rsqn2->cantidad_aduana;}else{$cantidad_aduana=null;}
				if($rsqn2->marca!=null && trim($rsqn2->marca)!=""){$marca=$rsqn2->marca;}else{$marca=null;}
				if($cona==0){
					$comercio[$cona]=array(
							'tipoOperacion'=>$rsqn->tipo_operacion,
							'claveDePedimento'=>$rsqn->clv_pedimento,
							'numeroExportadorConfiable'=>$rsqn->no_exportador,
							'incoterm'=>$rsqn->incoterm,
							'observaciones'=>$rsqn->observaciones,
							'rNumRegIdTrib'=>$rsqn->num_identificacion,
							'tipoCambio'=>$rsqn->tipocambio,
							'certificadoOrigen'=>$rsqn->certificadoorigen,
							'subdivision'=>$rsqn->subdivision,
							'totalUSD'=>str_replace(array("-",","),"",number_format($rsqn->totalusd,2)),
							'valorDolares'=>str_replace(array("-",","),"",number_format($rsqn2->preciousd,2)),
							'noIdentificacion'=>$rsqn2->noidentificacion,
							'residenciaFiscal'=>$rslex->code_iso,
							'fraccionArancelaria'=>$rsqn2->fraccion_arancelaria,
							'unidadAduana'=>$unidadcext,
							'valorUnitarioAduana'=>str_replace(array("-",","),"",number_format($valor_unitario,2)),
							'cantidadAduana'=>$cantidad_aduana,
							'marca'=>$marca
					);
					$cona++;
				}else{
					$comercio[$cona]=array(
							'valorDolares'=>str_replace(array("-",","),"",number_format($rsqn2->preciousd,2)),
							'noIdentificacion'=>$rsqn2->noidentificacion,
							'fraccionArancelaria'=>$rsqn2->fraccion_arancelaria,
							'unidadAduana'=>$unidadcext,
							'valorUnitarioAduana'=>str_replace(array("-",","),"",number_format($valor_unitario,2)),
							'cantidadAduana'=>$cantidad_aduana,
							'marca'=>$marca
					);
					$cona++;
				}
			}
		}
	}else{
		$comercio[0]=array(
				'tipoOperacion'=>$rsqn->tipo_operacion,
				'claveDePedimento'=>$rsqn->clv_pedimento,
				'numeroExportadorConfiable'=>$rsqn->no_exportador,
				'incoterm'=>$rsqn->incoterm,
				'observaciones'=>$rsqn->observaciones,
				'rNumRegIdTrib'=>$rsqn->num_identificacion,
				'tipoCambio'=>$rsqn->tipocambio,
				'certificadoOrigen'=>$rsqn->certificadoorigen,
				'subdivision'=>$rsqn->subdivision,
				'totalUSD'=>str_replace(array("-",","),"",number_format($rsqn->totalusd,2))
		);
	}
	if($cona==0){
		$comercio[0]=array(
				'tipoOperacion'=>$rsqn->tipo_operacion,
				'claveDePedimento'=>$rsqn->clv_pedimento,
				'numeroExportadorConfiable'=>$rsqn->no_exportador,
				'incoterm'=>$rsqn->incoterm,
				'observaciones'=>$rsqn->observaciones,
				'rNumRegIdTrib'=>$rsqn->num_identificacion,
				'tipoCambio'=>$rsqn->tipocambio,
				'certificadoOrigen'=>$rsqn->certificadoorigen,
				'subdivision'=>$rsqn->subdivision,
				'totalUSD'=>str_replace(array("-",","),"",number_format($rsqn->totalusd,2)));
	}
	$adicionales["comercio"]=$comercio;
	//print_r($comercio);exit();
}
//DATOS DEL EMISOR
$emisor=array();
$emisor["emisorRFC"]=$rfc_emisor;
$emisor["emisorRegimen"]=limpiar($regimen);
//COMPLEMENTARIOS EMISOR
if( $razon_social_emisor!="" ){
	$emisor["nombre"]=limpiar(trim(preg_replace("/ +/"," ",$razon_social_emisor)));
}
if( $emisor_calle!="" ){
	$emisor["calle"]=trim(preg_replace("/ +/"," ",$emisor_calle));
}
if( $col_emisor!="" ){
	$emisor["colonia"]=trim(preg_replace("/ +/"," ",$col_emisor));
}
if( $emisor_noext!="" ){
	$emisor["noExterior"]=trim(preg_replace("/ +/"," ",$emisor_noext));
}
if( $emisor_noint!="" ){
	$emisor["noInterior"]=trim(preg_replace("/ +/"," ",$emisor_noint));
}
if( $emisor_delompio!="" ){
	$emisor["municipio"]=trim(preg_replace("/ +/"," ",$emisor_delompio));
}
if( $estado_emisor!="" ){
	$emisor["estado"]=limpiar(trim(preg_replace("/ +/"," ",$estado_emisor)));
}
if( $pais!="" ){
	$emisor["pais"]=trim(preg_replace("/ +/"," ",$pais));
}
if( $cp!="" ){
	$emisor["codigoPostal"]=trim(preg_replace("/ +/"," ",$cp));
}

//DATOS DEL RECEPTOR
$receptor=array();
$receptor["rfc"]=$datareceptor_main["rfc"];
//COMPLEMENTARIOS RECEPTOR
$sqn="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
WHERE fk_facture=".$facid;
$rqn=$db->query($sqn);
$numrn=$db->num_rows($rqn);
if($numrn>0){
	$auxpais=$pais;
	$sqn="SELECT b.code_departement, c.code_iso
		FROM ".MAIN_DB_PREFIX."societe a, ".MAIN_DB_PREFIX."c_departements b, ".MAIN_DB_PREFIX."c_country c
		WHERE a.fk_departement=b.rowid AND a.fk_pays=c.rowid AND a.rowid=".$datareceptor_main["rowid"];
	$rqn=$db->query($sqn);
	$rsn=$db->fetch_object($rqn);
	$pais=$rsn->code_iso;
	$datareceptor_main["estado"]=$rsn->code_departement;
	$receptor_delompio=$receptor_cod_municipio;
}
if( $datareceptor_main["razon_social"]!="" ){
	$receptor["nombre"]=trim(preg_replace("/ +/"," ",$datareceptor_main["razon_social"]));
}
if( $receptor_calle!="" ){
	$receptor["calle"]=trim(preg_replace("/ +/"," ",$receptor_calle));
}
if( $receptor_colonia!="" ){
	$receptor["colonia"]=trim(preg_replace("/ +/"," ",$receptor_colonia));
}
if( $receptor_noext!="" ){
	$receptor["noExterior"]=trim(preg_replace("/ +/"," ",$receptor_noext));
}
if( $receptor_noint!="" ){
	$receptor["noInterior"]=trim(preg_replace("/ +/"," ",$receptor_noint));
}
if( $receptor_delompio!="" ){
	$receptor["municipio"]=trim(preg_replace("/ +/"," ",$receptor_delompio));
}
if( $datareceptor_main["estado"]!="" ){
	$receptor["estado"]=limpiar(trim(preg_replace("/ +/"," ",$datareceptor_main["estado"])));
}

$sqm2 = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'numregtrib'";
$resqlm2=$db->query($sqm2);
$existe_2 = $db->num_rows($resqlm2);
if( $existe_1 > 0 && $existe_2>0){
	$sqlm = "SELECT numregtrib FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
	$resqlm=$db->query($sqlm);
	$objm = $db->fetch_object($resqlm);
	if($objm->numregtrib!="" && $objm->numregtrib!=NULL && $objm->numregtrib!=null){ 
		$auxpais=$pais;
		$sqn="SELECT b.code_departement, c.code_iso
			FROM ".MAIN_DB_PREFIX."societe a, ".MAIN_DB_PREFIX."c_departements b, ".MAIN_DB_PREFIX."c_country c
			WHERE a.fk_departement=b.rowid AND a.fk_pays=c.rowid AND a.rowid=".$datareceptor_main["rowid"];
		$rqn=$db->query($sqn);
		$rsn=$db->fetch_object($rqn);
		$pais=$rsn->code_iso;
		$receptor["numRegIdTrib"]=$objm->numregtrib;
	}
}

if( $pais!="" ){
	$receptor["pais"]=trim(preg_replace("/ +/"," ",$pais));
}
if($factura_usocfdi!=""){
	$receptor["usoCFDI"]=trim(preg_replace("/ +/"," ",$factura_usocfdi));
}
$pais=$auxpais;
if( $datareceptor_main["cp"]!="" ){
	$receptor["codigoPostal"]=trim(preg_replace("/ +/"," ",$datareceptor_main["cp"]));
}

$valida_rfc_emisor = validaRFC($rfc_emisor);
$valida_rfc_receptor = validaRFC($receptor["rfc"]);

#Validaci�n por si el RFC del receptor incluye la letra "�"
// $pos = strpos($receptor["rfc"], '�');
// if ($pos >= 0) {
//     $array_rfc = explode(utf8_encode("�"),$receptor["rfc"]);
//     $receptor["rfc"] = $array_rfc[0]."�".$array_rfc[1];
//     $valida_rfc_receptor=true;
// }

if( $valida_rfc_emisor && $valida_rfc_receptor  ){

	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture=".$facid." AND entity_id = " . $_SESSION['dol_entity'];
	$resql=$db->query( $sql );
	$num_llx_cfdimx = $db->num_rows($resql);

	if( $num_llx_cfdimx<1){
		//TIMBRADO

		$sql_control = " SELECT * FROM ".MAIN_DB_PREFIX."cfdmix_control";
		$sql_control .= " WHERE factura_seriefolio = '".$serie."-".$folio."'";

		$res_control = $db->query( $sql_control );

		if(1/* $db->num_rows($sql_control) < 1 */){

			//insert en tabla de control
			unset($sql_control);
			$sql_control  = " INSERT INTO ".MAIN_DB_PREFIX."cfdmix_control ";
			$sql_control .= " (
					       tipo_timbrado,
					       estatus,
						   factura_serie,
				           factura_folio,
				           factura_seriefolio,
				           fecha_emision,
				           hora_emision,
				           entity_id
				          ) ";
			$sql_control .= " VALUES(
					            '0',
				                '1',
				                '".$serie."',
				                '".$folio."',
				                '".$serie."-".$folio."',
				                '".$fecha_emison."',
							    '".$hora_emision."',
				                '".$_SESSION['dol_entity']."'
						        ) ";
			$res_control = $db->query( $sql_control );

		    //$db->affected_rows($res_control);

		    // print '<pre>header<br>'; print_r($header); print '</pre>';
			// print '<pre>conceptos<br>'; print_r($conceptos); print '</pre>';
			// print '<pre>emisor<br>'; print_r($emisor); print '</pre>';
			// print '<pre>receptor<br>'; print_r($receptor); print '</pre>';
			// print '<pre>rfc_emisor<br>'; print_r($rfc_emisor); print '</pre>';
			// print '<pre>passwd_timbrado<br>'; print_r($passwd_timbrado); print '</pre>';
			// print '<pre>adicionales<br>'; print_r($adicionales); print '</pre>';
			// exit();

			$client = new nusoap_client($wscfdi, 'wsdl');
			$result = $client->call("timbraCFDI",
				array(
					"comprobante"=>$header,
					"conceptos"=>$conceptos,
					"emisor"=>$emisor,
					"receptor"=>$receptor,
					"timbrado_usuario"=>$rfc_emisor,
					"timbrado_password"=>$passwd_timbrado,
					"adicionales"=>$adicionales
				)
			);
			//consulta tabla de control
			// delete o update segun el caso
			if( $result["return"]["rsp"]==1 ){
				//ADDENDA INICIO
				$sqlm = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'addendacfdi'";
				$resqlm=$db->query($sqlm);
				$existe_addendacfdi = $db->num_rows($resqlm);
				if( $existe_addendacfdi > 0 ){
					$sqlm = "SELECT addendacfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = ".$facid;
					$resqlm=$db->query($sqlm);
					$objm = $db->fetch_object($resqlm);
					if(trim($objm->addendacfdi)!="" && $objm->addendacfdi!=NULL && $objm->addendacfdi!=null){
						$xel = new SimpleXMLElement($result["return"]["xml"]);
						$xel->addChild($objm->addendacfdi);
						$result["return"]["xml"]=remultimo("/>","",$xel->asXML());
					}
				}
				//ADDENDA FIN

				$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);

				if(strtoupper($serie) == ''  ||  $folio == ''){
					$guion = "";
				}else{
					$guion = "-";
				}
				if(file_exists($conf->facture->dir_output."/".strtoupper($serie).$guion.$folio)){}else{
					mkdir($conf->facture->dir_output."/".strtoupper($serie).$guion.$folio,0700);
				}

				$file_xml = fopen ($conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/".$result["return"]["uuid"].".xml", "w");
				fwrite($file_xml,utf8_encode($result["return"]["xml"]));
				fclose($file_xml);
				$file_xml_str = $conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/".$result["return"]["uuid"].".xml";
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

				$result["return"]["version"]=isset($result["return"]["version"])?$result["return"]["version"]:"1.1";// AMM solucion provicional

				if($cuenta==""){
					$cuenta=0;
				}
				if(!is_numeric($cuenta)){
					$cuenta=0;
				}
				$insert = "
				INSERT INTO ".MAIN_DB_PREFIX."cfdimx (
					factura_serie,
					factura_folio,
					factura_seriefolio,
					xml,
					cadena,
					version,
					selloCFD,
					fechaTimbrado,
					uuid,
					certificado,
					sello,
					certEmisor,
					cancelado,
					u4dig,
					fk_facture,
					fecha_emision,
					hora_emision,
					fecha_timbrado,
					hora_timbrado,
					tipo_timbrado,
					divisa,
					entity_id
				) VALUES (
					'".$serie."',
					'".$folio."',
					'".$serie."-".$folio."',
					'".$db->escape(utf8_decode($result["return"]["xml"]))."',
					'".$db->escape(utf8_decode($result["return"]["cadenaOrig"]))."',
					'".utf8_decode($result["return"]["version"])."',
					'".utf8_decode($result["return"]["selloCFD"])."',
					'".$result["return"]["fechaTimbrado"]."',
					'".$result["return"]["uuid"]."',
					'".utf8_decode($result["return"]["certSAT"])."',
					'".utf8_decode($result["return"]["selloSAT"])."',
					'".$noCertificado."',
					'0',
					'".$cuenta."',
					'".$facid."',
					'".$fecha_emison."',
					'".$hora_emision."',
					'".$separa_ftimbrado[0]."',
					'".$separa_ftimbrado[1]."',
					'".$modotimb."',
					'".$moneda."',
					'".$_SESSION['dol_entity']."'
				)";
				$rr = $db->query( $insert );

				unset($sql_control);
				$sql_control = " DELETE FROM ".MAIN_DB_PREFIX."cfdmix_control";
				$sql_control .= " WHERE factura_seriefolio = '".$serie."-".$folio."'";
				$res_control = $db->query( $sql_control );
				if($factura_total_origen!=$header["total"] && $factura_tipo!=2){
					if($conf->global->MAIN_MODULE_MULTICURRENCY){
						if($header["moneda"]==$conf->currency){
							$sqlupd="UPDATE ".MAIN_DB_PREFIX."facture SET multicurrency_total_ttc=".$header["total"].",total_ttc=".$header["total"]." WHERE rowid=".$facid;
						}else{
							$sqlupd="UPDATE ".MAIN_DB_PREFIX."facture SET multicurrency_total_ttc=".$header["total"]." WHERE rowid=".$facid;
						}
						$ass=$db->query($sqlupd);
					}else{
						$sqlupd="UPDATE ".MAIN_DB_PREFIX."facture SET total_ttc=".$header["total"]." WHERE rowid=".$facid;
						$ass=$db->query($sqlupd);
					}
				}
				$prmsnd["orden_compra"]=getOC( $facid, $db );
				dol_syslog('ORDENpr:GEN:'.$prmsnd["orden_compra"]);
				$prmsnd["logosmall"]=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;

				$resql=$db->query("SELECT * FROM ".MAIN_DB_PREFIX."cfdimx WHERE factura_serie = '".$serie."' AND factura_folio = '".$folio."' AND entity_id = " . $_SESSION['dol_entity']);
				if( $resql ){
					$num_llx_cfdimx = $db->num_rows($resql);
					$i=0;
					if($num_llx_cfdimx){
						while ($i < $num_llx_cfdimx){
							$obj = $db->fetch_object($resql);
							$prmsnd["version"] = $obj->version;
							$prmsnd["uuid"] = $obj->uuid;
							$prmsnd["cadena"] = $obj->cadena;
							$prmsnd["selloCFD"] = $obj->selloCFD;
							$prmsnd["selloSAT"] = $obj->sello;
							$prmsnd["fechaTimbrado"] = $obj->fechaTimbrado;
							$prmsnd["certificado"] = $obj->certificado;
							$prmsnd["certEmisor"] = $obj->certEmisor;
							$prmsnd["u4dig"] = $obj->u4dig;
							$prmsnd["fechaEmision"] = $obj->fecha_emision."T".$obj->hora_emision;
				$prmsnd["coccds"] = "||".$prmsnd["version"]."|".$prmsnd["uuid"]."|".$prmsnd["fechaTimbrado"]."|".$prmsnd["selloCFD"]."|".$obj->certificado."||";			
// $prmsnd["coccds"] = "||".$prmsnd["version"]."|".$prmsnd["uuid"]."|".$prmsnd["fechaTimbrado"]."|".$prmsnd["selloCFD"]."|".$prmsnd["selloSAT"]."||";
							$i++;
						}
					}
				}
				dol_syslog('ANTES::PDF');
				if($factura_tipo==2){
					$vowels = array(",", "-");
					$factura_subtotal=str_replace($vowels, "", $factura_subtotal);
					if($descheader!=0){$descheader=str_replace($vowels, "", $descheader);}
					$factura_iva=str_replace($vowels, "", $factura_iva);
					if($impuestoish!='NO'){$impuestoish=str_replace($vowels, "", $impuestoish);}
					$factura_total=str_replace($vowels, "", $factura_total);
				}
				include("generaPDF.php");
				dol_syslog('DESPUES::PDF');
				/*
				if($datareceptor_main["email"]!=""){
					$msgText="Enviando Factura:".$prmsnd["uuid"];
					$msgHTML="Enviando Factura:".$prmsnd["uuid"];
					$maild->setFrom('Auribox Consulting <'.$conf->global->MAILING_EMAIL_FROM.'>');
					$maild->setSubject('Informacion de contacto');
					$maild->setText($msgText);
					$maild->setHTML($msgHTML);
					if($file_xml_str!=""){
						$maild->addAttachment(new fileAttachment($file_xml_str));
					}
					$address = $datareceptor_main["email"];
					$result  = $maild->send(array($address));
					if(!$result){
						$msg_cfdi_final = "El comprobante se generÃ³ de manera satisfactoria pero no se pudo enviar por correo";
					}else{
						$comp_email=" y enviado por correo a ".$datareceptor_main["email"]." de manera satisfactoria";
					}
				}
				*/
				if($movil=='si'){
				
				}else{
                    $msg_cfdi_final = "La Nota de crédito ".strtoupper($serie)."-".$folio." fue timbrada con éxito";
                    $success = true;
				}
			}else{

				// aqui requiere un web services para consultar insercion en tabla timbrado
				// evaluar respuesta y dar resultado
				if($result["return"]["rsp"]!=""){
					$msg_cfdi_final = $result["return"]["rsp"]." - ".$result["return"]["msg"];
				}else{

					//primera vuelta
					$msg_cfdi_final = "No hubo respuesta para la peticion, intente nuevamente";
				}
			}
		}else{
			//integrar web service de recuperacion de registro
			include 'regenCFDI.php';
		}
	}else{
		$msg_cfdi_final = "La factura con el folio:".strtoupper($serie)."-".$folio." ya esta asociada con un timbre fiscal <br><a href=''>Consultar</a>";
	}
}else{
	if(!$valida_rfc_emisor)
		$msg_cfdi_final = "El RFC del emisor es incorrecto<br>";
	if(!$valida_rfc_receptor)
		$msg_cfdi_final = "El RFC del receptor es incorrecto";
}

?>
