<?php
require_once '../../main.inc.php';

if( isset($_POST['clave_factura']) && isset($_POST['monto']) ) {
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
    $clave_factura = dol_sanitizeFileName(dol_string_nospecial($_POST['clave_factura']));
    $monto = dol_sanitizeFileName(dol_string_nospecial($_POST['monto']));
    $current_date = date('Y-m-d');
    $array_resultados = array();
    $timbrada = false;
    $found = false;
    $sql = '';
    $sql .= 'SELECT a.rowid as rowid, a.ref as ref, a.date_lim_reglement as date_lim, ';
    $sql .= 'a.multicurrency_total_ttc as monto, b.fk_departement as fk_estado, ';
    $sql .= 'b.nom as nombre, b.zip as cp, b.email as email, a.fk_soc as fk_soc, b.siren as rfc ';
    $sql .= 'FROM '.MAIN_DB_PREFIX.'facture as a, ';
    $sql .= MAIN_DB_PREFIX.'societe as b ';
    $sql .= 'WHERE a.ref = "' . $clave_factura . '" ';
    $sql .= 'AND a.multicurrency_total_ttc = ' . $monto . ' ';
    $sql .= 'AND b.rowid = a.fk_soc ';
    $sql .= 'AND a.fk_statut = ' . Facture::STATUS_CLOSED . ' ';//zip as CP
    $result = $db->query($sql);

    if ($result) {
        $num_resultado = $db->num_rows($result);
        $i = 0;
        while ($i < $num_resultado) {
            $row = $db->fetch_object($result);
            $array_resultados[$i] = array(
                'rowid' => $row->rowid,
                'ref' => $row->ref,
                'monto' => $row->monto,
                'nombre' => $row->nombre,
                'correo' => $row->email,
                'cp' => $row->cp,
                'fk_socid' => $row->fk_soc,
                'fk_estado' => $row->fk_estado,
                'date_lim' => $row->date_lim,
                'rfc' => $row->rfc
            );
            $i++;
        }
        $db->free($result);
    }
    if($num_resultado > 0){
        //Checando que la factura no haya sido timbrada previamente
        $sql = 'SELECT factura_seriefolio, uuid FROM ';
        $sql .= MAIN_DB_PREFIX . 'cfdimx ';
        $sql .= 'WHERE factura_seriefolio = "'.$clave_factura.'"';
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            if($num > 0){
                $row = $db->fetch_object($result);
                $uuid = $row->uuid;
                $timbrada = true;
            }
            $db->free($result);
        }
        $found = true;
        $array_uso_cfdi = array();
        $sql = 'SELECT code, label FROM ';
        $sql .= MAIN_DB_PREFIX . 'c_cfdimx_uso_cfdi ';
        $sql .= 'WHERE active = 1';
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            $j = 0;
            while($j < $num){
                $row = $db->fetch_object($result);
                $array_uso_cfdi[$j] = array(
                    'code' => $row->code,
                    'label' => $row->label
                );
                $j++;
            }
            $db->free($result);
        }
        $array_resultados[$i] = $array_uso_cfdi;
        $metodo_pago = array();
        $sql = 'SELECT param FROM ' . MAIN_DB_PREFIX . 'extrafields ';
        $sql .= 'WHERE name = "formpagcfdi"';
        $j = 0;
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            while($j < $num){
                $row = $db->fetch_object($result);
                $metodo_pago = array('param' => $row->param);
                $j++;
            }
            $db->free($result);
        }
        $array_resultados[$i+1] = $metodo_pago;
        $auxiliar = $array_resultados[0];
        $domicilio = array();
        $sql = 'SELECT rowid, receptor_rfc, tpdomicilio, receptor_delompio, receptor_colonia, ';
        $sql .= 'receptor_calle, receptor_noext, receptor_noint, receptor_id, cod_municipio, fk_socid ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'cfdimx_domicilios_receptor ';
        $sql .= 'WHERE fk_socid = ' . $auxiliar['fk_socid'];
        $j = 0;
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            while($j < $num){
                $row = $db->fetch_object($result);
                $domicilio = array(
                    'rowid' => $row->rowid,
                    'tpdomicilio' => $row->tpdomicilio,
                    'receptor_delompio' => $row->receptor_delompio,
                    'receptor_colonia' => $row->receptor_colonia,
                    'receptor_calle' => $row->receptor_calle,
                    'receptor_noext' => $row->receptor_noext,
                    'receptor_noint' => $row->receptor_noint,
                    'receptor_id' => $row->receptor_id,
                    'cod_municipio' => $row->cod_municipio,
                    'fk_socid' => $row->fk_socid
                );
                $j++;
            }
            $db->free($result);
        }
        $array_resultados[$i+2] = $domicilio;
        $estados = array();
        $sql = 'SELECT rowid, code_departement, nom  ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'c_departements ';
        $sql .= 'WHERE fk_region = 15401';
        $j = 0;
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            while($j < $num){
                $row = $db->fetch_object($result);
                $estados[$j] = array(
                    'rowid' => $row->rowid,
                    'code_departement' => $row->code_departement,
                    'nom' => $row->nom
                );
                $j++;
            }
            $db->free($result);
        }
        $array_resultados[$i+3] = $estados;
        $cfdi_sel = array();
        $sql = 'SELECT formpagcfdi, usocfdi  ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'facture_extrafields ';
        $sql .= 'WHERE fk_object = ' . $auxiliar['rowid'];
        $j = 0;
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            while($j < $num){
                $row = $db->fetch_object($result);
                $cfdi_sel[$j] = array(
                    'formpagcfdi' => $row->formpagcfdi,
                    'usocfdi' => $row->usocfdi
                );
                $j++;
            }
            $db->free($result);
        }
        $array_resultados[$i+4] = $cfdi_sel;
    }
    $date_lim = date('Y-m-d', strtotime($auxiliar['date_lim']. ' + 3 days'));
    $array_resultados[$i+5] = array('date_lim'=>$date_lim,'current_date'=>$current_date);

    if( ($current_date > $date_lim) && $found && !$timbrada ){
        $json_resultado = json_encode(array('error' => 1));
    }
    else if($timbrada){
        $json_resultado = json_encode(array('error' => 2, 'uuid' => $uuid, 'ref' => $clave_factura));
    }
    else if($found){
        $json_resultado = json_encode($array_resultados);
    }
    else{
        $json_resultado = json_encode(array('error' => -1));
    }
    echo $json_resultado;
}
