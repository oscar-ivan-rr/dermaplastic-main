<?php
$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory
dol_include_once('/pos/class/pos.class.php');
#dol_include_once('/pos/class/mobile_detect.php');
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";

global $db, $langs,$conf;
$langs->load("pos@pos");
$langs->load("rewards@rewards");
$langs->load("bills");
$langs->load("companies");
if(empty($_SESSION['uname']) || empty($_SESSION['TERMINAL_ID']))
{
	accessforbidden();
}
$form = new Form($db);
$cash = new Cash($db);
$cash->fetch($_SESSION['TERMINAL_ID']);
$rc_receive_payments = (!empty($user->rights->pos->receive_payments)) ? true:false;  
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html style="height: 100%; overflow: hidden;" xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"> 
	<title><?php echo $langs->trans("POS ").$conf->global->MAIN_INFO_SOCIETE_NOM; ?></title> 
	<link rel="stylesheet" type="text/css" href="css/layout-default-latest.css">
	<link rel="stylesheet" type="text/css" href="<?= DOL_URL_ROOT?>/theme/common/fontawesome-5/css/all.min.css?layout=classic&version=11.0.3">
	<link rel="stylesheet" type="text/css" href="<?= DOL_URL_ROOT?>/includes/jquery/plugins/jnotify/jquery.jnotify.min.css">
	<link rel="stylesheet" type="text/css" href="css/jquery.css">
	<link rel="stylesheet" type="text/css" href="css/keyboard.css">
	<link rel="stylesheet" type="text/css" href="css/jquery.ui.chatbox.css">
    <link href="css/jquery-ui.css" type="text/css" rel="Stylesheet" class="ui-theme">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
	<script type="text/javascript" src="js/jquery-latest.js"></script> 
	<script type="text/javascript" src="js/jquery-ui-latest.js"></script> 
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery.class.js"></script>
	<!--  <script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>-->
    <script type="text/javascript" src="<?= DOL_URL_ROOT ?>/includes/jquery/plugins/jnotify/jquery.jnotify.js"></script>
    <!-- dm (dummy) agregado para evitar problemas de caché. Cambia cada minuto. -->
	<script type="text/javascript" src="js/tpv.js?dm=<?php echo md5(date('Y-m-d H:i')) ?>"></script>
	<script type="text/javascript" src="js/layout.js"></script>
    <script type="text/javascript" src="js/jquery.keyboard.min.js"></script>
    <script type="text/javascript" src="js/jquery.printPage.js"></script>
    <script type="text/javascript" src="js/jquery.ui.chatbox.js"></script>
  	<?php if ($conf->global->POS_PRINT_MODE == 1) { ?>
	<div style="visibility:hidden;position:absolute; top:1%; left:1%;">
	<applet id="qz" name="QZ Print Plugin" code="qz.PrintApplet.class" archive="./qz-print.jar" width="50px" height="50px">
	<param name="jnlp_href" value="qz-print_jnlp.jnlp">
	<param name="cache_option" value="plugin">
	<param name="disable_logging" value="false">
	<param name="initial_focus" value="false">
	</applet></div>
	<script src="js/printer.js" type="text/javascript"></script>
	<?php } ?>
	<script type="text/javascript">
	var rc_stClr={};
	<?php foreach(POS::getEstadovArray(null,'color') as $ev_k => $ev_v) : ?>
	rc_stClr[<?php echo $ev_k;?>] = '<?php echo $ev_v;?>'; 
	<?php endforeach; ?>
	var rc_stBak={};
	<?php foreach(POS::getEstadovArray(null,'background') as $ev_k => $ev_v) : ?>
	rc_stBak[<?php echo $ev_k;?>] = '<?php echo $ev_v;?>'; 
	<?php endforeach; ?>
	var rc_stLbl={};
	<?php foreach(POS::getEstadovArray(null,'label') as $ev_k => $ev_v) : ?>
	rc_stLbl[<?php echo $ev_k;?>] = '<?php echo $ev_v;?>'; 
	<?php endforeach; ?>
	<?php 	
	echo "var printer_name='".$cash->printer_name."';";
	echo "var drawer='".$conf->global->POS_OPEN_DRAWER."';";
	?>
   
    // -- Permiso para recibir cobros --
    	var rc_receive_payments = <?php echo (empty($user->rights->pos->receive_payments)) ? 'false':'true'; ?>; 

	$(document).ready(function () {
      	$('#idClient :input').each(function(i,e){
      		if (e.id != 'id_customer_email')
      		{
	      		$(e).keyup(function(){
   	   				$(e).val($(e).val().toUpperCase()); 
   		   		});
      		}
      	});
		

	<?php if ($conf->global->POS_PRINT_MODE==1) { ?>
	setInterval(function(){check()},5000);
	<?php } ?>
});
	</script>
        <style>
            .options input{
                display: inline-block;
                float: right;
                margin-right: 3px;
            }
            .options label{
                width:170px !important;
                text-align:right;
            }
            .options table tr td label{
                width:80px !important;
                text-align:left;
                margin-left:0 !important;
            }
            .options table tr td input{
                display:inline-block;
                float:left;
                width:80px !important;
            }
            #table_selectProduct tbody tr:hover{
        		background-color: #E21D26;
        	}
        	#table_selectProduct tbody tr:hover td{
                color: #fff !important;
                text-shadow: none !important;
        	}
            #big-imagen{
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.1);
                display: none;
            }
            #big-img{
                /*left: calc(100%/2 - 180px);*/
                top: calc(100%/2 - 180px);

            }
           /* select#type_searchbyref{
                -webkit-appearance: button;
                -webkit-border-radius: 2px;
                -webkit-box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1);
                -webkit-padding-end: 20px;
                -webkit-padding-start: 2px;
                -webkit-user-select: none;
                background-image: url(http://i62.tinypic.com/15xvbd5.png), -webkit-linear-gradient(#FAFAFA, #F4F4F4 40%, #E5E5E5);
                background-position: 97% center;
                background-repeat: no-repeat;
                border: 1px solid #AAA;
                color: #555;
                /*border-radius: 10px;*/
            }*/
            .mySlides {display:none}
            .fullImage {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                right: 0;
                max-width: 80%;
                max-height: 80%;
                margin: auto;
                overflow: auto;
            }
            .w3-left, .w3-right, .w3-badge, .btn-close{cursor:pointer}
            .w3-badge {height:13px;width:13px;padding:0}
            #closedropdown,#moreProducts,#lessProducts{
                background: none!important;
                cursor: pointer;
                border: 0;
                color: white;
                padding-top: 3px;
            }
			*, *:before, *:after {
    			box-sizing: initial;
			}
			html, body {
				line-height: 1;
			}
            #table_selectProduct {
                width: 100%;background-color: white;
                border-spacing: 0;
            }

            #table_selectProduct th,
            #table_selectProduct td,
            #table_selectProduct tr,
            #table_selectProduct thead,
            #table_selectProduct tbody { display: block; }

            #table_selectProduct thead tr {
                /* fallback */
                width: 100%;
                /* minus scroll bar width */
                width: -webkit-calc(100% - 16px);
                width:    -moz-calc(100% - 16px);
                width:         calc(100% - 16px);
            }

            #table_selectProduct tr:after {
                content: ' ';
                display: block;
                visibility: hidden;
                clear: both;
            }

            #table_selectProduct tbody {
                height: 100px;
                overflow-y: auto;
                overflow-x: hidden;
            }

            #table_selectProduct tbody td,
            #table_selectProduct thead th {
                width: 19%;
                float: left;
            }

            #table_selectProduct thead tr th {
                height: 30px;
                line-height: 30px;
                /*text-align: left;*/
            }

            #table_selectProduct tbody { border-top: 2px solid black; }

            #table_selectProduct tbody td:last-child, #table_selectProduct thead th:last-child {
                /*border-right: none !important;*/
            }
            .rc_dark_buttons{
	float: left;
    border: 1px solid #666 !important;
    height: 25px;
    margin-bottom: 3px;
    border-radius: 3px;
    margin-left: 3px;
    font-size: 20px;
            }
        </style>
 </head>

<body style="position: relative; overflow: hidden; margin: 0px; padding: 0px; border: medium none;" class="ui-layout-container">
<div id="big-imagen">

    <img class="btn-close" src="img/close.png" onClick="ocultar()" style=" float: right; padding-right: 8px; padding-bottom: 8px; display:block;" width="100px" height="100 px">
    </img>
    <div class="w3-center w3-container w3-section w3-large w3-text-white w3-display-bottommiddle" style="width:80%" id="nav-buttons">
        <div class="w3-left w3-hover-text-khaki" onclick="plusDivs(-1)">&#10094;</div>
        <div class="w3-right w3-hover-text-khaki" onclick="plusDivs(1)">&#10095;</div>
    </div>
</div>
<script type="text/javascript">
    var imgwarning='<?=DOL_URL_ROOT."/theme/eldy/img/alert-24.png"?>';
    var slideIndex = 1;
    function mostrar2(){
        document.getElementById('big-imagen').style.display = 'block';
        showImages(slideIndex);
    }
    function ocultar(){
        document.getElementById('big-imagen').style.display = 'none';
        //document.getElementById('big-img').src = "";
    }
    function showImages(n) {
        var i;
        var dots = "";
        var x = document.getElementsByClassName("mySlides");
        if (n > x.length) {slideIndex = 1}
        if (n < 1) {slideIndex = x.length}
        var noimages = x.length;
        var images ="";
        for (i = 0; i < noimages ; i++) {
            images += '<img class="fullImage" src="'+x[i].src+'" onClick="ocultar()" style="width:100%; display:none">';
            dots += ' <span class="w3-badge demo w3-border w3-transparent w3-hover-white" onclick="currentDiv('+(i+1)+')"></span>';
        }
        $(".fullImage").remove();
        $(".demo").remove();
        document.getElementById('big-imagen').insertAdjacentHTML("beforeend", images);
        document.getElementById('nav-buttons').insertAdjacentHTML("beforeend", dots);
        x = document.getElementsByClassName("fullImage");
        dots = document.getElementsByClassName("demo");
        x[slideIndex-1].style.display = "block";
        dots[slideIndex-1].className += " w3-white";
    }
    function plusDivs(n) {
        showImages(slideIndex += n);
    }
    function currentDiv(n) {
        showImages(slideIndex = n);
    }
</script>
<!--<div style="position: absolute; margin: 0px; top: 0px; bottom: auto; left: 0px; right: 0px; width: auto; z-index: 1; height: 19px; visibility: visible; display: none;" class="ui-layout-north ui-widget-content add-padding ui-layout-pane ui-layout-pane-north">North</div> 
<div style="position: absolute; margin: 0px; top: auto; bottom: 0px; left: 0px; right: 0px; width: auto; z-index: 1; height: 19px; visibility: visible; display: none;" class="ui-layout-south ui-widget-content add-padding ui-layout-pane ui-layout-pane-south">South</div>-->
<?php if($conf->global->POS_INV){?>
<div class="total_but_inv" style="margin:10px;-webkit-transform: rotate(-180deg);-moz-transform: rotate(-180deg);padding:0 5px 0 0; color:#fff;">
<?php echo $langs->trans("TotalTicket"); ?>&nbsp;<span id="totalTicketinv" style="clear:both; font-weight:bold; font-size:80px; line-height:40px; margin:1px 0 0;">0</span>&nbsp;<?php echo $conf->currency ?>
</div>
<?php }?>

<!-- CENTER COL -->
<div id="tabs-center" class="ui-layout-center no-scrollbar add-padding  ui-layout-pane ui-layout-pane-center ui-layout-container ui-tabs ui-widget ui-widget-content ui-corner-all ui-layout-pane-hover ui-layout-pane-center-hover ui-layout-pane-open-hover ui-layout-pane-center-open-hover">

<!-- CENTER COL HEADER -->
<div class="header darkblue gradient"  >
	
    <div>
      	<img class="photo" id="id_image" alt="" src="" height="53">
    </div>
    
    <div class="user_top">
    	<span id="id_user_name" class="user">
			<?php echo $langs->trans("User"); ?>
        </span>
         <span id="id_user_terminal" class="user terminal">	
			<?php echo $langs->trans("Terminal: ".$cash->name);?>
        </span>
        <span id="infoCartTicket">
            <span id="infoCustomer"><?php echo $langs->trans("ByDefault"); ?></span>
        </span>
     </div>
         	
   
     	<div class="fecha">
            <span style="font-size: 12px; color: #ffffff !important;">
                 <script type="text/javascript">
                    var dia=new Array(7);
                    dia[0]='<?php echo $langs->trans("Sunday");?>';
                    dia[1]='<?php echo $langs->trans("Monday");?>';
                    dia[2]='<?php echo $langs->trans("Tuesday");?>';
                    dia[3]='<?php echo $langs->trans("Wednesday");?>';
                    dia[4]='<?php echo $langs->trans("Thursday");?>';
                    dia[5]='<?php echo $langs->trans("Friday");?>';
                    dia[6]='<?php echo $langs->trans("Saturday");?>';
                    var date = new Date();
                    var day = date.getDate();
                    var month = date.getMonth() + 1;
                    var yy = date.getYear();
                    var year = (yy < 1000) ? yy + 1900 : yy;
                    document.write(dia[date.getDay()] + " " + day + "." + month + "." + year);
                </script>    	
                    
                    <script type="text/javascript">
                        function startTime(){
                        today=new Date();
                        h=today.getHours();
                        m=today.getMinutes();
                        s=today.getSeconds();
                        m=checkTime(m);
                        document.getElementById('reloj').innerHTML=h+":"+m;
                        t=setTimeout('startTime()',500);}
                        function checkTime(i)
                        {if (i<10) {i="0" + i;}return i;}
                        window.onload=function(){startTime();}
                    </script>
              </span>
              <br/>
              <span id="reloj"></span>
                    
            </div>
    
    <a class="logout but"  href="#" id="btnLogout" title="<?php echo $langs->trans("Logout"); ?>" target="_self"></a>
    <!--<a class="top_tactil on" style="background-color: #555;  border: 1px #ffffff solid;  border-radius: 0px 0px 0px 0px" id="id_btn_tpvtactil" href="#" title="<?php echo $langs->trans("TouchTPV"); ?>"></a>
    <a class="top_infoproduct" id="id_btn_infoproduct" href="#" title="<?php echo $langs->trans("InfoProduct"); ?>"></a> -->
    <a class="top_employee but"  id="id_btn_employee" href="#" title="<?php echo $langs->trans("ChangeEmployee"); ?>"></a>
    <!-- <a class="top_barcode off" id="id_btn_barcode" href="#" title="<?php echo $langs->trans("barcode"); ?>"></a> -->
    <a class="top_closecash but"  id="id_btn_closecash" href="#" title="<?php echo $langs->trans("CashAccount"); ?>"></a>
    <!--  <a class="top_closecash but"  id="id_btn_closeproduct" href="#" title="<?php echo $langs->trans("CloseProducts"); ?>"></a>-->

   	<?php 
   	$detect = new Mobile_Detect();
   	if(!$detect->isMobile() && !$detect->isTablet()){?>
    <a class="top_closecash but"  id="id_btn_fullscreen" href="#" title="<?php echo $langs->trans("FullScreen"); ?>"></a>
    <?php }?>
    <?php if($conf->global->POS_CHAT){?><a class="top_closecash but"  id="id_btn_chat" href="#" title="<?php echo $langs->trans("Chat"); ?>"></a>
    <?php }?>
    <?php if($conf->global->POS_OPEN_DRAWER && $conf->global->POS_PRINT_MODE){?><a class="top_opendrawer but"  id="id_btn_opendrawer" href="#" title="<?php echo $langs->trans("PosOpenDrawer"); ?>"></a>
    <?php }?>
    <a class="fullreload but"  id="id_btn_fullreload" href="#" onclick="window.location.reload(true);" title="<?php echo $langs->trans("Recargar"); ?>"></a>   
