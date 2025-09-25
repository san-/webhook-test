<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;

class DatabaseOptimizationProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar SQLite para performance apenas se estivermos usando SQLite
        if (config('database.default') === 'sqlite') {
            $this->configureSQLitePerformance();
        }

        // Log de queries lentas apenas em desenvolvimento
        if (config('app.env') === 'local' && config('app.debug')) {
            $this->logSlowQueries();
        }

        // Configurar timeout de conexão
        $this->configureConnectionTimeout();
    }

    /**
     * Configure SQLite for better performance
     */
    private function configureSQLitePerformance(): void
    {
        try {
            DB::statement('PRAGMA journal_mode=WAL');
            DB::statement('PRAGMA synchronous=NORMAL');
            DB::statement('PRAGMA cache_size=10000');
            DB::statement('PRAGMA temp_store=MEMORY');
        } catch (\Exception $e) {
            // Ignore errors if pragmas are not supported
        }
    }

    /**
     * Log slow queries for debugging
     */
    private function logSlowQueries(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if ($query->time > 1000) { // Queries que demoram mais de 1 segundo
                \Log::warning('Slow query detected', [
                    'query' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms'
                ]);
            }
        });
    }

    /**
     * Configure connection timeout
     */
    private function configureConnectionTimeout(): void
    {
        // Configurar timeout menor para evitar travamentos
        if (config('database.default') === 'mysql') {
            try {
                DB::statement('SET SESSION wait_timeout=600');
                DB::statement('SET SESSION interactive_timeout=600');
            } catch (\Exception $e) {
                // Ignore if not supported
            }
        }
    }
}