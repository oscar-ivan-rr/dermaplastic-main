CREATE TABLE `llx_cfdimx_recepcion_pagos_docto_relacionado` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_recepago` int(11) DEFAULT '0',
  `idDocumento` varchar(50) DEFAULT NULL,
  `serie` varchar (50) DEFAULT NULL,
  `folio` varchar (50) DEFAULT NULL,
  `monedaDR` varchar(10) DEFAULT NULL,
  `tipoCambioDR` double DEFAULT 0,
  `metodoDePagoDR` varchar(50) DEFAULT NULL,
  `numParcialidad` int(11) DEFAULT '0',
  `impSaldoAnt` varchar(50) DEFAULT NULL,
  `impPagado` varchar(50) DEFAULT NULL,
  `impSaldoInsoluto` varchar(50) DEFAULT NULL,
  `entity` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);