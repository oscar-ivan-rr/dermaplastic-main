SET SQL_SAFE_UPDATES = 0;
SET FOREIGN_KEY_CHECKS = 0;

-- ┌────────────┐
-- │ Categorias │
-- └────────────┘
TRUNCATE llx_categorie_fournisseur;
TRUNCATE llx_categorie_account;
TRUNCATE llx_categorie_contact;
TRUNCATE llx_categorie_lang;
TRUNCATE llx_categorie_member;
TRUNCATE llx_categorie_product;
TRUNCATE llx_categorie_societe;
TRUNCATE llx_categorie_user;
TRUNCATE llx_categorie_warehouse;
TRUNCATE llx_categorie_project;
TRUNCATE llx_categories_extrafields;
TRUNCATE llx_categorie;

-- ┌──────┐
-- │ CFDI │
-- └──────┘
TRUNCATE llx_cfdmix_control;
TRUNCATE llx_cfdimx_type_document;
TRUNCATE llx_cfdimx_retencionesdet;
TRUNCATE llx_cfdimx_retenciones_locales;
TRUNCATE llx_cfdimx_retenciones;
TRUNCATE llx_cfdimx_recepcion_pagos_traslados;
TRUNCATE llx_cfdimx_recepcion_pagos_retenciones;
TRUNCATE llx_cfdimx_recepcion_pagos_relacion_facturas;
TRUNCATE llx_cfdimx_recepcion_pagos_docto_relacionado;
TRUNCATE llx_cfdimx_recepcion_pagos;
TRUNCATE llx_cfdimx_nomina_retenciones;
TRUNCATE llx_cfdimx_nomina_percepciones;
TRUNCATE llx_cfdimx_nomina_incapacidades;
TRUNCATE llx_cfdimx_nomina_impuestos_locales;
TRUNCATE llx_cfdimx_nomina_hrs_extra;
TRUNCATE llx_cfdimx_nomina_deducciones;
TRUNCATE llx_cfdimx_nomina_conceptos;
TRUNCATE llx_cfdimx_nomina_cabecera;
TRUNCATE llx_cfdimx_nomina;
TRUNCATE llx_cfdimx_facturefourn;
TRUNCATE llx_cfdimx_facture_mode_paiement;
TRUNCATE llx_cfdimx_facture_comercio_extranjero_mercancia;
TRUNCATE llx_cfdimx_facture_comercio_extranjero;
TRUNCATE llx_cfdimx_facturedet;
TRUNCATE llx_cfdimx_descuentos;
TRUNCATE llx_cfdimx_control_timbrado;
TRUNCATE llx_cfdimx_config_retenciones_locales;
TRUNCATE llx_cfdimx;

-- ┌────────┐
-- │ Agenda │
-- └────────┘
TRUNCATE llx_actioncomm_resources;
TRUNCATE llx_actioncomm_reminder;
TRUNCATE llx_actioncomm_extrafields;
TRUNCATE llx_actioncomm;

-- ┌──────┐
-- │ RRHH │
-- └──────┘
TRUNCATE llx_holiday_users;
TRUNCATE llx_holiday_logs;
TRUNCATE llx_holiday_extrafields;
TRUNCATE llx_holiday;

TRUNCATE llx_event_element;
TRUNCATE llx_events;

-- ┌──────────────┐
-- │ Bancos/Cajas │
-- └──────────────┘
-- Bancos
TRUNCATE llx_bank_url;
TRUNCATE llx_bank_class;
TRUNCATE llx_bank_categ;
TRUNCATE llx_bank_account_extrafields;
TRUNCATE llx_bank_account;
TRUNCATE llx_bank;

-- Pagos
TRUNCATE llx_payment_expensereport;
TRUNCATE llx_payment_various;
TRUNCATE llx_payment_salary_extrafields;
TRUNCATE llx_payment_salary;
TRUNCATE llx_payment_loan;
TRUNCATE llx_payment_donation;

-- ┌─────┐
-- │ POS │
-- └─────┘
TRUNCATE llx_localtax;
TRUNCATE llx_takepos_floor_tables;
TRUNCATE llx_pos_facture;
TRUNCATE llx_pos_cash;
TRUNCATE llx_pos_control_cash;
TRUNCATE llx_pos_places;
TRUNCATE llx_pos_cash_fence;
TRUNCATE llx_pos_moviments;
TRUNCATE llx_pos_paiement_ticket;
TRUNCATE llx_mrp_production;
TRUNCATE llx_mrp_myobject_extrafields;
TRUNCATE llx_mrp_mo;
TRUNCATE llx_pos_stock_mouvement;
TRUNCATE llx_pos_ticket;
TRUNCATE llx_pos_ticketdet;
TRUNCATE llx_pos_ticketdet_deleted;

-- ┌──────────┐
-- │ Finanzas │
-- └──────────┘
TRUNCATE llx_societe_remise_except;

-- Pagos
TRUNCATE llx_paiementfourn_facturefourn;
TRUNCATE llx_paiementfourn;
TRUNCATE llx_paiementcharge;
TRUNCATE llx_paiement_facture;
TRUNCATE llx_paiement;

