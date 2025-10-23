CREATE TABLE `llx_cfdimx_config` 
(
	`emisor_rfc` varchar (50) NOT NULL, 
	`password_timbrado` varchar (100), 
	`password_timbrado_txt` varchar (100), 
	`formato_cfdi` varchar (50), 
	`modo_timbrado` varchar (50), 
	`config_seriefolio` varchar (50),
	`status_conf` varchar (2),
	PRIMARY KEY (`emisor_rfc`)
);
ALTER TABLE `llx_cfdimx_config` ADD COLUMN `entity_id` INTEGER;