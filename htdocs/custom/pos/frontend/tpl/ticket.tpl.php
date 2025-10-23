<?php



$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/place.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
global $langs,$db,$mysoc;

$langs->load("main");
$langs->load("pos@pos");
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
        margin-left: 40%;
        width: 60%;
        /*float: right;*/
        text-align: left;
        font-size: 10px;
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
                <?php //print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_small).'">'; ?>
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
		
			// Variables
		
			$object=new Ticket($db);
			$result=$object->fetch($id,$ref);
			$entrepot = $object->getEntrepot();
			print '<tr><td>&nbsp;</td></tr>';
            print '<tr>';
            print '<td>';
            if(strpos(substr($object->ref,0,8),'-') === false)
            {
            	if (substr($object->ref,0,1)=='G')
            	{
            		$alm = 'Matriz Zac';
            	}
            	else
            	{
            		$alm = 'Almacén Gpe';
            	}
            	echo '<b>ORDEN DE ENTREGA DE MERCANCÍA ('.$alm.') </b>';
            }
            elseif($object->type == 0)
                print '<b>TICKET DE VENTA</b>';
            else if($object->type == 1)
                print '<b>DEVOLUCIÓN</b>';
            print '</td>';
            print '</tr>';
            print '<tr>';
            print '<td>';
            print '<b>'.$object->ref.'</b>';
            print '</td>';
            print '</tr>';
            print '</table>';
			//echo $entrepot->address."<br>";
			//echo $entrepot->zip.' '.$entrepot->town."<br>";
			//echo $entrepot->phone."<br>";
            print '<table style="width: 100%;">';
            print '<tr><td colspan="2" align="left">';
            /*if($object->type == 1)
                print 'FECHA Y HORA:'.dol_print_date($object->date_closed,'dayhour').'<br>';
            else if ($object->type == 0)*/
                print 'FECHA Y HORA:'.dol_print_date($object->date_creation,'dayhour').'<br>';
            print '</td></tr>';
			if($object->fk_project > 0){
				$sqlprojet = 'SELECT title from llx_projet where rowid='.$object->fk_project;
				$resqlprojet = $db->query($sqlprojet);
				if($resqlprojet){
					$proj = $db->fetch_object($resqlprojet);
					print '<tr><td align="left">PROYECTO: '.$proj->title;
					print '</td></tr>';
				}
			}
            print '<tr><td>&nbsp;</td></tr>';
			//cliente
            $soc = new Societe($db);
            $soc->fetch($object->socid);
            print '<tr><td align="left">';
            print 'CLIENTE: '.$soc->nom;
            if($user->rights->pos->receive_payments) {
                if ($object->statut == 1 && $object->type==0)
                    print '</td><td align="right">TIPO: CONTADO';
                else if ($object->statut == 2 && $object->type==0)
                    print '</td><td align="right">TIPO: CRÉDITO';
            }
            print '</td></tr>';
			//Vendedor
			$userstatic=new User($db);
			/*if($object->type == 1)
			    $userstatic->fetch($object->user_close);
			else if($object->type == 0)*/
			    $userstatic->fetch($object->user_author);
            print '<tr><td colspan="2" align="left">';
			print 'VENDEDOR: '.$userstatic->firstname.' '.$userstatic->lastname.'<br><br>';
            print '</td></tr>';
			/*if(!empty($object->fk_place))
			{
				$place = new Place($db);
				$place->fetch($object->fk_place);
				print $langs->trans("Place").': '.$place->name."</p>";
			}*/
			
			
		?>
</div>

