<?php

/**
 * @author admin
 * @copyright 2021
 */

$debug	=	true; 

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pos/class/pos.class.php';
llxHeader("", 'POS - Cierre de tickets', '');


$userid = GETPOST('userid');
$filters = $_POST['filters'];

if (GETPOST('action')=='Cerrar')
{
    foreach($filters['checkbox'] as $cbtk)
    {
        $oldTicket = new Ticket($db);
        $oldTicket->fetch($cbtk);
        
        POS::setAutoWarehouseStatusOnSave2($cbtk);

        $object = new Ticket($db);
        $object->fetch($cbtk);

        if (true || $object->type ==1 || ($object->type == 0 && $object->statut!=0))
        {
            $stock=POS::quitStock($object,$oldTicket);
            if($stock)
            {
                $db->rollback();
                $res = -4;
            }
        }

    }
}

if (!isset($filters['status']))
{
    $filters['status'][] = '2';
}

//var_dump($filters);

$sql =   'SELECT t.rowid, t.ticketnumber,t.fk_statut, td.fk_product, p.label, td.ls_warehouse_status'."\r\n"
        .'FROM llx_pos_ticket AS t'."\r\n"
        .'LEFT JOIN llx_pos_ticketdet AS td'."\r\n"
        .'  ON t.rowid = td.fk_ticket'."\r\n"
        .'LEFT JOIN llx_product AS p'."\r\n"
        .'  ON p.rowid = td.fk_product'."\r\n"
        .'WHERE t.rowid IN'."\r\n"
        .'  ('."\r\n"
        .'      SELECT DISTINCT st.rowid'."\r\n"
        .'      FROM llx_pos_ticket AS st'."\r\n"
        .'      LEFT JOIN llx_pos_ticketdet AS std'."\r\n"
        .'        ON st.rowid = std.fk_ticket'."\r\n"
        .'      WHERE st.type = 0'."\r\n"
        //.'        AND st.fk_statut NOT IN (1)'."\r\n"
        .'  )'
        ;
    $sql =   
         'SELECT DISTINCT st.rowid'."\r\n"
        .'      FROM llx_pos_ticket AS st'."\r\n"
        .'      LEFT JOIN llx_pos_ticketdet AS std'."\r\n"
        .'        ON st.rowid = std.fk_ticket'."\r\n"
        .'      WHERE st.type = 0'."\r\n"
        //.'        AND st.fk_statut NOT IN (1)'."\r\n"
        ;
if (!$user->admin && empty($user->rights->pos->viewunclosedticket))
{
    $sql .= '        AND st.fk_user_author = '.$user->id."\r\n";
}
if ($userid > 0)
{
    $sql .= '        AND st.fk_user_author = '.$userid."\r\n";
}
if (isset($filters['ticket']) && strlen($filters['ticket']))
{
    $sql .= '        AND st.ticketnumber LIKE \'%'.$filters['ticket'].'%\'';
}
if (isset($filters['status']) && is_array($filters['status']) && count($filters['status']) && !in_array(-1,$filters['status']))
{
    $sql .= '        AND st.fk_statut IN ('.implode(',',$filters['status']).')';
}
if (!$res = $db->query($sql))
{
    dol_print_error($db);
    die();
}

$sql2 =   
'SELECT DISTINCT st.fk_user_author'."\r\n"
.'      FROM llx_pos_ticket AS st'."\r\n"
.'      LEFT JOIN llx_pos_ticketdet AS std'."\r\n"
.'        ON st.rowid = std.fk_ticket'."\r\n"
.'      WHERE st.type = 0'."\r\n"
//.'        AND st.fk_statut NOT IN (1)'."\r\n"
;
if (!$user->admin)
{
$sql2 .= '        AND st.fk_user_author = '.$user->id."\r\n";
}
if (!$res2 = $db->query($sql2))
{
dol_print_error($db);
die();
}
$options = array();
while ($row2 = $db->fetch_object($res2))
{
    $dbu = new User($db);
    $dbu->fetch($row2->fk_user_author);
    $options[$row2->fk_user_author] = mb_strtoupper($dbu->firstname).' '.mb_strtoupper($dbu->lastname);
}
asort($options);


//die(var_dump($options));
$ev     = POS::getEstadovArray('numeric','label');
$evs    = POS::getEstadovArray('numeric','class');
$i      = 0;
?>
<style>
    <?php echo POS::getEstadovStyle(); ?>
    th {text-align:left;}
</style>
<form action="" id="useridform" method="POST">
    <div>
    
        <select name="userid" onchange="$('#useridform').submit();">
            <option value="">Todos</option>
            <?php foreach ($options as $ok => $ov): ?>
                <?php
                $selected = '';
                if ($ok == $userid)
                {
                    $selected = ' selected="selected"';
                }?>
                <option value="<?php echo $ok;?>"<?php echo $selected;?>><?php echo $ov;?></option>
            <?php endforeach; ?>
        </select>
    </div>
