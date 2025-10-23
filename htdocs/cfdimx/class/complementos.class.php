<?php
	//=====================================================================+
	// File name   : configuracion.class.php
	// Begin       : 2022-04-04
	// Last Update : 2022-04-04
	//
	// Description : Clase de Complementos de Factrua
	//
	//
	// Author: AURIBOX CONSULTING
	//
	// (c) Copyright:
	//               AURIBOX CONSULTING
	//=====================================================================+

	// require_once('lib/nusoap/lib/nusoap.php');
	require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
	// require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	class ComplementosCFDI{
		var $db;

		public $doli_version;

		public function __construct($db){
			global $conf;

		    $this->db = $db;
		    $this->doli_version = DOL_VERSION;
		}

		function cfdimx_docrel_prepare_head(){
            $h = 0;
            $head = array();

            $head[$h][0] = DOL_URL_ROOT.'/cfdimx/complementos_cfdimx.php?mod=cfdi_rel&facid='.GETPOST('facid');
            $head[$h][1] = "<strong>CFDIS Relacionados</strong>";
            $head[$h][2] = "uno";
            $h++;

			$archivos_complementos = DOL_DOCUMENT_ROOT.'/cfdimx/carta_porte.php';
			if(file_exists($archivos_complementos) == true){
				$head[$h][0] = DOL_URL_ROOT.'/cfdimx/complementos_cfdimx.php?mod=cce&facid='.GETPOST('facid');
				$head[$h][1] = "<strong>Comercio Exterior</strong>";
				$head[$h][2] = "dos";
				$h++;

				$head[$h][0] = DOL_URL_ROOT.'/cfdimx/complementos_cfdimx.php?mod=carta_porte&facid='.GETPOST('facid');
				$head[$h][1] = "<strong>Carta Porte</strong>";
				$head[$h][2] = "tres";
				$h++;
			}

            return $head;
        }

        public function complementosCFDI($facid){
			$complementos = null;

			$carta_porte     = "SELECT count(*) AS carta FROM ".MAIN_DB_PREFIX."cfdimx_facture_carta_porte WHERE facid = ".$facid;
			$res_carta_porte = $this->db->query($carta_porte);
			$num_carta_porte = $this->db->num_rows($res_carta_porte);

			if($num_carta_porte > 0){
				$obj_carta_porte    = $this->db->fetch_object($res_carta_porte);
				$val_carta_porteval = $obj_carta_porte->carta;
			}

			$comercio_exterior     = "SELECT count(*) AS cce FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero WHERE fk_facture = ".$facid;
			$res_comercio_exterior = $this->db->query($comercio_exterior);
			$num_comercio_exterior = $this->db->num_rows($res_comercio_exterior);

			if($num_comercio_exterior > 0){
				$obj_comercio_exterior = $this->db->fetch_object($res_comercio_exterior);
				$val_cce               = $obj_comercio_exterior->cce;
			}

			$cfdi_relacionados     = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_cfdi_relacionados WHERE fk_facture = ".$facid;
			$res_cfdi_relacionados = $this->db->query($cfdi_relacionados);
			$num_cfdi_relacionados = $this->db->num_rows($res_cfdi_relacionados);

			if($val_carta_porteval == 0 && $val_cce == 0 && $num_cfdi_relacionados == 0){

			}else{
				$complementos = array(
								'carta_porte' => $val_carta_porteval,
								'cce'         => $val_cce,
								'cfdi_rel'    => $num_cfdi_relacionados,
                                'fac_global'  => -1
							);
			}

			return $complementos;
		}

		public function getNomUrl($facid, $withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $addlinktonotes = 0, $save_lastsearch_value = -1, $target = '')
		{
			global $db, $langs, $conf, $user, $mysoc;

			$factura = new Facture($db);
			$factura->fetch($facid);

			if (!empty($conf->dol_no_mouse_hover)) {
				$notooltip = 1; // Force disable tooltips
			}

			$result = '';

			$url = DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$factura->id;

			if (!$user->rights->facture->lire) {
				$option = 'nolink';
			}

			if ($option !== 'nolink') {
				// Add param to save lastsearch_values or not
				$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
				if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
					$add_save_lastsearch_values = 1;
				}
				if ($add_save_lastsearch_values) {
					$url .= '&save_lastsearch_values=1';
				}
			}

			if ($short) {
				return $url;
			}

			$picto = $factura->picto;
			// if ($factura->type == $factura->self::TYPE_REPLACEMENT) {
			// 	$picto .= 'r'; // Replacement invoice
			// }
			// if ($factura->type == $factura->self::TYPE_CREDIT_NOTE) {
			// 	$picto .= 'a'; // Credit note
			// }
			// if ($factura->type == $factura->self::TYPE_DEPOSIT) {
			// 	$picto .= 'd'; // Deposit invoice
			// }
			$label = '';

			if ($user->rights->facture->lire) {
				$label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->trans("Invoice").'</u>';
				// if ($factura->type == $factura->self::TYPE_REPLACEMENT) {
				// 	$label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("ReplacementInvoice").'</u>';
				// }
				// if ($factura->type == $factura->self::TYPE_CREDIT_NOTE) {
				// 	$label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("CreditNote").'</u>';
				// }
				// if ($factura->type == $factura->self::TYPE_DEPOSIT) {
				// 	$label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("Deposit").'</u>';
				// }
				// if ($factura->type == $factura->self::TYPE_SITUATION) {
				// 	$label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("InvoiceSituation").'</u>';
				// }
				if (isset($factura->statut) && isset($factura->alreadypaid)) {
					$label .= ' '.$factura->getLibStatut(5, $factura->alreadypaid);
				}
				if (!empty($factura->ref)) {
					$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$factura->ref;
				}
				if (!empty($factura->ref_client)) {
					$label .= '<br><b>'.$langs->trans('RefCustomer').':</b> '.$factura->ref_client;
				}
				if (!empty($factura->date)) {
					$label .= '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($factura->date, 'day');
				}
				if (!empty($factura->total_ht)) {
					$label .= '<br><b>'.$langs->trans('AmountHT').':</b> '.price($factura->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
				}
				if (!empty($factura->total_tva)) {
					$label .= '<br><b>'.$langs->trans('AmountVAT').':</b> '.price($factura->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
				}
				if (!empty($factura->total_localtax1) && $factura->total_localtax1 != 0) {		// We keep test != 0 because $this->total_localtax1 can be '0.00000000'
					$label .= '<br><b>'.$langs->transcountry('AmountLT1', $mysoc->country_code).':</b> '.price($factura->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency);
				}
				if (!empty($factura->total_localtax2) && $factura->total_localtax2 != 0) {
					$label .= '<br><b>'.$langs->transcountry('AmountLT2', $mysoc->country_code).':</b> '.price($factura->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency);
				}
				if (!empty($factura->total_ttc)) {
					$label .= '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($factura->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
				}
				if ($moretitle) {
					$label .= ' - '.$moretitle;
				}
			}

			$linkclose = ($target ? ' target="'.$target.'"' : '');
			if (empty($notooltip) && $user->rights->facture->lire) {
				if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
					$label = $langs->trans("Invoice");
					$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
				}
				$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
				$linkclose .= ' class="classfortooltip"';
			}

			$linkstart = '<a href="'.$url.'"';
			$linkstart .= $linkclose.'>';
			$linkend = '</a>';

			if ($option == 'nolink') {
				$linkstart = '';
				$linkend = '';
			}

			$result .= $linkstart;
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
			if ($withpicto != 2) {
				$result .= ($max ?dol_trunc($factura->ref, $max) : $factura->ref);
			}
			$result .= $linkend;

			if ($addlinktonotes) {
				$txttoshow = ($user->socid > 0 ? $factura->note_public : $factura->note_private);
				if ($txttoshow) {
					//$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow, 1);
					$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.$txttoshow;
					$result .= ' <span class="note inline-block">';
					$result .= '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$factura->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow, 1, 1).'">';
					$result .= img_picto('', 'note');
					$result .= '</a>';
					//$result.=img_picto($langs->trans("ViewNote"),'object_generic');
					//$result.='</a>';
					$result .= '</span>';
				}
			}

			global $action, $hookmanager;
			$hookmanager->initHooks(array('invoicedao'));
			$parameters = array('id'=>$factura->id, 'getnomurl'=>$result, 'notooltip' => $notooltip, 'addlinktonotes' => $addlinktonotes, 'save_lastsearch_value'=> $save_lastsearch_value, 'target' => $target);
			$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $factura, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook > 0) {
				$result = $hookmanager->resPrint;
			} else {
				$result .= $hookmanager->resPrint;
			}

			return $result;
		}
    }
?>