</div>
<!-- END CENTER COL HEADER -->

<!-- CENTER TABS -->
	<ul style="position: absolute; width: auto; z-index: 1; height: 40px !important; visibility: visible; display: block;" class="ticket ui-layout-north no-scrollbar allow-overflow ui-layout-pane ui-layout-pane-north ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header no-border no-bg no-padding">
		
        <li class="tab_tick ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tab-center-1"><span class="tab_icon"></span><?php echo $langs->trans("Ticket"); ?></a></li>
		<!--  <li class="tab_data ui-state-default ui-corner-top"><a href="#tab-center-2"><span class="tab_icon"></span><?php echo $langs->trans("Data"); ?></a></li>-->
        <!--  <li class="tab_cust ui-state-default ui-corner-top"><a href="#tab-center-3"><span class="tab_icon"></span><?php echo $langs->trans("Customers"); ?></a></li>-->
		<?php if($conf->global->POS_TICKET){?><li class="tab_hist ui-state-default ui-corner-top"><a id="tabHistory" href="#history"><span class="tab_icon"></span><?php echo $langs->trans("History"); ?></a></li><?php }?>
		<?php if(!$conf->global->POS_TICKET){?><li class="tab_hist ui-state-default ui-corner-top"><a id="tabHistoryFac" href="#historyFac"><span class="tab_icon"></span><?php echo $langs->trans("History"); ?></a></li><?php }?>
		<!-- checkpoint-->
		<li class="tab_stoc ui-state-default ui-corner-top"><a id="tabStock" href="#almacen"><span class="tab_icon"></span><?php echo $langs->trans("TabStock"); ?></a></li>

	<!--  	<?php if($conf->global->POS_PLACES){?>
		<li class="tab_stoc ui-state-default ui-corner-top"><a id="tabPlaces" href="#places"><span class="tab_icon"></span><?php echo $langs->trans("Places"); ?></a></li>
        <?php }?>-->
       <!-- <li class="tab_dashboard ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tab-dashboard"><span class="tab_icon"></span><?php echo $langs->trans("Dashboard"); ?></a></li> --> 
		
         
         <!--   <span class="topinfo">
            	  <span>2</span>  
            	<img src="./img/info.png">
            </span>-->  
       
    </ul>
