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

$search_customer_code = trim(GETPOST('search_customer_code', 'alpha'));
$search_nom = trim(GETPOST("search_nom", 'none'));
$search_siren = trim(GETPOST('search_siren', 'alpha'));

$rc_status = GETPOST('rc_status', 'int') ?GETPOST('rc_status', 'int') : 1;


//Paginacion
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : "25";
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (!$sortorder) $sortorder = "desc";
if (!$sortfield) $sortfield = "total";
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha'))
	{
        $search_customer_code = '';
        $search_nom = '';
        $search_siren = '';
        $rc_status = 1;
	}

$form = new Form($db);

//COBRAR
if($rc_status == 1){
    //QUERY PRINCIPAL
$sql = "
SELECT socid, name,code_client, siren, total, noVencido, vencido, abono, AnoVencido, Avencido, (total-abono) as saldo, (noVencido-AnoVencido) as saldoNoVencido, (vencido-Avencido) as saldoVencido
,(SELECT a.datep FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action 
WHERE a.fk_soc=socid and c.type != 'systemauto' ORDER BY a.id DESC LIMIT 1) as fechapromesa
,(SELECT a.label FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action 
WHERE a.fk_soc=socid and c.type != 'systemauto' ORDER BY a.id DESC LIMIT 1) as descpromesa
FROM 
(
    SELECT socid, name, code_client, siren, SUM(total) as total, SUM(noVencido) as noVencido, SUM(vencido) as vencido, SUM(abono) as abono, SUM(AnoVencido) as AnoVencido, SUM(Avencido) as Avencido
	FROM ( 
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, SUM(f.total_ttc) AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_facture AS f 
					WHERE f.fk_statut = 1 AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 

		UNION 
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, SUM(f.total_ttc) AS noVencido, 0 AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_facture AS f 
					WHERE f.fk_statut = 1 AND f.fk_soc = s.rowid AND f.date_lim_reglement >= CURDATE() ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 
		UNION 
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, SUM(f.total_ttc) AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_facture AS f 
					WHERE f.fk_statut = 1 AND f.fk_soc = s.rowid AND f.date_lim_reglement < CURDATE() ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 
		UNION 
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, SUM(IF(f.type != 0,f.total_ttc * -1, f.total_ttc)) AS total, SUM(IF(f.type != 0,f.total_ttc * -1, f.total_ttc)) AS noVencido, 0 AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_pos_ticket AS f 
					WHERE fk_statut IN (1,2) AND f.paye = 0 AND f.fk_facture IS NULL AND (f.total_ttc != 0 OR f.fk_statut != 1) AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name )  
                    
                    
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, SUM(pf.amount) as abono, 0 AS AnoVencido, 0 AS Avencido
                                FROM llx_paiement_facture as pf,  llx_facture AS f, llx_societe AS s 
                                    WHERE f.fk_statut = 1 AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, SUM(rc.amount_ttc) as abono, 0 AS AnoVencido, 0 AS Avencido
                                FROM llx_societe_remise_except as rc, llx_facture as f, llx_societe AS s, llx_facture as f1
                                    WHERE rc.fk_facture_source=f.rowid  
										AND rc.fk_facture = f1.rowid AND f1.fk_soc = s.rowid AND f1.fk_statut = 1 
											AND f.type IN (0, 2, 3, 5) AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, SUM(pf.amount) AS AnoVencido, 0 AS Avencido
                                FROM llx_paiement_facture as pf, llx_facture AS f, llx_societe AS s 
                                    WHERE f.fk_statut = 1  AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid AND f.date_lim_reglement >= CURDATE() ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 
        UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, SUM(rc.amount_ttc) AS AnoVencido, 0 AS Avencido
                                FROM llx_societe_remise_except as rc, llx_facture as f, llx_societe AS s, llx_facture as f1 
                                    WHERE rc.fk_facture_source=f.rowid 
                                        AND rc.fk_facture = f1.rowid AND f1.fk_soc = s.rowid AND f1.fk_statut = 1 AND f1.date_lim_reglement >= CURDATE()
                                        AND f.type IN (0, 2, 3, 5) AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name )  
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, 0 AS AnoVencido, SUM(pf.amount) AS Avencido
                                FROM llx_paiement_facture as pf, llx_facture AS f, llx_societe AS s 
                                    WHERE f.fk_statut = 1 AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid AND f.date_lim_reglement < CURDATE() ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name )  
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, 0 AS AnoVencido, SUM(rc.amount_ttc) AS Avencido
                                FROM llx_societe_remise_except as rc, llx_facture as f, llx_societe AS s, llx_facture as f1 
                                    WHERE rc.fk_facture_source=f.rowid 
                                        AND rc.fk_facture = f1.rowid AND f1.fk_soc = s.rowid AND f1.fk_statut = 1 AND f1.date_lim_reglement < CURDATE()
                                        AND f.type IN (0, 2, 3, 5) AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name )  
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, s.code_client, s.siren, 0 AS total, 0 AS noVencido, 0 AS vencido, SUM(pf.amount) as abono, SUM(pf.amount) AS AnoVencido, 0 AS Avencido 
                                FROM llx_pos_paiement_ticket as pf, llx_pos_ticket AS f, llx_societe AS s
                                    WHERE pf.fk_ticket = f.rowid AND  f.fk_statut IN (1,2)  AND f.paye = 0   AND f.fk_facture IS NULL 
										AND (f.total_ttc != 0 OR f.fk_statut != 1) AND f.fk_soc = s.rowid ";
    if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
    if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
    if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
    $sql .= "                     GROUP BY name ) 
		)t_union GROUP BY name 
)t_total ";
    $sql.= 'WHERE 1=1';


}
//PAGADO
if($rc_status == 2){
    $sql= " SELECT s.rowid as socid, s.nom as name, s.code_client, s.siren, 
                IF(SUM(f.total_ttc) IS NULL, 0, SUM(f.total_ttc)) + 
                (SELECT IF(SUM(t.total_ttc) IS NULL, 0, SUM(t.total_ttc)) 
                    FROM ".MAIN_DB_PREFIX."pos_ticket AS t 
                        WHERE t.fk_statut IN (1,2) 
                            AND t.paye = 1 AND t.type = 0 
                            AND t.fk_soc = f.fk_soc
                            AND IF(t.fk_facture IS NOT NULL,(SELECT IF(ff.fk_statut=3,0,1) FROM ".MAIN_DB_PREFIX."facture AS ff WHERE ff.rowid=t.fk_facture),0) = 0
                ) AS saldo
                FROM ".MAIN_DB_PREFIX."societe AS s, ".MAIN_DB_PREFIX."facture AS f
                    WHERE f.fk_statut = 2 
                        AND f.type = 0 
                        AND f.paye = 1 
                        AND f.fk_soc = s.rowid ";

                        if ($search_nom)                $sql .= natural_search('s.nom', $search_nom);
                        if ($search_customer_code)      $sql .= natural_search('s.code_client', $search_customer_code);
                        if ($search_siren)              $sql .= natural_search('s.siren', $search_siren);
                        
$sql .= " GROUP BY s.rowid ";
}

