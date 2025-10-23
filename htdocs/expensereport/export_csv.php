<?php
require '../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

global $conf;

$langs->loadLangs(array('main', 'companies', 'users', 'trips'));

//Precio de compra sea mayor al precio de venta
$sql = "SELECT d.rowid, d.ref, d.referencia, d.fk_user_author, d.total_ht, d.total_tva, d.total_ttc, d.fk_statut as status, d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve, d.note_private, d.note_public, u.rowid as id_user, u.firstname, u.lastname, u.login, u.email, u.statut, u.photo, d.fk_projet, pr.ref as project_ref, pr.title as project_label, tf.label as tipo FROM llx_expensereport as d LEFT JOIN llx_projet as pr ON pr.rowid = d.fk_projet LEFT JOIN llx_c_type_fees as tf ON tf.id = d.fk_c_type_fees, llx_user as u WHERE d.fk_user_author = u.rowid AND d.entity IN (1)";
$filename = 'PCmayorPV.xlsx';


$result = $db->query($sql);
$objPHPExcel = new PHPExcel(); 
$objPHPExcel->setActiveSheetIndex(0); 

$projectstatic = new Project($db);
$usertmp = new User($db);
$expensereportstatic = new ExpenseReport($db);

//Cabeceras de Excel
$objPHPExcel->getActiveSheet()->SetCellValue('A1', "Ref.");
$objPHPExcel->getActiveSheet()->SetCellValue('B1', "Referencia");
$objPHPExcel->getActiveSheet()->SetCellValue('C1', "Proyecto");
$objPHPExcel->getActiveSheet()->SetCellValue('D1', "Tipo");
$objPHPExcel->getActiveSheet()->SetCellValue('E1', "Usuario");
$objPHPExcel->getActiveSheet()->SetCellValue('F1', "Inicio");
$objPHPExcel->getActiveSheet()->SetCellValue('G1', "Finalizacion");
$objPHPExcel->getActiveSheet()->SetCellValue('H1', "Validación");
$objPHPExcel->getActiveSheet()->SetCellValue('I1', "Aprobación");
$objPHPExcel->getActiveSheet()->SetCellValue('J1', "Subtotal");
$objPHPExcel->getActiveSheet()->SetCellValue('K1', "Importe IVA");
$objPHPExcel->getActiveSheet()->SetCellValue('L1', "Importe Total");
$objPHPExcel->getActiveSheet()->SetCellValue('M1', "Creación");
$objPHPExcel->getActiveSheet()->SetCellValue('N1', "Modificación");
$objPHPExcel->getActiveSheet()->SetCellValue('O1', "Estado");


$rowCount = 2;
//Información en Excel
while($row = $db->fetch_object($result)){
    // Para proyecto
    $projectstatic->id = $row->fk_projet;
    $projectstatic->ref = $row->project_ref;
    $projectstatic->title = $row->project_label;

    // Para usuario
    $usertmp->id=$row->id_user;
    $usertmp->lastname=$row->lastname;
    $usertmp->firstname=$row->firstname;
    $usertmp->login=$row->login;
    $usertmp->statut=$row->statut;
    $usertmp->photo=$row->photo;
    $usertmp->email=$row->email;

    // Reporte
    $expensereportstatic->id = $row->rowid;
    $expensereportstatic->ref = $row->ref;

    $expensereportstatic->fk_projet = $row->fk_projet;
    $projectstatic->id = $row->fk_projet;
    $projectstatic->ref = $row->project_ref;
    $projectstatic->title = $row->project_label;
    
    $expensereportstatic->status = $row->status;
    $expensereportstatic->date_debut = $db->jdate($row->date_debut);
    $expensereportstatic->date_fin = $db->jdate($row->date_fin);
    $expensereportstatic->date_create = $db->jdate($row->date_create);
    $expensereportstatic->date_modif = $db->jdate($row->date_modif);
    $expensereportstatic->date_valid = $db->jdate($row->date_valid);
    $expensereportstatic->date_approve = $db->jdate($row->date_approve);
    $expensereportstatic->note_private = $row->note_private;
    $expensereportstatic->note_public = $row->note_public;
    
    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $row->ref);
    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $row->referencia);
    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, strip_tags($projectstatic->getNomUrl(0)));
    $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $row->tipo);
    $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, strip_tags($usertmp->getNomUrl(0)));
    $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $row->date_debut > 0 ? dol_print_date($db->jdate($row->date_debut), 'day') : '');
    $objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $row->date_fin > 0 ? dol_print_date($db->jdate($row->date_fin), 'day') : '');
    $objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $row->date_valid > 0 ? dol_print_date($db->jdate($row->date_valid), 'day') : '');
    $objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $row->date_approve > 0 ? dol_print_date($db->jdate($row->date_approve), 'day') : '');
    $objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, price($row->total_ht));
    $objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, price($row->total_tva));
    $objPHPExcel->getActiveSheet()->SetCellValue('L'.$rowCount, price($row->total_ttc));
    $objPHPExcel->getActiveSheet()->SetCellValue('M'.$rowCount, dol_print_date($db->jdate($row->date_create), 'dayhour'));
    $objPHPExcel->getActiveSheet()->SetCellValue('N'.$rowCount, dol_print_date($db->jdate($row->date_modif), 'dayhour'));
    $objPHPExcel->getActiveSheet()->SetCellValue('O'.$rowCount, $langs->trans($expensereportstatic->getLibStatut(0)));
    
    $rowCount++; 
}

//Guardar archivo Excel
$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename='.$filename);
$objWriter->save('php://output');
exit;