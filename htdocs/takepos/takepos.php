<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	Josep Lluís Amador	<joseplluis@lliuretic.cat>
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
 *	\file       htdocs/takepos/takepos.php
 *	\ingroup    takepos
 *	\brief      Main TakePOS screen
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Bar or Restaurant
$action = GETPOST('action', 'alpha');
$setterminal = GETPOST('setterminal', 'int');

if ($setterminal > 0)
{
	$_SESSION["takeposterminal"] = $setterminal;
}

$_SESSION["urlfrom"] = '/takepos/takepos.php';

$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk", "receiptprinter"));

$categorie = new Categorie($db);

$maxcategbydefaultforthisdevice = 12;
$maxproductbydefaultforthisdevice = 24;
if ($conf->browser->layout == 'phone')
{
    $maxcategbydefaultforthisdevice = 8;
    $maxproductbydefaultforthisdevice = 16;
	//REDIRECT TO BASIC LAYOUT IF TERMINAL SELECTED AND BASIC MOBILE LAYOUT ENABLED
	if ($_SESSION["takeposterminal"] != "" && $conf->global->TAKEPOS_PHONE_BASIC_LAYOUT == 1)
	{
		$_SESSION["basiclayout"] = 1;
		header("Location: phone.php?mobilepage=invoice");
		exit;
	}
}
$MAXCATEG = (empty($conf->global->TAKEPOS_NB_MAXCATEG) ? $maxcategbydefaultforthisdevice : $conf->global->TAKEPOS_NB_MAXCATEG);
$MAXPRODUCT = 24;

/*
$constforcompanyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
$soc = new Societe($db);
if ($invoice->socid > 0) $soc->fetch($invoice->socid);
else $soc->fetch($conf->global->$constforcompanyid);
*/

// Security check
$result = restrictedArea($user, 'takepos', 0, '');


/*
 * View
 */

// Title
$title = 'Punto de Venta';
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = 'TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
print '<input type="hidden" id="globalpass" value="'.$conf->global->POS_ADMIN_PASSWORD.'" />';

?>
<link rel="stylesheet" href="css/pos.css">
<link rel="stylesheet" href="css/colorbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>	<!-- TODO It seems we don't need this -->
<style>
/* Styles for the terminal in use dialog */
#terminal-in-use-dialog {
	font-family: Arial, sans-serif;
}
.terminal-dialog-content {
	text-align: center;
	padding: 15px;
}
.terminal-icon {
	font-size: 48px;
	color: #e74c3c;
	margin: 15px 0;
}
.terminal-dialog-content h3 {
	color: #e74c3c;
	margin: 10px 0;
}
.terminal-dialog-content p {
	color: #333;
	margin: 10px 0;
	line-height: 1.5;
}
.ui-dialog .ui-dialog-buttonpane button.btn-primary {
	background: #3498db;
	color: white;
	border: none;
	padding: 8px 16px;
	border-radius: 4px;
	cursor: pointer;
	font-weight: bold;
	transition: background 0.3s;
}
.ui-dialog .ui-dialog-buttonpane button.btn-primary:hover {
	background: #2980b9;
}

.terminal-dialog {
	font-family: 'Roboto', Arial, sans-serif;
	border-radius: 10px;
	overflow: hidden;
}

.terminal-selector-container {
	padding: 20px;
	background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.terminal-selector-title {
	text-align: center;
	margin-bottom: 20px;
	color: #2c3e50;
	font-size: 1.5em;
	font-weight: 500;
}

.terminal-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
	gap: 15px;
	margin-top: 20px;
}

