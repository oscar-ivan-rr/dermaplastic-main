<?php
global $conf, $langs,$mysoc;
error_reporting(0);
date_default_timezone_set("America/Mexico_City");
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/template.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/mod_facture_terre.php';


class ComptaTemplate extends Template {
    /**
     * @var Facture
     */
    public $facture;
    /**
     * @var FactureLigne
     */
    public $factureligne;
    /**
     * @var Paiement
     */
    public $paiment;
    /**
     * @var mod_facture_terre
     */
    public $mod_facture_terre;


    /**
     * Defined  vars
     */
    public $idfact;
    public $ref;

    public $fecha;
    public $fecha_final;
    public $concepto;
    public $ref_cliente;
    public $rfc_cliente;
    public $cargo;
    public $abono;
    /**
     * Constructor
     *
     * @param   DoliDB  $db     Database handler
     * @param   User    $user   Usuario de dolibarr
     */
    public function __construct(DoliDB $db, User $user) {
        // Relación de propiedades con columnas de excel predefinidas
        $this->relation = array(
            'base' => array (
                'fecha'       => 'fecha inicio',
                'fecha_final'       => 'fecha venc',
                'concepto'       => 'concepto',
                'ref_cliente' => 'referencia',
                'rfc_cliente' => 'rfc cliente',
                'cargo'       => 'cargo',
                'abono'       => 'abono'

            ),
            'extra' => array(
            )
        );
        parent::__construct($db, $user,'');
    }

