-- Extrafields for customer birthday (month/day) and marketing opt-in
-- Run once on each environment DB

ALTER TABLE llx_societe_extrafields ADD COLUMN IF NOT EXISTS recibir_informacion INT(1) DEFAULT 0;
ALTER TABLE llx_societe_extrafields ADD COLUMN IF NOT EXISTS cumpleanos_mes VARCHAR(255) NULL;
ALTER TABLE llx_societe_extrafields ADD COLUMN IF NOT EXISTS cumpleanos_dia VARCHAR(255) NULL;

-- Skip insert if already present
INSERT INTO llx_extrafields (name, entity, elementtype, label, type, size, fieldunique, fieldrequired, perms, pos, alwayseditable, param, list, printable, totalizable, enabled, fielddefault, datec)
SELECT * FROM (
  SELECT 'recibir_informacion' AS name, 1 AS entity, 'societe' AS elementtype, 'Recibir información' AS label, 'boolean' AS type, '' AS size, 0 AS fieldunique, 0 AS fieldrequired, NULL AS perms, 90 AS pos, 1 AS alwayseditable,
    '' AS param,
    '((GETPOST("type","aZ")=="c") || (is_object($object) && !empty($object->client) && ($object->client==1 || $object->client==3)))?1:0' AS list,
    0 AS printable, 0 AS totalizable, '1' AS enabled, '0' AS fielddefault, NOW() AS datec
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name='recibir_informacion' AND elementtype='societe');

INSERT INTO llx_extrafields (name, entity, elementtype, label, type, size, fieldunique, fieldrequired, perms, pos, alwayseditable, param, list, printable, totalizable, enabled, datec)
SELECT * FROM (
  SELECT 'cumpleanos_mes' AS name, 1 AS entity, 'societe' AS elementtype, 'Mes de cumpleaños' AS label, 'select' AS type, '' AS size, 0 AS fieldunique, 1 AS fieldrequired, NULL AS perms, 91 AS pos, 1 AS alwayseditable,
    'a:1:{s:7:"options";a:12:{s:1:"1";s:5:"Enero";s:1:"2";s:8:"Febrero";s:1:"3";s:5:"Marzo";s:1:"4";s:5:"Abril";s:1:"5";s:4:"Mayo";s:1:"6";s:5:"Junio";s:1:"7";s:5:"Julio";s:1:"8";s:6:"Agosto";s:1:"9";s:11:"Septiembre";s:2:"10";s:7:"Octubre";s:2:"11";s:10:"Noviembre";s:2:"12";s:9:"Diciembre";}}' AS param,
    '((GETPOST("type","aZ")=="c") || (is_object($object) && !empty($object->client) && ($object->client==1 || $object->client==3)))?1:0' AS list,
    0 AS printable, 0 AS totalizable, '1' AS enabled, NOW() AS datec
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name='cumpleanos_mes' AND elementtype='societe');

INSERT INTO llx_extrafields (name, entity, elementtype, label, type, size, fieldunique, fieldrequired, perms, pos, alwayseditable, param, list, printable, totalizable, enabled, datec)
SELECT * FROM (
  SELECT 'cumpleanos_dia' AS name, 1 AS entity, 'societe' AS elementtype, 'Día de cumpleaños' AS label, 'select' AS type, '' AS size, 0 AS fieldunique, 1 AS fieldrequired, NULL AS perms, 92 AS pos, 1 AS alwayseditable,
    'a:1:{s:7:"options";a:31:{s:1:"1";s:1:"1";s:1:"2";s:1:"2";s:1:"3";s:1:"3";s:1:"4";s:1:"4";s:1:"5";s:1:"5";s:1:"6";s:1:"6";s:1:"7";s:1:"7";s:1:"8";s:1:"8";s:1:"9";s:1:"9";s:2:"10";s:2:"10";s:2:"11";s:2:"11";s:2:"12";s:2:"12";s:2:"13";s:2:"13";s:2:"14";s:2:"14";s:2:"15";s:2:"15";s:2:"16";s:2:"16";s:2:"17";s:2:"17";s:2:"18";s:2:"18";s:2:"19";s:2:"19";s:2:"20";s:2:"20";s:2:"21";s:2:"21";s:2:"22";s:2:"22";s:2:"23";s:2:"23";s:2:"24";s:2:"24";s:2:"25";s:2:"25";s:2:"26";s:2:"26";s:2:"27";s:2:"27";s:2:"28";s:2:"28";s:2:"29";s:2:"29";s:2:"30";s:2:"30";s:2:"31";s:2:"31";}}' AS param,
    '((GETPOST("type","aZ")=="c") || (is_object($object) && !empty($object->client) && ($object->client==1 || $object->client==3)))?1:0' AS list,
    0 AS printable, 0 AS totalizable, '1' AS enabled, NOW() AS datec
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name='cumpleanos_dia' AND elementtype='societe');
