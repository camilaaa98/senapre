FROM php:8.2-apache

# Instalar extensión PostgreSQL y otras necesarias
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite para URLs limpias
RUN a2enmod rewrite headers

# Configuración de Apache
RUN { \
    echo '<Directory /var/www/html>'; \
    echo '    Options Indexes FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '</Directory>'; \
} >> /etc/apache2/apache2.conf

# Copiar el proyecto
COPY . /var/www/html/

# Crear directorios necesarios
RUN mkdir -p /var/www/html/uploads \
    && mkdir -p /var/www/html/backup_biometria \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/backup_biometria

# Permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Exponer puerto 80
EXPOSE 80
