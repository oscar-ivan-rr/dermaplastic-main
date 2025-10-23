<?php

/**
 * @author lolkittens
 * @copyright 2020
 */

class importProductHelper
{
	static $uploadedFile = null;
	static $csv = null;
	static $headers = null;
	static $properties = array();
	static $relations = array(
            'base' => array (
                 'ref'					=> 'ref lion'
                ,'old_ref'				=> 'ref sae'
                ,'label'				=> 'descripción (etiqueta)'
                ,'seuil_stock_alerte'	=> 'stock mínimo'
                ,'barcode'				=> 'codigo de barras'
                ,'desiredstock_principal'=> 'stock máximo (stock deseado) matriz'
                ,'desiredstock_gpe'		=> 'stock máximo (stock deseado) gpe'
                ,'wh1percent'			=> 'porcentaje de stock matriz'
                ,'wh2percent'			=> 'porcentaje de stock gpe'
				,'wh1_limit'			=> 'limite de stock matriz'
                ,'wh2_limit'			=> 'limite de stock gpe'
                ,'location_matriz'		=> 'ubicación matriz zac'
				,'location_gpe'			=> 'ubicación almacen gpe'
				,'location_matriz2'		=> 'ubicación matriz zac2'
                ,'location_gpe2'		=> 'ubicación almacen gpe2'
                ,'unit_entrada'			=> 'unidad de entrada'
                ,'unit_salida'			=> 'unidad de salida'
                ,'date_compra'			=> 'fecha de última compra'
                ,'date_venta'			=> 'fecha de última venta'
				,'price'				=> 'precio de venta (con iva)'
				,'rotation'  			=> 'rotacion'
                ,'currency'				=> 'moneda' // Por verificar
				,'gain'					=> 'ganancia'
            ), // 17 campos
            'extra' => array (
                 'umed'					=> 'clave unidad (sat)'
                ,'claveprodserv'		=> 'clave sat'
            ), // 2 campos
            'iteractions'	=> array(
                 'ref_fourn_1'			=> 'clave alterna prov 1'
                ,'rfc_fourn_1'			=> 'proveedor 1 rfc'
                ,'delivery_time_days_1'	=> 'tiempo de surtido prov 1'
                ,'ref_fourn_2'			=> 'clave alterna prov 2'
                ,'rfc_fourn_2'			=> 'proveedor 2 rfc'
                ,'delivery_time_days_2'	=> 'tiempo de surtido prov 2'
                ,'ref_fourn_3'			=> 'clave alterna prov 3'
                ,'rfc_fourn_3'			=> 'proveedor 3 rfc'
                ,'delivery_time_days_3'	=> 'tiempo de surtido prov 3'
                ,'price'				=> 'costo sin iva'
                ,'packing'				=> 'unidad de empaque (empaque)'
			), // 11 campos
			'categs'	=> array(
                 'parent'				=> 'nueva linea general'
                ,'child'				=> 'nueva linea tipo producto'
                ,'lone'					=> 'línea marca'
			), // 3 campos
			'stocks'		=> array(
                 'stock_m'				=> 'existencias matriz'
                ,'stock_g'				=> 'existencias gpe'
			) // 2 campos
			// total 35 campos (incluye moneda)
        );

	
	static function saveFile()
	{
		$dPath = realpath(DOL_DOCUMENT_ROOT.'/../documents');
		if (!file_exists($dPath.DIRECTORY_SEPARATOR.'produit'))
		{
			mkdir($dPath.DIRECTORY_SEPARATOR.'produit');
		}
		$dPath = $dPath.DIRECTORY_SEPARATOR.'produit';
		if (!file_exists($dPath.DIRECTORY_SEPARATOR.'temp'))
		{
			mkdir($dPath.DIRECTORY_SEPARATOR.'temp');
		}
		$dPath = $dPath.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR.uniqid('product_import_').'.csv';
		
		if (move_uploaded_file($_FILES['file_xsl']['tmp_name'], $dPath)) 
		{
			self::$uploadedFile = $dPath;
			self::$csv = fopen(self::$uploadedFile,'r+');
			self::loadHeaders();	
		} 
		else 
		{
			die('Error al cargar el archivo CSV');
		}
	}
	
	static function loadHeaders()
	{
		rewind(self::$csv);
		self::$headers = self::getCsvRow(true);
		self::setProperties();
	}
	
	static function getCsvRow($lowercase = false)
	{
		$row = fgetcsv(self::$csv);
		
		foreach($row as $k => $v)
		{
			if (!mb_detect_encoding($row[$k], 'UTF-8', true))
			{
				$row[$k] = iconv('WINDOWS-1252', 'UTF-8', $row[$k]);
			}
			if ($lowercase)
			{
				$row[$k] = mb_strtolower($row[$k],'UTF-8');
			}
			$row[$k] = trim($row[$k]);
		}
		return $row;
	}
	
	static function setProperties()
	{
		foreach (self::$relations as $base => $base_array)
		{
			for ($i=0, $n=count(self::$headers); $i<$n; $i++)
			{
				$mkey = array_search(self::$headers[$i],$base_array);
				if (strlen($mkey))
				{
					self::$properties[$base][$mkey] = $i;
				}
			}
		}
		if (!isset(self::$properties['base']['ref']))
		{
			die ('No se econtró la columna "Ref Lion"');
		}
	}
	
