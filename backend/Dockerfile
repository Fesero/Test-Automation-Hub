# Используем официальный образ PHP 8.2 с FPM
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    supervisor \
    libhiredis-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install \
    pdo_pgsql \
    zip \
    gd \
    mbstring \
    exif \
    pcntl \
    bcmath \
    xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/* # Очистка

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www/html

# Копируем исходники
# Важно: Копируем конфиги supervisor ДО основного кода,
# чтобы избежать пересборки при изменении кода приложения
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

# Создаем директорию для логов Supervisor
RUN mkdir -p /var/log/supervisor

COPY . .

# Устанавливаем зависимости
RUN composer install --optimize-autoloader --no-dev

# Права доступа
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log/supervisor
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

# Запускаем supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]