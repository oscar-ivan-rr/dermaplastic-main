#!/bin/bash

# Nombre del archivo de origen
FILE="/etc/environment"

# Variables a extraer
VARIABLES=(
    PHP_EXTRA_CONFIGURE_ARGS
    APACHE_CONFDIR
    HOSTNAME
    PHP_INI_DIR
    LOCAL_DB_PORT
    PHP_EXTRA_BUILD_DEPS
    HOME
    DB_NAME
    APP_PORT
    PHP_LDFLAGS
    APP_NAME
    PHP_CFLAGS
    PHP_VERSION
    SET_ROOT_PASSWORD
    GPG_KEYS
    PHP_CPPFLAGS
    PHP_ASC_URL
    PHP_URL
    PATH
    DB_PASS
    PHPIZE_DEPS
    PWD
    PHP_SHA256
    APACHE_ENVVARS
    DB_USER
)

# Nombre del nuevo archivo
NEW_FILE="/etc/environment_ext"

# Verifica si el archivo nuevo ya existe y lo elimina si es así
if [ -f "$NEW_FILE" ]; then
    echo "El archivo $NEW_FILE ya existe. Eliminando..."
    rm "$NEW_FILE"
fi

# Iterar sobre las variables y extraerlas del archivo
for VAR in "${VARIABLES[@]}"; do
    # Utiliza grep con un patrón regular para extraer la variable
    VALUE=$(grep "^${VAR}=" "$FILE" | cut -d= -f2-)
    # Agrega comillas al valor de la variable
    VALUE="${VALUE//\"/\\\"}"  # Reemplaza comillas dobles con comillas dobles escapadas
    # Imprime la variable y su valor en el nuevo archivo
    echo "${VAR}='${VALUE}'" >> "$NEW_FILE"
done

# Indicar al usuario que se ha creado el nuevo archivo
echo "Se ha creado el archivo $NEW_FILE con las variables extraídas y sus valores entre comillas."

# Dar permisos de ejecución al archivo nuevo
chmod +x "$NEW_FILE"

# Indicar al usuario que se han establecido los permisos de ejecución
echo "Se han establecido permisos de ejecución en el archivo $NEW_FILE."
