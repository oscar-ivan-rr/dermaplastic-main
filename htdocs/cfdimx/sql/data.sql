-- Copyright (C) 2018 SuperAdmin
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.


--
-- Actualización de extrafields del módulo
--

---Tabla `llx_product`
UPDATE `llx_extrafields` SET `param`='a:1:{s:7:"options";a:1:{s:44:"c_cfdimx_clave_prodserv:label:code::active=1";N;}}' WHERE `name`="claveprodserv" AND `elementtype`="product";
UPDATE `llx_extrafields` SET `param`='a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_unidad_medida:label:code::active=1";N;}}' WHERE `name`="umed" AND `elementtype`="product";
---Tabla `llx_facturedet`
UPDATE `llx_extrafields` SET `param`='a:1:{s:7:"options";a:1:{s:44:"c_cfdimx_clave_prodserv:label:code::active=1";N;}}' WHERE `name`="claveprodserv" AND `elementtype`="facturedet";
UPDATE `llx_extrafields` SET `param`='a:1:{s:7:"options";a:1:{s:43:"c_cfdimx_unidad_medida:label:code::active=1";N;}}' WHERE `name`="umed" AND `elementtype`="facturedet";
---Tabla `llx_facture`
UPDATE `llx_extrafields` SET `param`='a:1:{s:7:"options";a:2:{s:3:"PUE";s:34:"PUE - Pago en una sola exhibición";s:3:"PPD";s:28:" PPD - Pago en parcialidades";}}' WHERE `name`="formpagcfdi" AND `elementtype`="facture";
UPDATE `llx_extrafields` SET `param`='a:1:{s:7:"options";a:1:{s:38:"c_cfdimx_uso_cfdi:label:code::active=1";N;}}' WHERE `name`="usocfdi" AND `elementtype`="facture";


--
-- Volcado de datos para la tabla `llx_c_cfdimx_clave_prodserv`
--

