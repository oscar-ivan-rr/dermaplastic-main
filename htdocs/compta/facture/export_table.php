<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/PHPExcel-1.8/Classes/PHPExcel.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
$langs->loadLangs(array('bills', 'companies', 'products', 'categories'));
//ini_set('display_errors', 1);
$facturestaticc = new Facture($db);
$thirdpartystatic = new Societe($db);
$ticketstatic = new Ticket($db);
$formcompany = new FormCompany($db);
$form = new Form($db);
$filename = "invoice_report.xls";
$arrayfields    = (array) json_decode($_POST['datos_a_enviar']);
foreach ($arrayfields as $key => $value)
    $arrayfields[$key] = (array) $value;
$sql    = $_POST['sql'];
$factures = json_decode($_POST['factures'])?? [];
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
$fecha = date("d-m-Y H:i:s");
header('Content-type: application/vnd.ms-excel charset=iso-8859-1');
header("Content-Disposition: attachment; filename=Cuentas_por_cobrar_$fecha.xls"); //Indica el nombre del archivo resultante
header("Pragma: no-cache");
header("Expires: 0");
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
$table.='<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table class="tagtable liste listwithfilterbefore" >'."\n";
$table.='<tr class="liste_titre">';

//'<'.$tag.' class="'.$prefix.'liste_titre" '.$moreattrib.'>'
//$langs->trans($name)
if (!empty($arrayfields['f.ref']['checked']))          $table.='<th class="rightliste_titre" colspan="2">'.$langs->trans($arrayfields['f.ref']['label']).'</th>';
if (!empty($arrayfields['f.ref_client']['checked']))         $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.ref_client']['label']).'</th>';
if (!empty($arrayfields['f.type']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.type']['label']).'</th>';
$table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.date']['label']).'</th>';
if (!empty($arrayfields['f.date_lim_reglement']['checked'])) $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.date_lim_reglement']['label']).'</th>';
if (!empty($arrayfields['s.earlypayment_validity']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.earlypayment_validity']['label']).'</th>';
if (!empty($arrayfields['p.ref']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans("Ref. proyecto").'</th>';
if (!empty($arrayfields['p.title']['checked']))              $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['p.title']['label']).'</th>';
if (!empty($arrayfields['s.nom']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.nom']['label']).'</th>';
if (!empty($arrayfields['s.phone']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.phone']['label']).'</th>';
if (!empty($arrayfields['s.town']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.town']['label']).'</th>';
if (!empty($arrayfields['s.zip']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['s.zip']['label']).'</th>';
if (!empty($arrayfields['state.nom']['checked']))            $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['state.nom']['label']).'</th>';
if (!empty($arrayfields['country.code_iso']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['country.code_iso']['label']).'</th>';
if (!empty($arrayfields['typent.code']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['typent.code']['label']).'</th>';
if (!empty($arrayfields['f.fk_mode_reglement']['checked']))  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.fk_mode_reglement']['label']).'</th>';
if (!empty($arrayfields['f.fk_cond_reglement']['checked']))  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.fk_cond_reglement']['label']).'</th>';
if (!empty($arrayfields['f.module_source']['checked']))      $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.module_source']['label']).'</th>';
if (!empty($arrayfields['f.pos_source']['checked']))         $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.pos_source']['label']).'</th>';
if (!empty($arrayfields['ht_without_discount']['checked']))  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['ht_without_discount']['label']).'</th>';
if (!empty($arrayfields['discount']['checked']))             $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['discount']['label']).'</th>';
if (!empty($arrayfields['f.total_ht']['checked']))           $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_ht']['label']).'</th>';
if (!empty($arrayfields['f.total_vat']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_vat']['label']).'</th>';
if (!empty($arrayfields['credit']['checked']))               $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['credit']['label']).'</th>';
if (!empty($arrayfields['debit']['checked']))                $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['debit']['label']).'</th>';
if (!empty($arrayfields['transfer']['checked']))             $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['transfer']['label']).'</th>';
if (!empty($arrayfields['cash']['checked']))                 $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['cash']['label']).'</th>';

if (!empty($arrayfields['f.total_localtax1']['checked']))    $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_localtax1']['label']).'</th>';
if (!empty($arrayfields['f.total_localtax2']['checked']))    $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_localtax2']['label']).'</th>';
if (!empty($arrayfields['f.total_ttc']['checked']))          $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.total_ttc']['label']).'</th>';
if (!empty($arrayfields['ef.formpagcfdi']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['ef.formpagcfdi']['label']).'</th>';
if (!empty($arrayfields['ef.usocfdi']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['ef.usocfdi']['label']).'</th>';
if (!empty($arrayfields['ef.cfdidoctiporelacion']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['ef.cfdidoctiporelacion']['label']).'</th>';
if (!empty($arrayfields['ef.tipodecambiocfdi']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['ef.tipodecambiocfdi']['label']).'</th>';
if (!empty($arrayfields['ef.clave_expor']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['ef.clave_expor']['label']).'</th>';if (!empty($arrayfields['f.retained_warranty']['checked']))  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.retained_warranty']['label']).'</th>';
if (!empty($arrayfields['dynamount_payed']['checked']))      $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['dynamount_payed']['label']).'</th>';
if (!empty($arrayfields['rtp']['checked']))                  $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['rtp']['label']).'</th>';
if (!empty($arrayfields['margin']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['margin']['label']).'</th>';
if (!empty($arrayfields['f.fk_user_author']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.fk_user_author']['label']).'</th>';
if (!empty($arrayfields['f.commission']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.commission']['label']).'</th>';


// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$param = '';
$sortfield = '';
$sortorde = '';
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
$table.=$hookmanager->resPrint;
if (!empty($arrayfields['f.datec']['checked']))     $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.datec']['label']).'</th>';
if (!empty($arrayfields['f.tms']['checked']))       $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.tms']['label']).'</th>';
if (!empty($arrayfields['f.date_closing']['checked']))       $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.date_closing']['label']).'</th>';
if (!empty($arrayfields['f.fk_statut']['checked'])) $table.='<th class="rightliste_titre">'.$langs->trans($arrayfields['f.fk_statut']['label']).'</th>';
$table.="</tr>\n";

$projectstatic = new Project($db);
$discount = new DiscountAbsolute($db);

if ($num > 0)
{
    $i = 0;
    $totalarray = array();
    $total_remaintopay = 0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        if ($obj->is_ticket)
        {
            $facturestatic = $ticketstatic;
            if ($obj->type !=0)
            {
                $obj->total_ttc = $obj->total_ttc *-1;
            }
        }
        else
        {
            $facturestatic = $facturestaticc;
        }

        $datelimit = $db->jdate($obj->datelimite);

        $facturestatic->id = $obj->id;
        $facturestatic->ref = $obj->ref;
        $facturestatic->ref_client = $obj->ref_client;
        $facturestatic->type = $obj->type;
        $facturestatic->total_ht = $obj->total_ht;
        $facturestatic->total_tva = $obj->total_vat;
        $facturestatic->total_ttc = $obj->total_ttc;
        $facturestatic->statut = $obj->fk_statut;
        $facturestatic->close_code = $obj->close_code;
        $facturestatic->total_ttc = $obj->total_ttc;
        $facturestatic->paye = $obj->paye;
        $facturestatic->fk_soc = $obj->socid;
        $facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);
        $facturestatic->note_public = $obj->note_public;
        $facturestatic->note_private = $obj->note_private;
        if ($conf->global->INVOICE_USE_SITUATION && $conf->global->INVOICE_USE_SITUATION_RETAINED_WARRANTY)
        {
            $facturestatic->retained_warranty = $obj->retained_warranty;
            $facturestatic->retained_warranty_date_limit = $obj->retained_warranty_date_limit;
            $facturestatic->situation_final = $obj->retained_warranty_date_limit;
            $facturestatic->situation_final = $obj->retained_warranty_date_limit;
            $facturestatic->situation_cycle_ref = $obj->situation_cycle_ref;
            $facturestatic->situation_counter = $obj->situation_counter;
        }
        $thirdpartystatic->id = $obj->socid;
        $thirdpartystatic->name = $obj->name;
        $thirdpartystatic->client = $obj->client;
        $thirdpartystatic->fournisseur = $obj->fournisseur;
        $thirdpartystatic->code_client = $obj->code_client;
        $thirdpartystatic->code_compta_client = $obj->code_compta_client;
        $thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
        $thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
        $thirdpartystatic->email = $obj->email;
        $thirdpartystatic->country_code = $obj->country_code;

        $projectstatic->id = $obj->project_id;
        $projectstatic->ref = $obj->project_ref;
        $projectstatic->title = $obj->project_label;

        $paiement = $facturestatic->getSommePaiement();

        // Get amount for each payment method
        $cash = 0;
        $debit = 0;
        $credit = 0;
        $transfer = 0;
        $paiements = $facturestatic->getListOfPayments();
        foreach ($paiements as $payment) {
            switch ($payment['type']) {
                case 'LIQ':
                    $cash += $payment['amount'];
                    break;
                case 'TD/C':
                    $debit += $payment['amount'];
                    break;
                case 'CB':
                    $credit += $payment['amount'];
                    break;
                case 'VIR':
                    $transfer += $payment['amount'];
                    break;
            }
        }

        $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
        $totaldeposits = $facturestatic->getSumDepositsUsed();
        $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
        $remaintopay = price2num($facturestatic->total_ttc - $totalpay);

        if ($facturestatic->statut == Facture::STATUS_CLOSED && $facturestatic->close_code == 'discount_vat') {		// If invoice closed with discount for anticipated payment
            $remaintopay = 0;
        }
        if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {		// If credit note closed, we take into account the amount not yet consummed
            $remaincreditnote = $discount->getAvailableDiscounts($obj->fk_soc, '', 'rc.fk_facture_source='.$facturestatic->id);
            $remaintopay = -$remaincreditnote;
            $totalpay = price2num($facturestatic->total_ttc - $remaintopay);
        }


        $table .= '<tr class="oddeven"';
        $table .= '>';
        if (!empty($arrayfields['f.ref']['checked'])) {
            $table .= '<td class="nowrap" colspan="2" >';
            $table .= $facturestatic->ref;
            $table .= "</td>\n";
        }
       
        // Customer ref
        if (!empty($arrayfields['f.ref_client']['checked'])) {
            $table .= '<td class="nowrap tdoverflowmax200">';
            $table .= $obj->ref_client;
            $table .= '</td>';
        }

        // Type
        if (!empty($arrayfields['f.type']['checked'])) {
            if ($obj->is_ticket)
            {
                $table .= '<td class="nowrap">';
                $table .= 'Tiket del POS';
                $table .= "</td>";

            }
            else
            {
                $table .= '<td class="nowrap">';
                $table .= $facturestatic->getLibType();
                $table .= "</td>";
            }

            if (!$i) $totalarray['nbfield']++;
            //$table .= '<td class="nowrap">';
            //$table .= $facturestatic->getLibType();
            //$table .= "</td>";
            //if (!$i) $totalarray['nbfield']++;
        }

        // Date
        //if (empty($arrayfields['f.date']['checked'])) {
            $table .= '<td align="center" class="nowrap">';
            $table .= dol_print_date($db->jdate($obj->df), 'day');
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        //}

        // Date limit
        if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
            if (method_exists($facturestatic,'hasDelay') && $facturestatic->hasDelay())
            {
                $table .= '<td align="center" class="nowrap"  style="color:red;">'.dol_print_date($datelimit, 'day');
            } else {
                $table .= '<td align="center" class="nowrap">'.dol_print_date($datelimit, 'day');
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        //Date valid discount
        if (!empty($arrayfields['s.earlypayment_validity']['checked']))
        {
            $thirdpartystatic->fetch($thirdpartystatic->id);
            if($thirdpartystatic->earlypayment_discount > 0){
                $date_invoiced = date("d-m-Y",strtotime($obj->df));

                if($thirdpartystatic->earlypayment_validity) $agregated_days = " + ".$thirdpartystatic->earlypayment_validity." days";
                else $agregated_days = '';

                $date_valid = date("d/m/Y",strtotime($date_invoiced.$agregated_days));

                $table .= '<td class="center nowrap">'.$date_valid;
//                    print img_info($thirdparty->earlypayment_discount.'%');
                $table .= '<span style="font-size: 12px;"> '.$thirdpartystatic->earlypayment_discount.'%</span>';
                if (strtotime($date_valid) > strtotime(date("d/m/Y")))
                {
                    $table .= img_error($langs->trans('Late'));
                }
                $table .= '</td>';
            }
            else {
                $table .= '<td class="center nowrap">Sin descuento</td>';
            }
            if (!$i) $totalarray['nbfield']++;
        }

        // Project ref
        if (!empty($arrayfields['p.ref']['checked'])) {
            $table .= '<td class="nocellnopadd nowrap">';
            if ($obj->project_id > 0) {
                $table .= $projectstatic->ref;
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Project title
        if (!empty($arrayfields['p.title']['checked'])) {
            $table .= '<td class="nowrap">';
            if ($obj->project_id > 0) {
                $table .= $projectstatic->title;
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Third party
        if (!empty($arrayfields['s.nom']['checked'])) {
            $table .= '<td class="tdoverflowmax200">';
            $table .= $thirdpartystatic->name;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        //Phone
        if (!empty($arrayfields['s.phone']['checked'])) {
            $table .= '<td class="tdoverflowmax200">';
            $table .= $obj->phone;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Town
        if (!empty($arrayfields['s.town']['checked'])) {
            $table .= '<td>';
            $table .= $obj->town;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Zip
        if (!empty($arrayfields['s.zip']['checked'])) {
            $table .= '<td>';
            $table .= $obj->zip;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // State
        if (!empty($arrayfields['state.nom']['checked'])) {
            $table .= "<td>" . $obj->state_name . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
        }
        // Country
        if (!empty($arrayfields['country.code_iso']['checked'])) {
            $table .= '<td class="center">';
            $tmparray = getCountry($obj->fk_pays, 'all');
            $table .= $tmparray['label'];
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Type ent
        if (!empty($arrayfields['typent.code']['checked'])) {
            $table .= '<td class="center">';
            if (!is_array($typenArray) || count($typenArray) == 0) $typenArray = $formcompany->typent_array(1);
            $table .= $typenArray[$obj->typent_code];
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // Staff
        if (!empty($arrayfields['staff.code']['checked'])) {
            $table .= '<td class="center">';
            if (!is_array($staffArray) || count($staffArray) == 0) $staffArray = $formcompany->effectif_array(1);
            $table .= $staffArray[$obj->staff_code];
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Payment mode
        if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
            $table .= '<td>';
            $form->load_cache_types_paiements();
            $table .= $form->cache_types_paiements[$obj->fk_mode_reglement]['label'];
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Payment terms
        if (!empty($arrayfields['f.fk_cond_reglement']['checked'])) {
            $table .= '<td>';
            $form->load_cache_conditions_paiements();
            $table .= $form->cache_conditions_paiements[$obj->fk_cond_reglement]['label'];
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Module Source
        if (!empty($arrayfields['f.module_source']['checked'])) {
            $table .= '<td>';
            $table .= $obj->module_source;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // POS Terminal
        if (!empty($arrayfields['f.pos_source']['checked'])) {
            $table .= '<td>';
            $table .= $obj->pos_source;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Amount without discount
        if (!empty($arrayfields['ht_without_discount']['checked']))
        {
            $facturestatic->fetch($facturestatic->id);
            $amount = price($facturestatic->getDiscount())+price($obj->total_ht);
            $table .=  '<td class="right nowrap">'.price($amount)."</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'ht_without_discount';
            $totalarray['val']['ht_without_discount'] += ($facturestatic->getDiscount()+$obj->total_ht);
        }
        // Discount
        if (!empty($arrayfields['discount']['checked']))
        {
            $facturestatic->fetch($facturestatic->id);
            $table .= '<td class="right nowrap">'.price($facturestatic->getDiscount())."</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'discount';
            $totalarray['val']['discount'] += $facturestatic->getDiscount();
        }
        // Amount HT
        if (!empty($arrayfields['f.total_ht']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_ht) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ht';
            $totalarray['val']['f.total_ht'] += $obj->total_ht;
        }
        // Amount VAT
        if (!empty($arrayfields['f.total_vat']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_vat) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_vat';
            $totalarray['val']['f.total_vat'] += $obj->total_vat;
        }
        // Credit card amount
        if (!empty($arrayfields['credit']['checked']))
        {
            $table .=  '<td class="right nowrap">'.($credit > 0 ? price($credit) : '')."</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'credit';
            $totalarray['val']['credit'] += $credit;
        }
        // Debit card amount
        if (!empty($arrayfields['debit']['checked']))
        {
            $table .=  '<td class="right nowrap">'.($debit > 0 ? price($debit) : '')."</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'debit';
            $totalarray['val']['debit'] += $debit;
        }
        // Transfer amount
        if (!empty($arrayfields['transfer']['checked']))
        {
            $table .=  '<td class="right nowrap">'.($transfer > 0 ? price($transfer) : '')."</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'transfer';
            $totalarray['val']['transfer'] += $transfer;
        }
        // Cash amount
        if (!empty($arrayfields['cash']['checked']))
        {
            $table .=  '<td class="right nowrap">'.($cash > 0 ? price($cash) : '')."</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'cash';
            $totalarray['val']['cash'] += $cash;
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
        if (!empty($arrayfields['f.total_ttc']['checked'])) {
            $table .= '<td class="right nowrap">' . price($obj->total_ttc) . "</td>\n";
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
            $totalarray['val']['f.total_ttc'] += $obj->total_ttc;
        }

        // Forma de pago CFDI
        if (!empty($arrayfields['ef.formpagcfdi']['checked'])) {
            $table .= '<td class="nowrap">';
            if (!empty($obj->options_formpagcfdi)){
                if ($obj->options_formpagcfdi == 'PUE') {
                    $table .= $obj->options_formpagcfdi.' - Pago en una sola exhibición';
                } else {
                    $table .= $obj->options_formpagcfdi.' - Pago en parcialidades';
                }
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Uso CFDI
        if (!empty($arrayfields['ef.usocfdi']['checked'])) {
            $sql = 'SELECT label FROM ';
            $sql .= MAIN_DB_PREFIX . 'c_cfdimx_uso_cfdi ';
            $sql .= 'WHERE code = "'.$obj->options_usocfdi.'"';
            $result = $db->query($sql);
            $table .= '<td class="nowrap">';
            if($result){
                $uso_cfdi = $db->fetch_object($result);
                $db->free($result);
                if (!empty($uso_cfdi->label))   $table .= $obj->options_usocfdi.' - '.$uso_cfdi->label;
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        
        // CFDI tipo de relación
        if (!empty($arrayfields['ef.cfdidoctiporelacion']['checked'])) {
            $sql = 'SELECT label FROM ';
            $sql .= MAIN_DB_PREFIX . 'c_cfdimx_tipo_rel ';
            $sql .= 'WHERE code = "'.$obj->options_cfdidoctiporelacion.'"';
            $result = $db->query($sql);
            $table .= '<td class="nowrap">';
            if($result){
                $tipo = $db->fetch_object($result);
                $db->free($result);
                if (!empty($tipo->label))   $table .= $obj->options_cfdidoctiporelacion.' - '.$tipo->label;
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // CFDI tipo de cambio
        if (!empty($arrayfields['ef.tipodecambiocfdi']['checked'])) {
            $table .= '<td class="nowrap">';
            $table .= $obj->options_tipodecambiocfdi;
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Clave de exportación
        if (!empty($arrayfields['ef.clave_expor']['checked'])) {
            $sql = 'SELECT label FROM ';
            $sql .= MAIN_DB_PREFIX . 'c_cfdimx_exportacion ';
            $sql .= 'WHERE code = "'.$obj->options_clave_expor.'"';
            $result = $db->query($sql);
            $table .= '<td class="nowrap">';
            if($result){
                $clave_exp = $db->fetch_object($result);
                $db->free($result);
                if (!empty($clave_exp->label))   $table .= $clave_exp->label;
            }
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['f.retained_warranty']['checked'])) {
            $table .= '<td align="right">' . (!empty($obj->retained_warranty) ? price($obj->retained_warranty) . '%' : '&nbsp;') . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        if (!empty($arrayfields['dynamount_payed']['checked'])) {
            $table .= '<td class="right nowrap">'.(!empty($totalpay) ?price($totalpay, 0, $langs) : '&nbsp;').'</td>'; // TODO Use a denormalized field
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'dynamount_payed';
            $totalarray['val']['dynamount_payed'] += $totalpay;
        }

        // Pending amount
        if (!empty($arrayfields['rtp']['checked'])) {
            $table .= '<td class="right nowrap">';
            $table .= (!empty($remaintopay) ? price($remaintopay, 0, $langs) : '&nbsp;');
            $table .= '</td>'; // TODO Use a denormalized field
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'rtp';
            $totalarray['val']['rtp'] += $remaintopay;
        }

        //Margen
        $formmargin = new FormMargin($db);
        $res_margin = 0;
		$count_margin = 0;
        if (!empty($arrayfields['margin']['checked']))
        {				
            $table .=  '<td class=" right nowrap">';
            if ($obj->is_ticket){
                //Validacion precio de compra por producto antes de la actualizacion
                $banPa = true;
                $sqlPa = "SELECT buy_price_ht FROM llx_pos_ticketdet WHERE fk_ticket = ".$facturestatic->id;
                $resultPa = $db->query($sqlPa);
                if ($resultPa){
                    $numPa = $db->num_rows($resultPa);
                    $iPa = 0;
                    while ($iPa < $numPa)
                    {
                        $objPa = $db->fetch_object($resultPa);
                        if(is_null($objPa->buy_price_ht)){
                            $banPa = false;
                            break;
                        }
                        $iPa ++;
                    }
                }
                if ($numPa > 0 && $banPa) {
                    $facturestatic->fetch_lines();
                    $margen = $formmargin->getMarginInfosArray($facturestatic);
                    $margin = price($margen['total_mark_rate'], null, null, null, null, 2);
                    $table .=  (($margen['total_mark_rate'] == '') ? '' : $margin.'%');
                    $res_margin += $margin;
                }else{
                    $margin = 0;
                    $res_margin += $margin;
                }
            }else{
                $facturestatic->fetch_lines();
                $margen = $formmargin->getMarginInfosArray($facturestatic);
                $margin = price($margen['total_mark_rate'], null, null, null, null, 2);
                $table .=  (($margen['total_mark_rate'] == '') ? '' : $margin.'%');
                $res_margin += $margin;
            }
            $count_margin += 1;
            $table .=  '</td>';
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'margin';
        }

        // User
        include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
        $userstatic = new User($db);
        if (!empty($arrayfields['f.fk_user_author']['checked']))
        {
            $userstatic->fetch($obj->fk_user_author);
            $table .=  '<td class="nowrap">';
            $table .=  $userstatic->getNomUrl(0, 'nolink');
            $table .=  '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Commission
        $objecttmp = new Facture($db);
        if (!empty($arrayfields['f.commission']['checked']))
        {
            $objecttmp->fetch($obj->id);
            $table .= '<td class="nowrap">';
            $table .= price($objecttmp->commission);
            $table .= '</td>';
            if (!$i) $totalarray['nbfield']++;
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
        // Date closing
        if (!empty($arrayfields['f.date_closing']['checked'])) {
            $table .= '<td align="center" class="nowrap">';
            $table .= dol_print_date($db->jdate($obj->date_closing), 'dayhour', 'tzuser');
            $table .= '</td>';
        }
        // Status
        if (!empty($arrayfields['f.fk_statut']['checked']))
        {
            if (!$obj->is_ticket)
            {
                $table .= '<td class="nowrap right">';
                $table .= $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 5, $paiement, $obj->type);
                $table .= "</td>";
                if (!$i) $totalarray['nbfield']++;
            }
            else
            {
                $table .= '<td class="nowrap right">';
                $table .= $facturestatic->LibStatut($obj->fk_statut, 1);
                $table .= "</td>";
                if (!$i) $totalarray['nbfield']++;
            }
        }

        $table .= "</tr>\n";

        $i++;
    }
    // Show total line
        
    $key = 0;
    $table .= '<tr class="liste_total">';
    $table .= '<td class="left"><b>'.$langs->trans("Total").'</b></td>';
    while($key < $totalarray['nbfield']){
        if(isset($totalarray['pos'][$key])){
            $table .= '<td class="right">'.price($totalarray['val'][$totalarray['pos'][$key]]).'</td>';
        }
        else{
            $table .= '<td>&nbsp;</td>';
        }
        $key++;
    }
    $table .= '</tr>';

}
$db->free($resql);
$table.="</table>\n";
echo $table;

