<?php
header('Content-Type: text/html; charset=utf-8');
define('FPDF_FONTPATH','font/');
require('lib/fpdf/fpdf.php');

class PDF extends FPDF {

	var $widths;
	var $aligns;
	var $prm;
	var $dolibarr_main_data_root;
	protected $leyendaCFDI;
	protected $print_header;
	protected $print_footer;

	function inicia_param( $prmsnd, $header="", $emisor="" ){
		$res=array();
		$res["header"]=$header;
		$res["emisor"]=$emisor;
		$res["otros"]=$prmsnd;
		$this->prm = $res;
		return $this->prm;
	}
	function inicia_dolibarr_main_data_root($param =""){
		$this->dolibarr_main_data_root = $param;
		return $this->dolibarr_main_data_root;
	}
	public function setPrintHeader($val=true) {
		$this->print_header = $val ? true : false;
	}
	
	public function setPrintFooter($val=true) {
		$this->print_footer = $val ? true : false;
	}
	public  function setLeyendaCFDI($val=true) {
		$this->leyendaCFDI = $val ? true : false;
	}
	
	public function limpiar($String){
		$String = str_replace(array('�','�','�','�','�','�'),"a",$String);
		$String = str_replace(array('�','�','�','�','�'),"A",$String);
		$String = str_replace(array('�','�','�','�'),"�",$String);
		$String = str_replace(array('�','�','�','�'),"�",$String);
		$String = str_replace(array('�','�','�','�'),"e",$String);
		$String = str_replace(array('�','�','�','�'),"E",$String);
		$String = str_replace(array('�','�','�','�','�','�'),"o",$String);
		$String = str_replace(array('�','�','�','�','�'),"O",$String);
		$String = str_replace(array('�','�','�','�'),"u",$String);
		$String = str_replace(array('�','�','�','�'),"U",$String);
		$String = str_replace(array('[','^','�','`','�','~',']'),"",$String);
		$String = str_replace("�","c",$String);
		$String = str_replace("�","C",$String);
		$String = str_replace("�","�",$String);
		//$String = str_replace("�","N",$String);
		$String = str_replace("�","Y",$String);
		$String = str_replace("�","y",$String);
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
		return $String;
	}

