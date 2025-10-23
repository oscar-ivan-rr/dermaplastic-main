<?php

require('../main.inc.php');
require 'conf.php';
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
include_once(DOL_DOCUMENT_ROOT . "/core/class/translate.class.php");
include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php"); 
$interface=new Interfaces($db);
$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

/*
if ( $db->num_rows($db->query("SHOW COLUMNS FROM  ".MAIN_DB_PREFIX."cfdimx LIKE 'divisa' ")) == 1 ) echo "el campo existe<br>" ;
else echo "el campo no existe<br>" ;
*/
if(GETPOST('finicio')){
$fecha1=GETPOST('finicio');
$fecha2=GETPOST('ffin');
/* print $fecha1."<br>";
print $fecha2."<br>"; */
$qry  = " SELECT ";
$qry .= "   ".MAIN_DB_PREFIX."cfdimx.factura_id AS id";
$qry .= ", siren AS rfc"; 
$qry .= ", nom AS nombre, (SELECT value FROM  ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_INFO_SOCIETE_NOM' AND entity = entity_id) sucursal, CONCAT(fecha_timbrado,'T', hora_timbrado) AS fecha";
$qry .= ", factura_serie AS serie";
if($conf->global->MAIN_MODULE_MULTICURRENCY){
	$qry .= ", factura_folio AS folio,multicurrency_total_tva  as impuesto";
	$qry .= ", multicurrency_total_ht AS subtotal";
	$qry .= ", multicurrency_total_ttc AS total";
}else{
	$qry .= ", factura_folio AS folio,tva  as impuesto";
	$qry .= ", total AS subtotal";
	$qry .= ", total_ttc AS total";
}
$qry .= ", CASE WHEN cancelado = 0 THEN 'ACTIVAS' ELSE 'CANCELADA' END AS estado";
$qry .= ", CASE divisa WHEN CHARACTER_LENGTH(divisa) > 0 THEN 'Mexico Pesos' ELSE divisa END AS moneda";
$qry .= ", ' ' AS ocompra";
$qry .= ", lastname AS ugenera";
$qry .= ", uuid ,xml"; 
$qry .= " FROM  ".MAIN_DB_PREFIX."cfdimx"; 
$qry .= " INNER JOIN  ".MAIN_DB_PREFIX."facture ON  ".MAIN_DB_PREFIX."cfdimx.fk_facture =  ".MAIN_DB_PREFIX."facture.rowid";
$qry .= " INNER JOIN  ".MAIN_DB_PREFIX."societe ON  ".MAIN_DB_PREFIX."societe.rowid =  ".MAIN_DB_PREFIX."facture.fk_soc";
$qry .= " INNER JOIN  ".MAIN_DB_PREFIX."user ON  ".MAIN_DB_PREFIX."user.rowid =  ".MAIN_DB_PREFIX."facture.fk_user_author";
$qry .= " WHERE sello NOT LIKE '%pruebas%' AND fecha_timbrado BETWEEN '".$fecha1."'  AND '".$fecha2."'";
}else{
	$qry  = " SELECT ";
	$qry .= "   ".MAIN_DB_PREFIX."cfdimx.factura_id AS id";
	$qry .= ", siren AS rfc";
	$qry .= ", nom AS nombre, (SELECT value FROM  ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_INFO_SOCIETE_NOM' AND entity = entity_id) sucursal, CONCAT(fecha_timbrado,'T', hora_timbrado) AS fecha";
	$qry .= ", factura_serie AS serie";
	if($conf->global->MAIN_MODULE_MULTICURRENCY){
		$qry .= ", factura_folio AS folio,multicurrency_total_tva  as impuesto";
		$qry .= ", multicurrency_total_ht AS subtotal";
		$qry .= ", multicurrency_total_ttc AS total";
	}else{
		$qry .= ", factura_folio AS folio,tva  as impuesto";
		$qry .= ", total AS subtotal";
		$qry .= ", total_ttc AS total";
	}
	$qry .= ", CASE WHEN cancelado = 0 THEN 'ACTIVAS' ELSE 'CANCELADA' END AS estado";
	$qry .= ", CASE divisa WHEN CHARACTER_LENGTH(divisa) > 0 THEN 'Mexico Pesos' ELSE divisa END AS moneda";
	$qry .= ", ' ' AS ocompra";
	$qry .= ", lastname AS ugenera";
	$qry .= ", uuid ,xml";
	$qry .= " FROM  ".MAIN_DB_PREFIX."cfdimx";
	$qry .= " INNER JOIN  ".MAIN_DB_PREFIX."facture ON  ".MAIN_DB_PREFIX."cfdimx.fk_facture =  ".MAIN_DB_PREFIX."facture.rowid";
	$qry .= " INNER JOIN  ".MAIN_DB_PREFIX."societe ON  ".MAIN_DB_PREFIX."societe.rowid =  ".MAIN_DB_PREFIX."facture.fk_soc";
	$qry .= " INNER JOIN  ".MAIN_DB_PREFIX."user ON  ".MAIN_DB_PREFIX."user.rowid =  ".MAIN_DB_PREFIX."facture.fk_user_author";
	$qry .= " WHERE sello NOT LIKE '%pruebas%' ";
}
// si todos los ejercicios estan en pruebas el registro no mosstrara nada sombrear la linea anterior
//print $qry;	

 $res = $db->query($qry);
 $num=$db->num_rows($res);

	//if($res->num_rows > 0 ){
	  if($num > 0 ){
						
		date_default_timezone_set('America/Mexico_City');

		if (PHP_SAPI == 'cli')
			die('Este archivo solo se puede ver desde un navegador web');

		/** Se agrega la libreria PHPExcel */
		require_once 'lib/PHPExcel/PHPExcel.php';

		// Se crea el objeto PHPExcel
		$objPHPExcel = new PHPExcel();

		// Se asignan las propiedades del libro
		$objPHPExcel->getProperties()->setCreator("Codedrinks") //Autor
							 ->setLastModifiedBy("Codedrinks") //Ultimo usuario que lo modificó
							 ->setTitle("Reporte Excel Dolibarr")
							 ->setSubject("Reporte Excel Dolibarr")
							 ->setDescription("Reporte de Global")
							 ->setKeywords("reporte facturacion electronica")
							 ->setCategory("Reporte excel");

		$tituloReporte = "Relacion de facturacion electronica";
		$titulosColumnas = array('#', 'RFC Receptor',	'Nombre Receptor',	'Sucursal',	
				                 'Fecha',	'Serie',	'Folio',	
				                 'Total Impuestos',	'Sub Total', 'Total Factura', 'Estado',	
				                 'Moneda',	'Orden Compra',	'Usuario Genera',	
				                 'UUID',	'XML', 'PDF');
		
		$objPHPExcel->setActiveSheetIndex(0)
        		    ->mergeCells('A1:Q1');
						
		// Se agregan los titulos del reporte
		$objPHPExcel->setActiveSheetIndex(0)
					->setCellValue('A1',$tituloReporte)
					
					->setCellValue('A3',  $titulosColumnas[0])
        		    ->setCellValue('B3',  $titulosColumnas[1])
		            ->setCellValue('C3',  $titulosColumnas[2])
        		    ->setCellValue('D3',  $titulosColumnas[3])
            		->setCellValue('E3',  $titulosColumnas[4]) 
		            ->setCellValue('F3',  $titulosColumnas[5]) 
		            ->setCellValue('G3',  $titulosColumnas[6]) 
		            ->setCellValue('H3',  $titulosColumnas[7]) 
		            ->setCellValue('I3',  $titulosColumnas[8]) 
		            ->setCellValue('J3',  $titulosColumnas[9]) 
		            ->setCellValue('K3',  $titulosColumnas[10]) 
		            ->setCellValue('L3',  $titulosColumnas[11]) 
		            ->setCellValue('M3',  $titulosColumnas[12]) 
		            ->setCellValue('N3',  $titulosColumnas[13]) 
		            ->setCellValue('O3',  $titulosColumnas[14])
				    ->setCellValue('P3',  $titulosColumnas[15])
				    ->setCellValue('Q3',  $titulosColumnas[16]);
		//Se agregan los datos de las facturas
		$i = 4;
		while ($fila = $db->fetch_array($res) ) {
			
			
			
			$objPHPExcel->setActiveSheetIndex(0)
			        ->setCellValue('A'.$i,  $fila['id'])
        		    ->setCellValue('B'.$i,  utf8_encode($fila['rfc']))
		            ->setCellValue('C'.$i,  utf8_encode($fila['nombre']))
        		    ->setCellValue('D'.$i,  trim(utf8_encode($fila['sucursal'])))
            		->setCellValue('E'.$i, utf8_encode($fila['fecha']))
            		->setCellValue('F'.$i, utf8_encode($fila['serie']))
            		->setCellValue('G'.$i, utf8_encode($fila['folio']))
            		->setCellValue('H'.$i, utf8_encode($fila['impuesto']))
            		->setCellValue('I'.$i, utf8_encode($fila['subtotal']))
            		->setCellValue('J'.$i, utf8_encode($fila['total']))
            		->setCellValue('K'.$i, utf8_encode($fila['estado']))
            		->setCellValue('L'.$i, utf8_encode($fila['moneda']))
            		->setCellValue('M'.$i, utf8_encode($fila['ocompra']))
            		->setCellValue('N'.$i, utf8_encode($fila['ugenera']))
            		->setCellValue('O'.$i, utf8_encode($fila['uuid']))
			        ->setCellValue('P'.$i, utf8_encode($fila['xml']))
			        ->setCellValue('Q'.$i, '    ');
			
					$i++;
		}
		
		$estiloTituloReporte = array(
        	'font' => array(
	        	'name'      => 'Verdana',
    	        'bold'      => true,
        	    'italic'    => false,
                'strike'    => false,
               	'size' =>16,
	            	'color'     => array(
    	            	'rgb' => 'FFFFFF'
        	       	)
            ),
	        'fill' => array(
				'type'	=> PHPExcel_Style_Fill::FILL_SOLID,
				'color'	=> array('argb' => 'FF220835')
			),
            'borders' => array(
               	'allborders' => array(
                	'style' => PHPExcel_Style_Border::BORDER_NONE                    
               	)
            ), 
            'alignment' =>  array(
        			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        			'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        			'rotation'   => 0,
        			'wrap'          => TRUE
    		)
        );

		$estiloTituloColumnas = array(
            'font' => array(
                'name'      => 'Arial',
                'bold'      => true,                          
                'color'     => array(
                    'rgb' => 'FFFFFF'
                )
            ),
            'fill' 	=> array(
				'type'		=> PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
				'rotation'   => 90,
        		'startcolor' => array(
            		//'rgb' => 'c47cf2'
        		    'rgb' => '6D9BC8'//Azulmarino claro en titulo columnas
        		),
        		'endcolor'   => array(
            		'argb' => 'FF431a5d' //titulo
        		)
			),
            'borders' => array(
            	'top'     => array(
                    'style' => PHPExcel_Style_Border::BORDER_MEDIUM ,
                    'color' => array(
                        'rgb' => '143860'
                    )
                ),
                'bottom'     => array(
                    'style' => PHPExcel_Style_Border::BORDER_MEDIUM ,
                    'color' => array(
                        'rgb' => '143860'
                    )
                )
            ),
			'alignment' =>  array(
        			'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        			'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        			'wrap'          => TRUE
    		));
			
		$estiloInformacion = new PHPExcel_Style();
		$estiloInformacion->applyFromArray(
			array(
           		'font' => array(
               	'name'      => 'Arial',               
               	'color'     => array(
                   	'rgb' => '000000'
               	)
           	),
           	'fill' 	=> array(
				'type'		=> PHPExcel_Style_Fill::FILL_SOLID,
				//'color'		=> array('argb' => 'FFd9b7f4')
           		'color'		=> array('argb' => 'A4CDF5')// llena campos info
			),
           	'borders' => array(
               	'left'     => array(
                   	'style' => PHPExcel_Style_Border::BORDER_THIN ,
	                'color' => array(
    	            	'rgb' => '3a2a47'
                   	)
               	)             
           	)
        ));
		 
		$objPHPExcel->getActiveSheet()->getStyle('A1:Q1')->applyFromArray($estiloTituloReporte);
		$objPHPExcel->getActiveSheet()->getStyle('A3:Q3')->applyFromArray($estiloTituloColumnas);		
		$objPHPExcel->getActiveSheet()->setSharedStyle($estiloInformacion, "A4:Q".($i-1));
				
		for($i = 'A'; $i <= 'Q'; $i++){
			if($i != 'P'){
			$objPHPExcel->setActiveSheetIndex(0)			
				->getColumnDimension($i)->setAutoSize(TRUE);
			}
		}
		
		// Se asigna el nombre a la hoja
		$format = 'Y-m-d';
		$objPHPExcel->getActiveSheet()->setTitle('REPO-'.date($format));

		// Se activa la hoja para que sea la que se muestre cuando el archivo se abre
		$objPHPExcel->setActiveSheetIndex(0);
		// Inmovilizar paneles 
		//$objPHPExcel->getActiveSheet(0)->freezePane('A4');
		// Freeze panel
		$objPHPExcel->getActiveSheet(0)->freezePaneByColumnAndRow(0,4);

		// Se manda el archivo al navegador web, con el nombre que se indica (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Reportedefacturacion.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		//$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2003');
		$objWriter->save('php://output');
		exit;
		
	}
	else{
		//print "<script>windo</script>";
		print "<script>window.location='reporte.php?mesag=no';</script>";
		print_r('No hay resultados para mostrar');
	}
?>