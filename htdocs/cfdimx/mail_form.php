<?php
#Envio de correo
$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');

$object = new Facture($db);
//$extrafields = new ExtraFields($db);

// Load object
$ret = $object->fetch($id, $ref, '', '', $conf->global->INVOICE_USE_SITUATION);

// Presend form
$modelmail='facture_send';
$defaulttopic='SendBillRef';
$diroutput = $conf->facture->dir_output;
$trackid = 'inv'.$object->id;

$langs->load("mails");

$titreform='SendMail';

$object->fetch_projet();

if (! in_array($object->element, array('societe', 'user', 'member')))
{
    // TODO get also the main_lastdoc field of $object. If not found, try to guess with following code
    $ref = dol_sanitizeFileName($object->ref);
    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    
    #Se adjuntan todos los documentos(CFDI y complementos si es que tiene) al correo 
    $fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
    $pdf_fac_tim = $fileparams['path'].'/'.$uuid.'.pdf'; //uuid del comprobante cfdi
    $xml_fac_tim = $fileparams['path'].'/'.$uuid.'.xml'; //uuid del comprobante cfdi

    //Consulta de Pagos
    $sql = 'SELECT pf.fk_paiement, p.rowid, p.ref, recep.uuid, recep.rel_facture FROM '.MAIN_DB_PREFIX.'paiement_facture pf INNER JOIN '.MAIN_DB_PREFIX.'paiement p ON pf.fk_paiement=p.rowid INNER JOIN '.MAIN_DB_PREFIX.'cfdimx_recepcion_pagos recep ON pf.fk_paiement=recep.fk_paiement WHERE pf.fk_facture='.$id;
    //echo $sql;
    $result = $db->query($sql);
    if ($db->num_rows($result) > 0) {
        
        while($res = $db->fetch_object($result)){
            // echo "<pre>";
            // print_r($res);   
            // echo "</pre>";

            if ($res->rel_facture == 1) {
                $fileparams['path'] = $diroutput . '/' .$res->ref;
            }
            else {
                $fileparams['path'] = $diroutput."/".$object->ref;
            }

            $uuidPago = $res->uuid;

            $pdf_fac_pago = $fileparams['path'].'/Pago_'.$uuidPago.'.pdf';
            $xml_fac_pago = $fileparams['path'].'/Pago_'.$uuidPago.'.xml';
            
        }
    }

    if ($pdf_fac_pago != "" && $xml_fac_pago != "") {
        $doctos_array = array($pdf_fac_tim, $xml_fac_tim, $pdf_fac_pago, $xml_fac_pago);
    }
    else {
        $doctos_array = array($pdf_fac_tim, $xml_fac_tim);
    }
}

// Define output language
$outputlangs = $langs;
$newlang = '';
if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) {
    $newlang = $_REQUEST['lang_id'];
}
if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
    $newlang = $object->thirdparty->default_lang;
}

if (!empty($newlang)) {
    $outputlangs = new Translate('', $conf);
    $outputlangs->setDefaultLang($newlang);
    // Load traductions files requiredby by page
    $outputlangs->loadLangs(array('commercial','bills','orders','contracts','members','propal','products','supplier_proposal','interventions'));
}

$topicmail='';
if (empty($object->ref_client)) {
    $topicmail = $outputlangs->trans($defaulttopic, '__REF__');
} elseif (! empty($object->ref_client)) {
    $topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
}

// Build document if it not exists
$forcebuilddoc=true;
if (in_array($object->element, array('societe', 'user', 'member'))) $forcebuilddoc=false;
if ($object->element == 'invoice_supplier' && empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF)) $forcebuilddoc=false;
if ($forcebuilddoc)    // If there is no default value for supplier invoice, we do not generate file, even if modelpdf was set by a manual generation
{
    if ((! $file || ! is_readable($file)) && method_exists($object, 'generateDocument'))
    {
        $result = $object->generateDocument(GETPOST('model') ? GETPOST('model') : $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        if ($result < 0) {
            dol_print_error($db, $object->error, $object->errors);
            exit();
        }
        if ($object->element == 'invoice_supplier')
        {
            $fileparams = dol_most_recent_file($diroutput . '/' . get_exdir($object->id, 2, 0, 0, $object, $object->element).$ref, preg_quote($ref, '/').'([^\-])+');
        }
        else
        {
            $fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
        }
        $file = $fileparams['fullname'];
    }
}

print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
print '<div class="clearboth"></div>';
print '<br>';
print load_fiche_titre($langs->trans($titreform),'', 'bill');

dol_fiche_head('');

// Create form for email
include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
//include_once DOL_DOCUMENT_ROOT . '/cfdimx/class/html.formmail_cfdi.class.php';
$formmail = new FormMail($db);

$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
$formmail->fromtype = (GETPOST('fromtype')?GETPOST('fromtype'):(!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE)?$conf->global->MAIN_MAIL_DEFAULT_FROMTYPE:'user'));

