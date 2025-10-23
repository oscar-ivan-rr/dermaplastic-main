<?php
	header('Content-Type: text/html; charset=utf-8');

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
	require '../main.inc.php';
	require_once(DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
	require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
	
	// Se incluye la libreria FPDF.
	//require('lib/fpdf/fpdf.php');
	require('lib/tfpdf/tfpdf.php');
	
	require('lib/numero_a_letra.php');
	require('lib/phpqrcode/qrlib.php');

	$id = (GETPOST('id') != "") ? GETPOST('id') : GETPOST('facid');
	$route = GETPOST('route');

	global $conf, $langs, $db;

	$langs->load("bills");

	//class cfdiPDF extends FPDF
	class cfdiPDF extends tFPDF
	{
		var $widths;
		var $aligns;
		var $data;
		var $emisor;
		var $receptor;
		var $datoscomprobante;

		public function limpiar($cadena){
			$cadena = str_replace("&aacute;","á",$cadena);
			$cadena = str_replace("&Aacute;","Á",$cadena);
			$cadena = str_replace("&eacute;","é",$cadena);
			$cadena = str_replace("&Eacute;","É",$cadena);
			$cadena = str_replace("&iacute;","í",$cadena);
			$cadena = str_replace("&Iacute;","Í",$cadena);
			$cadena = str_replace("&oacute;","ó",$cadena);
			$cadena = str_replace("&Oacute;","Ó",$cadena);
			$cadena = str_replace("&uacute;","ú",$cadena);
			$cadena = str_replace("&Uacute;","Ú",$cadena);
			return $cadena;
		}

		function SetData($id) {
			global $user, $db, $conf, $langs;

			$langs->load('main');

			$query = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx WHERE fk_facture='.$id.' AND entity_id='.$conf->entity;

			$resql = $db->query($query);

			if($db->num_rows($resql) > 0){
				while ($obj = $db->fetch_object($resql)) {

					$datos["version"] = $obj->version;
					$datos["uuid"] = $obj->uuid;
					$datos["moneda"] = $obj->divisa;
					$datos["cadena"] = $obj->cadena;
					$datos["selloCFD"] = $obj->selloCFD;
					$datos["selloSAT"] = $obj->sello;
					$datos["fechaTimbrado"] = $obj->fechaTimbrado;
					$datos["certificado"] = $obj->certificado;
					$datos["certEmisor"] = $obj->certEmisor;
					$datos["u4dig"] = $obj->u4dig;
					$datos["fechaEmision"] = $obj->fecha_emision."T".$obj->hora_emision;
					$datos["coccds"] = "||".$datos["version"]."|".$datos["uuid"]."|".$datos["fechaTimbrado"]."|".$datos["selloCFD"]."|".$obj->certificado."||";
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

		    if($factura->type == 2){
				$tipoComprobante = "E";
			}else{
				$tipoComprobante = "I";
			}

			$observaciones = $factura->note_public;
			$moneda = $datos["moneda"];
			$forma_pago      = "99";
			$label_formapago = $forma_pago." - Por Definir";

			$sql_head_cartaporte = '';
			$sql_head_cartaporte = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte WHERE facid = ".$id." AND entity =".$conf->entity;
			$res_head_cartaporte = $db->query($sql_head_cartaporte);
			$num_head_cartaporte = $db->num_rows($res_head_cartaporte);

			if($num_head_cartaporte > 0){
				$obj_head_cartaporte = $db->fetch_object($res_head_cartaporte);

				$tipoComprobante = ($obj_head_cartaporte->entrada_salida == 'Entrada' ? 'I' : 'T');

				if($tipoComprobante == 'T'){
					$moneda                      = "XXX";
					$factura->array_options["options_formpagcfdi"] = "";
					$label_formapago               = "";
					$factura->mode_reglement_code  = "";
					$factura->cond_reglement_code = "";
				}
			}

			// print $factura->mode_reglement_code.'<br>';
            if($factura->mode_reglement_code != ''){
            	$forma_pago = $factura->mode_reglement_code;
            	// $forma_pago = "01";
            	$label_formapago = mb_strtoupper($this->_obtener_catalogo($forma_pago, 4002));
            }

            $label_condicionpago = mb_strtoupper($this->_obtener_catalogo($factura->cond_reglement_code, 4003));

            $label_metodopago = "";
            if($factura->array_options["options_formpagcfdi"] != ""){
	            if($factura->array_options["options_formpagcfdi"] == 'PUE'){
	            	$label_metodopago = "PUE - Pago en una sola Exhibición";
	            }else{
	            	$label_metodopago = "PPD - Pago en Parcialidades o Diferido";
	            }
	        }
			$label_metodopago = mb_strtoupper(utf8_decode(utf8_encode($label_metodopago)));

            $label_usocfdi = $this->_obtener_catalogo($factura->array_options["options_usocfdi"], 4004);
            $label_cfditiporelacion = $this->_obtener_catalogo($factura->array_options["options_cfdidoctiporelacion"], 4005);
            $tipoCambio = (isset($factura->array_options["options_tipodecambiocfdi"]) ? $factura->array_options["options_tipodecambiocfdi"] : 0);

		    $datos_comprobante = array(
						"tipoDeComprobante" => $tipoComprobante,
						"formaDePago" => $label_formapago,
						"condicionesDePago" => $label_condicionpago,
						"metodoDePago" => $label_metodopago,
						"usoCFDI" => $label_usocfdi,
						"tipoRelacion" => $label_cfditiporelacion,
						"moneda" => $moneda,
						"tipoCambio" => $tipoCambio,
						"observaciones" => $observaciones
					);

    		$this->datoscomprobante = $datos_comprobante;

    		// print '<pre>'; print_r($datos_comprobante); print '</pre>';
    		// die;

		    //Informacion Receptor
	    	$cliente = new Societe($db);
        	$cliente->fetch($factura->socid);

        	$direccion_receptor_pdf   = "";
			$direccion_receptor_pdf  .= ($cliente->address != '' ? $cliente->address." " : '');
			// $direccion_receptor_pdf  .= ($noint != '' ? $noint." " : '');
			// $direccion_receptor_pdf  .= ($noext != '' ? $noext." " : '');
			// $direccion_receptor_pdf  .= ($colonia != '' ? $colonia." " : '');
			
			$direccion_receptor_pdf2 = "";
			// if($municipio != ''){
			// 	$direccion_pdf2 .= $municipio;
			// }

			if($cliente->state != ''){
				$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", ".$cliente->state : $cliente->state);
			}

			if($cliente->country != ''){
				$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", ".$cliente->country : $cliente->country);
			}

			if($cliente->zip != ''){
				$direccion_receptor_pdf2  .= ($direccion_receptor_pdf2 != '' ? ", CP. ".$cliente->zip : '');
			}


    		$informacion_receptor = array(
				"rfc" => $cliente->idprof1,
				"nombre" => $cliente->nom,
				"email" => $cliente->email,
				// "colonia" =>  $cliente->options_cce_colonia,
				"calle" => $cliente->address,
				"noint" => '',
				"noext" => '',
				"codigoPostal" => $cliente->zip,
				"cod_municipio" => $cliente->options_cce_codmunicipio,
				"estado" => $cliente->state,
				"pais" => $cliente->country,
				"direccion_pdf" => $direccion_receptor_pdf,
				"direccion_pdf2" => $direccion_receptor_pdf2,
				"ref_client" => $factura->ref_client
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
				$this->SetFont('DejaVu','', 8);
				$this->Ln(5);
				//Dibujar recuadro
				$this->SetFillColor(16,120,179);
				$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');

				//Cabeceras de tabla de partidas
				$this->SetTextColor(255,255,255);
				$this->Cell(16,7,"Clave",0,0,'C');
				$this->Cell(12,7,"Cant.",0,0,'C');
				$this->Cell(10,7,"Unidad",0,0,'C');
				$this->Cell(64,7,"Descripción",0,0,'C');
				$this->Cell(22,7,"Precio Unitario",0,0,'C');
				$this->Cell(22,7,"I.V.A.",0,0,'C');
				$this->Cell(22,7,"Dto.",0,0,'C');
				$this->Cell(22,7,"Importe",0,0,'C');
				$this->Ln();
				$this->SetTextColor(0,0,0);
			}

			$this->SetFont('DejaVu','', 7);

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

		function CheckPageBreak($h){
			//If the height h would cause an overflow, add a new page immediately
			if($this->GetY()+$h>$this->PageBreakTrigger)
				$this->AddPage($this->CurOrientation);
		}

		function NbLines($w,$txt) { 
			return intval($this->GetStringWidth($txt)/$w) + 1;
		}

		function NbLines_($w,$txt){
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

	        $id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');
	        $this->SetData($id);	      

	        $object = new Facture($db);
	        $object->fetch($id);
	        $logo_valido = 0;
	        $logo = $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;

	        if ($mysoc->logo)
	        {
	            if (is_readable($logo))
	            {
	                $posx   = 10;
	                $posy   = 10;
	                $width  = '';
	                $height = 15;

	                $this->Image($logo, $posx, $posy, $width, $height);    // width=0 (auto)
	                $logo_valido = 1;
	            }
	        }

	        $sql='SELECT IFNULL(tipo_document,NULL) as tipo_document FROM '.MAIN_DB_PREFIX.'cfdimx_type_document WHERE fk_facture='.$id;
            $reset=$db->query($sql);
            $res=$db->fetch_object($reset);

            if($res->tipo_document != NULL){
                if($res->tipo_document==1){
                    $tipo_doc = "Factura";
                }
                if($res->tipo_document==2){
                    $tipo_doc = "Recibo de Honorarios";
                }
                if($res->tipo_document==3){
                    $tipo_doc = "Recibo de Arrendamiento";
                }
                if($res->tipo_document==4){
                    $tipo_doc = "Nota de Crédito";
                }
                if($res->tipo_document==5){
                    $tipo_doc = "Factura de Fletes";
                }
                if($res->tipo_document==6){
                    $tipo_doc = "Factura RIF";
                }
                if($res->tipo_document==7){
                    $tipo_doc = "Factura Traslado";
                }
            }else{
                $tipo_doc="Factura";
            }

            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
            $this->Cell(60,4, $tipo_doc.": ".$object->ref,"",1,'C');// LRT
            $this->SetX(139);
				$this->SetTextColor(0,104,160);
            $this->Cell(60,4,"FOLIO FISCAL (UUID)","",1,'C');// LR
				$this->SetTextColor(0,0,0);
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["uuid"],"",1,'C');// LR
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
				$this->SetTextColor(0,104,160);
            $this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL SAT","",1,'C');// LR
				$this->SetTextColor(0,0,0);
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["certificado"],"",1,'C');// LR
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
				$this->SetTextColor(0,104,160);
            $this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL EMISOR","",1,'C');// LR
				$this->SetTextColor(0,0,0);
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["certEmisor"],"",1,'C');// LR
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
				$this->SetTextColor(0,104,160);
            $this->Cell(60,4,"FECHA Y HORA DE CERTIFICACIÓN","",1,'C');// LR
				$this->SetTextColor(0,0,0);
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["fechaTimbrado"],"",1,'C');// LR
            $this->SetFont('DejaVu','',7);
            $this->SetX(139);
				$this->SetTextColor(0,104,160);
            $this->Cell(60,4,"FECHA Y HORA DE EMISIÓN DEL CFDI","",1,'C');// LR
				$this->SetTextColor(0,0,0);
            $this->SetFont('DejaVu','',7);
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
			$this->RoundedRect(10,27,125,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();
			// $this->SetFont('DejaVu','',9);

			$this->SetFont('DejaVu','',9);
			$this->SetXY(10, 28);
			$this->Cell(75,4,"    Emisor",0,0,'L');
			$this->SetTextColor(0,0,0);
			$this->Ln(5);
			// print '<pre>'; print_r($this->emisor); print '</pre>'; die;

			$this->SetFont('DejaVu','',8);
			//Datos Emisor
			$this->SetXY(10, 33);
			$this->Cell(80,4,$this->limpiar($this->emisor["nombre"]),0,0,'L');

			$this->SetXY(10, 37);
			$this->Cell(80,4,$this->emisor["emisorRFC"],0,0,'L');

			// $this->SetFont('DejaVu','',7.5);
			$this->SetXY(38, 37);
				$this->SetTextColor(0,104,160);
			$this->Cell(80,4,$this->limpiar($this->emisor["emisorRegimenEtiqueta"]),0,0,'L');
				$this->SetTextColor(0,0,0);

			$this->SetFont('DejaVu','',8);
			$this->SetXY(10, 41);
			$this->Cell(80,4,$this->limpiar($this->emisor["direccion_pdf"]),0,0,'L');

			$this->SetXY(10, 45);
			$this->Cell(80,4,$this->limpiar($this->emisor["direccion_pdf2"]),0,0,'L');

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,$this->GetY()-13,125,19,2,'D','1234');
            //Termina Informacion Emisor

            $this->Ln();
	    }
	    
	    ##CFDIS relacionados
		function _pageCFDIRel($x, $y){
			global $db;

			$facid = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

			if($this->datoscomprobante["tipoRelacion"] != '' || $this->datoscomprobante["tipoRelacion"] != null){
				##Se valida que la factura tenga cfdi relacionados
				$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE fk_facture = " .$facid;
				$res_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
				$valida_cfdi_rel = $db->num_rows($res_valida_cfdi_rel);

				if($valida_cfdi_rel > 0){ 

					$this->SetY($this->GetY()+2);
					$antesMulticel = $this->GetY();

					//Dibujar recuadro
					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x, $this->GetY()+2);
					$this->Cell(75,4,$this->limpiar("    CFDIS Relacionados - ".$this->datoscomprobante["tipoRelacion"]),0,0,'L');
					$this->Ln();

					$this->SetTextColor(0,0,0);

					$this->SetFont('DejaVu','',8);
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
					
					$this->MultiCell(190,4,$txt_cfdi_relacionados,0, "L", false);
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

		// Función para mostrar el cuadro de datos del receptor
	    function _pageDatosComprobante($x, $y) {
	    	//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();

			$this->SetFont('DejaVu','',9);
			$this->SetXY($x, $this->GetY()-2);
			$this->Cell(75,4,"    Datos del Comprobante",0,0,'L');

			//Columna 1
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+7);
				$this->SetTextColor(0,104,160);
			$this->Cell(75,4,"Tipo Comprobante: ",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+27, $y+7);
			$this->Cell(75,4,$this->datoscomprobante["tipoDeComprobante"],0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+11);
				$this->SetTextColor(0,104,160);
			$this->Cell(75,4,"Forma de Pago: ",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+22, $y+11);
			$this->Cell(75,4,$this->limpiar($this->datoscomprobante["formaDePago"]),0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+15);
				$this->SetTextColor(0,104,160);
			$this->Cell(75,4,"Condiciones de Pago: ",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+31, $y+15);
			$this->Cell(75,4,$this->limpiar($this->datoscomprobante["condicionesDePago"]),0,0,'L');

			//Columna 2
			// $this->SetFont('DejaVu','',8);
			// $this->SetXY($x+60, $y+7);
			// $this->Cell(75,4,"Uso CFDI: ",0,0,'L');
			// $this->SetFont('DejaVu','',8);
			// $this->SetXY($x+75, $y+7);
			// $this->Cell(75,4,$this->limpiar($this->datoscomprobante["usoCFDI"]),0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+60, $y+7);
				$this->SetTextColor(0,104,160);
			$this->Cell(75,4,$this->limpiar("M&eacute;todo de Pago: "),0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+84, $y+7);
			$this->Cell(75,4,$this->limpiar($this->datoscomprobante["metodoDePago"]),0,0,'L');

			//Columna 3
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+140, $y+7);
				$this->SetTextColor(0,104,160);
			$this->Cell(75,4,"Moneda: ",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+152, $y+7);
			$this->Cell(75,4,$this->datoscomprobante["moneda"],0,0,'L');
			
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+140, $y+11);
			
			if($this->datoscomprobante["tipoCambio"] > 0){
				$this->Cell(75,4,"Tipo de Cambio: ",0,0,'L');
			}
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+163, $y+11);

			if($this->datoscomprobante["tipoCambio"] > 0){
				$this->Cell(75,4,$this->datoscomprobante["tipoCambio"],0,0,'L');
			}

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,$this->GetY()-5,190,14,2,'D','1234');

			$this->Ln();
			$this->SetY($this->GetY()+3);
	    }

	    // Función para mostrar el cuadro de datos del receptor
	    function _pageReceptor($x, $y) {
		    //Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
			$this->SetTextColor(255,255,255);

			$this->Ln();

			$this->SetFont('DejaVu','',9);
			$this->SetXY($x, $this->GetY()-2);
			$this->Cell(75,4,"    Receptor",0,0,'L');
			$this->SetTextColor(0,0,0);			

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+7);
			$this->Cell(80,4,$this->limpiar($this->receptor["nombre"]),0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+11);
			$this->Cell(80,4,$this->receptor["rfc"],0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+60, $y+11);
				$this->SetTextColor(0,104,160);
			$this->Cell(75,4,"Uso CFDI: ",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+75, $y+11);
			$this->Cell(75,4,$this->limpiar($this->datoscomprobante["usoCFDI"]),0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x+135, $y+11);
			$this->Cell(75,4,$this->limpiar($this->receptor["ref_client"]),0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+15);
			$this->Cell(80,4,$this->limpiar($this->receptor["direccion_pdf"]),0,0,'L');

			$this->SetFont('DejaVu','',8);
			$this->SetXY($x, $y+19);
			$this->Cell(80,4,$this->limpiar($this->receptor["direccion_pdf2"]),0,0,'L');

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect(10,$this->GetY()-13,190,18,2,'D','1234');

			$this->SetY($this->GetY()+1);
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

			$this->SetFont('DejaVu','',9);
			$this->SetXY($x, $this->GetY()+2);
			$this->Cell(75,4,"    Observaciones",0,0,'L');
			$this->Ln();

			$this->SetTextColor(0,0,0);
					
			$this->SetFont('DejaVu','',8);

			$this->MultiCell(190,4,$this->datoscomprobante["observaciones"],0, "L", false);
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

	    // Función para mostrar los totales del comprobante
	    function _pageTotal($x, $y,$porcentaje_des = 0){
		    global $db, $conf, $mysoc;

			$id = GETPOST('facid');
		    $object = new Facture($db);
		    $object->fetch($id);

		    if($conf->global->MAIN_MODULE_MULTICURRENCY == 1 && $this->data["moneda"] != "MXN") {
		    	$factura_subtotal = $object->multicurrency_total_ht;
		    	$factura_iva      = $object->multicurrency_total_tva;
		    	$factura_total    = $object->multicurrency_total_ttc;
			}else{
				$factura_subtotal = $object->total_ht;
				$factura_iva      = $object->total_tva;
				$factura_total    = $object->total_ttc;
			}

		    #Estilo de recuadros
		    $this->SetDrawColor(194,229,243);
			$this->SetFillColor(194,229,243);
			
			#Dibujo de recuadro
			$this->RoundedRect($this->GetX()+148,$this->GetY()+1,42,5,2,'DF','1234');
			$posXrecuadro = $this->GetX(); //Se almacena posición X para utulizarla en los demás recuadros
			#Subtotal
			$this->SetFont('DejaVu','',9);
			$this->SetXY($x+150, $y);
		    $this->Cell(15,7,"Subtotal",0,0,'L');
		    $this->SetFont('DejaVu','',9);
			$this->Cell(23,7, "$ ".number_format($factura_subtotal, 2),0,0,'R');

			#Dibujo de recuadro
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
			#Subtotal
			$this->SetFont('Arial','B',9);
			$this->SetXY($x+150, $y);
		    $this->Cell(15,7,"Subtotal",0,0,'L');
		    $this->SetFont('Arial','',9);
			$this->Cell(23,7, $this->datoscomprobante["label_moneda"]." ".number_format($factura_subtotal, 2),0,0,'R');

			#Dibujo de recuadro
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
			#Descuento
			$tot_desc = $factura_subtotal * ($porcentaje_des/100);
			$this->SetXY($x+150, $y+7);
			$this->SetFont('Arial','B',9);
			$this->Cell(15,7,"Descuento",0,0,'L');
			$this->SetFont('Arial','',9);
			$this->Cell(23,7, $this->datoscomprobante["label_moneda"]." ".number_format($tot_desc, 2),0,0,'R');

			#Dibujo de recuadro
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
			#Sub c/Desc
			$this->SetXY($x+150, $y+14);
			$this->SetFont('Arial','B',9);
			$this->Cell(15,7,"Sub c/desc",0,0,'L');
			$this->SetFont('Arial','',9);
			$this->Cell(23,7, $this->datoscomprobante["label_moneda"]." ".number_format(($factura_subtotal - $tot_desc), 2),0,0,'R');

			#Dibujo de recuadro
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
			#IVA Total
			$this->SetXY($x+150, $y+21);
			$this->SetFont('Arial','B',9);
			$this->Cell(15,7,"IVA",0,0,'L');
			$this->SetFont('Arial','',9);
			$this->Cell(23,7, $this->datoscomprobante["label_moneda"]." ".number_format($factura_iva, 2),0,0,'R');

			/*if ($object->remise > 0) {
				#Dibujo de recuadro para descuentos
				$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
				#Descuento
				$this->SetXY($x+150, $y+14);
				$this->SetFont('DejaVu','',9);
				$this->Cell(15,7,"Descuento",0,0,'L');
				$this->SetFont('DejaVu','',9);
				$this->Cell(23,7, "$ ".number_format($object->remise, 2),0,0,'R');
			}*/

			#Retenciones IVA (Inicio)
			$sql_ret = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_retenciones WHERE fk_facture = '.$id.' AND impuesto="IVA"';
			//echo $sql_ret."<br>";
			$resql = $db->query($sql_ret);

			if ($db->num_rows($resql) > 0) {

				#Dibujo de recuadro
				$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');

				$suma_ret_iva = 0;
				while ($obj = $db->fetch_object($resql)) {
					$suma_ret_iva = $suma_ret_iva + floatval($obj->importe);
				}

				if($suma_ret_iva < 0){
					$suma_ret_iva = abs($suma_ret_iva);
					$factura_total = $factura_total + $suma_ret_iva;
				}

				$this->SetXY($x+150, $this->GetY()+7);
				$this->SetFont('DejaVu','',9);
				$this->Cell(15,7,"Ret. IVA",0,0,'L');
				$this->SetFont('DejaVu','',9);
				$this->Cell(23,7, "$ ".number_format($suma_ret_iva, 2),0,0,'R');
			}
			#Retenciones IVA (Fin)
			
			#Retenciones ISR (Inicio)
			$sql_ret = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_retenciones WHERE fk_facture = '.$id.' AND impuesto="ISR"';
			$resql = $db->query($sql_ret);

			if ($db->num_rows($resql) > 0) {

				#Dibujo de recuadro
				$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');

				$suma_ret_isr = 0;
				while ($obj = $db->fetch_object($resql)) {
					$suma_ret_isr = $suma_ret_isr + floatval($obj->importe);
				}

				if($suma_ret_isr < 0){
					$suma_ret_isr = abs($suma_ret_isr);
					$factura_total = $factura_total + $suma_ret_isr;
				}

				$this->SetXY($x+150, $this->GetY()+7);
				$this->SetFont('DejaVu','',9);
				$this->Cell(15,7,"Ret. ISR",0,0,'L');
				$this->SetFont('DejaVu','',9);
				$this->Cell(23,7, "$ ".number_format($suma_ret_isr, 2),0,0,'R');
			}
			#Retenciones ISR (Fin)
			
			#Retenciones locales (Inicio)
			$sql_ret_loc = 'SELECT * FROM  '.MAIN_DB_PREFIX.'cfdimx_retenciones_locales WHERE fk_facture = ' . $id;
			$resql_loc = $db->query($sql_ret_loc);
				
			if ($db->num_rows($resql_loc) > 0){

				while ($obm = $db->fetch_object($resql_loc)){

					#Dibujo de recuadro
					$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');

					#Datos retención local
					$this->SetXY($x+150, $this->GetY()+7);
					$this->SetFont('DejaVu','',9);
					$this->Cell(15,7,"Ret. ".$obm->codigo,0,0,'L');
					$this->SetFont('DejaVu','',9);
					$this->Cell(23,7, "$ ".number_format($obm->importe, 2),0,0,'R');
				}
			}
			#Retenciones locales (Fin)
			
			#Bloque ISH (Incio) 
			//Productos Catalogo
			$sql = 'SHOW COLUMNS FROM '.MAIN_DB_PREFIX.'product_extrafields LIKE "prodcfish"';
			$resql = $db->query($sql);
			$num_rows_ish = $db->num_rows($resql);
			$totalish = 0;

			if($num_rows_ish > 0 ){

				if($conf->global->MAIN_MODULE_MULTICURRENCY) {
					$sql="SELECT a.fk_product,a.multicurrency_total_ht as total_ht,b.prodcfish,((b.prodcfish/100)*a.multicurrency_total_ht) as impish,c.ref,c.label FROM ".MAIN_DB_PREFIX."facturedet a, (SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, ".MAIN_DB_PREFIX."product c WHERE a.fk_facture=".$id." AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
				}else{
					$sql="SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label FROM ".MAIN_DB_PREFIX."facturedet a, (SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, ".MAIN_DB_PREFIX."product c WHERE a.fk_facture=".$id." AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
				}
				
				$resql = $db->query($sql);
				$num_rows_cat_prodcuto = $db->num_rows($resql);

				if($num_rows_cat_prodcuto > 0) {
					while($res = $db->fetch_object($resql)){
						$totalish = $totalish + $res->impish;
					}
				}
			}

			//Productos de Campo Libre
			$sql = '';
			$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facturedet_extrafields LIKE 'prodcfish'";
			$resql = $db->query($sql);
			$num_rows_ish_cl = $db->num_rows($resql);

			if($num_rows_ish_cl > 0){
				if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
		            $sql = "
		                    SELECT
		                        a.fk_product,
		                        a.multicurrency_total_ht AS total_ht,
		                        b.prodcfish,
		                        (
		                            (b.prodcfish / 100) * a.multicurrency_total_ht
		                        ) AS impish,
		                        a.label,
		                        a.description,
		                        b.prodcfish_label
		                    FROM
		                        ".MAIN_DB_PREFIX."facturedet a,
		                        (
		                        SELECT
		                            fk_object,
		                            prodcfish,
		                            prodcfish_label
		                        FROM
		                            ".MAIN_DB_PREFIX."facturedet_extrafields
		                        WHERE
		                            prodcfish != 0 AND prodcfish IS NOT NULL
		                    ) b
		                    WHERE
		                        a.fk_facture = ".$id." AND a.rowid = b.fk_object 
		                    ORDER BY
		                        a.rowid;
		                ";
		        }else {
		            $sql = "
		                   SELECT
		                        a.fk_product,
		                        a.total_ht,
		                        b.prodcfish,
		                        (
		                            (b.prodcfish / 100) * a.total_ht
		                        ) AS impish,
		                        a.label,
		                        a.description,
		                        b.prodcfish_label
		                    FROM
		                        ".MAIN_DB_PREFIX."facturedet a,
		                        (
		                        SELECT
		                            fk_object,
		                            prodcfish,
		                            prodcfish_label
		                        FROM
		                            ".MAIN_DB_PREFIX."facturedet_extrafields
		                        WHERE
		                            prodcfish != 0 AND prodcfish IS NOT NULL
		                    ) b
		                    WHERE
		                        a.fk_facture = ".$id." AND a.rowid = b.fk_object 
		                    ORDER BY
		                        a.rowid
		                ";
		        }
				$ass=$db->query($sql);
				$asf=$db->num_rows($ass);
				if($asf>0){
					while($asd=$db->fetch_object($ass)){
						$totalish = $totalish + $asd->impish;
					}
				}
			}

			if($totalish > 0){
				#Dibujo de recuadro
				$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
				$this->SetXY($x+150, $this->GetY()+7);
				$this->SetFont('DejaVu','',9);
				$this->Cell(15,7,"ISH",0,0,'L');
				$this->SetFont('DejaVu','',9);
				$this->Cell(23,7, "$ ".number_format($totalish, 2),0,0,'R');
			}
			#Bloque ISH (Fin)

			#Dibujo de recuadro de Total
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
			#Total
			$this->SetXY($x+150, $this->GetY()+7);
			$this->SetFont('DejaVu','',9);
			$this->Cell(15,7,"Total",0,0,'L');
			$this->SetFont('DejaVu','',9);
			$this->Cell(23,7, "$ ".number_format($factura_total, 2),0,0,'R');
		}

		// Monto de la factura en letra
	   	function _pageNum2Letra($x, $y){
			global $langs, $db, $conf;
			
			$id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

			$object = new Facture($db);
		    $object->fetch($id);

			// se modifica para utilizar el traductor de dolibarr
			// $moneda = $this->data['moneda'];
			$moneda = $this->datoscomprobante['moneda'];
			$aray_tipoDivisa = explode(' ', $langs->trans('Currency'. $moneda));

			if($aray_tipoDivisa[1] == 'USA'){
				if(strlen(floor($object->multicurrency_total_ht)) > 1){
					$tipoDivisa = strtoupper("Dolares ".$aray_tipoDivisa[1]);
				}else{
					$tipoDivisa = strtoupper("Dolares ".$aray_tipoDivisa[1]);
				}
			}else{
				if(strlen(floor($object->total_ttc)) > 1){
					$tipoDivisa = strtoupper($aray_tipoDivisa[0]." ".$aray_tipoDivisa[1]);
				}else{
					$tipoDivisa = strtoupper($aray_tipoDivisa[0]." ".$aray_tipoDivisa[1]);
				}
			}

			if($conf->global->MAIN_MODULE_MULTICURRENCY == 1 && $this->data["moneda"] != "MXN") {
				$factura_total = $object->multicurrency_total_ttc;
			}else{
				$factura_total = $object->total_ttc;
			}
			$factura_total = $object->total_ttc;

			if($factura_total < 0){
				$factura_total = abs($factura_total);
			}

			if($tipoDivisa=='USA'){$tipoDivisa='Dolares';}
			if($tipoDivisa=='USAS'){$tipoDivisa='Dolares';}

			$letras=num2letras($factura_total,0,0).' '.$tipoDivisa;
			$letras_len = strlen($letras);
			$letras_substr = substr($letras, $letras_len-2,$letras_len);
			if($letras_substr == "SS") $letras = substr($letras, 0, $letras_len-1);

			$ultimo = substr(strrchr(number_format($factura_total,2), "."), 1 ); //recupero lo que este despues del decimal
			$letras = strtoupper($letras)." ".$ultimo."/100 ".($moneda == "MXN"? "MN":"ME");

			$contar_letras=strlen($letras);
			$fuente_letras = ($contar_letras>=60) ? 7 : 8;
			
			$this->SetFont('DejaVu','',$fuente_letras);
			$this->SetXY(10,$this->GetY());
			if($moneda != 'XXX'){
				$this->Cell(10,7,$letras,0,0,'L');
			}
		}

		// Código QR y cadena
	    function _pageQRCode(){
			global $conf, $db;
			
			$id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

			$object = new Facture($db);
		    $object->fetch($id);
		    
			$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id='.$this->data["uuid"].'&re='.$this->data["rfc_emisor"].'&rr='.$this->data["rfc_receptor"].'&tt='.$object->total_ttc.'&fe='.substr($this->data["selloCFD"],-8);
			QRcode::png($data_cbb,$conf->facture->dir_output."/".$object->ref."/".$this->data["uuid"].".png");
			$this->CheckPageBreak(20);

			if((int)$this->GetY() > 220){
				$this->AddPage();
			}
			
			$this->Image($conf->facture->dir_output."/".$object->ref."/".$this->data["uuid"].".png",162, $this->GetY()+9,42);

			if((int)$this->GetX() == 20){
				$nueva_cc_x = $this->GetX()-10;
			}else{
				$nueva_cc_x = $this->GetX();
			}

			$this->SetFont('DejaVu','',6);
			// $this->SetXY($this->GetX()-10, $this->GetY()+13);
			$this->SetXY($nueva_cc_x, $this->GetY()+13); //revisar
			$this->MultiCell(150,4,"\n".$this->data["selloCFD"],0, "L", false);
			$this->SetY($this->GetY()-16);

			$this->SetFont('DejaVu','',6);
				$this->SetTextColor(0,104,160);
			$this->Cell(20,4,"SELLO DIGITAL DEL EMISOR",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetY($this->GetY()+16);

			$this->SetFont('DejaVu','',6);
			$this->MultiCell(150,4,"\n".$this->data["selloSAT"],0, "L", false);
			$this->SetY($this->GetY()-16);
			$this->SetFont('DejaVu','',6);
				$this->SetTextColor(0,104,160);
			$this->Cell(20,4,"SELLO DIGITAL DEL SAT",0,0,'L');
				$this->SetTextColor(0,0,0);
			$this->SetY($this->GetY()+16);

			$this->SetFont('DejaVu','',6);
			$this->MultiCell(150,4,"\n".$this->data["coccds"],0, "L", false);
			$this->SetY($this->GetY()-20);
			$this->SetFont('DejaVu','',6);
				$this->SetTextColor(0,104,160);
			$this->Cell(20,4,"CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT",0,0,'L');
				$this->SetTextColor(0,0,0);
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
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_clave_transporte WHERE code = '".$codigo."'";
		    		break;
		    	case 2:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_pais WHERE code = '".$codigo."'";
		    		break;
		    	case 3:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_estacion WHERE code = '".$codigo."'";
		    		break;
		    	case 5:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_clave_unidad_peso WHERE code = '".$codigo."'";
		    		break;
		    	case 6:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_clave_prodserv_cp WHERE code = '".$codigo."'";
		    		break;
		    	case 7:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_clave_prod_stcc WHERE code = '".$codigo."'";
		    		break;
		    	case 8:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_unidad_medida WHERE code = '".$codigo."'";
		    		break;
		    	case 9:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_material_peligroso WHERE code = '".$codigo."'";
		    		break;
		    	case 11:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_f_arancelaria WHERE code = '".$codigo."'";
		    		break;
		    	case 13:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_permiso WHERE code = '".$codigo."'";
		    		break;
		    	case 14:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_config_autotransporte WHERE code = '".$codigo."'";
		    		break;
		    	case 15:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_subtipo_rem WHERE code = '".$codigo."'";
		    		break;
		    	case 16:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_config_maritima WHERE code = '".$codigo."'";
		    		break;
		    	case 19:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_contenedor_mat WHERE code = '".$codigo."'";
		    		break;
		    	case 20:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_codtrans_aereo WHERE code = '".$codigo."'";
		    		break;
		    	case 21:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_servicio WHERE code = '".$codigo."'";
		    		break;
		    	case 22:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_trafico WHERE code = '".$codigo."'";
		    		break;
		    	case 23:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_derecho_paso WHERE code = '".$codigo."'";
		    		break;
		    	case 24:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_carro WHERE code = '".$codigo."'";
		    		break;
		    	case 25:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_contenedor_ferr WHERE code = '".$codigo."'";
		    		break;


		    	case 900:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."product";
		    		break;
		    	case 901:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid = 0";
		    		break;
		    	case 902:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid IN(".$tipo_sql.")";
		    		break;

		    	case 1000:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_figura_transporte WHERE code = '".$codigo."'";
		    		break;

		    	case 1001:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_figura_transporte WHERE active = 1";
		    		break;

		    	case 4001:
		    		$sql = "SELECT code, libelle AS label FROM ".MAIN_DB_PREFIX."c_forme_juridique WHERE code = '".$codigo."'";
		    		break;
		    	case 4002:
		    		$sql = "SELECT code, libelle AS label, accountancy_code FROM ".MAIN_DB_PREFIX."c_paiement WHERE code = '".$codigo."'";
		    		// print $sql;
		    		break;
		    	case 4003:
		    		$sql = "SELECT code, libelle AS label FROM ".MAIN_DB_PREFIX."c_payment_term WHERE code = '".$codigo."'";
		    		break;
		    	case 4004:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_uso_cfdi WHERE code = '".$codigo."'";
		    		break;
		    	case 4005:
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
		        		$etiqueta = $obj_catalogo->label;

		        		switch ($tipo_catalogo) {
		        			case 4002:
		        				$etiqueta = ($langs->trans("PaymentTypeShort".$obj_catalogo->code)!=("PaymentTypeShort".$obj_catalogo->code)?$langs->trans("PaymentTypeShort".$obj_catalogo->code):($obj_catalogo->label!='-'?$obj_catalogo->label:''));
		        				// $obj_catalogo->code = $obj_catalogo->accountancy_code;
		        				break;
		        			case 4003:
		        				$etiqueta = ($langs->trans("PaymentConditionShort".$obj_catalogo->code)!=("PaymentConditionShort".$obj_catalogo->code)?$langs->trans("PaymentConditionShort".$obj_catalogo->code):($obj_catalogo->label!='-'?$obj_catalogo->label:''));
		        				$etiqueta = $etiqueta;
		        				break;
		        		}

		            	$valor    = $obj_catalogo->code;
		            	if($tipo_catalogo != 4003){
		            		if($tipo_catalogo == 4002){
		            			$etiqueta = $obj_catalogo->accountancy_code." - ".$etiqueta;
		            		}else{
		            			$etiqueta = $obj_catalogo->code." - ".$etiqueta;
		            		}
		            	}else{
		            		$etiqueta = $etiqueta;
		            	}		            	

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

		//Page Carta Porte
		function _pageCartaPorte(){
			global $db, $conf;

			$facid = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');        
	        
	        $entidad = $conf->entity;

			$sql_head_cartaporte = '';
			$sql_head_cartaporte = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte WHERE facid = ".$facid." AND entity =".$entidad;
			$res_head_cartaporte = $db->query($sql_head_cartaporte);
			$num_head_cartaporte = $db->num_rows($res_head_cartaporte);

			if($num_head_cartaporte > 0){
				$x = $this->GetX();
				$obj_head_cartaporte = $db->fetch_object($res_head_cartaporte);

				$entrada_salida           = "";
				$via_entrada_salida       = "";
				$transporte_internacional = "";
				$tipo_transporte          = "";
				$label_transporte         = "";

				$entrada_salida            = $obj_head_cartaporte->entrada_salida;
				$via_entrada_salida        = $obj_head_cartaporte->via_entrada_salida;
				$transporte_internacional  = $obj_head_cartaporte->transporte_internacional;
				$label_transporte_int      = "S&iacute;";
				$tipo_transporte           = $obj_head_cartaporte->tipo_transporte;
				$label_transporte          = $this->_obtener_catalogo($tipo_transporte,1);
				$pais_origen_destino       = $obj_head_cartaporte->pais_origen_destino;
				$label_pais_origen_destino = $this->_obtener_catalogo($pais_origen_destino,1);
				$tot_distancia_recorrida  = $obj_head_cartaporte->tot_distancia_recorrida;

				if($obj_head_cartaporte->transporte_internacional == 2){
					$label_transporte_int = "No";
					$entrada_salida       = "";
					$via_entrada_salida   = "";
				}				

				$this->AddPage();
				$this->AliasNbPages();
				$this->SetY($this->GetY()+4);

				//Inicia Head Carta Porte
				//Dibujar recuadro
				$this->SetDrawColor(16,120,179);
				$this->SetFillColor(16,120,179);
				$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
				$this->SetTextColor(255,255,255);

				$this->Ln();
				
				$this->SetFont('DejaVu','',9);
				$this->SetXY($this->GetX(), $this->GetY()-2);
				$this->Cell(75,4,"    Carta Porte",0,0,'L');
				$this->SetTextColor(0,0,0);

				$y = $this->GetY()+4;
		        $this->SetFont('DejaVu','',8);
			    $this->SetXY($x, $y+1);
				$this->Cell(80,4,"Entrada/Salida: ",0,0,'L');
				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+22, $y+1);
				$this->Cell(80,4,$entrada_salida,0,0,'L');

				$this->SetFont('DejaVu','',8);
				$this->SetXY($x, $y+5);
				$this->Cell(80,4,"Via de Entrada/Salida: ",0,0,'L');
				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+30, $y+5);
				$this->Cell(80,4,$this->limpiar($label_transporte),0,0,'L');

				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+50, $y+1);
				$this->Cell(80,4,"Transporte Internacional: ",0,0,'L');
				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+85, $y+1);
				$this->Cell(80,4,$this->limpiar($label_transporte_int),0,0,'L');

				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+105, $y+1);
				$this->Cell(80,4,"Distancia Recorrida: ",0,0,'L');
				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+133, $y+1);
				$this->Cell(80,4,$tot_distancia_recorrida,0,0,'L');

				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+105, $y+5);
				$this->Cell(80,4,$this->limpiar("Pa&iacute;s Origen/Destino: "),0,0,'L');
				$this->SetFont('DejaVu','',8);
				$this->SetXY($x+134, $y+5);
				$this->Cell(80,4,$label_pais_origen_destino,0,0,'L');
				
				//Dibujar recuadro
				$this->SetDrawColor(16,120,179);
				$this->SetFillColor(194,229,243);
				$this->RoundedRect($x,$y,190,10,2,'D','1234');
				//Termina Head Carta Porte
			    
				//Inicia Informacion Ubicaciones
				$sql_ubicaciones = '';
				$sql_ubicaciones = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_direcciones WHERE facid = ".$facid." AND entity =".$entidad;
				$res_ubicaciones = $db->query($sql_ubicaciones);
				$num_ubicaciones = $db->num_rows($res_ubicaciones);

				if($num_ubicaciones > 0){
					$this->Ln();
					$x = $this->GetX();
					$y = $this->GetY()+4;
					//Dibujar recuadro
					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x,$y+1,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x,$y+2);
					$this->Cell(75,4,"    Ubicaciones",0,0,'L');
					$this->SetTextColor(0,0,0);
				
					$y = $this->GetY()+4;

					while ($obj_ubicacion = $db->fetch_object($res_ubicaciones)) {
						// $y = $this->GetY()+4;

						$y_tmp = (int)$y + 38;

						if($y_tmp > 261){
							$this->AddPage();
							$this->SetY(48);

							$x = $this->GetX();
							$y = $this->GetY()+4;
							//Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x,$y+1,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x,$y+2);
							$this->Cell(75,4,"    Ubicaciones",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y = $this->GetY() + 4;
						}

						//Fila 1
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+1);
						$this->Cell(80,4,"Dist. Recorrida: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+22, $y+1);
						$this->Cell(80,4,$obj_ubicacion->distancia_recorrida,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$label_tipo_estacion = $this->_obtener_catalogo($obj_ubicacion->tipo_estacion,3);
						$this->SetXY($x+40, $y+1);
						$this->Cell(80,4,"Tipo Estación: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+60, $y+1);
						$this->Cell(80,4,$label_tipo_estacion,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+85, $y+1);
						$this->Cell(80,4,"Fecha y Hora: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+104, $y+1);
						$this->Cell(80,4,$obj_ubicacion->fechahora,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+138, $y+1);
						$this->Cell(80,4,"IDUbicacion: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+156, $y+1);
						$this->Cell(80,4,$this->limpiar($obj_ubicacion->id_ubicacion),0,0,'L');

						//Fila 2
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+5);
						$this->Cell(80,4,"Tipo Ubicación: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+22, $y+5);
						$this->Cell(80,4,$obj_ubicacion->tipo_ubicacion,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+40, $y+5);
						$this->Cell(80,4,"Navegación Trafico: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+68, $y+5);
						$this->Cell(80,4,$obj_ubicacion->navegacion_trafico,0,0,'L');

						//Fila 3
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+9);
						$this->Cell(80,4,"Num. Estación: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+21, $y+9);
						$this->Cell(80,4,$obj_ubicacion->num_estacion,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+40, $y+9);
						$this->Cell(80,4,"Nombre Estación: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+65, $y+9);
						$this->Cell(80,4,$this->limpiar($obj_ubicacion->nom_estacion),0,0,'L');

						//Fila 4
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+13);
						$this->Cell(80,4,"Nombre: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+12, $y+13);
						$this->Cell(80,4,$obj_ubicacion->nombre,0,0,'L');

						//Fila 5
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+17);
						$this->Cell(80,4,"RFC: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+7, $y+17);
						$this->Cell(80,4,$obj_ubicacion->rfc,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+40, $y+17);
						$this->Cell(80,4,"NumRegIdTrib: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+61, $y+17);
						$this->Cell(80,4,$obj_ubicacion->numregidtrib,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+138, $y+17);
						$this->Cell(80,4,"Residencia Fiscal: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+164, $y+17);
						$label_residencia_fiscal = $this->_obtener_catalogo($obj_ubicacion->residencia_fiscal,2);
						$this->Cell(80,4,$obj_ubicacion->residencia_fiscal,0,0,'L');

						//Fila 6
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+21);
						$this->Cell(80,4,"Calle: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+8, $y+21);
						$this->Cell(80,4,$this->limpiar($obj_ubicacion->calle),0,0,'L');
						
						//Fila 7
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+25);
						$this->Cell(80,4,"Num. Int: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+13, $y+25);
						$this->Cell(80,4,$this->limpiar($obj_ubicacion->num_int),0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+40, $y+25);
						$this->Cell(80,4,"Num. Ext: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+54, $y+25);
						$this->Cell(80,4,$this->limpiar($obj_ubicacion->num_ext),0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+85, $y+25);
						$this->Cell(80,4,"Colonia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+97, $y+25);
						$this->Cell(80,4,$obj_ubicacion->colonia,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+138, $y+25);
						$this->Cell(80,4,"Localidad: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+153, $y+25);
						$this->Cell(80,4,$obj_ubicacion->localidad,0,0,'L');

						//Fila 8
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+29);
						$this->Cell(80,4,"C.P.: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+7, $y+29);
						$this->Cell(80,4,$obj_ubicacion->cp,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+40, $y+29);
						$this->Cell(80,4,"Referencia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+57, $y+29);
						$this->Cell(80,4,$obj_ubicacion->referencia,0,0,'L');

						//Fila 9
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x, $y+33);
						$this->Cell(80,4,"Municipio: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x+15, $y+33);
						$this->Cell(80,4,$obj_ubicacion->municipio,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+40, $y+33);
						$this->Cell(80,4,"Estado: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+51, $y+33);
						$this->Cell(80,4,$obj_ubicacion->estado,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+85, $y+33);
						$this->Cell(80,4,"País: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x+92, $y+33);
						$this->Cell(80,4,$obj_ubicacion->pais,0,0,'L');

						$num_y   = 38;
						$tamanio = 38;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x,$y,190,$tamanio,2,'D','1234');
						$y = $y + $num_y;
					}
					$this->SetX($this->GetX()+3);
				}

				//Termina Informacion Ubicaciones

				//Inicia head Mercancia
				$sql_head_mercancia = '';
				$sql_head_mercancia = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_mercancia WHERE facid = ".$facid." AND entity =".$entidad;
				$res_head_mercancia = $db->query($sql_head_mercancia);
				$num_head_mercancia = $db->num_rows($res_head_mercancia);			

				if($num_head_mercancia > 0){
					$obj_head_mercancia   = $db->fetch_object($res_head_mercancia);
					$peso_bruto_total     = $obj_head_mercancia->peso_bruto_total;
					$unidad_peso          = $obj_head_mercancia->unidad_peso;
					$peso_neto_total      = $obj_head_mercancia->peso_neto_total;
					$num_total_mercancias = $obj_head_mercancia->num_total_mercancias;
					$cargo_por_tasacion   = $obj_head_mercancia->cargo_por_tasacion;

					$x1 = $x;
					$y1 = $this->GetY() + 9;

					$y_tmp = (int)$y1 + 10;

					if($y_tmp > 260){
						$this->AddPage();
						$this->SetY(48);

						$x1 = $this->GetX();

						$y1 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x1,$y1,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x1, $y1+1);
					$this->Cell(75,4,"    Mercancias",0,0,'L');
					$this->SetTextColor(0,0,0);

			        $this->SetFont('DejaVu','',8);
				    $this->SetXY($x1, $y1+6);
					$this->Cell(80,4,"Peso Bruto Total: ",0,0,'L');
					$this->SetFont('DejaVu','',8);
				    $this->SetXY($x1+24, $y1+6);
					$this->Cell(80,4,$peso_bruto_total,0,0,'L');
					
					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+50, $y1+6);
					$this->Cell(80,4,"Número Total Mercancía: ",0,0,'L');
					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+85, $y1+6);
					$this->Cell(80,4,$num_total_mercancias,0,0,'L');
				    
					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+105, $y1+6);
					$this->Cell(80,4,"Cargo por Tasacion: ",0,0,'L');
					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+133, $y1+6);
					$this->Cell(80,4,$cargo_por_tasacion,0,0,'L');

					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1, $y1+10);
					$this->Cell(80,4,"Peso Neto Total: ",0,0,'L');
					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+23, $y1+10);
					$this->Cell(80,4,$peso_neto_total,0,0,'L');

					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+50, $y1+10);
					$this->Cell(80,4,"Unidad Peso: ",0,0,'L');
					$this->SetFont('DejaVu','',8);
					$this->SetXY($x1+69, $y1+10);
					$label_unidadpeso = $this->_obtener_catalogo($unidad_peso,5);
					$this->Cell(80,4,$label_unidadpeso,0,0,'L');					

					//Dibujar recuadro
					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(194,229,243);
					$this->RoundedRect($x1,$y1+5,190,10,2,'D','1234');
				}
				//Termina head Mercancia

				//Inicia Mercancias
				$sql_mercancias = '';
				$sql_mercancias = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_mercancias WHERE facid = ".$facid." AND entity =".$entidad;
				$res_mercancias = $db->query($sql_mercancias);
				$num_mercancias = $db->num_rows($res_mercancias);
				
				$x2 = $x1;
				$y2 = $y1 + 19;
				if($num_mercancias > 0){
					$y_tmp = (int)$y2 + 9;

					if($y_tmp > 260){
						$this->AddPage();
						$this->SetY(48);

						$x2 = $this->GetX();

						$y2 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x2,$y2,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x2, $y2+1);
					$this->Cell(75,4,"    Mercancia",0,0,'L');
					$this->SetTextColor(0,0,0);
				
					$y2 = $this->GetY() + 4;
					while($obj_mercancia = $db->fetch_object($res_mercancias)){
						$y_tmp = (int)$y2 + 38;

						if($y_tmp > 271){
							$this->AddPage();
							$this->SetY(48);

							$x2 = $this->GetX();
							$y2 = $this->GetY()+6;
							// //Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x2,$y2,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln(5);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x2, $y2+1);
							$this->Cell(75,4,"    Mercancias",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y2 = $this->GetY() + 4;
						}

						$producto = new Product($db);
					    $producto->fetch($obj_mercancia->fk_product);
					    
					    $segunda_columna = 45;
					    $tercera_columna = 99;
					    $cuarta_columna  = 155;

				        //Fila 1
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x2, $y2+1);
						$this->Cell(80,4,"Producto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2+14, $y2+1);
						$this->Cell(80,4,$this->limpiar($producto->ref),0,0,'L');

						//Fila 2
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2, $y2+5);
						$this->Cell(80,4,"Descripción: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2+18, $y2+5);
						$this->Cell(80,4,$this->limpiar($obj_mercancia->descripcion),0,0,'L');

						//Fila 3
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2, $y2+9);
						$this->Cell(80,4,"Cantidad: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2+14, $y2+9);
						$this->Cell(80,4,$obj_mercancia->cantidad,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna, $y2+9);
						$this->Cell(80,4,"BienesTransp: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna+20, $y2+9);
						$label_bienestransp = $this->_obtener_catalogo($obj_mercancia->bienestransp,6);
						$this->Cell(80,4,$label_bienestransp,0,0,'L');												

						//Fila 4
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2, $y2+13);
						$this->Cell(80,4,"Embalaje: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+14, $y2+13);
						$this->Cell(80,4,$producto->embalaje,0,0,'L');
					    
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna, $y2+13);
						$this->Cell(80,4,"Desc. Embalaje: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna+22, $y2+13);
						$this->Cell(80,4,$obj_mercancia->desc_embalaje,0,0,'L');

						//Fila 5
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2, $y2+17);
						$this->Cell(80,4,"Clave STCC: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2+18, $y2+17);
					    $label_clavestcc = $this->_obtener_catalogo($obj_mercancia->clavestcc,7);
						$this->Cell(80,4,$this->limpiar($label_clavestcc),0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna+54, $y2+17);
						$this->Cell(80,4,"Peso En Kg: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna+71, $y2+17);
						$this->Cell(80,4,$obj_mercancia->peso_en_kg,0,0,'L');

						//Fila 6
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2, $y2+21);
						$this->Cell(80,4,"Unidad: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2+11, $y2+21);
					    $label_claveunidad = $this->_obtener_catalogo($obj_mercancia->claveunidad,8);
						$this->Cell(80,4,$label_claveunidad,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna, $y2+21);
						$this->Cell(80,4,"Valor Mercancia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna+23, $y2+21);
						$this->Cell(80,4,$obj_mercancia->valor_mercancia,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$tercera_columna, $y2+21);
						$this->Cell(80,4,"Dimensiones: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$tercera_columna+19, $y2+21);
						$this->Cell(80,4,$obj_mercancia->dimensiones,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$cuarta_columna, $y2+21);
						$this->Cell(80,4,"Moneda: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$cuarta_columna+13, $y2+21);
						$this->Cell(80,4,$obj_mercancia->moneda,0,0,'L');

						//Fila 7
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2, $y2+25);
						$this->Cell(80,4,"Material Peligroso: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x2+26, $y2+25);
					    $label_materialpeligroso = ($obj_mercancia->material_peligroso == 1 ? "S&iacute;" : "No");
						$this->Cell(80,4,$this->limpiar($label_materialpeligroso),0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna, $y2+25);
						$this->Cell(80,4,"Cve Material Peligroso: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$segunda_columna+32, $y2+25);
						$this->Cell(80,4,$obj_mercancia->cve_material_peligroso,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$tercera_columna, $y2+25);
						$this->Cell(80,4,"Fracción Arancelaria: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+$tercera_columna+30, $y2+25);
						$this->Cell(80,4,$obj_mercancia->fraccion_arancelaria,0,0,'L');

						//Fila 8
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2, $y2+29);
						$this->Cell(80,4,"UUID Comercio Ext: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x2+28, $y2+29);
						$this->Cell(80,4,$obj_mercancia->uuidcomercioext,0,0,'L');

						$num_y   = 33;
						$tamanio = 33;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x2,$y2,190,$tamanio,2,'D','1234');
						$y2 = $y2 + $num_y;
					}
				}
				//Termina Mercancias

				//Inicia Cantidad Transporta
				$sql_mercancia_transporta = '';
				$sql_mercancia_transporta = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_cantidad_transporta WHERE facid = ".$facid." AND entity =".$entidad;
				$res_mercancia_transporta = $db->query($sql_mercancia_transporta);
				$num_mercancia_transporta = $db->num_rows($res_mercancia_transporta);

				$x3 = $x2;
				$y3 = $this->GetY() + 9;
				if($num_mercancia_transporta > 0){
					$y_tmp = (int)$y3 + 9;

					if($y_tmp > 265){
						$this->AddPage();
						$this->SetY(48);

						$x3 = $this->GetX();
						$y3 = $this->GetY()+6;

						$y3 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x3,$y3,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x3, $y3+1);
					$this->Cell(75,4,"    Cantidad Transporta",0,0,'L');
					$this->SetTextColor(0,0,0);
				
					$y3 = $this->GetY()+4;
					while($obj_mercancia_transporta = $db->fetch_object($res_mercancia_transporta)){

						$y_tmp = (int)$y3 + 10;

						if($y_tmp > 265){
							$this->AddPage();
							$this->SetY(48);

							$x3 = $this->GetX();
							$y3 = $this->GetY()+6;
							// //Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x3,$y3,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln(5);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x3, $y3+1);
							$this->Cell(75,4,"    Cantidad Transporta",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y3 = $this->GetY() + 4;
						}

						$producto = new Product($db);
					    $producto->fetch($obj_mercancia_transporta->fk_product);

					    $segunda_columna = 90;

				        //Fila 1
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x3, $y3+1);
						$this->Cell(80,4,"Producto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x3+14, $y3+1);
						$this->Cell(80,4,$this->limpiar($producto->ref),0,0,'L');

						//Fila 2
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3, $y3+5);
						$this->Cell(80,4,"ID Origen: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+14, $y3+5);
						$this->Cell(80,4,$obj_mercancia_transporta->id_origen,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+40, $y3+5);
						$this->Cell(80,4,"ID Destino: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+55, $y3+5);
						$this->Cell(80,4,$obj_mercancia_transporta->id_destino,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+80, $y3+5);
						$this->Cell(80,4,"Cantidad: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+93, $y3+5);
						$this->Cell(80,4,$obj_mercancia_transporta->cantidad,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+115, $y3+5);
						$this->Cell(80,4,"Cves Transporte: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x3+139, $y3+5);
						$obj_mercancia_transporta->cvestransporte = "04";
						$label_mert_cvestransporte = $this->_obtener_catalogo($obj_mercancia_transporta->cvestransporte, 1);
						$this->Cell(80,4,$this->limpiar($label_mert_cvestransporte),0,0,'L');

						$num_y   = 10;
						$tamanio = 10;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x3,$y3,190,$tamanio,2,'D','1234');
						$y3 = $y3 + $num_y;
					}
				}
				//Termina Cantidad Transporta

				//Inicia Detalle Mercancia
				$sql_detalle_mercancia = '';
				$sql_detalle_mercancia = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_detalle_mercancia WHERE facid = ".$facid." AND entity =".$entidad;
				$res_detalle_mercancia = $db->query($sql_detalle_mercancia);
				$num_detalle_mercancia = $db->num_rows($res_detalle_mercancia);
				
				$x4 = $x3;
				$y4 = $this->GetY() + 9;
				if($num_detalle_mercancia > 0){
					$y_tmp = (int)$y4 + 9;

					if($y_tmp > 265){
						$this->AddPage();
						$this->SetY(48);

						$x4 = $this->GetX();
						$y4 = $this->GetY()+6;
						//Dibujar recuadro
						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(16,120,179);
						$this->RoundedRect($x4,$y4,190,5,3,'DF','1234');
						$this->SetTextColor(255,255,255);

						$this->Ln(5);

						$this->SetFont('DejaVu','',9);
						$this->SetXY($x4, $y4+1);
						$this->Cell(75,4,"    Detalle Mercancia",0,0,'L');
						$this->SetTextColor(0,0,0);

						$y4 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x4,$y4,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x4, $y4+1);
					$this->Cell(75,4,"    Detalle Mercancia",0,0,'L');
					$this->SetTextColor(0,0,0);
				
					$y4 = $this->GetY()+4;
					while($obj_detalle_mercancia = $db->fetch_object($res_detalle_mercancia)){
						$y_tmp = (int)$y4 + 14;
						
						if($y_tmp > 273){
							$this->AddPage();
							$this->SetY(48);

							$x4 = $this->GetX();
							$y4 = $this->GetY()+6;
							// //Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x4,$y4,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln(5);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x4, $y4+1);
							$this->Cell(75,4,"    Detalle Mercancia",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y4 = $this->GetY() + 4;
						}
						$producto = new Product($db);
					    $producto->fetch($obj_detalle_mercancia->fk_product);

					    $segunda_columna = 90;

				        //Fila 1
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x4, $y4+1);
						$this->Cell(80,4,"Producto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x4+14, $y4+1);
						$this->Cell(80,4,$this->limpiar($producto->ref),0,0,'L');

						//Fila 2
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4, $y4+5);
						$this->Cell(80,4,"Unidad Peso: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+19, $y4+5);
						$label_unidad_peso = $this->_obtener_catalogo($obj_detalle_mercancia->unidad_peso, 5);
						$this->Cell(80,4,$this->limpiar($label_unidad_peso),0,0,'L');

						//Fila 3
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4, $y4+9);
						$this->Cell(80,4,"Peso Neto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+16, $y4+9);
						$this->Cell(80,4,$obj_detalle_mercancia->peso_neto,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+40, $y4+9);
						$this->Cell(80,4,"Peso Bruto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+57, $y4+9);
						$this->Cell(80,4,$obj_detalle_mercancia->peso_bruto,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+80, $y4+9);
						$this->Cell(80,4,"Peso Tara: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+96, $y4+9);
						$this->Cell(80,4,$obj_detalle_mercancia->peso_tara,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+130, $y4+9);
						$this->Cell(80,4,"Número de piezas: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x4+156, $y4+9);
						$this->Cell(80,4,$obj_detalle_mercancia->num_piezas,0,0,'L');

						$num_y   = 14;
						$tamanio = 14;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x3,$y4,190,$tamanio,2,'D','1234');
						$y4 = $y4 + $num_y;
					}
				}
				//Termina Detalle Mercancia

				//Inicia Guias Identificacion
				$sql_guias_identificacion = '';
				$sql_guias_identificacion = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_guias_identificacion WHERE facid = ".$facid." AND entity =".$entidad;
				$res_guias_identificacion = $db->query($sql_guias_identificacion);
				$num_guias_identificacion = $db->num_rows($res_guias_identificacion);
				
				$x99 = $x4;
				$y99 = $this->GetY() + 9;
				if($num_guias_identificacion > 0){
					$y_tmp = (int)$y99 + 9;

					if($y_tmp > 265){
						$this->AddPage();
						$this->SetY(48);

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(16,120,179);
						$this->RoundedRect($x99,$y99,190,5,3,'DF','1234');
						$this->SetTextColor(255,255,255);

						$this->Ln(5);

						$this->SetFont('DejaVu','',9);
						$this->SetXY($x99, $y99+1);
						$this->Cell(75,4,"Guías de identificación",0,0,'L');
						$this->SetTextColor(0,0,0);

						$x99 = $this->GetX();
						$y99 = $this->GetY()+6;

						$y99 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x99,$y99,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x99, $y99+1);
					$this->Cell(75,4,"Guías de identifiación",0,0,'L');
					$this->SetTextColor(0,0,0);
				
					$y99 = $this->GetY()+4;
					while($obj_guia_identificacion = $db->fetch_object($res_guias_identificacion)){
						$y_tmp = (int)$y99 + 14;
						
						if($y_tmp > 273){
							$this->AddPage();
							$this->SetY(48);

							$x99 = $this->GetX();
							$y99 = $this->GetY()+6;
							// //Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x99,$y99,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln(5);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x99, $y99+1);
							$this->Cell(75,4,"Guías de identificación",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y99 = $this->GetY() + 4;
						}

						$producto = new Product($db);
					    $producto->fetch($obj_guia_identificacion->fk_product);

					    $segunda_columna = 65;

				        //Fila 1
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x99, $y99+1);
						$this->Cell(80,4,"Producto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x99+14, $y99+1);
						$this->Cell(80,4,$this->limpiar($producto->ref),0,0,'L');

						//Fila 2
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x99, $y99+5);
						$this->Cell(80,4,"Número: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x99+13, $y99+5);
						$this->Cell(80,4,$obj_guia_identificacion->numero,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x99+$segunda_columna, $y99+5);
						$this->Cell(80,4,"Peso: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x99+$segunda_columna+8, $y99+5);
						$this->Cell(80,4,$obj_guia_identificacion->peso,0,0,'L');

						//Fila 3
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x99, $y99+9);
						$this->Cell(80,4,"Descripción: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x99+17, $y99+9);
						$this->Cell(80,4,$obj_guia_identificacion->descripcion,0,0,'L');

						$num_y   = 14;
						$tamanio = 14;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x99,$y99,190,$tamanio,2,'D','1234');
						$y99 = $y99 + $num_y;
					}
				}
				//Termina Guias Identificacion

				//Inicia Pedimentos
				$sql_pedimentos = '';
				$sql_pedimentos = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_pedimentos WHERE facid = ".$facid." AND entity =".$entidad;
				$res_pedimentos = $db->query($sql_pedimentos);
				$num_pedimentos = $db->num_rows($res_pedimentos);
				
				$x999 = $x99;
				$y999 = $this->GetY() + 9;
				if($num_pedimentos > 0){
					$y_tmp = (int)$y999 + 9;

					if($y_tmp > 265){
						$this->AddPage();
						$this->SetY(48);

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(16,120,179);
						$this->RoundedRect($x999,$y999,190,5,3,'DF','1234');
						$this->SetTextColor(255,255,255);

						$this->Ln(5);

						$this->SetFont('DejaVu','',9);
						$this->SetXY($x999, $y999+1);
						$this->Cell(75,4,"    Pedimentos",0,0,'L');
						$this->SetTextColor(0,0,0);

						$x999 = $this->GetX();
						$y999 = $this->GetY()+6;

						$y999 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x999,$y999,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x999, $y999+1);
					$this->Cell(75,4,"    Pedimentos",0,0,'L');
					$this->SetTextColor(0,0,0);
				
					$y999 = $this->GetY()+4;
					while($obj_pedimento = $db->fetch_object($res_pedimentos)){
						$y_tmp = (int)$y999 + 10;

						if($y_tmp > 273){
							$this->AddPage();
							$this->SetY(48);

							$x999 = $this->GetX();
							$y999 = $this->GetY()+6;
							// //Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x999,$y999,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln(5);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x999, $y999+1);
							$this->Cell(75,4,"    Pedimentos",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y999 = $this->GetY() + 4;
						}

						$producto = new Product($db);
					    $producto->fetch($obj_pedimento->fk_product);

					    $segunda_columna = 65;

				        //Fila 1
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x999, $y999+1);
						$this->Cell(80,4,"Producto: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x999+14, $y999+1);
						$this->Cell(80,4,$this->limpiar($producto->ref),0,0,'L');

						//Fila 2
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x999, $y999+5);
						$this->Cell(80,4,"Pedimento: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x999+16, $y999+5);
						$this->Cell(80,4,$obj_pedimento->pedimento,0,0,'L');

						$num_y   = 10;
						$tamanio = 10;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x999,$y999,190,$tamanio,2,'D','1234');

						$y999 = $y999 + $num_y;
					}
				}
				//Termina Pedimentos

				//Inicia Tipos Transporte
				$val_tipo_transporte = (int)$tipo_transporte;

				switch($val_tipo_transporte){
					case 1:
							$sql_head_autotransporte = '';
							$sql_head_autotransporte = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_autotransporte WHERE facid = ".$facid." AND entity =".$entidad;
							$res_head_autotransporte = $db->query($sql_head_autotransporte);
							$num_head_autotransporte = $db->num_rows($res_head_autotransporte);

							if($num_head_autotransporte > 0){
								$obj_head_autotransporte = $db->fetch_object($res_head_autotransporte);

								$at_permiso_sct         = $obj_head_autotransporte->permiso_sct;
								$at_num_permiso_sct     = $obj_head_autotransporte->num_permiso_sct;
								$at_nom_aseg            = $obj_head_autotransporte->nom_aseg;

								$x5 = $x4;
								$y5 = $this->GetY() + 8;

								$y_tmp = (int)$y5 + 9;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"    AutoTransporte",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$tamanio_letra        = "8";
							    $label_at_permiso_sct = $this->_obtener_catalogo($at_permiso_sct,13);
							    $tamanio_etiqueta     = strlen($label_at_permiso_sct);
								if($tamanio_etiqueta > 131){
									$tamanio_letra = "7";
								}
								
								//Fila 1
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Permiso SCT: ",0,0,'L');
								$this->SetFont('DejaVu','',$tamanio_letra);
							    $this->SetXY($x5+19, $y5+1);
								$this->Cell(80,4,$this->limpiar($label_at_permiso_sct),0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"Número de permiso SCT: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+35, $y5+5);
								$this->Cell(80,4,$at_num_permiso_sct,0,0,'L');

								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,9,2,'D','1234');

								//Identificacion Vehicular
								$sql_config_vehicular = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_autotransporte_i_vehicular WHERE facid = ".$facid." AND entity =".$entidad;
								$res_config_vehicular = $db->query($sql_config_vehicular);
								$num_config_vehicular = $db->num_rows($res_config_vehicular);

								if($num_config_vehicular > 0){
									$x6 = $x5;
									$y6 = $this->GetY() + 8;

									$y_tmp = (int)$y6 + 9;

									if($y_tmp > 265){
										$this->AddPage();
										$this->SetY(48);

										$x6 = $this->GetX();
										$y6 = $this->GetY() + 8;
									}

									$this->SetDrawColor(16,120,179);
									$this->SetFillColor(16,120,179);
									$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
									$this->SetTextColor(255,255,255);

									$this->Ln(5);

									$this->SetFont('DejaVu','',9);
									$this->SetXY($x6, $y6+1);
									$this->Cell(75,4,"AutoTransporte - Identificación vehicular",0,0,'L');
									$this->SetTextColor(0,0,0);

									$y6 = $this->GetY()+4;

									while($obj_config_vehicular = $db->fetch_object($res_config_vehicular)){
										$y_tmp = (int)$y6 + 9;

										if($y_tmp > 265){
											$this->AddPage();
											$this->SetY(48);

											$x6 = $this->GetX();
											$y6 = $this->GetY()+6;
											// //Dibujar recuadro
											$this->SetDrawColor(16,120,179);
											$this->SetFillColor(16,120,179);
											$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
											$this->SetTextColor(255,255,255);

											$this->Ln(5);

											$this->SetFont('DejaVu','',9);
											$this->SetXY($x6, $y6+1);
											$this->Cell(75,4,"AutoTransporte - Configuración vehicular",0,0,'L');
											$this->SetTextColor(0,0,0);

											$y6 = $this->GetY() + 4;
										}

										//Fila 1
										$tamanio_letra        = "8";
									    $label_configuracion  = $this->_obtener_catalogo($obj_config_vehicular->config_vehicular,14);
									    $tamanio_etiqueta     = strlen($label_configuracion);
										if($tamanio_etiqueta > 131){
											$tamanio_letra = "6.25";
										}

										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x6, $y6+1);
										$this->Cell(80,4,"Configuración: ",0,0,'L');
										$this->SetFont('DejaVu','',$tamanio_letra);
									    $this->SetXY($x6+21, $y6+1);
									    
										$this->Cell(80,4,$this->limpiar($label_configuracion),0,0,'L');

										//Fila 2
										$this->SetFont('DejaVu','',8);
										$this->SetXY($x6, $y6+5);
										$this->Cell(80,4,"Placa VM: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
										$this->SetXY($x6+14, $y6+5);
										$this->Cell(80,4,$obj_config_vehicular->placa_vm,0,0,'L');

										$this->SetFont('DejaVu','',8);
										$this->SetXY($x6+55, $y6+5);
										$this->Cell(80,4,"Año modelo VM: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
										$this->SetXY($x6+78, $y6+5);
										$this->Cell(80,4,$obj_config_vehicular->anio_modelo_vm,0,0,'L');

										$num_y   = 9;
										$tamanio = 9;

										$this->SetDrawColor(16,120,179);
										$this->SetFillColor(194,229,243);
										$this->RoundedRect($x6,$y6,190,$tamanio,2,'D','1234');
										$y6 = $y6 + $num_y;
									}
								}

								//Seguros
								$sql_seguros = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_autotransporte_seguros WHERE facid = ".$facid." AND entity =".$entidad;
								$res_seguros = $db->query($sql_seguros);
								$num_seguros = $db->num_rows($res_seguros);

								if($num_seguros > 0){
									$x6 = $x5;
									$y6 = $this->GetY() + 8;

									$y_tmp = (int)$y6 + 13;

									if($y_tmp > 265){
										$this->AddPage();
										$this->SetY(48);

										$x6 = $this->GetX();
										$y6 = $this->GetY()+6;
										$y6 = $this->GetY() + 4;
									}

									$this->SetDrawColor(16,120,179);
									$this->SetFillColor(16,120,179);
									$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
									$this->SetTextColor(255,255,255);

									$this->Ln(5);

									$this->SetFont('DejaVu','',9);
									$this->SetXY($x6, $y6+1);
									$this->Cell(75,4,"    AutoTransporte - Seguros",0,0,'L');
									$this->SetTextColor(0,0,0);

									$y6 = $this->GetY()+4;

									while($obj_seguro = $db->fetch_object($res_seguros)){
										$y_tmp = (int)$y6 + 13;

										if($y_tmp > 265){
											$this->AddPage();
											$this->SetY(48);

											$x6 = $this->GetX();
											$y6 = $this->GetY()+6;
											// //Dibujar recuadro
											$this->SetDrawColor(16,120,179);
											$this->SetFillColor(16,120,179);
											$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
											$this->SetTextColor(255,255,255);

											$this->Ln(5);

											$this->SetFont('DejaVu','',9);
											$this->SetXY($x6, $y6+1);
											$this->Cell(75,4,"    AutoTransporte - Seguros",0,0,'L');
											$this->SetTextColor(0,0,0);

											$y6 = $this->GetY() + 4;
										}
									    
									    $segunda_columna = 110;

								        //Fila 1
								        $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6, $y6+1);
										$this->Cell(80,4,"Asegura Resp. Civil: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+28, $y6+1);
									    $this->Cell(80,4,$obj_seguro->aseguraRespCivil,0,0,'L');

									    $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+$segunda_columna, $y6+1);
										$this->Cell(80,4,"Póliza resp. civil: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x64+$segunda_columna+35, $y6+1);
									    $this->Cell(80,4,$obj_seguro->polizaRespCivil,0,0,'L');

									    //Fila 2
									    $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6, $y6+5);
										$this->Cell(80,4,"Asegura Med. Ambiente: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+34, $y6+5);
									    $this->Cell(80,4,$obj_seguro->aseguraMedAmbiente,0,0,'L');

									    $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+$segunda_columna, $y6+5);
										$this->Cell(80,4,"Póliza med. ambiente: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x64+$segunda_columna+41, $y6+5);
									    $this->Cell(80,4,$obj_seguro->polizaMedAmbiente,0,0,'L');

									    //Fila 3
									    $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6, $y6+9);
										$this->Cell(80,4,"Asegura Carga: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+22, $y6+9);
									    $this->Cell(80,4,$obj_seguro->aseguraCarga,0,0,'L');

									    $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+$segunda_columna, $y6+9);
										$this->Cell(80,4,"Póliza carga: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x64+$segunda_columna+29, $y6+9);
									    $this->Cell(80,4,$obj_seguro->polizaCarga,0,0,'L');

									    //Fila 4
									    $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6, $y6+13);
										$this->Cell(80,4,"Prima Seguro: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+21, $y6+13);
									    $this->Cell(80,4,$obj_seguro->primaSeguro,0,0,'L');

										$num_y   = 17;
										$tamanio = 17;

										$this->SetDrawColor(16,120,179);
										$this->SetFillColor(194,229,243);
										$this->RoundedRect($x6,$y6,190,$tamanio,2,'D','1234');
										$y6 = $y6 + $num_y;
									}
								}

								//Remolques
								$sql_at_remolques  = '';
								$sql_at_remolques  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_autotransporte_remolques WHERE facid = ".$facid." AND entity =".$entidad;
								$res_at_remolques  = $db->query($sql_at_remolques);
								$num_at_remolques  = $db->num_rows($res_at_remolques);

								if($num_at_remolques > 0){
									$x6 = $x5;
									$y6 = $this->GetY() + 8;

									$y_tmp = (int)$y6 + 9;

									if($y_tmp > 269){
										$this->AddPage();
										$this->SetY(48);

										$x6 = $this->GetX();
										$y6 = $this->GetY()+6;
										$y6 = $this->GetY() + 4;
									}

									$this->SetDrawColor(16,120,179);
									$this->SetFillColor(16,120,179);
									$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
									$this->SetTextColor(255,255,255);

									$this->Ln(5);

									$this->SetFont('DejaVu','',9);
									$this->SetXY($x6, $y6+1);
									$this->Cell(75,4,"    AutoTransporte - Remolques",0,0,'L');
									$this->SetTextColor(0,0,0);

									$y6 = $this->GetY()+4;

									while($obj_at_remolque = $db->fetch_object($res_at_remolques)){	
										$y_tmp = (int)$y6 + 5;

										if($y_tmp > 269){
											$this->AddPage();
											$this->SetY(48);

											$x6 = $this->GetX();
											$y6 = $this->GetY()+6;
											// //Dibujar recuadro
											$this->SetDrawColor(16,120,179);
											$this->SetFillColor(16,120,179);
											$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
											$this->SetTextColor(255,255,255);

											$this->Ln(5);

											$this->SetFont('DejaVu','',9);
											$this->SetXY($x6, $y6+1);
											$this->Cell(75,4,"    AutoTransporte - Remolques",0,0,'L');
											$this->SetTextColor(0,0,0);

											$y6 = $this->GetY() + 4;
										}
									    
									    $segunda_columna = 90;

								        //Fila 1
								        $this->SetFont('DejaVu','',8);
									    $this->SetXY($x6, $y6+1);
										$this->Cell(80,4,"Sub. Tipo: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x6+15, $y6+1);

									    $label_subtipo = $this->_obtener_catalogo($obj_at_remolque->sub_tipo,15);
										$this->Cell(80,4,$this->limpiar($label_subtipo),0,0,'L');

										$this->SetFont('DejaVu','',8);
										$this->SetXY($x6+95, $y6+1);
										$this->Cell(80,4,"Placas: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
										$this->SetXY($x6+105, $y6+1);
										$this->Cell(80,4,$obj_at_remolque->placas,0,0,'L');
										
										$num_y   = 5;
										$tamanio = 5;

										$this->SetDrawColor(16,120,179);
										$this->SetFillColor(194,229,243);
										$this->RoundedRect($x6,$y6,190,$tamanio,2,'D','1234');
										
										$y6 = $y6 + $num_y;
									}
								}
							}
					break;

					case 2:
							$sql_head_transporte_maritimo = '';
							$sql_head_transporte_maritimo = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_transporte_maritimo WHERE facid = ".$facid." AND entity =".$entidad;
							$res_head_transporte_maritimo = $db->query($sql_head_transporte_maritimo);
							$num_head_transporte_maritimo = $db->num_rows($res_head_transporte_maritimo);

							if($num_head_transporte_maritimo > 0){
								$obj_transporte_maritimo = $db->fetch_object($res_head_transporte_maritimo);

								$tm_permiso_sct              = $obj_transporte_maritimo->permiso_sct;
								$tm_num_permiso_sct          = $obj_transporte_maritimo->num_permiso_sct;
								$tm_nom_aseg                 = $obj_transporte_maritimo->nom_aseg;
								$tm_num_poliza_seguro        = $obj_transporte_maritimo->num_poliza_seguro;
								$tm_tipo_embarcacion         = $obj_transporte_maritimo->tipo_embarcacion;
								$tm_matricula                = $obj_transporte_maritimo->matricula;
								$tm_num_omi                  = $obj_transporte_maritimo->num_omi;
								$tm_anio_embarcacion         = $obj_transporte_maritimo->anio_embarcacion;
								$tm_nombre_embarcacion       = $obj_transporte_maritimo->nombre_embarcacion;
								$tm_nacionalidad_embarcacion = $obj_transporte_maritimo->nacionalidad_embarcacion;
								$tm_unidades_arq_bruto       = $obj_transporte_maritimo->unidades_arq_bruto;
								$tm_tipo_carga               = $obj_transporte_maritimo->tipo_carga;
								$tm_numcert_itc              = $obj_transporte_maritimo->numcert_itc;
								$tm_eslora                   = $obj_transporte_maritimo->eslora;
								$tm_manga                    = $obj_transporte_maritimo->manga;
								$tm_calado                   = $obj_transporte_maritimo->calado;
								$tm_linea_naviera            = $obj_transporte_maritimo->linea_naviera;
								$tm_nom_agente_naviero       = $obj_transporte_maritimo->nom_agente_naviero;
								$tm_num_aut_naviero          = $obj_transporte_maritimo->num_aut_naviero;
								$tm_num_viaje                = $obj_transporte_maritimo->num_viaje;
								$tm_num_conoc_embarc         = $obj_transporte_maritimo->num_conoc_embarc;

								$x5 = $x4;
								$y5 = $this->GetY() + 9;

								$y_tmp = (int)$y5 + 46;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"Transporte marítimo",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$segunda_columna = 90;

								//Fila 1
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Permiso SCT: ",0,0,'L');
								$label_tm_permiso_sct = $this->_obtener_catalogo($tm_permiso_sct,13);
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+19, $y5+1);
								$this->Cell(80,4,$this->limpiar($label_tm_permiso_sct),0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"Num. de permiso SCT: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+31, $y5+5);
								$this->Cell(80,4,$tm_num_permiso_sct,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna, $y5+5);
								$this->Cell(80,4,"Aseguradora: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna+19, $y5+5);
								$this->Cell(80,4,$this->limpiar($tm_nom_aseg),0,0,'L');

								//Fila 3
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+9);
								$this->Cell(80,4,"Num. de Póliza de seguro: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+36, $y5+9);
								$this->Cell(80,4,$tm_num_poliza_seguro,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna, $y5+9);
								$this->Cell(80,4,"Tipo de embarcación: ",0,0,'L');
								$label_tm_tipo_embarcacion = $this->_obtener_catalogo($tm_tipo_embarcacion,16);
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna+31, $y5+9);
								$this->Cell(80,4,$this->limpiar($label_tm_tipo_embarcacion),0,0,'L');

								//Fila 4
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+13);
								$this->Cell(80,4,"Matricula: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+14, $y5+13);
								$this->Cell(80,4,$tm_matricula,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+55, $y5+13);
								$this->Cell(80,4,"Número Omi: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+55+19, $y5+13);
								$this->Cell(80,4,$tm_num_omi,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+115, $y5+13);
								$this->Cell(80,4,"Año embarcación: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+130, $y5+13);
								$this->Cell(80,4,$tm_anio_embarcacion,0,0,'L');

								//Fila 5
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+17);
								$this->Cell(80,4,"Nombre embarcación: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+31, $y5+17);
								$this->Cell(80,4,$this->limpiar($tm_nombre_embarcacion),0,0,'L');

								//Fila 6
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+21);
								$this->Cell(80,4,"Nac. embarcación: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+27, $y5+21);
								$this->Cell(80,4,$tm_nacionalidad_embarcacion,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+55, $y5+21);
								$this->Cell(80,4,"Unidades de Arq bruto: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+55+33, $y5+21);
								$this->Cell(80,4,$tm_unidades_arq_bruto,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+115, $y5+21);
								$this->Cell(80,4,"Tipo Carga: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+132, $y5+21);
								$this->Cell(80,4,$tm_tipo_carga,0,0,'L');

								//Fila 7
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+25);
								$this->Cell(80,4,"NumCertITC: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+18, $y5+25);
								$this->Cell(80,4,$tm_numcert_itc,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+55, $y5+25);
								$this->Cell(80,4,"Eslora: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+55+10, $y5+25);
								$this->Cell(80,4,$tm_eslora,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+95, $y5+25);
								$this->Cell(80,4,"Manga: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+105, $y5+25);
								$this->Cell(80,4,$tm_manga,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+145, $y5+25);
								$this->Cell(80,4,"Calado: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+156, $y5+25);
								$this->Cell(80,4,$tm_calado,0,0,'L');

								// Fila 8
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+29);
								$this->Cell(80,4,"Linea naviera: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+20, $y5+29);
								$this->Cell(80,4,$tm_linea_naviera,0,0,'L');

								// Fila 9
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+33);
								$this->Cell(80,4,"Nom. Agente Naviero: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+30, $y5+33);
								$this->Cell(80,4,$tm_nom_agente_naviero,0,0,'L');

								// Fila 10
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+37);
								$this->Cell(80,4,"Núm. autorización naviero: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+38, $y5+37);
								$this->Cell(80,4,$tm_num_aut_naviero,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+100, $y5+37);
								$this->Cell(80,4,"Num. Viaje: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+116, $y5+37);
								$this->Cell(80,4,$tm_num_viaje,0,0,'L');

								// Fila 11
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+41);
								$this->Cell(80,4,"Núm. conoc embarc: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+29, $y5+41);
								$this->Cell(80,4,$tm_num_conoc_embarc,0,0,'L');

								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,46,2,'D','1234');

								$sql_tm_contenedores  = '';
								$sql_tm_contenedores  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_transporte_maritimo_contenedores WHERE facid = ".$facid." AND entity =".$entidad;
								$res_tm_contenedores  = $db->query($sql_tm_contenedores);
								$num_tm_contenedores  = $db->num_rows($res_tm_contenedores);

								$x6 = $x5;
								$y6 = $this->GetY() + 9;

								$y_tmp = (int)$y6 + 10;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x6 = $this->GetX();
									$y6 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x6, $y6+1);
								$this->Cell(75,4,"Transporte marítimo - Contenedores",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y6 = $this->GetY()+4;

								while($obj_tm_contenedor = $db->fetch_object($res_tm_contenedores)){	
									$y_tmp = (int)$y6 + 7;

									if($y_tmp > 265){
										$this->AddPage();
										$this->SetY(48);

										$x6 = $this->GetX();
										$y6 = $this->GetY()+6;
										// //Dibujar recuadro
										$this->SetDrawColor(16,120,179);
										$this->SetFillColor(16,120,179);
										$this->RoundedRect($x6,$y6,190,5,3,'DF','1234');
										$this->SetTextColor(255,255,255);

										$this->Ln(5);

										$this->SetFont('DejaVu','',9);
										$this->SetXY($x6, $y6+1);
										$this->Cell(75,4,"Transporte Marítimo - Contenedores",0,0,'L');
										$this->SetTextColor(0,0,0);

										$y6 = $this->GetY() + 4;
									}

								    $segunda_columna = 45;
								    $tercera_columna = 125;

							        //Fila 1
							        $this->SetFont('DejaVu','',8);
								    $this->SetXY($x6, $y6+1);
									$this->Cell(80,4,"Matricula: ",0,0,'L');
									$this->SetFont('DejaVu','',8);
								    $this->SetXY($x6+14, $y6+1);
									$this->Cell(80,4,$obj_tm_contenedor->matricula,0,0,'L');

									$this->SetFont('DejaVu','',8);
									$this->SetXY($x6+$segunda_columna, $y6+1);
									$this->Cell(80,4,"Tipo: ",0,0,'L');
									$this->SetFont('DejaVu','',8);
									$this->SetXY($x6+$segunda_columna+8, $y6+1);
									$label_tm_contenedor = $this->_obtener_catalogo($obj_tm_contenedor->tipo,19);
									$this->Cell(80,4,$label_tm_contenedor,0,0,'L');

									$this->SetFont('DejaVu','',8);
									$this->SetXY($x6+$tercera_columna, $y6+1);
									$this->Cell(80,4,"Num Precinto: ",0,0,'L');
									$this->SetFont('DejaVu','',8);
									$this->SetXY($x6+$tercera_columna+20, $y6+1);
									$this->Cell(80,4,$obj_tm_contenedor->num_precinto,0,0,'L');

									$num_y   = 7;
									$tamanio = 7;

									$this->SetDrawColor(16,120,179);
									$this->SetFillColor(194,229,243);
									$this->RoundedRect($x6,$y6,190,$tamanio,2,'D','1234');
									$y6 = $y6 + $num_y;
								}
							}	
					break;

					case 3:
							$sql_head_transporte_aereo = '';
							$sql_head_transporte_aereo = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_transporte_aereo WHERE facid = ".$facid." AND entity =".$entidad;
							$res_head_transporte_aereo = $db->query($sql_head_transporte_aereo);
							$num_head_transporte_aereo = $db->num_rows($res_head_transporte_aereo);

							if($num_head_transporte_aereo > 0){
								$obj_trasnporte_aereo = $db->fetch_object($res_head_transporte_aereo);

								$ta_permiso_sct                = $obj_trasnporte_aereo->permiso_sct;
								$ta_num_permiso_sct            = $obj_trasnporte_aereo->num_permiso_sct;
								$ta_matricula_aeronave         = $obj_trasnporte_aereo->matricula_aeronave;
								$ta_nom_aseg                   = $obj_trasnporte_aereo->nom_aseg;
								$ta_num_poliza_seguro          = $obj_trasnporte_aereo->num_poliza_seguro;
								$ta_num_guia                   = $obj_trasnporte_aereo->num_guia;
								$ta_lugar_contrato             = $obj_trasnporte_aereo->lugar_contrato;
								$ta_codigo_transportista       = $obj_trasnporte_aereo->codigo_transportista;
								$ta_rfc_embarcador             = $obj_trasnporte_aereo->rfc_embarcador;
								$ta_numregidtrib_embarc        = $obj_trasnporte_aereo->numregidtrib_embarc;
								$ta_residencia_fiscal_embarc   = $obj_trasnporte_aereo->residencia_fiscal_embarc;
								$ta_nom_embarcador             = $obj_trasnporte_aereo->nom_embarcador;

								$x5 = $x4;
								$y5 = $this->GetY() + 9;

								$y_tmp = (int)$y5 + 18;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"Transporte aéreo",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$segunda_columna = 100;
								
								//Fila 1
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Permiso SCT: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+19, $y5+1);
							    $label_ta_permiso_sct = $this->_obtener_catalogo($ta_permiso_sct,13);
								$this->Cell(80,4,$this->limpiar($label_ta_permiso_sct),0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"Número de permiso SCT: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+35, $y5+5);
								$this->Cell(80,4,$this->limpiar($ta_num_permiso_sct),0,0,'L');

								//Fila 3
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+9);
								$this->Cell(80,4,"Matricula Aeronove: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+28, $y5+9);
								$this->Cell(80,4,$ta_matricula_aeronave,0,0,'L');

								//Fila 4
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+13);
								$this->Cell(80,4,"Código transportista: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+30, $y5+13);
								$this->Cell(80,4,$ta_codigo_transportista,0,0,'L');
								
								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,18,2,'D','1234');

								//Inicia Apartado TA - Aseguradora
								$x5 = $x5;
								$y5 = $this->GetY() + 9;

								$y_tmp = (int)$y5 + 18;

								if($y_tmp > 270){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"Transporte aéreo - Aseguradora",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$segunda_columna = 100;
								
								//Fila 1
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Nombre: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+13, $y5+1);
								$this->Cell(80,4,$this->limpiar($ta_nom_aseg),0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"Número de póliza: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+26, $y5+5);
								$this->Cell(80,4,$ta_num_poliza_seguro,0,0,'L');

								//Fila 3
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+9);
								$this->Cell(80,4,"Número de guía: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+24, $y5+9);
								$this->Cell(80,4,$ta_num_guia,0,0,'L');

								//Fila 4
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+13);
								$this->Cell(80,4,"Lugar Contrato: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+22, $y5+13);
								$this->Cell(80,4,$ta_lugar_contrato,0,0,'L');

								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,18,2,'D','1234');

								//Termina Apartado TA - Aseguradora
								
								//Inicia Apartado TA - Embarcador
								$x5 = $x5;
								$y5 = $this->GetY() + 9;

								$y_tmp = (int)$y5 + 18;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"Transporte aéreo - Embarcador",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$segunda_columna = 100;
								
								//Fila 1
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Nombre: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+12, $y5+1);
								$this->Cell(80,4,$this->limpiar($ta_nom_embarcador),0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"RFC: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+9, $y5+5);
								$this->Cell(80,4,$ta_rfc_embarcador,0,0,'L');

								//Fila 3
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+9);
								$this->Cell(80,4,"Residencia Fiscal: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+26, $y5+9);
								$this->Cell(80,4,$ta_residencia_fiscal_embarc,0,0,'L');

								//Fila 4
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5, $y5+13);
								$this->Cell(80,4,"NumRegIdTrib: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+22, $y5+13);
								$this->Cell(80,4,$ta_numregidtrib_embarc,0,0,'L');

								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,18,2,'D','1234');

								//Termina Apartado TA - Embarcador
							}
					break;

					case 4:
							$sql_head_transporte_ferroviario = '';
							$sql_head_transporte_ferroviario = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_transporte_ferroviario WHERE facid = ".$facid." AND entity =".$entidad;
							$res_head_transporte_ferroviario = $db->query($sql_head_transporte_ferroviario);
							$num_head_transporte_ferroviario = $db->num_rows($res_head_transporte_ferroviario);

							if($num_head_transporte_ferroviario > 0){
								$obj_transporte_ferroviario = $db->fetch_object($res_head_transporte_ferroviario);

								$tf_tipo_servicio         = $obj_transporte_ferroviario->tipo_servicio;
								$tf_nom_aseguradora       = $obj_transporte_ferroviario->nom_aseguradora;
								$tf_num_poliza_seguro     = $obj_transporte_ferroviario->num_poliza_seguro;
								$tf_tipo_trafico          = $obj_transporte_ferroviario->tipo_trafico;
								$tf_tipo_derecho_paso     = $obj_transporte_ferroviario->tipo_derecho_paso;
								$tf_kilometraje_pagado    = $obj_transporte_ferroviario->kilometraje_pagado;
								$tf_tipo_carro            = $obj_transporte_ferroviario->tipo_carro;
								$tf_matricula_carro       = $obj_transporte_ferroviario->matricula_carro;
								$tf_guia_carro            = $obj_transporte_ferroviario->guia_carro;
								$tf_toneladas_netas_carro = $obj_transporte_ferroviario->toneladas_netas_carro;

								$x5 = $x4;
								$y5 = $this->GetY() + 9;

								$y_tmp = (int)$y5 + 18;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"Transporte ferroviario",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$segunda_columna = 100;
								
								//Fila 1
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Tipo de Servicio: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+24, $y5+1);
							    $label_tf_tipo_servicio = $this->_obtener_catalogo($tf_tipo_servicio,21);
								$this->Cell(80,4,$label_tf_tipo_servicio,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna, $y5+1);
								$this->Cell(80,4,"Tipo Derecho de Paso: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna+31, $y5+1);
								$label_tf_tipo_servicio = $this->_obtener_catalogo($tf_tipo_derecho_paso,23);
								$this->Cell(80,4,$label_tf_tipo_servicio,0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"Aseguradora: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+19, $y5+5);
								$this->Cell(80,4,$this->limpiar($tf_nom_aseguradora),0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna, $y5+5);
								$this->Cell(80,4,"Kilometraje Pagado: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna+28, $y5+5);
								$this->Cell(80,4,$tf_kilometraje_pagado,0,0,'L');

								//Fila 3
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+9);
								$this->Cell(80,4,"Número póliza seguro: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+32, $y5+9);
								$this->Cell(80,4,$tf_num_poliza_seguro,0,0,'L');

								//Fila 4
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+13);
								$this->Cell(80,4,"Tipo de tráfico: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+22, $y5+13);
							    $label_tf_tipo_trafico = $this->_obtener_catalogo($tf_tipo_trafico, 22);
								$this->Cell(80,4,$label_tf_tipo_trafico,0,0,'L');

								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,18,2,'D','1234');

								$x5 = $x4;
								$y5 = $this->GetY() + 9;

								$y_tmp = (int)$y5 + 10;

								if($y_tmp > 265){
									$this->AddPage();
									$this->SetY(48);

									$x5 = $this->GetX();
									$y5 = $this->GetY() + 8;
								}

								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(16,120,179);
								$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
								$this->SetTextColor(255,255,255);

								$this->Ln(5);

								$this->SetFont('DejaVu','',9);
								$this->SetXY($x5, $y5+1);
								$this->Cell(75,4,"Transporte ferroviario - Carro",0,0,'L');
								$this->SetTextColor(0,0,0);

								$y5 = $this->GetY()+4;

								$segunda_columna = 55;
								$tercera_columna = 105;								
								
								//Fila 1
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+1);
								$this->Cell(80,4,"Tipo: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+8, $y5+1);
							    $label_tf_tipo_carro = $this->_obtener_catalogo($tf_tipo_carro, 24);
								$this->Cell(80,4,$label_tf_tipo_carro,0,0,'L');

								//Fila 2
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5, $y5+5);
								$this->Cell(80,4,"Matricula: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
							    $this->SetXY($x5+14, $y5+5);
								$this->Cell(80,4,$tf_matricula_carro,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna, $y5+5);
								$this->Cell(80,4,"Guía: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$segunda_columna+8, $y5+5);
								$this->Cell(80,4,$tf_guia_carro,0,0,'L');

								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$tercera_columna, $y5+5);
								$this->Cell(80,4,"Toneladas Netas: ",0,0,'L');
								$this->SetFont('DejaVu','',8);
								$this->SetXY($x5+$tercera_columna+24, $y5+5);
								$this->Cell(80,4,$tf_toneladas_netas_carro,0,0,'L');

								//Dibujar recuadro
								$this->SetDrawColor(16,120,179);
								$this->SetFillColor(194,229,243);
								$this->RoundedRect($x5,$y5,190,10,2,'D','1234');

								$sql_tf_contenedores = '';
								$sql_tf_contenedores = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_transporte_f_contenedores WHERE facid = ".$facid." AND entity =".$entidad;
								$res_tf_contenedores = $db->query($sql_tf_contenedores);
								$num_tf_contenedores = $db->num_rows($res_tf_contenedores);

								if($num_tf_contenedores > 0){
									$x5 = $x4;
									$y5 = $this->GetY() + 9;

									$y_tmp = (int)$y5 + 46;

									if($y_tmp > 265){
										$this->AddPage();
										$this->SetY(48);

										$x5 = $this->GetX();
										$y5 = $this->GetY() + 8;
									}

									$this->SetDrawColor(16,120,179);
									$this->SetFillColor(16,120,179);
									$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
									$this->SetTextColor(255,255,255);

									$this->Ln(5);

									$this->SetFont('DejaVu','',9);
									$this->SetXY($x5, $y5+1);
									$this->Cell(75,4,"Transporte ferroviario - Contenedores",0,0,'L');
									$this->SetTextColor(0,0,0);

									$y5 = $this->GetY()+4;
									$this->SetFont('DejaVu','',8);

									while($obj_tf_contenedor = $db->fetch_object($res_tf_contenedores)){
										$y_tmp = (int)$y5 + 6;

										if($y_tmp > 265){
											$this->AddPage();
											$this->SetY(48);

											$x5 = $this->GetX();
											$y5 = $this->GetY()+6;
											// //Dibujar recuadro
											$this->SetDrawColor(16,120,179);
											$this->SetFillColor(16,120,179);
											$this->RoundedRect($x5,$y5,190,5,3,'DF','1234');
											$this->SetTextColor(255,255,255);

											$this->Ln(5);

											$this->SetFont('DejaVu','',9);
											$this->SetXY($x5, $y6+1);
											$this->Cell(75,4,"Transporte ferroviario - Contenedores",0,0,'L');
											$this->SetTextColor(0,0,0);

											$y5 = $this->GetY() + 4;
										}

									    $segunda_columna = 40;
									    $tercera_columna = 110;

								        //Fila 1
								        $this->SetFont('DejaVu','',8);
									    $this->SetXY($x5, $y5+1);
										$this->Cell(80,4,"Tipo: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
									    $this->SetXY($x5+8, $y5+1);
										$this->Cell(80,4,$obj_tf_contenedor->tipo,0,0,'L');

										$this->SetFont('DejaVu','',8);
										$this->SetXY($x5+$segunda_columna, $y5+1);
										$this->Cell(80,4,"Peso Contenedor Vacio: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
										$this->SetXY($x5+$segunda_columna+34, $y5+1);
										$this->Cell(80,4,$obj_tf_contenedor->peso_contendor_vacio,0,0,'L');

										$this->SetFont('DejaVu','',8);
										$this->SetXY($x5+$tercera_columna, $y5+1);
										$this->Cell(80,4,"Peso Neto Merancia: ",0,0,'L');
										$this->SetFont('DejaVu','',8);
										$this->SetXY($x5+$tercera_columna+29, $y5+1);
										$this->Cell(80,4,$obj_tf_contenedor->peso_neto_mercancia,0,0,'L');

										$num_y   = 6;
										$tamanio = 6;

										$this->SetDrawColor(16,120,179);
										$this->SetFillColor(194,229,243);
										$this->RoundedRect($x5,$y5,190,$tamanio,2,'D','1234');
										
										$y5 = $y5 + $num_y;
									}
								}
							}
					break;
				}

				//Termina Tipos Transporte

				//Inicia Figura Transporte
				$sql_figuras_transporte = '';
				$sql_figuras_transporte = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte_figura_transporte WHERE facid = ".$facid." AND entity =".$entidad;
				$res_figuras_transporte = $db->query($sql_figuras_transporte);
				$num_figuras_transporte = $db->num_rows($res_figuras_transporte);

				if($num_figuras_transporte > 0){
					$x10 = $x;
					$y10 = $this->GetY() + 9;

					$y_tmp = (int)$y10 + 31;

					if($y_tmp > 265){
						$this->AddPage();
						$this->SetY(48);

						$x10 = $this->GetX();
						$y10 = $this->GetY() + 4;
					}

					$this->SetDrawColor(16,120,179);
					$this->SetFillColor(16,120,179);
					$this->RoundedRect($x10,$y10,190,5,3,'DF','1234');
					$this->SetTextColor(255,255,255);

					$this->Ln(5);

					$this->SetFont('DejaVu','',9);
					$this->SetXY($x10, $y10+1);
					$this->Cell(75,4,"    Figura Transporte",0,0,'L');
					$this->SetTextColor(0,0,0);

					$y10 = $this->GetY()+4;

					while ($obj_figura_transporte = $db->fetch_object($res_figuras_transporte)) {
						$y_tmp = (int)$y10 + 29;

						if($y_tmp > 265){
							$this->AddPage();
							$this->SetY(48);

							$x10 = $this->GetX();
							$y10 = $this->GetY()+6;
							// //Dibujar recuadro
							$this->SetDrawColor(16,120,179);
							$this->SetFillColor(16,120,179);
							$this->RoundedRect($x10,$y10,190,5,3,'DF','1234');
							$this->SetTextColor(255,255,255);

							$this->Ln(5);

							$this->SetFont('DejaVu','',9);
							$this->SetXY($x10, $y10+1);
							$this->Cell(75,4,"    Figura Transporte",0,0,'L');
							$this->SetTextColor(0,0,0);

							$y10 = $this->GetY() + 4;
						}

					    $segunda_columna = 45;
					    $tercera_columna = 100;

				        //Fila 1
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+1);
						$this->Cell(80,4,"Tipo: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+8, $y10+1);
					    $label_tipo_figura = $this->_obtener_catalogo($obj_figura_transporte->tipo,1000);
						$this->Cell(80,4,$label_tipo_figura,0,0,'L');

						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$segunda_columna, $y10+1);
						$this->Cell(80,4,"Parte Transporte: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$segunda_columna+25, $y10+1);
						$this->Cell(80,4,$obj_figura_transporte->parte_transporte,0,0,'L');

						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$tercera_columna, $y10+1);
						$this->Cell(80,4,"Res. Fiscal: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$tercera_columna+17, $y10+1);
						$this->Cell(80,4,$obj_figura_transporte->residencia_fiscal,0,0,'L');

						//Fila 2
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+5);
						$this->Cell(80,4,"Nombre: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+13, $y10+5);
						$this->Cell(80,4,$obj_figura_transporte->nombre,0,0,'L');

						//Fila 3
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+9);
						$this->Cell(80,4,"RFC: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+8, $y10+9);
						$this->Cell(80,4,$obj_figura_transporte->rfc,0,0,'L');

						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$segunda_columna, $y10+9);
						$this->Cell(80,4,"Num. Licencia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$segunda_columna+21, $y10+9);
						$this->Cell(80,4,$obj_figura_transporte->num_licencia,0,0,'L');

						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$tercera_columna, $y10+9);
						$this->Cell(80,4,"NumRegIdTrib: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$tercera_columna+21, $y10+9);
						$this->Cell(80,4,$obj_figura_transporte->numregidtrib,0,0,'L');

						//Fila 4
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+9);
						$this->Cell(80,4,"RFC: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+8, $y10+9);
						$this->Cell(80,4,$obj_figura_transporte->rfc,0,0,'L');

						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$segunda_columna, $y10+9);
						$this->Cell(80,4,"Num. Licencia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$segunda_columna+21, $y10+9);
						$this->Cell(80,4,$obj_figura_transporte->num_licencia,0,0,'L');

						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$tercera_columna, $y10+9);
						$this->Cell(80,4,"NumRegIdTrib: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+$tercera_columna+21, $y10+9);
						$this->Cell(80,4,$obj_figura_transporte->numregidtrib,0,0,'L');

						//Fila 5
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+13);
						$this->Cell(80,4,"Calle: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+9, $y10+13);
						$this->Cell(80,4,$obj_figura_transporte->calle,0,0,'L');

						//Fila 6
				        $this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+17);
						$this->Cell(80,4,"Num. Int: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+13, $y10+17);
						$this->Cell(80,4,$obj_figura_transporte->num_int,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+40, $y10+17);
						$this->Cell(80,4,"Num. Ext: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+54, $y10+17);
						$this->Cell(80,4,$obj_figura_transporte->num_ext,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+85, $y10+17);
						$this->Cell(80,4,"Colonia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+97, $y10+17);
						$this->Cell(80,4,$obj_figura_transporte->colonia,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+138, $y10+17);
						$this->Cell(80,4,"Localidad: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+153, $y10+17);
						$this->Cell(80,4,$obj_figura_transporte->localidad,0,0,'L');

						//Fila 7
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+21);
						$this->Cell(80,4,"C.P.: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+7, $y10+21);
						$this->Cell(80,4,$obj_figura_transporte->cp,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+40, $y10+21);
						$this->Cell(80,4,"Referencia: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+57, $y10+21);
						$this->Cell(80,4,$obj_figura_transporte->referencia,0,0,'L');

						//Fila 8
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10, $y10+25);
						$this->Cell(80,4,"Municipio: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
					    $this->SetXY($x10+15, $y10+25);
						$this->Cell(80,4,$obj_figura_transporte->municipio,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+40, $y10+25);
						$this->Cell(80,4,"Estado: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+51, $y10+25);
						$this->Cell(80,4,$obj_figura_transporte->estado,0,0,'L');

						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+85, $y10+25);
						$this->Cell(80,4,"País: ",0,0,'L');
						$this->SetFont('DejaVu','',8);
						$this->SetXY($x10+92, $y10+25);
						$this->Cell(80,4,$obj_ubicacion->pais,0,0,'L');

						$num_y   = 29;
						$tamanio = 29;

						$this->SetDrawColor(16,120,179);
						$this->SetFillColor(194,229,243);
						$this->RoundedRect($x10,$y10,190,$tamanio,2,'D','1234');
						
						$y10 = $y10 + $num_y;
					}
				}
				//Termina Figura Transporte
			}
		}

	    // Page footer
	    function Footer()
		{
		    // Go to 1.5 cm from bottom
		    $this->SetY(-15);
		    // Select Arial italic 8
		    $this->SetFont('DejaVu','',6);
		    // Print current and total page numbers
		    $this->Cell(0,10,'Este documento es una representación gráfica de un CFDI - Página '.$this->PageNo().'/{nb}',0,0,'C');
		}
		#Funciones de armado de PDF (Fin)
	}

	$id = $_GET['facid'];
	$cfdi_commit = $_GET['cfdi_commit'];

	$pdf=new cfdiPDF();
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	$pdf->SetFont('DejaVu','',14);		
	
	$pdf->AddPage();
	$pdf->AliasNbPages();
	$pdf->SetFont('DejaVu','',14);
	//Table with n rows and 6 columns add columns PDF
	$pdf->SetWidths(array(16,12,10,64,22,22,22,22));
	$pdf->SetAligns(array('C','C','C','L','R','R','R','R'));

	$pdf->SetFont('DejaVu','',10);
	//$pdf->SetFillColor(0,0,0);//Fondo verde de celda
	$pdf->SetTextColor(0, 0, 0); //Letra color negro

	$object = new Facture($db);
	$object->fetch($id);

	$pdf->SetY($pdf->GetY()+5);
	$pdf->_pageDatosComprobante($pdf->GetX(), $pdf->GetY());

	$pdf->SetY($pdf->GetY()+5);
	$pdf->_pageReceptor($pdf->GetX(), $pdf->GetY());

	$pdf->SetY($pdf->GetY()+5);
	$pdf->_pageCFDIRel($pdf->GetX(), $pdf->GetY());

	if ($object->note_public != "") {
		$pdf->SetY($pdf->GetY()+1);
		$pdf->_pageObservaciones($pdf->GetX(), $pdf->GetY());
	}

	$pdf->SetY($pdf->GetY()+2);

	$pdf->SetFont('DejaVu','', 8);

	//Dibujar recuadro
	$pdf->SetDrawColor(16,120,179);
	$pdf->SetFillColor(16,120,179);
	$pdf->RoundedRect($pdf->GetX(),$pdf->GetY()+1,190,5,3,'DF','1234');

	//Cabeceras de tabla de partidas
	$pdf->SetTextColor(255,255,255);
	$pdf->Cell(16,7,"Clave",0,0,'C');
	$pdf->Cell(12,7,"Cant.",0,0,'C');
	$pdf->Cell(10,7,"Unidad",0,0,'C');
	$pdf->Cell(64,7,"Descripción",0,0,'C');
	$pdf->Cell(22,7,"Precio unitario",0,0,'C');
	//$pdf->Cell(22,7,"I.V.A.",0,0,'C');
	$pdf->Cell(22,7,"Dto.",0,0,'C');
	$pdf->Cell(22,7,"C/DESC",0,0,'C');
	$pdf->Cell(22,7,"Importe",0,0,'C');
	$pdf->Ln();
	$pdf->SetTextColor(0,0,0);

	$producto = new Product($db);
	//Nuevo armado de conceptos
	for($i=0;$i<sizeof($object->lines);$i++) {
		if ($object->lines[$i]->fk_product != "") {
			$producto->fetch($object->lines[$i]->fk_product);

			$descripcion      = $pdf->limpiar($producto->description);

			if($descripcion == '' || $descripcion == null){
				$descripcion      = $pdf->limpiar($producto->label);
			}

		    $unidad           = $producto->array_options["options_umed"];
		    $claveprodserv    = $producto->array_options["options_claveprodserv"];
		    $noIdentificacion = $producto->array_options["options_noidenticfdi"];
		    $cuentapredial    = $producto->array_options["options_cuentapredial"];
		    $tipoFactor       = $producto->array_options["options_exentoiva"];
		}else{
			$descripcion      = $pdf->limpiar($object->lines[$i]->desc);
			$unidad           = $object->lines[$i]->array_options["options_umed"];
			$claveprodserv    = $object->lines[$i]->array_options["options_claveprodserv"];
			$noIdentificacion = $object->lines[$i]->array_options["options_noidenticfdi"];
			$cuentapredial    = $object->lines[$i]->array_options["options_cuentapredial"];
			$tipoFactor       = $object->lines[$i]->array_options["options_exentoiva"];
		}

		$cantidad       = $object->lines[$i]->qty;
		$total_des      = 0;
		$porcentaje_des = 0;

		//Si esta activo multimoneda 
		if($conf->global->MAIN_MODULE_MULTICURRENCY == 1 && $pdf->data["moneda"] != "MXN") {
			$precio_unitario = $object->lines[$i]->multicurrency_subprice;
			$iva             = $object->lines[$i]->multicurrency_total_tva;
			$importe         = $object->lines[$i]->multicurrency_total_ht;
			$total_partida   = $object->lines[$i]->multicurrency_total_ttc;
		}else{
			$precio_unitario = $object->lines[$i]->subprice;
			$iva             = $object->lines[$i]->total_tva;
			$importe         = $object->lines[$i]->total_ht;
			$total_partida   = $object->lines[$i]->total_ttc;
		}
		$cdesc = $precio_unitario;

		if($object->lines[$i]->remise_percent != 0){
			$porcentaje_des = $object->lines[$i]->remise_percent/100;
			$total_des      = ($precio_unitario * $cantidad) * $porcentaje_des;
			$cdesc = (1-($porcentaje_des/100))* $precio_unitario;
		}

		if($conf->global->CFDIMX_DESC_PDF == 0){
			$descripcion_pdf = $descripcion;
		}else{
			$descripcion_pdf = strtoupper($descripcion);
		}

		$pdf->Row( 
					array(
							$claveprodserv,
							$cantidad,
							$unidad,
							ucwords(trim($descripcion_pdf)),
							"$ ".number_format($precio_unitario, 2),
							//"$ ".number_format($iva, 2),
							number_format($porcentaje_des, 2),
							"$ ".number_format($cdesc, 2),
							"$ ".number_format($total_partida, 2)
						)
				);
	}

	$pdf->_pageTotal($pdf->GetX(), $pdf->GetY(),$porcentaje_des);
	$pdf->_pageNum2Letra($pdf->GetX(), $pdf->GetY());
	$pdf->_pageQRCode();

	$pdf->_pageCartaPorte();

	$pdf->Output($conf->facture->dir_output."/".$object->ref."/Nuevo_".$pdf->data['uuid'].".pdf", 'F');

	if ($route == "genera")  {
		header('Location: facture.php?facid='.$id.'&cfdi_commit='.$cfdi_commit.'');
	}else {
		print '<script>window.location.href="facture.php?facid='.$id.'";</script>';
	}
?>