	//Cabecera de página
	function Header(){
		if ($this->print_header){
			Global $guion;
			$this->SetFont('Arial','B',12);

			/*
			 $this->MultiCell(192,5,strtoupper(utf8_decode($this->prm["parametros"]["emisorNombre"])),0,"C");
			$this->MultiCell(192,5,$this->prm["emisorRFC"],0,"C");
			*/

			$this->SetFont('Arial','B',7);

			$this->SetDrawColor(16,120,179);
			$this->SetFillColor(194,229,243);

			$sql="SELECT IFNULL(tipo_document,NULL) as tipo_document
			FROM ".MAIN_DB_PREFIX."cfdimx_type_document
			WHERE fk_facture=".$_REQUEST["facid"];
			global $db;
			$resp=$db->query($sql);
			$respp=$db->fetch_object($resp);
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			if($respp->tipo_document!=NULL){
				if($respp->tipo_document==1){
					//$this->Cell(80,4,'',0,0,'L');
					$tipo_doc="Factura";
				}
				if($respp->tipo_document==2){
					//$this->Cell(80,4,strtoupper('Recibo de Honorarios'),0,0,'L');
					$tipo_doc="Recibo de Honorarios";
				}
				if($respp->tipo_document==3){
					//$this->Cell(80,4,strtoupper('Recibo de Arrendamiento'),0,0,'L');
					$tipo_doc="Recibo de Arrendamiento";
				}
				if($respp->tipo_document==4){
					$tipo_doc="Nota de Credito";
					//print "Nota de Credito";
				}
				if($respp->tipo_document==5){
					$tipo_doc="Factura de Fletes";
					//print "Nota de Credito";
				}
				if($respp->tipo_document==6){
					$tipo_doc="Factura RIF";
				}
			}else{
				//$this->Cell(80,4,'',0,0,'L');
				$tipo_doc="Factura";
			}
			//$this->Cell(2,4,"",0,0,'C'); //enmedio
			if($this->prm["otros"]["orden_compra"]!=""){
				//$this->Cell(70,4,strtoupper("Orden de Compra:".$this->prm["otros"]["orden_compra"]),0,1,'C');
				$this->MultiCell(310,5,strtoupper("Orden de Compra:").$this->prm["otros"]["orden_compra"],0,"C");
			}/* else{
				$this->Cell(70,4,"",0,1,'C'); //factura - 2
			} */
			
			$pdf2=new PDF();
			$this->Cell(2,4,"",0,0,'L'); //inicio
			$this->Cell(45,4,"",0,0,'C'); //
			//$this->Cell(80,4,strtoupper($pdf2->limpiar($this->prm["emisor"]["nombre"])),0,0,'L');
			$this->Cell(80,4,"",0,0,'L'); //
			$this->Cell(2,4,"",0,0,'C'); //enmedio

			switch ($this->prm["header"]["tipoDeComprobante"]) {
				case "E":
					$doctype = "NOTA DE CREDITO";
					break;
				case "I":
					$doctype = "FACTURA";
					break;
				default:
					$doctype = "FACTURA";
					break;
			}
			if($respp->tipo_document==2){
				$doctype="Recibo de Honorarios";
			}
			if($respp->tipo_document==3){
				$doctype="Recibo de Arrendamiento";
			}
			if($respp->tipo_document==5){
				$doctype="Factura de Fletes";
				$doctype="Factura";
			}
			if($respp->tipo_document==4 && $doctype=="NOTA DE CREDITO"){
				$doctype="Nota de Credito";
			}
			if($respp->tipo_document==6){
				$doctype="Factura RIF";
			}
			$this->Cell(60,4,strtoupper($doctype).": ".strtoupper($this->prm["header"]["serie"]).$guion.$this->prm["header"]["folio"],"LRT",1,'C');

			if($respp->tipo_document==4){
				$sqm="SELECT fk_facture_source FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$_REQUEST["facid"];
				$resp2=$db->query($sqm);
				$respp2=$db->fetch_object($resp2);
				$sqm="SELECT ref FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$respp2->fk_facture_source;
				$resp2=$db->query($sqm);
				$respp2=$db->fetch_object($resp2);
				$this->Cell(2,4,"",0,0,'L');
				$this->Cell(45,4,"",0,0,'C'); //inicio
				//$this->Cell(80,4,$this->prm["emisor"]["emisorRFC"],0,0,'L');
				$this->Cell(80,4,"",0,0,'L');
				$this->Cell(2,4,"",0,0,'C'); //enmedio
				$this->Cell(60,4,strtoupper("Corresponde a la factura: ").$respp2->ref,"LR",1,'C'); //factura - 2
			}
			/**********ROW************/
			$this->Cell(2,4,"",0,0,'L'); 
			$this->Cell(45,4,"",0,0,'C'); //inicio
			//$this->Cell(80,4,$this->prm["emisor"]["emisorRFC"],0,0,'L');
			$this->Cell(80,4,"",0,0,'L');
			$this->Cell(2,4,"",0,0,'C'); //enmedio
			$this->Cell(60,4,"FOLIO FISCAL (UUID)","LR",1,'C'); //factura - 2

			/**********ROW************/

			$this->Cell(2,4,"",0,0,'L'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			//$this->Cell(80,4,strtoupper($pdf2->limpiar(utf8_decode($this->prm["emisor"]["calle"]." ".$this->prm["emisor"]["noExterior"]." ".$this->prm["emisor"]["noInterior"]))),0,0,'L');
			$this->Cell(80,4,"",0,0,'L');
			$this->Cell(2,4,"",0,0,'C'); //enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["uuid"],"LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->Cell(2,4,"",0,0,'L');
			$this->SetFont('Arial','B',7);
			$this->Cell(45,4,"",0,0,'C'); //inicio
			//$this->Cell(80,4,strtoupper("Colonia ".$pdf2->limpiar(utf8_decode($this->prm["emisor"]["colonia"]))),0,0,'L'); //cliente - 1
			$this->Cell(80,4,"",0,0,'L');
			$this->Cell(2,4,"",0,0,'C'); //enmedio
			$this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL SAT","LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->Cell(2,4,"",0,0,'L');
			$this->Cell(45,4,"",0,0,'C'); //inicio
			//$this->Cell(80,4,strtoupper($pdf2->limpiar(utf8_decode($this->prm["emisor"]["municipio"].", ".utf8_encode($this->prm["emisor"]["estado"])))),0,0,'L'); //cliente - 1
			$this->Cell(80,4,"",0,0,'L');
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["certificado"],"LR",1,'C'); //factura - 2

			/**********ROW************/

			$this->SetFont('Arial','B',7);
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(2,4,strtoupper($pdf2->limpiar($this->prm["emisor"]["nombre"])),0,0,'L');
			$this->Cell(45,4,"",0,0,'C');
			// $this->Cell(80,4,"",0,0,'L');
			$this->Cell(80,4,$this->prm["emisor"]["emisorRFC"],0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL EMISOR","LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->Cell(2,4,strtoupper(((strtoupper("Forma de Pago: ".$this->prm["header"]["formaDePago"])))),0,0,'L');
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			// $this->Cell(80,4,strtoupper(((strtoupper("Forma de Pago: ".$this->prm["header"]["formaDePago"])))),0,0,'L');
			$this->Cell(80,4,$pdf2->limpiar(html_entity_decode(strtoupper(utf8_decode("Metodo de pago: ").$pdf2->limpiar($this->prm["header"]["metodoDePago"])))),0,0,'L');
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["certEmisor"],"LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->SetFont('Arial','B',7);
			$this->Cell(2,4, $pdf2->limpiar((strtoupper(utf8_decode("Condicion de pago: ").$pdf2->limpiar(utf8_decode($this->prm["header"]["condicionesDePago"]))))),0,0,'L'); //cliente - 1
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(80,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,utf8_decode("FECHA Y HORA DE CERTIFICACION"),"LR",1,'C'); //factura - 2


			$this->SetFont('Arial','B',7);
			$domicilio_emisor_pdf = strtoupper($pdf2->limpiar(utf8_decode($this->prm["emisor"]["calle"]." , ".$this->prm["emisor"]["colonia"])));
			$domicilio_emisor_pdf .= strtoupper($pdf2->limpiar(utf8_decode($this->prm["emisor"]["noExterior"]."  ".$this->prm["emisor"]["noInterior"])));

			$this->Cell(82,4,$domicilio_emisor_pdf,0,0,'L');
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(2,4,"",0,0,'C');//enmedio
			//$this->Cell(80,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["fechaTimbrado"],"LR",1,'C'); //factura - 2

			/**********ROW************/
			// $this->Cell(2,4, "<>",0,0,'L'); //cliente - 1
			// //$this->Cell(37,4,"",0,0,'C'); //inicio
			// $this->Cell(45,4,"",0,0,'C');
			// $this->Cell(80,4,$pdf2->limpiar((strtoupper(utf8_decode("Condicion de pago: ").$pdf2->limpiar(utf8_decode($this->prm["header"]["condicionesDePago"]))))),0,0,'L'); //cliente - 1
			// $this->Cell(2,4,"",0,0,'C');//enmedio
			// $this->SetFont('Arial','',7);
			// $this->Cell(60,4,$this->prm["otros"]["fechaTimbrado"],"LR",1,'C'); //factura - 2

			/**********ROW************/

			$this->SetFont('Arial','B',7);
			$this->Cell(2,4,strtoupper($pdf2->limpiar(($this->prm["emisor"]["municipio"].", ".($this->prm["emisor"]["estado"])))),0,0,'L'); //cliente - 1
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			//$this->Cell(80,4,$pdf2->limpiar(html_entity_decode(strtoupper(utf8_decode("Metodo de pago: ").$pdf2->limpiar($this->prm["header"]["metodoDePago"])))),0,0,'L');
			$this->Cell(80,4,strtoupper(utf8_decode("Lugar de expedicion: ")).strtoupper($pdf2->limpiar($this->prm["header"]["lugarExpedicion"])),0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,utf8_decode("FECHA Y HORA DE EMISION DEL CFDI"),"LR",1,'C'); //factura - 2

			/**********ROW************/

			if($this->prm["header"]["numCtaPago"]!=""){
				$noCta="NUM CUENTA DE PAGO:".$this->prm["header"]["numCtaPago"];
			}else{ $noCta="";
			}
			//print '<pre>'; print_r($this->prm); print '</pre>'; die;
			$this->Cell(2,4,strtoupper($this->prm["emisor"]["pais"])." , C.P. ".$this->prm["emisor"]["codigoPostal"],0,0,'L'); //cliente - 1
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(80,4,$noCta,0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["fechaEmision"],"LRB",1,'C'); //factura - 2

			/**********ROW************/

			$this->SetFont('Arial','B',7);
			$this->Cell(2,4,strtoupper(utf8_decode("Moneda: ".$this->prm["header"]["moneda"])),0,0,'L');
			$this->Cell(45,4,"",0,0,'C'); //inicio
			$this->Cell(80,4,strtoupper(utf8_decode("Regimen Fiscal: "). $pdf2->limpiar($this->prm["emisor"]["emisorRegimen"])),0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,"",0,0,'L'); //factura - 2

			if($this->prm["header"]["tipoCambio"]!=NULL && $this->prm["header"]["tipoCambio"]!=null && $this->prm["header"]["tipoCambio"]!=""){
				$this->Ln();
				$this->SetFont('Arial','B',7);
				$this->Cell(2,4,strtoupper("Tipo de Cambio:".$this->prm["header"]["tipoCambio"]),0,0,'L');
				$this->Cell(45,4,"",0,0,'C'); //inicio
				$this->Cell(80,4,"",0,0,'L'); //cliente - 1
				$this->Cell(2,4,"",0,0,'C');//enmedio
				$this->Cell(60,4,"",0,0,'L'); //factura - 2
			}


			//logo hace refencia al Logo de la empresa no cuadriculado
			if( $this->prm["otros"]["logo"] != ""){
				if($_SESSION['dol_entity'] == 1){
					$this->Image($this->dolibarr_main_data_root.'/mycompany/logos/thumbs/'.$this->prm["otros"]["logo"],6,10,60);
				}else{
					$this->Image($this->dolibarr_main_data_root.'/'.$_SESSION['dol_entity'].'/mycompany/logos/thumbs/'.$this->prm["otros"]["logo"],30,6,20);
				}
			}else{
				//logo hace refencia al Logo de la empresa cuadriculado
				if( $this->prm["otros"]["logotipo"] != ""){
					if($_SESSION['dol_entity'] == 1){
						$this->Image($this->dolibarr_main_data_root.'/mycompany/logos/thumbs/'.$this->prm["otros"]["logotipo"],6,10,60);
					}else{
						$this->Image($this->dolibarr_main_data_root.'/'.$_SESSION['dol_entity'].'/mycompany/logos/thumbs/'.$this->prm["otros"]["logotipo"],30,6,20);
					}
				}
			}


			$this->Ln(5);
		}
	}

	//Pie de página
	function Footer()
	{
		if ($this->print_footer){
			//Posición: a 1,5 cm del final
			$this->SetY(-15);
			//Arial italic 8
			$this->SetFont('Arial','I',8);
			//Número de página
			if($this->leyendaCFDI)$this->Cell(0,5,utf8_decode('Este documento es una representacion impresa de un CFDI'),0,0,'C');
			$this->Ln();
			$this->Cell(0,5,utf8_decode('Pagina '.$this->PageNo().'/{nb}'),0,0,'C');
		}
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

	function Row($data, $rect=1, $mcBorder=0, $textAlign="")
	{

	        //$rect="";
	        //$mcBorder="";
	        //$textAlign="";

		//Calculate the height of the row
		$nb=0;
                $cl=count($data);
		for($i=0;$i<$cl;$i++)
			$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));//PDF_MC_Table
		$h=6*$nb;


                //Issue a page break first if needed
                $this->CheckPageBreak($h,$cl);
		//Draw the cells of the row
		for($i=0;$i<=count($data);$i++)
		{
			$w=$this->widths[$i];
			if($textAlign == ""){
			    if($i==3){
				$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			    }else if( $i<2 ){
				$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'C';
			    }else{
				$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'R';
			    }
			}else{
			    $a=$textAlign;
			}
			//Save the current position
                        // $a: contiene alineación de texto
                        // $x: punto de anclaje de la coordena horizontal
                        // $y: punto de anclaje de la coordena vertical
                        // $w: ancho de la celda
                        // $h: altura de la celda

			$x=$this->GetX();
			$y=$this->GetY();
			//Draw the border

			if($rect == 1){
			//Issue a page break first if needed
    		             $this->CheckPageBreak($h);
			     $this->Rect($x, $y, $w, $h);
			}else{

			    $this->Line($x, $y, $x, $y+$h);
                            if($y < 60) $this->Line($x, $y, $x+$w, $y);

			}
			//Print the text
			if($i<6)$this->MultiCell($w, 6,$data[$i], $mcBorder, $a, $fill);
			//Put the position to the right of the cell

			$this->SetXY($x+$w,$y);
		}

		//Go to the next line
		$this->Ln($h);
	}

