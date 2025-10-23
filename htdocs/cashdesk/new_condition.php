<?php 
require '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","cashdesk"));

$form=new Form($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('', '', 0, 0, '', $arrayofcss);

$action         = GETPOST('action', 'aZ09');
$condition  = GETPOST('name_condition');
$notes          = $_POST["note"];
$prod_id = $_POST["prod_id"];
$ref = $_POST["ref"];
$errorName      = "";
$errorNotes     = "";
$errorProducts  = "";

// Variables de sesión para conservar valores de inputs al refrescar página
$_SESSION['name'] = $condition;
$_SESSION['inputs'] = $notes;
$_SESSION['products_id'] = $prod_id;
$_SESSION['products_ref'] = $ref;

/*
 * Actions
 */
if($action == 'add'){
    $prod_qty = count($prod_id);
    $notes_qty = count($notes);

    // VALIDACIONES
    if (empty(trim($condition))){
        $errorName = "El nombre no debe estar vacío";
    } 

    if($notes_qty == 0){
        $errorNotes = "Debe agregar al menos una nota";
    } else {
        foreach ($notes as $valor) {
            if (empty(trim($valor))){
                $errorNotes = "Asegúrese de llenar todos los campos";
            }
        }
    }

    if ($prod_qty <=0){
        $errorProducts = "Debe agregar al menos un producto";
    }


    // Guardar la información si todo el formulario se ha llenado correctamente
    if($errorName == "" && $errorNotes == "" && $errorProducts == ""){
        $sql="INSERT INTO llx_condition (label, user) VALUES('".$condition."', '".$user->id."')";
        $resql = $db->query($sql);
        if($resql > 0){
            $id = $db->last_insert_id(MAIN_DB_PREFIX."condition");
            
            foreach ($notes as $note) {
                $sql="INSERT INTO llx_condition_note (fk_condition, note) VALUES('".$id."', '".$note."')";
                $resql2 = $db->query($sql);
            }

            foreach ($prod_id as $product) {
                $sql="INSERT INTO llx_condition_product (fk_condition, fk_product) VALUES('".$id."', '".$product."')";
                $resql3 = $db->query($sql);
            }

            if($resql2 > 0 && $resql3 > 0){
                print '<p class="txtsave"> Padecimiento agregado correctamente <br> &#10004; </p>';
                print '<script language="javascript">
                        setTimeout(function(){
                            history.go(-2);
                        },100);
                    </script>';
            }
        }
    }
}

/*
 * View
 */
print '<div class="backgroundgradient">';

print '<div class="title-condition">';
print '<a href="#" onclick="toBack();"><img class="img-add" src="/cashdesk/img/back.png"></a><h4 class="titre1 inline-block">Nuevo padecimiento</h4>';
print '</div>';
session_start();
print '<form id="formadd" name="formadd" method="POST" action="new_condition.php">';
print '<input type="hidden" name="action" value="add">';
print '<div class="condition-info">';
print '<div class="condition-display">';
print '<p class="titre2 inline-block">Nombre: </p>';
if (!empty($_SESSION['name'])) {
    $value = $_SESSION['name'];
} else {
    $value = "";
}
print '<input  class="input-condition" type="text" name="name_condition" id="name_condition" value="'.$value.'""/>';
print '</div>';
print '<span class="msgError">'.$errorName.'</span>';
print '<br>';
print '<br>';
print '<div class="condition-display">';

print '<p class="titre2 inline-block">Notas: </p>
        <div class="contenedor-inputs" id="contenedor-inputs">';

if (isset($_SESSION['inputs'])) {
    foreach ($_SESSION['inputs'] as $input) {
        print '<input  class="note-condition" type="text" name="note[]" value="' . $input . '" />';
    }
} else {
print '<input  class="note-condition" type="text" name="note[]" />';
}
print '</div>';
print '</div>';
print '<br>';
print '<div class="btnotes">';
print '<span class="msgError">'.$errorNotes.'</span>';
print '<br>';
print '<a class="button add-input" id="agregar-input">Agregar</a>';
print '<a class="button delete-input eliminar-input">Eliminar</a>';
print '</div>';
print '<br>';
print '<br>';
print '<br>';
print '<div class="condition-display">';
print '<p class="titre2 inline-block">Productos: </p>
        <div class="contenedor-prod" id="contenedor-prod">';
print $form->select_produits('Busqueda de productos', 'list_prod', '0', 20, 1, -1, 2, '', 2, 0, 0, 1, 0, 'minwidth550');
print '<a class="button add-prod" id="agregar-prod">Agregar</a>';
print '<br>';
if (isset($_SESSION['products_id']) && isset($_SESSION['products_ref'])) {
    $i = 0;
    foreach ($_SESSION['products_id'] as $id) {
        print '<div class="divProd" id="div'.$i.'">';
        print '<input class="product-input" type="hidden" name="prod_id[]" value="'.$id.'"/>';
        print '<input readOnly="true" class="product-input" type="text" name="ref[]" value="'.$_SESSION['products_ref'][$i].'"/>';
        print '<span class="msgError"><a class="eliminarDivProd"><img class="img-delete-item" src="/cashdesk/img/delete_item.png"></a></span>';
        print '</div>';
        $i++;
    }
}
print '</div>';
print '</div>';
print '<br>';
print '<span class="msgError">'.$errorProducts.'</span>';
print '</div>';
print '<br>';
print '<br>';
print '<div class="title-condition">';
print '<input type="submit" class="button add-condition" value="Guardar"/>';
print '</div>';
print '</form>';

print '</div>';
?>

<script language="javascript">

    function toBack(){
        if (confirm('¿Está seguro de salir de esta página? No se guardará ningún cambio.')) {
            history.back();
        }
    }

    $(document).ready(function() {
        var num_prod = 0;

        

        $('#agregar-input').click(function() {
            var nuevoInput = $('<input class="note-condition" type="text" name="note[]" />');
            $('#contenedor-inputs').append(nuevoInput);
        });

        $('.eliminar-input').click(function() {
            $('#contenedor-inputs input:last-child').remove();
        });

        $('#agregar-prod').click(function() {
            var ref = $('#search_list_prod').val();
            var id = $('#list_prod').val();
            if(id!=''){
                num_prod ++;
                var nuevoProd = $(
                '<div class="divProd" id="div'+num_prod+'"><input class="product-input" type="hidden" name="prod_id[]" value="'+id+'"/>'+
                '<input readOnly="true" class="product-input" type="text" name="ref[]" value="'+ref+'"/>'+
                '<span class="msgError"><a class="eliminarDivProd"><img class="img-delete-item" src="/cashdesk/img/delete_item.png"></a></span></div>');
                $('#contenedor-prod').append(nuevoProd);
            }
        });

        $('.eliminarDivProd').click(function() {
            var divId = $(this).closest('.divProd').attr('id');
            $('#' + divId).remove();
        });
    });
</script>

