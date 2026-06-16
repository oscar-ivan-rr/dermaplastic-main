<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/pay.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter payments
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER', '1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB', '1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$place = (GETPOST('place', 'int') > 0 ? GETPOST('place', 'int') : 0); // $place is id of table for Ba or Restaurant

$invoiceid = GETPOST('invoiceid', 'int');


/*
 * View
 */

$invoice = new Facture($db);
if ($invoiceid > 0)
{
    $invoice->fetch($invoiceid);
}
else
{
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
        $invoiceid = $obj->rowid;
    }
    if (!$invoiceid)
    {
        $invoiceid = 0; // Invoice does not exist yet
    }
    else
    {
        $invoice->fetch($invoiceid);
    }
}

$arrayofcss = array('/takepos/css/pos.css');
$arrayofjs=array();

top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

$langs->loadLangs(array("main", "bills", "cashdesk"));

$sql = "SELECT code, libelle as label FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
        $paycode = $obj->code;
        if ($paycode == 'LIQ') $paycode = 'CASH';
        if ($paycode == 'CB')  $paycode = 'CB';
        if ($paycode == 'CHQ') $paycode = 'CHEQUE';

        $accountname = "CASHDESK_ID_BANKACCOUNT_".$paycode.$_SESSION["takeposterminal"];
		if (!empty($conf->global->$accountname) && $conf->global->$accountname > 0) array_push($paiements, $obj);
	}
}
?>
<link rel="stylesheet" href="css/pos.css">
</head>
<body>

<script>
<?php
$remaintopay = 0;
if ($invoice->id > 0)
{
    $remaintopay = $invoice->getRemainToPay();
}
$alreadypayed = (is_object($invoice) ? ($invoice->total_ttc - $remaintopay) : 0);

if ($conf->global->TAKEPOS_NUMPAD == 0) print "var received='';";
else print "var received=0;";

