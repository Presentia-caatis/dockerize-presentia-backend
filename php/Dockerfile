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
    procps \
    supervisor \
    nano

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Ensure Supervisor configuration directory exists
RUN mkdir -p /etc/supervisor/conf.d

# Add Laravel queue worker configurations
RUN echo "[program:store-attendance-queue]\n\
process_name=%(program_name)s_%(process_num)02d\n\
command=php /var/www/html/artisan queue:work --queue=store-attendance --tries=3\n\
autostart=true\n\
autorestart=true\n\
stopasgroup=true\n\
killasgroup=true\n\
user=www-data\n\
numprocs=5\n\
redirect_stderr=true\n\
stdout_logfile=/var/log/laravel-queue-store-attendance.log\n\
\n\
[program:import-student-queue]\n\
process_name=%(program_name)s_%(process_num)02d\n\
command=php /var/www/html/artisan queue:work --queue=import_student --tries=3\n\
autostart=true\n\
autorestart=true\n\
stopasgroup=true\n\
killasgroup=true\n\
user=www-data\n\
numprocs=2\n\
redirect_stderr=true\n\
stdout_logfile=/var/log/laravel-queue-import-student.log\n" > /etc/supervisor/conf.d/laravel-queue.conf

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer




