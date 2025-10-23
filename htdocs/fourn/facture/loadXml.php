<?php

/**
 * Copyright (C) 2020    Daniel Molina    <danmnvx@gmail.com>
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
if (!empty($conf->barcode->enabled)) dol_include_once('/core/class/html.formbarcode.class.php');

$action = GETPOST('action', 'alpha');
$socid = GETPOST('socid', 'int');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');

$langs->load('bills');
/**
 * Actions
 */

if ($action == "processxml") {
    $filexml = $_FILES["xmlToUpload"]["tmp_name"];

    if ($filexml) {
        $error = 0;
        $xmlObject = new stdClass();
        $facture = new FactureFournisseur($db);
        $xml = simplexml_load_file($filexml);
        $ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('c', $ns['cfdi']);

        // General properties
        foreach ($xml->xpath('//cfdi:Comprobante') as $cfdiComprobante) {
            $xmlObject->Fecha = str_replace("T", " ", $cfdiComprobante['Fecha']);
            $xmlObject->Payment = $cfdiComprobante['FormaPago']; //FIXME: Añadir forma de pago al objeto

            // Fields to check the amounts at the end of creation
            $xmlObject->Total = $cfdiComprobante['Total'];
            $xmlObject->SubTotal = $cfdiComprobante['SubTotal'];

            // Facture properties
            $facture->date = $db->jdate($xmlObject->Fecha);
            $facture->note_private = "Pedido de proveedor generado desde XML";
            $facture->ref_supplier = trim($cfdiComprobante['Serie'] . "-" . $cfdiComprobante['Folio']);
        }

        // Thirdparty
        foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $Emisor) {
            $xmlObject->Name = $Emisor['Nombre'];
            $xmlObject->Rfc = trim($Emisor['Rfc']);

            $thirdparty = Societe::getSupplierID($xmlObject->Rfc);

            // Societe not found
            if ($thirdparty == -1) {
                setEventMessage($langs->trans("SocNotFound", $xmlObject->Rfc), 'errors');
                $error++;
                break;
            } // If socid is defined match with thirdparty
            elseif ($socid > 0) {
                if ($socid != $thirdparty) {
                    setEventMessage($langs->trans("DifferentSoc", $xmlObject->Rfc), 'warnings');
                    $error++;
                    break;
                }
            }
            $facture->socid = $thirdparty;
            
            $provider_static = new fournisseur($db);
            $provider_static->fetch($thirdparty);
            switch($provider_static->cond_reglement_supplier_id)
            {
                case '2':
                    $credit_days = '30';
                    break;
                case '13':
                    $credit_days = '15';
                    break;
                case '14':
                    $credit_days = '45';
                    break;
                case '4':
                    $credit_days = '60';
                    break;
                case '9':
                    $credit_days = '10';
                    break;
                default:
                    $credit_days = '0';
                    break;
                 }
       $facture->date_echeance = ($facture->date+($credit_days*24*60*60));
            //die(var_dump($facture->date_echeance));

        }

        // If creation from order supplier
        if (!$error && !empty($origin) && !empty($originid)) {
            // Parse element/subelement
            // $element = 'fourn'; $subelement = 'fournisseur.commande';

            $facture->origin = $origin;
            $facture->origin_id = $originid;

            require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
            $objectsrc = new CommandeFournisseur($db);

            if ($objectsrc->fetch($originid) > 0) {
                $objectsrc->fetch_thirdparty();
                $facture->linkedObjectsIds[$facture->origin] = $facture->origin_id;
            } else {
                setEventMessage($langs->trans("ObjectNotLinked"), 'erros');
                $error++;
            }

            unset($origin);
            unset($originid);
            unset($objectsrc);
        }

        if (!$error) {
            // Facture lines
            $i = 0;
            foreach ($xml->xpath('//cfdi:Comprobante//cfdi:Conceptos//cfdi:Concepto') as $Concepto) {
                $ref_supplier = trim($Concepto["NoIdentificacion"]);
                $descripcion = trim($Concepto["Descripcion"]);
                $ref_supplier = (!empty($ref_supplier)) ? $ref_supplier : md5 ($xmlObject->Rfc . $descripcion);
                $clavesat = trim($Concepto["ClaveProdServ"]);
                $unidadmedida = trim($Concepto["ClaveUnidad"]);
                $uentrada = trim($Concepto["UnidadEntrada"]);
                $usalida = trim($Concepto["UnidadSalida"]);
                $product = (!empty($ref_supplier)) ? ProductFournisseur::getProduct($ref_supplier) : null;
                if ($product == null)
                    $product = ProductFournisseur::getProductByLabel($descripcion);
                $facture->lines[$i] = new SupplierInvoiceLine($db);

                // Defined product
                if ($product->id > 0) {
                    $facture->lines[$i]->fk_product = $product->id;
                    $facture->lines[$i]->product_ref = $product->ref;
                    $facture->lines[$i]->product_label = $product->label;
                    $facture->lines[$i]->description = (!empty($ref_supplier) && !empty($product->description)) ? $product->description : $descripcion;
                    $facture->lines[$i]->product_type = intval($product->type);
                    $facture->lines[$i]->notfound = 0;
                    $facture->lines[$i]->buyprice_updated = 1;
                    $facture->lines[$i]->claveprodserv = $clavesat;
                    $facture->lines[$i]->umed = $unidadmedida;
                    //Unidades de entrada
                    $sqlUnidadES = "SELECT cu.scale FROM abreviaciones_unidades au";
                    $sqlUnidadES.= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON cu.short_label = au.short_label";
                    $sqlUnidadES.= " WHERE JSON_SEARCH(labels, 'all', '".$uentrada."') IS NOT NULL LIMIT 1";
                    $result = $db->query($sqlUnidadES);
                    if($db->num_rows($result) > 0){
                        $obj = $db->fetch_object($result);
                        $facture->lines[$i]->uentrada = $obj->scale;
                    }
                    $sqlUnidadES = "SELECT cu.scale FROM abreviaciones_unidades au";
                    $sqlUnidadES.= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON cu.short_label = au.short_label";
                    $sqlUnidadES.= " WHERE JSON_SEARCH(labels, 'all', '".$usalida."') IS NOT NULL LIMIT 1";
                    $result = $db->query($sqlUnidadES);
                    if($db->num_rows($result) > 0){
                        $obj = $db->fetch_object($result);
                        $facture->lines[$i]->usalida = $obj->scale;
                    }

                    // Save Fourn id
                    $fourns[$i] = $product->fourn;
                } // Product not found (temporarily free)
                else {
                    $facture->lines[$i]->notfound = 1;
                    $facture->lines[$i]->product_type = 0;
                    $facture->lines[$i]->description = trim($Concepto['Descripcion']);
                }

                // Supplier product ref
                $facture->lines[$i]->ref_supplier = (!empty($ref_supplier)) ? $ref_supplier : md5 ($xmlObject->Rfc . $Concepto['Descripcion']);

                // Taxes
                foreach ($Concepto->xpath('cfdi:Impuestos//cfdi:Traslados//cfdi:Traslado') as $Traslado)
                    $facture->lines[$i]->tva_tx = floatval(trim($Traslado['TasaOCuota'])) * 100;

                // Quantity and unitary price
                $facture->lines[$i]->qty = trim($Concepto["Cantidad"]);
                $facture->lines[$i]->subprice = price2num(trim($Concepto["ValorUnitario"]), 'MU');
                $facture->lines[$i]->remise_percent = price2num(trim($Concepto['Descuento']))/ price2num(trim($Concepto['Importe'])) *100;

                $i++;
            }
            //die (var_dump($facture->lines));

            $res = $facture->create($user);

            // Demo FIXME: Handle errors
            if ($res > 0) {
                header('Location: ' . DOL_MAIN_URL_ROOT . "/fourn/facture/card.php?id=$res");
                exit;
            } else {
                $msg = "<div class='error'><h3>$facture->error</h3>";
                if ($res == -1) {
                    $msg .= "YA EXISTE EL FACTURA DE PROVEEDOR: $facture->ref_supplier";
                } elseif ($res == -2 && $facture->socid < 0) {
                    $msg .= "NO SE ENCONTRO PROVEEDOR CON RFC: " . $xmlObject->Rfc;
                } else {
                    $msg .= "ERROR AL GENERAR FACTURA DE PROVEEDOR";
                }
                $msg .= "</>";

            }
        }


    }
}

