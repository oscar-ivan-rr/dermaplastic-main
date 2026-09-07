<?php
/**
 * Carga stock de prueba en Almacen DG (copia lotes desde CEDIS, sin restar CEDIS).
 *
 * Opciones CLI:
 *   --limit=N     Máx. productos (default 150)
 *   --qty=N       Cantidad por lote (default 25; no supera el lote CEDIS)
 *   --all         Sin límite de productos (cuidado: puede tardar)
 *
 * CLI: docker exec dermaplastic-test-php php /var/www/html/htdocs/custom/dev-tools/seed_stock_almacen_dg.php
 */

if (php_sapi_name() === 'cli') {
	define('NOLOGIN', 1);
	define('NOREQUIREMENU', 1);
	define('NOREQUIREHTML', 1);
	define('NOREQUIREAJAX', 1);
	define('NOCSRFCHECK', 1);
}

require __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/functions.php';

header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli') {
	if (empty($user->id) || empty($user->admin)) {
		accessforbidden();
	}
} else {
	$user->fetch(1);
	$user->getrights();
}

$limit = 150;
$qtyPerLot = 25;
$noLimit = false;
if (php_sapi_name() === 'cli') {
	foreach (array_slice($argv, 1) as $arg) {
		if ($arg === '--all') {
			$noLimit = true;
		} elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
			$limit = (int) $m[1];
		} elseif (preg_match('/^--qty=(\d+)$/', $arg, $m)) {
			$qtyPerLot = max(1, (int) $m[1]);
		}
	}
}

$dgWhId = !empty($conf->global->DG_WAREHOUSE) ? (int) $conf->global->DG_WAREHOUSE : 0;
$cedisWhId = !empty($conf->global->CEDIS_WAREHOUSE) ? (int) $conf->global->CEDIS_WAREHOUSE : 29;
if ($dgWhId <= 0) {
	echo "ERROR: Constante DG_WAREHOUSE no definida. Ejecuta setup_almacen_dg.php primero.\n";
	exit(1);
}

echo "Almacen DG id={$dgWhId}, CEDIS id={$cedisWhId}\n";
echo "limit=".($noLimit ? 'ALL' : $limit)." qty_por_lote={$qtyPerLot}\n";

// Productos prioritarios: líneas recientes de OC a Almacen DG
$priorityIds = array();
$dgSup = !empty($conf->global->DG_SUPPLIER) ? (int) $conf->global->DG_SUPPLIER : 0;
if ($dgSup > 0) {
	$sql = "SELECT DISTINCT cfd.fk_product"
		." FROM ".MAIN_DB_PREFIX."commande_fournisseurdet AS cfd"
		." JOIN ".MAIN_DB_PREFIX."commande_fournisseur AS cf ON cf.rowid = cfd.fk_commande"
		." WHERE cf.fk_soc = ".$dgSup
		." AND cfd.fk_product > 0"
		." ORDER BY cf.rowid DESC"
		." LIMIT 50";
	$res = $db->query($sql);
	while ($res && ($obj = $db->fetch_object($res))) {
		$priorityIds[(int) $obj->fk_product] = true;
	}
}

// Top productos con stock/lotes en CEDIS
$sql = "SELECT ps.fk_product, SUM(pb.qty) AS lot_qty, MAX(ps.reel) AS reel"
	." FROM ".MAIN_DB_PREFIX."product_batch AS pb"
	." JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON ps.rowid = pb.fk_product_stock"
	." JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid = ps.fk_product"
	." WHERE ps.fk_entrepot = ".$cedisWhId
	." AND pb.qty > 0"
	." AND p.tosell = 1"
	." AND p.fk_product_type = 0"
	." GROUP BY ps.fk_product"
	." ORDER BY reel DESC";
if (!$noLimit) {
	$sql .= " LIMIT ".((int) $limit);
}
$res = $db->query($sql);
if (!$res) {
	echo "ERROR: ".$db->lasterror()."\n";
	exit(1);
}

