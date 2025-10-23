<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (!class_exists('POS'))
{
	require_once(DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php');
}

$id			= GETPOST('id','integer');
$sortfield	= GETPOST("sortfield", 'alpha');
$sortorder	= GETPOST("sortorder", 'alpha');
$selected	= GETPOST('sts','array');
$filters	= GETPOST('filters','array');
$date_month	= intval($filters['month']);
$date_year	= intval($filters['year']);

$stTime		= mktime(0,0,0,gmdate('m'),1,gmdate('Y'));


if(!count($selected))
{
	$selected= array(1,2,4,7,8);
}


if (!$sortfield) $sortfield = "t.date_ticket";
if (!$sortorder) $sortorder = "DESC";

	$order	= explode(',',$sortorder);
	$field	= explode(',',$sortfield);
	$sort	= array();
	for ($i=0,$n=count($field);$i<$n;$i++)
	{
		if (count($order) == count($field))
		{
			if ($field[$i] == 't.fk_statut')
			{
				if (strtolower($order[$i]) == 'asc')
				{
					$field[$i] = ' FIELD(t.fk_statut,0,2,1) '; 
				}
				else
				{
					$field[$i] = ' FIELD(t.fk_statut,1,2,0) ';
				}
				$sort[$i] = $field[$i];
			}
			else
			{
		 		$sort[$i] = $field[$i].' '.$order[$i]; 
			}
		}
		else
		{
			if ($field[$i] == 't.fk_statut')
			{
				if (strtolower($order[0]) == 'asc')
				{
					$field[$i] = ' FIELD(t.fk_statut,0,2,1) '; 
				}
				else
				{
					$field[$i] = ' FIELD(t.fk_statut,1,2,0) ';
				}
				$sort[$i] = $field[$i];
			}
			else
			{
				$sort[$i] = $field[$i].' '.$order[0];
			}
		}
	}
	$stList =	POS::getEstadovArray(null,'class');
	$stLabel =	POS::getEstadovArray(null,'label');

$form	= new Form($db);
$formother=new Formother($db);
$object = new Entrepot($db);
$result = $object->fetch($id, '');
if ($result <= 0)
{
	print 'Almacén no localizado';
	exit;
}

$sql =	 'SELECT  t.rowid '."\r\n"
		.'		, t.ticketnumber AS ref '."\r\n"
		.'		, t.date_ticket '."\r\n"
		.'		, t.fk_soc '."\r\n"
		.'		, c.name AS cash_name '."\r\n"
		.'		, c.fk_warehouse AS warehouse '."\r\n"
		.'		, t.fk_user_author '."\r\n"
		.'		,SUM(td.qty) AS total_items '."\r\n"
		.'		, u.firstname AS user_firstname'."\r\n"
		.'		, u.lastname AS user_lastname'."\r\n"
		.'		, u.login AS user_login'."\r\n"
		.'		, s.nom AS soc_name'."\r\n"
		.'		, s.rowid AS soc_id'."\r\n"
		.'FROM `'.MAIN_DB_PREFIX.'pos_ticket` AS `t`'."\r\n"
		.' JOIN `'.MAIN_DB_PREFIX.'pos_ticketdet_deleted` AS `td`'."\r\n"
		.'  ON `t`.`rowid` = `td`.`fk_ticket`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'pos_cash` AS `c`'."\r\n"
		.'  ON `c`.`rowid` = `t`.`fk_cash`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'user` AS `u`'."\r\n"
		.'  ON `u`.`rowid` = `t`.`fk_user_author`'."\r\n"
		.'LEFT JOIN `'.MAIN_DB_PREFIX.'societe` AS `s`'."\r\n"
		.'  ON `t`.`fk_soc` = `s`.`rowid`'."\r\n"
		.'WHERE `c`.`fk_warehouse`= '.$id.''."\r\n"
		.'  AND `td`.`fk_product` IS NOT NULL '."\r\n"
		;
if (isset($filters['ref']) && strlen($filters['ref']))
{
	$sql .=	'  AND t.ticketnumber LIKE \'%'.$db->escape($filters['ref']).'%\''."\r\n";
}
if (isset($filters['cash_name']) && strlen($filters['cash_name']))
{
	$sql .=	'  AND c.name LIKE \'%'.$db->escape($filters['cash_name']).'%\''."\r\n";
}
if (isset($filters['user']) && strlen($filters['user']))
{
	$sql .=	 '  AND ('."\r\n"
			.'          u.firstname LIKE \'%'.$db->escape($filters['user']).'%\''."\r\n"
			.'       OR u.lastname LIKE \'%'.$db->escape($filters['user']).'%\''."\r\n"
			.'       OR u.login LIKE \'%'.$db->escape($filters['user']).'%\''."\r\n"
			.')'."\r\n"
			;
}
if (isset($filters['soc_name']) && strlen($filters['soc_name']))
{
	$sql .=	'  AND s.nom LIKE \'%'.$db->escape($filters['soc_name']).'%\''."\r\n";
}
if (isset($filters['statut']) && strlen($filters['statut']))
{
	$sql .=	'  AND t.fk_statut = \''.$db->escape($filters['statut']).'\''."\r\n";
}
if (
		isset($filters['month']) && $filters['month']>0
	 &&	isset($filters['year']) && $filters['year']>0
	)
{
	$stDate	=	mktime(0,0,0,$filters['month'],1,$filters['year']);
	$sql .=	'  AND t.date_ticket >= \''.date('Y-m-d H:i:s',$stDate).'\''."\r\n";
	$enDate	=	mktime(0,0,0,$filters['month']+1,1,$filters['year'])-1;
	$sql .=	'  AND t.date_ticket <= \''.date('Y-m-d H:i:s',$enDate).'\''."\r\n";
}

$sql .=	 'GROUP BY t.rowid '."\r\n";

if (isset($filters['total_items']) && is_numeric($filters['total_items']))
{
	$sql .=	'  HAVING total_items = '.$db->escape($filters['total_items']).''."\r\n";
}
$sql .=	 'ORDER BY '.implode(', ',$sort).', t.rowid '.$order[0]."\r\n";


if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die();
}
//die ($sql);
// Load translation files required by the page
$langs->loadLangs(array('stocks', 'productbatch'));


