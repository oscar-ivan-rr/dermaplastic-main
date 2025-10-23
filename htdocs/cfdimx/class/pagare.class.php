<?php

class Pagare {
	var $ipdf;
	var $db;
	var $prm;
	var $factura_total;
	var $cfdi_decimal;
	var $letras;

	function __construct($pdf, $db, $prmsnd, $header="", $emisor="", $factura_total, $cfdi_decimal, $letras){
		$this->ipdf = $pdf;
		$this->db = $db;
		$res=array();
		$res["header"]=$header;
		$res["emisor"]=$emisor;
		$res["otros"]=$prmsnd;
		$this->prm = $res;
		
		$this->factura_total = $factura_total;
		$this->cfdi_decimal = $cfdi_decimal;
		$this->letras = $letras;
		
		return $this->prm;
	}
	function crea() {

		if($this->ipdf->GetY()>200){
			$this->ipdf->setPrintHeader(false);
			$this->ipdf->setLeyendaCFDI(false);
			$this->ipdf->setPrintFooter();

			$this->ipdf->AddPage();
		}else{
			$this->ipdf->ln(15);
		}
		
		$titular =  strtoupper($this->prm["emisor"]["nombre"]);//"BRYAN ALEJANDRO GASTELUM LOPEZ";
		$rfc= strtoupper($this->prm["emisor"]["emisorRFC"]);//"GALB990204RY8";
		$cantidad_con_letra = strtoupper($this->letras);//"DOSCIENTOS TREINTA Y DOS PESOS 00/100 M.N.";
		$total = number_format($this->factura_total,$this->cfdi_decimal);//"232.00";
		$moneda = $this->prm["header"]["moneda"];//"Pesos";
		$dia = date('Y-m-d');//"20140628";
		
		$direccion = "Dirección: " . strtoupper($this->prm['emisor']['calle']);
		$localidad = "Localidad: ". strtoupper($this->prm['emisor']['colonia']).", Municipio: ".strtoupper($this->prm['emisor']['municipio']).",Estado: ".strtoupper($this->prm['emisor']['estado']) .",País: ".strtoupper($this->prm['emisor']['pais']);//"CD. OBREGON";
		
		$this->ipdf->SetFont('Arial','B',10);
		$this->ipdf->SetFillColor(229, 229, 229); //Gris tenue de cada fila
		$this->ipdf->SetTextColor(3, 3, 3); //Color del texto: Negro
		
		$this->ipdf->Cell(48,7,"LUGAR DE EXPEDICION",0,0,'C', true);
		$this->ipdf->Cell(48,7,"FECHA DE EXPEDICION",0,0,'C', true);
		$this->ipdf->Cell(47,7,utf8_decode("MONTO DEL PAGARE"),0,0,'C', true);
		$this->ipdf->Cell(47,7,"PAGARE No.",0,0,'C', true);
		$this->ipdf->Ln();
		$this->ipdf->SetFont('Arial','B',8);
		$this->ipdf->Cell(48,7, strtoupper($this->prm["emisor"]["colonia"]),0,0,'C');
		$this->ipdf->Cell(48,7,$dia,0,0,'C');
		$this->ipdf->Cell(47,7,$total,0,0,'C');
		$this->ipdf->Cell(47,7,strtoupper($this->prm["header"]["serie"]).$guion.$this->prm["header"]["folio"],0,0,'C');
		$this->ipdf->Ln(10);
		$this->ipdf->SetFont('Arial','',8);
		$text1 = "Por medio de este pagare me(nos) obligo(amos) a pagar incondicionalmente a la orden de $titular la cantidad de $total Son:($cantidad_con_letra)";
		$this->ipdf->MultiCell(190,3,$text1,0,"L");
		$this->ipdf->Ln();
		$text2 = "El dia $dia en la ciudad de ".$this->prm['emisor']['colonia']." el valor del servicio o mercancía recibida a mi(nuestra) entera satisfacción, el cual de no pagarse a la fecha de su vencimiento causara intereses moratorios a la razón del 10% mensual, pagaderos en esta ciudad o en cualquier otra que fuera requerido el pago.";
		$this->ipdf->MultiCell(190,3,$text2,0,"L");
		$this->ipdf->Ln(5);
		$this->ipdf->SetFont('Arial','B',8);
		$this->ipdf->Cell(94,7,"$titular RFC: $rfc",0,0,'L');
		$this->ipdf->Cell(16,7,"",0,0,'C');
		$this->ipdf->Cell(80,7,"Recibí(mos)",0,0,'C');
		$this->ipdf->Ln(5);
		$this->ipdf->Cell(94,7,"$direccion",0,0,'L');
		$this->ipdf->Ln(5);
		$this->ipdf->Cell(94,7,"$localidad",0,0,'L');
		$this->ipdf->Ln(5);
		$this->ipdf->Cell(94,7,"",0,0,'L');
		$this->ipdf->Cell(16,7,"",0,0,'C');
		$this->ipdf->Cell(80,7,"________________________________",0,0,'C');
		$this->ipdf->Ln(5);
		$this->ipdf->Cell(94,7,"",0,0,'L');
		$this->ipdf->Cell(16,7,"",0,0,'C');
		$this->ipdf->Cell(80,7,"firma",0,0,'C');
	}
}
?>