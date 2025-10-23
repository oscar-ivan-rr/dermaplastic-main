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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template

use function Sabre\Uri\split;

if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

$usemargins = 0;
if (!empty($conf->margin->enabled) && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) $usemargins = 1;

if (empty($dateSelector)) $dateSelector = 0;
if (empty($forceall)) $forceall = 0;
if (empty($senderissupplier)) $senderissupplier = 0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax = 0;
if (empty($outputalsopricetotalwithtax)) $outputalsopricetotalwithtax = 0;

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

$coldisplay = 0; ?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr  id="row-<?php print $line->id?>" class="drag drop oddeven" <?php print $domData; ?> >
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"><?php $coldisplay++; ?><?php print ($i + 1); ?></td>
<?php } ?>
<?php if ($this->element == 'commande' && $this->statut == 0) { //Descuento?>
	<td><input type="checkbox" class="product_discount" name="line_<?=$line->id?>" value="<?=$line->id?>"></td>
<?php } ?>
<?php if ($this->element == 'propal' && $this->statut == 0) { ?>
	<td><input type="checkbox" class="product_discount ptp_checkbox" name="line_<?=$line->id?>" value="<?=$line->id?>"></td>
<?php } ?>
<?php if ($this->element == 'facture' && $this->statut == 0) { //Facturas Checkbox?>
	<td class="left"><input type="checkbox" class="product_discount ptp_checkbox" name="line_<?=$line->id?>" value="<?=$line->id?>"></td>
<?php } ?>
<?php if (($this->element == 'invoice_supplier' || $this->element == 'order_supplier' || $this->element == 'supplier_proposal') && $this->statut == 0) { //Cot a proveedor?>
	<td><input type="checkbox" class="product_discount ptp_checkbox" name="line_<?=$line->id?>" value="<?=$line->id?>"></td>
<?php } ?>
	<td class="linecoldescription"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>
<?php


//$info = (explode(' - ',$text));

