CREATE TABLE `llx_cfdimx_facture_carta_porte_pedimentos` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `fk_product` int(11) DEFAULT NULL,
    `pedimento` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);