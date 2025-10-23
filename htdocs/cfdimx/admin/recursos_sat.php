<script>
    $(document).ready(function(){
        $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
    });
</script>
<?php
    global $db, $conf, $user;

    require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

    // $marcador = new Bookmark($db);

    $url_api        = "https://api-cfdi.auribox.com/recursos_sat";
    $lista_urls_api = getURLContent($url_api, 'GET', '', 1, array(), array('http', 'https'), 0);
    $lista_recursos = json_decode($lista_urls_api['content']);

    $action    = GETPOST('action');
	$valor_sel = GETPOST('valor');
    $proceso   = GETPOST('proceso');

    ##
	$titulo_ayuda_act    = "Agregar Marcadores";
	$titulo_ayuda_desact = "Eliminar Marcadores";
	$btn_act             = "on";
	$btn_desact          = "off";

    if($action == "add_mark"){
        dolibarr_set_const($db, "CFDIMX_MARCADORES", $valor_sel, 'chaine', 0, '', $conf->entity);

        $lista_errores = array();
        $num_errores   = 0;
        $mensaje       = -1;

        ##Eliminar marcadores
        if($valor_sel == 0){
            if(!is_null($lista_recursos) && count($lista_recursos) > 0){
                $mensaje = 1;
                $lista_marcadores = array();
                foreach ($lista_recursos as $recurso) {
                    $lista_marcadores[] = $recurso->posicion;
                }

                if(!is_null($lista_marcadores) && count($lista_marcadores) > 0){
                    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."bookmark";
		            $sql .= " WHERE position IN(".implode(",", $lista_marcadores).")";
                    $resql = $db->query($sql);

                    if(!$resql){
                        $lista_errores[] = "Error: No se pudieron Eliminar todos los marcadores";
                        $num_errores++;
                    }
                }
            }
        }else{
            ##Agregar marcadores
            if($valor_sel == 1){
                if(!is_null($lista_recursos) && count($lista_recursos) > 0){
                    $mensaje = 2;
                    foreach ($lista_recursos as $recurso) {

                        $marcador = new Bookmark($db);

                        $marcador->fk_user  = $user->id;
                        $marcador->url      = $recurso->url;
                        $marcador->target   = 1;
                        $marcador->title    = $recurso->nombre;
                        $marcador->favicon  = "none";
                        $marcador->position = $recurso->posicion;

                        $id_mark = $marcador->create();

                        if($id_mark < 0){
                            $lista_errores[] = "Error: No se puedo crear el Marcador '".$recurso->nombre."', con URL '".$recurso->url."'";
                            $num_errores++;
                        }
                    }
                }
            }
        }

        $_SESSION["lista_errores_rs"] = $lista_errores;
        $_SESSION["num_errores"] = $num_errores;
        $_SESSION["mensaje"] = $mensaje;

        print "<script>window.location='" . $_SERVER["PHP_SELF"] . "?mod=recursos_sat&proceso=1';</script>";
    }   

    if($proceso == 1){
        if(!is_null($_SESSION["lista_errores_rs"]) && count($_SESSION["lista_errores_rs"]) > 0){
            setEventMessage(implode("<br>", $_SESSION["lista_errores_rs"]), 'errors');
        }

        if($_SESSION["num_errores"] == 0){
            if($_SESSION["mensaje"] == 1){
                setEventMessage('Se eliminaron correctamente todos los marcadores.');
            }else{
                if($_SESSION["mensaje"] == 2){
                    setEventMessage('Se agregaron correctamente todos los marcadores.');
                }
            }
        }
    }

    $opc_marcadores = $conf->global->CFDIMX_MARCADORES;

    print '<div class="div-table-responsive">';
		print '<table class="noborder" width="100%">';
			print '<tbody>';
				print '<tr class="liste_titre">';
					print '<th align="center"><strong>Estatus</strong></th>';
					print '<th align="left"><strong>Funcionalidad</strong></th>';
				print '</tr>';

				print '<tr>';
					print '<td align="center">';
                        $texto = ($opc_marcadores == 1 ? 'Eliminar' : 'Agregar');
						if($opc_marcadores == 1){
							print '<a href="cfdimx.php?mod=recursos_sat&action=add_mark&valor=0">';
								print img_picto($titulo_ayuda_desact, $btn_act);
						}else{
							print '<a href="cfdimx.php?mod=recursos_sat&action=add_mark&valor=1">';
								print img_picto($titulo_ayuda_act, $btn_desact);
						}
						print '</a>';
					print '</td>';
					print '<td>'.$texto.' Marcadores SAT</td>';
				print '</tr>';
            print '</tbody>';
        print '</table>';
    print '</div>';

    if(!is_null($lista_recursos) && count($lista_recursos) > 0){
        print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
                print '<th>';
                    print '<span class="fa fa-globe valignmiddle btnTitle-icon"></span>';
                    print '&nbsp;';
                    print '<strong>Lista de Recursos Disponibles</strong>';
                print '</th>';
            print '</tr>';

            foreach ($lista_recursos as $recurso) {
                print '<tr>';
                    print '<td>';
                        print '<a class="dropdown-item bookmark-item bookmark-item-external" target="_blank" rel="noopener noreferrer" href="'.$recurso->url.'">';
                            print $recurso->nombre;
                        print '</a>';
                    print '</td>';
                print '</tr>';
            }




        print '</table>';
    }