<!-- END CENTER TABS --> 

    
<p id="top_sep"> <br clear="all" />  </p>
	
    <div class="ticket_content ui-layout-center ui-widget-content add-scrollbar ui-layout-pane ui-layout-pane-center ui-layout-pane-hover ui-layout-pane-center-hover ui-layout-pane-open-hover ui-layout-pane-center-open-hover" style="">
 
        <div id="tab-center-1" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom" style="margin-top:0 !important;">
		 	
      		<!--<div id="ticketLeft">


      		    <div style="">
	                <div class="clearfix tabContainer2" id="info_product">
	            	    <div id="product-right-column" style="padding: 10px; color: #fff">
	                        <div>
	                            <img width="90px;" onClick="mostrar2()" height="90px;" style=" float: left; padding-right: 8px; padding-bottom: 8px;" width="100%" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
	                        </div>
	                        <div class="label">
	                            <span id="our_label_display" style="font-size: 20px !important;"> </span>
	                        </div>
	                        <div class="price">
	                            <span class="our_price_display" >
	                                <span id="our_price_display" style="font-size: 28px !important;"> </span><?php echo $langs->trans($conf->currency);?>
	                            </span>
	                        </div>
	                        <div class="price_min">
	                            <span id="our_price_min" class="our_price_min_display" >
	                                <span id="our_price_min_display" style="font-size: 14px !important;"> </span><?php echo $langs->trans($conf->currency);?>
	                            </span>
	                        </div>
                            <div class="">
	                            <span id="delivery_days" class="" >
	                                <span id="delivery_days_display" style="font-size: 12px !important;">Días de Entrega</span>
	                            </span>
                            </div>
	                        <a class="btn3d" id="btnHideInfo" style="width:80px; float:right;"><?php echo $langs->trans("More");?></a>
	                        <div id="short_description_block">
	                            <p><br><span class="rte align_justify" id="short_description_content" style="font-size: 11px;"><p></p></span></p>
	                        </div>


					    </div>
	            	    <div style="clear:both"></div>
	                </div>
                </div>

                <div id="ticketOptions" class="leftBlock clearfix tabContainer2" style="display:none">
                    <div class="colActions"></div>
                </div>

      		    <div id="products" class="leftBlock"  style="overflow: auto;" ></div>



                <div id="idTicketLine" class="leftBlock bloqueOpciones" style="display:none" title="">
			    <div class="options">
				    <ul>
    					<li><label><?php echo $langs->trans("Units"); ?>:</label>
					    <input onclick="this.select()" type="text" size="6" name="line_quantity" id="line_quantity" value="0"  class="numKeyboard"></li>
					    <br clear="all" />
					    <li><label>% <?php echo $langs->trans("Discount"); ?>:</label>
					    <input onclick="this.select()" type="text" size="5" maxlength="3" name="line_discount" id="line_discount"  value="0" class="numKeyboard"></li>
					    <br clear="all" />
					    <li><label><?php echo $langs->trans("Price"); ?>:</label>
					    <input onclick="this.select()" type="text" size="6" name="line_price" id="line_price" value="0"  class="numKeyboard"></li>
					    <br clear="all" />
					    <li><label><?php echo $langs->trans("Note"); ?>:</label>
					    <input onclick="this.select()" type="text" size="6" name="line_note" id="line_note" value=""  class="quertyKeyboard"></li>
					    <br clear="all" />
				    </ul>
					<input type="button" id="id_btn_editTicketline" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">

			</div>
			</div>
            <div id="sustitutes" class="leftBlock bloqueOpciones" style="display:none" title="">
                <div class="options">
                    <table class="tableList">
                        <thead style="background: -webkit-gradient(linear, left top, left bottom, color-stop(0, #A8890E), color-stop(1, #FEEE8F));">
                        <tr class="">
                            <th style="width:122px; text-align:left; padding:0 0 0 5px;">Referencia</th>
                            <th style="width:122px; text-align:left; padding:0 0 0 5px;">Etiqueta</th>
                        </tr>
                        </thead>
                        <tbody id="sustituteslist"></tbody>
                    </table>
                </div>
            </div>

                <div id="complements" class="leftBlock bloqueOpciones" style="display:none" title="">
                    <div class="options">
                        <table class="tableList">
                            <thead style="background: -webkit-gradient(linear, left top, left bottom, color-stop(0, #A8890E), color-stop(1, #FEEE8F));">
                            <tr class="">
                                <th style="width:122px; text-align:left; padding:0 0 0 5px;">Referencia</th>
                                <th style="width:122px; text-align:left; padding:0 0 0 5px;">Etiqueta</th>
                                <th style="width:122px; text-align:left; padding:0 0 0 5px;">Cantidad</th>
                            </tr>
                            </thead>
                            <tbody id="complementslist"></tbody>
                        </table>
                    </div>
                </div>

			<div id="payType" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMode");?>">
				<div class="options">
					<div>
					<?php
						$payments = POS::select_Type_Payments();
						console.log($payments);
						if(sizeof($payments))
						{
							$i=0;
							while($i < sizeof($payments))
							//foreach($payments as $payment)
							{
								//echo "<div class='payment_types'><a class='btn3dbig' id='paytype".$payment['id']."' style='height:40px;'>".$payment['label']."</a></div>";
								echo '<div class="payment_types">'
									.'<label>'.$payments[$i]['label'].'</label>'
									.'<input style="height:35px; width:60%; '
									.		'border-radius:6px;font-size:25px;" '
									.		'onclick="this.select()" '
									.		''
									.		'value="asd" '
									.		'name="pay_client_'.$i.'" '
									.		'id="pay_client_'.$i.'" '
									.		($i<4 ? 'type="text" ':'type="hidden" ')
									.		'class="numKeyboard">';
								if ($i<4)
								{
									echo '<input type="button" id="pay_all_'.$i.'" value="'.$langs->trans("Remainder").'" class="chk3d	"></div>'."\r\n";
								}
								$i++;
							}
#							echo 	 '<input type="hidden" '
#									.		'value="" '
#									.		'name="pay_client_99" '
#									.		'id="pay_client_99" />';

						}
					?>
					</div>
				</div>
				<?php if($conf->global->REWARDS_POS){?>
				<div id="payment_points" class="payment_options">
					<div id="points_div">
					<label><?php echo $langs->trans("Points");?></label>
					<div class="points_total"></div><div id=eur ><span class="points_money"></span><?php echo $conf->currency ?></div>
					<label><?php echo $langs->trans("UsePoints");?></label>
					<div class="points_client">
						<input onclick="this.select()" type="text" value="" name="points_client_id" id="points_client_id" class="numKeyboard">
					</div>
					</div>
					<label><?php echo $langs->trans("CustomerRet");?></label>
					<div class="payment_return"></div>

				</div>
				<?php }?>

		 		<div id="payment_coupon"><input type="button" id="id_btn_coupon" value="<?php echo $langs->trans("UseCoupon"); ?>" class="btn3dbig">

		 		</div>
				<div id="payment_total_points" class="payment_options">



				<label><?php echo $langs->trans("CustomerRet");?></label>
				<div class="payment_return"></div>

				</div>
				<input type="button" id="id_btn_add_ticket" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig" style="block !mportant">
			</div>

		<div id="payTypeRet" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMode");?>">
			<div class="options">
			<div>
			<?php
				$payments = POS::select_Type_Payments();
				if(sizeof($payments))
				{
					$i=0;
					while($i < sizeof($payments))
					//foreach($payments as $payment)
					{
						//echo "<div class='payment_types'><a class='btn3dbig' id='paytype".$payment['id']."' style='height:40px;'>".$payment['label']."</a></div>";
						echo '<div class="payment_types"><label>'.$payments[$i]['label'].'</label><input style="height:35px; width:60%; border-radius:6px;font-size:25px;" onclick="this.select()" type="text" value="" name="pay_client_ret_'.$i.'" id="pay_client_ret_'.$i.'" class="numKeyboard">';
						echo '<input type="button" id="pay_all_ret_'.$i.'" value="'.$langs->trans("Remainder").'" class="chk3d	"></div>';
						$i++;
					}
				}
			?>
			</div>
			</div>

		 	<div id="payment_total_ret" class="payment_options">


				<label><?php echo $langs->trans("YetUnreturned");?></label>
				<div class="payment_return_ret"></div>

			</div>
			<input type="button" id="id_btn_add_ticket_ret" value="<?php echo $langs->trans("DoPaymentBack"); ?>" class="btn3dbig">
			<div id="convert_coupon"><input type="button" id="id_btn_add_ticket_desc" value="<?php echo $langs->trans("ConvertToReduc"); ?>" class="btn3dbig"></div>
		</div>

		<div id="idFactureMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Facture"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_TICKET) {?>
					<input type="button" id="id_btn_ticketPay" value="<?php echo $langs->trans("Ticket"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_FACTURE) {?>

					<?php }?>
				</div>
			</div>
		</div>

		<div id="idReturnMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Facture"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_TICKET) {?>
					<input type="button" id="id_btn_ticketRet" value="<?php echo $langs->trans("Ticket"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_FACTURE) {?>
					<input type="button" id="id_btn_facsimRet" value="<?php echo $langs->trans("Facturesim"); ?>" class="btn3dbig">
					<input type="button" id="id_btn_factureRet" value="<?php echo $langs->trans("Facture"); ?>" class="btn3dbig">
					<?php }?>
				</div>
			</div>
		</div>

			<div id="idTicketMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Ticket"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_PRINT) {?>
					<input type="checkbox" id="id_cb_ticketPrint" name="id_cb_ticketPrint" class="chk3d">
					<label style="float:left"><?php echo $langs->trans("GiftTicket"); ?></label>
					<input type="button" id="id_btn_ticketPrint" value="<?php echo $langs->trans("PrintTicket"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_MAIL) {?>
					<input type="button" id="id_btn_ticketMail" value="<?php echo $langs->trans("SendTicket"); ?>" class="btn3dbig">
					<?php }?>
				</div>
			</div>
		</div>
		<div id="idCashMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("CloseCash"); ?>">
			<div class="options">
				<div>
					<?php if($conf->global->POS_PRINT) {?>
					<input type="button" id="id_btn_cashPrint" value="<?php echo $langs->trans("Imprimir Cierre"); ?>" class="btn3dbig">
					<?php } if($conf->global->POS_MAIL) {?>
					<input type="button" id="id_btn_cashMail" value="<?php echo $langs->trans("Enviar cierre"); ?>" class="btn3dbig">
					<?php }?>
				</div>
			</div>
		</div>


			<div id="idDiscount" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("ApplyDiscount"); ?>">
			<div class="options">


					<ul>
					<li><div id="typeDiscount0">
						<label><?php echo $langs->trans("Percent"); ?></label><input onclick="this.select()" type="text" size="5" maxlength="2" name="ticket_discount_perc" id="ticket_discount_perc"  value="0" class="numKeyboard" />
					</div>


				 <input type="button" id="id_btn_add_discount" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">
				 </li>
				 </ul>
			</div>
		</div>

			</div>-->
            <div id="ticketRight">
             <div id="productSearch" class="topSearch">
    
          <!--   <div class="but barcode">
                <img height="48" width="60" id="id_btn_codebar" title="<?php echo $langs->trans("AddBarcode"); ?>" name="btnShowManualProducts" src="./img/barcode.png"></img>
                <span class="text"><?php echo $langs->trans("Barcode"); ?></span>
              </div>-->  	
    
               <div class="inputs" style="width:100% !important;">
               
               		<div border="0" style="width:100%;  height:65px;"  >
	                    
	                    <div class="tabContainer0" style="width:30%;margin-right:5px;">
		                    <!-- <label>
		                    	<?php echo $langs->trans("Search"); ?> 
		                    </label>-->
		                    <img id="img_product_search" class="search_but" class="but" src="./img/search_prod.png" height="40px" style="float:left;margin-left:8px;cursor:pointer">
		                    <input onclick="this.select()" type="text" class="quertyKeyboard" size=10 name="id_product_search" id="id_product_search">
                            <br>
                            <div name="boton-prospecto" id="boton-prospecto" class="text" style="display:none;"><a  class="btn3d"><?php echo 'Agregar Búsqueda';?></a></div>
	                    </div>
	                    <div class="tabContainer0" style="width:8%;margin-right:5px;" >
	                    	<h3 class="but" name="btnTotalNote" id="btnTotalNote" >
		                    	<?php echo $langs->trans("Notes"); ?>
		                   	</h3>
		                    <span id="totalNote_" style="display:block; margin:10px auto;text-align:center;font-size: 30px; color:#FFFFFF;font-weight:bold">
		                    	 0
		                    </span>
		                    
		                     
	                    </div>
	                     <?php if($conf->global->POS_PLACES){?>
	                    <div class="tabContainer1" style="width:35%;margin-right:5px;" >
	                       <?php }   else{?>
	                    <div class="tabContainer1" style="width:50%;margin-right:5px;display: inline-table;" >
	                     <?php }   ?>
	                     	<div style="display: table-cell; width: 100%;">
                            <!--<table style="border:none;">
                                <tr>
                                    <td style="width: 50%;border:none;">-->
                                    <div style="width: 100%;display:inline-table;">
	                    	            <h3 id="infoCustomer_" style="margin: 0;display: table-cell;"><?php echo $langs->trans("Customer");?></h3>
                                        <h3 id="Customer_remise" style="margin: 0;display: table-cell;"></h3>
                                        <h3 id="infoProyectCustomer_" style="marign:0;display: table-cell;"><?php echo $langs->trans("Project");?></h3>
                                        <br/><h3 id="infoCustomer_rfc" style="marign:0;display: table-cell;">RFC:</h3>
                                        
                                    </div>
                                    <div style="clear:both;display:block;witdh:100%;"></div>
                                    <div style="width: 100%;display:inline-table;">
                                        <h3 style="margin: 0;display: table-cell;">Ref. Cliente</h3>
                                        <input type="text" id="infoCustomer_ref" name="infoCustomer_ref" value="" />
                                    </div>
                                    <!--</td>
                                    <td style="width: 50%;border:none;">-->

                                    <!--</td>
                                </tr>
                            </table>-->
	                    	<div  name="btnChangeCustomer" id="btnChangeCustomer" ><a class="btn3d"><?php echo $langs->trans("ChangeCustomer");?></a></div>
	                    	<div   name="btnNewCustomer" id="btnNewCustomer"><a class="btn3d" ><?php echo "Añade un nuevo Cliente"?></a></div>
	                    	</div>
	                    </div>
 	                    	<div style="display: inline-table;">
							<div style="display: block;
    overflow: hidden;
    width: 84px;
    height: 95px;
    /* background-color: blue; */
    text-align: center;
    margin: 0 auto !important;
    padding-top: 10px;
    background-image: -webkit-gradient( linear, left bottom, left top, color-stop(0, rgb(255,255,255)), color-stop(1, rgb(3,77,162)) );display: none;display: none;" id="btnReloadTickett">
								<div class="but" name="btnReloadTicket" id="btnReloadTicket" style="width: 60px;height:48px;display:block;margin: 5px;">
									<img title="Recargar Ticket" src="./img/refresh.png" style="width: auto;height: 45px;" />
								</div>
							</div>
							</div>

	                    <?php if($conf->global->POS_PLACES){?>
	                    <div class="tabContainer0" style="width:24%; "> 
	                            <h3 class="text"><span id="totalPlace"> <?php echo $langs->trans("Place");?></span></h3>
	                            <div name="btnChangePlace" id="btnChangePlace" class="text" ><a  class="btn3d"><?php echo $langs->trans("ChangePlace");?></a></div>       
	                    </div>
	                    <?php }   ?>
	                    
                    </div>
               		<div id="divSelectProducts" style="display:none">
                        <button id="closedropdown" style="float:left" class="rc_dark_buttons">X</button>
                        <button id="moreProducts" style="float:left" class="rc_dark_buttons" onclick="$('#table_selectProduct>tbody').css('height','380px');$('#moreProducts').css('display','none');$('#lessProducts').css('display','block');">Más</button>
                        <button id="lessProducts" style="float:left;display:none;" class="rc_dark_buttons" onclick="$('#table_selectProduct>tbody').css('height','100px');$('#lessProducts').css('display','none');$('#moreProducts').css('display','block');">Menos</button>
                        <select name="id_selectProduct" id="id_selectProduct" multiple style="float:left;display:none;">
                        </select>
                        <table id="table_selectProduct" style="float:left;" class="tableList">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">CÓDIGO</th>
                                    <th style="width: 50%;">DESCRIPCIÓN</th>
                                    <th style="width: 10%;">PRECIO</th>
                                    <th style="width: 10%;">MATRIZ</th>
                                    <th style="width: 8%;">GPE</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
               		</div>
                    
	
	                	
	               </div>   
		    <br clear="all" />   
			</div>
            <div id="totalCart" class="grey">
            	<div id="totalCartDesc">
                <div class="but" name="btnOkTicket" id="btnOkTicket" style="display: none;"><img height=" " width=" " title="<?php echo $langs->trans("SaveThisTicket"); ?>" src="./img/acceptTicket.png">
                	<span class="text" ><?php echo $langs->trans("SaveTicket"); ?></span></div>
                <div class="but" id="btnSaveTicket" name="btnSaveTicket" style="display: none;"><img height=" " width=" " title="<?php echo $langs->trans("CreateDraftTicket"); ?>" src="./img/saveTicket.png">
                    <span class="text" ><?php echo $langs->trans("TicketDraft"); ?></span></div>
                <div class="but" name="btnAddDiscount" id="btnAddDiscount" style="display: none;"><img height=" " width=" " title="<?php echo $langs->trans("ApplyDiscountTicket"); ?>" src="./img/discount.png">
                    <span class="text" ><?php echo $langs->trans("ApplyDiscount"); ?></span></div>
                <div class="but" name="btnNewTicket" id="btnNewTicket" ><img height=" " width=" " title="<?php echo $langs->trans("CreateNewTicket"); ?>" src="./img/new_ticket.png">
                    <span class="text" ><?php echo $langs->trans("CreateNewTicket"); ?></span></div>
                 <div class="but" name="btnReturnTicket" id="btnReturnTicket" style="display:none"><img height=" " width=" " title="<?php echo $langs->trans("ReturnTicket"); ?>" src="./img/deleteTicket.png">
                    <span class="text" ><?php echo $langs->trans("ReturnTicket"); ?></span></div>
                 <div class="but" name="btnTicketNote" id="btnTicketNote" style="display:none"><img height=" " width=" " title="<?php echo $langs->trans("Note"); ?>" src="./img/noteTicket.png">
                    <span class="text" ><?php echo $langs->trans("Note"); ?></span></div>
                 <div class="but" name="btnTicketSendFront" id="btnTicketSendFront" style="display:none"><img title="<?php echo $langs->trans("Envio a Almac&eacute;n"); ?>" src="./img/send_front.png" style="width: 50px;height: 50px;">
                    <span class="text" ><?php echo $langs->trans("Envio a Almac&eacute;n"); ?></span></div>
                 <div class="but" name="btnTicketApartado" id="btnTicketApartado" style="display: none;"><img title="<?php echo $langs->trans("Apartado"); ?>" src="./img/star-xl.png" style="width: 50px;height: 50px;">
                    <span class="text" ><?php echo $langs->trans("Apartado"); ?></span></div>
                <div class="but" name="btnFreight" id="btnFreight" style="display: none;">
                    <img height=" " width=" " title="<?php echo $langs->trans("DemoFreightCalc"); ?>" src="./img/calc_freight.png" style="width: 50px;height: 50px;">
                	<span class="text" ><?php echo $langs->trans("SaveTicket"); ?></span></div>
                 <div class="but" name="btnTicketHomeDelivery" id="btnTicketHomeDelivery" style="display: none;">
				 	<img title="<?php echo $langs->trans("Entrega a Domicilio"); ?>" src="./img/truck-xl.png" style="width: 50px;height: 50px;">
                    <span class="text" ><?php echo $langs->trans("Entrega a Domicilio"); ?></span></div>
                 <div class="but" name="btnTicketAskTransfer" id="btnTicketAskTransfer" style="display: none; ">
				 	<img title="<?php echo $langs->trans("Solicitar Transferencia"); ?>" src="./img/arrow-down.png" style="width: 50px;height: 50px;">
				 	<span class="text" ><?php echo $langs->trans("Solicitar Transferencia"); ?></span></div>				 	
                 <div class="but" name="btnTicketAskAbroad" id="btnTicketAskAbroad" style="display: none;">
				 	<img title="<?php echo $langs->trans("Solicitar Surtido en Otro Almacén"); ?>" src="./img/arrow-58-64.png" style="width: 50px;height: 50px;">
				 	<span class="text" ><?php echo $langs->trans("Solicitar Surtido en Otro Almacén"); ?></span></div>
                 <div class="but" name="btnTicketPrintFront" id="btnTicketPrintFront" style="display: none;"><img title="<?php echo $langs->trans("Imprimir Ticket"); ?>" src="./img/print.png" style="width: 50px;height: 50px;">
                    <span class="text" ><?php echo $langs->trans("Imprimir Ticket"); ?></span></div>
                 <div class="text" name="btnTicketRef" id="btnTicketRef" style="display:none; float:left;font-size:25px;"></div>
                </div>
                  <div id="totalCartTicket">
                	<div class="discount_but" style="display: flex;" id="block_credit">
                    	<div style="width: 50%;">
                            <span class="total_text" style="color:#FFFFFF;font-size:10pt;margin-bottom: 4px;"><?php print $langs->trans("OutstandingBill")?>:</span>
                            <span id="limite_c" class="total_text" style="font-size:10pt;"></span>
                        </div>
                        <div style="width: 50%;">
                    	    <span class="total_text" style="color:#FFFFFF;font-size:9pt;margin-bottom: 4px;">Crédito Disponible:</span>
                            <span id="disponible_c" class="total_text" style="font-size:10pt;"></span>
                        </div>
                    </div>
                    <div class="discount_but">
                        <div style="float:left;">
                            <span id="btn_ocultcredit"class="btn" style="cursor:pointer;" onclick="$('#block_credit').css('display','none');$('#btn_ocultcredit').css('display','none');$('#btn_showcredit').css('display','block');">Ocultar</span>
                            <span id="btn_showcredit"class="btn" style="display:none;cursor:pointer;" onclick="$('#block_credit').css('display','flex');$('#btn_showcredit').css('display','none');$('#btn_ocultcredit').css('display','block');">Mostrar</span>
                        </div>
                        <div style="float:right;" id="totalwithoutdiscount">
                              <span class="total_text">Total</span>
                              <span class="number"><span id="totalWdiscount">0</span>&nbsp;<?php echo $conf->currency ?></span>
                          </div>
                    </div>
                      <div class="discount_but">
                      <div style="float:right;">
                            <span class="total_text"><?php echo $langs->trans("Discount"); ?></span>
                            <span class="number"><span id="totalDiscount">0</span>&nbsp;<?php echo $conf->currency ?></span>
                        </div>
                      </div>
                      <div class="total_but">
                       	<span class="total_text"><?php echo $langs->trans("TotalTicket"); ?> con descuento</span>
                       <span class="number" style="font-size: 18px;line-height: 18px;"><span id="totalTicket" >0</span>&nbsp;<?php echo $conf->currency ?><span id="alertfaclim" >
                       <img title="<?php echo $langs->trans('OverFactureLimit')?> " src="img/alert.png" style="float: left; margin: 1% 0px 0px 5%;display: none;"></span></span>
                                                   
                    </div>
                      <div class="discount_but" style="height:38px;">
                          <div style="float:right;display:none;" id="containerRestToPay">
                              <span class="total_text">Resta por pagar</span>
                              <span class="number" style="font-size: 28px;line-height: 28px;"><span id="totalRestToPay">0</span>&nbsp;<?php echo $conf->currency ?></span>
                          </div>

                      </div>
		        </div>
            <div style="clear:both"></div>
            </div>   
            
            
			<div id="ticketCart">
					<table cellspacing="0" cellpadding="0" id="tablaTicket" class="tableList">
		            <thead>
		              <tr>
                          <th class="idCol" style="width:122px; text-align:left; padding:0 0 0 5px;"><?php echo $langs->trans("IdProduct"); ?></th>
                          <th style="width:100px"><?php echo $langs->trans("Código"); ?></th>
                          <th style="width:70px"><?php echo $langs->trans("Stock"); ?></th>
                          <th style="text-align:left; padding:0 0 0 5px;"><input type="checkbox" onclick="ls_tpv_switch_all_checkboxes();" id="ls_tpv_checkall" /> <?php echo $langs->trans("Product"); ?></th>
                          <th style="text-align:left; padding:0 0 0 5px;"><?php echo "Estado V."; ?></th>

                          <th style="width:100px"><?php echo $langs->trans("Price"); ?></th>
                          <th style="width:70px">% <?php echo $langs->trans("Dct"); ?></th>
                          <th style="width:100px"><?php echo $langs->trans("Precio c/Desc."); ?></th>

                          <th style="width:70px"><?php echo $langs->trans("Units"); ?></th>
                          <th style="width:70px"><?php echo $langs->trans("U. Ent."); ?></th>
                          <th style="width:100px;"><?php echo $langs->trans("Total"); ?></th>
                          <th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
		              </tr>
		            </thead>
					<tbody id="listado_productos_ticket" style="overflow:scroll">
					 </tbody>
		          </table>

                  <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
				
            </div>
            </div>
                <div id="ticketLeft">


                    <div style="">
                        <div class="clearfix tabContainer2" id="info_product">
                            <div id="product-right-column" style="padding: 10px; color: #fff">
                                <div>
                                    <img width="90px;" onClick="mostrar2()" height="90px;" style=" float: left; padding-right: 8px; padding-bottom: 8px;" width="100%" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
                                </div>
                                <div class="label">
                                    <span id="our_label_display" style="font-size: 20px !important;"> </span>
                                </div>
                                <div class="price">
	                            <span class="our_price_display" >
	                                <span id="our_price_display" style="font-size: 28px !important;"> </span><?php echo $langs->trans($conf->currency);?>
	                            </span>
                                </div>
                                <div class="price_min">
	                            <span id="our_price_min" class="our_price_min_display" >
	                                <span id="our_price_min_display" style="font-size: 14px !important;"> </span><?php echo $langs->trans($conf->currency);?>
	                            </span>
                                </div>
                                <div class="">
	                            <span id="delivery_days" class="" >
	                                <span id="delivery_days_display" style="font-size: 12px !important;">Días de Entrega</span>
	                            </span>
                                </div>
                                <a class="btn3d" id="btnHideInfo" style="width:80px; float:right;"><?php echo $langs->trans("More");?></a>
                                <div id="short_description_block">
                                    <p><br><span class="rte align_justify" id="short_description_content" style="font-size: 11px;"><p></p></span></p>
                                </div>


                            </div>
                            <div style="clear:both"></div>
                        </div>
                    </div>

                    <!-- info del producto-->
                    <div id="ticketOptions" class="leftBlock clearfix tabContainer2" style="display:none">
                        <div class="colActions"></div>
                    </div>

                    <div id="products" class="leftBlock"  style="overflow: auto;" ></div>

                    <!-- INFO de datos -->



                    <!--FIN INFO de datos -->
                    <!-- FIN berni -->


                    <div id="idTicketLine" class="leftBlock bloqueOpciones" style="display:none" title="">
                        <div class="options">
                            <ul>
                                <li><label><?php echo $langs->trans("Units"); ?>:</label>
                                    <input onclick="this.select()" type="text" size="6" name="line_quantity" id="line_quantity" value="0"  class="numKeyboard" onkeyup="$('#line_qty_ent').val($(this).val());"></li>
                                <br clear="all" />
                                <li><label><?php echo $langs->trans("U. Entregadas"); ?>:</label>
                                    <input onclick="this.select()" type="text" size="6" name="line_qty_ent" id="line_qty_ent" value="0"  class="numKeyboard"></li>
                                <br clear="all" />
                                <li><label>% <?php echo $langs->trans("Discount"); ?>:</label>
                                    <input onclick="this.select()" type="text" size="5" maxlength="3" name="line_discount" id="line_discount"  value="0" class="numKeyboard"></li>
                                <br clear="all" />
                                <li><label><?php echo $langs->trans("Price"); ?>:</label>
                                    <input onclick="this.select()" type="text" size="6" name="line_price" id="line_price" value="0"  class="numKeyboard"></li>
                                <br clear="all" />
                                <li><label><?php echo $langs->trans("Note"); ?>:</label>
                                    <input onclick="this.select()" type="text" size="6" name="line_note" id="line_note" value=""  class="quertyKeyboard"></li>
                                <br clear="all" />
                            </ul>
                            <input type="button" id="id_btn_editTicketline" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">

                        </div>
                    </div>
                    <div id="sustitutes" class="leftBlock bloqueOpciones" style="display:none" title="">
                        <div class="options">
                            <table class="tableList">
                                <thead style="background: -webkit-gradient(linear, left top, left bottom, color-stop(0, #A8890E), color-stop(1, #FEEE8F));">
                                <tr class="">
                                    <th style="width:122px; text-align:left; padding:0 0 0 5px;">Referencia</th>
                                    <th style="width:122px; text-align:left; padding:0 0 0 5px;">Etiqueta</th>
                                </tr>
                                </thead>
                                <tbody id="sustituteslist"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="complements" class="leftBlock bloqueOpciones" style="display:none" title="">
                        <div class="options">
                            <table class="tableList">
                                <thead style="background: -webkit-gradient(linear, left top, left bottom, color-stop(0, #A8890E), color-stop(1, #FEEE8F));">
                                <tr class="">
                                    <th style="width:122px; text-align:left; padding:0 0 0 5px;">Referencia</th>
                                    <th style="width:122px; text-align:left; padding:0 0 0 5px;">Etiqueta</th>
                                    <th style="width:122px; text-align:left; padding:0 0 0 5px;">Cantidad</th>
                                </tr>
                                </thead>
                                <tbody id="complementslist"></tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    if($user->rights->pos->cancel_ticket) {?>
                        <div id = "CancelSquare" class="leftBlock bloqueOpciones" style = "display:none;" title = "" >
                            <div class="options">
                                <input type="button" id="id_btn_cancel_ticket" value="Cancelar Venta" class="btn3dbig">
                            </div>
                        </div >
                    <?php }
                    ?>

                    <div id="payType" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMode");?>">
                        <div class="optionss">
                        	<div id="solo_contado" style="background-color:#f99;color:#900;display:none;font-size:32px;padding: 10px;border-radius: 20px 20px 0 0;text-align: center;border: 1px solid #900;">
							Sólo contado							
							</div>
                            <div>
                                <?php
                                $payments = POS::select_Type_Payments();
                                if(sizeof($payments))
                                {
                                    $i=0;
                                    while($i < sizeof($payments))
                                        //foreach($payments as $payment)
                                    {
                                        //echo "<div class='payment_types'><a class='btn3dbig' id='paytype".$payment['id']."' style='height:40px;'>".$payment['label']."</a></div>";
                                        if ($i<4)
                                        {
                                        echo '<div class="payment_types"><label>'.$payments[$i]['label'].'</label><input style="height:35px; width:60%; border-radius:6px;font-size:25px;" onclick="this.select()" type="text" value="" name="pay_client_'.$i.'" id="pay_client_'.$i.'" class="numKeyboard">';
                                        echo '<input type="button" id="pay_all_'.$i.'" value="'.$langs->trans("Remainder").'" class="chk3d	"></div>';
                                        }
                                        else
                                        {
                                        echo '<div class="payment_types"><label>'.$payments[$i]['label'].'</label><input style="margin-right:40px;height:35px; width:60%; border-radius:6px;font-size:25px;" onclick="this.select()" type="hiden" value="" name="pay_client_'.$i.'" id="pay_client_'.$i.'" class="numKeyboard" readonly="readonly">';
                                        echo '</div>';
                                        }
                                        $i++;
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?php if($conf->global->REWARDS_POS){?>
                            <div id="payment_points" class="payment_options">
                                <div id="points_div">
                                    <label><?php echo $langs->trans("Points");?></label>
                                    <div class="points_total"></div><div id=eur ><span class="points_money"></span><?php echo $conf->currency ?></div>
                                    <label><?php echo $langs->trans("UsePoints");?></label>
                                    <div class="points_client">
                                        <input onclick="this.select()" type="text" value="" name="points_client_id" id="points_client_id" class="numKeyboard">
                                    </div>
                                </div>
                                <!--  <label><?php echo $langs->trans("Total");?></label>
					<div class="payment_total"></div>-->
                                <label ><?php echo $langs->trans("CustomerRet");?></label>
                                <div class="payment_return"></div>

                            </div>
                        <?php }?>

                        <div id="payment_total_points" class="payment_options">


                            <!--  <label><?php echo $langs->trans("Total");?></label>
				<div  class="payment_total"></div>-->

                            <label id="label_add_ticket""><?php echo $langs->trans("CustomerRet");?></label>
                            <div class="payment_return"></div>

                        </div>
                        <input type="button" id="id_btn_add_ticket" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig" style="block !mportant">
                         <div id="payment_coupon"><input type="button" id="id_btn_coupon" value="<?php echo $langs->trans("Usar Ticket de Regalo"); ?>" class="btn3dbig">

                        </div>
                   </div>

                    <div id="payTypeRet" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("PaymentMode");?>">
                        <div class="options">
                            <div>
                                <?php
                                $payments = POS::select_Type_Payments();
                                if(sizeof($payments))
                                {
                                    $i=0;
                                    while($i <4)
                                        //foreach($payments as $payment)
                                    {
                                        //echo "<div class='payment_types'><a class='btn3dbig' id='paytype".$payment['id']."' style='height:40px;'>".$payment['label']."</a></div>";
                                        echo '<div class="payment_types"><label>'.$payments[$i]['label'].'</label><input style="height:35px; width:60%; border-radius:6px;font-size:25px;" onclick="this.select()" type="text" value="" name="pay_client_ret_'.$i.'" id="pay_client_ret_'.$i.'" class="numKeyboard">';
                                        echo '<input type="button" id="pay_all_ret_'.$i.'" value="'.$langs->trans("Remainder").'" class="chk3d	"></div>';
                                        $i++;
                                    }
                                }
#                                							echo 	 '<input type="hidden" '
#									.		'value="" '
#									.		'name="pay_client_99" '
#									.		'id="pay_client_99" />';

                                ?>
                            </div>
                        </div>

                        <div id="payment_total_ret" class="payment_options">


                            <label><?php echo $langs->trans("YetUnreturned");?></label>
                            <div class="payment_return_ret"></div>

                        </div>
                        <input type="button" id="id_btn_add_ticket_ret" value="<?php echo $langs->trans("DoPaymentBack"); ?>" class="btn3dbig">
                        <div id="convert_coupon"><input type="button" id="id_btn_add_ticket_desc" value="<?php echo $langs->trans("ConvertToReduc"); ?>" class="btn3dbig"></div>
                    </div>

                    <div id="idFactureMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Facture"); ?>">
                        <div class="options">
                            <div>
                                <?php if($conf->global->POS_TICKET) {?>
                                    <input type="button" id="id_btn_ticketPay" value="<?php echo $langs->trans("Ticket"); ?>" class="btn3dbig">
                                <?php } if($conf->global->POS_FACTURE) {?>
                                    <!-- <input type="button" id="id_btn_facsimPay" value="<?php echo $langs->trans("Facturesim"); ?>" class="btn3dbig">
					<input type="button" id="id_btn_facturePay" value="<?php echo $langs->trans("Facture"); ?>" class="btn3dbig"> -->
                                <?php }?>
                            </div>
                        </div>
                    </div>

                    <div id="idReturnMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Facture"); ?>">
                        <div class="options">
                            <div>
                                <?php if($conf->global->POS_TICKET) {?>
                                    <input type="button" id="id_btn_ticketRet" value="<?php echo $langs->trans("Ticket"); ?>" class="btn3dbig">
                                <?php } if($conf->global->POS_FACTURE) {?>
                                    <input type="button" id="id_btn_facsimRet" value="<?php echo $langs->trans("Facturesim"); ?>" class="btn3dbig">
                                    <input type="button" id="id_btn_factureRet" value="<?php echo $langs->trans("Facture"); ?>" class="btn3dbig">
                                <?php }?>
                            </div>
                        </div>
                    </div>

                    <div id="idTicketMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Ticket"); ?>">
                        <div class="options">
                            <div>
                                <?php if($conf->global->POS_PRINT) {?>
                                    <input type="checkbox" id="id_cb_ticketPrint" name="id_cb_ticketPrint" class="chk3d">
                                    <label style="float:left"><?php echo $langs->trans("GiftTicket"); ?></label>
                                    <input type="button" id="id_btn_ticketPrint" value="<?php echo $langs->trans("PrintTicket"); ?>" class="btn3dbig">
                                <?php } if($conf->global->POS_MAIL) {?>
                                    <input type="button" id="id_btn_ticketMail" value="<?php echo $langs->trans("SendTicket"); ?>" class="btn3dbig">
                                <?php }?>
                            </div>
                        </div>
                    </div>
                    <div id="idCashMode" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("CloseCash"); ?>">
                        <div class="options">
                            <div>
                                <?php if($conf->global->POS_PRINT) {?>
                                    <input type="button" id="id_btn_cashPrint" value="<?php echo $langs->trans("PrintCloseCash"); ?>" class="btn3dbig">
                                <?php } if($conf->global->POS_MAIL) {?>
                                    <input type="button" id="id_btn_cashMail" value="<?php echo $langs->trans("SendCloseCash"); ?>" class="btn3dbig">
                                <?php }?>
                            </div>
                        </div>
                    </div>


                    <div id="idDiscount" class="leftBlock bloqueOpciones" style="display:none" title="<?php echo $langs->trans("ApplyDiscount"); ?>">
                        <div class="options">

                            <!-- <div class='btnselect type_discount btnon'><a id='btnTypeDiscount0'><?php echo $langs->trans("Percent");?></a></div>
					<div class='btnselect type_discount'><a id='btnTypeDiscount1'><?php echo $langs->trans("Quantity");?></a></div>-->
                            <ul>
                                <li><div id="typeDiscount0">
                                        <label><?php echo $langs->trans("Percent"); ?></label><input onclick="this.select()" type="text" size="5" maxlength="2" name="ticket_discount_perc" id="ticket_discount_perc"  value="0" class="numKeyboard" />
                                    </div>
                                    <!-- <div id="typeDiscount1" style="display:none">
						<label><?php echo $langs->trans("Quantity"); ?>:</label><input type="text" size="6" name="ticket_discount_qty" id="ticket_discount_qty" value="0"  class="numKeyboard" />
					 </div>-->

                                    <input type="button" id="id_btn_add_discount" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig">
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
		</div>

        
		<div id="tab-center-2" class="no-top no-border no-padding no-scrollbar ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="position: absolute; top: 0px !important; bottom: 0pt; left: 0pt; right: 0pt; margin-top:0px !important;">
			<div class="ui-layout-center no-scrollbar">
				<div class="topSearch">
					<label><?php echo $langs->trans("Information"); ?></label>
				</div>
				
            	<div class="bottom_search">
					<div class="clearfix" id="info_product">
						
                        <div id="product-right-column">
                            <img height="200" onClick="mostrar2()" width="200" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
							<h1><?php echo $langs->trans("SelectProduct"); ?></h1>
                            <div id="short_description_block">
								<div class="rte align_justify" id="short_description_content"><p><?php echo $langs->trans("NoDescription"); ?></p></div>
							</div>
                            
							<p class="price" >
								<span class="our_price_display" >
									<span id="our_price_display" ><?php echo $langs->trans("00,00"); ?></span>ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬
                        		</span>
							</p>
                        	<p id="quantity_wanted_p">
                        		<label><?php echo $langs->trans("Quantity"); ?></label>
								<input onclick="this.select()" type="text" maxlength="3" size="2" value="1" class="numKeyboard" id="id_product_quantity" name="qty">
							</p>
							<p class="buttons_bottom_block" id="add_to_cart">
								<input type="button"  class="addCart" value="<?php echo $langs->trans("AddToTicket"); ?>" name="btnAddProductCart" id="btnAddProductCart">
                       		</p>
						</div>
               		<div style="clear:both"></div>
                    </div>	
            	</div>
        	</div>
		</div>
		
        <div id="tab-center-3" class="no-padding no-scrollbar ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="position: absolute; top: 0pt; bottom: 0pt; left: 0pt; right: 0pt; margin-top:0px !important;">
			<div id="customerSearch" class="topSearch grey" style="height:60px;padding:8px;">
				   <div class="but">
              		<img id="btnAddCustomer" title="<?php echo $langs->trans("NewCustomer"); ?>" name="btnAddProduct" src="./img/new_customer.png" height="38" >
               		<!--<span class="text"><?php echo $langs->trans("New"); ?></span>-->
              	</div>
              	 <div class="code">
				<label><?php echo $langs->trans("Search"); ?></label>
				<input onclick="this.select()" type="text"  size=10 name="id_customer_search" id="id_customer_search"></input>
				</div>  
			</div>
			<table id="customerTable" class="tableList">
				<thead>
					<tr>
						<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th><?php echo $langs->transcountry('ProfId1',$mysoc->country_code); ?></th>
						<th><?php echo $langs->trans("Name"); ?></th>
						<th><?php echo $langs->trans("Address"); ?></th>
						<th><?php echo $langs->trans("Tel."); ?></th>
						<th><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
					</thead>
				<tbody></tbody>
			</table>
            
             <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		
        </div>

		<div id="history" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
        	
            <!-- berni -->
            <!-- INFO datos-->
            <div id="historyLeft" style="width:100%;"> 
         		 <!-- info del producto-->
	            <div id="historyOptions" class="leftBlock clearfix tabContainer2" style="display:none">
	                <input type="hidden" id="historyTicketSelected" value="">
	            	<div class="colActions"></div>
	            </div> 
	             <div class="tabContainer0" style="display:block;width:100%;height:55px;whitespace:nowrap;">  
	             <div style="float:left;"><img  title="Filtrado" src="./img/calendar.png" width="23" style="margin-left:6px;margin-top:10px;margin-right:4px;"></div>
	             
                <div onclick="_TPV.searchByRef(100);" class="botonStats" align="center" title=" "   >
                <span><?php echo $langs->trans("Today")?> </span>
                <span id="histToday"  style="font-size:22px">0 </span>
                </div> 
                <div onclick="_TPV.searchByRef(101);" class="botonStats" align="center" title=" " >
                    <span><?php echo $langs->trans("Yesterday")?> </span>
                    <span id="histYesterday" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRef(102);" class="botonStats" align="center" title=" "   >
                    <span> <?php echo $langs->trans("ThisWeek")?></span>     
                    <span  id="histThisWeek" style="font-size:22px">  0   </span>

                </div> 
                <div onclick="_TPV.searchByRef(103);" class="botonStats" align="center" title=" " >
                   <span> <?php echo $langs->trans("LastWeek")?> </span>
                    <span id="histLastWeek" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRef(104);" class="botonStats" align="center" title=" "   >
                    <span> <?php echo $langs->trans("TwoWeeksAgo")?></span>
                     <span  id="histTwoWeeks" style="font-size:22px">  0   </span>

       
                </div> 
                <div onclick="_TPV.searchByRef(105);" class="botonStats" align="center" title=" " >
  					<span> <?php echo $langs->trans("ThreeWeeksAgo")?></span>                    
                    <span id="histThreeWeeks" style="font-size:22px">0  </span>

                </div><div onclick="_TPV.searchByRef(106);" class="botonStats" align="center" title=" "   >
		           <span> <?php echo $langs->trans("ThisMonth")?></span>
                   <span  id="histThisMonth" style="font-size:22px">  0   </span>
      
                </div>
                 <div onclick="_TPV.searchByRef(107);" class="botonStats" align="center" title=" "   >
                 	<span> <?php echo $langs->trans("OneMonthAgo")?></span>
                    <span  id="histOneMonth" style="font-size:22px">0</span>
	
         
                </div> 
                <div onclick="_TPV.searchByRef(108);" class="botonStats" align="center" title=" " >
                <span> <?php echo $langs->trans("LastMonth")?> </span>                      
                 <span id="histLastMonth" style="font-size:22px">0</span>
    
                </div>
             </div>
                 
            </div>
            
            <!-- FIN INFO Datos -->
            <!-- berni -->
        

        	<div id="historyRight">
       <div class="grey">			
			<div id="refSearch" class="topSearch tabContainer1" style="height:40px;padding:8px;display:inline-table;">
				<!-- <label><?php echo $langs->trans("Search"); ?></label> -->	
				 
                <img id="img_ref_search" class="search_but" src="./img/search_ticket.png"  height="40px" style="float:left;">
            	<input onclick="this.select()" type="text" size=10 name="id_ref_search" id="id_ref_search"></input>
                <select id="type_searchbyref">
                    <option value="ref">Referencia</option>
                    <option value="terminal">Terminal</option>
                    <option value="seller">Usario</option>
                    <option value="client">Cliente</option>
                </select>
            </div>
			 <div id="historyTypes" >
                     <div id="legend" class="legend" >
                     	
                       <a class="icontype state0"  onclick="_TPV.searchByRef(0);"><?php echo $langs->trans('StatusTicketDraft');?></a> 
                        <a class="icontype state1" onclick="_TPV.searchByRef(1);"><?php echo $langs->trans('StatusTicketClosed');?></a>
                        <a class="icontype state2" onclick="_TPV.searchByRef(2);"><?php echo $langs->trans('StatusTicketProcessed');?></a>
                        <a class="icontype state3" onclick="_TPV.searchByRef(3);"><?php echo $langs->trans('StatusTicketCanceled');?></a>
                        <a class="icontype state1 type1" onclick="_TPV.searchByRef(4);"><?php echo $langs->trans('StatusTicketReturned');?></a>
                        <a class="icontype state0" style="background-color: green;" onclick="_TPV.searchByRef(1000);"><?php echo $langs->trans('Por Surtir');?></a>
                        <a class="icontype state6" style="background-color: #999;" onclick="_TPV.searchByRef(110);"><?php echo $langs->trans('Listo P/Entregar');?></a>


     
                    </div>
                </div>
	</div>	                
			<div id="historyContainer">
			<table id="historyTable" class="tableList">
				
				<thead>
					<tr>
						<th colspan="2"><?php echo $langs->trans("Reference"); ?></th>
						<th><?php echo $langs->trans("Date"); ?></th>
						<th><?php echo $langs->trans("Terminal"); ?></th>
						<th><?php echo $langs->trans("User"); ?></th>
						<th><?php echo $langs->trans("Customer"); ?></th>
						
						<th><?php echo $langs->trans("Total"); ?></th>
						<th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
				</thead>
				<tbody></tbody>
			</table>
			</div>
            </div>
      
            
             <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		</div>
		
		<div id="historyFac" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
        	
            <!-- berni -->
            <!-- INFO datos-->
            <div id="historyFacLeft" style="width:100%;"> 
         		 <!-- info del producto-->
	            <div id="historyFacOptions" class="leftBlock clearfix tabContainer2" style="display:none">
	                <input type="hidden" id="historyFacTicketSelected" value="">
	            	<div class="colActions"></div>
	            </div> 
	             <div class="tabContainer0" style="display:block;width:100%;height:55px;whitespace:nowrap;">  
	             <div style="float:left;"><img  title="Filtrado" src="./img/calendar.png" width="23" style="margin-left:6px;margin-top:10px;margin-right:4px;"></div>
	            
	            <div onclick="_TPV.searchByRefFac(100);" class="botonStats" align="center" title=" "  >
                    <span ><?php echo $langs->trans("Today")?> </span>
                    <span id="histFacToday"  style="font-size:22px">  0   </span>
                </div>
           
                <div onclick="_TPV.searchByRefFac(101);" class="botonStats" align="center" title=" " >
                     <span><?php echo $langs->trans("Yesterday")?> </span>
                    <span id="histFacYesterday" style="font-size:22px">0  </span>
                </div>
               
                <div onclick="_TPV.searchByRefFac(102);" class="botonStats" align="center" title=" "  >
                   <span> <?php echo $langs->trans("ThisWeek")?></span>
                    <span  id="histFacThisWeek" style="font-size:22px">  0   </span>
           		</div> 
                <div onclick="_TPV.searchByRefFac(103);" class="botonStats" align="center" title=" " >
                    <span> <?php echo $langs->trans("LastWeek")?> </span>
                    <span id="histFacLastWeek" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRefFac(104);" class="botonStats" align="center" title=" "  >
                     <span> <?php echo $langs->trans("TwoWeeksAgo")?></span>
                    <span  id="histFacTwoWeeks" style="font-size:22px">  0   </span>
	            </div> 
                <div onclick="_TPV.searchByRefFac(105);" class="botonStats" align="center" title=" " >
                     <span> <?php echo $langs->trans("ThreeWeeksAgo")?></span>
                    <span id="histFacThreeWeeks" style="font-size:22px">0  </span>
                </div>
                <div onclick="_TPV.searchByRefFac(106);" class="botonStats" align="center" title=" "  >
                     <span> <?php echo $langs->trans("ThisMonth")?></span>
                    <span  id="histFacThisMonth" style="font-size:22px">  0   </span>
                </div>
                 <div onclick="_TPV.searchByRefFac(107);" class="botonStats" align="center" title=" "  >
                    <span> <?php echo $langs->trans("OneMonthAgo")?></span>
                    <span  id="histFacOneMonth" style="font-size:22px">  0   </span>
                </div> 
                <div onclick="_TPV.searchByRefFac(108);" class="botonStats" align="center" title=" " >
                   <span> <?php echo $langs->trans("LastMonth")?> </span>
                   <span id="histFacLastMonth" style="font-size:22px">0  </span>
                </div>
             </div>
                 
            </div>
            
            <!-- FIN INFO Datos -->
            <!-- berni -->
        
        	<div id="historyFacRight">
			 <div class="grey">
			<div id="refFacSearch" class="topSearch tabContainer1" style="height:40px;padding:8px;">
				<!-- <label><?php echo $langs->trans("Search"); ?></label> -->	
				<img id="img_ref_fac_search" class="search_but" src="./img/search_ticket.png"  height="40px" style="float:left;">
                <input onclick="this.select()" type="text" size=10 name="id_ref_fac_search" id="id_ref_fac_search"></input>
			</div>
			 <div id="historyFacTypes" >
                     <div id="legendFac" class="legend" >
                        <a class="icontype state0"  onclick="_TPV.searchByRefFac(0);" ><?php echo $langs->trans('BillStatusDraft');?></a> 
                        <a class="icontype state1" onclick="_TPV.searchByRefFac(1);" ><?php echo $langs->trans('BillStatusValidated');?></a>
                        <a class="icontype state2" onclick="_TPV.searchByRefFac(2);" ><?php echo $langs->trans('BillStatusPaid');?></a>
                        <a class="icontype state3" onclick="_TPV.searchByRefFac(3);" ><?php echo $langs->trans('BillStatusCanceled');?></a>
                        <a class="icontype state1 type1" onclick="_TPV.searchByRefFac(4);"><?php echo $langs->trans('StatusTicketReturned');?></a>
                      
     
                    </div>
                </div>
                </div>
			<div id="historyFacContainer">
			<table id="historyFacTable" class="tableList">
				
				<thead>
					<tr>
						<th><?php echo $langs->trans("Reference"); ?></th>
						<th><?php echo $langs->trans("Date"); ?></th>
						<th><?php echo $langs->trans("Terminal"); ?></th>
						<th><?php echo $langs->trans("User"); ?></th>
						<th><?php echo $langs->trans("Customer"); ?></th>
						
						<th><?php echo $langs->trans("Total"); ?></th>
						<th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
				</thead>
				<tbody></tbody>
			</table>
			</div>
            </div>
      
      		      
             <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		</div>
		
		<div id="almacen" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
		
		
			
              <!-- merla -->
        <!-- INFO datos-->
        <!--<div id="ticketLeft">
      		<div id="products" class="leftBlock"  style="overflow: auto;" ></div> 
        	<div style="">
	            <div class="clearfix tabContainer2" id="info_product_st">
	            	<div id="product-right-column" style="padding: 10px; color: #fff">
	                    <div>
	                    <img width="90px;" onClick="mostrar2()" height="90px;" style="border-radius: 20px 15px 20px 20px; float: left; padding-right: 8px; padding-bottom: 8px;" width="100%" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
	                    </div>
	                    <div class="label">
	                        <span id="our_label_display_st" style="font-size: 20px !important;"> </span>
	                    </div>
	                     <div class="price">
	                        <span class="our_price_display" >
	                            <span id="our_price_display_st" style="font-size: 28px !important;"> </span><?php echo $langs->trans($conf->currency);?> 
	                        </span>
	                    </div>
	                    <div class="price_min">
	                        <span id="our_price_min_st" class="our_price_min_display" >
	                            <span id="our_price_min_display_st" style="font-size: 14px !important;"> </span><?php echo $langs->trans($conf->currency);?> 
	                        </span>
	                    </div>
	                    <a class="btn3d" id="btnHideInfoSt" style="float: right; width: 80px;"><?php echo $langs->trans("More");?> </a>
	                    <div id="short_description_block_st">
	                        <p><br><span class="rte align_justify" id="short_description_content_st" style="font-size: 11px;"></span></p>
	                    </div>
	                    
	                    
					</div>
	            	<div style="clear:both"></div>
	            </div>
            </div>	
             
            
       <div>    
              <div id="stockOptions" class="leftBlock clearfix tabContainer2" style="display:none">
	                <input type="hidden" id="stockSelected" value="">
	            	<div class="colActions"></div>
	            </div> 
                <div onclick="_TPV.searchByStock(-1,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('NoSell')?></span>
                    <span id="stockNoSell" style="font-size:22px">0</span>
                </div>
                
                <div onclick="_TPV.searchByStock(-2,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%" >
                    <span ><?php echo $langs->trans('Sell')?></span>
                    <span id="stockSell" style="font-size:22px">0</span>
                </div> 
                 
                <div onclick="_TPV.searchByStock(-3,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('WithStock')?></span>
                    <span id="stockWith" style="font-size:22px">0</span>
                </div> 
                
                <div onclick="_TPV.searchByStock(-4,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('NoStock')?></span>
                    <span id="stockWithout" style="font-size:22px">0</span>
                </div> 
                
                <div onclick="_TPV.searchByStock(-5,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('BestSell')?></span>
                    <span id="stockBest" style="font-size:22px">0</span>
                </div> 
                
                <div onclick="_TPV.searchByStock(-6,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                    <span ><?php echo $langs->trans('WorstSell')?></span>
                    <span id="stockWorst" style="font-size:22px">0</span>
                </div>
                <?php 
       		
       		$list = array();
       		$list = POS::getWarehouse();
       		$num = count($list);
       		$i=0;
       		$warehouse = new Entrepot($db);
       		while($i < $num){
				$warehouse->fetch($list[$i]['id']);
				$ret = $warehouse->nb_products();
       ?>
                <div onclick="_TPV.searchByStock(1,<?php echo $list[$i]['id']?>);" class="botonStats" align="center" title=" " style="width: 48%">
                   <span><?php echo $warehouse->libelle;?></span>
                    <span   style="font-size:22px"><?php echo $ret['nb'];?> </span>
                </div> 
                <?php $i++;}?>
               <div id="sustituteProd" class="botonStats" align="center" title=" " style="display:none;width: 48%;height: 30%;" >
                   <span> <?php echo $langs->trans("Sustitutos")?> </span>
                   <span id="numbersustitute" style="font-size:22px">0</span>

               </div>
               <div id="ComplementProd" class="botonStats" align="center" title=" " style="display:none;width: 48%;height: 30%;" >
                   <span> <?php echo $langs->trans("Complementos")?> </span>
                   <span id="numbercomplementos" style="font-size:22px">0</span>

               </div>
             </div>
             </div>-->
        
        <!-- FIN INFO Datos -->
        <!-- berni -->
            
            
            
			
       <div id="ticketRight"> 
			<div id="stockSearch" class="topSearch tabContainer1" style="width:100%; height:60px;padding:8px;" >
			<div class="but" >
              		 <img height="38" id="btnAddProduct" title="<?php echo $langs->trans("AddProductTicket"); ?>" name="btnAddProduct" src="./img/add_product.png">
              		 
               		<!-- <span class="text"><?php echo $langs->trans("NewProd"); ?></span>-->
            </div>	
            <div class="inputs"  >
            <!-- 
            <label><?php echo $langs->trans("Search"); ?></label>
			-->
			<img id="img_stock_search" class="search_but" src="./img/search_prod.png" height="40px" style="float:left; margin-right:5px;"  >
			<input onclick="this.select()" type="text" size=10 name="id_stock_search" id="id_stock_search"></input>
			</div> 
			
			
			      
			</div>
            <div>
			<table id="storeTable" class="tableList" style="clear:both;" >
				<thead>
					<tr>
						<th>Id</th>
						<th><?php echo $langs->trans("Reference"); ?></th>
						<th><?php echo $langs->trans("Name"); ?></th>
						<th style="cursor:pointer;" onclick="_TPV.sortTable(3,'int');"><?php echo $langs->trans("Matriz"); ?> <span id="upM" class="fas fa-chevron-up "></span><span id="downM" class="fas fa-chevron-down" style="display: none;"></span></th>
						<th style="cursor:pointer;" onclick="_TPV.sortTable(4,'int');"><?php echo $langs->trans("GPE"); ?> <span id="upG" class="fas fa-chevron-up "></span><span id="downG" class="fas fa-chevron-down" style="display: none;"></span></th>
						<th><?php echo $langs->trans("Supplier"); ?></th>
						<th style="display:none"><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
				</thead>
				<tbody>	</tbody>	
				</table>
            </div>
                
                 <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
		</div>
        <!-- berni -->
        <!-- INFO datos-->
        <div id="ticketLeft">
                <div id="products" class="leftBlock"  style="overflow: auto;" ></div>
                <div style="">
                    <div class="clearfix tabContainer2" id="info_product_st">
                        <div id="product-right-column" style="padding: 10px; color: #fff">
                            <div>
                                <img width="90px;" onClick="mostrar2()" height="90px;" style="border-radius: 20px 15px 20px 20px; float: left; padding-right: 8px; padding-bottom: 8px;" width="100%" id="bigpic" alt="" title="<?php echo $langs->trans("Product"); ?>" src="" style="display: inline;">
                            </div>
                            <div class="label">
                                <span id="our_label_display_st" style="font-size: 20px !important;"> </span>
                            </div>
                            <div class="price">
	                        <span class="our_price_display" >
	                            <span id="our_price_display_st" style="font-size: 28px !important;"> </span><?php echo $langs->trans($conf->currency);?>
	                        </span>
                            </div>
                            <div class="price_min">
	                        <span id="our_price_min_st" class="our_price_min_display" >
	                            <span id="our_price_min_display_st" style="font-size: 14px !important;"> </span><?php echo $langs->trans($conf->currency);?>
	                        </span>
                            </div>
                            <a class="btn3d" id="btnHideInfoSt" style="float: right; width: 80px;"><?php echo $langs->trans("More");?> </a>
                            <div id="short_description_block_st">
                                <p><br><span class="rte align_justify" id="short_description_content_st" style="font-size: 11px;"></span></p>
                            </div>


                        </div>
                        <div style="clear:both"></div>
                    </div>
                </div>


                <div>
                    <div id="stockOptions" class="leftBlock clearfix tabContainer2" style="display:none">
                        <input type="hidden" id="stockSelected" value="">
                        <div class="colActions"></div>
                    </div>
                    <div onclick="_TPV.searchByStock(-1,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                        <span ><?php echo $langs->trans('NoSell')?></span>
                        <span id="stockNoSell" style="font-size:22px">0</span>
                    </div>

                    <div onclick="_TPV.searchByStock(-2,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%" >
                        <span ><?php echo $langs->trans('Sell')?></span>
                        <span id="stockSell" style="font-size:22px">0</span>
                    </div>

                    <div onclick="_TPV.searchByStock(-3,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                        <span ><?php echo $langs->trans('WithStock')?></span>
                        <span id="stockWith" style="font-size:22px">0</span>
                    </div>

                    <div onclick="_TPV.searchByStock(-4,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                        <span ><?php echo $langs->trans('NoStock')?></span>
                        <span id="stockWithout" style="font-size:22px">0</span>
                    </div>

                    <div onclick="_TPV.searchByStock(-5,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                        <span ><?php echo $langs->trans('BestSell')?></span>
                        <span id="stockBest" style="font-size:22px">0</span>
                    </div>

                    <div onclick="_TPV.searchByStock(-6,_TPV.warehouseId);" class="botonStats" align="center" title=" " style="width: 48%">
                        <span ><?php echo $langs->trans('WorstSell')?></span>
                        <span id="stockWorst" style="font-size:22px">0</span>
                    </div>
                    <?php

                    $list = array();
                    $list = POS::getWarehouse();
                    $num = count($list);
                    $i=0;
                    $warehouse = new Entrepot($db);
                    while($i < $num){
                        $warehouse->fetch($list[$i]['id']);
                        $ret = $warehouse->nb_products();
                        ?>
                        <div onclick="_TPV.searchByStock(1,<?php echo $list[$i]['id']?>);" class="botonStats" align="center" title=" " style="width: 48%">
                            <span><?php echo $warehouse->libelle;?></span>
                            <span   style="font-size:22px"><?php echo $ret['nb'];?> </span>
                        </div>
                        <?php $i++;}?>
                    <div id="sustituteProd" class="botonStats" align="center" title=" " style="display:none;width: 48%;height: 30%;" >
                        <span> <?php echo $langs->trans("Sustitutos")?> </span>
                        <span id="numbersustitute" style="font-size:22px">0</span>

                    </div>
                    <div id="ComplementProd" class="botonStats" align="center" title=" " style="display:none;width: 48%;height: 30%;" >
                        <span> <?php echo $langs->trans("Complementos")?> </span>
                        <span id="numbercomplementos" style="font-size:22px">0</span>

                    </div>
                </div>
            </div>
        <!-- FIN INFO Datos -->
        <!-- berni -->
		</div>
		<div id="places" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
			<div id="placeTable"></div>
            <div class="go_up"><a class="grey" id="top" title="" target="_self"><?php echo $langs->trans("Up"); ?></a></div>
				
		
		
		</div>
		<!--  <div id="tab-dashboard" class="outline ui-tabs-panel ui-widget-content ui-corner-bottom ui-tabs-hide" style="margin-top:0px !important;">
			
			 <table cellpadding="10px;" cellspacing="10px;" width="100%">
			 	<tr valign="top">
			 		<td width="400px;">
			 			
			 			 <center><h2 style="background-color: #555;">Dashboard</h2></center>
			 			 <!-- dashboard 
			 			 	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
			 			    <script type="text/javascript">
						      google.load('visualization', '1', {packages: ['gauge']});
						    </script>
						    <script type="text/javascript">
						      function drawVisualization() {
						        // Create and populate the data table.
						        var data = google.visualization.arrayToDataTable([
						          ['Label', 'Value'],
						          ['Memory', 80],
						          ['CPU', 55],
						          ['Network', 68]
						        ]);
						      
						        // Create and draw the visualization.
						        new google.visualization.Gauge(document.getElementById('visualization2')).
						            draw(data);
						      }
						      
						
						      google.setOnLoadCallback(drawVisualization);
						    </script> 
			 			 <center>
			 			     <div id="visualization2" style="width: 400px; height: 140px;"></div>
			 			 </center>
			 			
			 			
			 			<!-- grÃƒÂ¯Ã‚Â¿Ã‚Â½fico 
			 			
			 			 <script type="text/javascript" src="http://www.google.com/jsapi"></script>
						    <script type="text/javascript">
						      google.load('visualization', '1');
						    </script>
						    <script type="text/javascript">
						      function drawVisualization() {
						        var wrapper = new google.visualization.ChartWrapper({
						          chartType: 'ColumnChart',
						          dataTable: [['', 'Germany', 'USA', 'Brazil', 'Canada', 'France', 'RU'],
						                      ['', 700, 300, 400, 500, 600, 800]],
						          options: {'title': 'Countries'},
						          containerId: 'visualization'
						        });
						        wrapper.draw();
						      }
						      
						      
						
						      google.setOnLoadCallback(drawVisualization);
						    </script>
						 <center>
			 			 <div id="visualization" style="width: 400px; height: 200px;"></div>
			 			 </center>
			 			 
			 			 <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
			 			 
			 		</td>
			 		<td width="29,33%" >
			 			<div style="margin-left: 10px;">
						<center><h2 style="background-color: #555;">Dashboard</h2></center>			 			
						<div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius:20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
			 			<div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                
			 			</div>
			 		</td>
			 		<td >
			 			<center><h2 style="background-color: #555;">Dashboard</h2></center>
			 			<div style="margin-left: 10px;">
			 			<div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#389f1d !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		                <div  class="botonStats" align="center" title=" "  style="background:#ff0000 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;" >
		                    <div align="center"  >
		                    <span   style="font-size:22px">  1232,32   </span>
		                    <br/>
		                    <span>Efectivo</span>
		                    </div>
		           
		                </div> 
		                <div  class="botonStats" align="center" title=" " style="background:#e5d726 !important; border-radius: 20px 20px 20px 20px; padding-top: 10px;">
		                    <div align="center">
		                    <span  style="font-size:22px">8  </span>
		                    <br/>
		                    <span>Tickets sin cerrar </span>
		                    </div>
		                </div>
		              </div>
			 		</td>
			 	</tr>
			 </table>
			 
			  
		</div>-->

	<div id="buttomTicket"  class="ui-layout-south ui-widget-content ui-corner-bottom no-scrollbar ui-layout-pane">
								
	</div>
	<!-- /centerTabsLayout--> 

