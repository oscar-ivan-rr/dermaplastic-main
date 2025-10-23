CREATE TABLE `llx_cfdimx_facture_carta_porte_cantidad_transporta` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `fk_product` int(11) DEFAULT NULL,
    `cantidad` varchar(255) DEFAULT NULL,
    `id_origen` varchar(255) DEFAULT NULL,
    `id_destino` varchar(255) DEFAULT NULL,
    `cvestransporte` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);