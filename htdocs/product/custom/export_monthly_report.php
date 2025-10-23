<?php
/**
 *  \file       htdocs/product/custom/export_warehouse_report.php
 *  \ingroup    product
 *  \brief      monthly report
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';

$sqlfees = GETPOST('sqlfees', 'alpha');
$sqlsales = GETPOST('sqlsales', 'alpha');
$sqlcost = GETPOST('sqlcost', 'alpha');
$sqlcommande = GETPOST('sqlcommande', 'alpha');
$sqlmovement = GETPOST('sqlmovement', 'alpha');

$dateinicio  = GETPOST('dateinicio');
$datefinal   = GETPOST('datefinal');
$name_warehouse= GETPOST('nom', 'alpha');
$rfc_label= GETPOST('rfc_label', 'alpha');
$date = "";
if (!empty($dateinicio) && !empty($datefinal)) $date = dol_print_date($dateinicio, 'day')." - ".dol_print_date($datefinal, 'day');
elseif (!empty($dateinicio)) $date = dol_print_date($dateinicio, 'day')." - ".dol_print_date(dol_now('tzuser'), 'day');
elseif (!empty($datefinal)) $date = "Hasta ".dol_print_date($datefinal, 'day');
elseif (empty($dateinicio) && empty($datefinal)) $date = "Hasta ".dol_print_date(dol_now('tzuser'), 'day');

// Calculate the difference of days
if(!empty($dateinicio)){
  if(!empty($dateinicio)) 	$fechaFin = new DateTime($datefinal);
	else 	$fechaFin = new DateTime(date('Y-m-d', dol_now()));

	$fechaInicio = new DateTime($dateinicio);
	$interval = $fechaInicio->diff($fechaFin);
	$days = $interval->days + 1;
} else {
	$days = 0;
}

$resqlfees = $db->query($sqlfees);
$resqlsales = $db->query($sqlsales);
$resqlcost = $db->query($sqlcost);
$resqlcommande = $db->query($sqlcommande);
$resqlmovement = $db->query($sqlmovement);

if (!$resqlfees || !$resqlsales || !$resqlcost || !$resqlcommande || !$resqlmovement) {
	dol_print_error($db);
	exit;
} else {
	$sales = $db->fetch_object($resqlsales);
	$cost = $db->fetch_object($resqlcost);
	$commande = $db->fetch_object($resqlcommande);
	$movement = $db->fetch_object($resqlmovement);

  $filename = "Reporte_mensual_".$name_warehouse.".xlsx";

  try {
      // Clean the Buffer (Note: If it is not cleaned, the downloaded file may not be able to be opened)
      ob_clean();

      // Create a PHPExcel instance
      $objPHPExcel = new PHPExcel();
      // Create an active worksheet
      $objPHPExcel->setActiveSheetIndex(0);

      $sheet = $objPHPExcel->getActiveSheet()->setTitle($name_warehouse);

      // Styles
      $sheet->getStyle("B")->getFont()->setSize(10)->setName('Arial');
      $sheet->getStyle("C")->getFont()->setSize(10)->setName('Arial');
      $sheet->getColumnDimension('A')->setWidth(20);
      $sheet->getColumnDimension('B')->setWidth(40);
      $sheet->getColumnDimension('C')->setWidth(25);

      // Write data to cells
      $sheet->setCellValue('C6', $date);
      $sheet->getStyle("C6")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

      $line = 7;
      /**--------------------------- Fees --------------------------- */
      while ($row = $db->fetch_object($resqlfees)){

          if($row->label == 'Vacaciones') $line ++;
          $sheet->setCellValue('B'.$line, $row->label);
          $sheet->setCellValue('C'.$line, ($row->total > 0 ? $row->total : ''));

          $sheet->getStyle('C'.$line)->getNumberFormat()->setFormatCode('###,###.00');

          $line++;
      }
      $sum_gastos = '=SUM(C7:C'.($line-1).')';
      $line += 2;
      $c1 = $line;
      $sheet->setCellValue('B'.$line, 'Suma');
      $sheet->setCellValue('C'.$line, $sum_gastos);
      $sheet->getStyle('C'.$line)->getFont()->setSize(11)->setBold(true)->getColor()->setARGB('FF0000');
      $sum_gastos2 = '=C'.$line;

      /**--------------------------- Sales and stock movements --------------------------- */
      $line +=2;
      // Calculations with Excel formula format
      $monthly_income = '=C'.$line.'+C'.($line+1); // $sales->total_ht + $sales->total_tva
      $gross_profit = '=C'.($line+2).'-C'.($line+3); // $monthly_income - $sales->cost_price
      $sale_per_day = '=C'.($line+2).'/'.$days; // $monthly_income / $days
      $cost_per_day = '=C'.($line+3).'/'.$days; // $sales->cost_price / $days
      $cost_avg = '=C'.($line+3).'/C'.($line+19); // $sales->cost_price / $sales->qty
      $net_profit = '=C'.($line+4).'-'.str_replace('=', '', $sum_gastos2); // $gross_profit - $total
      $inventory_cost = $cost->gravado + $cost->no_gravado + $cost->iva;
      $percentage = '=C'.($line+14).'/C'.($line+2).'/100'; // $net_profit / $monthly_income
      $ticket_avg = '=C'.($line+2).'/'.$sales->num_factures; // $monthly_income / $sales->num_factures

      $sheet->setCellValue('B'.($line), 'Ingresos sin iva');
      $sheet->setCellValue('C'.($line), $sales->total_ht);
      $sheet->setCellValue('B'.($line + 1), 'IVA');
      $sheet->setCellValue('C'.($line + 1), $sales->total_tva);
      $sheet->setCellValue('B'.($line + 2), 'Ingresos mensuales');
      $sheet->setCellValue('C'.($line + 2), $monthly_income);
      $sheet->getStyle('C'.($line + 2))->getFont()->setBold(true)->getColor()->setARGB('FF0000');
      $sheet->setCellValue('B'.($line + 3), 'Costo');
      $sheet->setCellValue('C'.($line + 3), $sales->cost_price);
      $sheet->setCellValue('B'.($line + 4), 'Utilidad bruta');
      $sheet->setCellValue('C'.($line + 4), $gross_profit);

      $line +=6;
      $profit = '=C'.($line).'-C'.($line+1); // $sale_per_day - $cost_per_day
      $prod_cost_avg = '=C'.($line).'/C'.($line+4); // $sale_per_day / $cost_avg
      $sheet->setCellValue('B'.($line), 'Venta por día');
      if($days > 0) $sheet->setCellValue('C'.($line), $sale_per_day);
      $sheet->setCellValue('B'.($line + 1), 'Costo por día');
      if($days > 0) $sheet->setCellValue('C'.($line + 1), $cost_per_day);
      $sheet->setCellValue('B'.($line + 2), 'Utilidad');
      if($days > 0) $sheet->setCellValue('C'.($line + 2), $profit);

      $line +=4;
      $sheet->setCellValue('B'.($line), 'Costo de productos promedio');
      $sheet->setCellValue('C'.($line), $cost_avg);
      $sheet->setCellValue('B'.($line + 1), 'Productos por día con un costo promedio');
      if($days > 0) $sheet->setCellValue('C'.($line + 1), $prod_cost_avg);
      $sheet->getStyle('C'.($line + 1))->getFont()->setSize(14)->setBold(true);
      // Apply border styles to the cell
      $style = array(
        'borders' => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_MEDIUM
            ),
        ),
      );
      $sheet->getStyle('C'.($line + 1))->applyFromArray($style);

      $line +=3;
      $sheet->setCellValue('B'.($line), 'Gastos fijos');
      $sheet->setCellValue('C'.($line), $sum_gastos2);
      $sheet->setCellValue('B'.($line + 1), 'Utilidad neta');
      $sheet->setCellValue('C'.($line + 1), $net_profit);
      $sheet->getStyle('C'.($line + 1))->getFont()->setSize(12)->setBold(true);
      $c2 = $line+1;
      $sheet->getStyle('C'.$c1.':C'.$c2)->getNumberFormat()->setFormatCode('###,###.00');

      $sheet->getStyle('C'.($line+2))->getNumberFormat()->setFormatCode($format);
      $sheet->setCellValue('C'.($line + 2), $percentage);
      if ($percentage > 0) {
        $format = '#.00%'; 
      } else {
          $format = '0.00%';
      }
      $sheet->getStyle('C'.($line + 2))->getNumberFormat()->setFormatCode($format);
      $sheet->getStyle('C'.($line + 2))->getFont()->setSize(12)->setBold(true)->setName('Bookman Old Style')->getColor()->setARGB('993300');

      $line +=4;
      $sheet->setCellValue('B'.($line), 'Costo del inventario');
      $sheet->setCellValue('C'.($line), $inventory_cost);
      $sheet->getStyle('C'.$line)->getNumberFormat()->setFormatCode('###,###.00');

      $line +=2;
      $sheet->setCellValue('B'.($line), 'Productos vendidos por mes');
      $sheet->setCellValue('C'.($line), $sales->qty);

      $line +=2;
      $total_commande = '=C'.$line.'+C'.($line+1); // $commande->total_ht + $commande->total_tva
      $sheet->setCellValue('B'.($line), 'Compras sin iva');
      $sheet->setCellValue('C'.($line), $commande->total_ht);
      $sheet->setCellValue('B'.($line + 1), 'IVA');
      $sheet->setCellValue('C'.($line + 1), $commande->total_tva);
      $sheet->setCellValue('B'.($line + 2), 'Total de compras');
      $sheet->setCellValue('C'.($line + 2), $total_commande);
      $sheet->getStyle('C'.$line.':C'.($line+2))->getNumberFormat()->setFormatCode('###,###.00');

      $line +=4;
      $sheet->setCellValue('B'.($line), 'Tickets por mes');
      $sheet->setCellValue('C'.($line), $sales->num_factures);

      $line +=2;
      $sheet->setCellValue('B'.($line), 'Promedio por ticket');
      $sheet->setCellValue('C'.($line), $ticket_avg);
      $sheet->getStyle('C'.$line)->getNumberFormat()->setFormatCode('###,###.00');

      $line +=2;
      $sheet->setCellValue('B'.($line), 'Faltantes y mermas');
      $sheet->setCellValue('C'.($line), $movement->total);
      $sheet->getStyle('C'.$line)->getNumberFormat()->setFormatCode('###,###.00');

      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="'.$filename.'"');
      header('Cache-Control: max-age=0');

      // Create an Excel writer (Excel 2007)
      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

      // Output Excel file to browser
      $objWriter->save('php://output');
  } catch (\Throwable $th) {
    print_r($th);
  }
}



