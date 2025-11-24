FROM php:8.3-apache

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql

# Habilitar Apache mod_rewrite (necessário para Laravel)
RUN a2enmod rewrite

# Instalar Composer na última versão
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivo de configuração do Apache (opcional)
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Expor porta
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
