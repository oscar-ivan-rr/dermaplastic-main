<script>
  $(document).ready(function(){
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
    // error_reporting(-1);

    global $db, $conf;

    $action       = GETPOST('action');
    $catalogo_sel = GETPOST('catalogo_sel');

    /*
     * Actions
     */

    if($action == 'save'){
        $codigos = $_REQUEST['codigo'];
        $etiquetas = $_REQUEST['etiqueta'];
        $estatus = $_REQUEST['estatus'];
        $clave_transporte = $_REQUEST['clave_t'];
        $tabla = $_REQUEST['catalogo_sel_view'];
        $catalogo_extra = $_REQUEST['cnt'];
        $validaciones = null;
        $codigos_validos = 0;

        if(count($codigos) > 0 && count($etiquetas) > 0 && count($estatus) > 0){
            for ($i=0; $i < count($codigos); $i++) {
                if((int)$estatus[$i] != 2 && trim($codigos[$i]) != ''){
                    //Se valida si existe o no la clave en el diccionario seleccionado
                    $sql_validar     = "SELECT count(*) AS validacion FROM ".MAIN_DB_PREFIX."".$tabla." WHERE code = '".$codigos[$i]."';";
                    $res_sql_validar = $db->query($sql_validar);
                    $num_sql_validar = $db->num_rows($res_sql_validar);
                    // print $num_sql_validar.'<br>';
                    // print $sql_validar.'<br>';
                    if($num_sql_validar){
                        $obj_codigo      = $db->fetch_object($res_sql_validar);

                        $label           = ($etiquetas[$i] != '' ? $etiquetas[$i] : $codigos[$i]);

                        if($obj_codigo->validacion == 0){
                            $sql_catalogo = '';
                            $sql_catalogo .= "INSERT INTO ".MAIN_DB_PREFIX."".$tabla;

                            if($catalogo_extra == 3){
                                $sql_catalogo .= " (code,label,active)";
                            }else{
                                $sql_catalogo .= " (code,label,active,clave_t)";
                            }

                            $sql_catalogo .= " VALUES";
                            $sql_catalogo .= " (";
                                $sql_catalogo .= "'".$codigos[$i]."'," ;
                                $sql_catalogo .= "'".$label."',";

                                if($catalogo_extra == 3){
                                    $sql_catalogo .= "'".$estatus[$i]."'";
                                }else{
                                    $sql_catalogo .= "'".$estatus[$i]."',";
                                    $sql_catalogo .= "'".$clave_transporte[$i]."'";
                                }
                            $sql_catalogo .= " );";

                            $res_sql_catalogo = $db->query($sql_catalogo);
                            // print $sql_catalogo.'<br>';
                            $codigos_validos++;
                        }else{
                            $validaciones[] = $codigos[$i]." - ".$label;
                        }
                    }
                }
            }
        }
    }

    function obtener_catalogo($selected='', $htmlname='', $tipo_catalogo='', $tipo_informacion = 0, $tipo_sql = '', $tipo_select = 0, $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

        global $conf,$user,$langs,$db;

        // If no preselected user defined, we take current user
        if ((is_numeric($selected) && ($selected < -2 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;
        $excludeUsers=null;
        $includeUsers=null;
        // Permettre l’exclusion d’utilisateurs
        if (is_array($exclude)) $excludeUsers = implode("','",$exclude);
        // Permettre l’inclusion d’utilisateurs
        if (is_array($include)) $includeUsers = implode("','",$include);
        else if ($include == 'hierarchy')
        {
            // Build list includeUsers to have only hierarchy
            $userid=$user->id;
            $include=array();
            if (empty($user->users) || ! is_array($user->users)) $user->get_full_tree();
            foreach($user->users as $key => $val)
            {
                if (preg_match('/'.$userid.'/',$val['fullpath'])) $include[]=$val['id'];
            }
            $includeUsers = implode("','",$include);
        }
        $out='';
        switch ($tipo_catalogo) {
            case 1:
                $sql   = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_catalogos WHERE active = 1";
                break;
            case 2:
                $sql   = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_catalogos WHERE code = '".$selected."'";
                break;

            default:
                // code...
                break;
        }

        $resql = $db->query($sql);

        if ($resql){
            $num = $db->num_rows($resql);
            $i = 0;
            $etiqueta = "";
            if ($num)
            {
                // Enhance with select2
                $nodatarole='';
                if ($conf->use_javascript_ajax)
                {
                    include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
                    $comboenhancement = ajax_combobox($htmlname);
                    $out.=$comboenhancement;
                    $nodatarole=($comboenhancement?' data-role="none"':'');
                }

                $inicia_select = '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.' required="true">';

                $out .= $inicia_select;

                if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
                if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";

                // if($tipo_catalogo >= 2000 && $tipo_catalogo <= 2999){
                // }else{
                    $out.= '<option value="">&nbsp;</option>';
                // }

                $i=0;
                while ($rw = $db->fetch_object($resql)) {
                    $valor      = $rw->code;
                    $etiqueta   = $rw->label;
                    $validacion = $rw->code;

                    // if($tipo_catalogo == 4){
                    //  print 'selected :: '.$selected.'<br>';
                    //  print 'valor :: '.$valor.'<br>';
                    // }

                    if ($selected == $validacion) {
                        $out.= '<option value="'.$valor.'" selected>';

                        if($tipo_informacion == 1)
                            break;
                    }else{
                        $out.= '<option value="'.$valor.'">';
                    }
                    $out.= $etiqueta."</option>";
                }
            }else{
                $out.= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'">';
                $out.= '<option value="">'.$langs->trans("None").'</option>';
            }
            $out.= '</select>';

            if($tipo_informacion == 1)
                $out = $etiqueta;
        }else{
            dol_print_error($db);
        }

        return $out;
    }

    /*
     * View
     */

    if ($action == '' || $action == 'view' || $action == 'save') {
        print '<form method="POST" action="cfdimx.php?mod=cargamasivaclaves&action=view" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
            print '<table style="width: 100% !important;" class="noborder">';

                    print '<tr class="liste_titre">';
                        print "<td colspan='5'>";
                            print "<b>&nbsp;Carga Masiva de Claves SAT</b>";
                        print "</td>";
                    print '</tr>';


                print '<tr>';
                    print '<td>';
                        print '<b>Plantilla: </b>';
                        print '<input type="file" name="plantilla_sat" id="plantilla_sat" required accept=".xls,.xlsx">';
                    print '</td>';
                    print '<td>';
                        print '<b>Catálogo: </b>';
                        print obtener_catalogo($catalogo_sel, 'catalogo_sel',1);
                    print '</td>';
                print '</tr>';
            print '</table>';

            print '<table style="width: 100% !important;" class="">';
                print '<tr>';
                    print '<td colspan="2" align="center"><input class="butAction" type="submit" value="Visualizar"></td>';
                print '</tr>';

            print '</table>';
        print '</form>';

        print '<div class="info hideonsmartphone clearboth">';
            print '<span class="badge badge-status3 badge-status" title="Descargar Plantilla">';
                print '<span class="fa fa-file-excel-o valignmiddle btnTitle-icon"></span>';
                print '&nbsp;&nbsp;';
                print '<a href="plantilla_cargamasiva_claves_sat.xlsx" download style="text-decoration:none;">Descargar Plantilla</a>';
            print '</span>';
        print '</div>';

        if(isset($validaciones) && $action == 'save' || @$codigos_validos > 0){
            $color_mensaje    = 0;
            $msg_validaciones = '';

            if($codigos_validos > 0){
                $msg_validaciones .= "Se importo correctamente el archivo.";
            }

            if($validaciones != null && count($validaciones) > 0){
                $color_mensaje = 1;
                if($msg_validaciones != ''){
                    $msg_validaciones .= "<br>";
                }

                $msg_validaciones .= "Error<br>";
                $msg_validaciones .= "Las siguientes claves no se registraron porque ya existen en el catálogo <u>".obtener_catalogo($tabla, 'tabla',2,1).'</u><br><br>';
                foreach($validaciones as $error){
                    $msg_validaciones .= $error.'<br>';
                }
            }

            if($msg_validaciones != ''){
                if($color_mensaje == 1){
                    setEventMessage($msg_validaciones, 'errors');
                }
            }
        }
    }

    if($color_mensaje == 0){
        setEventMessage($msg_validaciones);
    }

    if($action == 'view'){
        $plantilla = PHPExcel_IOFactory::load($_FILES["plantilla_sat"]["tmp_name"]);

        print '<form method="POST" action="cfdimx.php?mod=cargamasivaclaves&action=save">';
            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
            print '<br><br>';
            print '<div class="info hideonsmartphone clearboth">';
                print 'Catálogo Seleccionado: <strong>'.obtener_catalogo($catalogo_sel, 'catalogo_view',2,1).'</strong>';
                print '<br><br><strong>Lista de Claves del archivo: </strong>'.$_FILES["plantilla_sat"]["name"];
            print '</div>';

            $val = (strcmp("c_cfdimx_estaciones", $catalogo_sel) == 0 ? '' : 'none');
            $cnt = (strcmp("c_cfdimx_estaciones", $catalogo_sel) == 0 ? 4 : 3);

            print '<input type="hidden" id="catalogo_sel_view" name="catalogo_sel_view" value='.$catalogo_sel.'>';
            print '<input type="hidden" id="cnt" name="cnt" value='.$cnt.'>';


            print '<table style="width: 100% !important;" class="noborder">';
                // print '<thead>';
                    print '<tr class="liste_titre">';
                        print '<th><strong>Código</strong></th>';
                        print '<th><strong>Etiqueta</strong></th>';
                        print '<th><strong>Estatus</strong></th>';
                        print '<th style="display: '.$val.'"><strong>Clave Transporte</strong></th>';
                    print '</tr>';
                // print '</thead>';                

                foreach($plantilla->getWorksheetIterator() as $worksheet){
                    $highestRow = $worksheet->getHighestRow();

                    $renglon = 0;
                    $i       = 0;
                    for($row=2; $row<=$highestRow; $row++){
                        $codigo   =  $worksheet->getCellByColumnAndRow(0, $row)->getValue();
                        $etiqueta =  $worksheet->getCellByColumnAndRow(1, $row)->getValue();
                        $estatus  =  $worksheet->getCellByColumnAndRow(2, $row)->getValue();
                        $clave_t  =  $worksheet->getCellByColumnAndRow(3, $row)->getValue();

                        if($codigo != "" && !is_null($codigo)){
                            print '<tr>';
                                print "<td><input type='text' name='codigo[]' id='codigo".$i."' value='".$codigo."'></td>";
                                print "<td>";
                                    print "<textarea name='etiqueta[]' id='etiqueta".$i."' rows='4' cols='60'>";
                                        print $etiqueta;
                                    print '</textarea>';
                                print "</td>";
                                print "<td><input type='text' name='estatus[]' id='estatus".$i."' value='".$estatus."'></td>";
                                print "<td style='display: ".$val."'><input type='text' name='clave_t[]' id='clave_t".$i."' value='".$clave_t."'></td>";
                            print '</tr>';

                            $i++;
                        }
                    }
                }

                // print '<thead>';
                    print '<tr>';
                        print '<td colspan="'.$cnt.'" align="center"><input class="butAction" type="submit" value="Importar"></td>';
                    print '</tr>';
                // print '</thead>';

            print '</table>';
        print '</form>';
    }
