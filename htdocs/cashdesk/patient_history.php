<?php 
/**
 * Zabdi Ramírez Garcia                                 zabdi.ramirez@lionintel.com
 * 
 *Funcionalidad visualizar el historial de consultas de un paciente 
 *y realizar una copia de los productos recetados.
 * 
 */
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
$socid = (GETPOST('id', 'int') ? GETPOST('id', 'int') : ''); // For backward compatibility
$societe = new Societe($db);
$societe->fetch($socid);
?>



<?php
print '<h4 class="titre1">Historial de recetas</h4>';
print '<h4 class="titre1">Paciente: '.$societe -> name.'</h4>';

print '<div class="contenu">';
print '<div class="cadre_search">';
print '<table class="center patientTable">';
print '<thead style="width: 50%;">';
print '<tr>';
print '<th> Referencia </th>';
print '<th> Fecha </th>';
print '<th> Padecimiento </th>';
print '<th> Acción </th>';
print '</tr>';
print '</thead>';

// Obtener historial de consultas
$sql="SELECT f.rowid, f.ref, f.datef, c.label FROM llx_facture f LEFT JOIN llx_condition c ON f.medical_condition = c.rowid WHERE f.fk_soc = '".$societe->id."' AND f.fk_statut = 0";
$resql = $db->query($sql);

print '<tbody>';
while ($obj = $db->fetch_object($resql)){
    // La acción del form selecciona el listado de productos del borrador seleccionado y además relaciona el padecimiento y paciente de la misma
    print '<form class="searchForm" method="POST" action="facturation_verif.php?action=change_invoice">';
    print '<input type="hidden" name="CASHDESK_ID_THIRDPARTY" value="'.$societe->id.'">';
    print '<input type="hidden" name="invoice_id" value="'.$obj->rowid.'">';
    print '<tr">';
    print '<td style="text-align: left;">'.$obj->ref.'</td>';
    print '<td>'.$obj->datef.'</td>';
    print '<td>'.$obj->label.'</td>';
    print '<td style="text-align: center;">';
    print '<input class="button btnselect inline-block valignmiddle" onclick="closev();" type="submit" id="bouton_change_thirdparty" value="'.$langs->trans("Select").'">';
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
print '</tbody>';
print '</table>';
print '</div>';
print '</div>';
