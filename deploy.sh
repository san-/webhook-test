#!/bin/bash

# Script de Deploy - Webhook Dashboard
# Autor: GitHub Copilot
# Data: 25/09/2025

echo "=== Webhook Dashboard - Deploy Script ==="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log colorido
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    log_error "artisan não encontrado. Execute este script a partir do diretório raiz do Laravel."
    exit 1
fi

log_info "Iniciando deploy do Webhook Dashboard..."

# 1. Verificar requisitos
log_info "Verificando requisitos..."

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log_success "PHP encontrado: $PHP_VERSION"
else
    log_error "PHP não encontrado. Instale PHP 8.1 ou superior."
    exit 1
fi

# Verificar Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version)
    log_success "Composer encontrado: $COMPOSER_VERSION"
else
    log_error "Composer não encontrado. Instale o Composer."
    exit 1
fi

# 2. Instalar dependências
log_info "Instalando dependências..."
composer install --no-dev --optimize-autoloader --no-interaction
if [ $? -eq 0 ]; then
    log_success "Dependências instaladas com sucesso"
else
    log_error "Erro ao instalar dependências"
    exit 1
fi

# 3. Configurar ambiente
log_info "Configurando ambiente..."

# Copiar .env se não existir
if [ ! -f ".env" ]; then
    cp .env.example .env
    log_success "Arquivo .env criado"
else
    log_warning "Arquivo .env já existe"
fi

# Gerar chave da aplicação
log_info "Gerando chave da aplicação..."
php artisan key:generate --force
if [ $? -eq 0 ]; then
    log_success "Chave da aplicação gerada"
else
    log_error "Erro ao gerar chave"
    exit 1
fi

# 4. Configurar banco de dados
log_info "Configurando banco de dados SQLite..."

# Criar arquivo do banco se não existir
if [ ! -f "database/database.sqlite" ]; then
    touch database/database.sqlite
    log_success "Banco SQLite criado"
else
    log_warning "Banco SQLite já existe"
fi

# Executar migrações
log_info "Executando migrações..."
php artisan migrate --force
if [ $? -eq 0 ]; then
    log_success "Migrações executadas com sucesso"
else
    log_error "Erro ao executar migrações"
    exit 1
fi

# 5. Configurar permissões
log_info "Configurando permissões..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 database/database.sqlite
log_success "Permissões configuradas"

# 6. Otimizar aplicação
log_info "Otimizando aplicação para produção..."

# Cache de configuração
php artisan config:cache
if [ $? -eq 0 ]; then
    log_success "Cache de configuração criado"
else
    log_warning "Erro ao criar cache de configuração"
fi

# Cache de rotas
php artisan route:cache
if [ $? -eq 0 ]; then
    log_success "Cache de rotas criado"
else
    log_warning "Erro ao criar cache de rotas"
fi

# Cache de views
php artisan view:cache
if [ $? -eq 0 ]; then
    log_success "Cache de views criado"
else
    log_warning "Erro ao criar cache de views"
fi

# 7. Configuração do .env para produção
log_info "Configurando .env para produção..."

# Perguntar URL do site
read -p "Digite a URL do site (ex: https://webhook.save.eti.br): " APP_URL
if [ ! -z "$APP_URL" ]; then
    sed -i "s|APP_URL=.*|APP_URL=$APP_URL|g" .env
    log_success "APP_URL configurado: $APP_URL"
fi

# Configurar para produção
sed -i 's/APP_ENV=.*/APP_ENV=production/g' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/g' .env
log_success "Ambiente configurado para produção"

# 8. Testar aplicação
log_info "Testando aplicação..."

# Testar comando de limpeza
php artisan webhooks:cleanup --help > /dev/null 2>&1
if [ $? -eq 0 ]; then
    log_success "Comando de limpeza funcionando"
else
    log_warning "Comando de limpeza com problemas"
fi

# Verificar rotas
php artisan route:list | grep webhook > /dev/null 2>&1
if [ $? -eq 0 ]; then
    log_success "Rotas do webhook configuradas"
else
    log_warning "Rotas do webhook não encontradas"
fi

echo ""
log_success "=== Deploy concluído com sucesso! ==="
echo ""

# Exibir informações finais
echo -e "${BLUE}Próximos passos:${NC}"
echo "1. Configure o subdomínio no cPanel apontando para: public/"
echo "2. Configure o cron job para limpeza automática:"
echo "   0 * * * * /usr/local/bin/php $(pwd)/artisan webhooks:cleanup"
echo ""
echo -e "${BLUE}URLs importantes:${NC}"
echo "- Dashboard: $APP_URL"
echo "- Webhook endpoint: $APP_URL/webhook"
echo ""
echo -e "${BLUE}Comandos úteis:${NC}"
echo "- Limpar webhooks: php artisan webhooks:cleanup"
echo "- Verificar logs: tail -f storage/logs/laravel.log"
echo "- Testar webhook: curl -X POST $APP_URL/webhook -H 'Content-Type: application/json' -d '{\"test\": true}'"
echo ""
echo -e "${GREEN}Webhook Dashboard está pronto para uso!${NC}"