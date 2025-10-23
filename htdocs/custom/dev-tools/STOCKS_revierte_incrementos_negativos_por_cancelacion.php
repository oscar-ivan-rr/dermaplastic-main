<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
llxHeader("", 'Recalcular Estado de Pagado de Tickets Según sus Pagos Reales', '');

$sql =	 'SELECT `sm`.`rowid`, `sm`.`fk_product`,`sm`.`value`,`sm`.`fk_entrepot`,`sm`.`type_mouvement`,`sm`.`label` '."\r\n"
		.'FROM `llx_stock_mouvement` AS `sm` '."\r\n"
		.'WHERE `sm`.`label` LIKE \'Cancelación de Ticket %\' '."\r\n"
		.'  AND (`sm`.`inventorycode` NOT LIKE \'fix_%\'  OR `sm`.`inventorycode` IS NULL) '."\r\n"
		.'  AND ( '."\r\n"
		.'				(`sm`.`type_mouvement` =3 AND `sm`.`value` < 0) '."\r\n"
		.'			OR	(`sm`.`type_mouvement` =2 AND `sm`.`value` > 0) '."\r\n"
		.'		) '
		;
if (!$res1 = $db->query($sql))
{
	dol_print_error($db);
	die;
}
$rows = array();
echo '<table>';
$icode = uniqid('fix_'.date('Ymd'));
$tikets = array();
$ct=1;
while ($row = $db->fetch_object($res1))
{
	$tk = str_replace ('Cancelación de Ticket ','',$row->label);
	if (!isset($tikets[$tk]))
	{
		$sql3 = 'SELECT rowid FROM llx_pos_ticket WHERE ticketnumber=\''.$tk.'\' LIMIT 1';
		
		
		if (!$res3 = $db->query($sql3))
		{
			dol_print_error($db);
			die();
		}
		$trowid = $db->fetch_object($res3);
		$tikets[$tk] = $trowid->rowid;
	}
	
	
	
	
	$static_stock_mov = new MouvementStock($db);
	if (isset($tikets[$tk]))
	{
		$static_stock_mov->setOrigin('ticket',$tikets[$tk]);
	}
	$static_stock_mov->_create($user,$row->fk_product,$row->fk_entrepot,($row->value * -1),$row->type_mouvement,0,'(-) '.$row->label,$icode.substr('00000'.(($ct*3)-1),-5),null,null,null,null,null,null,0);
	
	$static_stock_mov = new MouvementStock($db);
	if (isset($tikets[$tk]))
	{
		$static_stock_mov->setOrigin('ticket',$tikets[$tk]);
	}
	$static_stock_mov->_create($user,$row->fk_product,$row->fk_entrepot,($row->value * -1),$row->type_mouvement,0,'(+) '.$row->label,$icode.substr('00000'.($ct*3),-5),null,null,null,null,null,null,0);
	$sql2 = 'UPDATE `llx_stock_mouvement` SET '
			.'`inventorycode`=\''.$icode.substr('00000'.(($ct*3)-2),-5).'\' '
			;
	if (isset($tikets[$tk]))
	{
		$sql2 .= ', `origintype`=\'ticket\', `fk_origin`='.$tikets[$tk].' ';
	}
	$sql2 .='WHERE rowid='.$row->rowid;
	if (!$res2 = $db->query($sql2))
	{
		dol_print_error($db);
		die ('afsdfaf');
	}
	echo '<tr>'
		.'<td>'
		.'<a href="'.DOL_URL_ROOT.'/product/stock/movement_card.php?search_ref='.$row->rowid.'" target="_blank">'
		.$row->rowid
		.'</a>'
		.'</td>'
		.'<td>'
		.'<a href="'.DOL_URL_ROOT.'/product/stock/movement_card.php?search_inventorycode='.$icode.'&sortfield=m.inventorycode&sortorder=asc" target="_blank">'
		.$icode
		.'</a>'
		.'</td>'
		.'<td>'
		.$row->fk_product
		.'</td>'
		.'<td>'
		.$row->value
		.'</td>'
		.'</tr>'
		;
	$ct++;
}
echo '</table>';