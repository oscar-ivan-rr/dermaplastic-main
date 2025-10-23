CREATE TABLE `llx_cfdimx_nomina_conceptos` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `unidad` varchar(255) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `valorUnitario` varchar(255) DEFAULT NULL,
  `importe` double DEFAULT NULL,
  `impuesto` varchar(255) DEFAULT NULL,
  `tasa` double DEFAULT NULL,
  `importeImps` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);