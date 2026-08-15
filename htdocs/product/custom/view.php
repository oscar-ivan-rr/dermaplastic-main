<?php

require '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'other', 'main'));

$mesg = '';
$error = 0;
$errors = array();

$refalreadyexists = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$type = (GETPOST('type', 'int') !== '') ? GETPOST('type', 'int') : Product::TYPE_PRODUCT;

$object = new Product($db);
$object->type = $type; // so test later to fill $usercancxxx is correct

$formfile = new FormFile($db);

$entity = (empty($object->entity) ? $conf->entity : $object->entity);


if ($id > 0 || !empty($ref)) {
    $resultid = $object->fetch($id, $ref);
}

$title = $langs->trans('Product')." ".dol_trunc($object->label, 16);

if (!empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity] . '/' . get_exdir(0, 0, 0, 0, $object, 'product') . dol_sanitizeFileName($object->ref);
elseif (!empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity] . '/' . get_exdir(0, 0, 0, 0, $object, 'product') . dol_sanitizeFileName($object->ref);

$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', "", SORT_ASC, 3);
$formatosim=array("jpg","png","gif","tiff","psd","bmp","jpeg");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode('logos/thumbs/' . $mysoc->logo_mini) ?>">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= DOL_URL_ROOT ?>/includes/materialize-src/css/materialize.css">
    <link rel="stylesheet" type="text/css" href="<?= DOL_URL_ROOT ?>/theme/common/fontawesome-5/css/all.min.css?layout=classic&amp;version=11.0.3">
    <link rel="stylesheet" type="text/css" href="<?= DOL_URL_ROOT ?>/theme/common/fontawesome-5/css/v4-shims.min.css?layout=classic&amp;version=11.0.3">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
        }

        .brand-logo img {
            height: 4.2rem;
            width: auto;
            vertical-align: middle;
        }

        .brand-logo span {
            font-size: 1.4rem;
        }
        .card .indicators .indicator-item {
            background-color: black;
        }

        @media (max-width: 990px) {
            .brand-logo span {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 630px) {
            .brand-logo img {
                height: 4rem;
            }

            .brand-logo span {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 445px) {
            .brand-logo {
                left: 50%;
                transform: translateX(-50%);
            }
            .brand-logo span {
                display: none;
            }
        }
    </style>
</head>

<body class="grey lighten-2">
    <header>
        <div class="navbar-fixed">
            <nav>
                <div class="nav-wrapper white ">
                    <a href="#" class="ml-3 brand-logo black-text valign-wrapper">
                        <img class="responsive-img " style="height: 3.5rem;" src="<?= DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode('logos/thumbs/' . $mysoc->logo_small) ?>" alt="" >
                        <span class="black-text company"><?= $mysoc->name ?></span>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <?php if ($resultid > 0) { ?>
            <div class="container-fluid">
                <div class="row">
                    <div class="col s12 offset-m2 m8">
                        <?php
                        $htmlPhoto = '<div style="margin: 0.3rem;">';
                        $htmlPhoto .= $object->show_cover_public('product', $conf->product->multidir_output[$entity], 1);
                        $htmlPhoto .= '</div>';

                        ?>
                        <!-- Large screens -->
                        <div class="card horizontal hide-on-med-and-down" style="margin-top: 2.5rem;">
                            <div class="card-image valign-wrapper" style="width: 300px">
                                <div class="carousel" style="min-height: 300px;width: 100%;height: 100%;position: ">
                                    <?php
                                    if(!empty($filearray)){
                                        $flag=false;
                                        foreach ($filearray as $key=>$y){
                                            $extension = new SplFileInfo($y["name"]);
                                            $extenim=$extension->getExtension();
                                            if(in_array($extenim,$formatosim)){
                                                $ruta= "../../../documents/produit/".$y['level1name']."/".$y['relativename'];
                                                print "<img  class=\"carousel-item img-responsive\" src='" .$ruta. "'  style=\"min-height: 300px;width: 100%;height: 100%;position: absolute;top: 0%;left: 0%;\">";
                                                $flag=true;
                                            }
                                        }
                                        if(!$flag){
                                            $nophoto = '/public/theme/common/nophoto.png';
                                            print "<img  class=\"carousel-item img-responsive\" src='" . DOL_URL_ROOT . $nophoto . "'  style=\"min-height: 300px;width: 100%;height: 100%;position: absolute;top: 0%;left: 0%;\">";
                                        }
                                    }else{
                                       print $htmlPhoto;
                                    }

                                    ?>
                                </div>
                            </div>
                            <div class="card-stacked">
                                <div class="card-content">
                                    <h6 class="teal-text">Ref: <?= $object->ref ?></h6>
                                    <div class="">
                                        <span class="card-title"><?= $object->label ?></span>
                                        <p class="text-justify" style="font-size: 16px;"><?= $object->description ?></p>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <div class="flex-container flex-row flex-space-between">
                                        <div class="flex-child ">
                                            <span class="mr-5 "><strong>Precio:</strong> $<?= price($object->price_ttc) ?></span>
                                            <span><strong>Almacen:</strong> <?= is_null($object->stock_reel) ? 0 : $object->stock_reel ?></span>
                                        </div>
                                        <div class="flex-child">
                                            <span style="position: relative; top: -5px;"><strong>Ficha Tecnica</strong></span>
                                            <a href="#files" class="modal-trigger red-text text-darken-3"><span class="hide-on-med-and-down"><i class="material-icons">description</i></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Small screens -->
                        <div class="card hide-on-large-only" style="margin-top: 2.5rem;">
                            <div class="card-image" style="height: 250px">
                                <div class="carousel center-align" style="justify-content: center;align-items: center;width: 100%;height: 100%;">
                                    <?php
                                    if(!empty($filearray)){
                                        $flag=false;
                                        foreach ($filearray as $key=>$y){
                                            $extension = new SplFileInfo($y["name"]);
                                            $extenim=$extension->getExtension();
                                            if(in_array($extenim,$formatosim)){
                                                $ruta= "../../../documents/produit/".$y['level1name']."/".$y['relativename'];
                                                print "<img  class=\"carousel-item img-responsive\" src='" .$ruta. "'  style=\"width: 100%;height: 100%;position: absolute;top: 0%;left: 0%;\">";
                                                $flag=true;
                                            }
                                        }
                                        if(!$flag){
                                            $nophoto = '/public/theme/common/nophoto.png';
                                            print "<img  class=\"carousel-item img-responsive\" src='" . DOL_URL_ROOT . $nophoto . "'   style=\"width: 100%;height: 100%;position: absolute;top: 0%;left: 0%;\">";
                                        }
                                    }else{
                                        print $htmlPhoto;
                                    }

                                    ?>
                                </div>

                            </div>
                            <div class="card-content">
                                    <h6 class="teal-text">Ref: <?= $object->ref ?></h6>
                                    <div class="">
                                        <span class="card-title"><?= $object->label ?></span>
                                        <p class="text-justify" style="font-size: 16px;"><?= $object->description ?></p>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <div class="flex-container flex-row flex-space-between">
                                        <span class="flex-child" class="mr-5"><strong>Precio:</strong> $<?= price($object->price_ttc) ?></span>
                                        <span class="flex-child"><strong>Almacen:</strong> <?= is_null($object->stock_reel) ? 0 : $object->stock_reel ?></span>
                                        <span style="position: relative; top: -5px;"><strong>Ficha Tecnica</strong></span>
                                        <span><a href="#files" class="modal-trigger red-text text-darken-3"><i class="material-icons">description</i></a></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Structure -->
            <div id="files" class="modal bottom-sheet">
                <div class="modal-content">
                    <h4>Documentos</h4>
                    <!-- List of document -->
                    <?=$formfile->list_of_public_files($filearray, $upload_dir, $langs->trans('NoFileFound'), 'none');?>
                    <div class="modal-footer">
                        <a href="#!" class="modal-close waves-effect waves-light btn-flat indigo darken-2 white-text">Cerrar</a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </main>

    <footer class="page-footer grey darken-2">
        <div class="container">
            <div class="row">
                <div class="col s12 center-align">
                    <a href="#" target="_blank">
                        <img src="<?= DOL_URL_ROOT . '/viewimage.php?cache=1&modulepart=mycompany&file=' . urlencode('logos/thumbs/' . $mysoc->logo_small) ?>" alt="">
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-copyright">
            <div class="container">
                <div class="center-align">
                    <span class="white-text left ">Copyright &copy;<script> document.write(new Date().getFullYear());</script> </span>
                    <a class="white-text right" href="mailto: <?= $mysoc->email ?>"><?= $mysoc->email ?></a>
                </div>

            </div>
        </div>
    </footer>

    <script type="text/javascript" src="<?= DOL_URL_ROOT ?>/includes/materialize-src/js/bin/materialize.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.querySelectorAll('#files');
            var instanceModal = M.Modal.init(modal);

            var materialboxed = document.querySelectorAll('.materialboxed');
            var instanceBoxed = M.Materialbox.init(materialboxed);
        });
        document.addEventListener('DOMContentLoaded', ()=> {
            const elements = document.querySelectorAll('.carousel');
            M.Carousel.init(elements,{
               duration: 10,
               dist: 0,
               shift: 5,
               padding: 5,
               numVisible: 1,
               indicators: true,
               noWrap: false,
               fullWidth: true
            });
        });

    </script>
</body>

</html>

<?php

$db->close();

?>