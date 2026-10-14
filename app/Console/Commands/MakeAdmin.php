<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * The ONLY way to create a platform admin. There is deliberately no web
 * form for this: whoever can run commands on the server is already trusted.
 */
#[Signature('sofra:admin {email : Email of the admin account}')]
#[Description('Create a platform admin, or give admin rights to an existing account')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user !== null) {
            if ($user->isAdmin()) {
                $this->info("{$email} is already an admin.");

                return self::SUCCESS;
            }

            if (! confirm("{$email} already exists (a shop owner). Give this account admin rights?", default: false)) {
                return self::FAILURE;
            }
        } else {
            $user = new User([
                'email' => $email,
                'name' => text('Name', required: true),
                'password' => password('Password (min. 12 characters)', required: true, validate: fn (string $value) => Validator::make(
                    ['password' => $value],
                    ['password' => ['required', Password::min(12)->letters()->numbers()]],
                )->errors()->first('password') ?: null),
                'locale' => 'ar',
            ]);
            $user->email_verified_at = now();
        }

        $user->is_admin = true;
        $user->save();

        $this->info("Admin ready: {$email} — log in and open /admin");

        return self::SUCCESS;
    }
}
