<?php
/* Copyright (C) 2017 Maxime Kohlhaas <support@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See theg
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/modules/expensereport/mod_expensereport_email.php
 * \ingroup    expensereport
 * \brief      Fichier contenant la classe du modele de numerotation de reference de note de frais Email
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';


/**
 *	Class to manage expense report numbering rules Email
 */
class mod_expensereport_email extends ModeleNumRefExpenseReport
{
	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see $name
	 */
	public $nom = 'Email';

	/**
	 * @var string model name
	 */
	public $name = 'Email';


    /**
     *  Returns the description of the numbering model
     *
     *  @return     string      Texte descripif
     */
    public function info()
    {
        global $langs;
        return $langs->trans("DesEmailGasto");
    }
}
