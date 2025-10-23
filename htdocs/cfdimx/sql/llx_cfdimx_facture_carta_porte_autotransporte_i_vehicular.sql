CREATE TABLE `llx_cfdimx_facture_carta_porte_autotransporte_i_vehicular` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `config_vehicular` varchar(255) DEFAULT NULL,
    `placa_vm` varchar(255) DEFAULT NULL,
    `anio_modelo_vm` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);