<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Teddy Andreotti         <125155@supinfo.com>
 * Copyright (C) 2015       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/paiement.php
 *	\ingroup    facture
 *	\brief      Payment page for customers invoices
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/payment.class.php');

require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");

require_once(DOL_DOCUMENT_ROOT . '/cfdimx/conf.php');
require_once(DOL_DOCUMENT_ROOT . '/cfdimx/lib/functionsGeneraCFDI.php');
// include(DOL_DOCUMENT_ROOT . '/cfdimx/lib/nusoap/lib/nusoap.php');
// include(DOL_DOCUMENT_ROOT . '/cfdimx/lib/phpqrcode/qrlib.php');
// require(DOL_DOCUMENT_ROOT . '/cfdimx/lib/numero_a_letra.php');

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'banks', 'multicurrency'));

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');

$facid = GETPOST('facid', 'int');
$accountid = GETPOST('accountid', 'int');
$paymentnum	= GETPOST('num_paiement', 'alpha');
$socid      = GETPOST('socid', 'int');

$sortfield	= GETPOST('sortfield', 'alpha');
$sortorder	= GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
$massive_payment = GETPOST('masspaiement', 'alpha');

$amounts = array();
$amountsresttopay = array();
$addwarning = 0;

$multicurrency_amounts = array();
$multicurrency_amountsresttopay = array();

// Security check
if ($user->socid > 0)
{
    $socid = $user->socid;
}



// Load object
if ($facid > 0)
{
    $object = new Facture($db);
	$ret = $object->fetch($facid);
}

// Initialize technical object to manage hooks of paiements. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paiementcard', 'globalcard'));


/*
 * Actions
 */
$reshook = 0;
if ($facid)
{$parameters = array('socid'=>$socid);
//$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
}

