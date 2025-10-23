<?php

global $conf, $langs;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';

//Busquedas por filtro
$search_ref = GETPOST('search_ref');
$search_soc = GETPOST('search_soc');
$search_autor = GETPOST('search_autor');
$search_tipo = GETPOST('search_tipo');
$search_start_day = GETPOST('search_start_day');
$search_start_month = GETPOST('search_start_month');
$search_start_year = GETPOST('search_start_year');
$search_end_day = GETPOST('search_end_day');
$search_end_month = GETPOST('search_end_month');
$search_end_year = GETPOST('search_end_year');

if($search_start_year){
    $search_start=dol_mktime(0,0,0,$search_start_month,$search_start_day,$search_start_year);
}else{
    $search_start=null;
}
if($search_end_year){
    $search_end=dol_mktime(0,0,0,$search_end_month,$search_end_day,$search_end_year);
}else{
    $search_end=null;
}

//Paginacion
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : "25";
$rc_status = GETPOST('rc_status', 'int') ?GETPOST('rc_status', 'int') : null;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (!$sortorder) $sortorder = "desc";
if (!$sortfield) $sortfield = "date";
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_ref = '';
    $search_soc = '';
    $search_autor = '';
    $search_tipo = '';
    $search_start = null;
    $search_end = null;
    $search_start_day = null;
    $search_start_month = null;
    $search_start_year = null;
    $search_end_day = null;
    $search_end_month = null;
    $search_end_year = null;
}


$form = new Form($db);
$userstatic = new User($db);
$companystatic=new Societe($db);
$facture = new Facture($db);
$ticket = new Ticket($db);

$sql= "SELECT id, ref, date, cliente, status, subtotal, total, autor, tipo
        FROM (
                SELECT f.rowid AS id, f.ref AS ref, s.nom as name, f.datef AS date, f.fk_soc AS cliente, f.fk_statut AS status, f.total AS subtotal, f.total_ttc AS total, f.fk_user_author AS autor, 'Factura' as tipo
                    FROM ".MAIN_DB_PREFIX."facture AS f
                        LEFT JOIN llx_societe AS s ON f.fk_soc=s.rowid
                        WHERE f.fk_statut IN (1,2)
                            AND f.rowid NOT IN (SELECT fk_source FROM ".MAIN_DB_PREFIX."element_element WHERE sourcetype = 'facture' and targettype = 'ticket') 
                            AND f.rowid NOT IN (SELECT fk_target FROM ".MAIN_DB_PREFIX."element_element WHERE targettype = 'facture' and sourcetype = 'ticket')
                            AND f.fk_projet is null
                UNION
                SELECT f.rowid AS id, f.ticketnumber AS ref, s.nom as name, f.date_creation AS date, f.fk_soc AS cliente, f.fk_statut AS status, f.total_ht AS subtotal, f.total_ttc as total, f.fk_user_author AS autor, 'Ticket' as tipo
                    FROM ".MAIN_DB_PREFIX."pos_ticket AS f
                        LEFT JOIN llx_societe AS s ON f.fk_soc=s.rowid
                        WHERE f.fk_statut IN (1,2)
                            AND f.fk_projet is null
            )ventas
                WHERE 1 = 1";
	 
                if($search_start_year && !$search_end_year){
                    $sql.=" AND date >= '".$search_start_year."-".$search_start_month."-".$search_start_day." 00:00:00'";
                }else if (!$search_start_year && $search_end_year){
                    $sql.=" AND date <= '".$search_end_year."-".$search_end_month."-".$search_end_day." 23:59:59'";
                }else if ($search_start_year && $search_end_year){
                    $sql.=" AND date BETWEEN '".$search_start_year."-".$search_start_month."-".$search_start_day." 00:00:00' AND '".$search_end_year."-".$search_end_month."-".$search_end_day." 23:59:59'";
                }
                if ($search_ref)           $sql.= natural_search("ref", $search_ref);
                if ($search_tipo)           $sql.= natural_search("tipo", $search_tipo);
                if ($search_soc)           $sql.= natural_search("name", $search_soc);
                if ($search_autor > 0)           $sql.= " AND autor = ".$search_autor;

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

if ($search_ref != '')     $param .= "&search_ref=".urlencode($search_ref);
if ($search_soc != '') $param .= "&search_soc=".urlencode($search_soc);
if ($search_autor != '') $param .= '&search_autor='.urlencode($search_autor);
if ($search_tipo != '') $param .= '&search_tipo='.urlencode($search_tipo);
if ($search_start_day)         $param .= '&search_start_day='.urlencode($search_start_day);
if ($search_start_month)       $param .= '&search_start_month='.urlencode($search_start_month);
if ($search_start_year)        $param .= '&search_start_year='.urlencode($search_start_year);
if ($search_end_day)         $param .= '&search_end_day='.urlencode($search_end_day);
if ($search_end_month)       $param .= '&search_end_month='.urlencode($search_end_month);
if ($search_end_year)        $param .= '&search_end_year='.urlencode($search_end_year);


/*
 * View
 */
