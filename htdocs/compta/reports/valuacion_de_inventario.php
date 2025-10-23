<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019       Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/reassort.php
 *  \ingroup    produit
 *  \brief      Page to list stocks
 */

require '../../main.inc.php';;
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks'));

// Security check
if ($user->socid) $socid=$user->socid;
$result=restrictedArea($user, 'produit|service');


$action=GETPOST('action', 'alpha');
$sref=GETPOST("sref", 'alpha');
$snom=GETPOST("snom", 'alpha');
$sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$type=GETPOST("type", "int");
$search_barcode=GETPOST("search_barcode", 'alpha');
$catid=GETPOST('catid', 'int');
$toolowstock=GETPOST('toolowstock');
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");
$fourn_id = GETPOST("fourn_id", 'int');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page < 0) $page = 0;
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "ASC";
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;

// Load sale and categ filters
$search_sale = GETPOST("search_sale");
$search_categ = GETPOST("search_categ");

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'list', $canvas);
}

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION))
{
    $virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $sref = "";
    $snom = "";
    $sall = "";
	$tosell = "";
	$tobuy = "";
    $search_sale = "";
    $search_categ = "";
    $type = "";
    $catid = '';
    $toolowstock = '';
	$fourn_id = '';
	$sbarcode = '';


}



if ($action == 'import') {
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    setlocale(LC_ALL, 'es-MX.utf-8');
    header("Content-disposition: attachment; filename=\"Valuacion_de_Productos.csv\"");
    $outputBuffer = fopen("php://output", 'w');
    $sql1="SELECT p.rowid, p.cost_price, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity, p.fk_product_type,
 p.tms as datem, p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte, p.desiredstock, SUM(s.reel) as stock_physique,
  (select f.unitprice from llx_product_fournisseur_price as f where fk_product=p.rowid order by datec desc limit 1) as price_last,
   ((select unitprice from llx_product_fournisseur_price where fk_product=p.rowid order by datec desc limit 1) * sum(s.reel)) as valuacion,
   (p.price_ttc * sum(s.reel)) as importe_venta
    FROM llx_product as p
     LEFT JOIN llx_product_stock as s ON p.rowid = s.fk_product 
     LEFT JOIN llx_entrepot as e ON s.fk_entrepot = e.rowid AND e.entity IN (1) 
     WHERE p.entity IN (1) 
     GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity, p.fk_product_type, p.tms, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock ORDER BY p.ref ASC";
    $result = $db->query($sql1);
    if($db->num_rows($result)>0){
        $data = array();
        fputcsv($outputBuffer,array("Ref","Etiqueta","Stock Matriz","Stock GPE","Stock fisico", "P.U. UEPS", "Importe UEPS","Precio de Venta","Importe Venta"),",");
        $langs->load("bills");
        $langs->load("dict");
        while ($row= $db->fetch_object($result)){
            $product = new Product($db);
            $product->fetch($row->rowid);

			$x = array($row->ref?$row->ref:'');
            array_push($x,$row->label?$row->label:'');

			$sql = "SELECT rowid, ref, lieu, fk_parent, statut from ".MAIN_DB_PREFIX."entrepot WHERE entity IN (1) ORDER BY ref";
			$res = $db->query($sql);
			$numentr = $db->num_rows($res);
			$j = 1;
			$total_physical_stock = 0;
			while($j <= $numentr){
				//J = 1 para Matriz Zac
				//J = 2 para Gpe. 
				$sql_physical_stock = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut, ps.reel, ps.rowid as product_stock_id, p.pmp";
				$sql_physical_stock .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
				$sql_physical_stock .= " ".MAIN_DB_PREFIX."product_stock as ps";
				$sql_physical_stock .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
				$sql_physical_stock .= " WHERE ps.reel != 0";
				$sql_physical_stock .= " AND ps.fk_entrepot = e.rowid";
				$sql_physical_stock .= " AND e.rowid = $j";
				$sql_physical_stock .= " AND e.entity IN (".getEntity('stock').")";
				$sql_physical_stock .= " AND ps.fk_product = ".$row->rowid;
				$sql_physical_stock .= " ORDER BY e.ref";
	
				$resql_physical_stock = $db->query($sql_physical_stock);
				if ($db->query($sql_physical_stock)) {
					$obj = $db->fetch_object($resql_physical_stock);
					$stock_real = price2num($obj->reel, 'MS');
	
	
				}else{
					$stock_real = price2num(0, 'MS');
				}
				// Physical Stock
				$physical_stock = ($stock_real > 0)? $stock_real : 0;
				$total_physical_stock += $physical_stock;
	
				array_push($x,$physical_stock);
	
				$j++;
			}
			array_push($x,$total_physical_stock);
			array_push($x,$row->cost_price?$row->cost_price:'');
			array_push($x,($row->cost_price * $total_physical_stock));
			array_push($x,$row->price_ttc?$row->price_ttc:'');
			array_push($x,$row->importe_venta>0?$row->importe_venta:'');
            fputcsv($outputBuffer,$x,",");
        }
        fclose($outputBuffer);
        exit();
    }else{
        fclose($outputBuffer);
        exit();
    }
}