/**
 * View
 */

llxHeader("", $langs->trans('BillSupplierXml'));
print load_fiche_titre($langs->trans('NewBill'));

dol_htmloutput_events();


if (!empty($origin) && !empty($originid)) {
    // Parse element/subelement (ex: project_task)
    $element = $subelement = $origin;

    if ($element == 'project') {
        $projectid = $originid;
        $element = 'projet';
    }

    // For compatibility
    if ($element == 'order') {
        $element = $subelement = 'commande';
    }
    if ($element == 'propal') {
        $element = 'comm/propal';
        $subelement = 'propal';
    }
    if ($element == 'contract') {
        $element = $subelement = 'contrat';
    }
    if ($element == 'order_supplier') {
        $element = 'fourn';
        $subelement = 'fournisseur.commande';
    }

    require_once DOL_DOCUMENT_ROOT . '/' . $element . '/class/' . $subelement . '.class.php';
    $classname = ucfirst($subelement);

    if ($classname == 'Fournisseur.commande') $classname = 'CommandeFournisseur';
    $objectsrc = new $classname($db);
    $objectsrc->fetch($originid);
    $objectsrc->fetch_thirdparty();

    $projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
    //$ref_client			= (!empty($objectsrc->ref_client)?$object->ref_client:'');

    $soc = $objectsrc->thirdparty;
    $cond_reglement_id = (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_supplier_id) ? $soc->cond_reglement_supplier_id : 0)); // TODO maybe add default value option
    $mode_reglement_id = (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_supplier_id) ? $soc->mode_reglement_supplier_id : 0));
    $fk_account = (!empty($objectsrc->fk_account) ? $objectsrc->fk_account : (!empty($soc->fk_account) ? $soc->fk_account : 0));
    $remise_percent = (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_supplier_percent) ? $soc->remise_supplier_percent : 0));
    $remise_absolue = (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));
    $dateinvoice = empty($conf->global->MAIN_AUTOFILL_DATE) ? -1 : '';

    if (!empty($conf->multicurrency->enabled)) {
        if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
        if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx)) $currency_tx = $objectsrc->multicurrency_tx;
    }

    // Replicate extrafields
    $objectsrc->fetch_optionals($originid);
    $object->array_options = $objectsrc->array_options;
}

