<?php
/**
 * Zabdi Ramírez Garcia
 * Visualización del listado de padecimientos correspondientes al usuario logueado
 * y funcionalidad para modificar la variable de sesión relacionada al mismo.
 * 
 */
 
use Luracast\Restler\Data\Arr;

require '../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","cashdesk"));



$form=new Form($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('', '', 0, 0, '', $arrayofcss);

$action     = GETPOST('action', 'aZ09');
$keyword    = GETPOST('buscar');


/*
 * View
 */
print '<div class="backgroundgradient">';

print '<div class="title-condition">';
print '<h4 class="titre1 inline-block">Lista de padecimientos</h4><a href="new_condition.php"><img class="img-add" src="/cashdesk/img/add.png"></a>';
print '</div>';

// Obtener padecimientos
$sql="SELECT rowid, label FROM llx_condition WHERE user = '".$user->id."' ORDER BY label";
$resql = $db->query($sql);
print '<div class="condition-list">';
if($resql->num_rows > 0){
    $id = array();
    $i = 0;
    while ($obj = $db->fetch_object($resql)){
        print '<div class="condition-display divProd" id="div'.$i.'">';
        print '<form class="searchForm" method="POST" action="facturation_verif.php?action=change_condition">';
        print '<input type="hidden" name="action" value="delete"/>';
        print '<input class="idProd" type="hidden" name="id" id="id'.$i.'" value="'.$obj->rowid.'"/>';
        print '<input readOnly="true" type="submit" onclick="closev();" class="label-condition" name="condition" value="'.$obj->label.'"/><a href="#" class="optionProd editProd" title="Editar padecimiento"><img class="options-img" src="/cashdesk/img/edit.png"></a>
        <a title="Eliminar padecimiento" href="#" class="eliminarProd optionProd"><img class="options-img" src="/cashdesk/img/delete.png"></a>';
        print '</form>';
        print '</div>';
        $i++;
    }
} else {
    print '<p class="txtsave">Aún no se agregan padecimientos. </p>';
}

print '</div>';

print '</div>';
?>

<script language="javascript">
    function closev()
    {
        setTimeout(function(){
            parent.location.reload();
        },100);
        
    }

    $(document).ready(function() {
        $('.editProd').click(function() {
            var formularioId = $(this).closest('.divProd').attr('id');
            var id = $('#' + formularioId + ' input[name="id"]').val();
            window.location.href = "edit_condition.php?id="+id;
        });

        // Eliminar producto seleccionado en el listado
        $('.eliminarProd').click(function() {
            var formularioId = $(this).closest('.divProd').attr('id');
            var id = $('#' + formularioId + ' input[name="id"]').val();
            var condition = $('#' + formularioId + ' input[name="condition"]').val();
            if (confirm('¿Está seguro de eliminar el padecimiento "'+condition+'"?')) {
                $.ajax({
                    url: 'ajax/ajax_condition.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        // Manejar la respuesta del servidor
                        location.reload();
                        
                    }
                });
            }
        });
    });
</script>