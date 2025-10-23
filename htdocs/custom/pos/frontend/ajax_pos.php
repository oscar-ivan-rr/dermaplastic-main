<?php
/* Copyright (C) 2011 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011 Jorge Donet
 * Copyright (C) 2012 Ferran Marcet           <fmarcet@2byte.es> 
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
 *	\file       htdocs/pos/ajax_pos.php
 *	\ingroup    ticket
 *	\brief      Tickets home page
 *	\version    $Id: ajax_pos.php,v 1.2 2011-06-30 11:00:41 jdonet Exp $
*/
$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';//checkpoint 318
dol_include_once('/pos/class/pos.class.php');

//if (!$user->rights->pos->lire) accessforbidden();
$data = file_get_contents('php://input');
$data = json_decode($data, true);
$langs->load("pos@pos");
$html = '';
$action = GETPOST('action');
$category = GETPOST('category');
$ticketstate = GETPOST('ticketstate');
//$parentcategory = GETPOST('parentcategory');
$product_id = GETPOST('product');

if(empty($_SESSION["TERMINAL_ID"])){
	$fm["data"]=0;
	$fm["error"]["desc"] = $langs->trans("ErrSession");
	$fm["error"]["value"] = 99;
	echo json_encode($fm);
}

else if($action=='getProducts')
{
		$products = POS::getProductsbyCategory($category,0, $ticketstate);
		echo json_encode($products);
}
else if($action=='getMoreProducts')
{
	$pag = intval(GETPOST('pag','int'));
	$categories = POS::getProductsbyCategory($category,$pag, $ticketstate);
	echo json_encode($categories);
}
else if($action=='getCategories')
{
	//$parentcategory = intval($data['data']);
	$parentcategory = intval(GETPOST('parentcategory','int'));
	$categories = POS::getCategories($parentcategory);
	echo json_encode($categories);	
}
elseif($action=='newTicket')
{
		//$html.=	POS::CreateTicket();
		//$jorge = $html;
}
elseif($action=='getProduct')
{
	if(isset($data['data']))
	{
		$product_id = intval($data['data']['product']);
		$customer_id = intval($data['data']['customer']);
		$product = POS::getProductbyId($product_id, $customer_id);
		
		echo json_encode($product);
	}
}
elseif($action=='getTicket')
{
	if(sizeof($data))
	{
		$ticketId = $data['data'];
		$ticket = POS::getTicket($ticketId);
		
		if ($ticket['data']{'data'}['customerId']>0)
		{
			$prods = array();
			foreach($ticket['data']['data']['lines'] as $line)
			{
				$prods[$line['idProduct']] = POS::getProductbyId($line['idProduct'], $ticket['data']['customerId']);
			}												
			$ticket['data']['data']['rc_products']['customerId'] = $ticket['data']['data']['customerId'];
			$ticket['data']['data']['rc_products']['ticketId'] = $ticketId;
			$ticket['data']['data']['rc_products']['products'] = $prods;
		}								
		
		
								
		echo json_encode($ticket);
	}
}
elseif($action=='getFacture')
{
	if(sizeof($data))
	{
		$ticketId = $data['data'];
		$ticket = POS::getFacture($ticketId);
		echo json_encode($ticket);
	}
}
elseif($action=='getHistory')
{
    $searchValue = '';
    if(sizeof($data))
    {
        $searchValue = $data['data']['search'];
        $stat = $data['data']['stat'];
    }
    $history = POS::getHistoric($searchValue,$stat);
    echo json_encode($history);
}
elseif($action=='getHistoryByTerminal')
{
    $searchValue = '';
    if(sizeof($data))
    {
        $searchValue = $data['data']['search'];
        $stat = $data['data']['stat'];
    }
    $history = POS::getHistoric(null,$stat,$searchValue);
    echo json_encode($history);
}
elseif($action=='getHistoryByClient')
{
    $searchValue = '';
    if(sizeof($data))
    {
        $searchValue = $data['data']['search'];
        $stat = $data['data']['stat'];
    }
    $history = POS::getHistoric(null,$stat,null,'',$searchValue);
    echo json_encode($history);
}
elseif($action=='getHistoryByUser')
{
    $searchValue = '';
    if(sizeof($data))
    {
        $searchValue = $data['data']['search'];
        $stat = $data['data']['stat'];
    }
    $history = POS::getHistoricUser($searchValue,$stat);
    echo json_encode($history);
}
elseif($action=='getHistoryFac')
{
	$searchValue = '';
	if(sizeof($data))
	{
		$searchValue = $data['data']['search'];
		$stat = $data['data']['stat'];
	}
	$history = POS::getHistoricFac($searchValue,$stat);
	echo json_encode($history);
}
elseif($action=='countHistory')
{
	$history = POS::countHistoric();
	echo json_encode($history);
}
elseif($action=='countHistoryFac')
{
	$history = POS::countHistoricFac();
	echo json_encode($history);
}
elseif($action=='getParking')
{
	$history = POS::getHistoric();
	echo json_encode($history);
}
elseif($action=='saveTicket')
{
	$result = POS::SetTicket($data);
	echo json_encode($result);
}
elseif($action=='searchProducts')
{
	if(sizeof($data))
	{
		$searchValue = $data['data']['search'];
		$warehouse = $data['data']['warehouse'];
		$ticketstate = $data['data']['ticketstate'];
		$customerId = $data['data']['customer'];
		$result = POS::SearchProduct($searchValue, true, $warehouse,1, $ticketstate, $customerId);
		echo json_encode($result);
		
	}
}
elseif($action=='countProduct')
{
	$warehouseId = $data['data'];
	$stock = POS::countProduct($warehouseId);
	echo json_encode($stock);
}
elseif($action=='searchStocks')
{
	if(sizeof($data))
	{
		$searchValue = $data['data']['search'];
		$mode = $data['data']['mode'];
		$warehouse = $data['data']['warehouse'];
		$ticketstate = 0;
		$customerId = 0;
		$result = POS::SearchProduct($searchValue,true,$warehouse,$mode, $ticketstate, $customerId);
		echo json_encode($result);
		
	}
}
elseif($action=='getNumberofSustitute')
{
    if(sizeof($data))
    {
        $idprod = $data['data'];
        $result = POS::SearchNumberOfSustitute($idprod);
        echo json_encode($result);
    }
}
elseif($action=='getNumberofComplement')
{
    if(sizeof($data))
    {
        $idprod = $data['data'];
        $result = POS::SearchNumberOfComplements($idprod);
        echo json_encode($result);
    }
}
elseif($action=='getAllSustites')
{
    if(sizeof($data))
    {
        $idprod = $data['data'];
        $result = POS::getAllSustitutes($idprod);
        echo json_encode($result);
    }
}
elseif($action=='getAllComplements')
{
    if(sizeof($data))
    {
        $idprod = $data['data'];
        $result = POS::getAllComplements($idprod);
        echo json_encode($result);
    }
}
elseif($action=='searchCustomer')
{
	if(sizeof($data))
	{
		$searchValue = $data['data'];
		$result = POS::SearchCustomer($searchValue,false);
		echo json_encode($result);

	}
}
elseif($action=='getAllCustomers')
{
    $result = POS::getAllCustomers(false);
    echo json_encode($result);
}
elseif($action=='addCustomer')
{
	if(sizeof($data))
	{
		$customer = $data['data'];
		$customer['idprof1'] = preg_replace('/[^\da-z]/i','',$customer['idprof1']);
		$rfc_ptrn = '/^(([ÑA-Z|&]{3}|[A-Z]{4})\d{2}((0[1-9]|1[012])(0[1-9]|1\d|2[0-8])|(0[13456789]|1[012])(29|30)|(0[13578]|1[02])31)((\w{2})([A|0-9]{1})){0,1})$|^(([ÑA-Z|&]{3}|[A-Z]{4})([02468][048]|[13579][26])0229)((\w{2})([A|0-9]{1})){0,1}$/i';
		if (strlen($customer['idprof1']) < 9 || !preg_match($rfc_ptrn,$customer['idprof1']))
		{
			$result = array('error'=>array('value'=>'500','desc'=>'El RFC no es válido.'));
		}
		else
		{
			$sql = 'SELECT `nom` FROM `llx_societe` WHERE `siren`=\''.$db->escape($customer['idprof1']).'\' LIMIT 0,1';
			if (!$dbres = $db->query($sql))
			{
				$result = array('error'=>array('value'=>'500','desc'=>'Error '.___LINE__.'.'));
			}
			elseif ($db->num_rows($dbres) > 0)
			{
				$clRow = $db->fetch_object($dbres);
				$result = array('error'=>array('value'=>'500','desc'=>'El cliente '.$clRow->nom.' (RFC: '.$customer['idprof1'].') ya está dado de alta.'));
			}
			else
			{
				$result = POS::SetCustomer($customer,false);
			}
		}
		echo json_encode($result);
		
	}
}
elseif($action=='addNewProduct')
{
	if(sizeof($data))
	{
		$product = $data['data'];
		$result = POS::SetProduct($product,false);
		echo json_encode($result);
		
	}
}
elseif($action=='getMoneyCash')
{
	$result = POS::getMoneyCash();
	echo json_encode($result);
}
elseif($action=='getConfig')
{
	$result = POS::getConfig();
	echo json_encode($result);
}
elseif($action=='closeCash')
{
	if(sizeof($data))
	{
		$cash = $data['data'];
		$result = POS::setControlCash($cash);
		echo json_encode($result);
		
	}
}
elseif($action=='getPlaces')
{
	$places = POS::getPlaces();
	echo json_encode($places);
	
}
elseif($action=='SendMail')
{
	$email = $data['data'];
	$result = POS::sendMail($email);
	echo json_encode($result);

}
elseif($action=='filePDF')
{
	$ticket= new Ticket($db);
	$ticket->fetch($data['data']);
	$ticket->generateDocument("azur",'',0,0,0,null);
	$x = str_replace('htdocs','',DOL_MAIN_URL_ROOT).'documents/'.$ticket->last_main_doc;
	$result = array('url'=>$x);
	echo json_encode($result);

}
elseif($action=='deleteTicket')
{
	$idticket = $data['data'];
	$result = POS::Delete_Ticket($idticket);
	echo json_encode($result);

}
elseif($action=='Translate')
{
	if(sizeof($data))
	{
		echo json_encode($langs->trans($data['data']));
	}
}
elseif($action=='calculePrice')
{
	if(sizeof($data))
	{
		$product = $data['data'];
		$result = POS::calculePrice($product);
		echo json_encode($result);
	}
}
elseif ($action=='asignarLabel') {
    $qty = $data['data']['cant'];
    $label = $data['data']['label'];
    $result = POS::asignarLabel($qty,$label);
    echo json_encode($result);
}
elseif($action=='getLocalTax')
{
	if(sizeof($data))
	{
		$data = $data['data'];
		$result = POS::getLocalTax($data);
		echo json_encode($result);
	}
}
elseif($action=='getNotes')
{
	$mode = $data['data'];
	$result = POS::getNotes($mode);
	echo json_encode($result);
}
elseif($action=='getWarehouse')
{
	$result = POS::getWarehouse();
	echo json_encode($result);
}
elseif($action=='checkPassword')
{
	$pass = $data['data']['pass'];
	$login = $data['data']['login'];
	$result = POS::checkPassword($login, $pass);
	echo json_encode($result);
}
elseif($action=='searchCoupon')
{
	$customerId = $data['data']['customer'];
	$amount = $data['data']['amount'];
	$result = POS::searchCoupon($customerId);
	echo json_encode($result);
}
elseif($action=='addPrint')
{
	$addprint = $data['data'];
	$result = POS::addPrint($addprint);
	echo json_encode($result);
}
elseif($action=='askToWarehouse')
{
	$warehouseId= $data['data']['warehouse'];
	$ticketId	= $data['data']['ticket'];
	$products	= $data['data']['lines'];
	$usr		= POS::setEntrepotUserToTicket($ticketId,$warehouseId);
	$return		= array('data'=>'','error'=>array());
	if (intval($data['data']['ticket_status']) === 0)
	{
		$status		=  1;
	}
	else
	{
		$status		= null;
	}
	
	switch($usr)
	{
		case -1:
			$return['error']=array('value'=>$usr,'desc'=>'El identificador de ticket \''.$ticketId.'\' no es válido.');
			break;
		case -2:
			$return['error']=array('value'=>$usr,'desc'=>'Error al consultar la base de datos: '.$db->db->error);
			break;
		case -3:
			$return['error']=array('value'=>$usr,'desc'=>'No se pudo obtener el almacén relacionado a la terminal');
			break;
		case -4:
			$return['error']=array('value'=>$usr,'desc'=>'Error al consultar la base de datos: '.$db->db->error);
			break;
		case -5:
			$return['error']=array('value'=>$usr,'desc'=>'No hay usuarios, del grupo Almacén, asignados al almacén de la terminal');
			break;
		case $usr < -9999990000000000:
			$user_id = abs($usr + 9999990000000000);
			$static_user = new User($db);
			$static_user->fetch($user_id);
			if (POS::setWarehouseStauts($ticketId,$warehouseId,$products,$status,true))
			{
				$return['error']=array('value'=>0,'desc'=>'La venta fue solicitada y está asignada a "'.$static_user->firstname.' '.$static_user->lastname.'" (ID: '.$user_id.' / Login: '.$static_user->login.')');
				$return['data'] = $user_id;
			}
			else
			{	
				# NO con mayuscula para diferenciarlo del mensaje del siguiente case.
				$return['error']=array('value'=>0,'desc'=>'La venta NO pudo ser solicitada.');
				$return['data']=$usr;
			}
			
			break;
		case $usr>0:
			$static_user = new User($db);
			$static_user->fetch($usr);
			if (POS::setWarehouseStauts($ticketId,$warehouseId,$products,$status))
			{
				$return['error']=array('value'=>0,'desc'=>'La venta fue solicitada y asignada a "'.$static_user->firstname.' '.$static_user->lastname.'" (ID: '.$usr.' / Login: '.$static_user->login.')');
				$return['data']=$usr;
			}
			else
			{
				$return['error']=array('value'=>0,'desc'=>'La venta no pudo ser solicitada.');
				$return['data']=$usr;
			}
			
			break;
		default:
			$return['error']=array('value'=>500,'desc'=>'Error no identificado, por favor consulte al Soporte Técnico.');
	}
	echo json_encode($return);
}
elseif($action=='setToApartado')
{
	$warehouseId= $data['data']['warehouse'];
	$ticketId	= $data['data']['ticket'];
	$products	= $data['data']['lines'];
	$oldTicket	= new Ticket($db);
	$oldTicket->fetch($ticketId);
	if(POS::setWarehouseStauts($ticketId,$warehouseId,$products,11))
	{
		$ticket	= new Ticket($db);
		$ticket->fetch($ticketId);		
		if (POS::quitStock($ticket,$oldTicket))
		{
			$return['error']=array('value'=>500,'desc'=>'Error en movimiento de inventario.');
		}
		else
		{
			$return['error']=array('value'=>0,'desc'=>'Apartado correctamente.');
		}
		if ($ticket->statut == '1')
		{
			$ticket->statut = '2';
			$ticket->update($user->id);
		}
	}
	else
	{
		$return['error']=array('value'=>500,'desc'=>'Error no identificado. Por favor consulte al Soporte Técnico.');
	}
	echo json_encode($return);
}
elseif($action=='factureTicket'){
    ob_start();
    $ticket_id = GETPOST('ticketId');
    require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
    $tipoPago = GETPOST('tipoPago');
    $metodoCFDI = GETPOST('metodoCFDI');
    $usoCFDI = GETPOST('usoCFDI');
    $rfc = GETPOST('rfc');
    $zipCode = GETPOST('zipCode');
    $phone = GETPOST('phone');

    $ticketToFacture = new Ticket($db);
    $ticketToFacture->fetch($ticket_id);
    
    
    if (!POS::isCredit($ticket_id) && $ticket->diff_payment != 0)
    {
		$error = array('value'=>'99','desc'=>'No es posible facturar un ticket de contado sin pagar.');
    }
    else
    {
	    if ($ticketToFacture->fk_facture > 0)
    	{
	    	$facid = $ticketToFacture->fk_facture;
	    	$newFact = false;
	    }
	    else
	    {
	    	$facid = $ticketToFacture->create_facture();
	    	$newFact = true;
	    }
	    //$facid = $ticketToFacture->create_facture();
	    if ($facid > 0 && $newFact){
	        $facture = new Facture($db);
	        $facture->fetch($facid);
	        if ($newFact)
	        {
		        $facture->mode_reglement_id = $tipoPago;
		        $facture->update($user);
		        $facture->validate($user);
		        $client = new Societe($db);
		        $client->fetch($facture->socid);
		        $client->zip = $zipCode;
		        $client->phone = $phone;
		        $client->idprof1 = $rfc;
		        $client->update(0);
		        $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facture_extrafields WHERE fk_object = '.$facid;
		        $result = $db->query($sql);
		        if(!$result){
		            $error = array('value'=>'1','desc'=>'Error al actualizar datos CFDI');
		        }
		        if ($db->num_rows($resql))
		        {
			        $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_extrafields SET ';
			        $sql .= 'formpagcfdi = "'.$metodoCFDI.'", ';
			        $sql .= 'usocfdi = "'.$usoCFDI.'" ';
			        $sql .= 'WHERE fk_object = '.$facid;
		        }
		        else
		        {
		        	$sql =	 'INSERT INTO '.MAIN_DB_PREFIX.'facture_extrafields '
		        			.'(`formpagcfdi`,`usocfdi`,`fk_object`)'
		        			.'VALUES '
		        			."('{$metodoCFDI}','{$usoCFDI}','{$facid}')"
		        			;
		        }
		        
		        
		        $result = $db->query($sql);
		        if(!$result){
		            $error = array('value'=>'1','desc'=>'Error al actualizar datos CFDI');
		        }
	        }
	        $data['ref'] = $facture->ref;
	        $data['facid'] = $facid;
	        $error = array('value'=>'0','desc'=>'');
	    }
	    elseif(!$newFact)
	    {
	        $facture = new Facture($db);
	        $facture->fetch($facid);
	    	$error = array('value'=>'0','desc'=>'El ticket ya había sido facturado ('.$facture->ref.').');
	    }
	    else{
	        $error = array('value'=>'1','desc'=>'Error al crear la factura');
	    }
    }
    ob_get_clean();
    $return = array('error'=>$error,'data'=>$data);
    echo json_encode($return);
}
elseif($action == 'timbraFactura'){

    require( DOL_DOCUMENT_ROOT . '/cfdimx/conf.php' );
    include( DOL_DOCUMENT_ROOT . '/cfdimx/lib/nusoap/lib/nusoap.php' );
    include( DOL_DOCUMENT_ROOT . '/cfdimx/lib/phpqrcode/qrlib.php' );
    require( DOL_DOCUMENT_ROOT . '/cfdimx/lib/numero_a_letra.php' );

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

    $facid = GETPOST('facid');
    $socid = GETPOST('socid');
    $email = GETPOST('email');
    if( !empty($socid) && !empty($facid)){
        #Datos de la factura dolibarr
        ob_start();
        $sql   = " SELECT * FROM " . MAIN_DB_PREFIX . "facture WHERE rowid = " . $facid;
        $resql = $db->query($sql);
        if ($resql) {
            $num_fact = $db->num_rows($resql);
            $i        = 0;
            if ($num_fact) {
                while ($i < $num_fact) {
                    $obj       = $db->fetch_object($resql);
                    $ref = $obj->ref;
                    $separafac = explode("-", $ref);
                    $serie     = $separafac[0];
                    $folio     = $separafac[1];
                    $i++;
                }
            }
        }
    //$soc_rfc='';
    #Datos del receptor
        $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "facture f,  " . MAIN_DB_PREFIX . "societe s WHERE f.rowid = '" . $facid . "' AND f.fk_soc = s.rowid";
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
                        $soc_email = $obj->email;
                        $status    = $obj->fk_statut;
                    }
                    $i++;
                }
            }
        }

    #Datos de configuración del módulo
        $resql = $db->query("SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_config WHERE emisor_rfc = '" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id = " . $conf->entity);
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
        $guion = '-';
        $cliente_id = $socid;
        $_REQUEST["facid"] = $facid;
        $_REQUEST['tpdomi'] = 'Domicilio';
        $_REQUEST['osd'] = 'MXN';
        $movil = 'si';
        include(DOL_DOCUMENT_ROOT . '/cfdimx/generaCFDI.php');

        $filepath_pdf = $conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/";
        $filename_pdf = $prmsnd["uuid"].".pdf";
        $filepath_pdf .= dol_sanitizeFileName($filename_pdf);

        $filepath_xml = $conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/";
        $filename_xml = $prmsnd["uuid"].".xml";
        $filepath_xml .= dol_sanitizeFileName($filename_xml);

        if( !empty($email)){
            /*--------------------------Envio de los archivos-----------------------------*/
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
            $subject = "Archivos de timbrado de la factura ".strtoupper($serie).$guion.$folio;
            $sendto = $email; //email user
            $from = $conf->global->MAIN_MAIL_EMAIL_FROM;//email from company
            $message = "";
            $message .= "
                                <p><b>Muy buen día, en este correo viene adjunto el CFDI de la factura ".strtoupper($serie).$guion.$folio." </b></p>
                                <br>
                                <p>Saludos cordiales.</p>
                            ";
            $mimetype_pdf = dol_mimetype($filepath_pdf);
            $mimetype_xml = dol_mimetype($filepath_xml);

            $mailfile = new CMailFile($subject, $sendto, $from, $message, array($filepath_pdf, $filepath_xml), array($mimetype_pdf, $mimetype_xml), array($filename_pdf, $filename_xml), $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);

            if (!$mailfile->error) {
                $result_mail = $mailfile->sendfile();
                if ($result_mail) {
                    $msg_email = "Correo enviado con exito";

                } else {//error with send mail
                    $msg_email = 'Error enviando el correo';
                }
            } else {
                $msg_email = 'Error enviando el correo';
            }
        }
        else{
            $msg_email = 'No se ingresó un correo';
        }

        ob_get_clean();
    }
    else{
        $error++;
    }
    $resultado = array(
        'path_pdf' => strtoupper($serie).$guion.$folio."/".$filename_pdf,
        'path_xml' => strtoupper($serie).$guion.$folio."/".$filename_xml,
        'fac_reference' => $ref,
        'msg_email' => $msg_email,
        'msg' => $msg_cfdi_final,
        'error' => $error
    );
    echo json_encode($resultado);
}
elseif($action == 'getAutomaticInvoicing'){
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    $socid = $data['data'];
    $societe = new Societe($db);
    $result = $societe->fetch($socid);
    if($result > 0){
        $data = $societe->automatic_invoicing;
        $error = array('value'=>'0','desc'=>'');
    }
    else{
        $error = array('value'=>'1','desc'=>'No se encontró el cliente');
    }
    $return = array('error'=>$error,'data'=>$data);
    echo json_encode($return);
}
elseif($action == 'getRequiredFieldsCFDI'){
    $ticketId = GETPOST('ticketId');
    if($ticketId > 0){
        $ticket = new Ticket($db);
        $ticket->fetch($ticketId);
        $socid = $ticket->socid;
    }
    else{
        $socid = GETPOST('socid');
    }

    $usoCFDI = array();

	$useCFDIdefault = '';
	$sql = 'SELECT usocfdi FROM '.MAIN_DB_PREFIX.'societe_extrafields WHERE fk_object ='.$socid;
    $result = $db->query($sql);
	if($result){
		$num = $db->num_rows($result);
		if( $num > 0){
			$row = $db->fetch_object($result);
			$useCFDIdefault = $row->usocfdi;
		}
	}
	if($useCFDIdefault == '' || $useCFDIdefault == '0'){
		$useCFDIdefault = 'P01';
	}

    $sql = 'SELECT code, label FROM ';
    $sql .= MAIN_DB_PREFIX . 'c_cfdimx_uso_cfdi ';
    $sql .= 'WHERE active = 1';
    $result = $db->query($sql);
    if($result){
        $num = $db->num_rows($result);
        $j = 0;
        while($j < $num){
            $row = $db->fetch_object($result);
            $usoCFDI[$j] = array(
                'code' => $row->code,
                'label' => $row->label,
				'labelConfirm' => $useCFDIdefault
            );
            $j++;
        }
        $db->free($result);
    }
    else{
        $error = array('value'=>'1','desc'=>'Error al consultar la Base de datos');
    }

    $metodoPagoCFDI = array();

	$metodoCFDIdefault = '';
	$sql = 'SELECT formpagcfdi FROM '.MAIN_DB_PREFIX.'societe_extrafields WHERE fk_object ='.$socid;
    $result = $db->query($sql);
	if($result){
		$num = $db->num_rows($result);
		if( $num > 0){
			$row = $db->fetch_object($result);
			$metodoCFDIdefault = $row->formpagcfdi;
		}
	}

    $sql2 = 'SELECT param FROM ' . MAIN_DB_PREFIX . 'extrafields ';
    $sql2 .= 'WHERE name = "formpagcfdi"';
    $result = $db->query($sql2);
    if($result)
	{
        $j = 0;
        $num = $db->num_rows($result);
        while($row = $db->fetch_object($result))
        {
        	$arrayMetodos		= unserialize($row->param);
        	foreach($arrayMetodos['options'] as $aK => $aV)
        	{
        		$metodoPagoCFDI[]	= array(
					'label'=>$aV,
					'code'=>trim($aK),
					'labelConfirm' => $metodoCFDIdefault
				);
        	}
			break;
        }
/**
        while($j < $num){
            $row = $db->fetch_object($result);
            $arrayMetodos = explode('"', $row->param);
            $flag1 = 0;
            $flag2 = True;
            for($i = 0; $i <= count($arrayMetodos); $i++){
                if($i % 2 != 0){
                    if($flag1 == 2){
                        if($flag2) {
                            $flag2 = !$flag2;
                            $label = $arrayMetodos[$i];
                        }
                        else{
                            $flag2 = !$flag2;
                            $code = $arrayMetodos[$i];
                            array_push($metodoPagoCFDI, array('label'=>$label, 'code'=>$code));
                        }
                    }
                    else{
                        $flag1++;
                    }
                }
            }
            $j++;
        }
*/
        $db->free($result);
    }
    else{
        $error = array('value'=>'1','desc'=>'Error al consultar la Base de datos');
    }
    $metodoPago = array();

	$pago_confirmado = '';
	if($ticketId > 0){
		$sql22 = 'SELECT paye from llx_pos_ticket where rowid = '.$ticketId.' ';
		$result22 = $db->query($sql22);
		if($result22){
			$row22 = $db->fetch_object($result22);
			if($row22->paye > 0){
				$sql23 = 'SELECT c.libelle ';
				$sql23 .=' FROM llx_pos_paiement_ticket AS a, llx_paiement AS b, llx_c_paiement AS c ';
				$sql23 .=' WHERE a.amount = (SELECT MAX(amount) FROM llx_pos_paiement_ticket where fk_ticket = '.$ticketId.') ';
				$sql23 .=' AND fk_ticket = '.$ticketId.' AND b.rowid = a.fk_paiement AND b.fk_paiement = c.id ';
				$result23 = $db->query($sql23);
    			if($result23){
					$row23 = $db->fetch_object($result23);
					$pago_confirmado = $row23->libelle;
				}
				if($pago_confirmado == ''){
					$sql24 = 'SELECT c.libelle ';
					$sql24 .= ' FROM llx_pos_ticket AS a, llx_societe AS b, llx_c_paiement AS c ';
					$sql24 .= ' WHERE a.rowid = '.$ticketId.' AND a.fk_soc = b.rowid AND b.mode_reglement = c.id';
					$result24 = $db->query($sql24);
					if($result24){
						$row24 = $db->fetch_object($result24);
						$pago_confirmado = $row24->libelle;
					}
				}
			}else{
				$sql24 = 'SELECT c.libelle ';
				$sql24 .= ' FROM llx_pos_ticket AS a, llx_societe AS b, llx_c_paiement AS c ';
				$sql24 .= ' WHERE a.rowid = '.$ticketId.' AND a.fk_soc = b.rowid AND b.mode_reglement = c.id';
				$result24 = $db->query($sql24);
				if($result24){
					$row24 = $db->fetch_object($result24);
					$pago_confirmado = $row24->libelle;
				}
			}
		}
	}
	if($pago_confirmado == ''){
		$pago_confirmado = 'Por Definir';
	}
	if($pago_confirmado == 'Nota de Crédito')
	{
		$sql99 = 'SELECT cp.libelle '."\r\n"
				.'FROM llx_societe_remise_except AS sre '."\r\n"
				.'LEFT JOIN llx_c_paiement as cp '."\r\n"
				.'ON sre.fk_paiement_type = cp.id '."\r\n"
				.'WHERE sre.fk_ticket = '.$ticketId."\r\n"
				.'ORDER BY sre.amount_ttc DESC  '."\r\n"
				.'LIMIT 0,1'."\r\n"
				.''
				;
		//die ($sql99);
		if ($res99 = $db->query($sql99))
		{
			while ($row99 = $db->fetch_object($res99))
			{
				if (strlen($row99->libelle))
				{
					$pago_confirmado = $row99->libelle;
				}
			}
		}

	}

	$sql2 = 'SELECT id, code, libelle FROM ' . MAIN_DB_PREFIX . 'c_paiement ';
	$sql2 .= 'WHERE active = 1';	

    $result = $db->query($sql2);
    if($result){
        $j = 0;
        $num = $db->num_rows($result);
        while($j < $num){
            $row = $db->fetch_object($result);
            $rowMetodoPago = array(
                'id' => $row->id,
                'code' => $row->code,
                'label' => $row->libelle,
				'labelConfirm' => $pago_confirmado
            );
            array_push($metodoPago, $rowMetodoPago);
            $j++;
        }
        $db->free($result);
    }
    else{
        $error = array('value'=>'1','desc'=>'Error al consultar la Base de datos');
    }
    $sql3 = 'SELECT zip, phone, email, siren FROM ' . MAIN_DB_PREFIX . 'societe ';
    $sql3 .= 'WHERE rowid = '.$socid;
    $result = $db->query($sql3);
    if($result){
        $j = 0;
        $num = $db->num_rows($result);
        while($j < $num){
            $row = $db->fetch_object($result);
            $clientInfo = array(
                'zip' => $row->zip,
                'phone' => $row->phone,
                'email' => $row->email,
                'rfc' => $row->siren
            );
            $j++;
        }
        $db->free($result);
    }
    else{
        $error = array('value'=>'1','desc'=>'Error al consultar la Base de datos');
    }
    $data = array(
        'metodoPago' => $metodoPago,
        'metodoPagoCFDI' => $metodoPagoCFDI,
        'usoCFDI' => $usoCFDI,
        'clientInfo' => $clientInfo
    );
    if(!$error) $error = array('value'=>'0','desc'=>'');
    $return = array('error'=>$error,'data'=>$data);
    echo json_encode($return);
}

