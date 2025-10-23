<?php

require '../../main.inc.php';

$nom = urldecode(GETPOST('search'));

$sql =	 "SELECT rowid,nom "."\r\n"
		."FROM llx_societe  "."\r\n"
		."WHERE fournisseur=1  "."\r\n"
		."  AND nom LIKE '%{$nom}%'  "."\r\n"
		."ORDER BY nom ASC "."\r\n"
		."";

$rows = array();

if (!$res=$db->query($sql))
{
	dol_print_error($db);
	die();
}
else
{
	while ($row = $db->fetch_object($res))
	{
		$rows[] = array('id'=>$row->rowid,'text'=>$row->nom);
	}
}

echo json_encode(array('results'=>$rows));