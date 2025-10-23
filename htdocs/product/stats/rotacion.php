<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/product/rotacion.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
//Required to translate NbOfProposals
$langs->load('propal');

$type=GETPOST("type", "int");

// Security check
if (! empty($user->socid)) $socid=$user->socid;
$result=restrictedArea($user, 'produit|service');

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
if (! $sortfield) $sortfield="c";
if (! $sortorder) $sortorder="DESC";
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

//UNIDADES
$search_monthUnidades = GETPOST('search_monthUnidades', 'alpha');
$search_yearUnidades = GETPOST('search_yearUnidades', 'int');
if (GETPOST('removeUnidades')) {
	$search_monthUnidades='';
	$search_yearUnidades='';
}
//MONTO
$search_monthMonto = GETPOST('search_monthMonto', 'alpha');
$search_yearMonto = GETPOST('search_yearMonto', 'int');
if (GETPOST('removeMonto')) {
	$search_monthMonto='';
	$search_yearMonto='';
}
//MODA
$search_monthModa = GETPOST('search_monthModa', 'alpha');
$search_yearModa = GETPOST('search_yearModa', 'int');
if (GETPOST('removeModa')) {
	$search_monthModa='';
	$search_yearModa='';
}

$staticproduct=new Product($db);
$form = new Form($db);
$formother= new FormOther($db);

/*
 * View
 */

