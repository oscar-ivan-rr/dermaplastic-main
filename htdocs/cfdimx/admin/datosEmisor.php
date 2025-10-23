<script>
  $(document).ready(function(){
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
    if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

    $form        = new Form($db);
    $obj_conf    = new ConfiguracionCFDI($db);
    $obj_conf->getEmisor();
    $formcompany = new FormCompany($db);

    $action = GETPOST('action');

    $langs->load('cfdimx');

    /* ************************************************************************** */
    /*                                                                            */
    /* Actions                                                                    */
    /*                                                                            */
    /* ************************************************************************** */

    if ($action == "guardar") {
		$estado = explode(" - ", $obj_conf->datos_emisor["MAIN_INFO_SOCIETE_STATE_L"])[0];
        $direccion_int = GETPOST('noint') ? " INT. " . GETPOST('noint') : "";
        $direccion = GETPOST('calle') . " NO. " . GETPOST('noext') . $direccion_int . " COL. " . GETPOST('colonia') . ", " . GETPOST('delmpio') . ", " . $estado;
        dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", $direccion, 'chaine', 1, '', $conf->entity);

        $rowid_reg_emisor = $obj_conf->validarEmisor();
        $emisor = array(
                        "rfc"            => $_REQUEST['rfc'],
                        "regimen_fiscal" => $_REQUEST['regimen_fiscal'],
                        "razon_social"   => $_REQUEST['razon_social'],
                        "direccion"      => $direccion,
                        "huso_horario"   => $_REQUEST['huso_horario'],
                        "rowid"          => $rowid_reg_emisor,
                        "delmpio"        => $_REQUEST['delmpio'],
                        "clave_mpio"     => $_REQUEST['clave_mpio'],
                        "colonia"        => $_REQUEST['colonia'],
                        "clave_col"      => $_REQUEST['clave_col'],
                        "calle"          => $_REQUEST['calle'],
                        "noext"          => $_REQUEST['noext'],
                        "noint"          => $_REQUEST['noint']
                    );

        $obj_conf->datos_emisor = $emisor;

        if($rowid_reg_emisor == 0){
            #Se almacena el registro del Emisor
            $registro = $obj_conf->registarEmisor();

            if($registro == 1){
                $obj_conf->getEmisor();
                setEventMessage('Datos del Emisor registrados correctamente.');
            }else{
                if($registro == 0){
                    setEventMessage('Error al insertar los datos del Emisor.', 'errors');
                }
            }
        }else{
            #Se actualiza el registro del Emisor
            $actualizar = $obj_conf->actualizarEmisor();

            if($actualizar == 1){
                $obj_conf->getEmisor();
                setEventMessage('Datos del Emisor actualizados correctamente.');
            }else{
                if($actualizar == 0){
                    setEventMessage('Error al actualizar los datos del Emisor.', 'errors');
                }
            }
        }
    }

    /* ************************************************************************** */
    /*                                                                            */
    /* View                                                                       */
    /*                                                                            */
    /* ************************************************************************** */

    if(GETPOST("update") != ""){
        if(GETPOST("update") == 1){
            setEventMessage('Datos del Emisor actualizados correctamente.');
        }else{
            if(GETPOST("update") == 0){
                setEventMessage('Error al actualizar los datos.', 'errors');
            }
        }
    }

    print '<div class="div-table-responsive">';
        print '<form method="post" name="comp_dataemisor" action="'.$_SERVER['PHP_SELF'].'">';
            print '<input type="hidden" name="action" value="guardar">';
            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
            print '<table class="noborder" width="100%">';
                print '<tbody>';
                    print "<tr class='liste_titre'>";
                        print '<th colspan="2">';
                            print img_picto('', 'building', 'class="pictofixedwidth"').'&nbsp;';
                            print '<strong>Información Fiscal</strong>';
                        print '</th>';
                    print '</tr>';

                    print '<tr>';
                        print '<td class="fieldrequired">RFC</td>';
                        print '<td>';
                            print '<input name="rfc" id="rfc" value="'.$obj_conf->datos_emisor["MAIN_INFO_SIREN"].'" placeholder="XAXX010101000">';

                            if($obj_conf->datos_emisor["MAIN_INFO_SIREN"] == ""){
                                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                print '<div class="inline-block divButAction">';
                                    print '<div class="error hideonsmartphone clearboth">';
                                        print img_error('Campo requerido').'&nbsp;';
                                        print '<strong>Campo requerido.</strong>';
                                    print '</div>';
                                print '</div>';
                            }
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td class="fieldrequired">Régimen Fiscal</td>';
                        print '<td>';
                            print $obj_conf->obtener_catalogo($obj_conf->datos_emisor["CFDIMX_REGIMEN_FISCAL"],'regimen_fiscal',2);
                            if($obj_conf->datos_emisor["CFDIMX_REGIMEN_FISCAL"] == "" || $obj_conf->datos_emisor["CFDIMX_REGIMEN_FISCAL"] <= 0){
                                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                print '<div class="inline-block divButAction">';
                                    print '<div class="error hideonsmartphone clearboth">';
                                        print img_error('Campo requerido').'&nbsp;';
                                        print '<strong>Campo requerido.</strong>';
                                    print '</div>';
                                print '</div>';
                            }
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td class="fieldrequired">Razón Social</td>';
                        print '<td>';
                            $ayuda = "Ingresa el Nombre de la Razón Social sin incorporar el régimen de capital.\nEjemplo CFDI 3.3: Auribox Consulting SA de CV\nEjemplo CFDI 4.0: Auribox Consulting";

                            print '<textarea id="razon_social" name="razon_social" rows="4" cols="120" placeholder="'.$ayuda.'">';
                                print ($obj_conf->datos_emisor["CFDIMX_RAZON_SOCIAL"] != "" ? $obj_conf->datos_emisor["CFDIMX_RAZON_SOCIAL"] : '');
                            print '</textarea>';

                            if($obj_conf->datos_emisor["CFDIMX_RAZON_SOCIAL"] == ""){
                                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                print '<div class="inline-block divButAction">';
                                    print '<div class="error hideonsmartphone clearboth">';
                                        print img_error('Campo requerido').'&nbsp;';
                                        print '<strong>Campo requerido.</strong>';
                                    print '</div>';
                                print '</div>';
                            }
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Dirección</td>';
                        print '<td>';
                            $titulo = "Este valor es el que se muestra en el PDF en el apartado del Emisor.";
                            print '<textarea id="direccion" name="direccion" rows="4" cols="120" title="'.$titulo.'">';
                                print $obj_conf->datos_emisor["CFDIMX_DIRECCION"];
                            print '</textarea>';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td class="fieldrequired">País</td>';
                        print '<td>';
                            print img_picto('', 'country', 'class="pictofixedwidth"').'&nbsp;';
                            print $obj_conf->datos_emisor["MAIN_INFO_SOCIETE_COUNTRY_L"].' '.$form->textwithpicto('', "Nota: Para modificar este campo es necesario ingresar al área de ".$langs->trans("RutaCambioValores")." de dolibarr en el campo: País.", 1, 'help', '', 0, 3);

                            if($obj_conf->datos_emisor["MAIN_INFO_SOCIETE_COUNTRY_L"] == ""){
                                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                print '<div class="inline-block divButAction">';
                                    print '<div class="error hideonsmartphone clearboth">';
                                        print img_error('Campo requerido').'&nbsp;';
                                        print '<strong>Campo requerido.</strong>';
                                    print '</div>';
                                print '</div>';
                            }
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Estado</td>';
                        print '<td>';
                            print img_picto('', 'state', 'class="pictofixedwidth"').'&nbsp;';
                            print $obj_conf->datos_emisor["MAIN_INFO_SOCIETE_STATE_L"].' '.$form->textwithpicto('', "Nota: Para modificar este campo es necesario ingresar al área de ".$langs->trans("RutaCambioValores")." de dolibarr en el campo: Estado.", 1, 'help', '', 0, 3);
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td class="fieldrequired">Código Postal</td>';
                        print '<td>';
                            print $obj_conf->datos_emisor["MAIN_INFO_SOCIETE_ZIP"].' '.$form->textwithpicto('', "Nota: Para modificar este campo es necesario ingresar al área de ".$langs->trans("RutaCambioValores")." de dolibarr en el campo: Código postal.", 1, 'help', '', 0, 3);

                            if($obj_conf->datos_emisor["MAIN_INFO_SOCIETE_ZIP"] == ""){
                                print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                print '<div class="inline-block divButAction">';
                                    print '<div class="error hideonsmartphone clearboth">';
                                        print img_error('Campo requerido').'&nbsp;';
                                        print '<strong>Campo requerido.</strong>';
                                    print '</div>';
                                print '</div>';
                            }
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Delegacion o Municipio</td>';
                        print '<td>';
                            print '<input name="delmpio" id="delmpio" size="60" value="'.$obj_conf->datos_emisor["delmpio"].'">';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Código del Municipio</td>';
                        print '<td><input name="clave_mpio" id="clave_mpio" size="60" value="'.$obj_conf->datos_emisor["clave_mpio"].'"></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Colonia</td>';
                        print '<td>';
                            print '<input name="colonia" id="colonia" size="60" value="'.$obj_conf->datos_emisor["colonia"].'">';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Código de la Colonia</td>';
                        print '<td><input name="clave_col" id="clave_col" size="60" value="'.$obj_conf->datos_emisor["clave_col"].'"></td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>Calle</td>';
                        print '<td>';
                            print '<input name="calle" id="calle" size="60" value="'.$obj_conf->datos_emisor["calle"].'">';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>No. Exterior</td>';
                        print '<td>';
                            print '<input name="noext" id="noext" size="20" value="'.$obj_conf->datos_emisor["noext"].'">';
                        print '</td>';
                    print '</tr>';

                    print '<tr>';
                        print '<td>No. Interior</td>';
                        print '<td>';
                            print '<input name="noint" id="noint" size="20" value="'.$obj_conf->datos_emisor["noint"].'">';
                        print '</td>';
                    print '</tr>';

                    print  '<tr>';
                        print '<td class="fieldrequired">Huso horario</td>';
                        print '<td>';
                            print $obj_conf->obtener_catalogo($obj_conf->datos_emisor["CFDIMX_HUSO_HORARIO"],'huso_horario',3);

                            if($obj_conf->datos_emisor["CFDIMX_HUSO_HORARIO"] != ""){
                                if(is_numeric($obj_conf->datos_emisor["CFDIMX_HUSO_HORARIO"])){
                                    if($obj_conf->datos_emisor["CFDIMX_HUSO_HORARIO"] <= 0){
                                        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                        print '<div class="inline-block divButAction">';
                                            print '<div class="error hideonsmartphone clearboth">';
                                                print img_error('Campo requerido').'&nbsp;';
                                                print '<strong>Campo requerido.</strong>';
                                            print '</div>';
                                        print '</div>';
                                    }
                                }
                            }
                        print  '</td>';
                    print '</tr>';

                print '</tbody>';
            print '</table>';

            print '<div align="center" style="margin-top: 10px;">';
                print '<input type="submit" class="butAction" name="save" value="Guardar">';
            print '</div>';

            print '<br>';
        print '</form>';
    print '</div>';
?>
