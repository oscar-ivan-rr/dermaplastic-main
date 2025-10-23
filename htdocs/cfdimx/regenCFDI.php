<?php
// Se realiza una consula al ws de busqueda de registros

$sql_mod_tim   = ' SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_config_ws ';
$sql_mod_tim  .= ' WHERE emisor_rfc = "'.$conf->global->MAIN_INFO_SIREN.'" AND entity_id = '. $_SESSION['dol_entity'];

$res_mod_tim = $db->query($sql_mod_tim);
if($res_mod_tim){
	$num_mod_tim = $db->num_rows($res_mod_tim);
	if($num_mod_tim >0){
		$obj_mod = $db->fetch_object($num_mod_tim);
		if($obj_mod->ws_modo_timbrado == 1){
			$wsadmin='http://facturacion.auriboxenlinea.com/doli_extract_timbrado/prod/doli_reg_cfdimx.php?wsdl';
		}elseif($obj_mod->ws_modo_timbrado == 2){
			$wsadmin='http://facturacion.auriboxenlinea.com/doli_extract_timbrado/pruebas/doli_reg_cfdimx.php?wsdl';
		}
	}
}else{
	$ws_msg = ' error en el web services de doli_extract_timbrado: '. $wsadmin;
}


/*
function checkURL($url){
	if(!filter_var($url, FILTER_VALIDATE_URL)) {
		return 0;
	}
	$curlInit = curl_init($url);
	curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curlInit, CURLOPT_HEADER, true);
	curl_setopt($curlInit, CURLOPT_NOBODY, true);
	curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curlInit);
	curl_close($curlInit);
	if ($response) return 1;
	return 0;
}
*/
$sql_tim  = '';
$sql_tim .= ' SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_config ';
$sql_tim .= ' WHERE emisor_rfc = "'.$conf->global->MAIN_INFO_SIREN.'" AND entity_id = '. $_SESSION['dol_entity'];

$res_tim=$db->query($sql_tim);

if ($res_tim){

	//$num = $db->num_rows($res_tim);
	//print $conf->global->MAIN_INFO_SIREN;
	//print '<br>';
	//extrae el pwd timbrado
	$obj_tim = $db->fetch_object($res_tim);
}
if(isset($obj_tim->password_timbrado_txt) && isset($conf->global->MAIN_INFO_SIREN)){
	//$status_ws = checkURL($wsadmin);
	$status_ws = 1;
	if($status_ws){

		$admincltdoli = new nusoap_client($wsadmin, 'wsdl');

		$rfc_emisor = isset($conf->global->MAIN_INFO_SIREN)?$conf->global->MAIN_INFO_SIREN:$emisor["emisorRFC"];
		$rfc_receptor = isset($rfc_receptor)?$rfc_receptor:$receptor["rfc"];
		$serie = isset($serie)?$serie:$header["serie"];
		$folio = isset($folio)?$folio:$header["folio"];
		$factura_emision = date("Y-m-d");
		$total = isset($total)?$total:$header["total"];


		$argumentos = array( "rfc_emisor"=>$rfc_emisor,
				"rfc_receptor"=>$rfc_receptor,
				"serie"=>$serie,
				"folio"=>$folio,
				"factura_emision"=>$factura_emision,
				"total"=>$total,
				"passwd_timbrado"=>$obj_tim->password_timbrado_txt
		);
		$result = $admincltdoli->call('searh_factura',$argumentos);

		//print '<br>';
		//print_r($result);
		//print '<br>';
		//print $result["rsp"];
		//print '<br>';
		// 4 bloqueo

		if($result["rsp"] == 4){
			print $result["xml"];
			print '<br>';
			print $result["cadenaOriginal"];
			print '<br>';
			print isset($result["version"])?$result["version"]:'1.0';
			print '<br>';
			print $result["selloCFD"];
			print '<br>';
			print $result["fechaTimbrado"];
			print '<br>';
			print $result["uuid"];
			print '<br>';
			print $result["certSAT"];
			print '<br>';
			print $result["selloSAT"];
			print '<br>';
			//print htmlentities(base64_decode($result["xml"]));
			print '<br>';
			//print $result["rsp"];
			//
		}

	}
}else{
	print 'Te falto algo!!';
}