.terminal-button {
	background: white;
	border: none;
	border-radius: 10px;
	padding: 15px 10px;
	transition: all 0.3s ease;
	cursor: pointer;
	display: flex;
	flex-direction: column;
	align-items: center;
	box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.terminal-button:hover {
	transform: translateY(-5px);
	box-shadow: 0 7px 10px rgba(0, 0, 0, 0.15);
}

.terminal-button.active {
	background: #3498db;
	color: white;
}

.terminal-icon {
	font-size: 24px;
	margin-bottom: 8px;
}

.terminal-name {
	font-weight: 500;
	font-size: 14px;
	text-align: center;
}
</style>
<script language="javascript">
<?php
$categories = $categorie->get_full_arbo('product', (($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0) ? $conf->global->TAKEPOS_ROOT_CATEGORY_ID : 0), 1);


// Search root category to know its level
//$conf->global->TAKEPOS_ROOT_CATEGORY_ID=0;
$levelofrootcategory = 0;
if ($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0)
{
    foreach ($categories as $key => $categorycursor)
    {
        if ($categorycursor['id'] == $conf->global->TAKEPOS_ROOT_CATEGORY_ID)
        {
            $levelofrootcategory = $categorycursor['level'];
            break;
        }
    }
}
$levelofmaincategories = $levelofrootcategory + 1;

$maincategories = array();
$subcategories = array();
foreach ($categories as $key => $categorycursor)
{
    if ($categorycursor['level'] == $levelofmaincategories)
    {
        $maincategories[$key] = $categorycursor;
    }
    else
    {
        $subcategories[$key] = $categorycursor;
    }
}

sort($maincategories);
sort($subcategories);
?>

var categories = <?php echo json_encode($maincategories); ?>;
var subcategories = <?php echo json_encode($subcategories); ?>;

var currentcat;
var pageproducts=0;
var pagecategories=0;
var pageactions=0;
var place="<?php echo $place; ?>";
var editaction="qty";
var editnumber="";

/*
var app = this;
app.hasKeyboard = false;
this.keyboardPress = function() {
    app.hasKeyboard = true;
    $(window).unbind("keyup", app.keyboardPress);
    localStorage.hasKeyboard = true;
    console.log("has keyboard!")
}
$(window).on("keyup", app.keyboardPress)
if(localStorage.hasKeyboard) {
    app.hasKeyboard = true;
    $(window).unbind("keyup", app.keyboardPress);
    console.log("has keyboard from localStorage")
}
*/

function toggleLoadingOverlay(show) {
	if (show) {
		$("body").append('<div id="loading-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; display:flex; justify-content:center; align-items:center;"><div style="background:white; padding:20px; border-radius:5px;"><span class="fas fa-spinner fa-spin" style="font-size:3em; margin-right:10px;"></span><span style="font-size:1.5em;"><?php echo $langs->trans("Cargando..."); ?></span></div></div>');
	} else {
		$("#loading-overlay").remove();
	}
}

function ClearSearch() {
	console.log("ClearSearch");
	$("#search").val('');
	$("#searchprod").val('');
	$("#searchfam").val('');
	LockButtons();
}

// Set the focus on search field but only on desktop. On tablet or smartphone, we don't to avoid to have the keyboard open automatically
function setFocusOnSearchField() {
	console.log("Call setFocusOnSearchField in page takepos.php");
	<?php if ($conf->browser->layout == 'classic') { ?>
		console.log("has keyboard from localStorage, so we can force focus on search field");
		$("#search").focus();
	<?php } ?>
}

function PrintCategories(first) {
	console.log("PrintCategories");
	for (i = 0; i < <?php echo ($MAXCATEG - 2); ?>; i++) {
		if (typeof (categories[parseInt(i)+parseInt(first)]) == "undefined")
		{
			$("#catdivdesc"+i).hide();
			$("#catdesc"+i).text("");
			$("#catimg"+i).attr("src","genimg/empty.png");
			$("#catwatermark"+i).hide();
			$("#catdiv"+i).attr('class', 'wrapper divempty');
			continue;
		}
		$("#catdivdesc"+i).show();
		$("#catdesc"+i).text(categories[parseInt(i)+parseInt(first)]['label']);
        $("#catimg"+i).attr("src","genimg/index.php?query=cat&id="+categories[parseInt(i)+parseInt(first)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[parseInt(i)+parseInt(first)]['rowid']);
		$("#catdiv"+i).attr('class', 'wrapper');
		$("#catwatermark"+i).show();
	}
}

function MoreCategories(moreorless) {
	console.log("MoreCategories moreorless="+moreorless+" pagecategories="+pagecategories);
	if (moreorless=="more") {
		$('#catdiv11').animate({opacity: '0.5'}, 1);
		$('#catdiv11').animate({opacity: '1'}, 100);
		pagecategories=pagecategories+1;
	}
	if (moreorless=="less") {
		$('#catdiv10').animate({opacity: '0.5'}, 1);
		$('#catdiv10').animate({opacity: '1'}, 100);
		if (pagecategories==0) return; //Return if no less pages
		pagecategories=pagecategories-1;
	}
	if (typeof (categories[<?php echo ($MAXCATEG - 2); ?> * pagecategories] && moreorless=="more") == "undefined"){ // Return if no more pages
		pagecategories=pagecategories-1;
		return;
	}
	for (i = 0; i < <?php echo ($MAXCATEG - 2); ?>; i++) {
		if (typeof (categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]) == "undefined") {
			$("#catdivdesc"+i).hide();
			$("#catdesc"+i).text("");
			$("#catimg"+i).attr("src","genimg/empty.png");
			$("#catwatermark"+i).hide();
			continue;
		}
		$("#catdivdesc"+i).show();
		$("#catdesc"+i).text(categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]['label']);
        $("#catimg"+i).attr("src","genimg/index.php?query=cat&id="+categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
        $("#catdiv"+i).data("rowid",categories[i+(<?php echo ($MAXCATEG - 2); ?> * pagecategories)]['rowid']);
		$("#catwatermark"+i).show();
	}

	ClearSearch();
}

// LoadProducts
function LoadProducts(position, issubcat) {
	console.log("LoadProducts");
	var maxproduct = <?php echo ($MAXPRODUCT - 2); ?>;

	$('#catimg'+position).animate({opacity: '0.5'}, 1);
	$('#catimg'+position).animate({opacity: '1'}, 100);
	if (issubcat==true) currentcat=$('#prodiv'+position).data('rowid');
	else currentcat=$('#catdiv'+position).data('rowid');
    if (currentcat == undefined) return;
	pageproducts=0;
	ishow=0; //product to show counter

	jQuery.each(subcategories, function(i, val) {
		if (currentcat==val.fk_parent) {
			$("#prodivdesc"+ishow).show();
			$("#prodesc"+ishow).text(val.label);
			$("#proimg"+ishow).attr("src","genimg/index.php?query=cat&id="+val.rowid);
			$("#prodiv"+ishow).data("rowid",val.rowid);
			$("#prodiv"+ishow).data("iscat",1);
			$("#prowatermark"+ishow).show();
			ishow++;
		}
	});

	idata=0; //product data counter
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getProducts&category='+currentcat, function(data) {
		console.log("Call ajax.php (in LoadProducts) to get Products of category "+currentcat+" then loop on result to fill image thumbs");
		while (ishow < maxproduct) {
			if (typeof (data[idata]) == "undefined") {
				$("#prodivdesc"+ishow).hide();
				$("#prodesc"+ishow).text("");
				$("#proimg"+ishow).attr("title","");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				$("#prodiv"+ishow).attr("class","wrapper2 divempty");
				$("#prowatermark"+ishow).hide();
				ishow++; //Next product to show after print data product
			}
			else /* if ((data[idata]['status']) == "1") */ {// Only show products with status=1 (for sell)
				var titlestring = '<?php echo dol_escape_js($langs->transnoentities('Ref').': '); ?>'+data[idata]['ref'];
				$("#prodivdesc"+ishow).show();
				$("#prodesc"+ishow).text(data[parseInt(idata)]['label']);
				$("#proimg"+ishow).attr("title", titlestring);
				$("#proimg"+ishow).attr("src", "genimg/index.php?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid", data[idata]['id']);
				$("#prodiv"+ishow).data("iscat", 0);
				$("#prodiv"+ishow).data("tobuy", data[idata]['status_buy']);
				$("#prodiv"+ishow).attr("class","wrapper2");
				$("#prowatermark"+ishow).hide();
				ishow++; //Next product to show after print data product
			}
			//console.log("Hide the prowatermark for ishow="+ishow);
			idata++; //Next data everytime
		}
	});

	ClearSearch();
}

function MoreProducts(moreorless) {
	console.log("MoreProducts");
	var maxproduct = <?php echo ($MAXPRODUCT - 2); ?>;

	if (moreorless=="more"){
		$('#prodiv23').animate({opacity: '0.5'}, 1);
		$('#prodiv23').animate({opacity: '1'}, 100);
		pageproducts=pageproducts+1;
	}
	if (moreorless=="less"){
		$('#prodiv22').animate({opacity: '0.5'}, 1);
		$('#prodiv22').animate({opacity: '1'}, 100);
		if (pageproducts==0) return; //Return if no less pages
		pageproducts=pageproducts-1;
	}
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=getProducts&category='+currentcat, function(data) {
		console.log("Call ajax.php (in MoreProducts) to get Products of category "+currentcat);

		if (typeof (data[(maxproduct * pageproducts)]) == "undefined" && moreorless=="more"){ // Return if no more pages
			pageproducts=pageproducts-1;
			return;
		}
		idata=<?php echo ($MAXPRODUCT - 2); ?> * pageproducts; //product data counter
		ishow=0; //product to show counter

		while (ishow < maxproduct) {
			if (typeof (data[idata]) == "undefined") {
				$("#prodivdesc"+ishow).hide();
				$("#prodesc"+ishow).text("");
				$("#proimg"+ishow).attr("src","genimg/empty.png");
				$("#prodiv"+ishow).data("rowid","");
				ishow++; //Next product to show after print data product
			}
			else if ((data[idata]['status']) == "1") {
				//Only show products with status=1 (for sell)
				$("#prodivdesc"+ishow).show();
				$("#prodesc"+ishow).text(data[parseInt(idata)]['label']);
				$("#proimg"+ishow).attr("src","genimg/index.php?query=pro&id="+data[idata]['id']);
				$("#prodiv"+ishow).data("rowid",data[idata]['id']);
				$("#prodiv"+ishow).data("iscat",0);
				ishow++; //Next product to show after print data product
			}
			$("#prowatermark"+ishow).hide();
			idata++; //Next data everytime
		}
	});

	ClearSearch();
}

function ClickProduct(position) {
	console.log("ClickProduct");
    $('#proimg'+position).animate({opacity: '0.5'}, 1);
	$('#proimg'+position).animate({opacity: '1'}, 100);
	if ($('#prodiv'+position).data('iscat')==1){
		console.log("Click on a category at position "+position);
		LoadProducts(position, true);
	}
	else{
		idproduct=$('#prodiv'+position).data('rowid');
		console.log("Click on product at position "+position+" for idproduct "+idproduct);
		if (idproduct=="") return;
		tobuy=$('#prodiv'+position).data('tobuy');
		if (tobuy==1){
			// Call page invoice.php to generate the section with product lines
			$("#poslines").load("invoice.php?action=addline&place="+place+"&idproduct="+idproduct, function() {
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});
		}else $("#poslines").load("invoice.php?action=notsell&place="+place+"&idproduct="+idproduct, function() {});
	}

	ClearSearch();
}

