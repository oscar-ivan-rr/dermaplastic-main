<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 */
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/class/Facturation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Load translation files required by the page
$langs->loadLangs(array("main","bills","cashdesk"));

// Object $form must de defined

// Obtener categorías
$sql = "SELECT rowid as id, label FROM llx_categorie WHERE type = 0 AND fk_parent = 'NULL' ORDER BY label";
$resql = $db->query($sql);

// Obtener padecimiento
$sql = "SELECT label FROM llx_condition WHERE rowid = '".$_SESSION['condition_id']."'";
$resql2 = $db->query($sql);
$condition = $db->fetch_object($resql2);

?>

<script type="text/javascript" src="javascript/facturation1.js"></script>
<script type="text/javascript" src="javascript/dhtml.js"></script>
<script type="text/javascript" src="javascript/keypad.js"></script>

<!-- ========================= Cadre "Article" ============================= -->
<fieldset class="cadre_facturation"><legend class="titre1"><?php echo $langs->trans("Article"); ?></legend>
	
	<form id="frmFacturation" class="formulaire1" method="post" action="facturation_verif.php" autocomplete="off">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>" />

		<input type="hidden" name="hdnSource" value="NULL" />
		<?php 
		// Mostrar la opción de filtrado por padecimiento
			if($_SESSION['condition_id']){
				if($_SESSION['use_condition'] == 1) $checked = 'checked';
				else $checked = '';
				print '<div  class="center">
				<p class="inline-block label2">Filtrar por padecimiento "'.$condition -> label.'"</p><span><input type="checkbox" name="condition_filer" id="condition_filter" '.$checked.'/></span>
				</div>';
			}
		?>
		

		<table class="center">
			<tr><th class="label2"><?php echo $langs->trans("Category"); ?></th><th class="label2"><?php echo $langs->trans("Designation"); ?></th></tr>
			<tr>
			<!-- Affichage de la reference et de la designation -->
			<!-- Suppression de l'attribut onkeyup qui causait un probleme d'emulation avec les douchettes -->
			<td class="select_design maxwidthonsmartphone">
				<select class="maxwidthonsmartphone texte_ref2" type="text" id ="txtRef" name="txtRef" onchange="javascript: setSource('REF');">
				<?php
					print '<option value=""></option>';

					while ($obj = $db->fetch_object($resql)){
						if ($obj->id == $tab_designations[$i]['fk_categorie']) {
							$selected = 'selected';
						} else {
							$selected = '';
						}
						print '<option class="texte_bold" '.$selected.' value="'.$obj->id.'">'.$obj->label.'</option>';

						// Obtener subcategorías
						$sql2 = "SELECT rowid as id, label FROM llx_categorie WHERE type = 0 AND fk_parent = '".$obj->id."' ORDER BY label";
						$resql2 = $db->query($sql2);
						while ($obj2 = $db->fetch_object($resql2)){
							if ($obj2->id == $tab_designations[$i]['fk_categorie']) {
								$selected = 'selected';
							} else {
								$selected = '';
							}
							print '<option class="" '.$selected.' value="'.$obj2->id.'">&nbsp;&nbsp;&nbsp;'.$obj->label.' >> '.$obj2->label.'</option>';
	
						}
					}

				?>
				</select>
			</td>
			<td class="select_design maxwidthonsmartphone">
				<select id="selProduit" class="maxwidthonsmartphone texte_ref2" name="selProduit" onchange="javascript: setSource('LISTE');">
<?php
print '<option value="0">'.$top_liste_produits.'</option>'."\n";

$id = $obj_facturation->id();

// Si trop d'articles ont ete trouves, on n'affiche que les X premiers (defini dans le fichier de configuration) ...

$nbtoshow = $nbr_enreg;
if (! empty($conf_taille_listes) && $nbtoshow > $conf_taille_listes) $nbtoshow = $conf_taille_listes;

for ($i = 0; $i < $nbtoshow; $i++)
{
	if ($id == $tab_designations[$i]['rowid']) {
		$selected = 'selected';
	} else {
		$selected = '';
	}

	$label = $tab_designations[$i]['label'];

	print '<option '.$selected.' value="'.$tab_designations[$i]['rowid'].'">'.$tab_designations[$i]['ref'].'';
	if (! empty($conf->stock->enabled) && !empty($conf_fkentrepot) && $tab_designations[$i]['fk_product_type']==0) {
		print ' ('.$langs->trans("CashDeskStock").': '.(empty($tab_designations[$i]['reel'])?0:$tab_designations[$i]['reel']).')';
	}
	print '</option>'."\n";
}
?>
				</select>
			</td>
			</tr>
		</table>
	</form>

	<form id="frmQte" class="formulaire1" method="post" action="facturation_verif.php?action=ajout_article" onsubmit ="javascript: return verifSaisie();">
		<input type="hidden" name="token" value="<?php echo newToken(); ?>" />
		<table class="center">
			<tr class="texte3">
			<th ><?php echo $langs->trans("Qty"); ?></th>
			<th><?php echo $langs->trans("PriceUHT"); ?></th>
			<th><?php echo $langs->trans("Discount"); ?> (%)</th>
			<th></th>
            </tr>
			<tr class="texte3">

				<td><input class="texte1 maxwidth50onsmartphone" type="text" id="txtQte" name="txtQte" value="1" onkeyup="javascript: modif();" onfocus="javascript: this.select();" />
