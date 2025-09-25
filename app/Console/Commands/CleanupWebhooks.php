<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Webhook;
use Carbon\Carbon;

class CleanupWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:cleanup {--keep=1000 : Número de webhooks mais recentes para manter} {--hours= : Remover webhooks mais antigos que X horas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove webhooks antigos do banco de dados mantendo apenas os mais recentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $keep = $this->option('keep');
        
        if ($hours) {
            // Limpeza baseada em tempo
            $cutoff = Carbon::now()->subHours($hours);
            $count = Webhook::where('created_at', '<', $cutoff)->count();
            
            if ($count > 0) {
                Webhook::where('created_at', '<', $cutoff)->delete();
                $this->info("Removidos {$count} webhooks com mais de {$hours} horas.");
            } else {
                $this->info("Nenhum webhook antigo encontrado para remover.");
            }
        } else {
            // Limpeza baseada em quantidade (padrão)
            $totalBefore = Webhook::count();
            $removedCount = Webhook::keepLatest($keep);
            
            if ($removedCount > 0) {
                $this->info("Removidos {$removedCount} webhooks antigos. Mantidos os {$keep} mais recentes.");
                $this->info("Total antes: {$totalBefore} | Total após: " . Webhook::count());
            } else {
                $this->info("Nenhuma limpeza necessária. Total de webhooks: {$totalBefore}");
            }
        }
        
        return 0;
    }
}
