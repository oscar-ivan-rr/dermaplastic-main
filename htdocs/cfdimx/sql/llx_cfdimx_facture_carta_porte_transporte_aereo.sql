CREATE TABLE `llx_cfdimx_facture_carta_porte_transporte_aereo` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `permiso_sct` varchar(255) DEFAULT NULL,
    `num_permiso_sct` varchar(255) DEFAULT NULL,
    `matricula_aeronave` varchar(255) DEFAULT NULL,
    `nom_aseg` varchar(255) DEFAULT NULL,
    `num_poliza_seguro` varchar(255) DEFAULT NULL,
    `num_guia` varchar(255) DEFAULT NULL,
    `lugar_contrato` varchar(255) DEFAULT NULL,
    `codigo_transportista` varchar(255) DEFAULT NULL,    
    `rfc_embarcador` varchar(255) DEFAULT NULL,
    `numregidtrib_embarc` varchar(255) DEFAULT NULL,
    `residencia_fiscal_embarc` varchar(255) DEFAULT NULL,
    `nom_embarcador` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);
