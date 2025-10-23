<?php
/* Copyright (C) 2010-2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2010-2014 		Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015           Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/core/modules/supplier_payment/doc/pdf_standard.modules.php
 *	\ingroup    fournisseur
 *	\brief      Class file to generate the supplier invoice payment file with the standard model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_payment/modules_supplier_payment.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionsnumtoword.lib.php';


/**
 *	Class to generate the supplier invoices payment file with the standard model
 */
class pdf_standard extends ModelePDFSuppliersPayments
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
	 * @var Societe
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB		$db     	Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "standard";
		$this->description = $langs->trans('DocumentModelStandardPDF');

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_multilang = 1;               // Dispo en plusieurs langues

		$this->franchise=!$mysoc->tva_assuj;

        // Define column position
		$this->posxdate=$this->marge_gauche+1;
		$this->posxreffacturefourn=35;
		$this->posxreffacture=45;
		$this->posxtype=117;
		$this->posxtotalht=157;
		$this->posxtva=80;
		$this->posxtotalttc=157;

		//if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) $this->posxtva=$this->posxup;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxreffacturefourn-=20;
			$this->posxreffacture-=20;
			$this->posxtype-=20;
			$this->posxtotalht-=20;
			$this->posxtva-=20;
			$this->posxtotalttc-=20;
		}

		$this->tva=array();
        $this->localtax1=array();
        $this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang, -2);    // By default if not defined
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Function to build pdf onto disk
     *
     *  @param		PaiementFourn		$object				Id of object to generate
     *  @param		Translate			$outputlangs		Lang output object
     *  @param		string				$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int					$hidedetails		Do not show line details
     *  @param		int					$hidedesc			Do not show desc
     *  @param		int					$hideref			Do not show ref
     *  @return		int										1=OK, 0=KO
     */
	public function write_file($object, $outputlangs = '', $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
        // phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "suppliers", "companies", "bills", "dict", "products"));

		$object->factures = array();

		if ($conf->fournisseur->payment->dir_output)
		{
			$object->fetch_thirdparty();
			/**
			 *	Supplier invoice list
			 */
			$sql = 'SELECT f.rowid, f.ref, f.datef, f.ref_supplier, f.total_ht, f.total_tva, f.total_ttc, pf.amount, f.rowid as facid, f.paye';
			$sql .= ', f.fk_statut, s.nom as name, s.rowid as socid';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf,'.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
			$sql .= ' WHERE pf.fk_facturefourn = f.rowid AND f.fk_soc = s.rowid';
			$sql .= ' AND pf.fk_paiementfourn = '.$object->id;
			$resql=$this->db->query($sql);
			if ($resql)
			{
				if ($this->db->num_rows($resql) > 0)
				{
					while($objp = $this->db->fetch_object($resql)) {
						$objp->type = $outputlangs->trans('SupplierInvoice');
						$object->lines[] = $objp;
					}
				}
			}

			$total = $object->montant;

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->fournisseur->payment->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$objectrefsupplier = dol_sanitizeFileName($object->ref_supplier);
                $dir = $conf->fournisseur->payment->dir_output.'/'.$objectref;
				$file = $dir . "/" . $objectref . ".pdf";
				if (! empty($conf->global->SUPPLIER_REF_IN_NAME)) $file = $dir . "/" . $objectref . ($objectrefsupplier?"_".$objectrefsupplier:"").".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

				$nblines = count($object->lines);

                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
	            if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;
                $pdf->SetAutoPageBreak(1, 0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Order")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right


                // New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Incoterm
				$height_incoterms = 0;

				$height_note=0;

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;
				
				$subtotal = 0;
				$totaliva = 0;
				$pay = 0;
				$credit_note = 0;
				// Loop on each lines
				for ($i = 0 ; $i < $nblines ; $i++)
				{
					$curY = $nexY+2;
					$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxdate-1;
					$showpricebeforepagebreak=1;

					$pdf->startTransaction();
					//pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxtva-$curX,3,$curX,$curY,$hideref,$hidedesc,1);
					$pdf->writeHTMLCell($this->posxtva-$curX, 4, $curX, $curY, '', 0, 1, false, true, 'J', true);
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						//pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxtva-$curX,4,$curX,$curY,$hideref,$hidedesc,1);
						$pdf->writeHTMLCell($this->posxtva-$curX, 4, $curX, $curY, '', 0, 1, false, true, 'J', true);
						$posyafter=$pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblines-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('', '', true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break

							// Allows data in the first page if description is long enough to break in multiples pages
							if(!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE))
								$showpricebeforepagebreak = 1;
							else
								$showpricebeforepagebreak = 0;
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}

					$nexY = $pdf->GetY();
                    $pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1);   // On repositionne la police par defaut

					// ref facture fourn
					$pdf->SetXY($curX, $curY);
					$pdf->MultiCell($this->posxreffacturefourn-0.8, 3, $object->lines[$i]->ref, 0, 'L', 0);

					// ref fourn
					$pdf->SetXY($this->posxreffacture-0.8, $curY);
					$pdf->MultiCell(72, 3, $object->lines[$i]->ref_supplier, 0, 'L', 0);

					// Num payment
					$pdf->SetXY($this->posxtype-0.8, $curY);
					$pdf->MultiCell(40, 3, $object->num_payment, 0, 'R', 0);

					// // Total ht (sin IVA)
					$subtotal += $object->lines[$i]->total_ht;
					// $pdf->SetXY($this->posxtotalht, $curY);
					// $pdf->MultiCell($this->posxtotalht-$this->posxup-0.8, 3, price($object->lines[$i]->total_ht), 0, 'R', 0);

					// // Total tva
					$totaliva += $object->lines[$i]->total_tva;
					// $pdf->SetXY($this->posxtva, $curY);
					// $pdf->MultiCell($this->posxtva-$this->posxup-0.8, 3, price($object->lines[$i]->total_tva), 0, 'R', 0);

					// Total ttc
					$pay += $object->lines[$i]->total_ttc;

					// Amount
                    $pdf->SetXY($this->posxtotalttc-0.8, $curY);
                    $pdf->MultiCell(42, 3, '$ '.price($object->lines[$i]->amount), 0, 'R', 0);

					$nexY+=2;

					// Buscar las notas de crédito relacionadas a la factura
					if($object->lines[$i]->amount < $object->lines[$i]->total_ttc && $object->lines[$i]->fk_statut==2){
						$sql2 = "SELECT lff.`ref` as ref, lff.ref_supplier ref_sup, lff.total_ttc as total 
						FROM llx_facture_fourn lff WHERE lff.fk_facture_source = '".$object->lines[$i]->rowid."' AND lff.`ref` LIKE 'SA%'";
						$resql2=$this->db->query($sql2);
						if ($resql2)
						{
							while($objnc = $this->db->fetch_object($resql2)) {
								
								// ref facture fourn
								$pdf->SetXY($curX, $curY+4);
								$pdf->MultiCell($this->posxreffacturefourn-0.8, 3, '- '.$objnc->ref, 0, 'L', 0);

								// ref fourn
								$pdf->SetXY($this->posxreffacture-0.8, $curY+4);
								$pdf->MultiCell(72, 3, '- '.$objnc->ref_sup, 0, 'L', 0);

								// Amount
								$pdf->SetXY($this->posxtotalttc-0.8, $curY+4);
								$pdf->SetTextColor(232, 11, 11);
								$pdf->MultiCell(42, 3, '$ '.price($objnc->total*-1), 0, 'R', 0);
								$credit_note += price($objnc->total*-1);

								$nexY+=2;
							}
						}
					}

					$nexY+=2;    // Add space between lines

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				// Affiche zone cheèque
				$posy=$this->_tableau_cheque($pdf, $object, $bottomlasttab, $outputlangs, $subtotal, $totaliva, $pay, $credit_note);

				// Affiche zone totaux
				//$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0)
				{
				    $this->error = $hookmanager->error;
				    $this->errors = $hookmanager->errors;
				}

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1;   // No error
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined", "SUPPLIER_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Show total to pay
	 *
	 *	@param	PDF				$pdf			Object PDF
	 *	@param  PaiementFourn	$object         Object PaiementFourn
	 *	@param	int				$posy			Position depart
	 *	@param	Translate		$outputlangs	Objet langs
	 *	@return int								Position pour suite
	 */
	protected function _tableau_cheque(&$pdf, $object, $posy, $outputlangs, $subtotal, $totaliva, $pay, $credit_note)
	{
        // phpcs:enable
		global $conf,$mysoc, $langs;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetFillColor(255, 255, 255);

		// Pagos efectuados
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell(30, 3, 'Pagos efectuados', 0, 'L', 1);

        $pdf->line($this->marge_gauche, $posy+4, 112, $posy+4);

		$pdf->SetXY($this->marge_gauche, $posy+4);
		$pdf->MultiCell(15, 3, 'Pago', 0, 'L', 1);

		$pdf->SetXY(27, $posy+4);
		$pdf->MultiCell(20, 3, 'Importe', 0, 'C', 1);

		$pdf->SetXY(50, $posy+4);
		$pdf->MultiCell(30, 3, 'Tipo', 0, 'L', 1);

		$pdf->SetXY(82, $posy+4);
		$pdf->MultiCell(15, 3, 'Núm.', 0, 'L', 1);

		$pdf->line($this->marge_gauche, $posy+8, 112, $posy+8);

		$pdf->SetXY($this->marge_gauche, $posy+8);
		$pdf->MultiCell(15, 3, date("d/m/Y", $object->datepaye), 0, 'L', 1);

		$pdf->SetXY(27, $posy+8);
		$pdf->MultiCell(20, 3, '$ '.price($object->amount), 0, 'C', 1);

		$pdf->SetXY(50, $posy+8);
		$pdf->MultiCell(30, 3, $langs->trans($object->type_label), 0, 'L', 1);

		$pdf->SetXY(82, $posy+8);
		$pdf->MultiCell(30, 3, $object->num_payment, 0, 'L', 1);

		$pdf->line($this->marge_gauche, $posy+14, 112, $posy+14);

		// Totales
		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetTextColor(3, 24, 62);
		$pdf->SetFillColor(241, 245, 252);
		$pdf->SetXY($this->marge_gauche+120, $posy);
		$pdf->MultiCell(30, 4, 'Subtotal', 0, 'L', 1);
		$pdf->SetXY($this->marge_gauche+150, $posy);
		$pdf->MultiCell(40, 4, price($subtotal), 0, 'R', 1);
		$posy+=5;
		
		$pdf->SetFillColor(235, 241, 250);
		$pdf->SetXY($this->marge_gauche+120, $posy);
		$pdf->MultiCell(30, 4, 'Total IVA %16', 0, 'L', 1);
		$pdf->SetXY($this->marge_gauche+150, $posy);
		$pdf->MultiCell(40, 4, price($totaliva), 0, 'R', 1);
		$posy+=5;

		$pdf->SetFillColor(231, 236, 245);
		$pdf->SetXY($this->marge_gauche+120, $posy);
		$pdf->MultiCell(30, 4, 'Total por pagar', 0, 'L', 1);
		$pdf->SetXY($this->marge_gauche+150, $posy);
		$pdf->MultiCell(40, 4, price($pay), 0, 'R', 1);
		$posy+=5;

		$pdf->SetFillColor(224, 229, 238);
		$pdf->SetTextColor(232, 11, 11);
		$pdf->SetXY($this->marge_gauche+120, $posy);
		$pdf->MultiCell(30, 4, 'Nota de crédito', 0, 'L', 1);
		$pdf->SetXY($this->marge_gauche+150, $posy);
		$pdf->MultiCell(40, 4, '- '.price($credit_note), 0, 'R', 1);
		$posy+=5;

		$pdf->SetTextColor(3, 24, 62);
		$pdf->SetFillColor(197, 202, 209);
		$pdf->SetXY($this->marge_gauche+120, $posy);
		$pdf->MultiCell(30, 4, 'Pagado', 0, 'L', 1);
		$pdf->SetXY($this->marge_gauche+150, $posy);
		$pdf->MultiCell(40, 4, price($object->amount), 0, 'R', 1);
		$posy+=5;

		$saldo = price($pay) - price($credit_note) - price($object->amount);
		$pdf->SetXY($this->marge_gauche+120, $posy);
		$pdf->MultiCell(30, 4, 'Saldo por pagar', 0, 'L', 1);
		$pdf->SetXY($this->marge_gauche+150, $posy);
		$pdf->MultiCell(40, 4, price($saldo), 0, 'R', 1);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		integer		$tab_top		Top position of table
	 *   @param		integer		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf,$mysoc;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

        // Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
		$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top);
		$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

		$pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(100, 100, 100)));
        $pdf->SetDrawColor(100,100, 100);
		$pdf->line($this->marge_gauche, $tab_top+4, $this->page_largeur - $this->marge_droite, $tab_top+4);
        $pdf->line($this->marge_gauche, $tab_top+4, $this->marge_gauche, $this->page_largeur);
       

		if (empty($hidetop))
        {
            $pdf->SetXY(10, $tab_top + 5);
            $pdf->MultiCell($this->posxreffacturefourn-0.8, 3, $outputlangs->transnoentities("Ref."), 0, 'L');
        }

		$pdf->line($this->posxreffacture-0.8, $tab_top+4, $this->posxreffacture-0.8, $this->page_largeur);

		if (empty($hidetop))
        {
            $pdf->SetXY($this->posxreffacture-0.8, $tab_top + 5);
            $pdf->MultiCell(72, 3, $outputlangs->transnoentities("Ref. Proveedor"), 0, 'L');
        }

		$pdf->line($this->posxtype-0.8, $tab_top+4, $this->posxtype-0.8, $this->page_largeur);

		if (empty($hidetop))
        {
            $pdf->SetXY($this->posxtype-0.8, $tab_top + 5);
            $pdf->MultiCell(40, 3, $outputlangs->transnoentities("Número de transferencia"), 0, 'L');
        }

		$pdf->line($this->posxtotalttc-0.8, $tab_top+4, $this->posxtotalttc-0.8, $this->page_largeur);

		if (empty($hidetop))
        {
            $pdf->SetXY($this->posxtotalttc-0.8, $tab_top + 5);
            $pdf->MultiCell(42, 3, $outputlangs->transnoentities("Importe"), 0, 'C');
        }
		
		$pdf->SetLineStyle(array('dash'=>0, 'color'=>array(100, 100, 100)));
        $pdf->SetDrawColor(128, 128, 128);
		$pdf->line($this->page_largeur - $this->marge_droite, $tab_top+4, $this->page_largeur - $this->marge_droite, $this->page_largeur);
		$pdf->line($this->marge_gauche, $tab_top+9, $this->page_largeur - $this->marge_droite, $tab_top+9);
		$pdf->line($this->marge_gauche, $this->page_largeur, $this->page_largeur - $this->marge_droite, $this->page_largeur);
		$pdf->SetFont('', '', $default_font_size - 1);
		

		// Output Rect
		//$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect takes a length in 3rd parameter and 4th parameter
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  FactureFournisseur		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs, $conf, $mysoc;

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "orders", "companies", "bills"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Do not add the BACKGROUND as this is for suppliers
		//pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-100;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetXY(100, $posy);
    	$pdf->MultiCell(100, 3, "Pago", 0, 'R');
		$pdf->SetXY(100, $posy+5);
    	$pdf->MultiCell(100, 3, "Ref: ".$object->ref, 0, 'R');
		$pdf->SetXY(100, $posy+10);
		$pdf->SetFont('', '', $default_font_size);
    	$pdf->MultiCell(100, 3, "Fecha de pago: ".date("d/m/Y", $object->datepaye), 0, 'R');
        /*
		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("SupplierInvoice")." ".$outputlangs->convToOutputCharset($object->ref), '', 'R');
		$posy+=1;

		if ($object->ref_supplier)
		{
    		$posy+=4;
			$pdf->SetFont('','B', $default_font_size);
    		$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
    		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefSupplier")." : " . $object->ref_supplier, '', 'R');
			$posy+=1;
		}

		$pdf->SetFont('','', $default_font_size - 1);

		if (! empty($conf->global->PDF_SHOW_PROJECT))
		{
			$object->fetch_projet();
			if (! empty($object->project->ref))
			{
        		$posy+=4;
				$pdf->SetXY($posx,$posy);
        		$langs->load("projects");
				$pdf->SetTextColor(0,0,60);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Project")." : " . (empty($object->project->ref)?'':$object->projet->ref), '', 'R');
			}
		}

		if ($object->date)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,"day",false,$outputlangs,true), '', 'R');
		}
		else
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(255,0,0);
			$pdf->MultiCell(100, 4, strtolower($outputlangs->transnoentities("OrderToProcess")), '', 'R');
		}

		if ($object->thirdparty->code_fournisseur)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("SupplierCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_fournisseur), '', 'R');
		}

		$posy+=1;
		$pdf->SetTextColor(0,0,60);

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
        */
		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty);

			// Show payer
			$posy=42;
			$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=40;

			// Show sender frame
			// Get warehouse
			$sql_w = "SELECT lff.fk_warehouse as warehouse FROM llx_facture_fourn lff WHERE lff.`ref` = '".$object->lines[0]->ref."'";
			$res_w = $this->db->query($sql_w);
			$warehouse= $this->db->fetch_object($res_w);
			
			// DATOS DE EMISOR
			$sqlrfc = "SELECT lcr.code as rfc FROM llx_entrepot le JOIN llx_c_rfc lcr ON le.fk_rfc = lcr.rowid WHERE le.rowid = ".$warehouse->warehouse."";
			$resrfc = $this->db->query($sqlrfc);
			$rfc= $this->db->fetch_object($resrfc);
			
			$sql_emisor = "SELECT lced.emisor_rfc as rfc, lced.razon_social as razon_social, lced.regimen as regimen, lcd.nom as estado, lced.codigo_postal as cp, lced.emisor_delompio as municipio, 
			lced.emisor_colonia as colonia, lced.emisor_calle as calle, lced.emisor_noext as numext, lced.emisor_noint as numint  FROM llx_cfdimx_emisor_datacomp lced 
			JOIN llx_c_departements lcd ON lced.estado = lcd.rowid WHERE lced.emisor_rfc = '".$rfc->rfc."'";
			$res_emisor = $this->db->query($sql_emisor);
			$emisor= $this->db->fetch_object($res_emisor);

			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy-5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("PayedBy").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 4, $emisor->razon_social, 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4,  $emisor->calle.' '.$emisor->numext.', '.$emisor->colonia, 0, 'L');
			$posy=$pdf->getY();

			if(!empty($emisor->numint)){
				$pdf->SetXY($posx+2, $posy);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(80, 4, 'Int: '.$emisor->numint, 0, 'L');
				$posy=$pdf->getY();
			}
			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, 'C.P: '.$emisor->cp, 0, 'L');
			$posy=$pdf->getY();

			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $emisor->municipio.', '.$emisor->estado, 0, 'L');
			$posy=$pdf->getY();

			$pdf->SetXY($posx+2, $posy+2);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, 'Teléfono: '.$mysoc->phone, 0, 'L');
			$posy=$pdf->getY();

			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, 'Correo: '.$mysoc->email, 0, 'L');
			$posy=$pdf->getY();
			// Payed
			$thirdparty = $object->thirdparty;
			$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client=pdf_build_address($outputlangs, $thirdparty, $mysoc, ((!empty($object->contact))?$object->contact:null), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox=90;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=42;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx+2, $posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("PayedTo").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient information
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $object->thirdparty->nom, 0, 'L');

			$posy = $pdf->getY();

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $object->thirdparty->address, 0, 'L');

			$posy = $pdf->getY();

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $object->thirdparty->zip, 0, 'L');

			$posy = $pdf->getY();

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $object->thirdparty->state.', '.$object->thirdparty->town, 0, 'L');

			$posy = $pdf->getY();

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy+4);
			$pdf->MultiCell($widthrecbox, 4, $object->thirdparty->phone, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $object->thirdparty->email, 0, 'L');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	FactureFournisseur		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		// Get warehouse
		$sql_w = "SELECT lff.fk_warehouse as warehouse FROM llx_facture_fourn lff WHERE lff.`ref` = '".$object->lines[0]->ref."'";
		$res_w = $this->db->query($sql_w);
		$warehouse= $this->db->fetch_object($res_w);
		// DATOS DE EMISOR
		$sqlrfc = "SELECT lcr.code as rfc FROM llx_entrepot le JOIN llx_c_rfc lcr ON le.fk_rfc = lcr.rowid WHERE le.rowid = ".$warehouse->warehouse."";
		$resrfc = $this->db->query($sqlrfc);
		$rfc= $this->db->fetch_object($resrfc);

		$sql_emisor = "SELECT lced.regimen as regimen FROM llx_cfdimx_emisor_datacomp lced WHERE lced.emisor_rfc = '".$rfc->rfc."'";
		$res_emisor = $this->db->query($sql_emisor);
		$emisor= $this->db->fetch_object($res_emisor);
		
		$sql_reg = "SELECT lccrf.label as regimen FROM llx_c_cfdimx_regimen_f lccrf WHERE lccrf.code = '".$emisor->regimen."'";
		$res_reg = $this->db->query($sql_reg);
		$regimen= $this->db->fetch_object($res_reg);

		$pdf->SetTextColor(39, 45, 78);
		$pdf->SetFont('', '', 7);
		$pdf->SetXY(5, 288);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite, 4, $regimen->regimen.' - R.F.C: '.$rfc->rfc, 0, 'C');
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'SUPPLIER_INVOICE_FREE_TEXT', '', $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