</div>
<div id="showpanels" style="display:none">
		
		<div id="idEmployee" class="bloqueOpciones" title="<?php echo $langs->trans("Employees");?>" style="display:none;">
			<div class="options">
			<?php 	
				$users = POS::select_Users();
				foreach($users as $user) 
				{
					echo "<div class='btnselect'><a id='employeetype".$user['code']."' photo='".$user['photo']."' login='".$user['login']."'>".$user['label']."</a></div>";
				}
			?>	
			</div>	
		</div>
		
		<div id="idEmpPass" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Password"); ?>">
			<div class="options">
				<ul>
					<li><label> <?php echo $langs->trans("Password"); ?>:</label><input style="width:175px;" onclick="this.select()" type="password" size="5" maxlength="40" name="password" id="password"  value="" class="quertyKeyboard">
					<br clear="all" />
					<input type="button" id="id_btn_empPass" value="<?php echo $langs->trans("Send"); ?>" class="btn3dbig"></li>
				</ul>		
			</div>	
		</div>
								
	
								
		<div id="idPanelInfo" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Info"); ?>">
			<div class="options">
				<div id="infoInfo" style="margin-top: 23px; margin-left: 15%;">
					<img src="./img/ok.png" style="float:left;">
					<span id="infoText"></span>
				</div>		
			</div>	
		</div>
		
		<div id="idPanelError" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Error"); ?>">
			<div class="options">
				<div id="infoError" style="margin-top: 23px; margin-left: 15%;">
					<img src="./img/error.png" style="float:left;">
					<span id="errorText"></span>
				</div>		
			</div>	
		</div>
								
		
		
		<!-- Mis cosicas para enviar por mail -->
		<div id="idSendMail" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("SendMail"); ?>">
			<div class="options">
				<ul>
					<li><label> <?php echo $langs->trans("MailTo"); ?>:</label><input style="width:175px;" onclick="this.select()" type="text" size="5" maxlength="40" name="mail_to" id="mail_to"  value="" class="quertyKeyboard">
					<br clear="all" />
					<input type="button" id="id_btn_ticketLine" value="<?php echo $langs->trans("Send"); ?>" class="btn3dbig"></li>
				</ul>		
			</div>	
		</div>
		
		<div id="ticketNote" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("TicketNote"); ?>">
			<div class="options">
				<ul>
				<li><label><?php echo $langs->trans("Note"); ?></label><input onclick="this.select()" type="text" size="10" name="ticket_note" id="ticket_note"  value="" class="quertyKeyboard" />
				<input type="button" id="id_btn_ticket_note" value="<?php echo $langs->trans("Save"); ?>" class="btn3dbig"></li>
				</ul>	
			</div>	
		</div>
		
		<div id="idTicketDelet" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("DeleteTicket"); ?>">
			<div class="options">
				<div>
					<p> <?php echo $langs->trans("ConfirmDeleteTicket"); ?></p>
					<input type="button" id="id_btn_ticketYes" value="<?php echo $langs->trans("Yes"); ?>" class="btn3dbig">
					<input type="button" id="id_btn_ticketNo" value="<?php echo $langs->trans("No"); ?>" class="btn3dbig">
				</div>		
			</div>	
		</div>
		
		<!-- Mis cosicas para enviar por mail -->
		
		<div id="idPanelProduct" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("AddProduct");?>">
			<div class="options">
				<ul>
					<li><label><?php echo $langs->trans("Name");?>:</label><input onclick="this.select()" type="text" name="id_product_name" id="id_product_name" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("Reference");?>:</label><input onclick="this.select()" type="text" name="id_product_ref" id="id_product_ref" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("PricePVP");?>:</label><input onclick="this.select()" type="text" name="id_product_price" class="numKeyboard" id="id_product_price" class=""></li>
					<br clear="all" />
				</ul>
				<div style="margin-left:5%;">	
					<?php 
						$taxes = POS::select_VAT();
						foreach($taxes as $tax) 
						{
							echo "<div class='btnselect btnminiselect tax_types'><a title='".$tax['id']."' id='taxtype".$tax['id']."'>".$tax['label']."</a></div>";
						}
					?>
				</div>
                    <input type="button" id="id_btn_add_product" value="<?php echo $langs->trans("New");?>" class="btn3dbig" onclick="" style="display:inline-block">
						
			</div>	
		</div>
		
		<div id="idClient" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("AddCustomer"); ?>">
			<div class="options">
				<ul>
					<li><label><?php echo $langs->trans("FirstName");?>:</label><input onclick="this.select()" type="text" name="id_customer_name" id="id_customer_name" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("LastName");?>:</label><input onclick="this.select()" type="text" name="id_customer_lastname" id="id_customer_lastname" class=""></li>
                    <br clear="all" />
					<li><label><?php echo $langs->transcountry('ProfId1',$mysoc->country_code);?>:</label><input onclick="this.select()" type="text" name="id_customer_cif" id="id_customer_cif" class=""></li>
					<br clear="all" />
					
					<li>
						<label><?php echo $langs->trans("Calle");?>:</label>
						<input onclick="this.select()" type="text" name="id_customer_address" id="id_customer_address" class="" />
					</li>
					<br clear="all" />
					<li>
						<label><?php echo $langs->trans("No. exterior");?>:</label>
						<input onclick="this.select()" type="text" name="id_customer_outnum" id="id_customer_outnum" class="" />
					</li>
					<br clear="all" />
					<li>
						<label><?php echo $langs->trans("No. interior");?>:</label>
						<input onclick="this.select()" type="text" name="id_customer_innum" id="id_customer_innum" class="" />
					</li>
					<br clear="all" />
					<li>
						<label><?php echo $langs->trans("Colonia");?>:</label>
						<input onclick="this.select()" type="text" name="id_customer_neigh" id="id_customer_neigh" class="" />
					</li>
					<br clear="all" />

					
					<li><label><?php echo $langs->trans("Town");?>:</label><input onclick="this.select()" type="text" name="id_customer_town" id="id_customer_town" class=""></li>
					<br clear="all" />
					<li>
						<label><?php echo $langs->trans("Municipio");?>:</label>
						<input onclick="this.select()" type="text" name="id_customer_county" id="id_customer_county" class="" />
					</li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Zip");?>:</label><input onclick="this.select()" type="text" name="id_customer_zip" id="id_customer_zip" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Phone");?>:</label><input onclick="this.select()" type="text" name="id_customer_phone" id="id_customer_phone" class=""></li>
					<br clear="all" />
					<li><label><?php echo $langs->trans("Email");?>:</label><input onclick="this.select()" type="text" name="id_customer_email" id="id_customer_email" class=""></li>
					<br clear="all" />
                </ul>    
                <input type="button" id="id_btn_add_customer" value="<?php echo $langs->trans("New");?>" class="btn3dbig">
						
			</div>	
		</div>
		<div 
			id="rc_confirm_warehouse_transfer" 
			class="bloqueOpciones" 
			style="display:block;" 
			title="Solicitar Surtido">
			<div class="options">
                <input type="button" id="rc_confirm_warehouse_transfer_button" value="Aceptar" class="btn3dbig" />
			</div>	
		</div>
        <?php if ($conf->global->POS_OPEN_ALERT == 'yes'): ?>
		<div 
			id="rc_alert_pending_tickets" 
			class="bloqueOpciones" 
			style="display:block;" 
			title="Tickets Sin Cerrar">
			<div class="options">
                <p id="rc_alert_pending_tickets_p"></p>
                <p>Haga <a target="_blank" href="<?php echo DOL_URL_ROOT;?>/custom/pos/backend/close_tickets.php?idmenu=160&mainmenu=pos&leftmenu=" style="color:#090;">click aquí</a> para ver el detalle.</p>
                <input type="button" id="rc_alert_pending_tickets_button" value="Cerrar" class="btn3dbig" />
			</div>	
		</div>
        <?php endif; ?>
		<div 
			id="rc_confirm_ask_abroad" 
			class="bloqueOpciones" 
			style="display:none;" 
			title="Solicitar Surtido en Otro Almacén">
			<div class="options">
				<table style="width: 100%;">
					<thead>
						<tr style="color: #ddd;">
							<th>Producto</th>
							<th>Cant.</th>
						</tr>
					</thead>
					<tbody id="rc_confirm_ask_abroad_tbody">
					</tbody>
				</table>
                <input type="button" id="rc_confirm_ask_abroad_button" value="Aceptar" class="btn3dbig" style="margin-top: 15px;" />
			</div>	
		</div>
		<div 
			id="rc_product_sheet" 
			class="bloqueOpciones" 
			style="padding:0; overflow: hidden;" 
			title="Detalles del Producto">
			<iframe src="" id="rc_product_sheet_options" style="height: 100%; width: 100%;margin: 0;padding: 0;border: 0;"></iframe>
