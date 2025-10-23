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
 *	\file       htdocs/pos/backend/liste.php
 *	\ingroup    ticket
 *	\brief      Page to list tickets
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php");
dol_include_once('/pos/class/pos.class.php');
require_once(DOL_DOCUMENT_ROOT ."/projet/class/project.class.php");

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
$action=GETPOST('action','string');
$massinvoice = GETPOST('massinvoice', 'alpha');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = explode(',',GETPOST("sortorder",'alpha'))[0];
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$month    =GETPOST('month','int');
$year     =GETPOST('year','int');

$limit = $conf->liste_limit;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.date_ticket';

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
$now=dol_now();

// -----------
//    Begin multiple tickets to single facture
//--------------
$invoicedtickets = array();
if($massinvoice){
    require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
    foreach($_REQUEST as $item=>$value){
        $substring = substr($item, 0, 6);
        if($substring == 'ticket'){
            array_push($invoicedtickets, $value);
        }
    }
    $facture = New Facture($db);
    $tempTicket = New Ticket($db);
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

}
//- ---------

if (!$user->rights->pos->backend)
{
	//print '<a href="'.dol_buildpath('/pos/frontend/index.php',1).'"><img src='.dol_buildpath('/pos/frontend/img/bgback.png',1).' WIDTH="50%" HEIGHT="50%" ></a>';
}	
else {
	if ($page == -1) $page = 0 ;
	
	$sql = 'SELECT ';
	$sql.= ' f.rowid as ticketid, f.type, f.ticketnumber, f.total_ht, f.total_ttc,';
	$sql.= ' f.date_creation as df, f.fk_user_close,';
	$sql.= ' f.paye as paye, f.fk_statut, f.customer_pay, f.difpayment, f.note_public, ';
	$sql.= ' s.nom, s.rowid as socid,';
	$sql.= ' u.firstname, u.lastname,';
	$sql.= ' pj.title AS project_name,pj.rowid AS project_id,';
    $sql.= ' (SELECT COUNT(lptd.rowid) FROM '.MAIN_DB_PREFIX.'pos_ticketdet as lptd LEFT JOIN '.MAIN_DB_PREFIX.'product as prod on lptd.fk_product=prod.rowid WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=4 AND lptd.qty<=prod.stock) as total_of_surtir,';
	$sql.= ' t.name, f.fk_cash';
	$sql.= ' FROM ('.MAIN_DB_PREFIX.'societe as s ';
	$sql.= ', '.MAIN_DB_PREFIX.'pos_ticket as f ';
	$sql.= ', '.MAIN_DB_PREFIX.'pos_cash as t ';
	$sql.= ', '.MAIN_DB_PREFIX.'user as u )';
	$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'projet as pj ';
	$sql.= " ON f.fk_projet = pj.rowid ";
	$sql.= ' WHERE f.fk_soc = s.rowid';
	$sql.= " AND f.entity = ".$conf->entity;
	$sql.= " AND f.fk_cash = t.rowid";
	
	//$sql.= " AND f.fk_user_close = u.rowid";
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
	if ($_GET['search_project'])
	{
		 $sql.= ' AND pj.ref LIKE \'%'.$db->escape(trim($_GET['search_project'])).'%\'';
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
		$sql.= ' AND ( s.nom LIKE \'%'.$db->escape(trim($_GET['search_societe'])).'%\' ';
		$sql.= ' OR f.note_public LIKE \'%'.$db->escape(trim($_GET['search_societe'])).'%\' )';
	}
	if ($_GET['search_montant_ht'])
	{
		$sql.= ' AND f.total_ht = \''.$db->escape(trim($_GET['search_montant_ht'])).'\'';
	}
	if ($_GET['search_montant_ttc'])
	{
		$sql.= ' AND f.total_ttc = \''.$db->escape(trim($_GET['search_montant_ttc'])).'\'';
	}
	if ($month > 0)
	{
		if ($year > 0)
			$sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
		else
		$sql.= " AND date_format(f.date_ticket, '%m') = '".$month."'";
	}
	else if ($year > 0)
	{
		$sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
	}
	if ($_POST['sf_ref'])
	{
		$sql.= ' AND f.ticketnumber LIKE \'%'.$db->escape(trim($_POST['sf_ref'])) . '%\'';
		}
	if ($sall)
	{
		$sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.ticketnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
	}
	if (! $sall)
	{
		$sql.= ' GROUP BY f.rowid, f.ticketnumber, f.total_ht, f.total_ttc,';
		$sql.= ' f.date_ticket,';
		$sql.= ' f.paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid';
	}
	$sql.= ' ORDER BY ';
	$listfield=explode(',',$sortfield);
	foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
	$sql.= ' f.rowid DESC ';
	$sql.= $db->plimit($limit+1,$offset);
	        //print $sql;
	
	$resql = $db->query($sql);
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
		
		$txtListe = $langs->trans('TicketsCustomers');
		
		if ($viewstatut <> '')
		{
			$txtListe = $txtListe." - ".$ticketstatic->LibStatut($viewstatut);
		}
		
		if ($viewtype <> '')
		{
			$txtListe = $txtListe." - ".$langs->trans("StatusTicketReturned");
		}
	
		print_barre_liste($txtListe.' '.($socid?' '.$soc->nom:''),$page,'liste.php',$param,$sortfield,$sortorder,'',$num);
	
		$i = 0;
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
        print '<th class="liste_titre">&nbsp;</th>';
		print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.ticketnumber','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.date_ticket','',$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Cash'),$_SERVER['PHP_SELF'],'t.name','',$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('User'),$_SERVER['PHP_SELF'],'u.lastname','',$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Customer'),$_SERVER['PHP_SELF'],'s.nom,f.note_public','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Project'),$_SERVER['PHP_SELF'],'pj.ref','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total_ht','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('AmountDiff'),$_SERVER['PHP_SELF'],'f.difpayment','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
	    print '<th class="liste_titre">&nbsp;</th>';
	    //print '<th class="liste_titre">&nbsp;</th>';
		print '</tr>';
	
		// Lignes des champs de filtre
	
		print '<tr class="liste_titre">';
		print '<td>&nbsp;</td>'; //Part of checkbox addition
        print '<td class="liste_titre" align="left">';
        /*$ch=$_GET['surtir']?"checked":'';
        print '<input type="checkbox" class="ls_tpv_line_checkbox" name="surtir" '.$ch.'> ';*/
        //print '</td>';
        //print '<td class="liste_titre" align="left">';
        print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
        print '</td>';
        print '<td class="liste_titre" colspan="1" align="center">';
		//print '<td class="liste_titre" align="left">';
		//print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
		//print '<td class="liste_titre" colspan="1" align="center">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		//$syear = $year;
	    //if ($syear == '') $syear = date("Y");
		$html->select_year($syear?$syear:-1,'year',1, 20, 5);
		print '</td>';
		
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_cash" value="'.$_GET['search_cash'].'">';
		print '</td>';
		
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_user" value="'.$_GET['search_user'].'">';
		print '</td>';

		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_societe" value="'.$_GET['search_societe'].'">';
		print	 '</td>'
				.'<td>'
				.'<input class="flat" type="text" name="search_project" value="'.$_GET['search_project'].'">'
				.'</td>'
				.'<td class="liste_titre" align="right">'
				;
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET['search_montant_ttc'].'">';
		print '</td>';
		print '<td class="liste_titre" align="right">';
		print '&nbsp;';
		print '</td>';
		print '<td class="liste_titre" align="right">';
		print '&nbsp;';
		print '</td>';
		print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '<td class="liste_titre" align="left">&nbsp;</td>';
		print "</td></tr>\n";
	
		if ($num > 0)
		{
			$var=True;
			$total=0;
			$totalrecu=0;

			$rc_t_st = 0;
			$rc_t_tt = 0;
			$rc_t_ab = 0;
			$rc_t_df = 0;
	
			while ($i < min($num,$limit))
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;

                $ticketstatic->id=$objp->ticketid;
                $ticketstatic->ref=$objp->ticketnumber;
                $paiement = $ticketstatic->getSommePaiement();
                $ticketstatic->fetch($objp->ticketid);
				print '<tr '.$bc[$var].'>';

				// Addition of checkbox
                if($ticketstatic->statut == '1'){

                    print '<td>
                    <input 
                    type="checkbox" 
                    id="ticket_'.$objp->ticketid.'" 
                    name="ticket_'.$objp->ticketid.'" 
                    value="'.$objp->ticketid.'" 
                    '.($ticketstatic->fk_facture? 'disabled checked':'').'
                    ></td>';
                }
                else{
                    print '<td>&nbsp;</td>';
                }


				print '<td nowrap="nowrap">';
	
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';

				print '<td class="nobordernopadding" nowrap="nowrap">';
                if($objp->total_of_surtir > 0) {
                    print img_picto('Ya hay existencia, listo para surtir', 'star.png', 'style="color:#fc0;width:10px;height:auto;"' , 0, 0, 0);
                }
				//print $ticketstatic->ref;
				print $ticketstatic->getNomUrl(1);
				print '</td>';
	
				print '</tr></table>';
	
				print "</td>\n";
	
				// Date
				print '<td align="center" nowrap>';
				print dol_print_date($db->jdate($objp->df),'day');
				print '</td>';
	
				print '<td>';
				$cash=new Cash($db);
				$cash->fetch($objp->fk_cash);
				print $cash->getNomUrl(1);
				print '</td>';
				print '<td>';
				if ($objp->fk_user_close>0)
				{
					$userstatic=new User($db);
		        	$userstatic->fetch($objp->fk_user_close); 
		       	 	print $userstatic->getNomUrl(1);
				}
				print '</td>';
	            
				print '<td>';
				if(!$user->rights->societe->client->voir) 
				{
					print $objp->nom;
				}
				else
				{
					$thirdparty=new Societe($db);
					$thirdparty->id=$objp->socid;
					$thirdparty->nom=$objp->nom;
					print $thirdparty->getNomUrl(1,'customer');
					if(strlen($objp->note_public))
					{
						echo ' ('.$objp->note_public.')';
					}
				}
				print '</td>';

				echo '<td>';
				if ($objp->project_id > 0)
				{
					$static_project = new Project($db);
					$static_project->fetch($objp->project_id);
					echo $static_project->getNomUrl(1);
				}
				else
				{
					echo '&nbsp';
				}
				echo '</td>';
				
				if($objp->type==0)
				{
					$objtotal=$objp->total_ht;
					$objttc=$objp->total_ttc;
					$objcustpay=$objp->total_ttc>$objp->customer_pay?$objp->customer_pay:$objp->total_ttc;
					$objdifpay=$objp->total_ttc-$objcustpay;				
				}
				else
				{
					$objtotal=$objp->total_ht *-1;
					$objttc=$objp->total_ttc *-1;
					$objcustpay=$objp->total_ttc *-1;
					$objdifpay=$objp->difpayment  *-1;
				}
				$rc_t_st += $objtotal;
				$rc_t_tt += $objttc;
				$rc_t_ab += $objcustpay;
				$rc_t_df += $objdifpay;				
				print '<td align="right">'.price($objtotal).'</td>';
				print '<td align="right">'.price($objttc).'</td>';
				print '<td align="right">'.price($objcustpay).'</td>';
				print '<td align="right">'.price($objdifpay).'</td>';
				
				// Affiche statut de la ticket
				print '<td align="left" nowrap="nowrap">';
				print $ticketstatic->LibStatut($objp->fk_statut,1);
				print "</td>";
				print "<td>&nbsp;</td>";
				print "</tr>\n";
				$total+=$objtotal;
				$total_ttc+=$objttc;
				$totalrecu+=$objcustpay;
				$totaldif+=$objdifpay;
				$i++;
			}
	
			if (true || ($offset + $num) <= $limit)
			{
				// Print total
				print '<tr class="liste_total">';
				print '<td>&nbsp;</td>'; //Part of checkbox addition
				print '<td class="liste_total" colspan="3" align="left">'.$langs->trans('Total').'</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print '<td class="liste_total" align="right">'.price($total).'</td>';
				print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
				print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
				print '<td class="liste_total" align="right">'.price($total_ttc-$totalrecu).'</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print "<td>&nbsp;</td>";
				print '</tr>';
			}
		}

		// ---------------
		print "</table>\n";
        // Submit mass paiement
        print '<input type="hidden" name="massinvoice" id="massinvoice" value="true">';
        print '<input type="submit" class="button valignmiddle" value="Facturar selección">';
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
llxFooter();

$db->close();
?>