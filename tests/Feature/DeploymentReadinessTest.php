<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDirectory = storage_path('framework/testing/backups');
        File::deleteDirectory($this->backupDirectory);
        config(['sofra.backup.path' => $this->backupDirectory]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDirectory);

        parent::tearDown();
    }

    public function test_pages_carry_security_headers(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_sent_only_over_https_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
    }

    public function test_friendly_error_pages_render_in_the_visitors_language(): void
    {
        foreach ([403, 419, 429, 500, 503] as $status) {
            Route::middleware('web')->get("/__fail-{$status}", fn () => abort($status));

            $this->withHeader('Accept-Language', 'en')->get("/__fail-{$status}")
                ->assertStatus($status)
                ->assertSee(__("errors.{$status}.title", [], 'en'), false);
        }
    }

    public function test_unknown_shop_shows_the_404_page(): void
    {
        $this->get('/no-such-shop')->assertNotFound()->assertSee(__('app.errors.404_title', [], 'ar'));
    }

    public function test_backup_saves_a_compressed_dump_and_the_uploads(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('shops/1/product/a.webp', 'img');
        Process::fake(function ($process) {
            $argument = collect($process->command)->first(fn ($part) => str_starts_with($part, '--result-file='));
            file_put_contents(substr($argument, strlen('--result-file=')), '-- fake dump');

            return Process::result();
        });

        $this->artisan('sofra:backup')->assertSuccessful();

        $names = collect(File::files($this->backupDirectory))->map->getFilename();
        $this->assertTrue($names->contains(fn ($name) => str_starts_with($name, 'db-') && str_ends_with($name, '.sql.gz')));
        $this->assertTrue($names->contains(fn ($name) => str_starts_with($name, 'files-') && str_ends_with($name, '.zip')));
        $this->assertCount(2, $names); // the plain .sql is removed after compressing
    }

    public function test_backup_fails_loudly_when_the_dump_fails(): void
    {
        Process::fake(fn () => Process::result(output: '', errorOutput: 'access denied', exitCode: 2));

        $this->artisan('sofra:backup')->assertFailed();

        $this->assertSame([], File::files($this->backupDirectory));
    }

    public function test_old_backups_are_deleted(): void
    {
        File::ensureDirectoryExists($this->backupDirectory);
        $old = "{$this->backupDirectory}/db-2020-01-01_000000.sql.gz";
        $stranger = "{$this->backupDirectory}/notes.txt";
        file_put_contents($old, 'x');
        file_put_contents($stranger, 'x');
        touch($old, now()->subDays(30)->getTimestamp());
        touch($stranger, now()->subDays(30)->getTimestamp());
        Process::fake(function ($process) {
            $argument = collect($process->command)->first(fn ($part) => str_starts_with($part, '--result-file='));
            file_put_contents(substr($argument, strlen('--result-file=')), '-- fake dump');

            return Process::result();
        });

        $this->artisan('sofra:backup')->assertSuccessful();

        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($stranger); // only our own backup files are ever touched
    }

    public function test_unattended_migrate_runs_in_production_without_force(): void
    {
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('migrate', ['--no-interaction' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringNotContainsString('APPLICATION IN PRODUCTION', Artisan::output());
    }

    public function test_mail_test_command_sends_a_message(): void
    {
        config(['mail.default' => 'array']);

        $this->artisan('sofra:mail-test', ['email' => 'me@example.com'])->assertSuccessful();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame('me@example.com', $messages[0]->getEnvelope()->getRecipients()[0]->getAddress());
    }
}