//if ( $reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'add_paiement' || ($action == 'confirm_paiement' && isset($confirm)))
	{
	    $error = 0;

	    $datepaye = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
	    $paiement_id = 0;
	    $totalpayment = 0;
		$multicurrency_totalpayment = 0;
	    $atleastonepaymentnotnull = 0;
		$formquestion = array();
		$i = 0;

	    // Generate payment array and check if there is payment higher than invoice and payment date before invoice date
	    $tmpinvoice = new Facture($db);
        $tmpticket  = new Ticket($db);
	    foreach ($_POST as $key => $value)
	    {
			if (substr($key, 0, 7) == 'amount_' && GETPOST($key) != '')
	        {
	            $cursorfacid = substr($key, 7);
	            $amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $totalpayment = $totalpayment + $amounts[$cursorfacid];
				if($totalpayment < 0.01 && $totalpayment > -0.01)
					$totalpayment = 0;
	            if (!empty($amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result = $tmpinvoice->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $amountsresttopay[$cursorfacid] = price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
	            if ($amounts[$cursorfacid])
	            {
		            // Check amount
		            /*if ($amounts[$cursorfacid] && (abs($amounts[$cursorfacid]) > abs($amountsresttopay[$cursorfacid])))
		            {
		                $addwarning = 1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
		            }*/
		            // Check date
		            if ($datepaye && ($datepaye < $tmpinvoice->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => $_POST[$key]);
	        }
            elseif (substr($key, 0, 9) == 't_amount_' && GETPOST($key) != '')
	        {
	            $cursorfacid = substr($key, 9);
	            $t_amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $totalpayment = $totalpayment + $t_amounts[$cursorfacid];
	            if (!empty($t_amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result = $tmpticket->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $t_amountsresttopay[$cursorfacid] = price2num($tmpticket->total_ttc - $tmpticket->getSommePaiement());
	            if ($t_amounts[$cursorfacid])
	            {
		            // Check amount
		            if ($t_amounts[$cursorfacid] && (abs($t_amounts[$cursorfacid]) > abs($t_amountsresttopay[$cursorfacid])))
		            {
		                $addwarning = 1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
		            }
		            // Check date
		            if ($datepaye && ($datepaye < $tmpticket->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => $_POST[$key]);
	        }
			elseif (substr($key, 0, 21) == 'multicurrency_amount_')
			{
				$cursorfacid = substr($key, 21);
	            $multicurrency_amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
	            $multicurrency_totalpayment += $multicurrency_amounts[$cursorfacid];
	            if (!empty($multicurrency_amounts[$cursorfacid])) $atleastonepaymentnotnull++;
	            $result = $tmpinvoice->fetch($cursorfacid);
	            if ($result <= 0) dol_print_error($db);
	            $multicurrency_amountsresttopay[$cursorfacid] = price2num($tmpinvoice->multicurrency_total_ttc - $tmpinvoice->getSommePaiement(1));
	            if ($multicurrency_amounts[$cursorfacid])
	            {
		            // Check amount
		            if ($multicurrency_amounts[$cursorfacid] && (abs($multicurrency_amounts[$cursorfacid]) > abs($multicurrency_amountsresttopay[$cursorfacid])))
		            {
		                $addwarning = 1;
		                $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
		            }
		            // Check date
		            if ($datepaye && ($datepaye < $tmpinvoice->date))
		            {
		            	$langs->load("errors");
		                //$error++;
		                setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
		            }
	            }

	            $formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => GETPOST($key, 'int'));
			}
	    }
        
        

	    // Check parameters
	    if (!GETPOST('paiementcode'))
	    {
	        setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), null, 'errors');
	        $error++;
	    }

	    if (!empty($conf->banque->enabled))
	    {
	        // If bank module is on, account is required to enter a payment
	        if (GETPOST('accountid') <= 0)
	        {
	            setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
	            $error++;
	        }
	    }

	    if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull))
	    {
	        setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->trans('PaymentAmount')), null, 'errors');
	        $error++;
	    }

	    if (empty($datepaye))
	    {
	        setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('Date')), null, 'errors');
	        $error++;
	    }

		// Check if payments in both currency
		if ($totalpayment > 0 && $multicurrency_totalpayment > 0)
		{
			setEventMessages($langs->transnoentities('ErrorPaymentInBothCurrency'), null, 'errors');
	        $error++;
		}
	}

	/*
	 * Action add_paiement
	 */
	 
	if ($action == 'add_paiement')
	{
	    if ($error)
	    {
	        $action = 'create';
	    }
	    // Le reste propre a cette action s'affiche en bas de page.
	}
    if($massive_payment == 'true'){
        $massive_ids = array();
        foreach($_REQUEST as $clave=>$valor){
            $substring = substr($clave,0,5);
            if( $substring == 'facid'){
                array_push($massive_ids, $valor);
            }
        }
        $t_massive_ids = array();
        foreach($_REQUEST as $clave=>$valor){
            $substring = substr($clave,0,5);
            if( $substring == 'tktid' && !in_array($valor,$t_massive_ids)){
                array_push($t_massive_ids, $valor);
            }
        }
    }
	/*
	 * Action confirm_paiement
	 */
	if ($action == 'confirm_paiement' && isset($confirm))
	{
	    $error = 0;

	    $datepaye = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

	    $db->begin();

	    $thirdparty = new Societe($db);
	    if ($socid > 0) $thirdparty->fetch($socid);

	    // Clean parameters amount if payment is for a credit note
		
		$temporal_facid = $facid; // Saving for later, required for payment generation
	    foreach ($amounts as $key => $value)	// How payment is dispatched
	    {
	        $tmpinvoice = new Facture($db);
	        $tmpinvoice->fetch($key);
            $tmpsociete = new Societe($db);
            $tmpsociete->fetch($tmpinvoice->socid);
            if ($tmpsociete->earlypayment_discount && $massive_payment && $tmpinvoice->type != 2) {
                $tmpinvoice->total_ttc_before_discount = $tmpinvoice->total_ttc;
                //$tmpinvoice->discount_applied = $tmpsociete->earlypayment_discount;
                //$tmpinvoice->total_ttc = $tmpinvoice->total_ttc * (100 - $tmpsociete->earlypayment_discount) / 100;
				//$tmpinvoice->update($user);
				
				$crerid_note_id = $tmpinvoice->createDiscountCreditNote($user,1,$tmpsociete->earlypayment_discount);
				
				if($crerid_note_id > 0 && $confirm == 'yes'){
					$facid = $crerid_note_id;
					include(DOL_DOCUMENT_ROOT . '/cfdimx/generaCFDIAutomatico.php');
					if($success)
						setEventMessages($msg_cfdi_final, array());
					else
						setEventMessages($msg_cfdi_final, array(), 'errors');
				}

		        //$amounts[$crerid_note_id] = -1 * $tmpinvoice->total_ttc_before_discount * ($tmpsociete->earlypayment_discount / 100);
				//$value = abs($tmpinvoice->total_ttc_before_discount);
				//$amounts[$key] = $value ;
            }
	        if ($tmpinvoice->type == Facture::TYPE_CREDIT_NOTE)
	        {
	            $newvalue = price2num($value, 'MT');
	            $amounts[$key] = - abs($newvalue);
	        }
		}
		$facid = $temporal_facid;

        $total_credit_notes = 0;
        $total_factures = 0;
	    foreach ($amounts as $key => $value)	// How payment is dispatched
	    {
	       if ($value >= 0)
           {
                $total_factures += $value;
           }
           else
           {
                $total_credit_notes += $value;
           }
        }
		$resta = $total_factures - abs($total_credit_notes);
		$entrar = $resta < 0.01 && $resta > -0.01;
        if ($total_factures < abs($total_credit_notes) && !$entrar)
        {
	    	setEventMessages('El total de las notas de crédito ($'.price($total_credit_notes).') excede el total de las facturas ('.price($total_factures).')', null, 'errors');
	    	$error++;
        }
        elseif($total_factures > abs($total_credit_notes) && abs($total_credit_notes) > 0)
        {
            arsort($amounts);
            $new_amounts = array();
            $new_total_credit_notes= abs($total_credit_notes);
            foreach ($amounts as $tkey =>$tam)
            {
                if ($amounts[$tkey] > 0)
                {
                    if ($amounts[$tkey] <= $new_total_credit_notes)
                    {
                        $new_amounts[$tkey] = $amounts[$tkey];
                        $new_total_credit_notes -= $amounts[$tkey];
                        unset($amounts[$tkey]); 
                        
                    }
                    else
                    {
                        $new_amounts[$tkey] = $amounts[$tkey] - ($amounts[$tkey] - $new_total_credit_notes);
                        $amounts[$tkey] = $amounts[$tkey] - $new_amounts[$tkey];
                        $new_total_credit_notes = 0; 
                    }
                }
                else
                {
                    $new_amounts[$tkey] = $amounts[$tkey];
                    unset($amounts[$tkey]);
                }
            }
            
            if (count($new_amounts) && count($amounts))
            {
 	          // Creation of payment line
	           $paiement = new Paiement($db);
	           $paiement->datepaye     = $datepaye;
	           $paiement->amounts      = $new_amounts; // Array with all payments dispatching with invoice id
	           $paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
	           $paiement->paiementid   = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement', 'code', 'id', 1);
	           $paiement->num_payment  = GETPOST('num_paiement', 'alpha');
	           $paiement->note_private = GETPOST('comment', 'alpha');
	           $paiement->num_paiement = $paiement->num_payment;		// For bacward compatibility
	           $paiement->note         = $paiement->note_private;		// For bacward compatibility

	           if (!$error)
	           {
	               // Create payment and update this->multicurrency_amounts if this->amounts filled or
	               // this->amounts if this->multicurrency_amounts filled.
	               //$paiement_id = $paiement->create($user, (GETPOST('closepaidinvoices') == 'on' ? 1 : 0), $thirdparty); // This include closing invoices and regenerating documents
	               $paiement_id = $paiement->create($user, 1, $thirdparty); // This include closing invoices and regenerating documents
	    	      if ($paiement_id < 0)
	              {
	                   setEventMessages($paiement->error, $paiement->errors, 'errors');
	                   $error++;
	              }
	           }

	           if (!$error)
	           {
	    	      $label = '(CustomerInvoicePayment)';
	    	      if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) $label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
	               $result = $paiement->addPaymentToBank($user, 'payment', $label, GETPOST('accountid'), GETPOST('chqemetteur'), GETPOST('chqbank'));
	              if ($result < 0)
	              {
	                   setEventMessages($paiement->error, $paiement->errors, 'errors');
	                   $error++;
	              }
	           }
               
            }
            elseif (count($new_amounts) && !count($amounts))
            {
                $amounts == $new_amounts;
            }
        }

	    foreach ($multicurrency_amounts as $key => $value)	// How payment is dispatched
	    {
	        $tmpinvoice = new Facture($db);
	        $tmpinvoice->fetch($key);
            $tmpsociete = new Societe($db);
            $tmpsociete->fetch($tmpinvoice->socid);
            if ($tmpsociete->earlypayment_discount and $massive_payment) {
                $tmpinvoice->total_ttc_before_discount = $tmpinvoice->total_ttc;
                $tmpinvoice->discount_applied = $tmpsociete->earlypayment_discount;
                $tmpinvoice->total_ttc = $tmpinvoice->total_ttc * (100 - $tmpsociete->earlypayment_discount) / 100;
                $tmpinvoice->update($user);
            }
	        if ($tmpinvoice->type == Facture::TYPE_CREDIT_NOTE)
	        {
	            $newvalue = price2num($value, 'MT');
	            $multicurrency_amounts[$key] = - abs($newvalue);
	        }
	    }

	    if (!empty($conf->banque->enabled))
	    {
	    	// Si module bank actif, un compte est obligatoire lors de la saisie d'un paiement
	    	if (GETPOST('accountid', 'int') <= 0)
	    	{
	    		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
	    		$error++;
	    	}
	    }

	    // Creation of payment line
	    $paiement = new Paiement($db);
	    $paiement->datepaye     = $datepaye;
	    $paiement->amounts      = $amounts; // Array with all payments dispatching with invoice id
	    $paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
	    $paiement->paiementid   = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement', 'code', 'id', 1);
	    $paiement->num_payment  = GETPOST('num_paiement', 'alpha');
	    $paiement->note_private = GETPOST('comment', 'alpha');
	    $paiement->num_paiement = $paiement->num_payment;		// For bacward compatibility
	    $paiement->note         = $paiement->note_private;		// For bacward compatibility

	    if (!$error && count($paiement->amounts))
	    {
	        // Create payment and update this->multicurrency_amounts if this->amounts filled or
	        // this->amounts if this->multicurrency_amounts filled.
	        //$paiement_id = $paiement->create($user, (GETPOST('closepaidinvoices') == 'on' ? 1 : 0), $thirdparty); // This include closing invoices and regenerating documents
	        $paiement_id = $paiement->create($user, 1, $thirdparty); // This include closing invoices and regenerating documents
	    	if ($paiement_id < 0)
	        {
	            setEventMessages($paiement->error, $paiement->errors, 'errors');
	            $error++;
	        }
	    }

	    if (!$error && count($paiement->amounts))
	    {
	    	$label = '(CustomerInvoicePayment)';
	    	if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) $label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
	        $result = $paiement->addPaymentToBank($user, 'payment', $label, GETPOST('accountid'), GETPOST('chqemetteur'), GETPOST('chqbank'));
	        if ($result < 0)
	        {
	            setEventMessages($paiement->error, $paiement->errors, 'errors');
	            $error++;
	        }
	    }

	    if (!$error)
	    {
			$db->commit();
			
			/** Checado de facturas para timbrado automático de Pago */
			$sql = "SELECT fk_facture FROM ".MAIN_DB_PREFIX."paiement_facture WHERE fk_paiement = $paiement_id";
			$resql = $db->query($sql);
			if($resql){
				$timbrable = false;
				while($row = $db->fetch_object($resql)){
					$check = $db->query("SELECT count(*) as num FROM ".MAIN_DB_PREFIX."cfdimx where fk_facture = $row->fk_facture");
					if($db->fetch_object($check)->num > 0){
						$check = $db->query("SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields where fk_object = $row->fk_facture");
						if($db->fetch_object($check)->formpagcfdi == 'PPD'){
							// $timbrable = true;
						}
					}
				}
				if($timbrable){
					include_once(DOL_DOCUMENT_ROOT."/cfdimx/pagos/generaCFDIAutomatico.php");
				}
			}
			

	        // If payment dispatching on more than one invoice, we stay on summary page, otherwise jump on invoice card
            // We also dispatch all tickets for every invoice paid

            $ticketstatic = new Ticket($db);
            $total_paid = $paiement->amount;
            $invoiceid = 0;
            $still_founds = true;
            $error_ticket = 0;
	        foreach ($paiement->amounts as $key => $amount)
	        {
	            $facid = $key;
	            if (is_numeric($amount) && $amount <> 0)
	            {
                    if($still_founds){
                        $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'pos_ticket ';
                        $sql .= 'WHERE fk_facture = ' . $facid .' AND paye != 1';
                        $resql = $db->query($sql);
                        $iterator = 0;
                        if ($resql) {
                            $numrows = $db->num_rows($resql);
                            while ($iterator < $numrows and $still_founds) {
                                $id_ticket = $db->fetch_object($resql)->rowid;
                                $ticketstatic->fetch($id_ticket);

                                $remain_paid = $total_paid - $ticketstatic->diff_payment;

                                if ($remain_paid > 0) {
                                    $total_paid = $remain_paid;
                                    $to_pay = $ticketstatic->diff_payment;
                                } else {
                                    $to_pay = $total_paid;
                                    $still_founds = false;
                                }

                                $sql2 = 'INSERT INTO ' . MAIN_DB_PREFIX . 'pos_paiement_ticket(fk_paiement, fk_ticket, amount) ';
                                $sql2 .= 'VALUES (' . $paiement->id . ',' . $id_ticket . ',' . $to_pay . ')';
                                $resql2 = $db->query($sql2);
                                if ($resql2) {
                                    $ticketstatic->diff_payment -= $to_pay;
                                    $ticketstatic->customer_pay += $to_pay;
                                    $ticketstatic->update($user);
                                    if($still_founds) $ticketstatic->set_paid($user);
                                }


                                $iterator++;
                            }
                        }
                    }

	                if ($invoiceid != 0) $invoiceid = -1; // There is more than one invoice payed by this payment
	                else $invoiceid = $facid;
	            }
	        }
            
            
	        //if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/compta/facture/card.php?facid='.$invoiceid;
	        //else 
	    }
	    else
	    {
	        $db->rollback();
	    }
        $ticket_refs = array();
        foreach($t_amounts as $t_k => $t_v)
        {
            $ticketstatic = new Ticket($db);
            $ticketstatic->fetch($t_k);
            $ticket_refs[] = $ticketstatic->ref;
            $tmpsociete = new Societe($db);
            $tmpsociete->fetch($ticketstatic->socid);
            if ($tmpsociete->earlypayment_discount > 0 && $ticketstatic->type == 0)
            {
            	$alreadyDiscounted = false;
            	foreach ($ticketstatic->lines as $static_line)
            	{
            		if ($static_line->desc == 'Bonificación por descuento CxC')
            		{
            			$alreadyDiscounted = true;
            			break;
            		}
            	}
            	
            	if (!$alreadyDiscounted)
            	{
	            	$linetotal = $ticketstatic->diff_payment * ($tmpsociete->earlypayment_discount) /100 /1.16 * -1;
    	            $ticketstatic->addline('Bonificación por descuento CxC',$linetotal,1,16,0,0,0,0,'',1,$linetotal*1.16,'TTC');
	                $tures = $ticketstatic->update($user);
	                if ($tures < 0)
	                {
	                    $error++;
	                }
            	}
               	$ticketstatic->fetch($t_k);
               	if (abs($ticketstatic->total_ttc - $t_amounts[$t_k])<0.109)
               	{
               		$t_amounts[$t_k] = $ticketstatic->total_ttc;
               	}
            	
            }
                
        }
        
        
        # inicio
        $total_credit_notes = 0;
        $total_factures = 0;
	    foreach ($t_amounts as $key => $value)	// How payment is dispatched
	    {
	       if ($value >= 0)
           {
                $total_factures += $value;
           }
           else
           {
                $total_credit_notes += $value;
           }
        }
        if ($total_factures < abs($total_credit_notes))
        {
	    	setEventMessages('El total de los tickets de regalo ($'.price($total_credit_notes).') excede el total de los tickets de venta ('.price($total_factures).')', null, 'errors');
	    	$error++;
        }
        elseif($total_factures > abs($total_credit_notes) && abs($total_credit_notes) > 0)
        {
            arsort($t_amounts);
            $new_amounts = array();
            $new_total_credit_notes= abs($total_credit_notes);
            foreach ($t_amounts as $tkey =>$tam)
            {
                if ($t_amounts[$tkey] > 0)
                {
                    if ($t_amounts[$tkey] <= $new_total_credit_notes)
                    {
                        $new_amounts[$tkey] = $t_amounts[$tkey];
                        $new_total_credit_notes -= $t_amounts[$tkey];
                        unset($t_amounts[$tkey]); 
                        
                    }
                    else
                    {
                        $new_amounts[$tkey] = $t_amounts[$tkey] - ($t_amounts[$tkey] - $new_total_credit_notes);
                        $t_amounts[$tkey] = $t_amounts[$tkey] - $new_amounts[$tkey];
                        $new_total_credit_notes = 0; 
                    }
                }
                else
                {
                    $new_amounts[$tkey] = $t_amounts[$tkey];
                    unset($t_amounts[$tkey]);
                }
            }
            
            if (count($new_amounts) && count($t_amounts))
            {
            	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/payment.class.php');
		    $thirdparty = new Societe($db);
		    if ($socid > 0) $thirdparty->fetch($socid);
 	          // Creation of payment line
	           $paiement = new Payment($db);
	           $paiement->datepaye     = $datepaye;
	           $paiement->amounts      = $new_amounts; // Array with all payments dispatching with invoice id
	           $paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
	           $paiement->paiementid   = dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement', 'code', 'id', 1);
	           $paiement->num_payment  = GETPOST('num_paiement', 'alpha');
	           $paiement->note_private = GETPOST('comment', 'alpha');
	           $paiement->num_paiement = $paiement->num_payment;		// For bacward compatibility
	           $paiement->note         = $paiement->note_private;		// For bacward compatibility

	           if (!$error)
	           {
	               // Create payment and update this->multicurrency_amounts if this->amounts filled or
	               // this->amounts if this->multicurrency_amounts filled.
	               $paiement_id = $paiement->create($user,  $thirdparty); // This include closing invoices and regenerating documents
	    	      if ($paiement_id < 0)
	              {
	                   setEventMessages($paiement->error, $paiement->errors, 'errors');
	                   
	                   $error++;
	              }
	           }
	    	      

	           if (!$error)
	           {
	    	      $label = '(CustomerInvoicePayment)';
	    	      if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) $label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
	               $result = $paiement->addPaymentToBank($user, 'payment', $label, GETPOST('accountid'),GETPOST('socid'), GETPOST('chqemetteur'), GETPOST('chqbank'));
	              if ($result < 0)
	              {
	                   setEventMessages($paiement->error, $paiement->errors, 'errors');
	                   $error++;
	              }
	           }
               
            }
            elseif (count($new_amounts) && !count($amounts))
            {
                $amounts == $new_amounts;
            }
        }
        
        
        # Fin
            if (!$error && count($t_amounts))
            {
                require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/payment.class.php');
	    		$paiement = new Payment($db);
	    		//die ('corriendo');
	    		
		    $thirdparty = new Societe($db);
		    if ($socid > 0) $thirdparty->fetch($socid);
				$paiement->datepaye=$datepaye;
				$paiement->bank_account=$accountid;
				$paiement->amounts = $t_amounts;
				$paiement->note=$langs->trans("Payment").' '.$langs->trans("Ticket").' '.implode(', ',$ticket_refs) ;
				$paiement->paiementid=dol_getIdFromCode($db, GETPOST('paiementcode'), 'c_paiement', 'code', 'id', 1);;
				$paiement->num_paiement='';
				
				foreach($t_amounts as $ttk=>$ttv)
				{
					$static_ticket = new Ticket($db);
					$tsocid = 0;
					if ($static_ticket->fetch($ttk))
					{
						if ($tsocid == 0)
						{
							$tsocid = $static_ticket->socid;
						}
						if ($tsocid != $static_ticket->socid)
						{
							$tsocid = 0;
							break;
						}
						
					}
					else
					{
						$tsocid = 0;
						break;
					}
					if (!$tsocid)
					{
						$error++;
					}
				}
	    		
	    		

			    if (!$error)
	    		{
			        $static_soc = new Societe($db);
			        $static_soc->fetch($tsocid);
			        // Create payment and update this->multicurrency_amounts if this->amounts filled or
			        // this->amounts if this->multicurrency_amounts filled.
			        $paiement_id = $paiement->create($user, $thirdparty); // This include closing invoices and regenerating documents
			    	if ($paiement_id < 0)
			        {
			            setEventMessages($paiement->error, $paiement->errors, 'errors');
			            $error++;
			        }
			    }
	           if (!$error)
	           {
	    	      $label = '(CustomerInvoicePayment)';
	    	      //if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) $label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
	               $result = $paiement->addPaymentToBank($user, 'payment', $label, GETPOST('accountid'),GETPOST('socid'), GETPOST('chqemetteur'), GETPOST('chqbank'));
	              if ($result < 0)
	              {
	                   setEventMessages($paiement->error, $paiement->errors, 'errors');
	                   $error++;
	              }
	           }
		        $total_paid = $paiement->amount;
                $invoiceid = 0;
                $still_founds = true;
                $error_ticket = 0;
               	foreach($t_amounts as $t_k => $t_v)
				   {
				   	break;
				   	$ttick = new Ticket($db);
				   	$ttick ->fetch($t_k);
				   	$ttick->set_paid($user);
				   }                
	        }
        if ((!$error) || $t_k == null)
        {
			$db->commit();
			setEventMessages('Pago(s) registrado(s) con éxito.','');
			//$loc = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$paiement_id;
			$loc = DOL_URL_ROOT.'/cfdimx/pagosfacturas.php?id='.$paiement_id;
			//$loc = DOL_URL_ROOT.'/compta/facture/list.php';
			header('Location: '.$loc);
			exit;    
		}
  		else 
    	{
     		$db->rollback();
       	}
        }
	}


