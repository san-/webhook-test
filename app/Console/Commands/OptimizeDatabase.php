<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize {--vacuum : Vacuum SQLite database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $driver = config('database.default');
        $this->info("Optimizing database ({$driver})...");

        switch ($driver) {
            case 'sqlite':
                $this->optimizeSQLite();
                break;
            case 'mysql':
            case 'mariadb':
                $this->optimizeMySQL();
                break;
            default:
                $this->error("Optimization not implemented for {$driver}");
                return 1;
        }

        $this->info('Database optimization completed!');
        return 0;
    }

    /**
     * Optimize SQLite database
     */
    private function optimizeSQLite()
    {
        $this->info('Setting SQLite pragmas for better performance...');
        
        // Configurações de performance para SQLite
        DB::statement('PRAGMA journal_mode=WAL');
        DB::statement('PRAGMA synchronous=NORMAL');
        DB::statement('PRAGMA cache_size=10000');
        DB::statement('PRAGMA temp_store=MEMORY');
        DB::statement('PRAGMA mmap_size=268435456'); // 256MB

        if ($this->option('vacuum')) {
            $this->info('Running VACUUM...');
            DB::statement('VACUUM');
        }

        $this->info('Creating indexes for better performance...');
        $this->createIndexes();
    }

    /**
     * Optimize MySQL database
     */
    private function optimizeMySQL()
    {
        $this->info('Optimizing MySQL tables...');
        
        try {
            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                DB::statement("OPTIMIZE TABLE `{$tableName}`");
                $this->line("Optimized table: {$tableName}");
            }
        } catch (\Exception $e) {
            $this->warn("Could not optimize tables: " . $e->getMessage());
        }

        $this->createIndexes();
    }

    /**
     * Create performance indexes
     */
    private function createIndexes()
    {
        try {
            // Index para webhooks por data
            DB::statement('CREATE INDEX IF NOT EXISTS idx_webhooks_created_at ON webhooks(created_at)');
            
            // Index para webhooks por método
            DB::statement('CREATE INDEX IF NOT EXISTS idx_webhooks_method ON webhooks(method)');
            
            // Index para webhooks por IP
            DB::statement('CREATE INDEX IF NOT EXISTS idx_webhooks_ip ON webhooks(ip_address)');
            
            // Index para webhooks lidos
            if (DB::getSchemaBuilder()->hasColumn('webhooks', 'read_at')) {
                DB::statement('CREATE INDEX IF NOT EXISTS idx_webhooks_read_at ON webhooks(read_at)');
            }
            
            $this->info('Performance indexes created successfully.');
            
        } catch (\Exception $e) {
            $this->warn("Could not create some indexes: " . $e->getMessage());
        }
    }
}