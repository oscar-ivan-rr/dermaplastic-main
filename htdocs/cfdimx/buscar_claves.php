<?php
	require('../main.inc.php');

	print '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">';
	print '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>';	

	print '<link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css" rel="stylesheet" crossorigin="anonymous" />';
	print '<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/js/all.min.js" crossorigin="anonymous"></script>';

	global $db,$conf;

	$action           = GETPOST('action');
	$buscar_estado    = GETPOST('estado') != '' ? GETPOST('estado') : '';
    $buscar_mpo       = GETPOST('mpo') != '' ? GETPOST('mpo') : '';
    $buscar_localidad = GETPOST('localidad') != '' ? GETPOST('localidad') : '';
    $buscar_cp        = GETPOST('cp') != '' ? GETPOST('cp') : '';
    $buscar_colonia   = GETPOST('colonia') != '' ? GETPOST('colonia') : '';
    $lista_claves     = null;

	if($action == 'buscar'){
		if($buscar_estado != '' || $buscar_mpo != ''){
			$select_mpios     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_municipio";

			if($buscar_estado != ''){
				$select_mpios .= " WHERE clave_estado = '".$buscar_estado."'";
			}

			if($buscar_mpo != ''){
				if($buscar_estado != ''){
					$select_mpios .= " AND descripcion like '%".$buscar_mpo."%'";
				}else{
					$select_mpios .= " WHERE descripcion like '%".$buscar_mpo."%'";
				}
			}

			$res_select_mpios = $db->query($select_mpios);
			$num_select_mpios = $db->num_rows($res_select_mpios);

			if($num_select_mpios > 0){
				while ($obj = $db->fetch_object($res_select_mpios)) {
					$lista_claves[] = array(
											'clave_estado'    => $obj->clave_estado,
											'mpio'            => $obj->descripcion,
											'clave_mpio'      => $obj->clave_mpio,
											'localidad'       => '',
											'clave_localidad' => '',
											'cp'              => '',
											'colonia'         => '',
											'clave_colonia'   => ''
											);
				}
			}
		}

		if($buscar_estado != '' || $buscar_localidad != ''){
			$select_localidades     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_localidad";

			if($buscar_estado != ''){
				$select_localidades .= " WHERE clave_estado = '".$buscar_estado."'";
			}

			if($buscar_localidad != ''){
				if($buscar_estado != ''){
					$select_localidades .= " OR descripcion like '%".$buscar_localidad."%'";
				}else{
					$select_localidades .= " WHERE descripcion like '%".$buscar_localidad."%'";
				}
			}

			$res_select_localidades = $db->query($select_localidades);
			$num_select_localidades = $db->num_rows($res_select_localidades);

			if($num_select_localidades > 0){
				while ($obj = $db->fetch_object($res_select_localidades)) {
					$lista_claves[] = array(
											'clave_estado'    => $obj->clave_estado,
											'mpio'            => '',
											'clave_mpio'      => '',
											'localidad'       => $obj->descripcion,
											'clave_localidad' => $obj->clave_localidad,
											'cp'              => '',
											'colonia'         => '',
											'clave_colonia'   => ''
											);
				}
			}
		}

		if($buscar_cp != '' || $buscar_colonia != ''){
			$select_colonias     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_colonia";

			if($buscar_cp != ''){
				$select_colonias .= " WHERE codigo_postal = '".$buscar_cp."'";
			}

			if($buscar_colonia != ''){
				if($buscar_cp != ''){
					$select_colonias .= " OR descripcion like '%".$buscar_colonia."%'";
				}else{
					$select_colonias .= " WHERE descripcion like '%".$buscar_colonia."%'";
				}
			}

			$res_select_colonias = $db->query($select_colonias);
			$num_select_colonias = $db->num_rows($res_select_colonias);

			if($num_select_colonias > 0){
				while ($obj = $db->fetch_object($res_select_colonias)) {
					$lista_claves[] = array(
											'clave_estado'    => '',
											'mpio'            => '',
											'clave_mpio'      => '',
											'localidad'       => '',
											'clave_localidad' => '',
											'cp'              => $obj->codigo_postal,
											'colonia'         => $obj->descripcion,
											'clave_colonia'   => $obj->clave_colonia
											);
				}
			}
		}
	}

	print '<div class="container">';
		print '<div class="row">';
			print '<div class="col-12 col-md-12">';
				print '<center><label class="form-label"><strong>Buscador de Códigos</strong></label></center>';
			print '</div>';

			print '<div class="col-1 col-md-1">';
		    	print '&nbsp;';
		    print '</div>';

		    print '<div class="col-10 col-md-10">';
		    	print '<form class="row g-3 needs-validation" novalidate>';
		    		print '<input type="hidden" id="action" name="action" value="buscar">';

					print '<div class="col-md-4">';
					    print '<label class="form-label"><strong>Estado</strong></label>';
					    print '<select class="form-control" id="estado" name="estado">';
						    print '<option value="">--Seleccione Estado--</option>';
						    
						    $select_estados     = "SELECT * FROM ".MAIN_DB_PREFIX."c_cfdimx_estado";
						    $res_select_estados = $db->query($select_estados);
						    $num_select_estados = $db->num_rows($res_select_estados);

						    if($num_select_estados > 0){
						    	while($obj_estado = $db->fetch_object($res_select_estados)){
						    		$selected = '';
						    		if($buscar_estado == $obj_estado->clave_estado){
						    			$selected = " selected";
						    		}

						    		print '<option value="'.$obj_estado->clave_estado.'" '.$selected.'>'.$obj_estado->descripcion.'</option>';
						    	}
						    }
						print '</select>';

				  	print '</div>';
				  	print '<div class="col-md-4">';
				    	print '<label class="form-label"><strong>Municipio</strong></label>';
				    	print '<input type="text" class="form-control" id="mpo" name="mpo" placeholder="Abasolo" value='.$buscar_mpo.'>';
				  	print '</div>';
				  	print '<div class="col-md-4">';
				    	print '<label class="form-label"><strong>Localidad</strong></label>';
				    	print '<input type="text" class="form-control" id="localidad" name="localidad" placeholder="San Francisco de Campeche" value='.$buscar_localidad.'>';
				  	print '</div>';

				  	print '<div class="col-md-4">';
				    	print '<label class="form-label"><strong>C.P.</strong></label>';
				    	print '<input type="text" class="form-control" id="cp" name="cp" placeholder="30907" value='.$buscar_cp.'>';
				  	print '</div>';

				  	print '<div class="col-md-4">';
				    	print '<label class="form-label"><strong>Colonia</strong></label>';
				    	print '<input type="text" class="form-control" id="colonia" name="colonia" placeholder="Rancheria El Carmen" value='.$buscar_colonia.'>';
				  	print '</div>';
				 
				  	print '<div class="col-12">';
				    	print '<button class="btn btn-primary" type="submit">Buscar</button>';
				  	print '</div>';
				print '</form>';
		    print '</div>';
		    print '<div class="col-1 col-md-1">';
		    	print '&nbsp;';
		    print '</div>';
		print '</div>';
	print '</div>';

	if($lista_claves != null){
		print '<div class="container">';
			print '<div class="row">';
				print '<div class="col-12 col-md-12">';
					print '<center><label class="form-label"><strong>Lista de Códigos</strong></label></center>';
				print '</div>';

			    print '<div class="col-12 col-md-12">';
			    	print '<div class="card mb-4">';
				        print '<div class="card-body">';
				            print '<div class="table-responsive">';
				                print '<table class="table table-striped" id="listaClaves" width="100%" cellspacing="0">';
				                    print '<thead>';
				                        print '<tr>';
				                            // print '<th>Estado</th>';
				                            print '<th>Clave Estado</th>';
				                            print '<th>Municipio</th>';
				                            print '<th>Clave Municipio</th>';
				                            print '<th>Localidad</th>';
				                            print '<th>Clave Localidad</th>';
											print '<th>C.P.</th>';
				                            print '<th>Colonia</th>';
				                            print '<th>Clave Colonia</th>';
				                        print '</tr>';
				                    print '</thead>';

				                    print '<tbody>';
										foreach ($lista_claves as $clave) {
											print '<tr>';
					                    		print '<td>'.$clave["clave_estado"].'</td>';
					                    		print '<td>'.$clave["mpio"].'</td>';
					                    		print '<td>'.$clave["clave_mpio"].'</td>';
					                    		print '<td>'.$clave["localidad"].'</td>';
					                    		print '<td>'.$clave["clave_localidad"].'</td>';
					                    		print '<td>'.$clave["cp"].'</td>';
					                    		print '<td>'.$clave["colonia"].'</td>';
					                    		print '<td>'.$clave["clave_colonia"].'</td>';
					                    	print '</tr>';
										}
				                    print '</tbody>';
				                print '</table>';
				            print '</div>';
				        print '</div>';
				    print '</div>';
			    print '</div>';

			print '</div>';
		print '</div>';
	}else{
		if($action == 'buscar'){
			print '<div class="col-12 col-md-12">';
				print '<center><label class="form-label"><strong>No se encontró ninguna clave, intenta cambiando los filtros.</strong></label></center>';
			print '</div>';
		}
	}

	print '<script src="https://code.jquery.com/jquery-3.5.0.js"></script>';
    print '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>';
    print '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>';
    print '<script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>';
    print '<script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js" crossorigin="anonymous"></script>';

    ?>
    	<script type="text/javascript">
            $(document).ready(function() {

                $( '#listaClaves' ).DataTable({
                    "aoColumnDefs": [
                        { 'bSortable': false, 'aTargets': [ 3 ] }
                    ],
                    "lengthMenu": [ 20, 50, 100, 200 ],
                    "order": [[ 0, "asc" ]],
                    "language": {
                        "lengthMenu": "Mostrar _MENU_ Claves",
                        "zeroRecords": "No se encontraron Claves",
                        "info": "Página _PAGE_ de _PAGES_",
                        "infoEmpty": "No hay Claves para mostrar",
                        "infoFiltered": "(filtrados de _MAX_ resultados totales)",
                        "oPaginate": {
                            "sFirst": "Primero",
                            "sLast": "Último",
                            "sNext": "Siguiente",
                            "sPrevious": "Anterior"
                        },
                        "search": "Buscar "
                    }           
                });
            });
        </script>
    <?php
?>