<?php
/* Copyright (C) 2020 Daniel Molina <danmnvx@gmail.com>
 */

/**
 *	\file       htdocs/core/modules/pos/modules_pos.php
 *	\ingroup    pos
 *	\brief      File that contains parent class for pos models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';   // Required because used in classes that inherit


/**
 *	Parent class of pos document generators
 */
abstract class ModelePDFPos extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
    public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
		global $conf;

		$type='pos';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db, $type, $maxfilenamelength);

		return $liste;
	}
}

/**
 *  Parent class of pos reference numbering templates
 */
abstract class ModeleNumRefPos
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * Return if a module can be used or not
	 *
	 * @return	boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 * Renvoi la description par defaut du modele de numerotation
	 *
	 * @return    string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("pos@pos");
		return $langs->trans("NoDescription");
	}

	/**
	 * Return an example of numbering
	 *
	 * @return	string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load("pos@pos");
		return $langs->trans("NoExample");
	}

	/**
     *  Checks if the numbers already in force in the data base do not
     *  cause conflicts that would prevent this numbering from working.
	 *
	 * @return	boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		return true;
	}

	/**
	 * Renvoi prochaine valeur attribuee
	 *
	 * @param	Societe		$objsoc		Objet societe
	 * @param   Facture		$facture	Objet facture
	 * @return  string      			Value
	 */
	public function getNextValue($objsoc, $facture)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * Renvoi version du modele de numerotation
	 *
	 * @return    string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		elseif ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("NotAvailable");
	}
}