if (($line->info_bits & 2) == 2) {
    print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$this->socid.'">';
	$txt = '';
	print img_object($langs->trans("ShowReduc"), 'reduc').' ';
	if ($line->description == '(DEPOSIT)') $txt = $langs->trans("Deposit");
	elseif ($line->description == '(EXCESS RECEIVED)') $txt = $langs->trans("ExcessReceived");
	elseif ($line->description == '(EXCESS PAID)') $txt = $langs->trans("ExcessPaid");
	//else $txt=$langs->trans("Discount");
	print $txt;
	print '</a>';
	if ($line->description)
	{
		if ($line->description == '(CREDIT_NOTE)' && $line->fk_remise_except > 0)
		{
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print ($txt ? ' - ' : '').$langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
		}
		elseif ($line->description == '(DEPOSIT)' && $line->fk_remise_except > 0)
		{
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print ($txt ? ' - ' : '').$langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
			// Add date of deposit
			if (!empty($conf->global->INVOICE_ADD_DEPOSIT_DATE))
			    print ' ('.dol_print_date($discount->datec).')';
		}
		elseif ($line->description == '(EXCESS RECEIVED)' && $objp->fk_remise_except > 0)
		{
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print ($txt ? ' - ' : '').$langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
		}
		elseif ($line->description == '(EXCESS PAID)' && $objp->fk_remise_except > 0)
		{
			$discount = new DiscountAbsolute($this->db);
			$discount->fetch($line->fk_remise_except);
			print ($txt ? ' - ' : '').$langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
		}
		else
		{
			print ($txt ? ' - ' : '').dol_htmlentitiesbr($line->description);
		}
	}
}
else
{
	$format = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE ? 'dayhour' : 'day';

    if ($line->fk_product > 0)
	{	
		$match = ''; // Prevent
		// Relations for lines (only invoice_supplier or order_supplier)
		if (in_array($object->element, array('invoice_supplier', 'order_supplier')) && !empty($relationLines)) {
			// Element with relation
			if ($object->element == 'invoice_supplier') {
				$elementRelation = 'order_supplier';
				$labelTrans = $langs->trans('Order');
			}
			elseif ($object->element == 'order_supplier') {
				$elementRelation = 'invoice_supplier';
				$labelTrans = $langs->trans('Bill');
			}
			$labelTrans = dol_strtolower($labelTrans);

			$prodid = (int)$line->fk_product; // Key for relation
			$infomatch = '<strong>'.$langs->trans("Match", 'con '.$labelTrans. ' de proveedor').'</strong><br>'; // Title tooltip
			$linked = count($object->linkedObjectsIds[$elementRelation]); // Number of linked orders
			$icon = 'error.png'; // Icon per default

			// If $prodid exist in relations, check totals
			if (key_exists($prodid, $relationLines)) {
				$relation = $relationLines[$prodid];

				if (($elementRelation == 'order_supplier' && $relation->qty == $line->qty) || $elementRelation == 'invoice_supplier') {
					$icon = 'tick.png';
					$infomatch .= $langs->trans("ProdMatchCorrectly");
				}
				// Differents
				else {
					$infomatch .= $langs->trans("NoMatchQty", $relation->qty, $linked, $line->qty);
				}
				unset($relationLines[$prodid]);
			}
			// Not exist in relation
			else $infomatch .= $langs->trans("ProductNotFound", $linked, $labelTrans);
			
			// Tooltip to print
			$match = $form->textwithpicto('', $infomatch, 1, $icon);
		}
		//REF EN UNA COLUMNA
		print $form->textwithtooltip($match . $text, $description, 3, '', '', $i, 0, (!empty($line->fk_parent_line) ?img_picto('', 'rightarrow') : ''));
	}
	else
	{
		//var_dump($line);
		if ($type == 1) $text = img_object($langs->trans('Service'), 'service');
		else $text = img_object($langs->trans('Product'), 'product');

		if (!empty($line->label)) {
			$text .= ' <strong>'.$line->label.'</strong>';
			print $form->textwithtooltip($text, dol_htmlentitiesbr($line->description), 3, '', '', $i, 0, (!empty($line->fk_parent_line) ?img_picto('', 'rightarrow') : ''));
		} else {
			if (!empty($line->fk_parent_line)) print img_picto('', 'rightarrow');
			if (preg_match('/^\(DEPOSIT\)/', $line->description)) {
				$newdesc = preg_replace('/^\(DEPOSIT\)/', $langs->trans("Deposit"), $line->description);
				print $text.' '.dol_htmlentitiesbr($newdesc);
			}
			// Warning for products not defined
			elseif ($object->element == 'invoice_supplier' && $line->notfound) {
				$helptool = '<strong>'.$langs->trans("ProductNotDefined").'</strong>';
				print $form->textwithpicto($text.' '.dol_htmlentitiesbr($line->description), $helptool, 1, 'warning');
			}
			else {
				print $text.' '.dol_htmlentitiesbr($line->description);
			}
		}
	}

	// Show date range
	if ($line->element == 'facturedetrec') {
		if ($line->date_start_fill || $line->date_end_fill) print '<br><div class="clearboth nowraponall">';
		if ($line->date_start_fill) print $langs->trans('AutoFillDateFromShort').': '.yn($line->date_start_fill);
		if ($line->date_start_fill && $line->date_end_fill) print ' - ';
		if ($line->date_end_fill) print $langs->trans('AutoFillDateToShort').': '.yn($line->date_end_fill);
		if ($line->date_start_fill || $line->date_end_fill) print '</div>';
	}
	else {
		if ($line->date_start || $line->date_end) print '<br><div class="clearboth nowraponall">'.get_date_range($line->date_start, $line->date_end, $format).'</div>';
		//print get_date_range($line->date_start, $line->date_end, $format);
	}

	// Add description in form
	if ($line->fk_product > 0 && !empty($conf->global->PRODUIT_DESC_IN_FORM))
	{
		//QUITAR DESCRIPCION OPCIONAL (REVISION)---
		//print (!empty($line->description) && $line->description != $line->product_label) ? '<br>'.dol_htmlentitiesbr($line->description) : '';
	}
}

if ($user->rights->fournisseur->lire && $line->fk_fournprice > 0)
{
    require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	$productfourn = new ProductFournisseur($this->db);
	$productfourn->fetch_product_fournisseur_price($line->fk_fournprice);
	print '<div class="clearboth"></div>';
	print '<span class="opacitymedium">'.$langs->trans('Supplier').' : </span>'.$productfourn->getSocNomUrl(1, 'supplier').' - <span class="opacitymedium">'.$langs->trans('Ref').' : </span>';
	// Supplier ref
	if ($user->rights->produit->creer || $user->rights->service->creer) // change required right here
	{
		print $productfourn->getNomUrl();
	}
	else
	{
		print $productfourn->ref_supplier;
	}
}

