<script>
  $(document).ready(function(){
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
    global $db, $conf;

    /*
     * Actions
     */

    if(GETPOST('action')=='add'){
    	$ret=GETPOST('cod');
    	$descripcion=GETPOST('descripcion');
    	$tasa=GETPOST('tasa');
    	$sql="SELECT rowid, cod,descripcion,tasa FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales WHERE cod='".$ret."' AND entity=".$conf->entity ;
    	//print $sql."<br>";
    	$rqes=$db->query($sql);
    	$nrow=$db->num_rows($rqes);
        $val = 0;

    	if($nrow==0){
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales(cod,descripcion,tasa,entity) VALUES('".$ret."','".$descripcion."','".$tasa."','".$conf->entity."')";
    		//print $sql."<br>";
    		$rqes = $db->query($sql);

            if($rqes){
                $val = 1;
            }else{
                $val = 0;
            }
    	}else{
            $val = 2;
        }

    	print "<script>window.location.href='cfdimx.php?mod=retenciones&val=".$val."'</script>";
    }

    if(GETPOST('action')=='del'){
    	$idi = GETPOST('id');
    	$sql = "DELETE FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales WHERE rowid=".$idi;
    	//print $sql."<br>";
    	$rqes = $db->query($sql);
        $eliminar_ret = 0;
        if($rqes){
            $eliminar_ret = 1;
        }else{
            $eliminar_ret = 0;
        }
    }

    /*
     * View
     */

    if(GETPOST("val") != ""){
        if(GETPOST("val") == 1){
            setEventMessage('Retención agregada correctamente.');
        }else{
            if(GETPOST("val") == 0){
                setEventMessage('Error al guardar la Retención.', 'errors');
            }else{
                if(GETPOST("val") == 2){
                    setEventMessage('Error al agregar la Retención porque ya existe una previa.', 'errors');
                }
            }
        }
    }

    if($eliminar_ret != ""){
        if($eliminar_ret == 1){
            setEventMessage('Se elimino correctamente la Retención seleccionada.');
        }else{
            if($eliminar_ret == 0){
                setEventMessage('Error al eliminar la Retención seleccionada.', 'errors');
            }
        }
    }

    print '<div class="div-table-responsive-no-min">';
    	print '<form method="POST" action="cfdimx.php">';
            print '<input type="hidden" name="mod" id="mod" value="retenciones">';
            print '<input type="hidden" name="action" id="action" value="add">';
            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
            print '<table class="noborder" width="100%">';

                print '<tr class="liste_titre">';
                    print '<th><strong>Código</strong></th>';
                    print '<th><strong>Etiqueta</strong></th>';
                    print '<th><strong>Tasa (%)</strong></th>';
                    print '<th>&nbsp;</th>';
                print '</tr>';

                print '<tr>';
                    print '<td >';
                        print '<input type="text" name="cod" id="cod">';
                    print '</td>';
                    print '<td>';
                        print '<input type="text" name="descripcion" id="descripcion" size="40">';
                    print '</td>';
                    print '<td>';
                        print '<input type="text" name="tasa" id="tasa">';
                    print '</td>';
                    print '<td  class="center">';
                        print '<input type="submit" class="butAction" name="actionadd" value="Añadir">';
                    print '</td>';
                print '</tr>';

            print '</table>';
        print '</form>';
    print '</div>';
    print '<br><br>';

    $out_liste = "";
    $sql="SELECT rowid, cod,descripcion,tasa FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales WHERE entity=".$conf->entity." ORDER BY rowid";
    //print $sql;
    $rqs=$db->query($sql);

    while($rs=$db->fetch_object($rqs)){
    	$out_liste .= '<tr>';
    		$out_liste .= '<td align="left">'.$rs->cod.'</td>';
    		$out_liste .= '<td align="left">'.$rs->descripcion.'</td>';
    		$out_liste .= '<td align="center">'.$rs->tasa.'</td>';
    		$out_liste .= '<td align="center" width="10%"><a href="cfdimx.php?mod=retenciones&action=del&id='.$rs->rowid.'">'.img_delete().'</a></td>';
    	$out_liste .= '</tr>';
    }

    if($out_liste != ""){
        print '<div class="div-table-responsive">';
            print '<table class="noborder" width="100%">';

                print '<tr class="liste_titre">';
                    print '<th align="left"><strong>Código</strong></th>';
                    print '<th align="left"><strong>Etiqueta</strong></th>';
                    print '<th align="center"><strong>Tasa (%)</strong></th>';
                    print '<th align="center">&nbsp;</th>';
                print '</tr>';

                print $out_liste;

            print '</table>';
        print '</div>';
    }
?>