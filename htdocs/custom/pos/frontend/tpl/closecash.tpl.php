<?php




$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");

$form = new Form($db);

global $langs, $db, $mysoc;

$langs->load("main");
$langs->load("pos@pos");
header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
$mensaje = '';
//$terminal=GETPOST('terminal');
$mensaje .= '<html>
<head>
<title>Print ticket</title>

<style type="text/css">

	body {
		font-size: 14px;
		position: relative;
		font-family: monospace,courier,arial,helvetica,system;
	}

	.entete {
/* 		position: relative; */
	}

		.adresse {
/* 			float: left; */
			font-size: 12px;
		}

		.date_heure {
			position: absolute;
			top: 0;
			right: 0;
			font-size: 15px;
		}

		.infos {
			position: relative;
			font-size: 14px;
		}


	.liste_articles {
		width: 100%;
		border-bottom: 1px solid #000;
		text-align: center;
		font-size: 14px;
	}

		.liste_articles tr.titres th {
			border-bottom: 1px solid #000;
			font-size: 14px;
		}

		.liste_articles td.total {
			text-align: right;
			font-size: 14px;
		}

	.totaux {
		margin-top: 11px;
		width: 30%;
		float: right;
		text-align: right;
		font-size: 14px;
	}

	.lien {
		position: absolute;
		top: 0;
		left: 0;
		display: none;
		font-size: 14px;
	}

	@media print {

		.lien {
			display: none;
		}

	}

</style>

</head>

<body>';
?>
<?php

		// Cash

		$sql = "select ref, fk_user, date_c, fk_cash";
    	$sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
    	$sql .=" where rowid = ".$id;
    	$result=$db->query($sql);

		if ($result)
		{
			$objp = $db->fetch_object($result);
        	$date_end = $objp->date_c;
        	$fk_user = $objp->fk_user;
        	$ref = $objp->ref;
        	$terminal = $objp->fk_cash;
        }

		$sql = "select date_c";
    	$sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
    	$sql .=" where fk_cash = ".$terminal." AND date_c < '".$date_end."'";
    	$sql .=" ORDER BY date_c DESC";
    	$sql .=" LIMIT 1";
    	$result=$db->query($sql);

		if ($result)
		{
			$objd = $db->fetch_object($result);
        	$date_start = $objd->date_c;
        }
        $total_ventas=0;
        $total_ventas_tva=0;
        $total_ventas_ht=0;

		$mensaje .= '<div class="entete">
	<div class="logo">
        <img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('receipts/imgtop.png').'">
	</div>';
		$mensaje .= '<div class="infos">
		<p class="adresse">'.$mysoc->name.'<br>
		'.$mysoc->idprof1.'<br>
		'.$mysoc->address.'<br>
		'.$mysoc->zip.' '.$mysoc->town.'</p>';
	?>





		<?php
			$mensaje.= '<p>'.$langs->trans("CloseCashReport").': '.$ref.'<br>';
			$cash = new Cash($db);
			$cash->fetch($terminal);
			$mensaje.= $langs->trans("Terminal").': '.$cash->name.'<br>';

			$userstatic=new User($db);
			$userstatic->fetch($fk_user);
			$mensaje.= $langs->trans("User").': '.$userstatic->firstname.' '.$userstatic->lastname.'</p>';
			$mensaje.= '<p class="date_heure">'.dol_print_date($db->jdate($date_end),'dayhour').'</p>';
			$mensaje.='</div>
</div>';
		?>

<?php

	$form->load_cache_types_paiements();

