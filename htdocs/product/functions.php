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

/**
 * Proveedor hub interno (CEDIS o Almacen DG).
 * @param int $socid
 * @return bool
 */
function isInternalHubSupplier($socid)
{
	global $conf;
	$socid = (int) $socid;
	if ($socid <= 0) {
		return false;
	}
	if (!empty($conf->global->CEDIS_SUPPLIER) && $socid === (int) $conf->global->CEDIS_SUPPLIER) {
		return true;
	}
	if (!empty($conf->global->DG_SUPPLIER) && $socid === (int) $conf->global->DG_SUPPLIER) {
		return true;
	}
	return false;
}

/**
 * @param int $socid
 * @return bool
 */
function isAlmacenDgSupplier($socid)
{
	global $conf;
	return !empty($conf->global->DG_SUPPLIER) && (int) $socid === (int) $conf->global->DG_SUPPLIER;
}

/**
 * Warehouse id del hub asociado al proveedor (CEDIS o Almacen DG).
 * @param int $socid
 * @return int
 */
function getHubWarehouseId($socid)
{
	global $conf;
	if (isAlmacenDgSupplier($socid) && !empty($conf->global->DG_WAREHOUSE)) {
		return (int) $conf->global->DG_WAREHOUSE;
	}
	return !empty($conf->global->CEDIS_WAREHOUSE) ? (int) $conf->global->CEDIS_WAREHOUSE : 0;
}

/**
 * @param DoliDB $db
 * @param int    $entrepot_id
 * @return bool
 */
function isSucursalWarehouse($db, $entrepot_id)
{
	$entrepot_id = (int) $entrepot_id;
	if ($entrepot_id <= 0) {
		return false;
	}
	$e = new Entrepot($db);
	if ($e->fetch($entrepot_id) <= 0) {
		return false;
	}
	$ref = !empty($e->ref) ? $e->ref : $e->libelle;
	return (strpos($ref, 'Sucursal') === 0);
}

/**
 * True if customer order already has a linked customer invoice (DG flow).
 *
 * @param DoliDB $db
 * @param int    $commande_id
 * @return bool
 */
function dgOrderAlreadyHasInvoice($db, $commande_id)
{
	$commande_id = (int) $commande_id;
	if ($commande_id <= 0) {
		return false;
	}
	$sql = "SELECT ee.rowid FROM ".MAIN_DB_PREFIX."element_element AS ee"
		." WHERE (ee.fk_source = ".$commande_id." AND ee.sourcetype = 'commande' AND ee.targettype = 'facture')"
		." OR (ee.fk_target = ".$commande_id." AND ee.targettype = 'commande' AND ee.sourcetype = 'facture')"
		." LIMIT 1";
	$res = $db->query($sql);
	return ($res && $db->num_rows($res) > 0);
}

/**
 * Factura de venta a la sucursal por envío desde Almacen DG (costo + 10%).
 * No mueve stock (ya salió en el envío / dispatch).
 *
 * @param DoliDB     $db
 * @param User       $user
 * @param Expedition $expedition
 * @param Commande   $commande Cliente (pedido a hub)
 * @return array{ok:bool,skipped?:bool,error?:string,facture_id?:int,facture_ref?:string}
 */
function createInvoiceFromDgShipment($db, $user, $expedition, $commande)
{
	global $conf;

	$result = array('ok' => false);

	if (empty($commande->fk_commande_fourn)) {
		$result['skipped'] = true;
		$result['ok'] = true;
		return $result;
	}

	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	$po = new CommandeFournisseur($db);
	if ($po->fetch((int) $commande->fk_commande_fourn) <= 0 || !isAlmacenDgSupplier($po->socid)) {
		$result['skipped'] = true;
		$result['ok'] = true;
		return $result;
	}

	if (!empty($commande->id) && dgOrderAlreadyHasInvoice($db, $commande->id)) {
		$result['skipped'] = true;
		$result['ok'] = true;
		return $result;
	}

	if (empty($expedition->lines)) {
		$expedition->fetch_lines();
	}
	if (empty($expedition->lines)) {
		$result['error'] = 'Envio sin lineas para facturar DG';
		return $result;
	}

	$note = 'Factura automatica Almacen DG → sucursal. Traslado '.$expedition->ref
		.(!empty($commande->ref) ? ' / pedido '.$commande->ref : '')
		.(!empty($po->ref) ? ' / OC '.$po->ref : '')
		.'. Precio = costo + 10%.';

	$facture = new Facture($db);
	$facture->socid = $commande->socid;
	$facture->type = Facture::TYPE_STANDARD;
	$facture->date = dol_now('tzuser');
	$facture->ref_client = !empty($commande->ref_client) ? $commande->ref_client : '';
	$facture->note_public = $note;
	$facture->note_private = $note;
	$facture->origin = 'shipping';
	$facture->origin_id = $expedition->id;
	$facture->linked_objects['shipping'] = $expedition->id;
	$facture->linked_objects['commande'] = $commande->id;
	if (!empty($po->id)) {
		$facture->linked_objects['order_supplier'] = $po->id;
	}

	$facture_id = $facture->create($user);
	if ($facture_id <= 0) {
		$result['error'] = 'Error al crear factura DG: '.(!empty($facture->error) ? $facture->error : 'create failed');
		return $result;
	}

	$product = new Product($db);
	$error = 0;
	foreach ($expedition->lines as $line) {
		$qty = price2num($line->qty);
		$fk_product = (int) $line->fk_product;
		if ($qty <= 0 || $fk_product <= 0) {
			continue;
		}
		if ($product->fetch($fk_product) <= 0) {
			$error++;
			$result['error'] = 'Producto no encontrado: '.$fk_product;
			break;
		}
		$cost = price2num($product->cost_price);
		if ($cost === '' || $cost === null) {
			$cost = 0;
		}
		$subprice = price2num(round(((float) $cost) * 1.10, 2));
		$tva_tx = (!empty($product->tva_tx) || $product->tva_tx === '0' || $product->tva_tx === 0) ? $product->tva_tx : 16;
		$label = $product->label;
		$desc = !empty($product->description) ? $product->description : $product->label;
		$type = !empty($product->type) ? $product->type : 0;

		$add = $facture->addline(
			$desc,
			$subprice,
			$qty,
			$tva_tx,
			0,
			0,
			$fk_product,
			0,
			'',
			'',
			0,
			0,
			'',
			'HT',
			0,
			$type,
			-1,
			0,
			'shipping',
			$line->id,
			0,
			null,
			$cost,
			$label
		);
		if ($add < 0) {
			$error++;
			$result['error'] = 'Error linea factura DG '.$product->ref.': '.$facture->error;
			break;
		}
	}

	if ($error) {
		$facture->delete($user);
		return $result;
	}

	$prevBillStock = isset($conf->global->STOCK_CALCULATE_ON_BILL) ? $conf->global->STOCK_CALCULATE_ON_BILL : null;
	$conf->global->STOCK_CALCULATE_ON_BILL = 0;
	$valid = $facture->validate($user, '', 0);
	if ($prevBillStock !== null) {
		$conf->global->STOCK_CALCULATE_ON_BILL = $prevBillStock;
	}

	if ($valid < 0) {
		$result['error'] = 'Error al validar factura DG: '.$facture->error;
		$result['facture_id'] = $facture->id;
		$result['ok'] = true;
		return $result;
	}

	$result['ok'] = true;
	$result['facture_id'] = $facture->id;
	$result['facture_ref'] = $facture->ref;
	return $result;
}
