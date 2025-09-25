<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Webhook extends Model
{
    protected $fillable = [
        'method',
        'url', 
        'ip_address',
        'size',
        'headers',
        'body',
        'read_at'
    ];

    protected $casts = [
        'headers' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime'
    ];

    // Scope para buscar webhooks das últimas 24 horas
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDay());
    }

    // Scope para buscar webhooks de hoje
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    // Scope para buscar webhooks da última hora
    public function scopeLastHour($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHour());
    }

    // Marcar webhook como lido
    public function markAsRead()
    {
        $this->update(['read_at' => Carbon::now()]);
    }

    // Verificar se o webhook foi lido
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    // Accessor para formatar o tamanho
    protected function formattedSize(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => $this->formatBytes($attributes['size'] ?? 0)
        );
    }

    // Helper para formatar bytes
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Remove os webhooks mais antigos, mantendo apenas os N mais recentes
     */
    public static function keepLatest($keepCount = 1000)
    {
        $total = static::count();
        
        if ($total > $keepCount) {
            $removeCount = $total - $keepCount;
            
            $oldestIds = static::orderBy('created_at', 'asc')
                ->limit($removeCount)
                ->pluck('id');
            
            static::whereIn('id', $oldestIds)->delete();
            
            return $removeCount;
        }
        
        return 0;
    }
}
