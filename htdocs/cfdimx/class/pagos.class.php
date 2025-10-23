<?php
	//============================================================+
	// File name   : pagos.class.php
	// Begin       : 2022-04-01
	// Last Update : 2022-04-01
	//
	// Description : Pagos 2.0
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//============================================================+

	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';

	class ComplementoPagos{
		var $db;

		public $doli_version;

		public $datos_emisor = array();
		public $conf_ws      = array();
		public $ret_locales  = array();
		public $formas_pago  = array();
		public $ajustes_opc  = array();
		public $carga_masiva = array();

		public function __construct($db){
			global $conf;

		    $this->db = $db;
		    $this->doli_version = DOL_VERSION;
		}

		public function obtener_catalogo($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion = 0, $tipo_sql = '', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

		    global $conf,$user,$langs;
		    $db = $this->db;

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
		    $valor_placeholder = "";

		    switch ($tipo_catalogo) {
		    	case 1:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_uso_cfdi WHERE active = 1";
		    		$valor_placeholder = "Uso CFDI";
		    		break;
				case 2:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_formapago WHERE active = 1";
					$valor_placeholder = "Forma de Pago";
					break;
				case 3:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity = ".$conf->entity;
					$valor_placeholder = "Moneda del Pago";
					break;
				case 4:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_currencies WHERE code_iso='".$conf->currency."'";
					$valor_placeholder = "Moneda del Pago";
					break;
				case 5:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_formapago WHERE active = 1";
					$valor_placeholder = "Forma de Pago";
					break;
				case 6:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_exportacion WHERE active = 1";
					$valor_placeholder = "Exportación";
					break;
				case 7:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."paiement";
					if($tipo_sql != ""){
						$sql .= " WHERE rowid = ".$tipo_sql;
					}
					$valor_placeholder = "Monto del Pago";
					break;
				case 8:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_rel";
					$valor_placeholder = "Tipo de Relación";
					break;

		    	default:
		    		$sql = "";
		    		break;
		    }

		    // if($tipo_informacion == 1)
				// $sql .= " AND code='".$selected."'";

			$resql = "";
		   	$resql = $db->query($sql);
		   	// print $sql;

		    if ($resql)
		    {
		        $num = $db->num_rows($resql);
		        $i = 0;
		        $etiqueta = "";
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
		            $out.= '<option value="-1">Seleccione '.$valor_placeholder.'</option>';
		            $i=0;
		            while ($rw = $db->fetch_object($resql)) {

						switch ($tipo_catalogo) {
							case 3:
									$valor    = $rw->code;
									$etiqueta = $rw->code." - ".$rw->name;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code." - ".$rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;
							case 4:
									$valor    = $rw->code_iso;
									$etiqueta = $rw->code_iso." - ".$rw->label;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code_iso." - ".$rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;

							case 5:
									$valor    = $rw->cod_doli;
									$valor2   = $rw->code;
									$etiqueta = $rw->code." - ".$rw->label;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code." - ".$rw->label;
									}else{
										if ($selected == $valor2) {
											$out.= '<option value="'.$valor.'" selected>';
											$etiqueta_list = $rw->code." - ".$rw->label;
										}else{
											$out.= '<option value="'.$valor.'">';
										}
									}

									$out.= $etiqueta."</option>";
								break;

							case 6:
									$valor    = $rw->code;
									$etiqueta = $rw->label;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;

							case 7:
									// print '<pre>'; print_r($rw); print '</pre>';
									$valor    = str_replace(",","",number_format($rw->amount, 2));
									$etiqueta = str_replace(",","",number_format($rw->amount, 2));

									$valor2    = str_replace(",","",number_format($rw->multicurrency_amount, 2));
									$etiqueta2 = str_replace(",","",number_format($rw->multicurrency_amount, 2));

									$ban_valor1 = 0;
									$ban_valor2 = 0;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$out.= $etiqueta."</option>";

										$etiqueta_list = $rw->amount;
										$ban_valor1 = 1;
									}else{
										if ($selected == $valor2) {
											$out.= '<option value="'.$valor2.'" selected>';
											$out.= $etiqueta2."</option>";

											$etiqueta_list = $rw->multicurrency_amount;
											$ban_valor2 = 1;
										}
									}

									if($ban_valor1 == 0){
										$out.= '<option value="'.$valor.'">';
										$out.= $etiqueta."</option>";
									}

									if($ban_valor2 == 0){
										$out.= '<option value="'.$valor2.'">';
										$out.= $etiqueta2."</option>";
									}

									// $out.= $etiqueta."</option>";
								break;

							default:
									$valor         = $rw->code;
									$etiqueta      = $rw->code." - ".$rw->label;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code." - ".$rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;
						}
		            }
		        }else{
		            $nodatarole='';
		            if ($conf->use_javascript_ajax)
		            {
		                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		                $comboenhancement = ajax_combobox($htmlname);
		                $out.=$comboenhancement;
		                $nodatarole=($comboenhancement?' data-role="none"':'');
		            }

		            $out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
		        }
		        $out.= '</select>';

		        if($tipo_informacion == 1)
		        	$out = $etiqueta_list;
		    }else{
		        dol_print_error($db);
		    }

		    return $out;
		}

		##Funcion Exclusiva de la Ficha donde se llena el Pago
		public function obtener_moneda($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion = 0, $tipo_sql = '', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

		    global $conf,$user,$langs;
		    $db = $this->db;

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
		    $valor_placeholder = "";

			$sql_val = "SELECT * FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity = ".$conf->entity;
			$res_val = $db->query($sql_val);
			$num_val = $db->num_rows($res_val);

			if($num_val > 0){
				$tipo_catalogo = 2;
			}

		    switch ($tipo_catalogo) {
				case 1:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_currencies";
					$valor_placeholder = "Moneda del Pago";
					break;
				case 2:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity = ".$conf->entity;
					$valor_placeholder = "Moneda del Pago";
					break;

		    	default:
		    		$sql = "";
		    		break;
		    }

		    // if($tipo_informacion == 1)
				// $sql .= " AND code='".$selected."'";

			$resql = "";
		   	$resql = $db->query($sql);
		   	// print $sql;

		    if ($resql)
		    {
		        $num = $db->num_rows($resql);
		        $i = 0;
		        $etiqueta = "";
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

            		$out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.' form="payment_form">';

		            if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
		            if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
		            $out.= '<option value="-1">Seleccione '.$valor_placeholder.'</option>';
		            $i=0;
		            while ($rw = $db->fetch_object($resql)) {

						switch ($tipo_catalogo) {

							case 1:
									$valor    = $rw->code_iso;
									$etiqueta = $rw->code_iso." - ".$rw->label;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code_iso." - ".$rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;

							case 2:
									$valor    = $rw->code;
									$etiqueta = $rw->code." - ".$rw->name;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code." - ".$rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;
						}
		            }
		        }else{
		            $nodatarole='';
		            if ($conf->use_javascript_ajax)
		            {
		                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		                $comboenhancement = ajax_combobox($htmlname);
		                $out.=$comboenhancement;
		                $nodatarole=($comboenhancement?' data-role="none"':'');
		            }

		            $out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
		        }
		        $out.= '</select>';

		        if($tipo_informacion == 1)
		        	$out = $etiqueta_list;
		    }else{
		        dol_print_error($db);
		    }

		    return $out;
		}

		##Funcion Exclusiva para CFDI Relacionados Pagos
		public function obtener_rel_pagos($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion = 0, $tipo_sql = '', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

		    global $conf,$user,$langs;
		    $db = $this->db;

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
		    $valor_placeholder = "";

		    switch ($tipo_catalogo) {
				case 1:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_tipo_rel";
					$valor_placeholder = "Tipo de Relación";
					break;

				case 2:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos";
					$sql .= " WHERE fk_facture = 0";
					$sql .= " AND fk_paiement NOT IN(".$tipo_sql.")";
					$sql .= " AND uuid IS NOT NULL";
					$valor_placeholder = "Complemento de Pagos";
					break;

		    	default:
		    		$sql = "";
		    		break;
		    }

		    // if($tipo_informacion == 1)
				// $sql .= " AND code='".$selected."'";

			$resql = "";
		   	$resql = $db->query($sql);
		   	// print $sql;

		    if ($resql)
		    {
		        $num = $db->num_rows($resql);
		        $i = 0;
		        $etiqueta = "";
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

					// if($tipo_catalogo == 2){
					// 	$out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.' onchange="seleccionPago()">';
					// }else{

					// }

					$out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';

		            if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
		            if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
		            $out.= '<option value="-1">Seleccione '.$valor_placeholder.'</option>';
		            $i=0;
		            while ($rw = $db->fetch_object($resql)) {

						switch ($tipo_catalogo) {
							case 1:
									$valor         = $rw->code;
									$etiqueta      = $rw->code." - ".$rw->label;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $rw->code." - ".$rw->label;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;

							case 2:
									$pago_tmp = new Paiement($db);
									$pago_tmp->fetch($rw->fk_paiement);

									$valor         = $rw->fk_paiement."<>".$rw->uuid;
									$etiqueta      = "Ref: ".$pago_tmp->ref."  Monto: ".$rw->monto."  UUID: ".$rw->uuid;

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
										$etiqueta_list = $pago_tmp->ref." - ".$rw->monto;
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								break;

						}
		            }
		        }else{
		            $nodatarole='';
		            if ($conf->use_javascript_ajax)
		            {
		                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		                $comboenhancement = ajax_combobox($htmlname);
		                $out.=$comboenhancement;
		                $nodatarole=($comboenhancement?' data-role="none"':'');
		            }

		            $out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
		        }
		        $out.= '</select>';

		        if($tipo_informacion == 1)
		        	$out = $etiqueta_list;
		    }else{
		        dol_print_error($db);
		    }

		    return $out;
		}

		public function mostrarDescuentos(){
			global $conf;

			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_descuentos";
			$sql = " WHERE entity_id=".$conf->entity;

			$res_sql     = $this->db->query($sql);
			$num_res_sql = $this->db->num_rows($res_sql);

			$mostrar_descuento=1;
			if($num_res_sql > 0){
				$obj_descuento = $this->db->fetch_object($res_sql);

				if($obj_descuento->mostrar == 1){
					$mostrar_descuento = 1;
				}else{
					$mostrar_descuento = 2;
				}
			}

			return $mostrar_descuento;
		}

		//Funcion para obtener las retenciones del Producto
		function obtenerRetencionesProducto($id, $id_detalle, $impuesto, $vowels){
			$retenBase = null;
			$retenImporte = null;
			$retenTasa = null;
			$retenTipoFactor = null;
			$retencImpuesto = null;

			$sql_ret = "
					SELECT
						base,impuesto,tipo_factor,tasa,importe
					FROM
						".MAIN_DB_PREFIX."cfdimx_retencionesdet
					WHERE
						factura_id=".$id." AND fk_facturedet=".$id_detalle." AND impuesto='".$impuesto."'";

			$res_sql_ret = $this->db->query($sql_ret);
			$num_sql_ret = $this->db->num_rows($res_sql_ret);

			if($num_sql_ret > 0){
				$obj_ret         = $this->db->fetch_object($res_sql_ret);
				$retenBase       = str_replace($vowels, "",number_format($obj_ret->base,2));
				$retenImporte    = str_replace($vowels, "",number_format($obj_ret->importe,2));
				$retenTasa       = $obj_ret->tasa;
				$retenTipoFactor = $obj_ret->tipo_factor;
				$retencImpuesto  = $obj_ret->impuesto;
			}

			$retenciones = array(
							"retenBase" => $retenBase,
							"retenImporte" => $retenImporte,
							"retenTasa" => $retenTasa,
							"retenTipoFactor" => $retenTipoFactor,
							"retencImpuesto" => $retencImpuesto
						);

			return $retenciones;
		}

		public function getImpuestosDoctoRel($ref_factura, $uuid_factura){
			global $conf;

			$factura = new Facture($this->db);
			$factura->fetch('',$ref_factura);

			if($factura->type == 2){
				$vowels = array(",", "-");
			}else{
				$vowels = array(",");
			}

			$producto = new Product($this->db);

			//print '<pre>'; print_r($factura->lines); print '</pre>';
			$descheader = 0;
			$cfdi_decimal = isset($conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL) ? $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL : 2;
			$mostrar_descuentos = $this->mostrarDescuentos();
			$version_cfdi_sat = $conf->global->CFDIMX_VERSION_SAT;
			$conceptos = array();

			for($i=0;$i<sizeof($factura->lines);$i++) {
				$impuesto = '002';

				if ($factura->lines[$i]->fk_product != "") {
					$producto->fetch($factura->lines[$i]->fk_product);

					$tipoFactor       = $producto->array_options["options_exentoiva"];
		    		$objimp           = $producto->array_options["options_objimp"];

					if($factura->lines[$i]->array_options["options_exentoiva"] != ""){
						$tipoFactor = $factura->lines[$i]->array_options["options_exentoiva"];
					}

					if($factura->lines[$i]->array_options["options_objimp"] != "" && $factura->lines[$i]->array_options["options_objimp"] != 0){
						$objimp = $factura->lines[$i]->array_options["options_objimp"];
					}
				}else{
					$tipoFactor       = $factura->lines[$i]->array_options["options_exentoiva"];
					$objimp           = $factura->lines[$i]->array_options["options_objimp"];
				}

				$label_tipoFactor = ($tipoFactor == 1 ? 'Exento' : 'Tasa');

				if($conf->global->MAIN_MODULE_MULTICURRENCY == 1 && $factura->multicurrency_code != "MXN") {
					$factura_subtotal_detalle = $factura->lines[$i]->multicurrency_total_ht;
					$importeImpuesto  = $factura->lines[$i]->multicurrency_total_tva;

					if($factura->lines[$i]->remise_percent != 0  && $mostrar_descuentos == 1){
						$descuento  = $factura->lines[$i]->remise_percent/100;
						$descuento2 = ($factura->lines[$i]->multicurrency_subprice*$factura->lines[$i]->qty)*$descuento;
						$descuento2 = str_replace(array("-",","), "",number_format($descuento2,2));
						$descprodl  = str_replace(array("-",","), "",number_format($descuento2,2));
						$descheader = $descheader+$descuento2;
						$total_vuni = number_format($factura->lines[$i]->multicurrency_subprice,$cfdi_decimal);
						$total_ttc  = number_format($factura->lines[$i]->multicurrency_subprice*$factura->lines[$i]->qty,$cfdi_decimal);
					}else{
						if($factura->lines[$i]->remise_percent!=0  && $mostrar_descuentos==2){
							$descuento  = $factura->lines[$i]->multicurrency_total_ht/$factura->lines[$i]->qty;
							$total_vuni = number_format($descuento,$cfdi_decimal);
							$total_ttc  = number_format($factura->lines[$i]->multicurrency_total_ht,$cfdi_decimal);
						}else{
							$total_vuni = number_format($factura->lines[$i]->multicurrency_subprice,$cfdi_decimal);
							$total_ttc  = number_format($factura->lines[$i]->multicurrency_total_ht,$cfdi_decimal);
						}
					}
				}else{
					$factura_subtotal_detalle = $factura->lines[$i]->total_ht;
					$importeImpuesto  = $factura->lines[$i]->total_tva;

					if($factura->lines[$i]->remise_percent != 0  && $mostrar_descuentos == 1){
						$descuento  = $factura->lines[$i]->remise_percent/100;
						$descuento2 = ($factura->lines[$i]->subprice*$factura->lines[$i]->qty)*$descuento;
						$descuento2 = str_replace(array("-",","), "",number_format($descuento2,2));
						$descprodl  = str_replace(array("-",","), "",number_format($descuento2,2));
						$descheader = $descheader+$descuento2;
						$total_vuni = number_format($factura->lines[$i]->subprice,$cfdi_decimal);
						$total_ttc  = number_format($factura->lines[$i]->subprice*$factura->lines[$i]->qty,$cfdi_decimal);
					}else{
						if($factura->lines[$i]->remise_percent!=0  && $mostrar_descuentos==2){
							$descuento  = $factura->lines[$i]->total_ht/$factura->lines[$i]->qty;
							$total_vuni = number_format($descuento,$cfdi_decimal);
							$total_ttc  = number_format($factura->lines[$i]->total_ht,$cfdi_decimal);
						}else{
							$total_vuni = number_format($factura->lines[$i]->subprice,$cfdi_decimal);
							$total_ttc  = number_format($factura->lines[$i]->total_ht,$cfdi_decimal);
						}
					}
				}

				///Inicia Ajuste Nota Credito
				if($total_vuni == 0)
					$total_vuni = number_format($factura->lines[$i]->subprice, $cfdi_decimal);

				if($total_ttc == 0)
					$total_ttc = number_format($factura->lines[$i]->total_ht, $cfdi_decimal);

				if($factura->lines[$i]->total_tva == 0)
					$factura->lines[$i]->total_tva = $factura->lines[$i]->total_tva;

				if($factura->lines[$i]->total_ht == 0)
					$factura->lines[$i]->total_ht = $factura->lines[$i]->total_ht;
				///Termina Ajuste Nota Credito

				$retencion_iva = $this->obtenerRetencionesProducto($factura->id, $factura->lines[$i]->id, '002', $vowels);
				$retencion_isr = $this->obtenerRetencionesProducto($factura->id, $factura->lines[$i]->id, '001', $vowels);

				if(strcmp($version_cfdi_sat, "4.0") == 0){
					$conceptos[] = array(
						// "descripcion" => $descripcion,
						// "descripcion" => $descripcion_xml,
						// 'cantidad' =>str_replace($vowels, "",number_format($cantidad,2)),
						// 'valorUnitario'=>str_replace($vowels, "", $total_vuni),
						// 'importe'=>str_replace($vowels, "", $total_ttc),
						// 'importeImpuesto'=>str_replace($vowels, "",round(($factura->lines[$i]->total_tva),6)),
						'importeImpuesto'=>str_replace($vowels, "",round(($importeImpuesto),6)),
						'impuesto'=>$impuesto,
						'tasa'=>number_format(($factura->lines[$i]->tva_tx/100),6),
						// "unidad" => $unidad,
						// 'noIdentificacion' => $noIdentificacion,
						// 'tipoFactor'=>"Tasa",
						'tipoFactor' => $label_tipoFactor,
						// 'claveProdServ' => $claveprodserv,
						// 'base'=>str_replace($vowels, "", number_format($factura->lines[$i]->total_ht,2)),
						'base'=>str_replace($vowels, "", number_format($factura_subtotal_detalle,2)),
						'retenBase'=>$retencion_iva["retenBase"],
						'retenImporte'=>$retencion_iva["retenImporte"],
						'retenTasa'=>number_format($retencion_iva["retenTasa"],6),
						'retenTipoFactor'=>$retencion_iva["retenTipoFactor"],
						'retencImpuesto'=>$retencion_iva["retencImpuesto"],
						'retenBaseISR'=>$retencion_isr["retenBase"],
						'retenImporteISR'=>$retencion_isr["retenImporte"],
						'retenTasaISR'=>number_format($retencion_isr["retenTasa"],6),
						'retenTipoFactorISR'=>$retencion_isr["retenTipoFactor"],
						'retencImpuestoISR'=>$retencion_isr["retencImpuesto"],
						// 'descuento'=>$descprodl,
						// 'cuentaPredial'=>$cuentapredial,
						'objetoImp'=>$objimp
					);
				}

			}

			#Inicia verifiación de desglose de Impuestos
			$impuestos = array();

			if($conceptos != null){
				foreach($conceptos AS $valores){
					// print '<pre>'; print_r($valores); print '</pre>';

					##Retencion IVA
					$retencion_iva =  array(
						"base" => $valores["retenBase"],
						"impuesto" => $valores["retencImpuesto"],
						"tipoFactor" => $valores["retenTipoFactor"],
						"tasaOCuota" => $valores["retenTasa"],
						"importe" => $valores["retenImporte"],
						"tipo" => 1
					);

					if($valores["retencImpuesto"] != null && $valores["objetoImp"] == "02"){
						array_push($impuestos, $retencion_iva);
					}

					##Retencion ISR
					$retencion_isr =  array(
						"base" => $valores["retenBaseISR"],
						"impuesto" => $valores["retencImpuestoISR"],
						"tipoFactor" => $valores["retenTipoFactorISR"],
						"tasaOCuota" => $valores["retenTasaISR"],
						"importe" => $valores["retenImporteISR"],
						"tipo" => 1
					);

					if($valores["retencImpuestoISR"] != null && $valores["objetoImp"] == "02"){
						array_push($impuestos, $retencion_isr);
					}

					##Traslado
					$traslado =  array(
						"base" => $valores["base"],
						"impuesto" => $valores["impuesto"],
						"tipoFactor" => $valores["tipoFactor"],
						"tasaOCuota" => $valores["tasa"],
						"importe" => $valores["importeImpuesto"],
						"tipo" => 2
					);

					if($valores["importeImpuesto"] != null && $valores["objetoImp"] == "02"){
						array_push($impuestos, $traslado);
					}
				}
			}
			#Termina verifiación de desglose de Impuestos

			#Inicia Agrupacion de Impuestos
			if($impuestos != null){
				$nuevos_impuestos_tras_ret = array();

				foreach($impuestos  AS $impuesto){
					$impuesto_tras_ret = array(
												"base"       => $impuesto["base"],
												"impuesto"   => $impuesto["impuesto"],
												"tipoFactor" => $impuesto["tipoFactor"],
												"tasaOCuota" => $impuesto["tasaOCuota"],
												"importe"    => $impuesto["importe"],
												"tipo"       => $impuesto["tipo"]
										);

					if(count($nuevos_impuestos_tras_ret) == 0){
						array_push($nuevos_impuestos_tras_ret, $impuesto_tras_ret);
					}else{
						$validaciones = $this->existeTasaOCuota($nuevos_impuestos_tras_ret, $impuesto_tras_ret);

						if($validaciones["encontrado"] == 0){
							array_push($nuevos_impuestos_tras_ret, $impuesto_tras_ret);
						}else{
							if($validaciones["posicion"] >= 0){
								$nuevos_impuestos_tras_ret[$validaciones["posicion"]]["base"]    += $impuesto["base"];
								$nuevos_impuestos_tras_ret[$validaciones["posicion"]]["importe"] += $impuesto["importe"];
							}
						}
					}
				}

				if(!is_null($nuevos_impuestos_tras_ret) && count($nuevos_impuestos_tras_ret) > 0){
					unset($impuestos);
					$impuestos = array();

					foreach ($nuevos_impuestos_tras_ret AS $nuevo_tras_ret) {
						$info_impuesto =  array(
							"base"       => $nuevo_tras_ret["base"],
							"impuesto"   => $nuevo_tras_ret["impuesto"],
							"tipoFactor" => $nuevo_tras_ret["tipoFactor"],
							"tasaOCuota" => $nuevo_tras_ret["tasaOCuota"],
							"importe"    => number_format($nuevo_tras_ret["base"] * $nuevo_tras_ret["tasaOCuota"], 2, ".", ""),
							"tipo"       => $nuevo_tras_ret["tipo"]
						);

						array_push($impuestos, $info_impuesto);
					}
				}
			}
			#Termina Agrupacion de Impuestos

			return $impuestos;
		}

		public function existeTasaOCuota($lista_impuetos, $impuesto){
			$encontrado = 0;
			$posicion   = 0;

			foreach ($lista_impuetos as $lista_impuesto) {

				if(
					strcmp($lista_impuesto["impuesto"], $impuesto["impuesto"]) == 0    &&
					strcmp($lista_impuesto["tipoFactor"], $impuesto["tipoFactor"]) == 0 &&
					strcmp($lista_impuesto["tasaOCuota"], $impuesto["tasaOCuota"]) == 0 &&
					$lista_impuesto["tipo"] == $impuesto["tipo"]
				){
					$encontrado = 1;
					break;
				}

				$posicion++;
			}

			$validaciones = array(
							"encontrado" => $encontrado,
							"posicion" => $posicion,
						);

			return $validaciones;
		}

		public function getDatosXML($ref_pago, $uuid){
			global $conf;

			$ruta_xml = $conf->facture->dir_output."/".$ref_pago."/Pago_".$uuid.".xml";

			$xml = simplexml_load_string(file_get_contents($ruta_xml));
			$ns = $xml->getNamespaces(true);
			$xml->registerXPathNamespace('c', $ns['cfdi']);
			$xml->registerXPathNamespace('t', $ns['tfd']);

			$emisor_rfc   = "";
			$receptor_rfc = "";

			foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor){
				$emisor_rfc   = (String)$Emisor["Rfc"];
			}

			foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $Receptor){
				$receptor_rfc   = (String)$Receptor["Rfc"];
			}

			$informacion_xml = array();

			if($emisor_rfc != "" && $receptor_rfc != ""){
				$informacion_xml = array(
							"emisor_rfc" => $emisor_rfc,
							"receptor_rfc" => $receptor_rfc
						);
			}

			return $informacion_xml;
		}
	}
?>