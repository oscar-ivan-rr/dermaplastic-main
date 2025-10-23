<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the barcodes of the GNU General Public License as published by
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
 */

/**
 *	\file       htdocs/takepos/ajax/ajax.php
 *	\brief      Ajax search component for TakePos. It search products of a category.
 */

if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/productbatch.class.php';

$action = GETPOST('action', 'alpha');
$barcode = GETPOST('barcode', 'san_alpha');
$whid = intval(GETPOST('warehouse', 'int'));

/*
 * View
 */

if ($action == 'search' && $barcode != '') {
	$batch_data = new Productbatch($db);
	$response = $batch_data->fetch(0, $barcode, $whid);

	if ($response > 0) {
		$status = array('status' => 'ok');

		// Convert $batch_data to an array
		$batch_data = (array) $batch_data;

		// Add $status to $batch_data
		$batch_data = $status + $batch_data;
		echo json_encode($batch_data);
	} else echo json_encode(array('status' => 'error', 'message' => 'Producto no encontrado'));
} else echo json_encode(array('status' => 'error', 'message' => 'Código de barras no encontrado'));
