<?php

function validaRFC($valor) {

$valor = str_replace("-", "", $valor);
$cuartoValor = substr($valor, 3, 1);
//RFC Persona Moral.
if (ctype_digit($cuartoValor) && strlen($valor) == 12) {
    $letras = substr($valor, 0, 3);
    $numeros = substr($valor, 3, 6);
    $homoclave = substr($valor, 9, 3);
    $search = array("Ã‘", "&");//caracteres admitidos por el SAT
    $replace = R;//se reemplaza en la busqueda para omitir el caracter
    $letras = str_replace($search, $replace, $letras);	//reemplazar
    if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
        return true;
    }
//RFC Persona Fisica.
} else if (ctype_alpha($cuartoValor) && strlen($valor) == 13) {
    $letras = substr($valor, 0, 4);
    $numeros = substr($valor, 4, 6);
    $homoclave = substr($valor, 10, 3);
    if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
        return true;
    }
}else {
    return false;
}
}

function limpiar($String){
$String = str_replace(array('�','�','�','�','�','�'),"a",$String);
    $String = str_replace(array('�','�','�','�','�'),"A",$String);
    $String = str_replace(array('�','�','�','�'),"I",$String);
    $String = str_replace(array('�','�','�','�'),"i",$String);
    $String = str_replace(array('�','�','�','�'),"e",$String);
    $String = str_replace(array('�','�','�','�'),"E",$String);
    $String = str_replace(array('�','�','�','�','�','�'),"o",$String);
    $String = str_replace(array('�','�','�','�','�'),"O",$String);
    $String = str_replace(array('�','�','�','�'),"u",$String);
    $String = str_replace(array('�','�','�','�'),"U",$String);
    $String = str_replace(array('[','^','�','`','�','~',']'),"",$String);
    $String = str_replace("�","c",$String);
    $String = str_replace("�","C",$String);
    $String = str_replace("�","n",$String);
    $String = str_replace("�","N",$String);
    $String = str_replace("�","Y",$String);
    $String = str_replace("�","y",$String);
    $String = str_replace("&aacute;","a",$String);
    $String = str_replace("&Aacute;","A",$String);
    $String = str_replace("&eacute;","e",$String);
    $String = str_replace("&Eacute;","E",$String);
    $String = str_replace("&iacute;","i",$String);
    $String = str_replace("&Iacute;","I",$String);
    $String = str_replace("&oacute;","o",$String);
    $String = str_replace("&Oacute;","O",$String);
    $String = str_replace("&uacute;","u",$String);
    $String = str_replace("&Uacute;","U",$String);
return $String;
}

function getDataCliente( $db, $id ){
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe	WHERE rowid = " . $id;
    $resql=$db->query($sql);
    $obj = $db->fetch_object($resql);
    $data["rowid"] = $obj->rowid;
    $data["rfc"] = $obj->siren;
    $data["razon_social"] = utf8_decode($obj->nom);
    $data["colonia"] = utf8_decode($obj->town); //Covertir a del o mpio
    $data["estado"] = utf8_decode(getState($obj->fk_departement));
    $data["cp"] = $obj->zip;
    $data["email"] = $obj->email;
    return $data;
}

function getUnidadMedida( $db, $id ){

    $umed="No identificado";
    if( $id!="" ){
        $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."product_extrafields'";
        $resql=$db->query($sql);
        $existe_tabla = $db->num_rows($resql);
        if( $existe_tabla>0 ){
            $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'umed'";
            $resql=$db->query($sql);
            $existe_umed = $db->num_rows($resql);
            if( $existe_umed > 0 ){
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = " . $id;
                $resql=$db->query($sql);
                $obj = $db->fetch_object($resql);
                if( $obj->umed!="" && $obj->umed != 0){ $umed = utf8_decode($obj->umed); }else{ $umed = "NA"; }
            }else{
                $umed = "ZZ";
            }
        }else{
            $umed = "ZZ";
        }
    }else{ $umed = "ZZ"; }

    return $umed;
}

function getclaveprodserv( $db, $id ){

    $umed="No identificado";
    if( $id!="" ){
        $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."product_extrafields'";
        $resql=$db->query($sql);
        $existe_tabla = $db->num_rows($resql);
        if( $existe_tabla>0 ){
            $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'claveprodserv'";
            $resql=$db->query($sql);
            $existe_umed = $db->num_rows($resql);
            if( $existe_umed > 0 ){
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = " . $id;
                $resql=$db->query($sql);
                $obj = $db->fetch_object($resql);
                if( $obj->claveprodserv!="" && $obj->claveprodserv!=0 ){ $claveprodserv = utf8_decode($obj->claveprodserv); }else{ $claveprodserv = "01010101"; }
            }else{
                $claveprodserv = "01010101";
            }
        }else{
            $claveprodserv = "01010101";
        }
    }else{ $claveprodserv = "01010101"; }

    return $claveprodserv;
}

