<?php
require '../../main.inc.php';
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}

$id = GETPOST('id','integer');
$wid = GETPOST('wid','integer');
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
		.'FROM `'.MAIN_DB_PREFIX.'pos_ticketdet_deleted` AS `td`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'pos_ticket` AS `t`'."\r\n"
		.'  ON `td`.`fk_ticket` = `t`.`rowid`'."\r\n"
		.'WHERE `fk_ticket` = '.$id."\r\n"
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die();
}

function ls_select_status($line,$st)
{
	$stValid = rc_getArrayOfEstadosV();
	$stList =	POS::getEstadovArray(null,'class');
	$stLabel =	POS::getEstadovArray(null,'label');
	
	$return =	 '<a id="dropdown_link_'.$line.'" class="dropdown-trigger btn btn-small '.$stList[$st].'" style="width:100%;" href="#" data-target="dropdown'.$line.'">'.$stLabel[$st].'</a>'."\r\n"
				.'<ul id="dropdown'.$line.'" class="dropdown-content">'."\r\n"
				;
	for($i=0,$n=count($stList);$i<$n;$i++)
	{
		if (!in_array($i,$stValid[$st]))
		{
			continue;
		}
		$return .=	 '<li>'
					.'<a '
					.	'href="#" '
					.	'class="'.$stList[$i].'" '
					.	'onclick="return ls_update_status('.$line.','.$i.');" '
					.'>'
					.$stLabel[$i]
					.'</a>'
					.'</li>'."\r\n"
					;
	}
				
		$return .=	'</ul>'
  				;
  
  return $return;
}

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

function rc_getArrayOfEstadosV()
{
	global $conf,$user;
	$estados_v = POS::getEstadovArray('constant','label');
	$perms = array();
	$i=0;
	foreach ($estados_v as $k => $v)
	{
		$var = 'POS_ALMACEN_SIN_PERMISO_DE_'.$k;
		if (property_exists($conf->global,$var) && strlen($conf->global->$var))
		{
			$perms[$i] = explode(',','0,'.$i);
		}
		else
		{
			$perms[$i] = array('0',$i);
		}
		$i++;
	}
	return $perms;
	
}
?>
<div style="display: block; overflow: auto;">
	<div style="display: block;overflow: hidden; height: 30px;background-color: #ccc;padding:5px;font-weight:bold;">
		Gestión de Envíos a Mostrador <span style="float: right;"><a href="#" onclick="$('.modal').modal('close'); return false;">X</a></span>
	</div>
	<div style="padding-top: 30px; border:5px solid #999;margin: 3px 10px;">
		<h3 id="this-modal-title" style="font-size: 20px;padding-left: 10px;"></h3>
		<table class="noborder centpercent">
			<thead>
				<tr class="liste_titre">
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
					<td><?php echo strlen(trim($row->description)) ? trim($row->description) : $static_product->label; ?></td>
					<td><?php echo $row->qty_ent; ?></td>
					<td id="stock_real_<?php echo $row->fk_product; ?>"><?php echo isset($static_product->stock_warehouse[$wid]) ? $static_product->stock_warehouse[$wid]->real : '0'; ?></td>
					<td>
						<div style="display: none;" class="edit_stock_form" id="edit_stock_form_<?php echo $row->fk_product; ?>">
							<input value="<?php echo getRevisedStock($db,$row->fk_product,$wid); ?>" type="number" min="0" style="width: 100px;" class="edit_stock_input" id="edit_stock_input_<?php echo $row->fk_product; ?>" />
							<a href="#" onclick="return ls_edit_stock_save(<?php echo $id.','.$wid.','.$row->fk_product.','.$row->rowid; ?>);" class="btn btn-small" title="Guardar">
								<i class="material-icons">save</i>						
							</a>
						</div>
						<div style="display: block;" class="edit_stock_button" id="edit_stock_button_<?php echo $row->fk_product; ?>">
							<div style="display: inline-block;" id="stock_revised_<?php echo $row->fk_product; ?>"><?php echo getRevisedStock($db,$row->fk_product,$wid); ?></div>
							<a 
								class="editfielda" 
								href="#" 
								onclick="return ls_edit_stock(<?php echo $id.','.$wid.','.$row->fk_product; ?>);"
							>
								<span 
									class="fas fa-pencil-alt marginleftonly" 
									style=" color: #444;  float: right" 
									title="Editar">
								</span>
							</a>
						</div>
					</td>
					<td></td>
					<td><?php echo ls_select_status($row->rowid, $row->ls_warehouse_status); ?></td>
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
<script>
var stCls=['<?php echo implode("'\r\n\t,'",POS::getEstadovArray(null,'class')) ?>'];
var stLbl=['<?php echo implode("'\r\n\t,'",POS::getEstadovArray(null,'label')) ?>'];

function ls_edit_stock(tk,wh,prod)
{
	$('.edit_stock_button').show();
	$('.edit_stock_form').hide();
	//$('.edit_stock_input').val('');
	$('#edit_stock_button_'+prod).hide();
	$('#edit_stock_form_'+prod).show();
	return false;
}

function ls_update_status(ln,st)
{
	var data = {'line':ln,'status':st,'action':'updateOrphanLineEstadoV'};
	$.ajax({
		'url': '<?php echo DOL_URL_ROOT.'/product/custom/by_warehouse_ajax.php';?>',
		'type': "post",
		'data': data,
		'success': function(result){
			var res = JSON.parse(result);
			if (res.status == 'OK')
			{
				$('#dropdown_link_'+ln).html(stLbl[st]);
				$('#dropdown_link_'+ln).removeClass();
				$('#dropdown_link_'+ln).addClass('dropdown-trigger btn btn-small');
				$('#dropdown_link_'+ln).addClass(stCls[st]);
				$('#dropdown_link_'+ln).blur();
				$('#td_row_date_'+ln).html(res.date); 
				
			}
			else
			{
				alert(res['status'] +': '+res.msg[0]);
			}
			
		},
		'error': function (resutl){
			alert('No fue posible guardar los cambios. Intente nuevamente.'+"\r\n\r\n"+resutl.responseText)
		}
	});
	 
	return false;
}

function ls_edit_stock_save(tk,wh,prod,lid)
{
	var stk = $('#edit_stock_input_'+prod).val();
	var data = {'action':'saveStock','ticket':tk,'warehouse':wh,'product':prod,'stock':stk,'line':lid};
	$.ajax({
		'url': '<?php echo DOL_URL_ROOT.'/product/custom/by_warehouse_ajax.php';?>',
		'type': "post",
		'data': data,
		'success': function(result){
			var res = JSON.parse(result);
			if (res.status == 'OK')
			{
				$('.edit_stock_button').show();
				$('.edit_stock_form').hide();
				//$('.edit_stock_input').val('');
				$('#edit_stock_button_'+prod).show();
				$('#edit_stock_form_'+prod).hide();
				$('#stock_revised_'+prod).html('<span style="color:#090;">'+stk+'</span>')
			}
			else
			{
				alert(res['status'] +': '+res.msg[0]);
			}
			
		},
		'error': function (resutl){
			alert('No fue posible guardar los cambios. Intente nuevamente.'+"\r\n\r\n"+resutl.responseText)
		}
	});
	rc_reload_modal();
	 
	return false;
}


</script>