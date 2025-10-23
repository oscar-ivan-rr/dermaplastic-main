<?php

/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *  \file       htdocs/product/substitutes.php
 *  \ingroup    product
 *  \brief      Page de la fiche produit
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'products', 'stocks'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$key = GETPOST('key');
$parent = GETPOST('parent');
$substitute_del = GETPOST('delsub');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : '';
// Security check
if (!empty($user->socid)) $socid = $user->socid;
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

$object = new Product($db);
$objectid = 0;
if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    $objectid = $object->id;
    $id = $object->id;
}

if ($contextpage == 'poslist')
{
	$_GET['optioncss'] = 'print';
}

/*
 * Actions
 */

if ($cancel) $action = '';
if($action == 'delete' && $user->rights->produit->supprimer){
    if($object->del_substitute($id, $substitute_del))
    {
        $action = 'view';
    }
}
// Action association d'un sousproduit
if ($action == 'add_prod' && ($user->rights->produit->creer || $user->rights->service->creer)) {
    $error = 0;
    for ($i = 0; $i < $_POST["max_prod"]; $i++) {
        if ($_POST["prod_incdec_".$i]) {
            if ($object->add_substitute($id, $_POST["prod_id_" . $i]) > 0) {
                //var_dump($id.' - '.$_POST["prod_id_".$i].' - '.$_POST["prod_qty_".$i]);exit;
                //$action = 'edit';
            } else {
                $error++;
                //$action = 're-edit';
                if ($object->error == "isSubstituteOfThis") {
                    setEventMessages($langs->trans("Ya es un sustituto del producto"), null, 'errors');
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
        }
    }
    if (!$error) {
        header("Location: " . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
        exit;
    }
} elseif ($action === 'save_composed_product') {
    $TProduct = GETPOST('TProduct', 'array');
    if (!empty($TProduct)) {
        foreach ($TProduct as $id_product => $row) {
            if ($row['qty'] > 0) $object->update_sousproduit($id, $id_product, $row['qty'], isset($row['incdec']) ? 1 : 0);
            else $object->del_sousproduit($id, $id_product);
        }
        setEventMessages('RecordSaved', null);
    }
    $action = '';
}


/*
 * View
 */

$product_fourn = new ProductFournisseur($db);
$productstatic = new Product($db);
$form = new Form($db);

// action recherche des produits par mot-cle et/ou par categorie
if ($action == 'search') {
    $current_lang = $langs->getDefaultLang();

    $sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.fk_product_type as type, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
    $sql .= ' p.fk_product_type, p.tms as datem';
    if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= ', pl.label as labelm, pl.description as descriptionm';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product as p';
    $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_product as cp ON p.rowid = cp.fk_product';
    if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_lang as pl ON pl.fk_product = p.rowid AND lang='" . ($current_lang) . "'";
    $sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
    if ($key != "") {
        // For natural search
        $params = array('p.ref', 'p.label', 'p.description', 'p.note');
        // multilang
        if (!empty($conf->global->MAIN_MULTILANGS)) {
            $params[] = 'pl.label';
            $params[] = 'pl.description';
            $params[] = 'pl.note';
        }
        if (!empty($conf->barcode->enabled)) {
            $params[] = 'p.barcode';
        }
        $sql .= natural_search($params, $key);
    }
    if (!empty($conf->categorie->enabled) && !empty($parent) && $parent != -1) {
        $sql .= " AND cp.fk_categorie ='" . $db->escape($parent) . "'";
    }
    $sql .= " ORDER BY p.ref ASC";

    $resql = $db->query($sql);
}

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
    $title = $langs->trans('Product') . " " . $shortlabel . " - " . $langs->trans('AssociatedProducts');
    $helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
    $title = $langs->trans('Service') . " " . $shortlabel . " - " . $langs->trans('AssociatedProducts');
    $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$head = product_prepare_head($object);
$titre = $langs->trans("CardProduct" . $object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
if ($contextpage != 'poslist')
{
    dol_fiche_head($head, 'substitutes', $titre, -1, $picto);
}


if ($id > 0 || !empty($ref)) {
    /*
     * Fiche en mode edition
     */
    if ($user->rights->produit->lire || $user->rights->service->lire) {
        $linkback = '<a href="' . DOL_URL_ROOT . '/product/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        $shownav = 1;
        if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

        dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', '', '', '', 0, '', '', 0);

        if ($object->type != Product::TYPE_SERVICE || empty($conf->global->PRODUIT_MULTIPRICES)) {
            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';

            print '<table class="border centpercent tableforfield">';

            // Nature
            if ($object->type != Product::TYPE_SERVICE) {
                print '<tr><td class="titlefield">' . $langs->trans("Nature") . '</td><td>';
                print $object->getLibFinished();
                print '</td></tr>';
            }

            if (empty($conf->global->PRODUIT_MULTIPRICES)) {
                // Price
                print '<tr><td class="titlefield">' . $langs->trans("SellingPrice") . '</td><td>';
                if ($object->price_base_type == 'TTC') {
                    print price($object->price_ttc) . ' ' . $langs->trans($object->price_base_type);
                } else {
                    print price($object->price) . ' ' . $langs->trans($object->price_base_type ? $object->price_base_type : 'HT');
                }
                print '</td></tr>';

                // Price minimum
                print '<tr><td>' . $langs->trans("MinPrice") . '</td><td>';
                if ($object->price_base_type == 'TTC') {
                    print price($object->price_min_ttc) . ' ' . $langs->trans($object->price_base_type);
                } else {
                    print price($object->price_min) . ' ' . $langs->trans($object->price_base_type ? $object->price_base_type : 'HT');
                }
                print '</td></tr>';
            }

            print '</table>';
            print '</div>';
        }

        dol_fiche_end();

        print '<br>';

        $prodsfather = $object->getSubstitute();      // Parent Products

        print '<div class="fichecenter">';

        print load_fiche_titre($langs->trans("ProductSubstiteList"), '', '');

        print '<table class="liste">';
        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans('Products') . '</td>';
        print '<td>' . $langs->trans('Label') . '</td>';
        if ($contextpage != 'poslist') print '<td>' . $langs->trans('Action') . '</td>';
        print '</td>';
        if (count($prodsfather) > 0) {
            foreach ($prodsfather as $value) {
                $idprod = $value["id"];
                $productstatic->id = $idprod;// $value["id"];
                $productstatic->type = $value["fk_product_type"];
                $productstatic->ref = $value['ref'];
                $productstatic->label = $value['label'];
                $productstatic->entity = $value['entity'];

                print '<tr class="oddeven">';
                print '<td>' . $productstatic->getNomUrl(1, 'composition') . '</td>';
                print '<td>' . $productstatic->label . '</td>';
                if ($contextpage != 'poslist') print '<td> <a href="'.DOL_URL_ROOT .'/product/substitutes.php?id=' . $id . '&action=delete&delsub='.$productstatic->id.'">'.img_picto($langs->trans("Remove"), 'delete').'</a> </td>';
                print '</tr>';
            }
        } else {
            print '<tr class="oddeven">';
            print '<td colspan="3" class="opacitymedium">' . $langs->trans("None") . '</td>';
            print '</tr>';
        }
        print '</table>';
        print '</div>';

        print '<br>' . "\n";


        print '<div class="fichecenter">';

        $atleastonenotdefined = 0;

        // Form with product to add
        if ((empty($action) || $action == 'view' || $action == 'edit' || $action == 'search' || $action == 're-edit') && ($user->rights->produit->creer || $user->rights->service->creer) && $contextpage != 'poslist') {
            print '<br>';

            $rowspan = 1;
            if (!empty($conf->categorie->enabled)) $rowspan++;

            print load_fiche_titre($langs->trans("ProductToAddSearch"), '', '');
            print '<form action="' . DOL_URL_ROOT . '/product/substitutes.php?id=' . $id . '" method="POST">';
            print '<input type="hidden" name="action" value="search">';
            print '<input type="hidden" name="id" value="' . $id . '">';
            print '<div class="inline-block">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print $langs->trans("KeywordFilter") . ': ';
            print '<input type="text" name="key" value="' . $key . '"> &nbsp; ';
            print '</div>';
            if (!empty($conf->categorie->enabled)) {
                require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
                print '<div class="inline-block">' . $langs->trans("CategoryFilter") . ': ';
                print $form->select_all_categories(Categorie::TYPE_PRODUCT, $parent, 'parent') . ' &nbsp; </div>';
                print ajax_combobox('parent');
            }
            print '<div class="inline-block">';
            print '<input type="submit" class="button" value="' . $langs->trans("Search") . '">';
            print '</div>';
            print '</form>';
        }


        // List of products
        if ($action == 'search') {
            print '<br>';
            print '<form action="' . DOL_URL_ROOT . '/product/substitutes.php?id=' . $id . '" method="post">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="add_prod">';
            print '<input type="hidden" name="id" value="' . $id . '">';

            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<th class="liste_titre">&nbsp;</td>';
            print '<th class="liste_titre">&nbsp</td>';
            print '<th class="liste_titre"> &nbsp; </td>';
            print '<th class="center">';
            print '<input type="checkbox" id="check_all" value="1" checked>';
            print '<script>';
            print '$(\'#check_all\').click(function() {';
            print ' if ($(this).prop(\'checked\')) {';
            print '     $(\'.chck\').prop(\'checked\', true);';
            print ' } else {';
            print '     $(\'.chck\').prop(\'checked\', false);';
            print ' }';
            print '});';
            print '</script>';
            print '</th>';
            print '</tr>';
            print '<tr class="liste_titre">';
            print '<th class="liste_titre">' . $langs->trans("ComposedProduct") . '</td>';
            print '<th class="liste_titre">' . $langs->trans("Label") . '</td>';
            print '<th class="liste_titre"> &nbsp; </td>';
            print '<th class="center">' . $langs->trans('Add') . '</th>';
            print '</tr>';
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;

                if ($num == 0) print '<tr><td colspan="4">' . $langs->trans("NoMatchFound") . '</td></tr>';

                while ($i < $num) {
                    $objp = $db->fetch_object($resql);
                    if ($objp->rowid != $id) {
                        // check if a product is not already a parent product of this one
                        $prod_arbo = new Product($db);
                        $prod_arbo->id = $objp->rowid;

                        print "\n" . '<tr class="oddeven">';

                        $productstatic->id = $objp->rowid;
                        $productstatic->ref = $objp->ref;
                        $productstatic->label = $objp->label;
                        $productstatic->type = $objp->type;
                        $productstatic->entity = $objp->entity;

                        print '<td>' . $productstatic->getNomUrl(1, '', 24) . '</td>';
                        $labeltoshow = $objp->label;
                        if ($conf->global->MAIN_MULTILANGS && $objp->labelm) $labeltoshow = $objp->labelm;

                        print '<td>' . $labeltoshow . '</td>';


                        if ($object->is_sustitute($id, $objp->rowid)) {
                            $incdec = 0;
                        } else {
                            $incdec = 1;
                        }
                        // Contained into package
                        print '<td class="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
                        print '<td class="center">';
                        print '<input type="checkbox" class="chck" name="prod_incdec_' . $i . '" value="1" ' . ($incdec ? 'checked' : '') . '>';

                        print '</td>';

                        print '</tr>';
                    }
                    $i++;
                }
            } else {
                dol_print_error($db);
            }
            print '</table>';
            print '<input type="hidden" name="max_prod" value="' . $i . '">';

            if ($num > 0) {
                print '<br><div class="center">';
                print '<input type="submit" class="button" name="save" value="' . $langs->trans("Add") . '/' . $langs->trans("Update") . '">';
                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
                print '</div>';
            }

            print '</form>';
        }
    }
}

// End of page
llxFooter();
$db->close();
