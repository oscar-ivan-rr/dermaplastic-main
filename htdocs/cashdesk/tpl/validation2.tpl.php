<?php
/* Copyright (C) 2007-2008	Jeremie Ollivier	<jeremie.o@laposte.net>
 * Copyright (C) 2012       Marcos García       <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Load translation files required by the page
$langs->loadLangs(array("main","bills"));

?>

<div class="blocksellfinished">

<div class="cadre_facturation2">
<h3 class="titre1"><?php echo $langs->trans("Receta creada"); ?></h3><br>

<div class="center">
<br>
<p><a class="button lien1" style="color:white; font: normal normal bold 20px/30px Calibri; margin-bottom: 30px;" href="#" onclick="Javascript: popupTicket(<?php echo GETPOST('facid', 'int'); ?>,'<?php echo $langs->trans('Imprimir receta') ?>'); return(false);"><?php echo $langs->trans("Imprimir"); ?></a></p>
</div>
</div>
</div>
<br>
<script type="text/javascript">

	function popupTicket(id,name)
	{
		largeur = 600;
		hauteur = 500;
		opt = 'width='+largeur+', height='+hauteur+', left='+(screen.width - largeur)/2+', top='+(screen.height-hauteur)/2+'';
		window.open('validation_ticket.php?facid='+id,name, opt);
	}
</script>
