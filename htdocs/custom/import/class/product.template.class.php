<?php
date_default_timezone_set("America/Mexico_City");
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/template.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/functions.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
// require_once DOL_DOCUMENT_ROOT.''
ini_set('display_errors', '0');
class ProductTemplate extends Template {

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Categorie
     */
    private $category;

    /**
     * @var float
     */
    private $currencyRate;
    private $currencyCode;

    /**
     * Defined product vars
     */
    public $ref;
    public $label;
    public $description;
    public $categories;
    public $type;
    public $weight;
    public $status;
    public $status_buy;
    public $pieces;
    public $stock_min;
    public $stock_max;
    public $warehouse;
    public $nature;
    public $length;
    public $width;
    public $height;
    public $volume;
    public $code_adu;
    public $fk_country;
    public $barcode;
    public $cost_price;
    public $price;
    public $price_min;
    public $supplier;
    public $price_buy;
    public $min_qty;
    public $time_delivery;
    public $ref_supplier;
    public $gain_percent;
    public $packing;
    public $country_id;
    public $desc_max;
    public $empaque;
    public $cant_dentro_empaque;
    public $exentoiva;
    public $batch;
    public $stock;
    public $eatby;
    public $platform;
    public $code_type;
    public $ubication;
    public $objimp;
    public $tva_tx;


    /**
     * Constructor
     * 
     * @param   DoliDB  $db             Database handler
     * @param   User    $user           Usuario de dolibarr
     * @param   float   $currencyRate   Tasa de cambio para productos solamente
     * @param   string  $currency       Moneda precio del producto
     */
    public function __construct(DoliDB $db, User $user, $currencyRate, $currency) {
        // Relación de propiedades con columnas de excel predefinidas
        $this->relation = array(
            'base' => array (
                 'ref'					=> 'ref'
                ,'old_ref'				=> 'ref sae'
                ,'label'				=> 'descripcion (etiqueta)'
                ,'seuil_stock_alerte'	=> 'stock mínimo'
                ,'barcode'				=> 'codigo de barras'
                ,'desiredstock_principal'=> 'stock máximo (stock deseado) matriz'
                ,'wh1percent'			=> 'porcentaje de stock matriz'
                ,'wh1_limit'			=> 'limite de stock matriz'
                ,'unidad_entrada'		=> 'unidad de entrada'
                ,'unidad_salida'		=> 'unidad de salida'
                ,'date_compra'			=> 'fecha de última compra'
                ,'date_venta'			=> 'fecha de última venta'
                ,'price'		        => 'precio de venta (con iva)'
                ,'cost_price'  			=> 'precio de compra'
                ,'rotation'  			=> 'rotacion'
                ,'gain'  			    => 'ganancia'
                ,'desc_max'		        => 'descuento maximo'
                ,'empaque'              => 'empaque'
                ,'cant_dentro_empaque'	=> 'cantidad dentro del empaque'
                ,'categories'           => 'categoria'
                ,'exentoiva'            => 'exentoiva'
                ,'warehouse'            => 'almacen'
                ,'batch'                => 'lote'
                ,'stock'                => 'stock'
                ,'eatby'                => 'fecha de caducidad'
                ,'length'               => 'longitud'
                ,'width'                => 'largo'
                ,'height'               => 'alto'
                ,'volume'               => 'volumen'
                ,'code_type'            => 'tipo de producto'
                ,'platform'             => 'publicar en plataforma'
                ,'tva_tx'               => 'tasa de iva'
                ,'ubication'            => 'ubicacion'
                ,'objimp'               => 'objeto impuesto'
                //,'currency'		=> 'Moneda' // Por verificar
            ), // 15 campos
            'extra' => array (
                 'umed'					=> 'clave unidad (sat)'
                ,'claveprodserv'		=> 'clave sat'
                ,'objimp'               => 'objeto impuesto'
            ), // 5 campos
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
                ,'price'				=> 'precio de venta (con iva)'
                ,'packing'				=> 'unidad de empaque (empaque)'
                
			), // 11 campos
			// 'categs'	=> array(
            //      'parent'				=> 'nueva linea general'
            //     ,'child'				=> 'nueva linea tipo producto'
            //     ,'lone'					=> 'linea marca'//línea marca
                
                
			// ), // 3 campos
			// 'stocks'		=> array(
            //      'stock_m'				=> 'existencias matriz'
            //     ,'stock_g'				=> 'existencias gpe'
			// ) // 2 campos
			// total 34 campos (incluye moneda)
        );
        $this->currencyCode = $currency;
        $this->currencyRate = $currencyRate;
        $this->status		= 'en venta';
        $this->status_buy	= 'en compra';


