<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies', 'categories'));

#Etiqueta que se usará para las Correcciones de Stock
$mvLabel='Corrección de stock del producto por Revisión';


$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$filters	= GETPOST('filters','array');
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "DESC";

$action = GETPOST('action','alpha');
$cancl = GETPOST('cancel','int');
$productts='';
if($action == 'correctionm'){
    if(!empty(GETPOST('total_items','int'))) {
        for ($i = 0; $i < GETPOST('total_items','int');$i ++){
            if(GETPOST('correction_'.$i,'int') > 0) {
                $productts .= ',' . GETPOST('idproduct' . $i);
            }
        }
        $productts = substr($productts,1,strlen($productts)-1);
    }
}
if ($action == 'stock_moved' && !$cancl)
{
	$prod_id = GETPOST('prod_id','int');
	$tick_id = GETPOST('ticket','int');
	if ($prod_id > 0)
	{
		$sql =	 'UPDATE llx_pos_ticketdet'."\r\n"
				.'SET ls_warehouse_status = 3'."\r\n"
				.'   ,ls_warehouse_status_by='.$user->id."\r\n"
				.'   ,ls_warehouse_status_date=\''.gmdate('Y-m-d H:i:s').'\''."\r\n"
				.'WHERE fk_product='.$prod_id."\r\n"
				.'  AND fk_ticket='.$tick_id."\r\n"
				;
		if (!$db->query($sql))
		{
			dol_print_error($db);
			die();
		}
				
	}
}

$object = new Entrepot($db);
if ($id > 0 || !empty($ref)) {
    $ret = $object->fetch($id, $ref);
	//    if ($ret > 0)
	//        $ret = $object->fetch_thirdparty();
    if ($ret <= 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    }
}

