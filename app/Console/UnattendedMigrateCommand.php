<?php

namespace App\Console;

use Closure;
use Illuminate\Database\Console\Migrations\MigrateCommand;

/**
 * `php artisan migrate` that does not stop to ask "Are you sure?" in
 * production when nobody is there to answer.
 *
 * Hosts such as Wasmer run a plain `php artisan migrate` after each deploy,
 * without `--force` and without a terminal. Laravel's prompt then defaults to
 * "no" and the tables are never created. When a person runs the command in a
 * real terminal, the usual production confirmation still appears.
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
        return $this->input->isInteractive()
            && defined('STDIN')
            && stream_isatty(STDIN);
    }
}
