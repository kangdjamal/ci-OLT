FROM php:8.2-fpm

# 1. Install dependensi sistem: PHP Extensions & Python
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    libicu-dev \
    libsqlite3-dev \
    zip \
    unzip \
    && docker-php-ext-install intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install library Python secara global di dalam kontainer
RUN pip3 install --no-cache-dir netmiko python-dotenv --break-system-packages

WORKDIR /var/www/html

# 3. Kita tidak menggunakan COPY . . agar Anda bisa terus bereksperimen di folder asli
# Folder akan dihubungkan via Docker Compose (Volume)

EXPOSE 9000
CMD ["php-fpm"]