?>
	var alreadypayed = <?php echo $alreadypayed ?>;
	var isProcessing = false; // Variable para controlar si hay un proceso de pago en curso

	document.onkeydown = function (e){
		if (isProcessing) return; // No procesar teclas si hay un pago en curso
		
		if (e.key == "0"){
			addreceived(0);
		}
		else if (e.key == "1"){
			addreceived(1);
		}
		else if (e.key == "2"){
			addreceived(2);
		}
		else if (e.key == "3"){
			addreceived(3);
		}
		else if (e.key == "4"){
			addreceived(4);
		}
		else if (e.key == "5"){
			addreceived(5);
		}
		else if (e.key == "6"){
			addreceived(6);
		}
		else if (e.key == "7"){
			addreceived(7);
		}
		else if (e.key == "8"){
			addreceived(8);
		}
		else if (e.key == "9"){
			addreceived(9);
		}
		else if (e.key == "."){
			addreceived('.');
		}
		else if (e.key == "Backspace"){
			reset();
		}
	}

	function addreceived(price)
	{
		if (isProcessing) return; // No procesar si hay un pago en curso
		
    	<?php
    	if (empty($conf->global->TAKEPOS_NUMPAD)) print 'received+=String(price);'."\n";
    	else print 'received+=parseFloat(price);'."\n";
    	?>
    	$('.change1').html(pricejs(parseFloat(received), 'MT'));
    	$('.change1').val(parseFloat(received));
		alreadypaydplusreceived=price2numjs(alreadypayed + parseFloat(received));
    	//console.log("already+received = "+alreadypaydplusreceived);
    	//console.log("total_ttc = "+<?php echo $invoice->total_ttc; ?>);
    	if (alreadypaydplusreceived > <?php echo $invoice->total_ttc; ?>)
   		{
			var change=parseFloat(alreadypayed + parseFloat(received) - <?php echo $invoice->total_ttc; ?>);
			$('.change2').html(pricejs(change, 'MT'));
	    	$('.change2').val(change);
	    	$('.change1').removeClass('colorred');
	    	$('.change1').addClass('colorgreen');
	    	$('.change2').removeClass('colorwhite');
	    	$('.change2').addClass('colorred');
		}
    	else
    	{
			$('.change2').html(pricejs(0, 'MT'));
	    	$('.change2').val(0);
	    	if (alreadypaydplusreceived == <?php echo $invoice->total_ttc; ?>)
	    	{
		    	$('.change1').removeClass('colorred');
		    	$('.change1').addClass('colorgreen');
	    		$('.change2').removeClass('colorred');
	    		$('.change2').addClass('colorwhite');
	    	}
	    	else
	    	{
		    	$('.change1').removeClass('colorgreen');
		    	$('.change1').addClass('colorred');
	    		$('.change2').removeClass('colorred');
	    		$('.change2').addClass('colorwhite');
	    	}
    	}
	}

	function reset()
	{
		if (isProcessing) return; // No procesar si hay un pago en curso
		
		received=0;
		$('.change1').html(pricejs(received, 'MT'));
		$('.change1').val(price2numjs(received));
		$('.change2').html(pricejs(received, 'MT'));
		$('.change2').val(price2numjs(received));
    	$('.change1').removeClass('colorgreen');
    	$('.change1').addClass('colorred');
    	$('.change2').removeClass('colorred');
    	$('.change2').addClass('colorwhite');
	}

	// Función para actualizar el estado visual de los botones
	function toggleButtonsState(disabled) {
		$('.calcbutton, .calcbutton2, .calcbutton3').prop('disabled', disabled);
		if (disabled) {
			$('.calcbutton, .calcbutton2, .calcbutton3').addClass('payment-processing');
			$('body').append('<div id="payment-overlay"><div class="payment-message">Procesando pago, espere por favor...</div></div>');
		} else {
			$('.calcbutton, .calcbutton2, .calcbutton3').removeClass('payment-processing');
			$('#payment-overlay').remove();
		}
	}

	function Validate(payment)
	{
		if (isProcessing) return; // Evitar doble clic
		
		isProcessing = true;
		toggleButtonsState(true);
		
		var invoiceid = <?php echo ($invoiceid > 0 ? $invoiceid : 0); ?>;
		var amountpayed = $("#change1").val();
		var amountpayed2 = amountpayed;
		if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
			amountpayed = <?php echo $invoice->total_ttc; ?>;
		}
		console.log("We click on the payment mode to pay amount = "+amountpayed);
		parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay="+payment+"&amount="+amountpayed+"&payment="+amountpayed2+"&invoiceid="+invoiceid, function() {
		    if (amountpayed > <?php echo $remaintopay; ?> || amountpayed == <?php echo $remaintopay; ?> || amountpayed==0 ) {
				parent.$.colorbox.close();
			} else {
				isProcessing = false;
				toggleButtonsState(false);
				location.reload();
			}
		});
	}

	function ValidateSumup() {
		if (isProcessing) return; // Evitar doble clic
		
		isProcessing = true;
		toggleButtonsState(true);
		
		console.log("Launch ValidateSumup");
		<?php $_SESSION['SMP_CURRENT_PAYMENT'] = "NEW" ?>
        var invoiceid = <?php echo($invoiceid > 0 ? $invoiceid : 0); ?>;
        var amountpayed = $("#change1").val();
        if (amountpayed > <?php echo $invoice->total_ttc; ?>) {
            amountpayed = <?php echo $invoice->total_ttc; ?>;
        }

        // Starting sumup app
        window.open('sumupmerchant://pay/1.0?affiliate-key=<?php echo $conf->global->TAKEPOS_SUMUP_AFFILIATE ?>&app-id=<?php echo $conf->global->TAKEPOS_SUMUP_APPID ?>&total=' + amountpayed + '&currency=EUR&title=' + invoiceid + '&callback=<?php echo DOL_MAIN_URL_ROOT ?>/takepos/smpcb.php');

        var loop = window.setInterval(function () {
            $.ajax('/takepos/smpcb.php?status').done(function (data) {
                console.log(data);
                if (data === "SUCCESS") {
                    parent.$("#poslines").load("invoice.php?place=<?php echo $place; ?>&action=valid&pay=CB&amount=" + amountpayed + "&invoiceid=" + invoiceid, function () {
                        parent.$.colorbox.close();
                    });
                    clearInterval(loop);
                } else if (data === "FAILED") {
                    isProcessing = false;
                    toggleButtonsState(false);
                    parent.$.colorbox.close();
                    clearInterval(loop);
                }
            });
        }, 2500);
    }

	function removePromo() {
		var invoiceid = <?php echo ($invoiceid > 0 ? $invoiceid : 0); ?>;

		$('#promo-remove-btn').prop('disabled', true).css({'background':'#888', 'cursor':'not-allowed'});

		$.ajax({
			url: 'invoice.php',
			data: {
				action: 'removepromo',
				place: <?php echo $place; ?>,
				invoiceid: invoiceid
			},
			dataType: 'json',
			success: function(resp) {
				if (resp.success) {
					location.reload();
				}
			},
			error: function() {
				$('#promo-msg').html('<span style="color:#f2dede;">Error al quitar el código</span>');
				$('#promo-remove-btn').prop('disabled', false).css({'background':'#c0392b', 'cursor':'pointer'});
			}
		});
	}

	function applyPromo() {
		var code = $('#promo-code-input').val().trim().toUpperCase();
		if (!code) return;

		var invoiceid = <?php echo ($invoiceid > 0 ? $invoiceid : 0); ?>;

		$.ajax({
			url: 'invoice.php',
			data: {
				action: 'applypromo',
				code: code,
				place: <?php echo $place; ?>,
				invoiceid: invoiceid
			},
			dataType: 'json',
			success: function(resp) {
				if (resp.success) {
					$('#promo-msg').html('<span style="color:#dff0d8;">✓ Descuento del ' + resp.discount + '% aplicado</span>');
					$('#promo-code-input').prop('readonly', true);
					$('#promo-apply-btn').prop('disabled', true).css({'background':'#888', 'cursor':'not-allowed'});
					setTimeout(function() { location.reload(); }, 900);
				} else {
					$('#promo-msg').html('<span style="color:#f2dede;">Código inválido</span>');
				}
			},
			error: function() {
				$('#promo-msg').html('<span style="color:#f2dede;">Error al aplicar el código</span>');
			}
		});
	}
