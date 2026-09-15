<?php
/**
 * Regla de autofactura_ticket: solo se puede facturar/timbrar
 * dentro del mismo mes calendario de la venta (America/Mexico_City).
 * Ej.: venta 14-sep → hasta 30-sep; venta 30-sep → solo ese día.
 */

/**
 * @param string|int $fechaVenta Fecha de venta (Y-m-d, datetime o timestamp Unix)
 * @return bool
 */
function autofactura_ticket_puede_facturar($fechaVenta)
{
	if ($fechaVenta === null || $fechaVenta === '') {
		return false;
	}

	$tz = new DateTimeZone('America/Mexico_City');
	$hoy = new DateTime('now', $tz);

	if (is_numeric($fechaVenta)) {
		$venta = new DateTime('@'.((int) $fechaVenta));
		$venta->setTimezone($tz);
	} else {
		$venta = new DateTime(substr(trim((string) $fechaVenta), 0, 10), $tz);
	}

	return $hoy->format('Y-m') === $venta->format('Y-m');
}

/**
 * Último día del mes de la venta (Y-m-d) en zona México.
 *
 * @param string|int $fechaVenta
 * @return string
 */
function autofactura_ticket_fecha_limite($fechaVenta)
{
	$tz = new DateTimeZone('America/Mexico_City');

	if (is_numeric($fechaVenta)) {
		$venta = new DateTime('@'.((int) $fechaVenta));
		$venta->setTimezone($tz);
	} else {
		$venta = new DateTime(substr(trim((string) $fechaVenta), 0, 10), $tz);
	}

	$venta->modify('last day of this month');
	return $venta->format('Y-m-d');
}

/**
 * Fecha de hoy en Mexico (Y-m-d).
 *
 * @return string
 */
function autofactura_ticket_fecha_hoy()
{
	$tz = new DateTimeZone('America/Mexico_City');
	return (new DateTime('now', $tz))->format('Y-m-d');
}
