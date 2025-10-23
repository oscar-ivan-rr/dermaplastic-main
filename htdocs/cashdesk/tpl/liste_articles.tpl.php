<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

// Protection to avoid direct call of template
if (empty($langs) || !is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array("main", "bills", "cashdesk"));

?>

<div class="liste_articles_haut">

<div class="liste_articles_bas">
<?php 
if($_SESSION['condition_id']){
    print '<div class="divNotes" style="display:none;">';
    print '<p class="titre">Notas predeterminadas</p>';
    // Obtener notas de padecimiento
    $sql="SELECT rowid, note FROM llx_condition_note WHERE fk_condition = '".$_SESSION['condition_id']."'";
    $resql = $db->query($sql);
    if(!empty($resql)){
        $i = 0;
        print '<div style="height: 180px; width: 100%; overflow-y: auto; " name="scrollp" id="scrollp">';
        while($obj = $db->fetch_object($resql)){
            print '<div class="divNote inline-block">';
            print '<p  class="note-group" type="text" data-textarea="note'.$i.'" data-text="'.$obj->note .'">' . $obj->note . '</p>';
            print '</div>';
            $i++;
        }
        print '</div>';
    }
    print '</div>';
}
?>
<p class="titre"><?php echo $langs->trans("Productos añadidos"); ?></p>

<?php
/** add Ditto for MultiPrix*/
$thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];
$societe = new Societe($db);
$societe->fetch($thirdpartyid);
/** end add Ditto */

$tab = (!empty($_SESSION['poscart']) ? $_SESSION['poscart'] : array());



$tab_size = count($tab);
if ($tab_size <= 0) print '<div class="center texte3">'.$langs->trans("NoArticle").'</div><br>';
else
{
    print '<div style="height: 250px; width: 100%; overflow-y: auto; " name="scrollp" id="scrollp">';
    for ($i = 0; $i < $tab_size; $i++)
    {
        echo ('<div class="cadre_article">'."\n");
        print '<div class="inline-block article">';
        echo ('<p class="texte4">'.$tab[$i]['ref'].'</p>'."\n");
        if ($tab[$i]['remise_percent'] > 0) {
            $remise_percent = ' -'.$tab[$i]['remise_percent'].'%';
        } else {
            $remise_percent = '';
        }

        $remise = $tab[$i]['remise'];

        echo ('<p class="texte5">'.$tab[$i]['qte'].' x '.price2num($tab[$i]['price'], 'MT').$remise_percent.' = '.price(price2num($tab[$i]['total_ht'], 'MT'), 0, $langs, 0, 0, -1, $conf->currency).' '.$langs->trans("HT").' ('.price(price2num($tab[$i]['total_ttc'], 'MT'), 0, $langs, 0, 0, -1, $conf->currency).' '.$langs->trans("TTC").')</p>'."\n");
        if($tab[$i]['note']!='') print '<textarea readOnly="true" id="saved_formnote'.$i.'" class="textnote">'.$tab[$i]['note'].'</textarea>';
        print '</div>';
        print '<div class="inline-block notearticle">';
        print '<a href="facturation_verif.php?action=suppr_article&suppr_id='.$tab[$i]['id'].'" title="'.$langs->trans("DeleteArticle").'"><img class="noteimg" src="/cashdesk/img/eliminar.png"></a>';
        print '<a data-idc="formnote'.$i.'" id="add'.$i.'" class="clickn" href="#" title="Haga clic para agregar nota"><img class="noteimg" src="/cashdesk/img/nota.png"></a>';
        print '</div>';
        print '<div class="inline-block notearticle2">';
        print '<form method="post" class="formnote" style="display:none;" name="formnote" id="formnote' . $i. '" action="facturation_verif.php?action=add_note_article">';
        print '<input type="hidden" name="id" value="'.$tab[$i]['id'].'">';
        print '<textarea type="text" class="inline-block textnote txtnewnote" name="note" id="new_formnote'.$i.'" value="'.$tab[$i]['note'].'">'.$tab[$i]['note'].'</textarea>';
        print '<input class="button inline-block btnote" type="submit" name="addnote" id="addnote" value="Guardar">';
        print '</form>';
        print '</div>';
        
        echo ('</div>'."\n");
        
    }
    print '</div>';
}

