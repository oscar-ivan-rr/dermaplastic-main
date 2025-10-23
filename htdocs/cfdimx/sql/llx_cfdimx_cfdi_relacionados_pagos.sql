CREATE TABLE `llx_cfdimx_cfdi_relacionados_pagos` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pago` int(11) DEFAULT NULL,
  `uuid` text DEFAULT NULL,
  `fk_pago_rel` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);