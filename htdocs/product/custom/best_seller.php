<?php
/*
 * Luis Antonio Ramírez Garcia
 * antonio.ramirez@lionintel.com
 */

/**
 *  \file       htdocs/product/custom/best_seller.php
 *  \ingroup    product
 *  \brief      reporte de productos en facturas de proveedor y a cliente
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
ini_set('display_errors', '0');
date_default_timezone_set("GMT");
global $conf, $langs;
$langs->loadLangs(array('main', 'companies', 'bills', 'product', 'stocks', 'productbatch'));

// Fecha de facturación
$dayinicio   = GETPOST('inicio_day', 'int');
$monthinicio = GETPOST('inicio_month', 'int');
$yearinicio  = GETPOST('inicio_year', 'int');
$dayfinal    = GETPOST('final_day', 'int');
$monthfinal  = GETPOST('final_month', 'int');
$yearfinal   = GETPOST('final_year', 'int');
$dateinicio  = GETPOST('inicio_year') ? $yearinicio . "-" . $monthinicio . "-" . $dayinicio : "";
$datefinal   = GETPOST('final_year') ? $yearfinal . "-" . $monthfinal . "-" . $dayfinal : "";

$dateinicio = $dateinicio ?: date('Y-m-d', strtotime('-1 day'));
$datefinal = $datefinal ?: date('Y-m-d');

// Fecha de caducidad
$duedayinicio   = GETPOST('inicio_due_day', 'int');
$duemonthinicio = GETPOST('inicio_due_month', 'int');
$dueyearinicio  = GETPOST('inicio_due_year', 'int');
$duedayfinal    = GETPOST('final_due_day', 'int');
$duemonthfinal  = GETPOST('final_due_month', 'int');
$dueyearfinal   = GETPOST('final_due_year', 'int');
$duedateinicio  = GETPOST('inicio_due_year') ? $dueyearinicio . "-" . $duemonthinicio . "-" . $duedayinicio : "";
$duedatefinal   = GETPOST('final_due_year') ? $dueyearfinal . "-" . $duemonthfinal . "-" . $duedayfinal : "";

$ref         = trim(GETPOST('ref', 'alpha'));
$label       = trim(GETPOST('label', 'alpha'));
$client      = GETPOST('client', 'alpha');
$batch       = GETPOST('batch', 'alpha');
$warehouse_id= GETPOST('fk_warehouse', 'alpha');
$group 		 = GETPOST('group');

$sqlexport   = GETPOST('sqlexport');
$group2   = GETPOST('groupE');
if (isset($_GET['search_category_product_list'])) {
	$searchCategoryProductList = unserialize($_GET['search_category_product_list']);
} else {
	$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
}

// Almacén
if(!empty($warehouse_id)){
	$sql_w = "SELECT e.lieu as ref FROM " . MAIN_DB_PREFIX . "entrepot e WHERE e.rowid = '".$warehouse_id."'";
	$resql_w = $db->query($sql_w);
	$warehouse = $db->fetch_object($resql_w);
}

// Paginacion
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->global->MAIN_SIZE_LISTE_LIMIT;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOST("page", 'int');
$offset    = $limit * $page;
$pageprev  = $page - 1;
$pagenext  = $page + 1;
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "qty";

$titlepage = "Reporte de ventas";
// Validar si se requiere el reporte por factura o agrupado por producto
if(empty($group)){
	$sql = "SELECT DISTINCT s.nom as name, s.rowid as socid, s.code_client, s.email,";
	$sql .= " f.ref, f.datef, f.paye, f.type, f.fk_statut as statut, f.rowid as facid, f.fk_user_author,";
	$sql .= " d.rowid, d.total_ht as total_ht, d.total_ttc as total_ttc, d.qty, d.description, d.fk_product as id_product,";
	$sql .= " p.rowid as prodid, p.ref as prodref, p.barcode, p.tosell, p.tobuy, p.tobatch, (d.subprice * d.qty) as price, ";
	$sql .= " (COALESCE(d.buy_price_ht, 0)* d.qty) as cost, (d.total_ht - (COALESCE(d.buy_price_ht, 0) * d.qty)) as profit, ((d.subprice * d.qty) - (COALESCE(d.buy_price_ht, 0) * d.qty)) as profit_without_disc, ";
	$sql .= " lfe.batch, lfe.expdate as eatby";
	$sql .= " FROM " . MAIN_DB_PREFIX . "societe s";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture f ON s.rowid = f.fk_soc";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facturedet d ON f.rowid = d.fk_facture";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facturedet_extrafields lfe ON lfe.fk_object = d.rowid ";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "categorie_product lcp ON d.fk_product = lcp.fk_product";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = d.fk_product";
	$sql .= " WHERE f.entity IN (" . getEntity('invoice') . ") AND f.fk_statut >= 1 AND f.paye=1 ";

	if ($client) {
		$sql .= " AND s.nom LIKE '%" . $client . "%'";
	}
	if ($ref) {
		$sql .= " AND f.ref LIKE '%" . $ref . "%'";
	}
	if ($label) {
		$sql .= " AND p.ref LIKE '%" . $label . "%'";
	}
	if ($batch) $sql .= natural_search("lfe.batch", $batch);
	if ($warehouse) {
		$sql .= " AND f.ref LIKE '" . $warehouse->ref . "%'";
	}
	if (!empty($dateinicio) && !empty($datefinal)) {
		$sql .= " AND (f.datef BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
	} elseif (!empty($dateinicio)) {
		$sql .= " AND (f.datef BETWEEN '" . $dateinicio . "' AND CURDATE())";
	} elseif (!empty($datefinal)) {
		$sql .= " AND (f.datef BETWEEN '1970-1-1' AND '" . $datefinal . "')";
	}
	if (!empty($duedateinicio) && !empty($duedatefinal)) {
		$sql .= " AND (lfe.expdate BETWEEN '" . $duedateinicio . "' AND '" . $duedatefinal . "')";
	} elseif (!empty($duedateinicio)) {
		$sql .= " AND (lfe.expdate BETWEEN '" . $duedateinicio . "' AND CURDATE())";
	} elseif (!empty($duedatefinal)) {
		$sql .= " AND (lfe.expdate BETWEEN '1970-1-1' AND '" . $duedatefinal . "')";
	}
	if ($searchCategoryProductList) {
		$sql .= " AND (p.rowid IN";
		$sql .= " (SELECT c.fk_product FROM llx_categorie_product as c, llx_product as o";
		$sql .= " WHERE o.entity IN (" . getEntity('invoice') . ") AND c.fk_categorie IN (";
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			$sql .= $searchCategoryProduct . ", ";
		}
		$sql = substr($sql, 0, -2);
		$sql .= ")))";
	}
} else {
	$sql = "SELECT DISTINCT p.rowid as prodid, p.barcode, p.ref as prodref, p.barcode, COALESCE(qty_sum, 0) as qty";
	$sql .= ", COALESCE(sum_subquery.cost_price, 0) as cost, (sum_subquery.total_ht - COALESCE(sum_subquery.cost_price, 0)) as profit, (sum_subquery.price - COALESCE(sum_subquery.cost_price, 0)) as profit_without_disc";
	$sql .= ", sum_subquery.price, sum_subquery.total_ht, sum_subquery.paye FROM llx_product p ";
	$sql .= "JOIN (SELECT d.fk_product as product_id, SUM(d.buy_price_ht * d.qty) as cost_price, SUM(d.qty) as qty_sum, SUM(d.subprice * d.qty) as price, SUM(d.total_ht) as total_ht, f.paye ";
	$sql .= "FROM llx_societe s LEFT JOIN llx_facture f ON s.rowid = f.fk_soc LEFT JOIN llx_facturedet d ON f.rowid = d.fk_facture ";
	$sql .= "WHERE f.entity IN (1) AND f.fk_statut >= 1 AND f.paye = 1 ";
	if ($warehouse) {
		$sql .= " AND f.ref LIKE '" . $warehouse->ref . "%'";
	}
	if (!empty($dateinicio) && !empty($datefinal)) {
		$sql .= " AND (f.datef BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
	} elseif (!empty($dateinicio)) {
		$sql .= " AND (f.datef BETWEEN '" . $dateinicio . "' AND CURDATE())";
	} elseif (!empty($datefinal)) {
		$sql .= " AND (f.datef BETWEEN '1970-1-1' AND '" . $datefinal . "')";
	}
	$sql .= " GROUP BY d.fk_product) sum_subquery ON p.rowid = sum_subquery.product_id ";
	$sql .= "LEFT JOIN llx_categorie_product lcp ON p.rowid = lcp.fk_product";
	$sql .= " WHERE sum_subquery.paye = 1";
	if ($label) {
		$sql .= " AND p.ref LIKE '%" . $label . "%'";
	}
	if ($searchCategoryProductList) {
		$sql .= " AND (p.rowid IN";
		$sql .= " (SELECT c.fk_product FROM llx_categorie_product as c, llx_product as o";
		$sql .= " WHERE o.entity IN (" . getEntity('invoice') . ") AND c.fk_categorie IN (";
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			$sql .= $searchCategoryProduct . ", ";
		}
		$sql = substr($sql, 0, -2);
		$sql .= ")))";
	}
	
}
$sql .= $db->order($sortfield, $sortorder);
// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sqlnolimit = $sql;
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);
$param = '';
if ($dayinicio)      $param .= '&inicio_day=' . urlencode($dayinicio);
if ($monthinicio)    $param .= '&inicio_month=' . urlencode($monthinicio);
if ($yearinicio)     $param .= '&inicio_year=' . urlencode($yearinicio);
if ($dayfinal)       $param .= '&final_day=' . urlencode($dayfinal);
if ($monthfinal)     $param .= '&final_month=' . urlencode($monthfinal);
if ($yearfinal)      $param .= '&final_year=' . urlencode($yearfinal);
if ($duedayinicio)   $param .= '&inicio_due_day=' . urlencode($duedayinicio);
if ($duemonthinicio) $param .= '&inicio_due_month=' . urlencode($duemonthinicio);
if ($dueyearinicio)  $param .= '&inicio_due_year=' . urlencode($dueyearinicio);
if ($duedayfinal)    $param .= '&final_due_day=' . urlencode($duedayfinal);
if ($duemonthfinal)  $param .= '&final_due_month=' . urlencode($duemonthfinal);
if ($dueyearfinal)   $param .= '&final_due_year=' . urlencode($dueyearfinal);
if ($client)         $param .= '&client=' . urlencode($client);
if ($ref)            $param .= '&ref=' . urlencode($ref);
if ($label)          $param .= '&label=' . urlencode($label);
if ($batch)          $param .= '&batch=' . urlencode($batch);
if ($warehouse_id) $param .= '&fk_warehouse=' . urlencode($warehouse_id);
if ($group) $param .= '&group=' . urlencode($group);
if ($searchCategoryProductList) $param .= '&search_category_product_list=' . urlencode(serialize($searchCategoryProductList));
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);

/**
 * Actions
 */
