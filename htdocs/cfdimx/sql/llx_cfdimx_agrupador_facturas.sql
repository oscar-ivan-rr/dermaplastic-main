CREATE TABLE `llx_cfdimx_agrupador_facturas` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid_origen` int(11) DEFAULT NULL,
    `facid_relacionada` int(11) DEFAULT NULL,
    `estatus` int(11) DEFAULT NULL,
    `tipo` int(11) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);