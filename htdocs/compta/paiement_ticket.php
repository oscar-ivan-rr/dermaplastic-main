<?php
    if (is_array($t_amounts))
    {
        $t_massive_ids = array();
        foreach ($t_amounts as $t_key =>$t_value )
        {
            $t_massive_ids[] = $t_key;
        }
    }

//if (is_array($t_massive_ids) && count($t_massive_ids))
//die(var_dump($t_amounts));
if (true)
{
    require_once (DOL_DOCUMENT_ROOT.'/custom/pos/class/ticket.class.php');
    foreach ($t_massive_ids as $t_massive_id)
    {
        $soc = new Societe($db);
		$soc->fetch($facture->socid);

        $creditnotes = 0;
        $rc_static_ticket = new Ticket($db);
        $rc_static_ticket->fetch($t_massive_id);
        if($rc_static_ticket->type == 1)
        {
            $rc_static_ticket->total_ttc = abs($rc_static_ticket->total_ttc) * -1; 
        }
        if($massive_payment && $soc->earlypayment_discount && $rc_static_ticket->type != 1) 
        {
            $ep_discount = $rc_static_ticket->diff_payment * $soc->earlypayment_discount / 100;
            //die (var_dump($rc_static_ticket->diff_payment,$rc_static_ticket->total_ttc - $rc_static_ticket->customer_pay ,$ep_discount));
            $after_discount = $rc_static_ticket->diff_payment - $ep_discount;
        }
        else
        {
            $ep_discount = 0;
            $after_discount = $rc_static_ticket->diff_payment;
        }
        echo '<tr class="oddeven">';
        echo '<td class="nowraponall">'.$rc_static_ticket->getNomUrl(1).'</td>';
        echo '<td class="center">'.date('d/m/Y',$rc_static_ticket->date_creation).'</td>';
        echo '<td class="center">'.''.'</td>';
        echo '<td class="right">'.price($rc_static_ticket->total_ttc).'</td>';
        echo '<td class="right">'.price($ep_discount).'</td>';
        echo '<td class="right">'.price($rc_static_ticket->customer_pay).'</td>';
        $remaintopay = price2num($after_discount,'MT');
        echo '<td class="right">'.price($remaintopay).'</td>';
        echo '<td class="right nowraponall">';
        $namef = 't_amount_'.$rc_static_ticket->id;
        $nameRemain = 't_remain_'.$rc_static_ticket->id;
        if ($action != 'add_paiement')
        {
            if (!empty($conf->use_javascript_ajax))
                print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $remaintopay)."'");

                if($massive_payment)
                {
                    if(in_array($rc_static_ticket->id, $t_massive_ids))
                    {
                        print '<input type="text" class="maxwidth75 amount" name="'.$namef.'" value="'.$remaintopay.'">';
                    }
                    else
                    {
                        print '<input type="text" class="maxwidth75 amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                    }
                }
                else
                {
                    print '<input type="text" class="maxwidth75 amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                }


                print '<input type="hidden" class="remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
            }
            else
            {
                print '<input type="text" class="maxwidth75" name="'.$namef.'_disabled" value="'.dol_escape_htmltag(GETPOST($namef)).'" disabled>';
                print '<input type="hidden" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
            }
        echo '</td>';
        echo '</tr>';
                    $total += $rc_static_ticket->total_ttc;
                    $total_ttc += $rc_static_ticket->total_ttc;
                    $totalrecu += $rc_static_ticket->customer_pay;
                    $totalrecucreditnote += $creditnotes;
                    //$totalrecudeposits += $rc_static_ticket->customer_pay;
                    $total_discounted = $total_discounted + $ep_discount;
                    $i++;
    }
    
}