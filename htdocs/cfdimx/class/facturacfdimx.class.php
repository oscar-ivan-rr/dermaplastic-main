<?php
	//============================================================+
	// File name   : facturacfdimx.class.php
	// Begin       : 2021-11-18
	// Last Update : 2021-11-18
	//
	// Description : Generador de Timbrado CFDI 3.3
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//============================================================+

	// require_once('lib/nusoap/lib/nusoap.php');
	require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

	class FacturaCFDI{
		var $db;

		public $doli_version;

		public $id;
		public $entidad;
		public $emisor_id;
		public $emisor = array();
		public $cliente_id;
		public $cliente = array();
		public $errores;

		public $lista_domicilios = array();
		public $lista_facturas   = array();
		public $pagos            = array();
		public $nomina           = array();
		public $cce              = array();
		public $cartaporte       = array();

		const TYPE_REPLACEMENT = 1;
		const TYPE_CREDIT_NOTE = 2;
		const TYPE_DEPOSIT = 3;
		const TYPE_SITUATION = 5;

		public function __construct($db){
			global $conf;

		    $this->db = $db;
		    $this->doli_version = DOL_VERSION;
		    $this->entidad = $conf->entity;
		}

		//Funcion para Validar RFC
		public function validarRFC($rfc) {
			$rfc = str_replace("-", "", $rfc);
			$cuartoValor = substr($rfc, 3, 1);

			//RFC Persona Moral.
			if (ctype_digit($cuartoValor) && strlen($rfc) == 12) {
				$letras = substr($rfc, 0, 3);
				$numeros = substr($rfc, 3, 6);
				$homoclave = substr($rfc, 9, 3);
				$search = array("Ãƒâ€˜", "&");//caracteres admitidos por el SAT
				$replace = R;//se reemplaza en la busqueda para omitir el caracter
				$letras = str_replace($search, $replace, $letras);	//reemplazar

				if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
					//return 'El RFC '.$rfc.' es valido.';
					return 1;
				}
			}else{
				//RFC Persona Fisica.
				if (ctype_alpha($cuartoValor) && strlen($rfc) == 13) {
					$letras = substr($rfc, 0, 4);
					$numeros = substr($rfc, 4, 6);
					$homoclave = substr($rfc, 10, 3);
					if (ctype_alpha($letras) && ctype_digit($numeros) && ctype_alnum($homoclave)) {
						//return 'El RFC '.$rfc.' es valido.';
						return 1;
					}
				}else {
					//return 'El RFC '.$rfc.' no es valido.';
					$this->errores[] = "El RFC '".$rfc."' no es valido.";
					return 0;
				}
			}
		}

		function getEstatusCFDI($factura_estatus, $factura_id){
			$url = DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$factura_id;
			$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = ". $factura_id;
			$resql=$this->db->query($sql);
			if ($resql){
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num){
					while ($i < $num){
						$obj = $this->db->fetch_object($resql);
						if ($obj){
							return '<a href="facture.php?facid='.$factura_id.'">'. $obj->uuid .'</a>';
						}
						$i++;
					}
				}else{
					if($factura_estatus == 1 || $factura_estatus == 2){
						$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE rowid = " . $factura_id . " AND datef >  NOW() - INTERVAL 72 HOUR";
						$resql=$this->db->query($sql);
						if ($resql){
							$num = $this->db->num_rows($resql);
							$i = 0;
							if ($num){
								return '<b><a href="'.$url.'">Generar CFDI</a></b>';
							}else{
								return '<span class="badge badge-pill badge-warning">Factura fuera de Fecha de Timbrado</span>';
							}
						}
					}else{
						return '<span class="badge badge-pill badge-danger">Factura en Estado Borrador</span>';
					}
				}
			}else{
				return "N/A";
			}
		}

		public function obtener_catalogo($selected='', $htmlname='', $tipo_catalogo, $tipo_informacion = 0, $tipo_sql = '', $show_empty=0, $exclude='', $disabled=0, $include='', $enableonly='', $force_entity=0, $maxlength=0, $showstatus=0, $morefilter='', $show_every=0, $enableonlytext='', $morecss='', $noactive=0, $entrepot=0){

		    global $conf,$user,$langs;
		    $db = $this->db;

		    // If no preselected user defined, we take current user
		    if ((is_numeric($selected) && ($selected < -2 || empty($selected))) && empty($conf->global->SOCIETE_DISABLE_DEFAULT_SALESREPRESENTATIVE)) $selected=$user->id;
		    $excludeUsers=null;
		    $includeUsers=null;
		    // Permettre l’exclusion d’utilisateurs
		    if (is_array($exclude)) $excludeUsers = implode("','",$exclude);
		    // Permettre l’inclusion d’utilisateurs
		    if (is_array($include)) $includeUsers = implode("','",$include);
		    else if ($include == 'hierarchy')
		    {
		        // Build list includeUsers to have only hierarchy
		        $userid=$user->id;
		        $include=array();
		        if (empty($user->users) || ! is_array($user->users)) $user->get_full_tree();
		        foreach($user->users as $key => $val)
		        {
		            if (preg_match('/'.$userid.'/',$val['fullpath'])) $include[]=$val['id'];
		        }
		        $includeUsers = implode("','",$include);
		    }
		    $out='';

		    switch ($tipo_catalogo) {
		    	case 1:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_pais WHERE active = 1";
		    		break;
		    	case 2:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_regimen_f WHERE active = 1";
		    		break;
		    	case 3:
		    		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_husoh WHERE active = 1";
		    		break;

		    	default:
		    		$sql = "";
		    		break;
		    }

		    if($tipo_informacion == 1)
				// $sql .= " AND code='".$selected."'";

			$resql = "";
		   	$resql = $db->query($sql);
		   	// print $sql;

		    if ($resql)
		    {
		        $num = $db->num_rows($resql);
		        $i = 0;
		        $etiqueta = "";
		        if ($num)
		        {
		            // Enhance with select2
		            $nodatarole='';
		            if ($conf->use_javascript_ajax)
		            {
		                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		                $comboenhancement = ajax_combobox($htmlname);
		                $out.=$comboenhancement;
		                $nodatarole=($comboenhancement?' data-role="none"':'');
		            }

            		$out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';

		            if ($show_empty) $out.= '<option value="-1"'.((empty($selected) || $selected==-1)?' selected':'').'>&nbsp;</option>'."\n";
		            if ($show_every) $out.= '<option value="-2"'.(($selected==-2)?' selected':'').'>-- '.$langs->trans("Everybody").' --</option>'."\n";
		            $out.= '<option value="-1"></option>';
		            $i=0;
		            while ($rw = $db->fetch_object($resql)) {
		            	if($tipo_catalogo == 3){
		            		$valor    = $rw->label;
		            		$etiqueta = $rw->label;

		            		if ($selected == $valor) {
		            			$out.= '<option value="'.$valor.'" selected>';
		            		}else{
		            			$out.= '<option value="'.$valor.'">';
		            		}

	        	            $out.= $etiqueta."</option>";
		            	}else{
		            		$valor    = $rw->code;
		            		$etiqueta = $rw->code." - ".$rw->label;

		            		if ($selected == $valor) {
		            			$out.= '<option value="'.$valor.'" selected>';
		            		}else{
		            			$out.= '<option value="'.$valor.'">';
		            		}

	        	            $out.= $etiqueta."</option>";
		            	}
		            }
		        }else{
		            $nodatarole='';
		            if ($conf->use_javascript_ajax)
		            {
		                include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		                $comboenhancement = ajax_combobox($htmlname);
		                $out.=$comboenhancement;
		                $nodatarole=($comboenhancement?' data-role="none"':'');
		            }

		            $out.= '<select class="flat minwidth200'.($morecss?' '.$morecss:'').'" id="'.$htmlname.'" name="'.$htmlname.'"'.($disabled?' disabled':'').$nodatarole.'>';
		        }
		        $out.= '</select>';

		        // if($tipo_informacion == 1)
		        // 	$out = $etiqueta;
		    }else{
		        dol_print_error($db);
		    }
		    return $out;
		}

		public function getfacturasTabCliente($cliente_id){
			$sql = "";

			if((int)$this->doli_version >= 14){
				$sql = "
					SELECT
						f.rowid as facid, f.ref, f.type, f.total_ht AS total, f.total_ttc, f.datef as df,f.datec as dc,
			       		f.paye as paye, f.fk_statut as statut, s.nom, s.rowid as socid
					FROM
						".MAIN_DB_PREFIX."societe as s,
						".MAIN_DB_PREFIX."facture as f
					WHERE
						s.rowid=".$cliente_id." AND f.fk_soc = s.rowid AND f.entity = ".$this->entidad."
					GROUP BY
						f.rowid, f.ref, f.type, total, f.total_ttc, f.datef,f.datec, f.paye, f.fk_statut, s.nom, s.rowid
					ORDER BY
						f.datef DESC,
						f.datec DESC";
			}else{
				$sql = "
					SELECT
						f.rowid as facid, f.ref, f.type, f.amount, f.total, f.total_ttc, f.datef as df,f.datec as dc,
			       		f.paye as paye, f.fk_statut as statut, s.nom, s.rowid as socid
					FROM
						".MAIN_DB_PREFIX."societe as s,
						".MAIN_DB_PREFIX."facture as f
					WHERE
						s.rowid=".$cliente_id." AND f.fk_soc = s.rowid AND f.entity = ".$this->entidad."
					GROUP BY
						f.rowid, f.ref, f.type, f.amount, f.total, f.total_ttc, f.datef,f.datec, f.paye, f.fk_statut, s.nom, s.rowid
					ORDER BY
						f.datef DESC,
						f.datec DESC
					";
			}

			$res_sql      = $this->db->query($sql);
			$num_facturas = $this->db->num_rows($res_sql);
			$this->lista_facturas = null;

			if($num_facturas > 0){
				$i = 0 ;

				while ($i < $num_facturas){
					$obj_factura = $this->db->fetch_object($res_sql);

					$factura          = new Facture($this->db);
					$factura->fetch($obj_factura->facid);
					// $factura_url      = '<b>'.$factura->getNomUrl(1).'</b>';
					$factura_url      = $factura->getNomUrl(1);
					$factura_fecha    =	dol_print_date($this->db->jdate($obj_factura->df),'day');
					$factura_total    = "$ ".number_format($obj_factura->total_ttc, 2, ".", ",");
					$factura_url_cfdi = $this->getEstatusCFDI($factura->statut, $factura->id);
					$factura_estatus  = $factura->LibStatut($factura->paye,$factura->statut,5,'');

					$this->lista_facturas[] =
								array(
										'factura' => $factura_url,
										'fecha'   => $factura_fecha,
										'total'   => $factura_total,
										'cfdi'    => $factura_url_cfdi,
										'estatus' => $factura_estatus
									);
					$i++;
				}
			}

			$this->db->free($res_sql);
			return $num_facturas;
		}

		public function getDomicilioFiscalCliente($cliente_id, $domicilio_id){
			$datos_cliente    = null;

			$rowid             = -1;
			$fk_soc            = $cliente_id;
		    $entity_id         = $this->entidad;
		    $rfc               = null;
		    $nombre            = null;
		    $regimenfiscal     = null;
		    $numregidtrib      = null;
		    $direccion         = null;
		    $cp                = null;
		    $municipio         = null;
		    $estado            = null;
		    $pais              = null;
		    $estatus           = null;
		    $residencia_fiscal = null;
			$calle             = null;
			$clave_mpio        = null;
			$noint             = null;
			$noext             = null;
			$clave_col         = null;

		    //Se busca si tiene domicilio guardado
		    $sql_domicilio  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_domicilio_fiscal_receptor";
		    $sql_domicilio .= " WHERE fk_soc = ".$cliente_id." AND entity_id = ".$this->entidad;

		    // print $sql_domicilio;

		    if($domicilio_id > 0){
		    	$sql_domicilio .= " AND rowid = ".$domicilio_id;
		    }

		    $sql_domicilio .= " LIMIT 1";

		    $res_sql_domicilio = $this->db->query($sql_domicilio);
		    $num_domicilio     = $this->db->num_rows($res_sql_domicilio);

		    if($num_domicilio > 0){
		    	$obj_domicilio = $this->db->fetch_object($res_sql_domicilio);

		    	$rowid             = $obj_domicilio->rowid;
				$fk_soc            = $obj_domicilio->fk_soc;
			    $entity_id         = $obj_domicilio->entity_id;
			    $rfc               = $obj_domicilio->rfc;
			    $nombre            = $obj_domicilio->nombre;
			    $regimenfiscal     = $obj_domicilio->regimenfiscal;
			    $numregidtrib      = $obj_domicilio->numregidtrib;
			    $direccion         = $obj_domicilio->direccion;
			    $cp                = $obj_domicilio->cp;
			    $municipio         = $obj_domicilio->municipio;
			    $estado            = $obj_domicilio->estado;
			    $pais              = $obj_domicilio->pais;
			    $estatus           = $obj_domicilio->estatus;
			    $residencia_fiscal = $obj_domicilio->residencia_fiscal;
				$calle             = $obj_domicilio->calle;
				$clave_mpio        = $obj_domicilio->clave_mpio;
				$noint             = $obj_domicilio->noint;
				$noext             = $obj_domicilio->noext;
				$clave_col         = $obj_domicilio->clave_col;
		    }else{
		    	$cliente = new Societe($this->db);
		    	$cliente->fetch($cliente_id);

		    	$nombre = $cliente->nom;
		    	$rfc = $cliente->idprof1;
		    	$direccion = $cliente->address;
		    	$cp = $cliente->zip;
		    	$estatus = 1;
		    }

		    $datos_cliente = array(
		    				'rowid'             => $rowid,
		    				'fk_soc'            => $fk_soc,
						    'entity_id'         => $entity_id,
						    'rfc'               => $rfc,
						    'nombre'            => $nombre,
						    'regimenfiscal'     => $regimenfiscal,
						    'numregidtrib'      => $numregidtrib,
						    'direccion'         => $direccion,
						    'cp'                => $cp,
						    'municipio'         => $municipio,
						    'estado'            => $estado,
						    'pais'              => $pais,
						    'estatus'      	    => $estatus,
						    'residencia_fiscal' => $residencia_fiscal,
							'calle'             => $calle,
							'clave_mpio'        => $clave_mpio,
							'noint'             => $noint,
							'noext'             => $noext,
							'clave_col'         => $clave_col
		    			);

		    return $datos_cliente;
		}

		public function getDomiciliosFiscalesCliente($cliente_id){
			$num_domicilios = 0;

			//Se busca si tiene domicilios guardados
		    $sql_domicilios  = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_domicilio_fiscal_receptor";
		    $sql_domicilios .= " WHERE fk_soc = ".$cliente_id." AND entity_id = ".$this->entidad;
		    // $sql_domicilio .= " LIMIT 1";

		    $res_sql_domicilios = $this->db->query($sql_domicilios);
		    $num_domicilios     = $this->db->num_rows($res_sql_domicilios);

		    if($num_domicilios > 0){
		    	while ($domicilio = $this->db->fetch_object($res_sql_domicilios)) {
		    		$this->lista_domicilios[] = $domicilio;
		    	}
		    }

			return $num_domicilios;
		}

		public function guardarDomicilioFiscal(){
			$this->errores = null;
			$num_errores   = 0;

			if($this->emisor["rfc"] == '' || $this->emisor["rfc"] == null){
				$this->errores[] = 'El Campo <u><b>RFC</b></u> esta vacio y es obligatorio.';
				$num_errores++;
			}else{
				$this->emisor["rfc"] = mb_strtoupper($this->emisor["rfc"]);
			}

			if($this->emisor["nombre"] == '' || $this->emisor["nombre"] == null){
				$this->errores[] = 'El Campo <u><b>Nombre</b></u> esta vacio y es obligatorio.';
				$num_errores++;
			}else{
				$this->emisor["nombre"] = mb_strtoupper($this->emisor["nombre"]);
			}

			if($this->emisor["cp"] == '' || $this->emisor["cp"] == null){
				$this->errores[] = 'El Campo <u><b>Domicilio Fiscal</b></u> esta vacio y es obligatorio.';
				$num_errores++;
			}

			if($this->emisor["regimenfiscal"] == -1 || $this->emisor["regimenfiscal"] == '' || $this->emisor["regimenfiscal"] == null){
				$this->errores[] = 'El Campo <u><b>Régimen Fiscal</b></u> esta vacio y es obligatorio.';
				$num_errores++;
			}

			if(strcmp($this->emisor["rfc"], "XEXX010101000") != 0){
				$this->emisor["residencia_fiscal"] = '';
				$this->emisor["numregidtrib"] = '';
			}

			if($this->emisor["residencia_fiscal"] != '' && $this->emisor["numregidtrib"] == ''){
				$this->emisor["residencia_fiscal"] = '';
				$this->emisor["numregidtrib"] = '';
			}

			if($num_errores == 0 && $this->emisor["rowid"] == -1){
				$sql = "";
				$sql .= "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_domicilio_fiscal_receptor";
				$sql .= " (";
				$sql .= "   fk_soc, entity_id, rfc, nombre, regimenfiscal, numregidtrib, direccion, cp, municipio, estado, pais, estatus,";
				$sql .= "   residencia_fiscal, calle, clave_mpio, noint, noext, clave_col";
				$sql .= " )";
				$sql .= " VALUES";
				$sql .= " (";
					$sql .= "'".trim($this->emisor["fk_soc"] != '' ? $this->emisor["fk_soc"] : -1)."',";
					$sql .= "'".trim($this->emisor["entity_id"] != '' ? $this->emisor["entity_id"] : -1)."',";
					$sql .= "'".trim($this->emisor["rfc"] != '' ? $this->db->escape($this->emisor["rfc"]) : null)."',";
					$sql .= "'".trim($this->emisor["nombre"] != '' ? $this->db->escape($this->emisor["nombre"]) :  null)."',";
					$sql .= "'".trim($this->emisor["regimenfiscal"] != '' ? $this->emisor["regimenfiscal"] :  null)."',";
					$sql .= "'".trim($this->emisor["numregidtrib"] != '' ? $this->emisor["numregidtrib"] :  null)."',";
					$sql .= "'".trim($this->emisor["direccion"] != '' ? $this->db->escape($this->emisor["direccion"]) :  null)."',";
					$sql .= "'".trim($this->emisor["cp"] != '' ? $this->emisor["cp"] : null)."',";
					$sql .= "'".trim($this->emisor["municipio"] != '' ? $this->db->escape($this->emisor["municipio"]) : null)."',";
					$sql .= "'".trim($this->emisor["estado"] != '' ? $this->db->escape($this->emisor["estado"]) : null)."',";
					$sql .= "'".trim($this->emisor["pais"] != '' ? $this->emisor["pais"] : null)."',";
					$sql .= "'".trim($this->emisor["estatus"] != '' ? $this->emisor["estatus"] : 0)."',";
					$sql .= "'".trim($this->emisor["residencia_fiscal"] != '' ? $this->emisor["residencia_fiscal"] : null)."',";
					$sql .= "'".trim($this->emisor["calle"] != '' ? $this->db->escape($this->emisor["calle"]) : null)."',";
					$sql .= "'".trim($this->emisor["clave_mpio"] != '' ? $this->emisor["clave_mpio"] : null)."',";
					$sql .= "'".trim($this->emisor["noint"] != '' ? $this->emisor["noint"] : null)."',";
					$sql .= "'".trim($this->emisor["noext"] != '' ? $this->emisor["noext"] : null)."',";
					$sql .= "'".trim($this->emisor["clave_col"] != '' ? $this->emisor["clave_col"] : null)."'";
				$sql .= " );";

				$res_sql = $this->db->query($sql);

				if($res_sql){
					$num_errores = 0;
				}else{
					$num_errores++;
					$this->errores[] = dol_print_error($this->db);
				}
			}else{
				if($num_errores == 0 && $this->emisor["rowid"] > 0){
					$sql = "";
					$sql .= "UPDATE ".MAIN_DB_PREFIX."cfdimx_domicilio_fiscal_receptor";
					$sql .= "   SET ";
						$sql .= " fk_soc = '".trim($this->emisor["fk_soc"] != '' ? $this->emisor["fk_soc"] : -1)."',";
						$sql .= " entity_id = '".trim($this->emisor["entity_id"] != '' ? $this->emisor["entity_id"] : -1)."',";
						$sql .= " rfc = '".trim($this->emisor["rfc"] != '' ? $this->db->escape($this->emisor["rfc"]) : null)."',";
						$sql .= " nombre = '".trim($this->emisor["nombre"] != '' ? $this->db->escape($this->emisor["nombre"]) :  null)."',";
						$sql .= " regimenfiscal = '".trim($this->emisor["regimenfiscal"] != '' ? $this->emisor["regimenfiscal"] :  null)."',";
						$sql .= " numregidtrib = '".trim($this->emisor["numregidtrib"] != '' ? $this->emisor["numregidtrib"] :  null)."',";
						$sql .= " direccion = '".trim($this->emisor["direccion"] != '' ? $this->db->escape($this->emisor["direccion"]) :  null)."',";
						$sql .= " cp = '".trim($this->emisor["cp"] != '' ? $this->emisor["cp"] : null)."',";
						$sql .= " municipio = '".trim($this->emisor["municipio"] != '' ? $this->db->escape($this->emisor["municipio"]) : null)."',";
						$sql .= " estado = '".trim($this->emisor["estado"] != '' ? $this->db->escape($this->emisor["estado"]) : null)."',";
						$sql .= " pais = '".trim($this->emisor["pais"] != '' ? $this->emisor["pais"] : null)."',";
						$sql .= " estatus = '".trim($this->emisor["estatus"] != '' ? $this->emisor["estatus"] : 0)."',";
						$sql .= " residencia_fiscal = '".trim($this->emisor["residencia_fiscal"] != '' ? $this->emisor["residencia_fiscal"] : null)."',";
						$sql .= " calle = '".trim($this->emisor["calle"] != '' ? $this->db->escape($this->emisor["calle"]) : null)."',";
						$sql .= " clave_mpio = '".trim($this->emisor["clave_mpio"] != '' ? $this->emisor["clave_mpio"] : null)."',";
						$sql .= " noint = '".trim($this->emisor["noint"] != '' ? $this->emisor["noint"] : null)."',";
						$sql .= " noext = '".trim($this->emisor["noext"] != '' ? $this->emisor["noext"] : null)."',";
						$sql .= " clave_col = '".trim($this->emisor["clave_col"] != '' ? $this->emisor["clave_col"] : null)."'";
					$sql .= " WHERE rowid = ".$this->emisor["rowid"].";";

					$res_sql = $this->db->query($sql);

					if($res_sql){
						$num_errores = 0;
					}else{
						$num_errores++;
						$this->errores[] = dol_print_error($this->db);
					}
				}
			}

			return $num_errores;
		}

		public function validarVersionDoli($version_min, $version_max){
			$version_actual = explode(".", DOL_VERSION);
			$version_minima = explode(".", $version_min);
			$version_maxima = explode(".", $version_max);

			$mensaje        = "";

			if($version_actual[0] < $version_minima[0]){
				$mensaje .= "El módulo CFDIMX no es compatible con tu vesión (".DOL_VERSION.") de Dolibarr, la versión mínima requerida es ".$version_min.", si se utiliza el módulo puede que no funcione de manera correcta.";
			}else{
				if($version_actual[0] > $version_maxima[0]){
					$mensaje .= "El módulo CFDIMX no es compatible con tu vesión (".DOL_VERSION.") de Dolibarr, la versión máxima requerida es ".$version_maxima.", si se utiliza el módulo puede que no funcione de manera correcta.";
				}else{
					if($version_actual[0] == $version_maxima[0]){
						if($version_actual[1] > $version_maxima[1]){
							$mensaje .= "El módulo CFDIMX no es compatible con tu vesión (".DOL_VERSION.") de Dolibarr, la versión máxima requerida es ".$version_maxima.", si se utiliza el módulo puede que no funcione de manera correcta.";
						}

						if($version_actual[2] > $version_maxima[2]){
							$mensaje .= "El módulo CFDIMX no es compatible con tu vesión (".DOL_VERSION.") de Dolibarr, la versión máxima requerida es ".$version_maxima.", si se utiliza el módulo puede que no funcione de manera correcta.";
						}
					}
				}
			}

			return $mensaje;
		}

		function getLinkGeneraCFDI($facstatut, $factura_id){
			global $db;
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

	}
?>