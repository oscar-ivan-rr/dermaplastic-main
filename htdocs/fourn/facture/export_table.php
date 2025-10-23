<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));
//ini_set('display_errors', 1);
$facturestatic = new FactureFournisseur($db);
$supplierstatic = new Fournisseur($db);
$thirdparty = new Societe($db);
$form = new Form($db);
$formcompany = new FormCompany($db);
$filename = "invoice_report.xls";
$arrayfields    = (array) json_decode($_POST['datos_a_enviar']);
foreach ($arrayfields as $key => $value)
    $arrayfields[$key] = (array) $value;
$sql    = $_POST['sql'];
$factures    = json_decode($_POST['factures']);
if(sizeof($factures) > 0)
{
    $sqlExplode = explode('WHERE', $sql);
    $rowids = '(';
    $numFac = sizeof($factures);
    $iterator = 1;
    foreach ($factures as $fac){
        if($numFac != $iterator)
            $rowids .= ' f.rowid = '.$fac.' OR';
        else
            $rowids .= ' f.rowid = '.$fac.'';
        $iterator++;
    }
    $rowids.=') AND';
    $sql = $sqlExplode[0]."WHERE".$rowids.$sqlExplode[1];
}
$resql = $db->query($sql);
$num = $db->num_rows($resql);
$table = '';
$columnas=0;
foreach ($arrayfields as $key => $value) {
    if (!empty($value['checked'])) {
        $columnas++;
    }
}
/*header("Pragma: public");
header("Expires: 0");
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");*/
$table.='<table class="tagtable liste listwithfilterbefore" >'."\n";
$table.='<tr style="height: 250px;" rowspan="3">';
$table.='<td colspan="'.$columnas.'">&nbsp;</td>';
$table.='</tr>';
$table.='<tr style="height: 250px;" rowspan="3">';
$table.='<td colspan="'.$columnas.'">&nbsp;</td>';
$table.='</tr>';
$table.='<tr style="height: 250px;" rowspan="3">';
$table.='<td colspan="'.$columnas.'">&nbsp;</td>';
$table.='</tr>';
$table.='<tr style="height: 250px;" rowspan="3">';
$table.='<td colspan="'.$columnas.'">&nbsp;</td>';
$table.='</tr>';
$table.='<tr style="height: 250px;" rowspan="3">';
$table.='<td colspan="'.$columnas.'">&nbsp;</td>';
$table.='</tr>';
$table.='<tr style="height: 250px;" rowspan="3">';
$table.='<td colspan="'.$columnas.'">&nbsp;</td>';
$table.='</tr>';
$logo = DOL_DATA_ROOT.'/mycompany/logos/'.$mysoc->logo;
$table.='<tr>';
$table.='<td colspan="'.$columnas.'">DERMAGLOBAL</td>';
$table.='</tr>';
$table.='<tr class="liste_titre">';
//'<'.$tag.' class="'.$prefix.'liste_titre" '.$moreattrib.'>'
//$langs->trans($name)
if (!empty($arrayfields['f.ref']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.ref']['label']).'</th>';
if (!empty($arrayfields['f.ref_supplier']['checked']))         $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.ref_supplier']['label']).'</th>';
if (!empty($arrayfields['s.nom']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.nom']['label']).'</th>';
if (empty($arrayfields['f.type']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.type']['label']).'</th>';
if (empty($arrayfields['f.label']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.label']['label']).'</th>';
if (!empty($arrayfields['f.datef']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.datef']['label']).'</th>';
if (!empty($arrayfields['f.date_lim_reglement']['checked'])) $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.date_lim_reglement']['label']).'</th>';
if (!empty($arrayfields['s.earlypayment_validity']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.earlypayment_validity']['label']).'</th>';
if (empty($arrayfields['p.ref']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans("Ref. proyecto").'</th>';
if (!empty($arrayfields['s.town']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.town']['label']).'</th>';
if (!empty($arrayfields['s.zip']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.zip']['label']).'</th>';
if (empty($arrayfields['state.nom']['checked']))            $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['state.nom']['label']).'</th>';
if (empty($arrayfields['country.code_iso']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['country.code_iso']['label']).'</th>';
if (empty($arrayfields['typent.code']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['typent.code']['label']).'</th>';
if (!empty($arrayfields['f.fk_mode_reglement']['checked']))  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.fk_mode_reglement']['label']).'</th>';
if (!empty($arrayfields['f.total_ht']['checked']))           $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_ht']['label']).'</th>';
if (empty($arrayfields['f.total_vat']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_vat']['label']).'</th>';
if (empty($arrayfields['f.total_ttc']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_ttc']['label']).'</th>';
if (empty($arrayfields['dynamount_payed']['checked']))      $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['dynamount_payed']['label']).'</th>';
if (empty($arrayfields['rtp']['checked']))                  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['rtp']['label']).'</th>';



// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
//$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
//$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
//$table.=$hookmanager->resPrint;
if (!empty($arrayfields['f.datec']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.datec']['label']).'</th>';
if (!empty($arrayfields['f.tms']['checked']))       $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.tms']['label']).'</th>';
if (!empty($arrayfields['f.fk_statut']['checked'])) $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.fk_statut']['label']).'</th>';
$table.="</tr>\n";

$projectstatic = new Project($db);
$facturestatic = new FactureFournisseur($db);
$supplierstatic = new Fournisseur($db);

if ($num > 0)
{
    $i = 0;
    $totalarray = array();
    $total_remaintopay = 0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        $datelimit = $db->jdate($obj->datelimite);

        $facturestatic->id = $obj->facid;
        $facturestatic->ref = $obj->ref;
        $facturestatic->type = $obj->type;
        $facturestatic->ref_supplier = $obj->ref_supplier;
        $facturestatic->date_echeance = $db->jdate($obj->datelimite);
        $facturestatic->statut = $obj->fk_statut;
        $facturestatic->note_public = $obj->note_public;
        $facturestatic->note_private = $obj->note_private;

        $thirdparty->id = $obj->socid;
        $thirdparty->name = $obj->name;
        $thirdparty->client = $obj->client;
        $thirdparty->fournisseur = $obj->fournisseur;
        $thirdparty->code_client = $obj->code_client;
        $thirdparty->code_compta_client = $obj->code_compta_client;
        $thirdparty->code_fournisseur = $obj->code_fournisseur;
        $thirdparty->code_compta_fournisseur = $obj->code_compta_fournisseur;
        $thirdparty->email = $obj->email;
        $thirdparty->country_code = $obj->country_code;

        $projectstatic->id = $obj->project_id;
        $projectstatic->ref = $obj->project_ref;
        $projectstatic->title = $obj->project_label;

        $paiement = $facturestatic->getSommePaiement();
        $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
        $totaldeposits = $facturestatic->getSumDepositsUsed();
        $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
        $remaintopay = $obj->total_ttc - $totalpay;

        if ($facturestatic->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
            if ($facturestatic->isCreditNoteUsed()) {
                $remaintopay = -$facturestatic->getSumFromThisCreditNotesNotUsed();
            }
        }


        $table .= '<tr class="oddeven" ';
        $table .= '>';
        if (!empty($arrayfields['f.ref']['checked'])) {
            $table .= '<td class="nowrap">';
            $table .= $facturestatic->ref;
            $table .= "</td>\n";
        }

        // Supplier ref
        if (!empty($arrayfields['f.ref_supplier']['checked'])) {
            $table .= '<td class="nowrap tdoverflowmax200">';
            $table .= $obj->ref_supplier;
            $table .= '</td>';
        }
        // Third party
        if (! empty($arrayfields['s.nom']['checked']))
        {
            $table .= '<td class="tdoverflowmax200">';
            $table .= $thirdparty->name;
            $table .= '</td>';

        }

        // Type
        if (empty($arrayfields['f.type']['checked'])) {
            $table .= '<td class="nowrap">';
            $table .= $facturestatic->getLibType();
            $table .= "</td>";
            if (!$i) $totalarray['nbfield']++;
        }
        // Label
        if (empty($arrayfields['f.label']['checked']))
        {
            $table .= '<td class="nowrap">';
            $table .= $obj->label;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Date
        if (!empty($arrayfields['f.datef']['checked'])) {
            $table .= '<td align="center" class="nowrap">';
            $table .= dol_print_date($db->jdate($obj->datef), 'day');
            $table .= '</td>';
        }

        // Date limit
        if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
            $table .= '<td align="center" class="nowrap">'.dol_print_date($datelimit, 'day');
            if ($facturestatic->hasDelay())
            {
                $table .= img_warning($langs->trans('Late'));
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        //Date valid discount
        if (!empty($arrayfields['s.earlypayment_validity']['checked']))
        {
            $thirdparty->fetch($thirdparty->id);

            if($thirdparty->earlypayment_discount > 0){
                $date_invoiced = date("d-m-Y",strtotime($obj->datef));

                if($thirdparty->earlypayment_validity) $agregated_days = " + ".$thirdparty->earlypayment_validity." days";
                else $agregated_days = '';

                $date_valid = date("d/m/Y",strtotime($date_invoiced.$agregated_days));

                $table .= '<td class="center nowrap">'.$date_valid;
//                    print img_info($thirdparty->earlypayment_discount.'%');
                $table .= '<span style="font-size: 12px;"> '.$thirdparty->earlypayment_discount.'%</span>';
                if (strtotime($date_valid) < strtotime(date("d/m/Y")))
                {
                    $table .= img_error($langs->trans('Late'));
                }
                $table .= '</td>';
            }
            else {
                $table .= '<td class="center nowrap">Sin descuento</td>';
            }
            if (! $i) $totalarray['nbfield']++;
        }

        // Project
        if (empty($arrayfields['p.ref']['checked'])) {
            $table .= '<td class="nocellnopadd nowrap">';
            if ($obj->project_id > 0) {
                $table .= $projectstatic->ref;
            }
            $table .= '</td>';
        }

        // Town
        if (!empty($arrayfields['s.town']['checked']))
        {
            $table .= '<td class="nocellnopadd">';
            $table .= $obj->town;
            $table .= '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Zip
        if (! empty($arrayfields['s.zip']['checked']))
        {
            $table .= '<td class="nocellnopadd center">';
            $table .= $obj->zip;
            $table .= '</td>';
            if (! $i) $totalarray['nbfield']++;
        }


        // State
        if (empty($arrayfields['state.nom']['checked'])) {
            $table .= "<td>" . $obj->state_name . "</td>\n";
        }
        // Country
        if (empty($arrayfields['country.code_iso']['checked'])) {
            $table .= '<td class="center">';
            $tmparray = getCountry($obj->fk_pays, 'all');
            $table .= $tmparray['label'];
            $table .= '</td>';
        }
        // Type ent
        if (empty($arrayfields['typent.code']['checked'])) {
            $table .= '<td class="center">';
            if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
            $table .= $typenArray[$obj->typent_code];
            $table .= '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Payment mode
        if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
            $table .= '<td>';
            $form->load_cache_types_paiements();
            $table .= $form->cache_types_paiements[$obj->fk_mode_reglement]['label'];
            $table .= '</td>';
        }
        // Amount HT
        if (!empty($arrayfields['f.total_ht']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_ht) . "</td>\n";
            $totalarray['val']['f.total_ht'] += $obj->total_ht;
        }
        // Amount VAT
        if (empty($arrayfields['f.total_vat']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_vat) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_vat';
            $totalarray['val']['f.total_vat'] += $obj->total_vat;
        }
        // Amount LocalTax1
        if (!empty($arrayfields['f.total_localtax1']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_localtax1) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_localtax1';
            $totalarray['val']['f.total_localtax1'] += $obj->total_localtax1;
        }
        // Amount LocalTax2
        if (!empty($arrayfields['f.total_localtax2']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_localtax2) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_localtax2';
            $totalarray['val']['f.total_localtax2'] += $obj->total_localtax2;
        }
        // Amount TTC
        if (empty($arrayfields['f.total_ttc']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_ttc) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
            $totalarray['val']['f.total_ttc'] += $obj->total_ttc;
        }

        if (empty($arrayfields['dynamount_payed']['checked'])) {
            $table .= '<td class="right nowrap">'.(!empty($totalpay) ?price($totalpay, 0, $langs) : '&nbsp;').'</td>'; // TODO Use a denormalized field
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totalam';
            $totalarray['val']['totalam'] += $totalpay;
        }

        // Pending amount
        if (empty($arrayfields['rtp']['checked'])) {
            $table .= '<td class="right nowrap">';
            $table .= (! empty($remaintopay)?price($remaintopay, 0, $langs):'&nbsp;').'</td>'; // TODO Use a denormalized field
            $table .= '</td>'; // TODO Use a denormalized field
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'rtp';
            $totalarray['val']['rtp'] += $remaintopay;
        }

        // Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
        // Fields from hook
        $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i);
        $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
        $table .= $hookmanager->resPrint;
        // Date creation
        if (!empty($arrayfields['f.datec']['checked'])) {
            $table .= '<td align="center" class="nowrap">';
            $table .= dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
            $table .= '</td>';
        }
        // Date modification
        if (!empty($arrayfields['f.tms']['checked'])) {
            $table .= '<td align="center" class="nowrap">';
            $table .= dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
            $table .= '</td>';
        }
        // Status
        if (!empty($arrayfields['f.fk_statut']['checked']))
        {
            $table .= '<td class="right nowrap">';
            // TODO $paiement is not yet defined
            $table .= $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 5, $paiement, $obj->type,1);
            $table .= "</td>";
            if (!$i) $totalarray['nbfield']++;
        }

        $table .= "</tr>\n";

        $i++;
    }
    // Show total line
    //include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
}
$db->free($resql);
$table.="</table>\n";
// save $table inside temporary file that will be deleted later
$tmpfile = tempnam(sys_get_temp_dir(), 'html');
file_put_contents($tmpfile, $table);
//Imagen
$objDrawing = new PHPExcel_Worksheet_Drawing();
$objDrawing->setName('Logo');
$objDrawing->setDescription('Logo');
$objDrawing->setPath($logo);
$objDrawing->setHeight(100);
$objDrawing->setCoordinates('E1');
// insert $table into $objPHPExcel's Active Sheet through $excelHTMLReader
$objPHPExcel     = new PHPExcel();
$excelHTMLReader = PHPExcel_IOFactory::createReader('HTML');
$excelHTMLReader->loadIntoExisting($tmpfile, $objPHPExcel);
$objPHPExcel->getActiveSheet()->setTitle('Reporte de facturas'); // Change sheet's title if you want
$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
unlink($tmpfile); // delete temporary file because it isn't needed anymore

header('Content-Type: application/vnd.ms-excel'); // header for .xlxs file
header('Content-Disposition: attachment;filename='.$filename); // specify the download file name
header('Cache-Control: max-age=0');

// Creates a writer to output the $objPHPExcel's content
$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$writer->save('php://output');