if (!empty($conf->accounting->enabled) && $line->fk_accounting_account > 0)
{
	$accountingaccount = new AccountingAccount($this->db);
	$accountingaccount->fetch($line->fk_accounting_account);
	print '<div class="clearboth"></div><br><span class="opacitymedium">'.$langs->trans('AccountingAffectation').' : </span>'.$accountingaccount->getNomUrl(0, 1, 1);
}
print '</td>';
//DESC EN OTRA COLUMNA
$coldisplay++;
print '<td ';
//var_dump($line);
if($line->tosell == 0 && $line->tobuy == 0)
{
	print 'style="color:red" ';
}
print 'class="nowrap">'.$label.'</td>';
?>
<td class="linecolqty nowrap right"><?php $coldisplay++; ?>
<?php
if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
	// I comment this because it shows info even when not required
	// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
	// must also not be output for most entities (proposal, intervention, ...)
	//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
	print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
	if( $this->table_element_line == 'propaldet' && $this->statut == 2  && $line->qty > $line->stock_sign){
		$text = "<center><b>Stock Insuficiente</b></center><br>";
		$text .= "Stock al ".date('d/m/Y', $this->date_cloture) ." = ". $line->stock_sign ."<br>";
		$text .= "Stock por Pedir = ".($line->qty - $line->stock_sign)."<br>";
		print $form->textwithpicto('', $text, 1, 'warning');
	}
} else print '&nbsp;';
print '</td>';

//UNIDAD ENTRADA/SALIDA
if($this->table_element != 'pos_ticket'){
	$coldisplay++;
	print '<td class="linecolunit right">';
	if(!empty($line->fk_unit)){
		$sqlUnidad = "SELECT label FROM ".MAIN_DB_PREFIX."c_units WHERE active = 1 AND scale = ".$line->fk_unit;
		$resqlUnidad = $this->db->query($sqlUnidad);
		if ($resqlUnidad)
		{
			$objUnidad = $this->db->fetch_object($resqlUnidad);
			print $objUnidad->label;
		}
	}else{
		//SALIDA
		if($this->table_element_line == 'facturedet' || $this->table_element_line == 'commandedet' || $this->table_element_line == 'propaldet'){ 
			$sqlSalida = "SELECT unit_salida as unidad FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$line->fk_product;
			$resqlSalida = $this->db->query($sqlSalida);
			if ($resqlSalida)
			{
				$objU = $this->db->fetch_object($resqlSalida);
			}
		}
		//ENTRADA
		if($this->table_element_line == 'facture_fourn_det' || $this->table_element_line == 'commande_fournisseurdet' || $this->table_element_line == 'supplier_proposaldet'){
			$sqlEntrada = "SELECT unit_entrada as unidad FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".$line->fk_product;
			$resqlEntrada = $this->db->query($sqlEntrada);
			if ($resqlEntrada)
			{
				$objU = $this->db->fetch_object($resqlEntrada);
			}
		}
		$sqlUnidad = "SELECT label FROM ".MAIN_DB_PREFIX."c_units WHERE active = 1 AND scale = ".$objU->unidad;
		$resqlUnidad = $this->db->query($sqlUnidad);
		if ($resqlUnidad)
		{
			$objUnidad = $this->db->fetch_object($resqlUnidad);
			print $objUnidad->label;
		}
	}
	print '</td>';
}

/*
if($this->table_element_line == 'facturedet' || $this->table_element_line == 'commandedet' || $this->table_element_line == 'propaldet'
|| $this->table_element_line == 'facture_fourn_det' || $this->table_element_line == 'commande_fournisseurdet' || $this->table_element_line == 'supplier_proposaldet'){
    //Unidad de Entrada/Salida
    if($this->table_element_line == 'facturedet') $fieldkey='fk_facture';
    if($this->table_element_line == 'commandedet' || $this->table_element_line == 'commande_fournisseurdet') $fieldkey='fk_commande';
    if($this->table_element_line == 'propaldet') $fieldkey='fk_propal';
    if($this->table_element_line == 'facture_fourn_det') $fieldkey='fk_facture_fourn';
    if($this->table_element_line == 'supplier_proposaldet') $fieldkey='fk_supplier_proposal';
    $sql = "SELECT cu.label FROM ".MAIN_DB_PREFIX."c_units cu";
    if($this->table_element_line == 'facturedet' || $this->table_element_line == 'commandedet' || $this->table_element_line == 'propaldet')
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.unit_salida=cu.scale";
    else if($this->table_element_line == 'facture_fourn_det' || $this->table_element_line == 'commande_fournisseurdet' || $this->table_element_line == 'supplier_proposaldet')
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.unit_entrada=cu.scale";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX.$this->table_element_line." fd ON fd.fk_product=p.rowid";
    $sql.= " WHERE fd.".$fieldkey."=".$this->id;
    $sql.= " AND fd.rowid = ".$line->id;
    $result = $this->db->query($sql);

    if($result){
        print '<td class="linecolunit right">';
        print $this->db->fetch_object($result)->label;
        print '</td>';
    }
    else{
        print '<td class="linecolunit right">';
        print '&nbsp;';
        print '</td>';
    }
}
*/