    /**
     * createObject
     *
     * Función para crear el societe o actualizar en la BD.
     */
    public function createObject() {
        $this->cargo= str_replace(',','',$this->cargo);
        $this->abono= str_replace(',','',$this->abono);
        if(($this->cargo-$this->abono)>0){
        $this->facture = new Facture($this->db);
        $this->factureligne = new FactureLigne($this->db);
        $this->paiment = new Paiement($this->db);
        $this->mod_facture_terre = new mod_facture_terre($this->db);
        $this->mod_facture_terre->validation=1;

        $b= array('ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dic'=>'12');
        //Validamos si la fecha no esta en el archivo de importacion ponemos la fecha de la importacion
        if(empty($this->fecha)){
             $this->fecha = date('d/m/Y');
        }
        if(empty($this->fecha_final)){
            $this->fecha_final = date('d/m/Y');
        }
        if(strlen($this->fecha)<10){
            $fecha1 = explode('-',$this->fecha);
            $this->fecha =$fecha1[0]."/".$b[$fecha1[1]]."/".$fecha1[2];
        }
        if(strlen($this->fecha_final)<10){
            $fecha2 = explode('-',$this->fecha_final);
            $this->fecha_final =$fecha2[0]."/".$b[$fecha2[1]]."/".$fecha2[2];
        }



        //obtenemos la fecha de inicio para insertar la factura
        if (strpos($this->fecha,'/'))
        {
            $tdate = explode('/',$this->fecha);
            $this->fecha = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
        }
        if (strpos($this->fecha_final,'/'))
        {
            $tdate = explode('/',$this->fecha_final);
            $this->fecha_final = mktime(0,0,0,$tdate[1],$tdate[0],$tdate[2]);
        }

        //llenamos los campos para la factura
        $this->facture->date               = $this->fecha;
        $this->facture->date_lim_reglement = $this->fecha_final;
        $this->facture->ref_client         = trim($this->ref_cliente )." (".$this->concepto.")";
        $this->facture->cond_reglement_id  = 1;
        $this->facture->modelpdf= "crabe";
        $estatus = 0;
        //si no esta vacio el rfc continuamos con el proceso
        if(!empty($this->rfc_cliente)) {

            //Obtenemos el id del proveedor y lo creamos la factura
            $sql ="SELECT rowid from  llx_societe where fournisseur=0 and siren = '".$this->rfc_cliente."'";
            $result = $this->db->query($sql);
            //Validamos que si exista un proveedor
            if($result->num_rows > 0){
                $id = $this->db->fetch_object($result);
                $this->facture->socid = $id->rowid;
                $sql1="SELECT s.cond_reglement, p.nbjour, p.sortorder  from llx_societe as s  inner join llx_c_payment_term as p on  s.cond_reglement = p.sortorder where s.rowid = ".$this->facture->socid;
                $result1= $this->db->query($sql1);
                if($result1->num_rows >0){
                    $data= $this->db->fetch_object($result1);
                    $this->fecha_final=date("Y-m-d",$this->facture->date);
                    $nueva=date("Y-m-d",strtotime($this->fecha_final." + ".$data->nbjour." days"));
                    $this->fecha_final = strtotime($nueva);//mktime(0,0,0,$tdate3[2],$tdate3[1],$tdate3[0]);

                }
                $this->facture->create($this->user,0,$this->fecha_final);

                //obtenemos el ultimo id para insertar en extrafields
                $sql ="SELECT rowid,ref from llx_facture  order by rowid DESC limit 1";
                $result  = $this->db->query($sql);
                $id= $this->db->fetch_object($result);
                $this->idfact = $id->rowid;
                $this->ref =$id->ref;

                //insertamos en extrafields
                $sql = "INSERT INTO  llx_facture_extrafields (tms,fk_object,formpagcfdi,usocfdi) VALUES ('".$this->facture->date."','".$this->idfact."','PUE','0')";
                $result = $this->db->query($sql);
                $estatus=1;
                $valida=0;
            }else{
                $estatus=-2;
                $this->errors['error'][$this->rfc_cliente]['No se encotro  registro en el sistema del provedor']['label'] = "No se encotro  registro en el sistema". $this->rfc_cliente;
            }
            //si no esta vacio el monto de la factura añadimos un producto
            if ($estatus>0 && !empty($this->cargo)){

                //añadimos el producto a la factura
                $this->factureligne->fk_facture = $this->idfact;
                $this->factureligne->desc = "Producto generado por importacion";
                $this->factureligne->qty ="1";
                $this->factureligne->subprice = price2num($this->cargo);
                $this->factureligne->total_ht = price2num($this->cargo);
                $this->factureligne->total_ttc = price2num($this->cargo);
                $this->factureligne->rang =1;
                $this->factureligne->total_tva=0;
                $this->factureligne->multicurrency_code= 'MXN';
                $this->factureligne->multicurrency_subprice = price2num($this->cargo);
                $this->factureligne->multicurrency_total_ht = price2num($this->cargo);
                $this->factureligne->multicurrency_total_ttc = price2num($this->cargo);
                $a = $this->factureligne->insert();
                $valida=2;


                if ($valida>0){


                    $sabe = new Societe($this->db);
                    $sabe->fetch($this->facture->socid);
                    $newxt =$this->mod_facture_terre->getNumRef($sabe,$this->facture->id);
                    unset($this->mod_facture_terre->validation);

                    $this->facture->validate($this->user,$newxt,'',0);
                    $this->facture->generateDocument('crabe','',0,0,0);
                    //fin creacion del documento

                    if(!empty($this->abono)){

                        //añadimos el pago a la factura
                        if ($this->abono>0){
                            //si es mayor a 0 se agrega el pago
                            $array= array($this->idfact => $this->abono);

                            $array2= array();
                            //calculamos el pago

                            $this->paiment->datepaye     = $this->fecha;
                            $this->paiment->amounts      = $array;
                            $this->paiment->multicurrency_amounts =$array2;
                            $this->paiment->paiementid   = 4;
                            $this->paiment->num_payment  = '';
                            $this->paiment->note_private = '';
                            $this->paiment->num_paiement = '';
                            $this->paiment->note         = '';

                            $thirdparty = new Societe($this->db);
                            $thirdparty->fetch($this->facture->socid);


                            $this->paiment->create($this->user,'',$thirdparty);
                            //Agregamos el pago al banco
                            $label = '(CustomerInvoicePayment)';
                            $result = $this->paiment->addPaymentToBank($this->user, 'payment', $label, 4, 0,0);
                            return $estatus;
                        }
                    }else{
                        //no se puede añadir ala factura el pago
                        //$this->errors['warning'][$this->rfc_cliente]['No existe referencia del proveedor']['label'] = "No se pudo añadir el pago de la factura " .$this->rfc_cliente. " con referencia ". $this->ref_cliente;
                        return $estatus;
                    }
                }else{
                    $this->errors['warning'][$this->rfc_cliente]['No se agregar el producto a la factura ']['label'] = "No se pudo agregar  el producto a la factura ". $this->rfc_cliente;
                }
            }else{
                //si falta monto de factura
                $this->errors['warning'][$this->rfc_cliente]['No existe referencia del proveedor']['label'] = "No se pudo añadir el monto de la factura " .$this->rfc_cliente." con referencia ".$this->ref_cliente;
                return $estatus;
            }
        }else{
            //si falta ref cliente
            $this->errors['warning'][$this->rfc_cliente]['No existe referencia del proveedor']['label'] = "No se pudo crear la factura ". $this->ref_cliente;
            return $estatus;
        }


        unset($this->facture);
        unset($this->factureligne);
        unset($this->paiment);
        unset($thirdparty);
        unset($this->mod_facture_terre);
        unset($sabe);
        unset($a);
        }else{
            $sql ="SELECT rowid from  llx_societe where fournisseur=0 and siren = '".$this->rfc_cliente."'";
            $result = $this->db->query($sql);
            //Validamos que si exista un proveedor
            if($result->num_rows > 0) {
                $id = $this->db->fetch_object($result);
                $descripcion = trim($this->ref_cliente) . " (" . $this->concepto . ")";
                $cantidad = $this->cargo - $this->abono;
                $cantidad = abs($cantidad);
                $soc = new Societe($this->db);
                $soc->fetch($id->rowid);
                $descuento = $soc->set_remise_except($cantidad, $this->user, $descripcion, '', 0);
                if ($descuento > 0) {
                    return 1;
                } elseif ($descuento < 0 && strlen($descripcion) > 3) {
                    $this->errors['warning'][$descripcion]['No se puede agregar el descuento absoluto']['label'] = "No se puede agregar el descuento absoluto " . $this->rfc_cliente . " con referencia " . $this->ref_cliente;
                }
            }else{
                $descripcion = trim($this->ref_cliente) . " (" . $this->concepto . ")";
                $this->errors['error'][$descripcion]['No se encontro el provedor']['label'] = "No se encotro ningun cliente con RFC  " . $this->rfc_cliente;
                return -2;
            }
            unset($soc);
        }

    }


}

?>