if($action == 'masive_correction'){
    $total = $_POST['total_items'];
    $masivecorrection = array();
    $codeA = '';
    for($i = 0; $i <=$total;$i ++){
        if(GETPOST('correction_'.$i) > 0) {
            $codeA = dol_print_date(dol_now(), '%y%m%d%H%M%S');
            $line = new stdClass();
            $product = new Product($db);
            if (!empty(GETPOST('idproduct'.$i))) $result = $product->fetch(GETPOST('idproduct'.$i));
            $error = 0;

            if (empty(GETPOST('idproduct'.$i)))
            {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
                $action = 'correction';
            }

            $nbpieces = GETPOST('correction_'.$i) - GETPOST('reel'.$i);
            $line->product_ref = $product->ref;
            $line->product_label = $product->label;
            $line->product_id = $product->id;
            $line->qty = $nbpieces;
            $line->datem = dol_print_date(dol_now(),'standard');
            $line->entrepot_source_ref = $object->label;
            $line->user_firstname = $user->firstname;
            $line->user_lastname = $user->lastname;
            $line->user_login = $user->login;
            $line->reason = (GETPOST('confirm_reason') == 1?GETPOST('reasonCS'): '');
            $line->label = GETPOST("label".$i, 'san_alpha');
            $line->code = dol_print_date(dol_now(), '%y%m%d%H%M%S');

            if (!$error)
            {
                $origin_element = '';
                $origin_id = null;

                if (GETPOST('projectid', 'int'))
                {
                    $origin_element = 'project';
                    $origin_id = GETPOST('projectid', 'int');
                }

                if ($product->hasbatch())
                {
                    $batch = GETPOST('batch_number', 'alphanohtml');

                    //$eatby=GETPOST('eatby');
                    //$sellby=GETPOST('sellby');
                    $eatby = dol_mktime(0, 0, 0, GETPOST('eatbymonth', 'int'), GETPOST('eatbyday', 'int'), GETPOST('eatbyyear', 'int'));
                    $sellby = dol_mktime(0, 0, 0, GETPOST('sellbymonth', 'int'), GETPOST('sellbyday', 'int'), GETPOST('sellbyyear', 'int'));

                    $result = $product->correct_stock_batch(
                        $user,
                        $id,
                        $nbpieces,
                        ($nbpieces<0?$nbpieces*-1:$nbpieces),
                        (GETPOST('confirm_reason') == 1? GETPOST('reasonCS'):''),
                        GETPOST('unitprice'),
                        $eatby, $sellby, $batch,
                        dol_print_date(dol_now(), '%y%m%d%H%M%S'),
                        $origin_element,
                        $origin_id
                    ); // We do not change value of stock for a correction
                }
                else
                {
                    $result = $product->correct_stock(
                        $user,
                        $id,
                        ($nbpieces<0?$nbpieces*-1:$nbpieces),
                        ($nbpieces<0?1:0),
                        (GETPOST('confirm_reason') == 1? GETPOST('reasonCS'): ''),
                        0,
                        dol_print_date(dol_now(), '%y%m%d%H%M%S'),
                        $origin_element,
                        $origin_id
                    ); // We do not change value of stock for a correction
                }

                if ($result < 0)
                {
                    $error++;
                    setEventMessages($product->error, $product->errors, 'errors');
                }else{
                    $sqldel = "DELETE FROM ".MAIN_DB_PREFIX."product_stock_revised WHERE fk_product=".GETPOST('idproduct'.$i)." AND fk_warehouse = ".$id;
                    $resdel = $db->query($sqldel);
                    if(!$resdel){
                        setEventMessages($db->lasterror(), $db->lasterror(), 'errors');
                    }
                    array_push($masivecorrection,$line);
                }

            }
        }
    }
    if(sizeof($masivecorrection) > 0){
        require (DOL_DOCUMENT_ROOT.'/core/modules/stock/doc/pdf_masivestockcorrection.modules.php');
        $doc = new pdf_masivestockcorrection($db);
        $doc->write_file($masivecorrection,null,null);
        $dir = $conf->stock->dir_output."/stock_correction/". dol_sanitizeFileName($codeA);
        $cdir = scandir($dir);
        $file = "";
        foreach ($cdir as $key => $value)
        {
            if (substr($value,strlen($value)-4) == ".pdf" ){
                $file = $value;
            }
        }
        print "<script> 
            window.open('".DOL_URL_ROOT."/document.php?modulepart=stock&attachment=0&file=stock_correction%2F".urlencode(dol_sanitizeFileName($codeA))."%2F".urlencode($file)."&entity=1', '_blank');
        </script>";
    }
    //header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
}
	$stList =	POS::getEstadovArray(null,'class');
	$stLabel =	POS::getEstadovArray(null,'label');
$fNow =	 time()+date('Z');
$thisDay=mktime(0,0,0,date('m',$fNow),date('d',$fNow),date('Y',$fNow))+date('Z');
$today = date('Y-m-d H:i:s',$thisDay);
$sql =	 'SELECT '."\r\n"
		.'t.rowid,t.ticketnumber'."\r\n"
		.',td.fk_product'."\r\n"
		.',td.ls_warehouse_status'."\r\n"
		.',IF (td.description =\'\',p.label,td.description) AS prod_label'."\r\n"
		.',p.ref AS prod_ref'."\r\n"
		.',ps.reel'."\r\n"
		.',psr.stock_revised'."\r\n"
		.',p.rowid as prodid'."\r\n"
		.'FROM llx_pos_ticket AS t'."\r\n"
		.'LEFT JOIN llx_pos_ticketdet AS td'."\r\n"
		.'  ON t.rowid=td.fk_ticket'."\r\n"
		.'LEFT JOIN llx_product AS p'."\r\n"
		.'  ON td.fk_product=p.rowid'."\r\n"
		.'LEFT JOIN llx_product_stock AS ps'."\r\n"
		.'  ON ps.fk_product=p.rowid AND fk_entrepot='.$id."\r\n"
		.'LEFT JOIN llx_product_stock_revised AS psr'."\r\n"
		.'  ON psr.fk_product=p.rowid AND fk_warehouse='.$id."\r\n"
		.'WHERE ('."\r\n"
		.'          td.ls_warehouse_status IS NOT NULL'."\r\n"
		.'		AND t.fk_statut IN (1,2)'
		.'		AND psr.fk_product IS NOT NULL '
		//.'       OR ('."\r\n"
		//.'                td.ls_warehouse_status=3 '."\r\n"
		//.'            AND td.ls_warehouse_status_date > \''.$today.'\''."\r\n"
		//.'           )'."\r\n"
		.'       )'
		;
