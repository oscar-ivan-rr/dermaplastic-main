CREATE TABLE `llx_cfdimx_recepcion_pagos_retenciones` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_recepago` int(11) DEFAULT '0',
  `impuesto` varchar (50),
  `importe` double DEFAULT NULL,
  `entity` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);