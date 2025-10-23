CREATE TABLE IF NOT EXISTS `llx_cfdimx_estatus` (
	`rowid` integer (11) NOT NULL AUTO_INCREMENT,
	`uuid` text,
	`facid` integer (11),
	`entity` integer (11),
	`estado` text NULL,
	`escancelable` text NULL,
	`message` text NULL,
	`fk_user` integer (11),
	`fecha` date, 
	`hora` time,
	PRIMARY KEY (`rowid`)
);