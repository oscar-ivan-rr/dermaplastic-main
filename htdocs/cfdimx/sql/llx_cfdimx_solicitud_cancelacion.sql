CREATE TABLE `llx_cfdimx_solicitud_cancelacion` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) DEFAULT NULL,
  `httpStatusCode` text DEFAULT NULL,
  `acuse` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `uuid` text DEFAULT NULL,
  `uuidStatusCode` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `messageDetail` text DEFAULT NULL,
  `fecha` date,
  `hora` time,
  PRIMARY KEY (`rowid`)
);