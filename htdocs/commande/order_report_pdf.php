<?php
require '../main.inc.php';
require_once("../includes/tecnickcom/tcpdf/tcpdf_import.php");

ob_clean();

$arrayofselected    = (array) json_decode($_POST['arrayofselected']);

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF{
    
    //Page header
    public function Header() {
        $htmlh = '<table style="text-align: center; font-size: 1.2em; color:#0a0a44;">
            <tr>
            <td rowspan="3" style="width: 20%;"><img style="width: 120px; height: 80px;" src="'.DOL_DOCUMENT_ROOT.'/commande/img/logo_derma.png"></td>
            <td style="width: 60%; font-size: 1.2em; "><b>REPORTE DE GENERACIÓN DE PEDIDOS <br>DESPACHADOS DESDE CEDIS</b></td>
            <td style="width: 20%;"></td>
            </tr>
            </table>';
        $this->writeHTML($htmlh, true, false, true, false, '');
    }

    // Page footer
    public function Footer() {
        global $db, $conf;
        // DATOS DE EMISOR
		$sqlrfc = "SELECT lcr.code as rfc FROM llx_entrepot le JOIN llx_c_rfc lcr ON le.fk_rfc = lcr.rowid WHERE le.rowid = ".$conf->global->CEDIS_WAREHOUSE."";
		$resrfc = $db->query($sqlrfc);
		$rfc= $db->fetch_object($resrfc);

        $sql_emisor = "SELECT lced.regimen as regimen FROM llx_cfdimx_emisor_datacomp lced WHERE lced.emisor_rfc = '".$rfc->rfc."'";
		$res_emisor = $db->query($sql_emisor);
		$emisor= $db->fetch_object($res_emisor);
		
		$sql_reg = "SELECT lccrf.label as regimen FROM llx_c_cfdimx_regimen_f lccrf WHERE lccrf.code = '".$emisor->regimen."'";
		$res_reg = $db->query($sql_reg);
		$regimen= $db->fetch_object($res_reg);

        $pageNumber = $this->getAliasNumPage();
        $totalPages = $this->getAliasNbPages();   

        $this->SetY(-10);
        $htmlh = '<table style="text-align: right; font-size: 1.1em; color:#0a0a44; width:100%;">
        <tr>
            <td style="width: 70%; border-top: 0.4px solid #d7d7d1;">'.$regimen->regimen.' - R.F.C: '.$rfc->rfc.'.</td>
            <td style="width: 30%; border-top: 0.4px solid #d7d7d1;">'.$pageNumber.' / '.$totalPages.'</td>
        </tr>
        </table>';

        $this->writeHTML($htmlh, true, false, true, false, '');
    }
}

// create new PDF document
$pageLayout = array(215.9, 139.7);
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(8, 40, 8);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font
$pdf->SetFont('helvetica', '', 10);
$pdf->setJPEGQuality(100);
$fecha = date("d-m-Y");

$filename = 'Reporte_de_pedidos_'.$fecha.'.pdf';
// add a page
$pdf->AddPage();

// -------------------------DATA BEGINS--------------------------------
$selected_id = implode(', ', $arrayofselected);

$html .= '';
$html .= '<table style="font-size: 1.1em; width:100%;">
<tr>
    <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Ref. de pedido</th>
    <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Fecha de pedido</th>
    <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Cliente</th>
    <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Cantidad de piezas</th>
</tr>';

$order_sql = "SELECT lc.rowid, lc.ref, lc.date_commande, ls.rowid, ls.nom, SUM(lc2.qty) as qty";
$order_sql .= " FROM ".MAIN_DB_PREFIX."commande lc";
$order_sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe ls ON ls.rowid = lc.fk_soc";
$order_sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet lc2 ON lc2.fk_commande = lc.rowid";
$order_sql .= " WHERE lc.rowid IN (".$selected_id.")";
$order_sql .= " GROUP BY lc.rowid ORDER BY lc.ref";
$order_res = $db->query($order_sql);

while($order= $db->fetch_object($order_res)){
    $html .= '<tr>
                <td style="border: 1px solid #c3c3bf;">'.$order->ref.'</td>
                <td style="border: 1px solid #c3c3bf;">'.dol_print_date($order->date_commande).'</td>
                <td style="border: 1px solid #c3c3bf;">'.$order->nom.'</td>
                <td style="border: 1px solid #c3c3bf;">'.$order->qty.' pz.</td>
             </tr>';
}

$html .= '<tr>
            <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Descripción</th>
            <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Código de barras</th>
            <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Ubicación</th>
            <th style="border: 1px solid #c3c3bf; font-weight:bold; background-color:#cfd0d8; text-align:center;">Cantidad</th>
         </tr>';

$product_sql = "SELECT lp.rowid, lp.`ref`, lp.barcode, pl.ubication, SUM(lc.qty) as qty, lpe.noidenticfdi as location";
$product_sql .= " FROM ".MAIN_DB_PREFIX."product lp";
$product_sql .= " JOIN ".MAIN_DB_PREFIX."commandedet lc ON lc.fk_product = lp.rowid";
$product_sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields lpe ON lpe.fk_object = lp.rowid ";
$product_sql .= " WHERE lc.fk_commande IN (".$selected_id.")";
$product_sql .= " GROUP BY lp.rowid ORDER BY lpe.noidenticfdi";
$product_res = $db->query($product_sql);

while($product= $db->fetch_object($product_res)){
    $html .= '<tr>
                <td style="border: 1px solid #c3c3bf;">'.$product->ref.'</td>
                <td style="border: 1px solid #c3c3bf;">'.$product->barcode.'</td>
                <td style="border: 1px solid #c3c3bf;">'.$product->ubication.'</td>
                <td style="border: 1px solid #c3c3bf;">'.$product->qty.' pz.</td>
             </tr>';
}

$html .= '</table>';
// -------------------------DATA ENDS--------------------------------

// The HTML elements are finished printing and written to the PDF
$pdf->writeHTML($html, true, false, true, false, '');

//Close and output PDF document
$pdf->Output($filename, 'D');

?>
