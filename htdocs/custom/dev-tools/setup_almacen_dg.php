<?php
/**
 * One-shot: crea almacén y proveedor "Almacen DG".
 * - Domicilio copiado de CEDIS
 * - RFC propio: DFB240327DN8 (llx_c_rfc)
 * Constantes: DG_WAREHOUSE, DG_SUPPLIER. Idempotente.
 *
 * CLI: docker exec dermaplastic-test-php php /var/www/html/htdocs/custom/dev-tools/setup_almacen_dg.php
 */

if (php_sapi_name() === 'cli') {
	define('NOLOGIN', 1);
	define('NOREQUIREMENU', 1);
	define('NOREQUIREHTML', 1);
	define('NOREQUIREAJAX', 1);
	define('NOCSRFCHECK', 1);
}

require __DIR__.'/../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

header('Content-Type: text/plain; charset=utf-8');

if (php_sapi_name() !== 'cli') {
	if (empty($user->id) || empty($user->admin)) {
		accessforbidden();
	}
} else {
	$user->fetch(1);
	$user->getrights();
}

const DG_RFC_CODE = 'DFB240327DN8';
const DG_RFC_LABEL = 'Almacen DG';

$cedisWhId = !empty($conf->global->CEDIS_WAREHOUSE) ? (int) $conf->global->CEDIS_WAREHOUSE : 29;
$cedis = new Entrepot($db);
if ($cedis->fetch($cedisWhId) <= 0) {
	echo "ERROR: No se encontro almacen CEDIS (id={$cedisWhId})\n";
	exit(1);
}

// --- RFC catalog entry ---
$dgRfcId = 0;
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_rfc WHERE code = '".$db->escape(DG_RFC_CODE)."' LIMIT 1";
$res = $db->query($sql);
if ($res && ($obj = $db->fetch_object($res))) {
	$dgRfcId = (int) $obj->rowid;
	echo "RFC ".DG_RFC_CODE." ya existe (rowid={$dgRfcId})\n";
} else {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_rfc (code, label, active) VALUES ("
		."'".$db->escape(DG_RFC_CODE)."',"
		."'".$db->escape(DG_RFC_LABEL)."',"
		."1)";
	if (!$db->query($sql)) {
		echo "ERROR al crear RFC ".DG_RFC_CODE.": ".$db->lasterror()."\n";
		exit(1);
	}
	$dgRfcId = (int) $db->last_insert_id(MAIN_DB_PREFIX.'c_rfc');
	echo "RFC ".DG_RFC_CODE." creado (rowid={$dgRfcId})\n";
}

// --- Warehouse ---
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE ref = 'Almacen DG' AND entity IN (0, ".(int) $conf->entity.") LIMIT 1";
$res = $db->query($sql);
$dgWhId = 0;
if ($res && ($obj = $db->fetch_object($res))) {
	$dgWhId = (int) $obj->rowid;
	$dg = new Entrepot($db);
	$dg->fetch($dgWhId);
	$dg->libelle = 'Almacen DG';
	$dg->description = 'Almacen DG — hub interno a sucursales (RFC '.DG_RFC_CODE.')';
	$dg->lieu = 'DG';
	$dg->address = $cedis->address;
	$dg->zip = $cedis->zip;
	$dg->town = $cedis->town;
	$dg->pays_id = $cedis->country_id ? $cedis->country_id : 154;
	$dg->statut = Entrepot::STATUS_OPEN_ALL;
	$dg->fk_rfc = $dgRfcId;
	$upd = $dg->update($dgWhId, $user);
	if ($upd <= 0) {
		echo "ERROR al actualizar Almacen DG: ".$dg->error."\n";
		exit(1);
	}
	echo "Almacen DG actualizado (rowid={$dgWhId}, fk_rfc={$dgRfcId})\n";
} else {
	$dg = new Entrepot($db);
	$dg->libelle = 'Almacen DG';
	$dg->description = 'Almacen DG — hub interno a sucursales (RFC '.DG_RFC_CODE.')';
	$dg->lieu = 'DG';
	$dg->address = $cedis->address;
	$dg->zip = $cedis->zip;
	$dg->town = $cedis->town;
	$dg->pays_id = $cedis->country_id ? $cedis->country_id : 154;
	$dg->statut = Entrepot::STATUS_OPEN_ALL;
	$dg->fk_rfc = $dgRfcId;

	$dgWhId = $dg->create($user);
	if ($dgWhId <= 0) {
		echo "ERROR al crear Almacen DG: ".$dg->error."\n";
		exit(1);
	}
	echo "Almacen DG creado (rowid={$dgWhId}, fk_rfc={$dgRfcId})\n";
}

dolibarr_set_const($db, 'DG_WAREHOUSE', (string) $dgWhId, 'chaine', 0, 'Warehouse id for Almacen DG', $conf->entity);
$conf->global->DG_WAREHOUSE = $dgWhId;
echo "Constante DG_WAREHOUSE={$dgWhId}\n";

// --- Supplier (mirror CEDIS societe pattern) ---
$cedisSupId = !empty($conf->global->CEDIS_SUPPLIER) ? (int) $conf->global->CEDIS_SUPPLIER : 0;
$cedisSoc = new Societe($db);
if ($cedisSupId > 0) {
	$cedisSoc->fetch($cedisSupId);
}

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE nom = 'Almacen DG' AND fournisseur = 1 AND entity IN (0, ".(int) $conf->entity.") ORDER BY rowid ASC LIMIT 1";
$res = $db->query($sql);
$dgSupId = 0;
if ($res && ($obj = $db->fetch_object($res))) {
	$dgSupId = (int) $obj->rowid;
	echo "Proveedor Almacen DG ya existe (rowid={$dgSupId})\n";
} else {
	$soc = new Societe($db);
	$soc->name = 'Almacen DG';
	$soc->nom = 'Almacen DG';
	$soc->client = 0;
	$soc->fournisseur = 1;
	$soc->code_fournisseur = -1; // auto
	$soc->country_id = 154;
	$soc->tva_assuj = 1;
	$soc->status = 1;
	// Copy address / contact from CEDIS warehouse (societe CEDIS is usually empty)
	$soc->address = $cedis->address;
	$soc->zip = $cedis->zip;
	$soc->town = $cedis->town;
	if (!empty($cedisSoc->id)) {
		if (!empty($cedisSoc->phone)) $soc->phone = $cedisSoc->phone;
		if (!empty($cedisSoc->email)) $soc->email = $cedisSoc->email;
		if (!empty($cedisSoc->idprof1)) $soc->idprof1 = $cedisSoc->idprof1;
		if (!empty($cedisSoc->tva_intra)) $soc->tva_intra = $cedisSoc->tva_intra;
	}

	$dgSupId = $soc->create($user);
	if ($dgSupId <= 0) {
		echo "ERROR al crear proveedor Almacen DG: ".$soc->error."\n";
		if (!empty($soc->errors)) {
			echo implode("\n", $soc->errors)."\n";
		}
		exit(1);
	}
	echo "Proveedor Almacen DG creado (rowid={$dgSupId})\n";
}

dolibarr_set_const($db, 'DG_SUPPLIER', (string) $dgSupId, 'chaine', 0, 'Supplier societe id for Almacen DG', $conf->entity);
$conf->global->DG_SUPPLIER = $dgSupId;
echo "Constante DG_SUPPLIER={$dgSupId}\n";
echo "OK\n";
