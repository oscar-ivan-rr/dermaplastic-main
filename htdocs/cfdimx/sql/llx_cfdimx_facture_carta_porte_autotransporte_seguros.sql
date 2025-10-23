CREATE TABLE `llx_cfdimx_facture_carta_porte_autotransporte_seguros` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `aseguraRespCivil` varchar(255) DEFAULT NULL,
    `polizaRespCivil` varchar(255) DEFAULT NULL,
    `aseguraMedAmbiente` varchar(255) DEFAULT NULL,
    `polizaMedAmbiente` varchar(255) DEFAULT NULL,
    `aseguraCarga` varchar(255) DEFAULT NULL,
    `polizaCarga` varchar(255) DEFAULT NULL,
    `primaSeguro` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);