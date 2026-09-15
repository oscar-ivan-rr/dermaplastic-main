<?php
/*
 * Zabdi Ramírez Garcia
 */

/**
 *	\file       htdocs/product/stock/export_ticket.php
 *	\ingroup    stock
 *	\brief      Page fiche entrepot
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once("../../includes/tecnickcom/tcpdf/tcpdf_import.php");

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 0);

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies', 'categories'));
if (!empty($conf->productbatch->enabled)) $langs->load('productbatch');

$warehouse_id = GETPOST('id');
$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
if (!is_array($searchCategoryProductList)) $searchCategoryProductList = array();
$sortfield = GETPOST('sortfield');
$sortorder = GETPOST('sortorder');
if (empty($sortfield)) $sortfield = 'p.ref';
if (empty($sortorder)) $sortorder = 'ASC';

// create new PDF document
$pageLayout = array(80, 210);
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, 'mm', $pageLayout, true, 'UTF-8', false);

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(2, 1, 2);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 0.5);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------

// set font
$pdf->SetFont('helvetica', '', 10);
$pdf->setJPEGQuality(100);
// add a page
$pdf->AddPage();

$sql = "SELECT p.rowid as rowid, p.ref, p.barcode as barcode,";
$sql .= " COALESCE(pb.qty, ps.reel) as value, pb.batch as batch, COALESCE(pl.eatby, pb.eatby) as eatby,";
$sql .= " e.ref as warehouse_ref, last_in.last_entry as last_entry";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_stock as ps ON ps.fk_product = p.rowid AND ps.fk_entrepot = ".(int) $warehouse_id;
$sql .= " LEFT JOIN (";
$sql .= " SELECT fk_product_stock, batch, SUM(qty) as qty, MIN(eatby) as eatby";
$sql .= " FROM ".MAIN_DB_PREFIX."product_batch";
$sql .= " GROUP BY fk_product_stock, batch";
$sql .= " ) as pb ON pb.fk_product_stock = ps.rowid";
$sql .= " LEFT JOIN (";
$sql .= " SELECT fk_product, batch, MIN(eatby) as eatby";
$sql .= " FROM ".MAIN_DB_PREFIX."product_lot";
$sql .= " GROUP BY fk_product, batch";
$sql .= " ) as pl ON pl.fk_product = p.rowid AND pl.batch = pb.batch";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON e.rowid = ps.fk_entrepot";
$sql .= " LEFT JOIN (SELECT fk_product, MAX(datem) as last_entry FROM ".MAIN_DB_PREFIX."stock_mouvement";
$sql .= " WHERE fk_entrepot = ".(int) $warehouse_id." AND value > 0 GROUP BY fk_product) as last_in ON last_in.fk_product = p.rowid";
if (!empty($searchCategoryProductList)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
$sql .= " WHERE ps.fk_product = p.rowid";
$sql .= " AND COALESCE(pb.qty, ps.reel) <> 0";
$sql .= " AND (pb.batch IS NULL OR pb.qty <> 0)";
$sql .= " AND ps.fk_entrepot = ".(int) $warehouse_id;

$searchCategoryProductSqlList = array();
if (!empty($searchCategoryProductList) && is_array($searchCategoryProductList)) {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
		} elseif (intval($searchCategoryProduct) > 0) {
			$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$searchCategoryProduct.")";
		}
	}
}
if (!empty($searchCategoryProductSqlList)) {
	$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
}
$sql .= $db->order($sortfield, $sortorder);
$result = $db->query($sql);

$warehouse = new Entrepot($db);
$warehouse->fetch($warehouse_id);

$html = '';
// Nombre de sucursal
$html .= '<h6 style="font-size: 10; text-decoration: bold; text-align: center;">'.$warehouse->ref.'</h6>
<br>';

if ($result && $db->num_rows($result) > 0) {
	$html .= '<table style="border-style: double; width=100%; text-align: center;">

    <tr style="font-weight: bold; font-size: 8;">
        <th colspan="4">'.$langs->trans("Referencia").'</th>
    </tr>
    <tr style="font-weight: bold; font-size: 7;">
        <th style="border-bottom-style: double;">'.$langs->trans("Código de barras").'</th>
        <th style="border-bottom-style: double;">'.$langs->trans("Unidades").'</th>
        <th style="border-bottom-style: double;">'.$langs->trans("Fís.").'</th>
        <th style="border-bottom-style: double;">'.$langs->trans("Dif.").'</th>
    </tr>

    <tbody>';
	while ($row = $db->fetch_object($result)) {
		$lote_txt = !empty($row->batch) ? ' | Lote: '.$row->batch : '';
		$cad_txt = !empty($row->eatby) ? ' | Cad: '.dol_print_date($db->jdate($row->eatby), 'day') : '';
		$entrada_txt = !empty($row->last_entry) ? ' | Entrada: '.dol_print_date($db->jdate($row->last_entry), 'day') : '';
		$html .= '<tr style="font-size: 7;">
            <td colspan="4" style="text-align: left;"> '.$row->ref.$lote_txt.$cad_txt.$entrada_txt.'</td>
        </tr>
        <tr>
		<td style="font-size: 8;"> '.$row->barcode.'</td>
		<td style="font-size: 8;"> '.$row->value.'</td>
		<td style="font-size: 8;">_________</td>
        <td style="font-size: 8;">_________</td>
        </tr>';
	}
}
$html .= '</tbody>
</table>
<br>
<br>';

// Se termina de imprimir los elementos HTML y se escribe en el PDF
$pdf->writeHTML($html, true, false, true, false, '');
ob_end_clean();
// ---------------------------------------------------------
//Close and output PDF document
$pdf->Output('Productos_'.$warehouse->ref.'.pdf', 'D');

?>
