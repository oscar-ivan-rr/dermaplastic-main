<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 * 	\defgroup   mymodule     Module MyModule
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/mymodule/core/modules directory.
 *  \file       htdocs/mymodule/core/modules/modMyModule.class.php
 *  \ingroup    mymodule
 *  \brief      Description and activation file for module MyModule
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *  Description and activation class for module MyModule
 */
class modCFDIMX extends DolibarrModules
{
	//Variables CFDIMX
	public $mes_udpate_cfdimx;
	public $anio_udpate_cfdimx;
	public $version_cfdimx;
	public $version_cfdimx_sat;
	public $version_min_dolibarr;
	public $version_max_dolibarr;

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function modCFDIMX($db)
	{
        global $langs,$conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 9646330;
		$this->editor_name = "Auribox Consulting";
		$this->editor_url = "https://auriboxconsulting.com/";
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'cfdimx';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		// $this->description = "Módulo para facturación electrónica en México.<br>Actualización Septiembre 2021 (CFDI 3.3).<br><br><b>Compatible con Dolibarr 10, 11 ,12, 13 y 14.</b>";
		// $this->description = "Módulo para facturación electrónica en México.<br>Actualización Octubre 2021 (CFDI 3.3).<br><br><b>Compatible con Dolibarr 10.0.0 hasta 14.0.2</b>";

		$this->mes_udpate_cfdimx     = "Mayo";
		$this->anio_udpate_cfdimx    = "2022";
		$this->version_cfdimx        = "9.5.1";
		$this->version_cfdimx_sat    = "3.3";
		$this->version_min_dolibarr  = "10.0.0";
		$this->version_max_dolibarr  = "15.0.1";
		$this->url_last_version      = "https://api-cfdi.auribox.com/versioncfdi";
		$complementos_cc_cp 		 = 0;
		$factura_global         	 = 0;

		$archivos_facturaglobal = DOL_DOCUMENT_ROOT.'/cfdimx/agrupar_facturas.php';
		if(file_exists($archivos_facturaglobal) == true){
			$factura_global = 1;
		}

		$archivos_complementos = DOL_DOCUMENT_ROOT.'/cfdimx/carta_porte.php';
		if(file_exists($archivos_complementos) == true){
			$complementos_cc_cp = 1;
		}

		$descripcion_cfdimx = "";
		$descripcion_cfdimx = "Módulo para facturación electrónica en México.<br>.";
		$descripcion_cfdimx .= "Actualización ".$this->mes_udpate_cfdimx." ".$this->anio_udpate_cfdimx." (CFDI 3.3, 4.0).<br><br>";
		$descripcion_cfdimx .= "<b>Complementos activos</b><br>";
		$descripcion_cfdimx .= " • CFDI Relacionados<br>";

		if($factura_global == 1){
			$descripcion_cfdimx .= " • Factura Global<br>";
		}

		if($complementos_cc_cp == 1){
			$descripcion_cfdimx .= " • Comercio Exterior (CCE)<br>";
			$descripcion_cfdimx .= " • Carta Porte<br>";
		}

		$descripcion_cfdimx .= "<br><b>Compatible con Dolibarr ".$this->version_min_dolibarr." hasta ".$this->version_max_dolibarr."</b>";

		$this->description = $descripcion_cfdimx;

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = $this->version_cfdimx;
		if((int)DOL_VERSION >= 14){
			$this->checkForUpdate();
		}
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// $this->picto='accounting@cfdimx';//$this->picto='generic';
		$this->picto = 'bill';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		//$this->module_parts = array(
		//                        	'triggers' => 0,                                 // Set this to 1 if module has its own trigger directory
		//							'login' => 0,                                    // Set this to 1 if module has its own login method directory
		//							'substitutions' => 0,                            // Set this to 1 if module has its own substitution function file
		//							'menus' => 0,                                    // Set this to 1 if module has its own menus handler directory
		//							'barcode' => 0,                                  // Set this to 1 if module has its own barcode directory
		//							'models' => 0,                                   // Set this to 1 if module has its own models directory
		//							'css' => '/mymodule/css/mymodule.css.php',       // Set this to relative path of css if module has its own css file
		//							'hooks' => array('hookcontext1','hookcontext2')  // Set here all hooks context managed by module
		//							'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE')) // Set here all workflow context managed by module
		//                        );
		$this->module_parts = array(
			'triggers' => 1,
			'tpl' => 1,
			'hooks' => array(
						'invoicecard',
						// 'invoicelist',
						'formmail',
						'paiementcard',
						'formConfirm'
						)
		);
		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		$this->config_page_url = array("cfdimx.php@cfdimx");

		// Dependencies
		$this->depends = array(
							'modFacture',
							'modBanque',
							'modProduct',
							'modService',
							'modBookmark'
						);		// List of modules id that must be enabled if this module is enabled

		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(10,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("cfdimx@cfdimx");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0)
		// );

		#Claves de c_ClaveProdServCP con valor (0,1) y (1)
		$lista_claveprodservcp = "
					01010101, 10151608, 10171600, 10171601, 10171602, 10171603, 10171604, 10171605, 10171606, 10171607, 10171608,
					10171609, 10171610, 10171611, 10171700, 10171701, 10171702, 10191500, 10191506, 10191507, 10191508, 10191509,
					10191510, 10191511, 11101505, 11101511, 11101512, 11101521, 11101522, 11101528, 11101600, 11101601, 11101602,
					11101603, 11101604, 11101605, 11101606, 11101607, 11101608, 11101610, 11101611, 11101612, 11101613, 11101614,
					11101615, 11101622, 11101700, 11101705, 11101706, 11101709, 11101710, 11101711, 11101712, 11101713, 11101714,
					11101716, 11101900, 11101901, 11101902, 11101903, 11101904, 11101905, 11101906, 11101907, 11101908, 11121800,
					11121801, 11121802, 11121803, 11121804, 11121805, 11121806, 11121807, 11121808, 11121809, 11121810, 11121900,
					11121901, 11141600, 11141601, 11141602, 11141603, 11141604, 11141605, 11141606, 11141607, 11141608, 11141609,
					11141610, 11141700, 11141701, 11141702, 11162100, 11162101, 11162102, 11162104, 11162105, 11162107, 11162108,
					11162109, 11162110, 11162111, 11162112, 11162113, 11162114, 11162115, 11162116, 11162117, 11162118, 11162119,
					11162120, 11162121, 11162122, 11162123, 11162124, 11162125, 11162126, 11162127, 11162128, 11162129, 11162130,
					11162131, 11162132, 11162133, 11172000, 11172001, 11172002, 11172003, 11172200, 11172201, 12131500, 12131501,
					12131502, 12131503, 12131504, 12131505, 12131506, 12131507, 12131508, 12131509, 12131700, 12131701, 12131702,
					12131703, 12131704, 12131705, 12131706, 12131707, 12131708, 12131709, 12141500, 12141501, 12141502, 12141503,
					12141504, 12141505, 12141601, 12141702, 12141704, 12141707, 12141709, 12141710, 12141711, 12141715, 12141718,
					12141721, 12141723, 12141724, 12141726, 12141727, 12141729, 12141732, 12141740, 12141743, 12141745, 12141746,
					12141747, 12141748, 12141749, 12141801, 12141803, 12141804, 12141805, 12141806, 12141901, 12141902, 12141903,
					12141904, 12141905, 12141906, 12141907, 12141908, 12141909, 12141910, 12141912, 12141913, 12141915, 12141916,
					12142001, 12142003, 12142004, 12142005, 12142006, 12142101, 12142102, 12142103, 12142104, 12142105, 12142106,
					12142107, 12142108, 12161600, 12161601, 12161602, 12161603, 12161604, 12161605, 12161606, 12162211, 12163800,
					12163801, 12163802, 12164503, 12171501, 12171502, 12171503, 12171504, 12171506, 12171507, 12171508, 12171509,
					12171510, 12171511, 12171701, 12171702, 12171703, 12191500, 12191501, 12191502, 12191503, 12191600, 12191601,
					12191602, 12352000, 12352005, 12352100, 12352101, 12352102, 12352103, 12352104, 12352105, 12352106, 12352107,
					12352111, 12352114, 12352115, 12352117, 12352118, 12352119, 12352120, 12352206, 12352210, 12352300, 12352301,
					12352302, 12352303, 12352304, 12352305, 12352306, 12352311, 12352312, 12352313, 12352314, 12352315, 12352316,
					12352317, 12352318, 12352319, 12352320, 12352321, 12352400, 12352401, 12352402, 13102000, 13102001, 13102002,
					13102003, 13102005, 13102006, 13102010, 13102031, 13111001, 13111002, 13111003, 13111004, 13111005, 13111006,
					13111007, 13111008, 13111009, 13111012, 13111013, 13111014, 13111015, 13111017, 13111018, 13111019, 13111020,
					13111022, 13111023, 13111024, 13111025, 13111026, 13111027, 13111029, 13111033, 13111035, 13111036, 13111037,
					13111040, 13111048, 13111049, 13111050, 13111051, 13111052, 13111054, 13111058, 13111059, 13111060, 13111061,
					13111062, 13111063, 13111064, 13111065, 13111066, 13111067, 13111069, 13111070, 13111071, 13111072, 13111073,
					13111074, 13111100, 14122200, 14122201, 14122202, 15101500, 15101502, 15101503, 15101504, 15101505, 15101506,
					15101507, 15101508, 15101509, 15101510, 15101511, 15101512, 15101513, 15101514, 15101515, 15101600, 15101601,
					15101602, 15101603, 15101604, 15101605, 15101606, 15101607, 15101608, 15101609, 15101610, 15101611, 15101612,
					15101613, 15101614, 15111500, 15111501, 15111502, 15111503, 15111504, 15111505, 15111506, 15111507, 15111508,
					15111509, 15111510, 15111511, 15111512, 15131500, 15131502, 15131503, 15131504, 15131505, 15131506, 20122100,
					20122101, 20122102, 20122103, 20122104, 20122105, 20122106, 20122107, 20122108, 20122109, 20122110, 20122111,
					20122112, 20122114, 20122115, 20122213, 24121800, 24121801, 24121802, 24121803, 24121804, 24121805, 24121806,
					24121807, 24121808, 24131500, 24131501, 24131502, 24131503, 24131504, 24131505, 24131506, 24131507, 24131508,
					24131509, 24131510, 24131511, 24131512, 24131513, 24131514, 25174000, 25174001, 25174002, 25174003, 25174004,
					25174005, 25174006, 26101500, 26101501, 26101502, 26101503, 26101504, 26101505, 26101506, 26101507, 26101508,
					26101509, 26101510, 26101511, 26101512, 26101513, 26101514, 26101515, 26111700, 26111701, 26111702, 26111703,
					26111704, 26111705, 26111706, 26111707, 26111708, 26111709, 26111710, 26111711, 26111712, 26111713, 26111714,
					26111715, 26111716, 26111717, 26111718, 26111719, 26111720, 26111721, 26111722, 26111723, 26111724, 26111725,
					26111726, 26111727, 26111728, 26111729, 30121500, 30121501, 30121600, 30121601, 30121602, 30121604, 31132000,
					31132001, 31132002, 31201600, 31201601, 31201604, 31201605, 31201606, 31201607, 31201608, 31201609, 31201610,
					31201611, 31201612, 31201613, 31201614, 31201615, 31201616, 31201617, 31201618, 31201619, 31201620, 31201621,
					31201622, 31201623, 31201624, 31201625, 31201626, 31201627, 31201628, 31201629, 31201630, 31201631, 31201632,
					31201633, 31201634, 31201635, 31201636, 31201637, 31211500, 31211501, 31211502, 31211503, 31211504, 31211505,
					31211506, 31211507, 31211508, 31211509, 31211510, 31211511, 31211512, 31211513, 31211514, 31211515, 31211516,
					31211517, 31211518, 31211519, 31211520, 31211521, 31211522, 31211600, 31211601, 31211602, 31211603, 31211604,
					31211605, 31211606, 31211607, 31211800, 31211801, 31211802, 31211803, 39111714, 41104000, 41104001, 41104002,
					41104003, 41104004, 41104005, 41104006, 41104007, 41104008, 41104009, 41104010, 41104011, 41104012, 41104013,
					41104014, 41104015, 41104016, 41104017, 41104018, 41104019, 41104020, 41104021, 41104022, 42172000, 42172001,
					42172002, 42172003, 42172004, 42172005, 42172006, 42172007, 42172008, 42172009, 42172010, 42172011, 42172012,
					42172013, 42172014, 42172015, 42172016, 42172017, 42172018, 42191700, 42191706, 42191711, 42271700, 42271701,
					46101500, 46101501, 46101502, 46101503, 46101504, 46101505, 46101506, 46101600, 46101601, 46101800, 46101801,
					46101802, 46191600, 46191601, 47101600, 47101601, 47101602, 47101603, 47101604, 47101605, 47101606, 47101607,
					47101608, 47101609, 47101610, 47101611, 47101612, 47101613, 47101614, 47101615, 47131823, 47131824, 47131825,
					47131826, 47131827, 47131828, 47131829, 47131830, 47131831, 47131832, 47131833, 47131834, 47131835, 49131600,
					49131601, 49131602, 49131603, 49131604, 49131605, 49131606, 49131607, 50121500, 50121537, 50121538, 50121539,
					50202200, 50202201, 50202202, 50202203, 50202204, 50202205, 50202206, 50202207, 50202208, 50202209, 50202210,
					51101525, 51101607, 51101710, 51101716, 51102718, 51102722, 51102728, 51121601, 51121602, 51121603, 51122111,
					51131503, 51142901, 51142930, 51142931, 51161806, 51171513, 51171629, 51171632, 51191703, 51191804
				";

		$this->const =
				array(
						0 => array('CFDIMX_HUSO_HORARIO','chaine','0','Configuración del huso horario del módulo CFDIMX',0),
						1 => array('CFDIMX_RET_INDIVIDUALES','chaine','0','Configuración para habilitar Retenciones Individuales en el módulo CFDIMX',0),
						2 => array('CFDIMX_DEBUG_TIMBRADO','chaine','0','Visualización de la información que envia el módulo CFDIMX al WS',0),
						3 => array('CFDIMX_VERSION_SAT','chaine',$this->version_cfdimx_sat,'Constante que guarda la versión de CFDI que maneja el SAT',0),
						4 => array('CFDIMX_VERSION','chaine',$this->version_cfdimx,'Constante que guarda la versión de CFDIMX que se esta utilizando',0),
						5 => array('CFDIMX_DESC_PDF','chaine','0','Configuración para habilitar la descripción del Producto en Mayusculas',0),
						6 => array('CFDIMX_DESC_PROD_CAT_ETIQUETA','chaine','1','Configuración de la descripción del Producto de Catalogo utilizando Etiqueta',0),
						7 => array('CFDIMX_DESC_PROD_CAT_DESC','chaine','1','Configuración de la descripción del Producto de Catalogo utilizando Desc',0),
						8 => array('CFDIMX_DESC_PROD_NO_CAT_DESC','chaine','0','Configuración de la descripción del Producto no Catalogo utilizando Desc',0),
						9 => array('CFDIMX_RAZON_SOCIAL','chaine','0','Constante que almacena la Razón Social del Emisor',0),
						10 => array('CFDIMX_REGIMEN_FISCAL','chaine','0','Constante que almacena el Regimen Fiscal del Emisor',0),
						11 => array('CFDIMX_V_MIN_DOLI','chaine',$this->version_min_dolibarr,'Constante que almacena la versión Minima de Dolibarr que soporta el módulo',0),
						12 => array('CFDIMX_V_MAX_DOLI','chaine',$this->version_max_dolibarr,'Constante que almacena la versión Máxima de Dolibarr que soporta el módulo',0),
						13 => array('CFDIMX_DESC_PROD_CAT_REF','chaine','1','Configuración de la descripción del Producto de Catalogo utilizando Referencia',0),
						14 => array('CFDIMX_ADD_INFO_LINE','chaine','0','Configuración para agregar la informacion que se captura en las lineas de las Facturas para los Productos de Catalogos',0),
						15 => array('CFDIMX_DIRECCION','chaine','0','Constante que almacena la Dirección del Emisor',0),
						16 => array('CFDIMX_LIST_CLAVE_PRODSERVCP','chaine',trim($lista_claveprodservcp),'Lista de Claves Prod Serv CP',1),
						17 => array('CFDIMX_USOCFDI_PAGOS','chaine','CP01','Constante que almacena el Uso CFDI de los Complementos de Pago',1),
						18 => array('CFDIMX_LIM_DESC','chaine','0','Constante que almacena si se trunca la Descripción del Producto cuando supera el limite(1,000 caracteres) establecido por el SAT',1),
						19 => array('CFDIMX_PAGOS_C_PRODSERV','chaine','84111506','Constante que almacena la Clave Producto Servicio para Complemento de Pagos.',1),
						20 => array('CFDIMX_PAGOS_C_UMED','chaine','ACT','Constante que almacena la Clave de Unidad de Medida para Complemento de Pagos.',1),
						21 => array('CFDIMX_PAGOS_C_DESC','chaine','ACT','Constante que almacena la Descripcion para Complemento de Pagos.',1),
						22 => array('CFDIMX_MARCADORES','chaine','0','Configuración para agregar a los Marcadores los Recursos SAT.',1)
					);

		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

		dolibarr_set_const($this->db, "CFDIMX_V_MAX_DOLI", $this->version_max_dolibarr, 'chaine', 1, '', $conf->entity);

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:langfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  // To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:langfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  // To add another new tab identified by code tabname2
        //                              'objecttype:-tabname');                                                     // To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)

		#Pestañas CFDI
        $this->tabs = array(
        					'invoice:+tabfactclient:CFDI:@hwtitle:true:/cfdimx/facture.php?facid=__ID__',
							'invoice:+tabfactclientglobal:Agrupar Facturas:@hwtitle:true:/cfdimx/agrupar_facturas.php?facid=__ID__',
        					'thirdparty:+tablistfact:CFDI:@hwtitle:true:/cfdimx/listafacturas.php?socid=__ID__',
        					'payment:+tabpaimentcfdi:Complemento de Pagos:@hwtitle:true:/cfdimx/pagosfacturas.php?id=__ID__',
        					'thirdparty:+tabdomicilioclient:Domicilio Fiscal:@hwtitle:true:/cfdimx/domicilio_fiscal.php?socid=__ID__'
        				);

		// 'invoice:+tabfactpagosclt:Complementos de Pago:@hwtitle:true:/cfdimx/pagos.php?facid=__ID__',
		// 'user:+tabdatosusuario:Datos Usuario:@hwtitle:true:/cfdimx/datosusuario.php?id=__ID__',
		// 'invoice:+tabfactaddenda:Addenda:@hwtitle:true:/cfdimx/addenda_biopapel.php?facid=__ID__'
        // 'user:+tabdatosnomina:Datos Nómina:@hwtitle:true:/cfdimx/nomina.php?id=__ID__'

        // Dictionnaries
        if (! isset($conf->cfdimx->enabled)){
        	@$conf->cfdimx->enabled=0;
        }

		$this->dictionnaries=array();
        /* Example:
        if (! isset($conf->mymodule->enabled)) $conf->mymodule->enabled=0;	// This is to avoid warnings
        $this->dictionnaries=array(
            'langs'=>'mymodule@mymodule',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionnary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionnary
        );
        */

        // $this->dictionnaries=array(
        //     'langs'=>'cfdmimx@cfdmimx',
        //     'tabname'=>array(MAIN_DB_PREFIX."c_cfdimx_clave_prodserv",MAIN_DB_PREFIX."c_cfdimx_unidad_medida",MAIN_DB_PREFIX."c_cfdimx_uso_cfdi"),		// List of tables we want to see into dictonnary editor
        //     'tablib'=>array("Claves Producto-Servicio","Unidades de medida CFDI","Uso CFDI"),													// Label of tables
        //     'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_prodserv as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_unidad_medida as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_uso_cfdi as f'),	// Request to select fields
        //     'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
        //     'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionnary)
        //     'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
        //     'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
        //     'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
        //     'tabcond'=>array($conf->cfdimx->enabled,$conf->cfdimx->enabled,$conf->cfdimx->enabled)												// Condition to show each dictionnary
        // );

        $this->dictionnaries=array(
            'langs'=>'cfdmimx@cfdmimx',
            'tabname'=>array(
						MAIN_DB_PREFIX."c_cfdimx_clave_prodserv",           //CFDI - Clave Producto Servicio
						MAIN_DB_PREFIX."c_cfdimx_unidad_medida",            //CFDI - Unidad de Medida
						MAIN_DB_PREFIX."c_cfdimx_uso_cfdi",                 //CFDI - Uso CFDI
						MAIN_DB_PREFIX."c_cfdimx_tipo_rel",                 //CFDI - Tipo Relación
						MAIN_DB_PREFIX."c_cfdimx_regimen_f",                //CFDI - Regimen Fiscal
						MAIN_DB_PREFIX."c_cfdimx_objimpuesto",              //CFDI - Objeto Impuesto
						MAIN_DB_PREFIX."c_cfdimx_exportacion",              //CFDI - Clave Exportación
						MAIN_DB_PREFIX."c_cfdimx_formapago",                //CFDI - Formas de Pago
						MAIN_DB_PREFIX."c_cfdimx_tipo_facturas",            //CFDI - Tipos de Facturas
						MAIN_DB_PREFIX."c_cfdimx_meses",                    //CFDI Global - Meses
						MAIN_DB_PREFIX."c_cfdimx_periodicidad",             //CFDI Global - Periodicidad
						MAIN_DB_PREFIX."c_cfdimx_incoterm",                 //CCE - Claves Incoterm
						MAIN_DB_PREFIX."c_cfdimx_unidad_aduana",            //CCE - Clave Unidad Aduana
						MAIN_DB_PREFIX."c_cfdimx_f_arancelaria",            //CCE - Clave Fracción Arancelaria
						MAIN_DB_PREFIX."c_cfdimx_clave_transporte",         //CP - Clave Transporte
						MAIN_DB_PREFIX."c_cfdimx_pais",                     //CP - Clave Pais
						MAIN_DB_PREFIX."c_cfdimx_tipo_estacion",            //CP - Clave Tipo Estación
						MAIN_DB_PREFIX."c_cfdimx_estaciones",               //CP - Estaciones
						MAIN_DB_PREFIX."c_cfdimx_clave_unidad_peso",        //CP - Clave Unidad Peso
						MAIN_DB_PREFIX."c_cfdimx_clave_prodserv_cp",        //CP - Clave Producto Servicio CP
						MAIN_DB_PREFIX."c_cfdimx_clave_prod_stcc",          //CP - Clave Producto STCC
						MAIN_DB_PREFIX."c_cfdimx_material_peligroso",	    //CP - Material Peligroso
						MAIN_DB_PREFIX."c_cfdimx_tipo_embalaje",            //CP - Tipo de Embalaje
						MAIN_DB_PREFIX."c_cfdimx_tipo_permiso",			    //CP - Tipo de Permiso
						MAIN_DB_PREFIX."c_cfdimx_config_autotransporte",    //CP - Configuración AutoTransporte
						MAIN_DB_PREFIX."c_cfdimx_subtipo_rem",              //CP - Subtipo Remolque
						MAIN_DB_PREFIX."c_cfdimx_config_maritima",		    //CP - Configuración Maritima
						MAIN_DB_PREFIX."c_cfdimx_clave_tipo_carga",         //CP - Clave Tipo de Carga
						MAIN_DB_PREFIX."c_cfdimx_numaut_naviero",           //CP - Número de Autorización Naviero
						MAIN_DB_PREFIX."c_cfdimx_contenedor_mat",           //CP - Clave Contenedor Maritimo
						MAIN_DB_PREFIX."c_cfdimx_codtrans_aereo",           //CP - Código Transporte Aereo
						MAIN_DB_PREFIX."c_cfdimx_tipo_servicio",            //CP - Clave Tipo de Servicio
						MAIN_DB_PREFIX."c_cfdimx_tipo_trafico",             //CP - Clave Tipo Tráfico
						MAIN_DB_PREFIX."c_cfdimx_derecho_paso",             //CP - Clave Derechos de Paso
						MAIN_DB_PREFIX."c_cfdimx_tipo_carro",               //CP - Clave Tipo Carro
						MAIN_DB_PREFIX."c_cfdimx_contenedor_ferr",          //CP - Clave Contenedor Ferroviario
						MAIN_DB_PREFIX."c_cfdimx_parte_transporte",         //CP - Clave Parte Transporte
						MAIN_DB_PREFIX."c_cfdimx_tipo_figura_transporte",   //CP - Clave Figura Transporte
            		),		// List of tables we want to see into dictonnary editor
            'tablib'=>array(
						"CFDI 3.3, 4.0 - Claves Producto-Servicio",               //CFDI - Clave Producto Servicio
						"CFDI 3.3, 4.0 - Unidades de medida CFDI",                //CFDI - Unidad de Medida
						"CFDI 3.3, 4.0 - Uso CFDI",                               //CFDI - Uso CFDI
						"CFDI 3.3, 4.0 - Tipo Relación",                          //CFDI - Tipo Relación
						"CFDI 4.0 - Regimen Fiscal",                              //CFDI - Regimen Fiscal
						"CFDI 4.0 - Objeto de Impuesto",                          //CFDI - Objeto Impuesto
						"CFDI 4.0 - Exportación",                                 //CFDI - Clave Exportación
						"CFDI 3.3,4.0 - Formas de Pago",                          //CFDI - Formas de Pago
						"CFDI 3.3,4.0 - Tipos de Facturas",                       //CFDI - Tipos de Facturas
						"CFDI Global 4.0 - Meses",                                //CFDI Global - Meses
						"CFDI Global 4.0 - Periodicidad",                         //CFDI Global - Periodicidad
						"CCE - Claves Incoterm",                                  //CCE - Claves Incoterm
						"CCE - Unidades Aduana",                                  //CCE - Clave Unidad Aduana
						"CCE - Fracción Arancelaria",                             //CCE - Clave Fracción Arancelaria
						"Carta Porte 2.0 - Claves Transporte",                    //CP - Clave Transporte
						"Carta Porte 2.0 - País",                                 //CP - Clave Pais
						"Carta Porte 2.0 - Tipo Estación",                        //CP - Clave Tipo Estación
						"Carta Porte 2.0 - Estaciones",                           //CP - Estaciones
						"Carta Porte 2.0 - Claves Unidad Peso",                   //CP - Clave Unidad Peso
						"Carta Porte 2.0 - Claves Producto-Servicio CP",          //CP - Clave Producto Servicio CP
						"Carta Porte 2.0 - Claves Producto STCC",                 //CP - Clave Producto STCC
						"Carta Porte 2.0 - Claves Material Peligroso",            //CP - Material Peligroso
						"Carta Porte 2.0 - Tipo Embalaje",                        //CP - Tipo de Embalaje
						"Carta Porte 2.0 - Tipo Permiso",                         //CP - Tipo de Permiso
						"Carta Porte 2.0 - Configuración AutoTransporte",	      //CP - Configuración AutoTransporte
						"Carta Porte 2.0 - Sub. Tipo Remolque",                   //CP - Subtipo Remolque
						"Carta Porte 2.0 - Configuración Maritima",               //CP - Configuración Maritima
						"Carta Porte 2.0 - Claves Tipo Carga",                    //CP - Clave Tipo de Carga
						"Carta Porte 2.0 - Claves Num. Autorización Naviera",     //CP - Número de Autorización Naviero
						"Carta Porte 2.0 - Claves Contenedores Maritimos",        //CP - Clave Contenedor Maritimo
						"Carta Porte 2.0 - Claves Transporte Aereo",              //CP - Código Transporte Aereo
						"Carta Porte 2.0 - Tipo de Servicio",                     //CP - Clave Tipo de Servicio
						"Carta Porte 2.0 - Tipo de Tráfico",                      //CP - Clave Tipo Tráfico
						"Carta Porte 2.0 - Derechos de Paso",                     //CP - Clave Derechos de Paso
						"Carta Porte 2.0 - Tipo de Carro",                        //CP - Clave Tipo Carro
						"Carta Porte 2.0 - Claves Contenedores Ferroviarios",     //CP - Clave Contenedor Ferroviario
						"Carta Porte 2.0 - Partes Transporte",                    //CP - Clave Parte Transporte
						"Carta Porte 2.0 - Tipo Figura Transporte"                //CP - Clave Figura Transporte
            		),// Label of tables
            'tabsql'=>array(
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_prodserv as f',             //CFDI - Clave Producto Servicio
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_unidad_medida as f',              //CFDI - Unidad de Medida
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_uso_cfdi as f',                   //CFDI - Uso CFDI
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_rel as f',                   //CFDI - Tipo Relación
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_regimen_f as f',                  //CFDI - Regimen Fiscal
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_objimpuesto as f',                //CFDI - Objeto Impuesto
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_exportacion as f',                //CFDI - Clave Exportación
            		'SELECT f.rowid as rowid, f.code, f.label, f.cod_doli, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_formapago as f',      //CFDI - Formas de Pago
					'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_facturas as f',              //CFDI - Tipos de Facturas
					'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_meses as f',      				  //CFDI Global - Meses
					'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_periodicidad as f',    			  //CFDI Global - Periodicidad
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_incoterm as f',                   //CCE - Claves Incoterm
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_unidad_aduana as f',              //CCE - Clave Unidad Aduana
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_f_arancelaria as f',              //CCE - Clave Fracción Arancelaria
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_transporte as f',           //CP - Clave Transporte
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_pais as f',                       //CP - Clave Pais
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_estacion as f',              //CP - Clave Tipo Estación
            		'SELECT f.rowid as rowid, f.code, f.label, f.clave_t, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_estaciones as f',      //CP - Estaciones
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_unidad_peso as f',          //CP - Clave Unidad Peso
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_prodserv_cp as f',          //CP - Clave Producto Servicio CP
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_prod_stcc as f',            //CP - Clave Producto STCC
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_material_peligroso as f',         //CP - Material Peligroso
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_embalaje as f',              //CP - Tipo de Embalaje
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_permiso as f',               //CP - Tipo de Permiso
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_config_autotransporte as f',      //CP - Configuración AutoTransporte
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_subtipo_rem as f',                //CP - Subtipo Remolque
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_config_maritima as f',            //CP - Configuración Maritima
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_clave_tipo_carga as f',           //CP - Clave Tipo de Carga
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_numaut_naviero as f',             //CP - Número de Autorización Naviero
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_contenedor_mat as f',             //CP - Clave Contenedor Maritimo
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_codtrans_aereo as f',             //CP - Código Transporte Aereo
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_servicio as f',              //CP - Clave Tipo de Servicio
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_trafico as f',               //CP - Clave Tipo Tráfico
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_derecho_paso as f',               //CP - Clave Derechos de Paso
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_carro as f',                 //CP - Clave Tipo Carro
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_contenedor_ferr as f',            //CP - Clave Contenedor Ferroviario
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_parte_transporte as f',           //CP - Clave Parte Transporte
            		'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'c_cfdimx_tipo_figura_transporte as f'      //CP - Clave Figura Transporte
            		),	// Request to select fields
            'tabsqlsort'=>array(
						"label ASC",		//CFDI - Clave Producto Servicio
						"label ASC",		//CFDI - Unidad de Medida
						"label ASC",		//CFDI - Uso CFDI
						"code ASC",			//CFDI - Tipo Relación
						"label ASC",		//CFDI - Regimen Fiscal
						"label ASC",		//CFDI - Objeto Impuesto
						"label ASC",		//CFDI - Clave Exportación
						"label ASC",		//CFDI - Formas de Pago
						"code ASC",			//CFDI - Tipos de Facturas
						"code ASC",			//CFDI Global - Meses
						"code ASC",			//CFDI Global - Periodicidad
						"label ASC",		//CCE - Claves Incoterm
						"label ASC",		//CCE - Clave Unidad Aduana
						"label ASC",		//CCE - Clave Fracción Arancelaria
						"label ASC",		//CP - Clave Transporte
						"label ASC",		//CP - Clave Pais
						"label ASC",		//CP - Clave Tipo Estación
						"label ASC",		//CP - Estaciones
						"label ASC",		//CP - Clave Unidad Peso
						"label ASC",		//CP - Clave Producto Servicio CP
						"label ASC",		//CP - Clave Producto STCC
						"label ASC",		//CP - Material Peligroso
						"label ASC",		//CP - Tipo de Embalaje
						"label ASC",		//CP - Tipo de Permiso
						"label ASC",		//CP - Configuración AutoTransporte
						"label ASC",		//CP - Subtipo Remolque
						"label ASC",		//CP - Configuración Maritima
						"label ASC",		//CP - Clave Tipo de Carga
						"label ASC",		//CP - Número de Autorización Naviero
						"label ASC",		//CP - Clave Contenedor Maritimo
						"label ASC",        //CP - Código Transporte Aereo
						"label ASC",        //CP - Clave Tipo de Servicio
						"label ASC",        //CP - Clave Tipo Tráfico
						"label ASC",	    //CP - Clave Derechos de Paso
						"label ASC",        //CP - Clave Tipo Carro
						"label ASC",        //CP - Clave Contenedor Ferroviario
						"label ASC",        //CP - Clave Parte Transporte
						"label ASC"         //CP - Clave Figura Transporte
            		),// Sort order
            'tabfield'=>array(
						"code,label",					//CFDI - Clave Producto Servicio
						"code,label",					//CFDI - Unidad de Medida
						"code,label",					//CFDI - Uso CFDI
						"code,label",					//CFDI - Tipo Relación
						"code,label",					//CFDI - Regimen Fiscal
						"code,label",					//CFDI - Objeto Impuesto
						"code,label",					//CFDI - Clave Exportación
						"code,label,cod_doli",			//CFDI - Formas de Pago
						"code,label",		  			//CFDI - Tipos de Facturas
						"code,label",					//CFDI Global - Meses
						"code,label",					//CFDI Global - Periodicidad
						"code,label",					//CCE - Claves Incoterm
						"code,label",					//CCE - Clave Unidad Aduana
						"code,label",					//CCE - Clave Fracción Arancelaria
						"code,label",					//CP - Clave Transporte
						"code,label",					//CP - Clave Pais
						"code,label",					//CP - Clave Tipo Estación
						"code,label,clave_t",			//CP - Estaciones
						"code,label",					//CP - Clave Unidad Peso
						"code,label",					//CP - Clave Producto Servicio CP
						"code,label",					//CP - Clave Producto STCC
						"code,label",					//CP - Material Peligroso
						"code,label",					//CP - Tipo de Embalaje
						"code,label",					//CP - Tipo de Permiso
						"code,label",					//CP - Configuración AutoTransporte
						"code,label",					//CP - Subtipo Remolque
						"code,label",					//CP - Configuración Maritima
						"code,label",					//CP - Clave Tipo de Carga
						"code,label",					//CP - Número de Autorización Naviero
						"code,label",					//CP - Clave Contenedor Maritimo
						"code,label",					//CP - Código Transporte Aereo
						"code,label",					//CP - Clave Tipo de Servicio
						"code,label",					//CP - Clave Tipo Tráfico
						"code,label",					//CP - Clave Derechos de Paso
						"code,label",					//CP - Clave Tipo Carro
						"code,label",					//CP - Clave Contenedor Ferroviario
						"code,label",					//CP - Clave Parte Transporte
						"code,label" 					//CP - Clave Figura Transporte
            		),// List of fields (result of select to show dictionnary)
            'tabfieldvalue'=>array(
						"code,label",					//CFDI - Clave Producto Servicio
						"code,label",					//CFDI - Unidad de Medida
						"code,label",					//CFDI - Uso CFDI
						"code,label",					//CFDI - Tipo Relación
						"code,label",					//CFDI - Regimen Fiscal
						"code,label",					//CFDI - Objeto Impuesto
						"code,label",					//CFDI - Clave Exportación
						"code,label,cod_doli",			//CFDI - Formas de Pago
						"code,label",		  			//CFDI - Tipos de Facturas
						"code,label",					//CFDI Global - Meses
						"code,label",					//CFDI Global - Periodicidad
						"code,label",					//CCE - Claves Incoterm
						"code,label",					//CCE - Clave Unidad Aduana
						"code,label",					//CCE - Clave Fracción Arancelaria
						"code,label",					//CP - Clave Transporte
						"code,label",					//CP - Clave Pais
						"code,label",					//CP - Clave Tipo Estación
						"code,label,clave_t",			//CP - Estaciones
						"code,label",					//CP - Clave Unidad Peso
						"code,label",					//CP - Clave Producto Servicio CP
						"code,label",					//CP - Clave Producto STCC
						"code,label",					//CP - Material Peligroso
						"code,label",					//CP - Tipo de Embalaje
						"code,label",					//CP - Tipo de Permiso
						"code,label",					//CP - Configuración AutoTransporte
						"code,label",					//CP - Subtipo Remolque
						"code,label",					//CP - Configuración Maritima
						"code,label",					//CP - Clave Tipo de Carga
						"code,label",					//CP - Número de Autorización Naviero
						"code,label",					//CP - Clave Contenedor Maritimo
						"code,label",					//CP - Código Transporte Aereo
						"code,label",					//CP - Clave Tipo de Servicio
						"code,label",					//CP - Clave Tipo Tráfico
						"code,label",					//CP - Clave Derechos de Paso
						"code,label",					//CP - Clave Tipo Carro
						"code,label",					//CP - Clave Contenedor Ferroviario
						"code,label",					//CP - Clave Parte Transporte
						"code,label" 					//CP - Clave Figura Transporte
            		),// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array(
						"code,label",					//CFDI - Clave Producto Servicio
						"code,label",					//CFDI - Unidad de Medida
						"code,label",					//CFDI - Uso CFDI
						"code,label",					//CFDI - Tipo Relación
						"code,label",					//CFDI - Regimen Fiscal
						"code,label",					//CFDI - Objeto Impuesto
						"code,label",					//CFDI - Clave Exportación
						"code,label,cod_doli",			//CFDI - Formas de Pago
						"code,label",		  			//CFDI - Tipos de Facturas
						"code,label",					//CFDI Global - Meses
						"code,label",					//CFDI Global - Periodicidad
						"code,label",					//CCE - Claves Incoterm
						"code,label",					//CCE - Clave Unidad Aduana
						"code,label",					//CCE - Clave Fracción Arancelaria
						"code,label",					//CP - Clave Transporte
						"code,label",					//CP - Clave Pais
						"code,label",					//CP - Clave Tipo Estación
						"code,label,clave_t",			//CP - Estaciones
						"code,label",					//CP - Clave Unidad Peso
						"code,label",					//CP - Clave Producto Servicio CP
						"code,label",					//CP - Clave Producto STCC
						"code,label",					//CP - Material Peligroso
						"code,label",					//CP - Tipo de Embalaje
						"code,label",					//CP - Tipo de Permiso
						"code,label",					//CP - Configuración AutoTransporte
						"code,label",					//CP - Subtipo Remolque
						"code,label",					//CP - Configuración Maritima
						"code,label",					//CP - Clave Tipo de Carga
						"code,label",					//CP - Número de Autorización Naviero
						"code,label",					//CP - Clave Contenedor Maritimo
						"code,label",					//CP - Código Transporte Aereo
						"code,label",					//CP - Clave Tipo de Servicio
						"code,label",					//CP - Clave Tipo Tráfico
						"code,label",					//CP - Clave Derechos de Paso
						"code,label",					//CP - Clave Tipo Carro
						"code,label",					//CP - Clave Contenedor Ferroviario
						"code,label",					//CP - Clave Parte Transporte
						"code,label" 					//CP - Clave Figura Transporte
            		),// List of fields (list of fields for insert)
            'tabrowid'=>array(
						"rowid",				//CFDI - Clave Producto Servicio
						"rowid",				//CFDI - Unidad de Medida
						"rowid",				//CFDI - Uso CFDI
						"rowid",				//CFDI - Tipo Relación
						"rowid",				//CFDI - Regimen Fiscal
						"rowid",				//CFDI - Objeto Impuesto
						"rowid",				//CFDI - Clave Exportación
						"rowid",				//CFDI - Formas de Pago
						"rowid",				//CFDI - Tipos de Facturas
						"rowid",				//CFDI Global - Meses
						"rowid",				//CFDI Global - Periodicidad
						"rowid",				//CCE - Claves Incoterm
						"rowid",				//CCE - Clave Unidad Aduana
						"rowid",				//CCE - Clave Fracción Arancelaria
						"rowid",				//CP - Clave Transporte
						"rowid",				//CP - Clave Pais
						"rowid",				//CP - Clave Tipo Estación
						"rowid",				//CP - Estaciones
						"rowid",				//CP - Clave Unidad Peso
						"rowid",				//CP - Clave Producto Servicio CP
						"rowid",				//CP - Clave Producto STCC
						"rowid",				//CP - Material Peligroso
						"rowid",				//CP - Tipo de Embalaje
						"rowid",				//CP - Tipo de Permiso
						"rowid",				//CP - Configuración AutoTransporte
						"rowid",				//CP - Subtipo Remolque
						"rowid",				//CP - Configuración Maritima
						"rowid",				//CP - Clave Tipo de Carga
						"rowid",				//CP - Número de Autorización Naviero
						"rowid",				//CP - Clave Contenedor Maritimo
						"rowid",				//CP - Código Transporte Aereo
						"rowid",				//CP - Clave Tipo de Servicio
						"rowid",				//CP - Clave Tipo Tráfico
						"rowid",				//CP - Clave Derechos de Paso
						"rowid",				//CP - Clave Tipo Carro
						"rowid",				//CP - Clave Contenedor Ferroviario
						"rowid",				//CP - Clave Parte Transporte
						"rowid" 				//CP - Clave Figura Transporte
            		),// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array(
						$conf->cfdimx->enabled,						//CFDI - Clave Producto Servicio
						$conf->cfdimx->enabled,						//CFDI - Unidad de Medida
						$conf->cfdimx->enabled,						//CFDI - Uso CFDI
						$conf->cfdimx->enabled,						//CFDI - Tipo Relación
						$conf->cfdimx->enabled,						//CFDI - Regimen Fiscal
						$conf->cfdimx->enabled,						//CFDI - Objeto Impuesto
						$conf->cfdimx->enabled,						//CFDI - Clave Exportación
						$conf->cfdimx->enabled,						//CFDI - Formas de Pago
						$conf->cfdimx->enabled,						//CFDI - Tipos de Facturas
						$conf->cfdimx->enabled,						//CFDI Global - Meses
						$conf->cfdimx->enabled,						//CFDI Global - Periodicidad
						$conf->cfdimx->enabled,						//CCE - Claves Incoterm
						$conf->cfdimx->enabled,						//CCE - Clave Unidad Aduana
						$conf->cfdimx->enabled,						//CCE - Clave Fracción Arancelaria
						$conf->cfdimx->enabled,						//CP - Clave Transporte
						$conf->cfdimx->enabled,						//CP - Clave Pais
						$conf->cfdimx->enabled,						//CP - Clave Tipo Estación
						$conf->cfdimx->enabled,						//CP - Estaciones
						$conf->cfdimx->enabled,						//CP - Clave Unidad Peso
						$conf->cfdimx->enabled,						//CP - Clave Producto Servicio CP
						$conf->cfdimx->enabled,						//CP - Clave Producto STCC
						$conf->cfdimx->enabled,						//CP - Material Peligroso
						$conf->cfdimx->enabled,						//CP - Tipo de Embalaje
						$conf->cfdimx->enabled,						//CP - Tipo de Permiso
						$conf->cfdimx->enabled,						//CP - Configuración AutoTransporte
						$conf->cfdimx->enabled,						//CP - Subtipo Remolque
						$conf->cfdimx->enabled,						//CP - Configuración Maritima
						$conf->cfdimx->enabled,						//CP - Clave Tipo de Carga
						$conf->cfdimx->enabled,						//CP - Número de Autorización Naviero
						$conf->cfdimx->enabled,						//CP - Clave Contenedor Maritimo
						$conf->cfdimx->enabled,						//CP - Código Transporte Aereo
						$conf->cfdimx->enabled,						//CP - Clave Tipo de Servicio
						$conf->cfdimx->enabled,						//CP - Clave Tipo Tráfico
						$conf->cfdimx->enabled,						//CP - Clave Derechos de Paso
						$conf->cfdimx->enabled,						//CP - Clave Tipo Carro
						$conf->cfdimx->enabled,						//CP - Clave Contenedor Ferroviario
						$conf->cfdimx->enabled,						//CP - Clave Parte Transporte
						$conf->cfdimx->enabled 						//CP - Clave Figura Transporte
            		)// Condition to show each dictionnary
        );

        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		$r=0;
		// Example:
		/*
		$this->boxes[$r][1] = "myboxa.php";
		$r++;
		$this->boxes[$r][1] = "myboxb.php";
		$r++;
		*/

		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)


		$num_permiso = 100001;
		$this->rights[$r][0] = $num_permiso; 				     // Permission id (must not be already used)
		$this->rights[$r][1] = 'Generar CFDI';	         // Permission label
		$this->rights[$r][3] = 0; 					     // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'create';				 // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				     // Permission id (must not be already used)
		$this->rights[$r][1] = 'Cancelar y eliminar CFDI'; // Permission label
		$this->rights[$r][3] = 0; 					     // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';				 // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Consulta de datos de CFDIMX';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'select';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Acceso a la Configuración de CFDIMX';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'config';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'access';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Generar Complementos Pagos';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'create_comp_pagos';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Cancelar Complementos Pagos';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'cancelar_comp_pagos';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Eliminar Registro Complementos Pagos';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete_reg_comp_pagos';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Agregar CFDI Relacionados';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'add_comp_cfdi_rel';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Agrupar Facturas';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'agrupar_fac';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Agregar Comercio Exterior (CCE)';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'add_comp_cce';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;

		$this->rights[$r][0] = $num_permiso; 				      // Permission id (must not be already used)
		$this->rights[$r][1] = 'Agregar Carta Porte';	// Permission label
		$this->rights[$r][3] = 0; 					      // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'add_comp_carta_porte';				  // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;
		$num_permiso++;


		$filename2=DOL_DOCUMENT_ROOT.'/cfdimx/leertxt';
		if(file_exists($filename2)==true && $conf->global->MAIN_MODULE_SALARIES){
		 	$this->rights[$r][0] = 100005; 				// Permission id (must not be already used)
		 	$this->rights[$r][1] = 'Permitir timbrado de nómina';	// Permission label
		 	//$this->rights[$r][2] = 'a';
		 	$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		 	$this->rights[$r][4] = 'gennomina';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		 	$r++;

		 	$this->rights[$r][0] = 100006; 				// Permission id (must not be already used)
		 	$this->rights[$r][1] = 'Consulta del listado de timbrado de nómina';	// Permission label
		 	//$this->rights[$r][2] = 'a';
		 	$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		 	$this->rights[$r][4] = 'consnomina';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		 	$r++;
		}
		$r++;

		// Example to declare a new Top Menu entry and its Left menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>0,			                // Put 0 if this is a top menu
		//							'type'=>'top',			                // This is a Top menu entry
		//							'titre'=>'MyModule top menu',
		//							'mainmenu'=>'mymodule',
		//							'leftmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagetop.php',
		//							'langs'=>'mylangfile',	                // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->mymodule->enabled',	// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		// $this->menu[$r]=array(	'fk_menu'=>'r=0',		                // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
		//							'type'=>'left',			                // This is a Left menu entry
		//							'titre'=>'MyModule left menu',
		//							'mainmenu'=>'mymodule',
		//							'leftmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagelevel1.php',
		//							'langs'=>'mylangfile',	                // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->mymodule->enabled',	// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		//
		// Example to declare a Left Menu entry into an existing Top menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=mainmenucode',	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
		//							'type'=>'left',			                // This is a Left menu entry
		//							'titre'=>'MyModule left menu',
		//							'mainmenu'=>'mainmenucode',
		//							'leftmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagelevel2.php',
		//							'langs'=>'mylangfile',	                // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'enabled'=>'$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//							'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		// $r++;

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		// TOP MENU
		$this->menu[$r]=array(
			'fk_menu'=>0,			// Put 0 if this is a top menu
			'type'=>'top',			// This is a Top menu entry
			'titre'=>'CFDI',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'',
			'url'=>'/cfdimx/index.php',
			'langs'=>'cfdimx@cfdimx',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>100+$r,
			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->cfdimx->select',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		//LEFT MENUS
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx',
			'type'=>'left',
			'titre'=>'Facturacion CFDI',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'left_cfdi1',
			'url'=>'/cfdimx/index.php',
			'langs'=>'agenda',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi1',
			'type'=>'left',
			'titre'=>'Facturas Timbradas',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/index.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi1',
			'type'=>'left',
			'titre'=>'Facturas Activas',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/qry_timbradas.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi1',
			'type'=>'left',
			'titre'=>'Facturas Canceladas',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/qry_canceladas.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi1',
			'type'=>'left',
			'titre'=>'Facturas Max 72h',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/qry_sin_timbrar.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx',
			'type'=>'left',
			'titre'=>'Facturas Y Pagos',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'left_cfdi2',
			'url'=>'/cfdimx/index.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi2',
			'type'=>'left',
			'titre'=>'Facturas Ingresos',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/consulta/facturacion.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi2',
			'type'=>'left',
			'titre'=>'Pagos Ingresos',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/consulta/ingresos.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi2',
			'type'=>'left',
			'titre'=>'Facturas Egresos',
			'mainmenu'=>'cfdimx',
			'leftmenu'=>'cfdimx',
			'url'=>'/cfdimx/consultas/consulta/facturacion_egresos.php',
			'langs'=>'commercial',
			'position'=>100+$r,
			'perms'=>'1',
			'enabled'=>'1',
			'target'=>'',
			'user'=>2);
		$r++;

		$filename2=DOL_DOCUMENT_ROOT.'/cfdimx/leertxt';
		if(file_exists($filename2)==true && $conf->global->MAIN_MODULE_SALARIES){
			$this->menu[$r]=array(
					'fk_menu'=>'fk_mainmenu=cfdimx',
					'type'=>'left',
					'titre'=>'Timbrado de Nomina',
					'mainmenu'=>'cfdimx',
					'leftmenu'=>'left_cfdi_nom',
					'url'=>'/cfdimx/index.php',
					'langs'=>'commercial',
					'position'=>100+$r,
					'perms'=>'1',
					'enabled'=>'1',
					'target'=>'',
					'user'=>2);
			$r++;
			$this->menu[$r]=array(
				'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi_nom',
				'type'=>'left',
				'titre'=>'Timbrado',//'Carga TXT',
				'mainmenu'=>'cfdimx',
				'leftmenu'=>'cfdimx',
				'url'=>'/cfdimx/leertxt/cargatxt.php',
				'langs'=>'commercial',
				'position'=>100+$r,
				'perms'=>'$user->rights->cfdimx->gennomina',
				'enabled'=>'1',
				'target'=>'',
				'user'=>2);
			$r++;
			$this->menu[$r]=array(
				'fk_menu'=>'fk_mainmenu=cfdimx,fk_leftmenu=left_cfdi_nom',
				'type'=>'left',
				'titre'=>'Lista Timbrado',
				'mainmenu'=>'cfdimx',
				'leftmenu'=>'cfdimx',
				'url'=>'/cfdimx/leertxt/list.php',
				'langs'=>'commercial',
				'position'=>100+$r,
				'perms'=>'$user->rights->cfdimx->consnomina',
				'enabled'=>'1',
				'target'=>'',
				'user'=>2);
			$r++;
		}		

		// Exports
		$r=1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $db, $conf;

		$entidad = $conf->entity;

		//echo "<pre>";
			//print_r($res);
		//echo "</pre>";

		$sql="";
		#Si no existe la columna rowid un llx_cfdimx_emisor_datacomp se elimina la clave primaria que tiene la tabla y se agrega rowid como campo y llave primaria
		$sql = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = "rowid" AND TABLE_NAME = "'.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp"';
		$r = $db->query($sql);

		if ($db->num_rows($r) == 0) {
			$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp DROP COLUMN emisor_id';
			$db->query($sql);
			$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp ADD COLUMN rowid int(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (rowid)';
			$db->query($sql);
		}
		unset($sql);

		#Agregar o editar extrafields mínimos necesarios para funcionamiento del módulo (Inicio)
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		#Inicia Extrafields para la tabla productos

		#Clave Producto/Servicio
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "claveprodserv" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", fieldrequired=1, alwayseditable=1, list=1 WHERE name="claveprodserv" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('claveprodserv', 'ClaveProdServ', 'sellist', 100, '', 'product', 0, 1, '', 'a:1:{s:7:"options";a:1:{s:44:"c_cfdimx_clave_prodserv:label:code::active=1";N;}}');
		}

		#Clave Unidad de Medida
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "umed" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", fieldrequired=1, alwayseditable=1, list=1 WHERE name="umed" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('umed', 'Unidad de medida', 'sellist', 101, '', 'product', 0, 1, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_unidad_medida:label:code::active=1";N;}}');
		}

		#Clave No. Identificación
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "noidenticfdi" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="noidenticfdi" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('noidenticfdi', 'No. Identificación', 'varchar', 102, '255', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');
		}

		#Clave Cuenta Pedrial
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "cuentapredial" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="cuentapredial" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('cuentapredial', 'Cuenta Predial', 'varchar', 103, '255', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='cuentapredial' AND elementtype='product' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Impuesto ISH
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "prodcfish" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="prodcfish" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('prodcfish', 'Impuesto sobre Hospedaje (ISH)', 'varchar', 105, '255', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='prodcfish' AND elementtype='product' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Etiqueta Impuesto ISH
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "prodcfish_label" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="prodcfish_label" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('prodcfish_label', 'Etiqueta para Impuesto sobre Hospedaje (ISH)', 'varchar', 106, '255', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='prodcfish_label' AND elementtype='product' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Unidad Aduana
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "uaduana" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", alwayseditable=1, list=1 WHERE name="uaduana" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('uaduana', 'Unidad Aduana', 'sellist', 107, '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_unidad_aduana:label:code::active=1";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='uaduana' AND elementtype='product' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Fraccion Arancelaria
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "f_arancelaria" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", alwayseditable=1, list=1 WHERE name="f_arancelaria" AND elementtype="product" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('f_arancelaria', 'Fracción Arancelaria', 'sellist', 108, '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_f_arancelaria:label:code::active=1";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='f_arancelaria' AND elementtype='product'";
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Informacion Aduanera - Num Pedimento
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "numpedimento" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('numpedimento', 'Información Aduanera - Num. Pedimento', 'varchar', 109, '255', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='numpedimento' AND elementtype='product' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Exento I.V.A.
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "exentoiva" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('exentoiva', 'Exento de I.V.A.', 'boolean', 110, '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',1,'',1);
		}

		#Objeto de Impuesto
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "objimp" AND elementtype="product" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('objimp', 'Objeto de Impuesto', 'select', 111, '', 'product', 0, 0, '', 'a:1:{s:7:"options";a:3:{s:2:"01";s:26:"01 - No objeto de impuesto";s:2:"02";s:26:"02 - Si objeto de impuesto";s:2:"03";s:53:"03 - Si objeto del impuesto y no obligado al desglose";}}');
		}

		#Termina Extrafields para la tabla productos


		#Inicia Extrafields para la tabla Factura Extrafields

		#Clave Producto/Servicio
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "claveprodserv" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", fieldrequired=1, alwayseditable=1, list=1 WHERE name="claveprodserv" AND elementtype="facturedet" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('claveprodserv', 'ClaveProdServ', 'sellist', 100, '', 'facturedet', 0, 1, '', 'a:1:{s:7:"options";a:1:{s:44:"c_cfdimx_clave_prodserv:label:code::active=1";N;}}');
		}

		#Clave Unidad de medida
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "umed" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", fieldrequired=1, alwayseditable=1, list=1 WHERE name="umed" AND elementtype="facturedet" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('umed', 'Unidad de medida', 'sellist', 101, '', 'facturedet', 0, 1, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_unidad_medida:label:code::active=1";N;}}');
		}

		#Clave No. Identificación
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "noidenticfdi" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="noidenticfdi" AND elementtype="facturedet" AND entity = '.$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('noidenticfdi', 'No. Identificación', 'varchar', 102, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');
		}

		#Clave Cuenta Predial
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "cuentapredial" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="cuentapredial" AND elementtype="facturedet" AND entity = '.$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('cuentapredial', 'Cuenta Predial', 'varchar', 103, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='cuentapredial' AND elementtype='facturedet' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Impuesto ISH
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "prodcfish" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="prodcfish" AND elementtype="facturedet" AND entity = '.$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('prodcfish', 'Impuesto sobre Hospedaje (ISH)', 'varchar', 105, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='prodcfish' AND elementtype='facturedet' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Impuesto ISH
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "prodcfish_label" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="varchar", size="255", alwayseditable=1 WHERE name="prodcfish_label" AND elementtype="facturedet" AND entity = '.$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}else{
			$extrafields->addExtraField('prodcfish_label', 'Etiqueta para Impuesto sobre Hospedaje (ISH)', 'varchar', 106, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='prodcfish_label' AND elementtype='facturedet' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Unidad Aduana
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "uaduana" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="255", alwayseditable=1, list=1 WHERE name="uaduana" AND elementtype="facturedet" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('uaduana', 'Unidad Aduana', 'sellist', 107, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_unidad_aduana:label:code::active=1";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='uaduana' AND elementtype='facturedet' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Clave Fraccion Arancelaria
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "f_arancelaria" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);

			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", alwayseditable=1, list=1 WHERE name="f_arancelaria" AND elementtype="facturedet" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('f_arancelaria', 'Fracción Arancelaria', 'sellist', 108, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_f_arancelaria:label:code::active=1";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='f_arancelaria' AND elementtype='facturedet' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Informacion Aduanera - Num Pedimento
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "numpedimento" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('numpedimento', 'Información Aduanera - Num. Pedimento', 'varchar', 109, '255', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='numpedimento' AND elementtype='facturedet' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}

		#Exento I.V.A.
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "exentoiva" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('exentoiva', 'Exento de I.V.A.', 'boolean', 110, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',1,'',1);
		}

		#Objeto de Impuesto
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "objimp" AND elementtype="facturedet" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('objimp', 'Objeto de Impuesto', 'select', 111, '', 'facturedet', 0, 0, '', 'a:1:{s:7:"options";a:3:{s:2:"01";s:26:"01 - No objeto de impuesto";s:2:"02";s:26:"02 - Si objeto de impuesto";s:2:"03";s:53:"03 - Si objeto del impuesto y no obligado al desglose";}}');
		}

		#Termina Extrafields para la tabla Factura Extrafields


		#Inicia Extrafields para la tabla Factura

		#Clave Forma de Pago
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "formpagcfdi" AND elementtype="facture" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="select", size="", fieldrequired=1, alwayseditable=1, list=1 WHERE name="formpagcfdi" AND elementtype="facture" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else{
			$extrafields->addExtraField('formpagcfdi', 'Método de Pago CFDI', 'select', 100, '', 'facture', 0, 1, '', 'a:1:{s:7:"options";a:2:{s:3:"PUE";s:34:"PUE - Pago en una sola exhibición";s:3:"PPD";s:28:" PPD - Pago en parcialidades";}}');
		}

		#Clave Uso CFDI
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "usocfdi" AND elementtype="facture" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			if ($res->type == 'varchar') {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET type="sellist", size="", fieldrequired=1, alwayseditable=1, list=1 WHERE name="usocfdi" AND elementtype="facture" AND entity = '.$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			}
		}else {
			$extrafields->addExtraField('usocfdi', 'Uso CFDI', 'sellist', 101, '', 'facture', 0, 1, '', 'a:1:{s:7:"options";a:1:{s:38:"c_cfdimx_uso_cfdi:label:code::active=1";N;}}');
		}

		#Clave Tipo Relación
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "cfdidoctiporelacion" AND elementtype="facture" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res = $db->fetch_object($r);
			// if ($res->type == 'varchar') {
				$param = 'a:1:{s:7:"options";a:1:{s:38:"c_cfdimx_tipo_rel:label:code::active=1";N;}}';

				$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
				$sql .= " SET type='sellist', size='', alwayseditable=1, list=1,";
				$sql .= " param='".$param."'";
				$sql .= " WHERE name='cfdidoctiporelacion' AND elementtype='facture' AND entity = ".$entidad;
				//echo $sql."<br>";
				$db->query($sql);
			// }
		}else {
			$extrafields->addExtraField('cfdidoctiporelacion', 'CFDI Rel. Tipo Relación', 'sellist', 102, '', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:38:"c_cfdimx_tipo_rel:label:code::active=1";N;}}');

			$sql = "UPDATE ".MAIN_DB_PREFIX."extrafields";
			$sql .= " SET ";
			$sql .= " list=0";
			$sql .= " WHERE name='cfdidoctiporelacion' AND elementtype='facture' AND entity = ".$entidad;
			//echo $sql."<br>";
			$db->query($sql);
		}
		unset($sql);
		//$extrafields->addExtraField('tipodecambiocfdi', 'Tipo de cambio CFDI', 'varchar', 100, '255', 'facture', 0, 0, '');

		#Tipo de Cambio CFDI
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "tipodecambiocfdi" AND elementtype="facture" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {
		}else{
			$extrafields->addExtraField('tipodecambiocfdi', 'Tipo de Cambio', 'varchar', 103, '255', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');
		}

		#Clave Exportación
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE "clave_expor" AND elementtype="facture" AND entity = '.$entidad;
		//echo $sql;
		$r = $db->query($sql);
		if ($db->num_rows($r) > 0) {

		}else{
			//$extrafields->addExtraField('clave_expor', 'Clave Exportacion', 'select', 104, '', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:3:{s:2:"01";s:14:"01 - No Aplica";s:2:"02";s:15:"02 - Definitiva";s:2:"03";s:16:"03 - No Temporal";}}');
			$extrafields->addExtraField('clave_expor', 'Clave Exportacion', 'sellist', 104, '', 'facture', 0, 0, '', 'a:1:{s:7:"options";a:1:{s:41:"c_cfdimx_exportacion:label:code::active=1";N;}}');
		}
		#Termina Extrafields para la tabla Factura

		#Inicia Ajuste para agregar nuevas claves de Uso CFDI
	    $lista_usocfdi = array(
	            'S01'  => "('S01', 'Sin efectos fiscales', 1)",
	            'CP01' => "('CP01', 'Pagos', 1)",
	            'CN01' => "('CN01', 'Nómina', 1)"
	          );

	    $sql_usocfdi = 'SELECT * FROM '.MAIN_DB_PREFIX.'c_cfdimx_uso_cfdi';
	    $res_sql_usocfdi = $db->query($sql_usocfdi);

	    if($db->num_rows($res_sql_usocfdi) > 0) {
	    	while($res = $db->fetch_object($res_sql_usocfdi)){
	        	if (isset($lista_usocfdi[$res->code])) unset($lista_usocfdi[$res->code]);
	      	}

	      	if (count($lista_usocfdi)>0){
		        $sql = "INSERT IGNORE INTO llx_c_cfdimx_uso_cfdi (code, label, active) VALUES ".implode(', ',$lista_usocfdi);
	        	$db->query($sql);
	      	}
	    }
	    #Termina Ajuste para agregar nuevas claves de Uso CFDI

		#Inicia Actualizacion del Catalogo de Formas de Pago con código Dolibar
		$sql_list_formpago = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_formapago";
		$sql_list_formpago .= " WHERE cod_doli IS NOT NULL";
		$res_list_formpago = $db->query($sql_list_formpago);
		$num_list_formpago = $db->num_rows($res_list_formpago);

		if($num_list_formpago > 0){
			while ($obj_formpago = $db->fetch_object($res_list_formpago)) {
				$sql_paiment = "SELECT * FROM ".MAIN_DB_PREFIX."c_paiement";
				$sql_paiment .= " WHERE code = '".$obj_formpago->cod_doli."'";
				$sql_paiment .= " AND entity = ".$entidad;
				$res_paiment = $db->query($sql_paiment);
				$num_paiment = $db->num_rows($res_paiment);

				if($num_paiment > 0){
					$obj_aux_f_pago = $db->fetch_object($res_paiment);

					$update_f_pago = "UPDATE ".MAIN_DB_PREFIX."c_paiement";
					$update_f_pago .= " SET";
						$update_f_pago .= " accountancy_code = '".trim($obj_formpago->code)."'";
					$update_f_pago .= " WHERE";
						$update_f_pago .= " id = ".$obj_aux_f_pago->id;

					$res_update = $db->query($update_f_pago);
				}else{
					$insert_f_pago = "INSERT INTO ".MAIN_DB_PREFIX."c_paiement";
					$insert_f_pago .= " (entity, code, libelle, type, active, accountancy_code)";
					$insert_f_pago .= " VALUES";
					$insert_f_pago .= " (";
						$insert_f_pago .= "'".$entidad."',";
						$insert_f_pago .= "'".$obj_formpago->cod_doli."',";
						$insert_f_pago .= "'".$obj_formpago->label."',";
						$insert_f_pago .= " 2,";
						$insert_f_pago .= " 0,";
						$insert_f_pago .= "'".trim($obj_formpago->code)."'";
					$insert_f_pago .= " )";

					$res_insert = $db->query($insert_f_pago);
				}
			}
		}
		#Termina Actualizacion del Catalogo de Formas de Pago con código Dolibar

		##Inicia verificar si existe Clave de Pagos
		$sql_val_claveprodserv  = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_clave_prodserv";
		$sql_val_claveprodserv .= " WHERE code = '84111506'";
		$res_val_claveprodserv  = $db->query($sql_val_claveprodserv);
		$num_val_claveprodserv  = $db->num_rows($res_val_claveprodserv);

		if($num_val_claveprodserv == 0){
			$sql_claveprodserv  = "INSERT INTO ".MAIN_DB_PREFIX."c_cfdimx_clave_prodserv";
			$sql_claveprodserv .= "(code, label, active) VALUES ('84111506','84111506 - Servicios de facturación',1);";
			$res_claveprodserv  =  $db->query($sql_claveprodserv);
		}
		##Termina verificar si existe Clave de Pagos

		##Inicia veificar nueva Clave de Exportacion
		$sql_val_exportacion  = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_exportacion";
		$sql_val_exportacion .= " WHERE code = '04'";
		$res_val_exportacion  = $db->query($sql_val_exportacion);
		$num_val_exportacion  = $db->num_rows($res_val_exportacion);

		if($num_val_exportacion == 0){
			$sql_exportacion  = "INSERT INTO ".MAIN_DB_PREFIX."c_cfdimx_exportacion";
			$sql_exportacion .= "(code, label, active) VALUES ('04','04 - Definitiva con clave distinta a A1 o cuando no existe enajenación en términos del CFF',1);";
			$res_exportacion  =  $db->query($sql_exportacion);
		}
		##Termina veificar nueva Clave de Exportacion

		$result=$this->load_tables();
		$sql = array();
		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}


	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/cfdimx/sql/');
	}
}

?>