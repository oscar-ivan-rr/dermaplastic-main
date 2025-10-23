<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
global $langs,$db,$mysoc;
$langs->loadLangs(array('companies', 'propal', 'compta', 'bills', 'orders', 'products', 'deliveries', 'sendings', 'other'));

header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
?>
<html>
<head>
    <title>Print ticket</title>
    <style type="text/css">

        body {
            font-size: 14px;
            margin-left: 40px;
            margin-right: 40px;
            position: relative;
            /*font-family: monospace,courier,arial,helvetica,system;*/
            font-family: Arial;
        }

        .entete {
            /* 		position: relative; */
        }

        .adresse {
            /* 			float: left; */
            font-size: 10px;
        }

        .date_heure {
            float: right;
            font-size: 12px;
            width: 100%;
            text-align: center;
        }

        .infos {
            position: relative;
            font-size: 14px;
        }


        .liste_articles {
            width: 98%;
            /*border-bottom: 1px solid #000;*/
            text-align: center;
            font-size: 13px;
        }

        .liste_articles tr.titres th {
            /*border-bottom: 1px solid #000;*/
            font-size: 13px;
            padding-bottom: 15px;
        }
        .liste_articles tr.list td {
            /*border-bottom: 1px solid #000;*/
            font-size: 13px;
            padding-bottom: 20px;
        }

        .liste_articles td.total {
            text-align: right;
            font-size: 13px;
        }

        .total_tot {
            font-size: 13px;
            font-weight: bold;
            text-align: right;
        }

        .totaux {
            margin-top: 20px;
            width: 40%;
            float: right;
            text-align: right;
            font-size: 14px;
        }

        .totpay {

            width: 300px;
            float: right;
            text-align: left;
            font-size: 12px;
        }

        .note{
            float: right;
            font-size: 12px;
            width: 100%;
            text-align: center;
        }

        .lien {
            position: absolute;
            top: 0;
            left: 0;
            display: none;
            font-size: 12px;
        }

        @media print {

            .lien {
                display: none;
            }
            @page{

                margin: 0;

            }

        }
    </style>
</head>

<body>

