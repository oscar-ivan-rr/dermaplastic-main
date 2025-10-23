<?php

#require_once './class/PHPExcel.php';
#require_once './class/connection.php';
#require_once './class/enums/ColumnEnum.php';
#require_once '../main.inc.php';
#require_once '../product/class/product.class.php';

if (!defined('__EXPIMP__TEMP_FILE_PATH')) {
    define('__EXPIMP__TEMP_FILE_PATH', 'temp.csv');
}

if (!defined('__RC_EXPIMP__TEMP_ZIP_PATH_BASE')) 
{
	define('__RC_EXPIMP__TEMP_ZIP_PATH_BASE', realpath(DOL_DOCUMENT_ROOT.'/../').'/documents/import');
}

if (!defined('__EXPIMP__TEMP_ZIP_PATH')) {
    define('__EXPIMP__TEMP_ZIP_PATH', __RC_EXPIMP__TEMP_ZIP_PATH_BASE.'/images.zip');
}

if (!defined('__EXPIMP__TEMP_ZIP_FILES_PATH')) {
    define('__EXPIMP__TEMP_ZIP_FILES_PATH', __RC_EXPIMP__TEMP_ZIP_PATH_BASE.'/tmp');
}

if (!defined('__EXPIMP__UPLOAD_FILE_ID')) {
    define('__EXPIMP__UPLOAD_FILE_ID', 'import');
}

if (!defined('__EXPIMP__UPLOAD_ZIP_ID')) {
    define('__EXPIMP__UPLOAD_ZIP_ID', 'zipImages');
}

if (!defined('__EXPIMP__DEBUG')) {
    define('__EXPIMP__DEBUG', false);
}

if (!defined('__EXPIMP__DEBUG_IMPORTANT')) {
    // Define si se debe de imprimir cualquier mensaje con un prefijo importante
    define('__EXPIMP__DEBUG_IMPORTANT', true);
}

/**
 * @param $string string String a revisar
 * @param $query string Query de inicio
 * @return bool La cadena empieza con el query?
 */
function str_starts($string, $query) {
    return substr($string, 0, strlen($query)) === $query;
}

/**
 * Función de apoyo de depuración ( Dump )
 *
 * @param $obj mixed Objeto a depurar
 */
function dumpDebug($obj)
{
    if (__EXPIMP__DEBUG) {
        print("<br/>");
        print("<pre>");
        var_dump($obj);
        print("</pre>");
    }
}

/**
 * Función de apoyo de depuración ( Print )
 *
 * @param $str String
 */
function printDebug($str)
{
    $allow_important = false;
    if (__EXPIMP__DEBUG_IMPORTANT) {
        if (str_starts($str, "WARNING") || str_starts($str, "ERROR")) {
            $allow_important = true;
        }
    }

    if (__EXPIMP__DEBUG || $allow_important) {
        print("<br/>");
        print($str);
    }
}

/**
 * Traducir un identificador de campo de la base de datos
 *
 * @param string $id Identificador a traducir
 *
 * @return string Resultado traducido en caso de que exista la traducción
 */
function translateId($id)
{
    if (array_key_exists($id, ColumnEnum::$translation)) {
        return ColumnEnum::$translation[$id];
    } else {
        return $id;
    }
}

/**
 * Obtener un identificador desde una traducción
 *
 * @param string $tlation Traducción de la cual obtener el identificador
 *
 * @return string|bool Resultado de una traducción o false en caso de que no exista
 */
function idFromTranslation($tlation)
{
    if (in_array($tlation, ColumnEnum::$translation)) {
        return array_search($tlation, ColumnEnum::$translation);
    } else {
        return false;
    }
}

/**
 * Revisar si el archivo a importar fue subido
 *
 * @return bool
 */
function checkImportFile()
{
    if (isset($_FILES[__EXPIMP__UPLOAD_FILE_ID])) {
        if ($_FILES[__EXPIMP__UPLOAD_FILE_ID]['type'] == 'text/csv'
            || $_FILES[__EXPIMP__UPLOAD_FILE_ID]['type'] == 'application/vnd.ms-excel'
        ) {

            move_uploaded_file($_FILES[__EXPIMP__UPLOAD_FILE_ID]['tmp_name'], __EXPIMP__TEMP_FILE_PATH);

            return true;

        } else {
            return false;
        }

    } else {
        return false;
    }
}

/**
 * Revisar si las imagenes a importar fueron subidas en un archivi ZIP
 *
 * @return bool Resultado
 */