function deleteline() {
	console.log("Delete line");
	$("#poslines").load("invoice.php?action=deleteline&place="+place+"&idline="+selectedline, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
	ClearSearch();
}

function Customer() {
	console.log("Open box to select the thirdparty place="+place);
	$.colorbox({href:"../societe/list.php?contextpage=poslist&nomassaction=1&place="+place, width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Customer"); ?>"});
}

function ControlCashOpening(terminal)
{
	$.colorbox({href:"../compta/cashcontrol/cashcontrol_card.php?action=create&contextpage=poslist&posnumber="+terminal, width:"90%", height:"60%", transition:"none", iframe:"true", title:"Apertura de caja"});
}

function CloseCashFence(rowid)
{
	$.colorbox({href:"../compta/cashcontrol/cashcontrol_card.php?id="+rowid+"&contextpage=poslist", width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Cierre de caja"); ?>"});
	$("#cashcontrol_dialog").dialog("close");
}

function CashReport(rowid)
{
	$.colorbox({href:"../compta/cashcontrol/arqueo.php?id="+rowid+"&contextpage=poslist", width:"30%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Arqueo"); ?>"});
	$("#cashcontrol_dialog").dialog("close");
}

function ControlCaja(rowid){
	$("#controlbtn").empty();
	$("#cashcontrol_dialog").dialog({
		autoOpen: false,
		modal: true,
		width:300,
		height:150,
	});
	$("#controlbtn").append($('<button>', {
		class: "control arqueo",
		text: "Arqueo",
		click: function(){
			CashReport(rowid);
		},
	}));
	$("#controlbtn").append($('<button>', {
		class: "control cierre",
		text: "Corte",
		click: function(){
			CloseCashFence(rowid);
		},
	}));
	$("#cashcontrol_dialog").dialog("open");
}

function History()
{
    console.log("Open box to select the history");
    $.colorbox({href:"../compta/facture/list.php?contextpage=poslist&search_type=0", width:"90%", height:"80%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("History"); ?>"});
}

function CloseBill() {
	if (!window.hasProductLines || $("#tablelines tbody tr.posinvoiceline").length === 0) {
		alert("<?php echo $langs->trans("No hay productos para facturar", "TakePOS"); ?>");
		return;
	}
	// Evitar clics múltiples
	if ($("#closebillbutton").hasClass("disabled")) {
		console.log("Payment processing already in progress");
		return;
	}
	
	// Deshabilitar el botón
	$("#closebillbutton").addClass("disabled");
	
	// Show loading overlay
	toggleLoadingOverlay(true);
	
	invoiceid = $("#invoiceid").val();
	console.log("Open popup to enter payment on invoiceid="+invoiceid);
	$.colorbox({
		href: "pay.php?place="+place+"&invoiceid="+invoiceid, 
		width: "85%", 
		height: "90%", 
		transition: "none", 
		iframe: "true", 
		title: "",
		onOpen: function() {
			// Keep loading overlay visible while the payment screen loads
		},
		onComplete: function() {
			// Remove loading overlay once the payment screen is fully loaded
			toggleLoadingOverlay(false);
			
			// Comprobar si el iframe se cargó correctamente
			var iframe = document.getElementById("colorbox").getElementsByTagName("iframe")[0];
			if (iframe) {
				iframe.onerror = function() {
					// Ocurrió un error al cargar el iframe
					console.log("Error loading payment iframe");
					toggleLoadingOverlay(false);
					if (window.hasProductLines) {
						$("#closebillbutton").removeClass("disabled");
					}
				};
			}
		},
		onClosed: function() {
			// Re-habilitar el botón cuando se cierra el colorbox
			toggleLoadingOverlay(false);
			if (window.hasProductLines) {
				$("#closebillbutton").removeClass("disabled");
			}
		}
	});
	
	// Establecer un timeout como respaldo en caso de que ocurra algún error
	setTimeout(function() {
		toggleLoadingOverlay(false);
		if ($("#closebillbutton").hasClass("disabled") && window.hasProductLines) {
			console.log("Timeout - resetting payment button state");
			$("#closebillbutton").removeClass("disabled");
		}
	}, 5000); // 5 segundos como tiempo máximo de espera
}

function Floors() {
	console.log("Open box to select floor");
	$.colorbox({href:"floors.php?place="+place, width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Floors"); ?>"});
}

function FreeZone() {
	console.log("Open box to enter a free product");
	$.colorbox({href:"freezone.php?action=freezone&place="+place, onClosed: function () { Refresh(); },width:"80%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("FreeZone"); ?>"});
}

function TakeposOrderNotes() {
	console.log("Open box to order notes");
	$.colorbox({href:"freezone.php?action=addnote&place="+place+"&idline="+selectedline, onClosed: function () { Refresh(); },width:"80%", height:"30%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("OrderNotes"); ?>"});
}

function Refresh() {
	console.log("Refresh");
	$("#poslines").load("invoice.php?place="+place, function() {
		setTimeout(function() {
			if ($("#tablelines tbody tr.posinvoiceline").length === 0) {
				$("#closebillbutton").addClass("disabled");
			} else {
				$("#closebillbutton").removeClass("disabled");
			}
		}, 100); // Small delay to ensure DOM is updated
	});
}

function New() {
	// Create confirmation dialog
	$("#confirm-delete-dialog").dialog({
		autoOpen: false,
		modal: true,
		width: 400,
		height: 200,
		closeOnEscape: false,
		title: "<?php echo $langs->trans('Confirmación'); ?>",
		open: function(event, ui) {
			// Disable the close button
			$(this).closest('.ui-dialog').find('.ui-dialog-titlebar-close').hide();
		},
		buttons: [
			{
				text: "<?php echo $langs->trans('Si'); ?>",
				click: function() {
					// Disable the payment button during deletion process
					$("#closebillbutton").addClass("disabled");
					
					// Show loading overlay
					toggleLoadingOverlay(true);
					
					// Process the deletion
					$("#poslines").load("invoice.php?action=delete&place="+place, function(){
						// Remove the loading overlay when done
						toggleLoadingOverlay(false);
						
						// Re-enable the payment button
						$("#closebillbutton").removeClass("disabled");
						
						// Clear search
						ClearSearch();
					});
					
					$(this).dialog("close");
				}
			},
			{
				text: "<?php echo $langs->trans('No'); ?>",
				click: function() {
					$(this).dialog("close");
				}
			}
		]
	});
	
	// Set dialog message based on place value
	if (place > 0) {
		$("#confirm-delete-message").text('<?php echo $langs->transnoentitiesnoconv("ConfirmDeletionOfThisPOSSale"); ?>');
	} else {
		$("#confirm-delete-message").text('<?php echo $langs->transnoentitiesnoconv("ConfirmDiscardOfThisPOSSale"); ?>');
	}
	
	// Open the dialog
	$("#confirm-delete-dialog").dialog("open");
}

function Search(){
	console.log("Search2 Call ajax search to replace products");
	pageproducts=0;
	jQuery(".wrapper2 .catwatermark").hide();
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=searchprod&term='+$('#searchprod').val(), function(data) {
		for (i = 0; i < <?php echo $MAXPRODUCT ?>; i++) {
			if (typeof (data[i]) == "undefined"){
				$("#prodesc"+i).text("");
				$("#proimg"+i).attr("src","genimg/empty.png");
                $("#prodiv"+i).data("rowid","");
				continue;
			}
			var titlestring = '<?php echo dol_escape_js($langs->transnoentities('Ref').': '); ?>'+data[i]['ref'];
			$("#prodesc"+i).text(data[i]['label']);
			$("#prodivdesc"+i).show();
			$("#proimg"+i).attr("title", titlestring);
			$("#proimg"+i).attr("src", "genimg/?query=pro&id="+data[i]['rowid']);
			$("#prodiv"+i).data("rowid", data[i]['rowid']);
			$("#prodiv"+i).data("iscat", 0);
			$("#prodiv"+i).data("tobuy", data[i]['tobuy']);
		}
	});
}

