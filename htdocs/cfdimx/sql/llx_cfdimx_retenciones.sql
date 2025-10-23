CREATE TABLE `llx_cfdimx_retenciones` (
  `retenciones_id` int(11) NOT NULL AUTO_INCREMENT,
  `factura_id` int(11) NOT NULL,
  `fk_facture` int(11) NOT NULL,
  `impuesto` varchar(250) NOT NULL,
  `importe` double NOT NULL,
  PRIMARY KEY (`retenciones_id`)
);
