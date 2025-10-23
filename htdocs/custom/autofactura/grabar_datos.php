<?php
require_once '../../main.inc.php';
if( isset($_POST['ref']) && isset($_POST['fk_soc'])){
    $client = 1;
    $calle = dol_sanitizeFileName($_POST['calle']);//
    $colonia = dol_sanitizeFileName($_POST['colonia']);//
    $correo = $_POST['correo'];//
    $cp = price2num($_POST['cp']);//
    $fk_estado = price2num($_POST['estado']);//
    $fk_facture = price2num($_POST['fk_facture']);
    $fk_soc = price2num($_POST['fk_soc']);
    $metodo_cfdi = $_POST['metodo_cfdi'];//
    $municipio = dol_sanitizeFileName($_POST['municipio']);//
    $nombre = dol_sanitizeFileName($_POST['nombre']);//
    $nombre_anterior = dol_sanitizeFileName($_POST['nombre_anterior']);
    $numero_ext = dol_sanitizeFileName($_POST['numero_ext']);//
    $numero_int = dol_sanitizeFileName($_POST['numero_int']);//
    $ref = $_POST['ref'];
    $monto = $_POST['monto'];
    $rfc = dol_sanitizeFileName($_POST['rfc']);//
    $rfc_anterior = dol_sanitizeFileName($_POST['rfc_anterior']);//
    $uso_cfdi = $_POST['uso_cfdi'];//

    if($nombre == $nombre_anterior && $rfc == $rfc_anterior){//mismo nombre, se edita el mismo cliente
        //SOCIETE :: nombre, correo, cp, fk_estado,
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'societe SET ';
        $sql .= 'nom = "' . $nombre . '", ';
        $sql .= 'email = "' . $correo . '", ';
        $sql .= 'zip = "' . $cp . '", ';
        $sql .= 'fk_departement = ' . $fk_estado . ' ';
        $sql .= 'WHERE rowid = ' . $fk_soc;
        $error = 0;
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


        //CFDIMX_DOMICILIOS_RECEPTOR :: municipio, colonia, calle, noint, noext, rfc
        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'cfdimx_domicilios_receptor SET ';
        $sql .= 'receptor_rfc = "' . $rfc . '", ';
        $sql .= 'receptor_delompio = "' . $municipio . '", ';
        $sql .= 'receptor_colonia = "' . $colonia . '", ';
        $sql .= 'receptor_calle = "' . $calle . '", ';
        $sql .= 'receptor_noext = "' . $numero_ext . '", ';
        $sql .= 'receptor_noint = "' . $numero_int . '" ';
        $sql .= 'WHERE fk_socid = ' . $fk_soc . ' AND determinado = 1';
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
        //FACTURE_EXTRAFIELDS :: metodo_cfdi, uso_cfdi
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
    }
    else{
        $object = new Societe($db);
        $object->name = $nombre;
        $object->email = $correo;
        $object->zip = $cp;
        $object->state_id = $fk_estado;
        $object->country_id = 154;
        $object->client = $client;
        $object->town = $municipio;
        $object->idprof1 = $rfc;
        $res = $object->create($user);
        if($res < 0){
            $error++;
        }
        $new_fk_soc = $object->id;
        //CFDIMX_RECEPTOR_DATACOMP ::
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'cfdimx_receptor_datacomp(';
        $sql .= 'receptor_rfc, receptor_delompio, receptor_colonia, receptor_calle, ';
        $sql .= 'receptor_noext, receptor_noint, entity_id) ';
        $sql .= 'VALUES("'.$rfc.'","'.$municipio.'","'.$colonia.'","'.$calle.'"';
        $sql .= ',"'.$numero_ext.'","'.$numero_int.'", 1)';
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
        $tpdomicilio = 'Domicilio';
        $entity_id = 1;
        $determinado = 1;
        $cod_municipio = 'COD';
        $sql = 'SELECT receptor_id FROM ' . MAIN_DB_PREFIX . 'cfdimx_receptor_datacomp ';
        $sql .= 'WHERE receptor_rfc = "' . $rfc.'"';
        $receptor_id_result = $db->query($sql);
        if($receptor_id_result){
            $receptor_id = $db->fetch_object($receptor_id_result);
            $receptor_id = $receptor_id->receptor_id;
            $db->free($receptor_id_result);

            //CFDIMX_DOMICILIOS_RECEPTOR :: municipio, colonia, calle, noint, noext, rfc
            $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'cfdimx_domicilios_receptor(';
            $sql .= 'receptor_rfc, tpdomicilio, receptor_delompio, receptor_colonia, ';
            $sql .= 'receptor_calle, receptor_noext, receptor_noint, receptor_id, ';
            $sql .= 'entity_id, determinado, cod_municipio, fk_socid) VALUES( ';
            $sql .= '"'.$rfc.'","'.$tpdomicilio.'","'.$municipio.'","'.$colonia.'","'.$calle.'",';
            $sql .= '"'.$numero_ext.'","'.$numero_int.'",'.$receptor_id.','.$entity_id.',';
            $sql .= $determinado.',"'.$cod_municipio.'",'.$new_fk_soc.')';
            $resql=$db->query($sql);
            if ($resql){
                if (!$error) {
                    $db->commit();
                    $db->free($resql);
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facture ';
                    $sql .= 'SET fk_soc = ' . $new_fk_soc . ' ';
                    $sql .= 'WHERE ref = "' . $ref . '"';
                    $result_update = $db->query($sql);
                    if ($result_update){
                        if (!$error) { $db->commit(); }
                        else{ $db->rollback(); $error++; }
                    }
                    else{
                        $error=$db->error();
                        $db->rollback();
                        $error++;
                    }
                    $db->free($result_update);
                }
                else{ $db->rollback(); $error++; }
            }
            else{
                $error=$db->error();
                $db->rollback();
                $error++;
            }
        }else{
            $error++;
        }
        $fk_soc = $new_fk_soc;
    }
}
$post = array(
    'monto' => $monto,
    'ref' => $ref,
    'fk_socid' => $fk_soc,
    'fk_facture' => $fk_facture,
    'email' => $correo
);
$response = array(
    'response' => $error,
    'post' => $_POST,
    'params' => $post
);
echo json_encode($response);
