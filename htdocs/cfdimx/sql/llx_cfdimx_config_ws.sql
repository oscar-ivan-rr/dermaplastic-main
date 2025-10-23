CREATE TABLE `llx_cfdimx_config_ws` 
(
	`emisor_rfc` varchar (50) NOT NULL, 
	`ws_modo_timbrado` varchar (50), 
	`ws_pruebas` varchar (255),
	`ws_produccion` varchar (255),
	`ws_status_conf` varchar (2),
	`entity_id` INTEGER,
	PRIMARY KEY (`emisor_rfc`)
);