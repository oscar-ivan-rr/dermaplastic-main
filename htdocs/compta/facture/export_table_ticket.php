<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once("../../includes/tecnickcom/tcpdf/tcpdf_import.php");

$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));


//ini_set('display_errors', 1);
$facturestaticc = new Facture($db);
$thirdpartystatic = new Societe($db);
$ticketstatic = new Ticket($db);
$formcompany = new FormCompany($db);
$form = new Form($db);
//Desde Hasta fecha factura
$date_start_facturemonth=GETPOST("date_start_facturemonth");
$date_start_factureday=GETPOST("date_start_factureday");
$date_start_factureyear=GETPOST("date_start_factureyear");
$date_end_facturemonth=GETPOST("date_end_facturemonth");
$date_end_factureday=GETPOST("date_end_factureday");
$date_end_factureyear=GETPOST("date_end_factureyear");

$date_start_facture=dol_mktime(0,0,0,$date_start_facturemonth,$date_start_factureday,$date_start_factureyear);
$date_end_facture=dol_mktime(0,0,0,$date_end_facturemonth,$date_end_factureday,$date_end_factureyear);

$limit = GETPOST("limit") + 1;

$sql    = $_POST['sql'];
// Ignorar límite del paginado
$sql = str_replace("LIMIT ".$limit, "", $sql);
$factures = json_decode($_POST['factures'])?? [];
if(sizeof($factures) > 0)
{
    $sqlExplode = explode('WHERE', $sql);
    $rowids = '(';
    $numFac = sizeof($factures);
    $iterator = 1;
    foreach ($factures as $fac){
        if($numFac != $iterator)
            $rowids .= ' f.rowid = '.$fac.' OR';
        else
            $rowids .= ' f.rowid = '.$fac.'';
        $iterator++;
    }
    $rowids.=') AND';
    $sql = $sqlExplode[0]."WHERE".$rowids.$sqlExplode[1];
}
$resql = $db->query($sql);
$num = $db->num_rows($resql);
$fecha = date("d-m-Y");
$table = '';

// create new PDF document
$pageLayout = array(80, 210);
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', $pageLayout, true, 'UTF-8', false);

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(3, 1, 3);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 0.5);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);
$pdf->setJPEGQuality(100);
// add a page
$pdf->AddPage();

$table .= '<h6 style="font-size: 8; text-decoration: bold; text-align: center;">REPORTE DE VENTAS</h6>
<br>';
if(!empty($date_start_facture) && empty($date_end_facture))
    $table .= '<h6 style="font-size: 8; text-decoration: bold; text-align: center;">A partir de: '.date('d-m-Y', $date_start_facture).'</h6><br>';
elseif (empty($date_start_facture) && !empty($date_end_facture))
    $table .= '<h6 style="font-size: 8; text-decoration: bold; text-align: center;">Hasta el: '.date('d-m-Y', $date_end_facture).'</h6><br>';
elseif (!empty($date_start_facture) && !empty($date_end_facture))
    $table .= '<h6 style="font-size: 8; text-decoration: bold; text-align: center;">De: '.date('d-m-Y', $date_start_facture).' A: '.date('d-m-Y', $date_end_facture).'</h6><br>';


$table.='<table class="tagtable liste listwithfilterbefore" style="font-size: 7;">'."\n";
$table.='<tr class="liste_titre" style="font-weight: bold;">';
$table.='<th class="rightliste_titre" colspan="2"  style="border-bottom-style: double;">Folio</th>';
$table.='<th class="rightliste_titre"  style="border-bottom-style: double;">Subtotal</th>';
$table.='<th class="rightliste_titre"  style="border-bottom-style: double;">Impuesto</th>';
$table.='<th class="rightliste_titre"  style="border-bottom-style: double;">Total</th>';
$table.="</tr>\n";

$projectstatic = new Project($db);
$discount = new DiscountAbsolute($db);

if ($num > 0)
{
    $i = 0;
    $t = 0;
    $totalarray = array();
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        if ($obj->is_ticket)
        {
            $facturestatic = $ticketstatic;
            if ($obj->type !=0)
            {
                $obj->total_ttc = $obj->total_ttc *-1;
            }
        }
        else
        {
            $facturestatic = $facturestaticc;
        }

        $facturestatic->ref = $obj->ref;

        // Validar si la CxC no es un borrador
        $draft = strpos($facturestatic->ref, 'PROV');
        if($draft === false){
            // Ref
            $table .= '<tr class="oddeven" style="text-align: right;"';
            $table .= '>';
            $table .= '<td class="nowrap" colspan="2" style="text-align: left;">';
            $table .= $facturestatic->ref;
            $table .= "</td>\n";

            // Amount HT
            $table .= '<td class="right nowrap">' . price($obj->total_ht) . "</td>\n";
            if (!$t) $totalarray['nbfield']++;
            if (!$t) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ht';
            $totalarray['val']['f.total_ht'] += $obj->total_ht;

            // Amount VAT
            $table .= '<td class="right nowrap">' . price($obj->total_vat) . "</td>\n";
            if (!$t) $totalarray['nbfield']++;
            if (!$t) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_vat';
            $totalarray['val']['f.total_vat'] += $obj->total_vat;

            // Amount TTC
            $table .= '<td class="right nowrap">' . price($obj->total_ttc) . "</td>\n";
            if (!$t) $totalarray['nbfield']++;
            if (!$t) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
            $totalarray['val']['f.total_ttc'] += $obj->total_ttc;

            $table .= "</tr>\n";
            $t++;
        }
        $i++;
        
    }
    // Show total line
    $key = 0;
    $table .= '<tr class="liste_total">';
    $table .= '<td class="left" colspan="2" style="border-top-style: double;"><b>'.$langs->trans("Total").'</b></td>';
    while($key <= $totalarray['nbfield']){
        if(isset($totalarray['pos'][$key])){
            $table .= '<td class="right" style="border-top-style: double; text-align: right;">'.price($totalarray['val'][$totalarray['pos'][$key]]).'</td>';
        }
        $key++;
    }
    $table .= '</tr>';

}
$db->free($resql);
$table.="</table>\n";




// Se termina de imprimir los elementos HTML y se escribe en el PDF
$pdf->writeHTML($table, true, false, true, false, '');

// ---------------------------------------------------------
//Close and output PDF document
$pdf->Output('Reporte de ventas_'.$fecha.'.pdf', 'D');
?>

