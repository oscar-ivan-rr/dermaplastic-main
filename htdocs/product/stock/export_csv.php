<?php
/**
 * Export XLSX de movimientos de stock.
 * Usa solo campos de la query (sin modelos Dolibarr) y exporta todas las filas.
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';

if (empty($user->rights->stock->mouvement->lire)) {
	accessforbidden();
}

$langs->loadLangs(array('main', 'companies', 'users', 'stocks'));

$sql = GETPOST('sql', 'none');
if ($sql !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $sql)) {
	$decoded = base64_decode($sql, true);
	if ($decoded !== false) {
		$sql = $decoded;
	}
}

if (empty($sql)) {
	accessforbidden();
}

// Quitar LIMIT de paginación por si llega en la query
$sql = preg_replace('/\s+LIMIT\s+\d+(\s*,\s*\d+)?\s*$/i', '', trim($sql));

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '0');
if (function_exists('set_time_limit')) {
	@set_time_limit(0);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$typeLabels = array(
	'0' => $langs->trans('StockIncreaseAfterCorrectTransfer'),
	'1' => $langs->trans('StockDecreaseAfterCorrectTransfer'),
	'2' => $langs->trans('StockDecrease'),
	'3' => $langs->trans('StockIncrease'),
);

$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->setActiveSheetIndex(0);
$sheet->setTitle('Movimientos');

$headers = array(
	'A1' => 'Referencia',
	'B1' => 'Fecha',
	'C1' => 'Código de barras',
	'D1' => 'Producto',
	'E1' => 'Almacén',
	'F1' => 'Autor',
	'G1' => 'Etiqueta de Movimiento',
	'H1' => 'Tipo',
	'I1' => 'Cantidad',
	'J1' => 'Precio de Compra',
	'K1' => 'Subtotal',
	'L1' => 'IVA',
	'M1' => 'Total',
);
foreach ($headers as $cell => $label) {
	$sheet->setCellValue($cell, $label);
}
$sheet->getStyle('A1:M1')->getFont()->setBold(true);

$rowCount = 2;
$subtotal = 0.0;
$tva = 0.0;
$total = 0.0;

while ($row = $db->fetch_object($resql)) {
	$author = trim(($row->firstname ? $row->firstname.' ' : '').($row->lastname ? $row->lastname : ''));
	if ($author === '' && !empty($row->login)) {
		$author = $row->login;
	}

	$typeKey = isset($row->type_mouvement) ? (string) $row->type_mouvement : '';
	$type = isset($typeLabels[$typeKey]) ? $typeLabels[$typeKey] : $typeKey;

	$fecha = '';
	if (!empty($row->datem) && $row->datem != '0000-00-00 00:00:00') {
		$fecha = dol_print_date($db->jdate($row->datem), 'dayhour', 'tzuserrel');
	}

	$qty = (float) $row->qty;
	$price = (float) $row->price;
	$rowSubtotal = isset($row->subtotal) ? (float) $row->subtotal : abs($qty * $price);
	$rowTva = isset($row->tva) ? (float) $row->tva : 0.0;
	$rowTotal = isset($row->total) ? (float) $row->total : ($rowSubtotal + $rowTva);

	$sheet->setCellValue('A'.$rowCount, $row->mid);
	$sheet->setCellValue('B'.$rowCount, $fecha);
	$sheet->setCellValue('C'.$rowCount, $row->barcode);
	$sheet->setCellValue('D'.$rowCount, $row->product_ref);
	$sheet->setCellValue('E'.$rowCount, $row->warehouse_ref);
	$sheet->setCellValue('F'.$rowCount, $author);
	$sheet->setCellValue('G'.$rowCount, $row->label);
	$sheet->setCellValue('H'.$rowCount, $type);
	$sheet->setCellValueExplicit('I'.$rowCount, $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('J'.$rowCount, $price, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('K'.$rowCount, $rowSubtotal, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('L'.$rowCount, $rowTva, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('M'.$rowCount, $rowTotal, PHPExcel_Cell_DataType::TYPE_NUMERIC);

	$subtotal += $rowSubtotal;
	$tva += $rowTva;
	$total += $rowTotal;
	$rowCount++;
}

$db->free($resql);

$sheet->setCellValue('A'.$rowCount, 'Total');
$sheet->getStyle('A'.$rowCount)->getFont()->setBold(true);
$sheet->setCellValueExplicit('K'.$rowCount, $subtotal, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('L'.$rowCount, $tva, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('M'.$rowCount, $total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->getStyle('A'.$rowCount.':M'.$rowCount)->getFont()->setBold(true);

$sheet->getStyle('J2:M'.$rowCount)->getNumberFormat()->setFormatCode('#,##0.00');
$sheet->getStyle('I2:I'.$rowCount)->getNumberFormat()->setFormatCode('#,##0.####');

foreach (range('A', 'M') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'Movimientos_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S').'.xlsx';

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