function Search2() {
	search = $("#search").val().replace(/ /g, "");
	console.log("Search2 Call ajax search to replace products");
	pageproducts=0;
	let id_fac = $("#invoiceid").val();
	if ($("#prodExpiration_dialog").open){
		$("#prodExpiration_dialog").dialog("close");
		$("#searchbarcode").val("");
	}
	jQuery(".wrapper2 .catwatermark").hide();
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=search&term='+search+'&id='+id_fac, function(data) {
		if (data == 405) alert("No hay suficiente stock en este lote. Por favor, seleccione otro lote.")
		else if (data == 404) alert("Error al encontrar el producto")
		else {
			$("#poslines").load("invoice.php?action=addline&place="+place+"&idproduct="+data['fk_product']+"&lotid="+data['lotid'], function() {
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
			});
			ClearSearch();
		}
	});
}

function SearchCat(){
	pagecategories=0;
	jQuery(".wrapper .catwatermark").hide();
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=searchfam&termfam='+$('#searchfam').val(), function(data) {
		for (i = 0; i < <?php echo $MAXCATEG ?>; i++) {
			if (typeof (data[i]) == "undefined"){
				$("#catdesc"+i).text("");
				$("#catimg"+i).attr("src","genimg/empty.png");
                $("#catdiv"+i).data("rowid","");
				continue;
			}
			var titlestring = '<?php echo dol_escape_js($langs->transnoentities('Ref').': '); ?>'+data[i]['ref'];
			$("#catdesc"+i).text(data[i]['label']);
			$("#catdivdesc"+i).show();
			$("#catimg"+i).attr("title", titlestring);
			$("#catimg"+i).attr("src", "genimg/?query=cat&id="+data[i]['rowid']);
			$("#catdiv"+i).data("rowid", data[i]['rowid']);
			$("#catdiv"+i).data("iscat", 0);
		}
	});
}

