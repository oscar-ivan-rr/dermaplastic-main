<?php
    require '../../main.inc.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
    $objPHPExcel = new PHPExcel();
    date_default_timezone_set("America/Mexico_City");

    $productid=GETPOST('productid');
    $search_month=GETPOST('search_month');
    $search_year=GETPOST('search_year');
    $socid=GETPOST('socid');
    $product=GETPOST('product');

    $invoicestatic=new Facture($db);
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
        ->setTitle("Facturas a Clientes por Producto CEZAC")
        ->setSubject("Facturas a Clientes")
        ->setDescription("Reporte de Facturas a Clientes por Producto")
        ->setKeywords("CEZAC")
        ->setCategory("facturas");
    
    //sheet header
    $sheet->getStyle('A1:K4')->applyFromArray($style_header1);
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'REPORTE DE FACTURAS A CLIENTES POR PRODUCTO: '.$product.'');
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
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, 'Fecha Facturacion');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, 'Cantidad');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, 'Subtotal');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, 'Estado');
    
    $i++;
    $row_start = $i;

    $sql = "SELECT DISTINCT s.nom as name, s.rowid as socid, s.code_client,";
            $sql.= " f.ref, f.datef, f.paye, f.type, f.fk_statut as statut, f.rowid as facid,";
            $sql.= " d.rowid, d.total_ht as total_ht, d.qty";           // We must keep the d.rowid here to not loose record because of the distinct used to ignore duplicate line when link on societe_commerciaux is used
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
            $sql.= ", ".MAIN_DB_PREFIX."facture as f";
            $sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
            if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE f.fk_soc = s.rowid";
            $sql.= " AND f.entity IN (".getEntity('invoice').")";
            $sql.= " AND d.fk_facture = f.rowid";
            $sql.= " AND d.fk_product = ".$productid;
            if (! empty($search_month))
            	$sql.= ' AND MONTH(f.datef) IN (' . $search_month . ')';
            if (! empty($search_year))
            	$sql.= ' AND YEAR(f.datef) IN (' . $search_year . ')';
            if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($socid) $sql.= " AND f.fk_soc = ".$socid;

    $result = $db->query($sql);
    if ($result)
    {
        while ($objp = $db->fetch_object($result))
        {
            $invoicestatic->id=$objp->facid;
            $invoicestatic->ref=$objp->ref;
            $societestatic->fetch($objp->socid);
            $paiement = $invoicestatic->getSommePaiement();

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, $objp->ref);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $societestatic->name);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, $objp->code_client);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, $objp->datef);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $objp->qty);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$i, $objp->total_ht);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G'.$i, $invoicestatic->LibStatut($objp->paye, $objp->statut, 1, $paiement, $objp->type));
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
    $filename = "FacturasClientesProducto".$product."";

    header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=".$filename.".xlsx"); 
    header('Cache-Control: max-age=0');
     
    $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
    $objWriter->save('php://output');
    exit;
?>