<?php
/**
 * Data de prueba para reaprovisionar desde Almacen DG hacia sucursales.
 *
 * - Ajusta desiredstock / alert / stock_max en la sucursal destino
 * - Baja stock de sucursal si hace falta (para que aparezcan en Replenish V2)
 * - Crea product_fournisseur_price del proveedor Almacen DG
 *
 * CLI:
 *   docker exec dermaplastic-test-php php /var/www/html/htdocs/custom/dev-tools/seed_replenish_almacen_dg.php
 *   docker exec dermaplastic-test-php php .../seed_replenish_almacen_dg.php --warehouse=26 --max=20 --limit=80
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
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/functions.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli') {
	if (empty($user->id) || empty($user->admin)) {
		accessforbidden();
	}
} else {
	$user->fetch(1);
	$user->getrights();
}

$targetWh = 26; // Sucursal Altacia
$stockMax = 20;
$desired = 2;
$alert = 5;
$limit = 80;
$targetStock = 0; // dejar stock sucursal en 0 para forzar necesidad

if (php_sapi_name() === 'cli') {
	foreach (array_slice($argv, 1) as $arg) {
		if (preg_match('/^--warehouse=(\d+)$/', $arg, $m)) {
			$targetWh = (int) $m[1];
		} elseif (preg_match('/^--max=(\d+)$/', $arg, $m)) {
			$stockMax = max(1, (int) $m[1]);
		} elseif (preg_match('/^--desired=(\d+)$/', $arg, $m)) {
			$desired = (int) $m[1];
		} elseif (preg_match('/^--alert=(\d+)$/', $arg, $m)) {
			$alert = (int) $m[1];
		} elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
			$limit = max(1, (int) $m[1]);
		} elseif (preg_match('/^--target-stock=(\d+)$/', $arg, $m)) {
			$targetStock = (int) $m[1];
		}
	}
}

$dgWh = !empty($conf->global->DG_WAREHOUSE) ? (int) $conf->global->DG_WAREHOUSE : 0;
$dgSup = !empty($conf->global->DG_SUPPLIER) ? (int) $conf->global->DG_SUPPLIER : 0;
if ($dgWh <= 0 || $dgSup <= 0) {
	echo "ERROR: Faltan DG_WAREHOUSE / DG_SUPPLIER. Ejecuta setup_almacen_dg.php\n";
	exit(1);
}

$ent = new Entrepot($db);
if ($ent->fetch($targetWh) <= 0) {
	echo "ERROR: Almacén destino id={$targetWh} no encontrado\n";
	exit(1);
}
echo "Sucursal destino: {$ent->ref} (id={$targetWh})\n";
echo "DG warehouse={$dgWh} supplier={$dgSup}\n";
echo "props desired={$desired} alert={$alert} max={$stockMax} target_stock_sucursal={$targetStock} limit={$limit}\n";

$sql = "SELECT p.rowid, p.ref, p.cost_price, p.tva_tx, dg.reel AS dg_qty,"
	." IFNULL(ps.reel, 0) AS suc_qty"
	." FROM ".MAIN_DB_PREFIX."product AS p"
	." JOIN ".MAIN_DB_PREFIX."product_stock AS dg ON dg.fk_product = p.rowid AND dg.fk_entrepot = ".$dgWh." AND dg.reel > 0"
	." LEFT JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON ps.fk_product = p.rowid AND ps.fk_entrepot = ".$targetWh
	." WHERE p.tosell = 1 AND p.fk_product_type = 0"
	." ORDER BY dg.reel DESC"
	." LIMIT ".(int) $limit;
$res = $db->query($sql);
if (!$res) {
	echo "ERROR: ".$db->lasterror()."\n";
	exit(1);
}

$inventorycode = generateUniqueInventoryCode($db, $user, 'DG-REPL-'.dol_print_date(dol_now(), '%Y%m%d%H%M%S'));
$label = 'Ajuste prueba reaprovisionamiento DG → '.$ent->ref;
$product = new Product($db);

$nProps = 0;
$nPfp = 0;
$nStock = 0;
$nErr = 0;
$eligible = 0;

while ($obj = $db->fetch_object($res)) {
	$pid = (int) $obj->rowid;
	$sucQty = (float) $obj->suc_qty;
	$dgQty = (float) $obj->dg_qty;

	// --- warehouse properties ---
	$maxForProduct = $stockMax;
	if ($sucQty >= $maxForProduct) {
		$maxForProduct = (int) $sucQty + $stockMax; // fuerza necesidad aunque tenga stock
	}
	$sqlPw = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_warehouse_properties"
		." WHERE fk_product = ".$pid." AND fk_entrepot = ".$targetWh." LIMIT 1";
	$resPw = $db->query($sqlPw);
	if ($resPw && ($pw = $db->fetch_object($resPw))) {
		$sqlUp = "UPDATE ".MAIN_DB_PREFIX."product_warehouse_properties SET"
			." desiredstock = ".(float) $desired.","
			." seuil_stock_alerte = ".(float) $alert.","
			." stock_max = ".(float) $maxForProduct
			." WHERE rowid = ".(int) $pw->rowid;
		if (!$db->query($sqlUp)) {
			$nErr++;
			echo "ERR props update {$obj->ref}: ".$db->lasterror()."\n";
			continue;
		}
	} else {
		$sqlIns = "INSERT INTO ".MAIN_DB_PREFIX."product_warehouse_properties"
			." (fk_product, fk_entrepot, desiredstock, seuil_stock_alerte, stock_max)"
			." VALUES (".$pid.", ".$targetWh.", ".(float) $desired.", ".(float) $alert.", ".(float) $maxForProduct.")";
		if (!$db->query($sqlIns)) {
			$nErr++;
			echo "ERR props insert {$obj->ref}: ".$db->lasterror()."\n";
			continue;
		}
	}
	$nProps++;

	// --- bajar stock sucursal si está por encima del target ---
	if ($sucQty > $targetStock) {
		$remove = $sucQty - $targetStock;
		if ($product->fetch($pid) > 0) {
			// Preferir sacar de un lote existente en sucursal
			$sqlLot = "SELECT pb.batch, pb.eatby, pb.sellby, pb.qty"
				." FROM ".MAIN_DB_PREFIX."product_batch AS pb"
				." JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON ps.rowid = pb.fk_product_stock"
				." WHERE ps.fk_entrepot = ".$targetWh." AND ps.fk_product = ".$pid." AND pb.qty > 0"
				." ORDER BY pb.qty DESC";
			$resLot = $db->query($sqlLot);
			$left = $remove;
			$okMove = true;
			if ($resLot && $db->num_rows($resLot) > 0) {
				while ($left > 0 && ($lot = $db->fetch_object($resLot))) {
					$q = min($left, (float) $lot->qty);
					$eatby = !empty($lot->eatby) ? $db->jdate($lot->eatby) : '';
					$sellby = !empty($lot->sellby) ? $db->jdate($lot->sellby) : '';
					$r = $product->correct_stock_batch($user, $targetWh, $q, 1, $label, 0, $eatby, $sellby, $lot->batch, $inventorycode);
					if ($r <= 0) {
						$okMove = false;
						echo "ERR stock- {$obj->ref} lote {$lot->batch}: ".$product->error."\n";
						break;
					}
					$left -= $q;
				}
			}
			// Si no hay lotes o quedó remanente sin lote
			if ($okMove && $left > 0) {
				$r = $product->correct_stock($user, $targetWh, $left, 1, $label, 0, $inventorycode);
				if ($r <= 0) {
					$okMove = false;
					echo "ERR stock- {$obj->ref}: ".$product->error."\n";
				} else {
					$left = 0;
				}
			}
			if ($okMove && $left <= 0) {
				$nStock++;
				$sucQty = $targetStock;
			} else {
				$nErr++;
			}
		}
	}

	// --- precio proveedor Almacen DG ---
	$cost = (float) $obj->cost_price;
	$unit = price2num(round($cost * 1.10, 2));
	$tva = ($obj->tva_tx !== '' && $obj->tva_tx !== null) ? $obj->tva_tx : 16;
	$sqlPfp = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price"
		." WHERE fk_product = ".$pid." AND fk_soc = ".$dgSup." AND quantity = 1 LIMIT 1";
	$resPfp = $db->query($sqlPfp);
	if ($resPfp && ($pfp = $db->fetch_object($resPfp))) {
		$sqlUp = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price SET"
			." price = ".$db->escape($unit).","
			." unitprice = ".$db->escape($unit).","
			." tva_tx = ".$db->escape($tva).","
			." multicurrency_unitprice = ".$db->escape($unit).","
			." multicurrency_price = ".$db->escape($unit).","
			." reason = 'Seed DG'"
			." WHERE rowid = ".(int) $pfp->rowid;
		if ($db->query($sqlUp)) {
			$nPfp++;
		} else {
			$nErr++;
			echo "ERR pfp update {$obj->ref}: ".$db->lasterror()."\n";
		}
	} else {
		$refFourn = 'DG-'.dol_trunc($obj->ref, 120);
		$sqlIns = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price"
			." (entity, datec, fk_product, fk_soc, ref_fourn, price, quantity, unitprice, tva_tx, fk_user,"
			." fk_multicurrency, multicurrency_code, multicurrency_tx, multicurrency_unitprice, multicurrency_price, reason)"
			." VALUES (".(int) $conf->entity.", '".$db->idate(dol_now())."', ".$pid.", ".$dgSup.","
			." '".$db->escape($refFourn)."', ".$db->escape($unit).", 1, ".$db->escape($unit).", ".$db->escape($tva).", ".(int) $user->id.","
			." 1, 'MXN', 1, ".$db->escape($unit).", ".$db->escape($unit).", 'Seed DG')";
		if ($db->query($sqlIns)) {
			$nPfp++;
		} else {
			$nErr++;
			echo "ERR pfp insert {$obj->ref}: ".$db->lasterror()."\n";
		}
	}

	$need = max(0, $maxForProduct - $sucQty);
	$hubCap = (int) $dgQty;
	$tobuy = min($need, $hubCap);
	if ($tobuy > 0) {
		$eligible++;
		echo "OK {$obj->ref}: suc={$sucQty} max={$maxForProduct} dg={$dgQty} tobuy~{$tobuy} pu={$unit}\n";
	} else {
		echo "SKIP {$obj->ref}: sin cantidad a pedir (suc={$sucQty} max={$maxForProduct} dg={$dgQty})\n";
	}
}

echo "\n--- Resumen ---\n";
echo "props={$nProps} pfp={$nPfp} stock_ajustados={$nStock} errores={$nErr} elegibles_replenish~{$eligible}\n";
echo "Probar: Productos → Stock → Reaprovisionamiento V2\n";
echo "  Almacén = {$ent->ref}\n";
echo "  Proveedor = Almacen DG\n";
echo "Done.\n";
