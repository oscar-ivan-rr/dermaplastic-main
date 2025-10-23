<?php

$res=0;

if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");

if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

if (! $res) die("Include of main fails");

//if(file_exists("lib/nusoap/lib/nusoap.php")) {include("/lib/nusoap/lib/nusoap.php");}else{print 'nusoap lib not found';}

require_once("lib/nusoap/lib/nusoap.php");
require_once("conf.php");



// Load traductions files requiredby by page

$langs->load("companies");

$langs->load("other");



// Get parameters

$id			= GETPOST('id','int');

$action		= GETPOST('action','alpha');

$myparam	= GETPOST('myparam','alpha');



// Protection if external user

if ($user->societe_id > 0)

{

	//accessforbidden();

}







/*******************************************************************

* ACTIONS

*

* Put here all code to do according to value of "action" parameter

********************************************************************/


/***************************************************

* VIEW

*

* Put here all code to build page

****************************************************/



llxHeader('','Facturacion Electronica::CFDI','');



$form=new Form($db);





// Put here content of your page



// Example 1 : Adding jquery code

print '<script type="text/javascript" language="javascript">

jQuery(document).ready(function() {

	function init_myfunc()

	{

		jQuery("#myid").removeAttr(\'disabled\');

		jQuery("#myid").attr(\'disabled\',\'disabled\');

	}

	init_myfunc();

	jQuery("#mybutton").click(function() {

		init_needroot();

	});

});

</script>';







// Example 2 : Adding jquery code

//$somethingshown=$myobject->showLinkedObjectBlock();



//REPORTE
print '<table width="100%" class="notopnoleftnoright">';
print '<tr>';
print '<td valign="top" width="20%" class="notopnoleft">';
//print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id_menu='.$_GET['idmenu'].'&bus=1">';
$urlsource2=DOL_MAIN_URL_ROOT."/cfdimx/reporteexcel.php";
print '<form method="POST" action="'.$urlsource2.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table width="100%" class="noborder" >';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">Reporte</td></tr>';
print '</tr>';
print '<tr><td>&nbsp;<td></tr>';
$fecha=date("Y-m-d");
print '<tr><td colspan="2" align="center">Del: <input type="date" name="finicio" id="finicio" value="'.$fecha.'"><td></tr>';
print '<tr><td colspan="2" align="center">Al: <input type="date" name="ffin" id="ffin" value="'.$fecha.'"><td></tr>';
//$urlsource= str_replace('reporte', 'reporteexcel', $_SERVER['PHP_SELF']);
//print '<tr><td colspan="2" align="center"><a href="'.$urlsource.'">Reporte al '.date('Y-m-d').'<a/></td></tr>';
print '<tr><td colspan="2" align="center"><input type="submit" value="Generar Reporte"></td></tr>';
print '<tr><td>&nbsp;<td></tr>';
if(GETPOST('mesag')=='no'){
print '<tr><td  colspan="2" align="center"><span style="color:red">No se han encontrado resultados</span><td></tr>';
}
print "</table></form><br>";

/* 
//REPORTE
print '<table width="100%" class="notopnoleftnoright">';
print '<tr>';
print '<td valign="top" width="20%" class="notopnoleft">';
print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id_menu='.$_GET['idmenu'].'&bus=1">';
//$urlsource2=DOL_MAIN_URL_ROOT."/cfdimx/reporteexcel.php";
//print '<form method="POST" action="'.$urlsource2.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table width="100%" class="noborder" >';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">Reporte Unico</td></tr>';
print '</tr>';
print '<tr><td>&nbsp;<td></tr>';
$urlsource= str_replace('reporte', 'reporteexcel', $_SERVER['PHP_SELF']);
print '<tr><td colspan="2" align="center"><a href="'.$urlsource.'">Reporte al '.date('Y-m-d').'<a/></td></tr>';
//print '<tr><td colspan="2" align="center"><input type="submit" value="Generar Reporte"></td></tr>';
print '<tr><td>&nbsp;<td></tr>';
print "</table></form><br>";
 */


// End of page
llxFooter();

$db->close();

?>

