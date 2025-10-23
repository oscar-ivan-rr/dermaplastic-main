<?php

if (strpos($action,'set_credit_docs_expiration_') !== false)
{
	$aKey = str_replace('set_credit_docs_expiration_','',$action);
	$cNam = 'SOCIETE_CREDIT_DOCS_EXPIRATION_'.strtoupper($aKey);
	if (
		dolibarr_set_const($db, $cNam, GETPOST($cNam), 'chaine', 0, '', $conf->entity) > 0
		&& dolibarr_set_const($db, $cNam.'_NOTIFICATION', GETPOST($cNam.'_NOTIFICATION','int'), 'chaine', 0, '', $conf->entity) > 0
		)
	{
		echo '<script>'
			.'window.location.replace('
			.'\''.$_SERVER['PHP_SELF'].'#SOCIETE_CREDIT_DOCS_EXPIRATION_'.strtoupper($aKey).'_TR_ID\''
			.');'
			.'</script>';
		//header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

print load_fiche_titre($langs->trans("Vigencia en Documentos de Crédito"), '', '');

$titles = array(
				 'address'		=> 'Comprobante de Domicilio'
				,'photo_out'	=> 'Fotos del inmueble (exterior)'
				,'photo_in'		=> 'Fotos del inmueble (interior)'
				,'bank'			=> 'Estado de cuenta'
				,'irs'			=> 'Alta de Hacienda'
				,'acta'			=> 'Acta Constitutiva' 
				,'rfc'			=> 'RFC'
				,'const_sat'	=> 'Constancia de Situación Fiscal'
				,'opinion_sat'	=> 'Opinión del SAT'
				,'ref'			=> 'Cartas de referencia'
				,'auth'			=> 'Relación de personas autorizadas para compra y recepción de mercancía'
				,'ine_al'		=> 'INE del apoderado legal'
				,'ine_ph'		=> 'INE del encargado de compras'
				,'ine_pay'		=> 'INE del encargado de pagos ' 
				,'prom'			=> 'Pagaré firmado'
				);
				
# Cambiar también en htdocs/societe/credit_document.php 
#					 htdocs/societe/class/societe.class.php
$vals	= array(
				 '-1'			=> 'No Expira'
				,'+1 month'		=> '1 Mes'
				,'+2 months'	=> '2 Meses'
				,'+3 months'	=> '3 Meses'
				,'+4 months'	=> '4 Meses'
				,'+5 months'	=> '5 Meses'
				,'+6 months'	=> '6 Meses'
				,'+7 months'	=> '7 Meses'
				,'+8 months'	=> '8 Meses'
				,'+9 months'	=> '9 Meses'
				,'+10 months'	=> '10 Meses'
				,'+11 months'	=> '11 Meses'
				,'+12 months'	=> '12 Meses'
				);
?>
<table class="noborder centpercent">
	<tbody>
		<tr class="liste_titre">
			<td class="right" style="width: 30%;">Documento</td>
			<td>Vigencia</td>
			<td>Alerta anticipada</td>
		</tr>
		<?php foreach ($titles as $k => $v): ?>
		<?php $tmpSelName = 'SOCIETE_CREDIT_DOCS_EXPIRATION_'.strtoupper($k);?>
		<tr>
			<td class="right">
				<a id="<?php echo $tmpSelName.'_TR_ID'; ?>" style="display: block;position: relative;top: -70px;visibility: hidden;"></a>
				<?php echo $v;?>
			</td>
			<td>
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>?dummy=" method="post">
					<select name="<?php echo $tmpSelName ;?>" onchange="this.form.submit()">
						<?php foreach($vals as $vk => $vv): ?>
						<?php $tmpSelSel = (property_exists($conf->global,$tmpSelName) && $conf->global->$tmpSelName == $vk) ? ' selected="selected"' :''; ?>
						<option value="<?php echo $vk; ?>"<?php echo $tmpSelSel; ?>><?php echo $vv; ?></option>
						<?php endforeach; ?>
					</select>
					<input type="hidden" name="action" value="set_credit_docs_expiration_<?php echo $k; ?>" />
			</td>
			<td><?php $tmpNotName = $tmpSelName.'_NOTIFICATION'; ?>
				<input type="number" name="<?php echo $tmpNotName ;?>" value="<?php echo(property_exists($conf->global,$tmpNotName))? $conf->global->$tmpNotName: '';  ?>" step="1" min="0" max="90" style="text-align: right;width:100px;" />
				días
				<button type="submit" class="btn" value="Guardar">Guardar</button>
			</td>
				</form>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>