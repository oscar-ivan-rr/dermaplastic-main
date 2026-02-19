<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php CORE -->\n";

// Title line
print "<thead>\n";

print '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td class="linecolnum center">&nbsp;</td>';
//Discount
if(($this->element == 'commande' || $this->element == 'facture' ||($this->element == 'propal' && !empty($user->rights->propal->client_to_fourn))) && $this->statut == 0) {
    print '<td style="width: 15px;"><input type="checkbox" id="all_lines" name="all_lines"></td>';
}
if(($this->element == 'invoice_supplier' || $this->element == 'order_supplier' || $this->element == 'supplier_proposal') && $this->statut == 0){
    print '<td style="width: 15px;"><input type="checkbox" id="all_lines" name="all_lines"></td>';
}
// REF (REVISION)
print '<td style="font-size: 18px;width:20px;">'.$langs->trans('Ref').'</td>';
// Description
print '<td class="linecoldescription" style="font-size: 18px;width:20px;">'.$langs->trans('Description').'</td>';
// Qty
print '<td class="linecolqty right" style="width:20px;">'.$langs->trans('Qty').'</td>';
if($this->table_element_line == 'facturedet' || $this->table_element_line == 'commandedet' || $this->table_element_line == 'propaldet'
    || $this->table_element_line == 'facture_fourn_det' || $this->table_element_line == 'commande_fournisseurdet'
    || $this->table_element_line == 'supplier_proposaldet') {
    //Unidad Entrada/Salida
    //print '<td class="linecolunit right">' . "Unidad". '</br>'. "Entrada" . '</td>';
}
// Unit (propal, supplier_proposal)

if ($object->element == 'propal' || $object->element == 'supplier_proposal' || $object->element == 'commande' || $object->element == 'order_supplier' ||$object->element == 'facture' || $object->element == 'invoice_supplier'){
    print '<td class="linecolunit right" style="width:20px;">'.$langs->trans('Unit').'</td>';
}
/*if ($this->element == 'supplier_proposal' || $this->element == 'order_supplier' || $this->element == 'invoice_supplier')
{
	print '<td class="linerefsupplier"><span id="title_fourn_ref">'.$langs->trans("SupplierRef").'</span></td>';
}*/

// VAT
//SE OCULTA LA COLUMNA IVA
//print '<td class="linecolvat right" style="width: 80px">'.$langs->trans('VAT').'</td>';


// Price HT
print '<td class="linecoluht right" style="width:20px;">'.$langs->trans('PriceUHT').'</td>';

// Precio Compra
$sql1 = "SELECT a.fk_source FROM ".MAIN_DB_PREFIX."element_element AS a, ".MAIN_DB_PREFIX."propal AS b ";
$sql1 .= " WHERE a.fk_target = ".$object->lines[0]->fk_propal." AND a.targettype = 'propal' AND a.sourcetype = 'supplier_proposal' ";
$sql1 .= " AND b.fk_statut = 0 AND b.rowid = ".$object->lines[0]->fk_propal."";
$resql = $this->db->query($sql1);
if ($resql) {
	$numrows = $this->db->num_rows($resql);
	if ($numrows > 0){
		print '<td class="center" style="width: 20px">'.$langs->trans('PriceBuy').'</td>';
	}
}
// Multicurrency
if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) print '<td class="linecoluht_currency right" style="width: 80px">'.$langs->trans('PriceUHTCurrency', $this->multicurrency_code).'</td>';

if ($inputalsopricewithtax){
    if($object->element == 'order_supplier' || $object->element == 'supplier_proposal' || $object->element=='invoice_supplier'){

    }else{
    print '<td class="right" style="width: 20px">'.$langs->trans('PriceUTTC').'</td>';
    }
}




if($conf->global->PRODUCT_USE_UNITS)
{
	print '<td class="linecoluseunit left" style="width:20px;">'.$langs->trans('Unit').'</td>';
}

// Reduction short
print '<td class="linecoldiscount right" style="width:20px;">'.$langs->trans('ReductionShort').'</td>';

// Reduction amount
// print '<td class="linecoldiscount right">'.$langs->trans('ReductionAmountShort').'</td>';

// Fields for situation invoice
if ($this->situation_cycle_ref) {
	print '<td class="linecolcycleref right">' . $langs->trans('Progress') . '</td>';
	print '<td class="linecolcycleref2 right">' . $langs->trans('TotalHT100Short') . '</td>';
}

/*if ($usemargins && ! empty($conf->margin->enabled) && empty($user->socid))
{
	if (!empty($user->rights->margins->creer))
	{
		if ($conf->global->MARGIN_TYPE == "1") {
			print '<td class="linecolmargin1 margininfos right" style="width: 80px">'.$langs->trans('BuyingPrice').'</td>';
		} else {
			print '<td class="linecolmargin1 margininfos right" style="width: 80px">'.$langs->trans('CostPrice').'</td>';
		}
	}

	if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous) {
		print '<td class="linecolmargin2 margininfos right" style="width: 50px">'.$langs->trans('MarginRate').'</td>';
	}
	if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous) {
		print '<td class="linecolmargin2 margininfos right" style="width: 50px">'.$langs->trans('MarkRate').'</td>';
	}
}*/
//Precio unitario con descuento
print '<td class="linecolht right" style="width:20px;">'.$langs->trans('P.U c/Dto').'</td>';
if($this->element == 'invoice_supplier'){

	$compd = 0;
    foreach ($this->lines as $key => $line){
        if(!empty($line->compta_discount))
            $compd = $line->compta_discount;
    }
    print '<td class="linecolht right" style="width:20px;" title="Descuento Financiero = '.$compd.'%">'.$langs->trans('Costo Real').'</td>';
}
// Total HT
print '<td class="linecolht right" style="width:20px;">' . $langs->trans('Importe') . '</td>';

// Multicurrency
if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) print '<td class="linecoltotalht_currency right">'.$langs->trans('TotalHTShortCurrency', $this->multicurrency_code).'</td>';

if ($outputalsopricetotalwithtax) print '<td class="right" style="width: 80px">'.$langs->trans('TotalTTCShort').'</td>';

print '<td class="" style="width: 5px;"></td>';  // No width to allow autodim

print '<td class="" style="width: 5px;"></td>';

print '<td class="" style="width: 5px;"></td>';

if($action == 'selectlines')
{
	print '<td class="linecolcheckall center">';
	print '<input type="checkbox" class="linecheckboxtoggle" />';
	print '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
	print '</td>';
}

print "</tr>\n";
print "</thead>\n";

print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
