<?php

require '../../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

$idprod = GETPOST('idprod');
$socid = GETPOST('socid');
$desc = GETPOST('desc');
$price = '';

$prod = new Product($db);
$prod->fetch($idprod);

$soc = new Societe($db);
$soc->fetch($socid);

if ($desc != ''){
    $descuento = $desc;
}else{
    $descuento = ($prod->temp_discount != 0) ? $prod->temp_discount : (($prod->desc_max != 0) ? (($prod->desc_max >= 10) ? $soc->remise_percent : $prod->desc_max) : 0);
}

$pu = $prod->price_ttc;
$price = $pu * (1 - ($descuento / 100));

echo json_encode(array(
    'product' => $prod->label,
    'discount' => $descuento,
    'price' => (float)$pu,
    'subtotal' => $price
));