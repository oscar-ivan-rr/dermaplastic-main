<?php
	//============================================================+
	// File name   : timbrado.class.php
	// Begin       : 2021-08-18
	// Last Update : 2021-11-18
	//
	// Description : Generador de Timbrado CFDI 3.3
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//============================================================+

	require_once('lib/nusoap/lib/nusoap.php');
	class TimbradoCFDI{

		var $db;

		public $timbrado_id; 
		public $factura_id;
		public $factura_ref;
		public $cliente_id;
		public $detalle_id;

		public $decimales_cfdi;
		public $vowels;

		public $errores_validaciones = array();
		public $errores_timbrado = array();

		public $factura     = array(); //listo
		public $emisor      = array(); //listo
		public $receptor    = array(); //listo
		public $conceptos   = array(); //listo
		public $header      = array(); //listo
		public $adicionales = array(); //listo solo falta CCE, Carta Porte

		public $url_ws = "http://155.138.207.11:8080/TimbraCFDI33/services/ServicioTimbrado1?wsdl";
		public $passwd_timbrado;
		public $modo_timbrado;
		public $resultado_timbrado;

		public function __construct($db){
		    $this->db = $db;
		}

		//Funcion para Validar RFC
		public function validarRFC($rfc) {

			$rfc = str_replace("-", "", $rfc);
			$cuartoValor = substr($rfc, 3, 1);

			//RFC Persona Moral.
			if (ctype_digit($cuartoValor) && strlen($rfc) == 12) {
				$letras = substr($rfc, 0, 3);
				$numeros = substr($rfc, 3, 6);
				$homoclave = substr($rfc, 9, 3);
				$search = array("Ãƒâ€˜", "&");//caracteres admitidos por el SAT
				$replace = R;//se reemplaza en la busqueda para omitir el caracter
				$letras = str_replace($search, $replace, $letras);	//reemplazar

				if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
					//return 'El RFC '.$rfc.' es valido.';
					return 1;
				}				
			}else{
				//RFC Persona Fisica.
				if (ctype_alpha($cuartoValor) && strlen($rfc) == 13) {
					$letras = substr($rfc, 0, 4);
					$numeros = substr($rfc, 4, 6);
					$homoclave = substr($rfc, 10, 3);
					if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
						//return 'El RFC '.$rfc.' es valido.';
						return 1;
					}
				}else {
					//return 'El RFC '.$rfc.' no es valido.';
					$this->errores_validaciones[] = 'El RFC '.$rfc.' no es valido.';
					return 0;
				}
			}
		}

		//Funcion para obtener la informacion del Emisor
		public function datosEmisor(){
			global $conf;

		    if ($this->validarRFC($conf->global->MAIN_INFO_SIREN)){
		    	
				$sql_direccion = 'SELECT rowid, emisor_calle, emisor_noext, emisor_noint, emisor_colonia, emisor_delompio, codigo_postal, pais, estado FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE emisor_rfc="'.$conf->global->MAIN_INFO_SIREN.'"';

				$res_direccion = $this->db->query($sql_direccion);
	            
	            while ($res_dir = $this->db->fetch_object($res_direccion)) {

	            	$rfc           = $conf->global->MAIN_INFO_SIREN;
					// $razon_social  = utf8_decode($conf->global->MAIN_INFO_SOCIETE_NOM);
					$razon_social  = utf8_decode(utf8_encode($conf->global->CFDIMX_RAZON_SOCIAL));
					$regimen       = $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
					$cp            = $conf->global->MAIN_INFO_SOCIETE_ZIP;

					$separa_pais   = explode(":",$conf->global->MAIN_INFO_SOCIETE_COUNTRY);
					$pais          = utf8_decode($separa_pais[2]);

					$separa_estado = explode(':', $res_dir->estado);
					$estado        = ($state_id == 1070) ? "Ciudad de ".getState($separa_estado[0], 2) : getState($separa_estado[0], 2);

					$municipio     = $res_dir->emisor_delompio;
					$calle         = $res_dir->emisor_calle;
					$colonia       = $res_dir->emisor_colonia;
					$noint         = $res_dir->emisor_noint;
					$noext         = $res_dir->emisor_noext;

					$datos = array(
						"emisorRFC" => $rfc,
						"emisorRegimen" => $regimen,
						"nombre" => '"'.$razon_social.'"',
						"calle" => $calle,
						"colonia" => $colonia,
						"noExterior" => $noext,
						"noInterior" => $noint,
						"municipio" => $municipio,
						"estado" => $estado,
						"pais" => $pais,
						"codigoPostal" => $cp

						);
	            }

		    	$this->emisor = $datos;
		    }else{		        
		        $this->errores_validaciones[] = "El RFC de la empresa esta mal configurado, entra a la configuración para cambiarlo.";
		    }
		}

		//Funcion para obtener la informacion del Receptor (Cliente)
		public function datosReceptor(){
			global $conf;

			$cliente = new Societe($this->db);
        	$cliente->fetch($this->cliente_id);

        	if ($this->validarRFC($cliente->idprof1)){
        		$datos = array(
					"rfc" => $cliente->idprof1,
					"nombre" => $cliente->nom,
					"email" => $cliente->email,
					"colonia" =>  $cliente->options_cce_colonia,
					"calle" => $cliente->address,
					"noint" => '',
					"noext" => '',
					"codigoPostal" => $cliente->zip,
					"cod_municipio" => $cliente->options_cce_codmunicipio,
					"estado" => $cliente->state,
					"usoCFDI" => $this->factura["usocfdi"]
				);

				$this->receptor = $datos;
			}else{
				$this->errores_validaciones[] = "El RFC ('".$cliente->idprof1."') del Receptor ('".$cliente->nom."') esta incorrecto para CFDI 3.3.";
			}
		}

		//Funcion para obtener la informacion de la Factura
		public function datosFactura(){
			global $conf, $langs;

			$factura = new Facture($this->db);
    		$factura->fetch($this->factura_id);

			if($factura->type == 2){
				$tipoComprobante = "E";
			}else{
				$tipoComprobante = "I";
			}

			$separa_ref = explode("-", $factura->ref);

			$serie = $separa_ref[0];
			$folio = $separa_ref[1];
			
			$hora_actual = dol_now();

			$this->factura_ref   = $factura->ref;
			$this->cliente_id    = $factura->socid;
			$fecha               = date("Y-m-d",$factura->date);
			$hora                = date("H:i:s",$hora_actual);

			//Forma de Pago
			$sql_fpago = 'SELECT accountancy_code FROM '.MAIN_DB_PREFIX.'c_paiement WHERE code="'.$factura->mode_reglement_code.'"';
			
			$res_fpago = $this->db->query($sql_fpago);
			$obj_fpago = $this->db->fetch_object($res_fpago);
			
			$forma_pago = "99";
            if($obj_fpago){
            	$forma_pago = $obj_fpago->accountancy_code;
            }

            //Condicion de Pago
			$sql_condpago = 'SELECT code, libelle AS label FROM '.MAIN_DB_PREFIX.'c_payment_term WHERE code="'.$factura->cond_reglement_code.'"';
			
			$res_condpago = $this->db->query($sql_condpago);
            $obj_condpago = $this->db->fetch_object($res_condpago);

            $condicionpago_label = ($langs->trans("PaymentConditionShort".$obj_condpago->code)!=("PaymentConditionShort".$obj_condpago->code)?$langs->trans("PaymentConditionShort".$obj_condpago->code):($obj_condpago->label!='-'?$obj_condpago->label:''));

            // print '<pre>'; print_r($factura); print '</pre>';
            //Arreglo con los datos generales de la Factura
			$datos = array(
					"rowid" => $factura->id,
					"ref" => $factura->ref,
					"serie" => $serie,
					"folio" => $folio,
					"observaciones" => $factura->note_public,
					"cliente_id" => $factura->socid,
					"iva" => str_replace(",", "", number_format($factura->total_tva,2)),
					"subtotal" => str_replace(",", "", number_format($factura->total_ht,2)),
					"total" => str_replace(",", "", number_format($factura->total_ttc,2)),
					"total_origen" => str_replace(",", "", number_format($factura->total_ttc,2)),
					"fecha" => $fecha,
					"hora" => $hora,
					"fecha_timbrado" => $fecha."T".$hora,
					"formapago_id" => $factura->mode_reglement_code,
					"condiciopago_id" => $factura->cond_reglement_code,
					"formapago_label" => $forma_pago,
					"condicionpago_label" => $condicionpago_label,
					"tipoComprobante_id" => $factura->type,
					"tipoComprobante_label" => $tipoComprobante,
					"formpagcfdi" => $factura->array_options["options_formpagcfdi"],
					"usocfdi" => $factura->array_options["options_usocfdi"]
				);

			$this->factura = $datos;

			$datos_header = array(
					"fecha" => $fecha."T".$hora,
					"subTotal" => str_replace(",", "", number_format($factura->total_ht,2)),
					"total" => str_replace(",", "", number_format($factura->total_ttc,2)),
					"tipoDeComprobante" => $tipoComprobante,
					"lugarExpedicion" => $this->emisor["codigoPostal"],
					"formaDePago" => $forma_pago,
					"condicionesDePago" => $condicionpago_label,
					"metodoDePago" => $factura->array_options["options_formpagcfdi"],
					// "metodoDePago" => "PUES",
					"moneda" => $conf->currency,
					"serie" => $serie,
					"folio" => $folio
					);

    		$this->header = $datos_header;
		}

		//Funcion para validar si esta Activo el Modulo de Lotes
		public function validarLotes(){
			global $conf;

			/***Lotes****/
			$lote = "NO";

			if($conf->global->MAIN_MODULE_PRODUCTBATCH){
				$sql_lote = "
						SELECT 
							ifnull(fk_source,null) as fk_source
						FROM 
							".MAIN_DB_PREFIX."element_element
						WHERE 
							fk_target=".$facid." AND targettype='facture' AND sourcetype='commande'";
			
				$res_sql_lote = $this->db->query($sql_lote);


				if($res_sql_lote){
					$obj_sql_lote = $this->db->fetch_object($res_sql_lote);
				
					if($obj_sql_lote->fk_source != NULL && $obj_sql_lote->fk_source != null && $obj_sql_lote->fk_source > 0){
						$sql_lote2 ="
							SELECT 
								ifnull(fk_target,null) as fk_target
							FROM 
								".MAIN_DB_PREFIX."element_element
							WHERE 
								fk_source=".$obj_sql_lote->fk_source." AND sourcetype='commande' AND targettype='shipping'";
						
						$res_sql_lote2 = $this->db->query($sql_lote2);

						if($res_sql_lote2){

							$obj_sql_lote2 = $this->db->fetch_object($res_sql_lote2);
							
							if($obj_sql_lote2->fk_target!=NULL && $obj_sql_lote2->fk_target!=null && $obj_sql_lote2->fk_target>0){
								$lote = $obj_sql_lote2->fk_target;
							}else{
								$lote = "NO";
							}
						}
					}else{
						$lote = "NO";
					}
				}
			}

			return $lote;
		}

		//Funcion para validar si mostrar o no descuentos
		public function validarDescuentosCFDI(){
			global $conf;

			$sql_descuentos ="
					SELECT 
						*
					FROM 
						".MAIN_DB_PREFIX."cfdimx_descuentos
					WHERE 
						entity_id=".$conf->entity;

			$res_sql_descuentos  = $this->db->query($sql_descuentos);
			$num_rows_descuentos = $this->db->num_rows($res_sql_descuentos);

			$mostrar_descuentos = 1;
			if($num_rows_descuentos > 0){
				$obj_descuentos = $this->db->fetch_object($res_sql_descuentos);

				if($obj_descuentos->mostrar==1){
					$mostrar_descuentos = 1;
				}else{
					$mostrar_descuentos = 2;
				}
			}
			return $mostrar_descuentos;
		}

		//Funcion para validar si la Factura tiene CE
		public function validarCCE(){
			$sql_cce ="
				SELECT 
					*
				FROM 
					".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
				WHERE 
					fk_facture=".$this->factura_id;

			$res_sql_cce = $this->db->query($sql_cce);
			$num_sql_cce = $this->db->num_rows($res_sql_cce);

			$cce = '';

			if($num_sql_cce > 0){
				$cce='SI';
			}

			return $cce;
		}

		//Funcion para obtener las retenciones del Producto
		public function obtenerRetencionesProducto($impuesto){
			global $conf;

			$retenBase = null;
			$retenImporte = null;
			$retenTasa = null;
			$retenTipoFactor = null;
			$retencImpuesto = null;

			$sql_ret = "
					SELECT 
						base,impuesto,tipo_factor,tasa,importe 
					FROM 
						".MAIN_DB_PREFIX."cfdimx_retencionesdet 
					WHERE 
						factura_id=".$this->factura_id." AND fk_facturedet=".$this->detalle_id." AND impuesto='".$impuesto."'";
			
			$res_sql_ret = $this->db->query($sql_ret);
			$num_sql_ret = $this->db->num_rows($res_sql_ret);

			if($num_sql_ret > 0){
				$obj_ret         = $this->db->fetch_object($res_sql_ret);
				$retenBase       = str_replace($this->vowels, "",number_format($obj_ret->base,2));
				$retenImporte    = str_replace($this->vowels, "",number_format($obj_ret->importe,2));
				$retenTasa       = $obj_ret->tasa;
				$retenTipoFactor = $obj_ret->tipo_factor;
				$retencImpuesto  = $obj_ret->impuesto;
			}

			$retenciones = array(
							"retenBase" => $retenBase,
							"retenImporte" => $retenImporte,
							"retenTasa" => $retenTasa,
							"retenTipoFactor" => $retenTipoFactor,
							"retencImpuesto" => $retencImpuesto
						);			

			return $retenciones;
		}

		public function obtenerRetenciones(){
			global $conf;

			$factura_total = $this->factura["total"];

			$sql_retenciones = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_retenciones WHERE fk_facture = ".$this->factura_id;
			
			$res_sql_retenciones = $this->db->query($sql_retenciones);

			if ($res_sql_retenciones){
				$tot_ret = $this->db->num_rows($res_sql_retenciones);
				$i = 0;
				if ($tot_ret){
					while ($i < $tot_ret){
						$obj = $this->db->fetch_object($res_sql_retenciones);

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
							"importe"=>str_replace($this->vowels, "",number_format(($obj->importe),2))
						);
						$retenciones2[$i]= array(
								"impuesto"=>trim(preg_replace("/ +/"," ",$retenclave)),
								"importe"=>str_replace($this->vowels, "",number_format($obj->importe,2))
						);
						
						$i++;
					}
				}
			}

			##Retenciones Locales

			$sql_retenciones_locales = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $this->factura_id;

			$res_sql_retenciones_locales = $this->db->query($sql_retenciones_locales);
			$total_retlocal = 0;

			if ($res_sql_retenciones_locales){
				$cfdi_m = $this->db->num_rows($res_sql_retenciones_locales);
				$m = 0;
				if ($cfdi_m>0){
					while ($m < $cfdi_m){
						$obm = $this->db->fetch_object($res_sql_retenciones_locales);
						$total_retlocal = str_replace(",", "", number_format(($total_retlocal+$obm->importe),2));
						$m++;
					}
				}
			}

			##ISH

			$impuestoslocales = array();
			$impuestoish = "NO";

			$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'prodcfish'";
			$resql = $this->db->query($sql);
			$existe_ish = $this->db->num_rows($resql);

			$totalish=0;
			$imporcen='';
			if($existe_ish > 0){
				if($conf->global->MAIN_MODULE_MULTICURRENCY){
					$sql="SELECT a.fk_product,a.multicurrency_total_ht as total_ht,b.prodcfish,((b.prodcfish/100)*a.multicurrency_total_ht) as impish,c.ref,c.label FROM ".MAIN_DB_PREFIX."facturedet a, (SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, ".MAIN_DB_PREFIX."product c WHERE a.fk_facture=".$this->factura_id." AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
				}else {
					$sql="SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label FROM ".MAIN_DB_PREFIX."facturedet a, (SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, ".MAIN_DB_PREFIX."product c WHERE a.fk_facture=".$this->factura_id." AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
				}
				$ass=$this->db->query($sql);
				$asf=$this->db->num_rows($ass);
				if($asf>0){
					while($asd=$this->db->fetch_object($ass)){
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
				$sql="SELECT count(*) as exist FROM ".MAIN_DB_PREFIX."cfdimx_facturedet WHERE fk_facture=".$this->factura_id." AND impuesto='ISH'";
				$ass=$this->db->query($sql);
				$asd=$this->db->fetch_object($ass);
				if($asd->exist==0) {
					$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facturedet (fk_facture,impuesto,importe) VALUES ('".$this->factura_id."','ISH','".$totalish."')";
					$ass=$this->db->query($sql);
					$i=0;
				}
			}

			if($impuestoish!="NO"){
				$impuestoish=str_replace($this->vowels, "", number_format($impuestoish,2));
				$totalish=str_replace($this->vowels, "", number_format($totalish,2));
				$impuestoslocales[$i]= array(
						"totalDeRetenciones"=>$total_retlocal,
						"totalDeTraslados"=>"".($totalish),
						"tasadeTraslado"=>"".$imporcen,
						"impLocTrasladado"=>"ISH",
						"importe"=>"".($totalish));
			}
			// print '<pre>'; print_r($impuestoslocales); print '</pre>';

			##Termina ISH

			//Retencion local parte 2
			if($total_retlocal>0){
				$n=0;
				if($impuestoish!="NO"){
					$n=1;
				}else{
					$factura_total=$factura_total-$total_retlocal;		
					$factura_total=str_replace(",", "", number_format($factura_total,2));
				}

				$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " .$this->factura_id;

				$resqm=$this->db->query($sql);
				if ($resqm){
					$cfdi_m = $this->db->num_rows($resqm);
					$m = 0;
					if ($cfdi_m>0){
						while ($m < $cfdi_m){
							$obm = $this->db->fetch_object($resqm);
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
							$n++;	$m++;
						}
					}
				}
			}

			$this->adicionales["servicio_id"] = "2";

			if($retenciones!= ""){
				$this->adicionales["retenciones"]=$retenciones;
				$adicionales2["retenciones"]=$retenciones2;
				
				// realizamos la resta de las retenciones en este caso se aplicara directamente al total
				$index = count($this->adicionales["retenciones"]);
				$suma_retenciones = 0;
				for ($ir = 0; $ir < $index; $ir++) {
					$suma_retenciones = $suma_retenciones + $adicionales2["retenciones"][$ir]["importe"];
				}
			
				$factura_total = ($factura_total - $suma_retenciones);
				$this->header["total"]=(str_replace($this->vowels, "", number_format($factura_total,2)));
			}

			if($impuestoish != "NO" || $total_retlocal > 0){
				$this->adicionales["ilocales"] = $impuestoslocales;				
			}
		}

		//Funcion para obtener la informacion de los conceptos de la Factura
		public function datosConceptos(){
			global $conf;

			$factura = new Facture($this->db);
    		$factura->fetch($this->factura_id);

    		$producto = new Product($this->db);

    		$this->decimales_cfdi = $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL?$conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL:2;

    		if($this->factura["tipoComprobante_id"] == 2){
				$this->vowels = array(",", "-");
			}else{
				$this->vowels = array(",");
			}

			$subtotal_tmp = 0;

    		// print '<pre>'; print_r($factura); print '1</pre>';

    		for($i=0;$i<sizeof($factura->lines);$i++) {
	
				// if ($object->lines[$i]->fk_product != "") {
    			// multicurrency_code
    			// print '<pre>PartidFac<br>'; print_r($factura->lines[$i]); print '</pre>';
    			// print '<pre>Producto<br>'; print_r($producto); print '</pre>';    			

    			$lote               = $this->validarLotes();
    			$mostrar_descuentos = $this->validarDescuentosCFDI();
    			$cce                = $this->validarCCE();
    			$this->detalle_id   = $factura->lines[$i]->id;

    			if ($factura->lines[$i]->fk_product != "") {

    				$producto->fetch($factura->lines[$i]->fk_product);

    				$descripcion = $producto->description; //agregar validacion limite

    				if($lote != "NO" && $factura->lines[$i]->fk_product != ""){
				    	$sql_lote_prod = "
				    			SELECT 
				    				ifnull(fk_product,null) as fk_product, batch, eatby,(value * -1) as qty
								FROM 
									".MAIN_DB_PREFIX."stock_mouvement
								WHERE 
									fk_product=".$factura->lines[$i]->fk_product." AND fk_origin=".$lote." AND origintype='shipping'";

				    	$res_sql_lote_prod = $this->query($sql_lote_prod);

				    	while($obj_lote_prod = $this->db->fetch_object($res_sql_lote_prod)){
				    		if($obj_lote_prod->fk_product!=NULL && (trim($obj_lote_prod->batch)!="" && $obj_lote_prod->batch!=NULL)){
				    			$descripcion .= " - Cantidad: ".$obj_lote_prod->qty." Lote: ".$obj_lote_prod->batch." Cad: ".$obj_lote_prod->eatby;
				    		}
				    	}
				    }

				    $unidad           = $producto->array_options["options_umed"];
				    $claveprodserv    = $producto->array_options["options_claveprodserv"];
				    $noIdentificacion = $producto->array_options["options_noidenticfdi"];
				    $cuentapredial    = $producto->array_options["options_cuentapredial"];
    			}else{
    				$descripcion      = $factura->lines[$i]->desc;
    				$unidad           = $factura->lines[$i]->array_options["options_umed"];
    				$claveprodserv    = $factura->lines[$i]->array_options["options_claveprodserv"];
    				$noIdentificacion = $factura->lines[$i]->array_options["options_noidenticfdi"];
    				$cuentapredial    = $factura->lines[$i]->array_options["options_cuentapredial"];
    			}

    			$descprodl = null;
				if($factura->lines[$i]->remise_percent!=0  && $mostrar_descuentos==1){
					$descuento  = $factura->lines[$i]->remise_percent/100;
					$descuento2 = ($factura->lines[$i]->subprice*$factura->lines[$i]->qty)*$descuento;
					$descuento2 = str_replace(array("-",","), "",number_format($descuento2,2));
					$descprodl  = str_replace(array("-",","), "",number_format($descuento2,2));
					$descheader = $descheader+$descuento2;
					$total_vuni = number_format($factura->lines[$i]->subprice,$this->decimales_cfdi);
					$total_ttc  = number_format($factura->lines[$i]->subprice*$factura->lines[$i]->qty,$this->decimales_cfdi);
				}else{
					if($factura->lines[$i]->remise_percent!=0  && $mostrar_descuentos==2){
						$descuento  = $factura->lines[$i]->total_ht/$factura->lines[$i]->qty;
						$total_vuni = number_format($descuento,$this->decimales_cfdi);
						$total_ttc  = number_format($factura->lines[$i]->total_ht,$this->decimales_cfdi);
					}else{
						$total_vuni = number_format($factura->lines[$i]->subprice,$this->decimales_cfdi);
						$total_ttc  = number_format($factura->lines[$i]->total_ht,$this->decimales_cfdi);
					}
				}

				///Inicia Ajuste Nota Credito
				if($total_vuni == 0)
					$total_vuni = number_format($factura->lines[$i]->subprice, $this->decimales_cfdi);

				if($total_ttc == 0)
					$total_ttc = number_format($factura->lines[$i]->total_ht, $this->decimales_cfdi);

				if($factura->lines[$i]->total_tva == 0)
					$factura->lines[$i]->total_tva = $factura->lines[$i]->total_tva;
				
				if($factura->lines[$i]->total_ht == 0)
					$factura->lines[$i]->total_ht = $factura->lines[$i]->total_ht;
				///Termina Ajuste Nota Credito

				$retencion_iva = $this->obtenerRetencionesProducto('002');
				$retencion_isr = $this->obtenerRetencionesProducto('001');

				if($cce == 'SI'){
					$sql_cce_noid = "
						SELECT 
							noidentificacion
						FROM 
							".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero_mercancia a
						WHERE 
							a.fk_facture=".$this->factura_id." AND a.fk_facturedet=".$this->detalle_id;

					$res_sql_cce_noid = $this->db->query($sql_cce_noid);
					$num_sql_cce_noid = $this->db->num_rows($res_sql_cce_noid);
					
					$noIdentificacion = 0;
					
					if($num_sql_cce_noid > 0){
						$obj_cce_noid = $this->db->fetch_object($res_sql_cce_noid);
						$noIdentificacion = $obj_cce_noid->noidentificacion;
					}
					$impuesto = '002';
				}else{
					if ($factura->lines[$i]->tva_tx == 0) {
						$impuesto = '000';
					}else {
						$impuesto = '002';
					}
				}

				$cantidad = $factura->lines[$i]->qty;

    			$datos[] = array(
    						"descripcion" => $descripcion,
    						'cantidad' =>str_replace($this->vowels, "",number_format($cantidad,2)),
    						'valorUnitario'=>str_replace($this->vowels, "", $total_vuni),
							'importe'=>str_replace($this->vowels, "", $total_ttc),
							'importeImpuesto'=>str_replace($this->vowels, "",round(($factura->lines[$i]->total_tva),6)),
							'impuesto'=>$impuesto,
							'tasa'=>number_format(($factura->lines[$i]->tva_tx/100),6),
    						"unidad" => $unidad,
    						'noIdentificacion' => $noIdentificacion,
    						'tipoFactor'=>"Tasa",
    						'claveProdServ' => $claveprodserv,
    						'base'=>str_replace($this->vowels, "", number_format($factura->lines[$i]->total_ht,2)),
							'retenBase'=>$retencion_iva["retenBase"],
							'retenImporte'=>$retencion_iva["retenImporte"],  
							'retenTasa'=>number_format($retencion_iva["retenTasa"],6),
							'retenTipoFactor'=>$retencion_iva["retenTipoFactor"],
							'retencImpuesto'=>$retencion_iva["retencImpuesto"],
							'retenBaseISR'=>$retencion_isr["retenBase"],
							'retenImporteISR'=>$retencion_isr["retenImporte"],
							'retenTasaISR'=>number_format($retencion_isr["retenTasa"],6),
							'retenTipoFactorISR'=>$retencion_isr["retenTipoFactor"],
							'retencImpuestoISR'=>$retencion_isr["retencImpuesto"],
							'descuento'=>$descprodl,
							'cuentaPredial'=>$cuentapredial
    					);

    			$subtotal_tmp += str_replace($this->vowels, "",$total_ttc);
    		}

    		$this->conceptos = $datos;
		}

		//Funcion para obtener los datos para el WS
		public function configuracionWS(){
			global $conf;

			$sql_conf_ws = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $conf->entity;

			$res_sql_conf_ws = $this->db->query($sql_conf_ws);

			if ($res_sql_conf_ws) {
			    $conf_num = $this->db->num_rows($res_sql_conf_ws);
			    $i        = 0;
			    if ($conf_num) {
			        while ($i < $conf_num) {
			            $obj = $this->db->fetch_object($res_sql_conf_ws);

			            $this->passwd_timbrado = $obj->password_timbrado_txt;
			            $this->modo_timbrado   = $obj->modo_timbrado;
			            $i++;
			        }
			    }
			}
		}

		//Funcion para guardar el registro de Timbrado
		public function controlTimbrado(){
			global $conf, $user;


			$sql   = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture=".$this->factura_id." AND entity_id=".$conf->entity;
			$resql = $this->db->query($sql);
			$num_llx_cfdimx = $this->db->num_rows($resql);

			if( $num_llx_cfdimx < 1){
				//Tabla para controlar timrbados de facturas y que usuarios las generan
				$sql_control_timbrado = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_control_timbrado";
				$sql_control_timbrado .= " WHERE factura_rowid = ".$this->factura_id;
				$sql_control_timbrado .= " AND tipo_timbrado = ".$this->modo_timbrado;
				$res_control_timbrado = $this->db->query($sql_control_timbrado);

				$registros_control_timbrado = $this->db->num_rows($res_control_timbrado);
				$inserta_control = 0;

				if($registros_control_timbrado > 0){
					$obj_control_timbrado = $this->db->fetch_object($res_control_timbrado);

					$inserta_control = 1;

					if($obj_control_timbrado->estatus == 0){
						$registros_control_timbrado = 0;
						$this->header["fecha"] = $obj_control_timbrado->factura_fecha_timbrado;
					}
				}		

				unset($res_control_timbrado);
				
				//Validacion para que solo entre una vez a la petición del timbrado
				if($registros_control_timbrado == 0){

					//Se inserta el registro en la primera petición
					$sql_insert_control = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_control_timbrado";
					$sql_insert_control .= "(";
					$sql_insert_control .= " factura_rowid,";
					$sql_insert_control .= " factura_serie,";
					$sql_insert_control .= " factura_folio,";
					$sql_insert_control .= " factura_fecha_timbrado,";
					$sql_insert_control .= " tipo_timbrado,";
					$sql_insert_control .= " usuario_rowid,";
					$sql_insert_control .= " estatus,";
					$sql_insert_control .= " entity_id";
					$sql_insert_control .= ")";
					$sql_insert_control .= "VALUES";
					$sql_insert_control .= "(";
					$sql_insert_control .= "'".$this->factura_id."',";
					$sql_insert_control .= "'".$this->header["serie"]."',";
					$sql_insert_control .= "'".$this->header["folio"]."',";
					$sql_insert_control .= "'".$this->header["fecha"]."',";
					$sql_insert_control .= "'".$this->modo_timbrado."',";
					$sql_insert_control .= "'".$user->id."',";
					$sql_insert_control .= "0,";
					$sql_insert_control .= "'".$conf->entity."'";
					$sql_insert_control .= ");";

					if($inserta_control == 0){
						$res_insert_control = $this->db->query($sql_insert_control);
					}
					unset($sql_insert_control);
				}else{
					$this->errores_validaciones[] = "Error 9001: La factura ".strtoupper($this->header["serie"])."-".$this->header["folio"]." ya esta asociada con un timbre fiscal.";
				}
			}else{
				// $msg_cfdi_final .= "Error 9002: La factura ".strtoupper($serie)."-".$folio." ya esta asociada con un timbre fiscal.";

				$this->errores_validaciones[] = "Error 9002: La factura ".strtoupper($this->header["serie"])."-".$this->header["folio"]." ya esta asociada con un timbre fiscal.";
			}

		}

		public function guardarXML(){
			global $conf;

			$separa_ftimbrado = explode("T",$this->resultado_timbrado["return"]["fechaTimbrado"]);

			if(strtoupper($this->header["serie"]) == '' || $this->header["folio"] == ''){
				$guion = "";
			}else{
				$guion = "-";
			}
			if(file_exists($conf->facture->dir_output."/".strtoupper($this->header["serie"]).$guion.$this->header["folio"])){}else{
				mkdir($conf->facture->dir_output."/".strtoupper($this->header["serie"]).$guion.$this->header["folio"],0700);
			}

			$file_xml = fopen ($conf->facture->dir_output."/".strtoupper($this->header["serie"]).$guion.$this->header["folio"]."/".$this->resultado_timbrado["return"]["uuid"].".xml", "w");
			fwrite($file_xml,utf8_encode($this->resultado_timbrado["return"]["xml"]));
			fclose($file_xml);
			$file_xml_str = $conf->facture->dir_output."/".strtoupper($this->header["serie"]).$guion.$this->header["folio"]."/".$this->resultado_timbrado["return"]["uuid"].".xml";
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
		}

		public function guardarRegistroCFDI(){
			global $conf;


			$this->resultado_timbrado["return"]["version"]=isset($this->resultado_timbrado["return"]["version"])?$this->resultado_timbrado["return"]["version"]:"1.1";
			$cuenta = 0;
			// if($cuenta==""){
			// 	$cuenta=0;
			// }
			// if(!is_numeric($cuenta)){
			// 	$cuenta=0;
			// }

			//Se crea el SQL de registro del CFDI
			$insert = "
				INSERT INTO ".MAIN_DB_PREFIX."cfdimx 
					(
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
					) 
				VALUES 
					(
						'".$this->header["serie"]."',
						'".$this->header["folio"]."',
						'".$this->header["serie"]."-".$this->header["folio"]."',
						'".$this->db->escape(utf8_decode($this->resultado_timbrado["return"]["xml"]))."',
						'".$this->db->escape(utf8_decode($this->resultado_timbrado["return"]["cadenaOrig"]))."',
						'".utf8_decode($this->resultado_timbrado["return"]["version"])."',
						'".utf8_decode($this->resultado_timbrado["return"]["selloCFD"])."',
						'".$this->resultado_timbrado["return"]["fechaTimbrado"]."',
						'".$this->resultado_timbrado["return"]["uuid"]."',
						'".utf8_decode($this->resultado_timbrado["return"]["certSAT"])."',
						'".utf8_decode($this->resultado_timbrado["return"]["selloSAT"])."',
						'".$noCertificado."',
						'0',
						'".$cuenta."',
						'".$this->factura_id."',
						'".$this->factura["fecha_emison"]."',
						'".$this->factura["hora_emision"]."',
						'".$separa_ftimbrado[0]."',
						'".$separa_ftimbrado[1]."',
						'".$this->modo_timbrado."',
						'".$this->header["moneda"]."',
						'".$conf->entity."'
					)
				";

			dol_syslog('SQL_Timrbado:'.$insert);

			$rr = $this->db->query($insert);
		}

		public function ajustarTotalFactura(){
			global $conf;


			if($this->factura["total_origen"] != $this->header["total"] && $this->factura["tipoComprobante_id"] != 2){
				if($conf->global->MAIN_MODULE_MULTICURRENCY){
					if($this->header["moneda"] == $conf->currency){
						$sqlupd="UPDATE ".MAIN_DB_PREFIX."facture SET multicurrency_total_ttc=".$this->header["total"].",total_ttc=".$this->header["total"]." WHERE rowid=".$this->factura_id;
					}else{
						$sqlupd="UPDATE ".MAIN_DB_PREFIX."facture SET multicurrency_total_ttc=".$this->header["total"]." WHERE rowid=".$this->factura_id;
					}
					$ass=$this->db->query($sqlupd);
				}else{
					$sqlupd="UPDATE ".MAIN_DB_PREFIX."facture SET total_ttc=".$this->header["total"]." WHERE rowid=".$this->factura_id;
					$ass=$this->db->query($sqlupd);
				}
			}

			// if($this->factura["tipoComprobante_id"] == 2){
			// 	$vowels = array(",", "-");
			// 	$factura_subtotal=str_replace($vowels, "", $factura_subtotal);
			// 	if($descheader!=0){$descheader=str_replace($vowels, "", $descheader);}
			// 	$factura_iva=str_replace($vowels, "", $factura_iva);
			// 	if($impuestoish!='NO'){$impuestoish=str_replace($vowels, "", $impuestoish);}
			// 	$factura_total=str_replace($vowels, "", $factura_total);
			// }
		}

		public function guardarPDF(){
			global $conf;

			$_GET['facid'] = $this->factura_id;

			require_once('cfdi_pdf.php');
		}

		//Funcion para Timbrar Factura
		public function timbrarFactura(){

			//$validacion_rfc = $this->validarRFC($idfactura);
			$this->configuracionWS();
			$this->datosEmisor();
			$this->datosFactura();
			$this->datosConceptos();
			$this->datosReceptor();
			$this->obtenerRetenciones();

			// print '<pre>'; print_r($this->conceptos); print '</pre>';
			// print '<pre>'; print_r($this->factura); print '</pre>';
			// print '<pre>'; print_r($this->receptor); print '</pre>';
			// print '<pre>'; print_r($this->header); print '</pre>';
			// print '<pre>'; print_r($this->adicionales); print '</pre>';
			// print '<pre>'; print_r($this->emisor); print '</pre>';

			// print '-++'.$wscfdi.'++-';

			// print '-++'.count($this->errores_validaciones).'++-';

			if(count($this->errores_validaciones) == 0){

				$this->controlTimbrado();

				// print '<pre>'; print_r($this->header); print '</pre>';

				$timbrarFactura = new nusoap_client($this->url_ws, 'wsdl');
				$resultado_timbrado = $timbrarFactura->call("timbraCFDI",
					array(
						"comprobante"=>$this->header,
						"conceptos"=>$this->conceptos,
						"emisor"=>$this->emisor,
						"receptor"=>$this->receptor,
						"timbrado_usuario"=>$this->emisor["emisorRFC"],
						"timbrado_password"=>$this->passwd_timbrado,
						"adicionales"=>$this->adicionales
					)
				);


				//Timbrado de Factura Correcto
				if($resultado_timbrado["return"]["rsp"] == 1 || $resultado_timbrado["return"]["rsp"] == 307){
					// print '<pre>res_ws<br>'; print_r($resultado_timbrado); print '</pre>';

					//Se actualiza el control con el estatus de factura timbrada
					$sql_update_control = "UPDATE ".MAIN_DB_PREFIX."cfdimx_control_timbrado";
					$sql_update_control .= " SET";
					$sql_update_control .= " estatus = 1";
					$sql_update_control .= " WHERE factura_rowid = ".$this->factura_id;
					$sql_update_control .= " AND tipo_timbrado = ".$this->modo_timbrado;

					$res_update_control = $this->db->query($sql_update_control);
					unset($sql_update_control);

					$this->resultado_timbrado = $resultado_timbrado;

					$this->guardarXML();
					$this->guardarRegistroCFDI();
					$this->ajustarTotalFactura();
					$this->guardarPDF();

					return 1;
				}else{
					if($resultado_timbrado["return"]["rsp"]!=""){
						$this->errores_timbrado[] = $resultado_timbrado["return"]["msg"]."<br><br>".$resultado_timbrado["return"]["msgDetail"];
					}else{
						$this->errores_timbrado[] = "No hubo respuesta para la peticion, intente nuevamente.";
					}
					return -2;
					// if($result["return"]["rsp"]!=""){
					// $msg_cfdi_final .= $result["return"]["rsp"]." - ".$result["return"]["msg"]."<br><br>".$result["return"]["msgDetail"];
					// }else{
					// 	$msg_cfdi_final .= "No hubo respuesta para la peticion, intente nuevamente.";
					// }
				}
			}else{
				return -1;
			}

			##Ya timbra falta el check posterior al timbrado

			##Revisar linea 1225 - 1245 generacfdi
			#falta agregar el tema de descuentos
			#tipo de cambio
			#cfdis_relacionados
			#agregar CCE linea 1397

			##falta adenda

			// print '<pre>Val<br>'; print_r($this->errores_validaciones); print '</pre>';

			// return 'Hello World! :: '.$validacion_rfc;			
		}

	}
?>