$array_mode_pays = array(
    $cash->fk_modepaycash => $form->cache_types_paiements[$cash->fk_modepaycash]['label']
, $cash->fk_modepaybank => $form->cache_types_paiements[$cash->fk_modepaybank]['label']
, $cash->fk_modepaybank_extra => $form->cache_types_paiements[$cash->fk_modepaybank_extra]['label']
,$cash->fk_modepaybank_extra_2 => $form->cache_types_paiements[$cash->fk_modepaybank_extra_2]['label']
);
$array_account_pays = array(
    $cash->fk_modepaycash => $cash->fk_paycash,
    $cash->fk_modepaybank => $cash->fk_paybank,
    $cash->fk_modepaybank_extra => $cash->fk_paybank_extra,
    $cash->fk_modepaybank_extra_2 => $cash->fk_paybank_extra_2
);
foreach ($array_mode_pays as $key => $value) {// for all paiement types
    $mensaje.='<p>';
    if($value == "Efectivo") {
            $mensaje.= "<b style='font-size: 14pt;'>".$value."</b>";
        }else{
            $mensaje.= $value;
        }
    $mensaje .= '</p>';
    $mensaje .= '<table class="liste_articles">
        <tr class="titres"><th>Ref. Ticket</th><th>Ref. Fac. Asociada</th><th align="right">'.$langs->trans("Total").'</th></tr>';
    ?>

	<?php

        // Cash
        $sql = "(SELECT t.ticketnumber, f.ref,";
        $sql.="IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount,p.amount) as amount";
        $sql.=",IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount*0.16,p.amount*0.16) as amount_tva";
        $sql.=",IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,(pt.amount-(pt.amount*0.16)),(p.amount-(p.amount*0.16))) as amount_ht";
        $sql.=", t.type";
        $sql .=" FROM (".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p,".MAIN_DB_PREFIX."bank as b,".MAIN_DB_PREFIX."user as u)";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = t.fk_facture ";
        $sql .=" WHERE p.fk_paiement=".$key." AND t.fk_statut > 0 AND DATE(p.datep) = DATE('".$date_end."')";
        //$sql .=" WHERE t.fk_cash=".$terminal." AND p.fk_paiement=".$key." AND t.fk_statut > 0 AND DATE(p.datep) = DATE('".$date_end."')";
        //$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket AND t.paye=1 AND t.fk_cash in (SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash WHERE fk_warehouse = ".$cash->fk_warehouse.")";
        //$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket AND t.fk_cash in (SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash WHERE fk_warehouse = ".$cash->fk_warehouse."))";
        $sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket AND b.rowid = p.fk_bank AND b.fk_account=".$array_account_pays[$key]." AND u.rowid = p.fk_user_creat AND u.fk_warehouse = ".$cash->fk_warehouse.")";

        $sql.= 'UNION';

        $sql.="(SELECT '&nbsp;' as ticketnumber,f.ref,IF(p.amount=0,pf.amount,p.amount),IF(p.amount=0,pf.amount*0.16,p.amount*0.16) as amount_tva,IF(p.amount=0,(pf.amount-(pf.amount*0.16)),(p.amount-(p.amount*0.16))) as amount_ht";
        $sql.=",f.type as type";
        $sql.= " FROM (".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p)";
        $sql.= " WHERE p.fk_paiement=".$key." AND f.fk_statut > 0 AND DATE(p.datep) = DATE('".$date_end."')";
        $sql.= " AND p.rowid=pf.fk_paiement AND f.rowid=pf.fk_facture AND f.type = 0 AND";
        if($terminal == 1) {
            $sql .= " (SELECT u.fk_warehouse FROM llx_user as u WHERE u.rowid=p.fk_user_creat) = 1";
        }else {
            $sql .= " (SELECT u.fk_warehouse FROM llx_user as u WHERE u.rowid=p.fk_user_creat) = 2";
        }
        $sql.= " AND f.rowid not in (SELECT tt.fk_facture FROM llx_pos_ticket as tt WHERE tt.fk_facture is not null))";

        //Cuentas que no son con la caja que debia
        $sql.= 'UNION (SELECT t.ticketnumber,f.ref,';
        $sql.= ' IF((SELECT COUNT(at.rowid) FROM llx_pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount,p.amount) as amount,';
        $sql.= ' IF((SELECT COUNT(at.rowid) FROM llx_pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount*0.16,p.amount*0.16) as amount_tva,';
        $sql.= ' IF((SELECT COUNT(at.rowid) FROM llx_pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,';
        $sql.= ' (pt.amount-(pt.amount*0.16)),(p.amount-(p.amount*0.16))) as amount_ht, t.type';
        $sql.= ' FROM llx_pos_ticket as t';
        $sql.= ' LEFT JOIN llx_pos_paiement_ticket as pt ON pt.fk_ticket = t.rowid';
        $sql.= ' LEFT JOIN llx_paiement as p ON p.rowid = pt.fk_paiement';
        $sql.= ' LEFT JOIN llx_bank as b ON b.rowid = p.fk_bank';
        $sql.= ' LEFT JOIN llx_user as u ON u.rowid = p.fk_user_creat';
        $sql.= ' LEFT JOIN llx_facture as f ON f.rowid = t.fk_facture';
        $sql.= ' WHERE';
        $sql.= ' DATE(p.datep) = DATE("'.$date_end.'") AND p.fk_paiement='.$key.' AND t.fk_statut > 0';
        $sql.= ' AND b.fk_account<>'.$array_account_pays[$key];
        $sql.= ' AND u.fk_warehouse = '.$cash->fk_warehouse.')';
    	$subtotalcash=0;
    	$subtotalcash_tva=0;
    	$subtotalcash_ht=0;
    	$subtotaldevolucionescash=0;
    	$result=$db->query($sql);

		if ($result)
		{
			$total_rows = $db->num_rows($result);
      
			if($total_rows>0)
			{
        $curr_row = 0;
        $subtotalcash=0;
        $subtotalcash_tva=0;
        $subtotalcash_ht=0;
        $tickets = [];
        

        while ($curr_row < $total_rows) {
          $objp = $db->fetch_object($result);
          $hasRef = ($objp->ref != NULL) ? true : false ;
          if (array_key_exists($objp->ticketnumber,$tickets)){
            // append ';' if has ref
            if ($hasRef) {
              $tickets[$objp->ticketnumber]["ref"].= ';';
            }
            // append values
            $tickets[$objp->ticketnumber]["ref"].= $objp->ref;
            $tickets[$objp->ticketnumber]["amount"] +=$objp->amount;
          } else {
            // create array
            $tickets[$objp->ticketnumber] = array(
              "ref" => $objp->ref,
              "amount" => $objp->amount,
            );
          }

          $curr_row++;
          if($objp->amount > 0 || $objp->type == 0) {
            $subtotalcash += $objp->amount;
            $subtotalcash_tva += $objp->amount_tva;
            $subtotalcash_ht += $objp->amount_ht;
          }
          else {
            $subtotaldevolucionescash+=abs($objp->amount);
          }
        }
        // foreach ticket print values
        foreach ($tickets as $ticket => $valor) {
          $mensaje.= ('<tr><td align="center">'.$ticket.
            '</td><td align="center">'.$valor["ref"].
            '</td><td align="right">'.price($valor["amount"]).
            '</td></tr>');
        }
      }
			else {
				$mensaje.= ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
			}
		}
		$mensaje .= '</table>

    <table class="totaux">';
	?>
        <?php
        if($value == 'Efectivo')
            $vent=$subtotalcash;
        $total_cash = $subtotalcash;
        $total_ventas += $subtotalcash;
        $total_ventas_tva +=$subtotalcash_tva;
        $total_ventas_ht +=$subtotalcash_ht;
        if($value == 'Efectivo') {
            $mensaje.= '<tr><th nowrap="nowrap"><b style="font-size: 12pt;">Total Ventas de ' . strtolower($value) . '</b></th><td nowrap="nowrap">' . price($subtotalcash) . " " . $langs->trans(currency_name($conf->currency)) . "</td></tr>";
            $mensaje.= '<tr><th nowrap="nowrap"><b style="font-size: 12pt;">Total Devoluciones de ' . strtolower($value) . '</b></th><td nowrap="nowrap">' . price($subtotaldevolucionescash) . " " . $langs->trans(currency_name($conf->currency)) . "</td></tr>";
        }else{
            $mensaje.= '<tr><th nowrap="nowrap">Total Ventas de ' . strtolower($value) . '</th><td nowrap="nowrap">' . price($subtotalcash) . " " . $langs->trans(currency_name($conf->currency)) . "</td></tr>";
            $mensaje.= '<tr><th nowrap="nowrap">Total Devoluciones de ' . strtolower($value) . '</th><td nowrap="nowrap">' . price($subtotaldevolucionescash) . " " . $langs->trans(currency_name($conf->currency)) . "</td></tr>";
        }
    $mensaje.='</table><br><br>';
        ?>



    <?php
}
$mensaje.='</table>
<br><br>';
?>

