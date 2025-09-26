<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    // Exibe o dashboard principal
    public function dashboard()
    {
        $webhooks = Webhook::recent()->orderBy('created_at', 'desc')->get();
        
        $statistics = [
            'total' => Webhook::count(),
            'today' => Webhook::today()->count(),
            'last_hour' => Webhook::lastHour()->count(),
            'status' => 'Online'
        ];

        return view('dashboard', compact('webhooks', 'statistics'));
    }

    // Recebe chamadas webhook (POST e PUT)
    public function receive(Request $request)
    {
        // Captura todos os dados da requisição incluindo informações detalhadas do cliente
        $webhook = Webhook::create([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $this->getRealIpAddress($request),
            'size' => strlen($request->getContent()),
            'headers' => $this->getEnhancedHeaders($request),
            'body' => $request->getContent()
        ]);

        // Executa limpeza automática se necessário
        $this->cleanupOldWebhooks();

        return response()->json([
            'success' => true,
            'message' => 'Webhook recebido com sucesso',
            'id' => $webhook->id,
            'timestamp' => $webhook->created_at
        ], 200);
    }

    // API para buscar webhooks (para atualização em tempo real)
    public function getWebhooks(Request $request)
    {
        $webhooks = Webhook::recent()
            ->orderBy('created_at', 'desc')
            ->get();

        $statistics = [
            'total' => Webhook::count(),
            'today' => Webhook::today()->count(),
            'last_hour' => Webhook::lastHour()->count(),
            'status' => 'Online'
        ];

        return response()->json([
            'webhooks' => $webhooks,
            'statistics' => $statistics
        ]);
    }

    // Limpa todos os webhooks
    public function clear()
    {
        $count = Webhook::count();
        Webhook::truncate();
        
        return response()->json([
            'success' => true,
            'message' => "{$count} webhooks foram removidos com sucesso"
        ]);
    }

    // Detalhes de um webhook específico
    public function show($id)
    {
        $webhook = Webhook::findOrFail($id);
        
        // Marcar como lido quando visualizado
        if (!$webhook->isRead()) {
            $webhook->markAsRead();
        }
        
        return response()->json($webhook);
    }

    /**
     * Limpa webhooks antigos quando o limite é atingido
     * Mantém apenas os 1000 webhooks mais recentes quando ultrapassa 1500
     */
    private function cleanupOldWebhooks()
    {
        $totalWebhooks = Webhook::count();
        
        if ($totalWebhooks > 500) {
            $removedCount = Webhook::keepLatest(300);
            
            // Log da ação para debug (opcional)
            Log::info("Limpeza automática executada: {$removedCount} webhooks antigos foram removidos. Total atual: " . ($totalWebhooks - $removedCount));
        }
    }

    /**
     * Obter o IP real da requisição considerando proxies e load balancers
     */
    private function getRealIpAddress($request)
    {
        // Lista de headers que podem conter o IP real
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Standard forwarded for
            'HTTP_X_FORWARDED',          // Forwarded
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_CLIENT_IP',            // Client IP
            'HTTP_FORWARDED_FOR',        // Forwarded for
            'HTTP_FORWARDED',            // Forwarded
            'REMOTE_ADDR'                // Default
        ];

        foreach ($ipHeaders as $header) {
            if ($request->server($header)) {
                $ips = explode(',', $request->server($header));
                $ip = trim($ips[0]);
                
                // Validar se é um IP válido e não local
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Se não encontrou IP válido, usar o padrão
        return $request->ip();
    }

    /**
     * Captura headers padrão + informações detalhadas do cliente
     */
    private function getEnhancedHeaders($request)
    {
        $headers = $request->headers->all();
        
        // Adicionar informações específicas do cliente se disponíveis
        $clientInfo = [
            'client_ip' => $this->getRealIpAddress($request),
            'server_name' => $request->server('SERVER_NAME'),
            'server_port' => $request->server('SERVER_PORT'),
            'request_time' => $request->server('REQUEST_TIME'),
            'request_method' => $request->server('REQUEST_METHOD'),
            'query_string' => $request->server('QUERY_STRING'),
            'document_root' => $request->server('DOCUMENT_ROOT'),
            'request_uri' => $request->server('REQUEST_URI'),
            'script_name' => $request->server('SCRIPT_NAME'),
            'remote_port' => $request->server('REMOTE_PORT'),
            'request_scheme' => $request->server('REQUEST_SCHEME') ?: ($request->isSecure() ? 'https' : 'http'),
        ];

        // Adicionar informações do cliente nas seções apropriadas
        $headers['_client_info'] = array_filter($clientInfo, function($value) {
            return !is_null($value) && $value !== '';
        });

        return $headers;
    }
}
