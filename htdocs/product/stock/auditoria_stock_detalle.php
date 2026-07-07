<?php
/**
 *  \file       htdocs/product/stock/auditoria_stock_detalle.php
 *  \ingroup    stock
 *  \brief      Detalle de lotes de un producto para la auditoria de stock:
 *              muestra cada registro de llx_product_batch por almacen y lo compara
 *              contra el stock de llx_product_stock.
 */

require '../../main.inc.php';

$langs->loadLangs(array('main', 'products', 'stocks', 'productbatch'));

if (empty($user->rights->stock->lire)) accessforbidden();

$fk_product  = GETPOST('fk_product', 'int');
$fk_entrepot = GETPOST('fk_entrepot', 'int');

// Force selected warehouse if user has default warehouse
if (!$user->rights->stock->show_all_warehouses && !empty($user->fk_warehouse)) {
    $fk_entrepot = $user->fk_warehouse;
}

if ($fk_product <= 0) {
    header('Location: '.DOL_URL_ROOT.'/product/stock/auditoria_stock.php');
    exit;
}

// Paginacion del detalle de lotes
$limit  = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page   = GETPOST('page', 'int');
if ($page < 0) $page = 0;
$offset = $limit * $page;

/*
 * SQL (puro)
 */

// Datos del producto
$sqlp = "SELECT p.rowid, p.ref, p.label, p.tobatch FROM ".MAIN_DB_PREFIX."product p";
$sqlp .= " WHERE p.rowid = ".((int) $fk_product)." AND p.entity IN (".getEntity('product').")";
$resqlp = $db->query($sqlp);
if (!$resqlp) dol_print_error($db);
$product = $db->fetch_object($resqlp);
if (!$product) {
    header('Location: '.DOL_URL_ROOT.'/product/stock/auditoria_stock.php');
    exit;
}

// Resumen por almacen: stock vs total de lotes
$sqlres  = "SELECT e.rowid as warehouse_id, e.ref as warehouse_ref, ps.reel as stock_qty,";
$sqlres .= " COALESCE(b.batch_total, 0) as batch_qty,";
$sqlres .= " COALESCE(b.nb_lotes, 0) as nb_lotes,";
$sqlres .= " COALESCE(b.nb_lotes_neg, 0) as nb_lotes_neg,";
$sqlres .= " (ps.reel - COALESCE(b.batch_total, 0)) as diferencia";
$sqlres .= " FROM ".MAIN_DB_PREFIX."product_stock ps";
$sqlres .= " INNER JOIN ".MAIN_DB_PREFIX."entrepot e ON e.rowid = ps.fk_entrepot";
$sqlres .= " LEFT JOIN (";
$sqlres .= "   SELECT fk_product_stock, SUM(qty) as batch_total, COUNT(rowid) as nb_lotes,";
$sqlres .= "   SUM(CASE WHEN qty < 0 THEN 1 ELSE 0 END) as nb_lotes_neg";
$sqlres .= "   FROM ".MAIN_DB_PREFIX."product_batch GROUP BY fk_product_stock";
$sqlres .= " ) b ON b.fk_product_stock = ps.rowid";
$sqlres .= " WHERE ps.fk_product = ".((int) $fk_product);
$sqlres .= " AND e.entity IN (".getEntity('stock').")";
if ($fk_entrepot > 0) $sqlres .= " AND ps.fk_entrepot = ".((int) $fk_entrepot);
$sqlres .= " ORDER BY e.ref";

$resqlres = $db->query($sqlres);
if (!$resqlres) dol_print_error($db);

// Total de lotes y suma para la paginacion y la fila de totales (sobre el conjunto completo, no la pagina)
$sqllotcount  = "SELECT COUNT(*) as nb, COALESCE(SUM(pb.qty), 0) as total_qty";
$sqllotcount .= " FROM ".MAIN_DB_PREFIX."product_batch pb";
$sqllotcount .= " INNER JOIN ".MAIN_DB_PREFIX."product_stock ps ON ps.rowid = pb.fk_product_stock";
$sqllotcount .= " INNER JOIN ".MAIN_DB_PREFIX."entrepot e ON e.rowid = ps.fk_entrepot";
$sqllotcount .= " WHERE ps.fk_product = ".((int) $fk_product);
$sqllotcount .= " AND e.entity IN (".getEntity('stock').")";
if ($fk_entrepot > 0) $sqllotcount .= " AND ps.fk_entrepot = ".((int) $fk_entrepot);

