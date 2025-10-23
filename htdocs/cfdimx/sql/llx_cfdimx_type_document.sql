CREATE TABLE `llx_cfdimx_type_document` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL DEFAULT '0',
  `tipo_document` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowid`)
)