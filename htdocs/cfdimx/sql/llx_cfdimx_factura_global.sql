CREATE TABLE `llx_cfdimx_factura_global` (
    `rowid` int(11) NOT NULL AUTO_INCREMENT,
    `entity` int(11) DEFAULT NULL,
    `facid` int(11) DEFAULT NULL,
    `anio` int(11) DEFAULT NULL,
    `meses` varchar(255) DEFAULT NULL,
    `periodicidad` varchar(255) DEFAULT NULL,
    `estatus` int(11) DEFAULT NULL,
    `numfactrel` int(11) DEFAULT NULL,
    PRIMARY KEY (`rowid`)
);