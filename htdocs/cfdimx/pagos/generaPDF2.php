<?php
header('Content-Type: text/html; charset=utf-8');
define('FPDF_FONTPATH','font/');
require('../lib/fpdf/fpdf.php');

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

			
			$tipo_doc="Pago";
			
			$pdf2=new PDF();
			$this->Cell(2,4,"",0,0,'L'); //inicio
			$this->Cell(45,4,"",0,0,'C'); //
			//$this->Cell(80,4,strtoupper($pdf2->limpiar($this->prm["emisor"]["nombre"])),0,0,'L');
			$this->Cell(80,4,"",0,0,'L'); //
			$this->Cell(2,4,"",0,0,'C'); //enmedio

			
			$doctype="Pago";
			
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
			$this->Cell(80,4,"",0,0,'L');
			//$this->Cell(80,4,"C.P. ".$this->prm["emisor"]["codigoPostal"],0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,"NO. DE SERIE DEL CERTIFICADO DEL EMISOR","LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->Cell(2,4,$this->prm["emisor"]["emisorRFC"],0,0,'L');
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(80,4,"",0,0,'L');
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["certEmisor"],"LR",1,'C'); //factura - 2

			/**********ROW************/

			$this->SetFont('Arial','B',7);
			$this->Cell(2,4,strtoupper(utf8_decode("Lugar de expedicion: ")).strtoupper($pdf2->limpiar($this->prm["header"]["lugarExpedicion"])),0,0,'L');
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			//if(strpos($this->prm["header"]["metodoDePago"], ',')){
			$this->Cell(80,4,"",0,0,'L'); //cliente - 1
			//}else{
				//$this->Cell(80,4,$pdf2->limpiar(html_entity_decode(strtoupper(utf8_decode("Metodo de pago: ").$pdf2->limpiar($this->prm["header"]["metodoDePago"]." - ".$this->prm["header"]["metodoDePago2"])))),0,0,'L'); //cliente - 1
			//}
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,utf8_decode("FECHA Y HORA DE CERTIFICACION"),"LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->Cell(2,4,strtoupper(utf8_decode("Moneda:".$this->prm["header"]["moneda"])),0,0,'L'); //cliente - 1
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(80,4,"",0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["fechaTimbrado"],"LR",1,'C'); //factura - 2

			/**********ROW************/

			$this->SetFont('Arial','B',7);
			$this->Cell(2,4,"",0,0,'L'); //cliente - 1
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(80,4,"",0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->Cell(60,4,utf8_decode("FECHA Y HORA DE EMISION DEL CFDI"),"LR",1,'C'); //factura - 2

			/**********ROW************/
			$this->Cell(2,4,"",0,0,'L'); //cliente - 1
			//$this->Cell(37,4,"",0,0,'C'); //inicio
			$this->Cell(45,4,"",0,0,'C');
			$this->Cell(80,4,"",0,0,'L'); //cliente - 1
			$this->Cell(2,4,"",0,0,'C');//enmedio
			$this->SetFont('Arial','',7);
			$this->Cell(60,4,$this->prm["otros"]["fechaEmision"],"LRB",1,'C'); //factura - 2

			/**********ROW************/

			$this->SetFont('Arial','B',7);
			$this->Cell(2,4,strtoupper(utf8_decode("Regimen Fiscal: "). strtoupper($pdf2->limpiar($this->prm["emisor"]["emisorRegimen"]))),0,0,'L');
			$this->Cell(45,4,"",0,0,'C'); //inicio
			$this->Cell(80,4,"",0,0,'L'); //cliente - 1
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

			if( $this->prm["otros"]["logosmall"]!="" ){
				//AMM
				$punto_anclaje = 6;//Abscisa de la esquina superior izquierda
				$max_width = 225;// Maximo ancho
				$max_height = 150;//Maximo alto

				$ordena = 16; //Ordenada de la esquina superior izquierda
				$width = 40; // Ancho de la imagen en la p�gina


				//$imageMeasures = list($ancho, $alto, $tipo, $atributos) = getimagesize($this->prm["otros"]["logosmall"]);//getimagesize($this->prm["logosmall"]);

				//$ordena = ($max_width/$ordena)*$imageMeasures[0];
				//$width = ($max_width/$width)*$imageMeasures[1];

				if($_SESSION['dol_entity'] == 1){
					//$this->Image($this->dolibarr_main_data_root.'/mycompany/logos/thumbs/'.$this->prm["otros"]["logosmall"],$punto_anclaje,$ordena, $width, $height);
					$this->Image($this->dolibarr_main_data_root.'/mycompany/logos/thumbs/'.$this->prm["otros"]["logosmall"],11,6,20);

				}else{
					//$this->Image($this->dolibarr_main_data_root.'/'.$_SESSION['dol_entity'].'/mycompany/logos/thumbs/'.$this->prm["otros"]["logosmall"],$punto_anclaje,$ordena, $width, $height);
					$this->Image($this->dolibarr_main_data_root.'/'.$_SESSION['dol_entity'].'/mycompany/logos/thumbs/'.$this->prm["otros"]["logosmall"],11,6,20);
				}
				//AMM
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
		$h=5*$nb;


                //Issue a page break first if needed
                $this->CheckPageBreak($h,$cl);
		//Draw the cells of the row
		for($i=0;$i<=count($data);$i++)
		{
			$w=$this->widths[$i];
			if($textAlign == ""){
			    if($i==2){
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
			if($i<5)$this->MultiCell($w, 5,$data[$i], $mcBorder, $a, $fill);
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
//************* cortar texto *****************
//AMM

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

$pdf->AliasNbPages();
$pdf->AddPage();

$sql2="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
		,tipocambio,certificadoorigen,subdivision,totalusd
FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
WHERE fk_facture=".$facid;
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
$pdf->Cell(190,4,strtoupper($pdf2->limpiar($receptor["nombre"])),"LR",0,'L'); //cliente
$pdf->Ln();

$pdf->Cell(190,4,$receptor["rfc"],"LR",0,'L'); //cliente
$pdf->Ln();

$pdf->Cell(190,4,"Uso CFDI: ".mb_strtoupper($receptor["usoCFDI"]) ,"LR",0,'L'); //cliente
$pdf->Ln();

$pdf->Cell(190,4,"","LBR",0,'L'); //cliente
$pdf->Ln(7);

if( $observaciones!="" ){
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(190,4,"OBSERVACIONES",1,0,'C'); //cliente
	$pdf->Ln();
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(190,4,$observaciones,"LRB","L");
	$pdf->Ln(5);
}


$pdf->SetFont('Times','B',11);

$pdf->Cell(20,7,"Cantidad",1,0,'C');
$pdf->Cell(20,7,"U. de Med",1,0,'C');
$pdf->Cell(90,7,utf8_decode("Descripcion"),1,0,'C');
$pdf->Cell(30,7,"Precio Unitario",1,0,'C');
$pdf->Cell(30,7,"Importe",1,0,'C');
$pdf->Ln();

$pdf->SetFont('Times','',10);
$pdf->SetWidths(array(20,20,90,30,30));
srand(microtime()*1000000);

/* for($i=0;$i<count($conceptos);$i++) {
	$conc[$i] = $conceptos[$i]["descripcion"];
    $pdf->Row(array(
		$conceptos[$i]["cantidad"],
		$conceptos[$i]["unidad"],
		$conc[$i],
		"$".number_format($conceptos[$i]["valorUnitario"],$cfdi_decimal),
		"$".number_format($conceptos[$i]["importe"],$cfdi_decimal)
	));
} */


for($i=0;$i<count($conceptos);$i++) {
      $conc[$i] = utf8_decode($conceptos[$i]["descripcion"]);
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
		    $conceptos[$i]["cantidad"],
		    $conceptos[$i]["unidad"],
		    $conc[$i],
		    "$".number_format($conceptos[$i]["valorUnitario"],$cfdi_decimal),
		    "$".number_format($conceptos[$i]["importe"],$cfdi_decimal)
	          ));
     }else{
     //
		for($j=0; $j<count($lineas); $j++){
			 if(count($lineas)>1){
 				if($j==0){
// Agrega las linea inicial
				         $pdf->Row(array(
						    $conceptos[$i]["cantidad"],
						    $conceptos[$i]["unidad"],
						    $lineas[$j],
						    "$".number_format($conceptos[$i]["valorUnitario"],$cfdi_decimal),
						    "$".number_format($conceptos[$i]["importe"],$cfdi_decimal)
					          ),0,"LTR","J");
				}elseif($j>0){
// Agrega las lineas intermedias
				        $pdf->Row(array(
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

// $pdf->Cell(100,7,"",0,0,'R');
// $pdf->Cell(60,7,"Subtotal:  ",0,0,'R');
// $pdf->Cell(30,7,"$".number_format($factura_subtotal,$cfdi_decimal),"LRB",0,'R');
// $pdf->Ln();

// $pdf->Cell(100,7,"",0,0,'R');
// $pdf->Cell(60,7,"IVA:  ",0,0,'R');
// $pdf->Cell(30,7,"$".number_format($factura_iva,$cfdi_decimal),"LRB",0,'R');
// $pdf->Ln();


// $moneda=$pagos["monedaP"];
// 	// se modifica para utilizar el traductor de dolibarr
// 	$acroDivisa = $moneda;
// 	$aray_tipoDivisa = explode(' ', $langs->trans('Currency'. $acroDivisa));
// 	$tipoDivisa = strtoupper($aray_tipoDivisa[count($aray_tipoDivisa)-1].((strlen(floor($factura_total))>1)?'s':''));

	
// //cantidad con letra y total

// if($tipoDivisa=='USA'){$tipoDivisa='Dolares';}
// if($tipoDivisa=='USAS'){$tipoDivisa='Dolares';}

// $letras=utf8_decode(num2letras($factura_total,0,0).' '.$tipoDivisa);
// $letras_len = strlen($letras);
// $letras_substr = substr($letras, $letras_len-2,$letras_len);
// if($letras_substr == "SS") $letras = substr($letras, 0 ,$letras_len-1);

// $ultimo = substr(strrchr ($factura_total, "."), 1 ); //recupero lo que este despues del decimal
// $letras = strtoupper($letras)." ".$ultimo."/100 ".($acroDivisa == "MXN"? "MN":"ME");


// $contar_letras=strlen($letras);

// if( $contar_letras>60 ){
// 	$fuente_letras = 7;
// }else{
// 	$fuente_letras = 8;
// }

// $pdf->SetFont('Times','B',$fuente_letras);
// $pdf->Cell(100,7,$letras,0,0,'R');
// $pdf->SetFont('Times','',10);
// $pdf->Cell(60,7,"Total:  ",0,0,'R');
// $pdf->Cell(30,7,"$".number_format($factura_total,$cfdi_decimal),"LRB",0,'R');


$pdf->SetFont('Arial','B',8);
$pdf->Ln(5);
$pdf->Cell(190,4,"PAGO",1,0,'C'); //cliente
$pdf->Ln();
$pdf2=new PDF();
$pdf->SetFont('Arial','',8);
$pdf->Cell(190,4,"Fecha de Pago: ".$pagos[0]["fechaPago"],"LR",0,'L'); //cliente
$pdf->Ln();

$pdf->Cell(190,4,"Forma de Pago: ".strtoupper($pagos[0]["formaDePago"]),"LR",0,'L'); //cliente
$pdf->Ln();

$pdf->Cell(190,4,"Moneda de Pago: ".$pagos[0]["monedaP"] ,"LR",0,'L'); //cliente
$pdf->Ln();

if($pagos[0]["tipoCambioP"]!=NULL && $pagos[0]["tipoCambioP"]){
	//$pagos["tipoCambioP"]=$respag->TipoCambioP;
	$pdf->Cell(190,4,"Tipo de Cambio: ".$pagos[0]["tipoCambioP"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
//$pagos["monto"]=str_replace(",", "", number_format($respag->monto,2));
$pdf->Cell(190,4,"Monto: ".number_format($pagos[0]["monto"],2) ,"LR",0,'L'); //cliente
$pdf->Ln();
if($pagos[0]["numOperacion"]!=NULL && $pagos[0]["numOperacion"]){
	//$pagos["numOperacion"]=$respag->numOperacion;
	$pdf->Cell(190,4,"Numero de Operacion: ".$pagos[0]["numOperacion"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["rfcEmisorCtaOrd"]!=NULL && $pagos[0]["rfcEmisorCtaOrd"]){
	//$pagos["rfcEmisorCtaOrd"]=$respag->rfcEmisorCtaOrd;
	$pdf->Cell(190,4,"RFC Emisor Cuenta Ordenante: ".$pagos[0]["rfcEmisorCtaOrd"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["nomBancoOrdExt"]!=NULL && $pagos[0]["nomBancoOrdExt"]){
	//$pagos["nomBancoOrdExt"]=$respag->nomBancoOrdExt;
	$pdf->Cell(190,4,"Nombre Banco Ordenante: ".$pagos[0]["nomBancoOrdExt"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["ctaOrdenante"]!=NULL && $pagos[0]["ctaOrdenante"]){
	//$pagos["ctaOrdenante"]=$respag->ctaOrdenante;
	$pdf->Cell(190,4,"Cuenta Ordenante: ".$pagos[0]["ctaOrdenante"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["rfcEmisorCtaBen"]!=NULL && $pagos[0]["rfcEmisorCtaBen"]){
	//$pagos["rfcEmisorCtaBen"]=$respag->rfcEmisorCtaBen;
	$pdf->Cell(190,4,"RFC Emisor Cuenta Beneficiario: ".$pagos[0]["rfcEmisorCtaBen"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["ctaBeneficiario"]!=NULL && $pagos[0]["ctaBeneficiario"]){
	//$pagos["ctaBeneficiario"]=$respag->ctaBeneficiario;
	$pdf->Cell(190,4,"Cuenta Beneficiario: ".$pagos[0]["ctaBeneficiario"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["tipoCadPago"]!=NULL && $pagos[0]["tipoCadPago"]){
	//$pagos["tipoCadPago"]=$respag->tipoCadPago;
	$pdf->Cell(190,4,"Tipo Cadena Pago: ".$pagos[0]["tipoCadPago"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["certPago"]!=NULL && $pagos[0]["certPago"]){
	//$pagos["certPago"]=$respag->certPago;
	$pdf->Cell(190,4,"Certificado del Pago: ".$pagos[0]["certPago"] ,"LR",0,'L'); //cliente
	$pdf->Ln();
}
if($pagos[0]["cadPago"]!=NULL && $pagos[0]["cadPago"]){
	//$pagos["cadPago"]=$respag->cadPago;
	/*$pdf->Cell(190,4,"Cadena del Pago: ".$pagos[0]["cadPago"] ,"LR",0,'L'); //cliente
	$pdf->Ln();*/
	$axscad_pago=str_split($pagos[0]["cadPago"],110);
	$con=count($axscad_pago);
	for($i=0;$i<$con;$i++){
		if($i==0){
			$pdf->Cell(190,4,"Cadena del Pago: " ,"LR",0,'L'); //cliente
			$pdf->Ln();
			$pdf->Cell(190,4,"".$axscad_pago[$i] ,"LR",0,'L'); //cliente
			$pdf->Ln();
		}else{
			$pdf->Cell(190,4,"".$axscad_pago[$i] ,"LR",0,'L'); //cliente
			$pdf->Ln();
		}
	}
}
if($pagos[0]["selloPago"]!=NULL && $pagos[0]["selloPago"]){
	//$pagos["selloPago"]=$respag->selloPago;
	/*$pdf->Cell(190,4,"Sello del Pago: ".$pagos[0]["selloPago"] ,"LR",0,'L'); //cliente
	$pdf->Ln();*/
	$axsello_pago=str_split($pagos[0]["selloPago"],110);
	$con=count($axsello_pago);
	for($i=0;$i<$con;$i++){
		if($i==0){
			$pdf->Cell(190,4,"Sello del Pago: " ,"LR",0,'L'); //cliente
			$pdf->Ln();
			$pdf->Cell(190,4,"".$axsello_pago[$i] ,"LR",0,'L'); //cliente
			$pdf->Ln();
		}else{
			$pdf->Cell(190,4,"".$axsello_pago[$i] ,"LR",0,'L'); //cliente
			$pdf->Ln();
		}
	}
}
$pdf->Cell(190,4,"","LBR",0,'L'); //cliente
$pdf->Ln(5);

$auxc=count($pagos);
//print "<br>:::".$auxc;exit();

$pdf->Ln(5);
//$pdf2=new PDF();
for($i=0;$i<$auxc;$i++){
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(190,4,"Documento Relacionado",1,0,'C'); //cliente
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(190,4,"ID Documento: ".$pagos[$i]["idDocumento"],"LR",0,'L'); //cliente
	$pdf->Ln();
	
	if($pagos[$i]["serie"]!=NULL && $pagos[$i]["serie"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Serie: ".$pagos[$i]["serie"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	if($pagos[$i]["folio"]!=NULL && $pagos[$i]["folio"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Folio: ".$pagos[$i]["folio"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	$pdf->Cell(190,4,"Moneda: ".$pagos[$i]["monedaDR"],"LR",0,'L'); //cliente
	$pdf->Ln();
	if($pagos[$i]["tipoCambioDR"]!=NULL && $pagos[$i]["tipoCambioDR"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Tipo de Cambio: ".$pagos[$i]["tipoCambioDR"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	$pdf->Cell(190,4,"Metodo de Pago: ".$pagos[$i]["metodoDePagoDR"],"LR",0,'L'); //cliente
	$pdf->Ln();
	if($pagos[$i]["numParcialidad"]!=NULL && $pagos[$i]["numParcialidad"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Numero de Parcialidad: ".$pagos[$i]["numParcialidad"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	if($pagos[$i]["impSaldoAnt"]!=NULL && $pagos[$i]["impSaldoAnt"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Importe Saldo Anterior: ".$pagos[$i]["impSaldoAnt"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	if($pagos[$i]["impPagado"]!=NULL && $pagos[$i]["impPagado"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Importe Pagado: ".$pagos[$i]["impPagado"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	if($pagos[$i]["impSaldoInsoluto"]!=NULL && $pagos[$i]["impSaldoInsoluto"]){
		//$pagos["tipoCambioP"]=$respag->TipoCambioP;
		$pdf->Cell(190,4,"Importe Saldo Insoluto: ".$pagos[$i]["impSaldoInsoluto"] ,"LR",0,'L'); //cliente
		$pdf->Ln();
	}
	$pdf->Cell(190,4,"","LBR",0,'L'); //cliente
	$pdf->Ln();
}
$pdf->Ln(5);



if($previewpdf == 'previewpdf'){
	$prmsnd["uuid"]='PRV-'.strtoupper($serie).$guion.$folio;
}

$array_factura_total = explode(".", $factura_total);
$factura_total_CBB='';
$factura_total_CBB .= sprintf('%0' . (int)10 . 's',   isset($array_factura_total[0])?$array_factura_total[0]:0);
$factura_total_CBB .= '.';
$factura_total_CBB .= sprintf('%-0' . (int)6 . 's',   isset($array_factura_total[1])?$array_factura_total[1]:0);


//$data_cbb = '?re='.$pdf->prm["emisor"]["emisorRFC"].'&rr='.$receptor["rfc"].'&tt='.$factura_total_CBB.'&id='.$prmsnd["uuid"];
$data_cbb = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id='.$prmsnd["uuid"].'&re='.$pdf->prm["emisor"]["emisorRFC"].'&rr='.$receptor["rfc"].'&tt='.$factura_total_CBB.'&fe='.substr($prmsnd["selloCFD"],-8);
QRcode::png($data_cbb,$conf->facture->dir_output."/".$pago->ref."/Pago_".$prmsnd["uuid"].'.png');
// $pdf->Cell(150,5,'',0,0,'L');
// $pdf->CheckPageBreak(24);
$pdf->Image($conf->facture->dir_output."/".$pago->ref."/Pago_".$prmsnd["uuid"].'.png',162,$pdf->GetY()+10,42);

$pdf->Ln(10);

$pdf->SetFont('Times','',6);

$pdf->Cell(150,5,utf8_decode("SELLO DIGITAL DEL EMISOR"),0,0,'L');
$pdf->Ln();
$pdf->MultiCell(150,3,$prmsnd["selloCFD"],1,"L");

$pdf->Cell(150,5,utf8_decode("SELLO DIGITAL DEL SAT"),0,0,'L');
$pdf->Ln();
$pdf->MultiCell(150,3,$prmsnd["selloSAT"],1,"L");

$pdf->Cell(150,5,utf8_decode("CADENA ORIGINAL DEL COMPLEMENTO DE CERTIFICACION DIGITAL DEL SAT"),0,0,'L');
$pdf->Ln();
//$pdf->MultiCell(150,3,utf8_decode($prmsnd["cadena"]),1,"L");
$pdf->MultiCell(150,3,$prmsnd["coccds"],1,"L");


if ($conf->global->MAIN_INFO_ADD_PAGARE) {
	
	include_once DOL_DOCUMENT_ROOT.'/cfdimx/class/pagare.class.php';
	
	$paga = new Pagare($pdf,$db,$prmsnd, $header, $emisor, $factura_total, $cfdi_decimal, $letras);
	$paga->crea();
}

$pdf->Output($conf->facture->dir_output."/".$pago->ref."/Pago_".$prmsnd["uuid"].".pdf","F");
?>
