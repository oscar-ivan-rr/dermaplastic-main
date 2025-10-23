CREATE TABLE `llx_cfdimx_recepcion_pagos_relacion_facturas` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) DEFAULT '0',
  `fk_relacion_pagos` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
);