INSERT IGNORE INTO `llx_c_cfdimx_clave_prodserv`(`rowid`, `code`, `label`, `active`) VALUES (1,"01010101","01010101 - No existe en el catálogo",1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_unidad_medida`
--

INSERT IGNORE INTO `llx_c_cfdimx_unidad_medida` (`rowid`, `code`, `label`, `active`) VALUES
(1, 'H87', 'Pieza', 1),
(2, 'KGM', 'Kilogramo', 1),
(3, 'MTR', 'Metro', 1),
(4, 'LTR', 'Litro', 1),
(5, 'GRM', 'Gramo', 1),
(6, 'ACT', 'Actividad', 1);


--
-- Volcado de datos para la tabla `llx_c_cfdimx_uso_cfdi`
--

INSERT IGNORE INTO `llx_c_cfdimx_uso_cfdi` (`rowid`, `code`, `label`, `active`) VALUES
(1, 'G01', 'Adquisición de mercancias', 1),
(2, 'G02', 'Devoluciones, descuentos o bonificaciones', 1),
(3, 'G03', 'Gastos en general', 1),
(4, 'I01', 'Construcciones', 1),
(5, 'I02', 'Mobilario y equipo de oficina por inversiones', 1),
(6, 'I03', 'Equipo de transporte', 1),
(7, 'I04', 'Equipo de computo y accesorios', 1),
(8, 'I05', 'Dados, troqueles, moldes, matrices y herramental', 1),
(9, 'I06', 'Comunicaciones telefónicas', 1),
(10, 'I07', 'Comunicaciones satelitales', 1),
(11, 'I08', 'Otra maquinaria y equipo', 1),
(12, 'D01', 'Honorarios médicos, dentales y gastos hospitalarios.', 1),
(13, 'D02', 'Gastos médicos por incapacidad o discapacidad', 1),
(14, 'D03', 'Gastos funerales.', 1),
(15, 'D04', 'Donativos.', 1),
(16, 'D05', 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación).', 1),
(17, 'D06', 'Aportaciones voluntarias al SAR.', 1),
(18, 'D07', 'Primas por seguros de gastos médicos.', 1),
(19, 'D08', 'Gastos de transportación escolar obligatoria.', 1),
(20, 'D09', 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones.', 1),
(21, 'D10', 'Pagos por servicios educativos (colegiaturas)', 1),
(22, 'P01', 'Por definir', 1);

INSERT IGNORE INTO `llx_c_cfdimx_uso_cfdi` (`rowid`, `code`, `label`, `active`) VALUES
(23, 'S01', 'Sin efectos fiscales', 1),
(24, 'CP01', 'Pagos', 1),
(25, 'CN01', 'Nómina', 1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_tipo_rel`
--

INSERT IGNORE INTO `llx_c_cfdimx_tipo_rel` (`rowid`, `code`, `label`, `active`) VALUES
(1, '01', 'Notas de Crédito de Documentos Relacionados', 1),
(2, '02', 'Notas de Débito de los Documentos Relacionados', 1),
(3, '03', 'Devolución de Mercancías sobre Facturas o Traslados Previos', 1),
(4, '04', 'Sustitución de los CFDI Previos', 1),
(5, '05', 'Traslados de Mercancías Facturados Previamente', 1),
(6, '06', 'Factura Generada por los Traslados Previos', 1),
(7, '07', 'CFDI por Aplicación de Anticipo', 1),
(8, '08', 'Facturas Generadas por Pagos en Parcialidades', 1),
(9, '09', 'Factura Generada por Pagos Diferidos', 1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_incoterm`
--

INSERT IGNORE INTO `llx_c_cfdimx_incoterm` (`rowid`, `code`, `label`, `active`) VALUES
(1, 'CFR', 'Coste y flete (puerto de destino convenido)', 1),
(2, 'CIF', 'Coste, seguro y flete (puerto de destino convenido)', 1),
(3, 'CPT', 'Transporte pagado hasta (el lugar de destino convenido)', 1),
(4, 'CIP', 'Transporte y seguro pagados hasta (lugar de destino convenido)', 1),
(5, 'DAP', 'Entregada en lugar', 1),
(6, 'DDP', 'Entregada derechos pagados (lugar de destino convenido)', 1),
(7, 'DPU', 'Entregada y descargada en lugar acordado', 1),
(8, 'EXW', 'En fabrica (lugar convenido)', 1),
(9, 'FCA', 'Franco transportista (lugar designado)', 1),
(10, 'FAS', 'Franco al costado del buque (puerto de carga convenido)', 1),
(11, 'FOB', 'Franco a bordo (puerto de carga convenido)', 1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_unidad_aduana`
--

INSERT IGNORE INTO `llx_c_cfdimx_unidad_aduana` (`rowid`, `code`, `label`, `active`) VALUES
(1, '01', 'Kilo', 1),
(2, '02', 'Gramo', 1),
(3, '03', 'Metro Lineal', 1),
(4, '04', 'Metro Cuadrado', 1),
(5, '05', 'Metro Cubico', 1),
(6, '06', 'Pieza', 1),
(7, '07', 'Cabeza', 1),
(8, '08', 'Litro', 1),
(9, '09', 'Par', 1),
(10, '10', 'Kilowatt', 1),
(11, '11', 'Millar', 1),
(12, '12', 'Juego', 1),
(13, '13', 'Kilowatt/Hora', 1),
(14, '14', 'Tonelada', 1),
(15, '15', 'Barril', 1),
(16, '16', 'Gramo Neto', 1),
(17, '17', 'Decenas', 1),
(18, '18', 'Cientos', 1),
(19, '19', 'Docenas', 1),
(20, '20', 'Caja', 1),
(21, '21', 'Botella', 1),
(22, '22', 'Carat', 1),
(23, '99', 'Servicio', 1);

--
-- Volcado de datos para la tabla `llx_cfdimx_catalog_retenciones`
--

INSERT IGNORE INTO `llx_cfdimx_catalog_retenciones` (`impuesto`) VALUES('IVA');
INSERT IGNORE INTO `llx_cfdimx_catalog_retenciones` (`impuesto`) VALUES('ISR');

--
-- Volcado de datos para la tabla `llx_cfdimx_catalogos`
--
INSERT IGNORE INTO `llx_cfdimx_catalogos` (`rowid`, `code`, `label`, `active`) VALUES
(1,"c_cfdimx_clave_prodserv","CFDI 3.3 - Claves Producto-Servicio",1),
(2,"c_cfdimx_unidad_medida","CFDI 3.3 - Unidades de medida CFDI",1),
(3,"c_cfdimx_uso_cfdi","CFDI 3.3 - Uso CFDI", 1),
(4,"c_cfdimx_tipo_rel","CFDI 3.3 - Tipo Relación",1),
(5,"c_cfdimx_regimen_f","CFDI 4.0 - Regimen Fiscal",1),
(6,"c_cfdimx_incoterm","CCE - Claves Incoterm", 1),
(7,"c_cfdimx_unidad_aduana","CCE - CUnidades Aduana",1),
(8,"c_cfdimx_f_arancelaria","CCE - Fracción Arancelaria",1),
(33,"c_cfdimx_formapago","CFDI 3.3,4.0 - Formas de Pago",1);

--
-- Volcado de datos para la tabla `llx_cfdimx_catalogos`
--
INSERT IGNORE INTO `llx_c_cfdimx_regimen_f` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'601',	'General de Ley Personas Morales',	1),
(2,	'603',	'Personas Morales con Fines no Lucrativos',	1),
(3,	'605',	'Sueldos y Salarios e Ingresos Asimilados a Salarios',	1),
(4,	'606',	'Arrendamiento',	1),
(5,	'607',	'Régimen de Enajenación o Adquisición de Bienes',	1),
(6,	'608',	'Demás ingresos',	1),
(7,	'609',	'Consolidación',	1),
(8,	'610',	'Residentes en el Extranjero sin Establecimiento Permanente en México',	1),
(9,	'611',	'Ingresos por Dividendos (socios y accionistas)',	1),
(10,	'612',	'Personas Físicas con Actividades Empresariales y Profesionales',	1),
(11,	'614',	'Ingresos por intereses',	1),
(12,	'615',	'Régimen de los ingresos por obtención de premios',	1),
(13,	'616',	'Sin obligaciones fiscales',	1),
(14,	'620',	'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',	1),
(15,	'621',	'Incorporación Fiscal',	1),
(16,	'622',	'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',	1),
(17,	'623',	'Opcional para Grupos de Sociedades',	1),
(18,	'624',	'Coordinados',	1),
(19,	'625',	'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',	1),
(20,	'626',	'Regimen Simplificado de Confianza (RESICO)',	1),
(21,	'628',	'Hidrocarburos',	1),
(22,	'629',	'De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales',	1),
(23,	'630',	'Enajenación de acciones en bolsa de valores',	1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_objimpuesto`
--
INSERT IGNORE INTO `llx_c_cfdimx_objimpuesto` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'01',	'01 - No objeto de impuesto',	1),
(2,	'02',	'02 - Si objeto de impuesto',	1),
(3,	'03',	'03 - Si objeto del impuesto y no obligado al desglose',	1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_husoh`
--
INSERT IGNORE INTO `llx_c_cfdimx_husoh` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'1',	'America/Cancun',	1),
(2,	'2',	'America/Chihuahua',	1),
(3,	'3',	'America/Hermosillo',	1),
(4,	'4',	'America/Mazatlan',	1),
(5,	'5',	'America/Merida',	1),
(6,	'6',	'America/Mexico_City',	1),
(7,	'7',	'America/Tijuana',	1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_exportacion`
--
INSERT IGNORE INTO `llx_c_cfdimx_exportacion` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'01',	'01 - No Aplica',	1),
(2,	'02',	'02 - Definitiva',	1),
(3,	'03',	'03 - Temporal',	1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_formapago`
--
INSERT IGNORE INTO `llx_c_cfdimx_formapago` (`rowid`, `code`, `label`, `active`, `cod_doli`) VALUES
(1,	'01',	'Efectivo',	1,	'LIQ'),
(2,	'02',	'Cheque nominativo',	1,	'CHQ'),
(3,	'03',	'Transferencia electrónica de fondos',	1,	'VIR'),
(4,	'04',	'Tarjeta de crédito',	1,	'CB'),
(5,	'05',	'Monedero electrónico',	0,	'ME05'),
(6,	'06',	'Dinero electrónico',	0,	'DE06'),
(7,	'08',	'Vales de despensa',	0,	'VDD08'),
(8,	'12',	'Dación en pago',	0,	'DEP12'),
(9,	'13',	'Pago por subrogación',	0,	'PPS13'),
(10,	'14',	'Pago por consignación',	0,	'PPC14'),
(11,	'15',	'Condonación',	0,	'C15'),
(12,	'17',	'Compensación',	0,	'C17'),
(13,	'23',	'Novación',	0,	'N23'),
(14,	'24',	'Confusión',	0,	'C24'),
(15,	'25',	'Remisión de deuda',	0,	'RDD25'),
(16,	'26',	'Prescripción o caducidad',	0,	'POC26'),
(17,	'27',	'A satisfacción del acreedor',	0,	'ASDA27'),
(18,	'28',	'Tarjeta de débito',	1,	'TDD28'),
(19,	'29',	'Tarjeta de servicios',	0,	'TDS29'),
(20,	'30',	'Aplicación de anticipos',	0,	'AA30'),
(21,	'31',	'Intermediario pagos',	0,	'IP31'),
(22,	'99',	'Por definir',	1,	'PD99');

--
-- Volcado de datos para la tabla `llx_c_cfdimx_periodicidad`
--
INSERT IGNORE INTO `llx_c_cfdimx_periodicidad` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'01',	'Diario',	1),
(2,	'02',	'Semanal',	1),
(3,	'03',	'Quincenal',	1),
(4,	'04',	'Mensual',	1),
(5,	'05',	'Bimestral',	1);

--
-- Volcado de datos para la tabla `llx_c_cfdimx_meses`
--
INSERT IGNORE INTO `llx_c_cfdimx_meses` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'01',	'Enero',	1),
(2,	'02',	'Febrero',	1),
(3,	'03',	'Marzo',	1),
(4,	'04',	'Abril',	1),
(5,	'05',	'Mayo',	1),
(6,	'06',	'Junio',	1),
(7,	'07',	'Julio',	1),
(8,	'08',	'Agosto',	1),
(9,	'09',	'Septiembre',	1),
(10,	'10',	'Octubre',	1),
(11,	'11',	'Noviembre',	1),
(12,	'12',	'Diciembre',	1),
(13,	'13',	'Enero-Febrero',	1),
(14,	'14',	'Marzo-Abril',	1),
(15,	'15',	'Mayo-Junio',	1),
(16,	'16',	'Julio-Agosto',	1),
(17,	'17',	'Septiembre-Octubre',	1),
(18,	'18',	'Noviembre-Diciembre',	1);

INSERT IGNORE INTO `llx_c_cfdimx_tipo_facturas` (`rowid`, `code`, `label`, `active`) VALUES
(1,	'1',	'Factura Estándar',	1),
(2,	'2',	'Recibo de Honorarios',	1),
(3,	'3',	'Recibo de Arrendamiento',	1),
(4,	'4',	'Nota de Crédito',	1),
(5,	'5',	'Factura de Fletes',	1),
(6,	'6',	'Factura RIF',	1),
(7,	'7',	'Factura Traslado',	1),
(8,	'8',	'Factura Global',	1);