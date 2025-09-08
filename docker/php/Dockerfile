FROM php:8.2-apache

# Extensiones necesarias para MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilitar mod_rewrite para URLs limpias si lo necesitas
RUN a2enmod rewrite

# VirtualHost: sirve la carpeta /var/www/html (tu ./app)
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
