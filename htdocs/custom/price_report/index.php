<?php
/**
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */

require '../../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action = GETPOST('action', 'alpha');
$type = GETPOST('select_export');

// Desde - Hasta
$date_start_creationday		=GETPOST("date_start_creationday");
$date_start_creationmonth	=GETPOST("date_start_creationmonth");
$date_start_creationyear	=GETPOST("date_start_creationyear");
$date_end_creationmonth		=GETPOST("date_end_creationmonth");
$date_end_creationday		=GETPOST("date_end_creationday");
$date_end_creationyear		=GETPOST("date_end_creationyear");

// Security check
if ($search_type == '0') $result = restrictedArea($user, 'produit', '', '', '', '', '', $objcanvas);
elseif ($search_type == '1') $result = restrictedArea($user, 'service', '', '', '', '', '', $objcanvas);
else $result = restrictedArea($user, 'produit|service', '', '', '', '', '', $objcanvas);

/*
 * Actions
 */
if ($action == 'confirm_export') {
    include_once './exportPrice.php';
}

/*
 * View
 */

$icon = 'products';
$title = 'Reporte de Precios';
$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';

llxHeader("", $langs->trans($title), $helpurl);
print load_fiche_titre($title, '', $icon);

print '<div class="fichecenter">';
    print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
            print '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Export") . '</th></tr>';
            print '<tr><td class="center" colspan="2">';
                print '<form action="' . $_SERVER["PHP_SELF"] . '?element='.$element.'&type='.$type.'" method="POST">';
                    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    print '<input type="hidden" name="action" value="confirm_export">';
                    print '<select id="select_export" name="select_export">';
                        print '<option value="1">Precio de Compra Mayor al Precio de Venta</option>';
                        print '<option value="2">Precio de Compra igual a 0 con Stock Mayor a 1</option>';
                        print '<option id="prod_regalados" value="4">Precio de Compra menor a $0.10( Productos Regalados)</option>';
                        print '<option value="3">Precio de Venta igual a 0</option>';
                    print '</select>';
                    print '<br><br><div class="center">';
                    print '<div id="fechas" style="display : none;">';
                    print "Desde<br>".$form->select_date($date_start_creation,'date_start_creation',0,0,0,'',1,0,1);
                    print "<br>Hasta<br>".$form->select_date($date_end_creation,'date_end_creation',0,0,0,'',1,0,1);
                    print '</div>'; 
                        print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('Export') . '">';
                    print '</div>';
                print '</form>';
         print '</td></tr>';
        print '</table>';
    print '</div>';
print '</div>';

// End of page
llxFooter();
$db->close();

?>

<script>
    $( document ).ready(function() {
        select = document.getElementById("select_export");
        fechas = document.getElementById("fechas");

        select.addEventListener("change", (event) => {
            if(select.value == 4){
               fechas.style.display = "block";
            }else{
               fechas.style.display = "none";
            }
        });
    });
</script>
