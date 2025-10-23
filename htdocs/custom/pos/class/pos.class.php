<?php
/* Copyright (C) 2011 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2015 Ferran Marcet      <fmarcet@2byte.es>
 * Copyright (C) 2013 Iván Casco              <admin@gestionintegraltn.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
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

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
                        dol_include_once('/custom/pos/class/ticket.class.php');
dol_include_once('/custom/pos/class/payment.class.php');
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
dol_include_once('/custom/pos/class/cash.class.php');
dol_include_once('/custom/pos/backend/lib/errors.lib.php');
dol_include_once('/custom/pos/class/place.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once (DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
dol_include_once('/custom/pos/class/facturesim.class.php');
dol_include_once('/rewards/class/rewards.class.php');
require_once (DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php');
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
/**
 *	\class      POS
 *	\brief      Class for POS gestion
 */
class POS extends CommonObject
{
    

	/**
 	*  Return Categories list
 	*  @param		int		$idCat		Id of Category, if 0 return Principal cats
 	*  @return      array				Array with categories
 	*/
	public static function getCategories($idCat=0)
	{
		global $db;
		switch($idCat)
		{
			case 0: //devolver las categorias con nivel 1
				$objCat = new Categorie($db);
				//$cats=$objCat->get_full_arbo(0);
				$cats = $objCat->get_full_arbo($idCat);
								
				if (sizeof ($cats) > 0)
				{
					$retarray=array();
					foreach($cats as $key => $val)
					{
						if ($val['level'] < 2)
						{
							$val['image']=self::getImageCategory($val['id']);
							$val['thumb']=self::getImageCategory($val['id']);
							$retarray[]=$val;
						}	
					}
					return $retarray;
				}
				break;
	
			case ($idCat>0):
				$objCat = new Categorie($db);
			
				$result=$objCat->fetch($idCat);
				if($result > 0)
				{
					$cats = $objCat->get_filles($idCat,'166');
					//$cats = self::get_filles($idCat);
					if (sizeof ($cats) > 0)
					{
						$retarray=array();
						foreach($cats as $val)
						{
							$cat['id']=$val->id;
							$cat['label']=$val->label;
							$cat['fulllabel']=$val->label;
							$cat['fullpath']='_'.$val->id;
							$cat['image']=self::getImageCategory($val->id);
							$cat['thumb']=self::getImageCategory($val->id);
							$retarray[]=$cat;
						}
						return $retarray;
					}
				}
				
				break;
				
			default:
				return -1;
				break;
		}
	}
	
