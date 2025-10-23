<?php



$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/place.class.php');
dol_include_once('/pos/class/pos.class.php');

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

$langs->load("main");
$langs->load("pos@pos");
$id=GETPOST('id');



// ID required
if ($id > 0) {
	$object = new Ticket($db);

	// Get Ticket object
	if ($object->fetch($id) > 0 ) {
		
		
		
		$sign = ((in_array($object->statut, [2]) && POS::isCredit($id) && $object->diff_payment >0 ) || $object->type == 1);
		if ($object->statut == 3)
		{
			$sign = true;
		}
		
		
		// Generate doc
		if ($sign && $conf->global->MAIN_SIGNATURE_MODULE) {
			$ref = $object->ref;
			$element = "Ticket";
			$objectfile = "/custom/pos/class/ticket.class.php";
			$dir = $conf->pos->dir_output."/" . $object->ref;

			$ref = dol_sanitizeFileName($object->ref);
			$model_pdf = 'ticket';
			$moreinputs = array('force_redirect' => 1, 'url_doc' => 1, 'modulepart' => 'pos', 'relativepath' => "$ref/$ref.pdf");
			
			include (DOL_DOCUMENT_ROOT .'/custom/signature/index.php');
			die();
		}
		else $result = $object->generateDocument("ticket", $langs);
		
		
		
		if ($result > 0) {
			// Show doc in window if are saved in system
			$ref = dol_sanitizeFileName($object->ref);
			$relativepath = "$ref/$ref.pdf";
	
			$urladvancedpreview = getAdvancedPreviewUrl("pos", $relativepath, 1); // Return if a file is qualified for preview.
			if (count($urladvancedpreview)) {
				header ("Location: " . $urladvancedpreview['url']);
				exit;
			}
		}
	}
	
}

?>