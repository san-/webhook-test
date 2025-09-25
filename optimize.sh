#!/bin/bash

# Script de Otimização do Webhook Dashboard
# Focado em reduzir problemas de conexão e travamentos

echo "=== Webhook Dashboard - Otimização ==="
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

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    log_error "Este script deve ser executado na raiz do projeto Laravel!"
    exit 1
fi

log_info "Iniciando otimização do sistema..."

# 1. Limpar caches existentes
log_info "Limpando caches existentes..."
php artisan cache:clear 2>/dev/null || log_warning "Falha ao limpar cache"
php artisan config:clear 2>/dev/null || log_warning "Falha ao limpar config cache"
php artisan route:clear 2>/dev/null || log_warning "Falha ao limpar route cache"
php artisan view:clear 2>/dev/null || log_warning "Falha ao limpar view cache"

# 2. Otimizar composer
log_info "Otimizando autoloader do Composer..."
composer dump-autoload -o --no-dev 2>/dev/null || log_warning "Falha na otimização do autoloader"

# 3. Executar migrations de forma segura
log_info "Executando migrations com timeout reduzido..."
timeout 300 php artisan migrate --force 2>/dev/null
if [ $? -eq 0 ]; then
    log_success "Migrations executadas com sucesso"
else
    log_warning "Migrations falharam ou demoraram muito - tentando alternativa..."
    
    # Tentar executar migrations uma por uma
    for migration in database/migrations/*.php; do
        filename=$(basename "$migration")
        log_info "Executando migration: $filename"
        timeout 60 php artisan migrate --path="database/migrations/$filename" --force 2>/dev/null
        if [ $? -ne 0 ]; then
            log_warning "Migration $filename falhou - continuando..."
        fi
    done
fi

# 4. Otimizar banco de dados
log_info "Otimizando banco de dados..."
php artisan db:optimize --vacuum 2>/dev/null
if [ $? -eq 0 ]; then
    log_success "Banco de dados otimizado"
else
    log_warning "Otimização do banco falhou"
fi

# 5. Criar caches otimizados apenas se não houver problemas
log_info "Criando caches otimizados..."
php artisan config:cache 2>/dev/null && log_success "Config cache criado" || log_warning "Falha no config cache"
php artisan route:cache 2>/dev/null && log_success "Route cache criado" || log_warning "Falha no route cache"
php artisan view:cache 2>/dev/null && log_success "View cache criado" || log_warning "Falha no view cache"

# 6. Configurar permissões
log_info "Configurando permissões..."
chmod -R 755 storage bootstrap/cache 2>/dev/null
chmod -R 777 storage/logs storage/framework 2>/dev/null

# 7. Verificar e configurar banco SQLite se necessário
if [ -f "database/database.sqlite" ]; then
    log_info "Configurando permissões do SQLite..."
    chmod 664 database/database.sqlite
    chmod 775 database/
fi

# 8. Otimizar arquivos de sessão e cache
log_info "Limpando arquivos antigos..."
find storage/framework/sessions/ -name "sess_*" -mtime +7 -delete 2>/dev/null || true
find storage/framework/cache/ -name "*" -mtime +7 -delete 2>/dev/null || true
find storage/logs/ -name "*.log" -mtime +30 -delete 2>/dev/null || true

# 9. Verificar status do sistema
log_info "Verificando status do sistema..."
php artisan about --only=environment 2>/dev/null || log_warning "Não foi possível verificar o ambiente"

# 10. Criar arquivo de status
echo "$(date): Otimização executada com sucesso" > storage/logs/optimization.log

log_success "Otimização concluída!"
echo ""
echo "Dicas para manter a performance:"
echo "• Execute este script regularmente (semanalmente)"
echo "• Monitore os logs em storage/logs/"
echo "• Use 'php artisan db:optimize --vacuum' periodicamente"
echo "• Considere usar SQLite para hosts com recursos limitados"
echo "• Mantenha o DEBUG=false em produção"
echo ""