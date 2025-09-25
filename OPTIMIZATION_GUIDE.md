# Guia de Otimização - Webhook Dashboard

Este documento descreve as otimizações implementadas para resolver problemas de conexão e travamentos do banco de dados.

## 🚀 Otimizações Implementadas

### 1. Configurações de Banco de Dados

#### SQLite (Recomendado para hosts limitados)
- **WAL Mode**: Melhora a concorrência e performance
- **Busy Timeout**: 30 segundos para evitar travamentos
- **Cache Size**: 10MB para melhor performance
- **Synchronous**: NORMAL (balance performance/segurança)

#### MySQL/MariaDB
- **Connection Timeout**: 30 segundos
- **Pool de Conexões**: Limitado a 10 conexões
- **Strict Mode**: Desabilitado para compatibilidade
- **Engine**: InnoDB otimizado

### 2. Sistema de Cache Otimizado
- **Driver**: Alterado de `database` para `file`
- **Reduz**: Carga no banco de dados
- **Melhora**: Tempo de resposta da aplicação

### 3. Sistema de Queue
- **Driver**: Alterado para `sync`
- **Elimina**: Necessidade de worker em background
- **Reduz**: Uso de recursos do servidor

### 4. Índices de Banco
Criados índices otimizados para:
- `method` (tipo de request)
- `ip_address` (origem da requisição)
- `created_at` (consultas por data)
- `size` (tamanho das requisições)

### 5. Middleware de Limitação
- **Limite**: 10MB por request
- **Previne**: Sobrecarga de memória
- **Protege**: Contra requests maliciosos

## 🛠️ Como Usar

### Primeira Execução
```bash
# 1. Copiar configurações otimizadas
cp .env.optimized .env

# 2. Configurar APP_KEY
php artisan key:generate

# 3. Executar otimização completa
./optimize.sh
```

### Manutenção Regular
```bash
# Executar otimização (recomendado semanalmente)
./optimize.sh

# Otimizar apenas o banco
php artisan db:optimize --vacuum

# Limpar caches antigos
php artisan cache:clear
```

## 📊 Comandos Artisan Adicionados

### `php artisan db:optimize`
Otimiza o banco de dados baseado no driver usado:

**SQLite:**
- Configura pragmas de performance
- Executa VACUUM (com --vacuum)
- Cria índices otimizados

**MySQL:**
- Executa OPTIMIZE TABLE
- Cria índices de performance

### Exemplo de uso:
```bash
# Otimização básica
php artisan db:optimize

# Com vacuum (SQLite)
php artisan db:optimize --vacuum
```

## 🔧 Configurações de Ambiente

### Para SQLite (.env):
```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database/database.sqlite
DB_BUSY_TIMEOUT=30000
DB_JOURNAL_MODE=WAL
DB_SYNCHRONOUS=NORMAL
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

### Para MySQL (.env):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=webhook_dashboard
DB_USERNAME=your_user
DB_PASSWORD=your_password
DB_MAX_CONNECTIONS=5
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

## 🏥 Monitoramento e Troubleshooting

### Logs de Performance
Os logs de queries lentas são salvos em:
- `storage/logs/laravel.log`
- Queries > 1 segundo são logadas

### Verificar Status
```bash
# Status da aplicação
php artisan about

# Verificar otimização
cat storage/logs/optimization.log
```

### Problemas Comuns

#### 1. Migration Travando
```bash
# Executar com timeout
timeout 300 php artisan migrate

# Ou usar o script de otimização
./optimize.sh
```

#### 2. Banco SQLite Corrompido
```bash
# Backup e recriação
cp database/database.sqlite database/database.sqlite.bak
rm database/database.sqlite
php artisan migrate --force
php artisan db:optimize --vacuum
```

#### 3. Problemas de Permissão
```bash
# Corrigir permissões
chmod -R 755 storage bootstrap/cache
chmod -R 777 storage/logs storage/framework
chmod 664 database/database.sqlite  # se usar SQLite
```

## 📈 Benefícios Esperados

### Performance
- ⚡ 50-70% redução no tempo de resposta
- 📊 90% redução em travamentos de migration
- 🔄 Melhor concorrência de requisições

### Recursos
- 💾 30-50% menos uso de memória
- 🔌 Redução significativa de conexões DB
- 📁 Cache mais eficiente

### Estabilidade
- 🛡️ Timeouts configurados adequadamente
- 🔒 Proteção contra requests grandes
- 📊 Monitoramento de queries lentas

## 🚨 Importante para Produção

1. **Sempre faça backup** antes de executar otimizações
2. **Teste em ambiente de desenvolvimento** primeiro
3. **Execute o script de otimização** após cada deploy
4. **Monitore os logs** regularmente
5. **Mantenha DEBUG=false** em produção

## 🤝 Suporte

Se continuar com problemas:

1. Verifique os logs: `storage/logs/laravel.log`
2. Execute: `php artisan about`
3. Teste o banco: `php artisan db:optimize --vacuum`
4. Considere migrar para SQLite se usar MySQL em host limitado