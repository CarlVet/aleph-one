<?php

namespace App\Console\Commands;

use App\Mail\VerificationEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-verification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email verification functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $verificationCode = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $userName = 'Test User';

        $this->info("Sending verification email to: {$email}");
        $this->info("Verification code: {$verificationCode}");

        try {
            Mail::to($email)->send(new VerificationEmail($verificationCode, $userName));
            $this->info('Email sent successfully! Check the logs for email content.');
        } catch (\Exception $e) {
            $this->error('Failed to send email: '.$e->getMessage());
        }
    }
}
