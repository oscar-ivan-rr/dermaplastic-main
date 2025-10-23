<?php
/* Copyright (C) 2011 		Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2013 	Ferran Marcet           <fmarcet@2byte.es>
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
 *  \file       htdocs/pos/class/ticket.class.php
 *  \ingroup    ticket
 *  \brief      Ticket Class file
 *  \version    $Id: ticket.class.php,v 1.9 2011-08-04 16:12:46 jmenent Exp $
 */
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");


/**
 *  \class      Ticket
 *  \brief      Class to manage customers tickets
 */
/**
 *	\class      Ticket
 *	\brief      Classe permettant la gestion des tickets clients
 */
class Ticket extends CommonObject
{
    var $db;
    var $error;
    var $errors=array();
    var $element='ticket';
    var $table_element='pos_ticket';
    var $table_element_line = 'pos_ticketdet';
    var $fk_element = 'fk_ticket';
    var $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;
    //! Id client
    var $socid;
    //! Objet societe client (to load with fetch_client method)
    var $client;
    var $author;
    var $fk_user_author;
    var $fk_user_valid;
    //! Ticket date
    var $date;				// Ticket date
    var $date_creation;		// Creation date
    var $date_closed;		// Closure date
    var $datem;
    var $ref;
    var $ticketnumber;
        
    var $id_source;
    var $fk_cash;
    var $fk_facture;
    
    //! 0=Standard ticket, 1=Credit note ticket,2=Deposit ticket
    var $type;
	var $fk_place;
	var $fk_project;

    var $remise_absolute;
    var $remise_percent;
    var $total_ht;
    var $total_tva;
    var $total_ttc;
    var $total_localtax1;
    var $customer_pay;
    var $diff_payment;
    
    var $note;
    var $note_public;
       
    //! 0=draft,
    //! 1=to invoice
    //! 2=invoiced
    //! 3=No invoicable
    //! 4=return ticket
    var $statut;
    //! Fermeture apres paiement partiel: discount_vat, badcustomer, abandon
    //! Fermeture alors que aucun paiement: replaced (si remplace), abandon
    var $close_code;
    //! Commentaire si mis a paye sans paiement complet
    var $close_note;
    //! 1 if ticket paid COMPLETELY, 0 otherwise (do not use it anymore, use statut and close_code
    var $paye;

    var $mode_reglement_id;			// Id in llx_c_paiement
    var $mode_reglement_code;		// Code in llx_c_paiement
    var $modelpdf;
    var $products=array();	// TODO deprecated
    var $lines=array();
    var $line;
    //! Pour board
    var $nbtodo;
    var $nbtodolate;
    var $specimen;
    //! Numero d'erreur de 512 a 1023
    var $errno = 0;

    // Early payment discount
    /**
     * @var double total_ttc_before_discount
     */
    var $total_ttc_before_discount;

    /**
     * @var int discount_applied
     */
    var $discount_applied;


    //checkpoint: var pre_prefix N/S
    var $pre_prefix="";
    /**
     *	Class constructor
     *
     *	@param  DoliDB	$DB         	DB handler
     */
    function Ticket($DB)
    {
        $this->db = $DB;

        $this->amount = 0;
        $this->remise_absolute = 0;
        $this->remise_percent = 0;
        $this->total_ht = 0;
        $this->total_tva = 0;
        $this->total_ttc = 0;
        $this->statut = 1;
        $this->fk_place = 0;
       
    }

