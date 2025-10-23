<?php
require '../main.inc.php';
require_once "../includes/tecnickcom/tcpdf/tcpdf_import.php";
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

$prodId = GETPOST("id");
$loteId = GETPOST("batch");

$product = new Product($db);
$lote = new ProductLot($db);

$pageLayout = array(25.4, 12.7);
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', $pageLayout, true, 'UTF-8', false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->setJPEGQuality(100);
$pdf->SetFont('times', '', 6);
$pdf->AddPage('L');

$product->fetch($prodId);
$lote->fetch($loteId);

$eatby = dol_print_date($lote->eatby, "%d%m%y");

$posYTag = 1;
$posXTag = 0;
$fontSize = 3;

//Precio del Producto (Etiqueta)
$pdf->SetFont('Helvetica', 'B', $fontSize);
if ($product->exentoiva > 0) {
    $pdf->MultiCell($pageLayout[0], 0, $eatby, 0, 'L', false, 1, $posXTag, $posYTag);
    $pdf->MultiCell($pageLayout[0], 0, "ID:{$lote->fk_product}", 0, 'C', false, 1, $posXTag, $posYTag);
    $pdf->MultiCell($pageLayout[0], 0, '$' . price($product->price), 0, 'R', false, 1, $posXTag, $posYTag);
} else {
    $pdf->MultiCell($pageLayout[0], 0, $eatby, 0, 'L', false, 1, $posXTag, $posYTag);
    $pdf->MultiCell($pageLayout[0], 0, "ID:{$lote->fk_product}", 0, 'C', false, 1, $posXTag, $posYTag);
    $pdf->MultiCell($pageLayout[0], 0, "$" . price($product->price_ttc), 0, 'R', false, 1, $posXTag, $posYTag);
}
$posYTag = $pdf->GetY();

//Referencia del Producto
$pdf->SetFont('Helvetica', 'B', $fontSize);
$pdf->MultiCell($pageLayout[0], 0, $product->ref, 0, 'J', false, 1, $posXTag, $posYTag, true, 0, false, false, 4);

//Se imprime Código de Barras en base al Ref del Componente
if ($lote) {
    $pdf->write1DBarcode($lote->id, 'C128', $posXTag + 1, $pdf->GetY(), $pageLayout[0] - 2, $pageLayout[1] - 2.5 - $pdf->GetY(), '', array(), 'N');
    $pdf->MultiCell($pageLayout[0], 0, $lote->id, 0, 'C', false, 2, $posXTag, $pdf->GetY(), true, 4);
}

$pdf->Output('tags.pdf');