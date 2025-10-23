<?php 
require '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","cashdesk"));

/*
 * View
 */

$form=new Form($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('', '', 0, 0, '', $arrayofcss);

$action     = GETPOST('action', 'aZ09');
$name       = GETPOST('name');
$doctor     = GETPOST('doctor');
$email      = GETPOST('email');
$phone      = GETPOST('phone');
$discount   = GETPOST('disc');
$type       = GETPOST('type');
$category   = GETPOST('category');
$dob        =GETPOST('dob');
?>

    <link rel="stylesheet" href="/cashdesk/css/style.css">
    <link rel="stylesheet" href="/cashdesk/css/colorbox.css" type="text/css" media="screen" />
    <script type="text/javascript" src="/cashdesk/javascript/jquery.colorbox-min.js"></script>	<!-- TODO It seems we don't need this -->

<?php
print '<h4 class="titre1">Nuevo paciente</h4>';

print '<div class="center contenu">';
print '<div class="cadre_search">';
print '<form class="formadd" id="form2" name="form2" method="POST" action="newpatient.php">';
print '<input type="hidden" name="doctor" value="'.$user->id.'">';
print '<input type="hidden" name="type" value="1">';
print '<input type="hidden" name="category" value="3">';
print '<input type="hidden" name="action" value="add">';
print '<table class="tableadd">';
print '<tr>';
print '<td><b>Nombre: </b></td>';
print '<td><input class="datapatient" type="text" name="name" /></td>';
print '</tr>';
print '<tr>';
print '<td><b>Teléfono: </b></td>';
print '<td><input class="datapatient" type="phone" name="phone" /></td>';
print '</tr>';
print '<tr>';
print '<td><b>Fecha de nacimiento: </b></td>';
print '<td><input class="datapatient_off" type="date" name="dob" /></td>';
print '</tr>';
print '<tr>';
print '<td>Correo electrónico: </td>';
print '<td><input class="datapatient" type="email" name="email" /></td>';
print '</tr>';
print '<tr>';
print '<td>Descuento por defecto (%): </td>';
print '<td><input readOnly="true" class="datapatient_off" type="text" name="disc" value="10"/></td>';
print '</tr>';
print '<tr>';
print '<td>Médico: </td>';
print '<td><input readOnly="true" class="datapatient" type="text" value="'.$_SESSION['firstname'].' '.$_SESSION['lastname'].'" /></td>';
print '</tr>';
print '</table>';
print '<input type="submit" class="button btnmenu" onclick="closev();" value="Agregar">';
print '</form>';
print '<br>';
print '</div>';
print '</div>';

if($action == 'add'){

    if(empty(trim($name)) || empty(trim($phone)) || empty($dob)){
        print '<p class="txtrequired"> Hay campos obligatorios vacíos </p>';
    } else{
        $object = new Societe($db);
        $object -> name = $name;
        $object -> email = $email;
        $object -> phone = $phone;
        $object -> remise_percent = $discount;
        $object -> commercial_id = $doctor;
        $object -> country_id = $mysoc->country_id;
        $object -> client = $type;
        $object -> code_client = -1;
        $object -> create($user);
        $object -> set_remise_client(GETPOST('disc'), 'Primera visita', $user);
        $object -> setCategories($category, 'customer');

        // Agregar fecha de nacimiento en el campo que se agregó desde configuración
        $sql = "INSERT INTO llx_societe_extrafields
        (fk_object, import_key, formpagcfdi, usocfdi, fecha_nacimiento)
        VALUES('".$object->id."', NULL, NULL, NULL, '".$dob."')";
        $resql = $db->query($sql);

        $_SESSION["CASHDESK_ID_THIRDPARTY"] = $object -> id;
        print '<p class="txtsave"> Paciente agregado correctamente <br> &#10004; </p>';
        print '<script language="javascript">
            setTimeout(function(){
                parent.location.reload();
            },100);
        </script>';
        
    }
}

print '</tbody>';
print '</table>';
print '</div>';
print '</div>';

?>
