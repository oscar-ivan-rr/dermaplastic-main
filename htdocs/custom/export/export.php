<?php
global $conf, $langs;
error_reporting(0);
include_once '../../core/lib/product.lib.php';
$langs = new Translate("", $conf);
$langs->setDefaultLang($langcode);
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
setlocale(LC_ALL, 'es-MX.utf-8');

if($type == 2)
{
    header("Content-disposition: attachment; filename=\"exportacion_clientes.csv\"");
    $outputBuffer = fopen("php://output", 'w');
    $sql ="SELECT s.nom, s.name_alias,s.siren,s.code_client,s.client,s.status, s.address,s.zip,s.town as poblacion, cc.code as pais,";
    $sql.=" cd.nom as estado,cp.libelle,s.phone,s.email,s.url,s.earlypayment_discount,ct.libelle AS credito,";
    $sql.=" CONCAT(u.firstname,' ',u.lastname) as asignado,cpt.nbjour AS dias_credito,s.remise_client as descuento_r,";
    $sql.=" sre.multicurrency_amount_ttc as descuento_f, s.outstanding_limit as limite_credito,s.fk_stcomm,s.tva_intra as iva,s.automatic_invoicing,";
    $sql.=" s.rowid,formpagcfdi,usocfdi";
    $sql.=" FROM ".MAIN_DB_PREFIX."societe AS s";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_typent as ct on ct.id = s.fk_typent";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term AS cpt ON cpt.rowid = s.cond_reglement";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe_rib AS sr ON sr.fk_soc = s.rowid";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = sc.fk_user";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe_remise_except as sre ON sre.fk_soc = s.rowid";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON cp.id = s.mode_reglement";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_departements as cd ON cd.rowid = s.fk_departement";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as soc ON soc.fk_object = s.rowid";
    $sql.=" WHERE s.fournisseur = 0 GROUP BY s.rowid ORDER BY s.rowid ASC";
    $resql = $db->query($sql);

    if ($db->num_rows($resql) > 0) {
        $data = array();
        fputcsv($outputBuffer,array('Nombre','Apodo','Facturación automatica','RFC','Código de Cliente','Cliente','Estado','Dirección','Código Postal','Población','País','Provincia','Tipo de pago',
            'Telefono','eMail','Web','Tipo de Tercero','Días de crédito','Asignado al comercial','Descuento fijo (%)',
            'Descuento absoluto ($)','Descuento por Pronto pago (%)','Importe máximo de facturas pendientes','Estado de Prospección','IVA','Categoría','Pago CFDI','Uso CFDI'), ",");
        //array_push($data,array('Clave','Estatus','Nombre','Calle','Teléfono','Clasificación','Saldo'));
        $langs->load("bills");
        $langs->load("dict");
        while($clients = $db->fetch_object($resql))
        {
            $x = array($clients->nom?$clients->nom:'');
            array_push($x,$clients->name_alias?$clients->name_alias:'');
            array_push($x,$clients->automatic_invoicing== 0?"No":"Si");
            array_push($x,$clients->siren?$clients->siren:'');
            array_push($x,$clients->code_client?$clients->code_client:'');
            if($clients->client == 1)
                array_push($x,"Cliente");
            else if($clients->client == 2)
                array_push($x,"Cliente potencial");
            else if($clients->client == 3)
                array_push($x,"Cliente potencial / Cliente");
            else if($clients->client == 0)
                array_push($x,"Ni cliente, ni cliente potencial");

            if($clients->status == 1)
                array_push($x,"Activo");
            else
                array_push($x,"Suspendido");
            array_push($x,$clients->address?$clients->address:'');
            array_push($x,$clients->zip?$clients->zip:'');
            array_push($x,$clients->poblacion?$clients->poblacion:'');
            array_push($x,$clients->pais?$clients->pais:'');

            array_push($x,$clients->estado?$clients->estado:'');
            array_push($x,$clients->libelle?$langs->trans($clients->libelle):'');
            array_push($x,$clients->phone?$clients->phone:'');
            array_push($x,$clients->email?$clients->email:'');
            array_push($x,$clients->url?$clients->url:'');
            array_push($x,$clients->credito != '-'?$clients->credito:'');
            array_push($x,$clients->dias_credito?$clients->dias_credito:'');
            array_push($x,$clients->asignado?$clients->asignado:'');
            array_push($x,$clients->descuento_r?$clients->descuento_r:'');
            array_push($x,$clients->descuento_f?$clients->descuento_f:'');
            array_push($x,$clients->earlypayment_discount?$clients->earlypayment_discount:'');
            array_push($x,$clients->limite_credito?$clients->limite_credito:'');
            switch ($clients->fk_stcomm)
            {
                case 0: array_push($x,"Nunca contactado");
                    break;
                case -1: array_push($x,"No contactar");
                    break;
                case 1: array_push($x,"Para ser contactado");
                    break;
                case 2: array_push($x,"Contacto en proceso");
                    break;
                case 3: array_push($x,"Contacto realizado");
                    break;
            }
            array_push($x,$clients->iva?$clients->iva:'');
            //Categoria
            $sql2 = "SELECT c.label FROM  ".MAIN_DB_PREFIX."categorie_societe as cs";
            $sql2.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = cs.fk_categorie";
            $sql2.= " WHERE cs.fk_soc=".$clients->rowid;
            $resql2 = $db->query($sql2);
            $c='';$i=1;
            $num_c = $db->num_rows($resql2);
            while($cat = $db->fetch_object($resql2))
            {
                if($num_c == $i)
                    $c.=$cat->label;
                else
                    $c.=$cat->label.";";
                $i++;
            }
            array_push($x,$c != ''?$c:'');
            array_push($x,$clients->formpagcfdi?$clients->formpagcfdi:'');
            array_push($x,$clients->usocfdi?$clients->usocfdi:'');
            fputcsv($outputBuffer,$x, ",");
        }
    }
}
else if($type == 3)
{
    header("Content-disposition: attachment; filename=\"exportacion_proveedores.csv\"");
    $outputBuffer = fopen("php://output", 'w');
    $sql ="SELECT f.code_fournisseur,f.status,f.nom,f.address,f.phone,f.town,cd.nom as estado,cc.code as pais,";
    $sql.=" f.capital,f.url,ct.libelle AS credito,cpt.nbjour dias_credito,sr.proprio as titular,";
    $sql.=" sr.bank as banco,sr.label as sucursal,sr.number as cuenta, sr.iban_prefix";
    $sql.=" FROM ".MAIN_DB_PREFIX."societe AS f";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_typent as ct on ct.id = f.fk_typent";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term as cpt on cpt.rowid=f.cond_reglement";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."societe_rib as sr on sr.fk_soc=f.rowid";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_departements as cd ON cd.rowid = f.fk_departement";
    $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = f.fk_pays";
    $sql.=" WHERE f.fournisseur = 1 GROUP BY f.rowid ORDER BY f.rowid ASC";
    $resql = $db->query($sql);

    if ($db->num_rows($resql) > 0) {
        $data = array();
        fputcsv($outputBuffer,array('Clave','Estatus','Nombre','Calle','Teléfono','Número interior','Número exterior',
            'Colonia','Población','Municipio','Estado','Saldo','Página web','Clasificación','Con crédito',
            'Días de crédito','Titular','Banco','Num. de cuenta','Clabe'), ",");
        //array_push($data,array('Clave','Estatus','Nombre','Calle','Teléfono','Clasificación','Saldo'));
        $langs->load("bills");
        $langs->load("dict");
        while($clients = $db->fetch_object($resql))
        {
            $x = array($clients->code_fournisseur?$clients->code_fournisseur:'');
            if($clients->status == 1)
                array_push($x,"Activo");
            else
                array_push($x,"Suspendido");
            array_push($x,$clients->nom?$clients->nom:'');
            array_push($x,$clients->address?$clients->address:'');
            array_push($x,$clients->phone?$clients->phone:'');
            array_push($x,'');
            array_push($x,'');
            array_push($x,'');
            array_push($x,'');
            array_push($x,$clients->town?$clients->town:'');
            array_push($x,$clients->estado?$clients->estado:'');
            array_push($x,$clients->capital?$clients->capital:'');
            array_push($x,$clients->url?$clients->url:'');
            array_push($x,'');
            array_push($x,$clients->credito != '-'?$clients->credito:'');
            array_push($x,$clients->dias_credito?$clients->dias_credito:'');
            array_push($x,$clients->titular?$clients->titular:'');
            array_push($x,$clients->banco?$clients->banco:'');
            array_push($x,$clients->cuenta?$clients->cuenta:'');
            array_push($x,$clients->iban_prefix?$clients->iban_prefix:'');
            fputcsv($outputBuffer,$x, ",");
        }
    }
}