<!--
			<div class="options" >
			</div>	
-->
		</div>
        <div id="idChangeTypePaiement" class="bloqueOpciones" style="display:none;" title="<?php echo $langs->trans("Cambio de Tipo de pago");?>">
            <div id="closedialogpaymentschange" align="center"><a class="btn3d">Cerrar</a></div>
            <div id="confirmpaymentschanges" align="center"><a class="btn3d">Modificar</a></div>
            <?php $form->load_cache_types_paiements();
            $array_mode_pays = array(
                $cash->fk_modepaycash => $form->cache_types_paiements[$cash->fk_modepaycash]['label']
            , $cash->fk_modepaybank => $form->cache_types_paiements[$cash->fk_modepaybank]['label']
            , $cash->fk_modepaybank_extra => $form->cache_types_paiements[$cash->fk_modepaybank_extra]['label']
            ,$cash->fk_modepaybank_extra_2 => $form->cache_types_paiements[$cash->fk_modepaybank_extra_2]['label']
            );//var_dump($array_mode_pays);
            foreach ($array_mode_pays as $key => $value) {// for all paiement types
            ?>
                <p style='font-size: 14pt;'><?=$value?></p>
                <div style="height: 120px;overflow: auto;">
                <table style="width: 100%;">
                    <thead><tr style="background-color: rgb(3, 77, 162);color: white;">
                        <th>Ticket</th>
                        <th>Pago</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody id="payments_<?=$key?>" class="payments_changes" style="background-color: white;color:black;"></tbody>
                </table>
                </div>
            <?php }?>
        </div>
		<div id="idCloseCash" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("CloseCash");?>">
			<div class="options">
				
					<!--<div  class='btnselect --><div class='close_types btnon'><a class="btn3dbig" id='closetype1' style='height:40px;'><?php echo $langs->trans("Closing");?></a></div>
                    <!--<div   class='btnselect--><div class='close_types'><a class="btn3dbig" id='closetype0' style='height:40px;'><?php echo $langs->trans("Arching");?></a></div>
                  <ul>                   
                    <br clear="all" />
					<li><label><?php echo $langs->trans("CashMoney");?>:</label><input onclick="this.select()" type="text" name="id_terminal_cash" id="id_terminal_cash" readonly="readonly"></li>
                    <br clear="all" />
					<li><label><?php echo $langs->trans("MoneyInCash");?>:</label><input onclick="this.select()" type="text" name="id_money_cash" id="id_money_cash" class="numKeyboard"></li>
                    <br clear="all" />
                    <li>
                        <table>
                            <tr>
                                <td>
                                    <label>$1,000</label>
                                </td>
                                <td>
                                    <label>$500</label>
                                </td>
                                <td>
                                    <label>$200</label>
                                </td>
                                <td>
                                    <label>$100</label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="number" id="mil" name="mil" min="0">
                                </td>
                                <td>
                                    <input type="number" id="quin" name="quin" min="0">
                                </td>
                                <td>
                                    <input type="number" id="dosc" name="dosc" min="0">
                                </td>
                                <td>
                                    <input type="number" id="cien" name="cien" min="0">
                                </td>
                             </tr>
                            <tr>
                                <td>
                                    <label>$50</label>
                                </td>
                                <td>
                                    <label>$20</label>
                                </td>
                                <td>
                                    <label>$10</label>
                                    </td>
                                <td>
                                    <label>$5</label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="number" id="cinc" name="cinc" min="0">
                                 </td>
                                <td>
                                    <input type="number" id="vein" name="vein" min="0">
                                </td>
                                <td>
                                    <input type="number" id="diez" name="diez" min="0">
                                </td>
                                <td>
                                    <input type="number" id="cinco" name="cinco" min="0">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label>$2</label>
                                </td>
                                <td>
                                    <label>$1</label>
                                </td>
                                <td>
                                    <label>$0.50</label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="number" id="dos" name="dos" min="0">
                                </td>
                                <td>
                                    <input type="number" id="uno" name="uno" min="0">
                                </td>
                                <td>
                                    <input type="number" id="centavos" name="centavos" min="0">
                                </td>
                            </tr>
                        </table>
                    </li>
                    <br clear="all" />
                    <li>
                        <label>Cantidad a entregar</label>
                        <input type="number" id="cantidad_entregar" name="cantidad_entregar" min="0">
                    </li>
                </ul>
                <br clear="all" />
                <br clear="all" />
                    <script>var clickeado=false;</script>
                    <input type="button" id="id_btn_close_cash" value="<?php echo $langs->trans("Confirmar Cierre");?>" class="btn3dbig">
						
			</div>	
		</div>
		
		<div id="idTotalNote" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("Notes");?>">
			<div class="options">
				<div>
					<table id="noteTable" class="tableList" >
				
				<thead>
					<tr>
						<!--<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th><?php echo $langs->trans("Reference"); ?></th>
						 <th><?php echo $langs->trans("Description"); ?></th> 
						<th><?php echo $langs->trans("Note"); ?></th>
						<th><?php echo $langs->trans("Actions"); ?></th>-->
					</tr>	
				</thead>
				<tbody></tbody>
			</table>
				</div>		
			</div>	
		</div>
		
		<div id="idChangeCustomer" class="bloqueOpciones" style="display:none; height:400px !important;" title="<?php echo $langs->trans("ChangeCustomer");?>">
			<div class="options">
				<div id="customerSearch_" class="topSearch">
					<div class="code">
						<img id="img_customer_search" class="search_but" class="but" src="./img/search_customer.png" height="40px" style="float:left;margin-left:8px;cursor:pointer">
						<input onclick="this.select()" type="text"  size=10 name="id_customer_search_" id="id_customer_search_"></input>
					</div>  
				</div>
			<table id="customerTable_" class="tableList" style="float:left;">
				<thead>
					<tr>
						<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th width=100px; style="color: #fff;"><?php echo $langs->transcountry('ProfId1',$mysoc->country_code); ?></th>
						<th><?php echo $langs->trans("Name"); ?></th>
						<th><?php echo $langs->trans("Project"); ?></th>
						<th width=70px;><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
					</thead>
				<tbody></tbody>
			</table>
            <script>
                $(document).ready(function () {
                    _TPV.getAllCustomers();
                });
            </script>
            </div>         
        </div>
        
        <div id="idCoupon" class="bloqueOpciones" style="display:none; height:400px !important;" title="<?php echo $langs->trans("AddCoupon");?>">
			<div class="options">
			<table id="couponTable_" class="tableList" style="float:left;">
				<thead>
					<tr>
						<th style="display:none"><?php echo $langs->trans("ID"); ?></th>
						<th><?php echo $langs->trans("ReasonDiscount"); ?></th>
						<th width=100px;><?php echo $langs->trans("AmountTTC"); ?></th>
						<th width=70px;><?php echo $langs->trans("Actions"); ?></th>
					</tr>	
					</thead>
				<tbody></tbody>
			</table>
            </div>         
        </div>
        
        <div id="idChangePlace" class="bloqueOpciones" style="display:none" title="<?php echo $langs->trans("ChangePlace");?>">
			<div class="options">
				<div id="placeTable_"></div>
            </div>
		</div>

      </div>                      
