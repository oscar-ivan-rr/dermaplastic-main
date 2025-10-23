DROP TABLE IF EXISTS `llx_cfdimx_emisores_datacom`;
DROP TABLE IF EXISTS `llx_cfdimx_receptor_datacomp`;

ALTER TABLE `llx_cfdimx_cfdi_relacionados` CHANGE `uuid` `uuid` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `llx_cfdimx` ADD COLUMN `rfc` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx` ADD COLUMN `usocfdi` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx` ADD COLUMN `nombre` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx` ADD COLUMN `codigopostal` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx` ADD COLUMN `regimenfiscal` VARCHAR(255) NULL;

ALTER TABLE `llx_cfdimx` CHANGE `factura_folio` `factura_folio` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `llx_cfdimx_recepcion_pagos_docto_relacionado` ADD COLUMN `equivalencia` VARCHAR(255) NULL AFTER `entity`;

ALTER TABLE `llx_cfdimx_recepcion_pagos` ADD COLUMN `tipo_rel` VARCHAR(255) NULL;

ALTER TABLE `llx_cfdimx_domicilio_fiscal_receptor` ADD COLUMN `residencia_fiscal` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx_domicilio_fiscal_receptor` ADD COLUMN `calle` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx_domicilio_fiscal_receptor` ADD COLUMN `clave_mpio` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx_domicilio_fiscal_receptor` ADD COLUMN `noint` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx_domicilio_fiscal_receptor` ADD COLUMN `noext` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx_domicilio_fiscal_receptor` ADD COLUMN `clave_col` VARCHAR(255) NULL;

ALTER TABLE `llx_c_cfdimx_estaciones` ADD COLUMN `clave_t` VARCHAR(255) NULL;

ALTER TABLE `llx_cfdimx_solicitud_cancelacion` ADD COLUMN `archivo` TEXT NULL;

ALTER TABLE `llx_c_cfdimx_formapago` ADD COLUMN `cod_doli` TEXT NULL;
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "LIQ" WHERE code = "01";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "CHQ" WHERE code = "02";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "VIR" WHERE code = "03";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "CB" WHERE code = "04";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "ME05" WHERE code = "05";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "DE06" WHERE code = "06";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "VDD08" WHERE code = "08";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "DEP12" WHERE code = "12";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "PPS13" WHERE code = "13";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "PPC14" WHERE code = "14";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "C15" WHERE code = "15";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "C17" WHERE code = "17";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "N23" WHERE code = "23";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "N24" WHERE code = "24";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "RDD25" WHERE code = "25";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "POC26" WHERE code = "26";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "ASDA27" WHERE code = "27";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "TDD28" WHERE code = "28";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "TDS29" WHERE code = "29";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "AA30" WHERE code = "30";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "IP31" WHERE code = "31";
UPDATE `llx_c_cfdimx_formapago` SET cod_doli = "PD99" WHERE code = "99";

ALTER TABLE `llx_cfdimx_facture_comercio_extranjero` ADD COLUMN `motivotraslado` VARCHAR(255) NULL;
ALTER TABLE `llx_cfdimx_facture_comercio_extranjero` ADD COLUMN `numcertificadoorigen` VARCHAR(255) NULL;