$resqllotcount = $db->query($sqllotcount);
if (!$resqllotcount) dol_print_error($db);
$lottotals = $db->fetch_object($resqllotcount);
$nbtotallots = (int) $lottotals->nb;

// Detalle de cada lote (fechas de caducidad desde llx_product_lot cuando existen)
$sqllot  = "SELECT e.rowid as warehouse_id, e.ref as warehouse_ref,";
$sqllot .= " pb.batch, pb.qty, pb.tms,";
$sqllot .= " pl.eatby, pl.sellby";
$sqllot .= " FROM ".MAIN_DB_PREFIX."product_batch pb";
$sqllot .= " INNER JOIN ".MAIN_DB_PREFIX."product_stock ps ON ps.rowid = pb.fk_product_stock";
$sqllot .= " INNER JOIN ".MAIN_DB_PREFIX."entrepot e ON e.rowid = ps.fk_entrepot";
// llx_product_lot tiene filas duplicadas por (producto, lote) en esta BD: se agrega para no multiplicar filas
$sqllot .= " LEFT JOIN (";
$sqllot .= "   SELECT fk_product, batch, MIN(eatby) as eatby, MIN(sellby) as sellby";
$sqllot .= "   FROM ".MAIN_DB_PREFIX."product_lot GROUP BY fk_product, batch";
$sqllot .= " ) pl ON pl.fk_product = ps.fk_product AND pl.batch = pb.batch";
$sqllot .= " WHERE ps.fk_product = ".((int) $fk_product);
$sqllot .= " AND e.entity IN (".getEntity('stock').")";
if ($fk_entrepot > 0) $sqllot .= " AND ps.fk_entrepot = ".((int) $fk_entrepot);
$sqllot .= " ORDER BY e.ref, pl.eatby, pb.batch";
// Fila extra: convencion de Dolibarr para que print_barre_liste muestre la navegacion
$sqllot .= " LIMIT ".((int) $limit + 1)." OFFSET ".((int) $offset);

$resqllot = $db->query($sqllot);
if (!$resqllot) dol_print_error($db);
$numlots = $db->num_rows($resqllot);

/*
 * View
 */

$title = "Auditoría stock: detalle de lotes";
llxHeader('', $title, '');