<?php
if ($result){
	if (! empty($object->lines)){	
		$onediscount = false;			
		foreach ($object->lines as $line){
			if($line->remise_percent)
				$onediscount = true;
		}
	}
}
if($user->rights->pos->receive_payments){
?>

<table class="liste_articles">
	<!--<tr class="titres"><th><?php print $langs->trans("Label"); ?></th><th><?php print $langs->trans("Qty")."/".$langs->trans("Price"); ?></th><?php if($onediscount)print '<th>'.$langs->trans("DiscountLineal").'</th>'; ?><th><?php print $langs->trans("Total"); ?></th></tr>-->
	<tr class="titres"><th style="font-size: 11px;" align="left">CANT</th><th style="font-size: 11px;" align="left">COD</th><th style="font-size: 11px;" align="left">DESCRIPCIÓN</th><th style="font-size: 11px;" align="left">PRECIO</th><th style="font-size: 11px;" align="left">IMPORTE</th></tr>

	<?php
        //Total_sin_Descuento
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
	
			$product_fourn = new ProductFournisseur($db);
			$productstatic = new Product($db);

			// Number of subproducts
			$prodsfather = $product->getFather(); //Parent Products
			$product->get_sousproduits_arbo();			// Defined $product->sousprod
			$prods_arbo=$product->get_arbo_each_prod();
			$nbofsubproducts=count($prods_arbo);
			if ($nbofsubproducts > 0)
			{

			
			foreach($prods_arbo as $value)
				{
					$atleastonenotdefined=0;
					$notdefined=0;
					$productstatic->id=$value['id'];
					$productstatic->ref=$value['label'];
					$productstatic->type=$value['type'];
					$productstatic->nb=$value['nb'];

					$notdefined=0;
					$productstatic->ref=$value['fullpath'];
					//print '<td>'.$productstatic->getNomUrl(1,'composition').' ('.$value['nb'].')</td>';
					//print '<td align="right">';
					if ($product_fourn->find_min_price_product_fournisseur($productstatic->id) > 0)
						{
							//print $langs->trans("BuyingPriceMinShort").': ';
				    		if ($product_fourn->product_fourn_price_id > 0) $product_fourn->display_price_product_fournisseur(0,0);
				   			 else { 
				    			//print $langs->trans("NotDefined"); 
				    			$notdefined++; 
				    			$atleastonenotdefined++; 
				    		}
						}

					$totalline=price2num($value['nb'] * $product_fourn->fourn_unitprice, 'MT');
					$total+=$totalline;
					//echo ('<tr><td align="left">'.$productstatic->ref.'</td><td align="left">'.$productstatic->nb." * ".($notdefined?'':price($totalline,'','',0,0,-1,$conf->currency)).'</td><td class="total">'.price($totalline*$productstatic->nb,"","","","",2).'</tr>');
					print '<tr><td align="left">'.$productstatic->nb.'</td><td align="left">'.$productstatic->nb." * ".($notdefined?'':price($totalline,'','',0,0,-1,$conf->currency)).'</td><td class="total">'.price($totalline*$productstatic->nb,"","","","",2).'</tr>';

				}
			}
			else {//$line->remise_percent descuento en %
					$totalline= $line->qty*$line->subprice;
					if($object->type == 0)
					    echo ('<tr><td align="left">'.$line->qty.'</td><td align="left">'.$product->ref.'</td><td align="left">'.$line->desc.'</td><td align="left">'.price($line->price,"","","","",2).'</td><td align="left">'.price($line->price*$line->qty,"","","","",2).'</td></tr>');
					else if($object->type == 1)
					    echo ('<tr><td align="left">'.abs($line->qty).'</td><td align="left">'.$product->ref.'</td><td align="left">'.$line->desc.'</td><td align="left">'.price(abs($line->price),"","","","",2).'</td><td align="left">'.price(abs($line->price*$line->qty),"","","","",2).'</td></tr>');
					$subtotal[$line->tva_tx] += $line->total_ht;;
					$subtotaltva[$line->tva_tx] += $line->total_tva;
					$tot_iva+=$line->price*$line->qty;
					if(!empty($line->total_localtax1)){
						$localtax1 = $line->localtax1_tx;
					}
					if(!empty($line->total_localtax2)){
						$localtax2 = $line->localtax2_tx;
					}
			}
		}
					
					/////
		// if ($result)
		// {
		// 	//$object->getLinesArray();
		// 	if (! empty($object->lines))
		// 	{
		// 		//$subtotal=0;
		// 		foreach ($object->lines as $line)
		// 		{
		// 			$totalline= $line->qty*$line->subprice;
		// 			echo ('<tr><td align="left">'.$line->libelle.'</td><td align="left">'.$line->qty." * ".price($line->subprice*(1+$line->tva_tx/100),"","","","",2).'</td>'.($onediscount?'<td align="right">'.$line->remise_percent.'%</td>':'').'<td class="total">'.price($line->total_ttc,"","","","",2).'</td></tr>');
		// 			$subtotal[$line->tva_tx] += $line->total_ht;;
		// 			$subtotaltva[$line->tva_tx] += $line->total_tva;
		// 			if(!empty($line->total_localtax1)){
		// 				$localtax1 = $line->localtax1_tx;
		// 			}
		// 			if(!empty($line->total_localtax2)){
		// 				$localtax2 = $line->localtax2_tx;
		// 			}
		// 		}
		// 	}
		// 	else 
		// 	{
		// 		echo ('<p>'.print $langs->trans("ErrNoArticles").'</p>'."\n");
		// 	}
					
		// }
		

	?>