if ($sqlexport) {
	header("Content-type: application/vnd.ms-excel; charset=iso-8859-1");
	header("Content-Transfer-Encoding: Binary");
	setlocale(LC_ALL, 'es-MX.utf-8');
	header('Content-disposition: attachment; filename="reporte_productos_vendidos.csv"');
	$outputBuffer = fopen("php://output", 'w');

	$result = $db->query($sqlexport);
	if ($db->num_rows($result) > 0 or 1 == 1) {
		$data = array();
		if (empty($group2)){
			$titles = array(
				$langs->trans("Ref"),
				$langs->trans("Author"),
				utf8_decode("Código de barras"),
				$langs->trans("Label"),
				$langs->trans("Customer"),
				utf8_decode( "Etiquetas/Categorías"),
				"Fecha de venta",
				$langs->trans("Batch"),
				$langs->trans("DateDue"),
				$langs->trans("Qty"),
				$langs->trans("Costo"),
				$langs->trans("Subtotal"),
				$langs->trans("Subtotal sin descuento"),
				$langs->trans("Utilidad"),
				$langs->trans("Utilidad sin descuento"),
			);
		} else {
			$titles = array(
				utf8_decode("Código de barras"),
				$langs->trans("Label"),
				utf8_decode( "Etiquetas/Categorías"),
				$langs->trans("Qty"),
				$langs->trans("Costo"),
				$langs->trans("Subtotal"),
				$langs->trans("Subtotal sin descuento"),
				$langs->trans("Utilidad"),
				$langs->trans("Utilidad sin descuento"),
			);
		}

		fputcsv(
			$outputBuffer,
			$titles,
			","
		);
		$form = new Form($db);
		$cat = new Categorie($db);
		$facturestatic = new Facture($db);
		$total_qty = 0;
		$total_ht = 0;
		$total_price = 0;
		while ($row = $db->fetch_object($result)) {
			if(empty($group2)) {
				$facturestatic->info($row->facid);
			}
			$categories = $cat->containing($row->prodid, 'product', 'label');
			$out = array();
			if (empty($group2)) array_push($out, $row->ref ? utf8_decode($row->ref) : '');
			if (empty($group2)) array_push($out, utf8_decode($facturestatic->user_creation->firstname . ' ' . $facturestatic->user_creation->lastname));
			array_push($out, utf8_decode($row->barcode));
			array_push($out, $row->prodref ? utf8_decode($row->prodref) : '');
			if (empty($group2)) array_push($out, $row->name ? utf8_decode($row->name) : '');
			array_push($out, utf8_decode(implode("/", $categories)));
			if (empty($group2)) array_push($out, $row->datef ? dol_print_date($row->datef, "day") : '');
			if (empty($group2)) array_push($out, $row->batch ? $row->batch : '');
			if (empty($group2)) array_push($out, $row->eatby ? dol_print_date($row->eatby, "day") : '');
			array_push($out, $row->qty ? number_format($row->qty) : '');
			array_push($out, $row->cost ? number_format($row->cost, 2) : '0');
			array_push($out, $row->total_ht ? number_format($row->total_ht, 2) : '0');
			array_push($out, $row->price ? number_format($row->price, 2) : '0');
			array_push($out, $row->profit ? number_format($row->profit, 2) : '0');
			array_push($out, $row->profit_without_disc ? number_format($row->profit_without_disc, 2) : '0');

			fputcsv($outputBuffer, $out, ",");
			$total_qty += $row->qty;
			$total_ht += $row->total_ht;
			$total_price += $row->price;
			$total_cost += $row->cost;
			$total_profit += $row->profit;
			$total_profit_without_disc += $row->profit_without_disc;
		}
		// Totales
		$totals = array();
		if (empty($group2)){
			$totals = array('TOTALES', '', '', '', '', '', '', '', '', number_format($total_qty, 2), number_format($total_cost, 2), number_format($total_ht, 2), number_format($total_price, 2), number_format($total_profit, 2), number_format($total_profit_without_disc, 2));
		} else {
			$totals = array('TOTALES', '', '', number_format($total_qty, 2), number_format($total_cost, 2), number_format($total_ht, 2), number_format($total_price, 2), number_format($total_profit, 2), number_format($total_profit_without_disc, 2));
		}
		fputcsv($outputBuffer, $totals, ",");

		fclose($outputBuffer);
		exit();
	} else {
		fclose($outputBuffer);
		exit();
	}
}