// Clases propias: no usar .error/.warning aqui porque theme/alert_handler.js les inyecta un boton de cierre "x"
print '<style>
.auditneg  { color: #c62828; font-weight: bold; }
.auditwarn { color: #b26a00; font-weight: bold; }
.auditok   { color: #2e7d32; font-weight: bold; }
</style>';

$backurl = DOL_URL_ROOT.'/product/stock/auditoria_stock.php';

print load_fiche_titre($title, '<a class="button" href="'.$backurl.'">&laquo; Volver a auditoría</a>', 'stock');

// Ficha del producto
print '<div class="fichecenter"><div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';
print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td><a href="'.DOL_URL_ROOT.'/product/card.php?id='.((int) $product->rowid).'">'.img_object('', 'product', 'class="paddingright"').dol_escape_htmltag($product->ref).'</a>';
print ' &nbsp; <a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.((int) $product->rowid).'">(ver stock del producto)</a></td></tr>';
print '<tr><td>'.$langs->trans("Label").'</td><td>'.dol_escape_htmltag($product->label).'</td></tr>';
if ($fk_entrepot > 0) {
    print '<tr><td>'.$langs->trans("Warehouse").'</td><td>Filtrado por almacén (<a href="'.$_SERVER["PHP_SELF"].'?fk_product='.((int) $fk_product).'">ver todos los almacenes</a>)</td></tr>';
}
print '</table></div><br>';

// Resumen por almacen
print load_fiche_titre('Resumen por almacén', '', '');
print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent">';
print '<tr class="liste_titre">';
print '<th>Almacén</th>';
print '<th class="right">Stock almacén</th>';
print '<th class="right">Total lotes</th>';
print '<th class="right">Nº lotes</th>';
print '<th class="right">Lotes negativos</th>';
print '<th class="right">Diferencia</th>';
print '<th class="center">Estado</th>';
print '</tr>';

$nbres = 0;
while ($obj = $db->fetch_object($resqlres)) {
    $nbres++;
    $hasdiff = (round($obj->stock_qty, 3) != round($obj->batch_qty, 3));
    $hasneg  = ($obj->stock_qty < 0 || $obj->batch_qty < 0);
    $diff    = (float) $obj->diferencia;

    print '<tr class="oddeven">';
    print '<td><a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.((int) $obj->warehouse_id).'">'.img_object('', 'stock', 'class="paddingright"').dol_escape_htmltag($obj->warehouse_ref).'</a></td>';
    print '<td class="right'.($obj->stock_qty < 0 ? ' auditneg' : '').'">'.price2num($obj->stock_qty, 'MS').'</td>';
    print '<td class="right'.($obj->batch_qty < 0 ? ' auditneg' : '').'">'.price2num($obj->batch_qty, 'MS').'</td>';
    print '<td class="right">'.((int) $obj->nb_lotes).'</td>';
    print '<td class="right'.($obj->nb_lotes_neg > 0 ? ' auditneg' : '').'">'.((int) $obj->nb_lotes_neg).'</td>';
    print '<td class="right"><b'.($hasdiff ? ' class="auditneg"' : '').'>'.($diff > 0 ? '+' : '').price2num($diff, 'MS').'</b></td>';
    print '<td class="center">';
    if ($hasneg)      print '<span class="auditneg" title="Cantidades negativas">Negativo</span>';
    elseif ($hasdiff) print '<span class="auditwarn" title="El stock no coincide con la suma de lotes">Diferencia</span>';
    else              print '<span class="auditok">OK</span>';
    print '</td>';
    print '</tr>';
}
if (!$nbres) {
    print '<tr class="oddeven"><td colspan="7" class="opacitymedium center">Este producto no tiene stock registrado'.($fk_entrepot > 0 ? ' en este almacén' : '').'</td></tr>';
}
print '</table>';
print '</div><br>';

// Detalle de lotes (paginado)
$param = '&fk_product='.((int) $fk_product);
if ($fk_entrepot > 0) $param .= '&fk_entrepot='.((int) $fk_entrepot);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.((int) $limit);

// El selector de registros por pagina (selectlimit) hace submit de su formulario padre
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="fk_product" value="'.((int) $fk_product).'">';
if ($fk_entrepot > 0) print '<input type="hidden" name="fk_entrepot" value="'.((int) $fk_entrepot).'">';

print_barre_liste('Detalle de lotes', $page, $_SERVER["PHP_SELF"], $param, '', '', '', $numlots, $nbtotallots, '', 0, '', '', $limit);
print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent">';
print '<tr class="liste_titre">';
print '<th>Almacén</th>';
print '<th>Lote</th>';
print '<th class="right">Cantidad</th>';
print '<th class="center">Caducidad (eatby)</th>';
print '<th class="center">Consumo pref. (sellby)</th>';
print '<th class="center">Última modificación</th>';
print '</tr>';

$nblot = 0;
while ($nblot < min($numlots, $limit) && ($obj = $db->fetch_object($resqllot))) {
    $nblot++;

    print '<tr class="oddeven">';
    print '<td>'.dol_escape_htmltag($obj->warehouse_ref).'</td>';
    print '<td>'.img_object('', 'lot', 'class="paddingright"').dol_escape_htmltag($obj->batch).'</td>';
    print '<td class="right'.($obj->qty < 0 ? ' auditneg' : '').'">'.price2num($obj->qty, 'MS');
    if ($obj->qty < 0) print ' '.img_warning('Lote con cantidad negativa');
    print '</td>';
    print '<td class="center">'.($obj->eatby ? dol_print_date($db->jdate($obj->eatby), 'day') : '<span class="opacitymedium">-</span>').'</td>';
    print '<td class="center">'.($obj->sellby ? dol_print_date($db->jdate($obj->sellby), 'day') : '<span class="opacitymedium">-</span>').'</td>';
    print '<td class="center">'.($obj->tms ? dol_print_date($db->jdate($obj->tms), 'dayhour') : '').'</td>';
    print '</tr>';
}
if (!$nblot) {
    print '<tr class="oddeven"><td colspan="6" class="opacitymedium center">Sin registros de lote para este producto'.($fk_entrepot > 0 ? ' en este almacén' : '').'</td></tr>';
}
if ($nbtotallots) {
    print '<tr class="liste_total">';
    print '<td colspan="2">'.$langs->trans("Total").' ('.$nbtotallots.' lotes)</td>';
    print '<td class="right">'.price2num($lottotals->total_qty, 'MS').'</td>';
    print '<td colspan="3"></td>';
    print '</tr>';
}
print '</table>';
print '</div>';
print '</form>';

$db->free($resqlres);
$db->free($resqllot);

llxFooter();
$db->close();
