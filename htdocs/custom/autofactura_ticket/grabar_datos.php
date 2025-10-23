<?php
require_once '../../master.inc.php';
if( isset($_POST['ref']) && isset($_POST['fk_soc']) && isset($_POST['phone'])){
    $client = 1;
    $direccion = dol_sanitizeFileName($_POST['direccion']);
    $correo = $_POST['correo'];
    $cp = (string)$_POST['cp'];
    $fk_estado = price2num($_POST['estado']);
    $fk_facture = price2num($_POST['fk_facture']);
    $fk_soc = price2num($_POST['fk_soc']);
    $metodo_cfdi = $_POST['metodo_cfdi'];
    $municipio = dol_sanitizeFileName($_POST['municipio']);
    $nombre = dol_sanitizeFileName($_POST['nombre']);
    $nombre_anterior = dol_sanitizeFileName($_POST['nombre_anterior']);
    $regimen_fiscal = $_POST['regimen_fiscal'];
    $ref = $_POST['ref'];
    $monto = $_POST['monto'];
    $rfc = dol_sanitizeFileName($_POST['rfc']);
    $rfc_anterior = dol_sanitizeFileName($_POST['rfc_anterior']);
    $uso_cfdi = $_POST['uso_cfdi'];

    $phone = $_POST['phone'];
    
    $error = 0;

    $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'societe WHERE phone = "'.$phone.'"';
    $result = $db->query($sql);
    $num = $db->num_rows($result);
    if($num > 0){
        $obj = $db->fetch_object($result);
        $fk_soc = $obj->rowid;
        
        $sql_check_client = 'SELECT nom FROM ' . MAIN_DB_PREFIX . 'societe WHERE rowid = '.$fk_soc;
        $result_check_client = $db->query($sql_check_client);
        if($result_check_client){
            $client_data = $db->fetch_object($result_check_client);
            
            $es_publico_general = false;
            if($client_data->nom && (
                stripos($client_data->nom, 'Publico general') !== false || 
                stripos($client_data->nom, 'Público general') !== false || 
                stripos($client_data->nom, 'PUBLICO GENERAL') !== false
            )) {
                $es_publico_general = true;
            }
            $db->free($result_check_client);
            
            if($es_publico_general){
            } else {
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe SET ';
                $sql .= 'nom = "' . $nombre . '", ';
                $sql .= 'email = "' . $correo . '", ';
                $sql .= 'zip = "' . $cp . '", ';
                $sql .= 'siren = "' . $rfc . '", ';
                $sql .= 'fk_departement = ' . $fk_estado . ' ';
                $sql .= 'WHERE rowid = ' . $fk_soc;
                $sql .= ' AND phone = "'.$phone.'"';
                
                $resql=$db->query($sql);
                if ($resql)
                {
                    if (!$error) {
                        $db->commit();
                    }
                    else{
                        $db->rollback();
                        $error++;
                    }
                }
                else
                {
                    $error=$db->error();
                    $db->rollback();
                    $error++;
                }

                $sql = 'SELECT nom  ';
                $sql .= 'FROM ' . MAIN_DB_PREFIX . 'c_departements ';
                $sql .= 'WHERE rowid = ' . $fk_estado . '';
                $result = $db->query($sql);
                $est = $db->fetch_object($result);
                $estado = $est->nom;
                $db->free($result);

                $sql = 'SELECT rowid FROM ';
                $sql .= MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor ';
                $sql .= 'WHERE fk_soc = ' . $fk_soc;
                $result = $db->query($sql);
                if($result){
                    $domicilio_f = $db->fetch_object($result);
                    $db->free($result);
                }

                if(!empty($domicilio_f->rowid)){
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor SET ';
                    $sql .= 'rfc = "' . $rfc . '", ';
                    $sql .= 'municipio = "' . $municipio . '", ';
                    $sql .= 'nombre = "' . $nombre . '" ,';
                    $sql .= 'cp = "' . $cp . '" ,';
                    $sql .= 'estado = "' . $estado . '" ,';
                    $sql .= 'pais = "MEX" ,';
                    $sql .= 'regimenfiscal = "' . $regimen_fiscal . '", ';
                    $sql .= 'direccion = "' . $direccion . '", ';
                    $sql .= 'entity_id = 1 ';
                    $sql .= 'WHERE fk_soc = ' . $fk_soc . '';
                    $resql=$db->query($sql);
                    if ($resql)
                    {
                        if (!$error) {
                            $db->commit();
                        }
                        else{
                            $db->rollback();
                            $error++;
                        }

                    }
                    else
                    {
                        $error=$db->error();
                        $db->rollback();
                        $error++;
                    }
                } else{
                    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor(';
                    $sql .= 'rfc, municipio, nombre, cp, estado, pais, regimenfiscal, fk_soc, entity_id, direccion) ';
                    $sql .= 'VALUES("'.$rfc.'","'.$municipio.'", "'.$nombre.'", "'.$cp.'", "'.$estado.'", "MEX", "'.$regimen_fiscal.'", "'.$fk_soc.'", 1, "'. $direccion .'")';
                    $resql=$db->query($sql);
                    if ($resql){
                        if (!$error) { $db->commit(); }
                        else{ $db->rollback(); $error++; }
                    }
                    else{
                        $error=$db->error();
                        $db->rollback();
                        $error++;
                    }
                }
                
                //FACTURE_EXTRAFIELDS :: metodo_cfdi, uso_cfdi
                $sql = 'SELECT rowid FROM ';
                $sql .= MAIN_DB_PREFIX . 'facture_extrafields ';
                $sql .= 'WHERE fk_object = ' . $fk_facture;
                $result = $db->query($sql);
                if($result){
                    $facture_extrafield = $db->fetch_object($result);
                    $db->free($result);
                }

                if(!empty($facture_extrafield->rowid)){
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture_extrafields SET ';
                    $sql .= 'formpagcfdi = "' . $metodo_cfdi . '", ';
                    $sql .= 'usocfdi = "' . $uso_cfdi . '" ';
                    $sql .= 'WHERE fk_object = ' . $fk_facture;
                    $resql=$db->query($sql);
                    if ($resql)
                    {
                        if (!$error) {
                            $db->commit();
                        }
                        else{
                            $db->rollback();
                            $error++;
                        }

                    }
                    else
                    {
                        $error=$db->error();
                        $db->rollback();
                        $error++;
                    }
                } else{
                    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'facture_extrafields(';
                    $sql .= 'formpagcfdi, usocfdi, fk_object) ';
                    $sql .= 'VALUES("'.$metodo_cfdi.'","'.$uso_cfdi.'", "'.$fk_facture.'")';
                    $resql=$db->query($sql);
                    if ($resql){
                        if (!$error) { $db->commit(); }
                        else{ $db->rollback(); $error++; }
                    }
                    else{
                        $error=$db->error();
                        $db->rollback();
                        $error++;
                    }
                }
                
                $object->idprof1 = $rfc;
                
                $sql = 'SELECT label FROM ';
                $sql .= MAIN_DB_PREFIX . 'c_cfdimx_uso_cfdi ';
                $sql .= 'WHERE code = "'.$uso_cfdi.'"';
                $result = $db->query($sql);
                if($result){
                    $uso_cfdi = $db->fetch_object($result);
                    $db->free($result);
                }

                $sql = 'SELECT nom FROM ';
                $sql .= MAIN_DB_PREFIX . 'c_departements ';
                $sql .= 'WHERE rowid = "'.$fk_estado.'"';
                $result = $db->query($sql);
                if($result){
                    $estado = $db->fetch_object($result);
                    $db->free($result);
                }

                if($metodo_cfdi == 'PUE'){
                    $metodo_pago= 'Pago en una sola exhibición';
                } else {
                    $metodo_pago = 'Pago en parcialidades';
                }

                $sql = 'SELECT label FROM ';
                $sql .= MAIN_DB_PREFIX . 'c_cfdimx_regimen_f ';
                $sql .= 'WHERE code = "'.$regimen_fiscal.'"';
                $result = $db->query($sql);
                if($result){
                    $regimen_f = $db->fetch_object($result);
                    $db->free($result);
                }
            }
        }
    }else{
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'societe(';
        $sql .= 'nom, email, zip, siren, fk_departement, client, phone, address, mode_reglement, cond_reglement) ';
        $sql .= 'VALUES("'.$nombre.'","'.$correo.'","'.$cp.'","'.$rfc.'","'.$fk_estado.'","1","'.$phone.'","'.$calle.' '.$numero_ext.' '.$numero_int.' '.$colonia.' '.$municipio.'","1","1")';
        $resql=$db->query($sql);
        if (!$resql){
            $error=$db->error();
            $db->rollback();
            $error++;
        }

        $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'societe WHERE phone = "'.$phone.'"';
        $result = $db->query($sql);
        $soc = $db->fetch_object($result);
        $fk_soc = $soc->rowid;
        $db->free($result);

        $sql_category = 'INSERT INTO ' . MAIN_DB_PREFIX . 'categorie_societe(fk_categorie, fk_soc) VALUES(2,'. $fk_soc.')';
        $resql=$db->query($sql_category);
        if (!$resql){
            $error=$db->error();
            $db->rollback();
            $error++;
        }

        $sql = 'SELECT nom  ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'c_departements ';
        $sql .= 'WHERE rowid = ' . $fk_estado . '';
        $result = $db->query($sql);
        $est = $db->fetch_object($result);
        $estado = $est->nom;
        $db->free($result);

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor(';
        $sql .= 'rfc, municipio, nombre, cp, estado, pais, regimenfiscal, fk_soc, entity_id, direccion) ';
        $sql .= 'VALUES("'.$rfc.'","'.$municipio.'", "'.$nombre.'", "'.$cp.'", "'.$estado.'", "MEX", "'.$regimen_fiscal.'", "'.$fk_soc.'", 1, "'. $direccion .' '.$municipio.'")';
        $resql=$db->query($sql);
        if (!$resql){
            $error=$db->error();
            $db->rollback();
            $error++;
        }

        $sql = 'SELECT rowid FROM ';
        $sql .= MAIN_DB_PREFIX . 'facture_extrafields ';
        $sql .= 'WHERE fk_object = ' . $fk_facture;
        $result = $db->query($sql);
        if($result){
            $facture_extrafield = $db->fetch_object($result);
            $db->free($result);
        }

        if(!empty($facture_extrafield->rowid)){
            $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture_extrafields SET ';
            $sql .= 'formpagcfdi = "' . $metodo_cfdi . '", ';
            $sql .= 'usocfdi = "' . $uso_cfdi . '" ';
            $sql .= 'WHERE fk_object = ' . $fk_facture;
            $resql=$db->query($sql);
            if ($resql)
            {
                if (!$error) {
                    $db->commit();
                }
                else{
                    $db->rollback();
                    $error++;
                }

            }
            else
            {
                $error=$db->error();
                $db->rollback();
                $error++;
            }
        } else{
            $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'facture_extrafields(';
            $sql .= 'formpagcfdi, usocfdi, fk_object) ';
            $sql .= 'VALUES("'.$metodo_cfdi.'","'.$uso_cfdi.'", "'.$fk_facture.'")';
            $resql=$db->query($sql);
            if ($resql){
                if (!$error) { $db->commit(); }
                else{ $db->rollback(); $error++; }
            }
            else{
                $error=$db->error();
                $db->rollback();
                $error++;
            }
        }
        
        $sql = 'SELECT label FROM ';
        $sql .= MAIN_DB_PREFIX . 'c_cfdimx_uso_cfdi ';
        $sql .= 'WHERE code = "'.$uso_cfdi.'"';
        $result = $db->query($sql);
        if($result){
            $uso_cfdi = $db->fetch_object($result);
            $db->free($result);
        }

        $sql = 'SELECT nom FROM ';
        $sql .= MAIN_DB_PREFIX . 'c_departements ';
        $sql .= 'WHERE rowid = "'.$fk_estado.'"';
        $result = $db->query($sql);
        if($result){
            $estado = $db->fetch_object($result);
            $db->free($result);
        }

        if($metodo_cfdi == 'PUE'){
            $metodo_pago= 'Pago en una sola exhibición';
        } else {
            $metodo_pago = 'Pago en parcialidades';
        }

        $sql = 'SELECT label FROM ';
        $sql .= MAIN_DB_PREFIX . 'c_cfdimx_regimen_f ';
        $sql .= 'WHERE code = "'.$regimen_fiscal.'"';
        $result = $db->query($sql);
        if($result){
            $regimen_f = $db->fetch_object($result);
            $db->free($result);
        }

    }

    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture SET ';
    $sql .= 'fk_soc = ' . $fk_soc . ' ';
    $sql .= 'WHERE rowid = ' . $fk_facture;
    $resql=$db->query($sql);
    if (!$resql){
        $error=$db->error();
        $db->rollback();
        $error++;
    } else {
        $db->commit();
    }
}
$post = array(
    'monto' => $monto,
    'ref' => $ref,
    'fk_socid' => $fk_soc,
    'fk_facture' => $fk_facture,
    'email' => $correo,
    'uso_cfdi' => $uso_cfdi->label,
    'estado' => $estado->nom,
    'regimen' => $regimen_f->label,
    'metodo_pago' => $metodo_pago
);
$response = array(
    'response' => $error,
    'post' => $_POST,
    'params' => $post   
);
echo json_encode($response);
