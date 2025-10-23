<?php
require '../../main.inc.php';
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once('./by_warehouse.helper.php');


$id = GETPOST('id','integer');
$wid = GETPOST('wid','integer');

if (estadovLockHelper::isLockedUp($user,$db,$id))
{
	require('./by_warehouse_det_locked.php');
	die();
}
estadovLockHelper::updateLockUp($user,$db,$id);

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
		.'		,`t`.`rowid` AS `ticket_id`'."\r\n"
		.'		,`t`.`ticketnumber`'."\r\n"
		.'		,`td`.`ls_stock_dec_qty`'."\r\n"
		.'		,`td`.`ls_stock_dec_date`'."\r\n"
		.'		,sum(`sm`.`qty`) AS delivered'."\r\n"
		.'		,lp.location_matriz as location'."\r\n"
		.'FROM `'.MAIN_DB_PREFIX.'pos_ticketdet` AS `td`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'pos_ticket` AS `t`'."\r\n"
		.'  ON `td`.`fk_ticket` = `t`.`rowid`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'pos_stock_mouvement` AS `sm`'."\r\n"
		.'  ON `t`.`rowid` = `sm`.`fk_ticket` AND `td`.`fk_product` = `sm`.`fk_product`'."\r\n"
		.'left join llx_product lp on lp.rowid = `td`.fk_product '."\r\n"
		.'WHERE `td`.`fk_ticket` = '.$id."\r\n"
		.'GROUP BY `td`.`fk_product` '."\r\n"
		.'ORDER BY `td`.`rowid` DESC '."\r\n"
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die();
}
if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}

function ls_select_status($line,$st)
{
	$stValid = rc_getArrayOfEstadosV();

	$stList =	POS::getEstadovArray(null,'class');
	$stLabel =	POS::getEstadovArray();
	$return =	 '<a id="dropdown_link_'.$line.'" class="dropdown-trigger btn btn-small '.$stList[$st].'" style="width:100%;" href="#" data-target="dropdown'.$line.'">'.$stLabel[$st].'</a>'."\r\n"
				.'<ul id="dropdown'.$line.'" class="dropdown-content">'."\r\n"
				;
	foreach($stLabel as $i => $val)
	{
		if (!in_array((string)$i,$stValid[$st]))
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
	$estados_k = POS::getEstadovArray('constant','numeric');
	$perms = array();
	$i=0;
	foreach ($estados_v as $k => $v)
	{
		$var = 'POS_ALMACEN_SIN_PERMISO_DE_'.$k;
		if (property_exists($conf->global,$var) && strlen($conf->global->$var))
		{
			$perms[$estados_k[$k]] = explode(',',$estados_k[$k].','.$conf->global->$var);
		}
		else
		{
			$perms[$estados_k[$k]] = array((string)$estados_k[$k]);
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
				$perms[$estados_k[$k]] = explode(',',$estados_k[$k].','.$conf->global->$var);
			}
			$i++;
		}
	}
	return $perms;
	
}


?>
<div style="display: block; overflow: auto;">
	<div style="display: block;overflow: hidden; height: 30px;background-color: #ccc;padding:5px;font-weight:bold;">
		GESTIÓN DE ENVÍOS DE ALMACÉN <span style="float: right;"><a href="#" onclick="$('.modal').modal('close'); return false;">X</a></span>
	</div>
	<div style="padding-top: 30px; border:5px solid #999;margin: 3px 10px;">
		<h3 id="this-modal-title" style="font-size: 20px;padding-left: 10px;"></h3>
		<table class="noborder centpercent">
			<thead>
				<tr class="liste_titre">
					<th>Ref.</th>
					<th>Desc.</th>
					<th style="text-align: center;">Cant.</th>
					<th style="text-align: center;">Entregada</th>
					<th style="text-align: center;">Stock Físico</th>
					<th style="text-align: center;">Stock Revisado</th>
					<th style="text-align: center;">Ubicación</th>
					<th style="text-align: center;">Estado V.</th>
					<th style="text-align: center;">Asignado</th>
					<?php if ($user->rights->produit->reset_front_desk_shipments): ?>
					<th>&nbsp;</th>
					<?php endif;?>
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
					<td style="text-align: center;"><?php echo $row->qty; ?></td>
<!--
					<td></td>
