<?php
	global $user, $db, $conf;
    error_reporting(0);

    $zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

	if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
	if (! $res) die("Include of main fails");
	
	// include_once('lib/phpmailer/class.phpmailer.php');
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once("class/facturacfdimx.class.php");	
	
	$action           = GETPOST('action');

	if($action == 'domicilio'){
		$buscar_estado    = GETPOST('estado') != '' ? GETPOST('estado') : '';
	    $buscar_mpo       = GETPOST('mpo') != '' ? GETPOST('mpo') : '';
	    $buscar_localidad = GETPOST('localidad') != '' ? GETPOST('localidad') : '';
	    $buscar_cp        = GETPOST('cp') != '' ? GETPOST('cp') : '';
	    $buscar_colonia   = GETPOST('colonia') != '' ? GETPOST('colonia') : '';
	    $lista_claves     = null;

		if($buscar_estado != '' || $buscar_mpo != ''){
			$select_mpios     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_municipio";

			if($buscar_estado != ''){
				$select_mpios .= " WHERE clave_estado = '".$buscar_estado."'";
			}

			if($buscar_mpo != ''){
				if($buscar_estado != ''){
					$select_mpios .= " AND descripcion like '%".$buscar_mpo."%'";
				}else{
					$select_mpios .= " WHERE descripcion like '%".$buscar_mpo."%'";
				}
			}

			$res_select_mpios = $db->query($select_mpios);
			$num_select_mpios = $db->num_rows($res_select_mpios);

			if($num_select_mpios > 0){
				while ($obj = $db->fetch_object($res_select_mpios)) {
					$lista_claves[] = array(
											'clave_estado'    => $obj->clave_estado,
											'mpio'            => $obj->descripcion,
											'clave_mpio'      => $obj->clave_mpio,
											'localidad'       => '',
											'clave_localidad' => '',
											'cp'              => '',
											'colonia'         => '',
											'clave_colonia'   => ''
											);
				}
			}
		}

		if($buscar_estado != '' || $buscar_localidad != ''){
			$select_localidades     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_localidad";

			if($buscar_estado != ''){
				$select_localidades .= " WHERE clave_estado = '".$buscar_estado."'";
			}

			if($buscar_localidad != ''){
				if($buscar_estado != ''){
					$select_localidades .= " OR descripcion like '%".$buscar_localidad."%'";
				}else{
					$select_localidades .= " WHERE descripcion like '%".$buscar_localidad."%'";
				}
			}

			$res_select_localidades = $db->query($select_localidades);
			$num_select_localidades = $db->num_rows($res_select_localidades);

			if($num_select_localidades > 0){
				while ($obj = $db->fetch_object($res_select_localidades)) {
					$lista_claves[] = array(
											'clave_estado'    => $obj->clave_estado,
											'mpio'            => '',
											'clave_mpio'      => '',
											'localidad'       => $obj->descripcion,
											'clave_localidad' => $obj->clave_localidad,
											'cp'              => '',
											'colonia'         => '',
											'clave_colonia'   => ''
											);
				}
			}
		}

		if($buscar_cp != '' || $buscar_colonia != ''){
			$select_colonias     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_colonia";

			if($buscar_cp != ''){
				$select_colonias .= " WHERE codigo_postal = '".$buscar_cp."'";
			}

			if($buscar_colonia != ''){
				if($buscar_cp != ''){
					$select_colonias .= " OR descripcion like '%".$buscar_colonia."%'";
				}else{
					$select_colonias .= " WHERE descripcion like '%".$buscar_colonia."%'";
				}
			}

			$res_select_colonias = $db->query($select_colonias);
			$num_select_colonias = $db->num_rows($res_select_colonias);

			if($num_select_colonias > 0){
				while ($obj = $db->fetch_object($res_select_colonias)) {
					$lista_claves[] = array(
											'clave_estado'    => '',
											'mpio'            => '',
											'clave_mpio'      => '',
											'localidad'       => '',
											'clave_localidad' => '',
											'cp'              => $obj->codigo_postal,
											'colonia'         => $obj->descripcion,
											'clave_colonia'   => $obj->clave_colonia
											);
				}
			}
		}

		echo json_encode($lista_claves);
	}

	if($action == 'num_estacion'){
		$tipo_transporte   = GETPOST('tipo_transporte') != '' ? GETPOST('tipo_transporte') : '';
		$lista_num_estacion = null;

		$sql_num_estacion = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_estaciones WHERE clave_t = '".$tipo_transporte."' AND active = 1";
		$res_num_estacion = $db->query($sql_num_estacion);
		$num_num_estacion = $db->num_rows($res_num_estacion);

		if($num_num_estacion > 0){
			while ($obj = $db->fetch_object($res_num_estacion)) {
				$lista_num_estacion[] = array(
											'clave'    => $obj->code,
											'etiqueta' => $obj->label
										);
			}
		}

		echo json_encode($lista_num_estacion);
	}

	if($action == 'update_info_doli'){	
		$lista_claves[] = array(
								'clave'    => 1,
								'etiqueta' => 2
							);

		echo json_encode($lista_claves);
	}
	
	//echo obtener_catalogo('', 'tmp', 1);
?>