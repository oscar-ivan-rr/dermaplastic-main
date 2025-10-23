<?php
/**
 *  \file       htdocs/product/custom/export_warehouse_report.php
 *  \ingroup    product
 *  \brief      reporte exportado de ventas por sucursal
 */
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
$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));

$sql = GETPOST('sqlexport', 'alpha');
$sql2 = GETPOST('sqlexport2', 'alpha');
$sql_comm_fourn = GETPOST('sql_comm_fourn', 'alpha');
$dateinicio  = GETPOST('dateinicio');
$datefinal   = GETPOST('datefinal');
$name_warehouse= GETPOST('nom', 'alpha');
$rfc_label= GETPOST('rfc_label', 'alpha');
$date = "";
if (!empty($dateinicio) && !empty($datefinal)) $date = dol_print_date($dateinicio, 'day')." - ".dol_print_date($datefinal, 'day');
elseif (!empty($dateinicio)) $date = dol_print_date($dateinicio, 'day')." - ".dol_print_date(dol_now('tzuser'), 'day');
elseif (!empty($datefinal)) $date = "Hasta ".dol_print_date($datefinal, 'day');

$resql = $db->query($sql);
$resql2 = $db->query($sql2);
$resql_comm_fourn = $db->query($sql_comm_fourn);

header('Content-type: application/vnd.ms-excel charset=iso-8859-1');
header('Content-disposition: attachment; filename="Reporte '.$name_warehouse.'.xls"');
header("Pragma: no-cache");
header("Expires: 0");

$table = "&nbsp;";
$table .='<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

// Ventas
$table .= '<table class="liste" style="position: relative; bottom: 30px; font-size: 1.3em;">';
$table .= '<tr class="liste_titre">';
$table .= '<th class="rightliste_titre" colspan="2" style="background-color: #9BC2E6;">'.$date.'</th>';
$table .= '<th class="rightliste_titre">&nbsp;</th>';
$table .= '<th class="rightliste_titre" colspan="9" style="background-color: #FFFF00;">'.$rfc_label.'</th>';
$table .= '</tr>';
$table .= '<tr class="liste_titre">';
$table .= '<th class="rightliste_titre" colspan="2" style="background-color: #FFFF00;">Resumen</th>';
$table .= '<th class="rightliste_titre">&nbsp;</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Productos</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Precio promedio</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Total</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Efectivo</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Tarjeta crédito</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Tarjeta débito</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Transferencia</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">IVA</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Ventas con IVA</th>';
$table .= '</tr>';
$table .= '<tr>';
$table .= '<th class="rightliste_titre" colspan="2">'.$name_warehouse.'</th>';
$table .= '<th class="rightliste_titre" colspan="10">&nbsp;</th>';
$table .= '</tr>';

if ($resql > 0){
    $row = $db->fetch_object($resql);
    $table .= '<tr>';
    $table .= '<th class="rightliste_titre" colspan="2">&nbsp;</th>';
    $table .= '<th class="rightliste_titre">Totales</th>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= number_format($row->qty).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->average).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->total_ht).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->cash_total).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->credit_total).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->debit_total).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->transfer_total).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->total_tva).'</td>';$table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row->total_ht + $row->total_tva).'</td>';
    
    $table .= '</tr>';
}
$table .= '</table>';

$table .= '<br>';
$table .= '<br>';

// Compras a proveedor
$table .= '<table class="liste" style="position: relative; bottom: 30px; font-size: 1.3em;">';
$table .= '<tr class="liste_titre">';
$table .= '<th class="rightliste_titre" colspan="2" style="background-color: #FFFF00;">Compras a proveedores</th>';
$table .= '<th class="rightliste_titre">&nbsp;</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Total de compras</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Subtotal</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">IVA</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black;">Total</th>';
$table .= '</tr>';
$table .= '<tr>';
$table .= '<th class="rightliste_titre" colspan="2">'.$name_warehouse.'</th>';
$table .= '<th class="rightliste_titre" colspan="10">&nbsp;</th>';
$table .= '</tr>';

if ($resql_comm_fourn > 0){
    $row_comm_fourn = $db->fetch_object($resql_comm_fourn);
    $table .= '<tr>';
    $table .= '<th class="rightliste_titre" colspan="2">&nbsp;</th>';
    $table .= '<th class="rightliste_titre">Totales</th>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= $row_comm_fourn->qty.'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row_comm_fourn->subtotal).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row_comm_fourn->tva).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row_comm_fourn->total).'</td>';
    
    $table .= '</tr>';
}
$table .= '</table>';

$table .= '<br>';
$table .= '<br>';

// Costos
$table .= '<table class="liste" style="position: relative; bottom: 30px; font-size: 1.3em;">';
$table .= '<tr class="liste_titre">';
$table .= '<th class="rightliste_titre" colspan="2" style="background-color: #FFFF00;">Costo de inventario con IVA</th>';
$table .= '<th class="rightliste_titre">&nbsp;</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black; background-color: #FFFF00;">Total</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black; background-color: #FFFF00;">Gravado</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black; background-color: #FFFF00;">No gravado</th>';
$table .= '<th class="rightliste_titre" style="border: 2px solid black; background-color: #FFFF00;">IVA</th>';
$table .= '</tr>';
$table .= '<tr class="liste_titre">';
$table .= '<th class="rightliste_titre" colspan="2">'.$name_warehouse.'</th>';
$table .= '<th class="rightliste_titre">&nbsp;</th>';
$table .= '<th class="rightliste_titre" colspan=4>&nbsp;</th>';
$table .= '</tr>';
$table .= '<tr>';
$table .= '<th class="rightliste_titre" colspan="2">Total inventario con IVA</th>';
$table .= '<th class="rightliste_titre">&nbsp;</th>';

if($resql2 > 0){
    $row2 = $db->fetch_object($resql2);
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row2->gravado + $row2->no_gravado + $row2->iva) .'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row2->gravado).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row2->no_gravado).'</td>';
    $table .='<td class="rightliste_titre" 
                style="border-top: 1px solid gray; 
                border-right: 1px solid gray; 
                border-left: 1px solid gray; 
                border-bottom: 2px double gray;">';
    $table .= price($row2->iva).'</td>';
}
$table .= '</tr>';
$table .= '</table>';
echo $table;

