<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file 	htdocs/product/ajax/products.php
 * \brief 	File to return Ajax response on product list request.
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

require '../../main.inc.php';

$htmlname = GETPOST('htmlname', 'alpha');
$socid = GETPOST('socid', 'int');
$type = GETPOST('type', 'int');
$mode = GETPOST('mode', 'int');
$status = ((GETPOST('status', 'int') >= 0) ? GETPOST('status', 'int') : - 1);
$outjson = (GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$price_level = GETPOST('price_level', 'int');
$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$price_by_qty_rowid = GETPOST('pbq', 'int');
$finished = GETPOST('finished', 'int');
$alsoproductwithnosupplierprice = GETPOST('alsoproductwithnosupplierprice', 'int');
$warehouseStatus = GETPOST('warehousestatus', 'alpha');
$hidepriceinlabel = GETPOST('hidepriceinlabel', 'int');


/*
 * View
 */

// print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', $_GET));

if (!empty($action) && $action == 'fetch' && !empty($id))
{
	// action='fetch' is used to get product information on a product. So when action='fetch', id must be the product id.
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$outjson = array();

	$object = new Product($db);
	$ret = $object->fetch($id);
	if ($ret > 0)
	{
		$outref = $object->ref;
		$outlabel = $object->label;
		$outdesc = $object->description;
		$outtype = $object->type;
		$table_element = GETPOST('table_element');
        //$sql="SELECT u.label FROM ".MAIN_DB_PREFIX."product_extrafields  as pe LEFT JOIN ".MAIN_DB_PREFIX."c_cfdimx_unidad_medida as u ON u.code=pe.umed WHERE pe.fk_object = ".$object->id;
        $sql="SELECT unit_entrada,unit_salida, exentoiva FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$object->id;
        $resql = $db->query($sql);
		$objpUnidad = $db->fetch_object($resql);
		if($objpUnidad){
			$outUentrada = $objpUnidad->unit_entrada;
			$outUsalida = $objpUnidad->unit_salida;
			$outexentoiva = $objpUnidad->exentoiva;
		}
		$outqty = 1;
		// $outdiscount = 0;

		$found = false;

		$price_level = 1;
		if ($socid > 0) {
			$thirdpartytemp = new Societe($db);
			$thirdpartytemp->fetch($socid);
			$price_level = $thirdpartytemp->price_level;
		}

		// Price by qty
		if (!empty($price_by_qty_rowid) && $price_by_qty_rowid >= 1 && (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))) 		// If we need a particular price related to qty
		{
			$sql = "SELECT price, unitprice, quantity, remise_percent";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_price_by_qty ";
			$sql .= " WHERE rowid=".$price_by_qty_rowid."";

			$result = $db->query($sql);
			if ($result) {
				$objp = $db->fetch_object($result);
				if ($objp) {
					$found = true;
					$outprice_ht = price($objp->unitprice);
					$outprice_ttc = price($objp->unitprice * (1 + ($object->tva_tx / 100)));
					$outpricebasetype = $object->price_base_type;
					$outtva_tx = $object->tva_tx;
					$outqty = $objp->quantity;
					$outdiscount = $objp->remise_percent;
				}
			}
		}
		$outdiscount = ($object->temp_discount != 0) ? $object->temp_discount : (($object->desc_max != 0) ? (($object->desc_max >= 10) ? $thirdpartytemp->remise_percent : $object->desc_max) : 0);
		// Multiprice
		if (!$found && isset($price_level) && $price_level >= 1 && (!empty($conf->global->PRODUIT_MULTIPRICES))) // If we need a particular price level (from 1 to 6)
		{
			$sql = "SELECT price, price_ttc, price_base_type, tva_tx";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_price ";
			$sql .= " WHERE fk_product = '".$id."'";
			$sql .= " AND entity IN (".getEntity('productprice').")";
			$sql .= " AND price_level = ".((int) $price_level);
			$sql .= " ORDER BY date_price";
			$sql .= " DESC LIMIT 1";

			$result = $db->query($sql);
			if ($result) {
				$objp = $db->fetch_object($result);
				if ($objp) {
					$found = true;
					$outprice_ht = price($objp->price);
					$outprice_ttc = price($objp->price_ttc);
					$outpricebasetype = $objp->price_base_type;
					$outtva_tx = $objp->tva_tx;
				}
			}
		}

		// Price by customer
		if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES) && !empty($socid)) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

			$prodcustprice = new Productcustomerprice($db);

			$filter = array('t.fk_product' => $object->id, 't.fk_soc' => $socid);

			$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
			if ($result) {
				if (count($prodcustprice->lines) > 0) {
					$found = true;
					$outprice_ht = price($prodcustprice->lines [0]->price);
					$outprice_ttc = price($prodcustprice->lines [0]->price_ttc);
					$outpricebasetype = $prodcustprice->lines [0]->price_base_type;
					$outtva_tx = $prodcustprice->lines [0]->tva_tx;
				}
			}
		}

		if (!$found) {
			$outprice_ht = price($object->price);
			$outprice_ttc = price($object->price_ttc);
			$outpricebasetype = $object->price_base_type;
			$outtva_tx = $object->tva_tx;
		}

		$sql = "SELECT claveprodserv,umed,numpedimento,exentoiva,objimp FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object=".$object->id;
		$umed = '';
		$claveprodser = '';
		$resql = $db->query($sql);
		if($resql){
		    $extra = $db->fetch_object($resql);
		    $claveprodser = $extra->claveprodserv;
		    $umed = $extra->umed;
			$outnum_pedimento = $extra->numpedimento;
			//$outexentoiva = $extra->exentoiva;
			$outobjimp = $extra->objimp;
        }

		$outjson = array('ref' => $outref, 'label' => $outlabel, 'desc' => $outdesc, 'type' => $outtype, 'price_ht' => $outprice_ht, 'price_ttc' => $outprice_ttc, 'pricebasetype' => $outpricebasetype, 'tva_tx' => $outtva_tx, 'qty' => $outqty, 'discount' => $outdiscount, 'umed' => $umed, 'claveprodser' => $claveprodser, 'Uentrada' => $outUentrada, 'Usalida' => $outUsalida, 'num_pedimento' => $outnum_pedimento, 'exentoiva' => $outexentoiva, 'objimp' => $outobjimp);
	}

	echo json_encode($outjson);
}
elseif (!empty($action) && $action == 'search_by_barcode')
{
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	
	$barcode = GETPOST('barcode', 'alpha');
	$socid = GETPOST('socid', 'int');
	
	$outjson = array('success' => false);
	
	if (!empty($barcode)) {
		// Search for product by barcode
		$sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.tva_tx, p.price_base_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql .= " WHERE p.barcode = '".$db->escape($barcode)."'";
		$sql .= " AND p.entity IN (".getEntity('product').")";
		
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$outjson = array(
					'success' => true,
					'product_id' => $obj->rowid,
					'ref' => $obj->ref,
					'label' => $obj->label,
					'price_ht' => price($obj->price),
					'price_ttc' => price($obj->price_ttc),
					'tva_tx' => $obj->tva_tx,
					'price_base_type' => $obj->price_base_type
				);
				
				// Get additional product information
				$sql = "SELECT unit_entrada, unit_salida, exentoiva FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$obj->rowid;
				$resql = $db->query($sql);
				if ($resql) {
					$objpUnidad = $db->fetch_object($resql);
					if ($objpUnidad) {
						$outjson['Uentrada'] = $objpUnidad->unit_entrada;
						$outjson['Usalida'] = $objpUnidad->unit_salida;
						$outjson['exentoiva'] = $objpUnidad->exentoiva;
					}
				}
				
				// Get extrafields information
				$sql = "SELECT claveprodserv, umed, numpedimento, objimp FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = ".$obj->rowid;
				$resql = $db->query($sql);
				if ($resql) {
					$extra = $db->fetch_object($resql);
					if ($extra) {
						$outjson['claveprodser'] = $extra->claveprodserv;
						$outjson['umed'] = $extra->umed;
						$outjson['num_pedimento'] = $extra->numpedimento;
						$outjson['objimp'] = $extra->objimp;
					}
				}
			}
		}
	}
	
	echo json_encode($outjson);
}
else
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	$langs->loadLangs(array("main", "products"));

	top_httphead();

	if (empty($htmlname))
	{
		print json_encode(array());
	    return;
	}

	$match = preg_grep('/('.$htmlname.'[0-9]+)/', array_keys($_GET));
	sort($match);

	$idprod = (!empty($match[0]) ? $match[0] : '');

	if (GETPOST($htmlname, 'alpha') == '' && (!$idprod || !GETPOST($idprod, 'alpha')))
	{
		print json_encode(array());
	    return;
	}

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (($idprod && GETPOST($idprod, 'alpha')) ? GETPOST($idprod, 'alpha') : (GETPOST($htmlname, 'alpha') ? GETPOST($htmlname, 'alpha') : ''));

	$form = new Form($db);

	if (empty($mode) || $mode == 1) {  // mode=1: customer
		$arrayresult = $form->select_produits_list("", $htmlname, $type, 0, $price_level, $searchkey, $status, $finished, $outjson, $socid, '1', 0, '', $hidepriceinlabel, $warehouseStatus);
	} elseif ($mode == 2) {            // mode=2: supplier
		$arrayresult = $form->select_produits_fournisseurs_list($socid, "", $htmlname, $type, "", $searchkey, $status, $outjson, 0, $alsoproductwithnosupplierprice);
	}

	$db->close();

	if ($outjson)
		print json_encode($arrayresult);
}
