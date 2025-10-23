<?php
/**
 *  \file       htdocs/product/custom/supplier_report.php
 *  \ingroup    product
 *  \brief      reporte de compras a proveedor
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
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
$supplier= GETPOST('supplier', 'alpha');

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

$titlepage = "Reporte de proveedor";

// Get total for each payment type
$sql = "SELECT lcf.rowid, lcf.`ref`, lcf.tva as iva, lcf.total_ttc as total";
$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur lcf";
$sql .= " WHERE lcf.fk_statut IN (3, 4, 5)";
if ($warehouse_id) {
	$sql .= " AND lcf.fk_entrepot = ".$warehouse_id;
}
if ($supplier > 0) {
	$sql .= " AND lcf.fk_soc = ".$supplier;
}
if (!empty($dateinicio) && !empty($datefinal)) {
	$sql .= " AND (lcf.date_commande BETWEEN '" . $dateinicio . "' AND '" . $datefinal . "')";
} elseif (!empty($dateinicio)) {
	$sql .= " AND (lcf.date_commande BETWEEN '" . $dateinicio . "' AND CURDATE())";
} elseif (!empty($datefinal)) {
	$sql .= " AND (lcf.date_commande BETWEEN '1970-1-1' AND '" . $datefinal . "')";
}

$resql = $db->query($sql);
if (!$resql) {
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
print '<th colspan="8">' . $langs->trans("Compras a proveedor por sucursal") . '</th>';
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
print '<td>'.$langs->trans('Supplier').':</td>';
print '<td colspan="5">';
print $form->select_company((empty($supplier) ? '' : $supplier), 'supplier', 's.fournisseur=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Fecha de pedido:') . '</td>';
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

print '<table width="auto" style="position: relative; bottom: 40px;"><tr><td>';
print '<form method="POST" id="FormularioExportacion" action="export_supplier_report.php">';
print '<input type="hidden" id="sqlexport" name="sqlexport" value="' . $sql . '"/>';
print '<input type="hidden" id="sqlexport2" name="sqlexport2" value="' . $sql2 . '"/>';
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

print '<table class="liste" style="position: relative; bottom: 30px;">';
print '<tr>';
print '<td colspan=9>Compras a proveedor</td>';
print '</tr>';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Ref"));
print_liste_field_titre($langs->trans("IVA"));
print_liste_field_titre($langs->trans("Total"));
print '</tr>';
if ($resql > 0) {
	$iva_total = 0;
	$total = 0;
	while ($row = $db->fetch_object($resql)) {
		$oc = new CommandeFournisseur($db);
		$oc->fetch($row->rowid);

		$iva_total += $row->iva;
		$total += $row->total;
		print "<td>" . $oc->getNomUrl(1) .  "</td>";
		print "<td>" . price($row->iva) .  "</td>";
		print "<td>" . price($row->total) .  "</td>";
		print "</tr>";
	}
}

print '<tr class="liste_total">';
print '<td>Total</td>';
print '<td align="left">' . price($iva_total) . '</td>';
print '<td align="left">' . price($total) . '</td>';
print '</tr>';

print '</table>';

// End of page
llxFooter();
$db->close();
