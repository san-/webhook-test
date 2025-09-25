# Configuração para Hospedagem Compartilhada (cPanel)
# Webhook Dashboard Laravel

## 📋 Checklist de Configuração

### 1. Upload dos Arquivos
- [ ] Fazer upload de todos os arquivos do projeto
- [ ] Colocar na pasta `public_html/webhook-dashboard/`
- [ ] Certificar-se que a pasta `public/` está acessível via web

### 2. Configuração de Subdomínio
- [ ] No cPanel > Subdomains
- [ ] Criar subdomínio: `webhook.save.eti.br`
- [ ] Document Root: `public_html/webhook-dashboard/public`

### 3. Configuração de PHP (cPanel)
- [ ] PHP Version: 8.1 ou superior
- [ ] PHP Extensions necessárias:
  - [ ] sqlite3
  - [ ] pdo_sqlite
  - [ ] json
  - [ ] mbstring
  - [ ] openssl
  - [ ] tokenizer
  - [ ] xml
  - [ ] ctype
  - [ ] fileinfo

### 4. Instalação via Terminal SSH

```bash
# Navegar para o diretório
cd public_html/webhook-dashboard

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Executar script de deploy
chmod +x deploy.sh
./deploy.sh
```

### 5. Configuração Manual (se não tiver SSH)

#### 5.1 Configurar .env
```bash
# Copiar .env.example para .env
# Editar .env com os valores:

APP_NAME="Webhook Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://webhook.save.eti.br

DB_CONNECTION=sqlite

LOG_CHANNEL=single
LOG_LEVEL=error
```

#### 5.2 Gerar Chave da Aplicação
```bash
# Via File Manager, executar:
php artisan key:generate
```

#### 5.3 Configurar Banco de Dados
```bash
# Criar arquivo database/database.sqlite
touch database/database.sqlite

# Executar migrações
php artisan migrate --force
```

#### 5.4 Configurar Permissões
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod 644 database/database.sqlite
```

### 6. Configuração de Cron Job

#### Via cPanel > Cron Jobs:
```bash
# A cada hora (limpar webhooks antigos)
0 * * * * /usr/local/bin/php /home/SEU_USUARIO/public_html/webhook-dashboard/artisan webhooks:cleanup

# Ou a cada minuto (scheduler completo)
* * * * * /usr/local/bin/php /home/SEU_USUARIO/public_html/webhook-dashboard/artisan schedule:run
```

**Substitua `SEU_USUARIO` pelo seu nome de usuário no cPanel**

### 7. Configuração de .htaccess (Root)

Se não conseguir configurar subdomínio, criar `.htaccess` na raiz `public_html/`:

```apache
RewriteEngine On

# Redirecionar webhook.save.eti.br para a pasta do projeto
RewriteCond %{HTTP_HOST} ^webhook\.save\.eti\.br$
RewriteCond %{REQUEST_URI} !^/webhook-dashboard/public/
RewriteRule ^(.*)$ /webhook-dashboard/public/$1 [L]
```

### 8. Testes

#### 8.1 Testar o Site
- [ ] Acessar: https://webhook.save.eti.br
- [ ] Verificar se o dashboard carrega corretamente

#### 8.2 Testar Webhook
```bash
curl -X POST https://webhook.save.eti.br/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": true, "message": "Teste de webhook"}'
```

#### 8.3 Verificar Logs
- [ ] Verificar `storage/logs/laravel.log`
- [ ] Verificar se os webhooks aparecem no dashboard

### 9. Monitoramento

#### 9.1 URLs Importantes
- Dashboard: https://webhook.save.eti.br
- Webhook Endpoint: https://webhook.save.eti.br/webhook
- API: https://webhook.save.eti.br/api/webhooks

#### 9.2 Comandos de Manutenção
```bash
# Limpar cache
php artisan cache:clear

# Limpar webhooks antigos
php artisan webhooks:cleanup

# Ver logs
tail -f storage/logs/laravel.log

# Verificar status
php artisan route:list
```

## 🚨 Troubleshooting

### Problema: Erro 500
**Solução:**
1. Verificar logs: `storage/logs/laravel.log`
2. Verificar permissões: `chmod -R 755 storage bootstrap/cache`
3. Verificar .env: chave APP_KEY deve estar configurada

### Problema: Banco não encontrado
**Solução:**
```bash
touch database/database.sqlite
chmod 644 database/database.sqlite
php artisan migrate
```

### Problema: CSS/JS não carregam
**Solução:**
1. Verificar APP_URL no .env
2. Verificar configuração do subdomínio
3. Limpar cache: `php artisan cache:clear`

### Problema: Webhooks não recebidos
**Solução:**
1. Testar endpoint diretamente com curl
2. Verificar logs de erro do servidor
3. Verificar configuração de CSRF no bootstrap/app.php

### Problema: Cron não funciona
**Solução:**
1. Verificar caminho do PHP: `which php`
2. Verificar caminho completo do projeto
3. Testar comando manualmente

## 📞 Suporte

Para problemas específicos:
1. Verificar `storage/logs/laravel.log`
2. Testar comandos manualmente via SSH
3. Verificar configurações do servidor web
4. Confirmar versão do PHP e extensões

## ✅ Configuração Concluída

Após completar todos os itens acima:
- [ ] Dashboard acessível em https://webhook.save.eti.br
- [ ] Webhook endpoint funcionando
- [ ] Cron job configurado
- [ ] Logs funcionando
- [ ] Testes realizados com sucesso

**O Webhook Dashboard está pronto para receber requisições!**