/*
 * View
 */

llxHeader("", $titlepage);

print load_fiche_titre($titlepage, $linkback = "");
print '<div class="fichecenter">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="8">' . $langs->trans("Productos vendidos") . '</th>';
print '</tr>';
print '<form name="searchFormList" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

print '<tr>';
print '<td>';
print $langs->trans("Categoría:") . '</td><td colspan="5">';
print '<div class="divsearchfield">';
$categoriesProductArr = $form->select_all_categories('product', '', '', 64, 0, 1);
$categoriesProductArr[-2] = '- ' . $langs->trans('NotCategorized') . ' -';
print Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
print '</div>';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print $form->textwithpicto('Referencia:', 'Referencia de la factura en el sistema.') . '</td><td colspan="3">';
print '<input name="ref" id="ref" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="' . $ref . '">';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print $form->textwithpicto('Producto:', 'Referencia del producto en el sistema.') . '</td><td colspan="3">';
print '<input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="' . $label . '">';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print $form->textwithpicto('Agrupar productos:', 'Se muestran resultados por producto y no por factura.') . '</td><td colspan="3">';
print '<input name="group" id="group" type="checkbox"  maxlength="100" value="1"'.($group== 1 ? ' checked="checked"' : '').'>';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print $form->textwithpicto('Cliente:', 'Nombre del cliente en el sistema.') . '</td><td colspan="5">';
print '<input name="client" id="client" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="' . $client . '">';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print $form->textwithpicto('Lote:', 'Lote del pedido.') . '</td><td colspan="5">';
print '<input name="batch" id="batch" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="' . $batch . '">';
print '</td>';
print '</tr>';

