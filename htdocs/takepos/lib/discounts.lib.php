<?php
/**
 * Helpers de descuento TakePOS.
 *
 * Las campañas de promo suelen "restaurar" al descuento base antes de recalcular.
 * Eso pisaba un 0% (u otro %) puesto a mano. Usar estos helpers al reactivar promos.
 */

/**
 * Descuento base de un producto (temp_discount / desc_max / cliente).
 *
 * @param Product $prod
 * @param float   $customerRemise
 * @return float
 */
function takepos_product_base_discount($prod, $customerRemise = 0)
{
	if (!empty($prod->temp_discount) && (float) $prod->temp_discount != 0) {
		return (float) $prod->temp_discount;
	}
	if (!empty($prod->desc_max) && (float) $prod->desc_max != 0) {
		return ((float) $prod->desc_max >= 10) ? (float) $customerRemise : (float) $prod->desc_max;
	}
	return 0.0;
}

/**
 * ¿La línea tiene un descuento manual por debajo del base? (incluye 0%).
 * Esas líneas no deben tocarse al recomputar promos.
 *
 * @param float $currentRemise
 * @param float $baseRemise
 * @return bool
 */
function takepos_is_manual_discount_below_base($currentRemise, $baseRemise)
{
	return (float) $currentRemise < (float) $baseRemise;
}
