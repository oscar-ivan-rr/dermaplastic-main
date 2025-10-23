<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script>
	$(document).ready(function(){
		var total = 0;
		$('#search_list_prod').width('80%');
		$('#agregar_prod').click(function() {
			var idprod = $('#list_prod').val();
			var socid = $('#socid').val();
			var desc = $('#custom_desc').val();
			$.ajax({
				url: <?php DOL_DOCUMENT_ROOT ?>'/takepos/ajax/ajaxprice.php',
				method: 'GET',
				dataType: 'json',
				data: {
					idprod: idprod,
					socid: socid,
					desc: desc
				}
			}).done(function(data) {
				console.log(data);
				total += parseFloat(data.subtotal);
				if (data.discount == null) {
					data.discount = 0;
				}
				var nuevoProd = $(
					'<tr>' +
						'<td>' + data.product + '</td>' +
						'<td>' + data.discount + '</td>' +
						'<td>' + pricejs(data.price, 'MT') + '</td>' +
						'<td>' + pricejs(data.subtotal, 'MT') + '</td>');
				$('#listofproducts tbody').append(nuevoProd);
				$('#total_col').html(pricejs(total, 'MT'));
			});
			$('#search_list_prod').val('');
			$('#custom_desc').val('');
		});
	});
</script>
</head>
<?php

if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk", "receiptprinter"));

$action = GETPOST('action');
$prodid = GETPOST('list_prod');
$socid = GETPOST('socid');

if ($action == 'add') {

}

$title = 'Punto de Venta';
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $title = 'TakePOS - ' . $conf->global->MAIN_APPLICATION_TITLE;
$head = '<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
print '<input type="hidden" id="globalpass" value="' . $conf->global->POS_ADMIN_PASSWORD . '" />';

$form = new Form($db);
print '<form name="check_price" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print '<input type="hidden" name="action" value="add">';
print '<table class="border centpercent center">';
print '<tr><td colspan=2>';
print $form->select_company(GETPOST('socid'), 'socid', '((s.client = 1 OR s.client = 3) AND s.status=1)', 1);
print '</td></tr>';
print '<tr><td>';
print $form->select_produits('Busqueda de productos', 'list_prod', '', 10, 1, -1, 2, '', 2);
print '</td><td><input id="custom_desc" placeholder="Descuento"><td><a id="agregar_prod" type="button" class="button">Agregar</a></td></tr>';
print '</table>';
print '<div id="divforlistofproducts">';
print '<table id="listofproducts" class="center centpercent">';
print '<thead><tr><th>Producto</th><th>Dto. %</th><th>P.U.</th><th>Subtotal</th></tr></thead>';
print '<tbody>';
print '</tbody>';
print '<tfoot><tr><td class="right" colspan=3>Total:</td><td id="total_col">0.00</td></tr></tfoot>';
print '</table>';
print '</div>';
print '</form>';
