CREATE TABLE `llx_cfdimx_nomina_percepciones` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `TipoPercepcion` varchar(255) DEFAULT NULL,
  `Clave` varchar(255) DEFAULT NULL,
  `Concepto` varchar(255) DEFAULT NULL,
  `ImporteGravado` double DEFAULT NULL,
  `ImporteExento` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);