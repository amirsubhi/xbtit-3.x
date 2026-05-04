<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneUsers extends Command
{
    protected $signature   = 'tracker:prune-users
                              {--unconfirmed-days=7  : Days before pruning unconfirmed accounts}
                              {--inactive-days=365   : Days before pruning inactive confirmed accounts}
                              {--dry-run             : Show counts without deleting}';

    protected $description = 'Prune stale unconfirmed and inactive user accounts (C-36)';

    public function handle(): int
    {
        $unconfirmedDays = (int) $this->option('unconfirmed-days');
        $inactiveDays    = (int) $this->option('inactive-days');
        $dryRun          = $this->option('dry-run');

        // Levels where can_be_deleted = 'no' (guest id=1, owner id=8 by default).
        // Never prune these regardless of age.
        $protectedLevels = DB::table('users_level')
            ->where('can_be_deleted', 'no')
            ->pluck('id_level')
            ->toArray();

        // Unconfirmed accounts: email_verified_at IS NULL, older than N days.
        $unconfirmedCutoff = now()->subDays($unconfirmedDays);
        $unconfirmedQuery  = DB::table('users')
            ->whereNull('email_verified_at')
            ->where('created_at', '<', $unconfirmedCutoff)
            ->whereNotIn('id_level', $protectedLevels);

        $unconfirmedCount = $unconfirmedQuery->count();

        // Inactive confirmed accounts: last seen older than N days.
        $inactiveCutoff = now()->subDays($inactiveDays);
        $inactiveQuery  = DB::table('users')
            ->whereNotNull('email_verified_at')
            ->where(function ($q) use ($inactiveCutoff) {
                $q->whereNull('updated_at')
                  ->orWhere('updated_at', '<', $inactiveCutoff);
            })
            ->whereNotIn('id_level', $protectedLevels);

        $inactiveCount = $inactiveQuery->count();

        $this->info("Unconfirmed accounts to prune: {$unconfirmedCount}");
        $this->info("Inactive accounts to prune:    {$inactiveCount}");

        if ($dryRun) {
            $this->info('Dry run — no accounts deleted.');
            return self::SUCCESS;
        }

        if ($this->confirm('Delete these accounts? This cannot be undone.', false)) {
            $unconfirmedQuery->delete();
            $inactiveQuery->delete();
            $this->info('Accounts pruned.');
        } else {
            $this->info('Aborted.');
        }

        return self::SUCCESS;
    }
}