/*
 * View
 */

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';

$form = new Form($db);
$htmlother = new FormOther($db);

$sql = 'SELECT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
$sql .= ' p.fk_product_type, p.tms as datem,';
$sql .= ' p.duration, p.tosell as statut, p.tobuy, p.seuil_stock_alerte, p.desiredstock, p.cost_price,';
$sql .= ' SUM(s.reel) as stock_physique, ';
$sql .=  '(SELECT reel FROM llx_product_stock WHERE fk_product = p.rowid AND fk_entrepot = 1) as sf_01,'
		.'(SELECT reel FROM llx_product_stock WHERE fk_product = p.rowid AND fk_entrepot = 2) as sf_02,'
		;
$sql .= ' (select f.unitprice from llx_product_fournisseur_price as f where fk_product=p.rowid order by datec desc limit 1) as price_last,
((select unitprice from llx_product_fournisseur_price where fk_product=p.rowid order by datec desc limit 1) * sum(s.reel)) as valuacion, 
(p.price_ttc * sum(s.reel)) as importe_venta';
if (!empty($conf->global->PRODUCT_USE_UNITS)) $sql .= ', u.short_label as unit_short';
$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as s ON p.rowid = s.fk_product';
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot as e ON s.fk_entrepot = e.rowid AND e.entity IN ('.getEntity('entrepot').')';
if (!empty($conf->global->PRODUCT_USE_UNITS)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_units as u on p.fk_unit = u.rowid';
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql .= ", ".MAIN_DB_PREFIX."categorie_product as cp";
$sql .= " WHERE p.entity IN (".getEntity('product').")";
if ($search_categ) $sql .= " AND p.rowid = cp.fk_product"; // Join for the needed table to filter by categ
if ($sall) $sql .= natural_search(array('p.ref', 'p.label', 'p.description', 'p.note'), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type))
{
    if ($type == 1)
    {
        $sql .= " AND p.fk_product_type = '1'";
    }
    else
    {
        $sql .= " AND p.fk_product_type <> '1'";
    }
}
if ($sref)     $sql .= natural_search('p.ref', $sref);
if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);
if ($snom)     $sql .= natural_search('p.label', $snom);
if (!empty($tosell)) $sql .= " AND p.tosell = ".$tosell;
if (!empty($tobuy)) $sql .= " AND p.tobuy = ".$tobuy;
if (!empty($canvas)) $sql .= " AND p.canvas = '".$db->escape($canvas)."'";
if ($catid) $sql .= " AND cp.fk_categorie = ".$catid;
if ($fourn_id > 0) $sql .= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
// Insert categ filter
if ($search_categ) $sql .= " AND cp.fk_categorie = ".$db->escape($search_categ);
$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,";
$sql .= " p.fk_product_type, p.tms, p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock";
if ($toolowstock) $sql .= " HAVING SUM(".$db->ifsql('s.reel IS NULL', '0', 's.reel').") < p.seuil_stock_alerte";
$sql .= $db->order($sortfield, $sortorder);
// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;

	if ($num == 1 && GETPOST('autojumpifoneonly') && ($sall || $snom || $sref))
	{
		$objp = $db->fetch_object($resql);
		header("Location: card.php?id=$objp->rowid");
		exit;
	}

	if (isset($type))
	{
		if ($type == 1) { $texte = $langs->trans("Services"); }
		else { $texte = $langs->trans("Products"); }
	} else {
		$texte = $langs->trans("ProductsAndServices");
	}
	$texte .= ' ('.$langs->trans("MenuStocks").')';

	$param = '';
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall)	    $param .= "&sall=".urlencode($sall);
	if ($tosell)	$param .= "&tosell=".urlencode($tosell);
	if ($tobuy)		$param .= "&tobuy=".urlencode($tobuy);
	if ($type)		$param .= "&type=".urlencode($type);
	if ($fourn_id)	$param .= "&fourn_id=".urlencode($fourn_id);
	if ($snom)		$param .= "&snom=".urlencode($snom);
	if ($sref)		$param .= "&sref=".urlencode($sref);
	if ($search_sale)  $param .= "&search_sale=".urlencode($search_sale);
	if ($search_categ) $param .= "&search_categ=".urlencode($search_categ);
	if ($toolowstock)  $param .= "&toolowstock=".urlencode($toolowstock);
	if ($sbarcode) $param .= "&sbarcode=".urlencode($sbarcode);
	if ($catid)    $param .= "&catid=".urlencode($catid);

	llxHeader("", 'Valuacion de Productos', $helpurl);
    print '<form method="POST" id="FormularioExportacion" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="import">';
    print '</form>';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	print_barre_liste('Valuacion de Productos', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'products', 0, '', '', $limit);

    print '<table width="100%">';
    print '<tr>';
    print '<td align="left">';
    print '<a name="exportar" id="exportar"><img src="../facture/img/xlsx.png" alt=""></a>';
    $script =   '<script language="javascript">
                $(document).ready(function() {
                    $("#exportar").on("click",function() {
                        $("#FormularioExportacion").submit();
                    });
                });
                </script>';
    print $script;
    print '</td>';
    print '</tr>';
    print '</table>';






	$param = '';
	if ($type)		$param .= "&type=".urlencode($type);
	if ($fourn_id)	$param .= "&fourn_id=".urlencode($fourn_id);
	if ($snom)		$param .= "&snom=".urlencode($snom);
	if ($sref)		$param .= "&sref=".urlencode($sref);
	if ($toolowstock)		$param .= "&toolowstock=".urlencode($toolowstock);
	if ($search_categ)		$param .= "&search_categ=".urlencode($search_categ);

	$formProduct = new FormProduct($db);
	$formProduct->loadWarehouses();
	$warehouses_list = $formProduct->cache_warehouses;
	$nb_warehouse = count($warehouses_list);
	$colspan_warehouse = 1;
	if (!empty($conf->global->STOCK_DETAIL_ON_WAREHOUSE)) { $colspan_warehouse = $nb_warehouse > 1 ? $nb_warehouse + 1 : 1; }

    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

	// Fields title search
	print '<tr class="liste_titre_filter">';

	print '<td class="liste_titre" >';
	print '<input class="flat" type="text" name="sref" size="10" value="'.$sref.'">';
	print '</td>';
	print '<td class="liste_titre ">';
	print '<input class="flat" type="text" name="snom" size="20" value="'.$snom.'">';
	print '</td>';
	print '<td class="liste_titre" colspan="6"></td>';

	print '<td class="liste_titre maxwidthsearch right">';

   	$searchpicto = $form->showFilterAndCheckAddButtons(0);
   	print $searchpicto;
	print '</td>';
	print '</tr>';

	//Line for column titles
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", $param, "", "", $sortfield, $sortorder);
    print_liste_field_titre("Stock Matriz", $_SERVER["PHP_SELF"], "sf_01", $param, "", '', $sortfield, $sortorder, 'right ');
    print_liste_field_titre("Stock Gpe.", $_SERVER["PHP_SELF"], "sf_02", $param, "", '', $sortfield, $sortorder, 'right ');
    print_liste_field_titre("PhysicalStock", $_SERVER["PHP_SELF"], "stock_physique", $param, "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("PUUEPS", $_SERVER["PHP_SELF"], "price_last", $param, "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("UEPSImport", $_SERVER["PHP_SELF"], "valuacion", $param, "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Precio de Venta", $_SERVER["PHP_SELF"], "price_ttc", $param, "", '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Importe Precio Venta", $_SERVER["PHP_SELF"], "importe_venta", $param, "", '', $sortfield, $sortorder, 'right ');


	print "</tr>\n";

	while ($i < min($num, $limit))
	{
		$objp = $db->fetch_object($resql);

		$product = new Product($db);
		$product->fetch($objp->rowid);
		$product->load_stock();

		print '<tr>';
		print '<td >';
		print $product->getNomUrl(1, '', 16);
		//if ($objp->stock_theorique < $objp->seuil_stock_alerte) print ' '.img_warning($langs->trans("StockTooLow"));
		print '</td>';
		print '<td>'.$product->label.'</td>';	

        //Desired stock Principal

		$sql = "SELECT rowid, ref, lieu, fk_parent, statut from ".MAIN_DB_PREFIX."entrepot WHERE entity IN (1) ORDER BY ref";
		$res = $db->query($sql);
		$numentr = $db->num_rows($res);
		$j = 1;
		$total_physical_stock = 0;
		while($j <= $numentr){
			$sql_physical_stock = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut, ps.reel, ps.rowid as product_stock_id, p.pmp";
			$sql_physical_stock .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
			$sql_physical_stock .= " ".MAIN_DB_PREFIX."product_stock as ps";
			$sql_physical_stock .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
			$sql_physical_stock .= " WHERE ps.reel != 0";
			$sql_physical_stock .= " AND ps.fk_entrepot = e.rowid";
			$sql_physical_stock .= " AND e.rowid = $j";
			$sql_physical_stock .= " AND e.entity IN (".getEntity('stock').")";
			$sql_physical_stock .= " AND ps.fk_product = ".$product->id;
			$sql_physical_stock .= " ORDER BY e.ref";

			$resql_physical_stock = $db->query($sql_physical_stock);
			if ($db->query($sql_physical_stock)) {
				$obj = $db->fetch_object($resql_physical_stock);
				$stock_real = price2num($obj->reel, 'MS');


			}else{
				$stock_real = price2num(0, 'MS');
			}
			// Physical Stock
			$physical_stock = ($stock_real > 0)? $stock_real : 0;
			$total_physical_stock += $physical_stock;



			
			print '<td class="right">'.$physical_stock.'</td>';

			$j++;
		}
		print '<td class="right">'.$total_physical_stock.'</td>';
        //print '<td class="right">'.$product->stock_warehouse[1]->real.'</td>';

        //Desired stock GPE
        //print '<td class="right">'.$product->stock_warehouse[2]->real.'</td>';

		// Real stock
		//print '<td class="right">';
        //if ($objp->seuil_stock_alerte != '' && ($objp->stock_physique < $objp->seuil_stock_alerte)) print img_warning($langs->trans("StockTooLow")).' ';
        //print price2num($objp->stock_physique, 'MS');
		//print '</td>';
        print '<td class="right">';
        if($objp->cost_price>0){
			print price($objp->cost_price);
        }
        print '</td>';
        print '<td class="right">';
        // if($objp->valuacion>0){
			// print "price($objp->valuacion)";
			print price($objp->cost_price*$total_physical_stock);
        // }
        print '</td>';

		print '<td class="right">';
        if($objp->price_ttc>0){
			print price($objp->price_ttc);
        }
        print '</td>';

		print '<td class="right">';
        if($objp->importe_venta>0){
			print price($objp->importe_venta);
        }
        print '</td>';


        print "</tr>\n";
		$i++;
	}

	print "</table>";
	print '</div>';

	print '</form>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();


function Physical_stock(){

}
