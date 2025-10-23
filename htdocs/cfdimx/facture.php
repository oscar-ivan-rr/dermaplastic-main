<?php
    require('../main.inc.php');

    global $user, $db, $conf;
    error_reporting(0);

    $zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

    // require('conf.php');
    include('lib/nusoap/lib/nusoap.php');
    include("lib/phpqrcode/qrlib.php");
    require('lib/numero_a_letra.php');

    require('class/timbrado.class.php');
    require_once("class/facturacfdimx.class.php");
    require_once("class/complementos.class.php");
    require_once("class/pagos.class.php");

    require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
    require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php");
    require_once(DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
    require_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
    require_once(DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
    require_once(DOL_DOCUMENT_ROOT . '/core/class/discount.class.php');
    require_once(DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php');
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
    require_once(DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php');
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");

    session_start();

    if (@$conf->commande->enabled){
        require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
    }

    if (@$conf->projet->enabled) {
        require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php');
        require_once(DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php');
    }

    $wscfdi             = $wscfdi = $conf->global->MAIN_MODULE_CFDIMX_WS;
    $client             = new nusoap_client($wscfdi, 'wsdl');
    $result             = $client->call('validaCliente', array("rfc" => $conf->global->MAIN_INFO_SIREN));
    $status_clt         = $result["return"]["status_cliente_id"];
    $status_clt_desc    = $result["return"]["status_cliente_desc"];
    $folios_timbrados   = $result["return"]["folios_timbrados"];
    $folios_adquiridos  = $result["return"]["folios_adquiridos"];
    $folios_disponibles = $result["return"]["folios_disponibles"];

    $ban_timbrado = 0;
    if (@$_REQUEST["cfdi_commit"] == 1) {
        $msg_cfdi_final = "El comprobante se ha generado de manera exitosa.";
        $ban_timbrado = 1;
    }else {
        if (@$_REQUEST["cfdi_commit"] == 307) {
            $msg_cfdi_final = "El comprobante se ha recuperado de manera exitosa.";
            $ban_timbrado = 1;
        }
    }
    
    $contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'thirdpartylist';

    if ($contextpage == 'poslist')
    {
        $_GET['optioncss'] = 'print';
    }

    $langs->load('bills');
    $langs->load('companies');
    $langs->load('products');
    $langs->load('main');

    if (GETPOST('mesg', 'int', 1) && isset($_SESSION['message']))
        $mesg = $_SESSION['message'];

    $sall      = trim(GETPOST('sall'));
    $projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);

    $id      = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
    $ref     = GETPOST('ref', 'alpha');
    $factemp = new Facture($db);
    $factemp->fetch($id, $ref);
    $id = $factemp->id;
    @$_REQUEST["facid"] = $id;
    $socid              = GETPOST('socid', 'int');
    $action             = GETPOST('action', 'alpha');
    $confirm            = GETPOST('confirm', 'alpha');
    $lineid             = GETPOST('lineid', 'int');
    $userid             = GETPOST('userid', 'int');
    $search_ref         = GETPOST('sf_ref') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
    $search_societe     = GETPOST('search_societe', 'alpha');
    $search_montant_ht  = GETPOST('search_montant_ht', 'alpha');
    $search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
    $dol_version        = (int)DOL_VERSION;

    if (isset($_POST['selected_emisor'])) {
        $_SESSION['selected_emisor'] = $_POST['selected_emisor'];
    }

    $object = new Facture($db);

    // Actions to send emails
    if (empty($id)) $id=$facid;
    $trigger_name='BILL_SENTBYMAIL';
    $paramname='id';
    $autocopy='MAIN_MAIL_AUTOCOPY_INVOICE_TO';
    $trackid='inv'.$factemp->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

    // Security check
    $fieldid = (!empty($ref) ? 'ref' : 'rowid');

    if($dol_version >= 14){
        if ($user->socid)
            $socid = $user->socid;
    }else{
        if ($user->societe_id)
            $socid = $user->societe_id;
    }

    // Cargar object
    if ($id > 0 || !empty($ref)) {
        $ret = $object->fetch($id, $ref);
    }

    // Datos del receptor
    $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "facture f,  " . MAIN_DB_PREFIX . "societe s WHERE f.rowid = '" . $_REQUEST["facid"] . "' AND f.fk_soc = s.rowid";
    $resql = $db->query($sql);
    $tipo_domicilio = 0;

    if ($resql) {
        $soc_num = $db->num_rows($resql);
        $i       = 0;
        if ($soc_num) {
            while ($i < $soc_num) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $soc_rfc   = $obj->siren;
                    $soc_id    = $obj->rowid;
                    $soc_email = $obj->email;
                    $status    = $obj->fk_statut;
                    $tipo_domicilio = 0;
                }
                $i++;
            }
        }
    }

    $objComplementos = new ComplementosCFDI($db);

    $objFacturaCFDI = new FacturaCFDI($db);
    $objFacturaCFDI->entidad = $conf->entity;
    $num_domicilio_fiscal = $objFacturaCFDI->getDomiciliosFiscalesCliente($object->socid);
    $msj_error_domicilio_fiscal_40 = "";
    $num_validaciones_cfdi_40 = 0;

    if($num_domicilio_fiscal > 0 && strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
        $tipo_domicilio = 1;
        for ($i=0; $i < count($objFacturaCFDI->lista_domicilios); $i++) {
            $soc_rfc = $objFacturaCFDI->lista_domicilios[$i]->rfc;
            break;
        }
    }else{
        $tipo_domicilio = 1;
        if($num_domicilio_fiscal < 1){
            $msj_error_domicilio_fiscal_40 = "Error: Para el Timbrado de CFDI 4.0 se requiere llenar el apartado de Domicilio Fiscal en la Ficha del Cliente.";
        }
    }

    // Datos de configuración del módulo
    // Si usuario tiene almacen, extraer datos de almacen, si no de emisor predeterminado
    if ($user->fk_warehouse > 0) {
        $emisorsql = "SELECT status_conf, modo_timbrado, password_timbrado_txt";
        $emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
        $emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
        $emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
        $emisorsql .= " WHERE e.rowid = " . $user->fk_warehouse;

        $reemisorsql = $db->query($emisorsql);
        $conf_num = $db->num_rows($reemisorsql);

        if ($reemisorsql) {
            $emisor_almacen = $db->fetch_object($emisorsql);
        }

        $status_conf     = $emisor_almacen->status_conf;
        $modo_timbrado   = $emisor_almacen->modo_timbrado;
        $passwd_timbrado = $emisor_almacen->password_timbrado_txt;
    } else {
        // Priorizar emisor seleccionado en ficha, si no hay seleccion, usar predeterminado
        $selectedEmisor = $_SESSION['selected_emisor'] ? $_SESSION['selected_emisor'] : $conf->global->MAIN_INFO_SIREN;
        $sql   = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_config WHERE emisor_rfc = '" . $selectedEmisor . "' AND entity_id = " . $conf->entity;
        $resql = $db->query($sql);
        if ($resql) {
            $conf_num = $db->num_rows($resql);
            $i        = 0;
            if ($conf_num) {
                while ($i < $conf_num) {
                    $obj = $db->fetch_object($resql);
                    if ($obj) {
                        $status_conf     = $obj->status_conf;
                        $modo_timbrado   = $obj->modo_timbrado;
                        $passwd_timbrado = $obj->password_timbrado_txt;
                    }
                    $i++;
                }
            }
        }
    }

    #Datos de la factura cfdimx
    $uuid         = "";
    $cfdi_cancela = "";
    $totalpaye    = "";
    $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx WHERE fk_facture = " . $_REQUEST["facid"];
    $resql = $db->query($sql);
    if ($resql) {
        $i = 0;
        $cfdi_tot = $db->num_rows($resql);
        if ($conf_num) {
            while ($i < $conf_num) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $cfdi_cancela  = $obj->cancelado;
                    $uuid          = $obj->uuid;
                    $selloSAT      = $obj->selloSAT;
                    $selloCFD      = $obj->selloCFD;
                    $fechaTimbrado = $obj->fechaTimbrado;
                    $factura_id    = $obj->factura_id;
                    $divisa        = $obj->divisa;
                }
                $i++;
            }
        }
    }

    if (isset($conf->global->MAIN_MODULE_MULTICURRENCY)) {
        $object->total_ht  = $object->multicurrency_total_ht;
        $object->total_tva = $object->multicurrency_total_tva;
        $object->total_ttc = $object->multicurrency_total_ttc;
    }

    #Status del comprobante
    if($uuid != ""){
        $sql_verificar_estatus = "";
        $sql_verificar_estatus = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_estatus";
        $sql_verificar_estatus .= " WHERE uuid = '".$uuid."'";
        $sql_verificar_estatus .= " AND facid = ".$_REQUEST["facid"];
        $sql_verificar_estatus .= " AND entity = ".$conf->entity;
        $res_verificar_estatus = $db->query($sql_verificar_estatus);
        $num_verificar_estatus = $db->num_rows($res_verificar_estatus);

        if($num_verificar_estatus > 0){
            $obj_verificar_estatus = $db->fetch_object($res_verificar_estatus);
            $estado_cfdi            = $obj_verificar_estatus->estado;
            $estatus_cancelacion    = $obj_verificar_estatus->escancelable;
            $fecha_consulta_estatus = $obj_verificar_estatus->fecha."&nbsp;".$obj_verificar_estatus->hora;
        }
    }else{
        $estado_cfdi            = "Sin timbrar";
        $estatus_cancelacion    = "";
        $fecha_consulta_estatus = "";
    }

    /********************************************************************
     *                                                                   *
     * Actions                                                           *
     *                                                                   *
    *********************************************************************/

    if ($action == 'guarda_tercero') {
        $cambia_tercero = "UPDATE  " . MAIN_DB_PREFIX . "facture SET fk_soc = ".$_REQUEST["socid"]." WHERE rowid = " . $_REQUEST["facid"];

        $res_cambia_tercero = $db->query($cambia_tercero);
    }

    $form      = new Form($db);
    $htmlother = new FormOther($db);
    $formfile  = new FormFile($db);

    // generaCFDI
    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "generaCFDI") {
        foreach ($_REQUEST as $key => $value) {
            $$key = $value;
        }
        include("generaCFDI.php");
    }

    if ($action == 'confirm_del_reten_man') {
        $delete = "DELETE FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE retenciones_id = " . $_REQUEST["del_retencion"];
        $db->query($delete);
        $rescomm = $db->commit();
        if ($_REQUEST["tptre"] == "IVA") {
            $delete = "DELETE FROM  " . MAIN_DB_PREFIX . "cfdimx_retencionesdet WHERE impuesto='002' AND factura_id = " . $_REQUEST["facid"];
            $db->query($delete);
        }
        if ($_REQUEST["tptre"] == "ISR") {
            $delete = "DELETE FROM  " . MAIN_DB_PREFIX . "cfdimx_retencionesdet WHERE impuesto='001' AND factura_id = " . $_REQUEST["facid"];
            $db->query($delete);
        }
        print '<script>location.href="?facid=' . $_REQUEST["facid"] . '"</script>';
    }

    if (isset($_REQUEST["del_retencion_local"]) && $_REQUEST["del_retencion_local"] != "") {
        $delete = "DELETE FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE rowid = " . $_REQUEST["del_retencion_local"];
        //print $delete;
        $rescomm = $db->query($delete);

        if ($rescomm) {
            print '<script>location.href="?facid=' . $_REQUEST["facid"] . '"</script>';
        }
    }

    if ($action == 'add_reten_man') {
        if ($_REQUEST["impuesto"] != "" && $_REQUEST["importe"] != "") {
            $aplicar_ret_ind = $conf->global->CFDIMX_RET_INDIVIDUALES;
            //Se obtiene el valor para determinar si las retenciones se hacen por concepto
            //0 -> Se aplican retenciones a todos los conceptos
            //1 -> Se aplican retenciones solo a los conceptos seleccionados

            // print '<pre>'; print_r($object->lines); print '</pre>';
            $importe_retencion = 0;

            function truncateFloat($number, $digitos)
            {
                $raiz          = 10;
                $multiplicador = pow($raiz, $digitos);
                $resultado     = ((int) ($number * $multiplicador)) / $multiplicador;
                return number_format($resultado, $digitos);
            }

            for($i=0; $i < sizeof($object->lines);$i++) {
                // print '<pre>'; print_r($object->lines[$i]); print '</pre>';

                $aplicar_retencion = ($object->lines[$i]->array_options["options_aplicar_ret_individual"] != "" ? $object->lines[$i]->array_options["options_aplicar_ret_individual"] : 0);
                $total_ht_det = 0;

                if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                    $total_ht_det = $object->lines[$i]->multicurrency_total_ht;
                }else{
                    $total_ht_det = $object->lines[$i]->total_ht;
                }

                $importe_calculado = $total_ht_det * $_REQUEST["importe"];

                $sql_retencion = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet";
                $sql_retencion .= " (factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe)";
                $sql_retencion .= " VALUES";
                $sql_retencion .= " (";
                    $sql_retencion .= $_REQUEST["facid"].",";
                    $sql_retencion .= $object->lines[$i]->id.",";
                    $sql_retencion .= "'".round($total_ht_det, 2)."',";
                    $sql_retencion .= "'".$_REQUEST["impuesto"]. "',";
                    $sql_retencion .= "'Tasa',";
                    $sql_retencion .= "'".$_REQUEST["importe"]."',";
                    $sql_retencion .= "'".str_replace(",", "", truncateFloat($importe_calculado, 2)) . "'";
                $sql_retencion .= " )";
                // print $sql_retencion;

                if($aplicar_ret_ind == 1){
                    if($aplicar_retencion == 1){
                        $res_retencion_individual = $db->query($sql_retencion);
                        $importe_retencion += str_replace(",", "", truncateFloat($importe_calculado, 2));
                    }
                }else{
                    $res_retencion_individual = $db->query($sql_retencion);
                    $importe_retencion += str_replace(",", "", truncateFloat($importe_calculado, 2));
                }
            }

            $impuest_head_retencion = ($_REQUEST["impuesto"] == "002" ? "IVA" : "ISR");

            $head_retencion =  "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones";
            $head_retencion .= " (factura_id, fk_facture, impuesto, importe)";
            $head_retencion .= " VALUES";
            $head_retencion .= "(";
                $head_retencion .= $_REQUEST["facid"].",";
                $head_retencion .= $_REQUEST["facid"] . ",";
                $head_retencion .= "'".$impuest_head_retencion."',";
                $head_retencion .= round($importe_retencion, 2);
            $head_retencion .=")";

            $respuesta = 0;
            if($importe_retencion != 0){
                $respuesta = $db->query($head_retencion);
            }

            if ($respuesta == 1) {
                echo '<script>location.href="?facid=' . $_REQUEST["facid"] . '"</script>';
            } else {
                $msg_retenciones = "Error al registrar las Retenciones.";
                if($importe_retencion == 0){
                    $msg_retenciones .= "<br>Porque la suma de las Retenciones no es diferente de 0.";
                }
            }
        }
    }

    if (isset($_REQUEST["envRetencionLocal"]) && $_REQUEST["envRetencionLocal"] != "") {
        if ($_REQUEST["retlocal"] != "") {
            $sqm     = "SELECT rowid, cod,descripcion,tasa FROM " . MAIN_DB_PREFIX . "cfdimx_config_retenciones_locales
                    WHERE entity=" . $conf->entity . " AND rowid=" . $_REQUEST["retlocal"];
            //print $sqm;
            $rqs     = $db->query($sqm);
            $mrs     = $db->fetch_object($rqs);
            $importe = $object->total_ht * ($mrs->tasa / 100);
            $insert  = "
            INSERT INTO  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales (
                fk_facture,codigo,tasa,importe
            ) VALUES (
                '" . $_REQUEST["facid"] . "',
                '" . $mrs->cod . "',
                '" . $mrs->tasa . "',
                '" . $importe . "'
            )";
            $db->query($insert);
            $rescomm = $db->commit();
            if ($rescomm == 1) {
                echo '<script>location.href="?facid=' . $_REQUEST["facid"] . '"</script>';
            } else {
                echo 'Error al insertar';
            }
        }
    }

    if ($action == "confirm_cancel" && GETPOST('confirm') == "yes") {
        $foliosustitucion = (GETPOST("uuid_sustitucion") != '' ? GETPOST("uuid_sustitucion") : '');
        $motivo           = (GETPOST("motivo") != -1 ? GETPOST("motivo") : '');

        if ($user->fk_warehouse > 0) {
            $emisorsql = "SELECT edc.emisor_rfc, edc.razon_social, edc.regimen, edc.pais, edc.estado, edc.codigo_postal";
            $emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
            $emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
            $emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
            $emisorsql .= " WHERE e.rowid = " . $user->fk_warehouse;

            $reemisorsql = $db->query($emisorsql);

            if ($reemisorsql) {
                $emisor_almacen = $db->fetch_object($emisorsql);
            }

            $rfc_emisor          = $emisor_almacen->emisor_rfc;
        } else {
            $rfc_emisor          = $_SESSION['selected_emisor'] ? $_SESSION['selected_emisor'] : $conf->global->MAIN_INFO_SIREN;
        }

        $datos = array(
                    "timbrado_usuario" => $rfc_emisor,
                    "timbrado_password" => $passwd_timbrado,
                    "uuid" => GETPOST("uuid"),
                    "motivo" => $motivo,
                    "foliosustitucion" => $foliosustitucion
                );

        if($motivo != "" && $motivo != -1){
            $validacion = 1;
            if($motivo == "01"){
                $validacion = ($motivo == "01" && $foliosustitucion != "" ? 1 : 0);
            }

            if($validacion == 1){
                // Nuevo Esquema de Cancelación 2022
                $resultado = $client->call("cancelar", $datos);

                //Impresion de los arreglos que se mandan a timbrar
                if($conf->global->CFDIMX_DEBUG_TIMBRADO == 1){
                    print '<pre>'; print_r($datos); print '</pre>';
                    print '<pre>'; print_r($resultado); print '</pre>';
                }

                if($resultado["return"] != ""){
                    if($resultado["return"]["httpStatusCode"] == 200){
                        //Se guarda el acuse de cancelación en un archivo
                        if(file_exists($conf->facture->dir_output."/".$object->ref)){

                        }else{
                            mkdir($conf->facture->dir_output."/".$object->ref,0700);
                        }

                        $fecha = date("Y-m-d")."_".date("H-i-s");
                        $archivo_acuse = "acuse_cancelacion_".$fecha."_".GETPOST("uuid").".xml";

                        $nombre_file_acuse = $conf->facture->dir_output."/".$object->ref."/".$archivo_acuse;
                        $file_acuse = fopen ($nombre_file_acuse, "w");
                        fwrite($file_acuse,utf8_encode($resultado["return"]["acuse"]));
                        fclose($file_acuse);

                        #Inicio para guardar la respuesta a la solicitud de Cancelacion
                        $sql_solicitud_canceacion = "";
                        $sql_solicitud_canceacion .= "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_solicitud_cancelacion";
                        $sql_solicitud_canceacion .= " (fk_facture, httpStatusCode, acuse, status, uuid, uuidStatusCode, message, messageDetail, fecha, hora, archivo)";
                        $sql_solicitud_canceacion .= " VALUES";
                        $sql_solicitud_canceacion .= " (";
                            $sql_solicitud_canceacion .= "'".$_REQUEST["facid"]."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["httpStatusCode"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["acuse"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["status"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["uuid"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["uuidStatusCode"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["message"]))."',";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($resultado["return"]["messageDetail"]))."',";
                            $sql_solicitud_canceacion .= " now(), now(),";
                            $sql_solicitud_canceacion .= "'".$db->escape(utf8_decode($archivo_acuse))."'";
                        $sql_solicitud_canceacion .= " )";

                        // print $sql_solicitud_canceacion;
                        $res_solicitud_canceacion = $db->query($sql_solicitud_canceacion);
                        #Termina para guardar la respuesta a la solicitud de Cancelacion

                        print '<script>location.href="?facid=' . $_REQUEST["facid"] . '";</script>';
                    }else{
                        $msg_cfdi_final = "Error al Cancelar la Factura<br><br>";
                        if($resultado["return"]["message"] != "")
                            $msg_cfdi_final .= $resultado["return"]["message"]."&nbsp;";

                        if($resultado["return"]["messageDetail"] != "")
                            $msg_cfdi_final .= $resultado["return"]["messageDetail"];
                    }
                }else{
                    $msg_cfdi_final = "No hay respuesta para la cancelación con el SAT, favor de intentar mas tarde.";
                }
            }else{
                $msg_cfdi_final = "Error 9001: El Folio de Sustitución esta vacío y es requerido cuando el Motivo es 01 - Comprobantes emitidos con errores con relación.";
            }
        }else{
            $msg_cfdi_final = "Error 9002: El Motivo de Cancelación es obligatorio.";
        }
    }

    if ($action == "confirm_clasificar_factura" && GETPOST('confirm') == "yes") {
        // Classify "abandoned"
        $object->fetch($id);
        $close_code = GETPOST("close_code", 'restricthtml');
        $close_note = GETPOST("close_note", 'restricthtml');
        if ($close_code) {

            if($dol_version > 13){
                $result = $object->setCanceled($user, $close_code, $close_note);
            }else{
                $result = $object->set_canceled($user, $close_code, $close_note);
            }

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            }else{
                $cancela_update = "UPDATE ".MAIN_DB_PREFIX."cfdimx SET cancelado = 1 WHERE fk_facture = " . $_REQUEST["facid"];
                $rr = $db->query($cancela_update);
            }
        } else {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), null, 'errors');
        }
    }

    if ($action == "confirm_estatus" && GETPOST('confirm') == "yes") {
        $sql_verificar_estatus = "";
        $sql_verificar_estatus = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_estatus";
        $sql_verificar_estatus .= " WHERE uuid = '".$uuid."'";
        $sql_verificar_estatus .= " AND facid = ".$_REQUEST["facid"];
        $sql_verificar_estatus .= " AND entity = ".$conf->entity;
        $res_verificar_estatus = $db->query($sql_verificar_estatus);
        $num_verificar_estatus = $db->num_rows($res_verificar_estatus);

        $datos = array(
            "rfc_emisor"   => $conf->global->MAIN_INFO_SIREN,
            "rfc_receptor" => $soc_rfc,
            "total"        => $object->total_ttc,
            "uuid"         => $uuid
        );

        $result_status_factura = $client->call('getStatusFactura', $datos);

        $escancelable = $result_status_factura["return"]["esCancelable"];
        if($result_status_factura["return"]["estatusCancelacion"] != "" && !is_null($result_status_factura["return"]["estatusCancelacion"])){
            $escancelable = $result_status_factura["return"]["estatusCancelacion"];
        }

        if($num_verificar_estatus > 0){
            $obj_verificar_estatus = $db->fetch_object($res_verificar_estatus);

            $update_estatus = "";
            $update_estatus = "UPDATE ".MAIN_DB_PREFIX."cfdimx_estatus";
            $update_estatus .= " SET ";
            $update_estatus .= " estado = '".$db->escape(utf8_decode($result_status_factura["return"]["estado"]))."',";
            $update_estatus .= " escancelable = '".$db->escape(utf8_decode($escancelable))."',";
            $update_estatus .= " message = '".$db->escape(utf8_decode($result_status_factura["return"]["message"]))."',";
            $update_estatus .= " fk_user = '".$user->id."',";
            $update_estatus .= " fecha = now(), hora = now()";
            $update_estatus .= " WHERE rowid = ".$obj_verificar_estatus->rowid;

            if($obj_verificar_estatus->rowid > 0){
                $res_update = $db->query($update_estatus);

                print '<script>location.href="?facid=' . $_REQUEST["facid"] . '"</script>';
            }
        }else{
            $estado_cfdi            = $result_status_factura["return"]["estado"];
            $estatus_cancelacion    = $result_status_factura["return"]["esCancelable"];
            $fecha_consulta_estatus = date("Y-m-d")."&nbsp;".date("H:i:s");

            $sql_insert = "";
            $sql_insert .= " INSERT INTO ".MAIN_DB_PREFIX."cfdimx_estatus";
            $sql_insert .= " (";
            $sql_insert .= " uuid, facid, entity, estado, escancelable, message, fk_user, fecha, hora";
            $sql_insert .= " )";
            $sql_insert .= " VALUES";
            $sql_insert .= " (";
                $sql_insert .= "'".$uuid."',";
                $sql_insert .= "'".$_REQUEST["facid"]."',";
                $sql_insert .= "'".$conf->entity."',";
                $sql_insert .= "'".$db->escape(utf8_decode($result_status_factura["return"]["estado"]))."',";
                $sql_insert .= "'".$db->escape(utf8_decode($escancelable))."',";
                $sql_insert .= "'".$db->escape(utf8_decode($result_status_factura["return"]["message"]))."',";
                $sql_insert .= "'".$user->id."',";
                $sql_insert .= " now(), now()";
            $sql_insert .= " )";

            $res_insert = $db->query($sql_insert);

            print '<script>location.href="?facid=' . $_REQUEST["facid"] . '"</script>';
        }
    }

    /*********************************************************************
     *                                                                   *
     * Show object in view mode                                          *
     *                                                                   *
     *********************************************************************/

    llxHeader('', "CFDI ".$conf->global->CFDIMX_VERSION_SAT." - ".$langs->trans('Bill'), 'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

    $now       = dol_now();

    if ($id > 0 || !empty($ref)) {
        
        
        $extrafields = new ExtraFields($db);
        $extralabels = $extrafields->fetch_name_optionals_label('facture');

        if($conf->global->AUTO_INSERT_UPDATE_EXTRA_FIELDS){
            //* Funcionalidad para verificar que exista una forma de pago y uso de cfdi al momento de consultar la factura, en caso de que exista, solo se actualiza por la misma o en caso de null, se actualiza por valores por default
            $sql_extrafields = "SELECT * FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object = " . $object->id;

            $resql_extrafields = $db->query($sql_extrafields);
            if ($db->num_rows($resql_extrafields) == 0) {
                $sql_insert_extrafields = "INSERT INTO " . MAIN_DB_PREFIX . "facture_extrafields (fk_object, import_key, formpagcfdi, usocfdi, cfdidoctiporelacion, tipodecambiocfdi, clave_expor) VALUES(";
                $sql_insert_extrafields .= $object->id . ",";
                // Import Key
                $sql_insert_extrafields .= "NULL,";
                // Forma de pago
                $sql_insert_extrafields .= "'PUE',";
                // Uso de CFDI
                $sql_insert_extrafields .= "'G01',";
                // Tipo de relacion
                $sql_insert_extrafields .= "0,";
                // Tipo de cambio
                $sql_insert_extrafields .= "NULL,";
                // Clave de exportacion
                $sql_insert_extrafields .= "0";
                $sql_insert_extrafields .= ")";
                $resql_insert_extrafields = $db->query($sql_insert_extrafields);
            } else {
                $result_extrafields = $db->fetch_object($resql_extrafields);
                $forma_pago = $result_extrafields->formpagcfdi;
                $uso_cfdi = $result_extrafields->usocfdi;

                if (($forma_pago == NULL || $forma_pago == '')) {
                    $forma_pago = 'PUE';
                }
                if ($uso_cfdi == NULL || $uso_cfdi == '') {
                    $uso_cfdi = 'G01';
                }
                $sql_update_extrafields = "UPDATE " . MAIN_DB_PREFIX . "facture_extrafields SET ";
                $sql_update_extrafields .= "formpagcfdi = '" . $forma_pago . "',";
                $sql_update_extrafields .= "usocfdi = '" . $uso_cfdi . "'";
                $sql_update_extrafields .= " WHERE fk_object = " . $object->id;
                $resql_update_extrafields = $db->query($sql_update_extrafields);
            }    
        }


        if ($action == 'cancel') {
            $lista_motivos = array(
                    '-1' => 'Selecciona el Motivo de Cancelación&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                    '01' => '01 - Comprobantes emitidos con errores con relación.',
                    '02' => '02 - Comprobantes emitidos con errores sin relación.',
                    '03' => '03 - No se llevó a cabo la operación.',
                    '04' => '04 - Operación nominativa relacionada en una factura global.'
                );

            $titulo = "<br><em>El Folio de Sustitución es obligatorio cuando el motivo es <b>01</b>.</em>";
            $titulo .= "<br><br><strong>Nota:</strong> Se considera una solicitud de cancelación exitosa cuando regresa un valor de 200, sin embargo esto no asegura su cancelación.";

            $pregunta = "¿Desea enviar la solicitud de Cancelación de este CFDI?";
            $formquestion = array(
                array('type' => 'hidden', 'name' => 'token', 'id'=>'token', 'value' => $_SESSION["token"]),
                array('type' => 'hidden', 'name' => 'uuid_cancelar', 'id'=>'uuid_cancelar', 'value' => GETPOST("uuid")),
                // array('type' => 'other', 'name' => 'titulo', 'id'=>'titulo', 'label' =>'¿Desea cancelar este CFDI?'),
                array('type' => 'other', 'value' => '&nbsp;'),
                array('type' => 'select', 'name' => 'motivo', 'id'=>'motivo', 'label' => 'Motivo', 'values' => $lista_motivos, 'select_show_empty' => 0),
                array('type' => 'text', 'name' => 'uuid_sustitucion', 'id'=>'uuid_sustitucion', 'label' =>'Folio de Sustitución', 'moreattr' => 'placeholder="d0645efd-4abb-4c24-a0d7-7986e82cfedf"', 'size' => 35),
                array('type' => 'onecolumn', 'value' => $titulo)
            );

            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&rfc_emisor='.GETPOST("rfc_emisor").'&uuid='.GETPOST("uuid"), $pregunta, '', 'confirm_cancel', $formquestion, 0, 1, 320, 650);

            print $formconfirm;
        }

        if ($action == 'regen_pdf') {
            // DOL_URL_ROOT.'/cfdimx/regenpdf.php?facid=' . $object->id.'&uuid='.GETPOST("uuid").'&band=1&acep=Aceptar' //Anterior

            $formconfirm = $form->formconfirm(DOL_URL_ROOT.'/cfdimx/generaPDF_new.php?facid=' . $object->id.'&uuid='.GETPOST("uuid").'&band=1&route=regenpdf', $langs->trans('Regenerar PDF'), $langs->trans('¿Desea regenerar el PDF de este CFDI?'), 'confirm_regen', '', 0, 1);

            // $formconfirm = $form->formconfirm(DOL_URL_ROOT.'/cfdimx/generarPDF.php?facid=' . $object->id.'&uuid='.GETPOST("uuid").'&band=1&route=regenpdf', $langs->trans('Regenerar PDF'), $langs->trans('¿Desea regenerar el PDF de este CFDI?'), 'confirm_regen', '', 0, 1);

            // $formconfirm = $form->formconfirm(DOL_URL_ROOT.'/cfdimx/regenpdf.php?facid=' . $object->id.'&uuid='.GETPOST("uuid").'&band=1&route=regenpdf', $langs->trans('Regenerar PDF'), $langs->trans('¿Desea regenerar el PDF de este CFDI?'), 'confirm_regen', '', 0, 1);
            print $formconfirm;
        }

         if ($action == 'preview_pdf2') {
            $formconfirm = $form->formconfirm(DOL_URL_ROOT.'/cfdimx/pdfprevio_factura.php?facid=' . $object->id.'&route=regenpdf&previewpdf=previewpdf', $langs->trans('Previsualización PDF'), $langs->trans('Generar PDF previo de este CFDI?'), 'confirm_peview', '', 0, 1);

            print $formconfirm;
        }

        if ($action == 'preview_pdf') {
            $formconfirm = $form->formconfirm(DOL_URL_ROOT.'/cfdimx/generaPrevioPDF.php?facid=' . $object->id.'&route=regenpdf&previewpdf=previewpdf', $langs->trans('Previsualización PDF'), $langs->trans('¿Desea generar un PDF previo de este CFDI?'), 'confirm_peview', '', 0, 1);

            print $formconfirm;
        }

        if ($action == 'del_reten_man') {
            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&del_retencion='.GETPOST('del_retencion').'&tptre='.GETPOST('tptre'), $langs->trans('Eliminar retención'), $langs->trans('¿Desea eliminar esta retención?'), 'confirm_del_reten_man', '', 0, 1);
            print $formconfirm;
        }

        if ($action == 'consultar_estatus') {
            // $titulo = "<br><em>El Folio de Sustitución es obligatorio cuando el motivo es <b>01</b>.</em>";

            $titulo   = "Consultar Estatus CFDI";
            $emisor   = "<b>Emisor:</b> ".$conf->global->MAIN_INFO_SIREN;
            $receptor = "<b>Receptor:</b> ".$soc_rfc;
            $factura  = "<b>Factura:</b> ".$object->ref;
            $uuid     = "<b>UUID:</b> ".$uuid;
            $total    = "<b>Total:</b> ".number_format($object->total_ttc, 2);
            $pregunta = "¿Son correctos los Datos para la Consulta del Estatus de la Factura?";

            $formquestion = array(
                array('type' => 'onecolumn', 'value' => $emisor),
                array('type' => 'onecolumn', 'value' => $receptor),
                array('type' => 'onecolumn', 'value' => $factura),
                array('type' => 'onecolumn', 'value' => $uuid),
                array('type' => 'onecolumn', 'value' => $total),

                array('type' => 'onecolumn', 'value' => $pregunta)
            );

            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $titulo, '', 'confirm_estatus', $formquestion, 0, 1, 320, 600);

            print $formconfirm;
        }

        if($action == 'clasificar_factura'){
            $objectidnext = $object->getIdReplacingInvoice();

            if ($objectidnext) {
                $facturereplacement = new Facture($db);
                $facturereplacement->fetch($objectidnext);
                $statusreplacement = $facturereplacement->statut;
            }
            if ($objectidnext && $statusreplacement == 0) {
                print '<div class="error">'.$langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated").'</div>';
            } else {
                // Code
                $close[1]['code'] = 'badcustomer';
                $close[2]['code'] = 'abandon';
                // Help
                $close[1]['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
                $close[2]['label'] = $langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
                // Texte
                $close[1]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $object->ref), $close[1]['label'], 1);
                $close[2]['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"), $close[2]['label'], 1);
                // arrayreasons
                $arrayreasons[$close[1]['code']] = $close[1]['reason'];
                $arrayreasons[$close[2]['code']] = $close[2]['reason'];

                // Cree un tableau formulaire
                $formquestion = array(
                                    'text' => $langs->trans("ConfirmCancelBillQuestion"),
                                    array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"), 'values' => $arrayreasons),
                                    array('type' => 'text', 'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => 'CFDI Cancelado', 'morecss' => 'minwidth300')
                                );

                $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id, $langs->trans('CancelBill'), $langs->trans('ConfirmCancelBill', $object->ref), 'confirm_clasificar_factura', $formquestion, "yes", 1, 250);

                print $formconfirm;
            }
        }


        $result = $object->fetch($id, $ref);

        if(isset($conf->global->MAIN_MODULE_MULTICURRENCY)){
            $object->total_ht  = $object->multicurrency_total_ht;
            $object->total_tva = $object->multicurrency_total_tva;
            $object->total_ttc = $object->multicurrency_total_ttc;
        }

        if ($result > 0) {
            if($dol_version >= 14){
                if ($user->socid > 0 && $user->societe_id != $object->socid)
                    accessforbidden('', 0);
            }else{
                if ($user->societe_id > 0 && $user->societe_id != $object->socid)
                    accessforbidden('', 0);
            }

            $result = $object->fetch_thirdparty();

            $soc = new Societe($db);
            $soc->fetch($object->socid);

            //Aquí comienza la vista
            $head = facture_prepare_head($object);

            print dol_get_fiche_head($head, "tabfactclient", 'CFDI', -1, 'bill');

            $formconfirm = '';

            if (isset($msg_cfdi_final) && $msg_cfdi_final != "") {
                if($errores_factura != null){
                    $msg_cfdi_final .= "Validaciones del Comprobante<br>";
                    foreach ($errores_factura as $error_factura) {
                        $msg_cfdi_final .= $error_factura."<br>";
                    }
                }

                if($errores_conceptos != null){
                    $msg_cfdi_final .= "Validaciones de Conceptos<br>";
                    foreach ($errores_conceptos as $error_concepto) {
                        $msg_cfdi_final .= "<br>";
                        for ($i=0; $i < count($error_concepto); $i++) {
                            $msg_cfdi_final .= $error_concepto[$i]."<br>";
                        }
                    }
                }

                if($ban_timbrado == 1){
                    setEventMessage($msg_cfdi_final, 'mesgs');
                }else{
                    dol_htmloutput_errors($msg_cfdi_final);
                }
            }

            if(@$msg_retenciones != ""){
                dol_htmloutput_errors($msg_retenciones);
            }

            $validacion_cfdimx = $objFacturaCFDI->validarVersionDoli($conf->global->CFDIMX_V_MIN_DOLI, $conf->global->CFDIMX_V_MAX_DOLI);

            if($validacion_cfdimx != ""){
                print '<div class="error hideonsmartphone clearboth">';
                    print $validacion_cfdimx;
                print '</div>';
            }

            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                        $("#head_show").click(function() {
                            $("#tmporal_ref").show("fast");
                            $("#head_hide").show("fast");
                            $("#head_show").hide("fast");
                        });
                    });

                    jQuery(document).ready(function() {
                        $("#head_hide").click(function() {
                            $("#tmporal_ref").hide("fast");
                            $("#head_hide").hide("fast");
                            $("#head_show").show("fast");
                        });
                    });
                </script>

            <?php
            $totalpaye = $object->getSommePaiement();

            $linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

            $morehtmlref = '<div class="refidno">';
            // Ref invoice
            if ($object->status == $object::STATUS_DRAFT && !$mysoc->isInEEC() && !empty($conf->global->INVOICE_ALLOW_FREE_REF)) {
                $morehtmlref .= $form->editfieldkey("Ref", 'ref', $object->ref, $object, '', 'string', '', 0, 1);
                $morehtmlref .= $form->editfieldval("Ref", 'ref', $object->ref, $object, '', 'string', '', null, null, '', 1);
                $morehtmlref .= '<br>';
            }
            // Ref customer
            $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, '', 'string', '', 0, 1);
            $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, '', 'string', '', null, null, '', 1);

            // Thirdparty
            $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'customer');
            if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
                $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
            }

            // Project
            if (!empty($conf->projet->enabled)) {
                $langs->load("projects");
                $morehtmlref .= '<br>'.$langs->trans('Project').' ';
                if ($usercancreate) {
                    if ($action != 'classify') {
                        $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
                    }
                    if ($action == 'classify') {
                        $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                        $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                        $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                        $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                        $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                        $morehtmlref .= '</form>';
                    } else {
                        $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
                    }
                } else {
                    if (!empty($object->fk_project)) {
                        $proj = new Project($db);
                        $proj->fetch($object->fk_project);
                        $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
                        $morehtmlref .= $proj->ref;
                        $morehtmlref .= '</a>';
                    } else {
                        $morehtmlref .= '';
                    }
                }
            }
            $morehtmlref .= '</div>';
            $object->totalpaye = $totalpaye; // To give a chance to dol_banner_tab to use already paid amount to show correct status

            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '');

            print '<div class="fichecenter">';
                print '<div class="fichehalfleft">';

                    // Invoice content
                    print '<table class="noborder" width="100%">';
                        print '<tr class="liste_titre">';
                            print '<td colspan="2" align="center">';
                                print '<strong>';
                                    /*
                                    print '<button id="head_show" class="btn btn-link">';
                                        print '<span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>';
                                    print '</button>';

                                    print '<button id="head_hide" class="btn btn-link" style="display: none;">';
                                        print '<span class="fa fa-minus-circle valignmiddle btnTitle-icon"></span>';
                                    print '</button>';
                                    print '&nbsp;';*/
                                    print '<span class="fa fa-file-text-o valignmiddle btnTitle-icon"></span>';
                                    print '&nbsp;';
                                    print 'Datos del comprobante';
                                print '</strong>';
                            print '</td>';
                        print '</tr>';

                        // print '<tbody id="tmporal_ref" style="display: none;">';
                        print '<tbody">';
                            if (GETPOST('tdocument') && GETPOST('edittp') == 1) {
                                //    print GETPOST('tdocument');
                            	// print 'enra<br>';
                                $sql = "UPDATE " . MAIN_DB_PREFIX . "cfdimx_type_document SET tipo_document=" . GETPOST('tdocument') . " WHERE fk_facture=" . $id;
                                //print $sql;
                                $rsq = $db->query($sql);
                                // $sql2 = "UPDATE " . MAIN_DB_PREFIX . "facture SET type=" . GETPOST('tdocument') . " WHERE rowid=" . $id;
                                // print $sql2;
                                // $rsq2 = $db->query($sql2);

                                $sql = "DELETE FROM " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture=" . $id;
                                $rsq = $db->query($sql);
                                $sql = "DELETE FROM " . MAIN_DB_PREFIX . "cfdimx_retencionesdet WHERE factura_id=" . $id;
                                $rsq = $db->query($sql);
                                if ((GETPOST('tdocument') == 2 || GETPOST('tdocument') == 3)) {
                                    $sql = "SELECT rowid,total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                                        $sql = "SELECT rowid,multicurrency_total_ht as total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    }
                                    $retiva    = 0;
                                    $retisr    = 0;
                                    $resultset = $db->query($sql);
                                    while ($rsqq = $db->fetch_object($resultset)) {
                                        $isrprod = $rsqq->total_ht * 0.10;
                                        $ivaprod = $rsqq->total_ht * 0.106667;
                                        $sql     = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) VALUES(" . $id . "," . $rsqq->rowid . ",'" . round($rsqq->total_ht, 2) . "','002','Tasa','0.106667','" . round($ivaprod, 2) . "')";
                                        $rsq     = $db->query($sql);
                                        $sql     = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) VALUES(" . $id . "," . $rsqq->rowid . ",'" . round($rsqq->total_ht, 2) . "','001','Tasa','0.10','" . round($isrprod, 2) . "')";
                                        $rsq     = $db->query($sql);
                                        $retiva += $ivaprod;
                                        $retisr += $isrprod;
                                    }
                                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retenciones (factura_id,fk_facture,impuesto,importe) VALUES(" . $id . "," . $id . ",'IVA'," . round($retiva, 2) . ")";
                                    $rsq = $db->query($sql);
                                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retenciones (factura_id,fk_facture,impuesto,importe) VALUES(" . $id . "," . $id . ",'ISR'," . round($retisr, 2) . ")";
                                    $rsq = $db->query($sql);
                                    print "<script>location.href='facture.php?facid=" . $id . "'</script>";
                                }
                                if (GETPOST('tdocument') == 5) {
                                    $sql = "SELECT rowid,total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                                        $sql = "SELECT rowid,multicurrency_total_ht as total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    }
                                    $retiva    = 0;
                                    $resultset = $db->query($sql);
                                    while ($rsqq = $db->fetch_object($resultset)) {
                                        $ivaprod = $rsqq->total_ht * 0.04;
                                        $sql     = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) VALUES(" . $id . "," . $rsqq->rowid . ",'" . round($rsqq->total_ht, 2) . "','002','Tasa','0.04','" . round($ivaprod, 2) . "')";
                                        $rsq     = $db->query($sql);
                                        $retiva += $ivaprod;
                                    }
                                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retenciones (factura_id,fk_facture,impuesto,importe) VALUES(" . $id . "," . $id . ",'IVA'," . round($retiva, 2) . ")";
                                    $rsq = $db->query($sql);
                                    print "<script>location.href='facture.php?facid=" . $id . "'</script>";
                                }
                            }

                            if (GETPOST('tdocument') && GETPOST('edittp') == 2) {
                                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_type_document (fk_facture,tipo_document) VALUES (" . $id . "," . GETPOST('tdocument') . ")";
                                $rs  = $db->query($sql);
                                if (GETPOST('tdocument') == 2 || GETPOST('tdocument') == 3) {
                                    $sql = "SELECT rowid,total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                                        $sql = "SELECT rowid,multicurrency_total_ht as total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    }
                                    $retiva    = 0;
                                    $retisr    = 0;
                                    $resultset = $db->query($sql);
                                    while ($rsqq = $db->fetch_object($resultset)) {
                                        $isrprod = $rsqq->total_ht * 0.10;
                                        $ivaprod = $rsqq->total_ht * 0.106667;
                                        $sql     = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) VALUES(" . $id . "," . $rsqq->rowid . ",'" . round($rsqq->total_ht, 2) . "','002','Tasa','0.106667','" . round($ivaprod, 2) . "')";
                                        $rsq     = $db->query($sql);
                                        $sql     = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) VALUES(" . $id . "," . $rsqq->rowid . ",'" . round($rsqq->total_ht, 2) . "','001','Tasa','0.10','" . round($isrprod, 2) . "')";
                                        $rsq     = $db->query($sql);
                                        $retiva += $ivaprod;
                                        $retisr += $isrprod;
                                    }
                                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retenciones (factura_id,fk_facture,impuesto,importe) VALUES(" . $id . "," . $id . ",'IVA'," . round($retiva, 2) . ")";
                                    $rsq = $db->query($sql);
                                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retenciones (factura_id,fk_facture,impuesto,importe) VALUES(" . $id . "," . $id . ",'ISR'," . round($retisr, 2) . ")";
                                    $rsq = $db->query($sql);
                                    print "<script>location.href='facture.php?facid=" . $id . "'</script>";
                                }
                                if (GETPOST('tdocument') == 5) {
                                    $sql = "SELECT rowid,total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                                        $sql = "SELECT rowid,multicurrency_total_ht as total_ht FROM " . MAIN_DB_PREFIX . "facturedet WHERE fk_facture=" . $id;
                                    }
                                    $retiva    = 0;
                                    $resultset = $db->query($sql);
                                    while ($rsqq = $db->fetch_object($resultset)) {
                                        $ivaprod = $rsqq->total_ht * 0.04;
                                        $sql     = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) VALUES(" . $id . "," . $rsqq->rowid . ",'" . round($rsqq->total_ht, 2) . "','002','Tasa','0.04','" . round($ivaprod, 2) . "')";
                                        $rsq     = $db->query($sql);
                                        $retiva += $ivaprod;
                                    }
                                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_retenciones (factura_id,fk_facture,impuesto,importe) VALUES(" . $id . "," . $id . ",'IVA'," . round($retiva, 2) . ")";
                                    $rsq = $db->query($sql);
                                    print "<script>location.href='facture.php?facid=" . $id . "'</script>";
                                }
                            }

                            $sql   = "SELECT IFNULL(tipo_document,NULL) as tipo_document FROM " . MAIN_DB_PREFIX . "cfdimx_type_document WHERE fk_facture=" . $id;
                            $resp  = $db->query($sql);
                            if($resp){
                                $respp = $db->fetch_object($resp);
                                if (@$respp->tipo_document == NULL) {
                                    $sql   = "SELECT type FROM " . MAIN_DB_PREFIX . "facture WHERE rowid=" . $id;
                                    $resp  = $db->query($sql);
                                    $respp = $db->fetch_object($resp);
                                    if ($respp->type == 2) {
                                        $sql  = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_type_document (fk_facture,tipo_document) VALUES (" . $id . ",4)";
                                        $resp = $db->query($sql);
                                    }
                                }
                            }

                            if ($cfdi_tot > 0) {
                                print '<tr>';
                                    print '<td>Tipo de Documento CFDI</td>';
                                    $sql   = "SELECT IFNULL(tipo_document,NULL) as tipo_document FROM " . MAIN_DB_PREFIX . "cfdimx_type_document WHERE fk_facture=" . $id;
                                    $resp  = $db->query($sql);
                                    $respp = $db->fetch_object($resp);
                                    print '<td colspan="4">';
                                        if ($respp->tipo_document != NULL) {
                                            if ($respp->tipo_document == 1) {
                                                print "Factura Estándar";
                                            }
                                            if ($respp->tipo_document == 2) {
                                                print "Recibo de Honorarios";
                                            }
                                            if ($respp->tipo_document == 3) {
                                                print "Recibo de Arrendamiento";
                                            }
                                            if ($respp->tipo_document == 4) {
                                                print "Nota de Crédito";
                                            }
                                            if ($respp->tipo_document == 5) {
                                                print "Factura de Fletes";
                                            }
                                            if ($respp->tipo_document == 6) {
                                                print "Factura RIF";
                                            }
                                            if ($respp->tipo_document == 7) {
                                                print "Factura Traslado";
                                            }
                                            if ($respp->tipo_document == 8) {
                                                print "Factura Global";
                                            }
                                        } else {
                                            $sql   = "SELECT type FROM " . MAIN_DB_PREFIX . "facture WHERE rowid=" . $id;
                                            $resp  = $db->query($sql);
                                            $respp = $db->fetch_object($resp);
                                            if ($respp->type == 2) {
                                                print "Nota de Crédito";
                                            } else {
                                                print "Factura Estándar";
                                            }
                                        }
                                    print '</td>';
                                print '</tr>';
                            } else {
                                print '<tr>';
                                    $sql   = "SELECT IFNULL(tipo_document,NULL) as tipo_document FROM " . MAIN_DB_PREFIX . "cfdimx_type_document WHERE fk_facture=" . $id;
                                    $resp  = $db->query($sql);
                                    $respp = $db->fetch_object($resp);
                                    if (@$respp->tipo_document != NULL) {
                                        $edit_icon = '<td>';
                                            $edit_icon .= '<a href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=edit_type">'.img_edit($langs->trans('SetType'), 1).'</a>';
                                        $edit_icon .= '</td>';
                                    }

                                print '<td>
                                    <table width="100%" class="nobordernopadding">
                                        <tr>
                                            <td>Tipo de Documento CFDI</td>
                                            '.@$edit_icon.'
                                        </tr>
                                    </table>
                                </td>';
                                if (@$respp->tipo_document != NULL) {
                                    print '<td colspan="2">';
                                        if ($action == 'edit_type') {
                                            if ($respp->tipo_document != 4) {
                                                print '<form action="facture.php?facid=' . $id . '&edittp=1" method="POST">';
                                                    print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                                    print '<select name="tdocument" id="tdocument" >';
                                                        print '<option value="1">Factura Estándar</option>';
                                                        print '<option value="2">Recibo de Honorarios</option>';
                                                        print '<option value="3">Recibo de Arrendamiento</option>';
                                                        print '<option value="5">Factura de Fletes</option>';
                                                        print '<option value="6">Factura RIF</option>';
                                                        print '<option value="7">Factura Traslado</option>';
                                                        print '<option value="8">Factura Global</option>';
                                                    print '</select>';
                                                    print '<input class="button" type="submit" value="Editar">';
                                                print '</form>';
                                            }
                                        }else {
                                            if ($respp->tipo_document == 1) {
                                                $label = "Factura Estándar";
                                            }
                                            if ($respp->tipo_document == 2) {
                                                $label = "Recibo de Honorarios";
                                            }
                                            if ($respp->tipo_document == 3) {
                                                $label = "Recibo de Arrendamiento";
                                            }
                                            if ($respp->tipo_document == 4) {
                                                $label = "Nota de Crédito";
                                            }
                                            if ($respp->tipo_document == 5) {
                                                $label = "Factura de Fletes";
                                            }
                                            if ($respp->tipo_document == 6) {
                                                $label = "Factura RIF";
                                            }
                                            if ($respp->tipo_document == 7) {
                                                $label = "Factura Traslado";
                                            }
                                            if ($respp->tipo_document == 8) {
                                                $label = "Factura Global";
                                            }
                                            print $label;
                                        }
                                    print '</td>';
                                }else {
                                    print '<td colspan="4">';
                                        print '<form action="facture.php?facid=' . $id . '&edittp=2" method="POST">';
                                            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                            print '<select name="tdocument" id="tdocument">';
                                                print '<option value="1">Factura Estándar</option>';
                                                print '<option value="2">Recibo de Honorarios</option>';
                                                print '<option value="3">Recibo de Arrendamiento</option>';
                                                print '<option value="5">Factura de Fletes</option>';
                                                print '<option value="6">Factura RIF</option>';
                                                print '<option value="7">Factura Traslado</option>';
                                                print '<option value="8">Factura Global</option>';
                                            print '</select>';
                                            print '<input class="button" type="submit" value="Seleccionar">';
                                        print '</form>';
                                    print '</td>';
                                }
                                print '</tr>';
                            }

                            // Date invoice
                            print '<tr>';
                                print '<td>';
                                    print '<table class="nobordernopadding" width="100%">';
                                        print '<tr>';
                                            print '<td>' . $langs->trans('Date') . '</td>';
                                                if ($object->type != 2 && $action != 'editinvoicedate' && $object->brouillon && $user->rights->facture->creer)
                                                    print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editinvoicedate&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetDate'), 1) . '</a></td>';
                                        print '</tr>';
                                    print '</table>';
                                print '</td>';

                                print '<td colspan="3">';
                                    if ($object->type != 2) {
                                        if ($action == 'editinvoicedate') {
                                            $form->form_date($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->date, 'invoicedate');
                                        } else {
                                            print dol_print_date($object->date, 'daytext');
                                        }
                                    } else {
                                        print dol_print_date($object->date, 'daytext');
                                    }
                                print '</td>';
                            print '</tr>';

                            // Date payment term
                            print '<tr>';
                                print '<td>';
                                    print '<table class="nobordernopadding" width="100%">';
                                        print '<tr>';
                                            print '<td>' . $langs->trans('DateMaxPayment') . '</td>';
                                            if ($object->type != 2 && $action != 'editpaymentterm' && $object->brouillon && $user->rights->facture->creer)
                                                print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editpaymentterm&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetDate'), 1) . '</a></td>';
                                        print '</tr>';
                                    print '</table>';
                                print '</td>';
                                print '<td colspan="3">';
                                    if ($object->type != 2) {
                                        if ($action == 'editpaymentterm') {
                                            $form->form_date($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->date_lim_reglement, 'paymentterm');
                                        } else {
                                            print dol_print_date($object->date_lim_reglement, 'daytext');
                                            if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && !$object->paye && $object->statut == 1 && !$object->am)
                                                print img_warning($langs->trans('Late'));
                                        }
                                    } else {
                                        print '&nbsp;';
                                    }
                                print '</td>';
                            print '</tr>';

                            // Mode de reglement
                            $pagos_detalles = new ComplementoPagos($db);
                            print '<tr>';
                                print '<td>';
                                    print '<table class="nobordernopadding" width="100%">';
                                        print '<tr>';
                                            print '<td class="fieldrequired">';
                                                print 'Forma de Pago SAT';
                                            print '</td>';
                                        print '</tr>';
                                    print '</table>';
                                print '</td>';

                                print '<td>';
                                    print $pagos_detalles->obtener_catalogo($object->mode_reglement_code, 'formpago', 5, 1);
                                print '</td>';
                            print '</tr>';

                            // Conditions de reglement
                            print '<tr>';
                                print '<td>';
                                    print '<table class="nobordernopadding" width="100%">';
                                        print '<tr>';
                                            print '<td>';
                                                print $langs->trans('PaymentConditionsShort');
                                            print '</td>';
                                            if ($object->type != 2 && $action != 'editconditions' && $object->brouillon && $user->rights->facture->creer)
                                                print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetConditions'), 1) . '</a></td>';
                                        print '</tr>';
                                    print '</table>';
                                print '</td>';

                                print '<td colspan="3">';
                                    if ($object->type != 2) {
                                        if ($action == 'editconditions') {
                                            $form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id');
                                        } else {
                                            $form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'none');
                                        }
                                    } else {
                                        print '&nbsp;';
                                    }
                                print '</td>';
                            print '</tr>';

                            // Metodo de Pago
                            print '<tr>';
                                print '<td class="fieldrequired">';
                                    print '<table class="nobordernopadding" width="100%">';
                                        print '<tr>';
                                            print '<td>';
                                            print 'Metodo de Pago';
                                            print '</td>';
                                        print '</tr>';
                                    print '</table>';
                                print '</td>';
                                print '<td colspan="3">';
                                    if($object->array_options["options_formpagcfdi"] != ''){
                                        if($object->array_options["options_formpagcfdi"] == 'PUE'){
                                            print 'PUE - Pago en una sola exhibición';
                                        }else{
                                            print 'PPD - Pago en parcialidades o diferido';
                                        }
                                    }else{
                                        print '&nbsp;';
                                    }
                                print '</td>';
                            print '</tr>';

                            #Exportacion
                            if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
                                print '<tr>';
                                    print '<td class="fieldrequired">';
                                        print '<table class="nobordernopadding" width="100%">';
                                            print '<tr>';
                                                print '<td>';
                                                print 'Exportación';
                                                print '</td>';
                                            print '</tr>';
                                        print '</table>';
                                    print '</td>';
                                    print '<td colspan="3">';
                                        print $pagos_detalles->obtener_catalogo($object->array_options["options_clave_expor"], 'exportacion', 6, 1);

                                        // if($object->array_options["options_clave_expor"] != ''){
                                        //     print array_search($object->array_options["options_clave_expor"], array("01 - No Aplica" => "01", "02 - Definitiva" => "02", "03 - Temporal" => "03"));
                                        // }else{
                                        //     print '&nbsp;';
                                        // }
                                    print '</td>';
                                print '</tr>';
                            }

                            // Cuenta
                            // $sql   = "SELECT * FROM " . MAIN_DB_PREFIX . "societe_rib WHERE default_rib=1 AND fk_soc = " . $soc->id;
                            // $resql = $db->query($sql);
                            // $nmc   = $db->fetch_object($resql);
                            // print '<tr>';
                            //     print '<td>Cuenta:</td>';
                            //     if (DOL_VERSION < 8) {
                            //         $desc = $form->textwithpicto($nmc->number, "Nota: El valor de este dato es el correspondiente al campo Número de Cuenta de Pago de la factura electrónica. No es obligatorio.", 1, 'help', '', 0, 3) . ' <a href="../societe/rib.php?socid=' . $soc->id . '">Modificar Valor</a></td></tr>';
                            //         print '<td colspan="3">' . $desc . '</td>';
                            //     } else {
                            //         @$desc = $form->textwithpicto($nmc->number, "Nota: El valor de este dato es el correspondiente al campo Número de Cuenta de Pago de la factura electrónica. No es obligatorio.", 1, 'help', '', 0, 3) . ' <a href="../societe/paymentmodes.php?socid=' . $soc->id . '">Modificar Valor</a></td></tr>';
                            //         print '<td colspan="3">' . $desc . '</td>';
                            //     }
                            // print '</tr>';

                            //Divisa
                            print '<tr>';
                                print '<td>Divisa</td>';
                                print '<td>';

                                if ($cfdi_tot > 0) {
                                    if (!empty($divisa)) {
                                        echo $divisa;
                                    }
                                }else{
                                    if (@$conf->global->MAIN_MODULE_MULTICURRENCY) {
                                        $sql = "SELECT multicurrency_code AS divisa FROM " . MAIN_DB_PREFIX . "facture WHERE rowid=" . $id;
                                        $ra  = $db->query($sql);
                                        $rb  = $db->fetch_object($ra);
                                        print $rb->divisa;
                                        $conf->currency = $rb->divisa;
                                        print '<input type="hidden" id="osd" name="osd" value="' . $conf->global->MAIN_MODULE_MULTICURRENCY . '">';
                                    } else {
                                        if (!empty($_REQUEST['osd'])) {
                                            $osd = $_REQUEST['osd'];
                                        } else {
                                            $osd = $conf->currency;
                                        }
                                            print '<select id="osd" name="osd" onchange=location.href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;osd="+this.value>';
                                                print '<option value="">' . $osd . '</option>';
                                                print '<option value=""></option>';
                                                print '<option ' . $selectedMXN . 'value="MXN">MXN</option>';
                                                print '<option ' . $selectedUSD . 'value="USD">USD</option>';
                                            print '</select>';
                                        print '</td>';
                                    }
                                }
                            print '</tr>';
                        print '</tbody>';
                    print '</table>';
                    print '<br>';

                    #Agregar Complemento
                    if ($object->statut == 1 || $object->statut == 2) {
                        $complementos_cfdi = $objComplementos->complementosCFDI($object->id);

                        print '<table class="noborder" width="100%">';
                            print '<tr class="liste_titre">';
                                print '<td colspan="2" align="center">';
                                    if($uuid == ""){
                                        print '<a href="complementos_cfdimx.php?facid='.$object->id.'">';
                                            print '<b>';
                                                print '<span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span>&nbsp;';
                                                print 'Agregar Complementos CFDI';
                                            print '</b>';
                                        print '</a>';
                                    }else{
                                        if($complementos_cfdi != null){
                                            print '<a href="complementos_cfdimx.php?facid='.$object->id.'">';
                                                print '<span class="fa fa-file-text-o valignmiddle btnTitle-icon"></span>&nbsp;';
                                                print '<b>Ver Complementos CFDI</b>';
                                            print '</a>';
                                        }else{
                                            print '<span class="fa fa-file-text-o valignmiddle btnTitle-icon"></span>&nbsp;';
                                            print '<b>Complementos CFDI</b>';
                                        }
                                    }
                                print '</td>';
                            print '</tr>';

                            if($complementos_cfdi != null){
                                $cce_add         = ($complementos_cfdi["cce"] == 1 ? 'on' : 'off');
                                $carta_porte_add = ($complementos_cfdi["carta_porte"] == 1 ? 'on' : 'off');
                                $cfdi_rel_add    = ($complementos_cfdi["cfdi_rel"] > 0 ? 'on' : 'off');

                                $cce_titulo         = ($complementos_cfdi["cce"] == 1 ? 'Comercio Exterior Agregado a la Factura' : 'Comercio Exterior No Agregado a la Factura');
                                $carta_porte_titulo = ($complementos_cfdi["carta_porte"] == 1 ? 'Carta Porte agregada a la Factura' : 'Carta Porte No Agregada a la Factura');
                                $cfdi_rel_titulo    = ($complementos_cfdi["cfdi_rel"] > 0 ? 'CFDI Relacionados Agregados a la Factura' : 'CFDI Relacionados No Agregados a la Factura');

                                print '<tbody>';
                                    print '<tr>';
                                        print '<td colspan="2">';
                                            print '<table class="nobordernopadding" width="100%">';
                                                print '<thead>';
                                                    print '<tr>';
                                                        print '<th>CFDI Relacionados</th>';
                                                        print '<th>Comercio Exterior</th>';
                                                        print '<th>Carta Porte</th>';
                                                    print '</tr>';
                                                print '</thead>';

                                                print '<tbody>';
                                                    print '<td align="center">';
                                                        print img_picto($cfdi_rel_titulo, $cfdi_rel_add);
                                                    print '</td>';

                                                    print '<td align="center">';
                                                        print img_picto($cce_titulo, $cce_add);
                                                    print '</td>';

                                                    print '<td align="center">';
                                                        print img_picto($carta_porte_titulo, $carta_porte_add);
                                                    print '</td>';


                                                print '</tbody>';
                                            print '</table>';
                                        print '</td>';
                                    print '</tr>';
                                print '</tbody>';
                            }else{
                                if($uuid != ""){
                                    print '<tr>';
                                        print '<td>';
                                            print '<div class="info hideonsmartphone clearboth">';
                                                echo "No se registraron complementos para esta factura";
                                            print '</div>';
                                        print '</td>';
                                    print '</tr>';
                                }
                            }
                        print '</table>';
                        print '<br>';
                    }

                    if ($object->statut != 0) {
                        print '<table class="noborder" width="100%">';
                            #Datos de timbrado
                            if((int)DOL_VERSION == 10){
                                print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                    print '<td style="border: solid 1px #990000;" colspan="2" align="center">';
                                        print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>&nbsp;';
                                        print '<b>Datos de Timbrado</b>';
                                    print '</td>';
                                print '</tr>';
                            }else{
                                print '<tr style="background: #990000; color: white;">';
                                    print '<td style="border: solid 1px #990000;" colspan="2" align="center">';
                                        print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>&nbsp;';
                                        print '<b>Datos de Timbrado</b>';
                                    print '</td>';
                                print '</tr>';
                            }

                            print '<tr>';
                                print '<td colspan="2" style="border: solid 1px #990000;">';
                                    print '<table width="100%" class="noborder">';
                                        if($fecha_consulta_estatus != ""){
                                            print '<tr>';
                                                print '<td><strong>Estado CFDI</strong></td>';
                                                print '<td>'.$estado_cfdi.'</td>';
                                            print '</tr>';

                                            print '<tr>';
                                                print '<td><strong>Estatus de cancelación</strong></td>';
                                                print '<td>'.$estatus_cancelacion.'</td>';
                                            print '</tr>';

                                            $titulo_consulta = "Consultar el Estatus de la Factura";

                                            print '<tr>';
                                                print '<td><strong>Última Consulta de Estatus</strong></td>';
                                                print '<td>';
                                                    print '<form method="post">';
                                                        print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                                        print $fecha_consulta_estatus;
                                                        print '<input type="hidden" name="action" value="consultar_estatus">';
                                                        print '<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x" title="'.$titulo_consulta.'"><span class="fa fa-search"></span></button>';
                                                    print '</form>';
                                                print '</td>';
                                            print '</tr>';
                                        }else{
                                            if(strcmp("Sin timbrar", $estado_cfdi) == 0){
                                                print '<tr>';
                                                    print '<td><strong>Estado CFDI</strong></td>';
                                                    print '<td>'.$estado_cfdi.'</td>';
                                                print '</tr>';

                                            }else{
                                                $titulo_consulta = "Consultar el Estatus de la Factura";

                                                print '<tr>';
                                                    print '<td><strong>Consultar Estatus</strong></td>';
                                                    print '<td>';
                                                        print '<form method="post">';
                                                            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                                            print $fecha_consulta_estatus;
                                                            print '<input type="hidden" name="action" value="consultar_estatus">';
                                                            print '<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x" title="'.$titulo_consulta.'"><span class="fa fa-search"></span></button>';
                                                        print '</form>';
                                                    print '</td>';
                                                print '</tr>';
                                            }
                                        }

                                        print '<tr>';
                                            print '<td><strong>UUID</strong></td>';
                                            print '<td>' . $uuid . '</td>';
                                        print '</tr>';

                                        $modo_timbrado_desc = ($modo_timbrado == 1) ? "Producción" : "Pruebas";
                                        print '<tr>';
                                            print '<td><strong>Modo de Timbrado Activo</strong></td>';
                                            print '<td>' . $modo_timbrado_desc . '</td>';
                                        print '</tr>';

                                        print '<tr>';
                                            print '<td><strong>Versión de CFDI Activa</strong></td>';
                                            print '<td>' . $conf->global->CFDIMX_VERSION_SAT . '</td>';
                                        print '</tr>';

                                        print '<tr>';
                                            print '<td><strong>Folios Disponibles</strong></td>';
                                            print '<td>' . $folios_disponibles . '</td>';
                                        print '</tr>';

                                        print '<tr>';
                                            print '<td><strong>Folios Timbrados</strong></td>';
                                            print '<td>' . $folios_timbrados . '</td>';
                                        print '</tr>';
                                    print '</table>';
                                print '</td>';
                            print '</tr>';
                        print '</table>';
                        print '<br>';
                    }

                    $sql_solicitud = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_solicitud_cancelacion WHERE fk_facture = ".$object->id." ORDER BY rowid DESC LIMIT 1";
                    $res_solicitud = $db->query($sql_solicitud);
                    $num_solicitud = $db->num_rows($res_solicitud);

                    if($num_solicitud > 0){
                        print '<table class="noborder" width="100%">';
                            print '<tr class="liste_titre">';
                                print '<td colspan="2" align="center" >';
                                    print '<span class="fa fa-file-pdf-o valignmiddle btnTitle-icon"></span>&nbsp;';
                                    print '&nbsp;';
                                    print '<b>Solicitud de Cancelación</b>';
                                print '</td>';
                            print '</tr>';

                            $obj_cancelacion = $db->fetch_object($res_solicitud);

                            print '<tr>';
                                print '<th style="background-color: #990000; color: white;"><strong>UUID</strong></th>';
                                print '<td>'.$obj_cancelacion->uuid.'</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<th style="background-color: #990000; color: white;"><strong>Estatus</strong></th>';
                                print '<td>'.$obj_cancelacion->uuidStatusCode.'</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<th style="background-color: #990000; color: white;"><strong>Acuse</strong></th>';
                                print '<td>';
                                    $ruta = DOL_URL_ROOT.'/document.php?modulepart=facture&amp;file='.$object->ref.'/';
                                    $ruta .= $obj_cancelacion->archivo;

                                    print '<a class="documentdownload paddingright" href="'.$ruta.'" target="_blank">';
                                        print '<i class="fa fa-file-pdf-o paddingright"></i>';
                                        print $obj_cancelacion->archivo;
                                    print '</a>';
                                print '</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<th style="background-color: #990000; color: white;"><strong>Fecha</strong></th>';
                                print '<td>'.$obj_cancelacion->fecha.'&nbsp;'.$obj_cancelacion->hora.'</td>';
                            print '</tr>';

                        print '</table>';
                        print '<br>';
                    }
                print '</div>'; //Fiche left

                print '<div class="fichehalfright">';
                    print '<table class="noborder" width="100%" >';
                        print '<tr class="liste_titre">';
                            print '<td colspan="4" align="center" >';
                                print '<span class="fa fa-money valignmiddle btnTitle-icon"></span>';
                                print '&nbsp;';
                                print '<b>Montos del comprobante</b>';
                            print '</td>';
                        print '</tr>';

                        //Descuentos
                        // Relative and absolute discounts
                        $addrelativediscount = '<a href="' . DOL_URL_ROOT . '/comm/remise.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("EditRelativeDiscounts") . '</a>';
                        $addabsolutediscount = '<a href="' . DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("EditGlobalDiscounts") . '</a>';
                        $addcreditnote       = '<a href="' . DOL_URL_ROOT . '/compta/facture.php?action=create&socid=' . $soc->id . '&type=2&backtopage=' . urlencode($_SERVER["PHP_SELF"]) . '?facid=' . $object->id . '">' . $langs->trans("AddCreditNote") . '</a>';

                        print '<tr>';
                            print '<td colspan="2">';
                                print $langs->trans('Discounts');
                            print '</td>';

                            print '<td>';

                                if($dol_version >= 14){
                                    if ($soc->remise_percent)
                                        print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_percent);
                                    else
                                        print $langs->trans("CompanyHasNoRelativeDiscount");
                                }else{
                                    if ($soc->remise_client)
                                        print $langs->trans("CompanyHasRelativeDiscount", $soc->remise_client);
                                    else
                                        print $langs->trans("CompanyHasNoRelativeDiscount");
                                }

                                if (@$absolute_discount > 0) {
                                    print '. ';
                                    if ($object->statut > 0 || $object->type == 2 || $object->type == 3) {
                                        if ($object->statut == 0) {
                                            print $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                                            print '. ';
                                        } else {
                                            if ($object->statut < 1 || $object->type == 2 || $object->type == 3) {
                                                $text = $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                                                print '<br>' . $text . '.<br>';
                                            } else {
                                                $text  = $langs->trans("CompanyHasAbsoluteDiscount", price($absolute_discount), $langs->transnoentities("Currency" . $conf->currency));
                                                $text2 = $langs->trans("AbsoluteDiscountUse");
                                                print $form->textwithpicto($text, $text2);
                                            }
                                        }
                                    } else {
                                        // Remise dispo de type remise fixe (not credit note)
                                        print '<br>';
                                        $form->form_remise_dispo($_SERVER["PHP_SELF"] . '?facid=' . $object->id, GETPOST('discountid'), 'remise_id', $soc->id, $absolute_discount, $filterabsolutediscount, $resteapayer, ' (' . $addabsolutediscount . ')');
                                    }
                                } else {
                                    if (@$absolute_creditnote > 0) // If not, link will be added later
                                    {
                                        if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
                                            print ' (' . $addabsolutediscount . ')<br>';
                                        else
                                            print '.<br>';
                                    } else
                                        print '.<br>';
                                }

                                if (@$absolute_creditnote > 0) {
                                    // If validated, we show link "add credit note to payment"
                                    if ($object->statut != 1 || $object->type == 2 || $object->type == 3) {
                                        if ($object->statut == 0 && $object->type != 3) {
                                            $text = $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency));
                                            print $form->textwithpicto($text, $langs->trans("CreditNoteDepositUse"));
                                        } else {
                                            print $langs->trans("CompanyHasCreditNote", price($absolute_creditnote), $langs->transnoentities("Currency" . $conf->currency)) . '.';
                                        }
                                    } else {
                                        // Remise dispo de type avoir
                                        if (!$absolute_discount)
                                            print '<br>';
                                        //$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, $resteapayer);
                                        $form->form_remise_dispo($_SERVER["PHP_SELF"] . '?facid=' . $object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, 0); // We must allow credit not even if amount is higher
                                    }
                                }

                                if (!@$absolute_discount && !@$absolute_creditnote) {
                                    print $langs->trans("CompanyHasNoAbsoluteDiscount");
                                    if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
                                        print ' (' . $addabsolutediscount . ')<br>';
                                    else
                                        print '. ';
                                }
                            print '</td>';
                        print '</tr>';

                        //nueva val not credito
                        $total_ht = 0;
                        $total_tva = 0;
                        $total_ttc = 0;

                        if($object->total_ht == 0){
                            $total_ht = $object->lines[0]->total_ht;
                        }else{
                            $total_ht = $object->total_ht;
                        }

                        if($object->total_tva == 0){
                            $total_tva = $object->lines[0]->total_tva;
                        }else{
                            $total_tva = $object->total_tva;
                        }

                        if($object->total_ttc == 0){
                            $total_ttc = $object->lines[0]->total_ttc;
                        }else{
                            $total_ttc = $object->total_ttc;
                        }

                        // Amount
                        print '<tr>';
                            print '<td colspan="2">' . $langs->trans('AmountHT') . '</td>';
                            print '<td align="center">';
                                print price($total_ht, 0, '', 1, -1, -1, $object->multicurrency_code);
                            print '</td>';
                        print '</tr>';

                        print '<tr>';
                            print '<td colspan="2">' . $langs->trans('AmountVAT') . '</td>';
                            print '<td align="center">';
                                print price($total_tva, 0, '', 1, -1, -1, $object->multicurrency_code);
                            print '</td>';
                        print '</tr>';

                        // Amount Local Taxes
                        if($dol_version >= 14){
                            if ($mysoc->pays == 'ES') {
                                if ($mysoc->localtax1_assuj == "1") //Localtax1 RE
                                    {
                                    print '<tr>';
                                        print '<td colspan="2">' . $langs->transcountry("AmountLT1", $mysoc->pays) . '</td>';
                                        print '<td align="center">';
                                            print price($object->total_localtax1, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';
                                }
                                if ($mysoc->localtax2_assuj == "1") //Localtax2 IRPF
                                    {
                                    print '<tr>';
                                        print '<td colspan="2">' . $langs->transcountry("AmountLT2", $mysoc->pays) . '</td>';
                                        print '<td align="center">';
                                            print price($object->total_localtax2, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';
                                }
                            }
                        }else{
                            if ($mysoc->pays_code == 'ES') {
                                if ($mysoc->localtax1_assuj == "1") //Localtax1 RE
                                    {
                                    print '<tr>';
                                        print '<td colspan="2">' . $langs->transcountry("AmountLT1", $mysoc->pays_code) . '</td>';
                                        print '<td align="center">';
                                            print price($object->total_localtax1, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';
                                }
                                if ($mysoc->localtax2_assuj == "1") //Localtax2 IRPF
                                    {
                                    print '<tr>';
                                        print '<td colspan="2">' . $langs->transcountry("AmountLT2", $mysoc->pays_code) . '</td>';
                                        print '<td align="center">';
                                            print price($object->total_localtax2, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';
                                }
                            }
                        }

                        //Consulta Retenciones
                        $sqm  = "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '" . $db->database_name . "' AND table_name = '" . MAIN_DB_PREFIX . "cfdimx_config_retenciones_locales'";
                        $rqm  = $db->query($sqm);
                        $rqsm = $db->fetch_object($rqm);
                        if ($rqsm->count > 0) {
                            $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $_REQUEST["facid"];
                            $resqm = $db->query($sql);
                            if ($resqm) {
                                $cfdi_m = $db->num_rows($resqm);
                                $i      = 0;
                                if ($cfdi_m > 0) {
                                    while ($i < $cfdi_m) {
                                        $obm = $db->fetch_object($resqm);
                                        if ($cfdi_tot < 1) {
                                            print '<tr>';
                                                print '<td colspan="2">Ret. ' . $obm->codigo . '</td>';
                                                print '<td align="center">';
                                                    print price($obm->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                                print '</td>';
                                            print '</tr>';
                                            $object->total_ttc = $object->total_ttc - $obm->importe;
                                            $object->total_ttc = str_replace(",", "", number_format($object->total_ttc, 2));
                                        } else {
                                            print '<tr>';
                                                print '<td colspan="2">Ret. ' . $obm->codigo . '</td>';
                                                print '<td align="center">';
                                                    print price($obm->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                                print '</td>';
                                            print '</tr>';
                                        }
                                        $i++;
                                    }
                                }
                            }
                        }

                        $sql  = "SELECT count(*) as exist FROM " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture=" . $id;
                        $rsq  = $db->query($sql);
                        $rsqq = $db->fetch_object($rsq);
                        if ($rsqq->exist > 0) {
                            $sql    = "SELECT impuesto,importe FROM " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture=" . $id;
                            $rsq    = $db->query($sql);
                            $restar = 0;
                            while ($rsqq = $db->fetch_object($rsq)) {
                                $restar = $restar + $rsqq->importe;
                                print '<tr>';
                                    print '<td colspan="2">Ret. de ' . $rsqq->impuesto . '</td>';
                                    print '<td align="center">';
                                        print price($rsqq->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                    print '</td>';
                                print '</tr>';
                            }
                            if ($cfdi_tot < 1) {
                                $total_res = $object->total_ttc - $restar;

                                print '<tr>';
                                    print '<td colspan="2">' . $langs->trans('AmountTTC') . '</td>';
                                    print '<td align="center">';
                                        print price($total_res, 0, '', 1, -1, -1, $object->multicurrency_code);
                                    print '</td>';
                                print '</tr>';
                            } else {
                                print '<tr>';
                                    print '<td colspan="2">' . $langs->trans('AmountTTC') . '</td>';
                                    print '<td align="center">';
                                        print price($object->total_ttc, 0, '', 1, -1, -1, $object->multicurrency_code);
                                    print '</td>';
                                print '</tr>';
                            }
                        } else {
                            print '<tr>';
                                print '<td colspan="2">' . $langs->trans('AmountTTC') . '</td>';
                                print '<td align="center">';
                                    print price($total_ttc, 0, '', 1, -1, -1, $object->multicurrency_code);
                                print '</td>';
                            print '</tr>';
                        }
                    print '</table>';
                    print '<br>';

                    #Retenciones - Son como retenciones manuales (Inicio)
                    if ($object->statut != 0) {
                        print '<table class="noborder" width="100%" >';
                            print '<tr class="liste_titre">';
                                print '<td align="center">';
                                    print '<span class="fa fa-list-alt valignmiddle btnTitle-icon"></span>';
                                    print '&nbsp;';
                                    print '<b>Retenciones</b>';
                                print '</td>';
                            print '</tr>';

                            print '<tr>';
                                print '<td>';
                                    if ($cfdi_tot < 1) {
                                        print '<form method="post">';
                                            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                            print '<strong>Añade una Retención:</strong><br>';
                                            print '<input type="hidden" name="action" value="add_reten_man">';
                                            print '<div align="center">';
                                                print 'Impuesto:';

                                                print '<select name="impuesto">';
                                                    print '<option value="002">IVA</option>';
                                                    print '<option value="001">ISR</option>';
                                                print '</select>';
                                                    print 'Tasa (c_TasaOCuota): <input type="text" name="importe" size="5">';
                                                print '<input type="submit" class="button" name="envRetencion" value="Registrar">';
                                            print '</div>';
                                        print '</form>';
                                        print '<br>';

                                        $sql_ret = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture = " . $_REQUEST["facid"];
                                        $resql = $db->query($sql_ret);
                                        if ($resql) {
                                            $cfdi_tott = $db->num_rows($resql);
                                            $i         = 0;
                                            if ($cfdi_tott > 0) {
                                                print '<table class="noborder" width="100%">';
                                                    print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                                        print '<th align="center"><strong>Impuesto</strong></th>';
                                                        print '<th align="right"><strong>Importe</strong></th>';
                                                        print '<th align="center"><strong>Acción</strong></th>';
                                                    print '</tr>';

                                                while ($i < $cfdi_tott) {
                                                    $obj = $db->fetch_object($resql);
                                                    print '<tr>';
                                                        print '<td align="center">' . $obj->impuesto . '</td>';
                                                        print '<td align="right">';
                                                            print price($obj->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                                        print '</td>';

                                                        print '<td align="center">';
                                                            print '<a href="?facid=' . $_REQUEST["facid"] . '&del_retencion=' . $obj->retenciones_id . '&tptre=' . $obj->impuesto . '&action=del_reten_man">';
                                                                print img_delete('Eliminar Retención de '.$obj->impuesto);
                                                            print '</a>';
                                                        print '</td>';
                                                    print '</tr>';
                                                    $i++;
                                                }

                                                print '</table>';
                                            }
                                        }
                                    } else {
                                        $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture = " . $_REQUEST["facid"];
                                        $resql = $db->query($sql);
                                        if ($resql) {
                                            $cfdi_tott = $db->num_rows($resql);
                                            $i         = 0;

                                            if ($cfdi_tott > 0) {
                                                print '<table class="noborder" width="100%">';
                                                    print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                                        print '<th align="center"><strong>Impuesto</strong></th>';
                                                        print '<th align="right"><strong>Importe</strong></th>';
                                                    print '</tr>';

                                                while ($i < $cfdi_tott) {
                                                    $obj = $db->fetch_object($resql);

                                                    print '<tr>';
                                                        print '<td align="center">' . $obj->impuesto . '</td>';
                                                        print '<td align="right">';
                                                            print price($obj->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                                        print '</td>';
                                                    print '</tr>';
                                                    $i++;
                                                }

                                                print '</table>';
                                            }else{
                                                print '<div class="info hideonsmartphone clearboth">';
                                                    echo "No se registraron retenciones para esta factura";
                                                print '</div>';
                                            }
                                        }
                                    }
                                print '</td>';
                            print '</tr>';
                        print '</table>';
                        print '<br>';
                    }
                    #Retenciones - Son como retenciones manuales (Fin)

                    #Retenciones locales - Se agregan desde la configuración del módulo en Dolibarr (Inicio)
                    if ($object->statut != 0) {
                        print '<table class="noborder" width="100%" >';
                            print '<tr class="liste_titre">';
                                print '<td align="center">';
                                    print '<span class="fa fa-list-alt valignmiddle btnTitle-icon"></span>';
                                    print '&nbsp;';
                                    print '<b>Retenciones Locales</b>';
                                print '</td>';
                            print '</tr>';


                        $sqm = "SELECT rowid,cod,descripcion,tasa FROM " . MAIN_DB_PREFIX . "cfdimx_config_retenciones_locales WHERE entity=" . $conf->entity;
                        $rqm = $db->query($sqm);
                        $nrm = $db->num_rows($rqm);

                        if ($nrm > 0) {
                            print '<tr>';
                                print '<td>';
                                    if ($cfdi_tot < 1) {

                                        print '<form method="post">';
                                            print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                            print '<input type="hidden" name="facid" id="facid" value="' . $_REQUEST["facid"] . '">';
                                            print '<strong>Añade una Retención Local:</strong><br>';
                                            print '<div align="center">';
                                                    print 'Impuesto:';
                                                    print '<select name="retlocal">';
                                                        while ($rms = $db->fetch_object($rqm)) {
                                                            print "<option value='" . $rms->rowid . "'>" . $rms->cod . " - " . $rms->tasa . "%</option>";
                                                        }
                                                    print '</select>';
                                                print 'Tasa (c_TasaOCuota): <input type="text" name="importe" size="5">';
                                                print '<input type="submit" class="button" name="envRetencionLocal" value="Registrar">';
                                            print '</div>';
                                        print '</form>';
                                        print '<br>';

                                        $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $_REQUEST["facid"];
                                        $resql = $db->query($sql);
                                        if ($resql) {
                                            $cfdi_t = $db->num_rows($resql);
                                            $i      = 0;
                                            if ($cfdi_t > 0) {

                                                print '<table class="noborder" width="100%">';
                                                    print '<thead>';
                                                        print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                                            print '<th align="center"><strong>Código</strong></th>';
                                                            print '<th align="center"><strong>Tasa</strong></th>';
                                                            print '<th align="right"><strong>Importe</strong></th>';
                                                            print '<th align="center"><strong>Acción</strong></th>';
                                                        print '</tr>';
                                                    print '</thead>';

                                                    print '<tbody>';
                                                        while ($i < $cfdi_t) {
                                                            $obj = $db->fetch_object($resql);
                                                            print '<tr>';
                                                                print '<td align="center">'.$obj->codigo.'</td>';
                                                                print '<td align="center">'.$obj->tasa.'%</td>';
                                                                print '<td align="right">';
                                                                    print price($obj->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                                                print '</td>';
                                                                print '<td align="center">';
                                                                    print '<a href="?facid=' . $_REQUEST["facid"] . '&del_retencion_local=' . $obj->rowid . '" onClick="return confirm(\'Está seguro que desea eliminar esta retención?\')">' . img_delete('Eliminar la Retención '.$obj->codigo) . '</a>';
                                                                print '</td>';
                                                            print '</tr>';
                                                            $i++;
                                                        }
                                                    print '<tbody>';
                                                print '</table>';
                                            }
                                        }
                                    } else {
                                        $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $_REQUEST["facid"];
                                        $resql = $db->query($sql);
                                        if ($resql) {
                                            $cfdi_t = $db->num_rows($resql);
                                            $i      = 0;
                                            if ($cfdi_t > 0) {
                                                print '<table class="noborder" width="100%">';
                                                    print '<thead>';
                                                        print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                                            print '<th align="center"><strong>Código</strong></th>';
                                                            print '<th align="center"><strong>Tasa</strong></th>';
                                                            print '<th align="right"><strong>Importe</strong></th>';
                                                        print '</tr>';
                                                    print '</thead>';

                                                    print '<tbody>';

                                                        while ($i < $cfdi_t) {
                                                            $obj = $db->fetch_object($resql);
                                                            // print '<strong>Impuesto:</strong> ' . $obj->codigo . ' ' . $obj->tasa . '% <strong>Importe:</strong> ' . number_format($obj->importe, 2) . "<br>";

                                                            print '<tr>';
                                                                print '<td align="center">'.$obj->codigo.'</td>';
                                                                print '<td align="center">'.$obj->tasa.'%</td>';
                                                                print '<td align="right">';
                                                                    print price($obj->importe, 0, '', 1, -1, -1, $object->multicurrency_code);
                                                                print '</td>';
                                                            print '</tr>';
                                                            $i++;
                                                        }
                                                        print '<tbody>';
                                                    print '</table>';
                                            } else {
                                                // echo "No se registraron retenciones para esta factura";
                                                print '<div class="info hideonsmartphone clearboth">';
                                                    echo "No se registraron retenciones locales para esta factura";
                                                print '</div>';
                                            }
                                        }
                                    }
                                print '</td>';
                            print '</tr>';
                        }else{
                            print '<tr>';
                                print '<td>';
                                    print '<div class="info hideonsmartphone clearboth">';
                                        print "No se tienen registradas retenciones locales.";
                                        print '<br>';
                                        print "<b><a href='admin/cfdimx.php?mod=retenciones' target='_blank'>Click para Registrar Retenciones Locales</a></b>";
                                    print '</div>';
                                print '</td>';
                            print '</tr>';
                        }

                        print '</table>';
                        print '<br>';
                    }
                    #Retenciones locales - Se agregan desde la configuración del módulo en Dolibarr (Fin)

                    // Datos de emisor
                    if ($user->fk_warehouse > 0) {
                        print '<em>Desasignar almacén a usuario actual para habilitar menú de emisores.</em>';
                    } elseif ($user->rights->cfdimx->timbrar_diferentes_emisores) {
                        print '<table class="noborder" width="100%">';
                        print '<tr class="liste_titre">';
                        print '<td class="center"><span class="fa fa-list-alt"></span> <b>Emisor</b></td>';
                        print '</tr>';
                        print '<tr><td class="center" style="border-bottom: 0px;">';

                        $sql = "SELECT rowid, emisor_rfc, razon_social, predeterminado FROM " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp";
                        $resql = $db->query($sql);
                        if($resql) {
                            print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '">';
                            print '<select id="select_emisor" name="selected_emisor" style="width: 90%;">';
                            while ($obj = $db->fetch_object($resql)) {
                                print '<option';
                                print ' value="' . $obj->emisor_rfc . '"';
                                print ' title="' . $obj->emisor_rfc . ' - ' . $obj->razon_social . '" ';
                                if (isset($_SESSION['selected_emisor'])) {
                                    print ($_SESSION['selected_emisor'] == $obj->emisor_rfc ? 'selected' : '' );
                                } else {
                                    print ($obj->predeterminado ? ' selected' : '');
                                }
                                print '>';
                                print $obj->emisor_rfc . ' - ' . $obj->razon_social;
                                print '</option>';
                            }
                            print '</select>';
                            print '</tr></td>';
                            print '<tr><td class="center">';
                            print '<input class="button center" type="submit" value="' . $langs->trans('Save') . '">';
                            print '</form>';
                        } else {
                            print '<em>No se encontraron emisores.</em>';
                        }
                        print '</td></tr>';
                        print '</table> ';
                    }

                    # Inicio ISH
                    if ($object->statut != 0) {
                        $sql        = 'SHOW COLUMNS FROM ' . MAIN_DB_PREFIX . 'product_extrafields LIKE "prodcfish"';
                        $resql      = $db->query($sql);
                        $existe_ish = $db->num_rows($resql);

                        $sql              = 'SHOW COLUMNS FROM ' . MAIN_DB_PREFIX . 'facturedet_extrafields LIKE "prodcfish"';
                        $resql            = $db->query($sql);
                        $existe_ish_extra = $db->num_rows($resql);

                        $totalish = 0;

                        if ($existe_ish > 0) {

                            if (isset($conf->global->MAIN_MODULE_MULTICURRENCY)){
                                $sql = "SELECT a.fk_product,a.multicurrency_total_ht as total_ht,b.prodcfish,((b.prodcfish/100)*a.multicurrency_total_ht) as impish,c.ref,c.label FROM " . MAIN_DB_PREFIX . "facturedet a, (SELECT fk_object,prodcfish FROM " . MAIN_DB_PREFIX . "product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b, " . MAIN_DB_PREFIX . "product c WHERE a.fk_facture=" . $id . " AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
                            }else {
                                $sql = "SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label FROM " . MAIN_DB_PREFIX . "facturedet a,(SELECT fk_object,prodcfish FROM " . MAIN_DB_PREFIX . "product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b," . MAIN_DB_PREFIX . "product c WHERE a.fk_facture=" . $object->id . " AND a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
                            }
                            // print $sql;

                            $ass = $db->query($sql);
                            $asf = $db->num_rows($ass);

                            if ($asf > 0) {

                                $currency = $langs->trans('Currency' . (isset($divisa) ? $divisa : $conf->currency));
                                print '<table class="noborder" width="100%">';
                                    print '<thead>';
                                        print '<tr class="liste_titre">';
                                            print '<td align="center" colspan="4">';
                                                print '<span class="fa fa-list-alt valignmiddle btnTitle-icon"></span>';
                                                print '&nbsp;';
                                                print '<b>Impuesto ISH</b>';
                                            print '</td>';
                                        print '</tr>';
                                    print '</thead>';

                                    print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                        print '<td><strong>Producto</strong></td>';
                                        print '<td><strong>Importe Sin IVA</strong></td>';
                                        print '<td><strong>Porcentaje ISH</strong></td>';
                                        print '<td><strong>ISH</strong></td>';
                                    print '</tr>';

                                    // $totalish = 0;

                                    while ($asd = $db->fetch_object($ass)) {

                                        print '<tr>';
                                            print '<td>' . $asd->ref . '-' . $asd->label . '</td>';
                                            print '<td align="center">';
                                                print price($asd->total_ht, 0, '', 1, -1, -1, $object->multicurrency_code);
                                            print '</td>';
                                            print '<td align="center">' . $asd->prodcfish . ' %</td>';
                                            print '<td align="center">';
                                                print price($asd->impish, 0, '', 1, -1, -1, $object->multicurrency_code);
                                            print '</td>';
                                        print '</tr>';

                                        $totalish = $totalish + $asd->impish;
                                    }

                                    if ($totalish > 0) {
                                        $object->total_ttc = $object->total_ttc + $totalish;
                                        $object->total_ttc = str_replace(",", "", number_format($object->total_ttc, 2));
                                    }

                                if($existe_ish_extra == 0){
                                    print '<tr>';
                                        print '<td align="right" colspan="4">';
                                            print '<strong>Total Impuesto ISH:</strong>';
                                        print '</td>';
                                        print '<td align="center">';
                                            print price($totalish, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';

                                    print '</table>';
                                }
                            }
                        }

                        if($existe_ish_extra > 0){

                            if (isset($conf->global->MAIN_MODULE_MULTICURRENCY)){
                                $sql = "
                                        SELECT
                                            a.fk_product,
                                            a.multicurrency_total_ht AS total_ht,
                                            b.prodcfish,
                                            (
                                                (b.prodcfish / 100) * a.multicurrency_total_ht
                                            ) AS impish,
                                            a.label,
                                            a.description
                                        FROM
                                            ".MAIN_DB_PREFIX."facturedet a,
                                            (
                                            SELECT
                                                fk_object,
                                                prodcfish
                                            FROM
                                                ".MAIN_DB_PREFIX."facturedet_extrafields
                                            WHERE
                                                prodcfish != 0 AND prodcfish IS NOT NULL
                                        ) b
                                        WHERE
                                            a.fk_facture = ".$object->id." AND a.rowid = b.fk_object
                                        ORDER BY
                                            a.rowid;
                                    ";
                            }else {
                                $sql = "
                                    SELECT
                                            a.fk_product,
                                            a.total_ht,
                                            b.prodcfish,
                                            (
                                                (b.prodcfish / 100) * a.total_ht
                                            ) AS impish,
                                            a.label,
                                            a.description
                                        FROM
                                            ".MAIN_DB_PREFIX."facturedet a,
                                            (
                                            SELECT
                                                fk_object,
                                                prodcfish
                                            FROM
                                                ".MAIN_DB_PREFIX."facturedet_extrafields
                                            WHERE
                                                prodcfish != 0 AND prodcfish IS NOT NULL
                                        ) b
                                        WHERE
                                            a.fk_facture = ".$object->id." AND a.rowid = b.fk_object
                                        ORDER BY
                                            a.rowid
                                    ";
                            }
                            // print $sql;

                            $ass = $db->query($sql);
                            $asf = $db->num_rows($ass);

                            if ($asf > 0) {

                                $currency = $langs->trans('Currency' . (isset($divisa) ? $divisa : $conf->currency));

                                if($totalish == 0){
                                    print '<table class="noborder" width="100%">';
                                        print '<thead>';
                                            print '<tr class="liste_titre">';
                                                print '<td align="center" colspan="4">';
                                                    print '<span class="fa fa-list-alt valignmiddle btnTitle-icon"></span>';
                                                    print '&nbsp;';
                                                    print '<b>Impuesto ISH</b>';
                                                print '</td>';
                                            print '</tr>';
                                        print '</thead>';

                                        print '<tr class="liste_titre" style="background-color: #990000; color: white;">';
                                            print '<td><strong>Producto</strong></td>';
                                            print '<td><strong>Importe Sin IVA</strong></td>';
                                            print '<td><strong>Porcentaje ISH</strong></td>';
                                            print '<td><strong>ISH</strong></td>';
                                        print '</tr>';
                                }

                                while ($asd = $db->fetch_object($ass)) {

                                    print '<tr>';
                                        print '<td>' . $asd->description . '</td>';
                                        print '<td align="center">';
                                            print price($asd->total_ht, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                        print '<td align="center">' . $asd->prodcfish . ' %</td>';
                                        print '<td align="center">';
                                            print price($asd->impish, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';

                                    $totalish = $totalish + $asd->impish;
                                }

                                if ($totalish > 0) {
                                    $object->total_ttc = $object->total_ttc + $totalish;
                                    $object->total_ttc = str_replace(",", "", number_format($object->total_ttc, 2));

                                    print '<tr>';
                                        print '<td align="right" colspan="3">';
                                            print '<strong>Total Impuesto ISH:</strong>';
                                        print '</td>';
                                        print '<td align="center">';
                                            print price($totalish, 0, '', 1, -1, -1, $object->multicurrency_code);
                                        print '</td>';
                                    print '</tr>';
                                }

                                print '</table>';
                            }
                        }
                        print '<br>';
                    }
                    # Fin ISH
                print '</div>'; //Fiche right
            print '</div>'; //Fiche center

            print '<div class="clearboth"></div>';

            dol_fiche_end();

            if ($action == 'presend' || $action == 'send') {
                include 'mail_form.php';
            }

            if ($action != 'presend' && $action != 'send'){
                #Actions buttons
                print '<div class="tabsAction" width="100%" align="left">';

                    if ($object->statut == 1 || $object->statut == 2) {
                        if ($user->rights->cfdimx->create == 1 && $uuid == "") {
                            $filename2=DOL_DOCUMENT_ROOT.'/cfdimx/permisopdf.php';
                            if(file_exists($filename2) == true){
                                print '<div class="inline-block divButAction">';
                                    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=preview_pdf2">' . $langs->trans('PDF Pruebas') . '</a>';
                                print '</div>';
                            }

                            print '<div class="inline-block divButAction">';
                                print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=preview_pdf">' . $langs->trans('Previsualizar PDF') . '</a>';
                            print '</div>';
                        }

                        // Send by mail
                        if ($user->rights->facture->invoice_advance->send && $uuid != "") {
                            print '<div class="inline-block divButAction">';
                                print '<a class="butAction" href="'.DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$object->id.'&amp;action=presend&amp;mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>';
                            print '</div>';
                        }

                        if ($user->rights->cfdimx->create == 1) {
                            $sql = 'SELECT * FROM  '.MAIN_DB_PREFIX.'cfdimx WHERE fk_facture='.$object->id.' AND entity_id='. $conf->entity;
                            //echo $sql;
                            $resql = $db->query($sql);
                            //Si ya existe el registro en la BD de la factura seleccionada se puede regenerar el PDF
                            if ($db->num_rows($resql) > 0) {
                                print '<div class="inline-block divButAction">';
                                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;uuid='.$uuid.'&amp;action=regen_pdf">'.$langs->trans('Regenerar PDF').'</a>';
                                print '</div>';
                            }
                        }

                        if ($user->rights->cfdimx->delete == 1) {
                            if($status_conf==1){
                                if( $cfdi_tot>0 ){
                                    if($cfdi_cancela != 1) {
                                        //Hay que agregar estos en cancelar
                                        print '<div class="inline-block divButAction">';
                                            print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?facid='.$object->id.'&rfc_emisor='.$conf->global->MAIN_INFO_SIREN.'&uuid='.$uuid.'&action=cancel">Solicitar Cancelación CFDI</a>';
                                        print '</div>';
                                    }
                                }
                            }

                            $sql_solicitud_canceacion = "";
                            $sql_solicitud_canceacion .= "SELECT count(*) AS solicitudes FROM ".MAIN_DB_PREFIX."cfdimx_solicitud_cancelacion";
                            $sql_solicitud_canceacion .= " WHERE fk_facture = ".$object->id;
                            $res_solicitud_canceacion = $db->query($sql_solicitud_canceacion);
                            $num_solicitudes = 0;

                            if($res_solicitud_canceacion){
                                $obj_solicitud   = $db->fetch_object($res_solicitud_canceacion);
                                $num_solicitudes = $obj_solicitud->solicitudes;
                            }

                            if($num_solicitudes > 0){
                                print '<div class="inline-block divButAction">';
                                    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?facid='.$object->id.'&rfc_emisor='.$conf->global->MAIN_INFO_SIREN.'&action=clasificar_factura">';
                                        print "Clasificar 'Cancelada'";
                                    print '</a>';
                                print '</div>';
                            }
                        }

                        if ($status_conf == 1) {
                            if ($cfdi_tot < 1) {
                                if ($soc_rfc != "") {
                                    if ($user->rights->cfdimx->create == 1) {
                                        if ($object->getLibStatut(1, $totalpaye) == 'Borrador' || $objFacturaCFDI->getLinkGeneraCFDI($status, $id) == 'Fuera de fecha de timbrado') {
                                            if ($object->getLibStatut(1, $totalpaye) == 'Borrador') {
                                                print '<div class="inline-block divButAction">';
                                                    print '<div class="error hideonsmartphone clearboth">';
                                                        print '<strong>No puede timbrar un borrador.</strong>';
                                                    print '</div>';
                                                print '</div>';
                                            }
                                            if ($objFacturaCFDI->getLinkGeneraCFDI($status, $id) == 'Fuera de fecha de timbrado') {
                                                print '<div class="inline-block divButAction">';
                                                    print '<div class="error hideonsmartphone clearboth">';
                                                        print '<strong>Fuera de fecha de timbrado.</strong>';
                                                    print '</div>';
                                                print '</div>';
                                            }
                                        }else{
                                            if ($modo_timbrado == 1){
                                                if($folios_disponibles > 0) {
                                                    print '<div class="inline-block divButAction">';
                                                        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;osd=' . $osd . '&amp;action=generaCFDI">Generar CFDI</a>';
                                                    print '</div>';
                                                }else{
                                                    print '<br>';
                                                    print '<div class="inline-block divButAction">';
                                                        print '<div class="error hideonsmartphone clearboth">';
                                                            print '<strong>No cuenta con Folios para realizar Facturas Electrónicas.</strong>';
                                                        print '</div>';
                                                    print '</div>';
                                                }
                                            }else{
                                                if ($modo_timbrado == 2) {
                                                    print '<div class="inline-block divButAction">';
                                                        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;osd=' . $osd . '&amp;action=generaCFDI">Generar CFDI</a>';
                                                    print '</div>';
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    $msj_validacion = "";
                                    if(strcmp($conf->global->CFDIMX_VERSION_SAT, "4.0") == 0){
                                        $msj_validacion = '<label style="color:#990000; font-size:14px; font-weight: bold;">El Cliente (Receptor) no cuenta con un RFC asignado da <a href="domicilio_fiscal.php?socid='.$soc_id.'">Click aquí</a> para completar.</label>';
                                    }else{
                                        $msj_validacion = '<label style="color:#990000; font-size:14px; font-weight: bold;">El Cliente (Receptor) no cuenta con un RFC asignado da <a href="../societe/card.php?socid=' . $soc_id . '&action=edit">Click aquí</a> para completar.</label>';
                                    }

                                    print '<br>';
                                    print '<div class="inline-block divButAction">';
                                        print '<div class="error hideonsmartphone clearboth">';
                                            print $msj_validacion;
                                        print '</div>';
                                    print '</div>';
                                }
                            }
                        }else {
                            $msj_validacion = '<label style="color:#990000; font-size:14px; font-weight: bold;">Existen errores en la configuración';
                            $msj_validacion .= ' <a href="admin/cfdimx.php?mod=config">Click aquí</a> para completar.</label>';
                            print '<br>';
                            print '<div class="inline-block divButAction">';
                                print '<div class="error hideonsmartphone clearboth">';
                                    print $msj_validacion;
                                print '</div>';
                            print '</div>';
                        }
                    }else {
                        if ($object->statut == 0) {
                            print '<br>';
                            print '<div class="inline-block divButAction">';
                                print '<div class="error hideonsmartphone clearboth">';
                                    print '<strong>No es posible timbrar un borrador.</strong>';
                                print '</div>';
                            print '</div>';
                        }
                        if ($object->statut == 3) {
                            print '<div class="inline-block divButAction">';
                                print '<div class="error hideonsmartphone clearboth">';
                                    print '<strong>La factura esta abandonada, no es posible realizar acciones.</strong>';
                                print '</div>';
                            print '</div>';
                        }
                    }
                print '</div>';

                print '<div class="fichecenter">'; //fichecenter 2 inicio
                    #Listado de archivos#
                    $filedir=$conf->facture->dir_output.'/'.$object->ref.'/';
                    $file_list=dol_dir_list($filedir,'files',0,'','\.meta$','date',SORT_DESC);

                    // Loop on each file found
                    if (is_array($file_list))
                    {
                        $out_files = "";
                        foreach($file_list as $file)
                        {
                            $aux_ext = explode(".", $file['name']);
                            $ext = $aux_ext[1];

                            if (in_array($ext, array("pdf"))){
                                $out_lupa = '<a class="pictopreview documentpreview" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&amp;attachment=0&amp;file='.$file['level1name'].'/'.$file['name'].'" mime="application/pdf" target="_blank"><span class="fa fa-search-plus" style="color: gray"></span></a>';
                            }else {
                                $out_lupa = "";
                            }

                            $out_files.= '<tr>';

                            $out_files.='
                                <td class="minwidth200">
                                    <a class="documentdownload paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture&file='.$file['level1name'].'/'.$file['name'].'" target="_blank"><i class="fa fa-file-pdf-o paddingright"></i>'.$file['name'].'</a>
                                    '.$out_lupa.'
                                </td>
                                <td align="right" class="nowrap">'.filesize($file['fullname']).' Bytes</td>
                                <td align="right" class="nowrap">'.dol_print_date($file['date'], "%H:%M %d/%m/%Y").'</td>
                                <!--<td align="right">
                                    <a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$id.'&amp;action=remove_file&amp;file='.$file['level1name'].'/'.$file['name'].'&amp;entity='.$conf->entity.'">
                                        <img src="'.DOL_URL_ROOT.'/theme/eldy/img/delete.png" alt="" title="Eliminar" class="inline-block">
                                    </a>
                                </td>-->';
                            $out_files.= '</tr>';
                        }
                    }

                    if($out_files != ""){
                        print '<div class="fichehalfleft">';
                            print '<form action="generaPDF_new.php?facid='.$id.'" id="builddoc_form" method="post">';
                                print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
                                print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;">';
                                    print '<tbody>';
                                        print '<tr>';
                                            print '<td class="nobordernopadding" valign="middle">';
                                                print '<div class="titre">Archivos vinculados</div>';
                                            print '</td>';
                                        print '</tr>';
                                    print '</tbody>';
                                print '</table>';

                                print '<div class="div-table-responsive-no-min">';
                                    print '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';
                                        print '<tbody>';
                                            print '<tr class="liste_titre">';
                                                print '<td colspan="3">';
                                                    print '<div align="center" width="100%">';
                                                        print '<span class="fa fa-file-pdf valignmiddle btnTitle-icon"></span>&nbsp;';
                                                        print '<strong>Documentos</strong>';
                                                    print '</div>';
                                                print '</td>';
                                            print '</tr>';
                                            print $out_files;
                                        print '</tbody>';
                                    print '</table>';
                                print '</div>';
                            print '</form>';
                            print '<br>';
                        print '</div>';

                        // print '<div class="fichehalfright">';
                            // print '<table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;">';
                            //     print '<tbody>';
                            //         print '<tr>';
                            //             print '<td class="nobordernopadding" valign="middle">';
                            //                 print '<div class="titre">Eventos CFDIMX sobre la Factura</div>';
                            //             print '</td>';
                            //         print '</tr>';
                            //     print '</tbody>';
                            // print '</table>';

                            // print '<div class="div-table-responsive-no-min">';
                            //     print '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';
                            //         print '<tbody>';
                            //             print '<tr class="liste_titre">';
                            //                 print '<td colspan="3">';
                            //                     print '<div align="center" width="100%">';
                            //                         print '<span class="fa fa-calendar valignmiddle btnTitle-icon"></span>&nbsp;';
                            //                         print '<strong>Eventos</strong>';
                            //                     print '</div>';
                            //                 print '</td>';
                            //             print '</tr>';
                            //             // print $out_files;
                            //         print '</tbody>';
                            //     print '</table>';
                            // print '</div>';

                            // print '<div class="fichehalfright">';
                            //     include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
                            //     $formactions = new FormActions($db);
                            //     $somethingshown = $formactions->showactions($object, 'cfdimx', $soc_id, 1);
                            // print '</div>';
                        // print '</div>';
                    }

                    // print '<br><br>';
                print '</div>'; //fichecenter 2 fin
            }
        }else {
            dol_print_error($db, $object->error);
        }
    }

    llxFooter();
    $db->close();
?>
