CREATE TABLE `llx_cfdimx_facturedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL DEFAULT '0',
  `impuesto` varchar(11) NOT NULL DEFAULT '',
  `importe` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
);