function Edit(number) {

	if (typeof(selectedtext) == "undefined") return;	// We click on an action on the number pad but there is no line selected

	var text=selectedtext+"<br> ";

	if (number=='c'){
		editnumber="";
		$("#qty").html("<?php echo $langs->trans("Qty"); ?>");
		$("#price").html("<?php echo $langs->trans("Price"); ?>");
		$("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
		Refresh();
		return;
	}
	else if (number=='qty'){
		console.log("Edit "+number);
		if (editaction=='qty' && editnumber!=""){
			$("#poslines").load("invoice.php?action=updateqty&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
				editaction = '';
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
				$("#qty").html("<?php echo $langs->trans("Qty"); ?>");
			});

			setFocusOnSearchField();
			return;
		}
		else {
			editaction="qty";
		}
	}
	else if (number=='p'){
		console.log("Edit "+number);
		if (editaction=='p' && editnumber!=""){
			$("#poslines").load("invoice.php?action=updateprice&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
				editaction = '';
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
				$("#price").html("<?php echo $langs->trans("Price"); ?>");
			});

			ClearSearch();
			return;
		}
		else {
			editaction="p";
		}
	}
	else if (number=='r'){
		console.log("Edit "+number);
		if (editaction=='r' && editnumber!=""){
			$("#poslines").load("invoice.php?action=updatereduction&place="+place+"&idline="+selectedline+"&number="+editnumber, function() {
				editnumber="";
				editaction = "";
				//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
				$("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
			});

			ClearSearch();
			return;
		}
		else {
			editaction="r";
		}
	}
	else {
		editaction==''?editnumber="":editnumber = editnumber+number;
	}
	if (editaction=='qty'){
		text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("Qty").": "; ?>";
		$("#qty").html("OK");
		$("#price").html("<?php echo $langs->trans("Price"); ?>");
		$("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
	}
	if (editaction=='p'){
		text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("Price").": "; ?>";
		$("#qty").html("<?php echo $langs->trans("Qty"); ?>");
		$("#price").html("OK");
		$("#reduction").html("<?php echo $langs->trans("ReductionShort"); ?>");
	}
	if (editaction=='r'){
		text=text+"<?php echo $langs->trans("Modify")." -> ".$langs->trans("ReductionShort").": "; ?>";
		$("#qty").html("<?php echo $langs->trans("Qty"); ?>");
		$("#price").html("<?php echo $langs->trans("Price"); ?>");
		$("#reduction").html("OK");
	}
	$('#'+selectedline).find("td:first").html(text+editnumber);
}

function TakeposPrintingOrder(){
	console.log("TakeposPrintingOrder");
	$("#poslines").load("invoice.php?action=order&place="+place, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function TakeposPrintingTemp(){
	console.log("TakeposPrintingTemp");
	$("#poslines").load("invoice.php?action=temp&place="+place, function() {
		//$('#poslines').scrollTop($('#poslines')[0].scrollHeight);
	});
}

function OpenDrawer(){
	console.log("OpenDrawer");
	$.ajax({
		type: "POST",
		url: 'http://<?php print $conf->global->TAKEPOS_PRINT_SERVER;?>:8111/print',
		data: "opendrawer"
	});
}

function DolibarrOpenDrawer() {
	console.log("DolibarrOpenDrawer");
	$.ajax({
		type: "GET",
		url: "<?php print dol_buildpath('/takepos/ajax/ajax.php', 1).'?action=opendrawer&term='.$_SESSION["takeposterminal"]; ?>",
	});
}

function MoreActions(totalactions){
	if (pageactions==0){
		pageactions=1;
		for (i = 0; i <= totalactions; i++){
			if (i<9) $("#action"+i).hide();
			else $("#action"+i).show();
		}
	}
	else if (pageactions==1){
		pageactions=0;
		for (i = 0; i <= totalactions; i++){
			if (i<9) $("#action"+i).show();
			else $("#action"+i).hide();
		}
	}
}

// Popup to select the terminal to use
function TerminalsDialog() {
	console.log("Selección de terminal");
	
	// Crear el diálogo si no existe
	if ($("#terminal-selector-dialog").length === 0) {
		$("body").append(`
			<div id="terminal-selector-dialog" class="terminal-dialog" title="Selección de Terminal">
				<div class="terminal-selector-container">
					<div class="terminal-selector-title">
						<i class="fas fa-cash-register"></i> Seleccione su terminal
					</div>
					<div class="terminal-grid" id="terminals-grid"></div>
				</div>
			</div>
		`);
	}
	
	// Configurar el diálogo
	$("#terminal-selector-dialog").dialog({
		autoOpen: false,
		modal: true,
		width: 500,
		height: 'auto',
		resizable: false,
		closeOnEscape: false,
		dialogClass: 'terminal-dialog',
		open: function(event, ui) {
			// Quitar el botón de cerrar
			$(this).closest('.ui-dialog').find('.ui-dialog-titlebar-close').hide();
			
			// Animación de entrada
			$(this).closest('.ui-dialog').css('opacity', 0)
				.animate({ opacity: 1 }, 300);
		},
		close: function(event, ui) {
			// Animación de salida
			$(this).closest('.ui-dialog').animate({ opacity: 0 }, 300);
		}
	});
	
	// Limpiar el grid de terminales
	$("#terminals-grid").empty();
	
	// Cargar terminales
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=terminals', function(data) {
		if (data.length === 0) {
			$("#terminals-grid").html('<div class="alert alert-warning text-center">No hay terminales disponibles</div>');
			return;
		}
		
		// Crear botones para cada terminal
		for (i = 0; i < data.length; i++) {
			const name = data[i]['value'];
			const term = data[i]['term'];
			
			// Determinar si esta terminal es la activa actualmente
			const isActive = term == <?php echo json_encode($_SESSION["takeposterminal"]); ?>;
			const activeClass = isActive ? 'active' : '';
			
			$('#terminals-grid').append(`
				<button class="terminal-button ${activeClass}" data-terminal="${term}">
					<div class="terminal-icon">
						<i class="fas fa-cash-register"></i>
					</div>
					<div class="terminal-name">${name}</div>
				</button>
			`);
		}
		
		// Asignar eventos click a los botones
		$(".terminal-button").on('click', function() {
			const terminalId = $(this).data('terminal');
			
			// Efecto visual al hacer clic
			$(this).css('transform', 'scale(0.95)');
			setTimeout(() => $(this).css('transform', ''), 100);
			
			// Consultar si la terminal está ocupada
			$.ajax({
				url: '<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=setterminal',
				type: 'POST',
				data: { terminal: terminalId },
				beforeSend: function() {
					// Mostrar indicador de carga
					toggleLoadingOverlay(true);
				},
				success: function(response) {
					toggleLoadingOverlay(false);
					response = JSON.parse(response);
					console.log(response);
					
					if (response.success) {
						// Animar el cierre del diálogo
						$("#terminal-selector-dialog").closest('.ui-dialog').animate({ opacity: 0 }, 300, function() {
							// Redirigir después de la animación
							location.href = "takepos.php?setterminal=" + terminalId;
						});
					} else {
						// Mostrar diálogo de error con la terminal en uso por otro usuario
						$("#terminal-in-use-dialog").remove(); // Eliminar si existe
						var modalHtml = `
							<div id="terminal-in-use-dialog" title="Terminal en uso">
								<div class="terminal-dialog-content">
									<div class="terminal-icon"><i class="fas fa-exclamation-triangle"></i></div>
									<h3>Terminal no disponible</h3>
									<p>La terminal que intenta utilizar se encuentra actualmente en uso por <strong>${response.username}</strong>.</p>
									<p>Si requiere acceder, le recomendamos cerrar la sesión del usuario actual antes de continuar.</p>
								</div>
							</div>
						`;
						$("body").append(modalHtml);
						
						$("#terminal-in-use-dialog").dialog({
							autoOpen: true,
							modal: true,
							width: 400,
							closeOnEscape: true,
							buttons: [{
								text: "Entendido",
								click: function() {
									$(this).dialog("close");
								},
								class: "btn-primary"
							}],
							open: function() {
								// Estilos personalizados para la barra de título
								$(this).parent().find('.ui-dialog-titlebar').css({
									'background': '#e74c3c',
									'color': 'white',
									'border': 'none'
								});
							}
						});
					}
				},
				error: function() {
					toggleLoadingOverlay(false);
					alert("Error al conectar con el servidor. Intente nuevamente.");
				}
			});
		});
	});
	
	// Abrir el diálogo
	$("#terminal-selector-dialog").dialog("open");
}

function DirectPayment(){
	console.log("DirectPayment");
	$("#poslines").load("invoice.php?place"+place+"&action=valid&pay=<?php echo $langs->trans("cash"); ?>", function() {
	});
}

function Salida(){
	$.colorbox({href:"../compta/bank/various_payment/card.php?action=create&contextpage=takepos&type=salida", width:"90%", height:"80%", transition:"none", iframe:"true", title:"Salida de efectivo"});
}

function Entrada(){
	$.colorbox({href:"../compta/bank/various_payment/card.php?action=create&contextpage=takepos&type=entrada", width:"90%", height:"80%", transition:"none", iframe:"true", title:"Entrada de efectivo"});
}

function Facturar(){
	if ($(":contains('Facturar')").closest("div").attr("disabled")!="disabled"){
		var id = $("#invoiceid").val();
		$.colorbox({href:"../cfdimx/facture.php?contextpage=poslist&facid="+id, width:"80%", height:"90%", transition:"none", iframe:"true", title:"Facturar" });
	}else alert("Selefunctionccione una venta desde el Historico");
}

function Devolucion(){
	if ($(":contains('Devolver')").closest("div").attr("disabled")!="disabled"){
		var id = $("#invoiceid").val();
		var socid = $("#socid").val();
		$.colorbox({href:"../compta/facture/card.php?contextpage=poslist&socid="+socid+"&fac_avoir="+id+"&invoiceAvoirWithLines=1&action=create&type=2&originentity=1&return=1", width:"80%", height:"90%", transition:"none", iframe:"true", title:"Devolución"});
	}else alert("Seleccione una venta desde el Historico");
}

function LockButtons(){
	$(":contains('Facturar')").closest("div").attr("disabled", true);
	$(":contains('Devolver')").closest("div").attr("disabled", true);
}

function UnlockButtons(){
	$(":contains('Facturar')").closest("div").attr("disabled", false);
	$(":contains('Devolver')").closest("div").attr("disabled", false);
}


function OpenRecipe() {
	$("#supply_dialog").dialog({
		autoOpen: false,
		height: 400,
		width: 600,
		modal: true,
		buttons: [
			{
				text: "Surtir",
				click:  function() {
					if ($("#list_doc").val() == -1) {
						alert("Selecciona un doctor");
						return;
					} else {
						$("#rowsprods tr").each(function() {
							var id = $(this).find("td").eq(0).html();
							var batch = $(this).find("td").eq(2).html();
							var medic = $("#list_doc").val();
							$("#poslines").load("invoice.php?action=addline&place=" + place + "&idproduct=" + id + "&batch=" + batch + "&medic=" + medic, function() {});
						});
						$(this).dialog("close");
						alert("Productos añadidos a la venta");
					}
				},
				style: "background: #45b867; border-radius: 10px;"
			},
			{
				text: "Cancelar",
				click: function() {
					$(this).dialog("close");
				},
				style: "background: #ff0023; border-radius: 10px;"

			}
		]
	})
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=listdoctors', function(data) {
		$("#list_doc").empty();
		$("#list_doc").append("<option value=-1 disabled selected>Seleccionar Médico</option>");
		for (i = 0; i < data.length; i++) {
			$('#list_doc').append($('<option>', {
				value: data[i]['rowid'],
				text: data[i]['firstname'] + " " + data[i]['lastname']
			}));
		}
	});
	$("#supply_dialog").dialog("open");
	$("#list_products tbody").empty();
}

