<?php
/*
 * Daniel Molina
 * danmnvx@gmail.com
 */

/**
 *  \file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */
global $conf, $langs;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
$search_nom = trim(GETPOST("search_nom", 'none'));

$search_customer_code = trim(GETPOST('search_customer_code', 'alpha'));
$search_nom = trim(GETPOST("search_nom", 'none'));
$search_idprof1 = trim(GETPOST('search_idprof1', 'alpha'));
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
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : "25";
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

if(GETPOST('rc_status', 'int') == ''){
    $rc_status = 1;
}else{
    $rc_status = GETPOST('rc_status', 'int');
}

$page = GETPOST("page", 'int');
if (!$sortorder) $sortorder = "desc";
if (!$sortfield) $sortfield = "total";
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;



$sql= "select s.rowid,s.entity,s.status,s.fournisseur,s.client,s.nom, s.code_fournisseur, s.siren, "
	 ."sum(f.total_ht) as subtotal, sum(f.total_tva) as importe_iva ,sum(f.total_ttc) as total /*,  "
	 ."sum(pa.amount) as pago */ "
	 ."from llx_facture_fourn as f  "
	 ."join llx_societe as s  "
	 ."on f.fk_soc=s.rowid  "
	 ."/* left join llx_paiementfourn_facturefourn as pa  "
	 ."on f.rowid = pa.fk_paiementfourn */ "
	 ."where f.entity";
$sql .= ($rc_status != 0) ? ' AND f.fk_statut =  '.$rc_status:'';

if($date_start_factureyear && !$date_end_factureyear)
    $sql.=" AND f.datef >= '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."'";
else if (!$date_start_factureyear && $date_end_factureyear)
    $sql.=" AND f.datef <= '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";
else if ($date_start_factureyear && $date_end_factureyear)
    $sql.=" AND f.datef BETWEEN '".$date_start_factureyear."-".$date_start_facturemonth."-".$date_start_factureday."' AND '".$date_end_factureyear."-".$date_end_facturemonth."-".$date_end_factureday."'";

if ($search_nom)           $sql.= natural_search("nom", $search_nom);
if ($search_customer_code) $sql.= natural_search("code_fournisseur", $search_customer_code);
if (strlen($search_idprof1)) $sql.= natural_search("s.siren", $search_idprof1);
//$sql.= " AND s.client = 1";
$sql.= " group by f.fk_soc";
if(!empty($sortfield)){
    $sql.= " ORDER BY ".$sortfield. " $sortorder";
}


// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql)
{
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);
$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($search_nom != '')     $param .= "&search_nom=".urlencode($search_nom);
if ($search_customer_code != '') $param .= "&search_customer_code=".urlencode($search_customer_code);
if ($search_idprof1 != '') $param .= '&search_idprof1='.urlencode($search_idprof1);
if ($rc_status != '') $param .= '&rc_status='.urlencode($rc_status);
	if ($date_start_factureday)         $param .= '&date_start_factureday='.urlencode($date_start_factureday);
    if ($date_start_facturemonth)       $param .= '&date_start_facturemonth='.urlencode($date_start_facturemonth);
    if ($date_start_factureyear)        $param .= '&date_start_factureyear='.urlencode($date_start_factureyear);
    if ($date_end_factureday)         $param .= '&date_end_factureday='.urlencode($date_end_factureday);
    if ($date_end_facturemonth)       $param .= '&date_end_facturemonth='.urlencode($date_end_facturemonth);
    if ($date_end_factureyear)        $param .= '&date_end_factureyear='.urlencode($date_end_factureyear);



/*
 * View
 */
$companystatic=new Societe($db);
llxHeader("", $langs->trans("Saldo Proveedores"));



$formcompany=new FormCompany($db);
/*
 * View
 */
    //TABLA DE PRODUCTOS
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    if(!empty($orden))$orden=$orden;
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$orden.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="action" value="Mostrar_Productos">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print_barre_liste("Saldo Proveedores", $page, $_SERVER["PHP_SELF"],$param , $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'building', 0, '', '', $limit);

