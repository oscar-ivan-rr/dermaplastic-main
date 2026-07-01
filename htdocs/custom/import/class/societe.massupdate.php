<?php
/**
 * Actualización masiva de clientes desde el CSV generado por /custom/export
 * (element=societe&type=2).
 *
 * Solo actualiza clientes EXISTENTES emparejados por "Código de Cliente"
 * (llx_societe.code_client); nunca crea clientes nuevos. Todo se hace con SQL
 * directo: los catálogos se precargan en un query cada uno y cada fila del CSV
 * genera un solo UPDATE dentro de una transacción.
 *
 * Las celdas vacías se ignoran (no borran el valor actual del cliente).
 * La columna "Descuento absoluto ($)" se ignora: en la exportación es la suma
 * de descuentos disponibles; volver a crearla en cada importación duplicaría
 * los descuentos.
 */

/**
 * @param DoliDB    $db
 * @param User      $user
 * @param Translate $langs
 * @param Conf      $conf
 * @param string    $filepath   Ruta del CSV subido
 * @param string    $delimiter  ',' o ';'
 * @return array    ('updates' => int, 'errors' => int, 'warnings' => int, 'rows_failed' => array de filas para el log)
 */
function societe_mass_update(DoliDB $db, User $user, Translate $langs, Conf $conf, $filepath, $delimiter)
{
    $info = array('updates' => 0, 'errors' => 0, 'warnings' => 0, 'rows_failed' => array());

    if (false === ($gestor = fopen($filepath, 'r'))) {
        $info['errors']++;
        $info['rows_failed'][] = array('linea' => '', 'codigo' => '', 'nombre' => '', 'tipo' => 'error', 'mensaje' => 'No se pudo abrir el archivo');
        return $info;
    }

    $langs->load('bills');
    $langs->load('dict');

    // Normaliza una celda: quita BOM/espacios y convierte a UTF-8 si el archivo
    // fue re-guardado en latin1 (Excel).
    $clean = function ($v) {
        $v = preg_replace('/^\xEF\xBB\xBF/', '', (string) $v);
        if ($v !== '' && !mb_check_encoding($v, 'UTF-8')) $v = utf8_encode($v);
        return trim($v);
    };

    // ---- Encabezados -> índice de columna (mismos nombres que la exportación) ----
    $header = fgetcsv($gestor, 0, $delimiter);
    if ($header === false) {
        fclose($gestor);
        $info['errors']++;
        $info['rows_failed'][] = array('linea' => 1, 'codigo' => '', 'nombre' => '', 'tipo' => 'error', 'mensaje' => 'Archivo vacío');
        return $info;
    }

    $expected = array(
        'nombre'                                => 'nombre',
        'apodo'                                 => 'alias',
        'facturación automatica'                => 'auto_invoicing',
        'rfc'                                   => 'rfc',
        'código de cliente'                     => 'code_client',
        'cliente'                               => 'tipo_cliente',
        'estado'                                => 'status',
        'dirección'                             => 'address',
        'código postal'                         => 'zip',
        'población'                             => 'town',
        'país'                                  => 'country',
        'provincia'                             => 'state',
        'tipo de pago'                          => 'payment_mode',
        'telefono'                              => 'phone',
        'email'                                 => 'email',
        'web'                                   => 'url',
        'tipo de tercero'                       => 'typent',
        'días de crédito'                       => 'payment_term_days',
        'asignado al comercial'                 => 'sales_reps',
        'descuento fijo (%)'                    => 'remise_client',
        'descuento por pronto pago (%)'         => 'earlypayment_discount',
        'importe máximo de facturas pendientes' => 'outstanding_limit',
        'estado de prospección'                 => 'stcomm',
        'iva'                                   => 'tva_intra',
        'categoría'                             => 'categories',
        'pago cfdi'                             => 'formpagcfdi',
        'uso cfdi'                              => 'usocfdi',
        // 'descuento absoluto ($)' se ignora a propósito (ver comentario de cabecera).
    );

    $col = array(); // campo interno => índice de columna en el CSV
    foreach ($header as $i => $name) {
        $key = mb_strtolower($clean($name), 'UTF-8');
        if (isset($expected[$key])) $col[$expected[$key]] = $i;
    }

    if (!isset($col['code_client'])) {
        fclose($gestor);
        $info['errors']++;
        $info['rows_failed'][] = array('linea' => 1, 'codigo' => '', 'nombre' => '', 'tipo' => 'error', 'mensaje' => 'Falta la columna "Código de Cliente" en el encabezado');
        return $info;
    }

    $cell = function ($register, $field) use ($col, $clean) {
        if (!isset($col[$field]) || !isset($register[$col[$field]])) return '';
        return $clean($register[$col[$field]]);
    };

    // ---- Precarga de catálogos (una consulta por catálogo) ----

    // Clientes por código
    $clientMap = array();
    $resql = $db->query("SELECT rowid, code_client, remise_client FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur = 0 AND code_client IS NOT NULL AND code_client <> ''");
    if ($resql) {
        while ($o = $db->fetch_object($resql)) {
            $clientMap[mb_strtoupper(trim($o->code_client), 'UTF-8')] = array('rowid' => (int) $o->rowid, 'remise' => (float) $o->remise_client);
        }
    }

    // Países por código (MX, US, ...)
    $countryMap = array();
    $resql = $db->query("SELECT rowid, code FROM ".MAIN_DB_PREFIX."c_country");
    if ($resql) while ($o = $db->fetch_object($resql)) $countryMap[mb_strtoupper(trim($o->code), 'UTF-8')] = (int) $o->rowid;

    // Provincias por nombre
    $stateMap = array();
    $resql = $db->query("SELECT rowid, nom FROM ".MAIN_DB_PREFIX."c_departements");
    if ($resql) while ($o = $db->fetch_object($resql)) $stateMap[mb_strtoupper(trim($o->nom), 'UTF-8')] = (int) $o->rowid;

    // Tipos de pago: se indexan tanto por libelle crudo como por su traducción,
    // porque la exportación escribe $langs->trans(libelle).
    $paymentModeMap = array();
    $resql = $db->query("SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement");
    if ($resql) {
        while ($o = $db->fetch_object($resql)) {
            $paymentModeMap[mb_strtolower(trim($o->libelle), 'UTF-8')] = (int) $o->id;
            $paymentModeMap[mb_strtolower(trim($langs->trans($o->libelle)), 'UTF-8')] = (int) $o->id;
        }
    }

    // Condiciones de pago por días de crédito
    $paymentTermMap = array();
    $resql = $db->query("SELECT MIN(rowid) AS rowid, nbjour FROM ".MAIN_DB_PREFIX."c_payment_term WHERE active = 1 GROUP BY nbjour");
    if ($resql) while ($o = $db->fetch_object($resql)) $paymentTermMap[(int) $o->nbjour] = (int) $o->rowid;

    // Tipos de tercero por libelle
    $typentMap = array();
    $resql = $db->query("SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_typent");
    if ($resql) while ($o = $db->fetch_object($resql)) $typentMap[mb_strtolower(trim($o->libelle), 'UTF-8')] = (int) $o->id;

    // Usuarios (comerciales) por nombre completo
    $userMap = array();
    $resql = $db->query("SELECT rowid, CONCAT(firstname, ' ', lastname) AS fullname FROM ".MAIN_DB_PREFIX."user");
    if ($resql) while ($o = $db->fetch_object($resql)) $userMap[mb_strtoupper(trim($o->fullname), 'UTF-8')] = (int) $o->rowid;

    // Categorías de cliente por etiqueta (type 2 = clientes/prospectos)
    $categoryMap = array();
    $resql = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."categorie WHERE type = 2");
    if ($resql) while ($o = $db->fetch_object($resql)) $categoryMap[mb_strtoupper(trim($o->label), 'UTF-8')] = (int) $o->rowid;

    // ---- Mapeos de valores de texto (inversos a los CASE de la exportación) ----
    $mapCliente = array(
        'cliente'                          => 1,
        'cliente potencial'                => 2,
        'cliente potencial / cliente'      => 3,
        'ni cliente, ni cliente potencial' => 0,
    );
    $mapStcomm = array(
        'nunca contactado'    => 0,
        'no contactar'        => -1,
        'para ser contactado' => 1,
        'contacto en proceso' => 2,
        'contacto realizado'  => 3,
    );

    $entity = (int) $conf->entity;
    $userId = (int) $user->id;

    $db->begin();

    $lineNum = 1;
    while (($register = fgetcsv($gestor, 0, $delimiter)) !== false) {
        $lineNum++;
        if (count($register) == 1 && trim((string) $register[0]) === '') continue; // línea vacía

        $code   = mb_strtoupper($cell($register, 'code_client'), 'UTF-8');
        $nombre = $cell($register, 'nombre');

        if ($code === '') {
            $info['errors']++;
            $info['rows_failed'][] = array('linea' => $lineNum, 'codigo' => '', 'nombre' => $nombre, 'tipo' => 'error', 'mensaje' => 'Código de cliente vacío');
            continue;
        }
        if (!isset($clientMap[$code])) {
            $info['errors']++;
            $info['rows_failed'][] = array('linea' => $lineNum, 'codigo' => $code, 'nombre' => $nombre, 'tipo' => 'error', 'mensaje' => 'Cliente no encontrado (no se crean clientes nuevos)');
            continue;
        }

        $socid = $clientMap[$code]['rowid'];
        $set = array();
        $warn = function ($mensaje) use (&$info, $lineNum, $code, $nombre) {
            $info['warnings']++;
            $info['rows_failed'][] = array('linea' => $lineNum, 'codigo' => $code, 'nombre' => $nombre, 'tipo' => 'advertencia', 'mensaje' => $mensaje);
        };

        // Campos de texto directos: celda vacía = no tocar
        if ($nombre !== '') {
            if (is_numeric($nombre)) $warn('El nombre no puede ser numérico; no se actualizó el nombre');
            else $set[] = "nom = '".$db->escape($nombre)."'";
        }
        $v = $cell($register, 'alias');   if ($v !== '') $set[] = "name_alias = '".$db->escape($v)."'";
        $v = $cell($register, 'rfc');     if ($v !== '') $set[] = "siren = '".$db->escape($v)."'";
        $v = $cell($register, 'address'); if ($v !== '') $set[] = "address = '".$db->escape($v)."'";
        $v = $cell($register, 'zip');     if ($v !== '') $set[] = "zip = '".$db->escape($v)."'";
        $v = $cell($register, 'town');    if ($v !== '') $set[] = "town = '".$db->escape($v)."'";
        $v = $cell($register, 'phone');   if ($v !== '') $set[] = "phone = '".$db->escape($v)."'";
        $v = $cell($register, 'email');   if ($v !== '') $set[] = "email = '".$db->escape($v)."'";
        $v = $cell($register, 'url');     if ($v !== '') $set[] = "url = '".$db->escape($v)."'";
        $v = $cell($register, 'tva_intra'); if ($v !== '') $set[] = "tva_intra = '".$db->escape($v)."'";

        // Facturación automática: Si/No
        $v = mb_strtolower($cell($register, 'auto_invoicing'), 'UTF-8');
        if ($v !== '') $set[] = "automatic_invoicing = ".($v == 'si' ? 1 : 0);

        // Tipo de cliente
        $v = mb_strtolower($cell($register, 'tipo_cliente'), 'UTF-8');
        if ($v !== '') {
            if (isset($mapCliente[$v])) $set[] = "client = ".$mapCliente[$v];
            else $warn('Tipo de cliente no reconocido: '.$v);
        }

        // Estatus: Activo/Suspendido
        $v = mb_strtolower($cell($register, 'status'), 'UTF-8');
        if ($v !== '') $set[] = "status = ".($v == 'activo' ? 1 : 0);

        // País por código
        $v = mb_strtoupper($cell($register, 'country'), 'UTF-8');
        if ($v !== '') {
            if (isset($countryMap[$v])) $set[] = "fk_pays = ".$countryMap[$v];
            else $warn('País no encontrado: '.$v);
        }

        // Provincia por nombre
        $v = mb_strtoupper($cell($register, 'state'), 'UTF-8');
        if ($v !== '') {
            if (isset($stateMap[$v])) $set[] = "fk_departement = ".$stateMap[$v];
            else $warn('Provincia no encontrada: '.$v);
        }

        // Tipo de pago
        $v = mb_strtolower($cell($register, 'payment_mode'), 'UTF-8');
        if ($v !== '') {
            if (isset($paymentModeMap[$v])) $set[] = "mode_reglement = ".$paymentModeMap[$v];
            else $warn('Tipo de pago no encontrado: '.$v);
        }

        // Condiciones de pago por días de crédito
        $v = $cell($register, 'payment_term_days');
        if ($v !== '') {
            if (is_numeric($v) && isset($paymentTermMap[(int) $v])) $set[] = "cond_reglement = ".$paymentTermMap[(int) $v];
            else $warn('No hay condición de pago con '.$v.' días de crédito');
        }

        // Tipo de tercero
        $v = mb_strtolower($cell($register, 'typent'), 'UTF-8');
        if ($v !== '') {
            if (isset($typentMap[$v])) $set[] = "fk_typent = ".$typentMap[$v];
            else $warn('Tipo de tercero no encontrado: '.$v);
        }

        // Descuento por pronto pago (%)
        $v = $cell($register, 'earlypayment_discount');
        if ($v !== '') {
            if (is_numeric($v)) $set[] = "earlypayment_discount = ".(float) $v;
            else $warn('Descuento por pronto pago no numérico: '.$v);
        }

        // Importe máximo de facturas pendientes
        $v = $cell($register, 'outstanding_limit');
        if ($v !== '') {
            if (is_numeric($v)) $set[] = "outstanding_limit = ".(float) $v;
            else $warn('Importe máximo no numérico: '.$v);
        }

        // Estado de prospección
        $v = mb_strtolower($cell($register, 'stcomm'), 'UTF-8');
        if ($v !== '') {
            if (isset($mapStcomm[$v])) $set[] = "fk_stcomm = ".$mapStcomm[$v];
            else $warn('Estado de prospección no reconocido: '.$v);
        }

        // Descuento fijo (%): se actualiza societe.remise_client y, si cambió,
        // se agrega la fila de historial que Dolibarr espera en societe_remise.
        $v = $cell($register, 'remise_client');
        if ($v !== '') {
            if (is_numeric($v)) {
                $remise = (float) $v;
                $set[] = "remise_client = ".$remise;
                if ($remise != $clientMap[$code]['remise']) {
                    $db->query("INSERT INTO ".MAIN_DB_PREFIX."societe_remise (entity, datec, fk_soc, remise_client, note, fk_user_author)"
                        ." VALUES ($entity, NOW(), $socid, $remise, 'Actualización masiva por importación', $userId)");
                }
            } else {
                $warn('Descuento fijo no numérico: '.$v);
            }
        }

        $rowOk = true;
        if (count($set)) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET ".implode(', ', $set).", fk_user_modif = $userId WHERE rowid = $socid";
            if (!$db->query($sql)) {
                $rowOk = false;
                $info['errors']++;
                $info['rows_failed'][] = array('linea' => $lineNum, 'codigo' => $code, 'nombre' => $nombre, 'tipo' => 'error', 'mensaje' => 'Error SQL: '.$db->lasterror());
            }
        }

        // Extrafields CFDI (fk_object tiene índice único en las tablas *_extrafields)
        if ($rowOk) {
            $v = mb_strtoupper($cell($register, 'formpagcfdi'), 'UTF-8');
            if ($v !== '') {
                $db->query("INSERT INTO ".MAIN_DB_PREFIX."societe_extrafields (fk_object, formpagcfdi) VALUES ($socid, '".$db->escape($v)."')"
                    ." ON DUPLICATE KEY UPDATE formpagcfdi = '".$db->escape($v)."'");
            }
            $v = mb_strtoupper($cell($register, 'usocfdi'), 'UTF-8');
            if ($v !== '') {
                $db->query("INSERT INTO ".MAIN_DB_PREFIX."societe_extrafields (fk_object, usocfdi) VALUES ($socid, '".$db->escape($v)."')"
                    ." ON DUPLICATE KEY UPDATE usocfdi = '".$db->escape($v)."'");
            }
        }

        // Comerciales asignados: la exportación los separa con ';'. Si la celda
        // trae contenido se reemplaza la asignación completa.
        if ($rowOk) {
            $v = $cell($register, 'sales_reps');
            if ($v !== '') {
                $ids = array();
                foreach (explode(';', $v) as $name) {
                    $key = mb_strtoupper(trim($name), 'UTF-8');
                    if ($key === '') continue;
                    if (isset($userMap[$key])) $ids[$userMap[$key]] = true;
                    else $warn('Comercial no encontrado: '.trim($name));
                }
                if (count($ids)) {
                    $db->query("DELETE FROM ".MAIN_DB_PREFIX."societe_commerciaux WHERE fk_soc = $socid");
                    foreach (array_keys($ids) as $uid) {
                        $db->query("INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux (fk_soc, fk_user) VALUES ($socid, $uid)");
                    }
                }
            }
        }

        // Categorías: separadas con ';'. Si la celda trae contenido se reemplazan
        // todas las categorías de cliente del tercero.
        if ($rowOk) {
            $v = $cell($register, 'categories');
            if ($v !== '') {
                $ids = array();
                foreach (explode(';', $v) as $label) {
                    $key = mb_strtoupper(trim($label), 'UTF-8');
                    if ($key === '') continue;
                    if (isset($categoryMap[$key])) $ids[$categoryMap[$key]] = true;
                    else $warn('Categoría no encontrada: '.trim($label));
                }
                if (count($ids)) {
                    $db->query("DELETE FROM ".MAIN_DB_PREFIX."categorie_societe WHERE fk_soc = $socid");
                    foreach (array_keys($ids) as $cid) {
                        $db->query("INSERT INTO ".MAIN_DB_PREFIX."categorie_societe (fk_categorie, fk_soc) VALUES ($cid, $socid)");
                    }
                }
            }
        }

        if ($rowOk) $info['updates']++;
    }

    fclose($gestor);
    $db->commit();

    return $info;
}
