<?php

/*
 * Juan Pablo Merla De Lara
 */
global $conf, $langs;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$search_fac = GETPOST('search_fac','alpha');
$search_soc = GETPOST('search_soc','alpha');
//Desde Hasta fecha factura
$date_start_facturemonth=GETPOST("date_start_facturemonth");
$date_start_factureday=GETPOST("date_start_factureday");
$date_start_factureyear=GETPOST("date_start_factureyear");
$date_end_facturemonth=GETPOST("date_end_facturemonth");
$date_end_factureday=GETPOST("date_end_factureday");
$date_end_factureyear=GETPOST("date_end_factureyear");

if($date_start_factureyear)$date_start_facture=dol_mktime(0,0,0,$date_start_facturemonth,$date_start_factureday,$date_start_factureyear);
else $date_start_facture=null;
if($date_end_factureyear)$date_end_facture=dol_mktime(0,0,0,$date_end_facturemonth,$date_end_factureday,$date_end_factureyear);
else $date_end_facture=null;

//Paginacion
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : "25";
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (!$sortorder) $sortorder = "desc";
if (!$sortfield) $sortfield = "total";
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) {
    $page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);

$sql = "SELECT cd.factura_seriefolio, cd.fk_facture, cd.fechaTimbrado, cd.uuid, f.total_ttc,s.rowid,s.nom,f.type";
$sql .= ", s.entity, s.client, s.status, s.fournisseur";
$sql .= " FROM " . MAIN_DB_PREFIX . "cfdimx as cd";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = cd.fk_facture";
$sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
$sql .= " WHERE cd.entity_id = 1 AND cd.cancelado = 0 AND f.fk_mode_reglement = 4 AND f.type = 0";

if(!empty($search_fac))
{
    $sql .= " AND cd.factura_seriefolio like '%".$search_fac."%'";
}
if(!empty($search_soc))
{
    $sql .= " AND s.nom like '%".$search_soc."%'";
}
if($date_start_factureyear && !$date_end_factureyear)
    $sql.=" AND DATE(cd.fechaTimbrado) >= '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."'";
else if (!$date_start_factureyear && $date_end_factureyear)
    $sql.=" AND DATE(cd.fechaTimbrado) <= '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
else if ($date_start_factureyear && $date_end_factureyear)
    $sql.=" AND DATE(cd.fechaTimbrado) BETWEEN '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."' AND '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)    // if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}
$sql.= ' GROUP BY f.rowid, f.ref, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.fk_cond_reglement, f.total, f.tva, f.total_ttc, f.localtax1, f.localtax2, f.datef, f.date_lim_reglement, f.module_source, f.pos_source, f.paye, f.fk_statut, f.close_code, f.datec, f.tms, f.date_closing, f.retained_warranty, f.retained_warranty_date_limit, f.situation_final, f.situation_cycle_ref, f.situation_counter, s.rowid, s.nom, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur';
$sql.= ' ORDER BY f.datef DESC';
$sql .= $db->plimit($limit + 1, $offset);


$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);
$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage=' . urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit=' . urlencode($limit);
if(!empty($search_fac)) $param .= '&search_fac=' . urlencode($search_fac);
if(!empty($search_soc)) $param .= '&search_soc=' . urlencode($search_soc);
if ($date_start_factureday)         $param .= '&date_start_factureday='.urlencode($date_start_factureday);
if ($date_start_facturemonth)       $param .= '&date_start_facturemonth='.urlencode($date_start_facturemonth);
if ($date_start_factureyear)        $param .= '&date_start_factureyear='.urlencode($date_start_factureyear);
if ($date_end_factureday)         $param .= '&date_end_factureday='.urlencode($date_end_factureday);
if ($date_end_facturemonth)       $param .= '&date_end_facturemonth='.urlencode($date_end_facturemonth);
if ($date_end_factureyear)        $param .= '&date_end_factureyear='.urlencode($date_end_factureyear);
/*
 * View
 */
$companystatic = new Societe($db);
llxHeader("", $langs->trans("Ventas Efectivo F"));

/*
 * View
 */
//TABLA DE PRODUCTOS
print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter" autocomplete="off">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
if (!empty($orden)) $orden = $orden;
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="' . $orden . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
//print '<input type="hidden" name="rc_status" value="'.$rc_status.'">';
print '<input type="hidden" name="action" value="Mostrar_Productos">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print_barre_liste("Ventas Efectivo F", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'building', 0, '', '', $limit);