function AddProduct2Recipe() {
	scanprod = $("#scanprod");
	barcode = scanprod.val().replace(/ /g, "");
	$.getJSON('<?php echo DOL_URL_ROOT ?>/takepos/ajax/ajax.php?action=search&term='+barcode, function (data) {
		var last = data.length - 1;
		if (data == 404) alert("No hay productos con esa fecha de caducidad")
		else if (data == 405) alert("No hay productos con ese codigo de barras")
		else if (data[last]['state'] == false) alert("Existe una fecha de caducidad mas proxima")
		else if (data[last]['state'] == true) {
			for (i = 0; i < data.length - 1; i++) {
				$("#list_products tbody").append("<tr><td style='display:none'>" + data[i]['rowid'] + "</td><td class='colprod1'>" + data[i]['ref'] + "</td><td class='colprod2'>" + data[i]['batch'] + "</td></tr>");
			}
		}
	});
	$("#scanprod").val('');
}

function Apartado(){
	$("#auth_dialog").dialog({
		autoOpen: false,
		height: 155,
		width: 250,
		modal: true,
		buttons: [
			{
				text: "Apartar",
				click: function(){
					$("#poslines").load("invoice.php?place="+place+"&action=apartar", function() {});
					$(this).dialog("close");
				}
			},
			{
				text: "Cancelar",
				click: function() {
					$(this).dialog("close");
				}
			}
		]
	})
	$("#auth_dialog").dialog("open");
}

function DesApartado(){
	$.colorbox({href:"../product/stock/movement_list.php?contextpage=poslist&action=liberarapartado", onClosed: function () { Refresh(); }, width:"90%", height:"80%", transition:"none", iframe:"true", title:"Liberar apartado"});
}

function PriceCheck(){
	$.colorbox({href:"pricecheck.php?contextpage=poslist", onClosed: function () { Refresh(); }, width:"70%", height:"80%", transition:"none", iframe:"true", title:"Cotizar productos"});
}

$( document ).ready(function() {
	PrintCategories(0);
	LoadProducts(0);
	Refresh();
	LockButtons();
	
	// Add a global variable to track the product lines state
	window.hasProductLines = false;
	
	const posLinesObserver = new MutationObserver(function(mutations) {
		setTimeout(function() {
			if ($("#tablelines tbody tr.posinvoiceline").length === 0) {
				window.hasProductLines = false;
				$("#closebillbutton").addClass("disabled");
			} else {
				window.hasProductLines = true;
				$("#closebillbutton").removeClass("disabled");
			}
		}, 100);
	});
	
	// Start observing the poslines element
	posLinesObserver.observe(document.getElementById("poslines"), { 
		childList: true,
		subtree: true 
	});
	
	<?php
	// TODO: Always open dialog for select terminal
	//IF NO TERMINAL SELECTED
	if ($_SESSION["takeposterminal"] == "")
	{	
		$consulta = "SELECT lc.name FROM ".MAIN_DB_PREFIX."const lc, ".MAIN_DB_PREFIX."user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '".$_SESSION["dol_login"]."'";
		$consulta .= " AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%'";
		$consulta = $db->query($consulta);
		$consulta = $db->fetch_object($consulta);
		$terminal = str_replace("CASHDESK_ID_WAREHOUSE", "", $consulta->name);
		$_SESSION["takeposterminal"] = $terminal;
		if ($conf->global->TAKEPOS_NUM_TERMINALS == "1") $_SESSION["takeposterminal"] = 1;
		print "TerminalsDialog();";
	} else {
		$sql = "SELECT rowid, status FROM ".MAIN_DB_PREFIX."pos_cash_fence WHERE";
		$sql .= " entity = ".((int) $conf->entity);
		$sql .= " AND status = 0";
		$sql .= " AND posnumber IN (SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') FROM ".MAIN_DB_PREFIX."const lc, ".MAIN_DB_PREFIX."user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '".$_SESSION["dol_login"]."' AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%')";
		$sql .= " ORDER BY rowid DESC LIMIT 1";
		$resql = $db->query($sql);

		if ($resql) {
			$obj = $db->fetch_object($resql);
			$mainterm = substr($conf->global->{"CASHDESK_NAME".$_SESSION["takeposterminal"]}, -1);
			if ($obj->rowid == null && $mainterm == '1') {
				print "ControlCashOpening(".$_SESSION["takeposterminal"].");";
			}
		}
	}

	
	?>
});
</script>

<div hidden id="supply_dialog" name="supply_dialog" title="Surtir Receta Médica">
	<div>
		<div class="center">
			<!-- Seleccionar el doctor que receto -->
			<!-- <select class="seldoc" id="list_doc" placeholder="Seleccionar Médico"></select> -->
			<?php
			$form = new Form($db);
			print '<form name="form" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
			print '<table class="border centpercent">';
			print '<tr><td>';
			print $form->select_dolusers(0, 'list_doc', 1, 0, 0, '', '', 0, 0, 0, ' AND u.rowid IN (SELECT luu.fk_user FROM llx_usergroup_user luu, llx_usergroup lug WHERE lug.nom LIKE "%medico%")', 0, '', 'seldoc');
				print '<script type="text/javascript">
				$(document).ready(function() {
					$("#list_doc").change(function() {
						var doctor = $(this).val();
					});
				});
				</script>';
			//}
			print '</td>';
			print '</tr>'."\n";
			print '</table>';
			print '</form>';
			?>
			<br>
			<input class="scanprod" type="text" id="scanprod" name="scanprod" placeholder="Codigo de barras" autofocus autocomplete="off">
			<script>
				var handleScan = document.getElementById("scanprod");
				handleScan.addEventListener("keyup", function(event) {
					if (event.code === 'Enter') {
						AddProduct2Recipe()
					}
				});
			</script>
		</div>
		<table class="list_products" id="list_products">
			<thead class="titleprods">
				<th class="colprod1">Producto</th>
				<th class="colprod2">Lote</th>
			</thead>
			<tbody id="rowsprods"></tbody>
		</table>
	</div>
</div>

<!-- Terminal selector dialog is now dynamically created in TerminalsDialog() function -->

<div hidden id="cashcontrol_dialog" name="cashcontrol_dialog" title="Control de caja">
	<div id="controlbtn" class="center"></div>
</div>

<div hidden id="prodExpiration_dialog" name="prodExpiration_dialog" title="Fecha de caducidad">
	<h1 style="color:red;">Alerta</h1>
	<h2 style="color:red;">Existe un lote o más con fecha de caducidad más pronta a expirar</h2>
	<table class="noborder centpercent" id="fechas_exp">
		<thead>
			<tr class="liste_titre">
				<th class="liste_titre" width="20%"><h3>Lote</h3></th>
				<th class="liste_titre" width="20%"><h3>Fecha de caducidad</h3></th>
				<th class="liste_titre" width="20%"><h3>Codigo de barras</h3></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<div class="center">
	<input type="text" id="searchbarcode" name="searchbarcode" style="width:80%;font-size: 150%;" placeholder="Codigo de barras" autofocus autocomplete="off">
	</div>
</div>

<div hidden id="password_dialog" name="password_dialog" title="Autorizacion">
	<div class="center">
		<label>Contraseña</label>
		<input type="text" style="display:none">
		<input type="password" id="authpass" name="authpass" placeholder="Contraseña" autofocus autocomplete="new-password">
	</div>
</div>

<div hidden id="auth_dialog" name="auth_dialog" title="Confirmar apartado">
	<div class="center">
		<label>¿Desea apartar los productos añadidos?</label>
	</div>
</div>	

