<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
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

// Protection to avoid direct call of template

use Stripe\Customer;

if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

/*if (!empty($_SESSION["CASHDESK_ID_THIRDPARTY"]))
{
	$company=new Societe($db);
	$company->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);
	$companyLink = $company->getNomUrl(1);
}*/
if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]))
{
	$bankcash=new Account($db);
	$bankcash->fetch($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]);
	$bankcash->label=$bankcash->ref;
	$bankcashLink = $bankcash->getNomUrl(1);
}
if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]))
{
	$bankcb=new Account($db);
	$bankcb->fetch($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]);
	$bankcbLink = $bankcb->getNomUrl(1);
}
if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]))
{
	$bankcheque=new Account($db);
	$bankcheque->fetch($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]);
	$bankchequeLink = $bankcheque->getNomUrl(1);
}
if (!empty($_SESSION["CASHDESK_ID_WAREHOUSE"]) && ! empty($conf->stock->enabled))
{
	$warehouse=new Entrepot($db);
	$warehouse->fetch($_SESSION["CASHDESK_ID_WAREHOUSE"]);
	$warehouseLink = $warehouse->getNomUrl(1);
}

// Load translation files required by the page
$langs->loadLangs(array("main","cashdesk"));

$patient = new Societe($db);
$patient->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);

print "\n".'<!-- menu.tpl.php -->'."\n";
print '<div class="menu_bloc">';
print '<ul class="menu">';
// Link to new sell
print '<li style="margin-top: 16px;"><a style="color: white; font-size: 1rem;" id="new" class="button btnmenu1"><b>'.$langs->trans("Nueva receta").'<b></a></li>';

// Open new tab on backoffice (this is not a disconnect from POS)
// Disconnect
print '<li class="menu_choix0"><div class="cashdeskloginuser marginbottomonly valignmiddle"><div class="inline-block valignmiddle">'.$langs->trans("User").': '.$_SESSION['firstname'].' '.$_SESSION['lastname'].'</div>';
print '<div class="inline-block valignmiddle"> <a style="border-radius: 50%; height: 20px; width: 15px;" class="button" href="deconnexion.php">'.img_picto($langs->trans('Logout'), 'logout.png').'</a></div></div></li>';
print '<li class="menu_choix01">
<div class="inline-block valignmiddle">
<p class="textPatient inline-block valignmiddle">Paciente: </p>
<input class="patient" type="text" readOnly="true" value="'.$patient -> name.'">
<button class="button btnmenu1" onclick="popupPatient()">'.$langs->trans("Modify").'</button>
<button class="button btnpatientnew" onclick="popupNewPatient()">+</button></li>
</div>';
print '<li class="menu_choix01">';

print '<li class="menu_choix01"><div class="inline-block valignmiddle">';
print '<button class="button btnmenu_condition" onclick="popupCondition()">'.$langs->trans("Condition").'</button></div></div>';
print '<li class="menu_choix01">';
?>

<link rel="stylesheet" href="/cashdesk/css/style.css">
<link rel="stylesheet" href="/cashdesk/css/colorbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="/cashdesk/javascript/jquery.colorbox-min.js"></script>	<!-- TODO It seems we don't need this -->
<script language="javascript">

function popupPatient()
{
	$.colorbox({href:"/cashdesk/patient.php", width:"70%", height:"70%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Paciente"); ?>"});
}

function popupNewPatient()
{
	$.colorbox({href:"/cashdesk/newpatient.php", width:"80%", height:"650px", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Nuevo Paciente"); ?>"});
}

function popupCondition()
{
	$.colorbox({href:"/cashdesk/medical_condition.php", width:"50%", height:"650px", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Condition"); ?>"});
}

$(document).ready(function() {
	$("#new").on("click",function() {
		$.ajax({
			url: 'ajax/ajax_condition.php',
			type: 'POST',
			data: {
				new: 'new'
			},
			success: function(response) {
				window.location.href = '/cashdesk/affIndex.php?menutpl=facturation&id=NOUV';
			},
		});
	});
});
</script>

<?php
/*print $langs->trans("CashDeskBankCash").': '.$bankcashLink.'<br>';
print $langs->trans("CashDeskBankCB").': '.$bankcbLink.'<br>';
print $langs->trans("CashDeskBankCheque").': '.$bankchequeLink.'<br>';*/
print '<div class="clearboth">';
if (!empty($_SESSION["CASHDESK_ID_WAREHOUSE"]) && ! empty($conf->stock->enabled) && empty($conf->global->CASHDESK_NO_DECREASE_STOCK))
{
	print $langs->trans("CashDeskWarehouse").': '.$warehouseLink;
}
print '</div></li></ul>';
print '</div>';
print "\n".'<!-- menu.tpl.php end -->'."\n";