/*
 * View
 */

$form = new Form($db);


llxHeader('', $langs->trans("Payment"));



if ($action == 'create' || $action == 'confirm_paiement' || $action == 'add_paiement')
{
	if ($facid)
    {
    $facture = new Facture($db);
    $result = $facture->fetch($facid);
    }
    else
    {
        $facture = new Ticket($db);
        $facture->fetch(GETPOST('tktid'));
        $facture->thirdparty = new Societe($db);
        $facture->thirdparty->fetch($facture->socid);
    }
	

	if (!$facid || $result >= 0)
	{
	   
		$facture->fetch_thirdparty();

		$title = '';
		if ($facture->type != Facture::TYPE_CREDIT_NOTE) $title .= $langs->trans("EnterPaymentReceivedFromCustomer");
		if ($facture->type == Facture::TYPE_CREDIT_NOTE) $title .= $langs->trans("EnterPaymentDueToCustomer");
		print load_fiche_titre($title);

		// Initialize data for confirmation (this is used because data can be change during confirmation)
		if ($action == 'add_paiement')
		{
			$i = 0;

			$formquestion[$i++] = array('type' => 'hidden', 'name' => 'facid', 'value' => $facture->id);
			$formquestion[$i++] = array('type' => 'hidden', 'name' => 'socid', 'value' => $facture->socid);
			$formquestion[$i++] = array('type' => 'hidden', 'name' => 'type', 'value' => $facture->type);
		}

		// Invoice with Paypal transaction
		// TODO add hook possibility (regis)
		if (!empty($conf->paypalplus->enabled) && $conf->global->PAYPAL_ENABLE_TRANSACTION_MANAGEMENT && !empty($facture->ref_int))
		{
			if (!empty($conf->global->PAYPAL_BANK_ACCOUNT)) $accountid = $conf->global->PAYPAL_BANK_ACCOUNT;
			$paymentnum = $facture->ref_int;
		}

		// Add realtime total information
		if (!empty($conf->use_javascript_ajax))
		{
			print "\n".'<script type="text/javascript" language="javascript">';
			print '$(document).ready(function () {
            			setPaiementCode();

            			$("#selectpaiementcode").change(function() {
            				setPaiementCode();
            			});

            			function setPaiementCode()
            			{
            				var code = $("#selectpaiementcode option:selected").val();

                            if (code == \'CHQ\' || code == \'VIR\')
            				{
            					if (code == \'CHQ\')
			                    {
			                        $(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
			                    }
            					if ($(\'#fieldchqemetteur\').val() == \'\')
            					{
            						var emetteur = ('.$facture->type.' == '.Facture::TYPE_CREDIT_NOTE.') ? \''.dol_escape_js(dol_escape_htmltag($conf->global->MAIN_INFO_SOCIETE_NOM)).'\' : jQuery(\'#thirdpartylabel\').val();
            						$(\'#fieldchqemetteur\').val(emetteur);
            					}
            				}
            				else
            				{
            					$(\'.fieldrequireddyn\').removeClass(\'fieldrequired\');
            					$(\'#fieldchqemetteur\').val(\'\');
            				}
            			}

						function _elemToJson(selector)
						{
							var subJson = {};
							$.map(selector.serializeArray(), function(n,i)
							{
								subJson[n["name"]] = n["value"];
							});

							return subJson;
						}
						function callForResult(imgId)
						{
							var json = {};
							var form = $("#payment_form");

							json["invoice_type"] = $("#invoice_type").val();
            				json["amountPayment"] = $("#amountpayment").attr("value");
							json["amounts"] = _elemToJson(form.find("input.amount"));
							json["remains"] = _elemToJson(form.find("input.remain"));

							if (imgId != null) {
								json["imgClicked"] = imgId;
							}

							$.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
							{
								json = $.parseJSON(data);

								form.data(json);

								for (var key in json)
								{
									if (key == "result")	{
										if (json["makeRed"]) {
											$("#"+key).addClass("error");
										} else {
											$("#"+key).removeClass("error");
										}
										json[key]=json["label"]+" "+json[key];
										$("#"+key).text(json[key]);
									} else {console.log(key);
										form.find("input[name*=\""+key+"\"]").each(function() {
											$(this).attr("value", json[key]);
										});
									}
								}
							});
						}
						$("#payment_form").find("input.amount").change(function() {
							callForResult();
						});
						$("#payment_form").find("input.amount").keyup(function() {
							callForResult();
						});
			';

			print '	});'."\n";

			//Add js for AutoFill
			print ' $(document).ready(function () {';
			print ' 	$(".AutoFillAmout").on(\'click touchstart\', function(){
							$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
						});';
			print '	});'."\n";

			print '	</script>'."\n";
		}

		print '<form id="payment_form" name="add_paiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add_paiement">';
        if ($facid)
        {
            print '<input type="hidden" name="facid" value="'.$facture->id.'">';
        }
        else
        {
            print '<input type="hidden" name="tktid" value="'.$facture->id.'">';
        }
		
		print '<input type="hidden" name="socid" value="'.$facture->socid.'">';
		print '<input type="hidden" name="type" id="invoice_type" value="'.$facture->type.'">';
		print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($facture->thirdparty->name).'">';
        print '<input type="hidden" name="masspaiement" value="'.$massive_payment.'">';

		dol_fiche_head();

		print '<table class="border centpercent">';

        // Third party
        print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans('Company').'</span></td><td>'.$facture->thirdparty->getNomUrl(4)."</td></tr>\n";

        // Date payment
        print '<tr><td><span class="fieldrequired">'.$langs->trans('Date').'</span></td><td>';
        $datepayment = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        $datepayment = ($datepayment == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : '') : $datepayment);
        print $form->selectDate($datepayment, '', '', '', 0, "add_paiement", 1, 1, 0, '', '', $facture->date);
        print '</td></tr>';

        // Payment mode
        print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
        $form->select_types_paiements((GETPOST('paiementcode') ?GETPOST('paiementcode') : $facture->mode_reglement_code), 'paiementcode', '', 2);
        print "</td>\n";
        print '</tr>';

        // Bank account
        print '<tr>';
        if (!empty($conf->banque->enabled))
        {
            if ($facture->type != 2) print '<td><span class="fieldrequired">'.$langs->trans('AccountToCredit').'</span></td>';
            if ($facture->type == 2) print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
            print '<td>';
            $form->select_comptes($accountid, 'accountid', 0, '', 2);
            print '</td>';
        }
        else
        {
            print '<td>&nbsp;</td>';
        }
        print "</tr>\n";

		$temp = ($user->fk_account?$user->fk_account:'0');
        print '<script>
                $(document).ready(function () {
                    if($("#selectpaiementcode").val() == "LIQ" && '.$temp.' != "0"){
                        $("#selectaccountid option[value='.$temp.']").attr("selected", true);
                        $("#selectaccountid > option").each(function() {
                            if( this.value != '.$temp.' ) {
                                $("#selectaccountid option[value=" + this.value + "]").hide();
                            }
                        });
                    }else{
                        $("#selectaccountid > option").each(function() {
                            $("#selectaccountid option[value=" + this.value + "]").show();
                        });
                    }
                    $("#selectpaiementcode").on("change",function(){
                        if($("#selectpaiementcode").val() == "LIQ" && '.$temp.' != "0"){
                            $("#selectaccountid option[value='.$temp.']").attr("selected", true);
                            $("#selectaccountid > option").each(function() {
                                if( this.value != '.$temp.' ) {
                                    $("#selectaccountid option[value=" + this.value + "]").hide();
                                }
                            });
                        }else{
                            $("#selectaccountid > option").each(function() {
                                $("#selectaccountid option[value=" + this.value + "]").show();
                            });
                        }
                    });
                });
                </script>';

        // Cheque number
        print '<tr><td>'.$langs->trans('Numero');
        print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
        print '</td>';
        print '<td><input name="num_paiement" type="text" value="'.$paymentnum.'"></td></tr>';

        // Check transmitter
        print '<tr><td class="'.(GETPOST('paiementcode') == 'CHQ' ? 'fieldrequired ' : '').'fieldrequireddyn">'.$langs->trans('CheckTransmitter');
        print ' <em>('.$langs->trans("ChequeMaker").')</em>';
        print '</td>';
        print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.GETPOST('chqemetteur', 'alphanohtml').'"></td></tr>';

        // Bank name
        print '<tr><td>'.$langs->trans('Bank');
        print ' <em>('.$langs->trans("ChequeBank").')</em>';
        print '</td>';
        print '<td><input name="chqbank" size="30" type="text" value="'.GETPOST('chqbank', 'alphanohtml').'"></td></tr>';

		// Comments
		print '<tr><td>'.$langs->trans('Comments').'</td>';
		print '<td class="tdtop">';
		print '<textarea name="comment" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.GETPOST('comment', 'none').'</textarea></td></tr>';

        print '</table>';

		dol_fiche_end();


        /*
         * List of unpaid invoices
         */
                  

        $sql = 'SELECT f.rowid as facid, f.ref, f.total_ttc, f.multicurrency_code, f.multicurrency_total_ttc, f.type,';
        $sql .= ' f.datef as df, f.fk_soc as socid, f.date_lim_reglement as dlr';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql .= ' WHERE f.entity IN ('.getEntity('facture').')';
        $sql .= ' AND (f.fk_soc = '.$facture->socid;
		// Can pay invoices of all child of parent company
		if (!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS) && !empty($facture->thirdparty->parent)) {
			$sql .= ' OR f.fk_soc IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE parent = '.$facture->thirdparty->parent.')';
		}
		// Can pay invoices of all child of myself
		if (!empty($conf->global->FACTURE_PAYMENTS_ON_SUBSIDIARY_COMPANIES)) {
			$sql .= ' OR f.fk_soc IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE parent = '.$facture->thirdparty->id.')';
		}
        $sql .= ') AND f.paye = 0';
        $sql .= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled

        if ($facture->type != Facture::TYPE_CREDIT_NOTE)
        {
            $sql .= ' AND type IN (0,1,2,3,5)'; // Standard invoice, replacement, deposit, situation
        }
        else
        {
            $sql .= ' AND type IN (0,1,2,3,5)'; // If paying back a credit note, we show all credit notes
        }
        // Sort invoices by date and serial number: the older one comes first
        $sql .= ' ORDER BY f.datef ASC, f.ref ASC';

        $resql = $db->query($sql);
        
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num > 0 || true)
            {
                $temporal_client = new Societe($db);
                $temporal_client->fetch($facture->socid);
				$arraytitle = $langs->trans('Invoice');
				if ($facture->type == 999) $arraytitle = $langs->trans("CreditNotes");
				$alreadypayedlabel = $langs->trans('Received');
				$multicurrencyalreadypayedlabel = $langs->trans('MulticurrencyReceived');
				if ($facture->type == 999) { $alreadypayedlabel = $langs->trans("PaidBack"); $multicurrencyalreadypayedlabel = $langs->trans("MulticurrencyPaidBack"); }
				$remaindertopay = $langs->trans('RemainderToTake');
				$multicurrencyremaindertopay = $langs->trans('MulticurrencyRemainderToTake');
				if ($facture->type == 999) { $remaindertopay = $langs->trans("RemainderToPayBack"); $multicurrencyremaindertopay = $langs->trans("MulticurrencyRemainderToPayBack"); }

                $i = 0;
                //print '<tr><td colspan="3">';
                print '<br>';
                print '<table class="noborder centpercent">';

                print '<tr class="liste_titre">';
                print '<td>'.$arraytitle.'</td>';
                print '<td class="center">'.$langs->trans('Date').'</td>';
                print '<td class="center">'.$langs->trans('DateMaxPayment').'</td>';
                if (!empty($conf->multicurrency->enabled)) {
                	print '<td>'.$langs->trans('Currency').'</td>';
                	print '<td class="right">'.$langs->trans('MulticurrencyAmountTTC').'</td>';
                	print '<td class="right">'.$multicurrencyalreadypayedlabel.'</td>';
                	print '<td class="right">'.$multicurrencyremaindertopay.'</td>';
                	print '<td class="right">'.$langs->trans('MulticurrencyPaymentAmount').'</td>';
                }
                print '<td class="right">'.$langs->trans('AmountTTC').'</td>';

                // Early payment discount
                if($massive_payment){
                    print '<td class="right">';
                    print $langs->trans('Discount');
                    print '<input type="text" value="'.$temporal_client->earlypayment_discount.'%" style="width: 50px;" disabled>';
                    print'</td>';
                }
                print '<td class="right">'.$alreadypayedlabel.'</td>';
                print '<td class="right">'.$remaindertopay.'</td>';
                print '<td class="right">'.$langs->trans('PaymentAmount').'</td>';

                $parameters = array();
                //$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $facture, $action); // Note that $action and $object may have been modified by hook

                print '<td align="right">&nbsp;</td>';
                print "</tr>\n";

                $total = 0;
                $totalrecu = 0;
                $totalrecucreditnote = 0;
                $totalrecudeposits = 0;
                $total_discounted = 0;

				$sign = 1;
				//INICIO req para que al tener notas de crédito ponga el resto si es que es mayor a las facturas
				$invoicestatic = new Facture($db);
				$socstatic = new Societe($db);
				$totalfacturasNormales = 0;
				$massive_ids_unique = array_unique($massive_ids);
				foreach($massive_ids_unique as $key => $value){
                    $invoicestatic->fetch($value);
					$socstatic->fetch($invoicestatic->socid);
					if($massive_payment && $socstatic->earlypayment_discount && !$invoicestatic->discount_applied &&  $invoicestatic->type != 2) {
                        $ep_discount = $invoicestatic->total_ttc * $socstatic->earlypayment_discount / 100;
                        $after_discount = $invoicestatic->total_ttc - $ep_discount;
                    }
                    else {
                    	$ep_discount = 0;
                        $after_discount = $invoicestatic->total_ttc;
                    }

                    $paiement = $invoicestatic->getSommePaiement();
                    $creditnotes = $invoicestatic->getSumCreditNotesUsed();
                    $deposits = $invoicestatic->getSumDepositsUsed();
                    $alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
                    $remaintopay = price2num($after_discount - $paiement - $creditnotes - $deposits, 'MT');
					if($invoicestatic->type != 2){
						$totalfacturasNormales += $remaintopay;
					}
				}
				//FIN req para que al tener notas de crédito ponga el resto si es que es mayor a las facturas

                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);

                    //$sign = 1;
                    //if ($facture->type == Facture::TYPE_CREDIT_NOTE) $sign = -1;

					$soc = new Societe($db);
					$soc->fetch($objp->socid);

                    $invoice = new Facture($db);
                    $invoice->fetch($objp->facid);
                    // if we have early_payment discount and multipay
                    if($massive_payment && $soc->earlypayment_discount && !$invoice->discount_applied &&  $invoice->type != 2) {
                        $ep_discount = $invoice->total_ttc * $soc->earlypayment_discount / 100;
                        $after_discount = $invoice->total_ttc - $ep_discount;
                    }
                    else {
                    	$ep_discount = 0;
                        $after_discount = $invoice->total_ttc;
                    }

                    $paiement = $invoice->getSommePaiement();
                    $creditnotes = $invoice->getSumCreditNotesUsed();
                    $deposits = $invoice->getSumDepositsUsed();
                    $alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
                    $remaintopay = price2num($after_discount - $paiement - $creditnotes - $deposits, 'MT');

					// Multicurrency Price
					if (!empty($conf->multicurrency->enabled)) {
						$multicurrency_payment = $invoice->getSommePaiement(1);
						$multicurrency_creditnotes = $invoice->getSumCreditNotesUsed(1);
						$multicurrency_deposits = $invoice->getSumDepositsUsed(1);
						$multicurrency_alreadypayed = price2num($multicurrency_payment + $multicurrency_creditnotes + $multicurrency_deposits, 'MT');
	                    $multicurrency_remaintopay = price2num($invoice->multicurrency_total_ttc - $multicurrency_payment - $multicurrency_creditnotes - $multicurrency_deposits, 'MT');
					}

					//If is multipayment we ignore other factures
                    if ($massive_payment){
                        if (!in_array($invoice->id, $massive_ids)){
                            $i++;
                            continue;
                        }
                        else{
                            print '<input type="hidden" name="facid'.$invoice->id.'" value="'.$invoice->id.'">';
                        }
                    }


					print '<tr class="oddeven'.(($invoice->id == $facid) ? ' highlight' : '').'">';

					print '<td class="nowraponall">';
                    print $invoice->getNomUrl(1, '');
                    if ($objp->socid != $facture->thirdparty->id) print ' - '.$soc->getNomUrl(1).' ';
                    print "</td>\n";

                    // Date
                   	print '<td class="center">'.dol_print_date($db->jdate($objp->df), 'day')."</td>\n";

                    // Due date
                    if ($objp->dlr > 0)
                    {
                        print '<td class="nowraponall center">';
                        print dol_print_date($db->jdate($objp->dlr), 'day');

                        if ($invoice->hasDelay())
                        {
                            print img_warning($langs->trans('Late'));
                        }

                        print '</td>';
                    }
                    else
                    {
                        print '<td align="center"></td>';
                    }

                    // Currency
                    if (!empty($conf->multicurrency->enabled)) print '<td class="center">'.$objp->multicurrency_code."</td>\n";

					// Multicurrency Price
					if (!empty($conf->multicurrency->enabled))
					{
					    print '<td class="right">';
					    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) print price($sign * $objp->multicurrency_total_ttc);
					    print '</td>';

                    	// Multicurrency Price
						print '<td class="right">';
						if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
						{
						    print price($sign * $multicurrency_payment);
    		                if ($multicurrency_creditnotes) print '+'.price($multicurrency_creditnotes);
    		                if ($multicurrency_deposits) print '+'.price($multicurrency_deposits);
						}
		                print '</td>';

    					// Multicurrency remain to pay
    				    print '<td class="right">';
    				    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) print price($sign * $multicurrency_remaintopay);
    				    print '</td>';

    				    print '<td class="right nowraponall">';

    				    // Add remind multicurrency amount
    				    $namef = 'multicurrency_amount_'.$objp->facid;
    				    $nameRemain = 'multicurrency_remain_'.$objp->facid;

    				    if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
    				    {
    				    	if ($action != 'add_paiement')
    				    	{
    				    		if (!empty($conf->use_javascript_ajax))
    				    			print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $multicurrency_remaintopay)."'");
   				    			print '<input type="text" class="maxwidth75 multicurrency_amount" name="'.$namef.'" value="'.$_POST[$namef].'">';
   				    			print '<input type="hidden" class="multicurrency_remain" name="'.$nameRemain.'" value="'.$multicurrency_remaintopay.'">';
    				    	}
    				    	else
    				    	{
    				    		print '<input type="text" class="maxwidth75" name="'.$namef.'_disabled" value="'.$_POST[$namef].'" disabled>';
    				    		print '<input type="hidden" name="'.$namef.'" value="'.$_POST[$namef].'">';
    				    	}
    				    }
    				    print "</td>";
					}

					// Price
                    print '<td class="right">'.price($sign * $objp->total_ttc).'</td>';

					// Early payment discounted
                    if ($massive_payment){
                        print '<td class="right">' . price($ep_discount) . '</td>';
                    }

                    // Received or paid back
                    print '<td class="right">'.price($sign * $paiement);
                    if ($creditnotes) print '+'.price($creditnotes);
                    if ($deposits) print '+'.price($deposits);
                    print '</td>';

                    // Remain to take or to pay back
                    print '<td class="right">'.price($sign * $remaintopay).'</td>';
                    //$test= price(price2num($objp->total_ttc - $paiement - $creditnotes - $deposits));

                    // Amount
                    print '<td class="right nowraponall">';

                    // Add remind amount
                    $namef = 'amount_'.$objp->facid;
                    $nameRemain = 'remain_'.$objp->facid;
                    if ($action != 'add_paiement')
                    {
                        if (!empty($conf->use_javascript_ajax))
							print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $remaintopay)."'");




                        if($massive_payment){
                            if(in_array($objp->facid, $massive_ids)){
								if($invoice->type == 2){
									$remaintopay = $totalfacturasNormales * -1;
								}
                                print '<input type="text" class="maxwidth75 amount" name="'.$namef.'" value="'.round($remaintopay,2).'">';
                            }
                            else{
                                print '<input type="text" class="maxwidth75 amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                            }
                        }
                        else{
                            print '<input type="text" class="maxwidth75 amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                        }


                        print '<input type="hidden" class="remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
                    }
                    else
                    {
                        print '<input type="text" class="maxwidth75" name="'.$namef.'_disabled" value="'.dol_escape_htmltag(GETPOST($namef)).'" disabled>';
                        print '<input type="hidden" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                    }
                    print "</td>";

                    $parameters = array();
                    $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objp, $action); // Note that $action and $object may have been modified by hook

                    // Warning
                    print '<td align="center" width="16">';
                    //print "xx".$amounts[$invoice->id]."-".$amountsresttopay[$invoice->id]."<br>";
                    /*if ($amounts[$invoice->id] && (abs($amounts[$invoice->id]) > abs($amountsresttopay[$invoice->id]))
                    	|| $multicurrency_amounts[$invoice->id] && (abs($multicurrency_amounts[$invoice->id]) > abs($multicurrency_amountsresttopay[$invoice->id])))
                    {
                        print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));
                    }*/
                    print '</td>';

                    print "</tr>\n";

                    $total += $objp->total;
                    $total_ttc += $objp->total_ttc;
                    $totalrecu += $paiement;
                    $totalrecucreditnote += $creditnotes;
                    $totalrecudeposits += $deposits;
                    $total_discounted = $total_discounted + $ep_discount;
                    $i++;
                }
                require_once(DOL_DOCUMENT_ROOT.'/compta/paiement_ticket.php');

                if ($i > 1)
                {
                    // Print total
                    print '<tr class="liste_total">';
                    print '<td colspan="3" class="left">'.$langs->trans('TotalTTC').'</td>';
                    if (!empty($conf->multicurrency->enabled)) {
                    	print '<td></td>';
                    	print '<td></td>';
                    	print '<td></td>';
                    	print '<td></td>';
                    	print '<td class="right" id="multicurrency_result" style="font-weight: bold;"></td>';
                    }
					print '<td class="right"><b>'.price($sign * $total_ttc).'</b></td>';
                    if ($massive_payment) print '<td class="right"><b>'.price($total_discounted).'</b></td>';
                    print '<td class="right"><b>'.price($sign * $totalrecu);
                    if ($totalrecucreditnote) print '+'.price($totalrecucreditnote);
                    if ($totalrecudeposits) print '+'.price($totalrecudeposits);
                    print '</b></td>';
                    print '<td class="right"><b>'.price($sign * price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits - $total_discounted, 'MT')).'</b></td>';
                    print '<td class="right" id="result" style="font-weight: bold;"></td>'; // Autofilled
                    print '<td align="center">&nbsp;</td>';
                    print "</tr>\n";
                }
                print "</table>";
                //print "</td></tr>\n";
            }
            $db->free($resql);
        }
        else
		{
            dol_print_error($db);
        }
        
        


        // Bouton Enregistrer
        if ($action != 'add_paiement')
        {
        	$checkboxlabel = $langs->trans("ClosePaidInvoicesAutomatically");
        	if (false && $facture->type == Facture::TYPE_CREDIT_NOTE) $checkboxlabel = $langs->trans("ClosePaidCreditNotesAutomatically");
        	$buttontitle = $langs->trans('ToMakePayment');
        	if (false && $facture->type == Facture::TYPE_CREDIT_NOTE) $buttontitle = $langs->trans('ToMakePaymentBack');

        	print '<br><div class="center">';
        	print '<input type="checkbox" checked name="closepaidinvoices"> '.$checkboxlabel;
            /*if (! empty($conf->prelevement->enabled))
            {
                $langs->load("withdrawals");
                if (! empty($conf->global->WITHDRAW_DISABLE_AUTOCREATE_ONPAYMENTS)) print '<br>'.$langs->trans("IfInvoiceNeedOnWithdrawPaymentWontBeClosed");
            }*/
            print '<br><input type="submit" class="button" value="'.dol_escape_htmltag($buttontitle).'"><br><br>';
            print '</div>';
        }

        // Form to confirm payment
        if ($action == 'add_paiement')
        {
            $preselectedchoice = $addwarning ? 'no' : 'yes';
			if($temporal_client->earlypayment_discount && $massive_payment){
				print $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type, $langs->trans('AskGenerateCreditNoteCFDI'), '', 'confirm_paiement', $formquestion, $preselectedchoice);
			}
			else{
				print '<br>';
				if (!empty($totalpayment)) $text = $langs->trans('ConfirmCustomerPayment', $totalpayment, $langs->trans("Currency".$conf->currency));
				if (!empty($multicurrency_totalpayment))
				{
					$text .= '<br>'.$langs->trans('ConfirmCustomerPayment', $multicurrency_totalpayment, $langs->trans("paymentInInvoiceCurrency"));
				}
				if (GETPOST('closepaidinvoices'))
				{
					$text .= '<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
					print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
				}
				if (true) //TODO: Verificar el descuento por pronto pago del cliente
				{
					$text.= '<br /><br />Las <b>notas de crédito necesarias</b> seran creadas y aplicadas automáticamente.';
				}
				print $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type, $langs->trans('ReceivedCustomersPayments'), $text, 'confirm_paiement', $formquestion, $preselectedchoice);
			}	
        }

		print "</form>\n";
	}
}


