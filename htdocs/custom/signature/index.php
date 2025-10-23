<?php
/* Copyright (C) 2020	Daniel Molina	<danmnvx@gmail.com>

/**
 *	\file       htdocs/custom/signature/index.php
 *	\brief      Page to draw client signature
 */

if (!$conf->global->MAIN_SIGNATURE_MODULE) {
    die("Extensión no habilitada");
}

// Load translation files required by the page
$langs->loadLangs(array('main'));

/*
 *	View
 */
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $langs->trans('Signature') . " - $ref" ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode('logos/thumbs/' . $mysoc->logo_mini) ?>">
    <style>
        * {
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
        }

        html,
        body {
            min-height: 100%;
            height: 100%;
            max-width: 100%;
            width: 100%;
            overflow: hidden;
        }

        html,
        body,
        form {
            margin: 0px;
        }

        html,
        form {
            padding: 0px;
        }

        html,
        body,
        fieldset {
            background: transparent;
        }

        fieldset {
            position: absolute;
            border: 5px solid transparent;
            background: transparent;
            right: 0px;
            bottom: 0px;
        }

        #signature-area {
            outline: 5px solid #aaa;
            background: #fff;
        }
    </style>
    <link rel="stylesheet" href="<?= DOL_URL_ROOT ?>/includes/materialize-src/css/materialize.css">
    <link rel="stylesheet" href="<?= DOL_URL_ROOT ?>/includes/jquery/plugins/jnotify/jquery.jnotify.min.css">
    <script src="<?= DOL_URL_ROOT ?>/includes/jquery/js/jquery.min.js"></script>
    <script src="<?= DOL_URL_ROOT ?>/includes/jquery/plugins/jnotify/jquery.jnotify.min.js"></script>
</head>

<body>

    <?php if ($id > 0) { ?>
        <!-- Form with canvas and img_sign hidden -->
        <form id="formSignature" method="POST" enctype="multipart/form-data">
            <!-- <input type="hidden" name="action" value="sign_doc"> -->
            <input type="hidden" id="img_sign" name="img_sign">
            <input type="hidden" id="objectfile" name="objectfile" value="<?= $objectfile ?>">
            <input type="hidden" id="element" name="element" value="<?= $element ?>">
            <input type="hidden" id="dir_output" name="dir_output" value="<?= $dir ?>">
            <input type="hidden" id="model_pdf" name="model_pdf" value="<?= $model_pdf ?>">
            <?php foreach($moreinputs as $name => $value) { ?>
                <input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
            <?php } ?>
            <canvas class="pad" id="signature-area"></canvas>

            <!-- Buttons -->
            <fieldset class="valign-wrapper">
                <div class="flex-container flex-between">
                    <img src="<?= DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode('logos/thumbs/' . $mysoc->logo_small) ?>" alt="" height="40" class="flex-child">
                    <input type="reset" value="Limpiar" class="btn red darken-3 ml-2 flex-child">
                    <input id="saveSignature" type="button" value="Guardar" class="btn indigo darken-2 mr-3 ml-1 flex-child">
                </div>
            </fieldset>
        </form>

    <?php } else {
        /* Oject not found */
        print "Object undefined or access refuse";
    } ?>
    <!-- Required scripts -->
    <script src="<?= DOL_URL_ROOT ?>/includes/html2canvas/html2canvas.js"></script>
    <script src="<?= DOL_URL_ROOT ?>/includes/signature-pad/assets/numeric-1.2.6.min.js"></script>
    <script src="<?= DOL_URL_ROOT ?>/includes/signature-pad/assets/bezier.js"></script>
    <script src="<?= DOL_URL_ROOT ?>/includes/signature-pad/jquery.signaturepad.js"></script>
    <script>
        (function(window) {
            var canvas,
                onResize = function(event) {
                    // Full screen canvas.
                    canvas.attr({
                        height: window.innerHeight,
                        width: window.innerWidth
                    });
                };

            $(document).ready(function() {
                canvas = $('#signature-area');
                // Full screen and resize canvas
                window.addEventListener('orientationchange', onResize, false);
                window.addEventListener('resize', onResize, false);
                onResize();

                // Signature pad init
                $('#formSignature').signaturePad({
                    drawOnly: true,
                    drawBezierCurves: true,
                    defaultAction: 'drawIt',
                    lineWidth: 0,
                    penWidth: 5,
                    clear: 'input[type=reset]',
                });
            });

            // Save signature
            $('#saveSignature').click(function(e) {
                var signature = document.getElementById('signature-area');
                var image = signature.toDataURL("image/png");
                
                // Put data image in form
                $("#img_sign").val(image);

                // Submit form
                $.ajax({
                    type: "POST",
                    url: '<?= DOL_MAIN_URL_ROOT ?>/custom/signature/ajax.php?id=<?= $id ?>',
                    data: $('#formSignature').serialize(),
                    dataType: 'json'
                })
                    .done((response, textStatus, jqXHR) => {
                        // console.log(response, textStatus, jqXHR);
                        if (response.redirect)
                            window.location.href = `${response.url}<?= (!empty($params) ? '&' . implode("&", $params) : '') ?>`;
                        else
                            window.location.href = `<?= $_SERVER['PHP_SELF'] . "?id=$id" . (!empty($params) ? '&' . implode("&", $params) : '') ?>&saved=${(response.ok) ? 1 : 0}`;
                    })
                    .fail((jqXHR, err, textStatus) => {
                        // console.log(jqXHR, err, textStatus);
                        if (jqXHR.status == 400)
                            $.jnotify(`${err} ${textStatus}: ${jqXHR.responseJSON.msg}`, 'error');
                        else
                            $.jnotify("Ocurrió un error inesperado en el servidor.", 'error');
                    });
            });

        }(this));
    </script>
    <script src="<?= DOL_URL_ROOT ?>/includes/signature-pad/assets/json2.min.js"></script>

</body>

</html>

<?php

$db->close();

?>