</table>
<div class="">
    <table class="totpay" style="margin-top:20px;">
        <tr>
            <td>SUBTOTAL CON IVA</td>
            <td>$<?php print price($tot_iva,"","","","",2)?></td>
        </tr>
        <tr>
            <td>DESCUENTO</td>
            <td>$<?php print price($tot_iva-$object->total_ttc,"","","","",2)?></td>
        </tr>
        <tr>
            <td>TOTAL DEL TICKET</td>
            <td>$<?php print price($object->total_ttc,"","","","",2)?></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>


    <?php //echo .' '.$langs->trans(currency_name($conf->currency));?>
</div>
<!--<table class="totaux" style="display:none;">
	<?php
		/*if($object->remise_percent>0)
		{
			echo '<tr><th nowrap="nowrap">'.$langs->trans("Subtotal").'</th><td nowrap="nowrap">'.price($subtotal)."</td></tr>\n";
			echo '<tr><th nowrap="nowrap">'.$langs->trans("DiscountGlobal").'</th><td nowrap="nowrap">'.$object->remise_percent."%</td></tr>\n";
		}*/
	/*echo '<tr><th nowrap="nowrap" style="width:50%;">'.$langs->trans("TotalHT").'</th><th nowrap="nowrap" style="width:25%;">'.$langs->trans("VAT").'</th><th nowrap="nowrap" style="width:25%;">'.$langs->trans("TotalVAT").'</th></tr>';
	if(! empty($subtotal)){
		foreach($subtotal as $totkey => $totval){
			echo '<tr><td nowrap="nowrap" style="text-align:left;">'.price($subtotal[$totkey],"","","","",2).'</td><td nowrap="nowrap">'.price($totkey,"","","","",2).'%</td><td nowrap="nowrap">'.price($subtotaltva[$totkey],"","","","",2).'</td></tr>';
		}
	}
	
	
	echo '<tr><td nowrap="nowrap" style="border-top: 1px dashed #000000;text-align:left;">'.price($object->total_ht,"","","","",2).'</td><td style="border-top: 1px dashed #000000;">--</td><td nowrap="nowrap" style="border-top: 1px dashed #000000;">'.price($object->total_tva,"","","","",2)."</td></tr>";
		
		if($object->total_localtax1!=0){
			echo '<tr><td></td><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT1",$mysoc->country_code).' '.price($localtax1,"","","","",2).'%</th><td nowrap="nowrap">'.price($object->total_localtax1,"","","","",2)."</td></tr>";
		}
		if($object->total_localtax2!=0){
			echo '<tr><td></td><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT2",$mysoc->country_code).' '.price($localtax2,"","","","",2).'%</th><td nowrap="nowrap">'.price($object->total_localtax2,"","","","",2)."</td></tr>";
		}*/
		?>
				</table>-->
				
				<!--<table class="totpay">-->
				<?php 
		//echo '<tr><td></td></tr>';
		//echo '<tr><td></td></tr>';

		$terminal = new Cash($db);
		$terminal->fetch($object->fk_cash);
		
		//if ($object->type==0)
		{
			$pay = $object->getSommePaiement();
		
			if($object->customer_pay > $pay)
				$pay = $object->customer_pay;
			
		}
		$diff_payment = $object->total_ttc - $pay;
		$listofpayments=$object->getListOfPayments();
		$table_payments='';
		foreach($listofpayments as $paym)
		{
			if($paym['type'] != 'LIQ'){
				//echo '<tr><th nowrap="nowrap">'.$terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement')).'</th><td nowrap="nowrap">'.price($paym['amount'],"","","","",2)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
                $table_payments='<tr><td>MÉTODO DE PAGO: '.$terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement')).'</td><td>&nbsp;</td></tr>';
			}
			else{
				//echo '<tr><th nowrap="nowrap">'.$terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement')).'</th><td nowrap="nowrap">'.price($paym['amount']-($diff_payment<0?$diff_payment:0),"","","","",2)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
                $table_payments='<tr><td>MÉTODO DE PAGO: '.$terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement')).'</td><td>&nbsp;</td></tr>';
			}
		}
				
		//echo '<tr style="margin-left: 10px;"><th nowrap="nowrap">'.($diff_payment<0?$langs->trans("CustomerRet"):$langs->trans("CustomerDeb")).'</th><td nowrap="nowrap">'.price(abs($diff_payment),"","","","",2)." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
	?>
	<tr>
        <?php
            if($object->type == 0){
                ?>
            <td>TOTAL POR PAGAR</td>
            <td>$<?php print price(abs($diff_payment),"","","","",2)?></td>
        <?php }else if($object->type == 1){?>
            <td>TOTAL REEMBOLSO</td>
            <td>$<?php print price(abs($object->total_ttc),"","","","",2)?></td>
        <?php
            }
        ?>
        </tr>
