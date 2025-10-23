CREATE TABLE `llx_cfdimx_solicitud_cancelacion_pago` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_pago` int(11) DEFAULT NULL,
  `httpStatusCode` text DEFAULT NULL,
  `acuse` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `uuid` text DEFAULT NULL,
  `uuidStatusCode` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `messageDetail` text DEFAULT NULL,
  `fecha` date,
  `hora` time,
  `archivo` text DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);