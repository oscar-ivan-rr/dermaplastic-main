<?php
/**
 * Listado de productos próximos a caducar / caducados (home)
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'donations', 'products', 'stocks'));

require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
if (!$sortorder) $sortorder = 'ASC';
if (!$sortfield) $sortfield = 'pb.eatby';

$search_product = GETPOST('search_product', 'alpha');
$search_almacen = GETPOST('search_almacen', 'int');
$search_lote = GETPOST('search_lote', 'alpha');
$search_cantidad = GETPOST('search_cantidad', 'alpha');
// Aceptar GET o POST (el listado filtra por GET para que el paginado conserve el valor)
$search_months = GETPOST('search_months', 'int');
if (empty($search_months) && isset($_REQUEST['search_months'])) {
	$search_months = (int) $_REQUEST['search_months'];
}
if ($search_months != 3 && $search_months != 6) {
	$search_months = 3;
}

// Fecha limite de caducidad
$date_start_creationday = GETPOST('date_start_creationday', 'int');
$date_start_creationmonth = GETPOST('date_start_creationmonth', 'int');
$date_start_creationyear = GETPOST('date_start_creationyear', 'int');
$date_end_creationmonth = GETPOST('date_end_creationmonth', 'int');
$date_end_creationday = GETPOST('date_end_creationday', 'int');
$date_end_creationyear = GETPOST('date_end_creationyear', 'int');

$action_caducar = GETPOST('action_caducar', 'aZ09');

$form = new Form($db);

/**
 * Construye el SQL base (sin ORDER/LIMIT) del listado de caducados.
 *
 * @param DoliDB $db
 * @param User   $user
 * @return string
 */
function productos_a_caducar_build_sql($db, $user, $search_product, $search_almacen, $search_lote, $search_cantidad, $search_months, $date_start_creationday, $date_start_creationmonth, $date_start_creationyear, $date_end_creationday, $date_end_creationmonth, $date_end_creationyear)
{
	$sql = "SELECT";
	$sql .= " e.rowid as entrepot_id, e.ref as warehouse,";
	$sql .= " p.rowid as fk_product, p.ref as product_ref, p.label as product_label,";
	$sql .= " pb.fk_product_stock, pb.eatby, pb.batch, pb.qty, pl.rowid as id_lote";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_stock as ps ON pb.fk_product_stock = ps.rowid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."entrepot as e ON ps.fk_entrepot = e.rowid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_lot as pl ON pl.batch = pb.batch AND pl.fk_product = p.rowid AND pl.eatby = pb.eatby";
	$sql .= " WHERE 1=1";

	if (empty($user->rights->stock->show_all_warehouses)) {
		$sql .= " AND e.rowid = ".(int) $user->fk_warehouse;
	}
	if ($search_product) {
		$sql .= " AND p.ref LIKE '%".$db->escape($search_product)."%'";
	}
	if ($search_almacen > 0) {
		$sql .= " AND e.rowid = ".(int) $search_almacen;
	}
	if ($search_lote) {
		$sql .= " AND pb.batch LIKE '%".$db->escape($search_lote)."%'";
	}
	if ($search_cantidad !== '' && is_numeric($search_cantidad)) {
		$sql .= " AND pb.qty = ".(float) $search_cantidad;
	}
	if ($date_start_creationyear && !$date_end_creationyear) {
		$sql .= " AND pb.eatby >= '".$db->escape($date_start_creationyear.'-'.$date_start_creationmonth.'-'.$date_start_creationday)."'";
	} elseif (!$date_start_creationyear && $date_end_creationyear) {
		$sql .= " AND pb.eatby <= '".$db->escape($date_end_creationyear.'-'.$date_end_creationmonth.'-'.$date_end_creationday)."'";
	} elseif ($date_start_creationyear && $date_end_creationyear) {
		$sql .= " AND pb.eatby BETWEEN '".$db->escape($date_start_creationyear.'-'.$date_start_creationmonth.'-'.$date_start_creationday)."'";
		$sql .= " AND '".$db->escape($date_end_creationyear.'-'.$date_end_creationmonth.'-'.$date_end_creationday)."'";
	}

	$months = ((int) $search_months === 6) ? 6 : 3;
	// Solo lotes que caducan en los próximos N meses (desde hoy hasta hoy+N)
	$sql .= " AND pb.eatby IS NOT NULL";
	$sql .= " AND DATE(pb.eatby) >= CURDATE()";
	$sql .= " AND DATE(pb.eatby) <= DATE_ADD(CURDATE(), INTERVAL ".$months." MONTH)";

	return $sql;
}

$sql = productos_a_caducar_build_sql(
	$db,
	$user,
	$search_product,
	$search_almacen,
	$search_lote,
	$search_cantidad,
	$search_months,
	$date_start_creationday,
	$date_start_creationmonth,
	$date_start_creationyear,
	$date_end_creationday,
	$date_end_creationmonth,
	$date_end_creationyear
);

