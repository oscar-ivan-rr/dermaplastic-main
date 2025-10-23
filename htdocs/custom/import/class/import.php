<?php
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/product.template.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/societe.template.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/suppliers.template.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/compta.template.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/pagar.template.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';




class ImportExcel
{
    /**
     * @var int numero de inserciones que se hicieron al importar los datos
     */
    public $inserts;

    /**
     * @var int numero de actulizaciones que se hicieron al importar los datos
     */
    public $updates;

    /**
     * @var int numero de errores
     */
    public $errors;

    /**
     * @var Template
     */
    public $template;

    /**
     * @var Gestor
     */
    public $gestor;

    private $delimiter;

    /**
     *	Constructor
     *
     *  @param	DoliDB	$db             Database handler
     *  @param	int		$type           Tipo de importación
     *  @param  String	$nameFile       Nombre del archivo csv
     *  @param  char    $delimiter      Delimitador del archivo
     *  @param  User    $user           Usuario de dolibarr
     *  @param  float   $currencyRate   Tasa de cambio para productos solamente
     *  @param  string  $currency       Moneda precio del producto
     */
    public function __construct($db,$type,$nameFile, $delimiter, $user, $currencyRate = 1, $currency = 'MXN')
    {
        $this->inserts   = 0;
        $this->updates   = 0;
        $this->errors    = 0;
        $this->delimiter = $delimiter;

        //CSV
        if (false === $this->gestor = fopen($nameFile, "r"))
        {
        	die('No se puede leer el archivo '.$nameFile);
        }

        // Seleccionar plantilla
        $this->selectTypeObject($db, $user, $type, $currencyRate, $currency);
    }

    /**
     *	initColumns
     *
     * Funcion para obtener las columnas que viene en el archivo
     */
    private function initColumns()
    {
        // Permitir UTF8 en csv
        setlocale(LC_ALL, 'es_MX.iso88591');

        $datos = fgetcsv($this->gestor, 0, $this->delimiter);

        $it=0;
        foreach ($datos as $data) {
            //Codificamos el valor
            if(get_class($this->template) != 'ProductTemplate' && mb_detect_encoding($data) == 'UTF-8') {
                if (!strpos($data, 'á') && !strpos($data, 'é') && !strpos($data, 'í') && !strpos($data, 'ó') && !strpos($data, 'ú') &&
                    !strpos($data,'Á') && !strpos($data,'É') && !strpos($data,'Í') && !strpos($data,'Ó') && !strpos($data,'Ú') &&
                    !strpos($data,'Ñ') && !strpos($data,'ñ'))
                    $data = utf8_encode($data);
            }
            elseif(get_class($this->template) == 'ProductTemplate' && (true || mb_detect_encoding($data) != 'UTF-8'))
            {
            	$data = utf8_encode($data);
            }
            	
            $value = trim(dol_strtolower($data));
            foreach ($this->template->relation as $base_type => $array) {
                // Se obtiene la llave de la relación de columna con la propiedad del objeto
                $key = array_search($value, $array);
                // Si existe la llave se almacena su columna relacionada en su array especifico
                if ($key !== false) {
                    $this->template->setPropertyWithCell($key, $it, $base_type);
                    break;
                }
            }
            $it++;
        }
   }

    /**
     *	selectTypeObject
     *
     * Funcion para inicializar el tipo de plantilla
     * 
     * @param   DoliDb  $db             Database handler
     * @param   User    $user           Usuario de dolibarr
     * @param   int     $type           Tipo de plantilla
     * @param   float   $currencyRate   Tasa de cambio para productos solamente
     * @param   string  $currency       Moneda precio del producto
     */
    private function selectTypeObject(DoliDB $db, User $user, $type, $currencyRate, $currency)
    {
        switch ($type)
        {
            case 1:
                $this->template = new ProductTemplate($db, $user, $currencyRate, $currency);
                break;
            case 2:
                $this->template = new SocieteTemplate($db,$user,$type);
                break;
            case 3:
                $this->template = new SuppliersTemplate($db,$user,$type);
                break;
            case 4:
                $this->template = new ComptaTemplate($db,$user,$type);
                break;
            case 5:
                $this->template = new PagarTemplate($db,$user,$type);
                break;
        }
    }

    /**
     *	ImportData
     *
     * Funcion para leer el archivo Excel para la importación
     * @return  array     Array with info (insrts, updates, warnings, erros, msgs).
     */
    public function importData()
    {
        $this->initColumns();

        while (($register = fgetcsv($this->gestor, 1000, $this->delimiter)) !== FALSE) {

            // Asignación de propiedades
            
            $this->template->setProperties($register);
            $status = $this->template->createObject();
            if ($status == 1) $this->inserts++;
            elseif ($status == 2) $this->updates++;
            else { // TODO: Catch error
                $this->errors++;
            }
        }
         
        

        fclose($this->gestor);

        $warnings = (!empty($this->template->errors['warning'])) ? sizeof($this->template->errors['warning']) : 0;

        return array('inserts' => $this->inserts, 'updates' => $this->updates, 'warnings' => $warnings, 'errors' => $this->errors, 'msgs' => $this->template->errors);
    }
    /**
     *	CoincidenceRef
     *
     * Funcion para determinar el porcentaje de coincidencia entre ref vieja y nueva del archivo Excel para la importación
     * @return
     */
    public function CoincidenceRef()
    {
        $this->initColumns();
        $data = array();

        while (($register = fgetcsv($this->gestor, 1000, $this->delimiter)) !== FALSE) {

            // Asignación de propiedades
            $this->template->setProperties($register);
            $products = $this->template->getAllRefProducts();
            $data[] = $this->template->SearchCoincidence($products);
        }

        fclose($this->gestor);
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        setlocale(LC_ALL, 'es-MX.utf-8');
        header("Content-disposition: attachment; filename=\"coincidencias.csv\"");
        $outputBuffer = fopen("php://output", 'w');
        fputcsv($outputBuffer,array('Porcentaje (%)','Ref. vieja','Ref. nueva','Etiqueta'), ",");
        foreach ($data as $key => $searchs) {
            fputcsv($outputBuffer, $searchs, ",");
        }
        fclose($outputBuffer);
        exit();
    }
}