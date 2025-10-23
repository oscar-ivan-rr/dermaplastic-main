<?php
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
global $langs,$db,$mysoc;
$langs->loadLangs(array("trips", "bills", "mails"));

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
    width: 100%;
    /*border-bottom: 1px solid #000;*/
    text-align: center;
		font-size: 13px;
	}

		.liste_articles tr.titres th {
    /*border-bottom: 1px solid #000;*/
    font-size: 13px;
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
		width: 100%;
		float: right;
		text-align: right;
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
        $object = new ExpenseReport($db);
        $object->fetch($id);
        print '<tr><td>&nbsp;</td></tr>';
        print '<tr>';
        print '<td>';
        print '<b>COMPROBANTE DE GASTO</b>';
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
        print 'FECHA Y HORA:'.dol_print_date($object->date_valid,'dayhour').'<br>';
        print '</td></tr>';
        print '<tr><td colspan="2" align="left">';
        print 'PERIODO (DE / A):'.dol_print_date($object->date_debut,'day').' - '.dol_print_date($object->date_fin,'day').'<br>';
        print '</td></tr>';
        print '<tr><td>&nbsp;</td></tr>';
        //Empleado
        $userstatic=new User($db);
        $userstatic->fetch($object->fk_user_author);
        print '<tr><td colspan="2" align="left">NOMBRE DEL EMPLEADO:</td></tr>';
        print '<tr><td colspan="2" align="left">'.$userstatic->firstname.' '.$userstatic->lastname;
        print '</td></tr>';
        //Tipod de Gasto
        $sql = "SELECT c.label FROM ".MAIN_DB_PREFIX."c_type_fees as c";
        $sql .= " WHERE c.active = 1 and c.id=".$object->fk_c_type_fees;
        $res = $db->query($sql);
        $fees = $db->fetch_object($res);
        print '<tr><td colspan="2" align="left">TIPO DE GASTO:</td></tr>';
        print '<tr><td colspan="2" align="left">'.$langs->trans($fees->label);
        print '</td></tr>';

        print '<tr><td colspan="2">&nbsp;</td></tr>';
        ?>
    <table class="liste_articles">
        <tr class="titres"><th align="left">DESCRIPCIÓN</th><th></th><th align="left">IMPORTE</th></tr>
        <?php
            $proyect = new Project($db);
            foreach ($object->lines as $line){
                $proyect->fetch($line->fk_project);
                print '<tr><td align="left">'.$line->comments.'</td><td></td><td align="left">'.price($line->total_ttc,"","","","",2).'</td></tr>';
            }
        ?>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    </table>
        <div class="">
            <table class="totpay" style="margin-top:20px;">
                <tr>
                    <td>TOTAL: $<?php print price($object->total_ttc,"","","","",2)?></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>


                <?php //echo .' '.$langs->trans(currency_name($conf->currency));?>
        </div>
        <table style="float:left;">
        <?php
        //Empleado
        $userstatic->fetch($object->fk_user_approve);
        //Usuario que aprobo
        print '<tr><td colspan="2" align="left">AUTORIZADO POR:'.$userstatic->firstname.' '.$userstatic->lastname;
        print " (".dol_print_date($object->date_approve,'dayhour').")";
        print '</td></tr>';
        //Proyecto asignado al gasto
        print '<tr><td colspan="2" align="left">Proyecto:'.$proyect->ref;
        print '</td></tr>';
        //Plazo de Comprobacion
        print '<tr><td colspan="2" align="left">PLAZO DE COMPROBACIÓN: 7 DÍAS</td></tr>';
        print '<tr><td colspan="2">&nbsp;</td></tr>';
        ?>
        </table>
        <table style="float: left;">
            <tr>
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%;">&nbsp;</td>
            </tr>
            <tr>
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%;" align="center"><b>FIRMA</b></td>
                <td style="width: 33.33%;">&nbsp;</td>
            </tr>
            <tr>
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%; max-height: 50%;" align="center"><img style="width: 50%;" src="../../documents/expensereport/<?php print $object->ref?>/signature.png"></td>
                <td style="width: 33.33%;">&nbsp;</td>
            </tr>
            <tr>
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%;position: relative;top: -40px;" align="center">________________________</td>
                <td style="width: 33.33%;">&nbsp;</td>
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