llxHeader("", $langs->trans("Reporte de Ventas"));

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
    //Conteo de ventas
    print_barre_liste("Reporte de Ventas", $page, $_SERVER["PHP_SELF"],$param , $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'building', 0, '', '', $limit);
    
    print '<table class="liste">';
        print '<tr class="liste_titre">';
            //FILTRO REF
            print '<td class="liste_titre">';
                print '<input class="flat searchstring maxwidth100imp" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
            print '</td>';
            //FILTRO TIPO
            print '<td class="liste_titre">';
                print '<select name = "search_tipo">';
                    print '<option value = "0"'.(is_null($search_tipo)?' selected="selected"':'').'>Todos</option>';
                    print '<option value = "Factura"'.(($search_tipo=="Factura")?' selected="selected"':'').'>Factura</option>';
                    print '<option value = "Ticket"'.(($search_tipo=="Ticket")?' selected="selected"':'').'>Ticket</option>';
                print '</select>';
            print '</td>';
            //FILTRO CLIENTE
            print '<td class="liste_titre">';
                print '<input class="flat searchstring maxwidth100imp" type="text" name="search_soc" value="'.dol_escape_htmltag($search_soc).'">';
            print '</td>';
            //FILTRO FECHA
            print '<td class="liste_titre">';
                print "Desde: ".$form->select_date($search_start,'search_start_',0,0,0,'',1,0,1);
                print '<br>';
                print "Hasta: ".$form->select_date($search_end,'search_end_',0,0,0,'',1,0,1);
            print '</td>';
            //FILTRO USUARIO
            print '<td class="liste_titre">';
                print $form->select_dolusers($search_autor, 'search_autor', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
            print '</td>';
            print '<td colspan="2"></td>';
            // Action column
            print '<td class="liste_titre center">';
                $searchpicto = $form->showFilterButtons();
                print $searchpicto;
            print '</td>';
            print '<td></td>';
        print '</tr>';

    print '<tr class="liste_titre">';
    print_liste_field_titre("Ref.", $_SERVER["PHP_SELF"], "ref", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Tipo", $_SERVER["PHP_SELF"], "tipo", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Cliente", $_SERVER["PHP_SELF"], "cliente", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Fecha", $_SERVER["PHP_SELF"], "date", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Usuario", $_SERVER["PHP_SELF"], "autor", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Subtotal", $_SERVER["PHP_SELF"], "subtotal", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Total", $_SERVER["PHP_SELF"], "total", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Margen de Ganancia", $_SERVER["PHP_SELF"], "margen_ganancia", '', $param, 'colspan="2" align="center"', $sortfield, $sortorder, ' ');
    print '</tr>'."\n";
    if ($resql>0)
    {
    	$rc_sum_tot = 0;
        $rc_sum_sub = 0;
        while ($row= $db->fetch_object($resql)){
        	$rc_sum_tot += $row->total;
            $rc_sum_sub += $row->subtotal;
            print "<tr>";
                print "<td>";
                if($row->tipo == 'Factura'){
                    $facture->fetch($row->id);
                    print $facture->getNomUrl(1, '', 200, 0, '', 0, 1);
                }else{
                    $ticket->fetch($row->id);
                    print $ticket->getNomUrl(1, '', 200);
                }
                print "</td>";
                print "<td>".$row->tipo."</td>";
                $companystatic->fetch($row->cliente);
                print '<td style="max-width: 20%;">'.$companystatic->getNomUrl(1, 'customer', 100, 0, 1).'</td>';

                print "<td>".dol_print_date($db->jdate($row->date), 'day')."</td>";

                $userstatic->fetch($row->autor);
                print "<td>".$userstatic->getNomUrl(1)."</td>";

                print "<td>$".price($row->subtotal)."</td>";
                print "<td>$".price($row->total)."</td>";

                //Margen de Ganancia
                print "<td colspan='2' class=\"center\">";

                $facturestatic = new Ticket($db);
                $facturestatic->fetch($row->id);

                $formmargin = new FormMargin($db);

                if($row->tipo == 'Ticket'){
                    //Validacion precio de compra por producto antes de la actualizacion
                    $banPa = true;
                    $sqlPa = "SELECT buy_price_ht FROM llx_pos_ticketdet WHERE fk_ticket = ".$row->id;
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
                        print (($margen['total_mark_rate'] == '') ? '' : $margin.'%');
                        $res_margin += $margin;
                    }else{
                        $margin = 0;
                        $res_margin += $margin;
                    }
                }else{
                    $facturestatic->fetch_lines();
					$margen = $formmargin->getMarginInfosArray($facturestatic);
					$margin = price($margen['total_mark_rate'], null, null, null, null, 2);
					print (($margen['total_mark_rate'] == '') ? '' : $margin.'%');
					$res_margin += $margin;
                }

				$count_margin += 1;
                print "</td>";
            print "</tr>";
        }
        echo '<tr class="liste_titre">';
        echo "<td colspan=\"5\" class=\"right\" style=\"font-weight:bold;\">TOTALES</td>";
        echo "<td style=\"font-weight:bold;\">$".number_format($rc_sum_sub,2,'.',',')."</td>";
        echo "<td style=\"font-weight:bold;\">$".number_format($rc_sum_tot,2,'.',',')."</td>";
        
        //Margen de Ganancia
        echo "<td style=\"font-weight:bold;\" colspan=\"2\" class=\"center\">";
        echo price($res_margin / $count_margin)."%";
        echo "</td> </tr>";

    }
    print '</table>';
    print '</form>';

// End of page
llxFooter();
$db->close();