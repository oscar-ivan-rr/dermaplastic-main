<?php
	//============================================================+
	// File name   : generatorPDF.php
	// Begin       : 2019-08-15
	// Last Update : 2021-12-16
	//
	// Description : Generador de PDF del módulo CFDI
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//============================================================+

	// Se incluye el archivo principal Main
	require '../../main.inc.php';
	require_once(DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
	require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
	// Se incluye la libreria FPDF.
	require_once("../class/facturacfdimx.class.php");
	require('../lib/fpdf/fpdf.php');
	require('../lib/numero_a_letra.php');
	require('../lib/phpqrcode/qrlib.php');

	$id = (GETPOST('id') != "") ? GETPOST('id') : GETPOST('facid');
	$route = GETPOST('route');

	global $conf, $langs, $db;

	$langs->load("bills");

	class cfdiPDF extends FPDF
	{
		var $widths;
		var $aligns;
		var $data;
		var $emisor;
		var $receptor;
		var $datoscomprobante;
		var $pago_id;
		var $factura_id;

		public function limpiar($cadena){
			/*$cadena = str_replace("&aacute;","á",$cadena);
			$cadena = str_replace("&Aacute;","Á",$cadena);
			$cadena = str_replace("&eacute;","é",$cadena);
			$cadena = str_replace("&Eacute;","É",$cadena);
			$cadena = str_replace("&iacute;","í",$cadena);
			$cadena = str_replace("&Iacute;","Í",$cadena);
			$cadena = str_replace("&oacute;","ó",$cadena);
			$cadena = str_replace("&Oacute;","Ó",$cadena);
			$cadena = str_replace("&uacute;","ú",$cadena);
			$cadena = str_replace("&Uacute;","Ú",$cadena);*/
			$cadena = str_replace(array('á','à','â','ã','ª','ä'),"a",$cadena);
			$cadena = str_replace(array('Á','À','Â','Ã','Ä'),"A",$cadena);
			$cadena = str_replace(array('Í','Ì','Î','Ï'),"I",$cadena);
			$cadena = str_replace(array('í','ì','î','ï'),"i",$cadena);
			$cadena = str_replace(array('é','è','ê','ë'),"e",$cadena);
			$cadena = str_replace(array('É','È','Ê','Ë'),"E",$cadena);
			$cadena = str_replace(array('ó','ò','ô','õ','ö','º'),"o",$cadena);
			$cadena = str_replace(array('Ó','Ò','Ô','Õ','Ö'),"O",$cadena);
			$cadena = str_replace(array('ú','ù','û','ü'),"u",$cadena);
			$cadena = str_replace(array('Ú','Ù','Û','Ü'),"U",$cadena);
			$cadena = str_replace(array('[','^','´','`','¨','~',']'),"",$cadena);
			$cadena = str_replace("ç","c",$cadena);
			$cadena = str_replace("Ç","C",$cadena);
			//$cadena = str_replace("ñ","n",$cadena);
			//$cadena = str_replace("Ñ","N",$cadena);
			$cadena = str_replace("Ý","Y",$cadena);
			$cadena = str_replace("ý","y",$cadena);
			$cadena = str_replace("&aacute;","a",$cadena);
			$cadena = str_replace("&Aacute;","A",$cadena);
			$cadena = str_replace("&eacute;","e",$cadena);
			$cadena = str_replace("&Eacute;","E",$cadena);
			$cadena = str_replace("&iacute;","i",$cadena);
			$cadena = str_replace("&Iacute;","I",$cadena);
			$cadena = str_replace("&oacute;","o",$cadena);
			$cadena = str_replace("&Oacute;","O",$cadena);
			$cadena = str_replace("&uacute;","u",$cadena);
			$cadena = str_replace("&Uacute;","U",$cadena);
			$cadena = str_replace("'","",$cadena);
			$cadena = str_replace("\r\n"," ",$cadena);
			$cadena = str_replace("\r"," ",$cadena);
			$cadena = str_replace("\n"," ",$cadena);
			return $cadena;
		}

		function SetData($id) {
			global $user, $db, $conf, $langs;

			$langs->load('main');

			// print $this->pago_id;
			// die;

			$sql_pagos = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
			$sql_pagos .= " WHERE fk_facture = 0 AND entity = ".$conf->entity;
			$sql_pagos .= " AND fk_paiement = ".$this->pago_id;
			// print $sql_pagos;
			// die;
			$res_pagos = $db->query($sql_pagos);

			if($db->num_rows($res_pagos) > 0){
				while ($obj = $db->fetch_object($res_pagos)) {

					$datos["version"] = $conf->global->CFDIMX_VERSION_SAT;
					$datos["uuid"] = $obj->uuid;
					$datos["moneda"] = 'XXX';
					$datos["cadena"] = $obj->cadena;
					$datos["selloCFD"] = $obj->selloCFD;
					$datos["selloSAT"] = $obj->sello;
					$datos["fechaTimbrado"] = $obj->fecha_emision."T".$obj->hora_emision;
					$datos["certificado"] = $obj->certificado;
					$datos["certEmisor"] = $obj->certEmisor;
					$datos["u4dig"] = "";
					$datos["fechaEmision"] = $obj->fecha_emision."T".$obj->hora_emision;
					$datos["coccds"] = "||1.1|".$datos["uuid"]."|".$datos["fechaTimbrado"]."|".$datos["selloCFD"]."|".$datos["certificado"]."||";

					#Nuevos Campos para CFDI 4.0
					$datos["rfc"] = "";
					$datos["usocfdi"] = "";
					$datos["nombre"] = "";
					$datos["codigopostal"] = "";
					$datos["regimenfiscal"] = "";
				}
			}
			$this->data = $datos;

			//Informacion Emisor
			if ($user->fk_warehouse > 0) {
				$sql_emisor = "SELECT * FROM llx_cfdimx_emisor_datacomp edc";
				$sql_emisor .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
				$sql_emisor .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
				$sql_emisor .= " WHERE e.rowid = " . $user->fk_warehouse;
			} else {
				$selectedEmisor = $_SESSION['selected_emisor'] ? $_SESSION['selected_emisor'] : $conf->global->MAIN_INFO_SIREN;
				$sql_emisor = 'SELECT rowid, emisor_calle, emisor_noext, emisor_noint, emisor_colonia, emisor_delompio, codigo_postal, pais, estado FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE emisor_rfc="'. $selectedEmisor .'" AND entity_id = '.$conf->entity;
			}

			$res_emisor = $db->query($sql_emisor);

            while ($res_emi = $db->fetch_object($res_emisor)) {
			print '<pre>';
			print_r([$sql_emisor, $res_emi->emisor_calle]);
			print '</pre>';
				$rfc           = $res_emi->emisor_rfc ? utf8_decode($res_emi->emisor_rfc) : utf8_decode($conf->global->MAIN_INFO_SIREN);
				$razon_social  = $res_emi->razon_social ? mb_strtoupper(utf8_decode(utf8_encode($res_emi->razon_social))) : mb_strtoupper(utf8_decode(utf8_encode($conf->global->CFDIMX_RAZON_SOCIAL)));

				$regimen       = $res_emi->regimen ? $res_emi->regimen : $conf->global->CFDIMX_REGIMEN_FISCAL;
				$label_regimen = mb_strtoupper($this->_obtener_catalogo($regimen, 4006));
				$cp            = $res_emi->codigo_postal ? $res_emi->codigo_postal : $conf->global->MAIN_INFO_SOCIETE_ZIP;

				$separa_pais   = $res_emi->pais ? explode(":", $res_emi->pais) : explode(":",$conf->global->MAIN_INFO_SOCIETE_COUNTRY);
				$pais          = mb_strtoupper(utf8_decode($separa_pais[2]));

				$separa_estado = $res_emi->estado ? explode(":", $res_emi->estado) : explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
				$state_id      = $separa_estado[0];
				$estado        = mb_strtoupper(utf8_decode(($state_id == 1070) ? "Ciudad de ".getState($separa_estado[0], 2) : getState($separa_estado[0], 2)));

				$municipio     = mb_strtoupper(utf8_decode($res_emi->emisor_delompio));
				$calle         = utf8_decode($res_emi->emisor_calle);
				$colonia       = utf8_decode($res_emi->emisor_colonia);
				$noint         = $res_emi->emisor_noint;
				$noext         = $res_emi->emisor_noext;

				$direccion_pdf   = "";
				$direccion_pdf  .= ($calle != '' ? $calle." " : '');
				$direccion_pdf  .= ($noint != '' ? $noint." " : '');
				$direccion_pdf  .= ($noext != '' ? $noext." " : '');
				$direccion_pdf  .= ($colonia != '' ? $colonia." " : '');

				$direccion_pdf2 = "";
				if($municipio != ''){
					$direccion_pdf2 .= $municipio;
				}

				if($estado != ''){
					$direccion_pdf2  .= ($direccion_pdf2 != '' ? ", ".$estado : '');
				}

				if($pais != ''){
					$direccion_pdf2  .= ($direccion_pdf2 != '' ? ", ".$pais : '');
				}

				if($cp != ''){
					$direccion_pdf2  .= ($direccion_pdf2 != '' ? ", CP. ".$cp : '');
				}

				$informacion_emisor = array(
					"emisorRFC" => $rfc,
					"emisorRegimen" => $regimen,
					"emisorRegimenEtiqueta" => $label_regimen,
					"nombre" => $razon_social,
					"calle" => $calle,
					"colonia" => $colonia,
					"noExterior" => $noext,
					"noInterior" => $noint,
					"municipio" => $municipio,
					"estado" => $estado,
					"pais" => $pais,
					"codigoPostal" => $cp,
					"direccion_pdf" => $direccion_pdf,
					"direccion_pdf2" => $direccion_pdf2
					);
            }

	    	$this->emisor = $informacion_emisor;

	    	//Informacion Factura
	    	$factura = new Facture($db);
		    $factura->fetch($id);

			// print 'id :: '.$id;
			// print 'factura_id :: '.$this->factura_id;
			// print 'pago_id :: '.$this->pago_id;
			//die;

		    $tipoComprobante = "P";

			$observaciones = $factura->note_public;
			$moneda = $datos["moneda"];
			// $forma_pago      = "99";
			// $label_formapago = $forma_pago." - Por Definir";

			// $sql_head_cartaporte = '';
			// $sql_head_cartaporte = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte WHERE facid = ".$id." AND entity =".$conf->entity;
			// $res_head_cartaporte = $db->query($sql_head_cartaporte);
			// $num_head_cartaporte = $db->num_rows($res_head_cartaporte);

			// if($num_head_cartaporte > 0){
			// 	$obj_head_cartaporte = $db->fetch_object($res_head_cartaporte);

			// 	// $tipoComprobante = ($obj_head_cartaporte->entrada_salida == 'Entrada' ? 'I' : 'T');

			// 	if($tipoComprobante == 'T'){
			// 		$moneda                      = "XXX";
			// 		$factura->array_options["options_formpagcfdi"] = "";
			// 		$label_formapago               = "";
			// 		$factura->mode_reglement_code  = "";
			// 		$factura->cond_reglement_code = "";
			// 	}
			// }

			// print $factura->mode_reglement_code.'<br>';
            // if($factura->mode_reglement_code != ''){
            // 	$forma_pago = $factura->mode_reglement_code;
            // 	// $forma_pago = "01";
            // 	$label_formapago = $this->_obtener_catalogo($forma_pago, 4002);
            // }

			##Nuevo catalogo de formas pago
			// if(isset($factura->array_options["options_formapago"])){
			// 	if($tipoComprobante != 'T'){
			// 		$label_formapago = $this->_obtener_catalogo($factura->array_options["options_formapago"], 4007);
			// 	}
			// }

            // $label_condicionpago = $this->_obtener_catalogo($factura->cond_reglement_code, 4003);

            // $label_metodopago = "";
            // if($factura->array_options["options_formpagcfdi"] != ""){
	        //     if($factura->array_options["options_formpagcfdi"] == 'PUE'){
	        //     	$label_metodopago = "PUE - Pago en una sola Exhibici&oacute;n";
	        //     }else{
	        //     	$label_metodopago = "PPD - Pago en Parcialidades o Diferido";
	        //     }
	        // }

            $label_usocfdi = $this->_obtener_catalogo($conf->global->CFDIMX_USOCFDI_PAGOS, 2);
            // $label_cfditiporelacion = $this->_obtener_catalogo($factura->array_options["options_cfdidoctiporelacion"], 4005);
            // $tipoCambio = (isset($factura->array_options["options_tipodecambiocfdi"]) ? $factura->array_options["options_tipodecambiocfdi"] : 0);

            #Nuevos Campos para CFDI 4.0
            $clave_exportacion = "01";

            $label_moneda = "";
            if($moneda == "MXN" || $moneda == "USD"){
            	$label_moneda = "$";
            }

		    $datos_comprobante = array(
						"tipoDeComprobante" => $tipoComprobante,
						// "formaDePago" => $label_formapago,
						// "condicionesDePago" => $label_condicionpago,
						// "metodoDePago" => $label_metodopago,
						"usoCFDI" => $label_usocfdi,
						// "tipoRelacion" => $label_cfditiporelacion,
						"moneda" => $moneda,
						"label_moneda" => $label_moneda,
						// "tipoCambio" => $tipoCambio,
						"observaciones" => $observaciones,
						"clave_exportacion" => $clave_exportacion,
						"version_cfdi" => $datos["version"]
					);

    		$this->datoscomprobante = $datos_comprobante;

    		// print '<pre>'; print_r($datos_comprobante); print '</pre>';
    		// die;

		    //Informacion Receptor
	    	$cliente = new Societe($db);
        	$cliente->fetch($factura->socid);
			$rfc_receptor = $cliente->idprof1;

        	$domicilioReceptor     = new FacturaCFDI($db);
        	$objdomicilioRepcetor  = $domicilioReceptor->getDomicilioFiscalCliente($factura->socid, 0);
        	// print '<pre>'; print_r($objdomicilioRepcetor); print '</pre>'; die;

        	#Campos para CFDI 3.3
        	if(strcmp($conf->global->CFDIMX_VERSION_SAT, "3.3") == 0){
        		$nombre_receptor = $cliente->nom;
        		$cp_receptor     = $cliente->zip;

        		$direccion_receptor_pdf   = "";
				$direccion_receptor_pdf  .= ($cliente->address != '' ? $cliente->address." " : '');

				$direccion_receptor_pdf2 = "";

				if($cliente->state != ''){
					$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", ".$cliente->state : $cliente->state);
				}

				if($cliente->country != ''){
					$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", ".$cliente->country : $cliente->country);
				}

				if($cliente->zip != ''){
					$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", CP. ".$cliente->zip : '');
				}

				$estado_receptor  = $cliente->state;
				$pais_receptor    = $cliente->country;
				$regimen_receptor = "";
        	}else{
        		$nombre_receptor         = $objdomicilioRepcetor["nombre"];
        		$cp_receptor             = $objdomicilioRepcetor["cp"];
        		$direccion_receptor_pdf  = $objdomicilioRepcetor["direccion"];

        		$direccion_receptor_pdf2 = "";

        		if($objdomicilioRepcetor["estado"] != ''){
					$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", ".$objdomicilioRepcetor["estado"] : $objdomicilioRepcetor["estado"]);
				}

				if($objdomicilioRepcetor["pais"] != '' && $objdomicilioRepcetor["pais"] != -1){
					$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", ".$objdomicilioRepcetor["pais"] : $objdomicilioRepcetor["pais"]);
				}

				if($objdomicilioRepcetor["cp"] != ''){
					$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", CP. ".$objdomicilioRepcetor["cp"] : "CP. ".$objdomicilioRepcetor["cp"]);
				}

        		$estado_receptor         = $objdomicilioRepcetor["estado"];
				$pais_receptor           = $objdomicilioRepcetor["pais"];
				$regimen_receptor        = mb_strtoupper($this->_obtener_catalogo($objdomicilioRepcetor["regimenfiscal"], 1));
				$rfc_receptor            = $objdomicilioRepcetor["rfc"];
        	}


    		$informacion_receptor = array(
				"rfc" => $rfc_receptor,
				"nombre" => $nombre_receptor,
				"email" => $cliente->email,
				// "calle" => $cliente->address,
				// "noint" => '',
				// "noext" => '',
				"codigoPostal" => $cp_receptor,
				// "cod_municipio" => $cliente->options_cce_codmunicipio,
				"estado" => $estado_receptor,
				"pais" => $pais_receptor,
				"direccion_pdf" => $direccion_receptor_pdf,
				"direccion_pdf2" => $direccion_receptor_pdf2,
				"ref_client" => $factura->ref_client,
				"regimenfiscal" => $regimen_receptor
			);

			$this->receptor = $informacion_receptor;
		}

		function SetWidths($w){
			//Set the array of column widths
			$this->widths=$w;
		}

		function SetAligns($a){
			//Set the array of column alignments
			$this->aligns=$a;
		}

		#Esta sección de código es el algoritmo que se utiliza para la creación y distribución de la tabla de partidas de la factura (Inicio)
		function Row($data){
			//Calculate the height of the row
			$nb=0;
			for($i=0;$i<count($data);$i++)
				$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
			$h=5.1*$nb;#el valor de 5.1 es para el height del rectangulo

			$y_tmp = (int) $this->GetY() + $h;

			if($y_tmp > 255){
				$this->AddPage();
				$this->SetFont('Arial','B', 8);
				$this->Ln(5);
				//Dibujar recuadro
				$this->SetDrawColor(16,120,179);
				$this->SetFillColor(16,120,179);
				$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');

				//Cabeceras de tabla de partidas
				$this->SetTextColor(255,255,255);
				$this->Cell(16,7,"Clave",0,0,'C');
				$this->Cell(12,7,"Cant.",0,0,'C');
				$this->Cell(10,7,"Unidad",0,0,'C');
				$this->Cell(64,7,utf8_decode("Descripción"),0,0,'C');
				$this->Cell(22,7,"Precio Unitario",0,0,'C');
				$this->Cell(22,7,"I.V.A.",0,0,'C');
				$this->Cell(22,7,"Dto.",0,0,'C');
				$this->Cell(22,7,"Importe",0,0,'C');
				$this->Ln();
				$this->SetTextColor(0,0,0);
			}

			$this->SetFont('Arial','', 7);

			for($i=0;$i<count($data);$i++)
			{
				$w=$this->widths[$i];
				$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
				//Save the current position
				$x=$this->GetX();
				$y=$this->GetY();
				//Draw the border
				$this->Rect($x,$y,$w,$h);
				//Print the text
				$this->MultiCell($w,4,$data[$i],0,$a);#el valor de 5 es el interlineado entre renglones del texto
				//Put the position to the right of the cell
				$this->SetXY($x+$w,$y);
			}
			//Go to the next line
			if ($h >= 100) { #Esta condición no es parte del algoritmo original, fue agregado
				$h=$h-20;
			}

			$this->Ln($h);
		}

		// function CheckPageBreak($h){
		// 	//If the height h would cause an overflow, add a new page immediately
		// 	if($this->GetY()+$h>$this->PageBreakTrigger)
		// 		$this->AddPage($this->CurOrientation);
		// }

		function NbLines($w,$txt){
			//Computes the number of lines a MultiCell of width w will take
			$cw=&$this->CurrentFont['cw'];
			if($w==0)
				$w=$this->w-$this->rMargin-$this->x;
			$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			$s=str_replace("\r",'',$txt);
			$nb=strlen($s);
			if($nb>0 and $s[$nb-1]=="\n")
				$nb--;
			$sep=-1;
			$i=0;
			$j=0;
			$l=0;
			$nl=1;
			while($i<$nb)
			{
				$c=$s[$i];
				if($c=="\n")
				{
					$i++;
					$sep=-1;
					$j=$i;
					$l=0;
					$nl++;
					continue;
				}
				if($c==' ')
					$sep=$i;
				$l+=$cw[$c];
				if($l>$wmax)
				{
					if($sep==-1)
					{
						if($i==$j)
							$i++;
					}
					else
						$i=$sep+1;
					$sep=-1;
					$j=$i;
					$l=0;
					$nl++;
				}
				else
					$i++;
			}
			return $nl;
		}
		#Esta sección de código es el algoritmo que se utiliza para la creación y distribución de la tabla de partidas de la factura (Fin)

		#Sección de código que hace las celdas con esquinas redondeadas (Inicio)
		function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234'){
	        $k = $this->k;
	        $hp = $this->h;
	        if($style=='F')
	            $op='f';
	        elseif($style=='FD' or $style=='DF')
	            $op='B';
	        else
	            $op='S';
	        $MyArc = 4/3 * (sqrt(2) - 1);
	        $this->_out(sprintf('%.2f %.2f m', ($x+$r)*$k, ($hp-$y)*$k ));

	        $xc = $x+$w-$r;
	        $yc = $y+$r;
	        $this->_out(sprintf('%.2f %.2f l', $xc*$k, ($hp-$y)*$k ));
	        if (strpos($angle, '2')===false)
	            $this->_out(sprintf('%.2f %.2f l', ($x+$w)*$k, ($hp-$y)*$k ));
	        else
	            $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);

	        $xc = $x+$w-$r;
	        $yc = $y+$h-$r;
	        $this->_out(sprintf('%.2f %.2f l', ($x+$w)*$k, ($hp-$yc)*$k));
	        if (strpos($angle, '3')===false)
	            $this->_out(sprintf('%.2f %.2f l', ($x+$w)*$k, ($hp-($y+$h))*$k));
	        else
	            $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);

	        $xc = $x+$r;
	        $yc = $y+$h-$r;
	        $this->_out(sprintf('%.2f %.2f l', $xc*$k, ($hp-($y+$h))*$k));
	        if (strpos($angle, '4')===false)
	            $this->_out(sprintf('%.2f %.2f l', ($x)*$k, ($hp-($y+$h))*$k));
	        else
	            $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);

	        $xc = $x+$r ;
	        $yc = $y+$r;
	        $this->_out(sprintf('%.2f %.2f l', ($x)*$k, ($hp-$yc)*$k ));
	        if (strpos($angle, '1')===false)
	        {
	            $this->_out(sprintf('%.2f %.2f l', ($x)*$k, ($hp-$y)*$k ));
	            $this->_out(sprintf('%.2f %.2f l', ($x+$r)*$k, ($hp-$y)*$k ));
	        }
	        else
	            $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
	        $this->_out($op);
	    }

	    function _Arc($x1, $y1, $x2, $y2, $x3, $y3){
	        $h = $this->h;
	        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k, $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
	    }
	    #Sección de código que hace las celdas con esquinas redondeadas (Fin)

	    #Funciones de armado de PDF (Inicio)

	    // Función para mostrar el cuadro de cabecera del comprobante
		function Header() {

			global $db, $conf, $mysoc, $langs;

			$this->SetY(7);

			$pagcid = GETPOST("pagcid");
			$this->pago_id = $pagcid;
			$id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

			$sql_pago_head = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
			$sql_pago_head .= " WHERE fk_paiement = ".$pagcid;
			$sql_pago_head .= " AND fk_facture = 0";
			$sql_pago_head .= " AND entity = ".$conf->entity;
			$res_pago_head = $db->query($sql_pago_head);
			$num_pago_head = $db->num_rows($res_pago_head);

			if($num_pago_head > 0){
				$obj_pago_head = $db->fetch_object($res_pago_head);

				$sql_pago  = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_relacion_facturas";
				$sql_pago .= " WHERE fk_relacion_pagos=".$obj_pago_head->rowid;
				$res_pago = $db->query($sql_pago);
				$num_pago = $db->num_rows($res_pago);
				//print $sql_pago.'<br><br>';
				// $soc_id = 0;
				// $fk_facture = 0;

				if($num_pago > 0){
					$obj_pago = $db->fetch_object($res_pago);
					$id = $obj_pago->fk_facture;
					$this->factura_id = $id;
					// $id     = $fk_facture;
				}
			}

	        // $id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');
	        $this->SetData($id);

			// die;

	        $object = new Facture($db);
	        $object->fetch($id);
	        $logo_valido = 0;
	        $logo = $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;

	        if ($mysoc->logo)
	        {
	            if (is_readable($logo))
	            {
	                $posx   = 10;
	                $posy   = 6;
	                $width  = '';
	                $height = 17;

	                $this->Image($logo, $posx, $posy, $width, $height);    // width=0 (auto)
	                $logo_valido = 1;
	            }
	        }

	        // $sql='SELECT IFNULL(tipo_document,NULL) as tipo_document FROM '.MAIN_DB_PREFIX.'cfdimx_type_document WHERE fk_facture='.$id;
            // $reset=$db->query($sql);
            // $res=$db->fetch_object($reset);

            // if($res->tipo_document != NULL){
            //     if($res->tipo_document==1){
            //         $tipo_doc = "Factura";
            //     }
            //     if($res->tipo_document==2){
            //         $tipo_doc = "Recibo de Honorarios";
            //     }
            //     if($res->tipo_document==3){
            //         $tipo_doc = "Recibo de Arrendamiento";
            //     }
            //     if($res->tipo_document==4){
            //         $tipo_doc = utf8_decode("Nota de Crédito");
            //     }
            //     if($res->tipo_document==5){
            //         $tipo_doc = "Factura de Fletes";
            //     }
            //     if($res->tipo_document==6){
            //         $tipo_doc = "Factura RIF";
            //     }
            //     if($res->tipo_document==7){
            //         $tipo_doc = "Factura Traslado";
            //     }
            // }else{
            //     $tipo_doc="Factura";
            // }

			$tipo_doc = "Complemento de Pagos";

			$pago = new Paiement($db);
			$pago->fetch($pagcid);

            $this->SetFont('Arial','B',7);
            $this->SetX(139);
            $this->Cell(60,4, $tipo_doc.": ".$pago->ref,"",1,'C');// LRT
            $this->SetX(139);
            $this->Cell(60,4,"FOLIO FISCAL (UUID)","",1,'C');// LR
            $this->SetFont('Arial','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["uuid"],"",1,'C');// LR
            $this->SetFont('Arial','B',7);
            $this->SetX(139);
            $this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL SAT","",1,'C');// LR
            $this->SetFont('Arial','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["certificado"],"",1,'C');// LR
            $this->SetFont('Arial','B',7);
            $this->SetX(139);
            $this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL EMISOR","",1,'C');// LR
            $this->SetFont('Arial','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["certEmisor"],"",1,'C');// LR
            $this->SetFont('Arial','B',7);
            $this->SetX(139);
            $this->Cell(60,4,utf8_decode("FECHA Y HORA DE CERTIFICACION"),"",1,'C');// LR
            $this->SetFont('Arial','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["fechaTimbrado"],"",1,'C');// LR
            $this->SetFont('Arial','B',7);
            $this->SetX(139);
            $this->Cell(60,4,utf8_decode("FECHA Y HORA DE EMISION DEL CFDI"),"",1,'C');// LR
            $this->SetFont('Arial','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["fechaEmision"],"",1,'C');// LRB
            $this->SetX(139);
			$this->Cell(60,4,"",0,0,'L');

			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(194,229,243);
			$this->RoundedRect(138,$this->GetY()-45,62,45, 5,'D'); //$x,$y,$w,$h

            //Inicia Informacion Emisor
            //Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,25,125,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();

			$this->SetFont('Arial','B',9);
			$this->SetXY(10, 26);
			$this->Cell(75,4,"    Emisor",0,0,'L');
			$this->SetTextColor(0,0,0);
			$this->Ln(5);

			$this->SetFont('Arial','B',8);
			//Datos Emisor
			$this->SetXY(10, 31);
			$this->Cell(80,4,utf8_decode(utf8_encode($this->limpiar($this->emisor["nombre"]))),0,0,'L');

			$this->SetXY(10, 35);
			$this->Cell(80,4,$this->emisor["emisorRFC"],0,0,'L');

			if($this->emisor["emisorRegimen"] == 625){
				$this->SetFont('Arial','B',7);
			}
			$this->SetXY(10, 39);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->emisor["emisorRegimenEtiqueta"])),0,0,'L');

			$this->SetFont('Arial','',8);
			$this->SetXY(10, 43);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->emisor["direccion_pdf"])),0,0,'L');

			$this->SetXY(10, 47);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->emisor["direccion_pdf2"])),0,0,'L');

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,$this->GetY()-17,125,21,2,'D','1234');
            //Termina Informacion Emisor

            $this->Ln(3);
	    }

	    // Función para mostrar el cuadro de datos del receptor
	    function _pageReceptor($x, $y) {
		    //Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();

			$this->SetFont('Arial','B',9);
			$this->SetXY($x, $this->GetY()-2);
			$this->Cell(75,4,"    Receptor",0,0,'L');
			$this->SetTextColor(0,0,0);

			$this->SetFont('Arial','B',8);
			$this->SetXY($x, $y+7);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->receptor["nombre"])),0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x, $y+11);
			$this->Cell(80,4,$this->receptor["rfc"],0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+60, $y+11);
			$this->Cell(75,4,"Uso CFDI: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+75, $y+11);
			$this->Cell(75,4,utf8_decode(mb_strtoupper($this->limpiar($this->datoscomprobante["usoCFDI"]))),0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+135, $y+11);
			$this->Cell(75,4,utf8_decode($this->limpiar($this->receptor["ref_client"])),0,0,'L');

			$this->SetFont('Arial','',8);
			$this->SetXY($x, $y+15);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->receptor["regimenfiscal"])),0,0,'L');

			$this->SetFont('Arial','',8);
			$this->SetXY($x, $y+19);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->receptor["direccion_pdf"])),0,0,'L');

			$this->SetFont('Arial','',8);
			$this->SetXY($x, $y+23);
			$this->Cell(80,4,utf8_decode($this->limpiar($this->receptor["direccion_pdf2"])),0,0,'L');

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,$this->GetY()-17,190,22,2,'D','1234');

			$this->SetY($this->GetY()+2);
			$this->Ln();
		}

		//Funcion para mostrar el cuadro de la información del Pago
		function _infoPago($x, $y){
			global $db, $conf;

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();

			$this->SetFont('Arial','B',9);
			$this->SetXY($x, $this->GetY()-2);
			$this->Cell(75,4,"    Pago",0,0,'L');
			$this->SetTextColor(0,0,0);

			$pago = new Paiement($db);
			$pago->fetch($this->pago_id);

			$sql_info_pago = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
			$sql_info_pago .= " WHERE";
			$sql_info_pago .= " fk_facture = 0";
			$sql_info_pago .= " AND fk_paiement = ".$this->pago_id;
			$sql_info_pago .= " AND entity = ".$conf->entity;
			$res_info_pago = $db->query($sql_info_pago);

			$fecha = "";
			$forma_pago =  "";
			$moneda = "";
			$monto = "";
			$tipocambio = "";

			if($res_info_pago){
				$obj_info_pago = $db->fetch_object($res_info_pago);

				$fecha = $obj_info_pago->fechaPago;
				$forma_pago = $this->_obtener_catalogo($obj_info_pago->formaDePago, 3);
				$moneda = $obj_info_pago->monedaP;
				$monto = $obj_info_pago->monto;
				$tipocambio = $obj_info_pago->TipoCambioP;
			}

			//Fila 1
			$this->SetFont('Arial','B',8);
			$this->SetXY($x, $y+7);
			$this->Cell(75,4,"Tipo Comprobante: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+27, $y+7);
			$this->Cell(75,4,$this->datoscomprobante["tipoDeComprobante"],0,0,'L');

			$label_etiqueta1 = "";
			$valor_etiqueta1 = "";
			$label_etiqueta2 = "";
			$valor_etiqueta2 = "";
			if(strcmp($this->datoscomprobante["version_cfdi"], "4.0") == 0){
				$label_etiqueta1 = "Version CFDI: ";
				$valor_etiqueta1 = $this->datoscomprobante["version_cfdi"];
				$label_etiqueta2 = "Exportacion: ";
				$valor_etiqueta2 = $this->datoscomprobante["clave_exportacion"];
			}else{
				$label_etiqueta1 = "Version CFDI: ";
				$valor_etiqueta1 = "3.3";
			}

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+45, $y+7);
			$this->Cell(75,4,$label_etiqueta1,0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+65, $y+7);
			$this->Cell(75,4,$valor_etiqueta1,0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+90, $y+7);
			$this->Cell(75,4,$label_etiqueta2,0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+108, $y+7);
			$this->Cell(75,4,$valor_etiqueta2,0,0,'L');

			##Fila 2
			$this->SetFont('Arial','B',8);
			$this->SetXY($x, $y+11);
			$this->Cell(75,4,"Ref: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+7, $y+11);
			$this->Cell(75,4,$pago->ref,0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+45, $y+11);
			$this->Cell(75,4,"Monto: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+55, $y+11);
			$this->Cell(75,4,$monto,0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+80, $y+11);
			$this->Cell(75,4,"Tipo de Cambio: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+103, $y+11);
			$this->Cell(75,4,$tipocambio,0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+128, $y+11);
			$this->Cell(75,4,"Moneda: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+141, $y+11);
			$this->Cell(75,4,$moneda,0,0,'L');

			##Fila 2
			$this->SetFont('Arial','B',8);
			$this->SetXY($x, $y+15);
			$this->Cell(75,4,"Fecha: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+10, $y+15);
			$this->Cell(75,4,$fecha,0,0,'L');

			$this->SetFont('Arial','B',8);
			$this->SetXY($x+45, $y+15);
			$this->Cell(75,4,"Forma de Pago: ",0,0,'L');
			$this->SetFont('Arial','',8);
			$this->SetXY($x+67, $y+15);
			$this->Cell(75,4,strtoupper(utf8_decode($this->limpiar($forma_pago))),0,0,'L');

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,$this->GetY()-9,190,14,2,'D','1234');

			$this->SetY($this->GetY()+1);
			$this->Ln();
		}

		//Funcion para mostrar el cuadro de la información del Pago
		function _docRelacionados($x, $y){
			global $db, $conf, $langs;

			$langs->load("cfdimx@cfdimx");

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();

			$this->SetFont('Arial','B',9);
			$this->SetXY($x, $this->GetY()-2);
			$this->Cell(75,4,"    Documentos Relacionados",0,0,'L');
			$this->SetTextColor(0,0,0);

			$pago = new Paiement($db);
			$pago->fetch($this->pago_id);

			$sql_info_pago = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
			$sql_info_pago .= " WHERE";
			$sql_info_pago .= " fk_facture = 0";
			$sql_info_pago .= " AND fk_paiement = ".$this->pago_id;
			$sql_info_pago .= " AND entity = ".$conf->entity;
			$res_info_pago = $db->query($sql_info_pago);

			if($res_info_pago){
				$obj_info_pago = $db->fetch_object($res_info_pago);

				$sql_doc_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado";
				$sql_doc_rel .= " WHERE fk_recepago = ".$obj_info_pago->rowid;
				$sql_doc_rel .= " AND entity = ".$conf->entity;
				$sql_doc_rel .= " ORDER BY rowid DESC";
				$res_doc_rel = $db->query($sql_doc_rel);
				$num_doc_rel = $db->num_rows($res_doc_rel);

				if($num_doc_rel > 0){
					$y = $this->GetY()+4;

					while($obj_doc_rel = $db->fetch_object($res_doc_rel)){

						$sql_impuestos_guardados = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_pagos_impuestos";
						$sql_impuestos_guardados .= " WHERE";
							$sql_impuestos_guardados .= " entity = ".$conf->entity;
							$sql_impuestos_guardados .= " AND fk_pago = ".$this->pago_id;
							$sql_impuestos_guardados .= " AND uuid_doc_rel = '".$obj_doc_rel->idDocumento."'";
						$sql_impuestos_guardados .= " ORDER BY tipo, impuesto ASC";
						$res_impuestos_guardados = $db->query($sql_impuestos_guardados);
						$num_impuestos_guardados = $db->num_rows($res_impuestos_guardados);

						$num_impuestos = 0;
						if($num_impuestos_guardados > 0){
							$num_impuestos_guardados += 5;
							$num_impuestos = $num_impuestos_guardados * 4;
						}

						$y_tmp = (int)$y + 10;
						$y_tmp = $y_tmp + $num_impuestos;

						if($y_tmp > 271){
							$this->AddPage();
							$this->SetY(54);

							//Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln();

							$this->SetFont('Arial','B',9);
							$this->SetXY($x, $this->GetY()-2);
							$this->Cell(75,4,"    Documentos Relacionados",0,0,'L');
							$this->SetTextColor(0,0,0);

							// $x = $this->GetX();
							$y = $this->GetY()+4;
						}

						$ref_doc = "";
						if($obj_doc_rel->serie != ""){
							$ref_doc .= $obj_doc_rel->serie;
						}

						if($obj_doc_rel->folio != ""){
							if($ref_doc != "")
								$ref_doc .= "-";

							$ref_doc .= $obj_doc_rel->folio;
						}

						##Columna 1
						$this->SetFont('Arial','B',8);
						$this->SetXY($x, $y+1);
						$this->Cell(75,4,"Ref: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+6, $y+1);
						$this->Cell(75,4,$ref_doc,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+35, $y+1);
						$this->Cell(75,4,"UUID: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+44, $y+1);
						$this->Cell(75,4,$obj_doc_rel->idDocumento,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+105, $y+1);
						$this->Cell(75,4,"Moneda: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+118, $y+1);
						$this->Cell(75,4,$obj_doc_rel->monedaDR,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+135, $y+1);
						$this->Cell(75,4,"Tipo de Cambio: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+158, $y+1);
						$this->Cell(75,4,$obj_doc_rel->tipoCambioDR	,0,0,'L');

						##Columna 2
						$this->SetFont('Arial','B',8);
						$this->SetXY($x, $y+5);
						$this->Cell(75,4,"No Parcialidad: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+21, $y+5);
						$this->Cell(75,4,$obj_doc_rel->numParcialidad,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+30, $y+5);
						$this->Cell(75,4,"ImpSaldoAnt: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+49, $y+5);
						$this->Cell(75,4,$obj_doc_rel->impSaldoAnt,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+70, $y+5);
						$this->Cell(75,4,"ImpPagado: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+87, $y+5);
						$this->Cell(75,4,$obj_doc_rel->impPagado,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+110, $y+5);
						$this->Cell(75,4,"ImpSaldoInsoluto: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+136, $y+5);
						$this->Cell(75,4,$obj_doc_rel->impSaldoInsoluto,0,0,'L');

						$this->SetFont('Arial','B',8);
						$this->SetXY($x+155, $y+5);
						$this->Cell(75,4,"Equivalencia: ",0,0,'L');
						$this->SetFont('Arial','',8);
						$this->SetXY($x+174, $y+5);
						$this->Cell(75,4,$obj_doc_rel->equivalencia,0,0,'L');

						##Inicia desglose de Impuestos
						if($num_impuestos_guardados > 0){
							//Dibujar recuadro
							$this->SetDrawColor(194,229,243);
							$this->SetFillColor(194,229,243);
							$this->RoundedRect($x+8,$y+13,165,5,3,'DF','');

							$this->Ln();

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+8, $y+14);
							$this->Cell(75,4,"    Impuestos Relacionados",0,0,'L');

							$this->SetDrawColor(194,229,243);
							$this->SetFillColor(194,229,243);
							$this->RoundedRect($x+8,$y+18,165,5,3,'DF','');

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+8, $y+18);
							$this->Cell(75,4,"Tipo",0,0,'L');

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+30, $y+18);
							$this->Cell(75,4,"Base",0,0,'L');

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+60, $y+18);
							$this->Cell(75,4,"Impuesto",0,0,'L');

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+90, $y+18);
							$this->Cell(75,4,"Tipo Factor",0,0,'L');

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+120, $y+18);
							$this->Cell(75,4,"Tasa O Cuota",0,0,'L');

							$this->SetFont('Arial','B',8);
							$this->SetXY($x+150, $y+18);
							$this->Cell(75,4,"Importe",0,0,'L');

							$y2 = $y + 18;
							while($obj_imp_g = $db->fetch_object($res_impuestos_guardados)){
								$y2 += 5;

								$this->SetFont('Arial','',8);
								$this->SetXY($x+8, $y2);
								$this->Cell(75,4,$langs->trans("TipoImpPDF".$obj_imp_g->tipo),0,0,'L');

								$this->SetXY($x+30, $y2);
								$this->Cell(75,4,$obj_imp_g->base,0,0,'L');

								$this->SetXY($x+60, $y2);
								$this->Cell(75,4,$langs->trans("Imp".$obj_imp_g->impuesto),0,0,'L');

								$this->SetXY($x+90, $y2);
								$this->Cell(75,4,$obj_imp_g->tipo_factor,0,0,'L');

								$this->SetXY($x+120, $y2);
								$this->Cell(75,4,$obj_imp_g->tasa_o_cuota,0,0,'L');

								$this->SetXY($x+150, $y2);
								$this->Cell(75,4,$obj_imp_g->importe,0,0,'L');

							}
						}
						##Termina desglose de Impuestos

						$num_y   = 10 + $num_impuestos;
						$tamanio = 10 + $num_impuestos;

						// Dibujar recuadro
						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x,$y,190,$tamanio,2,'D','1234');
						$y = $y + $num_y;

						// Dibujar recuadro
						// $this->SetDrawColor(16,120,179);
						// $this->SetFillColor(16,120,179);
						// $this->RoundedRect(10,$this->GetY()-5,190,10,2,'D','1234');

						// $this->SetY($this->GetY()+1);
					}
					$this->Ln(5);
				}
			}
		}

		// Función para mostrar el cuadro de observaciones del comprobante
		function _pageObservaciones($x, $y) {
		    $this->SetY($this->GetY()+1);
			$antesMulticel = $this->GetY();

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->SetFont('Arial','B',9);
			$this->SetXY($x, $this->GetY()+2);
			$this->Cell(75,4,"    Observaciones",0,0,'L');
			$this->Ln();

			$this->SetTextColor(0,0,0);

			$this->SetFont('Arial','',8);

			$this->MultiCell(190,4,utf8_decode($this->datoscomprobante["observaciones"]),0, "L", false);
			$this->Ln();

			$despuesMulticel = $this->GetY();

		    $tam_cuadro = ($despuesMulticel - $antesMulticel)-4;
			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(194,229,243);
			$this->RoundedRect($this->GetX(),$this->GetY()-$tam_cuadro+2,190,$tam_cuadro,2,'D','1234');

			// $this->Ln();
			$this->SetY($this->GetY()+3);
		}

		##CFDIS relacionados
		function _pageCFDIRel($x, $y){
			global $db, $conf;

			$pagcid = GETPOST("pagcid");
			// $this->pago_id = $pagcid;
			// $id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');		

			$sql_pago_head = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
			$sql_pago_head .= " WHERE fk_paiement = ".$pagcid;
			$sql_pago_head .= " AND fk_facture = 0";
			$sql_pago_head .= " AND entity = ".$conf->entity;			
			$res_pago_head = $db->query($sql_pago_head);
			$num_pago_head = $db->num_rows($res_pago_head);			

			if($num_pago_head > 0){
				$obj_head_cfdi_rel = $db->fetch_object($res_pago_head);
				$label_tipo_rel = $this->_obtener_catalogo($obj_head_cfdi_rel->tipo_rel, 4);

				if(!is_null($obj_head_cfdi_rel->tipo_rel) && $obj_head_cfdi_rel->tipo_rel != -1){
					##Se valida que el complemento tenga cfdi relacionados
					$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados_pagos";
					$sql_valida_cfdi_rel .= " WHERE fk_pago = " .$pagcid;					
					$res_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
					$valida_cfdi_rel     = $db->num_rows($res_valida_cfdi_rel);					

					if($valida_cfdi_rel > 0){

						$this->SetY($this->GetY()+2);
						$antesMulticel = $this->GetY();
	
						//Dibujar recuadro
						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(16,120,179);
						$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
						$this->SetTextColor(255,255,255);
	
						$this->SetFont('Arial','B',9);
						$this->SetXY($x, $this->GetY()+2);
						$this->Cell(75,4,utf8_decode($this->limpiar("    CFDIS Relacionados - ".$label_tipo_rel)),0,0,'L');
						$this->Ln();
	
						$this->SetTextColor(0,0,0);
	
						$this->SetFont('Arial','',8);
						$txt_cfdi_relacionados = "";
	
						$contador = 1;
						while($cfdi_rel = $db->fetch_object($res_valida_cfdi_rel)){
							if($contador < $valida_cfdi_rel){
								$txt_cfdi_relacionados .= $contador.'.-  '.$cfdi_rel->uuid."\n";
							}else{
								$txt_cfdi_relacionados .= $contador.'.-  '.$cfdi_rel->uuid;
							}
							$contador++;
						}
	
						$this->MultiCell(190,4,utf8_decode($txt_cfdi_relacionados),0, "L", false);
						$this->Ln();
	
						$despuesMulticel = $this->GetY();
	
						$tam_cuadro = ($despuesMulticel - $antesMulticel)-4;
						$tam_cuadro2 = ($despuesMulticel - $antesMulticel)-10;
						//Dibujar recuadro
						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($this->GetX(),$this->GetY()-$tam_cuadro+2,190,$tam_cuadro2,2,'D','1234');
	
						$this->SetY($this->GetY()-3);
					}
				}
			}				
		}

		// Código QR y cadena
	    function _pageQRCode(){
			global $conf, $db;

			// $id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

			// $object = new Facture($db);
		    // $object->fetch($id);

			$pago_id = $this->pago_id;

			$pago=new Paiement($db);
			$pago->fetch($pago_id);

			$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id='.$this->data["uuid"].'&re='.$this->emisor["emisorRFC"].'&rr='.$this->receptor["rfc"].'&tt=0&fe='.substr($this->data["selloCFD"],-8);

			QRcode::png($data_cbb,$conf->facture->dir_output."/".$pago->ref."/Pago_".$this->data["uuid"].".png");
			//$this->CheckPageBreak(20);

			if((int)$this->GetY() > 220){
				$this->AddPage();
			}

			$this->Image($conf->facture->dir_output."/".$pago->ref."/Pago_".$this->data["uuid"].".png",162, $this->GetY()+9,42);

			if((int)$this->GetX() == 20){
				$nueva_cc_x = $this->GetX()-10;
			}else{
				$nueva_cc_x = $this->GetX();
			}

			$this->SetTextColor(0,0,0);

			$this->SetFont('Arial','',6);
			$this->SetY($this->GetY()+13);
			$this->MultiCell(150,4,"\n".$this->data["selloCFD"],0, "L", false);
			$this->SetY($this->GetY()-16);

			$this->SetFont('Arial','B',6);
			$this->Cell(20,4,utf8_decode("SELLO DIGITAL DEL EMISOR"),0,0,'L');
			$this->SetY($this->GetY()+16);

			$this->SetFont('Arial','',6);
			$this->MultiCell(150,4,"\n".$this->data["selloSAT"],0, "L", false);
			$this->SetY($this->GetY()-16);
			$this->SetFont('Arial','B',6);
			$this->Cell(20,4,utf8_decode("SELLO DIGITAL DEL SAT"),0,0,'L');
			$this->SetY($this->GetY()+16);

			$this->SetFont('Arial','',6);
			$this->MultiCell(150,4,"\n".$this->data["coccds"],0, "L", false);
			// $this->MultiCell(150,4,"\n".$this->data["cadena"],0, "L", false);
			$this->SetY($this->GetY()-20);
			$this->SetFont('Arial','B',6);
			$this->Cell(20,4,utf8_decode("CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT"),0,0,'L');
			$this->SetY($this->GetY()+20);

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(194,229,243);
			$this->RoundedRect($this->GetX(),$this->GetY()-53,150,55,2,'D','1234');
		}

		function _obtener_catalogo($codigo='', $tipo_catalogo, $tipo_sql = ''){
		    global $langs, $db;

		    $langs->load('main');

		    $out='';

		    switch ($tipo_catalogo) {
		    	case 1:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_regimen_f WHERE code = '".$codigo."'";
					break;
				case 2:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_uso_cfdi WHERE code = '".$codigo."'";
					break;
				case 3:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_formapago WHERE code = '".$codigo."'";
					break;
				case 4:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_rel WHERE code = '".$codigo."'";
					break;

		    	default:
		    		$sql = "";
		    		break;
		    }

		   	$res_catalogo_sql = $db->query($sql);

		    if ($res_catalogo_sql )
		    {
		        $num = $db->num_rows($res_catalogo_sql);
		        $i = 0;
		        if ($num)
		        {
		        	while ($obj_catalogo = $db->fetch_object($res_catalogo_sql )) {

		            	$valor    = $obj_catalogo->code;
						$etiqueta = $valor." - ".$obj_catalogo->label;

		            	if($valor == $codigo){
		            		$out = $etiqueta;
		            		break;
		            	}
		            }
		    	}
		    }else{
		        dol_print_error($db);
		    }
		    return $out;
		}

	    // Page footer
	    function Footer()
		{
		    // Go to 1.5 cm from bottom
		    $this->SetY(-15);
		    // Select Arial italic 8
		    $this->SetFont('Arial','I',6);
		    // Print current and total page numbers
		    $this->Cell(0,10,utf8_decode('Este documento es una representación gráfica de un CFDI - Página ').$this->PageNo().'/{nb}',0,0,'C');
		}
		#Funciones de armado de PDF (Fin)
	}

	$pagcid      = GETPOST("pagcid");
	$cfdi_commit = $_GET['cfdi_commit'];
    $action_pdf  = GETPOST("action_pdf");


	$pdf=new cfdiPDF();
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->SetFont('Arial','',14);
	//Table with n rows and 6 columns add columns PDF
	$pdf->SetWidths(array(16,12,10,64,22,22,22,22));
	$pdf->SetAligns(array('C','C','C','L','R','R','R','R'));

	$pdf->SetFont('arial','B',10);
	//$pdf->SetFillColor(0,0,0);//Fondo verde de celda
	$pdf->SetTextColor(0, 0, 0); //Letra color negro

	$pago = new Paiement($db);
	$pago->fetch($pagcid);

	// $pdf->SetY($pdf->GetY()+5);
	// $pdf->_pageDatosComprobante($pdf->GetX(), $pdf->GetY());

	$pdf->SetY($pdf->GetY()+5);
	$pdf->_pageReceptor($pdf->GetX(), $pdf->GetY());

	// $pdf->SetY($pdf->GetY()+5);
	$pdf->_pageCFDIRel($pdf->GetX(), $pdf->GetY());

	// if ($object->note_public != "") {
	// 	$pdf->SetY($pdf->GetY()+1);
	// 	$pdf->_pageObservaciones($pdf->GetX(), $pdf->GetY());
	// }

	$pdf->SetY($pdf->GetY()+2);

	$pdf->SetFont('Arial','B', 8);

	//Dibujar recuadro
	$pdf->SetDrawColor(16,120,179);
	$pdf->SetFillColor(16,120,179);
	$pdf->RoundedRect($pdf->GetX(),$pdf->GetY()+1,190,5,3,'DF','1234');

	//Cabeceras de tabla de partidas
	$pdf->SetTextColor(255,255,255);
	$pdf->Cell(16,7,"Clave",0,0,'C');
	$pdf->Cell(12,7,"Cant.",0,0,'C');
	$pdf->Cell(10,7,"Unidad",0,0,'C');
	$pdf->Cell(64,7,utf8_decode("Descripción"),0,0,'C');
	$pdf->Cell(22,7,"Precio Unitario",0,0,'C');
	$pdf->Cell(22,7,"I.V.A.",0,0,'C');
	$pdf->Cell(22,7,"Dto.",0,0,'C');
	$pdf->Cell(22,7,"Importe",0,0,'C');
	$pdf->Ln();
	$pdf->SetTextColor(0,0,0);

	if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
		$pdf->Row(
				array(
						"84111506",
						1,
						"ACT",
						trim("Pago"),
						$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2),
						$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2),
						$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2),
						$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2)
					)
			);
	}else{
		$pdf->Row(
			array(
					"84111506",
					1,
					"ACT",
					trim("Pago"),
					$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2),
					$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2),
					$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2),
					$pdf->datoscomprobante["label_moneda"]." ".number_format(0,2)
				)
		);
	}


	$pdf->Ln();
	$pdf->_infoPago($pdf->GetX(), $pdf->GetY());

	$pdf->Ln();
	$pdf->_docRelacionados($pdf->GetX(), $pdf->GetY());


	$pdf->_pageQRCode();

	// $pdf->_pageCartaPorte();

	$pdf->Output($conf->facture->dir_output."/".$pago->ref."/Pago_".$pdf->data['uuid'].".pdf", 'F');

	// print "<script>window.location.href='../pagosfacturas.php?id=".$pago->id."'</script>";
    if($action_pdf == 1){
        print "<script>window.location.href='../pagosfacturas.php?id=".$pago->id."&regen_pdf=1'</script>";
    }else{
        print "<script>window.location.href='../pagosfacturas.php?id=".$pago->id."&commit=1'</script>";
    }


	// if ($route == "genera")  {
	// 	header('Location: facture.php?facid='.$pago->id.'&cfdi_commit='.$cfdi_commit.'');
	// }else {
		// print '<script>window.location.href="facture.php?facid='.$pago->id.'";</script>';
	// }
?>
