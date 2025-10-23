CREATE TABLE `llx_cfdimx` 
(
	`factura_id` integer (11) NOT NULL AUTO_INCREMENT, 
	`tipo_timbrado` integer (11), 
	`factura_serie` varchar (50), 
	`factura_folio` integer (11), 
	`factura_seriefolio` varchar (50), 
	`xml` text, 
	`cadena` text,
	`version` varchar (10), 
	`selloCFD` text, 
	`fechaTimbrado` varchar (50), 
	`uuid` varchar (250), 
	`certificado` varchar (250), 
	`sello` text, 
	`certEmisor` varchar (250), 
	`cancelado` integer (11) DEFAULT 0, 
	`u4dig` integer (11), 
	`fk_facture` varchar (50),
	`fecha_emision` date, 
	`hora_emision` time, 
	`fecha_timbrado` date, 
	`hora_timbrado` time,
	`divisa` varchar (4),
	PRIMARY KEY (`factura_id`)
);
ALTER TABLE `llx_cfdimx` ADD COLUMN `entity_id` INTEGER;
ALTER TABLE `llx_cfdimx` ADD COLUMN `divisa` varchar(4) AFTER `hora_timbrado`;
ALTER TABLE `llx_cfdimx` CHANGE `u4dig` `u4dig` VARCHAR(255) NULL DEFAULT NULL;
#ALTER TABLE `llx_cfdimx` COLLATE='utf8_general_ci';