<?php print genkeypad("txtQte", "frmQte");?>
				</td>
				<!-- Show unit price -->
				<?php // TODO Remove the disabled and use this value when adding product into cart ?>
				<td><input class="texte1_off maxwidth50onsmartphone" type="text" name="txtPrixUnit" value="<?php echo price2num($obj_facturation->prix(), 'MU'); ?>" onchange="javascript: modif();" disabled /></td>
    			<!-- Choix de la remise -->
				<?php
					$buyer2 = new Societe($db);
					if ($_SESSION["CASHDESK_ID_THIRDPARTY"] > 0) $buyer2->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);
					print '<td><input readOnly=true class="texte1_off maxwidth50onsmartphone" type="text" id="txtRemise" name="txtRemise" value="'.$buyer2->remise_percent.'" onkeyup="javascript: modif();" onfocus="javascript: this.select();"/></td>';
					print genkeypad("txtRemise", "frmQte");
			    ?>
                <!-- Choix du taux de TVA -->
                <td class="select_tva center">
                <?php
					$vatrate = $obj_facturation->vatrate;      // To get vat rate we just have selected

					$buyer = new Societe($db);
					if ($_SESSION["CASHDESK_ID_THIRDPARTY"] > 0) $buyer->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);
					print '<div style="display:none !important;">'.$form->load_tva('selTva', (isset($_POST["selTva"])?GETPOST("selTva", 'alpha', 2):$vatrate), $mysoc, $buyer, 0, 0, '', false, -1).'</div>';
			    ?>
                </td>
				<td></td>
			</tr>
		</table>
		<?php 
			$prod = new Product($db);
			$prod->fetch($id);
			// Validar si el producto está fuera de compra
			if($prod->status_buy == 1 || $prod->status_buy == null){
				print '<div class="centerdiv">
				<input class="button bouton_ajout_article" type="submit" id="sbmtEnvoyer" value="'.$langs->trans("AddThisArticle").'" />
				</div>';
			} else{
				print '<h1 class="center msgerror">Producto descontinuado por el proveedor</h1>';
			}
		?>
	</form>
</fieldset>

<div class="inline-block">
<?php
print '<div class="inline-block">';
print '<div class="liste_articles">';
require 'tpl/liste_articles.tpl.php';
print '</div>';
?>

<!-- ========================= Cadre "Amount" ============================= -->
<div class="inline-block">
<form id="frmDifference"  class="formulaire3" method="post" onsubmit="javascript: return verifReglement()" action="validation_verif.php?action=valide_achat">
	<input type="hidden" name="hdnChoix" value="" />
	<input type="hidden" name="token" value="<?php echo newToken(); ?>" />

<input class="texte2_off maxwidth100onsmartphone" type="hidden" name="txtDu" value="<?php echo price2num($obj_facturation->prixTotalTtc(), 'MT'); ?>" disabled />
<input class="texte2 maxwidth100onsmartphone" type="hidden" id="txtEncaisse" name="txtEncaisse" value="" onkeyup="javascript: verifDifference();" onfocus="javascript: this.select();" />
<?php print genkeypad("txtEncaisse", "frmDifference");?>
<input class="texte2_off maxwidth100onsmartphone" type="hidden" name="txtRendu" value="0" disabled /></td>

<?php
print '<div style="margin-top: 20px;">';
print '<input class="button bouton_mode_reglement" type="submit" name="btnModeReglement" value="'.$langs->trans("Crear receta").'" onclick="javascript: verifClic(\'\');" />';
print '</div>';
?>
</form>
</div>
</div>

<script type="text/javascript">
/*	Calendar.setup ({
		inputField	: "txtDatePaiement",
		ifFormat	: "%Y-%m-%d",
		button		: "btnCalendrier"
	});
*/
	if (document.getElementById('frmFacturation').txtRef.value) {

		modif();
		document.getElementById('frmQte').txtQte.focus();
		document.getElementById('frmQte').txtQte.select();

	} else {

		document.getElementById('frmFacturation').txtRef.focus();

	}

	$(document).ready(function() {
		// Cambia el valor del uso de padecimiento para modificar consulta que muestra el listado del buscador
        $('#condition_filter').click(function() {
			if($(this).is(':checked')){
				$.ajax({
					url: 'ajax/ajax_condition.php',
					type: 'POST',
					data: {
						use_condition: 1
					},
					success: function(response) {
						window.location.href = '/cashdesk/affIndex.php?menutpl=facturation&id=NOUV';
					},
				});
			} else {
				$.ajax({
					url: 'ajax/ajax_condition.php',
					type: 'POST',
					data: {
						use_condition: 0
					},
					success: function(response) {
						window.location.href = '/cashdesk/affIndex.php?menutpl=facturation&id=NOUV';
					},
				});
			}
        });
    });

</script>
