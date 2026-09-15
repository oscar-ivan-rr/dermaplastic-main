<?php
/**
 *  \file       htdocs/product/custom/warehouse_report.php
 *  \ingroup    product
 *  \brief      reporte de ventas por sucursal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

date_default_timezone_set("GMT");
global $conf, $langs;
$langs->loadLangs(array('main', 'companies', 'bills', 'product', 'stocks', 'productbatch'));

// Fecha de venta
$dayinicio   = GETPOST('inicio_day', 'int');
$monthinicio = GETPOST('inicio_month', 'int');
$yearinicio  = GETPOST('inicio_year', 'int');
$dayfinal    = GETPOST('final_day', 'int');
$monthfinal  = GETPOST('final_month', 'int');
$yearfinal   = GETPOST('final_year', 'int');
$dateinicio  = GETPOST('inicio_year') ? $yearinicio . "-" . $monthinicio . "-" . $dayinicio : "";
$datefinal   = GETPOST('final_year') ? $yearfinal . "-" . $monthfinal . "-" . $dayfinal : "";
$warehouse_id= GETPOST('fk_warehouse', 'alpha') ? GETPOST('fk_warehouse', 'alpha') : $user->fk_warehouse;

$sqlexport   = GETPOST('sqlexport');

// Warehouse
if(!empty($warehouse_id)){
	$sql_w = "SELECT e.ref as nom, e.lieu as ref, r.label as rfc_label FROM " . MAIN_DB_PREFIX . "entrepot e ";
	$sql_w .= "JOIN " . MAIN_DB_PREFIX . "c_rfc r ON e.fk_rfc = r.rowid WHERE e.rowid = '".$warehouse_id."'";
	$resql_w = $db->query($sql_w);
	$warehouse = $db->fetch_object($resql_w);
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page      = GETPOST("page", 'int');
if (!$sortorder) $sortorder = "DESC";

$titlepage = "Reporte de sucursal";

// Totales de venta desde líneas de factura (mismo criterio que best_seller).
// Los desgloses de pago se calculan aparte: no mezclar SUM(pagos)-IVA como si fuera HT,
// porque facturas pagadas sin líneas (o pagos ≠ TTC) inflaban el "Total".
$sql = "SELECT qty, (total_ht_x/qty) as average, total_ht_x AS total_ht, cash_total, credit_total, debit_total, transfer_total, total_tva ";
$sql .= "FROM (SELECT SUM(CASE WHEN lcp.code = 'VIR' THEN lpf.amount ELSE 0 END) AS transfer_total, ";
$sql .= "SUM(CASE WHEN lcp.code = 'LIQ' THEN lpf.amount ELSE 0 END) AS cash_total, ";
$sql .= "SUM(CASE WHEN lcp.code = 'CB' THEN lpf.amount ELSE 0 END) AS credit_total, ";
$sql .= "SUM(CASE WHEN lcp.code = 'TD/C' THEN lpf.amount ELSE 0 END) AS debit_total ";
$sql .= "FROM llx_facture f LEFT JOIN llx_paiement_facture lpf ON lpf.fk_facture = f.rowid ";
$sql .= "JOIN llx_paiement lp ON lp.rowid = lpf.fk_paiement JOIN llx_c_paiement lcp ON lp.fk_paiement = lcp.id ";
$sql .= "WHERE f.entity IN (1) AND f.fk_statut >= 1 AND f.paye = 1 ";
$sql .= "AND EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."facturedet d WHERE d.fk_facture = f.rowid) ";
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
$sql .= ") AS t1 ";
// Get total for each invoice line
$sql .= "JOIN (SELECT SUM(d.qty) AS qty, SUM(d.total_ht) AS total_ht_x, SUM(d.total_tva) AS total_tva ";
$sql .= "FROM llx_facture f INNER JOIN llx_facturedet d ON f.rowid = d.fk_facture ";
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
$sql .= ") AS t2 ON 1 = 1";

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

// Get total for each order
$sql_comm_fourn = "SELECT  COUNT(*) as qty, SUM(cf.total_ht) as subtotal, SUM(cf.tva) as tva, SUM(cf.total_ttc) as total";
$sql_comm_fourn .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur cf";
$sql_comm_fourn .= " WHERE cf.entity = 1 AND cf.fk_statut = 5";
if ($warehouse) {
	$sql_comm_fourn .= " AND cf.ref LIKE '" . $warehouse->ref . "%'";
}
if (!empty($dateinicio) && !empty($datefinal)) {
	$sql_comm_fourn .= " AND (cf.date_commande BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
} elseif (!empty($dateinicio)) {
	$sql_comm_fourn .= " AND (cf.date_commande BETWEEN '" . $dateinicio . "' AND CURDATE())";
} elseif (!empty($datefinal)) {
	$sql_comm_fourn .= " AND (cf.date_commande BETWEEN '1970-1-1' AND '" . $datefinal . "')";
}

$resql_comm_fourn = $db->query($sql_comm_fourn);
if (!$resql_comm_fourn) {
	dol_print_error($db);
	exit;
}

// Get purchase prices by warehouse
$sql2 = "SELECT  SUM(CASE WHEN p.exentoiva = 0 THEN (p.cost_price * ps.reel) ELSE 0 END) AS gravado, ";
$sql2 .= "SUM(CASE WHEN p.exentoiva = 1 THEN (p.cost_price * ps.reel) ELSE 0 END) AS no_gravado,";
$sql2 .= "SUM(CASE WHEN p.exentoiva = 0 THEN (p.cost_price * ps.reel * (p.tva_tx/100)) ELSE 0 END) AS iva ";
$sql2 .= "FROM " . MAIN_DB_PREFIX . "product as p LEFT JOIN llx_product_stock as ps ON ps.fk_product = p.rowid WHERE ps.fk_entrepot = ".($warehouse_id ? $warehouse_id : $user->fk_warehouse);

$resql2 = $db->query($sql2);
if (!$resql2) {
	dol_print_error($db);
	exit;
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
print '<th colspan="8">' . $langs->trans("Total de ventas por sucursal") . '</th>';
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
print '<td>' . $langs->trans('Fecha de venta:') . '</td>';
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

print '<table width="auto" style="margin-top:20px;"><tr><td>';
print '<form method="POST" id="FormularioExportacion" action="export_warehouse_report.php">';
print '<input type="hidden" id="sqlexport" name="sqlexport" value="' . $sql . '"/>';
print '<input type="hidden" id="sqlexport2" name="sqlexport2" value="' . $sql2 . '"/>';
print '<input type="hidden" id="sql_comm_fourn" name="sql_comm_fourn" value="' . $sql_comm_fourn . '"/>';
print '<input type="hidden" id="dateinicio" name="dateinicio" value="' . $dateinicio . '"/>';
print '<input type="hidden" id="datefinal" name="datefinal" value="' . $datefinal . '"/>';
print '<input type="hidden" id="nom" name="nom" value="' . $warehouse->nom . '"/>';
print '<input type="hidden" id="rfc_label" name="rfc_label" value="' . $warehouse->rfc_label . '"/>';
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

print '<table class="liste" style="margin-top: 10px;">';
print '<tr>';
print '<td colspan=9>Ventas</td>';
print '</tr>';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Productos"));
print_liste_field_titre($langs->trans("Precio promedio"));
print_liste_field_titre($langs->trans("Total"));
print_liste_field_titre($langs->trans("Efectivo"));
print_liste_field_titre($langs->trans("Tarjeta crédito"));
print_liste_field_titre($langs->trans("Tarjeta débito"));
print_liste_field_titre($langs->trans("Transferencia"));
print_liste_field_titre($langs->trans("IVA"));
print_liste_field_titre($langs->trans("Ventas con IVA"));
print '</tr>';
if ($resql > 0) {

	while ($row = $db->fetch_object($resql)) {
		print "<td>" . number_format($row->qty) . "</td>";
		print "<td>" . price($row->average) .  "</td>";
		print "<td>" . price($row->total_ht) .  "</td>";
		print "<td>" . price($row->cash_total) .  "</td>";
		print "<td>" . price($row->credit_total) .  "</td>";
		print "<td>" . price($row->debit_total) .  "</td>";
		print "<td>" . price($row->transfer_total) .  "</td>";
		print "<td>" . price($row->total_tva) .  "</td>";
		print "<td>" . price($row->total_ht + $row->total_tva) .  "</td>";
		print "</tr>";
	}
}
print '</table>';

print '<table class="liste" style="margin-top: 60px;">';
print '<tr>';
print '<td colspan=5>Compras</td>';
print '</tr>';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Compras a proveedores"));
print_liste_field_titre($langs->trans("Total de compras"));
print_liste_field_titre($langs->trans("Subtotal"));
print_liste_field_titre($langs->trans("IVA"));
print_liste_field_titre($langs->trans("Total"));
print '</tr>';
if ($resql_comm_fourn > 0) {

	while ($row = $db->fetch_object($resql_comm_fourn)) {
		print "<td>" . $warehouse->nom . "</td>";
		print '<td>' . $row->qty .  '</td>';
		print "<td>" . price($row->subtotal) .  "</td>";
		print "<td>" . price($row->tva) .  "</td>";
		print "<td>" . price($row->total) .  "</td>";
		print "</tr>";
	}
}
print '</table>';

print '<table class="liste" style="margin-top: 60px;">';
print '<tr>';
print '<td colspan=5>Costos</td>';
print '</tr>';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Costo de inventario con IVA"));
print_liste_field_titre($langs->trans("Total"));
print_liste_field_titre($langs->trans("Gravado"));
print_liste_field_titre($langs->trans("No gravado"));
print_liste_field_titre($langs->trans("IVA"));
print '</tr>';
if ($resql2 > 0) {

	while ($row2 = $db->fetch_object($resql2)) {
		print "<td>" . $warehouse->nom .  "</td>";
		print "<td>" . price($row2->gravado + $row2->no_gravado + $row2->iva) .  "</td>";
		print "<td>" . price($row2->gravado) .  "</td>";
		print "<td>" . price($row2->no_gravado) .  "</td>";
		print "<td>" . price($row2->iva) .  "</td>";
		print "</tr>";
	}
}
print '</table>';

// End of page
llxFooter();
$db->close();