	static function getProductId($row)
	{
		global $db;
		$ref = $db->escape($row[self::$properties['base']['ref']]);
		$old_ref = $db->escape($row[self::$properties['base']['old_ref']]);
		$sql =	 'SELECT rowid '."\r\n"
				.'FROM llx_product '."\r\n"
				.'WHERE `ref`=\''.$ref.'\' '."\r\n"
				;
		if ($old_ref)
		{
				$sql .='  OR `ref`=\''.$old_ref.'\' ';
		}
		
		$return = 0;
		if ($res = $db->query($sql))
		{
			if ($db->num_rows($res))
			{
				$return = $db->fetch_object($res)->rowid;
			}
		}
		else
		{
			dol_print_error($db);
		}
		return $return;
	}
	
	static function createProduct($row)
	{
		global $db;
		$fields = self::setDbFields($row);
		$cols = array();
		$vals = array();
		if (!isset($fields['label']) || !strlen($fields['label']))
		{
			$fields['label'] = 'Importado sin descripción';
		}
		
		foreach ($fields as $k => $v)
		{
			$cols[] = '`'.$k.'`';
			$vals[] = '\''.$db->escape($v).'\'';
		}
		if (!count($cols))
		{
			return false;
		}
		$sql = 'INSERT INTO `llx_product` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')';
		if (!$res = $db->query($sql))
		{
			return false;
		}
		
		if (isset($fields['price_ttc']))
		{
			self::createProductPrice($db->last_insert_id('llx_product'),$fields);
		}
		
		//self::insertProductExtra($db->last_insert_id('llx_product'),$row);
		
