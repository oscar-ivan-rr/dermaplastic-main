<?php
/**
 *  \file       htdocs/product/stock/auditoria_stock.php
 *  \ingroup    stock
 *  \brief      Auditoria de stock: compara llx_product_stock contra los totales de lotes (llx_product_batch)
 *              para detectar discrepancias entre el stock por almacen y la suma de sus lotes.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

$langs->loadLangs(array('main', 'products', 'stocks', 'productbatch'));

if (empty($user->rights->stock->lire)) accessforbidden();

$fk_warehouse = GETPOST('fk_warehouse', 'int');
$filtro       = GETPOST('filtro', 'alpha');
$search_ref   = trim(GETPOST('search_ref', 'alphanohtml'));

if ($filtro === '') $filtro = 'any'; // Por defecto mostrar solo registros con alguna discrepancia
if (!in_array($filtro, array('any', 'diff', 'neg', 'all'))) $filtro = 'any';

// Force selected warehouse if user has default warehouse
$disableWarehouse = 0;
if (!$user->rights->stock->show_all_warehouses) {
    $fk_warehouse = $user->fk_warehouse;
    $disableWarehouse = 1;
}

// Pagination / sorting
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page      = GETPOST('page', 'int');
if ($page < 0) $page = 0;
$offset    = $limit * $page;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');

// Whitelist de columnas ordenables -> expresion SQL real (evita inyeccion via sortfield)
$sortable = array(
    'ref'       => 'p.ref',
    'label'     => 'p.label',
    'warehouse' => 'e.ref',
    'stock'     => 'ps.reel',
    'batch_qty' => 'batch_qty',
    'nb_lotes'  => 'nb_lotes',
    'nb_lotes_neg' => 'nb_lotes_neg',
    'diferencia'=> 'diferencia',
    'absdiff'   => 'ABS(ps.reel - COALESCE(b.batch_total, 0))',
);
if (empty($sortfield) || empty($sortable[$sortfield])) $sortfield = 'absdiff';
$sortorder = (strtoupper($sortorder) == 'ASC') ? 'ASC' : 'DESC';

/*
 * SQL (puro, sin modelos de Dolibarr)
 */

// Los lotes cuelgan de la linea de stock: llx_product_batch.fk_product_stock -> llx_product_stock.rowid
$sqlfrom  = " FROM ".MAIN_DB_PREFIX."product_stock ps";
$sqlfrom .= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ps.fk_product";
$sqlfrom .= " INNER JOIN ".MAIN_DB_PREFIX."entrepot e ON e.rowid = ps.fk_entrepot";
$sqlfrom .= " LEFT JOIN (";
$sqlfrom .= "   SELECT fk_product_stock, SUM(qty) as batch_total, COUNT(rowid) as nb_lotes, MIN(qty) as min_lote,";
$sqlfrom .= "   SUM(CASE WHEN qty < 0 THEN 1 ELSE 0 END) as nb_lotes_neg";
$sqlfrom .= "   FROM ".MAIN_DB_PREFIX."product_batch GROUP BY fk_product_stock";
$sqlfrom .= " ) b ON b.fk_product_stock = ps.rowid";

$sqlwhere  = " WHERE p.entity IN (".getEntity('product').")";
$sqlwhere .= " AND e.entity IN (".getEntity('stock').")";
// Productos gestionados por lote, con registros de lote (en esta BD tobatch=0 aunque hay lotes),
// o con stock negativo (un negativo siempre es auditable aunque no tenga lotes)
$sqlwhere .= " AND (p.tobatch > 0 OR b.fk_product_stock IS NOT NULL OR ps.reel < 0)";

if ($fk_warehouse > 0) {
    $sqlwhere .= " AND ps.fk_entrepot = ".((int) $fk_warehouse);
}
if ($search_ref !== '') {
    $sqlwhere .= " AND (p.ref LIKE '%".$db->escape($search_ref)."%' OR p.label LIKE '%".$db->escape($search_ref)."%')";
}

