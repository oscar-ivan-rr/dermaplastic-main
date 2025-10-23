<?php
	require_once("../main.inc.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
	require_once(DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php');
	require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
	// require_once("conf.php");
	require_once("class/complementos.class.php");

	global $user, $conf, $db;

	$objComplementos = new ComplementosCFDI($db);

	$langs->load('admin');
	$langs->load('companies');

	if ($user->rights->cfdimx->create == 1) {

		if(!isset($_REQUEST["mod"]) || $_REQUEST["mod"]=="cfdi_rel"){
			$inc   = "cfdi_relacionados.php";
			$tab   = "uno";
			$title = "CFDI ".$conf->global->CFDIMX_VERSION_SAT." - CFDI Relacionados";
		}

		$archivos_complementos = DOL_DOCUMENT_ROOT.'/cfdimx/carta_porte.php';
		if(file_exists($archivos_complementos) == true){
			if($_REQUEST["mod"]=="cce"){
				$inc   = "cce.php";
				$tab   = "dos";
				$title = "CFDI ".$conf->global->CFDIMX_VERSION_SAT." - Comercio Exterior";
			}
			if( $_REQUEST["mod"]=="carta_porte"){
				$inc   = "carta_porte.php";
				$tab   = "tres";
				$title ="CFDI ".$conf->global->CFDIMX_VERSION_SAT." - Carta Porte";
			}
		}
		
		llxHeader('',$title);		

		$head = $objComplementos->cfdimx_docrel_prepare_head();
		dol_fiche_head($head, $tab, "Complementos CFDI", 0, 'accounting');

		include($inc);

		dol_fiche_end();
	}
	else {
		accessforbidden();
	}

	llxFooter();

	$db->close();
?>