-- Contabilidad
TRUNCATE llx_accounting_fiscalyear;
TRUNCATE llx_accounting_bookkeeping_tmp;
TRUNCATE llx_accounting_bookkeeping;
TRUNCATE llx_accounting_account;

-- Facturas cliente
TRUNCATE llx_facturedet_rec_extrafields;
TRUNCATE llx_facturedet_rec;
TRUNCATE llx_facture_rec_extrafields;
TRUNCATE llx_facture_rec;
TRUNCATE llx_facturedet_extrafields;
TRUNCATE llx_facturedet;
TRUNCATE llx_facture_extrafields;
TRUNCATE llx_facture;

-- Facturas proveedor
TRUNCATE llx_facture_fourn_det_extrafields;
TRUNCATE llx_facture_fourn_det;
TRUNCATE llx_facture_fourn_extrafields;
TRUNCATE llx_facture_fourn;

-- Gastos
TRUNCATE llx_expensereport_rules;
TRUNCATE llx_expensereport_ik;
TRUNCATE llx_expensereport_extrafields;
TRUNCATE llx_expensereport_det;
TRUNCATE llx_expensereport;

-- Deducciones
TRUNCATE llx_prelevement_rejet;
TRUNCATE llx_prelevement_facture_demande;
TRUNCATE llx_prelevement_facture;
TRUNCATE llx_prelevement_lignes;
TRUNCATE llx_prelevement_bons;

-- ┌───────────┐
-- │ Comercial │
-- └───────────┘
-- Enterprise Content Management
TRUNCATE llx_ecm_files;
TRUNCATE llx_ecm_directories;

-- Pedido cliente
TRUNCATE llx_commandedet_extrafields;
TRUNCATE llx_commande_extrafields;
TRUNCATE llx_commandedet;
TRUNCATE llx_commande;

-- Pedido proveedor
TRUNCATE llx_commande_fournisseurdet_extrafields;
TRUNCATE llx_commande_fournisseurdet;
TRUNCATE llx_commande_fournisseur_log;
TRUNCATE llx_commande_fournisseur_extrafields;
TRUNCATE llx_commande_fournisseur_dispatch_extrafields;
TRUNCATE llx_commande_fournisseur_dispatch;
TRUNCATE llx_commande_fournisseur;

-- Recepcion
TRUNCATE llx_reception_extrafields;
TRUNCATE llx_reception;

-- Propuesta cliente
TRUNCATE llx_propaldet_extrafields;
TRUNCATE llx_propaldet;
TRUNCATE llx_propal_merge_pdf_product;
TRUNCATE llx_propal_extrafields;
TRUNCATE llx_propal;

-- Propuesta proveedor
TRUNCATE llx_supplier_proposaldet_extrafields;
TRUNCATE llx_supplier_proposaldet;
TRUNCATE llx_supplier_proposal_extrafields;
TRUNCATE llx_supplier_proposal;

-- Expedicion
TRUNCATE llx_expeditiondet_extrafields;
TRUNCATE llx_expeditiondet_batch;
TRUNCATE llx_expeditiondet;
TRUNCATE llx_expedition_package;
TRUNCATE llx_expedition_extrafields;
TRUNCATE llx_expedition;

-- ┌───────────┐
-- │ Proyectos │
-- └───────────┘
TRUNCATE llx_projet_task_extrafields;
TRUNCATE llx_projet_task_time;
TRUNCATE llx_projet_task;
TRUNCATE llx_projet_extrafields;
TRUNCATE llx_projet;

-- ┌─────────────────────┐
-- │ Productos/Servicios │
-- └─────────────────────┘
-- Otros
TRUNCATE complementary_products;
TRUNCATE llx_tva;

-- Almacen
TRUNCATE llx_entrepot_extrafields;
DELETE FROM llx_entrepot WHERE NOT ref='Almacén 1' AND NOT ref='Almacén 2';
TRUNCATE llx_product_warehouse_properties;

-- Stock
TRUNCATE llx_product_stock_revised;
TRUNCATE stock_mouvement_auto;
TRUNCATE llx_stock_mouvement_draft;
TRUNCATE llx_stock_mouvement;
TRUNCATE llx_product_batch;
TRUNCATE llx_product_stock;

-- Inventario
TRUNCATE llx_inventorydet;
TRUNCATE llx_inventory;

-- Producto
TRUNCATE llx_product_pricerules;
TRUNCATE llx_product_price_by_qty;
TRUNCATE llx_product_price;
TRUNCATE llx_product_lot_extrafields;
TRUNCATE llx_product_lot;
TRUNCATE llx_product_lang;
TRUNCATE llx_product_fournisseur_price_log;
TRUNCATE llx_product_fournisseur_price_extrafields;
TRUNCATE llx_product_fournisseur_price;
TRUNCATE llx_product_extrafields;
TRUNCATE llx_product_customer_price_log;
TRUNCATE llx_product_customer_price;
TRUNCATE llx_product_attribute_value;
TRUNCATE llx_product_attribute_combination;
TRUNCATE llx_product_attribute_combination2val;
TRUNCATE llx_product_attribute;
TRUNCATE llx_product_association;
TRUNCATE llx_product;

