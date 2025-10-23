<?php
/**
 *  \file       htdocs/product/custom/warehouse_report.php
 *  \ingroup    product
 *  \brief      monthly report
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

date_default_timezone_set("GMT");
global $conf, $langs;
$langs->loadLangs(array('main', 'companies', 'bills', 'product', 'stocks', 'productbatch'));

// Date
$dayinicio   = GETPOST('inicio_day', 'int');
$monthinicio = GETPOST('inicio_month', 'int');
$yearinicio  = GETPOST('inicio_year', 'int');
$dayfinal    = GETPOST('final_day', 'int');
$monthfinal  = GETPOST('final_month', 'int');
$yearfinal   = GETPOST('final_year', 'int');
$dateinicio  = GETPOST('inicio_year') ? $yearinicio . "-" . $monthinicio . "-" . $dayinicio : "";
$datefinal   = GETPOST('final_year') ? $yearfinal . "-" . $monthfinal . "-" . $dayfinal : "";
$warehouse_id= GETPOST('fk_warehouse', 'alpha') ? GETPOST('fk_warehouse', 'alpha') : $user->fk_warehouse;

// Calculate the difference of days
if(!empty($dateinicio)){
	if(!empty($dateinicio)) $fechaFin = new DateTime($datefinal);
	else $fechaFin = new DateTime(date('Y-m-d', dol_now()));

	$fechaInicio = new DateTime($dateinicio);
	$interval = $fechaInicio->diff($fechaFin);
	$days = $interval->days + 1;
} else {
	$days = 0;
}

$sqlexport   = GETPOST('sqlexport');
$total = 0;

// Warehouse
if(!empty($warehouse_id)){
	$sql_w = "SELECT e.ref as nom, e.lieu as ref, r.label as rfc_label FROM " . MAIN_DB_PREFIX . "entrepot e ";
	$sql_w .= "JOIN " . MAIN_DB_PREFIX . "c_rfc r ON e.fk_rfc = r.rowid WHERE e.rowid = '".$warehouse_id."'";
	$resql_w = $db->query($sql_w);
	$warehouse = $db->fetch_object($resql_w);
}

$titlepage = "Reporte mensual";

// Get total for each type fees
$sqlfees = "SELECT * FROM (SELECT lctf.id, lctf.code, lctf.label FROM " . MAIN_DB_PREFIX . "c_type_fees lctf WHERE lctf.active = 1) as type_fees";
$sqlfees .= " LEFT JOIN (SELECT le.fk_c_type_fees as type_fees, COALESCE(sum(le.total_ttc), 0) as total FROM " . MAIN_DB_PREFIX . "expensereport le";
$sqlfees .= " WHERE le.entity IN (1) AND le.fk_statut = 5";
if ($warehouse) {
	$sqlfees .= " AND le.fk_warehouse =".$warehouse_id;
}
if (!empty($dateinicio) && !empty($datefinal)) {
	$sqlfees .= " AND (DATE(le.date_approve) BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
} elseif (!empty($dateinicio)) {
	$sqlfees .= " AND (DATE(le.date_approve) BETWEEN '" . $dateinicio . "' AND CURDATE())";
} elseif (!empty($datefinal)) {
	$sqlfees .= " AND (DATE(le.date_approve) BETWEEN '1970-1-1' AND '" . $datefinal . "')";
}
$sqlfees .= " GROUP BY le.fk_c_type_fees) as expensereport";
$sqlfees .= " ON type_fees.id = expensereport.type_fees ORDER BY type_fees.code";
$resqlfees = $db->query($sqlfees);

// Get total for sales
$sqlsales = "SELECT SUM(d.qty) AS qty, SUM(d.total_ht) AS total_ht, SUM(d.total_tva) AS total_tva,  SUM(d.buy_price_ht * d.qty) as cost_price, COUNT(DISTINCT f.rowid) as num_factures ";
$sqlsales .= "FROM " . MAIN_DB_PREFIX . "facture f LEFT JOIN llx_facturedet d ON f.rowid = d.fk_facture ";
$sqlsales .= "WHERE f.entity IN (1) AND f.fk_statut >= 1 AND f.paye = 1 ";
if ($warehouse) {
	$sqlsales .= " AND f.ref LIKE '" . $warehouse->ref . "%'";
}
if (!empty($dateinicio) && !empty($datefinal)) {
	$sqlsales .= " AND (f.datef BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
} elseif (!empty($dateinicio)) {
	$sqlsales .= " AND (f.datef BETWEEN '" . $dateinicio . "' AND CURDATE())";
} elseif (!empty($datefinal)) {
	$sqlsales .= " AND (f.datef BETWEEN '1970-1-1' AND '" . $datefinal . "')";
}
$resqlsales = $db->query($sqlsales);

// Get purchase prices by warehouse
$sqlcost = "SELECT  SUM(CASE WHEN p.exentoiva = 0 THEN (p.cost_price * ps.reel) ELSE 0 END) AS gravado, ";
$sqlcost .= "SUM(CASE WHEN p.exentoiva = 1 THEN (p.cost_price * ps.reel) ELSE 0 END) AS no_gravado,";
$sqlcost .= "SUM(CASE WHEN p.exentoiva = 0 THEN (p.cost_price * ps.reel * (p.tva_tx/100)) ELSE 0 END) AS iva ";
$sqlcost .= "FROM " . MAIN_DB_PREFIX . "product as p LEFT JOIN llx_product_stock as ps ON ps.fk_product = p.rowid WHERE ps.fk_entrepot = ".($warehouse_id ? $warehouse_id : $user->fk_warehouse);
$resqlcost = $db->query($sqlcost);

// Get total for OC
$sqlcommande = "SELECT SUM(cd.total_ht) AS total_ht, SUM(cd.total_tva) AS total_tva ";
$sqlcommande .= "FROM ". MAIN_DB_PREFIX."commande_fournisseur c LEFT JOIN ". MAIN_DB_PREFIX."commande_fournisseurdet cd ON c.rowid = cd.fk_commande ";
$sqlcommande .= "WHERE c.entity IN (1) AND c.fk_statut NOT IN (0, 6, 9) ";
if ($warehouse) {
	$sqlcommande .= " AND c.fk_entrepot =".$warehouse_id;
}
if (!empty($dateinicio) && !empty($datefinal)) {
	$sqlcommande .= " AND (c.date_commande BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
} elseif (!empty($dateinicio)) {
	$sqlcommande .= " AND (c.date_commande BETWEEN '" . $dateinicio . "' AND CURDATE())";
} elseif (!empty($datefinal)) {
	$sqlcommande .= " AND (c.date_commande BETWEEN '1970-1-1' AND '" . $datefinal . "')";
}
$resqlcommande = $db->query($sqlcommande);

// Get total for stock movements
$sqlmovement = "SELECT SUM(lp.cost_price * ABS(lsm.value)) as total ";
$sqlmovement .= "FROM ".MAIN_DB_PREFIX."stock_mouvement lsm JOIN ".MAIN_DB_PREFIX."product lp ON lsm.fk_product = lp.rowid ";
$sqlmovement .= "WHERE (lsm.label LIKE '%robo' OR lsm.label LIKE '%merma' OR lsm.label LIKE '%inventario') AND lsm.type_mouvement = 1 ";
if ($warehouse) {
	$sqlmovement .= " AND lsm.fk_entrepot =".$warehouse_id;
}
if (!empty($dateinicio) && !empty($datefinal)) {
	$sqlmovement .= " AND (DATE(lsm.datem) BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
} elseif (!empty($dateinicio)) {
	$sqlmovement .= " AND (DATE(lsm.datem) BETWEEN '" . $dateinicio . "' AND CURDATE())";
} elseif (!empty($datefinal)) {
	$sqlmovement .= " AND (DATE(lsm.datem) BETWEEN '1970-1-1' AND '" . $datefinal . "')";
}
$resqlmovement = $db->query($sqlmovement);


if (!$resqlfees || !$resqlsales || !$resqlcost || !$resqlcommande || !$resqlmovement) {
	dol_print_error($db);
	exit;
} else {
	$sales = $db->fetch_object($resqlsales);
	$cost = $db->fetch_object($resqlcost);
	$commande = $db->fetch_object($resqlcommande);
	$movement = $db->fetch_object($resqlmovement);
}

$param = '';
if ($dayinicio)      $param .= '&inicio_day=' . urlencode($dayinicio);
if ($monthinicio)    $param .= '&inicio_month=' . urlencode($monthinicio);
if ($yearinicio)     $param .= '&inicio_year=' . urlencode($yearinicio);
if ($dayfinal)       $param .= '&final_day=' . urlencode($dayfinal);
if ($monthfinal)     $param .= '&final_month=' . urlencode($monthfinal);
if ($yearfinal)      $param .= '&final_year=' . urlencode($yearfinal);
if ($warehouse_id) 	 $param .= '&fk_warehouse=' . urlencode($warehouse_id);

/*
 * View
 */
