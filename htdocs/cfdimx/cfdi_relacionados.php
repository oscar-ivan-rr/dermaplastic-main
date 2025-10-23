<script>
  $(document).ready(function(){ 
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
	require_once("class/complementos.class.php");

	global $db, $conf;

	$objComplementos = new ComplementosCFDI($db);

	// $color = "";
	$msg_cfdi_final="";
	$ban_timbrado = 0;

	$facid = (GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
	$action=GETPOST('action');
	$id_cfdi_rel = GETPOST('id_cfdi_rel','int');
	$ref=GETPOST("ref");

	$langs->load('bills');
	$langs->load('banks');
	$langs->load('companies');

	#Se valida si la factura ya esta timbrada o no
	$sql_val_fac = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = " . $facid;

	$resql = $db->query($sql_val_fac);
	$result_val_fac = $db->num_rows($resql);

	$valida_fac_tim = 0;
	if($result_val_fac > 0){
		$factura_timbrada = $db->fetch_object($resql);
		$valida_fac_tim = 1;
	}

	$uuid_validar    = trim(GETPOST('uuid_relacionado')?GETPOST('uuid_relacionado'):GETPOST('sel_uuid_relacionado'));
	
	if($uuid_validar != ''){
		
		$tamanio_uuid    = strlen($uuid_validar);
		$validacion_uuid = 0;

		if($tamanio_uuid == 36){
			$separar_uuid_segmentos = explode("-", $uuid_validar);
			$cantidad_segmentos = count($separar_uuid_segmentos);

			if($cantidad_segmentos == 5){
				$segmento1 = strlen($separar_uuid_segmentos[0]);
				$segmento2 = strlen($separar_uuid_segmentos[1]);
				$segmento3 = strlen($separar_uuid_segmentos[2]);
				$segmento4 = strlen($separar_uuid_segmentos[3]);
				$segmento5 = strlen($separar_uuid_segmentos[4]);

				if($segmento1 != 8)
					$validacion_uuid = 1;

				if($segmento2 != 4)
					$validacion_uuid = 1;

				if($segmento3 != 4)
					$validacion_uuid = 1;

				if($segmento4 != 4)
					$validacion_uuid = 1;
				
				if($segmento5 != 12)
					$validacion_uuid = 1;
			}else{
				$validacion_uuid = 1;
			}
		}else{
			$validacion_uuid = 1;
		}
	}

	if($action == 'add_uuid'){

		#Se valida que el UUID cumpla con el formato correcto
		if($validacion_uuid == 0){
			##Se valida que el UUID no este asociado a la factura			
			$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE fk_facture=".$facid." AND uuid='".$uuid_validar."'";

			$result_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
			$num_cfdi_rel = $db->num_rows($result_valida_cfdi_rel);

			if($num_cfdi_rel > 0){
				// $color = '#f99696';
				$ban_timbrado = 1;
				$msg_cfdi_final = "Error: No se puede agregar el UUID '".$uuid_validar."', porque ya esta en la lista de UUID Relacionados.";
			}else{
				if($uuid_validar!=''){
					$sql_cfdi_rel = "INSERT INTO 
						".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados
							(fk_facture,uuid)
						VALUES
							(
								$facid,
								'".$uuid_validar."'
							)
						";
				
					$res_cfdi_rel = $db->query($sql_cfdi_rel);

					if($res_cfdi_rel){
						// $color = '#98f996';
						$msg_cfdi_final = "Se agrego correctamente el UUID '".$uuid_validar."'";
					}
				}else{
					// $color = '#f99696';
					$ban_timbrado = 1;
					$msg_cfdi_final = "Error: No se puede agregar el UUID '".$uuid_validar."' ya que no cumple con el formato requerido.";
				}		
			}
		}else{
			// $color = '#f99696';
			$ban_timbrado = 1;
			$msg_cfdi_final = "Error: No se puede agregar el UUID '".$uuid_validar."', porque no cumple con la estructura requerida.";
		}
	}

	if($action == 'guardar_cfdi_rel' && isset($_REQUEST["guardar_cfdi"])){

		if($validacion_uuid == 0){
			##Se valida que el UUID no este asociado a la factura		
			$sql_valida_cfdi_rel = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE fk_facture=".$facid." AND uuid='".$uuid_validar."'";

			$result_valida_cfdi_rel = $db->query($sql_valida_cfdi_rel);
			$num_cfdi_rel = $db->num_rows($result_valida_cfdi_rel);

			if($num_cfdi_rel > 0){
				// $color = '#f99696';
				$ban_timbrado = 1;
				$msg_cfdi_final = "Error: No se puede actualizar el UUID '".$uuid_validar."', porque ya esta en la lista de UUID Relacionados.";
			}else{
				if($uuid_validar != ''){
					$sql_cfdi_rel = "
						UPDATE 
							".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados
						SET
							uuid = '".$uuid_validar."'
						WHERE
							rowid = '".$id_cfdi_rel."'
					";

					$res_cfdi_rel = $db->query($sql_cfdi_rel);

					if($res_cfdi_rel){
						// $color = '#98f996';
						$msg_cfdi_final = "Se actualizo correctamente el UUID '".$uuid_validar."'";
					}
				}else{
					// $color = '#f99696';
					$ban_timbrado = 1;
					$msg_cfdi_final = "Error: No se puede actualizar el UUID '".$uuid_validar."' ya que no cumple con el formato requerido.";
				}		
			}
		}else{
			// $color = '#f99696';
			$ban_timbrado = 1;
			$msg_cfdi_final = "Error: No se puede actualizar el UUID '".$uuid_validar."', porque no cumple con la estructura requerida.";
		}
	}

	if($action == 'eliminar'){
		$sql_cfdi_rel = "
					DELETE FROM 
						".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados
					WHERE
						rowid = '".$id_cfdi_rel."'
				";

		$res_cfdi_rel = $db->query($sql_cfdi_rel);

		if($res_cfdi_rel){
			// $color = '#98f996';
			$msg_cfdi_final = "Se elimino correctamente el UUID '".$uuid_validar."'";
		}else{
			// $color = '#f99696';
			$ban_timbrado = 1;
			$msg_cfdi_final = "Error: No se puede actualizar el UUID '".$uuid_validar."', porque no cumple con la estructura requerida.";
		}
	}

	if($action == 'save_tipo_rel'){
		$sql_update_rel = "
				UPDATE 
					".MAIN_DB_PREFIX."facture_extrafields 
				SET 
					cfdidoctiporelacion='".$_REQUEST["sel_tipo_rel"]."' 
				WHERE 
					fk_object=".$facid;

		$res_sql_update_rel = $db->query($sql_update_rel);

		if($res_sql_update_rel){
			// $color = '#98f996';
			$msg_cfdi_final = "Se actualizo correctamente el nuevo tipo de Relación";
		}else{
			// $color = '#f99696';
			$ban_timbrado = 1;
			$msg_cfdi_final = "Error: No se puede actualizar correctamente el nuevo tipo de Relación, contacte al área de soporte.";
		}
	}

	if ($msg_cfdi_final != "") {	    
	    if($ban_timbrado == 0){
            setEventMessage($msg_cfdi_final, 'mesgs');
        }else{
            dol_htmloutput_errors($msg_cfdi_final);
        }
	}

	$object=new Facture($db);
	$object->fetch($facid,$ref);

	function obtener_catalogo($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion, $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0)
	{
	    global $conf,$user,$langs,$db;
	    // If no preselected user defined, we take current user
	    if ((is_numeric($selected) && ($selected < -2 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;
	    $excludeUsers=null;
	    $includeUsers=null;
	    // Permettre l’exclusion d’utilisateurs
	    if (is_array($exclude)) $excludeUsers = implode("','",$exclude);
	    // Permettre l’inclusion d’utilisateurs
	    if (is_array($include)) $includeUsers = implode("','",$include);
	    else if ($include == 'hierarchy')
	    {
	        // Build list includeUsers to have only hierarchy
	        $userid=$user->id;
	        $include=array();
	        if (empty($user->users) || ! is_array($user->users)) $user->get_full_tree();
	        foreach($user->users as $key => $val)
	        {
	            if (preg_match('/'.$userid.'/',$val['fullpath'])) $include[]=$val['id'];
	        }
	        $includeUsers = implode("','",$include);
	    }
	    $out='';

		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_rel WHERE active = 1";
		if($tipo_informacion == 1)
			$sql .= " AND code='".$selected."'";

	   	$resql=$db->query($sql);

	    if ($resql)
	    {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        if ($num)
	        {
	            // Enhance with select2
	            $nodatarole='';
	            if ($conf->use_javascript_ajax)
	            {
	                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	                $comboenhancement = ajax_combobox($htmlname);
	                $out.=$comboenhancement;
	                $nodatarole=($comboenhancement?' data-role="none"':'');
	            }
	            $out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
	            if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
	            if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
	            $out.= '<option value="-1"></option>';
	            $i=0;

	            while ($rw = $db->fetch_object($resql)) {
	            	$valor    = $rw->code;
	            	$etiqueta = $rw->code." - ".$rw->label;

	                if ($selected == $rw->code) {
	                    $out.= '<option value="'.$valor.'" selected>';
	                }else {
	                    $out.= '<option value="'.$valor.'">';
	                }
	                $out.= $etiqueta."</option>";
	            }
	        }else{
	            $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" disabled>';
	            $out.= '<option value="">'.$langs->trans("None").'</option>';
	        }
	        $out.= '</select>';

	        if($tipo_informacion == 1)
	        	$out = $etiqueta;
	    }else{
	        dol_print_error($db);
	    }
	    return $out;
	}

	function obtener_cfdi_relacionados($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion, $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0)
	{
	    global $conf,$user,$langs,$db;
	    // If no preselected user defined, we take current user
	    if ((is_numeric($selected) && ($selected < -2 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;
	    $excludeUsers=null;
	    $includeUsers=null;
	    // Permettre l’exclusion d’utilisateurs
	    if (is_array($exclude)) $excludeUsers = implode("','",$exclude);
	    // Permettre l’inclusion d’utilisateurs
	    if (is_array($include)) $includeUsers = implode("','",$include);
	    else if ($include == 'hierarchy')
	    {
	        // Build list includeUsers to have only hierarchy
	        $userid=$user->id;
	        $include=array();
	        if (empty($user->users) || ! is_array($user->users)) $user->get_full_tree();
	        foreach($user->users as $key => $val)
	        {
	            if (preg_match('/'.$userid.'/',$val['fullpath'])) $include[]=$val['id'];
	        }
	        $includeUsers = implode("','",$include);
	    }
	    $out='';

	    // $sql ="
	    // 	SELECT
		// 	    cfdi.*
		// 	FROM
		// 	    ".MAIN_DB_PREFIX."cfdimx AS cfdi
		// 	LEFT JOIN ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados AS cfdi_rel ON cfdi_rel.fk_facture = cfdi.fk_facture
		// 	WHERE
		// 		NOT EXISTS (SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE uuid = cfdi.uuid)
		// 		;
		// 	";
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx";

	   	$resql=$db->query($sql);

	    if ($resql)
	    {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        if ($num)
	        {
	            // Enhance with select2
	            $nodatarole='';
	            if ($conf->use_javascript_ajax)
	            {
	                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
	                $comboenhancement = ajax_combobox($htmlname);
	                $out.=$comboenhancement;
	                $nodatarole=($comboenhancement?' data-role="none"':'');
	            }
	            $out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
	            if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
	            if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
	            $out.= '<option value="-1"></option>';
	            $i=0;

	            while ($rw = $db->fetch_object($resql)) {
	            	$valor    = $rw->uuid;
	            	$etiqueta = $rw->factura_seriefolio." - ".$rw->fecha_timbrado." - ".$rw->uuid;

	                if ($selected == $rw->uuid) {
	                    $out.= '<option value="'.$valor.'" selected>';
	                }else {
	                    $out.= '<option value="'.$valor.'">';
	                }
	                $out.= $etiqueta."</option>";
	            }
	        }else{
	            $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" disabled>';
	            $out.= '<option value="">'.$langs->trans("None").'</option>';
	        }
	        $out.= '</select>';

	        if($tipo_informacion == 1)
	        	$out = $etiqueta;
	    }else{
	        dol_print_error($db);
	    }
	    return $out;
	}

	print '<div class="fichecenter">';

        print '<div class="fichehalfleft">';

            // Invoice content
            print '<table class="noborder" width="100%">';
            	// print '<thead>';
	            	print '<tr class="liste_titre">';
	            		print '<td colspan="2" align="center"><strong>Datos del comprobante</strong></td>';
	            	print '</tr>';
	            // print '</thead>';

	            // print '<tbody>';
	            	// Ref
	                print '<tr>';
	                	print '<td >' . $langs->trans('Ref') . '</td>';
	                	print '<td colspan="5">';
	                		// print "<b><a href='".DOL_MAIN_URL_ROOT."/cfdimx/facture.php?id=".$facid."' style='font-weight: bold !important; color: rgb(100,60,20) !important; font-size: 1.2em !important;'>".$object->ref."</a></b>";
	                			// print $object->getNomUrl(1);
								print '<strong>';
									print $objComplementos->getNomUrl($facid, 1);
								print '</strong>';								
	                	print '</td>';
	                print '</tr>';

	                // Tipo Relacion
	                print '<tr>';
	                	print '<td class="fieldrequired">Tipo de Relación';
	                		if($valida_fac_tim == 0 && GETPOST('action') != 'edit_tipo_rel')
		                		print '<a class="reposition editfielda" href="complementos_cfdimx.php?facid='.$object->id.'&amp;action=edit_tipo_rel&mod=cfdi_rel"><span class="fa fa-pencil marginleftonly valignmiddle" style=" color: #444;" alt="Modificar Tipo de Relación" title="Modificar Tipo de Relación"></span>  </a>';
		                print '</td>';
		                print '<td>';
		                	if(GETPOST('action') == 'edit_tipo_rel'){
		                		print "<form method='POST' action='complementos_cfdimx.php?facid=".$facid."&action=save_tipo_rel&mod=cfdi_rel'>";
									print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
									print obtener_catalogo($object->array_options["options_cfdidoctiporelacion"],"sel_tipo_rel",0,0);
		                			print '<input type="submit" value="Actualizar" class="butAction">';
		                		print '</form>';
		                	}else{
	                			print '<strong>'.obtener_catalogo($object->array_options["options_cfdidoctiporelacion"],"label_tipo_rel",0,1).'</strong>';
		                	}
		                print '</td>';
	                print '</tr>';
	            // print '</tbody>';
            print '</table>';
        print '</div>';

        print '<div class="fichehalfright">';
        	print '<table class="noborder" width="100%">';
        		print '<thead>';
	            	print '<tr class="liste_titre">';
	            		print '<td colspan="2" align="center"><strong>UUID Relacionados</strong></td>';
	            	print '</tr>';
	            print '</thead>';

            	$sql_res_leyenda = "
            		SELECT
					    cfdi_rel.*,
					    cfdi.factura_seriefolio,
					    cfdi.fk_facture AS fac_folio,
					    cfdi.fecha_emision
					FROM
					    ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados AS cfdi_rel
					LEFT JOIN ".MAIN_DB_PREFIX."cfdimx AS cfdi
					ON
					    cfdi.uuid = cfdi_rel.uuid
					WHERE
					    cfdi_rel.fk_facture = ".$facid;

				$result_leyenda = $db->query($sql_res_leyenda);
				$num_leyendas = $db->num_rows($result_leyenda);

				if($num_leyendas > 0){

					print "<table width='100%' class='noborder'>";
						print '<thead>';
							print '<tr class="liste_titre">';
								print '<td><strong>Factura</strong></td>';
								print '<td><strong>UUID</strong></td>';
								if($valida_fac_tim == 0)
									print '<td>&nbsp;</td>';
							print '</tr>';
						print '<thead>';

						$m = 0;
						while($rs=$db->fetch_object($result_leyenda)){
					    	if($m==0){
					    		$aa=' ';
					    		$m=1;
					    	}else{
					    		$aa=' ';
					    		$m=0;
					    	}

					    	print '<tr '.$aa.'>';
							
							if($action == 'modificar' && $id_cfdi_rel == $rs->rowid){
								print "<form method='POST' action='complementos_cfdimx.php?facid=".$facid."&action=guardar_cfdi_rel&mod=cfdi_rel'>";
									print "<td><input type='text' size='50' name='uuid_relacionado' value='".$rs->uuid."' required></td>";
									print '<td>';
										print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
										print "<input type='hidden' name='id_cfdi_rel' value='".$rs->rowid."'>";
										print '<input type="submit" class="button" name="guardar_cfdi" value="Guardar">';
										print '<input type="submit" class="button" name="cancel" value="Cancelar">';
								print "</form>";
							}else{
								if($rs->factura_seriefolio == null){
									print "<td><strong>Factura Externa</strong></td>";
								}else{
									print "<td>";
										// $titulo = "Ver la Factura ".$rs->factura_seriefolio;
										// print "<a href='".DOL_MAIN_URL_ROOT."/compta/facture/card.php?id=".$rs->fac_folio."'  target='_blank' title='".$titulo."'>".$rs->factura_seriefolio."</a>";

										$factura = new Facture($db);
										$factura->fetch($rs->fac_folio);
										print $factura->getNomUrl(1);
									print "</td>";
								}

								print "<td>".$rs->uuid."</td>";
								
								if($valida_fac_tim == 0){
									print '<td>';
										print '<a href="complementos_cfdimx.php?facid='.$facid.'&id_cfdi_rel='.$rs->rowid.'&action=modificar&mod=cfdi_rel"><span class="fa fa-pencil marginleftonly valignmiddle" style=" color: #444;" alt="Modificar UUID Relacionado" title="Modificar UUID Relacionado""></span></a>';
										print '<a href="complementos_cfdimx.php?facid='.$facid.'&id_cfdi_rel='.$rs->rowid.'&action=eliminar&mod=cfdi_rel&uuid_relacionado='.$rs->uuid.'"><span class="fa fa-trash marginleftonly valignmiddle pictoe" style=" color: #444;" alt="Eliminar UUID Relacionado" title="Eliminar UUID Relacionado"></span></a>';
									print '</td>';	
								}
							}

							print "</tr>";
						}
					print "</table>";
				}else{
					print '<tr>';
						print "<td  style='font-weight: bold !important; color: rgb(100,60,20) !important;'>";
							print '<center><strong>Ningún UUID Relacionado.<strong></center>';
						print '</td>';
					print '</tr>';
				}

            print '</table>';
        print '</div>';
    print '</div><br><br>';

	### CFDIS Relacionados
	if($valida_fac_tim == 0){
		print '<div class="fichecenter"><br>';
			print "<form method='POST' action='complementos_cfdimx.php?facid=".$facid."&action=add_uuid&mod=cfdi_rel'>";
				print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
				print '<table class="noborder" width="100%" style="border-bottom: none !important;">';					
					print '<thead>';
						print '<tr class="liste_titre">';
							print '<td>';
								print '<b>Relacionar UUID</b>';
							print '</td>';
							print '<td>&nbsp;</td>';
						print '</tr>';
					print '</thead>';

					print '<thead>';
						print '<tr>';
							print "<td>";
								print '<strong>Facturas Internas</strong><br>';
								print obtener_cfdi_relacionados('',"sel_uuid_relacionado",0,0);
								print '<br><br>';
								print '<strong>Facturas Externas</strong><br>';
								print "<input type='text' size='50' name='uuid_relacionado' title='Ingresa el UUID a relacionar, ejemplo: c6b2ff5f-3z1a-4ff9-bcb6-2345ca5eabee'>";
							print "</td>";
							print "<td align='center'>";
								print "<input type='submit' value='Agregar' class='butAction'>";
							print "</td>";
						print "</tr>";
					print '</thead>';
				print "</table>";
			print "</form>";
		print '</div>';
	}else{
		print '<div class="fichecenter"><br>';
			print '&nbsp;';
		print '</div>';
	}

?>