if(isset($filters['ticketnumber']) && strlen($filters['ticketnumber']))
{
	$sql .=	 '  AND t.ticketnumber LIKE \'%'.$filters['ticketnumber'].'%\''."\r\n";
}
if(isset($filters['ref']) && strlen($filters['ref']))
{
	$sql .=	 '  AND p.ref LIKE \'%'.$filters['ref'].'%\''."\r\n";
}
if(isset($filters['label']) && strlen($filters['label']))
{
	$sql .=	 '  AND ('."\r\n"
			.'          td.description LIKE \'%'.$filters['label'].'%\' '."\r\n"
			.'       OR p.label LIKE \'%'.$filters['label'].'%\''."\r\n"
			.'       )'."\r\n";
}
if($productts != ''){
    $sql .= ' OR p.rowid IN ('.$productts.') ';
}
//$sql .= 'GROUP BY `td`.`fk_product` ';
$sql .=	'ORDER BY '.$sortfield.' '.$sortorder;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die();
}
$params = "&amp;id=".$id;
foreach($filters as $k=>$v)
{
	$params .= "&filters[{$k}]={$v}";
}
$allproduct = array();
$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
$back_url = $_SERVER['PHP_SELF'].'?sortorder='.$sortorder.'&sortfield='.$sortfield.$params.'&action=stock_moved&prod_id=';
llxHeader("", $langs->trans("WarehouseCard"), $help_url);
			$head = stock_prepare_head($object);

			dol_fiche_head($head, 'in_review', $langs->trans("Warehouse"), -1, 'stock');
			$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			$morehtmlref .= $langs->trans("LocationSummary").' : '.$object->lieu;
        	$morehtmlref .= '</div>';

            $shownav = 1;
            if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

        	dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);
?>
<style>
<?php echo POS::getEstadovStyle(); ?>

.modal .modal-content{padding:0 !important;} .modal{width: 95% !important;}
select{display:block;overflow: auto;}
.width25{width:25px !important}
.maxwidth75imp{max-width: 75px !important; display: inline-block !important;}
select.flat{
padding-top: 4px;
    padding-right: 4px;
    padding-bottom: 3px;
    padding-left: 2px;


	display: inline-block; 
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
	
	background-color: #FFF;		
	border: none;
	
	font-weight: normal;
    font-size: unset;
	height: 25px;
font-family: roboto,arial,tahoma,verdana,helvetica;
    outline: none;
    margin: -6px 0px 0px 0px;
    border-bottom: solid 1px rgba(0,0,0,.2);

		}
</style>
<?php if($action != 'correctionm' && $user->rights->stock->masive_correctionstock){ ?>
<div style="vertical-align: middle">
    <div class="pagination paginationref">
        <a href="<?=$_SERVER['PHP_SELF']?>?id=<?=$id?>&action=correctionm&filters[ref]=<?=$filters['ref']?>&filters[label]=<?=$filters['label']?>" style="padding:8px !important;background-color:#74A7FE;text-decoration:none;color:white;padding: 1px 3px;width: 100%;display: inline-block;text-align: center;border-radius: 10px;">
            Corrección Masiva
        </a>
    </div>
</div>
<?php } ?>
<table class="noborder centpercent">
	<thead>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="form_correction">
		<tr class="liste_titre_filter">
			<th class="list_titre"><input type="text" name="filters[ticketnumber]" value="<?php echo $filters['ticketnumber']?>" class="flat" style="background-color: #fff; height: 1.5em; " /></th>
			<th class="list_titre" style="text-align: left;"><input type="text" name="filters[ref]" value="<?php echo $filters['ref']?>" class="flat" style="background-color: #fff; height: 1.5em; " /></th>
			<th class="list_titre" style="text-align: left;"><input type="text" name="filters[label]" value="<?php echo $filters['label']?>" class="flat" style="background-color: #fff; height: 1.5em; " /></th>
			<th class="list_titre"></th>
            <?php if($action == 'correctionm'){?>
			<th class="list_titre"></th>
            <?php } ?>
			<th class="list_titre"></th>
			<th class="list_titre"></th>
			<th class="list_titre"></th>
			<!--