$productIds = array();
while ($obj = $db->fetch_object($res)) {
	$productIds[(int) $obj->fk_product] = true;
}
foreach (array_keys($priorityIds) as $pid) {
	$productIds[$pid] = true;
}

echo "Productos a cargar: ".count($productIds)."\n";

$inventorycode = generateUniqueInventoryCode($db, $user, 'DG-SEED-'.dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
$label = 'Stock prueba Almacen DG (seed)';
echo "inventorycode={$inventorycode}\n";

$ok = 0;
$skip = 0;
$err = 0;
$units = 0;
$product = new Product($db);

foreach (array_keys($productIds) as $fk_product) {
	if ($product->fetch($fk_product) <= 0) {
		$err++;
		echo "SKIP fetch producto {$fk_product}\n";
		continue;
	}

	$sqlLot = "SELECT pb.batch, pb.eatby, pb.sellby, pb.qty"
		." FROM ".MAIN_DB_PREFIX."product_batch AS pb"
		." JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON ps.rowid = pb.fk_product_stock"
		." WHERE ps.fk_entrepot = ".$cedisWhId
		." AND ps.fk_product = ".$fk_product
		." AND pb.qty > 0"
		." ORDER BY pb.qty DESC";
	$resLot = $db->query($sqlLot);
	if (!$resLot || $db->num_rows($resLot) === 0) {
		// Sin lote en CEDIS: entrada simple + lote de prueba
		$qty = $qtyPerLot;
		$batch = 'DGTEST'.dol_print_date(dol_now(), '%Y%m');
		$eatby = dol_time_plus_duree(dol_now(), 24, 'm');
		$sellby = dol_time_plus_duree(dol_now(), 12, 'm');
		$r = $product->correct_stock_batch($user, $dgWhId, $qty, 0, $label, 0, $eatby, $sellby, $batch, $inventorycode);
		if ($r > 0) {
			$ok++;
			$units += $qty;
			echo "OK {$product->ref}: +{$qty} lote {$batch} (sintetico)\n";
		} else {
			$err++;
			echo "ERR {$product->ref}: ".$product->error."\n";
		}
		continue;
	}

	$addedForProduct = 0;
	while ($lot = $db->fetch_object($resLot)) {
		$qty = min($qtyPerLot, (float) $lot->qty);
		if ($qty <= 0) {
			continue;
		}
		$batch = trim((string) $lot->batch);
		if ($batch === '') {
			$batch = 'DGTEST'.dol_print_date(dol_now(), '%Y%m');
		}
		$eatby = !empty($lot->eatby) ? $db->jdate($lot->eatby) : dol_time_plus_duree(dol_now(), 24, 'm');
		$sellby = !empty($lot->sellby) ? $db->jdate($lot->sellby) : dol_time_plus_duree(dol_now(), 12, 'm');

		$r = $product->correct_stock_batch($user, $dgWhId, $qty, 0, $label, 0, $eatby, $sellby, $batch, $inventorycode);
		if ($r > 0) {
			$ok++;
			$units += $qty;
			$addedForProduct += $qty;
			echo "OK {$product->ref}: +{$qty} lote {$batch}\n";
		} else {
			$err++;
			echo "ERR {$product->ref} lote {$batch}: ".$product->error."\n";
		}
		// Un lote por producto basta para pruebas (rápido); si se quiere más, quitar break
		break;
	}
	if ($addedForProduct <= 0) {
		$skip++;
	}
}

$sqlSum = "SELECT COUNT(*) AS c, COALESCE(SUM(reel),0) AS s FROM ".MAIN_DB_PREFIX."product_stock WHERE fk_entrepot = ".$dgWhId;
$resSum = $db->query($sqlSum);
$sum = $resSum ? $db->fetch_object($resSum) : null;

echo "\n--- Resumen ---\n";
echo "movimientos_ok={$ok} errores={$err} skip={$skip} unidades_agregadas={$units}\n";
if ($sum) {
	echo "DG product_stock rows={$sum->c} sum_reel={$sum->s}\n";
}
echo "Done.\n";
