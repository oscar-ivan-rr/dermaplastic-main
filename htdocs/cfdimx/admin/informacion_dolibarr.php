<script>
  $(document).ready(function(){
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>
<?php
    // error_reporting(-1);
    require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
    require_once(DOL_DOCUMENT_ROOT."/cfdimx/js/informacion_dolibarr.js.php");

    global $db, $conf;

    $action             = GETPOST('action');
    $catalogo_sel       = GETPOST('catalogo_sel');
    $nuevo_valor        = GETPOST('nuevo_valor');
    $nuevo_valor_noide  = GETPOST('nuevo_valor_noide');
    $ban_filtro         = GETPOST('ban_filtro');
    $rowid_prod         = GETPOST('rowid_prod');
    $rowid_prod_del     = GETPOST('rowid_prod_del');
    $proceso_terminado  = GETPOST('proceso');


    $ob_configuracion = new ConfiguracionCFDI($db);
    // $factura_c = new Facture($db);
    // print '<pre>'; print_r($factura_c->liste_array(2)); print '</pre>';

    ##Actions
    if($action == "update"){
        // print '<pre>';
        //     print_r($_REQUEST);
        // print '</pre>';

        $lista_errores = array();

        if($catalogo_sel > 0){
            $lista_claves[] = "claveprodserv";
            $lista_claves[] = "umed";
            $lista_claves[] = "noidenticfdi";
            $lista_claves[] = "objimp";

            ##
            $lista_aplicar_cambios = array();
            for($con = 0 ; $con < count($rowid_prod_del); $con++){
                if($rowid_prod_del[$con] == 0){
                    $lista_aplicar_cambios[] = $rowid_prod[$con];
                }
            }

            if($catalogo_sel != 3){
                if($nuevo_valor > 0){
                    $campo_db = $lista_claves[$catalogo_sel-1];

                    $sql_up = "";
                    $sql_up = "UPDATE ".MAIN_DB_PREFIX."product_extrafields";
                    $sql_up .= " SET";
                        $sql_up.= ' '.$campo_db.' = "'.$nuevo_valor.'"';

                    // print 'aux :: '.$lista_claves[1];
                    // print '<pre>'; print_r($lista_claves); print '</pre>';

                    switch ($ban_filtro) {
                        case 0:
                            # Actualizar Todos

                            // where
                            // fk_object

                            break;

                        case 1:
                            # Actualizar Todos Excepto
                            if(!is_null($lista_aplicar_cambios) && count($lista_aplicar_cambios) > 0){
                                $sql_up .= " WHERE fk_object NOT IN(".implode(",", $lista_aplicar_cambios).")";
                            }else{
                                $sql_up = "";
                                $lista_errores[] = "Error 1003: No se encontraron Productos para omitir en el proceso de Actualizar";
                            }
                            break;

                        case 2:
                            # Actualizar Solo los Seleccionados
                            if(!is_null($lista_aplicar_cambios) && count($lista_aplicar_cambios) > 0){
                                $sql_up .= " WHERE fk_object IN(".implode(",", $lista_aplicar_cambios).")";
                            }else{
                                $lista_errores[] = "Error 1002: No se encontraron Productos para el proceso de Actualizar";
                            }
                            break;
                    }

                    if($sql_up != ""){
                        // print $sql_up;
                        $res = $db->query($sql_up);
                        // ejecutar sql
                    }else{
                        $lista_errores[] = "Error 1001: La sentencia de actualización no se puedo crear correctamente.";
                    }
                }else{
                    $lista_errores[] = "Error 1000: El campo Nuevo Valor es Obligatorio";
                }
            }else{
                if($catalogo_sel == 3){
                    // if(!is_null($nuevo_valor_noide) && $nuevo_valor_noide != ""){
                        if(in_array($ban_filtro, array(0, 1, 2))){

                            $campo_db = $lista_claves[$catalogo_sel-1];

                            $sql_up = "";
                            $sql_up = "UPDATE ".MAIN_DB_PREFIX."product_extrafields";
                            $sql_up .= " SET";

                            if(!is_null($nuevo_valor_noide) && $nuevo_valor_noide != ""){
                                $sql_up.= ' '.$campo_db.' = "'.$nuevo_valor_noide.'"';
                            }else{
                                $sql_up.= " ".$campo_db." = NULL ";
                            }

                            // print 'aux :: '.$lista_claves[1];
                            // print '<pre>'; print_r($lista_claves); print '</pre>';

                            switch ($ban_filtro) {
                                case 0:
                                    # Actualizar Todos

                                    // where
                                    // fk_object

                                    break;

                                case 1:
                                    # Actualizar Todos Excepto
                                    if(!is_null($lista_aplicar_cambios) && count($lista_aplicar_cambios) > 0){
                                        $sql_up .= " WHERE fk_object NOT IN(".implode(",", $lista_aplicar_cambios).")";
                                    }else{
                                        $sql_up = "";
                                        $lista_errores[] = "Error 2003: No se encontraron Productos para omitir en el proceso de Actualizar";
                                    }
                                    break;

                                case 2:
                                    # Actualizar Solo los Seleccionados
                                    if(!is_null($lista_aplicar_cambios) && count($lista_aplicar_cambios) > 0){
                                        $sql_up .= " WHERE fk_object IN(".implode(",", $lista_aplicar_cambios).")";
                                    }else{
                                        $lista_errores[] = "Error 2002: No se encontraron Productos para el proceso de Actualizar";
                                    }
                                    break;
                            }

                            if($sql_up != ""){
                                // print $sql_up;
                                $res = $db->query($sql_up);
                                // ejecutar sql
                            }else{
                                $lista_errores[] = "Error 2001: La sentencia de actualización no se puedo crear correctamente.";
                            }
                        }else{
                            $lista_errores[] = "Error 2001: Filtro de Actualización no permitido";
                        }
                    // }else{
                    //     $lista_errores[] = "Error 2000: El campo Nuevo Valor es Obligatorio";
                    // }
                }else{
                    $lista_errores[] = "Error 8000: Catálogo no permitido";
                }
            }
        }else{
            $lista_errores[] = "Error 9000: El campo Catálogo es Obligatorio";
        }

        $_SESSION["lista_errores"] = null;
        $_SESSION["lista_errores"] = $lista_errores;

        print "<script>window.location='" . $_SERVER["PHP_SELF"] . "?mod=updatedatos_doli&proceso=1';</script>";
    }

    if(isset($_SESSION["lista_errores"]) && $proceso_terminado == 1){
        if(!is_null($_SESSION["lista_errores"]) && count($_SESSION["lista_errores"]) > 0){
            setEventMessage(implode("<br>", $_SESSION["lista_errores"]), 'errors');
        }else{
            setEventMessage('Proceso de Actualización Terminado correctamente.');
        }
    }

    ##View

    print '<div class="info hideonsmartphone clearboth">';
        print '<span class="fa fa-info-circle valignmiddle btnTitle-icon"></span>&nbsp;&nbsp;';
        print 'Apartado para actualizar masivamente <strong>Claves SAT</strong> a los Productos de Catálogo';
    print '</div>';

    print '<form method="POST" action="cfdimx.php?mod=updatedatos_doli" enctype="multipart/form-data">';
        print '<input type="hidden" name="action" id="action" value="update">';
        print '<input type="hidden" name="token" id="token" value="'.newToken().'">';

        print '<table style="width: 100% !important;" class="noborder">';
            print '<tr class="liste_titre">';
                print "<td colspan='5'>";
                    print '<span class="fa fa-tasks valignmiddle btnTitle-icon"></span>&nbsp;&nbsp;';
                    print "<b>&nbsp;Actualización Masiva de Claves SAT</b>";
                print "</td>";
            print '</tr>';

            print '<tr>';
                print '<td class="fieldrequired">';
                    print 'Catálogo';
                print '</td>';
                print '<td>';
                    print $ob_configuracion->obtener_catalogo($catalogo_sel, 'catalogo_sel',4);
                print '</td>';
            print '</tr>';

             print '<tr>';
                print '<td class="fieldrequired">';
                    print 'Nuevo Valor';
                print '</td>';
                print '<td>';
                    print '<div id="lista_valores">';
                        print $ob_configuracion->obtener_catalogo($nuevo_valor, 'nuevo_valor',6);
                    print '</div>';
                    print '<input type="text" name="nuevo_valor_noide" id="nuevo_valor_noide" placeholder="Nuevo No. Identificación" style="display: none;" value="'.$nuevo_valor_noide.'">';
                print '</td>';
            print '</tr>';

            print '<tr>';
                print '<td>';
                    print 'Productos';
                print '</td>';
                print '<td>';
                    print '<input type="hidden" name="ban_filtro" id="ban_filtro" value="0"><br>';
                    print '<input type="radio" name="filtro_update" id="filtro_update" value="0" checked onchange="cambiarFiltro(0)">Todos<br>';
                    print '<input type="radio" name="filtro_update" id="filtro_update" value="1" onchange="cambiarFiltro(1)">Todos, excepto<br>';
                    print '<input type="radio" name="filtro_update" id="filtro_update" value="2" onchange="cambiarFiltro(2)">Solo los Seleccionados<br><br>';
                    print $ob_configuracion->obtener_catalogo($productos_sel, 'productos_sel',5);

                    print '<button onclick="agregarLista(); return false;" class="butAction">Agregar</button>';
                print '</td>';
            print '</tr>';
        print '</table>';

        print '<input type="hidden" name="num_productos" id="num_productos" value="0">';
        print '<table style="width: 100% !important; display: none;" class="noborder" id="list_prod_add">';
            print '<tr class="liste_titre">';
                print "<td colspan='2'>";
                    print '<span class="fa fa-tasks valignmiddle btnTitle-icon"></span>&nbsp;&nbsp;';
                    print "<b>&nbsp;Lista de Productos Seleccionados</b>";
                print "</td>";
            print '</tr>';

            print '<tr>';
                print '<th>';
                    print '<span class="fa fa-trash valignmiddle btnTitle-icon"></span>';
                print '</th>';
                print '<th><strong>Producto</strong></th>';
            print '</tr>';
        print '</table>';

        print '<table style="width: 100% !important;" class="">';
            print '<tr>';
                print '<td colspan="2" align="center"><input class="butAction" type="submit" value="Actualizar"></td>';
            print '</tr>';

        print '</table>';
    print '</form>';