<table>
    <thead>
        <tr>
            <th style="text-align:right;"></th>
            <th><input type="text" name="filters[ticket]" id="filters_ticket" value="<?php echo $filters['ticket'];?>" /></th>
            <th></th>
            <th><input type="text" name="filters[client]" id="filters_client" value="<?php echo $filters['client'];?>" /></th>
            <th style="text-align:right;"></th>
            <th style="text-align:right;"></th>
            <th style="text-align:right;"></th>  
            <?php if (empty($user->rights->pos->viewunclosedticket)): ?>          
            <th></th>
            <?php endif; ?>

            <th>
                <select name="filters[status][]" id="filters_status" multiple="multiple" style="height: 100px;overflow: auto;">
                    <option value="-1"<?php echo (in_array('-1',$filters['status']))?' selected="selected"':'';?>>Todos</option>
                    <option value="0"<?php echo (in_array('0',$filters['status']))?' selected="selected"':'';?>>Borrador</option>
                    <option value="1"<?php echo (in_array('1',$filters['status']))?' selected="selected"':'';?>>Cerrado</option>
                    <option value="2"<?php echo (in_array('2',$filters['status']))?' selected="selected"':'';?>>Procesado</option>
                    <option value="3"<?php echo (in_array('3',$filters['status']))?' selected="selected"':'';?>>Cancelado</option>
                </select>
            </th>
            <th>
                <input type="submit" name ="action" value="Filtrar" class="button" />
                <script>
                    function switchCheckBoxes()
                    {
                        if ($('#main_cbx').is(":checked"))
                        {
                            $('.cbsls').prop('checked', true);
                        }
                        else
                        {
                            $('.cbsls').prop('checked', false);
                        }
                    }
                    function switchCheckbox()
                    {
                        var all = true;
                        $('.cbsls').each(function(i,e){
                            if (!$(e).is(":checked"))
                            all =false;
                        });
                        if (all)
                        {
                            $('#main_cbx').prop('checked', true);
                        }
                        else
                        {
                            $('#main_cbx').prop('checked', false);
                        }
                    }
                    
                </script>
            </th>
        </tr>
        <tr>
            <th style="text-align:right;">#</th>
            <th>Ticket</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th style="text-align:right;">Importe</th>
            <th style="text-align:right;">Pagos</th>
            <th style="text-align:right;">Saldo</th>            
            <th>Autor</th>
            <th>Estatus</th>
            <?php if (empty($user->rights->pos->viewunclosedticket)): ?>
            <th>
                <input type="checkbox" onclick="switchCheckBoxes();" id="main_cbx"  />
                <input type="submit" name ="action" value="Cerrar" class="button" />
            </th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $db->fetch_object($res)): ?>
            <?php
                $i++;
                $static_ticket = new Ticket($db);
                if (! $static_ticket->fetch($row->rowid))
                {
                    die ('Error leyendo el ticket '.$row->rowid);
                }
                $static_user = new User($db);
                if (! $static_user->fetch($static_ticket->user_author))
                {
                    die ('Error leyendo el autor del ticket '.$row->rowid);
                }
                $static_soc = new Societe($db);
                if (! $static_soc->fetch($static_ticket->socid))
                {
                    die ('Error leyendo el cliente del ticket '.$row->rowid);
                }
                //die(var_dump($static_ticket));
            ?>
            <tr style="background-color:#eee;">
                <td style="text-align:right;"><?php echo $i; ?></td>
                <td><?php echo $static_ticket->getNomUrl(); ?></td>
                <td><?php echo date('Y-m-d H:i:s',$static_ticket->date_creation-(3600*5*0)); ?></td>
                <td>
                    <?php echo $static_soc->getNomUrl(); ?>
                    <?php if($static_soc->typent_id == 235): ?>
                    <br />
                    <?php $saldo =  $static_soc->getOutstandingTickets()['opened'] +
                                    $static_soc->getOutstandingOrders()['opened'] +
                                    $static_soc->getOutstandingBills()['opened']
                                    ;
                        echo number_format($saldo,2,'.',',') .' / ' .number_format($static_soc->outstanding_limit,2,'.',',');
                    ?>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;"><?php echo number_format($static_ticket->total_ttc,2,'.',','); ?></td>
                <td style="text-align:right;"><?php echo number_format($static_ticket->customer_pay,2,'.',','); ?></td>
                <td style="text-align:right;"><?php echo number_format($static_ticket->diff_payment,2,'.',','); ?></td>
                <td><?php echo $static_user->firstname.' '.$static_user->lastname; ?></td>
                <td style="width:120px;"><?php echo $static_ticket->getLibStatut(1); ?></td>
                <?php if (empty($user->rights->pos->viewunclosedticket)): ?>
                <td>
                    <input type="checkbox" name="filters[checkbox][]" value="<?php echo $static_ticket->id; ?>" class="cbsls" onclick="switchCheckbox();" />
                </td>
                <?php endif; ?>
            </tr>
            <tr>
                <td colspan="9">
                    <table style="width:100%;">
                        <?php foreach ($static_ticket->lines as $line): ?>
                        <tr>    
                            <td><?php //var_dump($line);die(); //echo $row->ticketnumber; ?></td>
                            <td><?php //echo $ticket_status[$row->fk_statut]; ?></td>
                            <td><?php echo $line->product_label; ?></td>
                            <td style="width:120px;" class="<?php echo $evs[$line->ls_warehouse_status]; ?>"><?php echo $ev[$line->ls_warehouse_status]; ?></td>
                        </tr>
                        <?php endforeach;?>
                    </table>
                </td>
            </tr>
                
                
        <?php endwhile; ?>
        </tbody>
    </table>
</form>
