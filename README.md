# Webhook Dashboard Laravel

Um dashboard completo para monitoramento de webhooks em tempo real, desenvolvido com Laravel e Bootstrap.

## Características

- **Dashboard em tempo real**: Monitora webhooks POST e PUT automaticamente
- **Interface moderna**: Design escuro com Bootstrap 5 e Font Awesome
- **Armazenamento SQLite**: Banco de dados local para facilitar a hospedagem
- **Filtragem por método**: Visualize webhooks por tipo (POST, PUT, todos)
- **Detalhes completos**: Headers, corpo da requisição, IP, tamanho, timestamp
- **Limpeza automática**: Remove webhooks antigos automaticamente (24h)
- **Simulação de webhooks**: Teste o sistema com webhooks simulados
- **Estatísticas**: Total, hoje, última hora, status do sistema

## Requisitos

- PHP 8.1+
- Composer
- Extensões PHP: sqlite3, json, mbstring, openssl, pdo, tokenizer, xml, ctype, fileinfo
- Laravel 11.x

## Instalação para Produção (Hospedagem Compartilhada)

### 1. Configurar no cPanel

1. **Upload do projeto**:
   ```bash
   # Comprimir o projeto (excluir node_modules, vendor, storage/logs/*)
   zip -r webhook-dashboard.zip . -x "node_modules/*" "vendor/*" "storage/logs/*" ".git/*"
   
   # Fazer upload via cPanel File Manager para public_html
   ```

2. **Instalação de dependências**:
   ```bash
   # Via SSH ou Terminal no cPanel
   cd public_html/webhook-dashboard
   composer install --no-dev --optimize-autoloader
   ```

3. **Configurar permissões**:
   ```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

4. **Configurar .env**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Ajustar .env para produção**:
   ```env
   APP_NAME="Webhook Dashboard"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://webhook.save.eti.br

   DB_CONNECTION=sqlite
   # DB_DATABASE será criado automaticamente

   LOG_CHANNEL=single
   LOG_LEVEL=error
   ```

6. **Executar migrações**:
   ```bash
   php artisan migrate --force
   ```

### 2. Configurar Subdomínio (webhook.save.eti.br)

1. **No cPanel**:
   - Vá em "Subdomains"
   - Adicione: `webhook` com document root: `public_html/webhook-dashboard/public`

2. **Ou configurar .htaccess no public_html**:
   ```apache
   # Criar arquivo .htaccess em public_html/
   RewriteEngine On
   RewriteCond %{HTTP_HOST} ^webhook\.save\.eti\.br$
   RewriteCond %{REQUEST_URI} !^/webhook-dashboard/public/
   RewriteRule ^(.*)$ /webhook-dashboard/public/$1 [L]
   ```

### 3. Configurar Cron Job

1. **No cPanel > Cron Jobs**:
   ```bash
   # A cada hora, limpar webhooks antigos
   0 * * * * /usr/local/bin/php /home/usuario/public_html/webhook-dashboard/artisan webhooks:cleanup
   ```

2. **Ou Schedule Runner** (a cada minuto):
   ```bash
   * * * * * /usr/local/bin/php /home/usuario/public_html/webhook-dashboard/artisan schedule:run
   ```

## Uso

### 1. Endpoint do Webhook

O sistema aceita requisições POST e PUT no endpoint:
```
https://webhook.save.eti.br/webhook
```

### 2. Exemplos de Uso

**Webhook POST:**
```bash
curl -X POST https://webhook.save.eti.br/webhook \\
  -H "Content-Type: application/json" \\
  -d '{
    "event": "payment_received",
    "amount": 99.99,
    "currency": "USD",
    "customer_id": "cust_123"
  }'
```

**Webhook PUT:**
```bash
curl -X PUT https://webhook.save.eti.br/webhook \\
  -H "Content-Type: application/json" \\
  -d '{
    "event": "user_updated",
    "user_id": 456,
    "changes": ["email", "name"]
  }'
```

**Resposta do servidor:**
```json
{
  "success": true,
  "message": "Webhook recebido com sucesso",
  "id": 123,
  "timestamp": "2025-09-25T17:50:00.000000Z"
}
```

### 3. Interface do Dashboard

- **Estatísticas**: Visualize total de requests, hoje, última hora, status
- **Lista de webhooks**: Filtre por método (POST, PUT, todos)
- **Detalhes**: Clique em qualquer webhook para ver detalhes completos
- **Ações**: Simular webhook, limpar todos, gerenciar URLs

### 4. Comandos Artisan

```bash
# Limpar webhooks antigos (padrão: 24h)
php artisan webhooks:cleanup

# Limpar webhooks com mais de 12 horas
php artisan webhooks:cleanup --hours=12

# Ver ajuda do comando
php artisan webhooks:cleanup --help
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
