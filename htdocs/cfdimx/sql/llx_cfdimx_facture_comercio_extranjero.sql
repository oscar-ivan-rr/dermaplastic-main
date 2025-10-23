CREATE TABLE `llx_cfdimx_facture_comercio_extranjero` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL DEFAULT '0',
  `tipo_operacion` varchar(50) DEFAULT NULL,
  `clv_pedimento` varchar(50) DEFAULT NULL,
  `no_exportador` varchar(50) DEFAULT NULL,
  `incoterm` varchar(50) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `num_identificacion` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);

ALTER TABLE `llx_cfdimx_facture_comercio_extranjero` ADD COLUMN `tipocambio` double DEFAULT 1;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero` ADD COLUMN `certificadoorigen` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero` ADD COLUMN `subdivision` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero` ADD COLUMN `totalusd` varchar(50) DEFAULT NULL;
