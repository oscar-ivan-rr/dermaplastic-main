<?php
	require("../../main.inc.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	include("../lib/nusoap/lib/nusoap.php");
	// include("../conf.php");
	require_once("../class/configuracion.class.php");
	require_once(DOL_DOCUMENT_ROOT.'/cfdimx/lib/PHPExcel/PHPExcel/IOFactory.php');

	global $user, $conf;

	$objConf = new ConfiguracionCFDI($db);

	$langs->load('admin');
	$langs->load('companies');

	if ($user->rights->cfdimx->config->access == 1 || !$user->admin) {
		$titulo_header = "";
		if( !isset($_REQUEST["mod"]) || $_REQUEST["mod"]=="dataEmisor" ){
			$inc = "datosEmisor.php";
			$tab="uno";
			$titulo_header = "Datos del Emisor";
			$icono_tab = "user";
		}

		if( $_REQUEST["mod"]=="config" ){
			$inc = "configuracion.php";
			$tab="dos";
			$titulo_header = "Configuración Web Services";
			$icono_tab = "globe-americas";
		}

		if( $_REQUEST["mod"]=="retenciones" ){
			$inc = "retenciones.php";
			$tab="cinco";
			$titulo_header = "Retenciones";
			$icono_tab = "bill";
		}

		if( $_REQUEST["mod"]=="formaspago" ){
			$inc = "formaspago.php";
			$tab="seis";
			$titulo_header = "Formas de Pago";
			$icono_tab = "ticket";
		}

		if( $_REQUEST["mod"]=="configopcional" ){
			$inc = "configuracion_opcional.php";
			$tab="siete";
			$titulo_header = "Configuración Opcional";
			$icono_tab = "tools";
		}

		if( $_REQUEST["mod"]=="cargamasivaclaves" ){
			$inc = "cargamasiva_claves.php";
			$tab="ocho";
			$titulo_header = "Carga Masiva de Claves SAT";
			$icono_tab = "dir";
		}
		
		if( $_REQUEST["mod"]=="updatedatos_doli" ){
			$inc = "informacion_dolibarr.php";
			$tab="nueve";
			$titulo_header = "Actualizar Información Dolibarr";
			$icono_tab = "edit";
		}

		if( $_REQUEST["mod"]=="recursos_sat" ){
			$inc = "recursos_sat.php";
			$tab="diez";
			$titulo_header = "Información y Guías de llenado del SAT";
			$icono_tab = "edit";
		}

		if( $_REQUEST["mod"]=="emisores" ){
			$inc = "emisores.php";
			$tab="once";
			$titulo_header = "Creación de nuevos emisores";
			$icono_tab = "edit";
		}

		if( $_REQUEST["mod"]=="changelog" ){
			$inc = "changelog.php";
			$tab="cien";
			$titulo_header = "ChangeLog";
			$icono_tab = "list";
		}

		$title="Configuración CFDI ".$conf->global->CFDIMX_VERSION_SAT." - ".$titulo_header;
		llxHeader('',$title);

		$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
		print load_fiche_titre($title,$linkback,'tools');

		$head = $objConf->cfdimx_admin_prepare_head();
		print dol_get_fiche_head($head, $tab, $titulo_header, 0, $icono_tab);

		include($inc);

		print dol_get_fiche_end();
	}else {
		accessforbidden();
	}

	llxFooter();

	$db->close();
?>
