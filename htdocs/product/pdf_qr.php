<?php
require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once("../includes/tecnickcom/tcpdf/tcpdf_import.php");
$object = new Product($db);
$id = GETPOST('id','int');
$object->fetch($id);
// Obtenemos los nombres de los proveedores de este producto
$proveedores='';
$sql= 'SELECT s.nom';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price as pfp';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = pfp.fk_soc';
$sql.= ' WHERE pfp.fk_product='.$id;
$sql.= ' GROUP BY pfp.fk_soc';
$result = $db->query($sql);
$num_p = $db->num_rows($result);
if($num_p > 0)
{
    $i = 1;
    while ($p = $db->fetch_object($result))
    {
        if($i == $num_p)
            $proveedores.=$p->nom;
        else
            $proveedores.=$p->nom.";";
        $i++;
    }
}

$pdf = new TCPDF('L', PDF_UNIT, '', true, 'UTF-8', false);
$formato = array( 355,  82.204); // mm (height,width)
//Image
$eje_y_img = 4;
$eje_y_txt = 4;
//Content
$eje_x = 35;
$start_y = 14;
$border = 0;
$width_cell = 100;
$width_cell_proveedores = 310;
$height = 0;

$pdf->AddPage('',$formato);
$pdf->setJPEGQuality(75);

//$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetFillColor(255,255,255);
$pdf->SetTextColor(0, 0, 0);

$style = array(
    'border' => false,
    'vpadding' => '0',
    'hpadding' => '0',
    'fgcolor' => array(0,0,0),
    'bgcolor' => false, //array(255,255,255)
    'module_width' => 1, // width of a single module in points
    'module_height' => 1 // height of a single module in points
);
//Código Qr
$barcodeValue = DOL_MAIN_URL_ROOT . "/product/custom/view.php?id=$object->id";
$pdf->write2DBarcode($barcodeValue, 'QRCODE,L', 10, $pdf->GetY()+4, 25, 25, $style, 'N');
//Referencia del producto
$pdf->SetXY($eje_x,$start_y);
$pdf->Cell($width_cell,$height,"Código ".$object->ref,0,0,'L',true);
//Etiqueta
$pdf->SetXY($eje_x,$start_y+5);
$pdf->Cell($width_cell_proveedores,$height,$object->label,0,0,'L',true);
//Descripcion
$pdf->SetXY($eje_x,$start_y+10);
$pdf->Cell($width_cell_proveedores,$height,$object->description,0,0,'L',true);
//Proveedores del producto
//$pdf->SetXY($eje_x,$start_y+15);
//$pdf->Cell($width_cell_proveedores,$height,"Proveedores: ".$proveedores,0,0,'L',true);

$pdf->Output('label_'.$id.'.pdf');