/**
 *  Show list of payments
 */
if (!GETPOST('action', 'aZ09'))
{
    if (empty($page) || $page == -1) $page = 0;
    $limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
    $offset = $limit * $page;

    if (!$sortorder) $sortorder = 'DESC';
    if (!$sortfield) $sortfield = 'p.datep';

    $sql = 'SELECT p.datep as dp, p.amount, f.amount as fa_amount, f.ref';
    $sql .= ', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'paiement as p LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
    $sql .= ', '.MAIN_DB_PREFIX.'facture as f';
    $sql .= ' WHERE p.fk_facture = f.rowid';
    $sql .= ' AND f.entity IN ('.getEntity('invoice').')';
    if ($socid)
    {
        $sql .= ' AND f.fk_soc = '.$socid;
    }

    $sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
    $sql .= $db->plimit($limit + 1, $offset);
    $resql = $db->query($sql);

    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        print_barre_liste($langs->trans('Payments'), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num);
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print_liste_field_titre('Invoice', $_SERVER["PHP_SELF"], 'ref', '', '', '', $sortfield, $sortorder);
        print_liste_field_titre('Date', $_SERVER["PHP_SELF"], 'dp', '', '', '', $sortfield, $sortorder);
        print_liste_field_titre('Type', $_SERVER["PHP_SELF"], 'libelle', '', '', '', $sortfield, $sortorder);
        print_liste_field_titre('Amount', $_SERVER["PHP_SELF"], 'fa_amount', '', '', '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
        print "</tr>\n";

        while ($i < min($num, $limit))
        {
            $objp = $db->fetch_object($resql);

            print '<tr class="oddeven">';
            print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$objp->facid.'">'.$objp->ref."</a></td>\n";
            print '<td>'.dol_print_date($db->jdate($objp->dp))."</td>\n";
            print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
            print '<td class="right">'.price($objp->amount).'</td>';
            print '<td>&nbsp;</td>';
            print '</tr>';

            $parameters = array();
            $reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $objp, $action); // Note that $action and $object may have been modified by hook

            $i++;
        }
        print '</table>';
    }
}

llxFooter();

$db->close();