</table>

<!--<div class="note"><p><?php print $conf->global->POS_PREDEF_MSG; ?> </p></div>
<div><?php // Recuperation et affichage de la date et de l'heure
			$now = dol_now();
			$label=$object->ref;
			$facture = new Facture($db);
			if($object->fk_facture){
				$facture->fetch($object->fk_facture);
				$label=$facture->ref;
			}
			
			//print '<p class="date_heure" align="right">'.$label." ".dol_print_date($object->date_closed,'dayhour').'</p>';?></div>
--><br>
<?php if($object->type == 0){?>
    <table style="float:left;">
        <?php print $table_payments;?>
        <tr>
            <td>No. DE PAGO
                <?php
                $sql ='SELECT p.ref FROM llx_pos_paiement_ticket AS pt';
                $sql.=' LEFT JOIN llx_paiement AS p ON p.rowid=pt.fk_paiement';
                $sql.=' WHERE fk_ticket='.$object->id;
                $sql.=' ORDER BY p.datec DESC LIMIT 1';
                $res = $db->query($sql);
                if($res)
                {
                    $pago=$db->fetch_object($res);
                    print $pago->ref;
                }
                ?>
            </td>
            <td>&nbsp;
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr><td style="font-size: 12px;">Para generar su autofactura, favor de ingresar al siguiente link o lea el código:</td></tr>
        <tr><td style="font-size: 12px;">http://cezac.solutionslion.com/htdocs/custom/autofactura_ticket/</td></tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    </table>
    <table style="float: left;">
        <tr>
            <?php $qr = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('receipts/qrreceipt.png')?>
            <td style="width: 15%;box-shadow: 10px 10px 76px -10px rgba(0,0,0,0.75);">
                <img src="<?=$qr?>" alt="" style="width: 100px;height: 100px;">
            </td>
            <!--<td style="width: 10%;">&nbsp;</td>-->
            <td style="width: 75%;font-size: 9px;">PAGARÉ:<br>
                POR ESTE PAGARE ME(NOS) COMPROMETO(EMOS) A <br>PAGAR
                INCONDICIONALMENTE A LA ORDEN DE DERMAGLOBAL. EN LA CIUDAD DE<br> AGUASCALIENTES,
                AGS. EL DIA: __________________ POR LA<br> CANTIDAD DE:
                _______________________________________________
                VALOR RECIBIDO A MI ENTERA SATISFACCION
				<!-- , DE NO SER PAGADO A SU
                VENCIMIENTO CAUSARA INTERESES A RAZON DEL 4% MENSUAL SIN
                QUE POR ELLO SE CONSIDERE PRORROGADO EL PLAZO -->
				. ESTE PAGARE
                ES MERCANTIL Y ESTA REGIDO POR LA LEY DE TITULOS DE CREDITO EN
                SU ARTICULO 173 PARTE FINAL Y ARTICULOS CORRELATIVOS.
                <!-- Calle: . RFC: PEH941027UZ1, Teléfono:4913133/4913127 -->
			</td>
        </tr>
        <!--<tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><span style="margin-left: 30%;">FIRMA</span></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><span style="margin-left: 15%;">___________________________________</span></td>
        </tr>
    </table>-->
<?php }
//else if($object->type == 1) {?>
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
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%;">&nbsp;</td>
            </tr>
            <tr>
                <td style="width: 33.33%;">&nbsp;</td>
                <td style="width: 33.33%;" align="center">___________________________</td>
                <td style="width: 33.33%;">&nbsp;</td>
            </tr>
        </table>
<?php    }?>
<script type="text/javascript">

	window.print();
	<?php if($conf->global->POS_CLOSE_WIN){?>
	window.close();
	<?php }?>

</script>

<a class="lien" href="#" onclick="javascript: window.close(); return(false);">Fermer cette fenetre</a>

</body>
