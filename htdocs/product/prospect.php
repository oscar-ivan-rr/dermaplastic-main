<?php
/**
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_eti = GETPOST("search_eti", 'alpha');
$search_qty = GETPOST("search_qty", 'alpha');


$diroutputmassaction = $conf->product->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = (GETPOST("page", 'int') ?GETPOST("page", 'int') : 0);
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "p.label";
if (!$sortorder) $sortorder = "ASC";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'productservicelist';
if ((string) $type == '1') { $contextpage = 'servicelist'; if ($search_type == '') $search_type = '1'; }
if ((string) $type == '0') { $contextpage = 'productlist'; if ($search_type == '') $search_type = '0'; }

$form = new Form($db);


if (empty($action)) $action = 'list';


// Security check
if ($search_type == '0') $result = restrictedArea($user, 'produit', '', '', '', '', '', $objcanvas);
elseif ($search_type == '1') $result = restrictedArea($user, 'service', '', '', '', '', '', $objcanvas);
else $result = restrictedArea($user, 'produit|service', '', '', '', '', '', $objcanvas);

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (!empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) || !empty($conf->global->STOCK_CALCULATE_ON_RECEPTION))
{
    $virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'p.label'=>"Ref",
    'p.qty'=>"RefSupplier",
    'p.datec'=>"Date"
);


// Definition of fields for lists
$arrayfields = array(
    'p.qty'=>array('label'=>"Cantidad", 'checked'=>1),
    'p.label'=>array('label'=>"Etiqueta", 'checked'=>1),
    'p.datec'=>array('label'=>"Fecha de creación", 'checked'=>1),
);
$arrayfields = dol_sort_array($arrayfields, 'position');

/*
 * Actions
 */

/*if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }
*/

/*
 * View
 */


$sql = 'SELECT DISTINCT p.id, p.label, p.qty, p.datec';
$sql .= ' FROM product_prospect as p';
$sql .=' WHERE 1';
if ($search_eti)     $sql .= natural_search('p.label', $search_eti);
if ($search_qty) $sql .= natural_search('p.qty', $search_qty);
//if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
$sql .= $db->order($sortfield, $sortorder);

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);

    $arrayofselected = is_array($toselect) ? $toselect : array();

    if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
    {
        $obj = $db->fetch_object($resql);
        $id = $obj->rowid;
        header("Location: ".DOL_URL_ROOT.'/product/card.php?id='.$id);
        exit;
    }

    $helpurl = '';
    if ($search_type != '')
    {
        if ($search_type == 0)
        {
            $helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
        }
        elseif ($search_type == 1)
        {
            $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
        }
    }

    llxHeader('', "Prospectos", $helpurl, '');

    // Displays product removal confirmation
    if (GETPOST('delprod')) {
        setEventMessages($langs->trans("ProductDeleted", GETPOST('delprod')), null, 'mesgs');
    }

    $param = '';
    if ($search_eti) $param = "&search_eti=".urlencode($search_eti);
    if ($search_qty) $param = "&search_qty=".urlencode($search_qty);
    if ($search_barcode) $param .= ($search_barcode ? "&search_barcode=".urlencode($search_barcode) : "");

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="type" value="'.$type.'">';
    if (empty($arrayfields['p.fk_product_type']['checked'])) print '<input type="hidden" name="search_type" value="'.dol_escape_htmltag($search_type).'">';

    print_barre_liste("Productos Prospectos", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'products', 0, $newcardbutton, '', $limit);


    if ($sall)
    {
        foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
        print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
    }

    // Filter on categories
    $moreforfilter = '';

    $parameters = array();
    $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
    else $moreforfilter = $hookmanager->resPrint;

    if ($moreforfilter)
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';
    }

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
    if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

    // Lines with input filters
    print '<tr class="liste_titre_filter">';
    if (!empty($arrayfields['p.label']['checked']))
    {
        print '<td class="liste_titre left">';
        print '<input class="flat" type="text" name="search_eti" size="8" value="'.dol_escape_htmltag($search_eti).'">';
        print '</td>';
    }
    if (!empty($arrayfields['p.qty']['checked']))
    {
        print '<td class="liste_titre left">';
        print '<input class="flat" type="text" name="search_qty" size="8" value="'.dol_escape_htmltag($search_qty).'">';
        print '</td>';
    }
    if (!empty($arrayfields['p.datec']['checked']))
    {
        print '<td class="liste_titre left">';
        print '&nbsp';
        print '</td>';
    }
    print '<td class="liste_titre center maxwidthsearch">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';

    print '</tr>';
    print '</tr>';
    print '<tr class="liste_titre">';
    print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], "p.label", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre($arrayfields['p.qty']['label'], $_SERVER["PHP_SELF"], "p.qty", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
    print '</tr>';
    $i = 0;
    $totalarray = array();
    while ($i < min($num, $limit))
    {
        $obj = $db->fetch_object($resql);

        print '<tr class="oddeven">';

        // Label
            print '<td class="tdoverflowmax200">';
            print $obj->label;
            print "</td>\n";
            if (!$i) $totalarray['nbfield']++;

        // Qty
            print '<td class="tdoverflowmax200">';
            print $obj->qty;
            print "</td>\n";

        // Date creation
            print '<td class="tdoverflowmax200">'.dol_print_date($obj->datec, '%H:%M %d/%m/%Y').'</td>';
            print '<td>&nbsp</td>';
        $i++;
    }

    $db->free($resql);

    print "</table>";
    print "</div>";
    print '</form>';
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
