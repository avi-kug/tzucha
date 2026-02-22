# Use official PHP image with FPM (PHP 8.2)
FROM php:8.2-fpm

# Install system dependencies required for PHP extensions
RUN apt-get update && apt-get install -y \
    pkg-config \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Configure GD extension with freetype and jpeg support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install all required PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    zip \
    xml \
    dom \
    intl \
    bcmath

# Verify GD extension is loaded
RUN php -m | grep -i gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install PHP dependencies with platform requirements ignored for build
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-req=ext-gd

# Copy rest of application files
COPY . .

# Copy nginx config
COPY nginx.conf /etc/nginx/nginx.conf

# Copy supervisor config
RUN echo '[supervisord]\n\
nodaemon=true\n\
\n\
[program:php-fpm]\n\
command=php-fpm -F\n\
autostart=true\n\
autorestart=true\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true' > /etc/supervisor/conf.d/supervisord.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port (Railway provides PORT env var)
EXPOSE 8080

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
