<?php

// $wscfdi = $conf->global->MAIN_MODULE_CFDIMX_WS;

// function cfdimx_docrel_prepare_head()
// {
//     $h = 0;
//     $head = array();

//     $head[$h][0] = DOL_URL_ROOT.'/cfdimx/complementos_cfdimx.php?mod=cfdi_rel&facid='.GETPOST('facid');
//     $head[$h][1] = "<strong>CFDIS Relacionados</strong>";
//     $head[$h][2] = "uno";
//     $h++;

//     $head[$h][0] = DOL_URL_ROOT.'/cfdimx/complementos_cfdimx.php?mod=cce&facid='.GETPOST('facid');
//     $head[$h][1] = "<strong>Comercio Exterior</strong>";
//     $head[$h][2] = "dos";
//     $h++;

//     $head[$h][0] = DOL_URL_ROOT.'/cfdimx/complementos_cfdimx.php?mod=carta_porte&facid='.GETPOST('facid');
//     $head[$h][1] = "<strong>Carta Porte</strong>";
//     $head[$h][2] = "tres";
//     $h++;

//     return $head;
// }

// function get_data_receptor( $db, $socid ){
// 	$data = array();
// 	$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."societe WHERE rowid = " . $socid;
// 	$resql=$db->query($sql);
// 	if ($resql){
// 		$num = $db->num_rows($resql);
// 		$i = 0;
// 		if ($num){
// 		    while ($i < $num){
// 				$obj = $db->fetch_object($resql);
// 				if ($obj){
// 					$data["id"] = $obj->rowid;
// 					$data["nom"] = $obj->nom;
// 					$data["rfc"] = $obj->siren;
// 				}
// 				$i++;
// 			}
// 		}
// 	}
// 	return $data;
// }

function getLinkGeneraCFDI($facstatut, $factura_id, $db){
	
	$url = DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$factura_id;
	$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = ". $factura_id;
	$resql=$db->query($sql);
	if ($resql){
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num){
			while ($i < $num){
				$obj = $db->fetch_object($resql);
				if ($obj){
					return '<a href="facture.php?facid='.$factura_id.'">'. $obj->uuid .'</a>';
				}
				$i++;
			}
		}else{
			if( $facstatut==1 || $facstatut==2 ){
				$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE rowid = " . $factura_id . " AND datef >  NOW() - INTERVAL 72 HOUR";
				$resql=$db->query($sql);
				if ($resql){
					$num = $db->num_rows($resql);
					$i = 0;
					if ($num){ 
						return '<a href="'.$url.'">Generar CFDI</a>'; 
					}else{ 
						return "<b>Fuera de fecha de timbrado</b>"; 
					}
				}
			}else{
				return "N/A";
			}
		}
	}else{
		return "N/A";
	}
}
?>
