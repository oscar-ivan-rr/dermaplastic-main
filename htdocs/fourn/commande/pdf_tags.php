<?php
require '../../main.inc.php';
require_once "vendor/TCPDF-6.2.26/tcpdf_import.php";
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

$tags = explode(",", $_GET['tags']);
unset($tags[count($tags) - 1]);

$product = new Product($db);
$lote = new ProductLot($db);

$pageLayout = array(25.4, 12.7);
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', $pageLayout, true, 'UTF-8', false);
$pdf->SetAutoPageBreak(false, 0);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->setJPEGQuality(100);
$pdf->SetFont('times', '', 6);

foreach ($tags as $tag) {
    $prodId = explode("_", $tag)[0];
    $qty = explode("_", $tag)[1];
    $loteId = explode("_", $tag)[2];

    $product->fetch($prodId);
    $lote->fetch($loteId);

    $eatby = dol_print_date($lote->eatby, "%d%m%y");

    $i = 0;

    while ($i < $qty) {

        $pdf->AddPage('L');

        $posYTag = 1;
        $posXTag = 0;
        $fontSize = 3;

        //Celda Precio del Producto (Etiqueta)
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

        //Celda Referencia del Producto
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        $pdf->MultiCell($pageLayout[0], 0, $product->ref, 0, 'J', false, 1, $posXTag, $posYTag, true, 0, false, false, 4);

        $posYTag += 5;

        //Se imprime Código de Barras en base al Ref del Componente
        if ($lote) {
            $pdf->write1DBarcode($lote->id, 'C128', $posXTag + 1, $pdf->GetY(), $pageLayout[0] - 2, $pageLayout[1] - 2.5 - $pdf->GetY(), '', array(), 'N');
            $pdf->MultiCell($pageLayout[0], 0, $lote->id, 0, 'C', false, 2, $posXTag, $pdf->GetY(), true, 4);
        }
        $i++;
    }
}

$pdf->Output('tags.pdf');