//checkpoint: validate password
elseif ($action == 'validate_pass') {
    global $db;
    $object_user = new User($db);
    $res = $object_user->fetch_all();
    $password = $data['data'];
    $ret = array();
    $ret['allow']=false;
    $ret['error']=0;
    $ret['mensaje']='';
    $ret['desc']=-1;
    if($res < 0){
        $ret['error']=1;
        $ret['mensaje']='Hubo un error';
    }else{
        foreach ($res as $k => $u) {
            if( $u['pos_pass'] == $password && $u['admin'] == 1 ) { //compare if the typed password correspond to a validator user
                $ret['allow'] = true;
                if(empty($u['pos_desc'])){
                    $ret['error']=2;
                    $ret['desc']=0;
                    $ret['mensaje']='No tienes descuento asociado';
                }
                else
                    $ret['desc'] = $u['pos_desc'];
            }
        }
    }
    if($ret['desc'] < 0){
        $ret['desc'] = 0;
        $ret['error'] = 3;
        $ret['mensaje']='Datos erroneos o no eres administrador';
    }
    echo json_encode($ret);
}
elseif($action == 'createDeliveryOrder')
{
	$return = array('error'=>array(),'data'=>'');

	if (is_array($data['data']['raw']['lines']) && count($data['data']['raw']['lines']))
	{
		$lines = $data['data']['raw']['lines'];
	}
	elseif (is_array($data['data']['raw']['oldproducts']) && count($data['data']['raw']['oldproducts']))
	{
		$lines = $data['data']['raw']['oldproducts'];
	}
	else
	{
		$lines = array();
	}

	$linesCnt = 0;
	foreach($lines as $line)
	{
		$cant = isset($line['cant']) && is_numeric($line['cant']) && $line['cant']> 0 ?
					$line['cant'] :
					'0'
					;
		$entr = isset($line['qty_ent']) && is_numeric($line['qty_ent']) && $line['qty_ent']> 0 ?
					$line['qty_ent'] :
					'0'
					;
 		if (($cant - $entr) > 0)
 		{
			$linesCnt++;
 		}
	}
	
	if (empty($data['data']['ticket']) || $data['data']['ticket']<=0)
	{
		$return['error'] = array('value'=>__LINE__,'desc'=>'Identificador de ticket no válido.');
	}
	elseif (!($linesCnt>0))
	{
		$return['error'] = array('value'=>__LINE__,'desc'=>'Pedido vacío (no queda nada que entregar).');
	}
	else
	{
		$sql =	 'SELECT fk_target '
				.'FROM llx_element_element '
				.'WHERE fk_source = '.$data['data']['ticket']
				.'  AND sourcetype=\'ticket\' '
				.'  AND targettype=\'commande\' '
				;
		if (!$res = $db->query($sql))
		{
			$return['error'] = array('value'=>__LINE__,'desc'=>'Error de base de datos: '.$db->db->error);
		}
		else
		{
			if (!class_exists('Commande'))
			{
				require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
			}
			$commande = new Commande($db);
			if ($cmdID = $db->fetch_object($res))
			{
				$commande->fetch($cmdID->fk_target);
				if ($commande->statut==0)
				{
					$commande->ref_client = 'POS Ent. a Domicilio / '.strtoupper($data['data']['name']);
					if ($data['data']['customer'] > 0)
					{
						$commande->socid = $data['data']['customer'];
					}
					if ($data['data']['project'] > 0)
					{
						$commande->fk_project = $data['data']['project'];
					}
					$commande->note_private= 	 'Actualizada: '.gmdate('Y-m-d H:i:s').'GMT por '.$user->login."\r\n"
												.'--- Información Anterior ---'."\r\n"
												.$commande->note_public."\r\n"
												.$commande->note_private."\r\n"
												;
					$commande->note_public  =	 'ENTREGA A DOMICILIO GENERADA EN EL POS A:'."\r\n"."\r\n"
												.'Nombre: '.$data['data']['name'].', '."\r\n"
												.'Domicilio: '.$data['data']['address'].', '."\r\n"
												.'Ciudad: '.$data['data']['city'].', '."\r\n"
												.'Estado: '.$data['data']['state'].', '."\r\n"
												.'Pais: '.$data['data']['country'].', '."\r\n"
												.'C.P. '.$data['data']['zip'].' '."\r\n"
												.'Teléfono: '.$data['data']['phone'].' '."\r\n"
												;
					//$commande->linked_objects['ticket'] = $data['data']['ticket'];
					$cid = $commande->update($user);
					foreach($commande->lines as $cl)
					{
						$commande->deleteline($user,$cl->id);
					}
					$ticket = new Ticket($db);
					$ticket->fetch($data['data']['ticket']);
					//$ticket->fetch_lines();
					foreach($ticket->lines as $tl)
					{
						foreach($lines as $line)
						{
							if ($line['idProduct'] != $tl->fk_product)
							{
								continue;
							}
							$cant = isset($line['cant']) && is_numeric($line['cant']) && $line['cant']> 0 ?
										$line['cant'] :
										'0'
										;
							$entr = isset($line['qty_ent']) && is_numeric($line['qty_ent']) && $line['qty_ent']> 0 ?
										$line['qty_ent'] :
										'0'
										;
							if (($cant - $entr) != 0)
							{
								$row = $commande->addline($line['label'],'0.00'/*$line['price']*/,$cant - $entr,$line['tva_tx'],0,0,$line['idProduct'],$line['remise'],0,0,'HT',0,'','',$line['fk_product_type']);
								if (!class_exists('MouvementStock'))
								{
									require_once(DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php');
								}
								$mouvP = new MouvementStock($db);
								$mouvP->setOrigin('ticket',$ticket->id);
								$result=$mouvP->livraison($user, $line['idProduct'], $ticket->getEntrepot()->id, ($cant - $entr), null, $langs->trans("Pedido de Cliente {$commande->ref} creado en el POS"));

								if ($result < 0) { $error++; }
								if (!$error)
								{
									$sql =	 'UPDATE llx_pos_ticketdet '
											.'SET 	 ls_stock_dec_qty = IF (`ls_stock_dec_qty` IS NULL, '.($cant - $entr).' ,IF(`ls_stock_dec_qty`+'.($cant - $entr).'>qty,qty,`ls_stock_dec_qty` + '.($cant - $entr).')), '
											.		'ls_stock_dec_date = \''.gmdate('Y-m-d H:i:s').'\', '
											.		'`qty_ent` = IF(`qty_ent`+'.($cant - $entr).'>qty,qty,`qty_ent` + '.($cant - $entr).') '
											.'WHERE fk_ticket='.$data['data']['ticket'].' '
											.'  AND fk_product='.$line['idProduct'].' '
											;
									if (!$db->query($sql))
									{
										dol_print_error($db);
										die();
									}
									$tl->updateEstadoV(7);
								}
							}
						}
					} 
					$return['error'] = array('value'=>0,'desc'=>'Pedido '.$commande->ref.' actualizado y se ecuentra asociado al ticket.');
				}
				else
				{
					$return['error'] = array('value'=>1,'desc'=>'El pedido '.$commande->ref.' ya fué procesado.');
				}
			}
			else
			{
				$commande->ref_client = 'POS Ent. a Domicilio / '.strtoupper($data['data']['name']);
				if ($data['data']['customer'] > 0)
				{
					$commande->socid = $data['data']['customer'];
				}
				if ($data['data']['project'] > 0)
				{
					$commande->fk_project = $data['data']['project'];
				}
				$commande->date_commande = gmdate('Y-m-d');
				$commande->note_public=	 'ENTREGA A DOMICILIO GENERADA EN EL POS A:'."\r\n"."\r\n"
										.'Nombre: '.$data['data']['name'].', '."\r\n"
										.'Domicilio: '.$data['data']['address'].', '."\r\n"
										.'Ciudad: '.$data['data']['city'].', '."\r\n"
										.'Estado: '.$data['data']['state'].', '."\r\n"
										.'Pais: '.$data['data']['country'].', '."\r\n"
										.'C.P. '.$data['data']['zip'].' '."\r\n"
										.'Teléfono: '.$data['data']['phone'].' '."\r\n"
										;
				$commande->linked_objects['ticket'] = $data['data']['ticket'];
					$ticket = new Ticket($db);
					$ticket->fetch($data['data']['ticket']);
					//$ticket->fetch_lines();
				$commande->warehouse_id = $ticket->getEntrepot()->id;
				$cid = $commande->create($user);
					foreach($ticket->lines as $tl)
					{
						foreach($lines as $line)
						{
							if ($line['idProduct'] != $tl->fk_product)
							{
								continue;
							}
							$cant = isset($line['cant']) && is_numeric($line['cant']) && $line['cant']> 0 ?
										$line['cant'] :
										'0'
										;
							$entr = isset($line['qty_ent']) && is_numeric($line['qty_ent']) && $line['qty_ent']> 0 ?
										$line['qty_ent'] :
										'0'
										;
							if (($cant - $entr) != 0)
							{
								$row = $commande->addline($line['label'],'0.00',$cant - $entr,$line['tva_tx'],0,0,$line['idProduct'],$line['remise'],0,0,'HT',0,'','',$line['fk_product_type']);
								if (!class_exists('MouvementStock'))
								{
									require_once(DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php');
								}
								$mouvP = new MouvementStock($db);
								$mouvP->setOrigin('ticket',$ticket->id);
								$result=$mouvP->livraison($user, $line['idProduct'], $ticket->getEntrepot()->id, ($cant - $entr), null, $langs->trans("Pedido de Cliente {$commande->ref} creado en el POS"));

								if ($result < 0) { $error++; }
								if (!$error)
								{
									$sql =	 'UPDATE llx_pos_ticketdet '
											.'SET 	 ls_stock_dec_qty = IF (`ls_stock_dec_qty` IS NULL, '.($cant - $entr).' ,IF(`ls_stock_dec_qty`+'.($cant - $entr).'>qty,qty,`ls_stock_dec_qty` + '.($cant - $entr).')), '
											.		'ls_stock_dec_date = \''.gmdate('Y-m-d H:i:s').'\', '
											.		'`qty_ent` = IF(`qty_ent` IS NULL ,'.($cant - $entr).',IF(`qty_ent`+'.($cant - $entr).'>qty,qty,`qty_ent` + '.($cant - $entr).')) '
											.'WHERE fk_ticket='.$data['data']['ticket'].' '
											.'  AND fk_product='.$line['idProduct'].' '
											;
									if (!$db->query($sql))
									{
										dol_print_error($db);
										die();
									}
									$tl->updateEstadoV(7);
								}
							}
						}
					} 
				$return['error'] = array('value'=>0,'desc'=>'El pedido '.$commande->ref.' fué creado y  asociado al ticket.');
			}
		}
	}
	echo json_encode($return);
}
// Get freight cost.
elseif ($action == 'getFreight') {
	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

	// Get service id
	$prodid = Product::getFreightServiceID();

	// Calculate cost
	$freightcost = Ticket::getFreightCost($prodid, $data['data']['distance'], $data['data']['shipment']);
	
	// Response
	if ($freightcost >= 0) echo json_encode(array('ok' => true, 'service' => $prodid, 'cost' => $freightcost, 'text' => price($freightcost)));
	else echo json_encode((array('ok' => false, 'error' => 'Ocurrió un error al determinar el costo de flete')));
}
elseif($action=='rc_askForWarehouseTransfer')
{
	$return = array('error'=>array(),'data'=>'-1');
	
	$id_tw = (isset($data['data']['target'])) ? $data['data']['target']: '0';
	//$id_sw = (isset($data['data']['source'])) ? $data['data']['source']: '0';
	$id_sw = ($id_tw == 1) ? 2 : 1 ;
	$id_tk = (isset($data['data']['ticket_id']['id'])) ? $data['data']['ticket_id']['id']: '0';
	
	if (!($id_tk > 0))
	{
		$return['error'] = array('value'=>'-1','desc'=>'No se recibió un "ticket" válido. Avise a Soporte Técnico.');
	}
	elseif (!($id_sw > 0))
	{
		$return['error'] = array('value'=>'-2','desc'=>'No se recibió un "almacén origen" válido. Avise a Soporte Técnico.');
	}
	elseif (!($id_tw > 0))
	{
		$return['error'] = array('value'=>'-3','desc'=>'No se recibió un "almacén destino" válido. Avise a Soporte Técnico.');
	}
	elseif ($id_tw == $id_sw)
	{
		$return['error'] = array('value'=>'-4','desc'=>'Los "almacén origen" y "almacén destino" son el mismo. Avise a Soporte Técnico.');
	}
	else
	{
		$db->begin();
		$ticket = new Ticket($db);
		$ticket->fetch($id_tk);
		$listofdata = array();
		for ($i=0,$n=count($data['data']['products']);$i<$n;$i++)
		{
			$id = $i + 1;
			$id_product = (isset($data['data']['products'][$i]['prod_id'])) ? $data['data']['products'][$i]['prod_id']: '0';
			$qty = (isset($data['data']['products'][$i]['qty'])) ? $data['data']['products'][$i]['qty']: '0';
			$ent = (isset($data['data']['products'][$i]['ent'])) ? $data['data']['products'][$i]['ent']: '0';
			$qty = $qty - $ent;
			if (!($id_product > 0))
			{
				$return['error'] = array('value'=>'-5','desc'=>'No se recibió un "producto" válido. Avise a Soporte Técnico.');
				break;
			}
			elseif (!$qty)
			{
				$return['error'] = array('value'=>'-6','desc'=>'El traspaso NO debe incluir productos con cantidad 0 (cero).');
				break;
			}
			else
			{
				$listofdata[$id] = array(
											  'id'=>$id
											, 'id_product'=>$id_product
											, 'qty'=>$qty
											, 'id_sw'=>$id_sw
											, 'id_tw'=>$id_tw
											, 'batch'=>$batch
											, 'origin_element'=>'ticket'
											, 'origin_id'=>$id_tk
										);
			}
		}
		if (!count($listofdata))
		{
			$return['error'] = array('value'=>'-7','desc'=>'No se recibieron productos para el traspaso.');
		}
		elseif (!count($return['error']))
		{
			$return['error']= array('value'=>'0','desc'=>'');
			$return['data']	= '1';
			$_SESSION['massstockmove'] = json_encode($listofdata); 
		}
	}
	if ($return['error']['value'] == 0)
	{
		$db->commit();
	}
	else
	{
		$db->rollBack();
	}
	echo json_encode($return);
}
elseif($action == 'getDeliveryData')
{
	require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
	$customerId = $data['data'];
	$soc = new Societe($db);
	$soc->fetch($customerId);
	$return['name'] = $soc->name;
	$return['address'] = $soc->address;
	$return['town'] = $soc->town;
	$return['state'] = $soc->state;
	$return['zip'] = $soc->zip;
	$return['phone'] = $soc->phone;
	$return['country'] = 'México';
	$return = array('error'=>array('value'=>'0','desc'=>''),'data'=>$return);
	echo json_encode($return);
}
elseif($action == 'abandonarTicket')
{
    $abandonated = POS::AbandonatedTicket($data['data']);
    echo json_encode($abandonated);
}
elseif($action == 'getEmailFromCustomer'){
    require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
    $socid = GETPOST('socid');
    $societe = new Societe($db);
    $societe->fetch($socid);
    $return['email'] = $societe->email;
    $return = array('error'=>array('value'=>'0','desc'=>''),'data'=>$return);
	echo json_encode($return);
}
elseif($action == 'createDeliveryTicket')
{
	$ticketId = $data['data']['ticket_id'];
	$ticket = new Ticket($db);
	$cash = new Cash($db);
	$cash->fetch($_SESSION['TERMINAL_ID']);
	$soc = new Societe($db);
	$soc->fetch($cash->fk_soc);
	if ($user->fk_warehouse == 1)
	{
		$prefix = 'MG';
		$cash = '2';
		$warehouse = 2;
	}
	else
	{
		$prefix = 'GM';
		$cash = '1';
		$warehouse = 1;
	}
	$ticket->ref = $ticket->getNextNumRef($soc,'next',$prefix);
	$ticket->type = '0';
	$ticket->fk_cash = $cash;
	$ticket->socid = $data['data']['client_id'];
	$ticket->statut = '1';
	$ticket->remise_absolue = 0;
	$ticket->remise_percent = 0;
	$ticket->customer_pay = 0;
	$ticket->diff_payment = 0;
	$ticket->note = 'Mercancía pagada en ticket '.$data['data']['ticket_ref'];
	//$ticket->fk_project = ''
	$tid = $ticket->create($user->id,true);
	
	foreach($data['data']['lines'] as $k => $v)
	{
		$prod = new Product($db);
		$prod->fetch($k);
		$ticket->addline($prod->label,'0',$v,'16',null,null,$k,100,null,null,null,null,null,null,null,null,null,null,null,null,null,null,10,$user->id,null,$v,gmdate('Y-m-d H:i:s'),$v);
		$sql =	 'UPDATE `llx_pos_ticketdet` '
				.'SET `qty_ent` = IF(`qty_ent` IS NULL,'.$v.',IF(`qty_ent`+'.$v.'>qty,qty,`qty_ent` + '.$v.')) '
				.'WHERE fk_ticket = '.$ticketId.' '
				.'  AND fk_product = '.$k
				;
		$db->query($sql);
		if (!class_exists('MouvementStock'))
		{
			require_once(DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php');
		}
		$mouvP = new MouvementStock($db);
		$mouvP->setOrigin('ticket',$ticketId);
		$result=$mouvP->livraison($user, $k, $warehouse, $v, null, $langs->trans("Orden de surtido {$ticket->ref} creado en el POS"));

		if ($result < 0) { $error++; }
		if (!$error)
		{
			$sql =	 'UPDATE llx_pos_ticketdet '."\r\n"
					.'SET	ls_stock_dec_date = \''.gmdate('Y-m-d H:i:s').'\', '."\r\n"
					.'		ls_stock_dec_qty = IF(`ls_stock_dec_qty` IS NULL,'.$v.',IF(`ls_stock_dec_qty`+'.$v.'>qty,qty,`ls_stock_dec_qty` + '.$v.'))  '."\r\n"
					.'WHERE fk_ticket='.$ticket->id.' '."\r\n"
					.'  AND fk_product='.$k."\r\n"
					;
			if (!$db->query($sql))
			{
				dol_print_error($db);
				die();
			}
		}
	}
	
	$return = array('error'=>array('value'=>'0','desc'=>'Se generó el ticket '.$ticket->ref),'data'=>$tid);
	echo json_encode($return);
	
}
elseif ($action=='can_delete_line')
{
	$tk = intval($data['data']['ticket']);
	if (empty($tk))
	{
		echo '0';
	}
	else
	{
        $sql =	 'SELECT `sm`.`fk_product` AS `fk_product`,SUM(`sm`.`value`) AS `pending`'."\r\n"
				.'FROM `llx_stock_mouvement` AS `sm`'."\r\n"
				.'WHERE `sm`.`origintype`=\'ticket\''."\r\n"
				.'  AND `sm`.`fk_origin`=\''.intval($data['data']['ticket']).'\''."\r\n"
				.'  AND `sm`.`fk_product`=\''.intval($data['data']['product']).'\''."\r\n"
				.'GROUP BY `fk_product`'."\r\n"
				;
		if (!$res = $db->query($sql))
		{
			echo '-999999999';
		}
		$pend = array();
		while ($row = $db->fetch_object($res))
		{
			if ($row->pending != 0)
			{
				$pend[$row->fk_product]=$row->pending;
			}
		}
        if (!count($pend) || !isset($pend[$data['data']['product']]))
        {
        	echo '0';
        }
        else
        {
        	echo abs($pend[$data['data']['product']]);
        }
	}
}
elseif ($action=='get_invoice_id')
{
	$tkid = $data['data']['ticket_id'];
	$ticket = new Ticket($db);
	$return = 0;
	if ($ticket->fetch($tkid))
	{
		if ($ticket->fk_facture > 0)
		{
			$return = $ticket->fk_facture;  
		}
		elseif($ticket->type == 1)
		{
			if ($ticket->id_source > 0)
			{
				$sale_ticket = new Ticket($db);
				$sale_ticket->fetch($ticket->id_source);
				if (!($sale_ticket->fk_facture > 0))
				{
					$return = '999999999999';
				}
			}
		}
	}
	//echo '1';
	echo $return;
}
elseif($action=='split_discount')
{
	
	pos::splitAbsoluteDiscount(GETPOST('id'),GETPOST('amount'));
}
elseif($action=='returnPaiementToChange')
{
    require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
    $form = new Form($db);
    $form->load_cache_types_paiements();
    $cash = new Cash($db);
    $cash->fetch($_SESSION['TERMINAL_ID']);
    $array_mode_pays = array(
        $cash->fk_modepaycash => $form->cache_types_paiements[$cash->fk_modepaycash]['label']
    , $cash->fk_modepaybank => $form->cache_types_paiements[$cash->fk_modepaybank]['label']
    , $cash->fk_modepaybank_extra => $form->cache_types_paiements[$cash->fk_modepaybank_extra]['label']
    ,$cash->fk_modepaybank_extra_2 => $form->cache_types_paiements[$cash->fk_modepaybank_extra_2]['label']
    );
    $result = array();
    foreach ($array_mode_pays as $key => $value) {
        $aux = array();
        $sql = "SELECT t.ticketnumber, p.ref";
        //$sql.=",IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount,p.amount) as amount";
        $sql.=", p.rowid";
        $sql .=" FROM (".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p)";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = t.fk_facture ";
        $sql .=" WHERE p.fk_paiement=".$key." AND t.fk_statut > 0 AND DATE(p.datep) = DATE(NOW())";
        $sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket AND t.fk_cash in (SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash WHERE fk_warehouse = ".$cash->fk_warehouse.")";

        $resql = $db->query($sql);
        if($resql){
            while($objp = $db->fetch_object($resql)){
                $objp->html='<select id="selectFrom_'.$key.'_'.$objp->rowid.'" class="changetypep">';
                $objp->html.= '<option value="-1" selected>&nbsp;</option>';
                foreach ($array_mode_pays as $key2 => $value2){
                    if($key != $key2) {
                        $objp->html .= '<option value="' . $key2 . '">' . $value2 . '</option>';
                    }
                }
                $objp->html.='</select>';
                array_push($aux,array($key,$objp));
            }
            if(sizeof($aux) == 0){
                $objp = new stdClass();
                $objp->ticketnumber='&nbsp;';
                $objp->ref='&nbsp;';
                $objp->html='Sin registros';
                array_push($aux,array($key,$objp));
            }
            array_push($result,$aux);
        }
    }
    echo json_encode($result);
}
elseif($action=='changePayments') {
    $info = $data['data'];
    $cash = new Cash($db);
    $cash->fetch($_SESSION['TERMINAL_ID']);
    foreach ($info as $key => $value){//id,from,to
        $sql = "SELECT fk_bank FROM ".MAIN_DB_PREFIX."paiement WHERE rowid=".$value['id'];
        $resql= $db->query($sql);
        $error = 0;
        if($resql) {
            $objp = $db->fetch_object($resql);
            // Cambiar el fk_paiement al pago
            $update_fkp = "UPDATE ".MAIN_DB_PREFIX."paiement SET fk_paiement=".$value['to'];
            $update_fkp.=" WHERE rowid=".$value['id'];
            $res1 = $db->query($update_fkp);
            if(!$res1) $error++;
            //cambiar el tipo de pago en llx_bank y el fk_account
            if($cash->fk_modepaycash == $value['to']) {
                $update_bnk = "UPDATE " . MAIN_DB_PREFIX . "bank SET fk_type='LIQ',fk_account=".$cash->fk_paycash." WHERE rowid=".$objp->fk_bank;
            }elseif ($cash->fk_modepaybank == $value['to']){
                $update_bnk = "UPDATE " . MAIN_DB_PREFIX . "bank SET fk_type='TD/C',fk_account=".$cash->fk_paybank." WHERE rowid=".$objp->fk_bank;
            }elseif ($cash->fk_modepaybank_extra == $value['to']){
                $update_bnk = "UPDATE " . MAIN_DB_PREFIX . "bank SET fk_type='VIR',fk_account=".$cash->fk_paybank_extra." WHERE rowid=".$objp->fk_bank;
            }elseif ($cash->fk_modepaybank_extra_2 == $value['to']){
                $update_bnk = "UPDATE " . MAIN_DB_PREFIX . "bank SET fk_type='CB',fk_account=".$cash->fk_paybank_extra_2." WHERE rowid=".$objp->fk_bank;
            }
            $res2 = $db->query($update_bnk);
            if(!$res2) $error++;
        }
    }
    echo $error;
}
elseif($action=='cloneTicket')
{
    require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
    $ticket= new Ticket($db);
    $ticket->fetch($data['data']);
    $objectutil = dol_clone($ticket, 1);
    $objectutil->id = 0;
    $objectutil->statut = 0;
    $lines = $ticket->lines;

    $res = $objectutil->create($user->id);
    $prod = new Product($db);
    foreach ($lines as $i => $line){
        //var_dump($line);
        $prod->fetch($line->fk_product);
        $objectutil->addline($line->description,$prod->price,$line->qty,'16',null,null,$line->fk_product,$line->remise_percent,null,null,$prod->price_ttc,null,null,null,null,null,null,null,null,null,null,null,null,$user->id,null,0,gmdate('Y-m-d H:i:s'),0);
    }
    $result = array('result'=>$res);
    echo json_encode($result);
}
elseif($action=='getOpenTickets')
{
	$sql =   
			 'SELECT DISTINCT st.rowid'."\r\n"
   			.'      FROM llx_pos_ticket AS st'."\r\n"
   			.'      LEFT JOIN llx_pos_ticketdet AS std'."\r\n"
   			.'        ON st.rowid = std.fk_ticket'."\r\n"
   			.'      WHERE st.type = 0'."\r\n"
			.'		  AND st.fk_statut IN (2)'
			.'        AND st.fk_user_author = '.$user->id."\r\n"
			;
	if (!$res1 = $db->query($sql.' AND st.date_creation < \''.date('Y-m-d').' 00:00:00\''))
	{
		dol_print_error($db);
		die();
	}
	$pc = 0;
	while ($db->fetch_object($res1))
	{
		$pc++;
	}

	if (!$res2 = $db->query($sql.' AND st.date_creation >= \''.date('Y-m-d').' 00:00:00\''))
	{
		dol_print_error($db);
		die();
	}
	$tc = 0;
	while ($db->fetch_object($res1))
	{
		$tc++;
	}
	$result = array('result'=>array('anteriores'=>$pc,'hoy'=>$tc));
	//$result = array('result'=>array('anteriores'=>0,'hoy'=>43));
    echo json_encode($result);
}
echo $html;


?>
