<script>
    $(document).ready(function(){
        $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
    });
</script>
<?php
    global $db, $conf;

    require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

    $zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

    if ((GETPOST('webprod') || GETPOST('webprueba')) && GETPOST('modotimbrado') != '') {
        $sql = "SELECT count(*) as exist FROM " . MAIN_DB_PREFIX . "cfdimx_config_ws";
        $sql .= " WHERE emisor_rfc='" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id=" . $conf->entity;

        $rs  = $db->query($sql);
        $rss = $db->fetch_object($rs);

        if ($rss->exist > 0) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."cfdimx_config_ws";
            $sql .= " SET ";
            $sql .= " ws_modo_timbrado=" . GETPOST('modotimbrado') . ",";
            $sql .= " ws_pruebas='" . GETPOST('webprueba') . "',";
            $sql .= " ws_produccion='" . GETPOST('webprod') . "'";
            $sql .= " WHERE emisor_rfc='" . $conf->global->MAIN_INFO_SIREN . "'";
            //print $sql.'<br>';
            $rs  = $db->query($sql);
            if (GETPOST('modotimbrado') == 1) {
                $ws = GETPOST('webprod');
            }
            if (GETPOST('modotimbrado') == 2) {
                $ws = GETPOST('webprueba');
            }

            dolibarr_set_const($db, "MAIN_MODULE_CFDIMX_WS", $ws, 'chaine', 1, '', $conf->entity);
        } else {
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_config_ws";
            $sql .= " (emisor_rfc,ws_modo_timbrado,ws_pruebas,ws_produccion,ws_status_conf,entity_id)";
            $sql .= " VALUES";
                $sql .= "(";
                    $sql .= "'".$conf->global->MAIN_INFO_SIREN."',";
                    $sql .= "'".GETPOST('modotimbrado'). "',";
                    $sql .= "'".GETPOST('webprueba')."',";
                    $sql .= "'".GETPOST('webprod')."',";
                    $sql .= "'',";
                    $sql .= $conf->entity;
                $sql .= ")";
            //print $sql.'<br>';
            $rs  = $db->query($sql);
            if (GETPOST('modotimbrado') == 1) {
                $ws = GETPOST('webprod');
            }
            if (GETPOST('modotimbrado') == 2) {
                $ws = GETPOST('webprueba');
            }

            dolibarr_set_const($db, "MAIN_MODULE_CFDIMX_WS", $ws, 'chaine', 1, '', $conf->entity);
        }
        print "<script>window.location='" . $_SERVER["PHP_SELF"] . "?mod=config';</script>";
    }

    $sql = "SELECT count(*) as exist FROM " . MAIN_DB_PREFIX . "cfdimx_config_ws";
    $sql .= " WHERE emisor_rfc='" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id=" . $conf->entity;
    $rs  = $db->query($sql);
    $rss = $db->fetch_object($rs);
    $a   = '';
    $b   = '';
    $c   = '';
    if ($rss->exist > 0) {
        $sql = "SELECT emisor_rfc,ws_modo_timbrado,ws_pruebas,ws_produccion FROM " . MAIN_DB_PREFIX . "cfdimx_config_ws WHERE emisor_rfc='" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id=" . $conf->entity;
        //print $sql.'<br>';
        $rs  = $db->query($sql);
        $rss = $db->fetch_object($rs);
        $a   = $rss->ws_produccion;
        $b   = $rss->ws_pruebas;
        $c   = $rss->ws_modo_timbrado;
    } else {
        $a = '';
        $b = '';
        $c = '';
    }

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
        print '<input type="hidden" name="mod" id="mod" value="config">';

        print '<table class="noborder" width ="100%">';
            print '<tbody>';
                print '<tr class="liste_titre">';
                    print '<th align="left" colspan="2">';
                        print img_picto('', 'globe', 'class="pictofixedwidth"').'&nbsp;';
                        print '<strong>Configuración del Webservice</strong>';
                    print '</th>';
                print '</tr>';

                print '<tr>';
                    print '<td width="25%">Webservice Producción</td>';
                    print '<td>';
                        print '<input type="text" name="webprod" id="webprod" size="70" value="' . $a . '">';
                    print '</td>';
                print '</tr>';

                print '<tr>';
                    print '<td  width="25%">Webservice Pruebas</td>';
                    print '<td>';
                        print '<input type="text"  name="webprueba" id="webprueba" size="70" value="' . $b . '">';
                    print '</td>';
                print '</tr>';

                print '<tr>';
                    print '<td  width="25%" class="fieldrequired">';
                        print 'Modo';
                    print '</td>';

                    print '<td>';
                        if ($c == '') {
                            $d = "SELECTED";
                            $e = '';
                            $f = '';
                        }
                        if ($c == 1) {
                            $d = '';
                            $e = "SELECTED";
                            $f = '';
                        }
                        if ($c == 2) {
                            $d = '';
                            $e = '';
                            $f = "SELECTED";
                        }

                        print '<select name="modotimbrado">';
                            print '<option value="" ' . $d . '>--Seleccione--</option>';
                            print '<option value="1" ' . $e . '>Producción</option>';
                            print '<option value="2" ' . $f . '>Pruebas</option>';
                        print '</select>';
                    print '</td>';
                print '</tr>';
            print '</tbody>';
        print '</table>';

        print '<div align="center" style="margin-top: 10px;">';
            print '<input type="submit" value="Guardar" class="butAction">';
        print '</div>';
    print '</form>';
    print '<br>';

    $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_config WHERE emisor_rfc = '" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id = " . $conf->entity;
    $resql = $db->query($sql);

    if ($resql) {
        $conf_num = $db->num_rows($resql);
        $i        = 0;
        if ($conf_num) {
            while ($i < $conf_num) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $status_conf           = $obj->status_conf;
                    $formato_cfdi          = $obj->formato_cfdi;
                    $password_timbrado_txt = $obj->password_timbrado_txt;
                    $modo_timbrado         = $obj->modo_timbrado;
                    $config_seriefolio     = $obj->config_seriefolio;
                }
                $i++;
            }
        }
    }

    function checkURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 0;
        }
        $curlInit = curl_init($url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlInit);
        curl_close($curlInit);
        if ($response)
            return 1;
        return 0;
    }

    $wscfdi = $conf->global->MAIN_MODULE_CFDIMX_WS;
    $status_ws = checkURL($wscfdi);

    if ($status_ws == 1) {
        $client = new nusoap_client($wscfdi, 'wsdl');
        $result = $client->call('validaCliente', array(
            "rfc" => $conf->global->MAIN_INFO_SIREN
        ));

        $status_clt         = $result["return"]["status_cliente_id"];
        $status_clt_desc    = $result["return"]["status_cliente_desc"];
        $folios_timbrados   = $result["return"]["folios_timbrados"];
        $folios_adquiridos  = $result["return"]["folios_adquiridos"];
        $folios_disponibles = $result["return"]["folios_disponibles"];

        $client_csd = new nusoap_client($wscfdi, 'wsdl');
        $result_csd = $client_csd->call('validarCSD', array(
            "rfc" => $conf->global->MAIN_INFO_SIREN,
            "pass" => $password_timbrado_txt
        ));
    }

    if ($_REQUEST["reg"] == "Registrar") {
        $result = $client->call('ValidaCliente', array(
            "emisorRFC" => $conf->global->MAIN_INFO_SIREN
        ));
        if ($result["status_cliente_id"] == 0) {
            echo "No se registró en el webservice";
        } else {
            echo "El cliente ya existe con el  status:" . $result["status_cliente_id"];
        }
    }

    if ($status_conf == 1) {
        $error = "Configuración OK";
    }

    if ($_REQUEST["saveconf"] == "Guardar") {
        if ($_REQUEST["conf_rfc_emisor"] != "" && $_REQUEST["formato_cfdi"] && $_REQUEST["passtimbrado"] && $_REQUEST["conf_modo"] && $_REQUEST["conf_seriefolio"]) {
            if ($conf_num == 0) {
                $insert = "
                INSERT INTO  " . MAIN_DB_PREFIX . "cfdimx_config VALUES (
                    '" . $_REQUEST["conf_rfc_emisor"] . "',
                    '" . md5($_REQUEST["passtimbrado"]) . "',
                    '" . $_REQUEST["passtimbrado"] . "',
                    '" . $_REQUEST["formato_cfdi"] . "',
                    '" . $_REQUEST["conf_modo"] . "',
                    '" . $_REQUEST["conf_seriefolio"] . "',
                    1,
                    '" . $conf->entity . "'
                )";

                $db->query($insert);

                dolibarr_set_const($db, "CFDIMX_VERSION_SAT", $_REQUEST["version_cfdi"], 'chaine', 0, '', $conf->entity);

                print "<script> location.href='?mod=config' </script>";
            }else{
                $update = "
                UPDATE  " . MAIN_DB_PREFIX . "cfdimx_config SET
                    emisor_rfc = '" . $_REQUEST["conf_rfc_emisor"] . "',
                    password_timbrado = '" . md5($_REQUEST["passtimbrado"]) . "',
                    password_timbrado_txt = '" . $_REQUEST["passtimbrado"] . "',
                    formato_cfdi = '" . $_REQUEST["formato_cfdi"] . "',
                    modo_timbrado = '" . $_REQUEST["conf_modo"] . "',
                    config_seriefolio = '" . $_REQUEST["conf_seriefolio"] . "'
                WHERE emisor_rfc = '" . $_REQUEST["conf_rfc_emisor"] . "'
                AND entity_id = '" . $conf->entity . "'";
                $db->query($update);

                $sql_up = "UPDATE  " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp SET password_timbrado = '" . md5($_REQUEST["passtimbrado"]) . "', password_timbrado_txt = '" . $_REQUEST["passtimbrado"] . "' WHERE emisor_rfc = '" . $_REQUEST["conf_rfc_emisor"] . "' AND entity_id = '" . $conf->entity . "'";
                $db->query($sql_up);

                dolibarr_set_const($db, "CFDIMX_VERSION_SAT", $_REQUEST["version_cfdi"], 'chaine', 0, '', $conf->entity);

                echo "<script> location.href='?mod=config' </script>";
            }
        }else{
            if ($_REQUEST["conf_rfc_emisor"] != "")
                echo "<script>alert('El RFC del emisor esta vacio')</script>";
            if ($_REQUEST["formato_cfdi"] != "")
                echo "<script>alert('Formato vacio')</script>";
            if ($_REQUEST["passtimbrado"] != "")
                echo "<script>alert('Password de timbrado vacio')</script>";
            if ($_REQUEST["conf_modo"] != "")
                echo "<script>alert('Modo de timbrado vacio')</script>";
            if ($_REQUEST["conf_seriefolio"] != "")
                echo "<script>alert('Serie y Folio vacio')</script>";
        }
    }

    $conf_emisor = 1;

    if ($status_ws == 1) {
        if ($conf_emisor == 1) {

            print '<form method="post">';
                print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                print '<table class="noborder" width="100%">';
                    print '<tbody>';
                        print '<tr class="liste_titre">';
                            print '<td colspan="2" align="left">';
                                print img_picto('', 'tools', 'class="pictofixedwidth"').'&nbsp;';
                                print '<strong>Configuración General</strong>';
                            print '</td>';
                        print '</tr>';

                        if ($error) {
                            print '<tr>';
                                print '<td colspan="2" align="center" style="font-size:16px; color:#C30"><strong>'.$error.'</strong></td>';
                            print '</tr>';
                        }

                        #RFC
                        print '<tr>';
                            print '<td width="25%">RFC</td>';
                            print '<td>'.$conf->global->MAIN_INFO_SIREN.'</td>';
                        print '</tr>';

                        #Estatus
                        if(empty($status_clt_desc)){
                            $status_clt_desc = "Pruebas";
                        }

                        print '<tr>';
                            print '<td width="25%">Estatus</td>';
                            print '<td>'.utf8_encode($status_clt_desc).'</td>';
                        print '</tr>';

                        #URL WebService
                        print '<tr>';
                            print '<td width="25%">URL Webservice</td>';
                            print '<td>'.$conf->global->MAIN_MODULE_CFDIMX_WS.'</td>';
                        print '</tr>';

                        #Verify WS
                        print '<tr>';
                            print '<td width="25%">Webservice</td>';
                            print '<td>';
                                if($wscfdi != ""){
                                    print img_picto("Web Services Activo", 'on');
                                }else{
                                    print img_picto("Web Services No Activo", 'off');
                                }
                            print '</td>';
                        print '</tr>';

                        if ($status_ws == 1) {
                            if ($status_clt == 1 || $status_clt == 2 || $status_clt == 3) {

                                #Folios Disponibles
                                print '<tr>';
                                    print '<td width="25%">Folios Disponibles</td>';
                                    print '<td>' . $folios_disponibles;

                                    if($folios_disponibles <= 0){
                                        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Adquirir Folios:</b> <a href="https://auriboxconsulting.com/soluciones-erp/facturacion-electronica-dolibarr-erp-crm" target="_blank">Click Aquí</a>';
                                    }
                                    print '</td>';
                                print '</tr>';

                                #Folios Timbrados
                                print '<tr>';
                                    print '<td width="25%">Folios Timbrados</td>';
                                    print '<td>' . $folios_timbrados . '</td>';
                                print '</tr>';

                                print '
                                    <script type="text/javascript">
                                        function mostrar(){
                                            var password, check;

                                            password = document.getElementById("passtimbrado");
                                            opc_ver = document.getElementById("opc_ver").value;

                                            // Si la checkbox de mostrar contraseña está activada
                                            if (opc_ver == 0){
                                                password.type = "text";
                                                document.getElementById("opc_ver").value = 1;
                                                document.getElementById("icono_ver").className = "fa fa-eye-slash valignmiddle btnTitle-icon";
                                                document.getElementById("icono_ver").title = "Ocultar Contraseña de Timbrado";
                                            }else{
                                                password.type = "password";
                                                document.getElementById("opc_ver").value = 0;
                                                document.getElementById("icono_ver").className = "fa fa-eye valignmiddle btnTitle-icon";
                                                document.getElementById("icono_ver").title = "Visualizar Contraseña de Timbrado";
                                            }
                                        }
                                    </script>';

                                #Password de Timbrado
                                print '<tr>';
                                    print '<td class="fieldrequired" width="25%">Password para Timbrar</td>';
                                    print '<td>';
                                        print '<input name="passtimbrado" id="passtimbrado" type="password" size="40" value="' . $password_timbrado_txt . '">';                                        
                                        print '<input type="hidden" name="opc_ver" id="opc_ver" value="0">';
                                        print '<a id="btn_ver_contra" class="btn btn-link" title="Visualizar Contraseña de Timbrado" onclick="mostrar()">';
                                            print '<span id="icono_ver" class="fa fa-eye valignmiddle btnTitle-icon"></span>';
                                        print '</a>';
                                    print '</td>';
                                print '</tr>';

                                #Modo de Timbrado
                                print '<tr>';
                                    print '<td class="fieldrequired" width="25%">Modo de Timbrado</td>';
                                    print '<td>';
                                        print '<select name="conf_modo">';
                                            if ($status_clt == 3) {
                                                print '<option value="">--Seleccione--</option>';
                                                print '<option value="1" '.$objConf->getSelected($modo_timbrado, 1).'>Producción</option>';
                                                print '<option value="2" '.$objConf->getSelected($modo_timbrado, 2).'>Pruebas</option>';
                                            } elseif ($status_clt == 2) {
                                                if ($modo_timbrado != $status_clt) {
                                                    print '<option value="">--Seleccione--</option>';
                                                    print '<option value="2">Pruebas</option>';
                                                } else {
                                                    print '<option value="2" '.$objConf->getSelected($modo_timbrado, $status_clt).'>Pruebas</option>';
                                                }
                                            } elseif ($status_clt == 1) {
                                                if ($modo_timbrado != $status_clt) {
                                                    print '<option value="">--Seleccione--</option>';
                                                    print '<option value="1">Producción</option>';
                                                } else {
                                                    print '<option value="1" '.$objConf->getSelected($modo_timbrado, $status_clt).'>Producción</option>';
                                                }
                                            }
                                        print '</select>';
                                    print '</td>';
                                print '</tr>';

                                #Version CFDI
                                $sel_version33 = '';
                                $sel_version40 = '';
                                if(strcmp($conf->global->CFDIMX_VERSION_SAT, "3.3") == 0){
                                    $sel_version33 = 'selected';
                                }else{
                                    if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
                                        $sel_version40 = 'selected';
                                    }
                                }

                                print '<tr>';
                                    print '<td width="25%">Versión CFDI</td>';
                                    print '<td>';
                                        print '<select name="version_cfdi">';
                                            print '<option value="0">--Seleccione--</option>';
                                            print '<option value="3.3" '.$sel_version33.'>3.3</option>';
                                            print '<option value="4.0" '.$sel_version40.'>4.0</option>';
                                        print '</select>';
                                print '</tr>';
                            }
                        }else {
                            print '<tr><td colspan="2" align="center">Problemas de conexion con el Servicio Web.</td></tr>';
                        }
                    print '</tbody>';                    
                print '</table>';

                print '<div align="center" style="margin-top: 10px;">';
                    print '<input type="hidden" name="conf_rfc_emisor" value="' . $conf->global->MAIN_INFO_SIREN . '">';
                    print '<input type="hidden" name="conf_seriefolio" id="conf_seriefolio" value="1">';
                    print '<input type="hidden" name="formato_cfdi" id="formato_cfdi" value="standard">';
                    print '<input name="saveconf" type="submit" value="Guardar" class="butAction">';
                print '</div>';
            print '</form>';

            if($result_csd != null && $password_timbrado_txt != ''){
                print '<br>';
                print '<table class="noborder" width="100%">';                    
                    print '<tr class="liste_titre">';
                        print '<th colspan="2" align="left">';
                            print img_picto('', 'check', 'class="pictofixedwidth"').'&nbsp;';
                            print '<strong>Validación de Sellos Digitales</strong>';
                        print '</th>';
                    print '</tr>';                    

                    $mensaje_csd = "";
                    $num_leyenda = 0;
                    if($result_csd["return"]["status"] == 2){
                        $mensaje_csd = "Vigente";
                        $num_leyenda = 4;
                    }else{
                        if(is_null($result_csd["return"]["status"])){
                            $mensaje_csd = $result_csd["return"]["mensaje"];
                            $num_leyenda = 8;
                        }
                    }

                    if($result_csd["return"]["status_cer"] > 0){
                        $mensaje_csd = "La fecha del certificado esta fuera de vigencia.";
                        $num_leyenda = 8;
                    }

                    if($result_csd["return"]["status_key"] > 0){
                        $mensaje_csd = "La contraseña es incorrecta.";
                        $num_leyenda = 8;
                    }

                    if($result_csd["return"]["status_cer"] > 998 && $result_csd["return"]["status_key"] > 998){
                        $mensaje_csd = $result_csd["return"]["mensaje"];
                        $num_leyenda = 8;
                    }

                    if(!is_null($result_csd["return"]["status"])){
                        print '<tr>';
                            print '<td width="25%">RFC</td>';
                            print '<td>'.$conf->global->MAIN_INFO_SIREN.'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td width="25%">Archivo .cer</td>';
                            print '<td>'.$result_csd["return"]["file_cer"].'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td width="25%">Archivo .key</td>';
                            print '<td>'.$result_csd["return"]["file_key"].'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td width="25%">Fecha Inicial de Vigencia</td>';
                            print '<td>'.$result_csd["return"]["fecha_inicio_vigencia"].'</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td width="25%">Fecha Final de Vigencia</td>';
                            print '<td>'.$result_csd["return"]["fecha_termino_vigencia"].'</td>';
                        print '</tr>';
                    }

                    print '<tr>';
                        print '<td width="25%">Estatus</td>';
                        print '<td><span class="badge badge-status'.$num_leyenda.' badge-status">'.utf8_encode($mensaje_csd).'</span></td>';
                    print '</tr>';
                print '</table>';

                $url_plataforma_timbrado = "http://admin.auriboxenlinea.com/produc/cfdi/";
                print '<div class="info hideonsmartphone clearboth">';
                    print img_picto('', 'info', 'class="pictofixedwidth"').'&nbsp;';
                    print '<b>Nota:</b> Los Sellos Digitales se actualizan en la plataforma <strong><a href="'.$url_plataforma_timbrado.'" target="_blank">Gestión de Timbrado</a></strong>, en caso de no contar con los accesos solicitarlos al área de <strong>Soporte de Auribox Consulting</strong>.';
                print '</div>';
                print '<br>';
            }
        } else {
            print img_warning().' <font class="error">Complete la configuración de la pestaña <i>Datos del Emisor</i> para continuar.</font>';
        }
    }else{
        if($wscfdi != ""){
            print '<div class="error hideonsmartphone clearboth">';
                print img_error();
                $mensaje  = "Problemas de conexion con el Web Services.<br>";
                $mensaje .= "<ol>Posibles errores que lo ocasionan:";
                    $mensaje .= "<li>RFC no registrado en la Plataforma de Timbrado.</li>";
                    $mensaje .= "<li>RFC no registrado en el Modo de Timbrado Seleccionado.</li>";
                    $mensaje .= "<li>RFC sin timbres asignados.</li>";
                    $mensaje .= "<li>URL del Web Services incorrecta.</li>";
                    $mensaje .= "<li>Modo de Timbrado incorrecto a la URL.</li>";
                    $mensaje .= "<li>Bloqueo del servidor que aloja el servidor con el Web Services.</li>";
                $mensaje .= "</ol>";
        }else{
            print '<div class="warning hideonsmartphone clearboth">';
                print img_warning();
                $mensaje = "<strong>Falta indicar la URL del Web Services para probar la Conectividad.</strong>";
        }

            print '&nbsp;&nbsp;';
            print $mensaje;
        print '</div>';
    }

    print '<script type="text/javascript">
			jQuery(document).ready(function() {
				$("#btn_ocultar_mostrar").click(function() {
					$("#tmporal_ref").show("fast");
					$("#head_hide").show("fast");
					$("#head_show").hide("fast");

					var aux = $("#list_url_ws").val();

					if(aux == 0){
						$("#list_url_ws").val(1);

						$("#lista_ajustes_pdf").show("fast");
						$("#icono_1").removeClass("fa fa-plus-circle valignmiddle btnTitle-icon");
						$("#icono_1").addClass("fa fa-minus-circle valignmiddle btnTitle-icon");
						$("#btn_ocultar_mostrar").prop("title","Ocultar Lista de URL de Web Services");
					}else{
						$("#list_url_ws").val(0);

						$("#lista_ajustes_pdf").hide("fast");
						$("#icono_1").removeClass("fa fa-minus-circle valignmiddle btnTitle-icon");
						$("#icono_1").addClass("fa fa-plus-circle valignmiddle btnTitle-icon");
						$("#btn_ocultar_mostrar").prop("title","Mostrar Lista de URL de Web Services");
					}
				});
			});
		</script>';

        $url_api        = "https://api-cfdi.auribox.com/list_ws";
        $lista_urls_api = getURLContent($url_api, 'GET', '', 1, array(), array('http', 'https'), 0);
        $lista_ws       = json_decode($lista_urls_api['content']);

        if(!is_null($lista_ws) && count($lista_ws) > 0){
            print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                    print '<th colspan="2">';
                        print '<input type="hidden" name="list_url_ws" id="list_url_ws" value="0">';

                        print '<button id="btn_ocultar_mostrar" class="butAction" title="Mostrar Lista de URL de Web Services">';
                            print '<span id="icono_1" class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>';
                        print '</button>';
                        print '&nbsp;';

                        print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>';
                        print '&nbsp;';
                        print '<strong>URL Web Services</strong>';
                    print '</th>';
                print '</tr>';

                print '<tbody id="lista_ajustes_pdf" style="display: none;">';
                    print '<tr>';
                        print '<th>Modo</th>';
                        print '<th>URL</th>';
                    print '</tr>';

                    foreach ($lista_ws as $webservices) {
                        print '<tr>';
                            print '<td>'.$webservices->modo.'</td>';
                            print '<td>'.$webservices->url.'</td>';
                        print '</tr>';
                    }
                print '</tbody>';
            print '</table>';
        }
?>