if ($formmail->fromtype === 'user')
{
    $formmail->fromid = $user->id;
}
$formmail->trackid=$trackid;
if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))    // If bit 2 is set
{
    include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
    $formmail->frommail=dolAddEmailTrackId($formmail->frommail, $trackid);
}
$formmail->withfrom = 1;

// Fill list of recipient with email inside <>.
$liste = array();
if ($object->element == 'expensereport')
{
    $fuser = new User($db);
    $fuser->fetch($object->fk_user_author);
    $liste['thirdparty'] = $fuser->getFullName($outputlangs)." <".$fuser->email.">";
}
elseif ($object->element == 'societe')
{
    foreach ($object->thirdparty_and_contact_email_array(1) as $key => $value) {
        $liste[$key] = $value;
    }
}
elseif ($object->element == 'contact')
{
    $liste['contact'] = $object->getFullName($outputlangs)." <".$object->email.">";
}
elseif ($object->element == 'user' || $object->element == 'member')
{
    $liste['thirdparty'] = $object->getFullName($outputlangs)." <".$object->email.">";
}
else
{
    if (is_object($object->thirdparty))
    {
        foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
            $liste[$key] = $value;
        }
    }
}
if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
    $listeuser=array();
    $fuserdest = new User($db);

    $result= $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql'=>'t.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''), 'AND', true);
    if ($result>0 && is_array($fuserdest->users) && count($fuserdest->users)>0) {
        foreach($fuserdest->users as $uuserdest) {
            $listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id, 'email');
        }
    } elseif ($result<0) {
        setEventMessages(null, $fuserdest->errors, 'errors');
    }
    if (count($listeuser)>0) {
        $formmail->withtouser = $listeuser;
        $formmail->withtoccuser = $listeuser;
    }
}

$formmail->withto = GETPOST('sendto') ? GETPOST('sendto') : $liste;
$formmail->withtocc = $liste;
$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
$formmail->withtopic = $topicmail;
$formmail->withfile = 2;
$formmail->withbody = 1;
$formmail->withdeliveryreceipt = 1;
$formmail->withcancel = 1;

//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;

// Make substitution in email content
$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $object);
$substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $object->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
$substitutionarray['__PERSONALIZED__'] = '';    // deprecated
$substitutionarray['__CONTACTCIVNAME__'] = '';
$parameters = array(
    'mode' => 'formemail'
);
complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);

// Find the good contact address
$tmpobject = $object;
if (($object->element == 'shipping'|| $object->element == 'reception')) {
    $origin = $object->origin;
    $origin_id = $object->origin_id;

    if (!empty($origin) && !empty($origin_id)) {
        $element = $subelement = $origin;
        if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
            $element = $regs[1];
            $subelement = $regs[2];
        }
        // For compatibility
        if ($element == 'order')    {
            $element = $subelement = 'commande';
        }
        if ($element == 'propal')   {
            $element = 'comm/propal';
            $subelement = 'propal';
        }
        if ($element == 'contract') {
            $element = $subelement = 'contrat';
        }
        if ($element == 'inter') {
            $element = $subelement = 'ficheinter';
        }
        if ($element == 'shipping') {
            $element = $subelement = 'expedition';
        }
        if ($element == 'order_supplier') {
            $element = 'fourn';
            $subelement = 'fournisseur.commande';
        }
        if ($element == 'project') {
            $element = 'projet';
        }

        dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
        $classname = ucfirst($origin);
        $objectsrc = new $classname($db);
        $objectsrc->fetch($origin_id);

        $tmpobject = $objectsrc;
    }
}

$custcontact = '';
$contactarr = array();
$contactarr = $tmpobject->liste_contact(- 1, 'external');

if (is_array($contactarr) && count($contactarr) > 0) {
    require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
    $contactstatic = new Contact($db);

    foreach ($contactarr as $contact) {
        $contactstatic->fetch($contact['id']);
        $substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($outputlangs, 1);
    }
}

// Tableau des substitutions
$formmail->substit = $substitutionarray;

// Tableau des parametres complementaires
$formmail->param['action'] = 'send';
$formmail->param['models'] = $modelmail;
$formmail->param['models_id']=GETPOST('modelmailselected', 'int');
$formmail->param['id'] = $object->id;
$formmail->param['returnurl'] = DOL_URL_ROOT . '/cfdimx/facture.php?id=' . $object->id;

//$formmail->param['fileinit'] = array($pdf_fac_tim,$xml_fac_tim);
$formmail->param['fileinit'] = $doctos_array;

// Show form
print $formmail->get_form();

dol_fiche_end();
?>