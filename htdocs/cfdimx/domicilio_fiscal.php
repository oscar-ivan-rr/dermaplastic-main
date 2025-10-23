<?php
	global $user, $db, $conf;
    error_reporting(0);

    $zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

	if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
	if (! $res) die("Include of main fails");

	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once("class/facturacfdimx.class.php");

	$action	      = GETPOST('action');
	$id           = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
	$domicilio_id = GETPOST('domicilio_id','int') ?  GETPOST('domicilio_id','int') : 0;
	$validaciones     = array();
	$ban_validaciones = 0;

	$object = new Societe($db);
	$object->fetch($id);

	$titulo_tab = "Domicilio Fiscal - CFDI ".$conf->global->CFDIMX_VERSION_SAT;

	llxHeader('',$titulo_tab,'','','','','CFDI','',0,0);
	$form=new Form($db);

	$head = societe_prepare_head($object);
	// dol_fiche_head($head, 'tabdomicilioclient', $langs->trans("ThirdParty"),0,'company');
	print dol_get_fiche_head($head, 'tabdomicilioclient', $langs->trans("ThirdParty"),0,'company');

	$objFactura        = new FacturaCFDI($db);

	if($action == 'guardar'){
		// print '<pre>'; print_r($_REQUEST); print '</pre>';

		$datos_cliente = array(
		    				'rowid'             => $_REQUEST['rowid'] != '' ? $_REQUEST['rowid'] : -1,
		    				'fk_soc'            => $_REQUEST['fk_soc'] != '' ? $_REQUEST['fk_soc'] : -1,
						    'entity_id'         => $_REQUEST['entity_id'] != '' ? $_REQUEST['entity_id'] : -1,
						    'estatus'           => $_REQUEST['estatus'] != '' ? $_REQUEST['estatus'] : 0,
						    'rfc'               => $_REQUEST['rfc'] != '' ? $_REQUEST['rfc'] : null,
						    'nombre'            => $_REQUEST['nombre'] != '' ? $_REQUEST['nombre'] : null,
						    'cp'                => $_REQUEST['cp'] != '' ? $_REQUEST['cp'] : null,
						    'municipio'         => $_REQUEST['municipio'] != '' ? $_REQUEST['municipio'] : null,
						    'estado'            => $_REQUEST['estado'] != '' ? $_REQUEST['estado'] : null,
						    'residencia_fiscal' => $_REQUEST['residencia_fiscal'] != '' ? $_REQUEST['residencia_fiscal'] : -1,
						    'numregidtrib'      => $_REQUEST['numregidtrib'] != '' ? $_REQUEST['numregidtrib'] : null,
						    'regimenfiscal'     => $_REQUEST['regimenfiscal'] != '' ? $_REQUEST['regimenfiscal'] : null,
						    'direccion'         => $_REQUEST['direccion'] != '' ? $_REQUEST['direccion'] : null,
						    'pais'              => $_REQUEST['pais'] != '' ? $_REQUEST['pais'] : null,
						    'calle'             => $_REQUEST['calle'] != '' ? $_REQUEST['calle'] : null,
						    'clave_mpio'        => $_REQUEST['clave_mpio'] != '' ? $_REQUEST['clave_mpio'] : null,
						    'noint'             => $_REQUEST['noint'] != '' ? $_REQUEST['noint'] : null,
						    'noext'             => $_REQUEST['noext'] != '' ? $_REQUEST['noext'] : null,
						    'clave_col'         => $_REQUEST['clave_col'] != '' ? $_REQUEST['clave_col'] : null
		    			);

		$objFactura->emisor = $datos_cliente;
		$res_domicilio = $objFactura->guardarDomicilioFiscal();

		if($res_domicilio != 0){
			$validaciones     = $objFactura->errores;
			print '<script>location.href="?socid=' . $_REQUEST["socid"] . '"</script>';
		}else{
			$ban_validaciones = 1;
			print '<script>location.href="?socid=' . $_REQUEST["socid"] . '"</script>';
		}
	}?>

	<script>
		$(document).ready(function(){
			$('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
		});
	</script>

	<?php

	// print '<pre>'; print_r($domicilio); print '</pre>';

	if($action == '' || $action == 'nuevo' || $action == 'guardar'){
		if($action == 'guardar'){
			$domicilio  = $objFactura->emisor;
		}else{
			$domicilio  = $objFactura->getDomicilioFiscalCliente($id, $domicilio_id);
		}

		if($domicilio["rowid"] < 0){
			print '<div class="error hideonsmartphone clearboth">';
				print img_picto('', 'info', 'class="pictofixedwidth"');
				print 'Para el Timbrado de <strong>CFDI 4.0</strong> se requiere guardar el Domicilio Fiscal del Cliente.';
			print '</div>';
		}

		$campos_obligatorios40  = "<label style='color: #960707 !important; font-weight: bold; font-size: medium;'>•</label>";
		$url_guardar_formulario = DOL_URL_ROOT.'/cfdimx/domicilio_fiscal.php?socid='.$id.'&action=guardar';

		print '<div class="fichecenter">';
            print "<form action='".$url_guardar_formulario."' method='POST'>";

				print "<input type='hidden' name='rowid' id='rowid' value='".$domicilio["rowid"]."'>";
				print "<input type='hidden' name='fk_soc' id='fk_soc' value='".$domicilio["fk_soc"]."'>";
				print "<input type='hidden' name='entity_id' id='entity_id' value='".$domicilio["entity_id"]."'>";
				print "<input type='hidden' name='estatus' id='estatus' value='".$domicilio["estatus"]."'>";
				print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';

				if (count($validaciones) > 0 || $ban_validaciones == 1) {
					$color_mensaje    = 0;
					$msg_validaciones = '';

					if($ban_validaciones == 0 && count($validaciones) > 0){
						$color_mensaje = 1;
						$msg_validaciones .= "Error al guardar el Domicilio Fiscal.<br><br>";
						foreach($validaciones as $error){
							$msg_validaciones .= $error.'<br>';
						}
					}

					if($ban_validaciones == 1){
						$msg_validaciones = "Se guardo correctamente la información del Domicilio Fiscal.";
					}

					if($msg_validaciones != ''){
			            if($color_mensaje != ""){
				            if($color_mensaje == 1){
				                setEventMessage($msg_validaciones, 'errors');
				            }
					    }
			        }
		        }

		        if(@$color_mensaje == 0){
		            setEventMessage($msg_validaciones);
		        }
				print '<table width="100%" class="noborder">';
					print '<tbody>';
						print "<tr class='liste_titre'>";
							print "<th colspan='2'>";
								print img_picto('', 'building', 'class="pictofixedwidth"');
								print "<b>Datos Requeridos para Timbrado de Facturas</b>";
							print "</th>";
						print "</tr>";

						print "<tr>";
							print "<td class='fieldrequired'><b>RFC</b></td>";
							print "<td><input type='text' name='rfc' id='rfc' value='".$domicilio["rfc"]."' placeholder='ABT190328MX1'></td>";
						print "</tr>";

						print "<tr>";
							print "<td class='fieldrequired'><b>Nombre</b></td>";
							print "<td>";
								$titulo_nombre = "El Nombre debe ser tal cual esta registrado ante el SAT sin omitir espacios, acentos, puntos y ñ.";
								print "<textarea name='nombre' id='nombre' rows='3' cols='80' title='".$titulo_nombre."' placeholder='Auribox Consulting'>";
									print $domicilio["nombre"];
								print "</textarea>";
							print "</td>";
						print "</tr>";

						print "<tr>";
							$titulo_cp = "El Domicilio Fiscal (Código Postal) debe ser el que se registro ante el SAT.";
							print "<td class='fieldrequired'><b>Domicilio Fiscal (C.P.)</b></td>";
							print "<td>";
								print "<input type='text' name='cp' id='cp' value='".$domicilio["cp"]."' title='".$titulo_cp."' placeholder='01125'>";
								print '&nbsp;'.img_info($titulo_cp);
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td>Residencia Fiscal</td>";
							print "<td>";
								print img_picto('', 'country', 'class="pictofixedwidth"');
								print $objFactura->obtener_catalogo($domicilio["residencia_fiscal"],'residencia_fiscal',1,0);
								print '&nbsp;'.img_info('Solo llenar en caso de que se utilice para el Timbrado');
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td>NumRegIdTrib</td>";
							print "<td>";
								print "<input type='text' name='numregidtrib' id='numregidtrib' value='".$domicilio["numregidtrib"]."' placeholder='121585958'>";
								print '&nbsp;'.img_info('Solo llenar en caso de que se utilice para el Timbrado');
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td class='fieldrequired'><b>Regimen Fiscal</b></td>";
							print "<td>";
								print $objFactura->obtener_catalogo($domicilio["regimenfiscal"],'regimenfiscal',2,0);
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td>Dirección</td>";
							print "<td>";
								print "<textarea name='direccion' id='direccion' rows='3' cols='80'>";
									print $domicilio["direccion"];
								print "</textarea>";
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td>Municipio</td>";
							print "<td>";
								print img_picto('', 'region', 'class="pictofixedwidth"');
								print "<input type='text' name='municipio' id='municipio' value='".$domicilio["municipio"]."'>";
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td>Estado</td>";
							print "<td>";
								print img_picto('', 'state', 'class="pictofixedwidth"');
								print "<input type='text' name='estado' id='estado' value='".$domicilio["estado"]."'>";
							print "</td>";
						print "</tr>";

						print "<tr>";
							print "<td>País</td>";
							print "<td>";
								print img_picto('', 'country', 'class="pictofixedwidth"');
								print $objFactura->obtener_catalogo($domicilio["pais"],'pais',1,0);
							print "</td>";
						print "</tr>";
					print '</tbody>';
				print "</table>";

				if(!file_exists('cce.php')){
					print '<table width="100%" class="">';
						print "<tr>";
							print "<td colspan='2' align='center'>";
								print "<input type='submit' class='butAction' value='Guardar'>";
							print "</td>";
						print "</tr>";
					print "</table>";
				}

				if(file_exists('cce.php')){
					print '<br>';
					print '<table width="100%" class="noborder">';
						print '<tbody>';
							print "<tr class='liste_titre'>";
								print "<th colspan='2'>";
									print img_picto('', 'building', 'class="pictofixedwidth"');
									print "<b>Datos Requeridos para Comercio Exterior 1.1</b>";
								print "</th>";
							print "</tr>";

							print "<tr>";
								print "<td class='fieldrequired'><b>Calle</b></td>";
								print "<td><input type='text' name='calle' id='calle' value='".$domicilio["calle"]."'></td>";
							print "</tr>";

							print "<tr>";
								print "<td class='fieldrequired'><b>Clave Municipio</b></td>";
								print "<td><input type='text' name='clave_mpio' id='clave_mpio' value='".$domicilio["clave_mpio"]."'></td>";
							print "</tr>";

							print "<tr>";
								print "<td class='fieldrequired'><b>No. Interior</b></td>";
								print "<td><input type='text' name='noint' id='noint' value='".$domicilio["noint"]."'></td>";
							print "</tr>";

							print "<tr>";
								print "<td class='fieldrequired'><b>No. Exterior</b></td>";
								print "<td><input type='text' name='noext' id='noext' value='".$domicilio["noext"]."'></td>";
							print "</tr>";

							print "<tr>";
								print "<td class='fieldrequired'><b>Clave Colonia</b></td>";
								print "<td><input type='text' name='clave_col' id='clave_col' value='".$domicilio["clave_col"]."'></td>";
							print "</tr>";
						print '</tbody>';
					print "</table>";

					print '<table width="100%" class="">';
						print "<tr>";
							print "<td colspan='2' align='center'>";
								print "<input type='submit' class='butAction' value='Guardar'>";
							print "</td>";
						print "</tr>";
					print "</table>";
				}
			print "</form>";
		print '</div>';

		print '<div class="clearboth"></div>';

		// print '<div class="info hideonsmartphone clearboth">';
		// 	print img_info().'&nbsp;';
  //           print '<b>Nota:</b>El Nombre del Cliente para el RFC  </strong>.';
  //       print '</div>';
	}
	llxFooter();
?>