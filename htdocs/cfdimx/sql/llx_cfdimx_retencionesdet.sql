CREATE TABLE `llx_cfdimx_retencionesdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `fk_facturedet` int(11) NOT NULL,
  `base` double NOT NULL,
  `impuesto` varchar(50) NOT NULL,
  `tipo_factor` varchar(50) NOT NULL,
  `tasa` varchar(50) NOT NULL,
  `importe` double NOT NULL,
  PRIMARY KEY (`rowid`)
);
