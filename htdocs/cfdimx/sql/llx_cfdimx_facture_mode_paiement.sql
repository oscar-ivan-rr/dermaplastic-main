CREATE TABLE IF NOT EXISTS `llx_cfdimx_facture_mode_paiement` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL,
  `fk_c_paiement` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
);
