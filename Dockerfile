# Run Composer
RUN composer install
# Copy the config files
RUN mv .env.example .env
COPY .docker/default.conf /etc/nginx/conf.d/default.conf