	/**
 	*  Return path of a catergory image 
 	*  @param 		int 	$idCat		Id of Category
 	*  @param		bool	$thumb		If enabled use thumb
 	*  @return      string				Image path
 	*/
	public static function getImageCategory($idCat)
	{	
		global $conf, $db;
		
		$extName="_small";
		$extImgTarget=".jpg";
		$outDir="pos";
		$maxWidth =90;
		$maxHeight=90;
		$quality=50;
			
		if($idCat>0)
		{
			$objCat = new Categorie($db);
			$objCat->fetch($idCat);
			
			$pdir = get_exdir($idCat,2) . $idCat ."/photos/";
			$dir = $conf->categorie->multidir_output[$objCat->entity].'/'.$pdir;
			foreach ($objCat->liste_photos($dir,1,166) as $key => $obj)
			{
				$filename = $dir.$obj['photo'];
				$filethumbs= $dir.$outDir.'/'.$obj['photo'];
				
				$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$filethumbs);
				$fileName = basename($fileName);
				$imgThumbName = $dir.$outDir.'/'.$fileName.$extName.$extImgTarget;
				
				$file_osencoded=$imgThumbName;
				if(!file_exists($file_osencoded))
				{
					$file_osencoded=dol_osencode($filename);
					if (file_exists($file_osencoded))
					{
						require_once(DOL_DOCUMENT_ROOT ."/core/lib/images.lib.php");
						vignette($filename,$maxWidth,$maxHeight,$extName,$quality,$outDir,2);			
					}
				}

				$filename=$outDir.'/'.$fileName.$extName.$extImgTarget;
				$realpath = DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$objCat->entity.'&file='.urlencode($pdir.$filename) ;
				
			}
			if(!$realpath)
			{
				$realpath = DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode('noimage.jpg');
			}
			return $realpath;
		}

	}
    public static function asignarLabel($qty,$label)
    {
        global $db;
        $response=array();
        $now=dol_now();
        $sql = "INSERT INTO product_prospect(label,qty,datec) VALUES";
        $sql.= "(";
        $sql.= "'".$label."',";
        $sql.= $qty;
        $sql.= ",'".$db->idate($now)."'";
        $sql.= ")";

        $res = $db->query($sql);
        if($res){
            $response['response']=0;
        }
        else{
            $response['response']=1;
        }
        $response['query']=$sql;
        return $response;
    }
	/**
 	*  Return products by a category
 	*  @param 		int		$idCat		Id of Category
 	*  @param		int		$more		list products position
 	*  @param		int		$ticketstate	Ticket state (2= return)
 	*  @return      array				List of products
 	*/
	public static function getProductsbyCategory($idCat,$more, $ticketstate)
	{
		global $db,$conf;
		
		if($idCat) //Productos de la categoría
		{
			$object = new Categorie($db);
			$result=$object->fetch($idCat);
			if ($result > 0)
			{
				if ($object->type == 0)
				{
					$prods = self::get_prod($idCat,$more, $ticketstate);
					return $prods;
						
				}	
			}
		}
		else //Productos sin categorías
		{
			
			$sql = "SELECT o.rowid as id, o.ref, o.label, o.description, o.price_ttc, o.price_min_ttc,";
			$sql .=" o.fk_product_type";
			$sql.= " FROM ".MAIN_DB_PREFIX."product as o";
			
			if($conf->global->POS_STOCK || $ticketstate ==1){
				$sql .=" WHERE rowid NOT IN ";
				$sql .=" (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product)";
				$sql .=" AND tosell=1";
				$sql.= " AND entity IN (".getEntity("product", 1).")";
				if(!$conf->global->POS_SERVICES){
					$sql .= " AND o.fk_product_type = 0";
				}
			}
			else
			{
				$cashid = $_SESSION['TERMINAL_ID'];
				$cash = new Cash($db);
				$cash->fetch($cashid);
				$warehouse = $cash->fk_warehouse;
					
				$sql .= ", ".MAIN_DB_PREFIX."product_stock as ps";
				$sql .=" WHERE o.rowid NOT IN ";
				$sql .=" (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product)";
				$sql .=" AND tosell=1";
				$sql.= " AND entity IN (".getEntity("product", 1).")";
				$sql .= " AND o.rowid = ps.fk_product";
				$sql .= " AND ps.fk_entrepot = ".$warehouse;
				$sql .= " AND ps.reel > 0";
				if($conf->global->POS_SERVICES){
					$sql .= " union select o.rowid as id, o.ref, o.label, o.description, o.price_ttc, o.price_min_ttc, 	";
					$sql .= " o.fk_product_type";
					$sql .= " FROM ".MAIN_DB_PREFIX."product as o";
					$sql .=" WHERE o.rowid NOT IN ";
					$sql .=" (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product)";
					$sql .=" AND tosell=1";
					$sql .=" AND fk_product_type=1";
					$sql.= " AND entity IN (".getEntity("product", 1).")";
				}
			}
			if($more >= 0)
				$sql.=" LIMIT ".$more.",10 ";
			
			$res = $db->query($sql);
			
			if ($res)
			{
				$num = $db->num_rows($res);
				$i = 0;
				
				while ($i < $num)
				{
					$objp = $db->fetch_object($res);
					
					$ret[$objp->id]["id"] = $objp->id;
					$ret[$objp->id]["ref"] = $objp->ref;
					$ret[$objp->id]["label"] = $objp->label;
					$ret[$objp->id]["price_ttc"] = $objp->price_ttc;
					$ret[$objp->id]["price_min_ttc"] = $objp->price_min_ttc;
					$ret[$objp->id]["description"] = $objp->description;
											
					$ret[$objp->id]["image"] = self::getImageProduct($objp->id, false);
					$ret[$objp->id]["thumb"] = self::getImageProduct($objp->id, true);
					
					$i++;
								
				}
				return $ret;
			}
			else 
			{
				return -1;
			}
		}	
		return -1;
	}
	
	/**
 	*  Return a catergory 
 	*  @param 		int		$idCat		Id of Category
 	*  @return      array				Category info
 	*/
	public static function getCategorybyId($idCat)
	{
		global $db;
		$objCat = new Categorie($db);
		$result=$objCat->fetch($idCat);
		if($result > 0)	
		{
			return $objCat;
		}
		return -1;
	}
	
	/**
 	*  Return product info
 	*  @param 		int		$idProd		Id of Product
 	*  @return      array				Product info
 	*/
	public static function getProductbyId($idProd, $idCust)
	{
		global $db, $conf;
		if($conf->global->PRODUIT_MULTIPRICES){
			$sql = "SELECT price_level";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe";
			$sql .= " WHERE rowid = ".$idCust;
			$res=$db->query ($sql);
			if ($res){
				$obj = $db->fetch_object($res);
				if($obj->price_level == NULL){
					$pricelevel= 1;
				}
				else{
					$pricelevel= $obj->price_level;
				}
			}
		}
		else{
			$pricelevel= 1;
		}
		
		$function="getProductbyId";
		
		$objp = new Product($db);
		$objp->fetch($idProd);
		
		$ret[0]["id"] = $objp->id;
		$ret[0]["ref"] = $objp->ref;
		$ret[0]["label"] = $objp->label;
		$ret[0]["description"] = $objp->description;
		$ret[0]["fk_product_type"] = $objp->type;
		$ret[0]["diff_price"] = 0;
		$ret[0]["discount_percent"] = $objp->base_price_ttc!=0?intval(100 - ($objp->price_ttc * 100 / $objp->base_price_ttc)):0;
		if(!empty( $objp->multiprices[$pricelevel]) && $objp->multiprices[$pricelevel] > 0 ){
			$ret[0]["tva_tx"] = $objp->multiprices_tva_tx[$pricelevel];
			$ret[0]["price_base_type"] = $objp->multiprices_base_type[$pricelevel];
			$ret[0]["price"] = $objp->multiprices[$pricelevel];
			$ret[0]["price_ttc"] = $objp->multiprices_ttc[$pricelevel];
			$ret[0]["price_min"] = $objp->multiprices_min[$pricelevel];
			$ret[0]["price_min_ttc"] = $objp->multiprices_min_ttc[$pricelevel];
		}
		else if($conf->global->PRODUIT_CUSTOMER_PRICES){
		
			require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';
			$prodcustprice = new Productcustomerprice($db);
			$filter = array('t.fk_product' => $objp->id,'t.fk_soc' => $idCust);

			$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
			if ($result >= 0) {
				if (count($prodcustprice->lines) > 0) {
					$ret[0]["price"] = $prodcustprice->lines[0]->price;
					$ret[0]["price_ttc"] = $prodcustprice->lines[0]->price_ttc;
					$ret[0]["price_min"] = $prodcustprice->lines[0]->price_min;
					$ret[0]["price_min_ttc"] = $prodcustprice->lines[0]->price_min_ttc;
					$ret[0]["price_base_type"] = $prodcustprice->lines[0]->price_base_type;
					$ret[0]["tva_tx"] = $prodcustprice->lines[0]->tva_tx;
				}else {
					$ret[0]["price"] = $objp->price;
					$ret[0]["price_ttc"] = $objp->price_ttc;
					$ret[0]["price_min"] = $objp->price_min;
					$ret[0]["price_min_ttc"] = $objp->price_base_type;
					$ret[0]["price_base_type"] = $objp->price_base_type;
					$ret[0]["tva_tx"] = $objp->tva_tx;
				}
			}
		}
		else{
			$ret[0]["tva_tx"] = $objp->tva_tx;
			$ret[0]["price_base_type"] = $objp->price_base_type;
			$ret[0]["price"] = $objp->price;
			$ret[0]["price_ttc"] = $objp->price_ttc;
			$ret[0]["price_min"] = $objp->price_min;
			$ret[0]["price_min_ttc"] = $objp->price_min_ttc;
			if($conf->global->PRODUIT_MULTIPRICES){
				$ret[0]["diff_price"] = 1;
			}
		}
		$ret[0]["localtax1_tx"] = $objp->localtax1_tx;
		$ret[0]["localtax2_tx"] = $objp->localtax2_tx;
		//Meses de precio de venta
        $sql = "SELECT TIMESTAMPDIFF(MONTH, date_price, '".dol_print_date(dol_now(),'%Y-%m-%d')."') as meses";
        $sql .= " FROM ".MAIN_DB_PREFIX."product_price where fk_product=".$idProd;
        $sql .= " ORDER BY date_price DESC LIMIT 1";
        $res=$db->query ($sql);
        if ($res) {
            $obj = $db->fetch_object($res);
            if($obj->meses >= 6)
                $ret[0]["flag"] = 1;
            else
                $ret[0]["flag"] = 0;
        }
        $sql = "SELECT delivery_time_days as entrega FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product=".$idProd." ORDER BY rowid DESC LIMIT 1";
        $res=$db->query ($sql);
        if ($res) {
            $obj = $db->fetch_object($res);
            $ret[0]["delivery_time_days"] = $obj->entrega?$obj->entrega:0;
        }
		$objp->load_stock();
		
		$cash = new Cash($db);
        	
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		
		//TODO controla si estamos vendiendo sin stock y controla que haya al menos una unidad
		/*if(!$conf->global->POS_STOCK)
		{*/
        if(($conf->global->STOCK_SUPPORTS_SERVICES && $objp->type == 1) || $objp->type == 0)
            $ret[0]["stock"] = $objp->stock_warehouse[$cash->fk_warehouse]->real?$objp->stock_warehouse[$cash->fk_warehouse]->real:0;
        else
            $ret[0]["stock"] = "all";
		/*}
		else 
		{
			$ret[0]["stock"] = "all";
		}*/
		$ret[0]["image"] = self::getImageProduct($objp->id, false);
		$ret[0]["thumb"] = self::getImageProduct($objp->id, true);
		$ret[0]["docs"] = self::getDocsProduct($objp->id, true);
		
		if ($objp->id > 0)
		{
			if (!empty($conf->product->enabled))
			{
				$upload_dir = $conf->product->multidir_output[$objp->entity].'/'
							 .get_exdir(0, 0, 0, 0, $objp, 'product')
							 .dol_sanitizeFileName($objp->ref);
			}
			elseif (!empty($conf->service->enabled))
			{
				$upload_dir = $conf->service->multidir_output[$objp->entity].'/'
							 .get_exdir(0, 0, 0, 0, $objp, 'product')
							 .str_replace('/','_',dol_sanitizeFileName($objp->ref));
			}
			$modulepart = 'produit';
			$filearray=dol_dir_list($upload_dir, "files", 0, '\\.pdf$', '(\.meta|_preview.*\.pdf)$', 'date', SORT_DESC, 1);
			
			$ret[0]['pdf_file'] = '';
			if (count($filearray) && isset($filearray[0]['name']))
			{
				$ret[0]['pdf_file'] = DOL_URL_ROOT
									 .'/document.php'
									 .'?modulepart=produit'
									 .'&attachment=0'
									 .'&file='.urlencode(dol_sanitizeFileName($objp->ref).'/'.$filearray[0]['name'])
									 .'&entity=1'
		 							 ;
			}
		}
		
		
		return Errorcontrol($ret,$function);
	}
	
	/**
 	*  Return product info
 	*  
 	*  @param 		string	$idSearch		Part of code, label or barcode
 	*  @param		boolean	$stock			Return stocks of products into info
 	*  @param		int $warehouse			Warehouse id
 	*  @param		int mode				Mode of search
 	*  @param		int $ticketstate		Ticket state
 	*  @return      array					Product info
 	*/
	public static function SearchProduct($idSearch,$stock=false, $warehouse,$mode=0, $ticketstate=0, $customerId)
	{
		global $db, $conf;

		$i=0;

		$ret=-1;
		$function="getProductbyId";

		if(dol_strlen($idSearch) != 0 && dol_strlen($idSearch) < $conf->global->PRODUIT_USE_SEARCH_TO_SELECT && $mode != -5 && $mode != -6)
			return ErrorControl(-2,$function);

		$prefix=empty($conf->global->PRODUCT_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if PRODUCT_DONOTSEARCH_ANYWHERE is on

		if($mode>=0){
			if ($stock)
			{
				$sql ="SELECT distinct p.rowid, p.ref, p.label ,p.price_ttc,";
				$sql .="(select w.reel from ".MAIN_DB_PREFIX."product_stock w left join ".MAIN_DB_PREFIX."entrepot e on w.fk_entrepot = e.rowid";
				$sql .=" where w.fk_product = p.rowid and e.rowid=ep.rowid) as stock1";
                $sql .=",(select w.reel from ".MAIN_DB_PREFIX."product_stock w left join ".MAIN_DB_PREFIX."entrepot e on w.fk_entrepot = e.rowid";
                $sql .=" where w.fk_product = p.rowid and e.rowid<>ep.rowid) as stock2";
                $sql .=",(select s.nom from ".MAIN_DB_PREFIX."product_fournisseur_price pfp left join ".MAIN_DB_PREFIX."societe s on pfp.fk_soc = s.rowid";
                $sql .=" where pfp.fk_product = p.rowid order by pfp.rowid desc limit 1) as supplier";
                $sql .=" , ep.lieu as warehouse, ep.rowid as warehouseId";
                //Mas vendidos en tickets
                $sql .=" ,(SELECT SUM(td.qty) FROM ".MAIN_DB_PREFIX."pos_ticketdet as td, ".MAIN_DB_PREFIX."pos_ticket as t";
                $sql .=" WHERE t.rowid=td.fk_ticket and td.fk_product=p.rowid) as qty_ticket";
                //Mas vendidos en facturas
                $sql .=" ,(SELECT SUM(td.qty) FROM ".MAIN_DB_PREFIX."facturedet as td, ".MAIN_DB_PREFIX."facture as t";
                $sql .=" WHERE t.rowid=td.fk_facture and td.fk_product=p.rowid) as qty_facture";
				$sql .=" FROM ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."entrepot ep ";

			}
			else
			{
				if($conf->global->PRODUIT_MULTIPRICES){
					$sql = "SELECT price_level";
					$sql .= " FROM ".MAIN_DB_PREFIX."societe";
					$sql .= " WHERE rowid = ".$customerId;
					$res=$db->query ($sql);
					if ($res){
						$obj = $db->fetch_object($res);
						if($obj->price_level == NULL){
							$pricelevel= 1;
						}
						else{
							$pricelevel= $obj->price_level;
						}
					}
				}
				else{
					$pricelevel= 1;
				}
				$sql = "SELECT p.rowid, p.ref, p.label, ep.rowid as warehouseId";
				$sql.= " FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_stock as w, ".MAIN_DB_PREFIX."entrepot as ep ";
			}

			$sql.= " WHERE p.tosell = 1 AND ep.statut = 1";
			$sql.= " AND p.entity IN (".getEntity("product", 1).")";
			$sql.= " AND ep.entity =".$conf->entity;
			//if($warehouse>0) $sql.=" AND ep.rowid = ".$warehouse;
			if(!$stock)
			{
				//if(!$conf->global->POS_STOCK){
					$sql.= " AND w.fk_product = p.rowid AND ep.rowid=w.fk_entrepot ";
					if($ticketstate!=1){
						$sql.= " AND w.reel > 0";
					}
				//}
			}

			if(!$conf->global->POS_SERVICES && $stock)
			{
				$sql.= " AND p.fk_product_type = 0";
			}
            $cad_label = explode(' ', $idSearch);
            $temp = "";
            for($i = 0; count($cad_label) > $i; $i++){
                if($i < count($cad_label) - 1) $temp .= $cad_label[$i]."%";
                else $temp .= $cad_label[$i];
            }

            // $sql.= " AND (p.ref LIKE '".$prefix.$db->escape(trim($idSearch))."%' OR p.label LIKE '".$prefix.$db->escape(trim($temp))."%' ";

            $sql.= " AND (p.ref LIKE '%".$db->escape(trim($idSearch))."%' ";
            $sql.= " OR p.label LIKE CONCAT('%', '".$db->escape(trim($temp))."', '%') ";
            $sql.= " OR p.description LIKE CONCAT('%', '".$db->escape(trim($temp))."', '%') ";
            //$sql.= " OR e.lieu LIKE CONCAT('%', '".$db->escape(trim($temp))."', '%') ";

			//$sql.= " AND (p.ref LIKE '".$prefix.$db->escape(trim($idSearch))."%' OR p.label LIKE '".$prefix.$db->escape(trim($idSearch))."%' ";

			if ($conf->barcode->enabled) $sql.= " OR p.barcode='".$db->escape(trim($idSearch))."')";
			else $sql.= ")";

			if(!$stock && $conf->global->POS_SERVICES)
			{
				$sql = "SELECT p.rowid, p.ref, p.label, ep.rowid as warehouseId";
				$sql.= " FROM ".MAIN_DB_PREFIX."product as p left join ".MAIN_DB_PREFIX."product_stock as w on w.fk_product = p.rowid, ".MAIN_DB_PREFIX."entrepot as ep ";
				$sql.= " WHERE (p.tosell = 1 AND  p.entity IN (".getEntity("product", 1).")";
				if(!$conf->global->POS_STOCK)
					$sql.= " AND ep.rowid=w.fk_entrepot ";
				$sql.= " AND (p.ref LIKE '%".$prefix.$db->escape(trim($idSearch))."%' OR p.label LIKE '".$prefix.$db->escape(trim($idSearch))."%' ";
				if ($conf->barcode->enabled)
					$sql.= " OR p.barcode='".$db->escape(trim($idSearch))."')";
				else
					$sql.= ")";
				if(!$conf->global->POS_STOCK && $ticketstate!=1){
					$sql.=" AND ep.rowid = ".$warehouse. " AND w.reel > 0";
				}

				$sql.=" ) OR (p.tosell = 1 AND p.entity IN (".getEntity("product", 1).") AND p.fk_product_type = 1";
				$sql.= " AND (p.ref LIKE '%".$prefix.$db->escape(trim($idSearch))."%' OR p.label LIKE '".$prefix.$db->escape(trim($idSearch))."%' ";
				if ($conf->barcode->enabled)
					$sql.= " OR p.barcode='".$db->escape(trim($idSearch))."')";
				else
					$sql.= ")";
				if(!$conf->global->POS_STOCK){
					$sql.=" AND ep.rowid = ".$warehouse;
				}
				$sql.=")";
			}

			if(!$stock && $conf->global->POS_STOCK)
			{
				$sql.= " GROUP BY p.label";
			}
			else{
				//$sql.= " GROUP BY p.rowid, ep.lieu";
				$sql.= " GROUP BY p.rowid";
				$sql.= " ORDER BY p.label, ep.rowid,qty_facture,qty_ticket";
			}
			$sql.=" LIMIT 100";
		}
		else
		{
		    if($mode == -8)
            {
                $sql ="SELECT distinct p.rowid, p.ref, p.label ,p.price_ttc,";
                $sql .="(select w.reel from ".MAIN_DB_PREFIX."product_stock w left join ".MAIN_DB_PREFIX."entrepot e on w.fk_entrepot = e.rowid";
                $sql .=" where w.fk_product = p.rowid and e.rowid=ep.rowid) as stock1";
                $sql .=",(select w.reel from ".MAIN_DB_PREFIX."product_stock w left join ".MAIN_DB_PREFIX."entrepot e on w.fk_entrepot = e.rowid";
                $sql .=" where w.fk_product = p.rowid and e.rowid=ep.rowid) as stock2";
                $sql .=",(select s.nom from ".MAIN_DB_PREFIX."product_fournisseur_price pfp left join ".MAIN_DB_PREFIX."societe s on pfp.fk_soc = s.rowid";
                $sql .=" where pfp.fk_product = p.rowid order by pfp.rowid desc limit 1) as supplier";
                //$sql .=" , ep.lieu as warehouse, ep.rowid as warehouseId";
                $sql .=" FROM ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."entrepot ep,product_subtitute as ps";
                $sql .=" WHERE p.rowid=ps.fk_product_sus AND ps.fk_product_ori=".$warehouse;
                $sql .=" AND p.tosell = 1 AND ep.statut = 1 AND p.entity IN (".getEntity("product", 1).")";
                //$sql .=" GROUP BY p.rowid, ep.lieu ORDER BY p.label, ep.rowid LIMIT 100";
                $sql .=" GROUP BY p.rowid ORDER BY p.label, ep.rowid LIMIT 100";
            }
		    else if($mode == -9)
            {
                $sql ="SELECT distinct p.rowid, p.ref, p.label ,p.price_ttc,cp.qty,";
                $sql .="(select w.reel from ".MAIN_DB_PREFIX."product_stock w left join ".MAIN_DB_PREFIX."entrepot e on w.fk_entrepot = e.rowid";
                $sql .=" where w.fk_product = p.rowid and e.rowid=ep.rowid) as stock1";
                $sql .=",(select w.reel from ".MAIN_DB_PREFIX."product_stock w left join ".MAIN_DB_PREFIX."entrepot e on w.fk_entrepot = e.rowid";
                $sql .=" where w.fk_product = p.rowid and e.rowid=ep.rowid) as stock2";
                $sql .=",(select s.nom from ".MAIN_DB_PREFIX."product_fournisseur_price pfp left join ".MAIN_DB_PREFIX."societe s on pfp.fk_soc = s.rowid";
                $sql .=" where pfp.fk_product = p.rowid order by pfp.rowid desc limit 1) as supplier";
                //$sql .=" , ep.lieu as warehouse, ep.rowid as warehouseId";
                $sql .=" FROM ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."entrepot ep,complementary_products as cp";
                $sql .=" WHERE p.rowid=cp.fk_product_child AND cp.fk_product_parent=".$warehouse;
                $sql .=" AND p.tosell = 1 AND ep.statut = 1 AND p.entity IN (".getEntity("product", 1).")";
                //$sql .=" GROUP BY p.rowid, ep.lieu ORDER BY p.label LIMIT 100";
                $sql .=" GROUP BY p.rowid ORDER BY p.label, ep.rowid LIMIT 100";
            }
		    else {
                //$sql = "SELECT distinct p.rowid, p.ref, p.label , w.reel as stock, w.fk_entrepot as warehouseId, e.lieu as warehouse ";
                $sql = "SELECT distinct p.rowid, p.ref, p.label , w.reel as stock1, ";
                $sql .="(select t.reel from ".MAIN_DB_PREFIX."product_stock t left join ".MAIN_DB_PREFIX."entrepot eq on t.fk_entrepot <> eq.rowid";
                $sql .=" where t.fk_product = p.rowid and eq.rowid=e.rowid) as stock2";
                $sql .=",(select s.nom from ".MAIN_DB_PREFIX."product_fournisseur_price pfp left join ".MAIN_DB_PREFIX."societe s on pfp.fk_soc = s.rowid";
                $sql .=" where pfp.fk_product = p.rowid order by pfp.rowid desc limit 1) as supplier";
                $sql .= " FROM " . MAIN_DB_PREFIX . "product p INNER JOIN " . MAIN_DB_PREFIX . "product_stock w ON w.fk_product=p.rowid ";
                $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "entrepot e ON e.rowid=w.fk_entrepot";
                $sql .= " WHERE p.entity IN (" . getEntity("product", 1) . ")";
                $sql .= " AND w.fk_entrepot=" . $warehouse;
                if (!$conf->global->POS_SERVICES) {
                    $sql .= " AND p.fk_product_type = 0";
                }
                $sql .= " AND (p.ref LIKE '%" . $prefix . $db->escape(trim($idSearch)) . "%' OR p.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
                if ($conf->barcode->enabled) $sql .= " OR p.barcode='" . $db->escape(trim($idSearch)) . "')";
                else $sql .= ")";
                if ($mode == -1) {//no sell
                    $sql .= " AND p.tosell = 0";
                    $sql .= " LIMIT 100";
                }
                if ($mode == -2) {//sell
                    $sql .= " AND p.tosell = 1";
                    $sql .= " LIMIT 100";
                }
                if ($mode == -3) {//with stock
                    $sql .= " AND w.reel > 0";
                    $sql .= " LIMIT 100";
                }
                if ($mode == -4) {//no stock
                    $sql .= " AND w.reel <= 0";
                    $sql .= " LIMIT 100";
                }
                if ($mode == -5) {//best sell
                    $sql = "SELECT SUM(fd.qty) as qty, pr.rowid, pr.ref, pr.label, ";
                    $sql .= "	(select w.reel from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot = e.rowid";
                    $sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock1,";
                    $sql .= "	(select w.reel from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot <> e.rowid";
                    $sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock2";
                    $sql .=",(select s.nom from ".MAIN_DB_PREFIX."product_fournisseur_price pfp left join ".MAIN_DB_PREFIX."societe s on pfp.fk_soc = s.rowid";
                    $sql .=" where pfp.fk_product = pr.rowid order by pfp.rowid desc limit 1) as supplier";
                    $sql .=" ,(SELECT SUM(td.qty) FROM ".MAIN_DB_PREFIX."pos_ticketdet as td, ".MAIN_DB_PREFIX."pos_ticket as t";
                    $sql .=" WHERE t.rowid=td.fk_ticket and td.fk_product=pr.rowid) as qty_ticket";
                    //$sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock, ep.lieu as warehouse, ep.rowid as warehouseId";
                    $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd, " . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "product as pr,";
                    $sql .= " " . MAIN_DB_PREFIX . "entrepot as ep, " . MAIN_DB_PREFIX . "pos_facture as pf ";
                    $sql .= " WHERE ep.rowid = " . $warehouse . " and pr.tosell = 1 AND f.rowid = fd.fk_facture AND f.entity = " . $conf->entity . " and pr.rowid = fd.fk_product";
                    $sql .= " AND (pr.ref LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR pr.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
                    if ($conf->barcode->enabled) $sql .= " OR pr.barcode='" . $db->escape(trim($idSearch)) . "')";
                    else $sql .= ")";
                    if (!$conf->global->POS_SERVICES) {
                        $sql .= " AND pr.fk_product_type = 0";
                    }
                    $sql .= " and pf.fk_facture = f.rowid GROUP BY fd.fk_product ORDER BY qty,qty_ticket DESC limit 10";
                }
                if ($mode == -6) {//worst sell

                    $sql = "SELECT 0 as qty, pr.rowid, pr.ref, pr.label, ";
                    $sql .= "(select w.reel from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot = e.rowid";
                    $sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock1,";
                    $sql .= "(select w.reel from " . MAIN_DB_PREFIX . "product_stock w left join " . MAIN_DB_PREFIX . "entrepot e on w.fk_entrepot <> e.rowid";
                    $sql .= " where w.fk_product = pr.rowid and e.rowid=ep.rowid) as stock2";
                    $sql .=",(select s.nom from ".MAIN_DB_PREFIX."product_fournisseur_price pfp left join ".MAIN_DB_PREFIX."societe s on pfp.fk_soc = s.rowid";
                    $sql .=" where pfp.fk_product = pr.rowid order by pfp.rowid desc limit 1) as supplier";
                    //$sql .= " ep.lieu as warehouse, ep.rowid as warehouseId";
                    $sql .= " from " . MAIN_DB_PREFIX . "product as pr, " . MAIN_DB_PREFIX . "entrepot as ep";
                    $sql .= " where pr.rowid not in ( SELECT p.rowid";
                    $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd, " . MAIN_DB_PREFIX . "facture as f, " . MAIN_DB_PREFIX . "product as p, " . MAIN_DB_PREFIX . "pos_facture as pf";
                    $sql .= " WHERE p.tosell = 1 AND f.rowid = fd.fk_facture AND f.entity = " . $conf->entity . " and p.rowid = fd.fk_product";
                    $sql .= " AND (pr.ref LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' OR pr.label LIKE '" . $prefix . $db->escape(trim($idSearch)) . "%' ";
                    if ($conf->barcode->enabled) $sql .= " OR pr.barcode='" . $db->escape(trim($idSearch)) . "')";
                    else $sql .= ")";
                    if (!$conf->global->POS_SERVICES) {
                        $sql .= " AND p.fk_product_type = 0";
                    }
                    $sql .= " and pf.fk_facture = f.rowid group by fd.fk_product ) AND ep.rowid = " . $warehouse;
                    if (!$conf->global->POS_SERVICES) {
                        $sql .= " AND pr.fk_product_type = 0";
                    }
                    $sql .= " ORDER BY qty ASC limit 10";
                }
            }
		}
		//print $sql;
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			unset($ret);

			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$ret[$i]["id"] = $objp->rowid;
				$ret[$i]["ref"] = $objp->ref;
				$ret[$i]["label"] = $objp->label;
				$ret[$i]["warehouseId"] = $objp->warehouseId;

				if ($stock)
				{
                    $ret[$i]["warehouse"] = $objp->warehouse;
					$ret[$i]["flag"] = $conf->global->POS_STOCK;
					$ret[$i]["price_ttc"] = $objp->price_ttc;
                    $ret[$i]["supplier"] = $objp->supplier ? $objp->supplier : "SIN ASIGNAR";
                    if($warehouse == 1) {
                        $ret[$i]["matriz"] = is_null($objp->stock1)? '0':$objp->stock1;
                        $ret[$i]["gpe"] = is_null($objp->stock2)?'0':$objp->stock2;
                    }
                    else{
                        $ret[$i]["gpe"] = is_null($objp->stock2)?'0':$objp->stock2;
                        $ret[$i]["matriz"] = is_null($objp->stock1)? '0':$objp->stock1;
                    }
                    /*if ($objp->stock) {
                        if($ret[$i-1]["id"] == $ret[$i]["id"]) {//si coincide el id con el anterior entonces es el mismo producto pero diferente almacen
                            $ret[$i-1]["proff"] = "hubo una coincidencia despues de esto";
                            if($mode != -8 && $mode != -9){
                                if($warehouse == $objp->warehouseId)
                                    $ret[$i-1]["stock1"] = $objp->stock;
                                else
                                    $ret[$i-1]["stock2"] = $objp->stock;
                            }
                            else{
                                if($ret[$i-1]["stock1"] == null)
                                {
                                    $ret[$i-1]["stock1"] = $objp->stock;
                                }
                                else{
                                    $ret[$i-1]["stock2"] = $objp->stock;
                                }
                            }
                            $i--;
                        }
                        else {
                            if($mode != -8 && $mode != -9) {
                                if ($warehouse == $objp->warehouseId) {
                                    $ret[$i]["stock1"] = $objp->stock;
                                    //$ret[$i]["stock2"] = 0;
                                } else {
                                    $ret[$i]["stock2"] = $objp->stock;
                                    //$ret[$i]["stock1"] = 0;
                                }
                            }
                            else{
                                if($mode == -9)
                                    $ret[$i]["qty"]=$objp->qty;
                                if($objp->warehouseId == 1)
                                    $ret[$i]["stock1"] = $objp->stock;
                                else
                                    $ret[$i]["stock2"] = $objp->stock;
                            }
                        }
                    } else {
                        $ret[$i]["stock1"] = 0;
                        $ret[$i]["stock2"] = 0;
                    }*/
				}
				else{
					$prod = new Product($db);
					$prod->fetch($objp->rowid);

					if(!empty( $prod->multiprices[$pricelevel]) && $prod->multiprices[$pricelevel] > 0 ){
						$ret[$i]["price_ttc"] = $prod->multiprices_ttc[$pricelevel];
					}
					else{
						$ret[$i]["price_ttc"] = $prod->price_ttc;
					}
				}
				if ($objp->ref == $idSearch)
				{
					$tmp = $ret[$i];
					unset ($ret);
					$ret[0] = $tmp;
					break;
				}
				$i++;
				

			}
			if($mode == -6 && $num < 10){
				$resto = 10 - $num;
				$sql= "SELECT SUM(facd.qty) as qty, p.rowid, p.ref, p.label, (select wa.reel";
				$sql.= " from ".MAIN_DB_PREFIX."product_stock wa left join ".MAIN_DB_PREFIX."entrepot entr on wa.fk_entrepot = entr.rowid";
				$sql.= " where wa.fk_product = p.rowid and entr.rowid=en.rowid) as stock,";
				$sql.= " en.lieu as warehouse, en.rowid as warehouseId";
				$sql.= " ,(SELECT SUM(td.qty) FROM ".MAIN_DB_PREFIX."pos_ticketdet as td, ".MAIN_DB_PREFIX."pos_ticket as t";
                $sql.= " WHERE t.rowid=td.fk_ticket and td.fk_product=p.rowid) as qty_ticket";
				$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as facd, ".MAIN_DB_PREFIX."facture as fac, ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."entrepot as en, ".MAIN_DB_PREFIX."pos_facture as pfac";
				$sql.= " WHERE p.tosell = 1 AND fac.rowid = facd.fk_facture AND fac.entity = ".$conf->entity;
				$sql.= "AND facd.fk_product != 'NULL'and p.rowid = facd.fk_product AND pfac.fk_facture = fac.rowid and en.rowid = ".$warehouse;
				$sql.= " group by facd.fk_product";
				$sql.= " order by qty,qty_ticket ASC limit ".$resto;

				$resql=$db->query($sql);
				if ($resql)
				{
					$num2 = $db->num_rows($resql);
					$i = $num;

					while ($i < $num2)
					{
						$objp = $db->fetch_object($resql);

						$ret[$i]["id"] = $objp->rowid;
						$ret[$i]["ref"] = $objp->ref;
						$ret[$i]["label"] = $objp->label;
						$ret[$i]["warehouseId"] = $objp->warehouseId;
						$ret[$i]["price_ttc"] = $objp->price_ttc;
                        $ret[$i]["supplier"] = $objp->supplier ? $objp->supplier : "SIN ASIGNAR";

						if ($stock)
						{
							$ret[$i]["warehouse"] = $objp->warehouse;
							if($objp->stock)
							{
								$ret[$i]["stock"] = $objp->stock;
							}
							else
							{
								$ret[$i]["stock"] = 0;
							}
							$ret[$i]["flag"] = $conf->global->POS_STOCK;
						}
						$i++;

					}
				}
			}
		}

		return ErrorControl($ret,$function);
	}
	
	public static function CountProduct($warehouseId)
	{
		global $db, $conf;
		
		$i=0;
		
		$ret=-1;
		$function="getProductbyId";
		
		$sql = "select(select count(p.rowid) from ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."product_stock ps where p.tosell = 0 and p.fk_product_type = 0 and ps.fk_entrepot = ".$warehouseId." and ps.fk_product = p.rowid) as no_venta, ";
		$sql.= "(select count(p.rowid) from ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."product_stock ps where p.tosell = 1 and p.fk_product_type = 0 and ps.fk_entrepot = ".$warehouseId." and ps.fk_product = p.rowid) as en_venta, ";
		$sql.= "(select count(p.rowid) from ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."product_stock ps where p.fk_product_type = 0 ";
		$sql.= "and ps.fk_entrepot = ".$warehouseId." and ps.reel > 0 and ps.fk_product = p.rowid) as con_stock, ";
		$sql.= "(select count(p.rowid) from ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."product_stock ps where p.fk_product_type = 0 ";
		$sql.= " and ps.fk_entrepot = ".$warehouseId." and ps.reel <= 0 and ps.fk_product = p.rowid) as sin_stock";
		
		$res=$db->query($sql);
		
		if ($res)
		{
			$obj = $db->fetch_object($res);
		
			$result["no_sell"] = $obj->no_venta;
			$result["sell"] = $obj->en_venta;
			$result["stock"] = $obj->con_stock;
			$result["no_stock"] = $obj->sin_stock;
			$result["best_sell"] = 10;
			$result["worst_sell"] = 10;
					
			return ErrorControl($result,$function);
		}
		else
		{
			return ErrorControl($ret, $function);
		}
		
		
	}

    /**
     *  Return number sustitute
     *
     *  @param 		string	$idSearch		id of product
     *  @return      int					number of sustitute info
     */
    public static function SearchNumberOfSustitute($idSearch)
    {
        global $db;
        $sql="SELECT COUNT(fk_product_sus) as sustitutes FROM product_subtitute WHERE fk_product_ori = ".$idSearch;
        $resql=$db->query($sql);
        if($resql)
        {
            $number=$db->fetch_object($resql);
            return $number->sustitutes;
        }
        else{
            return 0;
        }
    }
    /**
     *  Return number complements
     *
     *  @param 		string	$idSearch		id of product
     *  @return      int					number of sustitute info
     */
    public static function SearchNumberOfComplements($idSearch)
    {
        global $db;
        $sql="SELECT COUNT(fk_product_child) as complements FROM complementary_products WHERE fk_product_parent = ".$idSearch;
        $resql=$db->query($sql);
        if($resql)
        {
            $number=$db->fetch_object($resql);
            return $number->complements;
        }
        else{
            return 0;
        }
    }

    /**
     *  Return number sustitute
     *
     *  @param 		string	$idSearch		id of product
     *  @return      int					number of sustitute info
     */
    public static function getAllSustitutes($idSearch)
    {
        global $db;
        $ret=array();
        $sql="SELECT p.rowid, p.ref,p.label";
        $sql.=" FROM product_subtitute as ps";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid=ps.fk_product_sus";
        $sql.=" WHERE ps.fk_product_ori=".$idSearch;
        $resql=$db->query($sql);
        if($resql)
        {
            $num=$db->num_rows($resql);
            $i=0;
            while($num > $i) {
                $sustitutes = $db->fetch_object($resql);
                $ret[$i]["id"]=$sustitutes->rowid;
                $ret[$i]["ref"]=$sustitutes->ref;
                $ret[$i]["label"]=$sustitutes->label;
                $i++;
            }
            return $ret;
        }
        else{
            return 0;
        }
    }

    /**
     *  Return number sustitute
     *
     *  @param 		string	$idSearch		id of product
     *  @return      int					number of sustitute info
     */
    public static function getAllComplements($idSearch)
    {
        global $db;
        $ret=array();
        $sql="SELECT p.rowid, p.ref,p.label,cp.qty";
        $sql.=" FROM complementary_products as cp";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid=cp.fk_product_child";
        $sql.=" WHERE cp.fk_product_parent=".$idSearch;
        $resql=$db->query($sql);
        if($resql)
        {
            $num=$db->num_rows($resql);
            $i=0;
            while($num > $i) {
                $complement = $db->fetch_object($resql);
                $ret[$i]["id"]=$complement->rowid;
                $ret[$i]["ref"]=$complement->ref;
                $ret[$i]["label"]=$complement->label;
                $ret[$i]["qty"]=$complement->qty;
                $i++;
            }
            return $ret;
        }
        else{
            return 0;
        }
    }

    /**
     *  Return all customers info
     *
     *  @param		boolean	$extended		Return more info
     *  @return      array					Customer info
     */
    public static function getAllCustomers($extended=false)
    {
         return array();
       global $db, $conf;
        
        $ts = microtime(true);

        $ret=-1;
        $function="getAllCustomers";

        $prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on

        $i=0;

        $sql = "SELECT c.rowid, c.nom, c.code_client, c.siren, c.remise_client,c.outstanding_limit";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as c";
        $sql.= " WHERE c.client = 1";
        $sql.= " AND c.entity = ".$conf->entity;
        $sql.= " ORDER BY c.nom";

        $resql=$db->query($sql);
        $mt[__LINE__] = microtime(true)-$ts;
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            $soc = new Societe($db);
            unset($ret);

            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);
                $ret[$i]['points'] = null;
                if($conf->global->REWARDS_POS && ! empty($conf->rewards->enabled)){
                    $rew= new Rewards($db);
                    $res = $rew->getCustomerReward($objp->rowid);
                    if($res){
                        $ret[$objp->rowid]['points'] = $rew->getCustomerPoints($objp->rowid);
                    }
                }
                $soc->fetch($objp->rowid);
                $ret[$i]["coupon"] = $soc->getAvailableDiscounts();
                $ret[$i]["id"] = $objp->rowid;
                $ret[$i]["nom"] = $objp->nom;
                $ret[$i]["profid1"] = $objp->siren;
                $ret[$i]["remise"] = $objp->remise_client;
                $ret[$i]['limite']= $soc->outstanding_limit?$soc->outstanding_limit:0;
                //Proyectos asociados
                $sqlProyect = "SELECT title as proyect_t, rowid as proyect_id,budget_amount as presupuesto FROM ".MAIN_DB_PREFIX."projet WHERE fk_soc = ".$soc->id." AND fk_statut = 1";
                $resqlProyect=$db->query($sqlProyect);
                $numProyects = $db->num_rows($resqlProyect);
                if($numProyects > 0) {
                    $iterator = 0;
                    while ($iterator < $numProyects) {
                        $proyecto = $db->fetch_object($resqlProyect);
                        $ret[$i]["proyectos"][$proyecto->proyect_id]["proyect"] = $proyecto->proyect_t;
                        $ret[$i]["proyectos"][$proyecto->proyect_id]["proyect_id"] = $proyecto->proyect_id;
                        $ret[$i]["proyectos"][$proyecto->proyect_id]["presupuesto"] = price2num($proyecto->presupuesto);
                        //Gastos y cuentas
                        $sqlGastos = "SELECT SUM(pv.amount) as gastos,SUM(ff.total_ttc)-SUM(pf.amount) as cuentas";
                        $sqlGastos.= " FROM ".MAIN_DB_PREFIX."payment_various as pv";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn AS ff on ff.fk_projet = pv.fk_projet";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn AS pffp ON ff.rowid=pffp.fk_facturefourn";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn AS pf ON pffp.fk_paiementfourn=pf.rowid";
                        $sqlGastos.= " WHERE pv.fk_projet = ".$proyecto->proyect_id;
                        $resGastos=$db->query($sqlGastos);
                        $numeroGastos=$db->num_rows($resGastos);
                        if($numeroGastos > 0)
                        {
                            $gastos=$db->fetch_object($resGastos);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["gastos"] = $gastos->gastos == null ? 0:price2num($gastos->gastos);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["cuentas"] = $gastos->cuentas == null? 0:price2num($gastos->cuentas);
                        }
                        else
                        {
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["gastos"] = 0;
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["cuentas"] = 0;
                        }
                        //Por pagar
                        //facturas
                        $sqlPagar= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
                        $sqlPagar.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                        $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                        $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                        $sqlPagar.=" WHERE f.fk_projet=".$proyecto->proyect_id." AND f.fk_statut=1";
                        $resqlPa = $db->query($sqlPagar);
                        $numeroPagar = $db->num_rows($resqlPa);
                        if($numeroPagar>0) {
                            $ppagar = $db->fetch_object($resqlPa);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["por_pagar"]= $ppagar->total_pagar==null?0:$ppagar->total_pagar;
                        }
                        else
                            $ret[$i]["proyectos"][$proyecto->proyect_id]['por_pagar']=0;

                        $sqlTicket= "SELECT SUM(t.total_ttc) as tickets FROM ".MAIN_DB_PREFIX."pos_ticket as t WHERE fk_soc=".$objp->rowid." and (t.type <>1 and t.fk_statut=1 or t.fk_statut=2)";
                        $resqlTi = $db->query($sqlTicket);
                        $numeroTi = $db->num_rows($resqlTi);
                        if($numeroTi>0) {
                            $tticket = $db->fetch_object($resqlTi);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["por_pagar"]+= $tticket->tickets==null?0:$tticket->tickets;
                        }
                        $mt[__LINE__][$iterator] = microtime(true)-$ts;
                        $iterator++;
                    }
                    $mt[__LINE__] = microtime(true)-$ts;
                }
                else{
                    $ret[$i]["proyectos"][0]["proyect"] = "&nbsp";
                    $ret[$i]["proyectos"][0]["proyect_id"] = 0;
                    $ret[$i]["proyectos"][0]["presupuesto"] = 0;
                    $ret[$i]["proyectos"][0]["gastos"] = 0;
                    $ret[$i]["proyectos"][0]["cuentas"] = 0;
                    $ret[$i]["proyectos"][0]['por_pagar']=0;
                }
                //Limite de credito y cuentas por pagar
                //Cuentas por pagar
                $sqlpp= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
                $sqlpp.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                $sqlpp.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                $sqlpp.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                $sqlpp.=" WHERE f.fk_soc=".$objp->rowid." AND f.fk_statut=1";
                $resqlpp = $db->query($sqlpp);
                $mt[__LINE__] = microtime(true)-$ts;
                if($resqlpp) {
                    $ppagar = $db->fetch_object($resqlpp);
                    $ret[$i]['por_pagar']= $ppagar->total_pagar==null?0:$ppagar->total_pagar;
                }
                else
                    $ret[$i]['por_pagar']=0;
                $sqlTicket= "SELECT SUM(t.total_ttc) as tickets FROM ".MAIN_DB_PREFIX."pos_ticket as t WHERE fk_soc=".$objp->rowid." and (t.type <>1 and t.fk_statut=1 or t.fk_statut=2)";
                $resqlTi = $db->query($sqlTicket);
                $numeroTi = $db->num_rows($resqlTi);
                if($numeroTi>0) {
                    $tticket = $db->fetch_object($resqlTi);
                    $ret[$i]['por_pagar']+= $tticket->tickets==null?0:$tticket->tickets;
                }
                $i++;
            }
        }
        return $ret;
    }

	/**
 	*  Return customer info
 	*  
 	*  @param 		string	$idSearch		Part of code, name, firstname, idprof1
 	*  @param		boolean	$extended		Return more info
 	*  @return      array					Customer info
 	*/
	public static function SearchCustomer($idSearch,$extended=false)
	{
		global $db, $conf;

        require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
		
		$ret=-1;
		$function="SearchCustomer";
		if(dol_strlen($idSearch) == 0)
		    return self::getAllCustomers(false);
		else if(dol_strlen($idSearch) <= $conf->global->COMPANY_USE_SEARCH_TO_SELECT)
			return ErrorControl(-2,$function);

		$prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
		
		$i=0;
		
		$sql = "SELECT c.rowid, c.nom, c.code_client, c.siren, c.remise_client";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as c";
		$sql.= " WHERE c.client = 1";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND (c.nom LIKE '".$prefix.$db->escape(trim($idSearch))."%' OR c.code_client LIKE '".$prefix.$db->escape(trim($idSearch))."%' OR c.siren LIKE '".$prefix.$db->escape(trim($idSearch))."%' ";	
		$sql.= ")";
		$sql.= " ORDER BY c.nom";

		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$soc = new Societe($db);
            $cli = new Client($db);
			unset($ret);
			
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$ret[$i]['points'] = null;
				if($conf->global->REWARDS_POS && ! empty($conf->rewards->enabled)){
					$rew= new Rewards($db);
					$res = $rew->getCustomerReward($objp->rowid);
					if($res){
						$ret[$i]['points'] = $rew->getCustomerPoints($objp->rowid);
					}
				}
				$soc->fetch($objp->rowid);
				$ret[$i]["coupon"] = $soc->getAvailableDiscounts();
				$ret[$i]["id"] = $objp->rowid;
				$ret[$i]["nom"] = $objp->nom;
				$ret[$i]["profid1"] = $objp->siren;
				$ret[$i]["remise"] = $objp->remise_client;
                $ret[$i]['limite']= $soc->outstanding_limit?$soc->outstanding_limit:0;
                $sqlProyect = "SELECT title as proyect_t, rowid as proyect_id,budget_amount as presupuesto FROM ".MAIN_DB_PREFIX."projet WHERE fk_soc = ".$soc->id." AND fk_statut = 1";
                $resqlProyect=$db->query($sqlProyect);
                if($db->num_rows($resqlProyect) > 0) {
                    $numProyects = $db->num_rows($resqlProyect);
                    $iterator = 0;
                    while ($iterator < $numProyects) {
                        $proyecto = $db->fetch_object($resqlProyect);
                        $ret[$i]['proyectos'][$proyecto->proyect_id]["proyect"] = $proyecto->proyect_t;
                        $ret[$i]['proyectos'][$proyecto->proyect_id]["proyect_id"] = $proyecto->proyect_id;
                        $ret[$i]["proyectos"][$proyecto->proyect_id]["presupuesto"] = price2num($proyecto->presupuesto);
                        //Gastos y cuentas
                        $sqlGastos = "SELECT SUM(pv.amount) as gastos,SUM(ff.total_ttc)-SUM(pf.amount) as cuentas";
                        $sqlGastos.= " FROM ".MAIN_DB_PREFIX."payment_various as pv";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn AS ff on ff.fk_projet = pv.fk_projet";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn AS pffp ON ff.rowid=pffp.fk_facturefourn";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn AS pf ON pffp.fk_paiementfourn=pf.rowid";
                        $sqlGastos.= " WHERE pv.fk_projet = ".$proyecto->proyect_id;
                        $resGastos=$db->query($sqlGastos);
                        $numeroGastos=$db->num_rows($resGastos);
                        if($numeroGastos > 0)
                        {
                            $gastos=$db->fetch_object($resGastos);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["gastos"] = $gastos->gastos == null ? 0:price2num($gastos->gastos);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["cuentas"] = $gastos->cuentas == null? 0:price2num($gastos->cuentas);
                        }
                        else
                        {
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["gastos"] = 0;
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["cuentas"] = 0;
                        }
                        //Por pagar
                        $sqlPagar= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
                        $sqlPagar.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                        $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                        $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                        $sqlPagar.=" WHERE f.fk_projet=".$proyecto->proyect_id." AND f.fk_statut=1";
                        $resqlPa = $db->query($sqlPagar);
                        $numeroPagar = $db->num_rows($resqlPa);
                        if($numeroPagar>0) {
                            $ppagar = $db->fetch_object($resqlPa);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["por_pagar"]= $ppagar->total_pagar==null?0:$ppagar->total_pagar;
                        }
                        else
                            $ret[$i]["proyectos"][$proyecto->proyect_id]['por_pagar']=0;
                        //$sqlTicket= "SELECT SUM(t.total_ttc) as tickets FROM ".MAIN_DB_PREFIX."pos_ticket as t WHERE fk_soc=".$objp->rowid." and (t.type <>1 and (t.fk_statut=1 or t.fk_statut=2))";
                        $sqlTicket=  "SELECT sum(IF(t.type=0,t.total_ttc,t.total_ttc*-1) - IF(p.amount IS NOT NULL, p.amount,0)) as tickets "."\r\n"
									."FROM ".MAIN_DB_PREFIX."pos_ticket as t "."\r\n"
									."LEFT JOIN ".MAIN_DB_PREFIX."pos_paiement_ticket AS pf "."\r\n"
									."  ON pf.fk_ticket=t.rowid "."\r\n"
									."LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p "."\r\n"
									."  ON p.rowid = pf.fk_paiement "."\r\n"
									."WHERE fk_soc=".$objp->rowid." "."\r\n"
									."AND (t.fk_statut=1 OR t.fk_statut=2) "."\r\n"
									."AND fk_facture IS NULL"
									."AND fk_project = ".$proyecto->proyect_id."\r\n"
									;

                        $resqlTi = $db->query($sqlTicket);
                        $numeroTi = $db->num_rows($resqlTi);
                        if($numeroTi>0) {
                            $tticket = $db->fetch_object($resqlTi);
                            $ret[$i]['por_pagar']+= $tticket->tickets==null?0:$tticket->tickets;
                        }
                        $iterator++;
                    }
                }
                else{
                    $ret[$i]['proyectos'][0]["proyect"] = "&nbsp";
                    $ret[$i]['proyectos'][0]["proyect_id"] = 0;
                    $ret[$i]["proyectos"][0]["presupuesto"] = 0;
                    $ret[$i]["proyectos"][0]["gastos"] = 0;
                    $ret[$i]["proyectos"][0]["cuentas"] = 0;
                    $ret[$i]["proyectos"][0]['por_pagar']=0;
                }
                //Limite de credito y cuentas por pagar
                //Cuentas por pagar
                /*$sqlpp= "SELECT SUM(f.total_ttc)-sum(IF(p.amount IS NOT NULL, p.amount,0)) as total_pagar";
                $sqlpp.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                $sqlpp.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                $sqlpp.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                $sqlpp.=" WHERE f.fk_soc=".$objp->rowid;
                $resqlpp = $db->query($sqlpp);
                if($db->num_rows($resqlpp) > 0) {
                    $ppagar = $db->fetch_object($resqlpp);
                    $ret[$i]['por_pagar']= $ppagar->total_pagar==null?0:$ppagar->total_pagar;
                }
                else
                    $ret[$i]['por_pagar']=0;
                    $sqlTicket=  "SELECT sum(IF(t.type=0,t.total_ttc,t.total_ttc*-1) - IF(p.amount IS NOT NULL, p.amount,0)) as tickets "."\r\n"
								."FROM ".MAIN_DB_PREFIX."pos_ticket as t "."\r\n"
								."LEFT JOIN ".MAIN_DB_PREFIX."pos_paiement_ticket AS pf "."\r\n"
								."  ON pf.fk_ticket=t.rowid "."\r\n"
								."LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p "."\r\n"
								."  ON p.rowid = pf.fk_paiement "."\r\n"
								."WHERE fk_soc=".$objp->rowid." "."\r\n"
								."AND (t.fk_statut=1 OR t.fk_statut=2) "."\r\n"
								."AND fk_facture IS NULL"
								;
                //$sqlTicket= "SELECT SUM(t.total_ttc) as tickets FROM ".MAIN_DB_PREFIX."pos_ticket as t WHERE fk_soc=".$objp->rowid." and (t.type <>1 and (t.fk_statut=1 or t.fk_statut=2) AND fk_facture IS NULL)";
                //die(var_dump($sqlTicket));
                $resqlTi = $db->query($sqlTicket);
                $numeroTi = $db->num_rows($resqlTi);
                if($numeroTi>0) {
                    $tticket = $db->fetch_object($resqlTi);
                    $ret[$i]['por_pagar']+= $tticket->tickets==null?0:$tticket->tickets;
                }*/

                $cli->fetch($objp->rowid);
                $tmp = $cli->getOutstandingBills();
                $pos = $cli->getOutstandingTickets();
                $ret[$i]['por_pagar']= $tmp['opened']+$pos['opened'];
				$i++;
			}		
		}
		return ErrorControl($ret,$function);
	}
	/**
 	*  Return path of a catergory image 
 	*  
 	*  @param 		int		$idCat		Id of Category
 	*  @return      string				Image path
 	*/
	public static function getImageProduct($idProd, $thumb=false)
	{	
		global $conf, $db;
		
		$extName="_small";
		$extImgTarget=".jpg";
		$outDir="pos";
		$maxWidth =90;
		$maxHeight=90;
		$quality=50;
		$ret=array();
		$iterator=0;
		if($idProd>0)
		{
			$objProd = new Product($db);
			$objProd->fetch($idProd);
			$pdir = get_exdir($idProd,2,0,0,$objProd,'product') . str_replace('/','_',$objProd->ref)."/";// ."/photos/";
			$dir = $conf->product->multidir_output[$objProd->entity].'/'.$pdir;
			foreach ($objProd->liste_photos($dir,4) as $key => $obj)
			{
				$filename = $dir.$obj['photo'];
				$filethumbs= $dir.$outDir.'/'.$obj['photo'];
				
				$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$filethumbs);
				$fileName = basename($fileName);
				$imgThumbName = $dir.$outDir.'/'.$fileName.$extName.$extImgTarget;
				
				$file_osencoded=$imgThumbName;
				if(!file_exists($file_osencoded))
				{
					$file_osencoded=dol_osencode($filename);
					if (file_exists($file_osencoded))
					{
						require_once(DOL_DOCUMENT_ROOT ."/core/lib/images.lib.php");
						vignette($filename,$maxWidth,$maxHeight,$extName,$quality,$outDir,2);			
					}
				}
				
				if (! $thumb)
				{
					$filename=$obj['photo'];
				}
				else 
				{
					$filename=$outDir.'/'.$fileName.$extName.$extImgTarget;
				}

				//$realpath = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$objProd->entity.'&file='.urlencode($pdir.$filename) ;
				$ret[$iterator]=$realpath= DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$objProd->entity.'&file='.urlencode($pdir.$filename) ;
				$iterator++;
			}
			if(!$realpath)
			{
				//$realpath = DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode('noimage.jpg');
                $ret[0] = DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode('noimage.jpg');
			}
			return $ret;
		}

	}


	public static function getDocsProduct($idProd, $thumb=false)
	{	
		global $conf, $db;
		
		$ret = array();
		
		if($idProd>0)
		{
			$objProd = new Product($db);
			$objProd->fetch($idProd);
			$pdir = realpath(DOL_DOCUMENT_ROOT.'/../documents/produit').DIRECTORY_SEPARATOR.str_replace('/','_',$objProd->ref);
			if (is_dir($pdir))
			{
				$files = scandir($pdir);
				foreach ($files as $file)
				{
					if(strlen($file)>5)
					{
						if (strtolower(substr($file,-3)) == 'pdf')
						{
							$ret[] = str_replace('/','_',$objProd->ref).'/'.$file;
						}
					}
				}
			}
			return $ret;
		}

	}


	/**
 	*  Returns internal users of Dolibarr
 	*  @param 		string	$selected		RowId of user for select
 	*  @param    	string	$htmlname		name for object
 	*  @return      array					Dolibarr internal users
 	*/
	public static function select_Users($selected='',$htmlname='users')
	{
		global $db,$conf;
		
		$sql = "SELECT rowid, lastname, firstname, login";
		$sql.= " FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE entity IN (0,".$conf->entity.")";
	
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$var = true;
			$i = 0;
			
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				if (!$obj->fk_societe)
				{
					$userstatic=new User($db);
					$userstatic->fetch($obj->rowid); 
					$userstatic->getrights();
					$dir=$conf->user->dir_output;
					$file='';
					
					if($userstatic->rights->pos->frontend)
					{
						$username = $obj->firstname.' '.$obj->lastname;
						$internalusers[$i]['code'] = $obj->rowid;
						$internalusers[$i]['label'] = $username;
						$internalusers[$i]['login'] = $obj->login;
						
						if ($userstatic->photo)
						{
							$file=get_exdir(0,0,0,0,$userstatic,'user').$userstatic->id.'/'.$userstatic->photo;
						}
						//if (true){
						if ($file && file_exists($dir."/".$file)){
							$internalusers[$i]['photo'] = DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&entity='.$userstatic->entity.'&file='.urlencode($file);
						}
						else{
							$internalusers[$i]['photo'] = DOL_URL_ROOT.'/theme/common/nophoto.jpg';
						}
					}
				}
				
				$i++;
			}
			$db->free($resql);
		}
		
		return $internalusers;
	}
	
	/**
	 * Returns the type payments
	 * 
	 * @return		array					type of payments
	 */
	public static function select_Type_Payments()
	{
		global $db,$conf,$langs;
		
		$cash = new Cash($db);
        	
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		
		$sql = "SELECT id, code, libelle, type";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
        $sql.= " WHERE active > 0 and (id = ".$cash->fk_modepaycash." or id =".$cash->fk_modepaybank." or id =".$cash->fk_modepaybank_extra." or id =".$cash->fk_modepaybank_extra_2." or id =".$cash->fk_modepaybank_extra_3.")";
        $sql.= " ORDER BY id";

        $resql = $db->query($sql);
        
        if ($resql)
        {
        	$langs->load("bills");
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);

                $j = $obj->id == $cash->fk_modepaycash?0:($obj->id == $cash->fk_modepaybank?1:($obj->id == $cash->fk_modepaybank_extra?2:($obj->id == $cash->fk_modepaybank_extra_2?3:4)));
                
                $libelle=($langs->trans("PaymentTypeShort".$obj->code)!=("PaymentTypeShort".$obj->code)?$langs->trans("PaymentTypeShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $payments[$j]['id'] =$obj->id;
                $payments[$j]['code'] =$obj->code;
                $payments[$j]['label']=$libelle;
                $payments[$j]['type'] =$obj->type;
                $i++;
            }
            $db->free($resql);
        }
        
 		
		return $payments;
		
	}
	
	 /**
     *	Get object and lines from database
     * 	@param		int $idTicket	Id of ticket
     *	@return    	Object 			object if OK, <0 if KO
     */
    function fetch($idTicket)
    {
		global $db;
    	$object= new Ticket($db);
    	$res= $object->fetch($idTicket);
    	if($res)
    		return $object;
    	else
    		return -1;
    }
    
	/**
	 * 
	 * Set Ticket into DB
	 * 
	 * @param		array 	$aryTicket 	Ticket object
	 * @return		array	$result		Result
	 */
	public static function SetTicket($aryTicket,$return = '',$is_complementary=false)
	{	
		global $db;
		$function="SetTicket";
		$msg = '';
		$res = 0 ;
		//die(var_dump($aryTicket));
		$ln_vals = array('total','total_ttc','total_ttc_without_discount');
		
		if ($return)
		{
			//die(var_dump($aryTicket));
		}
		
		if($aryTicket['data']['idCoupon'])
		{
			$split = self::splitAbsoluteDiscount($aryTicket['data']['idCoupon'],$aryTicket['data']['customerpay5']);
			$aryTicket['data']['idCoupon']=$split['discount_1'];
		}
		
		if ($aryTicket['data']['newByDifference'] == 1)
		{
			$nwTk = $aryTicket;
			$oldTk = $aryTicket;
			$nwTt = 0;
			$oldTt = $aryTicket['data']['total'];
			$olddp = $aryTicket['data']['difpayment'];
			$oldap = $aryTicket['data']['auxPaymentcus'];
			$oldcp = $aryTicket['data']['customerpay'];
			$oldp1 = $aryTicket['data']['customerpay1'];
			$oldp2 = $aryTicket['data']['customerpay2'];
			$oldp3 = $aryTicket['data']['customerpay3'];
			$oldp4 = $aryTicket['data']['customerpay4'];
			
			
			foreach($aryTicket['data']['lines'] as $k => $v)
			{
				
				if ($aryTicket['data']['lines'][$k]['cant'] != $aryTicket['data']['lines'][$k]['qty_ent'])
				{
					$factor = $aryTicket['data']['lines'][$k]['qty_ent'] / $aryTicket['data']['lines'][$k]['cant'];
					$aryTicket['data']['lines'][$k]['cant'] = $aryTicket['data']['lines'][$k]['qty_ent'];
					foreach ($ln_vals as $lv)
					{
						$aryTicket['data']['lines'][$k][$lv] = $aryTicket['data']['lines'][$k][$lv] * $factor ;
					}
				}
				$nwTt +=  $aryTicket['data']['lines'][$k]['total_ttc'];
			}
			
			# Total del ticket
			if ($aryTicket['data']['total'] != $nwTt)
			{
				$aryTicket['data']['total'] = $nwTt;
			}

			# Pagos previos
			if ($aryTicket['data']['auxPaymentcus'] > $nwTt)
			{
				$aryTicket['data']['auxPaymentcus'] = $nwTt;
			}
			
			# Pagos Actuales
			if ($aryTicket['data']['customerpay'] + $aryTicket['data']['auxPaymentcus']   > $nwTt)
			{
				if ($nwTt - $aryTicket['data']['auxPaymentcus'] > 0)
				{
					$aryTicket['data']['customerpay'] = $nwTt - $aryTicket['data']['auxPaymentcus'];
				}
				else
				{
					$aryTicket['data']['customerpay'] = $nwTt;
				}
				//$aryTicket['data']['customerpay'] = $nwTt - $aryTicket['data']['auxPaymentcus'];
			}
			
			# Diferencia
			if ($aryTicket['data']['auxPaymentcus'] + $aryTicket['data']['customerpay1'] + $aryTicket['data']['customerpay2'] + $aryTicket['data']['customerpay3'] + $aryTicket['data']['customerpay4'] + $aryTicket['data']['difpayment']  > $nwTt)
			{
				$aryTicket['data']['difpayment'] = $nwTt - $aryTicket['data']['auxPaymentcus'] - $aryTicket['data']['customerpay1'] - $aryTicket['data']['customerpay2'] - $aryTicket['data']['customerpay3'] - $aryTicket['data']['customerpay4'];
				if ($aryTicket['data']['difpayment'] <0)
				{
					$nwTk['data']['difpayment'] += $aryTicket['data']['difpayment'];
					$aryTicket['data']['difpayment'] = 0;
				}
			}
			
			if ($aryTicket['data']['customerpay4'] > $aryTicket['data']['customerpay'])
			{
				$aryTicket['data']['customerpay4'] = $aryTicket['data']['customerpay'];
			}

			if ($aryTicket['data']['customerpay3'] + $aryTicket['data']['customerpay4'] > $aryTicket['data']['customerpay'])
			{
				$aryTicket['data']['customerpay3'] = $aryTicket['data']['customerpay'] - $aryTicket['data']['customerpay4'];
			}

			if ($aryTicket['data']['customerpay2'] + $aryTicket['data']['customerpay3'] + $aryTicket['data']['customerpay4'] > $aryTicket['data']['customerpay'])
			{
				$aryTicket['data']['customerpay2'] = $aryTicket['data']['customerpay'] - $aryTicket['data']['customerpay3'] - $aryTicket['data']['customerpay4'];
			}

			if ($aryTicket['data']['customerpay1'] + $aryTicket['data']['customerpay2'] + $aryTicket['data']['customerpay3'] + $aryTicket['data']['customerpay4'] > $aryTicket['data']['customerpay'])
			{
				$aryTicket['data']['customerpay1'] = $aryTicket['data']['customerpay'] - $aryTicket['data']['customerpay2'] - $aryTicket['data']['customerpay3'] - $aryTicket['data']['customerpay4'];
			}
			
			
			
			
			$nwTk['data']['id'] = 0;
			$nwTk['data']['total'] = $oldTt - $nwTt;
			$nwTk['data']['difpayment'] = $olddp - $aryTicket['data']['difpayment'];
			$nwTk['data']['auxPaymentcus'] = $oldap - $aryTicket['data']['auxPaymentcus'];
			$nwTk['data']['customerpay'] = $oldcp - $aryTicket['data']['customerpay'];
			$nwTk['data']['customerpay1'] -= $aryTicket['data']['customerpay1'];
			$nwTk['data']['customerpay2'] -= $aryTicket['data']['customerpay2'];
			$nwTk['data']['customerpay3'] -= $aryTicket['data']['customerpay3'];
			$nwTk['data']['customerpay4'] -= $aryTicket['data']['customerpay4'];
			$nwTk['data']['newByDifference'] = 0;
			$nwTk['data']['state'] = 2;

			
			foreach ($nwTk['data']['lines'] as $k => $v)
			{
				foreach($ln_vals as $lk)
				{
					$nwTk['data']['lines'][$k][$lk] -= $aryTicket['data']['lines'][$k][$lk]; 
				}
				$nwTk['data']['lines'][$k]['cant'] -= $aryTicket['data']['lines'][$k]['cant'];
				$nwTk['data']['lines'][$k]['qty_ent'] = $nwTk['data']['lines'][$k]['cant'];

				$cashid = $_SESSION['TERMINAL_ID'];
				$cash = new Cash($db);
				$cash->fetch($cashid);
				$warehouse = $cash->fk_warehouse;
				//die(var_dump($nwTk['data']['lines'][$k]));
				
				if (abs($nwTk['data']['difpayment']) <= 0.109 /* $nwTk['data']['total'] */)
				{
					if (self::hadStock1($nwTk['data']['id'],$nwTk['data']['lines'][$k]['idProduct'],$nwTk['data']['lines'][$k]['cant'],$warehouse))
					{
						$nwTk['data']['lines'][$k]['ls_warehouse_status'] = '4';
					}
					else
					{
						$nwTk['data']['lines'][$k]['ls_warehouse_status'] = '4';
					}
				}
				else
				{
					$nwTk['data']['lines'][$k]['ls_warehouse_status'] = null;
				}
			}
			$nwLns = array();
			foreach ($nwTk['data']['lines'] as $k => $v)
			{
				if ($nwTk['data']['lines'][$k]['cant'] > 0)
				{
					$nwLns[] = $v;
				}
			}
			$nwTk['data']['lines'] = $nwLns;
			
//var_dump($nwTk['data']['auxPaymentcus']);
			
			if ($nwTk['data']['auxPaymentcus'] > 0 )
			{
				$paiement_id = 0;
				require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
				$now=dol_now();
				$userstatic=new User($db);
				if(! $aryTicket['employeeId'])
				{
					$employee=$_SESSION['uid'];
				}
				else
				{
					$employee=$aryTicket['employeeId'];
				}
				$userstatic->fetch($employee);
		
		
				$cash = new Cash($db);
			
				$terminal = $_SESSION['TERMINAL_ID'];
				$cash->fetch($terminal);
			
				
				$modepay[1] =  $cash->fk_modepaycash;
				$amount[1] = $aryTicket['data']['customerpay1'];

				$payment=new Payment($db);
		
					$payment->datepaye=$now;
					$payment->bank_account=$cash->fk_paycash;
					$payment->amounts[$aryTicket['data']['id']]=$nwTk['data']['auxPaymentcus']*-1;
					$payment->note='Ticket Complementario'.' '.$aryTicket['data']['ref'] ;
					$payment->paiementid=$cash->fk_modepaycash;
					$payment->num_paiement='';
		
					$paiement_id =  $payment->create($userstatic,$soc);
					if ($paiement_id > 0)
					{
						$result=$payment->addPaymentToBank($userstatic,'payment','(CustomerFacturePayment)',$cash->fk_paycash,$aryTicket['data']['customerId'],'','');
						if (! $result > 0)
						{
							$error++;
						}
				}
				else
				{
					//die(var_dump($payment));
					$error++;
				}
			}
				
				//$oldTk['data']['customerpay'] = $nwTk['data']['auxPaymentcus'];
				//$oldTk['data']['customerpay1'] = $nwTk['data']['auxPaymentcus'];
				//$oldTk['type'] = 1;
				//$oldTk['data']['newByDifference'] = '0';
				//self::SetTicket($oldTk);
				if (isset($nwTk))
				{
					$nwTk['data']['customerpay1'] += $nwTk['data']['auxPaymentcus']; 
				}
				else
				{
					//$nwTk['data']['customerpay1'] += $nwTk['data']['auxPaymentcus']; 
				}
				
				$nwTk['data']['auxPaymentcus'] = 0;
			}
//		die (var_dump($aryTicket, $nwTk));						
		
		$data = $aryTicket['data'];
		$lines = $data['lines'];

		

		if(sizeof($data)>0)
		{
			foreach($aryTicket['data']['lines'] as $k => $v)
			{
				if ($aryTicket['data']['lines'][$k]['cant'] == 0 )
				{
					$aryTicket['data']['lines'][$k]['ls_warehouse_status'] = '15';
				}
			}
			if($data['mode']==0){
				if($data['id'])
				{
					$oldTicket = new Ticket($db);
					$oldTicket->fetch($data['id']);
					$res = self::UpdateTicket($aryTicket);
				}
				else 
				{
					//$oldTicket = false;
					$res = self::CreateTicket($aryTicket);
					$oldTicket = new Ticket($db);
					$oldTicket->fetch($res);
				}
				if ($res>0)
				{
					if(!$is_complementary)
					{
						self::setAutoWarehouseStatusOnSave2($res);
					}
					//self::setAutoWarehouseStatusOnSave2($res);
					
					$object = new Ticket($db);
					$object->fetch($res);

					if (true || $object->type ==1 || ($object->type == 0 && $object->statut!=0))
					{
						$stock=self::quitStock($object,$oldTicket);
						if($stock)
						{
							$db->rollback();
							$res = -4;
						}
					}

					if ($aryTicket['data']['idCoupon'] > 0)
					{
						$adisco = new DiscountAbsolute($db);
						if ($adisco->fetch($aryTicket['data']['idCoupon']))
						{
							$adisco->link_to_ticket($res);
						}
					}

				}
				if($aryTicket['data']['newByDifference'] == 1)
				{
					$ntid = self::SetTicket($nwTk,'id',true);
					
					$ndtk = new Ticket($db);
					$ndtk->fetch($ntid);
					if (self::isCredit($ntid))
					{
						foreach($ndtk->lines as $ntln)
						{
							if ($ntln->ls_warehouse_status == '1' || $ntln->ls_warehouse_status == '15'  || is_null($ntln->ls_warehouse_status))
							{
								$ntln->updateEstadoV('11');
							}
						}
					}
					$ivTk = new Ticket($db);
					$ivTk->fetch($res);
					if ($ivTk->fk_facture > 0)
					{
						$sql =   "INSERT INTO `llx_element_element` "
								."(`fk_source`,`sourcetype`,`fk_target`,`targettype`) "
								."VALUES ('{$ntid}','ticket','{$ivTk->fk_facture}','facture')"
								;
						if(!$dbres = $db->query($sql))
						{
							dol_print_error($db);
							die();
						}
						$sql =	 "UPDATE `llx_pos_ticket` "
								."SET `fk_facture`='{$ivTk->fk_facture}' "
								."WHERE `rowid` = {$ntid} "
								;
						if(!$dbres2 = $db->query($sql))
						{
							dol_print_error($db);
							die();
						}
					}
                    //Añadir Ticket complementario como objeto vinculado
                    $sql =   "INSERT INTO `llx_element_element` "
                        ."(`fk_source`,`sourcetype`,`fk_target`,`targettype`) "
                        ."VALUES ({$res},'ticket',{$ntid},'ticket')";
                    $dbres = $db->query($sql);
                    if(!$dbres)
                    {
                        dol_print_error($db);
                        die();
                    }
                    //Agregar ticket complementario como nota en ticket
                    $sql =	 "UPDATE `llx_pos_ticket` "
                        ."SET `note`='Ticket Complementario: {$ndtk->ref}' "
                        ."WHERE `rowid` = {$res}"
                    ;
                    $dbres2 = $db->query($sql);
                    if(!$dbres2)
                    {
                        dol_print_error($db);
                        die();
                    }
					$msg = '<br />Ticket relacionado: <b>'.$ndtk->ref.'</b>'; 
					
				}
			}
			else
			{
				$res = self::CreateFacture($aryTicket);
			}
			
			

		}
		
		if ($return == '')
		{
			return ErrorControl($res,$function,$msg);
		}
		else
		{
			return $res;
		}
		
	}
	
	/**
	 * 
	 * Get Ticket from DB
	 * 
	 * @param 	int		$id		Id Ticket to load
	 * @return	array			Array with data	
	 */
	public static function GetTicket($id)
	{
		$function="GetTicket";
		$res = 0 ;
		
		if($id)
		{
			$ret=self::LoadTicket($id);		
			return ErrorControl($ret, $function);
		}
		else
		{
			return ErrorControl($res,$function);		
		}
	}
	
	/**
	 *
	 * Get Facture from DB
	 *
	 * @param 	int		$id		Id Ticket to load
	 * @return	array			Array with data
	 */
	public static function GetFacture($id)
	{
		$function="GetTicket";
		$res = 0 ;
	
		if($id)
		{
			$ret=self::LoadFacture($id);
			return ErrorControl($ret, $function);
		}
		else
		{
			return ErrorControl($res,$function);
		}
	}
	
	/**
	 * 
	 * Load Ticket from DB
	 * 
	 * @param 	int 	$id		Id of ticket
	 * @return	array			Array with ticket data
	 */
	Private function LoadTicket($id)
	{
		global $db, $conf;
		$dataticket = array();
		
		$data = array();
		
		$object = new Ticket($db);
		$res=$object->fetch($id);
		
		
		if($res)
		{
			require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");

			$data['id'] = $object->id;
			$data['ref'] = $object->ref;
			$data['type'] = $object->type;
			$data['customerId'] = $object->socid;
			$data['infoCustomer_ref'] = $object->note_public;
			
			//SQL PARA SABER SI VIENE DE PROPAL EL TICKET
			$sql1 = "SELECT rowid FROM ".MAIN_DB_PREFIX."element_element WHERE fk_target = ".$object->id." AND targettype = 'ticket'";
			$resql1 = $db->query($sql1);
			if($resql1){
				$num1 = $db->num_rows($res);
				if($num1 > 0){
					$obj1 = $db->fetch_object($resql1);
					$data['isPropal']= 1;
				}else{
					$data['isPropal']= 0;
				}				
			}

			// hay que cargar nombre
			$soc = new Societe($db);
			$soc->fetch($object->socid);
			$data['customerName'] = $soc->name;
			$data['remise_percentCustom'] = $soc->remise_percent;
			$data['siren'] = $soc->idprof1;
			
			if ($object->socid == '8')
			{
				$data['lim_cred'] = 0;//'$'.number_format(0,0,'.',',');
				$data['dis_cred'] = 0;//'$'.number_format(0,0,'.',',');
			}
			else
			{
				$cust_data = self::GetCustomer($object->socid,true);
				if ($object->fk_project)
				{
					if ($cust_data[0]['proyectos'][$object->fk_project]['presupuesto'] > 0)
					{
						$lim_cred = $cust_data[0]['proyectos'][$object->fk_project]['presupuesto'];
					}
					else
					{
						$lim_cred = 0;
					}
					if ($cust_data[0]['proyectos'][$object->fk_project]['por_pagar'] > 0)
					{
						$dis_cred = $lim_cred - $cust_data[0]['proyectos'][$object->fk_project]['por_pagar'];
					}
					else
					{
						$dis_cred = $lim_cred;
					}
					$data['lim_cred'] = intval($lim_cred);//'$'.number_format($lim_cred,0,'.',',');
					$data['dis_cred'] = intval($dis_cred);//'$'.number_format($dis_cred,0,'.',',');
				}
				else
				{
					if ($cust_data[0]['limite'] > 0)
					{
						$lim_cred = $cust_data[0]['limite'];
					}
					else
					{
						$lim_cred = 0;
					}
					if ($cust_data[0]['por_pagar'] > 0)
					{
						$dis_cred = $lim_cred - $cust_data[0]['por_pagar'];
					}
					else
					{
						$dis_cred = $lim_cred;
					}
					$data['lim_cred'] = intval($lim_cred);//'$'.number_format($lim_cred,0,'.',',');
					$data['dis_cred'] = intval($dis_cred);//'$'.number_format($dis_cred,0,'.',',');
				}
			}
			$data['points'] = null;
			$data['projetId'] = $object->fk_project;
			/*if($conf->global->REWARDS_POS && ! empty($conf->rewards->enabled)){
				$rew= new Rewards($db);
				$res = $rew->getCustomerReward($object->socid);
				if($res){
					$data['points'] = $rew->getCustomerPoints($object->socid);
				}
			}*/				
			$data['coupon'] = $soc->getAvailableDiscounts();
			$data['state'] = $object->statut;
			$data['discount_percent'] = $soc->remise_percent?$soc->remise_percent:$object->remise_percent;
			$data['discount_qty'] = $object->remise_absolut;
			$data['payment_type'] =$object->mode_reglement_id;
			$data['customerpay'] =$object->customer_pay;
			$data['difpayment'] = $object->diff_payment;
			$data['total_ttc'] = $object->total_ttc;
			$data['id_place'] = $object->fk_place;
			$data['note'] = $object->note;
			$data['lines'] = self::LoadTicketLines($object->lines,$object->id);
			
			
			$data['ret_points'] = $object->getSommePaiement();
			
			$sql = "SELECT sum(pf.amount) as amount FROM ".MAIN_DB_PREFIX."pos_paiement_ticket as pf, ".MAIN_DB_PREFIX."pos_ticket as f";
			$sql .= " WHERE f.entity = ".$conf->entity." AND f.rowid = pf.fk_ticket AND f.fk_ticket_source = ".$id;
			$resql = $db->query($sql);
			$obj = $db->fetch_object($resql);
				
			$data['ret_points']= price2num($data['ret_points'] + $obj->amount,'MT');
            //Nombre del Proyecto
            if($object->fk_project) {
                $sql = "SELECT title FROM " . MAIN_DB_PREFIX . "projet where rowid=" . $object->fk_project;
                $resql = $db->query($sql);
                $obj = $db->fetch_object($resql);
                $data['proyect_name'] = $obj->title;
            }
            else
            {
                $data['proyect_name'] = '';
            }
			$dataticket['data']= $data;
			return $dataticket;	
		}
		else
		{
			return $res;
		}
	}
	
	/**
	 *
	 * Load Facture from DB
	 *
	 * @param 	int 	$id		Id of facture
	 * @return	array			Array with ticket data
	 */
	Private function LoadFacture($id)
	{
		global $db, $conf;
		$dataticket = array();
	
		$data = array();
	
		$object = new Facture($db);
		$res=$object->fetch($id);
	
		if($res)
		{
			require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");

			$data['id'] = $object->id;
			$data['ref'] = $object->ref;
			$data['type'] = $object->type;
			$data['customerId'] = $object->socid;
			//hay que cargar nombre
			$soc = new Societe($db);
			$soc->fetch($object->socid);
			$data['customerName'] = $soc->name;
			$data['state'] = $object->statut;
			$data['discount_percent'] = $object->remise_percent;
			$data['discount_qty'] = $object->remise_absolue;
			$data['payment_type'] =$object->mode_reglement_id;
			$data['total_ttc'] = $object->total_ttc;
			$data['lines'] = self::LoadFactureLines($object->lines);
			
			$listofpayments=$object->getListOfPayments();
			foreach($listofpayments as $paym)
			{
				// This payment might be this one or a previous one
				if ($paym['type']!='PNT')
				{
					$data['ret_points']+= $paym['amount'];
				}
			}
			$sql = "SELECT sum(pf.amount) as amount FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f";
			$sql .= " WHERE f.entity = ".$conf->entity." AND f.rowid = pf.fk_facture AND f.fk_facture_source = ".$id;
			$resql = $db->query($sql);
			$obj = $db->fetch_object($resql);
			
			$data['ret_points']= price2num($data['ret_points'] + $obj->amount,'MT');
	
			$dataticket['data']= $data;
			return $dataticket;
		}
		else
		{
			return $res;
		}
	}
	
	/**
	 * 
	 * Load lines of a ticket.
	 * 
	 * @param 	array 	$lines		Lines into database
	 * @return	array				Lines for front end
	 */
	private function LoadTicketLines($lines,$ticket)
	{
		global $db,$conf;
		$aryLines = array();
		$prod = new Product($db);
		$i=0;
		foreach ( $lines as $line )
		{
			if(is_object($line) && sizeof($line)>0)
			{	
				$prod->fetch($line->fk_product);
				$aryLines[$i]['id'] = $line->rowid;
				$aryLines[$i]['label'] = $prod->label;
				$aryLines[$i]['ref'] = $prod->ref;
				$aryLines[$i]['price'] = $line->subprice;
				$aryLines[$i]['cant'] = $line->qty;
				$aryLines[$i]['tva_tx'] = $line->tva_tx;
				$aryLines[$i]['localtax1_tx'] = $line->localtax1_tx;
				$aryLines[$i]['localtax2_tx'] = $line->localtax2_tx;
				$aryLines[$i]['idProduct'] = $line->fk_product;
				$aryLines[$i]['discount'] = $line->remise_percent;
				$aryLines[$i]['total_ttc'] = $line->total_ttc;
				$aryLines[$i]['remise'] = $line->remise;
				$aryLines[$i]['fk_product_type'] = $line->fk_product_type;
				if($line->note != 'null')$aryLines[$i]['note'] = $line->note;
				else $aryLines[$i]['note'] = '';
				$sql="SELECT ls_warehouse_status FROM ".MAIN_DB_PREFIX."pos_ticketdet WHERE fk_product='".$line->fk_product."' AND fk_ticket =".$ticket;
				if (!$resql=$db->query($sql))
				{
					dol_print_error($db);
				}
				$obj=$db->fetch_object($resql);
				$aryLines[$i]['ls_warehouse_status'] = $obj->ls_warehouse_status;
				$aryLines[$i]['ls_warehouse_status_by'] = $line->ls_warehouse_status_by;
				$aryLines[$i]['ls_warehouse_status_date'] = $line->ls_warehouse_status_date;
				$aryLines[$i]['qty_ent'] = is_null($line->qty_ent) ? '0':$line->qty_ent;
				$aryLines[$i]['ls_stock_mv_code'] = is_null($line->ls_stock_mv_code)?'':$line->ls_stock_mv_code;
				
                $prod->load_stock();

                $cash = new Cash($db);

                $terminal = $_SESSION['TERMINAL_ID'];
                $cash->fetch($terminal);

                //TODO controla si estamos vendiendo sin stock y controla que haya al menos una unidad
                //if(!$conf->global->POS_STOCK)
                //{
                if(($conf->global->STOCK_SUPPORTS_SERVICES && $prod->type == 1) || $prod->type == 0)
                    $aryLines[$i]["stock"] = $prod->stock_warehouse[$cash->fk_warehouse]->real?$prod->stock_warehouse[$cash->fk_warehouse]->real:0;
                else
                    $aryLines[$i]["stock"] = "all";
                /*}
                else
                {
                    $aryLines[$i]["stock"] = "all";
                }*/
												
				$i++;	
			}
		}
		return $aryLines;
	}
	
	/**
	 *
	 * Load lines of a facture.
	 *
	 * @param 	array 	$lines		Lines into database
	 * @return	array				Lines for front end
	 */
	private function LoadFactureLines($lines)
	{
		global $db;
		$aryLines = array();
		$prod = new Product($db);
		$i=0;
		foreach ( $lines as $line )
		{
			if(sizeof($line)>0)
			{
				if(empty($line->fk_product)){
					$aryLines[$i]['label'] = $line->desc;
				}
				else{
					$prod->fetch($line->fk_product);
					$aryLines[$i]['label'] = $prod->label;
				}
				
				$aryLines[$i]['id'] = $line->rowid;
				$aryLines[$i]['price'] = $line->subprice;
				$aryLines[$i]['cant'] = $line->qty;
				$aryLines[$i]['tva_tx'] = $line->tva_tx;
				$aryLines[$i]['localtax1_tx'] = $line->localtax1_tx;
				$aryLines[$i]['localtax2_tx'] = $line->localtax2_tx;
				$aryLines[$i]['idProduct'] = $line->fk_product;
				$aryLines[$i]['discount'] = $line->remise_percent;
				$aryLines[$i]['total_ttc'] = $line->total_ttc;
	
				$i++;
			}
		}
		return $aryLines;
	}
	
	/**
	 * 
	 * Create ticket into Database
	 * 
	 * @param	array	$aryTicket		Ticket object
	 */
	Private function CreateTicket($aryTicket,$is_complementary=false)
	{
		global $db,$user,$conf;
		
		$function="CreateTicket";
		$idTicket = -1 ;
		
		$data = $aryTicket['data'];
		$lines = $data['lines'];

		if($data['idsource']>0)
		{
			$prods_returned=self::testSource($aryTicket);
			
			if(sizeof($prods_returned)>0)
			{
				return -6;
			}
			$vater=self::fetch($data['idsource']);
			
			$data['payment_type']=$vater->mode_reglement_id;
		}
		
		$cash = new Cash($db);
		 
		$terminal = $data["cashId"];
		$cash->fetch($terminal);
		//checkpoint: 
		//	The TERMINAL name must have in the last word, the letter who is going to be in the prefix. 
		//	Eg. Pto. de venta Suc. Norte ('N' will be the prefix).
		#$term = explode(" ", $cash->name);
		#$pre_prefix = current(str_split(end($term) ) );
        if(! $data['customerId'])
        {
        	$socid=$cash->fk_soc;
        	$data['customerId']=$socid;
        }
        else 
        {
        	$socid=$data['customerId'];
        }
        
		if(! $data['employeeId'])
        {
        	$employee=$_SESSION['uid'];
        }
        else 
        {
        	$employee=$data['employeeId'];
        }

		$object = new Ticket($db);
		$object->type=$data['type'];
		$object->socid= $socid;
		$object->statut = $data['state'];
		$object->fk_cash = $terminal;
		$object->fk_project = $data['proyectId'];//boa
		//$object->remise_percent = $data['discount_percent'];
		$object->remise_percent = 0;
		$object->remise_absolut = $data['discount_qty'];
		if($data['customerpay1'] > 0)
			$object->mode_reglement_id = $cash->fk_modepaycash;
		else if($data['customerpay2'] > 0)
			$object->mode_reglement_id = $cash->fk_modepaybank;
		else
			$object->mode_reglement_id = $cash->fk_modepaybank_extra;
		
		$object->fk_place = $data['id_place'];  
		$object->note = $data['note'];  
				
		$object->customer_pay = 0; //$data['customerpay'];
		
		$object->diff_payment = $data['difpayment']+$data['customerpay'];
		$object->id_source = $data['idsource'];
		$object->note_public = $data['infoCustomer_ref'];

		//checkpoint assign the pre_prefix value in the object
		#$object->pre_prefix = $pre_prefix;

		$db->begin;
		
		$idTicket=$object->create($employee);
		$data['ref'] = $object->ref;
		if($idTicket<0) 
		{
			$db->rollback();
			return -1;
		}
		else 
		{
			$data['id']=$idTicket;
			if($data['id_place'])
			{
				$place = new Place($db);
				$place->fetch($data['id_place']);
				$place->fk_ticket = $idTicket;
				$place->set_place($idTicket);
			}
			$idLines = self::addTicketLines($lines,$idTicket,($object->type==1 ? true:false));
						
			if($idLines<0) 
			{
				$db->rollback();
				return -2;
			}
			else 
			{
				if($object->fk_place)
				{
					$place = new Place($db);
					$place->fetch($object->fk_place);
				}
				
				if($object->statut!=0)
				{
					//Adding Payments
					if($data['customerpay'] > 0)
					{
                        $soc = new Societe($db);
                        $soc->fetch($object->socid);
						$payment=self::addPayment($data,$soc);
						if(!$payment)
						{
							$db->rollback();
							return -3;
						}
					}

					if($object->diff_payment <= 0.01)
					{
						$object->set_paid($user);
					}
					//Decrease stock//
					
					
					// liberar puesto
					if($place)
					{
						$place->free_place();
					}					
				}
				else
				{
					// usar puesto
					if($place)
					{
						$place->set_place($idTicket);
					}	
				}
			}
		}
		if($object->type == 1)//Si es una devolucion
        {
            //Creamos una instancia de Societe para el cliente de la devolucion
            $soc = new Societe($db);
            $soc->fetch($object->socid);
			if ($object->id_source)
			{
				$object->add_object_linked('ticket',$object->id_source);
				$s_object = new Ticket($db);
				$s_object->fetch($object->id_source);
				$was_invoiced = (is_numeric($s_object->fk_facture) && $s_object->fk_facture>0);
			}
            $object->fetch($idTicket);//Fetch para extraer el total_ttc y el customer_pay
            
            
            if (!$was_invoiced)
            {
	            //Creamos el descuento absoluto con la diferencia(resta por pagar), el usuario, la nota del descuento será la referencia del ticket
    	        $discountid = $soc->set_remise_except(($object->total_ttc - $object->customer_pay), $user, $object->ref, 0, 0);
        	    dol_syslog('Ticket::Descuento absoluto para el cliente con el id numero '.$discountid);
        	    //$db->rollback();
            }
            else
            {
            	$facid = $object->create_facture();
            	$static_facture = new Facture($db);
            	$static_facture->fetch($facid);
            	
            	$static_facture->validate($user,null,$cash->fk_warehouse);
            	//$db->commit;
            	//die (var_dump($facid));
            }
            
            //die(var_dump($was_invoiced));
        }
		$db->commit;
		return $idTicket;
	}

    /**
     *
     * Create ticket into Database from Propal
     *
     * @param	array	$aryTicket		Ticket object
     */
    public static function CreateTicketPropal($aryTicket)
    {
        global $db,$user,$conf;

        $function="CreateTicket";
        $idTicket = -1 ;

        $data = $aryTicket['data'];
        $lines = $data['lines'];

        if($data['idsource']>0)
        {
            $prods_returned=self::testSource($aryTicket);

            if(sizeof($prods_returned)>0)
            {
                return -6;
            }
            $vater=self::fetch($data['idsource']);

            $data['payment_type']=$vater->mode_reglement_id;
        }

        $cash = new Cash($db);

        $terminal = $data["cashId"];
        $cash->fetch($terminal);
        //checkpoint: 
        //	The TERMINAL name must have in the last word, the letter who is going to be in the prefix. 
        //	Eg. Pto. de venta Suc. Norte ('N' will be the prefix).
        #$term = explode(" ", $cash->name);
        #$pre_prefix = current(str_split(end($term) ) );
        if(! $data['customerId'])
        {
            $socid=$cash->fk_soc;
            $data['customerId']=$socid;
        }
        else
        {
            $socid=$data['customerId'];
        }

        if(! $data['employeeId'])
        {
            $employee=$_SESSION['uid'];
        }
        else
        {
            $employee=$data['employeeId'];
        }

        $object = new Ticket($db);
        $object->type=$data['type'];
        $object->socid= $socid;
        $object->statut = $data['state'];
        $object->fk_cash = $terminal;
        $object->fk_project = $data['proyectId'];//boa
        $object->remise_percent = $data['discount_percent'];
        $object->remise_absolut = $data['discount_qty'];
        if($data['customerpay1'] > 0)
            $object->mode_reglement_id = $cash->fk_modepaycash;
        else if($data['customerpay2'] > 0)
            $object->mode_reglement_id = $cash->fk_modepaybank;
        else
            $object->mode_reglement_id = $cash->fk_modepaybank_extra;

        $object->fk_place = $data['id_place'];
        $object->note = $data['note'];

        $object->customer_pay = $data['customerpay'];

        $object->diff_payment = $data['difpayment'];
        $object->id_source = $data['idsource'];

        //checkpoint assign the pre_prefix value in the object
        #$object->pre_prefix = $pre_prefix;

        $db->begin;

        $idTicket=$object->create($employee);
        $data['ref'] = $object->ref;

        if($idTicket<0)
        {
            $db->rollback();
            return -1;
        }
        else
        {
            //Adding lines
            $data['id']=$idTicket;
            if($data['id_place'])
            {
                $place = new Place($db);
                $place->fetch($data['id_place']);
                $place->fk_ticket = $idTicket;
                $place->set_place($idTicket);
            }
            $idLines = self::addTicketLinesPropal($lines,$idTicket,($object->type==1 ? true:false));

            if($idLines<0)
            {
                $db->rollback();
                return -2;
            }
            else
            {
                if($object->fk_place)
                {
                    $place = new Place($db);
                    $place->fetch($object->fk_place);
                }

                if($object->statut!=0)
                {
                    //Adding Payments
                    $soc = new Societe($db);
                    $soc->fetch($object->socid);
                    $payment=self::addPayment($data,$soc);
                    if($payment <0)
                    {
                        $db->rollback();
                        return -3;
                    }
                    else
                    {
                        if($object->diff_payment <= 0)
                        {
                            $object->set_paid($user);
                        }
                    }
                    //Decrease stock

                    $stock=self::quitSotck($lines,($object->type==1 ? true:false));

                    if($stock)
                    {
                        $db->rollback();
                        return -4;
                    }

                    // liberar puesto
                    if($place)
                    {
                        $place->free_place();
                    }
                }
                else
                {
                    // usar puesto
                    if($place)
                    {
                        $place->set_place($idTicket);
                    }
                }
            }
        }
        $db->commit;
        $sql ="INSERT INTO ".MAIN_DB_PREFIX."element_element(fk_source,sourcetype,fk_target,targettype)";
        $sql.=" VALUES(";
        $sql.=$data['originid'];
        $sql.=",'".$data['origintype']."'";
        $sql.=",".$idTicket;
        $sql.=",'ticket'";
        $sql.=")";
        $resql=$db->query($sql);

        return $idTicket;
    }
	
	/**
	 *
	 * Create facture into Database
	 *
	 * @param	array	$aryTicket		Ticket object
	 */
	Private function CreateFacture($aryTicket)
	{
		global $db,$user,$conf;
		
		$function="CreateFacture";
		$idFacture = -1 ;
		
		$data = $aryTicket['data'];
		$lines = $data['lines'];
        $idTicket = $data["id"];
        
	
		if($data['idsource']>0)
		{
			$prods_returned=self::testSourceFac($aryTicket);
			
			if(sizeof($prods_returned)>0)
			{
				return -6;
			}
			$vater = new Facture($db);
			$vater->fetch($data['idsource']);
			
			$data['payment_type']=$vater->mode_reglement_id;
		}
		
		$cash = new Cash($db);
		 
		$terminal = $data['cashId'];
		$cash->fetch($terminal);
		
        if(! $data['customerId'])
        {
        	
        	$socid=$cash->fk_soc;
        	$data['customerId']=$socid;

        }
        else 
        {
        	$socid=$data['customerId'];
        }
        
		if(! $data['employeeId'])
        {
        	$employee=$_SESSION['uid'];

        }
        else 
        {
        	$employee=$data['employeeId'];
        }
        if($data['mode']==1){
			$object = new Facturesim($db);
        }
        else{
        	$object = new Facture($db);
        }
		$object->type=($data['type']==0?0:2);
		$object->socid= $socid;
		$object->statut = $data['state'];
		$object->fk_cash = $terminal;
		$object->remise_percent = $data['discount_percent'];
		$object->remise_absolue = $data['discount_qty'];
		
		if($data['customerpay1'] > 0)
			$object->mode_reglement_id = $cash->fk_modepaycash;
		else if($data['customerpay2'] > 0)
			$object->mode_reglement_id = $cash->fk_modepaybank;
		else
			$object->mode_reglement_id = $cash->fk_modepaybank_extra;
				
		$object->fk_place = $data['id_place'];  
		$object->note_private = $data['note'];  
				
		$object->customer_pay = $data['customerpay'];
		
		if($object->customer_pay > 0){
			$object->diff_payment = $data['difpayment'];
		}
		else{
			$object->diff_payment = $data['total'];
		}
		
		$object->fk_facture_source = $data['idsource'];
		
		$employ = new User($db);
		$employ->fetch($employee);
		$employ->getrights();
		$now = dol_now();
		$object->date = $now;
				
		$db->begin;
		
		$idFacture=$object->create($employ);
        $idLines = self::addFactureLines($lines,$idFacture,($object->type==1 ? true:false));
		if ($object->statut==1 || $object->type==2)
		{
			$res = $object->validate($employ);
			if($res < 0){
				$soc = new Societe($db);
				$soc->fetch($socid);
				$num = $object->getNextNumRef($soc);
				// Validate
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
				$sql.= " SET ref='".$num."', fk_statut = 1, fk_user_valid = ".$employ->id.", date_valid = '".$db->idate($now)."'";
				if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))	// If option enabled, we force invoice date
				{
					$sql.= ', datef='.$db->idate($now);
					$sql.= ', date_lim_reglement='.$db->idate($now);
				}
				$sql.= ' WHERE rowid = '.$object->id;
				
				dol_syslog(get_class($this)."::validate sql=".$sql);
				$resql=$db->query($sql);
				$object->ref = $num;
			}
			
		}
		
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'pos_facture (fk_cash, fk_place,fk_facture,customer_pay) VALUES ('.$object->fk_cash.','.($object->fk_place ? $object->fk_place: 'null').','.$idFacture.','.$object->customer_pay.')';
		 
		dol_syslog("pos_facture::update sql=".$sql);
		$resql=$db->query($sql);
		if (! $resql)
		{
			$this->db->rollback();
			return -1;
		}
		$data['ref'] = $object->ref;
		
		if($idFacture<0) 
		{
			$db->rollback();
			return -1;
		}
		else 
		{
			//Adding lines
			$data['id']=$idFacture;

			//introducir descuentos
			if(!empty($data['idCoupon'])){			
				$res_dis = $object->insert_discount($data['idCoupon']);
			}
			else{
				$res_dis = 1;
			}



			if($idLines<0 || $res_dis <0) 
			{
				$db->rollback();
				return -2;
			}
			else 
			{
				//Adding Payments
				$payment=self::addPaymentFac($data);
				if($payment < 0)
				{
					$db->rollback();
					return -3;
				}
				
				//Decrease stock
				
				$stock=self::quitSotck($lines,($object->type==2 ? true:false));
				
				if($stock)
				{
					$db->rollback();
					return -4;
				}
			}
		}
				
		if($idTicket){
			$ticket= new Ticket($db);
			$ticket->fetch($idTicket);
			$ticket->delete_ticket();
		}
		
		return $idFacture;
	}
	

	/**
	 * 
	 * Update Ticket into Database
	 * @param	array 		$aryTicket		Ticket object	
	 */
	private function UpdateTicket($aryTicket)
	{
	global $db, $conf;
		
		$function="UpdateTicket";
		$idTicket = -1 ;
		
		$data = $aryTicket['data'];
		$lines = $data['lines'];
        
        $idTicket = $data['id'];
        $statut = 0;
        
        if(! $data['customerId'])
        {
        	$cash = new Cash($db);
        	
        	$terminal = $_SESSION['TERMINAL_ID'];
        	$cash->fetch($terminal);
        	$socid=$cash->fk_soc;

        }
        else 
        {
        	$socid=$data['customerId'];
        }
        
		if(! $data['employeeId'])
        {
        	$employee=$_SESSION['uid'];

        }
        else 
        {
        	$employee=$data['employeeId'];
        }
		$object = new Ticket($db);
		$object->fetch($idTicket);
		$object->type=$data['type'];
		$object->socid= $socid;
		$object->statut = $data['state'];
		$object->fk_cash = $_SESSION['TERMINAL_ID'];
		$object->remise_percent = $data['discount_percent'];
		$object->remise_absolut = $data['discount_qty'];
		$object->mode_reglement_id = $data['payment_type'];
		$object->fk_place = $data['id_place'];
		$object->note = $data['note'];		
		$object->fk_project = $data['proyectId'];//boa
		$object->total_ttc = $data['total'];;
		$object->diff_payment = $data['difpayment'];
		$object->note_public = $data['infoCustomer_ref'];
				
		$cash=new Cash($db);
		$cash->fetch($_SESSION['TERMINAL_ID']);

		if($data['payment_type']!=$cash->fk_modepaycash && $data['difpayment'] <= 0)
		{
			if($data['points'] > 0)
				$object->customer_pay = $data['total_with_points'];
			else
				$object->customer_pay = $data['total'];
		}
		else
		{
			//$object->customer_pay = $data['customerpay'];
		}
		$data['customerpay'] = $object->customer_pay;
		//$object->diff_payment = $data['difpayment'];
		$object->id_source = $data['idsource'];
		
		$userstatic=new User($db);
		$userstatic->fetch($employee); 
			//introducir descuentos
			if(!empty($data['idCoupon'])){			
				$res_dis = $object->insert_discount($data['idCoupon']);
				self::addCoupon($aryTicket);
			}
			else{
				$res_dis = 1;
			}
		$db->begin;
		
		$res=$object->update($userstatic->id);
		$data['ref'] = $object->ref;
		if($res<0) 
		{
			$db->rollback();
			return -5;
		}
		else 
		{
			//Adding lines
			$idLines = self::addTicketLines($lines,$idTicket);
			if($idLines<0) 
			{
				$db->rollback();
				return -2;
			}
			else 
			{
				$place = new Place($db);
				$place->fetch($object->fk_place);
				
				if($object->statut!=0 && $object->type !=1)
				{
					//condicion para pago
                    if($data['customerpay'] > 0) 
                    {
                    	if($data['customerpay1'] > 0 || $data['customerpay2'] > 0 || $data['customerpay3'] > 0 || $data['customerpay4'] > 0  || $data['customerpay5'] > 0)
                    	{
                        	$soc = new Societe($db);
                        	$soc->fetch($object->socid);
                        	$payment=self::addPayment($data,$soc);
                        	if($payment < 0)
                        	{
                            	$db->rollback();
                            	return -3;
                        	}
                        }
                        if($object->diff_payment <= 0)
                        {
                            $object->set_paid($user);
                        }
                    }
					/*$payment=self::addPayment($data);
					if($payment < 0)
					{
						$db->rollback();
						return -3;
					}
					else 
					{
						if($object->diff_payment <= 0)
						{
							$object->set_paid($user);
						}
					}*/
					//Decrease stock
					
					// liberar puesto
					$place->free_place();
					
				}
				else 
				{
					// usar puesto
					$place->set_place($idTicket);
					
				}
			}
		}
		
		$db->commit;
		return $idTicket;
		
	}
	
	/**
     *	Delete ticket
     *	@param     	int		$idTicket    Id of ticket to delete
     *	@return		int					<0 if KO, >0 if OK
     */
	public static function DeleteTicket($idTicket=0)
	{
		global $db;
		
		$object= new Ticket($db);
		$db->begin;
		$res=$object->delete($idTicket);
		
		if ($res==1)
		{
			$reslines=DeleteTicketLines($id);
			if($reslines==1)
			{
				$db->commit();		
			}
			else 
			{
				$db->rollback();
				$res=-1;
			}
		}
		else 
		{
			$db->rollback;
		}
		
		return $res;
	}
	
	/**
     * 		Add ticket line into database (linked to product/service or not)
     * 		@param    	array	$lines           	Ticket Lines
     *    	@return    	array             			Result of adding
     */
    private function addTicketLines($lines, $idTicket,$isreturn=false)
    {
    	global $db;
    	
		$res=0;
        $ticket = new Ticket($db);
		$ticket->fetch($idTicket);
		$ticket->fetch_lines();
		$oldLines = $ticket->lines;
		unset($ticket);
		self::deleteTicketLines($idTicket);
		
		$object= new Ticket($db);
		$object->fetch($idTicket);
		
    	if (sizeof ($lines) > 0)
		{
			foreach ( $lines as $line )
			{
				if(sizeof($line)>0)
				{
					if ($line['idProduct']>0)
		    		{
		    			foreach($oldLines as $oldLine)
		    			{
		    				if ($line['idProduct'] == $oldLine->fk_product)
		    				{
		    					if (!isset($line['ls_warehouse_satus']) || is_null($line['ls_warehouse_satus']))
		    					{
		    						$line['ls_warehouse_satus'] = $oldLine->ls_warehouse_satus;
		    					}
		    					if(!isset($line['ls_warehouse_satus_by']) || is_null($line['ls_warehouse_satus_by']))
		    					{
			    					$line['ls_warehouse_satus_by'] = $oldLine->ls_warehouse_satus_by;
		    					}
		    					if(!isset($line['ls_warehouse_satus_date']) || is_null($line['ls_warehouse_satus_date']))
		    					{
			    					$line['ls_warehouse_satus_date'] = $oldLine->ls_warehouse_satus_date;
		    					}
		    					if(!isset($line['ls_stock_dec_date']) || is_null($line['ls_stock_dec_date']))
		    					{
			    					$line['ls_stock_dec_date'] = $oldLine->ls_stock_dec_date;
		    					}
		    					if(!isset($line['ls_stock_dec_qty']) || is_null($line['ls_stock_dec_qty']))
		    					{
			    					$line['ls_stock_dec_qty'] = $oldLine->ls_stock_dec_qty;
		    					}
		    					
		    					break;
		    				}
		    			}
		    			$product_static=new Product($db);
						$product_static->id = $line['idProduct'];
						$product_static->load_stock();
		
						if ($product_static->stock_reel < 1 ||$product_static->stock_reel<$line['cant']) 
						{
							$res=-4;
						}
						if (!strlen($line['qty_ent']))
						{
							$line['qty_ent'] = $line['cant'];
						}
						
						if(!$isreturn)
						{					
							$qty=$line['cant'];
						}
						else 
						{
							$qty=$line['cant']*-1;
						}
						$line['discount'] = $line['discount'];//+ $object->remise_percent;
						$line['description']= $line['description']." ".$line['note'];
						$res=$object->addline(/*$idTicket,*/ $line['description'], $line['price'], $qty, $line['tva_tx'], $line['localtax1_tx'], $line['localtax2_tx'], (int)$line['idProduct'], $line['discount'], $line['note'], $line['fk_product_type'], $line['price_ttc'], $line['price_base_type'],null,null,null,null,null,null,null,null,null,null,$line['ls_warehouse_status'],$line['ls_warehouse_status_by'],$line['ls_warehouse_status_date'],$line['qty_ent'],$line['ls_stock_dec_date'],$line['ls_stock_dec_qty']);
													
		    		}
				}
				else 
				{
					$res = -1;    	
				}
			
			}	
		}
		return $res;
		
    }

    /**
     * 		Add ticket line into database (linked to product/service or not)
     * 		@param    	array	$lines           	Ticket Lines
     *    	@return    	array             			Result of adding
     */
    private function addTicketLinesPropal($lines, $idTicket,$isreturn=false)
    {
        global $db;
        $res=0;
        self::deleteTicketLines($idTicket);
        $object= new Ticket($db);
        $object->fetch($idTicket);
        if (sizeof ($lines) > 0)
        {
            foreach ( $lines as $key => $line )
            {
                if(is_object ($line)>0)
                {
                    if ($line->fk_product>0)
                    {
                        $product_static=new Product($db);
                        $product_static->id = $line->fk_product;
                        $product_static->load_stock();

                        if ($product_static->stock_reel < 1 ||$product_static->stock_reel<$line->qty)
                        {
                            $res=-4;
                        }
                        if(!$isreturn)
                        {
                            $qty=$line->qty;
                        }
                        else
                        {
                            $qty=$line->qty*-1;
                            $line->remise_percent = 0;
                        }
                        $line->remise_percent = $line->remise_percent;
                        $line->description= $line->description." ".$line->note;
                        $res=$object->addline(/*$idTicket,*/ $line->desc, $line->price, $qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, (int)$line->fk_product, $line->remise_percent, $line->note, $line->fk_product_type, $line->subprice*1.16, 'TTC');
                        //$res=$object->addline(/*$idTicket,*/ $line->desc, $line->price, $qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, (int)$line->fk_product, $line->remise_percent, 'TTC', $line->total_ttc);
                    }
                }
                else
                {
                    $res = -1;
                }

            }
        }
        return $res;

    }
    
    /**
     * 		Add ticket line into database (linked to product/service or not)
     * 		@param    	array	$lines           	Ticket Lines
     *    	@return    	array             			Result of adding
     */
    private function addFactureLines($lines, $idTicket,$isreturn=false)
    {
    	global $db;
    	 
    	$res=0;
    
    	$object= new Facture($db);
    	$object->fetch($idTicket);
		$object->brouillon=1;
    
    	if (sizeof ($lines) > 0)
    	{
    		foreach ( $lines as $line )
    		{
    			if(sizeof($line)>0)
    			{
    				if ($line['idProduct']>0)
    				{
    					$product_static=new Product($db);
    					$product_static->id = $line['idProduct'];
    					$product_static->load_stock();
    
    					if ($product_static->stock_reel < 1 ||$product_static->stock_reel<$line['cant'])
    					{
    						$res=-4;
    					}
    
    
    					if(!$isreturn)
    					{
    						$qty=$line['cant'];
    					}
    					else
    					{
    						$qty=$line['cant']*-1;
    					}
    					$object->brouillon=1;
    					$line['discount'] = $line['discount']+ $object->remise_percent;
    					$line['description']= $line['description']." ".$line['note'];
    					// TODO buscar el pmp del producto para este almacén, si es cero, pmp en general.
    					$terminal = $_SESSION['TERMINAL_ID'];
    					$cash = new Cash($db);
    					$cash->fetch($terminal);
    					$warehouse=$cash->fk_warehouse;
    					$sql = "SELECT	p.pmp as totpmp, (select ps.reel FROM ".MAIN_DB_PREFIX."product_stock as ps ";
    					$sql.= " WHERE ps.fk_product = ".$line["idProduct"]." AND ps.fk_entrepot = ".$warehouse.") as warepmp ";
    					$sql.= " FROM ".MAIN_DB_PREFIX."product as p WHERE p.rowid = ".$line["idProduct"];
    					$resql = $db->query ($sql);
    					 
    					if ($resql)
    					{
    						$objp = $db->fetch_object($resql);
    						$pmp= $objp->warepmp;
    						if ($pmp <= 0){
    							$pmp= $objp->totpmp;
    							
    							if($pmp <=0 && $conf->global->ForceBuyingPriceIfNull)
    								$pmp = $line['price'];
    						}	
    					}
    					
    					$res=$object->addline(/*$idTicket,*/ $line['description'], $line['price'], $qty, $line['tva_tx'], $line['localtax1_tx'], $line['localtax2_tx'], $line['idProduct'], $line['discount'], '','',0,0,'', $line['price_base_type'], $line['price_ttc'], $line['fk_product_type'],-1,0,'',0,0,null,$pmp);

    				}
    			}
    			else
    			{
    				$res = -1;
    			}
    				
    		}
    	}
    	return $res;
    
    }
    
     /**
     *	Update a detail line
     *	@param    	array	$line	Line Ticket
     *	@return    	array           Result of update
     */
    public static function updateTicketLine($line)
    {
    	global $db;
		$object= new Ticket($db);
		if(sizeof($line)>0)
			$res=$object->updateline($line->$idTicketLine,$line->desc, $line->pu, $line->qty, $line->remise_percent, '', '', $line->txtva, $line->txlocaltax1, $line->txlocaltax2,$line->price_base_type);
		else
			$res=-1;
		return $res;
    }
    
 	/**
     *	Delete line in database
     *	@param		int		$idTicket 	Id Ticket to delete lines
     *	@return		int						<0 if KO, >0 if OK
     */
	public static function deleteTicketLines($idTicket)
    {
    	global $db, $conf;
    	
    	$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."pos_ticketdet";
		$sql.= " WHERE  fk_ticket= ".$idTicket;

   		$resql = $db->query ($sql);
   		
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$object= new Ticket($db);	
			
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$res=$object->deleteline($objp->rowid);
				if ($res!=1)
				{
					return -1;
				}
				
				$i++;
			}
			
		}
		return 1;
    }
    
    /**
     * 
     * Returns terminals of POS
     */
	public static function select_Terminals()
    {
    	global $db, $conf, $user;
    	
    	$sqlu = 'SELECT
					u.rowid,
					SUBSTRING_INDEX(ug.nom," ",-1) as gpo
				FROM
					llx_user AS u
				JOIN llx_usergroup_user AS ugu ON ugu.fk_user = u.rowid
				JOIN llx_usergroup AS ug ON ug.rowid = ugu.fk_usergroup ';
		$sqlu .= ' WHERE u.rowid = '.$user->id;
		$resu = $db->query($sqlu);
		if ($resu) {
			$object = $db->fetch_object($resu);
			if($object)
				$where = '';// ' AND name like "%'.$object->gpo.'%"';
		}
		$where .= '';
    	$sql = "SELECT rowid, code, name, fk_device, is_used, fk_user_u, tactil";
		$sql.= " FROM ".MAIN_DB_PREFIX."pos_cash";
		$sql.= " WHERE entity = ".$conf->entity.$where;
		//$sql.= " AND is_used = 0 OR (is_used=1 AND is_closed=1)";
		if (!$user->admin)
		{
			$sql .= '  AND fk_warehouse = \''.$user->fk_warehouse.'\' ';
		}
   		$res = $db->query ($sql);
   		
		if ($res)
		{
			$terms = array ();
			$i=0;
			while ($record = $db->fetch_array ($res))
			{
				foreach ( $record as $cle => $valeur )
				{
					$terms[$i][$cle] = $valeur;
				}
				$i++;
			}
			return $terms;
		}
		else
		{
			return -1;
		}
    }
    /**
     * Abandonar una venta
     */
    public static function AbandonatedTicket($data)
    {
        global $db,$user,$conf;
        $sql =	 'SELECT `sm`.`fk_product` AS `fk_product`,SUM(`sm`.`value`) AS `pending`'."\r\n"
				.'FROM `llx_stock_mouvement` AS `sm`'."\r\n"
				.'WHERE `sm`.`origintype`=\'ticket\''."\r\n"
				.'  AND `sm`.`fk_origin`=\''.intval($data['id']).'\''."\r\n"
				.'GROUP BY `fk_product`'."\r\n"
				;
		if (!$res = $db->query($sql))
		{
			return -5;
		}
		$pend = array();
		while ($row = $db->fetch_object($res))
		{
			if ($row->pending != 0)
			{
				$pend[$row->fk_product]=$row->pending;
			}
		}
        if (count($pend))
        {
        	return -99;
        }
        
        $sql =	 'SELECT SUM(`sm`.`amount`) AS `pending`'."\r\n"
				.'FROM `llx_pos_paiement_ticket` AS `sm`'."\r\n"
				.'WHERE `sm`.`fk_ticket` = \''.intval($data['id']).'\''."\r\n"
				;
        
		if (!$res = $db->query($sql))
		{
			return -5;
		}
		$pend = array();
		while ($row = $db->fetch_object($res))
		{
			if ($row->pending != 0)
			{
				$pend[$row->fk_product]=$row->pending;
			}
		}
        if (count($pend) && empty($user->rights->pos->cancelpaidticket))
        {
        	return -100;
        }
        
        //Se crea un ticket con el id
        $object = new Ticket($db);
        $object->fetch($data['id']);
        //Cancelamos el ticket
        $result=$object->set_canceled();
        if($result<0)
        {
            $db->rollback();
            return -5;
        }
        $db->commit;
        return $result;
    }
	/**
	 * 
	 * Return Ticket history
	 * 
	 * @param 	string	$ticketnumber	ticket number for filter
	 * @param 	int		$stat			status of ticket
	 * @param  int     $mode			0, count rows; 1, get rows
	 * @param 	string	$terminal		terminal for filter
	 * @param 	string	$seller			seller user for filter
	 * @param 	string	$client			client for filter
	 * @param 	float	$amount			amount for filter
	 * @param 	int		$month			month for filter
	 * @param 	int		$year			year for filter
	 */
	public static function getHistoric($ticketnumber='',$stat,$terminal='',$seller='',$client='',$amount='',$months=0,$years=0)
	{
		global $db, $conf,$user;
		
		$ret=-1;
		$function="GetHistoric";
		
		$sql= ' SELECT ';
		
		$sql.= ' f.rowid as ticketid, f.ticketnumber, f.total_ttc,';
		$sql.= ' f.date_closed, f.fk_user_author AS fk_user_close, f.date_creation as datec,';
		$sql.= ' f.fk_statut, f.customer_pay, f.difpayment, f.fk_place, f.note_public,';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' u.firstname, u.lastname,';
		$sql.= ' t.name, f.fk_cash, f.type';
		$sql.= ' ,(SELECT COUNT(lptd.rowid) FROM '.MAIN_DB_PREFIX.'pos_ticketdet as lptd LEFT JOIN '.MAIN_DB_PREFIX.'product as prod on lptd.fk_product=prod.rowid WHERE lptd.fk_ticket=f.rowid and lptd.ls_warehouse_status=4 AND lptd.qty<=prod.stock) as total_of_surtir';
		$sql.= ' ,COUNT(ls_stock_mv_id) AS hasAutoChangesByStock';

		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_ticket as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_cash as t';
		$sql.= ', '.MAIN_DB_PREFIX.'user as u';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_ticketdet as td';
		$sql.= ' WHERE f.fk_soc = s.rowid';
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.fk_cash = t.rowid";
		$sql.= " AND td.fk_ticket = f.rowid";
		$sql.= " AND u.rowid = f.fk_user_author";
		
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
		if($stat >= 0 && $stat !=4 && $stat <= 99){
			$sql.= " AND f.fk_statut = ".$stat;
			$sql.= " AND f.type = 0";
		}
		if($stat == 4){
			$sql.= " AND f.type = 1";
		}
		if ($socid) $sql.= ' AND s.rowid = '.$socid;
		
		if ($ticketnumber)
		{
			$sql.= ' AND f.ticketnumber LIKE \'%'.$db->escape(trim($ticketnumber)).'%\'';
		}
		if ($months > 0)
		{
			if ($years > 0)
				$sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($years,$months,false))."' AND '".$db->idate(dol_get_last_day($years,$months,false))."'";
			else
				$sql.= " AND date_format(f.date_ticket, '%m') = '".$months."'";
		}
		else if ($years > 0)
		{
			$sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($years,1,false))."' AND '".$db->idate(dol_get_last_day($years,12,false))."'";
		}
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];
		
		if($stat == 100)	{//Today
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 101)	{//Yesterday
			$time = dol_get_prev_day($day, $month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 102)	{//This week
			$time = dol_get_first_day_week($day,$month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['first_day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 103)	{//Last week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['prev_year'],$time['prev_month'],$time['prev_day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$time['first_day']-1==0?$time['prev_month']:$month,$time['first_day']-1==0?$time['prev_day']+6:$time['first_day']-1,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 104)	{//Two weeks ago
			$time = dol_get_prev_week($day,'', $month, $year);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 105)	{//Three weeks ago
			$time = dol_get_prev_week($day,'', $month, $year);
			$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 106)	{//This month
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,01,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 107)	{//One month ago
			$time = dol_get_prev_month($month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$day,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 108)	{//Last month
			$time = dol_get_prev_month($month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],01,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],31,0,0,0);
			$sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
        if($stat == 1000){
            $sql.=	 " AND "."\r\n"
					."	( "."\r\n"
					."		SELECT COUNT(lptd.rowid)  "."\r\n"
					."		FROM ".MAIN_DB_PREFIX."pos_ticketdet as lptd  "."\r\n"
					."		LEFT JOIN ".MAIN_DB_PREFIX."product as prod  "."\r\n"
					."		  on lptd.fk_product=prod.rowid  "."\r\n"
					."		WHERE lptd.fk_ticket=f.rowid  "."\r\n"
					."		  and lptd.ls_warehouse_status=4  "."\r\n"
					//."		  AND lptd.qty<=prod.stock "."\r\n"
					."	) > 0";
        }
		if ($terminal)
		{
			$sql.= ' AND t.name LIKE \'%'.$db->escape(trim($terminal)).'%\'';
		}
		if ($seller)
		{
			$sql.= ' AND (u.firstname LIKE \'%'.$db->escape(trim($seller)).'%\'';
			$sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($seller)).'%\')';
		}
		if ($client)
		{
			$prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$sql.= ' AND ( s.nom LIKE \''.$prefix.$db->escape(trim($client)).'%\' '
					.'OR f.note_public LIKE \''.$prefix.$db->escape(trim($client)).'%\' ) ';
		}
		
		if ($amount)
		{
			$sql.= ' AND f.total_ttc = \''.$db->escape(trim($amount)).'\'';
		}
				
		$sql.= ' GROUP BY f.rowid';
		if($stat == 110)	{// Con cambios en Estado V por movimientos de stock
			$sql.= " HAVING hasAutoChangesByStock > 0 ";
		}
		
		
		$sql.= ' ORDER BY ';
		$sql.= ' datec DESC ';
		$sql.= 'LIMIT 0,50';
		$res = $db->query($sql);
   		
		if ($res)
		{
			$num = $db->num_rows($res);
			$i = 0;
			$ticketstatic=new Ticket($db);	
			while ($i < $num)
			{
				$obj = $db->fetch_object($res);
                $tickets[$i]["id"] = $obj->ticketid;
                $tickets[$i]["type"] = $obj->type;
                $tickets[$i]["ticketnumber"] = $obj->ticketnumber;
                $tickets[$i]["date_creation"] = dol_print_date($db->jdate($obj->datec), 'dayhour');
                $tickets[$i]["date_close"] = dol_print_date($db->jdate($obj->date_closed), 'dayhour');
                $tickets[$i]["fk_place"] = $obj->fk_place;
                $tickets[$i]["total_surtir"] = $obj->total_of_surtir;
                $tickets[$i]["diffpayment"] = $obj->difpayment;

                $cash = new Cash($db);
                $cash->fetch($obj->fk_cash);
                $tickets[$i]["terminal"] = $cash->name;

                $userstatic = new User($db);
                $userstatic->fetch($obj->fk_user_close);
                $tickets[$i]["seller"] = $userstatic->getFullName($langs);
				if(strlen($obj->note_public))
				{
					$tickets[$i]["client"] = $obj->nom.' ('.$obj->note_public.')' ;
				}
				else
				{
					$tickets[$i]["client"] = $obj->nom;
				}
                
                $tickets[$i]["amount"] = $obj->total_ttc;
                $tickets[$i]["customer_pay"] = $obj->customer_pay;
                $tickets[$i]["statut"] = $obj->fk_statut;
                $tickets[$i]["statutlabel"] = $ticketstatic->LibStatut($obj->fk_statut, 0);
                $tickets[$i]["hasAutoChangesByStock"] = $obj->hasAutoChangesByStock;
                

                $i++;
			}
			return ErrorControl($tickets,$function);
			
				
		}
		else
		{
			return ErrorControl($ret, $function);
		}
		
	}
    public static function getHistoricUser($seller='',$stat)
    {
        global $db, $conf;

        $terminals = [];

        $sql= ' SELECT ';
        $sql.= ' t.rowid, t.name';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'pos_cash as t ';

        $res = $db->query($sql);

        if ($res)
        {
            while ($row = $db->fetch_object($res))
            {
                $terminals[$row->rowid] = $row->name;
            }
        }

        /////

        $users = [];
        $users_in = '';

        $sql= ' SELECT ';
        $sql.= ' u.rowid, u.firstname, u.lastname ';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'user as u ';

        if ($seller)
        {
            $sql.= ' WHERE (u.firstname LIKE \'%'.$db->escape(trim($seller)).'%\'';
            $sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($seller)).'%\')';
        }

        $res = $db->query($sql);

        if ($res)
        {
            while ($row = $db->fetch_object($res))
            {
                $users[$row->rowid] = $row->firstname.' '.$row->lastname;
                $users_in .= $row->rowid.',';
            }
        }

        $users_in = rtrim($users_in, ',');

        $ret=-1;
        $function="GetHistoric";

        $sql= ' SELECT ';

        $sql.= ' f.rowid as ticketid, f.ticketnumber, f.total_ttc,';
        $sql.= ' f.date_closed, f.fk_user_close, f.date_creation as datec,';
        $sql.= ' f.fk_statut, f.customer_pay, f.difpayment, f.fk_place, ';
        $sql.= ' s.nom, s.rowid as socid,';
        $sql.= ' f.fk_cash, f.type';

        $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        $sql.= ', '.MAIN_DB_PREFIX.'pos_ticket as f';
        $sql.= ' WHERE f.fk_soc = s.rowid';
        $sql.= " AND f.entity = ".$conf->entity;
        if($stat >= 0 && $stat !=4 && $stat <= 99){
            $sql.= " AND f.fk_statut = ".$stat;
            $sql.= " AND f.type = 0";
        }
        if($stat == 4){
            $sql.= " AND f.type = 1";
        }

        if ($months > 0)
        {
            if ($years > 0)
                $sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($years,$months,false))."' AND '".$db->idate(dol_get_last_day($years,$months,false))."'";
            else
                $sql.= " AND date_format(f.date_ticket, '%m') = '".$months."'";
        }
        else if ($years > 0)
        {
            $sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($years,1,false))."' AND '".$db->idate(dol_get_last_day($years,12,false))."'";
        }
        $now = dol_now();
        $time = dol_getdate($now);
        $day = $time['mday'];
        $month = $time['mon'];
        $year = $time['year'];

        if($stat == 100)	{//Today
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 101)	{//Yesterday
            $time = dol_get_prev_day($day, $month, $year);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 102)	{//This week
            $time = dol_get_first_day_week($day,$month, $year);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['first_day'],0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 103)	{//Last week
            $time = dol_get_first_day_week($day, $month, $year);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['prev_year'],$time['prev_month'],$time['prev_day'],0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$time['first_day']-1==0?$time['prev_month']:$month,$time['first_day']-1==0?$time['prev_day']+6:$time['first_day']-1,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 104)	{//Two weeks ago
            $time = dol_get_prev_week($day,'', $month, $year);
            $time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 105)	{//Three weeks ago
            $time = dol_get_prev_week($day,'', $month, $year);
            $time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
            $time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 106)	{//This month
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,01,0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 107)	{//One month ago
            $time = dol_get_prev_month($month, $year);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$day,0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if($stat == 108)	{//Last month
            $time = dol_get_prev_month($month, $year);
            $ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],01,0,0,0);
            $fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],31,0,0,0);
            $sql.= " AND f.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
        }
        if ($seller && count($users_in) > 0)
        {
            $sql.= ' AND f.fk_user_close IN ('.$users_in.')';
        }

        $sql.= ' GROUP BY f.rowid';

        $sql.= ' ORDER BY ';
        $sql.= ' datec DESC ';
        $sql.= 'LIMIT 0,50';

        $res = $db->query($sql);

        if ($res)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            $ticketstatic=new Ticket($db);
            while ($i < $num)
            {
                $obj = $db->fetch_object($res);

                $tickets[$i]["id"] = $obj->ticketid;
                $tickets[$i]["type"] = $obj->type;
                $tickets[$i]["ticketnumber"] = $obj->ticketnumber;
                $tickets[$i]["date_creation"] = dol_print_date($db->jdate($obj->datec),'dayhour');
                $tickets[$i]["date_close"] = dol_print_date($db->jdate($obj->date_closed),'dayhour');
                $tickets[$i]["fk_place"] = $obj->fk_place;

                // $cash=new Cash($db);
                // $cash->fetch($obj->fk_cash);
                $tickets[$i]["terminal"] = $terminals[$obj->fk_cash];

                // $userstatic=new User($db);
                // $userstatic->fetch($obj->fk_user_close);
                $tickets[$i]["seller"] = $users[$obj->fk_user_close] ? $users[$obj->fk_user_close] : '';

                $tickets[$i]["client"] = $obj->nom;
                $tickets[$i]["amount"] = $obj->total_ttc;
                $tickets[$i]["customer_pay"] = $obj->customer_pay;
                $tickets[$i]["statut"] = $obj->fk_statut;
                $tickets[$i]["statutlabel"] = $ticketstatic->LibStatut($obj->fk_statut,0);

                $i++;
            }
            return ErrorControl($tickets,$function);


        }
        else
        {
            return ErrorControl($ret, $function);
        }

    }
	/**
	 *
	 * Return Facture history
	 *
	 * @param 	string	$ticketnumber	ticket number for filter
	 * @param 	int		$stat			status of ticket
	 * @param  int     $mode			0, count rows; 1, get rows
	 * @param 	string	$terminal		terminal for filter
	 * @param 	string	$seller			seller user for filter
	 * @param 	string	$client			client for filter
	 * @param 	float	$amount			amount for filter
	 * @param 	int		$month			month for filter
	 * @param 	int		$year			year for filter
	 */
	public static function getHistoricFac($ticketnumber='',$stat, $terminal='',$seller='',$client='',$amount='',$months=0,$years=0)
	{
		global $db, $conf;
	
		$ret=-1;
		$function="GetHistoric";
	
		$sql= ' SELECT ';
	
		$sql.= ' f.rowid as ticketid, f.ref, f.total_ttc,';
		$sql.= ' f.fk_user_valid, f.datec as datec,';
		$sql.= ' f.fk_statut, pf.fk_place, ';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' u.firstname, u.lastname,';
		$sql.= ' t.name, pf.fk_cash, f.type';
	
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_cash as t';
		$sql.= ', '.MAIN_DB_PREFIX.'user as u';
		$sql.= ' WHERE f.fk_soc = s.rowid';
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND pf.fk_cash = t.rowid";
		$sql.= " AND pf.fk_facture = f.rowid";
		$sql.= " AND u.rowid = f.fk_user_author";
		if($stat >= 0 && $stat !=4 && $stat <= 99){
			$sql.= " AND f.fk_statut = ".$stat;
			$sql.= " AND f.type = 0";
		}
		if($stat == 4){
			$sql.= " AND f.type = 2";
		}
			
		if ($socid) $sql.= ' AND s.rowid = '.$socid;
	
		if ($ticketnumber)
		{
			$sql.= ' AND f.ref LIKE \'%'.$db->escape(trim($ticketnumber)).'%\'';
		}
		if ($months > 0)
		{
			if ($years > 0)
				$sql.= " AND f.datec BETWEEN '".$db->idate(dol_get_first_day($years,$months,false))."' AND '".$db->idate(dol_get_last_day($years,$months,false))."'";
			else
				$sql.= " AND date_format(f.datec, '%m') = '".$months."'";
		}
		else if ($years > 0)
		{
			$sql.= " AND f.datec BETWEEN '".$db->idate(dol_get_first_day($years,1,false))."' AND '".$db->idate(dol_get_last_day($years,12,false))."'";
		}
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];
	
		if($stat == 100)	{//Today
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 101)	{//Yesterday
			$time = dol_get_prev_day($day, $month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 102)	{//This week
			$time = dol_get_first_day_week($day,$month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['first_day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 103)	{//Last week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['prev_year'],$time['prev_month'],$time['prev_day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$time['first_day']-1==0?$time['prev_month']:$month,$time['first_day']-1==0?$time['prev_day']+6:$time['first_day']-1,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 104)	{//Two weeks ago
			$time = dol_get_prev_week($day,'', $month, $year);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 105)	{//Three weeks ago
			$time = dol_get_prev_week($day,'', $month, $year);
			$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 106)	{//This month
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,01,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 107)	{//One month ago
			$time = dol_get_prev_month($month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$day,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 108)	{//Last month
			$time = dol_get_prev_month($month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],01,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],31,0,0,0);
			$sql.= " AND f.datec BETWEEN '".$ini."' AND '".$fin."'";
		}
		if ($terminal)
		{
			$sql.= ' AND t.name LIKE \'%'.$db->escape(trim($terminal)).'%\'';
		}
		if ($seller)
		{
			$sql.= ' AND (u.firstname LIKE \'%'.$db->escape(trim($seller)).'%\'';
			$sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($seller)).'%\')';
		}
		if ($client)
		{
			$prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$sql.= ' AND s.nom LIKE \''.$prefix.$db->escape(trim($client)).'%\'';
		}
	
		if ($amount)
		{
			$sql.= ' AND f.total_ttc = \''.$db->escape(trim($amount)).'\'';
		}
	
		$sql.= ' GROUP BY f.rowid';
		
		$sql.= ' UNION SELECT ';
		
		$sql.= ' p.rowid as ticketid, p.ticketnumber, p.total_ttc,';
		$sql.= ' p.fk_user_close, p.date_creation as datec,';
		$sql.= ' p.fk_statut, p.fk_place, ';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' u.firstname, u.lastname,';
		$sql.= ' t.name, p.fk_cash, p.type';
		
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_ticket as p';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_cash as t';
		$sql.= ', '.MAIN_DB_PREFIX.'user as u';
		$sql.= ' WHERE p.fk_soc = s.rowid';
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND p.fk_cash = t.rowid";
		$sql.= " AND p.fk_statut = 0";
		$sql.= " AND u.rowid = p.fk_user_author";
		if($stat >= 0 && $stat !=4 && $stat <= 99){
			$sql.= " AND p.fk_statut = ".$stat;
		}
		if($stat == 4){
			$sql.= " AND p.type = 1";
		}
			
		if ($socid) $sql.= ' AND s.rowid = '.$socid;
		
		if ($ticketnumber)
		{
			$sql.= ' AND p.ticketnumber LIKE \'%'.$db->escape(trim($ticketnumber)).'%\'';
		}
		if ($months > 0)
		{
			if ($years > 0)
				$sql.= " AND p.date_ticket BETWEEN '".$db->idate(dol_get_first_day($years,$months,false))."' AND '".$db->idate(dol_get_last_day($years,$months,false))."'";
			else
				$sql.= " AND date_format(p.date_ticket, '%m') = '".$months."'";
		}
		else if ($years > 0)
		{
			$sql.= " AND p.date_ticket BETWEEN '".$db->idate(dol_get_first_day($years,1,false))."' AND '".$db->idate(dol_get_last_day($years,12,false))."'";
		}
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];
		
		if($stat == 100)	{//Today
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 101)	{//Yesterday
			$time = dol_get_prev_day($day, $month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 102)	{//This week
			$time = dol_get_first_day_week($day,$month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['first_day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 103)	{//Last week
			$time = dol_get_first_day_week($day, $month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['prev_year'],$time['prev_month'],$time['prev_day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$time['first_day']-1==0?$time['prev_month']:$month,$time['first_day']-1==0?$time['prev_day']+6:$time['first_day']-1,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 104)	{//Two weeks ago
			$time = dol_get_prev_week($day,'', $month, $year);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 105)	{//Three weeks ago
			$time = dol_get_prev_week($day,'', $month, $year);
			$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 106)	{//This month
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,01,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 107)	{//One month ago
			$time = dol_get_prev_month($month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$day,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if($stat == 108)	{//Last month
			$time = dol_get_prev_month($month, $year);
			$ini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],01,0,0,0);
			$fin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],31,0,0,0);
			$sql.= " AND p.date_ticket BETWEEN '".$ini."' AND '".$fin."'";
		}
		if ($terminal)
		{
			$sql.= ' AND t.name LIKE \'%'.$db->escape(trim($terminal)).'%\'';
		}
		if ($seller)
		{
			$sql.= ' AND (u.firstname LIKE \'%'.$db->escape(trim($seller)).'%\'';
			$sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($seller)).'%\')';
		}
		if ($client)
		{
			$prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$sql.= ' AND s.nom LIKE \''.$prefix.$db->escape(trim($client)).'%\'';
		}
		
		if ($amount)
		{
			$sql.= ' AND p.total_ttc = \''.$db->escape(trim($amount)).'\'';
		}
		
		$sql.= ' GROUP BY p.rowid';
	
		$sql.= ' ORDER BY ';
		$sql.= ' datec DESC ';
		$sql.= 'LIMIT 0,50';

		$res = $db->query($sql);
		 
		if ($res)
		{
			$num = $db->num_rows($res);
			$i = 0;
			$ticketstatic=new Ticket($db);
			while ($i < $num)
			{
				$obj = $db->fetch_object($res);
	
				$tickets[$i]["id"] = $obj->ticketid;
				$tickets[$i]["type"] = ($obj->type==2?1:$obj->type);
				$tickets[$i]["ticketnumber"] = $obj->ref;
				$tickets[$i]["date_creation"] = dol_print_date($db->jdate($obj->datec),'dayhour');
				$tickets[$i]["date_close"] = dol_print_date($db->jdate($obj->date_closed),'dayhour');
				$tickets[$i]["fk_place"] = $obj->fk_place;
	
				$cash=new Cash($db);
				$cash->fetch($obj->fk_cash);
				$tickets[$i]["terminal"] = $cash->name;
	
				$userstatic=new User($db);
				$userstatic->fetch($obj->fk_user_valid);
				$tickets[$i]["seller"]=$userstatic->getFullName($langs);
	
				$tickets[$i]["client"] = $obj->nom;
				$tickets[$i]["amount"] = $obj->total_ttc;
				$tickets[$i]["customer_pay"] = $obj->customer_pay;
				$tickets[$i]["statut"] = $obj->fk_statut;
				$tickets[$i]["statutlabel"] = $ticketstatic->LibStatut($obj->fk_statut,0);
	
				$i++;
			}
			return ErrorControl($tickets,$function);
				
	
		}
		else
		{
			return ErrorControl($ret, $function);
		}
	
	}
	
	/**
	 *
	 * Count Ticket history
	 *
	 */
	public static function countHistoric()
	{
		global $db, $conf,$user;
	
		$ret=-1;
		$function="GetHistoric";
	
		$sql = 'SELECT (SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
				
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];
	
		//Today
		$todayini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,0,0,0);
		$todayfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$todayini."' AND '".$todayfin."' ) as today, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
				
		//Yesterday
		$time = dol_get_prev_day($day, $month, $year);
		$yestini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],0,0,0);
		$yestfin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$yestini."' AND '".$yestfin."' ) as yesterday, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
		
		
		//This week
		$time = dol_get_first_day_week($day,$month, $year);
		$weekini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['first_day'],0,0,0);
		$weekfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$weekini."' AND '".$weekfin."' ) as thisweek, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
		
		
		//Last week
		$time = dol_get_first_day_week($day, $month, $year);
		$lweekini= sprintf("%04d%02d%02d%02d%02d%02d",$time['prev_year'],$time['prev_month'],$time['prev_day'],0,0,0);
		$lweekfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$time['first_day']-1==0?$time['prev_month']:$month,$time['first_day']-1==0?$time['prev_day']+6:$time['first_day']-1,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$lweekini."' AND '".$lweekfin."' ) as lastweek, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
		
		
		//Two weeks ago
		$time = dol_get_prev_week($day,'', $month, $year);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini2week= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
		$fin2week= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$ini2week."' AND '".$fin2week."' ) as twoweek, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
		
		
		//Three weeks ago
		$time = dol_get_prev_week($day,'', $month, $year);
		$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini3week= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
		$fin3week= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$ini3week."' AND '".$fin3week."' ) as threeweek, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
		
		
		//This month
		$monthini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,01,0,0,0);
		$monthfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$monthini."' AND '".$monthfin."' ) as thismonth, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
				
		
		//One month ago
		$time = dol_get_prev_month($month, $year);
		$monthagoini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$day,0,0,0);
		$monthagofin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.date_ticket BETWEEN '".$monthagoini."' AND '".$monthagofin."' ) as monthago, ";
		
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f ';
		$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'pos_cash as t ';
		$sql.= '  ON `t`.`rowid`=`f`.`fk_cash` ';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		if (
				!$user->admin 
			&& 	empty($user->rights->pos->operate_tickets_branch) 
			&&	empty($user->rights->pos->operate_tickets_co)
			)
		{
			$sql.= " AND t.rowid = {$_SESSION['TERMINAL_ID']}";
		}
		
		if (!$user->admin && empty($user->rights->pos->operate_tickets_co))
		{
			$sql.= " AND t.fk_warehouse = {$user->fk_warehouse}";
		}
		
				
		//Last month
		$time = dol_get_prev_month($month, $year);
		$lmonthini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],01,0,0,0);
		$lmonthfin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],31,0,0,0);
		$sql.= " AND f.date_ticket BETWEEN '".$lmonthini."' AND '".$lmonthfin."' ) as lastmonth";
		

		
		$res = $db->query($sql);
		 
		if ($res)
		{
			$obj = $db->fetch_object($res);
	
			$result["today"] = $obj->today;
			$result["yesterday"] = $obj->yesterday;
			$result["thisweek"] = $obj->thisweek;
			$result["lastweek"] = $obj->lastweek;
			$result["twoweek"] = $obj->twoweek;
			$result["threeweek"] = $obj->threeweek;
			$result["thismonth"] = $obj->thismonth;
			$result["monthago"] = $obj->monthago;
			$result["lastmonth"] = $obj->lastmonth;
				
			return ErrorControl($result,$function);
		}
		else
		{
			dol_print_error($db);
			return ErrorControl($ret, $function);
		}
	
	}
	
	/**
	 *
	 * Count Facture history
	 *
	 */
	public static function countHistoricFac()
	{
		global $db, $conf;
	
		$ret=-1;
		$function="GetHistoric";
	
		$sql = 'SELECT (SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		$now = dol_now();
		$time = dol_getdate($now);
		$day = $time['mday'];
		$month = $time['mon'];
		$year = $time['year'];
	
		//Today
		$todayini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,0,0,0);
		$todayfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$todayini."' AND '".$todayfin."' ) as today, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		//Yesterday
		$time = dol_get_prev_day($day, $month, $year);
		$yestini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],0,0,0);
		$yestfin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['day'],23,59,59);
		$sql.= " AND f.datec BETWEEN '".$yestini."' AND '".$yestfin."' ) as yesterday, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
	
		//This week
		$time = dol_get_first_day_week($day,$month, $year);
		$weekini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$time['first_day'],0,0,0);
		$weekfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$weekini."' AND '".$weekfin."' ) as thisweek, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		//Last week
		$time = dol_get_first_day_week($day, $month, $year);
		$lweekini= sprintf("%04d%02d%02d%02d%02d%02d",$time['prev_year'],$time['prev_month'],$time['prev_day'],0,0,0);
		$lweekfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$time['first_day']-1==0?$time['prev_month']:$month,$time['first_day']-1==0?$time['prev_day']+6:$time['first_day']-1,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$lweekini."' AND '".$lweekfin."' ) as lastweek, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		//Two weeks ago
		$time = dol_get_prev_week($day,'', $month, $year);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini2week= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
		$fin2week= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$ini2week."' AND '".$fin2week."' ) as twoweek, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		//Three weeks ago
		$time = dol_get_prev_week($day,'', $month, $year);
		$time = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$time2 = dol_get_prev_week($time['day'], '', $time['month'], $time['year']);
		$ini3week= sprintf("%04d%02d%02d%02d%02d%02d",$time2['year'],$time2['month'],$time2['day'],0,0,0);
		$fin3week= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['day']-1==0?$time2['month']:$time['month'],$time['day']-1==0?$time2['day']+6:$time['day']-1,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$ini3week."' AND '".$fin3week."' ) as threeweek, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		//This month
		$monthini= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,01,0,0,0);
		$monthfin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$monthini."' AND '".$monthfin."' ) as thismonth, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
	
		//One month ago
		$time = dol_get_prev_month($month, $year);
		$monthagoini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],$day,0,0,0);
		$monthagofin= sprintf("%04d%02d%02d%02d%02d%02d",$year,$month,$day,23,59,59);
		$sql.= " AND f.datec BETWEEN '".$monthagoini."' AND '".$monthagofin."' ) as monthago, ";
	
		$sql.= '(SELECT COUNT(f.rowid)';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_facture as pf';
		$sql.= ' WHERE f.entity = '.$conf->entity;
		$sql.= ' AND pf.fk_facture = f.rowid';
	
		//Last month
		$time = dol_get_prev_month($month, $year);
		$lmonthini= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],01,0,0,0);
		$lmonthfin= sprintf("%04d%02d%02d%02d%02d%02d",$time['year'],$time['month'],31,0,0,0);
		$sql.= " AND f.datec BETWEEN '".$lmonthini."' AND '".$lmonthfin."' ) as lastmonth";
	
		$res = $db->query($sql);
			
		if ($res)
		{
			$obj = $db->fetch_object($res);
	
			$result["today"] = $obj->today;
			$result["yesterday"] = $obj->yesterday;
			$result["thisweek"] = $obj->thisweek;
			$result["lastweek"] = $obj->lastweek;
			$result["twoweek"] = $obj->twoweek;
			$result["threeweek"] = $obj->threeweek;
			$result["thismonth"] = $obj->thismonth;
			$result["monthago"] = $obj->monthago;
			$result["lastmonth"] = $obj->lastmonth;
	
			return ErrorControl($result,$function);
		}
		else
		{
			return ErrorControl($ret, $function);
		}
	
	}
	
	/**
	 * 
	 * Add ticket Payment
	 * 
	 * @param array $aryTicket	Ticket data array
	 */
	private function addPayment($aryTicket,$soc=null,$debug=null)
	{
		
		global $db, $langs;
		
		$fixSql =	 "SELECT t.rowid, t.ticketnumber, t.total_ttc,t.paye,t.difpayment, SUM(pt.amount) AS payments "."\r\n"
					."FROM llx_pos_ticket AS t "."\r\n"
					."LEFT JOIN llx_pos_paiement_ticket AS pt "."\r\n"
					."  ON t.rowid = pt.fk_ticket "."\r\n"
					."LEFT JOIN llx_paiement AS p "."\r\n"
					."  ON p.rowid = pt.fk_paiement "."\r\n"
					."WHERE (t.paye = 0 "."\r\n"
					."   OR t.difpayment > 0) "."\r\n"
					." AND p.rowid IS NOT NULL "."\r\n"
					."GROUP BY t.rowid "."\r\n"
					."HAVING (total_ttc - payments) <= 0.01 "."\r\n"
					.""
					;
		if (!$fixRes = $db->query($fixSql))
		{
			//dol_print_error($db);
		}
		else
		{
			while($fixRow = $db->fetch_object($fixRes))
			{
				$stillToPay = $fixRow->total_ttc - $fixRow->payments;
				if ($stillToPay >= -0.011 && $stillToPay <= 0.011)
				{
					$stillToPay = 0;
				}
				$fix2Sql =	 "UPDATE llx_pos_ticket "."\r\n"
							."SET paye = 1 "."\r\n"
							."	 ,difpayment = ".$stillToPay." "."\r\n"
							."WHERE rowid = ".$fixRow->rowid." "."\r\n"
							.""
							;
				if (!$db->query($fix2Sql))
				{
					//dol_print_error($db);
				}
			}
		}
		
		
		
		$paiement_id = 0;
		require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
		$now=dol_now();
		$userstatic=new User($db);
		if(! $aryTicket['employeeId'])
		{
			$employee=$_SESSION['uid'];
		
		}
		else
		{
			$employee=$aryTicket['employeeId'];
		}
		$userstatic->fetch($employee);
		
		if($aryTicket['type']==1)
		{
			$aryTicket['total']=$aryTicket['total']*-1;
			$aryTicket['customerpay1']=$aryTicket['customerpay1']*-1;
			$aryTicket['customerpay2']=$aryTicket['customerpay2']*-1;
			$aryTicket['customerpay3']=$aryTicket['customerpay3']*-1;
			$aryTicket['customerpay4']=$aryTicket['customerpay4']*-1;
			$aryTicket['customerpay5']=$aryTicket['customerpay5']*-1;
		}
		
		$cash = new Cash($db);
			
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
			
		if ($aryTicket['customerpay1'] > 0)
		{
			$bankaccountid[1] = $cash->fk_paycash;
			$modepay[1] =  $cash->fk_modepaycash;
			$amount[1] = $aryTicket['customerpay1'] + ($aryTicket['difpayment']<0?$aryTicket['difpayment']:0);
		}
		elseif ($aryTicket['customerpay1'] < 0)
		{
			$bankaccountid[1] = $cash->fk_paycash;
			$modepay[1] =  $cash->fk_modepaycash;
			$amount[1] = $aryTicket['customerpay1'] + ($aryTicket['difpayment']<0?$aryTicket['difpayment']:0);
			//$amount[1] = $aryTicket['customerpay1'];
		}
		if($aryTicket['customerpay2'] != 0)
		{
			$bankaccountid[2] = $cash->fk_paybank ;
			$modepay[2] =  $cash->fk_modepaybank;
			$amount[2] = $aryTicket['customerpay2'];
		}
		if($aryTicket['customerpay3'] != 0)
		{
			$bankaccountid[3] = $cash->fk_paybank_extra ;
			$modepay[3] =  $cash->fk_modepaybank_extra;
			$amount[3] = $aryTicket['customerpay3'];
		}
		if($aryTicket['customerpay4'] != 0)
		{
			$bankaccountid[4] = $cash->fk_paybank_extra_2 ;
			$modepay[4] =  $cash->fk_modepaybank_extra_2;
			$amount[4] = $aryTicket['customerpay4'];
		}
		if($aryTicket['customerpay5'] != 0)
		{
			$bankaccountid[5] = $cash->fk_paybank_extra_3 ;
			$modepay[5] =  $cash->fk_modepaybank_extra_3;
			$amount[5] = $aryTicket['customerpay5'];
		}
		
		$i=1;
		
		$payment=new Payment($db);
		$pTicket = new Ticket($db);
		$pTicket->fetch($aryTicket['id']);
		$maxPay = 0;
		$pyMod = null;
		
		while($i <= 5){
			if ($amount[$i] > $maxPay)
			{
				$maxPay = $amount[$i];
				$pyMod = $modepay[$i];
			}
			$payment->datepaye=$now;
			$payment->bank_account=$bankaccountid[$i];
			$payment->amounts[$aryTicket['id']]=$amount[$i];
			$payment->note=$langs->trans("Payment").' '.$langs->trans("Ticket").' '.$aryTicket['ref'] ;
			$payment->paiementid=$modepay[$i];
			$payment->num_paiement='';
		
			if($amount[$i] != 0){
				$paiement_id = $payment->create($userstatic,$soc);
				if ($paiement_id > 0)
				{
					$result=$payment->addPaymentToBank($userstatic,'payment','(CustomerFacturePayment)',$bankaccountid[$i],$aryTicket['customerId'],'','');
					if (! $result > 0)
					{
						$error++;
					}
				}
				else
				{
					$error++;
				}
			}
			$i++;
		}
		if (!is_null($pyMod))
		{
			$pTicket->mode_reglement($pyMod);
		}

		if($error)return -1;
		else return $paiement_id;
	}
	
	/**
	 *
	 * Add facture Payment
	 *
	 * @param array $aryTicket	Ticket data array
	 */
	private function addPaymentFac($aryTicket)
	{
		global $db, $langs, $conf;
	
		require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
		$now=dol_now();
		$userstatic=new User($db);
		$error = 0;
		if(! $aryTicket['employeeId'])
        {
        	$employee=$_SESSION['uid'];

        }
        else 
        {
        	$employee=$aryTicket['employeeId'];
        }
		$userstatic->fetch($employee);
		
		$max_ite = 4;
		
		if($aryTicket['convertDis']){
			require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
			$object = new Facture($db);
			$object->fetch($aryTicket['id']);
			$object->fetch_thirdparty();
			

			// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
			$discountcheck=new DiscountAbsolute($db);
			$result=$discountcheck->fetch(0,$object->id);

			$canconvert=0;
			if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->paye == 0 && empty($discountcheck->id)) $canconvert=1;	// we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
			if ($canconvert)
			{
				$db->begin();

				// Boucle sur chaque taux de tva
				$i = 0;
				foreach ($object->lines as $line) {
					$amount_ht [$line->tva_tx] += $line->total_ht;
					$amount_tva [$line->tva_tx] += $line->total_tva;
					$amount_ttc [$line->tva_tx] += $line->total_ttc;
					$i ++;
				}

				// Insert one discount by VAT rate category
				$discount = new DiscountAbsolute($db);
				if ($object->type == Facture::TYPE_CREDIT_NOTE)
					$discount->description = $langs->trans('DiscountOf',$object->ref);
				
				$discount->tva_tx = abs($object->total_ttc);
				$discount->fk_soc = $object->socid;
				$discount->fk_facture_source = $object->id;

				$error = 0;
				foreach ($amount_ht as $tva_tx => $xxx) {
					$discount->amount_ht = abs($amount_ht [$tva_tx]);
					$discount->amount_tva = abs($amount_tva [$tva_tx]);
					$discount->amount_ttc = abs($amount_ttc [$tva_tx]);
					$discount->tva_tx = abs($tva_tx);

					$paiement_id = $discount->create($userstatic);
					if ($paiement_id < 0)
					{
						$error++;
						break;
					}
				}

				if (empty($error))
				{
					// Classe facture
					$paiement_id = $object->set_paid($user);
					if ($result >= 0)
					{
						//$mesgs[]='OK'.$discount->id;
						$db->commit();
					}
					else
					{
						$db->rollback();
					}
				}
				else
				{
					$db->rollback();
				}
			}
		}
		else{
			if($aryTicket['type']==1)
			{
				if($aryTicket['total'] > $aryTicket['customerpay'] && $aryTicket['difpayment'] == 0){
					dol_include_once('/rewards/class/rewards.class.php');
					$reward = new Rewards($db);
					$facture = new Facture($db);
					$facture->fetch($aryTicket['id']);
					
					$modepay[4] =  dol_getIdFromCode($db,'PNT','c_paiement');
					$amount[4] = $aryTicket['total'] - $aryTicket['customerpay'];
					
					$result = $reward->create($facture, (price2num($amount[4])/$conf->global->REWARDS_DISCOUNT));
					$max_ite++;
					$amount[4]=$amount[4]*-1;
					//TODO tot molt bonico, pero que pasa si no gaste punts?
				}
				$aryTicket['total']=$aryTicket['total']*-1;
				$aryTicket['customerpay1']=$aryTicket['customerpay1']*-1;
				$aryTicket['customerpay2']=$aryTicket['customerpay2']*-1;
				$aryTicket['customerpay3']=$aryTicket['customerpay3']*-1;
				$aryTicket['customerpay4']=$aryTicket['customerpay4']*-1;
			}
		
			$cash = new Cash($db);
			 
			$terminal = $_SESSION['TERMINAL_ID'];
			$cash->fetch($terminal);
			 
			if ($aryTicket['customerpay1'] != 0)
			{
				$bankaccountid[1] = $cash->fk_paycash;
				$modepay[1] =  $cash->fk_modepaycash;
				$amount[1] = $aryTicket['customerpay1'] + ($aryTicket['difpayment']<0?$aryTicket['difpayment']:0);
			}
			if($aryTicket['customerpay2'] != 0)
	        {
	        	$bankaccountid[2] = $cash->fk_paybank ;
	        	$modepay[2] =  $cash->fk_modepaybank;
	        	$amount[2] = $aryTicket['customerpay2'];
	        }
	        if($aryTicket['customerpay3'] != 0)
	        {
	        	$bankaccountid[3] = $cash->fk_paybank_extra ;
	        	$modepay[3] =  $cash->fk_modepaybank_extra;
	        	$amount[3] = $aryTicket['customerpay3'];
	        }
	        if($aryTicket['customerpay4'] != 0)
	        {
	        	$bankaccountid[4] = $cash->fk_paybank_extra_2 ;
	        	$modepay[4] =  $cash->fk_modepaybank_extra_2;
	        	$amount[4] = $aryTicket['customerpay4'];
	        }
	        if($aryTicket['customerpay5'] != 0)
	        {
	        	$bankaccountid[5] = $cash->fk_paybank_extra_3 ;
	        	$modepay[5] =  $cash->fk_modepaybank_extra_3;
	        	$amount[5] = $aryTicket['customerpay5'];
	        }
			//Añadir el posible pago de puntos
			if ($aryTicket['points'] > 0)
			{
				dol_include_once('/rewards/class/rewards.class.php');
				$reward = new Rewards($db);
				$facture = new Facture($db);
				$facture->fetch($aryTicket['id']);
				$res=$reward->usePoints($facture, $aryTicket['points']);
			}
			$i=1;
			
			$payment=new Paiement($db);
			
			while($i <= $max_ite){
				$payment->datepaye=$now;
				$payment->bank_account=$bankaccountid[$i];
				$payment->amounts[$aryTicket['id']]=$amount[$i];
				$payment->note=$langs->trans("Payment").' '.$langs->trans("Facture").' '.$aryTicket['ref'] ;
				$payment->paiementid=$modepay[$i];
				$payment->num_paiement='';
			
				if($amount[$i] != 0){
					$paiement_id = $payment->create($userstatic,1);
					
					if ($paiement_id > 0)
					{	
						if($payment->paiementid != dol_getIdFromCode($db,'PNT','c_paiement')){
							$result=$payment->addPaymentToBank($userstatic,'payment','(CustomerFacturePayment)',$bankaccountid[$i],$aryTicket['customerId'],'',1);
							
							if ($result < 0)
							{
								$error++;
							}
						}
					}
					else
					{
						$error++;
					}
				}
				$i++;
			}
		}
		if($error > 0)return -1;
		else return 1;//$paiement_id;
	}
	
	private function quitSotck($lines,$isreturn=false, $ticket_id = null)
	{
		global $db,$langs;
		require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
		
		$userstatic=new User($db);
		$userstatic->fetch($_SESSION['uid']); 
		
		$error=0;
		$cash = new Cash($db);	
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		$warehouse=$cash->fk_warehouse;
		
		foreach ( $lines as $line )
		{
			if(sizeof($line)>0)
			{
				if($line['idProduct'])
				{
					$dbQty = 0;
					if (!$isreturn)
					{
						$sql =	 'SELECT ls_stock_dec_qty FROM llx_pos_ticketdet '
								.'WHERE fk_ticket='.$line['idTicket'].' '
								.'  AND fk_product='.$line['idProduct']
								.'  AND ls_stock_dec_date IS NOT NULL'
								;
						if (!$lres = $db->query($sql))
						{
							dol_print_error($db);
							die();
						}
						while ($tlrow = $db->fetch_object($lres))
						{
							$dbQty = $dbQty + $tlrow->ls_stock_dec_qty;
						}
					}
					if ($dbQty == $line['cant'])
					{
						continue;
					}
					else
					{
						$toStoreQty = $line['cant'] - $dbQty;
					}
					if ($toStoreQty < 0)
					{
						$isreturn = true;
						$toStoreQty = abs($toStoreQty);
					}
			        $ticket = new Ticket($db);
			        $ticket->fetch($idTicket);
					$mouvP = new MouvementStock($db);
					//$mouvP->origin->id = $ticket_id;
					//$mouvP->origin->element = 'ticket';
					// We decrease stock for product
                    $mouvP->setOrigin('ticket',$ticket_id);
					if(!$isreturn)
					{
						$result=$mouvP->livraison($userstatic, $line['idProduct'], $warehouse, $toStoreQty, $line['price'], $langs->trans("Ticket {$ticket->ref} creado en el POS"));
					}
					else 
					{
						$result=$mouvP->reception($userstatic, $line['idProduct'], $warehouse, $toStoreQty, $line['price'], $langs->trans("Ticket {$ticket->ref} creado en el POS"));
					}
					if ($result < 0) { $error++; }
					
					if (!$error)
					{
						$sql =	 'UPDATE llx_pos_ticketdet '."\r\n"
								.'SET	ls_stock_dec_date = \''.gmdate('Y-m-d H:i:s').'\', '."\r\n"
								.'		ls_stock_dec_qty = '.$line['cant'].' '."\r\n"
								.'WHERE fk_ticket='.$line['idTicket'].' '."\r\n"
								.'  AND fk_product='.$line['idProduct']."\r\n"
								;
						if (!$db->query($sql))
						{
							dol_print_error($db);
							die();
						}
					}
					
				}
			
			}
		}
		return $error;	
	}
	
	public function quitStock(&$ticket,&$oldTicket)
	{
		if ($oldTicket === false)
		{
			return 0;
		}
		$forceQuit = (
						(
							($ticket->socid=='8' && $ticket->diff_payment < 0.01) 
						|| $ticket->socid!='8'
						) 
					&& $ticket->type == '0' 
					&& in_array($ticket->statut,array(1,2)) 
					)
					;
		$forceQuit = false;
		$evLabels = self::getEstadovArray(null,'label');
		global $db,$langs,$conf,$user;
		require_once(DOL_DOCUMENT_ROOT."/product/stock/class/mouvementstock.class.php");
		
		//$userstatic=new User($db);
		//$userstatic->fetch($_SESSION['uid']); 
		
		$error=0;
		$cash = new Cash($db);	
		$terminal = $ticket->fk_cash;
		$cash->fetch($terminal);
		$warehouse = $ticket->getEntrepot()->id;
		
		$stList =	self::getEstadovArray(null,'constant');
		
		
		$stockChange = array();
		$stockRise = array();
		foreach($stList as $k => $v)
		{
			$tConf = 'POS_ALMACEN_BAJA_STOCK_DE_'.$v;
			if(property_exists($conf->global,$tConf))
			{
				$stockChange[$k] = explode(',',$conf->global->$tConf);
			}
			$tConf = 'POS_ALMACEN_ALTA_STOCK_DE_'.$v;
			if(property_exists($conf->global,$tConf))
			{
				$stockRise[$k] = explode(',',$conf->global->$tConf);
			}
		}
		foreach ($oldTicket->lines as $oLine)
		{
			$continue = true;
			if (is_null($oLine->ls_warehouse_status))
 			{
 				$oLine->ls_warehouse_status = '0';
 			}
 			if ($forceQuit)
 			{
 				$continue = false;
 			}
			elseif (
					!is_null($oLine->ls_warehouse_status)
		 		&&	isset($stockChange[$oLine->ls_warehouse_status])
		 		&&	is_array($stockChange[$oLine->ls_warehouse_status])
		 		&&	count($stockChange[$oLine->ls_warehouse_status])
		 		&&	is_object($oLine)
		 		&&	!empty($oLine->fk_product)
		 		)
		 	{
		 		$continue = false;
		 	}
		 	elseif (
					!is_null($oLine->ls_warehouse_status)
		 		&&	isset($stockRise[$oLine->ls_warehouse_status])
		 		&&	is_array($stockRise[$oLine->ls_warehouse_status])
		 		&&	count($stockRise[$oLine->ls_warehouse_status])
		 		&&	is_object($oLine)
		 		&&	!empty($oLine->fk_product)
		 		)
	 		{
	 			$continue = false;
	 		}
	 		if ($continue)
	 		{
	 			continue;
	 		}
		 	foreach($ticket->lines as $line)
		 	{
		 		if (
		 				!is_object($line)
		 			||	empty($line->fk_product)	
				 	||	$oLine->fk_product != $line->fk_product
				 	)
		 		{
		 			continue;
		 		}
		 		if ($forceQuit || in_array($line->ls_warehouse_status,$stockChange[$oLine->ls_warehouse_status]))
		 		{
		 			$isreturn = false;
		 		}
		 		elseif (in_array($line->ls_warehouse_status,$stockRise[$oLine->ls_warehouse_status]))
		 		{
		 			$isreturn = true;
		 		}
		 		else
		 		{
		 			break;
		 		}
				$dbQty = 0;
				//$isreturn = $ticket->type==1 ? true:false ;
				if (!$isreturn)
					{
						$sql =	 'SELECT ls_stock_dec_qty FROM llx_pos_ticketdet '
								.'WHERE fk_ticket='.$ticket->id.' '
								.'  AND fk_product='.$line->fk_product
								.'  AND ls_stock_dec_date IS NOT NULL'
								;
						if (!$lres = $db->query($sql))
						{
							dol_print_error($db);
							die();
						}
						while ($tlrow = $db->fetch_object($lres))
						{
							$dbQty = $dbQty + $tlrow->ls_stock_dec_qty;
						}
					}
					if ($dbQty == $line->qty_ent)
					{
						continue;
					}
					else
					{
						$toStoreQty = $line->qty_ent - $dbQty;
					}
					if ($toStoreQty < 0)
					{
						$isreturn = true;
						$toStoreQty = abs($toStoreQty);
					}
					$mouvP = new MouvementStock($db);
                    $mouvP->setOrigin('ticket',$ticket->id);
                    
					if(!$isreturn)
					{
						$result=$mouvP->livraison($user, $line->fk_product, $warehouse, $toStoreQty, $line->price, $langs->trans("Ticket {$ticket->ref} del POS ({$evLabels[$oLine->ls_warehouse_status]} a {$evLabels[$line->ls_warehouse_status]})"));
					}
					else 
					{
						$result=$mouvP->reception($user, $line->fk_product, $warehouse, $toStoreQty, $line->price, $langs->trans("Ticket {$ticket->ref} del POS ({$evLabels[$oLine->ls_warehouse_status]} a {$evLabels[$line->ls_warehouse_status]})"));
					}
					if ($result < 0) { $error++; }
					
					if (!$error)
					{
						$sql =	 'UPDATE llx_pos_ticketdet '."\r\n"
								.'SET	ls_stock_dec_date = \''.gmdate('Y-m-d H:i:s').'\', '."\r\n"
								.'		ls_stock_dec_qty = '.$line->qty_ent.' '."\r\n"
								.'WHERE fk_ticket='.$ticket->id.' '."\r\n"
								.'  AND fk_product='.$line->fk_product."\r\n"
								;
						if (!$db->query($sql))
						{
							dol_print_error($db);
							die();
						}
					}
		 			break;
		 	}
 			
		}
		return $error;	
	}

	/**
	 * 
	 * Get user POS
	 * 
	 * @return	array	User and terminal name
	 */
	public static function getLogin()
	{
		global $db, $conf;
		
		$error=0;
		$function="getLogin";
		
		$userstatic=new User($db);
		if ($userstatic->fetch($_SESSION['uid'])!=0) $error++;
			
		$cash = new Cash($db);
		$terminal = $_SESSION['TERMINAL_ID'];
		if ($cash->fetch($terminal)<0) $error++;

		if(!$error)
		{
			$ret['User'] = $userstatic->getFullName($langs);
			$ret['Terminal'] = $cash->name;
			return ErrorControl($ret, $function);
		}
		else 
		{
			$error=$error*-1;
			return ErrorControl($error,$function);
		}
	}
	
	/**
	 * 
	 * Create Customer into DB
	 * 
	 * @param		array 	$aryCustomer 	Customer object
	 * @return		array	$result		Result
	 */
	public static function SetCustomer($aryCustomer)
	{	
		require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");
		
		global $conf, $db, $user;
		$function="SetCustomer";
		$res = -1 ;
		
		//We use code creation
        $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
        
        if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
        {
            $module = substr($module, 0, dol_strlen($module)-4);
        }
        require_once(DOL_DOCUMENT_ROOT ."/core/modules/societe/".$module.".php");
        $modCodeClient = new $module;
                
		$object = new Societe($db);
		
		
	
		$object->particulier = 1;
		$object->typent_id             	= 8; // TODO predict another method if the field "special" change of rowid
		$object->client                	= 1;
        $object->fournisseur           	= 0; 
		$object->tva_assuj = 1;
        $object->status= 1;
        		
		$object->name                  	= $conf->global->MAIN_FIRSTNAME_NAME_POSITION?trim($aryCustomer['prenom'].' '.$aryCustomer["nom"]):trim($aryCustomer["nom"].' '.$aryCustomer["prenom"]);
		$object->idprof1               	= $aryCustomer["idprof1"];
		$object->address				= $aryCustomer["address"];
		if (strlen(trim($aryCustomer["outnum"])))
		{
			if (is_numeric(trim($aryCustomer["outnum"])))
			{
				$object->address			.= ' #'.trim($aryCustomer["outnum"]);
			}
			else
			{
				$object->address			.= ' '.trim($aryCustomer["outnum"]);
			}
		}
		if (strlen(trim($aryCustomer["innum"])))
		{
			$object->address			.= '-'.trim($aryCustomer["innum"]);
		}
		if (strlen(trim($aryCustomer["neigh"])))
		{
			$object->address			.= ', '.trim($aryCustomer["neigh"]);
		}
		if (strlen(trim($aryCustomer["county"])))
		{
			$object->address			.= ', '.trim($aryCustomer["county"]);
		}
		
		
		$object->town					= $aryCustomer["town"];
		$object->zip					= $aryCustomer["zip"];
        $object->phone					= $aryCustomer["tel"] ;     
        $object->email					= $aryCustomer["email"] ; 
        
        if ($modCodeClient->code_auto) $tmpcode=$modCodeClient->getNextValue($object,0);
        $object->code_client = $tmpcode;
        
        $res=$object->create($user);
        
        $sql =	 'INSERT INTO `llx_cfdimx_receptor_datacomp` '."\r\n"
				.'( '."\r\n"
				.' `receptor_rfc` '."\r\n"
				.',`receptor_delompio` '."\r\n"
				.',`receptor_colonia` '."\r\n"
				.',`receptor_calle` '."\r\n"
				.',`receptor_noext` '."\r\n"
				.',`receptor_noint` '."\r\n"
				.',`entity_id` '."\r\n"
				.') '."\r\n"
				.'VALUES '."\r\n"
				.'( '."\r\n"
				.'\''.$db->escape($aryCustomer["idprof1"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["county"]).'\','."\r\n" // TODO Municipio
				.'\''.$db->escape($aryCustomer["neigh"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["address"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["outnum"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["innum"]).'\','."\r\n"
				.'\''.$db->escape('1').'\''."\r\n" //TODO fijar societe variable
				.')'
				;
		if (!$dbres = $db->query($sql))
		{
			dol_print_error($db);
			return array('data'=>null,'error'=>array('value'=>1,'desc'=>'No se pudo crear el domicilio fiscal'));
		}
		$iid = $db->last_insert_id('llx_cfdimx_receptor_datacomp');
        $sql =	 'INSERT INTO `llx_cfdimx_domicilios_receptor` '."\r\n"
				.'( '."\r\n"
				.' `receptor_rfc` '."\r\n"
				.',`tpdomicilio` '."\r\n"
				.',`receptor_delompio` '."\r\n"
				.',`receptor_colonia` '."\r\n"
				.',`receptor_calle` '."\r\n"
				.',`receptor_noext` '."\r\n"
				.',`receptor_noint` '."\r\n"
				.',`receptor_id` '."\r\n"
				.',`entity_id` '."\r\n"
				.',`determinado` '."\r\n"
				.',`cod_municipio` '."\r\n"
				.',`fk_socid` '."\r\n"
				.') '."\r\n"
				.'VALUES '."\r\n"
				.'( '."\r\n"
				.'\''.$db->escape($aryCustomer["idprof1"]).'\','."\r\n"
				.'\''.$db->escape('FISCAL (POS)').'\','."\r\n"
				.'\''.$db->escape($aryCustomer["county"]).'\','."\r\n" // TODO Municipio
				.'\''.$db->escape($aryCustomer["neigh"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["address"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["outnum"]).'\','."\r\n"
				.'\''.$db->escape($aryCustomer["innum"]).'\','."\r\n"
				.'\''.$db->escape($iid).'\','."\r\n" 
				.'\''.$db->escape('1').'\','."\r\n" //TODO fijar societe variable
				.'\''.$db->escape('1').'\','."\r\n" //TODO fijar variable
				.'\''.$db->escape('').'\','."\r\n" //TODO código del municipio
				.'\''.$db->escape($res).'\''."\r\n" //TODO fijar societe variable
				.')'
				;
		if (!$dbres = $db->query($sql))
		{
			dol_print_error($db);
			return array('data'=>null,'error'=>array('value'=>1,'desc'=>'No se pudo crear el domicilio fiscal.'));
		}
		
        

		return ErrorControl($res, $function);

	}
	
	/**
	 * 
	 * Create product into DB
	 * 
	 * @param		array 	$aryProduct 	Product object
	 * @return		array	$result		Result
	 */
	public static function SetProduct($aryProduct)
	{	
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		
		global $conf, $db, $mysoc;
		
		$code_pays="'".$mysoc->country_code."'";
		
		$function="SetProduct";
		$res = -1 ;

		$sql  = "SELECT DISTINCT t.taux";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
        $sql.= " WHERE t.fk_pays = p.rowid";
        $sql.= " AND t.active = 1";
        $sql.= " AND t.rowid = ". $aryProduct['tax'];
        $sql.= " AND p.code in (".$code_pays.")";
        $sql.= " ORDER BY t.taux DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $db->fetch_object($resql);
				
                }
            }         
        }
        
        $myproduct=new Product($db);

		$myproduct->ref                	= $aryProduct['ref'];
		$myproduct->libelle            	= $aryProduct['label'];
		$myproduct->price_ttc          	= $aryProduct['price_ttc'];
		$myproduct->price_base_type    	= 'TTC';
        $myproduct->tva_tx 				= $obj->taux;
		$myproduct->type            	= 0;
		$myproduct->status             	= 1;
			
		$userstatic=new User($db);
		$userstatic->fetch($_SESSION['uid']); 
		
		$res = $myproduct->create($userstatic);
		
		return ErrorControl($res,$function);
	}
	
	/**
	 * 
	 * Return the VAT list
	 * 
	 * @return		array		Applicable VAT
	 */
	public static function select_VAT()
	{
		global $db,$conf,$langs, $mysoc;
		
		$code_pays="'".$mysoc->country_code."'";
		
		$sql  = "SELECT DISTINCT t.rowid, t.taux";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
        $sql.= " WHERE t.fk_pays = p.rowid";
        $sql.= " AND t.active = 1";
        $sql.= " AND p.code in (".$code_pays.")";
        $sql.= " ORDER BY t.taux DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num)
            {
                for ($i = 0; $i < $num; $i++)
                {
                    $obj = $db->fetch_object($resql);
                    $vat[$i]['id']  = $obj->rowid;
                    $vat[$i]['label'] = $obj->taux.'%';
                }
            }         
        }
        
        return $vat;		
	}

	/**
	 * 
	 * Return the money in cash
	 * 
	 * @return		array		Applicable VAT
	 */
	public static function getMoneyCash($open=false)
	{
		global $db;
		
		$terminal = $_SESSION['TERMINAL_ID'];
		
		$cash = new ControlCash($db,$terminal);
	
		return $cash->getMoneyCash($open);
		
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param $aryClose
	 */
	public static function setControlCash($aryClose)
	{
		global $db,$user,$langs,$conf;
		
		$function = "closeCash";
		$error=0;
		
		$terminalid = $_SESSION['TERMINAL_ID'];
		$userpos = new User($db);
		$userpos->fetch($aryClose['employeeId']);
		$userpos->getrights('pos');
		if($userpos->rights->pos->closecash || !$aryClose['type']){
		
		
			$cash = new ControlCash($db,$terminalid);
			
			$data['userid'] 		= $aryClose['employeeId'];
			$data['amount_reel'] 	= $aryClose['moneyincash'];
			$data['amount_teoric'] 	= $cash->getMoneyCash();
			$data['amount_diff'] 	= $data['amount_reel'] - $data['amount_teoric'];
			$data['type_control'] 	= $aryClose['type'];
			$data['print']			= $aryClose['print'];
			$data['d_1000']			= $aryClose['d_1000'];
			$data['d_500']			= $aryClose['d_500'];
			$data['d_200']			= $aryClose['d_200'];
			$data['d_100']			= $aryClose['d_100'];
			$data['d_50']			= $aryClose['d_50'];
			$data['d_20']			= $aryClose['d_20'];
			$data['d_10']			= $aryClose['d_10'];
			$data['d_5']			= $aryClose['d_5'];
			$data['d_2']			= $aryClose['d_2'];
			$data['d_1']			= $aryClose['d_1'];
			$data['d_05']			= $aryClose['d_05'];
			$data['quantity_delivery']			= $aryClose['quantity_delivery'];

			$res = $cash->create($data);
						
			if ($res>0) 
			{
				$terminal = new Cash($db);
				$userstatic=new User($db);
				$userstatic->fetch($userpos->id);
				$terminal->fetch($terminalid);
				
				if($aryClose['type']==1)
				{
					if(!$terminal->set_closed($userstatic)) 
						$error++;
				}
				elseif($aryClose['type']==2)
				{
					if (!$terminal->set_open($userstatic)) 
						$error++;
				}

				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
				//Ajuste de 5 pesos
				$sql = "SELECT SUM(amount) as balance FROM llx_bank WHERE fk_account=".$terminal->fk_paycash;
				$resql = $db->query($sql);
				$balance = 0;
				if($resql){
					$balance = $db->fetch_object($resql)->balance;
				}
				$saldo_cierre = 0;
				$saldo_cierre += ($data['d_1000'] * 1000);
				$saldo_cierre += ($data['d_500'] * 500);
				$saldo_cierre += ($data['d_200'] * 200);
				$saldo_cierre += ($data['d_100'] * 100);
				$saldo_cierre += ($data['d_50']* 50);
				$saldo_cierre += ($data['d_20'] * 20);
				$saldo_cierre += ($data['d_10'] * 10);
				$saldo_cierre += ($data['d_5'] * 5);
				$saldo_cierre += ($data['d_2'] * 2);
				$saldo_cierre += ($data['d_1'] * 1);
				$saldo_cierre += ($data['d_05'] * 0.5);
				if(abs($balance-$saldo_cierre) <= 5 && abs($balance-$saldo_cierre) != 0){
					//Aqui va el ajuste de caja
					$accline = new AccountLine($db);
					$accline->datec = dol_now();
					$accline->dateo = dol_now();
					$accline->datev = dol_now();
					$accline->label = 'Ajuste de caja';
					$accline->amount = $saldo_cierre-$balance;
					$accline->fk_user_author = $user->id;
					$accline->fk_account = $terminal->fk_paycash;
					$accline->fk_type = 'LIQ';
					$res = $accline->insert();
				}

                $accountfrom=new Account($db);
                $accountfrom->fetch($terminal->fk_paycash);

                $accountto=new Account($db);
                $accountto->fetch(4);
                //Transferencia de resta por pagar
                $dateo = dol_now();
                $label = 'Cantidad a enviar en Corte';
                $amount = $aryClose['quantity_delivery'];
                $amountto = $amount;
                if (($accountto->id != $accountfrom->id) && empty($error) && $amount != 0)
                {
                    $db->begin();

                    $bank_line_id_from=0;
                    $bank_line_id_to=0;
                    $result=0;

                    // By default, electronic transfert from bank to bank
                    $typefrom='PRE';
                    $typeto='VIR';
                    if ($accountto->courant == Account::TYPE_CASH || $accountfrom->courant == Account::TYPE_CASH)
                    {
                        // This is transfer of change
                        $typefrom='LIQ';
                        $typeto='LIQ';
                    }

                    if (! $error) $bank_line_id_from = $accountfrom->addline($dateo, $typefrom, $label, -1*price2num($amount), '', '', $user);
                    if (! ($bank_line_id_from > 0)) $error++;
                    if (! $error) $bank_line_id_to = $accountto->addline($dateo, $typeto, $label, price2num($amountto), '', '', $user);
                    if (! ($bank_line_id_to > 0)) $error++;

                    if (! $error) $result=$accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
                    if (! ($result > 0)) $error++;
                    if (! $error) $result=$accountto->add_url_line($bank_line_id_to, $bank_line_id_from, DOL_URL_ROOT.'/compta/bank/line.php?rowid=', '(banktransfert)', 'banktransfert');
                    if (! ($result > 0)) $error++;

                    if (! $error)
                    {
                        //$mesgs = $langs->trans("TransferFromToDone", '<a href="bankentries_list.php?id='.$accountfrom->id.'&sortfield=b.datev,b.dateo,b.rowid&sortorder=desc">'.$accountfrom->label."</a>", '<a href="bankentries_list.php?id='.$accountto->id.'">'.$accountto->label."</a>", $amount, $langs->transnoentities("Currency".$conf->currency));
                        //setEventMessages($mesgs, null, 'mesgs');
                        $db->commit();

						$ref = 'Transfer' . " $bank_line_id_from";
						$dir = $conf->bank->dir_output."/Transfer_" . $bank_line_id_from;

						$model_pdf = 'transfer';
						require_once DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php";
						// Load object. Make an object->fetch
						
						$objectal = new AccountLine($db);
						if ($objectal->fetch($bank_line_id_from) > 0) {
							if (dol_mkdir($dir) >= 0) {
								// Build doc with given signature
								$rs = $objectal->generateDocument((!empty($model_pdf)) ? $model_pdf : $objectal->model_pdf, $langs);
								if ($rs) {
									// Url to force redirect
									$urladvancedpreview = DOL_URL_ROOT.'/document.php?modulepart=bank&file='.urlencode('/Transfer_'. $bank_line_id_from.'/Transferencia'.$bank_line_id_from.'.pdf');
									//$urladvancedpreview = getAdvancedPreviewUrl('bank', GETPOST('relativepath', 'none', 2), 1); // Return if a file is qualified for preview.
								}
							}
						}
                    }
                    else
                    {
                        $error = -2;
                        $db->rollback();
                    }
				}
				/*else
				{
					$error++;
				}*/
			}
			else
			{
				$error = 2;
			} 
		
			if ($error==0) {
				$error = $res;
            }
            else
            {
                $error = -3;
            }
        }
		else
			$error=$error*-1;
		
		return ErrorControl($error,$function,$urladvancedpreview);
		
	}
	
	/**
	 * 
	 * Return POS Config
	 * 
	 * @return	array		Array with config
	 */
	public static function getConfig()
	{
		global $db,$conf,$langs, $mysoc;
	
		$cash = new Cash($db);
        	
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		
		$userstatic=new User($db);
		$userstatic->fetch($_SESSION['uid']); 
		
		$soc = new Societe($db, $cash->fk_soc);
		$soc->fetch($cash->fk_soc);
		$name=$soc->name?$soc->name:$soc->nom;
		
		$ret['error']['value'] = 0;
		$ret['error']['desc'] = '';
		
		$ret['data']['terminal']['id'] = $cash->id;
		$ret['data']['terminal']['name'] = $cash->name;
		$ret['data']['terminal']['tactil'] = $cash->tactil;
		$ret['data']['terminal']['warehouse'] = $cash->fk_warehouse;
		$ret['data']['terminal']['barcode'] = $cash->barcode;
		$ret['data']['terminal']['mode_info']= 0;
		$ret['data']['terminal']['faclimit']= $conf->global->POS_MAX_TTC;
		
		$ret['data']['module']['places']= $conf->global->POS_PLACES;
		$ret['data']['module']['print']= 1;//$conf->global->POS_PRINT;
		$ret['data']['module']['mail']= 1;//$conf->global->POS_MAIL;
		$ret['data']['module']['points']= $conf->global->REWARDS_DISCOUNT;
		$ret['data']['module']['ticket']= $conf->global->POS_TICKET;
		$ret['data']['module']['facture']= $conf->global->POS_FACTURE;
		$ret['data']['module']['print_mode']= 0;//$conf->global->POS_PRINT_MODE;

		$ret['data']['user']['id'] = $userstatic->id;
		$ret['data']['user']['name'] = $userstatic->getFullName($langs);
		$ret['data']['user']['posdesc'] = $userstatic->posdesc;
		/*$dir=$conf->user->dir_output;
		if ($userstatic->photo) $file=get_exdir($userstatic->id,2).$userstatic->photo;
		if ($file && file_exists($dir."/".$file)){
		$ret['data']['user']['photo'] = DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&entity='.$userstatic->entity.'&file='.urlencode($file);
		}
		else{
			$ret['data']['user']['photo'] = DOL_URL_ROOT.'/theme/common/nophoto.jpg';
		}*/
		$file = '../../../documents/mycompany/logos/'.$mysoc->logo;
		if ($file && file_exists($file))
			$ret['data']['user']['photo'] = DOL_URL_ROOT."/viewimage.php?cache=1&modulepart=companylogo&file=".$mysoc->logo;
		else
			$ret['data']['user']['photo'] = DOL_URL_ROOT.'/theme/common/nophoto.jpg';

		$ret['data']['customer']['id'] = $soc->id;
		$ret['data']['customer']['name'] = $name;
		$ret['data']['customer']['remise'] = $soc->remise_percent;
		$ret['data']['customer']['coupon'] = $soc->getAvailableDiscounts();
		$ret['data']['customer']['points'] = null;
        $ret['data']['customer']['limite']= $soc->outstanding_limit;
		if($conf->global->REWARDS_POS && ! empty($conf->rewards->enabled)){
			$rew= new Rewards($db);
			$res = $rew->getCustomerReward($soc->id);
			if($res){
				$ret['data']['customer']['points'] = $rew->getCustomerPoints($soc->id);
			}
		}
        //Proyectos asociados
        $sqlProyect = "SELECT title as proyect_t, rowid as proyect_id,budget_amount as presupuesto FROM ".MAIN_DB_PREFIX."projet WHERE fk_soc = ".$soc->id." AND fk_statut = 1";
        $resqlProyect=$db->query($sqlProyect);
        $numProyects = $db->num_rows($resqlProyect);
        if($numProyects > 0) {
            $iterator = 0;
            while ($iterator < $numProyects) {
                $proyecto = $db->fetch_object($resqlProyect);
                $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["proyect"] = $proyecto->proyect_t;
                $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["proyectid"] = $proyecto->proyect_id;
                $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["presupuesto"] = price2num($proyecto->presupuesto);
                //Gastos y cuentas
                $sqlGastos = "SELECT SUM(pv.amount) as gastos,SUM(ff.total_ttc)-SUM(pf.amount) as cuentas";
                $sqlGastos.= " FROM ".MAIN_DB_PREFIX."payment_various as pv";
                $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn AS ff on ff.fk_projet = pv.fk_projet";
                $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn AS pffp ON ff.rowid=pffp.fk_facturefourn";
                $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn AS pf ON pffp.fk_paiementfourn=pf.rowid";
                $sqlGastos.= " WHERE pv.fk_projet = ".$proyecto->proyect_id;
                $resGastos=$db->query($sqlGastos);
                $numeroGastos=$db->num_rows($resGastos);
                if($numeroGastos > 0)
                {
                    $gastos=$db->fetch_object($resGastos);
                    $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["gastos"] = $gastos->gastos== null?0:price2num($gastos->gastos);
                    $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["cuentas"] = $gastos->cuentas==null?0:price2num($gastos->cuentas);
                }
                else
                {
                    $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["gastos"] = 0;
                    $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["cuentas"] = 0;
                }
                //Por pagar
                $sqlPagar= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
                $sqlPagar.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                $sqlPagar.=" WHERE f.fk_projet=".$proyecto->proyect_id;
                $resqlPa = $db->query($sqlPagar);
                $numeroPagar = $db->num_rows($resqlPa);
                if($numeroPagar>0) {
                    $ppagar = $db->fetch_object($resqlPa);
                    $ret['data']['customer']['proyectos'][$proyecto->proyect_id]["por_pagar"]= $ppagar->total_pagar==null?0:price2num($ppagar->total_pagar);
                }
                else
                    $ret['data']['customer']['proyectos'][$proyecto->proyect_id]['por_pagar']=0;
                $iterator++;
            }
        }
        else{
            $ret['data']['customer']['proyectos'][0]["proyect"] = "&nbsp";
            $ret['data']['customer']['proyectos'][0]["proyectid"] = 0;
        }
		//Limite de credito y cuentas por pagar
        //Cuentas por pagar
        $sql= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
        $sql.=" FROM ".MAIN_DB_PREFIX."facture AS f";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
        $sql.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
        $sql.=" WHERE f.fk_soc=".$soc->id;
        $resql = $db->query($sql);
        if($resql) {
            $ppagar = $db->fetch_object($resql);
            $ret['data']['customer']['por_pagar']= $ppagar->total_pagar==null?0:price2num($ppagar->total_pagar,2);
        }
        else
            $ret['data']['customer']['por_pagar']=0;

		$ret['data']['decrange']['unit'] = $conf->global->MAIN_MAX_DECIMALS_UNIT;
		$ret['data']['decrange']['tot'] = $conf->global->MAIN_MAX_DECIMALS_TOT;
		$ret['data']['decrange']['maxshow'] = $conf->global->MAIN_MAX_DECIMALS_SHOWN;
		
		return $ret;
	}
	
	public static function testSource($aryTicket)
	{
		global $db,$conf;
		
		$data = $aryTicket['data'];
		$lines = $data['lines'];
     
        //Compare
        $i=0;
        foreach ( $lines as $line )
		{
			if(sizeof($line)>0)
			{
				if ($line['idProduct']>0)
	    		{
	    		 	//Returned products for Source ticket
					$sql = "SELECT td.qty from ".MAIN_DB_PREFIX."pos_ticketdet td";
					$sql .=" INNER JOIN ".MAIN_DB_PREFIX."pos_ticket t";
					$sql .=" WHERE td.fk_ticket = t.rowid" ;
					$sql .=" AND t.rowid= ".$data['idsource'];
					$sql .= " AND td.fk_product = ".$line['idProduct'];
		
					$resql=$db->query($sql);
					
        			if ($resql)
        			{
        				//Compare quantity returned
            			if ($db->num_rows($resql))
            			{
               				$obj = $db->fetch_object($result);
               				
               				$vendidas = $obj->qty;
               				        
						}
        			}
        			
	    			//Returned products for Source ticket
					$sql  = "SELECT sum(td.qty) as qty from ".MAIN_DB_PREFIX."pos_ticketdet td";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."pos_ticket t";
					$sql .= " WHERE td.fk_ticket = t.rowid" ;
					$sql .= " AND t.fk_ticket_source= ".$data['idsource'];
					$sql .= " AND td.fk_product = ".$line['idProduct'];
	
					$resql=$db->query($sql);
						
					if ($resql)
					{
						//Compare quantity returned
						if ($db->num_rows($resql))
						{
							$obj = $db->fetch_object($resql);
							if ($vendidas -abs($obj->qty) < $line['cant'])
							{
								$prods_returns[$i] = $line['idProduct'];
								$i++;
							}
						}
					}
	    		}			
			}
		}	
        
        
        return $prods_returns;
	}
	
	public static function testSourceFac($aryTicket)
	{
		global $db,$conf;
	
		$data = $aryTicket['data'];
		$lines = $data['lines'];
		 
		//Compare
		$i=0;
		foreach ( $lines as $line )
		{
			if(sizeof($line)>0)
			{
				if ($line['idProduct']>0)
				{
					$sql = "SELECT fd.qty from ".MAIN_DB_PREFIX."facturedet fd";
					$sql .=" INNER JOIN ".MAIN_DB_PREFIX."facture f";
					$sql .=" WHERE fd.fk_facture = f.rowid" ;
					$sql .=" AND f.rowid= ".$data['idsource'];
					$sql .= " AND fd.fk_product = ".$line['idProduct'];
		
					$resql=$db->query($sql);
        			if ($resql)
        			{
        						
        				//Compare quantity returned
            			if ($db->num_rows($resql))
            			{
               				$obj = $db->fetch_object($result);
               				
               				$vendidas = $obj->qty;
               				        
						}
        			}	
					//Returned products for Source ticket
					$sql  = "SELECT sum(fd.qty) as qty from ".MAIN_DB_PREFIX."facturedet fd";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."facture f";
					$sql .= " WHERE fd.fk_facture = f.rowid" ;
					$sql .= " AND f.fk_facture_source= ".$data['idsource'];
					$sql .= " AND fd.fk_product = ".$line['idProduct'];
	
					$resql=$db->query($sql);
						
					if ($resql)
					{
						//Compare quantity returned
						if ($db->num_rows($resql))
						{
							$obj = $db->fetch_object($resql);
							if ($vendidas -abs($obj->qty) < $line['cant'])
							{
								$prods_returns[$i] = $line['idProduct'];
								$i++;
							}
						}
					}
	
				}
			}
		}
	
	
		return $prods_returns;
	}
	
	/**
	 * Return the places of the company
	 * 
	 * @return array		return <0 if KO; array of places
	 */
	public static function getPlaces()
	{
		global $db,$conf,$langs;
		
		$sql  = 'SELECT rowid,';
		$sql .= 'name, ';
		$sql .= 'description, ';
		$sql .= 'status, ';
		$sql .= 'fk_ticket ';
		$sql .= 'From '.MAIN_DB_PREFIX.'pos_places p';
		$sql .= ' WHERE p.entity ='.$conf->entity;
		
		$resql=$db->query($sql);
		
		if ($resql)
			
		{
			$places = array();
			$num = $db->num_rows($resql);
			$i=0;
			
			while($i < $num)
			{
				$obj = $db->fetch_object($resql);
				
				$places[$i]["id"]= $obj->rowid;
				$places[$i]["name"]= $obj->name;
				$places[$i]["description"]= $obj->description;
				$places[$i]["fk_ticket"]= $obj->fk_ticket;
				$places[$i]["status"]= $obj->status;
				
				$i++;			
			}
		}
		return $places;	
	}
	
	/**
	 * Fill the body of email's message with a ticket
	 * 
	 * @param int $id
	 * 
	 * @return string		String with ticket data
	 */
	public static function fillMailTicketBody($id)
	{
		global $db,$conf,$langs,$mysoc;
		
		$ticket= new Ticket($db);
		$res= $ticket->fetch($id);
		$mysoc = new Societe($db);
		$mysoc->fetch($ticket->socid);
		$userstatic=new User($db);
		$userstatic->fetch($ticket->user_close);
		
		$label=$ticket->ref;
		$facture = new Facture($db);
		if($ticket->fk_facture){
			$facture->fetch($ticket->fk_facture);
			$label=$facture->ref;
		}		
		
		$message = $conf->global->MAIN_INFO_SOCIETE_NOM." \n".$conf->global->MAIN_INFO_SOCIETE_ADRESSE." \n". $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE." \n\n";
		
		$message .= $label." \n".dol_print_date($ticket->date_closed,'dayhourtext')." \n";
		$message .= $langs->transnoentities("Vendor").': '.$userstatic->firstname." ".$userstatic->lastname."\n";
		if(!empty($ticket->fk_place))
		{
			$place = new Place($db);
			$place->fetch($ticket->fk_place);
			$message .= $langs->trans("Place").': '.$place->name."\n";
		}
			
		$message .= "\n";
		$message .= $langs->transnoentities("Label")."\t\t\t\t\t\t\t\t\t". $langs->transnoentities("Qty")."/".$langs->transnoentities("Price")."\t\t"./*$langs->transnoentities("DiscountLineal")."\t\t".*/$langs->transnoentities("Total")."\n";
		//$ticket->getLinesArray();
		if (! empty($ticket->lines))
		{
			//$subtotal=0;
			foreach ($ticket->lines as $line)
			{
				$espacio = '';
				$totalline= $line->qty*$line->subprice;
				while(dol_strlen(dol_trunc($line->libelle,30).$espacio)<29){
					$espacio .="    \t";
				}
				$message .= dol_trunc($line->libelle,33).$espacio;
				$message .= "\t\t".$line->qty." * ".price($line->total_ttc/$line->qty,"","","","",2)."\t\t"./*$line->remise_percent."%\t\t\t".*/price($line->total_ttc,"","","","",2).' '.$langs->trans(currency_name($conf->currency))."\n";
				$subtotal[$line->tva_tx] += $line->total_ht;;
				$subtotaltva[$line->tva_tx] += $line->total_tva;
				if(!empty($line->total_localtax1)){
					$localtax1 = $line->localtax1_tx;
				}
				if(!empty($line->total_localtax2)){
					$localtax2 = $line->localtax2_tx;
				}
			}
		}
		else
		{
			$message .= $langs->transnoentities("ErrNoArticles")."\n";
		}
		$message .= $langs->transnoentities("TotalTTC").":\t".price($ticket->total_ttc,"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		
		$message .= '\n'.$langs->trans("TotalHT")."\t".$langs->trans("VAT")."\t".$langs->trans("TotalVAT")."\n";
				
		if(! empty($subtotal)){
			foreach($subtotal as $totkey => $totval){
				$message .= price($subtotal[$totkey],"","","","",2)."\t\t\t".price($totkey,"","","","",2)."%\t".price($subtotaltva[$totkey],"","","","",2)."\n";
			}
		}
		$message .= "-------------------------------\n";
		$message .= price($ticket->total_ht,"","","","",2)."\t\t\t----\t".price($ticket->total_tva,"","","","",2)."\n";
		if($ticket->total_localtax1!=0){
			$message .= $langs->transcountrynoentities("TotalLT1",$mysoc->country_code)." ".price($localtax1,"","","","",2)."%\t".price($ticket->total_localtax1,"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		}
		if($ticket->total_localtax2!=0){
			$message .= $langs->transcountrynoentities("TotalLT2",$mysoc->country_code)." ".price($localtax2,"","","","",2)."%\t".price($ticket->total_localtax2,"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		}
		
		$message .= "\n\n";
			
		$terminal = new Cash($db);
		$terminal->fetch($ticket->fk_cash);
		
		$pay = $ticket->getSommePaiement();
		
		if($ticket->customer_pay > $pay)
			$pay = $ticket->customer_pay;
			
		
		$diff_payment = $ticket->total_ttc - $pay;
		$listofpayments=$ticket->getListOfPayments();
		foreach($listofpayments as $paym)
		{
			if($paym['type'] != 'LIQ'){
				$message .= $terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement'))."\t".price($paym['amount'],"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
			}
			else{
				$message .= $terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement'))."\t".price($paym['amount']-($diff_payment<0?$diff_payment:0),"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
			}
		}
				
		$message .= ($diff_payment<0?$langs->trans("CustomerRet"):$langs->trans("CustomerDeb"))."\t".price(abs($diff_payment),"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		
		$message .= $conf->global->POS_PREDEF_MSG;
		return $message;
	}
	
	/**
	 * Fill the body of email's message with a facture
	 *
	 * @param int $id
	 *
	 * @return string		String with ticket data
	 */
	public static function fillMailFactureBody($id)
	{
		global $db,$conf,$langs,$mysoc;
		$langs->Load("rewards@rewards");
	
		$facture= new Facture($db);
		$res= $facture->fetch($id);
		$mysoc = new Societe($db);
		$mysoc->fetch($facture->socid);
		$userstatic=new User($db);
		$userstatic->fetch($facture->user_valid);
	
		$label=$facture->ref;
					
		$message = $conf->global->MAIN_INFO_SOCIETE_NOM." \n".$conf->global->MAIN_INFO_SOCIETE_ADRESSE." \n". $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE." \n\n";
		$message .= $label." \n".dol_print_date($facture->date_creation,'dayhourtext')." \n";
		$message .= $langs->transnoentities("Vendor").': '.$userstatic->fistname." ".$userstatic->lastname."\n";
		
		$sql = "SELECT fk_place,fk_cash FROM ".MAIN_DB_PREFIX."pos_facture WHERE fk_facture =".$facture->id;
		$result=$db->query($sql);
		
		if ($result)
		{
			$objp = $db->fetch_object($result);
			if($objp->fk_place > 0){
				$place = new Place($db);
				$place->fetch($objp->fk_place);
				$message .= $langs->trans("Place").': '.$place->name."\n";
			}
		}
		
		$message .= "\n";
		$message .= $langs->transnoentities("Label")."\t\t\t\t\t\t". $langs->transnoentities("Qty")."/".$langs->transnoentities("Price")."\t\t"/*.$langs->transnoentities("DiscountLineal")."\t\t"*/.$langs->transnoentities("Total")."\n";
		//$facture->getLinesArray();
		if (! empty($facture->lines))
		{
			$subtotal=0;
			foreach ($facture->lines as $line)
			{
				$espacio = '';
				$totalline= $line->qty*$line->subprice;
				if(empty($line->libelle))
					$line->libelle = $line->description;
				while(dol_strlen(dol_trunc($line->libelle,30).$espacio)<29){
					$espacio .="    \t";
				}
				$message .= dol_trunc($line->libelle,33).$espacio;
				$message .= "\t\t".$line->qty." * ".price($line->subprice)."\t\t"./*$line->remise_percent."%\t\t\t".*/price($line->total_ht).' '.$langs->trans(currency_name($conf->currency))."\n";
				$subtotal[$line->tva_tx] += $line->total_ht;
				$subtotaltva[$line->tva_tx] += $line->total_tva;
				if(!empty($line->total_localtax1)){
					$localtax1 = $line->localtax1_tx;
				}
				if(!empty($line->total_localtax2)){
					$localtax2 = $line->localtax2_tx;
				}
			}
		}
		else
		{
			$message .= $langs->transnoentities("ErrNoArticles")."\n";
		}
	
		
		$message .= $langs->transnoentities("TotalTTC").":\t".price($facture->total_ttc)." ".$langs->trans(currency_name($conf->currency))."\n";
		$message .= "\n".$langs->trans("TotalHT")."\t".$langs->trans("VAT")."\t".$langs->trans("TotalVAT")."\n";
		
		if(! empty($subtotal)){
			foreach($subtotal as $totkey => $totval){
				if($tvakey > 0)
					$message .= price($subtotal[$totkey],"","","","",2)."\t\t\t".price($totkey,"","","","",2)."%\t".price($subtotaltva[$totkey],"","","","",2)."\n";
			}
		}
		$message .= "-------------------------------\n";
		$message .= price($facture->total_ht,"","","","",2)."\t\t\t----\t".price($facture->total_tva,"","","","",2)."\n";
		
		if($facture->total_localtax1!=0){
			$message .= $langs->transcountrynoentities("TotalLT1",$mysoc->country_code)." ".price($localtax1,"","","","",2)."%\t".price($facture->total_localtax1,"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		}
		if($facture->total_localtax2!=0){
			$message .= $langs->transcountrynoentities("TotalLT2",$mysoc->country_code)." ".price($localtax2,"","","","",2)."%\t".price($facture->total_localtax2,"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		}
		
		$message .= "\n\n";
			
		$terminal = new Cash($db);
		$sql = 'SELECT fk_cash, customer_pay FROM '.MAIN_DB_PREFIX.'pos_facture WHERE fk_facture = '.$facture->id;
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$customer_pay = $obj->customer_pay;
		$terminal->fetch($obj>fk_cash);
		
		if (! empty($conf->rewards->enabled)){
			$rewards = new Rewards($db);
			$points = $rewards->getInvoicePoints($facture->id);
		}
		if ($facture->type==0)
		{
			$pay = $facture->getSommePaiement();
				
			if (! empty($conf->rewards->enabled)){
				$usepoints= abs($rewards->getInvoicePoints($facture->id,1));
				$moneypoints = abs($usepoints*$conf->global->REWARDS_DISCOUNT);//falta fer algo per aci
				if($customer_pay > $pay-$moneypoints)
					$pay = $customer_pay;
				else
					$pay = $pay-$moneypoints;
			}
			else{
				if($customer_pay > $pay)
					$pay = $customer_pay;
			}	
		}
		if ($facture->type==2)
		{
			$customer_pay = $customer_pay*-1;
			$pay = $facture->getSommePaiement();
		
			if (! empty($conf->rewards->enabled)){
				$usepoints= abs($rewards->getInvoicePoints($facture->id,1));
				$moneypoints = abs($usepoints*$conf->global->REWARDS_DISCOUNT);//falta fer algo per aci
				if($customer_pay > $pay-$moneypoints)
					$pay = $customer_pay;
				else
					$pay = $pay-$moneypoints;
			}
			else{
				if($customer_pay > $pay)
					$pay = $customer_pay;
			}
		}
		$diff_payment = $facture->total_ttc -$moneypoints - $pay;
		$listofpayments=$facture->getListOfPayments();
		foreach($listofpayments as $paym)
		{
			if($paym['type'] != 'PNT'){
				if($paym['type'] != 'LIQ'){
					$message .= $terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement'))."\t".price($paym['amount'],"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
				}
				else{
					$message .= $terminal->select_Paymentname(dol_getIdFromCode($db,$paym['type'],'c_paiement'))."\t".price($paym['amount']-($diff_payment<0?$diff_payment:0),"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
				}
			}
		}
		if (! empty($conf->rewards->enabled)){
			if ($moneypoints>0){
				$message .= $usepoints." ".$langs->trans("Points")."\t".price($moneypoints,"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
			}
		}
		
		$message .= ($diff_payment<0?$langs->trans("CustomerRet"):$langs->trans("CustomerDeb"))."\t".price(abs($diff_payment),"","","","",2)." ".$langs->trans(currency_name($conf->currency))."\n";
		
		if ($points != 0 && ! empty($conf->rewards->enabled))
		{
			$message .= $langs->trans("TotalPointsInvoice")."\t".price($points,"","","","",2)." ".$langs->trans('Points')."\n";
			$total_points = $rewards->getCustomerPoints($facture->socid);
			$message .= $langs->trans("DispoPoints")."\t".price($total_points,"","","","",2)." ".$langs->trans('Points')."\n";
		}
		$message .= $conf->global->POS_PREDEF_MSG;
		return $message;
	}
	
	/**
	 * Fill the body of email's message with a close cash
	 *
	 * @param int $id
	 *
	 * @return string		String with ticket data
	 */
	public static function FillMailCloseCashBody($id)
	{
		global $db,$conf,$langs,$mysoc;
		
		$sql = "select fk_user, date_c, fk_cash, ref";
		$sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
		$sql .=" where rowid = ".$id;
		$result=$db->query($sql);
		
		if ($result)
		{
			$objp = $db->fetch_object($result);
			$date_end = $objp->date_c;
			$fk_user = $objp->fk_user;
			$terminal = $objp->fk_cash;	
			$ref = $objp->ref; 
		}
		
		$sql = "select date_c";
    	$sql .=" from ".MAIN_DB_PREFIX."pos_control_cash";
    	$sql .=" where fk_cash = ".$terminal." AND date_c < ".$date_end;
    	$sql .=" ORDER BY date_c DESC";
    	$sql .=" LIMIT 1";
    	$result=$db->query($sql);
		
		if ($result)
		{
			$objd = $db->fetch_object($result);
        	$date_start = $objd->date_c;
        }
		
		$message = $conf->global->MAIN_INFO_SOCIETE_NOM." \n".$conf->global->MAIN_INFO_SOCIETE_ADRESSE." \n". $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE." \n\n";
		$message .= $langs->transnoentities("CloseCashReport").': '.$ref."\n";
		$cash = new Cash($db);
		$cash->fetch($terminal);
		$message .= $langs->transnoentities("Terminal").': '.$cash->name."\n";
		
		$userstatic=new User($db);
		$userstatic->fetch($fk_user);
		$message .= $langs->transnoentities("User").': '.$userstatic->firstname.' '.$userstatic->lastname."\n";
		$message .= dol_print_date($db->jdate($date_end),'dayhourtext')."\n\n";
		
		$message .= $langs->transnoentities("TicketsCash")."\n";
		$message .= $langs->transnoentities("Ticket")."\t\t\t\t\t". $langs->transnoentities("Total")."\n";
		
		$sql = "SELECT t.ticketnumber, p.amount, t.type";
    	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p";
    	$sql .=" WHERE t.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaycash." AND t.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";
    	
    	$sql .= " UNION SELECT f.ref, p.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement=".$cash->fk_modepaycash. " AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";
    	
    	$result=$db->query($sql);
		
		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
	            $i = 0;
	            $subtotalcash=0;
	            while ($i < $num)
	            {
	            	$objp = $db->fetch_object($result);
	            	
	            	$message .= $objp->ticketnumber."\t\t".price($objp->amount)."\n";
	            	$i++;
	            	$subtotalcash+=$objp->amount;
	            }
			}
			else
			{
				$message .= $langs->transnoentities("NoTickets")."\n";
			}
		}

	$message .= $langs->trans("TotalCash")."\t".price($subtotalcash)." ".$langs->trans(currency_name($conf->currency))."\n";
	$message .= $langs->trans("TicketsCreditCard")."\n";

	$message .= $langs->trans("Ticket")."\t\t". $langs->trans("Total")."\n";

		// Credit card
		$sql = "SELECT t.ticketnumber, p.amount, t.type";
    	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t, ".MAIN_DB_PREFIX."pos_paiement_ticket as pt, ".MAIN_DB_PREFIX."paiement as p";
    	$sql .=" WHERE t.fk_cash=".$terminal." AND (p.fk_paiement=".$cash->fk_modepaybank." OR p.fk_paiement=".$cash->fk_modepaybank_extra.")AND t.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pt.fk_paiement AND t.rowid = pt.fk_ticket ";
    	
    	$sql .= " UNION SELECT f.ref, p.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND (p.fk_paiement=".$cash->fk_modepaybank." OR p.fk_paiement=".$cash->fk_modepaybank_extra.") AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";
    	 
    	$result=$db->query($sql);
		
		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
	            $i = 0;
	            $subtotalcard=0;
	            while ($i < $num)
	            {
	            	$objp = $db->fetch_object($result);
	            	
	            	$message .= $objp->ticketnumber."\t\t".price($objp->amount)."\n";
	            	$i++;
	            	$subtotalcard+=$objp->amount;
	            }
			}
			else
			{
				$message .= $langs->transnoentities("NoTickets")."\n";
			}	
		}

	$message .= $langs->trans("TotalCard")."\t".price($subtotalcard)." ".$langs->trans(currency_name($conf->currency))."\n";
	
if(!empty($conf->rewards->enabled)){
	$message .= $langs->trans("Points")."\n";

	$message .= $langs->trans("Ticket"); "\t\t". $langs->trans("Total")."\n";

		$sql = " SELECT f.ref, p.amount, f.type";
    	$sql .= " FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pfac, ".MAIN_DB_PREFIX."paiement as p ";
    	$sql .= " WHERE pf.fk_cash=".$terminal." AND p.fk_paiement= 100 AND pf.fk_facture = f.rowid and f.fk_statut > 0 AND p.datep > '".$date_start."' AND p.datep < '".$date_end."'";
    	$sql .= " AND p.rowid = pfac.fk_paiement AND f.rowid = pfac.fk_facture";
    	 
    	$result=$db->query($sql);
		
		if ($result)
		{
			$num = $db->num_rows($result);
			if($num>0)
			{
	            $i = 0;
	            $subtotalpoint=0;
	            while ($i < $num)
	            {
	            	$objp = $db->fetch_object($result);
	            	$message .= $objp->ref."\t\t".price($objp->amount)."\n";
	            	$i++;
	            	$subtotalpoint+=$objp->amount;
	            }
			}
			else
			{
				$message .= $langs->transnoentities("NoTickets")."\n";
			}	
		}

	
	$message .= $langs->trans("TotalPoints")."\t".price($subtotalpoint)." ".$langs->trans(currency_name($conf->currency))."\n\n\n";
}
	/*$sql = "SELECT t.ticketnumber, t.type, l.total_ht, l.tva_tx, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_ticket as t left join ".MAIN_DB_PREFIX."pos_ticketdet as l on l.fk_ticket= t.rowid";
	$sql .=" WHERE t.fk_control = ".$id." AND t.fk_cash=".$terminal." AND t.fk_statut > 0";
	
	$sql .= " UNION SELECT f.ref, f.type, fd.total_ht, fd.tva_tx, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.total_ttc";
	$sql .=" FROM ".MAIN_DB_PREFIX."pos_facture as pf,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."facturedet as fd on fd.fk_facture= f.rowid";
	$sql .=" WHERE pf.fk_control_cash = ".$id." AND pf.fk_cash=".$terminal." AND pf.fk_facture = f.rowid and f.fk_statut > 0";
	
	$result=$db->query($sql);
	
	if ($result)
	{
		$num = $db->num_rows($result);
		if($num>0)
		{
			$i = 0;
			$subtotalcardht=0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$i++;
				if($objp->type == 1){
					$objp->total_ht= $objp->total_ht * -1;
					$objp->total_tva= $objp->total_tva * -1;
					$objp->total_ttc= $objp->total_ttc * -1;
					$objp->total_localtax1= $objp->total_localtax1 * -1;
					$objp->total_localtax2= $objp->total_localtax2 * -1;
				}
				
				$subtotalcardht+=$objp->total_ht;
				$subtotalcardtva[$objp->tva_tx] += $objp->total_tva;
				$subtotalcardttc += $objp->total_ttc;
				$subtotalcardlt1 += $objp->total_localtax1;
				$subtotalcardlt2 += $objp->total_localtax2;
			}
		}
		
	}
	$message .= "------------------\n";
	if(! empty($subtotalcardht))$message .= $langs->trans("TotalHT")."\t".price($subtotalcardht)." ".$langs->trans(currency_name($conf->currency))."\n";
	if(! empty($subtotalcardtva)){
		foreach($subtotalcardtva as $tvakey => $tvaval){
			if($tvakey > 0)
				$message .= $langs->trans("TotalVAT").' '.round($tvakey).'%'."\t".price($tvaval)." ".$langs->trans(currency_name($conf->currency))."\n";
		}
	}
	if($subtotalcardlt1)
		$message .= $langs->transcountrynoentities("TotalLT1",$mysoc->country_code)."\t".price($subtotalcardlt1)." ".$langs->trans(currency_name($conf->currency))."\n";
	if($subtotalcardlt2)
		$message .= $langs->transcountrynoentities("TotalLT2",$mysoc->country_code)."\t".price($subtotalcardlt2)." ".$langs->trans(currency_name($conf->currency))."\n";
		
	$message .= $langs->trans("TotalPOS")."\t".price($subtotalcardttc)." ".$langs->trans(currency_name($conf->currency))."\n";
	*/
		return $message;
	}	
	
	
	/**
	 * Send mail with ticket data
	 * @param  $email
	 * @return int 			<0 if KO; >0 if OK
	 */
	public static function sendMail($email)
	{
		global $db,$conf,$langs;
		$function = "sendMail";
		$filename_list=array();
		$mimetype_list=array();
		$mimetype_list=array();

	
				
		require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
		if($email["idTicket"])
		{
			$ticket= new Ticket($db);
			$ticket->fetch($email["idTicket"]);
			$subject= $conf->global->MAIN_INFO_SOCIETE_NOM.': '.$langs->trans("CopyOfTicket").' '.$ticket->ticketnumber;
			$message ='DERMAGLOBAL';
            $ticket->generateDocument("azur",'',0,0,0,null);
            $x = trim(DOL_DOCUMENT_ROOT,'htdocs').'documents/'.$ticket->last_main_doc;
            $filename_list = array($x);
            $mimetype_list = array('application/pdf');
            $data= explode('/',$ticket->last_main_doc);
            $mimefilename_list = array($data[2]);
		}
		if($email["idFacture"])
		{
			$facture= new Facture($db);
			$facture->fetch($email["idFacture"]);
			$subject= $conf->global->MAIN_INFO_SOCIETE_NOM.': '.$langs->trans("CopyOfFacture").' '.$facture->ref;
			$message = self::FillMailFactureBody($facture->id);
		}
		if($email["idCloseCash"])
		{
			$subject= $conf->global->MAIN_INFO_SOCIETE_NOM.': '.$langs->trans("CopyOfCloseCash").' '.$email["idCloseCash"];
			$message = self::FillMailCloseCashBody($email["idCloseCash"]);
		}
		$from = $conf->global->MAIN_INFO_SOCIETE_NOM."<".$conf->global->MAIN_INFO_SOCIETE_MAIL.">";
			

			
		$mailfile = new CMailFile($subject,$email["mail_to"],$from,$message,$filename_list,$mimetype_list,$mimefilename_list);
		if ($mailfile->error)
		{
			$mesg='<div class="error">'.$mailfile->error.'</div>';
			$res = -1;
		}
		else
		{
			$res=$mailfile->sendfile();
			unlink($x);
		}

		return ErrorControl($res,$function);
	}
	
	/**
	 *	Delete ticket
	 *	@param     	int		$idTicket    Id of ticket to delete
	 *	@return		int					<0 if KO, >0 if OK
	 */
	public static function Delete_Ticket($idTicket)
	{
		global $db;
		
		$function = "deleteTicket";
	
		$object= new Ticket($db);
		$object->fetch($idTicket);
		$db->begin;
		$res=$object->delete_ticket();
	
		if ($res)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	
		return ErrorControl($res,$function);
	}	
	public static function calculePrice($product)
	{
		global $db, $mysoc;
		require_once (DOL_DOCUMENT_ROOT."/core/lib/price.lib.php");
		$qty = $product["cant"]>0? $product["cant"]:$product["cant"]*-1;
		if($product["price_base_type"] == "HT"){
			$pu = $product["price"]>0?$product["price"]:$product["price"]*-1;
		}
		else{
			$pu = $product["price_ttc"]>0?$product["price_ttc"]:$product["price_ttc"]*-1;
		}
		$remise_percent_ligne = $product["discount"]?$product["discount"]:0;
		$txtva = $product["tva_tx"];
		$uselocaltax1_rate = $product["localtax1_tx"] > 0?$product["localtax1_tx"]:0;
		$uselocaltax2_rate = $product["localtax2_tx"] > 0?$product["localtax2_tx"]:0;
		$remise_percent_global = $product["remise_percent_global"]?$product["remise_percent_global"]:0;
		$price_base_type = $product["price_base_type"];
		$type = $product["fk_product_type"]?$product["fk_product_type"]:0;
		$info_bits = 0;
		//$remise_percent_ligne = $remise_percent_global + $remise_percent_ligne;
		$remise_percent_ligne = $remise_percent_ligne;
		$remise_percent_global = 0;
		
		//$localtaxes_type=getLocalTaxesFromRate($txtva,0,$mysoc);
		
		$tabprice = calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $uselocaltax1_rate, $uselocaltax2_rate, $remise_percent_global, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type);

		$result["total_ht"]  = $tabprice[0];
		$result["total_tva"] = $tabprice[1];
		$result["total_ttc"] = $tabprice[2];
		$result["total_localtax1"] = $tabprice[9];
		$result["total_localtax2"] = $tabprice[10];
		$result["pu_ht"]  = $tabprice[3];
		$result["pu_tva"] = $tabprice[4];
		$result["pu_ttc"] = $tabprice[5];
		$result["total_ttc_without_discount"] = $tabprice[8];
		return $result;
	}
	public static function getLocalTax($data)
	{
		global $db;
		require_once (DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
		$customer = new Societe($db);
		$customer->fetch($data["customer"]);
		$localtax['1'] = get_localtax($data["tva"], 1,$customer);
		$localtax['2'] = get_localtax($data["tva"], 2,$customer);
		return $localtax;
	}
	
	public static function getNotes($mode)
	{
		global $db, $conf;
		
		$ret=-1;
		$function="GetNotes";
		if($mode){
			$sql = 'SELECT f.rowid as ticketid, f.ticketnumber, fd.description, f.note as ticketNote, fd.note as lineNote';
		}
		else{
			$sql = 'SELECT count(*)';
		}
		$sql.= ' FROM '.MAIN_DB_PREFIX.'pos_ticket as f';
		$sql.= ', '.MAIN_DB_PREFIX.'pos_ticketdet as fd';
		$sql.= ' WHERE f.fk_statut = 0';
		$sql.= ' AND f.rowid = fd.fk_ticket';
		$sql.= ' AND (f.note is not null';
		$sql.= ' OR fd.note is not null)';
		if($mode == 0){
			$sql .= 'GROUP BY f.ticketnumber';
		}
		
		$res = $db->query($sql);
		if($res)
		{
			$num = $db->num_rows($res);
			if($mode){
				$i = 0;
				$j = 0;
				$id=0; 
				while ($i < $num)
				{
					$obj = $db->fetch_object($res);
				
					if($id != $obj->ticketid){
						$id = $obj->ticketid;
						$tickets[$j]["id"] = $j;
						$tickets[$j]["ticketid"] = $obj->ticketid;
						$tickets[$j]["ticketnumber"] = $obj->ticketnumber;
						$tickets[$j]["description"] = '';
						$tickets[$j]["note"] = $obj->ticketNote?$obj->ticketNote:'';
						$j++;
					}
					if($obj->lineNote){
						$tickets[$j]["id"] = $j;
						$tickets[$j]["ticketid"] = $obj->ticketid;
						$tickets[$j]["ticketnumber"] = '';
						$tickets[$j]["description"] = $obj->description;
						$tickets[$j]["note"] = $obj->lineNote;
						$j++;
					}
					
					$i++;
				}
				return $tickets;
			}
			else {
				return $num;
			}	
			
		}
		else
		{
			return ErrorControl($ret, $function);
		}
	}
	 /**
	 *  Return list of all warehouses
	 *
	 *	@param	int		$status		Status
	 * 	@return array				Array list of warehouses
	 */
	function getWarehouse($status=1)
	{
		global $db;
		$liste = array();

		$sql = "SELECT rowid, lieu";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
		$sql.= " WHERE entity IN (".getEntity('warehouse', 1).")";
		$sql.= " AND statut = ".$status;

		$result = $db->query($sql);
		$i = 0;
		$num = $db->num_rows($result);
		if ( $result )
		{
			while ($i < $num)
			{
				$row = $db->fetch_row($result);
				$liste[$i]["id"] = $row[0];
				$liste[$i]["lieu"] = $row[1];
				$i++;
			}
			$db->free($result);
		}
		return $liste;
	}
	
	/**
	 * 	Reconstruit l'arborescence des categories sous la forme d'un tableau
	 *	Renvoi un tableau de tableau('id','id_mere',...) trie selon arbre et avec:
	 *				id = id de la categorie
	 *				id_mere = id de la categorie mere
	 *				id_children = tableau des id enfant
	 *				label = nom de la categorie
	 *				fulllabel = nom avec chemin complet de la categorie
	 *				fullpath = chemin complet compose des id
	 *
	 *	@param      string	$type		      Type of categories (0=product, 1=suppliers, 2=customers, 3=members)
	 *  @param      int		$markafterid      Mark all categories after this leaf in category tree.
	 *	@return		array		      		  Array of categories
	 */
	function get_full_arbo($type)
	{
		global $db,$conf;
		
		$categorie = new Categorie($db);
		
		$categorie->cats = array();
	
		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label, c.description, c.fk_parent";	// Distinct reduce pb with old tables with duplicates
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " WHERE c.entity IN (".getEntity('category',1).")";
		$sql.= " AND c.type = ".$type;
		$sql.= " AND fk_parent = 0";
			
		dol_syslog(get_class($categorie)."::get_full_arbo get category list sql=".$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj = $db->fetch_object($resql))
			{
				$categorie->cats[$obj->rowid]['rowid'] = $obj->rowid;
				$categorie->cats[$obj->rowid]['id'] = $obj->rowid;
				$categorie->cats[$obj->rowid]['fk_parent'] = $obj->fk_parent;
				$categorie->cats[$obj->rowid]['label'] = $obj->label;
				$categorie->cats[$obj->rowid]['description'] = $obj->description;
				$i++;
			}
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	
		// We add the fullpath property to each elements of first level (no parent exists)
		dol_syslog(get_class($categorie)."::get_full_arbo call to build_path_from_id_categ", LOG_DEBUG);
		foreach($categorie->cats as $key => $val)
		{
			$categorie->build_path_from_id_categ($key,0);	// Process a branch from the root category key (this category has no parent)
		}
	
		dol_syslog(get_class($categorie)."::get_full_arbo dol_sort_array", LOG_DEBUG);
		$categorie->cats=dol_sort_array($categorie->cats, 'fulllabel', 'asc', true, false);
	
		//$this->debug_cats();
	
		return $categorie->cats;
	}
	
	/**
	 * 	Return list of contents of a category
	 *
	 * 	@param	string	$field				Field name for select in table. Full field name will be fk_field.
	 * 	@param	string	$classname			PHP Class of object to store entity
	 * 	@param	string	$category_table		Table name for select in table. Full table name will be PREFIX_categorie_table.
	 *	@param	string	$object_table		Table name for select in table. Full table name will be PREFIX_table.
	 *	@return	void
	 */
	function get_prod($idCat,$more, $ticketstate)
	{
		global $db,$conf;
		$objs = array();
			
		$sql = "SELECT o.rowid as id, o.ref, o.label, o.description, ";
		$sql .=" o.fk_product_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_product as c";
		$sql.= ", ".MAIN_DB_PREFIX."product as o";
		if($conf->global->POS_STOCK || $ticketstate == 1){
			$sql.= " WHERE o.entity IN (".getEntity("product", 1).")";
			$sql.= " AND c.fk_categorie = ".$idCat;
			$sql.= " AND c.fk_product = o.rowid";
			$sql.= " AND o.tosell = 1";
			if(!$conf->global->POS_SERVICES){
				$sql .= " AND o.fk_product_type = 0";
			}
		}
		else 
		{
			$cashid = $_SESSION['TERMINAL_ID'];
			$cash = new Cash($db);
			$cash->fetch($cashid);
			$warehouse = $cash->fk_warehouse;
					
			$sql .= ", ".MAIN_DB_PREFIX."product_stock as ps";
			$sql .= " WHERE o.entity IN (".getEntity("product", 1).")";
			$sql .= " AND c.fk_categorie = ".$idCat;
			$sql .= " AND c.fk_product = o.rowid";
			$sql .= " AND o.tosell = 1";
			$sql .= " AND o.rowid = ps.fk_product";
			$sql .= " AND ps.fk_entrepot = ".$warehouse;
			$sql .= " AND ps.reel > 0";
			if($conf->global->POS_SERVICES){
				$sql .= " union select o.rowid as id, o.ref, o.label, o.description,	";
				$sql .= " o.fk_product_type";
				$sql .= " FROM ".MAIN_DB_PREFIX."categorie_product as c,";
				$sql .= MAIN_DB_PREFIX."product as o";
				$sql .= " where c.fk_categorie = ".$idCat;
				$sql .= " AND c.fk_product = o.rowid";
				$sql .= " AND o.tosell = 1";
				$sql .=" AND fk_product_type=1";
			}
		}
		if($more >= 0)
			$sql.=" LIMIT ".$more.",10 ";
		
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
			
				$objs[$objp->id]["id"] = $objp->id;
				$objs[$objp->id]["ref"] = $objp->ref;
				$objs[$objp->id]["label"] = $objp->label;
				$objs[$objp->id]["description"] = $objp->description;
				$objs[$objp->id]["type"] = $objp->fk_product_type;
				
				$objs[$objp->id]["image"] = self::getImageProduct($objp->id, false);
				$objs[$objp->id]["thumb"] = self::getImageProduct($objp->id, true);
				$i++;
			}
			return $objs;
		}
		else
		{
			return -1;
		}
	}
	function checkPassword($login,$password){
		dol_include_once('/pos/class/auth.class.php');
		$function = "checkPassword";
		
		$auth = new Auth($db);
		$res = $auth->verif ($login, $password);
		
		return ErrorControl($res,$function);
	}
	function searchCoupon($customerId,$amount=999999999){
		global $db;
		
		$sql = "SELECT rc.rowid, rc.amount_ttc,";
		$sql.= "  rc.description";
		$sql.= " FROM  ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql.= " WHERE rc.fk_soc =". $customerId;
		//$sql.= " AND rc.description LIKE '_TR%'";
		$sql.= " AND (rc.fk_facture_line IS NULL AND rc.fk_facture IS NULL AND rc.fk_ticket IS NULL) ";
		//$sql.= " AND rc.amount_ttc <= {$amount}";
		$sql.= " ORDER BY rc.datec DESC";
		
		$resql=$db->query($sql);
		if ($resql)
		{
			$i = 0 ;
			$num = $db->num_rows($resql);
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$coupon[$i]['id'] 			= $obj->rowid;
				$coupon[$i]['amount_ttc'] 	= $obj->amount_ttc;
				$coupon[$i]['description'] 	= $obj->description; 
				
				$i++;
			}
		}
		return $coupon;
	}
	function addPrint($addprint){
		require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
		global $db,$conf;
				
		$res = dolibarr_set_const($db,"POS_PENDING_PRINT", $conf->global->POS_PENDING_PRINT.$addprint.',','chaine',0,'');
		
		return $res;
	}
	
	function setWarehouseStauts($ticket,$warehouse,$data,$status=null,$fromask=false)
	{
		global $db,$user,$conf;
		$static_ticket = new Ticket($db);
		$static_ticket ->fetch($ticket);

		$return = array('status'=>'OK','errors'=>array());

		foreach($data as $product)
		{
			if(		!isset($product['prod_id'])
				||	!is_numeric($product['prod_id'])
				||	 $product['checked'] != '1'
				)
			{
				continue;
			}
			$sta = $status;//$product['checked'] == '1' ? $status : 'NULL'; 
			if ($status === null)
			{
				if (self::hasStock($product['prod_id'],$product['qty'],$warehouse))
				{
					$sta = 1;
				}
				else
				{
					$sta = 4;
				}
			}
			$sql =	 'UPDATE `'.MAIN_DB_PREFIX.'pos_ticketdet`'."\r\n";

			if ($fromask)
			{
				$sql1 =	 'SELECT ls_warehouse_status,fk_product,qty,qty_ent  '
						.'FROM llx_pos_ticketdet '
						.'WHERE fk_ticket = '.$ticket.' '
						.'  AND fk_product = '.$product['prod_id'].' '
						;
				if ($res = $db->query($sql1))
				{
					while($evRow = $db->fetch_object($res))
					{
						if (is_null($evRow->ls_warehouse_status))
						{
							$var = 'POS_ALMACEN_ON_ASK_N';
						}
						else
						{
							$var = 'POS_ALMACEN_ON_ASK_'.$evRow->ls_warehouse_status;
						}
						
						if (isset($conf->global->$var))
						{
							$sta = $conf->global->$var;
							$status = $conf->global->$var;
						}
					}
				}
			}

			$file = DOL_DOCUMENT_ROOT.'/../documents/pos/'.$static_ticket->ref.'/'.$static_ticket->ref.'.pdf';
			//die(var_dump($file,$sta,$static_ticket->diff_payment,$static_ticket->total_ttc));

			if (
					$sta == 14
				&&	(
							file_exists($file)
						||	$static_ticket->diff_payment <> $static_ticket->total_ttc
					)
				)
			{
				continue;
			}

			$sql .= 'SET `ls_warehouse_status`= '.$sta."\r\n";
			
			$sql .=	 '   ,`ls_warehouse_status_by`='.$user->id."\r\n"
					.'   ,`ls_warehouse_status_date`=\''.gmdate('Y-m-d H:i:s').'\''
					.'WHERE `fk_ticket` = '.$ticket."\r\n"
					.'  AND `fk_product` = '.$product['prod_id']."\r\n"
					;
			//die($sql);
			if(intval(($status) === 0 || intval($status) === 1) && !$fromask)
			{
				$sql .=	 '  AND (`ls_warehouse_status` IS NULL OR `ls_warehouse_status`<='.$sta.' OR `ls_warehouse_status` = 10) '."\r\n";
			}
			elseif(intval($status) === 7)
			{
				$sql .=	 '  AND (`ls_warehouse_status` IS NULL OR `ls_warehouse_status` NOT IN (2,3,4)) '."\r\n";
			}
			//die($sql);
			if (!$db->query($sql))
			{
				$return=false;
			}
			else
			{
				$return= true;
			}
		}
		return $return;
	}
	
	function setEntrepotUserToTicket($ticket,$warehouse)
	{
		global $db,$user;
		if(!is_numeric($ticket) || $ticket < 1)
		{
			return -1;
		}
		$sql =	 'SELECT t.fk_entrepot_user'."\r\n"
				.'FROM llx_pos_ticket AS t'."\r\n"
				.'WHERE t.rowid = '.$ticket."\r\n"
				;
		if (!$res = $db->query($sql))
		{
			return -2;
		}
		if (!$dpt = $db->fetch_object($res))
		{
			return -3;
		}
		if ($dpt->fk_entrepot_user > 0)
		{
			return (-9999990000000000-$dpt->fk_entrepot_user);
		}
		$sql =	 'SELECT u.rowid, SUM(td.qty) AS total,u.fk_warehouse'."\r\n"
				.'FROM llx_user AS u'."\r\n"
				.'LEFT JOIN llx_usergroup_user AS ugu'."\r\n"
				.'  ON u.rowid = ugu.fk_user'."\r\n"
				.'LEFT JOIN llx_pos_ticket AS t'."\r\n"
				.'	ON u.rowid = t.fk_entrepot_user'."\r\n"
				.'LEFT JOIN llx_pos_ticketdet AS td'."\r\n"
				.'  ON td.fk_ticket = t.rowid'."\r\n"
				.'WHERE ugu.fk_usergroup = 1'."\r\n"
				.'  AND u.fk_warehouse = \''.$warehouse."'\r\n"
				.'GROUP BY u.rowid'."\r\n"
				.'ORDER BY total ASC'."\r\n"
				.'LIMIT 0,1'
				;
		if (!$res = $db->query($sql))
		{
			return -4;
		}
		if(!$usr = $db->fetch_object($res))
		{
			return -5;
		}
		$sql =   'UPDATE llx_pos_ticket'."\r\n"
				.'SET fk_entrepot_user='.$usr->rowid.''."\r\n"
				.'WHERE rowid='.$ticket.''."\r\n"
				;
		if (!$db->query($sql))
		{
			return -6;
		}
		return $usr->rowid;
	}
	
	function setAutoWarehouseStatusOnSave2($ticket_id)
	{
		global $db,$user,$conf;
		$ticket = new Ticket($db);
		$ticket->fetch($ticket_id);
		
		$isOrder = false;
		if(strlen($ticket->ref) > 8 && strpos($ticket->ref,'(PROV')===false && strpos(substr($ticket->ref,0,8),'-')===false)
		{
			$isOrder = true;
		}
		if ($ticket->statut == 1 && !$isOrder && ($ticket->paye==0 || $ticket->diff_payment > 0))
		{
			if ($ticket->remise_absolute == 0 && $ticket->remise_percent == 0)
			{
				$ticket->statut = 2;
			}
		}
		$delivered = 0;
#		$credito = true;
#		if ($ticket->socid != 8 && $ticket->socid > 0)
#		{
#			if (!class_exists('Client'))
#			{
#				include_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';			
#			}
#			$static_soc = new Client($db);
#			$static_soc->fetch($ticket->socid);
#			if (!$static_soc->outstanding_limit || $static_soc->typent_id == 234 || !$static_soc->typent_id)
#			{
#				$credito = false;
#			}
#		}
		$credito = self::isCredit($ticket_id);
		
		
		$cust = ((!$credito || $ticket->socid == 8) && $ticket->diff_payment > 0 /*$ticket->total_ttc*/) ? '8':'0';
		foreach($ticket->lines as $line)
		{
			dol_syslog("    LINE {$line->ls_warehouse_status}");
			if (empty($line->ls_warehouse_status) && ($line->ls_warehouse_status !== 0 || $line->ls_warehouse_status !== '0'))
			{
				$line->ls_warehouse_status = 'N';
			}
			$stk = (self::hadStock1($ticket_id,$line->fk_product,$line->qty_ent,$ticket->getEntrepot()->id)) ? '1':'0';
			$constName = 'POS_ALMACEN_AUTO_'
						.$line->ls_warehouse_status.'_'
						.$ticket->type.'_'
						.$ticket->statut.'_'
						.$cust.'_'
						.$stk
						;
			
			if (property_exists($conf->global,$constName))
			{
				if ($conf->global->$constName != '-')
				{
					$line->updateEstadoV($conf->global->$constName);
					if($conf->global->$constName == '6')
					{
						$delivered++;
					}
				}
				elseif($line->ls_warehouse_status == 6 || $line->ls_warehouse_status == 15)
				{
					$delivered++;
				}
			}
		}
		if ($isOrder)
		{
			$ticket->statut = 1;
			$ticket->update($user->id);
		}
		else
		{
			if ($ticket->statut != 0)
			{
			if ($delivered != count($ticket->lines))
			{
				if ($credito)
				{
					$ticket->statut = 2;
					$ticket->update($user->id);
				}
				else
				{
					$ticket->statut = 2;
					$ticket->update($user->id);
				}
			}
			else
			{
				if ($credito)
				{
					$ticket->statut = 1;
					$ticket->update($user->id);
				}
				else
				{
					if ($ticket->diff_payment > 0)
					{
						$ticket->statut = 2;
						$ticket->update($user->id);
					}
					else
					{
						$ticket->statut = 1;
						$ticket->update($user->id);
					}
				}
			}
			}
		}
#		if (!$isOrder && $delivered == count($ticket->lines) && $credito)
#		{
#			$ticket->statut = 1;
#			$ticket->update($user->id);
#		}
#		elseif (!$isOrder && $ticket->statut == 1 && $delivered != count($ticket->lines))
#		{
#			$ticket->statut = 2;
#			$ticket->update($user->id);
#		}
#		elseif($isOrder)
#		{
#		}
	}
	
	function hasStock($prod_id,$qty,$warehouse)
	{
		if (!class_exists('Product'))
		{
			require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		}
		global $db,$user;
		$prod = new Product($db);
		$prod->fetch($prod_id);
		$prod->load_stock();
		if (isset($prod->stock_warehouse[$warehouse]->real) && $prod->stock_warehouse[$warehouse]->real >= $qty)
		{
			return true;
		}
		else
		{
			
			return false;
		}
	}
	
	function hadStock($ticket_id,$prod_id,$qty,$warehouse)
	{
		if (!class_exists('Product'))
		{
			require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		}
		$objs = self::getRelatedObjects($ticket_id);
		//die (var_dump($objs));
		
	}

	function hadStock1($ticket_id,$prod_id,$qty,$warehouse)
	{
		if (!class_exists('Product'))
		{
			require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		}
		global $db,$user;
		$prod = new Product($db);
		$prod->fetch($prod_id);
		$prod->load_stock();
		$currStock = (!isset($prod->stock_warehouse[$warehouse])||is_null($prod->stock_warehouse[$warehouse]->real))?0:$prod->stock_warehouse[$warehouse]->real;
		if ($currStock >= $qty)
		{
			return true;
		}
		elseif(!($currStock <= (-1 * $qty)))
		{
			$sql =	 'SELECT `rowid` '
					.'FROM llx_stock_mouvement '
					.'WHERE `fk_product` = '.$prod_id.' '."\r\n"
					.'  AND `fk_origin` = '.$ticket_id."\r\n"
					.'  AND `origintype` = \'ticket\''."\r\n"
					;
			if (!$res = $db->query($sql))
			{
				dol_print_error($db);
				die();
			}
			$return = false;
			while ($db->fetch_object($res))
			{
				$return = true;
			}
			return $return;
		}
		else
		{
			
			$sql =	 'SELECT `rowid`,`fk_origin`,`origintype` '
					.'FROM llx_stock_mouvement '
					.'WHERE `fk_product` = '.$prod_id.' '."\r\n"
					//.'  AND `fk_origin` = '.$ticket_id."\r\n"
					//.'  AND `origintype` = \'ticket\''."\r\n"
					.'  AND `fk_entrepot` = '.$warehouse.' '."\r\n"
					.' ORDER BY rowid DESC'
					;
			if (!$res = $db->query($sql))
			{
				dol_print_error($db);
				die();
			}
			$return = false;
			while ($db->fetch_object($res))
			{
				$return = true;
			}
			return $return;
		}
	}
	
	public static function getRelatedObjects($id)
	{
		static $objects = array();
		global $db;
		if (!isset($objects[$id]))
		{
			$static_ticket = new Ticket($db);
			$static_ticket->fetch($id);
			$objects[$id] = array(
								 'commande'	=> self::_getRelatedCommandes($id)
								,'order'	=> self::_getRelatedOrders($id,$static_ticket->ref)
								,'ticket'	=> $static_ticket
							);
		}
		return ($objects[$id]);
	}
	
	private static function _getRelatedOrders($id)
	{
		static $return = array();
		global $db;
		if (!isset($return[$id]))
		{
			$return[$id] = array();
			$sql =	 'SELECT rowid '
					.'FROM llx_pos_ticket '
					.'WHERE note LIKE \'%'.$ref.'%\''
				;
			if ($res = $db->query($sql))
			{
				$rows = array();
				while($row = $db->fetch_object($res))
				{
					$static_ticket = new Ticket($db);
					$static_ticket->fetch($row->rowid);
					$rows[$static_ticket->id] = $static_ticket;
				}
				$return[$id] = $rows;
			}
		}
		return $return[$id];
	}
	
	private static function _getRelatedCommandes($id)
	{
		static $return = array();
		global $db;
		if (!isset($return[$id]))
		{
			$return[$id] = array();
			if (!class_exists('Commande'))
			{
				require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
			}
			$sql =	 'SELECT fk_target '
					.'FROM llx_element_element '
					.'WHERE sourcetype =\'ticket\' '
					.'  AND targettype =\'commande\' '
					.'  AND fk_source = '.$id
					;
			if ($res = $db->query($sql))
			{
				$rows = array();
				while($row = $db->fetch_object($res))
				{
					$static_commande = new Commande($db);
					$static_commande->fetch($row->fk_target);
					$rows[$row->fk_target] = $static_commande;
				}
				$return[$id] = $rows;
			}
		}
		return $return[$id];
	}
	
	private static function _getEstadovArray($type)
	{
		static $max = null;
		static $ev = array(
							 'label' => array(
												 'En&nbsp;Almacén'			#  0
												,'Por&nbsp;Revisar'			#  1
												//,'Reportado'			#  2
												//,'Revisado'				#  3
												,'Por&nbsp;Surtir'			#  4
												,'Surtido'			#  5
												,'Surtido*'				#  6
												,'Apartados'			#  7
												,'Devolución'			#  8
												,'En&nbspAlmacén*'				#  9
												,'Apartados&nbsp;Remotos'	# 10
												,'Por&nbsp;Apartar'			# 11
												,'N/A'					# 12
												,'Por&nbsp;Apartar*'	# 13
												,'Ticket&nbsp;C.' #15
												,'Recoger&nbsp;de&nbsp;Mostrador' #14
												)
		 					,'constant'=> array(
												 'EN_ALMACEN'			#  0
												,'POR_REVISAR'			#  1
												//,'REPORTADO'			#  2
												//,'REVISADO'				#  3
												,'POR_SURTIR'			#  4
												,'ENTREGADO'			#  5
												,'SURTIDO'				#  6
												,'APARTADOS'			#  7
												,'DEVOLUCION'			#  8
												,'RETORNO'				#  9
												,'APARTADOSR'			#  10
												,'PORAPARTAR'			#  11
												,'NO_SURTIDO'			#  12
												,'RETIRO_APARTADO'		#  13
												,'TICKET_C'				#  15
												,'RECOGER_MOSTRADOR'	#  14
												)
		 					,'class'	=>	array(
												 'en_almacen'			#  0
												,'por_revisar'			#  1
												//,'reportado'			#  2
												//,'revisado'				#  3
												,'por_surtir'			#  4
												,'entregado'			#  5
												,'surtido'				#  6
												,'apartados'			#  7
												,'devolucion'			#  8
												,'retorno'				#  9
												,'apartadosr'			# 10
												,'porapartar'			# 11
												,'no_surtido'			# 12
												,'retiro_apartado'		# 13
												,'ticket_c'				# 15
												,'recoger_mostrador'	# 14
												)
		 					,'background' => array(
												 '#C0C0C0'				#  0
												,'#929292'				#  1
												//,'#EE220C'				#  2
												//,'#B51700'				#  3
												,'#0076BA'				#  4
												,'#1DB100'				#  5
												,'#1DB100'				#  6
												,'#FF9300'				#  7
												,'#F8BA00'				#  8
												,'#c0c0c0'				#  9
												,'#FFFF66'				# 10
												,'#B342F5'				# 11
												,'#A3B8F9'				# 12
												,'#B342F5'				# 13
												,'#cccccc'				# 15
												,'#F8BA00'				# 14
												)
		 					,'color'	=>	array(
							 					 '#FFFFFF'				#  0
		 										,'#FFFFFF'				#  1
		 										//,'#FFFFFF'				#  2
		 										//,'#FFFFFF'				#  3
		 										,'#FFFFFF'				#  4
		 										,'#FFFFFF'				#  5
		 										,'#FFFFFF'				#  6
		 										,'#FFFFFF'				#  7
		 										,'#FFFFFF'				#  8
		 										,'#FFFFFF'				#  9
		 										,'#000000'				# 10
		 										,'#FFFFFF'				# 11
		 										,'#FFFFFF'				# 12
		 										,'#FFFFFF'				# 13
		 										,'#000000'				# 15
		 										,'#FFFFFF'				# 14
												 )
		 					,'numeric'	=> array(
							 					0
												,1
												//,2
												//,3
												,4
												,5
												,6
												,7
												,8
												,9
												,10
												,11
												,12
												,13
												,15
												,14
							 					)
											);
		if (is_null($max))
		{
			foreach($ev as $evArr)
			{
				if (is_array($evArr) && count($evArr) && (is_null($max) || count($evArr)>$max))
				{
					$max = count($evArr);
				}
			}
		}
					
		if (!array_key_exists($type,$ev))
		{
			$type = 'numeric'; 
		}
		return $ev[$type];
	}
	
	public static function getEstadovArray($key='numeric',$value='label')
	{
		static $ev = array();
		if (!array_key_exists($key.'|'.$value,$ev))
		{
			$keys = self::_getEstadovArray($key);
			$vals = self::_getEstadovArray($value);
			for($i=0,$n=count($keys);$i<$n;$i++)
			{
				$ev[$key.'|'.$value][$keys[$i]] = $vals[$i];  
			}
		}
		return $ev[$key.'|'.$value];
	}
	
	public static function getEstadovStyle()
	{
		static $return = null;
		if (is_null($return))
		{
			$class	= self::getEstadovArray(null,'constant');
			$color	= self::getEstadovArray('constant','color');
			$back	= self::getEstadovArray('constant','background');
			$css	= self::getEstadovArray('constant','class');
			$return = "\r\n";
			foreach($class as $cls)
			{
				$return .=	 "\t.{$cls}{color:{$color[$cls]};background-color:{$back[$cls]};}\r\n";
				$return .=	 "\t.{$css[$cls]}, option.{$css[$cls]}:checked, a.{$css[$cls]} "
							."{color:{$color[$cls]}!important;background-color:{$back[$cls]};}\r\n"
							;
			}
		}
		return $return;
	}
	/**
 	*  Return customer info
 	*  
 	*  @param 		string	$idSearch		Part of code, name, firstname, idprof1
 	*  @param		boolean	$extended		Return more info
 	*  @return      array					Customer info
 	*/
	public static function GetCustomer($id,$extended=false)
	{
		global $db, $conf;
		
		$ret=-1;
		$function="GetCustomer";
#		if(dol_strlen($idSearch) == 0)
#		    return self::getAllCustomers(false);
#		else if(dol_strlen($idSearch) <= $conf->global->COMPANY_USE_SEARCH_TO_SELECT)
#			return ErrorControl(-2,$function);

#		$prefix=empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE)?'%':'';	// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
		
		$i=0;
		
		$sql = "SELECT c.rowid, c.nom, c.code_client, c.siren, c.remise_client,c.fk_typent";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as c";
		$sql.= " WHERE c.client = 1";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND (c.rowid ='".$db->escape(trim($id))."' ";	
		$sql.= ")";
#		$sql.= " ORDER BY c.nom";

		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$soc = new Societe($db);
			unset($ret);
			
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$ret[$i]['points'] = null;
				if($conf->global->REWARDS_POS && ! empty($conf->rewards->enabled)){
					$rew= new Rewards($db);
					$res = $rew->getCustomerReward($objp->rowid);
					if($res){
						$ret[$i]['points'] = $rew->getCustomerPoints($objp->rowid);
					}
				}
				$soc->fetch($objp->rowid);
				$ret[$i]["coupon"] = $soc->getAvailableDiscounts();
				$ret[$i]["id"] = $objp->rowid;
				$ret[$i]["nom"] = $objp->nom;
				$ret[$i]["profid1"] = $objp->siren;
				$ret[$i]["remise"] = $objp->remise_client;
                $ret[$i]['limite']= ($soc->outstanding_limit && $soc->typent_code=='TE_CREDI')?$soc->outstanding_limit:0;
                $sqlProyect = "SELECT title as proyect_t, rowid as proyect_id,budget_amount as presupuesto FROM ".MAIN_DB_PREFIX."projet WHERE fk_soc = ".$soc->id." AND fk_statut = 1";
                $resqlProyect=$db->query($sqlProyect);
                if($db->num_rows($resqlProyect) > 0) {
                    $numProyects = $db->num_rows($resqlProyect);
                    $iterator = 0;
                    while ($iterator < $numProyects) {
                        $proyecto = $db->fetch_object($resqlProyect);
                        $ret[$i]['proyectos'][$proyecto->proyect_id]["proyect"] = $proyecto->proyect_t;
                        $ret[$i]['proyectos'][$proyecto->proyect_id]["proyect_id"] = $proyecto->proyect_id;
                        $ret[$i]["proyectos"][$proyecto->proyect_id]["presupuesto"] = price2num($proyecto->presupuesto);
                        //Gastos y cuentas
                        $sqlGastos = "SELECT SUM(pv.amount) as gastos,SUM(ff.total_ttc)-SUM(pf.amount) as cuentas";
                        $sqlGastos.= " FROM ".MAIN_DB_PREFIX."payment_various as pv";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn AS ff on ff.fk_projet = pv.fk_projet";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn AS pffp ON ff.rowid=pffp.fk_facturefourn";
                        $sqlGastos.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn AS pf ON pffp.fk_paiementfourn=pf.rowid";
                        $sqlGastos.= " WHERE pv.fk_projet = ".$proyecto->proyect_id;
                        $resGastos=$db->query($sqlGastos);
                        $numeroGastos=$db->num_rows($resGastos);
                        if($numeroGastos > 0)
                        {
                            $gastos=$db->fetch_object($resGastos);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["gastos"] = $gastos->gastos == null ? 0:price2num($gastos->gastos);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["cuentas"] = $gastos->cuentas == null? 0:price2num($gastos->cuentas);
                        }
                        else
                        {
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["gastos"] = 0;
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["cuentas"] = 0;
                        }
                        //Por pagar
                        $sqlPagar= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
                        $sqlPagar.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                        $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                        $sqlPagar.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                        $sqlPagar.=" WHERE f.fk_projet=".$proyecto->proyect_id." AND f.fk_statut=1";
                        $resqlPa = $db->query($sqlPagar);
                        $numeroPagar = $db->num_rows($resqlPa);
                        if($numeroPagar>0) {
                            $ppagar = $db->fetch_object($resqlPa);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["por_pagar"]= $ppagar->total_pagar==null?0:$ppagar->total_pagar;
                        }
                        else
                            $ret[$i]["proyectos"][$proyecto->proyect_id]['por_pagar']=0;
                        $sqlTicket=  "SELECT SUM(t.total_ttc) as tickets "
									."FROM ".MAIN_DB_PREFIX."pos_ticket as t "
									."WHERE fk_soc=".$objp->rowid." "
									."and (t.type <>1 and (t.fk_statut=1 or t.fk_statut=2)) "
									."AND fk_projet={$proyecto->proyect_id}";
                        $resqlTi = $db->query($sqlTicket);
                        $numeroTi = $db->num_rows($resqlTi);
                        //die(var_dump($sqlTicket));
                        if($numeroTi>0) {
                            $tticket = $db->fetch_object($resqlTi);
                            $ret[$i]["proyectos"][$proyecto->proyect_id]["por_pagar"]+= is_null($tticket->tickets)?0:$tticket->tickets;
                        //die(var_dump($ret[$i]));
                        }
                        $iterator++;
                    }
                }
                else{
                    $ret[$i]['proyectos'][0]["proyect"] = "&nbsp";
                    $ret[$i]['proyectos'][0]["proyect_id"] = 0;
                    $ret[$i]["proyectos"][0]["presupuesto"] = 0;
                    $ret[$i]["proyectos"][0]["gastos"] = 0;
                    $ret[$i]["proyectos"][0]["cuentas"] = 0;
                    $ret[$i]["proyectos"][0]['por_pagar']=0;
                }
                //Limite de credito y cuentas por pagar
                //Cuentas por pagar
                $sqlpp= "SELECT SUM(f.total_ttc)-SUM(p.amount) as total_pagar";
                $sqlpp.=" FROM ".MAIN_DB_PREFIX."facture AS f";
                $sqlpp.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture=f.rowid";
                $sqlpp.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement AS p ON p.rowid = pf.fk_paiement";
                $sqlpp.=" WHERE f.fk_soc=".$objp->rowid;
                $resqlpp = $db->query($sqlpp);
                if($db->num_rows($resqlpp) > 0) {
                    $ppagar = $db->fetch_object($resqlpp);
                    //$ret[$i]['por_pagar']= $ppagar->total_pagar==null?0:$ppagar->total_pagar;
                }
                else
                    $ret[$i]['por_pagar']=0;
                $sqlTicket=  "SELECT SUM(t.total_ttc) as tickets "
							."FROM ".MAIN_DB_PREFIX."pos_ticket as t "
							."WHERE fk_soc=".$objp->rowid." "
							."and (t.type <>1 and t.fk_statut=1 or t.fk_statut=2)";
                $resqlTi = $db->query($sqlTicket);
                $numeroTi = $db->num_rows($resqlTi);
                if($numeroTi>0) {
                    $tticket = $db->fetch_object($resqlTi);
                    //$ret[$i]['por_pagar']+= $tticket->tickets==null?0:$tticket->tickets;

					require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
					$static_customer = new Client($db);
					$static_customer->fetch($objp->rowid);
			        $tmp = $static_customer->getOutstandingBills();
        			$pos = $static_customer->getOutstandingTickets();
        			$pedidos = $static_customer->getOutstandingOrders();
        			$ret[$i]['por_pagar']+= intval($tmp['opened']+$pos['opened']+$pedidos['opened']);

                }
                
				$i++;
			}		
		}
		return $ret;
	}
	public static function isCredit($ticket_id)
	{
		global $db;
		$ticket_id = intval($ticket_id);
		if ($ticket_id > 0)
		{
			$object = new Ticket($db);
			if ($object->fetch($ticket_id))
			{
				$cliente = POS::GetCustomer($object->socid);

				if (isset($object->fk_project) && intval($object->fk_project) > 0)
				{
					if (   isset($cliente[0]['proyectos'][$object->fk_project]['presupuesto']) 
						&& intval($cliente[0]['proyectos'][$object->fk_project]['presupuesto'])>0
					)
					{
						$return = true;
					}
					else
					{
						$return = false;
					}
					
				}
				else
				{
					if (isset($cliente[0]['limite']) && $cliente[0]['limite']>0)
					{
						$return = true;
					}
					else
					{
						$return = false;
					}
				}
				
			}
			else
			{
				$return = false;
			}
		}
		else
		{
			$return = false;
		}
		return $return;
	}
	
	public static function addCouponN($aryTicket)
	{
		if (	isset($aryTicket['data']['id']) && $aryTicket['data']['id'] > 0
			&&	isset($aryTicket['data']['idCoupon']) && $aryTicket['data']['idCoupon'] > 0
			&&	$aryTicket['data']['customerpay5'] != 0
			)
		{
				$couponTicket = self::LoadTicket($aryTicket['data']['idCoupon']);
				$couponTicket['data']['customerpay'] = $aryTicket['data']['customerpay5'];
				$couponTicket['data']['customerpay1'] = $aryTicket['data']['customerpay5'];
				self::SetTicket($couponTicket);
				
		}
	}
	
	public static function addCoupon($aryTicket)
	{
		return;
		global $db, $langs;
		
		$paiement_id = 0;
		require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
		$now=dol_now();
		$userstatic=new User($db);
		if(! $aryTicket['employeeId'])
		{
			$employee=$_SESSION['uid'];
		
		}
		else
		{
			$employee=$aryTicket['employeeId'];
		}
		$userstatic->fetch($employee);

		$cash = new Cash($db);
		
		$split = self::splitAbsoluteDiscount($aryTicket['data']['idCoupon'],$aryTicket['data']['customerpay5']);
		if (!is_array($split))
		{
			return false;
		}
		$terminal = $_SESSION['TERMINAL_ID'];
		$cash->fetch($terminal);
		
		//echo ("Aplicaondo pago por {$aryTicket['data']['customerpay5']} al ticket {$split['ticket_ref']}(id:{$split['ticket_id']})");
			
		$payment = new Payment($db);
		$payment->datepaye=$now;
		$payment->bank_account=$cash->fk_modepaybank_extra_3;
		$payment->amounts[$split['ticket_id']]=$aryTicket['data']['customerpay5'] * -1 ;
		//$payment->amounts[$aryTicket['data']['idCoupon']]=-1*$aryTicket['data']['customerpay5'];
		
		$payment->note=$langs->trans("Payment").' '.$langs->trans("Con Ticket de Regalo").' '.$aryTicket['ref'] ;
		$payment->paiementid=$cash->fk_paybank_extra_3;
		$payment->num_paiement='';
		//$payment->fk_paiement = '';
		
		if($aryTicket['data']['customerpay5'] != 0)
		{
			$paiement_id = $payment->create($userstatic,$soc);
			
			if ($paiement_id > 0)
			{
				$result=$payment->addPaymentToBank($userstatic,'payment','(CustomerFacturePayment)',$payment->bank_account,$aryTicket['data']['customerId'],'','1');
				if (! ($result > 0))
				{
					var_dump($_SESSION['TERMINAL_ID'],$cash);;
					
					die ('errorxx '.$payment->error.' '.$paiement_id);
					$error++;
				}
			}
			else
			{
				die ($payment->error);
				$error++;
			}
		}
		
		

	}
	
	public static function splitAbsoluteDiscount($discountId,$first_ammount)
	{
		global $db,$user;
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$error = 0;
		$return = false;
		$remid = $discountId;
		$discount = new DiscountAbsolute($db);
		$res = $discount->fetch($remid);

		if ($discount->amount_ttc <= $first_ammount)
		{
			if (preg_match('/\(([0-9]{1,})\)$/si',$discount->description,$MATCHES))
			{
				if (isset($MATCHES[1]) && is_string($MATCHES[1]))
				{
					$split_number = $MATCHES[1];
				}
			}
			if (empty($split_number))
			{
				$split_number = 1;
			}
			$desc = trim(preg_replace('/\('.$split_number.'\)$/si','',$discount->description));

			$ret_tik = new Ticket($db);
			$ticket_ref = trim(preg_replace('/\s\('.$split_number.'\)/si','',$desc));
			if (preg_match('/([M|G]{1,2}TK[0-9]{4}\-[0-9]{1,})/si',$ticket_ref,$MATCHES))
			{
				$ticket_ref = $MATCHES[0];
			}
			

			if(!$ret_tik->fetch(null,$ticket_ref))
			{
				$db->rollback();
			}
			else
			{
				if(true /* $discount->link_to_ticket($ret_tik->id) */)
				{
					$db->commit();
					return array(
											 'ticket_ref'=>$ticket_ref
											,'ticket_id'=>$ret_tik->id
											,'discount_1'=>$discount->id
											,'discount_2'=>null
											);
				}
			}
		}
		
		if (!$res > 0)
		{
			$error++;
			//setEventMessages($langs->trans("ErrorFailedToLoadDiscount"), null, 'errors');
		}
		$amount_ttc_1 = price2num($first_ammount);
		$amount_ttc_2 = $discount->amount_ttc - $amount_ttc_1;

		if (!$error && price2num($amount_ttc_1 + $amount_ttc_2) != $discount->amount_ttc)
		{
			$error++;
			//setEventMessages($langs->trans("TotalOfTwoDiscountMustEqualsOriginal"), null, 'errors');
		}
		if (!$error && ($discount->fk_facture_line || $discount->fk_facture || $discount->fk_ticket))
		{
			$error++;
			//setEventMessages($langs->trans("ErrorCantSplitAUsedDiscount"), null, 'errors');
		}
		if (!$error)
		{
			$newdiscount1 = new DiscountAbsolute($db);
			$newdiscount2 = new DiscountAbsolute($db);
			$newdiscount1->fk_facture_source = $discount->fk_facture_source;
			$newdiscount2->fk_facture_source = $discount->fk_facture_source;
			$newdiscount1->fk_facture = $discount->fk_facture;
			$newdiscount2->fk_facture = $discount->fk_facture;
			$newdiscount1->fk_facture_line = $discount->fk_facture_line;
			$newdiscount2->fk_facture_line = $discount->fk_facture_line;
			$newdiscount1->fk_invoice_supplier_source = $discount->fk_invoice_supplier_source;
			$newdiscount2->fk_invoice_supplier_source = $discount->fk_invoice_supplier_source;
			$newdiscount1->fk_invoice_supplier = $discount->fk_invoice_supplier;
			$newdiscount2->fk_invoice_supplier = $discount->fk_invoice_supplier;
			$newdiscount1->fk_invoice_supplier_line = $discount->fk_invoice_supplier_line;
			$newdiscount2->fk_invoice_supplier_line = $discount->fk_invoice_supplier_line;
			if ($discount->description == '(CREDIT_NOTE)' || $discount->description == '(DEPOSIT)')
			{
				$newdiscount1->description = $discount->description;
				$newdiscount2->description = $discount->description;
			}
			else
			{
				
				if (preg_match('/\(([0-9]{1,})\)$/si',$discount->description,$MATCHES))
				{
					if (isset($MATCHES[1]) && is_string($MATCHES[1]))
					{
						$split_number = $MATCHES[1];
					}
				}
				if (empty($split_number))
				{
					$split_number = 1;
				}
				$desc = trim(preg_replace('/\('.$split_number.'\)$/si','',$discount->description));
				$newdiscount1->description = $desc.' ('.($split_number).')';
				$newdiscount2->description = $desc.' ('.($split_number+1).')';
				$ticket_ref = trim(preg_replace('/\s\('.$split_number.'\)/si','',$desc));
				
			}

			$newdiscount1->fk_user = $discount->fk_user;
			$newdiscount2->fk_user = $discount->fk_user;
			$newdiscount1->fk_soc = $discount->fk_soc;
			$newdiscount2->fk_soc = $discount->fk_soc;
			$newdiscount1->discount_type = $discount->discount_type;
			$newdiscount2->discount_type = $discount->discount_type;
			$newdiscount1->datec = $discount->datec;
			$newdiscount2->datec = $discount->datec;
			$newdiscount1->tva_tx = $discount->tva_tx;
			$newdiscount2->tva_tx = $discount->tva_tx;
			$newdiscount1->amount_ttc = $amount_ttc_1;
			$newdiscount2->amount_ttc = price2num($discount->amount_ttc - $newdiscount1->amount_ttc);
			$newdiscount1->amount_ht = price2num($newdiscount1->amount_ttc / (1 + $newdiscount1->tva_tx / 100), 'MT');
			$newdiscount2->amount_ht = price2num($newdiscount2->amount_ttc / (1 + $newdiscount2->tva_tx / 100), 'MT');
			$newdiscount1->amount_tva = price2num($newdiscount1->amount_ttc - $newdiscount1->amount_ht);
			$newdiscount2->amount_tva = price2num($newdiscount2->amount_ttc - $newdiscount2->amount_ht);

			$newdiscount1->fk_paiement = $discount->fk_paiement;
			$newdiscount2->fk_paiement = $discount->fk_paiement;
			$newdiscount1->fk_paiement_type = $discount->fk_paiement_type;
			$newdiscount2->fk_paiement_type = $discount->fk_paiement_type;

			$db->begin();
			$discount->fk_facture_source = 0; // This is to delete only the require record (that we will recreate with two records) and not all family with same fk_facture_source
        	// This is to delete only the require record (that we will recreate with two records) and not all family with same fk_invoice_supplier_source
			$newid1 = $newdiscount1->create($user);
			$newid2 = $newdiscount2->create($user);
			if ($newid1 > 0 && $newid2 > 0 )
			{
				$discount->fk_invoice_supplier_source = 0;
				$res = $discount->delete($user);
			}
			
			//var_dump(preg_match('/^[M|G]TR[0-9]{4}\-[0-9]{4}$/si',$ticket_ref));

			if ($res > 0 && $newid1 > 0 && $newid2 > 0 && preg_match('/[\s]{0,}([M|G]T[R|K][0-9]{4}\-[0-9]{4})/si',$ticket_ref,$mats))
			{
				$ticket_ref = $mats[1];
				$ret_tik = new Ticket($db);
				
				if(false && !$ret_tik->fetch(null,$ticket_ref))
				{
					$db->rollback();
				}
				else
				{
					if(true /* || $newdiscount1->link_to_ticket($ret_tik->id) */ )
					{
						$db->commit();
						$return = array(
										 'ticket_ref'=>$ticket_ref
										,'ticket_id'=>$ret_tik->id
										,'discount_1'=>$newid1
										,'discount_2'=>$newid2
										);
					}
					else
					{
						$db->rollback();
					}
				}
			}
			else
			{
				$db->rollback();
			}
		}
		return $return;
	}
	
	public function cronFixSaldos($id=null)
	{

		global $db;
		$debug	=	true;
		$mStart = microtime(true);

		$return = '';
		
		$sql	=	 'UPDATE llx_pos_ticket AS t '."\r\n"
					.'LEFT JOIN llx_facture AS f '."\r\n"
					.'on t.fk_facture = f.rowid '."\r\n"
					.'SET t.fk_facture = NULL '."\r\n"
					.'WHERE t.fk_facture IS NOT NULL '."\r\n"
					.'  AND (f.rowid IS NULL '."\r\n"
					.'   OR f.fk_statut IN (3)) '."\r\n"
					.''
					;
		if (is_numeric($id) && $id >0)
		{
			$sql .= '  AND t.rowid = '.$id.' ';
		}
		if (!$res = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}
		$return .=  '<p>Se econtraron '.$db->affected_rows($res).' tickets con factura borrada.</p>';



		$sql	=	 'SELECT pt.rowid '."\r\n"
					.'FROM llx_pos_paiement_ticket AS pt '."\r\n"
					.'LEFT JOIN llx_paiement AS p '."\r\n"
					.'  ON pt.fk_paiement = p.rowid '."\r\n"
					.'WHERE p.rowid IS NULL'."\r\n"
					;
		if (is_numeric($id) && $id >0)
		{
			$sql .= '  AND pt.fk_ticket = '.$id.' ';
		}
		if (!$res = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}
		$idx	=	array();
		while ($row = $db->fetch_object($res))
		{
			$idx[]	= $row->rowid;
		}

		if (count($idx))
		{
			$sql	=	 'DELETE FROM llx_pos_paiement_ticket WHERE rowid IN ('.implode(',',$idx).')';
			if (!$db->query($sql))
			{
				dol_print_error($db);
				die(__LINE__.' Error');
			}
			$return .=  '<p>Se encontraron '.(count($idx)).' pagos borrados.</p>';
		}
		else
		{
			$return .=  '<p>No se encontraron pagos borrados.</p>';
		}


		$sql	=	 'LOCK TABLES llx_pos_ticket WRITE,llx_pos_ticket AS t WRITE , llx_pos_paiement_ticket AS pt WRITE, llx_paiement AS p WRITE';
		if (!$res = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}

		$sql =	 'UPDATE llx_pos_ticket '."\r\n"
				.'SET '."\r\n"
				.' paye = 0'."\r\n"
				.',difpayment = total_ttc '."\r\n"
				.',customer_pay = 0 '."\r\n"
				;
		if (is_numeric($id) && $id >0)
		{
			$sql .= '  WHERE rowid = '.$id.' ';
		}
		if (!$res2 = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}
		$return .=  '<p>Se reinició es estatus de '.$db->affected_rows($res).' tickets.</p>';





		$sql	=	 'SELECT t.ticketnumber '."\r\n"
					.'		,t.fk_statut '."\r\n"
					.'		,t.type '."\r\n"
					.'		,p.ref '."\r\n"
					.'		,p.rowid as pid '."\r\n"
					.'		,t.rowid '."\r\n"
					.'		,t.paye '."\r\n"
					.'		,t.total_ttc '."\r\n"
					.'		,t.difpayment '."\r\n"
					.'		,t.discount_applied '."\r\n"
					.'		,pt.amount AS pamount '."\r\n"
					.'FROM llx_pos_ticket AS t '."\r\n"
					.'LEFT JOIN llx_pos_paiement_ticket AS pt '."\r\n"
					.'  ON t.rowid = pt.fk_ticket '."\r\n"
					.'LEFT JOIN llx_paiement AS p '."\r\n"
					.'  ON p.rowid = pt.fk_paiement '."\r\n"
					.'WHERE t.fk_statut <>0 AND t.fk_statut IS NOT NULL '."\r\n"
					;
		
		if (is_numeric($id) && $id >0)
		{
			$sql .= '  AND t.rowid = '.$id.' ';
		}
		$sql	.=	 'ORDER BY t.ticketnumber ASC, p.rowid ASC'
					;
			
		if (!$res = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}
		

		$saldos = array();
		$lastbad = null;
		$i = 0;
		while ($row = $db->fetch_object($res))
		{
		
			if (!isset($saldos[$row->rowid]))
			{
				$saldos[$row->rowid]= $row->total_ttc;
			}
			if (is_numeric($row->pamount) && $row->pamount <> 0 )
			{
				$factor = ($row->type == 0)? 1: -1;
				$saldos[$row->rowid] -= $row->pamount*$factor;
			}
			$rclass = ''; 
			$lastbad = $row->rowid;
			$i++;
			$sql =	 'UPDATE llx_pos_ticket '."\r\n"
					.'SET '."\r\n"
					.'paye = '.((abs($saldos[$row->rowid]) <=0.109)?'1':'0')."\r\n"
					.',difpayment = '.((abs($saldos[$row->rowid]) <=0.109)?'0':$saldos[$row->rowid])."\r\n"
					.',customer_pay = customer_pay + '.($row->pamount?$row->pamount:'0')."\r\n"
					.'WHERE rowid = '.$row->rowid."\r\n"
					;
			if (!$res1 = $db->query($sql))
			{
				dol_print_error($db);
				die(__LINE__.' Error');
			}
		}

		$sql	=	 'UNLOCK TABLES';
		if (!$res = $db->query($sql))
		{
			dol_print_error($db);
			die(__LINE__.' Error');
		}

		$return .= '<p>Ejecutado en '.(microtime(true)-$mStart). ' segundos.</p>';
		
		return $return;	
	}
}
?>
