<?php 
/**
 * Zabdi Ramírez Garcia                                 zabdi.ramirez@lionintel.com
 * 
 *Funcionalidad para editar padecimiento
 * 
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
// Load translation files required by the page
$langs->loadLangs(array("admin","cashdesk"));

/*
 * View
 */

$form=new Form($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('', '', 0, 0, '', $arrayofcss);

$action     = GETPOST('action', 'aZ09');
$id_condition = (GETPOST('id', 'int') ? GETPOST('id', 'int') : ''); // For backward compatibility
$condition  = (GETPOST('name_condition') ? GETPOST('name_condition') : '');
$notes          = ($_POST["note"]);
$idnotes          = ($_POST["idnote"]);
$prod_id = ($_POST["prod_id"]);
$ref = ($_POST["ref"]);
// Variables de sesión para conservar valores de inputs al refrescar página
$_SESSION['name'] = $condition;
$_SESSION['inputs'] = $notes;
$_SESSION['id_inputs'] = $idnotes;
$_SESSION['products_id'] = $prod_id;
$_SESSION['products_ref'] = $ref;
$errorName      = "";
$errorNotes     = "";
$errorProducts  = "";



/*
 * Actions
 */
if($action == 'edit'){
    $rowid = GETPOST('idN');
    $prod_qty = count($prod_id);
    $notes_qty = count($notes);
    $error = 0;

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
        $sql="UPDATE llx_condition SET label = '".$condition."' WHERE rowid = '".$rowid."'";
        $resql = $db->query($sql);
        if($resql > 0){
            $i = 0;
            foreach ($notes as $note) {
                // Validar si el input corresponde a una nota existe o es una nueva
                if ($_SESSION['id_inputs'][$i] > 0 ){
                    $sql="UPDATE llx_condition_note SET NOTE = '".$note."' WHERE rowid =  '".$_SESSION['id_inputs'][$i]."'";
                    $resql2 = $db->query($sql);
                    if(empty($resql2)){
                        $error ++;
                    }
                } else {
                    $sql="INSERT INTO llx_condition_note (fk_condition, note) VALUES('".$rowid."', '".$note."')";
                    $resql2 = $db->query($sql);
                    if(empty($resql2)){
                        $error ++;
                    }
                }
                $i++;
            }

            $j = 0;
            foreach ($prod_id as $product) {
                // Validar si el producto no existe aún 
                $sql="SELECT rowid FROM llx_condition_product WHERE fk_condition = '".$rowid."' AND fk_product = '".$_SESSION['products_id'][$j]."'";
                $resql3 = $db->query($sql);
                if($resql3->num_rows == 0){
                    $sql="INSERT INTO llx_condition_product (fk_condition, fk_product) VALUES('".$rowid."', '".$product."')";
                    $resql3 = $db->query($sql);
                    if(empty($resql3)){
                        $error ++;
                    }
                }
                $j++;
            }

            // Regresar a la ventana del listado de padecimientos
            if($error == 0){
                print '<p class="txtsave"> Padecimiento agregado correctamente <br> &#10004; </p>';
                print '<script language="javascript">
                            setTimeout(function(){
                                history.go(-2);
                            },100);
                        </script>';
            }
        } else {
            $error ++;
        }
    }
}

/*
 * View
 */
print '<div class="backgroundgradient">';
print '<div class="title-condition">';
print '<a href="#" onclick="toBack();"><img class="img-add" src="/cashdesk/img/back.png"></a><h4 class="titre1 inline-block">Editar padecimiento</h4>';
print '</div>';
session_start();
print '<form id="formadd" name="formadd" method="POST" action="edit_condition.php">';
print '<input type="hidden" name="action" value="edit">';
print '<input type="hidden" name="idN" id="idN" value="'.$id_condition.'">';
print '<div class="condition-info">';
print '<div class="condition-display">';
// Obtener nombre de padecimiento
$sql="SELECT label FROM llx_condition WHERE rowid = '".$id_condition."'";
$resql = $db->query($sql);
if(!empty($resql)){
    $obj = $db->fetch_object($resql);
    print '<p class="titre2 inline-block">Nombre: </p>';
    if (!empty($_SESSION['name'])) {
        $value = $_SESSION['name'];
    } else {
        $value = $obj->label;
    }
    print '<input  class="input-condition" type="text" name="name_condition" id="name_condition" value="'.$value.'""/>';
    print '</div>';
    print '<span class="msgError">'.$errorName.'</span>';
}
print '<br>';
print '<br>';
print '<div class="condition-display">';

print '<p class="titre2 inline-block">Notas: </p>
        <div class="contenedor-inputs" id="contenedor-inputs">';
