<?php

require_once DOL_DOCUMENT_ROOT.'/custom/import/class/template.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

class SuppliersTemplate extends Template {

    /**
     * @var Societe
     */
    public $societe;
    /**
     * @var Categorie
     */
    public $categorie;

    /**
     * type of societe
     */
    public $type;

    /**
     * Defined societe vars
     */
    public $code;
    public $status;
    public $name;
    public $address;
    public $phone;
    public $intnum;
    public $extnum;
    public $nbhood;
    public $location;
    public $town;
    public $state_id;
    public $capital;
    public $url;
    public $typeid;
    public $credit;
    public $days_credit;
    public $titular;
    public $bank;
    public $suc;
    public $numaccount;
    public $clabe;

    public $nickname;
    public $facturacion;
    public $siren;
    public $postal;
    public $correo;
    public $country;
    public $pago;
    public $terceros;
    public $importe;
    public $des_fijo;
    public $des_abs;
    public $des_pro;
    public $day_pro;
    public $categoria;

    /**
     * Constructor
     *
     * @param   DoliDB  $db     Database handler
     * @param   User    $user   Usuario de dolibarr
     */
    public function __construct(DoliDB $db, User $user,$type) {
        // Relación de propiedades con columnas de excel predefinidas
        $this->relation = array(
            'base' => array (
                'code' => 'clave',
                'status' => 'estatus',
                'name' => 'nombre',
                'nickname' => 'apodo',
                'facturacion' => 'facturacion',
                'siren'   => 'rfc',
                'address' => 'calle',
                'phone' => 'teléfono',
                'intnum' => 'número interior',
                'extnum' => 'número exterior',
                'nbhood' => 'colonia',
                'postal' => 'codigo postal',
                'location' => 'población',
                'town' => 'municipio',
                'state_id' => 'estado',
                'country'  => 'pais',
                'pago' => 'tipo de pago',
                'capital' => 'saldo',
                'correo'  => 'correo',
                'url' => 'página web',
                'terceros' => 'cliente potencial / cliente',
                'credit' => 'con crédito',
                'days_credit' => 'días de crédito',
                'importe' => 'limite de credito',
                'des_fijo' => 'descuento fijo %',
                'des_abs' => 'descuento absoluto $',
                'des_pro' => 'descuento pronto pago %',
                'day_pro' => 'dias de pronto pago',
                'categoria' => 'categoria',
                'titular' => 'titular',
                'bank' => 'banco',
                'suc' => 'sucursal (banco)',
                'numaccount' => 'num. de cuenta',
                'clabe' => 'clabe'
            ),
            'extra' => array(
            )
        );
        $this->type = $type;
        parent::__construct($db, $user, "societe");
    }

