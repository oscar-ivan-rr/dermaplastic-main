<?php

require_once DOL_DOCUMENT_ROOT.'/custom/import/class/template.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

class SocieteTemplate extends Template {

    /**
     * @var Societe
     */
    public $societe;

    /**
     * @var Client
     */
    public $client;

    /**
     * type of societe
     */
    public $type;

    /**
     * Defined societe vars
     */
    public $name;
    public $automatic_invoicing;
    public $alias;
    public $rfc;
    public $code;
    public $type_client;
    public $status;
    public $address;
    public $zipcode;
    public $town;
    public $country;
    public $state_id;
    public $type_payment;
    public $phone;
    public $mail;
    public $url;
    public $typeid;
    public $comercial_asign;
    public $payment_conditions;
    public $relative_discount;
    public $fixed_discount;
    public $prontopago;
    public $lim_credit;
    public $pros_state;
    public $tva;
    public $category;
    public $days_pron;
    public $pacfdi;
    public $uscfdi;
    public $fecha_nacimiento;

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
                'name' => 'nombre',
                'alias' => 'apodo',
                'rfc' => 'rfc',
                'code' => 'código de cliente',
                'type_client' => 'cliente',
                'status' => 'estado',
                'address' => 'dirección',
                'zipcode' => 'código postal',
                'town' => 'población',
                'country' => 'país',
                'state_id' => 'provincia',
                'type_payment' => 'tipo de pago',
                'phone' => 'telefono',
                'mail' => 'email',
                'url' => 'web',
                'typeid' => 'tipo de tercero',
                'comercial_asign' => 'asignado al comercial',
                'payment_conditions' => 'días de crédito',
                'relative_discount' => 'descuento fijo (%)',
                'fixed_discount' => 'descuento absoluto ($)',
                'prontopago' => 'descuento por pronto pago (%)',
                'days_pron' => 'dias pronto pago',
                'lim_credit' => 'importe máximo de facturas pendientes',
                'pros_state' => 'estado de prospección',
                'tva' => 'iva',
                'category' => 'categoría',
                'automatic_invoicing' => 'facturación automatica',
                'pacfdi' => 'pago cfdi',
                'uscfdi' => 'uso cfdi',
                'fecha_nacimiento'	=> 'fecha de nacimiento'
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
    public function createObject()
    {
        global $conf;
        $this->societe = new Societe($this->db);
        $this->client = new Client($this->db);
        $res = $this->societe->fetch('', $this->name);

        //Nombre
        if(is_numeric($this->name)){
            $this->errors['error'][$this->name]['Actualización']['label'] = "El campo nombre no puede ser numerico ";
        }else{
        $this->societe->name = $this->name;
        }
        //Apodo
        if(!empty($this->alias)){
            $this->societe->name_alias = $this->alias != ''?$this->alias:'';
        }

        //RFC
        if(!empty($this->rfc)){
            $this->societe->idprof1 = $this->rfc!=''?$this->rfc:'';
        }


        //Codigo de cliente
        $module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
        if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
            $module = substr($module, 0, dol_strlen($module) - 4);
        }
        $dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
        foreach ($dirsociete as $dirroot) {
            $res1 = dol_include_once($dirroot . $module . '.php');
            if ($res1) break;
        }
        $modCodeClient = new $module;
        $tmpcode = $this->societe->code_client;
        if (empty($tmpcode) && !empty($modCodeClient->code_auto)) $tmpcode = $modCodeClient->getNextValue($this->societe, 0);
        $this->societe->code_client = $tmpcode;

        //Tipo de cliente
        if(!empty($this->type_client)){
            $this->type_client = strtolower($this->type_client);
            switch ($this->type_client) {
                case 'cliente':default:
                $this->societe->client = 1;
                break;
                case 'cliente potencial':
                    $this->societe->client = 2;
                    break;
                case 'cliente potencial / cliente':
                    $this->societe->client = 3;
                    break;
                case 'ni cliente, ni cliente potencial':
                    $this->societe->client = 0;
                    break;
            }
        }

        //Estatus
        if(!empty($this->status)){
            $this->status = strtolower($this->status);
            $this->societe->status = $this->status == "activo" ? 1 : 0;
        }
        //Direccion
        if(!empty($this->address)){
            $this->societe->address = $this->address?$this->address:'';
        }

        //Codigo postal
        if(!empty($this->zipcode)){
            $this->societe->zip = $this->zipcode!=''?$this->zipcode:'';
        }
        //Poblacion
        if(!empty($this->town)){
            $this->societe->town = $this->town != '' ? $this->town : '';
        }

