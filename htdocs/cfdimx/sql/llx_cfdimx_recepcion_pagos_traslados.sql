CREATE TABLE `llx_cfdimx_recepcion_pagos_traslados` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_recepago` int(11) DEFAULT '0',
  `impuesto` varchar (50) DEFAULT NULL,
  `tipoFactor` varchar (50) DEFAULT NULL,
  `tasaOCuota` double DEFAULT 0,
  `importe` double DEFAULT 0,
  `entity` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);