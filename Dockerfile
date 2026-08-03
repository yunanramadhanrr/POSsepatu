FROM php:8.2-cli

WORKDIR /app

# Install dependencies yang dibutuhkan Laravel (jika pakai Laravel)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy seluruh file project ke dalam container
COPY . .

# Install dependency PHP
RUN composer install --no-interaction

# Expose port
EXPOSE 8080

# Perintah untuk menjalankan aplikasi
CMD php -S 0.0.0.0:${PORT:-8080} -t public