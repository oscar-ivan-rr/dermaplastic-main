<?php
/* Copyright (C) 2011-2012	   Juanjo Menent   	   <jmenent@2byte.es>
 * Copyright (C) 2012-2015	   Ferran Marcet   	   <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/pos/frontend/index.php
 * 	\ingroup	pos
 *  \brief      File to login to point of sales
 */

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/pos.class.php');
#dol_include_once('/pos/class/mobile_detect.php');

$langs->load("admin");
$langs->load("pos@pos");

if (! $user->rights->pos->frontend)
  accessforbidden();

// Test if user logged
if ( $_SESSION['uid'] > 0 )
{
	header ('Location: '.dol_buildpath('/pos/frontend/disconect.php',1));
	exit;
}

global $user,$conf;

$usertxt=$user->login;
//$pwdtxt=$user->pass;

//hacer un getpost para recoger usuario, pass y terminal y que redireccione a verify.php Tú lo vales!!!
if(GETPOST("username")){
	$_SESSION["username"] = GETPOST("username","alpha");
	$_SESSION["password"] = GETPOST("password","alpha");
	$_SESSION["terminal"] = GETPOST("terminal","int");
 	header('Location: '.dol_buildpath('/pos/frontend/verify.php',1));
	exit;
}

$openterminal=GETPOST("openterminal");

/*
 * View
 */

$arrayofcss=array('/pos/frontend/css/pos.css');
top_htmlhead('','',0,0,'',$arrayofcss);

?>

	<!-- Basic Page Needs
  ================================================== -->
	<meta charset="utf-8">
	<title>DoliPOS</title>
	<meta name="description" content="">
	<meta name="author" content="">

	<!-- Mobile Specific Metas
  ================================================== -->
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<!-- CSS
  ================================================== -->
  	<link rel="stylesheet" href="js/jqtransform.css" type="text/css" media="all" />
  	<link rel="stylesheet" type="text/css" href="css/jquery.tweet.css"/>
	<link rel="stylesheet" type="text/css" href="css/keyboard.css">
	<link rel="stylesheet" href="css/base.css">
	<link rel="stylesheet" href="css/skeleton.css">
	<link rel="stylesheet" href="css/layout.css"> 
	<style>
		input:-webkit-autofill,
		input:-webkit-autofill:hover, 
		input:-webkit-autofill:focus, 
		input:-webkit-autofill:active{
    		-webkit-box-shadow: 0 0 0 30px white inset !important;
			background-color:red;
		}
	</style>

	
	
	
    <link href='http://fonts.googleapis.com/css?family=Exo:200,700' rel='stylesheet' type='text/css'>
    
	<script type="text/javascript" src="js/jquery.jqtransform.js" ></script>
	<script type="text/javascript" src="js/jquery.keyboard.min.js"></script>
	<script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.1.0.js"></script>

	
	<script language="javascript">
	$(function(){
		$('form.nice').jqTransform({imgPath:'img/'});
		
		});
		$(document).ready(function() {
			$('#tpvtactil').click(function(){
				tpvtactil();
			});
		});
	
		function tpvtactil()
		{
			$('#tpvtactil').removeClass('tactilon');	
			$('[type=text]').keyboard({
				layout:'qwerty',
				usePreview:false , 
				autoAccept : true,
				accepted : function(e, keyboard, el){
			
			}	
		});
		$('[type=password]').keyboard({
			layout:'qwerty',
			usePreview:false , 
			autoAccept : true,
			accepted : function(e, keyboard, el){
			
			}	
		});
	}
</script>
	
<body>
<?php
      global $mysoc;
      //$logo = DOL_URL_ROOT."/viewimage.php?cache=1&modulepart=companylogo&file=".$mysoc->logo;
      //$logo = DOL_URL_ROOT."/documents/mycompany/logos/".$mysoc->logo;
      $logo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('receipts/logoPOS.jpg');
    $detect = new Mobile_Detect();
    $style="";
    /*if($detect->isTablet())
    {
        $style='style="position:relative; left: 25%; top:40px;"';
    }
    else{
        $style='style="position:relative; left: 11px; top:38px;"';
    }*/
   ?>

	<div class="container" >
		<div class="twelve2 columns">
        	<div class="twelve2 columns">
				<h3 style="margin-top:50px;"><?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?> </h3>
				<h3 style="margin-top:20px;margin-bottom:20px;">Punto de Venta</h3>

			</div>
           
        	
		</div>
		
        
        
	<div class="twelve columns">

		<?php if(GETPOST("err","string")) {?>	
        <div class="errorLogin"><?php print GETPOST("err","string")."<br>"; ?></div> <?php }?>
		<fieldset class="cadre_facturation" style="border:none !important;"><!--<legend class="titre1"><?php /*?><?php echo $langs->trans("Identification"); ?><?php */?></legend>-->
            <div class="five columns">
                <div class="second_login">
                    <img src="<?= $logo?>" class="logo-fixed-pos" width="215" height="215" alt="Logo" title="" <?=$style?>>
                    <!-- 					<div id="tweets">-->
                    <!--            			  <div class="tweet"></div> -->
                    <!--            			<a class="twitter-timeline"  href="https://twitter.com/2byte"  data-widget-id="350616591467159552" data-theme="dark" height="275px">Tweets por @2byte</a>-->
                    <!--<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>-->
                    <!--            	-->
                    <!--            			 -->
                    <!--            		</div>-->
                </div>
            </div>
            <div class="sep"></div>
		 <?php 
				$terminals=POS::select_Terminals();
				/*if(sizeof($terminals))
				{*/
