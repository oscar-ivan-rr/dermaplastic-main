<?php
/* Copyright (C) 2020	Daniel Molina	<danmnvx@gmail.com>

/**
 *	\file       htdocs/custom/signature/ajax.php
 *	\brief      Form to save signature image
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

if ($conf->global->MAIN_SIGNATURE_MODULE) {
    $langs->loadLangs('main');
    
    $force_redirect = GETPOST('force_redirect', 'int', 2);
    $objectfile = GETPOST('objectfile', 'alpha', 2);
    $element = GETPOST('element', 'alpha', 2);
    $id = GETPOST('id', 'int');
    
    if (!empty($objectfile) && !empty($element)) {
        require_once DOL_DOCUMENT_ROOT.$objectfile;
        // Load object. Make an object->fetch
        
        $object = new $element($db);
        if ($object->fetch($id) > 0) {
            // Data image
            $data_sign = GETPOST('img_sign', 'none', 2);
            $dir = GETPOST('dir_output', 'none', 2);
            $model_pdf = GETPOST('model_pdf', 'alpha', 2);
                
            if (!empty($data_sign)) {

                $signatureFileName = 'signature.png';
                $signature = str_replace('data:image/png;base64,', '', $data_sign);
                $signature = str_replace(' ', '+', $signature);
            
                // Decode data image
                $data = base64_decode($signature);
            
                // Path to file
                $file = "$dir/$signatureFileName";

                // Build dir
                if (dol_mkdir($dir) >= 0) {
                    // Save image
                    if(file_put_contents($file, $data) !== false) {
                        $langs->loadLangs(array("main", "other", "errors"));
                        // Build doc with given signature
                        if ($object->generateDocument((!empty($model_pdf)) ? $model_pdf : $object->model_pdf, $langs)) {
                            // Url to force redirect
                            if (intval($force_redirect) == 1 && GETPOST('url_doc', 'int', 2) == 1) {
                                $urladvancedpreview = getAdvancedPreviewUrl(GETPOST('modulepart', 'alpha', 2), GETPOST('relativepath', 'none', 2), 1); // Return if a file is qualified for preview.
                                if (count($urladvancedpreview)) $redirect = $urladvancedpreview['url'];
                            }
                            elseif (GETPOSTISSET('url')) $redirect = GETPOST('url', 'none', 2);
                            buildResponse(true, $langs->trans('DocSigned', $object->ref), $redirect);
                        }
                        else
                            buildResponse(false, $langs->trans('ErrorSigningDoc', $object->ref));
                    }
                    else
                        buildResponse(false, $langs->trans('ErrorBuildSignature'));
                }
                else
                    buildResponse(false, $langs->trans('ErrorCanNotCreateDir', $dir));
            }
            else buildResponse(false, $langs->trans('NoSignature'));
        }
        else buildResponse(false, $langs->trans('ObjectNotFound', $element));
    }
    else buildResponse(false, $langs->trans('ErrorObjectBuild'));
}
else buildResponse(false, "Extensión de firma digital no habilitada");

/**
 * Build response to ajax call.
 * @param   boolean         $ok         True success | False error
 * @param   string          $text       Label to show in notification
 * @param   null|string     $redirect   Url to redirect
 */
function buildResponse($ok, $text, $redirect = null) {
    global $force_redirect;

    $force_redirect = intval($force_redirect);
    if ($force_redirect != 1) setEventMessage($text, ($ok) ? 'mesgs' : 'errors');

    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code($ok ? 201 : 400);
    die(json_encode(array('ok' => $ok, 'msg' => $text, 'redirect' => ($force_redirect == 1) ? true : false, 'url' => $redirect)));
}

?>