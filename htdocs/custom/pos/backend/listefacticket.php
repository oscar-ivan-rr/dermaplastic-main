<?php
/* Copyright (C) 2011-2012 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/pos/backend/listefacticket.php
 *	\ingroup    ticket
 *	\brief      Page to list tickets
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
require_once(DOL_DOCUMENT_ROOT ."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php");
dol_include_once('/pos/class/pos.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load('pos@pos');
$langs->load('deliveries');
$langs->load('companies');

$ticketyear=GETPOST("ticketyear","int");
$ticketmonth=GETPOST("ticketmonth","int");
$deliveryyear=GETPOST("deliveryyear","int");
$deliverymonth=GETPOST("deliverymonth","int");
$socid=GETPOST('socid','int');
$userid=GETPOST('userid','int');
$viewstatut=GETPOST('viewstatut');
$viewtype=GETPOST('viewtype');
$closeid=GETPOST('closeid','int');
$placeid=GETPOST('placeid','int');
$cashid=GETPOST('cashid','int');
$surtir=GETPOST('surtir');
$estado=GETPOST('estado','int');
$estado_v=GETPOST('state_v','int');
$action=GETPOST('action','string');
$mass_action = GETPOST('select_massaction', 'alpha');
$dfinicio=dol_mktime(12, 0, 0, $_GET['iniciomonth'], $_GET['inicioday'], $_GET['inicioyear']);
$dfinicio = dol_print_date($dfinicio,'%Y-%m-%d');
$dffin=dol_mktime(12, 0, 0, $_GET['finmonth'], $_GET['finday'], $_GET['finyear']);
$dffin = dol_print_date($dffin,'%Y-%m-%d');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$month    =GETPOST('month','int');
$year     =GETPOST('year','int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='df';


// -----------
//    Begin multiple tickets to single facture
//--------------
$invoicedtickets = array();
if($mass_action == 'facture'){
    require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
    $tempTicket = New Ticket($db);
    foreach($_REQUEST as $item=>$value){
        $substring = substr($item, 0, 7);
        if($substring == 'ticket_'){
            $tempTicket->fetch($value);
            if(!$tempTicket->fk_facture){
                array_push($invoicedtickets, $value);
            }
        }
    }
    if(sizeof($invoicedtickets) > 0){
        $facture = New Facture($db);

        $tempTicket->fetch($invoicedtickets[0]);

        $facture->socid =$tempTicket->socid;
        $facture->client = $tempTicket->client;
        $facture->author = $tempTicket->author;
        $facture->fk_user_author = $tempTicket->fk_user_author;
        $facture->fk_user_valid = $tempTicket->fk_user_valid;
        $now=dol_now();
        $facture->date = $now;				// Ticket date
        $facture->date_creation =  $now;		// Creation date
        $facture->datem = $tempTicket->datem;
        $facture->ref = $tempTicket->ref;
        $facture->type = 0;

        $facture->remise_absolue = $tempTicket->remise_absolute;
        $facture->remise_percent = $tempTicket->remise_percent;
        $facture->note = $tempTicket->note;
        $facture->note_public = $tempTicket->note_public;
        $facture->statut = $tempTicket->statut;
        $facture->close_code = $tempTicket->close_code;
        $facture->close_note = $tempTicket->close_note;
        $facture->paye = 0;
        $facture->mode_reglement_id = $tempTicket->mode_reglement_id;			// Id in llx_c_paiement
        $facture->mode_reglement_code = $tempTicket->mode_reglement_code;		// Code in llx_c_paiement
        $facture->modelpdf = $tempTicket->modelpdf;
        $facture->products = $tempTicket->products;	// TODO deprecated
        $facture->line = $tempTicket->line;
        $facture->nbtodo = $tempTicket->nbtodo;
        $facture->nbtodolate = $tempTicket->nbtodolate;
        $facture->specimen = $tempTicket->specimen;

        $n = 0;
        $temporal_ht = 0;
        $temporal_tva = 0;
        $temporal_ttc = 0;
        foreach ($invoicedtickets as $id)
        {
            $ticket = New Ticket($db);
            $ticket->fetch($id);

            for ($i = 0 ; $i < sizeof($ticket->lines) ; $i++) {
                $factline = new FactureLigne($db);

                $factline->fk_parent_line = $ticket->lines[$i]->fk_parent_line;
                //! Description ligne
                $factline->desc = $ticket->lines[$i]->desc;

                $factline->fk_product = $ticket->lines[$i]->fk_product;        // Id of predefined product
                $factline->product_type = $ticket->lines[$i]->product_type;    // Type 0 = product, 1 = Service

                $factline->qty = $ticket->lines[$i]->qty;                // Quantity (example 2)
                $factline->tva_tx = $ticket->lines[$i]->tva_tx;            // Taux tva produit/service (example 19.6)
                $factline->localtax1_tx = $ticket->lines[$i]->localtax1_tx;        // Local tax 1
                $factline->localtax2_tx = $ticket->lines[$i]->localtax2_tx;        // Local tax 2
                $factline->subprice = $ticket->lines[$i]->subprice;        // P.U. HT (example 100)
                $factline->remise_percent = $ticket->lines[$i]->remise_percent;    // % de la remise ligne (example 20%)
                $factline->fk_remise_except = $ticket->lines[$i]->fk_remise_except;    // Link to line into llx_remise_except
                $factline->rang = $ticket->lines[$i]->rang;

                $factline->info_bits = $ticket->lines[$i]->info_bits;        // Liste d'options cumulables:
                // Bit 0:	0 si TVA normal - 1 si TVA NPR
                // Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)

                $factline->special_code = $ticket->lines[$i]->special_code;    // Liste d'options non cumulabels:
                // 1: frais de port
                // 2: ecotaxe
                // 3: ??

                $factline->origin = $ticket->lines[$i]->origin;
                $factline->origin_id = $ticket->lines[$i]->origin_id;

                //! Total HT  de la ligne toute quantite et incluant la remise ligne
                $factline->total_ht = $ticket->lines[$i]->total_ht;
                $temporal_ht += $factline->total_ht; //

                //! Total TVA  de la ligne toute quantite et incluant la remise ligne
                $factline->total_tva = $ticket->lines[$i]->total_tva;
                $temporal_tva += $factline->total_tva; //

                $factline->total_localtax1 = $ticket->lines[$i]->total_localtax1; //Total Local tax 1 de la ligne
                $factline->total_localtax2 = $ticket->lines[$i]->total_localtax2; //Total Local tax 2 de la ligne
                //! Total TTC de la ligne toute quantite et incluant la remise ligne
                $factline->total_ttc = $ticket->lines[$i]->total_ttc;
                $temporal_ttc += $factline->total_ttc; //

                $factline->fk_code_ventilation = $ticket->lines[$i]->fk_code_ventilation;
                $factline->fk_export_compta = $ticket->lines[$i]->fk_export_compta;

                $factline->date_start = $ticket->lines[$i]->date_start;
                $factline->date_end = $ticket->lines[$i]->date_end;

                // From llx_product
                $factline->ref = $ticket->lines[$i]->ref;                // Product ref (deprecated)
                $factline->product_ref = $ticket->lines[$i]->product_ref;       // Product ref
                $factline->libelle = $ticket->lines[$i]->libelle;            // Product label (deprecated)
                $factline->product_label = $ticket->lines[$i]->product_label;     // Product label
                $factline->product_desc = $ticket->lines[$i]->product_desc;    // Description produit

                $factline->skip_update_total = $ticket->lines[$i]->skip_update_total; // Skip update price total for special lines
                $facture->lines[$n] = $factline;
                $n++;
            }
        }
        $facture->total_ht = $temporal_ht;
        $facture->total_tva = $temporal_tva;
        $facture->total_ttc = $temporal_ttc;

        $facture->create($user);
        $facture->validate($user);

        foreach ($invoicedtickets as $id){
            $ticket = New Ticket($db);
            $ticket->fetch($id);
            $sql = 'UPDATE '.MAIN_DB_PREFIX."pos_ticket SET fk_facture='".$facture->id."' WHERE rowid=".$ticket->id;

            $resql=$db->query($sql);
            if (! $resql)
            {
                $db->rollback();
                //
                // Code for error handling
                //
            }
            else {
                $db->commit();

                $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'pos_facture (fk_cash, fk_place,fk_facture) VALUES (' . $ticket->fk_cash . ',' . ($ticket->fk_place ? $ticket->fk_place : 'null') . ',' . $facture->id . ')';

                dol_syslog("pos_facture::update sql=" . $sql);
                $resql = $db->query($sql);
                if (!$resql) {
                    $db->rollback();
                    //
                    // Another error handling
                    //
                } else {
                    $db->commit();
                }
                $sql = 'SELECT fk_paiement, amount FROM ' . MAIN_DB_PREFIX . "pos_paiement_ticket WHERE fk_ticket=" . $ticket->id;
                $resql = $db->query($sql);
                if ($resql) {
                    $num = $db->num_rows($resql);
                    $i = 0;
                    $totalpaye = 0;
                    while ($i < $num) {
                        $objp = $db->fetch_object($resql);
                        $paye[$i]['fk_paiement'] = $objp->fk_paiement;
                        $paye[$i]['amount'] = $objp->amount;
                        $i++;

                    }
                    $i = 0;
                    while ($i < $num) {
                        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'paiement_facture (fk_paiement, fk_facture, amount) VALUES (' . $paye[$i]['fk_paiement'] . ',' . $facture->id . ',' . $paye[$i]['amount'] . ')';
                        $resql = $db->query($sql);
                        $i++;
                    }
                } else {
                    return -1;
                }


                $facture->add_object_linked('ticket', $id);
            }
        }
        if($facture->id > 0){
            header('Location: '.DOL_MAIN_URL_ROOT.'/compta/facture/card.php?facid='.$facture->id);
        }
        else{
            setEventMessage('Error al facturar los tickets',"errors");
        }

    }
}
//- ---------
// Begin multiple ticket/facture paiement
//------------
$array_ticket = array();
$array_facture = array();
if($mass_action == 'paiement'){
    // CODE FOR THE SUBMIT OF PAYMENTS
    $strout = '';
    foreach($_REQUEST as $key => $value){
        if(substr($key, 0, 7) == 'ticket_'){
            array_push($array_ticket, $value);
        }
        else if (substr($key, 0, 8) == 'facture_'){
            array_push($array_facture, $value);
        }
    }
    if(sizeof($array_ticket) > 0 or sizeof($array_facture) > 0){
        $firstEntry_flag = true;
        foreach($array_ticket as $item){
            if($firstEntry_flag){
                $strout .= '?ticketid='.$item.'&action=create';
                $firstEntry_flag = false;
            }
            $strout .= '&ticketid'.$item.'='.$item;
        }
        foreach($array_facture as $item){
            if($firstEntry_flag){
                $strout .= '?facid='.$item.'&action=create';
                $firstEntry_flag = false;
            }
            $strout .= '&facid'.$item.'='.$item;
        }
        header('Location: '.DOL_MAIN_URL_ROOT.'/custom/pos/backend/paiementfacticket.php'.$strout);
    }
    else{
        setEventMessage('No se seleccionó nada por pagar', 'errors');
    }

}


if ($action == 'send')
{
    $langs->load('mails');
    $actiontypecode='';$subject='';$actionmsg='';$actionmsg2='';

    if (GETPOST('sendto'))
    {
        // Le destinataire a ete fourni via le champ libre
        $sendto = GETPOST('sendto');
        $sendtoid = 0;
    }
    if (dol_strlen($sendto))
    {
        $langs->load("commercial");

        $from =  $conf->global->MAIN_INFO_SOCIETE_NOM."<".$conf->global->MAIN_INFO_SOCIETE_MAIL.">";
        $message = GETPOST('message','alpha');

        if (GETPOST('action','alpha') == 'send')
        {
            if (dol_strlen(GETPOST('subject','alpha'))) $subject = GETPOST('subject','alpha');
            else $subject = $langs->transnoentities('Bill').' '.$object->ref;
            $actiontypecode='AC_FAC';
            $actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
            if ($message)
            {
                $actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
                $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
                $actionmsg.=$message;
            }
        }


        // Send mail
        require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
        $mailfile = new CMailFile($subject,$sendto,$from,$message);
        if(!preg_match("/^(?:[\w\d]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,4}$/", $sendto)) {
            $mailfile->error = $langs->trans('ErrorFailedToSendMail',$from,$sendto);
        }

        if ($mailfile->error)
        {
            setEventMessage($mailfile->error,"errors");
        }
        else
        {
            $result=$mailfile->sendfile();
            if ($result)
            {
                setEventMessage($langs->trans('MailSuccessfulySent',$from,$sendto));		// Must not contain "

                Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'&mesg=1');
                //exit;

            }
            else
            {
                $langs->load("other");
                if ($mailfile->error)
                {
                    setEventMessage($langs->trans('ErrorFailedToSendMail',$from,$sendto).'<br>'.$mailfile->error,"errors");
                }
                else
                {
                    setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS',"errors");
                }

            }
        }
    }
    else
    {
        $langs->load("other");
        setEventMessage($langs->trans('ErrorMailRecipientIsEmpty'),"errors");
        dol_syslog('Recipient email is empty');
    }

    $_GET['action'] = 'presend';
}
/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader("",$langs->trans("Tickets"),$helpurl);
dol_htmloutput_events();

$html = new FormOther($db);
$ticketstatic=new Ticket($db);
$facturestatic=new Facture($db);
$formfile = new FormFile($db);
$now=dol_now();


if ($user->rights->pos->backend) {
    if ($page == -1) $page = 0 ;

    $sql = "SELECT objeto, id, type, ref, total, total_ttc, df, user, paye, fk_statut, am, nom, socid, total_of_surtir, total_dev, total_prod, total_surtido FROM (";
    //Ticket
    $sql.= "(SELECT 'ticket' as objeto, f.rowid as id, f.type, f.ticketnumber as ref, f.total_ht as total, f.total_ttc, f.date_creation as df,";
    $sql.= " f.fk_user_close as user, f.paye as paye, f.fk_statut, f.customer_pay as am, s.nom, s.rowid as socid,";
    //Obtener los estado_v de surtido, por surtir y devolucion
    $sql.= " (SELECT COUNT(DISTINCT lptd.rowid) FROM ".MAIN_DB_PREFIX."pos_ticketdet as lptd";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."product as prod on lptd.fk_product=prod.rowid";
    $sql.= " WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=4) as total_of_surtir,";
    $sql.= " (SELECT COUNT(DISTINCT lptd.rowid) FROM ".MAIN_DB_PREFIX."pos_ticketdet as lptd";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."product as prod on lptd.fk_product=prod.rowid";
    $sql.= " WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=8) as total_dev,";
    $sql.= " (SELECT COUNT(DISTINCT lptd.rowid) FROM ".MAIN_DB_PREFIX."pos_ticketdet as lptd";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."product as prod on lptd.fk_product=prod.rowid";
    $sql.= " WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=6) as total_surtido,";
    $sql.= " (SELECT COUNT(DISTINCT lptd.rowid) FROM ".MAIN_DB_PREFIX."pos_ticketdet as lptd";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."product as prod on lptd.fk_product=prod.rowid";
    $sql.= " WHERE lptd.fk_ticket=f.rowid ) as total_prod,";

    $sql.= " t.name, f.fk_cash as cashid";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."pos_ticket as f, ".MAIN_DB_PREFIX."pos_cash as t";
    $sql.= " WHERE f.fk_soc = s.rowid AND t.entity = ".$conf->entity." AND f.fk_cash = t.rowid";
    //Condiciones de filtros en tickets
    if ($socid) $sql.= ' AND s.rowid = '.$socid;
    if ($userid) {
        $sql.= ' AND u.rowid = '.$userid;
        $sql.= ' AND f.fk_user_close = u.rowid';
    }
    if ($viewstatut <> '') $sql.= ' AND f.fk_statut = '.$viewstatut;
    if ($viewtype <> '') $sql.= ' AND f.type = '.$viewtype;
    if ($closeid <> '') $sql.= ' AND f.fk_control = '.$closeid;
    if ($placeid <> '') $sql.= ' AND f.fk_place = '.$placeid;
    if ($cashid <> '') $sql.= ' AND f.fk_cash = '.$cashid;
    if ($_GET['filtre'])
    {
        $filtrearr = explode(',', $_GET['filtre']);
        foreach ($filtrearr as $fil)
        {
            $filt = explode(':', $fil);
            $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
        }
    }
    if ($_GET['search_ref'])
    {
        $sql.= ' AND f.ticketnumber LIKE \'%'.$db->escape(trim($_GET['search_ref'])).'%\'';
    }
    if ($_GET['surtir'])
    {
        $sql.= ' AND (SELECT COUNT(lptd.rowid) FROM '.MAIN_DB_PREFIX.'pos_ticketdet as lptd LEFT JOIN '.MAIN_DB_PREFIX.'product as prod on lptd.fk_product=prod.rowid WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=4 AND lptd.qty<=prod.stock) > 0';
    }
    if ($_GET['search_cash'])
    {
        $sql.= ' AND t.name LIKE \'%'.$db->escape(trim($_GET['search_cash'])).'%\'';
    }
    if ($_GET['search_user'])
    {
        $sql.= ' AND (u.firstname LIKE \'%'.$db->escape(trim($_GET['search_user'])).'%\'';
        $sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($_GET['search_user'])).'%\')';
    }
    if ($_GET['search_societe'])
    {
        $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($_GET['search_societe'])).'%\'';
    }
    if ($_GET['search_montant_ht'])
    {
        $sql.= ' AND f.total_ht = \''.$db->escape(trim($_GET['search_montant_ht'])).'\'';
    }
    if ($_GET['search_montant_ttc'])
    {
        $sql.= ' AND f.total_ttc = \''.$db->escape(trim($_GET['search_montant_ttc'])).'\'';
    }
    if($dfinicio && $dffin)
    {
        $sql.= " AND f.date_ticket BETWEEN '".$dfinicio." 00:00:00' AND '".$dffin." 23:59:59'";
    }
    else if($dfinicio & !$dffin)
    {
        $sql.= " AND f.date_ticket > '".$dfinicio." 00:00:00'";
    }
    else if(!$dfinicio && $dffin)
    {
        $sql.= " AND f.date_ticket < '".$dffin." 23:59:59'";
    }
    if ($_POST['sf_ref'])
    {
        $sql.= ' AND f.ticketnumber LIKE \'%'.$db->escape(trim($_POST['sf_ref'])) . '%\'';
    }
    if($estado && $estado>0)
    {
        if($estado == 2)
            $sql.= ' AND f.fk_statut = 1';
        else if ($estado == 1)
            $sql.= ' AND f.fk_statut = 2';
        else if ($estado == 3)
            $sql.= ' AND f.fk_statut = 3';
    }
    if ($sall)
    {
        $sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.ticketnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
    }
    //Fin de condcionales de filtro en tickets
    $sql.= " GROUP BY t.rowid, f.ticketnumber, f.total_ht, f.total_ttc, f.date_ticket, f.paye, f.fk_statut, s.nom, s.rowid";
    $sql.= " ORDER BY t.rowid DESC)";
    $sql.= " UNION";
    //Facturas
    $sql.= " (SELECT 'factura' as objeto,f.rowid as id, f.type, f.ref as ref, f.total as total, f.total_ttc, f.datef as df, f.fk_user_valid as user,";
    $sql.= " f.paye as paye,f.fk_statut,SUM(pf.amount) as am, s.nom, s.rowid as socid,";
    $sql.= " '' as total_of_surtir,'' as total_dev,'' as total_surtido,'' as total_prod,";
    $sql.= " ca.name as cash, ca.rowid as cashid";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."pos_cash as ca, ".MAIN_DB_PREFIX."pos_facture as posf, ".MAIN_DB_PREFIX."facture as f";
    $sql.= " INNER JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_facture = f.rowid";
    $sql.= " WHERE f.fk_soc = s.rowid AND f.entity = ".$conf->entity." AND posf.fk_facture = f.rowid AND posf.fk_cash = ca.rowid";
    //Condicionales de filtro en facturas
    if ($socid) $sql.= ' AND s.rowid = '.$socid;
    if ($userid) {
        $sql.= ' AND u.rowid = '.$userid;
        $sql.= ' AND f.fk_user_valid = u.rowid';
    }
    if ($viewstatut <> '') $sql.= ' AND f.fk_statut = '.$viewstatut;
    if ($viewtype <> '') $sql.= ' AND f.type = '.$viewtype;
    if ($closeid <> '') $sql.= ' AND f.fk_control = '.$closeid;
    if ($placeid <> '') $sql.= ' AND f.fk_place = '.$placeid;
    if ($cashid <> '') $sql.= ' AND f.fk_cash = '.$cashid;
    if ($_GET['filtre'])
    {
        $filtrearr = explode(',', $_GET['filtre']);
        foreach ($filtrearr as $fil)
        {
            $filt = explode(':', $fil);
            $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
        }
    }
    if ($_GET['search_ref'])
    {
        $sql.= ' AND f.ref LIKE \'%'.$db->escape(trim($_GET['search_ref'])).'%\'';
    }
    if ($_GET['surtir'])
    {
        $sql.= ' AND (SELECT COUNT(lptd.rowid) FROM '.MAIN_DB_PREFIX.'pos_ticketdet as lptd LEFT JOIN '.MAIN_DB_PREFIX.'product as prod on lptd.fk_product=prod.rowid WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=4 AND lptd.qty<=prod.stock) > 0';
    }
    if ($_GET['search_cash'])
    {
        $sql.= ' AND ca.name LIKE \'%'.$db->escape(trim($_GET['search_cash'])).'%\'';
    }
    if ($_GET['search_user'])
    {
        $sql.= ' AND (u.firstname LIKE \'%'.$db->escape(trim($_GET['search_user'])).'%\'';
        $sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($_GET['search_user'])).'%\')';
    }
    if ($_GET['search_societe'])
    {
        $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($_GET['search_societe'])).'%\'';
    }
    if ($_GET['search_montant_ht'])
    {
        $sql.= ' AND f.total = \''.$db->escape(trim($_GET['search_montant_ht'])).'\'';
    }
    if ($_GET['search_montant_ttc'])
    {
        $sql.= ' AND f.total_ttc = \''.$db->escape(trim($_GET['search_montant_ttc'])).'\'';
    }
    if($dfinicio && $dffin)
    {
        $sql.= " AND f.datef BETWEEN '".$dfinicio." 00:00:00' AND '".$dffin." 23:59:59'";
    }
    else if($dfinicio & !$dffin)
    {
        $sql.= " AND f.datef > '".$dfinicio." 00:00:00'";
    }
    else if(!$dfinicio && $dffin)
    {
        $sql.= " AND f.datef < '".$dffin." 23:59:59'";
    }
    if ($_POST['sf_ref'])
    {
        $sql.= ' AND f.ref LIKE \'%'.$db->escape(trim($_POST['sf_ref'])) . '%\'';
    }
    if($estado && $estado>0)
    {
        $sql.= ' AND f.fk_statut = '.$estado;
    }
    if ($sall)
    {
        $sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.ticketnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
    }
    //Fin de condiconales de filtro en facturas
    $sql.= " GROUP BY f.rowid, f.ref, f.type, f.increment, f.total, f.total_ttc, f.datef, f.date_lim_reglement, f.paye, f.fk_statut, s.nom, s.rowid";
    $sql.= " ORDER BY f.rowid DESC)) as main";
    /*if (! $sall)
    {
        $sql.= ' GROUP BY f.rowid, f.ticketnumber, f.total_ht, f.total_ttc,';
        $sql.= ' f.date_ticket,';
        $sql.= ' f.paye, f.fk_statut,';
        $sql.= ' s.nom, s.rowid';
    }*/
    if($estado_v > 0)
    {
        if($estado_v == 6) {
            if ($estado && $estado > 0)
                $sql .= " WHERE ((total_surtido = total_prod and total_prod > 0 and total_of_surtir = 0) or total_surtido = '')";
            else
                $sql .= " WHERE total_surtido = total_prod and total_prod > 0 and total_of_surtir = 0";
        }else if($estado_v == 4) {
            //$sql .= " WHERE total_of_surtir = total_prod and total_prod > 0";
            if ($estado && $estado > 0)
                $sql .= " WHERE ((total_of_surtir > 0) or total_of_surtir = '')";
            else
                $sql .= " WHERE total_of_surtir > 0";
        }
        else if($estado_v == 8) {
            if ($estado && $estado > 0)
                $sql .= " WHERE ((total_dev = total_prod and total_prod > 0) or total_dev = '')";
            else
                $sql .= " WHERE total_dev = total_prod and total_prod > 0";
        }
    }
    else if ($estado_v == -1){
        if ($estado && $estado > 0)
            $sql .= " WHERE ((total_surtido = total_prod and total_prod > 0 and total_of_surtir = 0) or (total_surtido = '') or (total_of_surtir > 0))";
        else
            $sql .= " WHERE ((total_surtido = total_prod and total_prod > 0 and total_of_surtir = 0) or (total_of_surtir > 0))";
    }
    $sql.= ' ORDER BY ';
    $sql.= $sortfield." ".$sortorder;

    $nbtotalofrecords = '';
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
        $sql.= $db->plimit($limit+1, $offset);
        $resql = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($resql);

        // if total resultset is smaller then paging size (filtering), goto and load page 0
        if (($page * $limit) > $nbtotalofrecords) {
            $page = 0;
            $offset = 0;
        }
    } else {
        $sql.= $db->plimit($limit+1, $offset);
        $resql = $db->query($sql);
    }

    if ($resql)
    {
        $num = $db->num_rows($resql);

        if ($socid)
        {
            $soc = new Societe($db);
            $soc->fetch($socid);
        }

        $param='&amp;socid='.$socid;
        if ($month) $param.='&amp;month='.$month;
        if ($year)  $param.='&amp;year=' .$year;
        if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
        if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
        if ($sall)				 $param .= '&sall='.urlencode($sall);
        if ($search_day)         $param .= '&search_day='.urlencode($search_day);
        if ($search_month)       $param .= '&search_month='.urlencode($search_month);
        if ($search_year)        $param .= '&search_year='.urlencode($search_year);
        if ($search_day_lim)     $param .= '&search_day_lim='.urlencode($search_day_lim);
        if ($search_month_lim)   $param .= '&search_month_lim='.urlencode($search_month_lim);
        if ($search_year_lim)    $param .= '&search_year_lim='.urlencode($search_year_lim);
        if ($search_ref)         $param .= '&search_ref='.urlencode($search_ref);
        if ($search_refcustomer) $param .= '&search_refcustomer='.urlencode($search_refcustomer);
        if ($search_project_ref) $param .= '&search_project_ref='.urlencode($search_project_ref);
        if ($search_project)     $param .= '&search_project='.urlencode($search_project);
        if ($search_type != '')  $param .= '&search_type='.urlencode($search_type);
        if ($search_societe)     $param .= '&search_societe='.urlencode($search_societe);
        if ($search_town)        $param .= '&search_town='.urlencode($search_town);
        if ($search_zip)         $param .= '&search_zip='.urlencode($search_zip);
        if ($search_sale > 0)    $param .= '&search_sale='.urlencode($search_sale);
        if ($search_user > 0)    $param .= '&search_user='.urlencode($search_user);
        if ($search_product_category > 0)   $param .= '&search_product_category='.urlencode($search_product_category);
        if ($search_montant_ht != '')  $param .= '&search_montant_ht='.urlencode($search_montant_ht);
        if ($search_montant_vat != '')  $param .= '&search_montant_vat='.urlencode($search_montant_vat);
        if ($search_montant_localtax1 != '')  $param .= '&search_montant_localtax1='.urlencode($search_montant_localtax1);
        if ($search_montant_localtax2 != '')  $param .= '&search_montant_localtax2='.urlencode($search_montant_localtax2);
        if ($search_montant_ttc != '') $param .= '&search_montant_ttc='.urlencode($search_montant_ttc);
        if ($search_status != '') $param .= '&search_status='.urlencode($search_status);
        if ($search_paymentmode > 0) $param .= '&search_paymentmode='.urlencode($search_paymentmode);
        if ($search_paymentterms > 0) $param .= '&search_paymentterms='.urlencode($search_paymentterms);
        if ($search_module_source)  $param .= '&search_module_source='.urlencode($search_module_source);
        if ($search_pos_source)  $param .= '&search_pos_source='.urlencode($search_pos_source);
        if ($show_files)         $param .= '&show_files='.urlencode($show_files);
        if ($option)             $param .= "&search_option=".urlencode($option);
        if ($optioncss != '')    $param .= '&optioncss='.urlencode($optioncss);
        if ($search_categ_cus > 0) $param .= '&search_categ_cus='.urlencode($search_categ_cus);

        $txtListe = $langs->trans('TicketsCustomers');

        if ($viewstatut <> '')
        {
            $txtListe = $txtListe." - ".$ticketstatic->LibStatut($viewstatut);
        }

        if ($viewtype <> '')
        {
            $txtListe = $txtListe." - ".$langs->trans("StatusTicketReturned");
        }
        
        $i = 0;
if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}
$ev_style = POS::getEstadovStyle();
       print '<style>';
        print '.modalDialog {
    position: fixed;
    font-family: Arial, Helvetica, sans-serif;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 99999;
    opacity:0;
    -webkit-transition: opacity 400ms ease-in;
    -moz-transition: opacity 400ms ease-in;
    transition: opacity 400ms ease-in;
    pointer-events: none;
}
.modalDialog:target {
    opacity:1;
    pointer-events: auto;
}
.modalDialog > div {
    width: 80%;
    position: relative;
    margin: 10% auto;
    padding: 5px 20px 13px 20px;
    border-radius: 10px;
    background: #fff;
    background: -moz-linear-gradient(#fff, #999);
    background: -webkit-linear-gradient(#fff, #999);
    background: -o-linear-gradient(#fff, #999);
}
.close {
    background: #606061;
    color: #FFFFFF;
    line-height: 25px;
    position: absolute;
    right: -12px;
    text-align: center;
    top: -10px;
    width: 24px;
    text-decoration: none;
    font-weight: bold;
    -webkit-border-radius: 12px;
    -moz-border-radius: 12px;
    border-radius: 12px;
    -moz-box-shadow: 1px 1px 3px #000;
    -webkit-box-shadow: 1px 1px 3px #000;
    box-shadow: 1px 1px 3px #000;
}
.close:hover {
    background: #00d9ff;
}


#rc_resumenevdiv{
	width: 30%;margin-left: 450px;margin-top: -100px;
}
#rc_status_filter{
	display: inline-block;width: 50%;float:right;
}
#rc_reload_button{width: 30%;margin-left: 120px;margin-top: -70px;}
@media only screen and (max-width: 768px)
{
	#rc_resumenevdiv{
		width: 40%;margin-left: 140px;margin-top: -30px;
	}
	#rc_status_filter{
		display: inline-block;width: 90%;float:right;
	}
	#rc_reload_button{width: 30%;margin-left: 0px;margin-top: -70px;}	
}
@media only screen and (max-width: 694px)
{
	#rc_resumenevdiv{
		width: 60%;margin-left: 110px;margin-top: -30px;
	}
	#rc_status_filter{
		display: inline-block;width: 90%;float:right;
	}
	#rc_reload_button{width: 30%;margin-left: 0px;margin-top: -70px;}	
}

