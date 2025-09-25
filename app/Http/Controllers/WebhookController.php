<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
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
        // Captura todos os dados da requisição
        $webhook = Webhook::create([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $this->getRealIpAddress($request),
            'size' => strlen($request->getContent()),
            'headers' => $request->headers->all(),
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

    // Simula uma chamada webhook
    public function simulate(Request $request)
    {
        $testData = [
            'test' => true,
            'message' => 'Esta é uma chamada webhook simulada',
            'timestamp' => now(),
            'data' => [
                'user_id' => rand(1, 100),
                'action' => 'test_webhook',
                'payload' => fake()->sentence()
            ]
        ];

        // Faz uma chamada HTTP para o próprio endpoint webhook
        $url = url('/webhook');
        
        try {
            $response = Http::post($url, $testData);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook simulado enviado com sucesso',
                'response_status' => $response->status()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao simular webhook: ' . $e->getMessage()
            ], 500);
        }
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
        
        if ($totalWebhooks > 1500) {
            $removedCount = Webhook::keepLatest(1000);
            
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
}