$sql .= $db->order($sortfield, $sortorder);
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
if ($search_siren != '') $param .= '&search_siren='.urlencode($search_siren);
if ($rc_status != '') $param .= '&rc_status='.urlencode($rc_status);

/*
 * View
 */
$companystatic=new Societe($db);
llxHeader("", $langs->trans("Saldo Clientes"));


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
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="action" value="Mostrar_Productos">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
    print_barre_liste("Saldo Clientes", $page, $_SERVER["PHP_SELF"],$param , $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'building', 0, '', '', $limit);
    echo '<select name = "rc_status" onchange="this.form.submit()">';
    //echo '<option value = "0"'.(is_null($rc_status)?' selected="selected"':'').'>Todos</option>';
    echo '<option value = "1"'.(($rc_status==1)?' selected="selected"':'').'>Por Cobrar</option>';
    echo '<option value = "2"'.(($rc_status==2)?' selected="selected"':'').'>Pagado</option>';
    echo '</select>';

    print '<table class="liste">';
    print '<tr class="liste_titre">';

    print '<td class="liste_titre">';
    print '<input class="flat searchstring" type="text" name="search_nom" value="'.dol_escape_htmltag($search_nom).'">';
    print '</td>';

    print '<td class="liste_titre">';
    print '<input class="flat searchstring" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'">';
    print '</td>';

    print '<td class="liste_titre">';
    print '<input class="flat searchstring" type="text" name="search_siren" value="'.dol_escape_htmltag($search_siren).'">';
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
    print_liste_field_titre("Nombre", $_SERVER["PHP_SELF"], "name", '', $param, '', $sortfield, $sortorder, ' ');
    print_liste_field_titre("Código", $_SERVER["PHP_SELF"], "code_client", '', $param, '', $sortfield, $sortorder, 'left ');
    print_liste_field_titre("RFC", $_SERVER["PHP_SELF"], "siren", '', $param, '', $sortfield, $sortorder, 'left ');
    print '<td class="right">'.$langs->trans('Subtotal').'</td>';
    print '<td class="right">'.$langs->trans('IVA').'</td>';
    print_liste_field_titre("Total", $_SERVER["PHP_SELF"], "saldo", '', $param, '', $sortfield, $sortorder, 'right ');


    print '</tr>'."\n";
    if ($resql>0)
    {
    	$rc_sum_stot = 0;
    	$rc_sum_iva = 0;
    	$rc_sum_tot = 0;
        while ($row= $db->fetch_object($resql)){

            $sub = $row->saldo * 0.84;
            $iva = $row->saldo * 0.16;

        	$rc_sum_stot += $sub;
        	$rc_sum_iva += $iva;
        	$rc_sum_tot += $row->saldo;
            $companystatic->id = $row->socid;
            $companystatic->name = $row->name;
            print "<tr>";
            print '<td>'.$companystatic->getNomUrl(1, '', 100, 0, 1).'</td>';
            print "<td>".$row->code_client."</td>";
            print "<td>".$row->siren."</td>";
            print '<td class="right">'.number_format($sub,2)."</td>";
            print '<td class="right">'.number_format($iva,2)."</td>";
            print '<td class="right">'.number_format($row->saldo,2)."</td>";

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