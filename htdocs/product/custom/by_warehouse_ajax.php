<?php
require '../../main.inc.php';
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
include_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php');



$action = GETPOST('action');

$jsonData = array('status'=>'ERROR','msg'=>array('Error desconocido'));

switch($action)
{
	case 'resetLineEstadoV':
		$idTckt = GETPOST('ticket','int');
		$idLine = GETPOST('line','int');
		$idProduct = GETPOST('product_id','int');
		$sql =	 'UPDATE `'.MAIN_DB_PREFIX.'pos_ticketdet` AS `td`'."\r\n"
				.'SET	`ls_warehouse_status`= NULL'."\r\n"
				.'		,`ls_warehouse_status_by`=NULL'."\r\n"
				.'		,`ls_warehouse_status_date`=NULL'."\r\n"
				.'		,`qty_ent`=`qty`'."\r\n"
				.'		,`ls_stock_mv_id`=NULL'."\r\n"
				.'		,`ls_stock_mv_code`=NULL'."\r\n"
				.'		,`ls_stock_dec_date`=NULL'."\r\n"
				.'		,`ls_stock_dec_qty`=NULL'."\r\n"
				.'WHERE `rowid` = '.$idLine
				;
		if (!$db->query($sql))
		{
			$jsonData['msg']=array('Error al intentar re-iniciar la información en la DB.');
		}
		else
		{
			$sql =	 'SELECT sum(`sm`.`value`) AS delivered'."\r\n"
					.'		,`sm`.`fk_entrepot` '."\r\n"
					.'FROM `'.MAIN_DB_PREFIX.'stock_mouvement` AS `sm`'."\r\n"
					.'WHERE `sm`.`fk_origin` = '.$idTckt."\r\n"
					.'  AND `sm`.`origintype` = \'ticket\''."\r\n"
					.'  AND `sm`.`fk_product` = '.$idProduct.''."\r\n"
					;
			if (!$res = $db->query($sql))
			{
				dol_print_error($db);
				$jsonData['msg']=array('Error al intentar obtener los movimientos de stock de la DB.');
			}
			else
			{
				if($stoks = $db->fetch_object($res))
				{
					$st_mv = new MouvementStock($db);
					$origin = new stdClass();
					$origin->element = 'ticket';
					$origin->id = $idTckt;
					$st_mv->origin = $origin;
					if ($stoks->delivered < 0)
					{
						$st_mv->reception($user,$idProduct,$stoks->fk_entrepot,($stoks->delivered*-1),0,'Reinicio de Ticket');
					}
					else
					{
						$st_mv->livraison($user,$idProduct,$stoks->fk_entrepot,($stoks->delivered*-1),0,'Reinicio de Ticket');
					}
					array('status'=>'OK','msg'=>array());
				}
			}
		}
		
		
		break;
	case 'saveStock':
		$idProduct = GETPOST('product','int');
		$stock = GETPOST('stock','int');
		$idWarehouse = GETPOST('warehouse','int');
		$idLine = GETPOST('line','int');
		$sql =	 'SELECT `rowid` '."\r\n"
				.'FROM `llx_product_stock_revised` '."\r\n"
				.'WHERE `fk_product`='.$idProduct."\r\n"
				.'  AND `fk_warehouse`='.$idWarehouse."\r\n"
				;
		if ($res = $db->query($sql))
		{
			if ($row=$db->fetch_object($res))
			{
				if (is_numeric($stock))
				{
					$sql =	 'UPDATE `llx_product_stock_revised` '."\r\n"
							.'SET `stock_revised`='.$stock."\r\n"
							.'WHERE `rowid`='.$row->rowid."\r\n"
							;
				}
				else
				{
					$sql =	 'DELETE FROM `llx_product_stock_revised` '."\r\n"
							.'WHERE `rowid`='.$row->rowid."\r\n"
							;
				}
			}
			else
			{
				if (is_numeric($stock))
				{
					$sql =	 'INSERT INTO `llx_product_stock_revised` '."\r\n"
							.'(`fk_product`,`fk_warehouse`,`stock_revised`)'."\r\n"
							.'VALUES'."\r\n"
							."({$idProduct},{$idWarehouse},{$stock})"."\r\n"
							;
				}
				else
				{
					$sql = false;
				}
			}
			if ($sql)
			{
				if ($db->query($sql))
				{
					$jsonData = array('status'=>'OK','msg'=>array());
					$sql =	 'UPDATE `llx_pos_ticketdet` AS `td` '."\r\n"
							.'LEFT JOIN `llx_pos_ticket` AS `t`'."\r\n"
							.'  ON `t`.`rowid` = `td`.`fk_ticket` '."\r\n"
							.'LEFT JOIN `llx_pos_cash` AS `c`'."\r\n"
							.'  ON `c`.`rowid` = `t`.`fk_cash` '."\r\n"
							.'SET	 `td`.`ls_warehouse_status`=2'."\r\n"
							.'		,`td`.`ls_warehouse_status_by`='.$user->id."\r\n"
							.'		,`td`.`ls_warehouse_status_date`=\''.gmdate('Y-m-d H:i:s').'\''."\r\n"
							.'WHERE ('."\r\n"
							.'				('."\r\n"
							.'					`td`.`rowid`='.$idLine."\r\n"
							.'				AND (`td`.`ls_warehouse_status` IS NULL OR `td`.`ls_warehouse_status` IN (0,1,12))'."\r\n"
							.'				)'."\r\n"
							.'			OR ('."\r\n"
							.'					`td`.`ls_warehouse_status` IN (1)'."\r\n"
							.'				AND `td`.`fk_product`='.$idProduct."\r\n"
							.'			)'."\r\n"
							.'		)'."\r\n"
							.'  AND `c`.`fk_warehouse`='.$idWarehouse."\r\n"
							;
					if (!$db->query($sql))
					{
						$jsonData = array('status'=>'ERROR','msg'=>array($sql . ' ' . $db->db->error));
					}
					else
					{
						$jsonData = array('status'=>'OK','msg'=>array());
					}
				}
				else
				{
					$jsonData = array('status'=>'ERROR','msg'=>array($sql . ' ' . $db->db->error));
				}
			}
			else
			{
				$jsonData = array('status'=>'OK','msg'=>array());
			}
		}
		else
		{
			$jsonData['msg']=array($db->db->error);
		}
		
		
		break;
	case 'updateLineEstadoV':
		$ln = GETPOST('line','int');
		$st = GETPOST('status','int');
		$qe = GETPOST('qty_ent');
		include_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php');
		$static_line = new TicketLigne($db);
		$static_line->fetch($ln);

		if ($st != 12)
		    $static_line->qty_ent = floatval($qe);
		else
		    $static_line->qty_ent = floatval(0);
		$static_line->update($user);

		if (true || in_array($st,array(9)))
		{
			include_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
			$oldTicket = new Ticket($db);
			$oldTicket->fetch($static_line->fk_ticket);
		}

		//include_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php');
		//$static_line = new TicketLigne($db);
		//$static_line->fetch($ln);
		$dbSt = $static_line->updateEstadoV($st);

		if (true || in_array($st,array(9)))
		{
			$ticket = new Ticket($db);
			$ticket->fetch($static_line->fk_ticket);
			POS::quitStock($ticket,$oldTicket);
		}

		if (strlen($dbSt) > 10)
		{
			$jsonData = array('status'=>'OK','msg'=>array(),'date'=>$dbSt);
		}
		else
		{
			$jsonData['msg']=array('No fue posible actualizar el Estado V.');
		}
		break;
	case 'updateOrphanLineEstadoV':
		$jsonData['msg']=array('Función en desarrollo.');
		break;
	case 'editLocation':
		// recibimos el id y la nueva ubicación y actualizamos
		$id = GETPOST('id','int');
		$location = GETPOST('location','alpha');
		$sql = 'UPDATE `llx_product` SET `location_matriz`=\''.$location.'\' WHERE `rowid`='.$id;
		if($db->query($sql))
		{
			$jsonData = array('status'=>'OK','msg'=>'Ubicación actualizada.', 'result' => 1);
		}
		else
		{
			$jsonData = array('status'=>'ERROR','msg'=>'No fue posible actualizar la ubicación.', 'result' => 0);
		}
	default:
		break;
}

echo (json_encode($jsonData));

?>