<div class="entete">
    <table style="margin: 0 auto;text-align: center;">
        <tr>
            <td>
                <div class="logo">
                    <?php print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('receipts/imgtop.png').'">'; ?>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <p class=""><?php echo $mysoc->name; ?><br>
            </td>
        </tr>
        <tr>
            <td>
                Regimen fiscal: (601) General de Ley Personas Morales
            </td>
        </tr>
        <tr>
            <td>
                RFC: CEZ000120D99
            </td>
        </tr>
        <tr>
            <td>
                Tel: 492 899 5640
            </td>
        </tr>
        <tr>
            <td>
                cezac@prodigy.net.mx
            </td>
        </tr>
        <?php
        $object = new Propal($db);
        $object->fetch($id);
        print '<tr><td>&nbsp;</td></tr>';
        print '<tr>';
        print '<td>';
        print '<b>COTIZACIÓN</b>';
        print '</td>';
        print '</tr>';
        print '<tr>';
        print '<td>';
        print '<b>'.$object->ref.'</b>';
        print '</td>';
        print '</tr>';
        print '</table>';
        print '<table style="width: 100%;">';
        print '<tr><td colspan="2" align="left">';
        print 'FECHA Y HORA:'.dol_print_date($object->date_creation,'dayhour').'<br>';
        print '</td></tr>';
        print '<tr><td>&nbsp;</td></tr>';
        //cliente
        $soc = new Societe($db);
        $soc->fetch($object->socid);
        $formcompany = new FormCompany($db);
        $arr = $formcompany->typent_array(1);
        $typent = $arr[$soc->typent_code];
        print '<tr><td align="left">';
        print 'CLIENTE: '.$soc->nom;
        print '</td></tr><tr><td align="left">TIPO: '.$typent;
        print '</td></tr>';
        //Empleado
        $userstatic=new User($db);
        $userstatic->fetch($object->user_author_id);
        print '<tr><td colspan="2" align="left">NOMBRE DEL EMPLEADO:</td></tr>';
        print '<tr><td colspan="2" align="left">'.$userstatic->firstname.' '.$userstatic->lastname;
        //print '<tr><td colspan="2" align="left">'.$object->fk_user_author;
        print '</td></tr>';
        print '<tr><td colspan="2">&nbsp;</td></tr>';
        //CANTIDAD CÓDIGO DESCRIPCIÓN PRECIO IMPORTE
        ?>
        <table class="liste_articles">
            <tr class="titres"><th style="font-size: 11px;width: 50px" align="left">CANT</th><th style="font-size: 11px;width: 150px" align="left">COD</th><th style="font-size: 11px;width: 400px" align="left">DESCRIPCIÓN</th><th style="font-size: 11px;" align="right">P.U</th><th style="font-size: 11px;" align="right">Dto. %</th><th style="font-size: 11px;" align="right">P.U c/Dto</th><th style="font-size: 11px;margin-right: 5%;" align="right">IMPORTE</th></tr>
            <?php
            $tot_iva=0;
            // checkpoint
            foreach ($object->lines as $line){
                $product = new Product($db);
                $productid=0;
                $idprod = $line->fk_product;
                $refprod = $line->product_label;
                if ($idprod > 0 || ! empty($refprod))
                {
                    $result = $product->fetch($idprod,$refprod);
                    $productid=$product->idprod;
                    $idprod=$product->idprod;
                }
                //var_dump($line);
                $dto=$line->remise_percent?$line->remise_percent:0;
                $pu=$line->subprice*(1-($dto/100));
                $ref=$line->desc?$line->desc:$product->label;
                echo ('<tr class="list">
                        <td align="left">'.$line->qty.'</td>
                        <td align="left">'.$product->ref.'</td>
                        <td align="justify" style="width: 60%;">'.$ref.'</td>
                        <td align="right">'.price($line->subprice,"","","","",2).'</td>
                        <td align="right">'.$dto.'</td>
                        <td align="right">'.price($pu,"","","","",2).'</td>
                        <td align="right" style="margin-right: 5%;">'.price($pu*$line->qty,"","","","",2).'</td></tr>');
                $subtotal[$line->tva_tx] += $line->total_ht;;
                $subtotaltva[$line->tva_tx] += $line->total_tva;
                $tot_iva+=$line->subprice*$line->qty;
                if(!empty($line->total_localtax1)){
                    $localtax1 = $line->localtax1_tx;
                }
                if(!empty($line->total_localtax2)){
                    $localtax2 = $line->localtax2_tx;
                }
            }
            ?>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        </table>
        <div class="">
            <table class="totpay" style="margin-top:20px;">
                <tr>
                    <td colspan="2" style="font-size: 13px;">Subtotal</td>
                    <td style="font-size: 13px;" align="right">$<?php print price($tot_iva,"","","","",2)?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 13px;">Descuento</td>
                    <td style="font-size: 13px;" align="right">$<?php print price($object->getDiscount(),"","","","",2)?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 13px;">Subtotal con Descuento</td>
                    <td style="font-size: 13px;" align="right">$<?php print price($tot_iva-$object->getDiscount(),"","","","",2)?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 13px;">IVA 16%</td>
                    <td style="font-size: 13px;" align="right">$<?php print price(($tot_iva-$object->getDiscount())*.16,"","","","",2)?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 13px;">Importe Total</td>
                    <td style="font-size: 13px;" align="right">$<?php  print price($object->total_ttc,"","","","",2)?></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
                <?php //echo .' '.$langs->trans(currency_name($conf->currency));?>
        </div>
        <table style="float: left;">
            <tr>
                <td style="width: 33.33%;">ESTA COTIZACIÓN TIENE UNA VIGENCIA DE 15 DÍAS A PARTIR DE ESTA FECHA</td>
            </tr>
        </table>
        <script type="text/javascript">

            window.print();
            <?php if($conf->global->POS_CLOSE_WIN){?>
            window.close();
            <?php }?>

        </script>
</div>
</body>
</html>