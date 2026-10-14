<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Run once after putting real SMTP details in .env, to see that mail works.
 */
#[Signature('sofra:mail-test {email : Where to send the test message}')]
#[Description('Send a test email to check the mail settings')]
class MailTest extends Command
{
    public function handle(): int
    {
        $mailer = config('mail.default');

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER is "log": messages are only written to the log file, nobody receives them.');
        }

        try {
            Mail::raw('If you can read this, Sofra can send email.', fn (Message $message) => $message
                ->to($this->argument('email'))
                ->subject('Sofra test email'));
        } catch (Throwable $exception) {
            $this->error('Sending failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent with the \"{$mailer}\" mailer to {$this->argument('email')}.");

        return self::SUCCESS;
    }
}
