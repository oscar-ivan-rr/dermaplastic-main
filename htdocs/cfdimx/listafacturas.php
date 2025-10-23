<?php
	global $user, $db, $conf;
    error_reporting(0);

    $zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

	if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
	if (! $res) die("Include of main fails");
	
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once("class/facturacfdimx.class.php");
	
	$action	= GETPOST('action');

	$facturestatic = new Facture($db);
	$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
	$object = new Societe($db);
	$object->fetch($id);

	$titulo_tab = "Listado de Facturas - CFDI ".$conf->global->CFDIMX_VERSION_SAT;

	llxHeader('',$titulo_tab,'','','','','CFDI','',0,0);
	$form=new Form($db);
	
	$head = societe_prepare_head($object);
	dol_fiche_head($head, 'tablistfact', $langs->trans("ThirdParty"),0,'company');
?>

<script>
	$(document).ready(function(){ 
		$('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
	});
</script>
<?php

	$objFactura   = new FacturaCFDI($db);
	$num_facturas = $objFactura->getfacturasTabCliente($id);

	if($num_facturas > 0){
		print "<table width='100%' class='noborder'>";
			print '<thead>';
			    print "<tr class='liste_titre'>";
					print "<th><strong>#</strong></th>";
					print "<th><strong>Factura</strong></th>";
					print "<th><strong>Fecha</strong></th>";
					print "<th><strong>Total</strong></th>";
					print "<th><strong>UUID</strong></th>";
	                print "<th><strong>Estado</strong></th>";
				print "</tr>";
			print '</thead>';

			print '<tbody>';
				$contador = 1;
				foreach ($objFactura->lista_facturas as $i => $factura) {
					print '<tr>';
						print "<td>".$contador."</td>";
						print "<td>".$factura["factura"]."</td>";
						print "<td>".$factura["fecha"]."</td>";
						print "<td>".$factura["total"]."</td>";
						print "<td>".$factura["cfdi"]."</td>";
						print "<td>".$factura["estatus"]."</td>";
					print "</tr>";
					$contador++;
				}
			print '</tbody>';
		print "</table>";
	}else{
		print '<div class="info hideonsmartphone clearboth">';
			print '<strong>Sin Facturas Asociadas</strong>';
		print '</div>';
	}

	llxFooter();
?>