?>
		  <div class="six columns fixed-col">
			<form id="frmLogin" method="POST" action="verify.php" class="nice">
				<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

					<div id="div-userlogin" style="margin-top:3px;">
					<label for="username" style="width: 100%;"><?php echo $langs->trans("Login"); ?></label>
					<input id="username" name="username" class="texte_login" type="text" value="<?php echo $usertxt; ?>"  />
					</div>
					<div class="sep"></div>
					<label for="password" style="width: 100%;"><?php echo $langs->trans("Password"); ?></label>
					<input id="password" name="password" class="texte_login" type="password" value="" style="background:transparent !important;" />
					<div class="sep"></div>
					<label style="width: 100%;"><?php echo $langs->trans("CashS"); ?></label>
					<select name='terminal' class="select-login">
					<!-- <option value='-1'><?php $langs->trans("Choose"); ?></option>  -->
					<?php
					$i=0;
					foreach ($terminals as $terminal)
	    			{
	    				if($detect->isMobile())
	    				{
	    					/*if($terminal["tactil"] == 2)
	    					{*/
	    						print "<option value='".$terminal["rowid"]."'>".$terminal["name"]."</option>\n";
	    					//}
	    				}	
	    				else 
	    				{
	    					print "<option value='".$terminal["rowid"]."'>".$terminal["name"]."</option>\n";
	    				}
	      				
	      				$i++;
	    			}
					?>
			  		</select>
			  	
			  	
        				<div class="sep"></div>
						<div class="sep"></div>
            			<input type="submit"  name="sbmtConnexion" value=<?php echo $langs->trans("Connection"); ?> />
            			<!--
						<input id="tpvtactil" type="button"  value=<?php echo $langs->trans("Tactil"); ?> />
						-->
						<input type="submit" id="Backend" name="sbmtBackend" value=<?php echo $langs->trans("Backend"); ?> />
				</form>		
					
		 </div>
		
		<?php 
			
	    	/*}
	    	else
	    	{ ?>
	    	<div class="six2 columns">
	    	<form id="frmLogin" method="POST" action="verify.php" class="nice">
	    		<p><?php echo $langs->trans("NotHasTerminal"); ?></p>
	    		<div class="sep"></div>
	    		<input type="submit" id="Backend" name="sbmtBackend" value=<?php echo $langs->trans("Backend"); ?> />
	    	</form>	
			</div>
<?php    	}*/
?>
        			
        	
			</fieldset>

<?php
		if ($_GET['err'] < 0) 
		{

			echo ('<script type="text/javascript">');
			echo ('	document.getElementById(\'frmLogin\').pwdPassword.focus();');
			echo ('</script>');

		}	 
		else 
		{

			echo ('<script type="text/javascript">');
			echo ('	document.getElementById(\'frmLogin\').txtUsername.focus();');
			echo ('</script>');

		}
?>
        	
        </div>	
		
        
		                    
		<!--  </li>-->
<!--END PAGE 1-->   
        
  

	<!--  </ul>-->

	<div class="twelve2 columns">  
	</br> 

	
	
		<div class="milogo">
<!--			<img src="img/co_logo.png" class="scale-with-grid" alt="" title="" width="" height="">-->
		</div>
        <?php echo $langs->trans("CopyRight"); ?> &copy; <?php echo date('Y'); ?> - <?php echo $langs->trans("RightsReserved"); ?>
	</div>
</div><!-- container -->

	<script type="text/javascript" src="js/jquery.tweet.js"></script>
	
	
<!-- LATEST TWEETS MODULE -->                       
<script type='text/javascript'>
    jQuery(function($){
        $(".tweet").tweet({
            username: "dolipos",
            join_text: "auto",
            avatar_size: 0,
            count: 20,
            auto_join_text_default: "",
            auto_join_text_ed: "",
            auto_join_text_ing: "",
            auto_join_text_reply: "",
            auto_join_text_url: "",
            loading_text: "Loading Tweets..."
        });
    });
</script> 

</body>

<?php
print '</html>';
?>