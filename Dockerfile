# Copiar el contenido de su proyecto en el contenedor
COPY . /var/www/html

# Establecer /var/www/html como el directorio de trabajo
WORKDIR /var/www/html


# Run Composer
RUN composer install
# Copy the config files
RUN mv .env.example .env
COPY .docker/default.conf /etc/nginx/conf.d/default.conf

# Expose the port
EXPOSE 80

