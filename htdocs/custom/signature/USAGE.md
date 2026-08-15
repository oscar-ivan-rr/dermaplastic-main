# Módulo de firma digital
Se creó un módulo de firma digital para ser utilizado en los PDF's requeridos en el sistema, de manera que no se tenga que repetir código constantemente al tener que firmar otro modelo de pdf.

> Si se desea conocer el funcionamiento de los archivos, se encuentran en htdocs/custom/signature/

Por defecto al llamar este archivo, se redirige a la misma página en la que se está junto con dos parámetros: 
- `$id`: ID del objeto desde el cuál se llamo al módulo de firma.
- `$saved`: Con un valor de 0 o 1, indicando si el proceso se realizó saitsfactoriamente (1) o no (0). Este parámetro podrá ser utilizado si se ocupa mandar una alerta personalizada.

> Se puede forzar el redireccionamiento a una url personalizada, vea la variable opcional `$moreinputs`.

#### Forma de usar
Para poder utilizar este módulo y que el documento requerido sea firmado, se deben de seguir ciertas reglas.

###### Variables
Existen variables obligatorias y opcionales para este módulo.
- Obligatorias:
  - `$id`: ID del objeto.
  - `$ref`: Utilizado para la etiqueta `<title>`, el título sería FIRMA - `$ref`. 
  - `$element`: Objeto a crear para gener su documento PDF con `$object = new $element($db)`.
  - `$objectfile`: Ruta del archivo class del objeto a crear.
  - `$dir`: Directorio para almacenar la firma (recomendable ser igual al del documento).
- Opcionales:
  - `$model_pdf`: Modelo PDF a generar, si no se proporciona se utiliza el default de del objeto.
  - `$params`: Parámetros necesarios al redirigir cuando el documento sea firmado (`archivo.php?id=1&param=1&extra=1`).
  - `$moreinputs`: Inputs extras que necesitan enviárse al archivo ajax para firmar el documento.
    - Existen dos grupos de inputs que modifican el redireccionamiento a una url en específico, los cuales utilizan un input en común `'force_redirect' => 1`, el cuál indica que el redireccionamiento será diferente al por defecto:
      - Redireccionar al documento previamente generado y visualizarlo en pantalla, para esta ruta se necesitan los siguientes inputs `array('force_redirect' => 1, 'url_doc' => 1, 'modulepart' => 'xxxx', 'relativepath' => "xxxx/yyy.pdf")`:
        - `url_doc`: Indica que la ruta será la visualización del documento generado.
        - `modulepart`: Modulo al que pertence el objeto.
        - `relativepath`: Ruta relativa del documento:
          - `xxx`: Carpeta del documento, normalemnte es la misma referencia.
          - `yyy`: Nombre del archivo, normalmente la misma referencia.
      - Redireccionar a una ruta totalmente personalizada `array('force_redirect' => 1, 'url' => 'xxxxxxxxx')`:
        - `url`: Ruta a la cual se redireccionará al crear el documento firmado. Si se utiliza la variable `params`, estos parámetros extra también son incrustados en en `url` para su correcto redireccionamiento.

###### Invocación
Se ocupa crear un enlace o botón que para redirigir al mismo archivo php preferiblemente (por ejemplo `$_SERVER['PHP_SELF']`)  con el parámetro `action=digital_sign`.
```php
# Ejemplo, utilizar $conf->global->MAIN_SIGNATURE_MODULE como bandera, debe estar en 1 para poder usar el módulo.
if ($object->statut == 1 && $conf->global->MAIN_SIGNATURE_MODULE) {
    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=digital_sign">'.$langs->trans("Signature").'</a>';
}
```
> El botón puede ser de la forma que se desea, el anterior es un ejemplo empleado en un caso.

En esta acción se invocará al archivo de la siguiente manera:
```php
include (DOL_DOCUMENT_ROOT .'/custom/signature/index.php');
die();
```
> Al realizar el documento firmado, se redirige al archivo actual, con el parámetro `id=$id` siempre.

