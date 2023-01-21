FROM php:8.1-fpm

# Copiar el contenido de su proyecto en el contenedor
COPY . /var/www/html

# Establecer /var/www/html como el directorio de trabajo
WORKDIR /var/www/html

# Ejecutar los siguientes comandos para instalar las dependencias de PHP
RUN apt-get update && \
    apt-get install -y \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip

# Establecer los permisos para el directorio "public"
RUN chmod -R 755 public
