FROM php:8.1-cli
 
# Instala extensões necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_mysql sockets \
    && rm -rf /var/lib/apt/lists/*
 
# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
 
WORKDIR /app
 
CMD ["/bin/bash"]
