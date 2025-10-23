CREATE TABLE IF NOT EXISTS `llx_c_cfdimx_municipio` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,  
  `clave_mpio` varchar(255) DEFAULT NULL,
  `clave_estado` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
);