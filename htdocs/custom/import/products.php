<?php

/**
 * @author lolkittens
 * @copyright 2020
 */

set_time_limit(0);

$mtStart = microtime(true);

require '../../main.inc.php';
require_once('./class/product.helper.php');

importProductHelper::saveFile();


$ct = 0;
$cc = 0;
$cu = 0;
$rc = array();
$er = array();



while(($row = importProductHelper::getCsvRow())/* && $ct < 100 */)
{
	$prodId = importProductHelper::getProductId($row);
	if ($prodId)
	{
		if(importProductHelper::updateProduct($prodId,$row))
		{
			$cu++;
		}
		else
		{
			$er[] = $row;
		}
	}
	else
	{
		if (importProductHelper::createProduct($row))
		{
			$rc[] = $row[importProductHelper::$properties['base']['ref']];
			$cc++;
		}
		else
		{
			$er[] = $row;
		}
	}
	$ct++;
}

llxHeader("", 'Importación de Productos', '');
?>
<h2>Importación de Productos</h2>
<?php if ($cc): ?>
<h3><?php echo number_format($cc,'0','.',','); ?> nuevo(s) producto(s)</h3>
<ul>
	<?php foreach($rc as $rec): ?>
		<li><?php echo $rec; ?></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($cu): ?>
<h3><?php echo number_format($cu,'0','.',','); ?> producto(s) actualizado(s)</h3>
<?php endif; ?>

<?php if (count($er)): ?>
<h3><?php echo number_format(count($er),'0','.',','); ?> error(es)</h3>
<h4>Registro de Errores</h4>
<table>
	<?php foreach($er as $e): ?>
	<tr>
		<?php foreach ($e as $col): ?>
			<td><?php echo $col; ?></td>
		<?php endforeach; ?> 
	</tr>
	<?php endforeach; ?> 
</table>
<?php endif; ?>
<h3>Total <?php echo number_format($ct,'0','.',','); ?> registro(s) procesado(s) en <?php echo number_format(microtime(true)-$mtStart,4,'.',',');?> segundos.</h3>
<a class="button" href="<?php echo DOL_URL_ROOT; ?>/custom/import/index.php?element=product&type=1&mainmenu=products&leftmenu=">Regresar</a>
<?php
llxFooter();
$db->close();