if( $result["rsp"]==1 ){

	$separa_ftimbrado = explode("T",$result["fechaTimbrado"]);

	if(strtoupper($serie) == ''  ||  $folio == ''){
		$guion = "";
	}else{
		$guion = "-";
	}


	$file_xml = fopen ($conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/".$result["uuid"].".xml", "w");
	fwrite($file_xml,utf8_encode($result["xml"]));
	fclose($file_xml);
	$file_xml_str = $conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/".$result["uuid"].".xml";
	try{
		$the_xml = file_get_contents($file_xml_str);
		$sxe = new SimpleXMLElement($the_xml);
		$ns = $sxe->getNamespaces(true);
		$sxe->registerXPathNamespace('t', $ns['cfdi']);
		foreach ($sxe->xpath('//t:Comprobante') as $tfd) {
			$noCertificado = "{$tfd['noCertificado']}";
		}
	}catch(Exception $e){
		echo $e->getMessage()."<br>";
	}

	$result["version"]=isset($result["version"])?$result["version"]:"1.0";// AMM solucion provicional

	$insert = "
				INSERT INTO  ".MAIN_DB_PREFIX."cfdimx (
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
					'".$serie."',
					'".$folio."',
					'".$serie."-".$folio."',
					'".utf8_decode($result["xml"])."',
					'".utf8_decode($result["cadenaOriginal"])."',
					'".utf8_decode($result["version"])."',
					'".utf8_decode($result["selloCFD"])."',
					'".$result["fechaTimbrado"]."',
					'".$result["uuid"]."',
					'".utf8_decode($result["certSAT"])."',
					'".utf8_decode($result["selloSAT"])."',
					'".$noCertificado."',
					'0',
					'".$cuenta."',
					'".$facid."',
					'".$fecha_emison."',
					'".$hora_emision."',
					'".$separa_ftimbrado[0]."',
					'".$separa_ftimbrado[1]."',
					'".$modotimb."',
					'".$moneda."',
					'".$_SESSION['dol_entity']."'
				)";
	$rr = $db->query( $insert );

	$prmsnd["orden_compra"]=getOC( $facid, $db );
	dol_syslog('ORDENpr:REG:'.$prmsnd["orden_compra"]);
	$prmsnd["logosmall"]=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;

	$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE factura_serie = '".$serie."' AND factura_folio = '".$folio."' AND entity_id = " . $_SESSION['dol_entity']);
	if( $resql ){
		$num_llx_cfdimx = $db->num_rows($resql);
		$i=0;
		if($num_llx_cfdimx){
			while ($i < $num_llx_cfdimx){
				$obj = $db->fetch_object($resql);
				$prmsnd["version"] = $obj->version;
				$prmsnd["uuid"] = $obj->uuid;
				$prmsnd["cadena"] = $obj->cadena;
				$prmsnd["selloCFD"] = $obj->selloCFD;
				$prmsnd["selloSAT"] = $obj->sello;
				$prmsnd["fechaTimbrado"] = $obj->fechaTimbrado;
				$prmsnd["certificado"] = $obj->certificado;
				$prmsnd["certEmisor"] = $obj->certEmisor;
				$prmsnd["u4dig"] = $obj->u4dig;
				$prmsnd["fechaEmision"] = $obj->fecha_emision."T".$obj->hora_emision;
			 $prmsnd["coccds"] = "||".$prmsnd["version"]."|".$prmsnd["uuid"]."|".$prmsnd["fechaTimbrado"]."|".$prmsnd["selloCFD"]."|".$prmsnd["selloSAT"]."|".$prmsnd["selloSAT"]."||";
			 $i++;
			}
		}
	}

	include("generaPDF.php");

	print '<script>
				location.href="facture.php?facid='.$_REQUEST["facid"].'&cfdi_commit=1";
		   </script>';
}else{

	if($result["rsp"]!=""){
		$msg_cfdi_final = $result["rsp"]." - ".$result["msg"];
		$msg_cfdi_final .= " 5001 - No hubo respuesta para la petición, intente nuevamente.";
	}else{
		$msg_cfdi_final = " 5003 - No se encontro registro en el web services, intente timbrando nuevamente";
	}
	//borra control
	unset($sql_control);
	$sql_control = " DELETE FROM  ".MAIN_DB_PREFIX."cfdmix_control";
	$sql_control .= " WHERE factura_seriefolio = '".$serie."-".$folio."'";
	$res_control = $db->query( $sql_control );
}


?>