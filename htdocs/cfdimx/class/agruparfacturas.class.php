<?php
	//=====================================================================+
	// File name   : configuracion.class.php
	// Begin       : 2022-04-04
	// Last Update : 2022-04-04
	//
	// Description : Clase de Complementos de Factrua
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//=====================================================================+

	// require_once('lib/nusoap/lib/nusoap.php');
	require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
	// require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	class AgruparFactura{
		var $db;

		public $doli_version;
		public $facid_origen;
		public $list_fac_sel       = array();
		public $list_fac_sel_status = array();

		public function __construct($db){
			global $conf;

		    $this->db = $db;
		    $this->doli_version = DOL_VERSION;
		}

		public function getTipoAgrupacion($facid){
			global $db;

			// 1.- Agrupación de Facturas
			// 2.- Factura Global
			$tipo = 1;

			$sql     = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_type_document WHERE fk_facture=" . $facid;
			$res_sql = $db->query($sql);

			if($res_sql){
				$obj_tipoComprobante = $db->fetch_object($res_sql);

				if($obj_tipoComprobante->tipo_document == 8){
					$tipo = 2;
				}
			}

			return $tipo;
		}

        public function agruparFacturas($tipo){
			global $db, $conf;

			$error = 0;

			if(is_null($this->facid_origen) && $this->facid_origen <= 0){
				$error++;
			}

			if(count($this->list_fac_sel) == 0){
				$error++;
			}

			if(count($this->list_fac_sel_status) == 0){
				$error++;
			}

			if($error == 0){
				$factura = new Facture($db);
				$factura->fetch($this->facid_origen);

				for($i = 0; $i < count($this->list_fac_sel); $i++){
					if($this->list_fac_sel_status[$i] == 1){
						$factura_sel = new Facture($db);
						$factura_sel->fetch($this->list_fac_sel[$i]);

						##Se guarda la relación de la Factura
						$sql_rel = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_agrupador_facturas";
						$sql_rel .= " (entity, facid_origen, facid_relacionada, estatus, tipo)";
						$sql_rel .= " VALUES";
						$sql_rel .= " (";
							$sql_rel .= "'".$conf->entity."',";
							$sql_rel .= "'".$this->facid_origen."',";
							$sql_rel .= "'".$this->list_fac_sel[$i]."',";
							$sql_rel .= "'0',";
							$sql_rel .= "'".$tipo."'";
						$sql_rel .= " )";
						$res_rel = $db->query($sql_rel);

						if(!$res_rel){
							dol_print_error($db);
							// die;
						}						

						##Agrupador de lineas de partidas de otras Facturas
						if($tipo == 1){
							foreach($factura_sel->lines AS $line){
								$array_options = array(
									"options_claveprodserv"			  => $line->array_options["options_claveprodserv"],
									"options_umed"         			  => $line->array_options["options_umed"],
									"options_noidenticfdi"			  => $line->array_options["options_noidenticfdi"],
									"options_aplicar_ret_individual"  => $line->array_options["options_aplicar_ret_individual"],
									"options_exentoiva"				  => $line->array_options["options_exentoiva"],
									"options_objimp"				  => $line->array_options["options_objimp"]
								);

								$factura->addline(
									$line->desc,
									$line->subprice,
									$line->qty,
									$line->tva_tx,
									$line->localtax1_tx,
									$line->localtax2_tx,
									$line->fk_product,
									$line->remise_percent,
									$line->date_start,
									$line->date_end,
									0,
									$line->info_bits,
									$line->fk_remise_except,
									'HT',
									0,
									$line->product_type,
									$line->rang,
									$line->special_code,
									$factura_sel->origin,
									$line->rowid,
									$line->fk_parent_line,
									$line->fk_fournprice,
									$line->pa_ht,
									$line->label,
									$array_options,
									$line->situation_percent,
									$line->fk_prev_id,
									$line->fk_unit
								);
							}
						}elseif($tipo == 2){
							##Agrupador de Facturas para Facturación Global
							// print '<pre>factura_sel<br>';
							// 	print_r($factura_sel);
							// print '</pre>';

							$objimp = ($factura_sel->total_tva > 0 ? "02" : "01");

							$array_options = array(
								"options_claveprodserv"			  => "01010101",
								"options_umed"         			  => "ACT",
								"options_noidenticfdi"			  => $factura_sel->ref,
								"options_aplicar_ret_individual"  => null,
								"options_exentoiva"				  => null,
								"options_objimp"				  => $objimp
							);

							$factura->addline(
								"Venta",
								$factura_sel->total_ht,
								1,
								$factura_sel->total_tva,
								null,
								null,
								null,
								null,
								null,
								null,
								0,
								null,
								null,
								'HT',
								0,
								0,
								null,
								null,
								$factura_sel->origin,
								null,
								null,
								null,
								null,
								null,
								$array_options,
								null,
								null,
								null
							);
						}

						/*$factura->addline(
							$desc,
							$lines[$i]->subprice,
							$lines[$i]->qty,
							$tva_tx,
							$localtax1_tx,
							$localtax2_tx,
							$lines[$i]->fk_product,
							$lines[$i]->remise_percent,
							$date_start,
							$date_end,
							0,
							$lines[$i]->info_bits,
							$lines[$i]->fk_remise_except,
							'HT',
							0,
							$product_type,
							$lines[$i]->rang,
							$lines[$i]->special_code,
							$object->origin,
							$lines[$i]->rowid,
							$fk_parent_line,
							$lines[$i]->fk_fournprice,
							$lines[$i]->pa_ht,
							$label,
							$array_options,
							$lines[$i]->situation_percent,
							$lines[$i]->fk_prev_id,
							$lines[$i]->fk_unit
						);*/
					}
				}
			}

			return $error;
		}

		public function listaFacturas($facid, $tipo){
			global $db, $conf;

			$facturas = null;

			$sql_list_agr = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_agrupador_facturas";
			$sql_list_agr .= " WHERE";
				$sql_list_agr .= " entity = ".$conf->entity;
				$sql_list_agr .= " AND facid_origen = ".$facid;
				$sql_list_agr .= " AND tipo = ".$tipo;
			// print $sql_list_agr;
			$res_list_agr = $db->query($sql_list_agr);
			$num_list_agr = $db->num_rows($res_list_agr);

			if($num_list_agr > 0){
				while($obj_fac = $db->fetch_object($res_list_agr)){

					$fac_tmp = new Facture($db);
					$fac_tmp->fetch($obj_fac->facid_relacionada);

					##Consulta con clave nativa de dolibarr
					$texto_f_pago = "";

					$sql_f_pago  = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_formapago";
					$sql_f_pago .= " WHERE cod_doli = '".$fac_tmp->mode_reglement_code."'";
					$sql_f_pago .= " LIMIT 1";
					$res_f_pago  = $db->query($sql_f_pago);
					$num_f_pago  = $db->num_rows($res_f_pago);

					if($num_f_pago > 0){
						$obj_pago     = $db->fetch_object($res_f_pago);
						$texto_f_pago = $obj_pago->label;
					}else{
						##Consulta con claves SAT
						$sql_f_pago_sat  = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_formapago";
						$sql_f_pago_sat .= " WHERE code = '".$fac_tmp->mode_reglement_code."'";
						$sql_f_pago_sat .= " LIMIT 1";
						$res_f_pago_sat  = $db->query($sql_f_pago_sat);
						$num_f_pago_sat  = $db->num_rows($res_f_pago_sat);

						if($num_f_pago_sat > 0){
							$obj_pago_sat  = $db->fetch_object($res_f_pago_sat);
							$texto_f_pago  = $obj_pago_sat->label;
						}
					}


					$facturas[] = array(
									'ref' 				=> $fac_tmp->getNomUrl(1),
									'fecha'				=> dol_print_date($fac_tmp->date, 'day'),
									'met_pago'			=> $texto_f_pago,
									'total'		     	=> $fac_tmp->total_ttc,
									'facid_relacionada' => $obj_fac->facid_relacionada,
									'estatus'			=> $obj_fac->estatus
								);
				}
			}

			return $facturas;
		}

		public function getInformacionGlobal($facid){
			global $db, $conf;

			$info_global = array();

			$sql_info_global  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_factura_global";
			$sql_info_global .= " WHERE";
			$sql_info_global .= " entity = ".$conf->entity;
			$sql_info_global .= " AND facid = ".$facid;
			$res_info_global  = $db->query($sql_info_global);
			$num_info_global  = $db->num_rows($res_info_global);

			if($num_info_global > 0){
				$obj_info_global = $db->fetch_object($res_info_global);

				$info_global = array(
									"periodicidad" => $obj_info_global->periodicidad,
									"meses"        => $obj_info_global->meses,
									"anio" 		   => $obj_info_global->anio,
									"estatus"      => $obj_info_global->estatus,
									"numfactrel"   => $obj_info_global->numfactrel
								);
			}

			return $info_global;
		}

		public function guardarInformacionGlobal($facid, $periodicidad, $meses, $anio){
			global $db, $conf;

			$errores = 0;

			$sql_tipo_operacion  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_factura_global";
			$sql_tipo_operacion .= " WHERE";
			$sql_tipo_operacion .= " entity = ".$conf->entity;
			$sql_tipo_operacion .= " AND facid = ".$facid;
			$res_tipo_operacion  = $db->query($sql_tipo_operacion);
			$num_tipo_operacion  = $db->num_rows($res_tipo_operacion);

			if($num_tipo_operacion > 0){
				$obj_info = $db->fetch_object($res_tipo_operacion);

				$update_info_global  = "UPDATE ".MAIN_DB_PREFIX."cfdimx_factura_global";
				$update_info_global .= " SET";
					$update_info_global .= " anio = '".$anio."',";
					$update_info_global .= " meses = '".$meses."',";
					$update_info_global .= " periodicidad = '".$periodicidad."'";
				$update_info_global .= " WHERE";
				$update_info_global .= " rowid = ".$obj_info->rowid;
				
				$res_update_info_global = $db->query($update_info_global);

				if($res_update_info_global){
					return 1;
				}else{
					$errores++;
				}
			}else{
				$insert_info_global  = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_factura_global";
				$insert_info_global .= " (entity, facid, anio, meses, periodicidad, estatus, numfactrel)";
				$insert_info_global .= " VALUES";
				$insert_info_global .= " (";
					$insert_info_global .= "'".$conf->entity."',";
					$insert_info_global .= "'".$facid."',";
					$insert_info_global .= "'".$anio."',";
					$insert_info_global .= "'".$meses."',";
					$insert_info_global .= "'".$periodicidad."',";
					$insert_info_global .= "'0',";
					$insert_info_global .= "'0'";
				$insert_info_global .= " )";

				print $insert_info_global;

				$res_insert_info_global = $db->query($insert_info_global);

				if($res_insert_info_global){
					return 1;
				}else{
					$errores++;
				}
			}

			return $errores;
		}

		public function obtener_cat_info_globaL($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion = 0, $tipo_sql = '', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

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
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_periodicidad ORDER BY code ASC";
					$valor_placeholder = "Periodicidad";
					break;
				case 2:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_meses ORDER BY code ASC";
					$valor_placeholder = "Meses";
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

						$valor    = $rw->code;
						$etiqueta = $rw->code." - ".$rw->label;

						if ($selected == $valor) {
							$out.= '<option value="'.$valor.'" selected>';
							$etiqueta_list = $rw->code." - ".$rw->label;
						}else{
							$out.= '<option value="'.$valor.'">';
						}

						$out.= $etiqueta."</option>";
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
    }
?>