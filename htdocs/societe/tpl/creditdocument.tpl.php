<?php 

if (empty($langs) || !is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


$langs->load("link");
if (empty($relativepathwithnofile)) $relativepathwithnofile='';

foreach ($creditfilearray as $k => $v )
{
	foreach($files as $fk => $fv)
	{
		$rex_ptrn	= '/^'.$fk.'_([\d]{4})_([\d]{2})_([\d]{2})_([\d]{1,})/i';
		if (preg_match($rex_ptrn,$creditfilearray[$k]['name'],$MATCHES))
		{
			$v['doc_date']	= mktime(0,0,0,$MATCHES[2],$MATCHES[3],$MATCHES[1]);
			$v['expires']	= $expire[$fk] != '-1' ? strtotime($expire[$fk], $v['doc_date']) : '' ;
			$v['valid_for']	= $vals[$expire[$fk]];
			$v['number']	= $MATCHES[4];
			$files[$fk][]	= $v;
			if ($MATCHES[4] > $last[$fk])
			{
				$last[$fk] = $MATCHES[4]; 
			}
			break;
		}
	}
}

?>
<table style="width:100%" id="tablelines" class="centpercent notopnoleftnoright table-fiche-title ">
	<tbody>
	<?php foreach($files as $k => $v): ?>
		<?php $tmpSelName = 'SOCIETE_CREDIT_DOCS_EXPIRATION_'.strtoupper($k).'_NOTIFICATION'; ?>
		<?php $days = ( is_numeric($conf->global->$tmpSelName) && $conf->global->$tmpSelName > 0 ) ? $conf->global->$tmpSelName : '30'; ?>
		<?php $param = '&show='.$k; ?>
		<?php $shHi	 = GETPOST('show') == $k ? 'normal':'none'; ?>
		<?php $unikId = '_'.$k; ?>
		<?php $expired = false; ?>
		<?php $hasFile = (count($files[$k])> 0)  ? true:false; ?>  
		<?php foreach($files[$k] as $disk_file): ?>
		<?php if (strlen($disk_file['expires']) && $disk_file['expires'] < time()
				|| strlen($disk_file['expires']) && strtotime('-'.$days.' days', $disk_file['expires']) < time()
				){$expired = true; break;} ?>
		<?php endforeach; ?>
	<tr<?php echo ($expired)?'':''; ?>>
		<td class="nobordernopadding widthpictotitle valignmiddle col-picto">
			<a id="row_<?php echo $k;?>" style="display: block;position: relative;top: -70px;visibility: hidden;"></a>
			<span style="color:#<?php echo ($expired || !$hasFile)?'900':'090'; ?>  ;" class="opacitymedium fas <?php echo ($expired || !$hasFile)?'fa-times':'fa-check'; ?> valignmiddle widthpictotitle pictotitle"></span></td>
		<td colspan="5" class="nobordernopadding widthpictotitle valignmiddle col-picto">
			<div class="titre inline-block"><?php echo $titles[$k]; ?></div>
		</td>
		<td class="nobordernopadding widthpictotitle valignmiddle col-picto">
			<div class="titre hide<?php echo $unikId;?>" style="display: <?php echo $shHi; ?>;">
				<a href="#" title="Ver lista simple" onclick="javascript:$('.showhide<?php echo $unikId;?>').slideUp('fast');$('.show<?php echo $unikId;?>').show('fase');$('.hide<?php echo $unikId;?>').hide('slow');return false;"><span class="fas fa-minus-circle"></span></a>
			</div>
			<div class="titre show<?php echo $unikId;?>" style="display: <?php echo $shHi=='normal' ?'none':'normal'; ?>">
				<a href="#" title="Ver los detalles" onclick="javascript:$('.showhide<?php echo $unikId;?>').slideDown('fast');$('.hide<?php echo $unikId;?>').show('fast');$('.show<?php echo $unikId;?>').hide('slow');return false;"><span class="fas fa-plus-circle"></span></a>
			</div>
		</td>
	</tr>
	<?php if (count($files[$k])): ?>
	<tr class="liste_titre nodrag nodrop showhide<?php echo $unikId;?>" style="display: <?php echo $shHi; ?>;">
		<td></td>
		<td>Documento</td>
		<td>Fecha Subido</td>
		<td>Fecha del Documento</td>
		<td>Vigencia (<?php echo $v[0]['valid_for']; ?>)</td>
		<td>Tamaño</td>
		<td></td>
	</tr>
	<?php foreach($files[$k] as $disk_file): ?>
	<tr class="showhide<?php echo $unikId;?>" style="display: <?php echo $shHi; ?>;">
		<td></td>
		<td><a class="paddingright" 
			href="<?php echo DOL_URL_ROOT
						.'/document.php?modulepart='
						.$modulepart;?>&attachment=1<?php
						if (!empty($object->entity)) print '&entity='.$object->entity;?><?php
						print '&file='.urlencode('credit/'.$object->id.'/'.$disk_file['name']);?>">
			<?php echo img_mime($disk_file['name'], $titles[$k].'_'.$disk_file['number'].' ('.dol_print_size($disk_file['size'], 0, 0).')', 'inline-block valignbottom paddingright');?>			
			<?php echo dol_trunc($titles[$k].'_'.$disk_file['number'], 200);?></a>
			<?php echo $formfile->showPreview($disk_file, $modulepart, 'credit/'.$object->id.'/'.$disk_file['name'], 0, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));?>
		</td>
		<td><?php echo date('Y-m-d H:i:s',$disk_file['date']); ?></td>
		<td><?php echo date('Y-m-d',$disk_file['doc_date']); ?></td>
		<td<?php if (strlen($disk_file['expires']) && $disk_file['expires'] < time())
				{
					echo ' style="color:#c00;"';
				}
				elseif (strlen($disk_file['expires']) && strtotime('-'.$days.' days', $disk_file['expires']) < time())
				{
					echo ' style="color:#c90;"';
				}
		 ?>><?php echo is_numeric($disk_file['expires']) ? date('Y-m-d H:i:s',$disk_file['expires']) : $disk_file['expires']; ?></td>
		<td><?php echo dol_print_size($disk_file['size'],1,1); ?></td>
		<td>
			<a href="<?php echo ($_SERVER['PHP_SELF'].'?socid='.$object->id.'&action=delete&urlfile='.urlencode( 'credit/'.$object->id.'/'.$disk_file['name']).$param).'" class="reposition deletefilelink" rel="'.$filepath;?>"><?php echo img_delete(); ?></a>
		</td>
	</tr>
	<?php endforeach; ?>
	<?php else: ?>
	<tr class="oddeven showhide<?php echo $unikId;?>" style="display: <?php echo $shHi; ?>;">
		<td></td>
		<td colspan="6" class="opacitymedium"><?php echo $langs->trans("NoFileFound"); ?></td></tr>
	<?php endif; ?>
	<tr class="showhide<?php echo $unikId;?>" style="display: <?php echo $shHi; ?>;">
		<td></td>
		<td colspan="6">
			<?php $form_date = '<label for="file_doc_date_'.$k.'">Fecha del Documento</label> <br/>'.$form->selectDate('','file_doc_date_'.$k); ?>
			<?php $formfile->form_attach_new_file($_SERVER['PHP_SELF'].'?action=upload&socid='.$id.'&show='.$k,'Agregar '.$titles[$k],0,0,1,50,$object,$form_date.'<input type="hidden" name="file_type" value="'.$k.'" /><input type="hidden" name="file_number" value="'.($last[$k]+1).'" />',0,'',0,'form_'.$k,'pdf,image/*'); ?>
		</td>
	</tr>
	<?php endforeach; ?>
	</tbody>
</table>