// Export XLSX de todos los resultados filtrados
if ($action_caducar == 'export') {
	require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';

	ini_set('memory_limit', '1024M');
	ini_set('max_execution_time', '0');
	if (function_exists('set_time_limit')) {
		@set_time_limit(0);
	}

	$sql_export = $sql.$db->order($sortfield, $sortorder);
	$resql = $db->query($sql_export);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$objPHPExcel = new PHPExcel();
	$sheet = $objPHPExcel->setActiveSheetIndex(0);
	$sheet->setTitle('Caducados');

	$sheet->setCellValue('A1', 'Producto');
	$sheet->setCellValue('B1', 'Almacen');
	$sheet->setCellValue('C1', 'Lote');
	$sheet->setCellValue('D1', 'Fecha Limite de Venta');
	$sheet->setCellValue('E1', 'Cantidad');
	$sheet->setCellValue('F1', 'Próximos a caducar (meses)');
	$sheet->getStyle('A1:F1')->getFont()->setBold(true);

	$rowCount = 2;
	while ($row = $db->fetch_object($resql)) {
		$sheet->setCellValue('A'.$rowCount, $row->product_ref);
		$sheet->setCellValue('B'.$rowCount, $row->warehouse);
		$sheet->setCellValue('C'.$rowCount, $row->batch);
		$sheet->setCellValue('D'.$rowCount, !empty($row->eatby) ? dol_print_date($db->jdate($row->eatby), 'day') : '');
		$sheet->setCellValueExplicit('E'.$rowCount, (float) $row->qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$sheet->setCellValue('F'.$rowCount, (int) $search_months);
		$rowCount++;
	}
	$db->free($resql);

	foreach (range('A', 'F') as $col) {
		$sheet->getColumnDimension($col)->setAutoSize(true);
	}

	$filename = 'Productos_caducados_'.$search_months.'m_'.dol_print_date(dol_now(), '%Y%m%d_%H%M%S').'.xlsx';
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
}

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$sqlforcount = preg_replace('/^SELECT[\s\S]+?\sFROM\s/i', 'SELECT COUNT(*) as nbtotalofrecords FROM ', $sql, 1);
	$rescount = $db->query($sqlforcount);
	if ($rescount) {
		$objcount = $db->fetch_object($rescount);
		$nbtotalofrecords = (int) $objcount->nbtotalofrecords;
		$db->free($rescount);
	}
	if ($limit > 0 && ($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
}

$sql_list = $sql.$db->order($sortfield, $sortorder).$db->plimit($limit + 1, $offset);
$resql = $db->query($sql_list);

$param = '';
if ($search_product) $param .= '&search_product='.urlencode($search_product);
if ($search_almacen > 0) $param .= '&search_almacen='.urlencode($search_almacen);
if ($search_lote) $param .= '&search_lote='.urlencode($search_lote);
if ($search_cantidad !== '') $param .= '&search_cantidad='.urlencode($search_cantidad);
if ($search_months) $param .= '&search_months='.urlencode($search_months);
if ($date_start_creationday) $param .= '&date_start_creationday='.urlencode($date_start_creationday);
if ($date_start_creationmonth) $param .= '&date_start_creationmonth='.urlencode($date_start_creationmonth);
if ($date_start_creationyear) $param .= '&date_start_creationyear='.urlencode($date_start_creationyear);
if ($date_end_creationday) $param .= '&date_end_creationday='.urlencode($date_end_creationday);
if ($date_end_creationmonth) $param .= '&date_end_creationmonth='.urlencode($date_end_creationmonth);
if ($date_end_creationyear) $param .= '&date_end_creationyear='.urlencode($date_end_creationyear);

if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<div style="overflow-y: auto; width: 100%; height:100%;">';

	print '<form method="POST" id="FormularioExportacionCaducados" action="'.$_SERVER['PHP_SELF'].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action_caducar" value="export">';
	print '<input type="hidden" name="search_product" value="'.dol_escape_htmltag($search_product).'">';
	print '<input type="hidden" name="search_almacen" value="'.((int) $search_almacen).'">';
	print '<input type="hidden" name="search_lote" value="'.dol_escape_htmltag($search_lote).'">';
	print '<input type="hidden" name="search_cantidad" value="'.dol_escape_htmltag($search_cantidad).'">';
	print '<input type="hidden" name="search_months" value="'.((int) $search_months).'">';
	print '<input type="hidden" name="date_start_creationday" value="'.dol_escape_htmltag($date_start_creationday).'">';
	print '<input type="hidden" name="date_start_creationmonth" value="'.dol_escape_htmltag($date_start_creationmonth).'">';
	print '<input type="hidden" name="date_start_creationyear" value="'.dol_escape_htmltag($date_start_creationyear).'">';
	print '<input type="hidden" name="date_end_creationday" value="'.dol_escape_htmltag($date_end_creationday).'">';
	print '<input type="hidden" name="date_end_creationmonth" value="'.dol_escape_htmltag($date_end_creationmonth).'">';
	print '<input type="hidden" name="date_end_creationyear" value="'.dol_escape_htmltag($date_end_creationyear).'">';
	print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
	print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
	print '</form>';

	print '<form method="GET" id="formCaducados" action="'.$_SERVER['PHP_SELF'].'">'."\n";
	print '<input type="hidden" name="mainmenu" value="home">';
	print '<input type="hidden" name="leftmenu" value="home">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="0">';

	print '<h3 style="color:red;">Productos próximos a caducar ( '.$nbtotalofrecords.' ) '.img_warning('Lote próximo a caducar').'</h3>';

	print '<div style="margin-bottom:10px;">';
	print '<label for="search_months"><b>Próximos a caducar:</b></label> ';
	print '<select class="flat" id="search_months" name="search_months" onchange="this.form.submit();">';
	print '<option value="3"'.($search_months == 3 ? ' selected' : '').'>3 meses</option>';
	print '<option value="6"'.($search_months == 6 ? ' selected' : '').'>6 meses</option>';
	print '</select>';
	print ' <button type="button" class="butAction" id="exportar_caducados">Exportar Excel</button>';
	print '</div>';

	print '<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#exportar_caducados").on("click", function() {
			jQuery("#FormularioExportacionCaducados input[name=search_months]").val(jQuery("#search_months").val());
			jQuery("#FormularioExportacionCaducados").submit();
		});
	});
	</script>';

	print_barre_liste('', $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '');
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">'."\n";

	// Filters
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input class="flat" size="10" type="text" name="search_product" value="'.dol_escape_htmltag($search_product).'"></td>';
	print '<td class="liste_titre">';
	if (!empty($user->rights->stock->show_all_warehouses)) {
		$formproduct = new FormProduct($db);
		print $formproduct->selectWarehouses($search_almacen, 'search_almacen', 'warehouseopen', 1);
	}
	print '</td>';
	print '<td class="liste_titre"><input class="flat" size="10" type="text" name="search_lote" value="'.dol_escape_htmltag($search_lote).'"></td>';

	$date_start_creation = $date_start_creationyear ? dol_mktime(0, 0, 0, $date_start_creationmonth, $date_start_creationday, $date_start_creationyear) : null;
	$date_end_creation = $date_end_creationyear ? dol_mktime(0, 0, 0, $date_end_creationmonth, $date_end_creationday, $date_end_creationyear) : null;
	print '<td>';
	print 'Desde<br>'.$form->select_date($date_start_creation, 'date_start_creation', 0, 0, 1, '', 1, 0, 1);
	print '<br>Hasta<br>'.$form->select_date($date_end_creation, 'date_end_creation', 0, 0, 1, '', 1, 0, 1);
	print '</td>';

	print '<td class="liste_titre"><input class="flat" size="10" type="text" name="search_cantidad" value="'.dol_escape_htmltag($search_cantidad).'"></td>';
	print '<td class="liste_titre center"><input type="submit" class="button" value="Filtrar"></td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre('Producto', $_SERVER['PHP_SELF'], 'p.ref', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Almacen', $_SERVER['PHP_SELF'], 'e.ref', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Lote', $_SERVER['PHP_SELF'], 'pb.batch', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Fecha Limite de Venta', $_SERVER['PHP_SELF'], 'pb.eatby', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Cant.', $_SERVER['PHP_SELF'], 'pb.qty', '', $param, '', $sortfield, $sortorder);
	print '<td></td>';
	print "</tr>\n";

	$object = new Product($db);
	$product_lot_static = new Productlot($db);

	while ($i < min($num, $limit)) {
		$objp = $db->fetch_object($resql);

		$object->id = $objp->fk_product;
		$object->ref = $objp->product_ref;
		$object->label = $objp->product_label;

		$product_lot_static->id = $objp->id_lote;
		$product_lot_static->batch = $objp->batch;
		$product_lot_static->eatby = $objp->eatby;
		$product_lot_static->fk_product = $objp->fk_product;

		print '<tr class="oddeven">';
		print '<td>'.$object->getNomUrl(1).'</td>';
		print '<td>'.dol_escape_htmltag($objp->warehouse).'</td>';
		print '<td>'.$product_lot_static->getNomUrl(1).'</td>';
		print '<td>'.dol_print_date($db->jdate($objp->eatby), 'day').'</td>';
		print '<td>'.$objp->qty.'</td>';
		print '<td></td>';
		print '</tr>';

		$i++;
	}
	print '</table>';
	print '</div>';
	print '</form>'."\n";
	print '</div>';
	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();
?>
