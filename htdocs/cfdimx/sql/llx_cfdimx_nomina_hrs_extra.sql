CREATE TABLE `llx_cfdimx_nomina_hrs_extra` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `dias` varchar(255) DEFAULT NULL,
  `TipoHoras` varchar(255) DEFAULT NULL,
  `HorasExtra` varchar(255) DEFAULT NULL,
  `ImportePagado` double DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);