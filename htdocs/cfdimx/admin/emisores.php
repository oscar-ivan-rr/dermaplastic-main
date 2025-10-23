<script>
  $(document).ready(function(){ 
    $('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
  });
</script>

<?php
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

	global $db, $conf;

	$action=GETPOST('action');
	$confirm=GETPOST('confirm');
	$form=new Form($db);
	$formcompany=new FormCompany($db);

	/* ************************************************************************** */
	/*                                                                            */
	/* Actions                        											  */
	/*                                                                            */
	/* ************************************************************************** */

	if($action=='add'){

		$sql='SELECT count(*) as exist FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE entity_id='.$conf->entity.' AND emisor_rfc="'.GETPOST('rfc').'"';
		//echo $sql;
		$td=$db->query($sql);
		$tg=$db->fetch_object($td);
		
		if($tg->exist==0){
			
			$tmparray=getCountry(GETPOST('country_id','int'),'all',$db,$langs,0);
			$country_id   =$tmparray['id'];
			$country_code =$tmparray['code'];
			$country_label=$tmparray['label'];
			$str_country=$country_id.':'.$country_code.':'.$country_label;

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp (';
			$sql.=' emisor_rfc';
			$sql.=', razon_social';
			$sql.=', regimen';
			$sql.=', pais';
			$sql.=', estado';
			$sql.=', codigo_postal';
			$sql.=', emisor_delompio';
			$sql.=', emisor_colonia';
			$sql.=', emisor_calle';
			$sql.=', emisor_noext';
			$sql.=', emisor_noint';
			$sql.=', entity_id';
			$sql.=', cod_municipio';
			$sql.=', cod_colonia';
			$sql.=', password_timbrado';
			$sql.=', password_timbrado_txt';
			$sql.=', formato_cfdi';
			$sql.=', modo_timbrado';
			$sql.=', config_seriefolio';
			$sql.=', status_conf';
			$sql.=', predeterminado';
			$sql.=')';
			$sql.= ' VALUES (';
			$sql.= '"'.GETPOST('rfc').'"';
			$sql.= ', "'.GETPOST('razonsoc').'"';
			$sql.= ', "'.GETPOST('forme_juridique_code').'"';
			$sql.= ', "'.$str_country.'"';
			$sql.= ', "'.GETPOST('state_id').'"';
			$sql.= ', "'.GETPOST('cgpostal').'"';
			$sql.= ', "'.GETPOST('delompio').'"';
			$sql.= ', "'.GETPOST('colonia').'"';
			$sql.= ', "'.GETPOST('calle').'"';
			$sql.= ', "'.GETPOST('noext').'"';
			$sql.= ', "'.GETPOST('noint').'"';
			$sql.= ', '.$conf->entity;
			$sql.= ', ""';
			$sql.= ', ""';
			$sql.= ', "'.md5(GETPOST('passwt')).'"';
			$sql.= ', "'.GETPOST('passwt').'"';
			$sql.= ', "standard"';
			$sql.= ', "1"';
			$sql.= ', "1"';
			$sql.= ', "1"';
			$sql.= ', 0';
			$sql.= ')';
			//echo $sql."<br>";
			$tf=$db->query($sql);
			if ($tf) {
				setEventMessage('Emisor registrado correctamente.');
			}
		}
		else{
			setEventMessage('Error: el RFC ingresado ya se encuentra registrado.', 'errors');
		}
	}

	if($action=='confirm_delete' && $confirm=='yes') {
		$sql="SELECT predeterminado FROM ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp WHERE entity_id=".$conf->entity." AND rowid=".GETPOST("id");
		//echo $sql."<br>";
		$qt=$db->query($sql);
		$qw=$db->fetch_object($qt);

		if($qw->predeterminado==1){
			setEventMessage('Error: no es posible eliminar el emisor predeterminado.', 'errors');
		}
		else{
			$sql_del='DELETE FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE rowid='.GETPOST("id");
			$qt_del=$db->query($sql_del);
			if ($qt_del) {
				setEventMessage('Emisor eliminado correctamente.');
			}
		}
	}

	if($action=='predeterminado'){
		$entity=GETPOST('entity');
		$rfc=GETPOST('rfc');
		$sql='SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp where entity_id='.$entity.'  AND emisor_rfc="'.$rfc.'"';
		$cp=$db->query($sql);
		if($cp){
			$emisor=$db->fetch_object($cp);
		}
		$sql='UPDATE '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp set predeterminado =0 where entity_id='.$entity;
		$cp=$db->query($sql);
		$sql='UPDATE '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp set predeterminado =1 where entity_id='.$entity.'  AND emisor_rfc="'.$rfc.'"';
		$cs=$db->query($sql);
		$sql='SELECT count(*)as num FROM '.MAIN_DB_PREFIX.'cfdimx_config WHERE emisor_rfc="'.$rfc.'" AND entity='.$entity;
		$cs=$db->query($sql);
        if($cs){
			$resp=$db->fetch_object($cs);
			if($resp->num==0){
				$sql='INSERT INTO '.MAIN_DB_PREFIX.'cfdimx_config (emisor_rfc, password_timbrado, password_timbrado_txt, formato_cfdi, modo_timbrado, config_seriefolio, status_conf, entity_id)';
				$sql.=' VALUES("'.$emisor->emisor_rfc.'","'.$emisor->password_timbrado.'","'.$emisor->password_timbrado_txt.'","standard", 1, 1, 1, '.$entity.')';
					//print $sql."<br>";
					$cs=$db->query($sql);
				$sql='INSERT INTO '.MAIN_DB_PREFIX.'cfdimx_config_ws (emisor_rfc, ws_modo_timbrado, ws_pruebas, ws_produccion, ws_status_conf, entity_id) VALUES("'.$emisor->emisor_rfc.'",1,"","","","'.$entity.'")';
				$cs=$db->query($sql);
			}
		}
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE entity_id='.$entity.' AND emisor_rfc="'.$rfc.'"';
		//print $sql."<br>";
		$r=$db->query($sql);
		if ($db->num_rows($r) > 0) {
			$res=$db->fetch_object($r);

			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->emisor_rfc."' WHERE name='MAIN_INFO_SIREN' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
			
			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->regimen."' WHERE name='MAIN_INFO_SOCIETE_FORME_JURIDIQUE' OR name='CFDIMX_REGIMEN_FISCAL' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
			
			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->razon_social."' WHERE name='MAIN_INFO_SOCIETE_NOM' AND entity=".$conf->entity;
			//print $sql."<br>";
			$cs=$db->query($sql);

			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->razon_social."' WHERE name='CFDIMX_RAZON_SOCIAL' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
			
			$estado = explode(":", $res->estado)[1];
			$direccion_int = $res->emisor_noint ? " INT. " . $res->emisor_noint : "";
			dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", $direccion, 'chaine', 1, '', $entity);
			$direccion = $res->emisor_calle . " NO. " . $res->emisor_noext . $direccion_int . " COL. " . $res->emisor_colonia . ", " . $res->emisor_delompio . ", " . $estado;
			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$direccion."' WHERE name='CFDIMX_DIRECCION' OR name='MAIN_INFO_SOCIETE_ADDRESS' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);

			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->pais."' WHERE name='MAIN_INFO_SOCIETE_COUNTRY' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
			
			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->estado."' WHERE name='MAIN_INFO_SOCIETE_STATE' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);

			$sql="UPDATE ".MAIN_DB_PREFIX."const SET value='".$res->codigo_postal."' WHERE name='MAIN_INFO_SOCIETE_ZIP' AND entity=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
			
			$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp SET predeterminado=0 WHERE entity_id=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
			
			$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp SET predeterminado=1 WHERE emisor_rfc='".$rfc."' AND entity_id=".$entity;
			//print $sql."<br>";
			$cs=$db->query($sql);
		}
		setEventMessage('Emisor predeterminado actualizado correctamente.');
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* View                                                                       */
	/*                                                                            */
	/* ************************************************************************** */


	if(isset($conf->global->MAIN_INFO_SIREN) && isset($conf->global->MAIN_INFO_SOCIETE_NOM) && $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE!=0 ){
		
		#Formconfirm actions
		if ($action == 'delete') {
			$rfc = GETPOST('rfc');
			$id = GETPOST('id');
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?mod=emisores&id='.$id, $langs->trans('Eliminar emisor'), $langs->trans('¿Desea eliminar el emisor <strong>'.$rfc.'</strong> ?'), 'confirm_delete', '', 0, 1);
	        print $formconfirm;
	    }

		#Esta funcionalidad genera la lista de emisores (Inicio)
		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_emisor_datacomp WHERE entity_id='.$conf->entity;
		$r=$db->query($sql);
		$out_liste = ""; $detalle_emi="";
		if ($db->num_rows($r) > 0) {
			$class_table = '';
			$con_table = 0;
			while($res = $db->fetch_object($r)){

				// print '<pre>'; print_r($res); print '</pre>';

				$regimen = getFormeJuridiqueLabel($res->regimen);
				$separa_estado = explode(":", $res->estado);
				$estado = ($separa_estado[0]!="") ? getState($separa_estado[0]) : "";
				$tmp=explode(':',$res->pais); $country_id=$tmp[0]; $country_code=$tmp[1]; $country=$tmp[2];
				$detalle_emi = '<table width="100%" class="liste">
					<tr>
						<td width="30%"><strong>RFC</strong></td>
						<td><strong>'.$res->emisor_rfc.'</strong></td>
					</tr>
					<tr>
					    <td><strong>Regimen</strong></td>
					    <td>'.$regimen.'</td>
					</tr>
					<tr>
					    <td><strong>Razon Social</strong></td>
					    <td>'.$res->razon_social.'</td>
					</tr>
					<tr>
					    <td><strong>Pais</strong></td>
					    <td>'.$country.'</td>
					</tr>
					<tr>
					    <td><strong>Estado</strong></td>
					    <td>'.$estado.'</td>
					</tr>
					<tr>
					    <td><strong>Codigo Postal</strong></td>
					    <td>'.$res->codigo_postal.'</td>
					</tr>
					<tr>
					    <td><strong>Delegacion o Municipio</strong></td>
					    <td>'.$res->emisor_delompio.'</td>
					</tr>
					<tr>
					    <td><strong>Colonia</strong></td>
					    <td>'.$res->emisor_colonia.'</td>
					</tr>
					<tr>
					    <td><strong>Calle</strong></td>
					    <td>'.$res->emisor_calle.'</td>
					</tr>
					<tr>
					    <td><strong>No. Exterior.</strong></td>
					    <td>'.$res->emisor_noext.'</td>
					</tr>
					<tr>
					    <td><strong>No. Interior</strong></td>
					    <td>'.$res->emisor_noint.'</td>
					</tr>
					<tr>
					    <td><strong>Password para Timbrar</strong></td>
					    <td>'.$res->password_timbrado_txt.'</td>
					</tr>
				</table>';

				if($con_table == 0){
					$class_table = 'class="impair"';
					$con_table = 1;
				}else{
					$class_table = 'style="background: #e8edf3c7 !important; color: black !important;"';
					$con_table = 0;
				}
				$out_liste .= '<tr '.$class_table.'>';
					if($res->predeterminado==1){
						$out_liste .= '<td>'.img_picto('Emisor predeterminado', 'switch_on').'</td>';
					}else{
						$out_liste .= '<td><a href="'.$_SERVER["PHP_SELF"].'?mod=emisores&action=predeterminado&rfc='.$res->emisor_rfc.'&entity='.$res->entity_id.'" title="Predeterminado">'.img_picto('Establecer como predeterminado', 'switch_off').'</a></td>';
					}
					$out_liste .= '<td><strong>'.$res->emisor_rfc.'</strong></td>';
					$regimen = getFormeJuridiqueLabel($res->regimen);
					$out_liste .= '<td>'.$regimen.'</td>';
					$out_liste .= '<td>'.$res->razon_social.'</td>';
					$out_liste .= '<td>'.$res->codigo_postal.'</td>';
					$out_liste .= '<td>'.$form->textwithpicto('', $detalle_emi, 1, 'list','', 0, 3, '').'</td>';
					//$out_liste .= '<td><a href="" title="">'.img_edit('Editar').'</a></td>';
					$out_liste .= '<td><a href="'.$_SERVER["PHP_SELF"].'?mod=emisores&action=delete&id='.$res->rowid.'&rfc='.$res->emisor_rfc.'" title="">'.img_delete('Eliminar').'</a></td>';
				$out_liste .= '</tr>';
			}

			print '<div class="div-table-responsive">
			    <table class="noborder" width="100%">
			        <thead>
			            <tr class="liste_titre">
			                <th align="center"><strong>Predet.</strong></th>
			                <th align="left"><strong>RFC</strong></th>
			                <th align="left"><strong>Régimen</strong></th>
			                <th align="left" width="40%"><strong>Razón Social</strong></th>
			                <th align="left"><strong>Código Postal</strong></th>
			                <th align="center" colspan="2" width="10%"><strong>Acción</strong></th>
			            </tr>
			        </thead>
			        <tbody>
				        '.$out_liste.'
			        </tbody>
			    </table>
			</div>';
			#Esta funcionalidad genera la lista de emisores (Fin)

			#Formulario para agregar nuevo Emisor (Inicio)
			if ($action == 'create')
			{
				//print '<br><div id="newattrib"></div>';
			    //rint load_fiche_titre($langs->trans('Nuevo Emisor'));
			    
			    if(GETPOST('country_id')){
					$varc=GETPOST('country_id');
				}else{
					$varc=$mysoc->country_id;
				}
				if(GETPOST('forme_juridique_code')){
					$vfomj=GETPOST('forme_juridique_code');
				}else{
					$vfomj=$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
				}
				$tmparray=getCountry($varc,'all',$db,$langs,0);
				$country_code =$tmparray['code'];

			    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?mod=emisores" method="post">';
					print '<input type="hidden" name="action" value="add">';
					print '<div class="tabs" data-role="controlgroup" data-type="horizontal"></div>';

					print '<div class="tabBar tabBarWithBottom">';
						print '<br><br><br><table summary="listofattributes" class="noborder">';
						 	print '<tr style="background: rgb(215,215,215) !important;">';
					            print '<td align="left" colspan="2"><strong>Nuevo Emisor</strong></td>';
					        print '</tr>';
						    print '<tr>';
								print '<td width="30%" class="fieldrequired">RFC</td>';
								print '<td><input type="text" name="rfc" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Régimen</td>';
								print '<td>';
								print $formcompany->select_juridicalstatus($vfomj,$mysoc->country_code);
								print '</td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Razón Social</td>';
								print '<td><input type="text" name="razonsoc" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Pais</td>';
								print '<td>';
								//print $form->select_country($varc,'country_id',' onchange=" window.location =\' '.$_SERVER["PHP_SELF"].'?mod=emisores&country_id=\'+this.options[this.selectedIndex].value"');
								print $form->select_country($varc,'country_id');
								print '</td>';
							print '</tr>';
							print '<tr class="impair">';
								print '<td class="fieldrequired">Estado</td>';
								print '<td><div id="estdiv" name="estdiv">';
								$formcompany->select_departement($conf->global->MAIN_INFO_SOCIETE_STATE,$country_code,'state_id');
								print '</div></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Código Postal</td>';
								print '<td><input type="text" name="cgpostal" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Delegación o Municipio</td>';
								print '<td><input type="text" name="delompio" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Colonia</td>';
								print '<td><input type="text" name="colonia" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Calle</td>';
								print '<td><input type="text" name="calle" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">No.exterior</td>';
								print '<td><input type="text" name="noext" required></td>';
							print '</tr>';
							print '<tr>';
								print '<td>No.interior</td>';
								print '<td><input type="text" name="noint"></td>';
							print '</tr>';
							print '<tr>';
								print '<td class="fieldrequired">Password para Timbrar</td>';
								print '<td><input type="text" name="passwt" required></td>';
							print '</tr>';
					    print '</table>';
					print '</div>';

				    print '<div class="center">';
				        print '<input type="submit" name="button" class="button" value="Registrar">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				        print '<input type="button" name="cancel" class="button" value="Cancelar" onclick="location.href=\''.$_SERVER["PHP_SELF"].'?mod=emisores\'">';
				    print '</div>';
				print '</form>';
			}
			else {
				print '<div class="tabsAction">
					<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?mod=emisores&action=create">Nuevo emisor</a>
				</div>';
			}
		}else{
			print img_warning() . ' ' . '<font class="error">Complete la configuración de la pestaña <i>Datos del Emisor</i> para continuar.</font>';
		}
		#Formulario para agregar nuevo Emisor (Inicio)
	}
	else{
		print img_warning().'<p style="color:red;">Debe ingresar los valores mínimos necesarios<strong>(RFC, Razón social, forma jurídica)</strong> en el área de Configuración->Empresa/Organización de dolibarr.</p>';
	}


?>
