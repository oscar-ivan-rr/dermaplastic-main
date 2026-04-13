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
    $result = curl_exec($ch);
    curl_close($ch);
    return 0;
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
    $result = curl_exec($ch);
    //$httpcode = curl_getinfo($result, CURLINFO_HTTP_CODE);
    //var_dump($httpcode);
    curl_close($ch);
    return 0;
}