</script>

<!-- Estilos CSS para el estado de procesamiento -->
<style>
.payment-processing {
    opacity: 0.7;
    cursor: not-allowed;
}
#payment-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}
.payment-message {
    background: white;
    padding: 20px;
    border-radius: 5px;
    font-size: 18px;
    font-weight: bold;
    color: #333;
}
</style>

<div style="position:absolute; top:2%; left:5%; height:30%; width:91%;">
<center>
<div class="paymentbordline paymentbordlinetotal">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans('TotalTTC'); ?>: </font><span id="totaldisplay" class="colorwhite"><?php echo price($invoice->total_ttc, 1, '', 1, -1, -1) ?></span></font></span></center>
</div>
<?php if ($remaintopay != $invoice->total_ttc) { ?>
<div class="paymentbordline paymentbordlineremain">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans('RemainToPay'); ?>: </font><span id="remaintopaydisplay" class="colorwhite"><?php echo price($remaintopay, 1, '', 1, -1, -1) ?></span></font></span></center>
</div>
<?php } ?>
<div class="paymentbordline paymentbordlinereceived">
    <center><span class="takepospay"><font color="white"><?php echo $langs->trans("Received"); ?>: </font><span class="change1 colorred"><?php echo price(0) ?></span><input type="hidden" id="change1" class="change1" value="0"></font></span></center>
</div>
<div class="paymentbordline paymentbordlinechange">
<center><span class="takepospay"><font color="white"><?php echo $langs->trans("Change"); ?>: </font><span class="change2 colorwhite"><?php echo price(0) ?></span><input type="hidden" id="change2" class="change2" value="0"></font></span></center>
</div>
</center>
</div>

<div style="position:absolute; top:33%; left:5%; width:91%;">
    <div style="background:#2a6496; padding:5px 8px; border-radius:3px;">
        <center>
            <span style="color:white; font-size:13px; margin-right:4px;">Código:</span>
            <?php $promoApplied = !empty($_SESSION['takepos_promo_code']); ?>
            <input type="text" id="promo-code-input" placeholder="Código de promoción"
                   style="width:<?php echo $promoApplied ? '40%' : '58%'; ?>; padding:3px 6px; font-size:13px; text-transform:uppercase; border:none; border-radius:3px;"
                   <?php if ($promoApplied) echo 'value="'.htmlspecialchars($_SESSION['takepos_promo_code']).'" readonly'; ?>>
            <button type="button" id="promo-apply-btn" onclick="applyPromo()"
                    style="width:20%; padding:3px 2px; font-size:13px; background:<?php echo $promoApplied ? '#888' : '#5cb85c'; ?>; color:white; border:none; border-radius:3px; cursor:<?php echo $promoApplied ? 'not-allowed' : 'pointer'; ?>; margin-left:3px;"
                    <?php if ($promoApplied) echo 'disabled'; ?>>
                Aplicar
            </button>
            <?php if ($promoApplied): ?>
            <button type="button" id="promo-remove-btn" onclick="removePromo()"
                    style="width:20%; padding:3px 2px; font-size:13px; background:#c0392b; color:white; border:none; border-radius:3px; cursor:pointer; margin-left:3px;">
                Quitar
            </button>
            <?php endif; ?>
        </center>
        <div id="promo-msg" style="font-size:12px; text-align:center; min-height:15px; margin-top:2px;">
            <?php if (!empty($_SESSION['takepos_promo_code'])): ?>
                <span style="color:#dff0d8;">✓ <?php echo htmlspecialchars($_SESSION['takepos_promo_code']); ?> (<?php echo $_SESSION['takepos_promo_discount']; ?>% desc.)</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="position:absolute; top:44%; left:5%; height:50%; width:91%; display: inline-table;">
