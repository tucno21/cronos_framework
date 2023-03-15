# Imagen base
FROM php:8.1-fpm

# Instala dependencias necesarias
RUN apt-get update \
    && apt-get install -y \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Configura PHP
RUN docker-php-ext-install pdo_mysql intl mbstring zip opcache
RUN pecl install apcu

# Habilita opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Configura Nginx
COPY nginx.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
RUN rm /etc/nginx/sites-enabled/default.dpkg-dist

# Copia los archivos de la aplicación
COPY . /var/www/html

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Exponer el puerto 8080
EXPOSE 8080
