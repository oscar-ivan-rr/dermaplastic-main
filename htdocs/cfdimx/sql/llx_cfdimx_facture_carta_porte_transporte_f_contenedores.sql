CREATE TABLE `llx_cfdimx_facture_carta_porte_transporte_f_contenedores` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `numero` int(11) DEFAULT NULL,
    `tipo` varchar(255) DEFAULT NULL,
    `peso_contendor_vacio` varchar(255) DEFAULT NULL,
    `peso_neto_mercancia` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);