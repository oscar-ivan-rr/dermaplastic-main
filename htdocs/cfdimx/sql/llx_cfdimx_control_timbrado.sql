CREATE TABLE `llx_cfdimx_control_timbrado` 
(  
	`control_timbrado_id` int(11) NOT NULL AUTO_INCREMENT,  
	`factura_rowid` int(11) DEFAULT NULL,
	`factura_serie` varchar(50) DEFAULT NULL,  
	`factura_folio` varchar(50) DEFAULT NULL, 
	`factura_fecha_timbrado` varchar(50) DEFAULT NULL, 
	`tipo_timbrado` int(11) DEFAULT NULL,
	`usuario_rowid` int(11) DEFAULT NULL,
	`tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`estatus` int(11) DEFAULT NULL,
	`entity_id` int(11) DEFAULT NULL,
	PRIMARY KEY (`control_timbrado_id`)
);