function checkImportZip()
{
    printDebug("Revisando subida de archivo ZIP");
    printDebug("Contenido de FILES:");
    dumpDebug($_FILES);

    if (isset($_FILES[__EXPIMP__UPLOAD_ZIP_ID])) {
        if ($_FILES[__EXPIMP__UPLOAD_ZIP_ID]['type'] == 'application/zip'
            || $_FILES[__EXPIMP__UPLOAD_ZIP_ID]['type'] == 'application/octet-stream'
            || $_FILES[__EXPIMP__UPLOAD_ZIP_ID]['type'] == 'application/x-zip-compressed'
            || $_FILES[__EXPIMP__UPLOAD_ZIP_ID]['type'] == 'multipart/x-zip'
        ) {
            move_uploaded_file($_FILES[__EXPIMP__UPLOAD_ZIP_ID]['tmp_name'], __EXPIMP__TEMP_ZIP_PATH);

            return true;

        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Generar markup HTML de un hipervinculo
 *
 * @param string $text Texto del hipervinculo
 * @param string $ref  Dirección del hipervinculo
 *
 * @return string       String con el markup HTML
 */
function linkHtmlMarkup($text, $ref)
{
    return "<a href='$ref' class='button'>$text</a>";
}

/**
 * Generar plantilla de producto en base a un array de datos y sus relaciones
 * con la estructura de un producto
 *
 * @param array $data      Datos a procesar
 * @param array $relations Relaciones entre los datos y los campos de un producto
 *
 * @return ProductTemplate Plantilla generada
 */
function createProductTemplate($data, $relations)
{
    $product_template = new ProductTemplate();

    $template_vars = get_object_vars($product_template);
    $template_vars_keys = array_keys($template_vars);

    foreach ($template_vars_keys as $template_key) {
        if (array_key_exists($template_key, $relations)) {
            $data_index = $relations[$template_key];
            $product_template->{$template_key} = $data[$data_index];
            // se saca la lista de propiedades de ProductTemplate
            // por cada propiedad..  si existe en el array de relaciones
            // data_index = relations en su posición de la propiedad encontrada
            // prod_template.propiedad = data[index]
        } else {
            $product_template->{$template_key} = null;
        }
    }
    //lo que se hizo fue basicamente invocar setters de ProductTemplate cuando tengamos alguno de sus valores en data

    //si la fila del CSV contiene barcode [*necesario] se quita los guiones y guarda en barcode_nodashes
    if (!empty($product_template->barcode) && $product_template->barcode != null) {
        printDebug("Valor de código de barras original: " . $product_template->barcode);

        // Quitar guiones de código de barras
        $product_template->barcode_nodashes = str_replace('-', '', $product_template->barcode);

        printDebug("Valor de código de barras cambiado: " . $product_template->barcode_nodashes);
    }

    //si la fila del CSV contiene ref, se quita los guiones y guarda en red_nodashes
    if (!empty($product_template->ref) && $product_template->ref != null) {
        printDebug("Valor de referencia original: " . $product_template->ref);

        // Quitar guiones de código de barras
        $product_template->ref_nodashes = str_replace('-', '', $product_template->ref);

        printDebug("Valor de referencia cambiado: " . $product_template->ref_nodashes);
    }

    //si no tiene rowid ni red ni barcode, eliminar el objeto antes de devolverlo
    if (empty($product_template->rowid) && empty($product_template->ref) && empty($product_template->barcode)) {
        unset($product_template);
    }

    return $product_template;
}

/**
 * Generar plantilla de producto en base a un array de datos y sus relaciones
 * con la estructura de un producto
 *
 * @param array $data      Datos a procesar
 * @param array $relations Relaciones entre los datos y los campos de un producto
 *
 * @return ProductCategoryTemplate Plantilla generada
 */
function createCategoryProductTemplate($data, $relations)
{
    $product_template = new ProductCategoryTemplate();

    $template_vars = get_object_vars($product_template);
    $template_vars_keys = array_keys($template_vars);

    foreach ($template_vars_keys as $template_key) {
        if (array_key_exists($template_key, $relations)) {
            $data_index = $relations[$template_key];
            $product_template->{$template_key} = $data[$data_index];
        }
    }

    printDebug("Valor de código de barras original: " . $product_template->barcode);

    // Quitar guiones de código de barras
    $product_template->barcode = str_replace('-', '', $product_template->barcode);

    printDebug("Valor de código de barras cambiado: " . $product_template->barcode);

    return $product_template;
}

/**
 * Generar parametros para query de actualización SQL en base a una plantilla
 *
 * @param ProductTemplate $product Producto del cual generar parametros
 *
 * @return string Resultado de parametros
 */
function generateErpUpdateParameters($product)
{ //$product es de tipo ProductTemplate
    $result_params = '';
    $product_vars = get_object_vars($product);
    $product_vars_names = array_keys($product_vars);

    printDebug("Limpiando campos de actualización");
    //deja sólo las propiedades usadas en el entity
    $product_vars_names = ProductTemplate::CleanNonTableMembers($product_vars_names);
    printDebug("Resultado de limpieza:");
    dumpDebug($product_vars_names);

    $index = 0;
    foreach ($product_vars_names as $product_prop) {
        //por cada nombre de campo ( propiedad del objeto )
        $param_val = $product->{$product_prop};
        //$param_val = producto.propiedad
        if (!empty($param_val) && $param_val != null) {
            if ($index > 0) {
                //si tiene valor y va mas de un campo, empieza a separar por ,
                $result_params .= ', ';
            }
            $result_params .= "$product_prop = ";
            //$result_param += "propiedad = "
            if (!is_numeric($param_val)) {
                // si es cadena : $result_param += "'valor'";
                $result_params .= "'$param_val'";
            } else {
                // sino : $result_param += "valor";
                $result_params .= "$param_val";
            }
        }
        $index++;
    }

    return $result_params;
}

/**
 * Generar parametros para query de actualización de extrafields SQL en base a una plantilla
 *
 * @param ProductTemplate $product Producto del cual generar parametros
 *
 * @return string Resultado de parametros
 */
function generateErpExtraUpdateParameters($product)
{
    $result_params = '';
    $product_vars = get_object_vars($product);
    $product_extrafields_vars_names = array_keys($product_vars);

    printDebug("Limpiando campos de actualización");
    $product_extrafields_vars_names = ProductTemplate::getExtrafields($product_extrafields_vars_names);
    printDebug("Resultado de limpieza:");
    dumpDebug($product_extrafields_vars_names);

    $index = 0;
    foreach ($product_extrafields_vars_names as $product_extrafields_prop) {

        $param_val = $product->{$product_extrafields_prop};

        if (!empty($param_val) && $param_val != null) {
            if ($index > 0) {
                $result_params .= ', ';
            }
            $result_params .= "$product_extrafields_prop = ";
            if (!is_numeric($param_val)) {
                $result_params .= "'$param_val'";
            } else {
                $result_params .= "$param_val";
            }
        }
        $index++;
    }

    return $result_params;
}

/**
 * Generar parametros para query de actualización SQL 
 *
 * @param ProductTemplate $product Producto del cual generar parametros
 *
 * @return array 
 */
function generateStoreUpdateQuery($product)
{
    //calculate width and height
    $product->width = $product->length > 0? $product->surface / $product->length : 0;
    $product->height = $product->surface > 0? $product->volume / $product->surface : 0;
    $meta_keys = array();
    if($product->weight) $meta_keys["weight"] = "_weight";
    if($product->length) $meta_keys["length"] = "_length";
    if($product->width) $meta_keys["width"] = "_width";
    if($product->height) $meta_keys["height"] = "_height";
    if($product->price_ttc) $meta_keys["price_ttc"] = "_sale_price";

    // query to delete postmeta
    $in = "";
    foreach ($meta_keys as $key => $value) {
        if($key == "price_ttc"){
            $in .= " '$value' '_regular_price' '_price'";
        }else{
            $in .= " '$value'";
        }

    }
    $in = str_replace(" ", ", ", trim($in));
    $query_delete = " DELETE FROM wp_cane_postmeta WHERE post_id = $product->id_store AND meta_key IN ($in)";
    
    // query to insert postmeta
    foreach ($meta_keys as $key => $value) {
        if($key == "price_ttc"){
            $values .= " ($product->price_ttc, $product->id_store, '_regular_price') ($product->price_ttc, $product->id_store, '_sale_price') ($product->price_ttc, $product->id_store, '_price')";
        }else{
            $values .= " ('".$product->$key."', $product->id_store, '$value')";  
        }      
    }
    $values = str_replace(") (", "), (", $values);
    $query_insert = " INSERT INTO wp_cane_postmeta (meta_value, post_id, meta_key) VALUES  $values"; 

    // query to update post
    if($product->etiqueta_tienda) 
        $query["update"] = " UPDATE wp_cane_posts SET post_title = '$product->etiqueta_tienda' WHERE ID = $product->id_store";

    $query["delete"] = $query_delete;
    $query["insert"] = $query_insert;
    return $query;
}


/**
 * @param $db mysqli Conexión a DB
 * @param $product ProductTemplate Producto
 * @return bool False en caso de que no se añadiera el precio
 */
function pushNewProductPrice($db, $product, $user)
{
    if ($product->price == null && $product->price_ttc == null) {
        printDebug("No hay precios a actualizar...");
        return false;
    }

    printDebug("Anadiendo precio a tabla de precios para producto: " . $product->barcode);

    $product_price_table = MAIN_DB_PREFIX . "product_price";

    $now = dol_now();

    printDebug("Fecha dol_now:");
    dumpDebug($now);

    $insert_price_query
        =   "INSERT INTO $product_price_table
                (
                    price_level,
                    date_price,
                    fk_product,
                    fk_user_author,
                    price,
                    price_ttc,
                    price_base_type,
                    tosell,
                    tva_tx,
                    recuperableonly,
                    localtax1_tx,
                    localtax2_tx,
                    price_min,
                    price_min_ttc,
                    price_by_qty,
                    entity
                ) VALUES (
                    1,
                    NOW(),
                    $product->rowid,
                    $user->id,
                    $product->price,
                    $product->price_ttc,
                    'TTC',
                    1,
                    16,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    1
                )";

    printDebug("Ejecutando consulta de inserción: ");
    dumpDebug($insert_price_query);

    $insert_price_result = $db->query($insert_price_query);

    printDebug("Resultado de inserción de precio: ");
    dumpDebug($insert_price_result);

    if ($insert_price_result === false) {
        printDebug("No se logro insertar el precio.");
        printDebug("Mensaje de mysqli:");
        dumpDebug($db->error);
    }
}

/**
 * Actualizar un producto del ERP
 *
 * @param mysqli          $db      Conexion a la base de datos para usar
 * @param ProductTemplate $product Producto a actualizar
 *
 * @return bool Resultado de la operación
 */
function updateErpProduct($db, &$product, $user)
{
    $product_table = MAIN_DB_PREFIX . "product";
    $product_check_query
        =   "SELECT rowid, ref, label, etiqueta_tienda, id_store, price_ttc
                FROM $product_table
            WHERE";
    //consultar los campos del producto de la tabla product donde rowid = product.rowid OR ...
    if (!empty($product->rowid) && $product->rowid != null) {
        $product_check_query .= " rowid = $product->rowid
            OR";
    } else {
        // REPLACE(ref, '-', '') [el campo ref sin guiones] = product.ref_nodashes OR...
        $product_check_query .= " ref = '$product->ref ' OR";
    }
    // REPLACE(barcode, '-', '') [el campo barcode sin guines] = product.barcode_nodashes;
    $product_check_query .= " barcode = '$product->barcode' ";

    printDebug("Revisando objeto con query:");
    dumpDebug($product_check_query);

    printDebug("Ejecutando Query --");
    $product_check_result = $db->query($product_check_query);
    printDebug("Resultado de ejecución:");
    dumpDebug($product_check_result);

    if ($product_check_result == false) { // si el registro no existe en ERP ...
        printDebug("ERROR: El query devolvio false");
        printDebug("Razón:");
        dumpDebug($db->error);
    }

    if ($product->price == null || empty($product->price)) {
        if ($product->price_ttc != null && !empty($product->price_ttc)) {
            //si no se puso precio pero si se puso precio_ttc [con impuesto] entonces se asigna precio quitando 16%
            printDebug("El precio sin IVA fue omitido en el producto, corrigiendo...");
            $product->price = $product->price_ttc / 1.16;
        }
    }

    if ($product->price_ttc == null || empty($product->price_ttc)) {
        if ($product->price != null && !empty($product->price)) {
            //si no se puso precio_ttc [ con impuesto] se asigna basado en el precio que si tiene, usa el 16%
            printDebug("El precio con IVA fue omitido en el producto, corrigiendo...");
            $product->price_ttc = $product->price * 1.16;
        }
    }

    if ($product->ref == null || empty($product->ref)) {
        if ($product->barcode != null && !empty($product->barcode)) {
            // si el ref está vacio pero si tenemos barcode, se asigna el barcode como ref
            printDebug("La referencia del producto fue omitida, corrigiendo...");
            $product->ref = $product->barcode;
            printDebug("La referencia del producto se establecio como el código de barras.");
        }
    }

    if ($result_obj = $product_check_result->fetch_object()) {
        //ahora jalamos el objeto del producto que consultamos por rowid
        printDebug("Resultado de fetching:");
        dumpDebug($result_obj);

        // Creación de datos omitidos
        if ($product->rowid == null || empty($product->rowid)) {
            printDebug("El objeto provisto no contiene rowid, corrigiendo...");
            // si el product no tiene rowid pero en la consulta que hicimos lo encontramos, se lo asignamos
            printDebug("Estableciendo rowid a: " . $result_obj->rowid);
            $product->rowid = $result_obj->rowid;
        }

        if ($product->id_store == null || empty($product->id_store)) {
            // si no se especificó id_store lo asigna desde el id_store del objeto en la BD que encontró
            printDebug("El objeto provisto no contiene id_store");

            printDebug("Estableciendo id_store a: " . $result_obj->id_store);
            $product->id_store = $result_obj->id_store;
        }

        $product->label = trim($product->label); // le quitamos espacios al label
        if ($product->label == null || empty($product->label)) {
            // si el label no se puso, se asigna desde el label del objeto en la BD
            printDebug("El objeto provisto no contiene label");

            printDebug("Estableciendo label a: " . $result_obj->label);
            $product->label = $result_obj->label;
        }

        $product->etiqueta_tienda = trim($product->etiqueta_tienda);
        if ($product->etiqueta_tienda == null || empty($product->etiqueta_tienda)) {
            //si no se especifica la etiqueda_tienda, se asigna desde el objeto en BD
            printDebug("El objeto provisto no contiene etiqueta_tienda");

            printDebug("Estableciendo etiqueta_tienda a: " . $result_obj->etiqueta_tienda);
            $product->etiqueta_tienda = $result_obj->etiqueta_tienda;
        }

        printDebug("Actualizando producto " . $product->ref);

        printDebug("Generando parametros de actualización");
        //ya que el producto tiene las propiedades que ocupa, se manda a generar el query de update
        $product_update_params = generateErpUpdateParameters($product);
        //aqui tendriamos algo como 'label' = 'label, 'precio' = 3545... etc.

        // definimos el precio para ser usado en la tienda, despues de que fue agregado al ERP
        if ($product->price_ttc_old == null || empty($product->price_ttc_old)) {
            //si no está definido el ttc_old, lo asignamos como ttc
            $product->price_ttc_old = $result_obj->price_ttc;
        }

        printDebug("Parametros generados:");
        dumpDebug($product_update_params);

        $product_update_query
            =   "UPDATE $product_table SET
                    $product_update_params
                WHERE
                    rowid = $product->rowid";
        //ejecutar el update, asignar los valores donde el rowid = product->rowid (despues de corregirlo )

        printDebug("Query de actualización:");
        dumpDebug($product_update_query);

        $product_update_result = $db->query($product_update_query);

        if ($product_update_result) {

            printDebug("Actualización exitosa, procesando precio...");
            pushNewProductPrice($db, $product, $user);
            printDebug("Actualizando categorias...");
            updateErpCategoryProductCategories($db, $product, $product->rowid);
            printDebug("Actualizando stock...");
            updateProductStock($db, $product, $user);
            printDebug("Actualizando precio de proveedor...");
            updateSupplierPrice($db, $product);
            // update extra fields
            updateExtraFields($db, $product);

            return true;
        } else {
            return false;
        }
    } else {

        printDebug("El producto no existe, generando...");
        $create_result = createErpProductFromDynamic($db, $product);

        if ($create_result === false) {
            printDebug("ERROR: No se inserto el producto!");
            printDebug("Mensaje de mysqli:");
            dumpDebug($db->error);
            return false;
        } else {
            printDebug("Creación exitosa, actualizando rowid a " . $create_result);
            $product->rowid = $create_result;
            printDebug("Actualizando precio...");
            pushNewProductPrice($db, $product, $user);
            printDebug("Actualizando categorias...");
            updateErpCategoryProductCategories($db, $product, $product->rowid);
            printDebug("Actualizando stock...");
            updateProductStock($db, $product, $user);
            printDebug("Actualizando precio de proveedor...");
            updateSupplierPrice($db, $product);
            // update extra fields
            updateExtraFields($db, $product);

            return true;
        }
    }
}

/**
 * Actualizar un producto en TL
 *
 * @param mysqli          $db      Conexion a la base de datos para usar
 * @param ProductTemplate $product Producto a actualizar
 *
 * @return bool Resultado de la operación
 */
function updateOnlineStoreProduct($db, &$product, $user)
{

    if ($product->id_store) {

        printDebug("Actualizando producto TL " . $product->ref);

        printDebug("Generando parametros de actualización");
        $query_array = generateStoreUpdateQuery($product);

        printDebug("Parametros generados:");
        dumpDebug($query_array);

        $result = $db->query($query_array["delete"]);
        if ($result) { 
            if (!$db->query($query_array["insert"])) {
                $db->rollback();
                print ("ERROR: No se actualizo el producto $product->ref en TL!");
                return false;
            }
        }
        if($query_array["update"]){
            if (!$db->query($query_array["update"])) {
                print ("ERROR: No se actualizo el titulo de $product->ref en TL!");
                return false;
            }
        }

    } else {

        printDebug("El producto no existe, generando...");
        $create_result = createStoreProduct($db, $product);

        if ($create_result === false) {
            printDebug("ERROR: El producto $product->ref no existe en TL y no se pudo insertar!");
            return false;
        }else {
            printDebug("Creación exitosa, actualizando id_store a " . $create_result);
            $product->id_store = $create_result;
        }

    }

    printDebug("Actualizando categorias...");
    updateStoreCategoryProductCategories($db, $product, $product->id_store);

    return true;
}


function checkBaseCategory($db, $categoryName, $isBrand = false)
{
    $category_table = MAIN_DB_PREFIX . "categorie";
    $category_check_query
        =   "SELECT rowid FROM
                $category_table
            WHERE
                label LIKE '$categoryName'";

    printDebug("Revisando categoria: " . $categoryName);
    printDebug("Query de revisión de categoria base: " . $category_check_query);

    $category_check_result = $db->query($category_check_query);

    printDebug("Resultado de check: ");
    dumpDebug($category_check_result);

    // Existe la categoria?
    if ($category_check_result->num_rows) {
        $row = $category_check_result->fetch_assoc();

        printDebug("La categoria existe con el ID: " . $row['rowid']);

        return $row['rowid'];
    } else {
        $fk_parent = 0;
        if ($isBrand) {
            $fk_parent = 1;
        }

        $tipo = 1;
        if ($isBrand) {
            $tipo = 0;
        }

        $category_insert_query
            =   "INSERT INTO
                    $category_table
                (
                    entity,
                    fk_parent,
                    label,
                    tipo
                ) VALUES (
                    1,
                    $fk_parent,
                    $categoryName,
                    $tipo
                )";

        printDebug("Se insertara la categoria con el siguiente query: " . $category_insert_query);

        $category_insert_result = $db->query($category_insert_query);

        printDebug("Resultado de inserción: ");
        dumpDebug($category_insert_result);
        printDebug("ID de la categoria insertada: " . $db->insert_id);

        return $db->insert_id;
    }

}

function checkChildCategory($db, $categoryName, $parentCategory, $isModel = false)
{
    $category_table = MAIN_DB_PREFIX . "categorie";
    $category_check_query
        =   "SELECT rowid FROM
                $category_table
            WHERE
                label LIKE '$categoryName'";

    printDebug("Revisando categoria: " . $categoryName);
    printDebug("Query de revisión de categoria base: " . $category_check_query);

    $category_check_result = $db->query($category_check_query);

    printDebug("Resultado de check: ");
    dumpDebug($category_check_result);

    // Existe la categoria?
    if ($category_check_result->num_rows) {
        $row = $category_check_result->fetch_assoc();

        printDebug("La categoria existe con el ID: " . $row['rowid']);

        return $row['rowid'];
    } else {
        $fk_parent = $parentCategory;

        $tipo = 1;
        if ($isModel) {
            $tipo = 0;
        }

        $category_insert_query
            =   "INSERT INTO
                    $category_table
                (
                    entity,
                    fk_parent,
                    label,
                    tipo
                ) VALUES (
                    1,
                    $fk_parent,
                    $categoryName,
                    $tipo
                )";

        printDebug("Se insertara la categoria con el siguiente query: " . $category_insert_query);

        $category_insert_result = $db->query($category_insert_query);

        printDebug("Resultado de inserción: ");
        dumpDebug($category_insert_result);
        printDebug("ID de la categoria insertada: " . $db->insert_id);

        return $db->insert_id;
    }
}

function checkStoreCategory($db, $categoryName)
{
    $query = "SELECT term_id FROM wp_cane_terms where name = '".$categoryName."'";
    $result = $db->query($query);

    // Existe la categoria? // solo se checa si existe, no se crea una nueva
    if ($result->num_rows) {
        $row = $result->fetch_assoc();
        return $row['term_id'];
    } else {
        return 0;
    }

}

/**
 * @param $db mysqli Base de datos
 * @param $product ProductTemplate Producto
 * @param $productId int Identificador rowid del producto
 */
function updateErpCategoryProductCategories($db, $product, $productId)
{
    printDebug("Actualizando categorias del producto: ");
    dumpDebug($product);
    printDebug("Identificado con el ID: ");
    dumpDebug($productId);

    if (!empty($product->parent_category)) {
        printDebug("Revisando categoria madre");
        $parentCatId = checkBaseCategory($db, $product->parent_category, false);
        if (!empty($product->child_category)) {
            printDebug("Revisando categoria hija");
            $childCatId = checkChildCategory($db, $product->child_category, $parentCatId, false);
            checkCategoryProductRelation($db, $childCatId, $productId, $product->from_year, $product->to_year);
        }
        checkCategoryProductRelation($db, $parentCatId, $productId, 0, 0);
    }
    if (!empty($product->brand)) {
        printDebug("Revisando categoria marca");
        $brandCatId = checkBaseCategory($db, $product->brand, true);
        if (!empty($product->model)) {
            printDebug("Revisando categoria modelo");
            $modelCatId = checkChildCategory($db, $product->model, $brandCatId, false);
            checkCategoryProductRelation($db, $modelCatId, $productId, $product->from_year, $product->to_year);
        }
        checkCategoryProductRelation($db, $brandCatId, $productId, 0, 0);
    }
}

/**
 * @param $db mysqli Base de datos
 * @param $product ProductTemplate Producto
 * @param $ID int Identificador del producto
 */
function updateStoreCategoryProductCategories($db, $product, $productId)
{

    if (!empty($product->parent_category)) {
        printDebug("Revisando categoria madre");
        $parentCatId = checkStoreCategory($db, $product->parent_category);
        if (!empty($product->child_category)) {
            printDebug("Revisando categoria hija");
            $childCatId = checkStoreCategory($db, $product->child_category);
            checkStoreCategoryProductRelation($db, $childCatId, $productId);
        }
        checkStoreCategoryProductRelation($db, $parentCatId, $productId);
    }
    if (!empty($product->brand)) {
        printDebug("Revisando categoria marca");
        $brandCatId = checkStoreCategory($db, $product->brand);
        if (!empty($product->model)) {
            printDebug("Revisando categoria modelo");
            $modelCatId = checkStoreCategory($db, $product->model);
            checkStoreCategoryProductRelation($db, $modelCatId, $productId);
        }
        checkStoreCategoryProductRelation($db, $brandCatId, $productId);
    }
}

/**
 * @param $db mysqli
 * @param $product ProductTemplate
 * @return bool Resultado de actualización
 */
function updateSupplierPrice($db, $product)
{
    $supplier_table = MAIN_DB_PREFIX . "societe";
    $check_supplier_query
        =   "SELECT rowid FROM
                $supplier_table
             WHERE
                nom = '$product->supplier'";

    printDebug("Se revisara el proveedor con el siguiente query:");
    dumpDebug($check_supplier_query);

    $check_supplier_result = $db->query($check_supplier_query);

    printDebug("Resultado de chequeo:");
    dumpDebug($check_supplier_result);

    if ($supplier = $check_supplier_result->fetch_object()) {

        $pp_ref = 'pp_' . $product->rowid;

        $supplier_price_table = MAIN_DB_PREFIX . "product_fournisseur_price";
        $check_supplier_price_query
            =   "SELECT rowid FROM
                    $supplier_price_table
                 WHERE
                    ref_fourn = '$pp_ref'
                 AND
                    fk_product = $product->rowid";

        printDebug("Se revisara la existencia del precio de proveedor con el siguiente query:");
        dumpDebug($check_supplier_price_query);

        $check_supplier_price_result = $db->query($check_supplier_price_query);

        printDebug("Resultado del chequeo de precio de proveedor:");
        dumpDebug($check_supplier_price_result);

        if ($supplier_price = $check_supplier_price_result->fetch_object()) {

            printDebug("El precio de proveedor existe, actualizando...");

            $update_supplier_price_query
                =   "UPDATE
                        $supplier_price_table
                    SET
                        fk_soc = $supplier->rowid,
                        fk_availability = 0,
                        quantity = 1,
                        tva_tx = 16,
                        price = $product->price,
                        unitprice = $product->price,
                        remise_percent = 0
                    WHERE
                        rowid = $supplier_price->rowid";

            printDebug("Se actualizara el precio de proveedor con el siguiente query:");
            dumpDebug($update_supplier_price_query);

            $update_supplier_price_result = $db->query($update_supplier_price_query);

            printDebug("Resultado de query:");
            dumpDebug($update_supplier_price_result);

            return $update_supplier_price_result;
        } else {

            printDebug("El precio de proveedor no existe, registrando...");

            $insert_supplier_price_query
                =   "INSERT INTO
                        $supplier_price_table
                    (
                        datec,
                        fk_product,
                        fk_soc,
                        ref_fourn,
                        fk_availability,
                        quantity,
                        tva_tx,
                        price,
                        unitprice,
                        remise_percent
                    ) VALUES (
                        ADDDATE(now(), INTERVAL -5 HOUR),
                        $product->rowid,
                        $supplier->rowid,
                        '$pp_ref',
                        0,
                        1,
                        16,
                        $product->price,
                        $product->price,
                        0
                    )";

            printDebug("Insertando precio de proveedor con el siguiente query:");
            dumpDebug("$insert_supplier_price_query");

            $insert_supplier_price_result = $db->query($insert_supplier_price_query);

            printDebug("Resultado de inserción:");
            dumpDebug($insert_supplier_price_result);

            return $insert_supplier_price_result;
        }

    } else {
        printDebug("WARNING: El proveedor no existe, omitiendo precio de proveedor");
        return false;
    }

}

/**
 * @param $db mysqli
 * @param $product ProductTemplate
 * @return bool Resultado de actualización
 */
function updateExtraFields($db, $product)
{
    $extrafields_table = MAIN_DB_PREFIX . "product_extrafields";
    $check_extrafields_query
        =   "SELECT rowid FROM
                $extrafields_table
             WHERE
                fk_object = '$product->rowid'";

    printDebug("Se revisara el extrafield con el siguiente query:");
    dumpDebug($check_extrafields_query);

    $check_extrafield_result = $db->query($check_extrafields_query);

    printDebug("Resultado de chequeo:");
    dumpDebug($check_extrafield_result);

    $product_extrafields_update_params = generateErpExtraUpdateParameters($product);

    if ($object = $check_extrafield_result->fetch_object()) {
        $update_query = " UPDATE $extrafields_table SET $product_extrafields_update_params WHERE rowid = $object->rowid";
        $update_result = $db->query($update_query);
        if(!$update_result) return false;
    } else {
        $insert_query = "INSERT INTO $extrafields_table SET $product_extrafields_update_params, fk_object = $product->rowid ";
        $insert_result = $db->query($insert_query);
        if(!$insert_result) return false;
        return false;
    }
    return true;

}


/**
 * @param $db mysqli Base de datos
 * @param $product ProductTemplate Producto
 * @param $user mixed Usuario
 * @return bool
 */
function updateProductStock($db, $product, $user)
{
    if ($product->stock === null) {
        printDebug("El producto no contiene datos de stock, omitiendo...");
        return false;
    }

    if ($product->warehouse == null || empty($product->warehouse)) {
        printDebug("El producto no contiene datos de almacen, omitiendo stock");
        return false;
    }

    printDebug("Actualizando stock de producto: " . $product->barcode);
    $warehouse_table = MAIN_DB_PREFIX . "entrepot";
    $check_warehouse_query
        =   "SELECT * FROM
                $warehouse_table
            WHERE
                label = '$product->warehouse'";

    printDebug("Obteniendo datos de almacen con query:");
    dumpDebug($check_warehouse_query);

    $check_warehouse_result = $db->query($check_warehouse_query);

    printDebug("Resultado de query:");
    dumpDebug($check_warehouse_result);

    if ($check_warehouse_result->num_rows == 0) {
        printDebug("WARNING: El almacen provisto no existe, omitiendo actualización de stock");
        return false;
    }

    $check_warehouse_obj = $check_warehouse_result->fetch_object();

    printDebug("Resultado de fetching de almacen:");
    dumpDebug($check_warehouse_obj);

    $stock_movement_table = MAIN_DB_PREFIX . "stock_mouvement";
    $product_stock_table = MAIN_DB_PREFIX . "product_stock";

    $check_product_stock_query
        =   "SELECT * FROM
                $product_stock_table
            WHERE
                fk_product = $product->rowid
            AND
                fk_entrepot = $check_warehouse_obj->rowid";

    printDebug("Se revisara existencia de stock de producto con el siguiente query:");
    dumpDebug($check_product_stock_query);

    $check_product_stock_result = $db->query($check_product_stock_query);

    printDebug("El resultado del chequeo de stock fue:");
    dumpDebug($check_product_stock_result);

    if ($check_product_stock_result->num_rows > 0) {
        printDebug("El stock ya existe, procediendo");

        $remove_stock_movement_query
            =   "INSERT INTO
                $stock_movement_table
            (
                datem,
                fk_product,
                fk_entrepot,
                value,
                price,
                type_mouvement,
                fk_user_author,
                label,
                fk_origin
            ) VALUES (
                ADDDATE(NOW(), INTERVAL -5 HOUR),
                $product->rowid,
                $check_warehouse_obj->rowid,
                -(
                    SELECT reel
                    FROM $product_stock_table
                    WHERE
                        fk_product = $product->rowid
                    AND
                        fk_entrepot = $check_warehouse_obj->rowid
                ),
                0,
                1,
                $user->id,
                'Importacion',
                0
            )";

        printDebug("Se generara un movimiento de eliminación de stock en almacen con el siguiente query:");
        dumpDebug($remove_stock_movement_query);

        $remove_stock_movement_result = $db->query($remove_stock_movement_query);

        printDebug("Resultado de query:");
        dumpDebug($remove_stock_movement_result);

        if ($remove_stock_movement_result === false) {
            printDebug("ERROR: La inserción del movimiento de stock fallo");
            printDebug("Mensaje de mysqli:");
            dumpDebug($db->error);
            return false;
        }

        $update_product_stock_query
            =   "UPDATE $product_stock_table
                SET
                    reel = $product->stock
                WHERE
                    fk_product = $product->rowid
                AND
                    fk_entrepot = $check_warehouse_obj->rowid";

        printDebug("Se actualizara el stock con el siguiente query:");
        dumpDebug($update_product_stock_query);

        $update_product_stock_result = $db->query($update_product_stock_query);

        printDebug("El resultado de la actualización de stock fue el siguiente:");
        dumpDebug($update_product_stock_result);

        if ($update_product_stock_result === false) {
            printDebug("ERROR: La actualización de stock fallo");
            printDebug("Mensaje de mysqli:");
            dumpDebug($db->error);
            return false;
        }

    } else {

        $insert_product_stock_query
            =   "INSERT INTO
                $product_stock_table
            (
                fk_product,
                fk_entrepot,
                reel
            ) VALUES (
                $product->rowid,
                $check_warehouse_obj->rowid,
                $product->stock
            )";

        printDebug("El stock de producto no existe, insertando con el siguiente query:");
        dumpDebug($insert_product_stock_query);

        $insert_product_stock_result = $db->query($insert_product_stock_query);

        printDebug("Resultado de inserción de stock:");
        dumpDebug($insert_product_stock_result);
    }

    $add_stock_mouvement_query
        =   "INSERT INTO
                $stock_movement_table
            (
                datem,
                fk_product,
                fk_entrepot,
                value,
                price,
                type_mouvement,
                fk_user_author,
                label,
                fk_origin
            ) VALUES (
                ADDDATE(NOW(), INTERVAL -5 HOUR),
                $product->rowid,
                $check_warehouse_obj->rowid,
                $product->stock,
                0,
                0,
                $user->id,
                'Importacion',
                0
            )";

    printDebug("Se creara un movimiento de ingreso de stock con el siguiente query:");
    dumpDebug($add_stock_mouvement_query);

    $add_stock_mouvement_result = $db->query($add_stock_mouvement_query);

    printDebug("El resultado del query fue el siguiente:");
    dumpDebug($add_stock_mouvement_result);

    if ($add_stock_mouvement_result === false) {
        printDebug("ERROR: No se añadio el movimiento de stock");
        printDebug("Mensaje de mysqli:");
        dumpDebug($db->error);
        return false;
    } else {
        printDebug("Se finalizo de actualizar el stock--------------------------");
        return true;
    }

}

function checkCategoryProductRelation($db, $categoryId, $productId, $year1 = null, $year2 = null)
{
    $category_product_table = MAIN_DB_PREFIX . "categorie_product";
    $check_category_product_relation_query
        =   "SELECT * FROM
                $category_product_table
            WHERE
                fk_product = $productId
            AND
                fk_categorie = $categoryId";

    printDebug("Se revisara la relación producto-categoria con el siguiente query: ");
    dumpDebug($check_category_product_relation_query);

    $check_cp_relation_result = $db->query($check_category_product_relation_query);

    printDebug("Resultado de chequeo: ");
    dumpDebug($check_cp_relation_result);

    // No existe la relación?
    if ($check_cp_relation_result->num_rows == 0) {

        printDebug("La relación no existe, se insertara.");

        $insert_cp_relation
            =   "INSERT INTO
                    $category_product_table
                (
                    fk_categorie,
                    fk_product,
                    year1,
                    year2
                ) VALUES (
                    $categoryId,
                    $productId,
                    '$year1',
                    '$year2'
                )";

        printDebug("Query de inserción: " . $insert_cp_relation);

        $insertion_result = $db->query($insert_cp_relation);

        printDebug("Resultado de inserción: ");
        dumpDebug($insertion_result);
    }
}

function checkStoreCategoryProductRelation($db, $categoryId, $ID)
{
    $query = "SELECT object_id FROM wp_cane_term_relationships where object_id = ".$ID." and term_taxonomy_id = ".$categoryId;
    $result = $db->query($query);
    if ($result->num_rows == 0) {
        // si no existe la relación, la creamos...
        $query = "INSERT INTO wp_cane_term_relationships (object_id, term_taxonomy_id, term_order)
        VALUES (".$ID.", ".$categoryId.", 0)";
        $result = $db->query($query);
        if ($result) {
            // actualiza el contador de productos por categoria
            $query = "UPDATE wp_cane_termmeta SET meta_value = meta_value + 1 WHERE term_id = ".$categoryId." AND 
            meta_key = 'product_count_product_cat'";
            $result = $db->query($query);

            $query = "UPDATE wp_cane_term_taxonomy SET count = count + 1 WHERE term_id = ".$categoryId." AND 
            taxonomy = 'product_cat'";
            $result = $db->query($query);

        }else{
            return -1;
        }
    }
    return 1;
}


/**
 * @param $product ProductTemplate Producto
 *
 * @return array Resultado de campos y valores
 */
function generateProductFields($product)
{
    $product_vars = get_object_vars($product);

    $product_vars_names = array_keys($product_vars);

    foreach ($product_vars_names as $var_name) {
        if ($product_vars[$var_name] == null) {
            unset($product_vars[$var_name]);
        } else {
            if (!is_numeric($product_vars[$var_name])) {
                $product_vars[$var_name] = "'$product_vars[$var_name]'";
            }
        }
    }

    printDebug("Resultado de variables:");
    dumpDebug($product_vars);

    return $product_vars;

}

function createErpProductFromDynamic($db, $product)
{
    printDebug("Generando variables de producto para inserción");
    $product_vars = generateProductFields($product);

    printDebug("Limpiando parametros de producto para inserción");
    $product_vars = ProductTemplate::CleanNonTableMembers($product_vars);

    printDebug("Valores a insertar en nuevo producto:");
    dumpDebug($product_vars);

    printDebug("Separando nombres de campo y valores de campo");
    $product_fields = array_keys($product_vars);
    $product_values = array_values($product_vars);

    printDebug("Arrays generados:");
    printDebug("Nombres:");
    dumpDebug($product_fields);
    printDebug("Valores:");
    dumpDebug($product_values);

    printDebug("Aplicando implosión a campos para inserción");
    // cs = comma sep
    $product_fields_cs = implode(',', $product_fields);
    $product_values_cs = implode(',', $product_values);

    $product_table = MAIN_DB_PREFIX . "product";
    $product_insert_query
        =   "INSERT INTO $product_table
                (
                    $product_fields_cs,
                    datec,
                    price_base_type,
                    tva_tx,
                    tosell,
                    tobuy,
                    tosellstore
                )
                VALUES
                (
                    $product_values_cs,
                    ADDDATE(now(), INTERVAL -5 HOUR),
                    'TTC',
                    16,
                    1,
                    1,
                    2
                )";

    printDebug("Creando nuevo producto en base a dynprod: " . $product_insert_query);

    $product_insert_result = $db->query($product_insert_query);

    printDebug("Resultado de inserción: ");
    dumpDebug($product_insert_result);

    if ($db->insert_id == 0 && !$product_insert_result) {
        return false;
    }

    return $db->insert_id;
}

function createStoreProduct($db, $product){

    if ( empty($product->label) && empty($product->etiqueta_tienda)) {
        // can not create a product without a label
        return false;
    }

    $result = Product::createStoreProduct($product->rowid, $product->ref, $product->etiqueta_tienda, $product->label, $product->price_ttc_old, $product->description, $product->weight, $product->length, 0);
    if ($result < 0) {
        return false;
    }
    return $result;
}

function createErpCategoryProduct($db, $product)
{
    $product_table = MAIN_DB_PREFIX . "product";
    $product_insert_query
        =   "INSERT INTO $product_table
                (
                    ref,
                    datec,
                    label,
                    price_base_type,
                    price,
                    price_ttc,
                    barcode,
                    price_min,
                    price_min_ttc,
                    tva_tx,
                    tosell,
                    tobuy,
                    tosellstore,
                    etiqueta_tienda
                )
                VALUES
                (
                    '$product->barcode',
                    ADDDATE(now(), INTERVAL -5 HOUR),
                    '$product->label',
                    'TTC',
                    0,
                    0,
                    '$product->barcode',
                    0,
                    0,
                    16,
                    1,
                    1,
                    2,
                    '$product->label'
                )";

    printDebug("Creando nuevo producto: " . $product_insert_query);

    $product_insert_result = $db->query($product_insert_query);

    printDebug("Resultado de inserción: ");
    dumpDebug($product_insert_result);

    return $db->insert_id;
}

function updateCategoryErpProduct($db, $product)
{
    $product_table = MAIN_DB_PREFIX . "product";

    $product_check_query
        =   "SELECT rowid, ref
                FROM $product_table
            WHERE
                barcode = '$product->barcode'";

    printDebug("Revisando existencia de producto: " . $product_check_query);

    $product_check_result = $db->query($product_check_query);

    printDebug("Resultado de chequeo: ");
    dumpDebug($product_check_result);

    $product_id = 0;

    // El producto existe?
    if ($product_check_result->num_rows > 0) {
        $product_update_query
            =   "UPDATE $product_table SET
                    label = '$product->label',
                    weight = $product->weight
                WHERE
                    barcode = '$product->barcode'";

        printDebug("Actualizando producto: " . $product_update_query);

        $product_update_result = $db->query($product_update_query);

        printDebug("Resultado de actualización: ");
        dumpDebug($product_update_result);

        $product_id = $db->insert_id;

        printDebug("ID cambiado a: " . $product_id);

    } else {

        printDebug("Creando nueva instancia del producto en la base de datos");

        createErpCategoryProduct($db, $product);
    }

    if ($product_id != 0) {
        updateErpCategoryProductCategories($db, $product, $product_id);
        return true;
    } else {

        printDebug("ERROR: El ID de producto era 0 ( No se creo ni se actualizo, esto no deberia pasar )");

        return false;
    }

}

/**
 * Generar relación de indices en base a las columnas
 * originales y las columnas reportadas por el CSV
 *
 * @param array $csvNames Array de columnas reportadas por el CSV
 *
 * @return array|false Relación de columnas de acuerdo a las columnas del CSV
 * o falso en caso de que no se puedan relacionar todas las columnas
 * @throws ReflectionException
 */
function generateDataRelations($csvNames)
{
    $original_disposition = ColumnEnum::getConstants();

    $result_disposition = array();

    $column_index = 0;

    foreach ($csvNames as $column) {
        $column_id = idFromTranslation($column);
        if ($column_id === false) {
            $column_id = $column;
        }

        $column_id = strtoupper($column_id);

        if (array_key_exists($column_id, $original_disposition)) {
            $result_disposition[strtolower($column_id)] = $column_index;
        }
        $column_index++;
    }

    return $result_disposition;
}

/**
 * Generar array asociativo de acuerdo a datos de archivo de categorizacion
 *
 * @param array $csvNames Valores de nombre del archivo a importar
 *
 * @return array Diccionario de valores de categorización
 */
function generateSupplierDataRelations($csvNames)
{
    //hardcoded values
    return array(
        'barcode' => 0,
        'label' => 1,
        'parentCategory' => 4,
        'childCategory' => 5,
        'brand' => 7,
        'model' => 8,
        'fromYear' => 9,
        'toYear' => 10,
        'weight' => 12
    );
}



/**
 * Procesar un archivo subido para importarlo
 *
 * @return int Numero de objetos procesados
 * @throws ReflectionException
 */
function importFile($user)
{
    $count_updated = 0;

    $file_uploaded = checkImportFile();
    if ($file_uploaded) {

        $handle = fopen(__EXPIMP__TEMP_FILE_PATH, 'r');

        $erpDB = connectDB(); $storeDB = connectStoreDB();
        //We'll try to avoid data truncated on updates caused by STRICT_TRANS_TABLES
        $original_sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";
        $temp_sql_mode = "NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";

        $index = 0;

        $csv_names = fgetcsv($handle, 1000, ',');


        printDebug("Generando relaciones data-column:");

        $column_relations = generateDataRelations($csv_names);

        printDebug("Relaciones generadas:");
        dumpDebug($column_relations);
        $erpDB->query("SET SESSION sql_mode = '" . $temp_sql_mode . "'");
        $storeDB->query("SET SESSION sql_mode = '" . $temp_sql_mode . "'");
        // Procesar cada linea de CSV
        while ($reg = fgetcsv($handle, 6000, ',')) {

            printDebug("PROCESANDO PRODUCTO ------------------------------------");

            $product_template = createProductTemplate($reg, $column_relations);

            printDebug("Template de archivo generado:");
            dumpDebug($product_template);

            if (empty($product_template)) {
                break;
            }

            $update_result = updateErpProduct($erpDB, $product_template, $user);
            $update_result2 = updateOnlineStoreProduct($storeDB, $product_template, $user);
            
            printDebug("Resultado de actualización: " . $update_result);

            if ($update_result) {
                $count_updated++;

                /*if ($count_updated == 3) {
                    print("Bloqueo de seguridad activado, solo se actualizaron 3 productos");

                    return $count_updated;
                }*/
            }
            unset($product_template);
            ++$index;
        }
        $erpDB->query("SET SESSION sql_mode ='" . $original_sql_mode . "'");
        $storeDB->query("SET SESSION sql_mode ='" . $original_sql_mode . "'");

    }

    return $count_updated;
}

/**
 * Procesar un archivo subido para importarlo
 *
 * @return int Numero de objetos procesados
 */
function importSupplierCategorization()
{
    $count_updated = 0;

    $file_uploaded = checkImportFile();
    if ($file_uploaded) {

        $handle = fopen(__EXPIMP__TEMP_FILE_PATH, 'r');

        $erpDB = connectDB();

        $csv_names = fgetcsv($handle, 1000, ',');

        $column_relations = generateSupplierDataRelations($csv_names);

        // Procesar cada linea de CSV
        while ($reg = fgetcsv($handle, 6000, ',')) {

            printDebug("PROCESANDO PRODUCTO -----------------------------------");

            $product_template = createCategoryProductTemplate($reg, $column_relations);

            printDebug("Resultado de template generado: ");

            dumpDebug($product_template);

            $update_result = updateCategoryErpProduct($erpDB, $product_template);

            if ($update_result) {
                $count_updated++;
            }
        }

    }

    return $count_updated;
}

/**
 * Exportar un archivo con los productos de la base de datos
 *
 * @param int $start Producto en el cual iniciar ( Inclusivo )
 * @param int $end   Producto en el cual terminar ( Inclusivo )
 *
 * @return null
 */
function exportFile($start, $end)
{
    $erpDB = connectDB();

    // TODO:
    // Los valores del query no estan sincronizados con las constantes
    // establecidas en ColumnEnum, seria bueno sincronizarlas para
    // tener un solo punto de cambios

    $count = ($end - $start) + 1;
    $offset = $start - 1;

    $select_query =     "SELECT
                            rowid,
                            ref,
                            label,
                            description,
                            note,
                            customcode,
                            price,
                            price_ttc,
                            price_min,
                            price_min_ttc,
                            barcode,
                            partnumber,
                            weight,
                            length,
                            surface,
                            volume,
                            stock,
                            supplier
                        FROM "
                            . MAIN_DB_PREFIX . "product
                        LIMIT $count
                        OFFSET $offset";

    $select_result = $erpDB->query($select_query);

    if ($select_result->num_rows > 0) {

        $exportSpreadsheet = new PHPExcel();
        $exportSpreadsheet->getProperties()
            ->setCreator('Lion ERP ExpImp')
            ->setLastModifiedBy('Lion ERP ExpImp')
            ->setTitle('Productos exportados - Lion ERP')
            ->setSubject('Productos exportados')
            ->setDescription('Datos exportados sobre los productos dentro del ERP');

        $exportSpreadsheet->setActiveSheetIndex(0);

        $column_names = ColumnEnum::getConstants();
        $column_names_keys = array_keys($column_names);
        $column_names_values = array_values($column_names);

        $col = 0;
        foreach ($column_names_keys as $column) {
            $column_lowercase = strtolower($column);

            $column_translated = translateId($column_lowercase);

            $exportSpreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow($col, 1, $column_translated);
            $col++;
        }

        $row_index = 2;
        while ($row = $select_result->fetch_row()) {
            foreach ($column_names_values as $column_index) {
                $exportSpreadsheet->getActiveSheet()
                    ->setCellValueByColumnAndRow(
                        $column_index,
                        $row_index,
                        $row[$column_index]
                    );
            }
            $row_index++;
        }

        $exportSpreadsheet->setActiveSheetIndex(0);
        ob_clean();
        $objWriter = PHPExcel_IOFactory::createWriter($exportSpreadsheet, 'CSV');
        $objWriter->setDelimiter(',');

        // Sending headers to force the user to download the file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Products_(' . $start . '-' . $end . ')_'.date('dMy').'.csv"');
        header('Cache-Control: max-age=0');

        $objWriter->save('php://output');
        exit;
    }
}

/**
 * Obtener el numero de productos en la base de datos
 *
 * @return int Numero de productos
 */
function getProductCount()
{
    $erpDB = connectDB();
    $product_table_name = MAIN_DB_PREFIX . "product";

    $count_query
        = "SELECT COUNT(rowid) FROM
            $product_table_name";

    $count_result = $erpDB->query($count_query);
    $count_result_arr = $count_result->fetch_array();

    return $count_result_arr[0];
}

/**
 * @param $db mysqli
 * @param $files array Nombres de archivo a procesar
 * @return int Numero de imagenes procesadas exitosamente
 */
function processProductImages($db, $files)
{
    global $conf;
    $success_count = 0;

    foreach ($files as $file) {

        $filename = pathinfo($file, PATHINFO_FILENAME);

        printDebug("Procesando archivo: " . $filename);

        $filename_parts = explode('_', $filename,2);
        $o_prod_id = $filename_parts[0];
        $product_id_str = str_replace('-','/',$o_prod_id);
        if ($product_id_str) {
            if (is_numeric($product_id_str)) {
                $product_id = intval($product_id_str);
            }else{
                $product_id = $product_id_str;
            }

            printDebug("ID generado: ".$o_prod_id);
            dumpDebug($product_id);

            $product_table = MAIN_DB_PREFIX . "product";
            $check_product_query
                =   "SELECT rowid,ref FROM
                        $product_table
                     WHERE
                        rowid = '$product_id'
                     OR
                        ref = '$product_id'
                     OR
                        old_ref = '$product_id'
                     OR
                        barcode = '$product_id'
                     OR
                        rowid = '$o_prod_id'
                     OR
                        ref = '$o_prod_id'
                     OR
                        old_ref = '$o_prod_id'
                     OR
                        barcode = '$o_prod_id'
                     ";

            printDebug("Revisando producto con el siguiente query:".$check_product_query);
            dumpDebug($check_product_query);

            $check_product_result = $db->query($check_product_query);

            //printDebug("Resultado del query:".var_dump($check_product_result->num_rows));
            dumpDebug($check_product_result);

            if ($product = $check_product_result->fetch_object()) {
                printDebug("Generando path a imagenes de producto");
                $product_id_str = strval($product->rowid);
                $product_id_len = strlen($product_id_str);

                /*print $product_id_str;
                print $product_id_len;*/

                $last_digit = $product_id_str[$product_id_len - 1];
                $penultimate_digit = 0;
                if ($product_id_len > 1) {
                    $penultimate_digit = $product_id_str[$product_id_len - 2];
                }

                /*$images_dir
                    =   $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
                        "documents" . DIRECTORY_SEPARATOR .
                        "produit" . DIRECTORY_SEPARATOR .
                        $last_digit . DIRECTORY_SEPARATOR .
                        $penultimate_digit . DIRECTORY_SEPARATOR .
                        $product_id_str . DIRECTORY_SEPARATOR .
                        "photos" . DIRECTORY_SEPARATOR;*/

                $images_dir = $conf->product->multidir_output[1];
                $rc_dir = get_exdir($product->rowid,2,1,0,$product,'produit');
                $images_dir .= '/'. $rc_dir . str_replace('/','_',$product->ref) ."/";
                

                dol_mkdir($images_dir);

                printDebug("Directorio de imagenes:");
                dumpDebug($images_dir);

                $dir_osencoded=$images_dir;

                if (is_dir($dir_osencoded)) {
                    printDebug("El directorio existe! Procediendo...");

                    $new_filename = $images_dir . pathinfo($file, PATHINFO_BASENAME);

                    $dir_output = '/'.$rc_dir.str_replace('/','_',$product->ref).'/'.pathinfo($file, PATHINFO_BASENAME);;

                    $sql = 'UPDATE '.MAIN_DB_PREFIX.'product SET has_photo = 1, dir_output = "'.$dir_output.'" WHERE rowid = '.$product->rowid;

                          $result = $db->query($sql);

                    printDebug("Nuevo nombre para archivo:" . $new_filename);

                    printDebug("Moviendo archivo de imagen...");

                    if (is_file($new_filename)) {
                        printDebug("WARNING: El archivo <b> $new_filename </b> ya existe, remplazando...");
                        unlink($new_filename);
                    }

                    $move_result = rename($file, $new_filename);

                    if ($move_result) {
                        printDebug("Se logro mover el archivo, continuando...");
                        
                        addFileIntoDatabaseIndex($images_dir,pathinfo($file, PATHINFO_BASENAME),'',null,1);
                        global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;
                        include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
                        if (image_format_supported($new_filename)  == 1)
                        {

                            vignette($new_filename, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
                            vignette($new_filename, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

                        }
                        $success_count++;
                    } else {
                        printDebug("ERROR: No se pudo mover el archivo");
                    }

                } else {
                    printDebug("WARNING: El directorio de imagenes ( $images_dir ) no existe, por seguridad no se procedera");
                }

            } else {
                printDebug("ERROR: El producto referenciado ".$product_id." no existe");
            }
        } else {
            printDebug("WARNING: El archivo provisto ( $file ) parece no tener un formato valido, omitiendo...");
        }
    }

    return $success_count;
}

/**
 * Importar imagenes de productos de acuerdo a ZIP
 *
 * @return null
 */
function importProductImages()
{
	global $db;
    $zip_uploaded = checkImportZip();
    if ($zip_uploaded) {
        //$erpDB = connectDB();

        $za = new ZipArchive();

        $za->open(__EXPIMP__TEMP_ZIP_PATH);

        printDebug("Archivo a extraer:");
        dumpDebug($za);

        printDebug("Descomprimiendo el archivo ZIP...");

        printDebug("Revisando directorio de extracción");
        dumpDebug(__EXPIMP__TEMP_ZIP_FILES_PATH);
        
        rcImpCheckDir(__RC_EXPIMP__TEMP_ZIP_PATH_BASE);


        if (!is_dir(__EXPIMP__TEMP_ZIP_FILES_PATH)) {
            printDebug("El directorio no existe, creando...");

            mkdir(__EXPIMP__TEMP_ZIP_FILES_PATH);
        }
        if (!is_dir(__EXPIMP__TEMP_ZIP_FILES_PATH)) {
            printDebug("No se pudo crear el directorio.");
        }
        if (!is_writable(__EXPIMP__TEMP_ZIP_FILES_PATH))
        {
            printDebug("No se puede escribir en el directorio");
        }

        printDebug("Extrayendo archivos desde ZipArchive");
        $za->extractTo(__EXPIMP__TEMP_ZIP_FILES_PATH);

        printDebug("Generando nombres de archivos de imagen..");
        $t_files = glob(__EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR . '*.{jpg,JPG,png,jpeg,PDF,pdf}', GLOB_BRACE);
        $p_files = array();
        
        foreach($t_files as $k => $v)
        {
        	$fparts = pathinfo($t_files[$k]);
        	if (strpos($fparts['filename'],',')===false)
        	{
        		$p_files[] = $v ;
        	}
        	else
        	{
        		$tcfiles = explode(',',$fparts['filename']);
        		foreach($tcfiles as $tcfile)
        		{
        			//$newFile = __EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR.$tcfile.'_'.uniqid().'.'.$fparts['extension'];
        			$newFile = __EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR.$tcfile.'.'.$fparts['extension'];
        			if (!copy($v,$newFile))
        			{
        				printDebug('ERROR: No se pudo crear el archivo '.$newFile);
        			}
        			else
        			{
        				$p_files[] = $newFile;
        			}
        		}
        		unlink($v);
        	}
        }
        $files = $p_files;
        

        printDebug("Imprimiendo nombres de archivo:");
        dumpDebug($files);

        printDebug("Procesando archivos de imagenes");
        $sucess_count = processProductImages($db, $files);


        printDebug("Eliminando archivos restantes...");
        foreach($files as $file) {
            if (is_file($file)) {
                printDebug("Eliminando archivo " . $file);
                unlink($file);
            }
        }

        return $sucess_count;

    } else {
        print("No fue importado el zip");
    }
}


/**
 * Importar imagenes de productos de acuerdo a ZIP
 *
 * @return null
 */
function importProductTech()
{
	global $db;
    $zip_uploaded = checkImportZip();
    if ($zip_uploaded) {
        //$erpDB = connectDB();

        $za = new ZipArchive();

        $za->open(__EXPIMP__TEMP_ZIP_PATH);

        printDebug("Archivo a extraer:");
        dumpDebug($za);

        printDebug("Descomprimiendo el archivo ZIP...");

        printDebug("Revisando directorio de extracción");
        dumpDebug(__EXPIMP__TEMP_ZIP_FILES_PATH);
        
        rcImpCheckDir(__RC_EXPIMP__TEMP_ZIP_PATH_BASE);


        if (!is_dir(__EXPIMP__TEMP_ZIP_FILES_PATH)) {
            printDebug("El directorio no existe, creando...");

            mkdir(__EXPIMP__TEMP_ZIP_FILES_PATH);
        }
        if (!is_dir(__EXPIMP__TEMP_ZIP_FILES_PATH)) {
            printDebug("No se pudo crear el directorio.");
        }
        if (!is_writable(__EXPIMP__TEMP_ZIP_FILES_PATH))
        {
            printDebug("No se puede escribir en el directorio");
        }

        printDebug("Extrayendo archivos desde ZipArchive");
        $za->extractTo(__EXPIMP__TEMP_ZIP_FILES_PATH);

        printDebug("Generando nombres de archivos de imagen..");
        $files = glob(__EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR . '*.{pdf,PDF}', GLOB_BRACE);

        printDebug("Imprimiendo nombres de archivo:");
        dumpDebug($files);

        printDebug("Procesando archivos de imagenes");
        die (var_dump($files));
        foreach($files as $file) 
        {
            if (strpos($file,',') !==false)
            {
                $filewoe = substr($file,0,strlen($file)-strripos($file,'.'));
                $refs = explode(',',$filewoe);
                foreach($refs as $fref)
                {
                    
                }
            }
            if (is_file($file)) {
                printDebug("Eliminando archivo " . $file);
                unlink($file);
            }
        }
        $sucess_count = processProductImages($db, $files);

        printDebug("Eliminando archivos restantes...");
        foreach($files as $file) {
            if (is_file($file)) {
                printDebug("Eliminando archivo " . $file);
                unlink($file);
            }
        }

        return $sucess_count;

    } else {
        print("No fue importado el zip");
    }
}

function rcImpCheckDir($path)
{
	$path = str_replace('/','\\',$path);
	$path = str_replace('\\','/',$path);
	$dirs = explode('/',$path);
	$home = '';
	$ret  = true;
	foreach($dirs as $dir)
	{
		$home .= $dir.DIRECTORY_SEPARATOR;
		
		printDebug("Revisando el directorio {$home}.");
		if (!is_dir($home)) 
		{
			printDebug("El directorio {$home} no existe, creando...");
            mkdir($home);
        }
        if (!file_exists($home)) 
		{
            printDebug("No se pudo crear el directorio.");
            $ret = false;
        }
	}
    if (!is_writable($home))
    {
        printDebug("No se puede escribir en el directorio");
        $ret = false;
    }
    return $ret;
}


function processInvoicePdfs($db, $files)
{
    global $conf;
    $success_count = 0;

    foreach ($files as $file) {

        $filename = pathinfo($file, PATHINFO_FILENAME);

        printDebug("Procesando archivo: " . $filename);

        $filename_parts = explode('_', $filename,2);
        $o_prod_id = $filename_parts[0];
        $product_id_str = str_replace(substr($o_prod_id,0,13),'',$o_prod_id);
        if ($product_id_str) {
            if (is_numeric($product_id_str)) {
                $product_id = intval($product_id_str);
            }else{
                $product_id = $product_id_str;
            }

            printDebug("ID generado: ".$o_prod_id);
            dumpDebug($product_id);

            $product_table = MAIN_DB_PREFIX . "facture";
            $check_product_query
                =   "SELECT rowid,ref FROM
                        $product_table
                     WHERE
                        rowid = '$product_id'
                     OR
                        ref = '$product_id'
                     OR
                        ref_client LIKE '$product_id%'
                     OR
                        rowid = '$o_prod_id'
                     OR
                        ref = '$o_prod_id'
                     OR
                        ref_client = '$o_prod_id'
                     ";

            printDebug("Revisando producto con el siguiente query:".$check_product_query);
            dumpDebug($check_product_query);

            $check_product_result = $db->query($check_product_query);

            //printDebug("Resultado del query:".var_dump($check_product_result->num_rows));
            dumpDebug($check_product_result);

            if ($product = $check_product_result->fetch_object()) {
                printDebug("Generando path a imagenes de producto");
                $product_id_str = strval($product->rowid);
                $product_id_len = strlen($product_id_str);

                /*print $product_id_str;
                print $product_id_len;*/

                $last_digit = $product_id_str[$product_id_len - 1];
                $penultimate_digit = 0;
                if ($product_id_len > 1) {
                    $penultimate_digit = $product_id_str[$product_id_len - 2];
                }

                /*$images_dir
                    =   $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
                        "documents" . DIRECTORY_SEPARATOR .
                        "produit" . DIRECTORY_SEPARATOR .
                        $last_digit . DIRECTORY_SEPARATOR .
                        $penultimate_digit . DIRECTORY_SEPARATOR .
                        $product_id_str . DIRECTORY_SEPARATOR .
                        "photos" . DIRECTORY_SEPARATOR;*/

                $images_dir = $conf->facture->multidir_output[1];
                $rc_dir = get_exdir($product->rowid,2,1,0,$product,'produit');
                $images_dir .= '/'. $rc_dir . str_replace('/','_',$product->ref) ."/";
                

                dol_mkdir($images_dir);

                printDebug("Directorio de imagenes:");
                dumpDebug($images_dir);

                $dir_osencoded=$images_dir;

                if (is_dir($dir_osencoded)) {
                    printDebug("El directorio existe! Procediendo...");

                    $new_filename = $images_dir . pathinfo($file, PATHINFO_BASENAME);

                    $dir_output = '/'.$rc_dir.str_replace('/','_',$product->ref).'/'.pathinfo($file, PATHINFO_BASENAME);;

                    $sql = 'UPDATE '.MAIN_DB_PREFIX.'product SET has_photo = 1, dir_output = "'.$dir_output.'" WHERE rowid = '.$product->rowid;

                          $result = $db->query($sql);

                    printDebug("Nuevo nombre para archivo:" . $new_filename);

                    printDebug("Moviendo archivo de imagen...");

                    if (is_file($new_filename)) {
                        printDebug("WARNING: El archivo <b> $new_filename </b> ya existe, remplazando...");
                        unlink($new_filename);
                    }

                    $move_result = rename($file, $new_filename);

                    if ($move_result) {
                        printDebug("Se logro mover el archivo, continuando...");
                        
                        addFileIntoDatabaseIndex($images_dir,pathinfo($file, PATHINFO_BASENAME),'',null,1);
                        global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;
                        include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
                        if (image_format_supported($new_filename)  == 1)
                        {

                            vignette($new_filename, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
                            vignette($new_filename, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");

                        }
                        $success_count++;
                    } else {
                        printDebug("ERROR: No se pudo mover el archivo");
                    }

                } else {
                    printDebug("WARNING: El directorio de imagenes ( $images_dir ) no existe, por seguridad no se procedera");
                }

            } else {
                printDebug("ERROR: El producto referenciado ".$product_id." no existe");
            }
        } else {
            printDebug("WARNING: El archivo provisto ( $file ) parece no tener un formato valido, omitiendo...");
        }
    }

    return $success_count;
}


/**
 * Importar imagenes de productos de acuerdo a ZIP
 *
 * @return null
 */
function importInvoicePdfs()
{
	global $db;
    $zip_uploaded = checkImportZip();
    if ($zip_uploaded) {
        //$erpDB = connectDB();

        $za = new ZipArchive();

        $za->open(__EXPIMP__TEMP_ZIP_PATH);

        printDebug("Archivo a extraer:");
        dumpDebug($za);

        printDebug("Descomprimiendo el archivo ZIP...");

        printDebug("Revisando directorio de extracción");
        dumpDebug(__EXPIMP__TEMP_ZIP_FILES_PATH);
        
        rcImpCheckDir(__RC_EXPIMP__TEMP_ZIP_PATH_BASE);


        if (!is_dir(__EXPIMP__TEMP_ZIP_FILES_PATH)) {
            printDebug("El directorio no existe, creando...");

            mkdir(__EXPIMP__TEMP_ZIP_FILES_PATH);
        }
        if (!is_dir(__EXPIMP__TEMP_ZIP_FILES_PATH)) {
            printDebug("No se pudo crear el directorio.");
        }
        if (!is_writable(__EXPIMP__TEMP_ZIP_FILES_PATH))
        {
            printDebug("No se puede escribir en el directorio");
        }

        printDebug("Extrayendo archivos desde ZipArchive");
        $za->extractTo(__EXPIMP__TEMP_ZIP_FILES_PATH);

        printDebug("Generando nombres de archivos de imagen..");
        $t_files = glob(__EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR . '*.{jpg,JPG,png,jpeg,PDF,pdf}', GLOB_BRACE);
        $p_files = array();
        
        foreach($t_files as $k => $v)
        {
        	$fparts = pathinfo($t_files[$k]);
        	if (strpos($fparts['filename'],',')===false)
        	{
        		$p_files[] = $v ;
        	}
        	else
        	{
        		$tcfiles = explode(',',$fparts['filename']);
        		foreach($tcfiles as $tcfile)
        		{
        			//$newFile = __EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR.$tcfile.'_'.uniqid().'.'.$fparts['extension'];
        			$newFile = __EXPIMP__TEMP_ZIP_FILES_PATH . DIRECTORY_SEPARATOR.$tcfile.'.'.$fparts['extension'];
        			if (!copy($v,$newFile))
        			{
        				printDebug('ERROR: No se pudo crear el archivo '.$newFile);
        			}
        			else
        			{
        				$p_files[] = $newFile;
        			}
        		}
        		unlink($v);
        	}
        }
        $files = $p_files;
        

        printDebug("Imprimiendo nombres de archivo:");
        dumpDebug($files);

        printDebug("Procesando archivos de imagenes");
        $sucess_count = processInvoicePdfs($db, $files);


        printDebug("Eliminando archivos restantes...");
        foreach($files as $file) {
            if (is_file($file)) {
                printDebug("Eliminando archivo " . $file);
                unlink($file);
            }
        }

        return $sucess_count;

    } else {
        print("No fue importado el zip");
    }
}

