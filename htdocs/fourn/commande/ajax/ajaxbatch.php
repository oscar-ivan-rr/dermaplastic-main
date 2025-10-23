<?php

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productstockentrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/functions.php';


$langs->loadLangs(array("bills", "orders", "sendings", "companies", "deliveries", "products", "stocks", "receptions", "productbatch"));

// Recibir los datos enviados por AJAX
$batchData = GETPOST('batchData');
$fecha = 0;

$error = 0;

if ($user->socid)
	$socid = $user->socid;

// Add a transaction for all the stock movements
$db->begin();

// Track completed movements to avoid duplicates
$processedMovements = array();

$consolidatedBatchData = array();

$giftPriceThreshold = 0.2; // Products with price <= 0.2 are considered gifts

dol_syslog("Starting batch processing with ".count($batchData)." items", LOG_DEBUG);
function randomString($length = 10) {
	return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

foreach ($batchData as $data) {
	$isGift = false;
	if (isset($data['idCommande']) && isset($data['idProd'])) {
		if (!empty($data['fk_commandefourndet'])) {
			$sql = "SELECT subprice, total_ttc, qty FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet 
					WHERE rowid = " . $data['fk_commandefourndet'];
		} else {
			// Otherwise search by command and product
			$sql = "SELECT subprice, total_ttc, qty FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet 
					WHERE fk_commande = " . $data['idCommande'] . " 
					AND fk_product = " . $data['idProd'];
		}
		$resql = $db->query($sql);
		if ($resql && $obj = $db->fetch_object($resql)) {
			if (($obj->subprice <= $giftPriceThreshold || $obj->total_ttc == 0) && $obj->qty == 1) {
				$isGift = true;
			}
		} 
	}
	
	$batchKey = randomString();
	
	$consolidatedBatchData[$batchKey] = $data;
	$consolidatedBatchData[$batchKey]['isGift'] = $isGift;
}


foreach ($consolidatedBatchData as $data) {
	// Generate a unique key for this stock movement
	$movementKey = $data['idProd'] . '_' . $data['idWarehouse'] . '_' . $data['batch'] . '_' . $data['eatby'] . '_' . $data['qty'] . '_' . $data['fk_commandefourndet'];
	
	// Skip if we've already processed this exact movement in this batch
	if (in_array($movementKey, $processedMovements)) {
		continue;
	}
	
	// Check if this exact stock movement already exists in the database
	$sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "stock_mouvement";
	$sql .= " WHERE fk_product = " . $data['idProd'];
	$sql .= " AND fk_entrepot = " . $data['idWarehouse'];
	$sql .= " AND batch = '" . $db->escape($data['batch']) . "'";
	$sql .= " AND label = '" . $db->escape($data['comments']) . "'";
	$sql .= " AND value = " . $data['qty'];
	$sql .= " AND fk_origin = " . $data['fk_commandefourndet'];
	$sql .= " AND origintype = 'commande_fournisseurdet'";


	// Add time window check (last 5 minutes)
	$timeWindow = dol_now() - (5 * 60); // 5 minutes ago in timestamp format
	$sql .= " AND datem > '" . $db->idate($timeWindow) . "'";
	
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && $obj->count > 0) {
			// This movement already exists, skip it
			$processedMovements[] = $movementKey;
			continue;
		}
	}
	
	// Execute the correct_stock_batch function
	$object = new Product($db);
	$result = $object->fetch($data['idProd']);
	
	$res = $object->correct_stock_batch(
		$user,
		$data['idWarehouse'],
		$data['qty'],
		0,
		$data['comments'],
		'',
		strtotime($data['eatby']),
		strtotime($data['sellby']),
		$data['batch'],
		'',
		'commande_fournisseurdet',
		$data['fk_commandefourndet']
	);
	// Check if there was an error
	if ($res < 0) {
		$error++;
		break;
	} else {
		$processedMovements[] = $movementKey;
		sendUpdateStockNotification($db, $data['idProd'], $data['idWarehouse']);
	}
}
if ($error > 0) {
	$db->rollback();
	echo json_encode(array('success' => false));
} else {
	$db->commit();
	echo json_encode(array('success' => true));
}
