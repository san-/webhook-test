FROM php:8.2-cli

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar arquivos da aplicação
COPY . .

# Instalar dependências do PHP
RUN composer install --no-dev --optimize-autoloader

# Criar diretórios necessários e definir permissões
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 755 storage bootstrap/cache

# Gerar chave da aplicação (caso não exista)
RUN php artisan key:generate --force || true

# Executar migrações
RUN php artisan migrate --force

# Expor porta 8000
EXPOSE 8000

# Comando para iniciar a aplicação
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]