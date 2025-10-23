<?php
    error_reporting(0);
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
    require_once DOL_DOCUMENT_ROOT.'/cfdimx/class/pagos.class.php';

    class Actionscfdimx {

        /**
         * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
         *
         * @param   array()         $parameters     Hook metadatas (context, etc...)
         * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
         * @param   string          &$action        Current action (if set). Generally create or edit or null
         * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
         * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
         */

        function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {

            global $db;

            /*if (in_array('invoicecard', explode(':', $parameters['context']))) {
                if ($object->statut == 1 || $object->statut == 2) {
                    $url = DOL_URL_ROOT . '/cfdimx/facture.php?facid=' . $object->id;
                    #Se valida si la factura esta dentro del rango de 72 horas
                    $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid = ' . $object->id . ' AND datef >  NOW() - INTERVAL 72 HOUR';
                    $resql = $db->query($sql);
                    if ($resql) {
                        if ($db->num_rows($resql) > 0) {
                            #Se valida si ya fue timbrada
                            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx WHERE fk_facture='.$object->id;
                            $resql = $db->query($sql);
                            if ($db->num_rows($resql) <= 0) {
                                $aux = '<a href="' . $url . '" class="butAction" >CFDI</a>';
                                print $aux;
                            }
                        }
                    }
                }
            }*/
            if (in_array('invoicecard', explode(':', $parameters['context']))) {
                if ($object->statut == 1 || $object->statut == 2) {
                    /** Se obtiene URL completa */
                    // Datos del receptor necesarios
                    $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "facture f,  " . MAIN_DB_PREFIX . "societe s WHERE f.rowid = '" . $object->id . "' AND f.fk_soc = s.rowid";
                    $resql = $db->query($sql);
                    if ($resql) {
                        $soc_num = $db->num_rows($resql);
                        $i       = 0;
                        if ($soc_num) {
                            while ($i < $soc_num) {
                                $obj = $db->fetch_object($resql);
                                if ($obj) {
                                    $soc_rfc   = $obj->siren;
                                    $soc_id    = $obj->rowid;
                                }
                                $i++;
                            }
                        }
                    }
                    $sqld = "SELECT tpdomicilio FROM " . MAIN_DB_PREFIX . "cfdimx_domicilios_receptor WHERE receptor_rfc='" . $soc_rfc . "' AND entity_id=1 AND fk_socid=" . $soc_id;
                    $resql = $db->query($sqld);
                    $obj = $db->fetch_object($resql);
                    $tpdomi = ($obj->tpdomicilio)?? '';
    
                    $url = DOL_URL_ROOT . '/cfdimx/facture.php?facid='.$object->id.'&tpdomi='.$tpdomi.'&osd=MXN&tdc='.'&action=generaCFDI';
                    #Se valida si la factura esta dentro del rango de 72 horas
                    $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid = ' . $object->id . ' AND datef >  NOW() - INTERVAL 72 HOUR';
                    $resql = $db->query($sql);
                    if ($resql) { 
                        if ($db->num_rows($resql) > 0) {
                            #Se valida si ya fue timbrada
                            $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx WHERE fk_facture='.$object->id;
                            $resql = $db->query($sql);
                            if ($db->num_rows($resql) <= 0) {
                                $aux = '<a href="' . $url . '" id="buttonCFDI" class="butAction" onclick="validButton();">Generar CFDI</a>';
                                
                                echo  '<script>
                                        function validButton () {
                                            $("#buttonCFDI").attr("class", "butActionRefused");
                                        }
                                        </script>';
                                        
                                print $aux;
                            }
                        }
                    }
                }
            }
        }       

        function formConfirm($parameters, &$object, &$action, $hookmanager){
            if (in_array('paiementcard', explode(':', $parameters['context']))) {
                global $langs, $conf, $db;

                $moneda_sel = (GETPOST('moneda_cfdimx') != '' ? GETPOST('moneda_cfdimx') : $conf->currency);

                $complemento = new ComplementoPagos($db);

                $langs->load('cfdimx@cfdimx');

                $list_monedas = '';
                $list_monedas .= '<div class="tabBar tabBarWithBottom">';
                    $list_monedas .= '<table class="tabBar tabBarWithBottom">';
                        $list_monedas .= '<tbody>';
                            $list_monedas .= '<tr>';
                                $list_monedas .= '<td class="fieldrequired">';
                                    $list_monedas .= $langs->trans("CurrencyCP");
                                $list_monedas .= '</td>';
                                $list_monedas .= '<td>';
                                $list_monedas .= $complemento->obtener_moneda($moneda_sel, 'moneda_cfdimx', 1);
                                $list_monedas .= '</td>';
                            $list_monedas .= '</tr>';
                        $list_monedas .= '</tbody>';
                    $list_monedas .= '</table>';
                $list_monedas .= '</div>';

                $hookmanager->resPrint =  $list_monedas;
            }
        }

        /**
         * Overloading the formObjectOptions function : replacing the parent's function with the one below
         *
         * @param   array()         $parameters     Hook metadatas (context, etc...)
         * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
         * @param   string          &$action        Current action (if set). Generally create or edit or null
         * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
         * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
         */

        function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
            if (in_array('invoicecard', explode(':', $parameters['context']))) {
                $posicion = strpos($_SERVER["PHP_SELF"], 'cfdimx');
                $confirm = GETPOST('confirm', 'alpha');

                if ($posicion == false) {
                    global $db, $conf, $user;

                    $dol_version = (int)DOL_VERSION;

                    /*print 'action :: '.$action;

                    $evento_agenda = new ActionComm($db);
                    $now=dol_now();

                    $evento_agenda->elementtype = "cfdimx";
                    $evento_agenda->type_code   = "AC_OTH_AUTO";
                    $evento_agenda->type_id     = "20";
                    $evento_agenda->code        = "AC_OTH_AUTO";
                    $evento_agenda->socid       = "1";
                    $evento_agenda->percentage  = 100;
                    $evento_agenda->label       = "Cambio de Tercero ZZZ";
                    $evento_agenda->note        = "Se Cambio el Tercero ZZZ";
                    $evento_agenda->fk_element  = $_REQUEST["facid"];
                    $evento_agenda->userownerid = $user->id;
                    $evento_agenda->datep       = $db->idate($now);

                    $aux = $evento_agenda->create($user,1);*/                    

                    if($action != 'create'){
                        print '<tr>';
                        print '<td class="liste_titre">';
                        print '<table class="nobordernopadding" width="100%">';
                        print '<tbody><tr>';

                        print '<td>Cambiar Tercero</td>';

                        print '<td class="right">';
                        print '<a class="reposition editfielda" href="' . DOL_URL_ROOT . '/compta/facture/card.php?facid='.$object->id.'&amp;action=edit_tercero">';
                            if($dol_version >= 14){
                                print '<span class="fas fa-pencil-alt" style=" color: #444; float: right" title="Cambiar Tercero"></span>';
                            }else{
                                print '<img src="' . DOL_URL_ROOT . '/theme/eldy/img/edit.png" alt="" title="Cambiar Tercero" style="float: right" class="pictoedit">  </a>';
                            }
                        print '</td>';
                        print '</tr></tbody>';
                        print '</table>';
                        print '</td>';

                        if(GETPOST('action') == 'edit_tercero'){
                            $form = new Form($db);

                            print '<td>';
                            $form->form_thirdparty($_SERVER['PHP_SELF'] . '?facid=' . $object->id.'&action=guarda_tercero', $object->socid, 'socid');
                            print '</td>';
                        }else{
                            if(GETPOST('action') == 'guarda_tercero'){
                                $tercero_old = new Societe($db);
                                $tercero_old->fetch($object->socid);
                                $nombre_tercero_old = $object->socid."::".$tercero_old->nom;

                                $tercero_new = new Societe($db);
                                $tercero_new->fetch($_REQUEST["socid"]);
                                $nombre_tercero_new = $_REQUEST["socid"]."::".$tercero_new->nom;

                                $cambia_tercero = "UPDATE  " . MAIN_DB_PREFIX . "facture SET fk_soc = ".$_REQUEST["socid"]." WHERE rowid = " . $_REQUEST["facid"];

                                $res_cambia_tercero = $db->query($cambia_tercero);

                                $evento_agenda = new ActionComm($db);
                                $now=dol_now();

                                $evento_agenda->elementtype = "invoice";
                                $evento_agenda->type_code   = "AC_OTH_AUTO";
                                $evento_agenda->type_id     = "20";
                                $evento_agenda->code        = "AC_OTH_AUTO";
                                $evento_agenda->socid       = $_REQUEST["socid"];
                                $evento_agenda->percentage  = 100;
                                $evento_agenda->label       = "Cambio de Tercero";
                                $evento_agenda->note        = "Se Cambio el Tercero ".$nombre_tercero_old." por el Tercero ".$nombre_tercero_new;
                                $evento_agenda->fk_element  = $_REQUEST["facid"];
                                $evento_agenda->userownerid = $user->id;
                                $evento_agenda->datep       = $db->idate($now);

                                $aux = $evento_agenda->create($user,1);

                                print '<script>location.href="?facid=' . $_REQUEST["facid"] . '";</script>';
                            }else{
                                $tercero_sel = new Societe($db);
                                $tercero_sel->fetch($object->socid);

                                print '<td>';
                                print ' &nbsp;' . $tercero_sel->getNomUrl(1, 'compta');
                                print '</td>';
                            }
                        }

                        print '</tr>';
                    }

                    if ($object->statut > 0) {
                        $url = DOL_URL_ROOT . '/cfdimx/facture.php?facid=' . $object->id;
                        $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx WHERE fk_facture = " . $object->id;
                        $resql = $db->query($sql);
                        if ($resql) {
                            $num = $db->num_rows($resql);
                            $i = 0;
                            if ($num) {
                                while ($i < $num) {
                                    $obj = $db->fetch_object($resql);
                                    if ($obj) {
                                        print '<tr class="liste_titre">';
                                            print '<td colspan="2" align="center">';
                                                print '<span class="fa fa-file-text-o valignmiddle btnTitle-icon"></span>';
                                                print '&nbsp;';
                                                print '<strong>Informaci&oacute;n Fiscal</strong>';
                                            print '</td>';
                                        print '</tr>';

                                        $sql = 'SELECT IFNULL(tipo_document,NULL) as tipo_document FROM ' . MAIN_DB_PREFIX . 'cfdimx_type_document WHERE fk_facture=' . $object->id;
                                        $resp = $db->query($sql);
                                        $respp = $db->fetch_object($resp);
                                        print '<tr><td>Tipo de documento</td><td>';
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
                                            if ($respp->tipo_document == 7) {
                                                print "Factura de Traslado";
                                            }
                                        }else {
                                            $sql = 'SELECT type FROM '.MAIN_DB_PREFIX.'facture WHERE rowid=' . $object->id;
                                            $resp = $db->query($sql);
                                            $respp = $db->fetch_object($resp);
                                            if ($respp->type == 2) {
                                                print "Nota de Crédito";
                                            }
                                            else {
                                                print "Factura Estándar";
                                            }
                                        }
                                        print '</td></tr>';
                                        print '<tr><td>UUID</td><td><a href="' . DOL_URL_ROOT . '/cfdimx/facture.php?facid=' . $object->id . '">' . $obj->uuid . '</a></td></tr>';
                                        print '<tr><td>Fecha y Hora de Timbrado</td>';
                                        print '<td>' . $obj->fecha_timbrado . ' ' . $obj->hora_timbrado . '</td></tr>';

                                        if($dol_version >= 14){
                                            $sql = 'SELECT round(total_ht,2) as total ,round(total_tva,2) as tva,round(total_ttc,2) as total_ttc FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid=' . $object->id;

                                            if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                                                $sql = 'SELECT round(multicurrency_total_ht,2) as total ,round(multicurrency_total_tva,2) as tva,round(multicurrency_total_ttc,2) as total_ttc FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid=' . $object->id;
                                            }
                                        }else{
                                            $sql = 'SELECT round(total,2) as total ,round(tva,2) as tva,round(total_ttc,2) as total_ttc FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid=' . $object->id;

                                            if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                                                $sql = 'SELECT round(multicurrency_total_ht,2) as total ,round(multicurrency_total_tva,2) as tva,round(multicurrency_total_ttc,2) as total_ttc FROM ' . MAIN_DB_PREFIX . 'facture WHERE rowid=' . $object->id;
                                            }
                                        }


                                        //print $sql;
                                        $rt = $db->query($sql);
                                        if($rt){
                                            $rtt = $db->fetch_object($rt);
                                            //print "<tr><td>Subtotal</td><td align='right'>" . number_format($rtt->total, 2) . "</td></tr>";
                                            //print "<tr><td>IVA</td><td align='right'>" . number_format($rtt->tva, 2) . "</td></tr>";

                                            #ISH
                                            $sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "product_extrafields LIKE 'prodcfish'";
                                            $resql = $db->query($sql);
                                            $existe_ish = $db->num_rows($resql);
                                            if ($existe_ish > 0) {
                                                $sql = "SELECT count(*) as exist,importe  FROM " . MAIN_DB_PREFIX . "cfdimx_facturedet WHERE fk_facture=" . $object->id . " AND impuesto='ISH'";
                                                $ass = $db->query($sql);
                                                $asd = $db->fetch_object($ass);
                                                if ($asd->exist > 0) {
                                                    print "<tr><td>ISH</td><td align='right' >" . number_format($asd->importe, 2) . "</td></tr>";
                                                }
                                            }
                                            #ISH

                                            $total_res = $rtt->total_ttc;
                                            #Retenciones
                                            if (1) {
                                                $sql = "SELECT impuesto,importe FROM " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture=" . $object->id;
                                                //print $sql;
                                                $rsq = $db->query($sql);
                                                $restar = 0;
                                                while ($rsqq = $db->fetch_object($rsq)) {
                                                    $restar = $restar + $rsqq->importe;
                                                    print "<tr><td>Retencion de " . $rsqq->impuesto . "</td><td align='right'>" . number_format($rsqq->importe, 2) . "</td></tr>";
                                                }
                                                $total_res = $rtt->total_ttc - $restar;
                                            }

                                            #Retenciones locales
                                            $sqm = "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '" . $db->database_name . "' AND table_name = '" . MAIN_DB_PREFIX . "cfdimx_config_retenciones_locales'";
                                            $rqm = $db->query($sqm);
                                            $rqsm = $db->fetch_object($rqm);
                                            $total_retlocal = 0;
                                            if ($rqsm > 0) {
                                                $resqm = $db->query("SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $object->id);
                                                if ($resqm) {
                                                    $cfdi_m = $db->num_rows($resqm);
                                                    $m = 0;
                                                    if ($cfdi_m > 0) {
                                                        while ($m < $cfdi_m) {
                                                            $obm = $db->fetch_object($resqm);
                                                            print "<tr><td>Ret. " . $obm->codigo . "</td><td align='right'>" . number_format($obm->importe, 2) . "</td></tr>";
                                                            $m++;
                                                        }
                                                    }
                                                }
                                            }
                                            $arc = $total_res;
                                            //print '<tr><td>Total</td><td align="right">' . number_format(round($rtt->total_ttc, 2), 2) . '</td></tr>';
                                        }
                                    }
                                    $i++;
                                }
                            }
                        }
                    }
                }
            }
        }

        function ActionButtons($parameters, &$object, &$action, $hookmanager) {

            if (in_array('takeposfrontend', explode(':', $parameters['context']))) {
                global $db, $conf, $user, $menus, $langs;

                // $menus[$r++] = array('title'=>'<span class="fa fa-layer-group paddingrightonly"></span><div class="trunc">'.$langs->trans("CFDI 3.3").'</div>', 'action'=>'window.location.href=\''.DOL_URL_ROOT.'/cfdimx/index.php\';');
            }
        }

        /*
            * ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  **** *
            *                                                                              *
            * Inicia Funciones para mostrar información adicional en el listado de Factura *
            *                                                                              *
            * ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  **** *
        */
        // public function printFieldListSelect($parameters)
        // {
        //     if (in_array($parameters['currentcontext'], array('invoicelist'))) {
        //         ###
        //         return ",xml.uuid as uuid, xml.cancelado as status, xml.fecha_emision as fecha_emision";
        //     }
        // }

        // public function printFieldListFrom($parameters)
        // {
        //     if (in_array($parameters['currentcontext'], array('invoicelist'))) {
        //         return " LEFT JOIN " . MAIN_DB_PREFIX . "cfdimx as xml on f.rowid = xml.fk_facture";
        //     }

        // }

        // public function printFieldListWhere($parameters)
        // {
        //     if (in_array($parameters['currentcontext'], array('invoicelist'))) {

        //         $search_cfdistatus = GETPOST('search_cfdistatus');
        //         $search_uuid = GETPOST('search_uuid');
        //         $search_femision = GETPOST('search_femision');

        //         if (empty($search_cfdistatus)) {

        //             $s = $_SESSION['lastsearch_values_tmp_compta/facture/list.php'];
        //             $s = json_decode($s, true);
        //             $search_cfdistatus = $s['search_cfdistatus'];

        //         }

        //         if (!empty($search_uuid)) {

        //             $search_uuid = " AND xml.uuid LIKE '%" . $search_uuid . "%' ";

        //         } else {

        //             $search_uuid = null;
        //         }

        //         if (!empty($search_femision)) {
        //             $search_cfdistatus .= " AND xml.fecha_emision LIKE '%" . $search_femision . "%'";
        //         }
        //         switch ($search_cfdistatus) {
        //             case null:
        //                 return null . $search_uuid;
        //                 break;
        //             case -1:
        //                 return null . $search_uuid;

        //                 break;
        //             case 2:

        //                 return " AND xml.cancelado IS NULL ";

        //                 break;
        //             default:
        //                 return " AND xml.cancelado = " . $search_cfdistatus;
        //                 break;
        //         }

        //     }
        // }

        // public function printFieldListOption($parameters)
        // {

        //     if (in_array($parameters['currentcontext'], array('invoicelist'))) {

        //         print '<td align="center" class="liste_titre">';
        //         echo '<input type="text" class="flat" name="search_femision" value="' . GETPOST('search_femision') . '">';
        //         print '</td>';

        //         print '<td align="center" class="liste_titre">';
        //         echo '<input type="text" class="flat" name="search_uuid" value="' . GETPOST('search_uuid') . '">';
        //         print '</td>';

        //         $liststatus = [
        //             0 => 'Timbrada',
        //             1 => 'Cancelada',
        //             2 => 'Sin Timbrar',
        //         ];
        //         $search_cfdistatus = GETPOST('search_cfdistatus');
        //         if ($search_cfdistatus == null) {
        //             $s = $_SESSION['lastsearch_values_tmp_compta/facture/list.php'];
        //             $s = json_decode($s, true);
        //             $search_cfdistatus = $s['search_cfdistatus'];
        //         }
        //         $form = new Form($this->db);
        //         print '<td class="liste_titre">';
        //         print $form->selectarray('search_cfdistatus', $liststatus, $search_cfdistatus, 1, 0, 0, '', 0, 0, 0, '', '', 1);
        //         print '</td>';

        //     }

        // }

        // public function printFieldListTitle($parameters)
        // {
        //     if (in_array($parameters['currentcontext'], array('invoicelist'))) {
        //         // echo '<pre>';var_dump($parameters);echo '</pre>';
        //         $sortorder = GETPOST('sortorder');
        //         if ($sortorder == "ASC") {
        //             $sortorder = "DESC";
        //         }
        //         if ($sortorder == "DESC") {
        //             $sortorder = "ASC";
        //         }

        //         print_liste_field_titre('Fecha Emision', $_SERVER["PHP_SELF"], "xml.fecha_emision", "", "", "align='center'", 'xml.fecha_emision', $sortorder);
        //         print_liste_field_titre('UUID', $_SERVER["PHP_SELF"], "xml.uuid", "", "", "align='center'", 'xml.uuid', $sortorder);
        //         print_liste_field_titre('Estado', $_SERVER["PHP_SELF"], "xml.cancelado", "", "", "align='center'", 'xml.cancelado', $sortorder);

        //     }
        // }

        // public function printFieldListValue($parameters)
        // {

        //     if (in_array($parameters['currentcontext'], array('invoicelist'))) {
        //         echo '<td align="center">' . $parameters['obj']->fecha_emision . '</td>';

        //         echo '<td align="center">' . $parameters['obj']->uuid . '</td>';
        //         $ref = explode('-', $parameters['obj']->ref);
        //         // echo '<pre>';
        //         // var_dump($parameters['obj']);
        //         // echo '</pre>';
        //         echo '<td align="center">';
        // 		if ($parameters['obj']->uuid != null) {
        // 			if ($parameters['obj']->status == 0) {
        // 				echo '<span class="badge  badge-status4 badge-status">Timbrada</span>';
        // 			} else {
        // 				echo '<span class="badge badge-danger0 badge-danger">Cancelada</span>';

        // 			}
        // 		} else {
        // 			echo '<span class="badge  badge-status0 badge-status">Sin Timbrar</span>';

        // 		}
        //         echo '</td>';
        //     }
        // }

        /*
            * ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  *
            *                                                                               *
            * Termina Funciones para mostrar información adicional en el listado de Factura *
            *                                                                               *
            * ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  ****  *
        */
    }
?>