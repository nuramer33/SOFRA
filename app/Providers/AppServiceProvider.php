<?php

namespace App\Providers;

use App\Console\UnattendedMigrateCommand;
use App\Tenancy\CurrentShop;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One CurrentShop per request (scoped = reset between requests/jobs).
        $this->app->scoped(CurrentShop::class);

        // GD ships with PHP everywhere (no Imagick needed on the VPS).
        // autoOrientation: phone photos come out the right way up.
        // decodeAnimation off: only the first frame is ever decoded.
        $this->app->singleton(ImageManager::class, fn () => ImageManager::usingDriver(
            GdDriver::class,
            autoOrientation: true,
            decodeAnimation: false,
            strip: true,
        ));

        // Deploy hooks run `php artisan migrate` with no terminal to answer
        // the production prompt; see UnattendedMigrateCommand.
        $this->app->extend(MigrateCommand::class, fn (MigrateCommand $command, $app) => new UnattendedMigrateCommand(
            $app['migrator'],
            $app[Dispatcher::class],
        ));
    }

    private static function mailLocale(object $user): string
    {
        return $user->locale ?? app()->getLocale();
    }

    /**
     * The shared top of an account email: greeting, subject and one intro line.
     *
     * @param  'verify'|'reset'  $kind
     */
    private static function accountMail(object $user, string $kind): MailMessage
    {
        $locale = self::mailLocale($user);

        return (new MailMessage)
            ->subject(__("account.mail.{$kind}_subject", [], $locale))
            ->greeting(__('account.mail.greeting', ['name' => $user->name], $locale))
            ->line(__("account.mail.{$kind}_line", [], $locale))
            ->salutation(__('account.mail.salutation', [], $locale));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // In development, shout about N+1 queries and silently-ignored
        // attributes instead of letting them slip into production.
        // Links, redirects and signed email URLs must be https:// in production.
        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->letters()->numbers()->uncompromised()
            : Password::min(8));

        // A product written only in Arabic still shows its Arabic name on the
        // Turkish menu, instead of an empty title.
        Translatable::fallback(fallbackAny: true);

        // Account emails in the owner's own language (Laravel's defaults are English).
        VerifyEmail::toMailUsing(fn (object $user, string $url) => self::accountMail($user, 'verify')->action(__('account.mail.verify_action', [], self::mailLocale($user)), $url)
            ->line(__('account.mail.verify_ignore', [], self::mailLocale($user))));

        ResetPassword::toMailUsing(function (object $user, string $token) {
            $locale = self::mailLocale($user);
            $url = url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return self::accountMail($user, 'reset')->action(__('account.mail.reset_action', [], $locale), $url)
                ->line(__('account.mail.reset_expire', ['count' => $minutes], $locale))
                ->line(__('account.mail.reset_ignore', [], $locale));
        });

        // Remember each account's last login (normal login, sign-up and
        // "remember me" all fire this event). Shown in the admin panel.
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
    }
}