        //Pais
        if($this->country != '') {
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_country WHERE code = '" . $this->country . "'";
            $result = $this->db->query($sql);
            $country = $this->db->fetch_object($result);
            $this->societe->country_id = $country->rowid;
        }else{
            $this->societe->country_id=154;
        }
        //Provincia
        if ($this->state_id != '')
        {
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_departements WHERE nom ='" . $this->state_id . "'";
            $result = $this->db->query($sql);
            $state = $this->db->fetch_object($result);
            $this->societe->state_id = $state->rowid;
        }
        //Telefono
        if(!empty($this->phone)){
            $this->societe->phone = $this->phone!=''?$this->phone:'';
        }
        //eMail
        if(!empty($this->mail)){
            $this->societe->email = $this->mail!=''?$this->mail:'';
        }
        //Web
        if(!empty($this->url)){
            $this->societe->url = $this->url!=''?$this->url:'';
        }
        //Facturación automatica
        if(!empty($this->automatic_invoicing)){
            if(strtolower($this->automatic_invoicing) == "si")
                $this->societe->automatic_invoicing = 1;
            else
                $this->societe->automatic_invoicing = 0;
        }

        //tipo de tercero
        /*if($this->typeid == "CREDI")
            $this->societe->typent_id=234;
        else
            $this->societe->typent_id=235;*/
        if(!empty($this->typeid)){
            $sql="select id from ".MAIN_DB_PREFIX."c_typent where libelle = '".$this->typeid."'";
            $resql = $this->db->query($sql);
            if($resql)
            {
                $typeS = $this->db->fetch_object($resql);
                $this->societe->typent_id = $typeS->id;
            }
        }
        //Asignado comercial
        if($this->comercial_asign != '') {
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE CONCAT(firstname,' ',lastname) like '%" . $this->comercial_asign . "%'";
            $result = $this->db->query($sql);
            $asig = $this->db->fetch_object($result);
            $this->societe->commercial_id = $asig->rowid;
        }
        //IVA
        if(!empty($this->tva)){
            $this->societe->tva_intra = $this->tva!=''?$this->tva:'';
        }


        $this->societe->fournisseur = 0;
        //descuento pronto pago
        if(!empty($this->prontopago) && !empty($this->days_pron)){
            $this->societe->earlypayment_discount= $this->prontopago;
            $this->societe->earlypayment_validity= $this->days_pron;
        }else{
            $this->societe->earlypayment_discount= '';
            $this->societe->earlypayment_validity= '';
        }