<?php if(!empty($conf->rewards->enabled)){
    $mensaje.='<p>'.$langs->trans("Points").'</p>
<table class="liste_articles">
	<tr class="titres"><th>'.$langs->trans("Ticket").'</th><th>'.$langs->trans("Total").'</th></tr>';
    ?>


	<?php
		// Points
		$sql = " SELECT f.facnumber, p.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement= 100 AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture AND t.paye=1 AND t.fk_cash in (SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash WHERE fk_warehouse = ".$cash->fk_warehouse.")";

    	$result=$db->query($sql);

		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
        $i = 0;
        $subtotalpoint=0;
        while ($i < $num)
        {
          $objp = $db->fetch_object($result);

          $mensaje.= ('<tr><td align="left">'.$objp->facnumber.'</td><td align="right">'.price($objp->amount).'</td></tr>');
          $i++;
          $subtotalpoint+=$objp->amount;
        }
			}
			else
			{
				$mensaje.= ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
			}
		}
    $mensaje.='</table>';
	?>

<?php }?>

	<?php
    $mensaje.='<table class="totaux">';
	if(!empty($conf->rewards->enabled)){
		$mensaje.= '<tr><th nowrap="nowrap">'.$langs->trans("TotalPoints").'</th><td nowrap="nowrap">'.price($subtotalpoint)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	}
    $mensaje.= '<tr></td><td></td><td></tr>';
    $mensaje.= '<tr></td><td></td><td></tr>';
    $mensaje.= '<tr></td><td></td><td></tr>';

	$sql = "SELECT t.ticketnumber, t.type, l.total_ht, l.tva_tx, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t left join ".MAIN_DB_PREFIX."pos_ticketdet as l on l.fk_ticket= t.rowid";
	$sql .=" WHERE t.fk_control = ".$id." AND t.fk_cash=".$terminal." AND t.fk_statut > 0";

	$result=$db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);
		if($num>0)
		{
			$i = 0;
			$subtotalcardht=0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$i++;
				if($objp->type == 1){
					$objp->total_ht= $objp->total_ht * -1;
					$objp->total_tva= $objp->total_tva * -1;
					$objp->total_ttc= $objp->total_ttc * -1;
					$objp->total_localtax1= $objp->total_localtax1 * -1;
					$objp->total_localtax2= $objp->total_localtax2 * -1;
				}

				$subtotalcardht+=$objp->total_ht;
				$subtotalcardtva[$objp->tva_tx] += $objp->total_tva;
				$subtotalcardttc += $objp->total_ttc;
				$subtotalcardlt1 += $objp->total_localtax1;
				$subtotalcardlt2 += $objp->total_localtax2;
			}
		}

	}

	//if(! empty($subtotalcardht))$mensaje.= '<tr><th nowrap="nowrap" style="border-top: 1px solid #000000;">'.$langs->trans("TotalHT").'</th><td nowrap="nowrap" style="border-top: 1px solid #000000;">'.price($subtotalcardht)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	if(! empty($total_ventas_ht))$mensaje.= '<tr><th nowrap="nowrap" style="border-top: 1px solid #000000;">'.$langs->trans("TotalHT").'</th><td nowrap="nowrap" style="border-top: 1px solid #000000;">'.price($total_ventas_ht)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	/*if(! empty($subtotalcardtva)){
		foreach($subtotalcardtva as $tvakey => $tvaval){
			if($tvakey > 0)
                $mensaje.= '<tr><th nowrap="nowrap">'.$langs->trans("TotalVAT").' '.round($tvakey).'%'.'</th><td nowrap="nowrap">'.price($tvaval)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
		}
	}*/

    if(! empty($subtotalcardtva)) $mensaje.= '<tr><th nowrap="nowrap">'.$langs->trans("TotalVAT").' '.round(16).'%'.'</th><td nowrap="nowrap">'.price($total_ventas_tva)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	if($subtotalcardlt1)
        $mensaje.= '<tr><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT1",$mysoc->country_code).'</th><td nowrap="nowrap">'.price($subtotalcardlt1)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	if($subtotalcardlt2)
        $mensaje.= '<tr><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT2",$mysoc->country_code).'</th><td nowrap="nowrap">'.price($subtotalcardlt2)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";

	//echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalPOS").'</th><td nowrap="nowrap">'.price($subtotalcardttc)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
    $mensaje.= '<tr><th nowrap="nowrap">'.$langs->trans("TotalPOS").' CONTADO</th><td nowrap="nowrap">'.price($total_ventas)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
    $mensaje.= '</table>';
    $mensaje.= '<br><br>
    <p>Ventas a crédito</p>
    <table class="liste_articles">
        <tr class="titres"><th>'.$langs->trans("Ref").'</th><th>Ref. Fac. Asociada</th><th>'.$langs->trans("Total").'</th></tr>';
	?>
    <!--Ventas a Crédito-->

        <?php
        $sql="SELECT t.ticketnumber,SUM(p.amount) as total,t.total_ttc-SUM(IF(p.amount is null,0,p.amount)) as restapagar,f.ref ";
        $sql.=" from ".MAIN_DB_PREFIX."pos_ticket as t";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."facture as f on f.rowid=t.fk_facture";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."pos_paiement_ticket as pt ON pt.fk_ticket=t.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.rowid = pt.fk_paiement";
        $sql.=" WHERE";
        //$sql.=" paye = 0 AND date_ticket = DATE('".$date_end."') AND fk_cash in (SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash WHERE fk_warehouse = ".$cash->fk_warehouse.") AND t.fk_statut not in (0,3)";
        $sql.=" t.paye = 0 AND t.date_ticket = DATE('".$date_end."')  AND t.fk_statut>0 AND fk_cash in (SELECT rowid FROM ".MAIN_DB_PREFIX."pos_cash WHERE fk_warehouse = ".$cash->fk_warehouse.") AND t.type = 0";
        $sql.=" GROUP BY t.ticketnumber";
        $resq = $db->query($sql);
        $numRows = $db->num_rows($resq);
        $total_procesados=0;
        $k=0;
        if($numRows>0) {
            while ($k < $numRows) {
                $procesados = $db->fetch_object($resq);
                if($procesados->restapagar > 0) {
                    $mensaje .= '<tr class="titres"><td>' . $procesados->ticketnumber . '</td><td>' . $procesados->ref . '</td><td>' . price($procesados->restapagar, 0, '', 0, 4) . '</td></tr>';
                    $total_procesados += $procesados->restapagar;
                }
                $k++;
            }
        }
        else
        {
            $mensaje.= ('<tr><td align="left">'.$langs->Trans("NoTickets").'</td></tr>');
        }
        $mensaje.='</table>
    <table class="totaux">';
        ?>
    <?php
    $mensaje.= '<tr><th nowrap="nowrap">Total Ventas a crédito</th><td nowrap="nowrap">'.price($total_procesados)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
    $mensaje.= '</table>';
    $mensaje.='<br><br>
    <!--Pagos varios-->
    <p>Pagos Varios</p>
    <table class="liste_articles">
        <tr class="titres"><th>'.$langs->trans("Ref");'.</th><th>'.$langs->trans("Label").'</th><th>'.$langs->trans("Total").'</th></tr>';
        ?>

        <?php
            $sql="SELECT pv.rowid,pv.label,pv.amount from ".MAIN_DB_PREFIX."payment_various as pv";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON pv.fk_bank = b.rowid";
            //$sql.=" or fk_bank=".$cash->fk_paycash." or fk_bank=".$cash->fk_paybank_extra;
            $sql.= " WHERE b.fk_account=".$cash->fk_paycash;
            //$sql.="fk_bank =".$cash->fk_paycash;
            $sql.=" AND pv.datep = DATE('".$date_end."')";
            $resq = $db->query($sql);
            $numRows = $db->num_rows($resq);
            $total_variousp=0;
            $k=0;
            if($numRows>0)
            {
                while($k < $numRows) {
                    $various_p = $db->fetch_object($resq);
                    $mensaje.= '<tr class="titres"><td>' . $various_p->rowid . '</td><td>' . $various_p->label . '</td><td>' . price($various_p->amount) . '</td></tr>';
                    $total_variousp+=$various_p->amount;
                    $k++;
                }
            }
            $mensaje.='</table>
        <table class="totaux">';
            ?>
        <?php
            $mensaje.= '<tr><th nowrap="nowrap">Total Pagos varios</th><td nowrap="nowrap">'.price($total_variousp)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
            $mensaje.= '</table>';
            $mensaje.='<br><br>
            <!--GASTOS-->
            <p>Gastos</p>
            <table class="liste_articles">
                <tr class="titres"><th>'.$langs->trans("Ref").'</th><th>'.$langs->trans("Designation").'</th><th>'.$langs->trans("Total").'</th></tr>';
            ?>

                <?php
                //PONER PRIMERO EFECTIVO
                $sql="SELECT ee.rowid,ee.ref,ee.total_ttc";
                $sql.=" FROM ".MAIN_DB_PREFIX."expensereport as ee";
                $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bank_account ba ON ba.rowid=ee.accountid";
                $sql.=" WHERE ee.fk_statut=2 AND DATE(ee.date_valid) = DATE('".$date_end."')";
                if($terminal == 1){
                    $sql.= " AND ba.label not like '%GUADALUPE%'";
                }else{
                    $sql.= " AND ba.label like '%GUADALUPE%'";
                }
                $sql.=" AND ba.rowid = ".$cash->fk_paycash;
                $resq = $db->query($sql);
                $numRows = $db->num_rows($resq);
                $total_gastos=0;
                $k=0;
                if($numRows>0)
                {
                    while($k < $numRows) {
                        $gastos = $db->fetch_object($resq);
                        $description_g = "SELECT comments FROM ".MAIN_DB_PREFIX."expensereport_det WHERE fk_expensereport=".$gastos->rowid;
                        $description_res = $db->query($description_g);
                        $iterator_desc = 0;
                        $numRows_g = $db->num_rows($description_res);
                        $text_description='';
                        if($numRows_g>0)
                        {
                            while($iterator_desc < $numRows_g) {
                                $descriptions = $db->fetch_object($description_res);
                                $text_description.=$descriptions->comments;
                                if($iterator_desc != $numRows_g-1)
                                    $text_description.=',';
                                $iterator_desc++;
                            }
                        }
                        $mensaje.= '<tr class="titres"><td>' . $gastos->ref . '</td><td>' . $text_description . '</td><td>' . price($gastos->total_ttc) . '</td></tr>';
                        $total_gastos+=$gastos->total_ttc;
                        $k++;
                    }
                    //Espaciado
                    $mensaje.= '<tr><td style="border-bottom: 1px solid black;" colspan="3">&nbsp;</td></tr>';
                }
                //Lo que no es efectivo
                $sql="SELECT ee.rowid,ee.ref,ee.total_ttc";
                $sql.=" FROM ".MAIN_DB_PREFIX."expensereport as ee";
                $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."bank_account ba ON ba.rowid=ee.accountid";
                $sql.=" WHERE ee.fk_statut=2 AND DATE(ee.date_valid) = DATE('".$date_end."')";
                if($terminal == 1){
                    $sql.= " AND ba.label not like '%GUADALUPE%'";
                }else{
                    $sql.= " AND ba.label like '%GUADALUPE%'";
                }
                $sql.=" AND ba.rowid <> ".$cash->fk_paycash;
                $resq = $db->query($sql);
                $numRows = $db->num_rows($resq);
                $k=0;
                if($numRows>0)
                {
                    while($k < $numRows) {
                        $gastos = $db->fetch_object($resq);
                        $description_g = "SELECT comments FROM ".MAIN_DB_PREFIX."expensereport_det WHERE fk_expensereport=".$gastos->rowid;
                        $description_res = $db->query($description_g);
                        $iterator_desc = 0;
                        $numRows_g = $db->num_rows($description_res);
                        $text_description='';
                        if($numRows_g>0)
                        {
                            while($iterator_desc < $numRows_g) {
                                $descriptions = $db->fetch_object($description_res);
                                $text_description.=$descriptions->comments;
                                if($iterator_desc != $numRows_g-1)
                                    $text_description.=',';
                                $iterator_desc++;
                            }
                        }
                        $mensaje.= '<tr class="titres"><td>' . $gastos->ref . '</td><td>' . $text_description . '</td><td>' . price($gastos->total_ttc) . '</td></tr>';
                        $total_gastos+=$gastos->total_ttc;
                        $k++;
                    }
                }
                $mensaje.=' </table>
            <table class="totaux">';
                ?>
           <?php
                $mensaje.= '<tr><th nowrap="nowrap">Total de Gastos</th><td nowrap="nowrap">'.price($total_gastos)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
                $mensaje.='</table>
            <br><br>';
                ?>

            <?php
            $sql = "SELECT cash_1000,cash_500,cash_200,cash_100,cash_50,cash_20,cash_10,cash_5,";
            $sql.= "cash_2,cash_1,cash_05,amount_real,quantity_delivery from ".MAIN_DB_PREFIX."pos_control_cash where rowid=".$id;
            $resql=$db->query($sql);
            $ca=$db->fetch_object($sql);
            $mensaje.='<p>Suma entregada</p>
            <table>
                <tr>
                    <th>Denominación</th>
                    <th>Entregado</th>
                    <th>Denominación</th>
                    <th>Entregado</th>
                </tr>
                <tr>
                    <td>$1000</td>
                    <td>'.$ca->cash_1000.'</td>
                    <td>$500</td>
                    <td>'.$ca->cash_500.'</td>
                </tr>
                <tr>
                    <td>$200</td>
                    <td>'.$ca->cash_200.'</td>
                    <td>$100</td>
                    <td>'.$ca->cash_100.'</td>
                </tr>
                <tr>
                    <td>$50</td>
                    <td>'.$ca->cash_50.'</td>
                    <td>$20</td>
                    <td>'.$ca->cash_20.'</td>
                </tr>
                <tr>
                    <td>$10</td>
                    <td>'.$ca->cash_10.'</td>
                    <td>$5</td>
                    <td>'.$ca->cash_5.'</td>
                </tr>
                <tr>
                    <td>$2</td>
                    <td>'.$ca->cash_2.'</td>
                    <td>$1</td>
                    <td>'.$ca->cash_1.'</td>
                </tr>
                <tr>
                    <td>$0.5</td>
                    <td>'.$ca->cash_05.'</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </table>';
            ?>

            <?php
            //$sql="SELECT SUM(amount) as saldo from llx_bank where fk_account=".$cash->fk_paycash." AND dateo < DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
            /*$sql = "SELECT";
            $sql.= " (SELECT SUM(if(w.amount is null,0,w.amount)) FROM ".MAIN_DB_PREFIX."bank as w WHERE w.rowid<=b.rowid and w.fk_account = ".$cash->fk_paycash.") as saldo";
            $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
            $sql.= " WHERE b.fk_account=".$cash->fk_paycash." AND b.dateo <= DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
            $sql.= " ORDER BY  b.datev DESC, b.dateo DESC, b.rowid LIMIT 1";*/

            $sql = "SELECT";
            $sql.= " (SELECT SUM(IF(w.dateo < b.dateo and w.rowid > b.rowid,if(w.amount is null,0,w.amount),IF(w.rowid <= b.rowid, w.amount, 0)))";
            $sql.= " FROM ".MAIN_DB_PREFIX."bank as w WHERE w.fk_account=".$cash->fk_paycash.")-b.amount as saldo";
            $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
            $sql.= " WHERE b.fk_account=".$cash->fk_paycash." AND b.dateo = DATE('".$date_end."')";
            $sql.= " LIMIT 1";


            $resql=$db->query($sql);
            $saldo_inicio_caja=$db->fetch_object($resql)->saldo;
            $efectivo = $saldo_inicio_caja;
            //aqui va cuenta bancaria
            //$sql_bank="SELECT SUM(amount) as saldo from llx_bank where fk_account=".$cash->fk_paybank." AND dateo < DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
            $sql_bank = "SELECT";
            $sql_bank.= " (SELECT SUM(if(w.amount is null,0,w.amount)) FROM ".MAIN_DB_PREFIX."bank as w WHERE w.rowid<=b.rowid and w.fk_account = ".$cash->fk_paybank.") as saldo";
            $sql_bank.= " FROM ".MAIN_DB_PREFIX."bank as b";
            $sql_bank.= " WHERE b.fk_account=".$cash->fk_paybank." AND b.dateo <= DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
            $sql_bank.= " ORDER BY  b.datev DESC, b.dateo DESC, b.rowid LIMIT 1";
            $resql=$db->query($sql_bank);
            $saldo =$db->fetch_object($resql)->saldo;
            $saldo_inicio_caja+=$saldo;
            //aqui va cuenta bancaria extra
            if($cash->fk_paycash != $cash->fk_paybank_extra && $cash->fk_paybank != $cash->fk_paybank_extra) {
                //$sql_bank="SELECT SUM(amount) as saldo from llx_bank where fk_account=".$cash->fk_paybank_extra." AND dateo < DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
                $sql_bank = "SELECT";
                $sql_bank.= " (SELECT SUM(if(w.amount is null,0,w.amount)) FROM ".MAIN_DB_PREFIX."bank as w WHERE w.rowid<=b.rowid and w.fk_account = ".$cash->fk_paybank_extra.") as saldo";
                $sql_bank.= " FROM ".MAIN_DB_PREFIX."bank as b";
                $sql_bank.= " WHERE b.fk_account=".$cash->fk_paybank_extra." AND b.dateo <= DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
                $sql_bank.= " ORDER BY  b.datev DESC, b.dateo DESC, b.rowid LIMIT 1";
                $resql=$db->query($sql_bank);
                $saldo_inicio_caja+=$db->fetch_object($resql)->saldo;
            }
            //aqui va cuenta bancaria extra 2
            if($cash->fk_paycash != $cash->fk_paybank_extra_2 && $cash->fk_paybank_extra_2 != $cash->fk_paybank_extra && $cash->fk_paybank != $cash->fk_paybank_extra_2) {
                //$sql_bank="SELECT SUM(amount) as saldo from llx_bank where fk_account=".$cash->fk_paybank_extra_2." AND dateo < DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
                $sql_bank = "SELECT";
                $sql_bank.= " (SELECT SUM(if(w.amount is null,0,w.amount)) FROM ".MAIN_DB_PREFIX."bank as w WHERE w.rowid<=b.rowid and w.fk_account = ".$cash->fk_paybank_extra_2.") as saldo";
                $sql_bank.= " FROM ".MAIN_DB_PREFIX."bank as b";
                $sql_bank.= " WHERE b.fk_account=".$cash->fk_paybank_extra_2." AND b.dateo <= DATE(DATE_SUB('".$date_end."',INTERVAL 1 DAY))";
                $sql_bank.= " ORDER BY  b.datev DESC, b.dateo DESC, b.rowid LIMIT 1";
                $resql=$db->query($sql_bank);
                $saldo_inicio_caja+=$db->fetch_object($resql)->saldo;
            }
            //$saldo_cierre=$vent-($total_gastos+$total_variousp);
            $saldo_cierre = 0;
            $saldo_cierre += ($ca->cash_1000 * 1000);
            $saldo_cierre += ($ca->cash_500 * 500);
            $saldo_cierre += ($ca->cash_200 * 200);
            $saldo_cierre += ($ca->cash_100 * 100);
            $saldo_cierre += ($ca->cash_50 * 50);
            $saldo_cierre += ($ca->cash_20 * 20);
            $saldo_cierre += ($ca->cash_10 * 10);
            $saldo_cierre += ($ca->cash_5 * 5);
            $saldo_cierre += ($ca->cash_2 * 2);
            $saldo_cierre += ($ca->cash_1 * 1);
            $saldo_cierre += ($ca->cash_05 * 0.5);
            $sqlSaldo = "SELECT (SELECT SUM(IF(w.dateo < b.dateo and w.rowid > b.rowid,if(w.amount is null,0,w.amount),IF(w.rowid <= b.rowid, w.amount, 0))) ";
            $sqlSaldo .= " FROM ".MAIN_DB_PREFIX."bank as w WHERE w.fk_account=".$cash->fk_paycash.") as saldo,b.label FROM ".MAIN_DB_PREFIX."bank as b WHERE b.fk_account=".$cash->fk_paycash." AND b.dateo <= DATE('".$date_end."') order by b.rowid desc";
            $resqlSaldo = $db->query($sqlSaldo);
            $realsaldo = 0;
            if($resqlSaldo){
                $realsaldo = $db->fetch_object($resqlSaldo);
                if($realsaldo->label = 'Cantidad a enviar en Corte'){
                    $realsaldo = $db->fetch_object($resqlSaldo);
                }
                $realsaldo = $realsaldo->saldo;
            }
            $mensaje.='<table>
                <tr>
                    <td><b>Saldo inicial de la caja</b></td>
                    <td>$'.price($efectivo).'</td>
                </tr>
                <tr>';
                if($realsaldo != $saldo_cierre && abs($realsaldo - $saldo_cierre) != 5){
                $mensaje.= '<td><b>Saldo al cierre</b></td>
                    <td>$'.price($saldo_cierre).' <b style="color:red;">Saldo real:'.price($realsaldo).'</b></td>';
                }else{
                    $mensaje.= '<td><b>Saldo al cierre</b></td>
                    <td>$'.price($saldo_cierre).'</td>';
                }
            $mensaje.= '</tr>
                <tr>
                    <td><b>Total entregado:</b></td>
                    <td>$'.price($ca->quantity_delivery).'</td>
                </tr>
                <tr>
                    <td><b>Saldo para el siguiente día:</b></td>
                    <td>$'.price($saldo_cierre - $ca->quantity_delivery).'</td>
                </tr>
            </table>
            <br><br>';
            $mensaje.= "<p>Tickets Pagados con Desc. Absoluto</p>";
            $mensaje.='<table  class="liste_articles">
            <tr class="titres"><th>Ref. Ticket</th><th>Ref. Fac. Asociada</th><th>Monto</th><tr>';
            //Ventas con descuento absoluto
            $sql = "(SELECT t.ticketnumber, f.ref,";
            $sql.="IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount,p.amount) as amount";
            $sql.=",IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,pt.amount*0.16,p.amount*0.16) as amount_tva";
            $sql.=",IF((SELECT COUNT(at.rowid) FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as at WHERE at.fk_paiement=p.rowid)>1,(pt.amount-(pt.amount*0.16)),(p.amount-(p.amount*0.16))) as amount_ht";
            $sql.=", t.type";
            $sql .=" FROM (".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p,".MAIN_DB_PREFIX."bank as b,".MAIN_DB_PREFIX."user as u)";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = t.fk_facture ";
            $sql .=" WHERE p.fk_paiement=".$cash->fk_modepaybank_extra_3." AND t.fk_statut > 0 AND DATE(p.datep) = DATE('".$date_end."')";
            $sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket AND b.rowid = p.fk_bank AND b.fk_account=".$cash->fk_paybank_extra_3." AND u.rowid = p.fk_user_creat AND u.fk_warehouse = ".$cash->fk_warehouse.")";
            //die($sql);
            $result=$db->query($sql);

            if ($result)
            {
                $num = $db->num_rows($result);
                if($num>0)
                {
                    $i = 0;
                    
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $mensaje.= ('<tr><td align="center">'.$objp->ticketnumber.'</td><td align="center">'.$objp->ref.'</td><td align="right">'.price($objp->amount).'</td></tr>');
                        $i++;
                    }
                }
                else
                {
                    $mensaje.= ('<tr><td align="left" colspan="3">'.$langs->Trans("NoTickets").'</td></tr>');
                }
            }
            $mensaje.='</table>';
            $message = $mensaje;
            $mensaje.= ' <script type="text/javascript">
	window.print();';
	if($conf->global->POS_CLOSE_WIN){
	$mensaje.='window.close();';
	}
    $mensaje.='</script>
    <a class="lien" href="#" onclick="javascript: window.close(); return(false);">Fermer cette fenetre</a>
    </body>';

    // Send mail
    $sql = "SELECT DATE(date_c) as date_c FROM ".MAIN_DB_PREFIX."pos_control_cash WHERE rowid = ".$id;
    $resql = $db->query($sql);
    if($resql)
        $fecha = $db->fetch_object($resql)->date_c;
    if($fecha == dol_print_date(dol_now(),'%Y-%m-%d')) {
        $subject = $conf->global->MAIN_INFO_SOCIETE_NOM . ': ' . $langs->trans("CopyOfCloseCash") . ' ' . $id . ' De ' . $userstatic->firstname . ' ' . $userstatic->lastname;
        $sendto = "luis.marquez@cezac.com.mx";
        $from = $conf->global->MAIN_INFO_SOCIETE_NOM . "<" . $conf->global->MAIN_INFO_SOCIETE_MAIL . ">";

        require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
        $mailfile = new CMailFile($subject, $sendto, $from, $message, array(), array(), array(), "", "", 0, 1);
        if (!preg_match("/^(?:[\w\d]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,4}$/", $sendto)) {
            $mailfile->error = $langs->trans('ErrorFailedToSendMail', $from, $sendto);
        }

        if ($mailfile->error) {
            setEventMessage($mailfile->error, "errors");
        } else {
            $result = $mailfile->sendfile();
            if (!$result) {
                $langs->load("other");
                if ($mailfile->error) {
                    setEventMessage($langs->trans('ErrorFailedToSendMail', $from, $sendto) . '<br>' . $mailfile->error, "errors");
                } else {
                    setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', "errors");
                }

            }
        }
    }

    print $mensaje;
?>
