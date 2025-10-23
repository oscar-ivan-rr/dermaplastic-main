<?php
/* Copyright (C) 2017 	Laurent Destailleur <eldy@stocks.sourceforge.net>
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
 *	\file       htdocs/core/modules/stock/doc/pdf_stdmovement.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build PDF documents for stocks movements
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class PDFMasiveStockCorrection extends ModelePDFMovement
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
     * @var Societe Issuer
     */
    public $emetteur;


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db, $large)
    {
        global $conf, $langs, $mysoc;

        // Load traductions files required by page
        $langs->loadLangs(array("main", "companies"));

        $this->db = $db;
        $this->name = "stdmouvement";
        $this->description = $langs->trans("DocumentModelStandardPDF");

        // Page size for A4 format
        $this->type = 'pdf';
        $this->page_largeur = 80;
        $this->page_hauteur = $large;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 5;
        $this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 5;
        $this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
        $this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

        $this->option_logo = 1; // Affiche logo
        $this->option_codestockservice = 0; // Affiche code stock-service
        $this->option_multilang = 1; // Dispo en plusieurs langues
        $this->option_freetext = 0; // Support add of a personalised text

        // Recupere emetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined

        // Define position of columns
        $this->wref = 15;
        //$this->posxidref = $this->marge_gauche;
        $this->posxdatemouv = $this->marge_gauche;
        $this->posxdesc = 37;
        $this->posxlabel = 50;
        $this->posxtva = 80;
        $this->posxqty = 105;
        $this->posxup = 119;
        $this->posxunit = 136;
        $this->posxdiscount = 167;
        $this->postotalht = 180;

        if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) || !empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) $this->posxtva = $this->posxup;
        $this->posxpicture = $this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH); // width of images
        if ($this->page_largeur < 210) // To work with US executive format
        {
            $this->posxpicture -= 20;
            $this->posxtva -= 20;
            $this->posxup -= 20;
            $this->posxqty -= 20;
            $this->posxunit -= 20;
            $this->posxdiscount -= 20;
            $this->postotalht -= 20;
        }
        $this->tva = array();
        $this->localtax1 = array();
        $this->localtax2 = array();
        $this->atleastoneratenotnull = 0;
        $this->atleastonediscount = 0;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Function to build a document on disk using the generic odt module.
     *
     *	@param		StockMovements	$object				Object source to build document
     *	@param		Translate		$outputlangs		Lang output object
     * 	@param		string			$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int				$hidedetails		Do not show line details
     *  @param		int				$hidedesc			Do not show desc
     *  @param		int				$hideref			Do not show ref
     *	@return		int         						1 if OK, <=0 if KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        // phpcs:enable
        global $user, $langs, $conf, $mysoc, $db, $hookmanager;

        if (!is_object($outputlangs)) $outputlangs = $langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (!empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output = 'ISO-8859-1';

        // Load traductions files required by page
        $outputlangs->loadLangs(array("main", "dict", "companies", "bills", "stocks", "orders", "deliveries"));

        if (!is_array($object))
        {
            $object = array($object);
        }

        $prods = array();
        foreach($object as $elm)
        {
            if (!in_array($elm->product_id,$prods))
            {
                $prods[] = $elm->product_id;
            }
        }
        sort($prods);

        // Definition of $dir and $file
        $dir = $conf->stock->dir_output."/stock_correction/". dol_sanitizeFileName($object[0]->code);
        $file = $dir."/".dol_sanitizeFileName($object[0]->code).".pdf";
        $ssFile = $dir."/".dol_sanitizeFileName($object[0]->code)."_source.png";
        $stFile = $dir."/".dol_sanitizeFileName($object[0]->code)."_target.png";

        if (!file_exists($dir))
        {
            if (dol_mkdir($dir) < 0)
            {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return -1;
            }
        }

        if (file_exists($dir))
        {
            // Add pdfgeneration hook
            global $action;
            // Create pdf instance
            $pdf = pdf_getInstance($this->format);
            $default_font_size = 10; // Must be after pdf_getInstance
            $pdf->SetAutoPageBreak(1, 0);
            $heightforinfotot = 40; // Height reserved to output the info and total part
            $heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
            $heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
            if (class_exists('TCPDF'))
            {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }
            $pdf->SetFont(pdf_getPDFFont($outputlangs));
            // Set path to the background PDF File
            if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
            {
                $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                $tplidx = $pdf->importPage(1);
            }

            $pdf->Open();
            $pagenb = 0;
            $pdf->SetDrawColor(128, 128, 128);

            $pdf->SetTitle($outputlangs->convToOutputCharset($object[0]));
            $pdf->SetSubject($outputlangs->transnoentities("Stock"));
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
            $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Stock")." ".$outputlangs->convToOutputCharset($object->label));
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

            $tab_top = $pdf->GetY(); // Added + 10 to create some space
            $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? $tab_top : 5);
            $tab_height = 130;

            /* ************************************************************************** */
            /*                                                                            */
            /* Affichage de la liste des produits du MouvementStock                       */
            /*                                                                            */
            /* ************************************************************************** */

            $nexY = $pdf->GetY() + 6;

            $totalunit = 0;

            $num = count($object);
            $i = 0;
            $nblines = $num;
            $printed = array();
            $totalneg = 0;
            $totalposi = 0;
            foreach($object as $objp)
            {
                // Multilangs
                if (
                    isset($printed[$objp->code])
                    && isset($printed[$objp->code][$objp->product_id])
                    && in_array(abs($objp->qty), $printed[$objp->code][$objp->product_id])
                ) {
                    continue;
                }
                $printed[$objp->code][$objp->product_id][] = abs($objp->qty);
                if (!empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
                {
                    $sql = "SELECT label";
                    $sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
                    $sql .= " WHERE fk_product=".$objp->product_id;
                    $sql .= " AND lang='".$langs->getDefaultLang()."'";
                    $sql .= " LIMIT 1";

                    $result = $db->query($sql);
                    if ($result)
                    {
                        $objtp = $db->fetch_object($result);
                        if ($objtp->label != '') $objp->product_label = $objtp->label;
                    }
                }

                $curY = $nexY;
                $pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
                $pdf->SetTextColor(0, 0, 0);

                $pdf->setTopMargin($tab_top_newpage);
                $pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
                $pageposbefore=$pdf->getPage();

                // Description of product line
                $curX = $this->posxdesc-1;

                $showpricebeforepagebreak=1;

                $pdf->startTransaction();
                pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxtva-$curX, 3, $curX, $curY, $hideref, $hidedesc);
                $pageposafter=$pdf->getPage();
                if ($pageposafter > $pageposbefore)	// There is a pagebreak
                {
                    $pdf->rollbackTransaction(true);
                    $pageposafter=$pageposbefore;
                    //print $pageposafter.'-'.$pageposbefore;exit;
                    $pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
                    pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxtva-$curX, 4, $curX, $curY, $hideref, $hidedesc);
                    $pageposafter=$pdf->getPage();
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

                $pdf->SetFont('', '', $default_font_size - 2);   // On repositionne la police par defaut

                // Ref.
                $pdf->SetXY($this->marge_gauche, $curY);
                $pdf->MultiCell(30, 3, $objp->product_ref, 0, 'L');
                $nexY = $pdf->GetY(); // Add space between lines

                // Qty
                $valtoshow = price2num($objp->qty, 'MS');
                $totalunit += $objp->qty;
                $pdf->SetXY(35, $curY);
                $pdf->MultiCell(10, 3, $objp->qty, 0, 'R', 0);

                //Costo
                $sqlP = "SELECT cost_price FROM " . MAIN_DB_PREFIX . "product WHERE ref = '" . $this->db->escape($objp->product_ref) . "'";
                $resqlP = $this->db->query($sqlP);
                $pprice = $this->db->fetch_object($resqlP);
                $pdf->SetXY(45, $curY);
                $pdf->MultiCell(15, 3, price($pprice->cost_price), 0, 'R', 0);

                //Importe
                if ($objp->qty < 0) $totalneg += $objp->qty * $pprice->cost_price;
                else $totalposi += $objp->qty * $pprice->cost_price;
                $pdf->SetXY(60, $curY);
                $pdf->MultiCell(15, 3, price($objp->qty * $pprice->cost_price), 0, 'R', 0);

                // Add line
                if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
                    $pdf->setPage($pageposafter);
                    $pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
                    //$pdf->SetDrawColor(190,190,200);
                    $pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
                    $pdf->SetLineStyle(array('dash' => 0));
                }

                $nexY += 1.5; // Add space between lines

                // Detect if some page were added automatically and output _tableau for past pages
                while ($pagenb < $pageposafter)
                {
                    $pdf->setPage($pagenb);
                    if ($pagenb == 1)
                    {
                        $this->_tableau($pdf, $object[0], $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
                    }
                    else
                    {
                        $this->_tableau($pdf, $object[0], $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
                    }
                    //$this->_pagefoot($pdf, $object, $outputlangs, 1);
                    $pagenb++;
                    $pdf->setPage($pagenb);
                    $pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                }
                if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak)
                {
                    if ($pagenb == 1)
                    {
                        $this->_tableau($pdf, $object[0], $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
                    }
                    else
                    {
                        $this->_tableau($pdf, $object[0], $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
                    }
                    //$this->_pagefoot($pdf, $object, $outputlangs, 1);
                    // New page
                    $pdf->AddPage();
                    if (!empty($tplidx)) $pdf->useTemplate($tplidx);
                    $pagenb++;
                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                }
                $i++;
            }

            /**
             * footer table
             */
            $curY = $nexY;

            $pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(220, 26, 26)));
            $pdf->line($this->marge_gauche, $curY, $this->page_largeur - $this->marge_droite, $curY);
            $pdf->SetLineStyle(array('dash'=>0));

            $pdf->SetFont('', 'B');
            $pdf->SetXY(30, $curY);
            $pdf->MultiCell(15, 3, 'Total: '.$totalunit, 0, 'R', 0);

            $pdf->SetFont('', 'B', $default_font_size - 1);
            $pdf->SetTextColor(0, 0, 120);

            if (file_exists($ssFile) && is_readable($ssFile))
            {
                $size = getimagesize($ssFile);
                $height=15;
                $width = intval($height * $size[0] / $size[1]);
                $posy = $pdf->GetY();
                $pdf->Image($ssFile, ((70-$width)/2)+10, $posy, 0, $height);
            }

            $curY = $pdf->GetY();
            $pdf->line(10, $curY +20, 70, $curY +20);
            $curY += 20;
            $pdf->SetXY(5, $curY);
            $pdf->MultiCell(70, 3, 'Nombre y Firma' , 0, 'C');

            $curY = $tab_top + 7;
            $nexY = $tab_top + 7;

            $tab_top = $tab_top_newpage;

            // Show square
            if ($pagenb == 1)
            {
                $this->_tableau($pdf, $object[0], $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
            }
            else
            {
                $this->_tableau($pdf, $object[0], $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
            }

            // Pied de page
            if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

            $pdf->Close();

            $pdf->Output($file, 'F');

            // Add pdfgeneration hook
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
     *   Show table for lines
     *
     *   @param		TCPDF		$pdf     		Object PDF
     *   @param		string		$tab_top		Top position of table
     *   @param		string		$tab_height		Height of table (rectangle)
     *   @param		int			$nexY			Y (not used)
     *   @param		Translate	$outputlangs	Langs object
     *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
     *   @param		int			$hidebottom		Hide bottom bar of array
     *   @param		string		$currency		Currency code
     *   @return	void
     */
    protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
    {
        global $conf;

        // Force to disable hidetop and hidebottom
        if ($hidetop) $hidetop = -1;

        $currency = !empty($currency) ? $currency : $conf->currency;
        $default_font_size = 10;

        // Amount in (at tab_top - 1)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        if (empty($hidetop))
        {
            //$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
            $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
            $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

            //$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
            if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
        }

        // Configuración inicial del PDF
        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetFont('', 'B', $default_font_size - 3);
        $pdf->SetTextColor(0, 0, 120);

        // Dibujar la primera línea roja
        $pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(220, 26, 26)));
        $pdf->SetDrawColor(220, 26, 26);
        $pdf->line($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite, $tab_top);
        $pdf->SetLineStyle(array('dash'=>0));
        $pdf->SetDrawColor(128, 128, 128);

        // Dibujar las celdas
        $cells = array(
            array($this->marge_gauche, 30, "Ref. Producto"),
            array(35, 10, "Cant."),
            array(45, 15, "Costo"),
            array(60, 15, "Importe")
        );

        foreach ($cells as $cell) {
            if (empty($hidetop) || $cell[0] > 35) {
                $pdf->SetXY($cell[0], $tab_top + 1);
                if ($cell[2] == 'Ref. Producto') $pdf->MultiCell($cell[1], 2, $outputlangs->transnoentities($cell[2]), '', 'L');
                else $pdf->MultiCell($cell[1], 2, $outputlangs->transnoentities($cell[2]), '', 'R');
            }
        }

        // Dibujar la segunda línea roja
        $pdf->SetLineStyle(array('dash'=>'0', 'color'=>array(220, 26, 26)));
        $pdf->SetDrawColor(220, 26, 26);
        $pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);
        $pdf->SetLineStyle(array('dash'=>0));
        $pdf->SetDrawColor(128, 128, 128);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     *  Show top header of page.
     *
     *  @param	TCPDF		$pdf     		Object PDF
     *  @param  Object		$object     	Object to show
     *  @param  int	    	$showaddress    0=no, 1=yes
     *  @param  Translate	$outputlangs	Object lang for output
     *  @param	string		$titlekey		Translation key to show as title of document
     *  @return	void
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "")
    {
        global $conf, $langs, $db, $hookmanager;

        // Load traductions files required by page
        $outputlangs->loadLangs(array("main", "propal", "companies", "bills", "orders", "stocks"));

        $default_font_size = 10;

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        // Show Draft Watermark

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $posy=$this->marge_haute;
        $posx=$this->page_largeur-$this->marge_droite-100;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        $logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
        if ($this->emetteur->logo)
        {
            if (is_readable($logo))
            {
                $height=pdf_getHeightForLogo($logo);
                $pdf->Image($logo, 24, $posy, 32, 32);	// width=0 (auto)
            }
            else
            {
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', 'B', $default_font_size -2);
                $pdf->MultiCell(70, 3, $outputlangs->transnoentities("LogoNotFound", $logo), 0, 'L');
                // $pdf->MultiCell(70, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        }
        else
        {
            $text=$this->emetteur->name;
            $pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
        }
        $posy += $height;
        $posy += $pdf->GetY();
        $posx = 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetFont('', '', $default_font_size); 
        $pdf->MultiCell(70, 3, 'Corrección de Stock', '', 'L');

        $posy = $pdf->GetY()+2;
        $pdf->SetXY(5, $posy);
        $pdf->SetFont('', '', $default_font_size);
        setlocale(LC_TIME,'es_MX');
        $pdf->MultiCell(70, 3, 'Fecha: '.dol_print_date(strtotime($object[0]->datem), 'pdf_format', 'tzuserrel'), '', 'J');

        // Obtener el almacén de origen
        $sql = "SELECT le.ref as source  FROM ".MAIN_DB_PREFIX."stock_mouvement lsm JOIN ".MAIN_DB_PREFIX."entrepot le ON lsm.fk_entrepot = le.rowid WHERE lsm.inventorycode = " . $object[0]->code;
        $result = $db->query($sql);
        if ($result)
        {
            $entrepot_source = $db->fetch_object($result);
        }
        $posy = $pdf->GetY()+2;
        $pdf->SetXY(5, $posy);
        $pdf->MultiCell(70, 3, 'Almacén: '.$entrepot_source->source, '', 'L');

        $posy = $pdf->GetY()+2;
        $pdf->SetXY(5, $posy);
        $pdf->MultiCell(70, 3, 'Mov: '.$object[0]->code, '', 'L');

        $posy += 2;

        $pdf->SetTextColor(0, 0, 0);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     *  Show footer of page. Need this->emetteur object
     *
     *  @param	TCPDF		$pdf     			PDF
     *  @param	Object		$object				Object to show
     *  @param	Translate	$outputlangs		Object lang for output
     *  @param	int			$hidefreetext		1=Hide free text
     *  @return	int								Return height of bottom margin including footer text
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        global $conf;
        $showdetails = $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
        return pdf_pagefoot($pdf, $outputlangs, 'PRODUCT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
    }
}