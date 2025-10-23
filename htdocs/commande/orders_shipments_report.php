<?php
require '../main.inc.php';

$sql = base64_decode($_POST['sqlexport']);
$action = $_POST['action'];

header('Content-type: application/vnd.ms-excel charset=iso-8859-1');
header('Content-disposition: attachment; filename="Reporte de Pedidos-Envíos.xls"');
header("Pragma: no-cache");
header("Expires: 0");

$table = "&nbsp;";
$table .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8;">';
$table .= '<table class="liste" style="position: relative; bottom: 30px; font-size: 1.3em;">';
$table .= '<tr>';
$table .= '<th colspan="15" style="background-color: #9BC2E6;">Reporte de Pedidos/Envíos</th>';
$table .= '</tr>';
$table .= '<tr><th></th></tr>';
$table .= '<tr>';
$table .= '<th colspan="2" style="border: 2px solid black;">Cliente</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Pedido</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Fecha de Pedido</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Total de Piezas</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Subtotal</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Importe IVA</th>';
$table .= '<th colspan="2" style="border: 2px solid black;">Importe Pedido</th>';
$table .= '</tr>';
//Genera reporte Excel
if ($action == 'generate_report') {
    try {
        $resql = $db->query($sql);
        if ($resql > 0) {
            $iva_total = 0;
            $total = 0;
            $subtotal = 0;
            $total_qty = 0;
            while ($row = $db->fetch_object($resql)) {
                $iva_total += $row->total_tva;
                $total += $row->total_ttc;
                $subtotal += $row->total_ht;
                $total_qty += $row->total_qty;
                $table .= '<tr>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= $row->name . '</td>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= $row->ref . '</td>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= $row->date_commande . '</td>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= $row->total_qty . '</td>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= price($row->total_ht) . '</td>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= price($row->total_tva) . '</td>';
                $table .= '<td colspan="2" style="border: 1px solid gray;">';
                $table .= price($row->total_ttc) . '</td>';
                $table .= '</tr>';
            }
            $table .= '<tr>';
        }
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
}
$table .= '<td colspan="6" style="border: 1px solid gray; text-align: center;"><b>Total</b></td>';
$table .= '<td colspan="2" style="border: 1px solid gray;">';
$table .= $total_qty . '</td>';
$table .= '<td colspan="2" style="border: 1px solid gray;">';
$table .= price($subtotal) . '</td>';
$table .= '<td colspan="2" style="border: 1px solid gray;">';
$table .= price($iva_total) . '</td>';
$table .= '<td colspan="2" style="border: 1px solid gray;">';
$table .= price($total) . '</td>';
$table .= '</tr>';
$table .= '</table>';
$table = utf8_decode($table);
echo $table;