// Obtener notas de padecimiento
$sql="SELECT rowid, note FROM llx_condition_note WHERE fk_condition = '".$id_condition."'";
$resql = $db->query($sql);
if(!empty($resql)){
    $i = 0;
    while($obj2 = $db->fetch_object($resql)){
        print '<div class="divNote" id="nota'.$i.'"><input  class="note-condition idn" type="hidden" name="idnote[]" value="' . $obj2->rowid . '" />';
        print '<input  class="note-condition" type="text" name="note[]" value="' . $obj2->note . '" />';
        print '<span class="msgError"><a class="eliminarNota"><img class="img-delete-item" src="/cashdesk/img/delete_item.png"></a></span>';
        print '</div>';
        $i++;
    }
}
if (isset($_SESSION['id_inputs'])) {
     $x = 0;
    foreach ($_SESSION['id_inputs'] as $input) {
        
        print '<div class="divNote" id="nota'.$i.'"><input  class="note-condition idn" type="hidden" name="idnote[]" value="' . $input . '" />';
        print '<input  class="note-condition" type="text" name="note[]" value="' . $_SESSION['inputs'][$x] . '" />';
        if($input>0){
            print '<span class="msgError"><a class="eliminarNota"><img class="img-delete-item" src="/cashdesk/img/delete_item.png"></a></span>';
        }
        print '</div>';
        $x++;
    }
} 
print '</div>';
print '</div>';
print '<br>';
print '<div class="btnotes">';
print '<span class="msgError">'.$errorNotes.'</span>';
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
$sql="SELECT rowid, fk_product FROM llx_condition_product WHERE fk_condition = '".$id_condition."'";
$resql = $db->query($sql);
if(!empty($resql)){
    while($obj3 = $db->fetch_object($resql)){
        $products = new Product($db);
        $products -> fetch($obj3->fk_product);
        print '<div class="divProd" id="div'.$i.'">';
        print '<input class="product-input prod_id" type="hidden" name="prod_id[]" value="'.$products->id.'"/>';
        print '<input readOnly="true" class="product-input" type="text" name="ref[]" value="'.$products->ref.'"/>';
        print '<span class="msgError"><a class="eliminarDivProd"><img class="img-delete-item" src="/cashdesk/img/delete_item.png"></a></span>';
        print '</div>';
    }
}
if (isset($_SESSION['products_id']) && isset($_SESSION['products_ref'])) {
    $i = 0;
    foreach ($_SESSION['products_id'] as $id) {
        print '<div class="divProd" id="div'.$i.'">';
        print '<input class="product-input prod_id" type="hidden" name="prod_id[]" value="'.$id.'"/>';
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
        // Agregar input adicional si se requiere una nuueva nota
        $('#agregar-input').click(function() {
            var nuevoInput = $('<div class="divNote"><input class="note-condition idn" type="hidden" name="idnote[]" value="0" />'+
            '<input class="note-condition" type="text" name="note[]" /></div>');
            $('#contenedor-inputs').append(nuevoInput);
        });

        // ELiminar input innecesario
        $('.eliminar-input').click(function() {
            var idNote = $('#contenedor-inputs div:last-child').find('.idn');
            if(idNote.val() === '0'){
                $('#contenedor-inputs div:last-child').remove()
            }
        });

        // Eliminar nota ya existente en la base de datos
        $('.eliminarNota').click(function() {
            var notaId = $(this).closest('.divNote').attr('id');
            var idNote = $('#' + notaId).find('.idn');
            $.ajax({
                url: 'ajax/ajax_condition.php',
                type: 'POST',
                data: { idNote: idNote.val() },
                success: function(response) {
                    // Manejar la respuesta del servidor
                    $('#' + notaId).remove();
                }
            });
        });

        // Agregar producto nuevo
        $('#agregar-prod').click(function() {
            var ref = $('#search_list_prod').val();
            var id = $('#list_prod').val();
            if(id!=''){
                num_prod ++;
                var nuevoProd = $(
                '<div class="divProd" id="div'+num_prod+'"><input class="product-input prod_id" type="hidden" name="prod_id[]" value="'+id+'"/>'+
                '<input readOnly="true" class="product-input" type="text" name="ref[]" value="'+ref+'"/>'+
                '<span class="msgError"><a class="eliminarDivProd"><img class="img-delete-item" src="/cashdesk/img/delete_item.png"></a></span></div>');
                $('#contenedor-prod').append(nuevoProd);
            }
        });

        // Eliminar producto existente en la tabla llx_condition_product
        $('.eliminarDivProd').click(function() {
            var divId = $(this).closest('.divProd').attr('id');
            var idProd = $('#' + divId).find('.prod_id');
            var idCondition = $('#idN').val();
            $.ajax({
                url: 'ajax/ajax_condition.php',
                type: 'POST',
                data: { idProd: idProd.val(), idCondition_edit: idCondition },
                success: function(response) {
                    // Manejar la respuesta del servidor
                    $('#' + divId).remove();
                }
            });
        });
    });
</script>

