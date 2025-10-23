<?php
//error_reporting(-1);
header('Content-Type: text/html; charset=utf-8');
$facid      = isset($_REQUEST["facid"]) ? $_REQUEST["facid"] : null;
$bandx      = isset($_REQUEST["band"]) ? $_REQUEST["band"] : null;
$id         = isset($_REQUEST["idx"]) ? $_REQUEST["idx"] : null;
$previewpdf = isset($_REQUEST["previewpdf"]) ? $_REQUEST["previewpdf"] : null;
$banpreviw  = 0;
if ($previewpdf == "previewpdf") {
    $banpreviw = 1;
}
require('../main.inc.php');
require('conf.php');
include("lib/phpqrcode/qrlib.php");
require('lib/numero_a_letra.php');
include('lib/nusoap/lib/nusoap.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
include_once(DOL_DOCUMENT_ROOT . "/core/class/translate.class.php");
include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");

$interface = new Interfaces($db);
$langs->load('bills');
$langs->load('companies');
$langs->load('products');
$langs->load('main');

if ($bandx == 0) {
    $tpdomic = str_replace('-', ' ', $_REQUEST['tpdomicilio']);
    print ' <html>
	<head> <title>Regenera PDF</title>
	<head>
	</head>
		<body>
			<center>
			<br/><br/>
			<form method="GET" action="">
				<table>
					<tr><td collspan="2">El PDF ya se generó con anterioridad. Desea reemplazarlo?</td></tr>
					<tr>
						<td>
							<input type="hidden" id="band" name="band" value="1">
							<input type="hidden" id="facid" name="facid" value="' . $facid . '">
				            <input type="hidden" id="previewpdf" name="previewpdf" value="' . $previewpdf . '">
				            <input type="hidden" id="tpdomicilio" name="tpdomicilio" value="' . $tpdomic . '">					
							<input type="submit" id="acep" name="acep" value="Aceptar">
						</td>
					<td>
					<input type="button" onClick="window.close();" id="cancela" name="cancela" value="cancelar"> </td>
					</tr>
			   </table>
		   </form>
		   </center>
	   </body>
</html>';    
}
elseif ($bandx == 1) {
    
    function validaRFC($valor)
    {
        
        $valor       = str_replace("-", "", $valor);
        @$cuartoValor = substr($valor, 3, 1);
        //RFC Persona Moral.
        if (ctype_digit($cuartoValor) && strlen($valor) == 12) {
            $letras    = substr($valor, 0, 3);
            $numeros   = substr($valor, 3, 6);
            $homoclave = substr($valor, 9, 3);
            if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
                return true;
            }
            //RFC Persona FÃ­sica.
        } else if (ctype_alpha($cuartoValor) && strlen($valor) == 13) {
            $letras    = substr($valor, 0, 4);
            $numeros   = substr($valor, 4, 6);
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
        $String = str_replace(array(
            'á',
            'à',
            'â',
            'ã',
            'ª',
            'ä'
        ), "a", $String);
        $String = str_replace(array(
            'Á',
            'À',
            'Â',
            'Ã',
            'Ä'
        ), "A", $String);
        $String = str_replace(array(
            'Í',
            'Ì',
            'Î',
            'Ï'
        ), "I", $String);
        $String = str_replace(array(
            'í',
            'ì',
            'î',
            'ï'
        ), "i", $String);
        $String = str_replace(array(
            'é',
            'è',
            'ê',
            'ë'
        ), "e", $String);
        $String = str_replace(array(
            'É',
            'È',
            'Ê',
            'Ë'
        ), "E", $String);
        $String = str_replace(array(
            'ó',
            'ò',
            'ô',
            'õ',
            'ö',
            'º'
        ), "o", $String);
        $String = str_replace(array(
            'Ó',
            'Ò',
            'Ô',
            'Õ',
            'Ö'
        ), "O", $String);
        $String = str_replace(array(
            'ú',
            'ù',
            'û',
            'ü'
        ), "u", $String);
        $String = str_replace(array(
            'Ú',
            'Ù',
            'Û',
            'Ü'
        ), "U", $String);
        $String = str_replace(array(
            '[',
            '^',
            '´',
            '`',
            '¨',
            '~',
            ']'
        ), "", $String);
        $String = str_replace("ç", "c", $String);
        $String = str_replace("Ç", "C", $String);
        $String = str_replace("ñ", "n", $String);
        $String = str_replace("Ñ", "N", $String);
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
        return $String;
    }
    function getDataCliente($db, $id)
    {
        $sql                  = "SELECT * FROM " . MAIN_DB_PREFIX . "societe	WHERE rowid = " . $id;
        $resql                = $db->query($sql);
        $obj                  = $db->fetch_object($resql);
        $data["rfc"]          = $obj->siren;
        $data["rowid"]        = $obj->rowid;
        $data["razon_social"] = utf8_decode($obj->nom);
        $data["colonia"]      = utf8_decode($obj->town); //Covertir a del o mpio
        $data["estado"]       = utf8_decode(getState($obj->fk_departement));
        $data["cp"]           = $obj->zip;
        $data["email"]        = $obj->email;
        return $data;
    }
    
    function getUnidadMedida($db, $id)
    {
        
        $umed = "No identificado";
        if ($id != "") {
            $sql          = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "product_extrafields'";
            $resql        = $db->query($sql);
            $existe_tabla = $db->num_rows($resql);
            if ($existe_tabla > 0) {
                $sql         = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "product_extrafields LIKE 'umed'";
                $resql       = $db->query($sql);
                $existe_umed = $db->num_rows($resql);
                if ($existe_umed > 0) {
                    $sql   = "SELECT * FROM " . MAIN_DB_PREFIX . "product_extrafields WHERE fk_object = " . $id;
                    $resql = $db->query($sql);
                    $obj   = $db->fetch_object($resql);
                    if ($obj->umed != "") {
                        $umed = utf8_decode($obj->umed);
                    } else {
                        $umed = "NA";
                    }
                } else {
                    $umed = "NA";
                }
            } else {
                $umed = "NA";
            }
        } else {
            $umed = "NA";
        }
        
        return $umed;
    }
    function getclaveprodserv($db, $id)
    {
        
        $umed = "No identificado";
        if ($id != "") {
            $sql          = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "product_extrafields'";
            $resql        = $db->query($sql);
            $existe_tabla = $db->num_rows($resql);
            if ($existe_tabla > 0) {
                $sql         = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "product_extrafields LIKE 'claveprodserv'";
                $resql       = $db->query($sql);
                $existe_umed = $db->num_rows($resql);
                if ($existe_umed > 0) {
                    $sql   = "SELECT * FROM " . MAIN_DB_PREFIX . "product_extrafields WHERE fk_object = " . $id;
                    $resql = $db->query($sql);
                    $obj   = $db->fetch_object($resql);
                    if ($obj->claveprodserv != "") {
                        $claveprodserv = utf8_decode($obj->claveprodserv);
                    } else {
                        $claveprodserv = "01010101";
                    }
                } else {
                    $claveprodserv = "01010101";
                }
            } else {
                $claveprodserv = "01010101";
            }
        } else {
            $claveprodserv = "01010101";
        }
        
        return $claveprodserv;
    }
    function getU4DigCta($id, $db)
    {
        $sql        = "SELECT * FROM " . MAIN_DB_PREFIX . "societe_rib WHERE default_rib=1 AND fk_soc = " . $id;
        $resql      = $db->query($sql);
        $nmc        = $db->fetch_object($resql);
        $total_char = strlen($nmc->number);
        if ($total_char >= 4) {
            //$cuenta = substr($nmc->number,0,-4);
            $cuenta = $nmc->number;
        } else {
            $cuenta = "";
        }
        return $cuenta;
    }
    
    function getProducto($id, $db)
    {
        $sql                     = "SELECT * FROM " . MAIN_DB_PREFIX . "product WHERE rowid = " . $id;
        $resql                   = $db->query($sql);
        $obj                     = $db->fetch_object($resql);
        $producto['ref']         = utf8_decode($obj->ref);
        $producto['label']       = utf8_decode($obj->label);
        $producto['description'] = utf8_decode($obj->description);
        
        return $producto;
    }
    
    function getFormasPago($id, $db)
    {
        $sql               = "SELECT * FROM " . MAIN_DB_PREFIX . "c_paiement WHERE id = " . $id;
        $resql             = $db->query($sql);
        $obj               = $db->fetch_object($resql);
        $data["code"]      = $obj->code;
        $data["tipo_pago"] = html_entity_decode($obj->libelle);
        return $data;
    }
    
    function getCondicionesPago($id, $db)
    {
        $sql                    = "SELECT * FROM " . MAIN_DB_PREFIX . "c_payment_term WHERE rowid = " . $id;
        $resql                  = $db->query($sql);
        $obj                    = $db->fetch_object($resql);
        $data["code"]           = $obj->code;
        $data["dias_credito"]   = $obj->nbjour;
        $data["condicion_pago"] = html_entity_decode($obj->libelle);
        return $data;
    }
    
    function getOC($id, $db)
    {
        $sql = "
		SELECT c.ref FROM  " . MAIN_DB_PREFIX . "element_element e,  " . MAIN_DB_PREFIX . "commande c
		WHERE e.sourcetype = 'commande'
		AND e.fk_target = " . $id . "
		AND e.fk_source = c.rowid";
        dol_syslog("GETOC::" . $sql);
        $resql = $db->query($sql);
        $obj   = $db->fetch_object($resql);
        $aux   = $obj->ref;
        dol_syslog('ORDEN:REG:' . $aux);
        return $aux;
    }
    
    $cfdi_decimal = $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL ? $conf->global->MAIN_INFO_CFDI_NUM_CFDI_DECIMAL : 2;
    //Datos de la factura
    $sql          = " SELECT * FROM " . MAIN_DB_PREFIX . "facture WHERE rowid = " . $facid;
    $resql        = $db->query($sql);
    if ($resql) {
        $num_fact = $db->num_rows($resql);
        $i        = 0;
        if ($num_fact) {
            while ($i < $num_fact) {
                $obj = $db->fetch_object($resql);
                if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
                    $obj->total     = $obj->multicurrency_total_ht;
                    $obj->tva       = $obj->multicurrency_total_tva;
                    $obj->total_ttc = $obj->multicurrency_total_ttc;
                    //$object->multicurrency_total_ht;
                    //$object->multicurrency_total_ttc;
                    //$object->multicurrency_total_tva;
                }
                $facref = $obj->ref;
                $separafac = explode("-", $facref);
                $serie     = $separafac[0];
                $folio     = $separafac[1];
                if ($separafac[1] == '' || $separafac[1] == NULL || $separafac[1] == null) {
                    $serie = "";
                    $folio = $separafac[0];
                }
                $observaciones            = $obj->note_public;
                $factura_tipo             = $obj->type;
                $cliente_id               = $obj->fk_soc;
                $factura_id               = $obj->rowid;
                $factura_iva              = str_replace(",", "", number_format($obj->tva, 2));
                $factura_subtotal         = str_replace(",", "", number_format($obj->total, 2));
                $factura_total            = str_replace(",", "", number_format($obj->total_ttc, 2));
                $factura_fecha            = $obj->datef;
                $factura_fechac_unix      = strtotime($obj->datec);
                $factura_hora             = date("H:i:s", $factura_fechac_unix);
                $fecha_factura            = $factura_fecha . "T" . $factura_hora;
                $factura_formapago_id     = $obj->fk_mode_reglement;
                $factura_condicionpago_id = $obj->fk_cond_reglement;
                $formaPago                = getFormasPago($factura_formapago_id, $db);
                $condPago                 = getCondicionesPago($factura_condicionpago_id, $db);
                
                $sqlm        = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facture_extrafields LIKE 'formpagcfdi'";
                $resqlv      = $db->query($sqlm);
                $existe_form = $db->num_rows($resqlv);
                if ($existe_form > 0) {
                    $sqlv               = "SELECT formpagcfdi FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object=" . $facid;
                    $rv                 = $db->query($sqlv);
                    $vrs                = $db->fetch_object($rv);
                    $factura_metodopago = $vrs->formpagcfdi;
                } else {
                    $factura_metodopago = "No identificado";
                }
                $sqlm2          = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facture_extrafields LIKE 'usocfdi'";
                $resqlv2        = $db->query($sqlm2);
                $existe_usocfdi = $db->num_rows($resqlv2);
                if ($existe_usocfdi > 0) {
                    $sqlv            = "SELECT usocfdi FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object=" . $facid;
                    $rv              = $db->query($sqlv);
                    $vrs             = $db->fetch_object($rv); //$factura_formapago cambio por $factura_metodopago
                    $factura_usocfdi = $vrs->usocfdi;
                } else {
                    $factura_usocfdi = "G03";
                }
                //$factura_formapago = ($langs->trans("PaymentTypeShort".$formaPago["code"])!=("PaymentTypeShort".$formaPago["code"])?$langs->trans("PaymentTypeShort".$formaPago["code"]):($formaPago["tipo_pago"]));
                $sqlb = "SELECT accountancy_code as acon FROM " . MAIN_DB_PREFIX . "c_paiement WHERE code = '" . $formaPago["code"] . "'";
                $vs   = $db->query($sqlb);
                $brs  = $db->num_rows($vs);
                if ($brs > 0) {
                    $bns = $db->fetch_object($brs);
                    if ($bns->acon != NULL && $bns->acon != '') {
                        $codmet = $bns->acon;
                    } else {
                        $codmet = 99;
                    }
                } else {
                    $codmet = 99;
                }
                $smq  = "SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_facture_mode_paiement," . MAIN_DB_PREFIX . "c_paiement
            				WHERE fk_facture=" . $facid . " AND fk_c_paiement=id";
                $mqs  = $db->query($smq);
                $mnrw = $db->num_rows($mqs);
                if ($mnrw > 0) {
                    while ($mrs = $db->fetch_object($mqs)) {
                        if ($mrs->accountancy_code == '' || $mrs->accountancy_code == null) {
                            $codpa = 99;
                        } else {
                            $codpa = $mrs->accountancy_code;
                        }
                        $codmet = $codmet . "," . $codpa;
                    }
                }
                //$factura_metodopago=$formaPago["code"];
                $factura_formapago     = $codmet;
                //$factura_metodopago2 = ($langs->trans("PaymentTypeShort".$formaPago["code"])!=("PaymentTypeShort".$formaPago["code"])?$langs->trans("PaymentTypeShort".$formaPago["code"]):($formaPago["tipo_pago"]));
                $factura_condicionpago = html_entity_decode(($langs->trans("PaymentConditionShort" . $condPago["code"]) != ("PaymentConditionShort" . $condPago["code"]) ? $langs->trans("PaymentConditionShort" . $condPago["code"]) : ($condPago["condicion_pago"])));
                $i++;
            }
        }
    }
    
    $cuenta            = getU4DigCta($cliente_id, $db);
    $datareceptor_main = getDataCliente($db, $cliente_id);
    $datareceptor_comp = get_data_receptor($db, $cliente_id);
    $sqld              = "SELECT *
		FROM " . MAIN_DB_PREFIX . "cfdimx_descuentos
		WHERE entity_id=" . $conf->entity;
    $rqsd              = $db->query($sqld);
    $nrd               = $db->num_rows($rqsd);
    $descumostrar      = 1;
    if ($nrd > 0) {
        $rsd = $db->fetch_object($rqsd);
        if ($rsd->mostrar == 1) {
            $descumostrar = 1;
        } else {
            $descumostrar = 2;
        }
    }
    //Conceptos
    $sql = " SELECT *, truncate(`tva_tx`,$cfdi_decimal) as tva_tx, truncate(`total_tva`,2) as total_tva";
    $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet  WHERE fk_facture =" . $facid;
    if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
        $sql = " SELECT *, truncate(`tva_tx`,$cfdi_decimal) as tva_tx, truncate(`multicurrency_total_tva`,2) as total_tva";
        $sql .= " ,multicurrency_total_ht as total_ht, multicurrency_total_ttc as total_ttc, multicurrency_subprice as subprice";
        $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet  WHERE fk_facture =" . $facid;
    }
    $descheader = 0;
    $resql      = $db->query($sql);
    if ($resql) {
        $num_detalle_fact = $db->num_rows($resql);
        $i                = 0;
        
        #Lotes
        $lote = "NO";
        if ($conf->global->MAIN_MODULE_PRODUCTBATCH) {
            $sql1 = "SELECT ifnull(fk_source,null) as fk_source
				FROM " . MAIN_DB_PREFIX . "element_element
				WHERE fk_target=" . $facid . " AND targettype='facture' AND sourcetype='commande'";
            //dol_syslog("MI QUERY 1::".$sql1);
            $rlo  = $db->query($sql1);
            $rlot = $db->fetch_object($rlo);
            //dol_syslog("MI QUERY 1RES::".$rlot->fk_source);
            if ($rlot->fk_source != NULL && $rlot->fk_source != null && $rlot->fk_source > 0) {
                $sql2  = "SELECT ifnull(fk_target,null) as fk_target
					FROM " . MAIN_DB_PREFIX . "element_element
					WHERE fk_source=" . $rlot->fk_source . " AND sourcetype='commande' AND targettype='shipping'";
                //dol_syslog("MI QUERY 2::".$sql2);
                $rlo2  = $db->query($sql2);
                $rlot2 = $db->fetch_object($rlo2);
                //dol_syslog("MI QUERY 2RES::".$rlot2->fk_target);
                if ($rlot2->fk_target != NULL && $rlot2->fk_target != null && $rlot2->fk_target > 0) {
                    $lote = $rlot2->fk_target;
                } else {
                    $lote = "NO";
                }
            } else {
                $lote = "NO";
            }
        }
        #Lotes#
        
        if ($num_detalle_fact) {
            while ($i < $num_detalle_fact) {
                $obj             = $db->fetch_object($resql);
                $desc            = '';
                $ref_add         = 1;
                $label_add       = 1;
                $description_add = 1;
                
                unset($producto);
                if ($obj->fk_product != "") {
                    $producto = getProducto($obj->fk_product, $db);
                }
                
                if ($ref_add == 1 && $producto['ref'] != "") {
                    //$desc .= utf8_decode($producto['ref']);
                }
                if ($label_add == 1 && $producto['label'] != "") {
                    if ($desc != "") {
                        //$desc .= ' - '; //Descomentar
                    }
                    $desc .= utf8_encode($producto['label']);
                }
                
                if ($description_add == 1 && $obj->description != "") {
                    if ($desc != "") {
                        $desc .= ' - ';
                    }
                    $desc .= $obj->description;
                }
                if ($lote != "NO" && $obj->fk_product != "") {
                    $sql3 = "SELECT ifnull(fk_product,null) as fk_product, batch, eatby,(value * -1) as qty
						FROM " . MAIN_DB_PREFIX . "stock_mouvement
						WHERE fk_product=" . $obj->fk_product . " AND fk_origin=" . $lote . " AND origintype='shipping'";
                    dol_syslog("MI QUERY 3::" . $sql3);
                    $rlo3 = $db->query($sql3);
                    while ($rlot3 = $db->fetch_object($rlo3)) {
                        if ($rlot3->fk_product != NULL && (trim($rlot3->batch) != "" && $rlot3->batch != NULL)) {
                            $desc .= " - Cantidad: " . $rlot3->qty . " Lote: " . $rlot3->batch . " Cad: " . $rlot3->eatby;
                        }
                    }
                }
                
                if ($obj->fk_product) {
                    $unidad = getUnidadMedida($db, $obj->fk_product);
                } else {
                    $sqlm         = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "facturedet_extrafields'";
                    $resqlm       = $db->query($sqlm);
                    $existe_tabla = $db->num_rows($resqlm);
                    if ($existe_tabla > 0) {
                        $sqlm        = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facturedet_extrafields LIKE 'umed'";
                        $resqlm      = $db->query($sqlm);
                        $existe_umed = $db->num_rows($resqlm);
                        if ($existe_umed > 0) {
                            $sqlm   = "SELECT * FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE fk_object = " . $obj->rowid;
                            $resqlm = $db->query($sqlm);
                            $objm   = $db->fetch_object($resqlm);
                            if ($objm->umed != "") {
                                $unidad = utf8_decode($objm->umed);
                            } else {
                                $unidad = "NA";
                            }
                        } else {
                            $unidad = "NA";
                        }
                    } else {
                        $unidad = "NA";
                    }
                }
                if ($obj->fk_product) {
                    $claveprodserv = getclaveprodserv($db, $obj->fk_product);
                } else {
                    $sqlm         = "SHOW TABLES LIKE '" . MAIN_DB_PREFIX . "facturedet_extrafields'";
                    $resqlm       = $db->query($sqlm);
                    $existe_tabla = $db->num_rows($resqlm);
                    if ($existe_tabla > 0) {
                        $sqlm                 = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facturedet_extrafields LIKE 'claveprodserv'";
                        $resqlm               = $db->query($sqlm);
                        $existe_claveprodserv = $db->num_rows($resqlm);
                        if ($existe_claveprodserv > 0) {
                            $sqlm   = "SELECT claveprodserv FROM " . MAIN_DB_PREFIX . "facturedet_extrafields WHERE fk_object = " . $obj->rowid;
                            $resqlm = $db->query($sqlm);
                            $objm   = $db->fetch_object($resqlm);
                            if ($objm->claveprodserv != "") {
                                $claveprodserv = utf8_decode($objm->claveprodserv);
                            } else {
                                $claveprodserv = "01010101";
                            }
                        } else {
                            $claveprodserv = "01010101";
                        }
                    } else {
                        $claveprodserv = "01010101";
                    }
                }
                if ($obj->remise_percent != 0 && $descumostrar == 1) {
                    $descuento  = $obj->remise_percent / 100;
                    $descuento2 = ($obj->subprice * $obj->qty) * $descuento;
                    $descheader = $descheader + $descuento2;
                    //$auxvuni=$obj->subprice-$descuento2;
                    $total_vuni = number_format($obj->subprice, $cfdi_decimal);
                    $total_ttc  = number_format($obj->subprice * $obj->qty, $cfdi_decimal);
                } else {
                    if ($obj->remise_percent != 0 && $descumostrar == 2) {
                        $descuento  = $obj->total_ht / $obj->qty;
                        //$descuento2=($obj->subprice*$obj->qty)*$descuento;
                        //$auxvuni=$obj->subprice-$descuento2;
                        $total_vuni = number_format($descuento, $cfdi_decimal);
                        $total_ttc  = number_format($obj->total_ht, $cfdi_decimal);
                    } else {
                        $total_vuni = number_format($obj->subprice, $cfdi_decimal);
                        $total_ttc  = number_format($obj->total_ht, $cfdi_decimal);
                    }
                }
                if ($factura_tipo == 2) {
                    $vowels = array(
                        ",",
                        "-"
                    );
                } else {
                    $vowels = array(
                        ","
                    );
                }

                $conceptos[$i]  = array(
                    'descripcion' => $desc,
                    'cantidad' => $obj->qty,
                    'valorUnitario' => str_replace($vowels, "", $total_vuni),
                    'importe' => str_replace($vowels, "", $total_ttc),
                    'importeImpuesto' => str_replace($vowels, "", round(($obj->total_tva), 6)),
                    'impuesto' => 'IVA',
                    'tasa' => $obj->tva_tx,
                    'unidad' => $unidad
                );
                $conceptos2[$i] = array(
                    'descripcion' => $desc,
                    'cantidad' => $obj->qty,
                    'valorUnitario' => str_replace($vowels, "", $total_vuni),
                    'importe' => str_replace($vowels, "", $total_ttc),
                    'importeImpuesto' => str_replace($vowels, "", round(($obj->total_tva), 6)),
                    'impuesto' => 'IVA',
                    'tasa' => $obj->tva_tx,
                    'unidad' => $unidad,
                    'claveProdServ' => $claveprodserv
                );
                $i++;
            }
        }
    }
    
    //Datos complementarios del emisor
    $sql_dce = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_emisor_datacomp WHERE emisor_rfc = '" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id = " . $_SESSION['dol_entity'];
    $resql   = $db->query($sql_dce);
    if ($resql) {
        $num_emisor_datacomp = $db->num_rows($resql);
        $i                   = 0;
        if ($num_emisor_datacomp) {
            while ($i < $num_emisor_datacomp) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $emisor_delompio = utf8_decode($obj->emisor_delompio); // Convertir a Colonia
                    $emisor_calle    = utf8_decode($obj->emisor_calle);
                    $emisor_noint    = utf8_decode($obj->emisor_noint);
                    $emisor_noext    = utf8_decode($obj->emisor_noext);
                    $col_emisor      = limpiar(html_entity_decode($obj->emisor_colonia));
                }
                $i++;
            }
        }
    }
    
    //Datos complementarios del receptor
    //if($_REQUEST['tpdomi']){
    /*$resql = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_domicilios_receptor WHERE receptor_rfc = '" . $datareceptor_main["rfc"] . "' AND entity_id = " . $conf->entity . " AND fk_socid=" . $datareceptor_main["rowid"]);
    dol_syslog("DOMICILIOS:: SELECT * FROM " . MAIN_DB_PREFIX . "cfdimx_domicilios_receptor WHERE tpdomicilio='" . $_REQUEST['tpdomi'] . "' AND receptor_rfc = '" . $datareceptor_main["rfc"] . "' AND entity_id = " . $conf->entity);*/
    $sql = '';
    $sql = 'SELECT rowid, nom, address, zip, town, fk_departement, siren FROM '.MAIN_DB_PREFIX.'societe WHERE rowid='.$datareceptor_main["rowid"];

    $res_sql = $db->query($sql);

    // }else{
    // 	$resql=$db->query("SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc = '".$datareceptor_main["rfc"]."' AND entity_id = ".$_SESSION['dol_entity']);
    //}
    dol_syslog('ESTE QUERY DIR: ' . $sql);
    if ($res_sql) {
        $num_emisor_datacomp = $db->num_rows($res_sql);
        $i                   = 0;
        if ($num_emisor_datacomp) {
            while ($i < $num_emisor_datacomp) {
                $obj = $db->fetch_object($res_sql);
                if ($obj) {
                    /*$receptor_delompio = $obj->receptor_delompio;
                    $receptor_colonia  = utf8_decode($obj->receptor_colonia);
                    $receptor_calle    = utf8_decode($obj->receptor_calle);
                    $receptor_noint    = utf8_decode($obj->receptor_noint);
                    $receptor_noext    = utf8_decode($obj->receptor_noext);*/
                    $dir_estado = getState($obj->fk_departement,2);
                    //print $dir_estado;
                    // print '<pre>'; print_r($obj); print '</pre>'; die;

                    $receptor_delompio = $obj->town; //Municipio
                    $receptor_colonia  = '';
                    $receptor_calle    = $obj->address;
                    $receptor_noint    = '';
                    $receptor_noext    = '';
                }
                $i++;
            }
        }
    }
    
    //Retenciones locales
    $sqm            = "SELECT COUNT(*) AS count FROM information_schema.tables
					WHERE table_schema = '" . $db->database_name . "'
					 AND table_name = '" . MAIN_DB_PREFIX . "cfdimx_config_retenciones_locales'";
    $rqm            = $db->query($sqm);
    $rqsm           = $db->fetch_object($rqm);
    $total_retlocal = 0;
    if ($rqsm > 0) {
        $resqm = $db->query("SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones_locales WHERE fk_facture = " . $facid);
        if ($resqm) {
            $cfdi_m = $db->num_rows($resqm);
            $m      = 0;
            if ($cfdi_m > 0) {
                while ($m < $cfdi_m) {
                    $obm            = $db->fetch_object($resqm);
                    $total_retlocal = str_replace(",", "", number_format(($total_retlocal + $obm->importe), 2));
                    $m++;
                }
            }
        }
    }
    if ($total_retlocal > 0 && $banpreviw == 1) {
        $factura_total = $factura_total - $total_retlocal;
    }
    //ISH
    $sql         = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "product_extrafields LIKE 'prodcfish'";
    $resql       = $db->query($sql);
    $existe_ish  = $db->num_rows($resql);
    $impuestoish = "NO";
    if ($existe_ish > 0) {
        $sql = "SELECT count(*) as exist,importe  FROM " . MAIN_DB_PREFIX . "cfdimx_facturedet WHERE fk_facture=" . $facid . " AND impuesto='ISH'";
        $ass = $db->query($sql);
        $asd = $db->fetch_object($ass);
        if ($asd->exist > 0) {
            if ($factura_tipo == 2) {
                $vowels = array(
                    ",",
                    "-"
                );
            } else {
                $vowels = array(
                    ","
                );
            }
            $impuestoish = (str_replace($vowels, "", number_format($asd->importe, 2)));
        }
    }
    
    //retenciones
    $resql = $db->query("SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_retenciones WHERE fk_facture = " . $facid);
    if ($resql) {
        $tot_ret = $db->num_rows($resql);
        $i       = 0;
        if ($tot_ret) {
            while ($i < $tot_ret) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    if ($factura_tipo == 2) {
                        $vowels = array(
                            ",",
                            "-"
                        );
                    } else {
                        $vowels = array(
                            ","
                        );
                    }
                    $retenciones[$i]  = array(
                        "impuesto" => trim(preg_replace("/ +/", " ", $obj->impuesto)),
                        "importe" => str_replace($vowels, "", $obj->importe)
                    );
                    $retenciones2[$i] = array(
                        "impuesto" => trim(preg_replace("/ +/", " ", $obj->impuesto)),
                        "importe" => str_replace($vowels, "", number_format($obj->importe, 2))
                    );
                    if ($obj->importe > 0 && $banpreviw == 1) {
                        $factura_total = $factura_total - $obj->importe;
                    }
                }
                $i++;
            }
        }
    }
    // AMM evaluar
    // SE PASA LA LINEA HASTA DESPUES DE LA EXTRACCION DE LA TABLA
    //if($osd == "MXN"){$moneda = $conf->currency;}else{if($osd == ""){$moneda = $conf->currency;}else{$moneda = $osd;}}
    if ($conf->global->MAIN_MODULE_MULTIDIVISA) {
        
        // require_once DOL_DOCUMENT_ROOT.'/multidivisa/main.inc.php';//Addon multimoneda
        $sql    = "SELECT divisa FROM " . MAIN_DB_PREFIX . "multidivisa_facture WHERE fk_object=" . $facid;
        $ra     = $db->query($sql);
        $rb     = $db->fetch_object($ra);
        $moneda = $rb->divisa;
    } else {
        if ($conf->global->MAIN_MODULE_MULTICURRENCY) {
            $sql    = "SELECT multicurrency_code AS divisa FROM " . MAIN_DB_PREFIX . "facture WHERE rowid=" . $facid;
            $ra     = $db->query($sql);
            $rb     = $db->fetch_object($ra);
            $moneda = $rb->divisa;
        } else {
            //$moneda = $conf->currency;
            $moneda = !empty($osd) ? $osd : $conf->currency;
        }
    }
    $fecha_emison = $factura_fecha;
    $hora_emision = $factura_hora;
    if ($factura_tipo == 2) {
        $tipoComprobante = "E";
        //$tipoComprobante="egreso"; //Nota de credito
    } else {
        $tipoComprobante = "I";
        //$tipoComprobante="ingreso"; //factura
    }
    
    //DATOS DEL EMISOR
    $rfc_emisor          = $conf->global->MAIN_INFO_SIREN;
    $razon_social_emisor = utf8_decode($conf->global->MAIN_INFO_SOCIETE_NOM);
    $regimen             = utf8_decode(getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE));
    $separa_pais         = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
    $pais                = utf8_decode($separa_pais[2]);
    //$estado_emisor       = getState($conf->global->MAIN_INFO_SOCIETE_STATE);

    $separa_estado = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
    $estado_emisor = getState($separa_estado[0]);

    $estado_emisor       = utf8_decode($estado_emisor);
    
    $cp = $conf->global->MAIN_INFO_SOCIETE_ZIP;
    //$col_emisor = $conf->global->MAIN_INFO_SOCIETE_TOWN;
    if ($estado_emisor != "") {
        $lugar_exp = $emisor_delompio . " " . $estado_emisor;
    } else {
        $lugar_exp = "No identificado";
    }
    $lugar_exp = $cp;
    
    
    //DATOS DEL HEADER DEL COMPROBANTE
    $header          = array();
    $header["fecha"] = $fecha_emison . "T" . $hora_emision . "::" . $factura_tipo;
    if ($factura_tipo == 2) {
        $vowels = array(
            ",",
            "-"
        );
    } else {
        $vowels = array(
            ","
        );
    }
    $header["subTotal"] = (str_replace($vowels, "", number_format($factura_subtotal, 2)));
    if ($descheader != 0) {
        $header["subTotal"]  = (str_replace($vowels, "", number_format($factura_subtotal + $descheader, 2)));
        $header["descuento"] = (str_replace($vowels, "", number_format($descheader, 2)));
    }
    $header["total"]             = (str_replace($vowels, "", number_format($factura_total, 2)));
    $header["tipoDeComprobante"] = $tipoComprobante;
    $header["lugarExpedicion"]   = $lugar_exp;
    if ($factura_formapago == NULL || $factura_formapago == null || $factura_formapago == '') {
        $factura_formapago = 'No identificado';
    }
    if ($factura_condicionpago == NULL || $factura_condicionpago == null || $factura_condicionpago == '') {
        $factura_condicionpago = 'No identificado';
    }
    // 	$header["formaDePago"]=$factura_formapago;
    //  $header["metodoDePago"]=$factura_condicionpago;
    $header["formaDePago"]       = limpiar(html_entity_decode($factura_formapago));
    $header["condicionesDePago"] = limpiar(html_entity_decode($factura_condicionpago));
    $header["metodoDePago"]      = $factura_metodopago;
    //$header["metodoDePago2"]=$factura_metodopago2;
    
    
    //COMPLEMENTARIOS HEADER
    $parametros = array();
    if ($moneda == "") { //SE CAMBIA PARA AGREAGAR UN ESPACION DE MEMORIA VACIO
        $header["moneda"] = trim(preg_replace("/ +/", " ", $moneda));
    }
    $sqlk         = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "facture_extrafields LIKE 'tipodecambiocfdi'";
    $resqlk       = $db->query($sqlk);
    $existeTC     = $db->num_rows($resqlk);
    $banExtratipo = 0;
    if ($existeTC > 0) {
        $sqlk   = "SELECT tipodecambiocfdi FROM " . MAIN_DB_PREFIX . "facture_extrafields WHERE fk_object=" . $facid;
        $resqlk = $db->query($sqlk);
        $resk   = $db->fetch_object($resqlk);
        if ($resk->tipodecambiocfdi != NULL && $resk->tipodecambiocfdi != null && $resk->tipodecambiocfdi != "") {
            $header["tipoCambio"] = $resk->tipodecambiocfdi;
            $banExtratipo         = 1;
        }
    }
    if ($conf->global->MAIN_MODULE_MULTIDIVISA && $banExtratipo == 0) {
        $sql = "SELECT IFNULL(tipo_cambio,null) as tipo_cambio
			FROM " . MAIN_DB_PREFIX . "multidivisa
			WHERE fk_document=" . $facid . " AND type_document='facture' AND entity=" . $conf->entity . " ORDER BY rowid DESC LIMIT 1";
        $rt  = $db->query($sql);
        $rts = $db->fetch_object($rt);
        if ($rts->tipo_cambio != NULL) {
            $header["tipoCambio"] = $rts->tipo_cambio;
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
    
    //ADICIONALES
    $adicionales                = array();
    $adicionales["servicio_id"] = "2";
    if ($retenciones != "") {
        $adicionales["retenciones"]  = $retenciones;
        $adicionales2["retenciones"] = $retenciones2;
        // realizamos la resta de las retenciones en este caso se aplicara directamente al total
        $index                       = count($adicionales["retenciones"]);
        $suma_retenciones            = 0;
        for ($ir = 0; $ir < $index; $ir++) {
            $suma_retenciones = $suma_retenciones + $adicionales2["retenciones"][$ir]["importe"];
        }
        //$header["total"] = ($header["total"] - $suma_retenciones);
    }
    
    //DATOS DEL EMISOR
    $emisor                  = array();
    $emisor["emisorRFC"]     = $rfc_emisor;
    $emisor["emisorRegimen"] = $regimen;
    //COMPLEMENTARIOS EMISOR
    if ($razon_social_emisor != "") {
        $emisor["nombre"] = trim(preg_replace("/ +/", " ", $razon_social_emisor));
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
    if ($estado_emisor != "" && $estado_emisor != 0) {
        $emisor["estado"] = trim(preg_replace("/ +/", " ", $estado_emisor));
    }
    if ($pais != "") {
        $emisor["pais"] = trim(preg_replace("/ +/", " ", $pais));
    }
    if ($cp != "") {
        $emisor["codigoPostal"] = trim(preg_replace("/ +/", " ", $cp));
    }
    
    //DATOS DEL RECEPTOR
    $receptor        = array();
    $receptor["rfc"] = $datareceptor_main["rfc"];
    //COMPLEMENTARIOS RECEPTOR
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
        $receptor["estado"] = trim(preg_replace("/ +/", " ", $datareceptor_main["estado"]));
    }
    if ($factura_usocfdi != "") {
        $receptor["usoCFDI"] = trim(preg_replace("/ +/", " ", $factura_usocfdi));
    }
    if ($pais != "") {
        $receptor["pais"] = trim(preg_replace("/ +/", " ", $pais));
    }
    if ($datareceptor_main["cp"] != "") {
        $receptor["codigoPostal"] = trim(preg_replace("/ +/", " ", $datareceptor_main["cp"]));
    }

    $valida_rfc_emisor   = validaRFC($datareceptor_main);
    $valida_rfc_receptor = validaRFC($receptor["rfc"]);
    
    
    $sql            = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx WHERE fk_facture = '" . $facid . "' AND entity_id = " . $_SESSION['dol_entity'];
    $resql          = $db->query($sql);
    $num_llx_cfdimx = $db->num_rows($resql);
    
    $prmsnd["orden_compra"] = getOC($facid, $db);
    $prmsnd["logo"]        = $conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
    $prmsnd["logotipo"]    = $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL;

    // print '<pre>'; print_r($conf->global); print '</pre>'; die;
    
    $resql = $db->query("SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx WHERE fk_facture = '" . $facid . "' AND entity_id = " . $_SESSION['dol_entity']);
    if ($resql) {
        $num_llx_cfdimx = $db->num_rows($resql);
        $i              = 0;
        if ($num_llx_cfdimx) {
            while ($i < $num_llx_cfdimx) {
                $obj                     = $db->fetch_object($resql);
                $prmsnd["version"]       = $obj->version;
                $prmsnd["uuid"]          = $obj->uuid;
                $prmsnd["cadena"]        = $obj->cadena;
                $prmsnd["selloCFD"]      = $obj->selloCFD;
                $prmsnd["selloSAT"]      = $obj->sello;
                $prmsnd["fechaTimbrado"] = $obj->fechaTimbrado;
                $prmsnd["certificado"]   = $obj->certificado;
                $prmsnd["certEmisor"]    = $obj->certEmisor;
                $prmsnd["u4dig"]         = $obj->u4dig;
                $prmsnd["fechaEmision"]  = $obj->fecha_emision . "T" . $obj->hora_emision;
                $moneda                  = $obj->divisa; //se agrega al regenerar
                $prmsnd["coccds"]        = "||" . $prmsnd["version"] . "|" . $prmsnd["uuid"] . "|" . $prmsnd["fechaTimbrado"] . "|" . $prmsnd["selloCFD"] . "|" . $obj->certificado . "||";
                $i++;
            }
        }
    }
    if (strtoupper($serie) == '' || $folio == '') {
        $guion = "";
    } else {
        $guion = "-";
    }
    
    $header["moneda"] = trim(preg_replace("/ +/", " ", $moneda)); //se agrega la divisa al regenerar
    
    if ($factura_tipo == 2) {
        $vowels           = array(
            ",",
            "-"
        );
        $factura_subtotal = str_replace($vowels, "", $factura_subtotal);
        if ($descheader != 0) {
            $descheader = str_replace($vowels, "", $descheader);
        }
        $factura_iva = str_replace($vowels, "", $factura_iva);
        if ($impuestoish != 'NO') {
            $impuestoish = str_replace($vowels, "", $impuestoish);
        }
        $factura_total = str_replace($vowels, "", $factura_total);
    }
    include("generaPDF.php");
    $filename = $prmsnd["uuid"].'.pdf';
    print '<script> alert("Se refrescara la pagina para cargar el archivo pdf ..."); </script>';
    print '<script> window.opener.location.reload(); window.close(); </script>';
    print '<script>location.href="facture.php?facid='.$_REQUEST["facid"].'";</script>';

    //print '<script>location.href="generaPDF_new.php?facid='.$_REQUEST["facid"].'&route=regenpdf";</script>';
    
    $db->close();
}
?>