$helpurl='';
if ($type == '0')
{
    $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
elseif ($type == '1')
{
    $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}
else
{
    $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}
$title=$langs->trans("Statistics");


llxHeader('', $title, $helpurl);

print load_fiche_titre($title, $mesg, 'products');


$param = '';
$title = $langs->trans("ListProductServiceByPopularity");
if ((string) $type == '1') {
	$title = $langs->trans("ListServiceByPopularity");
}
if ((string) $type == '0') {
	$title = $langs->trans("ListProductByPopularity");
}

if ($type != '') $param .= '&type='.$type;


$h=0;
$head = array();

$head[$h][0] = DOL_URL_ROOT.'/product/stats/card.php?id=all';
$head[$h][1] = $langs->trans("Chart");
$head[$h][2] = 'chart';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/popuprop.php'.($type != '' ? '?type='.$type : '');
$head[$h][1] = $title;
$head[$h][2] = 'popularityprop';
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/stats/rotacion.php';
$head[$h][1] = "Rotación";
$head[$h][2] = 'rotacion';
$h++;

dol_fiche_head($head, 'rotacion', $langs->trans("Statistics"), -1);

print '<form method="post" action="'.$_SERVER ['PHP_SELF'].'">';
print_barre_liste("Rotación por Unidades", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $totalnboflines, '');

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">';
print $langs->trans('Period').' - ';
print $langs->trans('Month') . ':
<select name="search_monthUnidades" class="flat">
    <option value="">&nbsp;</option>
    <option value="01" '.(($search_monthUnidades == '01') ? ' selected' : '').'>Enero</option>
    <option value="02" '.(($search_monthUnidades == '02') ? ' selected' : '').'>Febrero</option>
    <option value="03" '.(($search_monthUnidades == '03') ? ' selected' : '').'>Marzo</option>
    <option value="04" '.(($search_monthUnidades == '04') ? ' selected' : '').'>Abril</option>
    <option value="05" '.(($search_monthUnidades == '05') ? ' selected' : '').'>Mayo</option>
    <option value="06" '.(($search_monthUnidades == '06') ? ' selected' : '').'>Junio</option>
    <option value="07" '.(($search_monthUnidades == '07') ? ' selected' : '').'>Julio</option>
    <option value="08" '.(($search_monthUnidades == '08') ? ' selected' : '').'>Agosto</option>
    <option value="09" '.(($search_monthUnidades == '09') ? ' selected' : '').'>Septiembre</option>
    <option value="10" '.(($search_monthUnidades == '10') ? ' selected' : '').'>Octubre</option>
    <option value="11" '.(($search_monthUnidades == '11') ? ' selected' : '').'>Noviembre</option>
    <option value="12" '.(($search_monthUnidades == '12') ? ' selected' : '').'>Diciembre</option>
</select> ';
print $langs->trans('Year') . ':' . $formother->selectyear($search_yearUnidades ? $search_yearUnidades : - 1, 'search_yearUnidades', 1, 20, 5);
print '<div style="vertical-align: middle; display: inline-block">';
print '<button class="liste_titre" type="submit" name="searchUnidades" value="1" title="Buscar"> <i class="fas fa-search"></i></button>';
print '<button class="liste_titre" type="submit" name="removeUnidades" value="1" title="Remover Filtros"> <i class="far fa-window-close"></i></button>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="fichecenter"><div class="fichethirdleft">';

print '<table class="noborder centpercent">';

    print "<tr class=\"liste_titre\">";
        print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Description', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Units', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
    print "</tr>\n";

    //QUERY PARA UNIDADES
    $sqlUnidades = "SELECT id, ref, label, date, qty
                        FROM(
                            SELECT p.rowid AS id, p.ref AS ref, p.label AS label, f.datef AS date, SUM(fd.qty) AS qty
                                    FROM llx_facture AS f, llx_facturedet AS fd, llx_product AS p
                                        WHERE f.fk_statut IN (1,2) 
                                                AND fd.fk_facture = f.rowid
                                                AND fd.fk_product = p.rowid
                                                    GROUP BY p.rowid
                            UNION
                            SELECT p.rowid AS id, p.ref AS ref, p.label AS label, f.date_ticket AS date, SUM(fd.qty) AS qty
                                FROM llx_pos_ticket AS f, llx_pos_ticketdet AS fd, llx_product AS p
                                    WHERE f.fk_statut IN (1,2) 
                                        AND fd.fk_ticket = f.rowid
                                        AND fd.fk_product = p.rowid
                                            GROUP BY p.rowid
                            )ventas  
                            WHERE 1=1 ";
                                if (! empty($search_monthUnidades))
                                    $sqlUnidades.= ' AND MONTH(date) IN ('.$search_monthUnidades.')';
                                if (! empty($search_yearUnidades))
                                    $sqlUnidades.= ' AND YEAR(date) IN ('.$search_yearUnidades.')';
                            $sqlUnidades .= " ORDER BY qty DESC LIMIT 20";
    $resultUnidades = $db->query($sqlUnidades);
    if ($resultUnidades)
    {
        $cntUnidades = 0;
        while ($objUnidades= $db->fetch_object($resultUnidades)){
        	$qty_total += $objUnidades->qty;
            $product = new Product($db);
		    $product->fetch($objUnidades->id);
            print "<tr>";
                print "<td>";
                print $product->getNomUrl(1, '', 50);
                print "</td>";
                print "<td>";
                print $objUnidades->label;
                print "</td>";
                print "<td>";
                print $objUnidades->qty;
                print "</td>";
            print "</tr>";
            $rowsUnidades[$cntUnidades] = array($objUnidades->label,$objUnidades->qty);
            $cntUnidades++;
        }
        print '<tr class="liste_titre">';
        print '<td colspan="2" class="right" style="font-weight:bold;">TOTAL</td>';
        print '<td style=\"font-weight:bold;\">'.$qty_total.'</td>';
        print '</tr>';
    }


print "</table>";
print '</div>';

print '<div class="fichetwothirdright">';
    print '<table class="noborder centpercent">';
        print "<tr class=\"liste_titre\">";
            print '<th>Estadísticas</th>';
        print "</tr>\n";
        print '<tr>';
            print '<td>';
                $dolgraphUnidades = new DolGraph();
                $dolgraphUnidades->SetData($rowsUnidades);
                $dolgraphUnidades->setShowLegend(1);
                $dolgraphUnidades->setShowPercent(1);
                $dolgraphUnidades->setShowPointValue(0);    
                $dolgraphUnidades->SetType(array('pie'));
                $dolgraphUnidades->setWidth('100%');
                $dolgraphUnidades->SetHeight(500);
                $dolgraphUnidades->draw('idgraphcxc');
                print $dolgraphUnidades->show();
            print '</td>';
        print '<tr>';
    print "</table>";
print '</div>';
print '</form>';

print '<form method="post" action="'.$_SERVER ['PHP_SELF'].'">';
print_barre_liste("Rotación por Monto de Venta", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $totalnboflines, '');

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">';
print $langs->trans('Period').' - ';
print $langs->trans('Month') . ':
<select name="search_monthMonto" class="flat">
    <option value="">&nbsp;</option>
    <option value="01" '.(($search_monthMonto == '01') ? ' selected' : '').'>Enero</option>
    <option value="02" '.(($search_monthMonto == '02') ? ' selected' : '').'>Febrero</option>
    <option value="03" '.(($search_monthMonto == '03') ? ' selected' : '').'>Marzo</option>
    <option value="04" '.(($search_monthMonto == '04') ? ' selected' : '').'>Abril</option>
    <option value="05" '.(($search_monthMonto == '05') ? ' selected' : '').'>Mayo</option>
    <option value="06" '.(($search_monthMonto == '06') ? ' selected' : '').'>Junio</option>
    <option value="07" '.(($search_monthMonto == '07') ? ' selected' : '').'>Julio</option>
    <option value="08" '.(($search_monthMonto == '08') ? ' selected' : '').'>Agosto</option>
    <option value="09" '.(($search_monthMonto == '09') ? ' selected' : '').'>Septiembre</option>
    <option value="10" '.(($search_monthMonto == '10') ? ' selected' : '').'>Octubre</option>
    <option value="11" '.(($search_monthMonto == '11') ? ' selected' : '').'>Noviembre</option>
    <option value="12" '.(($search_monthMonto == '12') ? ' selected' : '').'>Diciembre</option>
</select> ';
print $langs->trans('Year') . ':' . $formother->selectyear($search_yearMonto ? $search_yearMonto : - 1, 'search_yearMonto', 1, 20, 5);
print '<div style="vertical-align: middle; display: inline-block">';
print '<button class="liste_titre" type="submit" name="searchMonto" value="1" title="Buscar"> <i class="fas fa-search"></i></button>';
print '<button class="liste_titre" type="submit" name="removeMonto" value="1" title="Remover Filtros"> <i class="far fa-window-close"></i></button>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="fichecenter"><div class="fichethirdleft">';
print '<table class="noborder centpercent">';

    print "<tr class=\"liste_titre\">";
        print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Description', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Units', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Importe', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
    print "</tr>\n";

    //QUERY PARA MONTOS
    $sqlMonto = "SELECT id, ref, label, date, qty, total
                        FROM(
                            SELECT p.rowid AS id, p.ref AS ref, p.label AS label, f.datef AS date, SUM(fd.qty) AS qty, SUM(fd.total_ttc) AS total
                                    FROM llx_facture AS f, llx_facturedet AS fd, llx_product AS p
                                        WHERE f.fk_statut IN (1,2) 
                                                AND fd.fk_facture = f.rowid
                                                AND fd.fk_product = p.rowid
                                                AND p.fk_product_type = 0
                                                    GROUP BY p.rowid
                            UNION
                            SELECT p.rowid AS id, p.ref AS ref, p.label AS label, f.date_ticket AS date, SUM(fd.qty) AS qty,  SUM(fd.total_ttc) AS total
                                FROM llx_pos_ticket AS f, llx_pos_ticketdet AS fd, llx_product AS p
                                    WHERE f.fk_statut IN (1,2) 
                                        AND fd.fk_ticket = f.rowid
                                        AND fd.fk_product = p.rowid
                                        AND p.fk_product_type = 0
                                            GROUP BY p.rowid
                            )ventas  
                            WHERE 1=1 ";
                                if (! empty($search_monthMonto))
                                    $sqlMonto.= ' AND MONTH(date) IN ('.$search_monthMonto.')';
                                if (! empty($search_yearMonto))
                                    $sqlMonto.= ' AND YEAR(date) IN ('.$search_yearMonto.')';
                            $sqlMonto .= " ORDER BY total DESC LIMIT 20";
    $resultMonto = $db->query($sqlMonto);
    if ($resultMonto)
    {
        $cntMonto = 0;
        while ($objMonto= $db->fetch_object($resultMonto)){
        	$qty_total += $objMonto->qty;
            $total_total += $objMonto->total;
            $product = new Product($db);
		    $product->fetch($objMonto->id);
            print "<tr>";
                print "<td>";
                print $product->getNomUrl(1, '', 50);
                print "</td>";
                print "<td>";
                print $objMonto->label;
                print "</td>";
                print "<td>";
                print $objMonto->qty;
                print "</td>";
                print "<td>$";
                print price($objMonto->total);
                print "</td>";
            print "</tr>";
            $rowsMonto[$cntMonto] = array($objMonto->label,$objMonto->total);
            $cntMonto++;
        }
        print '<tr class="liste_titre">';
        print '<td colspan="2" class="right" style="font-weight:bold;">TOTALES</td>';
        print '<td style=\"font-weight:bold;\">'.$qty_total.'</td>';
        print '<td style=\"font-weight:bold;\">$'.price($total_total).'</td>';
        print '</tr>';
    }

print "</table>";
print '</div>';

print '<div class="fichetwothirdright">';
    print '<table class="noborder centpercent">';
        print "<tr class=\"liste_titre\">";
            print '<th>Estadísticas</th>';
        print "</tr>\n";
        print '<tr>';
            print '<td>';
                $dolgraphMonto = new DolGraph();
                $dolgraphMonto->SetData($rowsMonto);
                $dolgraphMonto->setShowLegend(1);
                $dolgraphMonto->setShowPercent(1);
                $dolgraphMonto->setShowPointValue(0);    
                $dolgraphMonto->SetType(array('pie'));
                $dolgraphMonto->setWidth('100%');
                $dolgraphMonto->SetHeight(500);
                $dolgraphMonto->draw('idgraphcxp');
                print $dolgraphMonto->show();
            print '</td>';
        print '<tr>';
    print "</table>";
print '</div>';
print '</form>';

print '<form method="post" action="'.$_SERVER ['PHP_SELF'].'">';
print_barre_liste("Rotación por Producto de Moda", $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $totalnboflines, '');

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">';
print $langs->trans('Period').' - ';
print $langs->trans('Month') . ':
<select name="search_monthModa" class="flat">
    <option value="">&nbsp;</option>
    <option value="01" '.(($search_monthModa == '01') ? ' selected' : '').'>Enero</option>
    <option value="02" '.(($search_monthModa == '02') ? ' selected' : '').'>Febrero</option>
    <option value="03" '.(($search_monthModa == '03') ? ' selected' : '').'>Marzo</option>
    <option value="04" '.(($search_monthModa == '04') ? ' selected' : '').'>Abril</option>
    <option value="05" '.(($search_monthModa == '05') ? ' selected' : '').'>Mayo</option>
    <option value="06" '.(($search_monthModa == '06') ? ' selected' : '').'>Junio</option>
    <option value="07" '.(($search_monthModa == '07') ? ' selected' : '').'>Julio</option>
    <option value="08" '.(($search_monthModa == '08') ? ' selected' : '').'>Agosto</option>
    <option value="09" '.(($search_monthModa == '09') ? ' selected' : '').'>Septiembre</option>
    <option value="10" '.(($search_monthModa == '10') ? ' selected' : '').'>Octubre</option>
    <option value="11" '.(($search_monthModa == '11') ? ' selected' : '').'>Noviembre</option>
    <option value="12" '.(($search_monthModa == '12') ? ' selected' : '').'>Diciembre</option>
</select> ';
print $langs->trans('Year') . ':' . $formother->selectyear($search_yearModa ? $search_yearModa : - 1, 'search_yearModa', 1, 20, 5);
print '<div style="vertical-align: middle; display: inline-block">';
print '<button class="liste_titre" type="submit" name="searchModa" value="1" title="Buscar"> <i class="fas fa-search"></i></button>';
print '<button class="liste_titre" type="submit" name="removeModa" value="1" title="Remover Filtros"> <i class="far fa-window-close"></i></button>';
print '</div>';
print '</div>';
print '</div>';

print '<table class="noborder centpercent">';

    print "<tr class=\"liste_titre\">";
        print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Description', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Units', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Importe', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('Moda', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
    print "</tr>\n";

   //QUERY PARA MODA
   $sqlModa = "SELECT id, ref, label, date, moda, qty, total
                        FROM(
                            SELECT p.rowid AS id, p.ref AS ref, p.label AS label, f.datef AS date, COUNT(p.rowid) AS moda, SUM(fd.qty) AS qty, SUM(fd.total_ttc) AS total
                                    FROM llx_facture AS f, llx_facturedet AS fd, llx_product AS p
                                        WHERE f.fk_statut IN (1,2) 
                                                AND fd.fk_facture = f.rowid
                                                AND fd.fk_product = p.rowid
                                                AND p.fk_product_type = 0
                                                    GROUP BY p.rowid
                            UNION
                            SELECT p.rowid AS id, p.ref AS ref, p.label AS label, f.date_ticket AS date, COUNT(p.rowid) AS moda, SUM(fd.qty) AS qty,  SUM(fd.total_ttc) AS total
                                FROM llx_pos_ticket AS f, llx_pos_ticketdet AS fd, llx_product AS p
                                    WHERE f.fk_statut IN (1,2) 
                                        AND fd.fk_ticket = f.rowid
                                        AND fd.fk_product = p.rowid
                                        AND p.fk_product_type = 0
                                            GROUP BY p.rowid
                            )ventas  
                            WHERE 1=1 ";
                                if (! empty($search_monthModa))
                                    $sqlModa.= ' AND MONTH(date) IN ('.$search_monthModa.')';
                                if (! empty($search_yearModa))
                                    $sqlModa.= ' AND YEAR(date) IN ('.$search_yearModa.')';
                            $sqlModa .= " ORDER BY moda DESC LIMIT 20";
    $resultModa = $db->query($sqlModa);
    if ($resultModa)
    {
        while ($objModa= $db->fetch_object($resultModa)){
            $qty_total += $objModa->qty;
            $total_total += $objModa->total;
            $moda_total += $objModa->moda;
            $product = new Product($db);
            $product->fetch($objModa->id);
            print "<tr>";
                print "<td>";
                print $product->getNomUrl(1, '', 50);
                print "</td>";
                print "<td>";
                print $objModa->label;
                print "</td>";
                print "<td>";
                print $objModa->qty;
                print "</td>";
                print "<td>$";
                print price($objModa->total);
                print "</td>";
                print "<td>";
                print $objModa->moda;
                print "</td>";
            print "</tr>";
        }
        print '<tr class="liste_titre">';
        print '<td colspan="2" class="right" style="font-weight:bold;">TOTALES</td>';
        print '<td style=\"font-weight:bold;\">'.$qty_total.'</td>';
        print '<td style=\"font-weight:bold;\">$'.price($total_total).'</td>';
        print '<td style=\"font-weight:bold;\">'.$moda_total.'</td>';
        print '</tr>';
    }

print "</table>";
print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
