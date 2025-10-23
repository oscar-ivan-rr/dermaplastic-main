<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/societe/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 */
$files = array(
				 'address'		=> array() //Comprobante de Domicilio
				,'photo_out'	=> array() //Fotos del inmueble (exterior)
				,'photo_in'		=> array() //Fotos del inmueble (interior)
				,'bank'			=> array() //Estado de cuenta
				,'irs'			=> array() //Alta de Hacienda
				,'acta'			=> array() //Acta Constitutiva 
				,'rfc'			=> array() //RFC
				,'const_sat'	=> array() //Constancia de Situación Fiscal
				,'opinion_sat'	=> array() //Opinión del SAT
				,'ref'			=> array() //Cartas de referencia
				,'auth'			=> array() //Relación de personas autorizadas para compra y recepción de mercancía
				,'ine_al'		=> array() //INE del apoderado legal
				,'ine_ph'		=> array() //INE del encargado de compras
				,'ine_pay'		=> array() //INE del encargado de pagos 
				,'prom'			=> array() //Pagaré firmado
				,'credit_request'=> array() //Solicitud de Crédito
				);
$last = array(
				 'address'		=> 0 //Comprobante de Domicilio
				,'photo_out'	=> 0 //Fotos del inmueble (exterior)
				,'photo_in'		=> 0 //Fotos del inmueble (interior)
				,'bank'			=> 0 //Estado de cuenta
				,'irs'			=> 0 //Alta de Hacienda
				,'acta'			=> 0 //Acta Constitutiva 
				,'rfc'			=> 0 //RFC
				,'const_sat'	=> 0 //Constancia de Situación Fiscal
				,'opinion_sat'	=> 0 //Opinión del SAT
				,'ref'			=> 0 //Cartas de referencia
				,'auth'			=> 0 //Relación de personas autorizadas para compra y recepción de mercancía
				,'ine_al'		=> 0 //INE del apoderado legal
				,'ine_ph'		=> 0 //INE del encargado de compras
				,'ine_pay'		=> 0 //INE del encargado de pagos 
				,'prom'			=> 0 //Pagaré firmado
                ,'credit_request'=>0 //Solicitud de Crédito
				);
$titles = array(
				 'address'		=> 'Comprobante de Domicilio'
				,'photo_out'	=> 'Fotos del inmueble (exterior)'
				,'photo_in'		=> 'Fotos del inmueble (interior)'
				,'bank'			=> 'Estado de cuenta'
				,'irs'			=> 'Alta de Hacienda'
				,'acta'			=> 'Acta Constitutiva' 
				,'rfc'			=> 'RFC'
				,'const_sat'	=> 'Constancia de Situación Fiscal'
				,'opinion_sat'	=> 'Opinión del SAT'
				,'ref'			=> 'Cartas de referencia'
				,'auth'			=> 'Relación de personas autorizadas para compra y recepción de mercancía'
				,'ine_al'		=> 'INE del apoderado legal'
				,'ine_ph'		=> 'INE del encargado de compras'
				,'ine_pay'		=> 'INE del encargado de pagos ' 
				,'prom'			=> 'Pagaré firmado'
                ,'credit_request'=> 'Solicitud de Crédito' //Solicitud de Crédito
				);

$expire = array(
				 'address'		=> -1 //Comprobante de Domicilio
				,'photo_out'	=> -1 //Fotos del inmueble (exterior)
				,'photo_in'		=> -1 //Fotos del inmueble (interior)
				,'bank'			=> -1 //Estado de cuenta
				,'irs'			=> -1 //Alta de Hacienda
				,'acta'			=> -1 //Acta Constitutiva 
				,'rfc'			=> -1 //RFC
				,'const_sat'	=> -1 //Constancia de Situación Fiscal
				,'opinion_sat'	=> -1 //Opinión del SAT
				,'ref'			=> -1 //Cartas de referencia
				,'auth'			=> -1 //Relación de personas autorizadas para compra y recepción de mercancía
				,'ine_al'		=> -1 //INE del apoderado legal
				,'ine_ph'		=> -1 //INE del encargado de compras
				,'ine_pay'		=> -1 //INE del encargado de pagos 
				,'prom'			=> -1 //Pagaré firmado
                ,'credit_request'=>-1 //Solicitud de Crédito
				);

# Cambiar también en htdocs/societe/admin/societe_expiration.php
#					 htdocs/societe/class/societe.class.php
$vals	= array(
				 '-1'			=> 'No Expira'
				,'+1 month'		=> '1 Mes'
				,'+2 months'	=> '2 Meses'
				,'+3 months'	=> '3 Meses'
				,'+4 months'	=> '4 Meses'
				,'+5 months'	=> '5 Meses'
				,'+6 months'	=> '6 Meses'
				,'+7 months'	=> '7 Meses'
				,'+8 months'	=> '8 Meses'
				,'+9 months'	=> '9 Meses'
				,'+10 months'	=> '10 Meses'
				,'+11 months'	=> '11 Meses'
				,'+12 months'	=> '12 Meses'
				);

