<?php
global $conf, $langs,$mysoc;

date_default_timezone_set("America/Mexico_City");
require_once DOL_DOCUMENT_ROOT.'/custom/import/class/template.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/mod_facture_fournisseur_cactus.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';


class PagarTemplate extends Template {
    /**
     * @var FactureFournisseur
     */
    public $factureFournisseur;
    /**
     * @var SupplierInvoiceLine
     */
    public $supplierInvoiceLine;
    /**
     * @var PaiementFourn
     */
    public $paiementFourn;
    /**
     * @var mod_facture_fournisseur_cactus
     */
    public $mod_facture;


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
                'rfc_cliente' => 'rfc proveedor',
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
        $this->cargo=str_replace(',','',$this->cargo);
        $this->abono=str_replace(',','',$this->abono);
        if($this->cargo-$this->abono>0){
        $this->factureFournisseur   = new FactureFournisseur($this->db);
        $this->supplierInvoiceLine  = new SupplierInvoiceLine($this->db);
        $this->paiementFourn        = new PaiementFourn($this->db);
        $this->mod_facture    = new mod_facture_fournisseur_cactus($this->db);
        //algo
        $this->mod_facture->validation=5;




        $b= array('ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dic'=>'12');
        //Validamos si la fecha no esta en el archivo de importacion ponemos la fecha de la importacion
        $this->fecha=strtolower($this->fecha);
        $this->fecha_final=strtolower($this->fecha_final);
        $cambio=$this->fecha;
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

        //Llenamos los campos para la creacion de la factura
        $this->factureFournisseur->date = $this->fecha;
        $this->factureFournisseur->date_echeance = $this->fecha_final;
        $this->factureFournisseur->ref_supplier = trim($this->ref_cliente )." (".$this->concepto.")";
        //se cambio por los dias del cliente
        $this->factureFournisseur->cond_reglement_id = 1;
        $this->factureFournisseur->mode_reglement_id = 4;
        $this->factureFournisseur->fk_account = 1;
        $estatus = 0;
        if(!empty($this->rfc_cliente)) {
            //Obtenemos el id del proveedor y lo creamos la factura
            $sql ="SELECT rowid from  llx_societe where fournisseur=1 and siren = '".$this->rfc_cliente."'";
            $result = $this->db->query($sql);
            //Validamos que si exista un proveedor
            if($result->num_rows > 0){
                $id = $this->db->fetch_object($result);
                $this->factureFournisseur->socid = $id->rowid;
                $sql1="SELECT s.cond_reglement_supplier, p.nbjour, p.sortorder  from llx_societe as s  inner join llx_c_payment_term as p on  s.cond_reglement_supplier = p.sortorder where s.rowid = ".$this->factureFournisseur->socid;
                $result1= $this->db->query($sql1);
                if($result1->num_rows >0){
                    $data= $this->db->fetch_object($result1);
                    $this->fecha_final=date("Y-m-d",$this->factureFournisseur->date);
                    $nueva=date("Y-m-d",strtotime($this->fecha_final." + ".$data->nbjour." days"));
                    $this->factureFournisseur->date_echeance = strtotime($nueva);//mktime(0,0,0,$tdate3[2],$tdate3[1],$tdate3[0]);

                }
                $state=$this->factureFournisseur->create($this->user);
                if($state>0){
                    //obtenemos el ultimo id para insertar en el pago
                    $sql ="SELECT rowid  from llx_facture_fourn  order by rowid DESC limit 1";
                    $result  = $this->db->query($sql);
                    $id= $this->db->fetch_object($result);
                    $this->idfact = $id->rowid;

                    $estatus=1;
                    $valida=0;
                }else{
                    $estatus=-2;
                    $this->errors['error'][$this->factureFournisseur->ref_supplier]['No Referencia en uso']['label'] = "Referencia en uso ".trim($this->ref_cliente )." (".$this->concepto.")";
                    return -2;
                }
            }else{
                $estatus=-2;
                $this->errors['error'][$this->rfc_cliente]['No se encotro  registro en el sistema del provedor']['label'] = "No se encotro  registro en el sistema ". $this->rfc_cliente." con referencia ". $this->ref_cliente;
            }
            //si no esta vacio el monto de la factura añadimos un producto
            if ($estatus>0 && !empty($this->cargo)){

                //añadimos el producto a la factura
                 $this->supplierInvoiceLine->fk_facture_fourn = $this->idfact;
                 $this->supplierInvoiceLine->desc = "Agregado a partir de  importacion";
                 $this->supplierInvoiceLine->qty =1;
                 $this->supplierInvoiceLine->product_type=0;
                 $this->supplierInvoiceLine->subprice = price2num($this->cargo);
                 $this->supplierInvoiceLine->rang=1;
                 $this->supplierInvoiceLine->pu_ttc = price2num($this->cargo);
                 $this->supplierInvoiceLine->total_ht= price2num($this->cargo);
                 $this->supplierInvoiceLine->total_ttc = price2num($this->cargo);
                 $this->supplierInvoiceLine->multicurrency_code ='MXN';
                 //$this->supplierInvoiceLine->fk_unit ='3';
                 $this->supplierInvoiceLine->multicurrency_subprice = price2num($this->cargo);
                 $this->supplierInvoiceLine->multicurrency_total_ht = price2num($this->cargo);
                 $this->supplierInvoiceLine->multicurrency_total_ttc = price2num($this->cargo);
                 $ok=$this->supplierInvoiceLine->insert();
                 if($ok>0){
                     $this->factureFournisseur->update_price(1);
                 }



                 $valida=2;

                if ($valida>0){


                    $sabe = new Societe($this->db);
                    $sabe->fetch($this->factureFournisseur->socid);
                    $next= $this->mod_facture->getNumRef($sabe,$this->factureFournisseur->id);
                    //var_dump($next);
                    $this->factureFournisseur->validate($this->user,$next,'1');

                    unset($this->mod_facture->validation);
                    if(!empty($this->abono)){

                        //añadimos el pago a la factura
                        if ($this->abono>0){
                            //si es mayor a 0 se agrega el pago
                            $array= array($this->idfact => $this->abono);
                            $array2= array();
                            //calculamos el pago

                            $this->paiementFourn->datepaye     = $this->fecha;
                            $this->paiementFourn->amounts      = $array;
                            $this->paiementFourn->multicurrency_amounts =$array2;
                            $this->paiementFourn->paiementid   = 4;
                            $this->paiementFourn->num_payment  = '';
                            $this->paiementFourn->note_private = '';
                            $this->paiementFourn->num_paiement = '';
                            $this->paiementFourn->note         = '';

                            $thirdparty = new Societe($this->db);
                            $thirdparty->fetch($this->factureFournisseur->socid);


                            $this->paiementFourn->create($this->user,'',$thirdparty);

                            //Agregamos el pago al banco
                             $this->paiementFourn->addPaymentToBank($this->user, 'payment_supplier', '(SupplierInvoicePayment)', 4, '', '');
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
        unset($this->factureFournisseur);
        unset($this->supplierInvoiceLine);
        unset($this->paiementFournment);
        unset($this->mod_facture);
        unset($thirdparty);
        unset($sabe);
        unset($ok);
        unset($this->idfact);
    }else{
            $sql ="SELECT rowid from  llx_societe where fournisseur=1 and siren = '".$this->rfc_cliente."'";
            $result = $this->db->query($sql);
            //Validamos que si exista un proveedor
            if($result->num_rows > 0) {
                $id = $this->db->fetch_object($result);
                $descripcion = trim($this->ref_cliente) . " (" . $this->concepto . ")";
                $cantidad = $this->cargo - $this->abono;
                $cantidad = abs($cantidad);
                $soc = new Societe($this->db);
                $soc->fetch($id->rowid);
                $descuento = $soc->set_remise_except($cantidad, $this->user, $descripcion, '', 1);
                if ($descuento > 0) {
                    return 1;
                } elseif ($descuento < 0 && strlen($descripcion) > 3) {
                    $this->errors['warning'][$descripcion]['No se puede agregar el descuento absoluto']['label'] = "No se puede agregar el descuento absoluto " . $this->rfc_cliente . " con referencia " . $this->ref_cliente;
                }
            }else{
                $descripcion = trim($this->ref_cliente) . " (" . $this->concepto . ")";
                $this->errors['error'][$descripcion]['No se encontro el provedor']['label'] = "No se encotro ningun proveedor con RFC  " . $this->rfc_cliente;
                return -2;
            }
            unset($soc);

        }

    }


}

?>
