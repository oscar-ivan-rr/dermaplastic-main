<?php 
require '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","cashdesk"));

/*
 * View
 */

$form=new Form($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('', '', 0, 0, '', $arrayofcss);

$action     = GETPOST('action', 'aZ09');
$keyword    = GETPOST('buscar');
?>



<?php
print '<h4 class="titre1">Pacientes</h4>';
print '<div class="center contenu">';
print '<div class="cadre_search">';
print '<form class="searchForm" id="form2" name="form2" method="POST" action="patient.php">';
print '<input type="hidden" name="action" value="search">';
print '<input class="keyword" type="text"id="buscar" placeholder="Ingresa nombre o teléfono" name="buscar" value="" >';
print '<input type="submit" class="button btnmenu" value="Buscar">';
print '</form>';
print '</div>';
print '</div>';

print '<div class="contenu">';
print '<div class="cadre_search">';
print '<table class="center patientTable">';
print '<thead style="width: 50%;">';
print '<tr>';
print '<th style="width: 50%;"> Nombre </th>';
print '<th> Teléfono </th>';
print '<th> Acción </th>';
print '</tr>';
print '</thead>';

if($action == 'search'){
    $sql="SELECT ls.rowid, ls.nom, ls.phone FROM llx_societe as ls JOIN llx_societe_commerciaux lsc ON ls.rowid = lsc.fk_soc WHERE (ls.nom LIKE '%".$keyword."%' OR ls.phone LIKE '%".$keyword."%') AND lsc.fk_user = '".$user->id."'";
    $resql = $db->query($sql);
   
    print '<tbody>';
    while ($obj = $db->fetch_object($resql)){
        print '<form class="searchForm" method="POST" action="facturation_verif.php?action=change_thirdparty">';
        print '<input type="hidden" name="CASHDESK_ID_THIRDPARTY" value="'.$obj->rowid.'">';
        print '<tr">';
        print '<td style="text-align: left;">'.$obj->nom.'</td>';
        print '<td>'.$obj->phone.'</td>';
        print '<td style="text-align: center;">';
        print '<input class="button btnselect inline-block valignmiddle" onclick="closev();" type="submit" id="bouton_change_thirdparty" value="'.$langs->trans("Select").'">';
        print '<a style="color: white; font-weight: bold;" href="patient_history.php?id='.$obj->rowid.'" class="button btnHistory inline-block valignmiddle" id="bouton_change_thirdparty">Histórico</a>';
        print '</td>';
        print '</tr>';
        print '</form>';

    }
    ?>

    <script language="javascript">
    function closev()
    {
        setTimeout(function(){
            parent.location.reload();
        },100);
        
    }
    </script>

<?php
}

print '</tbody>';
print '</table>';
print '</div>';
print '</div>';