    /**
     *	Create ticket in database
     *
     *	@param     	user       		Id user that create
     *	@return		int				<0 if KO, >0 if OK
     */
    function create($user,$refIsSet=false)
    {
        global $langs,$conf,$mysoc;
        $error=0;

        // Clean parameters
        if (! $this->type) $this->type = 0;
        if (! $this->remise_absolute) $this->remise_absolute = 0;
        if (! $this->remise_percent) $this->remise_percent = 0;
        if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
        if (! $this->customer_pay) $this->customer_pay = 0;
        if (! $this->diff_payment) $this->diff_payment = 0;
        if (! $this->id_source) $this->id_source = 0;
        if (! $this->fk_place) $this->fk_place = 0;
        
		$this->note=trim($this->note);
        $this->note_public=trim($this->note_public);

        dol_syslog("Ticket::Create user=".$user);

        // Check parameters
        $soc = new Societe($this->db);
        $result=$soc->fetch($this->socid);
        if ($result < 0)
        {
            $this->error="Failed to fetch company";
            dol_syslog("Ticket::create ".$this->error, LOG_ERR);
            return -2;
        }

        $now=dol_now();

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."pos_ticket (";
        $sql.= " ticketnumber";
        $sql.= ", entity";
        $sql.= ", type";
        $sql.= ", fk_cash";
        $sql.= ", fk_soc";
        $sql.= ", date_creation";
        $sql.= ", date_closed";
        $sql.= ", remise_absolute";
        $sql.= ", remise_percent";
        $sql.= ", customer_pay";
        $sql.= ", difpayment";
        $sql.= ", date_ticket";
        $sql.= ", note";
        $sql.= ", note_public";
        $sql.= ", fk_statut";
        $sql.= ", fk_user_author";
        $sql.= ", fk_user_close";
        $sql.= ", fk_mode_reglement, model_pdf";
        $sql.= ", fk_ticket_source";
        $sql.= ", fk_place";
        $sql.= ", fk_projet";
        $sql.= ")";
        $sql.= " VALUES (";
        $sql.= "'(PROV)'";
        $sql.= ", ".$conf->entity;
        $sql.= ", '".$this->type."'";
        $sql.= ", '".$this->fk_cash."'";
        $sql.= ", '".$this->socid."'";
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", ".($this->statut==1 ? "'".$this->db->idate($now)."'":"null");
        $sql.= ", ".($this->remise_absolue>0?$this->remise_absolue:'NULL');
        $sql.= ", ".($this->remise_percent>0?$this->remise_percent:'NULL');
        $sql.= ", ".$this->customer_pay;
        $sql.= ", ".$this->diff_payment;
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", ".($this->note?"'".$this->db->escape($this->note)."'":"null");
        $sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
        $sql.= ", ".$this->statut;
        $sql.= ", ".$user;
        $sql.= ", ".($this->statut==1 ? "'".$user."'":"null");
        $sql.= ", 57"; // Se crea con forma de pago "Por Definir" y se define en el pago .$this->mode_reglement_id;
        $sql.= ", '".$this->modelpdf."'";
        $sql.= ", ".$this->id_source."";
        $sql.= ", ".($this->fk_place ? $this->fk_place: 'null');
        $sql.= ", ".($this->fk_project ? $this->fk_project: 'null').")";
        
        dol_syslog("Ticket::Create sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'pos_ticket');
			if (($this->statut==1 || $this->statut==2 || $this->type==1) && !$refIsSet)
			{
				$this->ref= $this->getNextNumRef($soc);
			}
			elseif ($this->statut==0 && !$refIsSet)
			{ 
				$this->ref='(PROV'.$this->id.')';
			}
			
			$sql = 'UPDATE '.MAIN_DB_PREFIX."pos_ticket SET ticketnumber='".$this->ref."' WHERE rowid=".$this->id;
			
            dol_syslog("Ticket::create sql=".$sql);
            $resql=$this->db->query($sql);
            if (! $resql) 
            {
            	$this->db->rollback();
            	return -1;
            }
            else
            {
                if($this->fk_project)
				{
                    global $user;
                    $result = $this->call_trigger('TICKET_CREATE', $user);
                }
                $this->db->commit();
				return $this->id;
            }

        }

        else
        {
            $this->error=$this->db->error();
            dol_syslog("Ticket::create error ".$this->error." sql=".$sql, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      Return clicable link of object (with eventually picto)
     *      @param      withpicto       Add picto into link
     *      @param      option          Where point the link
     *      @param      max             Maxlength of ref
     *      @return     string          String with URL
     */
    function getNomUrl($withpicto=0,$option='',$max=0)
    {
        global $langs;

        $result='';

        $lien = '<a href='.dol_buildpath('/pos/backend/ticket.php',1).'?id='.$this->id.'>';
        $lienfin='</a>';

        $picto='bill';

        $label=$langs->trans("ShowTicket").': '.$this->ref;
        
        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien.($max?dol_trunc($this->ref,$max):$this->ref).$lienfin;
        return $result;
    }


    /**
     *	Get object and lines from database
     *	@param      rowid       Id of object to load
     * 	@param		ref			Reference of ticket
     *	@return     int         >0 if OK, <0 if KO
     */
    function fetch($rowid, $ref='')
    {
        global $conf;

        if (empty($rowid) && empty($ref)) return -1;

        $sql = 'SELECT f.rowid, f.ticketnumber, f.fk_cash, f.type, f.fk_soc, f.tva, f.localtax1, f.localtax2';
        $sql.= ', f.total_ht,f.total_ttc,f.remise_percent,f.remise_absolute,f.remise';
        $sql.= ', f.date_creation, f.date_ticket, f.date_closed';
        $sql.= ', f.tms as datem, f.customer_pay, f.paye, f.difpayment';
        $sql.= ', f.note, f.note_public, f.fk_statut, f.paye,  f.fk_user_author, f.fk_user_close, f.model_pdf';
        $sql.= ', f.fk_mode_reglement';
        $sql.= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
        $sql.= ', f.fk_ticket_source AS fk_source';
        $sql.= ', el.fk_source AS fk_source_el';
        $sql.= ', f.fk_place';
        $sql.= ', f.fk_facture';
        $sql.= ', f.note, f.fk_projet, f.total_ttc_before_discount, f.discount_applied';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = f.rowid AND el.targettype = '".$this->element."'";
        $sql.= ' WHERE f.entity = '.$conf->entity;
        if ($rowid)   $sql.= " AND f.rowid=".$rowid;
        if ($ref)     $sql.= " AND f.ticketnumber='".$this->db->escape($ref)."'";

        dol_syslog("Ticket::Fetch sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id                     = $obj->rowid;
                $this->ticketnumber           = $obj->ticketnumber;
                $this->ref					  = $obj->ticketnumber;
                $this->type                   = $obj->type;
                $this->date_ticket            = $this->db->jdate($obj->date_ticket);
                $this->date_creation          = $this->db->jdate($obj->date_creation);
                $this->date_closed	          = $this->db->jdate($obj->date_closed);
                $this->remise_percent         = $obj->remise_percent;
                $this->remise_absolue         = $obj->remise_absolue;
                $this->remise                 = $obj->remise;
                $this->total_ht               = $obj->total_ht;
                $this->total_tva              = $obj->tva;
                $this->total_localtax1		  = $obj->localtax1;
                $this->total_localtax2		  = $obj->localtax2;
                $this->total_ttc              = $obj->total_ttc;
                $this->paye                   = $obj->paye;
                $this->customer_pay			  = $obj->customer_pay;
                $this->diff_payment			  = $obj->difpayment;
                $this->socid                  = $obj->fk_soc;
                $this->statut                 = $obj->fk_statut;
                $this->mode_reglement_id      = $obj->fk_mode_reglement;
                $this->fk_cash				  = $obj->fk_cash;
                $this->fk_project			  = $obj->fk_projet;

                $this->id_source		      = $obj->fk_source;
                $this->note                   = $obj->note;
                $this->note_public            = $obj->note_public;
                $this->user_author            = $obj->fk_user_author;
                $this->user_close             = $obj->fk_user_close;
                $this->modelpdf               = $obj->model_pdf;
                $this->fk_place				  = $obj->fk_place;
                $this->fk_facture		  	  = $obj->fk_facture;
                $this->note					  = $obj->note;
                //
                $this->situation_cycle_ref    = 0;

                //Earlypayment discount
                $this->total_ttc_before_discount = $obj->total_ttc_before_discount;
                $this->discount_applied          = $obj->discount_applied;

                /*
                 * Lines
                 */

                $this->lines  = array();

                $result=$this->fetch_lines();
                if ($result < 0)
                {
                    $this->error=$this->db->error();
                    dol_syslog('Ticket::Fetch Error '.$this->error, LOG_ERR);
                    return -3;
                }
                return 1;
            }
            else
            {
                $this->error='Bill with id '.$rowid.' or ref '.$ref.' not found sql='.$sql;
                dol_syslog('Ticket::Fetch Error '.$this->error, LOG_ERR);
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog('Ticket::Fetch Error '.$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *	Recupere les lignes de tickets dans this->lines
     *	@return     int         1 if OK, < 0 if KO
     */
    function fetch_lines()
    {
        $sql = 'SELECT l.rowid, l.fk_product, l.fk_parent_line, l.description, l.product_type, l.price, l.qty, l.tva_tx, ';
        $sql.= ' l.localtax1_tx, l.localtax2_tx, l.remise, l.remise_percent, l.fk_remise_except, l.subprice,';
        $sql.= ' l.rang,';
        //$sql.= ' l.date_start as date_start, l.date_end as date_end,';
        $sql.= ' l.date_start as date_start, l.date_end as date_end, l.buy_price_ht,';
        $sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc, l.fk_code_ventilation, l.fk_export_compta,';
        $sql.= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as label, p.description as product_desc, l.note as note, p.tosell,p.tobuy';
        $sql.= ' ,l.`ls_warehouse_status`, l.`ls_warehouse_status_by`, l.`ls_warehouse_status_date`,`l`.`qty_ent` ';
        $sql.= ' ,l.`ls_stock_mv_code`,`ls_stock_dec_date`,`ls_stock_dec_qty` ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticketdet as l';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
        $sql.= ' WHERE l.fk_ticket = '.$this->id;
        $sql.= ' ORDER BY l.rowid ASC';
        dol_syslog('Ticket::fetch_lines sql='.$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);
                $line = new TicketLigne($this->db);

                $line->rowid	        = $objp->rowid;
                $line->fk_product		= $opjp->fk_product;
                $line->desc             = $objp->description;     // Description line
                $line->product_type     = $objp->product_type;	// Type of line
                $line->product_ref      = $objp->product_ref;     // Ref product
                $line->ref				= $objp->product_ref;
                $line->libelle          = $objp->label;           // Label product
                $line->product_desc     = $objp->product_desc;    // Description product
                $line->product_label	= $objp->label;
                $line->tosell	        = $objp->tosell;
                $line->tobuy	        = $objp->tobuy;
                $line->fk_product_type  = $objp->fk_product_type;	// Type of product
                $line->qty              = $objp->qty;
                $line->subprice         = $objp->subprice;
                $line->tva_tx           = $objp->tva_tx;
                $line->localtax1_tx     = $objp->localtax1_tx;
                $line->localtax2_tx     = $objp->localtax2_tx;
                $line->remise_percent   = $objp->remise_percent;
                $line->fk_remise_except = $objp->fk_remise_except;
                $line->fk_product       = $objp->fk_product;
                $line->date_start       = $this->db->jdate($objp->date_start);
                $line->date_end         = $this->db->jdate($objp->date_end);
                $line->date_start       = $this->db->jdate($objp->date_start);
                $line->date_end         = $this->db->jdate($objp->date_end);
                $line->info_bits        = $objp->info_bits;
                $line->total_ht         = $objp->total_ht;
                $line->total_tva        = $objp->total_tva;
                $line->total_localtax1  = $objp->total_localtax1;
                $line->total_localtax2  = $objp->total_localtax2;
                $line->total_ttc        = $objp->total_ttc;
                $line->export_compta    = $objp->fk_export_compta;
                $line->code_ventilation = $objp->fk_code_ventilation;
                $line->rang				= $objp->rang;
                $line->special_code		= $objp->special_code;
                $line->fk_parent_line	= $objp->fk_parent_line;
				$line->note 			= $objp->note;
                
                // Ne plus utiliser
                $line->price            = $objp->price;
                $line->remise           = $objp->remise;
                
                $line->ls_warehouse_status = $objp->ls_warehouse_status;
                $line->ls_warehouse_status_by = $objp->ls_warehouse_status_by;
                $line->ls_warehouse_status_date = $objp->ls_warehouse_status_date;
                $line->qty_ent			= $objp->qty_ent;
                $line->ls_stock_mv_code = $objp->ls_stock_mv_code;
                $line->ls_stock_dec_date = $objp->ls_stock_dec_date;
				$line->ls_stock_dec_qty = $objp->ls_stock_dec_qty;

                //precio compra producto
			    $line->pa_ht	= $objp->buy_price_ht;
                
                $this->lines[$i] = $line;

                $i++;
            }
            $this->db->free($result);
            return 1;
        }
        else
        {
        	
            $this->error=$this->db->error();
            dol_syslog('Ticket::fetch_lines: Error '.$this->error,LOG_ERR);
            return -3;
        }
    }


    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;
        
        if (is_object($user) && property_exists($user,'id'))
        {
        	$user = $user->id;
        }
        
        $now=dol_now();

		// Clean parameters
        if (! $this->type) $this->type = 0;
        if (! $this->remise_absolute) $this->remise_absolute = 0;
        if (! $this->remise_percent) $this->remise_percent = 0;
        if (! $this->mode_reglement_id) $this->mode_reglement_id = 0;
        if (! $this->customer_pay) $this->customer_pay = 0;
        if (! $this->diff_payment) $this->diff_payment = 0;
              
        
		$this->note=trim($this->note);
        $this->note_public=trim($this->note_public);

    	if ($this->statut>0)
		{
			if ($this->ref == "(PROV".$this->id.")")
			{
				$soc = new Societe($this->db);
        		$result=$soc->fetch($this->socid);
            	$this->ref = $this->getNextNumRef($soc);
			}
		}

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."pos_ticket SET";

        $sql.= " ticketnumber=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
        $sql.= " type=".(isset($this->type)?$this->type:"null").",";
        $sql.= " fk_soc=".(isset($this->socid)?$this->socid:"null").",";
        $sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
        $sql.= " fk_place=".(isset($this->fk_place)?$this->fk_place:"null").",";
        $sql.= " date_closed=".($this->statut==1 ? "'".$this->db->idate($now)."'":"null").",";
        $sql.= " remise_percent=".(isset($this->remise_percent)?$this->remise_percent:"null").",";
        $sql.= " remise_absolute=".(isset($this->remise_absolute)?$this->remise_absolute:"null").",";
        $sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
        $sql.= " fk_user_close=".($this->statut==1 ? "'".$user."'":"null").",";
        $sql.= " customer_pay=".(isset($this->customer_pay)?$this->customer_pay:"null").",";
        $sql.= " difpayment=".(isset($this->diff_payment)?$this->diff_payment:"null").",";
        $sql.= " fk_ticket_source=".(isset($this->id_source)?$this->id_source:"null").",";
        $sql.= " fk_mode_reglement=".(isset($this->mode_reglement_id)?$this->mode_reglement_id:"null").",";
        $sql.= " model_pdf=".(isset($this->modelpdf)?"'".$this->db->escape($this->modelpdf)."'":"null").",";
        $sql.= " note=".($this->note?"'".$this->db->escape($this->note)."'":"null");
        $sql.= ", note_public=".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
        $sql.= ", total_ttc_before_discount=".($this->total_ttc_before_discount?"'".$this->db->escape($this->total_ttc_before_discount)."'":"0");
        $sql.= ", total_ttc=".($this->total_ttc?"'".$this->db->escape($this->total_ttc)."'":"0");
        $sql.= ", discount_applied =".($this->discount_applied?"'".$this->db->escape($this->discount_applied)."'":"0");
        $sql.= " WHERE rowid=".$this->id;

        $this->db->begin();
//die ($sql);
        dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {dol_print_error($this->db); $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error)
        {
            if (! $notrigger)
            {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action call a trigger.

                //// Call triggers
                //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('BILL_MODIFY',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return 1;
        }
    }


    /**
     *    \brief     Ajout en base d'une ligne remise fixe en ligne de ticket
     *    \param     idremise			Id de la remise fixe
     *    \return    int          		>0 si ok, <0 si ko
     */
    function insert_discount($idremise)
    {
        global $langs;

        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');
        include_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');

        $this->db->begin();

        $remise=new DiscountAbsolute($this->db);
        $result=$remise->fetch($idremise);
        
        //die (var_dump($remise));

        if ($result > 0)
        {
            if ($remise->fk_ticket)	// Protection against multiple submission
            {
                $this->error=$langs->trans("ErrorDiscountAlreadyUsed");
                $this->db->rollback();
                return -5;
            }

            $facligne=new TicketLigne($this->db);
            $facligne->fk_ticket=$this->id;
            $facligne->fk_remise_except=$remise->id;
            $facligne->desc=$remise->description;   	// Description ligne
            $facligne->tva_tx=$remise->tva_tx;
            $facligne->subprice=-$remise->amount_ht;
            $facligne->fk_product=0;					// Id produit predefini
            $facligne->qty=1;
            $facligne->remise_percent=0;
            $facligne->rang=-1;
            $facligne->info_bits=2;

            // Ne plus utiliser
            $facligne->price=-$remise->amount_ht;
            $facligne->remise=0;

            $facligne->total_ht  = -$remise->amount_ht;
            $facligne->total_tva = -$remise->amount_tva;
            $facligne->total_ttc = -$remise->amount_ttc;

                    // Cr�e lien entre remise et ligne de ticket
                    //$result=$remise->link_to_ticket($this->id);
                    if ($result < 0)
                    {
                        $this->error=$remise->error;
                        $this->db->rollback();
                        return -4;
                    }

            $lineid=$facligne->insert();
            if ($lineid > 0)
            {
                $result=$this->update_price(1);
                if ($result > 0)
                {

                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->error=$facligne->error;
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error=$facligne->error;
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            return -3;
        }
    }


    function set_ref_client($ref_client)
    {
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
        if (empty($ref_client))
        $sql .= ' SET ref_client = NULL';
        else
        $sql .= ' SET ref_client = \''.$this->db->escape($ref_client).'\'';
        $sql .= ' WHERE rowid = '.$this->id;
        if ($this->db->query($sql))
        {
            $this->ref_client = $ref_client;
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Delete draft tickets after close cash
     * 
     *	@return		int			<0 if KO, >0 if OK
     */
    function delete()
    {
		//return 1; // Se elimina el borrado de tickets en borrador al hacer el corte de caja.
		    	
        global $user,$langs,$conf,$db;
        $error=0;
        $this->db->begin();
        $to_delete = date('Y-m-d',strtotime('-40 days')).' 23:59:59';

		$sql =   "SELECT rowid "."\r\n"
                ."FROM ".MAIN_DB_PREFIX."pos_ticket  "."\r\n"
                ."WHERE ticketnumber LIKE '%PROV%' "."\r\n"
                ."  AND fk_statut=0 "."\r\n"
                ."  AND tms <='{$to_delete}' "."\r\n"
                ;
		$sql .= "  AND entity = ".$conf->entity;
		$resql=$this->db->query($sql);
		if ($resql)
		{
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {            	
                $objp = $this->db->fetch_object($resql);
                if (! $this->deleteline($objp->rowid))	$error++;
                if($objp->fk_place)
                {
                	dol_include_once('/pos/class/place.class.php');
                	$place = new Place($db);
                	$place->fetch($objp->fk_place);
                	$place->free_place();
                	
                }
                $i++;
            }
            
            if(! $error)
            {
            	$sql =   "DELETE "."\r\n"
                        ."FROM ".MAIN_DB_PREFIX."pos_ticket  "."\r\n"
                        ."WHERE ticketnumber LIKE '%PROV%' "."\r\n"
                        ."  AND fk_statut=0 "."\r\n"
                        ."  AND tms <='{$to_delete}' "."\r\n"
                        ;
            	$sql .= "  AND entity = ".$conf->entity;
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
            }
            else
            {
            	$this->db->rollback();
            	return -1;
            }
		}
     	else
            {
            	$this->db->rollback();
            	return -1;
            }
            
    }

    /**
     * Delete ticket and all the lines
     * @return number		<0 if KO, >0 if OK
     */
    function delete_ticket()
    {
    	global $user,$langs,$conf,$db;

        if ($this->statut == 3)
        {
            return -1;
        }

        return $this->set_canceled();

    /*

    	$sql =	 "UPDATE ".MAIN_DB_PREFIX."pos_ticket "." \r\n"
    			."SET `fk_statut` = 3 "." \r\n"
    			."WHERE rowid= ".$this->id." \r\n"
    			."  AND entity = ".$conf->entity." \r\n"
				;
		$resql=$this->db->query($sql);
		if (!$resql)
		{
    		$this->db->rollback();
    		return -1;
		}
		else
		{
			foreach ($this->lines as $stat_line)
			{
				if ($stat_line->ls_stock_dec_qty > 0)
				{
					require_once(DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php');
					$stkmv = new MouvementStock($this->db);
                    $stkmv->origin = $this;
					$smtype = ($this->type == 1)?'2':'3';
                    $qty = ($this->type == 0) ? $stat_line->ls_stock_dec_qty : $stat_line->ls_stock_dec_qty * -1;
					$stkmvst = $stkmv->_create($user,$stat_line->fk_product,$this->getEntrepot()->id,$qty,$smtype,0,'Cancelación de Ticket '.$this->ref);
					if (false && $stkmvst > 0)
					{
						if ($stat_line->reset_stock_register(0) <=0)
						{
							dol_print_error($this->db);
							die();
						}
					}
				}
			}
   			$this->db->commit();
   			return 1;
		}		
		dol_include_once('/pos/class/place.class.php');
    	
    	$sql = "DELETE FROM ".MAIN_DB_PREFIX."pos_ticketdet WHERE fk_ticket=".$this->id;
    	
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."pos_ticket";
    		$sql .= " WHERE rowid= ".$this->id;
    		$sql .= " AND entity = ".$conf->entity;
    		$resql=$this->db->query($sql);
    		if ($resql)
    		{
    			if($this->statut == 0)
    			{
    				$place = new Place($db);
    				$place->fetch($this->fk_place);
    				$place->free_place();
    			}
    			$this->db->commit();
    			return 1;
    		}
    		else
    		{
    			$this->db->rollback();
    			return -1;
    		}
    		
    	}
    	else
    	{
    		$this->db->rollback();
    		return -1;
    	}
    */
    }
    
     /**
     *	Renvoi une date limite de reglement de ticket en fonction des
     *	conditions de reglements de la ticket et date de facturation
     *	@param      cond_reglement_id   Condition de reglement a utiliser, 0=Condition actuelle de la ticket
     *	@return     date                Date limite de reglement si ok, <0 si ko
     */
    function calculate_date_lim_reglement($cond_reglement_id=0)
    {
        if (! $cond_reglement_id)
        $cond_reglement_id=$this->cond_reglement_id;
        $sqltemp = 'SELECT c.fdm,c.nbjour,c.decalage';
        $sqltemp.= ' FROM '.MAIN_DB_PREFIX.'c_payment_term as c';
        $sqltemp.= ' WHERE c.rowid='.$cond_reglement_id;
        $resqltemp=$this->db->query($sqltemp);
        if ($resqltemp)
        {
            if ($this->db->num_rows($resqltemp))
            {
                $obj = $this->db->fetch_object($resqltemp);
                $cdr_nbjour = $obj->nbjour;
                $cdr_fdm = $obj->fdm;
                $cdr_decalage = $obj->decalage;
            }
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
        $this->db->free($resqltemp);

        /* Definition de la date limite */

        // 1 : ajout du nombre de jours
        $datelim = $this->date + ( $cdr_nbjour * 3600 * 24 );

        // 2 : application de la regle "fin de mois"
        if ($cdr_fdm)
        {
            $mois=date('m', $datelim);
            $annee=date('Y', $datelim);
            if ($mois == 12)
            {
                $mois = 1;
                $annee += 1;
            }
            else
            {
                $mois += 1;
            }
            // On se deplace au debut du mois suivant, et on retire un jour
            $datelim=dol_mktime(12,0,0,$mois,1,$annee);
            $datelim -= (3600 * 24);
        }

        // 3 : application du decalage
        $datelim += ( $cdr_decalage * 3600 * 24);

        return $datelim;
    }

    /**
     *      Tag la ticket comme paye completement (close_code non renseigne) ou partiellement (close_code renseigne) + appel trigger BILL_PAYED
     *      @param      user      	Objet utilisateur qui modifie
     *		@param      close_code	Code renseigne si on classe a payee completement alors que paiement incomplet (cas escompte par exemple)
     *	   	@param      close_note	Commentaire renseigne si on classe a payee alors que paiement incomplet (cas escompte par exemple)
     *      @return     int         <0 si ok, >0 si ok
     */
    function set_paid($user,$close_code='',$close_note='')
    {
		global $conf,$langs;
        $error=0;
        
        require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
		$static_pos = new POS($this->db);
		$static_pos->cronFixSaldos($this->id); 
		$this->fetch($this->id);       

        if ($this->paye != 1)
        {
            $this->db->begin();

            dol_syslog("Ticket::set_paid rowid=".$this->id, LOG_DEBUG);
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket SET';
            $sql.= ' fk_statut=1';
            if (! $close_code) $sql.= ' ,paye=1';
            elseif ($close_code) $sql.= " ,close_code='".$this->db->escape($close_code)."'";
            if ($close_note) $sql.= ", close_note='".$this->db->escape($close_note)."'";
            $sql.= ' WHERE rowid = '.$this->id;

            $resql = $this->db->query($sql);
            if ($resql)
            {
                $this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

                // Appel des triggers
                /*include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('BILL_PAYED',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers*/
            }
            else
            {
                $error++;
                $this->error=$this->db->error();
                dol_print_error($this->db);
            }

            if (! $error)
            {
                $this->db->commit();
/** Done at POS::setAutoWarehouseStatusOnSave                	
				require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
                if (!is_array($this->lines) || !count($this->lines) )
                {
                	$this->fetch_lines();
                }
                foreach($this->lines as $line)
                {
                	$stat_prod=new Product($this->db);
                	$stat_prod->fetch($line->fk_product);
                	$stat_prod->load_stock();
                	$wh = $this->getEntrepot();
                	if($this->type==1)
                	{
                		$st='NULL';
                	}
                	elseif (isset($stat_prod->stock_warehouse[$wh]) && $stat_prod->stock_warehouse[$wh]->real >= $line->qty)
                	{
                		$st = 6;
                	}
                	else
                	{
                		$st = 4;
                	}
                	$line->updateEstadoV($st);
                }
*/
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            return 0;
        }
    }


    /**
     *      \brief      Tag la ticket comme non payee completement + appel trigger BILL_UNPAYED
     *				   	Fonction utilisee quand un paiement prelevement est refuse,
     * 					ou quand une ticket annulee et reouverte.
     *      \param      user        Object user that change status
     *      \return     int         <0 si ok, >0 si ok
     */
    function set_unpaid()
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
        $sql.= ' SET paye=0, fk_statut=1';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("Ticket::set_unpaid sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	\brief      Tag la ticket comme abandonnee, sans paiement dessus (exemple car ticket de remplacement) + appel trigger BILL_CANCEL
     *	\param      user        Objet utilisateur qui modifie
     *	\return     int         <0 si ok, >0 si ok
     */
    function set_canceled()
    {
        global $conf,$langs,$user;
        $this->db->begin();
        $sql =	 'SELECT `sm`.`fk_product` AS `fk_product`,SUM(`sm`.`value`) AS `pending`'."\r\n"
				.'FROM `llx_stock_mouvement` AS `sm`'."\r\n"
				.'WHERE `sm`.`origintype`=\'ticket\''."\r\n"
				.'  AND `sm`.`fk_origin`=\''.$this->id.'\''."\r\n"
				.'GROUP BY `fk_product`'."\r\n"
				;
		if (!$res = $this->db->query($sql))
		{
			return -5;
		}
		$pend = array();
		while ($row = $this->db->fetch_object($res))
		{
			if ($row->pending != 0)
			{
				$pend[$row->fk_product]=$row->pending;
			}
		}
        if (!$user->admin && empty($user->rights->pos->cancelpaidticket) && count($pend))
        {
        	return -99;
        }

        foreach ($this->lines as $stat_line)
        {
            if ($stat_line->ls_stock_dec_qty > 0)
            {
                require_once(DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php');
                $stkmv = new MouvementStock($this->db);
                $stkmv->origin = $this;
                $smtype = ($this->type == 1)?'2':'3';
                $qty = ($this->type == 0) ? $stat_line->ls_stock_dec_qty : $stat_line->ls_stock_dec_qty * -1;
                $stkmvst = $stkmv->_create($user,$stat_line->fk_product,$this->getEntrepot()->id,$qty,$smtype,0,'Cancelación de Ticket '.$this->ref);
                if (false && $stkmvst > 0)
                {
                    if ($stat_line->reset_stock_register(0) <=0)
                    {
                        //dol_print_error($this->db);
                        $this->db->rollback();
                        return -1;
                    }
                }
            }
        }
        $sql =	 'SELECT `sm`.`amount` AS `pending`'."\r\n"
                .'       ,p.fk_paiement as `tipo` '."\r\n"
                .'       ,p.rowid as paiement_id'."\r\n"
				.'FROM `llx_pos_paiement_ticket` AS `sm`'."\r\n"
                .'LEFT JOIN `llx_paiement` AS `p` '."\r\n"
                .'  ON p.rowid = sm.fk_paiement '."\r\n"
				.'WHERE `sm`.`fk_ticket` = \''.$this->id.'\''."\r\n"
				;
        
		if (!$res = $this->db->query($sql))
		{
            $this->db->rollback();
			return -5;
		}

        if (!$user->admin && empty($user->rights->pos->cancelpaidticket)  && empty($user->rights->pos->cancelpaidticket))
        {
            $this->db->rollback();
            return -100;
        }

		$pend = array();
        $return = true;
		while ($row = $this->db->fetch_object($res))
		{
			if ($row->pending != 0)
			{
                // Insert request
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_except"."\r\n";
                $sql .= " (entity, datec, fk_soc, discount_type, fk_user, description,"."\r\n";
                $sql .= " amount_ht, amount_tva, amount_ttc, tva_tx,"."\r\n";
                $sql .= " multicurrency_amount_ht, multicurrency_amount_tva, multicurrency_amount_ttc,"."\r\n";
                //$sql .= " fk_ticket,"."\r\n";
                $sql .= " fk_paiement, fk_paiement_type"."\r\n";
                $sql .= ")"."\r\n";
                $sql .= " VALUES (".$conf->entity.", ' "."\r\n"
                                ."".$this->db->idate(dol_now())."', "."\r\n"
                                ."".$this->socid.", "."\r\n"
                                ."".( 0 ).", "."\r\n"
                                ."".$user->id.", "."\r\n"
                                ."'".$this->db->escape('Cancelación de ticket '.$this->ref)."',"."\r\n"
                                ."".($row->pending / 1.16).", "."\r\n"
                                ."".($row->pending * .16).", "."\r\n"
                                ."".$row->pending.", "."\r\n"
                                ."" . 16 .", "."\r\n"
                                ."". 0 .", "."\r\n"
                                ."". 0 .", "."\r\n"
                                ."". 0 .", "."\r\n"
                                //."".("null").","."\r\n"
                                //."".($this->id).", "."\r\n"
                                ."".$row->paiement_id.", "."\r\n"
                                ."".$row->tipo." "."\r\n"
                                ;
                $sql .= ")";

                //echo '<pre>'.$sql.'</pre>';

                $resql = $this->db->query($sql);
                //die (var_dump($resql));
                if ($resql)
                {
                    $return = $this->db->last_insert_id(MAIN_DB_PREFIX."societe_remise_except");
                    //return $this->id;
                }
                else
                {
                    $return = false;
                    //dol_print_error($this->db);
                    //$this->error = $this->db->lasterror().' - sql='.$sql;
                    //return -1;
                }

			}
		}
        //die('si corro '.__LINE__);
        if ((!$return))
        {
            $this->db->rollback();
        	return -1;
        }





        dol_syslog("Ticket::set_canceled rowid=".$this->id, LOG_DEBUG);

        $facture_id = $this->unlink_invoice();

        if ($facture_id < 0)
        {
            //die ('Error: '.$facture_id);
            $this->db->rollback();
            return -1;
        }
        //$this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket SET';
        $sql.= ' fk_statut=3,';
        $sql.= ' date_cancel=\''.date('Y-m-d H:i:s').'\' ';
        $sql.= ' WHERE rowid = '.$this->id;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
       {
            $this->error=$this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      \brief     	Tag la ticket comme validee + appel trigger BILL_VALIDATE
     *      \param     	user            Utilisateur qui valide la ticket
     *      \param     	force_number	Reference a forcer de la ticket
     *	    \return		int				<0 si ko, >0 si ok
     */
    function validate($user, $force_number='')
    {
        global $conf,$langs;
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

        $error=0;

        // Protection
        if (! $this->brouillon)
        {
            dol_syslog("Ticket::validate no draft status", LOG_WARNING);
            return 0;
        }

        if (! $user->rights->ticket->valider)
        {
            $this->error='Permission denied';
            dol_syslog("Ticket::validate ".$this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $this->fetch_thirdparty();
        $this->fetch_lines();

        // Check parameters
        if ($this->type == 1)		// si ticket de remplacement
        {
            // Controle que ticket source connue
            if ($this->fk_ticket_source <= 0)
            {
                $this->error=$langs->trans("ErrorFieldRequired",$langs->trans("TicketReplacement"));
                $this->db->rollback();
                return -10;
            }

            // Charge la ticket source a remplacer
            $facreplaced=new Ticket($this->db);
            $result=$facreplaced->fetch($this->fk_ticket_source);
            if ($result <= 0)
            {
                $this->error=$langs->trans("ErrorBadTicket");
                $this->db->rollback();
                return -11;
            }

            // Controle que ticket source non deja remplacee par une autre
            $idreplacement=$facreplaced->getIdReplacingTicket('validated');
            if ($idreplacement && $idreplacement != $this->id)
            {
                $facreplacement=new Ticket($this->db);
                $facreplacement->fetch($idreplacement);
                $this->error=$langs->trans("ErrorTicketAlreadyReplaced",$facreplaced->ref,$facreplacement->ref);
                $this->db->rollback();
                return -12;
            }

            $result=$facreplaced->set_canceled($user,'replaced','');
            if ($result < 0)
            {
                $this->error=$facreplaced->error." sql=".$sql;
                $this->db->rollback();
                return -13;
            }
        }

        // Define new ref
        if ($force_number)
        {
            $num = $force_number;
        }
        else if (preg_match('/^[\(]?PROV/i', $this->ref))
        {
            if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force ticket date
            {
                $this->date=gmmktime();
                $this->date_lim_reglement=$this->calculate_date_lim_reglement();
            }
            $num = $this->getNextNumRef($this->client);
        }
        else
        {
            $num = $this->ref;
        }

        if ($num)
        {
            $this->update_price(1);

            // Validate
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
            $sql.= " SET ticketnumber='".$num."', fk_statut = 1, fk_user_valid = ".$user->id;
            if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force ticket date
            {
                $sql.= ', datef='.$this->db->idate($this->date);
                $sql.= ', date_lim_reglement='.$this->db->idate($this->date_lim_reglement);
            }
            $sql.= ' WHERE rowid = '.$this->id;

            dol_syslog("Ticket::validate sql=".$sql);
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                dol_syslog("Ticket::validate Echec update - 10 - sql=".$sql, LOG_ERR);
                dol_print_error($this->db);
                $error++;
            }

            // On verifie si la ticket etait une provisoire
            if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref)))
            {
                // La verif qu'une remise n'est pas utilisee 2 fois est faite au moment de l'insertion de ligne
            }

            if (! $error)
            {
                // Define third party as a customer
                $result=$this->client->set_as_client();

                // Si active on decremente le produit principal et ses composants a la validation de ticket
                if ($result >= 0 && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_BILL)
                {
                    require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");

                    // Loop on each line
                    for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
                    {
                        if ($this->lines[$i]->fk_product > 0 && $this->lines[$i]->product_type == 0)
                        {
                            $mouvP = new MouvementStock($this->db);
                            // We decrease stock for product
                            $entrepot_id = "1"; // TODO ajouter possibilite de choisir l'entrepot
                            $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $entrepot_id, $this->lines[$i]->qty, $this->lines[$i]->subprice);
                            if ($result < 0) { $error++; }
                        }
                    }
                }
            }

            if (! $error)
            {
                // Rename directory if dir was a temporary ref
                if (preg_match('/^[\(]?PROV/i', $this->ref))
                {
                    // On renomme repertoire ticket ($this->ref = ancienne ref, $num = nouvelle ref)
                    // afin de ne pas perdre les fichiers attaches
                    $facref = dol_sanitizeFileName($this->ref);
                    $snumfa = dol_sanitizeFileName($num);
                    $dirsource = $conf->ticket->dir_output.'/'.$facref;
                    $dirdest = $conf->ticket->dir_output.'/'.$snumfa;
                    if (file_exists($dirsource))
                    {
                        dol_syslog("Ticket::validate rename dir ".$dirsource." into ".$dirdest);

                        if (@rename($dirsource, $dirdest))
                        {
                            dol_syslog("Rename ok");
                            // Suppression ancien fichier PDF dans nouveau rep
                            dol_delete_file($conf->ticket->dir_output.'/'.$snumfa.'/'.$facref.'.*');
                        }
                    }
                }
            }

            // Set new ref and define current statut
            if (! $error)
            {
                $this->ref = $num;
                $this->ticketnumber=$num;
                $this->statut=1;
            }

            $this->use_webcal=($conf->global->PHPWEBCALENDAR_BILLSTATUS=='always'?1:0);

            // Trigger calls
            if (! $error)
            {
                // Appel des triggers
               /* include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('BILL_VALIDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers*/
            }
        }
        else
        {
            $error++;
        }

        if (! $error)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *		\brief		Set draft status
     *		\param		user		Object user that modify
     *		\param		int			<0 if KO, >0 if OK
     */
    function set_draft($user)
    {
        global $conf,$langs;

        $error=0;

        if ($this->statut == 0)
        {
            dol_syslog("Ticket::set_draft already draft status", LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."pos_ticket";
        $sql.= " SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("Ticket::set_draft sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            // Si active on decremente le produit principal et ses composants a la validation de ticket
            if ($result >= 0 && $conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_BILL)
            {
                require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");

                for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
                {
                    if ($this->lines[$i]->fk_product && $this->lines[$i]->product_type == 0)
                    {
                        $mouvP = new MouvementStock($this->db);
                        // We decrease stock for product
                        $entrepot_id = "1"; // TODO ajouter possibilite de choisir l'entrepot
                        $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $entrepot_id, $this->lines[$i]->qty, $this->lines[$i]->subprice);
                    }
                }
            }

            if ($error == 0)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }


    /**
     * 		Add an ticket line into database (linked to product/service or not)
     * 		\param    	facid           	Id de la ticket
     * 		\param    	desc            	Description de la ligne
     * 		\param    	pu_ht              	Prix unitaire HT (> 0 even for credit note)
     * 		\param    	qty             	Quantite
     * 		\param    	txtva           	Taux de tva force, sinon -1
     * 		\param		txlocaltax1			Local tax 1 rate
     *  	\param		txlocaltax2			Local tax 2 rate
     *		\param    	fk_product      	Id du produit/service predefini
     * 		\param    	remise_percent  	Pourcentage de remise de la ligne
     *      \param 		note				Line's note
     *      \param		type				Type of line (0=product, 1=service)
     * 		\param    	pu_ttc             	Prix unitaire TTC (> 0 even for credit note)      
     *		\param		price_base_type		HT or TTC
     * 		\param    	date_start      	Date de debut de validite du service
     * 		\param    	date_end        	Date de fin de validite du service
     * 		\param    	ventil          	Code de ventilation comptable
     * 		\param    	info_bits			Bits de type de lignes
     *		\param    	fk_remise_except	Id remise
     *      \param      rang                Position of line
     *    	\return    	int             	>0 if OK, <0 if KO
     * 		\remarks	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     *					de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     *					par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
     *					et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    function addline(/*$facid,*/ $desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $note='', $type=0, $pu_ttc=0, $price_base_type='HT', $date_start='', $date_end='', $ventil=0, $info_bits=0, $fk_remise_except='', $rang=-1, $special_code=0, $origin='', $origin_id=0, $fk_parent_line=0,$ls_warehouse_status=null,$ls_warehouse_status_by=null,$ls_warehouse_status_date=null,$qty_ent=null,$ls_stock_dec_date=null,$ls_stock_dec_qty=null)
    {
        dol_syslog("Ticket::Addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, fk_product=$fk_product,remise_percent=$remise_percent,date_start=$date_start,date_end=$date_end,ventil=$ventil,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type", LOG_DEBUG);
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');

        // Clean parameters
        if (empty($remise_percent)) $remise_percent=0;
        if (empty($qty)) $qty=0;
        if (empty($info_bits)) $info_bits=0;
        if (empty($rang)) $rang=0;
        if (empty($ventil)) $ventil=0;
        if (empty($txtva)) $txtva=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;
        if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;
        
        if (! $this->remise_percent)
        {
        	$this->remise_percent=0;
        }

        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu_ht=price2num($pu_ht);
        $pu_ttc=price2num($pu_ttc);
        $txtva=price2num($txtva);
        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);

        if ($price_base_type=='HT')
        {
            $pu=$pu_ht;
        }
        else
        {
            $pu=$pu_ttc;
        }

        // Check parameters
        if ($type < 0) return -1;

       // if ($this->brouillon)
       // {
            $this->db->begin();
            $txtva = 16;

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
           
            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits,$type);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];
            $pu_ht = $tabprice[3];

            // Rang to use
            $rangtouse = $rang;
            if ($rangtouse == -1)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $rangtouse = $rangmax + 1;
            }

            // TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $price = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100),2);
                $price = ($pu - $remise);
            }

            $product_type=$type;
            if ($fk_product)
            {
                $product=new Product($this->db);
                $result=$product->fetch($fk_product);
                $product_type=$product->type;
                $pa_ht = $product->cost_price;
            }

            // Insert line
            $this->line=new TicketLigne($this->db);
            $this->line->fk_ticket=$this->id;
            $this->line->desc=$desc;
            $this->line->product_label = $desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=$txlocaltax1;
            $this->line->localtax2_tx=$txlocaltax2;
            $this->line->fk_product=$fk_product;
            $this->line->product_type=$product_type;
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=       ($this->type==1?-1:1)*($pu_ht);
            $this->line->date_start=$date_start;
            $this->line->date_end=$date_end;
            $this->line->ventil=$ventil;
            $this->line->rang=$rangtouse;
            $this->line->info_bits=$info_bits;
            $this->line->fk_remise_except=$fk_remise_except;
            $this->line->total_ht=       ($this->type==1?-1:1)*($total_ht);
            $this->line->total_tva=      ($this->type==1?-1:1)*($total_tva);
            $this->line->total_localtax1=($this->type==1?-1:1)*($total_localtax1);
            $this->line->total_localtax2=($this->type==1?-1:1)*($total_localtax2);
            $this->line->total_ttc=      ($this->type==1?-1:1)*($total_ttc);
			$this->line->note=$note;
            //$this->line->special_code=$special_code;
            //$this->line->fk_parent_line=$fk_parent_line;
            //$this->line->origin=$origin;
            //$this->line->origin_id=$origin_id;

            // TODO Ne plus utiliser
            $this->line->price=($this->type==1?-1:1)*($pu);
            $this->line->remise=($this->type==1?-1:1)*($remise);
            $this->line->ls_warehouse_status=$ls_warehouse_status;
            $this->line->ls_warehouse_status_by=$user_id;
            $this->line->ls_warehouse_status_date=gmdate('Y-m-d H:i:s');
            $this->line->qty_ent = $qty_ent;
            $this->line->ls_stock_dec_date = $ls_stock_dec_date;
            $this->line->ls_stock_dec_qty = $ls_stock_dec_qty;

            //Precio de compra
            $this->line->pa_ht = $pa_ht;

            $result=$this->line->insert();
            if ($result > 0)
            {
            	// Reorder if child line
				if (! empty($fk_parent_line)) $this->line_order(true,'DESC');
				
                // Mise a jour informations denormalisees au niveau de la ticket meme
                //$this->id=$facid;	// TODO To move this we must remove parameter facid into this function declaration
                $result=$this->update_price(1);
                if ($result > 0)
                {
                    $sql =	 'DELETE FROM `llx_pos_ticketdet_deleted` '."\r\n"
							.'WHERE fk_ticket='.$this->line->fk_ticket.''."\r\n"
							.'  AND fk_product = '.$this->line->fk_product."\r\n"
							.''
							;
					if (!$this->db->query($sql))
					{
	                    $sql="";
    	                $this->error=$this->db->error();
        	            dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
            	        $this->db->rollback();
                	    return -1;
					}
                    $this->db->commit();
                    return $this->line->rowid;
                }
                else
                {
                    $sql="";
                    $this->error=$this->db->error();
                    dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error=$this->line->error;
                $this->db->rollback();
                return -2;
            }
       // }
    }

    /**
     *      Update a detail line
     *      @param     	rowid           Id of line to update
     *      @param     	desc            Description of line
     *      @param     	pu              Prix unitaire (HT ou TTC selon price_base_type) (> 0 even for credit note lines)
     *      @param     	qty             Quantity
     *      @param     	remise_percent  Pourcentage de remise de la ligne
     *      @param     	date_start      Date de debut de validite du service
     *      @param     	date_end        Date de fin de validite du service
     *      @param     	tva_tx          VAT Rate
     * 		@param		txlocaltax1		Local tax 1 rate
     *  	@param		txlocaltax2		Local tax 2 rate
     * 	   	@param     	price_base_type HT or TTC
     * 	   	@param     	info_bits       Miscellanous informations
     * 		@param		type			Type of line (0=product, 1=service)
     *      @return    	int             < 0 if KO, > 0 if OK
     */
    function updateline($rowid, $desc, $pu, $qty, $remise_percent=0, $date_start, $date_end, $txtva, $txlocaltax1=0, $txlocaltax2=0,$price_base_type='HT', $info_bits=0, $type=0, $fk_parent_line=0, $skip_update_total=0)
    {
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php');

        dol_syslog("Ticket::UpdateLine $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type", LOG_DEBUG);

        if ($this->brouillon)
        {
            $this->db->begin();

            // Clean parameters
            $remise_percent=price2num($remise_percent);
            $qty=price2num($qty);
            if (! $qty) $qty=0;
            $pu = price2num($pu);
            $txtva=price2num($txtva);
            $txlocaltax1=price2num($txlocaltax1);
            $txlocaltax2=price2num($txlocaltax2);
            // Check parameters
            if ($type < 0) return -1;

            // Calculate total with, without tax and tax from qty, pu, remise_percent and txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $this->remise_percent, $price_base_type, $info_bits);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1=$tabprice[9];
            $total_localtax2=$tabprice[10];
            $pu_ht  = $tabprice[3];
            $pu_tva = $tabprice[4];
            $pu_ttc = $tabprice[5];

            // Old properties: $price, $remise (deprecated)
            $price = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100),2);
                $price = ($pu - $remise);
            }
            $price    = price2num($price);

            if ($fk_product)
            {
                $product=new Product($this->db);
                $result=$product->fetch($this->line->fk_product);
                $pa_ht = $product->cost_price;
            }

            // Update line into database
            $this->line=new TicketLigne($this->db);
            $this->line->rowid=$rowid;
            $this->line->fetch($rowid);

            $this->line->desc				= $desc;
            $this->line->product_label		= $desc;
            $this->line->qty				= $qty;
            $this->line->tva_tx				= $txtva;
            $this->line->localtax1_tx		= $txlocaltax1;
            $this->line->localtax2_tx		= $txlocaltax2;
            $this->line->remise_percent		= $remise_percent;
            $this->line->subprice			= ($this->type==2?-1:1)*($pu_ht);
            $this->line->date_start			= $date_start;
            $this->line->date_end			= $date_end;
            $this->line->total_ht			= ($this->type==1?-1:1)*($total_ht);
            $this->line->total_tva			= ($this->type==1?-1:1)*($total_tva);
            $this->line->total_localtax1	= ($this->type==1?-1:1)*($total_localtax1);
            $this->line->total_localtax2	= ($this->type==1?-1:1)*($total_localtax2);
            $this->line->total_ttc			= ($this->type==1?-1:1)*($total_ttc);
			$this->line->note				= $note;
            $this->line->info_bits			= $info_bits;
            $this->line->product_type		= $type;
            $this->line->fk_parent_line		= $fk_parent_line;
            $this->line->skip_update_total	= $skip_update_total;
            //Precio de compra
            $this->line->pa_ht = $pa_ht;

            // A ne plus utiliser
            $this->line->price=$pu;
            $this->line->remise=$remise;

            $result=$this->line->update();
            if ($result > 0)
            {
                // Mise a jour info denormalisees au niveau ticket
                $this->update_price(1);
                $this->db->commit();
                return $result;
            }
            else
            {
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error="Ticket::UpdateLine Ticket statut makes operation forbidden";
            return -2;
        }
    }

    /**
     *	Delete line in database
     *	@param		rowid		Id of line to delete
     *	@return		int			<0 if KO, >0 if OK
     */
    function deleteline($rowid)
    {
        global $langs, $conf;

        dol_syslog("Ticket::Deleteline rowid=".$rowid, LOG_DEBUG);
        
        $sql =	 'INSERT INTO '.MAIN_DB_PREFIX.'pos_ticketdet_deleted '."\r\n"
				.'('."\r\n"
				.'	SELECT * FROM '.MAIN_DB_PREFIX.'pos_ticketdet '."\r\n"
				.'	WHERE rowid='.$rowid."\r\n"
				.'	  AND (ls_warehouse_status NOT IN (0,1) AND ls_warehouse_status IS NOT NULL ) '."\r\n"
				.')'
				.''."\r\n"
				;
		if (!$resql=$this->db->query($sql))
		{
			dol_print_error($this->db);
			return $resql;
		}
		$sql =	 'DELETE FROM '.MAIN_DB_PREFIX.'pos_ticketdet_deleted  '."\r\n"
				.'WHERE fk_product= '."\r\n"
				.'( '."\r\n"
				.'	SELECT fk_product  '."\r\n"
				.'	FROM  '.MAIN_DB_PREFIX.'pos_ticketdet'."\r\n"
				.'	WHERE rowid='.$rowid.''."\r\n"
				.')'."\r\n"
				.'  AND fk_ticket = '
				.'( '."\r\n"
				.'	SELECT fk_ticket  '."\r\n"
				.'	FROM  '.MAIN_DB_PREFIX.'pos_ticketdet'."\r\n"
				.'	WHERE rowid='.$rowid.''."\r\n"
				.')'."\r\n"
				.'  AND rowid <>'.$rowid
				;
		if (!$resql=$this->db->query($sql))
		{
			dol_print_error($this->db);
			return $resql;
		}
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."pos_ticketdet WHERE rowid=".$rowid.'';
		if (!$resql=$this->db->query($sql))
		{
			dol_print_error($this->db);
			return $resql;
		}
		
		return $resql;
    }

    /**
     * 		\brief     	Applique une remise relative
     * 		\param     	user		User qui positionne la remise
     * 		\param     	remise
     *		\return		int 		<0 si ko, >0 si ok
     */
    function set_remise($user, $remise)
    {
        $remise=trim($remise)?trim($remise):0;

        if ($user->rights->ticket->creer)
        {
            $remise=price2num($remise);

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
            $sql.= ' SET remise_percent = '.$remise;
            $sql.= ' WHERE rowid = '.$this->id;
            $sql.= ' AND fk_statut = 0 ;';

            if ($this->db->query($sql))
            {
                $this->remise_percent = $remise;
                $this->update_price(1);
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
    }


    /**
     * 		\brief     	Applique une remise absolue
     * 		\param     	user 		User qui positionne la remise
     * 		\param     	remise
     *		\return		int 		<0 si ko, >0 si ok
     */
    function set_remise_absolue($user, $remise)
    {
        $remise=trim($remise)?trim($remise):0;

        if ($user->rights->ticket->creer)
        {
            $remise=price2num($remise);

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
            $sql.= ' SET remise_absolue = '.$remise;
            $sql.= ' WHERE rowid = '.$this->id;
            $sql.= ' AND fk_statut = 0 ;';

            dol_syslog("Ticket::set_remise_absolue sql=$sql");

            if ($this->db->query($sql))
            {
                $this->remise_absolue = $remise;
                $this->update_price(1);
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
    }


    /**
     * 	Return amount of payments already done
     *	@return		int		Amount of payment already done, <0 if KO
     */
    function getSommePaiement()
    {
    	global $db;
    	
        $sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid,';
		$sql.= ' c.code as payment_code, c.libelle as payment_label,';
		$sql.= ' pf.amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'pos_paiement_ticket as pf';
		$sql.= ' WHERE pf.fk_ticket = '.$this->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
		$sql.= ' ORDER BY dp, tms';

	    $result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			$totalpaye=0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$totalpaye+= $objp->amount;
				$i++;
			}
			return $totalpaye;
		}
		else 
		{
			return -1;
		}
    }
    
    /**
     *  Return list of payments
     *
     *	@param		string	$filtertype		1 to filter on type of payment == 'PRE'
     *  @return     array					Array with list of payments
     */
    function getListOfPayments($filtertype='')
    {
    	$retarray=array();
    	if (!strlen($this->id))
    	{
    		return array();
    	}
    
    	$sql = 'SELECT pf.amount, pf.fk_paiement, p.date_ticket	, t.code';
    	$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_paiement_ticket as pf, '.MAIN_DB_PREFIX.'pos_ticket as p, '.MAIN_DB_PREFIX.'c_paiement as t, '.MAIN_DB_PREFIX.'paiement as pa';
    	$sql.= ' WHERE p.rowid = '.$this->id;
    	$sql.= ' AND pf.fk_ticket = p.rowid';
    	$sql.= ' AND pa.fk_paiement = t.id AND pf.fk_paiement = pa.rowid';
    	    
    	dol_syslog(get_class($this)."::getListOfPayments sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		$i=0;
    		while ($i < $num)
    		{
    			$obj = $this->db->fetch_object($resql);
    			$retarray[]=array('amount'=>$obj->amount,'type'=>$obj->code, 'date'=>$obj->datep);
    			$i++;
    		}
    		$this->db->free($resql);
    		return $retarray;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		dol_print_error($this->db);
    		return array();
    	}
    }

    /**
     *    	\brief      Return amount (with tax) of all credit notes and deposits tickets used by ticket
     *		\return		int			<0 if KO, Sum of credit notes and deposits amount otherwise
     */
    function getSumCreditNotesUsed()
    {
    	return 0;
        require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');

        $discountstatic=new DiscountAbsolute($this->db);
        $result=$discountstatic->getSumCreditNotesUsed($this);
        if ($result >= 0)
        {
            return $result;
        }
        else
        {
            $this->error=$discountstatic->error;
            return -1;
        }
    }

    /**
     *    	\brief      Return amount (with tax) of all deposits tickets used by ticket
     *		\return		int			<0 if KO, Sum of deposits amount otherwise
     */
    function getSumDepositsUsed()
    {
    	return 0;
        require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');

        $discountstatic=new DiscountAbsolute($this->db);
        $result=$discountstatic->getSumDepositsUsed($this);
        if ($result >= 0)
        {
            return $result;
        }
        else
        {
            $this->error=$discountstatic->error;
            return -1;
        }
    }

    /**
     * 	\brief     	Renvoie tableau des ids de ticket avoir issus de la ticket
     *	\return		array		Tableau d'id de tickets avoirs
     */
    function getListIdAvoirFromTicket()
    {
        $idarray=array();

        $sql = 'SELECT rowid';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' WHERE fk_ticket_source = '.$this->id;
        $sql.= ' AND type = 2';
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $idarray[]=$row[0];
                $i++;
            }
        }
        else
        {
            dol_print_error($this->db);
        }
        return $idarray;
    }

    /**
     * 	\brief     	Renvoie l'id de la ticket qui la remplace
     *	\param		option		filtre sur statut ('', 'validated', ...)
     *	\return		int			<0 si KO, 0 si aucune ticket ne remplace, id ticket sinon
     */
    function getIdReplacingTicket($option='')
    {
        $sql = 'SELECT rowid';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' WHERE fk_ticket_source = '.$this->id;
        $sql.= ' AND type < 2';
        if ($option == 'validated') $sql.= ' AND fk_statut = 1';
        // PROTECTION BAD DATA
        // Au cas ou base corrompue et qu'il y a une ticket de remplacement validee
        // et une autre non, on donne priorite a la validee.
        // Ne devrait pas arriver (sauf si acces concurrentiel et que 2 personnes
        // ont cree en meme temps une ticket de remplacement pour la meme ticket)
        $sql.= ' ORDER BY fk_statut DESC';

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                // Si il y en a
                return $obj->rowid;
            }
            else
            {
                // Si aucune ticket ne remplace
                return 0;
            }
        }
        else
        {
            return -1;
        }
    }

    /**
     *    \brief      Retourne le libelle du type de ticket
     *    \return     string        Libelle
     */
    function getLibType()
    {
        global $langs;
        if ($this->type == 0) return $langs->trans("TicketStandard");
        elseif($this->type==1) return $langs->trans("TicketCredit");
        return $langs->trans("Unknown");
    }


    /**
     *  Return label of object status
     *  @param      mode            0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
     *  @return     string          Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
     *    	\brief      Renvoi le libelle d'un statut donne
     *    	\param      paye          	Etat paye
     *    	\param      statut        	Id statut
     *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *		\param		alreadypaid	    Montant deja paye
     *		\param		type			Type ticket
     *    	\return     string        	Libelle du statut
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;
        $langs->load('@pos');

      	switch ($mode)
      	{
      		case 0:
      			if ($statut == 0) return $langs->trans('StatusTicketDraft');
				if ($statut == 1) return $langs->trans('StatusTicketClosed');
				if ($statut == 2) return $langs->trans('StatusTicketProcessed');
				if ($statut == 3) return $langs->trans('StatusTicketCanceled');
				break;
      		case 1:
				if ($statut == 0) return img_picto($langs->trans('StatusTicketDraft'),'statut0').' '.$langs->trans('StatusTicketDraft');
                if ($statut == 1) return img_picto($langs->trans('StatusTicketClosed'),'statut4').' '.$langs->trans('StatusTicketClosed');
                if ($statut == 2) return img_picto($langs->trans('StatusTicketProcessed'),'statut6').' '.$langs->trans('StatusTicketProcessed');
                if ($statut == 3) return img_picto($langs->trans('StatusTicketCanceled'),'statut5').' '.$langs->trans('StatusTicketCanceled');			
				break;	
      	}
    }

    /**
     *      Return next reference of ticket not already used (or last reference)
     *      according to numbering module defined into constant TICKET_ADDON
     *      @param	   soc  		           objet company
     *      @param     mode                    'next' for next value or 'last' for last value
     *      @return    string                  free ref or last ref
     */
    function getNextNumRef($soc,$mode='next',$prefix=null)
    {
        global $conf, $db, $langs;
        $langs->load("bills");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->TICKET_ADDON)) $conf->global->TICKET_ADDON='mod_ticket_barx';
        //else if ($conf->global->TICKET_ADDON=='barx') $conf->global->TICKET_ADDON='mod_ticket_barx';
        else $conf->global->TICKET_ADDON='mod_ticket_barx';

        $mybool=false;

        $file = $conf->global->TICKET_ADDON.".php";
        $classname = $conf->global->TICKET_ADDON;
        // Include file with class
        foreach ($conf->file->dol_document_root as $dirroot)
        {
            $dir = $dirroot."/pos/backend/numerotation/";
            // Load file with numbering class (if found)
            $mybool|=@include_once($dir.$file);
        }

        // For compatibility
        if (! $mybool)
        {
            $file = $conf->global->TICKET_ADDON."/".$conf->global->TICKET_ADDON.".modules.php";
            $classname = "mod_ticket_".$conf->global->TICKET_ADDON;
            // Include file with class
            foreach ($conf->file->dol_document_root as $dirroot)
            {
                $dir = $dirroot."/pos/backend/numerotation/";
                // Load file with numbering class (if found)
                $mybool|=@include_once($dir.$file);
            }
        }
        //print "xx".$mybool.$dir.$file."-".$classname;

        if (! $mybool)
        {
            dol_print_error('',"Failed to include file ".$file);
            return '';
        }

        $obj = new $classname();

        $numref = "";
        $numref = $obj->getNumRef($soc,$this,$mode,$prefix);

        if ( $numref != "")
        {
            return $numref;
        }
        else
        {
            //dol_print_error($db,"Ticket::getNextNumRef ".$obj->error);
            return false;
        }
    }

    /**
     *      \brief     Charge les informations de l'onglet info dans l'objet ticket
     *      \param     id       	Id de la ticket a charger
     */
    function info($id)
    {
        $sql = 'SELECT c.rowid, date_creation, date_closed, tms as datem,';
        $sql.= ' fk_user_author, fk_user_close';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as c';
        $sql.= ' WHERE c.rowid = '.$id;

        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                
                if ($obj->fk_user_author)
                {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation     = $cuser;
                }
                if ($obj->fk_user_close)
                {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_close);
                    $this->user_cloture = $vuser;
                }
                $this->date_creation     = $this->db->jdate($obj->date_creation);
                $this->date_cloture   = $this->db->jdate($obj->date_closed);	// Should be in log table
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *  \brief      Change les conditions de reglement de la ticket
     *  \param      cond_reglement_id      	Id de la nouvelle condition de reglement
     * 	\param		date					Date to force payment term
     *  \return     int                    	>0 si ok, <0 si ko
     */
    function cond_reglement($cond_reglement_id,$date='')
    {
        if ($this->statut >= 0 && $this->paye == 0)
        {
            // Define cond_reglement_id and datelim
            if (strval($date) != '')
            {
                $datelim=$date;
                $cond_reglement_id=0;
            }
            else
            {
                $datelim=$this->calculate_date_lim_reglement($cond_reglement_id);
                $cond_reglement_id=$cond_reglement_id;
            }

            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
            $sql.= ' SET fk_cond_reglement = '.$cond_reglement_id.',';
            $sql.= ' date_lim_reglement='.$this->db->idate($datelim);
            $sql.= ' WHERE rowid='.$this->id;

            dol_syslog('Ticket::cond_reglement sql='.$sql, LOG_DEBUG);
            if ( $this->db->query($sql) )
            {
                $this->cond_reglement_id = $cond_reglement_id;
                return 1;
            }
            else
            {
                dol_syslog('Ticket::cond_reglement Erreur '.$sql.' - '.$this->db->error());
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            dol_syslog('Ticket::cond_reglement, etat ticket incompatible');
            $this->error='Entity status not compatible '.$this->statut.' '.$this->paye;
            return -2;
        }
    }


    /**
     *   \brief      Change le mode de reglement
     *   \param      mode        Id du nouveau mode
     *   \return     int         >0 si ok, <0 si ko
     */
    function mode_reglement($mode_reglement_id)
    {
        dol_syslog('Ticket::mode_reglement('.$mode_reglement_id.')', LOG_DEBUG);
        if ($this->statut >= 0 && $this->paye == 0)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_ticket';
            $sql .= ' SET fk_mode_reglement = '.$mode_reglement_id;
            $sql .= ' WHERE rowid='.$this->id;
            $sql .= ' AND fk_mode_reglement = 57 ';
            if ( $this->db->query($sql) )
            {
                $this->mode_reglement_id = $mode_reglement_id;
                return 1;
            }
            else
            {
                dol_syslog('Ticket::mode_reglement Erreur '.$sql.' - '.$this->db->error());
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            dol_syslog('Ticket::mode_reglement, etat ticket incompatible');
            $this->error='Etat ticket incompatible '.$this->statut.' '.$this->paye;
            return -2;
        }
    }


    /**
     *   \brief      Renvoi si les lignes de ticket sont ventilees et/ou exportees en compta
     *   \param      user        Utilisateur creant la demande
     *   \return     int         <0 if KO, 0=no, 1=yes
     */
    function getVentilExportCompta()
    {
        // On verifie si les lignes de tickets ont ete exportees en compta et/ou ventilees
        $ventilExportCompta = 0 ;
        for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
        {
            if ($this->lines[$i]->export_compta <> 0 && $this->lines[$i]->code_ventilation <> 0)
            {
                $ventilExportCompta++;
            }
        }

        if ($ventilExportCompta <> 0)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }


    /**
     *  Return if an ticket can be deleted
     *	Rule is:
     *	If hidden option FACTURE_CAN_BE_REMOVED is on, we can
     *  If ticket has a definitive ref, is last, without payment and not dipatched into accountancy -> yes end of rule
     *  If ticket is draft and ha a temporary ref -> yes
     *  @return    int         <0 if KO, 0=no, 1=yes
     */
    function is_erasable()
    {
        global $conf,$db;

        if (! empty($conf->global->FACTURE_CAN_BE_REMOVED) && $this->statut != 3) return 1;
        
		if ($this->statut == 3) // Si ticket brouillon et provisoire
        {
            return 0;
        }        // on verifie si la ticket est en numerotation provisoire
        $facref = substr($this->ref, 1, 4);

        // If not a draft ticket and not temporary ticket
        if ($facref != 'PROV')
        {
        	$soc = new Societe($db);
        	$soc->fetch($this->socid);
            $maxticketnumber = $this->getNextNumRef($soc,'last');
            $ventilExportCompta = $this->getVentilExportCompta();
            // Si derniere ticket et si non ventilee, on peut supprimer
            if ($maxticketnumber == $this->ref && $ventilExportCompta == 0)
            {
                return 1;
            }
        }
        else if ($this->statut == 0 && $facref == 'PROV') // Si ticket brouillon et provisoire
        {
            return 1;
        }
        
        if (true || $this->getSommePaiement() == 0)
        {
        	return 1;
        }

        return 0;
    }


    /**
     *	\brief     	Renvoi liste des tickets remplacables
     *				Statut validee ou abandonnee pour raison autre + non payee + aucun paiement + pas deja remplacee
     *	\param		socid		Id societe
     *	\return    	array		Tableau des tickets ('id'=>id, 'ref'=>ref, 'status'=>status, 'paymentornot'=>0/1)
     */
    function list_replacable_tickets($socid=0)
    {
        global $conf;

        $return = array();

        $sql = "SELECT f.rowid as rowid, f.ticketnumber, f.fk_statut,";
        $sql.= " ff.rowid as rowidnext";
        $sql.= " FROM ".MAIN_DB_PREFIX."pos_ticket as f";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_ticket as pf ON f.rowid = pf.fk_ticket";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."pos_ticket as ff ON f.rowid = ff.fk_ticket_source";
        $sql.= " WHERE (f.fk_statut = 1 OR (f.fk_statut = 3 AND f.close_code = 'abandon'))";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.paye = 0";					// Pas classee payee completement
        $sql.= " AND pf.fk_paiement IS NULL";		// Aucun paiement deja fait
        $sql.= " AND ff.fk_statut IS NULL";			// Renvoi vrai si pas ticket de remplacement
        if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
        $sql.= " ORDER BY f.ticketnumber";

        dol_syslog("Ticket::list_replacable_tickets sql=$sql");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $return[$obj->rowid]=array(	'id' => $obj->rowid,
				'ref' => $obj->ticketnumber,
				'status' => $obj->fk_statut);
            }
            //print_r($return);
            return $return;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("Ticket::list_replacable_tickets ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *  	\brief     	Renvoi liste des tickets qualifiables pour correction par avoir
     *					Les tickets qui respectent les regles suivantes sont retournees:
     * 					(validee + paiement en cours) ou classee (payee completement ou payee partiellement) + pas deja remplacee + pas deja avoir
     *		\param		socid		Id societe
     *   	\return    	array		Tableau des tickets ($id => $ref)
     */
    function list_qualified_avoir_tickets($socid=0)
    {
        global $conf;

        $return = array();

        $sql = "SELECT f.rowid as rowid, f.ticketnumber, f.fk_statut, pf.fk_paiement";
        $sql.= " FROM ".MAIN_DB_PREFIX."pos_ticket as f";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_ticket as pf ON f.rowid = pf.fk_ticket";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."pos_ticket as ff ON (f.rowid = ff.fk_ticket_source AND ff.type=1)";
        $sql.= " WHERE f.entity = ".$conf->entity;
        $sql.= " AND f.fk_statut in (1,2)";
        //  $sql.= " WHERE f.fk_statut >= 1";
        //	$sql.= " AND (f.paye = 1";				// Classee payee completement
        //	$sql.= " OR f.close_code IS NOT NULL)";	// Classee payee partiellement
        $sql.= " AND ff.type IS NULL";			// Renvoi vrai si pas ticket de remplacement
        $sql.= " AND f.type != 2";				// Type non 2 si ticket non avoir
        if ($socid > 0) $sql.=" AND f.fk_soc = ".$socid;
        $sql.= " ORDER BY f.ticketnumber";

        dol_syslog("Ticket::list_qualified_avoir_tickets sql=$sql");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $qualified=0;
                if ($obj->fk_statut == 1) $qualified=1;
                if ($obj->fk_statut == 2) $qualified=1;
                if ($qualified)
                {
                    //$ref=$obj->ticketnumber;
                    $paymentornot=($obj->fk_paiement?1:0);
                    $return[$obj->rowid]=$paymentornot;
                }
            }

            return $return;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("Ticket::list_avoir_tickets ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *      @param      user                Objet user
     *      @return     int                 <0 if KO, >0 if OK
     */
    function load_board($user)
    {
        global $conf, $user;

        $now=dol_now();

        $this->nbtodo=$this->nbtodolate=0;
        $clause = " WHERE";

        $sql = "SELECT f.rowid, f.date_lim_reglement as datefin";
        $sql.= " FROM ".MAIN_DB_PREFIX."pos_ticket as f";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON f.fk_soc = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = " AND";
        }
        $sql.= $clause." f.paye=0";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.fk_statut = 1";
        if ($user->societe_id) $sql.= " AND f.fk_soc = ".$user->societe_id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($this->db->jdate($obj->datefin) < ($now - $conf->ticket->client->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }


    /* gestion des contacts d'une ticket */

    /**
     *      \brief      Retourne id des contacts clients de facturation
     *      \return     array       Liste des id contacts facturation
     */
    function getIdBillingContact()
    {
        return $this->getIdContact('external','BILLING');
    }

    /**
     *      \brief      Retourne id des contacts clients de livraison
     *      \return     array       Liste des id contacts livraison
     */
    function getIdShippingContact()
    {
        return $this->getIdContact('external','SHIPPING');
    }

    /**
     *      \brief      Charge indicateurs this->nb de tableau de bord
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_state_board()
    {
        global $conf, $user;

        $this->nb=array();

        $clause = "WHERE";

        $sql = "SELECT count(f.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."pos_ticket as f";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = "AND";
        }
        $sql.= " ".$clause." f.entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["tickets"]=$obj->nb;
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     * 	Return an array of ticket lines
     */
    function getLinesArray()
    {
        $sql = 'SELECT l.rowid, l.description, l.fk_product, l.product_type, l.qty, l.tva_tx,';
        $sql.= ' l.fk_remise_except,';
        $sql.= ' l.remise_percent, l.subprice, l.price, l.info_bits, l.rang,';
        $sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
        $sql.= ' l.date_start,';
        $sql.= ' l.date_end,';
        $sql.= ' l.product_type,';
        $sql.= ' p.ref as product_ref, p.fk_product_type, p.label as product_label,';
        $sql.= ' p.description as product_desc, p.price as product_price';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticketdet as l';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product p ON l.fk_product=p.rowid';
        $sql.= ' WHERE l.fk_ticket = '.$this->id;
        $sql.= ' ORDER BY l.rang ASC, l.rowid';

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                $this->lines[$i]->id				= $obj->rowid;
                $this->lines[$i]->description 		= $obj->description;
                $this->lines[$i]->fk_product		= $obj->fk_product;
                $this->lines[$i]->ref				= $obj->product_ref;
                $this->lines[$i]->product_label		= $obj->product_label;
                $this->lines[$i]->product_desc		= $obj->product_desc;
                $this->lines[$i]->fk_product_type	= $obj->fk_product_type;
                $this->lines[$i]->product_type		= $obj->product_type;
                $this->lines[$i]->qty				= $obj->qty;
                $this->lines[$i]->subprice			= $obj->subprice;
                $this->lines[$i]->price 			= $obj->price;
                $this->lines[$i]->product_price		= $obj->product_price;
                $this->lines[$i]->fk_remise_except 	= $obj->fk_remise_except;
                $this->lines[$i]->remise_percent	= $obj->remise_percent;
                $this->lines[$i]->tva_tx			= $obj->tva_tx;
                $this->lines[$i]->info_bits			= $obj->info_bits;
                $this->lines[$i]->total_ht			= $obj->total_ht;
                $this->lines[$i]->total_tva			= $obj->total_tva;
                $this->lines[$i]->total_ttc			= $obj->total_ttc;
                $this->lines[$i]->special_code		= $obj->special_code;
                $this->lines[$i]->rang				= $obj->rang;
                $this->lines[$i]->date_start		= $this->db->jdate($obj->date_start);
                $this->lines[$i]->date_end			= $this->db->jdate($obj->date_end);

                $i++;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("Error sql=$sql, error=".$this->error,LOG_ERR);
            return -1;
        }
    }
    
   /**
     *		Initialise an example of invoice with random values
     *		Used to build previews or test instances
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        $prodids = array();
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE entity = ".$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_prods = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods)
            {
                $i++;
                $row = $this->db->fetch_row($resql);
                $prodids[$i] = $row[0];
            }
        }

        // Initialize parameters
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = time();
        //$this->date_lim_reglement=$this->date+3600*24*30;
        $this->cond_reglement_id   = 1;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_id   = 7;
        $this->mode_reglement_code = '';  // No particular payment mode defined
        $this->note_public='This is a comment (public)';
        $this->note='This is a comment (private)';
        // Lines
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new TicketLigne($this->db);
            $line->desc=$langs->trans("Description")." ".$xnbp;
            $line->qty=1;
            $line->subprice=100;
            $line->price=100;
            $line->tva_tx=19.6;
            $line->localtax1_tx=0;
            $line->localtax2_tx=0;
            $line->remise_percent=10;
            $line->total_ht=90;
            $line->total_ttc=107.64;    // 90 * 1.196
            $line->total_tva=17.64;
            $prodid = rand(1, $num_prods);
            $line->fk_product=$prodids[$prodid];

            $this->lines[$xnbp]=$line;

            $xnbp++;
        }
        // Add a line "offered"
        $line=new TicketLigne($this->db);
        $line->desc=$langs->trans("Description")." ".$xnbp;
        $line->qty=1;
        $line->subprice=100;
        $line->price=100;
        $line->tva_tx=19.6;
        $line->localtax1_tx=0;
        $line->localtax2_tx=0;
        $line->remise_percent=100;
        $line->total_ht=0;
        $line->total_ttc=0;    // 90 * 1.196
        $line->total_tva=0;
        $prodid = rand(1, $num_prods);
        $line->fk_product=$prodids[$prodid];

        $this->lines[$xnbp]=$line;

        $xnbp++;

        $this->amount_ht      = $xnbp*90;
        $this->total_ht       = $xnbp*90;
        $this->total_tva      = $xnbp*90*0.196;
        $this->total_ttc      = $xnbp*90*1.196;
    }
    
    
    function create_facture()
    {
    	require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
        require_once (DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
    	global $db,$user,$langs;
    	$langs->load('pos@pos');
    	
    	$facture = New Facture($db);
    	$societe = New Societe($db);

    	$societe->fetch($this->socid);
    	if(sizeof($societe->array_options) != 0) {
            $facture->array_options['options_formpagcfdi'] = $societe->array_options['options_formpagcfdi'];
            $facture->array_options['options_usocfdi'] = $societe->array_options['options_usocfdi'];
        }
    	$facture->socid =$this->socid;
    	$facture->fk_project =$this->fk_project;
    	//! Objet societe client (to load with fetch_client method)
    	$facture->client = $this->client;
    	$facture->author = $this->author;
    	$facture->fk_user_author = $this->fk_user_author;
    	$facture->fk_user_valid = $this->fk_user_valid;
    	//! Ticket date
    	$now=dol_now();
    	$facture->date = $now;				// Ticket date
    	$facture->date_creation =  $now;		// Creation date
    	$facture->datem = $this->datem;
    	$facture->ref = $this->ref;
    	
    	//! 0=Standard ticket, 1=Credit note ticket,2=Deposit ticket
    	if ($this->type == 1)
    	{
    		$facture->type = 2;
    		$factor = -1;
    	}
    	else
		{
			$facture->type = 0;
			$factor = 1;
		} 
    	
    	    	
    	$facture->remise_absolue = $this->remise_absolute;
    	$facture->remise_percent = $this->remise_percent;
    	$facture->total_ht = $this->total_ht * $factor;
    	$facture->total_tva = $this->total_tva * $factor;
    	$facture->total_ttc = $this->total_ttc * $factor;
    	
    	$facture->note = $this->note;
    	$facture->note_public = $this->note_public;
    	 
    	//! 0=draft,
    	//! 1=to invoice
    	//! 2=invoiced
    	//! 3=No invoicable
    	//! 4=return ticket
    	//! 5=abandoned
    	$facture->statut = $this->statut;
    	//! Fermeture apres paiement partiel: discount_vat, badcustomer, abandon
    	//! Fermeture alors que aucun paiement: replaced (si remplace), abandon
    	$facture->close_code = $this->close_code;
    	//! Commentaire si mis a paye sans paiement complet
    	$facture->close_note = $this->close_note;
    	//! 1 if ticket paid COMPLETELY, 0 otherwise (do not use it anymore, use statut and close_code
    	$facture->paye = 0;
    	
    	/*$facture->mode_reglement_id = $this->mode_reglement_id;			// Id in llx_c_paiement
    	$facture->mode_reglement_code = $this->mode_reglement_code;		// Code in llx_c_paiement*/
    	$facture->mode_reglement_id = $societe->mode_reglement_id;
    	$facture->cond_reglement_id = $societe->cond_reglement_id;
    	$facture->modelpdf = $this->modelpdf;
    	$facture->products = $this->products;	// TODO deprecated
    	$facture->line = $this->line;
    	//! Pour board
    	$facture->nbtodo = $this->nbtodo;
    	$facture->nbtodolate = $this->nbtodolate;
    	$facture->specimen = $this->specimen;
    	
    	for ($i = 0 ; $i < sizeof($this->lines) ; $i++)
    	{
    		$factline = new FactureLigne($db);
    		
    		$factline->fk_parent_line = $this->lines[$i]->fk_parent_line;
    		//! Description ligne
    		$factline->desc = $this->lines[$i]->desc;
    		
    		$factline->fk_product = $this->lines[$i]->fk_product;		// Id of predefined product
    		$factline->product_type = $this->lines[$i]->product_type;	// Type 0 = product, 1 = Service
    		
    		$factline->qty = abs($this->lines[$i]->qty);				// Quantity (example 2)
    		$factline->tva_tx = $this->lines[$i]->tva_tx;			// Taux tva produit/service (example 19.6)
    		$factline->localtax1_tx = $this->lines[$i]->localtax1_tx;		// Local tax 1
    		$factline->localtax2_tx = $this->lines[$i]->localtax2_tx;		// Local tax 2
    		$factline->subprice = $this->lines[$i]->subprice;      	// P.U. HT (example 100)
    		$factline->remise_percent = $this->lines[$i]->remise_percent;	// % de la remise ligne (example 20%)
    		$factline->fk_remise_except = $this->lines[$i]->fk_remise_except;	// Link to line into llx_remise_except
    		$factline->rang = $this->lines[$i]->rang;
    		
    		$factline->info_bits = $this->lines[$i]->info_bits;		// Liste d'options cumulables:
    		// Bit 0:	0 si TVA normal - 1 si TVA NPR
    		// Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)
    		
    		$factline->special_code = $this->lines[$i]->special_code;	// Liste d'options non cumulabels:
    		// 1: frais de port
    		// 2: ecotaxe
    		// 3: ??
    		
    		$factline->origin = $this->lines[$i]->origin;
    		$factline->origin_id = $this->lines[$i]->origin_id;
    		
    		//! Total HT  de la ligne toute quantite et incluant la remise ligne
    		$factline->total_ht = $this->lines[$i]->total_ht * $factor;
    		//! Total TVA  de la ligne toute quantite et incluant la remise ligne
    		$factline->total_tva = $this->lines[$i]->total_tva * $factor;
    		$factline->total_localtax1 = $this->lines[$i]->total_localtax1 * $factor; //Total Local tax 1 de la ligne
    		$factline->total_localtax2 = $this->lines[$i]->total_localtax2 * $factor; //Total Local tax 2 de la ligne
    		//! Total TTC de la ligne toute quantite et incluant la remise ligne
    		$factline->total_ttc = $this->lines[$i]->total_ttc * $factor;
    		
    		$factline->fk_code_ventilation = $this->lines[$i]->fk_code_ventilation;
    		$factline->fk_export_compta = $this->lines[$i]->fk_export_compta;
    		
    		$factline->date_start = $this->lines[$i]->date_start;
    		$factline->date_end = $this->lines[$i]->date_end;
    		
    		// From llx_product
    		$factline->ref = $this->lines[$i]->ref;				// Product ref (deprecated)
    		$factline->product_ref = $this->lines[$i]->product_ref;       // Product ref
    		$factline->libelle = $this->lines[$i]->libelle;      		// Product label (deprecated)
    		$factline->product_label = $this->lines[$i]->product_label;     // Product label
    		$factline->product_desc = $this->lines[$i]->product_desc;  	// Description produit
    		
    		$factline->skip_update_total = $this->lines[$i]->skip_update_total; // Skip update price total for special lines
    		
    		$facture->lines[$i] = $factline;
    	}

    	$facture->create($user);
    	if ($facture->statut==1 || $facture->type==1)
    	{
    		$facture->validate($user);
    	
	    	if($this->diff_payment <= 0){
	    		$facture->set_paid($user);
	    	}
	    	if($this->diff_payment > 0)
	    	{
	    		$facture->setStatut(1);
	    	}
    	}
    	$sql = 'UPDATE '.MAIN_DB_PREFIX."pos_ticket SET fk_facture='".$facture->id."' WHERE rowid=".$this->id;
    		
    	dol_syslog("Ticket::update sql=".$sql);
    	$resql=$this->db->query($sql);
    	if (! $resql)
    	{
    		$this->db->rollback();
    		return -1;
    	}
    	else
    	{
    		$this->db->commit();
    		    		
    		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'pos_facture (fk_cash, fk_place,fk_facture) VALUES ('.$this->fk_cash.','.($this->fk_place ? $this->fk_place: 'null').','.$facture->id.')';
    		 
    		dol_syslog("pos_facture::update sql=".$sql);
    		$resql=$this->db->query($sql);
    		if (! $resql)
    		{
    			$this->db->rollback();
    			return -1;
    		}
    		else
    		{
    			$this->db->commit();
    		}
    		$sql = 'SELECT fk_paiement, amount FROM '.MAIN_DB_PREFIX."pos_paiement_ticket WHERE fk_ticket=".$this->id;
    		$resql=$this->db->query($sql);
    		if ($resql)
    		{
    			$num = $db->num_rows($resql);
    			$i = 0;
    			$totalpaye=0;
    			while ($i < $num)
    			{
    				$objp = $db->fetch_object($resql);
    				$paye[$i]['fk_paiement'] = $objp->fk_paiement;
    				$paye[$i]['amount'] = $objp->amount;
    				$i++;
    			
    			}
    			$i=0;
    			while ($i < $num){
    				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiement_facture (fk_paiement, fk_facture, amount) VALUES ('.$paye[$i]['fk_paiement'].','.$facture->id.','.$paye[$i]['amount'].')';
    				$resql=$this->db->query($sql);
    				$i++;
    			}
    		}
    		else
    		{
    			return -1;
    		}
    		
    		
    		$facture->add_object_linked('ticket',$this->id);
    		 
    		return $facture->id;
    	}
    	
    	
    }

    function getEntrepot() {
        global $db;

        $sql = "SELECT e.rowid FROM ".MAIN_DB_PREFIX."entrepot AS e JOIN llx_pos_cash AS c on c.rowid = ".$this->fk_cash." WHERE e.rowid = c.fk_warehouse LIMIT 1";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $e_id = $db->fetch_object($resql);
            $entrepot = new Entrepot($this->db);
            $entrepot->fetch($e_id->rowid);
            return $entrepot;
        }
    }

    /**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string		$modele			Generator to use. Caller must set it to obj->modelpdf or GETPOST('modelpdf','alpha') for example.
	 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @param   null|array  $moreparams     Array to provide more information
	 *	@return int        					<0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load('pos@pos');

		if (!dol_strlen($modele))
		{
			$modele = 'ticket';
			$thisTypeConfName = 'POS_ADDON_PDF_'.$this->type;

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (!empty($conf->global->$thisTypeConfName)) {
				$modele = $conf->global->$thisTypeConfName;
			} elseif (!empty($conf->global->POS_ADDON_PDF)) {
				$modele = $conf->global->POS_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/pos/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
    }
    
    /**
     * Set ticket freight cost with shipment type and depreciation
     * @param
     * @return int|float    <0 KO, otherwise freight cost
     */
    public static function getFreightCost($service, $distance, $shipping) {
        global $db;

        if ($service > 0) {
            // $obj_serv = array_filter($this->lines, function($line) use($service) { return ($line->fk_product == $service && $line->product_type); });
    
            if (!is_null($shipping)) {
                $sql = "SELECT performance, price_gasoline, depreciation FROM ".MAIN_DB_PREFIX."c_shipment_mode WHERE rowid = $shipping";
                $res = $db->query($sql);
        
                if ($res) {
                    $obj = $db->fetch_object($res);
                    $distance_client = (is_numeric($distance)) ? $distance : 0;
    
                    $freight_cost = ($obj->price_gasoline / $obj->performance) * $distance_client + $obj->depreciation;
                    // $freight_cost = 100 * round($freight_cost / 100);

                    return $freight_cost;
                }

                else return -1;
            }
            else return -2;
        }
        return -3;
    }

    public function unlink_invoice()
    {
        global $user;
        if (!$user->admin && empty($user->rights->pos->unlinkinvoice))
        {
            return -8;
        }
        # Debe haber un ticket cargado
        if (!is_numeric($this->id) && !($this->id > 0))
        {
            return -1;
        }
        
        # Si no hay factura asociada, no debe hacer nada y no hay error.
        if (!is_numeric($this->fk_facture) || !($this->fk_facture > 0))
        {
            return 0;
        }
        //$this->db->begin();
        $facture_id = $this->fk_facture;
        $sql =   'UPDATE llx_pos_ticket '."\r\n"
                .'SET fk_facture = NULL  '."\r\n"
                .'WHERE rowid='.$this->id.' '."\r\n"
                .''
                ;
        if (!$res = $this->db->query($sql))
        {
            //$this->db->rollback();
            return -2;
        }
        $sql =   'SELECT rowid  '."\r\n"
                .'FROM llx_element_element '."\r\n"
                .'WHERE  '."\r\n"
                .'      ( '."\r\n"
                .'          sourcetype=\'ticket\'  '."\r\n"
                .'          AND fk_source='.$this->id.' '."\r\n"
                .'          AND targettype=\'facture\' '."\r\n"
                .'          AND fk_target='.$facture_id.' '."\r\n"
                .'      ) '."\r\n"
                .'   OR '."\r\n"
                .'      ( '."\r\n"
                .'          targettype=\'ticket\'  '."\r\n"
                .'          AND fk_target='.$this->id.' '."\r\n"
                .'          AND sourcetype=\'facture\' '."\r\n"
                .'          AND fk_source='.$facture_id.' '."\r\n"
                .'      ) '."\r\n"
                .''
                ;
        if (!$res = $this->db->query($sql))
        {
            //$this->db->rollback();
            return -3;
        }
        if (!($this->db->num_rows($res) > 0))
        {
            $sql =   'INSERT INTO llx_element_element '."\r\n"
                    .'(sourcetype,fk_source,targettype,fk_target)  '."\r\n"
                    .'VALUES  '."\r\n"
                    .'(\'ticket\','.$this->id.',\'facture\','.$facture_id.')'
                    ;
            if (!$res = $this->db->query($sql))
            {
                //$this->db->rollback();
                return -4;
            }
        }
        $sql =   'SELECT    p.rowid '."\r\n"
                .'FROM '.MAIN_DB_PREFIX.'paiement as p,  '."\r\n"
                .''.MAIN_DB_PREFIX.'pos_paiement_ticket as pf '."\r\n"
                .'WHERE pf.fk_ticket = '.$this->id.' '."\r\n"
                .'  AND pf.fk_paiement = p.rowid '."\r\n"
                ;

        if (!$res = $this->db->query($sql))
        {
            //$this->db->rollback();
            return -5;
        }
        $rows = array();
        while ($row = $this->db->fetch_object($res))
        {
            $rows[] = $row->rowid;
        }
        if (count($rows))
        {
            $sql =   'DELETE FROM llx_paiement_facture '."\r\n"
                    .'WHERE fk_facture = '.$facture_id.' '."\r\n"
                    .'AND fk_paiement IN ('.implode(',',$rows).')'
                    ;
        }
        if (!$res = $this->db->query($sql))
        {
            //$this->db->rollback();
            return -6;
        }

        //require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
        //$act = new actionComm($this->db);


        $sql =   'UPDATE llx_facture '."\r\n"
                .'SET    fk_statut = 3 '."\r\n"
                .'      ,close_code=\'abandon\' '."\r\n"
                .'      ,paye=0'."\r\n"
                .'WHERE rowid='.$facture_id.' '."\r\n"
                .''
                ;

        if (!$res = $this->db->query($sql))
        {
            $this->db->rollback();
            //dol_print_error($this->db);
            //die ();
            return -7;
        }

        //$this->db->commit();
        return $facture_id;
    }
}



/**
 *	\class      	TicketLigne
 *	\brief      	Classe permettant la gestion des lignes de tickets
 *	\remarks		Gere des lignes de la table llx_ticketdet
 */
class TicketLigne extends CommonObject
{
    var $db;
    var $error;

    //! From llx_ticketdet
    var $rowid;
    //! Id ticket
    var $fk_ticket;
    //! Id parent line
    var $fk_parent_line;
    //! Description ligne
    var $desc;

    var $fk_product;		// Id of predefined product
    var $product_type = 0;	// Type 0 = product, 1 = Service

    var $qty;				// Quantity (example 2)
    var $tva_tx;			// Taux tva produit/service (example 19.6)
    var $localtax1_tx;		// Local tax 1
    var $localtax2_tx;		// Local tax 2
    var $subprice;      	// P.U. HT (example 100)
    var $remise_percent;	// % de la remise ligne (example 20%)
    var $fk_remise_except;	// Link to line into llx_remise_except
    var $rang = 0;

    var $info_bits = 0;		// Liste d'options cumulables:
    // Bit 0:	0 si TVA normal - 1 si TVA NPR
    // Bit 1:	0 si ligne normal - 1 si bit discount (link to line into llx_remise_except)

    var $special_code;	// Liste d'options non cumulabels:
    // 1: frais de port
    // 2: ecotaxe
    // 3: ??

    var $origin;
    var $origin_id;

    //! Total HT  de la ligne toute quantite et incluant la remise ligne
    var $total_ht;
    //! Total TVA  de la ligne toute quantite et incluant la remise ligne
    var $total_tva;
    var $total_localtax1; //Total Local tax 1 de la ligne
    var $total_localtax2; //Total Local tax 2 de la ligne
    //! Total TTC de la ligne toute quantite et incluant la remise ligne
    var $total_ttc;

    var $fk_code_ventilation = 0;
    var $fk_export_compta = 0;

    var $date_start;
    var $date_end;
    
    var $note;

    // Ne plus utiliser
    var $price;         	// P.U. HT apres remise % de ligne (exemple 80)
    var $remise;			// Montant calcule de la remise % sur PU HT (exemple 20)

    // From llx_product
    var $ref;				// Product ref (deprecated)
    var $product_ref;       // Product ref
    var $libelle;      		// Product label (deprecated)
    var $product_label;     // Product label
    var $product_desc;  	// Description produit
    
    var $skip_update_total; // Skip update price total for special lines
    
    var $ls_warehouse_status = null;
    var $ls_warehouse_status_by = null;
    var $ls_warehouse_status_date = null;
    
    var $qty_ent= null;

    //Precio de compra de producto
    var $pa_ht;

    /**
     *  \brief     Constructeur d'objets ligne de ticket
     *  \param     DB      handler d'acces base de donnee
     */
    function TicketLigne($DB)
    {
        $this->db= $DB ;
    }

    /**
     *	\brief     Recupere l'objet ligne de ticket
     *	\param     rowid           id de la ligne de ticket
     */
    function fetch($rowid)
    {
        $sql = 'SELECT fd.rowid, fd.fk_ticket, fd.fk_parent_line, fd.fk_product, fd.product_type, fd.description, fd.price, fd.qty, fd.tva_tx,';
        $sql.= ' fd.localtax1_tx, fd. localtax2_tx, fd.remise, fd.remise_percent, fd.fk_remise_except, fd.subprice,';
        $sql.= ' fd.date_start as date_start, fd.date_end as date_end,';
        $sql.= ' fd.info_bits, fd.total_ht, fd.total_tva, fd.total_ttc, fd.rang,';
        //$sql.= ' fd.fk_code_ventilation, fd.fk_export_compta,';
        $sql.= ' fd.fk_code_ventilation, fd.fk_export_compta, fd.buy_price_ht,';
        $sql.= ' p.ref as product_ref, p.label as product_libelle, p.description as product_desc, fd.note as note,';
        $sql.= ' fd.ls_warehouse_status,fd.ls_warehouse_status_by, fd.ls_warehouse_status_date, fd.qty_ent ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticketdet as fd';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON fd.fk_product = p.rowid';
        $sql.= ' WHERE fd.rowid = '.$rowid;
        
        $result = $this->db->query($sql);
        if ($result)
        {
            $objp = $this->db->fetch_object($result);
            
            $this->rowid				= $objp->rowid;
            $this->fk_ticket			= $objp->fk_ticket;
            $this->fk_parent_line		= $objp->fk_parent_line;
            $this->desc					= $objp->description;
            $this->product_label		= $objp->description;
            $this->qty					= $objp->qty;
            $this->subprice				= $objp->subprice;
            $this->tva_tx				= $objp->tva_tx;
            $this->localtax1_tx			= $objp->localtax1_tx;
            $this->localtax2_tx			= $objp->localtax2_tx;
            $this->remise_percent		= $objp->remise_percent;
            $this->fk_remise_except		= $objp->fk_remise_except;
            $this->fk_product			= $objp->fk_product;
            $this->product_type			= $objp->product_type;
            $this->date_start			= $this->db->jdate($objp->date_start);
            $this->date_end				= $this->db->jdate($objp->date_end);
            $this->info_bits			= $objp->info_bits;
            $this->total_ht				= $objp->total_ht;
            $this->total_tva			= $objp->total_tva;
            $this->total_localtax1		= $objp->total_localtax1;
            $this->total_localtax2		= $objp->total_localtax2;
            $this->total_ttc			= $objp->total_ttc;
            $this->fk_code_ventilation	= $objp->fk_code_ventilation;
            $this->fk_export_compta		= $objp->fk_export_compta;
            $this->rang					= $objp->rang;
            $this->note					= $objp->note;

            // Ne plus utiliser
            $this->price				= $objp->price;
            $this->remise				= $objp->remise;

            $this->ref					= $objp->product_ref;      // deprecated
            $this->product_ref			= $objp->product_ref;
            $this->libelle				= $objp->product_libelle;  // deprecated
            $this->product_label		= $objp->product_libelle;
            $this->product_desc			= $objp->product_desc;
            $this->ls_warehouse_status	= $objp->ls_warehouse_status;
            $this->ls_warehouse_status_by	= $objp->ls_warehouse_status_by;
            $this->ls_warehouse_status_date	= $objp->ls_warehouse_status_date;
            $this->qty_ent				= is_null($objp->qty_ent)? '0': $objp->qty_ent;
            //precio compra producto
			$this->pa_ht				= $objp->buy_price_ht;

            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *	\brief     	Insert line in database
     *	\param      notrigger		1 no triggers
     *	\return		int				<0 if KO, >0 if OK
     */
    function insert($notrigger=0)
    {
        global $langs,$user,$conf;

        dol_syslog("TicketLigne::Insert rang=".$this->rang, LOG_DEBUG);

        // Clean parameters
        $this->desc=trim($this->desc);
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->remise)) $this->remise=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->subprice)) $this->subprice=0;
        if (empty($this->price))    $this->price=0;
        if (empty($this->pa_ht)) $this->pa_ht = 0;
       
        //if (empty($this->special_code)) $this->special_code=0;
        //if (empty($this->fk_parent_line)) $this->fk_parent_line=0;

        // Check parameters
        if ($this->product_type < 0) return -1;

        $this->db->begin();

        // Insertion dans base de la ligne
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'pos_ticketdet';
        $sql.= ' (fk_ticket, fk_parent_line, description, qty, tva_tx, localtax1_tx, localtax2_tx,';
        $sql.= ' fk_product, product_type, remise_percent, subprice, price, remise, fk_remise_except,';
        $sql.= ' date_start, date_end, fk_code_ventilation, fk_export_compta, ';
        $sql.= ' rang,';
        $sql.= ' info_bits, total_ht, total_tva, total_ttc, total_localtax1, total_localtax2, note'
			.  ', ls_warehouse_status, ls_warehouse_status_by, ls_warehouse_status_date,qty_ent,ls_stock_dec_date,ls_stock_dec_qty,buy_price_ht)';  //buy_price_ht
        $sql.= " VALUES (".$this->fk_ticket.",";
        $sql.= " ".($this->fk_parent_line>0?"'".$this->fk_parent_line."'":"null").",";
        $sql.= " '".$this->db->escape($this->desc)."',";
        $sql.= " ".price2num($this->qty).",";
        $sql.= " ".price2num($this->tva_tx).",";
        $sql.= " ".price2num($this->localtax1_tx).",";
        $sql.= " ".price2num($this->localtax2_tx).",";
        if ($this->fk_product) { $sql.= $this->fk_product.","; }
        else { $sql.='null,'; }
        $sql.= " ".$this->product_type.",";
        $sql.= " ".price2num($this->remise_percent).",";
        $sql.= " ".price2num($this->subprice).",";
        $sql.= " ".price2num($this->price).",";
        $sql.= " ".($this->remise?price2num($this->remise):'0').",";	// Deprecated
        if ($this->fk_remise_except) $sql.= $this->fk_remise_except.",";
        else $sql.= 'null,';
        if ($this->date_start) { $sql.= "'".$this->db->idate($this->date_start)."',"; }
        else { $sql.='null,'; }
        if ($this->date_end)   { $sql.= "'".$this->db->idate($this->date_end)."',"; }
        else { $sql.='null,'; }
        $sql.= ' '.$this->fk_code_ventilation.',';
        $sql.= ' '.$this->fk_export_compta.',';
        $sql.= ' '.$this->rang.',';
        //$sql.= ' '.$this->special_code.',';
        $sql.= " '".$this->info_bits."',";
        $sql.= " ".price2num($this->total_ht).",";
		$sql.= " ".price2num($this->total_tva).",";
		$sql.= " ".price2num($this->total_ttc).",";
        $sql.= " ".price2num($this->total_localtax1).",";
        $sql.= " ".price2num($this->total_localtax2).",";
        $sql.= ($this->note?"'".$this->db->escape($this->note)."'":"null").', ';
        $sql.= ' '.((is_null($this->ls_warehouse_status))?'NULL':$this->ls_warehouse_status).', ';
        $sql.= ' '.((is_null($this->ls_warehouse_status_by))?'NULL':$this->ls_warehouse_status_by).', ';
        $sql.= ' '.((is_null($this->ls_warehouse_status_date))?'NULL':'\''.$this->ls_warehouse_status_date.'\'').', ';
        $sql.= ' '.((is_null($this->qty_ent))?'NULL':'\''.floatval($this->qty_ent).'\'').', ';
        $sql.= ' '.((is_null($this->ls_stock_dec_date))?'NULL':'\''.$this->ls_stock_dec_date.'\'').', ';
        //$sql.= ' '.((is_null($this->ls_stock_dec_qty))?'NULL':'\''.$this->ls_stock_dec_qty.'\'').'';
        $sql.= ' '.((is_null($this->ls_stock_dec_qty))?'NULL':'\''.$this->ls_stock_dec_qty.'\'').',';
        $sql.= ' '.price2num($this->pa_ht);
        
        
        
        $sql.= ')';

        dol_syslog("TicketLigne::insert sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'pos_ticketdet');
            // Si fk_remise_except defini, on lie la remise a la ticket
            // ce qui la flague comme "consommee".
            if ($this->fk_remise_except)
            {
                $discount=new DiscountAbsolute($this->db);
                $result=$discount->fetch($this->fk_remise_except);
                if ($result >= 0)
                {
                    // Check if discount was found
                    if ($result > 0)
                    {
                        // Check if discount not already affected to another ticket
                        if ($discount->fk_ticket)
                        {
                            $this->error=$langs->trans("ErrorDiscountAlreadyUsed",$discount->id);
                            dol_syslog("TicketLigne::insert Error ".$this->error, LOG_ERR);
                            $this->db->rollback();
                            return -3;
                        }
                        else
                        {
                            $result=$discount->link_to_ticket($this->fk_ticket,0);
                            $this->db->rollback();
                            //die(var_dump($this->fk_ticket,$result));
                            if ($result < 0)
                            {
                                $this->error=$discount->error;
                                dol_syslog("TicketLigne::insert Error ".$this->error, LOG_ERR);
                                $this->db->rollback();
                                return -3;
                            }
                        }
                    }
                    else
                    {
                        $this->error=$langs->trans("ErrorADiscountThatHasBeenRemovedIsIncluded");
                        dol_syslog("TicketLigne::insert Error ".$this->error, LOG_ERR);
                        $this->db->rollback();
                        return -3;
                    }
                }
                else
                {
                    $this->error=$discount->error;
                    dol_syslog("TicketLigne::insert Error ".$this->error, LOG_ERR);
                    $this->db->rollback();
                    return -3;
                }
            }

            if (! $notrigger)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result = $interface->run_triggers('LINEBILL_INSERT',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
            }

            $this->db->commit();
            return $this->rowid;

        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("TicketLigne::insert Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *  	Update line into database
     *		@return		int		<0 if KO, >0 if OK
     */
    function update()
    {
        global $user,$langs,$conf;

        // Clean parameters
        $this->desc=trim($this->desc);
		if (empty($this->tva_tx)) $this->tva_tx=0;
		if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
		if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
		if (empty($this->total_localtax1)) $this->total_localtax1=0;
		if (empty($this->total_localtax2)) $this->total_localtax2=0;
		if (empty($this->remise)) $this->remise=0;
		if (empty($this->remise_percent)) $this->remise_percent=0;
		if (empty($this->info_bits)) $this->info_bits=0;
		if (empty($this->product_type)) $this->product_type=0;
		if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (empty($this->pa_ht)) $this->pa_ht = 0;

        // Check parameters
        if ($this->product_type < 0) return -1;

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."pos_ticketdet SET";
        $sql.= " description='".$this->db->escape($this->desc)."'";
        $sql.= ",subprice=".price2num($this->subprice)."";
        $sql.= ",price=".price2num($this->price)."";
        $sql.= ",remise=".price2num($this->remise)."";
        $sql.= ",remise_percent=".price2num($this->remise_percent)."";
        if ($this->fk_remise_except) $sql.= ",fk_remise_except=".$this->fk_remise_except;
        else $sql.= ",fk_remise_except=null";
        $sql.= ",tva_tx=".price2num($this->tva_tx)."";
        $sql.= ",localtax1_tx=".price2num($this->localtax1_tx)."";
        $sql.= ",localtax2_tx=".price2num($this->localtax2_tx)."";
        $sql.= ",qty=".price2num($this->qty)."";
        $sql.= ",qty_ent=".price2num($this->qty_ent)."";
        if ($this->date_start) { $sql.= ",date_start='".$this->db->idate($this->date_start)."'"; }
        else { $sql.=',date_start=null'; }
        if ($this->date_end) { $sql.= ",date_end='".$this->db->idate($this->date_end)."'"; }
        else { $sql.=',date_end=null'; }
        $sql.= ",product_type=".$this->product_type;
        $sql.= ",rang='".$this->rang."'";
        $sql.= ",info_bits='".$this->info_bits."'";
        if (empty($this->skip_update_total))
        {
        	$sql.= ",total_ht=".price2num($this->total_ht)."";
        	$sql.= ",total_tva=".price2num($this->total_tva)."";
        	$sql.= ",total_ttc=".price2num($this->total_ttc)."";
        }
        $sql.= ",total_localtax1=".price2num($this->total_localtax1)."";
        $sql.= ",total_localtax2=".price2num($this->total_localtax2)."";
        $sql.= ",fk_parent_line=".($this->fk_parent_line>0?$this->fk_parent_line:"null");
        $sql.= ",note=".(!empty($this->note)?"'".$this->note."'":"null");
        $sql .= ", buy_price_ht=".price2num($this->pa_ht);
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("TicketLigne::update sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if (! $notrigger)
            {
                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result = $interface->run_triggers('LINEBILL_UPDATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
            }
            $this->db->commit();
            return 1;
        }
        else
        {
        	dol_print_error($this->db);
            $this->error=$this->db->error();
            dol_syslog("TicketLigne::update Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }
    
	/**
	 * 	Delete line in database
	 *	@return	 int  <0 si ko, >0 si ok
	 */
	function delete()
	{
		global $conf,$langs,$user;
		
		$this->db->begin();
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."pos_ticketdet WHERE rowid = ".$this->rowid;
		dol_syslog("TicketLigne::delete sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result = $interface->run_triggers('LINEBILL_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers
			
			$this->db->commit();
			
			return 1;
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog("TicketLigne::delete Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

    /**
     *      \brief     	Mise a jour en base des champs total_xxx de ligne de ticket
     *		\return		int		<0 si ko, >0 si ok
     */
    function update_total()
    {
        $this->db->begin();
        dol_syslog("TicketLigne::update_total", LOG_DEBUG);
        
        // Clean parameters
		if (empty($this->total_localtax1)) $this->total_localtax1=0;
		if (empty($this->total_localtax2)) $this->total_localtax2=0;

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."pos_ticketdet SET";
        $sql.= " total_ht=".price2num($this->total_ht)."";
        $sql.= ",total_tva=".price2num($this->total_tva)."";
        $sql.= ",total_localtax1=".price2num($this->total_localtax1)."";
        $sql.= ",total_localtax2=".price2num($this->total_localtax2)."";
        $sql.= ",total_ttc=".price2num($this->total_ttc)."";
        $sql.= " WHERE rowid = ".$this->rowid;
        
        dol_syslog("PropaleLigne::update_total sql=".$sql, LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("TicketLigne::update_total Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }


    function reset_stock_register($stk)
    {
        $this->db->begin();
        dol_syslog("TicketLigne::reset_stock_register", LOG_DEBUG);
        
        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."pos_ticketdet SET";
        $sql.= " ls_stock_dec_qty=0"." ";
        $sql.= ",ls_stock_dec_date='".date('Y-m-d H:i:s')."' ";
        $sql.= " WHERE rowid = ".$this->rowid;
        
        dol_syslog("TicketLigne::reset_stock_register sql=".$sql, LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog("TicketLigne::reset_stock_register Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }
    
    /**
     *  Return label of object status
     *  @param      mode            0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
     *  @return     string          Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }
    
    /**
     *    	\brief      Renvoi le libelle d'un statut donne
     *    	\param      paye          	Etat paye
     *    	\param      statut        	Id statut
     *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *		\param		alreadypaid	    Montant deja paye
     *		\param		type			Type facture
     *    	\return     string        	Libelle du statut
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;
        $langs->load('@pos');

        if ($mode == 0)
        {
            $prefix='';

			if ($statut == 0) return $langs->trans('Ticket'.$prefix.'StatusDraft');
			if ($statut == 1) return $langs->trans('Ticket'.$prefix.'StatusToBill');
			if ($statut == 2) return $langs->trans('Ticket'.$prefix.'StatusBilled');
			if ($statut == 3) return $langs->trans('Ticket'.$prefix.'StatusToProcessShort');
        }
    }
    
    /**
     * 
     * 		\param		st				Entero correspondiente al Estado V. (detalle abajo)
     * 									0	= En Almacén (default)	Color: #C0C0C0
     * 									1	= Por Revisar			Color: #929292
     * 									2	= Reportado				Color: #EE220C
     * 									3	= Revisado				Color: #B51700
     * 									4	= Por Surtir			Color: #0076BA
     * 									5	= Entregado				Color: #000000
     * 									6	= Surtido				Color: #1DB100
     * 									7	= Apartados				Color: #FF9300
     * 									8	= Devolución			Color: #F8BA00
     * 									9	= Retorno				Color: #F8BA00
     * 									10	= Apartados Remotos		Color: #FFFF66
     * 									11	= Por Apartar			Color: #B342F5
     * 									12	= N/A					Color: #A3B8F9
     * 
     */
	function updateEstadoV($st,$ct=null)
    {
    	global $user;
    	dol_syslog("TicketLigne::updateEstadoV st=".$st);
		if (!is_numeric($st))
		{
			$st='NULL';
    	}
		$now =	gmdate('Y-m-d H:i:s');
		$sql =	 'UPDATE llx_pos_ticketdet'."\r\n"
				.'SET ls_warehouse_status = '.$st."\r\n"
				.'   ,ls_warehouse_status_by='.$user->id."\r\n"
				.'   ,ls_warehouse_status_date=\''.$now.'\''."\r\n"
				;
		if (!is_null($ct))
		{
			$sql .=	 ',qty_ent = IF (qty_ent IS NULL, '.$ct.', IF (qty_ent + '.$ct.'>qty,qty,qty_ent + '.$ct.'))'."\r\n";
		}
		$sql .=	'WHERE rowid='.$this->rowid."\r\n"
   				;
		if ($this->db->query($sql))
		{
			return date('Y-m-d H:i:s',strtotime($now)+(date('Z')));
		}
		else
		{
			return -1;
		}
    }
    
    
}

?>