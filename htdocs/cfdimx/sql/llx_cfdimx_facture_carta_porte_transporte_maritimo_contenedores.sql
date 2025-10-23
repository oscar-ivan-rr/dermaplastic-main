CREATE TABLE `llx_cfdimx_facture_carta_porte_transporte_maritimo_contenedores` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `numero` int(11) DEFAULT NULL,
    `matricula` varchar(255) DEFAULT NULL,
    `tipo` varchar(255) DEFAULT NULL,
    `num_precinto` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);