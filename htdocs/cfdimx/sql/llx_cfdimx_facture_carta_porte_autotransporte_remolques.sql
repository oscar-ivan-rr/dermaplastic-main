CREATE TABLE `llx_cfdimx_facture_carta_porte_autotransporte_remolques` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `numero` int(11) DEFAULT NULL,
    `sub_tipo` varchar(255) DEFAULT NULL,
    `placas` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);