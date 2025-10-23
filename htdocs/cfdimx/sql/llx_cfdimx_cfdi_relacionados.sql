CREATE TABLE `llx_cfdimx_cfdi_relacionados` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) DEFAULT NULL,
  `uuid` text DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);