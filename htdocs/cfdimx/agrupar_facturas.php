<?php
    require('../main.inc.php');

    global $user, $db, $conf;
    error_reporting(0);

    $zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
    date_default_timezone_set($zona_horaria);

    require_once("class/facturacfdimx.class.php");
    require_once("class/agruparfacturas.class.php");
    require_once("class/complementos.class.php");
    require_once("js/agrupar_facturas.js.php");

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

    // if (@$conf->projet->enabled) {
        require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php');
        require_once(DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php');
    // }


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
    $sortfield = GETPOST('sortfield', 'aZ09comma');
    $sortorder = GETPOST('sortorder', 'aZ09comma');
    $limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
    $page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
    if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
        $page = 0;
    }     // If $page is not defined, or '' or -1 or if we click on clear filters
    $offset = $limit * $page;
    $contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'invoicelist';

    ##Atributos para filtros
    $search_ref = GETPOST('sf_ref') ?GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
    $search_status = GETPOST('search_status', 'intcomma');
    $search_date_startday = GETPOST('search_date_startday', 'int');
    $search_date_startmonth = GETPOST('search_date_startmonth', 'int');
    $search_date_startyear = GETPOST('search_date_startyear', 'int');
    $search_date_endday = GETPOST('search_date_endday', 'int');
    $search_date_endmonth = GETPOST('search_date_endmonth', 'int');
    $search_date_endyear = GETPOST('search_date_endyear', 'int');
    $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear); // Use tzserver
    $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
    $search_paymentmode = GETPOST('search_paymentmode', 'int');
    $search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');


    $object = new Facture($db);
    $form = new Form($db);

    if (!$sortorder) {
        $sortorder = 'DESC';
    }
    if (!$sortfield) {
        $sortfield = 'f.datef';
    }

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

    $objComplementos = new ComplementosCFDI($db);

    $objAgruparFacturas = new AgruparFactura($db);
    $tipo_agrupacion = $objAgruparFacturas->getTipoAgrupacion($id);


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


    /********************************************************************
    *                                                                   *
    * Actions                                                           *
    *                                                                   *
    *********************************************************************/

    if($action == 'agruparfacturas'){

        $objAgruparFacturas->facid_origen        = $id;
        $objAgruparFacturas->list_fac_sel        = $_REQUEST["list_facturas"];
        $objAgruparFacturas->list_fac_sel_status = $_REQUEST["sel_factura"];

        // 1 -> Agrupar Facturas
        // 2 -> Agrupar Factura Global
        $res =$objAgruparFacturas->agruparFacturas($tipo_agrupacion);
    }

    if($action == 'addinfoglobal'){        
        $res = $objAgruparFacturas->guardarInformacionGlobal($id, $_REQUEST["periodicidad_ig"],  $_REQUEST["meses_ig"], $_REQUEST["anio_ig"]);

        if($res == 1){
            setEventMessage('Se guardo correctamente la Información Global');
        }else{
            setEventMessage('Error: No see guardo correctamente la Información Global', 'error');
        }
    }

    // $form      = new Form($db);
    $htmlother = new FormOther($db);
    $formfile  = new FormFile($db);


    /*********************************************************************
    *                                                                   *
    * Show object in view mode                                          *
    *                                                                   *
    *********************************************************************/

    llxHeader('', "CFDI ".$conf->global->CFDIMX_VERSION_SAT." - ".$langs->trans('Bill'), 'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');

    $now       = dol_now();

    // print 'id :: '.$id;

    if ($id > 0 || !empty($ref)) {


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

            print dol_get_fiche_head($head, "tabfactclientglobal", 'CFDI', -1, 'bill');

            $formconfirm = '';

            $validacion_cfdimx = $objFacturaCFDI->validarVersionDoli($conf->global->CFDIMX_V_MIN_DOLI, $conf->global->CFDIMX_V_MAX_DOLI);

            if($validacion_cfdimx != ""){
                print '<div class="error hideonsmartphone clearboth">';
                    print $validacion_cfdimx;
                print '</div>';
            }

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

            ##Inicia Información de Factura Global
            if($tipo_agrupacion == 2){
                $informacion_global = $objAgruparFacturas->getInformacionGlobal($id);

                $anio_actual   = ($informacion_global["anio"] != null ? $informacion_global["anio"] : date("Y"));
                $anio_anterior = $anio_actual - 1;

                $place_holder = "Ingresa año (".$anio_anterior." o ".$anio_actual.")";

                print '<form method="POST" name="info_global_form" action="'.$_SERVER["PHP_SELF"].'?facid='.$id.'">'."\n";
                    print '<input type="hidden" name="token" value="'.newToken().'">';
                    print '<input type="hidden" name="action" id="action" value="addinfoglobal">';

                    print '<table class="noborder">';
                        print '<tr class="liste_titre">';
                            print '<th colspan="3"><strong>Información Global</strong></th>';
                        print '</tr>';

                        print '<tr>';
                            print '<td>';
                                print $objAgruparFacturas->obtener_cat_info_globaL($informacion_global["periodicidad"], 'periodicidad_ig', 1);
                            print '</td>';

                            print '<td>';
                                print $objAgruparFacturas->obtener_cat_info_globaL($informacion_global["meses"], 'meses_ig', 2);
                            print '</td>';

                            print '<td>';
                                print '<input type="text" name="anio_ig" id="anio_ig" value="'.$anio_actual.'" placeholder="'.$place_holder.'">';
                            print '</td>';
                        print '</tr>';
                    print '</table>';

                    print '<div align="center" >';
                        print '<input name="add_global" type="submit" value="Guardar" class="butAction">';
                    print '</div>';
                print '</form>';
                print '<br><br>';
            }
            ##Termina Información de Factura Global


            ##Inicia Lista de Facturas ya agrupadas
            $lista_fac_agrupadas = $objAgruparFacturas->listaFacturas($id, $tipo_agrupacion);
            $omitir_facturas = array();
            $omitir_facturas[] = $id;

            if(!is_null($lista_fac_agrupadas)){
                print '<table class="noborder">';

                    print '<tr class="liste_titre">';
                        print '<th colspan="4">';
                            print '<span class="fa fa-tasks valignmiddle btnTitle-icon"></span>&nbsp;&nbsp;';
                            print '<strong>Lista de Facturas Agrupadas</strong>';
                        print '</th>';
                    print '</tr>';

                    print '<tr class="liste_titre">';
                        print '<th><strong>Ref</strong></th>';
                        print '<th><strong>Fecha</strong></th>';
                        print '<th><strong>Forma de Pago</strong></th>';
                        print '<th><strong>Total</strong></th>';
                    print '</tr>';

                    foreach($lista_fac_agrupadas AS $info_factura){
                        print '<tr>';
                            print '<td>'.$info_factura["ref"].'</td>';
                            print '<td>'.$info_factura["fecha"].'</td>';
                            print '<td>'.$info_factura["met_pago"].'</td>';
                            print '<td>'.$info_factura["total"].'</td>';
                        print '</tr>';

                        $omitir_facturas[] = $info_factura["facid_relacionada"];
                    }

                print '</table>';
                print '<br><br>';
            }
            ##Termina Lista de Facturas ya agrupadas



            $arrayfields = array(
                                'f.ref'=>array('label'=>"Ref", 'checked'=>1, 'position'=>5),
                                'f.datef'=>array('label'=>"DateInvoice", 'checked'=>1, 'position'=>20),
                                // 's.nom'=>array('label'=>"ThirdParty", 'checked'=>1, 'position'=>50),
	                            // 's.name_alias'=>array('label'=>"AliasNameShort", 'checked'=>1, 'position'=>51),
                                'f.fk_mode_reglement'=>array('label'=>"PaymentMode", 'checked'=>1, 'position'=>80),
	                            'f.fk_cond_reglement'=>array('label'=>"PaymentConditionsShort", 'checked'=>1, 'position'=>85),
                                'f.total_ttc'=>array('label'=>"AmountTTC", 'checked'=>1, 'position'=>130),
                                'f.fk_statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000)
                            );

            $sql = 'SELECT';
            if ($sall) {
                $sql = 'SELECT DISTINCT';
            }
            $sql .= ' f.rowid as id, f.ref, f.ref_client, f.fk_soc, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.fk_cond_reglement, f.total, f.tva, f.total_ttc,';
            $sql .= ' f.localtax1 as total_localtax1, f.localtax2 as total_localtax2,';
            $sql .= ' f.fk_user_author,';
            $sql .= ' f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva as multicurrency_total_vat, f.multicurrency_total_ttc,';
            $sql .= ' f.datef, f.date_valid, f.date_lim_reglement as datelimite, f.module_source, f.pos_source,';
            $sql .= ' f.paye as paye, f.fk_statut, f.close_code,';
            $sql .= ' f.datec as date_creation, f.tms as date_update, f.date_closing as date_closing,';
            $sql .= ' f.retained_warranty, f.retained_warranty_date_limit, f.situation_final, f.situation_cycle_ref, f.situation_counter,';
            $sql .= ' s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.phone, s.fax, s.address, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta as code_compta_client, s.code_compta_fournisseur,';
            $sql .= ' typent.code as typent_code,';
            $sql .= ' state.code_departement as state_code, state.nom as state_name,';
            $sql .= ' country.code as country_code,';
            $sql .= ' p.rowid as project_id, p.ref as project_ref, p.title as project_label,';
            $sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender';
            // We need dynamount_payed to be able to sort on status (value is surely wrong because we can count several lines several times due to other left join or link with contacts. But what we need is just 0 or > 0)
            // TODO Better solution to be able to sort on already payed or remain to pay is to store amount_payed in a denormalized field.
            if (!$sall) {
                $sql .= ', SUM(pf.amount) as dynamount_payed, SUM(pf.multicurrency_amount) as multicurrency_dynamount_payed';
            }

            // Add fields from extrafields
            if (!empty($extrafields->attributes[$object->table_element]['label'])) {
                foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                    $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
                }
            }
            // Add fields from hooks
            $parameters = array();
            $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
            $sql .= $hookmanager->resPrint;
            $sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";

            $sql .= ', '.MAIN_DB_PREFIX.'facture as f';
            if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
                $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (f.rowid = ef.fk_object)";
            }
            if (!$sall) {
                $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
            }
            if ($sall) {
                $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
            }

            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = f.fk_projet";
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON f.fk_user_author = u.rowid';
            // We'll need this table joined to the select in order to filter by sale

            // Add table from hooks
            $parameters = array();
            $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
            $sql .= $hookmanager->resPrint;

            $sql .= ' WHERE f.fk_soc = s.rowid';
            $sql .= ' AND f.entity IN ('.getEntity('invoice').')';
            $sql .= " AND s.rowid = ".$object->socid;
            // $sql .= " AND f.rowid NOT IN(".$id.")";
            $sql .= " AND f.rowid NOT IN(".implode(",", $omitir_facturas).")";


            if ($search_ref) {
                $sql .= natural_search('f.ref', $search_ref);
            }

            if ($search_montant_ttc != '') {
                $sql .= natural_search('f.total_ttc', $search_montant_ttc, 1);
            }

            if ($search_status != '-1' && $search_status != '') {
                if (is_numeric($search_status) && $search_status >= 0) {
                    if ($search_status == '0') {
                        $sql .= " AND f.fk_statut = 0"; // draft
                    }
                    if ($search_status == '1') {
                        $sql .= " AND f.fk_statut = 1"; // unpayed
                    }
                    if ($search_status == '2') {
                        $sql .= " AND f.fk_statut = 2"; // payed     Not that some corrupted data may contains f.fk_statut = 1 AND f.paye = 1 (it means payed too but should not happend. If yes, reopen and reclassify billed)
                    }
                    if ($search_status == '3') {
                        $sql .= " AND f.fk_statut = 3"; // abandonned
                    }
                } else {
                    $sql .= " AND f.fk_statut IN (".$db->sanitize($db->escape($search_status)).")"; // When search_status is '1,2' for example
                }
            }

            if ($search_paymentmode > 0) {
                $sql .= " AND f.fk_mode_reglement = ".((int) $search_paymentmode);
            }

            if ($search_date_start) {
                $sql .= " AND f.datef >= '".$db->idate($search_date_start)."'";
            }
            if ($search_date_end) {
                $sql .= " AND f.datef <= '".$db->idate($search_date_end)."'";
            }

            // Add where from extra fields
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
            // Add where from hooks
            $parameters = array();
            $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
            $sql .= $hookmanager->resPrint;

            if (!$sall) {
                $sql .= ' GROUP BY f.rowid, f.ref, ref_client, f.fk_soc, f.type, f.note_private, f.note_public, f.increment, f.fk_mode_reglement, f.fk_cond_reglement, f.total, f.tva, f.total_ttc,';
                $sql .= ' f.localtax1, f.localtax2,';
                $sql .= ' f.datef, f.date_valid, f.date_lim_reglement, f.module_source, f.pos_source,';
                $sql .= ' f.paye, f.fk_statut, f.close_code,';
                $sql .= ' f.datec, f.tms, f.date_closing,';
                $sql .= ' f.retained_warranty, f.retained_warranty_date_limit, f.situation_final, f.situation_cycle_ref, f.situation_counter,';
                $sql .= ' f.fk_user_author, f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht,';
                $sql .= ' f.multicurrency_total_tva, f.multicurrency_total_ttc,';
                $sql .= ' s.rowid, s.nom, s.name_alias, s.email, s.phone, s.fax, s.address, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur,';
                $sql .= ' typent.code,';
                $sql .= ' state.code_departement, state.nom,';
                $sql .= ' country.code,';
                $sql .= " p.rowid, p.ref, p.title,";
                $sql .= " u.login, u.lastname, u.firstname, u.email, u.statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender";

                // Add fields from extrafields
                if (!empty($extrafields->attributes[$object->table_element]['label'])) {
                    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
                    }
                }
                // Add GroupBy from hooks
                // $parameters = array('all' => !empty($all) ? $all : 0, 'fieldstosearchall' => $fieldstosearchall);
                // $reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object); // Note that $action and $object may have been modified by hook
                // $sql .= $hookmanager->resPrint;
            } else {
                // $sql .= natural_search(array_keys($fieldstosearchall), $sall);
            }

            // Add HAVING from hooks
            $parameters = array();
            $reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object); // Note that $action and $object may have been modified by hook
            $sql .= empty($hookmanager->resPrint) ? "" : " HAVING 1=1 ".$hookmanager->resPrint;

            $sql .= ' ORDER BY ';
            $listfield = explode(',', $sortfield);
            $listorder = explode(',', $sortorder);
            foreach ($listfield as $key => $value) {
                $sql .= $listfield[$key].' '.($listorder[$key] ? $listorder[$key] : 'DESC').',';
            }
            $sql .= ' f.rowid DESC ';

            $nbtotalofrecords = '';
            if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
                $result = $db->query($sql);
                $nbtotalofrecords = $db->num_rows($result);
                if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
                    $page = 0;
                    $offset = 0;
                }
            }

            $sql .= $db->plimit($limit + 1, $offset);

            $resql = $db->query($sql);
            // print '<pre>';
            //     print $sql;
            // print '</pre>';

            if ($resql) {
                $num = $db->num_rows($resql);

                $param = '&socid='.urlencode($socid);
                $param .= '&facid='.$id;


                print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'?facid='.$id.'">'."\n";
                    // print '<input type="hidden" name="id" value="'.$id.'">';
                    print '<input type="hidden" name="token" value="'.newToken().'">';
                    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
                    print '<input type="hidden" name="action" id="action" value="agruparfacturas">';

                    print '<div class="div-table-responsive">';
                        print '<table class="tagtable liste'.(@$moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

                            print '<td>&nbsp;</td>';

                            // Ref
                            if (!empty($arrayfields['f.ref']['checked'])) {
                                print '<td class="liste_titre" align="left">';
                                print '<input class="flat maxwidth50imp" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
                                print '</td>';
                            }

                            // Date invoice
                            if (!empty($arrayfields['f.datef']['checked'])) {
                                print '<td class="liste_titre center">';
                                print '<div class="nowrap">';
                                print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
                                print '</div>';
                                print '<div class="nowrap">';
                                print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
                                print '</div>';
                                print '</td>';
                            }

                            // Payment mode
                            if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
                                print '<td class="liste_titre">';
                                $form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 1, 1, 10);
                                print '</td>';
                            }

                            if (!empty($arrayfields['f.total_ttc']['checked'])) {
                                // Amount
                                print '<td class="liste_titre right">';
                                print '<input class="flat" type="text" size="4" name="search_montant_ttc" value="'.dol_escape_htmltag($search_montant_ttc).'">';
                                print '</td>';
                            }

                            // Status
                            if (!empty($arrayfields['f.fk_statut']['checked'])) {
                                print '<td class="liste_titre maxwidthonsmartphone right">';
                                $liststatus = array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '0,1'=>$langs->trans("BillShortStatusDraft").'+'.$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '1,2'=>$langs->trans("BillShortStatusNotPaid").'+'.$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
                                print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', '', 1);
                                print '</td>';
                            }

                            // Action column
                            print '<td class="liste_titre" align="middle">';
                            $searchpicto = $form->showFilterButtons();
                            print $searchpicto;
                            print '</td>';
                            print "</tr>\n";

                            print '<tr class="liste_titre">';
                                print '<td>&nbsp;</td>';

                                if (!empty($arrayfields['f.ref']['checked'])) {
                                    print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER['PHP_SELF'], 'f.ref', '', $param, '', $sortfield, $sortorder);
                                }

                                if (!empty($arrayfields['f.datef']['checked'])) {
                                    print_liste_field_titre($arrayfields['f.datef']['label'], $_SERVER['PHP_SELF'], 'f.datef', '', $param, 'align="center"', $sortfield, $sortorder);
                                }

                                if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
                                    print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "f.fk_mode_reglement", "", $param, "", $sortfield, $sortorder);
                                }

                                if (!empty($arrayfields['f.total_ttc']['checked'])) {
                                    print_liste_field_titre($arrayfields['f.total_ttc']['label'], $_SERVER['PHP_SELF'], 'f.total_ttc', '', $param, 'class="right"', $sortfield, $sortorder);
                                }

                                if (!empty($arrayfields['f.fk_statut']['checked'])) {
                                    print_liste_field_titre($arrayfields['f.fk_statut']['label'], $_SERVER["PHP_SELF"], "f.fk_statut,f.paye,f.type,dynamount_payed", "", $param, 'class="right"', $sortfield, $sortorder);
                                }

                                print '<td>&nbsp;</td>';
                            print '</tr>';

                            $facturestatic = new Facture($db);
                            $projectstatic = new Project($db);
                            $discount = new DiscountAbsolute($db);
                            $userstatic = new User($db);
                            $companystatic = new Societe($db);

                            if ($num > 0) {
                                $i = 0;
                                $totalarray = array();
                                $totalarray['nbfield'] = 0;
                                $totalarray['val'] = array();
                                $totalarray['val']['f.total'] = 0;
                                $totalarray['val']['f.total_ttc'] = 0;


                                while ($i < min($num, $limit)) {
                                    $obj = $db->fetch_object($resql);

                                    // print 'ekeke';

                                    $facturestatic->id = $obj->id;
                                    $facturestatic->ref = $obj->ref;
                                    $facturestatic->ref_client = $obj->ref_client;
                                    $facturestatic->type = $obj->type;
                                    $facturestatic->total_ht = $obj->total_ht;
                                    $facturestatic->total_tva = $obj->total_tva;
                                    $facturestatic->total_ttc = $obj->total_ttc;
                                    $facturestatic->multicurrency_code = $obj->multicurrency_code;
                                    $facturestatic->multicurrency_tx = $obj->multicurrency_tx;
                                    $facturestatic->multicurrency_total_ht = $obj->multicurrency_total_ht;
                                    $facturestatic->multicurrency_total_tva = $obj->multicurrency_total_vat;
                                    $facturestatic->multicurrency_total_ttc = $obj->multicurrency_total_ttc;
                                    $facturestatic->statut = $obj->fk_statut;
                                    $facturestatic->close_code = $obj->close_code;
                                    $facturestatic->total_ttc = $obj->total_ttc;
                                    $facturestatic->paye = $obj->paye;
                                    $facturestatic->fk_soc = $obj->fk_soc;

                                    $facturestatic->date = $db->jdate($obj->datef);
                                    $facturestatic->date_valid = $db->jdate($obj->date_valid);
                                    $facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);

                                    $facturestatic->note_public = $obj->note_public;
                                    $facturestatic->note_private = $obj->note_private;
                                    if (!empty($conf->global->INVOICE_USE_SITUATION) && !empty($conf->global->INVOICE_USE_RETAINED_WARRANTY)) {
                                        $facturestatic->retained_warranty = $obj->retained_warranty;
                                        $facturestatic->retained_warranty_date_limit = $obj->retained_warranty_date_limit;
                                        $facturestatic->situation_final = $obj->retained_warranty_date_limit;
                                        $facturestatic->situation_final = $obj->retained_warranty_date_limit;
                                        $facturestatic->situation_cycle_ref = $obj->situation_cycle_ref;
                                        $facturestatic->situation_counter = $obj->situation_counter;
                                    }
                                    $companystatic->id = $obj->socid;
                                    $companystatic->name = $obj->name;
                                    $companystatic->name_alias = $obj->alias;
                                    $companystatic->client = $obj->client;
                                    $companystatic->fournisseur = $obj->fournisseur;
                                    $companystatic->code_client = $obj->code_client;
                                    $companystatic->code_compta_client = $obj->code_compta_client;
                                    $companystatic->code_fournisseur = $obj->code_fournisseur;
                                    $companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
                                    $companystatic->email = $obj->email;
                                    $companystatic->phone = $obj->phone;
                                    $companystatic->fax = $obj->fax;
                                    $companystatic->address = $obj->address;
                                    $companystatic->zip = $obj->zip;
                                    $companystatic->town = $obj->town;
                                    $companystatic->country_code = $obj->country_code;

                                    $projectstatic->id = $obj->project_id;
                                    $projectstatic->ref = $obj->project_ref;
                                    $projectstatic->title = $obj->project_label;

                                    $paiement = $facturestatic->getSommePaiement();
                                    $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
                                    $totaldeposits = $facturestatic->getSumDepositsUsed();
                                    $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
                                    $remaintopay = price2num($facturestatic->total_ttc - $totalpay);
                                    $multicurrency_paiement = $facturestatic->getSommePaiement(1);
                                    $multicurrency_totalcreditnotes = $facturestatic->getSumCreditNotesUsed(1);
                                    $multicurrency_totaldeposits = $facturestatic->getSumDepositsUsed(1);
                                    $multicurrency_totalpay = $multicurrency_paiement + $multicurrency_totalcreditnotes + $multicurrency_totaldeposits;
                                    $multicurrency_remaintopay = price2num($facturestatic->multicurrency_total_ttc - $multicurrency_totalpay);

                                    if ($facturestatic->statut == Facture::STATUS_CLOSED && $facturestatic->close_code == 'discount_vat') {		// If invoice closed with discount for anticipated payment
                                        $remaintopay = 0;
                                        $multicurrency_remaintopay = 0;
                                    }
                                    if ($facturestatic->type == Facture::TYPE_CREDIT_NOTE && $obj->paye == 1) {		// If credit note closed, we take into account the amount not yet consummed
                                        $remaincreditnote = $discount->getAvailableDiscounts($companystatic, '', 'rc.fk_facture_source='.$facturestatic->id);
                                        $remaintopay = -$remaincreditnote;
                                        $totalpay = price2num($facturestatic->total_ttc - $remaintopay);
                                        $multicurrency_remaincreditnote = $discount->getAvailableDiscounts($companystatic, '', 'rc.fk_facture_source='.$facturestatic->id, 0, 0, 1);
                                        $multicurrency_remaintopay = -$multicurrency_remaincreditnote;
                                        $multicurrency_totalpay = price2num($facturestatic->multicurrency_total_ttc - $multicurrency_remaintopay);
                                    }

                                    $facturestatic->alreadypaid = $paiement;

                                    print '<tr>';

                                    print '<td>';
                                        print '<input type="checkbox" name="ban_factura[]" id="ban_factura'.$i.'" onchange="seleccionarFactura('.$i.');">';
                                        print '<input type="hidden" name="sel_factura[]" id="sel_factura'.$i.'">';
                                        print '<input type="hidden" name="list_facturas[]" id="list_facturas'.$i.'" value="'.$facturestatic->id.'">';
                                    print '</td>';

                                    // Ref
                                    if (!empty($arrayfields['f.ref']['checked'])) {
                                        print '<td class="nowraponall">';

                                        print '<table class="nobordernopadding"><tr class="nocellnopadd">';

                                        print '<td class="nobordernopadding nowraponall">';
                                        if ($contextpage == 'poslist') {
                                            print dol_escape_htmltag($obj->ref);
                                        } else {
                                            print $facturestatic->getNomUrl(1, '', 200, 0, '', 0, 1);
                                        }

                                        $filename = dol_sanitizeFileName($obj->ref);
                                        $filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
                                        $urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->id;
                                        print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
                                        print '</td>';
                                        print '</tr>';
                                        print '</table>';

                                        print "</td>\n";
                                        if (!$i) {
                                            $totalarray['nbfield']++;
                                        }
                                    }

                                    // Date
                                    if (!empty($arrayfields['f.datef']['checked'])) {
                                        print '<td align="center" class="nowraponall">';
                                        print dol_print_date($db->jdate($obj->datef), 'day');
                                        print '</td>';
                                        if (!$i) {
                                            $totalarray['nbfield']++;
                                        }
                                    }

                                    // Payment mode
                                    if (!empty($arrayfields['f.fk_mode_reglement']['checked'])) {
                                        print '<td class="tdoverflowmax100">';
                                        $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
                                        print '</td>';
                                        if (!$i) {
                                            $totalarray['nbfield']++;
                                        }
                                    }

                                    // Amount TTC
                                    if (!empty($arrayfields['f.total_ttc']['checked'])) {
                                        print '<td class="right nowraponall amount">'.price($obj->total_ttc)."</td>\n";
                                        if (!$i) {
                                            $totalarray['nbfield']++;
                                        }
                                        if (!$i) {
                                            $totalarray['pos'][$totalarray['nbfield']] = 'f.total_ttc';
                                        }
                                        $totalarray['val']['f.total_ttc'] += $obj->total_ttc;
                                    }

                                    // Status
                                    if (!empty($arrayfields['f.fk_statut']['checked'])) {
                                        print '<td class="nowrap right">';
                                        print $facturestatic->LibStatut($obj->paye, $obj->fk_statut, 5, $paiement, $obj->type);
                                        print "</td>";
                                        if (!$i) {
                                            $totalarray['nbfield']++;
                                        }
                                    }

                                    print '<td>&nbsp;</td>';

                                    print "</tr>\n";

                                    $i++;
                                }
                            }
                        print '</table>';

                        print '<div align="center" >';
                            print '<input name="agrupar" type="submit" value="Agrupar" class="butAction">';
                        print '</div>';
                    print '</div>';
                print '</form>';


                print '<div class="clearboth"></div>';
            }else{
                dol_print_error($db);
            }

            print dol_get_fiche_end();
        }else {
            dol_print_error($db, $object->error);
        }
    }

    llxFooter();
    $db->close();
?>