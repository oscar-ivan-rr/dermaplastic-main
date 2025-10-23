<?php
ob_start();
define('FPDF_FONTPATH', 'font/');
require('lib/fpdf2/fpdf.php');

$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->Image('lib/fpdf2/formatoCancel.jpg' , 10 ,10, 200 , 90,'JPG');

$selloSAT = "";
$dateS = "";
$dateC = "";

$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos c WHERE c.fk_paiement = ".$id;
$resql = $db->query($sql);
if($resql) {
    if($row = $db->fetch_object($resql)) {
        $selloSAT = $row->sello;
        $dateS = $row->fecha_emision.' '.$row->hora_emision;
        $dateC = $row->fechaPago;
        $uuid = $row->uuid;
    }
}

$pdf->SetFont('Arial', '', 10);
$pdf->Ln(45);
$pdf->Cell(95, 6, "", 0);
$pdf->Cell(35, 6, $dateS, 0);
$pdf->Ln(9);
$pdf->Cell(95, 6, "", 0);
$pdf->Cell(35, 6, $dateC, 0);
$pdf->Ln(18);
$pdf->Cell(24, 6, "", 0);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(70, 6, $uuid, 0);
$pdf->Ln(6);
$pdf->SetFont('Times', '', 8);
$pdf->Cell(54, 6, "", 0);
$pdf->MultiCell(120, 3, $selloSAT, 0);
$pdf->SetY(150);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, utf8_decode('Este documento es una representación impresa de un CFDI cancelado'), 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(0, 5, utf8_decode('Pagina ' . $pdf->PageNo() ), 0, 0, 'C');
ob_get_clean();
$outpath = $dir_path."/acuse_cancelacion_".$object->ref.".pdf";
$pdf->Output($outpath, 'F');

?>