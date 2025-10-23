<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'compta', 'companies'));

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$date_end_limitmonth=GETPOST("date_end_limitmonth");
$date_end_limitday=GETPOST("date_end_limitday");
$date_end_limityear=GETPOST("date_end_limityear");
$facid	= GETPOST('facid', 'int');
$socid	= GETPOST('socid', 'int');
$option = GETPOST('option');

// Security check
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'facture', $facid, '');

$companystatic=new Societe($db);

$search_amount=GETPOST("search_amount", 'alpha');    // alpha because we must be able to search on "< x"
$search_company=GETPOST("search_company", 'alpha');

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "name";

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentlist'));
$extrafields = new ExtraFields($db);

//$arrayfields = array();
$arrayfields = array(
    'saldoVencido'=>array('label'=>"Saldo Vencido", 'checked'=>0, 'position'=>5),
	'saldoNoVencido'=>array('label'=>"Saldo No Vencido", 'checked'=>0, 'position'=>10),
	'fechapromesa'=>array('label'=>"Fecha Promesa", 'checked'=>0, 'position'=>15)
);
/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_company = "";
    $date_end_limitmonth=null;
    $date_end_limitday=null;
    $date_end_limityear=null;
    $option=null;
}

/*
 * 	View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader('', $langs->trans('ListPayment'));

//QUERY PRINCIPAL
$sql = "
SELECT socid, name, total, noVencido, vencido, abono, AnoVencido, Avencido, (total-abono) as saldo, (noVencido-AnoVencido) as saldoNoVencido, (vencido-Avencido) as saldoVencido
,(SELECT a.datep FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action 
WHERE a.fk_soc=socid and c.type != 'systemauto' ORDER BY a.id DESC LIMIT 1) as fechapromesa
,(SELECT a.label FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action 
WHERE a.fk_soc=socid and c.type != 'systemauto' ORDER BY a.id DESC LIMIT 1) as descpromesa
FROM 
(
    SELECT socid, name, SUM(total) as total, SUM(noVencido) as noVencido, SUM(vencido) as vencido, SUM(abono) as abono, SUM(AnoVencido) as AnoVencido, SUM(Avencido) as Avencido
	FROM ( 
			(SELECT s.rowid AS socid, s.nom AS name, SUM(f.total_ttc) AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_facture AS f 
					WHERE f.fk_statut = 1 AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 

		UNION 
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, SUM(f.total_ttc) AS noVencido, 0 AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_facture AS f 
					WHERE f.fk_statut = 1 AND f.fk_soc = s.rowid AND f.date_lim_reglement >= CURDATE() ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 
		UNION 
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, SUM(f.total_ttc) AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_facture AS f 
					WHERE f.fk_statut = 1 AND f.fk_soc = s.rowid AND f.date_lim_reglement < CURDATE() ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 
		UNION 
			(SELECT s.rowid AS socid, s.nom AS name, SUM(IF(f.type != 0,f.total_ttc * -1, f.total_ttc)) AS total, SUM(IF(f.type != 0,f.total_ttc * -1, f.total_ttc)) AS noVencido, 0 AS vencido, 0 as abono, 0 as AnoVencido, 0 as Avencido
				FROM llx_societe AS s, llx_pos_ticket AS f 
					WHERE fk_statut IN (1,2) AND f.paye = 0 AND f.fk_facture IS NULL AND (f.total_ttc != 0 OR f.fk_statut != 1) AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name )  
                    
                    
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, SUM(pf.amount) as abono, 0 AS AnoVencido, 0 AS Avencido
                                FROM llx_paiement_facture as pf,  llx_facture AS f, llx_societe AS s 
                                    WHERE f.fk_statut = 1 AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, SUM(rc.amount_ttc) as abono, 0 AS AnoVencido, 0 AS Avencido
                                FROM llx_societe_remise_except as rc, llx_facture as f, llx_societe AS s, llx_facture as f1
                                    WHERE rc.fk_facture_source=f.rowid  
										AND rc.fk_facture = f1.rowid AND f1.fk_soc = s.rowid AND f1.fk_statut = 1 
											AND f.type IN (0, 2, 3, 5) AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, SUM(pf.amount) AS AnoVencido, 0 AS Avencido
                                FROM llx_paiement_facture as pf, llx_facture AS f, llx_societe AS s 
                                    WHERE f.fk_statut = 1  AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid AND f.date_lim_reglement >= CURDATE() ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 
        UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, SUM(rc.amount_ttc) AS AnoVencido, 0 AS Avencido
                                FROM llx_societe_remise_except as rc, llx_facture as f, llx_societe AS s, llx_facture as f1 
                                    WHERE rc.fk_facture_source=f.rowid 
                                        AND rc.fk_facture = f1.rowid AND f1.fk_soc = s.rowid AND f1.fk_statut = 1 AND f1.date_lim_reglement >= CURDATE()
                                        AND f.type IN (0, 2, 3, 5) AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name )  
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, 0 AS AnoVencido, SUM(pf.amount) AS Avencido
                                FROM llx_paiement_facture as pf, llx_facture AS f, llx_societe AS s 
                                    WHERE f.fk_statut = 1 AND pf.fk_facture = f.rowid AND f.fk_soc = s.rowid AND f.date_lim_reglement < CURDATE() ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name )  
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, 0 as abono, 0 AS AnoVencido, SUM(rc.amount_ttc) AS Avencido
                                FROM llx_societe_remise_except as rc, llx_facture as f, llx_societe AS s, llx_facture as f1 
                                    WHERE rc.fk_facture_source=f.rowid 
                                        AND rc.fk_facture = f1.rowid AND f1.fk_soc = s.rowid AND f1.fk_statut = 1 AND f1.date_lim_reglement < CURDATE()
                                        AND f.type IN (0, 2, 3, 5) AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name )  
		UNION
			(SELECT s.rowid AS socid, s.nom AS name, 0 AS total, 0 AS noVencido, 0 AS vencido, SUM(pf.amount) as abono, SUM(pf.amount) AS AnoVencido, 0 AS Avencido 
                                FROM llx_pos_paiement_ticket as pf, llx_pos_ticket AS f, llx_societe AS s
                                    WHERE pf.fk_ticket = f.rowid AND  f.fk_statut IN (1,2)  AND f.paye = 0   AND f.fk_facture IS NULL 
										AND (f.total_ttc != 0 OR f.fk_statut != 1) AND f.fk_soc = s.rowid ";
    if ($search_company)    $sql .= natural_search('s.nom', $search_company);
    $sql .= "                     GROUP BY name ) 
		)t_union GROUP BY name 
)t_total ";
    $sql.= 'WHERE 1=1';
if ($date_end_limityear) {
    $sql .= " AND DATE((SELECT a.datep FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action
WHERE a.fk_soc=socid and c.type != 'systemauto' ORDER BY a.id DESC LIMIT 1)) = '".$date_end_limityear."-".$date_end_limitmonth."-".$date_end_limitday."'";
}
if($option == 'late'){
    $sql .= " AND (SELECT a.datep FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action
WHERE a.fk_soc=socid and c.type != 'systemauto' ORDER BY a.id DESC LIMIT 1) < NOW()";
}
$sql .= $db->order($sortfield, $sortorder);

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
if ($resql)
{
    $num = $db->num_rows($resql);

    $param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
    $param .= (GETPOST("orphelins") ? "&orphelins=1" : "");

    $param .= ($search_company ? "&search_company=".urlencode($search_company) : "");
    $param .= ($search_amount ? "&search_amount=".urlencode($search_amount) : "");
    
    if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

    print_barre_liste("Cobranza por Cliente", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'invoicing', 0, '', '', $limit);

    
    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

    print '<div class="div-table-responsive">';
    print '<table class="table tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";
    // Lines for filters fields
    print '<tr class="liste_titre_filter">';

    if (!empty($arrayfields['saldoVencido']['checked']) && !empty($arrayfields['saldoNoVencido']['checked'])){
        $colspan = 7;
    }
    if (!empty($arrayfields['saldoVencido']['checked']) && empty($arrayfields['saldoNoVencido']['checked'])){
        $colspan = 6;
    }
    if (empty($arrayfields['saldoVencido']['checked']) && !empty($arrayfields['saldoNoVencido']['checked'])){
        $colspan = 6;
    }
    if (empty($arrayfields['saldoVencido']['checked']) && empty($arrayfields['saldoNoVencido']['checked'])){
        $colspan = 5;
    }
    if (!empty($arrayfields['fechapromesa']['checked'])){
        $other = $colspan -1;
        $colspan = 1;
    }

    //BUSCADOR CLIENTE
    print '<td class="liste_titre" colspan="'.$colspan.'">';
    print '<input class="flat" type="text" size="36" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
    print '</td>';
    if (!empty($arrayfields['fechapromesa']['checked'])){
        print '<td class="liste_titre" align="right">';
        if($date_end_limityear)$date_end_limit=dol_mktime(0,0,0,$date_end_limitmonth,$date_end_limitday,$date_end_limityear);
        else $date_end_limit=null;
        print $form->select_date($date_end_limit,'date_end_limit',0,0,0,'',1,0,1);
        print '<br><input type="checkbox" name="option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
        //print '<input class="flat" type="text" size="36" name="desde" value="">';
        print '</td>';
        print '<td class="liste_titre" colspan="'.$other.'">&nbsp;';
        //print '<input class="flat" type="text" size="36" name="desde" value="">';
        print '</td>';
    }

    print '<td class="liste_titre maxwidthsearch">';
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';

    print "</tr>\n";

    print '<thead><tr class="liste_titre">';

    print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "name", "", $param, "", $sortfield, $sortorder);
    if (!empty($arrayfields['fechapromesa']['checked']))
        print_liste_field_titre($arrayfields['fechapromesa']['label'], $_SERVER["PHP_SELF"], "fechapromesa", "", $param, 'class="right"', $sortfield, $sortorder);
    print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "total", "", $param, 'class="right"', $sortfield, $sortorder);
    print_liste_field_titre("Total Vencido", $_SERVER["PHP_SELF"], "vencido", "", $param, 'class="right"', $sortfield, $sortorder);
    //print_liste_field_titre("Total No Vencido", $_SERVER["PHP_SELF"], "noVencido", "", $param, 'class="right"', $sortfield, $sortorder);
    print_liste_field_titre("Abono", $_SERVER["PHP_SELF"], "abono", "", $param, 'class="right"', $sortfield, $sortorder);
    //print_liste_field_titre("Abono Vencido", $_SERVER["PHP_SELF"], "Avencido", "", $param, 'class="right"', $sortfield, $sortorder);
    //print_liste_field_titre("Abono No Vencido", $_SERVER["PHP_SELF"], "AnoVencido", "", $param, 'class="right"', $sortfield, $sortorder);    
    print_liste_field_titre("Saldo", $_SERVER["PHP_SELF"], "saldo", "", $param, 'class="right"', $sortfield, $sortorder);

    if (!empty($arrayfields['saldoVencido']['checked']))
        print_liste_field_titre("Saldo Vencido", $_SERVER["PHP_SELF"], "saldoVencido", "", $param, 'class="right"', $sortfield, $sortorder);

    if (!empty($arrayfields['saldoNoVencido']['checked']))
        print_liste_field_titre("Saldo No Vencido", $_SERVER["PHP_SELF"], "saldoNoVencido", "", $param, 'class="right"', $sortfield, $sortorder);

    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');

	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print "</tr></thead>";

    $i = 0;
    $totalarray = array();
    print "<tbody>";
    while ($i < min($num, $limit))
    {        
        $objp = $db->fetch_object($resql);

        $companystatic->id = $objp->socid;
        $companystatic->name = $objp->name;

        print '<tr class="oddeven"';
        $banFila = false;
        if ($objp->saldo >= -0.019 && $objp->saldo <= 0.011){
            $banFila = true;
        }
        if($banFila){
            print ' style="display:none;" >';
        }else{
            print '>';
        }

        // Thirdparty
        print '<td>';
        if ($objp->socid > 0)
        {
            print $companystatic->getNomUrl(1, '', 200);
        }
        print '</td>';

        if (!empty($arrayfields['fechapromesa']['checked'])){
            //$link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
            print '<td class="right">';
            print dol_print_date($db->jdate($objp->fechapromesa), 'dayhour');
            if ($db->jdate($objp->fechapromesa) < dol_now() && $db->jdate($objp->fechapromesa) != '')
            {
                print img_warning($langs->trans('Alert').' - '.$langs->trans('LateList'));
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }

        // Importe Total
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
        print '<td class="right"><a href="'.$link.'">'.price($objp->total).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'total';
        if(!$banFila)
            $totalarray['val']['total'] += $objp->total;

        // Importe Vencido
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1&option=late';
        print '<td class="right"><a href="'.$link.'">'.price($objp->vencido).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'vencido';
        if(!$banFila)
            $totalarray['val']['vencido'] += $objp->vencido;

        /*
        // Importe No Vencido
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
        print '<td class="right"><a href="'.$link.'">'.price($objp->noVencido).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'noVencido';
		$totalarray['val']['noVencido'] += $objp->noVencido;
        */
        
        //Abono
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
        print '<td class="right"><a href="'.$link.'">'.price($objp->abono).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'abono';
        if(!$banFila)
            $totalarray['val']['abono'] += $objp->abono;

        /*
        // Abono Vencido
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1&option=late';
        print '<td class="right"><a href="'.$link.'">'.price($objp1->vencido).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'Avencido';
		$totalarray['val']['Avencido'] += $objp1->vencido;

        // Abono No Vencido
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
        print '<td class="right"><a href="'.$link.'">'.price($objp1->noVencido).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'AnoVencido';
		$totalarray['val']['AnoVencido'] += $objp1->noVencido;
        */

        // Saldo Total
        $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
        print '<td class="right"><a href="'.$link.'">'.price($objp->saldo).'</a></td>';
        if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'saldo';
        if(!$banFila)
            $totalarray['val']['saldo'] += ($objp->saldo);

        //Saldo Vencido
        if (!empty($arrayfields['saldoVencido']['checked'])){
            $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1&option=late';
            print '<td class="right"><a href="'.$link.'">'.price($objp->saldoVencido).'</a></td>';
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'saldoVencido';
            if(!$banFila)
                $totalarray['val']['saldoVencido'] += ($objp->saldoVencido);
        }

        //Saldo No Vencido
        if (!empty($arrayfields['saldoNoVencido']['checked'])){
            $link = DOL_URL_ROOT.'/compta/facture/list.php?socid='.$objp->socid.'&search_status=1';
            print '<td class="right"><a href="'.$link.'">'.price($objp->saldoNoVencido).'</a></td>';
            if (!$i) $totalarray['nbfield']++;
            if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'saldoNoVencido';
            if(!$banFila)
                $totalarray['val']['saldoNoVencido'] += ($objp->saldoNoVencido);
        }

        print '<td>';
        //Nuevo evento
        print '<table>';
        print '<tr>';
        print '<td>';
        print '<a href="'.DOL_MAIN_URL_ROOT.'/comm/action/card.php?eventCobranza=1&fk_soc='.$objp->socid.'&action=create" title="Crear evento">';
        print '<span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>';
        print '</a>';
        print '</td>';
        $htmltootltip = 'Sin eventos';
        $color="#828282";
        if($db->jdate($objp->fechapromesa) != '') {
            $htmltootltip = '<p>Descripción: <b>' . $objp->descpromesa . '</b></p>';
            $color="#090";
        }
        print '<td>';
        print '<i class="classfortooltip fa fa-circle" style="opacity:70%;color:'.$color.';" title="'.$htmltootltip.'"></i>';
        print '</td>';
        print '</tr>';
        print '</table>';
        print '</td>';
		if (!$i) $totalarray['nbfield']++;
        if (!$i) $totalarray['nbfield']++;

		print '</tr>';

        $i++;
    }
    print "</tbody>";
    // Show total line
    $temporalTotal = 1;
    include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

    print "</table>\n";
    print "</div>";
    print "</form>\n";
    
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