-->					<td style="text-align: center;"><input 
							type="number" 
							value="<?php echo $row->qty_ent; ?>" 
							style="width: 45px;" 
							name="rc_this_delivery_<?php echo $row->rowid ?>" 
							<?php echo (is_null($row->ls_warehouse_status))?' readonly="readony"':''; ?>
							id="rc_this_delivery_<?php echo $row->rowid ?>" /></td>
					<td id="stock_real_<?php echo $row->fk_product; ?>" style="text-align: center;">
						<?php echo isset($static_product->stock_warehouse[$wid]) ? $static_product->stock_warehouse[$wid]->real : '0'; ?>
						<?php if (!is_null($row->ls_stock_dec_qty)) : ?>
							<i 
								class="fa fa-flag hastooltip" 
								style="margin-left: 10px;color: rgba(0,50,150,0.7);font-size: 13px;"
								title="Este stock ya incluye la baja de <?php echo $row->ls_stock_dec_qty; ?> unidad(es) de este ticket<?php echo (strlen($row->ls_stock_dec_date)?' ('.$row->ls_stock_dec_date.')':'') ?>"
							></i>
						<?php endif; ?>
					</td>
					<td  style="text-align: center;">
						<?php $por_revisar = getRevisedStock($db,$row->fk_product,$wid)>0?false:true; ?>
						<div style="display: <?php echo $por_revisar?'block':'none';?>;" class="edit_stock_form" id="edit_stock_form_<?php echo $row->fk_product; ?>">
							<input value="1" type="hidden" class="edit_stock_input" id="edit_stock_input_<?php echo $row->fk_product; ?>" />
							<a href="#" onclick="return ls_edit_stock_save(<?php echo $id.','.$wid.','.$row->fk_product.','.$row->rowid; ?>);" class="btn btn-small" title="Informar Sobre Diferencia de Stock">
								<i class="material-icons">chat</i>						
							</a>
						</div>
						<div style="display: <?php echo $por_revisar?'none':'block';?>;" class="edit_stock_button" id="edit_stock_button_<?php echo $row->fk_product; ?>">
							<div style="display: inline-block;" id="stock_revised_<?php echo $row->fk_product; ?>"></div>
							<a 
								class="editfielda" 
								href="#" 
								onclick="return false;"
								title="Se ha registrado una diferencia de stock"								
							>
								<i class="material-icons" style=" color:#900;">info</i>
							</a>
						</div>
					</td>
					<td style="text-align: center;">
						<!-- creamos un div centrado para almacenar la ubicación -->
						<div >
							<!-- creamos un div para almacenar la ubicación -->
							<div id="location_<?php echo $row->fk_product; ?>" style="display: inline-block;">
								<?php echo $row->location?$row->location:'N/A'; ?>
							</div>
							<!-- creamos un div para almacenar el botón de edición -->
							<div style="display: inline-block;">
								<!-- creamos un botón para editar la ubicación -->
								<a 
									class="editfielda" 
									id="edit_location_<?php echo $row->fk_product; ?>"
									href="#" 
									onclick="return ls_edit_location(<?php echo $row->fk_product; ?>);" 
									title="Editar Ubicación"
								>
									<i class="material-icons">edit</i>
								</a>
							</div>
						<?php //echo $row->location?$row->location:'N/A'; ?>
					</td>
					<td style="text-align: center;"><?php echo ls_select_status($row->rowid, $row->ls_warehouse_status); ?></td>
					<td style="text-align: center;" id="td_row_date_<?php echo $row->rowid; ?>"><?php echo date('Y-m-d H:i:s',strtotime($row->ls_warehouse_status_date)+(date('Z'))); ?></td>
					<?php if ($user->rights->produit->reset_front_desk_shipments): ?>
					<td style="text-align: center;"><a style="background-color:#ccc;" class="btn bnt-small" title="Re-iniciar partida" onclick="return reset_ticket_line(<?php echo $row->fk_product; ?>,<?php echo $row->ticket_id; ?>,<?php echo $row->rowid; ?>);"><i class="fa fa-undo"></i></a></td>
					<?php endif; ?>
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
var stCls={};
<?php foreach (POS::getEstadovArray(null,'class') as $evk=>$evl): ?>
stCls['<?php echo $evk?>'] = '<?php echo $evl;?>'; 
<?php endforeach;?>
var stLbl={};
<?php foreach (POS::getEstadovArray(null,'label') as $evk=>$evl): ?>
stLbl['<?php echo $evk?>'] = '<?php echo $evl;?>'; 
<?php endforeach;?>

