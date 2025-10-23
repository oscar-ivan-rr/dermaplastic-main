CREATE TABLE `llx_cfdimx_nomina_impuestos_locales` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `impuesto` varchar(255) DEFAULT NULL,
  `tasa` double DEFAULT NULL,
  `importe` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);