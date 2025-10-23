<?php
require_once( '../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once('./by_warehouse.helper.php');


$id = GETPOST('id','integer');
$wid = GETPOST('wid','integer');
$usr = GETPOST('usr','integer');

if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}

$form = new Form($db);

if (!is_numeric($id) || !is_numeric($wid))
{
	die('ID no válido');
}

$sql =	 'SELECT `td`.`rowid`'."\r\n"
		.'		,`td`.`fk_ticket`'."\r\n"
		.'		,`td`.`fk_product`'."\r\n"
		.'		,`td`.`description`'."\r\n"
		.'		,`td`.`ls_warehouse_status`'."\r\n"
		.'		,`td`.`ls_warehouse_status_by`'."\r\n"
		.'		,`td`.`ls_warehouse_status_date`'."\r\n"
		.'		,`td`.`qty`'."\r\n"
		.'		,`td`.`qty_ent`'."\r\n"
		.'		,`t`.`ticketnumber`'."\r\n"
		.'FROM `'.MAIN_DB_PREFIX.'pos_ticketdet` AS `td`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'pos_ticket` AS `t`'."\r\n"
		.'  ON `td`.`fk_ticket` = `t`.`rowid`'."\r\n"
		.'WHERE `fk_ticket` = '.$id."\r\n"
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die();
}

if (!function_exists('ls_td_status'))
{
function ls_td_status($line,$st)
{
	$stValid = rc_getArrayOfEstadosV();
	$stList =	POS::getEstadovArray(null,'class');
	$stLabel =	POS::getEstadovArray(null,'label');
	$return =	 '<td id="dropdown_td_'.$line.'" class="'.$stList[$st].'" style="">'.$stLabel[$st].'</td>'."\r\n"
				;
  
  return $return;
}
}

if (!function_exists('getRevisedStock'))
{
function getRevisedStock(&$db,$prod,$warehouse)
{
	$sql = 'SELECT stock_revised FROM llx_product_stock_revised WHERE fk_product='.$prod.' AND fk_warehouse='.$warehouse;
	if (!$res = $db->query($sql))
	{
		dol_print_error($db); die();
	}
	if (!$row = $db->fetch_object($res))
	{
		return '';
	}
	else
	{
		return $row->stock_revised;
	}
}	
}

if (!function_exists('rc_getArrayOfEstadosV'))
{
function rc_getArrayOfEstadosV()
{
	global $conf,$user;
	$estados_v = POS::getEstadovArray('constant','label');
	$estados_k = POS::getEstadovArray('constant','numeric');
	$perms = array();
	$i=0;
	foreach ($estados_v as $k => $v)
	{
		$var = 'POS_ALMACEN_SIN_PERMISO_DE_'.$k;
		if (property_exists($conf->global,$var) && strlen($conf->global->$var))
		{
			$perms[$estados_k[$k]] = explode(',',$conf->global->$var);
		}
		else
		{
			$perms[$estados_k[$k]] = array($estados_k[$k]);
		}
		$i++;
	}
	if (
			is_object($user)
		&&	is_object($user->rights)
		&&	is_object($user->rights->produit)
		&&	property_exists($user->rights->produit,'special_status')
		&&	$user->rights->produit->special_status
		)
	{
		$i=0;
		foreach ($estados_v as $k => $v)
		{
			$var = 'POS_ALMACEN_CON_PERMISO_DE_'.$k;
			if (property_exists($conf->global,$var) && strlen($conf->global->$var))
			{
				$perms[$estados_k[$k]] = explode(',',$conf->global->$var);
			}
			$i++;
		}
	}
	return $perms;
	
}
	
}


?>
<style>


</style>

<div style="display: block; overflow: auto;">
	<div style="display: block;overflow: hidden; height: 30px;padding:5px;font-weight:bold;">
		Detalle del Ticket<span style="float: right;"><a href="#" onclick="$('.modal').modal('close'); return false;">X</a></span>
	</div>
	<div style="padding-top: 30px; border:5px solid #999;margin: 3px 10px;">
		<h3 id="this-modal-title" style="font-size: 20px;padding-left: 10px;"></h3>
		<table class="noborder centpercent">
			<thead>
				<tr class="liste_titre">
					<th>Ref.</th>
					<th>Descripción</th>
					<th>Cantidad Pedida</th>
					<th>Stock Físico</th>
					<th>Stock Revisado</th>
					<th>Ubicación</th>
					<th>Estado V.</th>
					<th>Hora de Asignación</th>
				</tr>
			</thead>
			<tbody>
				<?php while($row = $db->fetch_object($res)): ?>
					<?php $static_product = new Product($db); ?>
					<?php $static_product->fetch($row->fk_product); ?> 
					<?php $static_product->load_stock(); ?>
					<?php $ticketnumber = $row->ticketnumber; ?>
				<tr>
					<td><a target="_blank" href="<?php echo DOL_URL_ROOT;?>/product/card.php?id=<?php echo $static_product->id; ?>&save_lastsearch_values=1&mainmenu=products&leftmenu=stock"><?php echo $static_product->ref; ?></a></td>
					<td><?php echo strlen(trim($row->description)) ? trim($row->description) : $static_product->label; ?></td>
					<td><?php echo $row->qty_ent; ?></td>
					<td id="stock_real_<?php echo $row->fk_product; ?>"><?php echo isset($static_product->stock_warehouse[$wid]) ? $static_product->stock_warehouse[$wid]->real : '0'; ?></td>
					<td>
					</td>
					<td></td>
					<?php echo ls_td_status($row->rowid, $row->ls_warehouse_status); ?>
					<td id="td_row_date_<?php echo $row->rowid; ?>"><?php echo date('Y-m-d H:i:s',strtotime($row->ls_warehouse_status_date)+(date('Z'))); ?></td>
				</tr>
				<?php endwhile; ?>
				<script>
					$('#this-modal-title').text('<?php echo $ticketnumber; ?>'); 
				</script>
			</tbody>
		</table>
	</div>
</div>
