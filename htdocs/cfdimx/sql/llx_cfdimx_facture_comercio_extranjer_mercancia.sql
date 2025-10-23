CREATE TABLE IF NOT EXISTS `llx_cfdimx_facture_comercio_extranjero_mercancia` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL DEFAULT '0',
  `fk_facturedet` int(11) NOT NULL DEFAULT '0',
  `preciousd` double,
  `noidentificacion` varchar(50) DEFAULT NULL,
  `unidadcext` varchar(50) DEFAULT NULL,
  `fraccion_arancelaria` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);

ALTER TABLE `llx_cfdimx_facture_comercio_extranjero_mercancia` ADD COLUMN `unidadcext` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero_mercancia` ADD COLUMN `fraccion_arancelaria` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero_mercancia` ADD COLUMN `valor_unitario` double;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero_mercancia` ADD COLUMN `cantidad_aduana` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero_mercancia` ADD COLUMN `marca` varchar(50) DEFAULT NULL;