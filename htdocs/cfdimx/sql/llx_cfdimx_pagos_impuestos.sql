CREATE TABLE `llx_cfdimx_pagos_impuestos` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `fk_pago` int(11) DEFAULT NULL,
    `uuid_doc_rel` varchar(255) DEFAULT NULL,
    `base` varchar(255) DEFAULT NULL,
    `impuesto` varchar(255) DEFAULT NULL,
    `tipo_factor` varchar(255) DEFAULT NULL,
    `tasa_o_cuota` varchar(255) DEFAULT NULL,
    `importe` varchar(255) DEFAULT NULL,
    `tipo` int(11) DEFAULT NULL,
    `fk_user` int(11) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);