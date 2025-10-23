CREATE TABLE `llx_cfdimx_facture_carta_porte_detalle_mercancia` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `fk_product` int(11) DEFAULT NULL,
    `num_piezas` varchar(255) DEFAULT NULL,
    `unidad_peso` varchar(255) DEFAULT NULL,
    `peso_bruto` varchar(255) DEFAULT NULL,
    `peso_neto` varchar(255) DEFAULT NULL,
    `peso_tara` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);