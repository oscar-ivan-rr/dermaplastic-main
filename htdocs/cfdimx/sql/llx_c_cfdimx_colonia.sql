CREATE TABLE IF NOT EXISTS `llx_c_cfdimx_colonia` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,  
  `clave_colonia` varchar(255) DEFAULT NULL,
  `codigo_postal` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
);