<?php
require_once '../../master.inc.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';

if( isset($_POST['clave_factura']) ) {
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT . '/custom/autofactura_ticket/lib/fecha_facturacion.lib.php';
    $clave_factura = dol_sanitizeFileName(dol_string_nospecial($_POST['clave_factura']));
    $current_date = autofactura_ticket_fecha_hoy();
    $array_resultados = array();
    $timbrada = false;
    $found = false;
    $fecha_venta = '';
    $sql = '';
    $sql .= 'SELECT a.rowid as rowid, a.ref as ref, a.date_lim_reglement as date_lim, a.datef as datef, ';
    $sql .= 'a.multicurrency_total_ttc as monto_multicurrency, a.total_ttc as monto, b.fk_departement as fk_estado, ';
    $sql .= 'b.nom as nombre, b.zip as cp, b.email as email, a.fk_soc as fk_soc, b.siren as rfc ';
    $sql .= ', b.phone as phone '; 
    $sql .= 'FROM '.MAIN_DB_PREFIX.'facture as a, ';
    $sql .= MAIN_DB_PREFIX.'societe as b ';
    $sql .= 'WHERE a.ref = \'' . $clave_factura . '\' ';
    $sql .= 'AND b.rowid = a.fk_soc ';
    $sql .= 'AND a.fk_statut = ' . Facture::STATUS_CLOSED . ' ';//zip as CP
    $result = $db->query($sql);

    if ($result) {
        $num_resultado = $db->num_rows($result);
        $i = 0;
        while ($i < $num_resultado) {
            $row = $db->fetch_object($result);
            $monto_temp = $row->monto_multicurrency;
            if( $monto_temp == 0 ) $monto_temp = $row->monto;
            $fecha_venta = $row->datef;

            $array_resultados[$i] = array(
                'rowid' => $row->rowid,
                'ref' => $row->ref,
                'monto' => $monto_temp,
                'nombre' => $row->nombre,
                'correo' => $row->email,
                'cp' => (string)$row->cp,
                'fk_socid' => $row->fk_soc,
                'fk_estado' => $row->fk_estado,
                'date_lim' => $row->date_lim,
                'rfc' => $row->rfc,
                'phone' => $row->phone
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
        $sql = 'SELECT r.rowid as rowid, r.rfc as rfc, r.direccion as direccion, r.municipio as municipio, r.estado as estado, r.calle as calle, r.clave_col as clave_col,';
        $sql .= 'r.noext as noext, r.noint as noint, r.regimenfiscal as regimenfiscal, rf.label as label, r.fk_soc as fk_soc ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor r ';
        $sql .= 'JOIN ' . MAIN_DB_PREFIX . 'c_cfdimx_regimen_f rf ON r.regimenfiscal = rf.code ';
        $sql .= 'WHERE r.fk_soc = ' . $auxiliar['fk_socid'];
        $j = 0;
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            while($j < $num){
                $row = $db->fetch_object($result);
                $domicilio = array(
                    'rowid' => $row->rowid,
                    'tpdomicilio' => $row->direccion,
                    'receptor_delompio' => $row->municipio,
                    'receptor_colonia' => $row->clave_col,
                    'receptor_calle' => $row->calle,
                    'receptor_noext' => $row->noext,
                    'receptor_noint' => $row->noint,
                    'receptor_id' => $row->receptor_id,
                    'cod_municipio' => $row->cod_municipio,
                    'regimen_f' => $row->regimenfiscal,
                    'regimen_label' => $row->label,
                    'fk_socid' => $row->fk_soc,
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

        $sql = 'SELECT code, label FROM ';
        $sql .= MAIN_DB_PREFIX . 'c_cfdimx_regimen_f ';
        $sql .= 'WHERE active = 1';
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            $j = 0;
            while($j < $num){
                $row = $db->fetch_object($result);
                $array_regimen[$j] = array(
                    'code' => $row->code,
                    'label' => $row->label
                );
                $j++;
            }
            $db->free($result);
        }
        $array_resultados[$i+6] = $array_regimen;
        
    }
    // Límite = último día del mes de la venta (no del mes actual)
    $date_lim = $fecha_venta ? autofactura_ticket_fecha_limite($fecha_venta) : '';
    $array_resultados[$i+5] = array('date_lim'=>$date_lim,'current_date'=>$current_date);
    if ($found && !$timbrada && $fecha_venta && !autofactura_ticket_puede_facturar($fecha_venta)) {
        $json_resultado = json_encode(array(
            'error' => 1,
            'current_date' => $current_date,
            'date_limit' => $date_lim,
            'fecha_venta' => substr($fecha_venta, 0, 10),
        ));
    }
    else if($timbrada){
        $json_resultado = json_encode(array('error' => 2, 'uuid' => $uuid, 'ref' => $clave_factura));
    }
    else if($found){
        $json_resultado = json_encode($array_resultados);
    }
    else{
//        $json_resultado = json_encode(array('error' => -1));
        $json_resultado = json_encode($array_resultados);
    }
    echo $json_resultado;
}

if($action == 'buscarCliente'){
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    if( !empty($phone) ){
        $cliente_encontrado = false;
        $es_publico_general = false;

        $sql = 'SELECT s.rowid, s.nom, s.email, s.zip, s.siren, s.phone FROM ';
        $sql .= MAIN_DB_PREFIX . 'societe s ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_societe cs ON s.rowid = cs.fk_soc ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie c ON cs.fk_categorie = c.rowid ';
        $sql .= 'WHERE s.phone = "'.$phone.'"';
        $result = $db->query($sql);
        if($result){
            $num = $db->num_rows($result);
            if($num > 0){
                $cliente_encontrado = true;
                $row = $db->fetch_object($result);

                if ($row->nom && (
                    stripos($row->nom, 'Publico general') !== false || 
                    stripos($row->nom, 'Público general') !== false || 
                    stripos($row->nom, 'PUBLICO GENERAL') !== false
                )) {
                    $es_publico_general = true;
                }

                $sql_domicilio = 'SELECT r.rowid as rowid, r.rfc as rfc, r.direccion as direccion, r.municipio as municipio, r.estado as estado, r.calle as calle, r.clave_col as clave_col,e.rowid as rowid_estado,';
                $sql_domicilio .= 'r.noext as noext, r.noint as noint, r.regimenfiscal as regimenfiscal, rf.label as label, r.fk_soc as fk_soc ';
                $sql_domicilio .= 'FROM ' . MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor r ';
                $sql_domicilio .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'c_cfdimx_regimen_f rf ON r.regimenfiscal = rf.code ';
                $sql_domicilio .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'c_cfdimx_estado e ON r.estado = e.descripcion ';
                $sql_domicilio .= 'WHERE r.fk_soc = ' . $row->rowid;

                $result_domicilio = $db->query($sql_domicilio);
                $obj_domicilio = $db->fetch_object($result_domicilio);

                if($obj_domicilio->estado && $obj_domicilio != null){
                    $sql_estado = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_departements WHERE nom like '%".$obj_domicilio->estado."%'";
                    $result_estado = $db->query($sql_estado);
                    $obj_estado = $db->fetch_object($result_estado);
                    $rowid_estado = $obj_estado->rowid;
                }

                $domicilio = array(
                    'rowid' => $obj_domicilio->rowid,
                    'tpdomicilio' => $obj_domicilio->direccion,
                    'estado' => $obj_domicilio->estado,
                    'rowid_estado' => $rowid_estado ? $rowid_estado : '',
                    'municipio' => $obj_domicilio->municipio,
                    'colonia' => $obj_domicilio->clave_col,
                    'calle' => $obj_domicilio->calle,
                    'numero_ext' => $obj_domicilio->noext,
                    'numero_int' => $obj_domicilio->noint,
                    'regimen_fiscal' => $obj_domicilio->regimenfiscal,
                    'regimen_label' => $obj_domicilio->label,
                    'fk_socid' => $obj_domicilio->fk_soc,
                );
                $json_resultado = json_encode(array(
                    'rowid' => $row->rowid,
                    'nombre' => $row->nom,
                    'correo' => $row->email,
                    'cp' => (string)$row->zip,
                    'rfc' => $row->siren,
                    'phone' => $row->phone,
                    'cliente_encontrado' => $cliente_encontrado,
                    'es_publico_general' => $es_publico_general,
                    'domicilio' => $domicilio
                ));
            }
            else{
                $json_resultado = json_encode(array(
                    'error' => -1,
                    'cliente_encontrado' => $cliente_encontrado,
                    'es_publico_general' => false
                ));
            }
            $db->free($result);
        }
        echo $json_resultado;
    }else{
        echo json_encode(array('error' => -1));
    }
}

if($action == 'buscarClientes') {
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if(!empty($phone)) {
        $clientes = array();
        
        $sql = 'SELECT s.rowid, s.nom as nombre, s.email as correo, s.zip as cp, s.siren as rfc, s.phone 
                FROM ' . MAIN_DB_PREFIX . 'societe s 
                WHERE s.phone LIKE "%' . $db->escape($phone) . '%" 
                ORDER BY s.nom ASC 
                LIMIT 20';
        
        $result = $db->query($sql);
        if($result) {
            $num = $db->num_rows($result);
            if($num > 0) {
                $i = 0;
                while($i < $num) {
                    $row = $db->fetch_object($result);
                    
                    $es_publico_general = false;
                    if($row->nombre && (
                        stripos($row->nombre, 'Publico general') !== false || 
                        stripos($row->nombre, 'Público general') !== false || 
                        stripos($row->nombre, 'PUBLICO GENERAL') !== false
                    )) {
                        $es_publico_general = true;
                    }
                    
                    $clientes[] = array(
                        'rowid' => $row->rowid,
                        'nombre' => $row->nombre,
                        'correo' => $row->correo,
                        'cp' => (string)$row->cp,
                        'rfc' => $row->rfc,
                        'phone' => $row->phone,
                        'es_publico_general' => $es_publico_general
                    );
                    $i++;
                }
            }
            $db->free($result);
        }
        
        echo json_encode(array('clientes' => $clientes));
    } else {
        echo json_encode(array('clientes' => array()));
    }
}

if($action == 'obtenerCliente'){
    $rowid = isset($_POST['rowid']) ? intval($_POST['rowid']) : 0;
    
    if($rowid > 0){
        $response = array();
        $cliente_encontrado = false;
        
        $sql = 'SELECT s.rowid, s.nom as nombre, s.email as correo, s.zip as cp, s.phone, s.fk_departement ';
        $sql .= 'FROM ' . MAIN_DB_PREFIX . 'societe s ';
        $sql .= 'WHERE s.rowid = ' . $rowid;
        
        $result = $db->query($sql);
        if($result && $db->num_rows($result) > 0){
            $cliente_encontrado = true;
            $obj = $db->fetch_object($result);
            
            $es_publico_general = false;
            if ($obj->nombre && (
                stripos($obj->nombre, 'Publico general') !== false || 
                stripos($obj->nombre, 'Público general') !== false || 
                stripos($obj->nombre, 'PUBLICO GENERAL') !== false
            )) {
                $es_publico_general = true;
            }
            
            $sql_domicilio = 'SELECT r.rowid, r.direccion as tpdomicilio, r.municipio, r.estado, r.regimenfiscal, ';
            $sql_domicilio .= 'e.rowid as rowid_estado, r.rfc as rfc ';
            $sql_domicilio .= 'FROM ' . MAIN_DB_PREFIX . 'societe as s ';
            $sql_domicilio .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'cfdimx_domicilio_fiscal_receptor as r ON s.rowid = r.fk_soc ';
            $sql_domicilio .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'c_departements e ON e.rowid = s.fk_departement ';
            $sql_domicilio .= 'WHERE s.rowid = ' . $rowid;
            
            $result_domicilio = $db->query($sql_domicilio);
            $domicilio = array();
            
            if($result_domicilio && $db->num_rows($result_domicilio) > 0){
                $obj_dom = $db->fetch_object($result_domicilio);
                $domicilio = array(
                    'rowid' => $obj_dom->rowid,
                    'tpdomicilio' => $obj_dom->tpdomicilio,
                    'municipio' => $obj_dom->municipio,
                    'regimenfiscal' => $obj_dom->regimenfiscal,
                    'rowid_estado' => $obj_dom->rowid_estado,
                    'rfc' => $obj_dom->rfc
                );
            }
            
            $response = array(
                'cliente_encontrado' => true,
                'rowid' => $obj->rowid,
                'nombre' => $obj->nombre,
                'correo' => $obj->correo,
                'cp' => (string)$obj->cp,
                'rfc' => $domicilio['rfc'],
                'phone' => $obj->phone,
                'es_publico_general' => $es_publico_general,
                'domicilio' => $domicilio
            );
        } else {
            $response = array('cliente_encontrado' => false);
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(array('cliente_encontrado' => false));
    }
}