$formproduct = new FormProduct($db);
print '<tr>';
print '<td>' . $langs->trans('Almacén:') . '</td>';
print '<td colspan="5">';
if($user->rights->stock->show_all_warehouses) print $formproduct->selectWarehouses($warehouse_id, 'fk_warehouse', 'warehouseopen', 1);
else print $formproduct->selectWarehouses($user->fk_warehouse, 'fk_warehouse', 'warehouseopen', 0, 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Fecha de venta:') . '</td>';
print '<td colspan="5">';
print $langs->trans('Desde:  ');
print $form->selectDate($dateinicio, 'inicio_', '', '', '', '', 1, 1);print $langs->trans('Hasta:  ');
print $form->selectDate($datefinal ? $datefinal : '', 'final_', '', '', '', '', 1, 1); // Always autofill date with current date
print '</td>';
print '</tr>';

print '<tr id="date_batch">';
print '<td>' . $langs->trans('Fecha de caducidad:') . '</td>';
print '<td colspan="5">';
print $langs->trans('Desde:  ');
print $form->selectDate($duedateinicio ? $duedateinicio : '-1', 'inicio_due_', '', '', '', '', 1, 1);
print $langs->trans('Hasta:  ');
print $form->selectDate($duedatefinal ? $duedatefinal : '-1', 'final_due_', '', '', '', '', 1, 1); // Always autofill date with current date
print '</td>';
print '</tr>';

print '</table>';
print '<br><div class="center">';
print '<input type="submit" class="button" name="action" value="' . $langs->trans('Confirm') . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="clearQueryParamsAndReload()">';
print '<script>';
print 'function clearQueryParamsAndReload() {';
print 'const url = window.location.href.split("?")[0];';
print 'history.replaceState(null, null, url);';
print 'location.reload();';
print '}';
print '</script>';
print '</div>';

print_barre_liste("", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'products', 0, '', '', $limit);
print '</form>';
// Deshabilitar o habilitar campos dependiendo del checkbox de agrupamiento
print '<script>
	$(document).ready(function() {
		var checkbox = document.getElementById("group");
		if(checkbox.checked){
			$("#ref").prop("disabled", true);
				$("#client").prop("disabled", true);
				$("#batch").prop("disabled", true);
				$("#date_batch").hide();
		} else {
			$("#ref").prop("disabled", false);
			$("#client").prop("disabled", false);
			$("#batch").prop("disabled", false);
			$("#date_batch").show();
		}

		$("#group").change(function() {
			if (this.checked) {
				$("#ref").prop("disabled", true);
				$("#client").prop("disabled", true);
				$("#batch").prop("disabled", true);
				$("#date_batch").hide();
			} else {
				$("#ref").prop("disabled", false);
				$("#client").prop("disabled", false);
				$("#batch").prop("disabled", false);
				$("#date_batch").show();
			}
		});
	});
</script>';

// Exportar a excel
print '<table width="auto" style="position: relative; bottom: 40px;"><tr><td>';
print '<form method="POST" id="FormularioExportacion" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" id="sqlexport" name="sqlexport" value="' . $sqlnolimit . '"/>';
print '<input type="hidden" id="groupE" name="groupE" value="' . $group . '"/>';
print '</form>';
print '<a name="exportar" id="exportar"><img src="./img/xlsx.png" alt=""></a>';
print '<script language="javascript">';
print '$(document).ready(function() {';
print '    $("#exportar").on("click",function() {';
print '        $("#FormularioExportacion").submit();';
print '    });';
print '});';
print '</script>';
print '</td></tr></table>';

print '<table class="liste" style="position: relative; bottom: 30px;">';
print '<tr class="liste_titre">';
if (empty($group)) print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "ref", "", $param, "", $sortfield, $sortorder, "");
if (empty($group)) print_liste_field_titre($langs->trans("Author"), $_SERVER["PHP_SELF"], "author", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Código de barras"), $_SERVER["PHP_SELF"], "barcode", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "prodref", "", $param, "", $sortfield, $sortorder, "");
if (empty($group)) print_liste_field_titre($langs->trans("Customer"), $_SERVER["PHP_SELF"], "name", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Categories"), '', "", "", '', "", '', '', "");
if (empty($group)) print_liste_field_titre($langs->trans("Fecha de Venta"), $_SERVER["PHP_SELF"], "datef", "", $param, "", $sortfield, $sortorder, "");
if (empty($group)) print_liste_field_titre($langs->trans("Batch"), $_SERVER["PHP_SELF"], "lfe.batch", "", $param, "", $sortfield, $sortorder, "");
if (empty($group)) print_liste_field_titre($langs->trans("DateDue"), $_SERVER["PHP_SELF"], "eatby", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Qty"), $_SERVER["PHP_SELF"], "qty", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Costo"), $_SERVER["PHP_SELF"], "cost", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Subtotal"), $_SERVER["PHP_SELF"], "total_ht", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Subtotal sin descuento"), $_SERVER["PHP_SELF"], "price", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Utilidad"), $_SERVER["PHP_SELF"], "profit", "", $param, "", $sortfield, $sortorder, "");
print_liste_field_titre($langs->trans("Utilidad sin descuento"), $_SERVER["PHP_SELF"], "profit_without_disc", "", $param, "", $sortfield, $sortorder, "");
print '</tr>';

if ($resql > 0) {
	$total_qty = 0;
	$total_ht = 0;
	$total_price = 0;
	//$user = new User($db);
	$facturestatic = new Facture($db);
	$productstatic = new Product($db);
	$societestatic = new Societe($db);
	$productstatic_lot = new ProductLot($db);

	while ($row = $db->fetch_object($resql)) {
		//$user->fetch($row->fk_user_author);
		if (empty($group)) {
			$facturestatic->id  = $row->facid;
			$facturestatic->ref = $row->ref;
			$facturestatic->info($row->facid);
		}

		$productstatic->id           = $row->prodid;
		$productstatic->ref          = $row->prodref;
		$productstatic->status       = $row->tosell;
		$productstatic->status_buy   = $row->tobuy;
		$productstatic->price 		 = $row->price;
		$productstatic->status_batch = $row->tobatch;
		$productstatic->barcode = $row->barcode;

		$societestatic->id    = $row->socid;
		$societestatic->name  = $row->name;
		$societestatic->email = $row->email;

		$productstatic_lot->id     = $row->lotid;
		$productstatic_lot->batch  = $row->batch;
		$productstatic_lot->eatby  = $row->eatby;
		$productstatic_lot->sellby = $row->sellby;

		$total_qty += $row->qty;
		$total_ht += $row->total_ht;
		$total_price += $row->price;
		$total_cost += $row->cost;
		$total_profit += $row->profit;
		$total_profit_without_disc += $row->profit_without_disc;

		if (empty($group)) print "<td>" . $facturestatic->getNomUrl(1) . "</td>";
		if(empty($group)) print "<td>" . $facturestatic->user_creation->getNomUrl(1) . "</td>";
		print "<td>" . $productstatic->barcode . "</td>";
		print "<td>" . $productstatic->getNomUrl(1) . "</td>";
		if (empty($group)) print "<td>" . $societestatic->getNomUrl(1) . "</td>";
		print "<td>" . $form->showCategories($row->prodid, "product", 1) . "</td>";
		if (empty($group)) print "<td> " . dol_print_date($row->datef, "day") . " </td>";
		if (empty($group)) print "<td>" . $productstatic_lot->getNomUrl(1) . "</td>";
		if (empty($group)) print "<td> " . dol_print_date($row->eatby, "day") . " </td>";
		print "<td>" . number_format($row->qty) . "</td>";
		print "<td>" . number_format($row->cost, 2) . "</td>";
		print "<td>" . number_format($row->total_ht, 2) . "</td>";
		print "<td>" . number_format($row->price, 2) . "</td>";
		print "<td>" . number_format($row->profit, 2) . "</td>";
		print "<td>" . number_format($row->profit_without_disc, 2) . "</td>";
		print "</tr>";
	}
	print '<tr class="liste_total">';
	if ($num < $limit) print '<td class="left">' . $langs->trans("Total") . '</td>';
	else print '<td class="left">' . $langs->trans("Totalforthispage") . '</td>';
	print '<td '.(empty($group)? 'colspan="8"' : '').' ></td>';
	print '<td class="left">' . $total_qty . '</td>';
	print '<td align="left">' . price($total_cost) . '</td>';
	print '<td align="left">' . price($total_ht) . '</td>';
	print '<td align="left">' . price($total_price) . '</td>';
	print '<td align="left">' . price($total_profit) . '</td>';
	print '<td align="left">' . price($total_profit_without_disc) . '</td>';
	print '</tr>';
}
print '</table>';

// End of page
llxFooter();
$db->close();
