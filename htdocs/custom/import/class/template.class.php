<?php
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

abstract class Template {

    /**
     * @var DoliDB
     */
    protected $db;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Array
     */
    public $relation;

    /**
     * @var Array
     */
    protected $propertyCells = array('base' => array(), 'extra' => array());

    /**
     * @var ExtraFields
     */
    protected $extrafields;

    /**
     * @var Array
     * Array para almacenar los extra fields definidos del objeto base
     */
    protected $extralabels;

    /**
     * @var Array
     * Array para almacenar la llave y valor de los extra fields base
     */
    protected $array_options = array();

    /**
     * @var Array
     */
    public $errors = array();

    /**
     * Constructor
     * 
     * @param   DoliDB  $db             Database handler
     * @param   User    $user           Usuario de dolibarr
     * @param   String  $table_element  Tipo de objeto
     */
    public function __construct(DoliDB $db, User $user, $table_element) {
        $this->db = $db;
        $this->user = $user;

        $this->extrafields = new ExtraFields($db);
        $this->extrafields->fetch_name_optionals_label($table_element); // Obtener extra fields del objeto correspondiente

        // Extra fields de objetos base
        if (is_array($this->extrafields->attributes[$table_element]['label']))
            $this->extralabels = $this->extrafields->attributes[$table_element]['label']; // Se almacenan lalve y label de extra fields

    }

    /**
     * createObject
     * 
     * Abstract function to create the template object
     */
    abstract protected function createObject();

    /**
     * setPropertyWithCell
     * 
     * Función para setear la columna correspondiende a los atributos de la plantilla
     * @param   String  $property   Template property
     * @param   int     $num_cell   Number of excel cell
     * @param   String  $base_type  Tipo de registro en propertyCelss ("base", "extra")
     */
    public function setPropertyWithCell($property, $num_cell, $base_type) {
        
        $this->propertyCells[$base_type][$num_cell] = $property;
        
    }

    /**
     * setProperties
     * 
     * Función para setear las propiedades de la plantilla con los valores del archivo excel.
     * @param   Array   $register   Array with column values of one row
     */
    public function setProperties($register) {
        foreach ($register as $col => $value) {
            $value = utf8_encode(trim($value));
            if (!strlen($value))
            {
            	$value = null;
            }			            

            // Ciclo para array propertyCells de "base" y "extra"
            foreach ($this->propertyCells as $base_type => $array) {
                //Si la columna existe en la relación de columnas-propeidades se almacena el valor en la propiedad correspondiente.
                if (array_key_exists($col, $array)) {
                    $key = $this->propertyCells[$base_type][$col];

                    if ($base_type == "base") // Variable tipo base
                        $this->{$key} = $value;
                    elseif ($base_type == "extra")  { // Variable tipo extra
                        $this->array_options["options_$key"] = $value;
                        if (array_key_exists($key, $this->extralabels)) // Eliminación de llave en $extralabels puesto que es una llave estática
                            unset($this->extralabels[$key]);
                    }
                    else
                    {
                    	$this->{$base_type}[$key] = $value; 
                    }
                    break;
                }
                else { // TODO: Bloque para extra fields dinámicos de excel, aquí se crearán si no existe dicho extra field.
    
                }
            }
        }
    }
}

?>