#rc_resumenev tr td {padding:5px;}
'.$ev_style.'

.modal .modal-content{padding:0 !important;} .modal{width: 95% !important;}
select{display:block;overflow: auto;}
.width25{width:25px !important}
.maxwidth75imp{max-width: 75px !important; display: inline-block !important;}
select.flat{
padding-top: 4px;
    padding-right: 4px;
    padding-bottom: 3px;
    padding-left: 2px;


	display: inline-block; 
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
	
	background-color: #FFF;		
	border: none;
	
	font-weight: normal;
    font-size: unset;
	height: 25px;
	font-family: roboto,arial,tahoma,verdana,helvetica;
    outline: none;
    margin: -6px 0px 0px 0px;
    border-bottom: solid 1px rgba(0,0,0,.2);

		}
.rc_nowrap_elipsys
{
	display: block;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}';
        print '</style>';
        print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
        print_barre_liste($txtListe.' '.($socid?' '.$soc->nom:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'', $num, $nbtotalofrecords, 'list', 0, '', '', $limit);
        print '<table class="centpercent notopnoleftnoright table-fiche-title">';
        print '<tr><td class="nobordernopadding valignmiddle col-title">';
        print '<select style="font-size: 18px;" id="select_massaction" name="select_massaction">';
        print '<option value="">--Selecciona una Acción--</option>';
        print '<option value="facture">Facturar selección</option>';
        print '<option value="paiement">Pagar selección</option>';
        print '</select>';
        print '<input type="submit" class="button valignmiddle" value="Aceptar"/>';
        print '</td>';
        // INCIO Cambiar nombre
        if($user->rights->pos->change_socid) {
            print '<td class="nobordernopadding valignmiddle right">';
            $tickets = "SELECT t.ticketnumber,s.nom,t.rowid FROM " . MAIN_DB_PREFIX . "pos_ticket as t";
            $tickets .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON t.fk_soc = s.rowid";
            $tickets .= " WHERE t.fk_facture is null";
            $tickets .= " ORDER BY t.rowid DESC LIMIT " . $limit;
            $resqltickets = $db->query($tickets);
            print '<div id="change_namesoc" title="Cambiar de propietario el ticket">';
            print '<p>Seleccione los tickets</p>';
            print '<span id="change_socticket" class="button valignmiddle">Cambiar propietario</span>';
            print '<script>';
            print ' $("#change_socticket").on("click",function(){
                    var checks = [];
                    $(".dialog_checkb").each(function(index){
                        if($(this).is(":checked")) {
                            checks.push($(this).val());
                        }
                    });
                    $.ajax({
                        method: "POST",
                        url: "./ajaxsearchclient.php",
                        data: {
                            "checks": checks, "action": "changeclient","newclient": $("#socid_change").val()
                        },
                        success: function (data) {
                           if(parseInt(data)>0){
                            alter(data+" tickets tuvieron problemas al cambiar el propietario.");
                           }else{
                            location.href ="' . $_SERVER['PHP_SELF'] . '";
                           }
                        }
                    });
                });';
            print '</script>';
            print '<p> Nuevo propietario';
            print $form->select_company('', 'socid_change', '((s.client = 1 OR s.client = 3) AND s.status=1)', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
            print '</p>';
            print '<table id="tabletick" class="liste" width="100%">';
            print '<thead>';
            print '<tr class="liste_titre">';
            print '<th class="liste_titre"><input type="checkbox" id="all_checksdialog" name="all_checksdialog" value="1"></th>';
            print '<th class="liste_titre">Ticket</th>';
            print '<th class="liste_titre">Cliente</th>';
            print '</tr>';
            print '<tr class="liste_titre">';
            print '<th class="liste_titre">&nbsp;</th>';
            print '<th class="liste_titre"><input type="text" id="search_TicketFolio"></th>';
            print '<th class="liste_titre">';
            print $form->select_company('', 'search_SocName', '((s.client = 1 OR s.client = 3) AND s.status=1)', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
            print '</th>';
            print '</tr>';
            print '</thead>';
            print '<tbody id="bodytick">';
            while ($ticketsToChangeClients = $db->fetch_object($resqltickets)) {
                print '<tr>';
                print '<td><input 
                        type="checkbox" 
                        id="ticket_' . $ticketsToChangeClients->rowid . '" 
                        name="ticket_' . $ticketsToChangeClients->rowid . '" 
                        value="' . $ticketsToChangeClients->rowid . '" class="dialog_checkb"
                        ></td>';
                print '<td>' . $ticketsToChangeClients->ticketnumber . '</td>';
                print '<td>' . $ticketsToChangeClients->nom . '</td>';
                print '</tr>';
            }
            print '</tbody></table>';
            print '<script>';
            print '   $("#all_checksdialog").change(function(){
                    if($("#all_checksdialog").is(":checked")) {
                        $(".dialog_checkb").prop("checked",true);
                    }else{
                        $(".dialog_checkb").prop("checked",false);
                    }
                  });';
            print '   $("#search_SocName").on("change",function(){
                    $.ajax({
                        method: "POST",
                        url: "./ajaxsearchclient.php",
                        data: {
                            "name_client": $(this).val(), "action": "search_client","ticket_num": $("#search_TicketFolio").val()
                        },
                        success: function (data) {
                            var html = "";
                            info = JSON.parse(data);
                            info.forEach(function(reg, index) {
                            html += "<tr><td><input type=\"checkbox\" class=\"dialog_checkb\" id=\"ticket_"+reg.rowid+"\" name=\"ticket_"+reg.rowid+"\" value=\""+reg.rowid+"\"></td>";
                            html += "<td>"+reg.ticketnumber+"</td>";
                            html += "<td>"+reg.nom+"</td>";
                            html += "</tr>";
                            });
                            $("#bodytick").html(html);
                        }
                    });
                  });';
            print '   $("#search_TicketFolio").on("input",function(){
                    $.ajax({
                        method: "POST",
                        url: "./ajaxsearchclient.php",
                        data: {
                            "ticket_num": $(this).val(), "action": "search_ticket","name_client": $("#search_SocName").val()
                        },
                        success: function (data) {
                            var html = "";
                            info = JSON.parse(data);
                            info.forEach(function(reg, index) {
                            html += "<tr><td><input type=\"checkbox\" class=\"dialog_checkb\" id=\"ticket_"+reg.rowid+"\" name=\"ticket_"+reg.rowid+"\" value=\""+reg.rowid+"\"></td>";
                            html += "<td>"+reg.ticketnumber+"</td>";
                            html += "<td>"+reg.nom+"</td>";
                            html += "</tr>";
                            });
                            $("#bodytick").html(html);
                        }
                    });
                  });';
            print '</script>';
            print '</div>';
            print '<span id="button_change_namesoc" class="button valignmiddle">Cambiar propietario de ticket</span>';
            print '<script>';
            print '$("#change_namesoc").dialog({autoOpen: false});';
            print '$("#button_change_namesoc").on("click",function(){
                $("#change_namesoc").dialog("option", "width", 700);
                $("#change_namesoc").dialog("option", "height", 550);
                $("#change_namesoc").dialog("open");
        });';
            print '</script>';
            print '</td>';
        }
        // FIN Cambiar nombre
        print '</tr></<table>';
        print '<br>';
        print '<br>';
        print '<table class="liste" width="100%">';
        print '<tr class="liste_titre">';
        print '<th class="liste_titre">&nbsp;</th>';
        print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'ref','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Estado V'),$_SERVER['PHP_SELF'],'surtir','',$param,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'df','',$param,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Customer'),$_SERVER['PHP_SELF'],'nom','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'total','',$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'total_ttc','',$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('User'),$_SERVER['PHP_SELF'],'lastname','',$param,'align="center"',$sortfield,$sortorder);
        print '<th class="liste_titre">&nbsp;</th>';
        print '</tr>';

        // Lignes des champs de filtre
        print '<tr class="liste_titre">';
        print '<td>'; // Select all invoices and tickets
        print '<input type="checkbox" class="ls_tpv_line_checkbox" name="allFacTicSelected" id="allFacTicSelected" '.$allFacTicSelected.'> ';
        print '</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
        print '<td class="liste_titre" colspan="1" align="center">';
        print '<select name="state_v" id="state_v" style="font-size: 18px;">';
        !$estado_v?print '<option value="0" selected>&nbsp;</option>':print '<option value="0">&nbsp;</option>';
        $estado_v == 4?print '<option style="background-color: #0076BA; color: white;" value="4" selected>Por surtir</option>':print '<option style="background-color: #0076BA; color: white;" value="4">Por surtir</option>';
        $estado_v == 6?print '<option style="background-color: #1DB100; color: white;" value="6" selected>Surtidos</option>':print '<option style="background-color: #1DB100; color: white;" value="6">Surtidos</option>';
        $estado_v == 8?print '<option style="background-color: #F8BA00; color: white;" value="8" selected>Devolucion</option>':print '<option style="background-color: #F8BA00; color: white;" value="8">Devolucion</option>';
        $estado_v == -1?print '<option style="background-color: mediumpurple; color: white;" value="-1" selected>Surtido y Por surtir</option>':print '<option style="background-color: mediumpurple; color: white;" value="-1">Surtido y Por surtir</option>';
        print '</select>';
        print '</td>';
        print '<td class="liste_titre" colspan="1" align="center">';
        print $form->selectDate($dfinicio?$dfinicio:-1, 'inicio', '', '', '', "add", 1, 1);
        print $form->selectDate($dffin?$dffin:-1, 'fin', '', '', '', "add", 1, 1);
        print '</td>';

        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="search_societe" value="'.$_GET['search_societe'].'">';
        print '</td><td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
        print '</td><td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET['search_montant_ttc'].'">';
        print '</td>';
        print '<td class="liste_titre" align="right">';
        print '&nbsp;';
        print '</td>';
        print '<td class="liste_titre" align="right">';
        print '<select name="estado" id="estado">';
        print '<option value="-1">&nbsp;</option>';
        print '<option value="2">Cerrado</option>';
        print '<option value="1">Procesado</option>';
        print '<option value="0">Borrador</option>';
        print '</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="search_user" value="'.$_GET['search_user'].'">';
        print '</td>';
        print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print "</td></tr>\n";

        if ($num > 0)
        {
            $var=True;
            $total=0;
            $totalrecu=0;

            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);

                $var=!$var;
                if($objp->objeto == 'ticket') {
                    $ticketstatic->id = $objp->id;
                    $ticketstatic->ref = $objp->ref;
                    $paiement = $ticketstatic->getSommePaiement();
                    $ticketstatic->fetch($objp->id);
                    print '<tr ' . $bc[$var] . '>';
                    // Addition of checkbox
                    if ($ticketstatic->statut == '1' or $ticketstatic->statut == '2') {//
                        print '<td>
                        <input 
                        type="checkbox" 
                        id="ticket_' . $objp->id . '" 
                        name="ticket_' . $objp->id . '" 
                        value="' . $objp->id . '" 
                        ></td>';
                    } else {
                        print '<td>&nbsp;</td>';
                    }
                    print '<td nowrap="nowrap">';
                    print '<table class="nobordernopadding" style="width:100%;min-width:150px;"><tr class="nocellnopadd">';
                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    $chkd = '';
                    if ($objp->total_of_surtir > 0) {
                        print img_picto('Ya hay existencia, listo para surtir', 'star.png', 'style="color:#fc0;width:10px;height:auto;"' , 0, 0, 0);
                    }
                    //print $ticketstatic->ref;
                    print $ticketstatic->getNomUrl(1);
                    print '&nbsp;<a href="#openModal" style="float:right;" class="myButton" onclick="return setModalContent('.$objp->id.','.$ticketstatic->getEntrepot()->id.',0);">';
                    print img_picto('Ver detalle', 'help.png', 'style="color:#fc0;width:15px;height:auto;"' , 0, 0, 0);
                    print '</a>';
                    print '</td>';
                    print '</tr></table>';
                    print "</td>\n";
                    if($objp->total_dev == $objp->total_prod)
                    {
                        print"<td style=\"background-color: #F8BA00;color: white;\">Devolución</td>";
                    }
                    else if($objp->total_surtido == $objp->total_prod && $objp->total_of_surtir == 0)
                    {
                        print'<td style="background-color: #1DB100;color: white;">Surtido</td>';
                    }
                    else if($objp->total_of_surtir > 0 && $objp->total_prod > 0)
                    {
                        print'<td style="background-color: #0076BA;color: white;">Por Surtir</td>';
                    }
                    else
                        print"<td>&nbsp;</td>";
                    // Date
                    print '<td align="center" nowrap>';
                    print dol_print_date($db->jdate($objp->df), 'day');
                    print '</td>';
                    print '<td>';
                    if (!$user->rights->societe->client->voir) {
                        print $objp->nom;
                    } else {
                        $thirdparty = new Societe($db);
                        $thirdparty->id = $objp->socid;
                        $thirdparty->nom = $objp->nom;
                        print $thirdparty->getNomUrl(1, 'customer');
                    }
                    print '</td>';
                    if ($objp->type == 0) {
                        $objtotal = $objp->total;
                        $objttc = $objp->total_ttc;
                        $objcustpay = $objp->total_ttc > $objp->am ? $objp->am : $objp->total_ttc;
                        $objdifpay = $objp->total_ttc - $objcustpay;
                    } else {
                        $objtotal = $objp->total * -1;
                        $objttc = $objp->total_ttc * -1;
                        $objcustpay = $objp->total_ttc * -1;
                    }
                    print '<td align="right">' . price($objtotal) . '</td>';
                    print '<td align="right">' . price($objttc) . '</td>';
                    print '<td align="right">' . price($objcustpay) . '</td>';
                    //print '<td align="right">'.price($objdifpay).'</td>';
                    // Affiche statut de la ticket
                    print '<td align="center" nowrap="nowrap">';
                    print $ticketstatic->LibStatut($objp->fk_statut, 1);
                    print "</td>";
                    print '<td>';
                    if ($objp->user > 0) {
                        $userstatic = new User($db);
                        $userstatic->fetch($objp->user);
                        print $userstatic->getNomUrl(1);
                    }
                    print '</td>';
                    print "<td>&nbsp;</td>";
                    print "</tr>\n";
                }
                else{
                    //$datelimit=$db->jdate($objp->datelimite);
                    $facturestatic->id=$objp->id;
                    $facturestatic->ref=$objp->ref;
                    $facturestatic->type=$objp->type;
                    $paiement = $facturestatic->getSommePaiement();
                    $facturestatic->fetch($facturestatic->id);
                    print '<tr '.$bc[$var].'>';
                    if ($facturestatic->paye != '1') {//
                        print '<td>
                        <input 
                        type="checkbox" 
                        id="facture_' . $objp->id . '" 
                        name="facture_' . $objp->id . '" 
                        value="' . $objp->id . '" 
                        ></td>';
                    } else {
                        print '<td>&nbsp;</td>';
                    }
                    print '<td nowrap="nowrap">';

                    print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $facturestatic->getNomUrl(1);
                    print '</td>';
                    print '<td width="16" align="right" class="nobordernopadding">';
                    $filename=dol_sanitizeFileName($objp->ref);
                    $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->ref);
                    $urlsource=$_SERVER['PHP_SELF'].'?facid='.$objp->id;
                    $formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','',1,'',1);
                    print '</td>';
                    print '</tr></table>';
                    print "</td>\n";
                    //Estado V
                    print '<td>&nbsp;</td>';
                    // Date
                    print '<td align="center" nowrap>';
                    print dol_print_date($db->jdate($objp->df),'day');
                    print '</td>';
                    //Customer
                    print '<td>';
                    $thirdparty=new Societe($db);
                    $thirdparty->id=$objp->socid;
                    $thirdparty->nom=$objp->nom;
                    print $thirdparty->getNomUrl(1,'customer');
                    print '</td>';
                    print '<td align="right">'.price($objp->total).'</td>';

                    print '<td align="right">'.price($objp->total_ttc).'</td>';

                    print '<td align="right">'.price($paiement).'</td>';
                    // Affiche statut de la facture
                    print '<td align="right" nowrap="nowrap">';
                    print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
                    print "</td>";
                    print '<td>';
                    if ($objp->user>0)
                    {
                        $userstatic=new User($db);
                        $userstatic->fetch($objp->user);
                        print $userstatic->getNomUrl(1);
                    }
                    print '</td>';
                    print "</tr>\n";
                }
                $total+=$objtotal;
                $total_ttc+=$objttc;
                $totalrecu+=$objcustpay;
                $i++;
            }

                // Print total
                print '<tr class="liste_total">';
                print '<td>&nbsp;</td>'; //Part of checkbox addition
                print '<td class="liste_total" colspan="3" align="left">'.$langs->trans('Total').'</td>';
                print '<td class="liste_total" align="center">&nbsp;</td>';
                //print '<td class="liste_total" align="center">&nbsp;</td>';
                print '<td class="liste_total" align="right">'.price($total).'</td>';
                print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
                print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
                print '<td class="liste_total" align="right" id="totalResToPay">'.price($total_ttc-$totalrecu).'</td>';
                print '<td class="liste_total" align="center">&nbsp;</td>';
                print '<td class="liste_total" align="center">&nbsp;</td>';
                print "<td>&nbsp;</td>";
                print '</tr>';
        }
        // ---- Script for dynamic discount ---------------
        print '
	    <script>
	        $("#allFacTicSelected").change(function() {
                var checkboxes = $(this).closest(\'form\').find(\':checkbox\');
                checkboxes.prop(\'checked\', $(this).is(\':checked\'));
                if($(this).is(\':checked\')){
                    let restopay = document.getElementById("totalResToPay").innerText;
                    document.getElementById("total_amount").innerText = restopay;
                }
                else{
                    let restopay = "0.00";
                    document.getElementById("total_amount").innerText = restopay;
                }
            });
	        $(\'input[type="checkbox"]\').click(function(){
	            let checked = false;
                if($(this).prop("checked") == true){
                    checked = true;
                }
                let id = $(this).prop("value");
                let data = {
                    "id": id
                };
                let url = "'.DOL_MAIN_URL_ROOT.'/custom/pos/backend/amount_calculator.php";
                let total_amount = document.getElementById("total_amount").innerText;
                $.ajax({
                    type: "POST",
                    url: url,
                    data: data,
                    dataType: "json",
                    success: function (data){
                        let amount = parseFloat(data.amount).toFixed(2);
                        if(!isNaN(amount)){
                            let total = 0.0
                            if (checked) {
                                total = parseFloat(total_amount) + parseFloat(amount);
                            }
                            else {
                                total = parseFloat(total_amount) - parseFloat(amount);
                            }
                            total = total.toFixed(2);
                            document.getElementById("total_amount").innerText = "";
                            document.getElementById("total_amount").innerText = total;
                        }
                    },
                    error: function (err){
                        console.log(err);
                    }
                });
            });
	        function applyDiscount(){
	            let total_amount = parseFloat(document.getElementById("total_amount").innerText).toFixed(2);
	            let discountApplied = parseFloat(document.getElementById("discount_input").value).toFixed(2);
	            let amountDiscounted = total_amount * discountApplied / 100;
	            let total_discount = total_amount - amountDiscounted;
	            document.getElementById("total_discount").innerText = total_discount.toFixed(2);
                document.getElementById("discount_applied").value = discountApplied;
	        };
        </script>
	';
        // ---------------
        print "</table>\n";
        //---------------- Total a facturar
        print '<div style="font-size:16px;">
                <strong style="color: darkblue;">Monto a pagar: $ <span id="total_amount">0.00</span></strong><br>
                <strong style="">Descuento: % <input type="number" value="0" id="discount_input" name="discount_input" onkeydown="if (event.keyCode == 13) { return false;}"><button onclick="applyDiscount()" type="button" class="button valignmiddle">Aplicar</button></strong><br>
                <strong style="color: green;">Total con descuento: $ <span id="total_discount">0.00</span></strong>
        </div>';

        // Submit mass paiement
        //print '<input type="hidden" name="massinvoice" id="massinvoice" value="true">';
        //print '<input type="submit" class="button valignmiddle" value="Facturar selección">';
        print "</form>\n";
        $db->free($resql);

        print '<div class="tabsAction">';
        if ($closeid)
        {
            $url = '../frontend/tpl/closecash.tpl.php?id='.$closeid;
            print '<a class="butAction" href='.$url.' target="_blank">'.$langs->trans('PrintCopy').'</a>';

            print '<a class="butAction" href="'.dol_buildpath('/pos/backend/liste.php',1).'?closeid='.$closeid.'&viewstatut=2&action=mail">'.$langs->trans('MailCopy').'</a>';

        }
        print '</div>';

        if( GETPOST('action','string') == 'mail')
        {
            include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
            $formmail = new FormMail($db);

            $action='send';
            $modelmail='body';

            print '<br>';

            print_titre($langs->trans($titre));

            $formmail->fromtype = 'user';
            $formmail->fromid   = $user->id;
            $formmail->fromname = $conf->global->MAIN_INFO_SOCIETE_NOM;
            $formmail->frommail = $conf->global->MAIN_INFO_SOCIETE_MAIL;
            $formmail->withfrom=0;
            $formmail->withto=empty($_POST["sendto"])?1:GETPOST('sendto');
            $formmail->withtocc=0;
            $formmail->withtoccsocid=0;
            $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
            $formmail->withtocccsocid=0;
            $formmail->withtopic=$conf->global->MAIN_INFO_SOCIETE_NOM.': '.$langs->trans("CopyOfCloseCash").' '.$closeid;
            $formmail->withfile=0;
            $formmail->withbody= POS::FillMailCloseCashBody($closeid);
            $formmail->withdeliveryreceipt=0;
            $formmail->withcancel=1;

            $formmail->param['action']=$action;
            $formmail->param['models']=$modelmail;
            $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$id;
            $formmail->show_form();

            print '<br>';
        }
    }
    else
    {
        dol_print_error($db);
    }
}
echo 
'<div id="openModal" class="modalDialog">
    <div>
		<div id="modal-content-detail"></div>
    </div>
</div>';
?>
<script>
function setModalContent(id,wid='1',uid='0')
{
	var u = '<?php echo DOL_URL_ROOT; ?>/product/custom/by_warehouse_det_locked.php?id='+id+'&wid='+wid;
	$('#modal-content-detail').html('');
	$.get( u, function( data ) {
		$('#modal-content-detail').html(data);
	});	
}
</script>
<?php
llxFooter();

$db->close();
?>
