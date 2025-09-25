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
    protected $signature = 'webhooks:cleanup {--hours=24 : Número de horas para manter os webhooks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove webhooks antigos do banco de dados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = $this->option('hours');
        $cutoff = Carbon::now()->subHours($hours);
        
        $count = Webhook::where('created_at', '<', $cutoff)->count();
        
        if ($count > 0) {
            Webhook::where('created_at', '<', $cutoff)->delete();
            $this->info("Removidos {$count} webhooks com mais de {$hours} horas.");
        } else {
            $this->info("Nenhum webhook antigo encontrado para remover.");
        }
        
        return 0;
    }
}