require '../main.inc.php';
foreach($expire as $k => $v)
{
	$tConst = 'SOCIETE_CREDIT_DOCS_EXPIRATION_'.strtoupper($k); 
	if (property_exists($conf->global,$tConst))
	{
		$expire[$k]  = $conf->global->$tConst;
	}
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$formfile = new FormFile($db);

$langs->loadLangs(array("companies", "other"));

$langs->loadLangs(array("companies", "other"));

$action = GETPOST('action', 'aZ09');
$id = (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));

switch($action)
{
	case 'delete':
		$del_file	= GETPOST('urlfile');
		$del_res	= false;
		if (file_exists(DOL_DATA_ROOT.'/societe/'.urldecode($del_file)))
		{
			$del_res = unlink(DOL_DATA_ROOT.'/societe/'.urldecode($del_file));
		}
		header('Location:'.$_SERVER['PHP_SELF'].'?socid='.$id.'&show='.GETPOST('show').'#row_'.GETPOST('show'));
		die();
		break;
	case 'upload';
		$target_dir = DOL_DATA_ROOT.'/societe/credit/'.$id.'/';
		
		if (!file_exists(DOL_DATA_ROOT.'/societe'))
		{
			if(!mkdir(DOL_DATA_ROOT.'/societe'))
			{
				die('No se pudo crear el directorio '.DOL_DATA_ROOT.'/societe');
			}
		}
		if (!file_exists(DOL_DATA_ROOT.'/societe/credit'))
		{
			if(!mkdir(DOL_DATA_ROOT.'/societe/credit'))
			{
				die('No se pudo crear el directorio '.DOL_DATA_ROOT.'/societe/credit');
			}
		}
		if (!file_exists(DOL_DATA_ROOT.'/societe/credit/'.$id))
		{
			if(!mkdir(DOL_DATA_ROOT.'/societe/credit/'.$id))
			{
				die('No se pudo crear el directorio '.DOL_DATA_ROOT.'/societe/credit/'.$id);
			}
		}
		if (!is_dir(DOL_DATA_ROOT.'/societe/credit/'.$id) || !is_writable(DOL_DATA_ROOT.'/societe/credit/'.$id))
		{
			die ('No se puede escribir al directorio '.DOL_DATA_ROOT.'/societe/credit/'.$id);
		}
		for ($fi=0,$fn=count($_FILES["userfile"]["tmp_name"]);$fi<$fn;$fi++)
		{
			$fext = substr( $_FILES["userfile"]["name"][$fi],strrpos($_FILES["userfile"]["name"][$fi],'.')+1);
		
			$fdate = 'file_doc_date_'.GETPOST('file_type');
				$target_file =	 $target_dir 
							.GETPOST('file_type').'_'
							.GETPOST($fdate.'year').'_'
							.substr('00'.GETPOST($fdate.'month'),-2).'_'
							.substr('00'.GETPOST($fdate.'day'),-2).'_'
							.(GETPOST('file_number')+$fi).'.'.$fext; 
			$uploadOk = 1;
			if (!move_uploaded_file($_FILES["userfile"]["tmp_name"][$fi], $target_file)) 
			{
				echo 'No se pudo mover el archivo ';
				echo '<pre>';
				print_r($_FILES);
				print_r($target_file);
				echo '</pre>';
				die();
			}
			else
			{
				//echo 'Guardado '.$target_file.'<br>';
			}
		}
		header('Location:'.$_SERVER['PHP_SELF'].'?socid='.$id.'&show='.GETPOST('show').'#row_'.GETPOST('show'));
		die();
		break;
	
	default:
		break;
}

// Security check
if ($user->socid > 0)
{
	unset($action);
	$socid = $user->socid;
}

$result = restrictedArea($user, 'societe', $id, '&societe');
$object = new Societe($db);
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
	$credit_dir = $conf->societe->multidir_output[$object->entity] . "/credit/" . get_exdir($object->id, 0, 0, 0, $object, 'thirdparty');
}




$form = new Form($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Documentos de Crédito");
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name.' - Documentos de Cr&eacute;dito';
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);
if ($object->id)
{
	/*
	 * Show tabs
	 */
	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($object);
	$creditfilearray=dol_dir_list($credit_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1);
	

	$form=new Form($db);

	dol_fiche_head($head, 'credit_docs', $langs->trans("ThirdParty"), -1, 'company');


	// Build file list
	
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}
	foreach($creditfilearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->socid?0:1), 'rowid', 'nom');

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	// Prefix
	if (!empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	{
		print '<tr><td class="titlefield">'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
	}

	if ($object->client)
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $object->code_client;
		if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($object->fournisseur)
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $object->code_fournisseur;
		if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	$modulepart = 'societe';
	$permission = $user->rights->societe->creer;
	$permtoedit = $user->rights->societe->creer;
	$param = '&id='.$object->id;
	include_once DOL_DOCUMENT_ROOT.'/societe/tpl/creditdocument.tpl.php';

}
else
{
	accessforbidden('', 0, 0);
}

// End of page
llxFooter();
$db->close();
