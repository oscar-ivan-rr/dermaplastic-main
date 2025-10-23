<?php
require '../../main.inc.php';

$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));

$filename = "reporte_de_movimientos.xls";
$arrayfields    = (array) json_decode($_POST['datos_a_enviar']);
foreach ($arrayfields as $key => $value)
    $arrayfields[$key] = (array) $value;
$sql    = $_POST['sql'];

$resql = $db->query($sql);

$num = $db->num_rows($resql);

header('Content-type: application/vnd.ms-excel charset=iso-8859-1');
header("Content-Disposition: attachment; filename=$filename"); //Indica el nombre del archivo resultante
header("Pragma: no-cache");
header("Expires: 0");

$table = '';
$columnas=0;
foreach ($arrayfields as $key => $value) {
    if (!empty($value['checked'])) {
        $columnas++;
    }
}
$table.='<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table class="tagtable liste listwithfilterbefore" >'."\n";

$table.='<tr class="liste_titre">';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['p.ref']['label']).'</th>';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['m.inventorycode']['label']).'</th>';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['m.value']['label']).'</th>';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['m.price']['label']).'</th>';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['subtotal']['label']).'</th>';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['tva']['label']).'</th>';
$table.='<th style="border:1px solid black;">'.$langs->trans($arrayfields['total']['label']).'</th>';
$table.="</tr>\n";


if ($num > 0)
{
    $i = 0;
    $subtotal = 0;
    $total_tva = 0;
    $total = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($resql);

        $table .= '<tr>';
        // Product ref
        $table .= '<td style="border:1px solid black;">'.$objp->product_ref.'</td>';

        // Inventory code
        $table .= '<td style="border:1px solid black;">'.$objp->inventorycode.'</td>';

        // Qty
        $table .= '<td style="border:1px solid black;">';
        if ($objp->qty > 0) $table .= '+';
        $table .= $objp->qty;
        $table .= '</td>';

        // Price
        $table .= '<td style="border:1px solid black;">';
        if ($objp->price != 0) $table .= price($objp->price);
        $table .= '</td>';

        // Subtotal
        $table .= '<td style="border:1px solid black;">';
        $table .= price($objp->subtotal);
        $table .= '</td>';
        $subtotal += $objp->subtotal;

        // IVA
        $table .= '<td style="border:1px solid black;">';
        $table .= price($objp->tva);
        $table .= '</td>';
        $total_tva += $objp->tva;

        // Total
        $table .= '<td style="border:1px solid black;">';
        if ($objp->total != 0) $table .= price($objp->total);
        $table .= '</td>';
        $total += $objp->total;

        $table .= "</tr>\n";

        $i++;
    }
    // Show total line
    $table .= '<tr>';
    $table .= '<td style="background-color: #EFEFFF; border:1px solid black;" colspan=4><b>'.$langs->trans("Total").'</b></td>';
    $table .= '<td style="background-color: #EFEFFF; border:1px solid black;"><b>'.price($subtotal).'</b></td>';
    $table .= '<td style="background-color: #EFEFFF; border:1px solid black;"><b>'.price($total_tva).'</b></td>';
    $table .= '<td style="background-color: #EFEFFF; border:1px solid black;"><b>'.price($total).'</b></td>';
    $table .= '</tr>';
}

$db->free($resql);

$table.="</table>\n";

echo $table;

