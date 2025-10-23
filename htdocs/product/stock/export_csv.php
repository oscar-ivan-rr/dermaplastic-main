<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';

if (!empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->loadLangs(array('main', 'companies', 'users', 'trips', 'stocks'));


$sql = GETPOST("sql");
$limit = GETPOST("limit");

$resql = $db->query($sql);

$objPHPExcel = new PHPExcel(); 
$objPHPExcel->setActiveSheetIndex(0); 
//Cabeceras de Excel
$objPHPExcel->getActiveSheet()->SetCellValue('A1', "Referencia.");
$objPHPExcel->getActiveSheet()->SetCellValue('B1', "Fecha");
$objPHPExcel->getActiveSheet()->SetCellValue('C1', "Producto");
$objPHPExcel->getActiveSheet()->SetCellValue('D1', "Almacén");
$objPHPExcel->getActiveSheet()->SetCellValue('E1', "Autor");
$objPHPExcel->getActiveSheet()->SetCellValue('F1', "Etiqueta de Movimiento");
$objPHPExcel->getActiveSheet()->SetCellValue('G1', "Tipo");
$objPHPExcel->getActiveSheet()->SetCellValue('H1', "Cantidad");
$objPHPExcel->getActiveSheet()->SetCellValue('I1', "Precio de Compra");
$objPHPExcel->getActiveSheet()->SetCellValue('J1', "Subtotal");
$objPHPExcel->getActiveSheet()->SetCellValue('K1', "IVA");
$objPHPExcel->getActiveSheet()->SetCellValue('L1', "Total");

$rowCount = 2;

$filename = 'Movimientos.xlsx';
$subtotal = 0;
$tva = 0;
$total = 0;

$i = 0;
$num = $db->num_rows($resql);


//Información en Excel
while($i < min($num, $limit)){
    $row = $db->fetch_object($resql);

    $productlot = new ProductLot($db);
    $productstatic = new Product($db);
    $warehousestatic = new Entrepot($db);
    $movement = new MouvementStock($db);
    $userstatic = new User($db);
    $form = new Form($db);
    $formother = new FormOther($db);
    $formproduct = new FormProduct($db);

    // Warehouse
    $warehousestatic->id = $row->entrepot_id;
    $warehousestatic->ref = $row->warehouse_ref;
    $warehousestatic->libelle = $row->warehouse_ref; // deprecated
    $warehousestatic->label = $row->warehouse_ref;
    $warehousestatic->lieu = $row->lieu;
    $warehousestatic->fk_parent = $row->fk_parent;
    $warehousestatic->statut = $row->statut;

    // Author
    $userstatic->id = $row->fk_user_author;
    $userstatic->login = $row->login;
    $userstatic->lastname = $row->lastname;
    $userstatic->firstname = $row->firstname;
    $userstatic->photo = $row->photo;

    $productstatic->id = $row->rowid;
    $productstatic->ref = $row->product_ref;
    $productstatic->label = $row->produit;
    $productstatic->type = $row->type;
    $productstatic->entity = $row->entity;
    $productstatic->status = $row->tosell;
    $productstatic->status_buy = $row->tobuy;
    $productstatic->status_batch = $row->tobatch;

    $productlot->id = $row->lotid;
    $productlot->batch = $row->batch;
    $productlot->eatby = $row->eatby;
    $productlot->sellby = $row->sellby;

    // Type of movement
    $type = '';  
    switch ($row->type_mouvement) {
        case "0":
            $type = $langs->trans('StockIncreaseAfterCorrectTransfer');
            break;
        case "1":
            $type = $langs->trans('StockDecreaseAfterCorrectTransfer');
            break;
        case "2":
            $type = $langs->trans('StockDecrease');
            break;
        case "3":
            $type = $langs->trans('StockIncrease');
            break;
    }


    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row->mid);
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, dol_print_date($db->jdate($row->datem), 'dayhour', 'tzuserrel'));
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $row->product_ref);
    $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, strip_tags($warehousestatic->getNomUrl(0)));
    $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, strip_tags($userstatic->getNomUrl(-1)));
    $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $row->label);
    $objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $type);
    $objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $row->qty);
    $objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, price($row->price));
    $objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, price($row->subtotal));
    $objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, price($row->tva));
    $objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, price($row->total));

    $subtotal += $row->subtotal;
    $tva += $row->tva;
    $total += $row->total;


    $rowCount++; 
    $i++; 
}

$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, "Total");
$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, price($subtotal));
$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, price($tva));
$objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, price($total));

//Guardar archivo Excel
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename='.$filename);
$objWriter->save('php://output');
exit;