        parent::__construct($db, $user, "product");
    }

    /**
     * createObject
     * 
     * Función para crear el producto o actualizar en la BD.
     */
    public function createObject() {
        global $langs, $sellprice_cal, $user;
        $res_add = 0;
        $langs->load("products");
        $langs->load("produits");
        //$this->ref = dol_sanitizeFileName(dol_string_nospecial(trim($this->ref)));
        $this->ref = trim($this->ref);

        $this->product = new Product($this->db);
        //$res = $this->product->fetch('', $this->ref);
        $res = $this->product->fetch('', '', '', $this->barcode);
        
        if ($res == 1)
        {
            $this->status  = (dol_strtolower($this->product->status) == 1) ? 'en venta' : 'no';
            $this->status_buy = (dol_strtolower($this->product->status_buy) == 1) ? 'en compra' : 'no';
            if($this->old_ref){
                $res = $this->product->fetch('',$this->old_ref);
                if ($res > 0)
                {
                     $this->product->old_ref = trim($this->old_ref);
                     $this->product->ref = $this->ref;
                 }
            }
        }
        
        $this->_fillProductObjectProperties($this->product);

        if ($res == 0)
        {
        	$status = 1;
        	$prodid = $this->product->create($this->user);
            if ($prodid > 0) 
			{
                $this->product->id = $prodid;
                $this->setSellPrices();
            }
            else 
			{
                $this->errors['error'][$this->ref]['Creación']['label'] = "No se pudo crear producto $this->ref";
                $this->errors['error'][$this->ref]['Creación']['db'] = $this->product->error;
                $status = -2;
            }
        }
        elseif ($res > 0)
        {
        	$status = 2;
        }
        else
        {
        	$status = -2;
        }
        if ($status > 0)
        {
			if ($this->product->update($this->product->id, $this->user) == -2)
			{
				$this->errors['error'][$this->ref]['Actualización']['label'] = "No se pudo actualizar producto $this->ref";
				$this->errors['error'][$this->ref]['Actualización']['db'] = $this->product->error;
				$status = -1;
            }else {
                $objimp = $this->objimp;
                if(strpos($this->objimp, '0') !== 0) {
                    $objimp = '0' . $this->objimp;
                }
                $sql = "UPDATE llx_product_extrafields SET objimp='". $objimp . "' WHERE fk_object='". $this->product->id . "'";
                $this->db->query($sql);
            }
            if ($this->product->checkPercentages($this->wh1percent, $this->wh2percent) != '1')
            {
				//$this->errors['warning'][$this->ref]['Porcentajes']['label'] = "No se pudo asignar el porcentaje de stock para el producto $this->ref";
				//$this->errors['warning'][$this->ref]['Porcentajes']['db'] = $this->product->error;
            }
            if ($this->product->checkLimits($this->wh1_limit, $this->wh2_limit) != '1')
            {
				//$this->errors['warning'][$this->ref]['Porcentajes']['label'] = "No se pudo asignar el porcentaje de stock para el producto $this->ref";
				//$this->errors['warning'][$this->ref]['Porcentajes']['db'] = $this->product->error;
            }
			if ($this->product->setStocks() == -1)
			{
				$this->errors['warning'][$this->ref]['Stocks']['label'] = "No se pudo reasignar los stocks del producto $this->ref";
				$this->errors['warning'][$this->ref]['Stocks']['db'] = $this->product->error;
			}
        }

		// Stocks
        if(isset($this->warehouse) && isset($this->eatby) && isset($this->stock) && isset($this->batch)){
            $this->adjustStocks($this->warehouse);
        }
		
		
        
        
        
        

        if ($status > 0) {
            // Update prices if are different
            if (($this->product->price_ttc != $this->price || $this->product->price_min_ttc != $this->price_min) && $status == 2 && !$sellprice_cal) {
                $this->setSellPrices(); //TODO:  Warning
            }
            
            // Buy price
			for ($i=1;$i<4;$i++)
			{
				if (strlen($this->iteractions['rfc_fourn_'.$i]))
				{
					$this->supplier			= $this->iteractions['rfc_fourn_'.$i];
					$this->ref_supplier		= $this->iteractions['ref_fourn_'.$i];
					$this->time_delivery	= $this->iteractions['delivery_time_days_'.$i]!=null?$this->iteractions['delivery_time_days_'.$i]:0;
					$this->min_qty			= 1;
					$this->price_buy		= $this->iteractions['price'];
					$this->packing			= $this->iteractions['packing']!=null?$this->iteractions['packing']:0;
					
					if (!strlen($this->ref_supplier))
					{
						$this->ref_supplier = $this->ref;
					}
					//echo "Pasada {$i} para {$this->ref} / {$this->ref_supplier} con {$this->supplier} <br />\r\n"; 
					 $res_add = $this->setBuyPrices($sellprice_cal);
				}
			}
            
            $this->product->array_options = $this->array_options;

            if($this->exentoiva == 1){
                $resFields = $this->product->insertExtraFields();
                // $objimpsql="UPDATE llx_product_extrafields SET objimp='01' where fk_object= ".$this->product->id;
                // $this->db->query($objimpsql);
            }else {
                $resFields = $this->product->insertExtraFields();
                // $objimpsql="UPDATE llx_product_extrafields SET objimp='NULL' where fk_object= ".$this->product->id;
                // $this->db->query($objimpsql);
            }
            
            // If extra fields were inserted, check if exist in dictionary
            if ($resFields)
                $this->checkAttrCFDI();
            elseif($resFields < 0) {
                $this->errors['warning'][$this->ref]['CFDI']['label'] = "No se pudieron asignar valores de SAT(Clave, unidad de medida) del producto $this->ref";
                $this->errors['warning'][$this->ref]['CFDI']['db'] = $this->db->lasterror();
            }
            
			// $cats = array();
            // if ( strlen(trim($this->categs['parent'])) )
            // {
            // 	$cats[] = trim($this->categs['parent']); 
            	
            // }
            // if ( strlen(trim($this->categs['child'])) )
            // {
            // 	$cats[] = trim($this->categs['child']); 
            	
            // }
            // if ( strlen(trim($this->categs['lone'])) && strlen(trim($this->categs['child'])) )
            // {
            // 	$cats[] = trim($this->categs['lone']); 
            	
            // }else{
            //     $cats = array();
            // }
            // if ( count($cats) )
            // {
            // 	$this->categories = implode('/',$cats);
	        //     $this->setCategories();
            // }

        }
        if($this->price!=null && $sellprice_cal==false){
            $c = new Categorie($this->db);
            $categorias=$c->getListForItem($this->product->id,'product');
            foreach ($categorias as $cat){
                if($cat['fk_parent']>0){
                    $sql1="SELECT label from llx_categorie where rowid= ".$cat['fk_parent'];
                    $result1=$this->db->query($sql1);
                    $data=$this->db->fetch_object($result1);
                    if($data->label=='INCREMENTO DE PRECIO'){
                        $sql3="SELECT fk_product from llx_categorie_product where fk_categorie = ".$cat['id'] ;
                        $max=$this->db->query($sql3);
                        break;

                    }
                }
            }
            if($this->db->num_rows($max)>0){
                while($products_ids= $this->db->fetch_object($max)){
                    $object_aux= new Product($this->db);
                    $object_aux->fetch($products_ids->fk_product);

                    //Guardar registros en temporal para: PC, PV y Ganancia
                    $sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
                    $sql .= " gainTemp = gain , costPriceTemp = cost_price, precioTemp = price "; 
                    $sql .= " WHERE rowid = ".$object_aux->id;
                    $resql = $this->db->query($sql);
                    if(!$resql){
                        $status = -1;
                    }
                    $object_aux->updatePrice($this->price, 'TTC', $this->user, 16, $this->price*.75, 0, 0, 0, 0, '');
                    unset($object_aux);
                }
            }
        }
        if($res_add==1 && $sellprice_cal==true){
            $c = new Categorie($this->db);
            $categorias=$c->getListForItem($this->product->id,'product');
            foreach ($categorias as $cat){
                if($cat['fk_parent']>0){
                    $sql1="SELECT label from llx_categorie where rowid= ".$cat['fk_parent'];
                    $result1=$this->db->query($sql1);
                    $data=$this->db->fetch_object($result1);
                    if($data->label=='INCREMENTO DE PRECIO'){
                        $sql3="SELECT fk_product from llx_categorie_product where fk_categorie = ".$cat['id'] ;
                        $max=$this->db->query($sql3);
                        break;

                    }
                }
            }
            if($this->db->num_rows($max)>0){
                while($products_ids= $this->db->fetch_object($max)){
                    if($products_ids->fk_product!=$this->product->id){
                    $object_aux= new Product($this->db);
                    $object_aux->fetch($products_ids->fk_product);
                    //Guardar registros en temporal para: PC, PV y Ganancia
                    $sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
                    $sql .= " gainTemp = gain , costPriceTemp = cost_price, precioTemp = price "; 
                    $sql .= " WHERE rowid = ".$object_aux->id;
                    $resql = $this->db->query($sql);
                    if(!$resql){
                        $status = -1;
                    }
                    $object_aux->updatePrice($this->product->price_ttc, 'TTC', $this->user, 16, $this->product->price_min_ttc, 0, 0, 0, 0, '');
                    unset($object_aux);
                    }
                }
            }
        }
        // else $status = -1;

        // Categoría
        if($this->categories != '')
        {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_product='".$this->product->id."'";
            $result = $this->db->query($sql);
            $categories = explode('/',$this->categories);
            foreach ($categories as $cat)
            {
                $sql = "select rowid from ".MAIN_DB_PREFIX."categorie where label ='".$cat."'";
                $result = $this->db->query($sql);
                $catid = $this->db->fetch_object($result);
                $rescat = $this->product->AddProductCategory($catid->rowid);
            }
        }
        updateShopifyPrice($this->product->id);

        unset($entrepot);
        unset($this->product);
        return $status;
    }

    /**
     * Build the categories of product
     */
    // private function setCategories() {
    //     $cat_array = array();
    //     // Split categories
    //     $cats = explode("/", $this->categories);//var_dump($cats);die();

    //     foreach($cats as $index => $c) {
    //         $this->category = new Categorie($this->db);
    //         // Set cateogry parent
    //         $this->category->fk_parent = ($index !=  0 && !empty($cat_array)) ? end($cat_array) : 0;

    //         // Get category id, if exist save in array
    //         $catid = $this->category->getId($c, Categorie::TYPE_PRODUCT);
            
    //         if ($catid > 0) $cat_array[] = $catid;
    //         // Create category
    //         else {
    //             $this->category->label       = $c;
    //             $this->category->socid       = 'null';
    //             $this->category->type        = Categorie::TYPE_PRODUCT;
    //             $this->category->color       = str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT); // Random color

    //             $catid = $this->category->create($this->user);
    //             if ($catid > 0) $cat_array[] = $catid;
    //             else {
    //                 if (!empty($this->errors['warning']['_$cats?'])) {
    //                     if(array_key_exists($this->category->label, $this->errors['warning']['_$cats?']))
    //                         $this->errors['warning']['_$cats?'][$this->category->label]['prods'][] = $this->ref;
    //                 }
    //                 else {
    //                     $this->errors['warning']['_$cats?'][$this->category->label]['label'] = "No se pudo crear categoría ".$this->category->label;
    //                     $this->errors['warning']['_$cats?'][$this->category->label]['prods'][] = $this->ref;
    //                 }
    //             }
    //         }

    //         unset($this->category);
    //     }
        
    //     $this->product->setCategories($cat_array,1);
    // }

    /**
     * Check if claveprodserv and umed exist in dictionary, otherwise they created
     */
    private function checkAttrCFDI() {
        foreach($this->array_options as $key => $value) {
            // Prevent empty value
            if (!empty($value)) {
                // Assign table
                if ($key == "options_umed") $table = "c_cfdimx_unidad_medida";
                elseif ($key == "options_claveprodserv") $table = "c_cfdimx_clave_prodserv";
    
                $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."$table WHERE code = '$value'";
                $res = $this->db->query($sql);
    
                if ($res) {
                    // Create extra field.
                    if ($this->db->num_rows($res) == 0) {
                        $insert = "INSERT INTO ".MAIN_DB_PREFIX."$table(code, label) value('$value', '$value - Undefined')";
                        $this->db->begin();
                        $resInsert = $this->db->query($insert);
    
                        // Success
                        if ($resInsert) $this->db->commit();
                        // Error
                        else $this->db->rollback();
                    }
                }
            }
        }
    }

    /**
     * Set prices of product if exists
     */
    private function setSellPrices() {
        global $conf, $mysoc;

        if($this -> exentoiva == 0){
            $price_base_type = 'TTC';
            $tva_tx_txt = 16;
        } else{
            $price_base_type = 'HT';
            $tva_tx_txt = 0;
        }
        
        
        $maxpricesupplier = $this->product->min_recommended_price();

        $tva_tx = $tva_tx_txt;
        $vatratecode = '';
        if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg)) {
            $vat_src_code = $reg[1];
            $tva_tx = preg_replace('/\s*\(.*\)/', '', $tva_tx_txt); // Remove code into vatrate.
        }
        $tva_tx = price2num(preg_replace('/\*/', '', $tva_tx)); // keep remove all after the numbers and dot

        $npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
        $localtax1 = 0;
        $localtax2 = 0;
        $localtax1_type = '0';
        $localtax2_type = '0';
        // If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
        if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg)) {
            // We look into database using code
            $vatratecode = $reg[1];
            // Get record from code
            $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
            $sql .= " FROM " . MAIN_DB_PREFIX . "c_tva as t, " . MAIN_DB_PREFIX . "c_country as c";
            $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '" . $mysoc->country_code . "'";
            $sql .= " AND t.taux = " . $tva_tx . " AND t.active = 1";
            $sql .= " AND t.code ='" . $vatratecode . "'";
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                $npr = $obj->recuperableonly;
                $localtax1 = $obj->localtax1;
                $localtax2 = $obj->localtax2;
                $localtax1_type = $obj->localtax1_type;
                $localtax2_type = $obj->localtax2_type;

                // If spain, we don't use the localtax found into tax record in database with same code, but using the get_localtax rule
                if (in_array($mysoc->country_code, array('ES'))) {
                    $localtax1 = get_localtax($tva_tx, 1);
                    $localtax2 = get_localtax($tva_tx, 2);
                }
            }
        }
        $precio=(price2num($this->price)!=null)?$this->price:$this->product->price_ttc;
        if($this->price!=null){
            $precio_min_ttc=$precio*.75;
        }else{$precio_min_ttc=$this->product->price_min_ttc;}

        $pricestoupdate[0] = array(
            'price' => (price2num($this->price)!=null)?$this->price:$this->product->price_ttc,
            'price_min' => price2num($precio_min_ttc),
            'price_base_type' => $price_base_type,
            'default_vat_code' => $vatratecode,
            'vat_tx' => $tva_tx, // default_vat_code should be used in priority in a future
            'npr' => $npr, // default_vat_code should be used in priority in a future
            'localtaxes_array' => array('0' => $localtax1_type, '1' => $localtax1, '2' => $localtax2_type, '3' => $localtax2)   // default_vat_code should be used in priority in a future
        );

        $this->db->begin();
        $error = 0;

        foreach ($pricestoupdate as $key => $val) {
            $newprice = $val['price'];

            if ($val['price'] < $val['price_min'] && !empty($this->product->fk_price_expression)) {
                $newprice = $val['price_min']; //Set price same as min, the user will not see the
            }

            $newprice = price2num($newprice, 'MU');
            $newprice_min = price2num($val['price_min'], 'MU');

            if (!empty($conf->global->PRODUCT_MINIMUM_RECOMMENDED_PRICE) && $newprice_min < $maxpricesupplier) {
                // setEventMessages($langs->trans("MinimumPriceLimit", price($maxpricesupplier, 0, '', 1, -1, -1, 'auto')), null, 'errors');
                $error++;
                break;
            }

            if ($this->product->multiprices[$key] != $newprice || $this->product->multiprices_min[$key] != $newprice_min || $this->product->multiprices_base_type[$key] != $val['price_base_type'])
            {
                //Guardar registros en temporal para: PC, PV y Ganancia
                $sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
                $sql .= " gainTemp = gain , costPriceTemp = cost_price, precioTemp = price "; 
                $sql .= " WHERE rowid = ".$this->product->id;
                $resql = $this->db->query($sql);
                if(!$resql){
                    $status = -1;
                }
                // Precio mínimo de venta de acuerdo al descuento máximo del producto
                $newpricemin = $newprice - ($newprice * ($this -> desc_max)/100);
                $res = $this->product->updatePrice($newprice, $val['price_base_type'], $this->user, $val['vat_tx'], $newpricemin, $key, $val['npr'], 0, 0, $val['localtaxes_array'], $val['default_vat_code']);
            }else $res = 0;


            if ($res < 0) {
                $error++;
                $this->db->rollback();
                // setEventMessages($this->product->error, $this->product->errors, 'errors');
                break;
            }
        }

        if (!$error) $this->db->commit();
    }

    /**
     * Set buy prices of product if supplier exist
     * @param   boolean     $sellprice_cal      Calculate sell price from buy price.
     */
    private function setBuyPrices($sellprice_cal) {
        global $conf, $langs;

        $langs->loadLangs(array('products', 'suppliers', 'bills', 'margins', 'errors'));
        
        // Check if supplier exist.
        $supplier = new Fournisseur($this->db);
		if ($supplier->fetch(Societe::getSupplierID($this->supplier)) > 0) {
            $ref_fourn = $this->ref_supplier;
            $quantity = price2num($this->min_qty, 'MS');
            $npr = preg_match('/\*/', 16) ? 1 : 0;
            $tva_tx = price2num(16);
            $delivery_time_days = !empty($this->time_delivery) ? $this->time_delivery : '';
    
            $error = 0;
            if (empty($quantity)) $error++;
                // setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Qty")), null, 'errors');
            if (empty($ref_fourn)) $error++;
                // setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefSupplier")), null, 'errors');
            if (price2num($this->price_buy) < 0 || $this->price_buy == '') $error++;
                // setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Price")), null, 'errors');
    
            // if ($conf->multicurrency->enabled) {
            //     if (empty($_POST["multicurrency_code"])) {
            //         $error++;
            //         $langs->load("errors");
            //         setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Currency")), null, 'errors');
            //     }
            //     if (price2num($_POST["multicurrency_tx"]) <= 0 || $_POST["multicurrency_tx"] == '') {
            //         $error++;
            //         $langs->load("errors");
            //         setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("CurrencyRate")), null, 'errors');
            //     }
            //     if (price2num($_POST["multicurrency_price"]) < 0 || $_POST["multicurrency_price"] == '') {
            //         $error++;
            //         $langs->load("errors");
            //         setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PriceCurrency")), null, 'errors');
            //     }
            // }
    
            if (!$error) {
                $object = new ProductFournisseur($this->db);
                $object->fetch($this->product->id);
    
                $this->db->begin();
    
                if (!$error) {
                    $ret = $object->add_fournisseur($this->user, $supplier->id, $ref_fourn, $quantity); // This insert record with no value for price. Values are update later with update_buyprice
                    if ($ret == -3)
                    {
                        $error++;
    
                        // $object->fetch($object->product_id_already_linked);
                        // $productLink = $object->getNomUrl(1, 'supplier');
    
                        // setEventMessages($langs->trans("ReferenceSupplierIsAlreadyAssociatedWithAProduct", $productLink), null, 'errors');
                    }
                    elseif ($ret < 0) $error++;
                        // setEventMessages($object->error, $object->errors, 'errors');
                }
    
                if (!$error) {
                    // if (GETPOSTISSET('ref_fourn_price_id')) {
                    // 	$object->fetch_product_fournisseur_price(GETPOST('ref_fourn_price_id', 'int'));
                    // }
                    $newprice = price2num($this->price_buy);
    
                    if ($conf->multicurrency->enabled || $conf->global->PRODUIT_FOURN_MULTICURRENCY)
                    {
                        $multicurrency_tx = price2num(1 / $this->currencyRate);
                        $multicurrency_price = price2num($newprice);
                        $multicurrency_code = $this->currencyCode;

                        $newprice = price2num($multicurrency_price / $multicurrency_tx);
    
                        $ret = $object->update_buyprice($quantity, $newprice, $this->user, 'HT', $supplier, null, $ref_fourn, $tva_tx, 0, 0, 0, $npr, $delivery_time_days, '', array(), '', $multicurrency_price, 'HT', $multicurrency_tx, $multicurrency_code, '', '', '', $this->packing,"Importacion");
                    } else {
                        $ret = $object->update_buyprice($quantity, $newprice, $this->user, 'HT', $supplier, null, $ref_fourn, $tva_tx, 0, 0, 0, $npr, $delivery_time_days, '', array(), '', 0, 'HT', 1, '', '', '', '', $this->packing,"Importacion");
                    }
                    $gain=0;
                    if($object->gain==null){
                        $gain=0;
                    }else{
                        $gain=$object->gain;
                    }

                    if ($ret > 0 && $sellprice_cal) {
                        // Gain
                        $reg = $object->updatePriceGain($supplier->id, $gain, $this->user);
                        $object->cost_price=$newprice;
                        $object->update($object->id,$this->user);
                        if(!$reg) {
                            $this->errors['warning'][$this->ref]['Precio de venta']['label'] = "No se pudo calcular el precio de venta del producto $this->ref";
                            $this->errors['warning'][$this->ref]['Precio de venta']['db'] = $object->error;
                        }
                    }
                    elseif ($ret < 0) $error++;
                        // setEventMessages($object->error, $object->errors, 'errors');
                }
    
                if (!$error){
                    $this->db->commit();
                    return 1;
                }
                else {
                    $this->db->rollback();
                    $this->errors['warning'][$this->ref]['Precio de compra']['label'] = "No se pudo asignar el precio de compra del producto $this->ref";
                    $this->errors['warning'][$this->ref]['Precio de compra']['db'] = $object->error;
                }

                unset($object);
            }
        }
        unset($supplier);
    }
    /**
     * Search all ref of products
     * @return array all products with ref
     */
    public function getAllRefProducts()
    {
        $sql="SELECT rowid,ref FROM ".MAIN_DB_PREFIX."product";
        $resql = $this->db->query($sql);
        while($row = $this->db->fetch_array($resql)){
            $prod['rowid'] = $row["rowid"];
            $prod['ref'] = $row['ref'];
            $result[] = $prod;
        }
        return $result;
    }
    /**
     * Compare de new ref with the old ref and calculate de percent of coincidence
     * @return array the product and the max coincidence
     */
    public function SearchCoincidence($products)
    {
        $percent = 0;
        $result = array('best'=>0,'oldref'=>'','newref'=>$this->ref,'label'=>$this->label);
        foreach ($products as $key=>$value) {
            $oldRefWhithoutC = str_replace('/', '', $value['ref']);
            $oldRefWhithoutC = str_replace('-', '', $oldRefWhithoutC);
            $oldRefWhithoutC = str_replace('_', '', $oldRefWhithoutC);
            $newRefWhithoutC = str_replace('/', '', $this->ref);
            $newRefWhithoutC = str_replace('-', '', $newRefWhithoutC);
            $newRefWhithoutC = str_replace('_', '', $newRefWhithoutC);
            similar_text($oldRefWhithoutC, $newRefWhithoutC, $percent);
            if ($percent > $result['best'] && $percent >= 100){
                $result['best'] = $percent;
                $result['oldref'] = $value['ref'];
            }
        }
        return $result;
    }
    
    public static function createUnit($unit)
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
    		$mu = $measuringUnits->fetch('','',substr(dol_strtoupper($unit),0,5),'uni',1);
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
                    array_push($units,$unit);
    			}
    		}
    	}
    	//die(var_dump($units));
    	return array_search($unit,$units);
    }
    
    private function _fillProductObjectProperties(&$product)
    {
        global $user;
        if (strpos($this->date_compra,'/'))
        {
        	$tdate = explode('/',$this->date_compra);
        	$this->date_compra = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
        }
        if (strpos($this->date_venta,'/'))
        {
        	$tdate = explode('/',$this->date_venta);
        	$this->date_venta = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
        }
        if (strpos($this->eatby,'/'))
        {
        	$tdate = explode('/',$this->eatby);
        	$this->eatby = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
        }
		$atts = array(
      				 'ref'
      				,'old_ref'
      				,'label'
      				,'seuil_stock_alerte'
      				,'barcode'
      				,'desiredstock_principal'
      				,'desiredstock_gpe'
      				,'wh1percent'
      				,'wh2percent'
                    ,'wh1_limit'
      				,'wh2_limit'
      				,'location_matriz'
                    ,'location_gpe'
                    ,'location_matriz2'
      				,'location_gpe2'
      				,'date_compra'
                    ,'date_venta'
                    ,'cost_price'
                    ,'rotation'
                    ,'gain'
                    ,'desc_max'
                    ,'cant_dentro_empaque'
                    ,'empaque'
                    ,'exentoiva'
                    ,'warehouse'
                    ,'batch'
                    ,'eatby'
                    ,'stock'
                    ,'length'
                    ,'width'
                    ,'height'
                    ,'volume'
                    ,'code_type'
                    ,'platform'
                    ,'ubication'  
	  				);
		foreach($atts as $att)
		{
			if (strlen($this->{$att}))
			{
				$product->{$att}	= $this->{$att};
			}
        }
		if($this->code_type != null){
            $sql_type="SELECT rowid from ".MAIN_DB_PREFIX."c_product_type where code = '".$this->code_type."'";
            $result_type=$this->db->query($sql_type);
            if($this->db->num_rows($result_type)>0) {
                $obj_type = $this->db->fetch_object($result_type);
                $product->fk_type=$obj_type->rowid;
            }
        }

		if($this->country_id != null){
            $sql="SELECT rowid from ".MAIN_DB_PREFIX."c_country where label = '".$this->country_id."'";
            $result=$this->db->query($sql);
            $data= null;
            if($this->db->num_rows($result)>0) {
                $obj = $this->db->fetch_object($result);
                $product->country_id=$obj->rowid;
            }
        }
        if($this->date_compra==null && $product->date_compra!=null){
            $dt = new DateTime($product->date_compra);
            $fecha = $dt->format('d/m/Y');
            $tdate = explode('/',$fecha);
            $this->date_compra = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
            $product->date_compra=$this->date_compra;
        }else{
            $product->date_compra=$this->date_compra;
        }
        if($this->date_venta==null && $product->date_venta!=null){
            $dt = new DateTime($product->date_venta);
            $fecha = $dt->format('d/m/Y');
            $tdate = explode('/',$fecha);
            $this->date_venta = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
            $product->date_venta=$this->date_venta;
        }else{
            $product->date_venta=$this->date_venta;
        }
		if($this->unidad_entrada==null && $product->unidad_entrada!=null){
		    $label=$this->getUnit($product->unidad_entrada);
		    $product->unidad_entrada=$label;
        }else{
            $product->unidad_entrada=$this->unidad_entrada;
        }
        if($this->unidad_salida==null && $product->unidad_salida!=null){
            $label=$this->getUnit($product->unidad_salida);
            $product->unidad_salida=$label;
        }else{
            $product->unidad_salida=$this->unidad_salida;
        }
		
		# Campos fijos
		$product->type        	= 0; // this is a products template
		$product->status			= (dol_strtolower($this->status) == 'en venta') ? 1 : 0;
		$product->status_buy		= (dol_strtolower($this->status_buy) == 'en compra') ? 1 : 0;
        
        if($this -> exentoiva == 0){
            $product->tva_tx			= 16;
		    $product->price_base_type	= 'TTC';
        } else {
            $product->tva_tx			= 0;
		    $product->price_base_type	= 'HT';
        }
		//$product->country_id  	= getCountry($this->fk_country, '3');
		$product->finished		= 1;
		$product->fk_default_warehouse = $user->fk_warehouse;
		
        # Campos calculados
        if (is_numeric($product->desiredstock_principal) || is_numeric($product->desiredstock_gpe))
        {
        	$product->desiredstock = $product->desiredstock_principal + $product->desiredstock_gpe;
        	// $product->up
        }
        
        # Creación de diccionarios
        if (strlen($product->unidad_entrada))
        {
            $res = ProductTemplate::createUnit($product->unidad_entrada);
            if($res)
        	    $product->unidad_entrada = $res;
        }
        if (strlen($product->unidad_salida))
        {
            $res = ProductTemplate::createUnit($product->unidad_salida);
            if($res)
        	    $product->unidad_salida = $res;
        }
        if(!is_numeric($product->unidad_entrada)){
            $product->unidad_entrada=$this->getScaleFromLabel($product->unidad_entrada);
        }
        if(!is_numeric($product->unidad_salida)){
            $product->unidad_salida=$this->getScaleFromLabel($product->unidad_salida);
        }
        
        if(is_numeric($this->cost_price) && $this->cost_price > 0){
            $product->cost_price = $this->cost_price;
        }
		
        // Currency
        $product->code_currency = $this->currencyCode;
        
    }

    public function getUnit($scale){
        $sql="SELECT label from ".MAIN_DB_PREFIX."c_units where scale = ".$scale." AND unit_type='uni'";
        $result=$this->db->query($sql);
        $data= null;
        if($this->db->num_rows($result)>0){
            $obj=$this->db->fetch_object($result);
            $data=$obj->label;
        }else{
            return null;
        }
        return $data;

    }
    public function getScaleFromLabel($label){
        $sql="SELECT scale from ".MAIN_DB_PREFIX."c_units where label = '".$label."' AND unit_type='uni' AND active = 1";
        $result=$this->db->query($sql);
        $data= null;
        if($this->db->num_rows($result)>0){
            $obj=$this->db->fetch_object($result);
            $data=$obj->scale;
        }else{
            return null;
        }
        return $data;
    }
    
    public function adjustStocks($warehouse_label)
    {
    	global $user;
        if(trim($warehouse_label)!=NULL){
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."entrepot WHERE ref LIKE '%".trim($warehouse_label)."%'";
            $res = $this->db->query($sql);
            if ($this->db->num_rows($res)>0){
                $warehouse = $this->db->fetch_object($res);
                $code = dol_print_date(dol_now(), '%y%m%d%H%M%S');
                if ($this->product->hasbatch()) {
                    $result = $this->product->correct_stock_batch(
                            $user,
                            $warehouse->rowid,
                            $this->stock,
                            '0',
                            'Importación', // label movement
                            '0',
                            $this->eatby,
                            '',
                            $this->batch,
                            $code
                    );
                    if($result < 0){
                        $this->errors['warning'][$this->ref]['Stock físico']['label'] = "No se pudo asignar el stock físico del producto $this->ref ";
                        $this->errors['warning'][$this->ref]['Stock físico']['db'] = $this->product->error;
                    }
                }
            }
        }
    }
}

?>