<?php

namespace App\Console\Commands;

use App\Actions\License\ExpireLicenseAction;
use App\Enums\LicenseStatus;
use App\Models\License;
use Illuminate\Console\Command;

class CheckLicenseExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie les expirations de licences et applique les transitions système (active -> grace -> read_only)';

    /**
     * Execute the console command.
     */
    public function handle(ExpireLicenseAction $expireAction): int
    {
        $today = now()->startOfDay();
        $this->info("Contrôle des licences au {$today->toDateString()}...");

        $graceCount = 0;
        $readOnlyCount = 0;

        // 1. Transition active -> grace : ends_on dépassé
        $activeLicenses = License::query()
            ->withoutGlobalScopes()
            ->where('status', LicenseStatus::Active->value)
            ->where('ends_on', '<', $today->toDateString())
            ->get();

        foreach ($activeLicenses as $license) {
            $expireAction->handle($license, LicenseStatus::Grace);
            $graceCount++;
        }

        // 2. Transition grace -> read_only : ends_on + grace_days dépassé
        $graceLicenses = License::query()
            ->withoutGlobalScopes()
            ->where('status', LicenseStatus::Grace->value)
            ->get();

        foreach ($graceLicenses as $license) {
            $graceEnd = $license->ends_on->copy()->startOfDay()->addDays($license->grace_days);

            if ($today->gt($graceEnd)) {
                $expireAction->handle($license, LicenseStatus::ReadOnly);
                $readOnlyCount++;
            }
        }

        $this->info("Terminé : {$graceCount} licence(s) passée(s) en grâce, {$readOnlyCount} licence(s) passée(s) en lecture seule.");

        return Command::SUCCESS;
    }
}
