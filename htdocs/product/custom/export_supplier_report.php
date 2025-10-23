<?php
/**
 *  \file       htdocs/product/custom/export_supplier_report.php
 *  \ingroup    product
 *  \brief      reporte exportado de compras a proveedor
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));

$sql = GETPOST('sqlexport', 'alpha');
$sql2 = GETPOST('sqlexport2', 'alpha');
$dateinicio  = GETPOST('dateinicio');
$datefinal   = GETPOST('datefinal');
$name_warehouse= GETPOST('nom', 'alpha');
$rfc_label= GETPOST('rfc_label', 'alpha');
$date = "";
if (empty($dateinicio) && empty($datefinal)) $date = "Hasta ".dol_print_date(dol_now('tzuser'), 'day');
elseif (!empty($dateinicio) && !empty($datefinal)) $date = dol_print_date($dateinicio, 'day')." - ".dol_print_date($datefinal, 'day');
elseif (!empty($dateinicio)) $date = dol_print_date($dateinicio, 'day')." - ".dol_print_date(dol_now('tzuser'), 'day');
elseif (!empty($datefinal)) $date = "Hasta ".dol_print_date($datefinal, 'day');

$resql = $db->query($sql);

header('Content-type: application/vnd.ms-excel charset=iso-8859-1');
header('Content-disposition: attachment; filename="Reporte compras a proveedor de '.$name_warehouse.'.xls"');
header("Pragma: no-cache");
header("Expires: 0");

$table = "&nbsp;";
$table .='<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

// Ventas
$table .= '<table class="liste" style="position: relative; bottom: 30px; font-size: 1.3em;">';
$table .= '<tr>';
$table .= '<th colspan="8" style="background-color: #9BC2E6;">Reporte de compras a proveedor - '.$name_warehouse.'</th>';
$table .= '</tr>';
$table .= '<tr>';
$table .= '<th class="rightliste_titre" colspan="8" style="background-color: #9BC2E6;">'.$date.'</th>';
$table .= '</tr>';
$table .= '<tr><th></th></tr>';
$table .= '<tr>';
$table .= '<th></th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Ref.</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">IVA</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Total</th>';
$table .= '</tr>';

if ($resql > 0){
    $iva_total = 0;
	$total = 0;
    while($row = $db->fetch_object($resql)){
        $iva_total += $row->iva;
		$total += $row->total;
        $table .= '<tr>';
        $table .= '<td></td>';
        $table .= '<td colspan="2" style="border: 1px solid gray;">';
        $table .= $row->ref.'</td>';
        $table .= '<td colspan="2" style="border: 1px solid gray;">';
        $table .= price($row->iva).'</td>';
        $table .= '<td colspan="2" style="border: 1px solid gray;">';
        $table .= price($row->total).'</td>';
        $table .= '</tr>';
    }
}
$table .= '<tr>';
$table .= '<td></td>';
$table .= '<td colspan="2" style="background-color: #EBEBEE; border: 1px solid gray;">Total</td>';
$table .= '<td colspan="2" style="background-color: #EBEBEE; border: 1px solid gray;">' . price($iva_total) . '</td>';
$table .= '<td colspan="2" style="background-color: #EBEBEE; border: 1px solid gray;">' . price($total) . '</td>';
$table .= '</tr>';
$table .= '</table>';
echo $table;

