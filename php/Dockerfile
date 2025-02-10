FROM php:8.2.11-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    procps


# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*


# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Set PHP configurations
RUN echo "max_execution_time = 3600" >> /usr/local/etc/php/conf.d/docker-php.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/docker-php.ini


# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer