<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Pierre Ardoin      	<mapiolca@me.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/propale/doc/pdf_azur.modules.php
 *	\ingroup    propale
 *	\brief      File of Class to generate PDF proposal with Azur template
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/holiday/modules_holiday.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';



/**
 *	Class to generate PDF proposal Azur
 */
class pdf_dias extends ModelePDFHoliday
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short text)
     */
    public $description;

    /**
     * @var string Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

    /**
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
    public $phpmin = array(5, 5);

    /**
     * Dolibarr version of the loaded document
     * @var string
     */
    public $version = 'dolibarr';

    /**
     * @var int page_largeur
     */
    public $page_largeur;

    /**
     * @var int page_hauteur
     */
    public $page_hauteur;

    /**
     * @var array format
     */
    public $format;

    /**
     * @var int marge_gauche
     */
    public $marge_gauche;

    /**
     * @var int marge_droite
     */
    public $marge_droite;

    /**
     * @var int marge_haute
     */
    public $marge_haute;

    /**
     * @var int marge_basse
     */
    public $marge_basse;

    /**
     * Issuer
     * @var Societe object that emits
     */
    public $emetteur;
	


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "dias";
		$this->description = $langs->trans('dias');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        // Retrieves transmitter
        $this->emetteur=$mysoc;
        if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang, -2);    // By default if not defined

	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int             				1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		
        // phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		if (!is_object($outputlangs)) $outputlangs = $langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "propal", "products","holiday"));

		$nblines = 10;

			$deja_regle = 0;

			// Definition of $dir and $file

            $objectref = dol_sanitizeFileName($object->ref);
            $dir =      $conf->holiday->dir_output."/".$objectref;
            $file = $dir."/".$objectref.".pdf";


			if (!file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (!is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
                $pdf = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
	            $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                	$pagecount = $pdf->setSourceFile($conf->mycompany->multidir_output[$object->entity].'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();

				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("dias"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfCommercialProposalTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right



				// New page
				$pdf->AddPage();


                $heightforinfotot = 40; // Height reserved to output the info and total part

                //print $heightforinfotot + $heightforsignature + $heightforfreetext + $heightforfooter;exit;

				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);




					//$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, 10 + 10 + 10 + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.


					$valideur = new User($db);
					$valideur->fetch($object->fk_validator);
		
					$userRequest = new User($db);
					$userRequest->fetch($object->fk_user);

					$userCreate = new User($db);
                	$userCreate->fetch($object->fk_user_create);

					
					
					
					//Solicitante
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 60);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Solicitante: ");
					$pdf->MultiCell(25, 4, $title, '0', 'L');


					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+33, 60);
					$pdf->SetTextColor(0, 0, 0);
					$title = $userCreate->getFullName($outputlangs);
					$pdf->MultiCell(120, 4, $title, '0', 'L');



					$typeleaves = $object->getTypes(1, -1);
		            $labeltoshow = (($typeleaves[$object->fk_type]['code'] && $langs->trans($typeleaves[$object->fk_type]['code']) != $typeleaves[$object->fk_type]['code']) ? $langs->trans($typeleaves[$object->fk_type]['code']) : $typeleaves[$object->fk_type]['label']);
		        	empty($labeltoshow) ? $langs->trans("TypeWasDisabledOrRemoved", $object->fk_type) : $labeltoshow;

					//type
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 70);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Tipo: ");
					$pdf->MultiCell(15, 4, $title, '0', 'L');


					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+21, 70);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities($labeltoshow);
					$pdf->MultiCell(40, 4, $title, '0', 'L');



					//Numero de dias
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 80);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Numero de días: ");
					$pdf->MultiCell(35, 4, $title, '0', 'L');

					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+42, 80);
					$pdf->SetTextColor(0, 0, 0);
					$title = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday);
					$pdf->MultiCell(30, 4, $title, '0', 'L');

					//Fecha de Inicio
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 90);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Fecha de Inicio : ");
					$pdf->MultiCell(35, 4, $title, '0', 'L');

					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+42, 90);
					$pdf->SetTextColor(0, 0, 0);
					$title = dol_print_date($object->date_debut, "dayhour");
					$pdf->MultiCell(60, 4, $title, '0', 'L');

					//Fecha de Finalizacion
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 100);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Fecha de Finalizacion : ");
					$pdf->MultiCell(50, 4, $title, '0', 'L');

					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+54, 100);
					$pdf->SetTextColor(0, 0, 0);
					$title = dol_print_date($object->date_fin, "dayhour");
					$pdf->MultiCell(60, 4, $title, '0', 'L');

					//Descripcion
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 110);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Descripcion : ");
					$pdf->MultiCell(30, 4, $title, '0', 'L');

					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+36, 110);
					$pdf->SetTextColor(0, 0, 0);
					$title = $object->description;
					$pdf->MultiCell(140, 25, $title, '0', 'L');
					$pdf->startTransaction();
                    
					
					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+35, 145);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Solicitado por: ");
					$pdf->MultiCell(30, 4, $title, '0', 'L');


					$pdf->SetFont('', 'B', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+125, 145);
					$pdf->SetTextColor(0, 0, 0);
					$title = $outputlangs->transnoentities("Aprobado por: ");
					$pdf->MultiCell(30, 4, $title, '0', 'L');

                $signature = $conf->holiday->dir_output."/".$object->ref."/firma1/signature.png";
                    # Si existe el archivo y se puede leer
                    if (file_exists($signature) && is_readable($signature)) {
                        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                        $dim = pdf_getHeightForLogo($signature, false, 22, true);


                        $pdf->Image($signature, $this->marge_gauche+25, 155, 0, $dim['height']);

                        # Se elimina la firma para que no se quede en el sistema.
                        //dol_delete_file($signature, 0, 0, 0, $object, false, 1);
                    }

					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+15, 175);
					$pdf->SetTextColor(0, 0, 0);
					$title = "________________________________";
					$pdf->MultiCell(80, 4, $title, '0', 'L');

                    $signature = $conf->holiday->dir_output."/".$object->ref."/firma2/signature.png";
                    # Si existe el archivo y se puede leer
                    if (file_exists($signature) && is_readable($signature)) {
                        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

                        $dim = pdf_getHeightForLogo($signature, false, 22, true);


                        $pdf->Image($signature, $this->marge_gauche+120, 155, 0, $dim['height']);

                        # Se elimina la firma para que no se quede en el sistema.
                        //dol_delete_file($signature, 0, 0, 0, $object, false, 1);
                    }
					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+105, 175);
					$pdf->SetTextColor(0, 0, 0);
					$title = "________________________________";
					$pdf->MultiCell(80, 4, $title, '0', 'L');


					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+10, 180);
					$pdf->SetTextColor(0, 0, 0);
					$title = $userCreate->getFullName($outputlangs);
					$pdf->MultiCell(80, 4, $title, '0', 'C');

					$pdf->SetFont('', '', $default_font_size +1 );
       				$pdf->SetXY($this->marge_gauche+100, 180);
					$pdf->SetTextColor(0, 0, 0);
					$title = $valideur->getFullName($outputlangs);
					$pdf->MultiCell(80, 4, $title, '0', 'C');
					


				// Customer signature area
				if (empty($conf->global->PROPAL_DISABLE_SIGNATURE))
				{
				    //$posy = $this->_signature_area($pdf, $object, $posy, $outputlangs);
				}



				$pdf->Close();

				$pdf->Output($file, 'F');

				//Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0)
				{
				    $this->error = $hookmanager->error;
				    $this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1; // No error
			}
			else
			{
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}

	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "propal", "companies", "bills"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//  Show Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->PROPALE_DRAFT_WATERMARK)))
		{
            pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->PROPALE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO))
		{
			if ($this->emetteur->logo)
			{
				$logodir = $conf->mycompany->dir_output;
				if (! empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO))
				{
					$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
				}
				else {
					$logo = $logodir.'/logos/'.$this->emetteur->logo;
				}
				if (is_readable($logo))
				{
				    $height = pdf_getHeightForLogo($logo);
				    $pdf->Image($logo, $this->marge_gauche, $posy, 50, 40); // width=0 (auto)
				}
				else
				{
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			}
			else
			{
				$text = $this->emetteur->name;
				$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}
        $pdf->SetFont('', '', $default_font_size +1 );
        $pdf->SetXY(65, 27);
        $pdf->SetTextColor(0, 0, 0);
        $title = $outputlangs->transnoentities("DERMAGLOBAL");
        $pdf->MultiCell(130, 4, $title, '0', 'L');


        $pdf->SetFont('', 'B', $default_font_size  );
        $pdf->SetXY(95, 35);
        $pdf->SetTextColor(0, 0, 0);
        $title = $outputlangs->transnoentities("SOLICITUD DE DIAS LIBRES");
        $pdf->MultiCell(60, 4, $title, '0', 'C');
        
        $pdf->SetFont('', 'B', $default_font_size );
        $pdf->SetXY($posx, 42);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("Fecha de Creación")." : ".dol_print_date($object->date_create, "dayhour", false, $outputlangs, true), '', 'R');



		$pdf->SetTextColor(0, 0, 0);
		return "1";
	}




}
