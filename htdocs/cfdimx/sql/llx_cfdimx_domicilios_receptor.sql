CREATE TABLE IF NOT EXISTS `llx_cfdimx_domicilios_receptor` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `receptor_rfc` varchar(50) NOT NULL DEFAULT '',
  `tpdomicilio` varchar(255) NOT NULL DEFAULT '',
  `receptor_delompio` varchar(250) NOT NULL DEFAULT '',
  `receptor_colonia` varchar(250) NOT NULL DEFAULT '',
  `receptor_calle` varchar(250) NOT NULL DEFAULT '',
  `receptor_noext` varchar(200) NOT NULL DEFAULT '',
  `receptor_noint` varchar(200) DEFAULT NULL,
  `receptor_id` int(11) NOT NULL DEFAULT '0',
  `entity_id` int(11) NOT NULL DEFAULT '1',
  `determinado` int(11) NOT NULL DEFAULT '2',
  PRIMARY KEY (`rowid`)
);

ALTER TABLE `llx_cfdimx_domicilios_receptor` ADD COLUMN `cod_municipio` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_domicilios_receptor` ADD COLUMN `fk_socid` int(11) DEFAULT NULL;
