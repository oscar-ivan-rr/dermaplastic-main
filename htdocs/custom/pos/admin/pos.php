<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012-2014 Ferran Marcet        <fmarcet@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/admin/cashdesk.php
 *	\ingroup    cashdesk
 *	\brief      Setup page for cashdesk module
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once("/pos/class/ticket.class.php");
dol_include_once("/pos/backend/lib/pos.lib.php");




// Security check
if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("pos@pos");

if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}
$estados_v = POS::getEstadovArray('constant','label');
$estados_k = POS::getEstadovArray('constant','numeric');
$ticket_ty	= array(
					 '0' => 'Venta'
					,'1' => 'Devoluciön'
					);
$ticket_st	= array(
					 '0' => 'Borrador'
					,'2' => 'Procesado'
					,'1' => 'Cerrado'
					);
$ticket_cust= array(
					 '8' => 'Sin pagar'
					,'0' => 'Pagado'
					);
$ticket_stk	= array(
					 '0' => 'Sin Stock'
					,'1' => 'Con Stock' 
					);
					
$by_type	= array(
					 '0'=>array()
					,'1'=>array('8','9')
					);
foreach($estados_v as $k => $v)
{
	if (!in_array($estados_k[$k],$by_type['1']))
	{
		$by_type['0'][] = $estados_k[$k];
	}
}

/*
 * Actions
 */
if (GETPOST('action','string') == 'updateMask')
{
    $maskconstticket=GETPOST('maskconstticket');
    $maskconstticketcredit=GETPOST('maskconstticketcredit');
    $maskticket=GETPOST('maskticket');
    $maskcredit=GETPOST('maskcredit');
    $maskconstfacsim=GETPOST('maskconstfacsim');
    $maskconstfacsimcredit=GETPOST('maskconstfacsimcredit');
    $maskfacsim=GETPOST('maskfacsim');
    $maskfacsimcredit=GETPOST('maskfacsimcredit');
    $maskconstclosecash=GETPOST('maskconstclosecash');
    $maskconstclosecasharq=GETPOST('maskconstclosecasharq');
    $maskclosecash=GETPOST('maskclosecash');
    $maskclosecasharq=GETPOST('maskclosecasharq');
    if ($maskconstticket) dolibarr_set_const($db,$maskconstticket,$maskticket,'chaine',0,'',$conf->entity);
    if ($maskconstticketcredit) dolibarr_set_const($db,$maskconstticketcredit,$maskcredit,'chaine',0,'',$conf->entity);
    if ($maskconstfacsim) dolibarr_set_const($db,$maskconstfacsim,$maskfacsim,'chaine',0,'',$conf->entity);
    if ($maskconstfacsimcredit) dolibarr_set_const($db,$maskconstfacsimcredit,$maskfacsimcredit,'chaine',0,'',$conf->entity);
    if ($maskconstclosecash) dolibarr_set_const($db,$maskconstclosecash,$maskclosecash,'chaine',0,'',$conf->entity);
    if ($maskconstclosecasharq) dolibarr_set_const($db,$maskconstclosecasharq,$maskclosecasharq,'chaine',0,'',$conf->entity);
}
	