<th class="list_titre"></th>
-->
		</tr>
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="action" id="action" value="<?=$action?>" />
		<input type="hidden" name="sortfield" value="<?php echo $sortfield; ?>" />
		<input type="hidden" name="sortorder" value="<?php echo $sortorder; ?>" />
		<input type="submit" style="position: absolute; left: -9999px"/>
        <!--</form>-->
		<tr class="liste_titre">
			<?php print_liste_field_titre("Ticket", "", "t.ticketnumber", $params, "", "", $sortfield, $sortorder); ?>
			<?php print_liste_field_titre("Ref.", "", "p.ref", $params, "", "", $sortfield, $sortorder); ?>
			<?php print_liste_field_titre("Etiqueta", "", "prod_label", $params, "", "", $sortfield, $sortorder); ?>
			<?php print_liste_field_titre("Stock F&iacute;sico", "", "ps.reel", $params, "", "", $sortfield, $sortorder); ?>
			<?php if($action == 'correctionm')
                print_liste_field_titre("Stock Actual", "", "", $params, "", "", $sortfield, $sortorder); ?>
			<th>&nbsp;</th>
			<th>&nbsp;</th>
			<?php print_liste_field_titre("Estado V.", "", "td.ls_warehouse_status", $params, "", "", $sortfield, $sortorder); ?>
		</tr>
	</thead>
    <!--<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" >-->
	<tbody>
		<?php $ic = 0;$i = 0; ?>
		<?php while ($row = $db->fetch_object($res)): ?>
		<?php $lreel = (is_numeric($row->reel))? $row->reel:'0'; ?>
		<?php $lrev	 = (is_numeric($row->stock_revised))? $row->stock_revised:''; ?>
		<?php $lnbn	 = (is_numeric($lreel) && is_numeric($lrev))?($lrev-$lreel):''; ?>
		<tr>
			<td><?php echo $row->ticketnumber; ?></td>
			<td><?php echo $row->prod_ref; ?></td>
			<td><?php echo $row->prod_label; ?></td>
			<td><?php echo $lreel;?></td>
            <?php if($action == 'correctionm') {
                if (!in_array($row->fk_product, $allproduct)) {
                    ?>
                    <td class="list_titre" style="background-color: #77DD77;">
                        <input type="text" name="correction_<?= $i ?>">
                        <input type="hidden" name="ticket_id<?= $i ?>" value="<?= $row->rowid ?>">
                        <input type="hidden" name="idproduct<?= $i ?>" value="<?= $row->fk_product ?>">
                        <input type="hidden" name="product_id<?= $i ?>" value="<?= $row->fk_product ?>">
                        <input type="hidden" name="label<?= $i ?>" value="<?= $mvLabel ?>">
                        <input type="hidden" name="reel<?= $i ?>" value="<?= $lreel ?>">
                    </td>
                <?php
                    $i ++;
                array_push($allproduct,$row->fk_product);
                }else{?>
                    <td class="list_titre">
                        &nbsp;
                    </td>
                <?php }
            }?>
			<!--
