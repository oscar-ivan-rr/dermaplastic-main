FROM php:7.4-apache as base

WORKDIR /var/www/html

#gd
RUN apt-get update && apt-get install -y \
		libfreetype-dev \
		libjpeg-dev \
		libpng-dev \
		libicu-dev \
		libzip-dev \
		cron \
		vim \
	&& docker-php-ext-configure gd \
	&& docker-php-ext-install -j$(nproc) gd

#install mysql & mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

#mycrypt
# RUN apt-get install -y libmcrypt-dev \
# 	&& docker-php-ext-install mcrypt

#Calendar
RUN docker-php-ext-configure calendar && docker-php-ext-install calendar

#instalar intl
RUN apt-get install libicu-dev -y && docker-php-ext-configure intl && docker-php-ext-install intl

#zip
RUN docker-php-ext-install zip

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

#reemplaza la carpeta a servir por defecto a /var/www/html/htdocs del archivo de configuracion de apache
RUN sed -i 's|/var/www/html|/var/www/html/htdocs|g' /etc/apache2/sites-available/000-default.conf

#Permisos carpeta
RUN mkdir -p /var/www/html/documents
RUN mkdir -p /var/www/html/documents/bitacoras/

# RUN chown -R www-data:www-data /var/www/html
# RUN chmod -R 755 /var/www/html

# RUN pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug

# RUN touch /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# RUN echo "zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20170718/xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "xdebug.mode=debug,profile,trace" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
#     && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
# 	&& echo "xdebug.remote_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \

# Cambiar configuracion de php.ini
RUN echo "max_execution_time = 1000" >> "$PHP_INI_DIR/php.ini"
RUN echo "max_input_time = 1000" >> "$PHP_INI_DIR/php.ini"
RUN echo "memory_limit = 1024M" >> "$PHP_INI_DIR/php.ini"
RUN echo "post_max_size = 1024M" >> "$PHP_INI_DIR/php.ini"
RUN echo "upload_max_filesize = 1024M" >> "$PHP_INI_DIR/php.ini"
RUN echo "max_input_vars = 10000" >> "$PHP_INI_DIR/php.ini"

#Disable cron for testing
COPY list.cron .
#RUN crontab list.cron

# Script para extraer variables de entorno y ponerlas en /etc/environment_ext
COPY extract_variables.sh /etc/extract_variables.sh
RUN chmod +x /etc/extract_variables.sh

EXPOSE 80

FROM base as production
COPY . .
RUN chown -R www-data:www-data /var/www/html/documents
#RUN chmod +x /var/www/html/scripts/cron/cron_run_jobs.php
CMD /bin/sh -c "printenv > /etc/environment && /etc/extract_variables.sh && service cron start && apache2-foreground"


FROM base as development
CMD /bin/sh -c "printenv > /etc/environment && /etc/extract_variables.sh && service cron start && apache2-foreground"
