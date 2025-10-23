CREATE TABLE `llx_cfdimx_facture_carta_porte` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `entrada_salida` varchar(255) DEFAULT NULL,
    `via_entrada_salida` varchar(255) DEFAULT NULL,
    `transporte_internacional` varchar(255) DEFAULT NULL,
    `tipo_transporte` varchar(255) DEFAULT NULL,
    `tot_distancia_recorrida` varchar(255) DEFAULT NULL,
    `pais_origen_destino` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);