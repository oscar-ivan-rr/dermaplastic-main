<?php
global $conf;

$zona_horaria = $conf->global->CFDIMX_HUSO_HORARIO;
date_default_timezone_set($zona_horaria);

require_once("class/facturacfdimx.class.php");

try {
	set_time_limit(150);
} catch (Exception $e) {
	$msg_cfdi_final = "Error:" . $e->getMessage();
}
if ($modo_timbrado == "") {
	$modotimb = 2;
} else {
	$modotimb = $modo_timbrado;
}

function validaRFC($valor)
{
	$valor = str_replace("-", "", $valor);
	$cuartoValor = substr($valor, 3, 1);
	//RFC Persona Moral.
	if (ctype_digit($cuartoValor) && strlen($valor) == 12) {
		$letras = substr($valor, 0, 3);
		$numeros = substr($valor, 3, 6);
		$homoclave = substr($valor, 9, 3);
		$search = array("Ãƒâ€˜", "&"); //caracteres admitidos por el SAT
		$replace = 'R'; //se reemplaza en la busqueda para omitir el caracter
		$letras = str_replace($search, $replace, $letras); //reemplazar
		if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
			return true;
		}
		//RFC Persona Fisica.
	} elseif (ctype_alpha($cuartoValor) && strlen($valor) == 13) {
		$letras = substr($valor, 0, 4);
		$numeros = substr($valor, 4, 6);
		$homoclave = substr($valor, 10, 3);
		if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
			return true;
		}
	} else {
		return false;
	}
}

function limpiar($String)
{
	$String = str_replace(array('á', 'à', 'â', 'ã', 'ª', 'ä'), "a", $String);
	$String = str_replace(array('Á', 'À', 'Â', 'Ã', 'Ä'), "A", $String);
	$String = str_replace(array('Í', 'Ì', 'Î', 'Ï'), "I", $String);
	$String = str_replace(array('í', 'ì', 'î', 'ï'), "i", $String);
	$String = str_replace(array('é', 'è', 'ê', 'ë'), "e", $String);
	$String = str_replace(array('É', 'È', 'Ê', 'Ë'), "E", $String);
	$String = str_replace(array('ó', 'ò', 'ô', 'õ', 'ö', 'º'), "o", $String);
	$String = str_replace(array('Ó', 'Ò', 'Ô', 'Õ', 'Ö'), "O", $String);
	$String = str_replace(array('ú', 'ù', 'û', 'ü'), "u", $String);
	$String = str_replace(array('Ú', 'Ù', 'Û', 'Ü'), "U", $String);
	$String = str_replace(array('[', '^', '´', '`', '¨', '~', ']'), "", $String);
	$String = str_replace("ç", "c", $String);
	$String = str_replace("Ç", "C", $String);
	$String = str_replace("Ý", "Y", $String);
	$String = str_replace("ý", "y", $String);
	$String = str_replace("&aacute;", "a", $String);
	$String = str_replace("&Aacute;", "A", $String);
	$String = str_replace("&eacute;", "e", $String);
	$String = str_replace("&Eacute;", "E", $String);
	$String = str_replace("&iacute;", "i", $String);
	$String = str_replace("&Iacute;", "I", $String);
	$String = str_replace("&oacute;", "o", $String);
	$String = str_replace("&Oacute;", "O", $String);
	$String = str_replace("&uacute;", "u", $String);
	$String = str_replace("&Uacute;", "U", $String);
	$String = str_replace("'", "", $String);
	$String = str_replace("\r\n", " ", $String);
	$String = str_replace("\r", " ", $String);
	$String = str_replace("\n", " ", $String);
	return $String;
}

function getDataCliente($db, $id)
{
	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "societe	WHERE rowid = " . $id;
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	$data["rowid"] = $obj->rowid;
	$data["rfc"] = $obj->siren;
	$data["razon_social"] = limpiar($obj->nom);
	$data["colonia"] = limpiar($obj->town); //Covertir a del o mpio
	$data["estado"] = limpiar(getState($obj->fk_departement));
	// Ensure zip code is treated as a string to preserve leading zeros
	$data["cp"] = (string)$obj->zip;
	$data["email"] = $obj->email;
	return $data;
}

function getU4DigCta($id, $db)
{
	$sql	= "SELECT * FROM " . MAIN_DB_PREFIX . "societe_rib  WHERE default_rib=1 AND fk_soc = " . $id;
	$resql  = $db->query($sql);
	$cuenta = "";

	if ($resql) {
		$nmc = $db->fetch_object($resql);
		$total_char = strlen($nmc->number);
		if ($total_char >= 4) {
			$cuenta = $nmc->number;
		} else {
			$cuenta = "";
		}
	}

	return $cuenta;
}

function getFormaPago($id, $db)
{
	$sql_formapago     = "SELECT accountancy_code FROM " . MAIN_DB_PREFIX . "c_paiement WHERE code  = '" . $id . "'";
	$res_sql_formapago = $db->query($sql_formapago);
	$num_formapago     = $db->num_rows($res_sql_formapago);

	$forma_pago = null;

	if ($num_formapago  > 0) {
		$obj_formapago = $db->fetch_object($res_sql_formapago);
		$forma_pago	= $obj_formapago->accountancy_code;
	}
	return $forma_pago;
}

function getCondicionPago($code, $db)
{
	global $langs;
	$sql_condpago     = "SELECT code, libelle AS label FROM " . MAIN_DB_PREFIX . "c_payment_term WHERE code = '" . $code . "'";
	$res_sql_condpago = $db->query($sql_condpago);
	$num_condpago     = $db->num_rows($res_sql_condpago);

	$codicion_pago = null;

	if ($num_condpago > 0) {
		$obj_condpago = $db->fetch_object($res_sql_condpago);

		$codicion_pago = ($langs->trans("PaymentConditionShort" . $obj_condpago->code) != ("PaymentConditionShort" . $obj_condpago->code) ? $langs->trans("PaymentConditionShort" . $obj_condpago->code) : ($obj_condpago->label != '-' ? $obj_condpago->label : ''));
	}

	return $codicion_pago;
}

function mostrarDescuentos($entidad, $db)
{
	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_descuentos";
	$sql = " WHERE entity_id=" . $entidad;

	$res_sql     = $db->query($sql);
	$num_res_sql = $db->num_rows($res_sql);

	$mostrar_descuento = 1;
	if ($num_res_sql > 0) {
		$obj_descuento = $db->fetch_object($res_sql);

		if ($obj_descuento->mostrar == 1) {
			$mostrar_descuento = 1;
		} else {
			$mostrar_descuento = 2;
		}
	}

	return $mostrar_descuento;
}

function validarCCE($id, $db)
{
	$sql_cce = " SELECT *
		FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero
		WHERE fk_facture=" . $id;

	$res_sql_cce = $db->query($sql_cce);
	$num_sql_cce = $db->num_rows($res_sql_cce);

	$cce = '';

	if ($num_sql_cce > 0) {
		$cce = 'SI';
	}

	return $cce;
}

function validarLotes($conf, $db, $facid)
{
	$lote = "NO";

	if (isset($conf->global->MAIN_MODULE_PRODUCTBATCH)) {
		$sql_lote = " SELECT ifnull(fk_source,null) as fk_source
			FROM " . MAIN_DB_PREFIX . "element_element
			WHERE fk_target=" . $facid . " AND targettype='facture' AND sourcetype='commande'";

		$res_sql_lote = $db->query($sql_lote);

		if ($res_sql_lote) {
			$obj_sql_lote = $db->fetch_object($res_sql_lote);

			if ($obj_sql_lote->fk_source != null && $obj_sql_lote->fk_source != null && $obj_sql_lote->fk_source > 0) {
				$sql_lote2 = " SELECT ifnull(fk_target,null) as fk_target
					FROM " . MAIN_DB_PREFIX . "element_element
					WHERE fk_source=" . $obj_sql_lote->fk_source . " AND sourcetype='commande' AND targettype='shipping'";

				$res_sql_lote2 = $db->query($sql_lote2);

				if ($res_sql_lote2) {
					$obj_sql_lote2 = $db->fetch_object($res_sql_lote2);

					if ($obj_sql_lote2->fk_target != null && $obj_sql_lote2->fk_target != null && $obj_sql_lote2->fk_target > 0) {
						$lote = $obj_sql_lote2->fk_target;
					} else {
						$lote = "NO";
					}
				}
			} else {
				$lote = "NO";
			}
		}
	}

	return $lote;
}

//Funcion para obtener las retenciones del Producto
function obtenerRetencionesProducto($id, $id_detalle, $impuesto, $vowels, $db)
{
	global $conf;
	$retenBase = null;
	$retenImporte = null;
	$retenTasa = null;
	$retenTipoFactor = null;
	$retencImpuesto = null;

	$sql_ret = " SELECT base,impuesto,tipo_factor,tasa,importe
		FROM " . MAIN_DB_PREFIX . "cfdimx_retencionesdet
		WHERE factura_id=" . $id . " AND fk_facturedet=" . $id_detalle . " AND impuesto='" . $impuesto . "'";

	$res_sql_ret = $db->query($sql_ret);
	$num_sql_ret = $db->num_rows($res_sql_ret);

	if ($num_sql_ret > 0) {
		$obj_ret            = $db->fetch_object($res_sql_ret);
		$retenBase          = str_replace($vowels, "", number_format($obj_ret->base, isset($conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL) ? $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL : 2));
		$retenImporte       = str_replace($vowels, "", number_format($obj_ret->importe, 2));
		$retenTasa          = $obj_ret->tasa;
		$retenTipoFactor    = $obj_ret->tipo_factor;
		$retencImpuesto     = $obj_ret->impuesto;
	}

	$retenciones = array(
		"retenBase"         => $retenBase,
		"retenImporte"      => $retenImporte,
		"retenTasa"         => $retenTasa,
		"retenTipoFactor"   => $retenTipoFactor,
		"retencImpuesto"    => $retencImpuesto
	);

	return $retenciones;
}

//Inicia Informacion de la Factura
$descheader  = 0;
$auxsubtotal = 0;
$factura = new Facture($db);
$factura->fetch($facid);

$cfdi_decimal = isset($conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL) ? $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL : 2;
$entidad = $conf->entity;
$doli_version = (int) DOL_VERSION;
$version_cfdi_sat = $conf->global->CFDIMX_VERSION_SAT;
$errores_factura	= null;

$factura_tipo = $factura->type;
if ($factura_tipo == 2) {
	$tipoComprobante = "E";
} else {
	$tipoComprobante = "I";

	$sql	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_type_document WHERE fk_facture=" . $facid;
	$res_sql = $db->query($sql);

	if ($res_sql) {
		$obj_tipoComprobante = $db->fetch_object($res_sql);

		if ($obj_tipoComprobante->tipo_document == 7) {
			$tipoComprobante = "T";
		}
	}
}

$separafac = explode("-", $factura->ref);

$serie = $separafac[0];
$folio = $separafac[1];

if ($separafac[1] == '' || $separafac[1] == null || $separafac[1] == null) {
	$serie = "";
	$folio = $separafac[0];
}

// Inicia Ajuste para Facturas con mas de 1 serie
if (count($separafac) > 2) {
	$serie = "";
	$count_facturas = count($separafac);
	for ($i = 0; $i < $count_facturas; $i++) {
		if ($i == ($count_facturas - 1)) {
			$folio = $separafac[$i];
		} else {
			if ($i == 0) {
				$serie .= $separafac[$i];
			} else {
				$serie .= "-" . $separafac[$i];
			}
		}
	}
}
// Termina Ajuste

// Informacion de la Factura
$cliente_id = $factura->socid;

if ($doli_version >= 14) {
	if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
		$factura_iva = str_replace(",", "", number_format($factura->multicurrency_total_tva, 2));
	} else {
		$factura_iva = str_replace(",", "", number_format($factura->total_tva, 2));
	}
} else {
	if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
		$factura_iva = str_replace(",", "", number_format($factura->multicurrency_total_tva, 2));
	} else {
		$factura_iva = str_replace(",", "", number_format($factura->total_tva,2));
	}
}

if ($doli_version >= 14) {
	if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
		$factura_subtotal = str_replace(",", "", number_format($factura->multicurrency_total_ht, 2));
	} else {
		$factura_subtotal = str_replace(",", "", number_format($factura->total_ht, 2));
	}
} else {
	if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
		$factura_subtotal = str_replace(",", "", number_format($factura->multicurrency_total_ht, 2));
	} else {
		$factura_subtotal = str_replace(",", "", number_format($factura->total_ht, 2));
	}
}

if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
	$factura_total = str_replace(",", "", number_format($factura->multicurrency_total_ttc, 2));
	$factura_total_origen = str_replace(",", "", number_format($factura->multicurrency_total_ttc, 2));
} else {
	$factura_total = str_replace(",", "", ($factura_iva + $factura_subtotal));
	$factura_total_origen = str_replace(",", "", number_format(($factura_iva + $factura_subtotal), $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL));
}

// $factura_fecha          = date("Y-m-d", $factura->date);
$factura_fecha          = date("Y-m-d");
$factura_formapago      = getFormaPago($factura->mode_reglement_code, $db);
$factura_condicionpago  = getCondicionPago($factura->cond_reglement_code, $db);
$factura_metodopago     = $factura->array_options["options_formpagcfdi"];
$factura_usocfdi        = $factura->array_options["options_usocfdi"];
//Termina Informacion de la Factura

$entraconcepto = ''; // eliminar

$cuenta = getU4DigCta($cliente_id, $db);
$datareceptor_main = getDataCliente($db, $cliente_id);

//Empieza Ajuste RIF
$regimen_fiscal = $conf->global->CFDIMX_REGIMEN_FISCAL;
$rfc_ajuste_rif = $datareceptor_main["rfc"];
$ajustar_conceptos = 0;
//Termina Ajuste RIF

//Inicia Nueva obtencion de los conceptos
if ($factura->type == 2) {
	$vowels = array(",", "-");
} else {
	$vowels = array(",");
}

$producto = new Product($db);
$mostrar_descuentos = mostrarDescuentos($entidad, $db);
$validar_cce        = validarCCE($facid, $db);
$validar_lotes      = validarLotes($conf, $db, $facid);
$errores_conceptos  = null;
$total_iva_exento   = 0;

