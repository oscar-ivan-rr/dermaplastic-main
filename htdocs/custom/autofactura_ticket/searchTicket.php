<?php
require_once '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/autofactura_ticket/lib/fecha_facturacion.lib.php';

if (!empty($_POST['folio_ticket']) && !empty($_POST['monto'])) {
	global $db;

	$folioTicket = dol_sanitizeFileName($_POST['folio_ticket']);
	$monto = price2num($_POST['monto']);

	$sql_facture = "SELECT ref, paye, total_ttc, datef";
	$sql_facture .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql_facture .= " WHERE ref = '".$db->escape($folioTicket)."'";
	$sql_facture .= " AND total_ttc BETWEEN (".((float) $monto)." - 0.5) AND (".((float) $monto)." + 0.5)";
	$result = $db->query($sql_facture);
	$facture = ($result) ? $db->fetch_object($result) : false;

	if ($facture) {
		if ((int) $facture->paye === 1) {
			if (!autofactura_ticket_puede_facturar($facture->datef)) {
				$pre_response = array(
					'resultado' => '4',
					'fecha_venta' => substr($facture->datef, 0, 10),
					'fecha_limite' => autofactura_ticket_fecha_limite($facture->datef),
					'fecha_hoy' => autofactura_ticket_fecha_hoy(),
				);
			} else {
				$pre_response = array(
					'folio_ticket' => $facture->ref,
					'folio_factura' => $facture->ref,
					'monto' => $facture->total_ttc,
					'resultado' => '1',
				);
			}
		} else {
			$pre_response = array(
				'folio_ticket' => $facture->ref,
				'resultado' => '3',
			);
		}
	} else {
		$pre_response = array('resultado' => '-1');
	}

	echo json_encode($pre_response);
}