<div hidden id="confirm-delete-dialog" title="<?php echo $langs->trans('Confirmation'); ?>">
	<p id="confirm-delete-message"></p>
</div>

<body class="bodytakepos" style="overflow: hidden;">

<div class="header">
		<div class="topnav">
			<div class="topnav-left">
				<div class="inline-block valignmiddle">
				<a class="topnav-terminalhour" onclick='TerminalsDialog();'>
				<span class="fa fa-cash-register"></span>
				<span class="hideonsmartphone">
				<?php echo $langs->trans("Terminal"); ?>
				</span>
				<?php echo " ";
				if ($_SESSION["takeposterminal"] == "") {
					echo $conf->global->{'CASHDESK_NAME'.$_SESSION["takeposterminal"]};
				} else {
					echo $conf->global->{'CASHDESK_NAME'.$_SESSION["takeposterminal"]};
				}
				echo '<span class="hideonsmartphone"> - '.dol_print_date(dol_now('tzuser'), "day").'</span>';
				?>
				</a>
				</div>
				<!-- section for customer -->
				<div class="inline-block valignmiddle" id="openrecipe">
					<a class="topnav-customer" onclick='OpenRecipe();'>
						<span class="fa fa-clipboard-list"></span>
						Surtir Receta
					</a>
				</div>
				<div class="inline-block valignmiddle" id="apartado">
					<a class="topnav-customer" onclick='Apartado();'>
					<!-- <a class="topnav-customer"> -->
						<span class="fa fa-exchange"></span>
						Apartar
					</a>
				</div>
				<div class="inline-block valignmiddle" id="apartado">
					<a class="topnav-customer" onclick='DesApartado();'>
					<!-- <a class="topnav-customer"> -->
						<span class="fas fa-shopping-bag"></span>
						Liberar apartado
					</a>
				</div>
				<div class="inline-block valignmiddle" id="pricecheck">
					<a class="topnav-customer" onclick='PriceCheck();'>
						<span class="fas fa-shopping-bag"></span>
						Cotizar
					</a>
				</div>
			</div>
			<div class="topnav-right">
				<div class="login_block_other">
					<input type="text" id="searchprod" name="searchprod" class="input-search-takepos" onkeyup="Search('<?php echo dol_escape_js($keyCodeForEnter); ?>', null);" placeholder="<?php echo dol_escape_htmltag($langs->trans("Search")); ?>" autofocus>
					<a onclick="ClearSearch();"><span class="fa fa-backspace"></span></a>
					<a href="<?php echo DOL_URL_ROOT.'/'; ?>" target="backoffice" rel="opener"><!-- we need rel="opener" here, we are on same domain and we need to be able to reuse this tab several times -->
					<span class="fas fa-home centerfa"></span></a>
				<a>
					<?php print $_SESSION["dol_login"];	?>
				</a>
					<a href="<?php echo DOL_URL_ROOT.'/user/logout.php'; ?>">
					<span class="fas fa-sign-out-alt centerfa"></span></a>
				</div>
			</div>
		</div>
	</div>

<div class="container">
	<div class="row1">

		<div id="poslines" class="div1">
		</div>

		<div class="div2">
			<button type="button" class="calcbutton" onclick="Edit(7);">7</button>
			<button type="button" class="calcbutton" onclick="Edit(8);">8</button>
			<button type="button" class="calcbutton" onclick="Edit(9);">9</button>
			<button type="button" id="qty" class="calcbutton2" onclick="Edit('qty');"><?php echo $langs->trans("Qty"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(4);">4</button>
			<button type="button" class="calcbutton" onclick="Edit(5);">5</button>
			<button type="button" class="calcbutton" onclick="Edit(6);">6</button>
			<button type="button" id="price" class="calcbutton2" onclick="Edit('p');"><?php echo $langs->trans("Price"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(1);">1</button>
			<button type="button" class="calcbutton" onclick="Edit(2);">2</button>
			<button type="button" class="calcbutton" onclick="Edit(3);">3</button>
			<button type="button" id="reduction" class="calcbutton2" onclick="Edit('r');"><?php echo $langs->trans("ReductionShort"); ?></button>
			<button type="button" class="calcbutton" onclick="Edit(0);">0</button>
			<button type="button" class="calcbutton" onclick="Edit('.');">.</button>
			<button type="button" class="calcbutton poscolorblue" onclick="Edit('c');">C</button>
			<button type="button" class="calcbutton2 poscolordelete" id="delete" onclick="deleteline();"><span class="fa fa-trash"></span></button>
		</div>

<?php

// TakePOS setup check
$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";

$resql = $db->query($sql);
$paiementsModes = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
        $paycode = $obj->code;
        if ($paycode == 'LIQ') $paycode = 'CASH';
        if ($paycode == 'CHQ') $paycode = 'CHEQUE';

		$constantforkey = "CASHDESK_ID_BANKACCOUNT_".$paycode.$_SESSION["takeposterminal"];
		if (!empty($conf->global->$constantforkey) && $conf->global->$constantforkey > 0) array_push($paiementsModes, $obj);
	}
}

if (empty($paiementsModes)) {
	$langs->load('errors');
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("TakePOS")), null, 'errors');
	setEventMessages($langs->trans("ProblemIsInSetupOfTerminal", $_SESSION["takeposterminal"]), null, 'errors');
}
if (count($maincategories) == 0) {
	setEventMessages($langs->trans("TakeposNeedsCategories"), null, 'errors');
}
// User menu and external TakePOS modules
$menus = array();
$r = 0;

$sql = "SELECT rowid, status, entity, posnumber FROM ".MAIN_DB_PREFIX."pos_cash_fence";
$sql .= " WHERE entity = ".((int) $conf->entity);
$sql .= " AND status = 0";
$sql .= " AND posnumber IN (SELECT REPLACE(lc.name, 'CASHDESK_ID_WAREHOUSE', '') FROM ".MAIN_DB_PREFIX."const lc, ".MAIN_DB_PREFIX."user lu WHERE lc.value = lu.fk_warehouse AND lu.login = '".$_SESSION["dol_login"]."' AND lc.name LIKE 'CASHDESK_ID_WAREHOUSE%')";
$sql .= " ORDER BY rowid DESC LIMIT 1";
$resql = $db->query($sql);
// ($db->num_rows($resql) > 0) ? '' : 'disabled'=>'disabled'
$menus[$r++] = array('title'=>'<span class="far fa-building paddingrightonly"></span><div class="trunc">'.$langs->trans("Customer").'</div>', 'action'=>'Customer();');
$menus[$r++] = array('title'=>'<span class="fa fa-history paddingrightonly"></span><div class="trunc">'.$langs->trans("History").'</div>', 'action'=>'History();');
$menus[$r++] = array('style'=>'background: #ff0023;','title'=>'<span class="fas fa-sign-out-alt paddingrightonly"></span><div class="trunc">Salida</div>', 'action'=>'Salida();', 'class'=>'salida');
$menus[$r++] = array('title'=>'<span class="fa fa-layer-group paddingrightonly"></span><div class="trunc">'.$langs->trans("New").'</div>', 'action'=>'New();');
$menus[$r++] = array('title'=>'<span class="far fa-money-bill-alt paddingrightonly"></span><div class="trunc">'.$langs->trans("Payment").'</div>', 'action'=>'CloseBill();', 'id'=>'closebillbutton');
$menus[$r++] = array('style'=>'background: #45b867;','title'=>'<span class="fas fa-sign-in-alt paddingrightonly"></span><div class="trunc">Entrada</div>', 'action'=>'Entrada();', 'class'=>'entrada');
$menus[$r++] = array('title'=>'<span class="fas fa-sticky-note paddingrightonly"></span><div class="trunc">Facturar</div>', 'action'=>'Facturar();');
$menus[$r++] = array('title'=>'<span class="fas fa-undo-alt paddingrightonly"></span><div class="trunc">Devolver</div>', 'action'=>'Devolucion();');
if ($db->num_rows($resql) > 0) {
	$obj = $db->fetch_object($resql);
	if ($obj->posnumber == $_SESSION["takeposterminal"])$menus[$r++] = array('style'=>'background:#0064a7;','title'=>'<span class="fas fa-cash-register paddingrightonly" style="color:#fff;"></span><div class="trunc blanco">Corte de caja</div>', 'action'=>'ControlCaja('.$obj->rowid.');');
	else $menus[$r++] = array('style'=>'background:#0064a7;','title'=>'<span class="fas fa-cash-register paddingrightonly" style="color:#fff;"></span><div class="trunc blanco">Arqueo</div>', 'action'=>'CashReport('.$obj->rowid.');');
}
else{
	if (substr($conf->global->{"CASHDESK_NAME".$_SESSION["takeposterminal"]}, -1) == '1'){
		$menus[$r++] = array('style'=>'background:#0064a7;','title'=>'<span class="fas fa-file-invoice-dollar paddingrightonly" style="color:#fff;"></span><div class="trunc blanco">Abrir turno</div>', 'action'=>'ControlCashOpening('.$_SESSION["takeposterminal"].');');
	}
}
$hookmanager->initHooks(array('takeposfrontend'));
$reshook=$hookmanager->executeHooks('ActionButtons');
if (!empty($reshook)) {
    $menus[$r++]=$reshook;
}

