<?php

/**
 * @author admin
 * @copyright 2021
 */


require '../../main.inc.php';


$lines = explode(',',$_GET['lines']);
$prov = explode(',',$_GET['provs']);
$id = $_GET['source'];
foreach($lines as $k=>$v)
{
	if (strpos($v,'line_') === 0)
	{
		$lines[$k] = str_replace('line_','',$v);
		if (!is_numeric($lines[$k]) && !($lines[$k] > 0))
		{
			unset($lines[$k]);
		}
	}
	else
	{
		unset($lines[$k]);
	}
}


$error = 0;
if (!is_numeric($id) || !($id > 0))
{
	setEventMessages(500, array('No se ha proporcionado una Cotización a Cliente válida'), 'errors');
	$error++;
}
if (!is_array($lines) || !count($lines))
{
	setEventMessages(500, array('No se han seleccionado productos válidos'), 'errors');
	$error++;
}
if (!is_array($prov) || !count($prov))
{
	setEventMessages(500, array('No se han seleccionado proveedores válidos'), 'errors');
	$error++;
}

if (!$error)
{
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	$source = new propal($db);
	if ($source->fetch($id) < 0)
	{
		setEventMessages(500, array('Pudo abrir la Cotización a Cliente origen'), 'errors');
		$error++;
	}
}

if (!$error)
{
	require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
	foreach($prov as $pro)
	{
		$target = new SupplierProposal($db);
		$target->socid = $pro;
		foreach($source->lines as $s_line)
		{
			if (in_array($s_line->id,$lines))
			{
				$n_line = new SupplierProposalLine($db);
				$n_line->fk_product	=	$s_line->fk_product;
				$n_line->desc		=	$s_line->desc;
				$n_line->qty		=	$s_line->qty;
				$n_line->subprice	=	null;
				$n_line->product_type =	$s_line->product_type;
				$n_line->remise_percent='0';
				$n_line->tva_tx		=	'16';
				$target->lines[]=$n_line;
			}
		}
		if ($target->create($user)<0)
		{
			setEventMessages(500, array('No se pudo crear la Cotización de Proveedor ('.$pro.')'), 'errors');
			$error++;
		}
		else
		{
			//setEventMessages(500, array('Se creó Cotización de Proveedor '.$target->ref.''));
			$sql =	 'INSERT INTO `llx_element_element` '
					.'(`fk_target`,`targettype`,`fk_source`,`sourcetype`)'
					.'VALUES'
					.'('.$source->id.',\''.$source->element.'\','.$target->id.',\''.$target->element.'\')'
					;
			if (!$db->query($sql))
			{
				dol_print_error($db);
				$error++;
			}
		}
	}
	
}
?><?php if (!$error):?>
<script>
	var l = '<?php echo DOL_URL_ROOT; ?>/comm/propal/card.php?save_lastsearch_values=1&id=<?php echo $id; ?>'
	window.location.href = l;</script>
<?php endif;?>