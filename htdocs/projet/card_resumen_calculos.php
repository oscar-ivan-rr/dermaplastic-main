<?php


$sql =	 'SELECT sum(f.total_ttc) as total /*, SUM(pf.amount) as cobros */'."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_facture as f  '."\r\n"
		.'LEFT JOIN llx_facture_extrafields as ef  '."\r\n"
		.'  on (f.rowid = ef.fk_object)  '."\r\n"
		.'#LEFT JOIN llx_paiement_facture as pf  '."\r\n"
		.'#  ON pf.fk_facture = f.rowid  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = f.fk_projet  '."\r\n"
		.'WHERE f.fk_soc = s.rowid  '."\r\n"
		.'  AND f.entity IN (1)  '."\r\n"
		//.'  AND f.type IN (0)  '."\r\n"
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')  '."\r\n"
		.'  AND f.fk_statut IN (1,2)  '."\r\n"
		.'GROUP BY p.rowid  '."\r\n"
		.'ORDER BY f.datef DESC, f.rowid DESC'."\r\n";
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_facturas = $rc_facts->total;

$sql =	 'SELECT SUM(pf.amount) as cobros '."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_facture as f  '."\r\n"
		.'LEFT JOIN llx_facture_extrafields as ef  '."\r\n"
		.'  on (f.rowid = ef.fk_object)  '."\r\n"
		.'LEFT JOIN llx_paiement_facture as pf  '."\r\n"
		.'  ON pf.fk_facture = f.rowid  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = f.fk_projet  '."\r\n"
		.'WHERE f.fk_soc = s.rowid  '."\r\n"
		.'  AND f.entity IN (1)  '."\r\n"
		//.'  AND f.type IN (0)  '."\r\n"
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')  '."\r\n"
		.'  AND f.fk_statut IN (1,2)  '."\r\n"
		.'GROUP BY p.rowid  '."\r\n"
		.'ORDER BY f.datef DESC, f.rowid DESC'."\r\n";
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_cobros = $rc_facts->cobros;



$sql =	 'SELECT sum(p.total) as total_ttc'."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_propal as p  '."\r\n"
		.'LEFT JOIN llx_user as u  '."\r\n"
		.'  ON p.fk_user_author = u.rowid  '."\r\n"
		.'LEFT JOIN llx_projet as pr  '."\r\n"
		.'  ON pr.rowid = p.fk_projet  '."\r\n"
		.'LEFT JOIN llx_c_availability as ava  '."\r\n"
		.'  on (ava.rowid = p.fk_availability)  '."\r\n"
		.'WHERE p.fk_soc = s.rowid  '."\r\n"
		.'  AND p.entity IN (1)  '."\r\n"
		.'  AND (pr.ref LIKE \'%'.$object->ref.'%\')  '."\r\n"
		.'  AND p.fk_statut IN (1,2)  '."\r\n"
		.'ORDER BY p.ref DESC, p.ref DESC LIMIT 26'
		;

if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_presup = $rc_facts->total_ttc;


//ENVIOS

$sql = 'SELECT 
		SUM(ed.qty*(cd.subprice*(1-(cd.remise_percent/100))))*1.16 as total_ttc
			FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee, llx_projet as p
				WHERE ee.targettype = "shipping" AND ee.sourcetype = "commande" AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid
					AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid
					AND ed.fk_origin_line = cd.rowid
					AND e.fk_statut = 2
					AND e.fk_projet = p.rowid
					AND p.ref LIKE \'%'.$object->ref.'%\'';

if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_envio =$db->fetch_object($res);
$t_envio = $rc_envio->total_ttc;


//ENVIOS COSTO

$sql = 'SELECT 
		SUM(ed.qty*cd.cost_price)*1.16 as total_ttc
			FROM llx_commande AS c, llx_commandedet AS cd, llx_expedition AS e, llx_expeditiondet AS ed, llx_element_element AS ee, llx_projet as p
				WHERE ee.targettype = "shipping" AND ee.sourcetype = "commande" AND ee.fk_source = c.rowid AND ee.fk_target = e.rowid
					AND cd.fk_commande = c.rowid AND ed.fk_expedition = e.rowid
					AND ed.fk_origin_line = cd.rowid
					AND e.fk_statut = 2
					AND e.fk_projet = p.rowid
					AND p.ref LIKE \'%'.$object->ref.'%\'';

if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_envio_cost =$db->fetch_object($res);
$t_envio_cost = $rc_envio_cost->total_ttc;


//PEDIDOS CO
$sql =	 'SELECT SUM(c.total_ttc) as total_ttc '."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_commande as c  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = c.fk_projet  '."\r\n"
		.'WHERE c.fk_soc = s.rowid  '."\r\n"
		.'  AND c.entity IN (1)  '."\r\n"
		//.'	AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))'
		.'	AND c.fk_statut IN (1,2,3) '
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')   '."\r\n"
		.'GROUP BY p.rowid'."\r\n"
		.'ORDER BY c.ref DESC LIMIT 26'
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_co = $rc_facts->total_ttc;


