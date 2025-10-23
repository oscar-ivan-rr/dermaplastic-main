<script>
  $(document).ready(function(){
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	global $db, $conf;

	$form        = new Form($db);
	$extrafields = new ExtraFields($db);

	$action    = GETPOST('action');
	$opc_sel   = GETPOST('opc');
	$valor_sel = GETPOST('valor');
	$const_sel = GETPOST('const_cfdimx');

	##
	$titulo_ayuda_act    = "Activar";
	$titulo_ayuda_desact = "Desactivar";
	$btn_act             = "on";
	$btn_desact          = "off";

	#Inicio Descuentos
	$sql_descuentos =  " SELECT count(*) as exist FROM ".MAIN_DB_PREFIX."cfdimx_descuentos";
	$sql_descuentos .= " WHERE entity_id = ".$conf->entity;;

	$res_descuentos = $db->query($sql_descuentos);
	$obj_descuentos = $db->fetch_object($res_descuentos);

	if($obj_descuentos->exist == 0){
		$sql_insert_descuentos = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_descuentos(entity_id,mostrar) VALUES(".$conf->entity.", 1)";
		$res_insert_descuentos = $db->query($sql_insert_descuentos);
	}

	$sql_desc_list = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_descuentos";
	$sql_desc_list .= " WHERE entity_id = ".$conf->entity;

	$res_desc_list = $db->query($sql_desc_list);
	$obj_desc_list = $db->fetch_object($res_desc_list);
	#Termina Descuentos

	#Inicio de Valores Definidos
	$opc_descuentos     = $obj_desc_list->mostrar;
	$opc_ret_ind        = $conf->global->CFDIMX_RET_INDIVIDUALES;
	$opc_debug_timbrado = $conf->global->CFDIMX_DEBUG_TIMBRADO;
	$opc_desc_mayus     = $conf->global->CFDIMX_DESC_PDF;

	$pdf_desc_prod_cat_ref       = $conf->global->CFDIMX_DESC_PROD_CAT_REF;
	$pdf_desc_prod_cat_etiqueta  = $conf->global->CFDIMX_DESC_PROD_CAT_ETIQUETA;
	$pdf_desc_prod_cat_desc      = $conf->global->CFDIMX_DESC_PROD_CAT_DESC;
	$pdf_desc_prod_no_cat_desc   = $conf->global->CFDIMX_DESC_PROD_NO_CAT_DESC;
	$xml_limit_desc              = $conf->global->CFDIMX_LIM_DESC;
	
	#Termina de Valores Definidos

	// print $action;
	// print '<pre>'; print_r($_REQUEST); print '</pre>';



	if($opc_sel != "" && $opc_sel > 0){
		switch ($opc_sel) {
			case 1:
					$sql_update_descuentos = "UPDATE ".MAIN_DB_PREFIX."cfdimx_descuentos";
					$sql_update_descuentos .= " SET";
						$sql_update_descuentos .= " mostrar = ".$valor_sel;
					$sql_update_descuentos .= " WHERE entity_id = ".$conf->entity;
					print $sql_update_descuentos;
					$res_update_descuentos = $db->query($sql_update_descuentos);

					// print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
				break;
			case 2:
					dolibarr_set_const($db, "CFDIMX_RET_INDIVIDUALES", $valor_sel, 'chaine', 0, '', $conf->entity);

					if($valor_sel == 1){
						#Extrafield aplicar_ret_individual - product
						$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "aplicar_ret_individual" AND elementtype="product"';

						$r = $db->query($sql);
						if ($db->num_rows($r) > 0) {
						}else{
							$extrafields->addExtraField('aplicar_ret_individual', 'Aplicar Retención Individual', 'boolean', 104, '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',1,'',1);
						}

						#Extrafield aplicar_ret_individual - facturedet
						$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "aplicar_ret_individual" AND elementtype="facturedet"';

						$r = $db->query($sql);
						if ($db->num_rows($r) > 0) {
						}else{
							$extrafields->addExtraField('aplicar_ret_individual', 'Aplicar Retención Individual', 'boolean', 104, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',1,'',1);
						}
					}
				break;

			default:
				dolibarr_set_const($db, trim($const_sel), trim($valor_sel), 'chaine', 0, '', $conf->entity);
				break;
		}


		// print $const_sel; die;
		print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	// if($action=='actualdesc'){
	// 	$valmostrar = GETPOST('valmostrar');

	// 	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_descuentos
	// 			SET mostrar=".$valmostrar." WHERE entity_id=".$conf->entity;
	// 	$rqs=$db->query($sql);

	// 	print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	// }

	if($action=='actualretind'){
		// $valretind = GETPOST('valhabilitarretind');

		// dolibarr_set_const($db, "CFDIMX_RET_INDIVIDUALES", GETPOST("valhabilitarretind"), 'chaine', 0, '', $conf->entity);

        // if($valretind == 1){
	    //     #Extrafield aplicar_ret_individual - product
		// 	$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "aplicar_ret_individual" AND elementtype="product"';

		// 	$r = $db->query($sql);
		// 	if ($db->num_rows($r) > 0) {
		// 	}else{
		// 		$extrafields->addExtraField('aplicar_ret_individual', 'Aplicar Retención Individual', 'boolean', 104, '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',1,'',1);
		// 	}

		// 	#Extrafield aplicar_ret_individual - facturedet
		// 	$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "aplicar_ret_individual" AND elementtype="facturedet"';

		// 	$r = $db->query($sql);
		// 	if ($db->num_rows($r) > 0) {
		// 	}else{
		// 		$extrafields->addExtraField('aplicar_ret_individual', 'Aplicar Retención Individual', 'boolean', 104, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',1,'',1);
		// 	}
		// }

        // print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	if($action=='actualdebtim'){
		$valhabilitardebug = GETPOST('valhabilitardebug');

		dolibarr_set_const($db, "CFDIMX_DEBUG_TIMBRADO", GETPOST("valhabilitardebug"), 'chaine', 0, '', $conf->entity);

        print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	if($action=='actualdescm'){
		$valhabilitardescm = GETPOST('valhabilitardescm');

		dolibarr_set_const($db, "CFDIMX_DESC_PDF", GETPOST("valhabilitardescm"), 'chaine', 0, '', $conf->entity);

        print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	if($action=='actualdesc6'){
		$desc6 = GETPOST('desc6');

		dolibarr_set_const($db, "CFDIMX_DESC_PROD_CAT_ETIQUETA", GETPOST("desc6"), 'chaine', 0, '', $conf->entity);

        print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	if($action=='actualdesc7'){
		$desc7 = GETPOST('desc7');

		dolibarr_set_const($db, "CFDIMX_DESC_PROD_CAT_DESC", GETPOST("desc7"), 'chaine', 0, '', $conf->entity);

        print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	if($action=='actualdesc8'){
		$desc8 = GETPOST('desc8');

		dolibarr_set_const($db, "CFDIMX_DESC_PROD_NO_CAT_DESC", GETPOST("desc8"), 'chaine', 0, '', $conf->entity);

        print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}

	if($action=='actualdesc9'){
		$desc9 = GETPOST('desc9');

		dolibarr_set_const($db, "CFDIMX_DESC_PROD_CAT_REF", GETPOST("desc9"), 'chaine', 0, '', $conf->entity);

        print "<script>window.location.href='cfdimx.php?mod=configopcional'</script>";
	}


	print '<div class="div-table-responsive">';
		print '<table class="noborder" width="100%">';
			print '<tbody>';
				print '<tr class="liste_titre">';
					print '<th align="center"><strong>Estatus</strong></th>';
					print '<th align="left"><strong>Funcionalidad</strong></th>';
					print '<th align="left"><strong>Extrafields</strong></th>';
				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($opc_descuentos == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=1">';
								print img_picto($titulo_ayuda_desact, $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=1">';
								print img_picto($titulo_ayuda_act, $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>Mostrar Descuentos en PDF y XML</td>';
					print '<td>';
						print '<span class="badge badge-status4 badge-status">No se requiere ninguno.</span>';
					print '</td>';
				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($opc_ret_ind == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=2">';
								print img_picto($titulo_ayuda_desact." Retenciones Individuales", $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=2">';
								print img_picto($titulo_ayuda_act." Retenciones Individuales", $btn_desact);
						}
						print '</a>';
					print '</td>';

					// if($status_ret_individual == 1){
					// 	print '<td align="center">';
					// 		print '<a href="cfdimx.php?mod=configopcional&action=actualretind&valhabilitarretind=0" title="Predeterminado">';
					// 			print img_picto('Desactivar Retenciones Individuales', 'on');
					// 		print '</a>';
					// 	print '</td>';
					// }else{
					// 	print '<td align="center">';
					// 		print '<a href="cfdimx.php?mod=configopcional&action=actualretind&valhabilitarretind=1" title="Predeterminado">';
					// 			print img_picto('Activar Retenciones Individuales', 'off');
					// 		print '</a>';
					// 	print '</td>';
					// }

					print "<td>Retenciones Individuales</td>";
					$detalle_ret_ind .= '<p>';
						$detalle_ret_ind .= 'Se agregaron los siguientes extrafields en el módulo de <b>Productos</b> en el apartado de <b>Campos adicionales</b> y en el módulo de <b>Facturas</b> en el apartado de <b>Campos adicionales (líneas)</b>.</b>';
					$detalle_ret_ind .= '</p>';
					$detalle_ret_ind .= '<br>';
					$detalle_ret_ind .= '<table class="noborder" width="100%">';
						$detalle_ret_ind .= '<tbody>';
							$detalle_ret_ind .= '<tr class="liste_titre">';
								$detalle_ret_ind .= '<th><strong>Clave de traducción o cadena</strong></th>';
								$detalle_ret_ind .= '<th><strong>Código</strong></th>';
								$detalle_ret_ind .= '<th><strong>Tipo</strong></th>';
							$detalle_ret_ind .= '</tr>';
							$detalle_ret_ind .= '<tr>';
								$detalle_ret_ind .= '<td>Aplicar Retención Individual</td>';
								$detalle_ret_ind .= '<td>aplicar_ret_indvidual</td>';
								$detalle_ret_ind .= '<td>Boolean</td>';
							$detalle_ret_ind .= '</tr>';
						$detalle_ret_ind .= '</tbody>';
					$detalle_ret_ind .= '</table>';
					// $detalle_ret_ind .= '<img src="../img/Hecarim_4.jpg">';

					print '<td>'.$form->textwithpicto('', $detalle_ret_ind, 1, 'list','', 0, 3, 'info').'</td>';
				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($opc_debug_timbrado == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=3&const_cfdimx=CFDIMX_DEBUG_TIMBRADO">';
								print img_picto($titulo_ayuda_desact." Debug Timbrado", $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=3&const_cfdimx=CFDIMX_DEBUG_TIMBRADO">';
								print img_picto($titulo_ayuda_act." Debug Timbrado", $btn_desact);
						}
						print '</a>';
					print '</td>';

					print "<td>Debug Timbrado</td>";
					print '<td>';
						print '<span class="badge badge-status4 badge-status">No se requiere ninguno.</span>';
					print '</td>';
				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($opc_desc_mayus == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=4&const_cfdimx=CFDIMX_DESC_PDF">';
								print img_picto($titulo_ayuda_desact." Descripción en Mayúsculas", $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=4&const_cfdimx=CFDIMX_DESC_PDF">';
								print img_picto($titulo_ayuda_act." Descripción en Mayúsculas", $btn_desact);
						}
						print '</a>';
					print '</td>';

					print "<td>Mostrar Descripción del Producto en el PDF y XML en Mayúsculas.</td>";
					print '<td>';
						print '<span class="badge badge-status4 badge-status">No se requiere ninguno.</span>';
					print '</td>';
				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($xml_limit_desc == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=9&const_cfdimx=CFDIMX_LIM_DESC">';
								print img_picto($titulo_ayuda_desact." Cortar Descripción", $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=9&const_cfdimx=CFDIMX_LIM_DESC">';
								print img_picto($titulo_ayuda_act." Cortar Descripción", $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>Cortar la Descripción cuando supere el límite (1,000 caracteres) en el XML.</td>';
					print '<td>';
						print '<span class="badge badge-status4 badge-status">No se requiere ninguno.</span>';
					print '</td>';
				print '</tr>';
			print '</tbody>';
		print '</table>';
		print '<br>';

		print '<script type="text/javascript">
			jQuery(document).ready(function() {
				$("#btn_ocultar_mostrar").click(function() {
					$("#tmporal_ref").show("fast");
					$("#head_hide").show("fast");
					$("#head_show").hide("fast");

					var aux = $("#oc_pdf").val();

					if(aux == 0){
						$("#oc_pdf").val(1);

						$("#lista_ajustes_pdf").show("fast");											
						$("#icono_1").removeClass("fa fa-plus-circle valignmiddle btnTitle-icon");
						$("#icono_1").addClass("fa fa-minus-circle valignmiddle btnTitle-icon");
						$("#btn_ocultar_mostrar").prop("title","Ocultar Ajustes PDF Factura - Productos de Catálogo");
					}else{
						$("#oc_pdf").val(0);

						$("#lista_ajustes_pdf").hide("fast");						
						$("#icono_1").removeClass("fa fa-minus-circle valignmiddle btnTitle-icon");
						$("#icono_1").addClass("fa fa-plus-circle valignmiddle btnTitle-icon");
						$("#btn_ocultar_mostrar").prop("title","Mostrar Ajustes PDF Factura - Productos de Catálogo");
					}
				});
			});			
		</script>';

		print '<table class="noborder" width="100%">';
			
			print '<tr class="liste_titre">';
				print '<th colspan="2">';
					print '<input type="hidden" name="oc_pdf" id="oc_pdf" value="0">';

					print '<button id="btn_ocultar_mostrar" class="butAction" title="Mostrar Ajustes PDF Factura - Productos de Catálogo">';
						print '<span id="icono_1" class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>';
					print '</button>';						
					print '&nbsp;';

					print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>';
					print '&nbsp;';
					print '<strong>Ajustes PDF Factura - Productos de Catálogo</strong>';
				print '</th>';
			print '</tr>';

			print '<tbody id="lista_ajustes_pdf" style="display: none;">';
				print '<tr>';
					print '<td align="center">';
						if($pdf_desc_prod_cat_ref == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=5&const_cfdimx=CFDIMX_DESC_PROD_CAT_REF">';
								print img_picto($titulo_ayuda_desact, $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=5&const_cfdimx=CFDIMX_DESC_PROD_CAT_REF">';
								print img_picto($titulo_ayuda_act, $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>Agregar la Referencia de la Ficha del Producto.</td>';

				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($pdf_desc_prod_cat_etiqueta == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=6&const_cfdimx=CFDIMX_DESC_PROD_CAT_ETIQUETA">';
								print img_picto($titulo_ayuda_desact, $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=6&const_cfdimx=CFDIMX_DESC_PROD_CAT_ETIQUETA">';
								print img_picto($titulo_ayuda_act, $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>Agregar la Etiqueta de la Ficha del Producto.</td>';

				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($pdf_desc_prod_cat_desc == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=7&const_cfdimx=CFDIMX_DESC_PROD_CAT_DESC">';
								print img_picto($titulo_ayuda_desact, $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=7&const_cfdimx=CFDIMX_DESC_PROD_CAT_DESC">';
								print img_picto($titulo_ayuda_act, $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>Agregar la Descripción de la Ficha del Producto.</td>';

				print '</tr>';

				print '<tr>';
					print '<td align="center">';
						if($pdf_desc_prod_no_cat_desc == 1){
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=0&opc=8&const_cfdimx=CFDIMX_DESC_PROD_NO_CAT_DESC">';
								print img_picto($titulo_ayuda_desact, $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=configopcional&action=update_ajuste&valor=1&opc=8&const_cfdimx=CFDIMX_DESC_PROD_NO_CAT_DESC">';
								print img_picto($titulo_ayuda_act, $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>Agregar la Descripción de la Partida de la Factura.</td>';
				print '</tr>';
			print '</tbody>';
		print '</table>';

						
			/*print '<table class="noborder" width="100%">';				
				print '<tr class="liste_titre">';
					print '<th colspan="2">';
						print '<input type="hidden" name="oc_pdf" id="oc_pdf" value="0">';

						print '<button id="btn_ocultar_mostrar" class="butAction" title="Mostrar Ajustes PDF Factura - Productos de Catálogo">';
							print '<span id="icono_1" class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>';
						print '</button>';						
						print '&nbsp;';

						print '<span class="fa fa-money valignmiddle btnTitle-icon"></span>';
						print '&nbsp;';
						print '<strong>Ajustes Complementos de Pagos</strong>';
					print '</th>';
				print '</tr>';
				
				print '<tbody id="lista_ajustes_pagos" style="display: none;">';
				print '</tbody>';
			print '</table>';*/
	print '</div>';

	print dol_get_fiche_end();

	llxFooter();