#### Ejemplos
###### Simple
El siguiente es un ejemplo de firma de una nota de recepción de productos en ***htdocs/livraison/card.php***. 
```php
# Se utiliza $conf->global->MAIN_SIGNATURE_MODULE como bandera, debe estar en 1
if ($action == 'digital_sign' && $conf->global->MAIN_SIGNATURE_MODULE) {
    $ref = $object->ref; # Para el título.
    $element = "Livraison"; # Objecto a crear
    $objectfile = "/livraison/class/livraison.class.php"; # Archivo de clase de objeto
    $dir = $conf->expedition->dir_output."/receipt/" . $object->ref; # Directorio para almacenar la firma.
    
    # Invocación.
    include (DOL_DOCUMENT_ROOT .'/custom/signature/index.php');
    die();
}
```
En este caso solamente se utilizan las variables obligatorias a excepción de `$id`, ya que esta se define en la mayoría de las veces al inicio del archivo, si no es el caso, se debe de declarar.

###### Avanzado
El siguiente es un ejemplo más elaborado de firma de una transferencia interna calculada de stocks  ***htdocs/product/stock/movement_card.php***. 
```php
else if ($action == 'digital_sign' && $conf->global->MAIN_SIGNATURE_MODULE) {
    $entrepot = new Entrepot($db);
    # Se requiere construir $dir a partir de un objeto Entrepot.
    if ($entrepot->fetch('') > 0) {
        # Proceso necesario para $dir.
        $objectref = dol_sanitizeFileName($entrepot->ref);
        if (!empty($search_inventorycode)) $objectref .= "_".$id."_".$search_inventorycode;
        if ($search_type_mouvement) $objectref .= "_".$search_type_mouvement;

        # Variables obligatorias
        $ref = $objectref;
        $element = "MouvementStock";
        $objectfile = "/product/stock/class/mouvementstock.class.php";
        $dir = $conf->stock->dir_output . "/movement/" . $objectref;

        # Variables opcionales
        $model_pdf = "stdmouvement"; # Modelo PDF a utilizar si no es el default.
        $params = array("search_inventorycode=$search_inventorycode", "search_type_mouvement=$search_type_mouvement"); # Parametrós necesarios al redirigr.
        $moreinputs = array('search_inventorycode' => $search_inventorycode, 'search_type_mouvement' => $search_type_mouvement); # Inputs extra que se ponen en el formulario para ajax.

        unset ($entrepot);

        # Invocación
        include (DOL_DOCUMENT_ROOT .'/custom/signature/index.php');
        die();
    }
    # Se maneja un error ya que es necesario obtener el objeto Entrepot.
    else setEventMessage($langs->trans('ErrorSignatureArea'), 'errors');

    unset ($entrepot);
}
```
De igual manera que el ejemplo anterior, no se declara `$id` ya que está presente desde el inicio. En este caso se necesitó de un objeto externo a **MouvementStock** para construir el directorio de la firma. Se utilizaron las variables opcionales, `$model_pdf` para el PDF a firmar, `$params` para los parámetros necesarios al redirigir, en este ejemplo se redirige a `htdocs/product/stock/movement_card.php?id=1`, el `$id` siempre va, pero en este ejemplo se agregan dos parámetros más, quedando `htdocs/product/stock/movement_card.php?id=1&search_inventorycode=2439229&search_type_mouvement=0`, ya que es la vista que se debe mostrar al firmar dicho documento; por último `$moreinputs`, estos serán enviados al archivo ajax si se necesita en la creación del documento, em modelo utilizado aquí (stdmouvement) requería de parámetros por medio de `GETPOST()`, es por eso que se declaran.

#### Firma en doc
La firma siempre se guarda con el nombre **signature.png**. Para mostrarla en el modelo PDF se tiene el siguiente esquema.
```php
# Ruta de archivo.
$signature = $conf->expedition->dir_output."/receipt/" . $object->ref. "/signature.png";
# Si existe el archivo y se puede leer
if (file_exists($signature) && is_readable($signature)) {
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $dim = pdf_getHeightForLogo($signature, false, 22, true);

    # Aquí se le da un valor a $posx para centrar la firma respecto al ancho del documento, si no es el caso, se puede modificar el valor de la variable.
    $posx = ($this->page_largeur - $dim['width']) / 2; 
  	$posy = $pdf->GetY();
    # Se plasma la imagen en las coordenadas $posx y $posy, el 0 es para tomar el ancho automáticamente, el último parámetro la altura recomandada.
    $pdf->Image($signature, $posx, $posy, 0, $dim['height']);

    # Se elimina la firma para que no se quede en el sistema.
    dol_delete_file($signature, 0, 0, 0, $object, false, 1);
}
```
Esta es la manera de mostrarla en el PDF, si se requiere de más diseño ya es cuestión de jugar con los componentes del modelo.