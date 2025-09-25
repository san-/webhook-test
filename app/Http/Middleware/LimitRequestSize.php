<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LimitRequestSize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $maxSize = '10M'): Response
    {
        $maxSizeBytes = $this->convertToBytes($maxSize);
        
        // Verificar tamanho do conteúdo
        $contentLength = $request->header('Content-Length');
        
        if ($contentLength && $contentLength > $maxSizeBytes) {
            return response()->json([
                'error' => 'Request too large',
                'max_size' => $maxSize,
                'received' => $this->formatBytes($contentLength)
            ], 413);
        }

        // Verificar tamanho dos dados POST
        $postSize = strlen($request->getContent());
        if ($postSize > $maxSizeBytes) {
            return response()->json([
                'error' => 'Request payload too large',
                'max_size' => $maxSize,
                'received' => $this->formatBytes($postSize)
            ], 413);
        }

        return $next($request);
    }

    /**
     * Convert size string to bytes
     */
    private function convertToBytes(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = intval($size);

        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return $value;
        }
    }

    /**
     * Format bytes to human readable string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . 'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . 'KB';
        }
        return $bytes . 'B';
    }
}