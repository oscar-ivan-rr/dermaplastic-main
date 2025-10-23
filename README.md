## TABLE OF CONTENTS

- [TABLE OF CONTENTS](#table-of-contents)
- [LICENSE](#license)
	- [System Environment / Requirements](#system-environment--requirements)
- [DOCUMENTATION](#documentation)
- [INSTALLATION](#installation)
	- [Note](#note)
- [FAQ](#faq)
- [Base de datos](#base-de-datos)

## LICENSE

Dolibarr is released under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version (GPL-3+).

See the [COPYING](https://github.com/Dolibarr/dolibarr/blob/develop/COPYING) file for a full copy of the license.

Other licenses apply for some included dependencies. See [COPYRIGHT](https://github.com/Dolibarr/dolibarr/blob/develop/COPYRIGHT) for a full list.

### System Environment / Requirements

- Works with PHP 5.5+ and MariaDB 5.0.3+, MySQL 5.0.3+ or PostgreSQL 8.1.4+ (See requirements on the [Wiki](https://wiki.dolibarr.org/index.php/Prerequisite))
- Compatible with all Cloud solutions that match MySQL, PHP or PostgreSQL prerequisites.

## DOCUMENTATION

Administrator, user, developer and translator's documentations are available along with other community resources on the [Wiki](https://wiki.dolibarr.org).

## INSTALLATION

### Note 
  Para el funcionamiento de la aplicación se debe tener instalado docker y docker-compose y __crear una copia del archivo .env.example y renombrarlo a .env__ y configurar las variables de entorno.

1. Levantar el contenedor de la base de datos 
	```
	docker-compose -f docker-compose.develop.yml up -d --build
	```
2. Copiar la base de datos al contenedor
	```
	docker cp db_base.sql <nombre-contenedor-bd>:/home/
	```
3. Ingresar al contenedor de la base de datos
	```
	docker exec -it <nombre-contenedor-bd> bash
	```
4. Una vez dentro del contenedor ir a la carpeta /home
	```
	cd /home
	```
5. Cargar la base de datos ingresando la contraseña previamente establecida en el archivo .env
	```
	mysql -u root -p nombre-base-datos < db_base.sql
	```

## FAQ

- [How to contribute to Dolibarr?](https://wiki.dolibarr.org/index.php/Contribute)
- [How to report a bug?](https://wiki.dolibarr.org/index.php/Report_a_bug)
- [How to ask for a new feature?](https://wiki.dolibarr.org/index.php/Request_a_feature)
- [How to translate Dolibarr?](https://wiki.dolibarr.org/index.php/Translate)
- [How to get support?](https://wiki.dolibarr.org/index.php/Get_support)
- [How to get involved?](https://wiki.dolibarr.org/index.php/Get_involved)

## Base de datos 
- [db_base.sql](db_base.sql)


