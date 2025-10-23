CREATE TABLE `llx_cfdimx_facture_carta_porte_transporte_ferroviario` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `tipo_servicio` varchar(255) DEFAULT NULL,
    `nom_aseguradora` varchar(255) DEFAULT NULL,
    `num_poliza_seguro` varchar(255) DEFAULT NULL,
    `tipo_trafico` varchar(255) DEFAULT NULL,
    `tipo_derecho_paso` varchar(255) DEFAULT NULL,
    `kilometraje_pagado` varchar(255) DEFAULT NULL,
    `tipo_carro` varchar(255) DEFAULT NULL,
    `matricula_carro` varchar(255) DEFAULT NULL,
    `guia_carro` varchar(255) DEFAULT NULL,
    `toneladas_netas_carro` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);