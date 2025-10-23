<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra    <jove@bisquerra.com>
 * Copyright (C) 2019      Josep Lluís Amador  <joseplluis@lliuretic.cat>
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

/**
 *	\file       htdocs/takepos/floors.php
 *	\ingroup    takepos
 *	\brief      Page to show a receipt.
 */

require '../main.inc.php'; // Load $user and permissions
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once("../includes/tecnickcom/tcpdf/tcpdf_import.php");


$langs->loadLangs(array("main", "cashdesk", "companies"));

$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Ba or Restaurant

$facid = GETPOST('facid', 'int');

$numtickets = GETPOST('numtickets', 'int') > 0 ? GETPOST('numtickets', 'int') : 1;

/*
 * View
 */

top_httphead('text/html');

if ($place > 0)
{
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $facid = $obj->rowid;
    }
}
$object = new Facture($db);
$object->fetch($facid);
// Variable para validar si se trata de un ticket de reembolso, el cual incluye la nomenclatura "NC"
$ref = substr($object -> ref, 2, 2);

// create new PDF document
$pageLayout = array(80, 500);
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

// Consulta para obtener los montos abonados en la compra
$card = 0;
$cash = 0;
$credit_note = 0;
$transfer = 0;
$sql = "SELECT fk_paiement, amount FROM llx_amounts_paid WHERE num_paiement = '".$object -> ref."'";
$res_amount = $db->query($sql);
if($res_amount -> num_rows > 0 ){
	while ($amounts = $db->fetch_object($res_amount)) {
		if($amounts -> fk_paiement == 4) $cash += $amounts -> amount;
		elseif($amounts -> fk_paiement == 58) $credit_note += $amounts -> amount;
		elseif($amounts -> fk_paiement == 2) $transfer += $amounts -> amount;
		else $card += $amounts -> amount;
	}
}

// Ecabezado de ticket
// Se va concatenando todo en una variable HTML
$html = '<div style="text-align: center;"><img style="width:70px; height:60px;" src="img/logo_derma.png">
<h1 style="text-align: center; font-size: 12;">Derma Global</h1>
</div>';

$constantforkey = 'CASHDESK_ID_WAREHOUSE'.$_SESSION["takeposterminal"];
$warehouse = new Entrepot($db);
$warehouse->fetch($conf->global->$constantforkey);

// Consulta para obtener el RFC asociado en el almacén
$sql = "select code 
from llx_c_rfc where rowid = ".$warehouse->fk_rfc."";
$resql = $db->query($sql);
$objrfc = $db->fetch_object($resql);

// Datos de sucursal
$html .= '<h6 style="font-size: 8; text-decoration: bold;">'.$warehouse->ref.'</h6>
<br>
<p style="font-size: 8;">'.$warehouse->address.'</p>
<br>
<p style="line-height:0; font-size: 8;">C.P: '.$warehouse->zip.'</p>
<p style="line-height:0; font-size: 8;">'.$warehouse->town.', '.$warehouse->country.'</p>
<p style="line-height:0; font-size: 8;">RFC: '.$objrfc->code.'</p>
<p style="line-height:0; font-size: 8;">'.str_replace("\n", "<br>", $warehouse->description).'</p>
<br>';

// Datos de ticket
if($ref !== "NC")	$html .= '<h1 style="text-align: center; font-size: 12; line-height:0;">TICKET DE VENTA</h1>';
else	$html .= '<h1 style="text-align: center; font-size: 12; line-height:0;">DEVOLUCIÓN</h1>';

$html.='<h1 style="text-align: center; font-size: 11;">'.$object -> ref.'</h1>';

$soc = new Societe($db);
$soc->fetch($object->socid);

$sql = "select lcp.libelle as type 
from llx_paiement_facture lpf join llx_paiement lp on lpf.fk_paiement = lp.rowid 
JOIN llx_c_paiement lcp ON lp.fk_paiement = lcp.id where lpf.fk_facture = ".$facid."";
$resql = $db->query($sql);
$obj2 = $db->fetch_object($resql);
$typep = $obj2 -> type;

$html .= '<p style="line-height:0; font-size: 8;">Fecha y hora: '.dol_print_date($object->date_modification, 'dayhour', 'tzuser').'</p>
<br>
<p style="line-height:0; font-size: 8;">'.$langs->trans("Customer").': '.$soc -> name.'</p>
<br>
<p style="line-height:0; font-size: 8;">Tipo: Contado</p>
<br>
<p style="line-height:0; font-size: 8;">Vendedor: '.$user -> login.'</p>
<br>';

// Información de artículos vendidos
$html .= '<table style="border-top-style: double; width=100%;">

<tr style="font-weight: bold; font-size: 8;">
	<th colspan="4">'.$langs->trans("DESCRIPCIÓN DE PRODUCTO").'</th>