<a class="btnPrint" href='tpl/ticket.tpl.php?id=1' style="display:none"></a>
<div id="chat_div"></div>



<!-- GO TABLE, GO UP! -->
<script type="text/javascript">
					$("a#top").click(function() {
  					$("div.ticket_content").animate({ scrollTop: 0 }, "slow");
  					return false;
					});
      function ls_tpv_switch_all_checkboxes()
      {
      	var cv = $('#ls_tpv_checkall').is(":checked");
      	$('.ls_tpv_line_checkbox').attr("checked", cv);
      	ls_tpv_switch_line_checkbox();
      }
      
      function ls_tpv_switch_line_checkbox()
      {
      	var cv = $('#ls_tpv_checkall').is(":checked");
      	var ch = 0;
		var un = 0;
		var ct = 0
      	$('.ls_tpv_line_checkbox').each(function(i,e){
      		if ($(e).is(":checked"))
			{
				ch++;
			}
			else
			{
				un++;
			}
			ct++;
      	});
      	if (ct == 0)
      	{
      		$('#ls_tpv_checkall').attr("checked", false);
      	}
      	else if (ct == ch)
      	{
      		$('#ls_tpv_checkall').attr("checked", true);
      	}
      	else if (ct == un)
      	{
      		$('#ls_tpv_checkall').attr("checked", false);
      	}
      	else
      	{
      		$('#ls_tpv_checkall').attr("checked", false);
      	}
      }

                </script>