// Se redondea a 3 decimales para no marcar como discrepancia diferencias por precision de flotantes
$cond_diff = "ROUND(ps.reel, 3) <> ROUND(COALESCE(b.batch_total, 0), 3)";
$cond_neg  = "(ps.reel < 0 OR COALESCE(b.batch_total, 0) < 0)";
if ($filtro == 'diff')     $sqlwhere .= " AND ".$cond_diff;
elseif ($filtro == 'neg')  $sqlwhere .= " AND ".$cond_neg;
elseif ($filtro == 'any')  $sqlwhere .= " AND (".$cond_diff." OR ".$cond_neg.")";

// Totales del conjunto filtrado completo (no solo la pagina actual)
$sqlcount  = "SELECT COUNT(*) as nb,";
$sqlcount .= " SUM(ps.reel) as total_stock,";
$sqlcount .= " SUM(COALESCE(b.batch_total, 0)) as total_batch,";
$sqlcount .= " SUM(ps.reel - COALESCE(b.batch_total, 0)) as total_diff";
$sqlcount .= $sqlfrom.$sqlwhere;

$resqlcount = $db->query($sqlcount);
if (!$resqlcount) dol_print_error($db);
$totals = $db->fetch_object($resqlcount);
$nbtotalofrecords = (int) $totals->nb;

$sql  = "SELECT p.rowid as product_id, p.ref, p.label, p.tobatch,";
$sql .= " e.rowid as warehouse_id, e.ref as warehouse_ref,";
$sql .= " ps.reel as stock_qty,";
$sql .= " COALESCE(b.batch_total, 0) as batch_qty,";
$sql .= " COALESCE(b.nb_lotes, 0) as nb_lotes,";
$sql .= " COALESCE(b.nb_lotes_neg, 0) as nb_lotes_neg,";
$sql .= " COALESCE(b.min_lote, 0) as min_lote,";
$sql .= " (ps.reel - COALESCE(b.batch_total, 0)) as diferencia";
$sql .= $sqlfrom.$sqlwhere;
$sql .= " ORDER BY ".$sortable[$sortfield]." ".$sortorder.", p.ref ASC";
// Se pide una fila extra: es la convencion de Dolibarr para que print_barre_liste muestre la navegacion
$sql .= " LIMIT ".((int) $limit + 1)." OFFSET ".((int) $offset);

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);
$num = $db->num_rows($resql);

/*
 * View
 */

$formproduct = new FormProduct($db);

$title = "Auditoría stock";
llxHeader('', $title, '');