// creamos la funciona para editar la ubicación
function ls_edit_location(id)
{
	// creamos un div para almacenar el formulario
	var div = $('<div id="div_edit_location_'+id+'"></div>');
	// creamos un input para almacenar la ubicación
	var input = $('<input type="text" class="edit_location_input text-center" id="edit_location_input_'+id+'" />');
	// creamos un botón para guardar la ubicación
	var button = $('<a href="#" onclick="return ls_edit_location_save('+id+');" class="btn btn-small mr-2" title="Guardar Ubicación"><i class="material-icons">save</i></a>');
	// creamos un botón para cancelar la edición
	var button_cancel = $('<a href="#" onclick="return ls_edit_location_cancel('+id+');" class="btn btn-small" title="Cancelar"><i class="material-icons">cancel</i></a>');
	// creamos un div para almacenar los botones
	var div_buttons = $('<div></div>');
	// agregamos los botones al div
	div_buttons.append(button);
	div_buttons.append(button_cancel);
	// agregamos el input y los botones al div
	div.append(input);
	div.append(div_buttons);
	// obtenemos el valor de la ubicación
	var location = $('#location_'+id).text().trim();
	// agregamos el valor de la ubicación al input
	input.val(location);
	// ocultamos el div de la ubicación
	$('#location_'+id).hide();
	// ocultamos el botón de edición
	$('#edit_location_'+id).hide();
	// agregamos el div al td de la ubicación
	$('#location_'+id).parent().append(div);
	// seleccionamos el input
	// input.select();
	// retornamos false
	return false;
}

// creamos la función para guardar la ubicación
function ls_edit_location_save(id){
	let ubicacion = $('#edit_location_input_'+id).val();	
	console.log('id: '+id+' ubicacion: '+ubicacion);
	// con php hacemos un update de la ubicación
	$.ajax({
		'url': '<?php echo DOL_URL_ROOT.'/product/custom/by_warehouse_ajax.php';?>',
		'type': "post",
		'data': {'action':'editLocation','id':id,'location':ubicacion},
		'success': function(data){
			// convertimos el resultado en json para poder usarlo
			data = JSON.parse(data);
			
			// si el resultado es 1
			if(data.result == 1){
				// mostramos el div de la ubicación
				$('#location_'+id).show();
				// mostramos el botón de edición
				$('#edit_location_'+id).show();
				// eliminamos el div del formulario
				$('#div_edit_location_'+id).remove();
				// mostramos el valor de la ubicación
				$('#location_'+id).text(ubicacion);
			}
			// si el resultado es 0
			else{
				// mostramos un mensaje de error
				alert('No fue posible guardar los cambios. Intente nuevamente.');
			}
		},
		'error': function (resutl){
			// mostramos un mensaje de error
			alert('No fue posible guardar los cambios. Intente nuevamente.'+"\r\n\r\n"+resutl.responseText)
		}
	});
}

// creamos la función para cancelar la edición de la ubicación
function ls_edit_location_cancel(id){
	// mostramos el div de la ubicación
	$('#location_'+id).show();
	// mostramos el botón de edición
	$('#edit_location_'+id).show();
	// eliminamos el div del formulario
	$('#div_edit_location_'+id).remove();
	// retornamos false
	return false;
}

function reset_ticket_line(pr,tk,ln)
{
	if (confirm('¿Desea reiniciar Estado V. y movimientos de stock para esta partida?'))
	{
		var data = {'product_id':pr,'ticket':tk,'line':ln,'action':'resetLineEstadoV'};
		$.ajax({
			'url': '<?php echo DOL_URL_ROOT.'/product/custom/by_warehouse_ajax.php';?>',
			'type': "post",
			'data': data,
			'success': function(result){
				rc_reload_modal();
				},
			'error': function (resutl){
				alert('No fue posible guardar los cambios. Intente nuevamente.'+"\r\n\r\n"+resutl.responseText)
				}
		});
	}
	return false;
}

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
	var data = {'line':ln,'status':st,'action':'updateLineEstadoV','qty_ent':$('#rc_this_delivery_'+ln).val()};
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