function getnoIdentificacion( $db, $id ){

    if( $id!="" ){
        $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."product_extrafields'";
        $resql=$db->query($sql);
        $existe_tabla = $db->num_rows($resql);
        if( $existe_tabla>0 ){
            $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'noidenticfdi'";
            $resql=$db->query($sql);
            $existe_noidenticfdi = $db->num_rows($resql);
            if( $existe_noidenticfdi > 0 ){
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = " . $id;
                $resql=$db->query($sql);
                $obj = $db->fetch_object($resql);
                if( $obj->noidenticfdi!="" && $obj->noidenticfdi!=NULL && $obj->noidenticfdi!=null ){ 
                    $noIdentificacion =($obj->noidenticfdi); 
                }else{ $noIdentificacion = NULL; }
            }else{
                $noIdentificacion = NULL;
            }
        }else{
            $noIdentificacion = NULL;
        }
    }else{ $noIdentificacion = NULL; }

    return $noIdentificacion;
}

function getCuentaPredial( $db, $id ){

    if( $id!="" ){
        $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX."product_extrafields'";
        $resql=$db->query($sql);
        $existe_tabla = $db->num_rows($resql);
        if( $existe_tabla>0 ){
            $sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'cuentapredial'";
            $resql=$db->query($sql);
            $existe_cuentapredial = $db->num_rows($resql);
            if( $existe_cuentapredial > 0 ){
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."product_extrafields WHERE fk_object = " . $id;
                $resql=$db->query($sql);
                $obj = $db->fetch_object($resql);
                if( $obj->cuentapredial!="" && $obj->cuentapredial!=NULL && $obj->cuentapredial!=null ){
                    $cuentapredial =($obj->cuentapredial);
                }else{ $cuentapredial = NULL; }
            }else{
                $cuentapredial = NULL;
            }
        }else{
            $cuentapredial = NULL;
        }
    }else{ $cuentapredial = NULL; }

    return $cuentapredial;
}

function getU4DigCta( $id, $db ){
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe_rib  WHERE default_rib=1 AND fk_soc = " . $id;
    $resql=$db->query($sql);
    $nmc = $db->fetch_object($resql);
    $total_char = strlen($nmc->number);
    if( $total_char>=4 ){
        //$cuenta = substr($nmc->number,0,-4);
        $cuenta = $nmc->number;
    }else{
        $cuenta = "";
    }
    return $cuenta;
}

function getProducto( $id, $db ){
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid = " . $id;
    $resql=$db->query($sql);
    $obj = $db->fetch_object($resql);
    $producto['ref'] = utf8_decode($obj->ref);
    $producto['label'] = utf8_decode($obj->label);
    $producto['description'] = utf8_decode($obj->description);

    return $producto;
}

function getFormasPago( $id, $db ){
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_paiement WHERE id = " . $id;
    $resql=$db->query($sql);
    $obj = $db->fetch_object($resql);
    $data["code"]=$obj->code;
    $data["tipo_pago"]=html_entity_decode($obj->libelle);
    return $data;
}

function getCondicionesPago( $id, $db ){
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_payment_term WHERE rowid = " . $id;
    $resql=$db->query($sql);
    $obj = $db->fetch_object($resql);
    $data["code"] = $obj->code;
    $data["dias_credito"] = $obj->nbjour;
    $data["condicion_pago"] = html_entity_decode($obj->libelle);
    return $data;
}

function getOC( $id, $db ){
    $sql = "
    SELECT c.ref FROM ".MAIN_DB_PREFIX ."element_element e, ".MAIN_DB_PREFIX ."commande c
    WHERE e.sourcetype = 'commande'
    AND e.fk_target = ".$id."
    AND e.fk_source = c.rowid";
    dol_syslog("GETOC::".$sql);
    $resql=$db->query($sql);
    $obj = $db->fetch_object($resql);
    $aux=$obj->ref;
    dol_syslog('ORDEN:GEN:'.$aux);
    return $aux;
}

function truncateFloat($number, $digitos)
{
    $raiz = 10;
    $multiplicador = pow ($raiz,$digitos);
    $resultado = ((int)($number * $multiplicador)) / $multiplicador;
    return number_format($resultado, $digitos);

}

function remultimo($buscar, $remplazar, $texto){
    $pos = strrpos($texto, $buscar);
    if($pos !== false){
        $texto = substr_replace($texto, $remplazar, $pos, strlen($buscar));
    }
    return $texto;
}
