FROM php:8.2-fpm

# Instalar dependencias del SO
RUN apt-get update && apt-get install -y \
    curl \
    git \
    zip \
    unzip \
    supervisor \
    nginx \
    net-tools \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar extensiones PHP necesarias para Laravel
RUN docker-php-ext-install pdo pdo_mysql

# Crear directorio de trabajo
WORKDIR /app

# Copiar código Laravel
COPY ./app .

# Instalar dependencias Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Crear directorios necesarios
RUN mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache

# Dar permisos a storage
RUN chown -R www-data:www-data /app/storage /app/bootstrap

# Copiar configuración de Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crear directorio de logs de supervisor
RUN mkdir -p /var/log/supervisor

# Exponer puerto
EXPOSE 8000

# Comando por defecto
CMD ["php", "artisan", "serve", "--host=0.0.0.0"]