<?php if($conf->global->POS_CHAT){?>

   
                <script type="text/javascript">
      $(document).ready(function(){
          var box = null;
          oldscrollHeight = 0;
          box = $("#chat_div").chatbox({id:"chat_div", 
              user:{key : "value"},
              title : "Chat",
              messageSent : function(id, user, msg) {
                  $("#chat_div").chatbox("option", "boxManager").addMsg(id, msg);
              }});
          box.chatbox("option", "boxManager").toggleBox();
          $("#id_btn_chat").click(function() {
            //  if(box) {
                  box.chatbox("option", "boxManager").toggleBox();
              //}
            /*  else {
                  box = $("#chat_div").chatbox({id:"chat_div", 
                                                user:{key : "value"},
                                                title : "Chat",
                                                messageSent : function(id, user, msg) {
                                                    $("#chat_div").chatbox("option", "boxManager").addMsg(id, msg);
                                                }});
              }*/
          });
          function loadLog(){	
                            
           $.ajax({
      			url: "chat.html",
      			cache: false,
      			success: function(html){
      				$("#chat_div").html(html); //Insert chat log into the #chatbox div
      				var newscrollHeight = html.length;
      				if(newscrollHeight > oldscrollHeight){
              			oldscrollHeight = newscrollHeight;
      					$("#chat_div").animate({ scrollTop: newscrollHeight }, 'normal'); //Autoscroll to bottom of div
      					$("#chat_div").chatbox("option", "boxManager").myfunc();      					
      				}				
      		  	},
      		});
      	}
      	setInterval (loadLog, 4000);	//Reload file every 4 seconds
      });
    </script>
<?php }?>
        <!-- Formulario para agregar un producto usado -->

        <div class="ui-layout-resizer ui-layout-resizer-north ui-layout-resizer-open ui-layout-resizer-north-open" title="Resize" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; cursor: n-resize; top: 42px; display: block; " aria-disabled="false"></div></div><div id="form-prospecto" class="ui-dialog ui-widget ui-widget-content ui-corner-all  ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-prospect" style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 440px; top: 86px; left: 312px;"><div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix"><span class="ui-dialog-title" id="ui-dialog-title-prospect">Agregar Producto</span><a href="#" id="para-cerrar" class="ui-dialog-titlebar-close ui-corner-all" role="button"><span class="ui-icon ui-icon-closethick">close</span></a></div><div id="prospecto" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto;">
            <div class="options">
                <ul>
                    <li><label>Etiqueta:</label><input onclick="this.select()" type="text" name="producto_etiqueta" id="producto_etiqueta" class=""></li><!-- value="PU " onfocus="if(this.value=='PU ')this.value=''" -->
                    <br clear="all">
                    <li><label>Cantidad:</label><input onclick="this.select()" type="text" name="producto_cantidad" id="producto_cantidad" class=""></li>
                    <br clear="all">
                </ul>
                <input type="button" id="agregar-producto" value="Agregar" class="btn3dbig">

            </div>
        </div>
        <!--  End-->

        <!-- Dialogo para facturar ticket generado -->

        <div class="ui-layout-resizer ui-layout-resizer-north ui-layout-resizer-open ui-layout-resizer-north-open" title="Resize" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; cursor: n-resize; top: 42px; display: block; visibility: visible;" aria-disabled="false">
        </div>
    </div>
            <div id="form-invoicing" class="ui-dialog ui-widget ui-widget-content ui-corner-all  ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-prospect" style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 40%; top: 20%; left: 30%;">
                <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
                    <span class="ui-dialog-title" id="ui-dialog-title-prospect">Facturar ticket</span>
                    <a href="#" id="form-invoicing-close" class="ui-dialog-titlebar-close ui-corner-all" role="button">
                        <span class="ui-icon ui-icon-closethick">close</span>
                    </a>
                </div>
                <div id="" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto;">
            <div class="options">
            	<div id="div_aviso_factura_automática" style="text-align: center;font-size: 1.15em;font-weight: bold;background-color: #ccf;border: 2px solid #006;color:#006;padding: 10px;">Cliente de Factura Automática</div>
                <h2 style="color: white;">¿Desea generar una factura timbrada para esta venta?</h2>
                <input type="button" id="form-invoicing-accept" value="Aceptar" class="btn3dbig" style="display: inline-block;">
                <input type="button" id="form-invoicing-cancel" value="Cancelar" class="btn3dbig" style="display: inline-block;">

            </div>
        </div>
        <!--  End-->

        <!-- Formulario de confirmacion de datos para facturar -->

        <div class="ui-layout-resizer ui-layout-resizer-north ui-layout-resizer-open ui-layout-resizer-north-open" title="Resize" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; cursor: n-resize; top: 42px; display: block; visibility: visible;" aria-disabled="false">
        </div>
    </div>
    <div id="form-confirm-invoicing" class="ui-dialog ui-widget ui-widget-content ui-corner-all  ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-prospect" style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 60%; top: 5%; left: 20%;">
        <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
            <span class="ui-dialog-title" id="ui-dialog-title-prospect">Confirmacion de factura</span>
            <a href="#" id="form-confirm-invoicing-close" class="ui-dialog-titlebar-close ui-corner-all" role="button">
                <span class="ui-icon ui-icon-closethick">close</span>
            </a>
        </div>
        <div id="" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto; display: block;">
            <div class="options">
                <h4 style="color: white; margin: 8px; padding: 4px; width:200px;text-align: right; display: inline-block;">Confirme el correo del cliente para continuar: </h4>
                <input type="text" id="form-confirm-invoicing-email" style="height:25px; width:500px; border-radius:6px;font-size:20px;margin: 8px; padding: 4px;">
                <h4 style="color: white; margin: 8px; padding: 4px;" id="form-confirm-customer-email"></h4>
                <h4 style="color: white; margin: 8px; padding: 4px;">Asegúrese de que los datos sean correctos: </h4>
                <div style="white-space: nowrap; overflow-x: auto;">
                    <h4 style="color: white; margin: 8px; padding: 4px; display: inline-block;width:200px;text-align: right;">RFC </h4>
                    <input type="text" id="inputRFC" name="inputRFC" value="" style="height: 20px;width:500px;">
                </div>
                <div style="white-space: nowrap; overflow-x: auto;">
                    <h4 style="color: white; margin: 8px; padding: 4px; display: inline-block;width:200px;text-align: right; ">Código postal </h4>
                    <input type="number" id="inputCP" name="inputCP" value="" style="height: 20px;width:500px;">
                </div>
                <div style="white-space: nowrap; overflow-x: auto;">
                    <h4 style="color: white; margin: 8px; padding: 4px; display: inline-block;width:200px;text-align: right; ">Teléfono </h4>
                    <input type="number" id="inputTelefono" name="inputTelefono" value="" style="height: 20px;width:500px;">
                </div>
                <div style="white-space: nowrap; overflow-x: auto;">
                    <h4 style="color: white; margin: 8px; padding: 4px; display: inline-block;width:200px;text-align: right; ">Tipo de pago </h4>
                    <select id="selectTipoPago" style="height: 20px;width:500px;"></select>
                </div>
                <div style="white-space: nowrap; overflow-x: auto;">
                    <h4 style="color: white; margin: 8px; padding: 4px; display: inline-block;width:200px;text-align: right; ">Método de pago CFDI </h4>
                    <select id="selectMetodoCFDI" style="height: 20px;width:500px;"></select>
                </div>
                <div style="white-space: nowrap; overflow-x: auto;">
                    <h4 style="color: white; margin: 8px; padding: 4px; display: inline-block;width:200px;text-align: right; ">Uso de CFDI </h4>
                    <select id="selectUsoCFDI" style="height: 20px;width:500px;"></select>
                </div>
                <div id="form-confirm-invoicing-error" style="display: none;">
                    <h5 style="color:red; margin: 8px; padding: 4px;">Hay datos sin rellenar</h5>
                </div>
                <input type="hidden" id="ticketToFactureID" name="ticketToFactureID" value="">
                <input type="button" id="form-confirm-invoicing-ok" value="OK" class="btn3dbig" style="display: inline-block;">
                <input type="button" id="form-confirm-invoicing-cancel" value="Cancelar" class="btn3dbig" style="display: inline-block;">

            </div>
        </div>
        <!--  End-->

        <!-- Dialogo de informacion sobre facturacion y timbre -->

        <div class="ui-layout-resizer ui-layout-resizer-north ui-layout-resizer-open ui-layout-resizer-north-open" title="Resize" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; cursor: n-resize; top: 42px; display: none; visibility: visible;" aria-disabled="false">
        </div>
    </div>
    <div id="form-invoice-cfdi" class="ui-dialog ui-widget ui-widget-content ui-corner-all  ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-prospect" style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 40%; top: 20%; left: 30%;">
        <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
            <span class="ui-dialog-title" id="ui-dialog-title-prospect">Facturacion</span>
            <a href="#" id="form-invoice-cfdi-close" class="ui-dialog-titlebar-close ui-corner-all" role="button">
                <span class="ui-icon ui-icon-closethick">close</span>
            </a>
        </div>
        <div id="" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto;">
            <div class="options">
                <h3 style="color: white; margin: 8px; padding: 4px;" id="form-invoice-title"></h3>
                <h4 style="color: white; margin: 8px; padding: 4px;" id="form-invoice-subtitle"></h4>
                <p style="color: white; margin: 8px; padding: 4px; font-size: 12px;" id="form-invoice-text"></p>
                <div id="form-confirm-invoicing-error" style="display: none;">
                    <h5 style="color:red; margin: 8px; padding: 4px;">Los correos no coinciden</h5>
                </div>
                <input type="button" id="form-invoice-cfdi-ok" value="Aceptar" class="btn3dbig" style="display: inline-block;">

            </div>
        </div>
        <!--  End-->
        
        
        
        
        
        <!-- Formulario para validar contrasena de descuento -->

        <div class="ui-layout-resizer ui-layout-resizer-north ui-layout-resizer-open ui-layout-resizer-north-open" title="Resize" style="position: absolute; padding: 0px; margin: 0px; font-size: 1px; text-align: left; overflow: hidden; z-index: 2; cursor: n-resize; top: 42px; display: none; visibility: visible;" aria-disabled="false"></div>
        </div>
        <div id="form-validar-contrasena" class="ui-dialog ui-widget ui-widget-content ui-corner-all  ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-validar" style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 440px; top: 86px; left: 312px;">
            <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
                <span class="ui-dialog-title" id="ui-dialog-title-validar">Confirmacion de descuento</span>
                <a href="#" id="para-cerrar-validar" class="ui-dialog-titlebar-close ui-corner-all" role="button">
                    <span class="ui-icon ui-icon-closethick">close</span></a>
            </div>
            <div id="validar" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto;">
            <div class="options">
                <ul>
                    <li><label>Contraseña:</label><input onclick="this.select()" type="password" name="producto_etiqueta" id="contrasena_descuento" class=""></li><!-- value="PU " onfocus="if(this.value=='PU ')this.value=''" -->
                    <br clear="all">
                </ul>
                <input type="button" id="validar-contrasena" value="Aceptar" class="btn3dbig">

            </div>
            </div>
        </div>

        <div 
			id="rc_dialog-home-delivery" 
			class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable ui-resizable" 
			tabindex="-1" 
			role="dialog" 
			aria-labelledby="ui-dialog-title-home-delivery" 
			style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 640px; top: 86px; left: 312px;">
            <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
                <span class="ui-dialog-title" id="ui-dialog-title-home-delivery">Domicilio</span>
                <a 
					href="#" id="para-cerrar-home-delivery" 
					class="ui-dialog-titlebar-close ui-corner-all" 
					role="button"
					onclick="$('#rc_dialog-home-delivery').hide();return false;"
					>
                    <span class="ui-icon ui-icon-closethick">close</span></a>
            </div>
            <div id="home-delivery" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto;">
            <div class="options">
            	<table style="width: 100%;">
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Nombre:</label></td>
            			<td>
							<input 
								onclick="this.select()" 
								type="text" 
								name="rc_delivery[]" 
								id="rc_delivery_name" 
								class=""
								style="width: 100% !important;" 
							/>
						</td>
					</tr>
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Dirección</label></td>
            			<td>
            				<textarea id="rc_delivery_address" name="rc_delivery[]" style="width: 100%;"></textarea>
						</td>
					</tr>
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Ciudad:</label></td>
            			<td>
							<input 
								onclick="this.select()" 
								type="text" 
								name="rc_delivery[]" 
								id="rc_delivery_city" 
								class=""
								style="width: 100% !important;" 
							/>
						</td>
					</tr>
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Estado/Provincia:</label></td>
            			<td>
							<input 
								onclick="this.select()" 
								type="text" 
								name="rc_delivery[]" 
								id="rc_delivery_state" 
								class=""
								style="width: 100% !important;" 
							/>
						</td>
					</tr>
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Pais:</label></td>
            			<td>
							<input 
								onclick="this.select()" 
								type="text" 
								name="rc_delivery[]" 
								id="rc_delivery_country" 
								class=""
								style="width: 100% !important;" 
							/>
						</td>
					</tr>
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Código Postal:</label></td>
            			<td>
							<input 
								onclick="this.select()" 
								type="text" 
								name="rc_delivery[]" 
								id="rc_delivery_zip" 
								class=""
								style="width: 100% !important;" 
							/>
						</td>
					</tr>
            		<tr>
            			<td style="text-align: right; padding-right: 5px;"><label style="text-align: right;display:inline-block;width: initial !important;">Teléfono:</label></td>
            			<td>
							<input 
								onclick="this.select()" 
								type="text" 
								name="rc_delivery[]" 
								id="rc_delivery_phone" 
								class=""
								style="width: 100% !important;" 
							/>
						</td>
					</tr>
				</table>
				<table style="width: 100%;">
					<thead style="color: #eee;">
						<tr>
							<th>Producto</th>
							<th>Cantidad a Entregar</th>
						</tr>
					</thead>
					<tbody id="rc_homedeliv_product_list" style="">
					</tbody>
				</table>
                <input type="button" id="home-delivery-button" value="Aceptar" class="btn3dbig" onclick="_TPV.ticket.rc_save_delivery_address();return false;" />

            </div>
            </div>
        </div>

        <!-- Demo freight cost -->
        <div 
			id="dm_dialog_demoFreight" 
			class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable ui-resizable" 
			tabindex="-1" 
			role="dialog"
			style="display: none; z-index: 1002; outline: 0px; position: absolute; height: auto; width: 440px; top: 86px; left: 312px;">
            <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
                <span class="ui-dialog-title" id="ui-dialog-title-demoFreight">Servicio de flete</span>
                <a
					class="ui-dialog-titlebar-close ui-corner-all" 
					role="button"
					onclick="$('#dm_dialog_demoFreight').hide();return false;"
					>
                    <span class="ui-icon ui-icon-closethick">close</span></a>
            </div>
            <div id="home-delivery" class="bloqueOpciones ui-dialog-content ui-widget-content" style="width: auto; min-height: 97px; height: auto;">
            <div class="options">
            	<table style="width: 100%;">
                    <tr>
                        <td style="text-align: right; padding-right: 5px;"><label style="text-align: right;">Distancia (km):</label></td>
                        <td>
                            <input 
                                onclick="this.select()" 
                                type="text"
                                id="dm_delivery_distance" 
                                class=""
                                style="width: 100%;" 
                            />
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-right: 5px;"><label style="text-align: right;">Método de envío:</label></td>
                        <td>
                            <?= $form->selectShippingMethod('', 'dm_delivery_shipment', '', 1) ?>
                        </td>
                    </tr>
				</table>
                <input type="button" id="calcul_freight" value="Aplicar" class="btn3dbig" onclick="_TPV.ticket.dm_set_freight_cost();return false;" />

            </div>
            </div>
        </div>

        <!-- -->
        <!-- DIV to storage images-->
        <div id="imageStorage" style="display:none">

        </div>
        <!-- Script para formulario contraseÃ±a -->

        <script type="text/javascript">
		var rc_url_root = '<?php echo DOL_URL_ROOT; ?>';

            $("#form-validar-contrasena").hide();

            $("#para-cerrar-validar").click(function(){
                $("#form-validar-contrasena").hide();
            });
			function rc_showProductPdf(id)
			{
				
				$.get(id,function(d){
				$('#rc_product_sheet_options').attr('src',id);
				$('#rc_product_sheet').dialog({width:'95%',height:parseInt($( window ).height() * 0.95)}); 
				});
			}
        </script>
        <!-- -->
        <!-- -->
    <!-- Script para formulario de Prospectos -->

    <script type="text/javascript">

        $("#form-prospecto").hide();

        $("#boton-prospecto").click(function(){
            $("#form-prospecto").show();
        });

        $("#para-cerrar").click(function(){
            $("#form-prospecto").hide();
        });

        $("#agregar-producto").click(function(){
            _TPV.ticket.agregarProspecto();
            $("#form-prospecto").hide();
        });

    </script>

        <!--  Script para dialogos de facturacion de venta  -->
    <script type="text/javascript">
        $("#form-invoicing").hide();

        $("#form-confirm-invoicing").hide();

        $("#form-invoice-cfdi").hide();

        $("#form-invoicing-close").click(function(){
            $("#form-invoicing").hide();
            _TPV.ticket.sendTicket();
        });

        $("#form-invoicing-cancel").click(function(){
            $("#form-invoicing").hide();
            _TPV.ticket.sendTicket();
        });

        $("#form-invoicing-accept").click(function(){
            $("#form-invoicing").hide();
            _TPV.ticket.askInvoicingFromList(0);
        });

        $("#form-confirm-invoicing-close").click(function(){
            $("#form-confirm-invoicing").hide();
            let val = $("#ticketToFactureID").val();
            if(val === '')
                _TPV.ticket.sendTicket();
        });

        $("#form-confirm-invoicing-cancel").click(function(){
            $("#form-confirm-invoicing").hide();
            let val = $("#ticketToFactureID").val();
            if(val === '')
                _TPV.ticket.sendTicket();
        });

        $("#form-confirm-invoicing-ok").click(function(){
            let customerEmail = document.getElementById('form-confirm-customer-email').innerHTML;
            let tipoPago = $("#selectTipoPago").children("option:selected").val();
            let metodoCFDI = $("#selectMetodoCFDI").children("option:selected").val();
            let usoCFDI = $("#selectUsoCFDI").children("option:selected").val();
            let inputEmail = $("#form-confirm-invoicing-email").val();
            let inputRFC = $("#inputRFC").val();
            let inputCP = $("#inputCP").val();
            let inputTelefono = $("#inputTelefono").val();

            if(tipoPago != '' && metodoCFDI != '' && usoCFDI != '' && inputRFC != '' && inputCP != '' && inputTelefono != ''){
                //if((customerEmail.trim().length == 0 && inputEmail.trim().length >0) || (customerEmail.trim() === inputEmail.trim() && inputEmail.trim().length >0)){
                if(inputEmail.trim().length >0){	
                    $("#form-confirm-invoicing-email").val('');
                    $("#form-confirm-invoicing-error").hide();
                    $("#form-confirm-invoicing").hide();
                    let ticketId = $("#ticketToFactureID").val();
                    if(ticketId === '')
                        _TPV.ticket.invoicingTicket(tipoPago, usoCFDI, metodoCFDI, inputEmail, inputRFC, inputCP, inputTelefono);
                    else
                        _TPV.ticket.invoicingTicketCreated(ticketId, tipoPago, usoCFDI, metodoCFDI, inputEmail, inputRFC, inputCP, inputTelefono);
                }
                else{
                    $("#form-confirm-invoicing-error").show();
                }
            }
            else{
            	console.log('tipoPago '+tipoPago);
            	console.log('metodoCFDI '+metodoCFDI);
            	console.log('usoCFDI '+usoCFDI);
            	console.log('inputRFC '+inputRFC);
            	console.log('inputCP '+inputCP);
            	console.log('inputTelefono '+inputTelefono);
                $("#form-confirm-invoicing-error").show();
            }
        });

        $("#form-invoice-cfdi-close").click(function(){
            $("#form-invoice-cfdi").hide();
        });

        $("#form-invoice-cfdi-ok").click(function(){
            $("#form-invoice-cfdi").hide();
        });

		// View backend ticket
		function dol_ticket(id) {
			window.open(`<?= DOL_URL_ROOT ?>/custom/pos/backend/ticket.php?id=${id}`, '_blank');
		}
		var rc_canReceivePayments = <?php echo $rc_receive_payments?'true':'false'; ?>;
        var rc_openTicketsAlert = <?php echo $conf->global->POS_OPEN_ALERT == 'yes'?'true':'false'; ?>;
    </script>
</body>
</html>