$params = "&amp;id=".$id.'&amp;sts[]='.implode('&amp;sts[]=',$selected);
foreach($filters as $k=>$v)
{
	$params .= "&filters[{$k}]={$v}";
}

// Security check
//$result=restrictedArea($user, 'stock');



/*
 * View
 */

$producttmp=new Product($db);
$linkback = '';

$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("", $langs->trans("Stocks"), $help_url,'',0,0,array('/includes/materialize-src/js/bin/materialize.min.js','/includes/jquery/plugins/drop-down-combo-tree/comboTreePlugin.js'),array('/includes/materialize-src/css/materialize.css','https://fonts.googleapis.com/icon?family=Material+Icons&dummy=.css','/includes/jquery/plugins/drop-down-combo-tree/style.css'));

print load_fiche_titre($langs->trans("Productos solicitados en POS"));
dol_fiche_head(array(array(DOL_URL_ROOT.'/product/custom/by_warehouse.php?id='.$id,'Solicitados','sols'),array(DOL_URL_ROOT.'/product/custom/orphans.php?id='.$id,'Pendientes','pends')), 'pends', 'Almacén', -1, 'stock');

dol_banner_tab($object, 'id', $linkback, '1', 'rowid');

echo '</div>';
?><style>
#rc_resumenev tr td {padding:5px;}

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
.rc_nowrap_elipsys
{
	display: block;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
</style>

<div style="font-size:1.16em;">
<div>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return rc_formsubmit(this);">
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		<input type="hidden" name="sortfield" value="<?php echo $sortfield; ?>" />
		<input type="hidden" name="sortorder" value="<?php echo $sortorder; ?>" />
</div>
<table class="noborder centpercent" style="width: 100%;">
	<thead>
		<tr class="liste_titre">
			<?php print_liste_field_titre("Ref.", "", "t.ticketnumber", $params, "", 'style="width:10%;"', $sortfield, $sortorder);?>
			<th style="width:3%;"></th>
			<?php print_liste_field_titre("Fecha", "", "t.date_ticket", $params, "", 'style="width:5%;"', $sortfield, $sortorder);?>
			<?php print_liste_field_titre("Usuario de Terminal", "", "u.firstname,u.lastname,u.login", $params, "", "", $sortfield, $sortorder);?>
			<?php print_liste_field_titre("Cliente", "", "soc_name", $params, "", "", $sortfield, $sortorder);?>
			<?php print_liste_field_titre("Total de Unidades", "", "total_items", $params, "", 'align="right" style="width:70px;"', $sortfield, $sortorder);?>
			<?php print_liste_field_titre("Estado", "", "t.fk_statut", $params, "", "", $sortfield, $sortorder);?>
			<?php print_liste_field_titre("Terminal", "", "cash_name", $params, "", "", $sortfield, $sortorder);?>
		</tr>
		<tr class="liste_titre_filter">
			<td class="list_titre" style="vertical-align: top;"><input type="text" name="filters[ref]" value="<?php echo $filters['ref']?>" class="flat" style="background-color: #fff; height: 1.5em; " /></td>
			<td></td>
			<td class="list_titre" style="width: 120px !important;vertical-align: top;">
				<?php echo ''; //$form->selectDate(); ?>
<!--							
				<input class="flat width25 valignmiddle" type="text" maxlength="2" name="filters[month]" value="<?php echo $filters['month']; ?>" style="height: 1.5em !important;background-color: #fff !important;margin-right: 4px;" /><?php $formother->select_year($filters['year'], 'filters[year]', 1, 20, 5); ?>
-->				
			</td>
			<td class="list_titre" style="vertical-align: top;"><input type="text" name="filters[user]" value="<?php echo $filters['user']?>" class="flat" style="background-color: #fff; height: 1.5em; width:160px; " /></td>
			<td class="list_titre" style="vertical-align: top;"><input type="text" name="filters[soc_name]" value="<?php echo $filters['soc_name']?>" class="flat" style="background-color: #fff; height: 1.5em; " /></td>
			<td class="list_titre" style="vertical-align: top;"><input type="text" name="filters[total_items]" value="<?php echo $filters['total_items']?>" class="flat" style="background-color: #fff; height: 1.5em; width:70px; " /></td>
			<td class="list_titre" style="white-space: nowrap; vertical-align: top;">
				<select name="filters[statut]" style="display: inline-block; width: 110px;">
					<option value=""<?php echo($filters['statut']=='')?' selected="selected"':''; ?>>Todos</option>
					<option value="0"<?php echo($filters['statut']=='0')?' selected="selected"':''; ?>>Borrador</option>
					<option value="2"<?php echo($filters['statut']=='2')?' selected="selected"':''; ?>>Procesados</option>
					<option value="1"<?php echo($filters['statut']=='1')?' selected="selected"':''; ?>>Cerrados</option>
				</select>
			 </td>
			<td class="list_titre" style="vertical-align: top;">
			<div style="width: 130px;">
			<input type="text" name="filters[cash_name]" value="<?php echo $filters['cash_name']?>" class="flat" style="background-color: #fff; height: 1.5em; width:95px; " />
			<button type="submit" style="height: 45px;border: 1px solid #ddd; display: inline-block;"><i class="fa fa-search"></i></button>
			</div>
			</td>
		</tr>
	</thead>
	</form>
	<tbody>
		<?php while($row = $db->fetch_object($res)):?>
		<?php $static_ticket = new Ticket($db); $static_ticket->fetch($row->rowid); ?>
			<tr>
				<td>
					<span class="rc_nowrap_elipsys" title="<?php echo $row->ref;?>">
					<a href="<?php echo DOL_URL_ROOT; ?>/custom/pos/backend/ticket.php?id=<?php echo $row->rowid; ?>">
						<?php echo $row->ref;?>
					</a>
					</span>
					<?php echo rc_getRelatedObject($row);?>
				</td>
				<td><a 
						id="modal_link_<?php echo $row->rowid; ?>"
						href="#modal1" 
						class="btn-floating btn-small pulse modal-trigger" 
						data-source="<?php echo DOL_URL_ROOT; ?>/product/custom/orphans_det.php?id=<?php echo $row->rowid.'&wid='.$row->warehouse; ?>" 
						onclick="return ls_set_modal('<?php echo $row->rowid; ?>')"
						><i class="material-icons">edit</i></a></td>
				<td><span class="rc_nowrap_elipsys"><?php echo $row->date_ticket;?></span></td>
				<td><span class="rc_nowrap_elipsys" style="width:160px;" title="<?php echo $row->user_firstname.' '.$row->user_lastname.' ('.$row->user_login.')';?>"><?php echo $row->user_firstname.' '.$row->user_lastname.' ('.$row->user_login.')';?></span></td>
				<td><a href="<?php echo DOL_URL_ROOT; ?>/societe/card.php?socid=<?php echo $row->soc_id; ?>"><?php echo $row->soc_name;?></a></td>
				<td style="text-align: right;"><span class="rc_nowrap_elipsys" style="width: 70px;"><?php echo $row->total_items; ?></span></td>
				<td><?php echo $static_ticket->getLibStatut(1); ?></td>
				<td><span class="rc_nowrap_elipsys" title="<?php echo $row->cash_name;?>" style="width:130px;"><?php echo $row->cash_name;?></span></td>
			</tr>
		<?php endwhile; ?>
	</tbody>
</table>
</div>
  <!-- Modal Structure -->
  <div id="modal1" class="modal">
    <div class="modal-content">
    
    </div>
  </div>
<script>
var comboTree;
var myData = [
<?php foreach($stList AS $k => $v): ?>
    {
      id: <?php echo $k;?>,
      title:'<?php echo $stLabel[$k];?>',
      'class':'<?php echo $stList[$k];?>'
    },
<?php endforeach; ?>

];
document.addEventListener('DOMContentLoaded', function() {
	var elems = document.querySelectorAll('.modal');
	var options = {};
	var instances = M.Modal.init(elems, options);
});

$(document).ready(function(){
	combotree=$('#sts').comboTree({
		source : myData,
		isMultiple:true,
		selected:[<?php echo implode(',',$selected); ?>]

	});
});

var modal_url = '';
function ls_set_modal(id)
{
	modal_url = $('#modal_link_'+id).attr("data-source");
	$( ".modal-content" ).html('');
	$.get( modal_url, function( data ) {
		$( ".modal-content" ).html(data);
		$('.modal').modal('open');
    var elems = document.querySelectorAll('.dropdown-trigger');
    var options = {};
    var instances = M.Dropdown.init(elems, options);
	});
	return false;
}

function rc_reload_modal()
{
	$( ".modal-content" ).html('');
	$.get( modal_url, function( data ) {
		$( ".modal-content" ).html(data);
		$('.modal').modal('open');
    var elems = document.querySelectorAll('.dropdown-trigger');
    var options = {};
    var instances = M.Dropdown.init(elems, options);
	});
	return false;
}

function rc_formsubmit(e)
{
	combotree.getSelectedIds().forEach(function(i,el){
		var cb = $('<input />', { type: 'checkbox', value: i, name:'sts[]',checked:'checked' }).appendTo(e);
	});
}

</script>
<?php
// End of page
llxFooter();
$db->close();

function rc_getRelatedObject($row)
{
	global $db;
	$objects = array();
	$sql =	 'SELECT * '."\r\n"
			.'FROM `'.MAIN_DB_PREFIX.'element_element` AS `ee`'."\r\n"
			.'WHERE `ee`.`fk_source` = '.$row->rowid."\r\n"
			.'  AND `ee`.`sourcetype`=\'ticket\''."\r\n"
			.'  AND `ee`.`targettype`=\'commande\''."\r\n"
			;
	if (!$res = $db->query($sql))
	{
		dol_print_error($db);
		die();
	}
	while($rw = $db->fetch_object($res))
	{
		$cName = ucfirst(strtolower($rw->targettype));
		if (!class_exists($cName))
		{
			switch($cName)
			{
				case 'Facture':
					$class = DOL_DOCUMENT_ROOT . '/' . $rw->targettype . '/class/' . $rw->targettype . '.class.php';
					break;
				default:
					$class = DOL_DOCUMENT_ROOT . '/' . $rw->targettype . '/class/' . $rw->targettype . '.class.php';
					break;
			}
			if (file_exists($class))
			{
				require_once($class);
			}
		}
		if (class_exists($cName))
		{
			$object = new $cName($db);
			$object->fetch($rw->fk_target);
			$objects[] = '(<a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$rw->fk_target.'" target="_blank" style="color:#090;">'.$object->ref.'</a>)';
		}
	}
	
	$return = '';
	if (count($objects))
	{
		$return = '&nbsp;- '.implode('<br />&nbsp;- ',$objects);
	}
	return $return;
}
