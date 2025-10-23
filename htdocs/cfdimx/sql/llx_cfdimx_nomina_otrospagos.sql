CREATE TABLE `llx_cfdimx_nomina_otrospagos` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `importe` double DEFAULT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `clave` varchar(255) DEFAULT NULL,
  `tipopago` varchar(255) DEFAULT NULL,
  `subsidiocausado` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);