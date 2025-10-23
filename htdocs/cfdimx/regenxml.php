<?php
require('../main.inc.php');
require('../conf/conf.php');
include('lib/nusoap/lib/nusoap.php');
$bandx = $_REQUEST['band'];

if ($bandx==0) {
	print ' <html>
			<head> <title>Regenera PDF</title>
			<head>
			</head>
			<body">
			<center>
			<br/><br/>
			<form method="GET" action="">
			<table>
			<tr><td collspan="2">Desea regenerar el XML y reemplazarlo?</td></tr>
			<tr>
			<td>
			<input type="hidden" id="band" name="band" value="1">
			<input type="hidden" id="facidx" name="facidx" value="'.$facid.'">
            <input type="hidden" id="uuid" name="uuid" value="'.$_REQUEST['uuid'].'">					
					<input type="submit" id="acep" name="acep" value="Aceptar">
					</td>
					<td>
					<input type="button" onClick="window.close();" id="cancela" name="cancela" value="cancelar"> </td>
					</tr>
					</table>
					</form>
					</center>
		   </body>
					</html> ';
}else if ($bandx==1){
	
	
	$sql  = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE uuid = '".$_REQUEST['uuid']."'";
	$sql .= "AND entity_id = " . $_SESSION['dol_entity'];

	$sql = "SELECT cf.factura_seriefolio,cf.fecha_emision,fa.total_ttc,so.siren FROM  ".MAIN_DB_PREFIX."cfdimx cf
			INNER JOIN  ".MAIN_DB_PREFIX."facture fa on fa.rowid = cf.fk_facture
			INNER JOIN  ".MAIN_DB_PREFIX."societe so on so.rowid = fa.fk_soc
			WHERE cf.uuid = '".$_REQUEST['uuid']."'
					AND cf.entity_id = " . $_SESSION['dol_entity'];

	$resql=$db->query($sql);
	if($resql){
		$num = $db->num_rows($resql);
		
		
		if($num){

			$obj = $db->fetch_object($resql);
			$guion = '-';
			$factura_seriefolio = explode($guion, $obj->factura_seriefolio);
			$serie = $factura_seriefolio[0];
			$folio = $factura_seriefolio[1];

			$factura_emision = $obj->fecha_emision;
			$rfc_receptor = $obj->siren;//soc
			$total = $obj->total_ttc;//
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
					//$rfc_receptor = isset($rfc_receptor)?$rfc_receptor:$receptor["rfc"];
					//$serie = isset($serie)?$serie:$header["serie"];
					//$folio = isset($folio)?$folio:$header["folio"];
					//$factura_emision = date("Y-m-d");//Ejem: 2014-01-27
					//$total = isset($total)?$total:$header["total"];

					$total=str_replace(",", "", number_format($total,2));
					$argumentos = array( "rfc_emisor"=>$rfc_emisor,
							"rfc_receptor"=>$rfc_receptor,
							"serie"=>$serie,
							"folio"=>$folio,
							"factura_emision"=>$factura_emision,
							"total"=>$total,
							"passwd_timbrado"=>$obj_tim->password_timbrado_txt
					);
					print $rfc_emisor."<br>";
					print $rfc_receptor."<br>";
					print $serie."<br>";
					print $folio."<br>";
					print $factura_emision."<br>";
					print $total."<br>";
					print $obj_tim->password_timbrado_txt."<br>";
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
				fwrite($file_xml,$result["xml"]);
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
				print '<script> alert("Se ha regenerado el archivo XML con Exito ..."); window.opener.location.reload(); window.close(); </script>';
			}else{
				print '<script> alert("No existe el xml ..."); window.opener.location.reload(); window.close(); </script>';
			}
		}else{
			print '<script> alert("No se estrajo nada de la tabla cfdimx ..."); window.opener.location.reload(); window.close(); </script>';
		}
	}else{
		print '<script> alert("Error al extraer datos ..."); window.opener.location.reload(); window.close(); </script>';
	}
}
?>