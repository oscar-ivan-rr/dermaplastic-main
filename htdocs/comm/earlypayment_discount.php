<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/comm/remise.php
 *      \ingroup    societe
 *		\brief      Page to edit relative discount of a customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'orders', 'bills'));

$id = GETPOST("id", 'int');

$socid = GETPOST('id', 'int') ?GETPOST('id', 'int') : GETPOST('socid', 'int');
// Security check
if ($user->socid > 0)
{
    $socid = $user->socid;
}

$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha') && !empty($backtopage))
{
    header("Location: ".$backtopage);
    exit;
}

if (GETPOST('action', 'aZ09') == 'setdiscount')
{
    $object = new Societe($db);
    $object->fetch($id);
    $object->earlypayment_discount = price2num(GETPOST('earlypayment_discount'));
    $object->earlypayment_validity = price2num(GETPOST('earlypayment_validity'));
    if($object->earlypayment_discount == 0 && $object->earlypayment_validity == 0){
        $object->earlypayment_discount = '';
        $object->earlypayment_validity = '';
    }

    if((!empty($object->earlypayment_discount) && !empty($object->earlypayment_validity)) || ($object->earlypayment_discount== '' && $object->earlypayment_validity == '')){
        if($object->update($object->id) > 0){
            setEventMessages('Descuento aplicado con éxito', null, 'mesgs');
        }
        else{
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    else{
        setEventMessages('Error, por favor ingrese una cantidad para el descuento', null, 'errors');
    }

}


/*
 * View
 */

$form = new Form($db);

llxHeader();


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/
if ($socid > 0)
{
    // On recupere les donnees societes par l'objet
    $object = new Societe($db);
    $object->fetch($socid);

    $head = societe_prepare_head($object);

    $isCustomer = ($object->client == 1 || $object->client == 3);
    $isSupplier = $object->fournisseur == 1;

    print '<form method="POST" action="earlypayment_discount.php?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="setdiscount">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

    dol_fiche_head($head, 'relativediscount', $langs->trans("ThirdParty"), -1, 'company');

    dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';

    if (!$isCustomer && !$isSupplier) {
        print '<p class="opacitymedium">'.$langs->trans('ThirdpartyIsNeitherCustomerNorClientSoCannotHaveDiscounts').'</p>';

        dol_fiche_end();

        print '</form>';

        // End of page
        llxFooter();
        $db->close();
        exit;
    }

    print '<table class="border centpercent">';

    // Early payment discount
    print '<tr><td class="titlefield">';
    print $langs->trans("EarlyPaymentDiscountShort").'</td><td>'.price2num($object->earlypayment_discount)."%</td></tr>";
    print '<tr><td class="titlefield">';
    print $langs->trans("EarlyPaymentDiscountValidityShort").'</td><td>'.price2num($object->earlypayment_validity)." días</td></tr>";

    print '</table>';
    print '<br>';

    print load_fiche_titre($langs->trans("NewRelativeDiscount"), '', '');

    print '<div class="underbanner clearboth"></div>';

    /*if (! ($isCustomer && $isSupplier))
    {
        if ($isCustomer && ! $isSupplier) {
            print '<input type="hidden" name="discount_type" value="0" />';
        }
        if (! $isCustomer && $isSupplier) {
            print '<input type="hidden" name="discount_type" value="1" />';
        }
    }*/

    print '<table class="border centpercent">';

    if ($isCustomer || $isSupplier)
    {
        // Discount type
        print '<tr><td class="titlefield fieldrequired">'.$langs->trans('DiscountType').'</td><td>';
        if ($isCustomer) {
            print '<input type="radio" name="discount_type" id="discount_type_0" checked value="0"/> <label for="discount_type_0">'.$langs->trans('Customer').'</label>';
        }
        if ($isSupplier) {
            print ' <input type="radio" name="discount_type" id="discount_type_1"'.($isCustomer ? '' : ' checked').' value="1"/> <label for="discount_type_1">'.$langs->trans('Supplier').'</label>';
        }
        print '</td></tr>';
    }

    // New value
    print '<tr><td class="titlefield fieldrequired">';
    print 'Nuevo descuento</td><td><input type="text" size="5" name="earlypayment_discount" value="'.dol_escape_htmltag(GETPOST("earlypayment_discount")).'">%</td></tr>';

    // New number of days
    print '<tr><td class="titlefield fieldrequired">';
    print 'Número de días de vigencia </td><td><input type="text" size="5" name="earlypayment_validity" value="'.dol_escape_htmltag(GETPOST("earlypayment_validity")).'">días</td></tr>';

    print "</table>";

    print '</div>';

    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
    if (!empty($backtopage))
    {
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    }
    print '</div>';

    print "</form>";

    print '<br>';
}

// End of page
llxFooter();
$db->close();