</tr>
<tr style="font-weight: bold; font-size: 8;">
	<th style="text-align: center;">'.strtoupper($langs->trans("Qty")).'</th>
	<th style="text-align: center;">'.strtoupper($langs->trans("Price")).'</th>
	<th style="text-align: center;">'.strtoupper($langs->trans("Descto.")).'</th>
	<th style="text-align: right;">'.strtoupper($langs->trans("TotalTTC")).'</th>
</tr>

<tbody>';

$dis = 0;

foreach ($object->lines as $line)
{
	$sql = "select lp.price_ttc as price, lp.barcode as code, lp.exentoiva as eiva 
	from llx_product lp where lp.rowid = ".$line -> fk_product."";
	$resp = $db->query($sql);
	$obj3 = $db->fetch_object($resp);

	$html .= '<tr>
		<td colspan="4" style="font-size: 8;">';
		// Si tiene IVA incluido se agrega un asterisco
		if($obj3 -> eiva == 0){
			if (!empty($line->product_label)) $html.= ''.$obj3 -> code.' '.$line->product_label.'*';
			else $html.= ''.$obj3 -> code.' '.$line->description.'*';
		}else{
			if (!empty($line->product_label)) $html.= ''.$obj3 -> code.' '.$line->product_label.'';
			else $html.= ''.$obj3 -> code.' '.$line->description.'';
		}
	
	$html.= '</td>
	</tr>
	<tr>';
	$price = ($line->total_ht / ((100 - $line->remise_percent)/100));
	$dis = $price - $line->total_ht + $dis;
	if($ref !== "NC"){
		$html.='
		<td style="font-size: 8; text-align: center;">'.$line->qty.'</td>
		<td style="font-size: 8; text-align: center;">'.price($price).'</td>
		<td style="font-size: 8; text-align: center;">'.$line->remise_percent."%".'</td>
		<td style="font-size: 8; text-align: right;">'.price($line->total_ht, 1).'</td>
		</tr>';
	}else{
		$price2 = (($line->total_ht*-1) / ((100 - $line->remise_percent)/100));
		$total = $line->total_ht*-1;
		$html.='
		<td style="font-size: 8; text-align: center;">'.$line->qty.'</td>
		<td style="font-size: 8; text-align: center;">'.price($price2).'</td>
		<td style="font-size: 8; text-align: center;">'.$line->remise_percent."%".'</td>
		<td style="font-size: 8; text-align: right;">'.price($total).'</td>
		</tr>';
	}
	
}
$html.= '</tbody>
</table>
<br>
<br>';

// Se imprimen los totales
$html.= '<table>
<tr>
    <th colspan="3" style="font-size: 8; text-align: right;">'.strtoupper($langs->trans("TotalHT")).'</th>';
	if($ref !== "NC"){
		$html.='<td style="font-size: 8; text-align: right;">'.price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency).'</td>';
	}else{
		$subtotal = str_replace("-", "", price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency));
		$html.='<td style="font-size: 8; text-align: right;">'.$subtotal.'</td>';
	}
	$html.='</tr>';  
if($conf->global->TAKEPOS_TICKET_VAT_GROUPPED) {
	$vat_groups = array();
	foreach ($object->lines as $line)
	{
		if(!array_key_exists($line->tva_tx, $vat_groups)) {
			$vat_groups[$line->tva_tx] = 0;
		}
		$vat_groups[$line->tva_tx] += $line->total_tva;
	}
	foreach($vat_groups as $key => $val) {
		$html.= '<tr>
		<th colspan="3" style="font-size: 8; text-align: right;">'.strtoupper($langs->trans("VAT")).' '.vatrate($key, 1).'</th>
		<td style="font-size: 8; text-align: right;">'.price($val, 1, '', 1, - 1, - 1, $conf->currency).'</td>
		</tr>';
	}
} else {
	$html.= '<tr>';
	if($ref !== "NC"){
		$html.= '<th colspan="3"  style="font-size: 8; text-align: right;">'.strtoupper($langs->trans("TotalVAT")).':</th>
		<td style="font-size: 8; text-align: right;">'.price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency).'</td>';
	}else{
		$ivatotal = str_replace("-", "", price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency));
		$html.= '<th colspan="3"  style="font-size: 8; text-align: right;">'.strtoupper($langs->trans("INCLUYE IVA POR")).':</th>
		<td style="font-size: 8; text-align: right;">'.$ivatotal.'</td>';
	}
	$html.='</tr>';
}
$html.= '<tr>
<th colspan="3" style="font-size: 8; text-align: right;">'.strtoupper($langs->trans("Descuento")).':</th>';
if($ref !== "NC"){
	$html.='<td style="font-size: 8; text-align: right;">'.price($dis, 1, '', 1, - 1, - 1, $conf->currency).'</td>';
}else{
	$disctotal = str_replace("-", "", price($dis, 1, '', 1, - 1, - 1, $conf->currency));
	$html.='<td style="font-size: 8; text-align: right;">'.$disctotal.'</td>';
}
$html.='</tr>';

