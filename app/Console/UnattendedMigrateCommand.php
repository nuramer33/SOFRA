<?php

namespace App\Console;

use Closure;
use Illuminate\Database\Console\Migrations\MigrateCommand;

/**
 * `php artisan migrate` that does not stop to ask "Are you sure?" in
 * production when nobody is there to answer.
 *
 * Hosts such as Wasmer run a plain `php artisan migrate` after each deploy,
 * without `--force`. Laravel's prompt then defaults to "no" and the tables are
 * never created. The prompt is skipped when no terminal is attached, or when
 * MIGRATE_WITHOUT_PROMPT=true (Wasmer's sandbox reports a terminal even though
 * nobody can answer). Otherwise the usual production confirmation appears.
 *
 * Kept outside app/Console/Commands so it is not auto-registered a second
 * time; AppServiceProvider swaps it in for Laravel's own command.
 */
class UnattendedMigrateCommand extends MigrateCommand
{
    protected function getDefaultConfirmCallback(): Closure
    {
        $isProduction = parent::getDefaultConfirmCallback();

        return fn (): bool => $this->canAskQuestions() && $isProduction();
    }

    private function canAskQuestions(): bool
    {
        if (config('database.migrations.skip_production_prompt')) {
            return false;
        }

        return $this->input->isInteractive()
            && defined('STDIN')
            && stream_isatty(STDIN);
    }
}
