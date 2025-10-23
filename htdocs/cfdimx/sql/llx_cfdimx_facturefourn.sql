CREATE TABLE `llx_cfdimx_facturefourn` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL DEFAULT '0',
  `xml` varchar(255) NOT NULL DEFAULT '',
  `pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);