$count_lineas_factura = count($factura->lines);
for ($i = 0; $i < $count_lineas_factura; $i++) {
	$impuesto = '002';
	$val_concepto = null;

	if ($factura->lines[$i]->fk_product != "") {
		$producto->fetch($factura->lines[$i]->fk_product);

		$lista_descripciones = null;

		if ($conf->global->CFDIMX_DESC_PROD_CAT_REF == 1) {
			if ($producto->ref != "") {
				$lista_descripciones[] = $producto->ref;
			}
		}

		if ($conf->global->CFDIMX_DESC_PROD_CAT_ETIQUETA == 1) {
			if ($producto->label != "") {
				$lista_descripciones[] = $producto->label;
			}
		}

		if ($conf->global->CFDIMX_DESC_PROD_CAT_DESC == 1) {
			if ($producto->description != "") {
				$lista_descripciones[] = $producto->description;
			}
		}

		if ($conf->global->CFDIMX_DESC_PROD_NO_CAT_DESC == 1) {
			if ($factura->lines[$i]->desc != "") {
				$lista_descripciones[] = $factura->lines[$i]->desc;
			}
		}

		// Inicia Si el Producto Libre es Servicio se agrega las fechas a la descripcion
		if ($factura->lines[$i]->product_type == 1) {
			$fecha_ini_servicio = dol_print_date($factura->lines[$i]->date_start, '%d-%m-%Y');
			$fecha_fin_servicio = dol_print_date($factura->lines[$i]->date_end, '%d-%m-%Y');

			$fecha_limit_servicio = "";
			if ($fecha_ini_servicio != "" && $fecha_fin_servicio != "") {
				$fecha_limit_servicio = "(De " . $fecha_ini_servicio . " a " . $fecha_fin_servicio . ")";
			} else {
				if ($fecha_ini_servicio != "") {
					$fecha_limit_servicio = "(Desde " . $fecha_ini_servicio . ")";
				}

				if ($fecha_fin_servicio != "") {
					$fecha_limit_servicio = "(Hasta " . $fecha_fin_servicio . ")";
				}
			}

			if ($fecha_limit_servicio != "") {
				$lista_descripciones[] = $fecha_limit_servicio;
			}
		}
		// Termina Si el Producto Libre es Servicio se agrega las fechas a la descripcion

		$descripcion = "";

		if ($lista_descripciones != null) {
			$descripcion = implode(" - ", $lista_descripciones);
		}

		if ($validar_lotes != "NO" && $factura->lines[$i]->fk_product != "") {
			$sql_lote_prod = " SELECT ifnull(fk_product,null) as fk_product, batch, eatby,(value * -1) as qty
				FROM " . MAIN_DB_PREFIX . "stock_mouvement
				WHERE fk_product=" . $factura->lines[$i]->fk_product . " AND fk_origin=" . $validar_lotes . " AND origintype='shipping'";

			$res_sql_lote_prod = $db->query($sql_lote_prod);

			while ($obj_lote_prod = $db->fetch_object($res_sql_lote_prod)) {
				if ($obj_lote_prod->fk_product != null && (trim($obj_lote_prod->batch) != "" && $obj_lote_prod->batch != null)) {
					$descripcion .= " - Cantidad: " . $obj_lote_prod->qty . " Lote: " . $obj_lote_prod->batch . " Cad: " . $obj_lote_prod->eatby;
				}
			}
		}

		if (strlen($descripcion) > 1000) {
			if (isset($conf->global->CFDIMX_LIM_DESC) && $conf->global->CFDIMX_LIM_DESC == 1) {
				$descripcion = substr($descripcion, 0, 1000);
			} else {
				$val_concepto[] = "El producto '" . $producto->ref . "' supera el límite(1,000) de caracteres permitidos por el SAT.";
			}
		}

		$unidad           = $producto->array_options["options_umed"];
		$claveprodserv    = $producto->array_options["options_claveprodserv"];
		$noIdentificacion = $producto->array_options["options_noidenticfdi"];
		$cuentapredial    = $producto->array_options["options_cuentapredial"];
		$tipoFactor       = $producto->array_options["options_exentoiva"];
		// Si no se tiene objeto de impuesto por default asigna 01 (no objeto de impuesto)
		$objimp           = $producto->array_options["options_objimp"] ? $producto->array_options["options_objimp"] : "02";

		//Inicia Ajuste para tomar lo que se capture fuera del catalogo
		if ($factura->lines[$i]->array_options["options_umed"] != "" && $factura->lines[$i]->array_options["options_umed"] != 0) {
			$unidad = $factura->lines[$i]->array_options["options_umed"];
		}

		if ($factura->lines[$i]->array_options["options_claveprodserv"] != "" && $factura->lines[$i]->array_options["options_claveprodserv"] != 0) {
			$claveprodserv = $factura->lines[$i]->array_options["options_claveprodserv"];
		}

		if ($factura->lines[$i]->array_options["options_exentoiva"] != "") {
			$tipoFactor = $factura->lines[$i]->array_options["options_exentoiva"];
		}

		if ($factura->lines[$i]->array_options["options_objimp"] != "" && $factura->lines[$i]->array_options["options_objimp"] != 0) {
			$objimp = $factura->lines[$i]->array_options["options_objimp"];
		}

		//Termina Ajuste para tomar lo que se capture fuera del catalogo

		if ($unidad == "") {
			$val_concepto[] = "El producto '" . $producto->ref . "' no tiene seleccionada la Unidad de Medida.";
		} else {
			if (is_numeric($unidad)) {
				$val_concepto[] = "El producto '" . $producto->ref . "' no tiene seleccionada la Unidad de Medida.";
			}
		}

		if ($claveprodserv == "" || $claveprodserv == 0) {
			$val_concepto[] = "El producto '" . $producto->ref . "' no tiene seleccionada la Clave Producto/Servicio.";
		}

		if (strcmp($version_cfdi_sat, "4.0") == 0) {
			if ($objimp == "" || $objimp == 0) {
				$val_concepto[] = "El producto '" . $producto->ref . "' no tiene seleccionado el Objeto de Impuesto.";
			}
		}
	} else {
		$descripcion      = $factura->lines[$i]->desc;
		$unidad           = $factura->lines[$i]->array_options["options_umed"];
		$claveprodserv    = $factura->lines[$i]->array_options["options_claveprodserv"];
		$noIdentificacion = $factura->lines[$i]->array_options["options_noidenticfdi"];
		$cuentapredial    = $factura->lines[$i]->array_options["options_cuentapredial"];
		$tipoFactor       = $factura->lines[$i]->array_options["options_exentoiva"];
		// Si no se tiene objeto de impuesto por default asigna 01 (no objeto de impuesto)
		$objimp           = $factura->lines[$i]->array_options["options_objimp"] ? $factura->lines[$i]->array_options["options_objimp"] : "01";

		// Inicia Si el Producto Libre es Servicio se agrega las fechas a la descripcion
		if ($factura->lines[$i]->product_type == 1) {
			$fecha_ini_servicio = dol_print_date($factura->lines[$i]->date_start, '%d-%m-%Y');
			$fecha_fin_servicio = dol_print_date($factura->lines[$i]->date_end, '%d-%m-%Y');

			$fecha_limit_servicio = "";
			if ($fecha_ini_servicio != "" && $fecha_fin_servicio != "") {
				$fecha_limit_servicio = " (De " . $fecha_ini_servicio . " a " . $fecha_fin_servicio . ")";
			} else {
				if ($fecha_ini_servicio != "") {
					$fecha_limit_servicio = " (Desde " . $fecha_ini_servicio . ")";
				}

				if ($fecha_fin_servicio != "") {
					$fecha_limit_servicio = " (Hasta " . $fecha_fin_servicio . ")";
				}
			}

			if ($fecha_limit_servicio != "") {
				$descripcion .= $fecha_limit_servicio;
			}
		}
		// Termina Si el Producto Libre es Servicio se agrega las fechas a la descripcion

		if ($unidad == "") {
			$val_concepto[] = "El producto '" . $factura->lines[$i]->desc . "' no tiene seleccionada la Unidad de Medida.";
		} else {
			if (is_numeric($unidad)) {
				$val_concepto[] = "El producto '" . $factura->lines[$i]->desc . "' no tiene seleccionada la Unidad de Medida.";
			}
		}

		if ($claveprodserv == "" || $claveprodserv == 0) {
			$val_concepto[] = "El producto '" . $factura->lines[$i]->desc . "' no tiene seleccionada la Clave Producto/Servicio.";
		}

		if (strcmp($version_cfdi_sat, "4.0") == 0) {
			if ($objimp == "" || $objimp == 0) {
				$val_concepto[] = "El producto '" . $factura->lines[$i]->desc . "' no tiene seleccionado el Objeto de Impuesto.";
			}
		}

		if (strlen($descripcion) > 1000) {
			if (isset($conf->global->CFDIMX_LIM_DESC) && $conf->global->CFDIMX_LIM_DESC == 1) {
				$descripcion = substr($descripcion, 0, 1000);
			} else {
				$val_concepto[] = "El producto '" . $descripcion . "' supera el límite(1,000) de caracteres permitidos por el SAT.";
			}
		}
	}

	if ($val_concepto != null) {
		$errores_conceptos[] = $val_concepto;
	}

	$label_tipoFactor = ($tipoFactor == 1 ? 'Exento' : 'Tasa');
	$descprodl = null;

	if ($conf->global->MAIN_MODULE_MULTICURRENCY == 1 && $factura->multicurrency_code != "MXN") {
		$factura_subtotal_detalle = $factura->lines[$i]->multicurrency_total_ht;
		$importeImpuesto  = $factura->lines[$i]->multicurrency_total_tva;

		if ($factura->lines[$i]->remise_percent != 0  && $mostrar_descuentos == 1) {
			$descuento  = $factura->lines[$i]->remise_percent / 100;
			$descuento2 = ($factura->lines[$i]->multicurrency_subprice * $factura->lines[$i]->qty) * $descuento;
			$descuento2 = str_replace(array("-", ","), "", number_format($descuento2, 2));
			$descprodl  = str_replace(array("-", ","), "", number_format($descuento2, 2));
			$descheader = $descheader + $descuento2;
			$total_vuni = number_format($factura->lines[$i]->multicurrency_subprice, $cfdi_decimal);
			$total_ttc  = number_format($factura->lines[$i]->multicurrency_subprice * $factura->lines[$i]->qty, $cfdi_decimal);
		} else {
			if ($factura->lines[$i]->remise_percent != 0  && $mostrar_descuentos == 2) {
				$descuento  = $factura->lines[$i]->multicurrency_total_ht / $factura->lines[$i]->qty;
				$total_vuni = number_format($descuento, $cfdi_decimal);
				$total_ttc  = number_format($factura->lines[$i]->multicurrency_total_ht, $cfdi_decimal);
			} else {
				$total_vuni = number_format($factura->lines[$i]->multicurrency_subprice, $cfdi_decimal);
				$total_ttc  = number_format($factura->lines[$i]->multicurrency_total_ht, $cfdi_decimal);
			}
		}
	} else {
		$factura_subtotal_detalle = $factura->lines[$i]->total_ht;
		$importeImpuesto  = $factura->lines[$i]->total_tva;

		if ($factura->lines[$i]->remise_percent != 0  && $mostrar_descuentos == 1) {
			$descuento  = $factura->lines[$i]->remise_percent / 100;
			$descuento2 = ($factura->lines[$i]->subprice * $factura->lines[$i]->qty) * $descuento;
			$descuento2 = str_replace(array("-", ","), "", number_format($descuento2, $cfdi_decimal));
			$descprodl  = str_replace(array("-", ","), "",$descuento2);
			$descheader = $descheader + $descuento2;
			$total_vuni = $factura->lines[$i]->subprice;
			$total_ttc  = $factura->lines[$i]->subprice * $factura->lines[$i]->qty;

		} else {
			if ($factura->lines[$i]->remise_percent != 0  && $mostrar_descuentos == 2) {
				$descuento  = $factura->lines[$i]->total_ht / $factura->lines[$i]->qty;
				$total_vuni = $descuento;
				$total_ttc  = $factura->lines[$i]->total_ht;
			} else {
				$total_vuni = $factura->lines[$i]->subprice;
				$total_ttc  = $factura->lines[$i]->total_ht;
			}
		}
	}

	///Inicia Ajuste Nota Credito
	if ($total_vuni == 0)
		$total_vuni = $factura->lines[$i]->subprice;

	if ($total_ttc == 0)
		$total_ttc = $factura->lines[$i]->total_ht;

	if ($factura->lines[$i]->total_tva == 0)
		$factura->lines[$i]->total_tva = $factura->lines[$i]->total_tva;

	if ($factura->lines[$i]->total_ht == 0)
		$factura->lines[$i]->total_ht = $factura->lines[$i]->total_ht;
	///Termina Ajuste Nota Credito

	$retencion_iva = obtenerRetencionesProducto($facid, $factura->lines[$i]->id, '002', $vowels, $db);
	$retencion_isr = obtenerRetencionesProducto($facid, $factura->lines[$i]->id, '001', $vowels, $db);

	if ($validar_cce == 'SI') {
		$sql_cce_noid = " SELECT noidentificacion
			FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero_mercancia a
			WHERE a.fk_facture=" . $facid . " AND a.fk_facturedet=" . $factura->lines[$i]->id;

		$res_sql_cce_noid = $db->query($sql_cce_noid);
		$num_sql_cce_noid = $db->num_rows($res_sql_cce_noid);

		$noIdentificacion = 0;

		if ($num_sql_cce_noid > 0) {
			$obj_cce_noid = $db->fetch_object($res_sql_cce_noid);
			$noIdentificacion = $obj_cce_noid->noidentificacion;
		}
	}

	$cantidad = $factura->lines[$i]->qty;

	// Inicia Limpia de caracteres no permitidos por el SAT
	$lista_caracteres = array("|");
	$descripcion	  = str_replace($lista_caracteres, " ", $descripcion);
	// Termina Limpia de caracteres no permitidos por el SAT

	if ($conf->global->CFDIMX_DESC_PDF == 0) {
		$descripcion_xml = utf8_decode(strip_tags(html_entity_decode($descripcion)));
	} else {
		$descripcion_xml = utf8_decode(strip_tags(html_entity_decode(mb_strtoupper($descripcion))));
	}

	if ($tipoFactor == 1) {
		$total_iva_exento += $importeImpuesto;
	}

	if (strcmp($version_cfdi_sat, "4.0") == 0) {
		$conceptos[] = array(
			"descripcion" => $descripcion_xml,
			'cantidad' => str_replace($vowels, "", $cantidad),
			'valorUnitario' => str_replace($vowels, "", number_format($total_vuni, 2)),
			'importe' => str_replace($vowels, "", number_format($total_ttc,4)),
			'importeImpuesto' => str_replace($vowels, "", number_format(($importeImpuesto),$conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
			'impuesto' => $impuesto,
			'tasa' => number_format(($factura->lines[$i]->tva_tx / 100), $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL),
			"unidad" => $unidad,
			'noIdentificacion' => $noIdentificacion,
			'tipoFactor' => $label_tipoFactor,
			'claveProdServ' => $claveprodserv,
			'base' => str_replace($vowels, "", number_format($factura_subtotal_detalle, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
			'retenBase' => $retencion_iva["retenBase"],
			'retenImporte' => $retencion_iva["retenImporte"],
			'retenTasa' => $retencion_iva["retenTasa"],
			'retenTipoFactor' => $retencion_iva["retenTipoFactor"],
			'retencImpuesto' => $retencion_iva["retencImpuesto"],
			'retenBaseISR' => $retencion_isr["retenBase"],
			'retenImporteISR' => $retencion_isr["retenImporte"],
			'retenTasaISR' => $retencion_isr["retenTasa"],
			'retenTipoFactorISR' => $retencion_isr["retenTipoFactor"],
			'retencImpuestoISR' => $retencion_isr["retencImpuesto"],
			'descuento' => str_replace($vowels, "",number_format($descprodl, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
			'cuentaPredial' => $cuentapredial,
			'objetoImp' => $objimp
		);
	}

	if (strcmp($version_cfdi_sat, "3.3") == 0 || $version_cfdi_sat == '') {
		$conceptos[] = array(
			"descripcion" => $descripcion_xml,
			'cantidad' => str_replace($vowels, "", number_format($cantidad, 2)),
			'valorUnitario' => str_replace($vowels, "", $total_vuni),
			'importe' => str_replace($vowels, "", $total_ttc),
			'importeImpuesto' => str_replace($vowels, "", number_format(($importeImpuesto), $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
			'impuesto' => $impuesto,
			'tasa' => number_format(($factura->lines[$i]->tva_tx / 100), 6),
			"unidad" => $unidad,
			'noIdentificacion' => $noIdentificacion,
			'tipoFactor' => $label_tipoFactor,
			'claveProdServ' => $claveprodserv,
			'base' => str_replace($vowels, "", number_format($factura_subtotal_detalle, 2)),
			'retenBase' => $retencion_iva["retenBase"],
			'retenImporte' => $retencion_iva["retenImporte"],
			'retenTasa' => $retencion_iva["retenTasa"],
			'retenTipoFactor' => $retencion_iva["retenTipoFactor"],
			'retencImpuesto' => $retencion_iva["retencImpuesto"],
			'retenBaseISR' => $retencion_isr["retenBase"],
			'retenImporteISR' => $retencion_isr["retenImporte"],
			'retenTasaISR' => number_format($retencion_isr["retenTasa"], 6),
			'retenTipoFactorISR' => $retencion_isr["retenTipoFactor"],
			'retencImpuestoISR' => $retencion_isr["retencImpuesto"],
			'descuento' => $descprodl,
			'cuentaPredial' => $cuentapredial
		);
	}

	$auxsubtotal += str_replace($vowels, "", $total_ttc);
}

//Empieza Ajuste RIF Conceptos
if ($ajustar_conceptos == 1) {
	$conceptos_rif = $conceptos;
	$conceptos_rif2 = $conceptos2;

	//nuevas variables de totales
	$rif_subtotal = 0;
	$rif_iva	  = 0;

	$count_conceptos = count($conceptos_rif);
	for ($i = 0; $i < $count_conceptos; $i++) {
		$rif_subtotal += $conceptos_rif[$i]["importe"];
		$rif_iva += $conceptos_rif[$i]["importeImpuesto"];
		$descripcion = $conceptos_rif[$i]['descripcion'];
		$cantidad = $conceptos_rif[$i]['cantidad'];
		$valorUnitario = $conceptos_rif[$i]['valorUnitario'];
		$importe = $conceptos_rif[$i]['importe'];
		$importeImpuesto = $conceptos_rif[$i]['importeImpuesto'];
		$impuesto = $conceptos_rif[$i]['impuesto'];
		$tasa = $conceptos_rif[$i]['tasa'];
		$unidad = $conceptos_rif[$i]['unidad'];
		$tipoFactor = $conceptos_rif[$i]['tipoFactor'];
		$claveProdServ = $conceptos_rif[$i]['claveProdServ'];
		$base = $conceptos_rif[$i]['base'];
		$retenBase = $conceptos_rif[$i]['retenBase'];
		$retenImporte = $conceptos_rif[$i]['retenImporte'];
		$retenTasa = $conceptos_rif[$i]['retenTasa'];
		$retenTipoFactor = $conceptos_rif[$i]['retenTipoFactor'];
		$retencImpuesto = $conceptos_rif[$i]['retencImpuesto'];
		$retenBaseISR = $conceptos_rif[$i]['retenBaseISR'];
		$retenImporteISR = $conceptos_rif[$i]['retenImporteISR'];
		$retenTasaISR = $conceptos_rif[$i]['retenTasaISR'];
		$retenTipoFactorISR = $conceptos_rif[$i]['retenTipoFactorISR'];
		$retencImpuestoISR = $conceptos_rif[$i]['retencImpuestoISR'];
		$descuento = $conceptos_rif[$i]['descuento'];
		$cuentaPredial = $conceptos_rif[$i]['cuentaPredial'];

	}

	$total_rif = $rif_subtotal + $rif_iva;
	$importeImpuesto = 0;
	$impuesto = "000";
	$tasa = 0;

	$conceptos = null;
	$conceptos_tmp[0] = array(
		'descripcion' => $descripcion,
		'cantidad' => $cantidad,
		'valorUnitario' => str_replace($vowels, "", number_format($total_rif, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
		'importe' => str_replace($vowels, "", $total_rif),
		'importeImpuesto' => str_replace($vowels, "", number_format(($importeImpuesto), $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
		'impuesto' => $impuesto,
		'tasa' => number_format(($tasa / 100), $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL),
		'unidad' => $unidad,
		'tipoFactor' => $tipoFactor,
		'claveProdServ' => $claveProdServ,
		'base' => str_replace($vowels, "", number_format($total_rif, 2)),
		'retenBase' => $retenBase,
		'retenImporte' => $retenImporte,
		'retenTasa' => number_format($retenTasa, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL),
		'retenTipoFactor' => $retenTipoFactor,
		'retencImpuesto' => $retencImpuesto,
		'retenBaseISR' => $retenBaseISR,
		'retenImporteISR' => $retenImporteISR,
		'retenTasaISR' => number_format($retenTasaISR, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL),
		'retenTipoFactorISR' => $retenTipoFactorISR,
		'retencImpuestoISR' => $retencImpuestoISR,
		'descuento' => number_format($descuento, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL),
		'cuentaPredial' => $cuentaPredial
	);

	$conceptos2 = null;
	$conceptos2_tmp[0] = array(
		'descripcion' => $descripcion,
		'cantidad' => $cantidad,
		'valorUnitario' => str_replace($vowels, "", $total_rif),
		'importe' => str_replace($vowels, "", number_format($total_rif, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
		'importeImpuesto' => str_replace($vowels, "", number_format(($importeImpuesto), $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)),
		'impuesto' => $impuesto,
		'tasa' => $tasa,
		'unidad' => $unidad,
		'tipoFactor' => $tipoFactor,
		'claveProdServ' => $claveProdServ,
		'base' => str_replace($vowels, "", $total_rif)
	);

	$factura_subtotal = $total_rif;
	$factura_iva = 0;

	$conceptos = $conceptos_tmp;
	$conceptos2 = $conceptos2_tmp;
}
//Termina Ajuste RIF Conceptos

//Datos complementarios del emisor
// Si usuario tiene almacen, extraer datos de almacen, si no de emisor predeterminado
if($user->id > 0){
	if ($user->fk_warehouse > 0) {
		$emisorsql = "SELECT edc.emisor_delompio, edc.emisor_colonia, edc.emisor_calle, edc.emisor_noext, edc.emisor_noint";
		$emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
		$emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
		$emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
		$emisorsql .= " WHERE e.rowid = " . $user->fk_warehouse;

		$reemisorsql = $db->query($emisorsql);

		if ($reemisorsql) {
			$emisor_almacen = $db->fetch_object($emisorsql);
		}

		$emisor_delompio = $emisor_almacen->emisor_delompio;
		$col_emisor      = $emisor_almacen->emisor_colonia;
		$emisor_calle    = $emisor_almacen->emisor_calle;
		$emisor_noint    = $emisor_almacen->emisor_noint;
		$emisor_noext    = $emisor_almacen->emisor_noext;
	} else {
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp WHERE emisor_rfc = '" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id = " . $conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$num_emisor_datacomp = $db->num_rows($resql);
			$i = 0;
			if ($num_emisor_datacomp) {
				while ($i < $num_emisor_datacomp) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$sqn = "SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
						FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero
						WHERE fk_facture=" . $facid;
						$rqn = $db->query($sqn);
						$numrn = $db->num_rows($rqn);
						if ($numrn > 0) {
							$emisor_delompio = $obj->cod_municipio;
							$col_emisor = $obj->cod_colonia;
						} else {
							$emisor_delompio = utf8_decode($obj->emisor_delompio); // Convertir a Colonia
							$col_emisor = limpiar(html_entity_decode($obj->emisor_colonia));
						}
						$emisor_calle = utf8_decode($obj->emisor_calle);
						$emisor_noint = utf8_decode($obj->emisor_noint);
						$emisor_noext = utf8_decode($obj->emisor_noext);
					}
					$i++;
				}
			}
		}
	}
}else{

	$sql_warehouse = 'SELECT ba.fk_warehouse';
	$sql_warehouse .= ' FROM llx_facture as f';
	$sql_warehouse .= ' LEFT JOIN llx_paiement_facture as pf on f.rowid = pf.fk_facture';
	$sql_warehouse .= ' LEFT JOIN llx_paiement as p on pf.fk_paiement = p.rowid';
	$sql_warehouse .= ' LEFT JOIN llx_bank as b on p.fk_bank = b.rowid';
	$sql_warehouse .= ' LEFT JOIN llx_bank_account as ba on b.fk_account = ba.rowid';
	$sql_warehouse .= ' WHERE f.rowid = ' . $factura->id;

	$res_warehouse = $db->query($sql_warehouse);
	$obj_warehouse = $db->fetch_object($res_warehouse);
	$id_almacen = $obj_warehouse->fk_warehouse;
	if ($id_almacen > 0) {
		$emisorsql = "SELECT edc.emisor_delompio, edc.emisor_colonia, edc.emisor_calle, edc.emisor_noext, edc.emisor_noint";
		$emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
		$emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
		$emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
		$emisorsql .= " WHERE e.rowid = " . $id_almacen;

		$reemisorsql = $db->query($emisorsql);

		if ($reemisorsql) {
			$emisor_almacen = $db->fetch_object($emisorsql);
		}

		$emisor_delompio = $emisor_almacen->emisor_delompio;
		$col_emisor      = $emisor_almacen->emisor_colonia;
		$emisor_calle    = $emisor_almacen->emisor_calle;
		$emisor_noint    = $emisor_almacen->emisor_noint;
		$emisor_noext    = $emisor_almacen->emisor_noext;
	} else {
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp WHERE emisor_rfc = '" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id = " . $conf->entity;
		$resql = $db->query($sql);
		if ($resql) {
			$num_emisor_datacomp = $db->num_rows($resql);
			$i = 0;
			if ($num_emisor_datacomp) {
				while ($i < $num_emisor_datacomp) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$sqn = "SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
						FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero
						WHERE fk_facture=" . $facid;
						$rqn = $db->query($sqn);
						$numrn = $db->num_rows($rqn);
						if ($numrn > 0) {
							$emisor_delompio = $obj->cod_municipio;
							$col_emisor = $obj->cod_colonia;
						} else {
							$emisor_delompio = utf8_decode($obj->emisor_delompio); // Convertir a Colonia
							$col_emisor = limpiar(html_entity_decode($obj->emisor_colonia));
						}
						$emisor_calle = utf8_decode($obj->emisor_calle);
						$emisor_noint = utf8_decode($obj->emisor_noint);
						$emisor_noext = utf8_decode($obj->emisor_noext);
					}
					$i++;
				}
			}
		}
	}
}
$sql = '';
$sql = 'SELECT rowid, nom, address, zip, town, fk_departement, siren FROM ' . MAIN_DB_PREFIX . 'societe WHERE rowid=' . $datareceptor_main["rowid"];

$res_sql = $db->query($sql);

dol_syslog('ESTE QUERY DIR: ' . $sql);
if ($res_sql) {
	$num_emisor_datacomp = $db->num_rows($res_sql);
	$i				   = 0;
	if ($num_emisor_datacomp) {
		while ($i < $num_emisor_datacomp) {
			$obj = $db->fetch_object($res_sql);
			if ($obj) {
				$dir_estado = getState($obj->fk_departement, 2);
				$receptor_cod_municipio = '';
				$receptor_delompio = $obj->town; //Municipio
				$receptor_colonia  = '';
				$receptor_calle	= $obj->address;
				$receptor_noint	= '';
				$receptor_noext	= '';
			}
			$i++;
		}
	}
}

// Retenciones
$retenciones = null;
$retenciones2 = null;
$resql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture = " . $facid);
if ($resql) {
	$tot_ret = $db->num_rows($resql);
	$i = 0;
	if ($tot_ret) {
		while ($i < $tot_ret) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				if ($factura_tipo == 2) {
					$vowels = array(",", "-");
				} else {
					$vowels = array(",");
				}
				$retenclave = "";
				if ($obj->impuesto == "IVA") {
					$retenclave = "002";
				} else {
					if ($obj->impuesto == "ISR") {
						$retenclave = "001";
					} else {
						$retenclave = $obj->impuesto;
					}
				}
				$retenciones[$i] = array(
					"impuesto" => trim(preg_replace("/ +/", " ", $retenclave)),
					"importe" => str_replace($vowels, "", number_format(($obj->importe), 2))
				);
				$retenciones2[$i] = array(
					"impuesto" => trim(preg_replace("/ +/", " ", $retenclave)),
					"importe" => str_replace($vowels, "", number_format($obj->importe, 2))
				);
			}
			$i++;
		}
	}
}

//Retenciones locales Parte 1
$sqm = "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '" . $db->database_name . "' AND table_name = '" . MAIN_DB_PREFIX . "cfdimx_config_retenciones_locales'";
$rqm = $db->query($sqm);
$rqsm = $db->fetch_object($rqm);
$total_retlocal = 0;
if ($rqsm > 0) {
	$resqm = $db->query("SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $facid);
	if ($resqm) {
		$cfdi_m = $db->num_rows($resqm);
		$m = 0;
		if ($cfdi_m > 0) {
			while ($m < $cfdi_m) {
				$obm = $db->fetch_object($resqm);
				$total_retlocal = str_replace(",", "", number_format(($total_retlocal + $obm->importe), 2));
				$m++;
			}
		}
	}
}

//ISH
if (1) {
	$impuestoslocales = array();
	$impuestoish = "NO";
	$sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "product_extrafields LIKE 'prodcfish'";
	$resql = $db->query($sql);
	$existe_ish = $db->num_rows($resql);
	$totalish = 0;
	$imporcen = '';
	if ($existe_ish > 0) {
		if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
			$sql = "
				SELECT
					a.fk_product,a.multicurrency_total_ht as total_ht,
					b.prodcfish,((b.prodcfish/100)*a.multicurrency_total_ht) as impish,
					c.ref,
					c.label,
					b.prodcfish_label
				FROM " . MAIN_DB_PREFIX . "facturedet a,
					(
						SELECT fk_object,prodcfish,prodcfish_label
						FROM " . MAIN_DB_PREFIX . "product_extrafields
						WHERE prodcfish!=0 AND prodcfish IS NOT null
					) b,
					" . MAIN_DB_PREFIX . "product c
				WHERE a.fk_facture=" . $id . " AND a.fk_product =b.fk_object AND a.fk_product=c.rowid
				ORDER BY a.rowid
			";
		} else {
			$sql = "
				SELECT
					a.fk_product,
					a.total_ht,
					b.prodcfish,
					((b.prodcfish/100)*a.total_ht) as impish,
					c.ref,
					c.label,
					b.prodcfish_label
				FROM " . MAIN_DB_PREFIX . "facturedet a,
					(
						SELECT fk_object,prodcfish,prodcfish_label
						FROM " . MAIN_DB_PREFIX . "product_extrafields
						WHERE prodcfish!=0 AND prodcfish IS NOT null
					) b,
					" . MAIN_DB_PREFIX . "product c
				WHERE a.fk_facture=" . $facid . " AND a.fk_product =b.fk_object AND a.fk_product=c.rowid
				ORDER BY a.rowid
			";
		}
		$ass = $db->query($sql);
		$asf = $db->num_rows($ass);
		if ($asf > 0) {
			while ($asd = $db->fetch_object($ass)) {
				$totalish = $totalish + $asd->impish;
				$imporcen = $asd->prodcfish;
				$etiqueta_ish = $asd->prodcfish_label;
			}
		}
	}

	if ($totalish > 0) {
		$totalish = str_replace(",", "", number_format($totalish, 2));
		$impuestoish = $totalish;
		$factura_total = $factura_total + $totalish - $total_retlocal;
		$factura_total = str_replace(",", "", number_format($factura_total, 2));
		$sql = "SELECT count(*) as exist FROM " . MAIN_DB_PREFIX . "cfdimx_facturedet WHERE fk_facture=" . $facid . " AND impuesto='ISH'";
		$ass = $db->query($sql);
		$asd = $db->fetch_object($ass);
		if ($asd->exist == 0) {
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_facturedet (fk_facture,impuesto,importe) VALUES ('" . $id . "','ISH','" . $totalish . "')";
			$ass = $db->query($sql);
			$i = 0;
		}
	}

	// ISH Para productos de campo libre
	$impuestoish2 = "NO";
	$sql = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facturedet_extrafields LIKE 'prodcfish'";
	$resql = $db->query($sql);
	$existe_ish2 = $db->num_rows($resql);
	$totalish2 = 0;
	$imporcen2 = '';
	if ($existe_ish2 > 0) {
		if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
			$sql = "
				SELECT
					a.fk_product,
					a.multicurrency_total_ht AS total_ht, b.prodcfish,
					((b.prodcfish / 100) * a.multicurrency_total_ht) AS impish,
					a.label,
					a.description,
					b.prodcfish_label
				FROM
					" . MAIN_DB_PREFIX . "facturedet a,
					( SELECT fk_object, prodcfish, prodcfish_label
					FROM " . MAIN_DB_PREFIX . "facturedet_extrafields
					WHERE prodcfish != 0 AND prodcfish IS NOT null) b
				WHERE a.fk_facture = " . $facid . " AND a.rowid = b.fk_object
				ORDER BY a.rowid
			";
		} else {
			$sql = "
				SELECT
					a.fk_product,
					a.total_ht,
					b.prodcfish,
					( (b.prodcfish / 100) * a.total_ht) AS impish,
					a.label,
					a.description,
					b.prodcfish_label
				FROM
					" . MAIN_DB_PREFIX . "facturedet a,
					( SELECT fk_object, prodcfish, prodcfish_label
					FROM " . MAIN_DB_PREFIX . "facturedet_extrafields
					WHERE prodcfish != 0 AND prodcfish IS NOT null) b
				WHERE a.fk_facture = " . $facid . " AND a.rowid = b.fk_object
				ORDER BY a.rowid
			";
		}
		$ass = $db->query($sql);
		$asf = $db->num_rows($ass);
		if ($asf > 0) {
			while ($asd = $db->fetch_object($ass)) {
				$totalish2 = $totalish2 + $asd->impish;
				$imporcen2 = $asd->prodcfish;
				$etiqueta_ish2 = $asd->prodcfish_label;
			}
		}
	}

	$totalDeTraslados = $totalish + $totalish2;

	if ($impuestoish != "NO") {
		if ($factura_tipo == 2) {
			$vowels = array(",", "-");
		} else {
			$vowels = array(",");
		}
		$impuestoish = str_replace($vowels, "", number_format($impuestoish, 2));
		$totalish = str_replace($vowels, "", number_format($totalish, 2));
		$impuestoslocales[$i] = array(
			"totalDeRetenciones" => $total_retlocal,
			"totalDeTraslados" => "" . $totalDeTraslados,
			"tasadeTraslado" => "" . $imporcen,
			"impLocTrasladado" => $etiqueta_ish,
			"importe" => "" . ($totalish)
		);
	}

	if ($totalish2 > 0) {
		$totalish2 = str_replace(",", "", number_format($totalish2, 2));
		$impuestoish2 = $totalish2;
		$factura_total = $factura_total + $totalish2 - $total_retlocal;
		$factura_total = str_replace(",", "", number_format($factura_total, 2));
		$sql = "SELECT count(*) as exist FROM " . MAIN_DB_PREFIX . "cfdimx_facturedet WHERE fk_facture=" . $facid . " AND impuesto='ISH'";
		$ass = $db->query($sql);
		$asd = $db->fetch_object($ass);
		if ($asd->exist == 0) {
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_facturedet (fk_facture,impuesto,importe) VALUES ('" . $id . "','ISH','" . $totalish2 . "')";
			$ass = $db->query($sql);
			$i = 1;
		}
	}

	if ($impuestoish2 != "NO") {
		if ($factura_tipo == 2) {
			$vowels = array(",", "-");
		} else {
			$vowels = array(",");
		}
		$impuestoish2 = str_replace($vowels, "", number_format($impuestoish2, 2));
		$totalish2 = str_replace($vowels, "", number_format($totalish2, 2));
		$impuestoslocales[] = array(
			"totalDeRetenciones" => $total_retlocal,
			"totalDeTraslados" => "" . $totalDeTraslados,
			"tasadeTraslado" => "" . $imporcen2,
			"impLocTrasladado" => $etiqueta_ish2,
			"importe" => "" . ($totalish2)
		);
	}
}

if ($total_retlocal != 0) {
	$n = 0;
	if ($impuestoish != "NO" || $impuestoish2 != "NO") {
		$n = 1;
	} else {
		$factura_total = $factura_total - $total_retlocal;
		$factura_total = str_replace(",", "", number_format($factura_total, 2));
	}
	$sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $facid;
	$resqm = $db->query($sql);
	if ($resqm) {
		$cfdi_m = $db->num_rows($resqm);
		$m = 0;
		if ($cfdi_m > 0) {
			while ($m < $cfdi_m) {
				$obm = $db->fetch_object($resqm);

				if ($total_retlocal < 0) {
					$total_retlocal = abs($total_retlocal);
					$obm->importe   = abs($obm->importe);
				}

				if ($n == 0) {
					$impuestoslocales[$n] = array(
						"totalDeRetenciones" => $total_retlocal,
						"totalDeTraslados" => '0.00',
						"tasadeRetencion" => "" . str_replace(',', '', number_format($obm->tasa, 2)),
						"impLocRetenido" => $obm->codigo,
						"importeRetenido" => "" . str_replace(',', '', number_format($obm->importe, 2))
					);
				} else {
					$impuestoslocales[$n] = array(
						"tasadeRetencion" => "" . str_replace(',', '', number_format($obm->tasa, 2)),
						"impLocRetenido" => $obm->codigo,
						"importeRetenido" => "" . str_replace(',', '', number_format($obm->importe, 2))
					);
				}
				$n++;
				$m++;
			}
		}
	}
}

if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
	$sql = "SELECT multicurrency_code AS divisa FROM " . MAIN_DB_PREFIX . "facture WHERE rowid=" . $facid;
	$ra = $db->query($sql);
	$rb = $db->fetch_object($ra);
	$moneda = $rb->divisa;
} else {
	$moneda = !empty($osd) ? $osd : $conf->currency;
}

$hora_emision = date("H:i:s");
$fecha_emison = $factura_fecha;

// DATOS DEL EMISOR
if($user->id > 0){
	if ($user->fk_warehouse > 0) {
		$emisorsql = "SELECT edc.emisor_rfc, edc.razon_social, edc.regimen, edc.pais, edc.estado, edc.codigo_postal";
		$emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
		$emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
		$emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
		$emisorsql .= " WHERE e.rowid = " . $user->fk_warehouse;

		$reemisorsql = $db->query($emisorsql);

		if ($reemisorsql) {
			$emisor_almacen = $db->fetch_object($emisorsql);
		}

		$rfc_emisor          = $emisor_almacen->emisor_rfc;
		$razon_social_emisor = utf8_decode(utf8_encode($emisor_almacen->razon_social));
		$regimen             = $emisor_almacen->regimen;
		$separa_pais         = explode(":", $emisor_almacen->pais);
		$pais                = utf8_decode($separa_pais[2]);
		$separa_estado       = explode(':', $emisor_almacen->estado);
		$estado_emisor       = getState($separa_estado[0]);
		$estado_emisor       = utf8_decode($estado_emisor);
		$cp                  = $emisor_almacen->codigo_postal;
	} else {
		// Priorizar emisor seleccionado en ficha, si no hay seleccion, usar predeterminado
		$rfc_emisor          = $_SESSION['selected_emisor'] ? $_SESSION['selected_emisor'] : $conf->global->MAIN_INFO_SIREN;
		$razon_social_emisor = utf8_decode(utf8_encode($conf->global->CFDIMX_RAZON_SOCIAL));
		$regimen             = $conf->global->CFDIMX_REGIMEN_FISCAL;
		$separa_pais         = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
		$pais                = utf8_decode($separa_pais[2]);
		$separa_estado       = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
		$estado_emisor       = getState($separa_estado[0]);
		$estado_emisor       = utf8_decode($estado_emisor);
		$cp                  = $conf->global->MAIN_INFO_SOCIETE_ZIP;
	}
}else{
	$sql_warehouse = 'SELECT ba.fk_warehouse';
	$sql_warehouse .= ' FROM llx_facture as f';
	$sql_warehouse .= ' LEFT JOIN llx_paiement_facture as pf on f.rowid = pf.fk_facture';
	$sql_warehouse .= ' LEFT JOIN llx_paiement as p on pf.fk_paiement = p.rowid';
	$sql_warehouse .= ' LEFT JOIN llx_bank as b on p.fk_bank = b.rowid';
	$sql_warehouse .= ' LEFT JOIN llx_bank_account as ba on b.fk_account = ba.rowid';
	$sql_warehouse .= ' WHERE f.rowid = ' . $factura->id;

	$res_warehouse = $db->query($sql_warehouse);
	$obj_warehouse = $db->fetch_object($res_warehouse);
	$id_almacen = $obj_warehouse->fk_warehouse;

	if ($id_almacen > 0) {
		$emisorsql = "SELECT edc.emisor_rfc, edc.razon_social, edc.regimen, edc.pais, edc.estado, edc.codigo_postal";
		$emisorsql .= " FROM llx_cfdimx_emisor_datacomp edc";
		$emisorsql .= " LEFT JOIN llx_c_rfc c ON c.code = edc.emisor_rfc";
		$emisorsql .= " LEFT JOIN llx_entrepot e ON c.rowid = e.fk_rfc";
		$emisorsql .= " WHERE e.rowid = " . $id_almacen;

		$reemisorsql = $db->query($emisorsql);

		if ($reemisorsql) {
			$emisor_almacen = $db->fetch_object($emisorsql);
		}

		$rfc_emisor          = $emisor_almacen->emisor_rfc;
		$razon_social_emisor = utf8_decode(utf8_encode($emisor_almacen->razon_social));
		$regimen             = $emisor_almacen->regimen;
		$separa_pais         = explode(":", $emisor_almacen->pais);
		$pais                = utf8_decode($separa_pais[2]);
		$separa_estado       = explode(':', $emisor_almacen->estado);
		$estado_emisor       = getState($separa_estado[0]);
		$estado_emisor       = utf8_decode($estado_emisor);
		$cp                  = $emisor_almacen->codigo_postal;

		//* Obtencion de password de timbrado desde autofactura
		$sql = "SELECT cfdi.*";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid = pf.fk_facture";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p on pf.fk_paiement = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b on p.fk_bank = b.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba on b.fk_account = ba.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e on ba.fk_warehouse = e.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_rfc as rfc on rfc.rowid = e.fk_rfc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."cfdimx_config as cfdi on cfdi.emisor_rfc = rfc.code";
		$sql .= " WHERE f.rowid =" . $_REQUEST["facid"];
		$sql .= " AND cfdi.entity_id = " . $conf->entity;

		$res = $db->query($sql);
		if ($res) {
			$obj = $db->fetch_object($res);
			$passwd_timbrado = $obj->password_timbrado_txt;
		}

	} else {
		// Priorizar emisor seleccionado en ficha, si no hay seleccion, usar predeterminado
		$rfc_emisor          = $_SESSION['selected_emisor'] ? $_SESSION['selected_emisor'] : $conf->global->MAIN_INFO_SIREN;
		$razon_social_emisor = utf8_decode(utf8_encode($conf->global->CFDIMX_RAZON_SOCIAL));
		$regimen             = $conf->global->CFDIMX_REGIMEN_FISCAL;
		$separa_pais         = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
		$pais                = utf8_decode($separa_pais[2]);
		$separa_estado       = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
		$estado_emisor       = getState($separa_estado[0]);
		$estado_emisor       = utf8_decode($estado_emisor);
		$cp                  = $conf->global->MAIN_INFO_SOCIETE_ZIP;
	}
}

if ($estado_emisor != "") {
	$lugar_exp = $emisor_delompio . " " . $estado_emisor;
} else {
	$lugar_exp = "No identificado";
}
$lugar_exp = $cp;


//DATOS DEL HEADER DEL COMPROBANTE
$header = array();
$sqn = "SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion,tipocambio
	FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero
	WHERE fk_facture=" . $facid;
$rqn = $db->query($sqn);
$numrn = $db->num_rows($rqn);
if ($numrn > 0) {
	$rrn = $db->fetch_object($sqn);
	$sqn = "SELECT code_iso FROM " . MAIN_DB_PREFIX . "c_country WHERE rowid=" . $separa_pais[0];
	$rqn = $db->query($sqn);
	$rsn = $db->fetch_object($rqn);
	$pais = $rsn->code_iso;

	$separa_estado = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
	$sqn = "SELECT code_departement FROM " . MAIN_DB_PREFIX . "c_departements WHERE rowid=" . $separa_estado[0];
	$rqn = $db->query($sqn);
	$rsn = $db->fetch_object($rqn);
	$estado_emisor = $rsn->code_departement;

	$header["tipoCambio"] = (str_replace(array(",", "-"), "", $rrn->tipocambio));
}

$header["fecha"] = $fecha_emison . "T" . $hora_emision;

if ($factura_tipo == 2) {
	$vowels = array(",", "-");
} else {
	$vowels = array(",");
}
$header["subTotal"] = (str_replace($vowels, "", number_format($factura_subtotal, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)));

$header["total"] = (str_replace($vowels, "", number_format($factura_total, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)));
if ($descheader != 0) {
	$auxcp1 = str_replace($vowels, "", number_format($descheader, 2));
	$header["subTotal"] = (str_replace($vowels, "", number_format($auxsubtotal, 2)));
	$header["descuento"] = (str_replace($vowels, "", number_format($auxcp1, 2)));
	$auxcpp1 = str_replace($vowels, "", number_format($auxsubtotal, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL));
	$auxcpp2 = str_replace($vowels, "", number_format($factura_iva, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL));
	$sumcpp3 = $auxsubtotal - $auxcp1 + $auxcpp2;
	$factura_total = $sumcpp3;
	$header["total"] = (str_replace($vowels, "", number_format($sumcpp3, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL)));
}else{
	$header["descuento"] = 0;
}
$header["tipoDeComprobante"] = $tipoComprobante;

$header["lugarExpedicion"] = limpiar($lugar_exp);
if ($factura_formapago == null || $factura_formapago == null || $factura_formapago == '') {
	$factura_formapago = null;
}
if ($factura_condicionpago == null || $factura_condicionpago == null || $factura_condicionpago == '') {
	$factura_condicionpago = null;
}

$header["formaDePago"] = limpiar(html_entity_decode($factura_formapago));
if (is_null($factura_condicionpago)) {
} else {
	$header["condicionesDePago"] = limpiar(html_entity_decode($factura_condicionpago));
}

$header["metodoDePago"] = $factura_metodopago;

//COMPLEMENTARIOS HEADER
$parametros = array();
if ($moneda != "") {
	$header["moneda"] = trim(preg_replace("/ +/", " ", $moneda));
}
$sqlk = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facture_extrafields LIKE 'tipodecambiocfdi'";
$resqlk = $db->query($sqlk);
$existeTC = $db->num_rows($resqlk);
$banExtratipo = 0;
if ($existeTC > 0) {
	$sqlk = "SELECT tipodecambiocfdi FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object=" . $facid;
	$resqlk = $db->query($sqlk);
	$resk = $db->fetch_object($resqlk);
	if ($resk->tipodecambiocfdi != null && $resk->tipodecambiocfdi != null && $resk->tipodecambiocfdi != "") {
		$header["tipoCambio"] = $resk->tipodecambiocfdi;
		$banExtratipo = 1;
	}
}
if ($serie != "") {
	$header["serie"] = trim(preg_replace("/ +/", " ", $serie));
}
if ($folio != "") {
	$header["folio"] = trim(preg_replace("/ +/", " ", $folio));
}
if ($cuenta != "") {
	$header["numCtaPago"] = trim(preg_replace("/ +/", " ", $cuenta));
}

if (strcmp($version_cfdi_sat, "4.0") == 0) {
	$header["version"] = "4.0";
	// Si no se tiene código de exportación por default asigna 01 (no aplica)
	$header["exportacion"] = $factura->array_options["options_clave_expor"] ? $factura->array_options["options_clave_expor"] : "01";

	if (!in_array($header["exportacion"], array("01", "02", "03", "04"))) {
		$errores_factura[] = "El campo Exportación no contiene un valor establecido por el SAT.";
	}
}

//ADICIONALES
$adicionales = array();
$adicionales["servicio_id"] = "2";

// Inicia Ajustes Factura Global
$sql	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_type_document WHERE fk_facture = " . $facid;
$res_sql = $db->query($sql);

if ($res_sql) {
	$obj_info_c = $db->fetch_object($res_sql);

	if ($obj_info_c->tipo_document == 8) {
		$sql_info_global  = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_factura_global";
		$sql_info_global .= " WHERE";
		$sql_info_global .= " entity = " . $conf->entity;
		$sql_info_global .= " AND facid = " . $facid;
		$res_info_global  = $db->query($sql_info_global);
		$num_info_global  = $db->num_rows($res_info_global);

		if ($num_info_global > 0) {
			$obj_info_global = $db->fetch_object($res_info_global);

			$datos_infoglobal[0] = array(
				"anio"          => $obj_info_global->anio,
				"meses"         => $obj_info_global->meses,
				"periodicidad"  => $obj_info_global->periodicidad
			);

			$adicionales["informacionGlobal"] = $datos_infoglobal;
		}
	}
}
// Termina Ajustes Factura Global

// Nueva Validacion CFDI Rel
$tipo_relacion = $factura->array_options["options_cfdidoctiporelacion"];

if (!is_null($tipo_relacion)) {
	if ((int) $tipo_relacion > 0) {
		$sql_cfdi_relacionados = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_cfdi_relacionados WHERE fk_facture = " . $facid;
		$res_cfdi_relacionados = $db->query($sql_cfdi_relacionados);
		$num_cfdi_relacionados = $db->num_rows($res_cfdi_relacionados);

		if ($num_cfdi_relacionados > 0) {
			$contador = 0;
			while ($obj_cfdi_relacionado = $db->fetch_object($res_cfdi_relacionados)) {
				$docrelacionado[$contador] = array(
					"tipoRelacion"      => $tipo_relacion,
					"uuidRelacionado"   => $obj_cfdi_relacionado->uuid
				);
				$contador++;
			}

			$adicionales["relacionado"] = $docrelacionado;
		} else {
			$errores_factura[] = "Error 7000: La factura tiene una Clave (" . $tipo_relacion . ") para CFDI Relacionados, pero no tiene ningun UUID Relacionado.";
		}
	}
}

// Inicia Validaciones Factura
if ($factura_metodopago == "") {
	$errores_factura[] = "El campo Método de Pago esta vacío y es obligatorio.";
}

if ($factura_usocfdi == "") {
	$errores_factura[] = "El campo Uso CFDI esta vacío y es obligatorio.";
}

if ($regimen <= 0) {
	$errores_factura[] = "El campo Regimen Fiscal del Emisor contiene un valor que no esta el catálogo proporcionado por el SAT.";
}
// Termina Validaciones Factura

if ($retenciones != "" && $retenciones != null) {
	$adicionales["retenciones"] = $retenciones;
	$adicionales2["retenciones"] = $retenciones2;
	// realizamos la resta de las retenciones en este caso se aplicara directamente al total
	$index = count($adicionales["retenciones"]);
	$suma_retenciones = 0;
	for ($ir = 0; $ir < $index; $ir++) {
		$suma_retenciones = $suma_retenciones + $adicionales2["retenciones"][$ir]["importe"];
	}

	if ($factura_total < 0) {
		$factura_total = ($factura_total + $suma_retenciones);
	} else {
		$factura_total = ($factura_total - $suma_retenciones);
	}

	if ($factura_tipo == 2) {
		$vowels = array(",", "-");
	} else {
		$vowels = array(",");
	}
	$header["total"] = (str_replace($vowels, "", number_format($factura_total, 2)));
}
if ($impuestoish != "NO" || $impuestoish2 != "NO" || $total_retlocal > 0) {
	$adicionales["ilocales"] = $impuestoslocales;
}
$sqn = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero WHERE fk_facture = " . $facid;
$rqn = $db->query($sqn);
$numrn = $db->num_rows($rqn);

if ($numrn > 0) {
	$rsqn = $db->fetch_object($rqn);

	if ($rsqn->clv_pedimento == '' || $rsqn->clv_pedimento == null || $rsqn->clv_pedimento == null) {
		$rsqn->clv_pedimento = null;
	}

	if ($rsqn->no_exportador == '' || $rsqn->no_exportador == null || $rsqn->no_exportador == null) {
		$rsqn->no_exportador = null;
	}

	if ($rsqn->incoterm == '' || $rsqn->incoterm == null || $rsqn->incoterm == null) {
		$rsqn->incoterm = null;
	}

	if ($rsqn->observaciones == '' || $rsqn->observaciones == null || $rsqn->observaciones == null) {
		$rsqn->observaciones = null;
	}

	if ($rsqn->tipocambio == '' || $rsqn->tipocambio == null || $rsqn->tipocambio == null) {
		$rsqn->tipocambio = null;
	}

	if ($rsqn->certificadoorigen == '' || $rsqn->certificadoorigen == null || $rsqn->certificadoorigen == null) {
		$rsqn->certificadoorigen = null;
	}

	if ($rsqn->subdivision == '' || $rsqn->subdivision == null || $rsqn->subdivision == null) {
		$rsqn->subdivision = null;
	}

	if ($rsqn->totalusd == '' || $rsqn->totalusd == null || $rsqn->totalusd == null) {
		$rsqn->totalusd = null;
	}

	if ($rsqn->motivotraslado == '' || $rsqn->motivotraslado == null || $rsqn->motivotraslado == null) {
		$rsqn->motivotraslado = null;
	}

	if ($rsqn->numcertificadoorigen == '' || $rsqn->numcertificadoorigen == null || $rsqn->numcertificadoorigen == null) {
		$rsqn->numcertificadoorigen = null;
	}

	$facex = new Facture($db);
	$facex->fetch($facid);
	$socex = new Societe($db);
	$socex->fetch($facex->socid);
	$sqlex = "SELECT code_iso FROM " . MAIN_DB_PREFIX . "c_country WHERE code='" . $socex->country_code . "'";
	$rqex = $db->query($sqlex);
	$rslex = $db->fetch_object($rqex);
	$sqln2 = "SELECT preciousd,noidentificacion,fraccion_arancelaria,unidadcext,valor_unitario,cantidad_aduana,marca
			FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero_mercancia a, " . MAIN_DB_PREFIX . "facturedet b
			WHERE a.fk_facture=" . $facid . " AND a.fk_facture=b.fk_facture AND a.fk_facturedet=b.rowid";
	$reqsn2 = $db->query($sqln2);
	$numrn2 = $db->num_rows($reqsn2);
	$cona = 0;
	if ($numrn2 > 0) {
		while ($rsqn2 = $db->fetch_object($reqsn2)) {
			if ($rsqn2->preciousd != '' && $rsqn2->noidentificacion != '') {
				if ($rsqn2->unidadcext != null && trim($rsqn2->unidadcext) != "") {
					$unidadcext = $rsqn2->unidadcext;
				} else {
					$unidadcext = null;
				}
				if ($rsqn2->valor_unitario != null && trim($rsqn2->valor_unitario) != "") {
					$valor_unitario = $rsqn2->valor_unitario;
				} else {
					$valor_unitario = null;
				}
				if ($rsqn2->cantidad_aduana != null && trim($rsqn2->cantidad_aduana) != "") {
					$cantidad_aduana = $rsqn2->cantidad_aduana;
				} else {
					$cantidad_aduana = null;
				}
				if ($rsqn2->marca != null && trim($rsqn2->marca) != "") {
					$marca = $rsqn2->marca;
				} else {
					$marca = null;
				}
				if ($cona == 0) {
					$comercio[$cona] = array(
						'tipoOperacion' => $rsqn->tipo_operacion,
						'claveDePedimento' => $rsqn->clv_pedimento,
						'numeroExportadorConfiable' => $rsqn->no_exportador,
						'incoterm' => $rsqn->incoterm,
						'observaciones' => $rsqn->observaciones,
						'rNumRegIdTrib' => $rsqn->num_identificacion,
						'tipoCambio' => $rsqn->tipocambio,
						'certificadoOrigen' => $rsqn->certificadoorigen,
						'subdivision' => $rsqn->subdivision,
						'motivoTraslado' => $rsqn->motivotraslado,
						'numCertificadoOrigen' => $rsqn->numcertificadoorigen,
						'totalUSD' => str_replace(array("-", ","), "", number_format($rsqn->totalusd, 2)),
						'valorDolares' => str_replace(array("-", ","), "", number_format($rsqn2->preciousd, 2)),
						'noIdentificacion' => $rsqn2->noidentificacion,
						'residenciaFiscal' => $rslex->code_iso,
						'fraccionArancelaria' => $rsqn2->fraccion_arancelaria,
						'unidadAduana' => $unidadcext,
						'valorUnitarioAduana' => str_replace(array("-", ","), "", number_format($valor_unitario, 2)),
						'cantidadAduana' => $cantidad_aduana,
						'marca' => $marca
					);
					$cona++;
				} else {
					$comercio[$cona] = array(
						'valorDolares' => str_replace(array("-", ","), "", number_format($rsqn2->preciousd, 2)),
						'noIdentificacion' => $rsqn2->noidentificacion,
						'fraccionArancelaria' => $rsqn2->fraccion_arancelaria,
						'unidadAduana' => $unidadcext,
						'valorUnitarioAduana' => str_replace(array("-", ","), "", number_format($valor_unitario, 2)),
						'cantidadAduana' => $cantidad_aduana,
						'marca' => $marca
					);
					$cona++;
				}
			}
		}
	} else {
		$comercio[0] = array(
			'tipoOperacion' => $rsqn->tipo_operacion,
			'claveDePedimento' => $rsqn->clv_pedimento,
			'numeroExportadorConfiable' => $rsqn->no_exportador,
			'incoterm' => $rsqn->incoterm,
			'observaciones' => $rsqn->observaciones,
			'rNumRegIdTrib' => $rsqn->num_identificacion,
			'tipoCambio' => $rsqn->tipocambio,
			'certificadoOrigen' => $rsqn->certificadoorigen,
			'subdivision' => $rsqn->subdivision,
			'motivoTraslado' => $rsqn->motivotraslado,
			'numCertificadoOrigen' => $rsqn->numcertificadoorigen,
			'totalUSD' => str_replace(array("-", ","), "", number_format($rsqn->totalusd, 2))
		);
	}
	if ($cona == 0) {
		$comercio[0] = array(
			'tipoOperacion' => $rsqn->tipo_operacion,
			'claveDePedimento' => $rsqn->clv_pedimento,
			'numeroExportadorConfiable' => $rsqn->no_exportador,
			'incoterm' => $rsqn->incoterm,
			'observaciones' => $rsqn->observaciones,
			'rNumRegIdTrib' => $rsqn->num_identificacion,
			'tipoCambio' => $rsqn->tipocambio,
			'certificadoOrigen' => $rsqn->certificadoorigen,
			'subdivision' => $rsqn->subdivision,
			'motivoTraslado' => $rsqn->motivotraslado,
			'numCertificadoOrigen' => $rsqn->numcertificadoorigen,
			'totalUSD' => str_replace(array("-", ","), "", number_format($rsqn->totalusd, 2))
		);
	}
	$adicionales["comercio"] = $comercio;
}


//Inicia Carta Porte

$validar_carta_porte	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte WHERE facid = " . $facid . " AND entity =" . $entidad;
$res_validar_carta_porte = $db->query($validar_carta_porte);
$existe_carta_porte	  = $db->num_rows($res_validar_carta_porte);

if ($existe_carta_porte > 0) {
	//Cabeceras Carta Porte
	$sql_head_mercancia	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_mercancia WHERE facid = " . $facid . " AND entity =" . $entidad;
	$res_sql_head_mercancia = $db->query($sql_head_mercancia);
	$num_tot_mercancia	  = $db->num_rows($res_sql_head_mercancia);

	if ($num_tot_mercancia > 0) {
		$obj_head_mercancia = $db->fetch_object($res_sql_head_mercancia);

		$pesoBrutoTotal = $obj_head_mercancia->peso_bruto_total;
		$unidadPeso = $obj_head_mercancia->unidad_peso;
		$pesoNetoTotal = $obj_head_mercancia->peso_neto_total;
		$numTotalMercancias = $obj_head_mercancia->num_total_mercancias;
		$cargoPorTasacion = $obj_head_mercancia->cargo_por_tasacion;
	}

	$obj_head_carta_porte = $db->fetch_object($res_validar_carta_porte);
	$totalDistRec = $obj_head_carta_porte->tot_distancia_recorrida;

	$transporte_internacional = ($obj_head_carta_porte->transporte_internacional == 1 ? utf8_decode("Sí") : 'No');
	$entradaSalidaMerc		= $obj_head_carta_porte->entrada_salida;
	$viaEntradaSalida		 = $obj_head_carta_porte->via_entrada_salida;

	if ($obj_head_carta_porte->transporte_internacional == 2) {
		$entradaSalidaMerc = null;
		$viaEntradaSalida  = null;
	}

	//Se Valida el Tipo de Transporte seleccionado
	$tipo_transporte_seleccionado = (int) $obj_head_carta_porte->tipo_transporte;
	$obj_autotransporte_info = null;
	$obj_transporte_maritimo_info = null;
	$obj_transporte_aereo_info = null;
	$obj_transporte_ferroviario_info = null;

	switch ($tipo_transporte_seleccionado) {
		case 1:
			//Se busca si tiene remolques asociados
			$sql_remolques	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_autotransporte_remolques WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_sql_remolques = $db->query($sql_remolques);
			$num_tot_remolques = $db->num_rows($res_sql_remolques);
			$lista_remolques   = null;

			if ($num_tot_remolques > 0) {
				while ($obj_remolque = $db->fetch_object($res_sql_remolques)) {
					$lista_remolques[] = array(
						"placa" => $obj_remolque->placas,
						"subTipoRem" => $obj_remolque->sub_tipo
					);
				}
			}

			//Se busca si tiene configuracion vehicular asociado
			$sql_config_vehicular = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_autotransporte_i_vehicular WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_config_vehicular = $db->query($sql_config_vehicular);
			$num_config_vehicular = $db->num_rows($res_config_vehicular);
			$lista_config_vehicular = null;

			if ($num_config_vehicular > 0) {
				while ($obj_config_vehicular = $db->fetch_object($res_config_vehicular)) {
					$lista_config_vehicular[] = array(
						"configVehicular" => $obj_config_vehicular->config_vehicular,
						"placaVM" => $obj_config_vehicular->placa_vm,
						"anioModeloVM" => $obj_config_vehicular->anio_modelo_vm
					);
				}
			}

			//Se busca si tiene seguros asociados
			$sql_seguros = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_autotransporte_seguros WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_seguros = $db->query($sql_seguros);
			$num_seguros = $db->num_rows($res_seguros);
			$lista_seguros = null;

			if ($num_seguros > 0) {
				while ($obj_seguro = $db->fetch_object($res_seguros)) {
					$lista_seguros[] = array(
						"aseguraRespCivil" => $obj_seguro->aseguraRespCivil,
						"polizaRespCivil" => $obj_seguro->polizaRespCivil,
						"aseguraMedAmbiente" => $obj_seguro->aseguraMedAmbiente,
						"polizaMedAmbiente" => $obj_seguro->polizaMedAmbiente,
						"aseguraCarga" => $obj_seguro->aseguraCarga,
						"polizaCarga" => $obj_seguro->polizaCarga,
						"primaSeguro" => $obj_seguro->primaSeguro
					);
				}
			}

			$sql_head_autotransporte     = "SELECT *
				FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_autotransporte
				WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_sql_head_autotransporte = $db->query($sql_head_autotransporte);
			$num_tot_autotransporte      = $db->num_rows($res_sql_head_autotransporte);
			$obj_autotransporte = $db->fetch_object($res_sql_head_autotransporte);
			$informacion_autotransporte = array(
				"permSCT" => $obj_autotransporte->permiso_sct,
				"numPermisoSCT" => $obj_autotransporte->num_permiso_sct,
				"identificacionVehicular" => $lista_config_vehicular,
				"seguros" => $lista_seguros,
				"remolques" => $lista_remolques
			);

			$obj_autotransporte_info = $informacion_autotransporte;
			break;

		case 2:
			$sql_transporte_maritimo_contenedores      = "SELECT *
				FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_transporte_maritimo_contenedores
				WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_transporte_maritimo_contenedores      = $db->query($sql_transporte_maritimo_contenedores);
			$num_tot_transporte_maritimo_contenedores  = $db->num_rows($res_transporte_maritimo_contenedores);
			$lista_contenedores_maritimos = null;
			if ($num_tot_transporte_maritimo_contenedores > 0) {
				while ($obj_tm_contenedor = $db->fetch_object($res_transporte_maritimo_contenedores)) {
					$lista_contenedores_maritimos[] = array(
						"matricula" => $obj_tm_contenedor->matricula,
						"tipo" => $obj_tm_contenedor->tipo,
						"numPrecinto" => $obj_tm_contenedor->num_precinto
					);
				}
			}

			$sql_head_transporte_maritimo = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_transporte_maritimo WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_sql_head_transporte_maritimo = $db->query($sql_head_transporte_maritimo);
			$num_tot_transporte_maritimo	  = $db->num_rows($res_sql_head_transporte_maritimo);

			$obj_transporte_maritimo = $db->fetch_object($res_sql_head_transporte_maritimo);

			$informacion_transporte_maritimo = array(
				"permSCT" => $obj_transporte_maritimo->permiso_sct,
				"numPermisoSCT" => $obj_transporte_maritimo->num_permiso_sct,
				"nombreAseg" => $obj_transporte_maritimo->nom_aseg,
				"numPolizaSeguro" => $obj_transporte_maritimo->num_poliza_seguro,
				"tipoEmbarcacion" => $obj_transporte_maritimo->tipo_embarcacion,
				"matricula" => $obj_transporte_maritimo->matricula,
				"numeroOMI" => $obj_transporte_maritimo->num_omi,
				"anioEmbarcacion" => $obj_transporte_maritimo->anio_embarcacion,
				"nombreEmbarc" => $obj_transporte_maritimo->nombre_embarcacion,
				"nacionalidadEmbarc" => $obj_transporte_maritimo->nacionalidad_embarcacion,
				"unidadesDeArqBruto" => $obj_transporte_maritimo->unidades_arq_bruto,
				"tipoCarga" => $obj_transporte_maritimo->tipo_carga,
				"numCertITC" => $obj_transporte_maritimo->numcert_itc,
				"eslora" => $obj_transporte_maritimo->eslora,
				"manga" => $obj_transporte_maritimo->manga,
				"calado" => $obj_transporte_maritimo->calado,
				"lineaNaviera" => $obj_transporte_maritimo->linea_naviera,
				"nombreAgenteNaviero" => $obj_transporte_maritimo->nom_agente_naviero,
				"numAutorizacionNaviero" => $obj_transporte_maritimo->num_aut_naviero,
				"numViaje" => $obj_transporte_maritimo->num_viaje,
				"numConocEmbarc" => $obj_transporte_maritimo->num_conoc_embarc,
				"contenedores" => $lista_contenedores_maritimos
			);

			$obj_transporte_maritimo_info = $informacion_transporte_maritimo;
			break;

		case 3:
			$sql_head_transporte_aereo     = "SELECT *
				FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_transporte_aereo
				WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_sql_head_transporte_aereo = $db->query($sql_head_transporte_aereo);
			$num_tot_transporte_aereo      = $db->num_rows($res_sql_head_transporte_aereo);
			$obj_transporte_aereo = $db->fetch_object($res_sql_head_transporte_aereo);
			$informacion_transporte_aereo = array(
				"permSCT" => $obj_transporte_aereo->permiso_sct,
				"numPermisoSCT" => $obj_transporte_aereo->num_permiso_sct,
				"matriculaAeronave" => $obj_transporte_aereo->matricula_aeronave,
				"nombreAseg" => $obj_transporte_aereo->nom_aseg,
				"numPolizaSeguro" => $obj_transporte_aereo->num_poliza_seguro,
				"numeroGuia" => $obj_transporte_aereo->num_guia,
				"lugarContrato" => $obj_transporte_aereo->lugar_contrato,
				"codigoTransportista" => $obj_transporte_aereo->codigo_transportista,
				"RFCEmbarcador" => $obj_transporte_aereo->rfc_embarcador,
				"numRegIdTribEmbarc" => $obj_transporte_aereo->numregidtrib_embarc,
				"residenciaFiscalEmbarc" => $obj_transporte_aereo->residencia_fiscal_embarc,
				"nombreEmbarcador" => $obj_transporte_aereo->nom_embarcador
			);

			$obj_transporte_aereo_info = $informacion_transporte_aereo;
			break;

		case 4:
			$sql_transporte_ferroviario_contenedores      = "SELECT *
				FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_transporte_f_contenedores
				WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_transporte_ferroviario_contenedores      = $db->query($sql_transporte_ferroviario_contenedores);
			$num_tot_transporte_ferroviario_contenedores  = $db->num_rows($res_transporte_ferroviario_contenedores);
			$lista_contenedores_ferrocaril = null;
			if ($num_tot_transporte_ferroviario_contenedores > 0) {
				while ($obj_tf_contenedor = $db->fetch_object($res_transporte_ferroviario_contenedores)) {
					$lista_contenedores_ferrocaril[] = array(
						'tipo' => $obj_tf_contenedor->tipo,
						'pesoContenedorVacio' => $obj_tf_contenedor->peso_contendor_vacio,
						'pesoNetoMercancia' => $obj_tf_contenedor->peso_neto_mercancia
					);
				}
			}

			$sql_head_transporte_ferroviario     = "SELECT *
				FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_transporte_ferroviario
				WHERE facid = " . $facid . " AND entity =" . $entidad;
			$res_sql_head_transporte_ferroviario = $db->query($sql_head_transporte_ferroviario);
			$num_tot_transporte_ferroviario      = $db->num_rows($res_sql_head_transporte_ferroviario);

			$obj_transporte_ferroviario = $db->fetch_object($res_sql_head_transporte_ferroviario);

			$informacion_transporte_aereo_ferroviario = array(
				"tipoDeServicio" => $obj_transporte_ferroviario->tipo_servicio,
				"nombreAseg" => $obj_transporte_ferroviario->nom_aseguradora,
				"numPolizaSeguro" => $obj_transporte_ferroviario->num_poliza_seguro,
				"tipoTrafico" => $obj_transporte_ferroviario->tipo_trafico,
				"tipoDerechoDePaso" => $obj_transporte_ferroviario->tipo_derecho_paso,
				"kilometrajePagado" => $obj_transporte_ferroviario->kilometraje_pagado,
				"tipoCarro" => $obj_transporte_ferroviario->tipo_carro,
				"matriculaCarro" => $obj_transporte_ferroviario->matricula_carro,
				"guiaCarro" => $obj_transporte_ferroviario->guia_carro,
				"toneladasNetasCarro" => $obj_transporte_ferroviario->toneladas_netas_carro,
				'contenedores' => $lista_contenedores_ferrocaril
			);

			$obj_transporte_ferroviario_info = $informacion_transporte_aereo_ferroviario;
			break;
	}

	$sql_direcciones	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_direcciones WHERE facid = " . $facid . " AND entity =" . $entidad;
	$res_sql_direcciones = $db->query($sql_direcciones);
	$num_tot_direcciones = $db->num_rows($res_sql_direcciones);

	$contador_global = 1;
	$lista_ubicaciones = null;

	if ($num_tot_direcciones > 0) {
		$totalDistRec = 0;
		while ($obj_direccion = $db->fetch_object($res_sql_direcciones)) {
			$domicilio = null;
			$domicilio[] = array(
				'calle'          => $obj_direccion->calle,
				'numeroInterior' => $obj_direccion->num_int,
				'numeroExterior' => $obj_direccion->num_ext,
				'colonia'        => $obj_direccion->colonia,
				'referencia'     => $obj_direccion->referencia,
				'codigoPostal'   => $obj_direccion->cp,
				'localidad'      => $obj_direccion->localidad,
				'municipio'      => $obj_direccion->municipio,
				'estado'         => $obj_direccion->estado,
				'pais'           => $obj_direccion->pais
			);

			if ($obj_direccion->tipo_ubicacion == 'Destino') {
				$totalDistRec += $obj_direccion->distancia_recorrida;
			}

			$residencia_fiscal = $obj_direccion->residencia_fiscal;
			if ($obj_direccion->numregidtrib == null) {
				$residencia_fiscal = null;
			}

			$lista_ubicaciones[] = array(
				'distanciaRecorrida'          => $obj_direccion->distancia_recorrida,
				'tipoEstacion'                => $obj_direccion->tipo_estacion,
				'fechaHoraSalidaLlegada'      => $obj_direccion->fechahora,
				'IDUbicacion'                 => $obj_direccion->id_ubicacion,
				'tipoUbicacion'               => $obj_direccion->tipo_ubicacion,
				'nombreRemitenteDestinatario' => $obj_direccion->nombre,
				'RFCRemitenteDestinatario'    => $obj_direccion->rfc,
				'numRegIdTrib'                => $obj_direccion->numregidtrib,
				'residenciaFiscal'            => $residencia_fiscal,
				'numEstacion'                 => $obj_direccion->num_estacion,
				'nombreEstacion'              => $obj_direccion->nom_estacion,
				'navegacionTrafico'           => $obj_direccion->navegacion_trafico,
				'domicilio'                   => $domicilio
			);
		}
	}

	$sql_head_mercancias	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_mercancias WHERE facid = " . $facid . " AND entity =" . $entidad;
	$res_sql_head_mercancias = $db->query($sql_head_mercancias);
	$num_tot_mercancias	  = $db->num_rows($res_sql_head_mercancias);
	$lista_mercancia = null;
	if ($num_tot_mercancias > 0) {
		while ($obj_mercancias = $db->fetch_object($res_sql_head_mercancias)) {
			$cantidad_transporta  = null;
			$detalle			  = null;
			$guias_identificacion = null;
			$pedimentos		   = null;

			$sql_mercancia_transporta = '';
			$sql_mercancia_transporta = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_cantidad_transporta WHERE facid = " . $facid . " AND entity =" . $entidad . " AND fk_product = " . $obj_mercancias->fk_product;
			$res_mercancia_transporta = $db->query($sql_mercancia_transporta);
			$num_mercancia_transporta = $db->num_rows($res_mercancia_transporta);

			if ($num_mercancia_transporta > 0) {
				while ($obj_mercancia_transporta = $db->fetch_object($res_mercancia_transporta)) {
					$cantidad_transporta[] = array(
						"IDDestino" => $obj_mercancia_transporta->id_destino,
						"IDOrigen" => $obj_mercancia_transporta->id_origen,
						"cantidad" => $obj_mercancia_transporta->cantidad,
						"cvesTransporte" => $obj_mercancia_transporta->cvestransporte
					);
				}
			}

			$sql_detalle_mercancia = '';
			$sql_detalle_mercancia = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_detalle_mercancia WHERE facid = " . $facid . " AND entity =" . $entidad . " AND fk_product = " . $obj_mercancias->fk_product;
			$res_detalle_mercancia = $db->query($sql_detalle_mercancia);
			$num_detalle_mercancia = $db->num_rows($res_detalle_mercancia);

			if ($num_detalle_mercancia > 0) {
				while ($obj_detalle_mercancia = $db->fetch_object($res_detalle_mercancia)) {
					$detalle[] = array(
						"numPiezas" => $obj_detalle_mercancia->num_piezas,
						"pesoBruto" => $obj_detalle_mercancia->peso_bruto,
						"pesoNeto" => $obj_detalle_mercancia->peso_neto,
						"pesoTara" => $obj_detalle_mercancia->peso_tara,
						"unidadPeso" => $obj_detalle_mercancia->unidad_peso
					);
				}
			}

			$sql_guias_identificacion = '';
			$sql_guias_identificacion = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_guias_identificacion WHERE facid = " . $facid . " AND entity =" . $entidad . " AND fk_product = " . $obj_mercancias->fk_product;
			$res_guias_identificacion = $db->query($sql_guias_identificacion);
			$num_guias_identificacion = $db->num_rows($res_guias_identificacion);

			if ($num_guias_identificacion > 0) {
				while ($obj_guia_identificacion = $db->fetch_object($res_guias_identificacion)) {
					$guias_identificacion[] = array(
						"numeroGuiaIdentificacion" => $obj_guia_identificacion->numero,
						"descripGuiaIdentificacion" => $obj_guia_identificacion->descripcion,
						"pesoGuiaIdentificacion" => $obj_guia_identificacion->peso
					);
				}
			}

			$sql_pedimentos = '';
			$sql_pedimentos = "SELECT *
				FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_pedimentos
				WHERE facid = " . $facid . " AND entity =" . $entidad . " AND fk_product = " . $obj_mercancias->fk_product;
			$res_pedimentos = $db->query($sql_pedimentos);
			$num_pedimentos = $db->num_rows($res_pedimentos);

			if ($num_pedimentos > 0) {
				while ($obj_pedimento = $db->fetch_object($res_pedimentos)) {
					$pedimentos[] = array(
						"pedimento" => $obj_pedimento->pedimento
					);
				}
			}

			if (is_null($obj_mercancias->material_peligroso)) {
				$material_peligroso = null;
			} else {
				$material_peligroso = ($obj_mercancias->material_peligroso == 1 ? utf8_decode("Sí") : 'No');
			}

			$lista_mercancia[] = array(
				'cantidad'              => $obj_mercancias->cantidad,
				'descripcion'           => $obj_mercancias->descripcion,
				'bienesTransp'          => $obj_mercancias->bienestransp,
				'claveSTCC'             => $obj_mercancias->clavestcc,
				'claveUnidad'           => $obj_mercancias->claveunidad,
				'unidad'                => $obj_mercancias->unidad,
				'dimensiones'           => $obj_mercancias->dimensiones,
				'materialPeligroso'     => $material_peligroso,
				'cveMaterialPeligroso'  => $obj_mercancias->cve_material_peligroso,
				'embalaje'              => $obj_mercancias->embalaje,
				'descripEmbalaje'       => $obj_mercancias->desc_embalaje,
				'pesoEnKg'              => $obj_mercancias->peso_en_kg,
				'valorMercancia'        => $obj_mercancias->valor_mercancia,
				'moneda'                => $obj_mercancias->moneda,
				'fraccionArancelaria'   => $obj_mercancias->fraccion_arancelaria,
				'UUIDComercioExt'       => $obj_mercancias->uuidcomercioext,
				'cantidadTransporta'    => $cantidad_transporta,
				'detalle'               => $detalle,
				'guiasIdentificacion'   => $guias_identificacion,
				'pedimentos'            => $pedimentos
			);
		}
	}

	//Se valida los datos para Figura Transporte
	$sql_head_figura_transporte	 = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_carta_porte_figura_transporte WHERE facid = " . $facid . " AND entity =" . $entidad;
	$res_sql_head_figura_transporte = $db->query($sql_head_figura_transporte);
	$num_tot_figura_transporte	  = $db->num_rows($res_sql_head_figura_transporte);
	$lista_figura_transporte = null;
	if ($num_tot_figura_transporte > 0) {
		while ($obj_figura_tansporte = $db->fetch_object($res_sql_head_figura_transporte)) {
			if ($obj_figura_tansporte->tipo > 0) {
				$domicilio_figura = null;
				$domicilio_figura[] = array(
					"calle"         => $obj_figura_tansporte->calle,
					"numeroExterior"=> $obj_figura_tansporte->num_ext,
					"numeroInterior"=> $obj_figura_tansporte->num_int,
					"colonia"       => $obj_figura_tansporte->colonia,
					"localidad"     => $obj_figura_tansporte->localidad,
					"referencia"    => $obj_figura_tansporte->referencia,
					"codigoPostal"  => $obj_figura_tansporte->cp,
					"municipio"     => $obj_figura_tansporte->municipio,
					"estado"        => $obj_figura_tansporte->estado,
					"pais"          => $obj_figura_tansporte->pais
				);

				$lista_figura_transporte[] = array(
					"tipoFigura"            => $obj_figura_tansporte->tipo,
					"numLicencia"           => $obj_figura_tansporte->num_licencia,
					"RFCFigura"             => $obj_figura_tansporte->rfc,
					"nombreFigura"          => $obj_figura_tansporte->nombre,
					"numRegIdTribFigura"    => $obj_figura_tansporte->numregidtrib,
					"residenciaFiscalFigura"=> $obj_figura_tansporte->residencia_fiscal,
					"partesTransporte"      => $obj_figura_tansporte->parte_transporte,
					"domicilio"             => $domicilio_figura
				);
			}
		}
	}

	$mercancias = array(
		'pesoBrutoTotal'        => $pesoBrutoTotal,
		'unidadPeso'            => $unidadPeso,
		'pesoNetoTotal'         => $pesoNetoTotal,
		'numTotalMercancias'    => $numTotalMercancias,
		'cargoPorTasacion'      => $cargoPorTasacion,
		'mercancia'             => $lista_mercancia,
		'transporteaereo'       => $obj_transporte_aereo_info,
		'transporteferroviario' => $obj_transporte_ferroviario_info,
		'transportemaritimo'    => $obj_transporte_maritimo_info,
		'autotransporte'        => $obj_autotransporte_info
	);

	if ($obj_autotransporte_info == null && $obj_transporte_ferroviario_info == null) {
		$totalDistRec = null;
	}

	$cartaporte[] = array(
		'entradaSalidaMerc' => $entradaSalidaMerc,
		'viaEntradaSalida'  => $viaEntradaSalida,
		'transpInternac'    => $transporte_internacional,
		'cveTransporte'     => $obj_head_carta_porte->tipo_transporte,
		'totalDistRec'      => $totalDistRec,
		'paisOrigenDestino' => $obj_head_carta_porte->pais_origen_destino,
		'ubicaciones'       => $lista_ubicaciones,
		'mercancias'        => $mercancias,
		'figuratransporte'  => $lista_figura_transporte
	);

	$adicionales["cartaporte"] = $cartaporte;
}
//Termina Carta Porte

//Inicia Ajuste para Factura Traslado
if ($header["tipoDeComprobante"] == 'T') {
	$moneda                      = "XXX";
	$header["moneda"]            = "XXX";
	$header["subTotal"]          = "0";
	$header["total"]             = "0";
	$header["metodoDePago"]      = null;
	$header["formaDePago"]       = null;
	$header["condicionesDePago"] = null;
}
//Termina Ajuste para Factura de Traslado

//DATOS DEL EMISOR
$emisor = array();
$emisor["emisorRFC"] = $rfc_emisor;
$emisor["emisorRegimen"] = limpiar($regimen);
//COMPLEMENTARIOS EMISOR
if ($razon_social_emisor != "") {
	$razon_social_emisor = trim(mb_strtoupper($razon_social_emisor));
	$emisor["nombre"] = limpiar($razon_social_emisor);

	// Inicia Validación por si el RFC del receptor incluye la letra "Ñ"
	$buscar_letra = strpos($razon_social_emisor, 'Ñ');

	if ($buscar_letra >= 0 && $buscar_letra != '') {
		$array_nom_emisor = explode("Ñ", $razon_social_emisor);
		$emisor["nombre"] = implode(utf8_decode("Ñ"), $array_nom_emisor);
	}
	// Termina Validación por si el RFC del receptor incluye la letra "Ñ"
}
if ($emisor_calle != "") {
	$emisor["calle"] = trim(preg_replace("/ +/", " ", $emisor_calle));
}
if ($col_emisor != "") {
	$emisor["colonia"] = trim(preg_replace("/ +/", " ", $col_emisor));
}
if ($emisor_noext != "") {
	$emisor["noExterior"] = trim(preg_replace("/ +/", " ", $emisor_noext));
}
if ($emisor_noint != "") {
	$emisor["noInterior"] = trim(preg_replace("/ +/", " ", $emisor_noint));
}
if ($emisor_delompio != "") {
	$emisor["municipio"] = trim(preg_replace("/ +/", " ", $emisor_delompio));
}
if ($estado_emisor != "") {
	$emisor["estado"] = limpiar(trim(preg_replace("/ +/", " ", $estado_emisor)));
}
if ($pais != "") {
	$emisor["pais"] = trim(preg_replace("/ +/", " ", $pais));
}
if ($cp != "") {
	$emisor["codigoPostal"] = trim(preg_replace("/ +/", " ", $cp));
}

//DATOS DEL RECEPTOR
$receptor = array();
$receptor["rfc"] = $datareceptor_main["rfc"];
//COMPLEMENTARIOS RECEPTOR
$sqn = "SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
	FROM " . MAIN_DB_PREFIX . "cfdimx_facture_comercio_extranjero
	WHERE fk_facture=" . $facid;
$rqn = $db->query($sqn);
$numrn = $db->num_rows($rqn);
if ($numrn > 0) {
	$auxpais = $pais;
	$sqn = "SELECT b.code_departement, c.code_iso
		FROM " . MAIN_DB_PREFIX . "societe a, " . MAIN_DB_PREFIX . "c_departements b, " . MAIN_DB_PREFIX . "c_country c
		WHERE a.fk_departement=b.rowid AND a.fk_pays=c.rowid AND a.rowid=" . $datareceptor_main["rowid"];
	$rqn = $db->query($sqn);
	$rsn = $db->fetch_object($rqn);
	$pais = $rsn->code_iso;
	$datareceptor_main["estado"] = $rsn->code_departement;
	$receptor_delompio = $receptor_cod_municipio;
}
if ($datareceptor_main["razon_social"] != "") {
	$receptor["nombre"] = trim(preg_replace("/ +/", " ", $datareceptor_main["razon_social"]));
}
if ($receptor_calle != "") {
	$receptor["calle"] = trim(preg_replace("/ +/", " ", $receptor_calle));
}
if ($receptor_colonia != "") {
	$receptor["colonia"] = trim(preg_replace("/ +/", " ", $receptor_colonia));
}
if ($receptor_noext != "") {
	$receptor["noExterior"] = trim(preg_replace("/ +/", " ", $receptor_noext));
}
if ($receptor_noint != "") {
	$receptor["noInterior"] = trim(preg_replace("/ +/", " ", $receptor_noint));
}
if ($receptor_delompio != "") {
	$receptor["municipio"] = trim(preg_replace("/ +/", " ", $receptor_delompio));
}
if ($datareceptor_main["estado"] != "") {
	$receptor["estado"] = limpiar(trim(preg_replace("/ +/", " ", $datareceptor_main["estado"])));
}

$sqm2 = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facture_extrafields LIKE 'numregtrib'";
$resqlm2 = $db->query($sqm2);
$existe_2 = $db->num_rows($resqlm2);
$auxpais = null;
if ($existe_2 > 0) {
	$sqlm = "SELECT numregtrib FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object = " . $facid;
	$resqlm = $db->query($sqlm);
	$objm = $db->fetch_object($resqlm);
	if ($objm->numregtrib != "" && $objm->numregtrib != null && $objm->numregtrib != null) {
		$auxpais = $pais;
		$sqn = "SELECT b.code_departement, c.code_iso
			FROM " . MAIN_DB_PREFIX . "societe a, " . MAIN_DB_PREFIX . "c_departements b, " . MAIN_DB_PREFIX . "c_country c
			WHERE a.fk_departement=b.rowid AND a.fk_pays=c.rowid AND a.rowid=" . $datareceptor_main["rowid"];
		$rqn = $db->query($sqn);
		$rsn = $db->fetch_object($rqn);
		$pais = $rsn->code_iso;
		$receptor["numRegIdTrib"] = $objm->numregtrib;
	}
}

if ($pais != "") {
	$receptor["pais"] = trim(preg_replace("/ +/", " ", $pais));
}
if ($factura_usocfdi != "") {
	$receptor["usoCFDI"] = trim(preg_replace("/ +/", " ", $factura_usocfdi));
}
$pais = $auxpais;
if ($datareceptor_main["cp"] != "") {
	// Ensure codigoPostal is treated as a string to preserve leading zeros
	$receptor["codigoPostal"] = (string)trim(preg_replace("/ +/", " ", $datareceptor_main["cp"]));
}

//generamos query para obtener el regimen fiscal del receptor de la tabla llx_cfdimx_domicilio_fiscal_receptor
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_domicilio_fiscal_receptor WHERE fk_soc = " . $datareceptor_main["rowid"];
$resql = $db->query($sql);
$num = $db->num_rows($resql);
$regimenfiscal_receptor = "";
if ($num > 0) {
	$obj = $db->fetch_object($resql);
	$regimenfiscal_receptor = $obj->regimenfiscal;
	// $nombre_receptor = $obj->nombre;
	// Ensure cp is treated as a string to preserve leading zeros
	$cp_receptor = (string)$obj->cp;
}


//Inicia Nueva obtencion de Datos Fiscales Emisor
if (strcmp($version_cfdi_sat, "4.0") == 0) {
	$receptor = null;
	$objFacturaCFDI = new FacturaCFDI($db);
	$objFacturaCFDI->entidad = $conf->entity;
	$num_domicilio_fiscal = $objFacturaCFDI->getDomiciliosFiscalesCliente($datareceptor_main["rowid"]);

	if ($num_domicilio_fiscal > 0) {
		$count_lista_domicilios = count($objFacturaCFDI->lista_domicilios);
		for ($i = 0; $i < $count_lista_domicilios; $i++) {
			$receptor["rfc"] = trim($objFacturaCFDI->lista_domicilios[$i]->rfc);
			$receptor["usoCFDI"] = trim(preg_replace("/ +/", " ", $factura_usocfdi));
			$receptor["nombre"] = utf8_decode($objFacturaCFDI->lista_domicilios[$i]->nombre) ? utf8_decode($objFacturaCFDI->lista_domicilios[$i]->nombre) : $nombre_receptor;
			$receptor["codigoPostal"] = (string)($objFacturaCFDI->lista_domicilios[$i]->cp ? $objFacturaCFDI->lista_domicilios[$i]->cp : $cp_receptor);
			$receptor["regimenFiscal"] = $objFacturaCFDI->lista_domicilios[$i]->regimenfiscal ? $objFacturaCFDI->lista_domicilios[$i]->regimenfiscal : $regimenfiscal_receptor;

			if ($objFacturaCFDI->lista_domicilios[$i]->numregidtrib != "" && $objFacturaCFDI->lista_domicilios[$i]->numregidtrib != null) {
				$receptor["numRegIdTrib"] = $objFacturaCFDI->lista_domicilios[$i]->numregidtrib;
			}

			if ($objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal != "" && $objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal != null && $objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal != -1) {
				$receptor["residenciaFiscal"] = $objFacturaCFDI->lista_domicilios[$i]->residencia_fiscal;
			}

			//Inicia Ajustes para Cliente Mostrador XAXX010101000, XEXX010101000
			if (strcmp($receptor["rfc"], "XAXX010101000") == 0 || strcmp($receptor["rfc"], "XEXX010101000") == 0) {
				// Ensure codigoPostal is treated as a string to preserve leading zeros
				$receptor["codigoPostal"] = (string)$header["lugarExpedicion"];
				$receptor["regimenFiscal"] = 616;
				// $receptor["usoCFDI"] = "P01";
			}
			//Termina Ajustes para Cliente Mostrador XAXX010101000

			if ($validar_cce == 'SI') {
				$receptor["calle"] = trim(preg_replace("/ +/", " ", $objFacturaCFDI->lista_domicilios[$i]->calle));
				$receptor["colonia"] = trim(preg_replace("/ +/", " ", $objFacturaCFDI->lista_domicilios[$i]->clave_col));
				$receptor["noExterior"] = trim(preg_replace("/ +/", " ", $objFacturaCFDI->lista_domicilios[$i]->noint));
				$receptor["noInterior"] = trim(preg_replace("/ +/", " ", $objFacturaCFDI->lista_domicilios[$i]->noext));
				$receptor["municipio"] = trim(preg_replace("/ +/", " ", $objFacturaCFDI->lista_domicilios[$i]->clave_mpio));
				$receptor["estado"] = limpiar(trim(preg_replace("/ +/", " ", $objFacturaCFDI->lista_domicilios[$i]->estado)));
				$receptor["pais"] = $objFacturaCFDI->lista_domicilios[$i]->pais;
			}

			break;
		}
	}
}
$msg_cfdi_final = '';
$cfdi_code = 0;

$sql_fechas = "SELECT DATE_FORMAT(LAST_DAY(f.datef), '%Y-%m-%d') as fecha_fin_mes, ";
$sql_fechas .= " DATE_FORMAT(NOW(), '%Y-%m-%d') as fecha_hoy, ";
$sql_fechas .= " DATE_FORMAT(CONVERT_TZ(NOW(), 'UTC', 'America/Mexico_City'), '%H:%i:%s') as hora_actual";
$sql_fechas .= " FROM " . MAIN_DB_PREFIX . "facture f";
$sql_fechas .= " WHERE f.rowid = " . $facid;
$res_fechas = $db->query($sql_fechas);
$obj_fechas = $db->fetch_object($res_fechas);
$fecha_fin_mes = $obj_fechas->fecha_fin_mes;
$fecha_hoy = $obj_fechas->fecha_hoy;
$hora_actual = $obj_fechas->hora_actual;

// $data = array(
// 	"comprobante" => $header,
// 	"conceptos" => $conceptos,
// 	"emisor" => $emisor,
// 	"receptor" => $receptor,
// 	"timbrado_usuario" => $rfc_emisor,
// 	"timbrado_password" => $passwd_timbrado,
// 	"adicionales" => $adicionales
// );
// echo '<pre>';
// print_r($data);
// echo '</pre>';
// die();

$valida_rfc_emisor = validaRFC($rfc_emisor);
$valida_rfc_receptor = validaRFC($receptor["rfc"]);

// Inicia Validación por si el RFC del receptor incluye la letra "Ñ"
$buscar_letra = strpos($receptor["rfc"], 'Ñ');

if ($fecha_hoy > $fecha_fin_mes && $hora_actual >= '00:00:00') {
	$errores_factura[] = "La fecha de la factura no puede ser mayor a la fecha actual";
	$msg_cfdi_final = "La fecha de la factura no puede ser mayor a la fecha actual";
	$cfdi_code = -1;
}

if ($buscar_letra >= 0 && $buscar_letra != '') {
	$array_rfc_receptor = explode("Ñ", $receptor["rfc"]);

	$receptor["rfc"] = $array_rfc_receptor[0] . utf8_decode("Ñ") . $array_rfc_receptor[1];
	$valida_rfc_receptor = true;
}

// Termina Validación por si el RFC del receptor incluye la letra "Ñ"
if (
	$header != null && $conceptos != null && $emisor != null && $receptor != null && $rfc_emisor != null &&
	$passwd_timbrado != null && $adicionales != null && $errores_factura == null && $errores_conceptos == null
) {
	if ($valida_rfc_emisor && $valida_rfc_receptor) {
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx WHERE fk_facture=" . $facid . " AND entity_id = " . $conf->entity;
		$resql = $db->query($sql);
		$num_llx_cfdimx = $db->num_rows($resql);

		if ($num_llx_cfdimx < 1) {
			//Tabla para controlar timrbados de facturas y que usuarios las generan
			$sql_control_timbrado = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_control_timbrado";
			$sql_control_timbrado .= " WHERE factura_rowid = " . $facid;
			$sql_control_timbrado .= " AND tipo_timbrado = " . $modo_timbrado;
			$res_control_timbrado = $db->query($sql_control_timbrado);

			$registros_control_timbrado = $db->num_rows($res_control_timbrado);
			$inserta_control = 0;

			if ($registros_control_timbrado > 0) {
				$obj_control_timbrado = $db->fetch_object($res_control_timbrado);

				$inserta_control = 1;

				if ($obj_control_timbrado->estatus == 0) {
					$registros_control_timbrado = 0;
				}
			}
			unset($res_control_timbrado);

			//Validacion para que solo entre una vez a la petición del timbrado
			if ($registros_control_timbrado == 0) {
				//Se inserta el registro en la primera petición
				$sql_insert_control = "INSERT INTO " . MAIN_DB_PREFIX . "cfdimx_control_timbrado";
				$sql_insert_control .= "(";
				$sql_insert_control .= " factura_rowid,";
				$sql_insert_control .= " factura_serie,";
				$sql_insert_control .= " factura_folio,";
				$sql_insert_control .= " factura_fecha_timbrado,";
				$sql_insert_control .= " tipo_timbrado,";
				$sql_insert_control .= " usuario_rowid,";
				$sql_insert_control .= " estatus,";
				$sql_insert_control .= " entity_id";
				$sql_insert_control .= ")";
				$sql_insert_control .= "VALUES";
				$sql_insert_control .= "(";
				$sql_insert_control .= "'" . $facid . "',";
				$sql_insert_control .= "'" . $serie . "',";
				$sql_insert_control .= "'" . $folio . "',";
				$sql_insert_control .= "'" . $header["fecha"] . "',";
				$sql_insert_control .= "'" . $modo_timbrado . "',";
				$sql_insert_control .= "'" . $user->id . "',";
				$sql_insert_control .= "0,";
				$sql_insert_control .= "'" . $conf->entity . "'";
				$sql_insert_control .= ");";

				if ($inserta_control == 0) {
					$res_insert_control = $db->query($sql_insert_control);
				}
				unset($sql_insert_control);

				//Impresion de los arreglos que se mandan a timbrar
				if ($conf->global->CFDIMX_DEBUG_TIMBRADO == 1) {
					print '<pre>header<br>';
					print_r($header);
					print '</pre>';
					print '<pre>conceptos<br>';
					print_r($conceptos);
					print '</pre>';
					print '<pre>emisor<br>';
					print_r($emisor);
					print '</pre>';
					print '<pre>receptor<br>';
					print_r($receptor);
					print '</pre>';
					print '<pre>rfc_emisor<br>';
					print_r($rfc_emisor);
					print '</pre>';
					print '<pre>passwd_timbrado<br>';
					print_r($passwd_timbrado);
					print '</pre>';
					print '<pre>adicionales<br>';
					print_r($adicionales);
					print '</pre>';
				}

				//Peticion de timbrado al WS
				$wscfdi = $conf->global->MAIN_MODULE_CFDIMX_WS;
				$client = new nusoap_client($wscfdi, 'wsdl');
				$result = $client->call(
					"timbraCFDI",
					array(
						"comprobante" => $header,
						"conceptos" => $conceptos,
						"emisor" => $emisor,
						"receptor" => $receptor,
						"timbrado_usuario" => $rfc_emisor,
						"timbrado_password" => $passwd_timbrado,
						"adicionales" => $adicionales
					)
				);

				if ($conf->global->CFDIMX_DEBUG_TIMBRADO == 1) {
					print '<pre>result<br>';
					print_r($result);
					print '</pre>';
				}
				$pruebas = array();
				$pruebas['peticion'] = array(
					"comprobante" => $header,
					"conceptos" => $conceptos,
					"emisor" => $emisor,
					"receptor" => $receptor,
					"timbrado_usuario" => $rfc_emisor,
					"timbrado_password" => $passwd_timbrado,
					"adicionales" => $adicionales
				);
				$pruebas['result'] = $result;
				//Timbrado de Factura Correcto
				if ($result["return"]["rsp"] == 1 || $result["return"]["rsp"] == 307) {
					//Se actualiza el control con el estatus de factura timbrada
					$sql_update_control = "UPDATE " . MAIN_DB_PREFIX . "cfdimx_control_timbrado";
					$sql_update_control .= " SET";
					$sql_update_control .= " estatus = 1";
					$sql_update_control .= " WHERE factura_rowid = " . $facid;
					$sql_update_control .= " AND tipo_timbrado = " . $modo_timbrado;

					$res_update_control = $db->query($sql_update_control);
					unset($sql_update_control);

					//ADDENDA INICIO
					$sqlm = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facture_extrafields LIKE 'addendacfdi'";
					$resqlm = $db->query($sqlm);
					$existe_addendacfdi = $db->num_rows($resqlm);
					if ($existe_addendacfdi > 0) {
						$sqlm = "SELECT addendacfdi FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object = " . $facid;
						$resqlm = $db->query($sqlm);
						$objm = $db->fetch_object($resqlm);
						if (trim($objm->addendacfdi) != "" && $objm->addendacfdi != null && $objm->addendacfdi != null) {
							$xel = new SimpleXMLElement($result["return"]["xml"]);
							$xel->addChild($objm->addendacfdi);
							function remultimo($buscar, $remplazar, $texto)
							{
								$pos = strrpos($texto, $buscar);
								if ($pos !== false) {
									$texto = substr_replace($texto, $remplazar, $pos, strlen($buscar));
								}
								return $texto;
							}
							$result["return"]["xml"] = remultimo("/>", "", $xel->asXML());
						}
					}
					//ADDENDA FIN

					$separa_ftimbrado = explode("T", $result["return"]["fechaTimbrado"]);

					if ($serie == ''  ||  $folio == '') {
						$guion = "";
					} else {
						$guion = "-";
					}
					if (file_exists($conf->facture->dir_output . "/" . $serie . $guion . $folio)) {
					} else {
						mkdir($conf->facture->dir_output . "/" . $serie . $guion . $folio, 0700);
					}

					$file_xml = fopen($conf->facture->dir_output . "/" . $serie . $guion . $folio . "/" . $result["return"]["uuid"] . ".xml", "w");
					fwrite($file_xml, utf8_encode($result["return"]["xml"]));
					fclose($file_xml);
					$file_xml_str = $conf->facture->dir_output . "/" . $serie . $guion . $folio . "/" . $result["return"]["uuid"] . ".xml";
					try {
						$the_xml = file_get_contents($file_xml_str);
						$sxe = new SimpleXMLElement($the_xml);
						$ns = $sxe->getNamespaces(true);
						$sxe->registerXPathNamespace('t', $ns['cfdi']);
						foreach ($sxe->xpath('//t:Comprobante') as $tfd) {
							$noCertificado = "{$tfd['NoCertificado']}";
						}
					} catch (Exception $e) {
						echo $e->getMessage() . "<br>";
					}

					if ($version_cfdi_sat == '' || is_null($version_cfdi_sat)) {
						$result["return"]["version"] = '3.3';
					} else {
						if (strcmp($version_cfdi_sat, "4.0") == 0) {
							$result["return"]["version"] = '4.0';
						} else {
							$result["return"]["version"] = '3.3';
						}
					}

					if ($cuenta == "") {
						$cuenta = 0;
					}
					if (!is_numeric($cuenta)) {
						$cuenta = 0;
					}

					//Se crea el SQL de registro del CFDI
					if (strcmp($version_cfdi_sat, "4.0") == 0) {
						$insert = "
							INSERT INTO " . MAIN_DB_PREFIX . "cfdimx (
								factura_serie,
								factura_folio,
								factura_seriefolio,
								xml,
								cadena,
								version,
								selloCFD,
								fechaTimbrado,
								uuid,
								certificado,
								sello,
								certEmisor,
								cancelado,
								u4dig,
								fk_facture,
								fecha_emision,
								hora_emision,
								fecha_timbrado,
								hora_timbrado,
								tipo_timbrado,
								divisa,
								entity_id,
								rfc,
								usocfdi,
								nombre,
								codigopostal,
								regimenfiscal
							) VALUES (
								'" . $serie . "',
								'" . $folio . "',
								'" . $serie . "-" . $folio . "',
								'" . $db->escape(utf8_decode($result["return"]["xml"])) . "',
								'" . $db->escape(utf8_decode($result["return"]["cadenaOrig"])) . "',
								'" . utf8_decode($result["return"]["version"]) . "',
								'" . utf8_decode($result["return"]["selloCFD"]) . "',
								'" . $result["return"]["fechaTimbrado"] . "',
								'" . $result["return"]["uuid"] . "',
								'" . utf8_decode($result["return"]["certSAT"]) . "',
								'" . utf8_decode($result["return"]["selloSAT"]) . "',
								'" . $noCertificado . "',
								'0',
								'" . $cuenta . "',
								'" . $facid . "',
								'" . $fecha_emison . "',
								'" . $hora_emision . "',
								'" . $separa_ftimbrado[0] . "',
								'" . $separa_ftimbrado[1] . "',
								'" . $modotimb . "',
								'" . $moneda . "',
								'" . $conf->entity . "',
								'" . $receptor["rfc"] . "',
								'" . $receptor["usoCFDI"] . "',
								'" . $db->escape(utf8_decode($receptor["nombre"])) . "',
								'" . (string)$receptor["codigoPostal"] . "',
								'" . $receptor["regimenFiscal"] . "'
							)
						";
					} else {
						$insert = "
							INSERT INTO " . MAIN_DB_PREFIX . "cfdimx (
								factura_serie,
								factura_folio,
								factura_seriefolio,
								xml,
								cadena,
								version,
								selloCFD,
								fechaTimbrado,
								uuid,
								certificado,
								sello,
								certEmisor,
								cancelado,
								u4dig,
								fk_facture,
								fecha_emision,
								hora_emision,
								fecha_timbrado,
								hora_timbrado,
								tipo_timbrado,
								divisa,
								entity_id
							) VALUES (
								'" . $serie . "',
								'" . $folio . "',
								'" . $serie . "-" . $folio . "',
								'" . $db->escape(utf8_decode($result["return"]["xml"])) . "',
								'" . $db->escape(utf8_decode($result["return"]["cadenaOrig"])) . "',
								'" . utf8_decode($result["return"]["version"]) . "',
								'" . utf8_decode($result["return"]["selloCFD"]) . "',
								'" . $result["return"]["fechaTimbrado"] . "',
								'" . $result["return"]["uuid"] . "',
								'" . utf8_decode($result["return"]["certSAT"]) . "',
								'" . utf8_decode($result["return"]["selloSAT"]) . "',
								'" . $noCertificado . "',
								'0',
								'" . $cuenta . "',
								'" . $facid . "',
								'" . $fecha_emison . "',
								'" . $hora_emision . "',
								'" . $separa_ftimbrado[0] . "',
								'" . $separa_ftimbrado[1] . "',
								'" . $modotimb . "',
								'" . $moneda . "',
								'" . $conf->entity . "'
							)
						";
					}
					dol_syslog('SQL_Timbrado:' . $insert);
					echo $insert;
					$rr = $db->query($insert);
					if (!$rr) {
						if (strtoupper($serie) == ''  ||  $folio == '') {
							$guion = "";
						} else {
							$guion = "-";
						}

						if (file_exists($conf->facture->dir_output . "/" . $serie . $guion . $folio)) {
						} else {
							mkdir($conf->facture->dir_output . "/" . $serie . $guion . $folio, 0700);
						}

						$file_xml = fopen($conf->facture->dir_output . "/" . $serie . $guion . $folio . "/" . $serie . $guion . $folio . "_soporte.txt", "w");
						fwrite($file_xml, utf8_encode($insert));
						fclose($file_xml);
					}

					// Actualizar tabla de timbrado de factura
					$sqltimb = "INSERT INTO llx_cfdimx_cancelado (factura_id, factura_seriefolio, fk_facture, cancelado)
						SELECT factura_id, factura_seriefolio, fk_facture, cancelado FROM llx_cfdimx
						ON DUPLICATE KEY UPDATE factura_id = VALUES(factura_id), factura_seriefolio = VALUES(factura_seriefolio), fk_facture = VALUES(fk_facture), cancelado = VALUES(cancelado); ";
					$resqltimb = $db->query($sqltimb);

					// Mantener signo negativo en interfaz para notas de crédito
					if ($factura_tipo == 2) {
						$newTotal = str_replace(",", "", number_format($factura_total, $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL));
					} else {
						$newTotal = $header["total"];
					}
					// Actualizar tablas locales
					if ($factura_total_origen != $header["total"]) {
						if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
							if ($header["moneda"] == $conf->currency) {
								$sqlupd = "UPDATE " . MAIN_DB_PREFIX . "facture SET multicurrency_total_ttc=" . $newTotal . ",total_ttc=" . $newTotal . " WHERE rowid=" . $facid;
							} else {
								$sqlupd = "UPDATE " . MAIN_DB_PREFIX . "facture SET multicurrency_total_ttc=" . $newTotal . " WHERE rowid=" . $facid;
							}
							$ass = $db->query($sqlupd);
						} else {
							$sqlupd = "UPDATE " . MAIN_DB_PREFIX . "facture SET total_ttc=" . $newTotal . " WHERE rowid=" . $facid;
							$ass = $db->query($sqlupd);
						}
					}

					if ($factura_tipo == 2) {
						$vowels = array(",", "-");
						$factura_subtotal = str_replace($vowels, "", $factura_subtotal);
						if ($descheader != 0) {
							$descheader = str_replace($vowels, "", $descheader);
						}
						$factura_iva = str_replace($vowels, "", $factura_iva);
						if ($impuestoish != 'NO') {
							$impuestoish = str_replace($vowels, "", $impuestoish);
						}
						if ($impuestoish2 != 'NO') {
							$impuestoish2 = str_replace($vowels, "", $impuestoish2);
						}
						$factura_total = str_replace($vowels, "", $factura_total);
					}

					$cfdi_commit = $result["return"]["rsp"];

					if ($rr) {
						dol_syslog('ANTES:: generaPDF_new.php');
						print '<script>location.href="generaPDF_new.php?facid=' . $facid . '&route=genera&cfdi_commit=' . $cfdi_commit . '";</script>';
					} else {
						dol_syslog('ANTES:: Retorno CFDI');
						print '<script>location.href="facture.php?facid=' . $_REQUEST["facid"] . '&cfdi_commit=' . $cfdi_commit . '";</script>';
					}
				} else {
					if (strtoupper($serie) == ''  ||  $folio == '') {
						$guion = "";
					} else {
						$guion = "-";
					}

					if (file_exists($conf->facture->dir_output . "/" . $serie . $guion . $folio)) {
					} else {
						mkdir($conf->facture->dir_output . "/" . $serie . $guion . $folio, 0700);
					}

					$file_xml = fopen($conf->facture->dir_output . "/" . $serie . $guion . $folio . "/" . $serie . $guion . $folio . ".xml", "w");
					fwrite($file_xml, utf8_encode($result["return"]["xml_estructura"]));
					fclose($file_xml);

					if ($result["return"]["rsp"] != "") {
						$msg_cfdi_final .= utf8_encode($result["return"]["rsp"] . " - " . $result["return"]["msg"] . "<br><br>" . $result["return"]["msgDetail"]);
						$cfdi_code = -1;
					} else {
						$msg_cfdi_final .= "No hubo respuesta para la peticion, intente nuevamente.";
						$cfdi_code = -1;
					}
				}
			} else {
				$msg_cfdi_final .= "Error 9001: La factura " . $serie . "-" . $folio . " ya esta asociada con un timbre fiscal.";
				$cfdi_code = -1;
			}
		} else {
			$msg_cfdi_final .= "Error 9002: La factura " . $serie . "-" . $folio . " ya esta asociada con un timbre fiscal.";
			$cfdi_code = -1;
		}
	} else {
		if (!$valida_rfc_emisor){
			$msg_cfdi_final = "10000 - El RFC del emisor es incorrecto<br>";
			$cfdi_code = -1;
		}
		if (!$valida_rfc_receptor){
			$msg_cfdi_final = "10001 - El RFC del receptor es incorrecto";
			$cfdi_code = -1;
		}
	}
} else {
	if ($header == null) {
		$msg_cfdi_final .= "La información del Comprobante esta vacía y es obligatoria.<br>";
		$cfdi_code = -1;
	}

	if ($conceptos == null) {
		$msg_cfdi_final .= "La información de los Conceptos esta vacía y es obligatoria.<br>";
		$cfdi_code = -1;
	}

	if ($emisor == null) {
		$msg_cfdi_final .= "La información del Emisor esta vacía y es obligatoria.<br>";
		$cfdi_code = -1;
	}

	if ($receptor == null) {
		$msg_cfdi_final .= "La información del Receptor (Cliente) esta vacía y es obligatoria.<br>";
		$cfdi_code = -1;

		if (strcmp($version_cfdi_sat, "4.0") == 0) {
			$msg_cfdi_final .= " Para el Timbrado de CFDI 4.0 la información del Receptor (Cliente) se toma de la Pestaña Domicilio Fiscal.";
			$cfdi_code = -1;
		}
	}

	if ($rfc_emisor == null) {
		$msg_cfdi_final .= "El usuario de Timbrado esta vacío y es obligatorio.<br>";
		$cfdi_code = -1;
	}

	if ($passwd_timbrado == null) {
		$msg_cfdi_final .= "La Contraseña de Timbrado esta vacía y es obligatoria.<br>";
		$cfdi_code = -1;
	}

	if ($adicionales == null) {
		$msg_cfdi_final .= "La información de Adicionales esta vacía y es obligatoria.<br>";
		$cfdi_code = -1;
	}

	if ($errores_factura == null) {
		$msg_cfdi_final .= " ";
	}

	if ($errores_conceptos == null) {
		$msg_cfdi_final .= " ";
	}
}
