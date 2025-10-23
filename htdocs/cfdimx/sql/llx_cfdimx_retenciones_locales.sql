CREATE TABLE `llx_cfdimx_retenciones_locales` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `tasa` double NOT NULL,
  `importe` double NOT NULL,
  PRIMARY KEY (`rowid`)
);