echo ('<p class="cadre_prix_total">'.$langs->trans("Total").' : '.price(price2num($total_ttc, 'MT'), 0, $langs, 0, 0, -1, $conf->currency).'<br></p>'."\n");

?></div>
</div>

<br>
<div class="additionalNote">
<p class="titre">Adicionales</p>
<button class="button btnNoteAdditional" id="editAdditional">Editar</button>
<button class="button btnSaveAdditional" id="saveAdditional">Guardar</button>

<!-- Área para agregar productos no incluidos en la base de datos o notas adicionales -->
<div class="divTxtAdd center">
<textarea class="txtAdd inline-block" readonly="true"><?php print $_SESSION['additional_products'];?></textarea>
</div>
<?php 
if($_SESSION['condition_id']){
    print '<div class="divNotes2">';
    // Obtener notas de padecimiento
    $sql2="SELECT rowid, note FROM llx_condition_note WHERE fk_condition = '".$_SESSION['condition_id']."'";
    $resql2 = $db->query($sql2);
    if(!empty($resql2)){
        $i = 0;
        print '<div style="height: 180px; width: 100%; overflow-y: auto; " name="scrollp" id="scrollp">';
        while($obj2 = $db->fetch_object($resql2)){
            print '<div class="divNote inline-block">';
            print '<p  class="note-extra" type="text" data-textarea="note'.$i.'" data-text2="'.$obj2->note .'">' . $obj2->note . '</p>';
            print '</div>';
            $i++;
        }
        print '</div>';
    }
    print '</div>';
}
?>
</div>




<script language="javascript">
    $(document).ready(function() {
        // Mostrar el form para editar la nota del producto
        $('.clickn').click(function(event) {
            event.preventDefault()
            $('.txtAdd').prop('readonly', true);
            $('#editAdditional').show();
            $('#saveAdditional').hide();
            $('.divNotes2').hide();
            $('.divNotes').show();
            $('.txtnewnote').val('');
            var formId = $(this).data('idc');
            var getNote = $('#saved_'+formId).val();
            $('.formnote').hide(); // Oculta todos los formularios
            $('#' + formId).show(); // Muestra el formulario seleccionado
            $('#new_'+formId).val(getNote);
            $('.txtnewnote').val(getNote)
        });

        // Agregar texto de la nota seleccionada al texto de la nota del input activo
        $('.note-group').click(function() {
            var formId = $('.formnote:visible').attr('id')
            var textareaContent = $('#'+formId).find('.txtnewnote').val();
            var paragraphValue = $(this).data('text');
            if(textareaContent == ''){
                $('.txtnewnote').val(textareaContent +paragraphValue);
            } else {
                $('.txtnewnote').val(textareaContent + '\n'+paragraphValue);
            }
        });

        // Agregar texto de la nota seleccionada al texto del campo para productos adicionales
        $('.note-extra').click(function() {
            $('.formnote').hide();
            var textareaContent = $('.txtAdd').val();
            var paragraphValue = $(this).data('text2');
            if(textareaContent == ''){
                $('.txtAdd').val(textareaContent +paragraphValue);
            } else {
                $('.txtAdd').val(textareaContent + '\n'+paragraphValue);
            }
        });

        // Habilitar textarea para editar su valor
        $('#editAdditional').click(function(){
            $('.formnote').hide();
            $('.divNotes').hide();
            $('.divNotes2').show();
            $('#saveAdditional').show();
            $('.txtAdd').prop('readonly', false);
            $(this).hide();

        });

        // Guardar valor del textarea de productos adicionales
        $('#saveAdditional').click(function(){
            $.ajax({
                url: 'ajax/ajax_condition.php',
                type: 'POST',
                data: {
                    additional_products: $('.txtAdd').val()
                },
                success: function(response) {
                    $('#saveAdditional').hide();
                    $('.divNotes2').hide();
                    $('#editAdditional').show();
                    $('.txtAdd').prop('readonly', true);
                    $(this).hide();
                },
		    });
        });
    });
</script>
