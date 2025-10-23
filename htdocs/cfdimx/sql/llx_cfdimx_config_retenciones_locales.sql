CREATE TABLE `llx_cfdimx_config_retenciones_locales` 
(
	`rowid` integer (11) NOT NULL AUTO_INCREMENT, 
	`cod` varchar (15), 
	`descripcion` varchar (50), 
	`tasa` double,
	`entity` integer (11) NOT NULL,
	PRIMARY KEY (`rowid`)
);