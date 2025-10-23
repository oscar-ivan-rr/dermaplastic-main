<?php
// $dolibarr_main_url_root='http://localhost';
// $dolibarr_main_url_root='http://mydolibarrvirtualhost';
// $dolibarr_main_url_root='http://myserver/dolibarr/htdocs';
// $dolibarr_main_url_root='http://myserver/dolibarralias';
//en caso de docker en local para desarrollo usar 'http://localhost:puerto' 
//pero en la web el dns completo 'https://subdomino.dominio.com';
$localurl='http://:'.getenv('APP_PORT') ?: '8080';
$dolibarr_main_url_root= getenv('APP_URL') ? getenv('APP_URL') : $localurl;

// dolibarr_main_document_root
// This parameter contains absolute file system directory of Dolibarr
// htdocs directory
// Examples:
// $dolibarr_main_document_root='/var/www/dolibarr/htdocs';
// $dolibarr_main_document_root='C:/My web sites/dolibarr/htdocs';
//
$dolibarr_main_document_root='/var/www/html/htdocs';
$dolibarr_main_data_root='/var/www/html/documents';
$dolibarr_main_url_root_alt='/custom';
$dolibarr_main_document_root_alt='/var/www/html/htdocs/custom';
$dolibarr_main_db_host= getenv('DB_HOST') ? getenv('DB_HOST') : 'mysql';
$dolibarr_main_db_name= getenv('DB_NAME') ? getenv('DB_NAME') : 'dolibarr';
$dolibarr_main_db_port= getenv('DB_PORT') ? getenv('DB_PORT') : '3306';
$dolibarr_main_db_user= getenv('DB_USER') ? getenv('DB_USER') : 'test';
$dolibarr_main_db_pass= getenv('DB_PASS') ? getenv('DB_PASS') : (getenv('SET_ROOT_PASSWORD') ? getenv('SET_ROOT_PASSWORD') : 'test');

$dolibarr_main_db_type='mysqli';


$dolibarr_main_db_character_set='utf8';
$dolibarr_main_db_collation='utf8_unicode_ci';


$dolibarr_main_instance_unique_id='84b5bc91fasdkaskdoa83b56e458db71e0adac2b62';

$dolibarr_main_authentication='dolibarr';

//##################
// Security
//##################

// dolibarr_main_force_https
// This parameter allows to force the HTTPS mode.
// 0 = No forced redirect
// 1 = Force redirect to https, until SCRIPT_URI start with https into response
// 2 = Force redirect to https, until SERVER["HTTPS"] is 'on' into response
$dolibarr_main_force_https='0';

$dolibarr_main_prod='0';

$dolibarr_main_restrict_os_commands='mysqldump, mysql, pg_dump, pgrestore';


// dolibarr_nocsrfcheck
// This parameter can be used to disable CSRF protection.
// This might be required if you access Dolibarr behind a proxy that make
// URL rewriting, to avoid false alarms.
// Default value: 0
// Possible values: 0 or 1
// Examples:
// $dolibarr_nocsrfcheck='0';
//
$dolibarr_nocsrfcheck='0';

// dolibarr_mailing_limit_sendbyweb
// Can set a limit for mailing send by web. This overwrite database value. Can be used to restrict on OS level.
// Default value: '25'
// Examples: '-1' (sending by web is forbidden)
// $dolibarr_mailing_limit_sendbyweb='25';

// dolibarr_mailing_limit_sendbycli
// Can set a limit for mailing send by cli. This overwrite database value. Can be used to restrict on OS level.
// Default value: '0' (no hard limit, use soft database value if exists)
// Examples: '-1' (sending by cli is forbidden)
// $dolibarr_mailing_limit_sendbycli='0';


$dolibarr_main_db_prefix='llx_';


// dolibarr_strict_mode
// Set this to 1 to enable the PHP strict mode. For dev environment only.
// Default value: 0 (use database value if exist)
// Examples:
// $dolibarr_strict_mode=0;