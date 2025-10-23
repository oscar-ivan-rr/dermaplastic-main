CREATE TABLE `llx_cfdimx_facture_carta_porte_mercancias` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `fk_product` int(11) DEFAULT NULL,
    `descripcion` varchar(255) DEFAULT NULL,
    `cantidad` varchar(255) DEFAULT NULL,
    `bienestransp` varchar(255) DEFAULT NULL,
    `clavestcc` varchar(255) DEFAULT NULL,
    `claveunidad` varchar(255) DEFAULT NULL,
    `unidad` varchar(255) DEFAULT NULL,
    `dimensiones` varchar(255) DEFAULT NULL,
    `material_peligroso` varchar(255) DEFAULT NULL,
    `cve_material_peligroso` varchar(255) DEFAULT NULL,
    `embalaje` varchar(255) DEFAULT NULL,
    `desc_embalaje` varchar(255) DEFAULT NULL,
    `peso_en_kg` varchar(255) DEFAULT NULL,
    `valor_mercancia` varchar(255) DEFAULT NULL,
    `moneda` varchar(255) DEFAULT NULL,
    `fraccion_arancelaria` varchar(255) DEFAULT NULL,
    `uuidcomercioext` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);

ALTER TABLE `llx_cfdimx_facture_carta_porte_mercancias` CHANGE `descripcion` `descripcion` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;