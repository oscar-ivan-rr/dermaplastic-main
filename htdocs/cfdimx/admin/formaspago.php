<script>
	$(document).ready(function(){
		$('div.tabBarWithBottom').removeClass('tabBarWithBottom'); // to be able to be effective the liste_titre class and oddeven !!
	});
</script>

<?php
	global $langs, $conf;

	$langs->loadLangs(array('bills','companies','compta','products','banks','main','withdrawals'));

	if($_REQUEST['actualiza']){
		//print_r($_REQUEST);
		$sql="SELECT * FROM ".MAIN_DB_PREFIX."c_paiement WHERE active=1 AND id > 0 AND entity = ".$conf->entity;
		$req=$db->query($sql);
		while($rs=$db->fetch_object($req)){
			if($_REQUEST['clv_'.$rs->code]!=NULL && trim($_REQUEST['clv_'.$rs->code])!=""){
				//print "ASD::".$_REQUEST['clv_'.$rs->code]."<br>";
				$upd="UPDATE ".MAIN_DB_PREFIX."c_paiement SET accountancy_code='".trim($_REQUEST['clv_'.$rs->code])."' WHERE code='".$rs->code."'";
				//print $upd."<br>";
				$rq=$db->query($upd);
			}
		}
		print "<script>window.location.href='cfdimx.php?mod=formaspago&val=1'</script>";
	}

	if($_REQUEST['otros']){
		// print '<pre>'; print_r($_REQUEST); print '</pre>';

		if(count($_REQUEST["fp_code"]) > 0){
			for ($i=0; $i < count($_REQUEST["fp_code"]); $i++) {
				$insert_f_pago = '';
				$insert_f_pago = "INSERT INTO ".MAIN_DB_PREFIX."c_paiement";
				$insert_f_pago .= " (entity, code, libelle, type, active, accountancy_code)";
				$insert_f_pago .= " VALUES";
				$insert_f_pago .= " (";
				$insert_f_pago .= "'".$conf->entity."',";
				$insert_f_pago .= "'".$_REQUEST["fp_code"][$i]."',";
				$insert_f_pago .= "'".$_REQUEST["libelle"][$i]."',";
				$insert_f_pago .= " 2,";
				$insert_f_pago .= " 1,";
				$insert_f_pago .= "'".$_REQUEST["clv"][$i]."'";
				$insert_f_pago .= " )";

				$res = $db->query($insert_f_pago);
			}
		}
	}

	if(GETPOST("val") != ""){
        if(GETPOST("val") == 1){
            setEventMessage('Claves de Formas de Pago actualizadas.');
        }else{
            if(GETPOST("val") == 0){
                setEventMessage('Error al actualizar las Claves de Formas de Pago.', 'errors');
            }
        }
    }

	print '<form method="POST" action="cfdimx.php">';
		print '<input type="hidden" name="mod" id="mod" value="formaspago">';
		print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
		print '<table width="100%" class="noborder">';
			print '<thead>';
				print '<tr class="liste_titre">';
					print '<td width="33%" align="center"><strong>C&oacute;digo Dolibarr</strong></td>';
					print '<td width="33%" align="left"><strong>Etiqueta</strong></td>';
					print '<td width="33%" align="center"><strong>Clave SAT</strong></td>';
				print '</tr>';
			print '</thead>';
			$arreglo_tmp = null;
			print '<tbody>';
				$sql="SELECT id, entity, code, libelle AS label, type, active, accountancy_code, module, position FROM ".MAIN_DB_PREFIX."c_paiement WHERE active=1 AND id > 0 AND entity = ".$conf->entity;
				// print $sql;
				$req=$db->query($sql);
				while($rs=$db->fetch_object($req)){
					print '<tr>';
						print "<td align='center'>".$rs->code."</td>";
						$label=($langs->transnoentitiesnoconv("PaymentTypeShort".$rs->code)!=("PaymentTypeShort".$rs->code)?$langs->transnoentitiesnoconv("PaymentTypeShort".$rs->code):($rs->label!='-'?$rs->label:''));
						print "<td align='left'>".$label."</td>";
						print "<td align='center'><input type='text' name='clv_".$rs->code."' id='clv_".$rs->code."' value='".$rs->accountancy_code."' style='text-align: center;'></td>";
					print "</tr>";

					$arreglo_tmp[] = '"'.$rs->code.'"';
				}
			print '</tbody>';
		print '</table>';

		print '<div align="center" style="margin-top:15px;">';
			print '<input type="submit" class="butAction" name="actualiza" value="Guardar">';
		print '</div>';
	print '</form>';

	print '<div class="info hideonsmartphone clearboth">';
        print '<strong>Nota:</strong> Para agregar mas Formas de Pago, debes activarlas en el siguiente diccionario <strong><a href="'.DOL_URL_ROOT.'/admin/dict.php?id=13'.'" target="_blank">Modos de pago</a>.</strong>';
    print '</div>';

    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_paiement WHERE code NOT IN(".implode(",", $arreglo_tmp).") AND entity != ".$conf->entity;
	$sql .= " GROUP BY CODE";
	$req = $db->query($sql);
	$num_formas = $db->num_rows($req);

	if($num_formas > 0){
	    print '<br>';
	    print '<div class="info hideonsmartphone clearboth">';
	        print 'Otras Formas de Pago de Activas por Otras Empresas.</strong>';
	    print '</div>';

		print '<form method="POST" action="cfdimx.php">';
			print '<input type="hidden" name="mod" id="mod" value="formaspago">';
			print '<input type="hidden" name="token" id="token" value="'.$_SESSION["token"].'">';
			print '<table width="100%" class="noborder">';
				print '<thead>';
					print '<tr class="liste_titre">';
						print '<td width="33%" align="center"><strong>C&oacute;digo Dolibarr</strong></td>';
						print '<td width="33%" align="left"><strong>Etiqueta</strong></td>';
						print '<td width="33%" align="center"><strong>Clave SAT</strong></td>';
					print '</tr>';
				print '</thead>';

				print '<tbody>';
					while($rs=$db->fetch_object($req)){
						print '<tr>';
							print "<td align='center'>".$rs->code."</td>";
							$libelle=($langs->transnoentitiesnoconv("PaymentTypeShort".$rs->code)!=("PaymentTypeShort".$rs->code)?$langs->transnoentitiesnoconv("PaymentTypeShort".$rs->code):($rs->libelle!='-'?$rs->libelle:''));
							print "<td align='left'>".$libelle."</td>";
							print "<td align='center'><input type='text' name='clv[]' id='clv_".$rs->code."' value='".$rs->accountancy_code."' style='text-align: center;'></td>";

							print '<input type="hidden" id="fp_code" name="fp_code[]" value="'.$rs->code.'">';
							print '<input type="hidden" id="libelle" name="libelle[]" value="'.$rs->libelle.'">';
						print "</tr>";
					}

				print '</tbody>';
			print '</table>';

			print '<div align="center" style="margin-top:15px;">';
				print '<input type="submit" class="butAction" name="otros" value="Guardar">';
			print '</div>';
		print '</form>';
	}
?>