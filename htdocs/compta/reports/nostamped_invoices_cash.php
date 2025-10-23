<?php

/*
 * Juan Pablo Merla De Lara
 */
global $conf, $langs;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
dol_include_once('/pos/class/ticket.class.php');
require_once(DOL_DOCUMENT_ROOT ."/compta/facture/class/facture.class.php");

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

//FACTURAS
$sql = "SELECT f.rowid as facid, f.ref, f.date_valid, f.total_ttc, s.rowid, s.nom, f.type";
$sql.= ", s.entity, s.client, s.status, s.fournisseur, 'facture' as objeto";
$sql.= " FROM llx_facture as f";
$sql.= " JOIN llx_societe as s ON s.rowid = f.fk_soc";
$sql.= " WHERE f.fk_mode_reglement = 4 AND f.fk_statut > 0";
$sql.= " AND (SELECT cd.factura_id FROM llx_cfdimx as cd where cd.fk_facture = f.rowid) is null";

if(!empty($search_fac))
{
    $sql .= " AND f.ref like '%".$search_fac."%'";
}
if(!empty($search_soc))
{
    $sql .= " AND s.nom like '%".$search_soc."%'";
}
if($date_start_factureyear && !$date_end_factureyear)
    $sql.=" AND f.date_valid >= '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."'";
else if (!$date_start_factureyear && $date_end_factureyear)
    $sql.=" AND f.date_valid <= '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
else if ($date_start_factureyear && $date_end_factureyear)
    $sql.=" AND f.date_valid BETWEEN '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."' AND '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";

//TICKET
$sql2 = "  SELECT t.rowid as facid, t.ticketnumber as ref, t.date_ticket as date_valid, t.total_ttc, s.rowid, s.nom, t.type,"; 
$sql2.= " s.entity, s.client, s.status, s.fournisseur, 'Ticket' as objeto";
$sql2.= " FROM llx_pos_ticket as t";
$sql2.= " LEFT JOIN llx_societe as s ON s.rowid = t.fk_soc";
$sql2.= " WHERE t.fk_mode_reglement = 4 AND t.fk_statut > 0 AND t.fk_facture is null";

if(!empty($search_fac))
{
    $sql2 .= " AND t.ticketnumber like '%".$search_fac."%'";
}
if(!empty($search_soc))
{
    $sql2 .= " AND s.nom like '%".$search_soc."%'";
}
if($date_start_factureyear && !$date_end_factureyear)
    $sql2.=" AND t.date_ticket >= '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."'";
else if (!$date_start_factureyear && $date_end_factureyear)
    $sql2.=" AND t.date_ticket <= '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
else if ($date_start_factureyear && $date_end_factureyear)
    $sql2.=" AND t.date_ticket BETWEEN '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."' AND '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";

$sql = "(".$sql.") UNION (".$sql2.") ORDER BY date_valid";
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
llxHeader("", $langs->trans("Ventas Efectivo NF"));

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
print_barre_liste("Ventas Efectivo NF", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'building', 0, '', '', $limit);

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
// Action column
print '<td class="liste_titre center">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

print '<tr class="liste_titre">';

print_liste_field_titre("Factura", $_SERVER["PHP_SELF"], "ref", '', $param, '', $sortfield, $sortorder, ' ');
print_liste_field_titre("Cliente", $_SERVER["PHP_SELF"], "nom", '', $param, '', $sortfield, $sortorder, ' ');
print '<td class="left">' . $langs->trans('Fecha de Validación') . '</td>';
print '<td class="left">' . $langs->trans('Tipo') . '</td>';
print '<td class="right">' . $langs->trans('Importe Total') . '</td>';
print '<td class="right">' . $langs->trans('Abono') . '</td>';
print '<td class="right">' . $langs->trans('Saldo') . '</td>';


print '</tr>' . "\n";
$ticketstatic=new Ticket($db);
$facturestatic=new Facture($db);
if ($resql > 0) {
    $total_ttc = 0;
    $total_pay = 0;
    $total_restpay = 0;
    while ($row= $db->fetch_object($resql)) {
        print '<tr>';
        if($row->objeto == 'Ticket') {
            $ticketstatic->id = $row->facid;
            $ticketstatic->ref = $row->ref;
            $paiement = $ticketstatic->getSommePaiement();
            print "<td>&nbsp;<a href='".DOL_MAIN_URL_ROOT."/custom/pos/backend/ticket.php?id=".$row->facid."'>".$row->ref."</a></td>";
            $objttc = $row->total_ttc;
            $objrest_pay = ( $paiement > 0 ? $objttc - $paiement : $objttc);
        }else{
            $facturestatic->id = $row->facid;
            $facturestatic->ref = $row->ref;
            $paiement = $facturestatic->getSommePaiement();
            print "<td>&nbsp;<a href='".DOL_MAIN_URL_ROOT."/compta/facture/card.php?facid=".$row->facid."'>".$row->ref."</a></td>";
            $objttc = $row->total_ttc;
            $objrest_pay = ( $paiement > 0 ? $objttc - $paiement : $objttc);
        }
        $companystatic->id = $row->rowid;
        $companystatic->name = $row->nom;
        $companystatic->entity = $row->entity;
        $companystatic->client = $row->client;
        $companystatic->status = $row->status;
        $companystatic->fournisseur = $row->fournisseur;
        print '<td>' . $companystatic->getNomUrl(1, '', 100, 0, 1) . '</td>';
        print '<td>' . $row->date_valid . '</td>';
        $tipo_doc = "";
        if($row->objeto == 'facture'){
            if($row->type==2){
                $tipo_doc="Nota de Credito";
            }else{
                $tipo_doc="Factura Estandar";
            }
        }else{
            $tipo_doc=$row->objeto;
        }
        if ($objrest_pay >= -0.019 && $objrest_pay <= 0.011){
            $objrest_pay = 0;
        }
        print '<td>' . $tipo_doc . '</td>';
        print '<td>' . price($objttc) . '</td>';
        print '<td>' . price($paiement) . '</td>';
        print '<td>' . price($objrest_pay) . '</td>';
        $total_ttc += $row->total_ttc;
        $total_pay += $paiement;
        $total_restpay += $objrest_pay;
        print '</tr>';
    }
}
print '<tr class="liste_titre">';
print '<td class="right" colspan="4"><b>Total</b></td>';
print '<td>'.price($total_ttc).'</td>';
print '<td>'.price($total_pay).'</td>';
print '<td>'.price($total_restpay).'</td>';
print '</tr>';
print '</table>';
print '</form>';

// End of page
llxFooter();
$db->close();