<?php
/**
 * Export XLSX de pedidos a proveedor.
 * Exporta todos los resultados de la query filtrada (sin paginación).
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';

if (empty($user->rights->fournisseur->commande->lire)) {
	accessforbidden();
}

$langs->loadLangs(array('orders', 'companies', 'bills', 'projects', 'stocks'));

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

$statusLabels = array(
	0 => $langs->trans('StatusSupplierOrderDraft'),
	1 => $langs->trans('StatusSupplierOrderValidated'),
	2 => $langs->trans('StatusSupplierOrderApproved'),
	3 => empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)
		? $langs->trans('StatusSupplierOrderOnProcess')
		: $langs->trans('StatusSupplierOrderOnProcessWithValidation'),
	4 => $langs->trans('StatusSupplierOrderReceivedPartially'),
	5 => $langs->trans('Recibido completo'),
	6 => $langs->trans('StatusSupplierOrderCanceled'),
	7 => $langs->trans('StatusSupplierOrderCanceled'),
	9 => $langs->trans('StatusSupplierOrderRefused'),
);

$objPHPExcel = new PHPExcel();
$sheet = $objPHPExcel->setActiveSheetIndex(0);
$sheet->setTitle('Pedidos proveedor');

$headers = array(
	'A1' => $langs->trans('Ref'),
	'B1' => $langs->trans('RefOrderSupplierShort'),
	'C1' => $langs->trans('ProjectRef'),
	'D1' => $langs->trans('AuthorRequest'),
	'E1' => $langs->trans('ThirdParty'),
	'F1' => $langs->trans('Town'),
	'G1' => $langs->trans('Zip'),
	'H1' => $langs->trans('Warehouse'),
	'I1' => $langs->trans('RFC'),
	'J1' => $langs->trans('OrderDateShort'),
	'K1' => $langs->trans('DateDeliveryPlanned'),
	'L1' => $langs->trans('AmountHT'),
	'M1' => $langs->trans('AmountVAT'),
	'N1' => $langs->trans('AmountTTC'),
	'O1' => $langs->trans('Status'),
	'P1' => $langs->trans('Billed'),
);
foreach ($headers as $cell => $label) {
	$sheet->setCellValue($cell, $label);
}
$sheet->getStyle('A1:P1')->getFont()->setBold(true);

$rowCount = 2;
$total_ht = 0.0;
$total_vat = 0.0;
$total_ttc = 0.0;

while ($obj = $db->fetch_object($resql)) {
	$author = trim(($obj->firstname ? $obj->firstname.' ' : '').($obj->lastname ? $obj->lastname : ''));
	if ($author === '' && !empty($obj->login)) {
		$author = $obj->login;
	}

	$statusKey = (int) $obj->fk_statut;
	$status = isset($statusLabels[$statusKey]) ? $statusLabels[$statusKey] : (string) $statusKey;
	if (!empty($obj->billed)) {
		$status .= ' - '.$langs->trans('Billed');
	}

	$date_commande = '';
	if (!empty($obj->date_commande) && $obj->date_commande != '0000-00-00' && $obj->date_commande != '0000-00-00 00:00:00') {
		$date_commande = dol_print_date($db->jdate($obj->date_commande), 'day');
	}
	$date_delivery = '';
	if (!empty($obj->date_delivery) && $obj->date_delivery != '0000-00-00' && $obj->date_delivery != '0000-00-00 00:00:00') {
		$date_delivery = dol_print_date($db->jdate($obj->date_delivery), 'day');
	}

	$ht = (float) $obj->total_ht;
	$vat = (float) $obj->total_tva;
	$ttc = (float) $obj->total_ttc;

	$sheet->setCellValue('A'.$rowCount, $obj->ref);
	$sheet->setCellValue('B'.$rowCount, $obj->ref_supplier);
	$sheet->setCellValue('C'.$rowCount, $obj->project_ref);
	$sheet->setCellValue('D'.$rowCount, $author);
	$sheet->setCellValue('E'.$rowCount, $obj->name);
	$sheet->setCellValue('F'.$rowCount, $obj->town);
	$sheet->setCellValue('G'.$rowCount, $obj->zip);
	$sheet->setCellValue('H'.$rowCount, $obj->warehouse);
	$sheet->setCellValue('I'.$rowCount, $obj->rfc);
	$sheet->setCellValue('J'.$rowCount, $date_commande);
	$sheet->setCellValue('K'.$rowCount, $date_delivery);
	$sheet->setCellValueExplicit('L'.$rowCount, $ht, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('M'.$rowCount, $vat, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValueExplicit('N'.$rowCount, $ttc, PHPExcel_Cell_DataType::TYPE_NUMERIC);
	$sheet->setCellValue('O'.$rowCount, $status);
	$sheet->setCellValue('P'.$rowCount, !empty($obj->billed) ? $langs->trans('Yes') : $langs->trans('No'));

	$total_ht += $ht;
	$total_vat += $vat;
	$total_ttc += $ttc;
	$rowCount++;
}

$db->free($resql);

$sheet->setCellValue('A'.$rowCount, $langs->trans('Total'));
$sheet->getStyle('A'.$rowCount)->getFont()->setBold(true);
$sheet->setCellValueExplicit('L'.$rowCount, $total_ht, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('M'.$rowCount, $total_vat, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->setCellValueExplicit('N'.$rowCount, $total_ttc, PHPExcel_Cell_DataType::TYPE_NUMERIC);
$sheet->getStyle('A'.$rowCount.':P'.$rowCount)->getFont()->setBold(true);

$sheet->getStyle('L2:N'.$rowCount)->getNumberFormat()->setFormatCode('#,##0.00');

foreach (range('A', 'P') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'Pedidos_proveedor_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S').'.xlsx';

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