    /**
     * createObject
     *
     * Función para crear el societe o actualizar en la BD.
     */
    public function createObject() {
        global $conf;
        $this->societe = new Societe($this->db);
        $this->categorie = new Categorie($this->db);
        $res = $this->societe->fetch('', $this->name);
        //codigo de proveedor
        $module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
        if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
        {
            $module = substr($module, 0, dol_strlen($module) - 4);
        }
        $dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
        foreach ($dirsociete as $dirroot)
        {
            $res1 = dol_include_once($dirroot.$module.'.php');
            if ($res1) break;
        }
        $modCodeFournisseur = new $module;
        $tmpcode = $this->societe->code_fournisseur;
        if ((empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) || $this->societe->verify()<0) $tmpcode = $modCodeFournisseur->getNextValue($this->societe, 1);
        $this->societe->code_fournisseur = $tmpcode;
        //Con credito
        if(!empty($this->credit)){
            $credit = strtolower(trim($this->credit)) == 'si'?"credito":"contado";
            $sql="select id from ".MAIN_DB_PREFIX."c_typent where libelle = '".$credit."'";
            $resql = $this->db->query($sql);
            if($resql)
            {
                $typeS = $this->db->fetch_object($resql);
                $this->societe->typent_id = $typeS->id;
            }
        }
        //Estado
        if ($this->state_id != '')
        {
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_departements WHERE nom ='" . $this->state_id . "'";
            $result = $this->db->query($sql);
            $state = $this->db->fetch_object($result);
            $this->societe->state_id = $state->rowid;
        }
        //apodo
        if(!empty($this->nickname)){
            $this->societe->name_alias=$this->nickname?$this->nickname:'';
        }

        //facturacion
        if(!empty($this->facturacion)){
            if(strtolower($this->facturacion)=='si'){
                $this->societe->automatic_invoicing =1;
            }else{
                $this->societe->automatic_invoicing =0;
            }
        }

        //codigo postal
        if (!empty($this->postal)){
            $this->societe->zip = $this->postal?$this->postal:'';
        }

        //correo
        if(!empty($this->correo)){
            $this->societe->email = $this->correo?$this->correo:'';
        }

        //country
        if(!empty($this->country)){
            $sentece = "SELECT rowid from llx_c_country where code ='".trim($this->country)."'";
            $resultado= $this->db->query($sentece);
            $values= $this->db->fetch_object($resultado);
            $this->societe->country_id = $values->rowid;
        }else{
            $this->societe->country_id=154;
        }
        if(!empty($this->pago)){
            $this->pago= trim(strtolower($this->pago));
            switch ($this->pago){
                case 'cheque':
                    $this->societe->mode_reglement_supplier_id =7;
                    break;
                case 'tarjeta de debito/credito':
                    $this->societe->mode_reglement_supplier_id =54;
                    break;
                case 'efectivo':
                    $this->societe->mode_reglement_supplier_id =4;
                    break;
                case 'transferencia bancaria':
                    $this->societe->mode_reglement_supplier_id =2;
                    break;
                case 'domiciliacion':
                    $this->societe->mode_reglement_supplier_id =3;
                    break;
            }
        }
        //importe maximo
        if (!empty($this->importe)){
            $this->societe->outstanding_limit=$this->importe?$this->importe:'';
        }

        //decuento pronto pago con dias
        if(!empty($this->des_pro) && !empty($this->day_pro)){
            $this->societe->earlypayment_discount= $this->des_pro;
            $this->societe->earlypayment_validity= $this->day_pro;
        }
        //rfc
        if(!empty($this->siren)){
            $this->societe->idprof1 = $this->siren?$this->siren:'';
        }


        //$this->societe->code_fournisseur = $this->code;
        if(!empty($this->address)){
            $this->societe->address = $this->address != null?$this->address:'';
        }
        if($this->extnum != null)$this->societe->address.=" #".$this->extnum;
        if($this->intnum != null)$this->societe->address.=" int. ".$this->intnum;
        if($this->nbhood != null)$this->societe->address.=" Colonia ".$this->nbhood;
        if($this->location != null)$this->societe->address.=", ".$this->location;
        if(!empty($this->town)){
            $this->societe->town = $this->town != null?$this->town:null;
        }
        if(!empty($this->url)){
            $this->societe->url = $this->url != null?$this->url:null;
        }

        $this->societe->client = 0;
        $this->societe->fournisseur = 1;
        $object = new Fournisseur($this->db);
        if(!empty($this->days_credit)){
            $sql="select rowid from ".MAIN_DB_PREFIX."c_payment_term where nbjour =".$this->days_credit." order by sortorder asc limit 1";
            $result = $this->db->query($sql);
            $num_r_pc = $this->db->num_rows($result);
        }

        $companybankaccount = new CompanyBankAccount($this->db);

        if(!empty($this->capital)){
            $this->societe->capital = $this->capital != null? $this->capital : 0;
        }

        if(!empty($this->phone)){
            $this->societe->phone = $this->phone;
        }
        $this->societe->status  = $this->status == "Activo"? 1: 0;
        $this->societe->name = $this->name;

        if ($res > 0) {
            $status = 2;
            if($this->societe->update($this->societe->id, $this->user) < 0)
            {
                $this->errors['error'][$this->name]['Actualización']['label'] = "No se pudo actualizar proveedor $this->name";
                $this->errors['error'][$this->name]['Actualización']['db'] = $this->product->error;
                $status = -2;
            }
            if(!empty($this->des_fijo)){
                $this->societe->set_remise_supplier($this->des_fijo,"Creado a partir de importacion",$this->user);
            }
            if(!empty($this->des_abs)){
                $this->societe->set_remise_except($this->des_abs, $this->user, "Creado a partir de importacion", 0, 1);
            }

            $object->fetch($this->societe->id);
            if($result && $num_r_pc > 0) {
                $term = $this->db->fetch_object($result);
                if($object->setPaymentTerms($term->rowid) < 0)
                {
                    $this->errors['warning'][$this->name]['CondiciondePago']['label'] = "No se pudo actualizar la condición de pago $this->name";
                    $this->errors['warning'][$this->name]['CondiciondePago']['db'] = $this->product->error;
                }
            }
            //categoria
            $sqli="DELETE FROM ".MAIN_DB_PREFIX."categorie_fournisseur WHERE fk_soc = ".$this->societe->id;
            $rest=$this->db->query($sqli);
            $this->categoria= strtoupper($this->categoria);
            $valida = $this->categorie->fetch('',$this->categoria,1);
            if($valida>0){
                $this->societe->AddFournisseurInCategory($this->categorie->id);

            }else{
                $this->categorie->fk_parent=0;
                $this->categorie->label = $this->categoria;
                $this->categorie->description ="Agregada a partir de importacion";
                $color = substr(md5(time()), 0, 6);
                $this->categorie->color = $color;
                $this->categorie->type = 1;
                $this->categorie->import_key=null;
                $this->categorie->ref_ext=null;
                $this->categorie->samePrice=0;
                $this->categorie->create($this->user);
                $this->societe->AddFournisseurInCategory($this->categorie->id);
                unset($color);
            }
            // Cuenta de banco
            if($this->numaccount != null)
            {
                $companybankaccount->fetch(null,$this->societe->id);
                $companybankaccount->socid = $this->societe->id;
                $companybankaccount->label = $this->titular?$this->titular:null;
                $companybankaccount->bank = $this->bank?$this->bank:null;
                $companybankaccount->number = $this->numaccount?$this->numaccount:null;
                $companybankaccount->proprio = $this->titular?$this->titular:null;
                $companybankaccount->iban = $this->clabe?$this->clabe:null;
                if($companybankaccount->id == null)
                    if($companybankaccount->create($this->user) < 0)
                    {
                        $this->errors['warning'][$this->name]['CuentaBanco']['label'] = "No se pudo crear la cuenta de banco $this->name";
                        $this->errors['warning'][$this->name]['CuentaBanco']['db'] = $this->product->error;
                    }
                if($companybankaccount->update($this->user) < 0)
                {
                    $this->errors['warning'][$this->name]['CuentaBanco']['label'] = "No se pudo actualizar la cuenta de banco $this->name";
                    $this->errors['warning'][$this->name]['CuentaBanco']['db'] = $this->product->error;
                }
            }
        }
        else {
            $status = 1;
            if($this->societe->create($this->user) < 0)
            {
                $this->errors['error'][$this->name]['Creación']['label'] = "No se pudo crear el cliente $this->name";
                $this->errors['error'][$this->name]['Creación']['db'] = $this->product->error;
                $status = -1;
            }
            if(!empty($this->des_fijo)){
                $this->societe->set_remise_supplier($this->des_fijo,"Creado a partir de importacion",$this->user);
            }
            if(!empty($this->des_abs)){
                $this->societe->set_remise_except($this->des_abs, $this->user, "Creado a partir de importacion", 0, 1);
            }
            $object->fetch($this->societe->id);
            if($result && $num_r_pc > 0) {//Para condiciones de pago
                $term = $this->db->fetch_object($result);
                if($object->setPaymentTerms($term->rowid) < 0)
                {
                    $this->errors['warning'][$this->name]['CondiciondePago']['label'] = "No se pudo añadir la condición de pago $this->name";
                    $this->errors['warning'][$this->name]['CondiciondePago']['db'] = $this->product->error;
                }
            }
            //categoria
            $this->categoria= strtoupper($this->categoria);
            $valida = $this->categorie->fetch('',$this->categoria,1);
            if($valida>0){
                $this->societe->AddFournisseurInCategory($this->categorie->id);

            }else{
                $this->categorie->fk_parent=0;
                $this->categorie->label = $this->categoria;
                $this->categorie->description ="Agregada a partir de importacion";
                $color = substr(md5(time()), 0, 6);
                $this->categorie->color = $color;
                $this->categorie->type = 1;
                $this->categorie->import_key=null;
                $this->categorie->ref_ext=null;
                $this->categorie->samePrice=0;
                $this->categorie->create($this->user);
                $this->societe->AddFournisseurInCategory($this->categorie->id);
                unset($color);
            }
            // Cuenta de banco
            if($this->numaccount != null)
            {
                $companybankaccount->fetch(null,$this->societe->id);
                $companybankaccount->socid = $this->societe->id;
                $companybankaccount->label = $this->titular?$this->titular:null;
                $companybankaccount->bank = $this->bank?$this->bank:null;
                $companybankaccount->number = $this->numaccount?$this->numaccount:null;
                $companybankaccount->proprio = $this->titular?$this->titular:null;
                $companybankaccount->iban = $this->clabe?$this->clabe:null;
                if($companybankaccount->id == null)
                    if($companybankaccount->create($this->user) < 0)
                    {
                        $this->errors['warning'][$this->name]['CuentaBanco']['label'] = "No se pudo crear la cuenta de banco $this->name";
                        $this->errors['warning'][$this->name]['CuentaBanco']['db'] = $this->product->error;
                    }
                if($companybankaccount->update($this->user) < 0)
                {
                    $this->errors['warning'][$this->name]['CuentaBanco']['label'] = "No se pudo actualizar la cuenta de banco $this->name";
                    $this->errors['warning'][$this->name]['CuentaBanco']['db'] = $this->product->error;
                }
            }
        }
        return $status;
        unset($this->categorie);
        unset($this->societe);
    }
}

?>