	function CheckPageBreak($h)
	{
		//If the height h would cause an overflow, add a new page immediately
		if($this->GetY()+$h>$this->PageBreakTrigger){
                  $array = $this->widths;
      /*
                      for($=0; $i<count($array); $i++){
                        $w = $w+$this->widths[$i];
                      }
      */
                        $w = 20+20+90+30+30;//ancho de la tabla


                  $this->Line($this->GetX(), $this->GetY(), $this->GetX()+$w, $this->GetY());

                  $this->AddPage($this->CurOrientation);
                }
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


function fixRow($texto,$long=80, $nr = 10){
	//$cadena = CORTAR($text,$nr);

	$mitexto=explode(" ",trim($texto));
	$textonuevo=array();
	foreach($mitexto as $k=>$txt){
		if (strlen($txt)>$nr){
			$txt=wordwrap($txt, $nr, " ", 1);
		}
        $cadena[]=$txt;
	}
	if(count($cadena) == 1){
	   $cadena = explode(" ",$cadena[0]);
	}

	$conactena="";
	$i=0;
	$num = count($cadena);
	$limite = 0;

	for($j=0; $j<$num; $j++){

		if((isset($cadena[$i])) and ($cadena[$i]!="")){
			while($limite < $long){
				if(!isset($cadena[$i]))break;
				if($cadena[$i]=="")break;
				 $conactena = $conactena." ".$cadena[$i];
				$limite = strlen($conactena);

			  $i++;
			}
		}

		$limite = 0;
		$textoN[$j] = $conactena;
		$conactena="";
	}

    return $textoN;
}
//************* cortar texto fin*****************

}

$sql= "SELECT id, libelle, module FROM ".MAIN_DB_PREFIX ."rights_def";
$resql=$db->query($sql);
if ($resql){
	$num_fact = $db->num_rows($resql);
	$i=0;
	if($num_fact){
		while ($i < $num_fact){
			$obj = $db->fetch_object($resql);
			$datos[$i]=array($obj->id, $obj->libelle, $obj->module);
			$i++;
		}
	}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();

$pdf->setPrintHeader();
$pdf->setLeyendaCFDI();
$pdf->setPrintFooter();
$pdf->inicia_param( $prmsnd, $header, $emisor );
$pdf->inicia_dolibarr_main_data_root($dolibarr_main_data_root);

$pdf->SetDrawColor(16,120,179);
$pdf->SetFillColor(194,229,243);

$pdf->AliasNbPages();
$pdf->AddPage();

$sql2="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion,tipocambio,certificadoorigen,subdivision,totalusd FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero WHERE fk_facture=".$facid;
$reqs2=$db->query($sql2);
$numr2=$db->num_rows($reqs2);
if($numr2>0){
	$rsn2=$db->fetch_object($reqs2);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(190,4,"COMERCIO EXTERIOR",1,0,'C'); //cliente
	$pdf->Ln();
	$pdf2=new PDF();
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(63.33,4,strtoupper($pdf2->limpiar("Tipo de operacion: ".$rsn2->tipo_operacion)),1,0,'L'); 
	$pdf->Cell(63.33,4,strtoupper($pdf2->limpiar("Clave de pedimento: ".$rsn2->clv_pedimento)),1,0,'L'); 
	$pdf->Cell(63.33,4,strtoupper($pdf2->limpiar("Nupero de Exportador: ".$rsn2->no_exportador)),1,0,'L');
	$pdf->Ln();
	$pdf->Cell(63.33,4,strtoupper($pdf2->limpiar("Incoterm: ".$rsn2->incoterm)),1,0,'L');
	$pdf->Cell(63.33,4,strtoupper($pdf2->limpiar("Certificado Origen: ".$rsn2->certificadoorigen)),1,0,'L');
	$pdf->Cell(63.33,4,strtoupper($pdf2->limpiar("Subdivision: ".$rsn2->subdivision)),1,0,'L');
	$pdf->Ln();
	$pdf->Cell(190,4,strtoupper($pdf2->limpiar("Observaciones: ".$rsn2->observaciones)),1,0,'L');
	$pdf->Ln();
	$pdf->Cell(190,4,strtoupper($pdf2->limpiar("Numero de identificacion del receptor: ".$rsn2->num_identificacion)),1,0,'L');
	$pdf->Ln(7);
}

$pdf->SetFont('Arial','B',8);
$pdf->Cell(190,4,"DATOS DEL RECEPTOR",1,0,'C'); //cliente
$pdf->Ln();
$pdf2=new PDF();
$pdf->SetFont('Arial','',8);

$nombre_receptor_pdf = strtoupper($pdf2->limpiar($receptor["nombre"]))."    ".$receptor["rfc"];

$pdf->Cell(190,4,$nombre_receptor_pdf,"LR",0,'L'); //cliente
$pdf->Ln();

// $pdf->Cell(190,4,$receptor["rfc"],"LR",0,'L'); //cliente
// $pdf->Ln();

$domicilio_receptor = "";

if($receptor["calle"] != ""){
	$domicilio_receptor .= $receptor["calle"]." ";
}

if($receptor["noExterior"] != ""){
	$domicilio_receptor .= $receptor["noExterior"]." ";
}

if($receptor["noInterior"] != ""){
	$domicilio_receptor .= $receptor["noInterior"]." ";
}

if($receptor["colonia"] != ""){
	$domicilio_receptor .= "Colonia: ".$receptor["colonia"]." ";
}

if($receptor["municipio"] != ""){
	$domicilio_receptor .= $receptor["municipio"]."  ";
}

if($receptor["estado"] != ""){
	$domicilio_receptor .= $receptor["estado"]."  ";
}

if($receptor["codigoPostal"] != ""){
	$domicilio_receptor .= "C.P. ".$receptor["codigoPostal"];
}
// print '<pre>'; print_r($receptor); print '</pre>'; die;
$pdf->Cell(190,4,strtoupper($pdf2->limpiar(utf8_decode($domicilio_receptor))),"LR",0,'L'); //domicilio de cliente
$pdf->Ln();

if($receptor["numRegIdTrib"]!="" && $receptor["numRegIdTrib"]!=null){
	$pdf->Cell(190,4,strtoupper("No. registro identidad fiscal:".$receptor["numRegIdTrib"]) ,"LR",0,'L'); //cliente
	$pdf->Ln();
}

$pdf->Cell(190,4,"Uso CFDI: ".$receptor["usoCFDI"],"LR",0,'L'); //cliente
$pdf->Ln();

$pdf->Cell(190,4,"","LBR",0,'L'); //cliente
$pdf->Ln(7);

if( $observaciones!="" ){
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(190,4,"OBSERVACIONES",1,0,'C'); //cliente
	$pdf->Ln();
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(190,4,utf8_decode($observaciones),"LRB","L");
	$pdf->Ln(5);
}

##Se Valida que la factura tenga un tipo de relacion
$sql_tipo_rel = "SELECT cfdidoctiporelacion FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
$res_sql_tipo_rel = $db->query($sql_tipo_rel);
$valida_tipo_rel = $db->num_rows($res_sql_tipo_rel);

##Se valida que la factura tenga cfdi relacionados
$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE fk_facture = " .$facid;
$res_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
$valida_cfdi_rel = $db->num_rows($res_valida_cfdi_rel);

//if( $existe_1 > 0 && $existe_2>0){
if($valida_tipo_rel > 0 && $valida_cfdi_rel > 0){
	$sqlm = "SELECT cfdidoctiporelacion FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
	$resqlm=$db->query($sqlm);
	$objm = $db->fetch_object($resqlm);

	if($objm->cfdidoctiporelacion!="" && $objm->cfdidoctiporelacion!=NULL && $objm->cfdidoctiporelacion!=null){ 

		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(190,4,"CFDI RELACIONADO",1,0,'C'); //cliente
		$pdf->Ln();
		$pdf->Cell(40,7,"Tipo de relacion",1,0,'C');
		$pdf->Cell(150,7,"UUID",1,0,'C');
		$pdf->Ln();

		$contador = 0;
		while($cfdi_rel = $db->fetch_object($res_valida_cfdi_rel)){
			
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(40,7,$objm->cfdidoctiporelacion,1,0,'C');
			$pdf->Cell(150,7,$cfdi_rel->uuid,1,0,'C');
			$pdf->Ln();
		}
		$pdf->Ln(5);
	}
}



// $sqm1 = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'cfdidoctiporelacion'";
// $resqlm1=$db->query($sqm1);
// $existe_1 = $db->num_rows($resqlm1);
// $sqm2 = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'cfdidocuuid'";
// $resqlm2=$db->query($sqm2);
// $existe_2 = $db->num_rows($resqlm2);
// if( $existe_1 > 0 && $existe_2>0){
// 	$sqlm = "SELECT cfdidoctiporelacion,cfdidocuuid FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object = " .$facid;
// 	$resqlm=$db->query($sqlm);
// 	$objm = $db->fetch_object($resqlm);
// 	if($objm->cfdidoctiporelacion!="" && $objm->cfdidoctiporelacion!=NULL && $objm->cfdidoctiporelacion!=null && $objm->cfdidocuuid!="" && $objm->cfdidocuuid!=NULL && $objm->cfdidocuuid!=null){ 
// 		$pdf->SetFont('Arial','B',8);
// 		$pdf->Cell(190,4,"CFDI RELACIONADO",1,0,'C'); //cliente
// 		$pdf->Ln();
// 		$pdf->SetFont('Arial','',8);
// 		$pdf->MultiCell(190,4,"Tipo de relacion: ".$objm->cfdidoctiporelacion,"LR","L");
// 		$pdf->MultiCell(190,4,"UUID: ".$objm->cfdidocuuid,"LRB","L");
// 		$pdf->Ln(5);
// 	}
// }


$pdf->SetFont('Arial','B',9);

$pdf->Cell(27,7,"ClaveProdServ",1,0,'C');
$pdf->Cell(18,7,"Cantidad",1,0,'C');
$pdf->Cell(15,7,"Unidad",1,0,'C');
$pdf->Cell(70,7,"Descripci�n",1,0,'C');
$pdf->Cell(30,7,"Precio Unitario",1,0,'C');
$pdf->Cell(30,7,"Importe",1,0,'C');
$pdf->Ln();


$pdf->SetFont('Arial','',9);
$pdf->SetWidths(array(27,18,15,70,30,30));
srand(microtime()*1000000);

//print '<pre>'; print_r($conceptos); print '</pre>'; die;

for($i=0;$i<count($conceptos);$i++) {
	if($conceptos[$i]["cuentaPredial"]!=NULL){
		$conceptos2[$i]["descripcion"]=$conceptos2[$i]["descripcion"]." \nCuenta Predial: ".$conceptos[$i]["cuentaPredial"];
	}
      $conc[$i] = utf8_decode($conceptos2[$i]["descripcion"]);
      $concl=$conc[$i];
        //calcular vueltas de carro
    $lineas = explode("\r\n", $conc[$i]);// si es windows
    if(count($lineas) != 0) $lineas = explode("\n", $conc[$i]);// si es unix

    if(strlen($lineas[$i])>100){  // si es demasiado larga se cortara y se metera en un arreglo
        $textoN = $pdf->fixRow($concl, 39, 13); //$textoN = $pdf->fixRow($lineas[$i], 39, 13);
        if(count($textoN)>0){
		    for($k=0; $k<count($textoN); $k++){
		    	dol_syslog("AQUI:: ". $textoN[$k]);
		        if($textoN[$k] != "") {
                    $lineas[$k] = $textoN[$k];
                        }
		    }
		}
    }

    if( count($lineas)<10){// checamos el numero de indices
        $pdf->Row(array(
        	$conceptos2[$i]["claveProdServ"],
		    $conceptos2[$i]["cantidad"],
		    $conceptos2[$i]["unidad"],
		    $conc[$i],
		    "$ ".number_format($conceptos2[$i]["valorUnitario"],$cfdi_decimal),
		    "$ ".number_format($conceptos2[$i]["importe"],$cfdi_decimal)
	          ));
     }else{
     //
		for($j=0; $j<count($lineas); $j++){
			 if(count($lineas)>1){
 				if($j==0){
// Agrega las linea inicial
				         $pdf->Row(array(
				         	$conceptos2[$i]["claveProdServ"],
						    $conceptos2[$i]["cantidad"],
						    $conceptos2[$i]["unidad"],
						    $lineas[$j],
						    "$ ".number_format($conceptos2[$i]["valorUnitario"],$cfdi_decimal),
						    "$ ".number_format($conceptos2[$i]["importe"],$cfdi_decimal)
					          ),0,"LTR","J");
				}elseif($j>0){
// Agrega las lineas intermedias
				        $pdf->Row(array(
						    "   ",
				        	"   ",
						    "   ",
						    $lineas[$j],
						    "   ",
						    "   "
					          ),0,"LR","J");
				}
			}
		}
// Agrega la ultima linea
				        $pdf->Row(array(
						    "   ",
				        	"   ",
						    "   ",
						    "   ",
						    "   ",
						    "   "
					          ),0,"LBR","J");
      //
       }
}



/*
$sql = "
SELECT f.rowid, f.description, d.amount_ttc
FROM  ".MAIN_DB_PREFIX."facturedet f,  ".MAIN_DB_PREFIX."societe_remise_except d
WHERE f.fk_facture = ".$_REQUEST["facid"]."
AND f.rowid = d.fk_facture_line";
$resql=$db->query($sql);
if ($resql){
	$res = $db->num_rows($resql);
	$i=0;
	if($res>0){
		while ($i < $res){
			$obj = $db->fetch_object($resql);
			$pdf->Cell(100,7,"Motivo del descuento: ".$obj->description,0,0,'L');
			$pdf->Cell(60,7,"Importe del descuento: ",0,0,'R');
			$pdf->Cell(30,7,"$".number_format($obj->amount_ttc,2),"LRB",0,'R');
			$pdf->Ln();
			$sum_desc+=$obj->amount_ttc;
			$i++;
		}
		$pdf->Cell(100,7,$obj->description,0,0,'L');
		$pdf->Cell(60,7,"Subtotal antes del descuento:  ",0,0,'R');
		$pdf->Cell(30,7,"$".number_format($sum_desc,2),"LRB",0,'R');
		$pdf->Ln();
	}
}
*/



if($descheader!=0){
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(100,7,"",0,0,'R');
	$pdf->Cell(60,7,"Subtotal: ",0,0,'R');
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(30,7,"$ ".number_format($factura_subtotal+$descheader,$cfdi_decimal),"LRB",0,'R');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(100,7,"",0,0,'R');
	$pdf->Cell(60,7,"Descuento: ",0,0,'R');
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(30,7,"$ ".number_format($descheader,$cfdi_decimal),"LRB",0,'R');
	$pdf->Ln();
}else{
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(100,7,"",0,0,'R');
	$pdf->Cell(60,7,"Subtotal: ",0,0,'R');
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(30,7,"$ ".number_format($factura_subtotal,$cfdi_decimal),"LRB",0,'R');
	$pdf->Ln();
}

$pdf->SetFont('Arial','B',9);
$pdf->Cell(100,7,"",0,0,'R');
$pdf->Cell(60,7,"IVA: ",0,0,'R');
$pdf->SetFont('Arial','',9);
$pdf->Cell(30,7,"$ ".number_format($factura_iva,$cfdi_decimal),"LRB",0,'R');
$pdf->Ln();

if($impuestoish!="NO"){
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(100,7,"",0,0,'R');
	$pdf->Cell(60,7,"ISH:  ",0,0,'R');
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(30,7,"$ ".number_format($impuestoish,$cfdi_decimal),"LRB",0,'R');
	$pdf->Ln();
	//$factura_total=($factura_total+$impuestoish);
}

//Retencion
for( $i=0; $i<count($retenciones); $i++ ){

	$suma_retencion = $suma_retencion+$retenciones2[$i]["importe"];
	if( $suma_retencion!="" ){
		$pdf->Cell(100,7,"",0,0,'R');
		if($retenciones2[$i]["impuesto"]=="002"){$retenciones2[$i]["impuesto"]="IVA";}
		if($retenciones2[$i]["impuesto"]=="001"){$retenciones2[$i]["impuesto"]="ISR";}

		$pdf->SetFont('Arial','B',9);
		$pdf->Cell(60,7,"Ret. ".$retenciones2[$i]["impuesto"].":  ",0,0,'R');
		$pdf->SetFont('Arial','',9);
		$pdf->Cell(30,7,"$ ".number_format($retenciones2[$i]["importe"],$cfdi_decimal),"LRB",0,'R');
		$pdf->Ln();
	}
}
if($total_retlocal>0){
	$resqm=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $facid);
	if ($resqm){
		$cfdi_m = $db->num_rows($resqm);
		$m = 0;
		if ($cfdi_m>0){
			while ($m < $cfdi_m){
				$obm = $db->fetch_object($resqm);
				$pdf->SetFont('Arial','B',9);
				$pdf->Cell(100,7,"",0,0,'R');
				$pdf->Cell(60,7,"Ret. ".$obm->codigo.":  ",0,0,'R');
				$pdf->SetFont('Arial','',9);
				$pdf->Cell(30,7,"$ ".number_format($obm->importe,$cfdi_decimal),"LRB",0,'R');
				$pdf->Ln();
				$m++;
			}
		}
	}
}
if( $suma_retencion!="" ){
	//$factura_total=($factura_total-$suma_retencion);
}
	// se modifica para utilizar el traductor de dolibarr
	$acroDivisa = $moneda;
	$aray_tipoDivisa = explode(' ', $langs->trans('Currency'. $acroDivisa));
	$tipoDivisa = strtoupper($aray_tipoDivisa[count($aray_tipoDivisa)-1].((strlen(floor($factura_total))>1)?'s':''));

	
//cantidad con letra y total

if($tipoDivisa=='USA'){$tipoDivisa='Dolares';}
if($tipoDivisa=='USAS'){$tipoDivisa='Dolares';}

$letras=utf8_decode(num2letras($factura_total,0,0).' '.$tipoDivisa);
$letras_len = strlen($letras);
$letras_substr = substr($letras, $letras_len-2,$letras_len);
if($letras_substr == "SS") $letras = substr($letras, 0 ,$letras_len-1);

$ultimo = substr(strrchr ($factura_total, "."), 1 ); //recupero lo que este despues del decimal
$letras = strtoupper($letras)." ".$ultimo."/100 ".($acroDivisa == "MXN"? "MN":"ME");


$contar_letras=strlen($letras);

if( $contar_letras>60 ){
	$fuente_letras = 7;
}else{
	$fuente_letras = 8;
}

$pdf->SetFont('Arial','B',$fuente_letras);
$pdf->Cell(100,7,$letras,0,0,'L');

$pdf->SetFont('Arial','B',9);
$pdf->Cell(60,7,"Total: ",0,0,'R');
$pdf->SetFont('Arial','',9);
$pdf->Cell(30,7,"$ ".number_format($factura_total,$cfdi_decimal),"LRB",0,'R');

if($previewpdf == 'previewpdf'){
	$prmsnd["uuid"]='prev_'.strtoupper($serie).$guion.$folio;
}

$array_factura_total = explode(".", $factura_total);
$factura_total_CBB='';
$factura_total_CBB .= sprintf('%0' . (int)10 . 's',   isset($array_factura_total[0])?$array_factura_total[0]:0);
$factura_total_CBB .= '.';
$factura_total_CBB .= sprintf('%-0' . (int)6 . 's',   isset($array_factura_total[1])?$array_factura_total[1]:0);


$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id='.$prmsnd["uuid"].'&re='.$pdf->prm["emisor"]["emisorRFC"].'&rr='.$receptor["rfc"].'&tt='.$factura_total_CBB.'&fe='.substr($prmsnd["selloCFD"],-8);
//$data_cbb = '?re='.$pdf->prm["emisor"]["emisorRFC"].'&rr='.$receptor["rfc"].'&tt='.$factura_total_CBB.'&id='.$prmsnd["uuid"];
QRcode::png($data_cbb,$conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/".$prmsnd["uuid"].'.png');
$pdf->Cell(150,5,'',0,0,'L');
$pdf->CheckPageBreak(24);
// $pdf->SetAutoPageBreak(true);


// print 'GETY: '.$pdf->GetY().'<br>';
// print 'GETX: '.$pdf->GetX().'<br>'; die;

if((int)$pdf->GetY() > 240){
	$pdf->AddPage();
}

$pdf->Image($conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/".$prmsnd["uuid"].'.png',162,$pdf->GetY()+13,35);

$pdf->Ln(10);

$pdf->SetFont('Arial','B',7);

$pdf->Cell(150,5,utf8_decode("SELLO DIGITAL DEL EMISOR"),0,0,'L');
$pdf->Ln();
$pdf->SetFont('Arial','',7);
$pdf->MultiCell(150,3,$prmsnd["selloCFD"],1,"L");

$pdf->SetFont('Arial','B',7);
$pdf->Cell(150,5,utf8_decode("SELLO DIGITAL DEL SAT"),0,0,'L');
$pdf->Ln();
$pdf->SetFont('Arial','',7);
$pdf->MultiCell(150,3,$prmsnd["selloSAT"],1,"L");

$pdf->SetFont('Arial','B',7);
$pdf->Cell(150,5,utf8_decode("CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT"),0,0,'L');
$pdf->Ln();
//$pdf->MultiCell(150,3,utf8_decode($prmsnd["cadena"]),1,"L");
$pdf->SetFont('Arial','',7);
$pdf->MultiCell(150,3,$prmsnd["coccds"],1,"L");


if ($conf->global->MAIN_INFO_ADD_PAGARE) {
	
	include_once DOL_DOCUMENT_ROOT.'/cfdimx/class/pagare.class.php';
	
	$paga = new Pagare($pdf,$db,$prmsnd, $header, $emisor, $factura_total, $cfdi_decimal, $letras);
	$paga->crea();
}

$pdf->Output($conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/cce_".$prmsnd["uuid"].".pdf","F");
?>
