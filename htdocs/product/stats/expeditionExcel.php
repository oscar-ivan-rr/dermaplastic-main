<?php
    require '../../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
    require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
    $objPHPExcel = new PHPExcel();
    date_default_timezone_set("America/Mexico_City");

    $productid=GETPOST('productid');
    $search_month=GETPOST('search_month');
    $search_year=GETPOST('search_year');
    $socid=GETPOST('socid');
    $product=GETPOST('product');

    $expedition = new Expedition($db);
    $societestatic=new Societe($db);

    //styles
    $style_header1 = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        ),
        'font'  => array(
            'bold'  => true,
            'size'  => 10,
            'name'  => 'Consolas'
        )
    );
    $style_header = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        ),
        'font'  => array(
            'bold'  => true,
            'size'  => 10,
            'name'  => 'Consolas'
        )
    );
    $style_body = array(
        'font'  => array(
            'bold'  => false,
            'size'  => 11,
            'name'  => 'Consolas'
        )
    );

    $sheet = $objPHPExcel->getActiveSheet();
    //$sheet->getDefaultStyle()->getAlignment()->setWrapText(true);
    
    // Excel information
    $objPHPExcel->
    getProperties()
        ->setCreator("CEZAC")
        ->setLastModifiedBy("CEZAC")
        ->setTitle("Envios por Producto CEZAC")
        ->setSubject("Envios")
        ->setDescription("Reporte de Envios por Producto")
        ->setKeywords("CEZAC")
        ->setCategory("envios");
    
    //sheet header
    $sheet->getStyle('A1:K4')->applyFromArray($style_header1);
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'REPORTE DE ENVIOS POR PRODUCTO: '.$product.'');
    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:C1');
    if($search_month && $search_year){  
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: '.$search_month.'/'.$search_year);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('D1:F1');
    }
    elseif($search_month && !$search_year){
         
        switch ($search_month) {
            case 1:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Enero');
                break;
            case 2:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Febrero');
                break;
            case 3:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Marzo');
                break;
            case 4:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Abril');
                break;
            case 5:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Mayo');
                break;
            case 6:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Junio');
                break;
            case 7:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Julio');
                break;
            case 8:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Agosto');
                break;
            case 9:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Septiembre');
                break;
            case 10:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Octubre');
                break;
            case 11:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Noviembre');
                break;
            case 12:
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: Diciembre');
                break;
        }
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('D1:F1');
    }
    elseif(!$search_month && $search_year){  
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'FECHA: '.$search_year);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('D1:F1');
    }
    $i=5;
    $i1=$i+1;
    
    // table Header
    $sheet->getStyle("A".$i.":I".$i)->applyFromArray($style_header);
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, 'Ref');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, 'Empresa');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, 'Codigo Cliente');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, 'Fecha Pedido');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, 'Cantidad');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, 'Subtotal');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, 'Estado');
    
    $i++;
    $row_start = $i;

    $sql = "SELECT DISTINCT s.nom as name, s.rowid as socid, s.code_client, exp.rowid,";
    $sql.= " (expdet.qty*commdet.price) as total_ht, exp.ref, exp.ref_customer as ref_client,";
    $sql.= " exp.date_creation, exp.fk_statut as statut, exp.rowid as commandeid, commdet.rowid, commdet.qty";

    $sql .= " FROM ".MAIN_DB_PREFIX."expedition as exp";
    $sql .= ", ".MAIN_DB_PREFIX."expeditiondet as expdet";
    $sql .= ", ".MAIN_DB_PREFIX."commandedet as commdet";
    $sql .= ", ".MAIN_DB_PREFIX."societe as s";

    $sql.= " WHERE exp.rowid = expdet.fk_expedition";
    $sql.= " AND exp.fk_soc = s.rowid AND exp.entity IN (".getEntity('expedition').")";
    $sql .= " AND expdet.fk_origin_line = commdet.rowid AND commdet.fk_product = ".$productid;

        if (! empty($search_month))
            $sql.= ' AND MONTH(exp.date_creation) IN (' . $search_month . ')';
        if (! empty($search_year))
            $sql.= ' AND YEAR(exp.date_creation) IN (' . $search_year . ')';

    if ($socid) $sql.= " AND exp.fk_soc = ".$socid;

    $result = $db->query($sql);
    if ($result)
    {
        while ($objp = $db->fetch_object($result))
        {
            $expedition->id=$objp->commandeid;
            $expedition->ref=$objp->ref;
            $expedition->ref_customer=$objp->ref_client;
            $societestatic->fetch($objp->socid);

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, $objp->ref);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $societestatic->name);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, $objp->code_client);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, $objp->date_creation);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $objp->qty);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, $objp->total_ht);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, $expedition->LibStatut($objp->statut, 1));
            $i++;
        }
    }

    $row_end = $i;
    //apply styles to table body
    $objPHPExcel->getActiveSheet()->getStyle('G'.$row_start.':I'.$row_end)->getNumberFormat()->setFormatCode("#,##0.00");
    $sheet->getStyle('A'.$row_start.':I'.$row_end)->applyFromArray($style_body);
    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
    $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
    $i++;

    //table footer
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, 'Total');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.($i + 1), '=SUM(E'.$row_start.':E'.$row_end.')');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, 'Total');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.($i + 1), '=SUM(F'.$row_start.':F'.$row_end.')');
    $objPHPExcel->getActiveSheet()->getStyle('E'.($i+1).':F'.($i+1))->getNumberFormat()->setFormatCode("#,##0.00");
    $sheet->getStyle('E'.($i+1).':F'.($i+1))->applyFromArray($style_body);

    //file name
    $filename = "EnviosProducto".$product."";

    header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=".$filename.".xlsx"); 
    header('Cache-Control: max-age=0');
     
    $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
    $objWriter->save('php://output');
    exit;
?>