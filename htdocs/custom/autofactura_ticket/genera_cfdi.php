<?php
require_once('../../master.inc.php');
require( DOL_DOCUMENT_ROOT . '/cfdimx/conf.php' );
include( DOL_DOCUMENT_ROOT . '/cfdimx/lib/nusoap/lib/nusoap.php' );
include( DOL_DOCUMENT_ROOT . '/cfdimx/lib/phpqrcode/qrlib.php' );
require( DOL_DOCUMENT_ROOT . '/cfdimx/lib/numero_a_letra.php' );

require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
$error = 0;

if( !empty($_POST['fk_socid']) && !empty($_POST['fk_facture'])){
    $email = $_POST['email'];
    #Datos de la factura dolibarr
    ob_start();
    $sql   = " SELECT * FROM " . MAIN_DB_PREFIX . "facture WHERE rowid = " . $_POST['fk_facture'];
    $resql = $db->query($sql);
    if ($resql) {
        $num_fact = $db->num_rows($resql);
        $i        = 0;
        if ($num_fact) {
            while ($i < $num_fact) {
                $obj       = $db->fetch_object($resql);
                $ref = $obj->ref;
                $separafac = explode("-", $ref);
                $serie     = $separafac[0];
                $folio     = $separafac[1];
                $i++;
            }
        }
    }
//$soc_rfc='';
#Datos del receptor
    $sql = "SELECT * FROM  " . MAIN_DB_PREFIX . "facture f,  " . MAIN_DB_PREFIX . "societe s WHERE f.rowid = '" . $_POST['fk_facture'] . "' AND f.fk_soc = s.rowid";
    $resql = $db->query($sql);
    if ($resql) {
        $soc_num = $db->num_rows($resql);
        $i       = 0;
        if ($soc_num) {
            while ($i < $soc_num) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $soc_rfc   = $obj->siren;
                    $soc_id    = $obj->rowid;
                    $soc_email = $obj->email;
                    $status    = $obj->fk_statut;
                }
                $i++;
            }
        }
    }

#Datos de configuración del módulo
    $sqlconfig = "SELECT * FROM  " . MAIN_DB_PREFIX . "cfdimx_config WHERE emisor_rfc = '" . $conf->global->MAIN_INFO_SIREN . "' AND entity_id = " . $conf->entity;
    $resql = $db->query($sqlconfig);
    if ($resql) {
        $conf_num = $db->num_rows($resql);
        $i        = 0;
        if ($conf_num) {
            while ($i < $conf_num) {
                $obj = $db->fetch_object($resql);
                if ($obj) {
                    $status_conf     = $obj->status_conf;
                    $modo_timbrado   = $obj->modo_timbrado;
                    $passwd_timbrado = $obj->password_timbrado_txt;
                }
                $i++;
            }
        }
    }
    $guion = '-';
    $cliente_id = $_POST['fk_socid'];
    $facid = $_POST['fk_facture'];
    $_REQUEST["facid"] = $facid;
    $_REQUEST['tpdomi'] = 'Domicilio';
    $_REQUEST['osd'] = 'MXN';
    $movil = 'si';
    $uuid = $result["return"]["uuid"];
    include(DOL_DOCUMENT_ROOT . '/cfdimx/generaCFDI.php');
    include(DOL_DOCUMENT_ROOT . '/cfdimx/generaPDF_autofactura.php');

    $filepath_pdf = $conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/";
    $filename_pdf = $result["return"]["uuid"].".pdf";
    $filepath_pdf .= dol_sanitizeFileName($filename_pdf);

    $filepath_xml = $conf->facture->dir_output."/".strtoupper($serie).$guion.$folio."/";
    $filename_xml = $result["return"]["uuid"].".xml";
    $filepath_xml .= dol_sanitizeFileName($filename_xml);
    
    if($cfdi_code >= 0){
        if( !empty($email)){
            /*--------------------------Envio de los archivos-----------------------------*/
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
            $subject = "Archivos de timbrado de la factura ".strtoupper($serie).$guion.$folio;
            $sendto = $email; //email user
            $from = $conf->global->MAIN_MAIL_EMAIL_FROM;//email from company
            $message = "";
            $message .= "
                                    <p><b>Estimado cliente:</b></p>
                                    <p><b>Su factura ".strtoupper($serie).$guion.$folio." se generó exitosamente.</b></p>
                                    <p>Para mayor información, dudas o aclaraciones, contáctanos o visita nuestras sucursales.</p>
                                    <p>Atención al cliente 4497206639.</p>
                                    <br>
                                ";
            $mimetype_pdf = dol_mimetype($filepath_pdf);
            $mimetype_xml = dol_mimetype($filepath_xml);
            
            $mailfile = new CMailFile($subject, $sendto, $from, $message, array($filepath_pdf, $filepath_xml), array($mimetype_pdf, $mimetype_xml), array($filename_pdf, $filename_xml), $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);

            if (!$mailfile->error) {
                $result_mail = $mailfile->sendfile();
                if ($result_mail) {
                    $msg_email = "Correo enviado con exito";

                } else {//error with send mail
                    $msg_email = 'Error enviando el correo';
                }
            } else {
                $msg_email = 'Error enviando el correo';
            }
        }else{
            $msg_email = 'No se ingresó un correo';
        }
    }else{
        if( !empty($email)){
            /*--------------------------Envio de los archivos-----------------------------*/
            require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
            $subject = "Error en la generación de la factura ".strtoupper($serie).$guion.$folio;
            $sendto = $email; //email user
            $from = $conf->global->MAIN_MAIL_EMAIL_FROM;//email from company
            $message = "";
            $message .= "
                                    <p><b>Estimado cliente:</b></p>
                                    <p><b>Su factura no se generó correctamente. Es posible que alguno de los datos ingresados sean erróneos o que los datos no coincidan con la información brindada por el SAT.</b></p>

                                    <p>Queremos ayudarlo, para generar nuevamente la solicitud, favor de contactarse directamente con uno de nuestros asesores al 4497206639</p>

                                    <p>Si usted no ha realizado esta acción, favor de ignorar este correo.</p>

                                    <p>Para mayor información, dudas o aclaraciones, contáctanos o visita nuestras sucursales.</p>
                                    <br>
                                ";
            $mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);

            if (!$mailfile->error) {
                $result_mail = $mailfile->sendfile();
                if ($result_mail) {
                    $msg_email = "Correo enviado con exito";

                } else {//error with send mail
                    $msg_email = 'Error enviando el correo';
                }
            } else {
                $msg_email = 'Error enviando el correo';
            }
        }else{
            $msg_email = 'No se ingresó un correo';
        }
    }

    ob_get_clean();
}
else{
    $error++;
}
$resultado = array(
    'path_pdf' => strtoupper($serie).$guion.$folio."/".$filename_pdf,
    'path_xml' => strtoupper($serie).$guion.$folio."/".$filename_xml,
    'msg_email' => $msg_email,
    'msg' => $msg_cfdi_final,
    'error' => $error,
    'cfdi_code' => $cfdi_code,
);
echo json_encode($resultado);
