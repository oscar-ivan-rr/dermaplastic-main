<?php
require '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

global $conf;

//Consulta dependiendo la opcion seleccionada
if($type == 1){
    //Precio de compra sea mayor al precio de venta
    $sql = "SELECT ref, cost_price, price FROM ".MAIN_DB_PREFIX."product WHERE cost_price > price";
    $filename = 'PCmayorPV.xlsx';
}elseif($type == 2){
    //Precio de compra = 0 con stock mayor a 1
    $sql = "SELECT ref, cost_price, price, stock FROM ".MAIN_DB_PREFIX."product WHERE cost_price = 0 AND stock > 1";
    $filename = 'PC0STmayor1.xlsx';
}elseif($type == 3){
    //Precio de venta = 0
    $sql = "SELECT ref, cost_price, price FROM ".MAIN_DB_PREFIX."product WHERE price = 0";
    $filename = 'PV0.xlsx';
}elseif($type == 4){
    //Precio de compra menor a $0.10
    $sql = "
        SELECT 
            p.ref,
            ff.datec,
            ffd.multicurrency_subprice,
            ffd.remise_percent,
            ffd.qty,
            p.cost_price
        FROM
            ".MAIN_DB_PREFIX."facture_fourn_det AS ffd
        JOIN
            ".MAIN_DB_PREFIX."facture_fourn AS ff
        JOIN
            ".MAIN_DB_PREFIX."product AS p 
        ON 
            ff.rowid = ffd.fk_facture_fourn
        AND p.rowid = ffd.fk_product
        WHERE 
            ffd.multicurrency_subprice <= .10 and ffd.multicurrency_subprice >= .0
    ";
    if($date_start_creationyear && !$date_end_creationyear)
        $sql.=" AND ff.datec >= '".$date_start_creationyear."-".$date_start_creationmonth."-".$date_start_creationday."'";
    else if (!$date_start_creationyear && $date_end_creationyear)
        $sql.=" AND ff.datec <= '".$date_end_creationyear."-".$date_end_creationmonth."-".($date_end_creationday+1)."'";
    else if ($date_start_creationyear && $date_end_creationyear)
        $sql.=" AND ff.datec BETWEEN '".$date_start_creationyear."-".$date_start_creationmonth."-".$date_start_creationday."' AND '".$date_end_creationyear."-".$date_end_creationmonth."-".($date_end_creationday+1)."'";
    
        $filename = 'PC_Prod_Regalados.xlsx';
}

$result = $db->query($sql);
$objPHPExcel = new PHPExcel(); 
$objPHPExcel->setActiveSheetIndex(0); 
//Cabeceras de Excel
if($type >= 1 && $type <= 3){
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', "Ref");
    $objPHPExcel->getActiveSheet()->SetCellValue('B1', "PrecioCompra");
    $objPHPExcel->getActiveSheet()->SetCellValue('C1', "PrecioVenta");
}
if($type == 2){
    $objPHPExcel->getActiveSheet()->SetCellValue('D1', "Stock");
}
if($type == 4){
    $objPHPExcel->getActiveSheet()->SetCellValue('A1', "Ref del Producto");
    $objPHPExcel->getActiveSheet()->SetCellValue('B1', "Fecha de Compra");
    $objPHPExcel->getActiveSheet()->SetCellValue('C1', "Precio de Compra");
    $objPHPExcel->getActiveSheet()->SetCellValue('D1', "Cantidad");
    $objPHPExcel->getActiveSheet()->SetCellValue('E1', "Ultimo precio de Compra sin IVA");
    $objPHPExcel->getActiveSheet()->SetCellValue('F1', "Valor de Inventario");
}
$rowCount = 2;
//Información en Excel
while($row = $db->fetch_object($result)){
    if($type >= 1 && $type <= 3){
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row->ref);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row->cost_price);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $row->price);
    }
    if($type == 2){
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $row->stock);
    }
    if($type == 4){
        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row->ref);
        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row->datec);
        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, price($row->multicurrency_subprice * (100 - $row->remise_percent) / 100, 0, '', -1, -1, -1, 'MXN'));
        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $row->qty);
        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, price($row->cost_price, 0, '', 1, -1, -1, 'MXN'));
        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, price($row->qty * $row->cost_price, 0, '', 1, -1, -1, 'MXN'));
    }
    $rowCount++; 
}

//Guardar archivo Excel
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename='.$filename);
$objWriter->save('php://output');
exit;
?>