//PEDIDOS COSTO
$sql = 'SELECT 
		SUM(cd.qty*cd.cost_price)*1.16 as total_ttc
			FROM llx_commande AS c, llx_commandedet AS cd, llx_projet as p
				WHERE cd.fk_commande = c.rowid
					AND c.fk_statut IN (1,2,3)
					AND c.fk_projet = p.rowid
					AND p.ref LIKE \'%'.$object->ref.'%\'';

if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_co_cost =$db->fetch_object($res);
$t_co_cost = $rc_co_cost->total_ttc;

//POS
$sql =	 'SELECT SUM(c.total_ttc) as total_ttc '."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_pos_ticket as c  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = c.fk_projet  '."\r\n"
		.'WHERE c.fk_soc = s.rowid  '."\r\n"
		.'  AND c.entity IN (1)  '."\r\n"
		//.'	AND c.fk_facture is null'
		.'	AND c.fk_statut IN (1,2)'
		.'	AND c.type IN (0,1)'
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')'
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_pos = $rc_facts->total_ttc;


//FACTURAS DIRECTAS
$sql =	 'SELECT sum(f.total_ttc) as total '."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_facture as f  '."\r\n"
		.'LEFT JOIN llx_facture_extrafields as ef  '."\r\n"
		.'  on (f.rowid = ef.fk_object)  '."\r\n"
		.'#LEFT JOIN llx_paiement_facture as pf  '."\r\n"
		.'#  ON pf.fk_facture = f.rowid  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = f.fk_projet  '."\r\n"
		.'WHERE f.fk_soc = s.rowid  '."\r\n"
		.'  AND f.entity IN (1)  '."\r\n"
		//.'  AND f.type IN (0)  '."\r\n"
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')  '."\r\n"
		.'  AND f.fk_statut IN (1,2)  '."\r\n"
		.'  AND f.rowid NOT IN (SELECT fk_source FROM llx_element_element) AND f.rowid NOT IN (SELECT fk_target FROM llx_element_element)  '."\r\n"
		.'GROUP BY p.rowid  '."\r\n"
		.'ORDER BY f.datef DESC, f.rowid DESC'."\r\n";
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_factdir = $rc_facts->total;


//PRESUPUESTOS
$sql =	 'SELECT SUM(c.total) as total '."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_supplier_proposal as c  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = c.fk_projet  '."\r\n"
		.'WHERE c.fk_soc = s.rowid  '."\r\n"
		.'  AND c.entity IN (1)  '."\r\n"
		.'	AND c.fk_statut IN (1,2)'
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')'
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_epres = $rc_facts->total;


//COMPRAS
$sql =	 'SELECT SUM(c.total_ttc) as total_ttc '."\r\n"
		.'FROM llx_societe as s  '."\r\n"
		.'LEFT JOIN llx_c_country as country  '."\r\n"
		.'  on (country.rowid = s.fk_pays)  '."\r\n"
		.'LEFT JOIN llx_c_typent as typent  '."\r\n"
		.'  on (typent.id = s.fk_typent)  '."\r\n"
		.'LEFT JOIN llx_c_departements as state  '."\r\n"
		.'  on (state.rowid = s.fk_departement), llx_facture_fourn as c  '."\r\n"
		.'LEFT JOIN llx_projet as p  '."\r\n"
		.'  ON p.rowid = c.fk_projet  '."\r\n"
		.'WHERE c.fk_soc = s.rowid  '."\r\n"
		.'  AND c.entity IN (1)  '."\r\n"
		.'	AND c.fk_statut IN (1,2)'
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')'
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_compras = $rc_facts->total_ttc;


//GASTOS
$sql =	 'SELECT SUM(e.total_ttc) as total_ttc '."\r\n"
		.'FROM llx_expensereport AS e, llx_projet AS p  '."\r\n"
		.'WHERE e.fk_projet = p.rowid  '."\r\n"
		.'	AND e.fk_statut IN (2,5,6)'
		//.'	AND e.fk_statut IN (2)'
		.'  AND (p.ref LIKE \'%'.$object->ref.'%\')'
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_gastos = $rc_facts->total_ttc;

//NOMINA
$t_nomina=0;
$sql =	 'SELECT SUM(exp.total_ttc) AS total_ttc 
			FROM llx_expensereport AS exp
				LEFT JOIN llx_c_type_fees AS tp ON (tp.id = exp.fk_c_type_fees)
				LEFT JOIN llx_projet AS p ON p.rowid = exp.fk_projet 
				WHERE 
					tp.code = "CZ_NOM"
					AND exp.entity IN (1)
					AND exp.fk_statut IN (2,5,6)
					AND (p.ref LIKE \'%'.$object->ref.'%\')';
		;
if (!$res = $db->query($sql))
{
	dol_print_error($db);
	die;	
}

$rc_facts =$db->fetch_object($res);
$t_nomina = $rc_facts->total_ttc;

?>