print '<table class="liste">';
print '<tr class="liste_titre">';

print '<td class="liste_titre">';
print '<input class="flat searchstring maxwidth100imp" type="text" name="search_fac" value="' . dol_escape_htmltag($search_fac) . '">';
print '</td>';

print '<td class="liste_titre">';
print '<input class="flat searchstring maxwidth100imp" type="text" name="search_soc" value="' . dol_escape_htmltag($search_soc) . '">';
print '</td>';

print '<td class="liste_titre">';
print "Desde: "
    .$form->select_date($date_start_facture,'date_start_facture',0,0,0,'',1,0,1)
    ."<br>Hasta: "
    .$form->select_date($date_end_facture,'date_end_facture',0,0,0,'',1,0,1);
print '</td>';

print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre center">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';

print_liste_field_titre("Factura", $_SERVER["PHP_SELF"], "factura_serie", '', $param, '', $sortfield, $sortorder, ' ');
print_liste_field_titre("Cliente", $_SERVER["PHP_SELF"], "nom", '', $param, '', $sortfield, $sortorder, ' ');
print '<td class="left">' . $langs->trans('Fecha Timbrado') . '</td>';
print '<td class="left">' . $langs->trans('UUID') . '</td>';
print '<td class="left">' . $langs->trans('Tipo') . '</td>';
print '<td class="right">' . $langs->trans('Importe Total') . '</td>';
print '<td class="right">' . $langs->trans('Abono') . '</td>';
print '<td class="right">' . $langs->trans('Saldo') . '</td>';


print '</tr>' . "\n";
$facturestatic = new Facture($db);
if ($resql > 0) {
    $total_ttc = 0;
    $total_abono = 0;
    $total_resta_pagar = 0;
    while ($row= $db->fetch_object($resql)) {
        $facturestatic->fetch($row->fk_facture);
        print '<tr>';
        print "<td>&nbsp;<a href='../../compta/facture/card.php?facid=".$row->fk_facture."'>".$row->factura_seriefolio."</a></td>";
        $companystatic->id = $row->rowid;
        $companystatic->name = $row->nom;
        $companystatic->entity = $row->entity;
        $companystatic->client = $row->client;
        $companystatic->status = $row->status;
        $companystatic->fournisseur = $row->fournisseur;
        print '<td>' . $companystatic->getNomUrl(1, '', 100, 0, 1) . '</td>';
        print '<td>' . $row->fechaTimbrado . '</td>';
        print '<td>' . $row->uuid . '</td>';
        $tipo_doc = "";
        if($row->type==2){
            $tipo_doc="Nota de Credito";
        }else{
            $tipo_doc="Factura Estandar";
        }
        print '<td>' . $tipo_doc . '</td>';
        print '<td>' . price($row->total_ttc) . '</td>';
        $paiement = $facturestatic->getSommePaiement();
        if (method_exists($facturestatic,'getSumCreditNotesUsed'))
        {
            $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
        }
        else
        {
            $totalcreditnotes = 0;
        }
        if (method_exists($facturestatic,'getSumDepositsUsed'))
        {
            $totaldeposits = $facturestatic->getSumDepositsUsed();
        }
        else
        {
            $totaldeposits = 0;
        }
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
        if ($remaintopay >= -0.019 && $remaintopay <= 0.011){
            $remaintopay = 0;
        }
        print '<td class="right nowrap">'.(!empty($totalpay) ?price($totalpay, 0, $langs) : '&nbsp;').'</td>';
        print '<td class="right nowrap">'.price($remaintopay, 0, $langs).'</td>';
        $total_ttc += $row->total_ttc;
        $total_abono += $totalpay;
        $total_resta_pagar += $remaintopay;
        print '</tr>';
    }
}
print '<tr class="liste_titre">';
print '<td class="right" colspan="5"><b>Total</b></td>';
print '<td>'.price($total_ttc).'</td>';
print '<td>'.price($total_abono).'</td>';
print '<td>'.price($total_resta_pagar).'</td>';
print '</tr>';
print '</table>';
print '</form>';

// End of page
llxFooter();
$db->close();