//if (/*$line->element == 'propaldet' /*|| $line->element == 'supplier_proposaldet'||*/ $line->element == 'order_supplier'  /*|| $this->table_element_line == 'supplier_proposaldet'*/) {
//  $coldisplay++;
//	print '<td class="linecoluht nowrap right">'.$line->unit.'</td>';
//}
//if (/*$object->element == 'supplier_proposal' || $object->element == 'order_supplier' ||*/ $object->element == 'invoice_supplier')	// We must have same test in printObjectLines
//{
//	print '<td class="linecolrefsupplier">';
//	print ($line->ref_fourn ? $line->ref_fourn : $line->ref_supplier);
//	print '</td>';
//}

// VAT Rate
//SE OCULTA LA COLUMNA IVA

//print '<td class="linecolvat nowrap right">';
//$coldisplay++;
$positiverates = '';
if (price2num($line->tva_tx))          $positiverates .= ($positiverates ? '/' : '').price2num($line->tva_tx);
if (price2num($line->total_localtax1)) $positiverates .= ($positiverates ? '/' : '').price2num($line->localtax1_tx);
if (price2num($line->total_localtax2)) $positiverates .= ($positiverates ? '/' : '').price2num($line->localtax2_tx);
if (empty($positiverates)) $positiverates = '0';
//print vatrate($positiverates.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), '%', $line->info_bits);

//print vatrate($line->tva_tx.($line->vat_src_code?(' ('.$line->vat_src_code.')'):''), '%', $line->info_bits);
?>

<?php 

//PRECIO UNITARIO?>	
	<td class="linecoluht nowrap right"><?php $coldisplay++; ?><?php print price($line->subprice); ?></td>

<?php 
if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) { ?>
	<td class="linecoluht_currency nowrap right"><?php $coldisplay++; ?><?php print price($line->multicurrency_subprice); ?></td>
<?php }

if ($inputalsopricewithtax) {
    if($object->element == 'order_supplier' ||$object->element == 'supplier_proposal' || $object->element=='invoice_supplier'){
    }else{
    ?>
	<td class="linecoluttc nowrap right"><?php $coldisplay++; ?><?php print (isset($line->pu_ttc) ?price($line->pu_ttc) : price($line->subprice)); ?></td>
<?php }} ?>


<?php


if ($conf->global->PRODUCT_USE_UNITS)
{
	print '<td class="linecoluseunit nowrap left">';
	$label = $line->getLabelOfUnit('short');
	if ($label !== '') {
		print $langs->trans($label);
	}
	print '</td>';
}