echo '<select name = "rc_status" onchange="this.form.submit()">';
echo '<option value = "0"'.(($rc_status==0)?' selected="selected"':'').'>Todos</option>';
echo '<option value = "1"'.(($rc_status==1 || ($rc_status == ''))?' selected="selected"':'').'>Por Pagar</option>';
echo '<option value = "2"'.(($rc_status==2)?' selected="selected"':'').'>Pagado</option>';
echo '</select>';
        print " &nbsp;&nbsp;&nbsp;&nbsp;Desde: "
			 .$form->select_date($date_start_facture,'date_start_facture',0,0,0,'',1,0,1)
			 ."Hasta: "
			 .$form->select_date($date_end_facture,'date_end_facture',0,0,0,'',1,0,1);



    print '<table class="liste">';
    print '<tr class="liste_titre">';

    print '<td class="liste_titre">';
    print '<input class="flat searchstring maxwidth100imp" type="text" name="search_nom" value="'.dol_escape_htmltag($search_nom).'">';
    print '</td>';

    print '<td class="liste_titre">';
    print '<input class="flat searchstring" style="max-width:64px;" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'">';
    print '</td>';

    print '<td class="liste_titre">';
    print '<input class="flat searchstring maxwidth50imp" type="text" name="search_idprof1" value="'.dol_escape_htmltag($search_idprof1).'">';
    print '</td>';

    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre"></td>';
// Action column
    print '<td class="liste_titre center">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
    print '</tr>';

    print '<tr class="liste_titre">';
    //print '<td class="left" style="width: 40%;">'.$langs->trans('Nombre').'</td>';
    print_liste_field_titre("Nombre", $_SERVER["PHP_SELF"], "nom", '', $param, '', $sortfield, $sortorder, ' ');
    
    print '<td class="left">'.$langs->trans('Codigo').'</td>';
    print '<td class="left">'.$langs->trans('RFC').'</td>';
    print '<td class="right">'.$langs->trans('Subtotal').'</td>';
    print_liste_field_titre("Importe Iva", $_SERVER["PHP_SELF"], "importe_iva", '', $param, '', $sortfield, $sortorder, 'right ');
    print_liste_field_titre("Total", $_SERVER["PHP_SELF"], "total", '', $param, '', $sortfield, $sortorder, 'right ');


    print '</tr>'."\n";
    if ($resql>0)
    {
    	$rc_sum_stot = 0;
    	$rc_sum_iva = 0;
    	$rc_sum_tot = 0;
        while ($row= $db->fetch_object($resql)){
        	$rc_sum_stot += $row->subtotal;
        	$rc_sum_iva += $row->importe_iva;
        	$rc_sum_tot += $row->total;
            $companystatic->id = $row->rowid;
            $companystatic->name = $row->nom;
            $companystatic->entity = $row->entity;
            $companystatic->client = $row->client;
            $companystatic->status = $row->status;
            $companystatic->fournisseur = $row->fournisseur;
            print "<tr>";
            print "<td>".$companystatic->getNomUrl(1, '', 100, 0, 1)."</td>";
            print "<td>".$row->code_fournisseur."</td>";
            print "<td>".$row->siren."</td>";
            if($row->subtotal>0){
                print '<td class="right">'.number_format($row->subtotal,2)."</td>";
            }else{
                print '<td class="right"> </td>';
            }
            if($row->importe_iva>0){
                print '<td class="right">'.number_format($row->importe_iva,2)."</td>";
            }else{
                print '<td class="right"> </td>';
            }
            if($row->total>0){
                print '<td class="right">'.number_format($row->total,2)."</td>";
            }else{
                print '<td class="right"> </td>';
            }


            print "</tr>";
        }
        echo '<tr class="liste_titre">';
        echo "<td colspan=\"3\" class=\"right\" style=\"font-weight:bold;\">TOTALES</td>";
        echo "<td class=\"right\">".number_format($rc_sum_stot,2,'.',',')."</td>";
        echo "<td class=\"right\">".number_format($rc_sum_iva,2,'.',',')."</td>";
        echo "<td class=\"right\" style=\"font-weight:bold;\">".number_format($rc_sum_tot,2,'.',',')."</td>";
        echo '</tr>';

    }
    print '</table>';
    print '</form>';

// End of page
llxFooter();
$db->close();