<td><?php echo $lrev; ?></td>
-->
			<td><a href="<?php echo DOL_URL_ROOT; ?>/product/stock/movement_list.php?<?php echo "id={$id}&idproduct={$row->fk_product}";?>" target="_blank">Lista Completa de Mov.</a></td>
			<!-- nbpiece=cantidad de piezas a mover / mouvement=1 para eliminar -->
			<?php
				$link_url = DOL_URL_ROOT
							.'/product/stock/movement_list.php'
							.'?id=1'
							.'&ticket_id='.$row->rowid
							.'&idproduct='.$row->fk_product
							.'&product_id='.$row->fk_product
							.'&action=correction'
							.'&label='.$mvLabel
							//.'&nbpiece='.$lnbn
							.'&mouvement='
#							.'&backtopage='.urlencode(	 $back_url.$row->fk_product
#														.'&ticket='.$row->rowid
#													)
							.'#stock_correction'
							;
			?>
			<td><a href="<?php echo $link_url; ?>" target="_top" id="correct_link_">Corrección de Stock</a></td>
			<td><span style="padding: 1px 3px;width: 100%;display: inline-block;text-align: center;border-radius: 10px;" class="<?php echo $stList[$row->ls_warehouse_status];?>"><?php echo $stLabel[$row->ls_warehouse_status]; ?></span></td>
		</tr>
		<?php $ic++; ?>
		<?php endwhile; ?>
	</tbody>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <!--<input type="hidden" name="action" value="masive_correction" />-->
        <input type="hidden" name="total_items" value="<?=$i?>" />
        <input type="hidden" name="reasonCS" id="reasonCS"/>
        <input type="hidden" name="confirm_reason" id="confirm_reason"/>
    </form>
</table>
<?php if($action == 'correctionm'){ ?>
<input type="submit" id="submit_terminar" value="TERMINAR" style="float:right;padding:12px !important;background-color:#669D34;text-decoration:none;color:white;display: inline-block;text-align: center;border-radius: 10px;">
    <div id="dialog" title="Confirmación de Correción">
        <div>
            <p>
                ¿Qué tipo de Ajuste desea Realizar?
            </p>
            <p>
                <select id="ajuste" name="ajuste">
                    <option value="0">Inventario</option>
                    <option value="1">Otros</option>
                </select>
            </p>
            <p>
                ¿Cuál es el Motivo de la Corrección?
            </p>
            <p>
                <input type="text" id="reasonC" name="reasonC" style="min-width: 75%"/>
            </p>
        </div>
    </div>
    <script>
        $(document).ready(function(){

            const today = new Date();
            const todayDate = ("0" + today.getDate()).slice(-2)+"/"+("0" + (today.getMonth() + 1)).slice(-2)+"/"+today.getFullYear();
            const msgAjuste = "Corrección de Stock por Ajuste de Inventario (" + todayDate + ")";
            $('#reasonC').val(msgAjuste); //Se carga por Default la Fecha Actual en Formato d/m/Y

            $('#ajuste').change(function(){                
                const type = $(this).val();
                
                if(type == 1){
                    $('#reasonC').val(''); //Se limpia campo de Razón
                }else{
                    $('#reasonC').val(msgAjuste); //Se carga por Default la Fecha Actual en Formato d/m/Y
                }
            });

            $("#dialog").dialog({
                autoOpen: false,
                modal: true,
                buttons: {
                    "Confirmar motivo": function () {
                        $("#action").val('masive_correction');
                        $("#reasonCS").val($("#reasonC").val());
                        $("#confirm_reason").val(1);
                        $("#form_correction").submit();
                    },
                    "Cancelar motivo": function () {
                        $(this).dialog("close");
                        $("#action").val('masive_correction');
                        $("#confirm_reason").val(0);
                        //$("#form_correction").submit();
                    }
                }
            });
            $("#submit_terminar").on('click',function () {
                $("#dialog").dialog("option", "width", 500);
                $("#dialog").dialog("option", "height", 300);
                $("#dialog").dialog("option", "resizable", false);
                $("#dialog").dialog("open");
            });
        });
    </script>
<?php } ?>