llxHeader("", $titlepage);

print load_fiche_titre($titlepage, $linkback = "");
print '<div class="fichecenter">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="8">' . $langs->trans("Gastos y ventas mensuales") . '</th>';
print '</tr>';
print '<form name="searchFormList" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

$formproduct = new FormProduct($db);
print '<tr>';
print '<td>' . $langs->trans('Almacén:') . '</td>';
print '<td colspan="5">';
if($user->rights->stock->show_all_warehouses) print $formproduct->selectWarehouses($warehouse_id, 'fk_warehouse', 'warehouseopen', 0);
else print $formproduct->selectWarehouses($user->fk_warehouse, 'fk_warehouse', 'warehouseopen', 0, 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Fecha:') . '</td>';
print '<td colspan="5">';
print $langs->trans('Desde:  ');
print $form->selectDate($dateinicio ? $dateinicio : '-1', 'inicio_', '', '', '', '', 1, 1);
print $langs->trans('Hasta:  ');
print $form->selectDate($datefinal ? $datefinal : '-1', 'final_', '', '', '', '', 1, 1); // Always autofill date with current date
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
print '</form>';
print '<br>';
print '<br>';
print '<br>';

// Export form
print '<table width="auto" style="position: relative; bottom: 40px;"><tr><td>';
print '<form method="POST" id="FormularioExportacion" action="export_monthly_report.php">';
print '<input type="hidden" name="nom" value="' . $warehouse->nom . '"/>';
print '<input type="hidden" name="rfc_label" value="' . $warehouse->rfc_label . '"/>';
print '<input type="hidden" name="dateinicio"  value="' . $dateinicio . '"/>';
print '<input type="hidden" name="datefinal"   value="' . $datefinal . '"/>';
print '<input type="hidden" name="sqlfees" 	   value="' . $sqlfees . '"/>';
print '<input type="hidden" name="sqlsales"    value="' . $sqlsales . '"/>';
print '<input type="hidden" name="sqlcost" 	   value="' . $sqlcost . '"/>';
print '<input type="hidden" name="sqlcommande" value="' . $sqlcommande . '"/>';
print '<input type="hidden" name="sqlmovement" value="' . $sqlmovement . '"/>';
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

/**  -------------------------- Fees table starts ------------------------- */
print '<table class="liste" style="float: left; margin-right: 50px;position: relative; bottom: 30px; width:45%;">';
print '<tr>';
print '<td colspan=4>Gastos</td>';
print '</tr>';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Label"), '', "", "", '', '');
print_liste_field_titre($langs->trans("Total"), '', "", "", '', 'align="right"');
print_liste_field_titre('');
print_liste_field_titre('');
print '</tr>';
print '<tr>';
if ($resqlfees > 0) {
	while ($row = $db->fetch_object($resqlfees)) {
		$total += $row->total;
		print "<td>" . $row->label .  "</td>";
		print "<td class='right'>" . price($row->total) .  "</td>";
		print "<td colspan='2'></td>";
		print "</tr>";
	}
}

// Total
print '<tr class="liste_total">';
print '<td class="left">' . $langs->trans("Total") . '</td>';
print '<td align="right">' . price($total) . '</td>';
print '<td align="right" colspan="2"></td>';
print '</tr>';
print '</table>';
/** -------------------------- Fees table ends ------------------------- */ 

/** -------------------------- Sales table starts ------------------------- */ 
$monthly_income = $sales->total_ht + $sales->total_tva;
$gross_profit = $monthly_income - $sales->cost_price;
$sale_per_day = $monthly_income / $days;
$cost_per_day = $sales->cost_price / $days;
$cost_avg = $sales->cost_price / $sales->qty;
$prod_cost_avg = $sale_per_day / $cost_avg;
$net_profit = $gross_profit - $total;
$inventory_cost = $cost->gravado + $cost->no_gravado + $cost->iva;
$ticket_avg = $monthly_income / $sales->num_factures;

print '<table class="liste" style="float: right; position: relative; bottom: 30px; width:45%;">';
$label = ['Ingresos sin iva', 'IVA', 'Ingresos mensuales', 'Costo', 'Utilidad bruta', '',
		'Venta por día', 'Costo por día', 'Utilidad', '',
		'Costo de productos promedio', 'Productos por día con un costo promedio', '',
		'Gastos fijos', 'Utilidad neta', 'Porcentaje', '', 'Costo del inventario', '',
		'Productos vendidos por mes', '', 'Compras sin iva', 'IVA', 'Total de compras', '',
		'Tickets por mes', '', 'Promedio por ticket', '',
		'Faltantes y mermas'];
$val = [price($sales->total_ht), price($sales->total_tva), price($monthly_income), price($sales->cost_price), price($gross_profit),
		price($sale_per_day), price($cost_per_day), price($sale_per_day - $cost_per_day),
		price($cost_avg), price($prod_cost_avg),
		price($total), price($net_profit), price($net_profit / $monthly_income), price($inventory_cost),
		$sales->qty, price($commande->total_ht), price($commande->total_tva), price($commande->total_ht + $commande->total_tva),
		$sales->num_factures, price($ticket_avg),
		price($movement->total)];
$i = 0;
foreach ($label as $col) {
	if(empty($col)){
		print '<tr><td colspan=2></td></tr>';
	}elseif($col != 'Porcentaje') {
		print '<tr>';
		print '<td>'.$col.'</td>';
		print '<td class="right">'.$val[$i].'</td>';
		print '</tr>';
		$i++;
	} else {
		print '<tr><td class="right" colspan=2>'.$val[$i].'%</td></tr>';
		$i++;
	}
}
print '</table>';
/**  -------------------------- Sales table ends ------------------------- */

// End of page
llxFooter();
$db->close();
