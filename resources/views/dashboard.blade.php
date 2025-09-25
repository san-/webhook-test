<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .bg-dark-custom {
            background-color: #1a1d29 !important;
        }
        .bg-card {
            background-color: #2d3142;
        }
        .text-muted-custom {
            color: #8b949e !important;
        }
        .sidebar {
            background-color: #21252b;
            min-height: 100vh;
        }
        .status-online {
            color: #4ade80;
        }
        .method-badge {
            font-size: 0.75rem;
            font-weight: bold;
        }
        .webhook-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .webhook-item:hover {
            background-color: #374151;
        }
        .webhook-item.active {
            background-color: #1f2937;
            border-left: 3px solid #3b82f6;
        }
        .webhook-item.read {
            background-color: #1f2937;
            opacity: 0.7;
        }
        .webhook-item.read:hover {
            background-color: #374151;
            opacity: 0.8;
        }
        .webhook-item.unread {
            background-color: #2d3142;
        }
        .request-details {
            background-color: #1f2937;
            border-radius: 8px;
        }
        .json-content {
            background-color: #111827;
            border-radius: 6px;
            padding: 1rem;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-dark-custom text-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Header -->
            <div class="col-12 p-3 border-bottom border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Webhook Dashboard</h4>
                        <small class="text-muted-custom">Monitor suas requisições em tempo real</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-light btn-sm" onclick="generateUrls()">
                            <i class="fas fa-cog me-1"></i> Gerenciar URLs
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="clearAll()">
                            <i class="fas fa-trash me-1"></i> Limpar Tudo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistics -->
            <div class="col-12 p-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-card border-0 text-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-chart-bar fa-2x text-primary me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0" id="total-requests">{{ $statistics['total'] }}</h5>
                                        <p class="card-text text-muted-custom">Total de Requests</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-card border-0 text-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-day fa-2x text-info me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0" id="today-requests">{{ $statistics['today'] }}</h5>
                                        <p class="card-text text-muted-custom">Hoje</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-card border-0 text-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock fa-2x text-warning me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0" id="last-hour-requests">{{ $statistics['last_hour'] }}</h5>
                                        <p class="card-text text-muted-custom">Última Hora</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-card border-0 text-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-wifi fa-2x status-online me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-0 status-online">{{ $statistics['status'] }}</h5>
                                        <p class="card-text text-muted-custom">Status</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Method Filters -->
            <div class="col-12 px-3">
                <div class="mb-3">
                    <span class="text-muted-custom">Métodos:</span>
                    <button class="btn btn-outline-light btn-sm ms-2 method-filter active" data-method="all">
                        TODOS: <span id="count-all">{{ count($webhooks) }}</span>
                    </button>
                    <button class="btn btn-outline-warning btn-sm ms-2 method-filter" data-method="PUT">
                        PUT: <span id="count-put">{{ $webhooks->where('method', 'PUT')->count() }}</span>
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2 method-filter" data-method="POST">
                        POST: <span id="count-post">{{ $webhooks->where('method', 'POST')->count() }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Webhook List -->
            <div class="col-md-4">
                <div class="card bg-card border-0 text-light" style="height: 70vh;">
                    <div class="card-header">
                        <h6 class="mb-0">Webhook Requests</h6>
                        <small class="text-muted-custom" id="requests-count">{{ count($webhooks) }} requests recebidos</small>
                    </div>
                    <div class="card-body p-0" style="overflow-y: auto;">
                        <div id="webhook-list">
                            @forelse($webhooks as $webhook)
                            <div class="webhook-item p-3 border-bottom border-secondary {{ $webhook->isRead() ? 'read' : 'unread' }}" 
                                 data-id="{{ $webhook->id }}" 
                                 data-method="{{ $webhook->method }}"
                                 data-read="{{ $webhook->isRead() ? 'true' : 'false' }}"
                                 onclick="showWebhookDetails({{ $webhook->id }})">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge bg-{{ $webhook->method === 'POST' ? 'success' : 'warning' }} method-badge me-2">
                                                {{ $webhook->method }}
                                            </span>
                                            <small class="text-muted-custom">{{ $webhook->created_at->format('d/m H:i:s') }}</small>
                                            @if(!$webhook->isRead())
                                                <span class="badge bg-primary ms-2" style="font-size: 0.6rem;">NOVO</span>
                                            @endif
                                        </div>
                                        <div class="text-truncate" style="max-width: 250px;">
                                            <small>{{ $webhook->ip_address }}</small>
                                        </div>
                                        <div class="text-truncate text-muted-custom" style="max-width: 250px; font-size: 0.75rem;">
                                            {{ $webhook->url }}
                                        </div>
                                    </div>
                                    <div class="text-end ms-2">
                                        <small class="text-muted-custom">{{ $webhook->formatted_size }}</small>
                                        <br>
                                        <small class="text-success">{{ number_format($webhook->created_at->diffInMilliseconds(now())) }}ms</small>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="p-4 text-center text-muted-custom">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Nenhum webhook recebido ainda</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Details -->
            <div class="col-md-8">
                <div class="card bg-card border-0 text-light" style="height: 70vh;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Request Details & Headers</h6>
                        <button class="btn btn-sm btn-outline-primary" id="format-toggle">
                            <i class="fas fa-code me-1"></i> <span>Formatar JSON</span>
                        </button>
                    </div>
                    <div class="card-body" style="overflow-y: auto;">
                        <div id="request-details">
                            @if(count($webhooks) > 0)
                                @php $firstWebhook = $webhooks->first(); @endphp
                                
                                <!-- Informações do Host Cliente -->
                                <div class="request-details p-3 mb-3">
                                    <h6><i class="fas fa-server me-2"></i>Informações do Host Cliente</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>IP de Origem:</strong> <code>{{ $firstWebhook->ip_address }}</code></p>
                                            @if(isset($firstWebhook->headers['_client_info']['server_name']))
                                                <p><strong>Nome do Servidor:</strong> {{ $firstWebhook->headers['_client_info']['server_name'] }}</p>
                                            @endif
                                            @if(isset($firstWebhook->headers['_client_info']['remote_port']))
                                                <p><strong>Porta Remota:</strong> {{ $firstWebhook->headers['_client_info']['remote_port'] }}</p>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            @if(isset($firstWebhook->headers['_client_info']['request_scheme']))
                                                <p><strong>Protocolo:</strong> <span class="badge bg-{{ $firstWebhook->headers['_client_info']['request_scheme'] === 'https' ? 'success' : 'warning' }}">{{ strtoupper($firstWebhook->headers['_client_info']['request_scheme']) }}</span></p>
                                            @endif
                                            @if(isset($firstWebhook->headers['user-agent'][0]))
                                                <p><strong>User Agent:</strong> <small class="text-muted">{{ Str::limit($firstWebhook->headers['user-agent'][0], 50) }}</small></p>
                                            @endif
                                            @if(isset($firstWebhook->headers['host'][0]))
                                                <p><strong>Host Requisitado:</strong> {{ $firstWebhook->headers['host'][0] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Informações da Requisição -->
                                <div class="request-details p-3 mb-3">
                                    <h6><i class="fas fa-info-circle me-2"></i>Informações da Requisição</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Método:</strong> <span class="badge bg-{{ $firstWebhook->method === 'POST' ? 'success' : 'warning' }}">{{ $firstWebhook->method }}</span></p>
                                            <p><strong>ID:</strong> {{ $firstWebhook->id }}</p>
                                            <p><strong>Data:</strong> {{ $firstWebhook->created_at->format('d/m/Y, H:i:s') }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Tamanho:</strong> {{ $firstWebhook->formatted_size }}</p>
                                            <p><strong>Tempo de Resposta:</strong> <span class="text-success">{{ number_format($firstWebhook->created_at->diffInMilliseconds(now())) }}ms</span></p>
                                            @if(isset($firstWebhook->headers['content-type'][0]))
                                                <p><strong>Content-Type:</strong> <code>{{ $firstWebhook->headers['content-type'][0] }}</code></p>
                                            @endif
                                        </div>
                                    </div>
                                    <p><strong>URL:</strong> <code>{{ $firstWebhook->url }}</code></p>
                                </div>

                                <div class="request-details p-3 mb-3">
                                    <h6><i class="fas fa-list me-2"></i>Headers</h6>
                                    <div class="json-content">
                                        @foreach($firstWebhook->headers as $key => $value)
                                            @if($key !== '_client_info')
                                                <div><strong>{{ $key }}:</strong> {{ is_array($value) ? implode(', ', $value) : $value }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                <div class="request-details p-3">
                                    <h6>Request Content</h6>
                                    <div class="json-content" id="request-content">
                                        {{ $firstWebhook->body ?: 'Nenhum conteúdo' }}
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted-custom">
                                    <i class="fas fa-eye fa-3x mb-3"></i>
                                    <p>Selecione um webhook para ver os detalhes</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentWebhookId = null;
        let isFormatted = false;

        // CSRF Token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Auto-refresh webhooks every 5 seconds
        setInterval(refreshWebhooks, 5000);

        // Method filter functionality
        document.querySelectorAll('.method-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active state
                document.querySelectorAll('.method-filter').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Filter webhooks
                const method = btn.dataset.method;
                filterWebhooks(method);
            });
        });

        function filterWebhooks(method) {
            const webhookItems = document.querySelectorAll('.webhook-item');
            webhookItems.forEach(item => {
                if (method === 'all' || item.dataset.method === method) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function refreshWebhooks() {
            fetch('/api/webhooks')
                .then(response => response.json())
                .then(data => {
                    updateStatistics(data.statistics);
                    updateWebhookList(data.webhooks);
                })
                .catch(error => console.error('Erro ao atualizar webhooks:', error));
        }

        function updateStatistics(stats) {
            document.getElementById('total-requests').textContent = stats.total;
            document.getElementById('today-requests').textContent = stats.today;
            document.getElementById('last-hour-requests').textContent = stats.last_hour;
        }

        function updateWebhookList(webhooks) {
            const listContainer = document.getElementById('webhook-list');
            
            if (webhooks.length === 0) {
                listContainer.innerHTML = `
                    <div class="p-4 text-center text-muted-custom">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Nenhum webhook recebido ainda</p>
                    </div>
                `;
                return;
            }

            const webhookHTML = webhooks.map(webhook => {
                const date = new Date(webhook.created_at);
                const formattedDate = date.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                const isRead = webhook.read_at !== null;
                const readClass = isRead ? 'read' : 'unread';
                const newBadge = isRead ? '' : '<span class="badge bg-primary ms-2" style="font-size: 0.6rem;">NOVO</span>';

                return `
                    <div class="webhook-item p-3 border-bottom border-secondary ${readClass} ${currentWebhookId == webhook.id ? 'active' : ''}" 
                         data-id="${webhook.id}" 
                         data-method="${webhook.method}"
                         data-read="${isRead}"
                         onclick="showWebhookDetails(${webhook.id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge bg-${webhook.method === 'POST' ? 'success' : 'warning'} method-badge me-2">
                                        ${webhook.method}
                                    </span>
                                    <small class="text-muted-custom">${formattedDate}</small>
                                    ${newBadge}
                                </div>
                                <div class="text-truncate" style="max-width: 250px;">
                                    <small>${webhook.ip_address}</small>
                                </div>
                                <div class="text-truncate text-muted-custom" style="max-width: 250px; font-size: 0.75rem;">
                                    ${webhook.url}
                                </div>
                            </div>
                            <div class="text-end ms-2">
                                <small class="text-muted-custom">${formatBytes(webhook.size)}</small>
                                <br>
                                <small class="text-success">${Math.abs(Date.now() - new Date(webhook.created_at).getTime())}ms</small>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            listContainer.innerHTML = webhookHTML;
            
            // Update counts
            document.getElementById('count-all').textContent = webhooks.length;
            document.getElementById('count-post').textContent = webhooks.filter(w => w.method === 'POST').length;
            document.getElementById('count-put').textContent = webhooks.filter(w => w.method === 'PUT').length;
            document.getElementById('requests-count').textContent = `${webhooks.length} requests recebidos`;
        }

        function showWebhookDetails(id) {
            currentWebhookId = id;
            
            // Update active state
            document.querySelectorAll('.webhook-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const clickedItem = document.querySelector(`[data-id="${id}"]`);
            clickedItem.classList.add('active');

            // Fetch webhook details
            fetch(`/api/webhooks/${id}`)
                .then(response => response.json())
                .then(webhook => {
                    displayWebhookDetails(webhook);
                    
                    // Marcar como lido visualmente se ainda não estava
                    if (!clickedItem.classList.contains('read')) {
                        clickedItem.classList.remove('unread');
                        clickedItem.classList.add('read');
                        clickedItem.dataset.read = 'true';
                        
                        // Remover badge "NOVO"
                        const newBadge = clickedItem.querySelector('.badge.bg-primary');
                        if (newBadge) {
                            newBadge.remove();
                        }
                    }
                })
                .catch(error => console.error('Erro ao carregar detalhes:', error));
        }

        function displayWebhookDetails(webhook) {
            const date = new Date(webhook.created_at);
            const formattedDate = date.toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            // Extrair informações do cliente dos headers
            const clientInfo = webhook.headers._client_info || {};
            const userAgent = webhook.headers['user-agent'] ? (Array.isArray(webhook.headers['user-agent']) ? webhook.headers['user-agent'][0] : webhook.headers['user-agent']) : '';
            const hostHeader = webhook.headers['host'] ? (Array.isArray(webhook.headers['host']) ? webhook.headers['host'][0] : webhook.headers['host']) : '';
            const contentType = webhook.headers['content-type'] ? (Array.isArray(webhook.headers['content-type']) ? webhook.headers['content-type'][0] : webhook.headers['content-type']) : '';

            const detailsHTML = `
                <!-- Informações do Host Cliente -->
                <div class="request-details p-3 mb-3">
                    <h6><i class="fas fa-server me-2"></i>Informações do Host Cliente</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>IP de Origem:</strong> <code>${webhook.ip_address}</code></p>
                            ${clientInfo.server_name ? `<p><strong>Nome do Servidor:</strong> ${clientInfo.server_name}</p>` : ''}
                            ${clientInfo.remote_port ? `<p><strong>Porta Remota:</strong> ${clientInfo.remote_port}</p>` : ''}
                        </div>
                        <div class="col-md-6">
                            ${clientInfo.request_scheme ? `<p><strong>Protocolo:</strong> <span class="badge bg-${clientInfo.request_scheme === 'https' ? 'success' : 'warning'}">${clientInfo.request_scheme.toUpperCase()}</span></p>` : ''}
                            ${userAgent ? `<p><strong>User Agent:</strong> <small class="text-muted">${userAgent.length > 50 ? userAgent.substring(0, 50) + '...' : userAgent}</small></p>` : ''}
                            ${hostHeader ? `<p><strong>Host Requisitado:</strong> ${hostHeader}</p>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Informações da Requisição -->
                <div class="request-details p-3 mb-3">
                    <h6><i class="fas fa-info-circle me-2"></i>Informações da Requisição</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Método:</strong> <span class="badge bg-${webhook.method === 'POST' ? 'success' : 'warning'}">${webhook.method}</span></p>
                            <p><strong>ID:</strong> ${webhook.id}</p>
                            <p><strong>Data:</strong> ${formattedDate}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Tamanho:</strong> ${formatBytes(webhook.size)}</p>
                            <p><strong>Tempo de Resposta:</strong> <span class="text-success">${Math.abs(Date.now() - new Date(webhook.created_at).getTime())}ms</span></p>
                            ${contentType ? `<p><strong>Content-Type:</strong> <code>${contentType}</code></p>` : ''}
                        </div>
                    </div>
                    <p><strong>URL:</strong> <code>${webhook.url}</code></p>
                    ${webhook.read_at ? `<p><strong>Lido em:</strong> <small class="text-info">${new Date(webhook.read_at).toLocaleDateString('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    })}</small></p>` : ''}
                </div>

                <div class="request-details p-3 mb-3">
                    <h6><i class="fas fa-list me-2"></i>Headers</h6>
                    <div class="json-content">
                        ${Object.entries(webhook.headers).map(([key, value]) => {
                            // Pular informações do cliente já exibidas na seção específica
                            if (key === '_client_info') return '';
                            const displayValue = Array.isArray(value) ? value.join(', ') : value;
                            return `<div><strong>${key}:</strong> ${displayValue}</div>`;
                        }).filter(item => item !== '').join('')}
                    </div>
                </div>

                <div class="request-details p-3">
                    <h6><i class="fas fa-code me-2"></i>Request Content</h6>
                    <div class="json-content" id="request-content">
                        ${webhook.body || 'Nenhum conteúdo'}
                    </div>
                </div>
            `;

            document.getElementById('request-details').innerHTML = detailsHTML;
            isFormatted = false;
            document.querySelector('#format-toggle span').textContent = 'Formatar JSON';
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 B';
            
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        // Format toggle functionality
        document.getElementById('format-toggle').addEventListener('click', () => {
            const contentDiv = document.getElementById('request-content');
            const toggleSpan = document.querySelector('#format-toggle span');
            
            if (!isFormatted) {
                try {
                    const json = JSON.parse(contentDiv.textContent);
                    contentDiv.innerHTML = '<pre>' + JSON.stringify(json, null, 2) + '</pre>';
                    toggleSpan.textContent = 'Texto Simples';
                    isFormatted = true;
                } catch (e) {
                    // Not valid JSON, just prettify
                    contentDiv.innerHTML = '<pre>' + contentDiv.textContent + '</pre>';
                    toggleSpan.textContent = 'Texto Simples';
                    isFormatted = true;
                }
            } else {
                // Get original content from the webhook
                if (currentWebhookId) {
                    fetch(`/api/webhooks/${currentWebhookId}`)
                        .then(response => response.json())
                        .then(webhook => {
                            contentDiv.textContent = webhook.body || 'Nenhum conteúdo';
                            toggleSpan.textContent = 'Formatar JSON';
                            isFormatted = false;
                        });
                }
            }
        });

        }

        function clearAll() {
            if (confirm('Tem certeza que deseja limpar todos os webhooks? Esta ação não pode ser desfeita.')) {
                fetch('/api/webhooks/clear', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        refreshWebhooks();
                        document.getElementById('request-details').innerHTML = `
                            <div class="text-center text-muted-custom">
                                <i class="fas fa-eye fa-3x mb-3"></i>
                                <p>Selecione um webhook para ver os detalhes</p>
                            </div>
                        `;
                    } else {
                        alert('Erro ao limpar webhooks');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao limpar webhooks');
                });
            }
        }

        function generateUrls() {
            const urls = [
                {
                    name: 'Endpoint Principal',
                    url: window.location.origin + '/webhook',
                    description: 'Aceita POST e PUT'
                }
            ];

            let urlsHtml = '<h5>URLs de Webhook Disponíveis</h5>';
            urls.forEach(url => {
                urlsHtml += `
                    <div class="mb-3 p-3 bg-dark rounded">
                        <h6>${url.name}</h6>
                        <code>${url.url}</code>
                        <br><small class="text-muted">${url.description}</small>
                    </div>
                `;
            });

            // Simple modal implementation
            const modal = document.createElement('div');
            modal.className = 'modal fade show';
            modal.style.display = 'block';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header">
                            <h5 class="modal-title">Gerenciar URLs</h5>
                            <button type="button" class="btn-close btn-close-white" onclick="this.closest('.modal').remove()"></button>
                        </div>
                        <div class="modal-body">
                            ${urlsHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Fechar</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            @if(count($webhooks) > 0)
                showWebhookDetails({{ $webhooks->first()->id }});
            @endif
        });
    </script>
</body>
</html>