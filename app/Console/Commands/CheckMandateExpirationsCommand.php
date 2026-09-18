<?php

namespace App\Console\Commands;

use App\Actions\Mandate\ExpireMandatesAction;
use Illuminate\Console\Command;

class CheckMandateExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mandates:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les expirations de mandats et applique la transition système (active -> expired)';

    /**
     * Execute the console command.
     */
    public function handle(ExpireMandatesAction $expireAction): int
    {
        $today = now()->startOfDay();
        $this->info("Contrôle des mandats échus au {$today->toDateString()}...");

        $expiredCount = $expireAction->handle($today);

        $this->info("Terminé : {$expiredCount} mandat(s) expiré(s).");

        return Command::SUCCESS;
    }
}
