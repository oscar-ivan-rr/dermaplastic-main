<?php
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productstockentrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';
define("BACKEND_URL", getenv('BACKEND_PLATFORM_URL'));
define("API_KEY", getenv('VALID_API_KEY_ERP'));
/**
     *  Send notification to Api when stock is updated
     * @param  DoliDB	 $db			 Database handler
     * @param  int       $product_id     Product
     * @param  int       $warehouse_id   Warehouse
     * @return int                       <0 if KO, 0 if not found, >0 if OK
     */
function sendUpdateStockNotification($db, $product_id, $warehouse_id)
{
    $warehouse = new Entrepot($db);
    $warehouse->fetch($warehouse_id);
    //check if is not CEDIS Warehouse
    if ($warehouse->ref != "CEDIS") {
        return 1;
    }
    $url = BACKEND_URL."api/stock/update/".$product_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // evita que la respuesta se imprima en la página
    $result = curl_exec($ch);
    curl_close($ch);
    return 0;
}

function sendPackId($id_orden)
{
    $url = BACKEND_URL."api/mercadolibre/orders/".$id_orden."/send_shipment_to_erp";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // evita que la respuesta se imprima en la página
    $result = curl_exec($ch);
    curl_close($ch);
    return 0;
}

/**
 * Check if an inventory/transfer code is already used in movements or drafts.
 *
 * @param DoliDB $db
 * @param string $code
 * @return bool
 */
function inventoryCodeExists($db, $code)
{
	$code = trim((string) $code);
	if ($code === '') {
		return false;
	}
	$escaped = $db->escape($code);

	$sql = "SELECT inventorycode FROM " . MAIN_DB_PREFIX . "stock_mouvement WHERE inventorycode = '" . $escaped . "' LIMIT 1";
	$res = $db->query($sql);
	if ($res && $db->num_rows($res) > 0) {
		return true;
	}

	$sql = "SELECT code FROM " . MAIN_DB_PREFIX . "stock_mouvement_draft WHERE code = '" . $escaped . "' LIMIT 1";
	$res = $db->query($sql);
	return ($res && $db->num_rows($res) > 0);
}

/**
 * Generate a unique inventory/transfer code.
 * Format: YmdHis + userId(4) + random(2). Reuses $preferred when still free.
 * Prevents same-second collisions between users (root cause of shared transfer codes).
 *
 * @param DoliDB     $db
 * @param User       $user
 * @param string     $preferred Optional code from the form
 * @return string
 */
function generateUniqueInventoryCode($db, $user, $preferred = '')
{
	$preferred = trim((string) $preferred);
	if ($preferred !== '' && !inventoryCodeExists($db, $preferred)) {
		return $preferred;
	}

	$userId = isset($user->id) ? (int) $user->id : 0;
	for ($i = 0; $i < 20; $i++) {
		$code = dol_print_date(dol_now(), '%Y%m%d%H%M%S')
			. sprintf('%04d', $userId)
			. sprintf('%02d', mt_rand(0, 99));
		if (!inventoryCodeExists($db, $code)) {
			return $code;
		}
		usleep(10000);
	}

	return dol_print_date(dol_now(), '%Y%m%d%H%M%S')
		. sprintf('%04d', $userId)
		. substr(str_replace('.', '', uniqid('', true)), -6);
}

function updateShopifyPrice($product_id) {
    $headers = [
        'api-key: ' . API_KEY
    ];
    $url = BACKEND_URL.'api/erp/product/'.$product_id . '/pricing-sync';
    $ch = curl_init();
    // curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
    // curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // evita que la respuesta se imprima en la página
    $result = curl_exec($ch);
    //$httpcode = curl_getinfo($result, CURLINFO_HTTP_CODE);
    //var_dump($httpcode);
    curl_close($ch);
    return 0;
}
