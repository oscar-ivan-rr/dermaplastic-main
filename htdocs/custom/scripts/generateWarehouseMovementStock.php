<?php
require_once('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
ini_set('max_execution_time', 0);
/** Getting all products to synchronize */
$query_prods = "SELECT rowid, ref FROM `".MAIN_DB_PREFIX."product` ORDER BY rowid ASC";
// $query_prods = "SELECT rowid, ref FROM `".MAIN_DB_PREFIX."product` where rowid = 852";
$result_prods = $db->query($query_prods);
$num1 = $db->num_rows($result_prods);
/** Generating route to the log file */
$logfile = DOL_DATA_ROOT.'/stock_sincronizado_'.date("Y-m-d").'.log';
$j = 0;
while($j < $num1){  // Loop for each product
    $product = $db->fetch_object($result_prods);
    if($product->rowid > 1){    // If has a valid id
        /**
         * We look for every stock movement related to the product. Starting from the latest to the oldest.
         * This way we make sure that at the end the actual stock is the same as the latest from the movements
         */
        $query = "SELECT rowid, value, current_stock_1, current_stock_2, fk_entrepot FROM `".MAIN_DB_PREFIX."stock_mouvement` WHERE `fk_product` = $product->rowid ORDER BY rowid DESC";
        $result = $db->query($query);
        $num = $db->num_rows($result) - 1;
        $prod = new Product($db);
        $prod->fetch($product->rowid);
        $prod->load_stock(); // Loading current stocks to start from here
        
        $current_stock_1 = ($prod->stock_warehouse[1]->real)? $prod->stock_warehouse[1]->real : 0;
        $current_stock_2 = ($prod->stock_warehouse[2]->real)? $prod->stock_warehouse[2]->real : 0;
        /** Logging variables */
        $log_stock_1 = $current_stock_1;
        $log_stock_2 = $current_stock_2;
        $latest_row = 0;
        while($num >= 0){   // Loop for every movement of the product
            $object = $db->fetch_object($result);
            if($latest_row == 0) $latest_row = $object->rowid;
            $db->query("UPDATE `".MAIN_DB_PREFIX."stock_mouvement` SET current_stock_1=$current_stock_1, current_stock_2=$current_stock_2 WHERE rowid=$object->rowid");
            /** We calculate the previous stock substracting the value in the current movement */
            if($object->fk_entrepot == '1')
                $current_stock_1 -= $object->value;
            else
                $current_stock_2 -= $object->value;
            $num--;
        }
        /**
         * After every product, we save the state of the stock and the latest movement id at the moment of the 
         * synchronization for further references (so we know that from this and before the stock should be correct)
         */
        file_put_contents($logfile, print_r([
            'rowid' => $product->rowid,
            'ref' => $product->ref,
            'stock_matriz' => $log_stock_1,
            'stock_zacatecas' => $log_stock_2,
            'rowid_stock_mouvement_latest' => $latest_row
        ], true), FILE_APPEND);
    }  
    $j++;  
}
echo "OK";
?>