-- ┌──────────────────────┐
-- │ Clientes/Proveedores │
-- └──────────────────────┘
-- Contratos
TRUNCATE llx_contratdet_log;
TRUNCATE llx_contratdet_extrafields;
TRUNCATE llx_contratdet;
TRUNCATE llx_contrat_extrafields;
TRUNCATE llx_contrat;

-- Expediente
TRUNCATE llx_fichinterdet_rec;
TRUNCATE llx_fichinterdet_extrafields;
TRUNCATE llx_fichinterdet;
TRUNCATE llx_fichinter_rec;
TRUNCATE llx_fichinter_extrafields;
TRUNCATE llx_fichinter;

-- Entregas
TRUNCATE llx_livraisondet_extrafields;
TRUNCATE llx_livraisondet;
TRUNCATE llx_livraison_extrafields;
TRUNCATE llx_livraison;

-- Miembros
TRUNCATE llx_adherent_extrafields;
TRUNCATE llx_adherent;
TRUNCATE llx_adherent_type_lang;
TRUNCATE llx_adherent_type_extrafields;
TRUNCATE llx_adherent_type;

-- Terceros
TRUNCATE llx_societe_rib;
TRUNCATE llx_societe_remise_supplier;
TRUNCATE llx_societe_remise;
TRUNCATE llx_societe_prices;
TRUNCATE llx_societe_log;
TRUNCATE llx_societe_extrafields;
TRUNCATE llx_societe_contacts;
TRUNCATE llx_societe_commerciaux;
TRUNCATE llx_societe_address;
TRUNCATE llx_societe_account;
TRUNCATE llx_societe;

-- Contactos
TRUNCATE llx_socpeople_extrafields;
TRUNCATE llx_socpeople;

-- ┌──────────────┐
-- │ Diccionarios │
-- └──────────────┘
TRUNCATE llx_c_ziptown;
TRUNCATE llx_c_price_global_variable_updater;
TRUNCATE llx_c_price_global_variable;
TRUNCATE llx_c_price_expression;
TRUNCATE llx_c_field_list;
TRUNCATE llx_c_email_senderprofile;

-- ┌──────────┐
-- │ Usuarios │
-- └──────────┘
TRUNCATE llx_usergroup_user;
DELETE FROM llx_user_rights WHERE fk_user <> 1;
DELETE FROM llx_user WHERE NOT login='soporte' AND NOT login='invitado' AND NOT login='mcallen';

-- ┌───────┐
-- │ Otros │
-- └───────┘
TRUNCATE llx_zapier_hook_extrafields;
TRUNCATE llx_zapier_hook;
TRUNCATE llx_website_page;
TRUNCATE llx_website_extrafields;
TRUNCATE llx_website;
TRUNCATE llx_resource_extrafields;
TRUNCATE llx_resource;
TRUNCATE llx_advtargetemailing;
TRUNCATE llx_asset_extrafields;
TRUNCATE llx_asset;
TRUNCATE llx_asset_type_extrafields;
TRUNCATE llx_asset_type;
TRUNCATE llx_bordereau_cheque;
TRUNCATE llx_bookmark;
TRUNCATE llx_ticket_extrafields;
TRUNCATE llx_ticket;
TRUNCATE llx_subscription;
TRUNCATE llx_printing;
TRUNCATE llx_budget_lines;
TRUNCATE llx_budget;
TRUNCATE llx_boxes;
TRUNCATE llx_bom_bomline_extrafields;
TRUNCATE llx_bom_bomline;
TRUNCATE llx_bom_bom_extrafields;
TRUNCATE llx_bom_bom;
TRUNCATE llx_element_resources;
TRUNCATE llx_element_element;
TRUNCATE llx_element_contact;
TRUNCATE llx_don_extrafields;
TRUNCATE llx_don;
TRUNCATE llx_overwrite_trans;
TRUNCATE llx_opensurvey_user_studs;
TRUNCATE llx_opensurvey_user_formanswers;
TRUNCATE llx_opensurvey_sondage;
TRUNCATE llx_opensurvey_formquestions;
TRUNCATE llx_opensurvey_comments;
TRUNCATE llx_onlinesignature;
TRUNCATE llx_oauth_token;
TRUNCATE llx_oauth_state;
TRUNCATE llx_notify_def_object;
TRUNCATE llx_notify_def;
TRUNCATE llx_notify;
TRUNCATE llx_mailing_unsubscribe;
TRUNCATE llx_mailing_cibles;
TRUNCATE llx_mailing;
TRUNCATE llx_loan_schedule;
TRUNCATE llx_loan;
TRUNCATE llx_links;
TRUNCATE llx_import_model;
TRUNCATE llx_export_model;
TRUNCATE llx_export_compta;
TRUNCATE llx_estadov;
TRUNCATE llx_establishment;
TRUNCATE llx_deplacement;
TRUNCATE llx_default_values;
TRUNCATE llx_comment;
TRUNCATE llx_chargesociales;
TRUNCATE llx_blockedlog_authority;
TRUNCATE llx_blockedlog;

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
