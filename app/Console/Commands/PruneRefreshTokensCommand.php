<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;

class PruneRefreshTokensCommand extends Command
{
    protected $signature = 'refresh-tokens:prune {--days=7 : Retention days for already-revoked tokens}';

    protected $description = 'Delete expired refresh tokens and long-revoked tokens';

    public function handle(): int
    {
        $retentionDays = max(0, (int) $this->option('days'));

        $expired = RefreshToken::query()->where('expires_at', '<=', now())->delete();

        $revoked = RefreshToken::query()
            ->whereNotNull('revoked_at')
            ->where('revoked_at', '<=', now()->subDays($retentionDays))
            ->delete();

        $this->info("Pruned {$expired} expired and {$revoked} revoked refresh tokens.");

        return self::SUCCESS;
    }
}
