CREATE TABLE IF NOT EXISTS `llx_cfdimx_domicilio_fiscal_receptor` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `fk_soc` int(11),
    `entity_id` int(11),
    `rfc` varchar(50),
    `nombre` varchar(255),
    `regimenfiscal` varchar(255),
    `numregidtrib` varchar(200),
    `direccion` varchar(200),
    `cp` varchar(250),
    `municipio` varchar(200),
    `estado` varchar(250),
    `pais` varchar(200),
    `estatus` int(11),
    PRIMARY KEY (`rowid`)
);