<?php


require '../main.inc.php';

if (!empty($conf->accounting->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}


require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

$id=GETPOST('id');




// ID required
if ($id > 0) {
	$object = new ExpenseReport($db);

	// Get Ticket object
	if ($object->fetch($id) > 0 ) {
		// Generate doc
		if ($conf->global->MAIN_SIGNATURE_MODULE) {
            $ref = $object->ref;
            $element = "ExpenseReport";
            $objectfile = "/expensereport/class/expensereport.class.php";
            $dir = $conf->expensereport->dir_output."/" . $object->ref;
            $moreinputs = array('force_redirect' => 1, 'url' => DOL_URL_ROOT . '/expensereport/expenses_ticket.php?id= ' . $object->id);
            include (DOL_DOCUMENT_ROOT .'/custom/signature/index.php');
            die();
		}
	}
}

?>