		return true;
	}
	
	static function updateProduct($id,$row)
	{
		global $db;
		$fields = self::setDbFields($row);
		if (!count($fields))
		{
			return false;
		}
		$cols = array();
		$vals = array();
		foreach ($fields as $k => $v)
		{
			$cols[] = '`'.$k.'`';
			$vals[] = '\''.$db->escape($v).'\'';
		}
		$sql = 'UPDATE `llx_product` SET ';
		$pairs = array();
		for($i=0,$n=count($cols);$i<$n;$i++)
		{
			$pairs[] = $cols[$i].'='.$vals[$i];
		}
		$sql .= implode(',',$pairs);
		$sql .= ' WHERE rowid='.$id;
		
		if (!$res = $db->query($sql))
		{
			return false;
		}
		if (isset($fields['price_ttc']))
		{
			self::createProductPrice($id,$fields);
		}
		self::insertProductExtra($id,$row);
		return true;
	}
	
	static function createProductPrice($id,$fields)
	{
		global $db,$user;
		$sql =	 'INSERT INTO `llx_product_price` '."\r\n"
				.'('."\r\n"
				.' `entity`'."\r\n"
				.',`fk_product`'."\r\n"
				.',`date_price`'."\r\n"
				.',`price_level`'."\r\n"
				.',`price`'."\r\n"
				.',`price_ttc`'."\r\n"
				.',`price_base_type`'."\r\n"
				.',`tva_tx`'."\r\n"
				.',`tosell`'."\r\n"
				.',`price_by_qty`'."\r\n"
				.',`price_min`'."\r\n"
				.',`price_min_ttc`'."\r\n"
				.',`fk_user_author`'."\r\n"
				.')'."\r\n"
				.'VALUES'
				.'('."\r\n"
				.' 1'."\r\n"
				.','.$id.''."\r\n"
				.',\''.date('Y-m-d H:i:s').'\''."\r\n"
				.',1'."\r\n"
				.','.$fields['price'].''."\r\n"
				.','.$fields['price_ttc'].''."\r\n"
				.',\'TTC\''."\r\n"
				.',16.000'."\r\n"
				.',1'."\r\n"
				.',0'."\r\n"
				.',0'."\r\n"
				.',0'."\r\n"
				.','.$user->id.''."\r\n"
				.')'."\r\n"
				;
		if (!$db->query($sql))
		{
			dol_print_error($db);
			die();
		}
	}
	
	static function setDbFields($row)
	{
		$fields = array();
		foreach(self::$properties['base'] as $k => $v)
		{
			if (strlen(trim($row[$v])))
			{
				$fields[$k] = trim($row[$v]);
			}
		}
		
		if (isset($fields['date_compra']) && strpos($fields['date_compra'],'/'))
		{
			$parts = explode('/',$fields['date_compra']);
			$fields['date_compra'] = date('Y-m-d H:i:s',mktime(12,0,0,$parts[1],$parts[0],$parts[2]));
		}
		if (isset($fields['date_venta']) && strpos($fields['date_venta'],'/'))
		{
			$parts = explode('/',$fields['date_venta']);
			$fields['date_venta'] = date('Y-m-d H:i:s',mktime(12,0,0,$parts[1],$parts[0],$parts[2]));
		}
		if (isset($fields['price']))
		{
			$fields['price'] = str_replace('$','',$fields['price']);
			$fields['price'] = str_replace(',','',$fields['price']); 
			if (is_numeric($fields['price']))
			{
				$fields['price_ttc'] = $fields['price'];
				$fields['price'] = $fields['price'] / 1.16;
				$fields['price_base_type'] = 'TTC';
				$fields['tva_tx'] = '16.0';
				$fields['base_price'] = $fields['price'];
				$fields['base_price_ttc'] = $fields['price_ttc'];  
			}
		}
		if (isset($fields['unit_salida']))
		{
			$fields['unit_salida'] = self::getUnitCode($fields['unit_salida']);
		}
		if (isset($fields['unit_entrada']))
		{
			$fields['unit_entrada'] = self::getUnitCode($fields['unit_entrada']);
		}
		
		if (isset($fields['currency']))
		{
			unset($fields['currency']);
		}
		//if (isset($fields['']))
		
		
		return $fields;
	}
	
	static function checkClaveSat($clave)
	{
		if (!strlen(trim($clave)))
		{
			return;
		}
		global $db;
		static $claves = array();
		if (!count($claves))
		{
			$query = 'SELECT `code`,`label` FROM `llx_c_cfdimx_clave_prodserv`';
			if ($res = $db->query($query))
			{
				while($row = $db->fetch_object($res))
				{
					$claves[$row->code] = $row->label;
				}
			}
		}
		if (!isset($claves[$clave]))
		{
			$sql = 'INSERT INTO `llx_c_cfdimx_clave_prodserv` (`code`,`label`,`active`)'
					."VALUES ('{$clave}','{$clave} - Por definir',1)"
					;
			if (!$db->query($sql))
			{
				//dol_print_error($db));
			}
			else
			{
				$claves[$clave] = "{$clave} - Por definir";
			}
		}
	}
	
	static function getUnitCode($unit)
	{
    	global $db,$user; //$this
    	static $units = array();
    	if (!in_array($unit,$units))
    	{
    		if (!class_exists('CUnits'))
    		{
				require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
    		}
			$measuringUnits = new CUnits($db);
    		$mu = $measuringUnits->fetch('','',substr(dol_strtoupper($unit),0,5),'uni');
    		if ($mu > 0)
    		{
	    		if (!($measuringUnits->id))
				{
					$sql = 'SELECT max(scale) as `max` FROM llx_c_units WHERE unit_type = \'uni\'';
					if ($mures = $db->query($sql))
					{
						if ($nx = $db->fetch_object($mures))
						{
							$max = intval($nx->max) +1;
						}
						else
						{
							$max = '1';
						}
					}
    				$measuringUnits->code = substr(str_replace(' ','',$unit),0,3);
	    			$measuringUnits->label = dol_strtoupper($unit);
	    			$measuringUnits->short_label = dol_strtoupper($unit);
	    			$measuringUnits->unit_type = 'uni';
	    			$measuringUnits->scale = $max;
	    			$measuringUnits->active = 1;
	    			
	    			if ($measuringUnits->create($user) > 0)
	    			{
    					$units[$max] = $unit;
    				}
    				else
    				{
    					//dol_print_error($db);
    					//die();
    				}
    			}
    			else
    			{
    				$units[$measuringUnits->scale] = $unit;
    			}
    		}
    	}
    	//die(var_dump($units));
    	return array_search($unit,$units);
	}
	
	static function insertProductExtra($id,$row)
	{
		global $db;
		if (!($id > 0))
		{
			return;
		}
		$cveSat = trim($row[self::$properties['extra']['claveprodserv']]);
		$umeSat = trim($row[self::$properties['extra']['umed']]);
		self::checkClaveSat($cveSat);
		if (strlen($cveSat) || strlen($umeSat))
		{
			$fields = array();
			if (strlen($cveSat))
			{
				$fields['claveprodserv'] = $cveSat;
			}
			if (strlen($umeSat))
			{
				$fields['umed'] = $umeSat;
			}
			$sql = 'SELECT `rowid` FROM `llx_product_extrafields` WHERE `fk_object`='.$id;
			if (!$res = $db->query($sql))
			{
				dol_print_error($db);
				die();
			}
			if ($extraf = $db->fetch_object($res))
			{
				$sets = array();
				if (strlen($umeSat))
				{
					$sets[] = "`umed`='{$umed}'";
				}
				if (strlen($cveSat))
				{
					$sets[] = "`claveprodserv`='{$cveSat}'";
				}
				
				$sql = 'UPDATE `llx_product_extrafields` SET '.implode(',',$sets).' WHERE `fk_object`='.$id;
			}
			else
			{
				$cols = array('fk_object');
				$vals = array($id);
				if (strlen($umeSat))
				{
					$cols[] = 'umed';
					$vals[] = "'$umeSat'";
				}
				if (strlen($cveSat))
				{
					$cols[] = 'claveprodserv';
					$vals[] = "'$cveSat'";
				}
				$sql = 'INSERT INTO `llx_product_extrafields` ('.implode(',',$cols).') VALUES ('.implode(',',$vals).')';
			}
			if (!$db->query($sql))
			{
				dol_print_error($db);
			}
		}
	}
}