        if ($res > 0) {
            $status = 2;
            if($this->societe->update($this->societe->id, $this->user) < 0)
            {
                $this->errors['error'][$this->name]['Actualización']['label'] = "No se pudo actualizar cliente $this->name";
                $this->errors['error'][$this->name]['Actualización']['db'] = $this->product->error;
                $status = -2;
            }
            $this->client->fetch($this->societe->id);
            //Tipo de pago
            if($this->type_payment != '') {
                $tp = 0;
                switch ($this->type_payment) {
                    case "Cheque":
                        $tp = 7;
                        break;
                    case "Domiciliación":
                        $tp = 3;
                        break;
                    case "Efectivo":
                        $tp = 4;
                        break;
                    case "Tarjeta de crédito":
                        $tp = 6;
                        break;
                    case "Transferencia bancaria":
                        $tp = 2;
                        break;
                }
                if($this->client->setPaymentMethods($tp) < 0)
                {
                    $this->errors['warning'][$this->name]['TipodePago']['label'] = "No se pudo actualizar el tipo de pago $this->name";
                    $this->errors['warning'][$this->name]['TipodePago']['db'] = $this->product->error;
                }
            }
            //Condiciones de pago
            if($this->payment_conditions != '') {
                $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_payment_term where nbjour =" . $this->payment_conditions;
                $res = $this->db->query($sql);
                $cp = $this->db->fetch_object($res);
                if($this->client->setPaymentTerms($cp->rowid) < 0)
                {
                    $this->errors['warning'][$this->name]['CondiciondePago']['label'] = "No se pudo actualizar la condición de pago $this->name";
                    $this->errors['warning'][$this->name]['CondiciondePago']['db'] = $this->product->error;
                }
            }

            //Categoría
            $this->setCategory($this->category);

            //descuento relativo
            if($this->relative_discount != ''){
                if($this->societe->set_remise_client(price2num(intval($this->relative_discount)), 'descuento fijo en importación', $this->user) < 0)
                {
                    $this->errors['warning'][$this->name]['DescuentoFijo']['label'] = "No se pudo actualizar el descuento fijo $this->name";
                    $this->errors['warning'][$this->name]['DescuentoFijo']['db'] = $this->product->error;
                }
            }
            //descuento fijo
            if($this->fixed_discount != '') {
                $amount_ht = intval($this->fixed_discount) * ((100 - intval($this->tva)) / 100);
                if($this->societe->set_remise_except($amount_ht, $this->user, 'descuento en importacion', intval($this->tva), 0) < 0)
                {
                    $this->errors['warning'][$this->name]['DescuentoAbsoluto']['label'] = "No se pudo crear el descuento absoluto $this->name";
                    $this->errors['warning'][$this->name]['DescuentoAbsoluto']['db'] = $this->product->error;
                }
            }
            //Importe máximo para facturas pendientes
            if($this->lim_credit != '')
                $this->client->outstanding_limit = intval($this->lim_credit);
            //Estado de Prospección
            $this->pros_state=trim(strtolower($this->pros_state));
            switch ($this->pros_state) {
                case "nunca contactado":default:
                    $this->client->stcomm_id = 0;
                    break;
                case "no contactar":
                    $this->client->stcomm_id = -1;
                    break;
                case "para ser contactado":
                    $this->client->stcomm_id = 1;
                    break;
                case "contacto en proceso":
                    $this->client->stcomm_id = 2;
                    break;
                case "contacto realizado":
                    $this->client->stcomm_id = 3;
                    break;
            }
            if($this->client->update($this->societe->id, $this->user) < 0)
            {
                $this->errors['warning'][$this->name]['Prospección']['label'] = "No se pudo actualizar el estado de prospección o el importe máximo de facturas pendientes $this->name";
                $this->errors['warning'][$this->name]['Prospección']['db'] = $this->product->error;
            }
            if(!empty($this->pacfdi)){
                $sql3="select rowid from llx_societe_extrafields where fk_object = ". $this->societe->id;
                $existe=$this->db->query($sql3);
                if($this->db->num_rows($existe)==0){
                    $sql1="INSERT INTO " . MAIN_DB_PREFIX . "societe_extrafields (fk_object,formpagcfdi) VALUES (".$this->societe->id.",'".$this->pacfdi."')";
                    $status =$this->db->query($sql1);
                }else{
                    $this->pacfdi=strtoupper(trim($this->pacfdi));
                    $sql2="UPDATE llx_societe_extrafields  SET formpagcfdi = '" .$this->pacfdi. "' where fk_object = ". $this->societe->id;
                    $resk=$this->db->query($sql2);
                }

            }

            if(!empty($this->uscfdi)){
                $sql3="select rowid from llx_societe_extrafields where fk_object = ". $this->societe->id;
                $existe=$this->db->query($sql3);
                if($this->db->num_rows($existe)==0){
                    $sql1="INSERT INTO " . MAIN_DB_PREFIX . "societe_extrafields (fk_object,usocfdi) VALUES (".$this->societe->id.",'".$this->uscfdi."')";
                    $status =$this->db->query($sql1);
                }else{
                    $this->pacfdi=strtoupper(trim($this->pacfdi));
                    $sql2="UPDATE llx_societe_extrafields  SET usocfdi = '" .$this->uscfdi. "' where fk_object = ". $this->societe->id;
                    $resk=$this->db->query($sql2);

                }
            }

            if(!empty($this->fecha_nacimiento)){
                if (strpos($this->fecha_nacimiento,'/'))
                {
                    $tdate = explode('/',$this->fecha_nacimiento);
                    $this->fecha_nacimiento = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
                    $sql3="select rowid from llx_societe_extrafields where fk_object = ". $this->societe->id;
                    $existe=$this->db->query($sql3);
                    if($this->db->num_rows($existe)==0){
                        $sql1="INSERT INTO " . MAIN_DB_PREFIX . "societe_extrafields (fk_object,fecha_nacimiento) VALUES (".$this->societe->id.",'".date('Y-m-d', $this->fecha_nacimiento)."')";
                        $status =$this->db->query($sql1);
                    }else{
                        $sql2="UPDATE llx_societe_extrafields  SET fecha_nacimiento = '" .date('Y-m-d', $this->fecha_nacimiento). "' where fk_object = ". $this->societe->id;
                        $resk=$this->db->query($sql2);
                    }   
                }
            }
        }
        else {
            if($this->societe->create($this->user) < 0)
            {
                $this->errors['error'][$this->name]['Creación']['label'] = "No se pudo crear el cliente $this->name";
                $this->errors['error'][$this->name]['Creación']['db'] = $this->product->error;
                $status = -1;
            }
            else{
                $status = 1;
            }
            $this->client->fetch($this->societe->id);
            //Tipo de pago
            if($this->type_payment != '') {
                $tp = 0;
                switch ($this->type_payment) {
                    case "Cheque":
                        $tp = 7;
                        break;
                    case "Domiciliación":
                        $tp = 3;
                        break;
                    case "Efectivo":
                        $tp = 4;
                        break;
                    case "Tarjeta de crédito":
                        $tp = 6;
                        break;
                    case "Transferencia bancaria":
                        $tp = 2;
                        break;
                }
                if($this->client->setPaymentMethods($tp) < 0)
                {
                    $this->errors['warning'][$this->name]['TipoPago']['label'] = "No se pudo añadir el tipo de pago $this->name";
                    $this->errors['warning'][$this->name]['TipoPago']['db'] = $this->product->error;
                }
            }
            //Condiciones de pago
            if($this->payment_conditions != '') {
                $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_payment_term where nbjour =" . $this->payment_conditions;
                $res = $this->db->query($sql);
                $cp = $this->db->fetch_object($res);
                if($this->client->setPaymentTerms($cp->rowid) < 0)
                {
                    $this->errors['warning'][$this->name]['CondicionesPago']['label'] = "No se pudo añadir las condiciones de pago $this->name";
                    $this->errors['warning'][$this->name]['CondicionesPago']['db'] = $this->product->error;
                }
            }

            //Categoría
            $this->setCategory($this->category);

            //descuento relativo
            if($this->relative_discount != '')
                if($this->societe->set_remise_client(price2num(intval($this->relative_discount)), 'descuento relativo en importación', $this->user) < 0)
                {
                    $this->errors['warning'][$this->name]['DescuentoRelativo']['label'] = "No se pudo añadir el descuento relativo $this->name";
                    $this->errors['warning'][$this->name]['DescuentoRelativo']['db'] = $this->product->error;
                }
            //descuento fijo
            if($this->fixed_discount != '') {
                $amount_ht = intval($this->fixed_discount) * ((100 - intval($this->tva)) / 100);
                if($this->societe->set_remise_except($amount_ht, $this->user, 'descuento en importacion', intval($this->tva), 0) < 0)
                {
                    $this->errors['warning'][$this->name]['DescuentoFijo']['label'] = "No se pudo añadir el descuento fijo $this->name";
                    $this->errors['warning'][$this->name]['DescuentoFijo']['db'] = $this->product->error;
                }
            }
            $this->pacfdi=strtoupper($this->pacfdi);
            $this->uscfdi=strtoupper($this->uscfdi);
            $sql1="INSERT INTO " . MAIN_DB_PREFIX . "societe_extrafields (fk_object,formpagcfdi,usocfdi) VALUES (".$this->societe->id.",'".$this->pacfdi."','".$this->uscfdi."')";
            $status =$this->db->query($sql1);
            if(!empty($this->fecha_nacimiento)){
                if (strpos($this->fecha_nacimiento,'/'))
                {
                    $tdate = explode('/',$this->fecha_nacimiento);
                    $this->fecha_nacimiento = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
                    $sql2="UPDATE llx_societe_extrafields  SET fecha_nacimiento = '" .date('Y-m-d', $this->fecha_nacimiento). "' where fk_object = ". $this->societe->id;
                    $resk=$this->db->query($sql2);
                }
            }
            //Importe máximo para facturas pendientes
            if($this->lim_credit != '')
                $this->client->outstanding_limit = intval($this->lim_credit);
            //Estado de Prospección
            $this->pros_state=trim(strtolower($this->pros_state));
            switch ($this->pros_state) {
                case "nunca contactado":default:
                    $this->client->stcomm_id = 0;
                    break;
                case "no contactar":
                    $this->client->stcomm_id = -1;
                    break;
                case "para ser contactado":
                    $this->client->stcomm_id = 1;
                    break;
                case "contacto en proceso":
                    $this->client->stcomm_id = 2;
                    break;
                case "contacto realizado":
                    $this->client->stcomm_id = 3;
                    break;
            }
            if($this->client->update($this->societe->id, $this->user) < 0)
            {
                $this->errors['warning'][$this->name]['Prospección']['label'] = "No se pudo añadir el estado de prospección o el importe máximo de facturas pendientes $this->name";
                $this->errors['warning'][$this->name]['Prospección']['db'] = $this->product->error;
            }
        }
        
        unset($this->societe);
        unset($this->client);
        return $status;
    }

    public function setCategory($categorie)
    {
        if($categorie != '')
        {
            $categories = explode(';',$categorie);
            foreach ($categories as $cat)
            {
                $sql = "select rowid from ".MAIN_DB_PREFIX."categorie where label ='".$cat."'";
                $result = $this->db->query($sql);
                $catid = $this->db->fetch_object($result);
                $rescat = $this->societe->setCategories($catid->rowid, 'customer');
            }
        }
    }
}

?>