// Clases propias: no usar .error/.warning aqui porque theme/alert_handler.js les inyecta un boton de cierre "x"
print '<style>
.auditneg  { color: #c62828; font-weight: bold; }
.auditwarn { color: #b26a00; font-weight: bold; }
.auditok   { color: #2e7d32; font-weight: bold; }
</style>';

$param = '';
if ($fk_warehouse > 0)     $param .= '&fk_warehouse='.urlencode($fk_warehouse);
if ($filtro !== '')        $param .= '&filtro='.urlencode($filtro);
if ($search_ref !== '')    $param .= '&search_ref='.urlencode($search_ref);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);

// El formulario abre antes de print_barre_liste: el selector de registros por pagina (selectlimit)
// hace submit de su formulario padre, asi que debe quedar dentro de este form
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'stock', 0, '', '', $limit);

print '<div class="opacitymedium">Compara el stock por almacén contra la suma de sus lotes. Una diferencia distinta de cero o cantidades negativas indican inconsistencia que puede provocar stocks negativos al vender.</div><br>';

print '<div class="divsearchfield">';
print $langs->trans("Warehouse").': ';
print $formproduct->selectWarehouses($fk_warehouse, 'fk_warehouse', '', 1, $disableWarehouse, 0, '', 0, 0, array(), 'maxwidth300');
print '</div> ';

print '<div class="divsearchfield">';
print 'Mostrar: ';
print '<select name="filtro" class="flat">';
print '<option value="any"'.($filtro == 'any' ? ' selected' : '').'>Cualquier discrepancia</option>';
print '<option value="diff"'.($filtro == 'diff' ? ' selected' : '').'>Con diferencia stock vs lotes</option>';
print '<option value="neg"'.($filtro == 'neg' ? ' selected' : '').'>Con negativos</option>';
print '<option value="all"'.($filtro == 'all' ? ' selected' : '').'>Todos</option>';
print '</select>';
print '</div> ';

print '<div class="divsearchfield">';
print $langs->trans("Product").': ';
print '<input type="text" name="search_ref" class="flat" size="20" value="'.dol_escape_htmltag($search_ref).'" placeholder="Ref o etiqueta">';
print '</div> ';

print '<div class="divsearchfield">';
print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
print ' <a class="button" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("Reset").'</a>';
print '</div>';
print '<div class="clearboth"></div>';
print '</form><br>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent">';

print '<tr class="liste_titre">';
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "ref", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Etiqueta", $_SERVER["PHP_SELF"], "label", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Almacén", $_SERVER["PHP_SELF"], "warehouse", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Stock almacén", $_SERVER["PHP_SELF"], "stock", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("Total lotes", $_SERVER["PHP_SELF"], "batch_qty", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("Nº lotes", $_SERVER["PHP_SELF"], "nb_lotes", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("Lotes negativos", $_SERVER["PHP_SELF"], "nb_lotes_neg", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("Diferencia", $_SERVER["PHP_SELF"], "diferencia", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("Estado", $_SERVER["PHP_SELF"], "", "", $param, 'class="center"', $sortfield, $sortorder);
print '</tr>';

$i = 0;
while ($i < min($num, $limit) && ($obj = $db->fetch_object($resql))) {
    $i++;
    $diff       = (float) $obj->diferencia;
    $hasdiff    = (round($obj->stock_qty, 3) != round($obj->batch_qty, 3));
    $hasneg     = ($obj->stock_qty < 0 || $obj->batch_qty < 0);

    print '<tr class="oddeven">';

    print '<td><a href="'.DOL_URL_ROOT.'/product/stock/auditoria_stock_detalle.php?fk_product='.((int) $obj->product_id).'&fk_entrepot='.((int) $obj->warehouse_id).'" title="Ver detalle de lotes">'.img_object('', 'product', 'class="paddingright"').dol_escape_htmltag($obj->ref).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->label).'</td>';
    print '<td><a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.((int) $obj->warehouse_id).'">'.img_object('', 'stock', 'class="paddingright"').dol_escape_htmltag($obj->warehouse_ref).'</a></td>';

    print '<td class="right'.($obj->stock_qty < 0 ? ' auditneg' : '').'">'.price2num($obj->stock_qty, 'MS').'</td>';
    print '<td class="right'.($obj->batch_qty < 0 ? ' auditneg' : '').'">'.price2num($obj->batch_qty, 'MS');
    if ($obj->min_lote < 0) print ' '.img_warning('Hay lotes con cantidad negativa');
    print '</td>';
    print '<td class="right">'.((int) $obj->nb_lotes).'</td>';
    print '<td class="right'.($obj->nb_lotes_neg > 0 ? ' auditneg' : '').'">'.((int) $obj->nb_lotes_neg).'</td>';

    print '<td class="right"><b'.($hasdiff ? ' class="auditneg"' : '').'>'.($diff > 0 ? '+' : '').price2num($diff, 'MS').'</b></td>';

    print '<td class="center">';
    if ($hasneg)         print '<span class="auditneg" title="Cantidades negativas">Negativo</span>';
    elseif ($hasdiff)    print '<span class="auditwarn" title="El stock no coincide con la suma de lotes">Diferencia</span>';
    else                 print '<span class="auditok">OK</span>';
    print '</td>';

    print '</tr>';
}

if (!$num) {
    print '<tr class="oddeven"><td colspan="9" class="opacitymedium center">'.$langs->trans("NoRecordFound").'</td></tr>';
}

// Totales del conjunto filtrado completo
if ($nbtotalofrecords > 0) {
    print '<tr class="liste_total">';
    print '<td colspan="3">'.$langs->trans("Total").' ('.$nbtotalofrecords.' registros)</td>';
    print '<td class="right">'.price2num($totals->total_stock, 'MS').'</td>';
    print '<td class="right">'.price2num($totals->total_batch, 'MS').'</td>';
    print '<td></td>';
    print '<td></td>';
    print '<td class="right"><b>'.($totals->total_diff > 0 ? '+' : '').price2num($totals->total_diff, 'MS').'</b></td>';
    print '<td></td>';
    print '</tr>';
}

print '</table>';
print '</div>';

$db->free($resql);

llxFooter();
$db->close();