#PRECIO COMPRA
$banPC = false;
$sql3 = "SELECT a.fk_source FROM ".MAIN_DB_PREFIX."element_element AS a, ".MAIN_DB_PREFIX."propal AS b ";
$sql3 .= " WHERE a.fk_target = ".$object->lines[0]->fk_propal." AND a.targettype = 'propal' AND a.sourcetype = 'supplier_proposal' ";
$sql3 .= " AND b.fk_statut = 0 AND b.rowid = ".$object->lines[0]->fk_propal."";
$resql3 = $this->db->query($sql3);
if ($resql3) {
	$numrows3 = $this->db->num_rows($resql);
	if ($numrows3 > 0){
		print '<td class="nowrap center">';
		$banPC = true;
	}
}

	$sql1 =	 "SELECT a.fk_source, c.total_ht, c.qty, d.ref, e.nom";
	$sql1 .= " FROM ".MAIN_DB_PREFIX."element_element AS a, ".MAIN_DB_PREFIX."propal AS b, ".MAIN_DB_PREFIX."supplier_proposaldet AS c,"; 
	$sql1 .= " ".MAIN_DB_PREFIX."supplier_proposal AS d, ".MAIN_DB_PREFIX."societe AS e";
	$sql1 .= " WHERE a.fk_target = ".$line->fk_propal." AND a.targettype = 'propal' AND a.sourcetype = 'supplier_proposal'";
	$sql1 .= " AND b.fk_statut = 0 AND b.rowid = ".$line->fk_propal." AND c.fk_supplier_proposal = a.fk_source AND d.rowid = a.fk_source";
	$sql1 .= " AND c.fk_product = ".$line->fk_product." AND d.fk_soc = e.rowid AND d.fk_statut = 2";
	$resql = $this->db->query($sql1);
    if ($resql) {
		$numrows = $this->db->num_rows($resql);
		if ($numrows > 0){
			print '<select name="selectPC" id="selectPC_'.$line->id.'" style= "width: 100px;">';
			while($obj1 = $this->db->fetch_object($resql)){
				$sql2 = "SELECT p.gain";
				$sql2.= " FROM ".MAIN_DB_PREFIX."product as p";
				$sql2.= " WHERE p.rowid=".$line->fk_product;
				$resql2 = $this->db->query($sql2);
				if ($resql2){
					$obj2 = $this->db->fetch_object($resql2);
					$gain = $obj2->gain;
					//FORMULA PRECIO VENTA = Precio de Compra / (1-(Margen de Ganancia/100))
					if($gain == null){
						$gain = 1-(30/100);
					}else{
						if($gain == 0){
							$gain = 1-(30/100);
						}else{
							$gain = 1-($gain/100);
						}
					}
					//$sub = $obj1->total_ht / $gain;
					$sub = ($obj1->total_ht / $obj1->qty) / $gain;
					
				}
				if(price($sub) == price($line->subprice)){
					print '<option style= "width: 100px; overflow: hidden;" value="'.$sub.'" selected>$'.price($sub).'/'.$obj1->nom.'/'.$obj1->ref.'</option>';
				}else{
					print '<option style= "width: 100px; overflow: hidden;" value="'.$sub.'">$'.price($sub).'/'.$obj1->nom.'/'.$obj1->ref.'</option>';
				}
			}					
			print '</select>';
			print '<a id="ref'.$line->id.'">';
			print img_refresh();
			print '</a>';
			print '<script>
			$(document).ready(function(){
				$("#ref'.$line->id.'").click(function(){
					window.location.href = "'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&producto='.$line->id.'&selectPC="+$("#selectPC_'.$line->id.'").val();
				});
			});
			</script>';
		}
	}
	if($banPC){
		print '</td>';
	}

if (!empty($line->remise_percent) && $line->special_code != 3) {
	print '<td class="linecoldiscount  right">';
	$coldisplay++;
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	print '<span class="classfortooltip" title="';
	print $langs->trans("ReductionAmountShort").' = '.dol_print_reduction_amount((float) $line->remise_percent, (float) $line->subprice, (float) $line->qty, $langs);
	print '">';
	print dol_print_reduction($line->remise_percent, $langs);
	print '</span></td>';

	// print '<td class="linecoldiscountamount right">';
	// print dol_print_reduction_amount((float) $line->remise_percent, (float) $line->subprice, (float) $line->qty, $langs);
	// print '</td>';
} else {
	print '<td class="linecoldiscount">&nbsp;</td>';
	$coldisplay++;
}
//aqui va lo nuevo
$desc = 1-($line->remise_percent/100);
$pu= $line->subprice;
print '<td class ="linecolhy nowrap right">'.price($pu*$desc).'</td>';

// Fields for situation invoices
if ($this->situation_cycle_ref)
{
    include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
	$coldisplay++;
	print '<td class="linecolcycleref nowrap right">'.$line->situation_percent.'%</td>';
	$coldisplay++;
	$locataxes_array = getLocalTaxesFromRate($line->tva.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), 0, ($senderissupplier ? $mysoc : $object->thirdparty), ($senderissupplier ? $object->thirdparty : $mysoc));
	$tmp = calcul_price_total($line->qty, $line->pu, $line->remise_percent, $line->txtva, -1, -1, 0, 'HT', $line->info_bits, $line->type, ($senderissupplier ? $object->thirdparty : $mysoc), $locataxes_array, 100, $object->multicurrency_tx, $line->multicurrency_subprice);
	print '<td align="right" class="linecolcycleref2 nowrap">'.price($tmp[0]).'</td>';
}

