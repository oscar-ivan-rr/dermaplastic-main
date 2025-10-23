<?php



$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/place.class.php');

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

$langs->load("main");
$langs->load("pos@pos");
$id=GETPOST('id');

// ID required
if ($id > 0) {
    $object = new Ticket($db);

    // Get Ticket object
    if ($object->fetch($id) > 0 ) {
        $object->generateDocument("gift", $langs);
    }
}

?>