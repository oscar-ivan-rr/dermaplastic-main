CREATE TABLE `llx_cfdimx_nomina_incapacidades` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `DiasIncapacidad` varchar(255) DEFAULT NULL,
  `TipoIncapacidad` varchar(255) DEFAULT NULL,
  `Descuento` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);