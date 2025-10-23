CREATE TABLE IF NOT EXISTS `llx_cfdimx_emisor_datacomp` (
  `emisor_rfc` varchar(50),
  `emisor_delompio` varchar(250) DEFAULT NULL,
  `emisor_colonia` varchar(250) DEFAULT NULL,
  `emisor_calle` varchar(250) DEFAULT NULL,
  `emisor_noext` varchar(200) DEFAULT NULL,
  `emisor_noint` varchar(200) DEFAULT NULL
);
#ALTER TABLE `llx_cfdimx_emisor_datacomp` DROP PRIMARY KEY;
#ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `emisor_id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `entity_id` INTEGER;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `cod_municipio` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `cod_colonia` varchar(50) DEFAULT NULL;

#Campos agregados cuando se elimino tabla llx_cfdimx_emisores_datacom
ALTER TABLE `llx_cfdimx_emisor_datacomp` DROP COLUMN `emisor_id`;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `rowid` int(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`rowid`);
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `razon_social` text DEFAULT NULL AFTER `emisor_rfc`;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `regimen` text DEFAULT NULL AFTER `razon_social`;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `pais` text DEFAULT NULL AFTER `regimen`;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `estado` text DEFAULT NULL AFTER `pais`;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `codigo_postal` text DEFAULT NULL AFTER `estado`;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `password_timbrado` varchar(100) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `password_timbrado_txt` varchar(100) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `formato_cfdi` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `modo_timbrado` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `config_seriefolio` varchar(50) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `status_conf` varchar(2) DEFAULT NULL;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `predeterminado` int(11) NOT NULL DEFAULT '0';