if (GETPOST("action") == 'set')
{
	if(GETPOST("POS_FACTURE", "int") == 0 && $conf->global->POS_TICKET == 0){
		setEventMessage($langs->trans("ErrorConfig"),"errors");
	}
	else{
		$db->begin();
		$res = dolibarr_set_const($db,"POS_SERVICES", GETPOST("POS_SERVICES"),'chaine',0,'',$conf->entity);

		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_PLACES", GETPOST("POS_PLACES"),'chaine',0,'',$conf->entity);
	
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_STOCK", GETPOST("POS_STOCK"),'chaine',0,'',$conf->entity);
	
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_MAX_TTC", GETPOST("POS_MAX_TTC"),'chaine',0,'',$conf->entity);

		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_PRINT", GETPOST("POS_PRINT"),'chaine',0,'',$conf->entity);
	
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_MAIL", GETPOST("POS_MAIL"),'chaine',0,'',$conf->entity);
	
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_FACTURE", GETPOST("POS_FACTURE"),'chaine',0,'',$conf->entity);
	
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"REWARDS_POS", GETPOST("REWARDS_POS"),'chaine',0,'',$conf->entity);
	
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_CHAT", GETPOST("POS_CHAT"),'chaine',0,'',$conf->entity);

		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_INV", GETPOST("POS_INV"),'chaine',0,'',$conf->entity);

		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_CLOSE_WIN", GETPOST("POS_CLOSE_WIN"),'chaine',0,'', $conf->entity);
		
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_PRINT_MODE", GETPOST("POS_PRINT_MODE"),'chaine',0,'',$conf->entity);

		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_OPEN_DRAWER", GETPOST("POS_OPEN_DRAWER"),'chaine',0,'',$conf->entity);		

		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_PREDEF_MSG", GETPOST("POS_PREDEF_MSG"),'chaine',0,'',$conf->entity);
		
		if (! $res > 0) $error++;
		$res = dolibarr_set_const($db,"POS_OPEN_ALERT", GETPOST("POS_OPEN_ALERT"),'chaine',0,'',$conf->entity);
		
		if (! $res > 0) $error++;
		
		foreach ($estados_v as $k => $v)
		{
			$var = GETPOST('POS_ALMACEN_SIN_PERMISO_DE_'.$k.'','array');
			$res = dolibarr_set_const($db,"POS_ALMACEN_SIN_PERMISO_DE_".$k, implode(',',$var),'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
		}
		foreach ($estados_v as $k => $v)
		{
			$var = GETPOST('POS_ALMACEN_CON_PERMISO_DE_'.$k.'','array');
			$res = dolibarr_set_const($db,"POS_ALMACEN_CON_PERMISO_DE_".$k, implode(',',$var),'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
		}
		foreach ($estados_v as $k => $v)
		{
			$var = GETPOST('POS_ALMACEN_BAJA_STOCK_DE_'.$k.'','array');
			$res = dolibarr_set_const($db,"POS_ALMACEN_BAJA_STOCK_DE_".$k, implode(',',$var),'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
		}
		foreach ($estados_v as $k => $v)
		{
			$var = GETPOST('POS_ALMACEN_ALTA_STOCK_DE_'.$k.'','array');
			$res = dolibarr_set_const($db,"POS_ALMACEN_ALTA_STOCK_DE_".$k, implode(',',$var),'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
		}
		foreach($_POST as $k => $v)
		{
			if (strpos($k,'POS_ALMACEN_AUTO_') === 0)
			{
				$var = GETPOST($k);
				$res = dolibarr_set_const($db,$k, $var,'chaine',0,'',$conf->entity);
				 
			}
			if (strpos($k,'POS_ALMACEN_ON_ASK_') === 0)
			{
				$var = GETPOST($k);
				$res = dolibarr_set_const($db,$k, $var,'chaine',0,'',$conf->entity);
				 
			}
		}

		$var = GETPOST('POS_ALMACEN_CAMBIO_EN_STOCK','array');
		$res = dolibarr_set_const($db,"POS_ALMACEN_CAMBIO_EN_STOCK", implode(',',$var),'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		
		$var = GETPOST('POS_ALMACEN_MOSTRAR_EN_RESUMEN','array');
		$res = dolibarr_set_const($db,"POS_ALMACEN_MOSTRAR_EN_RESUMEN", implode(',',$var),'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		
		
//	echo '<pre>';
//	print_r($conf->global);
//	echo '</pre>';	
//	die();
	 	if (! $error)
		{
		    $db->commit();
		    setEventMessage($langs->trans("SetupSaved"));
		}
		else
		{
		    $db->rollback();
		    setEventMessage($langs->trans("Error"),"errors");
		}

	}
}


if ($_GET["action"] == 'setmod')
{
	dolibarr_set_const($db, "TICKET_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}
if ($_GET["action"] == 'setmodfacsim')
{
	dolibarr_set_const($db, "FACSIM_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}
if ($_GET["action"] == 'setmodclosecash')
{
	dolibarr_set_const($db, "CLOSECASH_ADDON",$_GET["value"],'chaine',0,'',$conf->entity);
}
	

/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader('',$langs->trans("POSSetup"),$helpurl);

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("POSSetup"),$linkback,'setup');
print '<br>';

$head = posadmin_prepare_head();

dol_fiche_head($head, 'configuration', $langs->trans("POS"), 0, 'list');

if($conf->global->POS_TICKET == 1){
print_titre($langs->trans("TicketsNumberingModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$var=true;
foreach ($conf->file->dol_document_root as $dirroot)
{
    $dir = $dirroot . "/pos/backend/numerotation/";

    if (is_dir($dir))
    {
        $handle = opendir($dir);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
                {
                    $filebis = $file;
                    $classname = preg_replace('/\.php$/','',$file);
                    // For compatibility
                    if (! is_file($dir.$filebis))
                    {
                        $filebis = $file."/".$file.".modules.php";
                        $classname = "mod_ticket_".$file;
                    }
                    //print "x".$dir."-".$filebis."-".$classname;
                    if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/',$filebis) || preg_match('/mod_/',$classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
                    {
                        // Chargement de la classe de numerotation
                        require_once($dir.$filebis);

                        $module = new $classname($db);

                        // Show modules according to features level
                        if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
                        if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

                        if ($module->isEnabled())
                        {
                            $var = !$var;
                            print '<tr '.$bc[$var].'><td width="100">';
                            echo preg_replace('/mod_ticket_/','',preg_replace('/\.php$/','',$file));
                            print "</td><td>\n";

                            print $module->info();

                            print '</td>';

                            // Show example of numbering module
                            print '<td nowrap="nowrap">';
                            $tmp=$module->getExample();
                            if (preg_match('/^Error/',$tmp)) print $langs->trans($tmp);
                            else print $tmp;
                            print '</td>'."\n";

                            print '<td align="center">';
                            //print "> ".$conf->global->FACTURE_ADDON." - ".$file;
                            if ($conf->global->TICKET_ADDON == $file || $conf->global->TICKET_ADDON.'.php' == $file)
                            {
                                print img_picto($langs->trans("Activated"),'on');
                            }
                            else
                            {
                                print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.preg_replace('/\.php$/','',$file).'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
                            }
                            print '</td>';

                            $facture=new Ticket($db);
                           // $facture->initAsSpecimen();

                            // Example for standard invoice
                            $htmltooltip='';
                            $htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                            $facture->type=0;
                            $nextval=$module->getNextValue($mysoc,$facture);
                            if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
                            {
                                $htmltooltip.=$langs->trans("NextValueForTickets").': ';
                                if ($nextval)
                                {
                                    $htmltooltip.=$nextval.'<br>';
                                }
                                else
                                {
                                    $htmltooltip.=$langs->trans($module->error).'<br>';
                                }
                            }
                            

                            print '<td align="center">';
                            print $html->textwithpicto('',$htmltooltip,1,0);

                            if ($conf->global->TICKET_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
                            {
                                if (! empty($module->error)) setEventMessage($module->error,"error");
                            }

                            print '</td>';

                            print "</tr>\n";

                        }
                    }
                }
            }
            closedir($handle);
        }
    }
}

print '</table>';

print "<br>";
}
if($conf->global->POS_FACTURE == 1){
print_titre($langs->trans("FacsimNumberingModule"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
print '</tr>'."\n";

clearstatcache();

$var=true;
foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/pos/backend/numerotation/numerotation_facsim/";

	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
				if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
				{
					$filebis = $file;
					$classname = preg_replace('/\.php$/','',$file);
					// For compatibility
					if (! is_file($dir.$filebis))
					{
						$filebis = $file."/".$file.".modules.php";
						$classname = "mod_facsim_".$file;
					}
					//print "x".$dir."-".$filebis."-".$classname;
					if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/',$filebis) || preg_match('/mod_/',$classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
					{
						// Chargement de la classe de numerotation
						require_once($dir.$filebis);

						$module = new $classname($db);

						// Show modules according to features level
						if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled())
						{
							$var = !$var;
							print '<tr '.$bc[$var].'><td width="100">';
							echo preg_replace('/mod_facsim_/','',preg_replace('/\.php$/','',$file));
							print "</td><td>\n";

							print $module->info();

							print '</td>';

							// Show example of numbering module
							print '<td nowrap="nowrap">';
							$tmp=$module->getExample();
							if (preg_match('/^Error/',$tmp)) print $langs->trans($tmp);
							else print $tmp;
							print '</td>'."\n";

							print '<td align="center">';
							//print "> ".$conf->global->FACTURE_ADDON." - ".$file;
							if ($conf->global->FACSIM_ADDON == $file || $conf->global->FACSIM_ADDON.'.php' == $file)
							{
								print img_picto($langs->trans("Activated"),'on');
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodfacsim&amp;value='.preg_replace('/\.php$/','',$file).'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
							}
							print '</td>';

							$facture=new Ticket($db);
							//$facture->initAsSpecimen();

							// Example for standard invoice
							$htmltooltip='';
							$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$facture->type=0;
							$nextval=$module->getNextValue($mysoc,$facture);
							if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
							{
								$htmltooltip.=$langs->trans("NextValueForTickets").': ';
								if ($nextval)
								{
									$htmltooltip.=$nextval.'<br>';
								}
								else
								{
									$htmltooltip.=$langs->trans($module->error).'<br>';
								}
							}


							print '<td align="center">';
							print $html->textwithpicto('',$htmltooltip,1,0);

							if ($conf->global->FACSIM_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
							{
								if (! empty($module->error)) setEventMessage($module->error,"error");
							}

							print '</td>';

							print "</tr>\n";

						}
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table>';

print "<br>";
}


	print_titre($langs->trans("CloseCashNumberingModule"));

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td nowrap>'.$langs->trans("Example").'</td>';
	print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
	print '<td align="center" width="16">'.$langs->trans("Infos").'</td>';
	print '</tr>'."\n";

	clearstatcache();

	$var=true;
	foreach ($conf->file->dol_document_root as $dirroot)
	{
		$dir = $dirroot . "/pos/backend/numerotation/numerotation_closecash/";

		if (is_dir($dir))
		{
			$handle = opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
					{
						$filebis = $file;
						$classname = preg_replace('/\.php$/','',$file);
						// For compatibility
						if (! is_file($dir.$filebis))
						{
							$filebis = $file."/".$file.".modules.php";
							$classname = "mod_closecash_".$file;
						}
						//print "x".$dir."-".$filebis."-".$classname;
						if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/',$filebis) || preg_match('/mod_/',$classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
						{
							// Chargement de la classe de numerotation
							require_once($dir.$filebis);

							$module = new $classname($db);

							// Show modules according to features level
							if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

							if ($module->isEnabled())
							{
								$var = !$var;
								print '<tr '.$bc[$var].'><td width="100">';
								echo preg_replace('/mod_closecash_/','',preg_replace('/\.php$/','',$file));
								print "</td><td>\n";

								print $module->info();

								print '</td>';

								// Show example of numbering module
								print '<td nowrap="nowrap">';
								$tmp=$module->getExample();
								if (preg_match('/^Error/',$tmp)) print $langs->trans($tmp);
								else print $tmp;
								print '</td>'."\n";

								print '<td align="center">';
								//print "> ".$conf->global->FACTURE_ADDON." - ".$file;
								if ($conf->global->CLOSECASH_ADDON == $file || $conf->global->CLOSECASH_ADDON.'.php' == $file)
								{
									print img_picto($langs->trans("Activated"),'on');
								}
								else
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmodclosecash&amp;value='.preg_replace('/\.php$/','',$file).'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
								}
								print '</td>';

								$facture=new Ticket($db);
								$facture->initAsSpecimen();

								// Example for standard invoice
								$htmltooltip='';
								$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
								$facture->type_control=1;
								$nextval=$module->getNextValue($mysoc,$facture);
								if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
								{
									$htmltooltip.=$langs->trans("NextValueForTickets").': ';
									if ($nextval)
									{
										$htmltooltip.=$nextval.'<br>';
									}
									else
									{
										$htmltooltip.=$langs->trans($module->error).'<br>';
									}
								}


								print '<td align="center">';
								print $html->textwithpicto('',$htmltooltip,1,0);

								if ($conf->global->CLOSECASH_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
								{
									if (! empty($module->error)) setEventMessage($module->error,"error");
								}

								print '</td>';

								print "</tr>\n";

							}
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print '</table>';

	print "<br>";

print_titre($langs->trans("OtherOptions"));

// Mode
$var=true;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

if($conf->global->POS_TICKET){

	/*$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSUseTickets");
	print '<td colspan="2">';
	
	print $html->selectyesno("POS_TICKET",$conf->global->POS_TICKET,1,$disable);
	
	print "</td></tr>\n";*/
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSFactureTicket");
	print '<td colspan="2">';
	
	print $html->selectyesno("POS_FACTURE",$conf->global->POS_FACTURE,1);
	
	print "</td></tr>\n";
}
else
	print '<input type="hidden" name="POS_FACTURE" value="1">';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("POSMaxTTC").'</td>';
print '<td><input type="text" class="flat" name="POS_MAX_TTC" value="'. ($_POST["POS_MAX_TTC"]?$_POST["POS_MAX_TTC"]:$conf->global->POS_MAX_TTC) . '" size="8"> '.$langs->trans("Currency".$conf->currency).'</td>';
print '</tr>';

if ($conf->service->enabled)
{
    $var=! $var;
    print '<tr '.$bc[$var].'><td>';
    print $langs->trans("POSShowServices");
    print '<td colspan="2">';;
    print $html->selectyesno("POS_SERVICES",$conf->global->POS_SERVICES,1);
    print "</td></tr>\n";
}

	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSShowPlaces");
	print '<td colspan="2">';;
	print $html->selectyesno("POS_PLACES",$conf->global->POS_PLACES,1);
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSSellStock");
	print '<td colspan="2">';;
	print $html->selectyesno("POS_STOCK",$conf->global->POS_STOCK,1);
	print "</td></tr>\n";

	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSPrintTicket");
	print '<td colspan="2">';;
	print $html->selectyesno("POS_PRINT",$conf->global->POS_PRINT,1);
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSMailTicket");
	print '<td colspan="2">';
	print $html->selectyesno("POS_MAIL",$conf->global->POS_MAIL,1);
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSChat");
	print '<td colspan="2">';
	print $html->selectyesno("POS_CHAT",$conf->global->POS_CHAT,1);
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("POSRewards");
	if (! empty($conf->rewards->enabled))
	{
		print '<td colspan="2">';
		print $html->selectyesno("REWARDS_POS",$conf->global->REWARDS_POS,1);
	}
	else 
	{
		print '<td colspan="2">'.$langs->trans("NoRewardsInstalled").' '.$langs->trans("GetRewards","http://www.dolistore.com/search.php?orderby=position&orderway=desc&search_query=2rewards&submit_search=Buscar").'</td>';
	}
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("InvertDisplay");
	print '<td colspan="2">';
	print $html->selectyesno("POS_INV",$conf->global->POS_INV,1);
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("AutomaticCloseWindow");
	print '<td colspan="2">';
	print $html->selectyesno("POS_CLOSE_WIN",$conf->global->POS_CLOSE_WIN,1);
	print "</td></tr>\n";
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("PosPrintMode");
	print '<td colspan="2">';
	print $html->selectyesno("POS_PRINT_MODE",$conf->global->POS_PRINT_MODE,1);
	print "</td></tr>\n";
	
	if($conf->global->POS_PRINT_MODE == 1){
		$var=! $var;
		print '<tr '.$bc[$var].'><td>';
		print $langs->trans("PosOpenDrawer");
		print '<td colspan="2">';
		print $html->selectyesno("POS_OPEN_DRAWER",$conf->global->POS_OPEN_DRAWER,1);
		print "</td></tr>\n";
	}
	
	$var=! $var;
	print '<tr '.$bc[$var].'><td colspan="2">';
	print $langs->trans("PredefMsg").'<br>';
	print '<textarea name="POS_PREDEF_MSG" class="flat" cols="120">'.$conf->global->POS_PREDEF_MSG.'</textarea>';
	print '</td></tr>';

	$var=! $var;
	print '<tr '.$bc[$var].'><td>';
	print $langs->trans("Alerta de Tickets Abiertos");
	print '<td colspan="2">';
	print $html->selectyesno("POS_OPEN_ALERT",$conf->global->POS_OPEN_ALERT,0);
	print "</td></tr>\n";


print '</table>';
print '<br>';
if ($user->login == 'soporte'):
if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}
$ev_style = POS::getEstadovStyle();

?>
<?php 	print_titre($langs->trans("Cambios Posibles de Estado V sin permiso especial")); ?>

<style><?php echo $ev_style; ?>
</style>

<table class="noborder" style="width: 100%;">
	<tr>
		<th style="width: 20%;">Parámetro</th>
		<th style="width: 30%;">Valor</th>
		<th style="width: 20%;">Parámetro</th>
		<th style="width: 30%;">Valor</th>
	</tr>
	<?php $i = 0; ?>
	<?php foreach($estados_v as $k => $v):?>
	<?php $tvar='POS_ALMACEN_SIN_PERMISO_DE_'.$k; $lvar = explode(',',$conf->global->$tvar); ?>
	<?php if ($i%2 == 0 ): ?>
	<tr>	
	<?php endif; ?>
		<td>De <?php echo $v; ?></td>
		<td>
			<select name="POS_ALMACEN_SIN_PERMISO_DE_<?php echo $k; ?>[]" multiple="multiple" class="select2" id="POS_ALMACEN_SIN_PERMISO_DE_<?php echo $k; ?>">
				<?php $si = 0; ?>
				<?php foreach ($estados_v as $sk => $sv) : ?>
				<option value="<?php echo $estados_k[$sk]; ?>"<?php echo (in_array((string)$estados_k[$sk],$lvar,true)) ? 'selected="selected"':''; ?>><?php echo $sv ?></option>
				<?php $si++; ?>
				<?php endforeach; ?> 
			</select>
		</td>
	<?php if ($i%2 != 0 ): ?>
	</tr>
	<?php endif; ?>
	<?php $i++; ?>
	<?php endforeach; ?>
	<?php if ($i%2 != 0 ): ?>
	<td></td><td></td></tr>
	<?php endif; ?>
</table>

<?php 	print_titre($langs->trans("Cambios Posibles de Estado V con permiso especial")); ?>

<table class="noborder" style="width: 100%;">
	<tr>
		<th style="width: 20%;">Parámetro</th>
		<th style="width: 30%;">Valor</th>
		<th style="width: 20%;">Parámetro</th>
		<th style="width: 30%;">Valor</th>
	</tr>
	<?php $i = 0; ?>
	<?php foreach($estados_v as $k => $v):?>
	<?php $tvar='POS_ALMACEN_CON_PERMISO_DE_'.$k; $lvar = explode(',',$conf->global->$tvar); ?>
	<?php if ($i%2 == 0 ): ?>
	<tr>	
	<?php endif; ?>
		<td>De <?php echo $v; ?></td>
		<td>
			<select name="POS_ALMACEN_CON_PERMISO_DE_<?php echo $k; ?>[]" multiple="multiple" class="select2" id="POS_ALMACEN_CON_PERMISO_DE_<?php echo $k; ?>">
				<?php $si = 0; ?>
				<?php foreach ($estados_v as $sk => $sv) : ?>
				<option value="<?php echo $estados_k[$sk]; ?>"<?php echo (in_array((string)$estados_k[$sk],$lvar,true)) ? 'selected="selected"':''; ?>><?php echo $sv ?></option>
				<?php $si++; ?>
				<?php endforeach; ?> 
			</select>
		</td>
	<?php if ($i%2 != 0 ): ?>
	</tr>
	<?php endif; ?>
	<?php $i++; ?>
	<?php endforeach; ?>
	<?php if ($i%2 != 0 ): ?>
	<td></td><td></td></tr>
	<?php endif; ?>
</table>

<table class="noborder" style="width: 100%;">
	<tr>
		<th style="padding-top: 20px;background-color: #ddd;" colspan="4"><?php echo mb_strtoupper('Envío a Almacén'); ?></th>
	</tr>
	<tr>
		<th>Estado V. Original</th>
		<th>Estado V. Al Enviar</th>
		<th>Estado V. Original</th>
		<th>Estado V. Al Enviar</th>
	</tr>
	<tr>
		<td>Nulo</td>
		<td>
			<?php $constName = 'POS_ALMACEN_ON_ASK_N'; ?>
			<select onchange="rc_changed_auto_status('<?php echo $constName ?>');" name="<?php echo $constName ; ?>" id="<?php echo $constName ; ?>">
				<?php $sevi = 0; ?>
				<option value="-"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === '-')?' selected="selected"': '-' ?>>Sin Cambio</option>
				<?php foreach($estados_v as $edovK => $edov): ?>
					<option class="<?php echo $edovK; ?>" value="<?php echo $estados_k[$edovK]; ?>"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === (string)$estados_k[$edovK])?' selected="selected"': '' ?>><?php echo $edov; ?></option>
					<?php $sevi++; ?>
					<?php endforeach; ?>
				</select>
		</td>
	<?php $ct = 1; ?>
	<?php foreach($estados_v as $ev_k => $ev_v): ?>
		<?php if ($ct/2 == intval($ct/2)):?>
		<tr>		
		<?php endif; ?>
			<td class="<?php echo $ev_k; ?>"><?php echo $ev_v; ?></td>
			<td>
				<?php $constName = 'POS_ALMACEN_ON_ASK_'.($estados_k[$ev_k]); ?>
				<select onchange="rc_changed_auto_status('<?php echo $constName ?>');" name="<?php echo $constName ; ?>" id="<?php echo $constName ; ?>">
					<?php $sevi = 0; ?>
					<option value="-"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === '-')?' selected="selected"': '-' ?>>Sin Cambio</option>
					<?php foreach($estados_v as $edovK => $edov): ?>
						<option class="<?php echo $edovK; ?>" value="<?php echo $estados_k[$edovK]; ?>"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === (string)$estados_k[$edovK])?' selected="selected"': '' ?>><?php echo $edov; ?></option>
						<?php $sevi++; ?>
						<?php endforeach; ?>
					</select>
			</td>
		<?php if ($ct/2 != intval($ct/2)):?>
		</tr>		
		<?php endif; ?>
		<?php $ct++; ?>
	<?php endforeach; ?>
	<?php if ($ct/2 == intval($ct/2)):?>
	<td></td>
	</tr>		
	<?php endif; ?>
	
</table>

<?php 	print_titre($langs->trans("Cambio de Estado V. Automático")); ?>
<table class="noborder" style="width: 100%;">
	<?php foreach($ticket_ty as $type_k => $type_v): ?>
	<tr>
		<th style="padding-top: 20px;background-color: #ddd;" colspan="5"><?php echo strtoupper($type_v); ?></th>
	</tr>
		<?php foreach ($ticket_st as $stat_k => $stat_v): ?>
		<tr>
			<th colspan="5"><?php echo $stat_v; ?></th>
		</tr>
			<tr>
				<th></th>
				<?php foreach($ticket_cust as $cust_k => $cust_v): ?>
				<th colspan="2">
					<?php echo $cust_v ?>
				</th>
				<?php endforeach; ?>
			</tr>
				<tr>
					<th>Estado V. Original</th>
					<?php foreach($ticket_stk as $stk_k => $stk_v): ?>
					<th>
						<?php echo $stk_v ?>
					</th>
					<?php endforeach; ?>
					<?php foreach($ticket_stk as $stk_k => $stk_v): ?>
					<th>
						<?php echo $stk_v ?>
					</th>
					<?php endforeach; ?>
			</tr>
			<tr>
				<td><?php echo 'Nulo (nuevo)'; ?></td>
				<?php $evi = 'N'; ?>
				<?php foreach($ticket_cust as $cust_k => $cust_v): ?>
					<?php foreach($ticket_stk as $stk_k => $stk_v): ?>
						<td>
						<?php $constName = 'POS_ALMACEN_AUTO_'.$evi.'_'.$type_k.'_'.$stat_k.'_'.$cust_k.'_'.$stk_k ; ?>
							<select onchange="rc_changed_auto_status('<?php echo $constName ?>');" name="<?php echo $constName ; ?>" id="<?php echo $constName ; ?>">
								<?php $sevi = 0; ?>
								<option value="-"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === '-')?' selected="selected"': '-' ?>>Sin Cambio</option>
								<?php foreach($estados_v as $edovK => $edov): ?>
								<option class="<?php echo $edovK; ?>" value="<?php echo $estados_k[$edovK]; ?>"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === (string)$estados_k[$edovK])?' selected="selected"': '' ?>><?php echo $edov; ?></option>
								<?php $sevi++; ?>
								<?php endforeach; ?>
							</select>
						</td>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tr>
			<?php $evi = 0; ?>
			<?php foreach($estados_v as $ev_k => $ev_v): ?>
			<?php if(!in_array($estados_k[$ev_k],$by_type[$type_k])){$evi++;continue;}?>
			<tr>
				<td class="<?php echo $ev_k; ?>"><?php echo $ev_v; ?></td>
				<?php foreach($ticket_cust as $cust_k => $cust_v): ?>
					<?php foreach($ticket_stk as $stk_k => $stk_v): ?>
						<td>
						<?php $constName = 'POS_ALMACEN_AUTO_'.$estados_k[$ev_k].'_'.$type_k.'_'.$stat_k.'_'.$cust_k.'_'.$stk_k ; ?>
							<select onchange="rc_changed_auto_status('<?php echo $constName ?>');" name="<?php echo $constName ; ?>" id="<?php echo $constName; ?>">
								<?php $sevi = 0; ?>
								<option value="-"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName == '-')?' selected="selected"': '' ?>>Sin Cambio</option>
								<?php foreach($estados_v as $edovK => $edov): ?>
								<option class="<?php echo $edovK; ?>" value="<?php echo $estados_k[$edovK]; ?>"<?php echo (property_exists($conf->global,$constName) && $conf->global->$constName === (string)$estados_k[$edovK])?' selected="selected"': '' ?>><?php echo $edov; ?></option>
								<?php $sevi++; ?>
								<?php endforeach; ?>
							</select>
						</td>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tr>
			<?php $evi++; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
	<?php endforeach; ?>
</table>



<?php 	print_titre($langs->trans("Baja de Inventario al Cambiar de Estado V.")); ?>
<table class="noborder" style="width: 100%;">
	<tr>
		<th style="width: 20%;">Estado Original</th>
		<th style="width: 30%;">Estado(s) con Baja</th>
		<th style="width: 20%;">Estado Original</th>
		<th style="width: 30%;">Estado(s) con Baja</th>
	</tr>
	<?php $i = 0; ?>
	<?php foreach($estados_v as $k => $v):?>
	<?php $tvar='POS_ALMACEN_BAJA_STOCK_DE_'.$k; $lvar = explode(',',$conf->global->$tvar); ?>
	<?php if ($i%2 == 0 ): ?>
	<tr>	
	<?php endif; ?>
		<td>De <?php echo $v; ?></td>
		<td>
			<select name="POS_ALMACEN_BAJA_STOCK_DE_<?php echo $k; ?>[]" multiple="multiple" class="select2" id="POS_ALMACEN_BAJA_STOCK_DE_<?php echo $k; ?>">
				<?php $si = 0; ?>
				<?php foreach ($estados_v as $sk => $sv) : ?>
				<option value="<?php echo $estados_k[$sk]; ?>"<?php echo (in_array((string)$estados_k[$sk],$lvar,true)) ? 'selected="selected"':''; ?>><?php echo $sv ?></option>
				<?php $si++; ?>
				<?php endforeach; ?> 
			</select>
		</td>
	<?php if ($i%2 != 0 ): ?>
	</tr>
	<?php endif; ?>
	<?php $i++; ?>
	<?php endforeach; ?>
	<?php if ($i%2 != 0 ): ?>
	<td></td><td></td></tr>
	<?php endif; ?>
</table>

<?php 	print_titre($langs->trans("Alta de Inventario al Cambiar de Estado V.")); ?>
<table class="noborder" style="width: 100%;">
	<tr>
		<th style="width: 20%;">Estado Original</th>
		<th style="width: 30%;">Estado(s) con Alta</th>
		<th style="width: 20%;">Estado Original</th>
		<th style="width: 30%;">Estado(s) con Alta</th>
	</tr>
	<?php $i = 0; ?>
	<?php foreach($estados_v as $k => $v):?>
	<?php $tvar='POS_ALMACEN_ALTA_STOCK_DE_'.$k; $lvar = explode(',',$conf->global->$tvar); ?>
	<?php if ($i%2 == 0 ): ?>
	<tr>	
	<?php endif; ?>
		<td>De <?php echo $v; ?></td>
		<td>
			<select name="POS_ALMACEN_ALTA_STOCK_DE_<?php echo $k; ?>[]" multiple="multiple" class="select2" id="POS_ALMACEN_ALTA_STOCK_DE_<?php echo $k; ?>">
				<?php $si = 0; ?>
				<?php foreach ($estados_v as $sk => $sv) : ?>
				<option value="<?php echo $estados_k[$sk]; ?>"<?php echo (in_array((string)$estados_k[$sk],$lvar,true)) ? 'selected="selected"':''; ?>><?php echo $sv ?></option>
				<?php $si++; ?>
				<?php endforeach; ?> 
			</select>
		</td>
	<?php if ($i%2 != 0 ): ?>
	</tr>
	<?php endif; ?>
	<?php $i++; ?>
	<?php endforeach; ?>
	<?php if ($i%2 != 0 ): ?>
	<td></td><td></td></tr>
	<?php endif; ?>
</table>

<?php 	print_titre($langs->trans("Parámetros Varios")); ?>
<table class="noborder" style="width: 100%;">
	<tr>
		<th style="width: 20%;">Parámetro</th>
		<th style="width: 30%;">Valor</th>
		<th style="width: 20%;">Parámetro</th>
		<th style="width: 30%;">Valor</th>
	</tr>
	<tr>
		<td style="width: 20%;">Cambio Auto en Movimientos de Stock</td>
		<td style="width: 30%;">
			<select name="POS_ALMACEN_CAMBIO_EN_STOCK[]" multiple="multiple" class="select2" id="POS_ALMACEN_CAMBIO_EN_STOCK">
				<?php $si = 0; $lvar = explode(',',$conf->global->POS_ALMACEN_CAMBIO_EN_STOCK); ?>
				<?php foreach ($estados_v as $sk => $sv) : ?>
				<option value="<?php echo $estados_k[$sk]; ?>"<?php echo (in_array((string)$estados_k[$sk],$lvar,true)) ? 'selected="selected"':''; ?>><?php echo $sv ?></option>
				<?php $si++; ?>
				<?php endforeach; ?>
			</select>
		</td>
		<td style="width: 20%;">Mostrar por omisión en Envíos a Almacén</td>
		<td style="width: 30%;">
			<select name="POS_ALMACEN_MOSTRAR_EN_RESUMEN[]" multiple="multiple" class="select2" id="POS_ALMACEN_MOSTRAR_EN_RESUMEN">
				<?php $si = 0; $lvar = explode(',',$conf->global->POS_ALMACEN_MOSTRAR_EN_RESUMEN); ?>
				<?php foreach ($estados_v as $sk => $sv) : ?>
				<option value="<?php echo $estados_k[$sk]; ?>"<?php echo (in_array((string)$estados_k[$sk],$lvar,true)) ? 'selected="selected"':''; ?>><?php echo $sv ?></option>
				<?php $si++; ?>
				<?php endforeach; ?>
			</select>
		
		</td>
	</tr>
</table>


<script>
	$(document).ready(function () {
		$('.select2').select2(
			{
				containerCssClass: ':all:',
				placeholder: "Seleccione",
				width:'100%',
			});
		$('select').each(function (i,e){
			lid = $(e).attr('id');
			if (typeof lid != 'undefined' )
			{
				if (lid.substring(0,17) == 'POS_ALMACEN_AUTO_')
				{
					$('#'+lid).parent().removeClass().addClass($('#'+lid+' :selected').attr('class'));
				}
				if (lid.substring(0,19) == 'POS_ALMACEN_ON_ASK_')
				{
					$('#'+lid).parent().removeClass().addClass($('#'+lid+' :selected').attr('class'));
				}
			}
		});
	});
	function rc_changed_auto_status(id)
	{
		$('#'+id).parent().removeClass().addClass($('#'+id+' :selected').attr('class'));
	}
</script>
<?php
endif;
print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';

print "</form>";

dol_htmloutput_events();

$db->close();

llxFooter();
?>
