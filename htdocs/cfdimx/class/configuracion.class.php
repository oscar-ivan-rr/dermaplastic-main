<?php
	//============================================================+
	// File name   : facturacfdimx.class.php
	// Begin       : 2021-11-18
	// Last Update : 2021-11-18
	//
	// Description : Generador de Timbrado CFDI 3.3
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//============================================================+

	// require_once('lib/nusoap/lib/nusoap.php');
	// require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	class ConfiguracionCFDI{
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

		function cfdimx_admin_prepare_head(){
			$h = 0;
			$head = array();

			$head[$h][0] = DOL_URL_ROOT."/cfdimx/admin/cfdimx.php?mod=dataEmisor";
			$head[$h][1] = "<strong>Datos del Emisor</strong>";
			$head[$h][2] = "uno";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=config';
			$head[$h][1] = "<strong>Configuración Web Services</strong>";
			$head[$h][2] = "dos";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=emisores';
			$head[$h][1] = "<strong>Emisores</strong>";
			$head[$h][2] = "doscientos";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=retenciones';
			$head[$h][1] = "<strong>Retenciones Locales</strong>";
			$head[$h][2] = "cinco";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=formaspago';
			$head[$h][1] = "<strong>Formas de Pago</strong>";
			$head[$h][2] = "seis";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=configopcional';
			$head[$h][1] = "<strong>Ajustes Opcionales</strong>";
			$head[$h][2] = "siete";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=cargamasivaclaves';
			$head[$h][1] = "<strong>Carga Masiva</strong>";
			$head[$h][2] = "ocho";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=updatedatos_doli';
			$head[$h][1] = "<strong>Actualizar Información Dolibarr</strong>";
			$head[$h][2] = "nueve";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=recursos_sat';
			$head[$h][1] = "<strong>Recursos SAT</strong>";
			$head[$h][2] = "diez";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=emisores';
			$head[$h][1] = "<strong>Emisores</strong>";
			$head[$h][2] = "once";
			$h++;

			$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=changelog';
			$head[$h][1] = "<strong>ChangeLog</strong>";
			$head[$h][2] = "cien";
			$h++;

			return $head;
		}

		function getSelected( $v1, $v2 ){
			if( $v1==$v2 ){
				return "selected";
			}else{
				return "";
			}
		}

		public function obtener_catalogo($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion = 0, $tipo_sql = '', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

		    global $conf,$user,$langs;
		    $db = $this->db;


			$lista_claves_doli[] = array( "id" => 1, "etiqueta" => "Clave Producto/Servicio");
			$lista_claves_doli[] = array( "id" => 2, "etiqueta" => "Unidad de Medida");
			$lista_claves_doli[] = array( "id" => 3, "etiqueta" => "No. Identificación");
			$lista_claves_doli[] = array( "id" => 4, "etiqueta" => "Objeto de Impuesto");


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
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_pais WHERE active = 1";
		    		$valor_placeholder = "País";
		    		break;
		    	case 2:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_regimen_f WHERE active = 1";
		    		$valor_placeholder = "Régimen Fiscal";
		    		break;
		    	case 3:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_husoh WHERE active = 1";
		    		$valor_placeholder = "Huso Horario";
		    		break;
				case 4:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_husoh WHERE active = 1 LIMIT 1";
					$valor_placeholder = "Catálogo";
					break;
				case 5:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."product";
					$valor_placeholder = "Producto";
					break;
				case 6:
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid = 0";
					$valor_placeholder = "Nuevo Valor";
					break;

		    	default:
		    		$sql = "";
		    		break;
		    }

		    if($tipo_informacion == 1)
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

					if($tipo_catalogo == 5){
						$out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.' style="display: none;">';
					}else{
						$out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
					}

		            if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
		            if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
		            $out.= '<option value="-1">Seleccione '.$valor_placeholder.'</option>';
		            $i=0;
		            while ($rw = $db->fetch_object($resql)) {
						switch ($tipo_catalogo) {
							case 3:
								$valor    = $rw->label;
								$etiqueta = $langs->trans($rw->label);

								if ($selected == $valor) {
									$out.= '<option value="'.$valor.'" selected>';
								}else{
									$out.= '<option value="'.$valor.'">';
								}

								$out.= $etiqueta."</option>";
								break;

							case 4:
								foreach ($lista_claves_doli as $list_ca) {
									$valor    = $list_ca["id"];
									$etiqueta = $list_ca["etiqueta"];

									if ($selected == $valor) {
										$out.= '<option value="'.$valor.'" selected>';
									}else{
										$out.= '<option value="'.$valor.'">';
									}

									$out.= $etiqueta."</option>";
								}
								break;

							case 5:
								$valor    = $rw->rowid;
								$list_etiqueta = array();

								if(!empty($rw->ref)){
									$list_etiqueta[] = $rw->ref;
								}

								if(!empty($rw->label)){
									$list_etiqueta[] = $rw->label;
								}

								$etiqueta = implode(" - ", $list_etiqueta);

								if ($selected == $valor) {
									$out.= '<option value="'.$valor.'" selected>';
								}else{
									$out.= '<option data-label="'.$etiqueta.'"  value="'.$valor.'">';
								}

								$out.= $etiqueta."</option>";
								break;

							default:
								$valor    = $rw->code;
								$etiqueta = $rw->code." - ".$rw->label;

								if ($selected == $valor) {
									$out.= '<option value="'.$valor.'" selected>';
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
					$out.= '<option value="-1">Seleccione '.$valor_placeholder.'</option>';
		        }
		        $out.= '</select>';

		        // if($tipo_informacion == 1)
		        // 	$out = $etiqueta;
		    }else{
		        dol_print_error($db);
		    }
		    return $out;
		}

		public function validarEmisor(){
			global $conf;

			$sql_emisor = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE emisor_rfc="'. $conf->global->MAIN_INFO_SIREN.'" AND entity_id = '.$conf->entity;
        	$res_emisor = $this->db->query($sql_emisor);
        	$num_emisor = $this->db->num_rows($res_emisor);

        	if($num_emisor > 0){
        		$obj_emisor = $this->db->fetch_object($res_emisor);
        		$num_emisor = $obj_emisor->rowid;
        	}

        	return $num_emisor;
		}

		public function registarEmisor(){
			global $conf;

			dolibarr_set_const($this->db, "MAIN_INFO_SIREN", $this->datos_emisor["rfc"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_HUSO_HORARIO", $this->datos_emisor["huso_horario"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_RAZON_SOCIAL", $this->datos_emisor["razon_social"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_REGIMEN_FISCAL", $this->datos_emisor["regimen_fiscal"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_DIRECCION", $this->datos_emisor["direccion"], 'chaine', 1, '', $conf->entity);

            $sql = "";
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp";
            $sql .= " ( ";
	            $sql.=' emisor_rfc,';
	            $sql.=' razon_social,';
	            $sql.=' regimen,';
	            $sql.=' pais,';
	            $sql.=' estado,';
	            $sql.=' codigo_postal,';
	            $sql.=' emisor_delompio,';
	            $sql.=' emisor_colonia,';
	            $sql.=' emisor_calle,';
	            $sql.=' emisor_noext,';
	            $sql.=' emisor_noint,';
	            $sql.=' entity_id,';
	            $sql.=' cod_municipio,';
	            $sql.=' cod_colonia';
            $sql.=')';
            $sql.= ' VALUES';
            $sql.= '(';
	            $sql.= '"'.$this->datos_emisor["rfc"].'",';
	            $sql.= '"'.$this->datos_emisor["razon_social"].'",';
	            $sql.= '"'.$this->datos_emisor["regimen_fiscal"].'",';
	            $sql.= '"'.$conf->global->MAIN_INFO_SOCIETE_COUNTRY.'",';
	            $sql.= '"'.$conf->global->MAIN_INFO_SOCIETE_STATE.'",';
	            $sql.= '"'.$conf->global->MAIN_INFO_SOCIETE_ZIP.'",';
	            $sql.= '"'.$this->datos_emisor["delmpio"].'",';
	            $sql.= '"'.$this->datos_emisor["colonia"].'",';
	            $sql.= '"'.$this->datos_emisor["calle"].'",';
	            $sql.= '"'.$this->datos_emisor["noext"].'",';
	            $sql.= '"'.$this->datos_emisor["noint"].'",';
	            $sql.= '"'.$conf->entity.'",';
	            $sql.= '"'.$this->datos_emisor["clave_mpio"].'",';
	            $sql.= '"'.$this->datos_emisor["clave_col"].'"';
            $sql.= ')';
            // echo $sql."<br>";
            $res = $this->db->query($sql);

            if ($res) {
                return 1;
            }else {
                return 0;
            }
		}

		public function actualizarEmisor(){
			global $conf;

			dolibarr_set_const($this->db, "MAIN_INFO_SIREN", $this->datos_emisor["rfc"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_HUSO_HORARIO", $this->datos_emisor["huso_horario"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_RAZON_SOCIAL", $this->datos_emisor["razon_social"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_REGIMEN_FISCAL", $this->datos_emisor["regimen_fiscal"], 'chaine', 1, '', $conf->entity);
            dolibarr_set_const($this->db, "CFDIMX_DIRECCION", $this->datos_emisor["direccion"], 'chaine', 1, '', $conf->entity);

	        $sql_up = "";
            $sql_up = "UPDATE  " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp";
            $sql_up .= " SET";
            	$sql_up.= ' emisor_rfc = "'.$this->datos_emisor["rfc"].'",';
	            $sql_up.= ' razon_social = "'.$this->datos_emisor["razon_social"].'",';
	            $sql_up.= ' regimen = "'.$this->datos_emisor["regimen_fiscal"].'",';
	            $sql_up.= ' pais = "'.$conf->global->MAIN_INFO_SOCIETE_COUNTRY.'",';
	            $sql_up.= ' estado = "'.$conf->global->MAIN_INFO_SOCIETE_STATE.'",';
	            $sql_up.= ' codigo_postal ="'.$conf->global->MAIN_INFO_SOCIETE_ZIP.'",';
	            $sql_up.= ' emisor_delompio ="'.$this->datos_emisor["delmpio"].'",';
	            $sql_up.= ' emisor_colonia ="'.$this->datos_emisor["colonia"].'",';
	            $sql_up.= ' emisor_calle = "'.$this->datos_emisor["calle"].'",';
	            $sql_up.= ' emisor_noext = "'.$this->datos_emisor["noext"].'",';
	            $sql_up.= ' emisor_noint = "'.$this->datos_emisor["noint"].'",';
	            $sql_up.= ' cod_municipio = "'.$this->datos_emisor["clave_mpio"].'",';
	            $sql_up.= ' cod_colonia = "'.$this->datos_emisor["clave_col"].'"';
            $sql_up .= " WHERE rowid = " . $this->datos_emisor["rowid"];

            if($this->datos_emisor["rowid"] > 0){
	            // echo $sql_up."<br>";
	            $res = $this->db->query($sql_up);
	            if ($res) {
	                return 1;
	            }else {
	                return 0;
	            }
	        }else{
	        	return 0;
	        }
		}

		public function getEmisor(){
			global $conf;

			$aux_tmp_country = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
			$aux_tmp_state   = explode(":", $conf->global->MAIN_INFO_SOCIETE_STATE);
			if($conf->global->CFDIMX_DIRECCION != ""){
				if(is_numeric($conf->global->CFDIMX_DIRECCION)){
					$aux_tmp_address = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
				}else{
					$aux_tmp_address = $conf->global->CFDIMX_DIRECCION;
				}
			}else{
				$aux_tmp_address = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
			}

			$delmpio    = null;
            $clave_mpio = null;
            $colonia    = null;
            $clave_col  = null;
            $calle      = null;
            $noext      = null;
            $noint      = null;

			$sql_emisor = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE emisor_rfc="'. $conf->global->MAIN_INFO_SIREN.'" AND entity_id = '.$conf->entity;
        	$res_emisor = $this->db->query($sql_emisor);
        	$num_emisor = $this->db->num_rows($res_emisor);

        	if($num_emisor > 0){
        		$obj_emisor = $this->db->fetch_object($res_emisor);

        		$delmpio    = $obj_emisor->emisor_delompio;
	            $clave_mpio = $obj_emisor->cod_municipio;
	            $colonia    = $obj_emisor->emisor_colonia;
	            $clave_col  = $obj_emisor->cod_colonia;
	            $calle      = $obj_emisor->emisor_calle;
	            $noext      = $obj_emisor->emisor_noext;
	            $noint      = $obj_emisor->emisor_noint;
        	}

		    $this->datos_emisor = array(
		    					"MAIN_INFO_SOCIETE_COUNTRY"   => $conf->global->MAIN_INFO_SOCIETE_COUNTRY,
		    					"MAIN_INFO_SOCIETE_COUNTRY_E" => $aux_tmp_country,
		    					"MAIN_INFO_SOCIETE_COUNTRY_L" => getCountry($aux_tmp_country[0], 1),
		    					"MAIN_INFO_SIREN"             => $conf->global->MAIN_INFO_SIREN,
		    					"MAIN_INFO_SOCIETE_STATE"     => $conf->global->MAIN_INFO_SOCIETE_STATE,
		    					"MAIN_INFO_SOCIETE_STATE_E"   => $aux_tmp_state,
		    					"MAIN_INFO_SOCIETE_STATE_L"   => getState($aux_tmp_state[0], 1),
		    					"MAIN_INFO_SOCIETE_ZIP"       => $conf->global->MAIN_INFO_SOCIETE_ZIP,
		    					"MAIN_INFO_SOCIETE_ADDRESS"   => $conf->global->MAIN_INFO_SOCIETE_ADDRESS,
		    					"CFDIMX_RAZON_SOCIAL"         => $conf->global->CFDIMX_RAZON_SOCIAL,
		    					"CFDIMX_REGIMEN_FISCAL"       => $conf->global->CFDIMX_REGIMEN_FISCAL,
		    					"CFDIMX_HUSO_HORARIO"         => $conf->global->CFDIMX_HUSO_HORARIO,
		    					"CFDIMX_DIRECCION"            => $aux_tmp_address,
		    					"delmpio"                     => $delmpio,
		    					"clave_mpio"                  => $clave_mpio,
		    					"colonia"                     => $colonia,
		    					"clave_col"                   => $clave_col,
		    					"calle"                       => $calle,
		    					"noext"                       => $noext,
		    					"noint"                       => $noint
		    				);
		}
	}
?>