if ($r % 3 == 2) $menus[$r++]=array('title'=>'', 'style'=>'visibility: hidden;');
?>
		<!-- Show buttons -->
		<div class="div3">
		<?php
        $i = 0;
        foreach($menus as $menu)
        {
        	$i++;
        	if (count($menus) > 9 and $i == 9)
        	{
        		echo '<button style="'.$menu['style'].'" type="button" id="actionnext" class="actionbutton" onclick="MoreActions('.count($menus).');">'.$langs->trans("Next").'</button>';
        		echo '<button style="display: none;" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
        	}
            elseif ($i > 9) echo '<button style="display: none;" type="button" id="action'.$i.'" class="actionbutton" onclick="'.$menu['action'].'">'.$menu['title'].'</button>';
            else {
                $buttonId = isset($menu['id']) ? $menu['id'] : 'action'.$i;
                $buttonClass = 'actionbutton' . (isset($menu['class']) ? ' ' . $menu['class'] : '');
                echo '<button style="'.$menu['style'].'" type="button" id="'.$buttonId.'" class="'.$buttonClass.'" onclick="'.$menu['action'].'" '.$menu['disabled'].'>'.$menu['title'].'</button>';
            }
        }

        print '<!-- Show the search input text -->'."\n";
        print '<div class="margintoponly">';
		print '<input type="text" id="search" class="searchbar" name="search" style="width:95%;font-size: 150%;" placeholder="Codigo de barras" autofocus> ';
		print '<span class="fa fa-backspace errspan" onclick="ClearSearch();"></span>';
		print '<script>';
		print 'var handleSearch = document.getElementById("search");
				handleSearch.addEventListener("keyup", function(event) {
					if (event.code === "Enter") {
						Search2();
					}
				});';
		print '</script>';
		print '</div>';
        ?>
		</div>
	</div>

	<div class="row2">

		<!--  Show categories -->
		<div class="div4">
		<input type="text" id="searchfam" class="searchcatbar" oninput="SearchCat();" name="searchfam" placeholder="Buscar familia" autofocus>
		<span class="fa fa-backspace spancat" onclick="ClearSearch();"></span>

	<?php
	$count = 0;
	while ($count < $MAXCATEG)
	{
	    ?>
			<div class="wrapper" <?php if ($count == ($MAXCATEG - 2)) echo 'onclick="MoreCategories(\'less\');"'; elseif ($count == ($MAXCATEG - 1)) echo 'onclick="MoreCategories(\'more\');"'; else echo 'onclick="LoadProducts('.$count.');"'; ?> id="catdiv<?php echo $count; ?>">
				<?php
				if ($count == ($MAXCATEG - 2)) {
				    //echo '<img class="imgwrapper" src="img/arrow-prev-top.png" height="100%" id="catimg'.$count.'" />';
				    echo '<span class="fa fa-chevron-left centerinmiddle" style="font-size: 5em;"></span>';
				}
				elseif ($count == ($MAXCATEG - 1)) {
				    //echo '<img class="imgwrapper" src="img/arrow-next-top.png" height="100%" id="catimg'.$count.'" />';
				    echo '<span class="fa fa-chevron-right centerinmiddle" style="font-size: 5em;"></span>';
				}
				else
				{
				    echo '<img class="imgwrapper" height="100%" id="catimg'.$count.'" />';
				}
				?>
				<?php if ($count != ($MAXCATEG - 2) && $count != ($MAXCATEG - 1)) { ?>
				<div class="description" id="catdivdesc<?php echo $count; ?>">
					<div class="description_content" id="catdesc<?php echo $count; ?>"></div>
				</div>
				<?php } ?>
				<div class="catwatermark" id='catwatermark<?php echo $count; ?>'>...</div>
			</div>
	    <?php
        $count++;
	}
	?>
		</div>

	    <!--  Show product -->
		<div class="div5">
    <?php
    $count = 0;
    while ($count < $MAXPRODUCT)
    {
        ?>
    			<div class="wrapper2" id='prodiv<?php echo $count; ?>' <?php if ($count == ($MAXPRODUCT - 2)) {?> onclick="MoreProducts('less');" <?php } if ($count == ($MAXPRODUCT - 1)) {?> onclick="MoreProducts('more');" <?php } else echo 'onclick="ClickProduct('.$count.');"'; ?>>
    				<?php
    				if ($count == ($MAXPRODUCT - 2)) {
    				    //echo '<img class="imgwrapper" src="img/arrow-prev-top.png" height="100%" id="proimg'.$count.'" />';
    				    echo '<span class="fa fa-chevron-left centerinmiddle" style="font-size: 5em;"></span>';
    				}
    				elseif ($count == ($MAXPRODUCT - 1)) {
    				    //echo '<img class="imgwrapper" src="img/arrow-next-top.png" height="100%" id="proimg'.$count.'" />';
    				    echo '<span class="fa fa-chevron-right centerinmiddle" style="font-size: 5em;"></span>';
    				}
    				else
    				{
    				    echo '<img class="imgwrapper" height="100%" title="" id="proimg'.$count.'">';
    				}
    				?>
					<?php if ($count != ($MAXPRODUCT - 2) && $count != ($MAXPRODUCT - 1)) { ?>
    				<div class="description" id="prodivdesc<?php echo $count; ?>">
    					<div class="description_content" id="prodesc<?php echo $count; ?>"></div>
    				</div>
    				<?php } ?>
    				<div class="catwatermark" id='prowatermark<?php echo $count; ?>'>...</div>
    			</div>
        <?php
        $count++;
    }
    ?>
		</div>
	</div>
</div>
</body>
<?php

llxFooter();

$db->close();