print '<div class="tabBar">';
print '<form action="loadXml.php" method="post" enctype="multipart/form-data">';
print '<input type="hidden" name="action" value="processxml">';
print '<table class="border centpercent">';
print '<tr><td>Selecciona XML para generar factura de proveedor:</td><td><input type="file" name="xmlToUpload" id="xmlToUpload" accept=".xml"></td></tr>';

if (is_object($objectsrc)) {
    if (GETPOST('socid') > 0) {
        $societe = new Societe($db);
        $societe->fetch(GETPOST('socid', 'int'));
        // Thirdparty
        print '<tr><td class="fieldrequired">' . $langs->trans('Supplier') . '</td>';
        print '<td>';
        print $societe->getNomUrl(1);
        print '<input type="hidden" name="socid" value="' . $societe->id . '">';
        print '</td></tr>';
    }

    print "\n<!-- " . $classname . " info -->";
    print "\n";
    print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
    print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
    print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
    print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
    print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

    $txt = $langs->trans($classname);
    if ($classname == 'CommandeFournisseur') {
        $langs->load('orders');
        $txt = $langs->trans("SupplierOrder");
    }
    print '<tr><td>' . $txt . '</td><td>' . $objectsrc->getNomUrl(1);
    // We check if Origin document (id and type is known) has already at least one invoice attached to it
    $objectsrc->fetchObjectLinked($originid, $origin, '', 'invoice_supplier');

    $invoice_supplier = $objectsrc->linkedObjects['invoice_supplier'];

    // count function need a array as argument (Note: the array must implement Countable too)
    if (is_array($invoice_supplier)) {
        $cntinvoice = count($invoice_supplier);

        if ($cntinvoice >= 1) {
            setEventMessages('WarningBillExist', null, 'warnings');
            echo ' (' . $langs->trans('LatestRelatedBill') . end($invoice_supplier)->getNomUrl(1) . ')';
        }
    }

    echo '</td></tr>';
    print '<tr><td>' . $langs->trans('AmountHT') . '</td><td>' . price($objectsrc->total_ht) . '</td></tr>';
    print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td>' . price($objectsrc->total_tva) . "</td></tr>";
    if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) //Localtax1
    {
        print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td>' . price($objectsrc->total_localtax1) . "</td></tr>";
    }

    if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) //Localtax2
    {
        print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td>' . price($objectsrc->total_localtax2) . "</td></tr>";
    }
    print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td>' . price($objectsrc->total_ttc) . "</td></tr>";

    if (!empty($conf->multicurrency->enabled)) {
        print '<tr><td>' . $langs->trans('MulticurrencyAmountHT') . '</td><td>' . price($objectsrc->multicurrency_total_ht) . '</td></tr>';
        print '<tr><td>' . $langs->trans('MulticurrencyAmountVAT') . '</td><td>' . price($objectsrc->multicurrency_total_tva) . "</td></tr>";
        print '<tr><td>' . $langs->trans('MulticurrencyAmountTTC') . '</td><td>' . price($objectsrc->multicurrency_total_ttc) . "</td></tr>";
    }

    // print '</table>';
}

print '</table>';

// Show origin lines
if (is_object($objectsrc)) {
    print '<br>';

    $title = $langs->trans('ProductsAndServices');
    print load_fiche_titre($title);

    print '<table class="noborder centpercent">';

    $objectsrc->printOriginLinesList('', array(), 1);

    print '</table>';
}

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '">';
print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
print '</div>';

print '</form>';
print '</div>';
print '<div>' . $msg . '</div>';


// End of page
llxFooter();
$db->close();