elseif($type == 1) {
    require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    setlocale(LC_ALL, 'es-MX.utf-8');
    header("Content-disposition: attachment; filename=\"productos.csv\"");
    $langs->loadLangs(array("dict", "products"));

    $outputBuffer = fopen("php://output", 'w');

    $sql = "SELECT p.rowid, p.old_ref, p.ref, p.label, p.description, p.desiredstock_principal, p.desiredstock_gpe, p.price_ttc, p.price_min_ttc, p.tva_tx, p.gain, p.price_base_type, p.seuil_stock_alerte AS stock_limit, p.stock, p.desiredstock";
    $sql .= ", p.finished, p.weight, p.weight_units, p.length, p.length_units, p.width, p.width_units, p.height, p.height_units, p.surface, p.surface_units, p.volume, p.volume_units";
    $sql .= ", p.customcode, p.wh1percent, p.wh2percent, p.wh1_limit, p.wh2_limit,  pe.claveprodserv, pe.umed, p.barcode, p.tosell, p.tobuy, p.fk_country, p.location_matriz, p.location_gpe, p.location_matriz2, p.location_gpe2, p.unit_entrada, p.unit_salida";
    $sql .= ", p.date_compra, p.date_venta, lcc.label AS code_country, p.rotation as rotation, p.gain as gain, p.fk_default_warehouse, e.ref AS entrepot";
    $sql .= ", p.desc_max, p.exentoiva, p.cant_dentro_empaque, p.empaque, p.ubication, pe.objimp ";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot e ON e.rowid = p.fk_default_warehouse";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields pe on pe.fk_object = p.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country lcc on p.fk_country = lcc.rowid";
    $sql .= " ORDER BY p.rowid";
    $resql = $db->query($sql);

    if ($resql->num_rows > 0) {
        $data = array();
        // FIXME: p.rowid
        if($all_col == 'on')
            $menu=array('Ref Lion','Ref Sae','Descripcion (etiqueta)', utf8_decode('Stock mínimo'), 'Codigo de barras', 'Precio de venta (con iva)', 'Precio de compra', 'Rotacion', 'Ganancia', 'Clave unidad (SAT)', 'Clave SAT', 'Descuento maximo', 'Empaque', 'Cantidad dentro del empaque', 'Exentoiva', 'Categoria', 'Stock en sucursal');
        else{
            $menu = array();
            if(in_array("0", $columns))
                array_push($menu,"Ref Lion");
            if(in_array("1", $columns))
                array_push($menu,"Ref Sae");
            if(in_array("2", $columns))
                array_push($menu,"Descripcion (Etiqueta)");
            if(in_array("3", $columns))
                array_push($menu,"Stock mínimo");
            if(in_array("4", $columns))
                array_push($menu,"Codigo de barras");
            if(in_array("5", $columns))
                array_push($menu,"Precio de venta (con iva)");
            if(in_array("6", $columns))
                array_push($menu,"Precio de compra");
            if(in_array("7", $columns))
                array_push($menu,"Rotacion");
            if(in_array("8", $columns))
                array_push($menu,"Ganancia");
            if(in_array("9", $columns))
                array_push($menu,"Clave unidad (SAT)");
            if(in_array("10", $columns))
                array_push($menu,"Clave SAT");
            if(in_array("11", $columns))
                array_push($menu,"Descuento maximo");
            if(in_array("12", $columns))
                array_push($menu,"Empaque");
            if(in_array("13", $columns))
                array_push($menu,"Cantidad dentro del empaque");
            if(in_array("14", $columns))
                array_push($menu,"Categoria");
            if(in_array("15", $columns))
                array_push($menu,"Stock en sucursal");
            if(in_array("16", $columns))
                array_push($menu,"Ubicacion");
            if(in_array("17", $columns))
                array_push($menu,"Exentoiva");
            if(in_array("18", $columns))
                array_push($menu,"Objeto impuesto");
            if(in_array("19", $columns))
                array_push($menu,"Tasa de IVA");        
        }
        fputcsv($outputBuffer,$menu, ",");
        //array_push($data,array('Clave','Estatus','Nombre','Calle','TelÃ©fono','ClasificaciÃ³n','Saldo'));
        while($product = $db->fetch_object($resql))
        {
            $register = array();
            if(in_array("0", $columns) || $all_col == 'on'){
                $register[] = $product->ref?utf8_decode($product->ref):'';
            }
            if(in_array("1", $columns) || $all_col == 'on'){
                $register[] = $product->old_ref?utf8_decode($product->old_ref):'';
            }
            if(in_array("2", $columns) || $all_col == 'on'){
                $register[] = $product->label?utf8_decode($product->label):'';
            }
            if(in_array("3", $columns) || $all_col == 'on'){
                $register[] = $product->stock_limit?$product->stock_limit:'';
            }

            if(in_array("4", $columns) || $all_col == 'on'){
                $register[] = $product->barcode?utf8_decode($product->barcode):'';
            }
            if(in_array("5", $columns) || $all_col == 'on'){
                if(!empty($product->price_ttc && $product->price_ttc > 0)){
                    $register[] = $product->price_ttc;
                }else{
                    $register[] = '';
                }
            }
            if(in_array("6",$columns) || $all_col == 'on'){
                //$sql3 = "SELECT p.price from llx_product_fournisseur_price as p where p.fk_product=".$product->rowid." order by p.datec desc limit 1";
                $sql3 = "SELECT p.cost_price from llx_product as p where p.rowid=".$product->rowid.";";
                $resql3 = $db->query($sql3);
                if($resql3->num_rows > 0){
                    while($claves = $db->fetch_object($resql3))
                    {
                        $register[] = $claves->cost_price?price($claves->cost_price):'';
                    }
                }else{
                    $register[] = '';
                }

            }
            if(in_array("7",$columns) || $all_col == 'on'){
                $register[] = $product->rotation;
            }
            if(in_array("8",$columns) || $all_col == 'on'){
                $register[] = $product->gain?$product->gain:'';
            }
            if(in_array("9",$columns) || $all_col == 'on'){
                $register[] = $product->umed?$product->umed:'';
            }
            if(in_array("10",$columns) || $all_col == 'on'){
                $register[] = $product->claveprodserv?$product->claveprodserv:'';
            }
            if(in_array("11",$columns) || $all_col == 'on')
            {
                $register[] = $product->desc_max;
            }
            if(in_array("12",$columns) || $all_col == 'on'){
                $register[] = utf8_decode($product->empaque);
            }

            if(in_array("13",$columns) || $all_col == 'on'){
                $register[] = $product->cant_dentro_empaque;
            }
            $cat = new Categorie($db);
            $categories = $cat->containing($product->rowid, 'product', 'label');
            if(in_array("14", $columns) || $all_col == 'on'){
                $register[] = utf8_decode(implode("/", $categories));
            }

            if(in_array("15", $columns) || $all_col == 'on'){
                $sql16 = "SELECT reel FROM llx_product_stock WHERE fk_product = '".$product->rowid."' AND fk_entrepot = '".$user->fk_warehouse."'";
                $resql16 = $db->query($sql16);
                if($resql16->num_rows > 0){
                    while($stock = $db->fetch_object($resql16))
                    {
                        $register[] = $stock->reel;
                    }
                }else{
                    $register[] = 0;
                }
            }
            
            if(in_array("16",$columns) || $all_col == 'on'){
                $register[] = $product->ubication;
            }
            
            if(in_array("17",$columns) || $all_col == 'on'){
                $register[] = $product->exentoiva;
            }
            if(in_array("18",$columns) || $all_col == 'on'){
                $register[] = '' . $product->objimp;
            }
            if(in_array("19",$columns) || $all_col == 'on'){
                $register[] = $product->tva_tx;
            }      
            /*if(in_array("3", $columns) || $all_col == 'on')
                $register[] = $product->description;


            if(in_array("4", $columns) || $all_col == 'on')
                $register[] = $product_fourn->ref_supplier;
            if(in_array("5", $columns) || $all_col == 'on')
                $register[] = $supplier;
            if(in_array("6", $columns) || $all_col == 'on')
                $register[] = $product->price_ttc;
            if(in_array("7", $columns) || $all_col == 'on')
                $register[] = $product->price_min_ttc;
            if(in_array("8", $columns) || $all_col == 'on')
                $register[] = $price_buy;
            if(in_array("9", $columns) || $all_col == 'on')
                $register[] = $product->gain;
            if(in_array("10", $columns) || $all_col == 'on')
                $register[] = $qty;
            if(in_array("11", $columns) || $all_col == 'on')
                $register[] = $time_delivery;
            if(in_array("12", $columns) || $all_col == 'on')
                $register[] = $product_fourn->packing;
            if(in_array("13", $columns) || $all_col == 'on')
                $register[] = $product->stock_limit;
            if(in_array("14", $columns) || $all_col == 'on')
                $register[] = $product->stock;
            if(in_array("15", $columns) || $all_col == 'on'){
                $sql3="select * from llx_product_stock where fk_product=".$product->rowid." and fk_entrepot=1";
                $r = $db->query($sql3);
                while($stock= $db->fetch_object($r))
                {
                    $register[] = $stock->reel?$stock->reel:'';
                }
            }
            if(in_array("16", $columns) || $all_col == 'on'){
                $sql3="select * from llx_product_stock where fk_product=".$product->rowid." and fk_entrepot=2";
                $r = $db->query($sql3);
                while($stock= $db->fetch_object($r))
                {
                    $register[] = $stock->reel?$stock->reel:'';
                }
            }
            if(in_array("17", $columns) || $all_col == 'on')
                $register[] = $product->desiredstock;
            if(in_array("18", $columns) || $all_col == 'on')
                $register[] = $product->desiredstock_principal;
            if(in_array("19", $columns) || $all_col == 'on')
                $register[] = $product->desiredstock_gpe;
            if(in_array("20", $columns) || $all_col == 'on')
                $register[] = $product->entrepot;

            if ($product->finished === null) $value = '';
            elseif ($product->finished == '0') $value = $langs->trans("OutLine");
            else $value = $langs->trans("OnLine");

            if(in_array("21", $columns) || $all_col == 'on')
                $register[] = $value;
            if(in_array("22", $columns) || $all_col == 'on')
                $register[] = $product->weight;

            if ($product->length === null) $product->length = 0;
            if ($product->width === null) $product->width = 0;
            if ($product->height === null) $product->height = 0;
            if(in_array("23", $columns) || $all_col == 'on')
                $register[] = "$product->length x  $product->width x $product->height";
            if(in_array("24", $columns) || $all_col == 'on')
                $register[] = $product->surface;
            if(in_array("25", $columns) || $all_col == 'on')
                $register[] = $product->volume;
            if(in_array("26", $columns) || $all_col == 'on')
                $register[] = $product->customcode;
            if(in_array("27", $columns) || $all_col == 'on')
                $register[] = $product->claveprodserv;
            if(in_array("28", $columns) || $all_col == 'on')
                $register[] = $product->umed;
            if(in_array("29", $columns) || $all_col == 'on')
                $register[] = $product->barcode;

            $cat = new Categorie($db);
            $categories = $cat->containing($product->rowid, 'product', 'label');
            if(in_array("30", $columns) || $all_col == 'on')
                $register[] = implode("/", $categories);

            $labelStatus = ($product->tosell) ? $langs->trans('ProductStatusOnSellShort') : $langs->trans('ProductStatusNotOnSellShort');
            if(in_array("31", $columns) || $all_col == 'on')
                $register[] = $labelStatus;

            $labelStatus = ($product->tobuy) ? $langs->trans('ProductStatusOnBuyShort') : $langs->trans('ProductStatusNotOnBuyShort');
            if(in_array("32", $columns) || $all_col == 'on')
                $register[] = $labelStatus;
            if(in_array("33", $columns) || $all_col == 'on')
                $register[] = (!is_null($product->code_country)) ? $langs->trans('Country'.$product->code_country) : '';

            if(in_array("34", $columns) || $all_col == 'on')
                $register[] = $product->location_matriz;
            if(in_array("35", $columns) || $all_col == 'on')
                $register[] = $product->location_gpe;
            if(in_array("36", $columns) || $all_col == 'on')
                $register[] = measuringUnitString(0, "uni", $product->unit_entrada);
            if(in_array("37", $columns) || $all_col == 'on')
                $register[] = measuringUnitString(0, "uni", $product->unit_salida);
            if(in_array("38", $columns) || $all_col == 'on')
                $register[] = dol_print_date($product->date_compra, 'day');
            if(in_array("39", $columns) || $all_col == 'on')
                $register[] = dol_print_date($product->date_venta, 'day');

            if(in_array("40",$columns) || $all_col == 'on')//Clave alterna prov 1
            {
                $sql4 = "SELECT p.ref_fourn from llx_product_fournisseur_price as p where p.fk_product=".$product->rowid." order by p.datec desc limit 1";
                $resql4 = $db->query($sql4);
                while($claves = $db->fetch_object($resql4))
                {
                    $register[] = $claves->ref_fourn?$claves->ref_fourn:'';
                    /*if(in_array("36", $columns) || $all_col == 'on')
                        $register[] = $claves->siren;*/
            /*}
            }
            if(in_array("41",$columns) || $all_col == 'on')//RFC prov 1
            {
                $sql4 = "SELECT m.siren from llx_product_fournisseur_price as p,llx_societe as m where  (p.fk_product=".$product->rowid." and p.fk_soc=m.rowid) order by p.datec desc limit 1";
                $resql4 = $db->query($sql4);
                while($claves = $db->fetch_object($resql4))
                {
                    $register[] = $claves->siren?$claves->siren:'';
                }
            }
            if(in_array("42",$columns) || $all_col == 'on')//Clave alterna prov 2
            {
                $sql4 = "SELECT p.ref_fourn from llx_product_fournisseur_price as p where p.fk_product=".$product->rowid." order by p.datec desc limit 1,1";
                $resql4 = $db->query($sql4);
                while($claves = $db->fetch_object($resql4))
                {
                    $register[] = $claves->ref_fourn?$claves->ref_fourn:'';
                }
            }
            if(in_array("43",$columns) || $all_col == 'on')//RFC prov 2
            {
                $sql4 = "SELECT m.siren from llx_product_fournisseur_price as p,llx_societe as m where  (p.fk_product=".$product->rowid." and p.fk_soc=m.rowid) order by p.datec desc limit 1,1";
                $resql4 = $db->query($sql4);
                while($claves = $db->fetch_object($resql4))
                {
                    $register[] = $claves->siren?$claves->siren:'';
                }
            }
            if(in_array("44",$columns) || $all_col == 'on')//Clave alterna prov 2
            {
                $sql4 = "SELECT p.ref_fourn from llx_product_fournisseur_price as p where p.fk_product=".$product->rowid." order by p.datec desc limit 2,1";
                $resql4 = $db->query($sql4);
                while($claves = $db->fetch_object($resql4))
                {
                    $register[] = $claves->ref_fourn?$claves->ref_fourn:'';
                }
            }
            if(in_array("45",$columns) || $all_col == 'on')//RFC prov 3
            {
                $sql4 = "SELECT m.siren from llx_product_fournisseur_price as p,llx_societe as m where  (p.fk_product=".$product->rowid." and p.fk_soc=m.rowid) order by p.datec desc limit 2,1";
                $resql4 = $db->query($sql4);
                while($claves = $db->fetch_object($resql4))
                {
                    $register[] = $claves->siren?$claves->siren:'';
                }
            }
                */
            fputcsv($outputBuffer,$register, ",");

            unset($register);
            unset($product_fourn);
            unset($thirdparty);
        }
    }
}


fclose($outputBuffer);
exit;
