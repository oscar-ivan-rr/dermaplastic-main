<?php
/**
 * Export XLSX - Reporte Pedidos/Envíos (devoluciones)
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';

if (empty($user->rights->commande->lire)) {
	accessforbidden();
}

$sql = base64_decode(GETPOST('sqlexport', 'none'));
$action = GETPOST('action', 'aZ09');

if ($action != 'generate_report' || empty($sql)) {
	accessforbidden();
}

// Evitar que se reintroduzca un LIMIT de paginación por error
$sql = preg_replace('/\s+LIMIT\s+\d+(\s*,\s*\d+)?\s*$/i', '', trim($sql));

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->setActiveSheetIndex(0);
$sheet->setTitle('Pedidos-Envios');

$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'Reporte de Pedidos/Envíos');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('9BC2E6');

$headers = array(
	'A3' => 'Cliente',
	'B3' => 'Pedido',
	'C3' => 'Fecha de Pedido',
	'D3' => 'Total de Piezas',
	'E3' => 'Subtotal',
	'F3' => 'Importe IVA',
	'G3' => 'Importe Pedido',
);
foreach ($headers as $cell => $label) {
	$sheet->setCellValue($cell, $label);
}
$sheet->getStyle('A3:G3')->getFont()->setBold(true);
$sheet->getStyle('A3:G3')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

$rowCount = 4;
$iva_total = 0;
$total = 0;
$subtotal = 0;
$total_qty = 0;

while ($row = $db->fetch_object($resql)) {
	$iva_total += (float) $row->total_tva;
	$total += (float) $row->total_ttc;
	$subtotal += (float) $row->total_ht;
	$total_qty += (float) $row->total_qty;

	$fecha = $row->date_commande;
	if (!empty($fecha) && $fecha != '0000-00-00' && $fecha != '0000-00-00 00:00:00') {
		$fecha = dol_print_date($db->jdate($row->date_commande), 'day');
	} else {
		$fecha = '';
	}

	$sheet->setCellValue('A'.$rowCount, $row->name);
	$sheet->setCellValue('B'.$rowCount, $row->ref);
	$sheet->setCellValue('C'.$rowCount, $fecha);
	$sheet->setCellValueExplicit('D'.$rowCount, (float) $row->total_qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('E'.$rowCount, (float) $row->total_ht, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('F'.$rowCount, (float) $row->total_tva, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('G'.$rowCount, (float) $row->total_ttc, PHPExcel_Cell_DataType::TYPE_NUMERIC);

	$rowCount++;
}

$db->free($resql);

// Totales
$sheet->setCellValue('A'.$rowCount, 'Total');
$sheet->mergeCells('A'.$rowCount.':C'.$rowCount);
$sheet->getStyle('A'.$rowCount)->getFont()->setBold(true);
$sheet->setCellValueExplicit('D'.$rowCount, (float) $total_qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('E'.$rowCount, (float) $subtotal, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('F'.$rowCount, (float) $iva_total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('G'.$rowCount, (float) $total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->getStyle('A'.$rowCount.':G'.$rowCount)->getFont()->setBold(true);

$sheet->getStyle('E4:G'.$rowCount)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('D4:D'.$rowCount)->getNumberFormat()->setFormatCode('#,##0');

foreach (range('A', 'G') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'Reporte_Pedidos_Envios_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S').'.xlsx';

if (ob_get_length()) {
	ob_end_clean();
}

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');
header('Pragma: public');
$objWriter->save('php://output');
exit;
