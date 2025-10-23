<?php
//============================================================+
// File name   : generatorPDF.php
// Begin       : 2019-08-15
// Last Update : 2021-07-14
//
// Description : Generador de PDF del módulo CFDI 3.3
//
//
// Author: AURIBOX CONSULTING
//
// (c) Copyright:
//               AURIBOX CONSULTING
//============================================================+

// Se incluye el archivo principal Main
require_once '../main.inc.php';
require_once(DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
// Se incluye la libreria FPDF.
require_once('lib/fpdf/fpdf.php');
require_once('lib/numero_a_letra.php');
require_once('lib/phpqrcode/qrlib.php');


// GETPOST values
$id = (GETPOST('id') != "") ? GETPOST('id') : GETPOST('facid');
$route = GETPOST('route');

global $conf, $langs, $db;

$langs->load("bills");


class cfdiPDF extends FPDF
{
	var $widths;
	var $aligns;
	var $data;

	public function limpiar($cadena){
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
		$cadena = str_replace("ñ","n",$cadena);
		$cadena = str_replace("Ñ","N",$cadena);
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
		return $cadena;
	}

	function SetData($id) {
		
		global $db, $conf;

		$query = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx WHERE fk_facture='.$id.' AND entity_id='.$conf->entity;

		$resql = $db->query($query);

		//$this->data["version"];
		// $this->data["uuid"];
		// $this->data["cadena"];
		// $this->data["selloCFD"];
		// $this->data["selloSAT"];
		// $this->data["fechaTimbrado"];
		// $this->data["certificado"];
		// $this->data["certEmisor"];
		// $this->data["u4dig"];
		// $this->data["fechaEmision"];
		// $this->data["coccds"];

		if($db->num_rows($resql) > 0){
			while ($obj = $db->fetch_object($resql)) {

				$aux["version"] = $obj->version;
				$aux["uuid"] = $obj->uuid;
				$aux["moneda"] = $obj->divisa;
				$aux["cadena"] = $obj->cadena;
				$aux["selloCFD"] = $obj->selloCFD;
				$aux["selloSAT"] = $obj->sello;
				$aux["fechaTimbrado"] = $obj->fechaTimbrado;
				$aux["certificado"] = $obj->certificado;
				$aux["certEmisor"] = $obj->certEmisor;
				$aux["u4dig"] = $obj->u4dig;
				$aux["fechaEmision"] = $obj->fecha_emision."T".$obj->hora_emision;
				$aux["coccds"] = "||".$aux["version"]."|".$aux["uuid"]."|".$aux["fechaTimbrado"]."|".$aux["selloCFD"]."|".$obj->certificado."||";
			}
		}
		$this->data = $aux;
		//return $this->data;
	}

	function SetWidths($w)
	{
		//Set the array of column widths
		$this->widths=$w;
	}

	function SetAligns($a)
	{
		//Set the array of column alignments
		$this->aligns=$a;
	}

	#Esta sección de código es el algoritmo que se utiliza para la creación y distribución de la tabla de partidas de la factura (Inicio)
	function Row($data)
	{
		//Calculate the height of the row
		$nb=0;
		for($i=0;$i<count($data);$i++)
			$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
		$h=5.1*$nb;#el valor de 5.1 es para el height del rectangulo

		$this->SetFont('Arial','', 8); #Esto no es parte del algoritmo original, fue agregado

		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row

		///Si la pagina esta por llenarse se vuelven a imprimir los encabezados
		if((int) $this->GetY() > 255){
			$this->AddPage();
			// $this->_pageReceptor($this->GetX(), $this->GetY());
			$this->Ln(5);
			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(16,120,179);
			$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');

			//Cabeceras de tabla de partidas
			$this->SetTextColor(255,255,255);
			$this->Cell(23,7,"ClaveProdServ",0,0,'C');
			$this->Cell(12,7,"Cant.",0,0,'C');
			$this->Cell(15,7,"Unidad",0,0,'C');
			$this->Cell(94,7,utf8_decode("Descripción"),0,0,'C');
			$this->Cell(23,7,"Precio Unitario",0,0,'C');
			$this->Cell(23,7,"Importe",0,0,'C');
			$this->Ln();
			$this->SetTextColor(0,0,0);
		}

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

	function CheckPageBreak($h)
	{
		//If the height h would cause an overflow, add a new page immediately
		if($this->GetY()+$h>$this->PageBreakTrigger)
			$this->AddPage($this->CurOrientation);
	}

	function NbLines($w,$txt)
	{
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
	function RoundedRect($x, $y, $w, $h, $r, $style = '', $angle = '1234')
    {
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
 
    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
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

        //echo "<pre>";
		//	print_r($conf);
		//echo "</pre>";
        
        //if ($this->print_header){

	        $object = new Facture($db);
	        $object->fetch($id);
	        $logo_valido = 0;
	        $logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
	        if ($mysoc->logo)
	        {
	            if (is_readable($logo))
	            {
	                $posx=10;
	                $posy=10;
	                $width=40;
	                $height=15;
	                $this->Image($logo, $posx, $posy, $width, $height);    // width=0 (auto)
	                $logo_valido = 1;
	            }
	        }
	        else
	        {
	         //    $text=$mysoc->name;
	         //    $this->SetFont('Arial','B',13);
	         //    $this->SetY(15);
		        // $this->Cell(80,4,strtoupper($text),0,0,'L');
		        // $this->SetY(10);
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
                    $tipo_doc = utf8_decode("Nota de CrÉdito");
                }
                if($res->tipo_document==5){
                    $tipo_doc = "Factura de Fletes";
                }
                if($res->tipo_document==6){
                    $tipo_doc = "Factura RIF";
                }
            }else{
                $tipo_doc="Factura";
            }

            $this->SetFont('helvetica','B',7);
            $this->SetX(139);
            $this->Cell(60,4, strtoupper($tipo_doc.": ".$object->ref),"",1,'C');// LRT
            $this->SetX(139);
            $this->Cell(60,4,"FOLIO FISCAL (UUID)","",1,'C');// LR
            $this->SetFont('helvetica','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["uuid"],"",1,'C');// LR
            $this->SetFont('helvetica','B',7);
            $this->SetX(139);
            $this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL SAT","",1,'C');// LR
            $this->SetFont('helvetica','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["certificado"],"",1,'C');// LR
            $this->SetFont('helvetica','B',7);
            $this->SetX(139);
            $this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL EMISOR","",1,'C');// LR
            $this->SetFont('helvetica','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["certEmisor"],"",1,'C');// LR
            $this->SetFont('helvetica','B',7);
            $this->SetX(139);
            $this->Cell(60,4,utf8_decode("FECHA Y HORA DE CERTIFICACION"),"",1,'C');// LR
            $this->SetFont('helvetica','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["fechaTimbrado"],"",1,'C');// LR
            $this->SetFont('helvetica','B',7);
            $this->SetX(139);
            $this->Cell(60,4,utf8_decode("FECHA Y HORA DE EMISION DEL CFDI"),"",1,'C');// LR
            $this->SetFont('helvetica','',7);
            $this->SetX(139);
            $this->Cell(60,4, $this->data["fechaEmision"],"",1,'C');// LRB
            $this->SetX(139);
			$this->Cell(60,4,"",0,0,'L');
			
			#Obtener información de emisor (Inicio)
			$sql_direccion = 'SELECT rowid, emisor_calle, emisor_noext, emisor_noint, emisor_colonia, emisor_delompio, codigo_postal, pais, estado FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE emisor_rfc="'.$conf->global->MAIN_INFO_SIREN.'"';
			$r_direccion=$db->query($sql_direccion);
            
            while ($res_dir=$db->fetch_object($r_direccion)) {
            	// echo "<pre>";
            	// print_r($res_dir);
            	// echo "</pre>";
            	$calle = $res_dir->emisor_calle." ".$res_dir->emisor_noext." ".$res_dir->emisor_noint;
            	$col = $res_dir->emisor_colonia;

            	$tmp = explode(':', $res_dir->estado);
				$state_id = $tmp[0];
            	$estado = ($state_id == 1070) ? "Ciudad de ".getState($state_id, 2) : getState($state_id, 2);
            	$delompio = $res_dir->emisor_delompio;
            }
            #Obtener información de emisor (Fin)

            //Titulo de la Empresa
			$this->SetFont('helvetica','B',7);
			$this->SetXY(10, 26);
			$this->Cell(80,4,strtoupper($mysoc->name),0,0,'L'); //$mysoc->name

			//Forma de Pago
			$sql = 'SELECT accountancy_code FROM '.MAIN_DB_PREFIX.'c_paiement WHERE code="'.$object->mode_reglement_code.'"';
			//echo $sql."<br>";
			$reset=$db->query($sql);
			$res=$db->fetch_object($reset);
			
			$forma_pago = "99";
            if($res){
            	$forma_pago = $res->accountancy_code;
            }
			$this->SetXY(10, 30);
			// $this->Cell(80,4,strtoupper(utf8_decode($calle)),0,0,'L');
			$this->Cell(80,4,strtoupper("forma de pago: ").$forma_pago,0,0,'L');

			//Condicion de Pago
			$sql = 'SELECT code, libelle AS label FROM '.MAIN_DB_PREFIX.'c_payment_term WHERE code="'.$object->cond_reglement_code.'"';
			//echo $sql."<br>";
			$reset=$db->query($sql);
            $res=$db->fetch_object($reset);
            $cond_pago = ($langs->trans("PaymentConditionShort".$res->code)!=("PaymentConditionShort".$res->code)?$langs->trans("PaymentConditionShort".$res->code):($res->label!='-'?$res->label:''));

			$this->SetXY(10, 34);
			// $this->Cell(80,4,strtoupper(utf8_decode($col)),0,0,'L');
			$this->Cell(80,4,strtoupper(utf8_decode("CondiciÓn de Pago: ".$cond_pago)),0,0,'L');

			//Direccion Empresa
			$this->SetXY(10, 38);
			$this->Cell(80,4,strtoupper(utf8_decode($calle." ".$col)),0,0,'L');

			//Municipio y Estado
			$this->SetXY(10, 42);
			$this->Cell(80,4, strtoupper(utf8_decode($delompio.", ".$estado)),0,0,'L');

			//CP
			$this->SetXY(10, 46);
			$this->Cell(80,4,strtoupper("C.P. ".utf8_decode($conf->global->MAIN_INFO_SOCIETE_ZIP)),0,0,'L'); //cliente - 1
			
			//Regimen Fiscal
			$this->SetXY(10, 50);
			$this->Cell(80,4,strtoupper(utf8_decode("RÉgimen Fiscal: ")).$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE,0,0,'L'); //cliente - 1
			$this->data['rfc_emisor'] = $conf->global->MAIN_INFO_SIREN;			

            $this->SetXY(75, 26);
			$this->Cell(80,4,$conf->global->MAIN_INFO_SIREN,0,0,'L');

			$this->SetXY(75, 30);
			$this->Cell(80,4,strtoupper(utf8_decode("mÉtodo de pago: ")).$object->array_options['options_formpagcfdi'],0,0,'L');
			
			$this->SetXY(75,34);
			$this->Cell(80,4,"",0,0,'L');
						
			$this->SetXY(75,38);
			$this->Cell(80,4, "",0,0,'L');
			
			$this->SetXY(75,42);
			$this->Cell(80,4,strtoupper(utf8_decode("Lugar de expediciÓn: ")).$conf->global->MAIN_INFO_SOCIETE_ZIP,0,0,'L'); //cliente - 1
			$this->SetXY(75,46);

			$sql = 'SELECT divisa FROM '.MAIN_DB_PREFIX.'cfdimx WHERE fk_facture='.$id;
			$reset=$db->query($sql);
			if ($db->num_rows($reset) > 0) {
				$res=$db->fetch_object($reset);
	            $moneda = $res->divisa;
			}
			else {
				$moneda = "MXN";
			}
			$this->Cell(2,4,strtoupper(utf8_decode("Moneda: ")).$moneda,0,0,'L');
			
			//Tipo de cambio
			if ($object->array_options['options_tipodecambiocfdi'] != 0) { //tipodecambiocfdi
				$this->SetXY(75,50);
				$vm = $object->array_options['options_tipodecambiocfdi'];
				$this->Cell(80,4,strtoupper("Tipo de cambio: ").$vm,0,0,'L');
				//$this->Ln();
			}

            $this->SetDrawColor(16,120,179);
			$this->SetFillColor(194,229,243);

			//Dibujar recuadro de cabecera
			//Tipo de cambio
			if ($object->array_options['options_tipodecambiocfdi'] > 0) { //tipodecambiocfdi
				$this->RoundedRect(138,$this->GetY()-43,62,45, 5,'D'); //$x,$y,$w,$h
			}else{
				$this->RoundedRect(138,$this->GetY()-39,62,45, 5,'D'); //$x,$y,$w,$h
			}

            //Dibujar recuadro de info emisor
            //Tipo de cambio
			if ($object->array_options['options_tipodecambiocfdi'] > 0) { //tipodecambiocfdi
				$this->RoundedRect($this->GetX()-145,$this->GetY()-24,115,30,2,'D','1234');
            }else{
            	$this->RoundedRect($this->GetX()-67,$this->GetY()-21,115,30,2,'D','1234');
            }

            #Imprimir encabezados de tabla despues de encabezado del comprobante en cada página (Inicio)
            /*$this->SetFont('Arial','B', 7);
        	$this->SetY(55);
			$this->Cell(27,7,"ClaveProdServ",1,0,'C');
			$this->Cell(12,7,"Cant.",1,0,'C');
			$this->Cell(15,7,"Unidad",1,0,'C');
			$this->Cell(76,7,utf8_decode("Descripción"),1,0,'C');
			$this->Cell(30,7,"Precio Unitario",1,0,'C');
			$this->Cell(30,7,"Importe",1,0,'C');
			$this->Ln();
			$this->SetY(57);*/
			#Imprimir encabezados de tabla despues de encabezado del comprobante en cada página (Fin)
          
            $this->Ln(7);
        //}
    }
    
    ##cfdis relacionados
	function _pageCFDIRel($x, $y){

		global $conf, $db;

		//Arreglo con los nombres de tipo relacion
		$nombres_tipo_relacion = 
					array(
						"Notas de Crédito de Documentos Relacionados" => 1,
						"Notas de Débito de los Documentos Relacionados" => 2,
						"Devolución de Mercancías sobre Facturas o Traslados Previos" => 3,
						"Sustitución de los CFDI Previos" => 4,
						"Traslados de Mercancías Facturados Previamente" => 5,
						"Factura Generada por los Traslados Previos" => 6,
						"CFDI por Aplicación de Anticipo" => 7,
						"Facturas Generadas por Pagos en Parcialidades" => 8,
						"Factura Generada por Pagos Diferidos" => 9
					);

		$facid = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

		##Se Valida que la factura tenga un tipo de relacion
		$sql_tipo_rel = "SELECT cfdidoctiporelacion FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
		$res_sql_tipo_rel = $db->query($sql_tipo_rel);
		$valida_tipo_rel = $db->num_rows($res_sql_tipo_rel);

		##Se valida que la factura tenga cfdi relacionados
		$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE fk_facture = " .$facid;
		$res_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
		$valida_cfdi_rel = $db->num_rows($res_valida_cfdi_rel);

		if($valida_tipo_rel > 0 && $valida_cfdi_rel > 0){
			$sqlm = "SELECT cfdidoctiporelacion FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
			$resqlm=$db->query($sqlm);
			$objm = $db->fetch_object($resqlm);

			if($objm->cfdidoctiporelacion!="" && $objm->cfdidoctiporelacion!=NULL && $objm->cfdidoctiporelacion!=null){ 

				$this->SetY($this->GetY()+4);
				$antesMulticel = $this->GetY();

				//Dibujar recuadro
				$this->SetDrawColor(16,120,179);
				$this->SetFillColor(16,120,179);
				$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
				$this->SetTextColor(255,255,255);

				$this->SetFont('Arial','B',9);
				$this->SetXY($x, $this->GetY()+2);
				$this->Cell(75,4,"    CFDIS Relacionados",0,0,'L');
				$this->Ln();
				$this->SetTextColor(0,0,0);
				
				$this->SetFont('Arial','',8);
				$txt_cfdi_relacionados = "Tipo de Relación: ".$objm->cfdidoctiporelacion." ".array_search((int)$objm->cfdidoctiporelacion, $nombres_tipo_relacion)."\n";
				$txt_cfdi_relacionados .= "Lista de UUID Relacionados: \n";

				$contador = 1;
				while($cfdi_rel = $db->fetch_object($res_valida_cfdi_rel)){
					// $txt_cfdi_relacionados .= $cfdi_rel->uuid;

					if($contador < $valida_cfdi_rel){
						// $txt_cfdi_relacionados .= $cfdi_rel->uuid."\n";
						$txt_cfdi_relacionados .= $cfdi_rel->uuid."\n";
					}else{
						$txt_cfdi_relacionados .= $cfdi_rel->uuid;
					}
					$contador++;
				}
				
				$this->MultiCell(190,4,utf8_decode($txt_cfdi_relacionados),0, "L", false);
				$this->Ln();

				$despuesMulticel = $this->GetY();

			    $tam_cuadro = ($despuesMulticel - $antesMulticel)-4;
				//Dibujar recuadro
				$this->SetDrawColor(16,120,179);
				$this->SetFillColor(194,229,243);
				$this->RoundedRect($this->GetX(),$this->GetY()-$tam_cuadro+2,190,$tam_cuadro,2,'D','1234');
			}
		}
		// die;
	}

    // Función para mostrar el cuadro de datos del receptor
    function _pageReceptor($x, $y) {
	    
	    global $db, $conf, $mysoc;

	    $id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');
        $object = new Facture($db);
	    $object->fetch($id);

	    //Dibujar recuadro
		$this->SetDrawColor(16,120,179);
		$this->SetFillColor(16,120,179);
		$this->RoundedRect($this->GetX(),$this->GetY()+1,190,5,3,'DF','1234');
		$this->SetTextColor(255,255,255);

		$this->Ln();
		// $this->SetFont('Arial','B',9);

		$this->SetFont('helvetica','B',9);
		$this->SetXY($x, $this->GetY()-2);
		$this->Cell(75,4,"    Datos del Cliente",0,0,'L');
		$this->SetTextColor(0,0,0);
		// $this->Ln();


	    //Se busca la etiqueta relacionado con el usocfdi seleccionado
	    $etiqueta_uso_cfdi      = "SELECT * FROM llx_c_cfdimx_uso_cfdi WHERE code = '".$object->array_options['options_usocfdi']."'";
	    $res_etiqueta_uso_cfdi  = $db->query($etiqueta_uso_cfdi);

		//Se forma la etiqueta uso_cfdi
		$uso_cfdi = $object->array_options['options_usocfdi'];

        if($res_etiqueta_uso_cfdi){
        	$arreglo_uso_cfdi       = $db->fetch_object($res_etiqueta_uso_cfdi);
        	$uso_cfdi .= " ".$arreglo_uso_cfdi->label;
        }
      	
        //Se obtiene la direccion del Emisor
	    $sql = 'SELECT rowid, nom, address, zip, town, fk_departement, siren FROM '.MAIN_DB_PREFIX.'societe WHERE rowid='.$object->socid;
		//echo $sql."<br>";
		$reset=$db->query($sql);
        $res=$db->fetch_object($reset);

        $this->data['rfc_receptor'] = $res->siren;

        $dir_estado = getState($res->fk_departement,2);

        if ($object->type == 2) {
        	$tipoComprobante="E - Egreso"; //Nota de crédito
		}else{
			$tipoComprobante="I - Ingreso";//Factura
		}

		$antesMulticel = $this->GetY();
        $this->SetFont('Arial','',8);
	    $this->SetXY($x, $this->GetY()+4);

	    $receptor_nombre = $res->nom."\n".$res->siren;

	    $direccion = "";
	    if(!empty($res->address)){
	    	$direccion .= $res->address." ";
	    }

	    if(!empty($res->town)){
	    	$direccion .= $res->tow." ";
	    }

	    if($res->fk_departement != 0){
	    	$direccion .= $dir_estado." ";
	    }	    
	
		if($direccion != ""){
			$this->MultiCell(190,4,utf8_decode($receptor_nombre."\n".$direccion."\n"."Uso CFDI: ".$uso_cfdi." \nTipo CDFI: ".$tipoComprobante),0, "L", false);
		}else{
			$this->MultiCell(190,4,utf8_decode($receptor_nombre."\n"."Uso CFDI: ".$uso_cfdi." \nTipo CDFI: ".$tipoComprobante),0, "L", false);
		}

	    $despuesMulticel = $this->GetY();

	    $tam_cuadro = ($despuesMulticel - $antesMulticel) - 2;
		//Dibujar recuadro
		$this->SetDrawColor(16,120,179);
		$this->SetFillColor(194,229,243);
		$this->RoundedRect($this->GetX(),$this->GetY()-$tam_cuadro+2,190,$tam_cuadro,2,'D','1234');
	}

	// Función para mostrar el cuadro de observaciones del comprobante
	function _pageObservaciones($x, $y) {

		global $db, $conf, $mysoc;

		$id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');
        $object = new Facture($db);
	    $object->fetch($id);

	    $this->SetY($this->GetY());
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

		$this->MultiCell(190,4,utf8_decode($object->note_public),0, "L", false);
		$this->Ln();

		$despuesMulticel = $this->GetY();

	    $tam_cuadro = ($despuesMulticel - $antesMulticel)-4;
		//Dibujar recuadro
		$this->SetDrawColor(16,120,179);
		$this->SetFillColor(194,229,243);
		$this->RoundedRect($this->GetX(),$this->GetY()-$tam_cuadro+2,190,$tam_cuadro,2,'D','1234');
	}

    // Función para mostrar los totales del comprobante
    function _pageTotal($x, $y)
	{
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
		$this->SetFont('helvetica','B',9);
		$this->SetXY($x+150, $y);
	    $this->Cell(15,7,"Subtotal",0,0,'L');
	    $this->SetFont('helvetica','',9);
		$this->Cell(23,7, "$ ".number_format($factura_subtotal, 2),0,0,'R');

		#Dibujo de recuadro
		$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
		#IVA Total
		$this->SetXY($x+150, $y+7);
		$this->SetFont('helvetica','B',9);
		$this->Cell(15,7,"IVA 16%",0,0,'L');
		$this->SetFont('helvetica','',9);
		$this->Cell(23,7, "$ ".number_format($factura_iva, 2),0,0,'R');

		if ($object->remise > 0) {
			#Dibujo de recuadro para descuentos
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
			#Descuento
			$this->SetXY($x+150, $y+14);
			$this->SetFont('helvetica','B',9);
			$this->Cell(15,7,"Descuento",0,0,'L');
			$this->SetFont('helvetica','',9);
			$this->Cell(23,7, "$ ".number_format($object->remise, 2),0,0,'R');
		}

		#Retenciones IVA (Inicio)
		$sql_ret = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_retenciones WHERE fk_facture = '.$id.' AND impuesto="ISR"';
		//echo $sql_ret."<br>";
		$resql = $db->query($sql_ret);

		if ($db->num_rows($resql) > 0) {

			#Dibujo de recuadro
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');

			$suma_ret_iva = 0;
			while ($obj = $db->fetch_object($resql)) {
				$suma_ret_iva = $suma_ret_iva + floatval($obj->importe);
			}
			$this->SetXY($x+150, $this->GetY()+7);
			$this->SetFont('helvetica','B',9);
			$this->Cell(15,7,"Ret. IVA",0,0,'L');
			$this->SetFont('helvetica','',9);
			$this->Cell(23,7, "$ ".number_format($suma_ret_iva, 2),0,0,'R');
		}
		#Retenciones IVA (Fin)
		
		#Retenciones ISR (Inicio)
		$sql_ret = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_retenciones WHERE fk_facture = '.$id.' AND impuesto="IVA"';
		$resql = $db->query($sql_ret);

		if ($db->num_rows($resql) > 0) {

			#Dibujo de recuadro
			$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');

			$suma_ret_isr = 0;
			while ($obj = $db->fetch_object($resql)) {
				$suma_ret_isr = $suma_ret_isr + floatval($obj->importe);
			}
			$this->SetXY($x+150, $this->GetY()+7);
			$this->SetFont('helvetica','B',9);
			$this->Cell(15,7,"Ret. ISR",0,0,'L');
			$this->SetFont('helvetica','',9);
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
				$this->SetFont('helvetica','B',9);
				$this->Cell(15,7,"Ret. ".$obm->codigo,0,0,'L');
				$this->SetFont('helvetica','',9);
				$this->Cell(23,7, "$ ".number_format($obm->importe, 2),0,0,'R');
			}
		}
		#Retenciones locales (Fin)
		
		#Bloque ISH (Incio)
		$sql = 'SHOW COLUMNS FROM '.MAIN_DB_PREFIX.'product_extrafields LIKE "prodcfish"';
		$resql = $db->query($sql);
		$totalish = 0; $banish = 0;
		if( $db->num_rows($resql) > 0 ){

			if($conf->global->MAIN_MODULE_MULTICURRENCY) {
				$sql="SELECT a.fk_product,a.multicurrency_total_ht as total_ht,b.prodcfish,((b.prodcfish/100)*a.multicurrency_total_ht) as impish,c.ref,c.label FROM ".MAIN_DB_PREFIX."facturedet a, (SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, ".MAIN_DB_PREFIX."product c WHERE a.fk_facture=".$id." AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
			}
			else {
				$sql="SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label FROM ".MAIN_DB_PREFIX."facturedet a, (SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, ".MAIN_DB_PREFIX."product c WHERE a.fk_facture=".$id." AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
			}
			$resql = $db->query($sql);

			if($db->num_rows($resql) > 0) {
				
				#Dibujo de recuadro
				$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');

				while($res = $db->fetch_object($resql)){
					$totalish = $totalish + $res->impish;
				}
				$this->SetXY($x+150, $this->GetY()+7);
				$this->SetFont('helvetica','B',9);
				$this->Cell(15,7,"ISH",0,0,'L');
				$this->SetFont('helvetica','',9);
				$this->Cell(23,7, "$ ".number_format($totalish, 2),0,0,'R');
				$banish = 1;
			}
		}
		#Bloque ISH (Fin)

		#Dibujo de recuadro de Total
		$this->RoundedRect($posXrecuadro+148,$this->GetY()+8,42,5,2,'DF','1234');
		#Total
		$this->SetXY($x+150, $this->GetY()+7);
		$this->SetFont('helvetica','B',9);
		$this->Cell(15,7,"Total",0,0,'L');
		$this->SetFont('helvetica','',9);
		$this->Cell(23,7, "$ ".number_format($factura_total, 2),0,0,'R');
	}

	// Monto de la factura en letra
    function _pageNum2Letra($x, $y)
	{
		global $langs, $db, $conf;
		
		$id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

		$object = new Facture($db);
	    $object->fetch($id);

		// se modifica para utilizar el traductor de dolibarr
		$moneda = $this->data['moneda'];
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

		if($tipoDivisa=='USA'){$tipoDivisa='Dolares';}
		if($tipoDivisa=='USAS'){$tipoDivisa='Dolares';}

		$letras=utf8_decode(num2letras($factura_total,0,0).' '.$tipoDivisa);
		$letras_len = strlen($letras);
		$letras_substr = substr($letras, $letras_len-2,$letras_len);
		if($letras_substr == "SS") $letras = substr($letras, 0, $letras_len-1);

		$ultimo = substr(strrchr(number_format($factura_total,2), "."), 1 ); //recupero lo que este despues del decimal
		$letras = strtoupper($letras)." ".$ultimo."/100 ".($moneda == "MXN"? "MN":"ME");

		$contar_letras=strlen($letras);
		$fuente_letras = ($contar_letras>=60) ? 7 : 8;

		$this->SetFont('Arial','B',$fuente_letras);
		$this->SetXY(10,$this->GetY());
		$this->Cell(10,7,$letras,0,0,'L');
	}

	// Código QR y cadena
    function _pageQRCode()
	{
		global $conf, $db;
		
		$id = (GETPOST('facid') != "") ? GETPOST('facid') : GETPOST('id');

		$object = new Facture($db);
	    $object->fetch($id);
	    
		$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id='.$this->data["uuid"].'&re='.$this->data["rfc_emisor"].'&rr='.$this->data["rfc_receptor"].'&tt='.$object->total_ttc.'&fe='.substr($this->data["selloCFD"],-8);
		QRcode::png($data_cbb,$conf->facture->dir_output."/".strtoupper($object->ref)."/".$this->data["uuid"].".png");
		$this->CheckPageBreak(20);

		if((int)$this->GetY() > 220){
			$this->AddPage();
		}

		$this->Image($conf->facture->dir_output."/".strtoupper($object->ref)."/".$this->data["uuid"].".png",162, $this->GetY()+9,42);

		/*print 'x: '.$this->GetX().'<br>';
		print 'y: '.$this->GetY().'<br>';
		print '<pre>'; print_r($object->array_options['options_tipodecambiocfdi']); print '</pre>';
		//if ($object->array_options['options_tipodecambiocfdi'] > 0) { //tipodecambiocfdi
		die;*/

		if((int)$this->GetX() == 20){
			$nueva_cc_x = $this->GetX()-10;
		}else{
			$nueva_cc_x = $this->GetX();
		}

		if ($object->array_options['options_tipodecambiocfdi'] > 0) { //tipodecambiocfdi
			$this->SetFont('Arial','',6);
			// $this->SetXY($this->GetX()-10, $this->GetY()+13);
			$this->SetXY($nueva_cc_x, $this->GetY()+13); //13
			$this->MultiCell(150,4,"\n".$this->data["selloCFD"],0, "L", false);
			$this->SetY($this->GetY()-16);

			$this->SetFont('Arial','B',6);
			// $this->SetY($this->GetY()+5);
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
			$this->SetY($this->GetY()-20);

			$this->SetFont('Arial','B',6);
			$this->Cell(20,4,utf8_decode("CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT"),0,0,'L');
			

			$this->SetY($this->GetY()+20);

			//Dibujar recuadro
			$this->SetDrawColor(16,140,260);
			$this->SetFillColor(194,229,243);
			$this->RoundedRect($this->GetX(),$this->GetY()-53,150,55,2,'D','1234');
		}else{
			$this->SetFont('Arial','',6);
			// $this->SetXY($this->GetX()-10, $this->GetY()+13);
			$this->SetXY($nueva_cc_x, $this->GetY()+13); //revisar
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
			$this->SetY($this->GetY()-20);
			$this->SetFont('Arial','B',6);
			$this->Cell(20,4,utf8_decode("CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT"),0,0,'L');
			$this->SetY($this->GetY()+20);

			//Dibujar recuadro
			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(194,229,243);
			$this->RoundedRect($this->GetX(),$this->GetY()-53,150,55,2,'D','1234');
		}		
		
		/*$this->SetFont('Arial','',6);
		$this->MultiCell(150,4,"\n".$this->data["coccds"],0, "L", false);
		$this->SetY($this->GetY()-20);
		$this->SetFont('Arial','B',6);
		$this->Cell(20,4,utf8_decode("CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT"),0,0,'L');
		$this->SetY($this->GetY()+20);

		//Dibujar recuadro
		$this->SetDrawColor(16,120,179);
		$this->SetFillColor(194,229,243);
		$this->RoundedRect($this->GetX(),$this->GetY()-53,150,55,2,'D','1234');*/
	}

    // Page footer
    function Footer()
	{
	    // Go to 1.5 cm from bottom
	    $this->SetY(-15);
	    // Select Arial italic 8
	    $this->SetFont('helvetica','I',6);
	    // Print current and total page numbers
	    $this->Cell(0,10,utf8_decode('Este documento es una representación gráfica de un CFDI - Página ').$this->PageNo().'/{nb}',0,0,'C');
	}
	#Funciones de armado de PDF (Fin)
}


$id = $_GET['facid'];
$cfdi_commit =1;


$pdf=new cfdiPDF();
$pdf->AddPage();
$pdf->AliasNbPages();
$pdf->SetFont('Arial','',14);
//Table with n rows and 6 columns
$pdf->SetWidths(array(23,12,15,94,23,23));
$pdf->SetAligns(array('C','C','C','L','R','R'));

$pdf->SetFont('arial','B',10);
//$pdf->SetFillColor(0,0,0);//Fondo verde de celda
$pdf->SetTextColor(0, 0, 0); //Letra color negro


$object = new Facture($db);
$object->fetch($id);

$pdf->SetY($pdf->GetY()+4);
$pdf->_pageReceptor($pdf->GetX(), $pdf->GetY());

$pdf->SetY($pdf->GetY()+2);
$pdf->_pageCFDIRel($pdf->GetX(), $pdf->GetY());

if ($object->note_public != "") {
	$pdf->SetY($pdf->GetY()+4);
	$pdf->_pageObservaciones($pdf->GetX(), $pdf->GetY());

	$pdf->SetY($pdf->GetY()+2);
}

$pdf->SetY($pdf->GetY()+2);

$pdf->SetFont('Arial','B', 7);

//Dibujar recuadro
$pdf->SetDrawColor(16,120,179);
$pdf->SetFillColor(16,120,179);
$pdf->RoundedRect($pdf->GetX(),$pdf->GetY()+1,190,5,3,'DF','1234');

//Cabeceras de tabla de partidas
$pdf->SetTextColor(255,255,255);
$pdf->Cell(23,7,"ClaveProdServ",0,0,'C');
$pdf->Cell(12,7,"Cant.",0,0,'C');
$pdf->Cell(15,7,"Unidad",0,0,'C');
$pdf->Cell(94,7,utf8_decode("Descripción"),0,0,'C');
$pdf->Cell(23,7,"Precio Unitario",0,0,'C');
$pdf->Cell(23,7,"Importe",0,0,'C');
$pdf->Ln();
$pdf->SetTextColor(0,0,0);

// Armado de tabla de partidas del comprobante
for($i=0;$i<sizeof($object->lines);$i++) {
	
	if ($object->lines[$i]->fk_product != "") {
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product_extrafields WHERE fk_object='.$object->lines[$i]->fk_product;
		//echo $sql."<br>";
		$r = $db->query($sql);
		$res = $db->fetch_object($r);
		$umed = $res->umed;
		$clave = $res->claveprodserv;
		// $desc = $object->lines[$i]->ref.' - '.$pdf->limpiar($object->lines[$i]->desc);
		$desc = utf8_decode($object->lines[$i]->ref.' - '.$object->lines[$i]->desc);
	}else {
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'facturedet_extrafields WHERE fk_object='.$object->lines[$i]->rowid;
		//echo $sql."<br>";
		$r = $db->query($sql);
		$res = $db->fetch_object($r);
		$umed = $res->umed;
		$clave = $res->claveprodserv;
		// $desc = strtolower($pdf->limpiar($object->lines[$i]->desc));
		$desc = utf8_decode(strtolower($object->lines[$i]->desc));
	}

	//Si esta activo multimoneda 
	if($conf->global->MAIN_MODULE_MULTICURRENCY == 1 && $pdf->data["moneda"] != "MXN") {
		$precio_unitario = $object->lines[$i]->multicurrency_subprice;
		$importe = $object->lines[$i]->multicurrency_total_ht;
	}else{
		$precio_unitario = $object->lines[$i]->subprice;
		$importe = $object->lines[$i]->total_ht;
	}

	$pdf->Row( array($clave,$object->lines[$i]->qty, $umed, ucwords(strtolower(trim($desc))), "$ ".number_format($precio_unitario, 2), "$ ".number_format($importe, 2) ) );
}

$pdf->_pageTotal($pdf->GetX(), $pdf->GetY());
$pdf->_pageNum2Letra($pdf->GetX(), $pdf->GetY());
$pdf->_pageQRCode();

$pdf->Output($conf->facture->dir_output."/".$object->ref."/".$pdf->data['uuid'].".pdf", 'F');

if ($route == "genera")  {
	header('Location: facture.php?facid='.$id.'&cfdi_commit='.$cfdi_commit.'');
}
else {
	print '<script>window.location.href="facture.php?facid='.$id.'&cfdi_commit='.$cfdi_commit.'";</script>';
}

?>