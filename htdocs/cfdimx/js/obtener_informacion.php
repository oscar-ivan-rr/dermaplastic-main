<?php	
    error_reporting(0);    

	include("../../main.inc.php");		
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once("../class/facturacfdimx.class.php");	

	$zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

	global $user, $db, $conf;
	
	$action           = GETPOST('action');

    if($action == 'update_info_doli'){	
		$catalogo_sel = GETPOST('catalogo');
		$catalogo_tabla = "";

		switch ($catalogo_sel) {
			case 1:
				$catalogo_tabla = "c_cfdimx_clave_prodserv";
				break;
			
			case 2:
				$catalogo_tabla = "c_cfdimx_unidad_medida";
				break;			

			case 4:
				$catalogo_tabla = "c_cfdimx_objimpuesto";
				break;
						
		}

		$lista_claves = null;

		if($catalogo_sel != 3){
			$sql_num_estacion = "SELECT * FROM ".MAIN_DB_PREFIX."".$catalogo_tabla." WHERE active = 1";
			$res_num_estacion = $db->query($sql_num_estacion);
			$num_num_estacion = $db->num_rows($res_num_estacion);

			if($num_num_estacion > 0){
				while ($obj = $db->fetch_object($res_num_estacion)) {
					$etiqueta = $obj->label;
					$num_espacios = explode("-", $etiqueta);
					if(count($num_espacios) > 1){
						$etiqueta = $num_espacios[1];
					}

					$lista_claves[] = array(
												'clave'    => $obj->code,
												'etiqueta' => $etiqueta,
												'count'    => count($num_espacios)
											);
				}
			}
		}

		echo json_encode($lista_claves);
	}
	
	//echo obtener_catalogo('', 'tmp', 1);
?>