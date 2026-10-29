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
// El ticket agrupa por producto, así que solo se puede ordenar por columnas de producto/stock
if (!in_array($sortfield, array('p.ref', 'p.barcode', 'value'))) $sortfield = 'p.ref';
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
$sql .= " ps.reel as value";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product_stock as ps ON ps.fk_product = p.rowid AND ps.fk_entrepot = ".(int) $warehouse_id;
$sql .= " WHERE ps.reel <> 0";

$searchCategoryProductSqlList = array();
if (!empty($searchCategoryProductList) && is_array($searchCategoryProductList)) {
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		if (intval($searchCategoryProduct) == -2) {
			$searchCategoryProductSqlList[] = "NOT EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."categorie_product as cp WHERE cp.fk_product = p.rowid)";
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
		$html .= '<tr style="font-size: 7;">
            <td colspan="4" style="text-align: left;"> '.$row->ref.'</td>
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
