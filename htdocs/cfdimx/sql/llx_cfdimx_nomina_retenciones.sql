CREATE TABLE `llx_cfdimx_nomina_retenciones` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `impuesto` varchar(255) DEFAULT NULL,
  `importe` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);