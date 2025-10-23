<?php
/**
 * @author admin
 * @copyright 2022
 */

$debug	=	true; 

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

$mov = GETPOST('mov','array');
if (is_array($mov) && count($mov))
{
    $inv_code = 'RMD'.date('YmdHis');
    $err = 0;
    foreach($mov as $mv)
    {
        $db->begin();
        $mv_old = new MouvementStock($db);
        if (!$mv_old->fetch($mv))
        {
            setEventMessages("No fue posible traer la información del movimiento {$mv}.", null, 'errors');
            continue;
        }
        if (strpos($mv_old->label,'(revertido)'))
        {
            setEventMessages("El movimiento {$mv} ya fue revertido.", null, 'errors');
            continue;
        }
        $mv_new = new MouvementStock($db);
        switch ($mv_old->type)
        {
            case 0:
                $mv_new->type = 1;
                break;
            case 1:
                $mv_new->type = 0;
                break;
            case 2:
                $mv_new->type = 3;
                break;
            case 3:
                $mv_new->type = 2;
                break;
            default:
                setEventMessages("El movimiento {$mv} no tiene un tipo válido.", null, 'errors');
                continue;
                break;
        }
        $mv_new->qty            = $mv_old->qty * -1;
        $mv_new->product_id     = $mv_old->product_id;
        $mv_new->warehouse_id   = $mv_old->warehouse_id;
        $mv_new->price          = $mv_old->price;
        $mv_new->fk_user_author = $user->id;
        $mv_new->label          = $mv_old->label.' (revertido)';
        $mv_new->fk_origin      = $mv_old->fk_origin;
        $mv_new->origintype     = $mv_old->origintype;
        $mv_new->batch          = $mv_old->batch;
        $mv_new->inventorycode  = $inv_code;
        if (!$mv_new->_create(
                                 $user
                                ,$mv_old->product_id
                                ,$mv_old->warehouse_id
                                ,$mv_new->qty
                                ,$mv_new->type
                                ,$mv_new->price
                                ,$mv_new->label
                                ,$mv_new->inventorycode
                                ,null
                                ,null
                                ,null
                                ,null
                                ,null
                                ,null
                                ,0
                            )
            )
        {
            setEventMessages("No se pudo crear el inverso del movimiento {$mv}.", null, 'errors');
            continue;
        }
        $sql0 =  'UPDATE llx_stock_mouvement '."\r\n"
                .'SET    label =\''.$mv_old->label.' (revertido)'.'\' '."\r\n"
                .'      ,inventorycode=\''.$inv_code.'\' '."\r\n"
                .'WHERE rowid='.$mv.' '."\r\n"
                .''
                ;
        if (!$db->query($sql0))
        {
            setEventMessages("No se pudo actualizar el movimiento {$mv}.", null, 'errors');
            $db->rollback();
        }
        else
        {
            $db->commit();
        }
    }
    header('Location:'.$_SERVER['PHP_SELF']);
}


llxHeader("", 'Movimientos de stock con etiqueta repetida', '');

$sql1 =  "SELECT "."\r\n"
        ."COUNT(sm.rowid) AS Repeticiones,  "."\r\n"
        ."sm.label AS Etiqueta,  "."\r\n"
        ."sm.datem AS Fecha,  "."\r\n"
        ."sm.fk_product AS 'ID_Producto',  "."\r\n"
        ."p.ref AS 'Ref. Producto',  "."\r\n"
        ."p.label AS Producto,  "."\r\n"
        ."sm.value AS Cantidad, "."\r\n"
        ."((COUNT(sm.rowid) -1) * sm.value * -1) AS Corrección "."\r\n"
        ."FROM llx_stock_mouvement as sm "."\r\n"
        ."LEFT JOIN llx_product as p "."\r\n"
        ."  ON p.rowid = sm.fk_product "."\r\n"
        ."WHERE sm.label LIKE 'Ticket %' "."\r\n"
        ."AND sm.label NOT LIKE '%(revertido)' "."\r\n"
        ."GROUP BY sm.label,sm.fk_product, sm.value "."\r\n"
        ."HAVING repeticiones > 1 "."\r\n"
        ."";
    //die($sql1);
if (!$res1 =$db->query($sql1))
{
    dol_print_error($db);
    die();
}
?>
<h1>Movimientos de stock con etiqueta repetida</h1>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">

<table>
    <tr>
        <th style="text-align:left;">Etiq. del Mov.</th>
        <th>Ref.Prod.</th>
        <th>Fecha</th>
        <th>Etiq. del Prod.</th>
        <th>Cant.</th>
        <th><input type="submit" value="Enviar" name="submit" /></td>
    </tr>

    <?php while ($row1 = $db->fetch_object($res1)): ?>
        
        <?php 
            $sql2 =  "SELECT sm.label as mov_label  "."\r\n"
                    ."      ,sm.rowid as mov_id "."\r\n"
                    ."      ,sm.value as mov_qty "."\r\n"
                    ."      ,sm.datem as mov_date "."\r\n"
                    ."      ,p.rowid as prod_id "."\r\n"
                    ."      ,p.ref as prod_ref "."\r\n"
                    ."      ,p.label as prod_label "."\r\n"
                    ."FROM llx_stock_mouvement as sm "."\r\n"
                    ."LEFT JOIN llx_product as p "."\r\n"
                    ."  ON p.rowid = sm.fk_product "."\r\n"
                    ."WHERE sm.label = '{$row1->Etiqueta}' "."\r\n"
                    ."  AND sm.fk_product = {$row1->ID_Producto} "."\r\n"
                    ;
            if (!$res2 = $db->query($sql2))
            {
                dol_print_error($db);
                die();
            }
            $is_first = true;
            $has_second = false;
            while ($row2 = $db->fetch_object($res2)):
                if ($is_first)
                {
                    $is_first = false;
                }
                else
                {
                    $has_second =true;
                }
                ?>
                <tr>
                    <td><a href="<?php echo DOL_URL_ROOT;?>/product/stock/movement_list.php?search_movement=<?php echo $row2->mov_label;?>&sortfield=p.ref&sortorder=asc"><?php echo $row2->mov_label;?></a></td>
                    <td><a href="<?php echo DOL_URL_ROOT;?>/product/stock/movement_list.php?search_movement=<?php echo $row2->mov_label;?>&search_product_ref=<?php echo $row2->prod_ref;?>"><?php echo $row2->prod_ref;?></a></td>
                    <td><?php echo $row2->mov_date;?></td>
                    <td><?php echo $row2->prod_label;?></td>
                    <td><?php echo $row2->mov_qty;?></td>
                    <td><input type="checkbox" name="mov[]" value="<?php echo $row2->mov_id;?>"></td>
                </tr>
            <?php endwhile; ?>
    <?php endwhile; ?>
</table>