<?php
$action_buttons = array(
	array(
		"function" =>"reset()",
		"span" => "style='font-size: 150%;'",
		"text" => "C",
	    "class" => "poscolorblue"
	),
	array(
		"function" => "parent.$.colorbox.close();",
		"span" => "id='printtext'",
		"text" => $langs->trans("Cancel"),
	    "class" => "poscolordelete"
	),
);
$numpad = $conf->global->TAKEPOS_NUMPAD;
?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "7"; else print "10"; ?>);"><?php if ($numpad == 0) print "7"; else print "10"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "8"; else print "20"; ?>);"><?php if ($numpad == 0) print "8"; else print "20"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "9"; else print "50"; ?>);"><?php if ($numpad == 0) print "9"; else print "50"; ?></button>
<?php if (count($paiements) > 0) {
    $paycode = $paiements[0]->code;
    if ($paycode == 'LIQ') $paycode = 'cash';
    if ($paycode == 'CB')  $paycode = 'card';
    if ($paycode == 'CHQ') $paycode = 'cheque';
    ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paycode); ?>');"><?php echo $langs->trans("PaymentTypeShort".$paiements[0]->code); ?></button>
<?php } else { ?>
<button type="button" class="calcbutton2"><?php echo $langs->trans("NoPaimementModesDefined"); ?></button>
<?php } ?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "4"; else print "1"; ?>);"><?php if ($numpad == 0) print "4"; else print "1"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "5"; else print "2"; ?>);"><?php if ($numpad == 0) print "5"; else print "2"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "6"; else print "5"; ?>);"><?php if ($numpad == 0) print "6"; else print "5"; ?></button>
<?php if (count($paiements) > 1) {
    $paycode = $paiements[1]->code;
    if ($paycode == 'LIQ') $paycode = 'cash';
    if ($paycode == 'CB')  $paycode = 'card';
    if ($paycode == 'CHQ') $paycode = 'cheque';
    ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paycode); ?>');"><?php echo $langs->trans("PaymentTypeShort".$paiements[1]->code); ?></button>
<?php } else {
    $button = array_pop($action_buttons);
    ?>
	<button type="button" class="calcbutton2" onclick="<?php echo $button["function"]; ?>"><span <?php echo $button["span"]; ?>><?php echo $button["text"]; ?></span></button>
<?php } ?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "1"; else print "0.10"; ?>);"><?php if ($numpad == 0) print "1"; else print "0.10"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "2"; else print "0.20"; ?>);"><?php if ($numpad == 0) print "2"; else print "0.20"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "3"; else print "0.50"; ?>);"><?php if ($numpad == 0) print "3"; else print "0.50"; ?></button>
<?php if (count($paiements) > 2) {
    $paycode = $paiements[2]->code;
    if ($paycode == 'LIQ') $paycode = 'cash';
    if ($paycode == 'CB')  $paycode = 'card';
    if ($paycode == 'CHQ') $paycode = 'cheque';
    ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paycode); ?>');"><?php echo $langs->trans("PaymentTypeShort".$paiements[2]->code); ?></button>
<?php } else {
    $button = array_pop($action_buttons);
    ?>
	<button type="button" class="calcbutton2" onclick="<?php echo $button["function"]; ?>"><span <?php echo $button["span"]; ?>><?php echo $button["text"]; ?></span></button>
<?php } ?>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "0"; else print "0.01"; ?>);"><?php if ($numpad == 0) print "0"; else print "0.01"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "'000'"; else print "0.02"; ?>);"><?php if ($numpad == 0) print "000"; else print "0.02"; ?></button>
<button type="button" class="calcbutton" onclick="addreceived(<?php if ($numpad == 0) print "'.'"; else print "0.05"; ?>);"><?php if ($numpad == 0) print "."; else print "0.05"; ?></button>
<?php
$i = 3;
while ($i < count($paiements)) {
    ?>
<button type="button" class="calcbutton2" onclick="Validate('<?php echo $langs->trans($paiements[$i]->code); ?>');"><?php echo $langs->trans("PaymentTypeShort".$paiements[$i]->code); ?></button>
    <?php
	$i = $i + 1;
}

$keyforsumupbank = "CASHDESK_ID_BANKACCOUNT_SUMUP".$_SESSION["takeposterminal"];
if ($conf->global->TAKEPOS_ENABLE_SUMUP) {
	if (!empty($conf->global->$keyforsumupbank)) {
		print '<button type="button" class="calcbutton2" onclick="ValidateSumup();">Sumup</button>';
	} else {
		$langs->load("errors");
		$langs->load("admin");
		print '<button type="button" class="calcbutton2 disabled" title="'.$langs->trans("SetupNotComplete").'">Sumup</button>';
	}
}

$class = ($i == 3) ? "calcbutton3" : "calcbutton2";
foreach ($action_buttons as $button) {
    $newclass = $class.($button["class"] ? " ".$button["class"] : "");
    ?>
	<button type="button" class="<?php echo $newclass; ?>" onclick="<?php echo $button["function"]; ?>"><span <?php echo $button["span"]; ?>><?php echo $button["text"]; ?></span></button>
    <?php
}
?>
</div>

</body>
</html>
