# Build stage
FROM php:8.2-fpm-alpine AS builder

# Install build dependencies and PHP extensions
RUN apk update && apk add --no-cache \
    build-base \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    curl \
    oniguruma-dev

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
# COPY . .

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Disable automatic loading of bcmath extension by renaming the .ini file
RUN mv /usr/local/etc/php/conf.d/docker-php-ext-bcmath.ini /usr/local/etc/php/conf.d/docker-php-ext-bcmath.ini.disabled

# Install Composer dependencies without dev dependencies
# RUN composer install --no-dev --optimize-autoloader

# Final stage
FROM php:8.2-fpm-alpine

# Install necessary runtime dependencies (if any)
RUN apk update && apk add --no-cache \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libxml2 \
    libzip \
    oniguruma \
# Install docker For sail up command
#     bash \
#     ncurses \
#     docker-cli \
#     docker-compose \
# Install Xdebug
    $PHPIZE_DEPS \
    linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# **Install nano**
RUN apk add --no-cache nano

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Git
RUN apk add --no-cache git

# Install Bash
# RUN apk add --no-cache bash

# Copy custom PHP configuration
COPY ./-services&configs/-php-config/php.ini /usr/local/etc/php/

# Set working directory
WORKDIR /var/www

# Copy PHP extensions from builder stage
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions

# Copy PHP configuration files from the builder stage
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Copy the application files from the builder stage
COPY --from=builder /var/www /var/www

# Ensure correct permissions for the application files
RUN chown -R www-data:www-data /var/www

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
