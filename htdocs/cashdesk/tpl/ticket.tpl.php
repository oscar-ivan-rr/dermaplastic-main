<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once("../includes/tecnickcom/tcpdf/tcpdf_import.php");

// Load translation files required by the page
$langs->loadLangs(array("main","cashdesk"));

top_httphead('text/html');

$facid=GETPOST('facid', 'int');
$object=new Facture($db);
$object->fetch($facid);
$patient = new Societe($db);
$patient->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);

$sql = "SELECT DATE_FORMAT(fecha_nacimiento, '%d-%m-%Y') as dob FROM llx_societe_extrafields WHERE fk_object = '".$patient->id."'";
$resql = $db->query($sql);
$birthday = $db->fetch_object($resql);


// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF{
    
    //Page header
    public function Header() {
        global $user;
        global $db;
        $facid=GETPOST('facid', 'int');
        $object=new Facture($db);
        $object->fetch($facid);
        $htmlh = '<table style="text-align: center; font-size: 1.2em; color:#535E89; border-bottom:1px solid red;">
            <tr>
            <td rowspan="3" style="width: 20%;"><img style="width: 150px; height: 95px;" src="/cashdesk/img/logo_derma2.png"></td>
            <td style="width: 60%; font-size: 1.2em; "><b>Dr(a). '.$user->firstname.' '.$user->lastname.'</b></td>
            <td style="width: 20%;"></td>
            </tr>

            <tr>
            <td>'.$user->job.'</td>
            <td style="color:red; text-align: right;">'.str_replace(["(", ")"], "", $object->ref).'</td>
            </tr>

            <tr>
            <td>'.str_replace("\n", "<br>", $user->signature).'</td>
            <td></td>
            </tr>
            </table>';
        $this->writeHTML($htmlh, true, false, true, false, '');
        
    }

    // Page footer
    public function Footer() {
        global $user;
        $state = ucwords(strtolower($user->state_code));
        // Position at 15 mm from bottom
        $this->SetY(-15);
        $htmlh = '<table style="text-align: center; font-size: 1.5em; color:#535E89;">
            <tr>
            <td style="border-bottom:1px solid red;">'.$user->address.', '.$user->zip.', '.$user->town.', '.$state.'.</td>
            </tr>
            <tr>
            <td>Tel: '.$user->office_phone.'</td>
            </tr>
            </table>';
        $this->writeHTML($htmlh, true, false, true, false, '');
    }
}

// create new PDF document
$pageLayout = array(215.9, 139.7);
$pdf = new MYPDF('L', 'mm', $pageLayout, true, 'UTF-8', false);
$p = '/cashdesk/img/eliminar.png';
// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(5, 35, 5);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);
$pdf->setJPEGQuality(100);
// add a page
$pdf->AddPage();


// Ecabezado de ticket
// Se va concatenando todo en una variable HTML


$html = '<table style="font-size: 1.2em; color:#535E89; font-weight: bold;">
<tr>
    <td>Nombre del paciente: '.$patient->name.'</td>
    <td style="text-align: right;">'.date('d-m-Y').'</td>
</tr>
<tr>
    <td>Teléfono: '.$patient->phone.'</td>
</tr>
<tr>
    <td>Fecha de nacimiento: '.$birthday->dob.'</td>
</tr>';

if(!empty($_SESSION['condition_id'])){
    $sql = "SELECT label FROM llx_condition WHERE rowid = '".$_SESSION['condition_id']."'";
    $resql = $db->query($sql);
    $condition = $db->fetch_object($resql);
    $html.= '<tr><td>Padecimiento: '.$condition -> label.'</td></tr>';
}

$html.= '</table>';

$tab=array();
    $tab = $_SESSION['poscart'];

    $tab_size=count($tab);
    for($i=0;$i < $tab_size;$i++)
    {
        $remise = $tab[$i]['remise'];
        $html.= '<p style="font-size: 1.2em; color:#535E89;">'.$tab[$i]['qte'].' '.$tab[$i]['ref'].'<br>'.str_replace("\n", " - ", $tab[$i]['note']).'</p>';
    }

if(!empty($_SESSION['additional_products'])){
    $html.= '<p style="font-size: 1.2em; color:#535E89;">Adicionales: <br>'.str_replace("\n", "<br>",$_SESSION['additional_products']).'</p>';
}

if(!empty($object->note_private)){
    $html.= '<p style="font-size: 1.2em; color:#535E89;">Nota: '.$object->note_private.'</p>';
}



// Se termina de imprimir los elementos HTML y se escribe en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// ---------------------------------------------------------
//Close and output PDF document
$pdf->Output('Receta-'.$object -> ref.'.pdf', 'I');
?>
