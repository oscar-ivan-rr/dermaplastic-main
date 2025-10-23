CREATE TABLE `llx_cfdimx_facture_carta_porte_mercancia` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `peso_bruto_total` varchar(255) DEFAULT NULL,
    `unidad_peso` varchar(255) DEFAULT NULL,
    `peso_neto_total` varchar(255) DEFAULT NULL,
    `num_total_mercancias` varchar(255) DEFAULT NULL,
    `cargo_por_tasacion` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);