/*if ($usemargins && !empty($conf->margin->enabled) && empty($user->socid))
{
	if (!empty($user->rights->margins->creer)) { ?>
		<td class="linecolmargin1 nowrap margininfos right"><?php $coldisplay++; ?><?php print price($line->pa_ht); ?></td>
	<?php }
	if (!empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous) { ?>
		<td class="linecolmargin2 nowrap margininfos right"><?php $coldisplay++; ?><?php print (($line->pa_ht == 0) ? 'n/a' : price(price2num($line->marge_tx, 'MT')).'%'); ?></td>
	<?php }
    if (!empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous) {?>
  	  <td class="linecolmargin2 nowrap margininfos right"><?php $coldisplay++; ?><?php print price(price2num($line->marque_tx, 'MT')).'%'; ?></td>
    <?php }
}*/
if($line->element == 'facture_fourn_det'){
    print '<td class="linecolht nowrap right">'.price($line->real_cost).'</td>';
    $coldisplay++;
}
if ($line->special_code == 3) { ?>
	<td class="linecoloption nowrap right"><?php $coldisplay++; ?><?php print $langs->trans('Option'); ?></td>
<?php } else {
	print '<td class="linecolht nowrap right">';
	$coldisplay++;
	if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
	{
    	print '<span class="classfortooltip" title="';
    	print $langs->transcountry("TotalHT", $mysoc->country_code).'='.price($line->total_ht);
    	print '<br>'.$langs->transcountry("TotalVAT", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)).'='.price($line->total_tva);
    	if (price2num($line->total_localtax1)) print '<br>'.$langs->transcountry("TotalLT1", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)).'='.price($line->total_localtax1);
    	if (price2num($line->total_localtax2)) print '<br>'.$langs->transcountry("TotalLT2", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)).'='.price($line->total_localtax2);
    	print '<br>'.$langs->transcountry("TotalTTC", $mysoc->country_code).'='.price($line->total_ttc);
    	print '">';
	}
	//IMPORTE
	print price($line->total_ht);
	if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
	{
	    print '</span>';
	}
	print '</td>';
	if (!empty($conf->multicurrency->enabled) && $this->multicurrency_code != $conf->currency) {
		print '<td class="linecolutotalht_currency nowrap right">'.price($line->multicurrency_total_ht).'</td>';
		$coldisplay++;
	}
}
if ($outputalsopricetotalwithtax) {
    print '<td class="linecolht nowrap right">'.price($line->total_ttc).'</td>';
	$coldisplay++;
}

if ($this->statut == 0 && ($object_rights->creer) && $action != 'selectlines') {
	print '<td class="linecoledit center">';
	$coldisplay++;
	if (($line->info_bits & 2) == 2 || !empty($disableedit)) {
	} else { ?>
		<a href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
		<?php print img_edit().'</a>';
	}
	print '</td>';

	print '<td class="linecoldelete center">';
	$coldisplay++;
	if (($line->fk_prev_id == null) && empty($disableremove)) { //La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id.'">';
		print img_delete();
		print '</a>';
	}
	print '</td>';

	if ($num > 1 && $conf->browser->layout != 'phone' && ($this->situation_counter == 1 || !$this->situation_cycle_ref) && empty($disablemove)) {
		print '<td class="linecolmove tdlineupdown center">';
		$coldisplay++;
		if ($i > 0) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id; ?>">
			<?php print img_up('default', 0, 'imgupforline'); ?>
			</a>
		<?php }
		if ($i < $num - 1) { ?>
			<a class="lineupdown" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id; ?>">
			<?php print img_down('default', 0, 'imgdownforline'); ?>
			</a>
		<?php }
		print '</td>';
    } else {
		print '<td '.(($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"').'></td>';
		$coldisplay++;
	}
} else {
	print '<td colspan="3"></td>';
	$coldisplay = $coldisplay + 3;
}

if ($action == 'selectlines') { ?>
	<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
<?php }

print "</tr>\n";

//Line extrafield
if (!empty($extrafields))
{
	print $line->showOptionals($extrafields, 'view', array('style'=>'class="drag drop oddeven"', 'colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD) ? 0 : 1);
}
print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