if($ref !== "NC"){
	// Calcular el cambio
	$change = ($card + $cash + $credit_note + $transfer) - $object->total_ttc;
	$html.='<tr>
	<th colspan="3" style="font-size: 11; text-align: right; font-weight:bold;">'.strtoupper($langs->trans("TotalTTC")).':</th><td style="font-size: 11;text-align: right; font-weight:bold;">'.price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency).'</td>
	</tr>';
	// Monto con crédito disponible
	if($credit_note > 0 ) $html.='<tr><th colspan="3" style="font-size: 8; text-align: right;">CRÉDITO: </th><td style="font-size: 8;text-align: right;">'.price($credit_note, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	// Monto con tarjeta
	if($card > 0 ) $html.='<tr><th colspan="3" style="font-size: 8; text-align: right;">TARJETA: </th><td style="font-size: 8;text-align: right;">'.price($card, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	// Monto con efectivo
	if($cash > 0)  $html.='<tr><th colspan="3" style="font-size: 8; text-align: right;">EFECTIVO: </th><td style="font-size: 8;text-align: right;">'.price($cash, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	// Monto con transferencia
	if($transfer > 0)  $html.='<tr><th colspan="3" style="font-size: 8; text-align: right;">TRANSFERENCIA: </th><td style="font-size: 8;text-align: right;">'.price($transfer, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	if($change >= 0) $html.='<tr><th colspan="3" style="font-size: 8; text-align: right;">CAMBIO: </th><td style="font-size: 8;text-align: right;">'.price($change, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
	$html.= '</table>';
	$html.='<div style="border-top-style: double;"></div>
	<h1 style="text-align: center; font-size: 11; line-height:0;">Usted ahorró '.price($dis, 1, '', 1, - 1, - 1, $conf->currency).' </h1>
	<div style="border-top-style: double;"></div>
	<h1 style="text-align: center; font-size: 11; line-height:0;">Gracias por su compra</h1>
	<p style="font-size: 8;">Para generar su autofactura, favor de ingresar al siguiente link: 
	<a href="'.DOL_MAIN_URL_ROOT.'/custom/autofactura_ticket/">'.DOL_MAIN_URL_ROOT.'/custom/autofactura_ticket/</a> Cuenta hasta el último día del mes desde su fecha de compra para generarla.';
	$html.= '<p align="justify">Cambio de producto dentro de 72 horas después de su compra.	"Excepto Omi, Dermico y Controlado". <br> <br>
	No hay devolución de efectivo, cualquier cambio o aclaración favor de presentar este comprobante. <br>
	'.str_replace("\n", "<br>", $warehouse->description).'
	</p>';
	$html.='<h1 style="text-align: center; font-size: 11; line-height:0;">Encuesta de satisfacción</h1>
	<br>
	<div style="text-align: center;">
	<img style="width:70px; height:60px;" src="img/qr_encuesta.jpg">
	</div>';

}else{
	$devtotal = str_replace("-", "", price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency));
	$html.='<tr>
	<th colspan="3" style="font-size: 11; text-align: right; font-weight:bold;">'.strtoupper("Total reembolso").':</th><td style="font-size: 11;text-align: right; font-weight:bold;">'.$devtotal.'</td>
	</tr>
	</table>
	<div style="border-top-style: double;"></div>
	<table style="font-size: 10;">
	<tr style="height: 100px;">
	<td colspan="2">Recibí por concepto de devolución la cantidad de: </td>
	<td  style="border-bottom: 1px solid black;"></td>
	</tr>
	<tr style="height: 100px;">
	<td colspan="3"  style="border-bottom: 1px solid black; height:22;"></td>
	</tr>
	<br>
	<tr>
	<td style="width: 60;">Nombre: </td>
	<td colspan="2"  style="border-bottom: 1px solid black; height:22; width: 208;"></td>
	</tr>
	<tr>
	<td colspan="3"  style="border-bottom: 1px solid black; height:22;"></td>
	</tr>
	<tr>
	<br>
	<td>Teléfono: </td>
	<td colspan="2"  style="border-bottom: 1px solid black; height:22;"></td>
	</tr>
	<tr>
	<td colspan="3"  style="border-bottom: 1px solid black; height:22;"></td>
	</tr>
	<tr>
	<br>
	<td>Email: </td>
	<td colspan="2"  style="border-bottom: 1px solid black; height:22;"></td>
	</tr>
	<tr>
	<td colspan="3"  style="border-bottom: 1px solid black; height:22;"></td>
	</tr>
	<tr style="height: 100px;">
	<td colspan="3"  style="height:2;"></td>
	</tr>
	<tr>
	<br>
	<td>Firma: </td>
	<td colspan="2" style="border-bottom: 1px solid black; "></td>
	</tr>
	</table>';
}

$ticket = 0;
while ($ticket <= $numtickets - 1) {
    // Agregar salto de página para cada ticket
    if ($ticket >= 1) {
        $pdf->AddPage();
    }

    $pdf->writeHTML($html, true, false, true, false, '');
    $ticket++;
}

// Close and output the PDF document
$pdf->Output('ticket-' . $object->ref . '.pdf', 'I');

?>

