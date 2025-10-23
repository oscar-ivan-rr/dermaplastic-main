<?php
// Load translation files required by the page
$langs->loadLangs(array("companies","donations"));

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";

$search_product = GETPOST("search_product");
$search_almacen = GETPOST("search_almacen");
$search_lote = GETPOST("search_lote");
$search_cantidad = GETPOST("search_cantidad");
// Fecha limite de caducidad
$date_start_creationday		=GETPOST("date_start_creationday");
$date_start_creationmonth	=GETPOST("date_start_creationmonth");
$date_start_creationyear	=GETPOST("date_start_creationyear");
$date_end_creationmonth		=GETPOST("date_end_creationmonth");
$date_end_creationday		=GETPOST("date_end_creationday");
$date_end_creationyear		=GETPOST("date_end_creationyear");

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('orderlist'));

/*
 * View
 */

$form=new Form($db);
if (! empty($conf->projet->enabled)) $projectstatic=new Project($db);

// Genere requete de liste des dons
$sql = "
SELECT 
e.rowid as entrepot_id, e.ref as warehouse, ps.fk_product, pb.fk_product_stock, pb.eatby, pb.batch, pb.qty, pl.rowid as id_lote
FROM ".MAIN_DB_PREFIX."product_batch as pb 
JOIN 
".MAIN_DB_PREFIX."product_stock as ps 
ON 
pb.fk_product_stock = ps.rowid
JOIN ".MAIN_DB_PREFIX."entrepot as e
ON
ps.fk_entrepot = e.rowid 
JOIN ".MAIN_DB_PREFIX."product as p
ON
ps.fk_product = p.rowid
JOIN ".MAIN_DB_PREFIX."product_lot as pl
ON
pl.batch = pb.batch AND pl.fk_product = p.rowid AND pl.eatby = pb.eatby
WHERE ";
if(!$user->rights->stock->show_all_warehouses) $sql.= "e.rowid = ".$user->fk_warehouse." AND ";
if($search_product){
    $sql .=  "p.ref like '%".$search_product."%' AND ";
}
if($search_almacen > 0 ){
    $sql .=  "e.rowid = '".$search_almacen."' AND ";
}
if($search_lote){
    $sql .=  "pb.batch like '%".$search_lote."%' AND ";
}
if($search_cantidad && is_numeric($search_cantidad )){
    $sql .=  "pb.qty = ".$search_cantidad." AND ";
}
if($date_start_creationyear && !$date_end_creationyear)
    $sql.=" pb.eatby >= '".$date_start_creationyear."-".$date_start_creationmonth."-".$date_start_creationday."' AND ";
else if (!$date_start_creationyear && $date_end_creationyear)
    $sql.=" pb.eatby <= '".$date_end_creationyear."-".$date_end_creationmonth."-".$date_end_creationday."' AND ";
else if ($date_start_creationyear && $date_end_creationyear)
    $sql.=" pb.eatby BETWEEN '".$date_start_creationyear."-".$date_start_creationmonth."-".$date_start_creationday."' AND '".$date_end_creationyear."-".$date_end_creationmonth."-".$date_end_creationday."' AND ";

// Obtener los que tengan mas de 3 meses de caducidad
$sql .= "(pb.eatby <= DATE_ADD(NOW(), INTERVAL 3 MONTH) OR pb.eatby < now()) AND ";
$sql .= "true";

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
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

    print '<div style="overflow-y: auto; width: 100%; height:100%;">';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="type" value="'.$type.'">';

    print '<h3 style="color:red;">Productos Caducados ( '.$nbtotalofrecords.' )   '.img_warning("Lote caducado").'</h3>';
	print_barre_liste($langs->trans(""), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, $newcardbutton);
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    // Filters lines
    print '<tr class="liste_titre_filter">';
    // Nombre del producto
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_product" value="'.$search_product.'">';
    print '</td>';
    // Almacen
    print '<td class="liste_titre">';
    if($user->rights->stock->show_all_warehouses){
        $formproduct = new FormProduct($db);
        print $formproduct->selectWarehouses('', 'search_almacen', 'warehouseopen', 1);
    }
    print '</td>';
    // lote
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_lote" value="'.$search_lote.'">';
    print '</td>';
    // caducidad
    if($date_start_creationyear)$date_start_creation=dol_mktime(0,0,0,$date_start_creationmonth,$date_start_creationday,$date_start_creationyear);
    else $date_start_creation=null;
    if($date_end_creationyear)$date_end_creation=dol_mktime(0,0,0,$date_end_creationmonth,$date_end_creationday,$date_end_creationyear);
    else $date_end_creation=null;
    print '<td>';
    print "Desde<br>".$form->select_date($date_start_creation,'date_start_creation',0,0,0,'',1,0,1);
    print "<br>Hasta<br>".$form->select_date($date_end_creation,'date_end_creation',0,0,0,'',1,0,1);
    print '</td>';
    // cantidad
    print '<td class="liste_titre">';
    print '<input class="flat" size="10" type="text" name="search_cantidad" value="'.$search_cantidad.'">';
    print '</td>';
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
    // Action column
    print '<td class="liste_titre center">';
    print '<input type="submit" value="Filtrar">';
    print '</td>';
    
    print "</tr>\n";
    print '<tr class="liste_titre">';
	print_liste_field_titre("Producto", $_SERVER["PHP_SELF"], "p.rowid", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Almacen", $_SERVER["PHP_SELF"], "pb.fk_product_stock", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Lote", $_SERVER["PHP_SELF"], "pb.batch", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Fecha Limite de Venta", $_SERVER["PHP_SELF"], "pb.eatby", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Cant.", $_SERVER["PHP_SELF"], "pb.qty", "", $param, '', $sortfield, $sortorder);
	print '<td></td>';
    
	print "</tr>\n";

    require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
    require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productstockentrepot.class.php';
    require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';
    $object = new Product($db);
    $product_lot_static = new Productlot($db);
    $entrepot = new Entrepot($db);
	while ($i < min($num, $limit))
	{
        $objp = $db->fetch_object($resql);
        $resss = $object->fetch($objp->fk_product);
        $ressss = $product_lot_static->fetch($objp->id_lote);
        $ressss = $entrepot->fetch($objp->entrepot_id);
        
        print '<tr class="oddeven">';
        
        print '<td>'.$object->getNomUrl().'</td>';
        print '<td>'.$objp->warehouse.'</td>';
        print '<td>'.$product_lot_static->getNomUrl(1).'</td>';
        print '<td>'.dol_print_date($objp->eatby).'</td>';
        print '<td>'.$objp->qty.'</td>';
        print '<td></td>';

        print '</tr>';
        
		
		$i++;
	}
    print "</table>";
    print "</form>\n";
    $db->free($resql);
}
else
{
    dol_print_error($db);
}
llxFooter();
?>